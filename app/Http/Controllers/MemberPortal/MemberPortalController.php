<?php

namespace App\Http\Controllers\MemberPortal;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Updates\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MemberPortalController extends Controller
{
    public function loginForm(Request $request)
    {
        if ($request->session()->has('member_portal.member_id')) return redirect()->route('member-portal.dashboard');
        return view('member-portal.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['member_number' => ['required', 'string', 'max:100'], 'national_id' => ['required', 'string', 'max:100']]);
        $number = trim($data['member_number']);
        $nationalId = Member::normalizeNationalId($data['national_id']);
        $key = 'member-portal-login:' . sha1($request->ip() . '|' . Str::lower($number));

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['member_number' => 'Too many login attempts. Please try again later.'])->onlyInput('member_number');
        }

        $matches = Member::query()->where(function ($query) use ($number) {
            $query->where('member_number', $number)->orWhere('penad_member_number', $number);
        })->limit(2)->get();

        // Never guess between duplicate member numbers.
        $member = $matches->count() === 1 ? $matches->first() : null;
        $storedId = $member ? (Member::normalizeNationalId($member->national_id_normalized) ?: Member::normalizeNationalId($member->national_id)) : null;

        if (!$member || !$nationalId || !$storedId || !hash_equals($storedId, $nationalId)) {
            RateLimiter::hit($key, 300);
            return back()->withErrors(['member_number' => 'Invalid member number or National ID.'])->onlyInput('member_number');
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('member_portal.member_id', $member->id);
        return redirect()->route('member-portal.dashboard');
    }

    public function dashboard(Request $request)
    {
        $member = $this->member($request);
        $statements = DB::table('member_benefit_statements as s')
            ->join('benefit_statement_bulk_runs as r', 'r.id', '=', 's.bulk_run_id')
            ->where('s.member_id', $member->id)
            ->where('r.status', 'completed')
            ->whereNotNull('r.pdf_path')
            ->orderByDesc('s.statement_date')->orderByDesc('s.id')
            ->select('s.id', 's.statement_date', 's.created_at')->get();

        return view('member-portal.dashboard', compact('member', 'statements'));
    }

    public function statement(Request $request, int $id): BinaryFileResponse
    {
        $member = $this->member($request);
        $statement = DB::table('member_benefit_statements as s')
            ->join('benefit_statement_bulk_runs as r', 'r.id', '=', 's.bulk_run_id')
            ->where('s.id', $id)->where('s.member_id', $member->id)
            ->where('r.status', 'completed')->whereNotNull('r.pdf_path')
            ->select('s.pdf_path', 's.statement_date')->first();
        abort_unless($statement && Storage::disk('local')->exists($statement->pdf_path), 404);

        $filename = 'Benefit_Statement_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $member->penad_member_number ?: $member->member_number) . '_' . date('Ymd', strtotime($statement->statement_date)) . '.pdf';
        $disposition = $request->query('action') === 'download' ? 'attachment' : 'inline';
        return response()->file(Storage::disk('local')->path($statement->pdf_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('member_portal');
        $request->session()->regenerateToken();
        return redirect()->route('member-portal.login');
    }

    private function member(Request $request): Member
    {
        $member = Member::find($request->session()->get('member_portal.member_id'));
        if (!$member) { $request->session()->forget('member_portal'); abort(403); }
        return $member;
    }
}

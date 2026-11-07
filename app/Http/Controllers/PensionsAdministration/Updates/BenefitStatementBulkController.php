<?php

namespace App\Http\Controllers\PensionsAdministration\Updates;

use App\Http\Controllers\Controller;
use App\Jobs\BenefitStatements\PrepareBulkBenefitStatements;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BenefitStatementBulkController extends Controller
{
    public function index()
    {
        $employers = DB::table('employers')
            ->orderBy('name')
            ->get(['id', 'name']);

        $recentRuns = DB::table('benefit_statement_bulk_runs')
            ->where('requested_by', auth()->id())
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view(
            'pensions-administration.updates.benefit-statements.bulk',
            compact('employers', 'recentRuns')
        );
    }

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employer_id' => ['required', 'integer', 'exists:employers,id'],
            'statement_date' => ['required', 'date', 'before_or_equal:today'],
            'action' => ['required', 'in:download,print'],
        ]);

        $employer = DB::table('employers')
            ->where('id', $data['employer_id'])
            ->first(['id', 'name']);

        $id = (string) Str::uuid();

        DB::table('benefit_statement_bulk_runs')->insert([
            'id' => $id,
            'employer_id' => $employer->id,
            'employer_name' => $employer->name,
            'statement_date' => Carbon::parse($data['statement_date'])->toDateString(),
            'requested_by' => auth()->id(),
            'requested_action' => $data['action'],
            'status' => 'queued',
            'total_members' => 0,
            'processed_members' => 0,
            'failed_members' => 0,
            'total_batches' => 0,
            'completed_batches' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        PrepareBulkBenefitStatements::dispatch($id);

        return response()->json([
            'run_id' => $id,
            'status' => 'queued',
            'status_url' => route(
                'pensions-administration.updates.benefit-statements.bulk.status',
                ['id' => $id]
            ),
        ]);
    }

    public function status(string $id): JsonResponse
    {
        $run = $this->ownedRun($id);

        $total = (int) $run->total_members;
        $processed = (int) $run->processed_members;
        $failed = (int) $run->failed_members;

        $percent = $total > 0
            ? min(100, max(0, (int) floor(($processed / $total) * 100)))
            : 0;

        if ($run->status === 'completed') {
            $percent = 100;
        }

        $downloadUrl = null;
        $printUrl = null;

        if ($run->status === 'completed') {
            $downloadUrl = route(
                'pensions-administration.updates.benefit-statements.bulk.file',
                ['id' => $run->id, 'action' => 'download']
            );

            $printUrl = route(
                'pensions-administration.updates.benefit-statements.bulk.file',
                ['id' => $run->id, 'action' => 'print']
            );
        }

        return response()->json([
            'id' => $run->id,
            'status' => $run->status,
            'employer' => $run->employer_name,
            'total' => $total,
            'processed' => $processed,
            'failed' => $failed,
            'percent' => $percent,
            'message' => $run->error_message,
            'download_url' => $downloadUrl,
            'print_url' => $printUrl,
        ]);
    }

    public function file(Request $request, string $id): BinaryFileResponse
    {
        $run = $this->ownedRun($id);

        abort_unless(
            $run->status === 'completed'
            && !empty($run->pdf_path)
            && Storage::disk('local')->exists($run->pdf_path),
            404
        );

        $filename = 'Benefit_Statements_'
            . Str::slug($run->employer_name, '_')
            . '_'
            . Carbon::parse($run->statement_date)->format('Ymd')
            . '.pdf';

        $disposition = $request->query('action') === 'print'
            ? 'inline'
            : 'attachment';

        return response()->file(
            Storage::disk('local')->path($run->pdf_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
            ]
        );
    }

    private function ownedRun(string $id): object
    {
        abort_unless(
            Str::isUuid($id),
            404
        );

        $run = DB::table('benefit_statement_bulk_runs')
            ->whereRaw('LOWER(id) = ?', [strtolower($id)])
            ->where('requested_by', auth()->id())
            ->first();

        abort_unless($run, 404);

        return $run;
    }
}
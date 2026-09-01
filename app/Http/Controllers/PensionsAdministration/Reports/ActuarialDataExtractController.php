<?php

namespace App\Http\Controllers\PensionsAdministration\Reports;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateActuarialDataExtract;
use App\Models\PensionsAdministration\Reports\ActuarialDataExtractBatch;
use App\Models\PensionsAdministration\Updates\Employer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActuarialDataExtractController extends Controller
{
    public function index(): View
    {
        $employers = Employer::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get([
                'id',
                'employer_number',
                'penad_employer_number',
                'name',
            ]);

        $batches = ActuarialDataExtractBatch::query()
            ->with('employer:id,name')
            ->where('requested_by', auth()->id())
            ->latest('id')
            ->limit(20)
            ->get();

        return view(
            'pensions-administration.reports.actuarial-data.index',
            compact('employers', 'batches')
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'employer_id' => ['nullable', 'integer', 'exists:employers,id'],
        ]);

        $from = Carbon::parse($validated['date_from'])->startOfMonth();
        $to = Carbon::parse($validated['date_to'])->endOfMonth();

        if ($from->diffInMonths($to) > 60) {
            return back()
                ->withInput()
                ->withErrors([
                    'date_to' => 'The actuarial extract is limited to a maximum of 5 years per extract.',
                ]);
        }

        $batch = ActuarialDataExtractBatch::query()->create([
            'batch_number' => $this->batchNumber(),
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'employer_id' => $validated['employer_id'] ?? null,
            'status' => 'queued',
            'progress_percentage' => 0,
            'requested_by' => auth()->id(),
        ]);

        GenerateActuarialDataExtract::dispatch($batch->id)
            ->onQueue('actuarial-data');

        return redirect()
            ->route('pensions-administration.reports.actuarial-data.show', $batch)
            ->with('success', 'Actuarial data extract has been queued.');
    }

    public function show(ActuarialDataExtractBatch $batch): View
    {
        $this->authorizeBatch($batch);

        $batch->load('employer:id,name');

        return view(
            'pensions-administration.reports.actuarial-data.show',
            compact('batch')
        );
    }

    public function status(ActuarialDataExtractBatch $batch): JsonResponse
    {
        $this->authorizeBatch($batch);

        $batch->refresh();

        return response()->json([
            'status' => $batch->status,
            'progress_percentage' => (float) $batch->progress_percentage,
            'active_members' => (int) $batch->active_members,
            'nil_contributors' => (int) $batch->nil_contributors,
            'exited_members' => (int) $batch->exited_members,
            'failure_reason' => $batch->failure_reason,
            'download_url' => $batch->status === 'completed'
                ? route(
                    'pensions-administration.reports.actuarial-data.download',
                    $batch
                )
                : null,
        ]);
    }

    public function download(
        ActuarialDataExtractBatch $batch
    ): BinaryFileResponse {
        $this->authorizeBatch($batch);

        abort_unless(
            $batch->status === 'completed'
            && $batch->file_path
            && Storage::disk('local')->exists($batch->file_path),
            404
        );

        return response()->download(
            Storage::disk('local')->path($batch->file_path),
            $batch->file_name
        );
    }

    private function authorizeBatch(
        ActuarialDataExtractBatch $batch
    ): void {
        abort_unless(
            (int) $batch->requested_by === (int) auth()->id()
            || auth()->user()?->hasRole('system-administrator'),
            403
        );
    }

    private function batchNumber(): string
    {
        return 'ACT-'
            . now()->format('Ymd-His')
            . '-'
            . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }
}
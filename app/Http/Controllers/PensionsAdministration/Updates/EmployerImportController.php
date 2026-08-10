<?php

namespace App\Http\Controllers\PensionsAdministration\Updates;

use App\Http\Controllers\Controller;
use App\Jobs\PensionsAdministration\Updates\ImportApprovedEmployers;
use App\Jobs\PensionsAdministration\Updates\ProcessEmployerImport;
use App\Models\PensionsAdministration\Updates\EmployerImportBatch;
use App\Models\PensionsAdministration\Updates\EmployerImportRow;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class EmployerImportController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }

    public function index(): View
    {
        $batches = EmployerImportBatch::query()
            ->with('uploadedBy')
            ->orderByDesc('id')
            ->paginate(25);

        return view(
            'pensions-administration.updates.employer-imports.index',
            compact('batches')
        );
    }

    public function create(): View
    {
        return view(
            'pensions-administration.updates.employer-imports.create'
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'import_file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:51200',
            ],
        ]);

        try {
            $file = $request->file('import_file');

            $uuid = (string) Str::uuid();

            $extension = strtolower(
                $file->getClientOriginalExtension()
            );

            $storedFilename = $uuid . '.' . $extension;

            $path = $file->storeAs(
                'employer-imports',
                $storedFilename
            );

            $batch = EmployerImportBatch::create([
                'import_uuid' => $uuid,

                'original_filename' => $file->getClientOriginalName(),

                'stored_filename' => $storedFilename,

                'file_path' => $path,

                'file_extension' => $extension,

                'file_size' => $file->getSize(),

                'import_type' => 'employers',

                'status' => 'uploaded',

                'progress_percentage' => 0,

                'uploaded_by' => auth()->id(),
            ]);

            $this->auditService->log(
                eventType: 'employer_import',
                module: 'Pensions Administration - Updates',
                action: 'UPLOAD_EMPLOYER_IMPORT',
                description: 'Employer import file '
                    . $batch->original_filename
                    . ' was uploaded.',
                auditable: $batch,
                newValues: $this->auditService->values($batch),
                metadata: [
                    'import_uuid' => $batch->import_uuid,
                    'filename' => $batch->original_filename,
                    'file_size' => $batch->file_size,
                ],
                request: $request
            );

            return redirect()
                ->route(
                    'pensions-administration.updates.employer-imports.show',
                    $batch
                )
                ->with(
                    'success',
                    'Employer Excel file uploaded successfully. It is ready for validation.'
                );

        } catch (Throwable $e) {
            $this->auditService->failure(
                eventType: 'employer_import',
                module: 'Pensions Administration - Updates',
                action: 'UPLOAD_EMPLOYER_IMPORT',
                description: 'Employer Excel upload failed.',
                failureReason: $e->getMessage(),
                request: $request
            );

            throw $e;
        }
    }

    public function show(
        EmployerImportBatch $batch
    ): View {
        $batch->load([
            'uploadedBy',
            'approvedBy',
        ]);

        $pendingRows = $batch->rows()
            ->where('review_decision', 'pending')
            ->count();

        return view(
            'pensions-administration.updates.employer-imports.show',
            compact(
                'batch',
                'pendingRows'
            )
        );
    }

    public function validateImport(
        Request $request,
        EmployerImportBatch $batch
    ): RedirectResponse {
        if (!in_array(
            $batch->status,
            [
                'uploaded',
                'failed',
                'awaiting_review',
            ],
            true
        )) {
            return back()->with(
                'error',
                'This employer import is already being processed.'
            );
        }

        $oldValues = $this->auditService->values(
            $batch
        );

        $batch->update([
            'status' => 'processing',
            'failure_reason' => null,
            'progress_percentage' => 0,
        ]);

        $this->auditService->log(
            eventType: 'employer_import',
            module: 'Pensions Administration - Updates',
            action: 'START_EMPLOYER_VALIDATION',
            description: 'Validation was started for employer import '
                . $batch->import_uuid
                . '.',
            auditable: $batch,
            oldValues: $oldValues,
            newValues: $this->auditService->values($batch),
            request: $request
        );

        ProcessEmployerImport::dispatch(
            $batch->id
        )->onQueue('employer-imports');

        return redirect()
            ->route(
                'pensions-administration.updates.employer-imports.show',
                $batch
            )
            ->with(
                'success',
                'Employer validation has started.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Approve All Clean Employer Rows
    |--------------------------------------------------------------------------
    */

    public function approveValid(
        Request $request,
        EmployerImportBatch $batch
    ): RedirectResponse {
        if ($batch->status !== 'awaiting_review') {
            return back()->with(
                'error',
                'This batch is not ready for approval.'
            );
        }

        $rows = EmployerImportRow::query()
            ->where('import_batch_id', $batch->id)
            ->where('validation_status', 'valid')
            ->where('duplicate_status', 'none')
            ->where('review_decision', 'pending')
            ->get();

        if ($rows->isEmpty()) {
            return back()->with(
                'error',
                'There are no clean employer rows waiting for approval.'
            );
        }

        foreach ($rows as $row) {
            $row->update([
                'review_decision' => 'create',

                'review_notes' =>
                    'Automatically approved as a valid non-duplicate employer.',

                'reviewed_by' => auth()->id(),

                'reviewed_at' => now(),
            ]);
        }

        $approvedRows = $batch->rows()
            ->whereIn('review_decision', [
                'create',
                'update',
                'use_existing',
                'ignore_warning',
            ])
            ->count();

        $batch->update([
            'approved_rows' => $approvedRows,

            'approved_by' => auth()->id(),

            'approved_at' => now(),
        ]);

        $this->auditService->log(
            eventType: 'employer_import',
            module: 'Pensions Administration - Updates',
            action: 'APPROVE_VALID_EMPLOYERS',
            description: number_format($rows->count())
                . ' valid employer rows were approved for import.',
            auditable: $batch,
            metadata: [
                'newly_approved_rows' => $rows->count(),
                'total_approved_rows' => $approvedRows,
            ],
            request: $request
        );

        return back()->with(
            'success',
            number_format($rows->count())
            . ' employer records were approved for final import.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Final Import
    |--------------------------------------------------------------------------
    */

    public function importApproved(
        Request $request,
        EmployerImportBatch $batch
    ): RedirectResponse {
        if ($batch->status !== 'awaiting_review') {
            return back()->with(
                'error',
                'This employer batch is not ready for final import.'
            );
        }

        $approved = $batch->rows()
            ->whereIn('review_decision', [
                'create',
                'update',
                'use_existing',
                'ignore_warning',
            ])
            ->whereNull('imported_at')
            ->count();

        if ($approved === 0) {
            return back()->with(
                'error',
                'There are no approved employer records waiting to be imported.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Record Person Authorising Final Import
        |--------------------------------------------------------------------------
        */

        $batch->update([
            'approved_by' => $batch->approved_by
                ?? auth()->id(),

            'approved_at' => $batch->approved_at
                ?? now(),

            'status' => 'importing',

            'progress_percentage' => 0,

            'failure_reason' => null,
        ]);

        $this->auditService->log(
            eventType: 'employer_import',
            module: 'Pensions Administration - Updates',
            action: 'START_FINAL_EMPLOYER_IMPORT',
            description: 'Final import started for employer batch '
                . $batch->import_uuid
                . '.',
            auditable: $batch,
            metadata: [
                'approved_records' => $approved,
            ],
            request: $request
        );

        ImportApprovedEmployers::dispatch(
            $batch->id
        )->onQueue('employer-imports');

        return redirect()
            ->route(
                'pensions-administration.updates.employer-imports.show',
                $batch
            )
            ->with(
                'success',
                'Approved employer records are being imported.'
            );
    }

    public function status(
        EmployerImportBatch $batch
    ): JsonResponse {
        $batch->refresh();

        $pendingRows = $batch->rows()
            ->where('review_decision', 'pending')
            ->count();

        return response()->json([
            'status' => $batch->status,

            'status_label' => $batch->status_label,

            'progress_percentage' =>
                (float) $batch->progress_percentage,

            'total_rows' =>
                $batch->total_rows,

            'processed_rows' =>
                $batch->processed_rows,

            'valid_rows' =>
                $batch->valid_rows,

            'warning_rows' =>
                $batch->warning_rows,

            'error_rows' =>
                $batch->error_rows,

            'duplicate_rows' =>
                $batch->duplicate_rows,

            'approved_rows' =>
                $batch->approved_rows,

            'rejected_rows' =>
                $batch->rejected_rows,

            'imported_rows' =>
                $batch->imported_rows,

            'pending_rows' =>
                $pendingRows,

            'failure_reason' =>
                $batch->failure_reason,

            'review_url' => route(
                'pensions-administration.updates.employer-imports.review',
                $batch
            ),
        ]);
    }

    public function review(
        Request $request,
        EmployerImportBatch $batch
    ): View {
        $query = EmployerImportRow::query()
            ->with([
                'matchedEmployerGroup',
                'matchedEmployer',
                'importedEmployer',
            ])
            ->where('import_batch_id', $batch->id)
            ->orderBy('row_number');

        if ($request->filled('status')) {
            $query->where(
                'validation_status',
                $request->input('status')
            );
        }

        if ($request->filled('duplicate')) {
            $query->where(
                'duplicate_status',
                $request->input('duplicate')
            );
        }

        if ($request->filled('decision')) {
            $query->where(
                'review_decision',
                $request->input('decision')
            );
        }

        $rows = $query
            ->paginate(50)
            ->withQueryString();

        $counts = [
            'total' => $batch->rows()->count(),

            'approved' => $batch->rows()
                ->whereIn('review_decision', [
                    'create',
                    'update',
                    'use_existing',
                    'ignore_warning',
                ])
                ->count(),

            'pending' => $batch->rows()
                ->where('review_decision', 'pending')
                ->count(),

            'rejected' => $batch->rows()
                ->where('review_decision', 'reject')
                ->count(),

            'imported' => $batch->rows()
                ->whereNotNull('imported_at')
                ->count(),
        ];

        return view(
            'pensions-administration.updates.employer-imports.review',
            compact(
                'batch',
                'rows',
                'counts'
            )
        );
    }

    public function destroy(
        Request $request,
        EmployerImportBatch $batch
    ): RedirectResponse {
        if (in_array(
            $batch->status,
            [
                'processing',
                'validating',
                'duplicate_checking',
                'importing',
                'completed',
            ],
            true
        )) {
            return back()->with(
                'error',
                'This employer import cannot be cancelled at its current stage.'
            );
        }

        $oldValues = $this->auditService->values(
            $batch
        );

        $batch->update([
            'status' => 'cancelled',
        ]);

        $this->auditService->log(
            eventType: 'employer_import',
            module: 'Pensions Administration - Updates',
            action: 'CANCEL_EMPLOYER_IMPORT',
            description: 'Employer import '
                . $batch->import_uuid
                . ' was cancelled.',
            auditable: $batch,
            oldValues: $oldValues,
            newValues: $this->auditService->values($batch),
            request: $request
        );

        return redirect()
            ->route(
                'pensions-administration.updates.employer-imports.create'
            )
            ->with(
                'success',
                'The previous employer import was cancelled. Upload the corrected file.'
            );
    }
}
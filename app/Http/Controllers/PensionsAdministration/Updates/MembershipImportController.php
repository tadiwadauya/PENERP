<?php

namespace App\Http\Controllers\PensionsAdministration\Updates;

use App\Http\Controllers\Controller;
use App\Jobs\PensionsAdministration\Updates\ProcessMembershipImport;
use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\MembershipImportBatch;
use App\Models\PensionsAdministration\Updates\MembershipImportRow;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class MembershipImportController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }

    public function index(): View
    {
        $batches = MembershipImportBatch::query()
            ->with(['employer', 'uploadedBy'])
            ->orderByDesc('id')
            ->paginate(25);

        return view(
            'pensions-administration.updates.imports.index',
            compact('batches')
        );
    }

    public function create(): View
    {
        $employers = Employer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'pensions-administration.updates.imports.create',
            compact('employers')
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'import_file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:51200',
            ],

            'employer_id' => [
                'nullable',
                'exists:employers,id',
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
                'membership-imports',
                $storedFilename
            );

            $batch = MembershipImportBatch::create([
                'import_uuid' => $uuid,
                'original_filename' => $file->getClientOriginalName(),
                'stored_filename' => $storedFilename,
                'file_path' => $path,
                'file_extension' => $extension,
                'file_size' => $file->getSize(),
                'import_type' => 'static_membership',
                'employer_id' => $validated['employer_id'] ?? null,
                'status' => 'uploaded',
                'progress_percentage' => 0,
                'uploaded_by' => auth()->id(),
            ]);

            $this->auditService->log(
                eventType: 'membership_import',
                module: 'Pensions Administration - Updates',
                action: 'UPLOAD_MEMBERSHIP_IMPORT',
                description: 'Membership import file ' . $batch->original_filename . ' was uploaded.',
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
                    'pensions-administration.updates.imports.show',
                    $batch
                )
                ->with(
                    'success',
                    'Excel file uploaded successfully. It is ready for validation.'
                );
        } catch (Throwable $e) {
            $this->auditService->failure(
                eventType: 'membership_import',
                module: 'Pensions Administration - Updates',
                action: 'UPLOAD_MEMBERSHIP_IMPORT',
                description: 'Membership Excel upload failed.',
                failureReason: $e->getMessage(),
                request: $request
            );

            throw $e;
        }
    }

    public function show(
        MembershipImportBatch $batch
    ): View {
        $batch->load([
            'employer',
            'uploadedBy',
            'approvedBy',
        ]);

        return view(
            'pensions-administration.updates.imports.show',
            compact('batch')
        );
    }

    public function validateImport(
        Request $request,
        MembershipImportBatch $batch
    ): RedirectResponse {
        if (!in_array(
            $batch->status,
            ['uploaded', 'failed', 'awaiting_review'],
            true
        )) {
            return back()->with(
                'error',
                'This import is already being processed.'
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
            eventType: 'membership_import',
            module: 'Pensions Administration - Updates',
            action: 'START_MEMBERSHIP_VALIDATION',
            description: 'Validation was started for membership import ' . $batch->import_uuid . '.',
            auditable: $batch,
            oldValues: $oldValues,
            newValues: $this->auditService->values($batch),
            request: $request
        );

        ProcessMembershipImport::dispatch(
            $batch->id
        )->onQueue('membership-imports');

        return redirect()
            ->route(
                'pensions-administration.updates.imports.show',
                $batch
            )
            ->with(
                'success',
                'Validation has started.'
            );
    }

    public function status(
        MembershipImportBatch $batch
    ): JsonResponse {
        $batch->refresh();

        return response()->json([
            'status' => $batch->status,
            'status_label' => $batch->status_label,
            'progress_percentage' => (float) $batch->progress_percentage,
            'total_rows' => $batch->total_rows,
            'processed_rows' => $batch->processed_rows,
            'valid_rows' => $batch->valid_rows,
            'warning_rows' => $batch->warning_rows,
            'error_rows' => $batch->error_rows,
            'duplicate_rows' => $batch->duplicate_rows,
            'failure_reason' => $batch->failure_reason,
            'review_url' => route(
                'pensions-administration.updates.imports.review',
                $batch
            ),
        ]);
    }

    public function review(
        Request $request,
        MembershipImportBatch $batch
    ): View {
        $query = MembershipImportRow::query()
            ->with([
                'matchedEmployer',
                'matchedMember',
            ])
            ->where(
                'import_batch_id',
                $batch->id
            )
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

        $rows = $query
            ->paginate(50)
            ->withQueryString();

        return view(
            'pensions-administration.updates.imports.review',
            compact(
                'batch',
                'rows'
            )
        );
    }

    public function destroy(
        Request $request,
        MembershipImportBatch $batch
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
                'This import cannot be cancelled at its current stage.'
            );
        }

        $oldValues = $this->auditService->values(
            $batch
        );

        $batch->update([
            'status' => 'cancelled',
        ]);

        $this->auditService->log(
            eventType: 'membership_import',
            module: 'Pensions Administration - Updates',
            action: 'CANCEL_MEMBERSHIP_IMPORT',
            description: 'Membership import ' . $batch->import_uuid . ' was cancelled.',
            auditable: $batch,
            oldValues: $oldValues,
            newValues: $this->auditService->values($batch),
            request: $request
        );


            return redirect()
    ->route('pensions-administration.updates.imports.create')
    ->with(
        'success',
        'The previous membership import was cancelled. Upload the corrected Excel file.'
    );
    }
}
<?php

namespace App\Http\Controllers\PensionsAdministration\HistoricalContributions;

use App\Http\Controllers\Controller;
use App\Jobs\PensionsAdministration\HistoricalContributions\ProcessHistoricalContributionImport;
use App\Models\PensionsAdministration\HistoricalContributions\HistoricalContributionImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class HistoricalContributionImportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(): View
    {
        $this->ensurePermission(
            'contributions.monthly-imports.view'
        );

        $batches = HistoricalContributionImportBatch::query()
            ->with([
                'uploadedBy',
                'approvedBy',
                'postedBy',
            ])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view(
            'pensions-administration.historical-contributions.imports.index',
            compact(
                'batches'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(): View
    {
        $this->ensurePermission(
            'contributions.monthly-imports.create'
        );

        return view(
            'pensions-administration.historical-contributions.imports.create'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ): RedirectResponse {
        $this->ensurePermission(
            'contributions.monthly-imports.create'
        );

        /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:102400',
            ],
        ], [
            'file.required' =>
                'Please select a historical contribution Excel workbook.',

            'file.mimes' =>
                'The historical contribution file must be an Excel workbook.',

            'file.max' =>
                'The historical contribution workbook may not exceed 100 MB.',
        ]);

        try {
            $file = $request->file(
                'file'
            );

            if (
                !$file
                ||
                !$file->isValid()
            ) {
                throw new RuntimeException(
                    'The historical contribution Excel file could not be received.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Import UUID
            |--------------------------------------------------------------------------
            */

            $uuid = (string) Str::uuid();

            /*
            |--------------------------------------------------------------------------
            | Original File
            |--------------------------------------------------------------------------
            */

            $originalFilename =
                $file->getClientOriginalName();

            $extension =
                strtolower(
                    $file->getClientOriginalExtension()
                    ?: 'xlsx'
                );

            $storedFilename =
                strtolower(
                    $uuid
                )
                . '.'
                . $extension;

            /*
            |--------------------------------------------------------------------------
            | Store File
            |--------------------------------------------------------------------------
            */

            $directory =
                'historical-contribution-imports';

            $filePath =
                $file->storeAs(
                    $directory,
                    $storedFilename,
                    'local'
                );

            if (!$filePath) {
                throw new RuntimeException(
                    'PENERP could not store the historical contribution workbook.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Physical File
            |--------------------------------------------------------------------------
            */

            $physicalPath =
                Storage::disk('local')
                    ->path(
                        $filePath
                    );

            /*
            |--------------------------------------------------------------------------
            | File Hash
            |--------------------------------------------------------------------------
            */

            $fileHash =
                hash_file(
                    'sha256',
                    $physicalPath
                );

            /*
            |--------------------------------------------------------------------------
            | Prevent Accidental Duplicate Active Upload
            |--------------------------------------------------------------------------
            |
            | Re-upload is allowed when the earlier batch was:
            |
            | failed
            | rejected
            | cancelled
            |
            */

            $existingBatch = HistoricalContributionImportBatch::query()
                ->where(
                    'file_hash',
                    $fileHash
                )
                ->whereNotIn(
                    'status',
                    [
                        'failed',
                        'rejected',
                        'cancelled',
                    ]
                )
                ->latest('id')
                ->first();

            if ($existingBatch) {
                Storage::disk('local')
                    ->delete(
                        $filePath
                    );

                return redirect()
                    ->route(
                        'pensions-administration.historical-contributions.imports.show',
                        $existingBatch
                    )
                    ->with(
                        'warning',
                        'The same historical contribution workbook has already been uploaded.'
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Create Batch
            |--------------------------------------------------------------------------
            */

            $batch = HistoricalContributionImportBatch::query()
                ->create([
                    'import_uuid' =>
                        $uuid,

                    'original_filename' =>
                        $originalFilename,

                    'stored_filename' =>
                        $storedFilename,

                    'file_path' =>
                        $filePath,

                    'file_extension' =>
                        $extension,

                    'file_size' =>
                        $file->getSize(),

                    'file_hash' =>
                        $fileHash,

                    'source_system' =>
                        'legacy_historical_excel',

                    /*
                    |--------------------------------------------------------------------------
                    | Migration Scope
                    |--------------------------------------------------------------------------
                    */

                    'start_year' =>
                        2009,

                    'start_month' =>
                        1,

                    'end_year' =>
                        2023,

                    'end_month' =>
                        10,

                    /*
                    |--------------------------------------------------------------------------
                    | Workflow
                    |--------------------------------------------------------------------------
                    */

                    'status' =>
                        'queued',

                    'progress_percentage' =>
                        0,

                    'failure_reason' =>
                        null,

                    /*
                    |--------------------------------------------------------------------------
                    | User
                    |--------------------------------------------------------------------------
                    */

                    'uploaded_by' =>
                        auth()->id(),
                ]);

            /*
            |--------------------------------------------------------------------------
            | Queue Validation
            |--------------------------------------------------------------------------
            */

            ProcessHistoricalContributionImport::dispatch(
                $batch->id
            );

            /*
            |--------------------------------------------------------------------------
            | Progress Page
            |--------------------------------------------------------------------------
            */

            return redirect()
                ->route(
                    'pensions-administration.historical-contributions.imports.show',
                    $batch
                )
                ->with(
                    'success',
                    'Historical contribution workbook uploaded successfully. Validation has started.'
                );

        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show / Progress
    |--------------------------------------------------------------------------
    */

    public function show(
        HistoricalContributionImportBatch $batch
    ): View {
        $this->ensurePermission(
            'contributions.monthly-imports.view'
        );

        $batch->load([
            'uploadedBy',
            'approvedBy',
            'postedBy',
        ]);

        return view(
            'pensions-administration.historical-contributions.imports.show',
            compact(
                'batch'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Status JSON
    |--------------------------------------------------------------------------
    */

    public function status(
        HistoricalContributionImportBatch $batch
    ): JsonResponse {
        $this->ensurePermission(
            'contributions.monthly-imports.view'
        );

        $batch->refresh();

        return response()->json([
            'id' =>
                $batch->id,

            'status' =>
                $batch->status,

            'status_label' =>
                $this->statusLabel(
                    $batch->status
                ),

            'progress_percentage' =>
                (float) $batch->progress_percentage,

            'posted_transaction_rows' =>
                (int) $batch->posted_transaction_rows,

            'posted_service_period_rows' =>
                (int) $batch->posted_service_period_rows,

            'new_members_created' =>
                (int) $batch->new_members_created,

            /*
            |--------------------------------------------------------------------------
            | Source Progress
            |--------------------------------------------------------------------------
            */

            'total_source_rows' =>
                (int) $batch->total_source_rows,

            'processed_source_rows' =>
                (int) $batch->processed_source_rows,

            /*
            |--------------------------------------------------------------------------
            | Transactions
            |--------------------------------------------------------------------------
            */

            'total_transaction_rows' =>
                (int) $batch->total_transaction_rows,

            'processed_transaction_rows' =>
                (int) $batch->processed_transaction_rows,

            'valid_transaction_rows' =>
                (int) $batch->valid_transaction_rows,

            'warning_transaction_rows' =>
                (int) $batch->warning_transaction_rows,

            'error_transaction_rows' =>
                (int) $batch->error_transaction_rows,

            /*
            |--------------------------------------------------------------------------
            | Duplicates
            |--------------------------------------------------------------------------
            */

            'duplicate_transaction_rows' =>
                (int) $batch->duplicate_transaction_rows,

            /*
            |--------------------------------------------------------------------------
            | Member Matching
            |--------------------------------------------------------------------------
            */

            'matched_member_rows' =>
                (int) $batch->matched_member_rows,

            'new_member_rows' =>
                (int) $batch->new_member_rows,

            'ambiguous_member_rows' =>
                (int) $batch->ambiguous_member_rows,

            'new_members_detected' =>
                (int) $batch->new_members_detected,

            /*
            |--------------------------------------------------------------------------
            | Service History
            |--------------------------------------------------------------------------
            */

            'contributed_periods' =>
                (int) $batch->contributed_periods,

            'zero_contribution_periods' =>
                (int) $batch->zero_contribution_periods,

            'break_in_service_periods' =>
                (int) $batch->break_in_service_periods,

            /*
            |--------------------------------------------------------------------------
            | Failure
            |--------------------------------------------------------------------------
            */

            'failure_reason' =>
                $batch->failure_reason,

            /*
            |--------------------------------------------------------------------------
            | Dates
            |--------------------------------------------------------------------------
            */

            'processing_started_at' =>
                optional(
                    $batch->processing_started_at
                )?->format(
                    'd M Y H:i:s'
                ),

            'validation_completed_at' =>
                optional(
                    $batch->validation_completed_at
                )?->format(
                    'd M Y H:i:s'
                ),

            /*
            |--------------------------------------------------------------------------
            | URLs
            |--------------------------------------------------------------------------
            */

            'show_url' =>
                route(
                    'pensions-administration.historical-contributions.imports.show',
                    $batch
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Unposted Batch
    |--------------------------------------------------------------------------
    */

    public function destroy(
        HistoricalContributionImportBatch $batch
    ): RedirectResponse {
        $this->ensurePermission(
            'contributions.monthly-imports.delete'
        );

        try {
            if (
                in_array(
                    $batch->status,
                    [
                        'posting',
                        'posted',
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'A posted or currently posting historical contribution batch cannot be deleted.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Delete File
            |--------------------------------------------------------------------------
            */

            if (
                $batch->file_path
                &&
                Storage::disk('local')
                    ->exists(
                        $batch->file_path
                    )
            ) {
                Storage::disk('local')
                    ->delete(
                        $batch->file_path
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Staging Rows Cascade
            |--------------------------------------------------------------------------
            */

            $batch->delete();

            return redirect()
                ->route(
                    'pensions-administration.historical-contributions.imports.index'
                )
                ->with(
                    'success',
                    'Historical contribution import batch deleted successfully.'
                );

        } catch (Throwable $e) {
            return back()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Status Label
    |--------------------------------------------------------------------------
    */

    private function statusLabel(
        string $status
    ): string {
        return match ($status) {
            'uploaded' =>
                'Uploaded',

            'queued' =>
                'Queued',

            'processing' =>
                'Validating',

            'awaiting_review' =>
                'Awaiting Review',

            'approved' =>
                'Approved',

            'posting' =>
                'Posting',

            'posted' =>
                'Posted',

            'failed' =>
                'Validation Failed',

            'posting_failed' =>
                'Posting Failed',

            'rejected' =>
                'Rejected',

            'cancelled' =>
                'Cancelled',

            default =>
                ucwords(
                    str_replace(
                        '_',
                        ' ',
                        $status
                    )
                ),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Permission
    |--------------------------------------------------------------------------
    */

    private function ensurePermission(
        string $permission
    ): void {
        $user = auth()->user();

        abort_if(
            !$user,
            401,
            'Unauthenticated.'
        );

        if ($user->is_system_administrator) {
            return;
        }

        abort_unless(
            $user->can(
                $permission
            ),
            403,
            'You do not have permission to perform this action.'
        );
    }
}
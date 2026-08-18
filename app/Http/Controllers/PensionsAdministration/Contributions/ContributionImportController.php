<?php

namespace App\Http\Controllers\PensionsAdministration\Contributions;

use App\Http\Controllers\Controller;
use App\Jobs\PensionsAdministration\Contributions\ProcessContributionImport;
use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Models\PensionsAdministration\Contributions\ContributionImportRow;
use App\Models\PensionsAdministration\Contributions\ContributionPeriod;
use App\Models\PensionsAdministration\Contributions\ContributionPeriodMemberStatus;
use App\Models\PensionsAdministration\Updates\Employer;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ContributionImportController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Import History
    |--------------------------------------------------------------------------
    */

    public function index(
    Request $request
): View {
    $this->ensurePermission(
        'contributions.monthly-imports.view'
    );


    $query =
        ContributionImportBatch::query()
            ->with([
                'employer',
                'contributionPeriod',
                'uploadedBy',
                'approvedBy',
                'postedBy',
            ])
            ->orderByDesc('id');


    /*
    |--------------------------------------------------------------------------
    | Employer Filter
    |--------------------------------------------------------------------------
    */

    if (
        $request->filled(
            'employer_id'
        )
    ) {
        $query->where(
            'employer_id',
            $request->input(
                'employer_id'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Status Filter
    |--------------------------------------------------------------------------
    */

    if (
        $request->filled(
            'status'
        )
    ) {
        $query->where(
            'status',
            $request->input(
                'status'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Year Filter
    |--------------------------------------------------------------------------
    */

    if (
        $request->filled(
            'year'
        )
    ) {
        $year =
            (int)
            $request->input(
                'year'
            );


        $query->whereHas(
            'contributionPeriod',
            function ($periodQuery) use (
                $year
            ): void {

                $periodQuery->where(
                    'period_year',
                    $year
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Results
    |--------------------------------------------------------------------------
    |
    | DataTables handles:
    |
    | - Pagination
    | - Quick Search
    | - Sorting
    | - Excel Export
    | - CSV Export
    | - PDF Export
    | - Print
    |
    | Therefore Laravel pagination is intentionally NOT used here.
    |
    */

    $batches =
        $query->get();


    /*
    |--------------------------------------------------------------------------
    | Employer Filter Values
    |--------------------------------------------------------------------------
    */

    $employers =
        Employer::query()
            ->where(
                'is_active',
                true
            )
            ->orderBy(
                'name'
            )
            ->get();


    return view(
        'pensions-administration.contributions.imports.index',
        compact(
            'batches',
            'employers'
        )
    );
}


    /*
    |--------------------------------------------------------------------------
    | Upload Form
    |--------------------------------------------------------------------------
    */

    public function create(): View
    {
        $this->ensurePermission(
            'contributions.monthly-imports.create'
        );


        $employers =
            Employer::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->get();


        return view(
            'pensions-administration.contributions.imports.create',
            compact(
                'employers'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store Contribution Upload
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
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validate([
                'employer_id' => [
                    'required',
                    'integer',
                    'exists:employers,id',
                ],

                'period_month' => [
                    'required',
                    'integer',
                    'between:1,12',
                ],

                'period_year' => [
                    'required',
                    'integer',
                    'between:2000,2100',
                ],

                'currency_code' => [
                    'required',
                    'string',
                    'in:ZWG,USD',
                ],

                'due_date' => [
                    'nullable',
                    'date',
                ],

                'scheme_code' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'import_file' => [
                    'required',
                    'file',
                    'mimes:xlsx,xls',
                    'max:51200',
                ],
            ]);


        try {

            $batch =
                DB::transaction(
                    function () use (
                        $request,
                        $validated
                    ): ContributionImportBatch {

                        /*
                        |--------------------------------------------------------------------------
                        | Employer
                        |--------------------------------------------------------------------------
                        */

                        $employer =
                            Employer::query()
                                ->where(
                                    'id',
                                    $validated[
                                        'employer_id'
                                    ]
                                )
                                ->where(
                                    'is_active',
                                    true
                                )
                                ->firstOrFail();


                        /*
                        |--------------------------------------------------------------------------
                        | Contribution Period
                        |--------------------------------------------------------------------------
                        */

                        $periodDate =
                            Carbon::create(
                                (int) $validated[
                                    'period_year'
                                ],
                                (int) $validated[
                                    'period_month'
                                ],
                                1
                            )
                                ->endOfMonth()
                                ->startOfDay();


                        $period =
                            ContributionPeriod::firstOrCreate(
                                [
                                    'employer_id' =>
                                        $employer->id,

                                    'period_year' =>
                                        $periodDate->year,

                                    'period_month' =>
                                        $periodDate->month,
                                ],
                                [
                                    'period_date' =>
                                        $periodDate
                                            ->toDateString(),

                                    'due_date' =>
                                        $validated[
                                            'due_date'
                                        ]
                                        ?? null,

                                    'scheme_code' =>
                                        $validated[
                                            'scheme_code'
                                        ]
                                        ?? null,

                                    'status' =>
                                        'open',

                                    'scheduled_members' =>
                                        0,

                                    'existing_members' =>
                                        0,

                                    'new_members' =>
                                        0,

                                    'nil_contributors' =>
                                        0,

                                    'created_by' =>
                                        auth()->id(),

                                    'updated_by' =>
                                        auth()->id(),
                                ]
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | Existing Active / Completed Import
                        |--------------------------------------------------------------------------
                        |
                        | IMPORTANT:
                        |
                        | We block another schedule ONLY when an existing batch
                        | is still active in the workflow or has already been
                        | successfully posted.
                        |
                        | Failure statuses DO NOT block another upload.
                        |
                        | Therefore:
                        |
                        | BLOCK:
                        | uploaded
                        | processing
                        | awaiting_review
                        | approved
                        | posting
                        | posted
                        |
                        | ALLOW REPLACEMENT:
                        | failed
                        | validation_failed
                        | posting_failed
                        | cancelled
                        |
                        */

                        $blockingBatch =
                            ContributionImportBatch::query()
                                ->where(
                                    'contribution_period_id',
                                    $period->id
                                )
                                ->whereIn(
                                    'status',
                                    [
                                        'uploaded',
                                        'processing',
                                        'awaiting_review',
                                        'approved',
                                        'posting',
                                        'posted',
                                    ]
                                )
                                ->orderByDesc('id')
                                ->first();


                        if ($blockingBatch) {

                            $statusLabel =
                                $blockingBatch
                                    ->status_label;


                            throw new RuntimeException(
                                'A contribution schedule already exists for '
                                . $employer->name
                                . ' for '
                                . $periodDate->format(
                                    'F Y'
                                )
                                . '. Existing batch: #'
                                . $blockingBatch->id
                                . ' ('
                                . $statusLabel
                                . ').'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Uploaded File
                        |--------------------------------------------------------------------------
                        */

                        $file =
                            $request->file(
                                'import_file'
                            );


                        if (!$file) {
                            throw new RuntimeException(
                                'No contribution Excel file was received.'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | File Hash
                        |--------------------------------------------------------------------------
                        */

                        $fileHash =
                            hash_file(
                                'sha256',
                                $file->getRealPath()
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | Duplicate File Check
                        |--------------------------------------------------------------------------
                        |
                        | The exact same file should only be blocked when it is
                        | currently active or was already successfully posted.
                        |
                        | A failed upload may legitimately be submitted again
                        | after the underlying problem has been corrected.
                        |
                        */

                        $duplicateActiveFile =
                            ContributionImportBatch::query()
                                ->where(
                                    'file_hash',
                                    $fileHash
                                )
                                ->whereIn(
                                    'status',
                                    [
                                        'uploaded',
                                        'processing',
                                        'awaiting_review',
                                        'approved',
                                        'posting',
                                        'posted',
                                    ]
                                )
                                ->orderByDesc('id')
                                ->first();


                        if ($duplicateActiveFile) {

                            throw new RuntimeException(
                                'This Excel file is already associated with active or posted contribution batch #'
                                . $duplicateActiveFile->id
                                . '.'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | UUID / Filename
                        |--------------------------------------------------------------------------
                        */

                        $uuid =
                            (string)
                            Str::uuid();


                        $extension =
                            strtolower(
                                $file
                                    ->getClientOriginalExtension()
                            );


                        $storedFilename =
                            $uuid
                            . '.'
                            . $extension;


                        /*
                        |--------------------------------------------------------------------------
                        | Store On Local Disk
                        |--------------------------------------------------------------------------
                        */

                        $path =
                            $file->storeAs(
                                'contribution-imports',
                                $storedFilename,
                                'local'
                            );


                        if (!$path) {
                            throw new RuntimeException(
                                'The contribution Excel file could not be stored.'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Verify Stored File
                        |--------------------------------------------------------------------------
                        */

                        if (
                            !Storage::disk(
                                'local'
                            )->exists(
                                $path
                            )
                        ) {
                            throw new RuntimeException(
                                'The contribution file was uploaded but could not be verified in storage.'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Create Batch
                        |--------------------------------------------------------------------------
                        */

                        $batch =
                            ContributionImportBatch::create([
                                'import_uuid' =>
                                    $uuid,

                                'contribution_period_id' =>
                                    $period->id,

                                'employer_id' =>
                                    $employer->id,

                                'original_filename' =>
                                    $file
                                        ->getClientOriginalName(),

                                'stored_filename' =>
                                    $storedFilename,

                                'file_path' =>
                                    $path,

                                'file_extension' =>
                                    $extension,

                                'file_size' =>
                                    $file->getSize(),

                                'file_hash' =>
                                    $fileHash,

                                'source_system' =>
                                    'monthly_excel',

                                /*
                                |--------------------------------------------------------------------------
                                | Currency
                                |--------------------------------------------------------------------------
                                */

                                'currency_code' =>
                                    strtoupper(
                                        $validated[
                                            'currency_code'
                                        ]
                                    ),

                                'exchange_rate_to_base' =>
                                    null,

                                'scheme_code' =>
                                    $validated[
                                        'scheme_code'
                                    ]
                                    ?? null,

                                'due_date' =>
                                    $validated[
                                        'due_date'
                                    ]
                                    ?? null,

                                /*
                                |--------------------------------------------------------------------------
                                | Processing
                                |--------------------------------------------------------------------------
                                */

                                'status' =>
                                    'uploaded',

                                'progress_percentage' =>
                                    0,

                                'total_rows' =>
                                    0,

                                'processed_rows' =>
                                    0,

                                'valid_rows' =>
                                    0,

                                'warning_rows' =>
                                    0,

                                'error_rows' =>
                                    0,

                                'existing_member_rows' =>
                                    0,

                                'new_member_rows' =>
                                    0,

                                'nil_contributor_rows' =>
                                    0,

                                'uploaded_by' =>
                                    auth()->id(),
                            ]);


                        /*
                        |--------------------------------------------------------------------------
                        | Reopen / Reset Contribution Period
                        |--------------------------------------------------------------------------
                        |
                        | A previous failed batch may have left the period with
                        | a failed/processing status. A replacement upload starts
                        | a fresh validation cycle for the same period.
                        |
                        */

                        $period->update([
                            'period_date' =>
                                $periodDate
                                    ->toDateString(),

                            'due_date' =>
                                $validated[
                                    'due_date'
                                ]
                                ??
                                $period->due_date,

                            'scheme_code' =>
                                $validated[
                                    'scheme_code'
                                ]
                                ??
                                $period->scheme_code,

                            'status' =>
                                'uploading',

                            'scheduled_members' =>
                                0,

                            'existing_members' =>
                                0,

                            'new_members' =>
                                0,

                            'nil_contributors' =>
                                0,

                            'updated_by' =>
                                auth()->id(),
                        ]);


                        return $batch;
                    }
                );


            /*
            |--------------------------------------------------------------------------
            | Audit Successful Upload
            |--------------------------------------------------------------------------
            */

            $this->auditService->log(
                eventType:
                    'contribution_import',

                module:
                    'Pensions Administration - Contributions',

                action:
                    'UPLOAD_MONTHLY_CONTRIBUTIONS',

                description:
                    'Monthly contribution schedule '
                    . $batch->original_filename
                    . ' was uploaded.',

                auditable:
                    $batch,

                newValues:
                    $this
                        ->auditService
                        ->values(
                            $batch
                        ),

                metadata: [
                    'batch_id' =>
                        $batch->id,

                    'employer_id' =>
                        $batch->employer_id,

                    'contribution_period_id' =>
                        $batch
                            ->contribution_period_id,

                    'currency_code' =>
                        $batch
                            ->currency_code,

                    'file_hash' =>
                        $batch
                            ->file_hash,

                    'replacement_upload' =>
                        true,
                ],

                request:
                    $request
            );


            /*
            |--------------------------------------------------------------------------
            | Start Validation Queue
            |--------------------------------------------------------------------------
            */

            ProcessContributionImport::dispatch(
                $batch->id
            )->onQueue(
                'contribution-imports'
            );


            return redirect()
                ->route(
                    'pensions-administration.contributions.imports.show',
                    $batch
                )
                ->with(
                    'success',
                    'Monthly contribution schedule uploaded successfully. Validation has started.'
                );

        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Audit Failed Upload
            |--------------------------------------------------------------------------
            */

            $this->auditService->failure(
                eventType:
                    'contribution_import',

                module:
                    'Pensions Administration - Contributions',

                action:
                    'UPLOAD_MONTHLY_CONTRIBUTIONS',

                description:
                    'Monthly contribution schedule upload failed.',

                failureReason:
                    $e->getMessage(),

                metadata: [
                    'employer_id' =>
                        $validated[
                            'employer_id'
                        ]
                        ?? null,

                    'period_year' =>
                        $validated[
                            'period_year'
                        ]
                        ?? null,

                    'period_month' =>
                        $validated[
                            'period_month'
                        ]
                        ?? null,

                    'currency_code' =>
                        $validated[
                            'currency_code'
                        ]
                        ?? null,
                ],

                request:
                    $request
            );


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
    | Batch Details
    |--------------------------------------------------------------------------
    */

    public function show(
        ContributionImportBatch $batch
    ): View {
        $this->ensurePermission(
            'contributions.monthly-imports.view'
        );


        $batch->load([
            'employer',
            'contributionPeriod',
            'uploadedBy',
            'approvedBy',
            'postedBy',
        ]);


        $recentRows =
            $batch
                ->rows()
                ->with([
                    'matchedMember',
                ])
                ->orderBy(
                    'row_number'
                )
                ->limit(10)
                ->get();


        $nilContributors =
            $batch
                ->contributionPeriod
                ->memberStatuses()
                ->with([
                    'member',
                ])
                ->where(
                    'contribution_status',
                    'nil_contributor'
                )
                ->orderBy(
                    'member_id'
                )
                ->limit(10)
                ->get();


        return view(
            'pensions-administration.contributions.imports.show',
            compact(
                'batch',
                'recentRows',
                'nilContributors'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Processing Status
    |--------------------------------------------------------------------------
    */

    public function status(
        ContributionImportBatch $batch
    ): JsonResponse {
        $this->ensurePermission(
            'contributions.monthly-imports.view'
        );


        $batch->refresh();


        return response()->json([
            'status' =>
                $batch->status,

            'status_label' =>
                $batch
                    ->status_label,

            'currency_code' =>
                $batch
                    ->currency_code,

            'progress_percentage' =>
                (float)
                $batch
                    ->progress_percentage,

            'total_rows' =>
                (int)
                $batch
                    ->total_rows,

            'processed_rows' =>
                (int)
                $batch
                    ->processed_rows,

            'valid_rows' =>
                (int)
                $batch
                    ->valid_rows,

            'warning_rows' =>
                (int)
                $batch
                    ->warning_rows,

            'error_rows' =>
                (int)
                $batch
                    ->error_rows,

            'existing_member_rows' =>
                (int)
                $batch
                    ->existing_member_rows,

            'new_member_rows' =>
                (int)
                $batch
                    ->new_member_rows,

            'nil_contributor_rows' =>
                (int)
                $batch
                    ->nil_contributor_rows,

            'failure_reason' =>
                $batch
                    ->failure_reason,

            'review_url' =>
                route(
                    'pensions-administration.contributions.imports.review',
                    $batch
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Cancel Contribution Import
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        ContributionImportBatch $batch
    ): RedirectResponse {
        $this->ensurePermission(
            'contributions.monthly-imports.delete'
        );


        /*
        |--------------------------------------------------------------------------
        | Posted Transactions Cannot Be Cancelled
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $batch->status,
                [
                    'approved',
                    'posting',
                    'posted',
                ],
                true
            )
        ) {
            return back()
                ->with(
                    'error',
                    'This contribution import cannot be cancelled at its current stage.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Processing Batch
        |--------------------------------------------------------------------------
        */

        if (
            $batch->status
            ===
            'processing'
        ) {
            return back()
                ->with(
                    'error',
                    'This contribution import is currently being validated. Wait for validation to finish before cancelling it.'
                );
        }


        $oldValues =
            $this
                ->auditService
                ->values(
                    $batch
                );


        DB::transaction(
            function () use (
                $batch
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Delete Staging Monthly Statuses
                |--------------------------------------------------------------------------
                */

                ContributionPeriodMemberStatus::query()
                    ->where(
                        'import_batch_id',
                        $batch->id
                    )
                    ->delete();


                /*
                |--------------------------------------------------------------------------
                | Delete Staging Rows
                |--------------------------------------------------------------------------
                */

                ContributionImportRow::query()
                    ->where(
                        'import_batch_id',
                        $batch->id
                    )
                    ->delete();


                /*
                |--------------------------------------------------------------------------
                | Cancel Batch
                |--------------------------------------------------------------------------
                */

                $batch->update([
                    'status' =>
                        'cancelled',

                    'progress_percentage' =>
                        0,
                ]);


                /*
                |--------------------------------------------------------------------------
                | Reopen Contribution Period
                |--------------------------------------------------------------------------
                */

                $batch
                    ->contributionPeriod
                    ->update([
                        'status' =>
                            'open',

                        'scheduled_members' =>
                            0,

                        'existing_members' =>
                            0,

                        'new_members' =>
                            0,

                        'nil_contributors' =>
                            0,

                        'updated_by' =>
                            auth()->id(),
                    ]);
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Audit
        |--------------------------------------------------------------------------
        */

        $this->auditService->log(
            eventType:
                'contribution_import',

            module:
                'Pensions Administration - Contributions',

            action:
                'CANCEL_MONTHLY_CONTRIBUTIONS',

            description:
                'Contribution import '
                . $batch->import_uuid
                . ' was cancelled.',

            auditable:
                $batch,

            oldValues:
                $oldValues,

            newValues:
                $this
                    ->auditService
                    ->values(
                        $batch
                    ),

            metadata: [
                'batch_id' =>
                    $batch->id,

                'contribution_period_id' =>
                    $batch
                        ->contribution_period_id,
            ],

            request:
                $request
        );


        return redirect()
            ->route(
                'pensions-administration.contributions.imports.index'
            )
            ->with(
                'success',
                'Monthly contribution import cancelled successfully. A replacement schedule may now be uploaded.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Permission Enforcement
    |--------------------------------------------------------------------------
    */

    private function ensurePermission(
        string $permission
    ): void {
        $user =
            auth()->user();


        abort_if(
            !$user,
            401,
            'Unauthenticated.'
        );


        if (
            $user
                ->is_system_administrator
        ) {
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
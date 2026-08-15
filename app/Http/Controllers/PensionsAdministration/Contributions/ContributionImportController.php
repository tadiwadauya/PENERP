<?php

namespace App\Http\Controllers\PensionsAdministration\Contributions;

use App\Http\Controllers\Controller;
use App\Jobs\PensionsAdministration\Contributions\ProcessContributionImport;
use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Models\PensionsAdministration\Contributions\ContributionPeriod;
use App\Models\PensionsAdministration\Contributions\ContributionImportRow;
use App\Models\PensionsAdministration\Contributions\ContributionPeriodMemberStatus;
use App\Models\PensionsAdministration\Updates\Employer;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                ->orderByDesc(
                    'id'
                );


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


        if (
            $request->filled(
                'year'
            )
        ) {
            $query->whereHas(
                'contributionPeriod',
                function ($query) use (
                    $request
                ): void {

                    $query->where(
                        'period_year',
                        $request->input(
                            'year'
                        )
                    );
                }
            );
        }


        $batches =
            $query
                ->paginate(25)
                ->withQueryString();


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
                ->orderBy(
                    'name'
                )
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
    | Store Upload
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ): RedirectResponse {
        $this->ensurePermission(
            'contributions.monthly-imports.create'
        );


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
                        | Period Date
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


                        /*
                        |--------------------------------------------------------------------------
                        | Contribution Period
                        |--------------------------------------------------------------------------
                        */

                        $period =
                            ContributionPeriod::firstOrCreate(
                                [
                                    'employer_id' =>
                                        $employer
                                            ->id,

                                    'period_year' =>
                                        $periodDate
                                            ->year,

                                    'period_month' =>
                                        $periodDate
                                            ->month,
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

                                    'created_by' =>
                                        auth()->id(),

                                    'updated_by' =>
                                        auth()->id(),
                                ]
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | Prevent Duplicate Active Import
                        |--------------------------------------------------------------------------
                        */

                        $existing =
                            ContributionImportBatch::query()
                                ->where(
                                    'contribution_period_id',
                                    $period
                                        ->id
                                )
                                ->whereNotIn(
                                    'status',
                                    [
                                        'cancelled',
                                        'failed',
                                    ]
                                )
                                ->exists();


                        if ($existing) {
                            throw new RuntimeException(
                                'A contribution import already exists for '
                                . $employer
                                    ->name
                                . ' for '
                                . $periodDate
                                    ->format(
                                        'F Y'
                                    )
                                . '. Cancel the existing batch before uploading another schedule.'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | File
                        |--------------------------------------------------------------------------
                        */

                        $file =
                            $request
                                ->file(
                                    'import_file'
                                );


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


                        $path =
                            $file->storeAs(
                                'contribution-imports',
                                $storedFilename
                            );


                        if (!$path) {
                            throw new RuntimeException(
                                'The contribution file could not be stored.'
                            );
                        }


                        $fileHash =
                            hash_file(
                                'sha256',
                                $file
                                    ->getRealPath()
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | Prevent Same File Being Uploaded Twice
                        |--------------------------------------------------------------------------
                        */

                        $duplicateFile =
                            ContributionImportBatch::query()
                                ->where(
                                    'file_hash',
                                    $fileHash
                                )
                                ->whereNotIn(
                                    'status',
                                    [
                                        'cancelled',
                                    ]
                                )
                                ->exists();


                        if ($duplicateFile) {
                            throw new RuntimeException(
                                'This exact Excel file has already been uploaded.'
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
                                    $period
                                        ->id,

                                'employer_id' =>
                                    $employer
                                        ->id,

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
                                    $file
                                        ->getSize(),

                                'file_hash' =>
                                    $fileHash,

                                'source_system' =>
                                    'monthly_excel',

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

                                'status' =>
                                    'uploaded',

                                'progress_percentage' =>
                                    0,

                                'uploaded_by' =>
                                    auth()->id(),
                            ]);


                        $period->update([
                            'due_date' =>
                                $validated[
                                    'due_date'
                                ]
                                ??
                                $period
                                    ->due_date,

                            'scheme_code' =>
                                $validated[
                                    'scheme_code'
                                ]
                                ??
                                $period
                                    ->scheme_code,

                            'status' =>
                                'uploading',

                            'updated_by' =>
                                auth()->id(),
                        ]);


                        return $batch;
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
                    'UPLOAD_MONTHLY_CONTRIBUTIONS',

                description:
                    'Monthly contribution schedule '
                    . $batch
                        ->original_filename
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
                        $batch
                            ->id,

                    'employer_id' =>
                        $batch
                            ->employer_id,

                    'contribution_period_id' =>
                        $batch
                            ->contribution_period_id,

                    'file_hash' =>
                        $batch
                            ->file_hash,
                ],

                request:
                    $request
            );


            /*
            |--------------------------------------------------------------------------
            | Start Validation
            |--------------------------------------------------------------------------
            */

            ProcessContributionImport::dispatch(
                $batch
                    ->id
            )
                ->onQueue(
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
                    $e
                        ->getMessage(),

                request:
                    $request
            );


            return back()
                ->withInput()
                ->with(
                    'error',
                    $e
                        ->getMessage()
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
    | Progress Status
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
                $batch
                    ->status,

            'status_label' =>
                $batch
                    ->status_label,

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
    | Cancel Batch
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        ContributionImportBatch $batch
    ): RedirectResponse {
        $this->ensurePermission(
            'contributions.monthly-imports.delete'
        );


        if (
            in_array(
                $batch
                    ->status,
                [
                    'processing',
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

                ContributionPeriodMemberStatus::query()
                    ->where(
                        'import_batch_id',
                        $batch
                            ->id
                    )
                    ->delete();


                ContributionImportRow::query()
                    ->where(
                        'import_batch_id',
                        $batch
                            ->id
                    )
                    ->delete();


                $batch->update([
                    'status' =>
                        'cancelled',
                ]);


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


        $this->auditService->log(
            eventType:
                'contribution_import',

            module:
                'Pensions Administration - Contributions',

            action:
                'CANCEL_MONTHLY_CONTRIBUTIONS',

            description:
                'Contribution import '
                . $batch
                    ->import_uuid
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

            request:
                $request
        );


        return redirect()
            ->route(
                'pensions-administration.contributions.imports.index'
            )
            ->with(
                'success',
                'Monthly contribution import cancelled successfully.'
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
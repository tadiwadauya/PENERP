<?php

namespace App\Http\Controllers\PensionsAdministration\Contributions;

use App\Http\Controllers\Controller;
use App\Jobs\PensionsAdministration\Contributions\ProcessContributionPosting;
use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ContributionPostingController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Approve Contribution Batch
    |--------------------------------------------------------------------------
    */

    public function approve(
        Request $request,
        ContributionImportBatch $batch
    ): RedirectResponse {
        $this->ensurePermission(
            'contributions.monthly-imports.approve'
        );

        $validated = $request->validate([
            'approval_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        try {
            DB::transaction(
                function () use (
                    $batch,
                    $validated
                ): void {
                    $lockedBatch = ContributionImportBatch::query()
                        ->where(
                            'id',
                            $batch->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                    /*
                    |--------------------------------------------------------------------------
                    | Workflow Stage
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !in_array(
                            $lockedBatch->status,
                            [
                                'awaiting_review',
                                'validated',
                            ],
                            true
                        )
                    ) {
                        throw new RuntimeException(
                            'Only a contribution batch awaiting review can be approved.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Only Errors Block Approval
                    |--------------------------------------------------------------------------
                    */

                    if (
                        (int) $lockedBatch->error_rows
                        >
                        0
                    ) {
                        throw new RuntimeException(
                            'This contribution batch cannot be approved because it contains '
                            . $lockedBatch->error_rows
                            . ' validation error(s).'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Maker / Checker
                    |--------------------------------------------------------------------------
                    */

                    if (
                        (int) $lockedBatch->uploaded_by
                        ===
                        (int) auth()->id()
                        &&
                        !auth()->user()->is_system_administrator
                    ) {
                        throw new RuntimeException(
                            'You cannot approve a contribution batch that you uploaded yourself.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Validate Proposed New Members Before Approval
                    |--------------------------------------------------------------------------
                    */

                    $newMemberRows = $lockedBatch
                        ->rows()
                        ->where(
                            'is_new_member',
                            true
                        )
                        ->get();

                    foreach (
                        $newMemberRows
                        as $row
                    ) {
                        $data = $row->normalized_data
                            ?? [];

                        /*
                        |--------------------------------------------------------------------------
                        | Date Joined Fund
                        |--------------------------------------------------------------------------
                        */

                        if (
                            blank(
                                $data['date_joined_fund']
                                ?? null
                            )
                        ) {
                            throw new RuntimeException(
                                'New member on Excel row '
                                . $row->row_number
                                . ' does not have Date Joined Fund.'
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Date Joined Employer
                        |--------------------------------------------------------------------------
                        */

                        if (
                            blank(
                                $data['date_joined_employer']
                                ?? null
                            )
                        ) {
                            throw new RuntimeException(
                                'New member on Excel row '
                                . $row->row_number
                                . ' does not have Date Joined Employer.'
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Staff Number
                        |--------------------------------------------------------------------------
                        */

                        if (
                            blank(
                                $data['staff_number']
                                ?? null
                            )
                        ) {
                            throw new RuntimeException(
                                'New member on Excel row '
                                . $row->row_number
                                . ' does not have a Staff Number.'
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Surname
                        |--------------------------------------------------------------------------
                        */

                        if (
                            blank(
                                $data['surname']
                                ?? null
                            )
                        ) {
                            throw new RuntimeException(
                                'New member on Excel row '
                                . $row->row_number
                                . ' does not have a surname.'
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | First Names
                        |--------------------------------------------------------------------------
                        */

                        if (
                            blank(
                                $data['first_names']
                                ?? null
                            )
                        ) {
                            throw new RuntimeException(
                                'New member on Excel row '
                                . $row->row_number
                                . ' does not have first names.'
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Date Of Birth
                        |--------------------------------------------------------------------------
                        */

                        if (
                            blank(
                                $data['date_of_birth']
                                ?? null
                            )
                        ) {
                            throw new RuntimeException(
                                'New member on Excel row '
                                . $row->row_number
                                . ' does not have Date of Birth.'
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Gender
                        |--------------------------------------------------------------------------
                        */

                        if (
                            blank(
                                $data['gender']
                                ?? null
                            )
                        ) {
                            throw new RuntimeException(
                                'New member on Excel row '
                                . $row->row_number
                                . ' does not have Gender.'
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | National ID
                        |--------------------------------------------------------------------------
                        */

                        if (
                            blank(
                                $data['national_id']
                                ?? null
                            )
                        ) {
                            throw new RuntimeException(
                                'New member on Excel row '
                                . $row->row_number
                                . ' does not have National ID.'
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Marital Status
                        |--------------------------------------------------------------------------
                        */

                        if (
                            blank(
                                $data['marital_status']
                                ?? null
                            )
                        ) {
                            throw new RuntimeException(
                                'New member on Excel row '
                                . $row->row_number
                                . ' does not have Marital Status.'
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Optional Contact Details
                        |--------------------------------------------------------------------------
                        |
                        | These are deliberately NOT required:
                        |
                        | - Cell Phone Number
                        | - Email Address
                        | - Home Address
                        |
                        */

                        /*
                        |--------------------------------------------------------------------------
                        | Member Number
                        |--------------------------------------------------------------------------
                        |
                        | PENERP/PenAd member number is NOT required here.
                        |
                        | A truly new member receives a new member number during posting.
                        |
                        */
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Approve Batch
                    |--------------------------------------------------------------------------
                    */

                    $lockedBatch->update([
                        'status' =>
                            'approved',

                        'approved_by' =>
                            auth()->id(),

                        'approved_at' =>
                            now(),

                        'approval_notes' =>
                            $validated['approval_notes']
                            ?? null,

                        'progress_percentage' =>
                            100,

                        /*
                        |--------------------------------------------------------------------------
                        | Clear Previous Posting Failure State
                        |--------------------------------------------------------------------------
                        */

                        'failure_reason' =>
                            null,
                    ]);
                }
            );

            $batch->refresh();

            /*
            |--------------------------------------------------------------------------
            | Audit Approval
            |--------------------------------------------------------------------------
            */

            $this->auditService->log(
                eventType:
                    'contribution_import',

                module:
                    'Pensions Administration - Contributions',

                action:
                    'APPROVE_MONTHLY_CONTRIBUTIONS',

                description:
                    'Monthly contribution batch #'
                    . $batch->id
                    . ' was approved.',

                auditable:
                    $batch,

                newValues:
                    $this->auditService->values(
                        $batch
                    ),

                metadata: [
                    'batch_id' =>
                        $batch->id,

                    'currency_code' =>
                        $batch->currency_code,

                    'approved_by' =>
                        auth()->id(),
                ],

                request:
                    $request
            );

            return redirect()
                ->route(
                    'pensions-administration.contributions.imports.review',
                    $batch
                )
                ->with(
                    'success',
                    'Monthly contribution batch approved successfully. It is ready for posting.'
                );

        } catch (Throwable $e) {
            $this->auditService->failure(
                eventType:
                    'contribution_import',

                module:
                    'Pensions Administration - Contributions',

                action:
                    'APPROVE_MONTHLY_CONTRIBUTIONS',

                description:
                    'Contribution batch approval failed.',

                failureReason:
                    $e->getMessage(),

                auditable:
                    $batch,

                request:
                    $request
            );

            return back()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Start Posting
    |--------------------------------------------------------------------------
    */

    public function post(
        Request $request,
        ContributionImportBatch $batch
    ): RedirectResponse {
        $this->ensurePermission(
            'contributions.monthly-imports.post'
        );

        try {
            $batch->refresh();

            /*
            |--------------------------------------------------------------------------
            | Must Be Approved
            |--------------------------------------------------------------------------
            */

            if (
                $batch->status
                !==
                'approved'
            ) {
                throw new RuntimeException(
                    'Only an approved contribution batch can be posted.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Approval Required
            |--------------------------------------------------------------------------
            */

            if (
                !$batch->approved_by
                ||
                !$batch->approved_at
            ) {
                throw new RuntimeException(
                    'This contribution batch does not have a valid approval.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Posting User
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            |
            | Queue workers do not have the browser authentication session.
            | We therefore capture the user NOW and persist the ID on the batch.
            |
            */

            $postingUserId = auth()->id();

            if (!$postingUserId) {
                throw new RuntimeException(
                    'PENERP could not determine the user responsible for posting this contribution batch.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Approver / Poster Separation
            |--------------------------------------------------------------------------
            */

            if (
                (int) $batch->approved_by
                ===
                (int) $postingUserId
                &&
                !auth()->user()->is_system_administrator
            ) {
                throw new RuntimeException(
                    'The user who approved this batch cannot also post it.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Posting
            |--------------------------------------------------------------------------
            */

            if ($batch->posted_at) {
                throw new RuntimeException(
                    'This contribution batch has already been posted.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Posting Start
            |--------------------------------------------------------------------------
            */

            if (
                $batch->status
                ===
                'posting'
            ) {
                throw new RuntimeException(
                    'This contribution batch is already being posted.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Persist Posting User Before Queue Dispatch
            |--------------------------------------------------------------------------
            |
            | This permanently fixes:
            |
            | posting_user_id = NULL
            | posted_by       = NULL
            |
            */

            DB::transaction(
                function () use (
                    $batch,
                    $postingUserId
                ): void {
                    $lockedBatch = ContributionImportBatch::query()
                        ->where(
                            'id',
                            $batch->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                    /*
                    |--------------------------------------------------------------------------
                    | Re-check State Under Lock
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $lockedBatch->status
                        !==
                        'approved'
                    ) {
                        throw new RuntimeException(
                            'This contribution batch is no longer available for posting.'
                        );
                    }

                    if ($lockedBatch->posted_at) {
                        throw new RuntimeException(
                            'This contribution batch has already been posted.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Move To Posting
                    |--------------------------------------------------------------------------
                    */

                    $lockedBatch->update([
                        'status' =>
                            'posting',

                        'progress_percentage' =>
                            1,

                        'posted_rows' =>
                            0,

                        'failure_reason' =>
                            null,

                        /*
                        |--------------------------------------------------------------------------
                        | Posting User
                        |--------------------------------------------------------------------------
                        */

                        'posting_user_id' =>
                            $postingUserId,

                        'posted_by' =>
                            $postingUserId,

                        /*
                        |--------------------------------------------------------------------------
                        | Posting Has Not Finished Yet
                        |--------------------------------------------------------------------------
                        */

                        'posted_at' =>
                            null,
                    ]);
                }
            );

            /*
            |--------------------------------------------------------------------------
            | Refresh Batch
            |--------------------------------------------------------------------------
            */

            $batch->refresh();

            /*
            |--------------------------------------------------------------------------
            | Dispatch Posting Job
            |--------------------------------------------------------------------------
            |
            | The user ID is also passed directly into the job as a second
            | safeguard.
            |
            */

            ProcessContributionPosting::dispatch(
                $batch->id,
                $postingUserId
            );

            /*
            |--------------------------------------------------------------------------
            | Audit Posting Start
            |--------------------------------------------------------------------------
            */

            $this->auditService->log(
                eventType:
                    'contribution_posting',

                module:
                    'Pensions Administration - Contributions',

                action:
                    'START_MONTHLY_CONTRIBUTION_POSTING',

                description:
                    'Posting was started for monthly contribution batch #'
                    . $batch->id
                    . '.',

                auditable:
                    $batch,

                newValues:
                    $this->auditService->values(
                        $batch
                    ),

                metadata: [
                    'batch_id' =>
                        $batch->id,

                    'currency_code' =>
                        $batch->currency_code,

                    'requested_by' =>
                        $postingUserId,

                    'posting_user_id' =>
                        $postingUserId,

                    'approved_by' =>
                        $batch->approved_by,
                ],

                request:
                    $request
            );

            /*
            |--------------------------------------------------------------------------
            | Posting Progress Screen
            |--------------------------------------------------------------------------
            */

            return redirect()
                ->route(
                    'pensions-administration.contributions.imports.posting',
                    $batch
                );

        } catch (Throwable $e) {
            $this->auditService->failure(
                eventType:
                    'contribution_posting',

                module:
                    'Pensions Administration - Contributions',

                action:
                    'START_MONTHLY_CONTRIBUTION_POSTING',

                description:
                    'Monthly contribution posting could not be started.',

                failureReason:
                    $e->getMessage(),

                auditable:
                    $batch,

                request:
                    $request
            );

            return back()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Posting Progress Screen
    |--------------------------------------------------------------------------
    */

    public function posting(
        ContributionImportBatch $batch
    ): View {
        $this->ensurePermission(
            'contributions.monthly-imports.view'
        );

        $batch->load([
            'employer',
            'contributionPeriod',
            'approvedBy',
            'postedBy',
        ]);

        return view(
            'pensions-administration.contributions.imports.posting',
            compact(
                'batch'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Posting Status JSON
    |--------------------------------------------------------------------------
    */

    public function postingStatus(
        ContributionImportBatch $batch
    ): JsonResponse {
        $this->ensurePermission(
            'contributions.monthly-imports.view'
        );

        $batch->refresh();

        $totalRows = (int) (
            $batch->total_rows
            ?:
            $batch
                ->rows()
                ->count()
        );

        return response()->json([
            'batch_id' =>
                $batch->id,

            'status' =>
                $batch->status,

            'status_label' =>
                $this->statusLabel(
                    $batch->status
                ),

            'progress_percentage' =>
                (float) $batch->progress_percentage,

            'total_rows' =>
                $totalRows,

            'posted_rows' =>
                (int) (
                    $batch->posted_rows
                    ?? 0
                ),

            'failure_reason' =>
                $batch->failure_reason,

            'posting_user_id' =>
                $batch->posting_user_id,

            'posted_by' =>
                $batch->posted_by,

            'show_url' =>
                route(
                    'pensions-administration.contributions.imports.show',
                    $batch
                ),

            'review_url' =>
                route(
                    'pensions-administration.contributions.imports.review',
                    $batch
                ),
        ]);
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
            'posting' =>
                'Posting',

            'posted' =>
                'Posted',

            'posting_failed' =>
                'Posting Failed',

            'approved' =>
                'Approved',

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

        if (
            $user->is_system_administrator
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
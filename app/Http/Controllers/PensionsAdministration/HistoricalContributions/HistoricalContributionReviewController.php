<?php

namespace App\Http\Controllers\PensionsAdministration\HistoricalContributions;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\HistoricalContributions\HistoricalContributionImportBatch;
use App\Models\PensionsAdministration\HistoricalContributions\HistoricalContributionImportRow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class HistoricalContributionReviewController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Review Centre
    |--------------------------------------------------------------------------
    */

    public function index(
        HistoricalContributionImportBatch $batch
    ): View {
        $this->ensurePermission(
            'contributions.monthly-imports.view'
        );

        if (
            !in_array(
                $batch->status,
                [
                    'awaiting_review',
                    'approved',
                    'posting_failed',
                ],
                true
            )
        ) {
            return redirect()
                ->route(
                    'pensions-administration.historical-contributions.imports.show',
                    $batch
                )
                ->with(
                    'warning',
                    'Historical contribution review is available after validation has completed.'
                );
        }

        $counts =
            $this->reviewCounts(
                $batch
            );

        return view(
            'pensions-administration.historical-contributions.review.index',
            compact(
                'batch',
                'counts'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Server-Side DataTables
    |--------------------------------------------------------------------------
    */

    public function data(
        Request $request,
        HistoricalContributionImportBatch $batch
    ): JsonResponse {
        $this->ensurePermission(
            'contributions.monthly-imports.view'
        );

        $draw =
            (int) $request->input(
                'draw',
                1
            );

        $start =
            max(
                0,
                (int) $request->input(
                    'start',
                    0
                )
            );

        $length =
            (int) $request->input(
                'length',
                25
            );

        if (
            $length < 1
            ||
            $length > 100
        ) {
            $length = 25;
        }

        /*
        |--------------------------------------------------------------------------
        | Total Batch Transactions
        |--------------------------------------------------------------------------
        */

        $recordsTotal =
            HistoricalContributionImportRow::query()
                ->where(
                    'import_batch_id',
                    $batch->id
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Query
        |--------------------------------------------------------------------------
        */

        $query =
            HistoricalContributionImportRow::query()
                ->where(
                    'import_batch_id',
                    $batch->id
                );

        /*
        |--------------------------------------------------------------------------
        | Advanced Filters
        |--------------------------------------------------------------------------
        */

        $this->applyFilters(
            $query,
            $request
        );

        /*
        |--------------------------------------------------------------------------
        | DataTables Quick Search
        |--------------------------------------------------------------------------
        */

        $quickSearch =
            trim(
                (string) $request->input(
                    'search.value',
                    ''
                )
            );

        if (
            $quickSearch !== ''
        ) {
            $this->applyQuickSearch(
                $query,
                $quickSearch
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filtered Count
        |--------------------------------------------------------------------------
        */

        $recordsFiltered =
            (clone $query)
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Ordering
        |--------------------------------------------------------------------------
        */

        $orderColumnIndex =
            (int) $request->input(
                'order.0.column',
                0
            );

        $orderDirection =
            strtolower(
                (string) $request->input(
                    'order.0.dir',
                    'asc'
                )
            );

        if (
            !in_array(
                $orderDirection,
                [
                    'asc',
                    'desc',
                ],
                true
            )
        ) {
            $orderDirection =
                'asc';
        }

        $orderColumns = [
            0 => 'source_row_number',
            1 => 'penad_member_number',
            2 => 'staff_number',
            3 => 'surname',
            4 => 'national_id',
            6 => 'period_date',
            7 => 'transaction_type',
            8 => 'employee_contribution',
            9 => 'employer_contribution',
            10 => 'service_status',
            11 => 'validation_status',
            12 => 'duplicate_status',
            13 => 'review_decision',
        ];

        $orderColumn =
            $orderColumns[
                $orderColumnIndex
            ]
            ??
            'source_row_number';

        $query
            ->orderBy(
                $orderColumn,
                $orderDirection
            )
            ->orderBy(
                'id',
                'asc'
            );

        /*
        |--------------------------------------------------------------------------
        | Only Load Current Page
        |--------------------------------------------------------------------------
        */

        $rows =
            $query
                ->skip(
                    $start
                )
                ->take(
                    $length
                )
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Format Rows
        |--------------------------------------------------------------------------
        */

        $data =
            $rows
                ->map(
                    fn (
                        HistoricalContributionImportRow $row
                    ) =>
                        $this->formatRow(
                            $batch,
                            $row
                        )
                )
                ->values();

        return response()->json([
            'draw' =>
                $draw,

            'recordsTotal' =>
                $recordsTotal,

            'recordsFiltered' =>
                $recordsFiltered,

            'data' =>
                $data,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Individual Review Decision
    |--------------------------------------------------------------------------
    */

    public function decision(
        Request $request,
        HistoricalContributionImportBatch $batch,
        HistoricalContributionImportRow $row
    ): JsonResponse {
        $this->ensurePermission(
            'contributions.monthly-imports.update'
        );

        if (
            $row->import_batch_id
            !=
            $batch->id
        ) {
            abort(
                404
            );
        }

        if (
            !in_array(
                $batch->status,
                [
                    'awaiting_review',
                    'posting_failed',
                ],
                true
            )
        ) {
            return response()->json([
                'success' =>
                    false,

                'message' =>
                    'This batch is no longer open for review.',
            ], 422);
        }

        $validated =
            $request->validate([
                'decision' => [
                    'required',
                    'in:approved,excluded,pending',
                ],

                'review_notes' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ]);

        $decision =
            $validated[
                'decision'
            ];

        /*
        |--------------------------------------------------------------------------
        | Do Not Approve Error Transactions
        |--------------------------------------------------------------------------
        */

        if (
            $decision === 'approved'
            &&
            $row->validation_status === 'error'
        ) {
            return response()->json([
                'success' =>
                    false,

                'message' =>
                    'A transaction with validation errors cannot be approved.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Do Not Approve Duplicates
        |--------------------------------------------------------------------------
        */

        if (
            $decision === 'approved'
            &&
            $row->duplicate_status !== 'none'
        ) {
            return response()->json([
                'success' =>
                    false,

                'message' =>
                    'A duplicate transaction cannot be approved. Exclude it or return it to Pending.',
            ], 422);
        }

        $row->update([
            'review_decision' =>
                $decision,

            'review_notes' =>
                $validated[
                    'review_notes'
                ]
                ??
                null,

            'reviewed_by' =>
                auth()->id(),

            'reviewed_at' =>
                now(),
        ]);

        return response()->json([
            'success' =>
                true,

            'message' =>
                match ($decision) {
                    'approved' =>
                        'Transaction approved.',

                    'excluded' =>
                        'Transaction excluded from historical posting.',

                    default =>
                        'Transaction returned to pending review.',
                },

            'counts' =>
                $this->reviewCounts(
                    $batch
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Approve All Eligible Transactions
    |--------------------------------------------------------------------------
    |
    | Eligible means:
    |
    | validation_status != error
    | duplicate_status = none
    |
    | Warnings are allowed because historical records can legitimately be
    | incomplete, especially old/exited members.
    |
    */

    public function approveEligible(
        HistoricalContributionImportBatch $batch
    ): RedirectResponse {
        $this->ensurePermission(
            'contributions.monthly-imports.approve'
        );

        if (
            !in_array(
                $batch->status,
                [
                    'awaiting_review',
                    'posting_failed',
                ],
                true
            )
        ) {
            return back()
                ->with(
                    'error',
                    'This historical contribution batch cannot currently be approved.'
                );
        }

        try {
            DB::transaction(
                function () use (
                    $batch
                ): void {
                    /*
                    |--------------------------------------------------------------------------
                    | Approve All Eligible Rows
                    |--------------------------------------------------------------------------
                    */

                    HistoricalContributionImportRow::query()
                        ->where(
                            'import_batch_id',
                            $batch->id
                        )
                        ->where(
                            'validation_status',
                            '<>',
                            'error'
                        )
                        ->where(
                            'duplicate_status',
                            'none'
                        )
                        ->where(
                            'review_decision',
                            'pending'
                        )
                        ->update([
                            'review_decision' =>
                                'approved',

                            'reviewed_by' =>
                                auth()->id(),

                            'reviewed_at' =>
                                now(),

                            'updated_at' =>
                                now(),
                        ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Automatically Exclude Duplicates
                    |--------------------------------------------------------------------------
                    |
                    | A duplicate must never be posted merely because a bulk
                    | approval was performed.
                    |
                    */

                    HistoricalContributionImportRow::query()
                        ->where(
                            'import_batch_id',
                            $batch->id
                        )
                        ->where(
                            'duplicate_status',
                            '<>',
                            'none'
                        )
                        ->where(
                            'review_decision',
                            'pending'
                        )
                        ->update([
                            'review_decision' =>
                                'excluded',

                            'review_notes' =>
                                'Automatically excluded because this historical transaction was identified as a duplicate.',

                            'reviewed_by' =>
                                auth()->id(),

                            'reviewed_at' =>
                                now(),

                            'updated_at' =>
                                now(),
                        ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Errors Stay Pending
                    |--------------------------------------------------------------------------
                    */

                    $batch->update([
                        'approved_by' =>
                            auth()->id(),

                        'approved_at' =>
                            now(),

                        'approval_notes' =>
                            'Eligible historical contribution transactions reviewed and approved.',
                    ]);
                }
            );

            return redirect()
                ->route(
                    'pensions-administration.historical-contributions.review.index',
                    $batch
                )
                ->with(
                    'success',
                    'All eligible historical contribution transactions have been approved. Duplicates were excluded automatically.'
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
    | Finalise Review
    |--------------------------------------------------------------------------
    |
    | This changes the batch from awaiting_review to approved.
    |
    | It DOES NOT post anything yet.
    |
    */

    public function finalise(
        HistoricalContributionImportBatch $batch
    ): RedirectResponse {
        $this->ensurePermission(
            'contributions.monthly-imports.approve'
        );

        try {
            DB::transaction(
                function () use (
                    $batch
                ): void {
                    $counts =
                        $this->reviewCounts(
                            $batch
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | Errors Must Be Resolved / Excluded
                    |--------------------------------------------------------------------------
                    */

                    $pendingErrors =
                        HistoricalContributionImportRow::query()
                            ->where(
                                'import_batch_id',
                                $batch->id
                            )
                            ->where(
                                'validation_status',
                                'error'
                            )
                            ->where(
                                'review_decision',
                                'pending'
                            )
                            ->count();

                    if (
                        $pendingErrors > 0
                    ) {
                        throw new RuntimeException(
                            number_format(
                                $pendingErrors
                            )
                            . ' error transaction(s) are still pending review. Exclude or correct them before finalising the batch.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Nothing Approved
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $counts[
                            'approved'
                        ]
                        <=
                        0
                    ) {
                        throw new RuntimeException(
                            'There are no approved historical contribution transactions to post.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Pending Eligible Transactions
                    |--------------------------------------------------------------------------
                    */

                    $pendingEligible =
                        HistoricalContributionImportRow::query()
                            ->where(
                                'import_batch_id',
                                $batch->id
                            )
                            ->where(
                                'validation_status',
                                '<>',
                                'error'
                            )
                            ->where(
                                'duplicate_status',
                                'none'
                            )
                            ->where(
                                'review_decision',
                                'pending'
                            )
                            ->count();

                    if (
                        $pendingEligible > 0
                    ) {
                        throw new RuntimeException(
                            number_format(
                                $pendingEligible
                            )
                            . ' eligible transaction(s) are still pending review.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Final Approval
                    |--------------------------------------------------------------------------
                    */

                    $batch->update([
                        'status' =>
                            'approved',

                        'approved_by' =>
                            auth()->id(),

                        'approved_at' =>
                            now(),

                        'approval_notes' =>
                            'Historical contribution review completed and approved for posting.',

                        'failure_reason' =>
                            null,
                    ]);
                }
            );

            return redirect()
                ->route(
                    'pensions-administration.historical-contributions.imports.show',
                    $batch
                )
                ->with(
                    'success',
                    'Historical contribution review has been finalised. The batch is now approved and ready for posting.'
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
    | Apply Filters
    |--------------------------------------------------------------------------
    */

    private function applyFilters(
        Builder $query,
        Request $request
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Validation Status
        |--------------------------------------------------------------------------
        */

        $validationStatus =
            trim(
                (string) $request->input(
                    'validation_status',
                    ''
                )
            );

        if (
            $validationStatus !== ''
        ) {
            $query->where(
                'validation_status',
                $validationStatus
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Member Type
        |--------------------------------------------------------------------------
        */

        $memberType =
            trim(
                (string) $request->input(
                    'member_type',
                    ''
                )
            );

        if (
            $memberType === 'existing'
        ) {
            $query->whereNotNull(
                'matched_member_id'
            );
        }

        if (
            $memberType === 'new'
        ) {
            $query->where(
                'is_new_member',
                true
            );
        }

        if (
            $memberType === 'ambiguous'
        ) {
            $query->where(
                'member_match_type',
                'conflict'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicate
        |--------------------------------------------------------------------------
        */

        $duplicateStatus =
            trim(
                (string) $request->input(
                    'duplicate_status',
                    ''
                )
            );

        if (
            $duplicateStatus !== ''
        ) {
            $query->where(
                'duplicate_status',
                $duplicateStatus
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Transaction Type
        |--------------------------------------------------------------------------
        */

        $transactionType =
            trim(
                (string) $request->input(
                    'transaction_type',
                    ''
                )
            );

        if (
            $transactionType !== ''
        ) {
            $query->where(
                'transaction_type',
                $transactionType
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Service Status
        |--------------------------------------------------------------------------
        */

        $serviceStatus =
            trim(
                (string) $request->input(
                    'service_status',
                    ''
                )
            );

        if (
            $serviceStatus !== ''
        ) {
            $query->where(
                'service_status',
                $serviceStatus
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Review Decision
        |--------------------------------------------------------------------------
        */

        $reviewDecision =
            trim(
                (string) $request->input(
                    'review_decision',
                    ''
                )
            );

        if (
            $reviewDecision !== ''
        ) {
            $query->where(
                'review_decision',
                $reviewDecision
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PenAd Member Number
        |--------------------------------------------------------------------------
        */

        $penadNumber =
            trim(
                (string) $request->input(
                    'penad_member_number',
                    ''
                )
            );

        if (
            $penadNumber !== ''
        ) {
            $query->where(
                'penad_member_number',
                'like',
                '%'
                . $penadNumber
                . '%'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Staff Number
        |--------------------------------------------------------------------------
        */

        $staffNumber =
            trim(
                (string) $request->input(
                    'staff_number',
                    ''
                )
            );

        if (
            $staffNumber !== ''
        ) {
            $query->where(
                'staff_number',
                'like',
                '%'
                . $staffNumber
                . '%'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | National ID
        |--------------------------------------------------------------------------
        */

        $nationalId =
            trim(
                (string) $request->input(
                    'national_id',
                    ''
                )
            );

        if (
            $nationalId !== ''
        ) {
            $query->where(
                'national_id',
                'like',
                '%'
                . $nationalId
                . '%'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Quick Search
    |--------------------------------------------------------------------------
    */

    private function applyQuickSearch(
        Builder $query,
        string $search
    ): void {
        $query->where(
            function (
                Builder $query
            ) use (
                $search
            ): void {
                $query
                    ->where(
                        'penad_member_number',
                        'like',
                        '%'
                        . $search
                        . '%'
                    )
                    ->orWhere(
                        'penerp_member_number',
                        'like',
                        '%'
                        . $search
                        . '%'
                    )
                    ->orWhere(
                        'fundworx_member_number',
                        'like',
                        '%'
                        . $search
                        . '%'
                    )
                    ->orWhere(
                        'staff_number',
                        'like',
                        '%'
                        . $search
                        . '%'
                    )
                    ->orWhere(
                        'national_id',
                        'like',
                        '%'
                        . $search
                        . '%'
                    )
                    ->orWhere(
                        'surname',
                        'like',
                        '%'
                        . $search
                        . '%'
                    )
                    ->orWhere(
                        'first_names',
                        'like',
                        '%'
                        . $search
                        . '%'
                    )
                    ->orWhere(
                        'employer_name',
                        'like',
                        '%'
                        . $search
                        . '%'
                    );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Format DataTable Row
    |--------------------------------------------------------------------------
    */

    private function formatRow(
        HistoricalContributionImportBatch $batch,
        HistoricalContributionImportRow $row
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Member Name
        |--------------------------------------------------------------------------
        */

        $memberName =
            '<strong>'
            . e(
                trim(
                    (
                        $row->surname
                        ??
                        ''
                    )
                    . ', '
                    . (
                        $row->first_names
                        ??
                        ''
                    )
                )
            )
            . '</strong>';

        if (
            $row->is_new_member
        ) {
            $memberName .=
                '<br><span class="badge bg-primary">Proposed Historical Member</span>';
        } elseif (
            $row->matched_member_id
        ) {
            $memberName .=
                '<br><span class="badge bg-success">Existing Member</span>';
        }

        /*
        |--------------------------------------------------------------------------
        | Employer
        |--------------------------------------------------------------------------
        */

        $employer =
            '<strong>'
            . e(
                $row->employer_name
                ??
                '-'
            )
            . '</strong>';

        if (
            filled(
                $row->penad_employer_number
            )
        ) {
            $employer .=
                '<br><small class="text-muted">PenAd: '
                . e(
                    $row->penad_employer_number
                )
                . '</small>';
        }

        /*
        |--------------------------------------------------------------------------
        | Period
        |--------------------------------------------------------------------------
        */

        $period =
            sprintf(
                '%02d/%04d',
                (int) $row->period_month,
                (int) $row->period_year
            );

        /*
        |--------------------------------------------------------------------------
        | Transaction Badge
        |--------------------------------------------------------------------------
        */

        $transactionType =
            strtolower(
                (string) $row->transaction_type
            );

        $transactionBadge =
            $transactionType === 'take_on'
                ? '<span class="badge bg-info text-dark">Take-On</span>'
                : '<span class="badge bg-secondary">Monthly</span>';

        /*
        |--------------------------------------------------------------------------
        | Service Status
        |--------------------------------------------------------------------------
        */

        $serviceStatus =
            match (
                $row->service_status
            ) {
                'contributed' =>
                    '<span class="badge bg-success">Contributed</span>',

                'zero_contribution' =>
                    '<span class="badge bg-warning text-dark">0.0000 Recorded</span>',

                'break_in_service' =>
                    '<span class="badge bg-danger">Break in Service</span>',

                default =>
                    '<span class="badge bg-secondary">'
                    . e(
                        ucwords(
                            str_replace(
                                '_',
                                ' ',
                                $row->service_status
                            )
                        )
                    )
                    . '</span>',
            };

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validation =
            match (
                $row->validation_status
            ) {
                'valid' =>
                    '<span class="badge bg-success">Valid</span>',

                'warning' =>
                    '<span class="badge bg-warning text-dark">Warning</span>',

                'error' =>
                    '<span class="badge bg-danger">Error</span>',

                default =>
                    '<span class="badge bg-secondary">'
                    . e(
                        ucfirst(
                            $row->validation_status
                        )
                    )
                    . '</span>',
            };

        /*
        |--------------------------------------------------------------------------
        | Duplicate
        |--------------------------------------------------------------------------
        */

        $duplicate =
            $row->duplicate_status === 'none'
                ? '<span class="badge bg-success">None</span>'
                : '<span class="badge bg-danger">'
                    . e(
                        ucwords(
                            str_replace(
                                '_',
                                ' ',
                                $row->duplicate_status
                            )
                        )
                    )
                    . '</span>';

        /*
        |--------------------------------------------------------------------------
        | Review
        |--------------------------------------------------------------------------
        */

        $review =
            match (
                $row->review_decision
            ) {
                'approved' =>
                    '<span class="badge bg-success">Approved</span>',

                'excluded' =>
                    '<span class="badge bg-danger">Excluded</span>',

                default =>
                    '<span class="badge bg-warning text-dark">Pending</span>',
            };

        /*
        |--------------------------------------------------------------------------
        | Messages
        |--------------------------------------------------------------------------
        */

        $messages =
            $this->formatMessages(
                $row
            );

        /*
        |--------------------------------------------------------------------------
        | Actions
        |--------------------------------------------------------------------------
        */

        $actions =
            $this->formatActions(
                $batch,
                $row
            );

        return [
            'source_row' =>
                number_format(
                    $row->source_row_number
                ),

            'penad_number' =>
                e(
                    $row->penad_member_number
                    ??
                    '-'
                ),

            'staff_number' =>
                e(
                    $row->staff_number
                    ??
                    '-'
                ),

            'member' =>
                $memberName,

            'national_id' =>
                e(
                    $row->national_id
                    ??
                    '-'
                ),

            'employer' =>
                $employer,

            'period' =>
                $period,

            'transaction_type' =>
                $transactionBadge,

            'employee_contribution' =>
                $row->employee_contribution === null
                    ? '<span class="text-muted">Blank</span>'
                    : number_format(
                        (float) $row->employee_contribution,
                        4
                    ),

            'employer_contribution' =>
                $row->employer_contribution === null
                    ? '<span class="text-muted">Blank</span>'
                    : number_format(
                        (float) $row->employer_contribution,
                        4
                    ),

            'service_status' =>
                $serviceStatus,

            'validation_status' =>
                $validation,

            'duplicate_status' =>
                $duplicate,

            'review_decision' =>
                $review,

            'messages' =>
                $messages,

            'actions' =>
                $actions,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */

    private function formatMessages(
        HistoricalContributionImportRow $row
    ): string {
        $messages = [];

        foreach (
            [
                [
                    'value' =>
                        $row->error_messages,

                    'class' =>
                        'text-danger',
                ],

                [
                    'value' =>
                        $row->warning_messages,

                    'class' =>
                        'text-warning',
                ],
            ]
            as $source
        ) {
            if (
                blank(
                    $source[
                        'value'
                    ]
                )
            ) {
                continue;
            }

            $decoded =
                json_decode(
                    $source[
                        'value'
                    ],
                    true
                );

            if (
                !is_array(
                    $decoded
                )
            ) {
                $decoded = [
                    $source[
                        'value'
                    ],
                ];
            }

            foreach (
                $decoded
                as $message
            ) {
                $messages[] =
                    '<div class="'
                    . $source[
                        'class'
                    ]
                    . ' mb-1">'
                    . e(
                        $message
                    )
                    . '</div>';
            }
        }

        if (
            empty(
                $messages
            )
        ) {
            return '<span class="text-muted">-</span>';
        }

        return implode(
            '',
            $messages
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    private function formatActions(
        HistoricalContributionImportBatch $batch,
        HistoricalContributionImportRow $row
    ): string {
        if (
            !in_array(
                $batch->status,
                [
                    'awaiting_review',
                    'posting_failed',
                ],
                true
            )
        ) {
            return '<span class="text-muted">Review closed</span>';
        }

        $url =
            route(
                'pensions-administration.historical-contributions.review.decision',
                [
                    'batch' =>
                        $batch->id,

                    'row' =>
                        $row->id,
                ]
            );

        $buttons =
            '<div class="d-flex flex-wrap gap-1">';

        if (
            $row->validation_status !== 'error'
            &&
            $row->duplicate_status === 'none'
        ) {
            $buttons .=
                '<button type="button"'
                . ' class="btn btn-sm btn-success review-row-btn"'
                . ' data-url="'
                . e(
                    $url
                )
                . '"'
                . ' data-decision="approved"'
                . ' title="Approve">'
                . '<i class="mdi mdi-check"></i>'
                . '</button>';
        }

        $buttons .=
            '<button type="button"'
            . ' class="btn btn-sm btn-outline-danger review-row-btn"'
            . ' data-url="'
            . e(
                $url
            )
            . '"'
            . ' data-decision="excluded"'
            . ' title="Exclude">'
            . '<i class="mdi mdi-close"></i>'
            . '</button>';

        if (
            $row->review_decision !== 'pending'
        ) {
            $buttons .=
                '<button type="button"'
                . ' class="btn btn-sm btn-outline-secondary review-row-btn"'
                . ' data-url="'
                . e(
                    $url
                )
                . '"'
                . ' data-decision="pending"'
                . ' title="Return to Pending">'
                . '<i class="mdi mdi-undo"></i>'
                . '</button>';
        }

        $buttons .=
            '</div>';

        return $buttons;
    }

    /*
    |--------------------------------------------------------------------------
    | Review Counts
    |--------------------------------------------------------------------------
    */

    private function reviewCounts(
        HistoricalContributionImportBatch $batch
    ): array {
        $base =
            HistoricalContributionImportRow::query()
                ->where(
                    'import_batch_id',
                    $batch->id
                );

        return [
            'total' =>
                (clone $base)
                    ->count(),

            'valid' =>
                (clone $base)
                    ->where(
                        'validation_status',
                        'valid'
                    )
                    ->count(),

            'warning' =>
                (clone $base)
                    ->where(
                        'validation_status',
                        'warning'
                    )
                    ->count(),

            'error' =>
                (clone $base)
                    ->where(
                        'validation_status',
                        'error'
                    )
                    ->count(),

            'duplicates' =>
                (clone $base)
                    ->where(
                        'duplicate_status',
                        '<>',
                        'none'
                    )
                    ->count(),

            'new_members' =>
                (clone $base)
                    ->where(
                        'is_new_member',
                        true
                    )
                    ->distinct(
                        'penad_member_number'
                    )
                    ->count(
                        'penad_member_number'
                    ),

            'breaks' =>
                (clone $base)
                    ->where(
                        'service_status',
                        'break_in_service'
                    )
                    ->count(),

            'approved' =>
                (clone $base)
                    ->where(
                        'review_decision',
                        'approved'
                    )
                    ->count(),

            'excluded' =>
                (clone $base)
                    ->where(
                        'review_decision',
                        'excluded'
                    )
                    ->count(),

            'pending' =>
                (clone $base)
                    ->where(
                        'review_decision',
                        'pending'
                    )
                    ->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Permission
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

    public function approveWarnings(
    HistoricalContributionImportBatch $batch
): RedirectResponse {
    $this->ensurePermission(
        'contributions.monthly-imports.approve'
    );

    if (
        !in_array(
            $batch->status,
            [
                'awaiting_review',
                'posting_failed',
            ],
            true
        )
    ) {
        return back()->with(
            'error',
            'This historical contribution batch is not open for review.'
        );
    }

    try {
        $approvedCount = 0;

        DB::transaction(
            function () use (
                $batch,
                &$approvedCount
            ): void {
                /*
                |--------------------------------------------------------------------------
                | Approve Warning Transactions Only
                |--------------------------------------------------------------------------
                |
                | We approve:
                |
                | validation_status = warning
                | duplicate_status  = none
                | review_decision   = pending
                |
                | Duplicate warning rows remain untouched.
                |
                */

                $approvedCount =
                    HistoricalContributionImportRow::query()
                        ->where(
                            'import_batch_id',
                            $batch->id
                        )
                        ->where(
                            'validation_status',
                            'warning'
                        )
                        ->where(
                            'duplicate_status',
                            'none'
                        )
                        ->where(
                            'review_decision',
                            'pending'
                        )
                        ->update([
                            'review_decision' =>
                                'approved',

                            'review_notes' =>
                                'Warning accepted during historical contribution review.',

                            'reviewed_by' =>
                                auth()->id(),

                            'reviewed_at' =>
                                now(),

                            'updated_at' =>
                                now(),
                        ]);
            }
        );

        return redirect()
            ->route(
                'pensions-administration.historical-contributions.review.index',
                $batch
            )
            ->with(
                'success',
                number_format($approvedCount)
                . ' warning transaction(s) approved successfully.'
            );

    } catch (Throwable $e) {
        return back()->with(
            'error',
            $e->getMessage()
        );
    }
}
}
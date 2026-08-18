<?php

namespace App\Services\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Models\PensionsAdministration\Contributions\ContributionImportRow;
use App\Models\PensionsAdministration\Contributions\ContributionPeriod;
use App\Models\PensionsAdministration\Contributions\ContributionPeriodMemberStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ContributionReconciliationService
{
    public function build(
        ContributionImportBatch $batch
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Load Batch
        |--------------------------------------------------------------------------
        */

        $batch->load([
            'employer',
            'contributionPeriod',
            'uploadedBy',
            'approvedBy',
            'postedBy',
        ]);


        $currentPeriod =
            $batch->contributionPeriod;


        if (!$currentPeriod) {
            throw new RuntimeException(
                'The contribution period could not be found for this batch.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Currency
        |--------------------------------------------------------------------------
        */

        $currency =
            strtoupper(
                $batch->currency_code
                ??
                'ZWG'
            );


        if (
            !in_array(
                $currency,
                [
                    'ZWG',
                    'USD',
                ],
                true
            )
        ) {
            $currency =
                'ZWG';
        }


        /*
        |--------------------------------------------------------------------------
        | Previous Period
        |--------------------------------------------------------------------------
        */

        $previousPeriod =
            ContributionPeriod::query()
                ->where(
                    'employer_id',
                    $batch->employer_id
                )
                ->where(
                    'period_date',
                    '<',
                    $currentPeriod->period_date
                )
                ->orderByDesc(
                    'period_date'
                )
                ->first();


        /*
        |--------------------------------------------------------------------------
        | Previous Posted Contributions
        |--------------------------------------------------------------------------
        */

        $previousContributions =
            $this->getPreviousContributions(
                $batch,
                $previousPeriod,
                $currency
            );


        /*
        |--------------------------------------------------------------------------
        | Current Validated Import Rows
        |--------------------------------------------------------------------------
        */

        $currentRows =
            ContributionImportRow::query()
                ->where(
                    'import_batch_id',
                    $batch->id
                )
                ->whereIn(
                    'validation_status',
                    [
                        'valid',
                        'warning',
                    ]
                )
                ->orderBy(
                    'row_number'
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Previous Nil Contributors
        |--------------------------------------------------------------------------
        */

        $previousNilMemberIds =
            collect();


        if ($previousPeriod) {
            $previousNilMemberIds =
                ContributionPeriodMemberStatus::query()
                    ->where(
                        'contribution_period_id',
                        $previousPeriod->id
                    )
                    ->where(
                        'employer_id',
                        $batch->employer_id
                    )
                    ->where(
                        'contribution_status',
                        'nil_contributor'
                    )
                    ->pluck(
                        'member_id'
                    )
                    ->filter()
                    ->unique()
                    ->values();
        }


        /*
        |--------------------------------------------------------------------------
        | Current Existing Members
        |--------------------------------------------------------------------------
        */

        $currentExistingMemberIds =
            $currentRows
                ->where(
                    'is_new_member',
                    false
                )
                ->pluck(
                    'matched_member_id'
                )
                ->filter()
                ->unique()
                ->values();


        /*
        |--------------------------------------------------------------------------
        | Reinstatements
        |--------------------------------------------------------------------------
        */

        $reinstatedMemberIds =
            $currentExistingMemberIds
                ->intersect(
                    $previousNilMemberIds
                )
                ->unique()
                ->values();


        /*
        |--------------------------------------------------------------------------
        | Current Nil Contributors
        |--------------------------------------------------------------------------
        */

        $currentNilMemberIds =
            ContributionPeriodMemberStatus::query()
                ->where(
                    'contribution_period_id',
                    $currentPeriod->id
                )
                ->where(
                    'employer_id',
                    $batch->employer_id
                )
                ->where(
                    'contribution_status',
                    'nil_contributor'
                )
                ->pluck(
                    'member_id'
                )
                ->filter()
                ->unique()
                ->values();


        /*
        |--------------------------------------------------------------------------
        | Previous Membership
        |--------------------------------------------------------------------------
        */

        $previousMemberIds =
            $previousContributions
                ->keys()
                ->merge(
                    $previousNilMemberIds
                )
                ->filter()
                ->unique()
                ->values();


        $previousMembership =
            $previousMemberIds
                ->count();


        /*
        |--------------------------------------------------------------------------
        | New Members
        |--------------------------------------------------------------------------
        */

        $newMemberRows =
            $currentRows
                ->where(
                    'is_new_member',
                    true
                );


        $newMembers =
            $newMemberRows
                ->count();


        /*
        |--------------------------------------------------------------------------
        | Membership Reconciliation
        |--------------------------------------------------------------------------
        */

        $reinstatements =
            $reinstatedMemberIds
                ->count();


        $nilContributors =
            $currentNilMemberIds
                ->count();


        $currentMembership =
            $previousMembership
            +
            $newMembers
            +
            $reinstatements
            -
            $nilContributors;


        /*
        |--------------------------------------------------------------------------
        | Previous Period Totals
        |--------------------------------------------------------------------------
        */

        $previousNormalContributionTotal =
            (float)
            $previousContributions
                ->sum(
                    'normal'
                );


        $previousAvcTotal =
            (float)
            $previousContributions
                ->sum(
                    'avc'
                );


        $previousTotalExpected =
            $previousNormalContributionTotal
            +
            $previousAvcTotal;


        /*
        |--------------------------------------------------------------------------
        | New Member Contributions
        |--------------------------------------------------------------------------
        */

        $newMemberNormalContribution =
            (float)
            $newMemberRows
                ->sum(
                    function (
                        ContributionImportRow $row
                    ) use (
                        $currency
                    ): float {
                        return $this
                            ->rowAmounts(
                                $row,
                                $currency
                            )[
                                'normal'
                            ];
                    }
                );


        $newMemberAvc =
            (float)
            $newMemberRows
                ->sum(
                    function (
                        ContributionImportRow $row
                    ) use (
                        $currency
                    ): float {
                        return $this
                            ->rowAmounts(
                                $row,
                                $currency
                            )[
                                'avc'
                            ];
                    }
                );


        $newMemberTotal =
            $newMemberNormalContribution
            +
            $newMemberAvc;


        /*
        |--------------------------------------------------------------------------
        | Reinstatement Contribution Rows
        |--------------------------------------------------------------------------
        */

        $reinstatementRows =
            $currentRows
                ->filter(
                    function (
                        ContributionImportRow $row
                    ) use (
                        $reinstatedMemberIds
                    ): bool {
                        if (
                            !$row->matched_member_id
                        ) {
                            return false;
                        }


                        return $reinstatedMemberIds
                            ->contains(
                                $row->matched_member_id
                            );
                    }
                );


        /*
        |--------------------------------------------------------------------------
        | Reinstatement Normal Contributions
        |--------------------------------------------------------------------------
        */

        $reinstatementNormalContribution =
            (float)
            $reinstatementRows
                ->sum(
                    function (
                        ContributionImportRow $row
                    ) use (
                        $currency
                    ): float {
                        return $this
                            ->rowAmounts(
                                $row,
                                $currency
                            )[
                                'normal'
                            ];
                    }
                );


        /*
        |--------------------------------------------------------------------------
        | Reinstatement AVC
        |--------------------------------------------------------------------------
        */

        $reinstatementAvc =
            (float)
            $reinstatementRows
                ->sum(
                    function (
                        ContributionImportRow $row
                    ) use (
                        $currency
                    ): float {
                        return $this
                            ->rowAmounts(
                                $row,
                                $currency
                            )[
                                'avc'
                            ];
                    }
                );


        $reinstatementTotal =
            $reinstatementNormalContribution
            +
            $reinstatementAvc;


        /*
        |--------------------------------------------------------------------------
        | Nil Contributor Reduction
        |--------------------------------------------------------------------------
        */

        $nilNormalReduction =
            0.0;


        $nilAvcReduction =
            0.0;


        foreach (
            $currentNilMemberIds
            as $memberId
        ) {
            if (
                !$previousContributions
                    ->has(
                        $memberId
                    )
            ) {
                continue;
            }


            $previous =
                $previousContributions[
                    $memberId
                ];


            $nilNormalReduction +=
                (float) (
                    $previous[
                        'normal'
                    ]
                    ??
                    0
                );


            $nilAvcReduction +=
                (float) (
                    $previous[
                        'avc'
                    ]
                    ??
                    0
                );
        }


        $nilTotalReduction =
            $nilNormalReduction
            +
            $nilAvcReduction;


        /*
        |--------------------------------------------------------------------------
        | Current Existing Contribution Map
        |--------------------------------------------------------------------------
        */

        $currentContributions =
            $this->buildCurrentContributionMap(
                $currentRows,
                $currency
            );


        /*
        |--------------------------------------------------------------------------
        | Excluded Members
        |--------------------------------------------------------------------------
        */

        $excludedMemberIds =
            $reinstatedMemberIds
                ->merge(
                    $currentNilMemberIds
                )
                ->filter()
                ->unique()
                ->values();


        /*
        |--------------------------------------------------------------------------
        | Increase / Decrease - Normal Contributions
        |--------------------------------------------------------------------------
        */

        $normalIncreaseDecrease =
            0.0;


        /*
        |--------------------------------------------------------------------------
        | Increase / Decrease - AVC
        |--------------------------------------------------------------------------
        */

        $avcIncreaseDecrease =
            0.0;


        foreach (
            $currentContributions
            as $memberId => $currentAmount
        ) {
            if (
                $excludedMemberIds
                    ->contains(
                        $memberId
                    )
            ) {
                continue;
            }


            if (
                !$previousContributions
                    ->has(
                        $memberId
                    )
            ) {
                continue;
            }


            $previousAmount =
                $previousContributions[
                    $memberId
                ];


            $normalIncreaseDecrease +=
                (
                    (float) (
                        $currentAmount[
                            'normal'
                        ]
                        ??
                        0
                    )
                )
                -
                (
                    (float) (
                        $previousAmount[
                            'normal'
                        ]
                        ??
                        0
                    )
                );


            $avcIncreaseDecrease +=
                (
                    (float) (
                        $currentAmount[
                            'avc'
                        ]
                        ??
                        0
                    )
                )
                -
                (
                    (float) (
                        $previousAmount[
                            'avc'
                        ]
                        ??
                        0
                    )
                );
        }


        $totalIncreaseDecrease =
            $normalIncreaseDecrease
            +
            $avcIncreaseDecrease;


        /*
        |--------------------------------------------------------------------------
        | Manual Differences / Adjustments
        |--------------------------------------------------------------------------
        |
        | These will later incorporate negative contribution corrections and
        | other approved reconciliation adjustments.
        |
        */

        $normalDifferences =
            0.0;


        $avcDifferences =
            0.0;


        $totalDifferences =
            $normalDifferences
            +
            $avcDifferences;


        /*
        |--------------------------------------------------------------------------
        | Reconciled Normal Contributions
        |--------------------------------------------------------------------------
        */

        $normalContributionDue =
            $previousNormalContributionTotal
            +
            $newMemberNormalContribution
            +
            $reinstatementNormalContribution
            +
            $normalIncreaseDecrease
            +
            $normalDifferences
            -
            $nilNormalReduction;


        /*
        |--------------------------------------------------------------------------
        | Reconciled AVC
        |--------------------------------------------------------------------------
        */

        $avcDue =
            $previousAvcTotal
            +
            $newMemberAvc
            +
            $reinstatementAvc
            +
            $avcIncreaseDecrease
            +
            $avcDifferences
            -
            $nilAvcReduction;


        /*
        |--------------------------------------------------------------------------
        | Total Expected Contributions Due
        |--------------------------------------------------------------------------
        */

        $totalContributionDue =
            $normalContributionDue
            +
            $avcDue;


        /*
        |--------------------------------------------------------------------------
        | Current Schedule Totals
        |--------------------------------------------------------------------------
        */

        $schedule =
            $this->scheduleTotals(
                $currentRows,
                $currency
            );


        $scheduleNormal =
            $schedule[
                'normal_contributions'
            ];


        $scheduleAvc =
            $schedule[
                'avc'
            ];


        $scheduleTotal =
            $schedule[
                'total_expected'
            ];


        /*
        |--------------------------------------------------------------------------
        | Variances
        |--------------------------------------------------------------------------
        */

        $normalVariance =
            $normalContributionDue
            -
            $scheduleNormal;


        $avcVariance =
            $avcDue
            -
            $scheduleAvc;


        $totalVariance =
            $totalContributionDue
            -
            $scheduleTotal;


        /*
        |--------------------------------------------------------------------------
        | ZWG Schedule
        |--------------------------------------------------------------------------
        */

        $zwgSchedule =
            $this->scheduleTotals(
                $currentRows,
                'ZWG'
            );


        /*
        |--------------------------------------------------------------------------
        | USD Schedule
        |--------------------------------------------------------------------------
        */

        $usdSchedule =
            $this->scheduleTotals(
                $currentRows,
                'USD'
            );


        /*
        |--------------------------------------------------------------------------
        | Return Report
        |--------------------------------------------------------------------------
        */

        return [
            'batch' =>
                $batch,

            'employer' =>
                $batch->employer,

            'current_period' =>
                $currentPeriod,

            'previous_period' =>
                $previousPeriod,

            'currency' =>
                $currency,


            /*
            |--------------------------------------------------------------------------
            | Membership
            |--------------------------------------------------------------------------
            */

            'membership' => [
                'previous' =>
                    $previousMembership,

                'new_members' =>
                    $newMembers,

                'reinstatements' =>
                    $reinstatements,

                'less_exits_suspended_nil' =>
                    $nilContributors,

                'current' =>
                    $currentMembership,
            ],


            /*
            |--------------------------------------------------------------------------
            | Contributions
            |--------------------------------------------------------------------------
            */

            'contributions' => [

                /*
                | Opening
                */

                'previous_normal' =>
                    $previousNormalContributionTotal,

                'previous_avc' =>
                    $previousAvcTotal,

                'previous_total' =>
                    $previousTotalExpected,


                /*
                | New Members
                */

                'new_members_normal' =>
                    $newMemberNormalContribution,

                'new_members_avc' =>
                    $newMemberAvc,

                'new_members_total' =>
                    $newMemberTotal,


                /*
                | Reinstatements
                */

                'reinstatements_normal' =>
                    $reinstatementNormalContribution,

                'reinstatements_avc' =>
                    $reinstatementAvc,

                'reinstatements_total' =>
                    $reinstatementTotal,


                /*
                | Increase / Decrease
                */

                'increase_decrease_normal' =>
                    $normalIncreaseDecrease,

                'increase_decrease_avc' =>
                    $avcIncreaseDecrease,

                'increase_decrease_total' =>
                    $totalIncreaseDecrease,


                /*
                | Differences
                */

                'differences_normal' =>
                    $normalDifferences,

                'differences_avc' =>
                    $avcDifferences,

                'differences_total' =>
                    $totalDifferences,


                /*
                | Nil Contributors
                */

                'less_nil_normal' =>
                    $nilNormalReduction,

                'less_nil_avc' =>
                    $nilAvcReduction,

                'less_nil_total' =>
                    $nilTotalReduction,


                /*
                | Reconciled Totals
                */

                'normal_due' =>
                    $normalContributionDue,

                'avc_due' =>
                    $avcDue,

                'total_due' =>
                    $totalContributionDue,


                /*
                | Current Schedule
                */

                'schedule_normal' =>
                    $scheduleNormal,

                'schedule_avc' =>
                    $scheduleAvc,

                'schedule_total' =>
                    $scheduleTotal,


                /*
                | Variances
                */

                'normal_variance' =>
                    $normalVariance,

                'avc_variance' =>
                    $avcVariance,

                'variance' =>
                    $totalVariance,


                /*
                |--------------------------------------------------------------------------
                | Compatibility With Existing Blade
                |--------------------------------------------------------------------------
                */

                'previous' =>
                    $previousTotalExpected,

                'new_members' =>
                    $newMemberTotal,

                'reinstatements' =>
                    $reinstatementTotal,

                'increase_decrease' =>
                    $totalIncreaseDecrease,

                'differences' =>
                    $totalDifferences,

                'less_exits_suspended_nil' =>
                    $nilTotalReduction,
            ],


            /*
            |--------------------------------------------------------------------------
            | ZWG
            |--------------------------------------------------------------------------
            */

            'zwg' =>
                $zwgSchedule,


            /*
            |--------------------------------------------------------------------------
            | USD
            |--------------------------------------------------------------------------
            */

            'usd' =>
                $usdSchedule,


            /*
            |--------------------------------------------------------------------------
            | Supporting IDs
            |--------------------------------------------------------------------------
            */

            'reinstated_member_ids' =>
                $reinstatedMemberIds,

            'nil_member_ids' =>
                $currentNilMemberIds,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Previous Posted Contributions
    |--------------------------------------------------------------------------
    */

    private function getPreviousContributions(
        ContributionImportBatch $batch,
        ?ContributionPeriod $previousPeriod,
        string $currency
    ): Collection {
        if (!$previousPeriod) {
            return collect();
        }


        $prefix =
            strtolower(
                $currency
            );


        $employeeColumn =
            $prefix
            .
            '_employee_contribution';


        $employerColumn =
            $prefix
            .
            '_employer_contribution';


        $employeeAvcColumn =
            $prefix
            .
            '_employee_avc';


        $employerAvcColumn =
            $prefix
            .
            '_employer_avc';


        return DB::table(
            'member_contributions'
        )
            ->where(
                'contribution_period_id',
                $previousPeriod->id
            )
            ->where(
                'employer_id',
                $batch->employer_id
            )
            ->select([
                'member_id',
                $employeeColumn,
                $employerColumn,
                $employeeAvcColumn,
                $employerAvcColumn,
            ])
            ->get()
            ->groupBy(
                'member_id'
            )
            ->map(
                function (
                    Collection $rows
                ) use (
                    $employeeColumn,
                    $employerColumn,
                    $employeeAvcColumn,
                    $employerAvcColumn
                ): array {
                    $employeeContribution =
                        (float)
                        $rows->sum(
                            $employeeColumn
                        );


                    $employerContribution =
                        (float)
                        $rows->sum(
                            $employerColumn
                        );


                    $employeeAvc =
                        (float)
                        $rows->sum(
                            $employeeAvcColumn
                        );


                    $employerAvc =
                        (float)
                        $rows->sum(
                            $employerAvcColumn
                        );


                    return [
                        'employee_contribution' =>
                            $employeeContribution,

                        'employer_contribution' =>
                            $employerContribution,

                        'employee_avc' =>
                            $employeeAvc,

                        'employer_avc' =>
                            $employerAvc,

                        'normal' =>
                            $employeeContribution
                            +
                            $employerContribution,

                        'avc' =>
                            $employeeAvc
                            +
                            $employerAvc,

                        'total' =>
                            $employeeContribution
                            +
                            $employerContribution
                            +
                            $employeeAvc
                            +
                            $employerAvc,
                    ];
                }
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Current Contribution Map
    |--------------------------------------------------------------------------
    */

    private function buildCurrentContributionMap(
        Collection $rows,
        string $currency
    ): Collection {
        return $rows
            ->filter(
                function (
                    ContributionImportRow $row
                ): bool {
                    return
                        !$row->is_new_member
                        &&
                        filled(
                            $row->matched_member_id
                        );
                }
            )
            ->groupBy(
                'matched_member_id'
            )
            ->map(
                function (
                    Collection $memberRows
                ) use (
                    $currency
                ): array {
                    $normal =
                        0.0;


                    $avc =
                        0.0;


                    foreach (
                        $memberRows
                        as $row
                    ) {
                        $amounts =
                            $this->rowAmounts(
                                $row,
                                $currency
                            );


                        $normal +=
                            $amounts[
                                'normal'
                            ];


                        $avc +=
                            $amounts[
                                'avc'
                            ];
                    }


                    return [
                        'normal' =>
                            $normal,

                        'avc' =>
                            $avc,

                        'total' =>
                            $normal
                            +
                            $avc,
                    ];
                }
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Amounts From Import Row
    |--------------------------------------------------------------------------
    */

    private function rowAmounts(
        ContributionImportRow $row,
        string $currency
    ): array {
        $data =
            $row->normalized_data
            ??
            [];


        $prefix =
            strtolower(
                $currency
            );


        $employeeContribution =
            (float) (
                $data[
                    $prefix
                    .
                    '_employee_contribution'
                ]
                ??
                0
            );


        $employerContribution =
            (float) (
                $data[
                    $prefix
                    .
                    '_employer_contribution'
                ]
                ??
                0
            );


        $employeeAvc =
            (float) (
                $data[
                    $prefix
                    .
                    '_employee_avc'
                ]
                ??
                0
            );


        $employerAvc =
            (float) (
                $data[
                    $prefix
                    .
                    '_employer_avc'
                ]
                ??
                0
            );


        $normal =
            $employeeContribution
            +
            $employerContribution;


        $avc =
            $employeeAvc
            +
            $employerAvc;


        return [
            'employee_contribution' =>
                $employeeContribution,

            'employer_contribution' =>
                $employerContribution,

            'employee_avc' =>
                $employeeAvc,

            'employer_avc' =>
                $employerAvc,

            'normal' =>
                $normal,

            'avc' =>
                $avc,

            'total' =>
                $normal
                +
                $avc,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Schedule Totals By Currency
    |--------------------------------------------------------------------------
    */

    private function scheduleTotals(
        Collection $rows,
        string $currency
    ): array {
        $prefix =
            strtolower(
                $currency
            );


        $basicPay =
            0.0;


        $employeeContribution =
            0.0;


        $employerContribution =
            0.0;


        $employeeAvc =
            0.0;


        $employerAvc =
            0.0;


        foreach (
            $rows
            as $row
        ) {
            $data =
                $row->normalized_data
                ??
                [];


            $basicPay +=
                (float) (
                    $data[
                        $prefix
                        .
                        '_basic_pay'
                    ]
                    ??
                    0
                );


            $employeeContribution +=
                (float) (
                    $data[
                        $prefix
                        .
                        '_employee_contribution'
                    ]
                    ??
                    0
                );


            $employerContribution +=
                (float) (
                    $data[
                        $prefix
                        .
                        '_employer_contribution'
                    ]
                    ??
                    0
                );


            $employeeAvc +=
                (float) (
                    $data[
                        $prefix
                        .
                        '_employee_avc'
                    ]
                    ??
                    0
                );


            $employerAvc +=
                (float) (
                    $data[
                        $prefix
                        .
                        '_employer_avc'
                    ]
                    ??
                    0
                );
        }


        $normalContributions =
            $employeeContribution
            +
            $employerContribution;


        $avc =
            $employeeAvc
            +
            $employerAvc;


        return [
            'basic_pay' =>
                $basicPay,

            'employee_contribution' =>
                $employeeContribution,

            'employer_contribution' =>
                $employerContribution,

            'employee_avc' =>
                $employeeAvc,

            'employer_avc' =>
                $employerAvc,

            'normal_contributions' =>
                $normalContributions,

            'avc' =>
                $avc,

            'total_expected' =>
                $normalContributions
                +
                $avc,
        ];
    }
}
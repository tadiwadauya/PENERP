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
    /*
    |--------------------------------------------------------------------------
    | Build Reconciliation
    |--------------------------------------------------------------------------
    */

    public function build(
        ContributionImportBatch $batch
    ): array {
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
                trim(
                    (string) (
                        $batch->currency_code
                        ??
                        'ZWG'
                    )
                )
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
        | Current Validated Rows
        |--------------------------------------------------------------------------
        |
        | Error rows are deliberately excluded.
        |
        | Warning rows remain part of reconciliation because warnings do not
        | prevent approval.
        |
        */

        $currentRows =
            ContributionImportRow::query()
                ->with([
                    'matchedMember',
                    'createdMember',
                ])
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
        |
        | A member was NIL last period and has contributions this period.
        |
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
        | Reinstatement Rows
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
                        return
                            !$row->is_new_member
                            &&
                            filled(
                                $row->matched_member_id
                            )
                            &&
                            $reinstatedMemberIds
                                ->contains(
                                    $row->matched_member_id
                                );
                    }
                );


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
        | Previous Contribution Totals
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
                    fn (
                        ContributionImportRow $row
                    ): float =>
                        $this->rowAmounts(
                            $row,
                            $currency
                        )[
                            'normal'
                        ]
                );


        $newMemberAvc =
            (float)
            $newMemberRows
                ->sum(
                    fn (
                        ContributionImportRow $row
                    ): float =>
                        $this->rowAmounts(
                            $row,
                            $currency
                        )[
                            'avc'
                        ]
                );


        $newMemberTotal =
            $newMemberNormalContribution
            +
            $newMemberAvc;


        /*
        |--------------------------------------------------------------------------
        | Reinstatement Contributions
        |--------------------------------------------------------------------------
        */

        $reinstatementNormalContribution =
            (float)
            $reinstatementRows
                ->sum(
                    fn (
                        ContributionImportRow $row
                    ): float =>
                        $this->rowAmounts(
                            $row,
                            $currency
                        )[
                            'normal'
                        ]
                );


        $reinstatementAvc =
            (float)
            $reinstatementRows
                ->sum(
                    fn (
                        ContributionImportRow $row
                    ): float =>
                        $this->rowAmounts(
                            $row,
                            $currency
                        )[
                            'avc'
                        ]
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
        | Excluded Existing Members
        |--------------------------------------------------------------------------
        |
        | Reinstatements and nil contributors are already separately reconciled.
        |
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
        | Increase / Decrease
        |--------------------------------------------------------------------------
        */

        $normalIncreaseDecrease =
            0.0;


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
        | Manual Differences
        |--------------------------------------------------------------------------
        |
        | Reserved for approved adjustment/reconciliation entries.
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
        | Movement Reconciliation - Normal Contributions
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
        | Movement Reconciliation - AVC
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
        | Movement Total
        |--------------------------------------------------------------------------
        */

        $totalContributionDue =
            $normalContributionDue
            +
            $avcDue;


        /*
        |--------------------------------------------------------------------------
        | Uploaded Schedule Totals
        |--------------------------------------------------------------------------
        */

        $schedule =
            $this->scheduleTotals(
                $currentRows,
                $currency
            );


        /*
        |--------------------------------------------------------------------------
        | SYSTEM CALCULATED CONTRIBUTIONS
        |--------------------------------------------------------------------------
        |
        | New member employee contribution = salary × 6%
        |
        | Existing member employee contribution = salary × uploaded employee
        | rate. Existing rate itself is separately validated to ensure it is
        | between 5% and 6%.
        |
        | Employer contribution = salary × 17.3%
        |
        | AVC does not currently have a salary-rate formula, therefore the
        | uploaded AVC remains the system AVC for reconciliation.
        |
        */

        $calculation =
            $this->systemCalculationTotals(
                $currentRows,
                $currency
            );


        /*
        |--------------------------------------------------------------------------
        | System vs Schedule Variances
        |--------------------------------------------------------------------------
        */

        $employeeVariance =
            $calculation[
                'employee_contribution'
            ]
            -
            $schedule[
                'employee_contribution'
            ];


        $employerVariance =
            $calculation[
                'employer_contribution'
            ]
            -
            $schedule[
                'employer_contribution'
            ];


        $normalCalculationVariance =
            $calculation[
                'normal_contributions'
            ]
            -
            $schedule[
                'normal_contributions'
            ];


        $avcCalculationVariance =
            $calculation[
                'avc'
            ]
            -
            $schedule[
                'avc'
            ];


        $calculationVariance =
            $calculation[
                'total_expected'
            ]
            -
            $schedule[
                'total_expected'
            ];


        /*
        |--------------------------------------------------------------------------
        | Movement vs Schedule Variance
        |--------------------------------------------------------------------------
        |
        | Kept because this is the traditional monthly reconciliation.
        |
        */

        $movementNormalVariance =
            $normalContributionDue
            -
            $schedule[
                'normal_contributions'
            ];


        $movementAvcVariance =
            $avcDue
            -
            $schedule[
                'avc'
            ];


        $movementTotalVariance =
            $totalContributionDue
            -
            $schedule[
                'total_expected'
            ];


        /*
        |--------------------------------------------------------------------------
        | Row-by-Row Calculation Details
        |--------------------------------------------------------------------------
        */

        $calculationRows =
            $currentRows
                ->map(
                    fn (
                        ContributionImportRow $row
                    ): array =>
                        $this->buildCalculationRow(
                            $row,
                            $currency
                        )
                )
                ->values();


        /*
        |--------------------------------------------------------------------------
        | Both Currency Schedule Summaries
        |--------------------------------------------------------------------------
        */

        $zwgSchedule =
            $this->scheduleTotals(
                $currentRows,
                'ZWG'
            );


        $usdSchedule =
            $this->scheduleTotals(
                $currentRows,
                'USD'
            );


        $zwgSystemCalculation =
            $this->systemCalculationTotals(
                $currentRows,
                'ZWG'
            );


        $usdSystemCalculation =
            $this->systemCalculationTotals(
                $currentRows,
                'USD'
            );


        /*
        |--------------------------------------------------------------------------
        | Exception Counters
        |--------------------------------------------------------------------------
        */

        $rateExceptionRows =
            $currentRows
                ->filter(
                    fn (
                        ContributionImportRow $row
                    ): bool =>
                        $this->rowHasWarningPrefix(
                            $row,
                            'RATE EXCEPTION'
                        )
                )
                ->count();


        $contributionExceptionRows =
            $currentRows
                ->filter(
                    fn (
                        ContributionImportRow $row
                    ): bool =>
                        $this->rowHasWarningPrefix(
                            $row,
                            'CONTRIBUTION EXCEPTION'
                        )
                )
                ->count();


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
            | Traditional Contribution Movement Reconciliation
            |--------------------------------------------------------------------------
            */

            'contributions' => [
                'previous_normal' =>
                    $previousNormalContributionTotal,

                'previous_avc' =>
                    $previousAvcTotal,

                'previous_total' =>
                    $previousTotalExpected,

                'new_members_normal' =>
                    $newMemberNormalContribution,

                'new_members_avc' =>
                    $newMemberAvc,

                'new_members_total' =>
                    $newMemberTotal,

                'reinstatements_normal' =>
                    $reinstatementNormalContribution,

                'reinstatements_avc' =>
                    $reinstatementAvc,

                'reinstatements_total' =>
                    $reinstatementTotal,

                'increase_decrease_normal' =>
                    $normalIncreaseDecrease,

                'increase_decrease_avc' =>
                    $avcIncreaseDecrease,

                'increase_decrease_total' =>
                    $totalIncreaseDecrease,

                'differences_normal' =>
                    $normalDifferences,

                'differences_avc' =>
                    $avcDifferences,

                'differences_total' =>
                    $totalDifferences,

                'less_nil_normal' =>
                    $nilNormalReduction,

                'less_nil_avc' =>
                    $nilAvcReduction,

                'less_nil_total' =>
                    $nilTotalReduction,

                'normal_due' =>
                    $normalContributionDue,

                'avc_due' =>
                    $avcDue,

                'total_due' =>
                    $totalContributionDue,

                'schedule_normal' =>
                    $schedule[
                        'normal_contributions'
                    ],

                'schedule_avc' =>
                    $schedule[
                        'avc'
                    ],

                'schedule_total' =>
                    $schedule[
                        'total_expected'
                    ],

                'normal_variance' =>
                    $movementNormalVariance,

                'avc_variance' =>
                    $movementAvcVariance,

                'variance' =>
                    $movementTotalVariance,


                /*
                |--------------------------------------------------------------------------
                | Backward Compatibility
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
            | Uploaded Schedule
            |--------------------------------------------------------------------------
            */

            'schedule' =>
                $schedule,


            /*
            |--------------------------------------------------------------------------
            | System Calculation
            |--------------------------------------------------------------------------
            */

            'calculation' => [
                ...$calculation,

                'employee_variance' =>
                    $employeeVariance,

                'employer_variance' =>
                    $employerVariance,

                'normal_variance' =>
                    $normalCalculationVariance,

                'avc_variance' =>
                    $avcCalculationVariance,

                'variance' =>
                    $calculationVariance,
            ],


            /*
            |--------------------------------------------------------------------------
            | Row Details
            |--------------------------------------------------------------------------
            */

            'calculation_rows' =>
                $calculationRows,


            /*
            |--------------------------------------------------------------------------
            | Exceptions
            |--------------------------------------------------------------------------
            */

            'exceptions' => [
                'rate_rows' =>
                    $rateExceptionRows,

                'contribution_rows' =>
                    $contributionExceptionRows,

                'warning_rows' =>
                    (int) (
                        $batch->warning_rows
                        ??
                        0
                    ),

                'error_rows' =>
                    (int) (
                        $batch->error_rows
                        ??
                        0
                    ),
            ],


            /*
            |--------------------------------------------------------------------------
            | ZWG
            |--------------------------------------------------------------------------
            */

            'zwg' => [
                'schedule' =>
                    $zwgSchedule,

                'calculation' =>
                    $zwgSystemCalculation,

                'variance' =>
                    $zwgSystemCalculation[
                        'total_expected'
                    ]
                    -
                    $zwgSchedule[
                        'total_expected'
                    ],
            ],


            /*
            |--------------------------------------------------------------------------
            | USD
            |--------------------------------------------------------------------------
            */

            'usd' => [
                'schedule' =>
                    $usdSchedule,

                'calculation' =>
                    $usdSystemCalculation,

                'variance' =>
                    $usdSystemCalculation[
                        'total_expected'
                    ]
                    -
                    $usdSchedule[
                        'total_expected'
                    ],
            ],


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
            . '_employee_contribution';


        $employerColumn =
            $prefix
            . '_employer_contribution';


        $employeeAvcColumn =
            $prefix
            . '_employee_avc';


        $employerAvcColumn =
            $prefix
            . '_employer_avc';


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
    | Current Existing Contribution Map
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
    | Uploaded Amounts From Row
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
                    . '_employee_contribution'
                ]
                ??
                0
            );


        $employerContribution =
            (float) (
                $data[
                    $prefix
                    . '_employer_contribution'
                ]
                ??
                0
            );


        $employeeAvc =
            (float) (
                $data[
                    $prefix
                    . '_employee_avc'
                ]
                ??
                0
            );


        $employerAvc =
            (float) (
                $data[
                    $prefix
                    . '_employer_avc'
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
    | Uploaded Schedule Totals
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
                        . '_basic_pay'
                    ]
                    ??
                    0
                );


            $employeeContribution +=
                (float) (
                    $data[
                        $prefix
                        . '_employee_contribution'
                    ]
                    ??
                    0
                );


            $employerContribution +=
                (float) (
                    $data[
                        $prefix
                        . '_employer_contribution'
                    ]
                    ??
                    0
                );


            $employeeAvc +=
                (float) (
                    $data[
                        $prefix
                        . '_employee_avc'
                    ]
                    ??
                    0
                );


            $employerAvc +=
                (float) (
                    $data[
                        $prefix
                        . '_employer_avc'
                    ]
                    ??
                    0
                );
        }


        $normal =
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
                $normal,

            'avc' =>
                $avc,

            'total_expected' =>
                $normal
                +
                $avc,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | System Calculation Totals
    |--------------------------------------------------------------------------
    */

    private function systemCalculationTotals(
        Collection $rows,
        string $currency
    ): array {
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
            $calculated =
                $this->systemAmounts(
                    $row,
                    $currency
                );


            $basicPay +=
                $calculated[
                    'basic_pay'
                ];


            $employeeContribution +=
                $calculated[
                    'employee_contribution'
                ];


            $employerContribution +=
                $calculated[
                    'employer_contribution'
                ];


            $employeeAvc +=
                $calculated[
                    'employee_avc'
                ];


            $employerAvc +=
                $calculated[
                    'employer_avc'
                ];
        }


        $normal =
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
                $normal,

            'avc' =>
                $avc,

            'total_expected' =>
                $normal
                +
                $avc,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | System Calculation For One Row
    |--------------------------------------------------------------------------
    */

    private function systemAmounts(
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


        $basicPay =
            (float) (
                $data[
                    $prefix
                    . '_basic_pay'
                ]
                ??
                0
            );


        $uploadedEmployeeRate =
            $this->normaliseRate(
                $data[
                    $prefix
                    . '_employee_rate'
                ]
                ??
                0
            );


        /*
        |--------------------------------------------------------------------------
        | Employee Rate
        |--------------------------------------------------------------------------
        */

        $systemEmployeeRate =
            $row->is_new_member
                ? 6.00
                : $uploadedEmployeeRate;


        /*
        |--------------------------------------------------------------------------
        | System Employee Contribution
        |--------------------------------------------------------------------------
        */

        $employeeContribution =
            round(
                $basicPay
                *
                (
                    $systemEmployeeRate
                    /
                    100
                ),
                2
            );


        /*
        |--------------------------------------------------------------------------
        | System Employer Contribution
        |--------------------------------------------------------------------------
        */

        $employerContribution =
            round(
                $basicPay
                *
                0.173,
                2
            );


        /*
        |--------------------------------------------------------------------------
        | AVC
        |--------------------------------------------------------------------------
        |
        | AVC has no pensionable salary percentage rule in the rules supplied
        | so it remains equal to the uploaded amount.
        |
        */

        $employeeAvc =
            (float) (
                $data[
                    $prefix
                    . '_employee_avc'
                ]
                ??
                0
            );


        $employerAvc =
            (float) (
                $data[
                    $prefix
                    . '_employer_avc'
                ]
                ??
                0
            );


        return [
            'basic_pay' =>
                $basicPay,

            'employee_rate' =>
                $systemEmployeeRate,

            'employer_rate' =>
                17.30,

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


    /*
    |--------------------------------------------------------------------------
    | Calculation Row For Report
    |--------------------------------------------------------------------------
    */

    private function buildCalculationRow(
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


        $system =
            $this->systemAmounts(
                $row,
                $currency
            );


        $schedule =
            $this->rowAmounts(
                $row,
                $currency
            );


        $uploadedEmployeeRate =
            $this->normaliseRate(
                $data[
                    $prefix
                    . '_employee_rate'
                ]
                ??
                0
            );


        $uploadedEmployerRate =
            $this->normaliseRate(
                $data[
                    $prefix
                    . '_employer_rate'
                ]
                ??
                0
            );


        $member =
            $row->matchedMember
            ??
            $row->createdMember;


        $memberName =
            trim(
                (
                    $data[
                        'surname'
                    ]
                    ??
                    $member?->surname
                    ??
                    ''
                )
                . ' '
                . (
                    $data[
                        'first_names'
                    ]
                    ??
                    $member?->first_names
                    ??
                    ''
                )
                . ' '
                . (
                    $data[
                        'other_names'
                    ]
                    ??
                    $member?->other_names
                    ??
                    ''
                )
            );


        return [
            'row_number' =>
                $row->row_number,

            'member_type' =>
                $row->is_new_member
                    ? 'Proposed New Member'
                    : 'Existing Member',

            'member_id' =>
                $member?->id,

            'penerp_member_number' =>
                $member?->member_number
                ??
                $data[
                    'penerp_member_number'
                ]
                ??
                '',

            'penad_member_number' =>
                $member?->penad_member_number
                ??
                $data[
                    'penad_member_number'
                ]
                ??
                $data[
                    'pension_reference_number'
                ]
                ??
                '',

            'staff_number' =>
                $data[
                    'staff_number'
                ]
                ??
                '',

            'national_id' =>
                $data[
                    'national_id'
                ]
                ??
                '',

            'member_name' =>
                $memberName,

            'basic_pay' =>
                $system[
                    'basic_pay'
                ],

            'employee_rate_uploaded' =>
                $uploadedEmployeeRate,

            'employee_rate_expected' =>
                $system[
                    'employee_rate'
                ],

            'employee_schedule' =>
                $schedule[
                    'employee_contribution'
                ],

            'employee_system' =>
                $system[
                    'employee_contribution'
                ],

            'employee_variance' =>
                $system[
                    'employee_contribution'
                ]
                -
                $schedule[
                    'employee_contribution'
                ],

            'employer_rate_uploaded' =>
                $uploadedEmployerRate,

            'employer_rate_expected' =>
                17.30,

            'employer_schedule' =>
                $schedule[
                    'employer_contribution'
                ],

            'employer_system' =>
                $system[
                    'employer_contribution'
                ],

            'employer_variance' =>
                $system[
                    'employer_contribution'
                ]
                -
                $schedule[
                    'employer_contribution'
                ],

            'employee_avc' =>
                $schedule[
                    'employee_avc'
                ],

            'employer_avc' =>
                $schedule[
                    'employer_avc'
                ],

            'schedule_total' =>
                $schedule[
                    'total'
                ],

            'system_total' =>
                $system[
                    'total'
                ],

            'variance' =>
                $system[
                    'total'
                ]
                -
                $schedule[
                    'total'
                ],

            'warnings' =>
                is_array(
                    $row->warning_messages
                )
                    ? $row->warning_messages
                    : [],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Warning Prefix
    |--------------------------------------------------------------------------
    */

    private function rowHasWarningPrefix(
        ContributionImportRow $row,
        string $prefix
    ): bool {
        $warnings =
            $row->warning_messages
            ??
            [];


        if (
            !is_array(
                $warnings
            )
        ) {
            return false;
        }


        foreach (
            $warnings
            as $warning
        ) {
            if (
                str_contains(
                    strtoupper(
                        (string)
                        $warning
                    ),
                    strtoupper(
                        $prefix
                    )
                )
            ) {
                return true;
            }
        }


        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Rate
    |--------------------------------------------------------------------------
    */

    private function normaliseRate(
        mixed $value
    ): float {
        $rate =
            (float) (
                $value
                ??
                0
            );


        if (
            $rate > 0
            &&
            $rate <= 1
        ) {
            return round(
                $rate
                *
                100,
                6
            );
        }


        return $rate;
    }
}
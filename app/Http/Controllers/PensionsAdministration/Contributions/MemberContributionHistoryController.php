<?php

namespace App\Http\Controllers\PensionsAdministration\Contributions;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Updates\Member;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MemberContributionHistoryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Member Expected Contribution History
    |--------------------------------------------------------------------------
    */

    public function show(
        Member $member
    ): View {
        $this->ensurePermission(
            'contributions.monthly-imports.view'
        );

        /*
        |--------------------------------------------------------------------------
        | Member
        |--------------------------------------------------------------------------
        */

        $member->load([
            'currentEmployment.employer',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Expected Contributions
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Historical contribution migration stores its actual values in:
        |
        | basic_pay
        | employee_rate
        | employer_rate
        | employee_contribution
        | employer_contribution
        | employee_avc
        | employer_avc
        |
        | Normal monthly imports continue to use the ZWG/USD fields.
        |
        */

        $contributions =
            DB::table(
                'member_contributions'
            )
                ->leftJoin(
                    'employers',
                    'employers.id',
                    '=',
                    'member_contributions.employer_id'
                )
                ->where(
                    'member_contributions.member_id',
                    $member->id
                )
                ->where(
                    'member_contributions.transaction_type',
                    'expected'
                )
                ->select([
                    'member_contributions.*',

                    'employers.name as employer_name',
                    'employers.employer_number',
                    'employers.penad_employer_number',
                    'employers.fundworx_employer_number',

                    /*
                    |--------------------------------------------------------------------------
                    | Display Basic Pay
                    |--------------------------------------------------------------------------
                    */

                    DB::raw("
                        CASE
                            WHEN member_contributions.source_system = 'historical_migration'
                                THEN COALESCE(member_contributions.basic_pay, 0)
                            ELSE COALESCE(member_contributions.zwg_basic_pay, 0)
                        END AS display_basic_pay
                    "),

                    /*
                    |--------------------------------------------------------------------------
                    | Display Employee Rate
                    |--------------------------------------------------------------------------
                    */

                    DB::raw("
                        CASE
                            WHEN member_contributions.source_system = 'historical_migration'
                                THEN COALESCE(member_contributions.employee_rate, 0)
                            ELSE COALESCE(member_contributions.zwg_employee_rate, 0)
                        END AS display_employee_rate
                    "),

                    /*
                    |--------------------------------------------------------------------------
                    | Display Employer Rate
                    |--------------------------------------------------------------------------
                    */

                    DB::raw("
                        CASE
                            WHEN member_contributions.source_system = 'historical_migration'
                                THEN COALESCE(member_contributions.employer_rate, 0)
                            ELSE COALESCE(member_contributions.zwg_employer_rate, 0)
                        END AS display_employer_rate
                    "),

                    /*
                    |--------------------------------------------------------------------------
                    | Display Employee Contribution
                    |--------------------------------------------------------------------------
                    */

                    DB::raw("
                        CASE
                            WHEN member_contributions.source_system = 'historical_migration'
                                THEN COALESCE(member_contributions.employee_contribution, 0)
                            ELSE COALESCE(member_contributions.zwg_employee_contribution, 0)
                        END AS display_employee_contribution
                    "),

                    /*
                    |--------------------------------------------------------------------------
                    | Display Employer Contribution
                    |--------------------------------------------------------------------------
                    */

                    DB::raw("
                        CASE
                            WHEN member_contributions.source_system = 'historical_migration'
                                THEN COALESCE(member_contributions.employer_contribution, 0)
                            ELSE COALESCE(member_contributions.zwg_employer_contribution, 0)
                        END AS display_employer_contribution
                    "),

                    /*
                    |--------------------------------------------------------------------------
                    | Display Employee AVC
                    |--------------------------------------------------------------------------
                    */

                    DB::raw("
                        CASE
                            WHEN member_contributions.source_system = 'historical_migration'
                                THEN COALESCE(member_contributions.employee_avc, 0)
                            ELSE COALESCE(member_contributions.zwg_employee_avc, 0)
                        END AS display_employee_avc
                    "),

                    /*
                    |--------------------------------------------------------------------------
                    | Display Employer AVC
                    |--------------------------------------------------------------------------
                    */

                    DB::raw("
                        CASE
                            WHEN member_contributions.source_system = 'historical_migration'
                                THEN COALESCE(member_contributions.employer_avc, 0)
                            ELSE COALESCE(member_contributions.zwg_employer_avc, 0)
                        END AS display_employer_avc
                    "),
                ])
                ->orderBy(
                    'member_contributions.period_year'
                )
                ->orderBy(
                    'member_contributions.period_month'
                )
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Employment History
        |--------------------------------------------------------------------------
        */

        $employments =
            DB::table(
                'member_employments'
            )
                ->leftJoin(
                    'employers',
                    'employers.id',
                    '=',
                    'member_employments.employer_id'
                )
                ->where(
                    'member_employments.member_id',
                    $member->id
                )
                ->select([
                    'member_employments.*',
                    'employers.name as employer_name',
                    'employers.employer_number',
                    'employers.penad_employer_number',
                    'employers.fundworx_employer_number',
                ])
                ->orderBy(
                    'member_employments.effective_from'
                )
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Period Member Status
        |--------------------------------------------------------------------------
        */

        $periodStatuses =
            DB::table(
                'contribution_period_member_statuses'
            )
                ->join(
                    'contribution_periods',
                    'contribution_periods.id',
                    '=',
                    'contribution_period_member_statuses.contribution_period_id'
                )
                ->where(
                    'contribution_period_member_statuses.member_id',
                    $member->id
                )
                ->select([
                    'contribution_period_member_statuses.contribution_status',
                    'contribution_period_member_statuses.reason',
                    'contribution_period_member_statuses.employer_id',
                    'contribution_periods.period_year',
                    'contribution_periods.period_month',
                    'contribution_periods.period_date',
                ])
                ->get()
                ->keyBy(
                    fn ($status) =>
                        sprintf(
                            '%04d-%02d',
                            $status->period_year,
                            $status->period_month
                        )
                );

        /*
        |--------------------------------------------------------------------------
        | Historical Break In Service Periods
        |--------------------------------------------------------------------------
        */

        $historicalServicePeriods =
            DB::table(
                'historical_member_service_periods'
            )
                ->where(
                    'member_id',
                    $member->id
                )
                ->where(
                    'service_status',
                    'break_in_service'
                )
                ->get()
                ->keyBy(
                    fn ($status) =>
                        sprintf(
                            '%04d-%02d',
                            $status->period_year,
                            $status->period_month
                        )
                );

        /*
        |--------------------------------------------------------------------------
        | History Range
        |--------------------------------------------------------------------------
        */

        $startDate =
            $this->resolveStartDate(
                $member,
                $contributions,
                $employments
            );

        $endDate =
            $this->resolveEndDate(
                $contributions,
                $employments,
                $historicalServicePeriods
            );

        /*
        |--------------------------------------------------------------------------
        | Build Monthly History
        |--------------------------------------------------------------------------
        */

        $history =
            collect();

        if (
            $startDate
            &&
            $endDate
            &&
            $startDate->lte(
                $endDate
            )
        ) {
            $period =
                CarbonPeriod::create(
                    $startDate
                        ->copy()
                        ->startOfMonth(),

                    '1 month',

                    $endDate
                        ->copy()
                        ->startOfMonth()
                );

            foreach (
                $period
                as $month
            ) {
                $history->push(
                    $this->buildMonth(
                        month:
                            Carbon::instance(
                                $month
                            ),

                        contributions:
                            $contributions,

                        employments:
                            $employments,

                        periodStatuses:
                            $periodStatuses,

                        historicalServicePeriods:
                            $historicalServicePeriods
                    )
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Latest First
        |--------------------------------------------------------------------------
        */

        $history =
            $history
                ->sortByDesc(
                    'period_sort'
                )
                ->values();

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $summary = [
            'total_months' =>
                $history->count(),

            'contributed_months' =>
                $history
                    ->where(
                        'status',
                        'contributed'
                    )
                    ->count(),

            'nil_contributor_months' =>
                $history
                    ->where(
                        'status',
                        'nil_contributor'
                    )
                    ->count(),

            'missing_expected_months' =>
                $history
                    ->where(
                        'status',
                        'missing_expected'
                    )
                    ->count(),

            'break_months' =>
                $history
                    ->where(
                        'status',
                        'break_in_service'
                    )
                    ->count(),

            /*
            |--------------------------------------------------------------------------
            | Display / Generic Historical Totals
            |--------------------------------------------------------------------------
            */

            'zwg_basic_pay_total' =>
                $history->sum(
                    'zwg_basic_pay'
                ),

            'zwg_employee_total' =>
                $history->sum(
                    'zwg_employee_contribution'
                ),

            'zwg_employer_total' =>
                $history->sum(
                    'zwg_employer_contribution'
                ),

            'zwg_employee_avc_total' =>
                $history->sum(
                    'zwg_employee_avc'
                ),

            'zwg_employer_avc_total' =>
                $history->sum(
                    'zwg_employer_avc'
                ),

            /*
            |--------------------------------------------------------------------------
            | USD
            |--------------------------------------------------------------------------
            */

            'usd_basic_pay_total' =>
                $history->sum(
                    'usd_basic_pay'
                ),

            'usd_employee_total' =>
                $history->sum(
                    'usd_employee_contribution'
                ),

            'usd_employer_total' =>
                $history->sum(
                    'usd_employer_contribution'
                ),

            'usd_employee_avc_total' =>
                $history->sum(
                    'usd_employee_avc'
                ),

            'usd_employer_avc_total' =>
                $history->sum(
                    'usd_employer_avc'
                ),
        ];

        /*
        |--------------------------------------------------------------------------
        | Blade
        |--------------------------------------------------------------------------
        |
        | resources/views/pensions-administration/contributions/members/history.blade.php
        |
        */

        return view(
            'pensions-administration.contributions.members.history',
            compact(
                'member',
                'history',
                'summary',
                'employments'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Build One Month
    |--------------------------------------------------------------------------
    */

    private function buildMonth(
        Carbon $month,
        Collection $contributions,
        Collection $employments,
        Collection $periodStatuses,
        Collection $historicalServicePeriods
    ): array {
        $year =
            (int) $month->year;

        $monthNumber =
            (int) $month->month;

        $monthKey =
            sprintf(
                '%04d-%02d',
                $year,
                $monthNumber
            );

        $monthStart =
            $month
                ->copy()
                ->startOfMonth();

        $monthEnd =
            $month
                ->copy()
                ->endOfMonth();

        /*
        |--------------------------------------------------------------------------
        | Contributions For Month
        |--------------------------------------------------------------------------
        */

        $monthContributions =
            $contributions
                ->filter(
                    fn ($contribution) =>
                        (int) $contribution->period_year === $year
                        &&
                        (int) $contribution->period_month === $monthNumber
                );

        /*
        |--------------------------------------------------------------------------
        | Employment Covering Month
        |--------------------------------------------------------------------------
        */

        $employment =
            $employments
                ->first(
                    function (
                        $employment
                    ) use (
                        $monthStart,
                        $monthEnd
                    ) {
                        $effectiveFrom =
                            $employment->effective_from
                                ? Carbon::parse(
                                    $employment->effective_from
                                )
                                : (
                                    $employment->date_joined_employer
                                        ? Carbon::parse(
                                            $employment->date_joined_employer
                                        )
                                        : null
                                );

                        if (!$effectiveFrom) {
                            return false;
                        }

                        $effectiveTo =
                            $employment->effective_to
                                ? Carbon::parse(
                                    $employment->effective_to
                                )
                                : null;

                        return
                            $effectiveFrom->lte(
                                $monthEnd
                            )
                            &&
                            (
                                !$effectiveTo
                                ||
                                $effectiveTo->gte(
                                    $monthStart
                                )
                            );
                    }
                );

        /*
        |--------------------------------------------------------------------------
        | Status Records
        |--------------------------------------------------------------------------
        */

        $periodStatus =
            $periodStatuses->get(
                $monthKey
            );

        $historicalBreak =
            $historicalServicePeriods->get(
                $monthKey
            );

        /*
        |--------------------------------------------------------------------------
        | Determine Whether Month Actually Has Contribution Activity
        |--------------------------------------------------------------------------
        |
        | An explicit zero historical contribution is still a contribution
        | record. Therefore if the row exists, the month is contributed /
        | recorded, not missing.
        |
        */

        if (
            $monthContributions->isNotEmpty()
        ) {
            $status =
                'contributed';

            $statusLabel =
                'Contributed';

            $statusReason =
                'Expected contribution record exists for this month.';

        } elseif (
            $historicalBreak
        ) {
            $status =
                'break_in_service';

            $statusLabel =
                'Break in Service';

            $statusReason =
                $historicalBreak->reason
                ??
                'Historical contribution data records this month as a break in service.';

        } elseif (
            !$employment
        ) {
            $status =
                'break_in_service';

            $statusLabel =
                'Break in Service';

            $statusReason =
                'No active employment relationship covers this month.';

        } elseif (
            $periodStatus
            &&
            $periodStatus->contribution_status === 'nil_contributor'
        ) {
            $status =
                'nil_contributor';

            $statusLabel =
                'Nil Contributor';

            $statusReason =
                $periodStatus->reason
                ??
                'Member was active but did not appear on the expected contribution schedule.';

        } else {
            $status =
                'missing_expected';

            $statusLabel =
                'No Expected Contribution';

            $statusReason =
                'Member was in service but no expected contribution record was found for this month.';
        }

        /*
        |--------------------------------------------------------------------------
        | Employer
        |--------------------------------------------------------------------------
        */

        $employerName =
            $monthContributions
                ->first()
                ?->employer_name
            ??
            $employment
                ?->employer_name
            ??
            '-';

        $employerNumber =
            $monthContributions
                ->first()
                ?->employer_number
            ??
            $employment
                ?->employer_number
            ??
            null;

        /*
        |--------------------------------------------------------------------------
        | Display Values
        |--------------------------------------------------------------------------
        |
        | display_* was prepared in SQL using:
        |
        | historical_migration -> generic historical columns
        | everything else      -> ZWG columns
        |
        */

        $displayBasicPay =
            $monthContributions->sum(
                fn ($row) =>
                    (float) (
                        $row->display_basic_pay
                        ??
                        0
                    )
            );

        $displayEmployeeContribution =
            $monthContributions->sum(
                fn ($row) =>
                    (float) (
                        $row->display_employee_contribution
                        ??
                        0
                    )
            );

        $displayEmployerContribution =
            $monthContributions->sum(
                fn ($row) =>
                    (float) (
                        $row->display_employer_contribution
                        ??
                        0
                    )
            );

        $displayEmployeeAvc =
            $monthContributions->sum(
                fn ($row) =>
                    (float) (
                        $row->display_employee_avc
                        ??
                        0
                    )
            );

        $displayEmployerAvc =
            $monthContributions->sum(
                fn ($row) =>
                    (float) (
                        $row->display_employer_avc
                        ??
                        0
                    )
            );

        /*
        |--------------------------------------------------------------------------
        | Display Rates
        |--------------------------------------------------------------------------
        */

        $displayEmployeeRate =
            $monthContributions->max(
                fn ($row) =>
                    (float) (
                        $row->display_employee_rate
                        ??
                        0
                    )
            );

        $displayEmployerRate =
            $monthContributions->max(
                fn ($row) =>
                    (float) (
                        $row->display_employer_rate
                        ??
                        0
                    )
            );

        /*
        |--------------------------------------------------------------------------
        | USD Values
        |--------------------------------------------------------------------------
        */

        $usdEmployeeRate =
            $monthContributions->max(
                fn ($row) =>
                    (float) (
                        $row->usd_employee_rate
                        ??
                        0
                    )
            );

        $usdEmployerRate =
            $monthContributions->max(
                fn ($row) =>
                    (float) (
                        $row->usd_employer_rate
                        ??
                        0
                    )
            );

        /*
        |--------------------------------------------------------------------------
        | Source
        |--------------------------------------------------------------------------
        */

        $sourceSystem =
            $monthContributions
                ->first()
                ?->source_system
            ??
            null;

        $isHistorical =
            $monthContributions
                ->contains(
                    fn ($row) =>
                        $row->source_system
                        ===
                        'historical_migration'
                );

        return [
            'period_sort' =>
                $month->format(
                    'Y-m'
                ),

            'period' =>
                $month->format(
                    'F Y'
                ),

            'period_year' =>
                $year,

            'period_month' =>
                $monthNumber,

            'period_date' =>
                $monthEnd->toDateString(),

            'status' =>
                $status,

            'status_label' =>
                $statusLabel,

            'status_reason' =>
                $statusReason,

            'employer_name' =>
                $employerName,

            'employer_number' =>
                $employerNumber,

            'source_system' =>
                $sourceSystem,

            'is_historical' =>
                $isHistorical,

            /*
            |--------------------------------------------------------------------------
            | Display Values
            |--------------------------------------------------------------------------
            |
            | Keep the existing zwg_* keys so your current Blade does not break.
            |
            | For historical rows these contain the generic historical values.
            |
            */

            'zwg_basic_pay' =>
                $displayBasicPay,

            'zwg_employee_rate' =>
                $displayEmployeeRate,

            'zwg_employer_rate' =>
                $displayEmployerRate,

            'zwg_employee_contribution' =>
                $displayEmployeeContribution,

            'zwg_employer_contribution' =>
                $displayEmployerContribution,

            'zwg_employee_avc' =>
                $displayEmployeeAvc,

            'zwg_employer_avc' =>
                $displayEmployerAvc,

            /*
            |--------------------------------------------------------------------------
            | Also Expose Proper Display Keys
            |--------------------------------------------------------------------------
            */

            'display_basic_pay' =>
                $displayBasicPay,

            'display_employee_rate' =>
                $displayEmployeeRate,

            'display_employer_rate' =>
                $displayEmployerRate,

            'display_employee_contribution' =>
                $displayEmployeeContribution,

            'display_employer_contribution' =>
                $displayEmployerContribution,

            'display_employee_avc' =>
                $displayEmployeeAvc,

            'display_employer_avc' =>
                $displayEmployerAvc,

            /*
            |--------------------------------------------------------------------------
            | USD
            |--------------------------------------------------------------------------
            */

            'usd_basic_pay' =>
                $monthContributions->sum(
                    fn ($row) =>
                        (float) (
                            $row->usd_basic_pay
                            ??
                            0
                        )
                ),

            'usd_employee_rate' =>
                $usdEmployeeRate,

            'usd_employer_rate' =>
                $usdEmployerRate,

            'usd_employee_contribution' =>
                $monthContributions->sum(
                    fn ($row) =>
                        (float) (
                            $row->usd_employee_contribution
                            ??
                            0
                        )
                ),

            'usd_employer_contribution' =>
                $monthContributions->sum(
                    fn ($row) =>
                        (float) (
                            $row->usd_employer_contribution
                            ??
                            0
                        )
                ),

            'usd_employee_avc' =>
                $monthContributions->sum(
                    fn ($row) =>
                        (float) (
                            $row->usd_employee_avc
                            ??
                            0
                        )
                ),

            'usd_employer_avc' =>
                $monthContributions->sum(
                    fn ($row) =>
                        (float) (
                            $row->usd_employer_avc
                            ??
                            0
                        )
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Start Date
    |--------------------------------------------------------------------------
    */

    private function resolveStartDate(
        Member $member,
        Collection $contributions,
        Collection $employments
    ): ?Carbon {
        $dates =
            collect();

        if (
            $member->date_joined_fund
        ) {
            $dates->push(
                Carbon::parse(
                    $member->date_joined_fund
                )
            );
        }

        foreach (
            $contributions
            as $contribution
        ) {
            if (
                $contribution->period_date
            ) {
                $dates->push(
                    Carbon::parse(
                        $contribution->period_date
                    )
                );
            }
        }

        foreach (
            $employments
            as $employment
        ) {
            $employmentStart =
                $employment->effective_from
                ??
                $employment->date_joined_employer
                ??
                null;

            if ($employmentStart) {
                $dates->push(
                    Carbon::parse(
                        $employmentStart
                    )
                );
            }
        }

        if (
            $dates->isEmpty()
        ) {
            return null;
        }

        return $dates
            ->sort()
            ->first()
            ->copy()
            ->startOfMonth();
    }

    /*
    |--------------------------------------------------------------------------
    | End Date
    |--------------------------------------------------------------------------
    */

    private function resolveEndDate(
        Collection $contributions,
        Collection $employments,
        Collection $historicalServicePeriods
    ): ?Carbon {
        $dates =
            collect();

        foreach (
            $contributions
            as $contribution
        ) {
            if (
                $contribution->period_date
            ) {
                $dates->push(
                    Carbon::parse(
                        $contribution->period_date
                    )
                );
            }
        }

        foreach (
            $employments
            as $employment
        ) {
            if (
                $employment->effective_to
            ) {
                $dates->push(
                    Carbon::parse(
                        $employment->effective_to
                    )
                );
            }
        }

        foreach (
            $historicalServicePeriods
            as $servicePeriod
        ) {
            if (
                $servicePeriod->period_date
            ) {
                $dates->push(
                    Carbon::parse(
                        $servicePeriod->period_date
                    )
                );
            }
        }

        if (
            $dates->isEmpty()
        ) {
            return null;
        }

        return $dates
            ->sortDesc()
            ->first()
            ->copy()
            ->endOfMonth();
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
            'You do not have permission to view member contribution history.'
        );
    }
}
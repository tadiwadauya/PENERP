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
        | Permanent Expected Contributions
        |--------------------------------------------------------------------------
        */

        $contributions = DB::table('member_contributions')
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
        |
        | We need all employment records, not only current employment, because
        | this is what allows us to identify genuine service breaks.
        |
        */

        $employments = DB::table('member_employments')
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

        $periodStatuses = DB::table(
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
        | Determine History Range
        |--------------------------------------------------------------------------
        |
        | Start with the earliest relevant employment or expected contribution.
        |
        */

        $startDate = $this->resolveStartDate(
            $member,
            $contributions,
            $employments
        );

        $endDate = $this->resolveEndDate(
            $contributions,
            $employments
        );

        /*
        |--------------------------------------------------------------------------
        | Monthly History
        |--------------------------------------------------------------------------
        */

        $history = collect();

        if (
            $startDate
            &&
            $endDate
            &&
            $startDate->lte($endDate)
        ) {
            $period = CarbonPeriod::create(
                $startDate->copy()->startOfMonth(),
                '1 month',
                $endDate->copy()->startOfMonth()
            );

            foreach ($period as $month) {
                $history->push(
                    $this->buildMonth(
                        month: Carbon::instance($month),
                        contributions: $contributions,
                        employments: $employments,
                        periodStatuses: $periodStatuses
                    )
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Latest Month First
        |--------------------------------------------------------------------------
        */

        $history = $history
            ->sortByDesc('period_sort')
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

            'zwg_employee_total' =>
                $history
                    ->sum(
                        'zwg_employee_contribution'
                    ),

            'zwg_employer_total' =>
                $history
                    ->sum(
                        'zwg_employer_contribution'
                    ),

            'zwg_employee_avc_total' =>
                $history
                    ->sum(
                        'zwg_employee_avc'
                    ),

            'zwg_employer_avc_total' =>
                $history
                    ->sum(
                        'zwg_employer_avc'
                    ),

            'usd_employee_total' =>
                $history
                    ->sum(
                        'usd_employee_contribution'
                    ),

            'usd_employer_total' =>
                $history
                    ->sum(
                        'usd_employer_contribution'
                    ),

            'usd_employee_avc_total' =>
                $history
                    ->sum(
                        'usd_employee_avc'
                    ),

            'usd_employer_avc_total' =>
                $history
                    ->sum(
                        'usd_employer_avc'
                    ),
        ];

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
        Collection $periodStatuses
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
            $month->copy()
                ->startOfMonth();

        $monthEnd =
            $month->copy()
                ->endOfMonth();

        /*
        |--------------------------------------------------------------------------
        | Contributions For Month
        |--------------------------------------------------------------------------
        */

        $monthContributions = $contributions
            ->filter(
                fn ($contribution) =>
                    (int) $contribution->period_year === $year
                    &&
                    (int) $contribution->period_month === $monthNumber
            );

        /*
        |--------------------------------------------------------------------------
        | Employment Covering This Month
        |--------------------------------------------------------------------------
        */

        $employment = $employments
            ->first(
                function ($employment) use (
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

                    return $effectiveFrom->lte(
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
        | Period Status
        |--------------------------------------------------------------------------
        */

        $periodStatus =
            $periodStatuses->get(
                $monthKey
            );

        /*
        |--------------------------------------------------------------------------
        | Determine Status
        |--------------------------------------------------------------------------
        */

        if ($monthContributions->isNotEmpty()) {
            $status =
                'contributed';

            $statusLabel =
                'Contributed';

            $statusReason =
                'Expected contribution posted for this month.';

        } elseif (!$employment) {
            $status =
                'break_in_service';

            $statusLabel =
                'Break in Service';

            $statusReason =
                'No active employment relationship covers this month.';

        } elseif (
            $periodStatus
            &&
            $periodStatus->contribution_status
            ===
            'nil_contributor'
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
        | Rates
        |--------------------------------------------------------------------------
        */

        $zwgEmployeeRate =
            $monthContributions
                ->max(
                    fn ($row) =>
                        (float) (
                            $row->zwg_employee_rate
                            ?? 0
                        )
                );

        $zwgEmployerRate =
            $monthContributions
                ->max(
                    fn ($row) =>
                        (float) (
                            $row->zwg_employer_rate
                            ?? 0
                        )
                );

        $usdEmployeeRate =
            $monthContributions
                ->max(
                    fn ($row) =>
                        (float) (
                            $row->usd_employee_rate
                            ?? 0
                        )
                );

        $usdEmployerRate =
            $monthContributions
                ->max(
                    fn ($row) =>
                        (float) (
                            $row->usd_employer_rate
                            ?? 0
                        )
                );

        return [
            'period_sort' =>
                $month->format('Y-m'),

            'period' =>
                $month->format('F Y'),

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

            /*
            |--------------------------------------------------------------------------
            | ZWG
            |--------------------------------------------------------------------------
            */

            'zwg_basic_pay' =>
                $monthContributions->sum(
                    fn ($row) =>
                        (float) (
                            $row->zwg_basic_pay
                            ?? 0
                        )
                ),

            'zwg_employee_rate' =>
                $zwgEmployeeRate,

            'zwg_employer_rate' =>
                $zwgEmployerRate,

            'zwg_employee_contribution' =>
                $monthContributions->sum(
                    fn ($row) =>
                        (float) (
                            $row->zwg_employee_contribution
                            ?? 0
                        )
                ),

            'zwg_employer_contribution' =>
                $monthContributions->sum(
                    fn ($row) =>
                        (float) (
                            $row->zwg_employer_contribution
                            ?? 0
                        )
                ),

            'zwg_employee_avc' =>
                $monthContributions->sum(
                    fn ($row) =>
                        (float) (
                            $row->zwg_employee_avc
                            ?? 0
                        )
                ),

            'zwg_employer_avc' =>
                $monthContributions->sum(
                    fn ($row) =>
                        (float) (
                            $row->zwg_employer_avc
                            ?? 0
                        )
                ),

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
                            ?? 0
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
                            ?? 0
                        )
                ),

            'usd_employer_contribution' =>
                $monthContributions->sum(
                    fn ($row) =>
                        (float) (
                            $row->usd_employer_contribution
                            ?? 0
                        )
                ),

            'usd_employee_avc' =>
                $monthContributions->sum(
                    fn ($row) =>
                        (float) (
                            $row->usd_employee_avc
                            ?? 0
                        )
                ),

            'usd_employer_avc' =>
                $monthContributions->sum(
                    fn ($row) =>
                        (float) (
                            $row->usd_employer_avc
                            ?? 0
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

        if ($member->date_joined_fund) {
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
            if ($contribution->period_date) {
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

        if ($dates->isEmpty()) {
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
        Collection $employments
    ): ?Carbon {
        $dates =
            collect();

        foreach (
            $contributions
            as $contribution
        ) {
            if ($contribution->period_date) {
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
            if ($employment->effective_to) {
                $dates->push(
                    Carbon::parse(
                        $employment->effective_to
                    )
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Current Employment
        |--------------------------------------------------------------------------
        |
        | If the member is still currently employed, history can run up to the
        | latest available contribution month. We do not automatically generate
        | future months.
        |
        */

        if ($dates->isEmpty()) {
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
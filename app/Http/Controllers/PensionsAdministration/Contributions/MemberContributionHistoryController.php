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
    public function show(Member $member): View
    {
        $this->ensurePermission('contributions.monthly-imports.view');

        $member->load([
            'currentEmployment.employer',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Contributions
        |--------------------------------------------------------------------------
        |
        | Expected = normal monthly contribution.
        | Take-On  = opening balance brought forward into January 2009.
        |
        | Take-On must never be treated as a December 2008 contribution and must
        | never be added into January 2009's normal monthly contribution.
        |
        */
        $contributions = DB::table('member_contributions')
            ->leftJoin('employers', 'employers.id', '=', 'member_contributions.employer_id')
            ->where('member_contributions.member_id', $member->id)
            ->whereIn('member_contributions.transaction_type', [
                'expected',
                'take_on',
            ])
            ->select([
                'member_contributions.*',
                'employers.name as employer_name',
                'employers.employer_number',
                'employers.penad_employer_number',
                'employers.fundworx_employer_number',

                DB::raw("
                    CASE
                        WHEN member_contributions.source_system = 'historical_migration'
                            THEN COALESCE(member_contributions.basic_pay, 0)
                        ELSE COALESCE(member_contributions.zwg_basic_pay, 0)
                    END AS display_basic_pay
                "),

                DB::raw("
                    CASE
                        WHEN member_contributions.source_system = 'historical_migration'
                            THEN COALESCE(member_contributions.employee_rate, 0)
                        ELSE COALESCE(member_contributions.zwg_employee_rate, 0)
                    END AS display_employee_rate
                "),

                DB::raw("
                    CASE
                        WHEN member_contributions.source_system = 'historical_migration'
                            THEN COALESCE(member_contributions.employer_rate, 0)
                        ELSE COALESCE(member_contributions.zwg_employer_rate, 0)
                    END AS display_employer_rate
                "),

                DB::raw("
                    CASE
                        WHEN member_contributions.source_system = 'historical_migration'
                            THEN COALESCE(member_contributions.employee_contribution, 0)
                        ELSE COALESCE(member_contributions.zwg_employee_contribution, 0)
                    END AS display_employee_contribution
                "),

                DB::raw("
                    CASE
                        WHEN member_contributions.source_system = 'historical_migration'
                            THEN COALESCE(member_contributions.employer_contribution, 0)
                        ELSE COALESCE(member_contributions.zwg_employer_contribution, 0)
                    END AS display_employer_contribution
                "),

                DB::raw("
                    CASE
                        WHEN member_contributions.source_system = 'historical_migration'
                            THEN COALESCE(member_contributions.employee_avc, 0)
                        ELSE COALESCE(member_contributions.zwg_employee_avc, 0)
                    END AS display_employee_avc
                "),

                DB::raw("
                    CASE
                        WHEN member_contributions.source_system = 'historical_migration'
                            THEN COALESCE(member_contributions.employer_avc, 0)
                        ELSE COALESCE(member_contributions.zwg_employer_avc, 0)
                    END AS display_employer_avc
                "),
            ])
            ->orderBy('member_contributions.period_year')
            ->orderBy('member_contributions.period_month')
            ->orderBy('member_contributions.transaction_type')
            ->get();

        $employments = DB::table('member_employments')
            ->leftJoin('employers', 'employers.id', '=', 'member_employments.employer_id')
            ->where('member_employments.member_id', $member->id)
            ->select([
                'member_employments.*',
                'employers.name as employer_name',
                'employers.employer_number',
                'employers.penad_employer_number',
                'employers.fundworx_employer_number',
            ])
            ->orderBy('member_employments.effective_from')
            ->get();

        $periodStatuses = DB::table('contribution_period_member_statuses')
            ->join(
                'contribution_periods',
                'contribution_periods.id',
                '=',
                'contribution_period_member_statuses.contribution_period_id'
            )
            ->where('contribution_period_member_statuses.member_id', $member->id)
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
                fn ($status) => sprintf(
                    '%04d-%02d',
                    $status->period_year,
                    $status->period_month
                )
            );

        $historicalServicePeriods = DB::table('historical_member_service_periods')
            ->where('member_id', $member->id)
            ->where('service_status', 'break_in_service')
            ->get()
            ->keyBy(
                fn ($status) => sprintf(
                    '%04d-%02d',
                    $status->period_year,
                    $status->period_month
                )
            );

        /*
        |--------------------------------------------------------------------------
        | History Range
        |--------------------------------------------------------------------------
        |
        | PENERP historical contribution history starts in January 2009.
        | Employment/joining dates before January 2009 must NOT manufacture
        | monthly "No Expected Contribution" rows for 2008 and earlier.
        |
        */
        $startDate = $this->resolveStartDate(
            $member,
            $contributions,
            $employments
        );

        $historicalFloor = Carbon::create(2009, 1, 1)->startOfMonth();

        if ($startDate && $startDate->lt($historicalFloor)) {
            $startDate = $historicalFloor->copy();
        }

        $endDate = $this->resolveEndDate(
            $contributions,
            $employments,
            $historicalServicePeriods
        );

        $history = collect();

        if ($startDate && $endDate && $startDate->lte($endDate)) {
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
                        periodStatuses: $periodStatuses,
                        historicalServicePeriods: $historicalServicePeriods
                    )
                );
            }
        }

        $history = $history
            ->sortByDesc('period_sort')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        |
        | Monthly totals deliberately exclude Take-On. Take-On is reported
        | separately as the opening balance.
        |
        */
        $summary = [
            'total_months' => $history->count(),

            'contributed_months' => $history
                ->where('status', 'contributed')
                ->count(),

            'take_on_months' => $history
                ->filter(fn ($row) => (bool) ($row['has_take_on'] ?? false))
                ->count(),

            'nil_contributor_months' => $history
                ->where('status', 'nil_contributor')
                ->count(),

            'missing_expected_months' => $history
                ->where('status', 'missing_expected')
                ->count(),

            'break_months' => $history
                ->where('status', 'break_in_service')
                ->count(),

            /*
            |--------------------------------------------------------------------------
            | Monthly / Expected Totals
            |--------------------------------------------------------------------------
            */
            'zwg_basic_pay_total' => $history->sum('zwg_basic_pay'),
            'zwg_employee_total' => $history->sum('zwg_employee_contribution'),
            'zwg_employer_total' => $history->sum('zwg_employer_contribution'),
            'zwg_employee_avc_total' => $history->sum('zwg_employee_avc'),
            'zwg_employer_avc_total' => $history->sum('zwg_employer_avc'),

            /*
            |--------------------------------------------------------------------------
            | Take-On / Opening Balance Totals
            |--------------------------------------------------------------------------
            */
            'take_on_employee_total' => $history->sum('take_on_employee_contribution'),
            'take_on_employer_total' => $history->sum('take_on_employer_contribution'),
            'take_on_employee_avc_total' => $history->sum('take_on_employee_avc'),
            'take_on_employer_avc_total' => $history->sum('take_on_employer_avc'),

            /*
            |--------------------------------------------------------------------------
            | USD Monthly Totals
            |--------------------------------------------------------------------------
            */
            'usd_basic_pay_total' => $history->sum('usd_basic_pay'),
            'usd_employee_total' => $history->sum('usd_employee_contribution'),
            'usd_employer_total' => $history->sum('usd_employer_contribution'),
            'usd_employee_avc_total' => $history->sum('usd_employee_avc'),
            'usd_employer_avc_total' => $history->sum('usd_employer_avc'),
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

    private function buildMonth(
        Carbon $month,
        Collection $contributions,
        Collection $employments,
        Collection $periodStatuses,
        Collection $historicalServicePeriods
    ): array {
        $year = (int) $month->year;
        $monthNumber = (int) $month->month;
        $monthKey = sprintf('%04d-%02d', $year, $monthNumber);

        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        $monthContributions = $contributions
            ->filter(
                fn ($contribution) =>
                    (int) $contribution->period_year === $year
                    &&
                    (int) $contribution->period_month === $monthNumber
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Keep Take-On Separate From The Monthly Expected Contribution
        |--------------------------------------------------------------------------
        */
        $expectedContributions = $monthContributions
            ->filter(
                fn ($contribution) =>
                    strtolower(trim((string) $contribution->transaction_type))
                    === 'expected'
            )
            ->values();

        $takeOnContributions = $monthContributions
            ->filter(
                fn ($contribution) =>
                    strtolower(trim((string) $contribution->transaction_type))
                    === 'take_on'
            )
            ->values();

        $hasExpectedContribution = $expectedContributions->isNotEmpty();
        $hasTakeOn = $takeOnContributions->isNotEmpty();

        $employment = $employments
            ->first(
                function ($employment) use ($monthStart, $monthEnd) {
                    $effectiveFrom = $employment->effective_from
                        ? Carbon::parse($employment->effective_from)
                        : (
                            $employment->date_joined_employer
                                ? Carbon::parse($employment->date_joined_employer)
                                : null
                        );

                    if (!$effectiveFrom) {
                        return false;
                    }

                    $effectiveTo = $employment->effective_to
                        ? Carbon::parse($employment->effective_to)
                        : null;

                    return
                        $effectiveFrom->lte($monthEnd)
                        &&
                        (
                            !$effectiveTo
                            ||
                            $effectiveTo->gte($monthStart)
                        );
                }
            );

        $periodStatus = $periodStatuses->get($monthKey);
        $historicalBreak = $historicalServicePeriods->get($monthKey);

        /*
        |--------------------------------------------------------------------------
        | Monthly Status
        |--------------------------------------------------------------------------
        |
        | Take-On by itself does NOT make January 2009 a contributed month.
        | It is an opening balance, not a normal monthly expected contribution.
        |
        */
        if ($hasExpectedContribution) {
            $status = 'contributed';
            $statusLabel = 'Contributed';

            $statusReason = $hasTakeOn
                ? 'Expected contribution record exists for this month. A separate historical Take-On opening balance also exists.'
                : 'Expected contribution record exists for this month.';

        } elseif ($hasTakeOn) {
            $status = 'take_on_only';
            $statusLabel = 'Opening Balance';
            $statusReason = 'Historical Take-On opening balance exists for January 2009, but no normal expected contribution record exists for this month.';

        } elseif ($historicalBreak) {
            $status = 'break_in_service';
            $statusLabel = 'Break in Service';
            $statusReason = $historicalBreak->reason
                ?? 'Historical contribution data records this month as a break in service.';

        } elseif (!$employment) {
            $status = 'break_in_service';
            $statusLabel = 'Break in Service';
            $statusReason = 'No active employment relationship covers this month.';

        } elseif (
            $periodStatus
            &&
            $periodStatus->contribution_status === 'nil_contributor'
        ) {
            $status = 'nil_contributor';
            $statusLabel = 'Nil Contributor';
            $statusReason = $periodStatus->reason
                ?? 'Member was active but did not appear on the expected contribution schedule.';

        } else {
            $status = 'missing_expected';
            $statusLabel = 'No Expected Contribution';
            $statusReason = 'Member was in service but no expected contribution record was found for this month.';
        }

        /*
        |--------------------------------------------------------------------------
        | Employer
        |--------------------------------------------------------------------------
        */
        $primaryContribution = $expectedContributions->first()
            ?? $takeOnContributions->first();

        $employerName = $primaryContribution?->employer_name
            ?? $employment?->employer_name
            ?? '-';

        $employerNumber = $primaryContribution?->employer_number
            ?? $employment?->employer_number
            ?? null;

        /*
        |--------------------------------------------------------------------------
        | Expected / Monthly Display Values
        |--------------------------------------------------------------------------
        */
        $displayBasicPay = $expectedContributions->sum(
            fn ($row) => (float) ($row->display_basic_pay ?? 0)
        );

        $displayEmployeeContribution = $expectedContributions->sum(
            fn ($row) => (float) ($row->display_employee_contribution ?? 0)
        );

        $displayEmployerContribution = $expectedContributions->sum(
            fn ($row) => (float) ($row->display_employer_contribution ?? 0)
        );

        $displayEmployeeAvc = $expectedContributions->sum(
            fn ($row) => (float) ($row->display_employee_avc ?? 0)
        );

        $displayEmployerAvc = $expectedContributions->sum(
            fn ($row) => (float) ($row->display_employer_avc ?? 0)
        );

        $displayEmployeeRate = $expectedContributions->max(
            fn ($row) => (float) ($row->display_employee_rate ?? 0)
        ) ?? 0;

        $displayEmployerRate = $expectedContributions->max(
            fn ($row) => (float) ($row->display_employer_rate ?? 0)
        ) ?? 0;

        /*
        |--------------------------------------------------------------------------
        | Take-On / Opening Balance Values
        |--------------------------------------------------------------------------
        */
        $takeOnEmployeeContribution = $takeOnContributions->sum(
            fn ($row) => (float) ($row->display_employee_contribution ?? 0)
        );

        $takeOnEmployerContribution = $takeOnContributions->sum(
            fn ($row) => (float) ($row->display_employer_contribution ?? 0)
        );

        $takeOnEmployeeAvc = $takeOnContributions->sum(
            fn ($row) => (float) ($row->display_employee_avc ?? 0)
        );

        $takeOnEmployerAvc = $takeOnContributions->sum(
            fn ($row) => (float) ($row->display_employer_avc ?? 0)
        );

        /*
        |--------------------------------------------------------------------------
        | USD - Expected Monthly Contribution Only
        |--------------------------------------------------------------------------
        */
        $usdEmployeeRate = $expectedContributions->max(
            fn ($row) => (float) ($row->usd_employee_rate ?? 0)
        ) ?? 0;

        $usdEmployerRate = $expectedContributions->max(
            fn ($row) => (float) ($row->usd_employer_rate ?? 0)
        ) ?? 0;

        $sourceSystem = $primaryContribution?->source_system ?? null;

        $isHistorical = $monthContributions
            ->contains(
                fn ($row) =>
                    $row->source_system
                    === 'historical_migration'
            );

        return [
            'period_sort' => $month->format('Y-m'),
            'period' => $month->format('F Y'),
            'period_year' => $year,
            'period_month' => $monthNumber,
            'period_date' => $monthEnd->toDateString(),

            'status' => $status,
            'status_label' => $statusLabel,
            'status_reason' => $statusReason,

            'employer_name' => $employerName,
            'employer_number' => $employerNumber,

            'source_system' => $sourceSystem,
            'is_historical' => $isHistorical,

            'has_expected_contribution' => $hasExpectedContribution,
            'has_take_on' => $hasTakeOn,

            /*
            |--------------------------------------------------------------------------
            | Expected / Monthly
            |--------------------------------------------------------------------------
            */
            'zwg_basic_pay' => $displayBasicPay,
            'zwg_employee_rate' => $displayEmployeeRate,
            'zwg_employer_rate' => $displayEmployerRate,
            'zwg_employee_contribution' => $displayEmployeeContribution,
            'zwg_employer_contribution' => $displayEmployerContribution,
            'zwg_employee_avc' => $displayEmployeeAvc,
            'zwg_employer_avc' => $displayEmployerAvc,

            'display_basic_pay' => $displayBasicPay,
            'display_employee_rate' => $displayEmployeeRate,
            'display_employer_rate' => $displayEmployerRate,
            'display_employee_contribution' => $displayEmployeeContribution,
            'display_employer_contribution' => $displayEmployerContribution,
            'display_employee_avc' => $displayEmployeeAvc,
            'display_employer_avc' => $displayEmployerAvc,

            /*
            |--------------------------------------------------------------------------
            | Take-On
            |--------------------------------------------------------------------------
            */
            'take_on_employee_contribution' => $takeOnEmployeeContribution,
            'take_on_employer_contribution' => $takeOnEmployerContribution,
            'take_on_employee_avc' => $takeOnEmployeeAvc,
            'take_on_employer_avc' => $takeOnEmployerAvc,

            /*
            |--------------------------------------------------------------------------
            | USD
            |--------------------------------------------------------------------------
            */
            'usd_basic_pay' => $expectedContributions->sum(
                fn ($row) => (float) ($row->usd_basic_pay ?? 0)
            ),

            'usd_employee_rate' => $usdEmployeeRate,
            'usd_employer_rate' => $usdEmployerRate,

            'usd_employee_contribution' => $expectedContributions->sum(
                fn ($row) => (float) ($row->usd_employee_contribution ?? 0)
            ),

            'usd_employer_contribution' => $expectedContributions->sum(
                fn ($row) => (float) ($row->usd_employer_contribution ?? 0)
            ),

            'usd_employee_avc' => $expectedContributions->sum(
                fn ($row) => (float) ($row->usd_employee_avc ?? 0)
            ),

            'usd_employer_avc' => $expectedContributions->sum(
                fn ($row) => (float) ($row->usd_employer_avc ?? 0)
            ),
        ];
    }

    private function resolveStartDate(
        Member $member,
        Collection $contributions,
        Collection $employments
    ): ?Carbon {
        $dates = collect();

        if ($member->date_joined_fund) {
            $dates->push(
                Carbon::parse($member->date_joined_fund)
            );
        }

        foreach ($contributions as $contribution) {
            if ($contribution->period_date) {
                $dates->push(
                    Carbon::parse($contribution->period_date)
                );
            }
        }

        foreach ($employments as $employment) {
            $employmentStart = $employment->effective_from
                ?? $employment->date_joined_employer
                ?? null;

            if ($employmentStart) {
                $dates->push(
                    Carbon::parse($employmentStart)
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

    private function resolveEndDate(
        Collection $contributions,
        Collection $employments,
        Collection $historicalServicePeriods
    ): ?Carbon {
        $dates = collect();

        foreach ($contributions as $contribution) {
            if ($contribution->period_date) {
                $dates->push(
                    Carbon::parse($contribution->period_date)
                );
            }
        }

        foreach ($employments as $employment) {
            if ($employment->effective_to) {
                $dates->push(
                    Carbon::parse($employment->effective_to)
                );
            }
        }

        foreach ($historicalServicePeriods as $servicePeriod) {
            if ($servicePeriod->period_date) {
                $dates->push(
                    Carbon::parse($servicePeriod->period_date)
                );
            }
        }

        if ($dates->isEmpty()) {
            return null;
        }

        return $dates
            ->sortDesc()
            ->first()
            ->copy()
            ->endOfMonth();
    }

    private function ensurePermission(string $permission): void
    {
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
            $user->can($permission),
            403,
            'You do not have permission to view member contribution history.'
        );
    }
}

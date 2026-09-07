<?php

namespace App\Services\PensionsAdministration\BenefitStatements;

use App\Models\PensionsAdministration\Updates\Member;
use App\Services\PensionsAdministration\Settings\BenefitSettingResolver;
use Carbon\Carbon;

class BenefitStatementDeathService
{
    public function __construct(private readonly BenefitSettingResolver $settings) {}

    public function calculate(
        Member $member,
        Carbon $statementDate,
        float $totalDeathBenefitContributions,
        float $annualEmoluments
    ): array {
        $statementDate=$statementDate->copy()->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Death Benefit Pensionable Service
        |--------------------------------------------------------------------------
        |
        | Death-benefit interest uses pensionable service months inclusively.
        |
        | Example:
        | January 2009 to December 2025 = 204 contribution/service months.
        |
        | This is intentionally separate from the Benefit Statement's displayed
        | completed-service value.
        |
        */
        $pensionableServiceMonths=$this->inclusivePensionableServiceMonths(
            $member,
            $statementDate
        );

        $serviceDivisor=(float)$this->settings->setting(
            'death.member_contribution_interest_service_divisor',
            $statementDate
        );

        /*
        |--------------------------------------------------------------------------
        | Interest on Death Benefit Contributions
        |--------------------------------------------------------------------------
        |
        | LAPF Rule:
        |
        | Pensionable Service to Date
        | --------------------------- × Total Death Benefit Contributions
        |             400
        |
        */
        $interestOnMemberContributions=$serviceDivisor>0
            ? ($pensionableServiceMonths/$serviceDivisor)
                *$totalDeathBenefitContributions
            : 0.0;

        /*
        |--------------------------------------------------------------------------
        | Contributions Including Interest
        |--------------------------------------------------------------------------
        */

        $contributionsWithInterest=
            $totalDeathBenefitContributions
            +$interestOnMemberContributions;

        /*
        |--------------------------------------------------------------------------
        | Death Lump Sum
        |--------------------------------------------------------------------------
        |
        | Twice the member's Death Benefit Contributions including interest.
        |
        */
        $lumpSumPayment=
            2*$contributionsWithInterest;

        /*
        |--------------------------------------------------------------------------
        | Spouse Pension
        |--------------------------------------------------------------------------
        |
        | Keep this separate from the death lump-sum calculation.
        |
        | Existing Benefit Statement behaviour is retained here until the full
        | spouse/dependant eligibility rules are implemented.
        |
        */
        $spousePension=
            strtolower(trim((string)$member->marital_status))==='married'
                ? $annualEmoluments*0.25
                : 0.0;

        return [
            'pensionable_service_months'=>$pensionableServiceMonths,

            'total_death_benefit_contributions'=>round(
                $totalDeathBenefitContributions,
                4
            ),

            'interest_service_divisor'=>$serviceDivisor,

            'interest_on_member_contributions'=>round(
                $interestOnMemberContributions,
                4
            ),

            'employee_interest'=>round(
                $interestOnMemberContributions,
                4
            ),

            'death_benefit_contributions_with_interest'=>round(
                $contributionsWithInterest,
                4
            ),

            'employee_contributions_with_interest'=>round(
                $contributionsWithInterest,
                4
            ),

            'lump_sum_payment'=>round(
                $lumpSumPayment,
                2
            ),

            'spouse_pension'=>round(
                $spousePension,
                2
            ),

            'children_pension'=>0.0,
        ];
    }

    private function inclusivePensionableServiceMonths(
        Member $member,
        Carbon $statementDate
    ): int {
        if (!$member->date_joined_fund) {
            return 0;
        }

        $joined=Carbon::parse($member->date_joined_fund)
            ->startOfMonth();

        $end=$member->exit_date
            && Carbon::parse($member->exit_date)->lte($statementDate)
                ? Carbon::parse($member->exit_date)->startOfMonth()
                : $statementDate->copy()->startOfMonth();

        if ($end->lt($joined)) {
            return 0;
        }

        return (($end->year-$joined->year)*12)
            +($end->month-$joined->month)
            +1;
    }
}
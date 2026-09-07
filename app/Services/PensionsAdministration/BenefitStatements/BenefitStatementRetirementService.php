<?php

namespace App\Services\PensionsAdministration\BenefitStatements;

use App\Models\PensionsAdministration\Updates\Member;
use App\Services\PensionsAdministration\Settings\BenefitSettingResolver;
use Carbon\Carbon;
use RuntimeException;

class BenefitStatementRetirementService
{
    public function __construct(private readonly BenefitSettingResolver $settings) {}

    public function calculate(Member $member,Carbon $statementDate,float $pensionableEmoluments,int $serviceMonths): array
    {
        if (!$member->date_of_birth) {
            throw new RuntimeException('Member date of birth is required.');
        }

        if (!$member->date_joined_fund) {
            throw new RuntimeException('Member date joined fund is required.');
        }

        $statementDate=Carbon::parse($statementDate)->endOfDay();

        $minimumAge=(int)$this->settings->setting('retirement.minimum_age',$statementDate);
        $normalAge=(int)$this->settings->setting('retirement.normal_age',$statementDate);
        $maximumAge=(int)$this->settings->setting('retirement.maximum_age',$statementDate);
        $divisor=(int)$this->settings->setting('retirement.pension_service_divisor_months',$statementDate);
        $accrualRate=(float)$this->settings->setting('retirement.accrual_rate',$statementDate);
        $commutationFraction=(float)$this->settings->setting('commutation.fraction',$statementDate);
        $earlyRetirementReductionPerYear=(float)$this->settings->setting('retirement.early_retirement_reduction_per_year',$statementDate);

        $dob=Carbon::parse($member->date_of_birth)->startOfDay();
        $normalRetirementDate=$dob->copy()->addYears($normalAge)->endOfMonth()->startOfDay();

        $ages=[];

        foreach ([$minimumAge,$normalAge,$maximumAge] as $age) {
            $ages[$age]=$this->atAge(
                $member,
                $statementDate,
                $age,
                $normalAge,
                $pensionableEmoluments,
                $serviceMonths,
                $divisor,
                $commutationFraction,
                $earlyRetirementReductionPerYear
            );
        }

        $projectedServiceMonths=$ages[$normalAge]['projected_service_months'];

        $projectedReplacementRatio=round(
            ($projectedServiceMonths/12)*$accrualRate*100,
            2
        );

        return [
            'minimum_age'=>$minimumAge,
            'normal_age'=>$normalAge,
            'maximum_age'=>$maximumAge,
            'normal_retirement_date'=>$normalRetirementDate,
            'projected_replacement_ratio'=>$projectedReplacementRatio,
            'pensionable_emoluments'=>round($pensionableEmoluments,2),
            'ages'=>$ages,
        ];
    }

    private function atAge(
        Member $member,
        Carbon $statementDate,
        int $retirementAge,
        int $normalAge,
        float $pensionableEmoluments,
        int $currentServiceMonths,
        int $divisor,
        float $commutationFraction,
        float $earlyRetirementReductionPerYear
    ): array {
        $dob=Carbon::parse($member->date_of_birth)->startOfDay();

        $retirementDate=$dob
            ->copy()
            ->addYears($retirementAge)
            ->endOfMonth()
            ->startOfDay();

        /*
        |--------------------------------------------------------------------------
        | Projected Pensionable Service
        |--------------------------------------------------------------------------
        |
        | Pensionable service is calculated by contribution months inclusive.
        |
        | Example:
        | Date joined fund : 01/01/2009
        | Retirement date : 30/04/2039
        |
        | January 2009 through April 2039 inclusive = 364 months.
        |
        | This avoids Carbon fractional-month calculations that previously
        | understated projected service by approximately two months.
        |
        */
        $projectedServiceMonths=$this->inclusiveServiceMonths(
            Carbon::parse($member->date_joined_fund),
            $retirementDate
        );

        $futureServiceMonths=max(
            0,
            $projectedServiceMonths-$currentServiceMonths
        );

        /*
        |--------------------------------------------------------------------------
        | Base Annual Pension
        |--------------------------------------------------------------------------
        |
        | LAPF formula:
        |
        | N / 600 × PE
        |
        | N  = projected pensionable service in months
        | PE = pensionable emoluments
        |
        */
        $baseAnnualPension=$divisor>0
            ? ($projectedServiceMonths/$divisor)*$pensionableEmoluments
            : 0.0;

        /*
        |--------------------------------------------------------------------------
        | Early Retirement
        |--------------------------------------------------------------------------
        |
        | Retirement before normal retirement age is reduced by the configured
        | percentage for every year before normal retirement age.
        |
        | Current LAPF statement behaviour:
        | Age 55 against normal age 60 = 5 years early.
        |
        | If configured at 3% per year:
        | 5 × 3% = 15% reduction.
        |
        */
        $earlyReductionPercentage=0.0;

        if ($retirementAge<$normalAge) {
            $yearsEarly=$normalAge-$retirementAge;

            $earlyReductionPercentage=
                $yearsEarly*$earlyRetirementReductionPerYear;
        }

        /*
        |--------------------------------------------------------------------------
        | Late Retirement Increase
        |--------------------------------------------------------------------------
        |
        | Existing retirement increase tables/settings continue to determine
        | the increase for retirement after normal retirement age.
        |
        */
        $lateIncreasePercentage=0.0;

        if ($retirementAge>$normalAge) {
            $lateIncreasePercentage=$this->settings
                ->retirementIncreasePercentage(
                    $retirementAge,
                    0,
                    $statementDate
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Adjusted Annual Pension
        |--------------------------------------------------------------------------
        */

        $annualPension=$baseAnnualPension;

        if ($earlyReductionPercentage>0) {
            $annualPension=$annualPension*(
                1-($earlyReductionPercentage/100)
            );
        }

        if ($lateIncreasePercentage>0) {
            $annualPension=$annualPension*(
                1+($lateIncreasePercentage/100)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Commutation
        |--------------------------------------------------------------------------
        */

        $commutationFactor=$this->settings->commutationFactor(
            $member->gender,
            $retirementAge,
            0,
            $statementDate
        );

        $commutationAmount=
            $annualPension
            *$commutationFraction
            *$commutationFactor;

        return [
            'age'=>$retirementAge,
            'retirement_date'=>$retirementDate,
            'future_service_months'=>$futureServiceMonths,
            'projected_service_months'=>$projectedServiceMonths,

            'base_annual_pension'=>round($baseAnnualPension,2),

            'early_retirement_reduction_percentage'=>round(
                $earlyReductionPercentage,
                4
            ),

            'increase_percentage'=>round(
                $lateIncreasePercentage,
                4
            ),

            'annual_pension'=>round($annualPension,2),

            'commutation_fraction'=>$commutationFraction,
            'commutation_factor'=>round($commutationFactor,6),
            'commutation_amount'=>round($commutationAmount,2),
        ];
    }

    private function inclusiveServiceMonths(Carbon $joined,Carbon $end): int
    {
        $joined=$joined->copy()->startOfMonth();
        $end=$end->copy()->startOfMonth();

        if ($end->lt($joined)) {
            return 0;
        }

        return (($end->year-$joined->year)*12)
            +($end->month-$joined->month)
            +1;
    }
}
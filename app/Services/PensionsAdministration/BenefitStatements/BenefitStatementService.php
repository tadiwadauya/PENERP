<?php

namespace App\Services\PensionsAdministration\BenefitStatements;

use App\Models\PensionsAdministration\Updates\Member;
use App\Models\PensionsAdministration\Updates\MemberEmployment;
use App\Services\PensionsAdministration\Settings\BenefitSettingResolver;
use Carbon\Carbon;
use RuntimeException;

class BenefitStatementService
{
    public function __construct(
        private readonly BenefitStatementContributionService $contributions,
        private readonly BenefitStatementRetirementService $retirement,
        private readonly BenefitStatementDeathService $death,
        private readonly BenefitSettingResolver $settings
    ) {}

    public function generate(Member|int $member,Carbon|string $statementDate): array
    {
        $member=$member instanceof Member ? $member : Member::query()->findOrFail($member);
        $statementDate=Carbon::parse($statementDate)->endOfDay();

        if ($statementDate->isFuture()) {
            throw new RuntimeException('The Benefit Statement date cannot be in the future.');
        }

        $employment=$this->employmentAsAt($member,$statementDate);

        if (!$employment) {
            throw new RuntimeException(
                'No employment record was found for this member as at '.$statementDate->format('d/m/Y').'.'
            );
        }

        $employment->loadMissing('employer');

        if (!$employment->employer) {
            throw new RuntimeException('The member employment record is not linked to an employer.');
        }

        $contributionData=$this->contributions->calculate(
            $member->id,
            $employment->employer_id,
            $statementDate
        );

        /*
        |--------------------------------------------------------------------------
        | Pensionable Service
        |--------------------------------------------------------------------------
        |
        | $serviceMonths represents completed pensionable service months for
        | statement display and other existing benefit calculations.
        |
        | Death-benefit interest uses inclusive service months and is calculated
        | independently by BenefitStatementDeathService.
        |
        */
        $serviceMonths=$this->pensionableServiceMonths(
            $member,
            $statementDate
        );

        $currentAnnualEmoluments=(float)$contributionData['current_annual_emoluments'];
        $pensionableEmoluments=(float)$contributionData['pensionable_emoluments'];

        $retirementData=$this->retirement->calculate(
            $member,
            $statementDate,
            $pensionableEmoluments,
            $serviceMonths
        );

        $withdrawal=$this->withdrawalBenefit(
            $statementDate,
            $serviceMonths,
            $contributionData
        );

        /*
        |--------------------------------------------------------------------------
        | Death Benefit
        |--------------------------------------------------------------------------
        |
        | Total Death Benefit Contributions currently correspond to the
        | accumulated employee contribution balance used by the Fund:
        |
        | employee contribution
        | + employee arrears
        | + employee transfer-in
        |
        | Interest is calculated in BenefitStatementDeathService using:
        |
        | Inclusive Pensionable Service / 400
        | × Total Death Benefit Contributions
        |
        */
        $death=$this->death->calculate(
            $member,
            $statementDate,
            (float)$withdrawal['accumulated_employee_contributions'],
            $currentAnnualEmoluments
        );

        $accumulatedInterest=$this->accumulatedInterestInFund(
            $member,
            $statementDate,
            $serviceMonths,
            $pensionableEmoluments
        );

        return [
            'statement_date'=>$statementDate,
            'currency'=>'ZWG',

            'member'=>[
                'id'=>$member->id,
                'member_number'=>$member->member_number,
                'penad_member_number'=>$member->penad_member_number,
                'fundworx_member_number'=>$member->fundworx_member_number,
                'national_id'=>$member->national_id,
                'name'=>trim($member->first_names.' '.$member->surname),
                'surname'=>$member->surname,
                'first_names'=>$member->first_names,
            ],

            'employment'=>[
                'employer_id'=>$employment->employer_id,
                'employer_number'=>$employment->employer->employer_number,
                'employer_name'=>$employment->employer->name,
                'staff_number'=>$employment->staff_number,
                'vote_number'=>$employment->vote_number,
                'department'=>$employment->department,
                'branch'=>$employment->branch,
            ],

            'personal_details'=>[
                'gender'=>$member->gender,
                'date_of_birth'=>$member->date_of_birth,
                'marital_status'=>$member->marital_status,
                'date_joined_fund'=>$member->date_joined_fund,
                'pensionable_service_months'=>$serviceMonths,
                'normal_retirement_date'=>$retirementData['normal_retirement_date'],
                'current_annual_emoluments'=>$currentAnnualEmoluments,
                'pensionable_emoluments'=>$pensionableEmoluments,
                'projected_replacement_ratio'=>$retirementData['projected_replacement_ratio'],
            ],

            'withdrawal_benefit'=>$withdrawal,
            'pension_benefit'=>$retirementData,
            'death_benefit'=>$death,
            'accumulated_interest_in_fund'=>$accumulatedInterest,
            'contribution_history'=>$contributionData,
        ];
    }

    private function employmentAsAt(Member $member,Carbon $statementDate): ?MemberEmployment
    {
        return MemberEmployment::query()
            ->where('member_id',$member->id)
            ->where(function($query) use($statementDate): void {
                $query->whereNull('effective_from')
                    ->orWhereDate('effective_from','<=',$statementDate);
            })
            ->where(function($query) use($statementDate): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to','>=',$statementDate);
            })
            ->orderByDesc('is_current')
            ->orderByDesc('effective_from')
            ->first();
    }

    private function pensionableServiceMonths(Member $member,Carbon $statementDate): int
    {
        if (!$member->date_joined_fund) {
            return 0;
        }

        $joined=Carbon::parse($member->date_joined_fund)->startOfDay();

        $end=$member->exit_date && Carbon::parse($member->exit_date)->lte($statementDate)
            ? Carbon::parse($member->exit_date)->startOfDay()
            : $statementDate->copy()->startOfDay();

        if ($statementDate->lt($joined) || $end->lte($joined)) {
            return 0;
        }

        return (int)floor($joined->diffInMonths($end));
    }

    private function withdrawalBenefit(Carbon $statementDate,int $serviceMonths,array $contributionData): array
    {
        $closing=$contributionData['closing_balance'];

        $employeeContributions=
            (float)($closing['employee_contribution']??0)
            +(float)($closing['employee_arrear']??0)
            +(float)($closing['employee_transfer_in']??0);

        $employerContributions=
            (float)($closing['employer_contribution']??0)
            +(float)($closing['employer_arrear']??0)
            +(float)($closing['employer_transfer_in']??0);

        $employeeAvc=(float)($closing['employee_avc']??0);
        $employerAvc=(float)($closing['employer_avc']??0);

        $entitlementPercentage=$this->settings
            ->withdrawalEmployerEntitlementPercentage(
                $serviceMonths,
                $statementDate
            );

        $employerEntitlement=
            $employerContributions
            *($entitlementPercentage/100);

        $additionalEmployerAmount=0.0;

        $minimum=(int)$this->settings->setting(
            'withdrawal.additional_employer_minimum_service_months',
            $statementDate
        );

        if ($serviceMonths>=$minimum) {
            $divisor=(int)$this->settings->setting(
                'withdrawal.additional_employer_service_divisor_months',
                $statementDate
            );

            if ($divisor>0) {
                $additionalEmployerAmount=
                    ($serviceMonths/$divisor)
                    *$employerContributions;
            }
        }

        return [
            'accumulated_employee_contributions'=>round(
                $employeeContributions,
                2
            ),

            /*
            | This remains the withdrawal-statement interest field.
            | Do not use it for Death Benefit interest.
            */
            'interest_earned_on_employee_contributions_during_period'=>0.0,

            'accumulated_additional_contributions_plus_interest'=>round(
                $employeeAvc+$employerAvc,
                2
            ),

            'total_employer_contributions'=>round(
                $employerContributions,
                2
            ),

            'employer_entitlement_percentage'=>round(
                $entitlementPercentage,
                4
            ),

            'employer_entitlement_amount'=>round(
                $employerEntitlement,
                2
            ),

            'additional_employer_amount_for_15_year_service'=>round(
                $additionalEmployerAmount,
                2
            ),

            'accumulated_employer_contributions_plus_interest'=>round(
                $employerEntitlement+$additionalEmployerAmount,
                2
            ),

            'past_service_contributions_plus_interest'=>0.0,
        ];
    }

    private function accumulatedInterestInFund(
        Member $member,
        Carbon $statementDate,
        int $serviceMonths,
        float $annualPensionableSalary
    ): array {
        if (!$member->date_of_birth) {
            return [
                'eligible'=>false,
                'amount'=>0.0,
                'reason'=>'Date of birth unavailable.',
            ];
        }

        $minimumService=(int)$this->settings->setting(
            'accumulated_interest.minimum_service_months',
            $statementDate
        );

        $maximumAge=(int)$this->settings->setting(
            'accumulated_interest.maximum_age_exclusive',
            $statementDate
        );

        $divisor=(int)$this->settings->setting(
            'accumulated_interest.formula_divisor',
            $statementDate
        );

        $dob=Carbon::parse($member->date_of_birth)->startOfDay();
        $asAt=$statementDate->copy()->startOfDay();

        $ageYears=(int)floor(
            $dob->diffInYears($asAt)
        );

        if ($serviceMonths<$minimumService) {
            return [
                'eligible'=>false,
                'amount'=>0.0,
                'age_years'=>$ageYears,
                'service_months'=>$serviceMonths,
                'reason'=>'Pensionable service is below the minimum required service.',
            ];
        }

        if ($ageYears>=$maximumAge) {
            return [
                'eligible'=>false,
                'amount'=>0.0,
                'age_years'=>$ageYears,
                'service_months'=>$serviceMonths,
                'reason'=>'Member has reached or exceeded the maximum qualifying age.',
            ];
        }

        $factor=$this->settings->accumulatedInterestFactor(
            $member->gender,
            $ageYears,
            $statementDate
        );

        $yearsInService=$serviceMonths/12;

        $amount=$divisor>0
            ? ($annualPensionableSalary*$factor*$yearsInService)/$divisor
            : 0.0;

        return [
            'eligible'=>true,
            'amount'=>round($amount,2),
            'age_years'=>$ageYears,
            'service_months'=>$serviceMonths,
            'years_in_service'=>round($yearsInService,6),
            'factor'=>$factor,
            'annual_pensionable_salary'=>round($annualPensionableSalary,2),
            'formula_divisor'=>$divisor,
        ];
    }
}
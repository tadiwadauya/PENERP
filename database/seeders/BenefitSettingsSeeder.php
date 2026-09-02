<?php

namespace Database\Seeders;

use App\Models\PensionsAdministration\Settings\BenefitSetting;
use Illuminate\Database\Seeder;

class BenefitSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['category'=>'currency','setting_key'=>'currency.base_currency','name'=>'Base Currency','description'=>'Fund base currency used for benefit calculations.','value_type'=>'string','value_string'=>'ZWG','effective_from'=>'2024-04-05','source_authority'=>'2024 Monetary Policy / SI 60 of 2024'],
            ['category'=>'retirement','setting_key'=>'retirement.minimum_age','name'=>'Minimum Retirement Age','description'=>'Minimum retirement age allowed under the Fund rules.','value_type'=>'integer','value_integer'=>55,'effective_from'=>'2000-01-01','source_authority'=>'LAPF Rules / Benefit Statement'],
            ['category'=>'retirement','setting_key'=>'retirement.normal_age','name'=>'Normal Retirement Age','description'=>'Normal retirement age used for benefit statement projections.','value_type'=>'integer','value_integer'=>60,'effective_from'=>'2000-01-01','source_authority'=>'LAPF Benefit Statement'],
            ['category'=>'retirement','setting_key'=>'retirement.maximum_age','name'=>'Maximum Retirement Age','description'=>'Maximum permissible retirement age before the 2025 amendment.','value_type'=>'integer','value_integer'=>65,'effective_from'=>'2000-01-01','effective_to'=>'2024-12-31','source_authority'=>'LAPF Rules prior to Amendment Number 2/2025'],
            ['category'=>'retirement','setting_key'=>'retirement.maximum_age','name'=>'Maximum Retirement Age','description'=>'Maximum permissible retirement age from 1 January 2025.','value_type'=>'integer','value_integer'=>70,'effective_from'=>'2025-01-01','source_authority'=>'LAPF Amendment Number 2/2025'],
            ['category'=>'retirement','setting_key'=>'retirement.pension_service_divisor_months','name'=>'Pension Service Divisor','description'=>'Pension formula divisor where N is pensionable service in months: N / 600 × Pensionable Emoluments.','value_type'=>'integer','value_integer'=>600,'effective_from'=>'2000-01-01','source_authority'=>'LAPF Benefit Statement'],
            ['category'=>'retirement','setting_key'=>'retirement.accrual_rate','name'=>'Fund Accrual Rate','description'=>'Current accrual rate used in replacement-ratio examples.','value_type'=>'decimal','value_decimal'=>0.02,'effective_from'=>'2000-01-01','source_authority'=>'LAPF Benefit Statement'],
            ['category'=>'retirement','setting_key'=>'retirement.late_retirement_monthly_increment_percentage','name'=>'Late Retirement Monthly Increment','description'=>'Additional percentage for each completed month above a completed retirement age.','value_type'=>'decimal','value_decimal'=>0.5,'effective_from'=>'2025-01-01','source_authority'=>'LAPF Amendment Number 2/2025'],
            ['category'=>'withdrawal','setting_key'=>'withdrawal.minimum_employer_entitlement_service_months','name'=>'Minimum Service for Employer Contribution Entitlement','description'=>'Minimum pensionable service required before withdrawal employer-contribution entitlement applies.','value_type'=>'integer','value_integer'=>60,'effective_from'=>'2000-01-01','source_authority'=>'LAPF Benefit Statement'],
            ['category'=>'withdrawal','setting_key'=>'withdrawal.additional_employer_minimum_service_months','name'=>'Minimum Service for Additional Employer Portion','description'=>'Minimum service for the additional employer contribution amount.','value_type'=>'integer','value_integer'=>180,'effective_from'=>'2000-01-01','source_authority'=>'LAPF withdrawal benefit rule supplied by Fund'],
            ['category'=>'withdrawal','setting_key'=>'withdrawal.additional_employer_service_divisor_months','name'=>'Additional Employer Service Divisor','description'=>'Formula divisor used as Total Pensionable Service / 400 × Total Employer Contributions.','value_type'=>'integer','value_integer'=>400,'effective_from'=>'2000-01-01','source_authority'=>'LAPF withdrawal benefit rule supplied by Fund'],
            ['category'=>'accumulated_interest','setting_key'=>'accumulated_interest.minimum_service_months','name'=>'Accumulated Interest Minimum Service','description'=>'Minimum service for Accumulated Interest in the Fund.','value_type'=>'integer','value_integer'=>180,'effective_from'=>'2000-01-01','source_authority'=>'LAPF Benefit Statement / Fund rule supplied'],
            ['category'=>'accumulated_interest','setting_key'=>'accumulated_interest.maximum_age_exclusive','name'=>'Accumulated Interest Maximum Age','description'=>'Member must be below this age for Accumulated Interest in the Fund.','value_type'=>'integer','value_integer'=>55,'effective_from'=>'2000-01-01','source_authority'=>'LAPF Benefit Statement / Fund rule supplied'],
            ['category'=>'accumulated_interest','setting_key'=>'accumulated_interest.formula_divisor','name'=>'Accumulated Interest Formula Divisor','description'=>'Formula divisor: Annual Pensionable Salary × Factor × Years in Service / 1000.','value_type'=>'integer','value_integer'=>1000,'effective_from'=>'2000-01-01','source_authority'=>'LAPF accumulated interest formula supplied by Fund'],
            ['category'=>'commutation','setting_key'=>'commutation.fraction','name'=>'One Third Commutation Fraction','description'=>'Maximum one-third pension commutation fraction.','value_type'=>'decimal','value_decimal'=>0.33333333,'effective_from'=>'2000-01-01','source_authority'=>'LAPF Commutation Factors / Benefit Statement'],
            ['category'=>'commutation','setting_key'=>'commutation.ill_health_age_offset_years','name'=>'Ill-health Commutation Age Offset','description'=>'For ill-health retirement, use the commutation factor at age x + 5 years.','value_type'=>'integer','value_integer'=>5,'effective_from'=>'2000-01-01','source_authority'=>'LAPF Commutation Factors'],
        ];

        foreach ($rows as $row) {
            BenefitSetting::query()->updateOrCreate(
                ['setting_key'=>$row['setting_key'],'effective_from'=>$row['effective_from']],
                array_merge(['effective_to'=>null,'notes'=>null,'is_active'=>true,'created_by'=>null,'updated_by'=>null], $row)
            );
        }
    }
}

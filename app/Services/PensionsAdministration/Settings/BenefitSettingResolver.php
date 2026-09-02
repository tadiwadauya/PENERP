<?php

namespace App\Services\PensionsAdministration\Settings;

use App\Models\PensionsAdministration\Settings\AccumulatedInterestFactor;
use App\Models\PensionsAdministration\Settings\BenefitSetting;
use App\Models\PensionsAdministration\Settings\CommutationFactor;
use App\Models\PensionsAdministration\Settings\RetirementAgeIncreaseFactor;
use App\Models\PensionsAdministration\Settings\WithdrawalEmployerEntitlementScale;
use Carbon\Carbon;
use RuntimeException;

class BenefitSettingResolver
{
    public function setting(string $key, Carbon|string|null $asAt = null): mixed
    {
        $date = $this->date($asAt);
        $setting = BenefitSetting::query()->where('setting_key',$key)->where('is_active',true)->whereDate('effective_from','<=',$date)->where(function($query) use($date): void {$query->whereNull('effective_to')->orWhereDate('effective_to','>=',$date);})->orderByDesc('effective_from')->first();
        if (!$setting) throw new RuntimeException("No active benefit setting found for [{$key}] as at {$date}.");
        return match ($setting->value_type) {
            'decimal' => $setting->value_decimal !== null ? (float) $setting->value_decimal : null,
            'integer' => $setting->value_integer !== null ? (int) $setting->value_integer : null,
            'boolean' => (bool) $setting->value_boolean,
            'string' => $setting->value_string,
            default => $setting->value_string,
        };
    }

    public function commutationFactor(string $gender, int $ageYears, int $ageMonths = 0, Carbon|string|null $asAt = null): float
    {
        $date=$this->date($asAt); $gender=strtolower(trim($gender));
        $factor=CommutationFactor::query()->where('gender',$gender)->where('age_years',$ageYears)->where('age_months',$ageMonths)->where('is_active',true)->whereDate('effective_from','<=',$date)->where(function($query) use($date): void {$query->whereNull('effective_to')->orWhereDate('effective_to','>=',$date);})->orderByDesc('effective_from')->value('factor');
        if ($factor===null) throw new RuntimeException("No commutation factor found for {$gender}, age {$ageYears} years {$ageMonths} months as at {$date}.");
        return (float)$factor;
    }

    public function accumulatedInterestFactor(string $gender, int $ageYears, Carbon|string|null $asAt = null): float
    {
        $date=$this->date($asAt); $gender=strtolower(trim($gender));
        $factor=AccumulatedInterestFactor::query()->where('gender',$gender)->where('age_years',$ageYears)->where('is_active',true)->whereDate('effective_from','<=',$date)->where(function($query) use($date): void {$query->whereNull('effective_to')->orWhereDate('effective_to','>=',$date);})->orderByDesc('effective_from')->value('factor');
        if ($factor===null) throw new RuntimeException("No accumulated-interest factor found for {$gender}, age {$ageYears} as at {$date}.");
        return (float)$factor;
    }

    public function withdrawalEmployerEntitlementPercentage(int $serviceMonths, Carbon|string|null $asAt = null): float
    {
        $date=$this->date($asAt);
        $row=WithdrawalEmployerEntitlementScale::query()->where('is_active',true)->where('minimum_service_months','<=',$serviceMonths)->where(function($query) use($serviceMonths): void {$query->whereNull('maximum_service_months')->orWhere('maximum_service_months','>=',$serviceMonths);})->whereDate('effective_from','<=',$date)->where(function($query) use($date): void {$query->whereNull('effective_to')->orWhereDate('effective_to','>=',$date);})->orderByDesc('minimum_service_months')->orderByDesc('effective_from')->first();
        return $row ? (float)$row->entitlement_percentage : 0.0;
    }

    public function retirementIncreasePercentage(int $ageYears, int $completedMonthsAfterBirthday, Carbon|string|null $asAt = null): float
    {
        $date=$this->date($asAt);
        $base=RetirementAgeIncreaseFactor::query()->where('age_years',$ageYears)->where('is_active',true)->whereDate('effective_from','<=',$date)->where(function($query) use($date): void {$query->whereNull('effective_to')->orWhereDate('effective_to','>=',$date);})->orderByDesc('effective_from')->value('increase_percentage');
        if ($base===null) throw new RuntimeException("No retirement increase factor found for age {$ageYears} as at {$date}.");
        $monthly=(float)$this->setting('retirement.late_retirement_monthly_increment_percentage',$date);
        return (float)$base + (max(0,min(11,$completedMonthsAfterBirthday))*$monthly);
    }

    private function date(Carbon|string|null $value): string
    {
        return $value ? Carbon::parse($value)->toDateString() : now()->toDateString();
    }
}

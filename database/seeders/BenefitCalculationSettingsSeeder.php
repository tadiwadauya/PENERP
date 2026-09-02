<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BenefitCalculationSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BenefitSettingsSeeder::class,
            WithdrawalEmployerEntitlementScaleSeeder::class,
            AccumulatedInterestFactorSeeder::class,
            CommutationFactorSeeder::class,
            RetirementAgeIncreaseFactorSeeder::class,
        ]);
    }
}

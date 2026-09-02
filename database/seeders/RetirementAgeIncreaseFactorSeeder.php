<?php

namespace Database\Seeders;

use App\Models\PensionsAdministration\Settings\RetirementAgeIncreaseFactor;
use Illuminate\Database\Seeder;

class RetirementAgeIncreaseFactorSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [60=>0.00,61=>6.00,62=>12.00,63=>18.00,64=>24.00,65=>30.00,66=>36.00,67=>42.00,68=>48.00,69=>54.00,70=>60.00];
        foreach ($rows as $age=>$percentage) {
            RetirementAgeIncreaseFactor::query()->updateOrCreate(
                ['age_years'=>$age,'effective_from'=>'2025-01-01'],
                ['increase_percentage'=>$percentage,'effective_to'=>null,'source_authority'=>'LAPF Amendment Number 2/2025 - Third Schedule','notes'=>'Add 0.5% for each completed month above the completed age where applicable.','is_active'=>true,'created_by'=>null,'updated_by'=>null]
            );
        }
    }
}

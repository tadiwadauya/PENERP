<?php

namespace Database\Seeders;

use App\Models\PensionsAdministration\Settings\WithdrawalEmployerEntitlementScale;
use Illuminate\Database\Seeder;

class WithdrawalEmployerEntitlementScaleSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [[60,71,30.00],[72,83,40.00],[84,95,50.00],[96,107,60.00],[108,119,70.00],[120,131,80.00],[132,143,90.00],[144,null,100.00]];
        foreach ($rows as [$minimum,$maximum,$percentage]) {
            WithdrawalEmployerEntitlementScale::query()->updateOrCreate(
                ['minimum_service_months'=>$minimum,'effective_from'=>'2000-01-01'],
                ['maximum_service_months'=>$maximum,'entitlement_percentage'=>$percentage,'effective_to'=>null,'source_authority'=>'LAPF Benefit Statement: 30% at 5 years, increasing by 10% for each additional year.','notes'=>null,'is_active'=>true,'created_by'=>null,'updated_by'=>null]
            );
        }
    }
}

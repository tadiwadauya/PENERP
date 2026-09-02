<?php

namespace Database\Seeders;

use App\Models\PensionsAdministration\Settings\InterestRate;
use Illuminate\Database\Seeder;

class InterestRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            ['2009-01-01', '2018-12-31', 1.50, null],
            ['2019-01-01', '2019-04-30', 10.00, null],
            ['2019-05-01', '2020-03-13', 15.00, null],
            ['2020-03-14', '2020-05-31', 30.00, null],
            ['2020-06-01', '2020-08-31', 40.00, null],
            ['2020-09-01', '2020-12-31', 50.00, null],
            ['2021-01-01', '2021-02-14', 55.00, null],
            ['2021-02-15', '2021-05-31', 45.00, null],
            ['2021-06-01', '2021-06-30', 47.00, null],
            ['2021-07-01', '2021-07-31', 47.00, null],
            ['2021-08-01', '2021-10-31', 45.00, null],
            ['2021-11-01', '2022-03-31', 47.00, null],
            ['2022-04-01', '2022-04-30', 55.00, null],
            ['2022-05-01', '2022-06-30', 64.00, null],
            ['2022-07-01', '2023-06-30', 103.00, null],
            ['2023-07-01', '2023-12-31', 90.00, null],
            ['2024-01-01', '2024-01-31', 85.00, null],
            ['2024-02-01', '2024-04-05', 90.00, null],
            ['2024-04-06', '2024-09-30', 25.00, null],
            ['2024-10-01', '2024-10-31', 40.00, null],
            ['2024-11-01', '2026-03-31', 50.00, null],
            ['2026-04-01', null, 45.00, 'Current rate.'],
        ];

        foreach ($rates as [$from, $to, $rate, $notes]) {
            InterestRate::updateOrCreate(
                ['effective_from' => $from],
                [
                    'effective_to' => $to,
                    'rate_percentage' => $rate,
                    'source_authority' => 'LAPF Schedule of Applicable Interest Rates',
                    'notes' => $notes,
                    'is_active' => true,
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );
        }
    }
}
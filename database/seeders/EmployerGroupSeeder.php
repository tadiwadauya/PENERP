<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployerGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            [
                'code' => '1',
                'name' => 'Group 1',
                'description' => 'Employer Group 1',
                'is_active' => true,
            ],
            [
                'code' => '2',
                'name' => 'Group 2',
                'description' => 'Employer Group 2',
                'is_active' => true,
            ],
            [
                'code' => '3',
                'name' => 'Group 3',
                'description' => 'Employer Group 3',
                'is_active' => true,
            ],
            [
                'code' => '4',
                'name' => 'Group 4',
                'description' => 'Employer Group 4',
                'is_active' => true,
            ],
        ];

        foreach ($groups as $group) {
            DB::table('employer_groups')->updateOrInsert(
                [
                    'code' => $group['code'],
                ],
                [
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'is_active' => $group['is_active'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $this->command->info('Employer Groups 1 to 4 seeded successfully.');
    }
}
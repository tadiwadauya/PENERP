<?php

namespace Database\Seeders;

use App\Models\UserManagement\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            [
                'code' => '1',
                'name' => 'Grade 1',
                'rank_order' => 1,
            ],
            [
                'code' => '2',
                'name' => 'Grade 2',
                'rank_order' => 2,
            ],
            [
                'code' => '3',
                'name' => 'Grade 3',
                'rank_order' => 3,
            ],
            [
                'code' => '4',
                'name' => 'Grade 4',
                'rank_order' => 4,
            ],
            [
                'code' => '5',
                'name' => 'Grade 5',
                'rank_order' => 5,
            ],
            [
                'code' => '6',
                'name' => 'Grade 6',
                'rank_order' => 6,
            ],
            [
                'code' => '7',
                'name' => 'Grade 7',
                'rank_order' => 7,
            ],
            [
                'code' => '8',
                'name' => 'Grade 8',
                'rank_order' => 8,
            ],
            [
                'code' => '9',
                'name' => 'Grade 9',
                'rank_order' => 9,
            ],
            [
                'code' => '10',
                'name' => 'Grade 10',
                'rank_order' => 10,
            ],
        ];

        foreach ($grades as $grade) {
            Grade::updateOrCreate(
                [
                    'code' => $grade['code'],
                ],
                [
                    'name' => $grade['name'],
                    'rank_order' => $grade['rank_order'],
                    'is_management' => false,
                    'is_active' => true,
                    'description' => null,
                ]
            );
        }
    }
}
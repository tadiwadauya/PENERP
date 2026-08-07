<?php

namespace Database\Seeders;

use App\Models\UserManagement\JobTitle;
use Illuminate\Database\Seeder;

class JobTitleSeeder extends Seeder
{
    public function run(): void
    {
        $jobTitles = [

            /*
            |--------------------------------------------------------------------------
            | Principal Officer's Office
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'CEO',
                'name' => 'Principal Officer / Chief Executive Officer',
                'description' => 'Principal Officer and Chief Executive Officer of LAPF.',
            ],

            [
                'code' => 'MCA',
                'name' => 'Manager Corporate Administration',
                'description' => 'Manager responsible for corporate administration and executive support to the Principal Officer.',
            ],

            /*
            |--------------------------------------------------------------------------
            | Department Heads / Executives
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'FE',
                'name' => 'Finance Executive',
                'description' => 'Executive responsible for the Finance Department.',
            ],

            [
                'code' => 'PAE',
                'name' => 'Pensions Administration Executive',
                'description' => 'Executive responsible for the Pensions Administration Department.',
            ],

            [
                'code' => 'HP',
                'name' => 'Head of Property',
                'description' => 'Head responsible for the Property Department.',
            ],

            /*
            |--------------------------------------------------------------------------
            | ICT Section
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'ICTO',
                'name' => 'ICT Officer',
                'description' => 'Officer responsible for ICT operations, systems administration, security and ICT management.',
            ],

            [
                'code' => 'ICTA',
                'name' => 'ICT Administrator',
                'description' => 'Administrator responsible for ICT systems, infrastructure and technical support.',
            ],

            /*
            |--------------------------------------------------------------------------
            | Pensions Administration - Updates and Benefit Claims
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'PAM',
                'name' => 'Pensions Administration Manager',
                'description' => 'Manager responsible for the Updates and Benefit Claims functions.',
            ],

            [
                'code' => 'PAO',
                'name' => 'Pensions Administration Officer',
                'description' => 'Officer responsible for member updates and related pensions administration activities.',
            ],

            [
                'code' => 'BCO',
                'name' => 'Benefit Claims Officer',
                'description' => 'Officer responsible for processing and managing pension benefit claims.',
            ],

            /*
            |--------------------------------------------------------------------------
            | Pensions Administration - Payroll
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'PPM',
                'name' => 'Pensions Payment Manager',
                'description' => 'Manager responsible for pension payroll and pension payment processing.',
            ],

            [
                'code' => 'PPO',
                'name' => 'Pensions Payroll Officer',
                'description' => 'Officer responsible for pension payroll processing and administration.',
            ],

            [
                'code' => 'RC',
                'name' => 'Registry Clerk',
                'description' => 'Clerk responsible for records and registry management.',
            ],

            /*
            |--------------------------------------------------------------------------
            | Finance Department
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'ACC',
                'name' => 'Accountant',
                'description' => 'Accounting position within the Finance Department.',
            ],

            [
                'code' => 'ASSIST-ACCOUNTANT',
                'name' => 'Assistant Accountant',
                'description' => 'Assistant accounting position within the Finance Department.',
            ],

            /*
            |--------------------------------------------------------------------------
            | Property Department
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'PROPA',
                'name' => 'Property Administrator',
                'description' => 'Administrator responsible for property administration.',
            ],

            [
                'code' => 'PROPC',
                'name' => 'Property Clerk',
                'description' => 'Clerical position within the Property Department.',
            ],

            /*
            |--------------------------------------------------------------------------
            | Human Resources
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'HRO',
                'name' => 'Human Resources Officer',
                'description' => 'Officer responsible for human resources administration and management.',
            ],

            /*
            |--------------------------------------------------------------------------
            | Procurement
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'PROC-O',
                'name' => 'Procurement Officer',
                'description' => 'Officer responsible for procurement activities.',
            ],

            /*
            |--------------------------------------------------------------------------
            | General Positions
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'RECEPTIONIST',
                'name' => 'Receptionist',
                'description' => 'Reception and front-office position.',
            ],
        ];

        foreach ($jobTitles as $jobTitle) {
            JobTitle::updateOrCreate(
                [
                    'code' => $jobTitle['code'],
                ],
                [
                    'name' => $jobTitle['name'],
                    'description' => $jobTitle['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
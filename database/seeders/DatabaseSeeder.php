<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([

            /*
            |--------------------------------------------------------------------------
            | Dashboard
            |--------------------------------------------------------------------------
            */

            DashboardSeeder::class,


            /*
            |--------------------------------------------------------------------------
            | Organisation Structure
            |--------------------------------------------------------------------------
            */

            OrganisationStructureSeeder::class,


            /*
            |--------------------------------------------------------------------------
            | Grades
            |--------------------------------------------------------------------------
            */

            GradeSeeder::class,


            /*
            |--------------------------------------------------------------------------
            | Job Titles
            |--------------------------------------------------------------------------
            */

            JobTitleSeeder::class,


            /*
            |--------------------------------------------------------------------------
            | Password Policy
            |--------------------------------------------------------------------------
            */

            PasswordPolicySeeder::class,


            /*
            |--------------------------------------------------------------------------
            | Modules and Permissions
            |--------------------------------------------------------------------------
            |
            | Includes:
            |
            | - Dashboard
            | - User Management
            | - Audit and Security
            | - Pensions Administration - Updates
            | - Pensions Administration - Contributions
            |
            */

            ModulePermissionSeeder::class,


            /*
            |--------------------------------------------------------------------------
            | Roles and Role Permissions
            |--------------------------------------------------------------------------
            */

            RolePermissionSeeder::class,


            /*
            |--------------------------------------------------------------------------
            | System Administrator
            |--------------------------------------------------------------------------
            */

            SystemAdministratorSeeder::class,

            EmployerGroupSeeder::class,

        ]);
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DashboardSeeder::class,
            OrganisationStructureSeeder::class,

            GradeSeeder::class,
            JobTitleSeeder::class,

            PasswordPolicySeeder::class,

            ModulePermissionSeeder::class,
            RolePermissionSeeder::class,

            SystemAdministratorSeeder::class,
        ]);
    }
}
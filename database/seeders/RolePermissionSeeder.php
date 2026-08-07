<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $systemAdministrator = Role::updateOrCreate(
            [
                'name' => 'system-administrator',
                'guard_name' => 'web',
            ],
            [
                'display_name' => 'System Administrator',
                'description' => 'ICT system administration role.',
                'is_system_role' => true,
                'is_active' => true,
            ]
        );

        /*
         * Initial administrator gets administrative permissions.
         * Future business permissions such as claim approval
         * will NOT automatically be assigned here.
         */
        $adminPermissions = Permission::query()
            ->where(function ($query) {
                $query->where('name', 'like', 'user-management.%')
                    ->orWhere('name', 'like', 'audit.%')
                    ->orWhere(
                        'name',
                        'dashboard.system-administration.view'
                    );
            })
            ->get();

        $systemAdministrator->syncPermissions($adminPermissions);
    }
}
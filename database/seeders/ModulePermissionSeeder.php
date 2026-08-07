<?php

namespace Database\Seeders;

use App\Models\UserManagement\SystemModule;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ModulePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'code' => 'dashboard',
                'name' => 'Dashboards',
                'display_order' => 1,
                'permissions' => [
                    [
                        'name' => 'dashboard.finance.view',
                        'display_name' => 'View Finance Dashboard',
                        'action' => 'view',
                    ],
                    [
                        'name' => 'dashboard.pensions.view',
                        'display_name' => 'View Pensions Dashboard',
                        'action' => 'view',
                    ],
                    [
                        'name' => 'dashboard.property.view',
                        'display_name' => 'View Property Dashboard',
                        'action' => 'view',
                    ],
                    [
                        'name' => 'dashboard.principal-office.view',
                        'display_name' => 'View Principal Office Dashboard',
                        'action' => 'view',
                    ],
                    [
                        'name' => 'dashboard.system-administration.view',
                        'display_name' => 'View System Administration Dashboard',
                        'action' => 'view',
                    ],
                ],
            ],

            [
                'code' => 'user-management',
                'name' => 'User Management',
                'display_order' => 2,
                'permissions' => [
                    ['name' => 'user-management.users.view', 'display_name' => 'View Users', 'action' => 'view'],
                    ['name' => 'user-management.users.create', 'display_name' => 'Create Users', 'action' => 'create'],
                    ['name' => 'user-management.users.update', 'display_name' => 'Update Users', 'action' => 'update'],
                    ['name' => 'user-management.users.delete', 'display_name' => 'Delete Users', 'action' => 'delete'],
                    ['name' => 'user-management.users.activate', 'display_name' => 'Activate Users', 'action' => 'activate'],
                    ['name' => 'user-management.users.deactivate', 'display_name' => 'Deactivate Users', 'action' => 'deactivate'],
                    ['name' => 'user-management.users.reset-password', 'display_name' => 'Reset User Passwords', 'action' => 'reset-password'],

                    ['name' => 'user-management.roles.view', 'display_name' => 'View Roles', 'action' => 'view'],
                    ['name' => 'user-management.roles.create', 'display_name' => 'Create Roles', 'action' => 'create'],
                    ['name' => 'user-management.roles.update', 'display_name' => 'Update Roles', 'action' => 'update'],
                    ['name' => 'user-management.roles.delete', 'display_name' => 'Delete Roles', 'action' => 'delete'],
                    ['name' => 'user-management.roles.assign-permissions', 'display_name' => 'Assign Permissions', 'action' => 'assign-permissions'],

                    ['name' => 'user-management.permissions.view', 'display_name' => 'View Permissions', 'action' => 'view'],

                    ['name' => 'user-management.organisation-units.view', 'display_name' => 'View Organisation Structure', 'action' => 'view'],
                    ['name' => 'user-management.organisation-units.create', 'display_name' => 'Create Organisation Units', 'action' => 'create'],
                    ['name' => 'user-management.organisation-units.update', 'display_name' => 'Update Organisation Units', 'action' => 'update'],
                    ['name' => 'user-management.organisation-units.delete', 'display_name' => 'Delete Organisation Units', 'action' => 'delete'],

                    ['name' => 'user-management.job-titles.view', 'display_name' => 'View Job Titles', 'action' => 'view'],
                    ['name' => 'user-management.job-titles.create', 'display_name' => 'Create Job Titles', 'action' => 'create'],
                    ['name' => 'user-management.job-titles.update', 'display_name' => 'Update Job Titles', 'action' => 'update'],

                    ['name' => 'user-management.grades.view', 'display_name' => 'View Grades', 'action' => 'view'],
                    ['name' => 'user-management.grades.create', 'display_name' => 'Create Grades', 'action' => 'create'],
                    ['name' => 'user-management.grades.update', 'display_name' => 'Update Grades', 'action' => 'update'],

                    ['name' => 'user-management.password-policies.view', 'display_name' => 'View Password Policy', 'action' => 'view'],
                    ['name' => 'user-management.password-policies.update', 'display_name' => 'Update Password Policy', 'action' => 'update'],
                ],
            ],

            [
                'code' => 'audit',
                'name' => 'Audit and Security',
                'display_order' => 3,
                'permissions' => [
                    ['name' => 'audit.audit-trails.view', 'display_name' => 'View Audit Trail', 'action' => 'view'],
                    ['name' => 'audit.audit-trails.export', 'display_name' => 'Export Audit Trail', 'action' => 'export'],
                    ['name' => 'audit.user-sessions.view', 'display_name' => 'View User Sessions', 'action' => 'view'],
                    ['name' => 'audit.user-sessions.terminate', 'display_name' => 'Terminate Sessions', 'action' => 'terminate'],
                    ['name' => 'audit.login-attempts.view', 'display_name' => 'View Login Attempts', 'action' => 'view'],
                ],
            ],
        ];

        foreach ($modules as $moduleData) {
            $permissions = $moduleData['permissions'];

            unset($moduleData['permissions']);

            $module = SystemModule::updateOrCreate(
                ['code' => $moduleData['code']],
                [
                    ...$moduleData,
                    'is_active' => true,
                    'show_in_sidebar' => true,
                ]
            );

            foreach ($permissions as $permissionData) {
                Permission::updateOrCreate(
                    [
                        'name' => $permissionData['name'],
                        'guard_name' => 'web',
                    ],
                    [
                        'system_module_id' => $module->id,
                        'display_name' => $permissionData['display_name'],
                        'action' => $permissionData['action'],
                        'is_active' => true,
                        'is_sensitive' => false,
                    ]
                );
            }
        }
    }
}
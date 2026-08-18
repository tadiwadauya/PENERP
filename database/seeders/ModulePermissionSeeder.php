<?php

namespace Database\Seeders;

use App\Models\UserManagement\SystemModule;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ModulePermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Clear Permission Cache
        |--------------------------------------------------------------------------
        */

        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();


        /*
        |--------------------------------------------------------------------------
        | System Modules and Permissions
        |--------------------------------------------------------------------------
        */

        $modules = [

            /*
            |--------------------------------------------------------------------------
            | Dashboards
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | User Management
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'user-management',
                'name' => 'User Management',
                'display_order' => 2,

                'permissions' => [

                    [
                        'name' => 'user-management.users.view',
                        'display_name' => 'View Users',
                        'action' => 'view',
                    ],

                    [
                        'name' => 'user-management.users.create',
                        'display_name' => 'Create Users',
                        'action' => 'create',
                    ],

                    [
                        'name' => 'user-management.users.update',
                        'display_name' => 'Update Users',
                        'action' => 'update',
                    ],

                    [
                        'name' => 'user-management.users.delete',
                        'display_name' => 'Delete Users',
                        'action' => 'delete',
                    ],

                    [
                        'name' => 'user-management.users.activate',
                        'display_name' => 'Activate Users',
                        'action' => 'activate',
                    ],

                    [
                        'name' => 'user-management.users.deactivate',
                        'display_name' => 'Deactivate Users',
                        'action' => 'deactivate',
                    ],

                    [
                        'name' => 'user-management.users.reset-password',
                        'display_name' => 'Reset User Passwords',
                        'action' => 'reset-password',
                    ],

                    [
                        'name' => 'user-management.roles.view',
                        'display_name' => 'View Roles',
                        'action' => 'view',
                    ],

                    [
                        'name' => 'user-management.roles.create',
                        'display_name' => 'Create Roles',
                        'action' => 'create',
                    ],

                    [
                        'name' => 'user-management.roles.update',
                        'display_name' => 'Update Roles',
                        'action' => 'update',
                    ],

                    [
                        'name' => 'user-management.roles.delete',
                        'display_name' => 'Delete Roles',
                        'action' => 'delete',
                    ],

                    [
                        'name' => 'user-management.roles.assign-permissions',
                        'display_name' => 'Assign Permissions',
                        'action' => 'assign-permissions',
                    ],

                    [
                        'name' => 'user-management.permissions.view',
                        'display_name' => 'View Permissions',
                        'action' => 'view',
                    ],

                    [
                        'name' => 'user-management.organisation-units.view',
                        'display_name' => 'View Organisation Structure',
                        'action' => 'view',
                    ],

                    [
                        'name' => 'user-management.organisation-units.create',
                        'display_name' => 'Create Organisation Units',
                        'action' => 'create',
                    ],

                    [
                        'name' => 'user-management.organisation-units.update',
                        'display_name' => 'Update Organisation Units',
                        'action' => 'update',
                    ],

                    [
                        'name' => 'user-management.organisation-units.delete',
                        'display_name' => 'Delete Organisation Units',
                        'action' => 'delete',
                    ],

                    [
                        'name' => 'user-management.job-titles.view',
                        'display_name' => 'View Job Titles',
                        'action' => 'view',
                    ],

                    [
                        'name' => 'user-management.job-titles.create',
                        'display_name' => 'Create Job Titles',
                        'action' => 'create',
                    ],

                    [
                        'name' => 'user-management.job-titles.update',
                        'display_name' => 'Update Job Titles',
                        'action' => 'update',
                    ],

                    [
                        'name' => 'user-management.grades.view',
                        'display_name' => 'View Grades',
                        'action' => 'view',
                    ],

                    [
                        'name' => 'user-management.grades.create',
                        'display_name' => 'Create Grades',
                        'action' => 'create',
                    ],

                    [
                        'name' => 'user-management.grades.update',
                        'display_name' => 'Update Grades',
                        'action' => 'update',
                    ],

                    [
                        'name' => 'user-management.password-policies.view',
                        'display_name' => 'View Password Policy',
                        'action' => 'view',
                    ],

                    [
                        'name' => 'user-management.password-policies.update',
                        'display_name' => 'Update Password Policy',
                        'action' => 'update',
                    ],
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | Audit & Security
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'audit',
                'name' => 'Audit and Security',
                'display_order' => 3,

                'permissions' => [

                    [
                        'name' => 'audit.audit-trails.view',
                        'display_name' => 'View Audit Trail',
                        'action' => 'view',
                    ],

                    [
                        'name' => 'audit.audit-trails.export',
                        'display_name' => 'Export Audit Trail',
                        'action' => 'export',
                    ],

                    [
                        'name' => 'audit.user-sessions.view',
                        'display_name' => 'View User Sessions',
                        'action' => 'view',
                    ],

                    [
                        'name' => 'audit.user-sessions.terminate',
                        'display_name' => 'Terminate Sessions',
                        'action' => 'terminate',
                    ],

                    [
                        'name' => 'audit.login-attempts.view',
                        'display_name' => 'View Login Attempts',
                        'action' => 'view',
                    ],
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | Pensions Administration - Updates
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'pensions-updates',
                'name' => 'Pensions Administration - Updates',
                'display_order' => 4,

                'permissions' => [

                    [
                        'name' => 'updates.dashboard.view',
                        'display_name' => 'View Updates Dashboard',
                        'action' => 'view',
                    ],

                    [
                        'name' => 'updates.employer-groups.view',
                        'display_name' => 'View Employer Groups',
                        'action' => 'view',
                    ],

                    [
                        'name' => 'updates.employer-groups.create',
                        'display_name' => 'Create Employer Groups',
                        'action' => 'create',
                    ],

                    [
                        'name' => 'updates.employer-groups.update',
                        'display_name' => 'Update Employer Groups',
                        'action' => 'update',
                    ],

                    [
                        'name' => 'updates.employer-groups.delete',
                        'display_name' => 'Delete Employer Groups',
                        'action' => 'delete',
                    ],

                    [
                        'name' => 'updates.employers.view',
                        'display_name' => 'View Employers',
                        'action' => 'view',
                    ],

                    [
                        'name' => 'updates.employers.create',
                        'display_name' => 'Create Employers',
                        'action' => 'create',
                    ],

                    [
                        'name' => 'updates.employers.update',
                        'display_name' => 'Update Employers',
                        'action' => 'update',
                    ],

                    [
                        'name' => 'updates.employers.delete',
                        'display_name' => 'Delete Employers',
                        'action' => 'delete',
                    ],

                    [
                        'name' => 'updates.employer-imports.view',
                        'display_name' => 'View Employer Imports',
                        'action' => 'view',
                    ],

                    [
                        'name' => 'updates.employer-imports.create',
                        'display_name' => 'Upload Employer Imports',
                        'action' => 'create',
                    ],

                    [
                        'name' => 'updates.employer-imports.review',
                        'display_name' => 'Review Employer Imports',
                        'action' => 'review',
                    ],

                    [
                        'name' => 'updates.employer-imports.approve',
                        'display_name' => 'Approve Employer Imports',
                        'action' => 'approve',
                    ],

                    [
                        'name' => 'updates.employer-imports.post',
                        'display_name' => 'Post Employer Imports',
                        'action' => 'post',
                    ],

                    [
                        'name' => 'updates.employer-imports.delete',
                        'display_name' => 'Cancel Employer Imports',
                        'action' => 'delete',
                    ],

                    [
                        'name' => 'updates.members.view',
                        'display_name' => 'View Members',
                        'action' => 'view',
                    ],

                    [
                        'name' => 'updates.members.create',
                        'display_name' => 'Create Members',
                        'action' => 'create',
                    ],

                    [
                        'name' => 'updates.members.update',
                        'display_name' => 'Update Members',
                        'action' => 'update',
                    ],

                    [
                        'name' => 'updates.members.delete',
                        'display_name' => 'Delete Members',
                        'action' => 'delete',
                    ],

                    [
                        'name' => 'updates.membership-imports.view',
                        'display_name' => 'View Membership Imports',
                        'action' => 'view',
                    ],

                    [
                        'name' => 'updates.membership-imports.create',
                        'display_name' => 'Upload Membership Imports',
                        'action' => 'create',
                    ],

                    [
                        'name' => 'updates.membership-imports.review',
                        'display_name' => 'Review Membership Imports',
                        'action' => 'review',
                    ],

                    [
                        'name' => 'updates.membership-imports.update',
                        'display_name' => 'Correct Membership Import Rows',
                        'action' => 'update',
                    ],

                    [
                        'name' => 'updates.membership-imports.approve',
                        'display_name' => 'Approve Membership Imports',
                        'action' => 'approve',
                    ],

                    [
                        'name' => 'updates.membership-imports.post',
                        'display_name' => 'Post Membership Imports',
                        'action' => 'post',
                    ],

                    [
                        'name' => 'updates.membership-imports.delete',
                        'display_name' => 'Cancel Membership Imports',
                        'action' => 'delete',
                    ],

                    [
                        'name' => 'updates.member-movements.view',
                        'display_name' => 'View Member Movements',
                        'action' => 'view',
                    ],

                    [
                        'name' => 'updates.member-movements.create',
                        'display_name' => 'Create Member Movements',
                        'action' => 'create',
                    ],

                    [
                        'name' => 'updates.member-movements.update',
                        'display_name' => 'Update Member Movements',
                        'action' => 'update',
                    ],

                    [
                        'name' => 'updates.reports.membership.view',
                        'display_name' => 'View Membership Reports',
                        'action' => 'view',
                    ],

                    [
                        'name' => 'updates.reports.membership.export',
                        'display_name' => 'Export Membership Reports',
                        'action' => 'export',
                    ],
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | Pensions Administration - Contributions
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'pensions-contributions',
                'name' => 'Pensions Administration - Contributions',
                'display_order' => 5,

                'permissions' => [

                    [
                        'name' => 'contributions.monthly-imports.view',
                        'display_name' => 'View Monthly Contribution Imports',
                        'action' => 'view',
                    ],

                    [
                        'name' => 'contributions.monthly-imports.create',
                        'display_name' => 'Upload Monthly Contributions',
                        'action' => 'create',
                    ],

                    [
                        'name' => 'contributions.monthly-imports.update',
                        'display_name' => 'Update Monthly Contribution Imports',
                        'action' => 'update',
                    ],

                    [
                        'name' => 'contributions.monthly-imports.delete',
                        'display_name' => 'Cancel Monthly Contribution Imports',
                        'action' => 'delete',
                    ],

                    [
                        'name' => 'contributions.monthly-imports.approve',
                        'display_name' => 'Approve Monthly Contributions',
                        'action' => 'approve',
                    ],

                    [
                        'name' => 'contributions.monthly-imports.reject',
                        'display_name' => 'Reject Monthly Contributions',
                        'action' => 'reject',
                    ],

                    [
                        'name' => 'contributions.monthly-imports.post',
                        'display_name' => 'Post Monthly Contributions',
                        'action' => 'post',
                    ],

                    [
                        'name' => 'contributions.reports.view',
                        'display_name' => 'View Contribution Reports',
                        'action' => 'view',
                    ],
                ],
            ],
        ];


        /*
        |--------------------------------------------------------------------------
        | Create / Update Modules and Permissions
        |--------------------------------------------------------------------------
        */

        foreach (
            $modules
            as $moduleData
        ) {
            $permissions =
                $moduleData[
                    'permissions'
                ];


            unset(
                $moduleData[
                    'permissions'
                ]
            );


            $module =
                SystemModule::updateOrCreate(
                    [
                        'code' =>
                            $moduleData[
                                'code'
                            ],
                    ],
                    [
                        'name' =>
                            $moduleData[
                                'name'
                            ],

                        'display_order' =>
                            $moduleData[
                                'display_order'
                            ],

                        'is_active' =>
                            true,

                        'show_in_sidebar' =>
                            true,
                    ]
                );


            foreach (
                $permissions
                as $permissionData
            ) {
                Permission::updateOrCreate(
                    [
                        'name' =>
                            $permissionData[
                                'name'
                            ],

                        'guard_name' =>
                            'web',
                    ],
                    [
                        'system_module_id' =>
                            $module->id,

                        'display_name' =>
                            $permissionData[
                                'display_name'
                            ],

                        'action' =>
                            $permissionData[
                                'action'
                            ],

                        'is_active' =>
                            true,

                        'is_sensitive' =>
                            $this->isSensitivePermission(
                                $permissionData[
                                    'action'
                                ]
                            ),
                    ]
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Clear Permission Cache
        |--------------------------------------------------------------------------
        */

        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();
    }


    /*
    |--------------------------------------------------------------------------
    | Sensitive Permissions
    |--------------------------------------------------------------------------
    */

    private function isSensitivePermission(
        string $action
    ): bool {
        return in_array(
            $action,
            [
                'delete',
                'approve',
                'reject',
                'post',
                'terminate',
                'reset-password',
                'assign-permissions',
            ],
            true
        );
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
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
        | SYSTEM ADMINISTRATOR
        |--------------------------------------------------------------------------
        */

        $systemAdministrator = Role::updateOrCreate(
            [
                'name' => 'system-administrator',
                'guard_name' => 'web',
            ],
            [
                'display_name' =>
                    'System Administrator',

                'description' =>
                    'ICT system administration role.',

                'is_system_role' =>
                    true,

                'is_active' =>
                    true,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | System Administrator Permissions
        |--------------------------------------------------------------------------
        |
        | System Administrator is mainly responsible for system administration,
        | security, users, roles, permissions and audit functions.
        |
        | Business permissions are not automatically assigned here.
        |
        */

        $adminPermissions =
            Permission::query()
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    function ($query): void {

                        $query
                            ->where(
                                'name',
                                'like',
                                'user-management.%'
                            )
                            ->orWhere(
                                'name',
                                'like',
                                'audit.%'
                            )
                            ->orWhere(
                                'name',
                                'dashboard.system-administration.view'
                            );
                    }
                )
                ->get();

        $systemAdministrator->syncPermissions(
            $adminPermissions
        );


        /*
        |--------------------------------------------------------------------------
        | UPDATES OFFICER
        |--------------------------------------------------------------------------
        */

        $updatesOfficer = Role::updateOrCreate(
            [
                'name' =>
                    'updates-officer',

                'guard_name' =>
                    'web',
            ],
            [
                'display_name' =>
                    'Updates Officer',

                'description' =>
                    'Processes member, employer and membership updates within the Pensions Administration module.',

                'is_system_role' =>
                    false,

                'is_active' =>
                    true,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Updates Officer Permissions
        |--------------------------------------------------------------------------
        |
        | Operational role.
        |
        | Can:
        | - View Updates dashboard
        | - View/create/update employers
        | - View/create/update employer groups
        | - Upload and review employer imports
        | - View/create/update members
        | - Upload/review/correct membership imports
        | - Create member movements
        | - View membership reports
        |
        | Cannot:
        | - Delete master data
        | - Approve imports
        | - Post imports
        |
        */

        $updatesOfficerPermissions = [
            /*
            | Dashboard
            */
            'dashboard.pensions.view',
            'updates.dashboard.view',

            /*
            | Employer Groups
            */
            'updates.employer-groups.view',
            'updates.employer-groups.create',
            'updates.employer-groups.update',

            /*
            | Employers
            */
            'updates.employers.view',
            'updates.employers.create',
            'updates.employers.update',

            /*
            | Employer Imports
            */
            'updates.employer-imports.view',
            'updates.employer-imports.create',
            'updates.employer-imports.review',

            /*
            | Members
            */
            'updates.members.view',
            'updates.members.create',
            'updates.members.update',

            /*
            | Membership Imports
            */
            'updates.membership-imports.view',
            'updates.membership-imports.create',
            'updates.membership-imports.review',
            'updates.membership-imports.update',

            /*
            | Member Movements
            */
            'updates.member-movements.view',
            'updates.member-movements.create',
            'updates.member-movements.update',

            /*
            | Reports
            */
            'updates.reports.membership.view',
            'updates.reports.membership.export',
        ];

        $updatesOfficer->syncPermissions(
            Permission::query()
                ->whereIn(
                    'name',
                    $updatesOfficerPermissions
                )
                ->get()
        );


        /*
        |--------------------------------------------------------------------------
        | UPDATES SUPERVISOR
        |--------------------------------------------------------------------------
        */

        $updatesSupervisor = Role::updateOrCreate(
            [
                'name' =>
                    'updates-supervisor',

                'guard_name' =>
                    'web',
            ],
            [
                'display_name' =>
                    'Updates Supervisor',

                'description' =>
                    'Supervises member, employer and membership updates and approves import processing.',

                'is_system_role' =>
                    false,

                'is_active' =>
                    true,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Updates Supervisor Permissions
        |--------------------------------------------------------------------------
        |
        | Supervisor has full Updates permissions including approval and posting.
        |
        */

        $updatesSupervisorPermissions = [
            /*
            | Dashboard
            */
            'dashboard.pensions.view',
            'updates.dashboard.view',

            /*
            | Employer Groups
            */
            'updates.employer-groups.view',
            'updates.employer-groups.create',
            'updates.employer-groups.update',
            'updates.employer-groups.delete',

            /*
            | Employers
            */
            'updates.employers.view',
            'updates.employers.create',
            'updates.employers.update',
            'updates.employers.delete',

            /*
            | Employer Imports
            */
            'updates.employer-imports.view',
            'updates.employer-imports.create',
            'updates.employer-imports.review',
            'updates.employer-imports.approve',
            'updates.employer-imports.post',
            'updates.employer-imports.delete',

            /*
            | Members
            */
            'updates.members.view',
            'updates.members.create',
            'updates.members.update',
            'updates.members.delete',

            /*
            | Membership Imports
            */
            'updates.membership-imports.view',
            'updates.membership-imports.create',
            'updates.membership-imports.review',
            'updates.membership-imports.update',
            'updates.membership-imports.approve',
            'updates.membership-imports.post',
            'updates.membership-imports.delete',

            /*
            | Member Movements
            */
            'updates.member-movements.view',
            'updates.member-movements.create',
            'updates.member-movements.update',

            /*
            | Reports
            */
            'updates.reports.membership.view',
            'updates.reports.membership.export',

            /*
            | Contribution Enquiry
            */
            'contributions.monthly-imports.view',
            'contributions.reports.view',
        ];

        $updatesSupervisor->syncPermissions(
            Permission::query()
                ->whereIn(
                    'name',
                    $updatesSupervisorPermissions
                )
                ->get()
        );


        /*
        |--------------------------------------------------------------------------
        | CONTRIBUTIONS OFFICER
        |--------------------------------------------------------------------------
        */

        $contributionsOfficer = Role::updateOrCreate(
            [
                'name' =>
                    'contributions-officer',

                'guard_name' =>
                    'web',
            ],
            [
                'display_name' =>
                    'Contributions Officer',

                'description' =>
                    'Uploads, validates and reviews monthly expected member contribution schedules.',

                'is_system_role' =>
                    false,

                'is_active' =>
                    true,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Contributions Officer Permissions
        |--------------------------------------------------------------------------
        |
        | The officer prepares and reviews contribution imports.
        |
        | Approval and posting are deliberately excluded.
        |
        */

        $contributionsOfficerPermissions = [
            /*
            | Dashboard
            */
            'dashboard.pensions.view',

            /*
            | Monthly Contributions
            */
            'contributions.monthly-imports.view',
            'contributions.monthly-imports.create',
            'contributions.monthly-imports.update',

            /*
            | Reports
            */
            'contributions.reports.view',

            /*
            | Required Member / Employer Enquiry
            */
            'updates.dashboard.view',
            'updates.employers.view',
            'updates.members.view',
        ];

        $contributionsOfficer->syncPermissions(
            Permission::query()
                ->whereIn(
                    'name',
                    $contributionsOfficerPermissions
                )
                ->get()
        );


        /*
        |--------------------------------------------------------------------------
        | CONTRIBUTIONS SUPERVISOR
        |--------------------------------------------------------------------------
        */

        $contributionsSupervisor = Role::updateOrCreate(
            [
                'name' =>
                    'contributions-supervisor',

                'guard_name' =>
                    'web',
            ],
            [
                'display_name' =>
                    'Contributions Supervisor',

                'description' =>
                    'Reviews, approves and posts monthly expected member contributions.',

                'is_system_role' =>
                    false,

                'is_active' =>
                    true,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Contributions Supervisor Permissions
        |--------------------------------------------------------------------------
        */

        $contributionsSupervisorPermissions = [
            /*
            | Dashboard
            */
            'dashboard.pensions.view',

            /*
            | Monthly Contributions
            */
            'contributions.monthly-imports.view',
            'contributions.monthly-imports.create',
            'contributions.monthly-imports.update',
            'contributions.monthly-imports.delete',
            'contributions.monthly-imports.approve',
            'contributions.monthly-imports.post',

            /*
            | Reports
            */
            'contributions.reports.view',

            /*
            | Required Updates Enquiry
            */
            'updates.dashboard.view',
            'updates.employers.view',
            'updates.members.view',
            'updates.reports.membership.view',
        ];

        $contributionsSupervisor->syncPermissions(
            Permission::query()
                ->whereIn(
                    'name',
                    $contributionsSupervisorPermissions
                )
                ->get()
        );


        /*
        |--------------------------------------------------------------------------
        | Clear Permission Cache Again
        |--------------------------------------------------------------------------
        */

        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();
    }
}
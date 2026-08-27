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

        $systemAdministrator =
            Role::updateOrCreate(
                [
                    'name' =>
                        'system-administrator',

                    'guard_name' =>
                        'web',
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
        | All active contribution permissions are included automatically.
        |--------------------------------------------------------------------------
        */

        $adminPermissions =
            Permission::query()
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    function (
                        $query
                    ): void {

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
                            )

                            ->orWhere(
                                'name',
                                'dashboard.pensions.view'
                            )

                            ->orWhere(
                                'name',
                                'like',
                                'contributions.%'
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

        $updatesOfficer =
            Role::updateOrCreate(
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


        $updatesOfficerPermissions = [
            'dashboard.pensions.view',
            'updates.dashboard.view',

            'updates.employer-groups.view',
            'updates.employer-groups.create',
            'updates.employer-groups.update',

            'updates.employers.view',
            'updates.employers.create',
            'updates.employers.update',

            'updates.employer-imports.view',
            'updates.employer-imports.create',
            'updates.employer-imports.review',

            'updates.members.view',
            'updates.members.create',
            'updates.members.update',

            'updates.membership-imports.view',
            'updates.membership-imports.create',
            'updates.membership-imports.review',
            'updates.membership-imports.update',

            'updates.member-movements.view',
            'updates.member-movements.create',
            'updates.member-movements.update',

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

        $updatesSupervisor =
            Role::updateOrCreate(
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
                        'Supervises member, employer and membership updates.',

                    'is_system_role' =>
                        false,

                    'is_active' =>
                        true,
                ]
            );


        $updatesSupervisorPermissions = [
            'dashboard.pensions.view',
            'updates.dashboard.view',

            'updates.employer-groups.view',
            'updates.employer-groups.create',
            'updates.employer-groups.update',
            'updates.employer-groups.delete',

            'updates.employers.view',
            'updates.employers.create',
            'updates.employers.update',
            'updates.employers.delete',

            'updates.employer-imports.view',
            'updates.employer-imports.create',
            'updates.employer-imports.review',
            'updates.employer-imports.approve',
            'updates.employer-imports.post',
            'updates.employer-imports.delete',

            'updates.members.view',
            'updates.members.create',
            'updates.members.update',
            'updates.members.delete',

            'updates.membership-imports.view',
            'updates.membership-imports.create',
            'updates.membership-imports.review',
            'updates.membership-imports.update',
            'updates.membership-imports.approve',
            'updates.membership-imports.post',
            'updates.membership-imports.delete',

            'updates.member-movements.view',
            'updates.member-movements.create',
            'updates.member-movements.update',

            'updates.reports.membership.view',
            'updates.reports.membership.export',

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
        |
        | Can upload and review expected contributions and actual receipts.
        | Cannot post receipts.
        |--------------------------------------------------------------------------
        */

        $contributionsOfficer =
            Role::updateOrCreate(
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
                        'Uploads, validates and reviews monthly expected contribution schedules and employer receipt files.',

                    'is_system_role' =>
                        false,

                    'is_active' =>
                        true,
                ]
            );


        $contributionsOfficerPermissions = [
            'dashboard.pensions.view',


            /*
            |--------------------------------------------------------------------------
            | Expected Contributions
            |--------------------------------------------------------------------------
            */

            'contributions.monthly-imports.view',
            'contributions.monthly-imports.create',
            'contributions.monthly-imports.update',
            'contributions.monthly-imports.delete',


            /*
            |--------------------------------------------------------------------------
            | Employer Receipts
            |--------------------------------------------------------------------------
            */

            'contributions.receipts.view',
            'contributions.receipts.create',


            /*
            |--------------------------------------------------------------------------
            | Exchange Rates
            |--------------------------------------------------------------------------
            */

            'contributions.exchange-rates.view',


            /*
            |--------------------------------------------------------------------------
            | Reports
            |--------------------------------------------------------------------------
            */

            'contributions.reports.view',


            /*
            |--------------------------------------------------------------------------
            | Supporting Updates Information
            |--------------------------------------------------------------------------
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
        |
        | Full day-to-day receipt access including posting and exchange rates.
        |--------------------------------------------------------------------------
        */

        $contributionsSupervisor =
            Role::updateOrCreate(
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
                        'Supervises monthly contribution processing, employer receipts and exchange rates.',

                    'is_system_role' =>
                        false,

                    'is_active' =>
                        true,
                ]
            );


        $contributionsSupervisorPermissions = [
            'dashboard.pensions.view',


            /*
            |--------------------------------------------------------------------------
            | Expected Contributions
            |--------------------------------------------------------------------------
            */

            'contributions.monthly-imports.view',
            'contributions.monthly-imports.create',
            'contributions.monthly-imports.update',
            'contributions.monthly-imports.delete',


            /*
            |--------------------------------------------------------------------------
            | Employer Receipts
            |--------------------------------------------------------------------------
            */

            'contributions.receipts.view',
            'contributions.receipts.create',
            'contributions.receipts.post',


            /*
            |--------------------------------------------------------------------------
            | Exchange Rates
            |--------------------------------------------------------------------------
            */

            'contributions.exchange-rates.view',
            'contributions.exchange-rates.manage',


            /*
            |--------------------------------------------------------------------------
            | Reports
            |--------------------------------------------------------------------------
            */

            'contributions.reports.view',


            /*
            |--------------------------------------------------------------------------
            | Supporting Updates Information
            |--------------------------------------------------------------------------
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
        | CONTRIBUTIONS APPROVER
        |--------------------------------------------------------------------------
        |
        | This role remains specifically for EXPECTED monthly contributions.
        |
        | Receipts do not require approval.
        |--------------------------------------------------------------------------
        */

        $contributionsApprover =
            Role::updateOrCreate(
                [
                    'name' =>
                        'contributions-approver',

                    'guard_name' =>
                        'web',
                ],
                [
                    'display_name' =>
                        'Contributions Approver',

                    'description' =>
                        'Authorised to approve or reject monthly expected contribution batches.',

                    'is_system_role' =>
                        false,

                    'is_active' =>
                        true,
                ]
            );


        $contributionsApproverPermissions = [
            'dashboard.pensions.view',

            'contributions.monthly-imports.view',
            'contributions.monthly-imports.approve',
            'contributions.monthly-imports.reject',

            'contributions.reports.view',

            'updates.employers.view',
            'updates.members.view',
        ];


        $contributionsApprover->syncPermissions(
            Permission::query()
                ->whereIn(
                    'name',
                    $contributionsApproverPermissions
                )
                ->get()
        );


        /*
        |--------------------------------------------------------------------------
        | CONTRIBUTIONS POSTER
        |--------------------------------------------------------------------------
        |
        | Can permanently post expected contributions and actual receipts.
        |--------------------------------------------------------------------------
        */

        $contributionsPoster =
            Role::updateOrCreate(
                [
                    'name' =>
                        'contributions-poster',

                    'guard_name' =>
                        'web',
                ],
                [
                    'display_name' =>
                        'Contributions Poster',

                    'description' =>
                        'Authorised to permanently post expected contributions and validated employer contribution receipts.',

                    'is_system_role' =>
                        false,

                    'is_active' =>
                        true,
                ]
            );


        $contributionsPosterPermissions = [
            'dashboard.pensions.view',


            /*
            |--------------------------------------------------------------------------
            | Expected Contributions
            |--------------------------------------------------------------------------
            */

            'contributions.monthly-imports.view',
            'contributions.monthly-imports.post',


            /*
            |--------------------------------------------------------------------------
            | Employer Receipts
            |--------------------------------------------------------------------------
            */

            'contributions.receipts.view',
            'contributions.receipts.post',


            /*
            |--------------------------------------------------------------------------
            | Exchange Rates
            |--------------------------------------------------------------------------
            */

            'contributions.exchange-rates.view',


            /*
            |--------------------------------------------------------------------------
            | Reports
            |--------------------------------------------------------------------------
            */

            'contributions.reports.view',


            /*
            |--------------------------------------------------------------------------
            | Supporting Information
            |--------------------------------------------------------------------------
            */

            'updates.employers.view',
            'updates.members.view',
        ];


        $contributionsPoster->syncPermissions(
            Permission::query()
                ->whereIn(
                    'name',
                    $contributionsPosterPermissions
                )
                ->get()
        );


        /*
        |--------------------------------------------------------------------------
        | Clear Permission Cache
        |--------------------------------------------------------------------------
        */

        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();
    }
}
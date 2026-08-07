<?php

namespace Database\Seeders;

use App\Models\UserManagement\Dashboard;
use App\Models\UserManagement\OrganisationUnit;
use Illuminate\Database\Seeder;

class OrganisationStructureSeeder extends Seeder
{
    public function run(): void
    {
        $principalDashboard = Dashboard::where(
            'code',
            'principal_office'
        )->firstOrFail();

        $financeDashboard = Dashboard::where(
            'code',
            'finance'
        )->firstOrFail();

        $pensionsDashboard = Dashboard::where(
            'code',
            'pensions'
        )->firstOrFail();

        $propertyDashboard = Dashboard::where(
            'code',
            'property'
        )->firstOrFail();

        $lapf = OrganisationUnit::updateOrCreate(
            ['code' => 'LAPF'],
            [
                'name' => 'Local Authorities Pension Fund',
                'unit_type' => 'organisation',
                'parent_id' => null,
                'dashboard_id' => $principalDashboard->id,
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        $principalOffice = OrganisationUnit::updateOrCreate(
            ['code' => 'PO'],
            [
                'name' => "Principal Officer's Office",
                'unit_type' => 'office',
                'parent_id' => $lapf->id,
                'dashboard_id' => $principalDashboard->id,
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        OrganisationUnit::updateOrCreate(
            ['code' => 'HR'],
            [
                'name' => 'Human Resources',
                'unit_type' => 'section',
                'parent_id' => $principalOffice->id,
                'dashboard_id' => $principalDashboard->id,
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        OrganisationUnit::updateOrCreate(
            ['code' => 'PROC'],
            [
                'name' => 'Procurement',
                'unit_type' => 'section',
                'parent_id' => $principalOffice->id,
                'dashboard_id' => $principalDashboard->id,
                'display_order' => 2,
                'is_active' => true,
            ]
        );

        $finance = OrganisationUnit::updateOrCreate(
            ['code' => 'FIN'],
            [
                'name' => 'Finance Department',
                'unit_type' => 'department',
                'parent_id' => $principalOffice->id,
                'dashboard_id' => $financeDashboard->id,
                'display_order' => 3,
                'is_active' => true,
            ]
        );

        $pensions = OrganisationUnit::updateOrCreate(
            ['code' => 'PEN'],
            [
                'name' => 'Pensions Administration Department',
                'unit_type' => 'department',
                'parent_id' => $principalOffice->id,
                'dashboard_id' => $pensionsDashboard->id,
                'display_order' => 4,
                'is_active' => true,
            ]
        );

        OrganisationUnit::updateOrCreate(
            ['code' => 'PROP'],
            [
                'name' => 'Property Department',
                'unit_type' => 'department',
                'parent_id' => $principalOffice->id,
                'dashboard_id' => $propertyDashboard->id,
                'display_order' => 5,
                'is_active' => true,
            ]
        );

        /*
         * ICT is temporarily configured to report directly to the
         * Principal Officer's Office. The parent_id can later be
         * changed to $finance->id without changing application code.
         */
        OrganisationUnit::updateOrCreate(
            ['code' => 'ICT'],
            [
                'name' => 'ICT Section',
                'unit_type' => 'section',
                'parent_id' => $principalOffice->id,
                'dashboard_id' => $financeDashboard->id,
                'display_order' => 6,
                'is_active' => true,
            ]
        );

        OrganisationUnit::updateOrCreate(
            ['code' => 'UPDATES'],
            [
                'name' => 'Updates Section',
                'unit_type' => 'section',
                'parent_id' => $pensions->id,
                'dashboard_id' => $pensionsDashboard->id,
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        OrganisationUnit::updateOrCreate(
            ['code' => 'CLAIMS'],
            [
                'name' => 'Benefit Claims Section',
                'unit_type' => 'section',
                'parent_id' => $pensions->id,
                'dashboard_id' => $pensionsDashboard->id,
                'display_order' => 2,
                'is_active' => true,
            ]
        );

        OrganisationUnit::updateOrCreate(
            ['code' => 'PAYROLL'],
            [
                'name' => 'Payroll Section',
                'unit_type' => 'section',
                'parent_id' => $pensions->id,
                'dashboard_id' => $pensionsDashboard->id,
                'display_order' => 3,
                'is_active' => true,
            ]
        );
    }
}
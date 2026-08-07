<?php

namespace Database\Seeders;

use App\Models\UserManagement\Dashboard;
use Illuminate\Database\Seeder;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        $dashboards = [
            [
                'code' => 'finance',
                'name' => 'Finance Dashboard',
                'route_name' => 'dashboard.finance',
                'icon' => 'bi bi-cash-stack',
                'display_order' => 1,
            ],
            [
                'code' => 'pensions',
                'name' => 'Pensions Administration Dashboard',
                'route_name' => 'dashboard.pensions',
                'icon' => 'bi bi-people',
                'display_order' => 2,
            ],
            [
                'code' => 'property',
                'name' => 'Property Dashboard',
                'route_name' => 'dashboard.property',
                'icon' => 'bi bi-buildings',
                'display_order' => 3,
            ],
            [
                'code' => 'principal_office',
                'name' => "Principal Officer's Dashboard",
                'route_name' => 'dashboard.principal-office',
                'icon' => 'bi bi-briefcase',
                'display_order' => 4,
            ],
            [
                'code' => 'system_administration',
                'name' => 'System Administration Dashboard',
                'route_name' => 'dashboard.system-administration',
                'icon' => 'bi bi-gear',
                'display_order' => 5,
            ],
        ];

        foreach ($dashboards as $dashboard) {
            Dashboard::updateOrCreate(
                ['code' => $dashboard['code']],
                [
                    ...$dashboard,
                    'is_active' => true,
                ]
            );
        }
    }
}
<?php

namespace Database\Seeders;

use App\Models\UserManagement\Dashboard;
use App\Models\UserManagement\Grade;
use App\Models\UserManagement\JobTitle;
use App\Models\UserManagement\OrganisationUnit;
use App\Models\UserManagement\PasswordPolicy;
use App\Models\UserManagement\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SystemAdministratorSeeder extends Seeder
{
    public function run(): void
    {
        $ict = OrganisationUnit::where(
            'code',
            'ICT'
        )->firstOrFail();

        $jobTitle = JobTitle::where(
            'code',
            'ICTO'
        )->firstOrFail();

        $grade = Grade::where(
            'code',
            'HOS'
        )->first();

        $policy = PasswordPolicy::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->firstOrFail();

        /*
         * DEVELOPMENT INITIAL PASSWORD ONLY.
         *
         * Change this before production.
         */
        $temporaryPassword =
            'Admin1@makanakamwari';

        $user = User::updateOrCreate(
            [
                'username' => 'tadiwa',
            ],
            [
                'employee_number' => 'SYS001',
                'title' => null,
                'first_name' => 'Tadiwanashe',
                'middle_name' => null,
                'surname' => 'Dauya',

                'email' =>
                    'tadiwa@lapf.local',

                'work_email' =>
                    null,

                'organisation_unit_id' =>
                    $ict->id,

                'job_title_id' =>
                    $jobTitle->id,

                'grade_id' =>
                    $grade?->id,

                'reports_to_user_id' =>
                    null,

                'employment_status' =>
                    'active',

                'account_status' =>
                    'active',

                'password' =>
                    Hash::make(
                        $temporaryPassword
                    ),

                'must_change_password' =>
                    true,

                'temporary_password' =>
                    true,

                'password_changed_at' =>
                    now(),

                'password_expires_at' =>
                    now()->addHours((int) $policy->temporary_password_expiry_hours),

                'failed_login_attempts' =>
                    0,

                'is_system_administrator' =>
                    true,

                'is_active' =>
                    true,
            ]
        );

        $user->syncRoles([
            'system-administrator',
        ]);

        $dashboard = Dashboard::where(
            'code',
            'system_administration'
        )->firstOrFail();

        $user->dashboards()->sync([
            $dashboard->id => [
                'is_default' => true,
                'assigned_by' => $user->id,
            ],
        ]);
    }
}
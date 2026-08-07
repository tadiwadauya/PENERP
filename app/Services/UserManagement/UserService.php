<?php

namespace App\Services\UserManagement;

use App\Models\UserManagement\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private readonly PasswordService $passwordService,
        private readonly AuditService $auditService
    ) {
    }

    public function create(
        array $data,
        User $administrator
    ): array {
        return DB::transaction(function () use (
            $data,
            $administrator
        ): array {
            $passwordOption =
                $data['password_option'];

            $temporaryPassword =
                $passwordOption === 'manual'
                    ? $data['temporary_password']
                    : $this->passwordService
                        ->generateTemporaryPassword();

            $temporaryUser = new User([
                'first_name' =>
                    $data['first_name'],
                'surname' =>
                    $data['surname'],
                'username' =>
                    $data['username'],
            ]);

            /*
             * Validate manual temporary passwords
             * against the LAPF policy.
             */
            if ($passwordOption === 'manual') {
                $this->passwordService
                    ->validatePassword(
                        $temporaryUser,
                        $temporaryPassword
                    );
            }

            $policy = $this->passwordService
                ->getActivePolicy();

            $user = User::create([
                'employee_number' =>
                    $data['employee_number'],

                'title' =>
                    $data['title'] ?? null,

                'first_name' =>
                    $data['first_name'],

                'middle_name' =>
                    $data['middle_name'] ?? null,

                'surname' =>
                    $data['surname'],

                'username' =>
                    $data['username'],

                'email' =>
                    $data['email'],

                'work_email' =>
                    $data['work_email'] ?? null,

                'cell_number' =>
                    $data['cell_number'] ?? null,

                'telephone_number' =>
                    $data['telephone_number'] ?? null,

                'phone_extension' =>
                    $data['phone_extension'] ?? null,

                'organisation_unit_id' =>
                    $data['organisation_unit_id'],

                'job_title_id' =>
                    $data['job_title_id'],

                'grade_id' =>
                    $data['grade_id'] ?? null,

                'reports_to_user_id' =>
                    $data['reports_to_user_id']
                    ?? null,

                'employment_date' =>
                    $data['employment_date']
                    ?? null,

                'employment_status' =>
                    'active',

                'account_status' =>
                    'active',

                'password' => Hash::make(
                    $temporaryPassword
                ),

                'must_change_password' =>
                    true,

                'temporary_password' =>
                    true,

                'password_changed_at' =>
                    now(),

                'password_expires_at' =>
                    now()->addHours(
                        $policy
                            ->temporary_password_expiry_hours
                    ),

                'is_system_administrator' =>
                    false,

                'is_active' =>
                    true,

                'created_by' =>
                    $administrator->id,

                'updated_by' =>
                    $administrator->id,
            ]);

            $user->syncRoles(
                $data['roles']
            );

            $this->syncDashboards(
                $user,
                $data['dashboard_ids'],
                (int) $data['default_dashboard_id'],
                $administrator
            );

            $this->auditService->record(
                eventType: 'user_created',
                module: 'user-management',
                action: 'create',
                description:
                    "User {$user->employee_number} created.",
                subject: $user,
                newValues: $user->only([
                    'employee_number',
                    'first_name',
                    'surname',
                    'username',
                    'email',
                    'organisation_unit_id',
                    'job_title_id',
                    'grade_id',
                    'reports_to_user_id',
                    'account_status',
                ]),
                metadata: [
                    'roles' => $data['roles'],
                    'dashboard_ids' =>
                        $data['dashboard_ids'],
                ]
            );

            return [
                'user' => $user,
                'temporary_password' =>
                    $temporaryPassword,
            ];
        });
    }

    public function update(
        User $user,
        array $data,
        User $administrator
    ): User {
        return DB::transaction(function () use (
            $user,
            $data,
            $administrator
        ): User {
            $oldValues = $user->only([
                'employee_number',
                'first_name',
                'middle_name',
                'surname',
                'username',
                'email',
                'work_email',
                'cell_number',
                'telephone_number',
                'phone_extension',
                'organisation_unit_id',
                'job_title_id',
                'grade_id',
                'reports_to_user_id',
                'employment_status',
            ]);

            $oldRoles = $user
                ->roles
                ->pluck('name')
                ->values()
                ->all();

            $user->update([
                'employee_number' =>
                    $data['employee_number'],

                'title' =>
                    $data['title'] ?? null,

                'first_name' =>
                    $data['first_name'],

                'middle_name' =>
                    $data['middle_name'] ?? null,

                'surname' =>
                    $data['surname'],

                'username' =>
                    $data['username'],

                'email' =>
                    $data['email'],

                'work_email' =>
                    $data['work_email'] ?? null,

                'cell_number' =>
                    $data['cell_number'] ?? null,

                'telephone_number' =>
                    $data['telephone_number'] ?? null,

                'phone_extension' =>
                    $data['phone_extension'] ?? null,

                'organisation_unit_id' =>
                    $data['organisation_unit_id'],

                'job_title_id' =>
                    $data['job_title_id'],

                'grade_id' =>
                    $data['grade_id'] ?? null,

                'reports_to_user_id' =>
                    $data['reports_to_user_id']
                    ?? null,

                'employment_date' =>
                    $data['employment_date']
                    ?? null,

                'employment_status' =>
                    $data['employment_status'],

                'updated_by' =>
                    $administrator->id,
            ]);

            $user->syncRoles(
                $data['roles']
            );

            $this->syncDashboards(
                $user,
                $data['dashboard_ids'],
                (int) $data['default_dashboard_id'],
                $administrator
            );

            $this->auditService->record(
                eventType: 'user_updated',
                module: 'user-management',
                action: 'update',
                description:
                    "User {$user->employee_number} updated.",
                subject: $user,
                oldValues: $oldValues,
                newValues: $user->fresh()->only([
                    'employee_number',
                    'first_name',
                    'middle_name',
                    'surname',
                    'username',
                    'email',
                    'work_email',
                    'cell_number',
                    'telephone_number',
                    'phone_extension',
                    'organisation_unit_id',
                    'job_title_id',
                    'grade_id',
                    'reports_to_user_id',
                    'employment_status',
                ]),
                metadata: [
                    'old_roles' =>
                        $oldRoles,
                    'new_roles' =>
                        $data['roles'],
                ]
            );

            return $user->fresh();
        });
    }

    private function syncDashboards(
        User $user,
        array $dashboardIds,
        int $defaultDashboardId,
        User $administrator
    ): void {
        $syncData = [];

        foreach ($dashboardIds as $dashboardId) {
            $dashboardId = (int) $dashboardId;

            $syncData[$dashboardId] = [
                'is_default' =>
                    $dashboardId
                    === $defaultDashboardId,

                'assigned_by' =>
                    $administrator->id,
            ];
        }

        $user->dashboards()->sync(
            $syncData
        );
    }
}
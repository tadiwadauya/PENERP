<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserManagement\StoreUserRequest;
use App\Http\Requests\UserManagement\UpdateUserRequest;
use App\Models\UserManagement\Dashboard;
use App\Models\UserManagement\Grade;
use App\Models\UserManagement\JobTitle;
use App\Models\UserManagement\OrganisationUnit;
use App\Models\UserManagement\User;
use App\Services\Audit\AuditService;
use App\Services\UserManagement\PasswordService;
use App\Services\UserManagement\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly PasswordService $passwordService,
        private readonly AuditService $auditService
    ) {
    }

    public function index(Request $request): View
    {
        $query = User::query()
            ->with([
                'organisationUnit',
                'jobTitle',
                'grade',
                'roles',
            ]);

        if ($request->filled('search')) {
            $search = trim(
                $request->string('search')->toString()
            );

            $query->where(function ($query) use ($search) {
                $query
                    ->where(
                        'employee_number',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'first_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'surname',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'username',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'email',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        if (
            $request->filled('organisation_unit_id')
        ) {
            $query->where(
                'organisation_unit_id',
                $request->integer(
                    'organisation_unit_id'
                )
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'account_status',
                $request->string('status')
            );
        }

        $users = $query
            ->orderBy('surname')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        $organisationUnits =
            OrganisationUnit::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

        return view(
            'user-management.users.index',
            compact(
                'users',
                'organisationUnits'
            )
        );
    }

    public function create(): View
    {
        return view(
            'user-management.users.create',
            $this->formData()
        );
    }

    public function store(
        StoreUserRequest $request
    ): RedirectResponse {
        $result = $this->userService->create(
            $request->validated(),
            $request->user()
        );

        /*
         * Temporary password is deliberately
         * shown once through the session.
         */
        return redirect()
            ->route(
                'user-management.users.show',
                $result['user']
            )
            ->with(
                'success',
                'User created successfully.'
            )
            ->with(
                'temporary_password',
                $result['temporary_password']
            );
    }

    public function show(User $user): View
    {
        $user->load([
            'organisationUnit',
            'jobTitle',
            'grade',
            'supervisor',
            'roles.permissions',
            'dashboards',
        ]);

        return view(
            'user-management.users.show',
            compact('user')
        );
    }

    public function edit(User $user): View
    {
        $user->load([
            'roles',
            'dashboards',
        ]);

        return view(
            'user-management.users.edit',
            [
                ...$this->formData($user),
                'user' => $user,
            ]
        );
    }

    public function update(
        UpdateUserRequest $request,
        User $user
    ): RedirectResponse {
        $this->userService->update(
            $user,
            $request->validated(),
            $request->user()
        );

        return redirect()
            ->route(
                'user-management.users.show',
                $user
            )
            ->with(
                'success',
                'User details updated successfully.'
            );
    }

    public function activate(
        Request $request,
        User $user
    ): RedirectResponse {
        abort_unless(
            $request->user()->can(
                'user-management.users.activate'
            ),
            403
        );

        $oldStatus = $user->account_status;

        $user->update([
            'account_status' => 'active',
            'is_active' => true,
            'failed_login_attempts' => 0,
            'locked_at' => null,
            'lock_expires_at' => null,
            'updated_by' => $request->user()->id,
        ]);

        $this->auditService->record(
            eventType: 'user_activated',
            module: 'user-management',
            action: 'activate',
            description:
                "User {$user->employee_number} activated.",
            subject: $user,
            oldValues: [
                'account_status' => $oldStatus,
            ],
            newValues: [
                'account_status' => 'active',
            ]
        );

        return back()->with(
            'success',
            'User account activated.'
        );
    }

    public function deactivate(
        Request $request,
        User $user
    ): RedirectResponse {
        abort_unless(
            $request->user()->can(
                'user-management.users.deactivate'
            ),
            403
        );

        if ($request->user()->id === $user->id) {
            return back()->with(
                'error',
                'You cannot deactivate your own account.'
            );
        }

        $oldStatus = $user->account_status;

        $user->update([
            'account_status' => 'disabled',
            'is_active' => false,
            'updated_by' => $request->user()->id,
        ]);

        $this->auditService->record(
            eventType: 'user_deactivated',
            module: 'user-management',
            action: 'deactivate',
            description:
                "User {$user->employee_number} deactivated.",
            subject: $user,
            oldValues: [
                'account_status' => $oldStatus,
            ],
            newValues: [
                'account_status' => 'disabled',
            ]
        );

        return back()->with(
            'success',
            'User account deactivated.'
        );
    }

    public function resetPassword(
        Request $request,
        User $user
    ): RedirectResponse {
        abort_unless(
            $request->user()->can(
                'user-management.users.reset-password'
            ),
            403
        );

        $request->validate([
            'password_option' => [
                'required',
                'in:generate,manual',
            ],
            'temporary_password' => [
                'nullable',
                'required_if:password_option,manual',
                'string',
                'max:128',
            ],
        ]);

        $temporaryPassword =
            $request->password_option === 'manual'
                ? $request->temporary_password
                : $this->passwordService
                    ->generateTemporaryPassword();

        if (
            $request->password_option === 'manual'
        ) {
            $this->passwordService
                ->validatePassword(
                    $user,
                    $temporaryPassword
                );
        }

        $policy = $this->passwordService
            ->getActivePolicy();

        DB::transaction(function () use (
            $request,
            $user,
            $temporaryPassword,
            $policy
        ): void {
            $user->passwordHistory()->create([
                'password_hash' => $user->password,
                'changed_at' => now(),
                'changed_by' => $request->user()->id,
                'change_reason' =>
                    'administrator_reset',
            ]);

            $user->update([
                'password' =>
                    Hash::make($temporaryPassword),

                'temporary_password' =>
                    true,

                'must_change_password' =>
                    true,

                'password_changed_at' =>
                    now(),

                'password_expires_at' =>
                    now()->addHours(
                        $policy
                            ->temporary_password_expiry_hours
                    ),

                'failed_login_attempts' =>
                    0,

                'locked_at' =>
                    null,

                'lock_expires_at' =>
                    null,

                'account_status' =>
                    'active',

                'is_active' =>
                    true,

                'updated_by' =>
                    $request->user()->id,
            ]);
        });

        $this->auditService->record(
            eventType: 'password_reset',
            module: 'user-management',
            action: 'reset-password',
            description:
                "Password reset for user {$user->employee_number}.",
            subject: $user
        );

        return redirect()
            ->route(
                'user-management.users.show',
                $user
            )
            ->with(
                'success',
                'Temporary password created successfully.'
            )
            ->with(
                'temporary_password',
                $temporaryPassword
            );
    }

    private function formData(
        ?User $currentUser = null
    ): array {
        return [
            'organisationUnits' =>
                OrganisationUnit::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(),

            'jobTitles' =>
                JobTitle::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(),

            'grades' =>
                Grade::query()
                    ->where('is_active', true)
                    ->orderBy('rank_order')
                    ->get(),

            'supervisors' =>
                User::query()
                    ->where('is_active', true)
                    ->when(
                        $currentUser,
                        fn ($query) =>
                            $query->where(
                                'id',
                                '!=',
                                $currentUser->id
                            )
                    )
                    ->orderBy('surname')
                    ->orderBy('first_name')
                    ->get(),

            'roles' =>
                Role::query()
                    ->where('is_active', true)
                    ->orderBy('display_name')
                    ->get(),

            'dashboards' =>
                Dashboard::query()
                    ->where('is_active', true)
                    ->orderBy('display_order')
                    ->get(),
        ];
    }
}
<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    /**
     * Display all system roles.
     */
    public function index(): View
    {
        $this->ensurePermission(
            'user-management.roles.view'
        );

        $roles = Role::query()
            ->withCount([
                'permissions',
                'users',
            ])
            ->with([
                'permissions:id,name',
            ])
            ->orderBy('name')
            ->get();

        return view(
            'user-management.roles.index',
            compact('roles')
        );
    }


    /**
     * Show the form for creating a role.
     */
    public function create(): View
    {
        $this->ensurePermission(
            'user-management.roles.create'
        );

        $permissionsByModule =
            $this->getPermissionsByModule();

        return view(
            'user-management.roles.create',
            compact('permissionsByModule')
        );
    }


    /**
     * Store a newly created role.
     */
    public function store(
        Request $request
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.roles.create'
        );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:125',
                'regex:/^[A-Za-z0-9._ -]+$/',
            ],

            'permissions' => [
                'nullable',
                'array',
            ],

            'permissions.*' => [
                'string',
                'exists:permissions,name',
            ],
        ], [
            'name.required' =>
                'The role name is required.',

            'name.regex' =>
                'The role name may only contain letters, numbers, spaces, dots, underscores and hyphens.',
        ]);


        $roleName = Str::of(
            $validated['name']
        )
            ->trim()
            ->lower()
            ->replace(' ', '-')
            ->toString();


        $exists = Role::query()
            ->where('name', $roleName)
            ->where('guard_name', 'web')
            ->exists();


        if ($exists) {
            return back()
                ->withErrors([
                    'name' =>
                        'A role with this name already exists.',
                ])
                ->withInput();
        }


        $permissionNames =
            $validated['permissions'] ?? [];


        DB::transaction(
            function () use (
                $roleName,
                $permissionNames
            ): void {

                $role = Role::create([
                    'name' => $roleName,
                    'guard_name' => 'web',
                ]);


                $role->syncPermissions(
                    $permissionNames
                );
            }
        );


        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();


        return redirect()
            ->route(
                'user-management.roles.index'
            )
            ->with(
                'success',
                'Role created successfully.'
            );
    }


    /**
     * Route::resource expects a show method.
     *
     * For roles, editing provides the useful detailed view,
     * therefore redirect the show route to edit.
     */
    public function show(
        Role $role
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.roles.view'
        );

        return redirect()
            ->route(
                'user-management.roles.edit',
                $role
            );
    }


    /**
     * Show the form for editing a role.
     */
    public function edit(
        Role $role
    ): View {
        $this->ensurePermission(
            'user-management.roles.update'
        );

        $role->load('permissions');

        $permissionsByModule =
            $this->getPermissionsByModule();

        $selectedPermissions =
            $role->permissions
                ->pluck('name')
                ->all();


        return view(
            'user-management.roles.edit',
            compact(
                'role',
                'permissionsByModule',
                'selectedPermissions'
            )
        );
    }


    /**
     * Update the specified role.
     */
    public function update(
        Request $request,
        Role $role
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.roles.update'
        );


        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:125',
                'regex:/^[A-Za-z0-9._ -]+$/',
            ],

            'permissions' => [
                'nullable',
                'array',
            ],

            'permissions.*' => [
                'string',
                'exists:permissions,name',
            ],
        ]);


        $roleName = Str::of(
            $validated['name']
        )
            ->trim()
            ->lower()
            ->replace(' ', '-')
            ->toString();


        $duplicateRole = Role::query()
            ->where('name', $roleName)
            ->where('guard_name', 'web')
            ->where('id', '<>', $role->id)
            ->exists();


        if ($duplicateRole) {
            return back()
                ->withErrors([
                    'name' =>
                        'A role with this name already exists.',
                ])
                ->withInput();
        }


        /*
        |--------------------------------------------------------------------------
        | Protect the System Administrator role
        |--------------------------------------------------------------------------
        |
        | The role may still have its permissions changed, but we do not allow
        | its identifying name to be changed accidentally.
        |
        */

        $protectedRoles = [
            'system-administrator',
            'system administrator',
            'super-admin',
            'super_admin',
        ];


        if (
            in_array(
                strtolower($role->name),
                $protectedRoles,
                true
            )
            && $roleName !== strtolower($role->name)
        ) {
            return back()
                ->withErrors([
                    'name' =>
                        'The System Administrator role name cannot be changed.',
                ])
                ->withInput();
        }


        $permissionNames =
            $validated['permissions'] ?? [];


        DB::transaction(
            function () use (
                $role,
                $roleName,
                $permissionNames
            ): void {

                $role->update([
                    'name' => $roleName,
                    'guard_name' => 'web',
                ]);


                $role->syncPermissions(
                    $permissionNames
                );
            }
        );


        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();


        return redirect()
            ->route(
                'user-management.roles.edit',
                $role
            )
            ->with(
                'success',
                'Role and permissions updated successfully.'
            );
    }


    /**
     * Delete a role.
     */
    public function destroy(
        Role $role
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.roles.delete'
        );


        /*
        |--------------------------------------------------------------------------
        | Protected Roles
        |--------------------------------------------------------------------------
        */

        $protectedRoles = [
            'system-administrator',
            'system administrator',
            'super-admin',
            'super_admin',
        ];


        if (
            in_array(
                strtolower($role->name),
                $protectedRoles,
                true
            )
        ) {
            return back()->with(
                'error',
                'The System Administrator role cannot be deleted.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent deleting roles currently assigned to users
        |--------------------------------------------------------------------------
        */

        $assignedUserCount =
            $role->users()->count();


        if ($assignedUserCount > 0) {
            return back()->with(
                'error',
                'This role cannot be deleted because it is currently assigned to '
                . $assignedUserCount
                . ' user(s). Remove the role from those users first.'
            );
        }


        DB::transaction(
            function () use ($role): void {

                $role->syncPermissions([]);

                $role->delete();
            }
        );


        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();


        return redirect()
            ->route(
                'user-management.roles.index'
            )
            ->with(
                'success',
                'Role deleted successfully.'
            );
    }


    /**
     * Build permissions grouped by module.
     *
     * Examples:
     *
     * user-management.users.view
     *      -> User Management
     *
     * audit.audit-trails.view
     *      -> Audit
     *
     * dashboard.finance.view
     *      -> Dashboard
     *
     * claims.withdrawal.view
     *      -> Claims
     */
    private function getPermissionsByModule(): Collection
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->groupBy(
                function (
                    Permission $permission
                ): string {

                    $firstPart =
                        explode(
                            '.',
                            $permission->name
                        )[0] ?? 'other';


                    return Str::of($firstPart)
                        ->replace('-', ' ')
                        ->replace('_', ' ')
                        ->title()
                        ->toString();
                }
            );
    }


    /**
     * Enforce controller-level permission checking.
     *
     * This is important because Route::resource('roles', ...)
     * currently does not have method-specific middleware.
     */
    private function ensurePermission(
        string $permission
    ): void {
        $user = auth()->user();


        abort_if(
            !$user,
            401,
            'Unauthenticated.'
        );


        /*
        |--------------------------------------------------------------------------
        | System Administrator
        |--------------------------------------------------------------------------
        |
        | Your system design allows the System Administrator to administer
        | access rights. is_system_administrator is therefore checked here.
        |
        */

        if ($user->is_system_administrator) {
            return;
        }


        abort_unless(
            $user->can($permission),
            403,
            'You do not have permission to perform this action.'
        );
    }
}
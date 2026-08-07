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
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    /**
     * Display all permissions.
     */
    public function index(): View
    {
        $this->ensurePermission(
            'user-management.permissions.view'
        );

        $permissions = Permission::query()
            ->withCount('roles')
            ->orderBy('name')
            ->get();

        $permissionsByModule =
            $this->groupPermissionsByModule(
                $permissions
            );

        return view(
            'user-management.permissions.index',
            compact(
                'permissions',
                'permissionsByModule'
            )
        );
    }


    /**
     * Show create permission form.
     */
    public function create(): View
    {
        $this->ensurePermission(
            'user-management.permissions.create'
        );

        return view(
            'user-management.permissions.create'
        );
    }


    /**
     * Store new permission.
     */
    public function store(
        Request $request
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.permissions.create'
        );

        $validated = $request->validate([
            'module' => [
                'required',
                'string',
                'max:100',
            ],

            'resource' => [
                'required',
                'string',
                'max:100',
            ],

            'action' => [
                'required',
                'string',
                'max:100',
            ],
        ]);


        $module = $this->normaliseSegment(
            $validated['module']
        );

        $resource = $this->normaliseSegment(
            $validated['resource']
        );

        $action = $this->normaliseSegment(
            $validated['action']
        );


        $permissionName =
            $module
            . '.'
            . $resource
            . '.'
            . $action;


        $exists = Permission::query()
            ->where(
                'name',
                $permissionName
            )
            ->where(
                'guard_name',
                'web'
            )
            ->exists();


        if ($exists) {
            return back()
                ->withErrors([
                    'action' =>
                        'This permission already exists: '
                        . $permissionName,
                ])
                ->withInput();
        }


        DB::transaction(
            function () use (
                $permissionName
            ): void {

                Permission::create([
                    'name' =>
                        $permissionName,

                    'guard_name' =>
                        'web',
                ]);
            }
        );


        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();


        return redirect()
            ->route(
                'user-management.permissions.index'
            )
            ->with(
                'success',
                'Permission created successfully.'
            );
    }


    /**
     * Show permission.
     *
     * We redirect to edit because the edit page provides
     * the detailed permission view.
     */
    public function show(
        Permission $permission
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.permissions.view'
        );

        return redirect()
            ->route(
                'user-management.permissions.edit',
                $permission
            );
    }


    /**
     * Show edit form.
     */
    public function edit(
        Permission $permission
    ): View {
        $this->ensurePermission(
            'user-management.permissions.update'
        );

        $permission->load(
            'roles'
        );


        $parts = explode(
            '.',
            $permission->name
        );


        $permissionModule =
            $parts[0] ?? '';

        $permissionResource =
            $parts[1] ?? '';

        $permissionAction =
            implode(
                '.',
                array_slice(
                    $parts,
                    2
                )
            );


        return view(
            'user-management.permissions.edit',
            compact(
                'permission',
                'permissionModule',
                'permissionResource',
                'permissionAction'
            )
        );
    }


    /**
     * Update permission.
     */
    public function update(
        Request $request,
        Permission $permission
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.permissions.update'
        );


        $validated = $request->validate([
            'module' => [
                'required',
                'string',
                'max:100',
            ],

            'resource' => [
                'required',
                'string',
                'max:100',
            ],

            'action' => [
                'required',
                'string',
                'max:100',
            ],
        ]);


        $module = $this->normaliseSegment(
            $validated['module']
        );

        $resource = $this->normaliseSegment(
            $validated['resource']
        );

        $action = $this->normaliseSegment(
            $validated['action']
        );


        $permissionName =
            $module
            . '.'
            . $resource
            . '.'
            . $action;


        $duplicate = Permission::query()
            ->where(
                'name',
                $permissionName
            )
            ->where(
                'guard_name',
                'web'
            )
            ->where(
                'id',
                '<>',
                $permission->id
            )
            ->exists();


        if ($duplicate) {
            return back()
                ->withErrors([
                    'action' =>
                        'Another permission already uses this name.',
                ])
                ->withInput();
        }


        /*
        |--------------------------------------------------------------------------
        | Protect critical permission names
        |--------------------------------------------------------------------------
        |
        | These permissions control access to the security administration
        | area itself. We do not want them renamed accidentally.
        |
        */

        $protectedPermissions = [
            'user-management.permissions.view',
            'user-management.permissions.create',
            'user-management.permissions.update',
            'user-management.permissions.delete',
        ];


        if (
            in_array(
                $permission->name,
                $protectedPermissions,
                true
            )
            &&
            $permissionName
            !== $permission->name
        ) {
            return back()
                ->withErrors([
                    'module' =>
                        'This is a protected system permission and its name cannot be changed.',
                ])
                ->withInput();
        }


        DB::transaction(
            function () use (
                $permission,
                $permissionName
            ): void {

                $permission->update([
                    'name' =>
                        $permissionName,

                    'guard_name' =>
                        'web',
                ]);
            }
        );


        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();


        return redirect()
            ->route(
                'user-management.permissions.edit',
                $permission
            )
            ->with(
                'success',
                'Permission updated successfully.'
            );
    }


    /**
     * Delete permission.
     */
    public function destroy(
        Permission $permission
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.permissions.delete'
        );


        $protectedPermissions = [
            'user-management.permissions.view',
            'user-management.permissions.create',
            'user-management.permissions.update',
            'user-management.permissions.delete',
        ];


        if (
            in_array(
                $permission->name,
                $protectedPermissions,
                true
            )
        ) {
            return back()
                ->with(
                    'error',
                    'This system permission cannot be deleted.'
                );
        }


        $roleCount =
            $permission->roles()->count();


        if ($roleCount > 0) {
            return back()
                ->with(
                    'error',
                    'This permission cannot be deleted because it is assigned to '
                    . $roleCount
                    . ' role(s). Remove it from the roles first.'
                );
        }


        DB::transaction(
            function () use (
                $permission
            ): void {

                $permission->delete();
            }
        );


        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();


        return redirect()
            ->route(
                'user-management.permissions.index'
            )
            ->with(
                'success',
                'Permission deleted successfully.'
            );
    }


    /**
     * Group permissions by first permission segment.
     */
    private function groupPermissionsByModule(
        Collection $permissions
    ): Collection {
        return $permissions
            ->groupBy(
                function (
                    Permission $permission
                ): string {

                    $firstSegment =
                        explode(
                            '.',
                            $permission->name
                        )[0] ?? 'other';


                    return Str::of(
                        $firstSegment
                    )
                        ->replace(
                            '-',
                            ' '
                        )
                        ->replace(
                            '_',
                            ' '
                        )
                        ->title()
                        ->toString();
                }
            );
    }


    /**
     * Convert user input into permission naming convention.
     */
    private function normaliseSegment(
        string $segment
    ): string {
        return Str::of(
            $segment
        )
            ->trim()
            ->lower()
            ->replace(
                '_',
                '-'
            )
            ->replace(
                ' ',
                '-'
            )
            ->replaceMatches(
                '/[^a-z0-9\-\.]/',
                ''
            )
            ->toString();
    }


    /**
     * Controller-level access enforcement.
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


        if (
            $user->is_system_administrator
        ) {
            return;
        }


        abort_unless(
            $user->can(
                $permission
            ),
            403,
            'You do not have permission to perform this action.'
        );
    }
}
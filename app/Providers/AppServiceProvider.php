<?php

namespace App\Providers;

use App\Models\UserManagement\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {

            /*
            |--------------------------------------------------------------------------
            | Block inactive accounts
            |--------------------------------------------------------------------------
            */

            if (!$user->isAccountActive()) {
                return false;
            }

            /*
            |--------------------------------------------------------------------------
            | System Administrator Handling
            |--------------------------------------------------------------------------
            |
            | The system administrator does NOT automatically bypass every
            | permission.
            |
            | We first check whether the requested permission actually exists.
            | This prevents Spatie from throwing PermissionDoesNotExist when
            | a module has not yet been created or seeded.
            |
            */

            if ($user->is_system_administrator) {

                $permissionExists = Permission::query()
                    ->where('name', $ability)
                    ->where('guard_name', 'web')
                    ->exists();

                if (!$permissionExists) {
                    return null;
                }

                if ($user->hasPermissionTo($ability, 'web')) {
                    return true;
                }
            }

            return null;
        });
    }
}
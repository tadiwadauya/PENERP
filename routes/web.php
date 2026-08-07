<?php

use App\Http\Controllers\Authentication\AuthenticationController;
use App\Http\Controllers\Authentication\PasswordController;
use App\Http\Controllers\Dashboard\DashboardController;

use App\Http\Controllers\UserManagement\UserController;
use App\Http\Controllers\UserManagement\RoleController;
use App\Http\Controllers\UserManagement\PermissionController;
use App\Http\Controllers\UserManagement\OrganisationUnitController;
use App\Http\Controllers\UserManagement\JobTitleController;
use App\Http\Controllers\UserManagement\GradeController;
use App\Http\Controllers\UserManagement\PasswordPolicyController;

use App\Http\Controllers\Audit\AuditTrailController;
use App\Http\Controllers\Audit\UserSessionController;
use App\Http\Controllers\Audit\LoginAttemptController;

use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Home
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');


/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function (): void {

    Route::get(
        '/login',
        [
            AuthenticationController::class,
            'create',
        ]
    )->name('login');

    Route::post(
        '/login',
        [
            AuthenticationController::class,
            'store',
        ]
    )->name('login.store');
});


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function (): void {

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/logout',
        [
            AuthenticationController::class,
            'destroy',
        ]
    )->name('logout');


    /*
    |--------------------------------------------------------------------------
    | Mandatory Password Change
    |--------------------------------------------------------------------------
    |
    | These routes MUST remain outside the password.changed middleware,
    | otherwise a user with a temporary or expired password would never
    | be able to reach the password-change page.
    |
    */

    Route::get(
        '/password/change-required',
        [
            PasswordController::class,
            'editRequired',
        ]
    )->name('password.required');

    Route::put(
        '/password/change-required',
        [
            PasswordController::class,
            'updateRequired',
        ]
    )->name('password.required.update');


    /*
    |--------------------------------------------------------------------------
    | Fully Authenticated System
    |--------------------------------------------------------------------------
    */

    Route::middleware([
        'account.active',
        'password.changed',
        'password.not-expired',
        'session.track',
    ])->group(function (): void {


        /*
        |--------------------------------------------------------------------------
        | Main Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/dashboard',
            [
                DashboardController::class,
                'index',
            ]
        )->name('dashboard');


        /*
        |--------------------------------------------------------------------------
        | Finance Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/dashboard/finance',
            [
                DashboardController::class,
                'finance',
            ]
        )
            ->middleware(
                'permission:dashboard.finance.view'
            )
            ->name(
                'dashboard.finance'
            );


        /*
        |--------------------------------------------------------------------------
        | Pensions Administration Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/dashboard/pensions',
            [
                DashboardController::class,
                'pensions',
            ]
        )
            ->middleware(
                'permission:dashboard.pensions.view'
            )
            ->name(
                'dashboard.pensions'
            );


        /*
        |--------------------------------------------------------------------------
        | Property Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/dashboard/property',
            [
                DashboardController::class,
                'property',
            ]
        )
            ->middleware(
                'permission:dashboard.property.view'
            )
            ->name(
                'dashboard.property'
            );


        /*
        |--------------------------------------------------------------------------
        | Principal Officer Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/dashboard/principal-office',
            [
                DashboardController::class,
                'principalOffice',
            ]
        )
            ->middleware(
                'permission:dashboard.principal-office.view'
            )
            ->name(
                'dashboard.principal-office'
            );


        /*
        |--------------------------------------------------------------------------
        | System Administration Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/dashboard/system-administration',
            [
                DashboardController::class,
                'systemAdministration',
            ]
        )
            ->middleware(
                'permission:dashboard.system-administration.view'
            )
            ->name(
                'dashboard.system-administration'
            );


        /*
        |--------------------------------------------------------------------------
        | User Management
        |--------------------------------------------------------------------------
        */
        /*
|--------------------------------------------------------------------------
| Audit and Security
|--------------------------------------------------------------------------
*/

Route::prefix('audit')
    ->name('audit.')
    ->group(function (): void {

        /*
        |--------------------------------------------------------------------------
        | Audit Trails
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/audit-trails',
            [
                AuditTrailController::class,
                'index',
            ]
        )
            ->middleware(
                'permission:audit.audit-trails.view'
            )
            ->name(
                'audit-trails.index'
            );


        Route::get(
            '/audit-trails/{auditTrail}',
            [
                AuditTrailController::class,
                'show',
            ]
        )
            ->middleware(
                'permission:audit.audit-trails.view'
            )
            ->name(
                'audit-trails.show'
            );


        /*
        |--------------------------------------------------------------------------
        | User Sessions
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/user-sessions',
            [
                UserSessionController::class,
                'index',
            ]
        )
            ->middleware(
                'permission:audit.user-sessions.view'
            )
            ->name(
                'user-sessions.index'
            );


        Route::get(
            '/user-sessions/{userSession}',
            [
                UserSessionController::class,
                'show',
            ]
        )
            ->middleware(
                'permission:audit.user-sessions.view'
            )
            ->name(
                'user-sessions.show'
            );


        /*
        |--------------------------------------------------------------------------
        | Login Attempts
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/login-attempts',
            [
                LoginAttemptController::class,
                'index',
            ]
        )
            ->middleware(
                'permission:audit.login-attempts.view'
            )
            ->name(
                'login-attempts.index'
            );


        Route::get(
            '/login-attempts/{loginAttempt}',
            [
                LoginAttemptController::class,
                'show',
            ]
        )
            ->middleware(
                'permission:audit.login-attempts.view'
            )
            ->name(
                'login-attempts.show'
            );
    });

        Route::prefix('user-management')
            ->name('user-management.')
            ->group(function (): void {


                /*
                |--------------------------------------------------------------------------
                | Users
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/users',
                    [
                        UserController::class,
                        'index',
                    ]
                )
                    ->middleware(
                        'permission:user-management.users.view'
                    )
                    ->name(
                        'users.index'
                    );


                Route::get(
                    '/users/create',
                    [
                        UserController::class,
                        'create',
                    ]
                )
                    ->middleware(
                        'permission:user-management.users.create'
                    )
                    ->name(
                        'users.create'
                    );


                Route::post(
                    '/users',
                    [
                        UserController::class,
                        'store',
                    ]
                )
                    ->middleware(
                        'permission:user-management.users.create'
                    )
                    ->name(
                        'users.store'
                    );


                Route::get(
                    '/users/{user}',
                    [
                        UserController::class,
                        'show',
                    ]
                )
                    ->middleware(
                        'permission:user-management.users.view'
                    )
                    ->name(
                        'users.show'
                    );


                Route::get(
                    '/users/{user}/edit',
                    [
                        UserController::class,
                        'edit',
                    ]
                )
                    ->middleware(
                        'permission:user-management.users.update'
                    )
                    ->name(
                        'users.edit'
                    );


                Route::put(
                    '/users/{user}',
                    [
                        UserController::class,
                        'update',
                    ]
                )
                    ->middleware(
                        'permission:user-management.users.update'
                    )
                    ->name(
                        'users.update'
                    );


                /*
                |--------------------------------------------------------------------------
                | Activate User
                |--------------------------------------------------------------------------
                */

                Route::patch(
                    '/users/{user}/activate',
                    [
                        UserController::class,
                        'activate',
                    ]
                )
                    ->middleware(
                        'permission:user-management.users.activate'
                    )
                    ->name(
                        'users.activate'
                    );


                /*
                |--------------------------------------------------------------------------
                | Deactivate User
                |--------------------------------------------------------------------------
                */

                Route::patch(
                    '/users/{user}/deactivate',
                    [
                        UserController::class,
                        'deactivate',
                    ]
                )
                    ->middleware(
                        'permission:user-management.users.deactivate'
                    )
                    ->name(
                        'users.deactivate'
                    );


                /*
                |--------------------------------------------------------------------------
                | Reset User Password
                |--------------------------------------------------------------------------
                */

                Route::put(
                    '/users/{user}/reset-password',
                    [
                        UserController::class,
                        'resetPassword',
                    ]
                )
                    ->middleware(
                        'permission:user-management.users.reset-password'
                    )
                    ->name(
                        'users.reset-password'
                    );


                /*
                |--------------------------------------------------------------------------
                | Roles
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/roles',
                    [
                        RoleController::class,
                        'index',
                    ]
                )
                    ->middleware(
                        'permission:user-management.roles.view'
                    )
                    ->name(
                        'roles.index'
                    );


                Route::get(
                    '/roles/create',
                    [
                        RoleController::class,
                        'create',
                    ]
                )
                    ->middleware(
                        'permission:user-management.roles.create'
                    )
                    ->name(
                        'roles.create'
                    );


                Route::post(
                    '/roles',
                    [
                        RoleController::class,
                        'store',
                    ]
                )
                    ->middleware(
                        'permission:user-management.roles.create'
                    )
                    ->name(
                        'roles.store'
                    );


                Route::get(
                    '/roles/{role}',
                    [
                        RoleController::class,
                        'show',
                    ]
                )
                    ->middleware(
                        'permission:user-management.roles.view'
                    )
                    ->name(
                        'roles.show'
                    );


                Route::get(
                    '/roles/{role}/edit',
                    [
                        RoleController::class,
                        'edit',
                    ]
                )
                    ->middleware(
                        'permission:user-management.roles.update'
                    )
                    ->name(
                        'roles.edit'
                    );


                Route::put(
                    '/roles/{role}',
                    [
                        RoleController::class,
                        'update',
                    ]
                )
                    ->middleware(
                        'permission:user-management.roles.update'
                    )
                    ->name(
                        'roles.update'
                    );


                Route::delete(
                    '/roles/{role}',
                    [
                        RoleController::class,
                        'destroy',
                    ]
                )
                    ->middleware(
                        'permission:user-management.roles.delete'
                    )
                    ->name(
                        'roles.destroy'
                    );


                /*
                |--------------------------------------------------------------------------
                | Permissions
                |--------------------------------------------------------------------------
                |
                | Permissions are defined by the application/modules.
                | ICT can view them and assign them through roles.
                |
                */

                Route::get(
                    '/permissions',
                    [
                        PermissionController::class,
                        'index',
                    ]
                )
                    ->middleware(
                        'permission:user-management.permissions.view'
                    )
                    ->name(
                        'permissions.index'
                    );


                Route::get(
                    '/permissions/{permission}',
                    [
                        PermissionController::class,
                        'show',
                    ]
                )
                    ->middleware(
                        'permission:user-management.permissions.view'
                    )
                    ->name(
                        'permissions.show'
                    );


                /*
                |--------------------------------------------------------------------------
                | Organisation Units
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/organisation-units',
                    [
                        OrganisationUnitController::class,
                        'index',
                    ]
                )
                    ->middleware(
                        'permission:user-management.organisation-units.view'
                    )
                    ->name(
                        'organisation-units.index'
                    );


                Route::get(
                    '/organisation-units/create',
                    [
                        OrganisationUnitController::class,
                        'create',
                    ]
                )
                    ->middleware(
                        'permission:user-management.organisation-units.create'
                    )
                    ->name(
                        'organisation-units.create'
                    );


                Route::post(
                    '/organisation-units',
                    [
                        OrganisationUnitController::class,
                        'store',
                    ]
                )
                    ->middleware(
                        'permission:user-management.organisation-units.create'
                    )
                    ->name(
                        'organisation-units.store'
                    );


                Route::get(
                    '/organisation-units/{organisation_unit}',
                    [
                        OrganisationUnitController::class,
                        'show',
                    ]
                )
                    ->middleware(
                        'permission:user-management.organisation-units.view'
                    )
                    ->name(
                        'organisation-units.show'
                    );


                Route::get(
                    '/organisation-units/{organisation_unit}/edit',
                    [
                        OrganisationUnitController::class,
                        'edit',
                    ]
                )
                    ->middleware(
                        'permission:user-management.organisation-units.update'
                    )
                    ->name(
                        'organisation-units.edit'
                    );


                Route::put(
                    '/organisation-units/{organisation_unit}',
                    [
                        OrganisationUnitController::class,
                        'update',
                    ]
                )
                    ->middleware(
                        'permission:user-management.organisation-units.update'
                    )
                    ->name(
                        'organisation-units.update'
                    );


                Route::delete(
                    '/organisation-units/{organisation_unit}',
                    [
                        OrganisationUnitController::class,
                        'destroy',
                    ]
                )
                    ->middleware(
                        'permission:user-management.organisation-units.delete'
                    )
                    ->name(
                        'organisation-units.destroy'
                    );


                /*
                |--------------------------------------------------------------------------
                | Job Titles
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/job-titles',
                    [
                        JobTitleController::class,
                        'index',
                    ]
                )
                    ->middleware(
                        'permission:user-management.job-titles.view'
                    )
                    ->name(
                        'job-titles.index'
                    );


                Route::get(
                    '/job-titles/create',
                    [
                        JobTitleController::class,
                        'create',
                    ]
                )
                    ->middleware(
                        'permission:user-management.job-titles.create'
                    )
                    ->name(
                        'job-titles.create'
                    );


                Route::post(
                    '/job-titles',
                    [
                        JobTitleController::class,
                        'store',
                    ]
                )
                    ->middleware(
                        'permission:user-management.job-titles.create'
                    )
                    ->name(
                        'job-titles.store'
                    );


                Route::get(
                    '/job-titles/{job_title}',
                    [
                        JobTitleController::class,
                        'show',
                    ]
                )
                    ->middleware(
                        'permission:user-management.job-titles.view'
                    )
                    ->name(
                        'job-titles.show'
                    );


                Route::get(
                    '/job-titles/{job_title}/edit',
                    [
                        JobTitleController::class,
                        'edit',
                    ]
                )
                    ->middleware(
                        'permission:user-management.job-titles.update'
                    )
                    ->name(
                        'job-titles.edit'
                    );


                Route::put(
                    '/job-titles/{job_title}',
                    [
                        JobTitleController::class,
                        'update',
                    ]
                )
                    ->middleware(
                        'permission:user-management.job-titles.update'
                    )
                    ->name(
                        'job-titles.update'
                    );


                Route::delete(
                    '/job-titles/{job_title}',
                    [
                        JobTitleController::class,
                        'destroy',
                    ]
                )
                    ->middleware(
                        'permission:user-management.job-titles.delete'
                    )
                    ->name(
                        'job-titles.destroy'
                    );


                /*
                |--------------------------------------------------------------------------
                | Grades
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/grades',
                    [
                        GradeController::class,
                        'index',
                    ]
                )
                    ->middleware(
                        'permission:user-management.grades.view'
                    )
                    ->name(
                        'grades.index'
                    );


                Route::get(
                    '/grades/create',
                    [
                        GradeController::class,
                        'create',
                    ]
                )
                    ->middleware(
                        'permission:user-management.grades.create'
                    )
                    ->name(
                        'grades.create'
                    );


                Route::post(
                    '/grades',
                    [
                        GradeController::class,
                        'store',
                    ]
                )
                    ->middleware(
                        'permission:user-management.grades.create'
                    )
                    ->name(
                        'grades.store'
                    );


                Route::get(
                    '/grades/{grade}',
                    [
                        GradeController::class,
                        'show',
                    ]
                )
                    ->middleware(
                        'permission:user-management.grades.view'
                    )
                    ->name(
                        'grades.show'
                    );


                Route::get(
                    '/grades/{grade}/edit',
                    [
                        GradeController::class,
                        'edit',
                    ]
                )
                    ->middleware(
                        'permission:user-management.grades.update'
                    )
                    ->name(
                        'grades.edit'
                    );


                Route::put(
                    '/grades/{grade}',
                    [
                        GradeController::class,
                        'update',
                    ]
                )
                    ->middleware(
                        'permission:user-management.grades.update'
                    )
                    ->name(
                        'grades.update'
                    );


                Route::delete(
                    '/grades/{grade}',
                    [
                        GradeController::class,
                        'destroy',
                    ]
                )
                    ->middleware(
                        'permission:user-management.grades.delete'
                    )
                    ->name(
                        'grades.destroy'
                    );


                /*
                |--------------------------------------------------------------------------
                | Password Policy
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/password-policy',
                    [
                        PasswordPolicyController::class,
                        'edit',
                    ]
                )
                    ->middleware(
                        'permission:user-management.password-policies.view'
                    )
                    ->name(
                        'password-policies.edit'
                    );


                Route::put(
                    '/password-policy',
                    [
                        PasswordPolicyController::class,
                        'update',
                    ]
                )
                    ->middleware(
                        'permission:user-management.password-policies.update'
                    )
                    ->name(
                        'password-policies.update'
                    );
            });
    });
});
<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Authentication\AuthenticationController;
use App\Http\Controllers\Authentication\PasswordController;

use App\Http\Controllers\Notifications\NotificationController;

use App\Http\Controllers\Dashboard\DashboardController;

use App\Http\Controllers\PensionsAdministration\PensionsDashboardController;
use App\Http\Controllers\PensionsAdministration\Updates\UpdatesDashboardController;

use App\Http\Controllers\UserManagement\UserController;
use App\Http\Controllers\UserManagement\RoleController;
use App\Http\Controllers\UserManagement\PermissionController;
use App\Http\Controllers\UserManagement\OrganisationUnitController;
use App\Http\Controllers\UserManagement\JobTitleController;
use App\Http\Controllers\UserManagement\GradeController;
use App\Http\Controllers\UserManagement\PasswordPolicyController;
use App\Http\Controllers\UserManagement\ProfileController;

use App\Http\Controllers\Audit\AuditTrailController;
use App\Http\Controllers\Audit\UserSessionController;
use App\Http\Controllers\Audit\LoginAttemptController;

use App\Http\Controllers\PensionsAdministration\Updates\EmployerController;
use App\Http\Controllers\PensionsAdministration\Updates\EmployerGroupController;
use App\Http\Controllers\PensionsAdministration\Updates\MemberController;
use App\Http\Controllers\PensionsAdministration\Updates\MembershipImportController;
use App\Http\Controllers\PensionsAdministration\Updates\MembershipImportReviewController;
use App\Http\Controllers\PensionsAdministration\Updates\MembershipImportDataController;
use App\Http\Controllers\PensionsAdministration\Updates\MembershipImportCommitController;
use App\Http\Controllers\PensionsAdministration\Updates\EmployerImportController;
use App\Http\Controllers\PensionsAdministration\Updates\MembershipReportController;

use App\Http\Controllers\PensionsAdministration\Contributions\ContributionImportController;
use App\Http\Controllers\PensionsAdministration\Contributions\ContributionImportReviewController;
use App\Http\Controllers\PensionsAdministration\Contributions\ContributionPostingController;
use App\Http\Controllers\PensionsAdministration\Contributions\ContributionReviewActionController;
use App\Http\Controllers\PensionsAdministration\Contributions\ContributionExceptionReportController;
use App\Http\Controllers\PensionsAdministration\Contributions\ContributionReconciliationController;


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

    Route::get('/login', [AuthenticationController::class, 'create'])->name('login');

    Route::post('/login', [AuthenticationController::class, 'store'])->name('login.store');

});


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function (): void {


    /*
    |--------------------------------------------------------------------------
    | Authentication / Profile
    |--------------------------------------------------------------------------
    */

    Route::post('/logout', [AuthenticationController::class, 'destroy'])->name('logout');

    Route::get('/password/change-required', [PasswordController::class, 'editRequired'])->name('password.required');

    Route::put('/password/change-required', [PasswordController::class, 'updateRequired'])->name('password.required.update');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');

    Route::get('/password/change', [PasswordController::class, 'edit'])->name('password.change');

    Route::put('/password/change', [PasswordController::class, 'update'])->name('password.change.update');


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
        | Dashboards
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/dashboard/finance', [DashboardController::class, 'finance'])->middleware('permission:dashboard.finance.view')->name('dashboard.finance');

        Route::get('/dashboard/pensions', [DashboardController::class, 'pensions'])->middleware('permission:dashboard.pensions.view')->name('dashboard.pensions');

        Route::get('/dashboard/property', [DashboardController::class, 'property'])->middleware('permission:dashboard.property.view')->name('dashboard.property');

        Route::get('/dashboard/principal-office', [DashboardController::class, 'principalOffice'])->middleware('permission:dashboard.principal-office.view')->name('dashboard.principal-office');

        Route::get('/dashboard/system-administration', [DashboardController::class, 'systemAdministration'])->middleware('permission:dashboard.system-administration.view')->name('dashboard.system-administration');


        /*
        |--------------------------------------------------------------------------
        | Notifications
        |--------------------------------------------------------------------------
        */

        Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');

        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');


        /*
        |--------------------------------------------------------------------------
        | Pensions Administration
        |--------------------------------------------------------------------------
        */

        Route::prefix('pensions-administration')->name('pensions-administration.')->group(function (): void {


            /*
            |--------------------------------------------------------------------------
            | Pensions Dashboard
            |--------------------------------------------------------------------------
            */

            Route::get('/', [PensionsDashboardController::class, 'index'])->name('dashboard');


            /*
            |--------------------------------------------------------------------------
            | Updates
            |--------------------------------------------------------------------------
            */

            Route::prefix('updates')->name('updates.')->group(function (): void {


                /*
                |--------------------------------------------------------------------------
                | Updates Dashboard
                |--------------------------------------------------------------------------
                */

                Route::get('/', [UpdatesDashboardController::class, 'index'])->middleware('permission:updates.dashboard.view')->name('dashboard');


                /*
                |--------------------------------------------------------------------------
                | Membership Reports
                |--------------------------------------------------------------------------
                */

                Route::get('reports/membership', [MembershipReportController::class, 'index'])->middleware('permission:updates.reports.membership.view')->name('reports.membership.index');


                /*
                |--------------------------------------------------------------------------
                | Employer Groups
                |--------------------------------------------------------------------------
                */

                Route::get('employer-groups', [EmployerGroupController::class, 'index'])->middleware('permission:updates.employer-groups.view')->name('employer-groups.index');

                Route::get('employer-groups/create', [EmployerGroupController::class, 'create'])->middleware('permission:updates.employer-groups.create')->name('employer-groups.create');

                Route::post('employer-groups', [EmployerGroupController::class, 'store'])->middleware('permission:updates.employer-groups.create')->name('employer-groups.store');

                Route::get('employer-groups/{employer_group}', [EmployerGroupController::class, 'show'])->middleware('permission:updates.employer-groups.view')->name('employer-groups.show');

                Route::get('employer-groups/{employer_group}/edit', [EmployerGroupController::class, 'edit'])->middleware('permission:updates.employer-groups.update')->name('employer-groups.edit');

                Route::put('employer-groups/{employer_group}', [EmployerGroupController::class, 'update'])->middleware('permission:updates.employer-groups.update')->name('employer-groups.update');

                Route::delete('employer-groups/{employer_group}', [EmployerGroupController::class, 'destroy'])->middleware('permission:updates.employer-groups.delete')->name('employer-groups.destroy');


                /*
                |--------------------------------------------------------------------------
                | Employers
                |--------------------------------------------------------------------------
                */

                Route::get('employers', [EmployerController::class, 'index'])->middleware('permission:updates.employers.view')->name('employers.index');

                Route::get('employers/create', [EmployerController::class, 'create'])->middleware('permission:updates.employers.create')->name('employers.create');

                Route::post('employers', [EmployerController::class, 'store'])->middleware('permission:updates.employers.create')->name('employers.store');

                Route::get('employers/{employer}', [EmployerController::class, 'show'])->middleware('permission:updates.employers.view')->name('employers.show');

                Route::get('employers/{employer}/edit', [EmployerController::class, 'edit'])->middleware('permission:updates.employers.update')->name('employers.edit');

                Route::put('employers/{employer}', [EmployerController::class, 'update'])->middleware('permission:updates.employers.update')->name('employers.update');

                Route::delete('employers/{employer}', [EmployerController::class, 'destroy'])->middleware('permission:updates.employers.delete')->name('employers.destroy');


                /*
                |--------------------------------------------------------------------------
                | Employer Imports
                |--------------------------------------------------------------------------
                */

                Route::get('employer-imports', [EmployerImportController::class, 'index'])->middleware('permission:updates.employer-imports.view')->name('employer-imports.index');

                Route::get('employer-imports/create', [EmployerImportController::class, 'create'])->middleware('permission:updates.employer-imports.create')->name('employer-imports.create');

                Route::post('employer-imports', [EmployerImportController::class, 'store'])->middleware('permission:updates.employer-imports.create')->name('employer-imports.store');

                Route::get('employer-imports/{batch}', [EmployerImportController::class, 'show'])->middleware('permission:updates.employer-imports.view')->name('employer-imports.show');

                Route::post('employer-imports/{batch}/validate', [EmployerImportController::class, 'validateImport'])->middleware('permission:updates.employer-imports.create')->name('employer-imports.validate');

                Route::post('employer-imports/{batch}/approve-valid', [EmployerImportController::class, 'approveValid'])->middleware('permission:updates.employer-imports.approve')->name('employer-imports.approve-valid');

                Route::post('employer-imports/{batch}/import', [EmployerImportController::class, 'importApproved'])->middleware('permission:updates.employer-imports.post')->name('employer-imports.import');

                Route::get('employer-imports/{batch}/status', [EmployerImportController::class, 'status'])->middleware('permission:updates.employer-imports.view')->name('employer-imports.status');

                Route::get('employer-imports/{batch}/review', [EmployerImportController::class, 'review'])->middleware('permission:updates.employer-imports.review')->name('employer-imports.review');

                Route::delete('employer-imports/{batch}', [EmployerImportController::class, 'destroy'])->middleware('permission:updates.employer-imports.delete')->name('employer-imports.destroy');


                /*
                |--------------------------------------------------------------------------
                | Members
                |--------------------------------------------------------------------------
                */

                Route::get('members', [MemberController::class, 'index'])->middleware('permission:updates.members.view')->name('members.index');

                Route::get('members/create', [MemberController::class, 'create'])->middleware('permission:updates.members.create')->name('members.create');

                Route::post('members', [MemberController::class, 'store'])->middleware('permission:updates.members.create')->name('members.store');

                Route::get('members/{member}', [MemberController::class, 'show'])->middleware('permission:updates.members.view')->name('members.show');

                Route::get('members/{member}/edit', [MemberController::class, 'edit'])->middleware('permission:updates.members.update')->name('members.edit');

                Route::put('members/{member}', [MemberController::class, 'update'])->middleware('permission:updates.members.update')->name('members.update');

                Route::delete('members/{member}', [MemberController::class, 'destroy'])->middleware('permission:updates.members.delete')->name('members.destroy');


                /*
                |--------------------------------------------------------------------------
                | Membership Imports
                |--------------------------------------------------------------------------
                */

                Route::get('imports', [MembershipImportController::class, 'index'])->middleware('permission:updates.membership-imports.view')->name('imports.index');

                Route::get('imports/create', [MembershipImportController::class, 'create'])->middleware('permission:updates.membership-imports.create')->name('imports.create');

                Route::post('imports', [MembershipImportController::class, 'store'])->middleware('permission:updates.membership-imports.create')->name('imports.store');

                Route::post('imports/{batch}/validate', [MembershipImportController::class, 'validateImport'])->middleware('permission:updates.membership-imports.create')->name('imports.validate');

                Route::get('imports/{batch}/status', [MembershipImportController::class, 'status'])->middleware('permission:updates.membership-imports.view')->name('imports.status');


                /*
                |--------------------------------------------------------------------------
                | Membership Import Review Data
                |--------------------------------------------------------------------------
                */

                Route::get('imports/{batch}/review/data', [MembershipImportDataController::class, 'data'])->middleware('permission:updates.membership-imports.review')->name('imports.review.data');

                Route::get('imports/{batch}/review/exceptions', [MembershipImportDataController::class, 'exceptions'])->middleware('permission:updates.membership-imports.review')->name('imports.review.exceptions');

                Route::get('imports/{batch}/review/exceptions/download', [MembershipImportDataController::class, 'downloadExceptions'])->middleware('permission:updates.membership-imports.review')->name('imports.review.exceptions.download');


                /*
                |--------------------------------------------------------------------------
                | Membership Import Review
                |--------------------------------------------------------------------------
                */

                Route::get('imports/{batch}/review', [MembershipImportReviewController::class, 'index'])->middleware('permission:updates.membership-imports.review')->name('imports.review');

                Route::get('imports/{batch}/rows/{row}/edit', [MembershipImportReviewController::class, 'edit'])->middleware('permission:updates.membership-imports.update')->name('imports.rows.edit');

                Route::put('imports/{batch}/rows/{row}', [MembershipImportReviewController::class, 'update'])->middleware('permission:updates.membership-imports.update')->name('imports.rows.update');

                Route::post('imports/{batch}/rows/{row}/decision', [MembershipImportReviewController::class, 'decision'])->middleware('permission:updates.membership-imports.review')->name('imports.rows.decision');

                Route::post('imports/{batch}/approve-valid', [MembershipImportReviewController::class, 'approveValid'])->middleware('permission:updates.membership-imports.approve')->name('imports.approve-valid');

                Route::post('imports/{batch}/reject-errors', [MembershipImportReviewController::class, 'rejectErrors'])->middleware('permission:updates.membership-imports.review')->name('imports.reject-errors');

                Route::delete('imports/{batch}/rows/{row}/remove', [MembershipImportReviewController::class, 'removeRow'])->middleware('permission:updates.membership-imports.update')->name('imports.rows.remove');


                /*
                |--------------------------------------------------------------------------
                | Membership Import Commit
                |--------------------------------------------------------------------------
                */

                Route::post('imports/{batch}/import', [MembershipImportCommitController::class, 'store'])->middleware('permission:updates.membership-imports.post')->name('imports.import');

                Route::get('imports/{batch}/import-status', [MembershipImportCommitController::class, 'status'])->middleware('permission:updates.membership-imports.view')->name('imports.import-status');


                /*
                |--------------------------------------------------------------------------
                | Membership Import Delete / Show
                |--------------------------------------------------------------------------
                */

                Route::delete('imports/{batch}', [MembershipImportController::class, 'destroy'])->middleware('permission:updates.membership-imports.delete')->name('imports.destroy');

                Route::get('imports/{batch}', [MembershipImportController::class, 'show'])->middleware('permission:updates.membership-imports.view')->name('imports.show');

            });


            /*
            |--------------------------------------------------------------------------
            | Contributions
            |--------------------------------------------------------------------------
            */

            Route::prefix('contributions')->name('contributions.')->group(function (): void {


                /*
                |--------------------------------------------------------------------------
                | Contribution Import Listing / Upload
                |--------------------------------------------------------------------------
                */

                Route::get('imports', [ContributionImportController::class, 'index'])->middleware('permission:contributions.monthly-imports.view')->name('imports.index');

                Route::get('imports/create', [ContributionImportController::class, 'create'])->middleware('permission:contributions.monthly-imports.create')->name('imports.create');

                Route::post('imports', [ContributionImportController::class, 'store'])->middleware('permission:contributions.monthly-imports.create')->name('imports.store');


                /*
                |--------------------------------------------------------------------------
                | Contribution Import Review
                |--------------------------------------------------------------------------
                */

                Route::get('imports/{batch}/review', [ContributionImportReviewController::class, 'index'])->middleware('permission:contributions.monthly-imports.view')->name('imports.review');


                /*
                |--------------------------------------------------------------------------
                | Review Exports
                |--------------------------------------------------------------------------
                */

                Route::get('imports/{batch}/new-members/export', [ContributionReviewActionController::class, 'exportNewMembers'])->middleware('permission:contributions.reports.view')->name('imports.new-members.export');

                Route::get('imports/{batch}/nil-contributors/export', [ContributionReviewActionController::class, 'exportNilContributors'])->middleware('permission:contributions.reports.view')->name('imports.nil-contributors.export');

                Route::get('imports/{batch}/reinstatements/export', [ContributionReviewActionController::class, 'exportReinstatements'])->middleware('permission:contributions.reports.view')->name('imports.reinstatements.export');


                /*
                |--------------------------------------------------------------------------
                | Contribution Validation Exceptions
                |--------------------------------------------------------------------------
                |
                | These warnings do NOT block approval.
                |
                | show:
                |   pensions-administration.contributions.imports.exceptions
                |
                | excel:
                |   pensions-administration.contributions.imports.exceptions.excel
                |
                */

                Route::get('imports/{batch}/exceptions', [ContributionExceptionReportController::class, 'show'])->middleware('permission:contributions.reports.view')->name('imports.exceptions');

                Route::get('imports/{batch}/exceptions/excel', [ContributionExceptionReportController::class, 'excel'])->middleware('permission:contributions.reports.view')->name('imports.exceptions.excel');


                /*
                |--------------------------------------------------------------------------
                | Backward-Compatible Contribution Exception Export Route
                |--------------------------------------------------------------------------
                |
                | Keep this route because some earlier Blade code may still use:
                |
                | pensions-administration.contributions.imports.contribution-exceptions.export
                |
                */

                Route::get('imports/{batch}/contribution-exceptions/export', [ContributionExceptionReportController::class, 'excel'])->middleware('permission:contributions.reports.view')->name('imports.contribution-exceptions.export');


                /*
                |--------------------------------------------------------------------------
                | Contribution Reconciliation
                |--------------------------------------------------------------------------
                */

                Route::get('imports/{batch}/reconciliation', [ContributionReconciliationController::class, 'show'])->middleware('permission:contributions.reports.view')->name('reconciliation.show');

                Route::get('imports/{batch}/reconciliation/pdf', [ContributionReconciliationController::class, 'pdf'])->middleware('permission:contributions.reports.view')->name('reconciliation.pdf');

                Route::get('imports/{batch}/reconciliation/pdf/download', [ContributionReconciliationController::class, 'downloadPdf'])->middleware('permission:contributions.reports.view')->name('reconciliation.pdf.download');

                Route::get('imports/{batch}/reconciliation/excel', [ContributionReconciliationController::class, 'excel'])->middleware('permission:contributions.reports.view')->name('reconciliation.excel');


                /*
                |--------------------------------------------------------------------------
                | Contribution Approval / Rejection
                |--------------------------------------------------------------------------
                */

                Route::post('imports/{batch}/approve', [ContributionPostingController::class, 'approve'])->middleware('permission:contributions.monthly-imports.approve')->name('imports.approve');

                Route::post('imports/{batch}/reject', [ContributionReviewActionController::class, 'reject'])->middleware('permission:contributions.monthly-imports.reject')->name('imports.reject');


                /*
                |--------------------------------------------------------------------------
                | Contribution Posting
                |--------------------------------------------------------------------------
                */

                Route::post('imports/{batch}/post', [ContributionPostingController::class, 'post'])->middleware('permission:contributions.monthly-imports.post')->name('imports.post');

                Route::get('imports/{batch}/posting', [ContributionPostingController::class, 'posting'])->middleware('permission:contributions.monthly-imports.view')->name('imports.posting');

                Route::get('imports/{batch}/posting-status', [ContributionPostingController::class, 'postingStatus'])->middleware('permission:contributions.monthly-imports.view')->name('imports.posting-status');


                /*
                |--------------------------------------------------------------------------
                | Import Validation Status
                |--------------------------------------------------------------------------
                */

                Route::get('imports/{batch}/status', [ContributionImportController::class, 'status'])->middleware('permission:contributions.monthly-imports.view')->name('imports.status');


                /*
                |--------------------------------------------------------------------------
                | Delete Contribution Import
                |--------------------------------------------------------------------------
                */

                Route::delete('imports/{batch}', [ContributionImportController::class, 'destroy'])->middleware('permission:contributions.monthly-imports.delete')->name('imports.destroy');


                /*
                |--------------------------------------------------------------------------
                | Contribution Batch Show
                |--------------------------------------------------------------------------
                |
                | Keep this last so that more specific /imports/{batch}/...
                | routes are matched first.
                |
                */

                Route::get('imports/{batch}', [ContributionImportController::class, 'show'])->middleware('permission:contributions.monthly-imports.view')->name('imports.show');

            });

        });


        /*
        |--------------------------------------------------------------------------
        | Audit & Security
        |--------------------------------------------------------------------------
        */

        Route::prefix('audit')->name('audit.')->group(function (): void {

            Route::get('/audit-trails', [AuditTrailController::class, 'index'])->middleware('permission:audit.audit-trails.view')->name('audit-trails.index');

            Route::get('/audit-trails/{auditTrail}', [AuditTrailController::class, 'show'])->middleware('permission:audit.audit-trails.view')->name('audit-trails.show');

            Route::get('/user-sessions', [UserSessionController::class, 'index'])->middleware('permission:audit.user-sessions.view')->name('user-sessions.index');

            Route::get('/user-sessions/{userSession}', [UserSessionController::class, 'show'])->middleware('permission:audit.user-sessions.view')->name('user-sessions.show');

            Route::get('/login-attempts', [LoginAttemptController::class, 'index'])->middleware('permission:audit.login-attempts.view')->name('login-attempts.index');

            Route::get('/login-attempts/{loginAttempt}', [LoginAttemptController::class, 'show'])->middleware('permission:audit.login-attempts.view')->name('login-attempts.show');

        });


        /*
        |--------------------------------------------------------------------------
        | User Management
        |--------------------------------------------------------------------------
        */

        Route::prefix('user-management')->name('user-management.')->group(function (): void {


            /*
            |--------------------------------------------------------------------------
            | Users
            |--------------------------------------------------------------------------
            */

            Route::get('/users', [UserController::class, 'index'])->middleware('permission:user-management.users.view')->name('users.index');

            Route::get('/users/create', [UserController::class, 'create'])->middleware('permission:user-management.users.create')->name('users.create');

            Route::post('/users', [UserController::class, 'store'])->middleware('permission:user-management.users.create')->name('users.store');

            Route::get('/users/{user}', [UserController::class, 'show'])->middleware('permission:user-management.users.view')->name('users.show');

            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:user-management.users.update')->name('users.edit');

            Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:user-management.users.update')->name('users.update');

            Route::patch('/users/{user}/activate', [UserController::class, 'activate'])->middleware('permission:user-management.users.activate')->name('users.activate');

            Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate'])->middleware('permission:user-management.users.deactivate')->name('users.deactivate');

            Route::put('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('permission:user-management.users.reset-password')->name('users.reset-password');


            /*
            |--------------------------------------------------------------------------
            | Roles
            |--------------------------------------------------------------------------
            */

            Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:user-management.roles.view')->name('roles.index');

            Route::get('/roles/create', [RoleController::class, 'create'])->middleware('permission:user-management.roles.create')->name('roles.create');

            Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:user-management.roles.create')->name('roles.store');

            Route::get('/roles/{role}', [RoleController::class, 'show'])->middleware('permission:user-management.roles.view')->name('roles.show');

            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:user-management.roles.update')->name('roles.edit');

            Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:user-management.roles.update')->name('roles.update');

            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:user-management.roles.delete')->name('roles.destroy');


            /*
            |--------------------------------------------------------------------------
            | Permissions
            |--------------------------------------------------------------------------
            */

            Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:user-management.permissions.view')->name('permissions.index');

            Route::get('/permissions/{permission}', [PermissionController::class, 'show'])->middleware('permission:user-management.permissions.view')->name('permissions.show');


            /*
            |--------------------------------------------------------------------------
            | Organisation Units
            |--------------------------------------------------------------------------
            */

            Route::get('/organisation-units', [OrganisationUnitController::class, 'index'])->middleware('permission:user-management.organisation-units.view')->name('organisation-units.index');

            Route::get('/organisation-units/create', [OrganisationUnitController::class, 'create'])->middleware('permission:user-management.organisation-units.create')->name('organisation-units.create');

            Route::post('/organisation-units', [OrganisationUnitController::class, 'store'])->middleware('permission:user-management.organisation-units.create')->name('organisation-units.store');

            Route::get('/organisation-units/{organisation_unit}', [OrganisationUnitController::class, 'show'])->middleware('permission:user-management.organisation-units.view')->name('organisation-units.show');

            Route::get('/organisation-units/{organisation_unit}/edit', [OrganisationUnitController::class, 'edit'])->middleware('permission:user-management.organisation-units.update')->name('organisation-units.edit');

            Route::put('/organisation-units/{organisation_unit}', [OrganisationUnitController::class, 'update'])->middleware('permission:user-management.organisation-units.update')->name('organisation-units.update');

            Route::delete('/organisation-units/{organisation_unit}', [OrganisationUnitController::class, 'destroy'])->middleware('permission:user-management.organisation-units.delete')->name('organisation-units.destroy');


            /*
            |--------------------------------------------------------------------------
            | Job Titles
            |--------------------------------------------------------------------------
            */

            Route::get('/job-titles', [JobTitleController::class, 'index'])->middleware('permission:user-management.job-titles.view')->name('job-titles.index');

            Route::get('/job-titles/create', [JobTitleController::class, 'create'])->middleware('permission:user-management.job-titles.create')->name('job-titles.create');

            Route::post('/job-titles', [JobTitleController::class, 'store'])->middleware('permission:user-management.job-titles.create')->name('job-titles.store');

            Route::get('/job-titles/{job_title}', [JobTitleController::class, 'show'])->middleware('permission:user-management.job-titles.view')->name('job-titles.show');

            Route::get('/job-titles/{job_title}/edit', [JobTitleController::class, 'edit'])->middleware('permission:user-management.job-titles.update')->name('job-titles.edit');

            Route::put('/job-titles/{job_title}', [JobTitleController::class, 'update'])->middleware('permission:user-management.job-titles.update')->name('job-titles.update');


            /*
            |--------------------------------------------------------------------------
            | Grades
            |--------------------------------------------------------------------------
            */

            Route::get('/grades', [GradeController::class, 'index'])->middleware('permission:user-management.grades.view')->name('grades.index');

            Route::get('/grades/create', [GradeController::class, 'create'])->middleware('permission:user-management.grades.create')->name('grades.create');

            Route::post('/grades', [GradeController::class, 'store'])->middleware('permission:user-management.grades.create')->name('grades.store');

            Route::get('/grades/{grade}', [GradeController::class, 'show'])->middleware('permission:user-management.grades.view')->name('grades.show');

            Route::get('/grades/{grade}/edit', [GradeController::class, 'edit'])->middleware('permission:user-management.grades.update')->name('grades.edit');

            Route::put('/grades/{grade}', [GradeController::class, 'update'])->middleware('permission:user-management.grades.update')->name('grades.update');


            /*
            |--------------------------------------------------------------------------
            | Password Policy
            |--------------------------------------------------------------------------
            */

            Route::get('/password-policy', [PasswordPolicyController::class, 'edit'])->middleware('permission:user-management.password-policies.view')->name('password-policies.edit');

            Route::put('/password-policy', [PasswordPolicyController::class, 'update'])->middleware('permission:user-management.password-policies.update')->name('password-policies.update');

            Route::get('/password-policy/report', [PasswordPolicyController::class, 'report'])->middleware('permission:user-management.password-policies.view')->name('password-policies.report');

        });

    });

});
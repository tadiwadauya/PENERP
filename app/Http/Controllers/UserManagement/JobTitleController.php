<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\UserManagement\JobTitle;
use App\Models\UserManagement\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobTitleController extends Controller
{
    /**
     * Display all job titles.
     */
    public function index(): View
    {
        $this->ensurePermission(
            'user-management.job-titles.view'
        );

        $jobTitles = JobTitle::query()
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Employee Counts
        |--------------------------------------------------------------------------
        |
        | Count users separately so we do not depend on a users()
        | relationship existing on the JobTitle model.
        |
        */

        $employeeCounts = User::query()
            ->selectRaw(
                'job_title_id, COUNT(*) as total'
            )
            ->whereNotNull('job_title_id')
            ->groupBy('job_title_id')
            ->pluck(
                'total',
                'job_title_id'
            );

        $summary = [
            'total' =>
                $jobTitles->count(),

            'active' =>
                $jobTitles
                    ->where('is_active', true)
                    ->count(),

            'inactive' =>
                $jobTitles
                    ->where('is_active', false)
                    ->count(),

            'employees' =>
                (int) $employeeCounts->sum(),
        ];

        return view(
            'user-management.job-titles.index',
            compact(
                'jobTitles',
                'employeeCounts',
                'summary'
            )
        );
    }


    /**
     * Show create form.
     */
    public function create(): View
    {
        $this->ensurePermission(
            'user-management.job-titles.create'
        );

        return view(
            'user-management.job-titles.create'
        );
    }


    /**
     * Store job title.
     */
    public function store(
        Request $request
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.job-titles.create'
        );

        $validated =
            $this->validateJobTitle(
                $request
            );

        DB::transaction(
            function () use (
                $validated
            ): void {

                JobTitle::create(
                    $validated
                );
            }
        );

        return redirect()
            ->route(
                'user-management.job-titles.index'
            )
            ->with(
                'success',
                'Job title created successfully.'
            );
    }


    /**
     * Resource show route redirects to edit.
     */
    public function show(
        JobTitle $jobTitle
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.job-titles.view'
        );

        return redirect()
            ->route(
                'user-management.job-titles.edit',
                $jobTitle
            );
    }


    /**
     * Show edit form.
     */
    public function edit(
        JobTitle $jobTitle
    ): View {
        $this->ensurePermission(
            'user-management.job-titles.update'
        );

        $employeeCount =
            User::query()
                ->where(
                    'job_title_id',
                    $jobTitle->id
                )
                ->count();

        return view(
            'user-management.job-titles.edit',
            compact(
                'jobTitle',
                'employeeCount'
            )
        );
    }


    /**
     * Update job title.
     */
    public function update(
        Request $request,
        JobTitle $jobTitle
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.job-titles.update'
        );

        $validated =
            $this->validateJobTitle(
                $request,
                $jobTitle
            );

        DB::transaction(
            function () use (
                $jobTitle,
                $validated
            ): void {

                $jobTitle->update(
                    $validated
                );
            }
        );

        return redirect()
            ->route(
                'user-management.job-titles.edit',
                $jobTitle
            )
            ->with(
                'success',
                'Job title updated successfully.'
            );
    }


    /**
     * Delete job title.
     */
    public function destroy(
        JobTitle $jobTitle
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.job-titles.delete'
        );

        /*
        |--------------------------------------------------------------------------
        | Prevent deletion when assigned to employees
        |--------------------------------------------------------------------------
        */

        $employeeCount =
            User::query()
                ->where(
                    'job_title_id',
                    $jobTitle->id
                )
                ->count();

        if ($employeeCount > 0) {
            return back()
                ->with(
                    'error',
                    'This job title cannot be deleted because it is currently assigned to '
                    . $employeeCount
                    . ' employee(s).'
                );
        }

        DB::transaction(
            function () use (
                $jobTitle
            ): void {

                $jobTitle->delete();
            }
        );

        return redirect()
            ->route(
                'user-management.job-titles.index'
            )
            ->with(
                'success',
                'Job title deleted successfully.'
            );
    }


    /**
     * Validate job title.
     */
    private function validateJobTitle(
        Request $request,
        ?JobTitle $jobTitle = null
    ): array {
        return $request->validate(
            [
                'code' => [
                    'required',
                    'string',
                    'max:50',

                    Rule::unique(
                        'job_titles',
                        'code'
                    )->ignore(
                        $jobTitle?->id
                    ),
                ],

                'name' => [
                    'required',
                    'string',
                    'max:150',
                ],

                'description' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],

                'is_active' => [
                    'required',
                    'boolean',
                ],
            ],
            [
                'code.required' =>
                    'The job title code is required.',

                'code.unique' =>
                    'This job title code is already in use.',

                'name.required' =>
                    'The job title name is required.',
            ]
        );
    }


    /**
     * Controller-level permission enforcement.
     */
    private function ensurePermission(
        string $permission
    ): void {
        $user =
            auth()->user();

        abort_if(
            !$user,
            401,
            'Unauthenticated.'
        );

        /*
        |--------------------------------------------------------------------------
        | System Administrator
        |--------------------------------------------------------------------------
        */

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
<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\UserManagement\Grade;
use App\Models\UserManagement\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GradeController extends Controller
{
    /**
     * Display all grades.
     */
    public function index(): View
    {
        $this->ensurePermission(
            'user-management.grades.view'
        );

        $grades = Grade::query()
            ->orderBy('rank_order')
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Employee Counts
        |--------------------------------------------------------------------------
        */

        $employeeCounts = User::query()
            ->selectRaw(
                'grade_id, COUNT(*) as total'
            )
            ->whereNotNull('grade_id')
            ->groupBy('grade_id')
            ->pluck(
                'total',
                'grade_id'
            );

        $summary = [
            'total' =>
                $grades->count(),

            'active' =>
                $grades
                    ->where('is_active', true)
                    ->count(),

            'inactive' =>
                $grades
                    ->where('is_active', false)
                    ->count(),

            'employees' =>
                (int) $employeeCounts->sum(),
        ];

        return view(
            'user-management.grades.index',
            compact(
                'grades',
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
            'user-management.grades.create'
        );

        return view(
            'user-management.grades.create'
        );
    }


    /**
     * Store grade.
     */
    public function store(
        Request $request
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.grades.create'
        );

        $validated =
            $this->validateGrade(
                $request
            );

        DB::transaction(
            function () use (
                $validated
            ): void {

                Grade::create(
                    $validated
                );
            }
        );

        return redirect()
            ->route(
                'user-management.grades.index'
            )
            ->with(
                'success',
                'Grade created successfully.'
            );
    }


    /**
     * Redirect show to edit.
     */
    public function show(
        Grade $grade
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.grades.view'
        );

        return redirect()
            ->route(
                'user-management.grades.edit',
                $grade
            );
    }


    /**
     * Show edit form.
     */
    public function edit(
        Grade $grade
    ): View {
        $this->ensurePermission(
            'user-management.grades.update'
        );

        $employeeCount = User::query()
            ->where(
                'grade_id',
                $grade->id
            )
            ->count();

        return view(
            'user-management.grades.edit',
            compact(
                'grade',
                'employeeCount'
            )
        );
    }


    /**
     * Update grade.
     */
    public function update(
        Request $request,
        Grade $grade
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.grades.update'
        );

        $validated =
            $this->validateGrade(
                $request,
                $grade
            );

        DB::transaction(
            function () use (
                $grade,
                $validated
            ): void {

                $grade->update(
                    $validated
                );
            }
        );

        return redirect()
            ->route(
                'user-management.grades.edit',
                $grade
            )
            ->with(
                'success',
                'Grade updated successfully.'
            );
    }


    /**
     * Delete grade.
     */
    public function destroy(
        Grade $grade
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.grades.delete'
        );

        $employeeCount = User::query()
            ->where(
                'grade_id',
                $grade->id
            )
            ->count();

        if ($employeeCount > 0) {
            return back()
                ->with(
                    'error',
                    'This grade cannot be deleted because '
                    . $employeeCount
                    . ' employee(s) are currently assigned to it.'
                );
        }

        DB::transaction(
            function () use (
                $grade
            ): void {

                $grade->delete();
            }
        );

        return redirect()
            ->route(
                'user-management.grades.index'
            )
            ->with(
                'success',
                'Grade deleted successfully.'
            );
    }


    /**
     * Validate grade data.
     */
    private function validateGrade(
        Request $request,
        ?Grade $grade = null
    ): array {
        return $request->validate(
            [
                'code' => [
                    'required',
                    'string',
                    'max:50',

                    Rule::unique(
                        'grades',
                        'code'
                    )->ignore(
                        $grade?->id
                    ),
                ],

                'name' => [
                    'required',
                    'string',
                    'max:150',
                ],

                'rank_order' => [
                    'required',
                    'integer',
                    'min:1',
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
                    'The grade code is required.',

                'code.unique' =>
                    'This grade code is already in use.',

                'name.required' =>
                    'The grade name is required.',

                'rank_order.required' =>
                    'The grade rank/order is required.',

                'rank_order.integer' =>
                    'The grade rank/order must be a number.',

                'rank_order.min' =>
                    'The grade rank/order must be at least 1.',
            ]
        );
    }


    /**
     * Enforce permissions.
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
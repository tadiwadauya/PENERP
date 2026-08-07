<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\UserManagement\OrganisationUnit;
use App\Models\UserManagement\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganisationUnitController extends Controller
{
    /**
     * Display LAPF organisation structure.
     */
    public function index(): View
    {
        $this->ensurePermission(
            'user-management.organisation-units.view'
        );

        /*
        |--------------------------------------------------------------------------
        | Organisation Units
        |--------------------------------------------------------------------------
        */

        $organisationUnits = OrganisationUnit::query()
            ->orderBy('type')
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | User Counts
        |--------------------------------------------------------------------------
        |
        | We calculate this separately so the controller does not depend on
        | OrganisationUnit having a users() relationship.
        |
        */

        $userCounts = User::query()
            ->selectRaw(
                'organisation_unit_id, COUNT(*) as total'
            )
            ->whereNotNull('organisation_unit_id')
            ->groupBy('organisation_unit_id')
            ->pluck(
                'total',
                'organisation_unit_id'
            );


        /*
        |--------------------------------------------------------------------------
        | Child Counts
        |--------------------------------------------------------------------------
        */

        $childCounts = OrganisationUnit::query()
            ->selectRaw(
                'parent_id, COUNT(*) as total'
            )
            ->whereNotNull('parent_id')
            ->groupBy('parent_id')
            ->pluck(
                'total',
                'parent_id'
            );


        /*
        |--------------------------------------------------------------------------
        | Lookup
        |--------------------------------------------------------------------------
        */

        $unitLookup =
            $organisationUnits->keyBy('id');


        /*
        |--------------------------------------------------------------------------
        | Summary Counts
        |--------------------------------------------------------------------------
        */

        $summary = [
            'total' =>
                $organisationUnits->count(),

            'principal_offices' =>
                $organisationUnits
                    ->where(
                        'type',
                        'principal_office'
                    )
                    ->count(),

            'departments' =>
                $organisationUnits
                    ->where(
                        'type',
                        'department'
                    )
                    ->count(),

            'sections' =>
                $organisationUnits
                    ->where(
                        'type',
                        'section'
                    )
                    ->count(),

            'active' =>
                $organisationUnits
                    ->where(
                        'is_active',
                        true
                    )
                    ->count(),
        ];


        return view(
            'user-management.organisation-units.index',
            compact(
                'organisationUnits',
                'unitLookup',
                'userCounts',
                'childCounts',
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
            'user-management.organisation-units.create'
        );


        $parentUnits = OrganisationUnit::query()
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();


        return view(
            'user-management.organisation-units.create',
            compact(
                'parentUnits'
            )
        );
    }


    /**
     * Store organisation unit.
     */
    public function store(
        Request $request
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.organisation-units.create'
        );


        $validated =
            $this->validateOrganisationUnit(
                $request
            );


        /*
        |--------------------------------------------------------------------------
        | Principal Office Validation
        |--------------------------------------------------------------------------
        |
        | A principal office is a root-level structure and therefore should not
        | report to another organisation unit.
        |
        */

        if (
            $validated['type']
            === 'principal_office'
        ) {
            $validated['parent_id'] = null;
        }


        DB::transaction(
            function () use (
                $validated
            ): void {

                OrganisationUnit::create(
                    $validated
                );

            }
        );


        return redirect()
            ->route(
                'user-management.organisation-units.index'
            )
            ->with(
                'success',
                'Organisation unit created successfully.'
            );
    }


    /**
     * Redirect show to edit.
     */
    public function show(
        OrganisationUnit $organisationUnit
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.organisation-units.view'
        );


        return redirect()
            ->route(
                'user-management.organisation-units.edit',
                $organisationUnit
            );
    }


    /**
     * Show edit form.
     */
    public function edit(
        OrganisationUnit $organisationUnit
    ): View {
        $this->ensurePermission(
            'user-management.organisation-units.update'
        );


        /*
        |--------------------------------------------------------------------------
        | Descendants
        |--------------------------------------------------------------------------
        |
        | A unit must not be allowed to report to itself or to one of its
        | descendants because that would create a circular hierarchy.
        |
        */

        $excludedIds =
            $this->getDescendantIds(
                $organisationUnit
            );

        $excludedIds[] =
            $organisationUnit->id;


        $parentUnits = OrganisationUnit::query()
            ->where('is_active', true)
            ->whereNotIn(
                'id',
                $excludedIds
            )
            ->orderBy('type')
            ->orderBy('name')
            ->get();


        $employeeCount =
            User::query()
                ->where(
                    'organisation_unit_id',
                    $organisationUnit->id
                )
                ->count();


        $childCount =
            OrganisationUnit::query()
                ->where(
                    'parent_id',
                    $organisationUnit->id
                )
                ->count();


        return view(
            'user-management.organisation-units.edit',
            compact(
                'organisationUnit',
                'parentUnits',
                'employeeCount',
                'childCount'
            )
        );
    }


    /**
     * Update organisation unit.
     */
    public function update(
        Request $request,
        OrganisationUnit $organisationUnit
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.organisation-units.update'
        );


        $validated =
            $this->validateOrganisationUnit(
                $request,
                $organisationUnit
            );


        /*
        |--------------------------------------------------------------------------
        | Principal Office
        |--------------------------------------------------------------------------
        */

        if (
            $validated['type']
            === 'principal_office'
        ) {
            $validated['parent_id'] = null;
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent Self Reporting
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $validated['parent_id']
            )
            &&
            (int) $validated['parent_id']
            === (int) $organisationUnit->id
        ) {
            return back()
                ->withErrors([
                    'parent_id' =>
                        'An organisation unit cannot report to itself.',
                ])
                ->withInput();
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent Circular Hierarchy
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $validated['parent_id']
            )
        ) {
            $descendantIds =
                $this->getDescendantIds(
                    $organisationUnit
                );


            if (
                in_array(
                    (int) $validated['parent_id'],
                    $descendantIds,
                    true
                )
            ) {
                return back()
                    ->withErrors([
                        'parent_id' =>
                            'This reporting structure would create a circular organisation hierarchy.',
                    ])
                    ->withInput();
            }
        }


        DB::transaction(
            function () use (
                $organisationUnit,
                $validated
            ): void {

                $organisationUnit->update(
                    $validated
                );

            }
        );


        return redirect()
            ->route(
                'user-management.organisation-units.edit',
                $organisationUnit
            )
            ->with(
                'success',
                'Organisation unit updated successfully.'
            );
    }


    /**
     * Delete organisation unit.
     */
    public function destroy(
        OrganisationUnit $organisationUnit
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.organisation-units.delete'
        );


        /*
        |--------------------------------------------------------------------------
        | Assigned Employees
        |--------------------------------------------------------------------------
        */

        $employeeCount =
            User::query()
                ->where(
                    'organisation_unit_id',
                    $organisationUnit->id
                )
                ->count();


        if (
            $employeeCount > 0
        ) {
            return back()
                ->with(
                    'error',
                    'This organisation unit cannot be deleted because '
                    . $employeeCount
                    . ' employee(s) are still assigned to it.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Child Units
        |--------------------------------------------------------------------------
        */

        $childCount =
            OrganisationUnit::query()
                ->where(
                    'parent_id',
                    $organisationUnit->id
                )
                ->count();


        if (
            $childCount > 0
        ) {
            return back()
                ->with(
                    'error',
                    'This organisation unit cannot be deleted because it has '
                    . $childCount
                    . ' child organisation unit(s). Reassign or remove them first.'
                );
        }


        DB::transaction(
            function () use (
                $organisationUnit
            ): void {

                $organisationUnit->delete();

            }
        );


        return redirect()
            ->route(
                'user-management.organisation-units.index'
            )
            ->with(
                'success',
                'Organisation unit deleted successfully.'
            );
    }


    /**
     * Validate organisation unit.
     */
    private function validateOrganisationUnit(
        Request $request,
        ?OrganisationUnit $organisationUnit = null
    ): array {
        return $request->validate(
            [
                'code' => [
                    'required',
                    'string',
                    'max:50',

                    Rule::unique(
                        'organisation_units',
                        'code'
                    )->ignore(
                        $organisationUnit?->id
                    ),
                ],

                'name' => [
                    'required',
                    'string',
                    'max:150',
                ],

                'type' => [
                    'required',
                    Rule::in([
                        'principal_office',
                        'department',
                        'section',
                    ]),
                ],

                'parent_id' => [
                    'nullable',
                    'integer',
                    'exists:organisation_units,id',
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
                    'The organisation unit code is required.',

                'code.unique' =>
                    'This organisation unit code is already in use.',

                'name.required' =>
                    'The organisation unit name is required.',

                'type.required' =>
                    'Please select the organisation unit type.',

                'parent_id.exists' =>
                    'The selected reporting organisation unit does not exist.',
            ]
        );
    }


    /**
     * Recursively find descendants.
     */
    private function getDescendantIds(
        OrganisationUnit $organisationUnit
    ): array {
        $allUnits =
            OrganisationUnit::query()
                ->select([
                    'id',
                    'parent_id',
                ])
                ->get();


        $childrenByParent =
            $allUnits->groupBy(
                'parent_id'
            );


        $descendants = [];


        $walk =
            function (
                int $parentId
            ) use (
                &$walk,
                &$descendants,
                $childrenByParent
            ): void {

                $children =
                    $childrenByParent
                        ->get(
                            $parentId,
                            collect()
                        );


                foreach (
                    $children
                    as $child
                ) {
                    $descendants[] =
                        (int) $child->id;


                    $walk(
                        (int) $child->id
                    );
                }

            };


        $walk(
            (int) $organisationUnit->id
        );


        return array_values(
            array_unique(
                $descendants
            )
        );
    }


    /**
     * Controller-level permission enforcement.
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
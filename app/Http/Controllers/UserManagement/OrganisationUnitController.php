<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\UserManagement\Dashboard;
use App\Models\UserManagement\OrganisationUnit;
use App\Models\UserManagement\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganisationUnitController extends Controller
{
    public function index(): View
    {
        $this->ensurePermission(
            'user-management.organisation-units.view'
        );

        $organisationUnits = OrganisationUnit::query()
            ->with('dashboard')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $unitLookup =
            $organisationUnits->keyBy('id');

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

        $summary = [
            'total' =>
                $organisationUnits->count(),

            'departments' =>
                $organisationUnits
                    ->where('unit_type', 'department')
                    ->count(),

            'sections' =>
                $organisationUnits
                    ->where('unit_type', 'section')
                    ->count(),

            'offices' =>
                $organisationUnits
                    ->where('unit_type', 'office')
                    ->count(),

            'active' =>
                $organisationUnits
                    ->where('is_active', true)
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


    public function create(): View
    {
        $this->ensurePermission(
            'user-management.organisation-units.create'
        );

        $parentUnits = OrganisationUnit::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $dashboards = Dashboard::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'user-management.organisation-units.create',
            compact(
                'parentUnits',
                'dashboards'
            )
        );
    }


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

        $validated['created_by'] =
            auth()->id();

        $validated['updated_by'] =
            auth()->id();

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


    public function edit(
        OrganisationUnit $organisationUnit
    ): View {
        $this->ensurePermission(
            'user-management.organisation-units.update'
        );

        $excludedIds =
            $this->getDescendantIds(
                $organisationUnit
            );

        $excludedIds[] =
            (int) $organisationUnit->id;

        $parentUnits = OrganisationUnit::query()
            ->where('is_active', true)
            ->whereNotIn(
                'id',
                $excludedIds
            )
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $dashboards = Dashboard::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $employeeCount = User::query()
            ->where(
                'organisation_unit_id',
                $organisationUnit->id
            )
            ->count();

        $childCount = OrganisationUnit::query()
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
                'dashboards',
                'employeeCount',
                'childCount'
            )
        );
    }


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

        if (
            !empty($validated['parent_id'])
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

        if (
            !empty($validated['parent_id'])
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
                            'This reporting relationship would create a circular organisation structure.',
                    ])
                    ->withInput();
            }
        }

        $validated['updated_by'] =
            auth()->id();

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


    public function destroy(
        OrganisationUnit $organisationUnit
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.organisation-units.delete'
        );

        $employeeCount = User::query()
            ->where(
                'organisation_unit_id',
                $organisationUnit->id
            )
            ->count();

        if ($employeeCount > 0) {
            return back()
                ->with(
                    'error',
                    'This organisation unit cannot be deleted because '
                    . $employeeCount
                    . ' employee(s) are still assigned to it.'
                );
        }

        $childCount = OrganisationUnit::query()
            ->where(
                'parent_id',
                $organisationUnit->id
            )
            ->count();

        if ($childCount > 0) {
            return back()
                ->with(
                    'error',
                    'This organisation unit cannot be deleted because it has '
                    . $childCount
                    . ' child organisation unit(s).'
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

                'unit_type' => [
                    'required',
                    Rule::in([
                        'office',
                        'department',
                        'section',
                    ]),
                ],

                'parent_id' => [
                    'nullable',
                    'integer',
                    'exists:organisation_units,id',
                ],

                'dashboard_id' => [
                    'nullable',
                    'integer',
                    'exists:dashboards,id',
                ],

                'email' => [
                    'nullable',
                    'email',
                    'max:150',
                ],

                'telephone' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'physical_location' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'display_order' => [
                    'required',
                    'integer',
                    'min:0',
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

                'unit_type.required' =>
                    'Please select the organisation unit type.',

                'parent_id.exists' =>
                    'The selected parent organisation unit does not exist.',

                'dashboard_id.exists' =>
                    'The selected dashboard does not exist.',
            ]
        );
    }


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
                    $childrenByParent->get(
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
<?php

namespace App\Http\Controllers\PensionsAdministration\Updates;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Updates\EmployerGroup;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class EmployerGroupController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }


    public function index(): View
    {
        $groups = EmployerGroup::query()
            ->withCount('employers')
            ->orderBy('code')
            ->get();

        return view(
            'pensions-administration.updates.employer-groups.index',
            compact('groups')
        );
    }


    public function create(): View
    {
        return view(
            'pensions-administration.updates.employer-groups.create'
        );
    }


    public function store(
        Request $request
    ): RedirectResponse {
        $validated =
            $this->validateGroup(
                $request
            );

        try {
            $validated['created_by'] =
                auth()->id();

            $validated['updated_by'] =
                auth()->id();

            $employerGroup =
                EmployerGroup::create(
                    $validated
                );


            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $this->auditService->log(
                eventType:
                    'data_change',

                module:
                    'Pensions Administration - Updates',

                action:
                    'CREATE_EMPLOYER_GROUP',

                description:
                    'Employer group '
                    . $employerGroup->name
                    . ' was created.',

                auditable:
                    $employerGroup,

                oldValues:
                    null,

                newValues:
                    $this->auditService
                        ->values(
                            $employerGroup
                        ),

                metadata: [
                    'group_code' =>
                        $employerGroup->code,
                ],

                request:
                    $request
            );


            return redirect()
                ->route(
                    'pensions-administration.updates.employer-groups.show',
                    $employerGroup
                )
                ->with(
                    'success',
                    'Employer group created successfully.'
                );
        } catch (Throwable $e) {
            $this->auditService->failure(
                eventType:
                    'data_change',

                module:
                    'Pensions Administration - Updates',

                action:
                    'CREATE_EMPLOYER_GROUP',

                description:
                    'Failed attempt to create an employer group.',

                failureReason:
                    $e->getMessage(),

                metadata: [
                    'submitted_code' =>
                        $validated['code']
                        ?? null,

                    'submitted_name' =>
                        $validated['name']
                        ?? null,
                ],

                request:
                    $request
            );

            throw $e;
        }
    }


    public function show(
        EmployerGroup $employerGroup
    ): View {
        $employerGroup->load([
            'employers' => function ($query) {
                $query->orderBy('name');
            },
        ]);

        return view(
            'pensions-administration.updates.employer-groups.show',
            compact(
                'employerGroup'
            )
        );
    }


    public function edit(
        EmployerGroup $employerGroup
    ): View {
        return view(
            'pensions-administration.updates.employer-groups.edit',
            compact(
                'employerGroup'
            )
        );
    }


    public function update(
        Request $request,
        EmployerGroup $employerGroup
    ): RedirectResponse {
        $validated =
            $this->validateGroup(
                $request,
                $employerGroup
            );

        $oldValues =
            $this->auditService
                ->values(
                    $employerGroup
                );

        try {
            $validated['updated_by'] =
                auth()->id();

            $employerGroup->update(
                $validated
            );

            $employerGroup->refresh();


            $this->auditService->log(
                eventType:
                    'data_change',

                module:
                    'Pensions Administration - Updates',

                action:
                    'UPDATE_EMPLOYER_GROUP',

                description:
                    'Employer group '
                    . $employerGroup->name
                    . ' was updated.',

                auditable:
                    $employerGroup,

                oldValues:
                    $oldValues,

                newValues:
                    $this->auditService
                        ->values(
                            $employerGroup
                        ),

                request:
                    $request
            );


            return redirect()
                ->route(
                    'pensions-administration.updates.employer-groups.show',
                    $employerGroup
                )
                ->with(
                    'success',
                    'Employer group updated successfully.'
                );
        } catch (Throwable $e) {
            $this->auditService->failure(
                eventType:
                    'data_change',

                module:
                    'Pensions Administration - Updates',

                action:
                    'UPDATE_EMPLOYER_GROUP',

                description:
                    'Failed attempt to update employer group '
                    . $employerGroup->name
                    . '.',

                failureReason:
                    $e->getMessage(),

                auditable:
                    $employerGroup,

                request:
                    $request
            );

            throw $e;
        }
    }


    public function destroy(
        Request $request,
        EmployerGroup $employerGroup
    ): RedirectResponse {
        if (
            $employerGroup
                ->employers()
                ->exists()
        ) {
            return back()
                ->with(
                    'error',
                    'This employer group cannot be deleted because employers are assigned to it.'
                );
        }


        $oldValues =
            $this->auditService
                ->values(
                    $employerGroup
                );


        $name =
            $employerGroup->name;

        $id =
            $employerGroup->id;


        $employerGroup->delete();


        $this->auditService->log(
            eventType:
                'data_change',

            module:
                'Pensions Administration - Updates',

            action:
                'DELETE_EMPLOYER_GROUP',

            description:
                'Employer group '
                . $name
                . ' was archived.',

            auditable:
                $employerGroup,

            oldValues:
                $oldValues,

            newValues: [
                'deleted_at' =>
                    $employerGroup
                        ->deleted_at
                        ?->toDateTimeString(),
            ],

            metadata: [
                'employer_group_id' =>
                    $id,
            ],

            request:
                $request
        );


        return redirect()
            ->route(
                'pensions-administration.updates.employer-groups.index'
            )
            ->with(
                'success',
                'Employer group archived successfully.'
            );
    }


    private function validateGroup(
        Request $request,
        ?EmployerGroup $group = null
    ): array {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',

                Rule::unique(
                    'employer_groups',
                    'code'
                )->ignore(
                    $group?->id
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
            ],

            'vote_number_required' => [
                'required',
                'boolean',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ]);
    }
}
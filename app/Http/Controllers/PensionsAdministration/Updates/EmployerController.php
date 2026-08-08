<?php

namespace App\Http\Controllers\PensionsAdministration\Updates;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\EmployerGroup;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class EmployerController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }


    public function index(): View
    {
        $employers = Employer::query()
            ->with('employerGroup')
            ->withCount([
                'contacts',
                'currentMemberEmployments',
            ])
            ->orderBy('name')
            ->get();

        return view(
            'pensions-administration.updates.employers.index',
            compact('employers')
        );
    }


    public function create(): View
    {
        $groups = EmployerGroup::query()
            ->where(
                'is_active',
                true
            )
            ->orderBy('code')
            ->get();

        return view(
            'pensions-administration.updates.employers.create',
            compact('groups')
        );
    }


    public function store(
        Request $request
    ): RedirectResponse {
        $validated =
            $this->validateEmployer(
                $request
            );

        try {
            $employer =
                DB::transaction(
                    function () use (
                        $validated
                    ): Employer {

                        $validated[
                            'employer_number'
                        ] =
                            $this
                                ->generateEmployerNumber();

                        $validated[
                            'created_by'
                        ] =
                            auth()->id();

                        $validated[
                            'updated_by'
                        ] =
                            auth()->id();


                        return Employer::create(
                            $validated
                        );
                    }
                );


            $this->auditService->log(
                eventType:
                    'data_change',

                module:
                    'Pensions Administration - Updates',

                action:
                    'CREATE_EMPLOYER',

                description:
                    'Employer '
                    . $employer->name
                    . ' was created.',

                auditable:
                    $employer,

                newValues:
                    $this->auditService
                        ->values(
                            $employer
                        ),

                metadata: [
                    'employer_number' =>
                        $employer
                            ->employer_number,

                    'penad_employer_number' =>
                        $employer
                            ->penad_employer_number,

                    'fundworx_employer_number' =>
                        $employer
                            ->fundworx_employer_number,
                ],

                request:
                    $request
            );


            return redirect()
                ->route(
                    'pensions-administration.updates.employers.show',
                    $employer
                )
                ->with(
                    'success',
                    'Employer created successfully.'
                );
        } catch (Throwable $e) {
            $this->auditService->failure(
                eventType:
                    'data_change',

                module:
                    'Pensions Administration - Updates',

                action:
                    'CREATE_EMPLOYER',

                description:
                    'Failed attempt to create an employer.',

                failureReason:
                    $e->getMessage(),

                metadata: [
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
        Employer $employer
    ): View {
        $employer->load([
            'employerGroup',
            'contacts',
        ]);

        $employer->loadCount([
            'currentMemberEmployments',
        ]);

        return view(
            'pensions-administration.updates.employers.show',
            compact('employer')
        );
    }


    public function edit(
        Employer $employer
    ): View {
        $groups =
            EmployerGroup::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('code')
                ->get();

        return view(
            'pensions-administration.updates.employers.edit',
            compact(
                'employer',
                'groups'
            )
        );
    }


    public function update(
        Request $request,
        Employer $employer
    ): RedirectResponse {
        $validated =
            $this->validateEmployer(
                $request
            );

        $oldValues =
            $this->auditService
                ->values(
                    $employer
                );

        try {
            $validated['updated_by'] =
                auth()->id();


            $employer->update(
                $validated
            );

            $employer->refresh();


            $this->auditService->log(
                eventType:
                    'data_change',

                module:
                    'Pensions Administration - Updates',

                action:
                    'UPDATE_EMPLOYER',

                description:
                    'Employer '
                    . $employer->name
                    . ' was updated.',

                auditable:
                    $employer,

                oldValues:
                    $oldValues,

                newValues:
                    $this->auditService
                        ->values(
                            $employer
                        ),

                request:
                    $request
            );


            return redirect()
                ->route(
                    'pensions-administration.updates.employers.show',
                    $employer
                )
                ->with(
                    'success',
                    'Employer updated successfully.'
                );
        } catch (Throwable $e) {
            $this->auditService->failure(
                eventType:
                    'data_change',

                module:
                    'Pensions Administration - Updates',

                action:
                    'UPDATE_EMPLOYER',

                description:
                    'Failed attempt to update employer '
                    . $employer->name
                    . '.',

                failureReason:
                    $e->getMessage(),

                auditable:
                    $employer,

                request:
                    $request
            );

            throw $e;
        }
    }


    public function destroy(
        Request $request,
        Employer $employer
    ): RedirectResponse {
        if (
            $employer
                ->memberEmployments()
                ->exists()
        ) {
            return back()
                ->with(
                    'error',
                    'This employer cannot be deleted because membership records are linked to it.'
                );
        }


        $oldValues =
            $this->auditService
                ->values(
                    $employer
                );

        $name =
            $employer->name;


        $employer->delete();


        $this->auditService->log(
            eventType:
                'data_change',

            module:
                'Pensions Administration - Updates',

            action:
                'ARCHIVE_EMPLOYER',

            description:
                'Employer '
                . $name
                . ' was archived.',

            auditable:
                $employer,

            oldValues:
                $oldValues,

            newValues: [
                'deleted_at' =>
                    $employer
                        ->deleted_at
                        ?->toDateTimeString(),
            ],

            request:
                $request
        );


        return redirect()
            ->route(
                'pensions-administration.updates.employers.index'
            )
            ->with(
                'success',
                'Employer archived successfully.'
            );
    }


    private function validateEmployer(
        Request $request
    ): array {
        return $request->validate([
            'penad_employer_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'fundworx_employer_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'employer_group_id' => [
                'nullable',
                'exists:employer_groups,id',
            ],

            'name' => [
                'required',
                'string',
                'max:200',
            ],

            'short_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'corporate_form' => [
                'nullable',
                'string',
                'max:100',
            ],

            'fund_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'scheme_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            'tpin' => [
                'nullable',
                'string',
                'max:100',
            ],

            'business_registration_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'telephone' => [
                'nullable',
                'string',
                'max:100',
            ],

            'postal_address' => [
                'nullable',
                'string',
            ],

            'physical_address' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                'string',
                'max:30',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ]);
    }


    private function generateEmployerNumber(): string
    {
        $lastId =
            (int) (
                Employer::withTrashed()
                    ->max('id')
                ?? 0
            );

        return 'EMP'
            . str_pad(
                (string) ($lastId + 1),
                6,
                '0',
                STR_PAD_LEFT
            );
    }
}
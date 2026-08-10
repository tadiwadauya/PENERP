<?php

namespace App\Http\Controllers\PensionsAdministration\Updates;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\Member;
use App\Models\PensionsAdministration\Updates\MemberEmployment;
use App\Models\PensionsAdministration\Updates\MemberStatusHistory;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class MemberController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Membership Register
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
{
    $query = Member::query()
        ->with([
            'currentEmployment.employer',
        ]);


    /*
    |--------------------------------------------------------------------------
    | General Search
    |--------------------------------------------------------------------------
    */

    if ($request->filled('search')) {

        $search = trim(
            $request->input('search')
        );

        $query->where(function ($query) use ($search) {

            $query
                ->where(
                    'member_number',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'penad_member_number',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'fundworx_member_number',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'national_id',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'surname',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'first_names',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'other_names',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'maiden_name',
                    'like',
                    '%' . $search . '%'
                );

        });

    }


    /*
    |--------------------------------------------------------------------------
    | PENERP Member Number
    |--------------------------------------------------------------------------
    */

    if (
        $request->filled(
            'penerp_member_number'
        )
    ) {

        $query->where(
            'member_number',
            'like',
            '%'
            . trim(
                $request->input(
                    'penerp_member_number'
                )
            )
            . '%'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | PenAd Member Number
    |--------------------------------------------------------------------------
    */

    if (
        $request->filled(
            'penad_member_number'
        )
    ) {

        $query->where(
            'penad_member_number',
            'like',
            '%'
            . trim(
                $request->input(
                    'penad_member_number'
                )
            )
            . '%'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Fundworx Member Number
    |--------------------------------------------------------------------------
    */

    if (
        $request->filled(
            'fundworx_member_number'
        )
    ) {

        $query->where(
            'fundworx_member_number',
            'like',
            '%'
            . trim(
                $request->input(
                    'fundworx_member_number'
                )
            )
            . '%'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Membership Status
    |--------------------------------------------------------------------------
    */

    if (
        $request->filled(
            'status'
        )
    ) {

        $query->where(
            'membership_status',
            $request->input(
                'status'
            )
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Employer
    |--------------------------------------------------------------------------
    */

    if (
        $request->filled(
            'employer_id'
        )
    ) {

        $employerId =
            (int)
            $request->input(
                'employer_id'
            );


        $query->whereHas(
            'currentEmployment',
            function ($query) use ($employerId) {

                $query->where(
                    'employer_id',
                    $employerId
                );

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Members
    |--------------------------------------------------------------------------
    */

    $members = $query
        ->orderBy('surname')
        ->orderBy('first_names')
        ->get();


    /*
    |--------------------------------------------------------------------------
    | Employers for Filter
    |--------------------------------------------------------------------------
    */

    $employers = Employer::query()
        ->where(
            'is_active',
            true
        )
        ->orderBy('name')
        ->get();


    return view(
        'pensions-administration.updates.members.index',
        compact(
            'members',
            'employers'
        )
    );
}

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(): View
    {
        $employers =
            Employer::query()
                ->with('employerGroup')
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->get();

        return view(
            'pensions-administration.updates.members.create',
            compact('employers')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ): RedirectResponse {
        $validated =
            $this->validateMember(
                $request
            );


        $normalizedId =
            Member::normalizeNationalId(
                $validated['national_id']
                ?? null
            );


        /*
        |--------------------------------------------------------------------------
        | Active Member Requires National ID
        |--------------------------------------------------------------------------
        */

        $this->validateActiveMemberNationalId(
            $validated,
            $normalizedId
        );


        /*
        |--------------------------------------------------------------------------
        | Exact ID Duplicate
        |--------------------------------------------------------------------------
        */

        $this->validateNationalIdDuplicate(
            $normalizedId
        );


        try {
            $member =
                DB::transaction(
                    function () use (
                        $validated,
                        $normalizedId,
                        $request
                    ): Member {

                        /*
                        |--------------------------------------------------------------------------
                        | Member
                        |--------------------------------------------------------------------------
                        */

                        $member = Member::create([
                            'member_number' =>
                                $this->generateMemberNumber(),

                            'penad_member_number' =>
                                $validated[
                                    'penad_member_number'
                                ] ?? null,

                            'fundworx_member_number' =>
                                $validated[
                                    'fundworx_member_number'
                                ] ?? null,

                            'title' =>
                                $validated['title']
                                ?? null,

                            'surname' =>
                                $validated['surname'],

                            'first_names' =>
                                $validated['first_names'],

                            'national_id' =>
                                $validated['national_id']
                                ?? null,

                            'national_id_normalized' =>
                                $normalizedId,

                            'date_of_birth' =>
                                $validated[
                                    'date_of_birth'
                                ] ?? null,

                            'gender' =>
                                $validated['gender']
                                ?? null,

                            'marital_status' =>
                                $validated[
                                    'marital_status'
                                ] ?? null,

                            'occupation' =>
                                $validated['occupation']
                                ?? null,

                            'email' =>
                                $validated['email']
                                ?? null,

                            'secondary_email' =>
                                $validated[
                                    'secondary_email'
                                ] ?? null,

                            'cell_number' =>
                                $validated[
                                    'cell_number'
                                ] ?? null,

                            'secondary_cell_number' =>
                                $validated[
                                    'secondary_cell_number'
                                ] ?? null,

                            'physical_address_1' =>
                                $validated[
                                    'physical_address_1'
                                ] ?? null,

                            'physical_address_2' =>
                                $validated[
                                    'physical_address_2'
                                ] ?? null,

                            'physical_address_3' =>
                                $validated[
                                    'physical_address_3'
                                ] ?? null,

                            'physical_suburb' =>
                                $validated[
                                    'physical_suburb'
                                ] ?? null,

                            'physical_city' =>
                                $validated[
                                    'physical_city'
                                ] ?? null,

                            'physical_country' =>
                                $validated[
                                    'physical_country'
                                ] ?? 'Zimbabwe',

                            'postal_address_1' =>
                                $validated[
                                    'postal_address_1'
                                ] ?? null,

                            'postal_address_2' =>
                                $validated[
                                    'postal_address_2'
                                ] ?? null,

                            'postal_address_3' =>
                                $validated[
                                    'postal_address_3'
                                ] ?? null,

                            'postal_city' =>
                                $validated[
                                    'postal_city'
                                ] ?? null,

                            'postal_country' =>
                                $validated[
                                    'postal_country'
                                ] ?? 'Zimbabwe',

                            'date_joined_fund' =>
                                $validated[
                                    'date_joined_fund'
                                ] ?? null,

                            'membership_status' =>
                                $validated[
                                    'membership_status'
                                ],

                            'is_active' =>
                                $validated[
                                    'membership_status'
                                ] === 'active',

                            'created_by' =>
                                auth()->id(),

                            'updated_by' =>
                                auth()->id(),
                        ]);


                        /*
                        |--------------------------------------------------------------------------
                        | Initial Employment
                        |--------------------------------------------------------------------------
                        */

                        if (
                            !empty(
                                $validated['employer_id']
                            )
                        ) {
                            $employment =
                                $this->createEmployment(
                                    $member,
                                    $validated
                                );


                            $this->auditService->log(
                                eventType:
                                    'membership',

                                module:
                                    'Pensions Administration - Updates',

                                action:
                                    'CREATE_MEMBER_EMPLOYMENT',

                                description:
                                    'Initial employment record was created for member '
                                    . $member->member_number
                                    . '.',

                                auditable:
                                    $employment,

                                newValues:
                                    $this->auditService
                                        ->values(
                                            $employment
                                        ),

                                metadata: [
                                    'member_id' =>
                                        $member->id,

                                    'member_number' =>
                                        $member
                                            ->member_number,
                                ],

                                request:
                                    $request
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Initial Movement
                        |--------------------------------------------------------------------------
                        */

                        $movement =
                            MemberStatusHistory::create([
                                'member_id' =>
                                    $member->id,

                                'old_status' =>
                                    null,

                                'new_status' =>
                                    $member
                                        ->membership_status,

                                'effective_date' =>
                                    $member
                                        ->date_joined_fund
                                    ?? now()->toDateString(),

                                'movement_type' =>
                                    'NEW_MEMBER',

                                'reason' =>
                                    'Member created in PENERP.',

                                'source' =>
                                    'manual',

                                'changed_by' =>
                                    auth()->id(),
                            ]);


                        /*
                        |--------------------------------------------------------------------------
                        | Member Audit
                        |--------------------------------------------------------------------------
                        */

                        $this->auditService->log(
                            eventType:
                                'membership',

                            module:
                                'Pensions Administration - Updates',

                            action:
                                'CREATE_MEMBER',

                            description:
                                'Member '
                                . $member->member_number
                                . ' - '
                                . $member->full_name
                                . ' was created.',

                            auditable:
                                $member,

                            newValues:
                                $this->auditService
                                    ->values(
                                        $member
                                    ),

                            metadata: [
                                'movement_id' =>
                                    $movement->id,

                                'member_number' =>
                                    $member
                                        ->member_number,

                                'penad_member_number' =>
                                    $member
                                        ->penad_member_number,

                                'fundworx_member_number' =>
                                    $member
                                        ->fundworx_member_number,
                            ],

                            request:
                                $request
                        );


                        return $member;
                    }
                );


            return redirect()
                ->route(
                    'pensions-administration.updates.members.show',
                    $member
                )
                ->with(
                    'success',
                    'Member created successfully. PENERP Member Number: '
                    . $member->member_number
                );
        } catch (Throwable $e) {
            $this->auditService->failure(
                eventType:
                    'membership',

                module:
                    'Pensions Administration - Updates',

                action:
                    'CREATE_MEMBER',

                description:
                    'Failed attempt to create a membership record.',

                failureReason:
                    $e->getMessage(),

                metadata: [
                    'national_id' =>
                        $validated[
                            'national_id'
                        ] ?? null,

                    'penad_member_number' =>
                        $validated[
                            'penad_member_number'
                        ] ?? null,

                    'fundworx_member_number' =>
                        $validated[
                            'fundworx_member_number'
                        ] ?? null,
                ],

                request:
                    $request
            );

            throw $e;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(
        Member $member
    ): View {
        $member->load([
            'currentEmployment.employer.employerGroup',

            'employments' => function ($query) {
                $query
                    ->with('employer')
                    ->orderByDesc(
                        'effective_from'
                    )
                    ->orderByDesc('id');
            },

            'statusHistories' => function ($query) {
                $query
                    ->orderByDesc(
                        'effective_date'
                    )
                    ->orderByDesc('id');
            },
        ]);


        return view(
            'pensions-administration.updates.members.show',
            compact('member')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(
        Member $member
    ): View {
        $member->load([
            'currentEmployment.employer.employerGroup',
        ]);


        $employers =
            Employer::query()
                ->with('employerGroup')
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->get();


        return view(
            'pensions-administration.updates.members.edit',
            compact(
                'member',
                'employers'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        Member $member
    ): RedirectResponse {
        $validated =
            $this->validateMember(
                $request
            );


        $normalizedId =
            Member::normalizeNationalId(
                $validated['national_id']
                ?? null
            );


        $this->validateActiveMemberNationalId(
            $validated,
            $normalizedId
        );


        $this->validateNationalIdDuplicate(
            $normalizedId,
            $member
        );


        $oldMemberValues =
            $this->auditService
                ->values(
                    $member
                );


        try {
            DB::transaction(
                function () use (
                    $member,
                    $validated,
                    $normalizedId,
                    $oldMemberValues,
                    $request
                ): void {

                    $oldStatus =
                        $member
                            ->membership_status;


                    /*
                    |--------------------------------------------------------------------------
                    | Update Member Static Data
                    |--------------------------------------------------------------------------
                    */

                    $member->update([
                        'penad_member_number' =>
                            $validated[
                                'penad_member_number'
                            ] ?? null,

                        'fundworx_member_number' =>
                            $validated[
                                'fundworx_member_number'
                            ] ?? null,

                        'title' =>
                            $validated['title']
                            ?? null,

                        'surname' =>
                            $validated['surname'],

                        'first_names' =>
                            $validated['first_names'],

                        'national_id' =>
                            $validated[
                                'national_id'
                            ] ?? null,

                        'national_id_normalized' =>
                            $normalizedId,

                        'date_of_birth' =>
                            $validated[
                                'date_of_birth'
                            ] ?? null,

                        'gender' =>
                            $validated['gender']
                            ?? null,

                        'marital_status' =>
                            $validated[
                                'marital_status'
                            ] ?? null,

                        'occupation' =>
                            $validated[
                                'occupation'
                            ] ?? null,

                        'email' =>
                            $validated['email']
                            ?? null,

                        'secondary_email' =>
                            $validated[
                                'secondary_email'
                            ] ?? null,

                        'cell_number' =>
                            $validated[
                                'cell_number'
                            ] ?? null,

                        'secondary_cell_number' =>
                            $validated[
                                'secondary_cell_number'
                            ] ?? null,

                        'physical_address_1' =>
                            $validated[
                                'physical_address_1'
                            ] ?? null,

                        'physical_address_2' =>
                            $validated[
                                'physical_address_2'
                            ] ?? null,

                        'physical_address_3' =>
                            $validated[
                                'physical_address_3'
                            ] ?? null,

                        'physical_suburb' =>
                            $validated[
                                'physical_suburb'
                            ] ?? null,

                        'physical_city' =>
                            $validated[
                                'physical_city'
                            ] ?? null,

                        'physical_country' =>
                            $validated[
                                'physical_country'
                            ] ?? 'Zimbabwe',

                        'postal_address_1' =>
                            $validated[
                                'postal_address_1'
                            ] ?? null,

                        'postal_address_2' =>
                            $validated[
                                'postal_address_2'
                            ] ?? null,

                        'postal_address_3' =>
                            $validated[
                                'postal_address_3'
                            ] ?? null,

                        'postal_city' =>
                            $validated[
                                'postal_city'
                            ] ?? null,

                        'postal_country' =>
                            $validated[
                                'postal_country'
                            ] ?? 'Zimbabwe',

                        'date_joined_fund' =>
                            $validated[
                                'date_joined_fund'
                            ] ?? null,

                        'membership_status' =>
                            $validated[
                                'membership_status'
                            ],

                        'is_active' =>
                            $validated[
                                'membership_status'
                            ] === 'active',

                        'updated_by' =>
                            auth()->id(),
                    ]);


                    $member->refresh();


                    /*
                    |--------------------------------------------------------------------------
                    | Static Data Audit
                    |--------------------------------------------------------------------------
                    */

                    $newMemberValues =
                        $this->auditService
                            ->values(
                                $member
                            );


                    if (
                        $this->valuesChanged(
                            $oldMemberValues,
                            $newMemberValues
                        )
                    ) {
                        $this->auditService->log(
                            eventType:
                                'membership',

                            module:
                                'Pensions Administration - Updates',

                            action:
                                'UPDATE_MEMBER',

                            description:
                                'Member '
                                . $member->member_number
                                . ' was updated.',

                            auditable:
                                $member,

                            oldValues:
                                $oldMemberValues,

                            newValues:
                                $newMemberValues,

                            request:
                                $request
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Membership Status Change
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $oldStatus
                        !==
                        $member
                            ->membership_status
                    ) {
                        $movement =
                            MemberStatusHistory::create([
                                'member_id' =>
                                    $member->id,

                                'old_status' =>
                                    $oldStatus,

                                'new_status' =>
                                    $member
                                        ->membership_status,

                                'effective_date' =>
                                    $validated[
                                        'status_effective_date'
                                    ]
                                    ?? now()->toDateString(),

                                'movement_type' =>
                                    $validated[
                                        'movement_type'
                                    ]
                                    ?? 'STATUS_CHANGE',

                                'reason' =>
                                    $validated[
                                        'status_change_reason'
                                    ]
                                    ?? 'Membership status changed.',

                                'source' =>
                                    'manual',

                                'changed_by' =>
                                    auth()->id(),
                            ]);


                        $this->auditService->log(
                            eventType:
                                'membership_movement',

                            module:
                                'Pensions Administration - Updates',

                            action:
                                'CHANGE_MEMBER_STATUS',

                            description:
                                'Membership status for '
                                . $member->member_number
                                . ' changed from '
                                . $oldStatus
                                . ' to '
                                . $member
                                    ->membership_status
                                . '.',

                            auditable:
                                $member,

                            oldValues: [
                                'membership_status' =>
                                    $oldStatus,
                            ],

                            newValues: [
                                'membership_status' =>
                                    $member
                                        ->membership_status,
                            ],

                            metadata: [
                                'movement_id' =>
                                    $movement->id,

                                'movement_type' =>
                                    $movement
                                        ->movement_type,

                                'effective_date' =>
                                    $movement
                                        ->effective_date
                                        ?->toDateString(),

                                'reason' =>
                                    $movement->reason,
                            ],

                            request:
                                $request
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Current Employment
                    |--------------------------------------------------------------------------
                    */

                    $this->updateEmployment(
                        $member,
                        $validated,
                        $request
                    );
                }
            );


            return redirect()
                ->route(
                    'pensions-administration.updates.members.show',
                    $member
                )
                ->with(
                    'success',
                    'Member updated successfully.'
                );
        } catch (Throwable $e) {
            $this->auditService->failure(
                eventType:
                    'membership',

                module:
                    'Pensions Administration - Updates',

                action:
                    'UPDATE_MEMBER',

                description:
                    'Failed attempt to update member '
                    . $member->member_number
                    . '.',

                failureReason:
                    $e->getMessage(),

                auditable:
                    $member,

                request:
                    $request
            );

            throw $e;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Archive
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        Member $member
    ): RedirectResponse {
        $oldValues =
            $this->auditService
                ->values(
                    $member
                );


        DB::transaction(
            function () use (
                $member,
                $oldValues,
                $request
            ): void {

                $oldStatus =
                    $member
                        ->membership_status;


                /*
                |--------------------------------------------------------------------------
                | Close Current Employment
                |--------------------------------------------------------------------------
                */

                $currentEmployment =
                    $member
                        ->currentEmployment()
                        ->first();


                if ($currentEmployment) {
                    $currentEmployment->update([
                        'effective_to' =>
                            now()->toDateString(),

                        'employment_status' =>
                            'inactive',

                        'is_current' =>
                            false,

                        'updated_by' =>
                            auth()->id(),
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Movement History
                |--------------------------------------------------------------------------
                */

                MemberStatusHistory::create([
                    'member_id' =>
                        $member->id,

                    'old_status' =>
                        $oldStatus,

                    'new_status' =>
                        'inactive',

                    'effective_date' =>
                        now()->toDateString(),

                    'movement_type' =>
                        'ARCHIVE',

                    'reason' =>
                        'Member record archived.',

                    'source' =>
                        'manual',

                    'changed_by' =>
                        auth()->id(),
                ]);


                /*
                |--------------------------------------------------------------------------
                | Archive Member
                |--------------------------------------------------------------------------
                */

                $member->update([
                    'membership_status' =>
                        'inactive',

                    'is_active' =>
                        false,

                    'updated_by' =>
                        auth()->id(),
                ]);


                $member->delete();


                /*
                |--------------------------------------------------------------------------
                | Audit
                |--------------------------------------------------------------------------
                */

                $this->auditService->log(
                    eventType:
                        'membership',

                    module:
                        'Pensions Administration - Updates',

                    action:
                        'ARCHIVE_MEMBER',

                    description:
                        'Member '
                        . $member->member_number
                        . ' was archived.',

                    auditable:
                        $member,

                    oldValues:
                        $oldValues,

                    newValues: [
                        'membership_status' =>
                            'inactive',

                        'is_active' =>
                            false,

                        'deleted_at' =>
                            $member
                                ->deleted_at
                                ?->toDateTimeString(),
                    ],

                    request:
                        $request
                );
            }
        );


        return redirect()
            ->route(
                'pensions-administration.updates.members.index'
            )
            ->with(
                'success',
                'Member archived successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Employment
    |--------------------------------------------------------------------------
    */

    private function updateEmployment(
        Member $member,
        array $validated,
        Request $request
    ): void {
        $currentEmployment =
            MemberEmployment::query()
                ->where(
                    'member_id',
                    $member->id
                )
                ->where(
                    'is_current',
                    true
                )
                ->first();


        $newEmployerId =
            !empty(
                $validated['employer_id']
            )
                ? (int) $validated[
                    'employer_id'
                ]
                : null;


        /*
        |--------------------------------------------------------------------------
        | No Current Employment + No Employer Selected
        |--------------------------------------------------------------------------
        */

        if (
            !$currentEmployment
            &&
            !$newEmployerId
        ) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | No Current Employment + New Employer
        |--------------------------------------------------------------------------
        */

        if (
            !$currentEmployment
            &&
            $newEmployerId
        ) {
            $employment =
                $this->createEmployment(
                    $member,
                    $validated
                );


            $this->auditService->log(
                eventType:
                    'membership',

                module:
                    'Pensions Administration - Updates',

                action:
                    'CREATE_MEMBER_EMPLOYMENT',

                description:
                    'A current employment record was added for member '
                    . $member->member_number
                    . '.',

                auditable:
                    $employment,

                newValues:
                    $this->auditService
                        ->values(
                            $employment
                        ),

                metadata: [
                    'member_number' =>
                        $member->member_number,
                ],

                request:
                    $request
            );


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Existing Employment Removed
        |--------------------------------------------------------------------------
        */

        if (
            $currentEmployment
            &&
            !$newEmployerId
        ) {
            $oldValues =
                $this->auditService
                    ->values(
                        $currentEmployment
                    );


            $currentEmployment->update([
                'effective_to' =>
                    $validated[
                        'employment_effective_date'
                    ]
                    ?? now()->toDateString(),

                'employment_status' =>
                    'inactive',

                'is_current' =>
                    false,

                'updated_by' =>
                    auth()->id(),
            ]);


            $currentEmployment->refresh();


            $this->auditService->log(
                eventType:
                    'membership_movement',

                module:
                    'Pensions Administration - Updates',

                action:
                    'CLOSE_MEMBER_EMPLOYMENT',

                description:
                    'Current employment was closed for member '
                    . $member->member_number
                    . '.',

                auditable:
                    $currentEmployment,

                oldValues:
                    $oldValues,

                newValues:
                    $this->auditService
                        ->values(
                            $currentEmployment
                        ),

                request:
                    $request
            );


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Same Employer
        |--------------------------------------------------------------------------
        */

        if (
            (int) $currentEmployment
                ->employer_id
            ===
            $newEmployerId
        ) {
            $this->validateStaffNumber(
                employerId:
                    $newEmployerId,

                staffNumber:
                    $validated[
                        'staff_number'
                    ] ?? null,

                ignoreEmployment:
                    $currentEmployment
            );


            $this->validateVoteNumber(
                employerId:
                    $newEmployerId,

                voteNumber:
                    $validated[
                        'vote_number'
                    ] ?? null
            );


            $oldValues =
                $this->auditService
                    ->values(
                        $currentEmployment
                    );


            $currentEmployment->update([
                'staff_number' =>
                    $validated[
                        'staff_number'
                    ] ?? null,

                'vote_number' =>
                    $validated[
                        'vote_number'
                    ] ?? null,

                'branch' =>
                    $validated[
                        'branch'
                    ] ?? null,

                'department' =>
                    $validated[
                        'department'
                    ] ?? null,

                'date_joined_employer' =>
                    $validated[
                        'date_joined_employer'
                    ] ?? null,

                'updated_by' =>
                    auth()->id(),
            ]);


            $currentEmployment->refresh();


            $newValues =
                $this->auditService
                    ->values(
                        $currentEmployment
                    );


            if (
                $this->valuesChanged(
                    $oldValues,
                    $newValues
                )
            ) {
                $this->auditService->log(
                    eventType:
                        'membership',

                    module:
                        'Pensions Administration - Updates',

                    action:
                        'UPDATE_MEMBER_EMPLOYMENT',

                    description:
                        'Employment information for member '
                        . $member->member_number
                        . ' was updated.',

                    auditable:
                        $currentEmployment,

                    oldValues:
                        $oldValues,

                    newValues:
                        $newValues,

                    request:
                        $request
                );
            }


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Employer Changed
        |--------------------------------------------------------------------------
        |
        | Never overwrite the old employment.
        |
        | Close it and create a new current employment.
        |
        */

        $this->validateStaffNumber(
            employerId:
                $newEmployerId,

            staffNumber:
                $validated[
                    'staff_number'
                ] ?? null
        );


        $this->validateVoteNumber(
            employerId:
                $newEmployerId,

            voteNumber:
                $validated[
                    'vote_number'
                ] ?? null
        );


        $oldEmploymentValues =
            $this->auditService
                ->values(
                    $currentEmployment
                );


        $changeDate =
            $validated[
                'employment_effective_date'
            ]
            ??
            $validated[
                'date_joined_employer'
            ]
            ??
            now()->toDateString();


        /*
        |--------------------------------------------------------------------------
        | Close Previous Employment
        |--------------------------------------------------------------------------
        */

        $currentEmployment->update([
            'effective_to' =>
                $changeDate,

            'employment_status' =>
                'transferred',

            'is_current' =>
                false,

            'updated_by' =>
                auth()->id(),
        ]);


        /*
        |--------------------------------------------------------------------------
        | Create New Employment
        |--------------------------------------------------------------------------
        */

        $newEmployment =
            $this->createEmployment(
                $member,
                $validated,
                $changeDate
            );


        /*
        |--------------------------------------------------------------------------
        | Audit Employer Movement
        |--------------------------------------------------------------------------
        */

        $this->auditService->log(
            eventType:
                'membership_movement',

            module:
                'Pensions Administration - Updates',

            action:
                'CHANGE_MEMBER_EMPLOYER',

            description:
                'Member '
                . $member->member_number
                . ' changed employer.',

            auditable:
                $newEmployment,

            oldValues:
                $oldEmploymentValues,

            newValues:
                $this->auditService
                    ->values(
                        $newEmployment
                    ),

            metadata: [
                'member_id' =>
                    $member->id,

                'member_number' =>
                    $member->member_number,

                'previous_employer_id' =>
                    $currentEmployment
                        ->employer_id,

                'new_employer_id' =>
                    $newEmployment
                        ->employer_id,

                'effective_date' =>
                    $changeDate,
            ],

            request:
                $request
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create Employment
    |--------------------------------------------------------------------------
    */

    private function createEmployment(
        Member $member,
        array $validated,
        ?string $effectiveFrom = null
    ): MemberEmployment {
        $employerId =
            (int) $validated[
                'employer_id'
            ];


        $this->validateStaffNumber(
            employerId:
                $employerId,

            staffNumber:
                $validated[
                    'staff_number'
                ] ?? null
        );


        $this->validateVoteNumber(
            employerId:
                $employerId,

            voteNumber:
                $validated[
                    'vote_number'
                ] ?? null
        );


        return MemberEmployment::create([
            'member_id' =>
                $member->id,

            'employer_id' =>
                $employerId,

            'staff_number' =>
                $validated[
                    'staff_number'
                ] ?? null,

            'vote_number' =>
                $validated[
                    'vote_number'
                ] ?? null,

            'branch' =>
                $validated['branch']
                ?? null,

            'department' =>
                $validated[
                    'department'
                ] ?? null,

            'date_joined_employer' =>
                $validated[
                    'date_joined_employer'
                ] ?? null,

            'effective_from' =>
                $effectiveFrom
                ??
                $validated[
                    'date_joined_employer'
                ]
                ??
                $validated[
                    'date_joined_fund'
                ]
                ??
                now()->toDateString(),

            'effective_to' =>
                null,

            'employment_status' =>
                'active',

            'is_current' =>
                true,

            'created_by' =>
                auth()->id(),

            'updated_by' =>
                auth()->id(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Staff Number Validation
    |--------------------------------------------------------------------------
    */

    private function validateStaffNumber(
        int $employerId,
        ?string $staffNumber,
        ?MemberEmployment $ignoreEmployment = null
    ): void {
        if (!$staffNumber) {
            return;
        }


        $query =
            MemberEmployment::query()
                ->where(
                    'employer_id',
                    $employerId
                )
                ->where(
                    'staff_number',
                    trim($staffNumber)
                )
                ->where(
                    'is_current',
                    true
                );


        if ($ignoreEmployment) {
            $query->where(
                'id',
                '<>',
                $ignoreEmployment->id
            );
        }


        $duplicate =
            $query->first();


        if ($duplicate) {
            throw ValidationException::withMessages([
                'staff_number' =>
                    'Staff number '
                    . $staffNumber
                    . ' is already assigned to another current member under this employer.',
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Vote Number Validation
    |--------------------------------------------------------------------------
    */

    private function validateVoteNumber(
        int $employerId,
        ?string $voteNumber
    ): void {
        $employer =
            Employer::query()
                ->with('employerGroup')
                ->findOrFail(
                    $employerId
                );


        if (
            $employer->employerGroup
            &&
            $employer
                ->employerGroup
                ->vote_number_required
            &&
            empty($voteNumber)
        ) {
            throw ValidationException::withMessages([
                'vote_number' =>
                    'A vote number is required for members under '
                    . $employer
                        ->employerGroup
                        ->name
                    . '.',
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Active Member National ID
    |--------------------------------------------------------------------------
    */

    private function validateActiveMemberNationalId(
        array $validated,
        ?string $normalizedId
    ): void {
        if (
            $validated[
                'membership_status'
            ] === 'active'
            &&
            !$normalizedId
        ) {
            throw ValidationException::withMessages([
                'national_id' =>
                    'A National ID is required for an active member.',
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Exact National ID Duplicate
    |--------------------------------------------------------------------------
    */

    private function validateNationalIdDuplicate(
        ?string $normalizedId,
        ?Member $ignoreMember = null
    ): void {
        if (!$normalizedId) {
            return;
        }


        $query =
            Member::query()
                ->where(
                    'national_id_normalized',
                    $normalizedId
                );


        if ($ignoreMember) {
            $query->where(
                'id',
                '<>',
                $ignoreMember->id
            );
        }


        $duplicate =
            $query->first();


        if ($duplicate) {
            throw ValidationException::withMessages([
                'national_id' =>
                    'This National ID already belongs to '
                    . $duplicate->member_number
                    . ' - '
                    . $duplicate->full_name
                    . '.',
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    private function validateMember(
        Request $request
    ): array {
        return $request->validate([
            'penad_member_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'fundworx_member_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'title' => [
                'nullable',
                'string',
                'max:30',
            ],

            'surname' => [
                'required',
                'string',
                'max:150',
            ],

            'first_names' => [
                'required',
                'string',
                'max:200',
            ],

            'national_id' => [
                'nullable',
                'string',
                'max:100',
            ],

            'date_of_birth' => [
                'nullable',
                'date',
                'before:today',
            ],

            'gender' => [
                'nullable',
                'string',
                'max:30',
            ],

            'marital_status' => [
                'nullable',
                'string',
                'max:50',
            ],

            'occupation' => [
                'nullable',
                'string',
                'max:150',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'secondary_email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'cell_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'secondary_cell_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'date_joined_fund' => [
                'nullable',
                'date',
            ],

            'membership_status' => [
                'required',
                'in:active,dormant,inactive,suspended',
            ],


            /*
            |--------------------------------------------------------------------------
            | Status Movement
            |--------------------------------------------------------------------------
            */

            'movement_type' => [
                'nullable',
                'string',
                'max:50',
            ],

            'status_effective_date' => [
                'nullable',
                'date',
            ],

            'status_change_reason' => [
                'nullable',
                'string',
                'max:1000',
            ],


            /*
            |--------------------------------------------------------------------------
            | Employment
            |--------------------------------------------------------------------------
            */

            'employer_id' => [
                'nullable',
                'exists:employers,id',
            ],

            'staff_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'vote_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'branch' => [
                'nullable',
                'string',
                'max:150',
            ],

            'department' => [
                'nullable',
                'string',
                'max:150',
            ],

            'date_joined_employer' => [
                'nullable',
                'date',
            ],

            'employment_effective_date' => [
                'nullable',
                'date',
            ],


            /*
            |--------------------------------------------------------------------------
            | Physical Address
            |--------------------------------------------------------------------------
            */

            'physical_address_1' => [
                'nullable',
                'string',
                'max:200',
            ],

            'physical_address_2' => [
                'nullable',
                'string',
                'max:200',
            ],

            'physical_address_3' => [
                'nullable',
                'string',
                'max:200',
            ],

            'physical_suburb' => [
                'nullable',
                'string',
                'max:150',
            ],

            'physical_city' => [
                'nullable',
                'string',
                'max:150',
            ],

            'physical_country' => [
                'nullable',
                'string',
                'max:100',
            ],


            /*
            |--------------------------------------------------------------------------
            | Postal Address
            |--------------------------------------------------------------------------
            */

            'postal_address_1' => [
                'nullable',
                'string',
                'max:200',
            ],

            'postal_address_2' => [
                'nullable',
                'string',
                'max:200',
            ],

            'postal_address_3' => [
                'nullable',
                'string',
                'max:200',
            ],

            'postal_city' => [
                'nullable',
                'string',
                'max:150',
            ],

            'postal_country' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | PENERP Member Number
    |--------------------------------------------------------------------------
    */

    private function generateMemberNumber(): string
    {
        $lastId =
            (int) (
                Member::withTrashed()
                    ->max('id')
                ?? 0
            );


        return 'MEM'
            . str_pad(
                (string) ($lastId + 1),
                8,
                '0',
                STR_PAD_LEFT
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Determine if Audited Values Changed
    |--------------------------------------------------------------------------
    */

    private function valuesChanged(
        array $oldValues,
        array $newValues
    ): bool {
        unset(
            $oldValues['updated_at'],
            $newValues['updated_at']
        );


        return $oldValues
            !==
            $newValues;
    }
}
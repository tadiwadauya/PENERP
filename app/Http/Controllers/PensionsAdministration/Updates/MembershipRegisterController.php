<?php

namespace App\Http\Controllers\PensionsAdministration\Updates;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MembershipRegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Membership Register
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | This page does NOT load all members.
    |
    | It only loads the employer list required by the filter.
    | Member records are requested separately by DataTables in small pages.
    |
    */

    public function index(): View
    {
        $employers = Employer::query()
            ->select([
                'id',
                'employer_number',
                'penad_employer_number',
                'fundworx_employer_number',
                'name',
            ])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'pensions-administration.updates.members.index',
            compact('employers')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Server-Side DataTables Data
    |--------------------------------------------------------------------------
    */

    public function data(Request $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | DataTables Parameters
        |--------------------------------------------------------------------------
        */

        $draw = (int) $request->input('draw', 1);

        $start = max(
            0,
            (int) $request->input('start', 0)
        );

        $length = (int) $request->input('length', 25);

        if ($length < 1 || $length > 100) {
            $length = 25;
        }

        /*
        |--------------------------------------------------------------------------
        | Total Members
        |--------------------------------------------------------------------------
        |
        | This count is done before any filters are applied.
        |
        */

        $recordsTotal = Member::query()->count();

        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        |
        | Only select fields used by the register.
        |
        | currentEmployment and employer are eager-loaded so Laravel does not
        | execute one query per member.
        |
        */

        $query = Member::query()
            ->select([
                'id',
                'member_number',
                'penad_member_number',
                'fundworx_member_number',
                'surname',
                'first_names',
                'other_names',
                'maiden_name',
                'national_id',
                'date_of_birth',
                'membership_status',
                'is_active',
            ])
            ->with([
                'currentEmployment' => function ($query) {
                    $query->select([
                        'id',
                        'member_id',
                        'employer_id',
                        'staff_number',
                        'vote_number',
                    ]);
                },
                'currentEmployment.employer' => function ($query) {
                    $query->select([
                        'id',
                        'employer_number',
                        'penad_employer_number',
                        'fundworx_employer_number',
                        'name',
                    ]);
                },
            ]);

        /*
        |--------------------------------------------------------------------------
        | Advanced Filters
        |--------------------------------------------------------------------------
        */

        $this->applyAdvancedFilters(
            $query,
            $request
        );

        /*
        |--------------------------------------------------------------------------
        | DataTables Quick Search
        |--------------------------------------------------------------------------
        */

        $quickSearch = trim(
            (string) $request->input(
                'search.value',
                ''
            )
        );

        if ($quickSearch !== '') {
            $this->applyQuickSearch(
                $query,
                $quickSearch
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filtered Count
        |--------------------------------------------------------------------------
        */

        $recordsFiltered = (clone $query)->count();

        /*
        |--------------------------------------------------------------------------
        | Ordering
        |--------------------------------------------------------------------------
        */

        $orderColumnIndex = (int) $request->input(
            'order.0.column',
            3
        );

        $orderDirection = strtolower(
            (string) $request->input(
                'order.0.dir',
                'asc'
            )
        );

        if (
            !in_array(
                $orderDirection,
                ['asc', 'desc'],
                true
            )
        ) {
            $orderDirection = 'asc';
        }

        /*
        |--------------------------------------------------------------------------
        | Orderable Columns
        |--------------------------------------------------------------------------
        |
        | Employer, Staff Number and Vote Number come from a relationship.
        | They are deliberately not ordered here to keep the query fast.
        |
        */

        $orderColumns = [
            0 => 'member_number',
            1 => 'penad_member_number',
            2 => 'fundworx_member_number',
            3 => 'surname',
            4 => 'national_id',
            8 => 'membership_status',
        ];

        $orderColumn = $orderColumns[
            $orderColumnIndex
        ] ?? 'surname';

        $query->orderBy(
            $orderColumn,
            $orderDirection
        );

        if ($orderColumn !== 'surname') {
            $query->orderBy(
                'surname',
                'asc'
            );
        }

        $query->orderBy(
            'first_names',
            'asc'
        );

        /*
        |--------------------------------------------------------------------------
        | Server-Side Pagination
        |--------------------------------------------------------------------------
        |
        | Only this page of records is retrieved from SQL Server.
        |
        */

        $members = $query
            ->skip($start)
            ->take($length)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Build DataTables Rows
        |--------------------------------------------------------------------------
        */

        $data = $members
            ->map(
                fn (Member $member) =>
                    $this->formatMemberRow(
                        $member
                    )
            )
            ->values();

        return response()->json([
            'draw' =>
                $draw,

            'recordsTotal' =>
                $recordsTotal,

            'recordsFiltered' =>
                $recordsFiltered,

            'data' =>
                $data,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Advanced Filters
    |--------------------------------------------------------------------------
    */

    private function applyAdvancedFilters(
        Builder $query,
        Request $request
    ): void {
        /*
        |--------------------------------------------------------------------------
        | General Search
        |--------------------------------------------------------------------------
        */

        $generalSearch = trim(
            (string) $request->input(
                'filter_search',
                ''
            )
        );

        if ($generalSearch !== '') {
            $query->where(
                function (Builder $query) use (
                    $generalSearch
                ): void {
                    $query
                        ->where(
                            'member_number',
                            'like',
                            '%' . $generalSearch . '%'
                        )
                        ->orWhere(
                            'penad_member_number',
                            'like',
                            '%' . $generalSearch . '%'
                        )
                        ->orWhere(
                            'fundworx_member_number',
                            'like',
                            '%' . $generalSearch . '%'
                        )
                        ->orWhere(
                            'national_id',
                            'like',
                            '%' . $generalSearch . '%'
                        )
                        ->orWhere(
                            'surname',
                            'like',
                            '%' . $generalSearch . '%'
                        )
                        ->orWhere(
                            'first_names',
                            'like',
                            '%' . $generalSearch . '%'
                        )
                        ->orWhere(
                            'other_names',
                            'like',
                            '%' . $generalSearch . '%'
                        )
                        ->orWhere(
                            'maiden_name',
                            'like',
                            '%' . $generalSearch . '%'
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PENERP Number
        |--------------------------------------------------------------------------
        */

        $penerpNumber = trim(
            (string) $request->input(
                'penerp_member_number',
                ''
            )
        );

        if ($penerpNumber !== '') {
            $query->where(
                'member_number',
                'like',
                '%' . $penerpNumber . '%'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PenAd Number
        |--------------------------------------------------------------------------
        */

        $penadNumber = trim(
            (string) $request->input(
                'penad_member_number',
                ''
            )
        );

        if ($penadNumber !== '') {
            $query->where(
                'penad_member_number',
                'like',
                '%' . $penadNumber . '%'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Fundworx Number
        |--------------------------------------------------------------------------
        */

        $fundworxNumber = trim(
            (string) $request->input(
                'fundworx_member_number',
                ''
            )
        );

        if ($fundworxNumber !== '') {
            $query->where(
                'fundworx_member_number',
                'like',
                '%' . $fundworxNumber . '%'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Membership Status
        |--------------------------------------------------------------------------
        */

        $status = trim(
            (string) $request->input(
                'status',
                ''
            )
        );

        if ($status !== '') {
            $query->where(
                'membership_status',
                $status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Employer
        |--------------------------------------------------------------------------
        */

        $employerId = $request->input(
            'employer_id'
        );

        if (
            $employerId !== null
            &&
            $employerId !== ''
        ) {
            $query->whereHas(
                'currentEmployment',
                function (Builder $employmentQuery) use (
                    $employerId
                ): void {
                    $employmentQuery->where(
                        'employer_id',
                        (int) $employerId
                    );
                }
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DataTables Quick Search
    |--------------------------------------------------------------------------
    */

    private function applyQuickSearch(
        Builder $query,
        string $search
    ): void {
        $query->where(
            function (Builder $query) use (
                $search
            ): void {
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
                    )
                    ->orWhere(
                        'national_id',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhereHas(
                        'currentEmployment',
                        function (
                            Builder $employmentQuery
                        ) use (
                            $search
                        ): void {
                            $employmentQuery
                                ->where(
                                    'staff_number',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'vote_number',
                                    'like',
                                    '%' . $search . '%'
                                );
                        }
                    )
                    ->orWhereHas(
                        'currentEmployment.employer',
                        function (
                            Builder $employerQuery
                        ) use (
                            $search
                        ): void {
                            $employerQuery
                                ->where(
                                    'name',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'employer_number',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'penad_employer_number',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'fundworx_employer_number',
                                    'like',
                                    '%' . $search . '%'
                                );
                        }
                    );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Format DataTables Row
    |--------------------------------------------------------------------------
    */

    private function formatMemberRow(
        Member $member
    ): array {
        $employment =
            $member->currentEmployment;

        $employer =
            $employment?->employer;

        /*
        |--------------------------------------------------------------------------
        | Member Display
        |--------------------------------------------------------------------------
        */

        $memberName =
            '<strong>'
            . e(
                $member->surname
                . ', '
                . $member->first_names
            )
            . '</strong>';

        if (
            filled(
                $member->other_names
            )
        ) {
            $memberName .=
                '<br><small>'
                . 'Other: '
                . e(
                    $member->other_names
                )
                . '</small>';
        }

        if (
            filled(
                $member->maiden_name
            )
        ) {
            $memberName .=
                '<br><small class="text-muted">'
                . 'Maiden: '
                . e(
                    $member->maiden_name
                )
                . '</small>';
        }

        if (
            filled(
                $member->date_of_birth
            )
        ) {
            try {
                $memberName .=
                    '<br><small class="text-muted">'
                    . 'DOB: '
                    . e(
                        $member
                            ->date_of_birth
                            ->format(
                                'd M Y'
                            )
                    )
                    . '</small>';
            } catch (\Throwable) {
                // Do not allow one invalid date to break the register.
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Employer Display
        |--------------------------------------------------------------------------
        */

        if ($employer) {
            $employerDisplay =
                '<strong>'
                . e(
                    $employer->name
                )
                . '</strong>'
                . '<br><small class="text-muted">'
                . e(
                    $employer->employer_number
                    ?? ''
                )
                . '</small>';
        } else {
            $employerDisplay =
                '<span class="text-muted">-</span>';
        }

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        $statusClass = match (
            strtolower(
                (string)
                $member->membership_status
            )
        ) {
            'active' =>
                'bg-success',

            'dormant' =>
                'bg-warning text-dark',

            'suspended' =>
                'bg-danger',

            'inactive' =>
                'bg-secondary',

            default =>
                'bg-secondary',
        };

        $status =
            '<span class="badge '
            . $statusClass
            . '">'
            . e(
                ucfirst(
                    (string)
                    $member->membership_status
                )
            )
            . '</span>';

        /*
        |--------------------------------------------------------------------------
        | Action Buttons
        |--------------------------------------------------------------------------
        */

        $actions =
            '<div class="member-action-buttons">'
            . '<a href="'
            . e(
                route(
                    'pensions-administration.updates.members.show',
                    $member
                )
            )
            . '" class="btn btn-sm btn-outline-primary me-1" title="View Member">'
            . '<i class="mdi mdi-eye-outline"></i>'
            . '</a>'

            . '<a href="'
            . e(
                route(
                    'pensions-administration.contributions.members.expected-contributions',
                    $member
                )
            )
            . '" class="btn btn-sm btn-outline-primary me-1" title="Expected Contribution History">'
            . '<i class="mdi mdi-history"></i>'
            . '</a>'

            . '<a href="'
            . e(
                route(
                    'pensions-administration.updates.members.edit',
                    $member
                )
            )
            . '" class="btn btn-sm btn-primary" title="Edit Member">'
            . '<i class="mdi mdi-pencil-outline"></i>'
            . '</a>'
            . '</div>';

        return [
            'penerp_number' =>
                '<strong>'
                . e(
                    $member->member_number
                )
                . '</strong>',

            'penad_number' =>
                e(
                    $member->penad_member_number
                    ?? '-'
                ),

            'fundworx_number' =>
                e(
                    $member->fundworx_member_number
                    ?? '-'
                ),

            'member' =>
                $memberName,

            'national_id' =>
                e(
                    $member->national_id
                    ?? '-'
                ),

            'employer' =>
                $employerDisplay,

            'staff_number' =>
                e(
                    $employment?->staff_number
                    ?? '-'
                ),

            'vote_number' =>
                e(
                    $employment?->vote_number
                    ?? '-'
                ),

            'status' =>
                $status,

            'actions' =>
                $actions,
        ];
    }
}
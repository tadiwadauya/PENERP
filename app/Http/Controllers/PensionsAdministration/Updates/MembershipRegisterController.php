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
        */

        $recordsTotal = Member::query()->count();

        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        |
        | We load all employment relationships for each member on the current
        | DataTables page.
        |
        | Display priority:
        |
        | 1. Current employment
        | 2. Latest historical employment
        |
        | This means exited/non-active members can still show their last
        | employer without incorrectly marking that employment as current.
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
                'employments' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'member_id',
                            'employer_id',
                            'staff_number',
                            'vote_number',
                            'employment_status',
                            'is_current',
                            'effective_from',
                            'effective_to',
                        ])
                        ->with([
                            'employer' => function ($query) {
                                $query->select([
                                    'id',
                                    'employer_number',
                                    'penad_employer_number',
                                    'fundworx_employer_number',
                                    'name',
                                ]);
                            },
                        ])
                        ->orderByDesc('is_current')
                        ->orderByDesc('effective_from')
                        ->orderByDesc('id');
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
        | Employer, Staff Number and Vote Number come from relationships and
        | are deliberately not ordered here to keep the member query fast.
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
        |
        | IMPORTANT:
        |
        | Search all employments, not only currentEmployment.
        |
        | This allows an exited historical member to still be found under the
        | employer they belonged to.
        |
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
                'employments',
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
                        'employments',
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
                        'employments.employer',
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
        /*
        |--------------------------------------------------------------------------
        | Resolve Employment For Register Display
        |--------------------------------------------------------------------------
        |
        | Rules:
        |
        | 1. Prefer current employment.
        | 2. If there is no current employment, use latest historical employment.
        | 3. If no employment exists at all, display "-".
        |
        */

        $employment = $member
            ->employments
            ->sort(
                function ($a, $b): int {
                    /*
                    |--------------------------------------------------------------------------
                    | Current Employment First
                    |--------------------------------------------------------------------------
                    */

                    $aCurrent =
                        (bool) $a->is_current;

                    $bCurrent =
                        (bool) $b->is_current;

                    if ($aCurrent !== $bCurrent) {
                        return $aCurrent
                            ? -1
                            : 1;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Latest Effective From Next
                    |--------------------------------------------------------------------------
                    */

                    $aEffectiveFrom =
                        $a->effective_from
                            ? strtotime(
                                (string) $a->effective_from
                            )
                            : 0;

                    $bEffectiveFrom =
                        $b->effective_from
                            ? strtotime(
                                (string) $b->effective_from
                            )
                            : 0;

                    if (
                        $aEffectiveFrom
                        !==
                        $bEffectiveFrom
                    ) {
                        return
                            $bEffectiveFrom
                            <=>
                            $aEffectiveFrom;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Highest Employment ID Last Tie-Breaker
                    |--------------------------------------------------------------------------
                    */

                    return
                        (int) $b->id
                        <=>
                        (int) $a->id;
                }
            )
            ->first();

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
                /*
                 * Do not allow one invalid date to break the register.
                 */
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
                . '</strong>';

            $employerReference =
                $employer->employer_number
                ??
                $employer->penad_employer_number
                ??
                $employer->fundworx_employer_number
                ??
                null;

            if (
                filled(
                    $employerReference
                )
            ) {
                $employerDisplay .=
                    '<br><small class="text-muted">'
                    . e(
                        $employerReference
                    )
                    . '</small>';
            }

            /*
            |--------------------------------------------------------------------------
            | Historical Employment Indicator
            |--------------------------------------------------------------------------
            |
            | Do not imply that an exited member is currently employed.
            |
            */

            if (
                !$employment->is_current
            ) {
                $employerDisplay .=
                    '<br><small class="text-muted">'
                    . 'Historical Employment'
                    . '</small>';
            }
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

            'exited' =>
                'bg-secondary',

            'waiting approval' =>
                'bg-warning text-dark',

            'waiting_approval' =>
                'bg-warning text-dark',

            'deferred' =>
                'bg-info text-dark',

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

        /*
        |--------------------------------------------------------------------------
        | Row
        |--------------------------------------------------------------------------
        */

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
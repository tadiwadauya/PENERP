<?php

namespace App\Http\Controllers\PensionsAdministration\Updates;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Updates\Employer;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmployerMembershipReportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Employer Membership Summary Page
    |--------------------------------------------------------------------------
    */

    public function index(): View
    {
        $summary = DB::table('members AS m')
            ->whereNull('m.deleted_at')
            ->selectRaw("
                COUNT(*) AS total_members,

                SUM(
                    CASE
                        WHEN LOWER(LTRIM(RTRIM(m.membership_status))) = 'active'
                        THEN 1 ELSE 0
                    END
                ) AS active_members,

                SUM(
                    CASE
                        WHEN LOWER(LTRIM(RTRIM(m.membership_status))) = 'inactive'
                        THEN 1 ELSE 0
                    END
                ) AS inactive_members,

                SUM(
                    CASE
                        WHEN LOWER(LTRIM(RTRIM(m.membership_status))) = 'exited'
                        THEN 1 ELSE 0
                    END
                ) AS exited_members,

                SUM(
                    CASE
                        WHEN LOWER(LTRIM(RTRIM(m.membership_status))) = 'suspended'
                        THEN 1 ELSE 0
                    END
                ) AS suspended_members,

                SUM(
                    CASE
                        WHEN LOWER(LTRIM(RTRIM(m.membership_status))) IN (
                            'waiting approval',
                            'waiting_approval'
                        )
                        THEN 1 ELSE 0
                    END
                ) AS waiting_approval_members,

                SUM(
                    CASE
                        WHEN LOWER(LTRIM(RTRIM(m.membership_status))) = 'deferred'
                        THEN 1 ELSE 0
                    END
                ) AS deferred_members
            ")
            ->first();

        return view(
            'pensions-administration.updates.reports.employer-membership.index',
            [
                'summary' => [
                    'total_members' => (int) ($summary->total_members ?? 0),
                    'active_members' => (int) ($summary->active_members ?? 0),
                    'inactive_members' => (int) ($summary->inactive_members ?? 0),
                    'exited_members' => (int) ($summary->exited_members ?? 0),
                    'suspended_members' => (int) ($summary->suspended_members ?? 0),
                    'waiting_approval_members' => (int) ($summary->waiting_approval_members ?? 0),
                    'deferred_members' => (int) ($summary->deferred_members ?? 0),
                ],
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Employer Summary Data
    |--------------------------------------------------------------------------
    */

    public function data(Request $request): JsonResponse
    {
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
        | Employer Base
        |--------------------------------------------------------------------------
        */

        $base = DB::table('employers AS e')
            ->leftJoin(
                'member_employments AS me',
                'me.employer_id',
                '=',
                'e.id'
            )
            ->leftJoin(
                'members AS m',
                function ($join): void {
                    $join
                        ->on(
                            'm.id',
                            '=',
                            'me.member_id'
                        )
                        ->whereNull(
                            'm.deleted_at'
                        );
                }
            );

        /*
        |--------------------------------------------------------------------------
        | DataTables Search
        |--------------------------------------------------------------------------
        */

        $search = trim(
            (string) $request->input(
                'search.value',
                ''
            )
        );

        if ($search !== '') {
            $like = '%' . $search . '%';

            $base->where(
                function (Builder $query) use ($like): void {
                    $query
                        ->where(
                            'e.employer_number',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'e.penad_employer_number',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'e.fundworx_employer_number',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'e.name',
                            'like',
                            $like
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Employer Count
        |--------------------------------------------------------------------------
        */

        $recordsFiltered = (clone $base)
            ->distinct()
            ->count('e.id');

        /*
        |--------------------------------------------------------------------------
        | Employer Summary
        |--------------------------------------------------------------------------
        */

        $query = $base
            ->selectRaw("
                e.id,
                e.employer_number,
                e.penad_employer_number,
                e.fundworx_employer_number,
                e.name,

                COUNT(DISTINCT m.id) AS total_members,

                COUNT(DISTINCT CASE
                    WHEN LOWER(LTRIM(RTRIM(m.membership_status))) = 'active'
                    THEN m.id
                END) AS active_members,

                COUNT(DISTINCT CASE
                    WHEN LOWER(LTRIM(RTRIM(m.membership_status))) = 'inactive'
                    THEN m.id
                END) AS inactive_members,

                COUNT(DISTINCT CASE
                    WHEN LOWER(LTRIM(RTRIM(m.membership_status))) = 'exited'
                    THEN m.id
                END) AS exited_members,

                COUNT(DISTINCT CASE
                    WHEN LOWER(LTRIM(RTRIM(m.membership_status))) = 'suspended'
                    THEN m.id
                END) AS suspended_members,

                COUNT(DISTINCT CASE
                    WHEN LOWER(LTRIM(RTRIM(m.membership_status))) IN (
                        'waiting approval',
                        'waiting_approval'
                    )
                    THEN m.id
                END) AS waiting_approval_members,

                COUNT(DISTINCT CASE
                    WHEN LOWER(LTRIM(RTRIM(m.membership_status))) = 'deferred'
                    THEN m.id
                END) AS deferred_members
            ")
            ->groupBy([
                'e.id',
                'e.employer_number',
                'e.penad_employer_number',
                'e.fundworx_employer_number',
                'e.name',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Ordering
        |--------------------------------------------------------------------------
        */

        $orderColumns = [
            'e.employer_number',
            'e.penad_employer_number',
            'e.fundworx_employer_number',
            'e.name',
            'total_members',
            'active_members',
            'inactive_members',
            'exited_members',
            'suspended_members',
            'waiting_approval_members',
            'deferred_members',
        ];

        $orderIndex = (int) $request->input(
            'order.0.column',
            3
        );

        /*
        | Nil Contributors and Latest Period are calculated after
        | pagination, therefore we don't SQL-order by those columns.
        */

        if ($orderIndex >= count($orderColumns)) {
            $orderIndex = 3;
        }

        $orderDirection = strtolower(
            (string) $request->input(
                'order.0.dir',
                'asc'
            )
        );

        $orderDirection =
            $orderDirection === 'desc'
                ? 'desc'
                : 'asc';

        $orderColumn =
            $orderColumns[$orderIndex]
            ??
            'e.name';

        /*
        |--------------------------------------------------------------------------
        | Retrieve Employer Page
        |--------------------------------------------------------------------------
        */

        $rows = $query
            ->orderBy(
                $orderColumn,
                $orderDirection
            )
            ->offset($start)
            ->limit($length)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Only Calculate Nil Contributors For Employers On This Page
        |--------------------------------------------------------------------------
        |
        | If 25 employers are displayed, we only calculate those 25.
        |
        */

        $employerIds = $rows
            ->pluck('id')
            ->map(
                fn ($id) => (int) $id
            )
            ->values()
            ->all();

        $latestPeriods =
            $this->latestPeriodsForEmployers(
                $employerIds
            );

        $nilContributorCounts =
            $this->nilContributorCountsForEmployers(
                $employerIds,
                $latestPeriods
            );

        /*
        |--------------------------------------------------------------------------
        | Format
        |--------------------------------------------------------------------------
        */

        $data = $rows
            ->map(
                function ($row) use (
                    $latestPeriods,
                    $nilContributorCounts
                ): array {
                    $employerId =
                        (int) $row->id;

                    $latestPeriod =
                        $latestPeriods[
                            $employerId
                        ]
                        ??
                        null;

                    $nilCount =
                        $nilContributorCounts[
                            $employerId
                        ]
                        ??
                        0;

                    return [
                        'employer_number' =>
                            e(
                                $row->employer_number
                                ?: '-'
                            ),

                        'penad_employer_number' =>
                            e(
                                $row->penad_employer_number
                                ?: '-'
                            ),

                        'fundworx_employer_number' =>
                            e(
                                $row->fundworx_employer_number
                                ?: '-'
                            ),

                        'employer_name' =>
                            '<strong>'
                            . e($row->name)
                            . '</strong>',

                        'total_members' =>
                            $this->memberCountLink(
                                employerId: $employerId,
                                count: (int) $row->total_members,
                                status: null
                            ),

                        'active_members' =>
                            $this->memberCountLink(
                                employerId: $employerId,
                                count: (int) $row->active_members,
                                status: 'active'
                            ),

                        'inactive_members' =>
                            $this->memberCountLink(
                                employerId: $employerId,
                                count: (int) $row->inactive_members,
                                status: 'inactive'
                            ),

                        'exited_members' =>
                            $this->memberCountLink(
                                employerId: $employerId,
                                count: (int) $row->exited_members,
                                status: 'exited'
                            ),

                        'suspended_members' =>
                            $this->memberCountLink(
                                employerId: $employerId,
                                count: (int) $row->suspended_members,
                                status: 'suspended'
                            ),

                        'waiting_approval_members' =>
                            $this->memberCountLink(
                                employerId: $employerId,
                                count: (int) $row->waiting_approval_members,
                                status: 'waiting_approval'
                            ),

                        'deferred_members' =>
                            $this->memberCountLink(
                                employerId: $employerId,
                                count: (int) $row->deferred_members,
                                status: 'deferred'
                            ),

                        'nil_contributors' =>
                            $this->memberCountLink(
                                employerId: $employerId,
                                count: $nilCount,
                                status: 'nil_contributor'
                            ),

                        'latest_period' =>
                            $latestPeriod
                                ? Carbon::parse(
                                    $latestPeriod
                                )->format('M Y')
                                : '<span class="text-muted">No contributions</span>',

                        'action' =>
                            '<a href="'
                            . e(
                                route(
                                    'pensions-administration.updates.reports.employer-membership.members',
                                    $employerId
                                )
                            )
                            . '" class="btn btn-sm btn-primary">'
                            . '<i class="mdi mdi-account-group-outline me-1"></i>'
                            . 'View Members'
                            . '</a>',
                    ];
                }
            )
            ->values();

        return response()->json([
            'draw' =>
                $draw,

            'recordsTotal' =>
                $recordsFiltered,

            'recordsFiltered' =>
                $recordsFiltered,

            'data' =>
                $data,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Employer Members Page
    |--------------------------------------------------------------------------
    */

    public function members(
        Request $request,
        Employer $employer
    ): View {
        $status = trim(
            (string) $request->query(
                'status',
                ''
            )
        );

        $allowedStatuses = [
            '',
            'active',
            'inactive',
            'exited',
            'suspended',
            'waiting_approval',
            'deferred',

            /*
            |--------------------------------------------------------------------------
            | Reporting Classification
            |--------------------------------------------------------------------------
            */

            'nil_contributor',
        ];

        if (
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {
            $status = '';
        }

        /*
        |--------------------------------------------------------------------------
        | Employer Latest Contribution Period
        |--------------------------------------------------------------------------
        */

        $latestContributionPeriod =
            DB::table(
                'member_contributions'
            )
                ->where(
                    'employer_id',
                    $employer->id
                )
                ->max(
                    'period_date'
                );

        return view(
            'pensions-administration.updates.reports.employer-membership.members',
            compact(
                'employer',
                'status',
                'latestContributionPeriod'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Employer Members Data
    |--------------------------------------------------------------------------
    */

    public function membersData(
        Request $request,
        Employer $employer
    ): JsonResponse {
        $draw =
            (int) $request->input(
                'draw',
                1
            );

        $start =
            max(
                0,
                (int) $request->input(
                    'start',
                    0
                )
            );

        $length =
            (int) $request->input(
                'length',
                25
            );

        if (
            $length < 1
            ||
            $length > 100
        ) {
            $length = 25;
        }

        $status = trim(
            (string) $request->input(
                'membership_status',
                ''
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Nil Contributors
        |--------------------------------------------------------------------------
        */

        if (
            $status
            ===
            'nil_contributor'
        ) {
            return $this->nilContributorMembersData(
                request: $request,
                employer: $employer,
                draw: $draw,
                start: $start,
                length: $length
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Normal Employer Membership Query
        |--------------------------------------------------------------------------
        */

        $query = DB::table('members AS m')
            ->join(
                'member_employments AS me',
                'me.member_id',
                '=',
                'm.id'
            )
            ->where(
                'me.employer_id',
                $employer->id
            )
            ->whereNull(
                'm.deleted_at'
            );

        /*
        |--------------------------------------------------------------------------
        | Membership Status
        |--------------------------------------------------------------------------
        */

        if ($status !== '') {
            if (
                $status
                ===
                'waiting_approval'
            ) {
                $query->where(
                    function (
                        Builder $query
                    ): void {
                        $query
                            ->whereRaw(
                                "LOWER(LTRIM(RTRIM(m.membership_status))) = ?",
                                [
                                    'waiting approval',
                                ]
                            )
                            ->orWhereRaw(
                                "LOWER(LTRIM(RTRIM(m.membership_status))) = ?",
                                [
                                    'waiting_approval',
                                ]
                            );
                    }
                );
            } else {
                $query->whereRaw(
                    "LOWER(LTRIM(RTRIM(m.membership_status))) = ?",
                    [
                        strtolower(
                            $status
                        ),
                    ]
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        $search = trim(
            (string) $request->input(
                'search.value',
                ''
            )
        );

        if ($search !== '') {
            $like =
                '%'
                . $search
                . '%';

            $query->where(
                function (Builder $query) use ($like): void {
                    $query
                        ->where(
                            'm.member_number',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'm.penad_member_number',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'm.fundworx_member_number',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'm.surname',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'm.first_names',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'm.other_names',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'm.national_id',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'me.staff_number',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'me.vote_number',
                            'like',
                            $like
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Unique Member Count
        |--------------------------------------------------------------------------
        */

        $recordsFiltered = DB::query()
            ->fromSub(
                (clone $query)
                    ->select(
                        'm.id'
                    )
                    ->distinct(),
                'member_count'
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | One Row Per Member
        |--------------------------------------------------------------------------
        */

        $rows = $query
            ->selectRaw("
                m.id,
                m.member_number,
                m.penad_member_number,
                m.fundworx_member_number,
                m.surname,
                m.first_names,
                m.other_names,
                m.national_id,
                m.date_of_birth,
                m.date_joined_fund,
                m.membership_status,

                MAX(me.staff_number) AS staff_number,
                MAX(me.vote_number) AS vote_number,
                MAX(me.date_joined_employer) AS date_joined_employer,
                MAX(me.employment_status) AS employment_status
            ")
            ->groupBy([
                'm.id',
                'm.member_number',
                'm.penad_member_number',
                'm.fundworx_member_number',
                'm.surname',
                'm.first_names',
                'm.other_names',
                'm.national_id',
                'm.date_of_birth',
                'm.date_joined_fund',
                'm.membership_status',
            ])
            ->orderBy(
                'm.surname'
            )
            ->orderBy(
                'm.first_names'
            )
            ->orderBy(
                'm.id'
            )
            ->offset(
                $start
            )
            ->limit(
                $length
            )
            ->get();

        return response()->json([
            'draw' =>
                $draw,

            'recordsTotal' =>
                $recordsFiltered,

            'recordsFiltered' =>
                $recordsFiltered,

            'data' =>
                $rows
                    ->map(
                        fn ($row) =>
                            $this->formatMemberRow(
                                $row
                            )
                    )
                    ->values(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Nil Contributor Member Listing
    |--------------------------------------------------------------------------
    |
    | A Nil Contributor is:
    |
    | 1. Active
    | 2. Associated with this employer
    | 3. Has no positive statutory contribution in the employer's latest
    |    contribution period.
    |
    | This catches:
    |
    | - no contribution record at all; OR
    | - contribution record exists but employee/employer amounts are 0.0000.
    |
    */

    private function nilContributorMembersData(
        Request $request,
        Employer $employer,
        int $draw,
        int $start,
        int $length
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Latest Employer Contribution Period
        |--------------------------------------------------------------------------
        */

        $latestPeriod =
            DB::table(
                'member_contributions'
            )
                ->where(
                    'employer_id',
                    $employer->id
                )
                ->max(
                    'period_date'
                );

        if (!$latestPeriod) {
            return response()->json([
                'draw' =>
                    $draw,

                'recordsTotal' =>
                    0,

                'recordsFiltered' =>
                    0,

                'data' =>
                    [],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Active Members For Employer
        |--------------------------------------------------------------------------
        */

        $query = DB::table('members AS m')
            ->join(
                'member_employments AS me',
                'me.member_id',
                '=',
                'm.id'
            )
            ->where(
                'me.employer_id',
                $employer->id
            )
            ->whereNull(
                'm.deleted_at'
            )
            ->whereRaw(
                "LOWER(LTRIM(RTRIM(m.membership_status))) = 'active'"
            )

            /*
            |--------------------------------------------------------------------------
            | No Positive Contribution For Latest Period
            |--------------------------------------------------------------------------
            */

            ->whereNotExists(
                function ($subQuery) use (
                    $employer,
                    $latestPeriod
                ): void {
                    $subQuery
                        ->selectRaw('1')
                        ->from(
                            'member_contributions AS mc'
                        )
                        ->whereColumn(
                            'mc.member_id',
                            'm.id'
                        )
                        ->where(
                            'mc.employer_id',
                            $employer->id
                        )
                        ->whereDate(
                            'mc.period_date',
                            $latestPeriod
                        )
                        ->whereRaw("
                            (
                                CASE
                                    WHEN mc.source_system = 'historical_migration'
                                        THEN
                                            COALESCE(mc.employee_contribution, 0)
                                            +
                                            COALESCE(mc.employer_contribution, 0)

                                    ELSE
                                            COALESCE(mc.zwg_employee_contribution, 0)
                                            +
                                            COALESCE(mc.zwg_employer_contribution, 0)
                                END
                            ) > 0
                        ");
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        $search = trim(
            (string) $request->input(
                'search.value',
                ''
            )
        );

        if ($search !== '') {
            $like =
                '%'
                . $search
                . '%';

            $query->where(
                function (Builder $query) use ($like): void {
                    $query
                        ->where(
                            'm.member_number',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'm.penad_member_number',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'm.fundworx_member_number',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'm.surname',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'm.first_names',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'm.other_names',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'm.national_id',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'me.staff_number',
                            'like',
                            $like
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Unique Count
        |--------------------------------------------------------------------------
        */

        $recordsFiltered = DB::query()
            ->fromSub(
                (clone $query)
                    ->select(
                        'm.id'
                    )
                    ->distinct(),
                'nil_member_count'
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Page
        |--------------------------------------------------------------------------
        */

        $rows = $query
            ->selectRaw("
                m.id,
                m.member_number,
                m.penad_member_number,
                m.fundworx_member_number,
                m.surname,
                m.first_names,
                m.other_names,
                m.national_id,
                m.date_of_birth,
                m.date_joined_fund,
                m.membership_status,

                MAX(me.staff_number) AS staff_number,
                MAX(me.vote_number) AS vote_number,
                MAX(me.date_joined_employer) AS date_joined_employer,
                MAX(me.employment_status) AS employment_status
            ")
            ->groupBy([
                'm.id',
                'm.member_number',
                'm.penad_member_number',
                'm.fundworx_member_number',
                'm.surname',
                'm.first_names',
                'm.other_names',
                'm.national_id',
                'm.date_of_birth',
                'm.date_joined_fund',
                'm.membership_status',
            ])
            ->orderBy(
                'm.surname'
            )
            ->orderBy(
                'm.first_names'
            )
            ->orderBy(
                'm.id'
            )
            ->offset(
                $start
            )
            ->limit(
                $length
            )
            ->get();

        $data = $rows
            ->map(
                function ($row) use (
                    $latestPeriod
                ): array {
                    $formatted =
                        $this->formatMemberRow(
                            $row
                        );

                    $formatted[
                        'contribution_status'
                    ] =
                        '<span class="badge bg-warning text-dark">'
                        . 'Nil Contributor'
                        . '</span>';

                    $formatted[
                        'latest_period'
                    ] =
                        Carbon::parse(
                            $latestPeriod
                        )->format(
                            'M Y'
                        );

                    return $formatted;
                }
            )
            ->values();

        return response()->json([
            'draw' =>
                $draw,

            'recordsTotal' =>
                $recordsFiltered,

            'recordsFiltered' =>
                $recordsFiltered,

            'data' =>
                $data,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Latest Period For Employer Page
    |--------------------------------------------------------------------------
    */

    private function latestPeriodsForEmployers(
        array $employerIds
    ): array {
        if (empty($employerIds)) {
            return [];
        }

        return DB::table(
            'member_contributions'
        )
            ->whereIn(
                'employer_id',
                $employerIds
            )
            ->selectRaw("
                employer_id,
                MAX(period_date) AS latest_period
            ")
            ->groupBy(
                'employer_id'
            )
            ->get()
            ->mapWithKeys(
                fn ($row) => [
                    (int) $row->employer_id =>
                        $row->latest_period,
                ]
            )
            ->all();
    }


    /*
    |--------------------------------------------------------------------------
    | Nil Contributor Counts
    |--------------------------------------------------------------------------
    |
    | Counts are calculated only for the employers currently visible
    | on the Employer Membership DataTable page.
    |
    */

    private function nilContributorCountsForEmployers(
        array $employerIds,
        array $latestPeriods
    ): array {
        if (
            empty($employerIds)
            ||
            empty($latestPeriods)
        ) {
            return [];
        }

        $counts = [];

        foreach (
            $employerIds
            as $employerId
        ) {
            $latestPeriod =
                $latestPeriods[
                    $employerId
                ]
                ??
                null;

            if (!$latestPeriod) {
                $counts[
                    $employerId
                ] = 0;

                continue;
            }

            $count = DB::table('members AS m')
                ->join(
                    'member_employments AS me',
                    'me.member_id',
                    '=',
                    'm.id'
                )
                ->where(
                    'me.employer_id',
                    $employerId
                )
                ->whereNull(
                    'm.deleted_at'
                )
                ->whereRaw(
                    "LOWER(LTRIM(RTRIM(m.membership_status))) = 'active'"
                )
                ->whereNotExists(
                    function ($subQuery) use (
                        $employerId,
                        $latestPeriod
                    ): void {
                        $subQuery
                            ->selectRaw('1')
                            ->from(
                                'member_contributions AS mc'
                            )
                            ->whereColumn(
                                'mc.member_id',
                                'm.id'
                            )
                            ->where(
                                'mc.employer_id',
                                $employerId
                            )
                            ->whereDate(
                                'mc.period_date',
                                $latestPeriod
                            )
                            ->whereRaw("
                                (
                                    CASE
                                        WHEN mc.source_system = 'historical_migration'
                                            THEN
                                                COALESCE(mc.employee_contribution, 0)
                                                +
                                                COALESCE(mc.employer_contribution, 0)

                                        ELSE
                                            COALESCE(mc.zwg_employee_contribution, 0)
                                            +
                                            COALESCE(mc.zwg_employer_contribution, 0)
                                    END
                                ) > 0
                            ");
                    }
                )
                ->distinct()
                ->count(
                    'm.id'
                );

            $counts[
                $employerId
            ] =
                (int) $count;
        }

        return $counts;
    }


    /*
    |--------------------------------------------------------------------------
    | Format Member Row
    |--------------------------------------------------------------------------
    */

    private function formatMemberRow(
        mixed $row
    ): array {
        $memberName = trim(
            implode(
                ' ',
                array_filter([
                    $row->first_names,
                    $row->other_names,
                    $row->surname,
                ])
            )
        );

        return [
            'member_number' =>
                e(
                    $row->member_number
                    ?: '-'
                ),

            'penad_member_number' =>
                e(
                    $row->penad_member_number
                    ?: '-'
                ),

            'fundworx_member_number' =>
                e(
                    $row->fundworx_member_number
                    ?: '-'
                ),

            'staff_number' =>
                e(
                    $row->staff_number
                    ?: '-'
                ),

            'member' =>
                '<strong>'
                . e(
                    $memberName
                    ?: '-'
                )
                . '</strong>',

            'national_id' =>
                e(
                    $row->national_id
                    ?: '-'
                ),

            'date_of_birth' =>
                $row->date_of_birth
                    ? Carbon::parse(
                        $row->date_of_birth
                    )->format(
                        'd M Y'
                    )
                    : '-',

            'date_joined_fund' =>
                $row->date_joined_fund
                    ? Carbon::parse(
                        $row->date_joined_fund
                    )->format(
                        'd M Y'
                    )
                    : '-',

            'date_joined_employer' =>
                $row->date_joined_employer
                    ? Carbon::parse(
                        $row->date_joined_employer
                    )->format(
                        'd M Y'
                    )
                    : '-',

            'membership_status' =>
                $this->statusBadge(
                    $row->membership_status
                ),

            'employment_status' =>
                e(
                    $row->employment_status
                    ?: '-'
                ),

            'contribution_status' =>
                '-',

            'latest_period' =>
                '-',

            'action' =>
                '<a href="'
                . e(
                    route(
                        'pensions-administration.updates.members.show',
                        $row->id
                    )
                )
                . '" class="btn btn-sm btn-light me-1">'
                . '<i class="mdi mdi-eye-outline"></i>'
                . '</a>'

                . '<a href="'
                . e(
                    route(
                        'pensions-administration.updates.members.edit',
                        $row->id
                    )
                )
                . '" class="btn btn-sm btn-primary">'
                . '<i class="mdi mdi-pencil-outline"></i>'
                . '</a>',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Clickable Count
    |--------------------------------------------------------------------------
    */

    private function memberCountLink(
        int $employerId,
        int $count,
        ?string $status
    ): string {
        if ($count <= 0) {
            return '<span class="text-muted">0</span>';
        }

        $parameters = [
            'employer' =>
                $employerId,
        ];

        if ($status !== null) {
            $parameters[
                'status'
            ] =
                $status;
        }

        $url = route(
            'pensions-administration.updates.reports.employer-membership.members',
            $parameters
        );

        $class =
            $status === 'nil_contributor'
                ? 'text-warning'
                : 'text-primary';

        return
            '<a href="'
            . e($url)
            . '" class="fw-semibold '
            . $class
            . ' text-decoration-none">'
            . number_format(
                $count
            )
            . '</a>';
    }


    /*
    |--------------------------------------------------------------------------
    | Membership Status Badge
    |--------------------------------------------------------------------------
    */

    private function statusBadge(
        mixed $status
    ): string {
        $status = strtolower(
            trim(
                (string) $status
            )
        );

        $class =
            match ($status) {
                'active' =>
                    'bg-success',

                'exited' =>
                    'bg-dark',

                'inactive' =>
                    'bg-secondary',

                'suspended' =>
                    'bg-danger',

                'waiting approval',
                'waiting_approval' =>
                    'bg-warning text-dark',

                'deferred' =>
                    'bg-info text-dark',

                default =>
                    'bg-secondary',
            };

        $label =
            match ($status) {
                'waiting_approval' =>
                    'Waiting Approval',

                default =>
                    ucwords(
                        str_replace(
                            '_',
                            ' ',
                            $status
                        )
                    ),
            };

        return
            '<span class="badge '
            . $class
            . '">'
            . e(
                $label
                ?: 'Not Specified'
            )
            . '</span>';
    }
}
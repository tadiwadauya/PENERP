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

class MembershipReportController extends Controller
{
    public function index(Request $request): View
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

        $base = $this->filteredMemberQuery($request);

        /*
        |--------------------------------------------------------------------------
        | Main Summary
        |--------------------------------------------------------------------------
        */

        $summaryRow = (clone $base)
            ->selectRaw("
                COUNT(DISTINCT m.id) AS total,

                COUNT(DISTINCT CASE
                    WHEN LOWER(m.membership_status) = 'active'
                    THEN m.id
                END) AS active,

                COUNT(DISTINCT CASE
                    WHEN LOWER(m.membership_status) = 'dormant'
                    THEN m.id
                END) AS dormant,

                COUNT(DISTINCT CASE
                    WHEN LOWER(m.membership_status) = 'inactive'
                    THEN m.id
                END) AS inactive,

                COUNT(DISTINCT CASE
                    WHEN LOWER(m.membership_status) = 'suspended'
                    THEN m.id
                END) AS suspended,

                COUNT(DISTINCT CASE
                    WHEN m.national_id IS NULL
                      OR LTRIM(RTRIM(m.national_id)) = ''
                    THEN m.id
                END) AS without_national_id,

                COUNT(DISTINCT CASE
                    WHEN m.date_of_birth IS NULL
                    THEN m.id
                END) AS without_dob,

                COUNT(DISTINCT CASE
                    WHEN me.id IS NULL
                      OR e.id IS NULL
                    THEN m.id
                END) AS without_employer,

                COUNT(DISTINCT CASE
                    WHEN m.penad_member_number IS NULL
                      OR LTRIM(RTRIM(m.penad_member_number)) = ''
                    THEN m.id
                END) AS without_penad_number,

                COUNT(DISTINCT CASE
                    WHEN m.fundworx_member_number IS NULL
                      OR LTRIM(RTRIM(m.fundworx_member_number)) = ''
                    THEN m.id
                END) AS without_fundworx_number,

                COUNT(DISTINCT CASE
                    WHEN m.email IS NULL
                      OR LTRIM(RTRIM(m.email)) = ''
                    THEN m.id
                END) AS without_email,

                COUNT(DISTINCT CASE
                    WHEN m.cell_number IS NULL
                      OR LTRIM(RTRIM(m.cell_number)) = ''
                    THEN m.id
                END) AS without_cell_number
            ")
            ->first();

        $summary = [
            'total' => (int) ($summaryRow->total ?? 0),
            'active' => (int) ($summaryRow->active ?? 0),
            'dormant' => (int) ($summaryRow->dormant ?? 0),
            'inactive' => (int) ($summaryRow->inactive ?? 0),
            'suspended' => (int) ($summaryRow->suspended ?? 0),
            'without_national_id' => (int) ($summaryRow->without_national_id ?? 0),
            'without_dob' => (int) ($summaryRow->without_dob ?? 0),
            'without_employer' => (int) ($summaryRow->without_employer ?? 0),
            'without_penad_number' => (int) ($summaryRow->without_penad_number ?? 0),
            'without_fundworx_number' => (int) ($summaryRow->without_fundworx_number ?? 0),
            'without_email' => (int) ($summaryRow->without_email ?? 0),
            'without_cell_number' => (int) ($summaryRow->without_cell_number ?? 0),
        ];

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        $statusSummary = (clone $base)
            ->selectRaw("
                COALESCE(
                    NULLIF(LTRIM(RTRIM(m.membership_status)), ''),
                    'Not Specified'
                ) AS status,
                COUNT(DISTINCT m.id) AS total
            ")
            ->groupBy('m.membership_status')
            ->orderBy('status')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Gender
        |--------------------------------------------------------------------------
        */

        $genderSummary = (clone $base)
            ->selectRaw("
                CASE
                    WHEN LOWER(LTRIM(RTRIM(m.gender))) = 'male'
                        THEN 'Male'
                    WHEN LOWER(LTRIM(RTRIM(m.gender))) = 'female'
                        THEN 'Female'
                    ELSE 'Not Specified'
                END AS gender,

                COUNT(DISTINCT m.id) AS total
            ")
            ->groupByRaw("
                CASE
                    WHEN LOWER(LTRIM(RTRIM(m.gender))) = 'male'
                        THEN 'Male'
                    WHEN LOWER(LTRIM(RTRIM(m.gender))) = 'female'
                        THEN 'Female'
                    ELSE 'Not Specified'
                END
            ")
            ->orderBy('gender')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Employer Summary
        |--------------------------------------------------------------------------
        */

        $employerSummary = (clone $base)
            ->whereNotNull('e.id')
            ->selectRaw("
                e.id,
                e.employer_number,
                e.penad_employer_number,
                e.fundworx_employer_number,
                e.name,

                COUNT(DISTINCT m.id) AS total_members,

                COUNT(DISTINCT CASE
                    WHEN LOWER(m.membership_status) = 'active'
                    THEN m.id
                END) AS active_members,

                COUNT(DISTINCT CASE
                    WHEN LOWER(m.membership_status) = 'dormant'
                    THEN m.id
                END) AS dormant_members,

                COUNT(DISTINCT CASE
                    WHEN LOWER(m.membership_status) = 'inactive'
                    THEN m.id
                END) AS inactive_members,

                COUNT(DISTINCT CASE
                    WHEN LOWER(m.membership_status) = 'suspended'
                    THEN m.id
                END) AS suspended_members,

                COUNT(DISTINCT CASE
                    WHEN LOWER(LTRIM(RTRIM(m.gender))) = 'male'
                    THEN m.id
                END) AS male_members,

                COUNT(DISTINCT CASE
                    WHEN LOWER(LTRIM(RTRIM(m.gender))) = 'female'
                    THEN m.id
                END) AS female_members,

                COUNT(DISTINCT CASE
                    WHEN m.gender IS NULL
                      OR LOWER(LTRIM(RTRIM(m.gender))) NOT IN ('male', 'female')
                    THEN m.id
                END) AS gender_not_specified,

                COUNT(DISTINCT CASE
                    WHEN m.national_id IS NULL
                      OR LTRIM(RTRIM(m.national_id)) = ''
                    THEN m.id
                END) AS missing_national_id,

                COUNT(DISTINCT CASE
                    WHEN m.date_of_birth IS NULL
                    THEN m.id
                END) AS missing_dob
            ")
            ->groupBy([
                'e.id',
                'e.employer_number',
                'e.penad_employer_number',
                'e.fundworx_employer_number',
                'e.name',
            ])
            ->orderBy('e.name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Age Profile
        |--------------------------------------------------------------------------
        */

        $today = now()->toDateString();

        $ageRow = (clone $base)
            ->selectRaw("
                COUNT(DISTINCT CASE
                    WHEN m.date_of_birth IS NOT NULL
                     AND DATEDIFF(YEAR, m.date_of_birth, ?) -
                        CASE
                            WHEN DATEADD(
                                YEAR,
                                DATEDIFF(YEAR, m.date_of_birth, ?),
                                m.date_of_birth
                            ) > ?
                            THEN 1 ELSE 0
                        END < 30
                    THEN m.id
                END) AS under_30,

                COUNT(DISTINCT CASE
                    WHEN m.date_of_birth IS NOT NULL
                     AND DATEDIFF(YEAR, m.date_of_birth, ?) -
                        CASE
                            WHEN DATEADD(
                                YEAR,
                                DATEDIFF(YEAR, m.date_of_birth, ?),
                                m.date_of_birth
                            ) > ?
                            THEN 1 ELSE 0
                        END BETWEEN 30 AND 39
                    THEN m.id
                END) AS age_30_39,

                COUNT(DISTINCT CASE
                    WHEN m.date_of_birth IS NOT NULL
                     AND DATEDIFF(YEAR, m.date_of_birth, ?) -
                        CASE
                            WHEN DATEADD(
                                YEAR,
                                DATEDIFF(YEAR, m.date_of_birth, ?),
                                m.date_of_birth
                            ) > ?
                            THEN 1 ELSE 0
                        END BETWEEN 40 AND 49
                    THEN m.id
                END) AS age_40_49,

                COUNT(DISTINCT CASE
                    WHEN m.date_of_birth IS NOT NULL
                     AND DATEDIFF(YEAR, m.date_of_birth, ?) -
                        CASE
                            WHEN DATEADD(
                                YEAR,
                                DATEDIFF(YEAR, m.date_of_birth, ?),
                                m.date_of_birth
                            ) > ?
                            THEN 1 ELSE 0
                        END BETWEEN 50 AND 54
                    THEN m.id
                END) AS age_50_54,

                COUNT(DISTINCT CASE
                    WHEN m.date_of_birth IS NOT NULL
                     AND DATEDIFF(YEAR, m.date_of_birth, ?) -
                        CASE
                            WHEN DATEADD(
                                YEAR,
                                DATEDIFF(YEAR, m.date_of_birth, ?),
                                m.date_of_birth
                            ) > ?
                            THEN 1 ELSE 0
                        END BETWEEN 55 AND 59
                    THEN m.id
                END) AS age_55_59,

                COUNT(DISTINCT CASE
                    WHEN m.date_of_birth IS NOT NULL
                     AND DATEDIFF(YEAR, m.date_of_birth, ?) -
                        CASE
                            WHEN DATEADD(
                                YEAR,
                                DATEDIFF(YEAR, m.date_of_birth, ?),
                                m.date_of_birth
                            ) > ?
                            THEN 1 ELSE 0
                        END >= 60
                    THEN m.id
                END) AS age_60_plus,

                COUNT(DISTINCT CASE
                    WHEN m.date_of_birth IS NULL
                    THEN m.id
                END) AS missing_dob
            ", [
                $today, $today, $today,
                $today, $today, $today,
                $today, $today, $today,
                $today, $today, $today,
                $today, $today, $today,
                $today, $today, $today,
            ])
            ->first();

        $ageProfile = [
            'under_30' => (int) ($ageRow->under_30 ?? 0),
            '30_39' => (int) ($ageRow->age_30_39 ?? 0),
            '40_49' => (int) ($ageRow->age_40_49 ?? 0),
            '50_54' => (int) ($ageRow->age_50_54 ?? 0),
            '55_59' => (int) ($ageRow->age_55_59 ?? 0),
            '60_plus' => (int) ($ageRow->age_60_plus ?? 0),
            'missing_dob' => (int) ($ageRow->missing_dob ?? 0),
        ];

        /*
        |--------------------------------------------------------------------------
        | Legacy Summary
        |--------------------------------------------------------------------------
        */

        $legacyRow = (clone $base)
            ->selectRaw("
                COUNT(DISTINCT m.id) AS total,

                COUNT(DISTINCT CASE
                    WHEN NULLIF(LTRIM(RTRIM(m.penad_member_number)), '') IS NOT NULL
                     AND NULLIF(LTRIM(RTRIM(m.fundworx_member_number)), '') IS NOT NULL
                    THEN m.id
                END) AS complete,

                COUNT(DISTINCT CASE
                    WHEN NULLIF(LTRIM(RTRIM(m.penad_member_number)), '') IS NULL
                     AND NULLIF(LTRIM(RTRIM(m.fundworx_member_number)), '') IS NOT NULL
                    THEN m.id
                END) AS missing_penad,

                COUNT(DISTINCT CASE
                    WHEN NULLIF(LTRIM(RTRIM(m.penad_member_number)), '') IS NOT NULL
                     AND NULLIF(LTRIM(RTRIM(m.fundworx_member_number)), '') IS NULL
                    THEN m.id
                END) AS missing_fundworx,

                COUNT(DISTINCT CASE
                    WHEN NULLIF(LTRIM(RTRIM(m.penad_member_number)), '') IS NULL
                     AND NULLIF(LTRIM(RTRIM(m.fundworx_member_number)), '') IS NULL
                    THEN m.id
                END) AS missing_both
            ")
            ->first();

        $duplicatePenadNumbers = DB::query()
            ->fromSub(
                (clone $base)
                    ->whereNotNull('m.penad_member_number')
                    ->whereRaw("LTRIM(RTRIM(m.penad_member_number)) <> ''")
                    ->selectRaw("
                        UPPER(LTRIM(RTRIM(m.penad_member_number))) AS reference_number,
                        COUNT(DISTINCT m.id) AS total
                    ")
                    ->groupByRaw("UPPER(LTRIM(RTRIM(m.penad_member_number)))"),
                'x'
            )
            ->where('total', '>', 1)
            ->count();

        $duplicateFundworxNumbers = DB::query()
            ->fromSub(
                (clone $base)
                    ->whereNotNull('m.fundworx_member_number')
                    ->whereRaw("LTRIM(RTRIM(m.fundworx_member_number)) <> ''")
                    ->selectRaw("
                        UPPER(LTRIM(RTRIM(m.fundworx_member_number))) AS reference_number,
                        COUNT(DISTINCT m.id) AS total
                    ")
                    ->groupByRaw("UPPER(LTRIM(RTRIM(m.fundworx_member_number)))"),
                'x'
            )
            ->where('total', '>', 1)
            ->count();

        $legacySummary = [
            'total' => (int) ($legacyRow->total ?? 0),
            'complete' => (int) ($legacyRow->complete ?? 0),
            'missing_penad' => (int) ($legacyRow->missing_penad ?? 0),
            'missing_fundworx' => (int) ($legacyRow->missing_fundworx ?? 0),
            'missing_both' => (int) ($legacyRow->missing_both ?? 0),
            'duplicate_penad_numbers' => (int) $duplicatePenadNumbers,
            'duplicate_fundworx_numbers' => (int) $duplicateFundworxNumbers,
        ];

        /*
        |--------------------------------------------------------------------------
        | Data Quality Count
        |--------------------------------------------------------------------------
        */

        $dataQualityCount = (clone $base)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('m.national_id')
                    ->orWhereRaw("LTRIM(RTRIM(m.national_id)) = ''")
                    ->orWhereNull('m.date_of_birth')
                    ->orWhereNull('e.id')
                    ->orWhereNull('m.penad_member_number')
                    ->orWhereRaw("LTRIM(RTRIM(m.penad_member_number)) = ''")
                    ->orWhereNull('m.fundworx_member_number')
                    ->orWhereRaw("LTRIM(RTRIM(m.fundworx_member_number)) = ''");
            })
            ->distinct()
            ->count('m.id');

        return view(
            'pensions-administration.updates.reports.membership.index',
            compact(
                'employers',
                'summary',
                'statusSummary',
                'genderSummary',
                'employerSummary',
                'ageProfile',
                'legacySummary',
                'dataQualityCount'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Member Register
    |--------------------------------------------------------------------------
    */

    public function membersData(Request $request): JsonResponse
    {
        $query = $this->filteredMemberQuery($request)
            ->select([
                'm.id',
                'm.member_number',
                'm.penad_member_number',
                'm.fundworx_member_number',
                'm.surname',
                'm.first_names',
                'm.other_names',
                'm.maiden_name',
                'm.national_id',
                'm.date_of_birth',
                'm.gender',
                'm.date_joined_fund',
                'm.membership_status',
                'e.name AS employer_name',
                'me.staff_number',
                'me.vote_number',
            ]);

        return $this->dataTableResponse(
            request: $request,
            query: $query,
            searchableColumns: [
                'm.member_number',
                'm.penad_member_number',
                'm.fundworx_member_number',
                'm.surname',
                'm.first_names',
                'm.other_names',
                'm.maiden_name',
                'm.national_id',
                'e.name',
                'me.staff_number',
                'me.vote_number',
            ],
            orderColumns: [
                'm.member_number',
                'm.penad_member_number',
                'm.fundworx_member_number',
                'm.surname',
                'm.national_id',
                'm.date_of_birth',
                'm.gender',
                'e.name',
                'me.staff_number',
                'me.vote_number',
                'm.date_joined_fund',
                'm.membership_status',
            ],
            transformer: function ($row): array {
                $memberName =
                    '<strong>'
                    . e(trim($row->surname . ', ' . $row->first_names))
                    . '</strong>';

                if ($row->other_names) {
                    $memberName .=
                        '<br><small class="text-muted">Other: '
                        . e($row->other_names)
                        . '</small>';
                }

                if ($row->maiden_name) {
                    $memberName .=
                        '<br><small class="text-muted">Maiden: '
                        . e($row->maiden_name)
                        . '</small>';
                }

                return [
                    'member_number' => e($row->member_number),
                    'penad_member_number' => e($row->penad_member_number ?: '-'),
                    'fundworx_member_number' => e($row->fundworx_member_number ?: '-'),
                    'member' => $memberName,
                    'national_id' => e($row->national_id ?: '-'),
                    'date_of_birth' => $row->date_of_birth
                        ? Carbon::parse($row->date_of_birth)->format('d M Y')
                        : '-',
                    'gender' => e($row->gender ?: '-'),
                    'employer' => e($row->employer_name ?: '-'),
                    'staff_number' => e($row->staff_number ?: '-'),
                    'vote_number' => e($row->vote_number ?: '-'),
                    'date_joined_fund' => $row->date_joined_fund
                        ? Carbon::parse($row->date_joined_fund)->format('d M Y')
                        : '-',
                    'status' => $this->statusBadge($row->membership_status),
                ];
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Age Profile
    |--------------------------------------------------------------------------
    */

    public function ageData(Request $request): JsonResponse
    {
        $today = now()->toDateString();

        $query = $this->filteredMemberQuery($request)
            ->whereNotNull('m.date_of_birth')
            ->select([
                'm.id',
                'm.member_number',
                'm.penad_member_number',
                'm.surname',
                'm.first_names',
                'm.national_id',
                'm.date_of_birth',
                'm.membership_status',
                'e.name AS employer_name',
            ])
            ->selectRaw("
                DATEDIFF(YEAR, m.date_of_birth, ?) -
                CASE
                    WHEN DATEADD(
                        YEAR,
                        DATEDIFF(YEAR, m.date_of_birth, ?),
                        m.date_of_birth
                    ) > ?
                    THEN 1
                    ELSE 0
                END AS current_age
            ", [
                $today,
                $today,
                $today,
            ]);

        return $this->dataTableResponse(
            request: $request,
            query: $query,
            searchableColumns: [
                'm.member_number',
                'm.penad_member_number',
                'm.surname',
                'm.first_names',
                'm.national_id',
                'e.name',
                'm.membership_status',
            ],
            orderColumns: [
                'm.member_number',
                'm.penad_member_number',
                'm.surname',
                'm.national_id',
                'm.date_of_birth',
                'current_age',
                'e.name',
                'm.membership_status',
            ],
            transformer: fn ($row) => [
                'member_number' => e($row->member_number),
                'penad_member_number' => e($row->penad_member_number ?: '-'),
                'member' => e($row->surname . ', ' . $row->first_names),
                'national_id' => e($row->national_id ?: '-'),
                'date_of_birth' => Carbon::parse($row->date_of_birth)->format('d M Y'),
                'age' => (int) $row->current_age,
                'employer' => e($row->employer_name ?: '-'),
                'status' => e(ucfirst($row->membership_status)),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Legacy Mapping
    |--------------------------------------------------------------------------
    */

    public function legacyData(Request $request): JsonResponse
    {
        $query = $this->filteredMemberQuery($request)
            ->select([
                'm.id',
                'm.member_number',
                'm.penad_member_number',
                'm.fundworx_member_number',
                'm.surname',
                'm.first_names',
                'm.national_id',
                'm.membership_status',
                'e.name AS employer_name',
            ]);

        return $this->dataTableResponse(
            request: $request,
            query: $query,
            searchableColumns: [
                'm.member_number',
                'm.penad_member_number',
                'm.fundworx_member_number',
                'm.surname',
                'm.first_names',
                'm.national_id',
                'e.name',
            ],
            orderColumns: [
                'm.member_number',
                'm.penad_member_number',
                'm.fundworx_member_number',
                'm.surname',
                'm.national_id',
                'e.name',
                'm.membership_status',
            ],
            transformer: function ($row): array {
                $penad = trim((string) $row->penad_member_number);
                $fundworx = trim((string) $row->fundworx_member_number);

                $mappingStatus =
                    $penad !== '' && $fundworx !== ''
                        ? 'Complete'
                        : (
                            $penad === '' && $fundworx === ''
                                ? 'Missing Both'
                                : (
                                    $penad === ''
                                        ? 'Missing PenAd'
                                        : 'Missing Fundworx'
                                )
                        );

                return [
                    'member_number' => e($row->member_number),
                    'penad_member_number' => e($penad ?: 'Missing'),
                    'fundworx_member_number' => e($fundworx ?: 'Missing'),
                    'member' => e($row->surname . ', ' . $row->first_names),
                    'national_id' => e($row->national_id ?: '-'),
                    'employer' => e($row->employer_name ?: '-'),
                    'status' => e(ucfirst($row->membership_status)),
                    'mapping_status' => e($mappingStatus),
                    'duplicate_check' => 'Checked in summary',
                ];
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Data Quality
    |--------------------------------------------------------------------------
    */

    public function dataQualityData(Request $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | Missing Employer gets its own optimised query.
        |
        */

        if ($request->input('data_quality') === 'missing_employer') {
            return $this->missingEmployerData($request);
        }

        $query = $this->filteredMemberQuery($request)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('m.national_id')
                    ->orWhereRaw("LTRIM(RTRIM(m.national_id)) = ''")
                    ->orWhereNull('m.date_of_birth')
                    ->orWhereNull('e.id')
                    ->orWhereNull('m.penad_member_number')
                    ->orWhereRaw("LTRIM(RTRIM(m.penad_member_number)) = ''")
                    ->orWhereNull('m.fundworx_member_number')
                    ->orWhereRaw("LTRIM(RTRIM(m.fundworx_member_number)) = ''");
            })
            ->select([
                'm.id',
                'm.member_number',
                'm.penad_member_number',
                'm.fundworx_member_number',
                'm.surname',
                'm.first_names',
                'm.national_id',
                'm.date_of_birth',
                'm.membership_status',
                'e.name AS employer_name',
            ]);

        return $this->dataTableResponse(
            request: $request,
            query: $query,
            searchableColumns: [
                'm.member_number',
                'm.penad_member_number',
                'm.fundworx_member_number',
                'm.surname',
                'm.first_names',
                'm.national_id',
                'e.name',
            ],
            orderColumns: [
                'm.member_number',
                'm.surname',
                'm.national_id',
                'm.date_of_birth',
                'm.penad_member_number',
                'm.fundworx_member_number',
                'e.name',
            ],
            transformer: function ($row): array {
                $exceptions = [];

                if (blank($row->national_id)) {
                    $exceptions[] =
                        '<span class="badge bg-danger exception-badge">National ID</span>';
                }

                if (!$row->date_of_birth) {
                    $exceptions[] =
                        '<span class="badge bg-warning text-dark exception-badge">DOB</span>';
                }

                if (!$row->employer_name) {
                    $exceptions[] =
                        '<span class="badge bg-danger exception-badge">Employer</span>';
                }

                if (blank($row->penad_member_number)) {
                    $exceptions[] =
                        '<span class="badge bg-warning text-dark exception-badge">PenAd</span>';
                }

                if (blank($row->fundworx_member_number)) {
                    $exceptions[] =
                        '<span class="badge bg-warning text-dark exception-badge">Fundworx</span>';
                }

                $actionLabel =
                    $row->employer_name
                        ? 'Edit Member'
                        : 'Assign Employer';

                $action =
                    '<a href="'
                    . e(
                        route(
                            'pensions-administration.updates.members.edit',
                            $row->id
                        )
                    )
                    . '" class="btn btn-sm btn-primary">'
                    . '<i class="mdi mdi-pencil-outline me-1"></i>'
                    . e($actionLabel)
                    . '</a>';

                return [
                    'member_number' => e($row->member_number),
                    'member' =>
                        '<strong>'
                        . e($row->surname . ', ' . $row->first_names)
                        . '</strong>',
                    'national_id' => e($row->national_id ?: '-'),
                    'date_of_birth' =>
                        $row->date_of_birth
                            ? Carbon::parse($row->date_of_birth)->format('d M Y')
                            : '-',
                    'penad_member_number' =>
                        e($row->penad_member_number ?: '-'),
                    'fundworx_member_number' =>
                        e($row->fundworx_member_number ?: '-'),
                    'employer' =>
                        $row->employer_name
                            ? e($row->employer_name)
                            : '<span class="text-danger fw-semibold">Not Assigned</span>',
                    'exceptions' => implode(' ', $exceptions),
                    'action' => $action,
                ];
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FAST Missing Employer Data
    |--------------------------------------------------------------------------
    */

    private function missingEmployerData(Request $request): JsonResponse
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

    $length = (int) $request->input(
        'length',
        25
    );

    if ($length < 1 || $length > 100) {
        $length = 25;
    }

    /*
    |--------------------------------------------------------------------------
    | Known Missing Employer Total
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | The main Membership Report has already calculated the Missing Employer
    | total. Therefore DataTables sends that number to this endpoint.
    |
    | We deliberately DO NOT run:
    |
    | COUNT(*)
    |
    | against member_employments again.
    |
    */

    $knownTotal = max(
        0,
        (int) $request->input(
            'missing_employer_total',
            0
        )
    );

    /*
    |--------------------------------------------------------------------------
    | Fast Missing Employer Query
    |--------------------------------------------------------------------------
    |
    | We only retrieve the requested page.
    |
    | No employers join.
    | No member_employments join.
    | No DISTINCT.
    | No GROUP BY.
    | No count query.
    |
    */

    $query = DB::table('members AS m')
        ->whereNull('m.deleted_at')
        ->whereNotExists(
            function ($subQuery): void {
                $subQuery
                    ->selectRaw('1')
                    ->from('member_employments AS me')
                    ->whereColumn(
                        'me.member_id',
                        'm.id'
                    )
                    ->where(
                        'me.is_current',
                        1
                    );
            }
        );

    /*
    |--------------------------------------------------------------------------
    | PENERP Member Number
    |--------------------------------------------------------------------------
    */

    if ($request->filled('penerp_member_number')) {
        $query->where(
            'm.member_number',
            'like',
            '%'
            . trim(
                (string) $request->input(
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

    if ($request->filled('penad_member_number')) {
        $query->where(
            'm.penad_member_number',
            'like',
            '%'
            . trim(
                (string) $request->input(
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

    if ($request->filled('fundworx_member_number')) {
        $query->where(
            'm.fundworx_member_number',
            'like',
            '%'
            . trim(
                (string) $request->input(
                    'fundworx_member_number'
                )
            )
            . '%'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    if ($request->filled('status')) {
        $query->where(
            'm.membership_status',
            $request->input('status')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Gender
    |--------------------------------------------------------------------------
    */

    if ($request->filled('gender')) {
        $query->where(
            'm.gender',
            $request->input('gender')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Joined From
    |--------------------------------------------------------------------------
    */

    if ($request->filled('joined_from')) {
        $query->where(
            'm.date_joined_fund',
            '>=',
            $request->input('joined_from')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Joined To
    |--------------------------------------------------------------------------
    */

    if ($request->filled('joined_to')) {
        $query->where(
            'm.date_joined_fund',
            '<=',
            $request->input('joined_to')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | General Membership Report Search
    |--------------------------------------------------------------------------
    */

    if ($request->filled('report_search')) {
        $reportSearch = trim(
            (string) $request->input(
                'report_search'
            )
        );

        $query->where(
            function (Builder $query) use ($reportSearch): void {
                $like =
                    '%'
                    . $reportSearch
                    . '%';

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
                        'm.national_id',
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
                        'm.maiden_name',
                        'like',
                        $like
                    );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DataTables Search
    |--------------------------------------------------------------------------
    */

    $dataTableSearch = trim(
        (string) $request->input(
            'search.value',
            ''
        )
    );

    $hasDataTableSearch =
        $dataTableSearch !== '';

    if ($hasDataTableSearch) {
        $query->where(
            function (Builder $query) use ($dataTableSearch): void {
                $like =
                    '%'
                    . $dataTableSearch
                    . '%';

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
                        'm.national_id',
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
                    );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Filtered Count
    |--------------------------------------------------------------------------
    |
    | Only run a COUNT if the user actually uses the DataTables search box.
    |
    | Normal opening/paging of Missing Employer DOES NOT count the table.
    |
    */

    if ($hasDataTableSearch) {
        $recordsFiltered =
            (clone $query)
                ->count('m.id');
    } else {
        $recordsFiltered =
            $knownTotal;
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve ONLY Current Page
    |--------------------------------------------------------------------------
    |
    | Example:
    |
    | Page 1 = rows 1-25
    | Page 2 = rows 26-50
    | Page 3 = rows 51-75
    |
    | The 1,756 members are NEVER loaded together.
    |
    */

    $members = $query
        ->select([
            'm.id',
            'm.member_number',
            'm.penad_member_number',
            'm.fundworx_member_number',
            'm.surname',
            'm.first_names',
            'm.other_names',
            'm.national_id',
            'm.date_of_birth',
            'm.membership_status',
        ])
        ->orderBy('m.id')
        ->offset($start)
        ->limit($length)
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Format 25 Rows Only
    |--------------------------------------------------------------------------
    */

    $data = $members
        ->map(
            function ($member): array {
                $memberName = trim(
                    implode(
                        ' ',
                        array_filter([
                            $member->first_names,
                            $member->other_names,
                            $member->surname,
                        ])
                    )
                );

                return [
                    'member_number' =>
                        e(
                            $member->member_number
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
                            $member->national_id
                            ?: '-'
                        ),

                    'date_of_birth' =>
                        $member->date_of_birth
                            ? Carbon::parse(
                                $member->date_of_birth
                            )->format('d M Y')
                            : '-',

                    'penad_member_number' =>
                        e(
                            $member->penad_member_number
                            ?: '-'
                        ),

                    'fundworx_member_number' =>
                        e(
                            $member->fundworx_member_number
                            ?: '-'
                        ),

                    'employer' =>
                        '<span class="text-danger fw-semibold">'
                        . 'Not Assigned'
                        . '</span>',

                    'exceptions' =>
                        '<span class="badge bg-danger">'
                        . 'Employer'
                        . '</span>',

                    'action' =>
                        '<a href="'
                        . e(
                            route(
                                'pensions-administration.updates.members.edit',
                                $member->id
                            )
                        )
                        . '" class="btn btn-sm btn-primary">'
                        . '<i class="mdi mdi-office-building-plus-outline me-1"></i>'
                        . 'Assign Employer'
                        . '</a>',
                ];
            }
        )
        ->values();

    /*
    |--------------------------------------------------------------------------
    | DataTables Response
    |--------------------------------------------------------------------------
    */

    return response()->json([
        'draw' =>
            $draw,

        'recordsTotal' =>
            $knownTotal,

        'recordsFiltered' =>
            $recordsFiltered,

        'data' =>
            $data,
    ]);
}

    /*
    |--------------------------------------------------------------------------
    | Base Query
    |--------------------------------------------------------------------------
    */

    private function filteredMemberQuery(Request $request): Builder
    {
        $query = DB::table('members AS m')
            ->leftJoin(
                'member_employments AS me',
                function ($join): void {
                    $join
                        ->on(
                            'me.member_id',
                            '=',
                            'm.id'
                        )
                        ->where(
                            'me.is_current',
                            true
                        );
                }
            )
            ->leftJoin(
                'employers AS e',
                'e.id',
                '=',
                'me.employer_id'
            )
            ->whereNull('m.deleted_at');

        if ($request->filled('search')) {
            $search =
                trim(
                    (string) $request->input('search')
                );

            $query->where(
                function (Builder $query) use ($search): void {
                    $like =
                        '%'
                        . $search
                        . '%';

                    $query
                        ->where('m.member_number', 'like', $like)
                        ->orWhere('m.penad_member_number', 'like', $like)
                        ->orWhere('m.fundworx_member_number', 'like', $like)
                        ->orWhere('m.national_id', 'like', $like)
                        ->orWhere('m.surname', 'like', $like)
                        ->orWhere('m.first_names', 'like', $like)
                        ->orWhere('m.other_names', 'like', $like)
                        ->orWhere('m.maiden_name', 'like', $like)
                        ->orWhere('me.staff_number', 'like', $like);
                }
            );
        }

        if ($request->filled('penerp_member_number')) {
            $query->where(
                'm.member_number',
                'like',
                '%'
                . trim(
                    (string) $request->input('penerp_member_number')
                )
                . '%'
            );
        }

        if ($request->filled('penad_member_number')) {
            $query->where(
                'm.penad_member_number',
                'like',
                '%'
                . trim(
                    (string) $request->input('penad_member_number')
                )
                . '%'
            );
        }

        if ($request->filled('fundworx_member_number')) {
            $query->where(
                'm.fundworx_member_number',
                'like',
                '%'
                . trim(
                    (string) $request->input('fundworx_member_number')
                )
                . '%'
            );
        }

        if ($request->filled('employer_id')) {
            $query->where(
                'me.employer_id',
                (int) $request->input('employer_id')
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'm.membership_status',
                $request->input('status')
            );
        }

        if ($request->filled('gender')) {
            $query->where(
                'm.gender',
                $request->input('gender')
            );
        }

        if ($request->filled('joined_from')) {
            $query->where(
                'm.date_joined_fund',
                '>=',
                $request->input('joined_from')
            );
        }

        if ($request->filled('joined_to')) {
            $query->where(
                'm.date_joined_fund',
                '<=',
                $request->input('joined_to')
            );
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Server-Side DataTables
    |--------------------------------------------------------------------------
    */

    private function dataTableResponse(
        Request $request,
        Builder $query,
        array $searchableColumns,
        array $orderColumns,
        callable $transformer
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

        $length =
            $length < 1
                ? 25
                : min(
                    $length,
                    100
                );

        /*
        |--------------------------------------------------------------------------
        | Total
        |--------------------------------------------------------------------------
        */

        $recordsTotal = DB::query()
            ->fromSub(
                (clone $query)
                    ->select('m.id')
                    ->distinct(),
                'report_count'
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | DataTables Search
        |--------------------------------------------------------------------------
        */

        $dataTableSearch =
            trim(
                (string) $request->input(
                    'search.value',
                    ''
                )
            );

        if ($dataTableSearch !== '') {
            $query->where(
                function (Builder $query) use (
                    $dataTableSearch,
                    $searchableColumns
                ): void {
                    $like =
                        '%'
                        . $dataTableSearch
                        . '%';

                    foreach (
                        $searchableColumns
                        as $index => $column
                    ) {
                        if ($index === 0) {
                            $query->where(
                                $column,
                                'like',
                                $like
                            );
                        } else {
                            $query->orWhere(
                                $column,
                                'like',
                                $like
                            );
                        }
                    }
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filtered Total
        |--------------------------------------------------------------------------
        */

        $recordsFiltered = DB::query()
            ->fromSub(
                (clone $query)
                    ->select('m.id')
                    ->distinct(),
                'report_count'
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Order
        |--------------------------------------------------------------------------
        */

        $orderIndex =
            (int) $request->input(
                'order.0.column',
                0
            );

        $orderDirection =
            strtolower(
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
            $orderColumns[0];

        $query->orderBy(
            $orderColumn,
            $orderDirection
        );

        /*
        |--------------------------------------------------------------------------
        | Page
        |--------------------------------------------------------------------------
        */

        $rows = $query
            ->offset($start)
            ->limit($length)
            ->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows
                ->map($transformer)
                ->values(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Status Badge
    |--------------------------------------------------------------------------
    */

    private function statusBadge(
        mixed $status
    ): string {
        $status =
            strtolower(
                trim(
                    (string) $status
                )
            );

        $class =
            match ($status) {
                'active' =>
                    'bg-success',

                'dormant' =>
                    'bg-warning text-dark',

                'suspended' =>
                    'bg-danger',

                default =>
                    'bg-secondary',
            };

        return
            '<span class="badge '
            . $class
            . '">'
            . e(
                ucfirst(
                    $status
                )
            )
            . '</span>';
    }
}
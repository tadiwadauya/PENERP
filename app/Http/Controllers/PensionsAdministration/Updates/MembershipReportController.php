<?php

namespace App\Http\Controllers\PensionsAdministration\Updates;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipReportController extends Controller
{
    public function index(Request $request): View
    {
        $employers = Employer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $query = Member::query()
            ->with('currentEmployment.employer');

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('member_number', 'like', "%{$search}%")
                    ->orWhere('penad_member_number', 'like', "%{$search}%")
                    ->orWhere('fundworx_member_number', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('first_names', 'like', "%{$search}%")
                    ->orWhere('other_names', 'like', "%{$search}%")
                    ->orWhere('maiden_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('penerp_member_number')) {
            $query->where(
                'member_number',
                'like',
                '%' . trim($request->penerp_member_number) . '%'
            );
        }

        if ($request->filled('penad_member_number')) {
            $query->where(
                'penad_member_number',
                'like',
                '%' . trim($request->penad_member_number) . '%'
            );
        }

        if ($request->filled('fundworx_member_number')) {
            $query->where(
                'fundworx_member_number',
                'like',
                '%' . trim($request->fundworx_member_number) . '%'
            );
        }

        if ($request->filled('employer_id')) {
            $employerId = (int) $request->employer_id;

            $query->whereHas(
                'currentEmployment',
                fn ($q) => $q->where('employer_id', $employerId)
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'membership_status',
                $request->status
            );
        }

        if ($request->filled('gender')) {
            $query->where(
                'gender',
                $request->gender
            );
        }

        if ($request->filled('joined_from')) {
            $query->whereDate(
                'date_joined_fund',
                '>=',
                $request->joined_from
            );
        }

        if ($request->filled('joined_to')) {
            $query->whereDate(
                'date_joined_fund',
                '<=',
                $request->joined_to
            );
        }

        $members = $query
            ->orderBy('surname')
            ->orderBy('first_names')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Main Statistics
        |--------------------------------------------------------------------------
        */

        $summary = [
            'total' => $members->count(),

            'active' => $members
                ->where('membership_status', 'active')
                ->count(),

            'dormant' => $members
                ->where('membership_status', 'dormant')
                ->count(),

            'inactive' => $members
                ->where('membership_status', 'inactive')
                ->count(),

            'suspended' => $members
                ->where('membership_status', 'suspended')
                ->count(),

            'without_national_id' => $members
                ->filter(
                    fn ($member) =>
                        blank($member->national_id)
                )
                ->count(),

            'without_dob' => $members
                ->filter(
                    fn ($member) =>
                        blank($member->date_of_birth)
                )
                ->count(),

            'without_employer' => $members
                ->filter(
                    fn ($member) =>
                        !$member->currentEmployment?->employer
                )
                ->count(),

            'without_penad_number' => $members
                ->filter(
                    fn ($member) =>
                        blank($member->penad_member_number)
                )
                ->count(),

            'without_fundworx_number' => $members
                ->filter(
                    fn ($member) =>
                        blank($member->fundworx_member_number)
                )
                ->count(),

            'without_email' => $members
                ->filter(
                    fn ($member) =>
                        blank($member->email)
                )
                ->count(),

            'without_cell_number' => $members
                ->filter(
                    fn ($member) =>
                        blank($member->cell_number)
                )
                ->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Status Summary
        |--------------------------------------------------------------------------
        */

        $statusSummary = $members
            ->groupBy(function ($member) {
                return strtolower(
                    trim(
                        (string) $member->membership_status
                    )
                ) ?: 'not specified';
            })
            ->map(function ($group, $status) {
                return (object) [
                    'status' => $status,
                    'total' => $group->count(),
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Gender Summary
        |--------------------------------------------------------------------------
        */

        $genderSummary = $members
            ->groupBy(function ($member) {
                $gender = strtolower(
                    trim(
                        (string) $member->gender
                    )
                );

                return match ($gender) {
                    'male', 'm' => 'Male',
                    'female', 'f' => 'Female',
                    default => 'Not Specified',
                };
            })
            ->map(function ($group, $gender) {
                return (object) [
                    'gender' => $gender,
                    'total' => $group->count(),
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Employer Summary
        |--------------------------------------------------------------------------
        */

        $employerSummary = $members
            ->filter(
                fn ($member) =>
                    $member->currentEmployment?->employer
            )
            ->groupBy(
                fn ($member) =>
                    $member->currentEmployment->employer_id
            )
            ->map(function ($group) {
                $employer = $group
                    ->first()
                    ->currentEmployment
                    ->employer;

                return (object) [
                    'employer_number' =>
                        $employer->employer_number,

                    'penad_employer_number' =>
                        $employer->penad_employer_number,

                    'fundworx_employer_number' =>
                        $employer->fundworx_employer_number,

                    'name' =>
                        $employer->name,

                    'total_members' =>
                        $group->count(),

                    'active_members' =>
                        $group
                            ->where(
                                'membership_status',
                                'active'
                            )
                            ->count(),

                    'dormant_members' =>
                        $group
                            ->where(
                                'membership_status',
                                'dormant'
                            )
                            ->count(),

                    'inactive_members' =>
                        $group
                            ->where(
                                'membership_status',
                                'inactive'
                            )
                            ->count(),

                    'suspended_members' =>
                        $group
                            ->where(
                                'membership_status',
                                'suspended'
                            )
                            ->count(),
                ];
            })
            ->sortBy('name')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Age Profile
        |--------------------------------------------------------------------------
        */

        $ageProfile = [
            'under_30' => 0,
            '30_39' => 0,
            '40_49' => 0,
            '50_54' => 0,
            '55_59' => 0,
            '60_plus' => 0,
            'missing_dob' => 0,
        ];

        $ageMembers = collect();

        foreach ($members as $member) {

            if (!$member->date_of_birth) {
                $ageProfile['missing_dob']++;
                continue;
            }

            try {
                $age = Carbon::parse(
                    $member->date_of_birth
                )->age;

                $member->current_age = $age;

                $ageMembers->push($member);

                if ($age < 30) {
                    $ageProfile['under_30']++;

                } elseif ($age <= 39) {
                    $ageProfile['30_39']++;

                } elseif ($age <= 49) {
                    $ageProfile['40_49']++;

                } elseif ($age <= 54) {
                    $ageProfile['50_54']++;

                } elseif ($age <= 59) {
                    $ageProfile['55_59']++;

                } else {
                    $ageProfile['60_plus']++;
                }

            } catch (\Throwable $e) {
                $ageProfile['missing_dob']++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Legacy Mapping
        |--------------------------------------------------------------------------
        */

        $legacyMapping = $members
            ->map(function ($member) {

                $penad = trim(
                    (string)
                    $member->penad_member_number
                );

                $fundworx = trim(
                    (string)
                    $member->fundworx_member_number
                );

                $mappingStatus = match (true) {
                    $penad !== '' && $fundworx !== '' =>
                        'complete',

                    $penad === '' && $fundworx === '' =>
                        'missing_both',

                    $penad === '' =>
                        'missing_penad',

                    default =>
                        'missing_fundworx',
                };

                return (object) [
                    'id' =>
                        $member->id,

                    'member_number' =>
                        $member->member_number,

                    'penad_member_number' =>
                        $member->penad_member_number,

                    'fundworx_member_number' =>
                        $member->fundworx_member_number,

                    'surname' =>
                        $member->surname,

                    'first_names' =>
                        $member->first_names,

                    'national_id' =>
                        $member->national_id,

                    'membership_status' =>
                        $member->membership_status,

                    'employer_name' =>
                        $member
                            ->currentEmployment
                            ?->employer
                            ?->name,

                    'mapping_status' =>
                        $mappingStatus,

                    'duplicate_penad' =>
                        false,

                    'duplicate_fundworx' =>
                        false,
                ];
            });

        $duplicatePenadNumbers = $legacyMapping
            ->filter(
                fn ($member) =>
                    filled(
                        $member->penad_member_number
                    )
            )
            ->groupBy(
                fn ($member) =>
                    strtoupper(
                        trim(
                            $member->penad_member_number
                        )
                    )
            )
            ->filter(
                fn ($group) =>
                    $group->count() > 1
            );

        $duplicateFundworxNumbers = $legacyMapping
            ->filter(
                fn ($member) =>
                    filled(
                        $member->fundworx_member_number
                    )
            )
            ->groupBy(
                fn ($member) =>
                    strtoupper(
                        trim(
                            $member->fundworx_member_number
                        )
                    )
            )
            ->filter(
                fn ($group) =>
                    $group->count() > 1
            );

        $duplicatePenadIds =
            $duplicatePenadNumbers
                ->flatten(1)
                ->pluck('id');

        $duplicateFundworxIds =
            $duplicateFundworxNumbers
                ->flatten(1)
                ->pluck('id');

        $legacyMapping->each(
            function ($member) use (
                $duplicatePenadIds,
                $duplicateFundworxIds
            ) {
                $member->duplicate_penad =
                    $duplicatePenadIds
                        ->contains($member->id);

                $member->duplicate_fundworx =
                    $duplicateFundworxIds
                        ->contains($member->id);
            }
        );

        $legacySummary = [
            'total' =>
                $legacyMapping->count(),

            'complete' =>
                $legacyMapping
                    ->where(
                        'mapping_status',
                        'complete'
                    )
                    ->count(),

            'missing_penad' =>
                $legacyMapping
                    ->where(
                        'mapping_status',
                        'missing_penad'
                    )
                    ->count(),

            'missing_fundworx' =>
                $legacyMapping
                    ->where(
                        'mapping_status',
                        'missing_fundworx'
                    )
                    ->count(),

            'missing_both' =>
                $legacyMapping
                    ->where(
                        'mapping_status',
                        'missing_both'
                    )
                    ->count(),

            'duplicate_penad_numbers' =>
                $duplicatePenadNumbers->count(),

            'duplicate_fundworx_numbers' =>
                $duplicateFundworxNumbers->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Data Quality
        |--------------------------------------------------------------------------
        */

        $dataQualityMembers = $members
            ->filter(function ($member) {

                return
                    blank($member->national_id)
                    || blank($member->date_of_birth)
                    || !$member->currentEmployment?->employer
                    || blank($member->penad_member_number)
                    || blank($member->fundworx_member_number);
            });

        return view(
            'pensions-administration.updates.reports.membership.index',
            compact(
                'employers',
                'members',
                'summary',
                'statusSummary',
                'genderSummary',
                'employerSummary',
                'ageProfile',
                'ageMembers',
                'legacyMapping',
                'legacySummary',
                'duplicatePenadNumbers',
                'duplicateFundworxNumbers',
                'dataQualityMembers'
            )
        );
    }
}
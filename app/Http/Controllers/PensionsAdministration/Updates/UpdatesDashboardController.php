<?php

namespace App\Http\Controllers\PensionsAdministration\Updates;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\EmployerGroup;
use App\Models\PensionsAdministration\Updates\Member;
use App\Models\PensionsAdministration\Updates\MemberEmployment;
use App\Models\PensionsAdministration\Updates\MemberStatusHistory;
use Illuminate\View\View;

class UpdatesDashboardController extends Controller
{
    /**
     * Display the Updates / Membership dashboard.
     */
    public function index(): View
    {
        /*
        |--------------------------------------------------------------------------
        | Main Statistics
        |--------------------------------------------------------------------------
        */

        $statistics = [
            'total_members' =>
                Member::query()
                    ->count(),

            'active_members' =>
                Member::query()
                    ->where(
                        'membership_status',
                        'active'
                    )
                    ->count(),

            'dormant_members' =>
                Member::query()
                    ->where(
                        'membership_status',
                        'dormant'
                    )
                    ->count(),

            'inactive_members' =>
                Member::query()
                    ->where(
                        'membership_status',
                        'inactive'
                    )
                    ->count(),

            'suspended_members' =>
                Member::query()
                    ->where(
                        'membership_status',
                        'suspended'
                    )
                    ->count(),

            'active_employers' =>
                Employer::query()
                    ->where(
                        'is_active',
                        true
                    )
                    ->count(),

            'employer_groups' =>
                EmployerGroup::query()
                    ->where(
                        'is_active',
                        true
                    )
                    ->count(),

            'current_employments' =>
                MemberEmployment::query()
                    ->where(
                        'is_current',
                        true
                    )
                    ->count(),
        ];


        /*
        |--------------------------------------------------------------------------
        | Current Month Movements
        |--------------------------------------------------------------------------
        */

        $monthlyMovements = [
            'new_members' =>
                MemberStatusHistory::query()
                    ->where(
                        'movement_type',
                        'NEW_MEMBER'
                    )
                    ->whereYear(
                        'effective_date',
                        now()->year
                    )
                    ->whereMonth(
                        'effective_date',
                        now()->month
                    )
                    ->count(),

            'reinstatements' =>
                MemberStatusHistory::query()
                    ->where(
                        'movement_type',
                        'REINSTATEMENT'
                    )
                    ->whereYear(
                        'effective_date',
                        now()->year
                    )
                    ->whereMonth(
                        'effective_date',
                        now()->month
                    )
                    ->count(),

            'suspensions' =>
                MemberStatusHistory::query()
                    ->where(
                        'movement_type',
                        'SUSPENSION'
                    )
                    ->whereYear(
                        'effective_date',
                        now()->year
                    )
                    ->whereMonth(
                        'effective_date',
                        now()->month
                    )
                    ->count(),

            'other_movements' =>
                MemberStatusHistory::query()
                    ->whereYear(
                        'effective_date',
                        now()->year
                    )
                    ->whereMonth(
                        'effective_date',
                        now()->month
                    )
                    ->whereNotIn(
                        'movement_type',
                        [
                            'NEW_MEMBER',
                            'REINSTATEMENT',
                            'SUSPENSION',
                        ]
                    )
                    ->count(),
        ];


        /*
        |--------------------------------------------------------------------------
        | Recent Members
        |--------------------------------------------------------------------------
        */

        $recentMembers =
            Member::query()
                ->with([
                    'currentEmployment.employer',
                ])
                ->orderByDesc('created_at')
                ->limit(8)
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Recent Movements
        |--------------------------------------------------------------------------
        */

        $recentMovements =
            MemberStatusHistory::query()
                ->with('member')
                ->orderByDesc(
                    'effective_date'
                )
                ->orderByDesc('id')
                ->limit(8)
                ->get();


        return view(
            'pensions-administration.updates.dashboard.index',
            compact(
                'statistics',
                'monthlyMovements',
                'recentMembers',
                'recentMovements'
            )
        );
    }
}
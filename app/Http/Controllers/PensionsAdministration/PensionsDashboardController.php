<?php

namespace App\Http\Controllers\PensionsAdministration;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\Member;
use App\Models\PensionsAdministration\Updates\MemberStatusHistory;
use Illuminate\View\View;

class PensionsDashboardController extends Controller
{
    /**
     * Display the main Pensions Administration dashboard.
     */
    public function index(): View
    {
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

            'employers' =>
                Employer::query()
                    ->where(
                        'is_active',
                        true
                    )
                    ->count(),

            'movements_this_month' =>
                MemberStatusHistory::query()
                    ->whereYear(
                        'effective_date',
                        now()->year
                    )
                    ->whereMonth(
                        'effective_date',
                        now()->month
                    )
                    ->count(),
        ];

        return view(
            'pensions-administration.dashboard.index',
            compact(
                'statistics'
            )
        );
    }
}
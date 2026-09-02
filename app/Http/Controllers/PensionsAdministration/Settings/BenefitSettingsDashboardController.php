<?php

namespace App\Http\Controllers\PensionsAdministration\Settings;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Contributions\ExchangeRate;
use App\Models\PensionsAdministration\Settings\AccumulatedInterestFactor;
use App\Models\PensionsAdministration\Settings\BenefitSetting;
use App\Models\PensionsAdministration\Settings\BenefitTaxTable;
use App\Models\PensionsAdministration\Settings\CommutationFactor;
use App\Models\PensionsAdministration\Settings\RetirementAgeIncreaseFactor;
use App\Models\PensionsAdministration\Settings\WithdrawalEmployerEntitlementScale;
use Illuminate\View\View;

class BenefitSettingsDashboardController extends Controller
{
    public function index(): View
    {
        $this->ensurePermission(
            'pensions.settings.view'
        );

        $counts = [

            'general_settings' =>
                BenefitSetting::query()
                    ->count(),

            'withdrawal_scales' =>
                WithdrawalEmployerEntitlementScale::query()
                    ->count(),

            'accumulated_interest_factors' =>
                AccumulatedInterestFactor::query()
                    ->count(),

            'commutation_factors' =>
                CommutationFactor::query()
                    ->count(),

            'retirement_increase_factors' =>
                RetirementAgeIncreaseFactor::query()
                    ->count(),

            'tax_tables' =>
                BenefitTaxTable::query()
                    ->count(),

            'exchange_rates' =>
                ExchangeRate::query()
                    ->count(),
        ];

        return view(
            'pensions-administration.settings.index',
            compact(
                'counts'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Access Control
    |--------------------------------------------------------------------------
    |
    | At this stage Pension Benefit Settings are restricted exclusively to
    | System Administrator users.
    |
    */

    private function ensurePermission(
        string $permission
    ): void {
        $user =
            auth()->user();

        abort_if(
            !$user,
            401,
            'Unauthenticated.'
        );

        abort_unless(
            $user->hasRole(
                'system-administrator'
            )
            &&
            $user->can(
                $permission
            ),
            403,
            'Only System Administrators may access pension benefit settings.'
        );
    }
}
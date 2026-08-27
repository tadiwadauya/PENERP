<?php

namespace App\Http\Controllers\PensionsAdministration\Contributions;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Contributions\ContributionReceipt;
use App\Models\PensionsAdministration\Contributions\ExchangeRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExchangeRateController extends Controller
{
    public function index(): View
    {
        $this->ensurePermission(
            'contributions.exchange-rates.view'
        );


        $rates =
            ExchangeRate::query()
                ->orderByDesc(
                    'rate_date'
                )
                ->paginate(50);


        return view(
            'pensions-administration.contributions.receipts.exchange-rates.index',
            compact(
                'rates'
            )
        );
    }


    public function store(
        Request $request
    ): RedirectResponse {

        $this->ensurePermission(
            'contributions.exchange-rates.manage'
        );


        $validated =
            $request->validate([
                'rate_date' => [
                    'required',
                    'date',
                ],

                'rate' => [
                    'required',
                    'numeric',
                    'gt:0',
                ],

                'source' => [
                    'nullable',
                    'string',
                    'max:150',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ]);


        $existing =
            ExchangeRate::query()
                ->whereDate(
                    'rate_date',
                    $validated['rate_date']
                )
                ->where(
                    'from_currency',
                    'USD'
                )
                ->where(
                    'to_currency',
                    'ZWG'
                )
                ->first();


        /*
        |--------------------------------------------------------------------------
        | Do not change a historical rate already used
        |--------------------------------------------------------------------------
        */

        if ($existing) {

            $used =
                ContributionReceipt::query()
                    ->where(
                        'exchange_rate_id',
                        $existing->id
                    )
                    ->exists();


            if ($used) {

                return back()->with(
                    'error',
                    'This rate has already been used by posted receipts and cannot be changed.'
                );
            }
        }


        ExchangeRate::updateOrCreate(
            [
                'rate_date' =>
                    $validated['rate_date'],

                'from_currency' =>
                    'USD',

                'to_currency' =>
                    'ZWG',
            ],
            [
                'rate' =>
                    $validated['rate'],

                'source' =>
                    $validated['source']
                    ?? null,

                'notes' =>
                    $validated['notes']
                    ?? null,

                'created_by' =>
                    $existing?->created_by
                    ?? auth()->id(),

                'updated_by' =>
                    auth()->id(),
            ]
        );


        return back()->with(
            'success',
            'USD to ZWG exchange rate saved successfully.'
        );
    }


    private function ensurePermission(
        string $permission
    ): void {

        abort_if(
            !auth()->check(),
            401
        );


        $user =
            auth()->user();


        if (
            $user->is_system_administrator
        ) {

            return;
        }


        abort_unless(
            $user->can(
                $permission
            ),
            403
        );
    }
}
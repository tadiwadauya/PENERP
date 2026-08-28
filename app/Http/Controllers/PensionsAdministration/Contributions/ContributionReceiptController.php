<?php

namespace App\Http\Controllers\PensionsAdministration\Contributions;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Contributions\ContributionReceipt;
use App\Models\PensionsAdministration\Updates\Employer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContributionReceiptController extends Controller
{
    public function index(
        Request $request
    ): View {

        $this->ensurePermission(
            'contributions.receipts.view'
        );


        $query =
            ContributionReceipt::query()
                ->with(
                    'employer'
                )
                ->orderByDesc(
                    'receipt_date'
                )
                ->orderByDesc(
                    'id'
                );


        if (
            $request->filled(
                'employer_id'
            )
        ) {

            $query->where(
                'employer_id',
                $request->input(
                    'employer_id'
                )
            );
        }


        if (
            $request->filled(
                'currency'
            )
        ) {

            $query->where(
                'currency',
                $request->input(
                    'currency'
                )
            );
        }


        if (
            $request->filled(
                'period'
            )
        ) {

            $query->whereDate(
                'contribution_period',
                $request->input(
                    'period'
                )
                . '-01'
            );
        }


        if (
            $request->filled(
                'receipt_from'
            )
        ) {

            $query->whereDate(
                'receipt_date',
                '>=',
                $request->input(
                    'receipt_from'
                )
            );
        }


        if (
            $request->filled(
                'receipt_to'
            )
        ) {

            $query->whereDate(
                'receipt_date',
                '<=',
                $request->input(
                    'receipt_to'
                )
            );
        }


        $receipts =
            $query
                ->paginate(50)
                ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $summary = [
            'receipt_count' =>
                ContributionReceipt::query()
                    ->count(),

            'total_zwg' =>
                ContributionReceipt::query()
                    ->sum(
                        'amount_zwg'
                    ),

            'original_usd' =>
                ContributionReceipt::query()
                    ->where(
                        'currency',
                        'USD'
                    )
                    ->sum(
                        'original_amount'
                    ),
        ];


        $employers =
            Employer::query()
                ->orderBy(
                    'name'
                )
                ->get([
                    'id',
                    'employer_number',
                    'name',
                ]);


        return view(
            'pensions-administration.contributions.receipts.index',
            compact(
                'receipts',
                'summary',
                'employers'
            )
        );
    }


    public function show(
        ContributionReceipt $receipt
    ): View {

        $this->ensurePermission(
            'contributions.receipts.view'
        );


        $receipt->load([
            'employer',
            'exchangeRate',
            'sourceBatch',
            'sourceRow',
        ]);


        return view(
            'pensions-administration.contributions.receipts.show',
            compact(
                'receipt'
            )
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
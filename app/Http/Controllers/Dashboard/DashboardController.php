<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (
            $user->can('dashboard.system-administration.view')
        ) {
            return redirect()
                ->route('dashboard.system-administration');
        }

        if ($user->can('dashboard.principal-office.view')) {
            return redirect()
                ->route('dashboard.principal-office');
        }

        if ($user->can('dashboard.pensions.view')) {
            return redirect()
                ->route('dashboard.pensions');
        }

        if ($user->can('dashboard.finance.view')) {
            return redirect()
                ->route('dashboard.finance');
        }

        if ($user->can('dashboard.property.view')) {
            return redirect()
                ->route('dashboard.property');
        }

        abort(403, 'You do not have access to any dashboard.');
    }

    public function finance(): View
    {
        abort_unless(
            auth()->user()->can('dashboard.finance.view'),
            403
        );

        return view('dashboard.finance');
    }

    public function pensions(): View
    {
        abort_unless(
            auth()->user()->can('dashboard.pensions.view'),
            403
        );

        return view('dashboard.pensions');
    }

    public function property(): View
    {
        abort_unless(
            auth()->user()->can('dashboard.property.view'),
            403
        );

        return view('dashboard.property');
    }

    public function principalOffice(): View
    {
        abort_unless(
            auth()->user()->can(
                'dashboard.principal-office.view'
            ),
            403
        );

        return view('dashboard.principal-office');
    }

    public function systemAdministration(): View
    {
        abort_unless(
            auth()->user()->can(
                'dashboard.system-administration.view'
            ),
            403
        );

        return view(
            'dashboard.system-administration'
        );
    }
}
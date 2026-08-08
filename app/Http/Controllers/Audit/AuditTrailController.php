<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\Audit\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditTrailController extends Controller
{
    /**
     * Display audit trail records.
     */
    public function index(
        Request $request
    ): View {
        $this->ensurePermission(
            'audit.audit-trails.view'
        );


        $query = AuditTrail::query()
            ->with([
                'user',
            ])
            ->orderByDesc('created_at');


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled('search')
        ) {
            $search =
                trim(
                    $request->input('search')
                );


            $query->where(
                function ($q) use ($search): void {

                    $q->where(
                        'action',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'module',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'entity_type',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'ip_address',
                        'like',
                        '%' . $search . '%'
                    );

                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Module Filter
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled('module')
        ) {
            $query->where(
                'module',
                $request->input('module')
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Action Filter
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled('action')
        ) {
            $query->where(
                'action',
                $request->input('action')
            );
        }


        /*
        |--------------------------------------------------------------------------
        | User Filter
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled('user_id')
        ) {
            $query->where(
                'user_id',
                $request->input('user_id')
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Date From
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled('date_from')
        ) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->input('date_from')
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Date To
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled('date_to')
        ) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->input('date_to')
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $auditTrails =
            $query
                ->paginate(50)
                ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Filter Values
        |--------------------------------------------------------------------------
        */

        $modules = AuditTrail::query()
            ->whereNotNull('module')
            ->where('module', '<>', '')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');


        $actions = AuditTrail::query()
            ->whereNotNull('action')
            ->where('action', '<>', '')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');


        return view(
            'audit.audit-trails.index',
            compact(
                'auditTrails',
                'modules',
                'actions'
            )
        );
    }


    /**
     * Enforce access permission.
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


        if (
            $user->is_system_administrator
        ) {
            return;
        }


        abort_unless(
            $user->can(
                $permission
            ),
            403,
            'You do not have permission to perform this action.'
        );
    }
}
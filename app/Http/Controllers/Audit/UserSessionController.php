<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\Audit\UserSession;
use App\Models\UserManagement\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserSessionController extends Controller
{
    /**
     * Display user session records.
     */
    public function index(
        Request $request
    ): View {
        $this->ensurePermission(
            'audit.user-sessions.view'
        );


        $query = UserSession::query()
            ->with([
                'user',
            ])
            ->orderByDesc(
                'login_at'
            );


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'search'
            )
        ) {
            $search =
                trim(
                    $request->input(
                        'search'
                    )
                );


            $query->where(
                function ($q) use (
                    $search
                ): void {

                    $q->where(
                        'session_uuid',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'laravel_session_id',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'ip_address',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'device_name',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'user_agent',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'logout_reason',
                        'like',
                        '%' . $search . '%'
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | User
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'user_id'
            )
        ) {
            $query->where(
                'user_id',
                $request->input(
                    'user_id'
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'status'
            )
        ) {
            if (
                $request->input(
                    'status'
                )
                === 'active'
            ) {
                $query->where(
                    'is_active',
                    true
                );
            }


            if (
                $request->input(
                    'status'
                )
                === 'closed'
            ) {
                $query->where(
                    'is_active',
                    false
                );
            }


            if (
                $request->input(
                    'status'
                )
                === 'terminated'
            ) {
                $query->where(
                    'was_forcibly_terminated',
                    true
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Date From
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'date_from'
            )
        ) {
            $query->whereDate(
                'login_at',
                '>=',
                $request->input(
                    'date_from'
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Date To
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'date_to'
            )
        ) {
            $query->whereDate(
                'login_at',
                '<=',
                $request->input(
                    'date_to'
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Results
        |--------------------------------------------------------------------------
        */

        $userSessions =
            $query
                ->paginate(50)
                ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | User Filter
        |--------------------------------------------------------------------------
        */

        $users = User::query()
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get([
                'id',
                'employee_number',
                'first_name',
                'surname',
                'username',
            ]);


        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $summary = [
            'total' =>
                UserSession::query()
                    ->count(),

            'active' =>
                UserSession::query()
                    ->where(
                        'is_active',
                        true
                    )
                    ->count(),

            'closed' =>
                UserSession::query()
                    ->where(
                        'is_active',
                        false
                    )
                    ->count(),

            'terminated' =>
                UserSession::query()
                    ->where(
                        'was_forcibly_terminated',
                        true
                    )
                    ->count(),

            'today' =>
                UserSession::query()
                    ->whereDate(
                        'login_at',
                        today()
                    )
                    ->count(),
        ];


        return view(
            'audit.user-sessions.index',
            compact(
                'userSessions',
                'users',
                'summary'
            )
        );
    }


    /**
     * Permission enforcement.
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
<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditService;
use App\Services\UserManagement\PasswordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function __construct(
        private readonly PasswordService $passwordService,
        private readonly AuditService $auditService
    ) {
    }

    public function editRequired(
        Request $request
    ): View {
        return view(
            'authentication.change-temporary-password',
            [
                'user' => $request->user(),
                'policy' => $this->passwordService
                    ->getActivePolicy(),
            ]
        );
    }

    public function updateRequired(
        Request $request
    ): RedirectResponse {
        $request->validate([
            'current_password' => [
                'required',
                'string',
            ],

            'password' => [
                'required',
                'string',
                'confirmed',
            ],
        ]);

        $user = $request->user();

        if (
            !Hash::check(
                $request->current_password,
                $user->password
            )
        ) {
            return back()->withErrors([
                'current_password' =>
                    'The current password is incorrect.',
            ]);
        }

        $this->passwordService
            ->changePassword(
                user: $user,
                newPassword: $request->password,
                changedBy: $user,
                reason: $user->temporary_password
                    ? 'first_login_change'
                    : 'expired_password_change'
            );

        $this->auditService->record(
            eventType: 'password_change',
            module: 'authentication',
            action: 'change-password',
            description:
                'User changed their password.',
            subject: $user
        );

        return redirect()
            ->route('dashboard')
            ->with(
                'success',
                'Password changed successfully.'
            );
    }
}
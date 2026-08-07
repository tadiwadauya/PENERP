<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Models\Audit\LoginAttempt;
use App\Models\UserManagement\PasswordPolicy;
use App\Models\UserManagement\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticationController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }

    public function create(): View
    {
        return view('authentication.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = User::query()
            ->where('username', $credentials['login'])
            ->orWhere('email', $credentials['login'])
            ->orWhere('employee_number', $credentials['login'])
            ->first();

        if (!$user) {
            $this->recordAttempt(
                request: $request,
                login: $credentials['login'],
                successful: false,
                failureReason: 'User not found'
            );

            return back()->withErrors([
                'login' => 'The supplied login details are invalid.',
            ])->onlyInput('login');
        }

        if (
            $user->account_status === 'locked'
            && $user->lock_expires_at
            && now()->lessThan($user->lock_expires_at)
        ) {
            $this->recordAttempt(
                request: $request,
                login: $credentials['login'],
                successful: false,
                failureReason: 'Account locked',
                user: $user
            );

            return back()->withErrors([
                'login' => 'The account is temporarily locked. Contact ICT.',
            ])->onlyInput('login');
        }

        if (
            $user->account_status === 'locked'
            && $user->lock_expires_at
            && now()->greaterThanOrEqualTo($user->lock_expires_at)
        ) {
            $user->update([
                'account_status' => 'active',
                'failed_login_attempts' => 0,
                'locked_at' => null,
                'lock_expires_at' => null,
            ]);
        }

        if (!$user->isAccountActive()) {
            $this->recordAttempt(
                request: $request,
                login: $credentials['login'],
                successful: false,
                failureReason: 'Inactive account',
                user: $user
            );

            return back()->withErrors([
                'login' => 'Your account is not active. Contact ICT.',
            ])->onlyInput('login');
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            $this->processFailedAttempt($request, $user);

            return back()->withErrors([
                'login' => 'The supplied login details are invalid.',
            ])->onlyInput('login');
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        $user->update([
            'failed_login_attempts' => 0,
            'locked_at' => null,
            'lock_expires_at' => null,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $this->recordAttempt(
            request: $request,
            login: $credentials['login'],
            successful: true,
            user: $user
        );

        $this->auditService->record(
            eventType: 'authentication',
            module: 'authentication',
            action: 'login',
            description: 'User logged into the system successfully.',
            subject: $user
        );

        if ($user->requiresPasswordChange()) {
            return redirect()->route('password.required');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $this->auditService->record(
                eventType: 'authentication',
                module: 'authentication',
                action: 'logout',
                description: 'User logged out of the system.',
                subject: $user
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function processFailedAttempt(
        Request $request,
        User $user
    ): void {
        $policy = PasswordPolicy::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->firstOrFail();

        $attempts = $user->failed_login_attempts + 1;

        $updates = [
            'failed_login_attempts' => $attempts,
        ];

        if ($attempts >= $policy->maximum_login_attempts) {
            $updates = [
                ...$updates,
                'account_status' => 'locked',
                'locked_at' => now(),
                'lock_expires_at' => now()->addMinutes(
                    $policy->account_lock_minutes
                ),
            ];
        }

        $user->update($updates);

        $this->recordAttempt(
            request: $request,
            login: $user->username,
            successful: false,
            failureReason: 'Incorrect password',
            user: $user
        );
    }

    private function recordAttempt(
        Request $request,
        string $login,
        bool $successful,
        ?string $failureReason = null,
        ?User $user = null
    ): void {
        LoginAttempt::create([
            'user_id' => $user?->id,
            'login_identifier' => $login,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'was_successful' => $successful,
            'failure_reason' => $failureReason,
            'attempted_at' => now(),
        ]);
    }
}
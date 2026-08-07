<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\Audit\LoginAttempt;
use Illuminate\View\View;

class LoginAttemptController extends Controller
{
    public function index(): View
    {
        $loginAttempts = LoginAttempt::query()
            ->with('user')
            ->latest('attempted_at')
            ->paginate(50);

        return view(
            'audit.login-attempts.index',
            compact('loginAttempts')
        );
    }

    public function show(LoginAttempt $loginAttempt): View
    {
        $loginAttempt->load('user');

        return view(
            'audit.login-attempts.show',
            compact('loginAttempt')
        );
    }
}
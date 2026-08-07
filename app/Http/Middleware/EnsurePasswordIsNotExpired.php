<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsNotExpired
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if ($user->passwordHasExpired()) {
            $user->forceFill([
                'must_change_password' => true,
            ])->save();

            return redirect()
                ->route('password.required')
                ->with(
                    'warning',
                    'Your password has expired. Please create a new password.'
                );
        }

        return $next($request);
    }
}
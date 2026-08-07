<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordWasChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if (
            $user->must_change_password
            || $user->temporary_password
        ) {
            if (!$request->routeIs([
                'password.required',
                'password.required.update',
                'logout',
            ])) {
                return redirect()
                    ->route('password.required')
                    ->with(
                        'warning',
                        'You must change your temporary password before continuing.'
                    );
            }
        }

        return $next($request);
    }
}
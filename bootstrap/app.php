<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsurePasswordIsNotExpired;
use App\Http\Middleware\EnsurePasswordWasChanged;
use App\Http\Middleware\TrackUserSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(
        function (Middleware $middleware): void {

            $middleware->alias([
                'account.active' =>
                    EnsureAccountIsActive::class,

                'password.changed' =>
                    EnsurePasswordWasChanged::class,

                'password.not-expired' =>
                    EnsurePasswordIsNotExpired::class,

                'session.track' =>
                    TrackUserSession::class,

                'role' =>
                    \Spatie\Permission\Middleware\RoleMiddleware::class,

                'permission' =>
                    \Spatie\Permission\Middleware\PermissionMiddleware::class,

                'role_or_permission' =>
                    \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            ]);

            $middleware->redirectGuestsTo(
                fn () => route('login')
            );
        }
    )
    ->withExceptions(
        function (Exceptions $exceptions): void {
            //
        }
    )
    ->create();
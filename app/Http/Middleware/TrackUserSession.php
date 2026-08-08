<?php

namespace App\Http\Middleware;

use App\Models\Audit\UserSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackUserSession
{
    /**
     * Track an authenticated user's current Laravel session.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        /*
        |--------------------------------------------------------------------------
        | Important
        |--------------------------------------------------------------------------
        |
        | We record/update the session BEFORE processing the response.
        |
        | This prevents logout/session invalidation operations from causing
        | the middleware to accidentally recreate an already closed session.
        |
        */

        if (
            auth()->check()
            && $request->hasSession()
        ) {
            $this->trackSession(
                $request
            );
        }

        return $next($request);
    }


    /**
     * Create or update the current tracked session.
     */
    private function trackSession(
        Request $request
    ): void {
        $user = $request->user();

        if (!$user) {
            return;
        }

        $laravelSessionId =
            $request->session()->getId();

        if (!$laravelSessionId) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Find Existing Active Session
        |--------------------------------------------------------------------------
        */

        $userSession = UserSession::query()
            ->where(
                'user_id',
                $user->id
            )
            ->where(
                'laravel_session_id',
                $laravelSessionId
            )
            ->where(
                'is_active',
                true
            )
            ->first();


        /*
        |--------------------------------------------------------------------------
        | Create Session
        |--------------------------------------------------------------------------
        */

        if (!$userSession) {
            UserSession::create([
                'session_uuid' =>
                    (string) Str::uuid(),

                'user_id' =>
                    $user->id,

                'laravel_session_id' =>
                    $laravelSessionId,

                'ip_address' =>
                    $request->ip(),

                'user_agent' =>
                    $request->userAgent(),

                'device_name' =>
                    $this->detectDeviceName(
                        $request->userAgent()
                    ),

                'login_at' =>
                    now(),

                'last_activity_at' =>
                    now(),

                'logout_at' =>
                    null,

                'logout_reason' =>
                    null,

                'is_active' =>
                    true,

                'was_forcibly_terminated' =>
                    false,
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Update Existing Session
        |--------------------------------------------------------------------------
        */

        $userSession->update([
            'ip_address' =>
                $request->ip(),

            'user_agent' =>
                $request->userAgent(),

            'device_name' =>
                $this->detectDeviceName(
                    $request->userAgent()
                ),

            'last_activity_at' =>
                now(),
        ]);
    }


    /**
     * Generate readable device information.
     */
    private function detectDeviceName(
        ?string $userAgent
    ): string {
        if (!$userAgent) {
            return 'Unknown Device';
        }

        $platform =
            'Unknown Device';

        $browser =
            'Browser';


        /*
        |--------------------------------------------------------------------------
        | Operating System / Device
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $userAgent,
                'Windows'
            )
        ) {
            $platform =
                'Windows PC';
        } elseif (
            str_contains(
                $userAgent,
                'Macintosh'
            )
            ||
            str_contains(
                $userAgent,
                'Mac OS'
            )
        ) {
            $platform =
                'Mac';
        } elseif (
            str_contains(
                $userAgent,
                'iPhone'
            )
        ) {
            $platform =
                'iPhone';
        } elseif (
            str_contains(
                $userAgent,
                'iPad'
            )
        ) {
            $platform =
                'iPad';
        } elseif (
            str_contains(
                $userAgent,
                'Android'
            )
        ) {
            $platform =
                'Android Device';
        } elseif (
            str_contains(
                $userAgent,
                'Linux'
            )
        ) {
            $platform =
                'Linux Device';
        }


        /*
        |--------------------------------------------------------------------------
        | Browser
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $userAgent,
                'Edg/'
            )
        ) {
            $browser =
                'Microsoft Edge';
        } elseif (
            str_contains(
                $userAgent,
                'Chrome/'
            )
        ) {
            $browser =
                'Google Chrome';
        } elseif (
            str_contains(
                $userAgent,
                'Firefox/'
            )
        ) {
            $browser =
                'Mozilla Firefox';
        } elseif (
            str_contains(
                $userAgent,
                'Safari/'
            )
        ) {
            $browser =
                'Safari';
        }


        return $platform
            . ' - '
            . $browser;
    }
}
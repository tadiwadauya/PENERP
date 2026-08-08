<?php

namespace App\Services\Audit;

use App\Models\Audit\AuditTrail;
use App\Models\Audit\UserSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditService
{
    /**
     * Generic audit recorder.
     *
     * This method exists because several parts of the system
     * already call AuditService::record().
     */
    public function record(
        string $eventType,
        string $module,
        string $action,
        string $description,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        string $outcome = 'success',
        ?string $failureReason = null,
        ?Request $request = null
    ): AuditTrail {
        $request ??= request();

        $user =
            auth()->user();

        $sessionId =
            $this->resolveSessionId(
                $request
            );


        return AuditTrail::create([
            'event_uuid' =>
                (string) Str::uuid(),

            'user_id' =>
                $user?->id,

            'session_id' =>
                $sessionId,

            'event_type' =>
                $eventType,

            'module' =>
                $module,

            'action' =>
                $action,

            'auditable_type' =>
                $subject
                    ? $subject::class
                    : null,

            'auditable_id' =>
                $subject
                    ? (string) $subject->getKey()
                    : null,

            'description' =>
                $description,

            'old_values' =>
                $oldValues,

            'new_values' =>
                $newValues,

            'metadata' =>
                $metadata,

            'route_name' =>
                $request
                    ->route()
                    ?->getName(),

            'url' =>
                $request->fullUrl(),

            'http_method' =>
                $request->method(),

            'ip_address' =>
                $request->ip(),

            'user_agent' =>
                $request->userAgent(),

            'device_identifier' =>
                $this->deviceIdentifier(
                    $request->userAgent()
                ),

            'outcome' =>
                $outcome,

            'failure_reason' =>
                $failureReason,

            'occurred_at' =>
                now(),
        ]);
    }


    /**
     * Record a successful system event.
     */
    public function log(
        string $eventType,
        string $module,
        string $action,
        string $description,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?Request $request = null
    ): AuditTrail {
        return $this->record(
            eventType:
                $eventType,

            module:
                $module,

            action:
                $action,

            description:
                $description,

            subject:
                $auditable,

            oldValues:
                $oldValues,

            newValues:
                $newValues,

            metadata:
                $metadata,

            outcome:
                'success',

            failureReason:
                null,

            request:
                $request
        );
    }


    /**
     * Record a failed system event.
     */
    public function failure(
        string $eventType,
        string $module,
        string $action,
        string $description,
        ?string $failureReason = null,
        ?Model $auditable = null,
        ?array $metadata = null,
        ?Request $request = null
    ): AuditTrail {
        return $this->record(
            eventType:
                $eventType,

            module:
                $module,

            action:
                $action,

            description:
                $description,

            subject:
                $auditable,

            oldValues:
                null,

            newValues:
                null,

            metadata:
                $metadata,

            outcome:
                'failed',

            failureReason:
                $failureReason,

            request:
                $request
        );
    }


    /**
     * Return safe model values for audit trail storage.
     */
    public function values(
        Model $model
    ): array {
        $values =
            $model->getAttributes();


        /*
        |--------------------------------------------------------------------------
        | Remove Sensitive Values
        |--------------------------------------------------------------------------
        */

        foreach ([
            'password',
            'remember_token',
        ] as $field) {
            unset(
                $values[$field]
            );
        }


        return $values;
    }


    /**
     * Resolve current tracked user session.
     */
    private function resolveSessionId(
        Request $request
    ): ?int {
        $user =
            auth()->user();


        if (
            !$user
            ||
            !$request->hasSession()
        ) {
            return null;
        }


        $laravelSessionId =
            $request
                ->session()
                ->getId();


        if (!$laravelSessionId) {
            return null;
        }


        $trackedSession =
            UserSession::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->where(
                    'laravel_session_id',
                    $laravelSessionId
                )
                ->orderByDesc('id')
                ->first();


        return $trackedSession?->id;
    }


    /**
     * Generate readable device identifier.
     */
    private function deviceIdentifier(
        ?string $userAgent
    ): ?string {
        if (!$userAgent) {
            return null;
        }


        $platform =
            'Unknown Device';

        $browser =
            'Unknown Browser';


        /*
        |--------------------------------------------------------------------------
        | Platform
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
                'Android'
            )
        ) {
            $platform =
                'Android Device';
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
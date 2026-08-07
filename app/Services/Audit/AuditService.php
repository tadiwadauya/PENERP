<?php

namespace App\Services\Audit;

use App\Models\Audit\AuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditService
{
    public function record(
        string $eventType,
        string $module,
        string $action,
        string $description,
        ?Model $subject = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = [],
        string $outcome = 'success',
        ?string $failureReason = null
    ): AuditTrail {
        $request = request();

        return AuditTrail::create([
            'event_uuid' => (string) Str::uuid(),
            'user_id' => auth()->id(),
            'session_id' => session()->get('audit_session_id'),
            'event_type' => $eventType,
            'module' => $module,
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'description' => $description,
            'old_values' => $this->removeSensitiveValues($oldValues),
            'new_values' => $this->removeSensitiveValues($newValues),
            'metadata' => $this->removeSensitiveValues($metadata),
            'route_name' => $request?->route()?->getName(),
            'url' => $request?->fullUrl(),
            'http_method' => $request?->method(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'device_identifier' => null,
            'outcome' => $outcome,
            'failure_reason' => $failureReason,
            'occurred_at' => now(),
        ]);
    }

    private function removeSensitiveValues(array $values): array
    {
        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'temporary_password',
            'remember_token',
            'token',
            '_token',
        ];

        foreach ($values as $key => $value) {
            if (in_array($key, $sensitiveKeys, true)) {
                $values[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->removeSensitiveValues($value);
            }
        }

        return $values;
    }
}
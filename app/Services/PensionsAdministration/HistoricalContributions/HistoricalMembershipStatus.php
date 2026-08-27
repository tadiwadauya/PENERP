<?php

namespace App\Services\PensionsAdministration\HistoricalContributions;

class HistoricalMembershipStatus
{
    public static function normalize(mixed $value): string
    {
        $value = strtolower(trim((string) ($value ?? '')));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return match ($value) {
            '1',
            'active',
            'act',
            'current',
            'contributing',
            'contributor',
            'in service',
            'in-service' => 'active',

            '2',
            'inactive',
            'in-active',
            'in active',
            'dormant' => 'inactive',

            '3',
            'exit',
            'exited',
            'ceased',
            'left',
            'terminated',
            'withdrawn',
            'withdrawal',
            'retired',
            'deceased' => 'exited',

            '4',
            'suspended',
            'suspend',
            'supended',
            'supended member' => 'suspended',

            '5',
            'waiting approval',
            'waiting_approval',
            'waiting aproval',
            'waitingapproval',
            'awaiting approval' => 'waiting approval',

            '6',
            'deferred',
            'deffered',
            'defered' => 'deferred',

            default => 'inactive',
        };
    }

    public static function isActive(mixed $value): bool
    {
        return self::normalize($value) === 'active';
    }

    public static function isExited(mixed $value): bool
    {
        return self::normalize($value) === 'exited';
    }

    public static function isSuspended(mixed $value): bool
    {
        return self::normalize($value) === 'suspended';
    }

    public static function employmentStatus(mixed $value): string
    {
        return match (self::normalize($value)) {
            'active' => 'active',
            'exited' => 'exited',
            'suspended' => 'suspended',
            'deferred' => 'deferred',
            'waiting approval' => 'waiting approval',
            default => 'inactive',
        };
    }

    public static function label(mixed $value): string
    {
        return match (self::normalize($value)) {
            'active' => 'Active',
            'exited' => 'Exited',
            'suspended' => 'Suspended',
            'deferred' => 'Deferred',
            'waiting approval' => 'Waiting Approval',
            default => 'Inactive',
        };
    }

    public static function isRecognised(mixed $value): bool
    {
        $value = strtolower(trim((string) ($value ?? '')));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return in_array($value, [
            '1', 'active', 'act', 'current', 'contributing', 'contributor', 'in service', 'in-service',
            '2', 'inactive', 'in-active', 'in active', 'dormant',
            '3', 'exit', 'exited', 'ceased', 'left', 'terminated', 'withdrawn', 'withdrawal', 'retired', 'deceased',
            '4', 'suspended', 'suspend', 'supended', 'supended member',
            '5', 'waiting approval', 'waiting_approval', 'waiting aproval', 'waitingapproval', 'awaiting approval',
            '6', 'deferred', 'deffered', 'defered',
            '', '0', 'null', 'n/a', 'na', '-',
        ], true);
    }
}

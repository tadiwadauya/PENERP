<?php

namespace App\Models\Audit;

use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'login_identifier',
        'ip_address',
        'user_agent',
        'was_successful',
        'failure_reason',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'was_successful' => 'boolean',
            'attempted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}
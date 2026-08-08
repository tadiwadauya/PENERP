<?php

namespace App\Models\Audit;

use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    protected $table = 'user_sessions';

    protected $fillable = [
        'session_uuid',
        'user_id',
        'laravel_session_id',
        'ip_address',
        'user_agent',
        'device_name',
        'login_at',
        'last_activity_at',
        'logout_at',
        'logout_reason',
        'is_active',
        'was_forcibly_terminated',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'logout_at' => 'datetime',
        'is_active' => 'boolean',
        'was_forcibly_terminated' => 'boolean',
    ];

    /**
     * User who owns this session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}
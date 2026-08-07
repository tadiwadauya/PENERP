<?php

namespace App\Models\UserManagement;

use Illuminate\Database\Eloquent\Model;

class PasswordPolicy extends Model
{
    protected $fillable = [
        'name',
        'is_default',
        'is_active',

        'minimum_length',
        'maximum_length',

        'require_uppercase',
        'require_lowercase',
        'require_number',
        'require_special_character',

        'password_expiry_days',
        'expiry_warning_days',
        'password_history_count',

        'allow_password_reuse',
        'allow_username_in_password',
        'allow_name_in_password',

        'maximum_login_attempts',
        'account_lock_minutes',

        'temporary_password_expiry_hours',

        'force_first_login_change',

        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',

            'minimum_length' => 'integer',
            'maximum_length' => 'integer',

            'require_uppercase' => 'boolean',
            'require_lowercase' => 'boolean',
            'require_number' => 'boolean',
            'require_special_character' => 'boolean',

            'password_expiry_days' => 'integer',
            'expiry_warning_days' => 'integer',
            'password_history_count' => 'integer',

            'allow_password_reuse' => 'boolean',
            'allow_username_in_password' => 'boolean',
            'allow_name_in_password' => 'boolean',

            'maximum_login_attempts' => 'integer',
            'account_lock_minutes' => 'integer',

            'temporary_password_expiry_hours' => 'integer',

            'force_first_login_change' => 'boolean',
        ];
    }
}
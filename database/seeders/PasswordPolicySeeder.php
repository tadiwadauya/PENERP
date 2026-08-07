<?php

namespace Database\Seeders;

use App\Models\UserManagement\PasswordPolicy;
use Illuminate\Database\Seeder;

class PasswordPolicySeeder extends Seeder
{
    public function run(): void
    {
        PasswordPolicy::updateOrCreate(
            ['name' => 'LAPF Default Password Policy'],
            [
                'is_default' => true,
                'is_active' => true,
                'minimum_length' => 8,
                'maximum_length' => 128,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_number' => true,
                'require_special_character' => true,
                'password_expiry_days' => 30,
                'expiry_warning_days' => 5,
                'password_history_count' => 5,
                'allow_password_reuse' => false,
                'allow_username_in_password' => false,
                'allow_name_in_password' => false,
                'maximum_login_attempts' => 5,
                'account_lock_minutes' => 30,
                'temporary_password_expiry_hours' => 24,
                'force_first_login_change' => true,
            ]
        );
    }
}
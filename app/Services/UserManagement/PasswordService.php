<?php

namespace App\Services\UserManagement;

use App\Models\UserManagement\PasswordHistory;
use App\Models\UserManagement\PasswordPolicy;
use App\Models\UserManagement\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordService
{
    public function getActivePolicy(): PasswordPolicy
    {
        return PasswordPolicy::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->firstOrFail();
    }

    public function generateTemporaryPassword(): string
    {
        return Str::password(
            length: 14,
            letters: true,
            numbers: true,
            symbols: true,
            spaces: false
        );
    }

    public function validatePassword(
        User $user,
        string $password
    ): void {
        $policy = $this->getActivePolicy();

        $errors = [];

        if (
            mb_strlen($password)
            < $policy->minimum_length
        ) {
            $errors[] =
                "Password must contain at least "
                .$policy->minimum_length
                ." characters.";
        }

        if (
            mb_strlen($password)
            > $policy->maximum_length
        ) {
            $errors[] =
                "Password must not exceed "
                .$policy->maximum_length
                ." characters.";
        }

        if (
            $policy->require_uppercase
            && !preg_match('/[A-Z]/', $password)
        ) {
            $errors[] =
                'Password must contain an uppercase letter.';
        }

        if (
            $policy->require_lowercase
            && !preg_match('/[a-z]/', $password)
        ) {
            $errors[] =
                'Password must contain a lowercase letter.';
        }

        if (
            $policy->require_number
            && !preg_match('/[0-9]/', $password)
        ) {
            $errors[] =
                'Password must contain a number.';
        }

        if (
            $policy->require_special_character
            && !preg_match(
                '/[^A-Za-z0-9]/',
                $password
            )
        ) {
            $errors[] =
                'Password must contain a special character.';
        }

        $passwordLower = mb_strtolower($password);

        if (
            !$policy->allow_username_in_password
            && $user->username
            && str_contains(
                $passwordLower,
                mb_strtolower($user->username)
            )
        ) {
            $errors[] =
                'Password may not contain the username.';
        }

        if (!$policy->allow_name_in_password) {
            foreach (
                [
                    $user->first_name,
                    $user->surname,
                ] as $name
            ) {
                if (
                    $name
                    && mb_strlen($name) >= 3
                    && str_contains(
                        $passwordLower,
                        mb_strtolower($name)
                    )
                ) {
                    $errors[] =
                        'Password may not contain your name.';

                    break;
                }
            }
        }

        if (!$policy->allow_password_reuse) {
            if (
                $user->exists
                && $user->password
                && Hash::check(
                    $password,
                    $user->password
                )
            ) {
                $errors[] =
                    'The new password cannot be the current password.';
            }

            if ($user->exists) {
                $history = $user
                    ->passwordHistory()
                    ->latest('changed_at')
                    ->limit(
                        $policy->password_history_count
                    )
                    ->get();

                foreach ($history as $oldPassword) {
                    if (
                        Hash::check(
                            $password,
                            $oldPassword->password_hash
                        )
                    ) {
                        $errors[] =
                            'You may not reuse a recently used password.';

                        break;
                    }
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'password' => $errors,
            ]);
        }
    }

    public function changePassword(
        User $user,
        string $newPassword,
        ?User $changedBy = null,
        string $reason = 'user_change'
    ): void {
        $this->validatePassword(
            $user,
            $newPassword
        );

        $policy = $this->getActivePolicy();

        DB::transaction(function () use (
            $user,
            $newPassword,
            $changedBy,
            $reason,
            $policy
        ): void {
            if ($user->password) {
                PasswordHistory::create([
                    'user_id' => $user->id,
                    'password_hash' => $user->password,
                    'changed_at' => now(),
                    'changed_by' => $changedBy?->id
                        ?? $user->id,
                    'change_reason' => $reason,
                ]);
            }

            $user->forceFill([
                'password' => Hash::make(
                    $newPassword
                ),
                'must_change_password' => false,
                'temporary_password' => false,
                'password_changed_at' => now(),
                'password_expires_at' =>
                    $policy->password_expiry_days > 0
                        ? now()->addDays(
                            $policy->password_expiry_days
                        )
                        : null,
                'failed_login_attempts' => 0,
                'locked_at' => null,
                'lock_expires_at' => null,
            ])->save();

            $keep = max(
                1,
                $policy->password_history_count
            );

            $idsToKeep = $user
                ->passwordHistory()
                ->latest('changed_at')
                ->limit($keep)
                ->pluck('id');

            $user->passwordHistory()
                ->whereNotIn('id', $idsToKeep)
                ->delete();
        });
    }
}
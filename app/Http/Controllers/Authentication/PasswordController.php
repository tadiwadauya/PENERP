<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordController extends Controller
{
    /**
     * Normal voluntary password change page.
     */
    public function edit(Request $request): View
    {
        return view(
            'authentication.password.change',
            [
                'user' => $request->user(),
                'requiredChange' => false,
                'policy' => $this->getPasswordPolicy(),
            ]
        );
    }


    /**
     * Normal voluntary password update.
     */
    public function update(
        Request $request
    ): RedirectResponse {
        return $this->processPasswordChange(
            $request,
            false
        );
    }


    /**
     * Mandatory password-change page.
     *
     * Used after first login with temporary password
     * or when the system forces a password change.
     */
    public function editRequired(
        Request $request
    ): View {
        return view(
            'authentication.password.change',
            [
                'user' => $request->user(),
                'requiredChange' => true,
                'policy' => $this->getPasswordPolicy(),
            ]
        );
    }


    /**
     * Update mandatory password.
     */
    public function updateRequired(
        Request $request
    ): RedirectResponse {
        return $this->processPasswordChange(
            $request,
            true
        );
    }


    /**
     * Process password change.
     */
    private function processPasswordChange(
        Request $request,
        bool $requiredChange
    ): RedirectResponse {
        $user = $request->user();

        $request->validate([
            'current_password' => [
                'required',
                'string',
            ],

            'password' => [
                'required',
                'string',
                'confirmed',
            ],
        ], [
            'current_password.required' =>
                'Your current password is required.',

            'password.required' =>
                'Please enter your new password.',

            'password.confirmed' =>
                'The password confirmation does not match.',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Verify Current Password
        |--------------------------------------------------------------------------
        */

        if (
            !Hash::check(
                $request->current_password,
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'current_password' =>
                    'The current password you entered is incorrect.',
            ]);
        }


        $newPassword =
            $request->password;


        /*
        |--------------------------------------------------------------------------
        | New Password Must Differ From Current Password
        |--------------------------------------------------------------------------
        */

        if (
            Hash::check(
                $newPassword,
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'password' =>
                    'Your new password cannot be the same as your current password.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Password Policy
        |--------------------------------------------------------------------------
        */

        $policy =
            $this->getPasswordPolicy();


        $minimumLength =
            (int) $this->policyValue(
                $policy,
                [
                    'minimum_length',
                    'min_length',
                    'password_min_length',
                ],
                8
            );


        $requireUppercase =
            $this->policyBoolean(
                $policy,
                [
                    'require_uppercase',
                    'uppercase_required',
                ],
                true
            );


        $requireLowercase =
            $this->policyBoolean(
                $policy,
                [
                    'require_lowercase',
                    'lowercase_required',
                ],
                true
            );


        $requireNumber =
            $this->policyBoolean(
                $policy,
                [
                    'require_number',
                    'require_numbers',
                    'number_required',
                ],
                true
            );


        $requireSpecial =
            $this->policyBoolean(
                $policy,
                [
                    'require_special_character',
                    'require_special_characters',
                    'special_character_required',
                ],
                true
            );


        /*
        |--------------------------------------------------------------------------
        | Minimum Length
        |--------------------------------------------------------------------------
        */

        if (
            mb_strlen($newPassword)
            < $minimumLength
        ) {
            throw ValidationException::withMessages([
                'password' =>
                    "Password must contain at least {$minimumLength} characters.",
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Uppercase
        |--------------------------------------------------------------------------
        */

        if (
            $requireUppercase
            && !preg_match(
                '/[A-Z]/',
                $newPassword
            )
        ) {
            throw ValidationException::withMessages([
                'password' =>
                    'Password must contain at least one uppercase letter.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Lowercase
        |--------------------------------------------------------------------------
        */

        if (
            $requireLowercase
            && !preg_match(
                '/[a-z]/',
                $newPassword
            )
        ) {
            throw ValidationException::withMessages([
                'password' =>
                    'Password must contain at least one lowercase letter.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Number
        |--------------------------------------------------------------------------
        */

        if (
            $requireNumber
            && !preg_match(
                '/[0-9]/',
                $newPassword
            )
        ) {
            throw ValidationException::withMessages([
                'password' =>
                    'Password must contain at least one number.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Special Character
        |--------------------------------------------------------------------------
        */

        if (
            $requireSpecial
            && !preg_match(
                '/[^A-Za-z0-9]/',
                $newPassword
            )
        ) {
            throw ValidationException::withMessages([
                'password' =>
                    'Password must contain at least one special character.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Password Reuse
        |--------------------------------------------------------------------------
        */

        $allowPasswordReuse =
            $this->policyBoolean(
                $policy,
                [
                    'allow_password_reuse',
                    'password_reuse_allowed',
                ],
                false
            );


        $historyCount =
            (int) $this->policyValue(
                $policy,
                [
                    'password_history_count',
                    'history_count',
                    'password_history',
                ],
                5
            );


        if (
            !$allowPasswordReuse
            && $historyCount > 0
        ) {
            $this->ensurePasswordNotPreviouslyUsed(
                $user->id,
                $newPassword,
                $historyCount
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Password Expiry
        |--------------------------------------------------------------------------
        */

        $expiryDays =
            (int) $this->policyValue(
                $policy,
                [
                    'password_expiry_days',
                    'expiry_days',
                    'password_expiration_days',
                ],
                30
            );


        DB::transaction(
            function () use (
                $user,
                $newPassword,
                $expiryDays,
                $historyCount
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Store Current Password In History Before Replacing It
                |--------------------------------------------------------------------------
                */

                $this->storePasswordHistory(
                    $user->id,
                    $user->password
                );


                /*
                |--------------------------------------------------------------------------
                | Change Password
                |--------------------------------------------------------------------------
                */

                $user->password =
                    Hash::make(
                        $newPassword
                    );


                $user->must_change_password =
                    false;


                $user->temporary_password =
                    false;


                $user->password_changed_at =
                    now();


                if (
                    $expiryDays > 0
                ) {
                    $user->password_expires_at =
                        now()->addDays(
                            $expiryDays
                        );
                } else {
                    $user->password_expires_at =
                        null;
                }


                /*
                |--------------------------------------------------------------------------
                | Reset Failed Login State
                |--------------------------------------------------------------------------
                */

                $user->failed_login_attempts =
                    0;

                $user->locked_at =
                    null;

                $user->lock_expires_at =
                    null;


                $user->save();


                /*
                |--------------------------------------------------------------------------
                | Trim Password History
                |--------------------------------------------------------------------------
                */

                $this->trimPasswordHistory(
                    $user->id,
                    max(
                        $historyCount,
                        1
                    )
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Audit
        |--------------------------------------------------------------------------
        */

        $this->writePasswordAudit(
            $user->id,
            $request,
            $requiredChange
        );


        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        if ($requiredChange) {
            return redirect()
                ->route('dashboard')
                ->with(
                    'success',
                    'Your password has been changed successfully.'
                );
        }


        return redirect()
            ->route('profile.show')
            ->with(
                'success',
                'Your password has been changed successfully.'
            );
    }


    /**
     * Get active password policy.
     */
    private function getPasswordPolicy(): ?object
    {
        if (
            !Schema::hasTable(
                'password_policies'
            )
        ) {
            return null;
        }


        $query =
            DB::table(
                'password_policies'
            );


        if (
            Schema::hasColumn(
                'password_policies',
                'is_active'
            )
        ) {
            $query->where(
                'is_active',
                true
            );
        }


        return $query
            ->orderByDesc('id')
            ->first();
    }


    /**
     * Get policy value using one of several possible field names.
     */
    private function policyValue(
        ?object $policy,
        array $names,
        mixed $default = null
    ): mixed {
        if (!$policy) {
            return $default;
        }


        foreach ($names as $name) {
            if (
                property_exists(
                    $policy,
                    $name
                )
                && $policy->{$name} !== null
            ) {
                return $policy->{$name};
            }
        }


        return $default;
    }


    /**
     * Get boolean policy value.
     */
    private function policyBoolean(
        ?object $policy,
        array $names,
        bool $default
    ): bool {
        $value =
            $this->policyValue(
                $policy,
                $names,
                $default
            );


        return filter_var(
            $value,
            FILTER_VALIDATE_BOOLEAN
        );
    }


    /**
     * Prevent reuse of previous passwords.
     */
    private function ensurePasswordNotPreviouslyUsed(
        int $userId,
        string $newPassword,
        int $historyCount
    ): void {
        if (
            !Schema::hasTable(
                'password_histories'
            )
        ) {
            return;
        }


        $passwordColumn =
            $this->passwordHistoryColumn();


        if (!$passwordColumn) {
            return;
        }


        $history =
            DB::table(
                'password_histories'
            )
                ->where(
                    'user_id',
                    $userId
                )
                ->orderByDesc('id')
                ->limit(
                    $historyCount
                )
                ->get();


        foreach ($history as $record) {
            $hash =
                $record->{$passwordColumn}
                ?? null;


            if (
                $hash
                && Hash::check(
                    $newPassword,
                    $hash
                )
            ) {
                throw ValidationException::withMessages([
                    'password' =>
                        "You cannot reuse any of your last {$historyCount} passwords.",
                ]);
            }
        }
    }


    /**
     * Store password history.
     */
    private function storePasswordHistory(
        int $userId,
        string $passwordHash
    ): void {
        if (
            !Schema::hasTable(
                'password_histories'
            )
        ) {
            return;
        }


        $passwordColumn =
            $this->passwordHistoryColumn();


        if (!$passwordColumn) {
            return;
        }


        $data = [
            'user_id' =>
                $userId,

            $passwordColumn =>
                $passwordHash,
        ];


        if (
            Schema::hasColumn(
                'password_histories',
                'created_at'
            )
        ) {
            $data['created_at'] =
                now();
        }


        if (
            Schema::hasColumn(
                'password_histories',
                'updated_at'
            )
        ) {
            $data['updated_at'] =
                now();
        }


        DB::table(
            'password_histories'
        )->insert(
            $data
        );
    }


    /**
     * Keep only configured password-history records.
     */
    private function trimPasswordHistory(
        int $userId,
        int $historyCount
    ): void {
        if (
            !Schema::hasTable(
                'password_histories'
            )
        ) {
            return;
        }


        $idsToKeep =
            DB::table(
                'password_histories'
            )
                ->where(
                    'user_id',
                    $userId
                )
                ->orderByDesc('id')
                ->limit(
                    $historyCount
                )
                ->pluck('id');


        if ($idsToKeep->isEmpty()) {
            return;
        }


        DB::table(
            'password_histories'
        )
            ->where(
                'user_id',
                $userId
            )
            ->whereNotIn(
                'id',
                $idsToKeep
            )
            ->delete();
    }


    /**
     * Determine password history hash field.
     */
    private function passwordHistoryColumn(): ?string
    {
        if (
            Schema::hasColumn(
                'password_histories',
                'password_hash'
            )
        ) {
            return 'password_hash';
        }


        if (
            Schema::hasColumn(
                'password_histories',
                'password'
            )
        ) {
            return 'password';
        }


        return null;
    }


    /**
     * Record password change without ever recording the password.
     */
    private function writePasswordAudit(
        int $userId,
        Request $request,
        bool $requiredChange
    ): void {
        Log::info(
            'User password changed',
            [
                'user_id' =>
                    $userId,

                'change_type' =>
                    $requiredChange
                        ? 'required'
                        : 'voluntary',

                'ip_address' =>
                    $request->ip(),

                'user_agent' =>
                    $request->userAgent(),

                'changed_at' =>
                    now()->toDateTimeString(),
            ]
        );
    }
}
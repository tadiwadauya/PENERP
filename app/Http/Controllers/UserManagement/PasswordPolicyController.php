<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\UserManagement\PasswordPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PasswordPolicyController extends Controller
{
    /**
     * Display the active password policy configuration.
     */
    public function edit(): View
    {
        $this->ensurePermission(
            'user-management.password-policies.view'
        );

        $policy = PasswordPolicy::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Fallback
        |--------------------------------------------------------------------------
        */

        if (!$policy) {
            $policy = PasswordPolicy::query()
                ->orderByDesc('id')
                ->first();
        }

        /*
        |--------------------------------------------------------------------------
        | Create Default Policy
        |--------------------------------------------------------------------------
        */

        if (!$policy) {
            $policy = PasswordPolicy::create([
                'name' =>
                    'Default LAPF Password Policy',

                'is_default' =>
                    true,

                'is_active' =>
                    true,

                'minimum_length' =>
                    8,

                'maximum_length' =>
                    128,

                'require_uppercase' =>
                    true,

                'require_lowercase' =>
                    true,

                'require_number' =>
                    true,

                'require_special_character' =>
                    true,

                'password_expiry_days' =>
                    30,

                'expiry_warning_days' =>
                    5,

                'password_history_count' =>
                    5,

                'allow_password_reuse' =>
                    false,

                'allow_username_in_password' =>
                    false,

                'allow_name_in_password' =>
                    false,

                'maximum_login_attempts' =>
                    5,

                'account_lock_minutes' =>
                    30,

                'temporary_password_expiry_hours' =>
                    24,

                'force_first_login_change' =>
                    true,

                'created_by' =>
                    auth()->id(),

                'updated_by' =>
                    auth()->id(),
            ]);
        }

        return view(
            'user-management.password-policies.edit',
            compact('policy')
        );
    }


    /**
     * Update password policy.
     */
    public function update(
        Request $request
    ): RedirectResponse {
        $this->ensurePermission(
            'user-management.password-policies.update'
        );

        $validated = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:150',
                ],

                'minimum_length' => [
                    'required',
                    'integer',
                    'min:6',
                    'max:128',
                ],

                'maximum_length' => [
                    'required',
                    'integer',
                    'min:6',
                    'max:255',
                    'gte:minimum_length',
                ],

                'require_uppercase' => [
                    'required',
                    'boolean',
                ],

                'require_lowercase' => [
                    'required',
                    'boolean',
                ],

                'require_number' => [
                    'required',
                    'boolean',
                ],

                'require_special_character' => [
                    'required',
                    'boolean',
                ],

                'password_expiry_days' => [
                    'required',
                    'integer',
                    'min:0',
                    'max:3650',
                ],

                'expiry_warning_days' => [
                    'required',
                    'integer',
                    'min:0',
                    'max:365',
                ],

                'password_history_count' => [
                    'required',
                    'integer',
                    'min:0',
                    'max:50',
                ],

                'allow_password_reuse' => [
                    'required',
                    'boolean',
                ],

                'allow_username_in_password' => [
                    'required',
                    'boolean',
                ],

                'allow_name_in_password' => [
                    'required',
                    'boolean',
                ],

                'maximum_login_attempts' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:100',
                ],

                'account_lock_minutes' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:10080',
                ],

                'temporary_password_expiry_hours' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:720',
                ],

                'force_first_login_change' => [
                    'required',
                    'boolean',
                ],

                'is_active' => [
                    'required',
                    'boolean',
                ],

                'is_default' => [
                    'required',
                    'boolean',
                ],
            ],
            [
                'maximum_length.gte' =>
                    'Maximum password length cannot be less than the minimum password length.',

                'maximum_login_attempts.required' =>
                    'Maximum failed login attempts is required.',

                'account_lock_minutes.required' =>
                    'Account lock duration is required.',
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Warning Days Validation
        |--------------------------------------------------------------------------
        */

        if (
            (int) $validated['password_expiry_days'] > 0
            &&
            (int) $validated['expiry_warning_days']
            > (int) $validated['password_expiry_days']
        ) {
            return back()
                ->withErrors([
                    'expiry_warning_days' =>
                        'Expiry warning days cannot exceed password expiry days.',
                ])
                ->withInput();
        }


        DB::transaction(
            function () use (
                $validated
            ): void {

                $policy = PasswordPolicy::query()
                    ->where('is_active', true)
                    ->orderByDesc('is_default')
                    ->orderByDesc('id')
                    ->first();


                if (!$policy) {
                    $policy = PasswordPolicy::query()
                        ->orderByDesc('id')
                        ->first();
                }


                if (!$policy) {
                    $policy = new PasswordPolicy();

                    $policy->created_by =
                        auth()->id();
                }


                /*
                |--------------------------------------------------------------------------
                | Only One Default Policy
                |--------------------------------------------------------------------------
                */

                if (
                    (bool) $validated['is_default']
                ) {
                    PasswordPolicy::query()
                        ->where(
                            'id',
                            '<>',
                            $policy->id ?? 0
                        )
                        ->update([
                            'is_default' =>
                                false,
                        ]);
                }


                $policy->fill(
                    $validated
                );


                $policy->updated_by =
                    auth()->id();


                $policy->save();
            }
        );


        return redirect()
            ->route(
                'user-management.password-policies.edit'
            )
            ->with(
                'success',
                'Password policy updated successfully.'
            );
    }


    /**
     * Auditor-friendly password policy report.
     */
    public function report(): View
    {
        $this->ensurePermission(
            'user-management.password-policies.view'
        );

        $policy = PasswordPolicy::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->first();


        if (!$policy) {
            $policy = PasswordPolicy::query()
                ->orderByDesc('id')
                ->firstOrFail();
        }


        return view(
            'user-management.password-policies.report',
            compact('policy')
        );
    }


    /**
     * Permission enforcement.
     */
    private function ensurePermission(
        string $permission
    ): void {
        $user =
            auth()->user();


        abort_if(
            !$user,
            401,
            'Unauthenticated.'
        );


        if (
            $user->is_system_administrator
        ) {
            return;
        }


        abort_unless(
            $user->can(
                $permission
            ),
            403,
            'You do not have permission to perform this action.'
        );
    }
}
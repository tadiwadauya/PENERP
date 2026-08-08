@extends('layouts.app')

@section('title', 'Password Policy')

@section('page-heading', 'Password Policy')


@section('page-actions')

    <a
        href="{{ route(
            'user-management.password-policies.report'
        ) }}"
        class="btn btn-info"
    >
        <i class="mdi mdi-file-document-outline me-1"></i>

        Policy Report
    </a>

@endsection


@section('content')


@if(session('success'))

    <div
        class="alert alert-success alert-dismissible fade show"
        role="alert"
    >

        <i class="mdi mdi-check-circle-outline me-1"></i>

        {{ session('success') }}

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

@endif


@if($errors->any())

    <div
        class="alert alert-danger alert-dismissible fade show"
        role="alert"
    >

        <h5 class="alert-heading">
            Please correct the following
        </h5>

        <ul class="mb-0">

            @foreach($errors->all() as $error)

                <li>
                    {{ $error }}
                </li>

            @endforeach

        </ul>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

@endif



<form
    method="POST"
    action="{{ route(
        'user-management.password-policies.update'
    ) }}"
>

    @csrf
    @method('PUT')


    {{-- =====================================================
         POLICY INFORMATION
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Policy Information
            </h4>


            <p class="card-title-desc">

                Define the password and account security
                requirements used by the LAPF Pension Fund System.

            </p>


            <div class="row">


                <div class="col-lg-8">

                    <div class="mb-3">

                        <label
                            for="name"
                            class="form-label"
                        >
                            Policy Name
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control"
                            value="{{ old(
                                'name',
                                $policy->name
                            ) }}"
                            required
                        >

                    </div>

                </div>


                <div class="col-lg-2">

                    <div class="mb-3">

                        <label class="form-label">
                            Active
                        </label>


                        <input
                            type="hidden"
                            name="is_active"
                            value="0"
                        >


                        <div class="form-check form-switch mt-2">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="is_active"
                                name="is_active"
                                value="1"
                                @checked(
                                    old(
                                        'is_active',
                                        $policy->is_active
                                    )
                                )
                            >

                            <label
                                class="form-check-label"
                                for="is_active"
                            >
                                Active
                            </label>

                        </div>

                    </div>

                </div>


                <div class="col-lg-2">

                    <div class="mb-3">

                        <label class="form-label">
                            Default
                        </label>


                        <input
                            type="hidden"
                            name="is_default"
                            value="0"
                        >


                        <div class="form-check form-switch mt-2">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="is_default"
                                name="is_default"
                                value="1"
                                @checked(
                                    old(
                                        'is_default',
                                        $policy->is_default
                                    )
                                )
                            >

                            <label
                                class="form-check-label"
                                for="is_default"
                            >
                                Default
                            </label>

                        </div>

                    </div>

                </div>


            </div>

        </div>

    </div>



    {{-- =====================================================
         PASSWORD LENGTH
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Password Length
            </h4>


            <div class="row">


                <div class="col-lg-6">

                    <div class="mb-3">

                        <label
                            for="minimum_length"
                            class="form-label"
                        >
                            Minimum Password Length
                        </label>


                        <div class="input-group">

                            <input
                                type="number"
                                id="minimum_length"
                                name="minimum_length"
                                class="form-control"
                                value="{{ old(
                                    'minimum_length',
                                    $policy->minimum_length
                                ) }}"
                                min="6"
                                max="128"
                                required
                            >

                            <span class="input-group-text">
                                Characters
                            </span>

                        </div>

                    </div>

                </div>



                <div class="col-lg-6">

                    <div class="mb-3">

                        <label
                            for="maximum_length"
                            class="form-label"
                        >
                            Maximum Password Length
                        </label>


                        <div class="input-group">

                            <input
                                type="number"
                                id="maximum_length"
                                name="maximum_length"
                                class="form-control"
                                value="{{ old(
                                    'maximum_length',
                                    $policy->maximum_length
                                ) }}"
                                min="6"
                                max="255"
                                required
                            >

                            <span class="input-group-text">
                                Characters
                            </span>

                        </div>

                    </div>

                </div>


            </div>

        </div>

    </div>



    {{-- =====================================================
         COMPLEXITY
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Password Complexity
            </h4>


            <p class="card-title-desc">
                Specify the character requirements for user passwords.
            </p>


            <div class="row">


                {{-- Uppercase --}}
                <div class="col-xl-3 col-md-6">

                    <div class="border rounded p-3 mb-3">

                        <input
                            type="hidden"
                            name="require_uppercase"
                            value="0"
                        >

                        <div class="form-check form-switch">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="require_uppercase"
                                name="require_uppercase"
                                value="1"
                                @checked(
                                    old(
                                        'require_uppercase',
                                        $policy->require_uppercase
                                    )
                                )
                            >

                            <label
                                class="form-check-label"
                                for="require_uppercase"
                            >
                                Uppercase Letter
                            </label>

                        </div>

                    </div>

                </div>



                {{-- Lowercase --}}
                <div class="col-xl-3 col-md-6">

                    <div class="border rounded p-3 mb-3">

                        <input
                            type="hidden"
                            name="require_lowercase"
                            value="0"
                        >

                        <div class="form-check form-switch">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="require_lowercase"
                                name="require_lowercase"
                                value="1"
                                @checked(
                                    old(
                                        'require_lowercase',
                                        $policy->require_lowercase
                                    )
                                )
                            >

                            <label
                                class="form-check-label"
                                for="require_lowercase"
                            >
                                Lowercase Letter
                            </label>

                        </div>

                    </div>

                </div>



                {{-- Number --}}
                <div class="col-xl-3 col-md-6">

                    <div class="border rounded p-3 mb-3">

                        <input
                            type="hidden"
                            name="require_number"
                            value="0"
                        >

                        <div class="form-check form-switch">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="require_number"
                                name="require_number"
                                value="1"
                                @checked(
                                    old(
                                        'require_number',
                                        $policy->require_number
                                    )
                                )
                            >

                            <label
                                class="form-check-label"
                                for="require_number"
                            >
                                Number
                            </label>

                        </div>

                    </div>

                </div>



                {{-- Special --}}
                <div class="col-xl-3 col-md-6">

                    <div class="border rounded p-3 mb-3">

                        <input
                            type="hidden"
                            name="require_special_character"
                            value="0"
                        >

                        <div class="form-check form-switch">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="require_special_character"
                                name="require_special_character"
                                value="1"
                                @checked(
                                    old(
                                        'require_special_character',
                                        $policy->require_special_character
                                    )
                                )
                            >

                            <label
                                class="form-check-label"
                                for="require_special_character"
                            >
                                Special Character
                            </label>

                        </div>

                    </div>

                </div>


            </div>

        </div>

    </div>



    {{-- =====================================================
         USER INFORMATION IN PASSWORD
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Personal Information Restrictions
            </h4>


            <p class="card-title-desc">

                Control whether users may include personal
                identifying information in their password.

            </p>


            <div class="row">


                <div class="col-lg-6">

                    <div class="border rounded p-3 mb-3">

                        <input
                            type="hidden"
                            name="allow_username_in_password"
                            value="0"
                        >

                        <div class="form-check form-switch">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="allow_username_in_password"
                                name="allow_username_in_password"
                                value="1"
                                @checked(
                                    old(
                                        'allow_username_in_password',
                                        $policy->allow_username_in_password
                                    )
                                )
                            >

                            <label
                                class="form-check-label"
                                for="allow_username_in_password"
                            >
                                Allow Username in Password
                            </label>

                        </div>

                    </div>

                </div>



                <div class="col-lg-6">

                    <div class="border rounded p-3 mb-3">

                        <input
                            type="hidden"
                            name="allow_name_in_password"
                            value="0"
                        >

                        <div class="form-check form-switch">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="allow_name_in_password"
                                name="allow_name_in_password"
                                value="1"
                                @checked(
                                    old(
                                        'allow_name_in_password',
                                        $policy->allow_name_in_password
                                    )
                                )
                            >

                            <label
                                class="form-check-label"
                                for="allow_name_in_password"
                            >
                                Allow Employee Name in Password
                            </label>

                        </div>

                    </div>

                </div>


            </div>

        </div>

    </div>



    {{-- =====================================================
         EXPIRY & HISTORY
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Password Expiry & History
            </h4>


            <div class="row">


                <div class="col-lg-4">

                    <div class="mb-3">

                        <label
                            for="password_expiry_days"
                            class="form-label"
                        >
                            Password Expiry
                        </label>


                        <div class="input-group">

                            <input
                                type="number"
                                id="password_expiry_days"
                                name="password_expiry_days"
                                class="form-control"
                                value="{{ old(
                                    'password_expiry_days',
                                    $policy->password_expiry_days
                                ) }}"
                                min="0"
                                required
                            >

                            <span class="input-group-text">
                                Days
                            </span>

                        </div>


                        <div class="form-text">
                            Use 0 for no expiry.
                        </div>

                    </div>

                </div>



                <div class="col-lg-4">

                    <div class="mb-3">

                        <label
                            for="expiry_warning_days"
                            class="form-label"
                        >
                            Expiry Warning
                        </label>


                        <div class="input-group">

                            <input
                                type="number"
                                id="expiry_warning_days"
                                name="expiry_warning_days"
                                class="form-control"
                                value="{{ old(
                                    'expiry_warning_days',
                                    $policy->expiry_warning_days
                                ) }}"
                                min="0"
                                required
                            >

                            <span class="input-group-text">
                                Days Before
                            </span>

                        </div>

                    </div>

                </div>



                <div class="col-lg-4">

                    <div class="mb-3">

                        <label
                            for="password_history_count"
                            class="form-label"
                        >
                            Password History
                        </label>


                        <div class="input-group">

                            <input
                                type="number"
                                id="password_history_count"
                                name="password_history_count"
                                class="form-control"
                                value="{{ old(
                                    'password_history_count',
                                    $policy->password_history_count
                                ) }}"
                                min="0"
                                max="50"
                                required
                            >

                            <span class="input-group-text">
                                Passwords
                            </span>

                        </div>

                    </div>

                </div>


                <div class="col-lg-4">

                    <div class="border rounded p-3 mt-2">

                        <input
                            type="hidden"
                            name="allow_password_reuse"
                            value="0"
                        >

                        <div class="form-check form-switch">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="allow_password_reuse"
                                name="allow_password_reuse"
                                value="1"
                                @checked(
                                    old(
                                        'allow_password_reuse',
                                        $policy->allow_password_reuse
                                    )
                                )
                            >

                            <label
                                class="form-check-label"
                                for="allow_password_reuse"
                            >
                                Allow Password Reuse
                            </label>

                        </div>

                    </div>

                </div>


            </div>

        </div>

    </div>



    {{-- =====================================================
         ACCOUNT LOCKOUT
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Account Lockout
            </h4>


            <p class="card-title-desc">

                Configure controls for repeated failed
                authentication attempts.

            </p>


            <div class="row">


                <div class="col-lg-6">

                    <div class="mb-3">

                        <label
                            for="maximum_login_attempts"
                            class="form-label"
                        >
                            Maximum Failed Attempts
                        </label>


                        <div class="input-group">

                            <input
                                type="number"
                                id="maximum_login_attempts"
                                name="maximum_login_attempts"
                                class="form-control"
                                value="{{ old(
                                    'maximum_login_attempts',
                                    $policy->maximum_login_attempts
                                ) }}"
                                min="1"
                                max="100"
                                required
                            >

                            <span class="input-group-text">
                                Attempts
                            </span>

                        </div>


                        <div class="form-text">

                            Account is locked when this
                            number of consecutive failed
                            login attempts is reached.

                        </div>

                    </div>

                </div>



                <div class="col-lg-6">

                    <div class="mb-3">

                        <label
                            for="account_lock_minutes"
                            class="form-label"
                        >
                            Lockout Duration
                        </label>


                        <div class="input-group">

                            <input
                                type="number"
                                id="account_lock_minutes"
                                name="account_lock_minutes"
                                class="form-control"
                                value="{{ old(
                                    'account_lock_minutes',
                                    $policy->account_lock_minutes
                                ) }}"
                                min="1"
                                max="10080"
                                required
                            >

                            <span class="input-group-text">
                                Minutes
                            </span>

                        </div>

                    </div>

                </div>


            </div>

        </div>

    </div>



    {{-- =====================================================
         TEMPORARY PASSWORDS
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <h4 class="header-title">
                Temporary Password & First Login
            </h4>


            <div class="row">


                <div class="col-lg-6">

                    <div class="mb-3">

                        <label
                            for="temporary_password_expiry_hours"
                            class="form-label"
                        >
                            Temporary Password Expiry
                        </label>


                        <div class="input-group">

                            <input
                                type="number"
                                id="temporary_password_expiry_hours"
                                name="temporary_password_expiry_hours"
                                class="form-control"
                                value="{{ old(
                                    'temporary_password_expiry_hours',
                                    $policy->temporary_password_expiry_hours
                                ) }}"
                                min="1"
                                max="720"
                                required
                            >

                            <span class="input-group-text">
                                Hours
                            </span>

                        </div>

                    </div>

                </div>



                <div class="col-lg-6">

                    <label class="form-label">
                        First Login
                    </label>


                    <div class="border rounded p-3">

                        <input
                            type="hidden"
                            name="force_first_login_change"
                            value="0"
                        >

                        <div class="form-check form-switch">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="force_first_login_change"
                                name="force_first_login_change"
                                value="1"
                                @checked(
                                    old(
                                        'force_first_login_change',
                                        $policy->force_first_login_change
                                    )
                                )
                            >

                            <label
                                class="form-check-label"
                                for="force_first_login_change"
                            >
                                Force Password Change on First Login
                            </label>

                        </div>

                    </div>

                </div>


            </div>

        </div>

    </div>



    {{-- =====================================================
         SAVE
    ====================================================== --}}

    <div class="card">

        <div class="card-body">

            <div
                class="d-flex flex-wrap justify-content-between align-items-center"
            >

                <div class="text-muted font-size-13">

                    Last updated:

                    <strong>

                        {{
                            $policy->updated_at
                                ? $policy->updated_at->format(
                                    'd M Y H:i'
                                )
                                : 'Not recorded'
                        }}

                    </strong>

                </div>


                @can(
                    'user-management.password-policies.update'
                )

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i
                            class="mdi mdi-content-save-outline me-1"
                        ></i>

                        Save Password Policy

                    </button>

                @endcan

            </div>

        </div>

    </div>

</form>

@endsection
@extends('layouts.app')

@section(
    'title',
    $requiredChange
        ? 'Change Password Required'
        : 'Change Password'
)

@section(
    'page-heading',
    $requiredChange
        ? 'Change Password Required'
        : 'Change Password'
)


@if(!$requiredChange)

    @section('page-actions')

        <a
            href="{{ route('profile.show') }}"
            class="btn btn-light"
        >
            <i class="mdi mdi-arrow-left me-1"></i>
            Back to Profile
        </a>

    @endsection

@endif


@section('content')


@php

    $minimumLength =
        (int) (
            $policy->minimum_length
            ?? $policy->min_length
            ?? $policy->password_min_length
            ?? 8
        );


    $requireUppercase =
        $policy->require_uppercase
        ?? $policy->uppercase_required
        ?? true;


    $requireLowercase =
        $policy->require_lowercase
        ?? $policy->lowercase_required
        ?? true;


    $requireNumber =
        $policy->require_number
        ?? $policy->require_numbers
        ?? $policy->number_required
        ?? true;


    $requireSpecial =
        $policy->require_special_character
        ?? $policy->require_special_characters
        ?? $policy->special_character_required
        ?? true;


    $expiryDays =
        $policy->password_expiry_days
        ?? $policy->expiry_days
        ?? 30;


    $historyCount =
        $policy->password_history_count
        ?? $policy->history_count
        ?? 5;

@endphp



@if($requiredChange)

    <div
        class="alert alert-warning"
        role="alert"
    >

        <div class="d-flex">

            <div class="me-3">

                <i
                    class="mdi mdi-shield-alert-outline font-size-24"
                ></i>

            </div>


            <div>

                <h5 class="alert-heading">
                    Password Change Required
                </h5>

                <p class="mb-0">

                    You must change your password before
                    continuing to use the LAPF Pension Fund System.

                </p>

            </div>

        </div>

    </div>

@endif



@if($errors->any())

    <div
        class="alert alert-danger alert-dismissible fade show"
        role="alert"
    >

        <h5 class="alert-heading">

            <i class="mdi mdi-alert-circle-outline me-1"></i>

            Password could not be changed

        </h5>


        <ul class="mb-0">

            @foreach(
                $errors->all()
                as $error
            )

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



<div class="row justify-content-center">

    <div class="col-xl-8 col-lg-9">

        <div class="card">

            <div class="card-body">


                <div class="text-center mb-4">

                    <div
                        class="avatar-lg mx-auto mb-3"
                    >

                        <span
                            class="avatar-title rounded-circle bg-soft-primary text-primary font-size-28"
                        >
                            <i class="mdi mdi-lock-reset"></i>
                        </span>

                    </div>


                    <h4>
                        Change Your Password
                    </h4>


                    <p class="text-muted mb-0">

                        Use a strong password that you
                        do not use on another system.

                    </p>

                </div>



                <form
                    method="POST"
                    action="{{
                        $requiredChange
                            ? route(
                                'password.required.update'
                            )
                            : route(
                                'password.change.update'
                            )
                    }}"
                    autocomplete="off"
                >

                    @csrf
                    @method('PUT')



                    {{-- Current Password --}}
                    <div class="mb-4">

                        <label
                            for="current_password"
                            class="form-label"
                        >
                            Current Password
                        </label>


                        <div class="input-group">

                            <input
                                type="password"
                                id="current_password"
                                name="current_password"
                                class="form-control"
                                autocomplete="current-password"
                                required
                            >


                            <button
                                type="button"
                                class="btn btn-light password-toggle"
                                data-target="current_password"
                            >
                                <i class="mdi mdi-eye-outline"></i>
                            </button>

                        </div>

                    </div>



                    {{-- New Password --}}
                    <div class="mb-4">

                        <label
                            for="password"
                            class="form-label"
                        >
                            New Password
                        </label>


                        <div class="input-group">

                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                autocomplete="new-password"
                                required
                            >


                            <button
                                type="button"
                                class="btn btn-light password-toggle"
                                data-target="password"
                            >
                                <i class="mdi mdi-eye-outline"></i>
                            </button>

                        </div>

                    </div>



                    {{-- Confirmation --}}
                    <div class="mb-4">

                        <label
                            for="password_confirmation"
                            class="form-label"
                        >
                            Confirm New Password
                        </label>


                        <div class="input-group">

                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                class="form-control"
                                autocomplete="new-password"
                                required
                            >


                            <button
                                type="button"
                                class="btn btn-light password-toggle"
                                data-target="password_confirmation"
                            >
                                <i class="mdi mdi-eye-outline"></i>
                            </button>

                        </div>

                    </div>



                    {{-- Password Policy --}}
                    <div
                        class="border rounded p-4 mb-4"
                    >

                        <h5 class="font-size-15 mb-3">

                            <i
                                class="mdi mdi-shield-check-outline me-1"
                            ></i>

                            Password Requirements

                        </h5>


                        <div class="row">

                            <div class="col-md-6">

                                <ul class="mb-md-0">

                                    <li>
                                        At least
                                        <strong>
                                            {{ $minimumLength }}
                                        </strong>
                                        characters
                                    </li>


                                    @if($requireUppercase)

                                        <li>
                                            At least one uppercase letter
                                        </li>

                                    @endif


                                    @if($requireLowercase)

                                        <li>
                                            At least one lowercase letter
                                        </li>

                                    @endif

                                </ul>

                            </div>


                            <div class="col-md-6">

                                <ul class="mb-0">

                                    @if($requireNumber)

                                        <li>
                                            At least one number
                                        </li>

                                    @endif


                                    @if($requireSpecial)

                                        <li>
                                            At least one special character
                                        </li>

                                    @endif


                                    @if($historyCount > 0)

                                        <li>
                                            Cannot reuse your last
                                            {{ $historyCount }}
                                            passwords
                                        </li>

                                    @endif


                                    @if($expiryDays > 0)

                                        <li>
                                            Password expires after
                                            {{ $expiryDays }}
                                            days
                                        </li>

                                    @endif

                                </ul>

                            </div>

                        </div>

                    </div>



                    {{-- Password Match Indicator --}}
                    <div
                        class="alert alert-light border"
                        id="password-match-message"
                        style="display:none;"
                    ></div>



                    <div
                        class="d-flex flex-wrap justify-content-end gap-2"
                    >

                        @if(!$requiredChange)

                            <a
                                href="{{ route('profile.show') }}"
                                class="btn btn-light"
                            >
                                Cancel
                            </a>

                        @endif


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >

                            <i
                                class="mdi mdi-content-save-outline me-1"
                            ></i>

                            Change Password

                        </button>

                    </div>


                </form>


            </div>

        </div>

    </div>

</div>

@endsection



@push('scripts')

<script>

$(document).ready(function () {


    /*
    |--------------------------------------------------------------------------
    | Show / Hide Password
    |--------------------------------------------------------------------------
    */

    $('.password-toggle').on(
        'click',
        function () {

            const targetId =
                $(this).data('target');


            const input =
                document.getElementById(
                    targetId
                );


            const icon =
                $(this).find('i');


            if (
                input.type
                === 'password'
            ) {

                input.type =
                    'text';


                icon.removeClass(
                    'mdi-eye-outline'
                );


                icon.addClass(
                    'mdi-eye-off-outline'
                );

            } else {

                input.type =
                    'password';


                icon.removeClass(
                    'mdi-eye-off-outline'
                );


                icon.addClass(
                    'mdi-eye-outline'
                );

            }

        }
    );



    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Indicator
    |--------------------------------------------------------------------------
    */

    function checkPasswordMatch() {

        const password =
            $('#password').val();


        const confirmation =
            $('#password_confirmation')
                .val();


        const message =
            $('#password-match-message');


        if (
            password === ''
            || confirmation === ''
        ) {

            message.hide();

            return;

        }


        if (
            password === confirmation
        ) {

            message
                .removeClass(
                    'alert-danger'
                )
                .addClass(
                    'alert-success'
                )
                .html(
                    '<i class="mdi mdi-check-circle-outline me-1"></i> Passwords match.'
                )
                .show();

        } else {

            message
                .removeClass(
                    'alert-success'
                )
                .addClass(
                    'alert-danger'
                )
                .html(
                    '<i class="mdi mdi-alert-circle-outline me-1"></i> Passwords do not match.'
                )
                .show();

        }

    }


    $('#password, #password_confirmation')
        .on(
            'keyup change',
            checkPasswordMatch
        );


});

</script>

@endpush
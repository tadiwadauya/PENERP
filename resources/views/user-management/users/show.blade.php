@extends('layouts.app')

@section('title', 'User Details')

@section('page-heading', 'User Details')


@section('page-actions')

    <div class="d-flex gap-2">

        <a
            href="{{ route('user-management.users.index') }}"
            class="btn btn-light"
        >
            <i class="mdi mdi-arrow-left me-1"></i>
            Back
        </a>


        @can('user-management.users.update')

            <a
                href="{{ route(
                    'user-management.users.edit',
                    $user
                ) }}"
                class="btn btn-primary"
            >
                <i class="mdi mdi-pencil-outline me-1"></i>
                Edit User
            </a>

        @endcan

    </div>

@endsection


@section('content')


{{-- =========================================================
     TEMPORARY PASSWORD
========================================================= --}}

@if(session('temporary_password'))

    <div
        class="alert alert-warning border-0"
        role="alert"
    >

        <div class="d-flex">

            <div class="me-3">

                <i
                    class="mdi mdi-key-variant font-size-24"
                ></i>

            </div>


            <div>

                <h5 class="alert-heading">
                    Temporary Password
                </h5>

                <p class="mb-2">
                    Copy this password now. For security reasons,
                    it will not be displayed again.
                </p>


                <div
                    class="bg-white text-dark border rounded px-3 py-2 d-inline-block"
                >

                    <code
                        class="font-size-16"
                        id="temporary-password-value"
                    >
                        {{ session('temporary_password') }}
                    </code>

                </div>


                <button
                    type="button"
                    class="btn btn-sm btn-warning ms-2"
                    onclick="copyTemporaryPassword()"
                >
                    <i class="mdi mdi-content-copy me-1"></i>
                    Copy
                </button>

            </div>

        </div>

    </div>

@endif



{{-- =========================================================
     USER SUMMARY
========================================================= --}}

<div class="row">

    <div class="col-12">

        <div class="card">

            <div class="card-body">


                <div
                    class="d-flex flex-column flex-md-row align-items-md-center"
                >

                    <div
                        class="avatar-lg me-md-4 mb-3 mb-md-0"
                    >

                        <span
                            class="avatar-title rounded-circle bg-soft-primary text-primary font-size-24"
                        >
                            {{ strtoupper(
                                substr(
                                    $user->first_name ?? '',
                                    0,
                                    1
                                )
                                .
                                substr(
                                    $user->surname ?? '',
                                    0,
                                    1
                                )
                            ) }}
                        </span>

                    </div>


                    <div class="flex-grow-1">

                        <h4 class="mb-1">
                            {{ $user->full_name }}
                        </h4>

                        <p class="text-muted mb-2">

                            {{ $user->jobTitle?->name ?? 'No Job Title' }}

                            @if($user->organisationUnit)

                                &bull;
                                {{ $user->organisationUnit->name }}

                            @endif

                        </p>


                        <div class="d-flex flex-wrap gap-2">

                            <span class="badge bg-soft-primary text-primary">
                                {{ $user->employee_number }}
                            </span>


                            @php

                                $statusClass = match($user->account_status) {
                                    'active' => 'success',
                                    'pending' => 'warning',
                                    'locked' => 'danger',
                                    'suspended' => 'warning',
                                    'disabled' => 'secondary',
                                    default => 'secondary',
                                };

                            @endphp


                            <span class="badge bg-{{ $statusClass }}">
                                {{ ucfirst($user->account_status) }}
                            </span>

                        </div>

                    </div>

                </div>


            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     DETAILS
========================================================= --}}

<div class="row">


    {{-- Account Information --}}
    <div class="col-xl-6">

        <div class="card h-100">

            <div class="card-body">

                <h4 class="header-title mb-4">
                    Account Information
                </h4>


                <div class="table-responsive">

                    <table class="table table-nowrap mb-0">

                        <tbody>

                            <tr>
                                <th width="40%">
                                    Username
                                </th>

                                <td>
                                    {{ $user->username }}
                                </td>
                            </tr>


                            <tr>
                                <th>
                                    Login Email
                                </th>

                                <td>
                                    {{ $user->email }}
                                </td>
                            </tr>


                            <tr>
                                <th>
                                    Work Email
                                </th>

                                <td>
                                    {{ $user->work_email ?? '-' }}
                                </td>
                            </tr>


                            <tr>
                                <th>
                                    Account Status
                                </th>

                                <td>
                                    {{ ucfirst($user->account_status) }}
                                </td>
                            </tr>


                            <tr>
                                <th>
                                    Last Login
                                </th>

                                <td>

                                    {{ $user->last_login_at
                                        ? $user->last_login_at
                                            ->format('d M Y H:i')
                                        : 'Never'
                                    }}

                                </td>
                            </tr>


                            <tr>
                                <th>
                                    Password Expires
                                </th>

                                <td>

                                    {{ $user->password_expires_at
                                        ? $user->password_expires_at
                                            ->format('d M Y H:i')
                                        : 'No expiry'
                                    }}

                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>



    {{-- Employment --}}
    <div class="col-xl-6 mt-4 mt-xl-0">

        <div class="card h-100">

            <div class="card-body">

                <h4 class="header-title mb-4">
                    Employment Information
                </h4>


                <div class="table-responsive">

                    <table class="table table-nowrap mb-0">

                        <tbody>

                            <tr>
                                <th width="40%">
                                    Job Title
                                </th>

                                <td>
                                    {{ $user->jobTitle?->name ?? '-' }}
                                </td>
                            </tr>


                            <tr>
                                <th>
                                    Grade
                                </th>

                                <td>
                                    {{ $user->grade?->name ?? '-' }}
                                </td>
                            </tr>


                            <tr>
                                <th>
                                    Department / Section
                                </th>

                                <td>
                                    {{ $user->organisationUnit?->name ?? '-' }}
                                </td>
                            </tr>


                            <tr>
                                <th>
                                    Reports To
                                </th>

                                <td>
                                    {{ $user->supervisor?->full_name ?? '-' }}
                                </td>
                            </tr>


                            <tr>
                                <th>
                                    Employment Status
                                </th>

                                <td>
                                    {{ ucfirst($user->employment_status) }}
                                </td>
                            </tr>


                            <tr>
                                <th>
                                    Employment Date
                                </th>

                                <td>

                                    {{ $user->employment_date
                                        ? $user->employment_date->format('d M Y')
                                        : '-'
                                    }}

                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     CONTACT
========================================================= --}}

<div class="row mt-4">

    <div class="col-12">

        <div class="card">

            <div class="card-body">

                <h4 class="header-title mb-4">
                    Contact Information
                </h4>


                <div class="row">


                    <div class="col-md-4">

                        <div class="mb-3 mb-md-0">

                            <span class="text-muted d-block mb-1">
                                Cell Number
                            </span>

                            <strong>
                                {{ $user->cell_number ?? '-' }}
                            </strong>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="mb-3 mb-md-0">

                            <span class="text-muted d-block mb-1">
                                Telephone Number
                            </span>

                            <strong>
                                {{ $user->telephone_number ?? '-' }}
                            </strong>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div>

                            <span class="text-muted d-block mb-1">
                                Extension
                            </span>

                            <strong>
                                {{ $user->phone_extension ?? '-' }}
                            </strong>

                        </div>

                    </div>


                </div>

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     ROLES & DASHBOARDS
========================================================= --}}

<div class="row">


    {{-- Roles --}}
    <div class="col-xl-6">

        <div class="card">

            <div class="card-body">

                <h4 class="header-title">
                    Assigned Roles
                </h4>

                <p class="card-title-desc">
                    Roles currently assigned to this user.
                </p>


                @forelse($user->roles as $role)

                    <span
                        class="badge bg-soft-primary text-primary font-size-13 me-1 mb-2"
                    >

                        {{ $role->display_name ?: $role->name }}

                    </span>

                @empty

                    <span class="text-muted">
                        No roles assigned.
                    </span>

                @endforelse

            </div>

        </div>

    </div>



    {{-- Dashboards --}}
    <div class="col-xl-6">

        <div class="card">

            <div class="card-body">

                <h4 class="header-title">
                    Dashboard Access
                </h4>

                <p class="card-title-desc">
                    Dashboards this user is authorised to access.
                </p>


                @forelse($user->dashboards as $dashboard)

                    <span
                        class="badge bg-soft-info text-info font-size-13 me-1 mb-2"
                    >

                        {{ $dashboard->name }}

                        @if($dashboard->pivot->is_default)

                            <i
                                class="mdi mdi-star ms-1"
                                title="Default Dashboard"
                            ></i>

                        @endif

                    </span>

                @empty

                    <span class="text-muted">
                        No dashboard access assigned.
                    </span>

                @endforelse

            </div>

        </div>

    </div>


</div>



{{-- =========================================================
     SECURITY ACTIONS
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title">
            Security Actions
        </h4>

        <p class="card-title-desc">
            Administrative account and password controls.
        </p>


        <div class="row">


            {{-- Password Reset --}}
            @can('user-management.users.reset-password')

                <div class="col-xl-7">

                    <div class="border rounded p-4">

                        <h5 class="font-size-15 mb-3">
                            Reset Password
                        </h5>


                        <form
                            method="POST"
                            action="{{ route(
                                'user-management.users.reset-password',
                                $user
                            ) }}"
                        >

                            @csrf
                            @method('PUT')


                            <div class="row">

                                <div class="col-lg-6">

                                    <div class="mb-3">

                                        <label
                                            for="password_option"
                                            class="form-label"
                                        >
                                            Reset Method
                                        </label>

                                        <select
                                            name="password_option"
                                            id="password_option"
                                            class="form-select"
                                        >

                                            <option value="generate">
                                                Generate Automatically
                                            </option>

                                            <option value="manual">
                                                Enter Manually
                                            </option>

                                        </select>

                                    </div>

                                </div>


                                <div class="col-lg-6">

                                    <div class="mb-3">

                                        <label
                                            for="temporary_password"
                                            class="form-label"
                                        >
                                            Manual Temporary Password
                                        </label>

                                        <input
                                            type="password"
                                            id="temporary_password"
                                            name="temporary_password"
                                            class="form-control"
                                            autocomplete="new-password"
                                        >

                                    </div>

                                </div>

                            </div>


                            <button
                                type="submit"
                                class="btn btn-warning"
                            >
                                <i class="mdi mdi-key-variant me-1"></i>
                                Reset Password
                            </button>

                        </form>

                    </div>

                </div>

            @endcan



            {{-- Account Activation --}}
            <div class="col-xl-5 mt-4 mt-xl-0">

                <div class="border rounded p-4">

                    <h5 class="font-size-15 mb-3">
                        Account Status
                    </h5>


                    @if($user->is_active)

                        <p class="text-muted">
                            This account is currently active.
                        </p>


                        @can('user-management.users.deactivate')

                            <form
                                method="POST"
                                action="{{ route(
                                    'user-management.users.deactivate',
                                    $user
                                ) }}"
                                onsubmit="
                                    return confirm(
                                        'Are you sure you want to deactivate this user?'
                                    );
                                "
                            >

                                @csrf
                                @method('PATCH')


                                <button
                                    type="submit"
                                    class="btn btn-danger"
                                >
                                    <i class="mdi mdi-account-off-outline me-1"></i>
                                    Deactivate Account
                                </button>

                            </form>

                        @endcan


                    @else

                        <p class="text-muted">
                            This account is currently inactive.
                        </p>


                        @can('user-management.users.activate')

                            <form
                                method="POST"
                                action="{{ route(
                                    'user-management.users.activate',
                                    $user
                                ) }}"
                            >

                                @csrf
                                @method('PATCH')


                                <button
                                    type="submit"
                                    class="btn btn-success"
                                >
                                    <i class="mdi mdi-account-check-outline me-1"></i>
                                    Activate Account
                                </button>

                            </form>

                        @endcan

                    @endif

                </div>

            </div>

        </div>

    </div>

</div>

@endsection


@push('scripts')

<script>

function copyTemporaryPassword() {

    const element =
        document.getElementById(
            'temporary-password-value'
        );

    if (!element) {
        return;
    }

    navigator.clipboard.writeText(
        element.innerText.trim()
    );

}

</script>

@endpush
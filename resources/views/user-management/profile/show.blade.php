@extends('layouts.app')

@section('title', 'My Profile')

@section('page-heading', 'My Profile')


@section('page-actions')

    <a
        href="{{ route('password.change') }}"
        class="btn btn-primary"
    >
        <i class="mdi mdi-lock-reset me-1"></i>
        Change Password
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


{{-- =========================================================
     PROFILE HEADER
========================================================= --}}

<div class="row">

    <div class="col-12">

        <div class="card">

            <div class="card-body">

                <div
                    class="d-flex flex-column flex-md-row align-items-md-center"
                >

                    <div
                        class="avatar-xl me-md-4 mb-3 mb-md-0"
                    >

                        <span
                            class="avatar-title rounded-circle bg-soft-primary text-primary font-size-32"
                        >
                            {{
                                strtoupper(
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
                                )
                            }}
                        </span>

                    </div>


                    <div class="flex-grow-1">

                        <h3 class="mb-1">

                            {{ $user->full_name }}

                        </h3>


                        <p class="text-muted mb-2">

                            {{
                                $user->jobTitle?->name
                                ?? 'No Job Title'
                            }}

                            @if($user->organisationUnit)

                                &bull;

                                {{
                                    $user
                                        ->organisationUnit
                                        ->name
                                }}

                            @endif

                        </p>


                        <div class="d-flex flex-wrap gap-2">

                            <span
                                class="badge bg-soft-primary text-primary"
                            >
                                Employee:
                                {{ $user->employee_number }}
                            </span>


                            @if($user->is_active)

                                <span
                                    class="badge bg-soft-success text-success"
                                >
                                    Active Account
                                </span>

                            @else

                                <span
                                    class="badge bg-soft-danger text-danger"
                                >
                                    Inactive Account
                                </span>

                            @endif


                            <span
                                class="badge bg-soft-info text-info"
                            >
                                {{
                                    ucfirst(
                                        $user->employment_status
                                    )
                                }}
                            </span>

                        </div>

                    </div>


                    <div class="mt-3 mt-md-0">

                        <a
                            href="{{ route('password.change') }}"
                            class="btn btn-primary"
                        >
                            <i
                                class="mdi mdi-lock-reset me-1"
                            ></i>

                            Change Password
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     PERSONAL / CONTACT DETAILS
========================================================= --}}

<div class="row">


    <div class="col-xl-6">

        <div class="card h-100">

            <div class="card-body">

                <h4 class="header-title mb-4">
                    Personal Information
                </h4>


                <div class="table-responsive">

                    <table
                        class="table table-nowrap mb-0"
                    >

                        <tbody>

                            <tr>

                                <th width="40%">
                                    Employee Number
                                </th>

                                <td>
                                    {{ $user->employee_number }}
                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Title
                                </th>

                                <td>
                                    {{ $user->title ?? '-' }}
                                </td>

                            </tr>


                            <tr>

                                <th>
                                    First Name
                                </th>

                                <td>
                                    {{ $user->first_name }}
                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Middle Name
                                </th>

                                <td>
                                    {{ $user->middle_name ?? '-' }}
                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Surname
                                </th>

                                <td>
                                    {{ $user->surname }}
                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Username
                                </th>

                                <td>
                                    {{ $user->username }}
                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>



    <div class="col-xl-6 mt-4 mt-xl-0">

        <div class="card h-100">

            <div class="card-body">

                <h4 class="header-title mb-4">
                    Contact Information
                </h4>


                <div class="table-responsive">

                    <table
                        class="table table-nowrap mb-0"
                    >

                        <tbody>


                            <tr>

                                <th width="40%">
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
                                    Cell Number
                                </th>

                                <td>
                                    {{ $user->cell_number ?? '-' }}
                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Telephone Number
                                </th>

                                <td>
                                    {{
                                        $user->telephone_number
                                        ?? '-'
                                    }}
                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Extension
                                </th>

                                <td>
                                    {{
                                        $user->phone_extension
                                        ?? '-'
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
     EMPLOYMENT INFORMATION
========================================================= --}}

<div class="row mt-4">

    <div class="col-12">

        <div class="card">

            <div class="card-body">

                <h4 class="header-title mb-4">
                    Employment & Organisation
                </h4>


                <div class="row">


                    <div class="col-lg-4 col-md-6">

                        <div class="mb-4">

                            <span
                                class="text-muted d-block mb-1"
                            >
                                Job Title
                            </span>

                            <strong>
                                {{
                                    $user->jobTitle?->name
                                    ?? '-'
                                }}
                            </strong>

                        </div>

                    </div>


                    <div class="col-lg-4 col-md-6">

                        <div class="mb-4">

                            <span
                                class="text-muted d-block mb-1"
                            >
                                Grade
                            </span>

                            <strong>
                                {{
                                    $user->grade?->name
                                    ?? '-'
                                }}
                            </strong>

                        </div>

                    </div>


                    <div class="col-lg-4 col-md-6">

                        <div class="mb-4">

                            <span
                                class="text-muted d-block mb-1"
                            >
                                Department / Section
                            </span>

                            <strong>
                                {{
                                    $user
                                        ->organisationUnit
                                        ?->name
                                    ?? '-'
                                }}
                            </strong>

                        </div>

                    </div>


                    <div class="col-lg-4 col-md-6">

                        <div class="mb-4">

                            <span
                                class="text-muted d-block mb-1"
                            >
                                Reports To
                            </span>

                            <strong>
                                {{
                                    $user
                                        ->supervisor
                                        ?->full_name
                                    ?? '-'
                                }}
                            </strong>

                        </div>

                    </div>


                    <div class="col-lg-4 col-md-6">

                        <div class="mb-4">

                            <span
                                class="text-muted d-block mb-1"
                            >
                                Employment Date
                            </span>

                            <strong>

                                {{
                                    $user->employment_date
                                        ? $user
                                            ->employment_date
                                            ->format('d M Y')
                                        : '-'
                                }}

                            </strong>

                        </div>

                    </div>


                    <div class="col-lg-4 col-md-6">

                        <div class="mb-4">

                            <span
                                class="text-muted d-block mb-1"
                            >
                                Employment Status
                            </span>

                            <strong>
                                {{
                                    ucfirst(
                                        $user->employment_status
                                    )
                                }}
                            </strong>

                        </div>

                    </div>


                </div>

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     ACCESS
========================================================= --}}

<div class="row">


    {{-- Roles --}}
    <div class="col-xl-6">

        <div class="card">

            <div class="card-body">

                <h4 class="header-title">
                    My Roles
                </h4>

                <p class="card-title-desc">
                    Roles assigned to your system account.
                </p>


                @forelse($user->roles as $role)

                    <span
                        class="badge bg-soft-primary text-primary font-size-13 me-1 mb-2"
                    >
                        {{
                            \Illuminate\Support\Str::of(
                                $role->name
                            )
                                ->replace('-', ' ')
                                ->replace('_', ' ')
                                ->title()
                        }}
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
                    Dashboards you are authorised to access.
                </p>


                @forelse(
                    $user->dashboards
                    as $dashboard
                )

                    <span
                        class="badge bg-soft-info text-info font-size-13 me-1 mb-2"
                    >

                        {{ $dashboard->name }}


                        @if(
                            $dashboard
                                ->pivot
                                ->is_default
                        )

                            <i
                                class="mdi mdi-star ms-1"
                                title="Default Dashboard"
                            ></i>

                        @endif

                    </span>

                @empty

                    <span class="text-muted">
                        No dashboards assigned.
                    </span>

                @endforelse

            </div>

        </div>

    </div>


</div>



{{-- =========================================================
     SECURITY INFORMATION
========================================================= --}}

<div class="card">

    <div class="card-body">

        <div
            class="d-flex flex-wrap justify-content-between align-items-center mb-4"
        >

            <div>

                <h4 class="header-title mb-1">
                    Account Security
                </h4>

                <p class="card-title-desc mb-0">
                    Security information for your account.
                </p>

            </div>


            <a
                href="{{ route('password.change') }}"
                class="btn btn-primary mt-3 mt-md-0"
            >
                <i class="mdi mdi-lock-reset me-1"></i>
                Change Password
            </a>

        </div>


        <div class="row">


            <div class="col-lg-3 col-md-6">

                <div class="border rounded p-3 mb-3">

                    <span class="text-muted d-block mb-2">
                        Last Login
                    </span>


                    <strong>

                        {{
                            $user->last_login_at
                                ? $user
                                    ->last_login_at
                                    ->format(
                                        'd M Y H:i'
                                    )
                                : 'Never'
                        }}

                    </strong>

                </div>

            </div>


            <div class="col-lg-3 col-md-6">

                <div class="border rounded p-3 mb-3">

                    <span class="text-muted d-block mb-2">
                        Password Changed
                    </span>


                    <strong>

                        {{
                            $user->password_changed_at
                                ? $user
                                    ->password_changed_at
                                    ->format(
                                        'd M Y H:i'
                                    )
                                : 'Not recorded'
                        }}

                    </strong>

                </div>

            </div>


            <div class="col-lg-3 col-md-6">

                <div class="border rounded p-3 mb-3">

                    <span class="text-muted d-block mb-2">
                        Password Expires
                    </span>


                    <strong>

                        {{
                            $user->password_expires_at
                                ? $user
                                    ->password_expires_at
                                    ->format(
                                        'd M Y H:i'
                                    )
                                : 'No expiry'
                        }}

                    </strong>

                </div>

            </div>


            <div class="col-lg-3 col-md-6">

                <div class="border rounded p-3 mb-3">

                    <span class="text-muted d-block mb-2">
                        Account Status
                    </span>


                    @if($user->is_active)

                        <span
                            class="badge bg-success"
                        >
                            Active
                        </span>

                    @else

                        <span
                            class="badge bg-danger"
                        >
                            Inactive
                        </span>

                    @endif

                </div>

            </div>


        </div>

    </div>

</div>

@endsection
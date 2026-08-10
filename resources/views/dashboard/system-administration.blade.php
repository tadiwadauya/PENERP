@extends('layouts.app')

@section('title', 'System Administration')

@section('page-heading', 'System Administration')

@section('content')


{{-- =========================================================
     INTRODUCTION
========================================================= --}}

<div class="row">

    <div class="col-12">

        <div class="card">

            <div class="card-body">

                <div
                    class="
                        d-flex
                        flex-wrap
                        justify-content-between
                        align-items-center
                    "
                >

                    <div>

                        <h4 class="header-title">
                            System Administration
                        </h4>

                        <p class="text-muted mb-0">
                            Manage users, organisational structure,
                            security, access control, audit information
                            and ERP modules.
                        </p>

                    </div>


                    <div class="mt-3 mt-md-0">

                        <a
                            href="{{ route('dashboard') }}"
                            class="btn btn-light"
                        >

                            <i
                                class="
                                    mdi
                                    mdi-view-dashboard-outline
                                    me-1
                                "
                            ></i>

                            Main Dashboard

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     MODULES
========================================================= --}}

<div class="row mt-2">

    <div class="col-12">

        <h4 class="header-title mb-3">
            Modules
        </h4>

    </div>



    {{-- Pensions Administration --}}
    <div class="col-xl-3 col-md-6">

        <a
            href="{{ route(
                'pensions-administration.dashboard'
            ) }}"
            class="text-decoration-none"
        >

            <div class="card">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="avatar-sm me-3">

                            <span
                                class="
                                    avatar-title
                                    rounded-circle
                                    bg-soft-primary
                                    text-primary
                                "
                            >

                                <i
                                    class="
                                        mdi
                                        mdi-account-group-outline
                                        font-size-22
                                    "
                                ></i>

                            </span>

                        </div>


                        <div>

                            <h5 class="font-size-16 mb-1">
                                Pensions Administration
                            </h5>

                            <p class="text-muted mb-0">
                                Pensions module
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </a>

    </div>



    {{-- Updates / Membership --}}
    <div class="col-xl-3 col-md-6">

        <a
            href="{{ route(
                'pensions-administration.updates.dashboard'
            ) }}"
            class="text-decoration-none"
        >

            <div class="card">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="avatar-sm me-3">

                            <span
                                class="
                                    avatar-title
                                    rounded-circle
                                    bg-soft-success
                                    text-success
                                "
                            >

                                <i
                                    class="
                                        mdi
                                        mdi-account-multiple-outline
                                        font-size-22
                                    "
                                ></i>

                            </span>

                        </div>


                        <div>

                            <h5 class="font-size-16 mb-1">
                                Updates / Membership
                            </h5>

                            <p class="text-muted mb-0">
                                Members & employers
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </a>

    </div>



    {{-- Pension Payroll --}}
    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="
                                avatar-title
                                rounded-circle
                                bg-soft-info
                                text-info
                            "
                        >

                            <i
                                class="
                                    mdi
                                    mdi-cash-multiple
                                    font-size-22
                                "
                            ></i>

                        </span>

                    </div>


                    <div>

                        <h5 class="font-size-16 mb-1">
                            Pension Payroll
                        </h5>

                        <p class="text-muted mb-0">
                            Coming next
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>



    {{-- Benefit Claims --}}
    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="
                                avatar-title
                                rounded-circle
                                bg-soft-warning
                                text-warning
                            "
                        >

                            <i
                                class="
                                    mdi
                                    mdi-file-document-outline
                                    font-size-22
                                "
                            ></i>

                        </span>

                    </div>


                    <div>

                        <h5 class="font-size-16 mb-1">
                            Benefit Claims
                        </h5>

                        <p class="text-muted mb-0">
                            Planned later
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>



    {{-- Document Management --}}
    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="
                                avatar-title
                                rounded-circle
                                bg-soft-secondary
                                text-secondary
                            "
                        >

                            <i
                                class="
                                    mdi
                                    mdi-folder-multiple-outline
                                    font-size-22
                                "
                            ></i>

                        </span>

                    </div>


                    <div>

                        <h5 class="font-size-16 mb-1">
                            Document Management
                        </h5>

                        <p class="text-muted mb-0">
                            Future module
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>



    {{-- Property Management --}}
    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="
                                avatar-title
                                rounded-circle
                                bg-soft-danger
                                text-danger
                            "
                        >

                            <i
                                class="
                                    mdi
                                    mdi-office-building-outline
                                    font-size-22
                                "
                            ></i>

                        </span>

                    </div>


                    <div>

                        <h5 class="font-size-16 mb-1">
                            Property Management
                        </h5>

                        <p class="text-muted mb-0">
                            Future module
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     USER MANAGEMENT
========================================================= --}}

<div class="row mt-4">

    <div class="col-12">

        <h4 class="header-title mb-3">
            User Management
        </h4>

    </div>



    {{-- Users --}}
    @if(
        auth()->user()->is_system_administrator
        ||
        auth()->user()->can(
            'user-management.users.view'
        )
    )

        <div class="col-xl-3 col-md-6">

            <a
                href="{{ route(
                    'user-management.users.index'
                ) }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="
                                        avatar-title
                                        rounded-circle
                                        bg-soft-primary
                                        text-primary
                                    "
                                >

                                    <i
                                        class="
                                            mdi
                                            mdi-account-group
                                            font-size-22
                                        "
                                    ></i>

                                </span>

                            </div>


                            <div>

                                <h5 class="font-size-16 mb-1">
                                    Users
                                </h5>

                                <p class="text-muted mb-0">
                                    Manage system users
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

    @endif



    {{-- Roles --}}
    @if(
        auth()->user()->is_system_administrator
        ||
        auth()->user()->can(
            'user-management.roles.view'
        )
    )

        <div class="col-xl-3 col-md-6">

            <a
                href="{{ route(
                    'user-management.roles.index'
                ) }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="
                                        avatar-title
                                        rounded-circle
                                        bg-soft-success
                                        text-success
                                    "
                                >

                                    <i
                                        class="
                                            mdi
                                            mdi-account-key
                                            font-size-22
                                        "
                                    ></i>

                                </span>

                            </div>


                            <div>

                                <h5 class="font-size-16 mb-1">
                                    Roles
                                </h5>

                                <p class="text-muted mb-0">
                                    Manage system roles
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

    @endif



    {{-- Permissions --}}
    @if(
        auth()->user()->is_system_administrator
        ||
        auth()->user()->can(
            'user-management.permissions.view'
        )
    )

        <div class="col-xl-3 col-md-6">

            <a
                href="{{ route(
                    'user-management.permissions.index'
                ) }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="
                                        avatar-title
                                        rounded-circle
                                        bg-soft-warning
                                        text-warning
                                    "
                                >

                                    <i
                                        class="
                                            mdi
                                            mdi-shield-key
                                            font-size-22
                                        "
                                    ></i>

                                </span>

                            </div>


                            <div>

                                <h5 class="font-size-16 mb-1">
                                    Permissions
                                </h5>

                                <p class="text-muted mb-0">
                                    Manage access permissions
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

    @endif



    {{-- Organisation Structure --}}
    @if(
        auth()->user()->is_system_administrator
        ||
        auth()->user()->can(
            'user-management.organisation-units.view'
        )
    )

        <div class="col-xl-3 col-md-6">

            <a
                href="{{ route(
                    'user-management.organisation-units.index'
                ) }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="
                                        avatar-title
                                        rounded-circle
                                        bg-soft-info
                                        text-info
                                    "
                                >

                                    <i
                                        class="
                                            mdi
                                            mdi-sitemap
                                            font-size-22
                                        "
                                    ></i>

                                </span>

                            </div>


                            <div>

                                <h5 class="font-size-16 mb-1">
                                    Organisation
                                </h5>

                                <p class="text-muted mb-0">
                                    Departments & sections
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

    @endif



    {{-- Job Titles --}}
    @if(
        auth()->user()->is_system_administrator
        ||
        auth()->user()->can(
            'user-management.job-titles.view'
        )
    )

        <div class="col-xl-3 col-md-6">

            <a
                href="{{ route(
                    'user-management.job-titles.index'
                ) }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="
                                        avatar-title
                                        rounded-circle
                                        bg-soft-primary
                                        text-primary
                                    "
                                >

                                    <i
                                        class="
                                            mdi
                                            mdi-briefcase-outline
                                            font-size-22
                                        "
                                    ></i>

                                </span>

                            </div>


                            <div>

                                <h5 class="font-size-16 mb-1">
                                    Job Titles
                                </h5>

                                <p class="text-muted mb-0">
                                    Maintain job titles
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

    @endif



    {{-- Grades --}}
    @if(
        auth()->user()->is_system_administrator
        ||
        auth()->user()->can(
            'user-management.grades.view'
        )
    )

        <div class="col-xl-3 col-md-6">

            <a
                href="{{ route(
                    'user-management.grades.index'
                ) }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="
                                        avatar-title
                                        rounded-circle
                                        bg-soft-success
                                        text-success
                                    "
                                >

                                    <i
                                        class="
                                            mdi
                                            mdi-chart-bar
                                            font-size-22
                                        "
                                    ></i>

                                </span>

                            </div>


                            <div>

                                <h5 class="font-size-16 mb-1">
                                    Grades
                                </h5>

                                <p class="text-muted mb-0">
                                    Maintain grade structure
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

    @endif

</div>



{{-- =========================================================
     SECURITY & ACCESS
========================================================= --}}

<div class="row mt-4">

    <div class="col-12">

        <h4 class="header-title mb-3">
            Security & Access
        </h4>

    </div>



    {{-- Password Policy --}}
    @if(
        auth()->user()->is_system_administrator
        ||
        auth()->user()->can(
            'user-management.password-policies.view'
        )
    )

        <div class="col-xl-3 col-md-6">

            <a
                href="{{ route(
                    'user-management.password-policies.edit'
                ) }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="
                                        avatar-title
                                        rounded-circle
                                        bg-soft-warning
                                        text-warning
                                    "
                                >

                                    <i
                                        class="
                                            mdi
                                            mdi-lock-outline
                                            font-size-22
                                        "
                                    ></i>

                                </span>

                            </div>


                            <div>

                                <h5 class="font-size-16 mb-1">
                                    Password Policy
                                </h5>

                                <p class="text-muted mb-0">
                                    Security policy
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

    @endif



    {{-- Audit Trail --}}
    @if(
        auth()->user()->is_system_administrator
        ||
        auth()->user()->can(
            'audit.audit-trails.view'
        )
    )

        <div class="col-xl-3 col-md-6">

            <a
                href="{{ route(
                    'audit.audit-trails.index'
                ) }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="
                                        avatar-title
                                        rounded-circle
                                        bg-soft-info
                                        text-info
                                    "
                                >

                                    <i
                                        class="
                                            mdi
                                            mdi-history
                                            font-size-22
                                        "
                                    ></i>

                                </span>

                            </div>


                            <div>

                                <h5 class="font-size-16 mb-1">
                                    Audit Trail
                                </h5>

                                <p class="text-muted mb-0">
                                    Review system activity
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

    @endif



    {{-- User Sessions --}}
    @if(
        auth()->user()->is_system_administrator
        ||
        auth()->user()->can(
            'audit.user-sessions.view'
        )
    )

        <div class="col-xl-3 col-md-6">

            <a
                href="{{ route(
                    'audit.user-sessions.index'
                ) }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="
                                        avatar-title
                                        rounded-circle
                                        bg-soft-primary
                                        text-primary
                                    "
                                >

                                    <i
                                        class="
                                            mdi
                                            mdi-monitor
                                            font-size-22
                                        "
                                    ></i>

                                </span>

                            </div>


                            <div>

                                <h5 class="font-size-16 mb-1">
                                    User Sessions
                                </h5>

                                <p class="text-muted mb-0">
                                    Review active sessions
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

    @endif



    {{-- Login Attempts --}}
    @if(
        auth()->user()->is_system_administrator
        ||
        auth()->user()->can(
            'audit.login-attempts.view'
        )
    )

        <div class="col-xl-3 col-md-6">

            <a
                href="{{ route(
                    'audit.login-attempts.index'
                ) }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="
                                        avatar-title
                                        rounded-circle
                                        bg-soft-danger
                                        text-danger
                                    "
                                >

                                    <i
                                        class="
                                            mdi
                                            mdi-shield-alert-outline
                                            font-size-22
                                        "
                                    ></i>

                                </span>

                            </div>


                            <div>

                                <h5 class="font-size-16 mb-1">
                                    Login Attempts
                                </h5>

                                <p class="text-muted mb-0">
                                    Authentication activity
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

    @endif

</div>


@endsection
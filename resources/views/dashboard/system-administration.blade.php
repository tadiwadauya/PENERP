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
                            security policies, access control and
                            system audit information.

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
     ERP MODULE NAVIGATION
========================================================= --}}

@can('dashboard.pensions.view')

    <div class="row">

        <div class="col-12">

            <h4 class="header-title mb-3">
                ERP Navigation
            </h4>

        </div>


        <div class="col-xl-4 col-md-6">

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


                            <div class="flex-grow-1">

                                <h5 class="font-size-16 mb-1">
                                    Pensions Administration
                                </h5>

                                <p class="text-muted mb-0">
                                    Membership, payroll and claims
                                </p>

                            </div>


                            <div>

                                <i
                                    class="
                                        mdi
                                        mdi-chevron-right
                                        font-size-20
                                        text-muted
                                    "
                                ></i>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>


        @can('updates.members.view')

            <div class="col-xl-4 col-md-6">

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


                                <div class="flex-grow-1">

                                    <h5 class="font-size-16 mb-1">
                                        Updates / Membership
                                    </h5>

                                    <p class="text-muted mb-0">
                                        Open Updates Dashboard
                                    </p>

                                </div>


                                <i
                                    class="
                                        mdi
                                        mdi-chevron-right
                                        text-muted
                                    "
                                ></i>

                            </div>

                        </div>

                    </div>

                </a>

            </div>

        @endcan

    </div>

@endcan



{{-- =========================================================
     USER MANAGEMENT
========================================================= --}}

<div class="row">

    <div class="col-12">

        <h4 class="header-title mb-3">
            User Management
        </h4>

    </div>



    {{-- Users --}}
    @can('user-management.users.view')

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

    @endcan



    {{-- Roles --}}
    @can('user-management.roles.view')

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
                                    Manage access roles
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

    @endcan



    {{-- Permissions --}}
    @can('user-management.permissions.view')

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
                                    Review access permissions
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

    @endcan



    {{-- Organisation --}}
    @can('user-management.organisation-units.view')

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

    @endcan



    {{-- Job Titles --}}
    @can('user-management.job-titles.view')

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
                                    Maintain positions
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

    @endcan



    {{-- Grades --}}
    @can('user-management.grades.view')

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

    @endcan

</div>



{{-- =========================================================
     SECURITY
========================================================= --}}

<div class="row mt-2">

    <div class="col-12">

        <h4 class="header-title mb-3">
            Security & Access
        </h4>

    </div>



    {{-- Password Policy --}}
    @can(
        'user-management.password-policies.view'
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

    @endcan



    {{-- Audit Trail --}}
    @can('audit.audit-trails.view')

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

    @endcan



    {{-- User Sessions --}}
    @can('audit.user-sessions.view')

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
                                    Review sessions
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

    @endcan



    {{-- Login Attempts --}}
    @can('audit.login-attempts.view')

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

    @endcan

</div>


@endsection
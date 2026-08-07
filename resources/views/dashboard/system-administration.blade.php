@extends('layouts.app')


@section('title', 'System Administration')


@section('page-heading', 'System Administration')


@section('content')


{{-- Introduction --}}
<div class="row">

    <div class="col-12">

        <div class="card">

            <div class="card-body">

                <h4 class="header-title">
                    System Administration
                </h4>

                <p class="text-muted mb-0">

                    Manage users, organisational structure,
                    security policies, access control and
                    system audit information.

                </p>

            </div>

        </div>

    </div>

</div>



{{-- User Management --}}
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
                href="{{ route('user-management.users.index') }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="avatar-title
                                           rounded-circle
                                           bg-soft-primary
                                           text-primary"
                                >

                                    <i
                                        class="mdi mdi-account-group
                                               font-size-22"
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
                href="{{ route('user-management.roles.index') }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="avatar-title
                                           rounded-circle
                                           bg-soft-success
                                           text-success"
                                >

                                    <i
                                        class="mdi mdi-account-key
                                               font-size-22"
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
                href="{{ route('user-management.permissions.index') }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="avatar-title
                                           rounded-circle
                                           bg-soft-warning
                                           text-warning"
                                >

                                    <i
                                        class="mdi mdi-shield-key
                                               font-size-22"
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
                href="{{ route('user-management.organisation-units.index') }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="avatar-title
                                           rounded-circle
                                           bg-soft-info
                                           text-info"
                                >

                                    <i
                                        class="mdi mdi-sitemap
                                               font-size-22"
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
                href="{{ route('user-management.job-titles.index') }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="avatar-title
                                           rounded-circle
                                           bg-soft-primary
                                           text-primary"
                                >

                                    <i
                                        class="mdi mdi-briefcase-outline
                                               font-size-22"
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
                href="{{ route('user-management.grades.index') }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="avatar-title
                                           rounded-circle
                                           bg-soft-success
                                           text-success"
                                >

                                    <i
                                        class="mdi mdi-chart-bar
                                               font-size-22"
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



{{-- Security --}}
<div class="row mt-2">

    <div class="col-12">

        <h4 class="header-title mb-3">
            Security & Access
        </h4>

    </div>


    {{-- Password Policy --}}
    @can('user-management.password-policies.view')

        <div class="col-xl-3 col-md-6">

            <a
                href="{{ route('user-management.password-policies.edit') }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="avatar-title
                                           rounded-circle
                                           bg-soft-warning
                                           text-warning"
                                >

                                    <i
                                        class="mdi mdi-lock-outline
                                               font-size-22"
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
                href="{{ route('audit.audit-trails.index') }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="avatar-title
                                           rounded-circle
                                           bg-soft-info
                                           text-info"
                                >

                                    <i
                                        class="mdi mdi-history
                                               font-size-22"
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



    {{-- Sessions --}}
    @can('audit.user-sessions.view')

        <div class="col-xl-3 col-md-6">

            <a
                href="{{ route('audit.user-sessions.index') }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="avatar-title
                                           rounded-circle
                                           bg-soft-primary
                                           text-primary"
                                >

                                    <i
                                        class="mdi mdi-monitor
                                               font-size-22"
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
                href="{{ route('audit.login-attempts.index') }}"
                class="text-decoration-none"
            >

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex align-items-center">

                            <div class="avatar-sm me-3">

                                <span
                                    class="avatar-title
                                           rounded-circle
                                           bg-soft-danger
                                           text-danger"
                                >

                                    <i
                                        class="mdi mdi-shield-alert-outline
                                               font-size-22"
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
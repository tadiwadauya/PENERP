@extends('layouts.app')

@section('title', 'Pensions Administration Dashboard')

@section('page-heading', 'Pensions Administration')

@section('page-subheading')
    Member administration, pension payroll and benefit claims
@endsection


@section('content')


{{-- =========================================================
     PENSIONS CONTEXT NAVIGATION
========================================================= --}}

@include(
    'pensions-administration.partials.navigation'
)



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

                        <h4 class="header-title mb-2">

                            Welcome,
                            {{
                                auth()->user()->first_name
                                ?? 'User'
                            }}

                        </h4>


                        <p class="text-muted mb-0">

                            Select a Pensions Administration
                            function below. Access is based on
                            your assigned roles and permissions.

                        </p>

                    </div>


                    <div class="mt-3 mt-md-0">

                        <a
                            href="{{ route(
                                'pensions-administration.dashboard'
                            ) }}"
                            class="btn btn-primary"
                        >

                            <i
                                class="
                                    mdi
                                    mdi-view-dashboard-outline
                                    me-1
                                "
                            ></i>

                            Pensions Home

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     PENSIONS MODULES
========================================================= --}}

<div class="row">

    <div class="col-12">

        <h4 class="header-title mb-3">
            Pensions Administration Modules
        </h4>

        <p class="text-muted">
            Your authorised pension administration functions.
        </p>

    </div>



    {{-- =====================================================
         UPDATES / MEMBERSHIP
    ====================================================== --}}

    @can('updates.members.view')

        <div class="col-xl-4 col-md-6">

            <a
                href="{{ route(
                    'pensions-administration.updates.dashboard'
                ) }}"
                class="text-decoration-none"
            >

                <div class="card h-100">

                    <div class="card-body">

                        <div
                            class="
                                d-flex
                                align-items-start
                            "
                        >

                            <div
                                class="
                                    avatar-md
                                    me-3
                                "
                            >

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
                                            font-size-24
                                        "
                                    ></i>

                                </span>

                            </div>


                            <div class="flex-grow-1">

                                <h4 class="font-size-18 mb-2">
                                    Updates / Membership
                                </h4>


                                <p class="text-muted mb-3">

                                    Manage contributing members,
                                    employers, employment information
                                    and membership movements.

                                </p>


                                <span class="text-primary">

                                    Open Updates Dashboard

                                    <i
                                        class="
                                            mdi
                                            mdi-arrow-right
                                            ms-1
                                        "
                                    ></i>

                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

    @endcan



    {{-- =====================================================
         PENSION PAYROLL
    ====================================================== --}}

    @can('payroll.payroll-runs.view')

        <div class="col-xl-4 col-md-6">

            <div class="card h-100">

                <div class="card-body">

                    <div
                        class="
                            d-flex
                            align-items-start
                        "
                    >

                        <div class="avatar-md me-3">

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
                                        mdi-cash-multiple
                                        font-size-24
                                    "
                                ></i>

                            </span>

                        </div>


                        <div class="flex-grow-1">

                            <h4 class="font-size-18 mb-2">
                                Pension Payroll
                            </h4>


                            <p class="text-muted mb-3">

                                Process pension payrolls,
                                adjustments, deductions,
                                payments and payroll reports.

                            </p>


                            <span
                                class="
                                    badge
                                    bg-soft-secondary
                                    text-secondary
                                "
                            >
                                To be implemented
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    @endcan



    {{-- =====================================================
         BENEFIT CLAIMS
    ====================================================== --}}

    @can('claims.claims.view')

        <div class="col-xl-4 col-md-6">

            <div class="card h-100">

                <div class="card-body">

                    <div
                        class="
                            d-flex
                            align-items-start
                        "
                    >

                        <div class="avatar-md me-3">

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
                                        font-size-24
                                    "
                                ></i>

                            </span>

                        </div>


                        <div class="flex-grow-1">

                            <h4 class="font-size-18 mb-2">
                                Benefit Claims
                            </h4>


                            <p class="text-muted mb-3">

                                Process retirement, withdrawal,
                                death, ill-health and other
                                benefit claims.

                            </p>


                            <span
                                class="
                                    badge
                                    bg-soft-secondary
                                    text-secondary
                                "
                            >
                                Planned after Payroll
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    @endcan

</div>



{{-- =========================================================
     UPDATES QUICK ACCESS
========================================================= --}}

@can('updates.members.view')

    <div class="row mt-2">

        <div class="col-12">

            <h4 class="header-title mb-3">
                Updates Quick Access
            </h4>

        </div>



        {{-- Members --}}
        <div class="col-xl-3 col-md-6">

            <a
                href="{{ route(
                    'pensions-administration.updates.members.index'
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
                                            mdi-account-multiple-outline
                                            font-size-22
                                        "
                                    ></i>

                                </span>

                            </div>


                            <div>

                                <h5 class="font-size-16 mb-1">
                                    Members
                                </h5>

                                <p class="text-muted mb-0">
                                    Membership register
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>



        {{-- Employers --}}
        <div class="col-xl-3 col-md-6">

            <a
                href="{{ route(
                    'pensions-administration.updates.employers.index'
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
                                            mdi-office-building-outline
                                            font-size-22
                                        "
                                    ></i>

                                </span>

                            </div>


                            <div>

                                <h5 class="font-size-16 mb-1">
                                    Employers
                                </h5>

                                <p class="text-muted mb-0">
                                    Local authorities
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>



        {{-- Employer Groups --}}
        <div class="col-xl-3 col-md-6">

            <a
                href="{{ route(
                    'pensions-administration.updates.employer-groups.index'
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
                                            mdi-folder-multiple-outline
                                            font-size-22
                                        "
                                    ></i>

                                </span>

                            </div>


                            <div>

                                <h5 class="font-size-16 mb-1">
                                    Employer Groups
                                </h5>

                                <p class="text-muted mb-0">
                                    Employer classifications
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>



        {{-- Updates Dashboard --}}
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
                                        bg-soft-warning
                                        text-warning
                                    "
                                >

                                    <i
                                        class="
                                            mdi
                                            mdi-view-dashboard-outline
                                            font-size-22
                                        "
                                    ></i>

                                </span>

                            </div>


                            <div>

                                <h5 class="font-size-16 mb-1">
                                    Updates Dashboard
                                </h5>

                                <p class="text-muted mb-0">
                                    Membership overview
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </a>

        </div>

    </div>

@endcan



{{-- =========================================================
     FUTURE PENSIONS FUNCTIONS
========================================================= --}}

<div class="row mt-2">

    <div class="col-12">

        <h4 class="header-title mb-3">
            Additional Pensions Functions
        </h4>

    </div>



    {{-- Pensioners --}}
    @can('pensioners.pensioners.view')

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
                                        mdi-account-card-details-outline
                                        font-size-22
                                    "
                                ></i>

                            </span>

                        </div>


                        <div>

                            <h5 class="font-size-16 mb-1">
                                Pensioners
                            </h5>

                            <p class="text-muted mb-0">
                                Pensioner records
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    @endcan



    {{-- Reports --}}
    @can('pensions.reports.view')

        <div class="col-xl-3 col-md-6">

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
                                        mdi-chart-box-outline
                                        font-size-22
                                    "
                                ></i>

                            </span>

                        </div>


                        <div>

                            <h5 class="font-size-16 mb-1">
                                Reports
                            </h5>

                            <p class="text-muted mb-0">
                                Pensions reports
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    @endcan

</div>



{{-- =========================================================
     NO ACCESS
========================================================= --}}

@if(
    !auth()->user()->can('updates.members.view')
    &&
    !auth()->user()->can('claims.claims.view')
    &&
    !auth()->user()->can('payroll.payroll-runs.view')
    &&
    !auth()->user()->can('pensioners.pensioners.view')
    &&
    !auth()->user()->can('contributions.contributions.view')
    &&
    !auth()->user()->can('pensions.reports.view')
)

    <div class="card">

        <div class="card-body text-center py-5">

            <div class="avatar-md mx-auto mb-3">

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
                            mdi-shield-lock-outline
                            font-size-24
                        "
                    ></i>

                </span>

            </div>


            <h4>
                No Pensions Modules Assigned
            </h4>


            <p class="text-muted mb-0">

                You currently do not have permission
                to access any Pensions Administration
                modules.

            </p>

        </div>

    </div>

@endif


@endsection
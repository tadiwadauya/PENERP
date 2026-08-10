@extends('layouts.app')

@section('title', 'Principal Officer Dashboard')

@section('page-heading', "Principal Officer's Office")

@section('page-subheading')
    Executive oversight and corporate administration
@endsection


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

                        <h4 class="header-title mb-2">

                            Welcome,
                            {{
                                auth()->user()->first_name
                                ?? 'User'
                            }}

                        </h4>

                        <p class="text-muted mb-0">

                            Access authorised executive,
                            human resources, procurement and
                            corporate administration functions.

                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     PRINCIPAL OFFICE FUNCTIONS
========================================================= --}}

@if(
    auth()->user()->can(
        'principal-office.executive-dashboard.view'
    )
    ||
    auth()->user()->can(
        'hr.employees.view'
    )
    ||
    auth()->user()->can(
        'procurement.procurement.view'
    )
    ||
    auth()->user()->can(
        'principal-office.approvals.view'
    )
    ||
    auth()->user()->can(
        'principal-office.reports.view'
    )
)

    <div class="row mt-2">

        <div class="col-12">

            <h4 class="header-title mb-3">
                Principal Office Functions
            </h4>

            <p class="text-muted">
                Only functions assigned to your account are displayed.
            </p>

        </div>



        {{-- =====================================================
             EXECUTIVE OVERVIEW
        ====================================================== --}}

        @can(
            'principal-office.executive-dashboard.view'
        )

            <div class="col-xl-3 col-md-6">

                <a
                    href="#"
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
                                                mdi-view-dashboard-outline
                                                font-size-22
                                            "
                                        ></i>

                                    </span>

                                </div>


                                <div>

                                    <h5 class="font-size-16 mb-1">
                                        Executive Overview
                                    </h5>

                                    <p class="text-muted mb-0">
                                        Organisation overview
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>

                </a>

            </div>

        @endcan



        {{-- =====================================================
             HUMAN RESOURCES
        ====================================================== --}}

        @can(
            'hr.employees.view'
        )

            <div class="col-xl-3 col-md-6">

                <a
                    href="#"
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
                                                mdi-account-tie-outline
                                                font-size-22
                                            "
                                        ></i>

                                    </span>

                                </div>


                                <div>

                                    <h5 class="font-size-16 mb-1">
                                        Human Resources
                                    </h5>

                                    <p class="text-muted mb-0">
                                        HR administration
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>

                </a>

            </div>

        @endcan



        {{-- =====================================================
             PROCUREMENT
        ====================================================== --}}

        @can(
            'procurement.procurement.view'
        )

            <div class="col-xl-3 col-md-6">

                <a
                    href="#"
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
                                                mdi-cart-check
                                                font-size-22
                                            "
                                        ></i>

                                    </span>

                                </div>


                                <div>

                                    <h5 class="font-size-16 mb-1">
                                        Procurement
                                    </h5>

                                    <p class="text-muted mb-0">
                                        Procurement processes
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>

                </a>

            </div>

        @endcan



        {{-- =====================================================
             APPROVALS
        ====================================================== --}}

        @can(
            'principal-office.approvals.view'
        )

            <div class="col-xl-3 col-md-6">

                <a
                    href="#"
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
                                                mdi-checkbox-marked-circle-outline
                                                font-size-22
                                            "
                                        ></i>

                                    </span>

                                </div>


                                <div>

                                    <h5 class="font-size-16 mb-1">
                                        Approvals
                                    </h5>

                                    <p class="text-muted mb-0">
                                        Executive approvals
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>

                </a>

            </div>

        @endcan



        {{-- =====================================================
             MANAGEMENT REPORTS
        ====================================================== --}}

        @can(
            'principal-office.reports.view'
        )

            <div class="col-xl-3 col-md-6">

                <a
                    href="#"
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
                                                mdi-chart-line
                                                font-size-22
                                            "
                                        ></i>

                                    </span>

                                </div>


                                <div>

                                    <h5 class="font-size-16 mb-1">
                                        Management Reports
                                    </h5>

                                    <p class="text-muted mb-0">
                                        Executive reports
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>

                </a>

            </div>

        @endcan


    </div>

@else

    {{-- =========================================================
         NO ACCESS
    ========================================================== --}}

    <div class="row">

        <div class="col-12">

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
                        No Principal Office Functions Assigned
                    </h4>


                    <p class="text-muted mb-0">

                        You currently do not have permission
                        to access any Principal Officer's Office
                        functions.

                    </p>

                </div>

            </div>

        </div>

    </div>

@endif


@endsection
@extends('layouts.app')

@section(
    'title',
    'Pensions Administration'
)

@section(
    'page-heading',
    'Pensions Administration'
)


@section('content')


{{-- =========================================================
     CONTEXT NAVIGATION
========================================================= --}}

@include(
    'pensions-administration.partials.navigation'
)



{{-- =========================================================
     WELCOME
========================================================= --}}

<div class="card">

    <div class="card-body">

        <div
            class="row
                   align-items-center"
        >

            <div class="col-lg-8">

                <h3 class="mb-2">
                    Pensions Administration
                </h3>

                <p class="text-muted mb-0">

                    Manage membership, pension payroll
                    processing and benefit claims from
                    one integrated administration area.

                </p>

            </div>


            <div
                class="col-lg-4
                       text-lg-end
                       mt-3 mt-lg-0"
            >

                <a
                    href="{{ route(
                        'pensions-administration.updates.dashboard'
                    ) }}"
                    class="btn btn-primary"
                >

                    <i
                        class="mdi mdi-account-group-outline me-1"
                    ></i>

                    Open Updates

                </a>

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     SUMMARY
========================================================= --}}

<div class="row">


    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body text-center">

                <div
                    class="mini-stat-icon
                           mx-auto
                           mb-3"
                >

                    <span
                        class="avatar-title
                               rounded-circle
                               bg-soft-primary"
                    >

                        <i
                            class="mdi
                                   mdi-account-group-outline
                                   text-primary
                                   font-size-22"
                        ></i>

                    </span>

                </div>

                <p class="text-muted mb-1">
                    Total Members
                </p>

                <h4>
                    {{
                        number_format(
                            $statistics[
                                'total_members'
                            ]
                        )
                    }}
                </h4>

            </div>

        </div>

    </div>



    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body text-center">

                <div
                    class="mini-stat-icon
                           mx-auto
                           mb-3"
                >

                    <span
                        class="avatar-title
                               rounded-circle
                               bg-soft-success"
                    >

                        <i
                            class="mdi
                                   mdi-account-check-outline
                                   text-success
                                   font-size-22"
                        ></i>

                    </span>

                </div>

                <p class="text-muted mb-1">
                    Active Members
                </p>

                <h4>
                    {{
                        number_format(
                            $statistics[
                                'active_members'
                            ]
                        )
                    }}
                </h4>

            </div>

        </div>

    </div>



    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body text-center">

                <div
                    class="mini-stat-icon
                           mx-auto
                           mb-3"
                >

                    <span
                        class="avatar-title
                               rounded-circle
                               bg-soft-warning"
                    >

                        <i
                            class="mdi
                                   mdi-account-clock-outline
                                   text-warning
                                   font-size-22"
                        ></i>

                    </span>

                </div>

                <p class="text-muted mb-1">
                    Dormant Members
                </p>

                <h4>
                    {{
                        number_format(
                            $statistics[
                                'dormant_members'
                            ]
                        )
                    }}
                </h4>

            </div>

        </div>

    </div>



    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body text-center">

                <div
                    class="mini-stat-icon
                           mx-auto
                           mb-3"
                >

                    <span
                        class="avatar-title
                               rounded-circle
                               bg-soft-info"
                    >

                        <i
                            class="mdi
                                   mdi-office-building-outline
                                   text-info
                                   font-size-22"
                        ></i>

                    </span>

                </div>

                <p class="text-muted mb-1">
                    Active Employers
                </p>

                <h4>
                    {{
                        number_format(
                            $statistics[
                                'employers'
                            ]
                        )
                    }}
                </h4>

            </div>

        </div>

    </div>


</div>



{{-- =========================================================
     PENSIONS MODULES
========================================================= --}}

<div class="row">


    {{-- Updates --}}
    <div class="col-xl-4 col-md-6">

        <div class="card h-100">

            <div class="card-body">

                <div
                    class="avatar-md mb-4"
                >

                    <span
                        class="avatar-title
                               rounded-circle
                               bg-soft-primary"
                    >

                        <i
                            class="mdi
                                   mdi-account-group-outline
                                   text-primary
                                   font-size-24"
                        ></i>

                    </span>

                </div>


                <h4>
                    Updates / Membership
                </h4>

                <p class="text-muted">

                    Manage contributing members,
                    participating employers,
                    employment information and
                    membership movements.

                </p>


                <div class="mt-4">

                    <a
                        href="{{ route(
                            'pensions-administration.updates.dashboard'
                        ) }}"
                        class="btn btn-primary"
                    >
                        Open Updates
                        <i
                            class="mdi mdi-arrow-right ms-1"
                        ></i>
                    </a>

                </div>

            </div>

        </div>

    </div>



    {{-- Payroll --}}
    <div class="col-xl-4 col-md-6">

        <div class="card h-100">

            <div class="card-body">

                <div class="avatar-md mb-4">

                    <span
                        class="avatar-title
                               rounded-circle
                               bg-soft-success"
                    >

                        <i
                            class="mdi
                                   mdi-cash-multiple
                                   text-success
                                   font-size-24"
                        ></i>

                    </span>

                </div>


                <h4>
                    Pension Payroll
                </h4>

                <p class="text-muted">

                    Pensioner payroll processing,
                    adjustments, deductions,
                    bank schedules and payroll
                    reconciliation.

                </p>


                <span
                    class="badge
                           bg-soft-secondary
                           text-secondary"
                >
                    To be implemented next
                </span>

            </div>

        </div>

    </div>



    {{-- Claims --}}
    <div class="col-xl-4 col-md-6">

        <div class="card h-100">

            <div class="card-body">

                <div class="avatar-md mb-4">

                    <span
                        class="avatar-title
                               rounded-circle
                               bg-soft-warning"
                    >

                        <i
                            class="mdi
                                   mdi-file-document-outline
                                   text-warning
                                   font-size-24"
                        ></i>

                    </span>

                </div>


                <h4>
                    Benefit Claims
                </h4>

                <p class="text-muted">

                    Retirement, withdrawal,
                    death, ill-health and other
                    benefit claim processing.

                </p>


                <span
                    class="badge
                           bg-soft-secondary
                           text-secondary"
                >
                    Planned after Payroll
                </span>

            </div>

        </div>

    </div>


</div>

@endsection
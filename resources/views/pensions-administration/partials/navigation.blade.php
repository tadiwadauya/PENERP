{{-- =========================================================
     PENSIONS ADMINISTRATION CONTEXT NAVIGATION
========================================================= --}}

@php

    /*
    |--------------------------------------------------------------------------
    | Current User
    |--------------------------------------------------------------------------
    */

    $pensionsNavUser =
        auth()->user();


    /*
    |--------------------------------------------------------------------------
    | Updates / Membership Access
    |--------------------------------------------------------------------------
    */

    $canSeeUpdates =
        $pensionsNavUser
        &&
        (
            $pensionsNavUser->can(
                'updates.dashboard.view'
            )
            ||
            $pensionsNavUser->can(
                'updates.members.view'
            )
            ||
            $pensionsNavUser->can(
                'updates.employers.view'
            )
            ||
            $pensionsNavUser->can(
                'updates.employer-groups.view'
            )
            ||
            $pensionsNavUser->can(
                'updates.membership-imports.view'
            )
            ||
            $pensionsNavUser->can(
                'updates.employer-imports.view'
            )
            ||
            $pensionsNavUser->can(
                'updates.member-movements.view'
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Contribution Access
    |--------------------------------------------------------------------------
    */

    $canSeeContributions =
        $pensionsNavUser
        &&
        (
            $pensionsNavUser->can(
                'contributions.monthly-imports.view'
            )
            ||
            $pensionsNavUser->can(
                'contributions.monthly-imports.create'
            )
            ||
            $pensionsNavUser->can(
                'contributions.monthly-imports.update'
            )
            ||
            $pensionsNavUser->can(
                'contributions.monthly-imports.delete'
            )
            ||
            $pensionsNavUser->can(
                'contributions.monthly-imports.approve'
            )
            ||
            $pensionsNavUser->can(
                'contributions.monthly-imports.reject'
            )
            ||
            $pensionsNavUser->can(
                'contributions.monthly-imports.post'
            )
            ||
            $pensionsNavUser->can(
                'contributions.reports.view'
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Contribution Processing Access
    |--------------------------------------------------------------------------
    */

    $canProcessContributions =
        $pensionsNavUser
        &&
        (
            $pensionsNavUser->can(
                'contributions.monthly-imports.create'
            )
            ||
            $pensionsNavUser->can(
                'contributions.monthly-imports.update'
            )
            ||
            $pensionsNavUser->can(
                'contributions.monthly-imports.delete'
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Contribution Approval Access
    |--------------------------------------------------------------------------
    */

    $canApproveContributions =
        $pensionsNavUser
        &&
        $pensionsNavUser->can(
            'contributions.monthly-imports.approve'
        );


    /*
    |--------------------------------------------------------------------------
    | Contribution Rejection Access
    |--------------------------------------------------------------------------
    */

    $canRejectContributions =
        $pensionsNavUser
        &&
        $pensionsNavUser->can(
            'contributions.monthly-imports.reject'
        );


    /*
    |--------------------------------------------------------------------------
    | Contribution Posting Access
    |--------------------------------------------------------------------------
    */

    $canPostContributions =
        $pensionsNavUser
        &&
        $pensionsNavUser->can(
            'contributions.monthly-imports.post'
        );


    /*
    |--------------------------------------------------------------------------
    | Membership Reports
    |--------------------------------------------------------------------------
    */

    $canSeeMembershipReports =
        $pensionsNavUser
        &&
        $pensionsNavUser->can(
            'updates.reports.membership.view'
        );


    /*
    |--------------------------------------------------------------------------
    | Contribution Reports
    |--------------------------------------------------------------------------
    */

    $canSeeContributionReports =
        $pensionsNavUser
        &&
        $pensionsNavUser->can(
            'contributions.reports.view'
        );


    /*
    |--------------------------------------------------------------------------
    | Any Reports
    |--------------------------------------------------------------------------
    */

    $canSeeReports =
        $canSeeMembershipReports
        ||
        $canSeeContributionReports;

@endphp


<div class="topnav pensions-topnav">

    <div class="container-fluid">

        <nav
            class="
                navbar
                navbar-light
                navbar-expand-lg
                topnav-menu
            "
        >

            {{-- =================================================
                 MOBILE TOGGLE
            ================================================== --}}

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#pensions-topnav-menu"
                aria-controls="pensions-topnav-menu"
                aria-expanded="false"
                aria-label="Toggle navigation"
            >

                <span class="navbar-toggler-icon"></span>

            </button>


            <div
                class="collapse navbar-collapse"
                id="pensions-topnav-menu"
            >

                <ul class="navbar-nav">


                    {{-- =================================================
                         PENSIONS HOME
                    ================================================== --}}

                    @can(
                        'dashboard.pensions.view'
                    )

                        <li class="nav-item">

                            <a
                                class="
                                    nav-link
                                    {{
                                        request()->routeIs(
                                            'pensions-administration.dashboard'
                                        )
                                            ? 'active'
                                            : ''
                                    }}
                                "
                                href="{{
                                    route(
                                        'pensions-administration.dashboard'
                                    )
                                }}"
                            >

                                <i class="dripicons-home me-2"></i>

                                Pensions Home

                            </a>

                        </li>

                    @endcan


                    {{-- =================================================
                         UPDATES / MEMBERSHIP
                    ================================================== --}}

                    @if($canSeeUpdates)

                        <li class="nav-item dropdown">

                            <a
                                class="
                                    nav-link
                                    dropdown-toggle
                                    arrow-none
                                    {{
                                        request()->routeIs(
                                            'pensions-administration.updates.*'
                                        )
                                        &&
                                        !request()->routeIs(
                                            'pensions-administration.updates.reports.*'
                                        )
                                            ? 'active'
                                            : ''
                                    }}
                                "
                                href="javascript:void(0);"
                                id="pensions-updates-menu"
                                role="button"
                            >

                                <i class="dripicons-user-group me-2"></i>

                                Updates / Membership

                                <div class="arrow-down"></div>

                            </a>


                            <div
                                class="dropdown-menu"
                                aria-labelledby="pensions-updates-menu"
                            >


                                {{-- =====================================
                                     UPDATES DASHBOARD
                                ====================================== --}}

                                @can(
                                    'updates.dashboard.view'
                                )

                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.updates.dashboard'
                                            )
                                        }}"
                                        class="
                                            dropdown-item
                                            {{
                                                request()->routeIs(
                                                    'pensions-administration.updates.dashboard'
                                                )
                                                    ? 'active'
                                                    : ''
                                            }}
                                        "
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-view-dashboard-outline
                                                me-2
                                            "
                                        ></i>

                                        Updates Dashboard

                                    </a>

                                @endcan


                                {{-- =====================================
                                     MASTER DATA
                                ====================================== --}}

                                @if(
                                    $pensionsNavUser->can(
                                        'updates.members.view'
                                    )
                                    ||
                                    $pensionsNavUser->can(
                                        'updates.employers.view'
                                    )
                                    ||
                                    $pensionsNavUser->can(
                                        'updates.employer-groups.view'
                                    )
                                )

                                    <div class="dropdown-divider"></div>

                                    <h6 class="dropdown-header">
                                        Membership
                                    </h6>

                                @endif


                                {{-- Members --}}

                                @can(
                                    'updates.members.view'
                                )

                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.updates.members.index'
                                            )
                                        }}"
                                        class="
                                            dropdown-item
                                            {{
                                                request()->routeIs(
                                                    'pensions-administration.updates.members.*'
                                                )
                                                    ? 'active'
                                                    : ''
                                            }}
                                        "
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-account-group-outline
                                                me-2
                                            "
                                        ></i>

                                        Members

                                    </a>

                                @endcan


                                {{-- Employers --}}

                                @can(
                                    'updates.employers.view'
                                )

                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.updates.employers.index'
                                            )
                                        }}"
                                        class="
                                            dropdown-item
                                            {{
                                                request()->routeIs(
                                                    'pensions-administration.updates.employers.*'
                                                )
                                                    ? 'active'
                                                    : ''
                                            }}
                                        "
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-office-building-outline
                                                me-2
                                            "
                                        ></i>

                                        Employers

                                    </a>

                                @endcan


                                {{-- Employer Groups --}}

                                @can(
                                    'updates.employer-groups.view'
                                )

                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.updates.employer-groups.index'
                                            )
                                        }}"
                                        class="
                                            dropdown-item
                                            {{
                                                request()->routeIs(
                                                    'pensions-administration.updates.employer-groups.*'
                                                )
                                                    ? 'active'
                                                    : ''
                                            }}
                                        "
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-folder-multiple-outline
                                                me-2
                                            "
                                        ></i>

                                        Employer Groups

                                    </a>

                                @endcan


                                {{-- =====================================
                                     MEMBER MOVEMENTS
                                ====================================== --}}

                                @can(
                                    'updates.member-movements.view'
                                )

                                    <div class="dropdown-divider"></div>


                                    <span
                                        class="
                                            dropdown-item
                                            text-muted
                                        "
                                        style="cursor:default;"
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-swap-horizontal
                                                me-2
                                            "
                                        ></i>

                                        Member Movements

                                        <small class="ms-1">
                                            Coming next
                                        </small>

                                    </span>

                                @endcan


                                {{-- =====================================
                                     IMPORTS
                                ====================================== --}}

                                @if(
                                    $pensionsNavUser->can(
                                        'updates.membership-imports.view'
                                    )
                                    ||
                                    $pensionsNavUser->can(
                                        'updates.employer-imports.view'
                                    )
                                )

                                    <div class="dropdown-divider"></div>


                                    <h6 class="dropdown-header">

                                        Excel Imports

                                    </h6>

                                @endif


                                {{-- Member Imports --}}

                                @can(
                                    'updates.membership-imports.view'
                                )

                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.updates.imports.index'
                                            )
                                        }}"
                                        class="
                                            dropdown-item
                                            {{
                                                request()->routeIs(
                                                    'pensions-administration.updates.imports.*'
                                                )
                                                    ? 'active'
                                                    : ''
                                            }}
                                        "
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-account-arrow-up-outline
                                                me-2
                                            "
                                        ></i>

                                        Member Imports

                                    </a>

                                @endcan


                                {{-- Employer Imports --}}

                                @can(
                                    'updates.employer-imports.view'
                                )

                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.updates.employer-imports.index'
                                            )
                                        }}"
                                        class="
                                            dropdown-item
                                            {{
                                                request()->routeIs(
                                                    'pensions-administration.updates.employer-imports.*'
                                                )
                                                    ? 'active'
                                                    : ''
                                            }}
                                        "
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-office-building-cog-outline
                                                me-2
                                            "
                                        ></i>

                                        Employer Imports

                                    </a>

                                @endcan

                            </div>

                        </li>

                    @endif


                    {{-- =================================================
                         CONTRIBUTIONS
                    ================================================== --}}

                    @if($canSeeContributions)

                        <li class="nav-item dropdown">

                            <a
                                class="
                                    nav-link
                                    dropdown-toggle
                                    arrow-none
                                    {{
                                        request()->routeIs(
                                            'pensions-administration.contributions.*'
                                        )
                                            ? 'active'
                                            : ''
                                    }}
                                "
                                href="javascript:void(0);"
                                id="pensions-contributions-menu"
                                role="button"
                            >

                                <i
                                    class="
                                        mdi
                                        mdi-cash-multiple
                                        me-2
                                    "
                                ></i>

                                Contributions

                                <div class="arrow-down"></div>

                            </a>


                            <div
                                class="dropdown-menu"
                                aria-labelledby="pensions-contributions-menu"
                            >


                                {{-- =====================================
                                     CONTRIBUTION REGISTER
                                ====================================== --}}

                                @can(
                                    'contributions.monthly-imports.view'
                                )

                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.contributions.imports.index'
                                            )
                                        }}"
                                        class="
                                            dropdown-item
                                            {{
                                                request()->routeIs(
                                                    'pensions-administration.contributions.imports.index'
                                                )
                                                    ? 'active'
                                                    : ''
                                            }}
                                        "
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-format-list-bulleted
                                                me-2
                                            "
                                        ></i>

                                        Monthly Contribution Batches

                                    </a>

                                @endcan


                                {{-- =====================================
                                     UPLOAD CONTRIBUTIONS
                                ====================================== --}}

                                @can(
                                    'contributions.monthly-imports.create'
                                )

                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.contributions.imports.create'
                                            )
                                        }}"
                                        class="
                                            dropdown-item
                                            {{
                                                request()->routeIs(
                                                    'pensions-administration.contributions.imports.create'
                                                )
                                                    ? 'active'
                                                    : ''
                                            }}
                                        "
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-upload
                                                me-2
                                            "
                                        ></i>

                                        Upload Monthly Contributions

                                    </a>

                                @endcan


                                {{-- =====================================
                                     PROCESSING
                                ====================================== --}}

                                @if($canProcessContributions)

                                    <div class="dropdown-divider"></div>


                                    <h6 class="dropdown-header">

                                        Processing

                                    </h6>


                                    @can(
                                        'contributions.monthly-imports.view'
                                    )

                                        <a
                                            href="{{
                                                route(
                                                    'pensions-administration.contributions.imports.index',
                                                    [
                                                        'status' =>
                                                            'awaiting_review',
                                                    ]
                                                )
                                            }}"
                                            class="dropdown-item"
                                        >

                                            <i
                                                class="
                                                    mdi
                                                    mdi-file-search-outline
                                                    me-2
                                                "
                                            ></i>

                                            Batches Awaiting Review

                                        </a>

                                    @endcan

                                @endif


                                {{-- =====================================
                                     APPROVAL / REJECTION
                                ====================================== --}}

                                @if(
                                    $canApproveContributions
                                    ||
                                    $canRejectContributions
                                )

                                    <div class="dropdown-divider"></div>


                                    <h6 class="dropdown-header">

                                        Approval

                                    </h6>


                                    @if(
                                        $canApproveContributions
                                        ||
                                        $canRejectContributions
                                    )

                                        <a
                                            href="{{
                                                route(
                                                    'pensions-administration.contributions.imports.index',
                                                    [
                                                        'status' =>
                                                            'awaiting_review',
                                                    ]
                                                )
                                            }}"
                                            class="dropdown-item"
                                        >

                                            <i
                                                class="
                                                    mdi
                                                    mdi-clipboard-check-outline
                                                    me-2
                                                "
                                            ></i>

                                            Contribution Approval Queue

                                        </a>

                                    @endif


                                    @if($canApproveContributions)

                                        <span
                                            class="
                                                dropdown-item
                                                contribution-permission-hint
                                            "
                                        >

                                            <i
                                                class="
                                                    mdi
                                                    mdi-check-decagram-outline
                                                    me-2
                                                    text-success
                                                "
                                            ></i>

                                            You Can Approve

                                        </span>

                                    @endif


                                    @if($canRejectContributions)

                                        <span
                                            class="
                                                dropdown-item
                                                contribution-permission-hint
                                            "
                                        >

                                            <i
                                                class="
                                                    mdi
                                                    mdi-close-circle-outline
                                                    me-2
                                                    text-danger
                                                "
                                            ></i>

                                            You Can Reject

                                        </span>

                                    @endif

                                @endif


                                {{-- =====================================
                                     POSTING
                                ====================================== --}}

                                @if($canPostContributions)

                                    <div class="dropdown-divider"></div>


                                    <h6 class="dropdown-header">

                                        Posting

                                    </h6>


                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.contributions.imports.index',
                                                [
                                                    'status' =>
                                                        'approved',
                                                ]
                                            )
                                        }}"
                                        class="dropdown-item"
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-database-arrow-up-outline
                                                me-2
                                            "
                                        ></i>

                                        Approved Batches to Post

                                    </a>

                                @endif


                                {{-- =====================================
                                     REJECTED BATCHES
                                ====================================== --}}

                                @if(
                                    $canApproveContributions
                                    ||
                                    $canRejectContributions
                                    ||
                                    $canProcessContributions
                                )

                                    <div class="dropdown-divider"></div>


                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.contributions.imports.index',
                                                [
                                                    'status' =>
                                                        'rejected',
                                                ]
                                            )
                                        }}"
                                        class="dropdown-item"
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-file-cancel-outline
                                                me-2
                                            "
                                        ></i>

                                        Rejected Batches

                                    </a>

                                @endif


                                {{-- =====================================
                                     CONTRIBUTION REPORTS
                                ====================================== --}}

                                @if($canSeeContributionReports)

                                    <div class="dropdown-divider"></div>


                                    <h6 class="dropdown-header">

                                        Contribution Reports

                                    </h6>


                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.contributions.imports.index',
                                                [
                                                    'status' =>
                                                        'posted',
                                                ]
                                            )
                                        }}"
                                        class="dropdown-item"
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-file-chart-outline
                                                me-2
                                            "
                                        ></i>

                                        Posted Contributions

                                    </a>


                                    <span
                                        class="
                                            dropdown-item
                                            contribution-permission-hint
                                        "
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-scale-balance
                                                me-2
                                            "
                                        ></i>

                                        Reconciliation Reports

                                        <small class="ms-1 text-muted">

                                            Open a batch

                                        </small>

                                    </span>

                                @endif

                            </div>

                        </li>

                    @endif


                    {{-- =================================================
                         REPORTS
                    ================================================== --}}

                    @if($canSeeReports)

                        <li class="nav-item dropdown">

                            <a
                                class="
                                    nav-link
                                    dropdown-toggle
                                    arrow-none
                                    {{
                                        request()->routeIs(
                                            'pensions-administration.updates.reports.*'
                                        )
                                        ||
                                        request()->routeIs(
                                            'pensions-administration.contributions.reconciliation.*'
                                        )
                                            ? 'active'
                                            : ''
                                    }}
                                "
                                href="javascript:void(0);"
                                id="pensions-reports-menu"
                                role="button"
                            >

                                <i class="dripicons-graph-bar me-2"></i>

                                Reports

                                <div class="arrow-down"></div>

                            </a>


                            <div
                                class="dropdown-menu"
                                aria-labelledby="pensions-reports-menu"
                            >


                                {{-- =====================================
                                     MEMBERSHIP REPORTS
                                ====================================== --}}

                                @can(
                                    'updates.reports.membership.view'
                                )

                                    <h6 class="dropdown-header">

                                        Membership Reports

                                    </h6>


                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.updates.reports.membership.index'
                                            )
                                        }}"
                                        class="dropdown-item"
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-chart-box-outline
                                                me-2
                                            "
                                        ></i>

                                        Membership Reports Centre

                                    </a>


                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.updates.reports.membership.index'
                                            )
                                        }}#member-register"
                                        class="dropdown-item"
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-account-group-outline
                                                me-2
                                            "
                                        ></i>

                                        Membership Register

                                    </a>


                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.updates.reports.membership.index'
                                            )
                                        }}#employer-summary"
                                        class="dropdown-item"
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-office-building-outline
                                                me-2
                                            "
                                        ></i>

                                        Employer Membership

                                    </a>


                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.updates.reports.membership.index'
                                            )
                                        }}#age-profile"
                                        class="dropdown-item"
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-calendar-account-outline
                                                me-2
                                            "
                                        ></i>

                                        Age Profile

                                    </a>


                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.updates.reports.membership.index'
                                            )
                                        }}#legacy-mapping"
                                        class="dropdown-item"
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-link-variant
                                                me-2
                                            "
                                        ></i>

                                        Legacy Number Mapping

                                    </a>


                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.updates.reports.membership.index'
                                            )
                                        }}#data-quality"
                                        class="dropdown-item"
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-database-alert-outline
                                                me-2
                                            "
                                        ></i>

                                        Data Quality

                                    </a>

                                @endcan


                                {{-- =====================================
                                     CONTRIBUTION REPORTS
                                ====================================== --}}

                                @if(
                                    $canSeeMembershipReports
                                    &&
                                    $canSeeContributionReports
                                )

                                    <div class="dropdown-divider"></div>

                                @endif


                                @can(
                                    'contributions.reports.view'
                                )

                                    <h6 class="dropdown-header">

                                        Contribution Reports

                                    </h6>


                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.contributions.imports.index',
                                                [
                                                    'status' =>
                                                        'posted',
                                                ]
                                            )
                                        }}"
                                        class="dropdown-item"
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-cash-check
                                                me-2
                                            "
                                        ></i>

                                        Posted Monthly Contributions

                                    </a>


                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.contributions.imports.index'
                                            )
                                        }}"
                                        class="dropdown-item"
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-scale-balance
                                                me-2
                                            "
                                        ></i>

                                        Contribution Reconciliations

                                    </a>

                                @endcan

                            </div>

                        </li>

                    @endif


                    {{-- =================================================
                         RETURN TO ERP
                    ================================================== --}}

                    <li class="nav-item">

                        <a
                            class="nav-link"
                            href="{{
                                route(
                                    'dashboard'
                                )
                            }}"
                        >

                            <i
                                class="
                                    mdi
                                    mdi-arrow-left-circle-outline
                                    me-2
                                "
                            ></i>

                            Main ERP

                        </a>

                    </li>

                </ul>

            </div>

        </nav>

    </div>

</div>


@once

    @push('styles')

        <style>

            /*
            |--------------------------------------------------------------------------
            | Pensions Context Navigation
            |--------------------------------------------------------------------------
            */

            .pensions-topnav {
                position: relative;
                z-index: 900;
            }


            .pensions-topnav .nav-link.active {
                font-weight: 600;
            }


            .pensions-topnav .dropdown-item.active {
                font-weight: 600;
            }


            .pensions-topnav .dropdown-item i {
                width: 18px;
                display: inline-block;
            }


            .pensions-topnav .dropdown-header {
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: .5px;
                font-weight: 600;
            }


            /*
            |--------------------------------------------------------------------------
            | Permission Information
            |--------------------------------------------------------------------------
            |
            | These are information-only items and are not clickable actions.
            |
            */

            .pensions-topnav .contribution-permission-hint {
                cursor: default;
                font-size: 12px;
            }


            /*
            |--------------------------------------------------------------------------
            | Dark Mode
            |--------------------------------------------------------------------------
            */

            body.lapf-dark-mode .pensions-topnav {
                border-bottom:
                    1px solid
                    rgba(
                        255,
                        255,
                        255,
                        .08
                    );
            }

        </style>

    @endpush

@endonce
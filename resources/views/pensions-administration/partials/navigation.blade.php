{{-- =========================================================
     PENSIONS ADMINISTRATION CONTEXT NAVIGATION
========================================================= --}}

@php

    /*
    |--------------------------------------------------------------------------
    | Current User
    |--------------------------------------------------------------------------
    */

    $pensionsNavUser = auth()->user();


    /*
    |--------------------------------------------------------------------------
    | Updates / Membership Access
    |--------------------------------------------------------------------------
    */

    $canSeeUpdates =
        $pensionsNavUser
        &&
        (
            $pensionsNavUser->can('updates.dashboard.view')
            ||
            $pensionsNavUser->can('updates.members.view')
            ||
            $pensionsNavUser->can('updates.employers.view')
            ||
            $pensionsNavUser->can('updates.employer-groups.view')
            ||
            $pensionsNavUser->can('updates.membership-imports.view')
            ||
            $pensionsNavUser->can('updates.employer-imports.view')
            ||
            $pensionsNavUser->can('updates.member-movements.view')
        );


    /*
    |--------------------------------------------------------------------------
    | Historical Contribution Migration
    |--------------------------------------------------------------------------
    */

    $canManageHistoricalContributions =
        $pensionsNavUser
        &&
        $pensionsNavUser->hasRole('system-administrator')
        &&
        $pensionsNavUser->can('contributions.historical-imports.manage');


    /*
    |--------------------------------------------------------------------------
    | Contribution Receipts
    |--------------------------------------------------------------------------
    */

    $canSeeReceipts =
        $pensionsNavUser
        &&
        (
            $pensionsNavUser->can('contributions.receipts.view')
            ||
            $pensionsNavUser->can('contributions.receipts.create')
            ||
            $pensionsNavUser->can('contributions.receipts.post')
            ||
            $pensionsNavUser->can('contributions.exchange-rates.view')
            ||
            $pensionsNavUser->can('contributions.exchange-rates.manage')
        );


    /*
    |--------------------------------------------------------------------------
    | Contribution Reports
    |--------------------------------------------------------------------------
    */

    $canSeeContributionReports =
        $pensionsNavUser
        &&
        $pensionsNavUser->can('contributions.reports.view');


    /*
    |--------------------------------------------------------------------------
    | Contribution Access
    |--------------------------------------------------------------------------
    */

    $canSeeContributions =
        $pensionsNavUser
        &&
        (
            $pensionsNavUser->can('contributions.monthly-imports.view')
            ||
            $pensionsNavUser->can('contributions.monthly-imports.create')
            ||
            $pensionsNavUser->can('contributions.monthly-imports.update')
            ||
            $pensionsNavUser->can('contributions.monthly-imports.delete')
            ||
            $pensionsNavUser->can('contributions.monthly-imports.approve')
            ||
            $pensionsNavUser->can('contributions.monthly-imports.reject')
            ||
            $pensionsNavUser->can('contributions.monthly-imports.post')
            ||
            $canSeeContributionReports
            ||
            $canSeeReceipts
            ||
            $canManageHistoricalContributions
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
            $pensionsNavUser->can('contributions.monthly-imports.create')
            ||
            $pensionsNavUser->can('contributions.monthly-imports.update')
            ||
            $pensionsNavUser->can('contributions.monthly-imports.delete')
        );


    /*
    |--------------------------------------------------------------------------
    | Contribution Approval Access
    |--------------------------------------------------------------------------
    */

    $canApproveContributions =
        $pensionsNavUser
        &&
        $pensionsNavUser->can('contributions.monthly-imports.approve');


    /*
    |--------------------------------------------------------------------------
    | Contribution Rejection Access
    |--------------------------------------------------------------------------
    */

    $canRejectContributions =
        $pensionsNavUser
        &&
        $pensionsNavUser->can('contributions.monthly-imports.reject');


    /*
    |--------------------------------------------------------------------------
    | Contribution Posting Access
    |--------------------------------------------------------------------------
    */

    $canPostContributions =
        $pensionsNavUser
        &&
        $pensionsNavUser->can('contributions.monthly-imports.post');


    /*
    |--------------------------------------------------------------------------
    | Shared Membership Reports
    |--------------------------------------------------------------------------
    */

    $canSeeMembershipReports =
        $pensionsNavUser
        &&
        $pensionsNavUser->can('pensions.reports.membership.view');


    /*
    |--------------------------------------------------------------------------
    | Employer Membership Reports
    |--------------------------------------------------------------------------
    */

    $canSeeEmployerMembershipReports =
        $pensionsNavUser
        &&
        $pensionsNavUser->can('pensions.reports.employer-membership.view');


    /*
    |--------------------------------------------------------------------------
    | Actuarial Valuation / Actuarial Data Extract
    |--------------------------------------------------------------------------
    */

    $canSeeActuarialReports =
        $pensionsNavUser
        &&
        $pensionsNavUser->can('pensions.reports.actuarial-data.view');


    /*
    |--------------------------------------------------------------------------
    | Any Reports
    |--------------------------------------------------------------------------
    */

    $canSeeReports =
        $canSeeMembershipReports
        ||
        $canSeeEmployerMembershipReports
        ||
        $canSeeContributionReports
        ||
        $canSeeActuarialReports;


    /*
    |--------------------------------------------------------------------------
    | Pension Benefit Settings
    |--------------------------------------------------------------------------
    |
    | Settings are restricted to System Administrator.
    |
    */

    $canSeeBenefitSettings =
        $pensionsNavUser
        &&
        $pensionsNavUser->hasRole('system-administrator')
        &&
        $pensionsNavUser->can('pensions.settings.view');

@endphp


<div class="topnav pensions-topnav">

    <div class="container-fluid">

        <nav class="navbar navbar-light navbar-expand-lg topnav-menu">

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

                    @can('dashboard.pensions.view')

                        <li class="nav-item">

                            <a
                                class="nav-link {{
                                    request()->routeIs('pensions-administration.dashboard')
                                        ? 'active'
                                        : ''
                                }}"
                                href="{{ route('pensions-administration.dashboard') }}"
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
                                class="nav-link dropdown-toggle arrow-none {{
                                    request()->routeIs('pensions-administration.updates.*')
                                    &&
                                    !request()->routeIs('pensions-administration.updates.reports.*')
                                        ? 'active'
                                        : ''
                                }}"
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

                                @can('updates.dashboard.view')

                                    <a
                                        href="{{ route('pensions-administration.updates.dashboard') }}"
                                        class="dropdown-item {{
                                            request()->routeIs('pensions-administration.updates.dashboard')
                                                ? 'active'
                                                : ''
                                        }}"
                                    >
                                        <i class="mdi mdi-view-dashboard-outline me-2"></i>

                                        Updates Dashboard
                                    </a>

                                @endcan


                                @if(
                                    $pensionsNavUser->can('updates.members.view')
                                    ||
                                    $pensionsNavUser->can('updates.employers.view')
                                    ||
                                    $pensionsNavUser->can('updates.employer-groups.view')
                                )

                                    <div class="dropdown-divider"></div>

                                    <h6 class="dropdown-header">
                                        Membership
                                    </h6>

                                @endif


                                @can('updates.members.view')

                                    <a
                                        href="{{ route('pensions-administration.updates.members.index') }}"
                                        class="dropdown-item {{
                                            request()->routeIs('pensions-administration.updates.members.*')
                                                ? 'active'
                                                : ''
                                        }}"
                                    >
                                        <i class="mdi mdi-account-group-outline me-2"></i>

                                        Members
                                    </a>

                                @endcan


                                @can('updates.employers.view')

                                    <a
                                        href="{{ route('pensions-administration.updates.employers.index') }}"
                                        class="dropdown-item {{
                                            request()->routeIs('pensions-administration.updates.employers.*')
                                                ? 'active'
                                                : ''
                                        }}"
                                    >
                                        <i class="mdi mdi-office-building-outline me-2"></i>

                                        Employers
                                    </a>

                                @endcan


                                @can('updates.employer-groups.view')

                                    <a
                                        href="{{ route('pensions-administration.updates.employer-groups.index') }}"
                                        class="dropdown-item {{
                                            request()->routeIs('pensions-administration.updates.employer-groups.*')
                                                ? 'active'
                                                : ''
                                        }}"
                                    >
                                        <i class="mdi mdi-folder-multiple-outline me-2"></i>

                                        Employer Groups
                                    </a>

                                @endcan


                                @can('updates.member-movements.view')

                                    <div class="dropdown-divider"></div>

                                    <span
                                        class="dropdown-item text-muted"
                                        style="cursor:default;"
                                    >
                                        <i class="mdi mdi-swap-horizontal me-2"></i>

                                        Member Movements

                                        <small class="ms-1">
                                            Coming next
                                        </small>
                                    </span>

                                @endcan


                                @if(
                                    $pensionsNavUser->can('updates.membership-imports.view')
                                    ||
                                    $pensionsNavUser->can('updates.employer-imports.view')
                                )

                                    <div class="dropdown-divider"></div>

                                    <h6 class="dropdown-header">
                                        Excel Imports
                                    </h6>

                                @endif


                                @can('updates.membership-imports.view')

                                    <a
                                        href="{{ route('pensions-administration.updates.imports.index') }}"
                                        class="dropdown-item {{
                                            request()->routeIs('pensions-administration.updates.imports.*')
                                                ? 'active'
                                                : ''
                                        }}"
                                    >
                                        <i class="mdi mdi-account-arrow-up-outline me-2"></i>

                                        Member Imports
                                    </a>

                                @endcan


                                @can('updates.employer-imports.view')

                                    <a
                                        href="{{ route('pensions-administration.updates.employer-imports.index') }}"
                                        class="dropdown-item {{
                                            request()->routeIs('pensions-administration.updates.employer-imports.*')
                                                ? 'active'
                                                : ''
                                        }}"
                                    >
                                        <i class="mdi mdi-office-building-cog-outline me-2"></i>

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
                                class="nav-link dropdown-toggle arrow-none {{
                                    request()->routeIs('pensions-administration.contributions.*')
                                    ||
                                    request()->routeIs('pensions-administration.historical-contributions.*')
                                        ? 'active'
                                        : ''
                                }}"
                                href="javascript:void(0);"
                                id="pensions-contributions-menu"
                                role="button"
                            >
                                <i class="mdi mdi-cash-multiple me-2"></i>

                                Contributions

                                <div class="arrow-down"></div>
                            </a>


                            <div
                                class="dropdown-menu"
                                aria-labelledby="pensions-contributions-menu"
                            >

                                @can('contributions.monthly-imports.view')

                                    <a
                                        href="{{ route('pensions-administration.contributions.imports.index') }}"
                                        class="dropdown-item {{
                                            request()->routeIs('pensions-administration.contributions.imports.index')
                                                ? 'active'
                                                : ''
                                        }}"
                                    >
                                        <i class="mdi mdi-format-list-bulleted me-2"></i>

                                        Monthly Contribution Batches
                                    </a>

                                @endcan


                                @can('contributions.monthly-imports.create')

                                    <a
                                        href="{{ route('pensions-administration.contributions.imports.create') }}"
                                        class="dropdown-item {{
                                            request()->routeIs('pensions-administration.contributions.imports.create')
                                                ? 'active'
                                                : ''
                                        }}"
                                    >
                                        <i class="mdi mdi-upload me-2"></i>

                                        Upload Monthly Contributions
                                    </a>

                                @endcan


                                {{-- =====================================
                                     CONTRIBUTION RECEIPTS
                                ====================================== --}}

                                @if($canSeeReceipts)

                                    <div class="dropdown-divider"></div>

                                    <h6 class="dropdown-header">
                                        Contribution Receipts
                                    </h6>


                                    @can('contributions.receipts.view')

                                        <a
                                            href="{{ route('pensions-administration.contributions.receipts.index') }}"
                                            class="dropdown-item {{
                                                request()->routeIs('pensions-administration.contributions.receipts.index')
                                                ||
                                                request()->routeIs('pensions-administration.contributions.receipts.show')
                                                    ? 'active'
                                                    : ''
                                            }}"
                                        >
                                            <i class="mdi mdi-receipt-text-outline me-2"></i>

                                            Receipts
                                        </a>


                                        <a
                                            href="{{ route('pensions-administration.contributions.receipts.imports.index') }}"
                                            class="dropdown-item {{
                                                request()->routeIs('pensions-administration.contributions.receipts.imports.*')
                                                    ? 'active'
                                                    : ''
                                            }}"
                                        >
                                            <i class="mdi mdi-file-document-multiple-outline me-2"></i>

                                            Receipt Imports
                                        </a>

                                    @endcan


                                    @can('contributions.receipts.create')

                                        <a
                                            href="{{ route('pensions-administration.contributions.receipts.imports.create') }}"
                                            class="dropdown-item {{
                                                request()->routeIs('pensions-administration.contributions.receipts.imports.create')
                                                    ? 'active'
                                                    : ''
                                            }}"
                                        >
                                            <i class="mdi mdi-upload-outline me-2"></i>

                                            Upload Receipts
                                        </a>

                                    @endcan


                                    @can('contributions.exchange-rates.view')

                                        <a
                                            href="{{ route('pensions-administration.contributions.receipts.exchange-rates.index') }}"
                                            class="dropdown-item {{
                                                request()->routeIs('pensions-administration.contributions.receipts.exchange-rates.*')
                                                    ? 'active'
                                                    : ''
                                            }}"
                                        >
                                            <i class="mdi mdi-currency-usd me-2"></i>

                                            Receipt Exchange Rates
                                        </a>

                                    @endcan

                                @endif


                                {{-- =====================================
                                     HISTORICAL CONTRIBUTIONS
                                ====================================== --}}

                                @if($canManageHistoricalContributions)

                                    <div class="dropdown-divider"></div>

                                    <h6 class="dropdown-header">
                                        Historical Migration
                                    </h6>


                                    <a
                                        href="{{ route('pensions-administration.historical-contributions.imports.index') }}"
                                        class="dropdown-item {{
                                            request()->routeIs('pensions-administration.historical-contributions.imports.index')
                                            ||
                                            request()->routeIs('pensions-administration.historical-contributions.imports.show')
                                            ||
                                            request()->routeIs('pensions-administration.historical-contributions.review.*')
                                            ||
                                            request()->routeIs('pensions-administration.historical-contributions.posting.*')
                                                ? 'active'
                                                : ''
                                        }}"
                                    >
                                        <i class="mdi mdi-database-clock-outline me-2"></i>

                                        Historical Contributions
                                    </a>


                                    <a
                                        href="{{ route('pensions-administration.historical-contributions.imports.create') }}"
                                        class="dropdown-item {{
                                            request()->routeIs('pensions-administration.historical-contributions.imports.create')
                                                ? 'active'
                                                : ''
                                        }}"
                                    >
                                        <i class="mdi mdi-database-import-outline me-2"></i>

                                        Upload Historical Contributions
                                    </a>

                                @endif


                                @if($canProcessContributions)

                                    <div class="dropdown-divider"></div>

                                    <h6 class="dropdown-header">
                                        Processing
                                    </h6>


                                    @can('contributions.monthly-imports.view')

                                        <a
                                            href="{{ route(
                                                'pensions-administration.contributions.imports.index',
                                                ['status' => 'awaiting_review']
                                            ) }}"
                                            class="dropdown-item"
                                        >
                                            <i class="mdi mdi-file-search-outline me-2"></i>

                                            Batches Awaiting Review
                                        </a>

                                    @endcan

                                @endif


                                @if(
                                    $canApproveContributions
                                    ||
                                    $canRejectContributions
                                )

                                    <div class="dropdown-divider"></div>

                                    <h6 class="dropdown-header">
                                        Approval
                                    </h6>


                                    <a
                                        href="{{ route(
                                            'pensions-administration.contributions.imports.index',
                                            ['status' => 'awaiting_review']
                                        ) }}"
                                        class="dropdown-item"
                                    >
                                        <i class="mdi mdi-clipboard-check-outline me-2"></i>

                                        Contribution Approval Queue
                                    </a>


                                    @if($canApproveContributions)

                                        <span class="dropdown-item contribution-permission-hint">
                                            <i class="mdi mdi-check-decagram-outline me-2 text-success"></i>

                                            You Can Approve
                                        </span>

                                    @endif


                                    @if($canRejectContributions)

                                        <span class="dropdown-item contribution-permission-hint">
                                            <i class="mdi mdi-close-circle-outline me-2 text-danger"></i>

                                            You Can Reject
                                        </span>

                                    @endif

                                @endif


                                @if($canPostContributions)

                                    <div class="dropdown-divider"></div>

                                    <h6 class="dropdown-header">
                                        Posting
                                    </h6>


                                    <a
                                        href="{{ route(
                                            'pensions-administration.contributions.imports.index',
                                            ['status' => 'approved']
                                        ) }}"
                                        class="dropdown-item"
                                    >
                                        <i class="mdi mdi-database-arrow-up-outline me-2"></i>

                                        Approved Batches to Post
                                    </a>

                                @endif


                                @if(
                                    $canApproveContributions
                                    ||
                                    $canRejectContributions
                                    ||
                                    $canProcessContributions
                                )

                                    <div class="dropdown-divider"></div>

                                    <a
                                        href="{{ route(
                                            'pensions-administration.contributions.imports.index',
                                            ['status' => 'rejected']
                                        ) }}"
                                        class="dropdown-item"
                                    >
                                        <i class="mdi mdi-file-cancel-outline me-2"></i>

                                        Rejected Batches
                                    </a>

                                @endif


                                @if($canSeeContributionReports)

                                    <div class="dropdown-divider"></div>

                                    <h6 class="dropdown-header">
                                        Contribution Reports
                                    </h6>


                                    <a
                                        href="{{ route(
                                            'pensions-administration.contributions.imports.index',
                                            ['status' => 'posted']
                                        ) }}"
                                        class="dropdown-item"
                                    >
                                        <i class="mdi mdi-file-chart-outline me-2"></i>

                                        Posted Contributions
                                    </a>


                                    <span class="dropdown-item contribution-permission-hint">
                                        <i class="mdi mdi-scale-balance me-2"></i>

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
                                class="nav-link dropdown-toggle arrow-none {{
                                    request()->routeIs('pensions-administration.updates.reports.*')
                                    ||
                                    request()->routeIs('pensions-administration.contributions.reconciliation.*')
                                    ||
                                    request()->routeIs('pensions-administration.reports.actuarial-data.*')
                                        ? 'active'
                                        : ''
                                }}"
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

                                @if(
                                    $canSeeMembershipReports
                                    ||
                                    $canSeeEmployerMembershipReports
                                )

                                    <h6 class="dropdown-header">
                                        Membership Reports
                                    </h6>

                                @endif


                                @if($canSeeMembershipReports)

                                    <a
                                        href="{{ route('pensions-administration.updates.reports.membership.index') }}"
                                        class="dropdown-item {{
                                            request()->routeIs('pensions-administration.updates.reports.membership.*')
                                                ? 'active'
                                                : ''
                                        }}"
                                    >
                                        <i class="mdi mdi-chart-box-outline me-2"></i>

                                        Membership Reports Centre
                                    </a>


                                    <a
                                        href="{{ route('pensions-administration.updates.reports.membership.index') }}#member-register"
                                        class="dropdown-item"
                                    >
                                        <i class="mdi mdi-account-group-outline me-2"></i>

                                        Membership Register
                                    </a>

                                @endif


                                @if($canSeeEmployerMembershipReports)

                                    <a
                                        href="{{ route('pensions-administration.updates.reports.employer-membership.index') }}"
                                        class="dropdown-item {{
                                            request()->routeIs('pensions-administration.updates.reports.employer-membership.*')
                                                ? 'active'
                                                : ''
                                        }}"
                                    >
                                        <i class="mdi mdi-office-building-outline me-2"></i>

                                        Employer Membership Report
                                    </a>

                                @endif


                                @if($canSeeMembershipReports)

                                    <a
                                        href="{{ route('pensions-administration.updates.reports.membership.index') }}#age-profile"
                                        class="dropdown-item"
                                    >
                                        <i class="mdi mdi-calendar-account-outline me-2"></i>

                                        Age Profile
                                    </a>


                                    <a
                                        href="{{ route('pensions-administration.updates.reports.membership.index') }}#legacy-mapping"
                                        class="dropdown-item"
                                    >
                                        <i class="mdi mdi-link-variant me-2"></i>

                                        Legacy Number Mapping
                                    </a>


                                    <a
                                        href="{{ route('pensions-administration.updates.reports.membership.index') }}#data-quality"
                                        class="dropdown-item"
                                    >
                                        <i class="mdi mdi-database-alert-outline me-2"></i>

                                        Data Quality
                                    </a>

                                @endif


                                {{-- =====================================
                                     ACTUARIAL VALUATION REPORTS
                                ====================================== --}}

                                @if($canSeeActuarialReports)

                                    @if(
                                        $canSeeMembershipReports
                                        ||
                                        $canSeeEmployerMembershipReports
                                    )

                                        <div class="dropdown-divider"></div>

                                    @endif


                                    <h6 class="dropdown-header">
                                        Actuarial Valuation
                                    </h6>


                                    <a
                                        href="{{ route('pensions-administration.reports.actuarial-data.index') }}"
                                        class="dropdown-item {{
                                            request()->routeIs('pensions-administration.reports.actuarial-data.*')
                                                ? 'active'
                                                : ''
                                        }}"
                                    >
                                        <i class="mdi mdi-file-excel-outline me-2"></i>

                                        Actuarial Valuation Data Extract
                                    </a>

                                @endif


                                {{-- =====================================
                                     CONTRIBUTION REPORTS
                                ====================================== --}}

                                @if(
                                    (
                                        $canSeeMembershipReports
                                        ||
                                        $canSeeEmployerMembershipReports
                                        ||
                                        $canSeeActuarialReports
                                    )
                                    &&
                                    $canSeeContributionReports
                                )

                                    <div class="dropdown-divider"></div>

                                @endif


                                @if($canSeeContributionReports)

                                    <h6 class="dropdown-header">
                                        Contribution Reports
                                    </h6>


                                    <a
                                        href="{{ route(
                                            'pensions-administration.contributions.imports.index',
                                            ['status' => 'posted']
                                        ) }}"
                                        class="dropdown-item"
                                    >
                                        <i class="mdi mdi-cash-check me-2"></i>

                                        Posted Monthly Contributions
                                    </a>


                                    <a
                                        href="{{ route('pensions-administration.contributions.imports.index') }}"
                                        class="dropdown-item"
                                    >
                                        <i class="mdi mdi-scale-balance me-2"></i>

                                        Contribution Reconciliations
                                    </a>

                                @endif

                            </div>

                        </li>

                    @endif


                    {{-- =================================================
                         SETTINGS
                    ================================================== --}}

                    @if($canSeeBenefitSettings)

                        <li class="nav-item dropdown">

                            <a
                                class="nav-link dropdown-toggle arrow-none {{
                                    request()->routeIs('pensions-administration.settings.*')
                                        ? 'active'
                                        : ''
                                }}"
                                href="javascript:void(0);"
                                id="pensions-settings-menu"
                                role="button"
                            >
                                <i class="mdi mdi-cog-outline me-2"></i>

                                Settings

                                <div class="arrow-down"></div>
                            </a>


                            <div
                                class="dropdown-menu"
                                aria-labelledby="pensions-settings-menu"
                            >

                                {{-- =====================================
                                     SETTINGS DASHBOARD
                                ====================================== --}}

                                <h6 class="dropdown-header">
                                    Benefit Settings
                                </h6>


                                <a
                                    href="{{ route('pensions-administration.settings.index') }}"
                                    class="dropdown-item {{
                                        request()->routeIs('pensions-administration.settings.index')
                                            ? 'active'
                                            : ''
                                    }}"
                                >
                                    <i class="mdi mdi-view-dashboard-outline me-2"></i>

                                    Settings Dashboard
                                </a>


                                {{-- =====================================
                                     GENERAL RULES
                                ====================================== --}}

                                <a
                                    href="{{ route('pensions-administration.settings.general.index') }}"
                                    class="dropdown-item {{
                                        request()->routeIs('pensions-administration.settings.general.*')
                                            ? 'active'
                                            : ''
                                    }}"
                                >
                                    <i class="mdi mdi-tune-variant me-2"></i>

                                    General Benefit Rules
                                </a>


                                {{-- =====================================
                                     WITHDRAWAL RULES
                                ====================================== --}}

                                <a
                                    href="{{ route('pensions-administration.settings.withdrawal-scales.index') }}"
                                    class="dropdown-item {{
                                        request()->routeIs('pensions-administration.settings.withdrawal-scales.*')
                                            ? 'active'
                                            : ''
                                    }}"
                                >
                                    <i class="mdi mdi-format-list-numbered me-2"></i>

                                    Withdrawal Scales
                                </a>


                                <div class="dropdown-divider"></div>


                                {{-- =====================================
                                     ACTUARIAL FACTORS
                                ====================================== --}}

                                <h6 class="dropdown-header">
                                    Actuarial Factors
                                </h6>


                                <a
                                    href="{{ route('pensions-administration.settings.accumulated-interest-factors.index') }}"
                                    class="dropdown-item {{
                                        request()->routeIs('pensions-administration.settings.accumulated-interest-factors.*')
                                            ? 'active'
                                            : ''
                                    }}"
                                >
                                    <i class="mdi mdi-chart-line me-2"></i>

                                    Accumulated Interest Factors
                                </a>


                                <a
                                    href="{{ route('pensions-administration.settings.commutation-factors.index') }}"
                                    class="dropdown-item {{
                                        request()->routeIs('pensions-administration.settings.commutation-factors.*')
                                            ? 'active'
                                            : ''
                                    }}"
                                >
                                    <i class="mdi mdi-calculator-variant-outline me-2"></i>

                                    Commutation Factors
                                </a>


                                <a
                                    href="{{ route('pensions-administration.settings.retirement-increases.index') }}"
                                    class="dropdown-item {{
                                        request()->routeIs('pensions-administration.settings.retirement-increases.*')
                                            ? 'active'
                                            : ''
                                    }}"
                                >
                                    <i class="mdi mdi-trending-up me-2"></i>

                                    Retirement Increase Factors
                                </a>
<a class="dropdown-item {{ request()->routeIs('pensions-administration.settings.interest-rates.*') ? 'active' : '' }}" href="{{ route('pensions-administration.settings.interest-rates.index') }}">Interest Rates</a>

                                <div class="dropdown-divider"></div>


                                {{-- =====================================
                                     TAX AND CURRENCY
                                ====================================== --}}

                                <h6 class="dropdown-header">
                                    Tax & Currency
                                </h6>


                                <a
                                    href="{{ route('pensions-administration.settings.tax-tables.index') }}"
                                    class="dropdown-item {{
                                        request()->routeIs('pensions-administration.settings.tax-tables.*')
                                            ? 'active'
                                            : ''
                                    }}"
                                >
                                    <i class="mdi mdi-table-large me-2"></i>

                                    Tax Tables
                                </a>


                                <a
                                    href="{{ route('pensions-administration.settings.exchange-rates.index') }}"
                                    class="dropdown-item {{
                                        request()->routeIs('pensions-administration.settings.exchange-rates.*')
                                            ? 'active'
                                            : ''
                                    }}"
                                >
                                    <i class="mdi mdi-currency-usd me-2"></i>

                                    Exchange Rates
                                </a>

                            </div>

                        </li>

                    @endif


                    {{-- =================================================
                         RETURN TO ERP
                    ================================================== --}}

                    <li class="nav-item">

                        <a
                            class="nav-link"
                            href="{{ route('dashboard') }}"
                        >
                            <i class="mdi mdi-arrow-left-circle-outline me-2"></i>

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

            .pensions-topnav .contribution-permission-hint {
                cursor: default;
                font-size: 12px;
            }

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
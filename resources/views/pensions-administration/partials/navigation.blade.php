{{-- =========================================================
     PENSIONS ADMINISTRATION CONTEXT NAVIGATION
========================================================= --}}

<div class="topnav pensions-topnav">

    <div class="container-fluid">

        <nav class="navbar navbar-light navbar-expand-lg topnav-menu">

            <button class="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#pensions-topnav-menu"
                    aria-controls="pensions-topnav-menu"
                    aria-expanded="false"
                    aria-label="Toggle navigation">

                <span class="navbar-toggler-icon"></span>

            </button>


            <div class="collapse navbar-collapse"
                 id="pensions-topnav-menu">

                <ul class="navbar-nav">


                    {{-- =================================================
                         PENSIONS HOME
                    ================================================== --}}

                    <li class="nav-item">

                        <a class="nav-link {{ request()->routeIs('pensions-administration.dashboard') ? 'active' : '' }}"
                           href="{{ route('pensions-administration.dashboard') }}">

                            <i class="dripicons-home me-2"></i>

                            Pensions Home

                        </a>

                    </li>


                    {{-- =================================================
                         UPDATES / MEMBERSHIP
                    ================================================== --}}

                    <li class="nav-item dropdown">

                        <a class="nav-link dropdown-toggle arrow-none {{ request()->routeIs('pensions-administration.updates.*') && !request()->routeIs('pensions-administration.updates.reports.*') ? 'active' : '' }}"
                           href="javascript:void(0);"
                           id="pensions-updates-menu"
                           role="button">

                            <i class="dripicons-user-group me-2"></i>

                            Updates / Membership

                            <div class="arrow-down"></div>

                        </a>


                        <div class="dropdown-menu"
                             aria-labelledby="pensions-updates-menu">


                            <a href="{{ route('pensions-administration.updates.dashboard') }}"
                               class="dropdown-item">

                                <i class="mdi mdi-view-dashboard-outline me-2"></i>
                                Updates Dashboard

                            </a>


                            <div class="dropdown-divider"></div>


                            <a href="{{ route('pensions-administration.updates.members.index') }}"
                               class="dropdown-item">

                                <i class="mdi mdi-account-group-outline me-2"></i>
                                Members

                            </a>


                            <a href="{{ route('pensions-administration.updates.employers.index') }}"
                               class="dropdown-item">

                                <i class="mdi mdi-office-building-outline me-2"></i>
                                Employers

                            </a>


                            <a href="{{ route('pensions-administration.updates.employer-groups.index') }}"
                               class="dropdown-item">

                                <i class="mdi mdi-folder-multiple-outline me-2"></i>
                                Employer Groups

                            </a>


                            <div class="dropdown-divider"></div>


                            <span class="dropdown-item text-muted"
                                  style="cursor: default;">

                                <i class="mdi mdi-swap-horizontal me-2"></i>

                                Member Movements

                                <small class="ms-1">
                                    Coming next
                                </small>

                            </span>


                            <div class="dropdown-divider"></div>


                            <h6 class="dropdown-header">
                                Excel Imports
                            </h6>


                            <a href="{{ route('pensions-administration.updates.imports.index') }}"
                               class="dropdown-item">

                                <i class="mdi mdi-account-arrow-up-outline me-2"></i>
                                Member Imports

                            </a>


                            <a href="{{ route('pensions-administration.updates.employer-imports.index') }}"
                               class="dropdown-item">

                                <i class="mdi mdi-office-building-cog-outline me-2"></i>
                                Employer Imports

                            </a>

                        </div>

                    </li>


                    {{-- =================================================
                         PAYROLL
                    ================================================== --}}

                    <li class="nav-item">

                        <a class="nav-link text-muted"
                           href="javascript:void(0);"
                           title="Payroll module will be implemented next">

                            <i class="dripicons-wallet me-2"></i>

                            Payroll

                            <span class="badge bg-soft-secondary text-secondary ms-1">
                                Next
                            </span>

                        </a>

                    </li>


                    {{-- =================================================
                         BENEFIT CLAIMS
                    ================================================== --}}

                    <li class="nav-item">

                        <a class="nav-link text-muted"
                           href="javascript:void(0);"
                           title="Benefit Claims module is not yet implemented">

                            <i class="dripicons-document me-2"></i>

                            Benefit Claims

                            <span class="badge bg-soft-secondary text-secondary ms-1">
                                Later
                            </span>

                        </a>

                    </li>


                    {{-- =================================================
                         REPORTS
                    ================================================== --}}

                    <li class="nav-item dropdown">

                        <a class="nav-link dropdown-toggle arrow-none {{ request()->routeIs('pensions-administration.updates.reports.*') ? 'active' : '' }}"
                           href="javascript:void(0);"
                           id="pensions-reports-menu"
                           role="button">

                            <i class="dripicons-graph-bar me-2"></i>

                            Reports

                            <div class="arrow-down"></div>

                        </a>


                        <div class="dropdown-menu"
                             aria-labelledby="pensions-reports-menu">


                            <h6 class="dropdown-header">
                                Membership Reports
                            </h6>


                            <a href="{{ route('pensions-administration.updates.reports.membership.index') }}"
                               class="dropdown-item">

                                <i class="mdi mdi-chart-box-outline me-2"></i>
                                Membership Reports Centre

                            </a>


                            <a href="{{ route('pensions-administration.updates.reports.membership.index') }}#member-register"
                               class="dropdown-item">

                                <i class="mdi mdi-account-group-outline me-2"></i>
                                Membership Register

                            </a>


                            <a href="{{ route('pensions-administration.updates.reports.membership.index') }}#employer-summary"
                               class="dropdown-item">

                                <i class="mdi mdi-office-building-outline me-2"></i>
                                Employer Membership

                            </a>


                            <a href="{{ route('pensions-administration.updates.reports.membership.index') }}#age-profile"
                               class="dropdown-item">

                                <i class="mdi mdi-calendar-account-outline me-2"></i>
                                Age Profile

                            </a>


                            <a href="{{ route('pensions-administration.updates.reports.membership.index') }}#legacy-mapping"
                               class="dropdown-item">

                                <i class="mdi mdi-link-variant me-2"></i>
                                Legacy Number Mapping

                            </a>


                            <a href="{{ route('pensions-administration.updates.reports.membership.index') }}#data-quality"
                               class="dropdown-item">

                                <i class="mdi mdi-database-alert-outline me-2"></i>
                                Data Quality

                            </a>


                            <div class="dropdown-divider"></div>


                            <span class="dropdown-item text-muted">

                                <i class="mdi mdi-swap-horizontal me-2"></i>

                                Movement Reports

                                <small class="ms-1">
                                    Later
                                </small>

                            </span>

                        </div>

                    </li>


                    {{-- =================================================
                         RETURN TO ERP
                    ================================================== --}}

                    <li class="nav-item">

                        <a class="nav-link"
                           href="{{ route('dashboard') }}">

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

            body.lapf-dark-mode .pensions-topnav {
                border-bottom: 1px solid rgba(255, 255, 255, .08);
            }

        </style>

    @endpush

@endonce
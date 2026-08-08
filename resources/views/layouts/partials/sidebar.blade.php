<div class="vertical-menu">

    <div data-simplebar class="h-100">


        {{-- =====================================================
             LOGGED IN USER
        ====================================================== --}}
        <div class="user-sidebar text-center">


            <div class="dropdown">


                <div class="user-img">


                    <div
                        class="rounded-circle mx-auto
                               d-flex align-items-center
                               justify-content-center
                               bg-primary text-white"
                        style="
                            width:64px;
                            height:64px;
                            font-size:24px;
                        "
                    >

                        {{ strtoupper(
                            substr(
                                auth()->user()->first_name ?? 'U',
                                0,
                                1
                            )
                        ) }}

                    </div>


                    <span class="avatar-online bg-success"></span>


                </div>


                <div class="user-info">


                    <h5 class="mt-3 font-size-16 text-white">

                        {{ auth()->user()->full_name ?? 'System User' }}

                    </h5>


                    <span class="font-size-13 text-white-50">

                        {{ auth()->user()->jobTitle?->name ?? 'User' }}

                    </span>


                </div>


            </div>


        </div>



        {{-- =====================================================
             SIDEBAR MENU
        ====================================================== --}}
        <div id="sidebar-menu">


            <ul
                class="metismenu list-unstyled"
                id="side-menu"
            >


                <li class="menu-title">
                    Menu
                </li>



                {{-- =================================================
                     DASHBOARD
                ================================================== --}}
                <li>


                    <a
                        href="{{ route('dashboard') }}"
                        class="waves-effect"
                    >

                        <i class="dripicons-home"></i>

                        <span>
                            Dashboard
                        </span>

                    </a>


                </li>



                {{-- =================================================
                     DASHBOARDS
                ================================================== --}}
                @if(
                    auth()->user()->is_system_administrator
                    ||
                    auth()->user()->can('dashboard.finance.view')
                    ||
                    auth()->user()->can('dashboard.pensions.view')
                    ||
                    auth()->user()->can('dashboard.property.view')
                    ||
                    auth()->user()->can('dashboard.principal-office.view')
                    ||
                    auth()->user()->can('dashboard.system-administration.view')
                )


                    <li>


                        <a
                            href="javascript: void(0);"
                            class="has-arrow waves-effect"
                        >

                            <i class="dripicons-browser"></i>

                            <span>
                                Dashboards
                            </span>

                        </a>


                        <ul
                            class="sub-menu"
                            aria-expanded="false"
                        >


                            {{-- Finance --}}
                            @if(
                                auth()->user()->is_system_administrator
                                ||
                                auth()->user()->can('dashboard.finance.view')
                            )

                                <li>

                                    <a
                                        href="{{ route('dashboard.finance') }}"
                                    >
                                        Finance
                                    </a>

                                </li>

                            @endif


                            {{-- Pensions Administration --}}
                            @if(
                                auth()->user()->is_system_administrator
                                ||
                                auth()->user()->can('dashboard.pensions.view')
                            )

                                <li>

                                    <a
                                        href="{{ route(
                                            'pensions-administration.dashboard'
                                        ) }}"
                                    >
                                        Pensions Administration
                                    </a>

                                </li>

                            @endif


                            {{-- Property --}}
                            @if(
                                auth()->user()->is_system_administrator
                                ||
                                auth()->user()->can('dashboard.property.view')
                            )

                                <li>

                                    <a
                                        href="{{ route('dashboard.property') }}"
                                    >
                                        Property
                                    </a>

                                </li>

                            @endif


                            {{-- Principal Officer --}}
                            @if(
                                auth()->user()->is_system_administrator
                                ||
                                auth()->user()->can('dashboard.principal-office.view')
                            )

                                <li>

                                    <a
                                        href="{{ route(
                                            'dashboard.principal-office'
                                        ) }}"
                                    >
                                        Principal Officer
                                    </a>

                                </li>

                            @endif


                            {{-- System Administration --}}
                            @if(
                                auth()->user()->is_system_administrator
                                ||
                                auth()->user()->can(
                                    'dashboard.system-administration.view'
                                )
                            )

                                <li>

                                    <a
                                        href="{{ route(
                                            'dashboard.system-administration'
                                        ) }}"
                                    >
                                        System Administration
                                    </a>

                                </li>

                            @endif


                        </ul>


                    </li>


                @endif



                {{-- =================================================
                     ADMINISTRATION HEADING
                ================================================== --}}
                @if(
                    auth()->user()->is_system_administrator
                    ||
                    auth()->user()->can('user-management.users.view')
                    ||
                    auth()->user()->can('user-management.roles.view')
                    ||
                    auth()->user()->can('user-management.permissions.view')
                    ||
                    auth()->user()->can(
                        'user-management.organisation-units.view'
                    )
                    ||
                    auth()->user()->can(
                        'user-management.job-titles.view'
                    )
                    ||
                    auth()->user()->can(
                        'user-management.grades.view'
                    )
                    ||
                    auth()->user()->can(
                        'user-management.password-policies.view'
                    )
                )


                    <li class="menu-title">
                        Administration
                    </li>



                    {{-- =============================================
                         USER MANAGEMENT
                    ============================================== --}}
                    <li>


                        <a
                            href="javascript: void(0);"
                            class="has-arrow waves-effect"
                        >

                            <i class="dripicons-user-group"></i>

                            <span>
                                User Management
                            </span>

                        </a>


                        <ul
                            class="sub-menu"
                            aria-expanded="false"
                        >


                            {{-- Users --}}
                            @if(
                                auth()->user()->is_system_administrator
                                ||
                                auth()->user()->can(
                                    'user-management.users.view'
                                )
                            )

                                <li>

                                    <a
                                        href="{{ route(
                                            'user-management.users.index'
                                        ) }}"
                                    >
                                        Users
                                    </a>

                                </li>

                            @endif


                            {{-- Roles --}}
                            @if(
                                auth()->user()->is_system_administrator
                                ||
                                auth()->user()->can(
                                    'user-management.roles.view'
                                )
                            )

                                <li>

                                    <a
                                        href="{{ route(
                                            'user-management.roles.index'
                                        ) }}"
                                    >
                                        Roles
                                    </a>

                                </li>

                            @endif


                            {{-- Permissions --}}
                            @if(
                                auth()->user()->is_system_administrator
                                ||
                                auth()->user()->can(
                                    'user-management.permissions.view'
                                )
                            )

                                <li>

                                    <a
                                        href="{{ route(
                                            'user-management.permissions.index'
                                        ) }}"
                                    >
                                        Permissions
                                    </a>

                                </li>

                            @endif


                            {{-- Organisation Structure --}}
                            @if(
                                auth()->user()->is_system_administrator
                                ||
                                auth()->user()->can(
                                    'user-management.organisation-units.view'
                                )
                            )

                                <li>

                                    <a
                                        href="{{ route(
                                            'user-management.organisation-units.index'
                                        ) }}"
                                    >
                                        Organisation Structure
                                    </a>

                                </li>

                            @endif


                            {{-- Job Titles --}}
                            @if(
                                auth()->user()->is_system_administrator
                                ||
                                auth()->user()->can(
                                    'user-management.job-titles.view'
                                )
                            )

                                <li>

                                    <a
                                        href="{{ route(
                                            'user-management.job-titles.index'
                                        ) }}"
                                    >
                                        Job Titles
                                    </a>

                                </li>

                            @endif


                            {{-- Grades --}}
                            @if(
                                auth()->user()->is_system_administrator
                                ||
                                auth()->user()->can(
                                    'user-management.grades.view'
                                )
                            )

                                <li>

                                    <a
                                        href="{{ route(
                                            'user-management.grades.index'
                                        ) }}"
                                    >
                                        Grades
                                    </a>

                                </li>

                            @endif


                            {{-- Password Policy --}}
                            @if(
                                auth()->user()->is_system_administrator
                                ||
                                auth()->user()->can(
                                    'user-management.password-policies.view'
                                )
                            )

                                <li>

                                    <a
                                        href="{{ route(
                                            'user-management.password-policies.edit'
                                        ) }}"
                                    >
                                        Password Policy
                                    </a>

                                </li>

                            @endif


                        </ul>


                    </li>


                @endif



                {{-- =================================================
                     AUDIT & SECURITY
                ================================================== --}}
                @if(
                    auth()->user()->is_system_administrator
                    ||
                    auth()->user()->can('audit.audit-trails.view')
                    ||
                    auth()->user()->can('audit.user-sessions.view')
                    ||
                    auth()->user()->can('audit.login-attempts.view')
                )


                    <li>


                        <a
                            href="javascript: void(0);"
                            class="has-arrow waves-effect"
                        >

                            <i class="dripicons-document"></i>

                            <span>
                                Audit & Security
                            </span>

                        </a>


                        <ul
                            class="sub-menu"
                            aria-expanded="false"
                        >


                            {{-- Audit Trail --}}
                            @if(
                                auth()->user()->is_system_administrator
                                ||
                                auth()->user()->can(
                                    'audit.audit-trails.view'
                                )
                            )

                                <li>

                                    <a
                                        href="{{ route(
                                            'audit.audit-trails.index'
                                        ) }}"
                                    >
                                        Audit Trail
                                    </a>

                                </li>

                            @endif


                            {{-- User Sessions --}}
                            @if(
                                auth()->user()->is_system_administrator
                                ||
                                auth()->user()->can(
                                    'audit.user-sessions.view'
                                )
                            )

                                <li>

                                    <a
                                        href="{{ route(
                                            'audit.user-sessions.index'
                                        ) }}"
                                    >
                                        User Sessions
                                    </a>

                                </li>

                            @endif


                            {{-- Login Attempts --}}
                            @if(
                                auth()->user()->is_system_administrator
                                ||
                                auth()->user()->can(
                                    'audit.login-attempts.view'
                                )
                            )

                                <li>

                                    <a
                                        href="{{ route(
                                            'audit.login-attempts.index'
                                        ) }}"
                                    >
                                        Login Attempts
                                    </a>

                                </li>

                            @endif


                        </ul>


                    </li>


                @endif


            </ul>


        </div>


    </div>

</div>
<aside class="main-sidebar">

    {{-- Brand --}}
    <div class="sidebar-brand">

        <a href="{{ route('dashboard') }}">

            <div class="brand-icon">
                <i class="bi bi-bank"></i>
            </div>

            <div class="brand-text">
                <strong>LAPF</strong>
                <span>Pension Fund System</span>
            </div>

        </a>

    </div>


    {{-- Logged In User --}}
    @auth

        <div class="sidebar-user">

            <div class="sidebar-user-avatar">
                {{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}
                {{ strtoupper(substr(auth()->user()->surname, 0, 1)) }}
            </div>

            <div class="sidebar-user-info">

                <strong>
                    {{ auth()->user()->first_name }}
                    {{ auth()->user()->surname }}
                </strong>

                <span>
                    {{ auth()->user()->jobTitle?->name ?? 'System User' }}
                </span>

            </div>

        </div>

    @endauth


    <nav class="sidebar-navigation">

        <ul class="sidebar-menu">


            {{-- Dashboard --}}
            <li class="sidebar-heading">
                Navigation
            </li>


            <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">

                <a href="{{ route('dashboard') }}">

                    <i class="bi bi-grid"></i>

                    <span>
                        Dashboard
                    </span>

                </a>

            </li>


            {{-- Finance --}}
            @can('dashboard.finance.view')

                <li class="{{ request()->routeIs('dashboard.finance') ? 'active' : '' }}">

                    <a href="{{ route('dashboard.finance') }}">

                        <i class="bi bi-cash-stack"></i>

                        <span>
                            Finance
                        </span>

                    </a>

                </li>

            @endcan


            {{-- Pensions --}}
            @can('dashboard.pensions.view')

                <li class="{{ request()->routeIs('dashboard.pensions') ? 'active' : '' }}">

                    <a href="{{ route('dashboard.pensions') }}">

                        <i class="bi bi-people"></i>

                        <span>
                            Pensions Administration
                        </span>

                    </a>

                </li>

            @endcan


            {{-- Property --}}
            @can('dashboard.property.view')

                <li class="{{ request()->routeIs('dashboard.property') ? 'active' : '' }}">

                    <a href="{{ route('dashboard.property') }}">

                        <i class="bi bi-buildings"></i>

                        <span>
                            Property
                        </span>

                    </a>

                </li>

            @endcan


            {{-- Principal Office --}}
            @can('dashboard.principal-office.view')

                <li class="{{ request()->routeIs('dashboard.principal-office') ? 'active' : '' }}">

                    <a href="{{ route('dashboard.principal-office') }}">

                        <i class="bi bi-briefcase"></i>

                        <span>
                            Principal Officer
                        </span>

                    </a>

                </li>

            @endcan


            {{-- System Administration --}}
            @can('dashboard.system-administration.view')

                <li class="{{ request()->routeIs('dashboard.system-administration') ? 'active' : '' }}">

                    <a href="{{ route('dashboard.system-administration') }}">

                        <i class="bi bi-gear"></i>

                        <span>
                            System Administration
                        </span>

                    </a>

                </li>

            @endcan



            {{-- User Management --}}
            @canany([
                'user-management.users.view',
                'user-management.roles.view',
                'user-management.permissions.view',
                'user-management.organisation-units.view',
                'user-management.job-titles.view',
                'user-management.grades.view',
                'user-management.password-policies.view'
            ])

                <li class="sidebar-heading">
                    User Management
                </li>

            @endcanany


            @can('user-management.users.view')

                <li class="{{ request()->routeIs('user-management.users.*') ? 'active' : '' }}">

                    <a href="{{ route('user-management.users.index') }}">

                        <i class="bi bi-people-fill"></i>

                        <span>
                            Users
                        </span>

                    </a>

                </li>

            @endcan


            @can('user-management.roles.view')

                <li class="{{ request()->routeIs('user-management.roles.*') ? 'active' : '' }}">

                    <a href="{{ route('user-management.roles.index') }}">

                        <i class="bi bi-person-badge"></i>

                        <span>
                            Roles
                        </span>

                    </a>

                </li>

            @endcan


            @can('user-management.permissions.view')

                <li class="{{ request()->routeIs('user-management.permissions.*') ? 'active' : '' }}">

                    <a href="{{ route('user-management.permissions.index') }}">

                        <i class="bi bi-shield-lock"></i>

                        <span>
                            Permissions
                        </span>

                    </a>

                </li>

            @endcan


            @can('user-management.organisation-units.view')

                <li class="{{ request()->routeIs('user-management.organisation-units.*') ? 'active' : '' }}">

                    <a href="{{ route('user-management.organisation-units.index') }}">

                        <i class="bi bi-diagram-3"></i>

                        <span>
                            Organisation Structure
                        </span>

                    </a>

                </li>

            @endcan


            @can('user-management.job-titles.view')

                <li class="{{ request()->routeIs('user-management.job-titles.*') ? 'active' : '' }}">

                    <a href="{{ route('user-management.job-titles.index') }}">

                        <i class="bi bi-briefcase-fill"></i>

                        <span>
                            Job Titles
                        </span>

                    </a>

                </li>

            @endcan


            @can('user-management.grades.view')

                <li class="{{ request()->routeIs('user-management.grades.*') ? 'active' : '' }}">

                    <a href="{{ route('user-management.grades.index') }}">

                        <i class="bi bi-bar-chart-steps"></i>

                        <span>
                            Grades
                        </span>

                    </a>

                </li>

            @endcan


            @can('user-management.password-policies.view')

                <li class="{{ request()->routeIs('user-management.password-policies.*') ? 'active' : '' }}">

                    <a href="{{ route('user-management.password-policies.edit') }}">

                        <i class="bi bi-key-fill"></i>

                        <span>
                            Password Policy
                        </span>

                    </a>

                </li>

            @endcan



            {{-- Audit --}}
            @canany([
                'audit.audit-trails.view',
                'audit.user-sessions.view',
                'audit.login-attempts.view'
            ])

                <li class="sidebar-heading">
                    Audit & Security
                </li>

            @endcanany


            @can('audit.audit-trails.view')

                <li class="{{ request()->routeIs('audit.audit-trails.*') ? 'active' : '' }}">

                    <a href="{{ route('audit.audit-trails.index') }}">

                        <i class="bi bi-clock-history"></i>

                        <span>
                            Audit Trail
                        </span>

                    </a>

                </li>

            @endcan


            @can('audit.user-sessions.view')

                <li class="{{ request()->routeIs('audit.user-sessions.*') ? 'active' : '' }}">

                    <a href="{{ route('audit.user-sessions.index') }}">

                        <i class="bi bi-pc-display"></i>

                        <span>
                            User Sessions
                        </span>

                    </a>

                </li>

            @endcan


            @can('audit.login-attempts.view')

                <li class="{{ request()->routeIs('audit.login-attempts.*') ? 'active' : '' }}">

                    <a href="{{ route('audit.login-attempts.index') }}">

                        <i class="bi bi-shield-exclamation"></i>

                        <span>
                            Login Attempts
                        </span>

                    </a>

                </li>

            @endcan


            {{--
            |--------------------------------------------------------------------------
            | Future Pension Modules
            |--------------------------------------------------------------------------
            |
            | Do NOT add claims.claims.view here yet unless that permission
            | has actually been created in ModulePermissionSeeder.
            |
            --}}

        </ul>

    </nav>

</aside>
<header id="page-topbar">

    <div class="navbar-header">


        <div class="d-flex">


            {{-- LOGO --}}
            <div class="navbar-brand-box">


                <a
                    href="{{ route('dashboard') }}"
                    class="logo logo-dark"
                >

                    <span class="logo-sm">

                        <img
                            src="{{ asset('layouts/assets/images/logo-sm.png') }}"
                            alt="LAPF"
                            height="22"
                        >

                    </span>


                    <span class="logo-lg">

                        <img
                            src="{{ asset('layouts/assets/images/logo-dark.png') }}"
                            alt="LAPF"
                            height="20"
                        >

                    </span>

                </a>


                <a
                    href="{{ route('dashboard') }}"
                    class="logo logo-light"
                >

                    <span class="logo-sm">

                        <img
                            src="{{ asset('layouts/assets/images/logo-sm.png') }}"
                            alt="LAPF"
                            height="22"
                        >

                    </span>


                    <span class="logo-lg">

                        <img
                            src="{{ asset('layouts/assets/images/logo-light.png') }}"
                            alt="LAPF"
                            height="20"
                        >

                    </span>

                </a>


            </div>


            {{-- Sidebar toggle --}}
            <button
                type="button"
                class="btn btn-sm px-3 font-size-24 header-item waves-effect"
                id="vertical-menu-btn"
            >

                <i class="mdi mdi-menu"></i>

            </button>


        </div>



        {{-- Search --}}
        <div
            class="search-wrap"
            id="search-wrap"
        >

            <div class="search-bar">

                <input
                    class="search-input form-control"
                    placeholder="Search"
                >


                <a
                    href="#"
                    class="close-search toggle-search"
                    data-target="#search-wrap"
                >

                    <i class="mdi mdi-close-circle"></i>

                </a>

            </div>

        </div>



        <div class="d-flex">


            {{-- Search --}}
            <div class="dropdown d-none d-lg-inline-block">

                <button
                    type="button"
                    class="btn header-item toggle-search noti-icon waves-effect"
                    data-target="#search-wrap"
                >

                    <i class="mdi mdi-magnify"></i>

                </button>

            </div>



            {{-- Full Screen --}}
            <div class="dropdown d-none d-lg-inline-block ms-1">

                <button
                    type="button"
                    class="btn header-item noti-icon waves-effect"
                    data-toggle="fullscreen"
                >

                    <i class="mdi mdi-fullscreen"></i>

                </button>

            </div>



            {{-- Notifications --}}
            <div class="dropdown d-inline-block">

                <button
                    type="button"
                    class="btn header-item noti-icon waves-effect"
                    id="page-header-notifications-dropdown"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                >

                    <i class="mdi mdi-bell-outline"></i>

                </button>


                <div
                    class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                    aria-labelledby="page-header-notifications-dropdown"
                >


                    <div class="p-3">

                        <div class="row align-items-center">

                            <div class="col">

                                <h6 class="m-0">
                                    Notifications
                                </h6>

                            </div>

                        </div>

                    </div>


                    <div class="p-4 text-center border-top">

                        <i
                            class="mdi mdi-bell-outline
                                   font-size-24
                                   text-muted"
                        ></i>


                        <p class="text-muted mt-2 mb-0">
                            No notifications at the moment.
                        </p>

                    </div>


                </div>

            </div>



            {{-- User Profile --}}
            <div class="dropdown d-inline-block">


                <button
                    type="button"
                    class="btn header-item waves-effect"
                    id="page-header-user-dropdown"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                >


                    <span
                        class="rounded-circle header-profile-user
                               d-inline-flex align-items-center
                               justify-content-center
                               bg-primary text-white"
                        style="width:36px;height:36px;"
                    >

                        {{ strtoupper(
                            substr(
                                auth()->user()->first_name ?? 'U',
                                0,
                                1
                            )
                        ) }}

                    </span>


                    <span class="d-none d-xl-inline-block ms-1">

                        {{ auth()->user()->first_name ?? 'User' }}

                    </span>


                    <i
                        class="mdi mdi-chevron-down
                               d-none d-xl-inline-block"
                    ></i>


                </button>


                <div class="dropdown-menu dropdown-menu-end">


                    <div class="dropdown-item-text">

                        <strong>
                            {{ auth()->user()->full_name ?? '' }}
                        </strong>

                        <br>

                        <small class="text-muted">

                            {{ auth()->user()->jobTitle?->name ?? '' }}

                        </small>

                    </div>


                    <div class="dropdown-divider"></div>


                    <a
                        class="dropdown-item"
                        href="#"
                    >

                        <i
                            class="mdi mdi-account-circle-outline
                                   font-size-16
                                   align-middle me-1"
                        ></i>

                        Profile

                    </a>


                    <div class="dropdown-divider"></div>


                    <form
                        method="POST"
                        action="{{ route('logout') }}"
                    >

                        @csrf


                        <button
                            type="submit"
                            class="dropdown-item text-danger"
                        >

                            <i
                                class="mdi mdi-power
                                       font-size-16
                                       align-middle me-1
                                       text-danger"
                            ></i>

                            Logout

                        </button>


                    </form>


                </div>

            </div>



            {{-- Settings --}}
            <div class="dropdown d-inline-block">
                        <button type="button" class="btn header-item noti-icon right-bar-toggle waves-effect">
                            <i class="mdi mdi-cog-outline font-size-20"></i>
                        </button>
                    </div>


        </div>

    </div>

</header>
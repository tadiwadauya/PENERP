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
                            height="40"
                        >

                    </span>


                    <span class="logo-lg">

                        <img
                            src="{{ asset('layouts/assets/images/logo-dark.png') }}"
                            alt="LAPF"
                            height="40"
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

@php

    $headerNotifications =
        auth()
            ->user()
            ->notifications()
            ->reorder()
            ->orderByDesc(
                'created_at'
            )
            ->limit(10)
            ->get();


    $unreadNotificationCount =
        auth()
            ->user()
            ->unreadNotifications()
            ->count();

@endphp


<div class="dropdown d-inline-block">

    <button
        type="button"
        class="btn header-item noti-icon waves-effect position-relative"
        id="page-header-notifications-dropdown"
        data-bs-toggle="dropdown"
        aria-haspopup="true"
        aria-expanded="false"
    >

        <i class="mdi mdi-bell-outline"></i>


        @if(
            $unreadNotificationCount > 0
        )

            <span
                class="
                    badge
                    bg-danger
                    rounded-pill
                "
                style="
                    position:absolute;
                    top:7px;
                    right:5px;
                    font-size:10px;
                "
            >

                {{
                    $unreadNotificationCount > 99
                        ? '99+'
                        : $unreadNotificationCount
                }}

            </span>

        @endif

    </button>


    <div
        class="
            dropdown-menu
            dropdown-menu-lg
            dropdown-menu-end
            p-0
        "
        aria-labelledby="page-header-notifications-dropdown"
        style="min-width:360px;"
    >

        <div class="p-3 border-bottom">

            <div class="row align-items-center">

                <div class="col">

                    <h6 class="m-0">
                        Notifications
                    </h6>

                    <small class="text-muted">

                        {{
                            number_format(
                                $unreadNotificationCount
                            )
                        }}

                        unread

                    </small>

                </div>


                @if(
                    $unreadNotificationCount > 0
                )

                    <div class="col-auto">

                        <form
                            method="POST"
                            action="{{
                                route(
                                    'notifications.mark-all-read'
                                )
                            }}"
                        >

                            @csrf

                            <button
                                type="submit"
                                class="
                                    btn
                                    btn-sm
                                    btn-link
                                    text-decoration-none
                                "
                            >

                                Mark all read

                            </button>

                        </form>

                    </div>

                @endif

            </div>

        </div>


        @if(
            $headerNotifications->isEmpty()
        )

            <div class="p-4 text-center">

                <i
                    class="
                        mdi
                        mdi-bell-outline
                        font-size-24
                        text-muted
                    "
                ></i>


                <p class="text-muted mt-2 mb-0">

                    No notifications at the moment.

                </p>

            </div>

        @else

            <div
                style="
                    max-height:420px;
                    overflow-y:auto;
                "
            >

                @foreach(
                    $headerNotifications
                    as $notification
                )

                    @php

                        $notificationData =
                            $notification->data
                            ?? [];


                        $isUnread =
                            is_null(
                                $notification->read_at
                            );

                    @endphp


                    <a
                        href="{{
                            route(
                                'notifications.open',
                                $notification->id
                            )
                        }}"
                        class="
                            dropdown-item
                            text-wrap
                            border-bottom
                            py-3
                            {{
                                $isUnread
                                    ? 'bg-light'
                                    : ''
                            }}
                        "
                    >

                        <div class="d-flex">

                            <div class="flex-shrink-0 me-3">

                                <span
                                    class="
                                        rounded-circle
                                        bg-danger
                                        d-inline-flex
                                        align-items-center
                                        justify-content-center
                                        text-white
                                    "
                                    style="
                                        width:40px;
                                        height:40px;
                                    "
                                >

                                    <i
                                        class="
                                            mdi
                                            mdi-alert-circle-outline
                                            font-size-18
                                        "
                                    ></i>

                                </span>

                            </div>


                            <div class="flex-grow-1">

                                <h6 class="mb-1">

                                    {{
                                        $notificationData[
                                            'title'
                                        ]
                                        ??
                                        'Notification'
                                    }}


                                    @if($isUnread)

                                        <span
                                            class="
                                                badge
                                                bg-primary
                                                ms-1
                                            "
                                        >

                                            New

                                        </span>

                                    @endif

                                </h6>


                                <p
                                    class="
                                        text-muted
                                        font-size-13
                                        mb-1
                                    "
                                >

                                    {{
                                        $notificationData[
                                            'message'
                                        ]
                                        ??
                                        ''
                                    }}

                                </p>


                                @if(
                                    filled(
                                        $notificationData[
                                            'reason'
                                        ]
                                        ??
                                        null
                                    )
                                )

                                    <p
                                        class="
                                            text-danger
                                            font-size-12
                                            mb-1
                                        "
                                    >

                                        <strong>
                                            Reason:
                                        </strong>

                                        {{
                                            $notificationData[
                                                'reason'
                                            ]
                                        }}

                                    </p>

                                @endif


                                <small class="text-muted">

                                    <i
                                        class="
                                            mdi
                                            mdi-clock-outline
                                            me-1
                                        "
                                    ></i>

                                    {{
                                        $notification
                                            ->created_at
                                            ->diffForHumans()
                                    }}

                                </small>

                            </div>

                        </div>

                    </a>

                @endforeach

            </div>

        @endif

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
    href="{{ route('profile.show') }}"
>
    <i
        class="mdi mdi-account-circle-outline
               font-size-16
               align-middle
               me-1"
    ></i>

    My Profile
</a>


<a
    class="dropdown-item"
    href="{{ route('password.change') }}"
>
    <i
        class="mdi mdi-lock-outline
               font-size-16
               align-middle
               me-1"
    ></i>

    Change Password
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
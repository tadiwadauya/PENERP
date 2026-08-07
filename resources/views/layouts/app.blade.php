<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <title>
        @yield('title', 'LAPF Pension Fund System')
    </title>

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <meta
        name="description"
        content="LAPF Pension Administration System"
    >

    <meta
        name="author"
        content="Local Authorities Pension Fund"
    >


    {{-- Favicon --}}
    <link
        rel="shortcut icon"
        href="{{ asset('layouts/assets/images/favicon.ico') }}"
    >


    {{-- Bootstrap CSS --}}
    <link
        href="{{ asset('layouts/assets/css/bootstrap.min.css') }}"
        id="bootstrap-style"
        rel="stylesheet"
        type="text/css"
    >


    {{-- Icons --}}
    <link
        href="{{ asset('layouts/assets/css/icons.min.css') }}"
        rel="stylesheet"
        type="text/css"
    >


    {{-- Morvin CSS --}}
    <link
        href="{{ asset('layouts/assets/css/app.min.css') }}"
        id="app-style"
        rel="stylesheet"
        type="text/css"
    >


    @stack('styles')


    <style>

        /*
        |--------------------------------------------------------------------------
        | DARK MODE - PAGE
        |--------------------------------------------------------------------------
        */

        body.lapf-dark-mode {
            background-color: #1c1f26;
            color: #d9e0e7;
        }

        body.lapf-dark-mode .main-content {
            background-color: #1c1f26;
        }

        body.lapf-dark-mode .page-content {
            background-color: #1c1f26;
        }

        body.lapf-dark-mode .page-content-wrapper {
            background-color: transparent;
        }


        /*
        |--------------------------------------------------------------------------
        | DARK MODE - PAGE TITLE
        |--------------------------------------------------------------------------
        */

        body.lapf-dark-mode .page-title-box {
            background-color: #242832;
        }

        body.lapf-dark-mode .page-title h4 {
            color: #ffffff;
        }

        body.lapf-dark-mode .breadcrumb-item,
        body.lapf-dark-mode .breadcrumb-item a {
            color: #aeb7c2;
        }

        body.lapf-dark-mode .breadcrumb-item.active {
            color: #ffffff;
        }


        /*
        |--------------------------------------------------------------------------
        | DARK MODE - TOPBAR
        |--------------------------------------------------------------------------
        */

        body.lapf-dark-mode #page-topbar {
            background-color: #242832;
            border-color: #303641;
        }

        body.lapf-dark-mode .navbar-header {
            background-color: #242832;
        }

        body.lapf-dark-mode .header-item {
            color: #d9e0e7;
        }

        body.lapf-dark-mode .header-item:hover {
            color: #ffffff;
        }

        body.lapf-dark-mode .topbar-social-icon a {
            color: #aeb7c2;
        }


        /*
        |--------------------------------------------------------------------------
        | DARK MODE - LEFT SIDEBAR
        |--------------------------------------------------------------------------
        |
        | Important:
        | We only change colours here.
        | We do NOT change widths, margins, heights or positioning.
        |
        */

        body.lapf-dark-mode .vertical-menu {
            background-color: #20242c;
            border-right-color: #303641;
        }

        body.lapf-dark-mode .user-sidebar {
            background-color: #20242c;
        }

        body.lapf-dark-mode .user-sidebar .user-info h5 {
            color: #ffffff !important;
        }

        body.lapf-dark-mode .user-sidebar .user-info span {
            color: #9ba6b2 !important;
        }

        body.lapf-dark-mode #sidebar-menu {
            background-color: #20242c;
        }

        body.lapf-dark-mode #sidebar-menu .menu-title {
            color: #6f7b89;
        }

        body.lapf-dark-mode #sidebar-menu ul li a {
            color: #aeb7c2;
        }

        body.lapf-dark-mode #sidebar-menu ul li a i {
            color: #8f9aa7;
        }

        body.lapf-dark-mode #sidebar-menu ul li a:hover {
            color: #ffffff;
        }

        body.lapf-dark-mode #sidebar-menu ul li a:hover i {
            color: #ffffff;
        }

        body.lapf-dark-mode #sidebar-menu ul li.mm-active > a {
            color: #ffffff;
            background-color: #292f39;
        }

        body.lapf-dark-mode #sidebar-menu ul li.mm-active > a i {
            color: #ffffff;
        }

        body.lapf-dark-mode #sidebar-menu ul li ul.sub-menu {
            background-color: #20242c;
        }

        body.lapf-dark-mode #sidebar-menu ul li ul.sub-menu li a {
            color: #8f9aa7;
        }

        body.lapf-dark-mode #sidebar-menu ul li ul.sub-menu li a:hover {
            color: #ffffff;
        }

        body.lapf-dark-mode #sidebar-menu ul li ul.sub-menu li.mm-active > a {
            color: #ffffff;
        }

        body.lapf-dark-mode .metismenu .has-arrow::after {
            border-color: #8f9aa7;
        }

        body.lapf-dark-mode .metismenu .mm-active > .has-arrow::after {
            border-color: #ffffff;
        }

        body.lapf-dark-mode .simplebar-scrollbar::before {
            background-color: #56606c;
        }


        /*
        |--------------------------------------------------------------------------
        | DARK MODE - CARDS
        |--------------------------------------------------------------------------
        */

        body.lapf-dark-mode .card {
            background-color: #252a34;
            border-color: #343a46;
            color: #d9e0e7;
        }

        body.lapf-dark-mode .card-body {
            color: #d9e0e7;
        }

        body.lapf-dark-mode .card-title,
        body.lapf-dark-mode .header-title,
        body.lapf-dark-mode .card h1,
        body.lapf-dark-mode .card h2,
        body.lapf-dark-mode .card h3,
        body.lapf-dark-mode .card h4,
        body.lapf-dark-mode .card h5,
        body.lapf-dark-mode .card h6 {
            color: #ffffff;
        }


        /*
        |--------------------------------------------------------------------------
        | DARK MODE - TEXT
        |--------------------------------------------------------------------------
        */

        body.lapf-dark-mode h1,
        body.lapf-dark-mode h2,
        body.lapf-dark-mode h3,
        body.lapf-dark-mode h4,
        body.lapf-dark-mode h5,
        body.lapf-dark-mode h6 {
            color: #ffffff;
        }

        body.lapf-dark-mode p {
            color: #c2cad3;
        }

        body.lapf-dark-mode .text-muted {
            color: #929eab !important;
        }


        /*
        |--------------------------------------------------------------------------
        | DARK MODE - TABLES
        |--------------------------------------------------------------------------
        */

        body.lapf-dark-mode .table {
            color: #d9e0e7;
            border-color: #343a46;
        }

        body.lapf-dark-mode .table > :not(caption) > * > * {
            background-color: #252a34;
            color: #d9e0e7;
            border-color: #343a46;
        }

        body.lapf-dark-mode .table thead th {
            background-color: #2c323d;
            color: #ffffff;
            border-color: #3b424f;
        }

        body.lapf-dark-mode .table tbody tr:hover > * {
            background-color: #2c323d;
        }


        /*
        |--------------------------------------------------------------------------
        | DARK MODE - FORMS
        |--------------------------------------------------------------------------
        */

        body.lapf-dark-mode .form-control,
        body.lapf-dark-mode .form-select {
            background-color: #20242c;
            border-color: #3b424f;
            color: #ffffff;
        }

        body.lapf-dark-mode .form-control:focus,
        body.lapf-dark-mode .form-select:focus {
            background-color: #20242c;
            color: #ffffff;
            border-color: #566272;
        }

        body.lapf-dark-mode .form-control::placeholder {
            color: #7f8a98;
        }

        body.lapf-dark-mode .form-label {
            color: #d9e0e7;
        }


        /*
        |--------------------------------------------------------------------------
        | DARK MODE - DROPDOWNS
        |--------------------------------------------------------------------------
        */

        body.lapf-dark-mode .dropdown-menu {
            background-color: #252a34;
            border-color: #343a46;
        }

        body.lapf-dark-mode .dropdown-item {
            color: #d9e0e7;
        }

        body.lapf-dark-mode .dropdown-item:hover,
        body.lapf-dark-mode .dropdown-item:focus {
            background-color: #303641;
            color: #ffffff;
        }

        body.lapf-dark-mode .dropdown-divider {
            border-color: #343a46;
        }


        /*
        |--------------------------------------------------------------------------
        | DARK MODE - MODALS
        |--------------------------------------------------------------------------
        */

        body.lapf-dark-mode .modal-content {
            background-color: #252a34;
            border-color: #343a46;
            color: #d9e0e7;
        }

        body.lapf-dark-mode .modal-header,
        body.lapf-dark-mode .modal-footer {
            border-color: #343a46;
        }


        /*
        |--------------------------------------------------------------------------
        | DARK MODE - LIST GROUP
        |--------------------------------------------------------------------------
        */

        body.lapf-dark-mode .list-group-item {
            background-color: #252a34;
            color: #d9e0e7;
            border-color: #343a46;
        }


        /*
        |--------------------------------------------------------------------------
        | DARK MODE - PAGINATION
        |--------------------------------------------------------------------------
        */

        body.lapf-dark-mode .page-link {
            background-color: #252a34;
            border-color: #343a46;
            color: #b9c2cc;
        }

        body.lapf-dark-mode .page-link:hover {
            background-color: #303641;
            color: #ffffff;
        }


        /*
        |--------------------------------------------------------------------------
        | DARK MODE - FOOTER
        |--------------------------------------------------------------------------
        */

        body.lapf-dark-mode .footer {
            background-color: #242832;
            color: #929eab;
            border-color: #303641;
        }


        /*
        |--------------------------------------------------------------------------
        | DARK MODE - RIGHT SETTINGS PANEL
        |--------------------------------------------------------------------------
        */

        body.lapf-dark-mode .right-bar {
            background-color: #252a34;
            color: #d9e0e7;
        }

        body.lapf-dark-mode .rightbar-title {
            color: #ffffff;
        }

        body.lapf-dark-mode .right-bar hr {
            border-color: #343a46;
        }

        body.lapf-dark-mode .right-bar h5,
        body.lapf-dark-mode .right-bar h6,
        body.lapf-dark-mode .right-bar label {
            color: #ffffff;
        }


        /*
        |--------------------------------------------------------------------------
        | DARK MODE - SEARCH
        |--------------------------------------------------------------------------
        */

        body.lapf-dark-mode .search-wrap,
        body.lapf-dark-mode .search-bar {
            background-color: #242832;
        }

        body.lapf-dark-mode .search-input {
            background-color: #20242c;
            color: #ffffff;
        }

    </style>

</head>


<body>


<div id="layout-wrapper">


    {{-- Topbar --}}
    @include('layouts.partials.navbar')


    {{-- Sidebar --}}
    @include('layouts.partials.sidebar')


    {{-- Main Content --}}
    <div class="main-content">

        <div class="page-content">


            {{-- Page title --}}
            <div class="page-title-box">

                <div class="container-fluid">

                    <div class="row align-items-center">

                        <div class="col-sm-6">

                            <div class="page-title">

                                <h4>
                                    @yield(
                                        'page-heading',
                                        'Dashboard'
                                    )
                                </h4>

                                <ol class="breadcrumb m-0">

                                    <li class="breadcrumb-item">

                                        <a href="{{ route('dashboard') }}">
                                            LAPF
                                        </a>

                                    </li>

                                    <li class="breadcrumb-item active">

                                        @yield(
                                            'page-heading',
                                            'Dashboard'
                                        )

                                    </li>

                                </ol>

                            </div>

                        </div>


                        <div class="col-sm-6">

                            @hasSection('page-actions')

                                <div class="float-end d-none d-sm-block">

                                    @yield('page-actions')

                                </div>

                            @endif

                        </div>

                    </div>

                </div>

            </div>


            {{-- Page content --}}
            <div class="container-fluid">

                <div class="page-content-wrapper">

                    @include('layouts.partials.alerts')

                    @yield('content')

                </div>

            </div>


        </div>


        @include('layouts.partials.footer')


    </div>


</div>



{{-- Right Sidebar --}}
<div class="right-bar">

    <div data-simplebar class="h-100">

        <div class="rightbar-title d-flex align-items-center px-3 py-4">

            <h5 class="m-0 me-2">
                Settings
            </h5>

            <a
                href="javascript:void(0);"
                class="right-bar-toggle ms-auto"
            >
                <i class="mdi mdi-close noti-icon"></i>
            </a>

        </div>


        <hr class="mt-0">


        <h6 class="text-center mb-0">
            Choose Theme
        </h6>


        <div class="p-4">


            {{-- Light Mode --}}
            <div class="mb-2">

                <img
                    src="{{ asset('layouts/assets/images/layouts/layout-1.jpg') }}"
                    class="img-fluid img-thumbnail"
                    alt="Light Mode"
                >

            </div>


            <div class="form-check form-switch mb-3">

                <input
                    class="form-check-input theme-choice"
                    type="checkbox"
                    id="light-mode-switch"
                    checked
                >

                <label
                    class="form-check-label"
                    for="light-mode-switch"
                >
                    Light Mode
                </label>

            </div>


            {{-- Dark Mode --}}
            <div class="mb-2">

                <img
                    src="{{ asset('layouts/assets/images/layouts/layout-2.jpg') }}"
                    class="img-fluid img-thumbnail"
                    alt="Dark Mode"
                >

            </div>


            <div class="form-check form-switch mb-3">

                <input
                    class="form-check-input theme-choice"
                    type="checkbox"
                    id="dark-mode-switch"
                >

                <label
                    class="form-check-label"
                    for="dark-mode-switch"
                >
                    Dark Mode
                </label>

            </div>


        </div>

    </div>

</div>


<div class="rightbar-overlay"></div>



{{-- Core JS --}}
<script
    src="{{ asset('layouts/assets/libs/jquery/jquery.min.js') }}">
</script>

<script
    src="{{ asset('layouts/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}">
</script>

<script
    src="{{ asset('layouts/assets/libs/metismenu/metisMenu.min.js') }}">
</script>

<script
    src="{{ asset('layouts/assets/libs/simplebar/simplebar.min.js') }}">
</script>

<script
    src="{{ asset('layouts/assets/libs/node-waves/waves.min.js') }}">
</script>


@stack('scripts-before-app')


<script>

$(document).ready(function () {


    /*
    |--------------------------------------------------------------------------
    | METIS MENU
    |--------------------------------------------------------------------------
    */

    if ($('#side-menu').length) {
        $('#side-menu').metisMenu();
    }


    /*
    |--------------------------------------------------------------------------
    | WAVES
    |--------------------------------------------------------------------------
    */

    if (typeof Waves !== 'undefined') {
        Waves.init();
    }


    /*
    |--------------------------------------------------------------------------
    | SIDEBAR TOGGLE
    |--------------------------------------------------------------------------
    */

    $('#vertical-menu-btn').on(
        'click',
        function (event) {

            event.preventDefault();

            $('body').toggleClass(
                'sidebar-enable'
            );

            if ($(window).width() >= 992) {

                $('body').toggleClass(
                    'vertical-collpsed'
                );

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | RIGHT SETTINGS BAR
    |--------------------------------------------------------------------------
    */

    $('.right-bar-toggle').on(
        'click',
        function (event) {

            event.preventDefault();

            $('body').toggleClass(
                'right-bar-enabled'
            );

        }
    );


    $('.rightbar-overlay').on(
        'click',
        function () {

            $('body').removeClass(
                'right-bar-enabled'
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    $('.toggle-search').on(
        'click',
        function (event) {

            event.preventDefault();

            const target =
                $(this).data('target');

            $(target).toggleClass(
                'search-visible'
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | FULL SCREEN
    |--------------------------------------------------------------------------
    */

    $('[data-toggle="fullscreen"]').on(
        'click',
        function (event) {

            event.preventDefault();

            if (!document.fullscreenElement) {

                document.documentElement
                    .requestFullscreen();

            } else {

                document.exitFullscreen();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | THEME
    |--------------------------------------------------------------------------
    */

    const lightSwitch =
        document.getElementById(
            'light-mode-switch'
        );

    const darkSwitch =
        document.getElementById(
            'dark-mode-switch'
        );


    function setLightMode() {

        document.body.classList.remove(
            'lapf-dark-mode'
        );

        if (lightSwitch) {
            lightSwitch.checked = true;
        }

        if (darkSwitch) {
            darkSwitch.checked = false;
        }

        localStorage.setItem(
            'lapf-display-mode',
            'light'
        );

    }


    function setDarkMode() {

        document.body.classList.add(
            'lapf-dark-mode'
        );

        if (lightSwitch) {
            lightSwitch.checked = false;
        }

        if (darkSwitch) {
            darkSwitch.checked = true;
        }

        localStorage.setItem(
            'lapf-display-mode',
            'dark'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | RESTORE THEME
    |--------------------------------------------------------------------------
    */

    const storedTheme =
        localStorage.getItem(
            'lapf-display-mode'
        );


    if (storedTheme === 'dark') {

        setDarkMode();

    } else {

        setLightMode();

    }


    /*
    |--------------------------------------------------------------------------
    | LIGHT MODE EVENT
    |--------------------------------------------------------------------------
    */

    $('#light-mode-switch').on(
        'change',
        function () {

            if (this.checked) {
                setLightMode();
            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | DARK MODE EVENT
    |--------------------------------------------------------------------------
    */

    $('#dark-mode-switch').on(
        'change',
        function () {

            if (this.checked) {

                setDarkMode();

            } else {

                setLightMode();

            }

        }
    );


});

</script>


@stack('scripts')


</body>

</html>
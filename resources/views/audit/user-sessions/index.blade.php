@extends('layouts.app')

@section('title', 'User Sessions')

@section('page-heading', 'User Sessions')


@push('styles')

    {{-- DataTables --}}
    <link
        href="{{ asset('layouts/assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}"
        rel="stylesheet"
        type="text/css"
    >

    <link
        href="{{ asset('layouts/assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css') }}"
        rel="stylesheet"
        type="text/css"
    >

    <link
        href="{{ asset('layouts/assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}"
        rel="stylesheet"
        type="text/css"
    >

    <style>

        #user-sessions-datatable {
            width: 100% !important;
        }

        #user-sessions-datatable th,
        #user-sessions-datatable td {
            vertical-align: top !important;
        }

        .session-user-column {
            min-width: 180px;
            white-space: normal !important;
        }

        .session-id-column {
            min-width: 220px;
            max-width: 300px;
            white-space: normal !important;
            word-break: break-all !important;
            overflow-wrap: anywhere !important;
        }

        .session-agent-column {
            min-width: 280px;
            max-width: 420px;
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: anywhere !important;
        }

        .session-date-column {
            min-width: 130px;
            white-space: nowrap !important;
        }

        .session-ip-column {
            min-width: 120px;
            white-space: nowrap !important;
        }

        .dt-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }

        body.lapf-dark-mode
        .dataTables_wrapper
        .dataTables_filter
        input,

        body.lapf-dark-mode
        .dataTables_wrapper
        .dataTables_length
        select {

            background-color: #20242c;
            border-color: #3b424f;
            color: #ffffff;
        }

        body.lapf-dark-mode
        .dataTables_wrapper
        .dataTables_info,

        body.lapf-dark-mode
        .dataTables_wrapper
        .dataTables_length,

        body.lapf-dark-mode
        .dataTables_wrapper
        .dataTables_filter {

            color: #c2cad3;
        }

    </style>

@endpush


@section('content')


{{-- =========================================================
     SUMMARY
========================================================= --}}

<div class="row">

    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="avatar-title rounded-circle bg-soft-primary text-primary"
                        >
                            <i
                                class="mdi mdi-monitor-account font-size-20"
                            ></i>
                        </span>

                    </div>

                    <div>

                        <p class="text-muted mb-1">
                            Total Sessions
                        </p>

                        <h4 class="mb-0">
                            {{ number_format($summary['total']) }}
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="avatar-title rounded-circle bg-soft-success text-success"
                        >
                            <i
                                class="mdi mdi-access-point-check font-size-20"
                            ></i>
                        </span>

                    </div>

                    <div>

                        <p class="text-muted mb-1">
                            Active Sessions
                        </p>

                        <h4 class="mb-0">
                            {{ number_format($summary['active']) }}
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="avatar-title rounded-circle bg-soft-secondary text-secondary"
                        >
                            <i
                                class="mdi mdi-logout font-size-20"
                            ></i>
                        </span>

                    </div>

                    <div>

                        <p class="text-muted mb-1">
                            Closed Sessions
                        </p>

                        <h4 class="mb-0">
                            {{ number_format($summary['closed']) }}
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-3 col-md-6">

        <div class="card">

            <div class="card-body">

                <div class="d-flex align-items-center">

                    <div class="avatar-sm me-3">

                        <span
                            class="avatar-title rounded-circle bg-soft-info text-info"
                        >
                            <i
                                class="mdi mdi-calendar-today font-size-20"
                            ></i>
                        </span>

                    </div>

                    <div>

                        <p class="text-muted mb-1">
                            Sessions Today
                        </p>

                        <h4 class="mb-0">
                            {{ number_format($summary['today']) }}
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     FILTERS
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title">
            Session Search & Filters
        </h4>

        <p class="card-title-desc">
            Search and filter user sessions by session status,
            IP address, browser information or date range.
        </p>


        <form
            method="GET"
            action="{{ route('audit.user-sessions.index') }}"
        >

            <div class="row">


                {{-- Search --}}
                <div class="col-xl-4 col-lg-4 col-md-6">

                    <div class="mb-3">

                        <label
                            for="search"
                            class="form-label"
                        >
                            Search
                        </label>

                        <input
                            type="text"
                            id="search"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Session ID, IP address, browser..."
                        >

                    </div>

                </div>


                {{-- Status --}}
                <div class="col-xl-2 col-lg-4 col-md-6">

                    <div class="mb-3">

                        <label
                            for="status"
                            class="form-label"
                        >
                            Session Status
                        </label>

                        <select
                            name="status"
                            id="status"
                            class="form-select"
                        >

                            <option value="">
                                All Sessions
                            </option>

                            <option
                                value="active"
                                @selected(
                                    request('status') === 'active'
                                )
                            >
                                Active
                            </option>

                            <option
                                value="closed"
                                @selected(
                                    request('status') === 'closed'
                                )
                            >
                                Closed
                            </option>

                        </select>

                    </div>

                </div>


                {{-- Date From --}}
                <div class="col-xl-3 col-lg-4 col-md-6">

                    <div class="mb-3">

                        <label
                            for="date_from"
                            class="form-label"
                        >
                            Date From
                        </label>

                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            class="form-control"
                            value="{{ request('date_from') }}"
                        >

                    </div>

                </div>


                {{-- Date To --}}
                <div class="col-xl-3 col-lg-4 col-md-6">

                    <div class="mb-3">

                        <label
                            for="date_to"
                            class="form-label"
                        >
                            Date To
                        </label>

                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            class="form-control"
                            value="{{ request('date_to') }}"
                        >

                    </div>

                </div>

            </div>


            <div class="d-flex flex-wrap gap-2">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    <i class="mdi mdi-magnify me-1"></i>
                    Apply Filters
                </button>


                <a
                    href="{{ route(
                        'audit.user-sessions.index'
                    ) }}"
                    class="btn btn-light"
                >
                    <i class="mdi mdi-refresh me-1"></i>
                    Clear
                </a>

            </div>

        </form>

    </div>

</div>



{{-- =========================================================
     USER SESSIONS TABLE
========================================================= --}}

<div class="card">

    <div class="card-body">


        <div
            class="d-flex flex-wrap align-items-center justify-content-between mb-3"
        >

            <div>

                <h4 class="header-title mb-1">
                    User Session Register
                </h4>

                <p class="card-title-desc mb-0">
                    Login sessions recorded by the LAPF Pension Fund System.
                </p>

            </div>


            <div class="text-muted font-size-13">

                Matching Records:

                <strong>

                    @if(
                        method_exists(
                            $userSessions,
                            'total'
                        )
                    )

                        {{
                            number_format(
                                $userSessions->total()
                            )
                        }}

                    @else

                        {{
                            number_format(
                                $userSessions->count()
                            )
                        }}

                    @endif

                </strong>

            </div>

        </div>


        <div class="table-responsive">

            <table
                id="user-sessions-datatable"
                class="table table-striped table-bordered"
            >

                <thead>

                    <tr>

                        <th>
                            User
                        </th>

                        <th>
                            Session ID
                        </th>

                        <th>
                            Login Time
                        </th>

                        <th>
                            Last Activity
                        </th>

                        <th>
                            Logout Time
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            IP Address
                        </th>

                        <th>
                            User Agent
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse(
                        $userSessions
                        as $session
                    )

                        <tr>


                            {{-- User --}}
                            <td class="session-user-column">

                                @if($session->user)

                                    <strong>

                                        {{
                                            $session->user->full_name
                                            ??
                                            trim(
                                                ($session->user->first_name ?? '')
                                                . ' '
                                                . ($session->user->surname ?? '')
                                            )
                                        }}

                                    </strong>


                                    <br>


                                    <small class="text-muted">

                                        {{
                                            $session->user->username
                                            ?? '-'
                                        }}

                                    </small>


                                    @if(
                                        $session->user->employee_number
                                        ?? false
                                    )

                                        <br>

                                        <small class="text-muted">

                                            Employee:
                                            {{
                                                $session->user
                                                    ->employee_number
                                            }}

                                        </small>

                                    @endif

                                @else

                                    <span class="text-muted">
                                        Unknown User
                                    </span>

                                @endif

                            </td>



                            {{-- Session ID --}}
                            <td class="session-id-column">

                                <code>
                                    {{
                                        $session->session_id
                                        ?? '-'
                                    }}
                                </code>

                            </td>



                            {{-- Login Time --}}
                            <td
                                class="session-date-column"
                                data-order="{{
                                    optional(
                                        $session->created_at
                                    )->timestamp
                                    ?? 0
                                }}"
                            >

                                @if($session->created_at)

                                    {{
                                        $session
                                            ->created_at
                                            ->format('d M Y')
                                    }}

                                    <br>

                                    <small class="text-muted">

                                        {{
                                            $session
                                                ->created_at
                                                ->format('H:i:s')
                                        }}

                                    </small>

                                @else

                                    -

                                @endif

                            </td>



                            {{-- Last Activity --}}
                            <td class="session-date-column">

                                @if(
                                    isset(
                                        $session->last_activity_at
                                    )
                                    && $session->last_activity_at
                                )

                                    {{
                                        $session
                                            ->last_activity_at
                                            ->format('d M Y')
                                    }}

                                    <br>

                                    <small class="text-muted">

                                        {{
                                            $session
                                                ->last_activity_at
                                                ->format('H:i:s')
                                        }}

                                    </small>

                                @elseif($session->updated_at)

                                    {{
                                        $session
                                            ->updated_at
                                            ->format('d M Y')
                                    }}

                                    <br>

                                    <small class="text-muted">

                                        {{
                                            $session
                                                ->updated_at
                                                ->format('H:i:s')
                                        }}

                                    </small>

                                @else

                                    -

                                @endif

                            </td>



                            {{-- Logout --}}
                            <td class="session-date-column">

                                @if($session->logout_at)

                                    {{
                                        $session
                                            ->logout_at
                                            ->format('d M Y')
                                    }}

                                    <br>

                                    <small class="text-muted">

                                        {{
                                            $session
                                                ->logout_at
                                                ->format('H:i:s')
                                        }}

                                    </small>

                                @else

                                    <span class="text-muted">
                                        -
                                    </span>

                                @endif

                            </td>



                            {{-- Status --}}
                            <td>

                                @if(!$session->logout_at)

                                    <span
                                        class="badge bg-success"
                                    >
                                        <i
                                            class="mdi mdi-circle-medium me-1"
                                        ></i>

                                        Active
                                    </span>

                                @else

                                    <span
                                        class="badge bg-secondary"
                                    >
                                        Closed
                                    </span>

                                @endif

                            </td>



                            {{-- IP --}}
                            <td class="session-ip-column">

                                {{
                                    $session->ip_address
                                    ?? '-'
                                }}

                            </td>



                            {{-- User Agent --}}
                            <td class="session-agent-column">

                                <small>

                                    {{
                                        $session->user_agent
                                        ?? '-'
                                    }}

                                </small>

                            </td>


                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="8"
                                class="text-center py-5"
                            >

                                <div
                                    class="avatar-md mx-auto mb-3"
                                >

                                    <span
                                        class="avatar-title rounded-circle bg-soft-secondary text-secondary"
                                    >

                                        <i
                                            class="mdi mdi-monitor-off font-size-24"
                                        ></i>

                                    </span>

                                </div>


                                <h5>
                                    No User Sessions Found
                                </h5>


                                <p class="text-muted mb-0">

                                    No session records match
                                    the selected filters.

                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>



        {{-- =================================================
             LARAVEL PAGINATION
        ================================================== --}}

        @if(
            method_exists(
                $userSessions,
                'links'
            )
            &&
            $userSessions->hasPages()
        )

            <div class="mt-4">

                {{
                    $userSessions
                        ->withQueryString()
                        ->links()
                }}

            </div>

        @endif


    </div>

</div>

@endsection



@push('scripts-before-app')


{{-- =========================================================
     DATATABLES CORE
========================================================= --}}

<script
    src="{{ asset('layouts/assets/libs/datatables.net/js/jquery.dataTables.min.js') }}">
</script>

<script
    src="{{ asset('layouts/assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}">
</script>



{{-- =========================================================
     DATATABLE BUTTONS
========================================================= --}}

<script
    src="{{ asset('layouts/assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js') }}">
</script>

<script
    src="{{ asset('layouts/assets/libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js') }}">
</script>



{{-- =========================================================
     EXCEL
========================================================= --}}

<script
    src="{{ asset('layouts/assets/libs/jszip/jszip.min.js') }}">
</script>



{{-- =========================================================
     PDF
========================================================= --}}

<script
    src="{{ asset('layouts/assets/libs/pdfmake/build/pdfmake.min.js') }}">
</script>

<script
    src="{{ asset('layouts/assets/libs/pdfmake/build/vfs_fonts.js') }}">
</script>



{{-- =========================================================
     EXPORT BUTTONS
========================================================= --}}

<script
    src="{{ asset('layouts/assets/libs/datatables.net-buttons/js/buttons.html5.min.js') }}">
</script>

<script
    src="{{ asset('layouts/assets/libs/datatables.net-buttons/js/buttons.print.min.js') }}">
</script>

<script
    src="{{ asset('layouts/assets/libs/datatables.net-buttons/js/buttons.colVis.min.js') }}">
</script>


@endpush



@push('scripts')

<script>

$(document).ready(function () {


    $('#user-sessions-datatable')
        .DataTable({

            /*
            |--------------------------------------------------------------------------
            | Keep table readable
            |--------------------------------------------------------------------------
            |
            | Laravel handles pagination.
            | DataTables handles searching, ordering and exporting.
            |
            */

            responsive: false,

            ordering: true,

            searching: true,

            paging: false,

            info: false,

            autoWidth: false,


            /*
            |--------------------------------------------------------------------------
            | Layout
            |--------------------------------------------------------------------------
            */

            dom:
                "<'row mb-3'<'col-lg-8 col-md-12'B><'col-lg-4 col-md-12'f>>"
                +
                "<'row'<'col-sm-12'tr>>",


            /*
            |--------------------------------------------------------------------------
            | Column Widths
            |--------------------------------------------------------------------------
            */

            columnDefs: [

                {
                    targets: 0,
                    width: '180px'
                },

                {
                    targets: 1,
                    width: '260px'
                },

                {
                    targets: 2,
                    width: '130px'
                },

                {
                    targets: 3,
                    width: '130px'
                },

                {
                    targets: 4,
                    width: '130px'
                },

                {
                    targets: 5,
                    width: '100px'
                },

                {
                    targets: 6,
                    width: '120px'
                },

                {
                    targets: 7,
                    width: '320px'
                }

            ],


            /*
            |--------------------------------------------------------------------------
            | Export Buttons
            |--------------------------------------------------------------------------
            */

            buttons: [


                /*
                |--------------------------------------------------------------------------
                | Copy
                |--------------------------------------------------------------------------
                */

                {
                    extend:
                        'copyHtml5',

                    text:
                        '<i class="mdi mdi-content-copy me-1"></i> Copy',

                    className:
                        'btn btn-light',

                    title:
                        'LAPF User Sessions',

                    exportOptions: {

                        columns: [
                            0,
                            1,
                            2,
                            3,
                            4,
                            5,
                            6,
                            7
                        ]

                    }
                },


                /*
                |--------------------------------------------------------------------------
                | Excel
                |--------------------------------------------------------------------------
                */

                {
                    extend:
                        'excelHtml5',

                    text:
                        '<i class="mdi mdi-file-excel-outline me-1"></i> Excel',

                    className:
                        'btn btn-success',

                    title:
                        'LAPF User Sessions',

                    filename:
                        'LAPF_User_Sessions',

                    exportOptions: {

                        columns: [
                            0,
                            1,
                            2,
                            3,
                            4,
                            5,
                            6,
                            7
                        ]

                    }
                },


                /*
                |--------------------------------------------------------------------------
                | PDF
                |--------------------------------------------------------------------------
                */

                {
                    extend:
                        'pdfHtml5',

                    text:
                        '<i class="mdi mdi-file-pdf-outline me-1"></i> PDF',

                    className:
                        'btn btn-danger',

                    title:
                        'LAPF Pension Fund System - User Sessions',

                    filename:
                        'LAPF_User_Sessions',

                    orientation:
                        'landscape',

                    pageSize:
                        'A3',

                    exportOptions: {

                        columns: [
                            0,
                            1,
                            2,
                            3,
                            4,
                            5,
                            6,
                            7
                        ]

                    },

                    customize:
                        function (doc) {

                            doc.defaultStyle.fontSize =
                                7;


                            doc.styles.tableHeader.fontSize =
                                7;


                            doc.styles.title = {

                                fontSize:
                                    14,

                                bold:
                                    true,

                                alignment:
                                    'center',

                                margin: [
                                    0,
                                    0,
                                    0,
                                    10
                                ]

                            };


                            doc.content.splice(
                                1,
                                0,
                                {

                                    text:
                                        'Generated: '
                                        +
                                        new Date()
                                            .toLocaleString(),

                                    alignment:
                                        'center',

                                    fontSize:
                                        8,

                                    margin: [
                                        0,
                                        0,
                                        0,
                                        10
                                    ]

                                }
                            );

                        }
                },


                /*
                |--------------------------------------------------------------------------
                | Print
                |--------------------------------------------------------------------------
                */

                {
                    extend:
                        'print',

                    text:
                        '<i class="mdi mdi-printer-outline me-1"></i> Print',

                    className:
                        'btn btn-primary',

                    title:
                        'LAPF Pension Fund System - User Sessions',

                    exportOptions: {

                        columns: [
                            0,
                            1,
                            2,
                            3,
                            4,
                            5,
                            6,
                            7
                        ]

                    }
                },


                /*
                |--------------------------------------------------------------------------
                | Column Visibility
                |--------------------------------------------------------------------------
                */

                {
                    extend:
                        'colvis',

                    text:
                        '<i class="mdi mdi-view-column-outline me-1"></i> Columns',

                    className:
                        'btn btn-secondary'
                }

            ]

        });


});

</script>

@endpush
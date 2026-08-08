@extends('layouts.app')

@section('title', 'Login Attempts')

@section('page-heading', 'Login Attempts')

@section('breadcrumb-parent')
    Audit & Security
@endsection


@push('styles')

    {{-- DataTables Bootstrap 5 --}}
    <link
        rel="stylesheet"
        href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css"
    >

    {{-- DataTables Buttons --}}
    <link
        rel="stylesheet"
        href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css"
    >

    <style>

        /*
        |--------------------------------------------------------------------------
        | Login Attempts Page
        |--------------------------------------------------------------------------
        */

        .login-attempts-card {
            border: 0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .05);
        }


        /*
        |--------------------------------------------------------------------------
        | Summary Cards
        |--------------------------------------------------------------------------
        */

        .audit-summary-card {
            border: 0;
            border-radius: 8px;
            height: 100%;
        }

        .audit-summary-icon {
            width: 48px;
            height: 48px;
            min-width: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .audit-summary-card h4 {
            margin-bottom: 2px;
            font-weight: 600;
        }

        .audit-summary-card p {
            margin-bottom: 0;
            font-size: 13px;
        }


        /*
        |--------------------------------------------------------------------------
        | Table
        |--------------------------------------------------------------------------
        */

        #loginAttemptsTable {
            width: 100% !important;
        }

        #loginAttemptsTable th {
            white-space: nowrap;
            vertical-align: middle;
        }

        #loginAttemptsTable td {
            vertical-align: middle;
        }

        .login-identifier {
            font-weight: 600;
        }

        .login-user-agent {
            max-width: 320px;
            white-space: normal;
            word-break: break-word;
            line-height: 1.4;
        }

        .failure-reason {
            max-width: 300px;
            white-space: normal;
            word-break: break-word;
            line-height: 1.4;
        }


        /*
        |--------------------------------------------------------------------------
        | DataTables Buttons
        |--------------------------------------------------------------------------
        */

        .dt-buttons {
            margin-bottom: 15px;
        }

        .dt-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .dataTables_filter {
            margin-bottom: 15px;
        }

        .dataTables_filter input {
            margin-left: 8px !important;
        }


        /*
        |--------------------------------------------------------------------------
        | Dark Mode Support
        |--------------------------------------------------------------------------
        */

        body[data-layout-mode="dark"] .login-attempts-card,
        body[data-layout-mode="dark"] .audit-summary-card {
            background-color: #252b3b;
        }

        body[data-layout-mode="dark"] #loginAttemptsTable {
            color: #f1f1f1;
        }

        body[data-layout-mode="dark"] #loginAttemptsTable th,
        body[data-layout-mode="dark"] #loginAttemptsTable td {
            border-color: #32394e;
        }

    </style>

@endpush


@section('content')

    {{-- =========================================================
         SUMMARY CARDS
    ========================================================== --}}

    <div class="row">

        {{-- Total Attempts --}}
        <div class="col-xl-3 col-md-6">

            <div class="card audit-summary-card">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div
                            class="audit-summary-icon
                                   bg-primary
                                   bg-opacity-10
                                   text-primary"
                        >
                            <i class="mdi mdi-login"></i>
                        </div>

                        <div class="ms-3">

                            <p class="text-muted">
                                Total Attempts
                            </p>

                            <h4>
                                {{ $loginAttempts->count() }}
                            </h4>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- Successful --}}
        <div class="col-xl-3 col-md-6">

            <div class="card audit-summary-card">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div
                            class="audit-summary-icon
                                   bg-success
                                   bg-opacity-10
                                   text-success"
                        >
                            <i class="mdi mdi-check-circle-outline"></i>
                        </div>

                        <div class="ms-3">

                            <p class="text-muted">
                                Successful
                            </p>

                            <h4>
                                {{
                                    $loginAttempts
                                        ->where(
                                            'was_successful',
                                            true
                                        )
                                        ->count()
                                }}
                            </h4>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- Failed --}}
        <div class="col-xl-3 col-md-6">

            <div class="card audit-summary-card">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div
                            class="audit-summary-icon
                                   bg-danger
                                   bg-opacity-10
                                   text-danger"
                        >
                            <i class="mdi mdi-close-circle-outline"></i>
                        </div>

                        <div class="ms-3">

                            <p class="text-muted">
                                Failed
                            </p>

                            <h4>
                                {{
                                    $loginAttempts
                                        ->where(
                                            'was_successful',
                                            false
                                        )
                                        ->count()
                                }}
                            </h4>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- Unique IP Addresses --}}
        <div class="col-xl-3 col-md-6">

            <div class="card audit-summary-card">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div
                            class="audit-summary-icon
                                   bg-info
                                   bg-opacity-10
                                   text-info"
                        >
                            <i class="mdi mdi-ip-network-outline"></i>
                        </div>

                        <div class="ms-3">

                            <p class="text-muted">
                                Unique IP Addresses
                            </p>

                            <h4>
                                {{
                                    $loginAttempts
                                        ->pluck('ip_address')
                                        ->filter()
                                        ->unique()
                                        ->count()
                                }}
                            </h4>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         LOGIN ATTEMPTS TABLE
    ========================================================== --}}

    <div class="row">

        <div class="col-12">

            <div class="card login-attempts-card">

                <div class="card-header bg-transparent">

                    <div
                        class="d-flex
                               justify-content-between
                               align-items-center
                               flex-wrap
                               gap-2"
                    >

                        <div>

                            <h4 class="card-title mb-1">
                                Authentication History
                            </h4>

                            <p class="text-muted mb-0">
                                Successful and failed login attempts recorded
                                by the system.
                            </p>

                        </div>


                        <div>

                            <span class="badge bg-light text-dark">
                                <i class="mdi mdi-shield-lock-outline me-1"></i>
                                Security Audit
                            </span>

                        </div>

                    </div>

                </div>


                <div class="card-body">

                    <div class="table-responsive">

                        <table
                            id="loginAttemptsTable"
                            class="
                                table
                                table-bordered
                                table-striped
                                table-hover
                                align-middle
                            "
                        >

                            <thead>

                                <tr>

                                    <th>
                                        #
                                    </th>

                                    <th>
                                        Date / Time
                                    </th>

                                    <th>
                                        User
                                    </th>

                                    <th>
                                        Login Identifier
                                    </th>

                                    <th>
                                        IP Address
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Failure Reason
                                    </th>

                                    <th>
                                        User Agent
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                @forelse($loginAttempts as $attempt)

                                    <tr>

                                        {{-- ID --}}
                                        <td>
                                            {{ $attempt->id }}
                                        </td>


                                        {{-- Attempt Time --}}
                                        <td
                                            data-order="{{
                                                optional(
                                                    $attempt->attempted_at
                                                )->timestamp ?? 0
                                            }}"
                                        >

                                            @if($attempt->attempted_at)

                                                <strong>
                                                    {{
                                                        $attempt
                                                            ->attempted_at
                                                            ->format(
                                                                'd M Y'
                                                            )
                                                    }}
                                                </strong>

                                                <br>

                                                <small class="text-muted">

                                                    {{
                                                        $attempt
                                                            ->attempted_at
                                                            ->format(
                                                                'H:i:s'
                                                            )
                                                    }}

                                                </small>

                                            @else

                                                <span class="text-muted">
                                                    -
                                                </span>

                                            @endif

                                        </td>


                                        {{-- User --}}
                                        <td>

                                            @if($attempt->user)

                                                <strong>

                                                    {{
                                                        $attempt
                                                            ->user
                                                            ->surname
                                                    }},

                                                    {{
                                                        $attempt
                                                            ->user
                                                            ->first_name
                                                    }}

                                                </strong>

                                                <br>

                                                <small class="text-muted">

                                                    {{
                                                        $attempt
                                                            ->user
                                                            ->employee_number
                                                        ?? $attempt
                                                            ->user
                                                            ->username
                                                    }}

                                                </small>

                                            @else

                                                <span class="text-muted">
                                                    Unknown User
                                                </span>

                                            @endif

                                        </td>


                                        {{-- Login Identifier --}}
                                        <td>

                                            <span class="login-identifier">

                                                {{
                                                    $attempt
                                                        ->login_identifier
                                                    ?? '-'
                                                }}

                                            </span>

                                        </td>


                                        {{-- IP --}}
                                        <td>

                                            @if($attempt->ip_address)

                                                <span
                                                    class="
                                                        badge
                                                        bg-light
                                                        text-dark
                                                    "
                                                >

                                                    <i
                                                        class="
                                                            mdi
                                                            mdi-ip-network-outline
                                                            me-1
                                                        "
                                                    ></i>

                                                    {{
                                                        $attempt
                                                            ->ip_address
                                                    }}

                                                </span>

                                            @else

                                                -

                                            @endif

                                        </td>


                                        {{-- Status --}}
                                        <td>

                                            @if($attempt->was_successful)

                                                <span
                                                    class="
                                                        badge
                                                        bg-success
                                                    "
                                                >

                                                    <i
                                                        class="
                                                            mdi
                                                            mdi-check-circle
                                                            me-1
                                                        "
                                                    ></i>

                                                    Successful

                                                </span>

                                            @else

                                                <span
                                                    class="
                                                        badge
                                                        bg-danger
                                                    "
                                                >

                                                    <i
                                                        class="
                                                            mdi
                                                            mdi-close-circle
                                                            me-1
                                                        "
                                                    ></i>

                                                    Failed

                                                </span>

                                            @endif

                                        </td>


                                        {{-- Failure Reason --}}
                                        <td class="failure-reason">

                                            @if(
                                                !$attempt->was_successful
                                                &&
                                                $attempt->failure_reason
                                            )

                                                <span class="text-danger">

                                                    {{
                                                        $attempt
                                                            ->failure_reason
                                                    }}

                                                </span>

                                            @elseif(
                                                $attempt->was_successful
                                            )

                                                <span class="text-muted">
                                                    Not applicable
                                                </span>

                                            @else

                                                <span class="text-muted">
                                                    -
                                                </span>

                                            @endif

                                        </td>


                                        {{-- User Agent --}}
                                        <td class="login-user-agent">

                                            @if($attempt->user_agent)

                                                <small>

                                                    {{
                                                        $attempt
                                                            ->user_agent
                                                    }}

                                                </small>

                                            @else

                                                <span class="text-muted">
                                                    -
                                                </span>

                                            @endif

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td
                                            colspan="8"
                                            class="
                                                text-center
                                                text-muted
                                                py-4
                                            "
                                        >

                                            <i
                                                class="
                                                    mdi
                                                    mdi-login-variant
                                                    font-size-24
                                                    d-block
                                                    mb-2
                                                "
                                            ></i>

                                            No login attempts have been
                                            recorded.

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection


@push('scripts')

    {{-- jQuery --}}
    <script
        src="https://code.jquery.com/jquery-3.7.1.min.js"
    ></script>


    {{-- DataTables --}}
    <script
        src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"
    ></script>

    <script
        src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"
    ></script>


    {{-- Export Dependencies --}}
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"
    ></script>

    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"
    ></script>

    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"
    ></script>


    {{-- DataTables Buttons --}}
    <script
        src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"
    ></script>

    <script
        src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"
    ></script>

    <script
        src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"
    ></script>

    <script
        src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"
    ></script>


    <script>

        $(document).ready(function () {

            /*
            |--------------------------------------------------------------------------
            | Prevent DataTables from Initialising Empty Placeholder Row
            |--------------------------------------------------------------------------
            */

            @if($loginAttempts->count() > 0)

                $('#loginAttemptsTable').DataTable({

                    /*
                    |--------------------------------------------------------------------------
                    | General
                    |--------------------------------------------------------------------------
                    */

                    responsive: false,

                    autoWidth: false,

                    processing: true,

                    pageLength: 25,

                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, 'All']
                    ],


                    /*
                    |--------------------------------------------------------------------------
                    | Default Sorting
                    |--------------------------------------------------------------------------
                    */

                    order: [
                        [1, 'desc']
                    ],


                    /*
                    |--------------------------------------------------------------------------
                    | Layout
                    |--------------------------------------------------------------------------
                    */

                    dom:
                        "<'row mb-3'" +
                            "<'col-md-8'B>" +
                            "<'col-md-4'f>" +
                        ">" +

                        "<'row'" +
                            "<'col-sm-12'tr>" +
                        ">" +

                        "<'row mt-3'" +
                            "<'col-md-5'i>" +
                            "<'col-md-7'p>" +
                        ">",


                    /*
                    |--------------------------------------------------------------------------
                    | Export Buttons
                    |--------------------------------------------------------------------------
                    */

                    buttons: [

                        {
                            extend: 'copyHtml5',

                            text:
                                '<i class="mdi mdi-content-copy me-1"></i> Copy',

                            className:
                                'btn btn-secondary btn-sm',

                            title:
                                'LAPF Login Attempts Audit',

                            exportOptions: {
                                columns: ':visible'
                            }
                        },


                        {
                            extend: 'excelHtml5',

                            text:
                                '<i class="mdi mdi-file-excel me-1"></i> Excel',

                            className:
                                'btn btn-success btn-sm',

                            title:
                                'LAPF Login Attempts Audit',

                            filename:
                                'LAPF_Login_Attempts_{{ now()->format("Y-m-d") }}',

                            exportOptions: {
                                columns: ':visible'
                            }
                        },


                        {
                            extend: 'csvHtml5',

                            text:
                                '<i class="mdi mdi-file-delimited me-1"></i> CSV',

                            className:
                                'btn btn-info btn-sm',

                            title:
                                'LAPF Login Attempts Audit',

                            filename:
                                'LAPF_Login_Attempts_{{ now()->format("Y-m-d") }}',

                            exportOptions: {
                                columns: ':visible'
                            }
                        },


                        {
                            extend: 'pdfHtml5',

                            text:
                                '<i class="mdi mdi-file-pdf-box me-1"></i> PDF',

                            className:
                                'btn btn-danger btn-sm',

                            title:
                                'LAPF Login Attempts Audit',

                            filename:
                                'LAPF_Login_Attempts_{{ now()->format("Y-m-d") }}',

                            orientation:
                                'landscape',

                            pageSize:
                                'A4',

                            exportOptions: {
                                columns: ':visible'
                            },

                            customize: function (doc) {

                                doc.defaultStyle.fontSize = 8;

                                doc.styles.tableHeader.fontSize = 8;

                                doc.styles.title.fontSize = 14;

                                doc.content[0].text =
                                    'Local Authorities Pension Fund\n' +
                                    'Login Attempts Audit Report';

                                doc.content[0].alignment =
                                    'center';

                                doc.content[0].margin =
                                    [0, 0, 0, 15];


                                /*
                                |--------------------------------------------------------------------------
                                | Footer
                                |--------------------------------------------------------------------------
                                */

                                doc.footer = function (
                                    currentPage,
                                    pageCount
                                ) {

                                    return {

                                        columns: [

                                            {
                                                text:
                                                    'Generated: ' +
                                                    '{{ now()->format("d M Y H:i") }}',

                                                alignment:
                                                    'left',

                                                margin:
                                                    [30, 0, 0, 0]
                                            },

                                            {
                                                text:
                                                    'Page ' +
                                                    currentPage +
                                                    ' of ' +
                                                    pageCount,

                                                alignment:
                                                    'right',

                                                margin:
                                                    [0, 0, 30, 0]
                                            }

                                        ],

                                        fontSize: 7

                                    };

                                };

                            }
                        },


                        {
                            extend: 'print',

                            text:
                                '<i class="mdi mdi-printer me-1"></i> Print',

                            className:
                                'btn btn-dark btn-sm',

                            title:
                                'LAPF Login Attempts Audit',

                            exportOptions: {
                                columns: ':visible'
                            },

                            customize: function (win) {

                                $(win.document.body)
                                    .prepend(
                                        '<div style="text-align:center;">' +
                                            '<h3>Local Authorities Pension Fund</h3>' +
                                            '<h4>Login Attempts Audit Report</h4>' +
                                            '<p>Generated: {{ now()->format("d M Y H:i") }}</p>' +
                                        '</div>'
                                    );

                            }
                        }

                    ],


                    /*
                    |--------------------------------------------------------------------------
                    | Language
                    |--------------------------------------------------------------------------
                    */

                    language: {

                        search:
                            'Search login attempts:',

                        lengthMenu:
                            'Show _MENU_ records',

                        info:
                            'Showing _START_ to _END_ of _TOTAL_ login attempts',

                        infoEmpty:
                            'No login attempts available',

                        zeroRecords:
                            'No matching login attempts found',

                        paginate: {
                            previous:
                                '<i class="mdi mdi-chevron-left"></i>',

                            next:
                                '<i class="mdi mdi-chevron-right"></i>'
                        }

                    }

                });

            @endif

        });

    </script>

@endpush
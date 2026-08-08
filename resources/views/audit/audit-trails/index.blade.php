@extends('layouts.app')

@section('title', 'Audit Trail')

@section('page-heading', 'Audit Trail')


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

        /*
        |--------------------------------------------------------------------------
        | AUDIT TABLE
        |--------------------------------------------------------------------------
        */

        #audit-trail-datatable {
            width: 100% !important;
        }


        #audit-trail-datatable th,
        #audit-trail-datatable td {
            vertical-align: top !important;
        }



        /*
        |--------------------------------------------------------------------------
        | OLD / NEW VALUE
        |--------------------------------------------------------------------------
        |
        | Important:
        | - Do not use nowrap.
        | - Allow long JSON/text to wrap.
        | - Allow the row to grow vertically.
        | - Do not allow content to overlap neighbouring columns.
        |
        */

        #audit-trail-datatable .audit-value {
            white-space: normal !important;
            word-wrap: break-word !important;
            overflow-wrap: anywhere !important;
            word-break: break-word !important;
            line-height: 1.6;
            min-width: 260px;
            max-width: 420px;
        }


        #audit-trail-datatable .audit-value-content {
            white-space: pre-wrap !important;
            word-wrap: break-word !important;
            overflow-wrap: anywhere !important;
            word-break: break-word !important;
            margin: 0;
        }



        /*
        |--------------------------------------------------------------------------
        | USER AGENT
        |--------------------------------------------------------------------------
        */

        #audit-trail-datatable .user-agent-value {
            white-space: normal !important;
            overflow-wrap: anywhere !important;
            word-break: break-word !important;
            min-width: 220px;
            max-width: 320px;
        }



        /*
        |--------------------------------------------------------------------------
        | ENTITY
        |--------------------------------------------------------------------------
        */

        #audit-trail-datatable .entity-column {
            min-width: 140px;
            white-space: normal !important;
        }



        /*
        |--------------------------------------------------------------------------
        | USER
        |--------------------------------------------------------------------------
        */

        #audit-trail-datatable .user-column {
            min-width: 170px;
            white-space: normal !important;
        }



        /*
        |--------------------------------------------------------------------------
        | DATE
        |--------------------------------------------------------------------------
        */

        #audit-trail-datatable .date-column {
            min-width: 120px;
            white-space: nowrap !important;
        }



        /*
        |--------------------------------------------------------------------------
        | MODULE / ACTION
        |--------------------------------------------------------------------------
        */

        #audit-trail-datatable .module-column {
            min-width: 120px;
            white-space: normal !important;
        }


        #audit-trail-datatable .action-column {
            min-width: 100px;
            white-space: normal !important;
        }



        /*
        |--------------------------------------------------------------------------
        | IP ADDRESS
        |--------------------------------------------------------------------------
        */

        #audit-trail-datatable .ip-column {
            min-width: 120px;
            white-space: nowrap !important;
        }



        /*
        |--------------------------------------------------------------------------
        | DATATABLE BUTTONS
        |--------------------------------------------------------------------------
        */

        .dt-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }



        /*
        |--------------------------------------------------------------------------
        | DARK MODE
        |--------------------------------------------------------------------------
        */

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


        body.lapf-dark-mode
        #audit-trail-datatable
        .audit-value-content {

            color: #d9e0e7;

        }

    </style>

@endpush


@section('content')


{{-- =========================================================
     FILTERS
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title">
            Audit Search & Filters
        </h4>


        <p class="card-title-desc">

            Filter recorded system activities by module,
            action, user or date range.

        </p>


        <form
            method="GET"
            action="{{ route('audit.audit-trails.index') }}"
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
                            placeholder="Action, module, entity, IP..."
                        >

                    </div>

                </div>



                {{-- Module --}}
                <div class="col-xl-2 col-lg-4 col-md-6">

                    <div class="mb-3">

                        <label
                            for="module"
                            class="form-label"
                        >
                            Module
                        </label>


                        <select
                            name="module"
                            id="module"
                            class="form-select"
                        >

                            <option value="">
                                All Modules
                            </option>


                            @foreach($modules as $module)

                                <option
                                    value="{{ $module }}"
                                    @selected(
                                        request('module')
                                        === $module
                                    )
                                >
                                    {{ $module }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>



                {{-- Action --}}
                <div class="col-xl-2 col-lg-4 col-md-6">

                    <div class="mb-3">

                        <label
                            for="action"
                            class="form-label"
                        >
                            Action
                        </label>


                        <select
                            name="action"
                            id="action"
                            class="form-select"
                        >

                            <option value="">
                                All Actions
                            </option>


                            @foreach($actions as $action)

                                <option
                                    value="{{ $action }}"
                                    @selected(
                                        request('action')
                                        === $action
                                    )
                                >
                                    {{ $action }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>



                {{-- Date From --}}
                <div class="col-xl-2 col-lg-4 col-md-6">

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
                <div class="col-xl-2 col-lg-4 col-md-6">

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
                        'audit.audit-trails.index'
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
     AUDIT TABLE
========================================================= --}}

<div class="card">

    <div class="card-body">


        <div
            class="d-flex
                   flex-wrap
                   align-items-center
                   justify-content-between
                   mb-3"
        >

            <div>

                <h4 class="header-title mb-1">
                    System Audit Trail
                </h4>


                <p class="card-title-desc mb-0">

                    Recorded activities performed within
                    the LAPF Pension Fund System.

                </p>

            </div>


            <div class="text-muted font-size-13">

                Total Records:

                <strong>

                    @if(
                        method_exists(
                            $auditTrails,
                            'total'
                        )
                    )

                        {{
                            number_format(
                                $auditTrails->total()
                            )
                        }}

                    @else

                        {{
                            number_format(
                                $auditTrails->count()
                            )
                        }}

                    @endif

                </strong>

            </div>

        </div>



        <div class="table-responsive">

            <table
                id="audit-trail-datatable"
                class="table
                       table-striped
                       table-bordered
                       align-middle"
            >

                <thead>

                    <tr>

                        <th>
                            Date / Time
                        </th>

                        <th>
                            User
                        </th>

                        <th>
                            Module
                        </th>

                        <th>
                            Action
                        </th>

                        <th>
                            Entity
                        </th>

                        <th>
                            Old Value
                        </th>

                        <th>
                            New Value
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
                        $auditTrails
                        as $audit
                    )


                        <tr>


                            {{-- Date --}}
                            <td
                                class="date-column"
                                data-order="{{
                                    optional(
                                        $audit->created_at
                                    )->timestamp
                                    ?? 0
                                }}"
                            >

                                @if($audit->created_at)

                                    {{
                                        $audit
                                            ->created_at
                                            ->format(
                                                'd M Y'
                                            )
                                    }}

                                    <br>

                                    <small
                                        class="text-muted"
                                    >

                                        {{
                                            $audit
                                                ->created_at
                                                ->format(
                                                    'H:i:s'
                                                )
                                        }}

                                    </small>

                                @else

                                    -

                                @endif

                            </td>



                            {{-- User --}}
                            <td class="user-column">

                                @if($audit->user)

                                    <strong>

                                        {{
                                            $audit->user->full_name
                                            ??
                                            (
                                                ($audit->user->first_name ?? '')
                                                . ' '
                                                . ($audit->user->surname ?? '')
                                            )
                                        }}

                                    </strong>


                                    <br>


                                    <small
                                        class="text-muted"
                                    >

                                        {{
                                            $audit->user->username
                                            ?? '-'
                                        }}

                                    </small>

                                @else

                                    <span
                                        class="text-muted"
                                    >
                                        System / Unknown
                                    </span>

                                @endif

                            </td>



                            {{-- Module --}}
                            <td class="module-column">

                                <span
                                    class="badge
                                           bg-soft-primary
                                           text-primary"
                                >

                                    {{
                                        $audit->module
                                        ?? '-'
                                    }}

                                </span>

                            </td>



                            {{-- Action --}}
                            <td class="action-column">

                                @php

                                    $actionClass =
                                        match(
                                            strtolower(
                                                $audit->action
                                                ?? ''
                                            )
                                        ) {

                                            'create',
                                            'created',
                                            'store'
                                                => 'success',

                                            'update',
                                            'updated',
                                            'edit'
                                                => 'primary',

                                            'delete',
                                            'deleted',
                                            'destroy'
                                                => 'danger',

                                            'login'
                                                => 'info',

                                            'logout'
                                                => 'secondary',

                                            default
                                                => 'warning',

                                        };

                                @endphp


                                <span
                                    class="badge
                                           bg-{{ $actionClass }}"
                                >

                                    {{
                                        $audit->action
                                        ?? '-'
                                    }}

                                </span>

                            </td>



                            {{-- Entity --}}
                            <td class="entity-column">

                                <div>

                                    {{
                                        $audit->entity_type
                                        ?? '-'
                                    }}

                                </div>


                                @if(
                                    isset(
                                        $audit->entity_id
                                    )
                                    &&
                                    $audit->entity_id
                                )

                                    <small
                                        class="text-muted"
                                    >

                                        ID:
                                        {{
                                            $audit->entity_id
                                        }}

                                    </small>

                                @endif

                            </td>



                            {{-- Old Value --}}
                            <td class="audit-value">

                                @if(
                                    isset(
                                        $audit->old_values
                                    )
                                    &&
                                    $audit->old_values
                                )

                                    <div
                                        class="audit-value-content"
                                    >{{
                                        is_string(
                                            $audit->old_values
                                        )
                                            ? $audit->old_values
                                            : json_encode(
                                                $audit->old_values,
                                                JSON_PRETTY_PRINT
                                                |
                                                JSON_UNESCAPED_UNICODE
                                                |
                                                JSON_UNESCAPED_SLASHES
                                            )
                                    }}</div>

                                @else

                                    <span
                                        class="text-muted"
                                    >
                                        -
                                    </span>

                                @endif

                            </td>



                            {{-- New Value --}}
                            <td class="audit-value">

                                @if(
                                    isset(
                                        $audit->new_values
                                    )
                                    &&
                                    $audit->new_values
                                )

                                    <div
                                        class="audit-value-content"
                                    >{{
                                        is_string(
                                            $audit->new_values
                                        )
                                            ? $audit->new_values
                                            : json_encode(
                                                $audit->new_values,
                                                JSON_PRETTY_PRINT
                                                |
                                                JSON_UNESCAPED_UNICODE
                                                |
                                                JSON_UNESCAPED_SLASHES
                                            )
                                    }}</div>

                                @else

                                    <span
                                        class="text-muted"
                                    >
                                        -
                                    </span>

                                @endif

                            </td>



                            {{-- IP --}}
                            <td class="ip-column">

                                {{
                                    $audit->ip_address
                                    ?? '-'
                                }}

                            </td>



                            {{-- User Agent --}}
                            <td class="user-agent-value">

                                <small>

                                    {{
                                        $audit->user_agent
                                        ?? '-'
                                    }}

                                </small>

                            </td>


                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="9"
                                class="text-center
                                       py-5"
                            >

                                <div
                                    class="avatar-md
                                           mx-auto
                                           mb-3"
                                >

                                    <span
                                        class="avatar-title
                                               rounded-circle
                                               bg-soft-secondary
                                               text-secondary"
                                    >

                                        <i
                                            class="mdi
                                                   mdi-history
                                                   font-size-24"
                                        ></i>

                                    </span>

                                </div>


                                <h5>
                                    No Audit Records Found
                                </h5>


                                <p
                                    class="text-muted
                                           mb-0"
                                >

                                    No audit trail records
                                    match the selected filters.

                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>



        {{-- Laravel Pagination --}}
        @if(
            method_exists(
                $auditTrails,
                'links'
            )
            &&
            $auditTrails->hasPages()
        )

            <div class="mt-4">

                {{
                    $auditTrails
                        ->withQueryString()
                        ->links()
                }}

            </div>

        @endif

    </div>

</div>

@endsection



@push('scripts-before-app')


{{-- DataTables --}}
<script
    src="{{ asset('layouts/assets/libs/datatables.net/js/jquery.dataTables.min.js') }}">
</script>

<script
    src="{{ asset('layouts/assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}">
</script>


{{-- Buttons --}}
<script
    src="{{ asset('layouts/assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js') }}">
</script>

<script
    src="{{ asset('layouts/assets/libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js') }}">
</script>


{{-- Excel --}}
<script
    src="{{ asset('layouts/assets/libs/jszip/jszip.min.js') }}">
</script>


{{-- PDF --}}
<script
    src="{{ asset('layouts/assets/libs/pdfmake/build/pdfmake.min.js') }}">
</script>

<script
    src="{{ asset('layouts/assets/libs/pdfmake/build/vfs_fonts.js') }}">
</script>


{{-- Export --}}
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


    $('#audit-trail-datatable')
        .DataTable({

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT
            |--------------------------------------------------------------------------
            |
            | Responsive is deliberately disabled here.
            |
            | The table-responsive wrapper provides horizontal scrolling
            | when required. This allows Old Value and New Value to retain
            | proper widths rather than DataTables collapsing the columns.
            |
            */

            responsive: false,

            ordering: true,

            searching: true,

            paging: false,

            info: false,

            autoWidth: false,


            dom:
                "<'row mb-3'<'col-lg-8 col-md-12'B><'col-lg-4 col-md-12'f>>"
                +
                "<'row'<'col-sm-12'tr>>",


            columnDefs: [

                {
                    targets: 0,
                    width: '120px'
                },

                {
                    targets: 1,
                    width: '170px'
                },

                {
                    targets: 2,
                    width: '120px'
                },

                {
                    targets: 3,
                    width: '100px'
                },

                {
                    targets: 4,
                    width: '140px'
                },

                /*
                |--------------------------------------------------------------------------
                | OLD VALUE
                |--------------------------------------------------------------------------
                */

                {
                    targets: 5,
                    width: '320px'
                },


                /*
                |--------------------------------------------------------------------------
                | NEW VALUE
                |--------------------------------------------------------------------------
                */

                {
                    targets: 6,
                    width: '420px'
                },


                {
                    targets: 7,
                    width: '120px'
                },

                {
                    targets: 8,
                    width: '260px'
                }

            ],


            buttons: [

                {
                    extend: 'copyHtml5',

                    text:
                        '<i class="mdi mdi-content-copy me-1"></i> Copy',

                    className:
                        'btn btn-light',

                    title:
                        'LAPF System Audit Trail'
                },


                {
                    extend: 'excelHtml5',

                    text:
                        '<i class="mdi mdi-file-excel-outline me-1"></i> Excel',

                    className:
                        'btn btn-success',

                    title:
                        'LAPF System Audit Trail',

                    filename:
                        'LAPF_System_Audit_Trail'
                },


                {
                    extend: 'pdfHtml5',

                    text:
                        '<i class="mdi mdi-file-pdf-outline me-1"></i> PDF',

                    className:
                        'btn btn-danger',

                    title:
                        'LAPF Pension Fund System - Audit Trail',

                    filename:
                        'LAPF_System_Audit_Trail',

                    orientation:
                        'landscape',

                    pageSize:
                        'A3'
                },


                {
                    extend: 'print',

                    text:
                        '<i class="mdi mdi-printer-outline me-1"></i> Print',

                    className:
                        'btn btn-primary',

                    title:
                        'LAPF Pension Fund System - Audit Trail'
                },


                {
                    extend: 'colvis',

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
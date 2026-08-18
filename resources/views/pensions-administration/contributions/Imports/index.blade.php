@extends('layouts.app')

@section('title', 'Monthly Contributions')

@section('page-heading', 'Monthly Contributions')


@section('page-actions')

    @can('contributions.monthly-imports.create')

        <a
            href="{{ route('pensions-administration.contributions.imports.create') }}"
            class="btn btn-primary"
        >

            <i class="mdi mdi-upload me-1"></i>

            Upload Contributions

        </a>

    @endcan

@endsection


@push('styles')

<link
    rel="stylesheet"
    href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css"
>

<link
    rel="stylesheet"
    href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css"
>


<style>

    /*
    |--------------------------------------------------------------------------
    | Contribution Import Table
    |--------------------------------------------------------------------------
    */

    #contribution-import-table {
        width: 100% !important;
    }


    #contribution-import-table th {
        white-space: nowrap;
        vertical-align: middle;
    }


    #contribution-import-table td {
        vertical-align: middle;
    }


    /*
    |--------------------------------------------------------------------------
    | Filter Card
    |--------------------------------------------------------------------------
    */

    .contribution-filter-card {
        border-left: 4px solid #0d6efd;
    }


    /*
    |--------------------------------------------------------------------------
    | Action Buttons
    |--------------------------------------------------------------------------
    */

    .contribution-action-buttons {
        white-space: nowrap;
    }


    /*
    |--------------------------------------------------------------------------
    | Export Toolbar
    |--------------------------------------------------------------------------
    */

    .contribution-export-bar {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 12px 15px;
        margin-bottom: 15px;
    }


    .contribution-export-title {
        font-weight: 600;
        color: #495057;
        margin-right: 8px;
    }


    #contribution-export-buttons .dt-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }


    #contribution-export-buttons .dt-buttons .btn {
        margin: 0 !important;
    }


    /*
    |--------------------------------------------------------------------------
    | DataTables Search
    |--------------------------------------------------------------------------
    */

    #contribution-import-table_wrapper .dataTables_filter {
        text-align: right;
    }


    #contribution-import-table_wrapper .dataTables_filter label {
        font-weight: 500;
        margin-bottom: 0;
    }


    #contribution-import-table_wrapper .dataTables_filter input {
        margin-left: 8px !important;
        min-width: 230px;
    }


    /*
    |--------------------------------------------------------------------------
    | DataTables Length
    |--------------------------------------------------------------------------
    */

    #contribution-import-table_wrapper .dataTables_length select {
        min-width: 75px;
    }


    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    #contribution-import-table_wrapper .pagination {
        margin-bottom: 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Responsive
    |--------------------------------------------------------------------------
    */

    @media(max-width: 768px) {

        #contribution-import-table_wrapper .dataTables_filter {
            text-align: left;
            margin-top: 10px;
        }


        #contribution-import-table_wrapper .dataTables_filter input {
            min-width: 160px;
        }


        #contribution-export-buttons .dt-buttons {
            width: 100%;
        }

    }

</style>

@endpush


@section('content')


{{-- =========================================================
     PENSIONS ADMINISTRATION NAVIGATION
========================================================= --}}

@include('pensions-administration.partials.navigation')


{{-- =========================================================
     SUCCESS MESSAGE
========================================================= --}}

@if(session('success'))

    <div class="alert alert-success">

        <i class="mdi mdi-check-circle-outline me-1"></i>

        {{ session('success') }}

    </div>

@endif


{{-- =========================================================
     ERROR MESSAGE
========================================================= --}}

@if(session('error'))

    <div class="alert alert-danger">

        <i class="mdi mdi-alert-circle-outline me-1"></i>

        {{ session('error') }}

    </div>

@endif


{{-- =========================================================
     FILTERS
========================================================= --}}

<div class="card contribution-filter-card">

    <div class="card-body">

        <div class="mb-3">

            <h4 class="header-title mb-1">

                Contribution Import Filters

            </h4>


            <p class="text-muted mb-0">

                Filter contribution batches before using
                Quick Search or exporting the results.

            </p>

        </div>


        <form
            method="GET"
            action="{{ route('pensions-administration.contributions.imports.index') }}"
        >

            <div class="row">


                {{-- =====================================================
                     EMPLOYER
                ====================================================== --}}

                <div class="col-xl-4 col-lg-6 col-md-6">

                    <div class="mb-3">

                        <label class="form-label">

                            Employer

                        </label>


                        <select
                            name="employer_id"
                            class="form-select"
                        >

                            <option value="">

                                All Employers

                            </option>


                            @foreach($employers as $employer)

                                <option
                                    value="{{ $employer->id }}"
                                    @selected(
                                        request('employer_id')
                                        ==
                                        $employer->id
                                    )
                                >

                                    {{ $employer->employer_number }}

                                    -

                                    {{ $employer->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>


                {{-- =====================================================
                     YEAR
                ====================================================== --}}

                <div class="col-xl-2 col-lg-3 col-md-6">

                    <div class="mb-3">

                        <label class="form-label">

                            Year

                        </label>


                        <input
                            type="number"
                            name="year"
                            class="form-control"
                            value="{{ request('year') }}"
                            min="2000"
                            max="2100"
                            placeholder="2026"
                        >

                    </div>

                </div>


                {{-- =====================================================
                     STATUS
                ====================================================== --}}

                <div class="col-xl-3 col-lg-3 col-md-6">

                    <div class="mb-3">

                        <label class="form-label">

                            Status

                        </label>


                        <select
                            name="status"
                            class="form-select"
                        >

                            <option value="">

                                All Statuses

                            </option>


                            @foreach(
                                [
                                    'uploaded' => 'Uploaded',
                                    'processing' => 'Processing',
                                    'awaiting_review' => 'Awaiting Review',
                                    'validated' => 'Validated',
                                    'approved' => 'Approved',
                                    'rejected' => 'Rejected',
                                    'posting' => 'Posting',
                                    'posted' => 'Posted',
                                    'posting_failed' => 'Posting Failed',
                                    'cancelled' => 'Cancelled',
                                    'failed' => 'Validation Failed',
                                ]
                                as $value => $label
                            )

                                <option
                                    value="{{ $value }}"
                                    @selected(
                                        request('status')
                                        ===
                                        $value
                                    )
                                >

                                    {{ $label }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>


                {{-- =====================================================
                     BUTTONS
                ====================================================== --}}

                <div class="col-xl-3 col-lg-12 col-md-6">

                    <div class="mb-3">

                        <label class="form-label d-block">

                            &nbsp;

                        </label>


                        <div class="d-flex flex-wrap gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i class="mdi mdi-filter-outline me-1"></i>

                                Apply Filters

                            </button>


                            <a
                                href="{{ route('pensions-administration.contributions.imports.index') }}"
                                class="btn btn-light"
                            >

                                <i class="mdi mdi-filter-remove-outline me-1"></i>

                                Clear

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </form>

    </div>

</div>


{{-- =========================================================
     ACTIVE FILTERS
========================================================= --}}

@if(
    request()->filled('employer_id')
    ||
    request()->filled('year')
    ||
    request()->filled('status')
)

    <div class="alert alert-info">

        <i class="mdi mdi-filter-check-outline me-1"></i>


        <strong>

            Filters Applied:

        </strong>


        @if(
            request()->filled('employer_id')
        )

            @php

                $selectedEmployer =
                    $employers->firstWhere(
                        'id',
                        (int)
                        request('employer_id')
                    );

            @endphp


            @if($selectedEmployer)

                <span class="badge bg-primary ms-1">

                    Employer:

                    {{ $selectedEmployer->name }}

                </span>

            @endif

        @endif


        @if(
            request()->filled('year')
        )

            <span class="badge bg-primary ms-1">

                Year:

                {{ request('year') }}

            </span>

        @endif


        @if(
            request()->filled('status')
        )

            <span class="badge bg-primary ms-1">

                Status:

                {{
                    ucwords(
                        str_replace(
                            '_',
                            ' ',
                            request('status')
                        )
                    )
                }}

            </span>

        @endif

    </div>

@endif


{{-- =========================================================
     IMPORT REGISTER
========================================================= --}}

<div class="card">

    <div class="card-body">


        {{-- =====================================================
             HEADER
        ====================================================== --}}

        <div class="mb-3">

            <h4 class="header-title mb-1">

                Monthly Contribution Import Register

            </h4>


            <p class="text-muted mb-0">

                Use Quick Search to search within the filtered
                contribution batches below.

            </p>

        </div>


        {{-- =====================================================
             EXPORT BAR
        ====================================================== --}}

        <div class="contribution-export-bar">

            <div
                class="
                    d-flex
                    flex-wrap
                    align-items-center
                    justify-content-between
                    gap-2
                "
            >

                <div
                    class="
                        d-flex
                        flex-wrap
                        align-items-center
                        gap-2
                    "
                >

                    <span class="contribution-export-title">

                        <i class="mdi mdi-export-variant me-1"></i>

                        Export Data:

                    </span>


                    <div id="contribution-export-buttons"></div>

                </div>


                <div id="contribution-search-area"></div>

            </div>

        </div>


        {{-- =====================================================
             TABLE
        ====================================================== --}}

        <div class="table-responsive">

            <table
                id="contribution-import-table"
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
                            Batch
                        </th>

                        <th>
                            Employer
                        </th>

                        <th>
                            Period
                        </th>

                        <th>
                            File
                        </th>

                        <th>
                            Rows
                        </th>

                        <th>
                            Existing
                        </th>

                        <th>
                            New
                        </th>

                        <th>
                            Nil
                        </th>

                        <th>
                            Errors
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Uploaded By
                        </th>

                        <th>
                            Uploaded
                        </th>

                        <th class="text-center">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @foreach(
                        $batches
                        as $batch
                    )

                        <tr>


                            {{-- Batch --}}
                            <td
                                data-order="{{
                                    $batch->id
                                }}"
                            >

                                <strong>

                                    #{{ $batch->id }}

                                </strong>

                            </td>


                            {{-- Employer --}}
                            <td>

                                {{
                                    $batch
                                        ->employer
                                        ?->name
                                    ??
                                    '-'
                                }}

                            </td>


                            {{-- Period --}}
                            <td>

                                {{
                                    $batch
                                        ->contributionPeriod
                                        ?->period_label
                                    ??
                                    '-'
                                }}

                            </td>


                            {{-- File --}}
                            <td>

                                {{
                                    $batch
                                        ->original_filename
                                    ??
                                    '-'
                                }}

                            </td>


                            {{-- Rows --}}
                            <td
                                data-order="{{
                                    $batch
                                        ->total_rows
                                    ??
                                    0
                                }}"
                            >

                                {{
                                    number_format(
                                        $batch
                                            ->total_rows
                                        ??
                                        0
                                    )
                                }}

                            </td>


                            {{-- Existing --}}
                            <td
                                data-order="{{
                                    $batch
                                        ->existing_member_rows
                                    ??
                                    0
                                }}"
                            >

                                {{
                                    number_format(
                                        $batch
                                            ->existing_member_rows
                                        ??
                                        0
                                    )
                                }}

                            </td>


                            {{-- New --}}
                            <td
                                data-order="{{
                                    $batch
                                        ->new_member_rows
                                    ??
                                    0
                                }}"
                            >

                                {{
                                    number_format(
                                        $batch
                                            ->new_member_rows
                                        ??
                                        0
                                    )
                                }}

                            </td>


                            {{-- Nil --}}
                            <td
                                data-order="{{
                                    $batch
                                        ->nil_contributor_rows
                                    ??
                                    0
                                }}"
                            >

                                {{
                                    number_format(
                                        $batch
                                            ->nil_contributor_rows
                                        ??
                                        0
                                    )
                                }}

                            </td>


                            {{-- Errors --}}
                            <td
                                data-order="{{
                                    $batch
                                        ->error_rows
                                    ??
                                    0
                                }}"
                            >

                                <span
                                    class="badge {{
                                        (
                                            $batch
                                                ->error_rows
                                            ??
                                            0
                                        )
                                        >
                                        0
                                            ? 'bg-danger'
                                            : 'bg-success'
                                    }}"
                                >

                                    {{
                                        number_format(
                                            $batch
                                                ->error_rows
                                            ??
                                            0
                                        )
                                    }}

                                </span>

                            </td>


                            {{-- Status --}}
                            <td>

                                @php

                                    $statusClass =
                                        match(
                                            $batch->status
                                        ) {
                                            'uploaded' =>
                                                'bg-secondary',

                                            'processing' =>
                                                'bg-info',

                                            'awaiting_review' =>
                                                'bg-warning text-dark',

                                            'validated' =>
                                                'bg-warning text-dark',

                                            'approved' =>
                                                'bg-primary',

                                            'rejected' =>
                                                'bg-danger',

                                            'posting' =>
                                                'bg-info',

                                            'posted' =>
                                                'bg-success',

                                            'posting_failed' =>
                                                'bg-danger',

                                            'cancelled' =>
                                                'bg-dark',

                                            'failed' =>
                                                'bg-danger',

                                            default =>
                                                'bg-secondary',
                                        };

                                @endphp


                                <span
                                    class="
                                        badge
                                        {{ $statusClass }}
                                    "
                                >

                                    {{
                                        ucwords(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $batch->status
                                            )
                                        )
                                    }}

                                </span>


                                @if(
                                    $batch->status
                                    ===
                                    'rejected'
                                    &&
                                    filled(
                                        $batch
                                            ->rejection_reason
                                    )
                                )

                                    <div
                                        class="
                                            small
                                            text-danger
                                            mt-1
                                        "
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-alert-circle-outline
                                                me-1
                                            "
                                        ></i>

                                        {{
                                            \Illuminate\Support\Str::limit(
                                                $batch
                                                    ->rejection_reason,
                                                70
                                            )
                                        }}

                                    </div>

                                @endif

                            </td>


                            {{-- Uploaded By --}}
                            <td>

                                {{
                                    $batch
                                        ->uploadedBy
                                        ?->full_name
                                    ??
                                    '-'
                                }}

                            </td>


                            {{-- Uploaded --}}
                            <td
                                data-order="{{
                                    $batch
                                        ->created_at
                                        ?->timestamp
                                    ??
                                    0
                                }}"
                            >

                                {{
                                    $batch
                                        ->created_at
                                        ?->format(
                                            'd M Y H:i'
                                        )
                                    ??
                                    '-'
                                }}

                            </td>


                            {{-- Action --}}
                            <td
                                class="
                                    text-center
                                    contribution-action-buttons
                                "
                            >

                                <a
                                    href="{{
                                        route(
                                            'pensions-administration.contributions.imports.show',
                                            $batch
                                        )
                                    }}"
                                    class="
                                        btn
                                        btn-sm
                                        btn-outline-primary
                                    "
                                    title="View Batch"
                                >

                                    <i
                                        class="
                                            mdi
                                            mdi-eye-outline
                                        "
                                    ></i>

                                </a>


                                @if(
                                    in_array(
                                        $batch->status,
                                        [
                                            'awaiting_review',
                                            'validated',
                                            'approved',
                                            'rejected',
                                            'posted',
                                        ],
                                        true
                                    )
                                )

                                    <a
                                        href="{{
                                            route(
                                                'pensions-administration.contributions.imports.review',
                                                $batch
                                            )
                                        }}"
                                        class="
                                            btn
                                            btn-sm
                                            btn-primary
                                        "
                                        title="Review Batch"
                                    >

                                        <i
                                            class="
                                                mdi
                                                mdi-file-search-outline
                                            "
                                        ></i>

                                    </a>

                                @endif

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>


        {{--
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | No:
        |
        | $batches->links()
        |
        | DataTables now controls pagination.
        |
        --}}

    </div>

</div>

@endsection


@push('scripts')


{{-- =========================================================
     DATATABLE CORE
========================================================= --}}

<script
    src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"
></script>

<script
    src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"
></script>


{{-- =========================================================
     EXPORT DEPENDENCIES
========================================================= --}}

<script
    src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"
></script>

<script
    src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"
></script>

<script
    src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"
></script>


{{-- =========================================================
     DATATABLE BUTTONS
========================================================= --}}

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

$(document).ready(
    function () {

        /*
        |--------------------------------------------------------------------------
        | Destroy Existing DataTable
        |--------------------------------------------------------------------------
        */

        if (
            $.fn.DataTable
            &&
            $.fn.DataTable.isDataTable(
                '#contribution-import-table'
            )
        ) {
            $('#contribution-import-table')
                .DataTable()
                .destroy();
        }


        /*
        |--------------------------------------------------------------------------
        | Initialise DataTable
        |--------------------------------------------------------------------------
        */

        const table =
            $('#contribution-import-table')
                .DataTable({

                    processing:
                        true,

                    responsive:
                        false,

                    autoWidth:
                        false,

                    pageLength:
                        25,

                    lengthMenu: [
                        [
                            10,
                            25,
                            50,
                            100,
                            -1
                        ],
                        [
                            10,
                            25,
                            50,
                            100,
                            'All'
                        ]
                    ],

                    order: [
                        [
                            0,
                            'desc'
                        ]
                    ],


                    /*
                    |--------------------------------------------------------------------------
                    | Layout
                    |--------------------------------------------------------------------------
                    |
                    | B = Buttons
                    | f = Search
                    |
                    | They are moved manually to the export bar.
                    |
                    */

                    dom:
                        "<'row mb-2'"
                            + "<'col-md-6'l>"
                            + "<'col-md-6 text-md-end'i>"
                        + ">"
                        + "rt"
                        + "<'row mt-3 align-items-center'"
                            + "<'col-md-6'i>"
                            + "<'col-md-6 d-flex justify-content-md-end'p>"
                        + ">",


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
                                'btn btn-secondary btn-sm',

                            title:
                                'PENERP Monthly Contribution Import Register',

                            exportOptions: {

                                columns: [
                                    0,
                                    1,
                                    2,
                                    3,
                                    4,
                                    5,
                                    6,
                                    7,
                                    8,
                                    9,
                                    10,
                                    11
                                ],

                                stripHtml:
                                    true
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
                                '<i class="mdi mdi-microsoft-excel me-1"></i> Excel',

                            className:
                                'btn btn-success btn-sm',

                            title:
                                'PENERP Monthly Contribution Import Register',

                            filename:
                                'PENERP_Monthly_Contribution_Imports',

                            sheetName:
                                'Contribution Imports',

                            exportOptions: {

                                columns: [
                                    0,
                                    1,
                                    2,
                                    3,
                                    4,
                                    5,
                                    6,
                                    7,
                                    8,
                                    9,
                                    10,
                                    11
                                ],

                                stripHtml:
                                    true
                            }
                        },


                        /*
                        |--------------------------------------------------------------------------
                        | CSV
                        |--------------------------------------------------------------------------
                        */

                        {
                            extend:
                                'csvHtml5',

                            text:
                                '<i class="mdi mdi-file-delimited-outline me-1"></i> CSV',

                            className:
                                'btn btn-info btn-sm',

                            title:
                                'PENERP Monthly Contribution Import Register',

                            filename:
                                'PENERP_Monthly_Contribution_Imports',

                            exportOptions: {

                                columns: [
                                    0,
                                    1,
                                    2,
                                    3,
                                    4,
                                    5,
                                    6,
                                    7,
                                    8,
                                    9,
                                    10,
                                    11
                                ],

                                stripHtml:
                                    true
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
                                '<i class="mdi mdi-file-pdf-box me-1"></i> PDF',

                            className:
                                'btn btn-danger btn-sm',

                            title:
                                'PENERP Monthly Contribution Import Register',

                            filename:
                                'PENERP_Monthly_Contribution_Imports',

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
                                    7,
                                    8,
                                    9,
                                    10,
                                    11
                                ],

                                stripHtml:
                                    true
                            },

                            customize:
                                function (
                                    doc
                                ) {

                                    doc.defaultStyle.fontSize =
                                        8;

                                    doc.styles.tableHeader.fontSize =
                                        8;

                                    doc.styles.title.fontSize =
                                        14;

                                    doc.styles.title.alignment =
                                        'center';


                                    doc.content.splice(
                                        1,
                                        0,
                                        {
                                            text:
                                                'Local Authorities Pension Fund',

                                            alignment:
                                                'center',

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
                                'btn btn-dark btn-sm',

                            title:
                                'PENERP Monthly Contribution Import Register',

                            exportOptions: {

                                columns: [
                                    0,
                                    1,
                                    2,
                                    3,
                                    4,
                                    5,
                                    6,
                                    7,
                                    8,
                                    9,
                                    10,
                                    11
                                ],

                                stripHtml:
                                    true
                            },

                            customize:
                                function (
                                    win
                                ) {

                                    $(win.document.body)
                                        .prepend(
                                            `
                                                <div
                                                    style="
                                                        text-align:center;
                                                        margin-bottom:20px;
                                                    "
                                                >

                                                    <h3
                                                        style="
                                                            margin-bottom:5px;
                                                        "
                                                    >
                                                        Local Authorities Pension Fund
                                                    </h3>

                                                    <strong>
                                                        PENERP Monthly Contribution Import Register
                                                    </strong>

                                                </div>
                                            `
                                        );


                                    $(win.document.body)
                                        .find('table')
                                        .addClass('compact')
                                        .css(
                                            'font-size',
                                            '9px'
                                        );

                                }
                        }

                    ],


                    /*
                    |--------------------------------------------------------------------------
                    | Column Settings
                    |--------------------------------------------------------------------------
                    */

                    columnDefs: [

                        {
                            targets:
                                12,

                            orderable:
                                false,

                            searchable:
                                false
                        }

                    ],


                    /*
                    |--------------------------------------------------------------------------
                    | Language
                    |--------------------------------------------------------------------------
                    */

                    language: {

                        search:
                            'Quick Search:',

                        searchPlaceholder:
                            'Search contribution batches...',

                        lengthMenu:
                            'Show _MENU_ batches',

                        info:
                            'Showing _START_ to _END_ of _TOTAL_ batches',

                        infoEmpty:
                            'No contribution batches found',

                        zeroRecords:
                            'No matching contribution batches found',

                        processing:
                            'Loading contribution batches...',

                        paginate: {

                            previous:
                                'Previous',

                            next:
                                'Next'

                        }

                    }

                });


        /*
        |--------------------------------------------------------------------------
        | Place Export Buttons In Export Bar
        |--------------------------------------------------------------------------
        */

        table
            .buttons()
            .container()
            .appendTo(
                '#contribution-export-buttons'
            );


        /*
        |--------------------------------------------------------------------------
        | Create Search Box
        |--------------------------------------------------------------------------
        |
        | Because "f" was removed from DataTables DOM, we create our own
        | search input and connect it to the DataTable.
        |
        */

        $('#contribution-search-area')
            .html(
                `
                    <div
                        class="
                            d-flex
                            align-items-center
                        "
                    >

                        <label
                            for="contribution-quick-search"
                            class="
                                form-label
                                mb-0
                                me-2
                                text-nowrap
                            "
                        >
                            Quick Search:
                        </label>

                        <input
                            type="search"
                            id="contribution-quick-search"
                            class="form-control form-control-sm"
                            placeholder="Search contribution batches..."
                            style="min-width:230px;"
                        >

                    </div>
                `
            );


        /*
        |--------------------------------------------------------------------------
        | Search DataTable
        |--------------------------------------------------------------------------
        */

        $('#contribution-quick-search')
            .on(
                'keyup search change',
                function () {

                    table
                        .search(
                            this.value
                        )
                        .draw();

                }
            );


        /*
        |--------------------------------------------------------------------------
        | Clean Button Styling
        |--------------------------------------------------------------------------
        */

        $('#contribution-export-buttons .dt-button')
            .removeClass(
                'dt-button'
            );

    }
);

</script>

@endpush
@extends('layouts.app')

@section('title', 'Review Contribution Receipts')

@section('page-heading', 'Review Contribution Receipts')


@section('page-actions')

    <a
        href="{{ route(
            'pensions-administration.contributions.receipts.imports.index'
        ) }}"
        class="btn btn-light"
    >

        <i class="mdi mdi-arrow-left me-1"></i>

        Receipt Imports

    </a>


    @can('contributions.receipts.post')

        @if($unpostedValidRows > 0)

            <form
                method="POST"
                action="{{ route(
                    'pensions-administration.contributions.receipts.imports.post',
                    $batch
                ) }}"
                class="d-inline"
                id="postReceiptForm"
            >

                @csrf


                <button
                    type="submit"
                    class="btn btn-success"
                    id="postReceiptButton"
                >

                    <i class="mdi mdi-database-check me-1"></i>

                    Post Valid Receipts

                    (
                        {{ number_format(
                            $unpostedValidRows
                        ) }}
                    )

                </button>

            </form>

        @endif

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
    | Summary Cards
    |--------------------------------------------------------------------------
    */

    .receipt-stat {
        min-height: 105px;
    }


    .receipt-stat-number {
        font-size: 1.4rem;
        font-weight: 600;
    }


    /*
    |--------------------------------------------------------------------------
    | Batch Information
    |--------------------------------------------------------------------------
    */

    .receipt-batch-card {
        border-left: 4px solid #0d6efd;
    }


    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    .receipt-filter-card {
        border-left: 4px solid #0d6efd;
    }


    /*
    |--------------------------------------------------------------------------
    | Receipt Table
    |--------------------------------------------------------------------------
    */

    #receipt-review-table {
        width: 100% !important;
    }


    #receipt-review-table th {
        white-space: nowrap;
        vertical-align: middle;
    }


    #receipt-review-table td {
        vertical-align: middle;
    }


    /*
    |--------------------------------------------------------------------------
    | Amounts
    |--------------------------------------------------------------------------
    */

    .amount-cell {
        white-space: nowrap;
    }


    /*
    |--------------------------------------------------------------------------
    | Errors
    |--------------------------------------------------------------------------
    */

    .error-message {
        white-space: pre-line;
        min-width: 250px;
        max-width: 420px;
    }


    /*
    |--------------------------------------------------------------------------
    | Export Toolbar
    |--------------------------------------------------------------------------
    */

    .receipt-export-bar {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 12px 15px;
        margin-bottom: 15px;
    }


    .receipt-export-title {
        font-weight: 600;
        color: #495057;
        margin-right: 8px;
    }


    #receipt-review-export-buttons .dt-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }


    #receipt-review-export-buttons .dt-buttons .btn {
        margin: 0 !important;
    }


    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    #receipt-review-table_wrapper .pagination {
        margin-bottom: 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Responsive
    |--------------------------------------------------------------------------
    */

    @media(max-width: 768px) {

        #receipt-review-export-buttons .dt-buttons {
            width: 100%;
        }


        #receipt-review-search-area {
            width: 100%;
        }


        #receipt-review-quick-search {
            width: 100%;
            min-width: 0 !important;
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
     BATCH INFORMATION
========================================================= --}}

<div class="card receipt-batch-card mb-4">

    <div class="card-body">

        <div class="mb-3">

            <h4 class="header-title mb-1">

                Receipt Import Batch

            </h4>


            <p class="text-muted mb-0">

                Review the validation results below before posting
                valid employer contribution receipts.

            </p>

        </div>


        <div class="row g-3">

            <div class="col-xl-4 col-lg-6">

                <div class="text-muted small">

                    File

                </div>


                <strong>

                    {{
                        $batch
                            ->original_filename
                        ??
                        '-'
                    }}

                </strong>


                <div class="small text-muted">

                    {{
                        $batch
                            ->import_uuid
                        ??
                        '-'
                    }}

                </div>

            </div>


            <div class="col-xl-2 col-lg-3 col-md-6">

                <div class="text-muted small">

                    Currency

                </div>


                <span class="badge bg-secondary">

                    {{
                        $batch
                            ->default_currency
                        ??
                        'ZWG'
                    }}

                </span>

            </div>


            <div class="col-xl-3 col-lg-3 col-md-6">

                <div class="text-muted small">

                    Status

                </div>


                @php

                    $batchStatusClass =
                        match(
                            $batch->status
                        ) {

                            'processing' =>
                                'bg-info',

                            'awaiting_review' =>
                                'bg-warning text-dark',

                            'posting' =>
                                'bg-info',

                            'posted' =>
                                'bg-success',

                            'partially_posted' =>
                                'bg-warning text-dark',

                            'failed' =>
                                'bg-danger',

                            default =>
                                'bg-secondary',
                        };

                @endphp


                <span
                    class="
                        badge
                        {{ $batchStatusClass }}
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

            </div>


            <div class="col-xl-3 col-lg-12">

                <div class="text-muted small">

                    Progress

                </div>


                <strong>

                    {{
                        number_format(
                            (float)
                            $batch
                                ->progress_percentage,
                            2
                        )
                    }}%

                </strong>


                <div class="progress mt-2">

                    <div
                        class="progress-bar"
                        role="progressbar"
                        style="
                            width:
                            {{
                                min(
                                    100,
                                    (float)
                                    $batch
                                        ->progress_percentage
                                )
                            }}%;
                        "
                    ></div>

                </div>

            </div>

        </div>


        @if($batch->failure_reason)

            <div class="alert alert-danger mt-3 mb-0">

                <i class="mdi mdi-alert-circle-outline me-1"></i>

                {{
                    $batch
                        ->failure_reason
                }}

            </div>

        @endif

    </div>

</div>


{{-- =========================================================
     SUMMARY
========================================================= --}}

<div class="row g-3 mb-4">

    <div class="col-xl-3 col-md-6">

        <div class="card receipt-stat">

            <div class="card-body">

                <div class="text-muted">

                    Total Rows

                </div>


                <div class="receipt-stat-number">

                    {{
                        number_format(
                            $batch
                                ->total_rows
                            ??
                            0
                        )
                    }}

                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-3 col-md-6">

        <div class="card receipt-stat">

            <div class="card-body">

                <div class="text-muted">

                    Valid

                </div>


                <div
                    class="
                        receipt-stat-number
                        text-success
                    "
                >

                    {{
                        number_format(
                            $batch
                                ->valid_rows
                            ??
                            0
                        )
                    }}

                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-3 col-md-6">

        <div class="card receipt-stat">

            <div class="card-body">

                <div class="text-muted">

                    Errors

                </div>


                <div
                    class="
                        receipt-stat-number
                        text-danger
                    "
                >

                    {{
                        number_format(
                            $batch
                                ->error_rows
                            ??
                            0
                        )
                    }}

                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-3 col-md-6">

        <div class="card receipt-stat">

            <div class="card-body">

                <div class="text-muted">

                    Posted

                </div>


                <div
                    class="
                        receipt-stat-number
                        text-primary
                    "
                >

                    {{
                        number_format(
                            $batch
                                ->posted_rows
                            ??
                            0
                        )
                    }}

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     FILTERS
========================================================= --}}

<div class="card receipt-filter-card mb-4">

    <div class="card-body">

        <div class="mb-3">

            <h4 class="header-title mb-1">

                Receipt Validation Filters

            </h4>


            <p class="text-muted mb-0">

                Filter the receipt rows by validation status before
                reviewing or exporting the results.

            </p>

        </div>


        <form
            method="GET"
            action="{{ route(
                'pensions-administration.contributions.receipts.imports.review',
                $batch
            ) }}"
        >

            <div class="row">


                {{-- =====================================================
                     VALIDATION STATUS
                ====================================================== --}}

                <div class="col-xl-4 col-lg-5 col-md-6">

                    <div class="mb-3">

                        <label class="form-label">

                            Validation Status

                        </label>


                        <select
                            name="status"
                            class="form-select"
                        >

                            <option value="">

                                All Rows

                            </option>


                            <option
                                value="valid"
                                @selected(
                                    request('status')
                                    ===
                                    'valid'
                                )
                            >

                                Valid

                            </option>


                            <option
                                value="error"
                                @selected(
                                    request('status')
                                    ===
                                    'error'
                                )
                            >

                                Error

                            </option>

                        </select>

                    </div>

                </div>


                {{-- =====================================================
                     BUTTONS
                ====================================================== --}}

                <div class="col-xl-4 col-lg-7 col-md-6">

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

                                Apply Filter

                            </button>


                            <a
                                href="{{ route(
                                    'pensions-administration.contributions.receipts.imports.review',
                                    $batch
                                ) }}"
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
     ACTIVE FILTER
========================================================= --}}

@if(request()->filled('status'))

    <div class="alert alert-info">

        <i class="mdi mdi-filter-check-outline me-1"></i>


        <strong>

            Filter Applied:

        </strong>


        <span class="badge bg-primary ms-1">

            Status:

            {{
                ucfirst(
                    request('status')
                )
            }}

        </span>

    </div>

@endif


{{-- =========================================================
     RECEIPT ROW REGISTER
========================================================= --}}

<div class="card">

    <div class="card-body">


        {{-- =====================================================
             HEADER
        ====================================================== --}}

        <div class="mb-3">

            <h4 class="header-title mb-1">

                Receipt Validation Register

            </h4>


            <p class="text-muted mb-0">

                Valid rows can be posted to the contribution receipt
                register. Error rows remain unposted until corrected
                through a new upload.

            </p>

        </div>


        {{-- =====================================================
             EXPORT BAR
        ====================================================== --}}

        <div class="receipt-export-bar">

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

                    <span class="receipt-export-title">

                        <i class="mdi mdi-export-variant me-1"></i>

                        Export Data:

                    </span>


                    <div
                        id="receipt-review-export-buttons"
                    ></div>

                </div>


                <div
                    id="receipt-review-search-area"
                ></div>

            </div>

        </div>


        {{-- =====================================================
             TABLE
        ====================================================== --}}

        <div class="table-responsive">

            <table
                id="receipt-review-table"
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
                            Row
                        </th>

                        <th>
                            Employer Code
                        </th>

                        <th>
                            Employer
                        </th>

                        <th>
                            Receipt Date
                        </th>

                        <th>
                            Due Date
                        </th>

                        <th>
                            Contribution Month
                        </th>

                        <th>
                            Currency
                        </th>

                        <th class="text-end">
                            Original Amount
                        </th>

                        <th class="text-end">
                            Rate
                        </th>

                        <th class="text-end">
                            ZWG Amount
                        </th>

                        <th>
                            Validation
                        </th>

                        <th>
                            Error
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @foreach(
                        $rows
                        as $row
                    )

                        <tr>


                            {{-- Row --}}
                            <td
                                data-order="{{
                                    $row
                                        ->row_number
                                }}"
                            >

                                {{
                                    $row
                                        ->row_number
                                }}

                            </td>


                            {{-- Employer Code --}}
                            <td>

                                <strong>

                                    {{
                                        $row
                                            ->employer_code
                                        ?:
                                        '-'
                                    }}

                                </strong>

                            </td>


                            {{-- Employer --}}
                            <td>

                                {{
                                    $row
                                        ->matchedEmployer
                                        ?->name
                                    ??
                                    '-'
                                }}

                            </td>


                            {{-- Receipt Date --}}
                            <td
                                data-order="{{
                                    $row
                                        ->receipt_date
                                        ?->timestamp
                                    ??
                                    0
                                }}"
                            >

                                {{
                                    $row
                                        ->receipt_date
                                        ?->format(
                                            'd M Y'
                                        )
                                    ??
                                    '-'
                                }}

                            </td>


                            {{-- Due Date --}}
                            <td
                                data-order="{{
                                    $row
                                        ->due_date
                                        ?->timestamp
                                    ??
                                    0
                                }}"
                            >

                                {{
                                    $row
                                        ->due_date
                                        ?->format(
                                            'd M Y'
                                        )
                                    ??
                                    '-'
                                }}

                            </td>


                            {{-- Contribution Month --}}
                            <td>

                                {{
                                    $row
                                        ->contribution_period
                                        ?->format(
                                            'M Y'
                                        )
                                    ??
                                    '-'
                                }}

                            </td>


                            {{-- Currency --}}
                            <td>

                                <span class="badge bg-secondary">

                                    {{
                                        $row
                                            ->currency
                                        ??
                                        '-'
                                    }}

                                </span>

                            </td>


                            {{-- Original Amount --}}
                            <td
                                class="
                                    text-end
                                    amount-cell
                                "
                                data-order="{{
                                    $row
                                        ->original_amount
                                    ??
                                    0
                                }}"
                            >

                                {{
                                    $row
                                        ->original_amount
                                    !==
                                    null
                                        ?
                                        number_format(
                                            (float)
                                            $row
                                                ->original_amount,
                                            2
                                        )
                                        :
                                        '-'
                                }}

                            </td>


                            {{-- Exchange Rate --}}
                            <td
                                class="
                                    text-end
                                    amount-cell
                                "
                                data-order="{{
                                    $row
                                        ->exchange_rate
                                    ??
                                    0
                                }}"
                            >

                                {{
                                    $row
                                        ->exchange_rate
                                    !==
                                    null
                                        ?
                                        number_format(
                                            (float)
                                            $row
                                                ->exchange_rate,
                                            8
                                        )
                                        :
                                        '-'
                                }}

                            </td>


                            {{-- ZWG Amount --}}
                            <td
                                class="
                                    text-end
                                    amount-cell
                                "
                                data-order="{{
                                    $row
                                        ->amount_zwg
                                    ??
                                    0
                                }}"
                            >

                                {{
                                    $row
                                        ->amount_zwg
                                    !==
                                    null
                                        ?
                                        number_format(
                                            (float)
                                            $row
                                                ->amount_zwg,
                                            2
                                        )
                                        :
                                        '-'
                                }}

                            </td>


                            {{-- Validation --}}
                            <td>

                                @if(
                                    $row
                                        ->validation_status
                                    ===
                                    'valid'
                                )

                                    <span class="badge bg-success">

                                        Valid

                                    </span>

                                @else

                                    <span class="badge bg-danger">

                                        Error

                                    </span>

                                @endif


                                @if($row->imported_at)

                                    <span class="badge bg-primary">

                                        Posted

                                    </span>

                                @endif

                            </td>


                            {{-- Error --}}
                            <td>

                                @if($row->error_messages)

                                    <div
                                        class="
                                            text-danger
                                            error-message
                                        "
                                    >

                                        {{
                                            $row
                                                ->error_messages
                                        }}

                                    </div>

                                @else

                                    <span class="text-muted">

                                        -

                                    </span>

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
        | DataTables controls pagination for the rows displayed on this page.
        |
        | If your controller still uses paginate(50), DataTables will only
        | receive those 50 rows. If you want DataTables to search/export the
        | entire batch, change the controller from paginate(50) to get().
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
                '#receipt-review-table'
            )
        ) {

            $('#receipt-review-table')
                .DataTable()
                .destroy();

        }


        /*
        |--------------------------------------------------------------------------
        | Initialise DataTable
        |--------------------------------------------------------------------------
        */

        const table =
            $('#receipt-review-table')
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
                            'asc'
                        ]
                    ],


                    /*
                    |--------------------------------------------------------------------------
                    | Layout
                    |--------------------------------------------------------------------------
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

                        {
                            extend:
                                'copyHtml5',

                            text:
                                '<i class="mdi mdi-content-copy me-1"></i> Copy',

                            className:
                                'btn btn-secondary btn-sm',

                            title:
                                'PENERP Contribution Receipt Validation Register',

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


                        {
                            extend:
                                'excelHtml5',

                            text:
                                '<i class="mdi mdi-microsoft-excel me-1"></i> Excel',

                            className:
                                'btn btn-success btn-sm',

                            title:
                                'PENERP Contribution Receipt Validation Register',

                            filename:
                                'PENERP_Contribution_Receipt_Validation',

                            sheetName:
                                'Receipt Validation',

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


                        {
                            extend:
                                'csvHtml5',

                            text:
                                '<i class="mdi mdi-file-delimited-outline me-1"></i> CSV',

                            className:
                                'btn btn-info btn-sm',

                            title:
                                'PENERP Contribution Receipt Validation Register',

                            filename:
                                'PENERP_Contribution_Receipt_Validation',

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


                        {
                            extend:
                                'pdfHtml5',

                            text:
                                '<i class="mdi mdi-file-pdf-box me-1"></i> PDF',

                            className:
                                'btn btn-danger btn-sm',

                            title:
                                'PENERP Contribution Receipt Validation Register',

                            filename:
                                'PENERP_Contribution_Receipt_Validation',

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
                                        7;

                                    doc.styles.tableHeader.fontSize =
                                        7;

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


                        {
                            extend:
                                'print',

                            text:
                                '<i class="mdi mdi-printer-outline me-1"></i> Print',

                            className:
                                'btn btn-dark btn-sm',

                            title:
                                'PENERP Contribution Receipt Validation Register',

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
                                                        PENERP Contribution Receipt Validation Register
                                                    </strong>

                                                </div>
                                            `
                                        );


                                    $(win.document.body)
                                        .find('table')
                                        .addClass('compact')
                                        .css(
                                            'font-size',
                                            '8px'
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
                            'Quick Search:',

                        searchPlaceholder:
                            'Search receipt rows...',

                        lengthMenu:
                            'Show _MENU_ receipt rows',

                        info:
                            'Showing _START_ to _END_ of _TOTAL_ receipt rows',

                        infoEmpty:
                            'No receipt rows found',

                        zeroRecords:
                            'No matching receipt rows found',

                        processing:
                            'Loading receipt rows...',

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
        | Place Export Buttons
        |--------------------------------------------------------------------------
        */

        table
            .buttons()
            .container()
            .appendTo(
                '#receipt-review-export-buttons'
            );


        /*
        |--------------------------------------------------------------------------
        | Quick Search
        |--------------------------------------------------------------------------
        */

        $('#receipt-review-search-area')
            .html(
                `
                    <div
                        class="
                            d-flex
                            align-items-center
                        "
                    >

                        <label
                            for="receipt-review-quick-search"
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
                            id="receipt-review-quick-search"
                            class="form-control form-control-sm"
                            placeholder="Search receipt rows..."
                            style="min-width:230px;"
                        >

                    </div>
                `
            );


        $('#receipt-review-quick-search')
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

        $('#receipt-review-export-buttons .dt-button')
            .removeClass(
                'dt-button'
            );


        /*
        |--------------------------------------------------------------------------
        | Post Receipt Form
        |--------------------------------------------------------------------------
        */

        const form =
            document.getElementById(
                'postReceiptForm'
            );

        const button =
            document.getElementById(
                'postReceiptButton'
            );


        if (
            form
            &&
            button
        ) {

            form.addEventListener(
                'submit',
                function (
                    event
                ) {

                    const confirmed =
                        confirm(
                            'Post all valid receipts in this batch?'
                        );


                    if (
                        !confirmed
                    ) {

                        event.preventDefault();

                        return;
                    }


                    button.disabled =
                        true;


                    button.innerHTML =
                        `
                            <span
                                class="
                                    spinner-border
                                    spinner-border-sm
                                    me-1
                                "
                                role="status"
                                aria-hidden="true"
                            ></span>

                            Posting...
                        `;

                }
            );

        }

    }
);

</script>

@endpush
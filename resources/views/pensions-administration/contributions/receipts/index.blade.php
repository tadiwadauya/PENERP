@extends('layouts.app')

@section('title', 'Contribution Receipts')

@section('page-heading', 'Contribution Receipts')


@section('page-actions')

    @can('contributions.exchange-rates.view')

        <a
            href="{{ route(
                'pensions-administration.contributions.receipts.exchange-rates.index'
            ) }}"
            class="btn btn-outline-secondary"
        >

            <i class="mdi mdi-currency-usd me-1"></i>

            USD / ZWG Rates

        </a>

    @endcan


    @can('contributions.receipts.view')

        <a
            href="{{ route(
                'pensions-administration.contributions.receipts.imports.index'
            ) }}"
            class="btn btn-outline-primary"
        >

            <i class="mdi mdi-history me-1"></i>

            Import History

        </a>

    @endcan


    @can('contributions.receipts.create')

        <a
            href="{{ route(
                'pensions-administration.contributions.receipts.imports.create'
            ) }}"
            class="btn btn-primary"
        >

            <i class="mdi mdi-upload me-1"></i>

            Upload Receipts

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
    | Summary Cards
    |--------------------------------------------------------------------------
    */

    .receipt-summary-card {
        min-height: 110px;
    }


    .receipt-summary-number {
        font-size: 1.45rem;
        font-weight: 600;
    }


    /*
    |--------------------------------------------------------------------------
    | Filter Card
    |--------------------------------------------------------------------------
    */

    .receipt-filter-card {
        border-left: 4px solid #0d6efd;
    }


    /*
    |--------------------------------------------------------------------------
    | Receipt Register
    |--------------------------------------------------------------------------
    */

    #receipt-register-table {
        width: 100% !important;
    }


    #receipt-register-table th {
        white-space: nowrap;
        vertical-align: middle;
    }


    #receipt-register-table td {
        vertical-align: middle;
    }


    /*
    |--------------------------------------------------------------------------
    | Amount Columns
    |--------------------------------------------------------------------------
    */

    .receipt-amount-cell {
        white-space: nowrap;
    }


    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    .receipt-action-buttons {
        white-space: nowrap;
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


    #receipt-export-buttons .dt-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }


    #receipt-export-buttons .dt-buttons .btn {
        margin: 0 !important;
    }


    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    #receipt-register-table_wrapper .pagination {
        margin-bottom: 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Responsive
    |--------------------------------------------------------------------------
    */

    @media(max-width: 768px) {

        #receipt-export-buttons .dt-buttons {
            width: 100%;
        }


        #receipt-search-area {
            width: 100%;
        }


        #receipt-quick-search {
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
     SUMMARY
========================================================= --}}

<div class="row g-3 mb-4">

    <div class="col-xl-4 col-md-6">

        <div class="card receipt-summary-card">

            <div class="card-body">

                <div class="text-muted">

                    Receipt Transactions

                </div>


                <div class="receipt-summary-number">

                    {{
                        number_format(
                            $summary['receipt_count']
                            ??
                            0
                        )
                    }}

                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-4 col-md-6">

        <div class="card receipt-summary-card">

            <div class="card-body">

                <div class="text-muted">

                    Total Actual Receipts ZWG

                </div>


                <div
                    class="
                        receipt-summary-number
                        text-success
                    "
                >

                    {{
                        number_format(
                            (float) (
                                $summary['total_zwg']
                                ??
                                0
                            ),
                            2
                        )
                    }}

                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-4 col-md-6">

        <div class="card receipt-summary-card">

            <div class="card-body">

                <div class="text-muted">

                    Original USD Received

                </div>


                <div
                    class="
                        receipt-summary-number
                        text-primary
                    "
                >

                    {{
                        number_format(
                            (float) (
                                $summary['original_usd']
                                ??
                                0
                            ),
                            2
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

                Receipt Filters

            </h4>


            <p class="text-muted mb-0">

                Filter posted employer receipts before using
                Quick Search or exporting the register.

            </p>

        </div>


        <form
            method="GET"
            action="{{ route(
                'pensions-administration.contributions.receipts.index'
            ) }}"
        >

            <div class="row">


                {{-- =====================================================
                     EMPLOYER
                ====================================================== --}}

                <div class="col-xl-4 col-lg-5 col-md-6">

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


                            @foreach(
                                $employers
                                as $employer
                            )

                                <option
                                    value="{{ $employer->id }}"
                                    @selected(
                                        (string)
                                        request('employer_id')
                                        ===
                                        (string)
                                        $employer->id
                                    )
                                >

                                    {{
                                        $employer
                                            ->employer_number
                                    }}

                                    -

                                    {{
                                        $employer
                                            ->name
                                    }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>


                {{-- =====================================================
                     CONTRIBUTION MONTH
                ====================================================== --}}

                <div class="col-xl-2 col-lg-3 col-md-6">

                    <div class="mb-3">

                        <label class="form-label">

                            Contribution Month

                        </label>


                        <input
                            type="month"
                            name="period"
                            class="form-control"
                            value="{{ request('period') }}"
                        >

                    </div>

                </div>


                {{-- =====================================================
                     CURRENCY
                ====================================================== --}}

                <div class="col-xl-2 col-lg-2 col-md-6">

                    <div class="mb-3">

                        <label class="form-label">

                            Currency

                        </label>


                        <select
                            name="currency"
                            class="form-select"
                        >

                            <option value="">

                                All

                            </option>


                            <option
                                value="ZWG"
                                @selected(
                                    request('currency')
                                    ===
                                    'ZWG'
                                )
                            >

                                ZWG

                            </option>


                            <option
                                value="USD"
                                @selected(
                                    request('currency')
                                    ===
                                    'USD'
                                )
                            >

                                USD

                            </option>

                        </select>

                    </div>

                </div>


                {{-- =====================================================
                     BUTTONS
                ====================================================== --}}

                <div class="col-xl-4 col-lg-12 col-md-6">

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
                                href="{{ route(
                                    'pensions-administration.contributions.receipts.index'
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
     ACTIVE FILTERS
========================================================= --}}

@if(
    request()->filled('employer_id')
    ||
    request()->filled('period')
    ||
    request()->filled('currency')
)

    <div class="alert alert-info">

        <i class="mdi mdi-filter-check-outline me-1"></i>


        <strong>

            Filters Applied:

        </strong>


        @if(
            request()->filled(
                'employer_id'
            )
        )

            @php

                $selectedEmployer =
                    $employers
                        ->firstWhere(
                            'id',
                            (int)
                            request(
                                'employer_id'
                            )
                        );

            @endphp


            @if($selectedEmployer)

                <span class="badge bg-primary ms-1">

                    Employer:

                    {{
                        $selectedEmployer
                            ->name
                    }}

                </span>

            @endif

        @endif


        @if(
            request()->filled(
                'period'
            )
        )

            <span class="badge bg-primary ms-1">

                Period:

                {{
                    \Carbon\Carbon::createFromFormat(
                        'Y-m',
                        request('period')
                    )->format(
                        'M Y'
                    )
                }}

            </span>

        @endif


        @if(
            request()->filled(
                'currency'
            )
        )

            <span class="badge bg-primary ms-1">

                Currency:

                {{
                    request(
                        'currency'
                    )
                }}

            </span>

        @endif

    </div>

@endif


{{-- =========================================================
     POSTED RECEIPT REGISTER
========================================================= --}}

<div class="card">

    <div class="card-body">


        {{-- =====================================================
             HEADER
        ====================================================== --}}

        <div class="mb-3">

            <h4 class="header-title mb-1">

                Posted Employer Receipt Register

            </h4>


            <p class="text-muted mb-0">

                This register contains actual employer cash
                remittances posted to the Pension Fund.

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


                    <div id="receipt-export-buttons"></div>

                </div>


                <div id="receipt-search-area"></div>

            </div>

        </div>


        {{-- =====================================================
             TABLE
        ====================================================== --}}

        <div class="table-responsive">

            <table
                id="receipt-register-table"
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
                            Receipt
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
                            Actual Paid ZWG
                        </th>

                        <th class="text-center">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @foreach(
                        $receipts
                        as $receipt
                    )

                        <tr>


                            {{-- Receipt --}}
                            <td
                                data-order="{{
                                    $receipt->id
                                }}"
                            >

                                <strong>

                                    #{{ $receipt->id }}

                                </strong>

                            </td>


                            {{-- Employer Code --}}
                            <td>

                                <strong>

                                    {{
                                        $receipt
                                            ->employer
                                            ?->employer_number
                                        ??
                                        '-'
                                    }}

                                </strong>

                            </td>


                            {{-- Employer --}}
                            <td>

                                {{
                                    $receipt
                                        ->employer
                                        ?->name
                                    ??
                                    '-'
                                }}

                            </td>


                            {{-- Receipt Date --}}
                            <td
                                data-order="{{
                                    $receipt
                                        ->receipt_date
                                        ?->timestamp
                                    ??
                                    0
                                }}"
                            >

                                {{
                                    $receipt
                                        ->receipt_date
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
                                    $receipt
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

                                <span
                                    class="badge {{
                                        $receipt->currency
                                        ===
                                        'USD'
                                            ? 'bg-primary'
                                            : 'bg-secondary'
                                    }}"
                                >

                                    {{
                                        $receipt
                                            ->currency
                                    }}

                                </span>

                            </td>


                            {{-- Original Amount --}}
                            <td
                                class="
                                    text-end
                                    receipt-amount-cell
                                "
                                data-order="{{
                                    $receipt
                                        ->original_amount
                                    ??
                                    0
                                }}"
                            >

                                {{
                                    number_format(
                                        (float)
                                        $receipt
                                            ->original_amount,
                                        2
                                    )
                                }}

                            </td>


                            {{-- Exchange Rate --}}
                            <td
                                class="
                                    text-end
                                    receipt-amount-cell
                                "
                                data-order="{{
                                    $receipt
                                        ->exchange_rate
                                    ??
                                    0
                                }}"
                            >

                                {{
                                    number_format(
                                        (float)
                                        $receipt
                                            ->exchange_rate,
                                        8
                                    )
                                }}

                            </td>


                            {{-- Actual Paid ZWG --}}
                            <td
                                class="
                                    text-end
                                    receipt-amount-cell
                                "
                                data-order="{{
                                    $receipt
                                        ->amount_zwg
                                    ??
                                    0
                                }}"
                            >

                                <strong>

                                    {{
                                        number_format(
                                            (float)
                                            $receipt
                                                ->amount_zwg,
                                            2
                                        )
                                    }}

                                </strong>

                            </td>


                            {{-- Action --}}
                            <td
                                class="
                                    text-center
                                    receipt-action-buttons
                                "
                            >

                                <a
                                    href="{{
                                        route(
                                            'pensions-administration.contributions.receipts.show',
                                            $receipt
                                        )
                                    }}"
                                    class="
                                        btn
                                        btn-sm
                                        btn-outline-primary
                                    "
                                    title="View Receipt"
                                >

                                    <i
                                        class="
                                            mdi
                                            mdi-eye-outline
                                        "
                                    ></i>

                                </a>

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
        | DataTables controls paging on the records delivered to this page.
        |
        | If ContributionReceiptController still uses paginate(50),
        | DataTables can only search/export those 50 records.
        |
        | For a fully client-side register, return ->get() instead.
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
                '#receipt-register-table'
            )
        ) {

            $('#receipt-register-table')
                .DataTable()
                .destroy();

        }


        /*
        |--------------------------------------------------------------------------
        | Initialise DataTable
        |--------------------------------------------------------------------------
        */

        const table =
            $('#receipt-register-table')
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
                                'PENERP Posted Contribution Receipt Register',

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
                                    8
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
                                'PENERP Posted Contribution Receipt Register',

                            filename:
                                'PENERP_Posted_Contribution_Receipts',

                            sheetName:
                                'Contribution Receipts',

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
                                    8
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
                                'PENERP Posted Contribution Receipt Register',

                            filename:
                                'PENERP_Posted_Contribution_Receipts',

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
                                    8
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
                                'PENERP Posted Contribution Receipt Register',

                            filename:
                                'PENERP_Posted_Contribution_Receipts',

                            orientation:
                                'landscape',

                            pageSize:
                                'A4',

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
                                    8
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


                        {
                            extend:
                                'print',

                            text:
                                '<i class="mdi mdi-printer-outline me-1"></i> Print',

                            className:
                                'btn btn-dark btn-sm',

                            title:
                                'PENERP Posted Contribution Receipt Register',

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
                                    8
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
                                                        PENERP Posted Contribution Receipt Register
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
                    | Action Column
                    |--------------------------------------------------------------------------
                    */

                    columnDefs: [

                        {
                            targets:
                                9,

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
                            'Search posted receipts...',

                        lengthMenu:
                            'Show _MENU_ receipts',

                        info:
                            'Showing _START_ to _END_ of _TOTAL_ receipts',

                        infoEmpty:
                            'No receipts found',

                        zeroRecords:
                            'No matching receipts found',

                        processing:
                            'Loading receipts...',

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
        | Export Buttons
        |--------------------------------------------------------------------------
        */

        table
            .buttons()
            .container()
            .appendTo(
                '#receipt-export-buttons'
            );


        /*
        |--------------------------------------------------------------------------
        | Quick Search
        |--------------------------------------------------------------------------
        */

        $('#receipt-search-area')
            .html(
                `
                    <div
                        class="
                            d-flex
                            align-items-center
                        "
                    >

                        <label
                            for="receipt-quick-search"
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
                            id="receipt-quick-search"
                            class="form-control form-control-sm"
                            placeholder="Search posted receipts..."
                            style="min-width:230px;"
                        >

                    </div>
                `
            );


        $('#receipt-quick-search')
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

        $('#receipt-export-buttons .dt-button')
            .removeClass(
                'dt-button'
            );

    }
);

</script>

@endpush
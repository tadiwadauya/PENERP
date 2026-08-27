@extends('layouts.app')

@section('title', 'Receipt Imports')

@section('page-heading', 'Contribution Receipt Imports')


@section('page-actions')

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
    | Receipt Import Table
    |--------------------------------------------------------------------------
    */

    #receipt-import-table {
        width: 100% !important;
    }


    #receipt-import-table th {
        white-space: nowrap;
        vertical-align: middle;
    }


    #receipt-import-table td {
        vertical-align: middle;
    }


    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    .receipt-status-badge {
        min-width: 95px;
    }


    /*
    |--------------------------------------------------------------------------
    | Action Buttons
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

    #receipt-import-table_wrapper .pagination {
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
     RECEIPT IMPORT REGISTER
========================================================= --}}

<div class="card">

    <div class="card-body">


        {{-- =====================================================
             HEADER
        ====================================================== --}}

        <div class="mb-3">

            <h4 class="header-title mb-1">

                Contribution Receipt Import Register

            </h4>


            <p class="text-muted mb-0">

                View employer receipt files that have been uploaded,
                validated and posted to the contribution receipts register.

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
                id="receipt-import-table"
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
                            Uploaded
                        </th>

                        <th>
                            File
                        </th>

                        <th>
                            Currency
                        </th>

                        <th>
                            Total Rows
                        </th>

                        <th>
                            Valid
                        </th>

                        <th>
                            Errors
                        </th>

                        <th>
                            Posted
                        </th>

                        <th>
                            Status
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

                        @php

                            $statusClass =
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


                        <tr>


                            {{-- Batch --}}
                            <td
                                data-order="{{ $batch->id }}"
                            >

                                <strong>

                                    #{{ $batch->id }}

                                </strong>

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


                            {{-- File --}}
                            <td>

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

                            </td>


                            {{-- Currency --}}
                            <td>

                                <span class="badge bg-secondary">

                                    {{
                                        $batch
                                            ->default_currency
                                        ??
                                        'ZWG'
                                    }}

                                </span>

                            </td>


                            {{-- Total --}}
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


                            {{-- Valid --}}
                            <td
                                data-order="{{
                                    $batch
                                        ->valid_rows
                                    ??
                                    0
                                }}"
                            >

                                <span class="text-success">

                                    {{
                                        number_format(
                                            $batch
                                                ->valid_rows
                                            ??
                                            0
                                        )
                                    }}

                                </span>

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


                            {{-- Posted --}}
                            <td
                                data-order="{{
                                    $batch
                                        ->posted_rows
                                    ??
                                    0
                                }}"
                            >

                                {{
                                    number_format(
                                        $batch
                                            ->posted_rows
                                        ??
                                        0
                                    )
                                }}

                            </td>


                            {{-- Status --}}
                            <td>

                                <span
                                    class="
                                        badge
                                        receipt-status-badge
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
                                    'failed'
                                    &&
                                    filled(
                                        $batch->failure_reason
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
                                                    ->failure_reason,
                                                70
                                            )
                                        }}

                                    </div>

                                @endif

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
                                            'pensions-administration.contributions.receipts.imports.review',
                                            $batch
                                        )
                                    }}"
                                    class="
                                        btn
                                        btn-sm
                                        btn-primary
                                    "
                                    title="Review Receipt Import"
                                >

                                    <i
                                        class="
                                            mdi
                                            mdi-file-search-outline
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
        | DataTables Pagination
        |--------------------------------------------------------------------------
        |
        | Laravel pagination is not shown here because DataTables manages
        | paging, searching and export on this register.
        |--------------------------------------------------------------------------
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
                '#receipt-import-table'
            )
        ) {

            $('#receipt-import-table')
                .DataTable()
                .destroy();

        }


        /*
        |--------------------------------------------------------------------------
        | Initialise DataTable
        |--------------------------------------------------------------------------
        */

        const table =
            $('#receipt-import-table')
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
                                'PENERP Contribution Receipt Import Register',

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
                                'PENERP Contribution Receipt Import Register',

                            filename:
                                'PENERP_Contribution_Receipt_Imports',

                            sheetName:
                                'Receipt Imports',

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
                                'PENERP Contribution Receipt Import Register',

                            filename:
                                'PENERP_Contribution_Receipt_Imports',

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
                                'PENERP Contribution Receipt Import Register',

                            filename:
                                'PENERP_Contribution_Receipt_Imports',

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
                                'PENERP Contribution Receipt Import Register',

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
                            'Search receipt imports...',

                        lengthMenu:
                            'Show _MENU_ receipt batches',

                        info:
                            'Showing _START_ to _END_ of _TOTAL_ receipt batches',

                        infoEmpty:
                            'No receipt batches found',

                        zeroRecords:
                            'No matching receipt batches found',

                        processing:
                            'Loading receipt batches...',

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
                            placeholder="Search receipt imports..."
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
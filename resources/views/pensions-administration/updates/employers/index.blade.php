@extends('layouts.app')

@section('title', 'Employers')

@section('page-heading', 'Employers')

@section('content')

@include('pensions-administration.partials.navigation')


@if(session('success'))
    <div class="alert alert-success">
        <i class="mdi mdi-check-circle-outline me-1"></i>
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        <i class="mdi mdi-alert-circle-outline me-1"></i>
        {{ session('error') }}
    </div>
@endif


{{-- =========================================================
     PAGE HEADER
========================================================= --}}

<div class="card">
    <div class="card-body">

        <div class="d-flex flex-wrap justify-content-between align-items-center">

            <div>
                <h4 class="header-title mb-1">
                    Employer Register
                </h4>

                <p class="text-muted mb-0">
                    View and manage employers registered in PENERP.
                </p>
            </div>


            <div class="d-flex gap-2 mt-3 mt-md-0">

                <a href="{{ route('pensions-administration.updates.employer-imports.index') }}"
                   class="btn btn-light">

                    <i class="mdi mdi-file-upload-outline me-1"></i>
                    Employer Imports

                </a>


                <a href="{{ route('pensions-administration.updates.employers.create') }}"
                   class="btn btn-primary">

                    <i class="mdi mdi-plus me-1"></i>
                    Add Employer

                </a>

            </div>

        </div>

    </div>
</div>


{{-- =========================================================
     EMPLOYER TABLE
========================================================= --}}

<div class="card">

    <div class="card-body">

        <div class="table-responsive">

            <table id="employers-table"
                   class="table table-bordered table-striped table-hover align-middle w-100">

                <thead>

                    <tr>

                        <th>
                            PENERP No.
                        </th>

                        <th>
                            PenAd No.
                        </th>

                        <th>
                            Fundworx No.
                        </th>

                        <th>
                            Employer
                        </th>

                        <th>
                            Group
                        </th>

                        <th>
                            Contact
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Created
                        </th>

                        <th class="text-center">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @foreach($employers as $employer)

                        <tr>

                            {{-- PENERP NUMBER --}}
                            <td>

                                <strong>
                                    {{ $employer->employer_number }}
                                </strong>

                            </td>


                            {{-- PENAD NUMBER --}}
                            <td>

                                {{ $employer->penad_employer_number ?? '-' }}

                            </td>


                            {{-- FUNDWORX NUMBER --}}
                            <td>

                                {{ $employer->fundworx_employer_number ?? '-' }}

                            </td>


                            {{-- EMPLOYER --}}
                            <td>

                                <strong>
                                    {{ $employer->name }}
                                </strong>


                                @if($employer->short_name)

                                    <br>

                                    <small class="text-muted">
                                        {{ $employer->short_name }}
                                    </small>

                                @endif

                            </td>


                            {{-- EMPLOYER GROUP --}}
                            <td>

                                @if($employer->employerGroup)

                                    <strong>
                                        {{ $employer->employerGroup->name }}
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        {{ $employer->employerGroup->code }}
                                    </small>

                                @else

                                    <span class="text-muted">
                                        -
                                    </span>

                                @endif

                            </td>


                            {{-- CONTACT --}}
                            <td>

                                @if($employer->telephone)

                                    <div class="mb-1">

                                        <i class="mdi mdi-phone-outline me-1"></i>

                                        {{ $employer->telephone }}

                                    </div>

                                @endif


                                @if($employer->email)

                                    <div>

                                        <i class="mdi mdi-email-outline me-1"></i>

                                        {{ $employer->email }}

                                    </div>

                                @endif


                                @if(!$employer->telephone && !$employer->email)

                                    <span class="text-muted">
                                        -
                                    </span>

                                @endif

                            </td>


                            {{-- STATUS --}}
                            <td>

                                @if($employer->is_active)

                                    <span class="badge bg-success">
                                        Active
                                    </span>

                                @else

                                    <span class="badge bg-secondary">
                                        Inactive
                                    </span>

                                @endif

                            </td>


                            {{-- CREATED --}}
                            <td data-order="{{ $employer->created_at?->timestamp ?? 0 }}">

                                {{ $employer->created_at
                                    ? $employer->created_at->format('d M Y')
                                    : '-'
                                }}

                            </td>


                            {{-- ACTIONS --}}
                            <td class="text-center">

                                <div class="d-flex justify-content-center gap-1">


                                    {{-- VIEW --}}
                                    <a href="{{ route('pensions-administration.updates.employers.show', $employer) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="View Employer">

                                        <i class="mdi mdi-eye-outline"></i>

                                    </a>


                                    {{-- EDIT --}}
                                    <a href="{{ route('pensions-administration.updates.employers.edit', $employer) }}"
                                       class="btn btn-sm btn-primary"
                                       title="Edit Employer">

                                        <i class="mdi mdi-pencil-outline"></i>

                                    </a>


                                </div>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection


{{-- =========================================================
     DATATABLE STYLES
========================================================= --}}

@push('styles')

<link rel="stylesheet"
      href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<link rel="stylesheet"
      href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">


<style>

    #employers-table th {
        white-space: nowrap;
    }


    #employers-table td {
        vertical-align: middle;
    }


    .dt-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }


    .dt-buttons .btn {
        margin: 0 !important;
    }


    .dataTables_filter {
        text-align: right;
    }


    .dataTables_filter input {
        margin-left: 8px !important;
        min-width: 220px;
    }


    .dataTables_length select {
        min-width: 75px;
    }


    .employer-datatable-toolbar {
        margin-bottom: 15px;
    }


    @media(max-width: 768px) {

        .dataTables_filter {
            text-align: left;
            margin-top: 10px;
        }

        .dataTables_filter input {
            min-width: 160px;
        }

    }

</style>

@endpush


{{-- =========================================================
     DATATABLE LIBRARIES
========================================================= --}}

@push('scripts-before-app')

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>


{{-- Excel --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>


{{-- PDF --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>


{{-- Buttons --}}
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

@endpush


{{-- =========================================================
     DATATABLE INITIALISATION
========================================================= --}}

@push('scripts')

<script>

$(document).ready(function () {

    if (
        $.fn.DataTable.isDataTable(
            '#employers-table'
        )
    ) {
        $('#employers-table')
            .DataTable()
            .destroy();
    }


    $('#employers-table').DataTable({

        /*
        |--------------------------------------------------------------------------
        | General
        |--------------------------------------------------------------------------
        */

        responsive: false,

        processing: true,

        pageLength: 25,


        lengthMenu: [

            [10, 25, 50, 100, -1],

            [10, 25, 50, 100, 'All']

        ],


        /*
        |--------------------------------------------------------------------------
        | Default Order
        |--------------------------------------------------------------------------
        |
        | Employer name
        |
        */

        order: [
            [3, 'asc']
        ],


        /*
        |--------------------------------------------------------------------------
        | Layout
        |--------------------------------------------------------------------------
        |
        | B = Buttons
        | f = Search
        | l = Length
        | i = Information
        | t = Table
        | p = Pagination
        |
        */

        dom:

            "<'row employer-datatable-toolbar align-items-center'"

                + "<'col-xl-8 col-lg-8 col-md-12 mb-2 mb-lg-0'B>"

                + "<'col-xl-4 col-lg-4 col-md-12'f>"

            + ">"

            + "<'row mb-2'"

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
            | COPY
            |--------------------------------------------------------------------------
            */

            {
                extend: 'copyHtml5',

                text:
                    '<i class="mdi mdi-content-copy me-1"></i> Copy',

                className:
                    'btn btn-secondary btn-sm',

                title:
                    'PENERP Employer Register',

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
                    ],

                    stripHtml: true
                }
            },


            /*
            |--------------------------------------------------------------------------
            | EXCEL
            |--------------------------------------------------------------------------
            */

            {
                extend: 'excelHtml5',

                text:
                    '<i class="mdi mdi-microsoft-excel me-1"></i> Excel',

                className:
                    'btn btn-success btn-sm',

                title:
                    'PENERP Employer Register',

                filename:
                    'PENERP_Employer_Register',

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
                    ],

                    stripHtml: true
                }
            },


            /*
            |--------------------------------------------------------------------------
            | CSV
            |--------------------------------------------------------------------------
            */

            {
                extend: 'csvHtml5',

                text:
                    '<i class="mdi mdi-file-delimited-outline me-1"></i> CSV',

                className:
                    'btn btn-info btn-sm',

                title:
                    'PENERP Employer Register',

                filename:
                    'PENERP_Employer_Register',

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
                    ],

                    stripHtml: true
                }
            },


            /*
            |--------------------------------------------------------------------------
            | PDF
            |--------------------------------------------------------------------------
            */

            {
                extend: 'pdfHtml5',

                text:
                    '<i class="mdi mdi-file-pdf-box me-1"></i> PDF',

                className:
                    'btn btn-danger btn-sm',

                title:
                    'PENERP Employer Register',

                filename:
                    'PENERP_Employer_Register',

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
                        7
                    ],

                    stripHtml: true
                },


                customize: function (doc) {

                    doc.defaultStyle.fontSize = 8;

                    doc.styles.tableHeader.fontSize = 9;

                    doc.styles.title = {

                        fontSize: 16,

                        bold: true,

                        alignment: 'center',

                        margin: [0, 0, 0, 15]

                    };


                    if (
                        doc.content
                        && doc.content[1]
                        && doc.content[1].table
                    ) {

                        doc.content[1].table.widths = [

                            '8%',
                            '8%',
                            '10%',
                            '20%',
                            '15%',
                            '18%',
                            '8%',
                            '10%'

                        ];

                    }

                }
            },


            /*
            |--------------------------------------------------------------------------
            | PRINT
            |--------------------------------------------------------------------------
            */

            {
                extend: 'print',

                text:
                    '<i class="mdi mdi-printer-outline me-1"></i> Print',

                className:
                    'btn btn-dark btn-sm',

                title:
                    'PENERP Employer Register',

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
                    ],

                    stripHtml: true
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
                targets: 8,

                orderable: false,

                searchable: false
            }

        ],


        /*
        |--------------------------------------------------------------------------
        | Language
        |--------------------------------------------------------------------------
        */

        language: {

            search:
                'Search Employers:',

            searchPlaceholder:
                'Name, number, group...',

            lengthMenu:
                'Show _MENU_ employers',

            info:
                'Showing _START_ to _END_ of _TOTAL_ employers',

            infoEmpty:
                'No employers found',

            infoFiltered:
                '(filtered from _MAX_ employers)',

            zeroRecords:
                'No matching employers found',

            processing:
                'Loading employers...'

        }

    });

});

</script>

@endpush
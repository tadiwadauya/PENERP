@extends('layouts.app')

@section('title', 'Membership')

@section('page-heading', 'Membership')


@section('page-actions')

<a href="{{ route('pensions-administration.updates.members.create') }}" class="btn btn-success">
    <i class="mdi mdi-account-plus-outline me-1"></i>
    Add Member
</a>

@endsection


@push('styles')

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<style>
    #membership-table {
        width: 100% !important;
    }

    #membership-table th {
        white-space: nowrap;
    }

    #membership-table td {
        vertical-align: middle;
    }

    #membership-table_wrapper .dt-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    #membership-table_wrapper .dt-buttons .btn {
        margin: 0 !important;
    }

    #membership-table_wrapper .dataTables_filter {
        text-align: right;
    }

    #membership-table_wrapper .dataTables_filter input {
        margin-left: 8px !important;
        min-width: 220px;
    }

    #membership-table_wrapper .dataTables_length select {
        min-width: 75px;
    }

    #membership-table_wrapper .dataTables_processing {
        position: absolute;
        top: 80px;
        left: 50%;
        width: auto;
        min-width: 220px;
        margin-left: -110px;
        padding: 14px 20px;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, .10);
        z-index: 100;
    }

    .membership-filter-card {
        border-left: 4px solid #0d6efd;
    }

    .membership-datatable-toolbar {
        margin-bottom: 15px;
    }

    .member-action-buttons {
        white-space: nowrap;
    }

    @media(max-width: 768px) {
        #membership-table_wrapper .dataTables_filter {
            text-align: left;
            margin-top: 10px;
        }

        #membership-table_wrapper .dataTables_filter input {
            min-width: 160px;
        }
    }
</style>

@endpush


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
     FILTERS
========================================================= --}}

<div class="card membership-filter-card">

    <div class="card-body">

        <div class="mb-3">

            <h4 class="header-title mb-1">
                Membership Filters
            </h4>

            <p class="text-muted mb-0">
                Apply filters without loading the full membership register.
            </p>

        </div>


        <form id="membership-filter-form">

            <div class="row">


                {{-- GENERAL SEARCH --}}
                <div class="col-xl-4 col-lg-6 col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            General Search
                        </label>

                        <input id="filter-search"
                               type="text"
                               class="form-control"
                               placeholder="Name, National ID, member number...">

                    </div>

                </div>


                {{-- PENERP NUMBER --}}
                <div class="col-xl-2 col-lg-3 col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            PENERP Number
                        </label>

                        <input id="filter-penerp-number"
                               type="text"
                               class="form-control"
                               placeholder="PENERP member no.">

                    </div>

                </div>


                {{-- PENAD NUMBER --}}
                <div class="col-xl-2 col-lg-3 col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            PenAd Number
                        </label>

                        <input id="filter-penad-number"
                               type="text"
                               class="form-control"
                               placeholder="PenAd member no.">

                    </div>

                </div>


                {{-- FUNDWORX NUMBER --}}
                <div class="col-xl-2 col-lg-3 col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            Fundworx Number
                        </label>

                        <input id="filter-fundworx-number"
                               type="text"
                               class="form-control"
                               placeholder="Fundworx member no.">

                    </div>

                </div>


                {{-- STATUS --}}
                <div class="col-xl-2 col-lg-3 col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select id="filter-status"
                                class="form-select">

                            <option value="">
                                All Statuses
                            </option>

                            <option value="active">
                                Active
                            </option>

                            <option value="dormant">
                                Dormant
                            </option>

                            <option value="inactive">
                                Inactive
                            </option>

                            <option value="suspended">
                                Suspended
                            </option>

                        </select>

                    </div>

                </div>


                {{-- EMPLOYER --}}
                <div class="col-xl-6 col-lg-6 col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            Employer
                        </label>

                        <select id="filter-employer"
                                class="form-select">

                            <option value="">
                                All Employers
                            </option>

                            @foreach($employers as $employer)

                                <option value="{{ $employer->id }}">
                                    {{ $employer->employer_number }}
                                    -
                                    {{ $employer->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>


                {{-- BUTTONS --}}
                <div class="col-xl-6 col-lg-6 col-md-6">

                    <div class="mb-3">

                        <label class="form-label d-block">
                            &nbsp;
                        </label>

                        <div class="d-flex flex-wrap gap-2">

                            <button type="submit"
                                    class="btn btn-primary">

                                <i class="mdi mdi-filter-outline me-1"></i>
                                Apply Filters

                            </button>


                            <button id="clear-membership-filters"
                                    type="button"
                                    class="btn btn-light">

                                <i class="mdi mdi-filter-remove-outline me-1"></i>
                                Clear Filters

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </form>

    </div>

</div>


{{-- =========================================================
     MEMBERSHIP REGISTER
========================================================= --}}

<div class="card">

    <div class="card-body">

        <div class="mb-3">

            <h4 class="header-title mb-1">
                Membership Register
            </h4>

            <p class="text-muted mb-0">
                Members are loaded directly from SQL Server in small pages for faster performance.
            </p>

        </div>


        <div class="table-responsive">

            <table id="membership-table"
                   class="table table-bordered table-striped table-hover align-middle nowrap"
                   style="width:100%">

                <thead>

                    <tr>

                        <th>PENERP No.</th>
                        <th>PenAd No.</th>
                        <th>Fundworx No.</th>
                        <th>Member</th>
                        <th>National ID</th>
                        <th>Employer</th>
                        <th>Staff No.</th>
                        <th>Vote No.</th>
                        <th>Status</th>

                        <th class="text-center">
                            Actions
                        </th>

                    </tr>

                </thead>

                <tbody></tbody>

            </table>

        </div>

    </div>

</div>

@endsection


@push('scripts')

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>


<script>
$(document).ready(function () {

    /*
    |--------------------------------------------------------------------------
    | Check DataTables Loaded
    |--------------------------------------------------------------------------
    */

    if (
        typeof $.fn.DataTable
        ===
        'undefined'
    ) {
        console.error(
            'DataTables library did not load.'
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Prevent Duplicate Initialisation
    |--------------------------------------------------------------------------
    */

    if (
        $.fn.DataTable
        .isDataTable(
            '#membership-table'
        )
    ) {
        $('#membership-table')
            .DataTable()
            .destroy();
    }


    /*
    |--------------------------------------------------------------------------
    | Initialise Server-Side Membership Table
    |--------------------------------------------------------------------------
    */

    const membershipTable =
        $('#membership-table')
            .DataTable({

                processing:
                    true,

                serverSide:
                    true,

                deferRender:
                    true,

                searchDelay:
                    500,

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
                        100
                    ],
                    [
                        10,
                        25,
                        50,
                        100
                    ]
                ],


                /*
                |--------------------------------------------------------------------------
                | AJAX
                |--------------------------------------------------------------------------
                */

                ajax: {

                    url:
                        "{{ route('pensions-administration.updates.members.data') }}",

                    type:
                        'GET',


                    data: function (
                        data
                    ) {

                        data.filter_search =
                            $('#filter-search')
                                .val();

                        data.penerp_member_number =
                            $('#filter-penerp-number')
                                .val();

                        data.penad_member_number =
                            $('#filter-penad-number')
                                .val();

                        data.fundworx_member_number =
                            $('#filter-fundworx-number')
                                .val();

                        data.status =
                            $('#filter-status')
                                .val();

                        data.employer_id =
                            $('#filter-employer')
                                .val();

                    },


                    error: function (
                        xhr
                    ) {

                        console.error(
                            'Membership DataTable AJAX request failed.'
                        );

                        console.error(
                            'HTTP Status:',
                            xhr.status
                        );

                        console.error(
                            xhr.responseText
                        );

                    }

                },


                /*
                |--------------------------------------------------------------------------
                | Columns
                |--------------------------------------------------------------------------
                */

                columns: [

                    {
                        data:
                            'penerp_number',

                        name:
                            'member_number'
                    },


                    {
                        data:
                            'penad_number',

                        name:
                            'penad_member_number'
                    },


                    {
                        data:
                            'fundworx_number',

                        name:
                            'fundworx_member_number'
                    },


                    {
                        data:
                            'member',

                        name:
                            'surname'
                    },


                    {
                        data:
                            'national_id',

                        name:
                            'national_id'
                    },


                    {
                        data:
                            'employer',

                        name:
                            'employer',

                        orderable:
                            false
                    },


                    {
                        data:
                            'staff_number',

                        name:
                            'staff_number',

                        orderable:
                            false
                    },


                    {
                        data:
                            'vote_number',

                        name:
                            'vote_number',

                        orderable:
                            false
                    },


                    {
                        data:
                            'status',

                        name:
                            'membership_status'
                    },


                    {
                        data:
                            'actions',

                        name:
                            'actions',

                        orderable:
                            false,

                        searchable:
                            false,

                        className:
                            'text-center'
                    }

                ],


                /*
                |--------------------------------------------------------------------------
                | Default Ordering
                |--------------------------------------------------------------------------
                */

                order: [
                    [
                        3,
                        'asc'
                    ]
                ],


                /*
                |--------------------------------------------------------------------------
                | Toolbar
                |--------------------------------------------------------------------------
                */

                dom:

                    "<'row membership-datatable-toolbar align-items-center'"

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
                |
                | Because this is server-side DataTables, these export the
                | records currently loaded on the page.
                |
                */

                buttons: [

                    {
                        extend:
                            'copyHtml5',

                        text:
                            '<i class="mdi mdi-content-copy me-1"></i> Copy Page',

                        className:
                            'btn btn-secondary btn-sm',

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
                            '<i class="mdi mdi-microsoft-excel me-1"></i> Excel Page',

                        className:
                            'btn btn-success btn-sm',

                        title:
                            'PENERP Membership Register',

                        filename:
                            'PENERP_Membership_Register',

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
                            '<i class="mdi mdi-file-delimited-outline me-1"></i> CSV Page',

                        className:
                            'btn btn-info btn-sm',

                        title:
                            'PENERP Membership Register',

                        filename:
                            'PENERP_Membership_Register',

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
                            'print',

                        text:
                            '<i class="mdi mdi-printer-outline me-1"></i> Print Page',

                        className:
                            'btn btn-dark btn-sm',

                        title:
                            'PENERP Membership Register',

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
                | Language
                |--------------------------------------------------------------------------
                */

                language: {

                    processing:
                        '<i class="mdi mdi-loading mdi-spin me-1"></i> Loading members...',

                    search:
                        'Quick Search:',

                    searchPlaceholder:
                        'Search members...',

                    lengthMenu:
                        'Show _MENU_ members',

                    info:
                        'Showing _START_ to _END_ of _TOTAL_ members',

                    infoEmpty:
                        'No members found',

                    infoFiltered:
                        '(filtered from _MAX_ members)',

                    zeroRecords:
                        'No matching members found'

                }

            });


    /*
    |--------------------------------------------------------------------------
    | Apply Filters
    |--------------------------------------------------------------------------
    */

    $('#membership-filter-form')
        .on(
            'submit',
            function (
                event
            ) {

                event.preventDefault();

                membershipTable
                    .page(
                        'first'
                    );

                membershipTable
                    .ajax
                    .reload();

            }
        );


    /*
    |--------------------------------------------------------------------------
    | Clear Filters
    |--------------------------------------------------------------------------
    */

    $('#clear-membership-filters')
        .on(
            'click',
            function () {

                $('#filter-search')
                    .val('');

                $('#filter-penerp-number')
                    .val('');

                $('#filter-penad-number')
                    .val('');

                $('#filter-fundworx-number')
                    .val('');

                $('#filter-status')
                    .val('');

                $('#filter-employer')
                    .val('');

                membershipTable
                    .search('');

                membershipTable
                    .page(
                        'first'
                    );

                membershipTable
                    .ajax
                    .reload();

            }
        );


    /*
    |--------------------------------------------------------------------------
    | Enter Key Applies Filters
    |--------------------------------------------------------------------------
    */

    $('#membership-filter-form input')
        .on(
            'keypress',
            function (
                event
            ) {

                if (
                    event.which
                    ===
                    13
                ) {
                    event.preventDefault();

                    $('#membership-filter-form')
                        .trigger(
                            'submit'
                        );
                }

            }
        );

});
</script>

@endpush
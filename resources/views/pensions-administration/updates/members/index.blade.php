@extends('layouts.app')

@section('title', 'Membership')

@section('page-heading', 'Membership')

@section('page-actions')

    <a href="{{ route('pensions-administration.updates.members.create') }}"
       class="btn btn-success">

        <i class="mdi mdi-account-plus-outline me-1"></i>
        Add Member

    </a>

@endsection


@push('styles')

<link rel="stylesheet"
      href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<link rel="stylesheet"
      href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<style>

    /*
    |--------------------------------------------------------------------------
    | Membership Table Only
    |--------------------------------------------------------------------------
    |
    | Keep DataTables styling scoped to this table so that it does not affect
    | the PENERP sidebar or other layout components.
    |
    */

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
     ADVANCED FILTERS
========================================================= --}}

<div class="card membership-filter-card">

    <div class="card-body">

        <div class="mb-3">

            <h4 class="header-title mb-1">
                Membership Filters
            </h4>

            <p class="text-muted mb-0">
                Filter the membership register using specific member references,
                employer or membership status.
            </p>

        </div>


        <form method="GET"
              action="{{ route('pensions-administration.updates.members.index') }}">

            <div class="row">


                {{-- GENERAL SEARCH --}}
                <div class="col-xl-4 col-lg-6 col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            General Search
                        </label>

                        <input type="text"
                               name="search"
                               class="form-control"
                               value="{{ request('search') }}"
                               placeholder="Name, National ID, member number...">

                    </div>

                </div>


                {{-- PENERP NUMBER --}}
                <div class="col-xl-2 col-lg-3 col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            PENERP Number
                        </label>

                        <input type="text"
                               name="penerp_member_number"
                               class="form-control"
                               value="{{ request('penerp_member_number') }}"
                               placeholder="PENERP member no.">

                    </div>

                </div>


                {{-- PENAD NUMBER --}}
                <div class="col-xl-2 col-lg-3 col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            PenAd Number
                        </label>

                        <input type="text"
                               name="penad_member_number"
                               class="form-control"
                               value="{{ request('penad_member_number') }}"
                               placeholder="PenAd member no.">

                    </div>

                </div>


                {{-- FUNDWORX NUMBER --}}
                <div class="col-xl-2 col-lg-3 col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            Fundworx Number
                        </label>

                        <input type="text"
                               name="fundworx_member_number"
                               class="form-control"
                               value="{{ request('fundworx_member_number') }}"
                               placeholder="Fundworx member no.">

                    </div>

                </div>


                {{-- STATUS --}}
                <div class="col-xl-2 col-lg-3 col-md-6">

                    <div class="mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select name="status"
                                class="form-select">

                            <option value="">
                                All Statuses
                            </option>

                            <option value="active"
                                @selected(request('status') === 'active')>
                                Active
                            </option>

                            <option value="dormant"
                                @selected(request('status') === 'dormant')>
                                Dormant
                            </option>

                            <option value="inactive"
                                @selected(request('status') === 'inactive')>
                                Inactive
                            </option>

                            <option value="suspended"
                                @selected(request('status') === 'suspended')>
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

                        <select name="employer_id"
                                class="form-select">

                            <option value="">
                                All Employers
                            </option>

                            @foreach($employers as $employer)

                                <option value="{{ $employer->id }}"
                                    @selected(
                                        request('employer_id') == $employer->id
                                    )>

                                    {{ $employer->employer_number }}
                                    -
                                    {{ $employer->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>


                {{-- FILTER BUTTONS --}}
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


                            <a href="{{ route('pensions-administration.updates.members.index') }}"
                               class="btn btn-light">

                                <i class="mdi mdi-filter-remove-outline me-1"></i>
                                Clear Filters

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
    request()->filled('search')
    || request()->filled('penerp_member_number')
    || request()->filled('penad_member_number')
    || request()->filled('fundworx_member_number')
    || request()->filled('employer_id')
    || request()->filled('status')
)

    <div class="alert alert-info">

        <div class="d-flex align-items-start">

            <i class="mdi mdi-filter-check-outline font-size-20 me-2"></i>

            <div>

                <strong>
                    Filters Applied
                </strong>

                <div class="mt-1">


                    @if(request()->filled('search'))

                        <span class="badge bg-primary me-1 mb-1">
                            Search: {{ request('search') }}
                        </span>

                    @endif


                    @if(request()->filled('penerp_member_number'))

                        <span class="badge bg-primary me-1 mb-1">
                            PENERP: {{ request('penerp_member_number') }}
                        </span>

                    @endif


                    @if(request()->filled('penad_member_number'))

                        <span class="badge bg-primary me-1 mb-1">
                            PenAd: {{ request('penad_member_number') }}
                        </span>

                    @endif


                    @if(request()->filled('fundworx_member_number'))

                        <span class="badge bg-primary me-1 mb-1">
                            Fundworx: {{ request('fundworx_member_number') }}
                        </span>

                    @endif


                    @if(request()->filled('status'))

                        <span class="badge bg-primary me-1 mb-1">
                            Status: {{ ucfirst(request('status')) }}
                        </span>

                    @endif


                    @if(request()->filled('employer_id'))

                        @php

                            $selectedEmployer = $employers->firstWhere(
                                'id',
                                (int) request('employer_id')
                            );

                        @endphp


                        @if($selectedEmployer)

                            <span class="badge bg-primary me-1 mb-1">
                                Employer: {{ $selectedEmployer->name }}
                            </span>

                        @endif

                    @endif

                </div>

            </div>

        </div>

    </div>

@endif


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
                Use Quick Search to search within the records returned
                by the filters above.
            </p>

        </div>


        <div class="table-responsive">

            <table id="membership-table"
                   class="table table-bordered table-striped table-hover align-middle">

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


                <tbody>

                    @forelse($members as $member)

                        <tr>


                            {{-- PENERP NUMBER --}}
                            <td>

                                <strong>
                                    {{ $member->member_number }}
                                </strong>

                            </td>


                            {{-- PENAD NUMBER --}}
                            <td>
                                {{ $member->penad_member_number ?? '-' }}
                            </td>


                            {{-- FUNDWORX NUMBER --}}
                            <td>
                                {{ $member->fundworx_member_number ?? '-' }}
                            </td>


                            {{-- MEMBER --}}
                            <td>

                                <strong>
                                    {{ $member->surname }},
                                    {{ $member->first_names }}
                                </strong>


                                @if($member->other_names)

                                    <br>

                                    <small>
                                        Other:
                                        {{ $member->other_names }}
                                    </small>

                                @endif


                                @if($member->maiden_name)

                                    <br>

                                    <small class="text-muted">
                                        Maiden:
                                        {{ $member->maiden_name }}
                                    </small>

                                @endif


                                @if($member->date_of_birth)

                                    <br>

                                    <small class="text-muted">
                                        DOB:
                                        {{ $member->date_of_birth->format('d M Y') }}
                                    </small>

                                @endif

                            </td>


                            {{-- NATIONAL ID --}}
                            <td>
                                {{ $member->national_id ?? '-' }}
                            </td>


                            {{-- EMPLOYER --}}
                            <td>

                                @if($member->currentEmployment?->employer)

                                    <strong>
                                        {{ $member->currentEmployment->employer->name }}
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        {{ $member->currentEmployment->employer->employer_number }}
                                    </small>

                                @else

                                    <span class="text-muted">
                                        -
                                    </span>

                                @endif

                            </td>


                            {{-- STAFF NUMBER --}}
                            <td>
                                {{ $member->currentEmployment?->staff_number ?? '-' }}
                            </td>


                            {{-- VOTE NUMBER --}}
                            <td>
                                {{ $member->currentEmployment?->vote_number ?? '-' }}
                            </td>


                            {{-- STATUS --}}
                            <td>

                                @php

                                    $statusClass = match(
                                        $member->membership_status
                                    ) {
                                        'active' => 'bg-success',
                                        'dormant' => 'bg-warning text-dark',
                                        'suspended' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };

                                @endphp


                                <span class="badge {{ $statusClass }}">

                                    {{ ucfirst($member->membership_status) }}

                                </span>

                            </td>


                            {{-- ACTIONS --}}
                            <td class="text-center member-action-buttons">

                                <a href="{{ route('pensions-administration.updates.members.show', $member) }}"
                                   class="btn btn-sm btn-outline-primary"
                                   title="View Member">

                                    <i class="mdi mdi-eye-outline"></i>

                                </a>


                                <a href="{{ route('pensions-administration.updates.members.edit', $member) }}"
                                   class="btn btn-sm btn-primary"
                                   title="Edit Member">

                                    <i class="mdi mdi-pencil-outline"></i>

                                </a>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td colspan="10"
                                class="text-center text-muted py-4">

                                No members found.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection


{{-- =========================================================
     DATATABLE LIBRARIES
========================================================= --}}

@push('scripts')

{{-- IMPORTANT:
     Do NOT load another jQuery here.
     layouts.app should already load jQuery for the PENERP theme.
--}}

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
    | Protect Against Duplicate Initialisation
    |--------------------------------------------------------------------------
    */

    if (
        $.fn.DataTable
        && $.fn.DataTable.isDataTable('#membership-table')
    ) {
        $('#membership-table')
            .DataTable()
            .destroy();
    }


    /*
    |--------------------------------------------------------------------------
    | Initialise Membership DataTable
    |--------------------------------------------------------------------------
    */

    $('#membership-table').DataTable({

        processing: true,

        responsive: false,

        autoWidth: false,

        pageLength: 25,


        lengthMenu: [

            [10, 25, 50, 100, -1],

            [10, 25, 50, 100, 'All']

        ],


        /*
        |--------------------------------------------------------------------------
        | Default Sort
        |--------------------------------------------------------------------------
        */

        order: [
            [3, 'asc']
        ],


        /*
        |--------------------------------------------------------------------------
        | DataTable Layout
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
        | Export
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
                    'PENERP Membership Register',

                exportOptions: {

                    columns: [
                        0, 1, 2, 3, 4,
                        5, 6, 7, 8
                    ],

                    stripHtml: true

                }
            },


            {
                extend: 'excelHtml5',

                text:
                    '<i class="mdi mdi-microsoft-excel me-1"></i> Excel',

                className:
                    'btn btn-success btn-sm',

                title:
                    'PENERP Membership Register',

                filename:
                    'PENERP_Membership_Register',

                exportOptions: {

                    columns: [
                        0, 1, 2, 3, 4,
                        5, 6, 7, 8
                    ],

                    stripHtml: true

                }
            },


            {
                extend: 'csvHtml5',

                text:
                    '<i class="mdi mdi-file-delimited-outline me-1"></i> CSV',

                className:
                    'btn btn-info btn-sm',

                title:
                    'PENERP Membership Register',

                filename:
                    'PENERP_Membership_Register',

                exportOptions: {

                    columns: [
                        0, 1, 2, 3, 4,
                        5, 6, 7, 8
                    ],

                    stripHtml: true

                }
            },


            {
                extend: 'pdfHtml5',

                text:
                    '<i class="mdi mdi-file-pdf-box me-1"></i> PDF',

                className:
                    'btn btn-danger btn-sm',

                title:
                    'PENERP Membership Register',

                filename:
                    'PENERP_Membership_Register',

                orientation:
                    'landscape',

                pageSize:
                    'A3',

                exportOptions: {

                    columns: [
                        0, 1, 2, 3, 4,
                        5, 6, 7, 8
                    ],

                    stripHtml: true

                },

                customize: function (doc) {

                    doc.defaultStyle.fontSize = 7;

                    doc.styles.tableHeader.fontSize = 8;

                    doc.styles.title = {

                        fontSize: 16,

                        bold: true,

                        alignment: 'center',

                        margin: [
                            0,
                            0,
                            0,
                            15
                        ]

                    };

                }
            },


            {
                extend: 'print',

                text:
                    '<i class="mdi mdi-printer-outline me-1"></i> Print',

                className:
                    'btn btn-dark btn-sm',

                title:
                    'PENERP Membership Register',

                exportOptions: {

                    columns: [
                        0, 1, 2, 3, 4,
                        5, 6, 7, 8
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
                targets: 9,

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
                'Quick Search:',

            searchPlaceholder:
                'Search filtered records...',

            lengthMenu:
                'Show _MENU_ members',

            info:
                'Showing _START_ to _END_ of _TOTAL_ members',

            infoEmpty:
                'No members found',

            infoFiltered:
                '(filtered from _MAX_ members)',

            zeroRecords:
                'No matching members found',

            processing:
                'Loading members...'

        }

    });

});
</script>

@endpush
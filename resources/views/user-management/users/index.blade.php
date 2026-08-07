@extends('layouts.app')

@section('title', 'Users')

@section('page-heading', 'User Management')


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
    
    <link
        href="{{ asset('layouts/assets/libs/select2/css/select2.min.css') }}"
        rel="stylesheet"
        type="text/css"
    >

    <style>

        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        .user-name-cell {
            min-width: 190px;
        }

        .user-table-actions {
            white-space: nowrap;
        }

        .dt-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }

        body.lapf-dark-mode .dataTables_wrapper .dataTables_filter input,
        body.lapf-dark-mode .dataTables_wrapper .dataTables_length select {
            background-color: #20242c;
            border-color: #3b424f;
            color: #ffffff;
        }

        body.lapf-dark-mode .dataTables_wrapper .dataTables_info,
        body.lapf-dark-mode .dataTables_wrapper .dataTables_length,
        body.lapf-dark-mode .dataTables_wrapper .dataTables_filter {
            color: #c2cad3;
        }

        body.lapf-dark-mode .page-item.disabled .page-link {
            background-color: #252a34;
            border-color: #343a46;
            color: #6f7b89;
        }
         .select2-container {
            width: 100% !important;
        }

        .select2-container .select2-selection--single {
            height: 38px;
        }

        .select2-container--default
        .select2-selection--single
        .select2-selection__rendered {
            line-height: 36px;
        }

        .select2-container--default
        .select2-selection--single
        .select2-selection__arrow {
            height: 36px;
        }

        body.lapf-dark-mode
        .select2-container--default
        .select2-selection--single,
        body.lapf-dark-mode
        .select2-container--default
        .select2-selection--multiple {
            background-color: #20242c;
            border-color: #3b424f;
            color: #ffffff;
        }

        body.lapf-dark-mode
        .select2-container--default
        .select2-selection--single
        .select2-selection__rendered {
            color: #ffffff;
        }

        body.lapf-dark-mode
        .select2-dropdown {
            background-color: #252a34;
            border-color: #3b424f;
            color: #ffffff;
        }

        body.lapf-dark-mode
        .select2-container--default
        .select2-results__option--highlighted[aria-selected] {
            background-color: #303641;
        }


    </style>

@endpush


@section('page-actions')

    @can('user-management.users.create')

        <a
            href="{{ route('user-management.users.create') }}"
            class="btn btn-success"
        >
            <i class="mdi mdi-account-plus-outline me-1"></i>
            Add User
        </a>

    @endcan

@endsection


@section('content')


{{-- =========================================================
     FILTERS
========================================================= --}}

<div class="row">

    <div class="col-12">

        <div class="card">

            <div class="card-body">

                <h4 class="header-title">
                    Search & Filter Users
                </h4>

                <p class="card-title-desc">
                    Search employees and filter the user register by
                    department, section or account status.
                </p>


                <form
                    method="GET"
                    action="{{ route('user-management.users.index') }}"
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
                                    placeholder="Employee, name, username..."
                                >

                            </div>

                        </div>


                        {{-- Organisation Unit --}}
                        <div class="col-xl-4 col-lg-4 col-md-6">

                            <div class="mb-3">

                                <label
                                    for="organisation_unit_id"
                                    class="form-label"
                                >
                                    Department / Section
                                </label>

                                <select
                                    name="organisation_unit_id"
                                    id="organisation_unit_id"
                                    class="form-control select2"
                                >

                                    <option value="">
                                        All Departments / Sections
                                    </option>

                                    @foreach($organisationUnits as $organisationUnit)

                                        <option
                                            value="{{ $organisationUnit->id }}"
                                            @selected(
                                                request('organisation_unit_id')
                                                == $organisationUnit->id
                                            )
                                        >
                                            {{ $organisationUnit->name }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>

                        </div>


                        {{-- Account Status --}}
                        <div class="col-xl-4 col-lg-4 col-md-6">

                            <div class="mb-3">

                                <label
                                    for="status"
                                    class="form-label"
                                >
                                    Account Status
                                </label>

                                <select
                                    name="status"
                                    id="status"
                                    class="form-select"
                                >

                                    <option value="">
                                        All Statuses
                                    </option>

                                    @foreach([
                                        'active',
                                        'pending',
                                        'locked',
                                        'suspended',
                                        'disabled',
                                    ] as $status)

                                        <option
                                            value="{{ $status }}"
                                            @selected(
                                                request('status') === $status
                                            )
                                        >
                                            {{ ucfirst($status) }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>

                        </div>

                    </div>


                    <div class="d-flex flex-wrap gap-2">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="mdi mdi-magnify me-1"></i>
                            Search
                        </button>


                        <a
                            href="{{ route('user-management.users.index') }}"
                            class="btn btn-light"
                        >
                            <i class="mdi mdi-refresh me-1"></i>
                            Clear Filters
                        </a>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>



{{-- =========================================================
     USER REGISTER
========================================================= --}}

<div class="row">

    <div class="col-12">

        <div class="card">

            <div class="card-body">

                <div
                    class="d-flex flex-wrap align-items-center justify-content-between mb-3"
                >

                    <div>

                        <h4 class="header-title mb-1">
                            User Register
                        </h4>

                        <p class="card-title-desc mb-0">
                            Registered users of the LAPF Pension Fund System.
                        </p>

                    </div>


                    <div class="text-muted font-size-13">

                        @if(method_exists($users, 'total'))

                            Total:
                            <strong>
                                {{ number_format($users->total()) }}
                            </strong>

                        @else

                            Total:
                            <strong>
                                {{ number_format($users->count()) }}
                            </strong>

                        @endif

                    </div>

                </div>


                <div class="table-responsive">

                    <table
                        id="users-datatable"
                        class="table table-striped table-bordered dt-responsive nowrap"
                        style="
                            border-collapse: collapse;
                            border-spacing: 0;
                            width: 100%;
                        "
                    >

                        <thead>

                            <tr>

                                <th>
                                    Employee No.
                                </th>

                                <th>
                                    Employee
                                </th>

                                <th>
                                    Job Title
                                </th>

                                <th>
                                    Department / Section
                                </th>

                                <th>
                                    Role
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Last Login
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($users as $user)

                                @php

                                    $statusClass = match($user->account_status) {
                                        'active' => 'success',
                                        'pending' => 'warning',
                                        'locked' => 'danger',
                                        'suspended' => 'warning',
                                        'disabled' => 'secondary',
                                        default => 'secondary',
                                    };

                                    $initials =
                                        strtoupper(
                                            substr(
                                                $user->first_name ?? '',
                                                0,
                                                1
                                            )
                                            .
                                            substr(
                                                $user->surname ?? '',
                                                0,
                                                1
                                            )
                                        );

                                @endphp


                                <tr>


                                    {{-- Employee Number --}}
                                    <td>

                                        <strong>
                                            {{ $user->employee_number }}
                                        </strong>

                                    </td>


                                    {{-- Employee --}}
                                    <td class="user-name-cell">

                                        <div class="d-flex align-items-center">

                                            <div
                                                class="user-avatar bg-soft-primary text-primary me-3"
                                            >
                                                {{ $initials ?: 'U' }}
                                            </div>


                                            <div>

                                                <h6 class="mb-1">

                                                    {{ $user->surname }},
                                                    {{ $user->first_name }}

                                                </h6>


                                                <span class="text-muted font-size-13">

                                                    <i class="mdi mdi-account-outline me-1"></i>

                                                    {{ $user->username }}

                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    {{-- Job Title --}}
                                    <td>

                                        {{ $user->jobTitle?->name ?? '-' }}

                                    </td>


                                    {{-- Organisation Unit --}}
                                    <td>

                                        {{ $user->organisationUnit?->name ?? '-' }}

                                    </td>


                                    {{-- Roles --}}
                                    <td>

                                        @forelse($user->roles as $role)

                                            <span class="badge bg-soft-primary text-primary me-1 mb-1">

                                                {{ $role->display_name ?: $role->name }}

                                            </span>

                                        @empty

                                            <span class="text-muted">
                                                No role
                                            </span>

                                        @endforelse

                                    </td>


                                    {{-- Status --}}
                                    <td>

                                        <span
                                            class="badge bg-{{ $statusClass }}"
                                        >

                                            {{ ucfirst($user->account_status) }}

                                        </span>

                                    </td>


                                    {{-- Last Login --}}
                                    <td
                                        data-order="{{ optional($user->last_login_at)->timestamp ?? 0 }}"
                                    >

                                        @if($user->last_login_at)

                                            {{ $user->last_login_at->format('d M Y') }}

                                            <br>

                                            <small class="text-muted">
                                                {{ $user->last_login_at->format('H:i') }}
                                            </small>

                                        @else

                                            <span class="text-muted">
                                                Never
                                            </span>

                                        @endif

                                    </td>


                                    {{-- Actions --}}
                                    <td class="user-table-actions">

                                        <a
                                            href="{{ route(
                                                'user-management.users.show',
                                                $user
                                            ) }}"
                                            class="btn btn-sm btn-info"
                                            title="View User"
                                        >
                                            <i class="mdi mdi-eye-outline"></i>
                                        </a>


                                        @can('user-management.users.update')

                                            <a
                                                href="{{ route(
                                                    'user-management.users.edit',
                                                    $user
                                                ) }}"
                                                class="btn btn-sm btn-primary"
                                                title="Edit User"
                                            >
                                                <i class="mdi mdi-pencil-outline"></i>
                                            </a>

                                        @endcan

                                    </td>


                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="8"
                                        class="text-center py-4"
                                    >

                                        <i
                                            class="mdi mdi-account-search-outline font-size-24 text-muted"
                                        ></i>

                                        <p class="text-muted mb-0 mt-2">
                                            No users found.
                                        </p>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                {{-- Keep Laravel pagination because controller currently paginates --}}
                @if(
                    method_exists($users, 'links')
                    && $users->hasPages()
                )

                    <div class="mt-4">

                        {{ $users->withQueryString()->links() }}

                    </div>

                @endif

            </div>

        </div>

    </div>

</div>

@endsection


@push('scripts-before-app')

    {{-- Required DataTables --}}
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


    {{-- Export Buttons --}}
    <script
        src="{{ asset('layouts/assets/libs/datatables.net-buttons/js/buttons.html5.min.js') }}">
    </script>

    <script
        src="{{ asset('layouts/assets/libs/datatables.net-buttons/js/buttons.print.min.js') }}">
    </script>

    <script
        src="{{ asset('layouts/assets/libs/datatables.net-buttons/js/buttons.colVis.min.js') }}">
    </script>


    {{-- Responsive --}}
    <script
        src="{{ asset('layouts/assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}">
    </script>

    <script
        src="{{ asset('layouts/assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}">
    </script>

@endpush


@push('scripts')

<script>

$(document).ready(function () {

    const table = $('#users-datatable').DataTable({

        responsive: true,

        ordering: true,

        searching: true,

        paging: false,

        info: false,

        autoWidth: false,

        dom:
            "<'row mb-3'<'col-md-8'B><'col-md-4'f>>" +
            "<'row'<'col-sm-12'tr>>",

        buttons: [

            {
                extend: 'copyHtml5',
                text: '<i class="mdi mdi-content-copy me-1"></i> Copy',
                className: 'btn btn-light',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            },

            {
                extend: 'excelHtml5',
                text: '<i class="mdi mdi-file-excel-outline me-1"></i> Excel',
                className: 'btn btn-success',
                title: 'LAPF User Register',
                filename: 'LAPF_User_Register',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            },

            {
                extend: 'pdfHtml5',
                text: '<i class="mdi mdi-file-pdf-outline me-1"></i> PDF',
                className: 'btn btn-danger',
                title: 'LAPF User Register',
                filename: 'LAPF_User_Register',
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                },
                customize: function (doc) {

                    doc.styles.title = {
                        fontSize: 14,
                        bold: true,
                        alignment: 'center'
                    };

                    doc.defaultStyle.fontSize = 8;

                    if (
                        doc.content.length > 1
                        && doc.content[1].table
                    ) {
                        doc.content[1].table.widths = [
                            '10%',
                            '18%',
                            '16%',
                            '18%',
                            '14%',
                            '10%',
                            '14%'
                        ];
                    }

                }
            },

            {
                extend: 'print',
                text: '<i class="mdi mdi-printer-outline me-1"></i> Print',
                className: 'btn btn-primary',
                title: 'LAPF User Register',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            },

            {
                extend: 'colvis',
                text: '<i class="mdi mdi-view-column-outline me-1"></i> Columns',
                className: 'btn btn-secondary'
            }

        ],

        columnDefs: [
            {
                targets: 7,
                searchable: false,
                orderable: false
            }
        ]

    });


    table
        .buttons()
        .container()
        .find('.btn')
        .removeClass('btn-secondary');


});

</script>

@endpush
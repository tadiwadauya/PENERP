@extends('layouts.app')

@section('title', 'Review Membership Import')

@section('page-heading', 'Review Membership Import')


@push('styles')

<link rel="stylesheet" href="https://cdn.datatables.net/2.3.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.5/css/buttons.bootstrap5.min.css">

<style>
    .import-stat-card {
        border: 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .04);
        height: 100%;
    }

    .import-stat-card .card-body {
        min-height: 105px;
    }

    .exception-message {
        padding: 6px 8px;
        margin-bottom: 5px;
        border-radius: 4px;
        white-space: normal;
        line-height: 1.4;
    }

    .exception-error {
        background: rgba(220, 53, 69, .10);
        color: #dc3545;
    }

    .exception-warning {
        background: rgba(255, 193, 7, .14);
        color: #997404;
    }

    .exception-duplicate {
        background: rgba(220, 53, 69, .08);
        color: #b02a37;
    }

    .exception-possible {
        background: rgba(13, 202, 240, .10);
        color: #087990;
    }

    .exception-table tbody tr.exception-row-error > td {
        background-color: rgba(220, 53, 69, .035);
    }

    .exception-table tbody tr.exception-row-warning > td {
        background-color: rgba(255, 193, 7, .035);
    }

    .dt-buttons .btn {
        margin-right: 5px;
        margin-bottom: 5px;
    }

    .review-tabs .nav-link {
        font-weight: 600;
    }

    .review-tabs .nav-link.active {
        color: #0d6efd;
    }

    .exception-help {
        border-left: 4px solid #dc3545;
    }

    .duplicate-help {
        border-left: 4px solid #0dcaf0;
    }

    .import-ready-help {
        border-left: 4px solid #198754;
    }

    table.dataTable td {
        vertical-align: top;
    }

    .dataTables_wrapper .dataTables_processing {
        z-index: 100;
    }

    .stat-label {
        font-size: 13px;
        min-height: 36px;
    }

    .review-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .review-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 13px;
    }
</style>

@endpush


@section('content')

@include('pensions-administration.partials.navigation')


@php

    /*
    |--------------------------------------------------------------------------
    | Exact Duplicates
    |--------------------------------------------------------------------------
    */

    $exactDuplicateCount = $batch->rows()
        ->where('duplicate_status', 'exact')
        ->count();


    /*
    |--------------------------------------------------------------------------
    | Possible Duplicates
    |--------------------------------------------------------------------------
    */

    $possibleDuplicateCount = $batch->rows()
        ->whereIn('duplicate_status', [
            'possible',
            'probable',
        ])
        ->count();


    /*
    |--------------------------------------------------------------------------
    | Unique Rows Requiring Review
    |--------------------------------------------------------------------------
    |
    | A row can contain:
    |
    | error + duplicate
    | warning + duplicate
    |
    | therefore count the row only once.
    |
    */

    $reviewRequiredCount = $batch->rows()
        ->where(function ($query) {
            $query
                ->where('validation_status', 'error')
                ->orWhere('validation_status', 'warning')
                ->orWhereIn('duplicate_status', [
                    'exact',
                    'possible',
                    'probable',
                ]);
        })
        ->count();


    /*
    |--------------------------------------------------------------------------
    | Approved Rows Still Waiting for Final Import
    |--------------------------------------------------------------------------
    */

    $approvedForImportCount = $batch->rows()
        ->whereIn('review_decision', [
            'create',
            'update',
            'use_existing',
            'ignore_warning',
        ])
        ->where('validation_status', '<>', 'error')
        ->whereNull('imported_at')
        ->count();

@endphp


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
     BATCH HEADER
========================================================= --}}

<div class="card">

    <div class="card-body">

        <div class="d-flex flex-wrap justify-content-between align-items-center">

            <div>

                <h4 class="header-title mb-1">
                    {{ $batch->original_filename }}
                </h4>

                <p class="text-muted mb-0">
                    Batch: {{ $batch->import_uuid }}
                </p>

            </div>


            <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">

                <a href="{{ route('pensions-administration.updates.imports.show', $batch) }}"
                   class="btn btn-light">

                    <i class="mdi mdi-arrow-left me-1"></i>
                    Batch Summary

                </a>


                @if($reviewRequiredCount > 0)

                    <a href="{{ route('pensions-administration.updates.imports.review.exceptions.download', $batch) }}"
                       class="btn btn-danger">

                        <i class="mdi mdi-microsoft-excel me-1"></i>
                        Download Exception Report

                    </a>

                @endif


                @if($batch->status === 'awaiting_review')

                    <form method="POST"
                          action="{{ route('pensions-administration.updates.imports.destroy', $batch) }}"
                          onsubmit="return confirm('Cancel this batch and upload a corrected Excel file?');">

                        @csrf
                        @method('DELETE')

                        <button type="submit"
                                class="btn btn-outline-danger">

                            <i class="mdi mdi-file-refresh-outline me-1"></i>
                            Cancel & Re-upload

                        </button>

                    </form>

                @endif

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     SUMMARY
========================================================= --}}

<div class="row g-3 mb-3">


    {{-- TOTAL --}}
    <div class="col-xl-2 col-lg-3 col-md-4 col-6">

        <div class="card import-stat-card">

            <div class="card-body">

                <p class="text-muted mb-1 stat-label">
                    Total Records
                </p>

                <h4>
                    {{ number_format($counts['total']) }}
                </h4>

            </div>

        </div>

    </div>


    {{-- VALID --}}
    <div class="col-xl-2 col-lg-3 col-md-4 col-6">

        <div class="card import-stat-card">

            <div class="card-body">

                <p class="text-muted mb-1 stat-label">
                    Valid
                </p>

                <h4 class="text-success">
                    {{ number_format($counts['valid']) }}
                </h4>

            </div>

        </div>

    </div>


    {{-- WARNINGS --}}
    <div class="col-xl-2 col-lg-3 col-md-4 col-6">

        <div class="card import-stat-card">

            <div class="card-body">

                <p class="text-muted mb-1 stat-label">
                    Warnings
                </p>

                <h4 class="text-warning">
                    {{ number_format($counts['warnings']) }}
                </h4>

            </div>

        </div>

    </div>


    {{-- ERRORS --}}
    <div class="col-xl-2 col-lg-3 col-md-4 col-6">

        <div class="card import-stat-card">

            <div class="card-body">

                <p class="text-muted mb-1 stat-label">
                    Errors
                </p>

                <h4 class="text-danger">
                    {{ number_format($counts['errors']) }}
                </h4>

            </div>

        </div>

    </div>


    {{-- EXACT DUPLICATES --}}
    <div class="col-xl-2 col-lg-3 col-md-4 col-6">

        <div class="card import-stat-card">

            <div class="card-body">

                <p class="text-muted mb-1 stat-label">
                    Exact Duplicates
                </p>

                <h4 class="text-danger">
                    {{ number_format($exactDuplicateCount) }}
                </h4>

            </div>

        </div>

    </div>


    {{-- POSSIBLE DUPLICATES --}}
    <div class="col-xl-2 col-lg-3 col-md-4 col-6">

        <div class="card import-stat-card">

            <div class="card-body">

                <p class="text-muted mb-1 stat-label">
                    Possible Duplicates
                </p>

                <h4 class="text-info">
                    {{ number_format($possibleDuplicateCount) }}
                </h4>

            </div>

        </div>

    </div>


    {{-- APPROVED --}}
    <div class="col-xl-2 col-lg-3 col-md-4 col-6">

        <div class="card import-stat-card">

            <div class="card-body">

                <p class="text-muted mb-1 stat-label">
                    Approved
                </p>

                <h4 class="text-success">
                    {{ number_format($approvedForImportCount) }}
                </h4>

            </div>

        </div>

    </div>


    {{-- PENDING --}}
    <div class="col-xl-2 col-lg-3 col-md-4 col-6">

        <div class="card import-stat-card">

            <div class="card-body">

                <p class="text-muted mb-1 stat-label">
                    Pending Review
                </p>

                <h4 class="text-warning">
                    {{ number_format($counts['pending']) }}
                </h4>

            </div>

        </div>

    </div>


    {{-- REMOVED --}}
    <div class="col-xl-2 col-lg-3 col-md-4 col-6">

        <div class="card import-stat-card">

            <div class="card-body">

                <p class="text-muted mb-1 stat-label">
                    Removed / Rejected
                </p>

                <h4 class="text-secondary">
                    {{ number_format($counts['rejected']) }}
                </h4>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     REVIEW LEGEND
========================================================= --}}

<div class="card">

    <div class="card-body py-3">

        <div class="review-legend">

            <div class="review-legend-item">

                <span class="badge bg-success">
                    Valid
                </span>

                <span>
                    Record passed validation.
                </span>

            </div>


            <div class="review-legend-item">

                <span class="badge bg-danger">
                    Exact Duplicate
                </span>

                <span>
                    Confirmed duplicate requiring a review decision.
                </span>

            </div>


            <div class="review-legend-item">

                <span class="badge bg-info">
                    Possible Duplicate
                </span>

                <span>
                    Requires review but is not a confirmed duplicate.
                </span>

            </div>


            <div class="review-legend-item">

                <span class="badge bg-warning text-dark">
                    Warning
                </span>

                <span>
                    Record requires review.
                </span>

            </div>


            <div class="review-legend-item">

                <span class="badge bg-danger">
                    Error
                </span>

                <span>
                    Must be corrected or removed.
                </span>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     APPROVED RECORDS READY FOR IMPORT
========================================================= --}}

@if(
    $approvedForImportCount > 0
    && $batch->status === 'awaiting_review'
)

    <div class="alert alert-light import-ready-help">

        <div class="d-flex align-items-start">

            <i class="mdi mdi-database-check-outline font-size-22 text-success me-2"></i>

            <div>

                <strong>
                    Approved Records Ready for Final Import
                </strong>

                <p class="mb-0 mt-1">

                    {{ number_format($approvedForImportCount) }}

                    approved member record(s) are ready to be committed
                    to the live PENERP membership register.

                </p>

            </div>

        </div>

    </div>

@endif


{{-- =========================================================
     EXCEPTION GUIDANCE
========================================================= --}}

@if($reviewRequiredCount > 0)

    <div class="alert alert-light exception-help">

        <div class="d-flex">

            <i class="mdi mdi-alert-circle-outline font-size-22 text-danger me-2"></i>

            <div>

                <strong>
                    Exception Review Required
                </strong>

                <p class="mb-1 mt-1">

                    {{ number_format($reviewRequiredCount) }}

                    record(s) require attention.

                </p>

                <p class="mb-0">

                    Errors must be corrected or removed.

                    Exact duplicates require a duplicate decision.

                    Possible duplicates are shown separately and are not
                    treated as confirmed duplicates.

                </p>

            </div>

        </div>

    </div>

@endif


@if($possibleDuplicateCount > 0)

    <div class="alert alert-light duplicate-help">

        <div class="d-flex">

            <i class="mdi mdi-account-search-outline font-size-22 text-info me-2"></i>

            <div>

                <strong>
                    Possible Duplicate Review
                </strong>

                <p class="mb-0 mt-1">

                    {{ number_format($possibleDuplicateCount) }}

                    record(s) are marked as possible duplicates.

                    They must be reviewed before any duplicate decision
                    is made.

                </p>

            </div>

        </div>

    </div>

@endif


{{-- =========================================================
     REVIEW ACTIONS
========================================================= --}}

<div class="card">

    <div class="card-body">

        <div class="d-flex flex-wrap justify-content-between align-items-center">

            <div>

                <h4 class="header-title mb-1">
                    Review Actions
                </h4>

                <p class="text-muted mb-0">
                    Approve clean records, remove unresolved errors
                    and commit approved records to the live membership register.
                </p>

            </div>


            <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">


                {{-- =================================================
                     APPROVE ALL VALID ROWS
                ================================================== --}}

                @if(
                    $batch->status === 'awaiting_review'
                    && $counts['valid'] > 0
                )

                    <form method="POST"
                          action="{{ route('pensions-administration.updates.imports.approve-valid', $batch) }}">

                        @csrf

                        <button type="submit"
                                class="btn btn-success">

                            <i class="mdi mdi-check-all me-1"></i>

                            Approve All Valid Rows

                        </button>

                    </form>

                @endif


                {{-- =================================================
                     REMOVE ERROR ROWS
                ================================================== --}}

                @if(
                    $batch->status === 'awaiting_review'
                    && $counts['errors'] > 0
                )

                    <form method="POST"
                          action="{{ route('pensions-administration.updates.imports.reject-errors', $batch) }}"
                          onsubmit="return confirm('Remove all unresolved error rows from this import?');">

                        @csrf

                        <button type="submit"
                                class="btn btn-outline-danger">

                            <i class="mdi mdi-delete-sweep-outline me-1"></i>

                            Remove All Error Rows

                        </button>

                    </form>

                @endif


                {{-- =================================================
                     FINAL IMPORT
                ================================================== --}}

                @if(
                    $approvedForImportCount > 0
                    && $batch->status === 'awaiting_review'
                )

                    <form method="POST"
                          action="{{ route('pensions-administration.updates.imports.import', $batch) }}"
                          onsubmit="return confirm('Import {{ number_format($approvedForImportCount) }} approved membership records into the live PENERP membership register? This will create or update live member records.');">

                        @csrf

                        <button type="submit"
                                class="btn btn-primary">

                            <i class="mdi mdi-database-import-outline me-1"></i>

                            Import
                            {{ number_format($approvedForImportCount) }}
                            Approved Members

                        </button>

                    </form>

                @endif

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     TABS
========================================================= --}}

<div class="card">

    <div class="card-body">

        <ul class="nav nav-tabs review-tabs mb-4"
            role="tablist">


            {{-- VALIDATED DATA --}}
            <li class="nav-item"
                role="presentation">

                <button class="nav-link active"
                        id="validated-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#validated-pane"
                        type="button"
                        role="tab">

                    <i class="mdi mdi-format-list-checks me-1"></i>

                    Validated Data

                    <span class="badge bg-secondary ms-1">
                        {{ number_format($counts['total']) }}
                    </span>

                </button>

            </li>


            {{-- EXCEPTIONS --}}
            <li class="nav-item"
                role="presentation">

                <button class="nav-link"
                        id="exceptions-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#exceptions-pane"
                        type="button"
                        role="tab">

                    <i class="mdi mdi-alert-outline me-1"></i>

                    Exceptions / Data to Review

                    @if($reviewRequiredCount > 0)

                        <span class="badge bg-danger ms-1">
                            {{ number_format($reviewRequiredCount) }}
                        </span>

                    @endif

                </button>

            </li>

        </ul>


        <div class="tab-content">


            {{-- =====================================================
                 VALIDATED DATA
            ====================================================== --}}

            <div class="tab-pane fade show active"
                 id="validated-pane"
                 role="tabpanel">

                <div class="table-responsive">

                    <table id="validated-members-table"
                           class="table table-bordered table-striped table-hover align-middle w-100">

                        <thead>

                            <tr>

                                <th>
                                    Excel Row
                                </th>

                                <th>
                                    Member
                                </th>

                                <th>
                                    National ID
                                </th>

                                <th>
                                    References
                                </th>

                                <th>
                                    Employer
                                </th>

                                <th>
                                    Validation
                                </th>

                                <th>
                                    Duplicate Review
                                </th>

                                <th>
                                    Decision
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>

                    </table>

                </div>

            </div>


            {{-- =====================================================
                 EXCEPTION REPORT
            ====================================================== --}}

            <div class="tab-pane fade"
                 id="exceptions-pane"
                 role="tabpanel">

                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">

                    <div>

                        <h5 class="mb-1">
                            Data Requiring Review
                        </h5>

                        <p class="text-muted mb-0">

                            Errors, warnings, exact duplicates
                            and possible duplicates are shown here.

                        </p>

                    </div>


                    @if($reviewRequiredCount > 0)

                        <a href="{{ route('pensions-administration.updates.imports.review.exceptions.download', $batch) }}"
                           class="btn btn-danger mt-2 mt-md-0">

                            <i class="mdi mdi-file-excel-outline me-1"></i>

                            Download Full Exception Report

                        </a>

                    @endif

                </div>


                <div class="table-responsive">

                    <table id="member-exceptions-table"
                           class="table table-bordered exception-table align-middle w-100">

                        <thead>

                            <tr>

                                <th>
                                    Excel Row
                                </th>

                                <th>
                                    Member
                                </th>

                                <th>
                                    National ID
                                </th>

                                <th>
                                    Employer
                                </th>

                                <th>
                                    Fields to Correct / Review
                                </th>

                                <th>
                                    Validation
                                </th>

                                <th>
                                    Duplicate Review
                                </th>

                                <th style="min-width:420px;">
                                    Exception Details
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection


{{-- =========================================================
     DATATABLE SCRIPTS
========================================================= --}}

@push('scripts-before-app')

<script src="https://cdn.datatables.net/2.3.5/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.3.5/js/dataTables.bootstrap5.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.12/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.12/vfs_fonts.js"></script>

<script src="https://cdn.datatables.net/buttons/3.2.5/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.print.min.js"></script>

@endpush


@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Validated Membership Data
    |--------------------------------------------------------------------------
    */

    const validatedTable = new DataTable(
        '#validated-members-table',
        {
            processing: true,
            serverSide: true,
            responsive: false,

            pageLength: 25,

            lengthMenu: [
                [25, 50, 100, 250],
                [25, 50, 100, 250]
            ],

            ajax: {
                url: @json(
                    route(
                        'pensions-administration.updates.imports.review.data',
                        $batch
                    )
                )
            },

            columns: [

                {
                    data: 'row_number',
                    name: 'row_number'
                },

                {
                    data: 'member',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'national_id',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'references',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'employer',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'validation',
                    orderable: true,
                    searchable: false
                },

                {
                    data: 'duplicate',
                    orderable: true,
                    searchable: false
                },

                {
                    data: 'decision',
                    orderable: true,
                    searchable: false
                },

                {
                    data: 'actions',
                    orderable: false,
                    searchable: false
                }

            ],

            layout: {

                topStart: {

                    buttons: [

                        {
                            extend: 'copyHtml5',

                            text:
                                '<i class="mdi mdi-content-copy me-1"></i> Copy',

                            className:
                                'btn btn-light',

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
                                ]
                            }
                        },


                        {
                            extend: 'csvHtml5',

                            text:
                                '<i class="mdi mdi-file-delimited-outline me-1"></i> CSV',

                            className:
                                'btn btn-light',

                            title:
                                'PENERP Validated Membership Data',

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
                                ]
                            }
                        },


                        {
                            extend: 'excelHtml5',

                            text:
                                '<i class="mdi mdi-microsoft-excel me-1"></i> Excel',

                            className:
                                'btn btn-success',

                            title:
                                'PENERP Validated Membership Data',

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
                                ]
                            }
                        },


                        {
                            extend: 'pdfHtml5',

                            text:
                                '<i class="mdi mdi-file-pdf-box me-1"></i> PDF',

                            className:
                                'btn btn-danger',

                            title:
                                'PENERP Validated Membership Data',

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
                                ]
                            }
                        },


                        {
                            extend: 'print',

                            text:
                                '<i class="mdi mdi-printer-outline me-1"></i> Print',

                            className:
                                'btn btn-light',

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
                                ]
                            }
                        }

                    ]

                }

            },

            order: [
                [0, 'asc']
            ]
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Exception Report
    |--------------------------------------------------------------------------
    */

    const exceptionTable = new DataTable(
        '#member-exceptions-table',
        {
            processing: true,
            serverSide: true,
            responsive: false,

            pageLength: 25,

            lengthMenu: [
                [25, 50, 100, 250],
                [25, 50, 100, 250]
            ],

            ajax: {
                url: @json(
                    route(
                        'pensions-administration.updates.imports.review.exceptions',
                        $batch
                    )
                )
            },

            columns: [

                {
                    data: 'row_number'
                },

                {
                    data: 'member',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'national_id',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'employer',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'exception_fields',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'validation'
                },

                {
                    data: 'duplicate'
                },

                {
                    data: 'details',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'actions',
                    orderable: false,
                    searchable: false
                }

            ],

            layout: {

                topStart: {

                    buttons: [

                        {
                            extend: 'copyHtml5',

                            text:
                                '<i class="mdi mdi-content-copy me-1"></i> Copy',

                            className:
                                'btn btn-light',

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
                                ]
                            }
                        },


                        {
                            extend: 'csvHtml5',

                            text:
                                '<i class="mdi mdi-file-delimited-outline me-1"></i> CSV',

                            className:
                                'btn btn-light',

                            title:
                                'PENERP Membership Exceptions',

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
                                ]
                            }
                        },


                        {
                            extend: 'excelHtml5',

                            text:
                                '<i class="mdi mdi-microsoft-excel me-1"></i> Current View Excel',

                            className:
                                'btn btn-success',

                            title:
                                'PENERP Membership Exceptions',

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
                                ]
                            }
                        },


                        {
                            extend: 'pdfHtml5',

                            text:
                                '<i class="mdi mdi-file-pdf-box me-1"></i> PDF',

                            className:
                                'btn btn-danger',

                            title:
                                'PENERP Membership Exceptions',

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
                                    7
                                ]
                            }
                        },


                        {
                            extend: 'print',

                            text:
                                '<i class="mdi mdi-printer-outline me-1"></i> Print',

                            className:
                                'btn btn-light',

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
                                ]
                            }
                        }

                    ]

                }

            },

            createdRow: function (row, data) {

                if (
                    data.validation
                    && data.validation.includes('Error')
                ) {
                    row.classList.add(
                        'exception-row-error'
                    );

                } else {
                    row.classList.add(
                        'exception-row-warning'
                    );
                }

            },

            order: [
                [0, 'asc']
            ]
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Fix DataTable Width When Exception Tab Opens
    |--------------------------------------------------------------------------
    */

    const exceptionsTab =
        document.getElementById(
            'exceptions-tab'
        );


    if (exceptionsTab) {

        exceptionsTab.addEventListener(
            'shown.bs.tab',
            function () {

                exceptionTable
                    .columns
                    .adjust();

            }
        );

    }

});
</script>

@endpush
@extends('layouts.app')

@section('title', 'Review Historical Contributions')

@section('page-heading', 'Review Historical Contributions')


@section('page-actions')

<a href="{{ route('pensions-administration.historical-contributions.imports.show', $batch) }}"
   class="btn btn-light">

    <i class="mdi mdi-arrow-left me-1"></i>

    Back to Import

</a>

@endsection


@push('styles')

<link rel="stylesheet"
      href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<link rel="stylesheet"
      href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">


<style>

    #historical-review-table {
        width: 100% !important;
    }

    #historical-review-table th {
        white-space: nowrap;
    }

    #historical-review-table td {
        vertical-align: middle;
    }

    #historical-review-table_wrapper .dataTables_filter {
        text-align: right;
    }

    #historical-review-table_wrapper .dataTables_filter input {
        margin-left: 8px;
        min-width: 220px;
    }

    #historical-review-table_wrapper .dt-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .review-filter-card {
        border-left: 4px solid #0d6efd;
    }

    .review-stat-card h3 {
        margin-bottom: 0;
    }

    .review-message-cell {
        min-width: 320px;
        max-width: 500px;
        white-space: normal;
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


@if(session('warning'))

<div class="alert alert-warning">

    <i class="mdi mdi-alert-outline me-1"></i>

    {{ session('warning') }}

</div>

@endif


@if(session('error'))

<div class="alert alert-danger">

    <i class="mdi mdi-alert-circle-outline me-1"></i>

    {{ session('error') }}

</div>

@endif


{{-- =========================================================
     BATCH DETAILS
========================================================= --}}

<div class="card">

    <div class="card-body">

        <div class="row align-items-center">

            <div class="col-lg-8">

                <h4 class="header-title mb-1">
                    {{ $batch->original_filename }}
                </h4>

                <p class="text-muted mb-0">

                    Batch #{{ $batch->id }}

                    &nbsp; | &nbsp;

                    {{ $batch->import_uuid }}

                </p>

            </div>


            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">

                <span class="badge bg-warning text-dark font-size-14">

                    {{ ucwords(str_replace('_', ' ', $batch->status)) }}

                </span>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     REVIEW SUMMARY
========================================================= --}}

<div class="row g-3">

    <div class="col-xl-2 col-md-4 col-6">

        <div class="card review-stat-card h-100">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Total
                </p>

                <h3 id="count-total">
                    {{ number_format($counts['total']) }}
                </h3>

            </div>

        </div>

    </div>


    <div class="col-xl-2 col-md-4 col-6">

        <div class="card review-stat-card h-100">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Valid
                </p>

                <h3 id="count-valid"
                    class="text-success">

                    {{ number_format($counts['valid']) }}

                </h3>

            </div>

        </div>

    </div>


    <div class="col-xl-2 col-md-4 col-6">

        <div class="card review-stat-card h-100">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Warnings
                </p>

                <h3 id="count-warning"
                    class="text-warning">

                    {{ number_format($counts['warning']) }}

                </h3>

            </div>

        </div>

    </div>


    <div class="col-xl-2 col-md-4 col-6">

        <div class="card review-stat-card h-100">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Errors
                </p>

                <h3 id="count-error"
                    class="text-danger">

                    {{ number_format($counts['error']) }}

                </h3>

            </div>

        </div>

    </div>


    <div class="col-xl-2 col-md-4 col-6">

        <div class="card review-stat-card h-100">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Duplicates
                </p>

                <h3 id="count-duplicates"
                    class="text-danger">

                    {{ number_format($counts['duplicates']) }}

                </h3>

            </div>

        </div>

    </div>


    <div class="col-xl-2 col-md-4 col-6">

        <div class="card review-stat-card h-100">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Breaks in Service
                </p>

                <h3 id="count-breaks"
                    class="text-info">

                    {{ number_format($counts['breaks']) }}

                </h3>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     REVIEW DECISION SUMMARY
========================================================= --}}

<div class="row g-3">

    <div class="col-md-4">

        <div class="card h-100">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Approved
                </p>

                <h3 id="count-approved"
                    class="text-success">

                    {{ number_format($counts['approved']) }}

                </h3>

            </div>

        </div>

    </div>


    <div class="col-md-4">

        <div class="card h-100">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Excluded
                </p>

                <h3 id="count-excluded"
                    class="text-danger">

                    {{ number_format($counts['excluded']) }}

                </h3>

            </div>

        </div>

    </div>


    <div class="col-md-4">

        <div class="card h-100">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Pending Review
                </p>

                <h3 id="count-pending"
                    class="text-warning">

                    {{ number_format($counts['pending']) }}

                </h3>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     BULK ACTIONS
========================================================= --}}
@if(in_array($batch->status, ['awaiting_review', 'posting_failed'], true))

<div class="card">

    <div class="card-body">

        <div class="d-flex flex-wrap gap-2">

            {{-- APPROVE WARNINGS ONLY --}}
            @if($counts['warning'] > 0)

                <form method="POST"
                      action="{{ route('pensions-administration.historical-contributions.review.approve-warnings', $batch) }}"
                      onsubmit="return confirm('Approve all pending warning transactions that are not duplicates?');">

                    @csrf

                    <button type="submit"
                            class="btn btn-warning">

                        <i class="mdi mdi-alert-check-outline me-1"></i>

                        Approve All Warnings
                        ({{ number_format($counts['warning']) }})

                    </button>

                </form>

            @endif


            {{-- APPROVE ALL ELIGIBLE --}}
            <form method="POST"
                  action="{{ route('pensions-administration.historical-contributions.review.approve-eligible', $batch) }}"
                  onsubmit="return confirm('Approve all remaining eligible valid and warning historical contribution transactions? Duplicate transactions will be excluded automatically.');">

                @csrf

                <button type="submit"
                        class="btn btn-success">

                    <i class="mdi mdi-check-all me-1"></i>

                    Approve All Eligible

                </button>

            </form>


            {{-- FINALISE --}}
            <form method="POST"
                  action="{{ route('pensions-administration.historical-contributions.review.finalise', $batch) }}"
                  onsubmit="return confirm('Finalise this historical contribution review and mark the batch ready for posting?');">

                @csrf

                <button type="submit"
                        class="btn btn-primary">

                    <i class="mdi mdi-shield-check-outline me-1"></i>

                    Finalise Review

                </button>

            </form>

        </div>


        <div class="alert alert-info mt-3 mb-0">

            <strong>Review rule:</strong>

            Warning transactions can be approved because historical members may have incomplete legacy information.

            Error transactions must be corrected or excluded.

            Duplicate transactions cannot be approved.

        </div>

    </div>

</div>

@endif


{{-- =========================================================
     FILTERS
========================================================= --}}

<div class="card review-filter-card">

    <div class="card-body">

        <h4 class="header-title mb-3">
            Review Filters
        </h4>


        <form id="review-filter-form">

            <div class="row">


                <div class="col-xl-2 col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Validation
                        </label>

                        <select id="filter-validation"
                                class="form-select">

                            <option value="">
                                All
                            </option>

                            <option value="valid">
                                Valid
                            </option>

                            <option value="warning">
                                Warning
                            </option>

                            <option value="error">
                                Error
                            </option>

                        </select>

                    </div>

                </div>


                <div class="col-xl-2 col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Member Type
                        </label>

                        <select id="filter-member-type"
                                class="form-select">

                            <option value="">
                                All
                            </option>

                            <option value="existing">
                                Existing Member
                            </option>

                            <option value="new">
                                Proposed New Member
                            </option>

                            <option value="ambiguous">
                                Ambiguous
                            </option>

                        </select>

                    </div>

                </div>


                <div class="col-xl-2 col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Duplicate
                        </label>

                        <select id="filter-duplicate"
                                class="form-select">

                            <option value="">
                                All
                            </option>

                            <option value="none">
                                Not Duplicate
                            </option>

                            <option value="duplicate_in_file">
                                Duplicate in File
                            </option>

                            <option value="duplicate_existing">
                                Already in PENERP
                            </option>

                        </select>

                    </div>

                </div>


                <div class="col-xl-2 col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Transaction
                        </label>

                        <select id="filter-transaction"
                                class="form-select">

                            <option value="">
                                All
                            </option>

                            <option value="expected">
                                Monthly
                            </option>

                            <option value="take_on">
                                Take-On
                            </option>

                        </select>

                    </div>

                </div>


                <div class="col-xl-2 col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Service
                        </label>

                        <select id="filter-service"
                                class="form-select">

                            <option value="">
                                All
                            </option>

                            <option value="contributed">
                                Contributed
                            </option>

                            <option value="zero_contribution">
                                0.0000
                            </option>

                            <option value="break_in_service">
                                Break in Service
                            </option>

                        </select>

                    </div>

                </div>


                <div class="col-xl-2 col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Review
                        </label>

                        <select id="filter-review"
                                class="form-select">

                            <option value="">
                                All
                            </option>

                            <option value="pending">
                                Pending
                            </option>

                            <option value="approved">
                                Approved
                            </option>

                            <option value="excluded">
                                Excluded
                            </option>

                        </select>

                    </div>

                </div>


                <div class="col-xl-3 col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            PenAd Member Number
                        </label>

                        <input id="filter-penad"
                               type="text"
                               class="form-control">

                    </div>

                </div>


                <div class="col-xl-3 col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            Staff Number
                        </label>

                        <input id="filter-staff"
                               type="text"
                               class="form-control">

                    </div>

                </div>


                <div class="col-xl-3 col-md-4">

                    <div class="mb-3">

                        <label class="form-label">
                            National ID
                        </label>

                        <input id="filter-national-id"
                               type="text"
                               class="form-control">

                    </div>

                </div>


                <div class="col-xl-3">

                    <div class="mb-3">

                        <label class="form-label d-block">
                            &nbsp;
                        </label>

                        <button type="submit"
                                class="btn btn-primary">

                            <i class="mdi mdi-filter-outline me-1"></i>

                            Apply Filters

                        </button>


                        <button id="clear-review-filters"
                                type="button"
                                class="btn btn-light">

                            Clear

                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>

</div>


{{-- =========================================================
     REVIEW TABLE
========================================================= --}}

<div class="card">

    <div class="card-body">

        <h4 class="header-title mb-3">
            Historical Contribution Transactions
        </h4>


        <div class="table-responsive">

            <table id="historical-review-table"
                   class="table table-bordered table-striped table-hover align-middle"
                   style="width:100%">

                <thead>

                    <tr>

                        <th>Source Row</th>

                        <th>PenAd No.</th>

                        <th>Staff No.</th>

                        <th>Member</th>

                        <th>National ID</th>

                        <th>Employer</th>

                        <th>Period</th>

                        <th>Type</th>

                        <th>Employee</th>

                        <th>Employer</th>

                        <th>Service</th>

                        <th>Validation</th>

                        <th>Duplicate</th>

                        <th>Review</th>

                        <th>Messages</th>

                        <th>Actions</th>

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

<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>


<script>

$(document).ready(function () {

    const csrfToken =
        @json(csrf_token());


    /*
    |--------------------------------------------------------------------------
    | DataTable
    |--------------------------------------------------------------------------
    */

    const table =
        $('#historical-review-table')
            .DataTable({

                processing:
                    true,

                serverSide:
                    true,

                deferRender:
                    true,

                searchDelay:
                    500,

                pageLength:
                    25,

                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],


                ajax: {

                    url:
                        @json(
                            route(
                                'pensions-administration.historical-contributions.review.data',
                                $batch
                            )
                        ),

                    type:
                        'GET',

                    data: function (data) {

                        data.validation_status =
                            $('#filter-validation').val();

                        data.member_type =
                            $('#filter-member-type').val();

                        data.duplicate_status =
                            $('#filter-duplicate').val();

                        data.transaction_type =
                            $('#filter-transaction').val();

                        data.service_status =
                            $('#filter-service').val();

                        data.review_decision =
                            $('#filter-review').val();

                        data.penad_member_number =
                            $('#filter-penad').val();

                        data.staff_number =
                            $('#filter-staff').val();

                        data.national_id =
                            $('#filter-national-id').val();

                    }

                },


                columns: [

                    {
                        data: 'source_row',
                        name: 'source_row_number'
                    },

                    {
                        data: 'penad_number',
                        name: 'penad_member_number'
                    },

                    {
                        data: 'staff_number',
                        name: 'staff_number'
                    },

                    {
                        data: 'member',
                        name: 'surname'
                    },

                    {
                        data: 'national_id',
                        name: 'national_id'
                    },

                    {
                        data: 'employer',
                        name: 'employer_name',
                        orderable: false
                    },

                    {
                        data: 'period',
                        name: 'period_date'
                    },

                    {
                        data: 'transaction_type',
                        name: 'transaction_type'
                    },

                    {
                        data: 'employee_contribution',
                        name: 'employee_contribution'
                    },

                    {
                        data: 'employer_contribution',
                        name: 'employer_contribution'
                    },

                    {
                        data: 'service_status',
                        name: 'service_status'
                    },

                    {
                        data: 'validation_status',
                        name: 'validation_status'
                    },

                    {
                        data: 'duplicate_status',
                        name: 'duplicate_status'
                    },

                    {
                        data: 'review_decision',
                        name: 'review_decision'
                    },

                    {
                        data: 'messages',
                        name: 'messages',
                        orderable: false,
                        searchable: false,
                        className: 'review-message-cell'
                    },

                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }

                ],


                order: [
                    [0, 'asc']
                ],


                dom:

                    "<'row mb-3 align-items-center'"

                        + "<'col-md-8'B>"

                        + "<'col-md-4'f>"

                    + ">"

                    + "<'row mb-2'"

                        + "<'col-md-6'l>"

                        + "<'col-md-6 text-md-end'i>"

                    + ">"

                    + "rt"

                    + "<'row mt-3'"

                        + "<'col-md-6'i>"

                        + "<'col-md-6 d-flex justify-content-md-end'p>"

                    + ">",


                buttons: [

                    {
                        extend:
                            'copyHtml5',

                        text:
                            'Copy Page',

                        className:
                            'btn btn-secondary btn-sm'
                    },

                    {
                        extend:
                            'excelHtml5',

                        text:
                            'Excel Page',

                        className:
                            'btn btn-success btn-sm',

                        filename:
                            'PENERP_Historical_Contribution_Review',

                        exportOptions: {
                            columns: [
                                0,1,2,3,4,5,6,7,
                                8,9,10,11,12,13,14
                            ],
                            stripHtml: true
                        }
                    }

                ]

            });


    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    $('#review-filter-form')
        .on(
            'submit',
            function (event) {

                event.preventDefault();

                table
                    .ajax
                    .reload();

            }
        );


    $('#clear-review-filters')
        .on(
            'click',
            function () {

                $('#review-filter-form')
                    .find(
                        'input, select'
                    )
                    .val('');

                table
                    .search('');

                table
                    .ajax
                    .reload();

            }
        );


    /*
    |--------------------------------------------------------------------------
    | Individual Review Actions
    |--------------------------------------------------------------------------
    */

    $(document)
        .on(
            'click',
            '.review-row-btn',
            async function () {

                const button =
                    $(this);

                const url =
                    button.data(
                        'url'
                    );

                const decision =
                    button.data(
                        'decision'
                    );


                let confirmationMessage =
                    'Apply this review decision?';


                if (
                    decision
                    ===
                    'approved'
                ) {
                    confirmationMessage =
                        'Approve this historical transaction?';
                }


                if (
                    decision
                    ===
                    'excluded'
                ) {
                    confirmationMessage =
                        'Exclude this transaction from historical posting?';
                }


                if (
                    !confirm(
                        confirmationMessage
                    )
                ) {
                    return;
                }


                button.prop(
                    'disabled',
                    true
                );


                try {

                    const response =
                        await fetch(
                            url,
                            {
                                method:
                                    'POST',

                                headers: {
                                    'Accept':
                                        'application/json',

                                    'Content-Type':
                                        'application/json',

                                    'X-CSRF-TOKEN':
                                        csrfToken,

                                    'X-Requested-With':
                                        'XMLHttpRequest'
                                },

                                body:
                                    JSON.stringify({
                                        decision:
                                            decision
                                    })
                            }
                        );


                    const result =
                        await response.json();


                    if (
                        !response.ok
                    ) {
                        alert(
                            result.message
                            ||
                            'The review decision could not be saved.'
                        );

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Counters
                    |--------------------------------------------------------------------------
                    */

                    if (
                        result.counts
                    ) {

                        $('#count-approved')
                            .text(
                                Number(
                                    result.counts.approved
                                    ||
                                    0
                                )
                                .toLocaleString()
                            );

                        $('#count-excluded')
                            .text(
                                Number(
                                    result.counts.excluded
                                    ||
                                    0
                                )
                                .toLocaleString()
                            );

                        $('#count-pending')
                            .text(
                                Number(
                                    result.counts.pending
                                    ||
                                    0
                                )
                                .toLocaleString()
                            );

                    }


                    table
                        .ajax
                        .reload(
                            null,
                            false
                        );


                } catch (error) {

                    console.error(
                        error
                    );

                    alert(
                        'The review decision could not be saved.'
                    );

                } finally {

                    button.prop(
                        'disabled',
                        false
                    );

                }

            }
        );

});

</script>

@endpush
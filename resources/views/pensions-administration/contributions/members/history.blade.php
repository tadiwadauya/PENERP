@extends('layouts.app')

@section('title', 'Member Contribution History')

@section('page-heading', 'Member Expected Contribution History')

@section('page-subheading')
Monthly expected contributions and service continuity
@endsection


@push('styles')

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<style>
    .history-stat-card {
        height: 100%;
        border: 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
    }

    .history-table th {
        white-space: nowrap;
    }

    .history-table td {
        vertical-align: middle;
    }

    .service-break-row {
        background-color: rgba(220, 53, 69, .06) !important;
    }

    .nil-contributor-row {
        background-color: rgba(255, 193, 7, .08) !important;
    }

    .missing-contribution-row {
        background-color: rgba(13, 110, 253, .05) !important;
    }

    .dt-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .dt-buttons .btn {
        margin: 0 !important;
    }
</style>

@endpush


@section('content')

@include('pensions-administration.partials.navigation')


{{-- =========================================================
     MEMBER
========================================================= --}}

<div class="card">

    <div class="card-body">

        <div class="row align-items-center">

            <div class="col-lg-8">

                <h4 class="mb-1">
                    {{ $member->surname }},
                    {{ $member->first_names }}
                </h4>

                <div class="text-muted">

                    <strong>PENERP:</strong>
                    {{ $member->member_number }}

                    <span class="mx-2">|</span>

                    <strong>PenAd:</strong>
                    {{ $member->penad_member_number ?? '-' }}

                    <span class="mx-2">|</span>

                    <strong>Fundworx:</strong>
                    {{ $member->fundworx_member_number ?? '-' }}

                </div>

                <div class="text-muted mt-1">

                    <strong>National ID:</strong>
                    {{ $member->national_id ?? '-' }}

                    <span class="mx-2">|</span>

                    <strong>Current Employer:</strong>
                    {{ $member->currentEmployment?->employer?->name ?? '-' }}

                </div>

            </div>

            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">

                <span class="badge bg-primary font-size-14">
                    {{ number_format($summary['contributed_months']) }}
                    Contribution Months
                </span>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     SUMMARY
========================================================= --}}

<div class="row g-3 mb-3">

    <div class="col-xl-2 col-md-4 col-6">

        <div class="card history-stat-card">

            <div class="card-body">

                <p class="text-muted mb-1">
                    History Months
                </p>

                <h3 class="mb-0">
                    {{ number_format($summary['total_months']) }}
                </h3>

            </div>

        </div>

    </div>


    <div class="col-xl-2 col-md-4 col-6">

        <div class="card history-stat-card">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Contributed
                </p>

                <h3 class="text-success mb-0">
                    {{ number_format($summary['contributed_months']) }}
                </h3>

            </div>

        </div>

    </div>


    <div class="col-xl-2 col-md-4 col-6">

        <div class="card history-stat-card">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Nil Contributor
                </p>

                <h3 class="text-warning mb-0">
                    {{ number_format($summary['nil_contributor_months']) }}
                </h3>

            </div>

        </div>

    </div>


    <div class="col-xl-2 col-md-4 col-6">

        <div class="card history-stat-card">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Missing Expected
                </p>

                <h3 class="text-primary mb-0">
                    {{ number_format($summary['missing_expected_months']) }}
                </h3>

            </div>

        </div>

    </div>


    <div class="col-xl-2 col-md-4 col-6">

        <div class="card history-stat-card">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Break Months
                </p>

                <h3 class="text-danger mb-0">
                    {{ number_format($summary['break_months']) }}
                </h3>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     HISTORY
========================================================= --}}

<div class="card">

    <div class="card-body">

        <div class="mb-3">

            <h4 class="header-title mb-1">
                Expected Contribution History
            </h4>

            <p class="text-muted mb-0">
                Month-by-month expected contribution history. Months without
                expected contributions are retained so that nil contributions,
                missing schedules and service breaks remain visible.
            </p>

        </div>


        <div class="table-responsive">

            <table id="member-contribution-history-table"
                   class="table table-bordered table-striped table-hover history-table w-100">

                <thead>

                    <tr>

                        <th>Period</th>
                        <th>Status</th>
                        <th>Employer</th>

                        <th>ZWG Basic Pay</th>
                        <th>ZWG EE Rate</th>
                        <th>ZWG Employee</th>
                        <th>ZWG ER Rate</th>
                        <th>ZWG Employer</th>
                        <th>ZWG EE AVC</th>
                        <th>ZWG ER AVC</th>

                        <th>USD Basic Pay</th>
                        <th>USD EE Rate</th>
                        <th>USD Employee</th>
                        <th>USD ER Rate</th>
                        <th>USD Employer</th>
                        <th>USD EE AVC</th>
                        <th>USD ER AVC</th>

                        <th>Remarks</th>

                    </tr>

                </thead>


                <tbody>

                    @foreach($history as $row)

                        @php
                            $rowClass = match($row['status']) {
                                'break_in_service' =>
                                    'service-break-row',

                                'nil_contributor' =>
                                    'nil-contributor-row',

                                'missing_expected' =>
                                    'missing-contribution-row',

                                default =>
                                    '',
                            };

                            $badgeClass = match($row['status']) {
                                'contributed' =>
                                    'bg-success',

                                'nil_contributor' =>
                                    'bg-warning text-dark',

                                'break_in_service' =>
                                    'bg-danger',

                                'missing_expected' =>
                                    'bg-primary',

                                default =>
                                    'bg-secondary',
                            };
                        @endphp

                        <tr class="{{ $rowClass }}">

                            <td data-order="{{ $row['period_sort'] }}">

                                <strong>
                                    {{ $row['period'] }}
                                </strong>

                            </td>


                            <td>

                                <span class="badge {{ $badgeClass }}">
                                    {{ $row['status_label'] }}
                                </span>

                            </td>


                            <td>

                                {{ $row['employer_name'] }}

                                @if($row['employer_number'])

                                    <br>

                                    <small class="text-muted">
                                        {{ $row['employer_number'] }}
                                    </small>

                                @endif

                            </td>


                            {{-- ZWG --}}

                            <td class="text-end">
                                {{ number_format($row['zwg_basic_pay'], 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($row['zwg_employee_rate'], 2) }}%
                            </td>

                            <td class="text-end">
                                {{ number_format($row['zwg_employee_contribution'], 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($row['zwg_employer_rate'], 2) }}%
                            </td>

                            <td class="text-end">
                                {{ number_format($row['zwg_employer_contribution'], 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($row['zwg_employee_avc'], 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($row['zwg_employer_avc'], 2) }}
                            </td>


                            {{-- USD --}}

                            <td class="text-end">
                                {{ number_format($row['usd_basic_pay'], 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($row['usd_employee_rate'], 2) }}%
                            </td>

                            <td class="text-end">
                                {{ number_format($row['usd_employee_contribution'], 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($row['usd_employer_rate'], 2) }}%
                            </td>

                            <td class="text-end">
                                {{ number_format($row['usd_employer_contribution'], 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($row['usd_employee_avc'], 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($row['usd_employer_avc'], 2) }}
                            </td>


                            <td>
                                {{ $row['status_reason'] }}
                            </td>

                        </tr>

                    @endforeach

                </tbody>

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

    $('#member-contribution-history-table').DataTable({
        pageLength: 25,

        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, 'All']
        ],

        order: [
            [0, 'desc']
        ],

        autoWidth: false,

        dom:
            "<'row align-items-center mb-3'"
                + "<'col-lg-8 col-md-12 mb-2 mb-lg-0'B>"
                + "<'col-lg-4 col-md-12'f>"
            + ">"
            + "<'row mb-2'"
                + "<'col-md-6'l>"
                + "<'col-md-6 text-md-end'i>"
            + ">"
            + "rt"
            + "<'row align-items-center mt-3'"
                + "<'col-md-6'i>"
                + "<'col-md-6 d-flex justify-content-md-end'p>"
            + ">",

        buttons: [
            {
                extend: 'copyHtml5',
                text: '<i class="mdi mdi-content-copy me-1"></i> Copy',
                className: 'btn btn-secondary btn-sm',
                title: 'PENERP Member Expected Contribution History'
            },

            {
                extend: 'excelHtml5',
                text: '<i class="mdi mdi-microsoft-excel me-1"></i> Excel',
                className: 'btn btn-success btn-sm',
                title: 'PENERP Member Expected Contribution History',
                filename: 'PENERP_Member_Expected_Contribution_History'
            },

            {
                extend: 'csvHtml5',
                text: '<i class="mdi mdi-file-delimited-outline me-1"></i> CSV',
                className: 'btn btn-info btn-sm',
                title: 'PENERP Member Expected Contribution History',
                filename: 'PENERP_Member_Expected_Contribution_History'
            },

            {
                extend: 'pdfHtml5',
                text: '<i class="mdi mdi-file-pdf-box me-1"></i> PDF',
                className: 'btn btn-danger btn-sm',
                title: 'PENERP Member Expected Contribution History',
                filename: 'PENERP_Member_Expected_Contribution_History',
                orientation: 'landscape',
                pageSize: 'A3'
            },

            {
                extend: 'print',
                text: '<i class="mdi mdi-printer-outline me-1"></i> Print',
                className: 'btn btn-dark btn-sm',
                title: 'PENERP Member Expected Contribution History'
            }
        ]
    });

});
</script>

@endpush
@extends('layouts.app')

@section('title', 'Member Contribution History')

@section('page-heading', 'Member Contribution History')

@section('page-subheading')
Monthly expected contributions, historical Take-On opening balances and service continuity
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
        vertical-align: middle;
    }

    .history-table td {
        vertical-align: middle;
        white-space: nowrap;
    }

    .history-table td.remarks-column {
        white-space: normal;
        min-width: 280px;
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

    .take-on-only-row {
        background-color: rgba(108, 117, 125, .07) !important;
    }

    .dt-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .dt-buttons .btn {
        margin: 0 !important;
    }

    .history-source {
        font-size: 11px;
    }

    .history-section-heading {
        background: #f8f9fa;
        text-align: center;
    }

    .take-on-heading {
        background: rgba(108, 117, 125, .10);
        text-align: center;
    }
</style>
@endpush

@section('content')

@include('pensions-administration.partials.navigation')

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="mb-1">
                    {{ $member->surname }},
                    {{ $member->first_names }}
                </h4>

                <div class="text-muted">
                    <strong>PENERP:</strong>
                    {{ $member->member_number ?? '-' }}

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
                    {{ number_format($summary['contributed_months'] ?? 0) }}
                    Contribution Months
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card history-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">History Months</p>
                <h3 class="mb-0">
                    {{ number_format($summary['total_months'] ?? 0) }}
                </h3>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6">
        <div class="card history-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Contributed</p>
                <h3 class="text-success mb-0">
                    {{ number_format($summary['contributed_months'] ?? 0) }}
                </h3>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6">
        <div class="card history-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Take-On Month</p>
                <h3 class="text-secondary mb-0">
                    {{ number_format($summary['take_on_months'] ?? 0) }}
                </h3>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6">
        <div class="card history-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Nil Contributor</p>
                <h3 class="text-warning mb-0">
                    {{ number_format($summary['nil_contributor_months'] ?? 0) }}
                </h3>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6">
        <div class="card history-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Missing Expected</p>
                <h3 class="text-primary mb-0">
                    {{ number_format($summary['missing_expected_months'] ?? 0) }}
                </h3>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6">
        <div class="card history-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Break Months</p>
                <h3 class="text-danger mb-0">
                    {{ number_format($summary['break_months'] ?? 0) }}
                </h3>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="mb-3">
            <h4 class="header-title mb-1">Historical Take-On / Opening Balance</h4>
            <p class="text-muted mb-0">
                Opening balances brought forward into January 2009. These amounts are kept separate from the normal January 2009 monthly contribution.
            </p>
        </div>

        <div class="row g-3">
            <div class="col-xl-3 col-md-6">
                <div class="card history-stat-card border">
                    <div class="card-body">
                        <p class="text-muted mb-1">Take-On Employee</p>
                        <h4 class="mb-0">
                            {{ number_format($summary['take_on_employee_total'] ?? 0, 4) }}
                        </h4>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card history-stat-card border">
                    <div class="card-body">
                        <p class="text-muted mb-1">Take-On Employer</p>
                        <h4 class="mb-0">
                            {{ number_format($summary['take_on_employer_total'] ?? 0, 4) }}
                        </h4>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card history-stat-card border">
                    <div class="card-body">
                        <p class="text-muted mb-1">Take-On Employee AVC</p>
                        <h4 class="mb-0">
                            {{ number_format($summary['take_on_employee_avc_total'] ?? 0, 4) }}
                        </h4>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card history-stat-card border">
                    <div class="card-body">
                        <p class="text-muted mb-1">Take-On Employer AVC</p>
                        <h4 class="mb-0">
                            {{ number_format($summary['take_on_employer_avc_total'] ?? 0, 4) }}
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card history-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Monthly Employee Contributions</p>
                <h4 class="mb-0">
                    {{ number_format($summary['zwg_employee_total'] ?? 0, 4) }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card history-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Monthly Employer Contributions</p>
                <h4 class="mb-0">
                    {{ number_format($summary['zwg_employer_total'] ?? 0, 4) }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card history-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Monthly Employee AVC</p>
                <h4 class="mb-0">
                    {{ number_format($summary['zwg_employee_avc_total'] ?? 0, 4) }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card history-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Monthly Employer AVC</p>
                <h4 class="mb-0">
                    {{ number_format($summary['zwg_employer_avc_total'] ?? 0, 4) }}
                </h4>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <h4 class="header-title mb-1">Contribution History</h4>

            <p class="text-muted mb-0">
                History begins from January 2009. Take-On is shown as a separate opening balance and is not combined with the normal January 2009 monthly contribution.
            </p>
        </div>

        <div class="table-responsive">
            <table id="member-contribution-history-table"
                   class="table table-bordered table-striped table-hover history-table w-100">

                <thead>
                    <tr>
                        <th rowspan="2">Period</th>
                        <th rowspan="2">Status</th>
                        <th rowspan="2">Source</th>
                        <th rowspan="2">Employer</th>

                        <th colspan="4" class="take-on-heading">
                            Take-On / Opening Balance
                        </th>

                        <th colspan="7" class="history-section-heading">
                            Expected Contribution
                        </th>

                        <th colspan="7" class="history-section-heading">
                            USD Contribution
                        </th>

                        <th rowspan="2">Remarks</th>
                    </tr>

                    <tr>
                        <th>Employee</th>
                        <th>Employer</th>
                        <th>EE AVC</th>
                        <th>ER AVC</th>

                        <th>Basic Pay</th>
                        <th>EE Rate</th>
                        <th>Employee</th>
                        <th>ER Rate</th>
                        <th>Employer</th>
                        <th>EE AVC</th>
                        <th>ER AVC</th>

                        <th>Basic Pay</th>
                        <th>EE Rate</th>
                        <th>Employee</th>
                        <th>ER Rate</th>
                        <th>Employer</th>
                        <th>EE AVC</th>
                        <th>ER AVC</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($history as $row)
                        @php
                            $status = $row['status'] ?? '';

                            $rowClass = match($status) {
                                'break_in_service' => 'service-break-row',
                                'nil_contributor' => 'nil-contributor-row',
                                'missing_expected' => 'missing-contribution-row',
                                'take_on_only' => 'take-on-only-row',
                                default => '',
                            };

                            $badgeClass = match($status) {
                                'contributed' => 'bg-success',
                                'nil_contributor' => 'bg-warning text-dark',
                                'break_in_service' => 'bg-danger',
                                'missing_expected' => 'bg-primary',
                                'take_on_only' => 'bg-secondary',
                                default => 'bg-secondary',
                            };

                            $sourceSystem = $row['source_system'] ?? null;

                            $isHistorical =
                                $row['is_historical']
                                ?? ($sourceSystem === 'historical_migration');

                            $sourceLabel =
                                $isHistorical
                                    ? 'Historical'
                                    : 'Monthly';

                            $sourceBadge =
                                $isHistorical
                                    ? 'bg-secondary'
                                    : 'bg-info text-dark';

                            $hasExpected =
                                (bool) (
                                    $row['has_expected_contribution']
                                    ?? ($status === 'contributed')
                                );

                            $hasTakeOn =
                                (bool) (
                                    $row['has_take_on']
                                    ?? false
                                );

                            $hasAnyRecord =
                                $hasExpected
                                || $hasTakeOn;

                            $formatAmount = function ($value) {
                                $number = (float) ($value ?? 0);

                                return abs($number) < 0.00005
                                    ? '-'
                                    : number_format($number, 4);
                            };

                            $formatRate = function ($value) {
                                $number = (float) ($value ?? 0);

                                return abs($number) < 0.00005
                                    ? '-'
                                    : number_format($number, 4) . '%';
                            };
                        @endphp

                        <tr class="{{ $rowClass }}">
                            <td data-order="{{ $row['period_sort'] ?? '' }}">
                                <strong>{{ $row['period'] ?? '-' }}</strong>
                            </td>

                            <td>
                                <span class="badge {{ $badgeClass }}">
                                    {{ $row['status_label'] ?? '-' }}
                                </span>
                            </td>

                            <td>
                                @if($hasAnyRecord)
                                    <span class="badge {{ $sourceBadge }} history-source">
                                        {{ $sourceLabel }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td>
                                {{ $row['employer_name'] ?? '-' }}

                                @if(!empty($row['employer_number']))
                                    <br>
                                    <small class="text-muted">
                                        {{ $row['employer_number'] }}
                                    </small>
                                @endif
                            </td>

                            {{-- TAKE-ON --}}
                            <td class="text-end"
                                data-order="{{ $row['take_on_employee_contribution'] ?? 0 }}">
                                {{ $hasTakeOn ? $formatAmount($row['take_on_employee_contribution'] ?? 0) : '-' }}
                            </td>

                            <td class="text-end"
                                data-order="{{ $row['take_on_employer_contribution'] ?? 0 }}">
                                {{ $hasTakeOn ? $formatAmount($row['take_on_employer_contribution'] ?? 0) : '-' }}
                            </td>

                            <td class="text-end"
                                data-order="{{ $row['take_on_employee_avc'] ?? 0 }}">
                                {{ $hasTakeOn ? $formatAmount($row['take_on_employee_avc'] ?? 0) : '-' }}
                            </td>

                            <td class="text-end"
                                data-order="{{ $row['take_on_employer_avc'] ?? 0 }}">
                                {{ $hasTakeOn ? $formatAmount($row['take_on_employer_avc'] ?? 0) : '-' }}
                            </td>

                            {{-- EXPECTED / GENERIC HISTORICAL --}}
                            <td class="text-end"
                                data-order="{{ $row['display_basic_pay'] ?? 0 }}">
                                {{ $hasExpected ? $formatAmount($row['display_basic_pay'] ?? 0) : '-' }}
                            </td>

                            <td class="text-end"
                                data-order="{{ $row['display_employee_rate'] ?? 0 }}">
                                {{ $hasExpected ? $formatRate($row['display_employee_rate'] ?? 0) : '-' }}
                            </td>

                            <td class="text-end"
                                data-order="{{ $row['display_employee_contribution'] ?? 0 }}">
                                @if($hasExpected)
                                    <strong>
                                        {{ $formatAmount($row['display_employee_contribution'] ?? 0) }}
                                    </strong>
                                @else
                                    -
                                @endif
                            </td>

                            <td class="text-end"
                                data-order="{{ $row['display_employer_rate'] ?? 0 }}">
                                {{ $hasExpected ? $formatRate($row['display_employer_rate'] ?? 0) : '-' }}
                            </td>

                            <td class="text-end"
                                data-order="{{ $row['display_employer_contribution'] ?? 0 }}">
                                @if($hasExpected)
                                    <strong>
                                        {{ $formatAmount($row['display_employer_contribution'] ?? 0) }}
                                    </strong>
                                @else
                                    -
                                @endif
                            </td>

                            <td class="text-end"
                                data-order="{{ $row['display_employee_avc'] ?? 0 }}">
                                {{ $hasExpected ? $formatAmount($row['display_employee_avc'] ?? 0) : '-' }}
                            </td>

                            <td class="text-end"
                                data-order="{{ $row['display_employer_avc'] ?? 0 }}">
                                {{ $hasExpected ? $formatAmount($row['display_employer_avc'] ?? 0) : '-' }}
                            </td>

                            {{-- USD --}}
                            <td class="text-end"
                                data-order="{{ $row['usd_basic_pay'] ?? 0 }}">
                                {{ $hasExpected ? $formatAmount($row['usd_basic_pay'] ?? 0) : '-' }}
                            </td>

                            <td class="text-end"
                                data-order="{{ $row['usd_employee_rate'] ?? 0 }}">
                                {{ $hasExpected ? $formatRate($row['usd_employee_rate'] ?? 0) : '-' }}
                            </td>

                            <td class="text-end"
                                data-order="{{ $row['usd_employee_contribution'] ?? 0 }}">
                                {{ $hasExpected ? $formatAmount($row['usd_employee_contribution'] ?? 0) : '-' }}
                            </td>

                            <td class="text-end"
                                data-order="{{ $row['usd_employer_rate'] ?? 0 }}">
                                {{ $hasExpected ? $formatRate($row['usd_employer_rate'] ?? 0) : '-' }}
                            </td>

                            <td class="text-end"
                                data-order="{{ $row['usd_employer_contribution'] ?? 0 }}">
                                {{ $hasExpected ? $formatAmount($row['usd_employer_contribution'] ?? 0) : '-' }}
                            </td>

                            <td class="text-end"
                                data-order="{{ $row['usd_employee_avc'] ?? 0 }}">
                                {{ $hasExpected ? $formatAmount($row['usd_employee_avc'] ?? 0) : '-' }}
                            </td>

                            <td class="text-end"
                                data-order="{{ $row['usd_employer_avc'] ?? 0 }}">
                                {{ $hasExpected ? $formatAmount($row['usd_employer_avc'] ?? 0) : '-' }}
                            </td>

                            <td class="remarks-column">
                                {{ $row['status_reason'] ?? '-' }}
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
    const table = $('#member-contribution-history-table').DataTable({
        pageLength: 25,

        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, 'All']
        ],

        order: [
            [0, 'desc']
        ],

        autoWidth: false,
        scrollX: true,

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
                title: 'PENERP Member Contribution History'
            },
            {
                extend: 'excelHtml5',
                text: '<i class="mdi mdi-microsoft-excel me-1"></i> Excel',
                className: 'btn btn-success btn-sm',
                title: 'PENERP Member Contribution History',
                filename: 'PENERP_Member_Contribution_History'
            },
            {
                extend: 'csvHtml5',
                text: '<i class="mdi mdi-file-delimited-outline me-1"></i> CSV',
                className: 'btn btn-info btn-sm',
                title: 'PENERP Member Contribution History',
                filename: 'PENERP_Member_Contribution_History'
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="mdi mdi-file-pdf-box me-1"></i> PDF',
                className: 'btn btn-danger btn-sm',
                title: 'PENERP Member Contribution History',
                filename: 'PENERP_Member_Contribution_History',
                orientation: 'landscape',
                pageSize: 'A3'
            },
            {
                extend: 'print',
                text: '<i class="mdi mdi-printer-outline me-1"></i> Print',
                className: 'btn btn-dark btn-sm',
                title: 'PENERP Member Contribution History'
            }
        ]
    });

    setTimeout(function () {
        table.columns.adjust();
    }, 100);
});
</script>

@endpush

@extends('layouts.app')

@section('title', 'Monthly Contribution Reconciliation')

@section('content')

@php
    $batch = $report['batch'];
    $employer = $report['employer'];
    $period = $report['current_period'];
    $previousPeriod = $report['previous_period'];
    $currency = $report['currency'];
    $membership = $report['membership'];
    $contributions = $report['contributions'];
    $schedule = $report['schedule'];
    $calculation = $report['calculation'];
    $exceptions = $report['exceptions'];
@endphp

<div class="container-fluid">

    <div class="row mb-3">

        <div class="col-12">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                <div>

                    <h4 class="mb-1">
                        Monthly Contribution Reconciliation
                    </h4>

                    <p class="text-muted mb-0">
                        {{ $employer?->name ?? 'Employer' }}
                        |
                        Batch #{{ $batch->id }}
                        |
                        {{ $period?->period_date?->format('F Y') }}
                        |
                        {{ $currency }}
                    </p>

                </div>


                <div class="d-flex flex-wrap gap-2">

                    <a href="{{ route('pensions-administration.contributions.imports.review', $batch) }}" class="btn btn-outline-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i>
                        Back to Review
                    </a>

                    <a href="{{ route('pensions-administration.contributions.reconciliation.pdf', $batch) }}" class="btn btn-danger">
                        <i class="mdi mdi-file-pdf-box me-1"></i>
                        PDF
                    </a>

                    <a href="{{ route('pensions-administration.contributions.reconciliation.excel', $batch) }}" class="btn btn-success">
                        <i class="mdi mdi-microsoft-excel me-1"></i>
                        Excel
                    </a>

                </div>

            </div>

        </div>

    </div>


    {{-- EXCEPTION SUMMARY --}}

    <div class="row g-3 mb-4">

        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Rate Exception Rows</p>
                    <h3 class="mb-0 text-warning">{{ number_format($exceptions['rate_rows']) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Calculation Exception Rows</p>
                    <h3 class="mb-0 text-warning">{{ number_format($exceptions['contribution_rows']) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Warning Rows</p>
                    <h3 class="mb-0 text-warning">{{ number_format($exceptions['warning_rows']) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Error Rows</p>
                    <h3 class="mb-0 {{ $exceptions['error_rows'] > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($exceptions['error_rows']) }}</h3>
                </div>
            </div>
        </div>

    </div>


    {{-- MEMBERSHIP RECONCILIATION --}}

    <div class="card mb-4">

        <div class="card-header">
            <h5 class="mb-0">
                Monthly Membership Reconciliation as at
                {{ $period?->period_date?->format('d F Y') }}
            </h5>
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered mb-0">

                    <tbody>

                        <tr>
                            <th>Membership as at {{ $previousPeriod?->period_date?->format('d F Y') ?? 'Previous Period' }}</th>
                            <td class="text-end"><strong>{{ number_format($membership['previous']) }}</strong></td>
                        </tr>

                        <tr>
                            <th>Add New Members</th>
                            <td class="text-end text-success"><strong>{{ number_format($membership['new_members']) }}</strong></td>
                        </tr>

                        <tr>
                            <th>Add Reinstatements</th>
                            <td class="text-end text-success"><strong>{{ number_format($membership['reinstatements']) }}</strong></td>
                        </tr>

                        <tr>
                            <th>Less Exits / Suspended / Nil Contributors</th>
                            <td class="text-end text-danger"><strong>{{ number_format($membership['less_exits_suspended_nil']) }}</strong></td>
                        </tr>

                        <tr class="table-primary">
                            <th>Membership as at {{ $period?->period_date?->format('d F Y') }}</th>
                            <td class="text-end"><strong>{{ number_format($membership['current']) }}</strong></td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    {{-- MOVEMENT RECONCILIATION --}}

    <div class="card mb-4">

        <div class="card-header">
            <h5 class="mb-0">
                Monthly Contribution Movement Reconciliation
            </h5>
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered align-middle mb-0">

                    <thead class="table-light">

                        <tr>
                            <th>Description</th>
                            <th class="text-end">Normal Contributions</th>
                            <th class="text-end">AVC</th>
                            <th class="text-end">Total</th>
                        </tr>

                    </thead>

                    <tbody>

                        <tr>
                            <th>Contributions Due as at {{ $previousPeriod?->period_date?->format('d F Y') ?? 'Previous Period' }}</th>
                            <td class="text-end">{{ $currency }} {{ number_format($contributions['previous_normal'], 2) }}</td>
                            <td class="text-end">{{ $currency }} {{ number_format($contributions['previous_avc'], 2) }}</td>
                            <td class="text-end"><strong>{{ $currency }} {{ number_format($contributions['previous_total'], 2) }}</strong></td>
                        </tr>

                        <tr>
                            <th>Add Contributions for New Members</th>
                            <td class="text-end">{{ $currency }} {{ number_format($contributions['new_members_normal'], 2) }}</td>
                            <td class="text-end">{{ $currency }} {{ number_format($contributions['new_members_avc'], 2) }}</td>
                            <td class="text-end"><strong>{{ $currency }} {{ number_format($contributions['new_members_total'], 2) }}</strong></td>
                        </tr>

                        <tr>
                            <th>Add Contributions for Reinstatements</th>
                            <td class="text-end">{{ $currency }} {{ number_format($contributions['reinstatements_normal'], 2) }}</td>
                            <td class="text-end">{{ $currency }} {{ number_format($contributions['reinstatements_avc'], 2) }}</td>
                            <td class="text-end"><strong>{{ $currency }} {{ number_format($contributions['reinstatements_total'], 2) }}</strong></td>
                        </tr>

                        <tr>
                            <th>Add Increase / Decrease on Contributions</th>
                            <td class="text-end">{{ $currency }} {{ number_format($contributions['increase_decrease_normal'], 2) }}</td>
                            <td class="text-end">{{ $currency }} {{ number_format($contributions['increase_decrease_avc'], 2) }}</td>
                            <td class="text-end"><strong>{{ $currency }} {{ number_format($contributions['increase_decrease_total'], 2) }}</strong></td>
                        </tr>

                        <tr>
                            <th>Add Differences on Contributions</th>
                            <td class="text-end">{{ $currency }} {{ number_format($contributions['differences_normal'], 2) }}</td>
                            <td class="text-end">{{ $currency }} {{ number_format($contributions['differences_avc'], 2) }}</td>
                            <td class="text-end"><strong>{{ $currency }} {{ number_format($contributions['differences_total'], 2) }}</strong></td>
                        </tr>

                        <tr>
                            <th>Less Contributions for Exits / Suspended / Nil Contributors</th>
                            <td class="text-end text-danger">{{ $currency }} {{ number_format($contributions['less_nil_normal'], 2) }}</td>
                            <td class="text-end text-danger">{{ $currency }} {{ number_format($contributions['less_nil_avc'], 2) }}</td>
                            <td class="text-end text-danger"><strong>{{ $currency }} {{ number_format($contributions['less_nil_total'], 2) }}</strong></td>
                        </tr>

                        <tr class="table-primary">
                            <th>Total Contributions Due as at {{ $period?->period_date?->format('d F Y') }}</th>
                            <td class="text-end"><strong>{{ $currency }} {{ number_format($contributions['normal_due'], 2) }}</strong></td>
                            <td class="text-end"><strong>{{ $currency }} {{ number_format($contributions['avc_due'], 2) }}</strong></td>
                            <td class="text-end"><strong>{{ $currency }} {{ number_format($contributions['total_due'], 2) }}</strong></td>
                        </tr>

                        <tr>
                            <th>Total Contributions as per Schedule from Local Authority</th>
                            <td class="text-end">{{ $currency }} {{ number_format($contributions['schedule_normal'], 2) }}</td>
                            <td class="text-end">{{ $currency }} {{ number_format($contributions['schedule_avc'], 2) }}</td>
                            <td class="text-end"><strong>{{ $currency }} {{ number_format($contributions['schedule_total'], 2) }}</strong></td>
                        </tr>

                        <tr class="{{ abs($contributions['variance']) > 0.01 ? 'table-warning' : 'table-success' }}">
                            <th>Movement Reconciliation Variance</th>
                            <td class="text-end">{{ $currency }} {{ number_format($contributions['normal_variance'], 2) }}</td>
                            <td class="text-end">{{ $currency }} {{ number_format($contributions['avc_variance'], 2) }}</td>
                            <td class="text-end"><strong>{{ $currency }} {{ number_format($contributions['variance'], 2) }}</strong></td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    {{-- SYSTEM CALCULATION --}}

    <div class="card mb-4">

        <div class="card-header">
            <h5 class="mb-0">
                PENERP System Calculation vs Uploaded Schedule
            </h5>
        </div>

        <div class="card-body">

            <div class="alert alert-info">
                Variance = PENERP system-calculated expected contribution less the amount supplied on the employer contribution schedule.
            </div>


            <div class="table-responsive">

                <table class="table table-bordered align-middle mb-0">

                    <thead class="table-light">

                        <tr>
                            <th>Description</th>
                            <th class="text-end">System Calculated</th>
                            <th class="text-end">Uploaded Schedule</th>
                            <th class="text-end">Variance</th>
                        </tr>

                    </thead>

                    <tbody>

                        <tr>
                            <th>Employee Contributions</th>
                            <td class="text-end">{{ $currency }} {{ number_format($calculation['employee_contribution'], 2) }}</td>
                            <td class="text-end">{{ $currency }} {{ number_format($schedule['employee_contribution'], 2) }}</td>
                            <td class="text-end {{ abs($calculation['employee_variance']) > 0.01 ? 'text-danger' : 'text-success' }}"><strong>{{ $currency }} {{ number_format($calculation['employee_variance'], 2) }}</strong></td>
                        </tr>

                        <tr>
                            <th>Employer Contributions</th>
                            <td class="text-end">{{ $currency }} {{ number_format($calculation['employer_contribution'], 2) }}</td>
                            <td class="text-end">{{ $currency }} {{ number_format($schedule['employer_contribution'], 2) }}</td>
                            <td class="text-end {{ abs($calculation['employer_variance']) > 0.01 ? 'text-danger' : 'text-success' }}"><strong>{{ $currency }} {{ number_format($calculation['employer_variance'], 2) }}</strong></td>
                        </tr>

                        <tr>
                            <th>Employee AVC</th>
                            <td class="text-end">{{ $currency }} {{ number_format($calculation['employee_avc'], 2) }}</td>
                            <td class="text-end">{{ $currency }} {{ number_format($schedule['employee_avc'], 2) }}</td>
                            <td class="text-end">{{ $currency }} 0.00</td>
                        </tr>

                        <tr>
                            <th>Employer AVC</th>
                            <td class="text-end">{{ $currency }} {{ number_format($calculation['employer_avc'], 2) }}</td>
                            <td class="text-end">{{ $currency }} {{ number_format($schedule['employer_avc'], 2) }}</td>
                            <td class="text-end">{{ $currency }} 0.00</td>
                        </tr>

                        <tr class="{{ abs($calculation['variance']) > 0.01 ? 'table-warning' : 'table-success' }}">
                            <th>GRAND TOTAL</th>
                            <td class="text-end"><strong>{{ $currency }} {{ number_format($calculation['total_expected'], 2) }}</strong></td>
                            <td class="text-end"><strong>{{ $currency }} {{ number_format($schedule['total_expected'], 2) }}</strong></td>
                            <td class="text-end"><strong>{{ $currency }} {{ number_format($calculation['variance'], 2) }}</strong></td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    {{-- MEMBER DETAIL --}}

    <div class="card">

        <div class="card-header">
            <h5 class="mb-0">
                Member Contribution Calculations
            </h5>
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table id="reconciliationMemberTable" class="table table-bordered table-striped table-hover align-middle w-100">

                    <thead>

                        <tr>
                            <th>Row</th>
                            <th>Member</th>
                            <th>Type</th>
                            <th>Basic Pay</th>
                            <th>Employee Rate Uploaded</th>
                            <th>Employee Rate Expected</th>
                            <th>Employee Schedule</th>
                            <th>Employee System</th>
                            <th>Employee Variance</th>
                            <th>Employer Rate Uploaded</th>
                            <th>Employer Rate Expected</th>
                            <th>Employer Schedule</th>
                            <th>Employer System</th>
                            <th>Employer Variance</th>
                            <th>Employee AVC</th>
                            <th>Employer AVC</th>
                            <th>Schedule Total</th>
                            <th>System Total</th>
                            <th>Total Variance</th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach($report['calculation_rows'] as $row)

                            <tr>

                                <td>{{ $row['row_number'] }}</td>

                                <td>
                                    <strong>{{ $row['member_name'] }}</strong>
                                    <div class="small text-muted">PENERP: {{ $row['penerp_member_number'] ?: '-' }}</div>
                                    <div class="small text-muted">Staff: {{ $row['staff_number'] ?: '-' }}</div>
                                </td>

                                <td>{{ $row['member_type'] }}</td>

                                <td class="text-end">{{ number_format($row['basic_pay'], 2) }}</td>

                                <td class="text-end">{{ number_format($row['employee_rate_uploaded'], 2) }}%</td>

                                <td class="text-end">{{ number_format($row['employee_rate_expected'], 2) }}%</td>

                                <td class="text-end">{{ number_format($row['employee_schedule'], 2) }}</td>

                                <td class="text-end">{{ number_format($row['employee_system'], 2) }}</td>

                                <td class="text-end {{ abs($row['employee_variance']) > 0.01 ? 'text-danger fw-bold' : '' }}">{{ number_format($row['employee_variance'], 2) }}</td>

                                <td class="text-end">{{ number_format($row['employer_rate_uploaded'], 2) }}%</td>

                                <td class="text-end">17.30%</td>

                                <td class="text-end">{{ number_format($row['employer_schedule'], 2) }}</td>

                                <td class="text-end">{{ number_format($row['employer_system'], 2) }}</td>

                                <td class="text-end {{ abs($row['employer_variance']) > 0.01 ? 'text-danger fw-bold' : '' }}">{{ number_format($row['employer_variance'], 2) }}</td>

                                <td class="text-end">{{ number_format($row['employee_avc'], 2) }}</td>

                                <td class="text-end">{{ number_format($row['employer_avc'], 2) }}</td>

                                <td class="text-end">{{ number_format($row['schedule_total'], 2) }}</td>

                                <td class="text-end">{{ number_format($row['system_total'], 2) }}</td>

                                <td class="text-end {{ abs($row['variance']) > 0.01 ? 'text-danger fw-bold' : 'text-success' }}">{{ number_format($row['variance'], 2) }}</td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

@endsection


@push('scripts')

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        if (
            typeof $
            ===
            'undefined'
            ||
            !$.fn.DataTable
        ) {
            return;
        }


        if (
            $.fn.DataTable.isDataTable(
                '#reconciliationMemberTable'
            )
        ) {
            $('#reconciliationMemberTable')
                .DataTable()
                .destroy();
        }


        $('#reconciliationMemberTable')
            .DataTable({
                pageLength:
                    25,

                lengthMenu:
                    [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, 'All']
                    ],

                order:
                    [
                        [0, 'asc']
                    ],

                scrollX:
                    true,

                dom:
                    'Bfrtip',

                buttons: [
                    'copy',
                    'excel',
                    'csv',
                    'print'
                ]
            });

    }
);

</script>

@endpush
@extends('layouts.app')

@section('title', 'Contribution Exceptions')

@section('content')

<div class="container-fluid">

    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="mb-1">Contribution Rate / Calculation Exceptions</h4>
                    <p class="text-muted mb-0">Batch #{{ $batch->id }} - {{ $batch->employer?->name ?? 'Employer' }}</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('pensions-administration.contributions.imports.review', $batch) }}" class="btn btn-outline-secondary"><i class="mdi mdi-arrow-left me-1"></i> Back to Review</a>

                    @can('contributions.reports.view')
                    <a href="{{ route('pensions-administration.contributions.imports.exceptions.excel', $batch) }}" class="btn btn-success"><i class="mdi mdi-microsoft-excel me-1"></i> Export Excel</a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">Batch</small>
                    <h4 class="mb-0">#{{ $batch->id }}</h4>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">Currency</small>
                    <h4 class="mb-0">{{ strtoupper($batch->currency_code ?? 'ZWG') }}</h4>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">Exception Rows</small>
                    <h4 class="mb-0">{{ count($exceptions) }}</h4>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">Batch Warnings</small>
                    <h4 class="mb-0">{{ number_format((int) $batch->warning_rows) }}</h4>
                </div>
            </div>
        </div>

    </div>

    <div class="alert alert-warning">
        <div class="d-flex">
            <i class="mdi mdi-alert-outline font-size-24 me-2"></i>

            <div>
                <strong>Approval Warning</strong>
                <div>These are contribution rate or calculation exceptions. They do not automatically prevent approval, but the approver must review them before approving the batch.</div>
            </div>
        </div>
    </div>

    <div class="card">

        <div class="card-header">
            <h5 class="mb-0">Rate and Contribution Exceptions</h5>
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table id="contributionExceptionTable" class="table table-bordered table-striped table-hover align-middle w-100">

                    <thead>
                        <tr>
                            <th>Excel Row</th>
                            <th>Member</th>
                            <th>Member Type</th>
                            <th>Basic Pay</th>
                            <th>Employee Rate</th>
                            <th>Expected Employee Rate</th>
                            <th>Employee Schedule</th>
                            <th>Employee System</th>
                            <th>Employee Variance</th>
                            <th>Employer Rate</th>
                            <th>Expected Employer Rate</th>
                            <th>Employer Schedule</th>
                            <th>Employer System</th>
                            <th>Employer Variance</th>
                            <th>Warnings</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($exceptions as $exception)

                            @php
                                $employeeVariance = (float) $exception['employee_variance'];
                                $employerVariance = (float) $exception['employer_variance'];
                                $currency = strtoupper($batch->currency_code ?? 'ZWG');
                            @endphp

                            <tr>

                                <td>{{ $exception['row_number'] }}</td>

                                <td>
                                    <strong>{{ $exception['member_name'] }}</strong>

                                    <div class="small text-muted">
                                        @if(filled($exception['penerp_member_number']))
                                            PENERP: {{ $exception['penerp_member_number'] }}
                                        @endif
                                    </div>

                                    <div class="small text-muted">
                                        @if(filled($exception['staff_number']))
                                            Staff: {{ $exception['staff_number'] }}
                                        @endif
                                    </div>

                                    <div class="small text-muted">
                                        @if(filled($exception['national_id']))
                                            ID: {{ $exception['national_id'] }}
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    @if($exception['member_type'] === 'Proposed New Member')
                                        <span class="badge bg-info">Proposed New Member</span>
                                    @else
                                        <span class="badge bg-primary">Existing Member</span>
                                    @endif
                                </td>

                                <td class="text-end">{{ $currency }} {{ number_format((float) $exception['basic_pay'], 2) }}</td>

                                <td class="text-end">{{ number_format((float) $exception['employee_rate_uploaded'], 2) }}%</td>

                                <td class="text-end">{{ number_format((float) $exception['employee_rate_expected'], 2) }}%</td>

                                <td class="text-end">{{ $currency }} {{ number_format((float) $exception['employee_contribution_uploaded'], 2) }}</td>

                                <td class="text-end">{{ $currency }} {{ number_format((float) $exception['employee_contribution_calculated'], 2) }}</td>

                                <td class="text-end fw-bold {{ abs($employeeVariance) > 0.01 ? 'text-danger' : 'text-success' }}">{{ $currency }} {{ number_format($employeeVariance, 2) }}</td>

                                <td class="text-end">{{ number_format((float) $exception['employer_rate_uploaded'], 2) }}%</td>

                                <td class="text-end">{{ number_format((float) $exception['employer_rate_expected'], 2) }}%</td>

                                <td class="text-end">{{ $currency }} {{ number_format((float) $exception['employer_contribution_uploaded'], 2) }}</td>

                                <td class="text-end">{{ $currency }} {{ number_format((float) $exception['employer_contribution_calculated'], 2) }}</td>

                                <td class="text-end fw-bold {{ abs($employerVariance) > 0.01 ? 'text-danger' : 'text-success' }}">{{ $currency }} {{ number_format($employerVariance, 2) }}</td>

                                <td style="min-width:350px;">
                                    @foreach($exception['warnings'] as $warning)
                                        <div class="alert alert-warning py-1 px-2 mb-1 small">{{ $warning }}</div>
                                    @endforeach
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="15" class="text-center py-5">
                                    <i class="mdi mdi-check-circle-outline text-success font-size-36"></i>
                                    <h5 class="mt-2">No Rate or Calculation Exceptions</h5>
                                    <p class="text-muted mb-0">The system did not identify any employee/employer rate or contribution calculation discrepancies in this batch.</p>
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

@endsection

@push('scripts')

@if(count($exceptions) > 0)

<script>
document.addEventListener('DOMContentLoaded', function () {
    if ($.fn.DataTable.isDataTable('#contributionExceptionTable')) {
        $('#contributionExceptionTable').DataTable().destroy();
    }

    $('#contributionExceptionTable').DataTable({
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[0, 'asc']],
        responsive: false,
        scrollX: true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'copy',
                title: 'Contribution Rate and Calculation Exceptions'
            },
            {
                extend: 'excelHtml5',
                title: 'Contribution Rate and Calculation Exceptions'
            },
            {
                extend: 'csvHtml5',
                title: 'Contribution Rate and Calculation Exceptions'
            },
            {
                extend: 'print',
                title: 'Contribution Rate and Calculation Exceptions'
            }
        ]
    });
});
</script>

@endif

@endpush
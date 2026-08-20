@extends('layouts.app')

@section('title', 'Contribution Validation Exceptions')
@section('page-heading', 'Contribution Validation Exceptions')

@section('content')

@include('pensions-administration.partials.navigation')


<div class="container-fluid">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>

            <h4 class="mb-1">
                Rate / Contribution Exceptions
            </h4>

            <p class="text-muted mb-0">
                {{ $batch->employer?->name ?? '-' }}
                —
                {{ $batch->contributionPeriod?->period_label ?? '-' }}
                —
                {{ $summary['currency'] ?? 'ZWG' }}
            </p>

        </div>


        <div class="d-flex flex-wrap gap-2">

            <a href="{{ route('pensions-administration.contributions.imports.review', $batch) }}" class="btn btn-light">
                <i class="mdi mdi-arrow-left me-1"></i>
                Back to Review
            </a>

            <a href="{{ route('pensions-administration.contributions.imports.exceptions.excel', $batch) }}" class="btn btn-success">
                <i class="mdi mdi-microsoft-excel me-1"></i>
                Export Excel
            </a>

        </div>

    </div>


    <div class="alert {{ ($summary['error_rows'] ?? 0) > 0 ? 'alert-danger' : 'alert-warning' }}">

        <div class="d-flex align-items-start">

            <i class="mdi mdi-alert-outline font-size-24 me-2"></i>

            <div>

                <strong>
                    Contribution Validation Summary
                </strong>

                <div class="mt-2">

                    Error rows:
                    <strong>{{ number_format($summary['error_rows'] ?? 0) }}</strong>

                    <span class="mx-2">|</span>

                    Warning rows:
                    <strong>{{ number_format($summary['batch_warning_rows'] ?? 0) }}</strong>

                    <span class="mx-2">|</span>

                    Contribution exceptions:
                    <strong>{{ number_format($summary['warning_rows'] ?? 0) }}</strong>

                </div>


                @if(($summary['error_rows'] ?? 0) > 0)

                    <div class="mt-2">
                        This batch cannot be approved until all blocking errors have been corrected.
                    </div>

                @else

                    <div class="mt-2">
                        These warnings do not prevent approval. They are presented so the approver can review contribution rates and calculated contribution amounts before approving.
                    </div>

                @endif

            </div>

        </div>

    </div>


    <div class="card">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead>

                        <tr>
                            <th>Row</th>
                            <th>PENERP No.</th>
                            <th>PenAd No.</th>
                            <th>Staff No.</th>
                            <th>Member</th>
                            <th class="text-end">Basic Pay</th>
                            <th class="text-end">Employee Rate</th>
                            <th class="text-end">Employer Rate</th>
                            <th class="text-end">Employee Contribution</th>
                            <th class="text-end">Employer Contribution</th>
                            <th>Warnings</th>
                        </tr>

                    </thead>


                    <tbody>

                        @forelse($rows as $row)

                            @php

                                $data =
                                    $row->normalized_data
                                    ??
                                    [];

                                $currency =
                                    strtolower(
                                        $summary['currency']
                                        ??
                                        'ZWG'
                                    );

                                $basicPay =
                                    (float) (
                                        $data[
                                            $currency
                                            . '_basic_pay'
                                        ]
                                        ??
                                        $data['basic_pay']
                                        ??
                                        0
                                    );

                                $employeeRate =
                                    (float) (
                                        $data[
                                            $currency
                                            . '_employee_rate'
                                        ]
                                        ??
                                        $data['employee_rate']
                                        ??
                                        0
                                    );

                                $employerRate =
                                    (float) (
                                        $data[
                                            $currency
                                            . '_employer_rate'
                                        ]
                                        ??
                                        $data['employer_rate']
                                        ??
                                        0
                                    );

                                if (
                                    $employeeRate > 0
                                    &&
                                    $employeeRate <= 1
                                ) {
                                    $employeeRate *= 100;
                                }

                                if (
                                    $employerRate > 0
                                    &&
                                    $employerRate <= 1
                                ) {
                                    $employerRate *= 100;
                                }

                                $employeeContribution =
                                    (float) (
                                        $data[
                                            $currency
                                            . '_employee_contribution'
                                        ]
                                        ??
                                        $data['employee_contribution']
                                        ??
                                        0
                                    );

                                $employerContribution =
                                    (float) (
                                        $data[
                                            $currency
                                            . '_employer_contribution'
                                        ]
                                        ??
                                        $data['employer_contribution']
                                        ??
                                        0
                                    );

                            @endphp


                            <tr>

                                <td>
                                    {{ $row->row_number }}
                                </td>

                                <td>
                                    {{ $row->matchedMember?->member_number ?? $data['penerp_member_number'] ?? '-' }}
                                </td>

                                <td>
                                    {{ $row->matchedMember?->penad_member_number ?? $data['penad_member_number'] ?? '-' }}
                                </td>

                                <td>
                                    {{ $data['staff_number'] ?? '-' }}
                                </td>

                                <td>

                                    <strong>
                                        {{ $row->matchedMember?->surname ?? $data['surname'] ?? '' }}
                                        {{ $row->matchedMember?->first_names ?? $data['first_names'] ?? '' }}
                                    </strong>

                                    @if($row->is_new_member)

                                        <br>

                                        <span class="badge bg-info">
                                            Proposed New Member
                                        </span>

                                    @endif

                                </td>

                                <td class="text-end">
                                    {{ strtoupper($currency) }}
                                    {{ number_format($basicPay, 2) }}
                                </td>

                                <td class="text-end">

                                    {{ number_format($employeeRate, 2) }}%

                                    <br>

                                    <small class="text-muted">
                                        {{ $row->is_new_member ? 'Expected 6.00%' : 'Expected 5.00% - 6.00%' }}
                                    </small>

                                </td>

                                <td class="text-end">

                                    {{ number_format($employerRate, 2) }}%

                                    <br>

                                    <small class="text-muted">
                                        Expected 17.30%
                                    </small>

                                </td>

                                <td class="text-end">
                                    {{ strtoupper($currency) }}
                                    {{ number_format($employeeContribution, 2) }}
                                </td>

                                <td class="text-end">
                                    {{ strtoupper($currency) }}
                                    {{ number_format($employerContribution, 2) }}
                                </td>

                                <td>

                                    @foreach($row->warning_messages ?? [] as $warning)

                                        <div class="text-warning small mb-1">
                                            <i class="mdi mdi-alert-outline me-1"></i>
                                            {{ $warning }}
                                        </div>

                                    @endforeach

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="11" class="text-center text-muted py-4">
                                    No rate or contribution exceptions were found for this batch.
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
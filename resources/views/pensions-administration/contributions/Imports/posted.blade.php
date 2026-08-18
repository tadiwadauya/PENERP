@extends('layouts.app')

@section('title', 'Posted Monthly Contributions')
@section('page-heading', 'Posted Monthly Contributions')
@section('page-subheading', 'Permanent expected member contribution transactions')

@section('content')

@include('pensions-administration.partials.navigation')

@if(session('success'))

    <div class="alert alert-success alert-dismissible fade show">
        <i class="mdi mdi-check-circle-outline me-1"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

@endif

@if(session('error'))

    <div class="alert alert-danger alert-dismissible fade show">
        <i class="mdi mdi-alert-circle-outline me-1"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

@endif


{{-- =========================================================
     BATCH HEADER
========================================================= --}}

<div class="card">

    <div class="card-body">

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">

            <div>

                <h4 class="header-title mb-2">
                    {{ $batch->employer?->name ?? '-' }}
                </h4>

                <p class="text-muted mb-1">
                    Contribution Period:
                    <strong>{{ $batch->contributionPeriod?->period_label ?? '-' }}</strong>
                </p>

                <p class="text-muted mb-1">
                    Import Batch:
                    <strong>#{{ $batch->id }}</strong>
                </p>

                <p class="text-muted mb-1">
                    Source File:
                    {{ $batch->original_filename ?? '-' }}
                </p>

                <p class="text-muted mb-0">
                    Status:
                    <span class="badge bg-success">Posted</span>
                </p>

            </div>


            <div class="d-flex flex-wrap gap-2">

                <a href="{{ route('pensions-administration.contributions.imports.show', $batch) }}" class="btn btn-light">
                    <i class="mdi mdi-arrow-left me-1"></i>
                    Batch Summary
                </a>

                <a href="{{ route('pensions-administration.contributions.imports.review', $batch) }}" class="btn btn-outline-primary">
                    <i class="mdi mdi-file-search-outline me-1"></i>
                    Review
                </a>


                @can('contributions.reports.view')

                    <a href="{{ route('pensions-administration.contributions.reconciliation.show', $batch) }}" class="btn btn-outline-primary">
                        <i class="mdi mdi-scale-balance me-1"></i>
                        Reconciliation
                    </a>

                    <a href="{{ route('pensions-administration.contributions.reconciliation.pdf', $batch) }}" target="_blank" class="btn btn-danger">
                        <i class="mdi mdi-file-pdf-box me-1"></i>
                        PDF
                    </a>

                    <a href="{{ route('pensions-administration.contributions.reconciliation.excel', $batch) }}" class="btn btn-success">
                        <i class="mdi mdi-microsoft-excel me-1"></i>
                        Excel
                    </a>

                @endcan

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     POSTING STATISTICS
========================================================= --}}

<div class="row">

    <div class="col-xl col-lg-4 col-md-6">

        <div class="card h-100">

            <div class="card-body text-center">

                <p class="text-muted mb-1">
                    Posted Members
                </p>

                <h3 class="mb-0">
                    {{ number_format($summary['posted_rows'] ?? 0) }}
                </h3>

            </div>

        </div>

    </div>


    <div class="col-xl col-lg-4 col-md-6">

        <div class="card h-100">

            <div class="card-body text-center">

                <p class="text-muted mb-1">
                    Reinstatements
                </p>

                <h3 class="mb-0 text-success">
                    {{ number_format($summary['reinstatements'] ?? 0) }}
                </h3>

                @if(filled($summary['previous_period'] ?? null))

                    <small class="text-muted">
                        Nil in {{ $summary['previous_period'] }}
                    </small>

                @endif

            </div>

        </div>

    </div>


    <div class="col-xl col-lg-4 col-md-6">

        <div class="card h-100">

            <div class="card-body text-center">

                <p class="text-muted mb-1">
                    Negative Adjustments
                </p>

                <h3 class="mb-0 text-warning">
                    {{ number_format($summary['adjustments'] ?? 0) }}
                </h3>

            </div>

        </div>

    </div>


    <div class="col-xl col-lg-4 col-md-6">

        <div class="card h-100">

            <div class="card-body text-center">

                <p class="text-muted mb-1">
                    Approved By
                </p>

                <h5 class="mb-1">
                    {{ $batch->approvedBy?->full_name ?? '-' }}
                </h5>

                <small class="text-muted">
                    {{ $batch->approved_at?->format('d M Y H:i') ?? '-' }}
                </small>

            </div>

        </div>

    </div>


    <div class="col-xl col-lg-4 col-md-6">

        <div class="card h-100">

            <div class="card-body text-center">

                <p class="text-muted mb-1">
                    Posted By
                </p>

                <h5 class="mb-1">
                    {{ $batch->postedBy?->full_name ?? '-' }}
                </h5>

                <small class="text-muted">
                    {{ $batch->posted_at?->format('d M Y H:i') ?? '-' }}
                </small>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     CURRENCY TOTALS
========================================================= --}}

<div class="row">


    {{-- ZWG --}}

    <div class="col-xl-6">

        <div class="card h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <div>

                        <h4 class="header-title mb-1">
                            ZWG Contribution Totals
                        </h4>

                        <p class="text-muted mb-0">
                            Zimbabwe Gold
                        </p>

                    </div>

                    <span class="badge bg-primary">
                        Base Currency
                    </span>

                </div>


                <div class="table-responsive">

                    <table class="table table-bordered mb-0">

                        <tbody>

                            <tr>

                                <th style="width:55%;">
                                    Basic Pay
                                </th>

                                <td class="text-end">
                                    ZWG {{ number_format($summary['zwg_basic_pay'] ?? 0, 2) }}
                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Employee Contribution
                                </th>

                                <td class="text-end">
                                    ZWG {{ number_format($summary['zwg_employee'] ?? 0, 2) }}
                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Employer Contribution
                                </th>

                                <td class="text-end">
                                    ZWG {{ number_format($summary['zwg_employer'] ?? 0, 2) }}
                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Employee AVC
                                </th>

                                <td class="text-end">
                                    ZWG {{ number_format($summary['zwg_employee_avc'] ?? 0, 2) }}
                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Employer AVC
                                </th>

                                <td class="text-end">
                                    ZWG {{ number_format($summary['zwg_employer_avc'] ?? 0, 2) }}
                                </td>

                            </tr>


                            <tr class="table-light">

                                <th>
                                    Total Expected Contributions
                                </th>

                                <th class="text-end">

                                    ZWG

                                    {{
                                        number_format(
                                            ($summary['zwg_employee'] ?? 0)
                                            +
                                            ($summary['zwg_employer'] ?? 0)
                                            +
                                            ($summary['zwg_employee_avc'] ?? 0)
                                            +
                                            ($summary['zwg_employer_avc'] ?? 0),
                                            2
                                        )
                                    }}

                                </th>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>


    {{-- USD --}}

    <div class="col-xl-6">

        <div class="card h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <div>

                        <h4 class="header-title mb-1">
                            USD Contribution Totals
                        </h4>

                        <p class="text-muted mb-0">
                            United States Dollar
                        </p>

                    </div>

                    <span class="badge bg-success">
                        Foreign Currency
                    </span>

                </div>


                <div class="table-responsive">

                    <table class="table table-bordered mb-0">

                        <tbody>

                            <tr>

                                <th style="width:55%;">
                                    Basic Pay
                                </th>

                                <td class="text-end">
                                    USD {{ number_format($summary['usd_basic_pay'] ?? 0, 2) }}
                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Employee Contribution
                                </th>

                                <td class="text-end">
                                    USD {{ number_format($summary['usd_employee'] ?? 0, 2) }}
                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Employer Contribution
                                </th>

                                <td class="text-end">
                                    USD {{ number_format($summary['usd_employer'] ?? 0, 2) }}
                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Employee AVC
                                </th>

                                <td class="text-end">
                                    USD {{ number_format($summary['usd_employee_avc'] ?? 0, 2) }}
                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Employer AVC
                                </th>

                                <td class="text-end">
                                    USD {{ number_format($summary['usd_employer_avc'] ?? 0, 2) }}
                                </td>

                            </tr>


                            <tr class="table-light">

                                <th>
                                    Total Expected Contributions
                                </th>

                                <th class="text-end">

                                    USD

                                    {{
                                        number_format(
                                            ($summary['usd_employee'] ?? 0)
                                            +
                                            ($summary['usd_employer'] ?? 0)
                                            +
                                            ($summary['usd_employee_avc'] ?? 0)
                                            +
                                            ($summary['usd_employer_avc'] ?? 0),
                                            2
                                        )
                                    }}

                                </th>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>


<div class="alert alert-light border mt-3">

    <i class="mdi mdi-information-outline me-1"></i>

    ZWG and USD totals are shown separately. These are expected contributions due and are not employer cash receipts.

</div>


{{-- =========================================================
     POSTED CONTRIBUTION TABLE
========================================================= --}}

<div class="card">

    <div class="card-body">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">

            <div>

                <h4 class="header-title mb-1">
                    Posted Member Contributions
                </h4>

                <p class="text-muted mb-0">
                    Permanent expected contribution records created from this batch.
                </p>

            </div>

            <span class="badge bg-success font-size-14">
                {{ number_format($summary['posted_rows'] ?? 0) }} Posted
            </span>

        </div>


        <div class="table-responsive">

            <table class="table table-bordered table-hover table-nowrap align-middle">

                <thead>

                    <tr>

                        <th>Row</th>
                        <th>PENERP No.</th>
                        <th>PenAd No.</th>
                        <th>Staff No.</th>
                        <th>Member</th>
                        <th>Type</th>

                        <th class="text-end">USD Salary</th>
                        <th class="text-end">USD EE</th>
                        <th class="text-end">USD ER</th>
                        <th class="text-end">USD EE AVC</th>
                        <th class="text-end">USD ER AVC</th>

                        <th class="text-end">ZWG Salary</th>
                        <th class="text-end">ZWG EE</th>
                        <th class="text-end">ZWG ER</th>
                        <th class="text-end">ZWG EE AVC</th>
                        <th class="text-end">ZWG ER AVC</th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($contributions as $contribution)

                        <tr>

                            <td>
                                {{ $contribution->source_row_number ?? '-' }}
                            </td>


                            <td>

                                @if($contribution->member_id)

                                    <a href="{{ route('pensions-administration.updates.members.show', $contribution->member_id) }}">
                                        {{ $contribution->penerp_member_number ?? '-' }}
                                    </a>

                                @else

                                    {{ $contribution->penerp_member_number ?? '-' }}

                                @endif

                            </td>


                            <td>
                                {{ $contribution->penad_member_number ?? '-' }}
                            </td>


                            <td>
                                {{ $contribution->staff_number ?? '-' }}
                            </td>


                            <td>

                                <strong>

                                    {{ $contribution->member?->surname ?? '' }},
                                    {{ $contribution->member?->first_names ?? '' }}

                                </strong>

                            </td>


                            <td>

                                @if($contribution->transaction_type === 'adjustment')

                                    <span class="badge bg-warning text-dark">
                                        Adjustment
                                    </span>

                                @else

                                    <span class="badge bg-success">
                                        Expected
                                    </span>

                                @endif

                            </td>


                            <td class="text-end">
                                {{ number_format((float) ($contribution->usd_basic_pay ?? 0), 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format((float) ($contribution->usd_employee_contribution ?? 0), 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format((float) ($contribution->usd_employer_contribution ?? 0), 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format((float) ($contribution->usd_employee_avc ?? 0), 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format((float) ($contribution->usd_employer_avc ?? 0), 2) }}
                            </td>


                            <td class="text-end">
                                {{ number_format((float) ($contribution->zwg_basic_pay ?? 0), 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format((float) ($contribution->zwg_employee_contribution ?? 0), 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format((float) ($contribution->zwg_employer_contribution ?? 0), 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format((float) ($contribution->zwg_employee_avc ?? 0), 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format((float) ($contribution->zwg_employer_avc ?? 0), 2) }}
                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="16" class="text-center text-muted py-4">
                                No contribution transactions have been posted for this batch.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if(method_exists($contributions, 'links'))

            <div class="mt-3">
                {{ $contributions->links() }}
            </div>

        @endif

    </div>

</div>

@endsection
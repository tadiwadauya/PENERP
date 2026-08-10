@extends('layouts.app')

@section('title', 'Membership Reports')

@section('page-heading', 'Membership Reports')

@section('page-subheading')
Static membership information, statistics and management reports
@endsection


@push('styles')

<link rel="stylesheet"
      href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<link rel="stylesheet"
      href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<style>
    .report-filter-card {
        border-left: 4px solid #0d6efd;
    }

    .report-stat-card {
        border: 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
        height: 100%;
    }

    .report-stat-card .card-body {
        min-height: 100px;
    }

    .report-tabs {
        display: flex;
        flex-wrap: nowrap;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: thin;
    }

    .report-tabs .nav-item {
        flex: 0 0 auto;
    }

    .report-tabs .nav-link {
        white-space: nowrap;
        font-weight: 500;
    }

    .report-tabs .nav-link.active {
        font-weight: 600;
    }

    .report-section-title {
        font-weight: 600;
        margin-bottom: 4px;
    }

    .statistics-card {
        height: 100%;
        border: 1px solid #e9ecef;
        box-shadow: none;
    }

    .statistics-card .card-header {
        background: #f8f9fa;
        font-weight: 600;
    }

    .statistics-main-value {
        font-size: 24px;
        font-weight: 600;
    }

    .statistics-small {
        font-size: 12px;
        color: #74788d;
    }

    .exception-badge {
        margin-right: 4px;
        margin-bottom: 4px;
    }

    .table-total-row {
        font-weight: 600;
        background: #f8f9fa;
    }

    .report-table th {
        white-space: nowrap;
    }

    .report-table td {
        vertical-align: middle;
    }

    .dt-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .dt-buttons .btn {
        margin: 0 !important;
    }

    .dataTables_filter {
        text-align: right;
    }

    @media(max-width: 767px) {
        .dataTables_filter {
            text-align: left;
            margin-top: 10px;
        }
    }
</style>

@endpush


@section('content')

@include('pensions-administration.partials.navigation')


@php
    $statisticsTotal = max(1, $summary['total']);

    $maleCount = $genderSummary
        ->firstWhere('gender', 'Male')
        ?->total ?? 0;

    $femaleCount = $genderSummary
        ->firstWhere('gender', 'Female')
        ?->total ?? 0;

    $genderUnknownCount = $genderSummary
        ->filter(function ($gender) {
            return !in_array(
                $gender->gender,
                ['Male', 'Female'],
                true
            );
        })
        ->sum('total');

    $withNationalId =
        $summary['total']
        - $summary['without_national_id'];

    $withEmployer =
        $summary['total']
        - $summary['without_employer'];

    $withPenad =
        $summary['total']
        - $summary['without_penad_number'];

    $withFundworx =
        $summary['total']
        - $summary['without_fundworx_number'];
@endphp


{{-- =========================================================
     FILTERS
========================================================= --}}

<div class="card report-filter-card">

    <div class="card-body">

        <div class="mb-3">
            <h4 class="header-title mb-1">
                Membership Report Filters
            </h4>

            <p class="text-muted mb-0">
                Filters apply across all membership reports and statistics below.
            </p>
        </div>


        <form method="GET"
              action="{{ route('pensions-administration.updates.reports.membership.index') }}">

            <div class="row">

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


                <div class="col-xl-2 col-lg-3 col-md-6">
                    <div class="mb-3">

                        <label class="form-label">
                            PENERP Number
                        </label>

                        <input type="text"
                               name="penerp_member_number"
                               class="form-control"
                               value="{{ request('penerp_member_number') }}"
                               placeholder="PENERP no.">

                    </div>
                </div>


                <div class="col-xl-2 col-lg-3 col-md-6">
                    <div class="mb-3">

                        <label class="form-label">
                            PenAd Number
                        </label>

                        <input type="text"
                               name="penad_member_number"
                               class="form-control"
                               value="{{ request('penad_member_number') }}"
                               placeholder="PenAd no.">

                    </div>
                </div>


                <div class="col-xl-2 col-lg-3 col-md-6">
                    <div class="mb-3">

                        <label class="form-label">
                            Fundworx Number
                        </label>

                        <input type="text"
                               name="fundworx_member_number"
                               class="form-control"
                               value="{{ request('fundworx_member_number') }}"
                               placeholder="Fundworx no.">

                    </div>
                </div>


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


                <div class="col-xl-4 col-lg-6 col-md-6">
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
                                    @selected(request('employer_id') == $employer->id)>

                                    {{ $employer->employer_number }}
                                    -
                                    {{ $employer->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>
                </div>


                <div class="col-xl-2 col-lg-3 col-md-6">
                    <div class="mb-3">

                        <label class="form-label">
                            Gender
                        </label>

                        <select name="gender"
                                class="form-select">

                            <option value="">
                                All Genders
                            </option>

                            <option value="Male"
                                @selected(request('gender') === 'Male')>
                                Male
                            </option>

                            <option value="Female"
                                @selected(request('gender') === 'Female')>
                                Female
                            </option>

                        </select>

                    </div>
                </div>


                <div class="col-xl-2 col-lg-3 col-md-6">
                    <div class="mb-3">

                        <label class="form-label">
                            Joined From
                        </label>

                        <input type="date"
                               name="joined_from"
                               class="form-control"
                               value="{{ request('joined_from') }}">

                    </div>
                </div>


                <div class="col-xl-2 col-lg-3 col-md-6">
                    <div class="mb-3">

                        <label class="form-label">
                            Joined To
                        </label>

                        <input type="date"
                               name="joined_to"
                               class="form-control"
                               value="{{ request('joined_to') }}">

                    </div>
                </div>


                <div class="col-xl-4 col-lg-6 col-md-12">
                    <div class="mb-3">

                        <label class="form-label d-block">
                            &nbsp;
                        </label>

                        <button type="submit"
                                class="btn btn-primary">

                            <i class="mdi mdi-filter-outline me-1"></i>
                            Apply Filters

                        </button>

                        <a href="{{ route('pensions-administration.updates.reports.membership.index') }}"
                           class="btn btn-light">

                            <i class="mdi mdi-filter-remove-outline me-1"></i>
                            Clear Filters

                        </a>

                    </div>
                </div>

            </div>

        </form>

    </div>

</div>


{{-- =========================================================
     TOP SUMMARY
========================================================= --}}

<div class="row g-3 mb-3">

    <div class="col-xl-2 col-lg-3 col-md-4 col-6">
        <div class="card report-stat-card">

            <div class="card-body">
                <p class="text-muted mb-1">
                    Total Members
                </p>

                <h3 class="mb-0">
                    {{ number_format($summary['total']) }}
                </h3>
            </div>

        </div>
    </div>


    <div class="col-xl-2 col-lg-3 col-md-4 col-6">
        <div class="card report-stat-card">

            <div class="card-body">
                <p class="text-muted mb-1">
                    Active
                </p>

                <h3 class="mb-0 text-success">
                    {{ number_format($summary['active']) }}
                </h3>
            </div>

        </div>
    </div>


    <div class="col-xl-2 col-lg-3 col-md-4 col-6">
        <div class="card report-stat-card">

            <div class="card-body">
                <p class="text-muted mb-1">
                    Dormant
                </p>

                <h3 class="mb-0 text-warning">
                    {{ number_format($summary['dormant']) }}
                </h3>
            </div>

        </div>
    </div>


    <div class="col-xl-2 col-lg-3 col-md-4 col-6">
        <div class="card report-stat-card">

            <div class="card-body">
                <p class="text-muted mb-1">
                    Inactive
                </p>

                <h3 class="mb-0 text-secondary">
                    {{ number_format($summary['inactive']) }}
                </h3>
            </div>

        </div>
    </div>


    <div class="col-xl-2 col-lg-3 col-md-4 col-6">
        <div class="card report-stat-card">

            <div class="card-body">
                <p class="text-muted mb-1">
                    Missing National ID
                </p>

                <h3 class="mb-0 text-danger">
                    {{ number_format($summary['without_national_id']) }}
                </h3>
            </div>

        </div>
    </div>


    <div class="col-xl-2 col-lg-3 col-md-4 col-6">
        <div class="card report-stat-card">

            <div class="card-body">
                <p class="text-muted mb-1">
                    Missing Employer
                </p>

                <h3 class="mb-0 text-danger">
                    {{ number_format($summary['without_employer']) }}
                </h3>
            </div>

        </div>
    </div>

</div>


{{-- =========================================================
     REPORTS
========================================================= --}}

<div class="card">

    <div class="card-body">

        <ul class="nav nav-tabs report-tabs mb-4">

            <li class="nav-item">
                <button class="nav-link active"
                        data-bs-toggle="tab"
                        data-bs-target="#membership-statistics"
                        type="button">

                    <i class="mdi mdi-chart-box-outline me-1"></i>
                    Statistics

                </button>
            </li>


            <li class="nav-item">
                <button class="nav-link"
                        data-bs-toggle="tab"
                        data-bs-target="#member-register"
                        type="button">

                    <i class="mdi mdi-account-group-outline me-1"></i>
                    Member Register

                </button>
            </li>


            <li class="nav-item">
                <button class="nav-link"
                        data-bs-toggle="tab"
                        data-bs-target="#employer-summary"
                        type="button">

                    <i class="mdi mdi-office-building-outline me-1"></i>
                    Employer Summary

                </button>
            </li>


            <li class="nav-item">
                <button class="nav-link"
                        data-bs-toggle="tab"
                        data-bs-target="#status-summary"
                        type="button">

                    <i class="mdi mdi-chart-donut me-1"></i>
                    Status

                </button>
            </li>


            <li class="nav-item">
                <button class="nav-link"
                        data-bs-toggle="tab"
                        data-bs-target="#gender-summary"
                        type="button">

                    <i class="mdi mdi-account-multiple-outline me-1"></i>
                    Gender

                </button>
            </li>


            <li class="nav-item">
                <button class="nav-link"
                        data-bs-toggle="tab"
                        data-bs-target="#age-profile"
                        type="button">

                    <i class="mdi mdi-calendar-account-outline me-1"></i>
                    Age Profile

                </button>
            </li>


            <li class="nav-item">
                <button class="nav-link"
                        data-bs-toggle="tab"
                        data-bs-target="#legacy-mapping"
                        type="button">

                    <i class="mdi mdi-link-variant me-1"></i>
                    Legacy Mapping

                </button>
            </li>


            <li class="nav-item">
                <button class="nav-link"
                        data-bs-toggle="tab"
                        data-bs-target="#data-quality"
                        type="button">

                    <i class="mdi mdi-database-alert-outline me-1"></i>
                    Data Quality

                </button>
            </li>

        </ul>


        <div class="tab-content">


            {{-- =====================================================
                 STATISTICS
            ====================================================== --}}

            <div class="tab-pane fade show active"
                 id="membership-statistics">

                <div class="mb-4">

                    <h5 class="report-section-title">
                        Membership Statistics
                    </h5>

                    <p class="text-muted mb-0">
                        Current static membership position based on the selected filters.
                    </p>

                </div>


                <div class="row g-3 mb-4">

                    <div class="col-xl-4 col-lg-6">

                        <div class="card statistics-card">

                            <div class="card-header">
                                Membership Status
                            </div>

                            <div class="card-body">

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span>Total Membership</span>

                                    <span class="statistics-main-value">
                                        {{ number_format($summary['total']) }}
                                    </span>
                                </div>

                                <div class="d-flex justify-content-between mb-2">
                                    <span>Active</span>
                                    <strong>{{ number_format($summary['active']) }}</strong>
                                </div>

                                <div class="d-flex justify-content-between mb-2">
                                    <span>Dormant</span>
                                    <strong>{{ number_format($summary['dormant']) }}</strong>
                                </div>

                                <div class="d-flex justify-content-between mb-2">
                                    <span>Inactive</span>
                                    <strong>{{ number_format($summary['inactive']) }}</strong>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <span>Suspended</span>
                                    <strong>{{ number_format($summary['suspended']) }}</strong>
                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="col-xl-4 col-lg-6">

                        <div class="card statistics-card">

                            <div class="card-header">
                                Gender Distribution
                            </div>

                            <div class="card-body">

                                <div class="d-flex justify-content-between align-items-center mb-3">

                                    <span>Male</span>

                                    <div class="text-end">

                                        <strong>
                                            {{ number_format($maleCount) }}
                                        </strong>

                                        <div class="statistics-small">
                                            {{ number_format(($maleCount / $statisticsTotal) * 100, 2) }}%
                                        </div>

                                    </div>

                                </div>


                                <div class="d-flex justify-content-between align-items-center mb-3">

                                    <span>Female</span>

                                    <div class="text-end">

                                        <strong>
                                            {{ number_format($femaleCount) }}
                                        </strong>

                                        <div class="statistics-small">
                                            {{ number_format(($femaleCount / $statisticsTotal) * 100, 2) }}%
                                        </div>

                                    </div>

                                </div>


                                <div class="d-flex justify-content-between align-items-center">

                                    <span>Not Specified</span>

                                    <div class="text-end">

                                        <strong>
                                            {{ number_format($genderUnknownCount) }}
                                        </strong>

                                        <div class="statistics-small">
                                            {{ number_format(($genderUnknownCount / $statisticsTotal) * 100, 2) }}%
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="col-xl-4 col-lg-6">

                        <div class="card statistics-card">

                            <div class="card-header">
                                Static Data Coverage
                            </div>

                            <div class="card-body">

                                <div class="d-flex justify-content-between mb-3">
                                    <span>With National ID</span>
                                    <strong>{{ number_format($withNationalId) }}</strong>
                                </div>

                                <div class="d-flex justify-content-between mb-3">
                                    <span>With Current Employer</span>
                                    <strong>{{ number_format($withEmployer) }}</strong>
                                </div>

                                <div class="d-flex justify-content-between mb-3">
                                    <span>With PenAd Number</span>
                                    <strong>{{ number_format($withPenad) }}</strong>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <span>With Fundworx Number</span>
                                    <strong>{{ number_format($withFundworx) }}</strong>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- OVERALL STATISTICS --}}

                @php
                    $overallStatistics = [
                        ['Membership Status', 'Total Membership', $summary['total']],
                        ['Membership Status', 'Active', $summary['active']],
                        ['Membership Status', 'Dormant', $summary['dormant']],
                        ['Membership Status', 'Inactive', $summary['inactive']],
                        ['Membership Status', 'Suspended', $summary['suspended']],

                        ['Gender', 'Male', $maleCount],
                        ['Gender', 'Female', $femaleCount],
                        ['Gender', 'Not Specified', $genderUnknownCount],

                        ['National ID', 'With National ID', $withNationalId],
                        ['National ID', 'Missing National ID', $summary['without_national_id']],

                        ['Employer', 'With Current Employer', $withEmployer],
                        ['Employer', 'Missing Current Employer', $summary['without_employer']],

                        ['Legacy References', 'With PenAd Number', $withPenad],
                        ['Legacy References', 'Missing PenAd Number', $summary['without_penad_number']],

                        ['Legacy References', 'With Fundworx Number', $withFundworx],
                        ['Legacy References', 'Missing Fundworx Number', $summary['without_fundworx_number']],

                        ['Data Quality', 'Missing Date of Birth', $summary['without_dob']],
                        ['Data Quality', 'Missing Email', $summary['without_email']],
                        ['Data Quality', 'Missing Cell Number', $summary['without_cell_number']],
                    ];
                @endphp


                <div class="card border mb-4">

                    <div class="card-body">

                        <div class="mb-3">

                            <h5 class="mb-1">
                                Overall Membership Statistics
                            </h5>

                            <p class="text-muted mb-0">
                                This table can be exported to Excel, CSV, PDF or Print.
                            </p>

                        </div>


                        <div class="table-responsive">

                            <table id="overall-statistics-table"
                                   class="table table-bordered table-striped table-hover report-table w-100">

                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Statistic</th>
                                        <th>Members</th>
                                        <th>% of Membership</th>
                                    </tr>
                                </thead>


                                <tbody>

                                    @foreach($overallStatistics as $stat)

                                        <tr>

                                            <td>
                                                {{ $stat[0] }}
                                            </td>

                                            <td>
                                                {{ $stat[1] }}
                                            </td>

                                            <td data-order="{{ $stat[2] }}">
                                                {{ number_format($stat[2]) }}
                                            </td>

                                            <td data-order="{{ ($stat[2] / $statisticsTotal) * 100 }}">

                                                @if($stat[1] === 'Total Membership')
                                                    100.00%
                                                @else
                                                    {{ number_format(($stat[2] / $statisticsTotal) * 100, 2) }}%
                                                @endif

                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>


                {{-- STATISTICS BY EMPLOYER --}}

                <div class="card border">

                    <div class="card-body">

                        <div class="mb-3">

                            <h5 class="mb-1">
                                Membership Statistics by Employer
                            </h5>

                            <p class="text-muted mb-0">
                                Current static membership position for each employer.
                            </p>

                        </div>


                        <div class="table-responsive">

                            <table id="membership-statistics-employer-table"
                                   class="table table-bordered table-striped table-hover report-table w-100">

                                <thead>

                                    <tr>

                                        <th>PENERP Employer No.</th>
                                        <th>PenAd Employer No.</th>
                                        <th>Fundworx Employer No.</th>
                                        <th>Employer</th>
                                        <th>Total</th>
                                        <th>Active</th>
                                        <th>Dormant</th>
                                        <th>Inactive</th>
                                        <th>Suspended</th>
                                        <th>Male</th>
                                        <th>Female</th>
                                        <th>Gender N/S</th>
                                        <th>Missing ID</th>
                                        <th>Missing DOB</th>
                                        <th>% of Membership</th>

                                    </tr>

                                </thead>


                                <tbody>

                                    @foreach($employerSummary as $employer)

                                        @php
                                            $employerMembers = $members
                                                ->filter(function ($member) use ($employer) {
                                                    return $member->currentEmployment
                                                        && $member->currentEmployment->employer
                                                        && $member->currentEmployment
                                                            ->employer
                                                            ->employer_number
                                                            === $employer->employer_number;
                                                });

                                            $employerMale = $employerMembers
                                                ->filter(function ($member) {
                                                    return strtolower(
                                                        trim((string) $member->gender)
                                                    ) === 'male';
                                                })
                                                ->count();

                                            $employerFemale = $employerMembers
                                                ->filter(function ($member) {
                                                    return strtolower(
                                                        trim((string) $member->gender)
                                                    ) === 'female';
                                                })
                                                ->count();

                                            $employerGenderUnknown = $employerMembers
                                                ->filter(function ($member) {
                                                    $gender = strtolower(
                                                        trim((string) $member->gender)
                                                    );

                                                    return !in_array(
                                                        $gender,
                                                        ['male', 'female'],
                                                        true
                                                    );
                                                })
                                                ->count();

                                            $employerMissingId = $employerMembers
                                                ->filter(function ($member) {
                                                    return blank($member->national_id);
                                                })
                                                ->count();

                                            $employerMissingDob = $employerMembers
                                                ->filter(function ($member) {
                                                    return blank($member->date_of_birth);
                                                })
                                                ->count();

                                            $employerPercentage =
                                                ($employer->total_members / $statisticsTotal)
                                                * 100;
                                        @endphp


                                        <tr>

                                            <td>
                                                {{ $employer->employer_number }}
                                            </td>

                                            <td>
                                                {{ $employer->penad_employer_number ?? '-' }}
                                            </td>

                                            <td>
                                                {{ $employer->fundworx_employer_number ?? '-' }}
                                            </td>

                                            <td>
                                                <strong>{{ $employer->name }}</strong>
                                            </td>

                                            <td>
                                                {{ number_format($employer->total_members) }}
                                            </td>

                                            <td>
                                                {{ number_format($employer->active_members) }}
                                            </td>

                                            <td>
                                                {{ number_format($employer->dormant_members) }}
                                            </td>

                                            <td>
                                                {{ number_format($employer->inactive_members) }}
                                            </td>

                                            <td>
                                                {{ number_format($employer->suspended_members) }}
                                            </td>

                                            <td>
                                                {{ number_format($employerMale) }}
                                            </td>

                                            <td>
                                                {{ number_format($employerFemale) }}
                                            </td>

                                            <td>
                                                {{ number_format($employerGenderUnknown) }}
                                            </td>

                                            <td>
                                                {{ number_format($employerMissingId) }}
                                            </td>

                                            <td>
                                                {{ number_format($employerMissingDob) }}
                                            </td>

                                            <td data-order="{{ $employerPercentage }}">
                                                {{ number_format($employerPercentage, 2) }}%
                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>


                                <tfoot>

                                    <tr class="table-total-row">

                                        <th colspan="4">
                                            TOTAL
                                        </th>

                                        <th>
                                            {{ number_format($employerSummary->sum('total_members')) }}
                                        </th>

                                        <th>
                                            {{ number_format($employerSummary->sum('active_members')) }}
                                        </th>

                                        <th>
                                            {{ number_format($employerSummary->sum('dormant_members')) }}
                                        </th>

                                        <th>
                                            {{ number_format($employerSummary->sum('inactive_members')) }}
                                        </th>

                                        <th>
                                            {{ number_format($employerSummary->sum('suspended_members')) }}
                                        </th>

                                        <th>
                                            {{ number_format($maleCount) }}
                                        </th>

                                        <th>
                                            {{ number_format($femaleCount) }}
                                        </th>

                                        <th>
                                            {{ number_format($genderUnknownCount) }}
                                        </th>

                                        <th>
                                            {{ number_format($summary['without_national_id']) }}
                                        </th>

                                        <th>
                                            {{ number_format($summary['without_dob']) }}
                                        </th>

                                        <th>
                                            100.00%
                                        </th>

                                    </tr>

                                </tfoot>

                            </table>

                        </div>

                    </div>

                </div>

            </div>


            {{-- =====================================================
                 MEMBER REGISTER
            ====================================================== --}}

            <div class="tab-pane fade"
                 id="member-register">

                <div class="mb-3">

                    <h5 class="report-section-title">
                        Membership Register
                    </h5>

                    <p class="text-muted mb-0">
                        Static member and current employment information.
                    </p>

                </div>


                <div class="table-responsive">

                    <table id="membership-report-table"
                           class="table table-bordered table-striped table-hover report-table w-100">

                        <thead>

                            <tr>
                                <th>PENERP No.</th>
                                <th>PenAd No.</th>
                                <th>Fundworx No.</th>
                                <th>Member</th>
                                <th>National ID</th>
                                <th>DOB</th>
                                <th>Gender</th>
                                <th>Employer</th>
                                <th>Staff No.</th>
                                <th>Vote No.</th>
                                <th>Joined Fund</th>
                                <th>Status</th>
                            </tr>

                        </thead>


                        <tbody>

                            @foreach($members as $member)

                                <tr>

                                    <td>
                                        {{ $member->member_number }}
                                    </td>

                                    <td>
                                        {{ $member->penad_member_number ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $member->fundworx_member_number ?? '-' }}
                                    </td>

                                    <td>

                                        <strong>
                                            {{ $member->surname }},
                                            {{ $member->first_names }}
                                        </strong>

                                        @if($member->other_names)
                                            <br>
                                            <small class="text-muted">
                                                Other: {{ $member->other_names }}
                                            </small>
                                        @endif

                                        @if($member->maiden_name)
                                            <br>
                                            <small class="text-muted">
                                                Maiden: {{ $member->maiden_name }}
                                            </small>
                                        @endif

                                    </td>

                                    <td>
                                        {{ $member->national_id ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $member->date_of_birth
                                            ? $member->date_of_birth->format('d M Y')
                                            : '-'
                                        }}
                                    </td>

                                    <td>
                                        {{ $member->gender ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $member->currentEmployment?->employer?->name ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $member->currentEmployment?->staff_number ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $member->currentEmployment?->vote_number ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $member->date_joined_fund
                                            ? $member->date_joined_fund->format('d M Y')
                                            : '-'
                                        }}
                                    </td>

                                    <td>

                                        @php
                                            $statusClass = match($member->membership_status) {
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

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- =====================================================
                 EMPLOYER SUMMARY
            ====================================================== --}}

            <div class="tab-pane fade"
                 id="employer-summary">

                <div class="mb-3">

                    <h5 class="report-section-title">
                        Employer Membership Summary
                    </h5>

                    <p class="text-muted mb-0">
                        Current membership totals grouped by employer.
                    </p>

                </div>


                <div class="table-responsive">

                    <table id="employer-summary-table"
                           class="table table-bordered table-striped table-hover report-table w-100">

                        <thead>

                            <tr>
                                <th>PENERP Employer</th>
                                <th>PenAd Employer</th>
                                <th>Fundworx Employer</th>
                                <th>Employer</th>
                                <th>Total</th>
                                <th>Active</th>
                                <th>Dormant</th>
                                <th>Inactive</th>
                                <th>Suspended</th>
                            </tr>

                        </thead>


                        <tbody>

                            @foreach($employerSummary as $employer)

                                <tr>

                                    <td>
                                        {{ $employer->employer_number }}
                                    </td>

                                    <td>
                                        {{ $employer->penad_employer_number ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $employer->fundworx_employer_number ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $employer->name }}
                                    </td>

                                    <td>
                                        {{ number_format($employer->total_members) }}
                                    </td>

                                    <td>
                                        {{ number_format($employer->active_members) }}
                                    </td>

                                    <td>
                                        {{ number_format($employer->dormant_members) }}
                                    </td>

                                    <td>
                                        {{ number_format($employer->inactive_members) }}
                                    </td>

                                    <td>
                                        {{ number_format($employer->suspended_members) }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- =====================================================
                 STATUS SUMMARY
            ====================================================== --}}

            <div class="tab-pane fade"
                 id="status-summary">

                <div class="mb-3">
                    <h5 class="report-section-title">
                        Membership Status Summary
                    </h5>
                </div>


                <div class="row g-3">

                    @forelse($statusSummary as $status)

                        <div class="col-xl-3 col-lg-4 col-md-6">

                            <div class="card border h-100">

                                <div class="card-body">

                                    <p class="text-muted mb-1">
                                        {{ ucwords($status->status) }}
                                    </p>

                                    <h3>
                                        {{ number_format($status->total) }}
                                    </h3>

                                    <p class="text-muted mb-0">
                                        {{ number_format(($status->total / $statisticsTotal) * 100, 2) }}%
                                    </p>

                                </div>

                            </div>

                        </div>

                    @empty

                        <div class="col-12">
                            <p class="text-muted mb-0">
                                No status statistics available.
                            </p>
                        </div>

                    @endforelse

                </div>

            </div>


            {{-- =====================================================
                 GENDER
            ====================================================== --}}

            <div class="tab-pane fade"
                 id="gender-summary">

                <div class="mb-3">
                    <h5 class="report-section-title">
                        Gender Summary
                    </h5>
                </div>


                <div class="table-responsive">

                    <table id="gender-summary-table"
                           class="table table-bordered table-striped report-table w-100">

                        <thead>

                            <tr>
                                <th>Gender</th>
                                <th>Members</th>
                                <th>Percentage</th>
                            </tr>

                        </thead>


                        <tbody>

                            @foreach($genderSummary as $gender)

                                <tr>

                                    <td>
                                        {{ $gender->gender }}
                                    </td>

                                    <td>
                                        {{ number_format($gender->total) }}
                                    </td>

                                    <td>
                                        {{ number_format(($gender->total / $statisticsTotal) * 100, 2) }}%
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- =====================================================
                 AGE PROFILE
            ====================================================== --}}

            <div class="tab-pane fade"
                 id="age-profile">

                <div class="mb-3">

                    <h5 class="report-section-title">
                        Member Age Profile
                    </h5>

                    <p class="text-muted mb-0">
                        Current age distribution based on date of birth.
                    </p>

                </div>


                <div class="row g-3 mb-4">

                    @foreach([
                        'under_30' => 'Under 30',
                        '30_39' => '30 - 39',
                        '40_49' => '40 - 49',
                        '50_54' => '50 - 54',
                        '55_59' => '55 - 59',
                        '60_plus' => '60+',
                        'missing_dob' => 'Missing DOB',
                    ] as $key => $label)

                        <div class="col-xl-2 col-lg-3 col-md-4 col-6">

                            <div class="card border h-100">

                                <div class="card-body">

                                    <p class="text-muted mb-1">
                                        {{ $label }}
                                    </p>

                                    <h3 class="mb-0">
                                        {{ number_format($ageProfile[$key]) }}
                                    </h3>

                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>


                <div class="table-responsive">

                    <table id="age-profile-table"
                           class="table table-bordered table-striped table-hover report-table w-100">

                        <thead>

                            <tr>
                                <th>PENERP No.</th>
                                <th>PenAd No.</th>
                                <th>Member</th>
                                <th>National ID</th>
                                <th>DOB</th>
                                <th>Age</th>
                                <th>Employer</th>
                                <th>Status</th>
                            </tr>

                        </thead>


                        <tbody>

                            @foreach($ageMembers as $member)

                                <tr>

                                    <td>
                                        {{ $member->member_number }}
                                    </td>

                                    <td>
                                        {{ $member->penad_member_number ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $member->surname }},
                                        {{ $member->first_names }}
                                    </td>

                                    <td>
                                        {{ $member->national_id ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $member->date_of_birth
                                            ? $member->date_of_birth->format('d M Y')
                                            : '-'
                                        }}
                                    </td>

                                    <td data-order="{{ $member->current_age ?? 0 }}">
                                        {{ $member->current_age ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $member->currentEmployment?->employer?->name ?? '-' }}
                                    </td>

                                    <td>
                                        {{ ucfirst($member->membership_status) }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- =====================================================
                 LEGACY MAPPING
            ====================================================== --}}

            <div class="tab-pane fade"
                 id="legacy-mapping">

                <div class="mb-3">

                    <h5 class="report-section-title">
                        Legacy Number Mapping
                    </h5>

                    <p class="text-muted mb-0">
                        Reconciliation of PENERP, PenAd and Fundworx membership numbers.
                    </p>

                </div>


                <div class="row g-3 mb-4">

                    @foreach([
                        'complete' => ['Complete', 'text-success'],
                        'missing_penad' => ['Missing PenAd', 'text-warning'],
                        'missing_fundworx' => ['Missing Fundworx', 'text-warning'],
                        'missing_both' => ['Missing Both', 'text-danger'],
                        'duplicate_penad_numbers' => ['Duplicate PenAd', 'text-danger'],
                        'duplicate_fundworx_numbers' => ['Duplicate Fundworx', 'text-danger'],
                    ] as $key => $legacyCard)

                        <div class="col-xl-2 col-lg-3 col-md-4 col-6">

                            <div class="card border h-100">

                                <div class="card-body">

                                    <p class="text-muted mb-1">
                                        {{ $legacyCard[0] }}
                                    </p>

                                    <h3 class="mb-0 {{ $legacyCard[1] }}">
                                        {{ number_format($legacySummary[$key]) }}
                                    </h3>

                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>


                <div class="table-responsive">

                    <table id="legacy-mapping-table"
                           class="table table-bordered table-striped table-hover report-table w-100">

                        <thead>

                            <tr>
                                <th>PENERP No.</th>
                                <th>PenAd No.</th>
                                <th>Fundworx No.</th>
                                <th>Member</th>
                                <th>National ID</th>
                                <th>Employer</th>
                                <th>Status</th>
                                <th>Mapping Status</th>
                                <th>Duplicate Check</th>
                            </tr>

                        </thead>


                        <tbody>

                            @foreach($legacyMapping as $member)

                                <tr>

                                    <td>
                                        {{ $member->member_number }}
                                    </td>

                                    <td>
                                        {{ $member->penad_member_number ?: 'Missing' }}
                                    </td>

                                    <td>
                                        {{ $member->fundworx_member_number ?: 'Missing' }}
                                    </td>

                                    <td>
                                        {{ $member->surname }},
                                        {{ $member->first_names }}
                                    </td>

                                    <td>
                                        {{ $member->national_id ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $member->employer_name ?? '-' }}
                                    </td>

                                    <td>
                                        {{ ucfirst($member->membership_status) }}
                                    </td>

                                    <td>

                                        @switch($member->mapping_status)

                                            @case('complete')
                                                <span class="badge bg-success">
                                                    Complete
                                                </span>
                                                @break

                                            @case('missing_penad')
                                                <span class="badge bg-warning text-dark">
                                                    Missing PenAd
                                                </span>
                                                @break

                                            @case('missing_fundworx')
                                                <span class="badge bg-warning text-dark">
                                                    Missing Fundworx
                                                </span>
                                                @break

                                            @default
                                                <span class="badge bg-danger">
                                                    Missing Both
                                                </span>

                                        @endswitch

                                    </td>

                                    <td>

                                        @if(!$member->duplicate_penad && !$member->duplicate_fundworx)

                                            <span class="text-success">
                                                <i class="mdi mdi-check-circle-outline me-1"></i>
                                                No Duplicate
                                            </span>

                                        @else

                                            @if($member->duplicate_penad)
                                                <span class="badge bg-danger exception-badge">
                                                    Duplicate PenAd
                                                </span>
                                            @endif

                                            @if($member->duplicate_fundworx)
                                                <span class="badge bg-danger exception-badge">
                                                    Duplicate Fundworx
                                                </span>
                                            @endif

                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- =====================================================
                 DATA QUALITY
            ====================================================== --}}

            <div class="tab-pane fade"
                 id="data-quality">

                <div class="mb-3">

                    <h5 class="report-section-title">
                        Membership Data Quality
                    </h5>

                    <p class="text-muted mb-0">
                        Members with missing key static information.
                    </p>

                </div>


                <div class="row g-3 mb-4">

                    <div class="col-xl-2 col-md-4 col-6">
                        <div class="card border h-100">

                            <div class="card-body">
                                <p class="text-muted mb-1">Missing National ID</p>
                                <h3 class="text-danger mb-0">
                                    {{ number_format($summary['without_national_id']) }}
                                </h3>
                            </div>

                        </div>
                    </div>


                    <div class="col-xl-2 col-md-4 col-6">
                        <div class="card border h-100">

                            <div class="card-body">
                                <p class="text-muted mb-1">Missing DOB</p>
                                <h3 class="text-warning mb-0">
                                    {{ number_format($summary['without_dob']) }}
                                </h3>
                            </div>

                        </div>
                    </div>


                    <div class="col-xl-2 col-md-4 col-6">
                        <div class="card border h-100">

                            <div class="card-body">
                                <p class="text-muted mb-1">Missing Employer</p>
                                <h3 class="text-danger mb-0">
                                    {{ number_format($summary['without_employer']) }}
                                </h3>
                            </div>

                        </div>
                    </div>


                    <div class="col-xl-2 col-md-4 col-6">
                        <div class="card border h-100">

                            <div class="card-body">
                                <p class="text-muted mb-1">Missing PenAd</p>
                                <h3 class="text-warning mb-0">
                                    {{ number_format($summary['without_penad_number']) }}
                                </h3>
                            </div>

                        </div>
                    </div>


                    <div class="col-xl-2 col-md-4 col-6">
                        <div class="card border h-100">

                            <div class="card-body">
                                <p class="text-muted mb-1">Missing Fundworx</p>
                                <h3 class="text-warning mb-0">
                                    {{ number_format($summary['without_fundworx_number']) }}
                                </h3>
                            </div>

                        </div>
                    </div>


                    <div class="col-xl-2 col-md-4 col-6">
                        <div class="card border h-100">

                            <div class="card-body">
                                <p class="text-muted mb-1">Members With Exceptions</p>
                                <h3 class="text-danger mb-0">
                                    {{ number_format($dataQualityMembers->count()) }}
                                </h3>
                            </div>

                        </div>
                    </div>

                </div>


                <div class="table-responsive">

                    <table id="data-quality-table"
                           class="table table-bordered table-striped table-hover report-table w-100">

                        <thead>

                            <tr>
                                <th>PENERP No.</th>
                                <th>Member</th>
                                <th>National ID</th>
                                <th>DOB</th>
                                <th>PenAd No.</th>
                                <th>Fundworx No.</th>
                                <th>Employer</th>
                                <th>Exceptions</th>
                            </tr>

                        </thead>


                        <tbody>

                            @foreach($dataQualityMembers as $member)

                                <tr>

                                    <td>
                                        {{ $member->member_number }}
                                    </td>

                                    <td>
                                        {{ $member->surname }},
                                        {{ $member->first_names }}
                                    </td>

                                    <td>
                                        {{ $member->national_id ?: '-' }}
                                    </td>

                                    <td>
                                        {{ $member->date_of_birth
                                            ? $member->date_of_birth->format('d M Y')
                                            : '-'
                                        }}
                                    </td>

                                    <td>
                                        {{ $member->penad_member_number ?: '-' }}
                                    </td>

                                    <td>
                                        {{ $member->fundworx_member_number ?: '-' }}
                                    </td>

                                    <td>
                                        {{ $member->currentEmployment?->employer?->name ?? '-' }}
                                    </td>

                                    <td>

                                        @if(blank($member->national_id))
                                            <span class="badge bg-danger exception-badge">
                                                National ID
                                            </span>
                                        @endif

                                        @if(blank($member->date_of_birth))
                                            <span class="badge bg-warning text-dark exception-badge">
                                                DOB
                                            </span>
                                        @endif

                                        @if(!$member->currentEmployment?->employer)
                                            <span class="badge bg-danger exception-badge">
                                                Employer
                                            </span>
                                        @endif

                                        @if(blank($member->penad_member_number))
                                            <span class="badge bg-warning text-dark exception-badge">
                                                PenAd
                                            </span>
                                        @endif

                                        @if(blank($member->fundworx_member_number))
                                            <span class="badge bg-warning text-dark exception-badge">
                                                Fundworx
                                            </span>
                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

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

    function reportButtons(title, filename) {

        return [
            {
                extend: 'copyHtml5',
                text: '<i class="mdi mdi-content-copy me-1"></i> Copy',
                className: 'btn btn-secondary btn-sm',
                title: title,
                footer: true,
                exportOptions: {
                    stripHtml: true
                }
            },

            {
                extend: 'excelHtml5',
                text: '<i class="mdi mdi-microsoft-excel me-1"></i> Excel',
                className: 'btn btn-success btn-sm',
                title: title,
                filename: filename,
                footer: true,
                exportOptions: {
                    stripHtml: true
                }
            },

            {
                extend: 'csvHtml5',
                text: '<i class="mdi mdi-file-delimited-outline me-1"></i> CSV',
                className: 'btn btn-info btn-sm',
                title: title,
                filename: filename,
                footer: true,
                exportOptions: {
                    stripHtml: true
                }
            },

            {
                extend: 'pdfHtml5',
                text: '<i class="mdi mdi-file-pdf-box me-1"></i> PDF',
                className: 'btn btn-danger btn-sm',
                title: title,
                filename: filename,
                orientation: 'landscape',
                pageSize: 'A3',
                footer: true,
                exportOptions: {
                    stripHtml: true
                },

                customize: function (doc) {
                    doc.defaultStyle.fontSize = 7;
                    doc.styles.tableHeader.fontSize = 8;

                    doc.styles.title = {
                        fontSize: 15,
                        bold: true,
                        alignment: 'center',
                        margin: [0, 0, 0, 15]
                    };
                }
            },

            {
                extend: 'print',
                text: '<i class="mdi mdi-printer-outline me-1"></i> Print',
                className: 'btn btn-dark btn-sm',
                title: title,
                footer: true,
                exportOptions: {
                    stripHtml: true
                }
            }
        ];
    }


    function initialiseReportTable(
        selector,
        title,
        filename,
        orderColumn = 0,
        orderDirection = 'asc'
    ) {

        if (!$(selector).length) {
            return;
        }

        if ($.fn.DataTable.isDataTable(selector)) {
            return;
        }

        $(selector).DataTable({
            pageLength: 25,

            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, 'All']
            ],

            responsive: false,
            autoWidth: false,

            order: [
                [orderColumn, orderDirection]
            ],

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

            buttons: reportButtons(
                title,
                filename
            ),

            language: {
                search: 'Search:',
                searchPlaceholder: 'Search report...',
                lengthMenu: 'Show _MENU_ records',
                info: 'Showing _START_ to _END_ of _TOTAL_ records',
                infoEmpty: 'No records found',
                zeroRecords: 'No matching records found'
            }
        });
    }


    initialiseReportTable(
        '#overall-statistics-table',
        'PENERP Overall Membership Statistics',
        'PENERP_Overall_Membership_Statistics',
        0
    );


    initialiseReportTable(
        '#membership-statistics-employer-table',
        'PENERP Membership Statistics by Employer',
        'PENERP_Membership_Statistics_By_Employer',
        3
    );


    initialiseReportTable(
        '#membership-report-table',
        'PENERP Membership Register',
        'PENERP_Membership_Register',
        3
    );


    initialiseReportTable(
        '#employer-summary-table',
        'PENERP Membership by Employer',
        'PENERP_Membership_By_Employer',
        3
    );


    initialiseReportTable(
        '#gender-summary-table',
        'PENERP Membership Gender Summary',
        'PENERP_Membership_Gender_Summary',
        0
    );


    initialiseReportTable(
        '#age-profile-table',
        'PENERP Membership Age Profile',
        'PENERP_Membership_Age_Profile',
        5,
        'desc'
    );


    initialiseReportTable(
        '#legacy-mapping-table',
        'PENERP Legacy Number Mapping',
        'PENERP_Legacy_Number_Mapping',
        3
    );


    initialiseReportTable(
        '#data-quality-table',
        'PENERP Membership Data Quality Report',
        'PENERP_Membership_Data_Quality',
        1
    );


    /*
    |--------------------------------------------------------------------------
    | Fix Tables When Bootstrap Tabs Are Opened
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('button[data-bs-toggle="tab"]')
        .forEach(function (tab) {

            tab.addEventListener(
                'shown.bs.tab',
                function () {

                    $.fn.dataTable
                        .tables({
                            visible: true,
                            api: true
                        })
                        .columns
                        .adjust();

                }
            );

        });


    /*
    |--------------------------------------------------------------------------
    | Open Correct Tab From URL Hash
    |--------------------------------------------------------------------------
    |
    | Allows links such as:
    | /reports/membership#age-profile
    |
    */

    if (window.location.hash) {

        const target =
            document.querySelector(
                '[data-bs-target="' + window.location.hash + '"]'
            );

        if (target) {
            bootstrap.Tab
                .getOrCreateInstance(target)
                .show();
        }

    }


    document
        .querySelectorAll('button[data-bs-toggle="tab"]')
        .forEach(function (tab) {

            tab.addEventListener(
                'shown.bs.tab',
                function (event) {

                    const target =
                        event.target.getAttribute(
                            'data-bs-target'
                        );

                    if (target) {
                        history.replaceState(
                            null,
                            null,
                            target
                        );
                    }

                }
            );

        });

});
</script>

@endpush
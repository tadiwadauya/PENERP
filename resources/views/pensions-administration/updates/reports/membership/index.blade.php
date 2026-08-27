@extends('layouts.app')

@section('title', 'Membership Reports')

@section('page-heading', 'Membership Reports')

@section('page-subheading')
Static membership information, statistics and management reports
@endsection


@push('styles')

<link rel="stylesheet"
      href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

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

    .clickable-stat-card {
        cursor: pointer;
        transition:
            transform .15s ease,
            box-shadow .15s ease;
    }

    .clickable-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 18px rgba(0, 0, 0, .10);
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

    .dataTables_filter {
        text-align: right;
    }

    div.dataTables_processing {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 12px 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, .08);
    }

    .missing-employer-active {
        border: 1px solid #0d6efd !important;
        background: #f8fbff;
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

    $statisticsTotal = max(
        1,
        $summary['total']
    );

    $maleCount =
        $genderSummary
            ->firstWhere(
                'gender',
                'Male'
            )
            ?->total
        ?? 0;

    $femaleCount =
        $genderSummary
            ->firstWhere(
                'gender',
                'Female'
            )
            ?->total
        ?? 0;

    $genderUnknownCount =
        $genderSummary
            ->firstWhere(
                'gender',
                'Not Specified'
            )
            ?->total
        ?? 0;

    $withNationalId =
        $summary['total']
        -
        $summary['without_national_id'];

    $withEmployer =
        $summary['total']
        -
        $summary['without_employer'];

    $withPenad =
        $summary['total']
        -
        $summary['without_penad_number'];

    $withFundworx =
        $summary['total']
        -
        $summary['without_fundworx_number'];

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


    {{-- =====================================================
         MISSING EMPLOYER - NO PAGE RELOAD
    ====================================================== --}}

    <div class="col-xl-2 col-lg-3 col-md-4 col-6">

        <div id="missing-employer-card"
             class="card report-stat-card clickable-stat-card">

            <div class="card-body">

                <p class="text-muted mb-1">
                    Missing Employer
                </p>

                <h3 class="mb-1 text-danger">
                    {{ number_format($summary['without_employer']) }}
                </h3>

                <small class="text-primary">

                    <i class="mdi mdi-eye-outline me-1"></i>

                    View and assign

                </small>

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

                                    <strong>
                                        {{ number_format($summary['active']) }}
                                    </strong>

                                </div>

                                <div class="d-flex justify-content-between mb-2">

                                    <span>Dormant</span>

                                    <strong>
                                        {{ number_format($summary['dormant']) }}
                                    </strong>

                                </div>

                                <div class="d-flex justify-content-between mb-2">

                                    <span>Inactive</span>

                                    <strong>
                                        {{ number_format($summary['inactive']) }}
                                    </strong>

                                </div>

                                <div class="d-flex justify-content-between">

                                    <span>Suspended</span>

                                    <strong>
                                        {{ number_format($summary['suspended']) }}
                                    </strong>

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

                                <div class="d-flex justify-content-between mb-3">

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


                                <div class="d-flex justify-content-between mb-3">

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


                                <div class="d-flex justify-content-between">

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

                                    <strong>
                                        {{ number_format($withNationalId) }}
                                    </strong>

                                </div>

                                <div class="d-flex justify-content-between mb-3">

                                    <span>With Current Employer</span>

                                    <strong>
                                        {{ number_format($withEmployer) }}
                                    </strong>

                                </div>

                                <div class="d-flex justify-content-between mb-3">

                                    <span>With PenAd Number</span>

                                    <strong>
                                        {{ number_format($withPenad) }}
                                    </strong>

                                </div>

                                <div class="d-flex justify-content-between">

                                    <span>With Fundworx Number</span>

                                    <strong>
                                        {{ number_format($withFundworx) }}
                                    </strong>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


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

                        <h5 class="mb-1">
                            Overall Membership Statistics
                        </h5>

                        <p class="text-muted mb-3">
                            Overall static membership statistics.
                        </p>


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

                                        @php

                                            $percentage =
                                                $stat[1] === 'Total Membership'
                                                    ? 100
                                                    : (($stat[2] / $statisticsTotal) * 100);

                                        @endphp

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

                                            <td data-order="{{ $percentage }}">
                                                {{ number_format($percentage, 2) }}%
                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>


                <div class="card border">

                    <div class="card-body">

                        <h5 class="mb-1">
                            Membership Statistics by Employer
                        </h5>

                        <p class="text-muted mb-3">
                            Current static membership position for each employer.
                        </p>


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

                                            $employerPercentage =
                                                (
                                                    $employer->total_members
                                                    /
                                                    $statisticsTotal
                                                )
                                                *
                                                100;

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
                                                <strong>
                                                    {{ $employer->name }}
                                                </strong>
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
                                                {{ number_format($employer->male_members) }}
                                            </td>

                                            <td>
                                                {{ number_format($employer->female_members) }}
                                            </td>

                                            <td>
                                                {{ number_format($employer->gender_not_specified) }}
                                            </td>

                                            <td>
                                                {{ number_format($employer->missing_national_id) }}
                                            </td>

                                            <td>
                                                {{ number_format($employer->missing_dob) }}
                                            </td>

                                            <td data-order="{{ $employerPercentage }}">
                                                {{ number_format($employerPercentage, 2) }}%
                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

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

                <h5 class="report-section-title">
                    Membership Register
                </h5>

                <p class="text-muted mb-3">
                    Static member and current employment information.
                </p>


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

                        <tbody></tbody>

                    </table>

                </div>

            </div>


            {{-- =====================================================
                 EMPLOYER SUMMARY
            ====================================================== --}}

            <div class="tab-pane fade"
                 id="employer-summary">

                <h5 class="report-section-title">
                    Employer Membership Summary
                </h5>

                <p class="text-muted mb-3">
                    Current membership totals grouped by employer.
                </p>


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
                 STATUS
            ====================================================== --}}

            <div class="tab-pane fade"
                 id="status-summary">

                <h5 class="report-section-title mb-3">
                    Membership Status Summary
                </h5>


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

                                        {{
                                            number_format(
                                                (
                                                    $status->total
                                                    /
                                                    $statisticsTotal
                                                )
                                                *
                                                100,
                                                2
                                            )
                                        }}%

                                    </p>

                                </div>

                            </div>

                        </div>

                    @empty

                        <div class="col-12">

                            <p class="text-muted">
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

                <h5 class="report-section-title mb-3">
                    Gender Summary
                </h5>


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

                                        {{
                                            number_format(
                                                (
                                                    $gender->total
                                                    /
                                                    $statisticsTotal
                                                )
                                                *
                                                100,
                                                2
                                            )
                                        }}%

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- =====================================================
                 AGE
            ====================================================== --}}

            <div class="tab-pane fade"
                 id="age-profile">

                <h5 class="report-section-title">
                    Member Age Profile
                </h5>

                <p class="text-muted mb-3">
                    Current age distribution based on date of birth.
                </p>


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
                                        {{ number_format($ageProfile[$key] ?? 0) }}
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

                        <tbody></tbody>

                    </table>

                </div>

            </div>


            {{-- =====================================================
                 LEGACY
            ====================================================== --}}

            <div class="tab-pane fade"
                 id="legacy-mapping">

                <h5 class="report-section-title">
                    Legacy Number Mapping
                </h5>

                <p class="text-muted mb-3">
                    Reconciliation of PENERP, PenAd and Fundworx membership numbers.
                </p>


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

                                    <h3 class="{{ $legacyCard[1] }} mb-0">

                                        {{
                                            number_format(
                                                $legacySummary[$key]
                                                ??
                                                0
                                            )
                                        }}

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

                        <tbody></tbody>

                    </table>

                </div>

            </div>


            {{-- =====================================================
                 DATA QUALITY
            ====================================================== --}}

            <div class="tab-pane fade"
                 id="data-quality">

                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">

                    <div>

                        <h5 class="report-section-title">
                            Membership Data Quality
                        </h5>

                        <p id="data-quality-description"
                           class="text-muted mb-0">
                            Members with missing key static information.
                        </p>

                    </div>


                    <button id="show-all-exceptions-button"
                            type="button"
                            class="btn btn-sm btn-outline-primary d-none">

                        <i class="mdi mdi-filter-remove-outline me-1"></i>

                        Show All Exceptions

                    </button>

                </div>


                <div class="row g-3 mb-4">

                    <div class="col-xl-2 col-md-4 col-6">

                        <div class="card border h-100">

                            <div class="card-body">

                                <p class="text-muted mb-1">
                                    Missing National ID
                                </p>

                                <h3 class="text-danger mb-0">
                                    {{ number_format($summary['without_national_id']) }}
                                </h3>

                            </div>

                        </div>

                    </div>


                    <div class="col-xl-2 col-md-4 col-6">

                        <div class="card border h-100">

                            <div class="card-body">

                                <p class="text-muted mb-1">
                                    Missing DOB
                                </p>

                                <h3 class="text-warning mb-0">
                                    {{ number_format($summary['without_dob']) }}
                                </h3>

                            </div>

                        </div>

                    </div>


                    <div class="col-xl-2 col-md-4 col-6">

                        <div id="missing-employer-quality-card"
                             class="card border clickable-stat-card h-100">

                            <div class="card-body">

                                <p class="text-muted mb-1">
                                    Missing Employer
                                </p>

                                <h3 class="text-danger mb-1">
                                    {{ number_format($summary['without_employer']) }}
                                </h3>

                                <small class="text-primary">
                                    View and assign
                                </small>

                            </div>

                        </div>

                    </div>


                    <div class="col-xl-2 col-md-4 col-6">

                        <div class="card border h-100">

                            <div class="card-body">

                                <p class="text-muted mb-1">
                                    Missing PenAd
                                </p>

                                <h3 class="text-warning mb-0">
                                    {{ number_format($summary['without_penad_number']) }}
                                </h3>

                            </div>

                        </div>

                    </div>


                    <div class="col-xl-2 col-md-4 col-6">

                        <div class="card border h-100">

                            <div class="card-body">

                                <p class="text-muted mb-1">
                                    Missing Fundworx
                                </p>

                                <h3 class="text-warning mb-0">
                                    {{ number_format($summary['without_fundworx_number']) }}
                                </h3>

                            </div>

                        </div>

                    </div>


                    <div class="col-xl-2 col-md-4 col-6">

                        <div class="card border h-100">

                            <div class="card-body">

                                <p class="text-muted mb-1">
                                    Members With Exceptions
                                </p>

                                <h3 class="text-danger mb-0">
                                    {{ number_format($dataQualityCount) }}
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
                                <th>Action</th>
                            </tr>

                        </thead>

                        <tbody></tbody>

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


<script>

$(document).ready(function () {

    /*
    |--------------------------------------------------------------------------
    | Dynamic Report Filters
    |--------------------------------------------------------------------------
    |
    | data_quality is deliberately dynamic.
    |
    | Clicking Missing Employer changes it without reloading this page.
    |
    */

    let reportFilters = {

        report_search:
            @json(request('search')),
        
        missing_employer_total:
            @json($summary['without_employer']),

        penerp_member_number:
            @json(request('penerp_member_number')),

        penad_member_number:
            @json(request('penad_member_number')),

        fundworx_member_number:
            @json(request('fundworx_member_number')),

        employer_id:
            @json(request('employer_id')),

        status:
            @json(request('status')),

        gender:
            @json(request('gender')),

        joined_from:
            @json(request('joined_from')),

        joined_to:
            @json(request('joined_to')),

        data_quality:
            null

    };


    /*
    |--------------------------------------------------------------------------
    | Small Tables
    |--------------------------------------------------------------------------
    */

    function initialiseSmallTable(
        selector,
        orderColumn = 0,
        orderDirection = 'asc'
    ) {

        if (!$(selector).length) {
            return;
        }

        if (
            $.fn.DataTable.isDataTable(
                selector
            )
        ) {
            return;
        }

        $(selector).DataTable({

            pageLength:
                25,

            lengthMenu: [
                [
                    10,
                    25,
                    50,
                    100
                ],
                [
                    10,
                    25,
                    50,
                    100
                ]
            ],

            deferRender:
                true,

            autoWidth:
                false,

            order: [
                [
                    orderColumn,
                    orderDirection
                ]
            ]

        });

    }


    initialiseSmallTable(
        '#overall-statistics-table',
        0
    );


    initialiseSmallTable(
        '#membership-statistics-employer-table',
        3
    );


    initialiseSmallTable(
        '#employer-summary-table',
        3
    );


    initialiseSmallTable(
        '#gender-summary-table',
        0
    );


    /*
    |--------------------------------------------------------------------------
    | Server Side Options
    |--------------------------------------------------------------------------
    */

    function serverSideOptions(
        url,
        columns,
        orderColumn = 0,
        orderDirection = 'asc'
    ) {

        return {

            processing:
                true,

            serverSide:
                true,

            deferRender:
                true,

            autoWidth:
                false,

            pageLength:
                25,

            searchDelay:
                500,

            lengthMenu: [
                [
                    10,
                    25,
                    50,
                    100
                ],
                [
                    10,
                    25,
                    50,
                    100
                ]
            ],

            ajax: {

                url:
                    url,

                type:
                    'GET',

                cache:
                    false,

                data:
                    function (d) {

                        Object.keys(
                            reportFilters
                        ).forEach(
                            function (key) {

                                if (
                                    reportFilters[key]
                                    !==
                                    null
                                    &&
                                    reportFilters[key]
                                    !==
                                    ''
                                ) {

                                    d[key] =
                                        reportFilters[key];

                                }

                            }
                        );

                    },

                error:
                    function (
                        xhr,
                        error,
                        thrown
                    ) {

                        console.error(
                            'Membership report request failed.',
                            error,
                            thrown,
                            xhr.responseText
                        );

                    }

            },

            columns:
                columns,

            order: [
                [
                    orderColumn,
                    orderDirection
                ]
            ],

            language: {

                processing:
                    '<span class="spinner-border spinner-border-sm me-2"></span> Loading records...',

                search:
                    'Search:',

                searchPlaceholder:
                    'Search report...',

                lengthMenu:
                    'Show _MENU_ records',

                info:
                    'Showing _START_ to _END_ of _TOTAL_ records',

                infoEmpty:
                    'No records found',

                zeroRecords:
                    'No matching records found'

            }

        };

    }


    /*
    |--------------------------------------------------------------------------
    | Member Register
    |--------------------------------------------------------------------------
    */

    let membershipTable =
        null;


    function initialiseMembershipTable() {

        if (membershipTable) {
            return;
        }

        membershipTable =
            $('#membership-report-table')
                .DataTable(
                    serverSideOptions(

                        @json(
                            route(
                                'pensions-administration.updates.reports.membership.members-data'
                            )
                        ),

                        [

                            {
                                data:
                                    'member_number'
                            },

                            {
                                data:
                                    'penad_member_number'
                            },

                            {
                                data:
                                    'fundworx_member_number'
                            },

                            {
                                data:
                                    'member'
                            },

                            {
                                data:
                                    'national_id'
                            },

                            {
                                data:
                                    'date_of_birth'
                            },

                            {
                                data:
                                    'gender'
                            },

                            {
                                data:
                                    'employer'
                            },

                            {
                                data:
                                    'staff_number'
                            },

                            {
                                data:
                                    'vote_number'
                            },

                            {
                                data:
                                    'date_joined_fund'
                            },

                            {
                                data:
                                    'status'
                            }

                        ],

                        3,
                        'asc'

                    )
                );

    }


    /*
    |--------------------------------------------------------------------------
    | Age
    |--------------------------------------------------------------------------
    */

    let ageTable =
        null;


    function initialiseAgeTable() {

        if (ageTable) {
            return;
        }

        ageTable =
            $('#age-profile-table')
                .DataTable(
                    serverSideOptions(

                        @json(
                            route(
                                'pensions-administration.updates.reports.membership.age-data'
                            )
                        ),

                        [

                            {
                                data:
                                    'member_number'
                            },

                            {
                                data:
                                    'penad_member_number'
                            },

                            {
                                data:
                                    'member'
                            },

                            {
                                data:
                                    'national_id'
                            },

                            {
                                data:
                                    'date_of_birth'
                            },

                            {
                                data:
                                    'age'
                            },

                            {
                                data:
                                    'employer'
                            },

                            {
                                data:
                                    'status'
                            }

                        ],

                        5,
                        'desc'

                    )
                );

    }


    /*
    |--------------------------------------------------------------------------
    | Legacy
    |--------------------------------------------------------------------------
    */

    let legacyTable =
        null;


    function initialiseLegacyTable() {

        if (legacyTable) {
            return;
        }

        legacyTable =
            $('#legacy-mapping-table')
                .DataTable(
                    serverSideOptions(

                        @json(
                            route(
                                'pensions-administration.updates.reports.membership.legacy-data'
                            )
                        ),

                        [

                            {
                                data:
                                    'member_number'
                            },

                            {
                                data:
                                    'penad_member_number'
                            },

                            {
                                data:
                                    'fundworx_member_number'
                            },

                            {
                                data:
                                    'member'
                            },

                            {
                                data:
                                    'national_id'
                            },

                            {
                                data:
                                    'employer'
                            },

                            {
                                data:
                                    'status'
                            },

                            {
                                data:
                                    'mapping_status'
                            },

                            {
                                data:
                                    'duplicate_check',

                                orderable:
                                    false,

                                searchable:
                                    false
                            }

                        ],

                        3,
                        'asc'

                    )
                );

    }


    /*
    |--------------------------------------------------------------------------
    | Data Quality
    |--------------------------------------------------------------------------
    */

    let qualityTable =
        null;


    function initialiseQualityTable() {

        if (qualityTable) {
            return;
        }

        qualityTable =
            $('#data-quality-table')
                .DataTable(
                    serverSideOptions(

                        @json(
                            route(
                                'pensions-administration.updates.reports.membership.data-quality-data'
                            )
                        ),

                        [

                            {
                                data:
                                    'member_number'
                            },

                            {
                                data:
                                    'member'
                            },

                            {
                                data:
                                    'national_id'
                            },

                            {
                                data:
                                    'date_of_birth'
                            },

                            {
                                data:
                                    'penad_member_number'
                            },

                            {
                                data:
                                    'fundworx_member_number'
                            },

                            {
                                data:
                                    'employer'
                            },

                            {
                                data:
                                    'exceptions',

                                orderable:
                                    false,

                                searchable:
                                    false
                            },

                            {
                                data:
                                    'action',

                                orderable:
                                    false,

                                searchable:
                                    false
                            }

                        ],

                        1,
                        'asc'

                    )
                );

    }


    /*
    |--------------------------------------------------------------------------
    | Show Missing Employer Members
    |--------------------------------------------------------------------------
    |
    | NO PAGE RELOAD.
    |
    */

    function showMissingEmployerMembers() {

        /*
        |--------------------------------------------------------------------------
        | Set Only The Dynamic Exception Filter
        |--------------------------------------------------------------------------
        */

        reportFilters.data_quality =
            'missing_employer';


        /*
        |--------------------------------------------------------------------------
        | Heading
        |--------------------------------------------------------------------------
        */

        $('#data-quality-description')
            .text(
                'Showing members who do not currently have an employer assigned.'
            );


        /*
        |--------------------------------------------------------------------------
        | Highlight Filter
        |--------------------------------------------------------------------------
        */

        $('#missing-employer-quality-card')
            .addClass(
                'missing-employer-active'
            );


        /*
        |--------------------------------------------------------------------------
        | Show Clear Filter Button
        |--------------------------------------------------------------------------
        */

        $('#show-all-exceptions-button')
            .removeClass(
                'd-none'
            );


        /*
        |--------------------------------------------------------------------------
        | Open Data Quality Tab
        |--------------------------------------------------------------------------
        */

        const tabButton =
            document.querySelector(
                '[data-bs-target="#data-quality"]'
            );


        if (tabButton) {

            bootstrap.Tab
                .getOrCreateInstance(
                    tabButton
                )
                .show();

        }


        /*
        |--------------------------------------------------------------------------
        | Load Only Missing Employer Records
        |--------------------------------------------------------------------------
        */

        if (qualityTable) {

            qualityTable
                .search(
                    ''
                );

            qualityTable
                .ajax
                .reload(
                    null,
                    true
                );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Show All Data Quality Exceptions
    |--------------------------------------------------------------------------
    */

    function showAllExceptions() {

        reportFilters.data_quality =
            null;


        $('#data-quality-description')
            .text(
                'Members with missing key static information.'
            );


        $('#missing-employer-quality-card')
            .removeClass(
                'missing-employer-active'
            );


        $('#show-all-exceptions-button')
            .addClass(
                'd-none'
            );


        if (qualityTable) {

            qualityTable
                .search(
                    ''
                );

            qualityTable
                .ajax
                .reload(
                    null,
                    true
                );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Missing Employer Clicks
    |--------------------------------------------------------------------------
    */

    $('#missing-employer-card')
        .on(
            'click',
            function () {

                showMissingEmployerMembers();

            }
        );


    $('#missing-employer-quality-card')
        .on(
            'click',
            function () {

                showMissingEmployerMembers();

            }
        );


    $('#show-all-exceptions-button')
        .on(
            'click',
            function () {

                showAllExceptions();

            }
        );


    /*
    |--------------------------------------------------------------------------
    | Lazy Load Tabs
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            'button[data-bs-toggle="tab"]'
        )
        .forEach(
            function (tab) {

                tab.addEventListener(
                    'shown.bs.tab',
                    function (event) {

                        const target =
                            event.target
                                .getAttribute(
                                    'data-bs-target'
                                );


                        switch (target) {

                            case '#member-register':

                                initialiseMembershipTable();

                                break;


                            case '#age-profile':

                                initialiseAgeTable();

                                break;


                            case '#legacy-mapping':

                                initialiseLegacyTable();

                                break;


                            case '#data-quality':

                                /*
                                |--------------------------------------------------------------------------
                                | Initialise only on first visit.
                                |--------------------------------------------------------------------------
                                */

                                initialiseQualityTable();

                                break;

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Adjust Visible Table Width
                        |--------------------------------------------------------------------------
                        */

                        setTimeout(
                            function () {

                                $.fn.dataTable
                                    .tables({
                                        visible:
                                            true,

                                        api:
                                            true
                                    })
                                    .columns
                                    .adjust();

                            },
                            100
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | Hash Only
                        |--------------------------------------------------------------------------
                        |
                        | replaceState does not reload the page.
                        |
                        */

                        if (target) {

                            history.replaceState(
                                null,
                                '',
                                target
                            );

                        }

                    }
                );

            }
        );


    /*
    |--------------------------------------------------------------------------
    | Existing Hash
    |--------------------------------------------------------------------------
    */

    if (
        window.location.hash
    ) {

        const targetTab =
            document.querySelector(
                '[data-bs-target="'
                +
                window.location.hash
                +
                '"]'
            );


        if (targetTab) {

            bootstrap.Tab
                .getOrCreateInstance(
                    targetTab
                )
                .show();

        }

    }

});

</script>

@endpush
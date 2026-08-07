@extends('layouts.app')

@section('title', 'Pensions Administration Dashboard')

@section('page-heading', 'Pensions Administration')

@section('page-subheading')
    Member administration, benefit claims and pension payroll
@endsection

@section('content')

<div class="dashboard-welcome">

    <div>
        <h2>
            Welcome, {{ auth()->user()->first_name }}
        </h2>

        <p>
            Select a pensions administration function below.
            Access is based on your assigned roles and permissions.
        </p>
    </div>

</div>


<div class="dashboard-section">

    <div class="section-heading">
        <h2>Pensions Administration Modules</h2>

        <p>
            Your authorised pension administration functions.
        </p>
    </div>


    <div class="dashboard-grid">

        {{-- Member Updates --}}
        @can('updates.members.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-person-lines-fill"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>Member Updates</h3>

                    <p>
                        Maintain member records, contributions
                        and member information.
                    </p>
                </div>
            </a>
        @endcan


        {{-- Benefit Claims --}}
        @can('claims.claims.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-file-earmark-medical"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>Benefit Claims</h3>

                    <p>
                        Process retirement, withdrawal, death
                        and other benefit claims.
                    </p>
                </div>
            </a>
        @endcan


        {{-- Pension Payroll --}}
        @can('payroll.payroll-runs.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-cash-coin"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>Pension Payroll</h3>

                    <p>
                        Process pension payrolls, payments and
                        payroll reports.
                    </p>
                </div>
            </a>
        @endcan


        {{-- Pensioners --}}
        @can('pensioners.pensioners.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-person-vcard"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>Pensioners</h3>

                    <p>
                        View and manage pensioner records.
                    </p>
                </div>
            </a>
        @endcan


        {{-- Contributions --}}
        @can('contributions.contributions.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-wallet2"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>Contributions</h3>

                    <p>
                        Review member and employer contribution
                        records.
                    </p>
                </div>
            </a>
        @endcan


        {{-- Reports --}}
        @can('pensions.reports.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-bar-chart-line"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>Reports</h3>

                    <p>
                        Access pensions administration reports.
                    </p>
                </div>
            </a>
        @endcan

    </div>

</div>


@if(
    !auth()->user()->can('updates.members.view')
    && !auth()->user()->can('claims.claims.view')
    && !auth()->user()->can('payroll.payroll-runs.view')
    && !auth()->user()->can('pensioners.pensioners.view')
    && !auth()->user()->can('contributions.contributions.view')
    && !auth()->user()->can('pensions.reports.view')
)
    <div class="empty-state">

        <i class="bi bi-shield-lock"></i>

        <h3>No Pensions Modules Assigned</h3>

        <p>
            You currently do not have permission to access any
            Pensions Administration modules.
        </p>

    </div>
@endif

@endsection
@extends('layouts.app')

@section('title', 'Principal Officer Dashboard')

@section('page-heading', "Principal Officer's Office")

@section('page-subheading')
    Executive oversight and corporate administration
@endsection

@section('content')

<div class="dashboard-welcome executive">

    <div>
        <h2>
            Welcome, {{ auth()->user()->first_name }}
        </h2>

        <p>
            Access authorised executive, HR, procurement and
            corporate administration functions.
        </p>
    </div>

</div>


<div class="dashboard-section">

    <div class="section-heading">

        <h2>Principal Office Functions</h2>

        <p>
            Only functions assigned to your account are displayed.
        </p>

    </div>


    <div class="dashboard-grid">

        {{-- Executive Overview --}}
        @can('principal-office.executive-dashboard.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-speedometer2"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>Executive Overview</h3>

                    <p>
                        View high-level organisational information
                        and performance indicators.
                    </p>
                </div>
            </a>
        @endcan


        {{-- Human Resources --}}
        @can('hr.employees.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-person-workspace"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>Human Resources</h3>

                    <p>
                        Access authorised human resources functions.
                    </p>
                </div>
            </a>
        @endcan


        {{-- Procurement --}}
        @can('procurement.procurement.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-cart-check"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>Procurement</h3>

                    <p>
                        Access procurement requests and processes.
                    </p>
                </div>
            </a>
        @endcan


        {{-- Approvals --}}
        @can('principal-office.approvals.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-check2-square"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>Approvals</h3>

                    <p>
                        Review items requiring executive approval.
                    </p>
                </div>
            </a>
        @endcan


        {{-- Reports --}}
        @can('principal-office.reports.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>Management Reports</h3>

                    <p>
                        Access authorised executive reports.
                    </p>
                </div>
            </a>
        @endcan

    </div>

</div>

@endsection
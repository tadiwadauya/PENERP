@extends('layouts.app')

@section('title', 'Finance Dashboard')

@section('page-heading', 'Finance Dashboard')

@section('page-subheading')
    Finance Department
@endsection

@section('content')

<div class="dashboard-welcome">

    <div>
        <h2>
            Welcome, {{ auth()->user()->first_name }}
        </h2>

        <p>
            Access the Finance Department modules and functions
            assigned to your account.
        </p>
    </div>

</div>


<div class="dashboard-section">

    <div class="section-heading">
        <h2>Finance Modules</h2>
        <p>
            Only modules you are authorised to access are shown.
        </p>
    </div>


    <div class="dashboard-grid">

        {{-- General Ledger --}}
        @can('finance.general-ledger.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-journal-text"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>General Ledger</h3>

                    <p>
                        Manage and review general ledger
                        transactions.
                    </p>
                </div>
            </a>
        @endcan


        {{-- Receipts --}}
        @can('finance.receipts.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-receipt"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>Receipts</h3>

                    <p>
                        Process and review receipts.
                    </p>
                </div>
            </a>
        @endcan


        {{-- Payments --}}
        @can('finance.payments.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-cash-stack"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>Payments</h3>

                    <p>
                        Manage authorised payments.
                    </p>
                </div>
            </a>
        @endcan


        {{-- ICT --}}
        @can('ict.dashboard.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-pc-display-horizontal"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>ICT</h3>

                    <p>
                        Access ICT administration and technology
                        services.
                    </p>
                </div>
            </a>
        @endcan

    </div>

</div>


@if(
    !auth()->user()->can('finance.general-ledger.view')
    && !auth()->user()->can('finance.receipts.view')
    && !auth()->user()->can('finance.payments.view')
    && !auth()->user()->can('ict.dashboard.view')
)
    <div class="empty-state">

        <i class="bi bi-shield-lock"></i>

        <h3>No Finance Modules Assigned</h3>

        <p>
            You currently do not have permission to access any
            Finance modules.
        </p>

    </div>
@endif

@endsection
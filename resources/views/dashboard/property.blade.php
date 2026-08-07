@extends('layouts.app')

@section('title', 'Property Dashboard')

@section('page-heading', 'Property Dashboard')

@section('page-subheading')
    Property management and administration
@endsection

@section('content')

<div class="dashboard-welcome">

    <div>
        <h2>
            Welcome, {{ auth()->user()->first_name }}
        </h2>

        <p>
            Access property management functions assigned to
            your account.
        </p>
    </div>

</div>


<div class="dashboard-section">

    <div class="section-heading">

        <h2>Property Modules</h2>

        <p>
            Select an authorised property function.
        </p>

    </div>


    <div class="dashboard-grid">

        @can('property.properties.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-buildings"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>Properties</h3>

                    <p>
                        View and maintain LAPF property records.
                    </p>
                </div>
            </a>
        @endcan


        @can('property.tenants.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-people"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>Tenants</h3>

                    <p>
                        Manage tenants and occupancy information.
                    </p>
                </div>
            </a>
        @endcan


        @can('property.leases.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-file-earmark-text"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>Leases</h3>

                    <p>
                        Maintain lease and tenancy agreements.
                    </p>
                </div>
            </a>
        @endcan


        @can('property.rentals.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-cash-stack"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>Rentals</h3>

                    <p>
                        Review rental charges and property income.
                    </p>
                </div>
            </a>
        @endcan


        @can('property.maintenance.view')
            <a
                href="#"
                class="dashboard-card"
            >
                <div class="dashboard-card-icon">
                    <i class="bi bi-tools"></i>
                </div>

                <div class="dashboard-card-body">
                    <h3>Maintenance</h3>

                    <p>
                        Track repairs and property maintenance.
                    </p>
                </div>
            </a>
        @endcan

    </div>

</div>

@endsection
@extends('layouts.app')

@section('title', 'Pension Benefit Settings')
@section('page-heading', 'Pension Benefit Settings')

@section('content')

@include('pensions-administration.partials.navigation')

<div class="row">

    {{-- =====================================================
         GENERAL BENEFIT RULES
    ====================================================== --}}
    <div class="col-xl-4 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-20">
                            <i class="mdi mdi-tune-variant"></i>
                        </span>
                    </div>

                    <div class="flex-grow-1">
                        <h5 class="mb-1">General Benefit Rules</h5>
                        <div class="text-muted">
                            {{ number_format($counts['general_settings'] ?? 0) }} configured rule(s)
                        </div>
                    </div>
                </div>

                <a href="{{ route('pensions-administration.settings.general.index') }}" class="btn btn-primary btn-sm mt-3">
                    Open Settings
                </a>
            </div>
        </div>
    </div>

    {{-- =====================================================
         WITHDRAWAL SCALES
    ====================================================== --}}
    <div class="col-xl-4 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-20">
                            <i class="mdi mdi-format-list-numbered"></i>
                        </span>
                    </div>

                    <div class="flex-grow-1">
                        <h5 class="mb-1">Withdrawal Scales</h5>
                        <div class="text-muted">
                            {{ number_format($counts['withdrawal_scales'] ?? 0) }} configured scale(s)
                        </div>
                    </div>
                </div>

                <a href="{{ route('pensions-administration.settings.withdrawal-scales.index') }}" class="btn btn-primary btn-sm mt-3">
                    Open Settings
                </a>
            </div>
        </div>
    </div>

    {{-- =====================================================
         ACCUMULATED INTEREST FACTORS
    ====================================================== --}}
    <div class="col-xl-4 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-20">
                            <i class="mdi mdi-percent"></i>
                        </span>
                    </div>

                    <div class="flex-grow-1">
                        <h5 class="mb-1">Accumulated Interest Factors</h5>
                        <div class="text-muted">
                            {{ number_format($counts['accumulated_interest_factors'] ?? 0) }} configured factor(s)
                        </div>
                    </div>
                </div>

                <a href="{{ route('pensions-administration.settings.accumulated-interest-factors.index') }}" class="btn btn-primary btn-sm mt-3">
                    Open Factors
                </a>
            </div>
        </div>
    </div>

    {{-- =====================================================
         COMMUTATION FACTORS
    ====================================================== --}}
    <div class="col-xl-4 col-md-6 mt-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-20">
                            <i class="mdi mdi-calculator-variant"></i>
                        </span>
                    </div>

                    <div class="flex-grow-1">
                        <h5 class="mb-1">Commutation Factors</h5>
                        <div class="text-muted">
                            {{ number_format($counts['commutation_factors'] ?? 0) }} configured factor(s)
                        </div>
                    </div>
                </div>

                <a href="{{ route('pensions-administration.settings.commutation-factors.index') }}" class="btn btn-primary btn-sm mt-3">
                    Open Factors
                </a>
            </div>
        </div>
    </div>

    {{-- =====================================================
         RETIREMENT INCREASE FACTORS
    ====================================================== --}}
    <div class="col-xl-4 col-md-6 mt-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-20">
                            <i class="mdi mdi-chart-line"></i>
                        </span>
                    </div>

                    <div class="flex-grow-1">
                        <h5 class="mb-1">Retirement Increase Factors</h5>
                        <div class="text-muted">
                            {{ number_format($counts['retirement_increase_factors'] ?? 0) }} configured factor(s)
                        </div>
                    </div>
                </div>

                <a href="{{ route('pensions-administration.settings.retirement-increases.index') }}" class="btn btn-primary btn-sm mt-3">
                    Open Factors
                </a>
            </div>
        </div>
    </div>

    {{-- =====================================================
         TAX TABLES
    ====================================================== --}}
    <div class="col-xl-4 col-md-6 mt-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-20">
                            <i class="mdi mdi-table"></i>
                        </span>
                    </div>

                    <div class="flex-grow-1">
                        <h5 class="mb-1">Tax Tables</h5>
                        <div class="text-muted">
                            {{ number_format($counts['tax_tables'] ?? 0) }} configured tax table(s)
                        </div>
                    </div>
                </div>

                <a href="{{ route('pensions-administration.settings.tax-tables.index') }}" class="btn btn-primary btn-sm mt-3">
                    Open Tax Tables
                </a>
            </div>
        </div>
    </div>

    {{-- =====================================================
         EXCHANGE RATES
    ====================================================== --}}
    <div class="col-xl-4 col-md-6 mt-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-20">
                            <i class="mdi mdi-currency-usd"></i>
                        </span>
                    </div>

                    <div class="flex-grow-1">
                        <h5 class="mb-1">Exchange Rates</h5>
                        <div class="text-muted">
                            {{ number_format($counts['exchange_rates'] ?? 0) }} configured rate(s)
                        </div>
                    </div>
                </div>

                <a href="{{ route('pensions-administration.settings.exchange-rates.index') }}" class="btn btn-primary btn-sm mt-3">
                    Open Exchange Rates
                </a>
            </div>
        </div>
    </div>
    {{-- =====================================================
     INTEREST RATES
====================================================== --}}
<div class="col-xl-4 col-md-6 mt-4">
    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="avatar-sm me-3">
                    <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-20">
                        <i class="mdi mdi-percent-outline"></i>
                    </span>
                </div>

                <div class="flex-grow-1">
                    <h5 class="mb-1">Interest Rates</h5>
                    <div class="text-muted">
                        {{ number_format($counts['interest_rates'] ?? 0) }} configured rate(s)
                    </div>
                </div>
            </div>

            <a href="{{ route('pensions-administration.settings.interest-rates.index') }}" class="btn btn-primary btn-sm mt-3">
                Open Interest Rates
            </a>
        </div>
    </div>
</div>

</div>

<div class="alert alert-info mt-4 mb-0">
    <div class="d-flex align-items-start">
        <i class="mdi mdi-information-outline font-size-20 me-2"></i>
        <div>
            <strong>Controlled Benefit Settings</strong>
            <div class="mt-1">
                Pension calculation rules, actuarial factors, tax tables and exchange rates are controlled settings.
                Changes must be effective-dated and audited.
            </div>
        </div>
    </div>
</div>

@endsection
@extends('layouts.app')

@section('title', 'Interest Rate Details')
@section('page-heading', 'Interest Rate Details')
@section('page-subheading', 'View an effective-dated LAPF interest rate')

@section('content')

@include('pensions-administration.partials.navigation')

@php
    $today = today();
    $isHistorical = $interestRate->effective_to && $interestRate->effective_to->lt($today);
    $isFuture = $interestRate->effective_from->gt($today);
    $isCurrent = $interestRate->is_active && $interestRate->effective_from->lte($today) && (!$interestRate->effective_to || $interestRate->effective_to->gte($today));
@endphp

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="header-title mb-1">{{ number_format((float) $interestRate->rate_percentage, 2) }}% Per Annum</h4>
                <p class="text-muted mb-0">Effective from {{ $interestRate->effective_from->format('d M Y') }}</p>
            </div>

            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="{{ route('pensions-administration.settings.interest-rates.index') }}" class="btn btn-light"><i class="mdi mdi-arrow-left me-1"></i>Back</a>

                @can('pensions.settings.manage')
                    @if($interestRate->is_active && $isFuture)
                        <a href="{{ route('pensions-administration.settings.interest-rates.edit', $interestRate) }}" class="btn btn-primary"><i class="mdi mdi-pencil-outline me-1"></i>Edit</a>
                    @elseif($interestRate->is_active && $isCurrent)
                        <a href="{{ route('pensions-administration.settings.interest-rates.version.create', $interestRate) }}" class="btn btn-primary"><i class="mdi mdi-source-branch me-1"></i>New Version</a>
                    @endif
                @endcan
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Interest Rate</p>
                <h3 class="mb-0">{{ number_format((float) $interestRate->rate_percentage, 2) }}%</h3>
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Effective From</p>
                <h5 class="mb-0">{{ $interestRate->effective_from->format('d M Y') }}</h5>
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Effective To</p>
                <h5 class="mb-0">{{ $interestRate->effective_to ? $interestRate->effective_to->format('d M Y') : 'Current' }}</h5>
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Status</p>

                @if(!$interestRate->is_active)
                    <span class="badge bg-secondary">Inactive</span>
                @elseif($isCurrent)
                    <span class="badge bg-success">Current</span>
                @elseif($isFuture)
                    <span class="badge bg-info text-dark">Future</span>
                @elseif($isHistorical)
                    <span class="badge bg-light text-dark">Historical</span>
                @else
                    <span class="badge bg-secondary">Inactive</span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h4 class="header-title mb-3">Rate Information</h4>

        <div class="row g-3">
            <div class="col-lg-6">
                <p class="text-muted mb-1">Source / Authority</p>
                <strong>{{ $interestRate->source_authority ?: '-' }}</strong>
            </div>

            <div class="col-lg-6">
                <p class="text-muted mb-1">Record Status</p>
                <strong>{{ $interestRate->is_active ? 'Active' : 'Inactive' }}</strong>
            </div>

            <div class="col-lg-12">
                <p class="text-muted mb-1">Notes</p>
                <span>{{ $interestRate->notes ?: '-' }}</span>
            </div>

            <div class="col-lg-6">
                <p class="text-muted mb-1">Created At</p>
                <span>{{ $interestRate->created_at?->format('d M Y H:i') ?: '-' }}</span>
            </div>

            <div class="col-lg-6">
                <p class="text-muted mb-1">Last Updated</p>
                <span>{{ $interestRate->updated_at?->format('d M Y H:i') ?: '-' }}</span>
            </div>
        </div>
    </div>
</div>

@endsection
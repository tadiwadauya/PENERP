@extends('layouts.app')

@section('title', 'Tax Table Details')
@section('page-heading', 'Tax Table Details')
@section('page-subheading', 'View the tax table and the complete set of tax bands')

@section('content')

@include('pensions-administration.partials.navigation')

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="header-title mb-1">{{ $taxTable->name }}</h4>
                <p class="text-muted mb-0">{{ $taxTable->tax_year }} | {{ $taxTable->currency }} | {{ $taxTable->benefit_type }}</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="{{ route('pensions-administration.settings.tax-tables.index') }}" class="btn btn-light"><i class="mdi mdi-arrow-left me-1"></i>Back</a>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3"><p class="text-muted mb-1">Tax Year</p><strong>{{ $taxTable->tax_year }}</strong></div>
            <div class="col-md-3"><p class="text-muted mb-1">Currency</p><strong>{{ $taxTable->currency }}</strong></div>
            <div class="col-md-3"><p class="text-muted mb-1">Effective From</p><strong>{{ $taxTable->effective_from?->format('d M Y') }}</strong></div>
            <div class="col-md-3"><p class="text-muted mb-1">Effective To</p><strong>{{ $taxTable->effective_to?->format('d M Y') ?? 'Open Ended' }}</strong></div>
            <div class="col-md-6"><p class="text-muted mb-1">Source / Authority</p><strong>{{ $taxTable->source_authority ?: '-' }}</strong></div>
            <div class="col-md-6"><p class="text-muted mb-1">Notes</p><span>{{ $taxTable->notes ?: '-' }}</span></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h4 class="header-title mb-3">Tax Bands</h4>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Band</th>
                        <th>Lower Limit</th>
                        <th>Upper Limit</th>
                        <th>Rate</th>
                        <th>Fixed Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($taxTable->bands as $band)
                        <tr>
                            <td>{{ $band->band_order }}</td>
                            <td>{{ number_format((float) $band->lower_limit, 2) }}</td>
                            <td>{{ $band->upper_limit !== null ? number_format((float) $band->upper_limit, 2) : 'No Maximum' }}</td>
                            <td>{{ rtrim(rtrim(number_format((float) $band->rate_percentage, 4, '.', ''), '0'), '.') }}%</td>
                            <td>{{ number_format((float) $band->fixed_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
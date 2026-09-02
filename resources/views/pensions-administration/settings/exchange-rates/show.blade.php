@extends('layouts.app')

@section('title', 'Exchange Rate Details')
@section('page-heading', 'Exchange Rate Details')
@section('page-subheading', 'View an exchange rate and its contribution receipt usage')

@section('content')

@include('pensions-administration.partials.navigation')

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="header-title mb-1">{{ $exchangeRate->from_currency }} → {{ $exchangeRate->to_currency }}</h4>
                <p class="text-muted mb-0">Rate date {{ $exchangeRate->rate_date?->format('d M Y') }}</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="{{ route('pensions-administration.settings.exchange-rates.index') }}" class="btn btn-light"><i class="mdi mdi-arrow-left me-1"></i>Back</a>

                @can('pensions.settings.manage')
                    @if($usageCount === 0)
                        <a href="{{ route('pensions-administration.settings.exchange-rates.edit', $exchangeRate) }}" class="btn btn-primary"><i class="mdi mdi-pencil-outline me-1"></i>Edit</a>
                    @endif
                @endcan
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-3">
        <div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Exchange Rate</p><h4 class="mb-0">{{ rtrim(rtrim(number_format((float) $exchangeRate->rate, 8, '.', ''), '0'), '.') }}</h4></div></div>
    </div>
    <div class="col-lg-3">
        <div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Currency Pair</p><h4 class="mb-0">{{ $exchangeRate->from_currency }}/{{ $exchangeRate->to_currency }}</h4></div></div>
    </div>
    <div class="col-lg-3">
        <div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Receipt Usage</p><h4 class="mb-0">{{ number_format($usageCount) }}</h4></div></div>
    </div>
    <div class="col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Status</p>
                @if($usageCount > 0)
                    <span class="badge bg-success"><i class="mdi mdi-lock-outline me-1"></i>Used / Locked</span>
                @else
                    <span class="badge bg-info text-dark">Unused / Editable</span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><p class="text-muted mb-1">Rate Date</p><strong>{{ $exchangeRate->rate_date?->format('d M Y') }}</strong></div>
            <div class="col-md-4"><p class="text-muted mb-1">Source</p><strong>{{ $exchangeRate->source ?: '-' }}</strong></div>
            <div class="col-md-4"><p class="text-muted mb-1">Created</p><strong>{{ $exchangeRate->created_at?->format('d M Y H:i') }}</strong></div>
            <div class="col-12"><p class="text-muted mb-1">Notes</p><span>{{ $exchangeRate->notes ?: '-' }}</span></div>
        </div>
    </div>
</div>

@if($usageCount > 0)
<div class="card">
    <div class="card-body">
        <h4 class="header-title mb-1">Receipt Usage</h4>
        <p class="text-muted mb-3">Showing up to the most recent 20 receipts linked to this rate.</p>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Receipt Date</th>
                        <th>Currency</th>
                        <th>Original Amount</th>
                        <th>Stored Rate</th>
                        <th>ZWG Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentReceipts as $receipt)
                        <tr>
                            <td>{{ $receipt->receipt_date?->format('d M Y') }}</td>
                            <td>{{ $receipt->currency }}</td>
                            <td>{{ number_format((float) $receipt->original_amount, 2) }}</td>
                            <td>{{ rtrim(rtrim(number_format((float) $receipt->exchange_rate, 8, '.', ''), '0'), '.') }}</td>
                            <td>{{ number_format((float) $receipt->amount_zwg, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endsection
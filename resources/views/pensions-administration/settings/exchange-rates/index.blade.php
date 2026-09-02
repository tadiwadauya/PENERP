@extends('layouts.app')

@section('title', 'Exchange Rates')

@section('page-heading', 'Exchange Rates')

@section('page-subheading')
Manage exchange rates used for pension contributions and benefit calculations
@endsection

@push('styles')
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<style>
    .rate-stat-card { height: 100%; border: 0; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
    .rate-table th { white-space: nowrap; vertical-align: middle; }
    .rate-table td { vertical-align: middle; }
    .rate-actions { min-width: 170px; white-space: nowrap; }
</style>
@endpush

@section('content')

@include('pensions-administration.partials.navigation')

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card rate-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Total Rates</p>
                <h3 class="mb-0">{{ number_format($summary['total']) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card rate-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Used / Locked</p>
                <h3 class="text-success mb-0">{{ number_format($summary['used']) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card rate-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Unused / Editable</p>
                <h3 class="text-primary mb-0">{{ number_format($summary['unused']) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card rate-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Latest Rate Date</p>
                <h5 class="mb-0">{{ $summary['latest_date'] ? \Carbon\Carbon::parse($summary['latest_date'])->format('d M Y') : '-' }}</h5>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="header-title mb-1">Exchange Rate Register</h4>
                <p class="text-muted mb-0">Rates already referenced by contribution receipts are automatically locked against further modification.</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                @can('pensions.settings.manage')
                    <a href="{{ route('pensions-administration.settings.exchange-rates.create') }}" class="btn btn-primary"><i class="mdi mdi-plus me-1"></i>Add Exchange Rate</a>
                @endcan
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="exchangeRatesTable" class="table table-bordered table-striped table-hover rate-table w-100">
                <thead>
                    <tr>
                        <th>Rate Date</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Exchange Rate</th>
                        <th>Source</th>
                        <th>Usage</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rates as $rate)
                        @php($isUsed = $rate->usage_count > 0)

                        <tr>
                            <td data-order="{{ $rate->rate_date?->format('Ymd') }}">{{ $rate->rate_date?->format('d M Y') }}</td>
                            <td><span class="badge bg-light text-dark">{{ $rate->from_currency }}</span></td>
                            <td><span class="badge bg-light text-dark">{{ $rate->to_currency }}</span></td>
                            <td data-order="{{ (float) $rate->rate }}"><strong>{{ rtrim(rtrim(number_format((float) $rate->rate, 8, '.', ''), '0'), '.') }}</strong></td>
                            <td>{{ $rate->source ?: '-' }}</td>
                            <td data-order="{{ $rate->usage_count }}">{{ number_format($rate->usage_count) }} {{ \Illuminate\Support\Str::plural('receipt', $rate->usage_count) }}</td>
                            <td>
                                @if($isUsed)
                                    <span class="badge bg-success"><i class="mdi mdi-lock-outline me-1"></i>Used / Locked</span>
                                @else
                                    <span class="badge bg-info text-dark"><i class="mdi mdi-pencil-outline me-1"></i>Unused / Editable</span>
                                @endif
                            </td>
                            <td>{{ $rate->notes ? \Illuminate\Support\Str::limit($rate->notes, 60) : '-' }}</td>
                            <td class="text-end rate-actions">
                                <a href="{{ route('pensions-administration.settings.exchange-rates.show', $rate) }}" class="btn btn-sm btn-outline-secondary"><i class="mdi mdi-eye-outline me-1"></i>View</a>

                                @can('pensions.settings.manage')
                                    @unless($isUsed)
                                        <a href="{{ route('pensions-administration.settings.exchange-rates.edit', $rate) }}" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-pencil-outline me-1"></i>Edit</a>
                                    @endunless
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script>
$(document).ready(function () {
    $('#exchangeRatesTable').DataTable({
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10,25,50,100,-1],[10,25,50,100,'All']],
        order: [[0,'desc']],
        columnDefs: [{ targets: 8, orderable: false, searchable: false }],
        language: {
            search: 'Search:',
            searchPlaceholder: 'Search exchange rates...',
            lengthMenu: 'Show _MENU_ records',
            info: 'Showing _START_ to _END_ of _TOTAL_ exchange rates',
            infoEmpty: 'No exchange rates available',
            zeroRecords: 'No matching exchange rates found'
        }
    });
});
</script>
@endpush
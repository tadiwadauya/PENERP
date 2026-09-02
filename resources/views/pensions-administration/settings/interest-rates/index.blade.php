@extends('layouts.app')

@section('title', 'Interest Rates')
@section('page-heading', 'Interest Rates')
@section('page-subheading', 'Manage LAPF effective-dated interest rates')

@push('styles')
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<style>
    .interest-stat-card { height: 100%; border: 0; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
    .interest-rate-table th { white-space: nowrap; vertical-align: middle; }
    .interest-rate-table td { vertical-align: middle; }
    .interest-rate-actions { min-width: 230px; white-space: nowrap; }
</style>
@endpush

@section('content')

@include('pensions-administration.partials.navigation')

@php
    $today = today();
@endphp

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card interest-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Total Rates</p>
                <h3 class="mb-0">{{ number_format($summary['total']) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card interest-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Current Rate</p>
                @if($summary['current'])
                    <h3 class="mb-0">{{ number_format((float) $summary['current']->rate_percentage, 2) }}%</h3>
                    <small class="text-muted">From {{ $summary['current']->effective_from->format('d M Y') }}</small>
                @else
                    <h3 class="mb-0">-</h3>
                    <small class="text-muted">No current rate</small>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card interest-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Historical Rates</p>
                <h3 class="mb-0">{{ number_format($summary['historical']) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card interest-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Future Rates</p>
                <h3 class="mb-0">{{ number_format($summary['future']) }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="header-title mb-1">LAPF Interest Rate Schedule</h4>
                <p class="text-muted mb-0">Effective-dated interest rates used by applicable PENERP calculations. Historical rates are retained for calculation and audit purposes.</p>
            </div>

            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                @can('pensions.settings.manage')
                    <a href="{{ route('pensions-administration.settings.interest-rates.create') }}" class="btn btn-primary"><i class="mdi mdi-plus me-1"></i>Add Interest Rate</a>
                @endcan
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="interestRatesTable" class="table table-bordered table-striped table-hover interest-rate-table w-100">
                <thead>
                    <tr>
                        <th>Effective From</th>
                        <th>Effective To</th>
                        <th>Rate Per Annum</th>
                        <th>Source / Authority</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rates as $rate)
                        @php
                            $isHistorical = $rate->effective_to && $rate->effective_to->lt($today);
                            $isFuture = $rate->effective_from->gt($today);
                            $isCurrent = $rate->is_active && $rate->effective_from->lte($today) && (!$rate->effective_to || $rate->effective_to->gte($today));
                        @endphp

                        <tr>
                            <td data-order="{{ $rate->effective_from->format('Ymd') }}">{{ $rate->effective_from->format('d M Y') }}</td>
                            <td data-order="{{ $rate->effective_to?->format('Ymd') ?: '99999999' }}">{{ $rate->effective_to ? $rate->effective_to->format('d M Y') : 'Current' }}</td>
                            <td data-order="{{ (float) $rate->rate_percentage }}"><strong>{{ number_format((float) $rate->rate_percentage, 2) }}%</strong></td>
                            <td>{{ $rate->source_authority ?: '-' }}</td>

                            <td>
                                @if(!$rate->is_active)
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
                            </td>

                            <td>{{ $rate->notes ? \Illuminate\Support\Str::limit($rate->notes, 60) : '-' }}</td>

                            <td class="text-end interest-rate-actions">
                                <a href="{{ route('pensions-administration.settings.interest-rates.show', $rate) }}" class="btn btn-sm btn-outline-secondary"><i class="mdi mdi-eye-outline me-1"></i>View</a>

                                @can('pensions.settings.manage')
                                    @if($rate->is_active && $isFuture)
                                        <a href="{{ route('pensions-administration.settings.interest-rates.edit', $rate) }}" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-pencil-outline me-1"></i>Edit</a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deactivateRateModal{{ $rate->id }}"><i class="mdi mdi-cancel me-1"></i>Deactivate</button>
                                    @elseif($rate->is_active && $isCurrent)
                                        <a href="{{ route('pensions-administration.settings.interest-rates.version.create', $rate) }}" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-source-branch me-1"></i>New Version</a>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@can('pensions.settings.manage')
    @foreach($rates as $rate)
        @if($rate->is_active && $rate->effective_from->gt($today))
            <div class="modal fade" id="deactivateRateModal{{ $rate->id }}" tabindex="-1" aria-labelledby="deactivateRateModalLabel{{ $rate->id }}" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('pensions-administration.settings.interest-rates.deactivate', $rate) }}">
                            @csrf
                            @method('PATCH')

                            <div class="modal-header">
                                <h5 class="modal-title" id="deactivateRateModalLabel{{ $rate->id }}">Deactivate Interest Rate</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <p>You are about to deactivate the future interest rate of <strong>{{ number_format((float) $rate->rate_percentage, 2) }}%</strong> effective from <strong>{{ $rate->effective_from->format('d M Y') }}</strong>.</p>

                                <div class="mb-0">
                                    <label class="form-label">Change Reason <span class="text-danger">*</span></label>
                                    <textarea name="change_reason" class="form-control" rows="4" maxlength="2000" required></textarea>
                                    <small class="text-muted">This reason will be recorded in the audit trail.</small>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger"><i class="mdi mdi-cancel me-1"></i>Deactivate</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endcan

@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script>
$(document).ready(function () {
    $('#interestRatesTable').DataTable({
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10,25,50,100,-1],[10,25,50,100,'All']],
        order: [[0,'desc']],
        columnDefs: [{ targets: 6, orderable: false, searchable: false }],
        language: {
            search: 'Search:',
            searchPlaceholder: 'Search interest rates...',
            lengthMenu: 'Show _MENU_ records',
            info: 'Showing _START_ to _END_ of _TOTAL_ interest rates',
            infoEmpty: 'No interest rates available',
            zeroRecords: 'No matching interest rates found'
        }
    });
});
</script>
@endpush
@extends('layouts.app')

@section('title', 'Tax Tables')

@section('page-heading', 'Tax Tables')

@section('page-subheading')
Manage effective-dated benefit tax tables and their tax bands
@endsection

@push('styles')
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<style>
    .tax-stat-card { height: 100%; border: 0; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
    .tax-table th { white-space: nowrap; vertical-align: middle; }
    .tax-table td { vertical-align: middle; }
    .tax-actions { min-width: 250px; white-space: nowrap; }
</style>
@endpush

@section('content')

@include('pensions-administration.partials.navigation')

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6"><div class="card tax-stat-card"><div class="card-body"><p class="text-muted mb-1">Total Tables</p><h3 class="mb-0">{{ number_format($summary['total']) }}</h3></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card tax-stat-card"><div class="card-body"><p class="text-muted mb-1">Active Tables</p><h3 class="mb-0">{{ number_format($summary['active']) }}</h3></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card tax-stat-card"><div class="card-body"><p class="text-muted mb-1">Current Tables</p><h3 class="text-success mb-0">{{ number_format($summary['current']) }}</h3></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card tax-stat-card"><div class="card-body"><p class="text-muted mb-1">Future Tables</p><h3 class="text-primary mb-0">{{ number_format($summary['future']) }}</h3></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="header-title mb-1">Benefit Tax Tables</h4>
                <p class="text-muted mb-0">Each tax table contains the complete set of tax bands applicable for a particular period.</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                @can('pensions.settings.manage')
                    <a href="{{ route('pensions-administration.settings.tax-tables.create') }}" class="btn btn-primary"><i class="mdi mdi-plus me-1"></i>Add Tax Table</a>
                @endcan
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="benefitTaxTablesTable" class="table table-bordered table-striped table-hover tax-table w-100">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Tax Year</th>
                        <th>Currency</th>
                        <th>Benefit Type</th>
                        <th>Bands</th>
                        <th>Effective From</th>
                        <th>Effective To</th>
                        <th>Status</th>
                        <th>Source / Authority</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($taxTables as $taxTable)
                        @php
                            $hasTakenEffect = $taxTable->effective_from?->copy()->startOfDay()->lte(today());
                            $isCurrent = $taxTable->is_active && $hasTakenEffect && (!$taxTable->effective_to || $taxTable->effective_to?->copy()->endOfDay()->gte(today()));
                            $isFuture = $taxTable->is_active && $taxTable->effective_from?->copy()->startOfDay()->gt(today());
                            $isHistorical = $taxTable->effective_to && $taxTable->effective_to?->copy()->endOfDay()->lt(today());

                            $statusLabel = match(true) {
                                !$taxTable->is_active => 'Inactive',
                                $isCurrent => 'Current',
                                $isFuture => 'Future',
                                $isHistorical => 'Historical',
                                default => 'Inactive',
                            };

                            $badgeClass = match($statusLabel) {
                                'Current' => 'bg-success',
                                'Future' => 'bg-info text-dark',
                                'Historical' => 'bg-light text-dark',
                                default => 'bg-secondary',
                            };
                        @endphp

                        <tr>
                            <td><strong>{{ $taxTable->name }}</strong></td>
                            <td>{{ $taxTable->tax_year }}</td>
                            <td><span class="badge bg-primary">{{ $taxTable->currency }}</span></td>
                            <td>{{ $taxTable->benefit_type }}</td>
                            <td data-order="{{ $taxTable->bands_count }}">{{ $taxTable->bands_count }}</td>
                            <td data-order="{{ $taxTable->effective_from?->format('Ymd') }}">{{ $taxTable->effective_from?->format('d M Y') }}</td>
                            <td data-order="{{ $taxTable->effective_to?->format('Ymd') ?? '99999999' }}">{{ $taxTable->effective_to?->format('d M Y') ?? 'Open Ended' }}</td>
                            <td><span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span></td>
                            <td>{{ $taxTable->source_authority ?: '-' }}</td>
                            <td class="text-end tax-actions">
                                <a href="{{ route('pensions-administration.settings.tax-tables.show', $taxTable) }}" class="btn btn-sm btn-outline-secondary"><i class="mdi mdi-eye-outline me-1"></i>View</a>

                                @can('pensions.settings.manage')
                                    @if($isFuture)
                                        <a href="{{ route('pensions-administration.settings.tax-tables.edit', $taxTable) }}" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-pencil-outline me-1"></i>Edit</a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deactivateTaxTable{{ $taxTable->id }}"><i class="mdi mdi-cancel me-1"></i>Deactivate</button>
                                    @endif

                                    @if($taxTable->is_active && $hasTakenEffect && !$taxTable->effective_to)
                                        <a href="{{ route('pensions-administration.settings.tax-tables.version.create', $taxTable) }}" class="btn btn-sm btn-primary"><i class="mdi mdi-source-branch me-1"></i>New Version</a>
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
    @foreach($taxTables as $taxTable)
        @if($taxTable->is_active && $taxTable->effective_from?->copy()->startOfDay()->gt(today()))
            <div class="modal fade" id="deactivateTaxTable{{ $taxTable->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('pensions-administration.settings.tax-tables.deactivate', $taxTable) }}">
                            @csrf
                            @method('PATCH')
                            <div class="modal-header"><h5 class="modal-title">Deactivate Tax Table</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <p>Deactivate <strong>{{ $taxTable->name }}</strong>?</p>
                                <label class="form-label">Change Reason <span class="text-danger">*</span></label>
                                <textarea name="change_reason" class="form-control" rows="4" maxlength="2000" required></textarea>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Deactivate</button></div>
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
    $('#benefitTaxTablesTable').DataTable({
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10,25,50,100,-1],[10,25,50,100,'All']],
        order: [[5,'desc']],
        columnDefs: [{ targets: 9, orderable: false, searchable: false }],
        language: {
            searchPlaceholder: 'Search tax tables...',
            info: 'Showing _START_ to _END_ of _TOTAL_ tax tables',
            zeroRecords: 'No matching tax tables found'
        }
    });
});
</script>
@endpush
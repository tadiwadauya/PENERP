@extends('layouts.app')

@section('title', 'Commutation Factors')

@section('page-heading', 'Commutation Factors')

@section('page-subheading')
Manage age, month and gender based factors used for pension commutation calculations
@endsection

@push('styles')
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">

<style>
    .factor-stat-card { height: 100%; border: 0; box-shadow: 0 2px 10px rgba(0, 0, 0, .05); }
    .factor-table th { white-space: nowrap; vertical-align: middle; }
    .factor-table td { vertical-align: middle; }
    .factor-actions { min-width: 210px; white-space: nowrap; }
    .dataTables_wrapper .dataTables_filter input { margin-left: .5rem; }
    .dataTables_wrapper .dataTables_length select { min-width: 70px; }
    table.dataTable > thead > tr > th { vertical-align: middle; }
</style>
@endpush

@section('content')

@include('pensions-administration.partials.navigation')

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card factor-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Total Records</p>
                <h3 class="mb-0">{{ number_format($summary['total']) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card factor-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Active Records</p>
                <h3 class="mb-0">{{ number_format($summary['active']) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card factor-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Current Factors</p>
                <h3 class="text-success mb-0">{{ number_format($summary['current']) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card factor-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Future Factors</p>
                <h3 class="text-primary mb-0">{{ number_format($summary['future']) }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="header-title mb-1">One Third Commutation Factors</h4>
                <p class="text-muted mb-0">Factors are maintained according to age in years, completed months and gender.</p>
            </div>

            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                @can('pensions.settings.manage')
                    <a href="{{ route('pensions-administration.settings.commutation-factors.create') }}" class="btn btn-primary"><i class="mdi mdi-plus me-1"></i>Add Factor</a>
                @endcan
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <h4 class="header-title mb-1">Configured Factors</h4>
            <p class="text-muted mb-0">Search, sort and filter the configured commutation factors. Historical factors are retained for previous pension calculations.</p>
        </div>

        <div class="table-responsive">
            <table id="commutationFactorsTable" class="table table-bordered table-striped table-hover factor-table w-100">
                <thead>
                    <tr>
                        <th>Age Years</th>
                        <th>Age Months</th>
                        <th>Exact Age</th>
                        <th>Gender</th>
                        <th>Factor</th>
                        <th>Effective From</th>
                        <th>Effective To</th>
                        <th>Status</th>
                        <th>Source / Authority</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($factors as $factor)
                        @php
                            $hasTakenEffect = $factor->effective_from?->copy()->startOfDay()->lte(today());

                            $isCurrent = $factor->is_active
                                && $factor->effective_from?->copy()->startOfDay()->lte(today())
                                && (!$factor->effective_to || $factor->effective_to?->copy()->endOfDay()->gte(today()));

                            $isFuture = $factor->is_active
                                && $factor->effective_from?->copy()->startOfDay()->gt(today());

                            $isHistorical = $factor->effective_to
                                && $factor->effective_to?->copy()->endOfDay()->lt(today());

                            $statusLabel = match(true) {
                                !$factor->is_active => 'Inactive',
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
                            <td data-order="{{ $factor->age_years }}">{{ $factor->age_years }}</td>
                            <td data-order="{{ $factor->age_months }}">{{ $factor->age_months }}</td>
                            <td data-order="{{ ($factor->age_years * 12) + $factor->age_months }}"><strong>{{ $factor->age_years }}y {{ $factor->age_months }}m</strong></td>
                            <td>{{ ucfirst($factor->gender) }}</td>
                            <td data-order="{{ (float) $factor->factor }}"><strong>{{ rtrim(rtrim(number_format((float) $factor->factor, 6, '.', ''), '0'), '.') }}</strong></td>
                            <td data-order="{{ $factor->effective_from?->format('Ymd') }}">{{ $factor->effective_from?->format('d M Y') }}</td>
                            <td data-order="{{ $factor->effective_to?->format('Ymd') ?? '99999999' }}">{{ $factor->effective_to?->format('d M Y') ?? 'Open Ended' }}</td>
                            <td><span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span></td>
                            <td>{{ $factor->source_authority ?: '-' }}</td>

                            <td class="text-end factor-actions">
                                @can('pensions.settings.manage')
                                    @if($isFuture)
                                        <a href="{{ route('pensions-administration.settings.commutation-factors.edit', $factor) }}" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-pencil-outline me-1"></i>Edit</a>
                                    @endif

                                    @if($factor->is_active && $hasTakenEffect && !$factor->effective_to)
                                        <a href="{{ route('pensions-administration.settings.commutation-factors.version.create', $factor) }}" class="btn btn-sm btn-primary"><i class="mdi mdi-source-branch me-1"></i>New Version</a>
                                    @endif

                                    @if($isFuture)
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deactivateCommutationFactor{{ $factor->id }}"><i class="mdi mdi-cancel me-1"></i>Deactivate</button>
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
    @foreach($factors as $factor)
        @php
            $isFutureFactor = $factor->is_active
                && $factor->effective_from?->copy()->startOfDay()->gt(today());
        @endphp

        @if($isFutureFactor)
            <div class="modal fade" id="deactivateCommutationFactor{{ $factor->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('pensions-administration.settings.commutation-factors.deactivate', $factor) }}">
                            @csrf
                            @method('PATCH')

                            <div class="modal-header">
                                <h5 class="modal-title">Deactivate Commutation Factor</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <p>You are about to deactivate the future factor for <strong>{{ ucfirst($factor->gender) }}, age {{ $factor->age_years }} years {{ $factor->age_months }} months</strong>.</p>

                                <div class="mb-0">
                                    <label class="form-label">Change Reason <span class="text-danger">*</span></label>
                                    <textarea name="change_reason" class="form-control" rows="4" maxlength="2000" required></textarea>
                                    <small class="text-muted">This reason will be permanently recorded in the audit trail.</small>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">Deactivate</button>
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
    $('#commutationFactorsTable').DataTable({
        responsive: true,
        processing: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[0, 'asc'], [1, 'asc'], [3, 'asc']],
        columnDefs: [
            { targets: 9, orderable: false, searchable: false }
        ],
        language: {
            search: 'Search:',
            searchPlaceholder: 'Search factors...',
            lengthMenu: 'Show _MENU_ records',
            info: 'Showing _START_ to _END_ of _TOTAL_ factors',
            infoEmpty: 'No factors available',
            zeroRecords: 'No matching commutation factors found',
            paginate: {
                first: 'First',
                last: 'Last',
                next: 'Next',
                previous: 'Previous'
            }
        }
    });
});
</script>
@endpush
@extends('layouts.app')

@section('title', 'Withdrawal Employer Entitlement Scales')

@section('page-heading', 'Withdrawal Employer Entitlement Scales')

@section('page-subheading')
Manage employer contribution entitlement percentages according to pensionable service
@endsection

@push('styles')
<style>
    .settings-stat-card {
        height: 100%;
        border: 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
    }

    .settings-table th {
        white-space: nowrap;
        vertical-align: middle;
    }

    .settings-table td {
        vertical-align: middle;
    }

    .settings-actions {
        min-width: 220px;
        white-space: nowrap;
    }
</style>
@endpush

@section('content')

@include('pensions-administration.partials.navigation')

@php
    $pageCollection = $scales->getCollection();

    $currentCount = $pageCollection->filter(function ($scale) {
        return $scale->is_active
            && $scale->effective_from?->copy()->startOfDay()->lte(today())
            && (!$scale->effective_to || $scale->effective_to?->copy()->endOfDay()->gte(today()));
    })->count();

    $futureCount = $pageCollection->filter(function ($scale) {
        return $scale->is_active && $scale->effective_from?->copy()->startOfDay()->gt(today());
    })->count();

    $historicalCount = $pageCollection->filter(function ($scale) {
        return $scale->is_active && $scale->effective_to && $scale->effective_to?->copy()->endOfDay()->lt(today());
    })->count();
@endphp

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card settings-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Configured Bands</p>
                <h3 class="mb-0">{{ number_format($scales->total()) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card settings-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Current</p>
                <h3 class="text-success mb-0">{{ number_format($currentCount) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card settings-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Future</p>
                <h3 class="text-primary mb-0">{{ number_format($futureCount) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card settings-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Historical</p>
                <h3 class="text-secondary mb-0">{{ number_format($historicalCount) }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="header-title mb-1">Withdrawal Employer Entitlement</h4>
                <p class="text-muted mb-0">Defines the percentage of employer contributions payable according to completed pensionable service.</p>
            </div>

            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                @can('pensions.settings.manage')
                    <a href="{{ route('pensions-administration.settings.withdrawal-scales.create') }}" class="btn btn-primary"><i class="mdi mdi-plus me-1"></i>Add Service Band</a>
                @endcan
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('pensions-administration.settings.withdrawal-scales.index') }}">
            <div class="row g-3">
                <div class="col-lg-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Records</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>

                <div class="col-lg-4 d-flex align-items-end">
                    <div>
                        <button type="submit" class="btn btn-primary"><i class="mdi mdi-filter-outline me-1"></i>Filter</button>
                        <a href="{{ route('pensions-administration.settings.withdrawal-scales.index') }}" class="btn btn-light">Clear</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <h4 class="header-title mb-1">Configured Service Bands</h4>
            <p class="text-muted mb-0">Current and historical versions are retained so benefit calculations can be reproduced using the applicable rule at the time.</p>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover settings-table w-100">
                <thead>
                    <tr>
                        <th>Minimum Service</th>
                        <th>Maximum Service</th>
                        <th>Service Range</th>
                        <th>Entitlement</th>
                        <th>Effective From</th>
                        <th>Effective To</th>
                        <th>Status</th>
                        <th>Source / Authority</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($scales as $scale)
                        @php
                            $hasTakenEffect = $scale->effective_from?->copy()->startOfDay()->lte(today());

                            $currentlyEffective =
                                $scale->is_active
                                && $scale->effective_from?->copy()->startOfDay()->lte(today())
                                && (!$scale->effective_to || $scale->effective_to?->copy()->endOfDay()->gte(today()));

                            $isFuture = $scale->is_active && $scale->effective_from?->copy()->startOfDay()->gt(today());

                            $isHistorical =
                                $scale->is_active
                                && $scale->effective_to
                                && $scale->effective_to?->copy()->endOfDay()->lt(today());

                            $badgeClass = match(true) {
                                !$scale->is_active => 'bg-secondary',
                                $currentlyEffective => 'bg-success',
                                $isFuture => 'bg-info text-dark',
                                $isHistorical => 'bg-light text-dark',
                                default => 'bg-secondary',
                            };

                            $statusLabel = match(true) {
                                !$scale->is_active => 'Inactive',
                                $currentlyEffective => 'Current',
                                $isFuture => 'Future',
                                $isHistorical => 'Historical',
                                default => 'Inactive',
                            };

                            $minimumYears = $scale->minimum_service_months / 12;
                            $maximumYears = $scale->maximum_service_months !== null ? $scale->maximum_service_months / 12 : null;
                        @endphp

                        <tr>
                            <td>{{ number_format($scale->minimum_service_months) }} months</td>

                            <td>
                                @if($scale->maximum_service_months !== null)
                                    {{ number_format($scale->maximum_service_months) }} months
                                @else
                                    <span class="text-muted">No Maximum</span>
                                @endif
                            </td>

                            <td>
                                @if($scale->maximum_service_months !== null)
                                    {{ number_format($minimumYears, 2) }} - {{ number_format($maximumYears, 2) }} years
                                @else
                                    {{ number_format($minimumYears, 2) }} years and above
                                @endif
                            </td>

                            <td><strong>{{ rtrim(rtrim(number_format((float) $scale->entitlement_percentage, 4, '.', ''), '0'), '.') }}%</strong></td>
                            <td>{{ $scale->effective_from?->format('d M Y') }}</td>

                            <td>
                                @if($scale->effective_to)
                                    {{ $scale->effective_to->format('d M Y') }}
                                @else
                                    <span class="text-muted">Open Ended</span>
                                @endif
                            </td>

                            <td><span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span></td>
                            <td>{{ $scale->source_authority ?: '-' }}</td>

                            <td class="text-end settings-actions">
                                @can('pensions.settings.manage')
                                    @if($isFuture)
                                        <a href="{{ route('pensions-administration.settings.withdrawal-scales.edit', $scale) }}" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-pencil-outline me-1"></i>Edit</a>
                                    @endif

                                    @if($scale->is_active && $hasTakenEffect && !$scale->effective_to)
                                        <a href="{{ route('pensions-administration.settings.withdrawal-scales.version.create', $scale) }}" class="btn btn-sm btn-primary"><i class="mdi mdi-source-branch me-1"></i>New Version</a>
                                    @endif

                                    @if($isFuture)
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deactivateScaleModal{{ $scale->id }}"><i class="mdi mdi-cancel me-1"></i>Deactivate</button>
                                    @endif
                                @endcan
                            </td>
                        </tr>

                        @can('pensions.settings.manage')
                            @if($isFuture)
                                <div class="modal fade" id="deactivateScaleModal{{ $scale->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('pensions-administration.settings.withdrawal-scales.deactivate', $scale) }}">
                                                @csrf
                                                @method('PATCH')

                                                <div class="modal-header">
                                                    <h5 class="modal-title">Deactivate Service Band</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <p>You are about to deactivate the service band starting from <strong>{{ $scale->minimum_service_months }} months</strong>.</p>

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
                        @endcan
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No withdrawal entitlement scales found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($scales->hasPages())
            <div class="mt-3">{{ $scales->links() }}</div>
        @endif
    </div>
</div>

@endsection
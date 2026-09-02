@extends('layouts.app')

@section('title', 'Accumulated Interest Factors')

@section('page-heading', 'Accumulated Interest Factors')

@section('page-subheading')
Manage age and gender based factors used in accumulated interest calculations
@endsection

@push('styles')
<style>
    .factor-stat-card { height: 100%; border: 0; box-shadow: 0 2px 10px rgba(0, 0, 0, .05); }
    .factor-table th { white-space: nowrap; vertical-align: middle; }
    .factor-table td { vertical-align: middle; }
    .factor-actions { min-width: 220px; white-space: nowrap; }
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
                <h4 class="header-title mb-1">Accumulated Interest Factor Table</h4>
                <p class="text-muted mb-0">Factors are maintained by completed age and gender with effective-dated history.</p>
            </div>

            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                @can('pensions.settings.manage')
                    <a href="{{ route('pensions-administration.settings.accumulated-interest-factors.create') }}" class="btn btn-primary"><i class="mdi mdi-plus me-1"></i>Add Factor</a>
                @endcan
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('pensions-administration.settings.accumulated-interest-factors.index') }}">
            <div class="row g-3">
                <div class="col-xl-2 col-md-4">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">All Genders</option>
                        <option value="male" @selected(request('gender') === 'male')>Male</option>
                        <option value="female" @selected(request('gender') === 'female')>Female</option>
                    </select>
                </div>

                <div class="col-xl-2 col-md-4">
                    <label class="form-label">Age From</label>
                    <input type="number" name="age_from" class="form-control" value="{{ request('age_from') }}" min="0" max="150">
                </div>

                <div class="col-xl-2 col-md-4">
                    <label class="form-label">Age To</label>
                    <input type="number" name="age_to" class="form-control" value="{{ request('age_to') }}" min="0" max="150">
                </div>

                <div class="col-xl-3 col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Records</option>
                        <option value="current" @selected(request('status') === 'current')>Current</option>
                        <option value="future" @selected(request('status') === 'future')>Future</option>
                        <option value="historical" @selected(request('status') === 'historical')>Historical</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>

                <div class="col-xl-3 col-md-6 d-flex align-items-end">
                    <div>
                        <button type="submit" class="btn btn-primary"><i class="mdi mdi-filter-outline me-1"></i>Filter</button>
                        <a href="{{ route('pensions-administration.settings.accumulated-interest-factors.index') }}" class="btn btn-light">Clear</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <h4 class="header-title mb-1">Configured Factors</h4>
            <p class="text-muted mb-0">Historical factors remain available so previous benefit calculations can be reproduced accurately.</p>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover factor-table w-100">
                <thead>
                    <tr>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Factor</th>
                        <th>Effective From</th>
                        <th>Effective To</th>
                        <th>Status</th>
                        <th>Source / Authority</th>
                        <th>Notes</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($factors as $factor)
                        @php
                            $hasTakenEffect = $factor->effective_from?->copy()->startOfDay()->lte(today());

                            $isCurrent = $factor->is_active
                                && $factor->effective_from?->copy()->startOfDay()->lte(today())
                                && (!$factor->effective_to || $factor->effective_to?->copy()->endOfDay()->gte(today()));

                            $isFuture = $factor->is_active && $factor->effective_from?->copy()->startOfDay()->gt(today());

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
                            <td><strong>{{ $factor->age_years }}</strong></td>
                            <td>{{ ucfirst($factor->gender) }}</td>
                            <td><strong>{{ rtrim(rtrim(number_format((float) $factor->factor, 6, '.', ''), '0'), '.') }}</strong></td>
                            <td>{{ $factor->effective_from?->format('d M Y') }}</td>
                            <td>{{ $factor->effective_to?->format('d M Y') ?? 'Open Ended' }}</td>
                            <td><span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span></td>
                            <td>{{ $factor->source_authority ?: '-' }}</td>
                            <td>{{ $factor->notes ? \Illuminate\Support\Str::limit($factor->notes, 50) : '-' }}</td>

                            <td class="text-end factor-actions">
                                @can('pensions.settings.manage')
                                    @if($isFuture)
                                        <a href="{{ route('pensions-administration.settings.accumulated-interest-factors.edit', $factor) }}" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-pencil-outline me-1"></i>Edit</a>
                                    @endif

                                    @if($factor->is_active && $hasTakenEffect && !$factor->effective_to)
                                        <a href="{{ route('pensions-administration.settings.accumulated-interest-factors.version.create', $factor) }}" class="btn btn-sm btn-primary"><i class="mdi mdi-source-branch me-1"></i>New Version</a>
                                    @endif

                                    @if($isFuture)
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deactivateAccumulatedFactor{{ $factor->id }}"><i class="mdi mdi-cancel me-1"></i>Deactivate</button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No accumulated interest factors found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($factors->hasPages())
            <div class="mt-3">{{ $factors->links() }}</div>
        @endif
    </div>
</div>

@can('pensions.settings.manage')
    @foreach($factors as $factor)
        @php
            $isFutureFactor = $factor->is_active && $factor->effective_from?->copy()->startOfDay()->gt(today());
        @endphp

        @if($isFutureFactor)
            <div class="modal fade" id="deactivateAccumulatedFactor{{ $factor->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('pensions-administration.settings.accumulated-interest-factors.deactivate', $factor) }}">
                            @csrf
                            @method('PATCH')

                            <div class="modal-header">
                                <h5 class="modal-title">Deactivate Accumulated Interest Factor</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <p>You are about to deactivate the future factor for <strong>{{ ucfirst($factor->gender) }}, age {{ $factor->age_years }}</strong>.</p>

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
@extends('layouts.app')

@section('title', 'General Benefit Rules')

@section('page-heading', 'General Benefit Rules')

@section('page-subheading')
Manage effective-dated pension benefit calculation rules
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

    .settings-key {
        font-size: 11px;
    }

    .settings-description {
        min-width: 260px;
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
    $totalRules = $settings->total();

    $currentRules = $settings->getCollection()->filter(function ($setting) {
        return $setting->is_active
            && $setting->effective_from?->copy()->startOfDay()->lte(today())
            && (!$setting->effective_to || $setting->effective_to?->copy()->endOfDay()->gte(today()));
    })->count();

    $futureRules = $settings->getCollection()->filter(function ($setting) {
        return $setting->is_active
            && $setting->effective_from?->copy()->startOfDay()->gt(today());
    })->count();

    $inactiveRules = $settings->getCollection()->where('is_active', false)->count();
@endphp

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card settings-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Configured Rules</p>
                <h3 class="mb-0">{{ number_format($totalRules) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card settings-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Current Rules</p>
                <h3 class="text-success mb-0">{{ number_format($currentRules) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card settings-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Future Rules</p>
                <h3 class="text-primary mb-0">{{ number_format($futureRules) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card settings-stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Inactive Rules</p>
                <h3 class="text-secondary mb-0">{{ number_format($inactiveRules) }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="header-title mb-1">General Benefit Rules</h4>
                <p class="text-muted mb-0">Configure the effective-dated rules used by PENERP when calculating pension benefits.</p>
            </div>

            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                @can('pensions.settings.manage')
                    <a href="{{ route('pensions-administration.settings.general.create') }}" class="btn btn-primary"><i class="mdi mdi-plus me-1"></i>Add Benefit Rule</a>
                @endcan
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h4 class="header-title mb-3">Filter Benefit Rules</h4>

        <form method="GET" action="{{ route('pensions-administration.settings.general.index') }}">
            <div class="row g-3">
                <div class="col-lg-5">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search rule, setting key, description or authority">
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Category</label>

                    <select name="category" class="form-select">
                        <option value="">All Categories</option>

                        @foreach($categories as $category)
                            <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-3 d-flex align-items-end">
                    <div>
                        <button type="submit" class="btn btn-primary"><i class="mdi mdi-filter-outline me-1"></i>Filter</button>
                        <a href="{{ route('pensions-administration.settings.general.index') }}" class="btn btn-light">Clear</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <h4 class="header-title mb-1">Configured Benefit Rules</h4>
            <p class="text-muted mb-0">Historical versions are retained so previous pension calculations can be reproduced using the rules that applied at the time.</p>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover settings-table w-100">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Benefit Rule</th>
                        <th>Value</th>
                        <th>Effective From</th>
                        <th>Effective To</th>
                        <th>Status</th>
                        <th>Source / Authority</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($settings as $setting)
                        @php
                            $hasTakenEffect = $setting->effective_from?->copy()->startOfDay()->lte(today());

                            $currentlyEffective =
                                $setting->is_active
                                && $setting->effective_from?->copy()->startOfDay()->lte(today())
                                && (!$setting->effective_to || $setting->effective_to?->copy()->endOfDay()->gte(today()));

                            $isFuture =
                                $setting->is_active
                                && $setting->effective_from?->copy()->startOfDay()->gt(today());

                            $isHistorical =
                                $setting->is_active
                                && $setting->effective_to
                                && $setting->effective_to?->copy()->endOfDay()->lt(today());

                            $displayValue = match($setting->value_type) {
                                'decimal' => rtrim(rtrim(number_format((float) $setting->value_decimal, 8, '.', ''), '0'), '.'),
                                'integer' => number_format((int) $setting->value_integer),
                                'string' => $setting->value_string,
                                'boolean' => $setting->value_boolean ? 'Yes' : 'No',
                                default => '-',
                            };

                            $badgeClass = match(true) {
                                !$setting->is_active => 'bg-secondary',
                                $currentlyEffective => 'bg-success',
                                $isFuture => 'bg-info text-dark',
                                $isHistorical => 'bg-light text-dark',
                                default => 'bg-secondary',
                            };

                            $statusLabel = match(true) {
                                !$setting->is_active => 'Inactive',
                                $currentlyEffective => 'Current',
                                $isFuture => 'Future',
                                $isHistorical => 'Historical',
                                default => 'Inactive',
                            };
                        @endphp

                        <tr>
                            <td><strong>{{ $setting->category }}</strong></td>

                            <td class="settings-description">
                                <strong>{{ $setting->name }}</strong>
                                <div class="text-muted settings-key">{{ $setting->setting_key }}</div>

                                @if($setting->description)
                                    <div class="small text-muted mt-1">{{ Str::limit($setting->description, 120) }}</div>
                                @endif
                            </td>

                            <td>
                                <strong>{{ $displayValue }}</strong>
                                <div class="small text-muted">{{ ucfirst($setting->value_type) }}</div>
                            </td>

                            <td>{{ $setting->effective_from?->format('d M Y') }}</td>

                            <td>
                                @if($setting->effective_to)
                                    {{ $setting->effective_to->format('d M Y') }}
                                @else
                                    <span class="text-muted">Open Ended</span>
                                @endif
                            </td>

                            <td><span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span></td>

                            <td>
                                @if($setting->source_authority)
                                    {{ $setting->source_authority }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td class="text-end settings-actions">
                                @can('pensions.settings.manage')
                                    @if($isFuture)
                                        <a href="{{ route('pensions-administration.settings.general.edit', $setting) }}" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-pencil-outline me-1"></i>Edit</a>
                                    @endif

                                    @if($setting->is_active && $hasTakenEffect && !$setting->effective_to)
                                        <a href="{{ route('pensions-administration.settings.general.version.create', $setting) }}" class="btn btn-sm btn-primary"><i class="mdi mdi-source-branch me-1"></i>New Version</a>
                                    @endif

                                    @if($isFuture)
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deactivateRuleModal{{ $setting->id }}"><i class="mdi mdi-cancel me-1"></i>Deactivate</button>
                                    @endif

                                    @if(!$isFuture && !($setting->is_active && $hasTakenEffect && !$setting->effective_to))
                                        <span class="text-muted small">No action required</span>
                                    @endif
                                @else
                                    <span class="text-muted small">View only</span>
                                @endcan
                            </td>
                        </tr>

                        @can('pensions.settings.manage')
                            @if($isFuture)
                                <div class="modal fade" id="deactivateRuleModal{{ $setting->id }}" tabindex="-1" aria-labelledby="deactivateRuleModalLabel{{ $setting->id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('pensions-administration.settings.general.deactivate', $setting) }}">
                                                @csrf
                                                @method('PATCH')

                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deactivateRuleModalLabel{{ $setting->id }}">Deactivate Benefit Rule</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <p class="mb-1">You are about to deactivate:</p>
                                                    <h5 class="mb-1">{{ $setting->name }}</h5>
                                                    <p class="text-muted mb-3">{{ $setting->setting_key }}</p>

                                                    <div class="mb-0">
                                                        <label class="form-label">Change Reason <span class="text-danger">*</span></label>
                                                        <textarea name="change_reason" class="form-control" rows="4" maxlength="2000" placeholder="Explain why this future rule is being deactivated" required></textarea>
                                                        <small class="text-muted">This reason will be permanently recorded in the audit trail.</small>
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger"><i class="mdi mdi-cancel me-1"></i>Deactivate Rule</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endcan
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No benefit rules found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($settings->hasPages())
            <div class="mt-3">{{ $settings->links() }}</div>
        @endif
    </div>
</div>

@endsection
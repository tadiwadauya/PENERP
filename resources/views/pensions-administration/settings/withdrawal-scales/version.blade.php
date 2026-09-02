@extends('layouts.app')

@section('title', 'New Withdrawal Scale Version')

@section('page-heading', 'New Withdrawal Scale Version')

@section('page-subheading')
Create a new effective-dated version without changing the historical scale
@endsection

@section('content')

@include('pensions-administration.partials.navigation')

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="header-title mb-1">Add New Version</h4>
                <p class="text-muted mb-0">{{ $scale->minimum_service_months }} months to {{ $scale->maximum_service_months !== null ? $scale->maximum_service_months . ' months' : 'No Maximum' }}</p>
            </div>

            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="{{ route('pensions-administration.settings.withdrawal-scales.index') }}" class="btn btn-light"><i class="mdi mdi-arrow-left me-1"></i>Back</a>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <i class="mdi mdi-information-outline me-1"></i>The current version will remain in history. Its Effective To date will automatically become one day before the new version starts.
</div>

<div class="card mb-3">
    <div class="card-body">
        <h4 class="header-title mb-3">Current Version</h4>

        <div class="row g-3">
            <div class="col-lg-3">
                <div class="border rounded p-3 h-100">
                    <p class="text-muted mb-1">Minimum Service</p>
                    <h5 class="mb-0">{{ $scale->minimum_service_months }} months</h5>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="border rounded p-3 h-100">
                    <p class="text-muted mb-1">Maximum Service</p>
                    <h5 class="mb-0">{{ $scale->maximum_service_months !== null ? $scale->maximum_service_months . ' months' : 'No Maximum' }}</h5>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="border rounded p-3 h-100">
                    <p class="text-muted mb-1">Current Entitlement</p>
                    <h5 class="mb-0">{{ rtrim(rtrim(number_format((float) $scale->entitlement_percentage, 4, '.', ''), '0'), '.') }}%</h5>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="border rounded p-3 h-100">
                    <p class="text-muted mb-1">Effective From</p>
                    <h5 class="mb-0">{{ $scale->effective_from?->format('d M Y') }}</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h4 class="header-title mb-3">New Version Details</h4>

        <form method="POST" action="{{ route('pensions-administration.settings.withdrawal-scales.version.store', $scale) }}">
            @csrf

            <div class="row">
                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label">New Entitlement Percentage <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="entitlement_percentage" class="form-control @error('entitlement_percentage') is-invalid @enderror" value="{{ old('entitlement_percentage', $scale->entitlement_percentage) }}" min="0" max="100" step="0.0001" required>
                            <span class="input-group-text">%</span>
                            @error('entitlement_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label">New Effective From <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from') }}" required>
                        @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">Source / Authority</label>
                        <input type="text" name="source_authority" class="form-control @error('source_authority') is-invalid @enderror" value="{{ old('source_authority', $scale->source_authority) }}" maxlength="255">
                        @error('source_authority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $scale->notes) }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">Change Reason <span class="text-danger">*</span></label>
                        <textarea name="change_reason" class="form-control @error('change_reason') is-invalid @enderror" rows="3" maxlength="2000" required>{{ old('change_reason') }}</textarea>
                        @error('change_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">This will be permanently recorded in the audit trail.</small>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <a href="{{ route('pensions-administration.settings.withdrawal-scales.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-source-branch me-1"></i>Create New Version</button>
            </div>
        </form>
    </div>
</div>

@endsection
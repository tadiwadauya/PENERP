@extends('layouts.app')

@section('title', 'New Commutation Factor Version')

@section('page-heading', 'New Commutation Factor Version')

@section('page-subheading')
Create a new effective-dated factor without changing the historical commutation factor
@endsection

@section('content')

@include('pensions-administration.partials.navigation')

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="header-title mb-1">Add New Version</h4>
                <p class="text-muted mb-0">{{ ucfirst($factor->gender) }} - Age {{ $factor->age_years }} years {{ $factor->age_months }} months</p>
            </div>

            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="{{ route('pensions-administration.settings.commutation-factors.index') }}" class="btn btn-light"><i class="mdi mdi-arrow-left me-1"></i>Back</a>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <i class="mdi mdi-information-outline me-1"></i>The existing factor will not be overwritten. Its Effective To date will automatically become one day before the new version starts.
</div>

<div class="card mb-3">
    <div class="card-body">
        <h4 class="header-title mb-3">Current Version</h4>

        <div class="row g-3">
            <div class="col-xl-3 col-md-6">
                <div class="border rounded p-3 h-100">
                    <p class="text-muted mb-1">Age</p>
                    <h5 class="mb-0">{{ $factor->age_years }} years {{ $factor->age_months }} months</h5>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="border rounded p-3 h-100">
                    <p class="text-muted mb-1">Gender</p>
                    <h5 class="mb-0">{{ ucfirst($factor->gender) }}</h5>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="border rounded p-3 h-100">
                    <p class="text-muted mb-1">Current Factor</p>
                    <h5 class="mb-0">{{ rtrim(rtrim(number_format((float) $factor->factor, 6, '.', ''), '0'), '.') }}</h5>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="border rounded p-3 h-100">
                    <p class="text-muted mb-1">Effective From</p>
                    <h5 class="mb-0">{{ $factor->effective_from?->format('d M Y') }}</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h4 class="header-title mb-3">New Version Details</h4>

        <form method="POST" action="{{ route('pensions-administration.settings.commutation-factors.version.store', $factor) }}">
            @csrf

            <div class="row">
                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label">New Factor <span class="text-danger">*</span></label>
                        <input type="number" name="factor" class="form-control @error('factor') is-invalid @enderror" value="{{ old('factor', $factor->factor) }}" min="0.000001" step="0.000001" required>
                        @error('factor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label">New Effective From <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from') }}" required>
                        @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">The current version will end one day before this date.</small>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">Source / Authority</label>
                        <input type="text" name="source_authority" class="form-control @error('source_authority') is-invalid @enderror" value="{{ old('source_authority', $factor->source_authority) }}" maxlength="255">
                        @error('source_authority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $factor->notes) }}</textarea>
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
                <a href="{{ route('pensions-administration.settings.commutation-factors.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-source-branch me-1"></i>Create New Version</button>
            </div>
        </form>
    </div>
</div>

@endsection
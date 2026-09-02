@extends('layouts.app')

@section('title', 'Add Withdrawal Service Band')

@section('page-heading', 'Add Withdrawal Service Band')

@section('page-subheading')
Create a new employer contribution entitlement service band
@endsection

@section('content')

@include('pensions-administration.partials.navigation')

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="header-title mb-1">Add Service Band</h4>
                <p class="text-muted mb-0">Define the service range and employer contribution entitlement percentage.</p>
            </div>

            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="{{ route('pensions-administration.settings.withdrawal-scales.index') }}" class="btn btn-light"><i class="mdi mdi-arrow-left me-1"></i>Back</a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('pensions-administration.settings.withdrawal-scales.store') }}">
            @csrf

            <div class="row">
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Minimum Service Months <span class="text-danger">*</span></label>
                        <input type="number" name="minimum_service_months" class="form-control @error('minimum_service_months') is-invalid @enderror" value="{{ old('minimum_service_months') }}" min="0" required>
                        @error('minimum_service_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Maximum Service Months</label>
                        <input type="number" name="maximum_service_months" class="form-control @error('maximum_service_months') is-invalid @enderror" value="{{ old('maximum_service_months') }}" min="0">
                        @error('maximum_service_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Leave blank when there is no maximum.</small>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Entitlement Percentage <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="entitlement_percentage" class="form-control @error('entitlement_percentage') is-invalid @enderror" value="{{ old('entitlement_percentage') }}" min="0" max="100" step="0.0001" required>
                            <span class="input-group-text">%</span>
                            @error('entitlement_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label">Effective From <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from') }}" required>
                        @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label">Effective To</label>
                        <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror" value="{{ old('effective_to') }}">
                        @error('effective_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Leave blank for an open-ended rule.</small>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">Source / Authority</label>
                        <input type="text" name="source_authority" class="form-control @error('source_authority') is-invalid @enderror" value="{{ old('source_authority') }}" maxlength="255">
                        @error('source_authority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes') }}</textarea>
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
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save-outline me-1"></i>Save Service Band</button>
            </div>
        </form>
    </div>
</div>

@endsection
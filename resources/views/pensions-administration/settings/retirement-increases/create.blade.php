@extends('layouts.app')

@section('title', 'Add Retirement Increase Factor')

@section('page-heading', 'Add Retirement Increase Factor')

@section('page-subheading')
Create a new effective-dated retirement age increase factor
@endsection

@section('content')

@include('pensions-administration.partials.navigation')

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="header-title mb-1">Add Retirement Increase Factor</h4>
                <p class="text-muted mb-0">Define the retirement age, percentage increase and effective period.</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="{{ route('pensions-administration.settings.retirement-increases.index') }}" class="btn btn-light"><i class="mdi mdi-arrow-left me-1"></i>Back</a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('pensions-administration.settings.retirement-increases.store') }}">
            @csrf

            <div class="row">
                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label">Retirement Age <span class="text-danger">*</span></label>
                        <input type="number" name="age_years" class="form-control @error('age_years') is-invalid @enderror" value="{{ old('age_years') }}" min="0" max="150" required>
                        @error('age_years')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label">Increase Percentage <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="increase_percentage" class="form-control @error('increase_percentage') is-invalid @enderror" value="{{ old('increase_percentage') }}" min="0" max="100" step="0.0001" required>
                            <span class="input-group-text">%</span>
                            @error('increase_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                        <small class="text-muted">Leave blank for an open-ended factor.</small>
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
                        <small class="text-muted">This reason will be permanently recorded in the audit trail.</small>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <a href="{{ route('pensions-administration.settings.retirement-increases.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save-outline me-1"></i>Save Factor</button>
            </div>
        </form>
    </div>
</div>

@endsection
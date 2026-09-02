@extends('layouts.app')

@section('title', 'Edit Accumulated Interest Factor')

@section('page-heading', 'Edit Accumulated Interest Factor')

@section('page-subheading')
Edit a future accumulated interest factor before it takes effect
@endsection

@section('content')

@include('pensions-administration.partials.navigation')

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="header-title mb-1">Edit Future Factor</h4>
                <p class="text-muted mb-0">This factor can be amended because its effective date has not yet been reached.</p>
            </div>

            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="{{ route('pensions-administration.settings.accumulated-interest-factors.index') }}" class="btn btn-light"><i class="mdi mdi-arrow-left me-1"></i>Back</a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('pensions-administration.settings.accumulated-interest-factors.update', $factor) }}">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Age in Years <span class="text-danger">*</span></label>
                        <input type="number" name="age_years" class="form-control @error('age_years') is-invalid @enderror" value="{{ old('age_years', $factor->age_years) }}" min="0" max="150" required>
                        @error('age_years')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                            <option value="male" @selected(old('gender', $factor->gender) === 'male')>Male</option>
                            <option value="female" @selected(old('gender', $factor->gender) === 'female')>Female</option>
                        </select>
                        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Factor <span class="text-danger">*</span></label>
                        <input type="number" name="factor" class="form-control @error('factor') is-invalid @enderror" value="{{ old('factor', $factor->factor) }}" min="0.000001" step="0.000001" required>
                        @error('factor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label">Effective From <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from', $factor->effective_from?->format('Y-m-d')) }}" required>
                        @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label">Effective To</label>
                        <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror" value="{{ old('effective_to', $factor->effective_to?->format('Y-m-d')) }}">
                        @error('effective_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                        <small class="text-muted">This change will be permanently recorded in the audit trail.</small>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <a href="{{ route('pensions-administration.settings.accumulated-interest-factors.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save-outline me-1"></i>Save Changes</button>
            </div>
        </form>
    </div>
</div>

@endsection
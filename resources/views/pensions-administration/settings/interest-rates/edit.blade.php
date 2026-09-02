@extends('layouts.app')

@section('title', 'Edit Interest Rate')
@section('page-heading', 'Edit Interest Rate')
@section('page-subheading', 'Edit a future-dated LAPF interest rate')

@section('content')

@include('pensions-administration.partials.navigation')

<div class="alert alert-info">
    <i class="mdi mdi-information-outline me-1"></i>Only future-dated interest rates can be edited directly. Once a rate becomes effective, changes must be made through a new version.
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="header-title mb-1">Edit Future Interest Rate</h4>
                <p class="text-muted mb-0">Currently scheduled from {{ $interestRate->effective_from->format('d M Y') }}.</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="{{ route('pensions-administration.settings.interest-rates.index') }}" class="btn btn-light"><i class="mdi mdi-arrow-left me-1"></i>Back</a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('pensions-administration.settings.interest-rates.update', $interestRate) }}">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Interest Rate Per Annum (%) <span class="text-danger">*</span></label>
                        <input type="number" name="rate_percentage" class="form-control @error('rate_percentage') is-invalid @enderror" value="{{ old('rate_percentage', $interestRate->rate_percentage) }}" min="0" step="0.0001" required>
                        @error('rate_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Effective From <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from', $interestRate->effective_from->format('Y-m-d')) }}" required>
                        @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Effective To</label>
                        <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror" value="{{ old('effective_to', $interestRate->effective_to?->format('Y-m-d')) }}">
                        @error('effective_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">Source / Authority</label>
                        <input type="text" name="source_authority" class="form-control @error('source_authority') is-invalid @enderror" value="{{ old('source_authority', $interestRate->source_authority) }}" maxlength="255">
                        @error('source_authority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" maxlength="2000">{{ old('notes', $interestRate->notes) }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">Change Reason <span class="text-danger">*</span></label>
                        <textarea name="change_reason" class="form-control @error('change_reason') is-invalid @enderror" rows="3" maxlength="2000" required>{{ old('change_reason') }}</textarea>
                        @error('change_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Both the old and new values will be retained in the audit trail.</small>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <a href="{{ route('pensions-administration.settings.interest-rates.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save-outline me-1"></i>Save Changes</button>
            </div>
        </form>
    </div>
</div>

@endsection
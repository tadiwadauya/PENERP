@extends('layouts.app')

@section('title', 'New Interest Rate Version')
@section('page-heading', 'New Interest Rate Version')
@section('page-subheading', 'Introduce a new interest rate without changing historical calculations')

@section('content')

@include('pensions-administration.partials.navigation')

<div class="card mb-3">
    <div class="card-body">
        <h4 class="header-title mb-3">Current Interest Rate</h4>

        <div class="row">
            <div class="col-md-3">
                <p class="text-muted mb-1">Rate</p>
                <h5>{{ number_format((float) $interestRate->rate_percentage, 2) }}%</h5>
            </div>

            <div class="col-md-3">
                <p class="text-muted mb-1">Effective From</p>
                <h5>{{ $interestRate->effective_from->format('d M Y') }}</h5>
            </div>

            <div class="col-md-3">
                <p class="text-muted mb-1">Effective To</p>
                <h5>{{ $interestRate->effective_to ? $interestRate->effective_to->format('d M Y') : 'Current' }}</h5>
            </div>

            <div class="col-md-3">
                <p class="text-muted mb-1">Source / Authority</p>
                <span>{{ $interestRate->source_authority ?: '-' }}</span>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <i class="mdi mdi-information-outline me-1"></i>Creating a new version will automatically close the current rate on the day before the new rate becomes effective. Historical calculations will continue using the original rate.
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('pensions-administration.settings.interest-rates.version.store', $interestRate) }}">
            @csrf

            <div class="row">
                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label">New Interest Rate Per Annum (%) <span class="text-danger">*</span></label>
                        <input type="number" name="rate_percentage" class="form-control @error('rate_percentage') is-invalid @enderror" value="{{ old('rate_percentage') }}" min="0" step="0.0001" required>
                        @error('rate_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label">New Effective From <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from') }}" min="{{ $interestRate->effective_from->copy()->addDay()->format('Y-m-d') }}" required>
                        @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" maxlength="2000">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">Change Reason <span class="text-danger">*</span></label>
                        <textarea name="change_reason" class="form-control @error('change_reason') is-invalid @enderror" rows="3" maxlength="2000" required>{{ old('change_reason') }}</textarea>
                        @error('change_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">State the authority or reason for introducing the new rate.</small>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <a href="{{ route('pensions-administration.settings.interest-rates.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-source-branch me-1"></i>Create New Version</button>
            </div>
        </form>
    </div>
</div>

@endsection
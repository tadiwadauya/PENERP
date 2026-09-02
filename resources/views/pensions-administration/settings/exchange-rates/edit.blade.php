@extends('layouts.app')

@section('title', 'Edit Exchange Rate')
@section('page-heading', 'Edit Exchange Rate')
@section('page-subheading', 'Correct an exchange rate that has not yet been used by a posted receipt')

@section('content')

@include('pensions-administration.partials.navigation')

<div class="alert alert-warning">
    <i class="mdi mdi-alert-outline me-1"></i>This rate can only be edited while it remains unused. Once referenced by a contribution receipt it becomes permanently locked.
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('pensions-administration.settings.exchange-rates.update', $exchangeRate) }}">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-lg-3">
                    <div class="mb-3">
                        <label class="form-label">Rate Date <span class="text-danger">*</span></label>
                        <input type="date" name="rate_date" class="form-control @error('rate_date') is-invalid @enderror" value="{{ old('rate_date', $exchangeRate->rate_date?->format('Y-m-d')) }}" required>
                        @error('rate_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="mb-3">
                        <label class="form-label">From Currency <span class="text-danger">*</span></label>
                        <input type="text" name="from_currency" class="form-control text-uppercase @error('from_currency') is-invalid @enderror" value="{{ old('from_currency', $exchangeRate->from_currency) }}" maxlength="3" required>
                        @error('from_currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="mb-3">
                        <label class="form-label">To Currency <span class="text-danger">*</span></label>
                        <input type="text" name="to_currency" class="form-control text-uppercase @error('to_currency') is-invalid @enderror" value="{{ old('to_currency', $exchangeRate->to_currency) }}" maxlength="3" required>
                        @error('to_currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="mb-3">
                        <label class="form-label">Exchange Rate <span class="text-danger">*</span></label>
                        <input type="number" name="rate" class="form-control @error('rate') is-invalid @enderror" value="{{ old('rate', $exchangeRate->rate) }}" min="0.00000001" step="0.00000001" required>
                        @error('rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">Source</label>
                        <input type="text" name="source" class="form-control @error('source') is-invalid @enderror" value="{{ old('source', $exchangeRate->source) }}" maxlength="150">
                        @error('source')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" maxlength="2000">{{ old('notes', $exchangeRate->notes) }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">Change Reason <span class="text-danger">*</span></label>
                        <textarea name="change_reason" class="form-control @error('change_reason') is-invalid @enderror" rows="3" maxlength="2000" required>{{ old('change_reason') }}</textarea>
                        @error('change_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">The previous and corrected values will both be retained in the audit trail.</small>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <a href="{{ route('pensions-administration.settings.exchange-rates.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save-outline me-1"></i>Save Changes</button>
            </div>
        </form>
    </div>
</div>

@endsection
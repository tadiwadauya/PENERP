@extends('layouts.app')

@section('title', 'Add Exchange Rate')
@section('page-heading', 'Add Exchange Rate')
@section('page-subheading', 'Create a new dated currency exchange rate')

@section('content')

@include('pensions-administration.partials.navigation')

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="header-title mb-1">Add Exchange Rate</h4>
                <p class="text-muted mb-0">Enter the applicable currency pair, date and authorised exchange rate.</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="{{ route('pensions-administration.settings.exchange-rates.index') }}" class="btn btn-light"><i class="mdi mdi-arrow-left me-1"></i>Back</a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('pensions-administration.settings.exchange-rates.store') }}">
            @csrf

            <div class="row">
                <div class="col-lg-3">
                    <div class="mb-3">
                        <label class="form-label">Rate Date <span class="text-danger">*</span></label>
                        <input type="date" name="rate_date" class="form-control @error('rate_date') is-invalid @enderror" value="{{ old('rate_date') }}" required>
                        @error('rate_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="mb-3">
                        <label class="form-label">From Currency <span class="text-danger">*</span></label>
                        <input type="text" name="from_currency" class="form-control text-uppercase @error('from_currency') is-invalid @enderror" value="{{ old('from_currency', 'USD') }}" maxlength="3" required>
                        @error('from_currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="mb-3">
                        <label class="form-label">To Currency <span class="text-danger">*</span></label>
                        <input type="text" name="to_currency" class="form-control text-uppercase @error('to_currency') is-invalid @enderror" value="{{ old('to_currency', 'ZWG') }}" maxlength="3" required>
                        @error('to_currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="mb-3">
                        <label class="form-label">Exchange Rate <span class="text-danger">*</span></label>
                        <input type="number" name="rate" class="form-control @error('rate') is-invalid @enderror" value="{{ old('rate') }}" min="0.00000001" step="0.00000001" required>
                        @error('rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">Source</label>
                        <input type="text" name="source" class="form-control @error('source') is-invalid @enderror" value="{{ old('source') }}" maxlength="150">
                        @error('source')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                        <small class="text-muted">This reason will be permanently recorded in the audit trail.</small>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <a href="{{ route('pensions-administration.settings.exchange-rates.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save-outline me-1"></i>Save Exchange Rate</button>
            </div>
        </form>
    </div>
</div>

@endsection
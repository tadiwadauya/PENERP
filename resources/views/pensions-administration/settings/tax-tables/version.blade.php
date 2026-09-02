@extends('layouts.app')

@section('title', 'New Tax Table Version')
@section('page-heading', 'New Tax Table Version')
@section('page-subheading', 'Create a new tax table version while preserving the current historical table')

@section('content')

@include('pensions-administration.partials.navigation')

<div class="alert alert-info">
    <i class="mdi mdi-information-outline me-1"></i>The existing tax table and all its tax bands will remain unchanged. Its Effective To date will become one day before this version starts.
</div>

<form method="POST" action="{{ route('pensions-administration.settings.tax-tables.version.store', $taxTable) }}">
    @csrf

    @include('pensions-administration.settings.tax-tables._fields', ['versionMode' => true])
    @include('pensions-administration.settings.tax-tables._bands')

    <div class="card">
        <div class="card-body">
            <label class="form-label">Change Reason <span class="text-danger">*</span></label>
            <textarea name="change_reason" class="form-control @error('change_reason') is-invalid @enderror" rows="3" maxlength="2000" required>{{ old('change_reason') }}</textarea>
            @error('change_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror

            <div class="text-end mt-3">
                <a href="{{ route('pensions-administration.settings.tax-tables.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-source-branch me-1"></i>Create New Version</button>
            </div>
        </div>
    </div>
</form>

@endsection
@extends('layouts.app')

@section('title', 'Edit Tax Table')
@section('page-heading', 'Edit Tax Table')
@section('page-subheading', 'Edit a future tax table before it takes effect')

@section('content')

@include('pensions-administration.partials.navigation')

<form method="POST" action="{{ route('pensions-administration.settings.tax-tables.update', $taxTable) }}">
    @csrf
    @method('PUT')

    @include('pensions-administration.settings.tax-tables._fields')
    @include('pensions-administration.settings.tax-tables._bands')

    <div class="card">
        <div class="card-body">
            <label class="form-label">Change Reason <span class="text-danger">*</span></label>
            <textarea name="change_reason" class="form-control @error('change_reason') is-invalid @enderror" rows="3" maxlength="2000" required>{{ old('change_reason') }}</textarea>
            @error('change_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror

            <div class="text-end mt-3">
                <a href="{{ route('pensions-administration.settings.tax-tables.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save-outline me-1"></i>Save Changes</button>
            </div>
        </div>
    </div>
</form>

@endsection
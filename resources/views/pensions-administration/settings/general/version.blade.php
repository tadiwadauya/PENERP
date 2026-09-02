@extends('layouts.app')

@section('title', 'Add New Rule Version')

@section('page-heading', 'Add New Rule Version')

@section('page-subheading')
Create a new effective-dated version without overwriting the existing benefit rule
@endsection

@section('content')

@include('pensions-administration.partials.navigation')

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="header-title mb-1">Add New Version</h4>
                <p class="text-muted mb-0">{{ $setting->setting_key }}</p>
            </div>

            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="{{ route('pensions-administration.settings.general.index') }}" class="btn btn-light"><i class="mdi mdi-arrow-left me-1"></i>Back</a>
            </div>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Please correct the following:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="alert alert-info">
    <i class="mdi mdi-information-outline me-1"></i>
    The existing version will not be overwritten. Its Effective To date will be closed one day before the new version starts.
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="mb-3">
            <h4 class="header-title mb-1">Current Version</h4>
            <p class="text-muted mb-0">Review the existing rule before creating the replacement version.</p>
        </div>

        <div class="row g-3">
            <div class="col-xl-4 col-md-6">
                <div class="border rounded p-3 h-100">
                    <p class="text-muted mb-1">Category</p>
                    <h5 class="mb-0">{{ $setting->category }}</h5>
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="border rounded p-3 h-100">
                    <p class="text-muted mb-1">Setting Key</p>
                    <h5 class="mb-0">{{ $setting->setting_key }}</h5>
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="border rounded p-3 h-100">
                    <p class="text-muted mb-1">Value Type</p>
                    <h5 class="mb-0">{{ ucfirst($setting->value_type) }}</h5>
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="border rounded p-3 h-100">
                    <p class="text-muted mb-1">Current Value</p>

                    @if($setting->value_type === 'decimal')
                        <h5 class="mb-0">{{ rtrim(rtrim(number_format((float) $setting->value_decimal, 8, '.', ''), '0'), '.') }}</h5>
                    @elseif($setting->value_type === 'integer')
                        <h5 class="mb-0">{{ number_format((int) $setting->value_integer) }}</h5>
                    @elseif($setting->value_type === 'string')
                        <h5 class="mb-0">{{ $setting->value_string }}</h5>
                    @elseif($setting->value_type === 'boolean')
                        <h5 class="mb-0">{{ $setting->value_boolean ? 'Yes' : 'No' }}</h5>
                    @else
                        <h5 class="mb-0">-</h5>
                    @endif
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="border rounded p-3 h-100">
                    <p class="text-muted mb-1">Effective From</p>
                    <h5 class="mb-0">{{ $setting->effective_from?->format('d M Y') }}</h5>
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="border rounded p-3 h-100">
                    <p class="text-muted mb-1">Effective To</p>
                    <h5 class="mb-0">{{ $setting->effective_to?->format('d M Y') ?? 'Open Ended' }}</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <h4 class="header-title mb-1">New Version Details</h4>
            <p class="text-muted mb-0">Enter the values that should apply from the new effective date.</p>
        </div>

        <form method="POST" action="{{ route('pensions-administration.settings.general.version.store', $setting) }}">
            @csrf

            <div class="row">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <label class="form-label">Rule Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $setting->name) }}" maxlength="180" required>

                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Value Type</label>
                        <input type="text" class="form-control" value="{{ ucfirst($setting->value_type) }}" disabled>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $setting->description) }}</textarea>

                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                @if($setting->value_type === 'decimal')
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label">New Decimal Value <span class="text-danger">*</span></label>
                            <input type="number" step="0.00000001" name="value_decimal" class="form-control @error('value_decimal') is-invalid @enderror" value="{{ old('value_decimal', $setting->value_decimal) }}" required>

                            @error('value_decimal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                @elseif($setting->value_type === 'integer')
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label">New Integer Value <span class="text-danger">*</span></label>
                            <input type="number" step="1" name="value_integer" class="form-control @error('value_integer') is-invalid @enderror" value="{{ old('value_integer', $setting->value_integer) }}" required>

                            @error('value_integer')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                @elseif($setting->value_type === 'string')
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label">New Text Value <span class="text-danger">*</span></label>
                            <input type="text" name="value_string" class="form-control @error('value_string') is-invalid @enderror" value="{{ old('value_string', $setting->value_string) }}" maxlength="1000" required>

                            @error('value_string')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                @elseif($setting->value_type === 'boolean')
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label">New Value <span class="text-danger">*</span></label>

                            <select name="value_boolean" class="form-select @error('value_boolean') is-invalid @enderror" required>
                                <option value="1" @selected((string) old('value_boolean', $setting->value_boolean) === '1')>Yes</option>
                                <option value="0" @selected((string) old('value_boolean', $setting->value_boolean) === '0')>No</option>
                            </select>

                            @error('value_boolean')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                @endif

                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label">New Effective From <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from') }}" required>

                        @error('effective_from')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        <small class="text-muted">The current version will end one day before this date.</small>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">Source / Authority</label>
                        <input type="text" name="source_authority" class="form-control @error('source_authority') is-invalid @enderror" value="{{ old('source_authority', $setting->source_authority) }}" maxlength="255" placeholder="e.g. LAPF Rules, Board Resolution, IPEC approval">

                        @error('source_authority')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $setting->notes) }}</textarea>

                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="mb-3">
                        <label class="form-label">Change Reason <span class="text-danger">*</span></label>
                        <textarea name="change_reason" class="form-control @error('change_reason') is-invalid @enderror" rows="3" maxlength="2000" placeholder="Explain why a new version of this rule is being introduced" required>{{ old('change_reason') }}</textarea>

                        @error('change_reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        <small class="text-muted">This reason will be permanently recorded in the audit trail.</small>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('pensions-administration.settings.general.index') }}" class="btn btn-light"><i class="mdi mdi-close me-1"></i>Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-source-branch me-1"></i>Create New Version</button>
            </div>
        </form>
    </div>
</div>

@endsection
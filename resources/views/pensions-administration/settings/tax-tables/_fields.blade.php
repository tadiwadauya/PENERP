<div class="card mb-3">
    <div class="card-body">
        <h4 class="header-title mb-3">Tax Table Details</h4>

        <div class="row">
            <div class="col-lg-6">
                <div class="mb-3">
                    <label class="form-label">Table Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $taxTable->name ?? '') }}" maxlength="180" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="col-lg-3">
                <div class="mb-3">
                    <label class="form-label">Tax Year <span class="text-danger">*</span></label>
                    <input type="text" name="tax_year" class="form-control @error('tax_year') is-invalid @enderror" value="{{ old('tax_year', $taxTable->tax_year ?? '') }}" maxlength="20" required>
                    @error('tax_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="col-lg-3">
                <div class="mb-3">
                    <label class="form-label">Currency <span class="text-danger">*</span></label>
                    <input type="text" name="currency" class="form-control text-uppercase @error('currency') is-invalid @enderror" value="{{ old('currency', $taxTable->currency ?? 'ZWG') }}" maxlength="10" required>
                    @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="col-lg-6">
                <div class="mb-3">
                    <label class="form-label">Benefit Type <span class="text-danger">*</span></label>
                    <input type="text" name="benefit_type" class="form-control @error('benefit_type') is-invalid @enderror" value="{{ old('benefit_type', $taxTable->benefit_type ?? '') }}" maxlength="80" required>
                    @error('benefit_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="col-lg-3">
                <div class="mb-3">
                    <label class="form-label">Effective From <span class="text-danger">*</span></label>
                    <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from', isset($taxTable) ? $taxTable->effective_from?->format('Y-m-d') : '') }}" required>
                    @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            @if(!($versionMode ?? false))
                <div class="col-lg-3">
                    <div class="mb-3">
                        <label class="form-label">Effective To</label>
                        <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror" value="{{ old('effective_to', isset($taxTable) ? $taxTable->effective_to?->format('Y-m-d') : '') }}">
                        @error('effective_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            @endif

            <div class="col-lg-12">
                <div class="mb-3">
                    <label class="form-label">Source / Authority</label>
                    <input type="text" name="source_authority" class="form-control @error('source_authority') is-invalid @enderror" value="{{ old('source_authority', $taxTable->source_authority ?? '') }}" maxlength="255">
                    @error('source_authority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="col-lg-12">
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $taxTable->notes ?? '') }}</textarea>
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
</div>
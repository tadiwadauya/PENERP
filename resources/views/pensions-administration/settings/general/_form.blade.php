@php
    $record = $setting ?? null;
    $selectedType = old('value_type', $record?->value_type ?? 'decimal');
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Please correct the following:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">Category <span class="text-danger">*</span></label>
            <input type="text" name="category" class="form-control" value="{{ old('category', $record?->category) }}" maxlength="100" required>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">Setting Key <span class="text-danger">*</span></label>
            <input type="text" name="setting_key" class="form-control" value="{{ old('setting_key', $record?->setting_key) }}" maxlength="180" placeholder="e.g. retirement.maximum_age" required>
            <small class="text-muted">Use lowercase letters, numbers, dots, underscores or hyphens.</small>
        </div>
    </div>

    <div class="col-lg-12">
        <div class="mb-3">
            <label class="form-label">Rule Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $record?->name) }}" maxlength="180" required>
        </div>
    </div>

    <div class="col-lg-12">
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $record?->description) }}</textarea>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="mb-3">
            <label class="form-label">Value Type <span class="text-danger">*</span></label>
            <select name="value_type" id="value_type" class="form-select" required>
                <option value="decimal" @selected($selectedType === 'decimal')>Decimal</option>
                <option value="integer" @selected($selectedType === 'integer')>Integer</option>
                <option value="string" @selected($selectedType === 'string')>Text</option>
                <option value="boolean" @selected($selectedType === 'boolean')>Yes / No</option>
            </select>
        </div>
    </div>

    <div class="col-lg-8">
        <div id="decimal_value_group" class="mb-3">
            <label class="form-label">Decimal Value</label>
            <input type="number" step="0.00000001" name="value_decimal" class="form-control" value="{{ old('value_decimal', $record?->value_decimal) }}">
        </div>

        <div id="integer_value_group" class="mb-3">
            <label class="form-label">Integer Value</label>
            <input type="number" step="1" name="value_integer" class="form-control" value="{{ old('value_integer', $record?->value_integer) }}">
        </div>

        <div id="string_value_group" class="mb-3">
            <label class="form-label">Text Value</label>
            <input type="text" name="value_string" class="form-control" maxlength="1000" value="{{ old('value_string', $record?->value_string) }}">
        </div>

        <div id="boolean_value_group" class="mb-3">
            <label class="form-label">Boolean Value</label>
            <select name="value_boolean" class="form-select">
                <option value="">Select...</option>
                <option value="1" @selected((string) old('value_boolean', $record?->value_boolean) === '1')>Yes</option>
                <option value="0" @selected((string) old('value_boolean', $record?->value_boolean) === '0')>No</option>
            </select>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">Effective From <span class="text-danger">*</span></label>
            <input type="date" name="effective_from" class="form-control" value="{{ old('effective_from', $record?->effective_from?->format('Y-m-d')) }}" required>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="mb-3">
            <label class="form-label">Effective To</label>
            <input type="date" name="effective_to" class="form-control" value="{{ old('effective_to', $record?->effective_to?->format('Y-m-d')) }}">
            <small class="text-muted">Leave blank for an open-ended rule.</small>
        </div>
    </div>

    <div class="col-lg-12">
        <div class="mb-3">
            <label class="form-label">Source / Authority</label>
            <input type="text" name="source_authority" class="form-control" value="{{ old('source_authority', $record?->source_authority) }}" maxlength="255">
        </div>
    </div>

    <div class="col-lg-12">
        <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $record?->notes) }}</textarea>
        </div>
    </div>

    <div class="col-lg-12">
        <div class="mb-3">
            <label class="form-label">Change Reason <span class="text-danger">*</span></label>
            <textarea name="change_reason" class="form-control" rows="3" maxlength="2000" required>{{ old('change_reason') }}</textarea>
            <small class="text-muted">This reason will be permanently recorded in the audit trail.</small>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const type = document.getElementById('value_type');

    function updateValueFields() {
        const value = type.value;

        document.getElementById('decimal_value_group').style.display = value === 'decimal' ? '' : 'none';
        document.getElementById('integer_value_group').style.display = value === 'integer' ? '' : 'none';
        document.getElementById('string_value_group').style.display = value === 'string' ? '' : 'none';
        document.getElementById('boolean_value_group').style.display = value === 'boolean' ? '' : 'none';
    }

    type.addEventListener('change', updateValueFields);
    updateValueFields();
});
</script>
@endpush
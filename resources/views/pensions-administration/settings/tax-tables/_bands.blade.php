<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="header-title mb-1">Tax Bands</h4>
                <p class="text-muted mb-0">Enter bands from the lowest threshold to the highest threshold.</p>
            </div>
            <button type="button" class="btn btn-sm btn-primary" id="addTaxBand"><i class="mdi mdi-plus me-1"></i>Add Band</button>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle" id="taxBandsTable">
                <thead>
                    <tr>
                        <th style="width:70px;">Band</th>
                        <th>Lower Limit</th>
                        <th>Upper Limit</th>
                        <th>Rate %</th>
                        <th>Fixed Amount</th>
                        <th style="width:80px;"></th>
                    </tr>
                </thead>
                <tbody id="taxBandsBody">
                    @php
                        $existingBands = old('bands', isset($taxTable) ? $taxTable->bands->map(fn($band) => [
                            'lower_limit' => $band->lower_limit,
                            'upper_limit' => $band->upper_limit,
                            'rate_percentage' => $band->rate_percentage,
                            'fixed_amount' => $band->fixed_amount,
                        ])->toArray() : [
                            ['lower_limit' => '0', 'upper_limit' => '', 'rate_percentage' => '0', 'fixed_amount' => '0']
                        ]);
                    @endphp

                    @foreach($existingBands as $index => $band)
                        <tr class="tax-band-row">
                            <td class="band-number text-center">{{ $index + 1 }}</td>
                            <td><input type="number" name="bands[{{ $index }}][lower_limit]" class="form-control" value="{{ $band['lower_limit'] ?? '' }}" min="0" step="0.0001" required></td>
                            <td><input type="number" name="bands[{{ $index }}][upper_limit]" class="form-control" value="{{ $band['upper_limit'] ?? '' }}" min="0" step="0.0001" placeholder="Open ended"></td>
                            <td><input type="number" name="bands[{{ $index }}][rate_percentage]" class="form-control" value="{{ $band['rate_percentage'] ?? '0' }}" min="0" max="100" step="0.0001" required></td>
                            <td><input type="number" name="bands[{{ $index }}][fixed_amount]" class="form-control" value="{{ $band['fixed_amount'] ?? '0' }}" min="0" step="0.0001" required></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-tax-band"><i class="mdi mdi-delete-outline"></i></button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @error('bands')<div class="text-danger small">{{ $message }}</div>@enderror
        <small class="text-muted">Only the final band should normally have a blank Upper Limit.</small>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const body = document.getElementById('taxBandsBody');
    const addButton = document.getElementById('addTaxBand');

    function reindexBands() {
        body.querySelectorAll('.tax-band-row').forEach((row, index) => {
            row.querySelector('.band-number').textContent = index + 1;
            row.querySelectorAll('input').forEach(input => {
                const match = input.name.match(/\[([^\]]+)\]$/);
                if (match) input.name = `bands[${index}][${match[1]}]`;
            });
        });
    }

    addButton.addEventListener('click', function () {
        const index = body.querySelectorAll('.tax-band-row').length;
        const row = document.createElement('tr');
        row.className = 'tax-band-row';
        row.innerHTML = `
            <td class="band-number text-center">${index + 1}</td>
            <td><input type="number" name="bands[${index}][lower_limit]" class="form-control" min="0" step="0.0001" required></td>
            <td><input type="number" name="bands[${index}][upper_limit]" class="form-control" min="0" step="0.0001" placeholder="Open ended"></td>
            <td><input type="number" name="bands[${index}][rate_percentage]" class="form-control" value="0" min="0" max="100" step="0.0001" required></td>
            <td><input type="number" name="bands[${index}][fixed_amount]" class="form-control" value="0" min="0" step="0.0001" required></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-tax-band"><i class="mdi mdi-delete-outline"></i></button></td>
        `;
        body.appendChild(row);
        reindexBands();
    });

    body.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-tax-band');
        if (!button) return;

        if (body.querySelectorAll('.tax-band-row').length <= 1) return;

        button.closest('.tax-band-row').remove();
        reindexBands();
    });
});
</script>
@endpush
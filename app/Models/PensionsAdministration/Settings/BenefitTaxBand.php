<?php

namespace App\Models\PensionsAdministration\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BenefitTaxBand extends Model
{
    use HasFactory;

    protected $table = 'benefit_tax_bands';
    protected $guarded = [];

    protected $casts = [
        'band_order' => 'integer',
        'lower_limit' => 'decimal:4',
        'upper_limit' => 'decimal:4',
        'rate_percentage' => 'decimal:4',
        'fixed_amount' => 'decimal:4',
    ];

    public function taxTable(): BelongsTo
    {
        return $this->belongsTo(BenefitTaxTable::class, 'tax_table_id');
    }
}
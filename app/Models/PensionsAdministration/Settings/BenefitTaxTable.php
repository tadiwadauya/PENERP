<?php

namespace App\Models\PensionsAdministration\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BenefitTaxTable extends Model
{
    use HasFactory;

    protected $table = 'benefit_tax_tables';
    protected $guarded = [];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function bands(): HasMany
    {
        return $this->hasMany(BenefitTaxBand::class, 'tax_table_id')->orderBy('band_order');
    }
}
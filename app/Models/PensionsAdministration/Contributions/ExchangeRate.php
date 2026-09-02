<?php

namespace App\Models\PensionsAdministration\Contributions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExchangeRate extends Model
{
    protected $fillable = [
        'rate_date',
        'from_currency',
        'to_currency',
        'rate',
        'source',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'rate_date' => 'date',
        'rate' => 'decimal:8',
    ];

    public function receipts(): HasMany
    {
        return $this->hasMany(ContributionReceipt::class, 'exchange_rate_id');
    }
}
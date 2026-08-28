<?php

namespace App\Models\PensionsAdministration\Contributions;

use Illuminate\Database\Eloquent\Model;

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
}
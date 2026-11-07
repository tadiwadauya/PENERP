<?php

namespace App\Models\PensionsAdministration\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalInterestRate extends Model
{
    use HasFactory;

    protected $guarded=[];

    protected $casts=[
        'rate_date'=>'date',
        'rate_percentage'=>'decimal:8',
        'is_active'=>'boolean',
    ];
}
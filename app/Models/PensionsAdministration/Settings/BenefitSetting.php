<?php

namespace App\Models\PensionsAdministration\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BenefitSetting extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = ['value_decimal'=>'decimal:8','value_integer'=>'integer','value_boolean'=>'boolean','effective_from'=>'date','effective_to'=>'date','is_active'=>'boolean'];
}

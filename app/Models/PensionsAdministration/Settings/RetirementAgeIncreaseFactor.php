<?php

namespace App\Models\PensionsAdministration\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetirementAgeIncreaseFactor extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = ['age_years'=>'integer','increase_percentage'=>'decimal:4','effective_from'=>'date','effective_to'=>'date','is_active'=>'boolean'];
}

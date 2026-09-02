<?php

namespace App\Models\PensionsAdministration\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommutationFactor extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = ['age_years'=>'integer','age_months'=>'integer','factor'=>'decimal:6','effective_from'=>'date','effective_to'=>'date','is_active'=>'boolean'];
}

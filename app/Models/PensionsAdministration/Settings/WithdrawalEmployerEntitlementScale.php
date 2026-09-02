<?php

namespace App\Models\PensionsAdministration\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalEmployerEntitlementScale extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = ['minimum_service_months'=>'integer','maximum_service_months'=>'integer','entitlement_percentage'=>'decimal:4','effective_from'=>'date','effective_to'=>'date','is_active'=>'boolean'];
}

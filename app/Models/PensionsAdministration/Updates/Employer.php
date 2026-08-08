<?php

namespace App\Models\PensionsAdministration\Updates;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employer_number',
        'penad_employer_number',
        'fundworx_employer_number',

        'employer_group_id',

        'name',
        'short_name',
        'corporate_form',

        'fund_number',
        'scheme_code',

        'tpin',
        'business_registration_number',

        'email',
        'telephone',

        'postal_address',
        'physical_address',

        'status',
        'is_active',

        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function employerGroup(): BelongsTo
    {
        return $this->belongsTo(
            EmployerGroup::class,
            'employer_group_id'
        );
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(
            EmployerContact::class
        );
    }

    public function memberEmployments(): HasMany
    {
        return $this->hasMany(
            MemberEmployment::class
        );
    }

    public function currentMemberEmployments(): HasMany
    {
        return $this->hasMany(
            MemberEmployment::class
        )->where(
            'is_current',
            true
        );
    }
}
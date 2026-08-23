<?php

namespace App\Models\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContributionPeriod extends Model
{
    protected $fillable = [
        'employer_id',
        'period_date',
        'due_date',
        'period_year',
        'period_month',
        'scheme_code',
        'status',
        'scheduled_members',
        'existing_members',
        'new_members',
        'nil_contributors',
        'created_by',
        'updated_by',
    ];


    protected function casts(): array
    {
        return [
            'period_date' =>
                'date',

            'due_date' =>
                'date',

            'period_year' =>
                'integer',

            'period_month' =>
                'integer',

            'scheduled_members' =>
                'integer',

            'existing_members' =>
                'integer',

            'new_members' =>
                'integer',

            'nil_contributors' =>
                'integer',
        ];
    }


    public function employer(): BelongsTo
    {
        return $this->belongsTo(
            Employer::class
        );
    }


    public function importBatches(): HasMany
    {
        return $this->hasMany(
            ContributionImportBatch::class,
            'contribution_period_id'
        );
    }


    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }
}
<?php

namespace App\Models\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Updates\Employer;
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


    /*
    |--------------------------------------------------------------------------
    | Employer
    |--------------------------------------------------------------------------
    */

    public function employer(): BelongsTo
    {
        return $this->belongsTo(
            Employer::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Import Batches
    |--------------------------------------------------------------------------
    */

    public function importBatches(): HasMany
    {
        return $this->hasMany(
            ContributionImportBatch::class,
            'contribution_period_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Monthly Member Statuses
    |--------------------------------------------------------------------------
    */

    public function memberStatuses(): HasMany
    {
        return $this->hasMany(
            ContributionPeriodMemberStatus::class,
            'contribution_period_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Period Label
    |--------------------------------------------------------------------------
    */

    public function getPeriodLabelAttribute(): string
    {
        if (!$this->period_date) {
            return '-';
        }

        return $this
            ->period_date
            ->format('F Y');
    }
}
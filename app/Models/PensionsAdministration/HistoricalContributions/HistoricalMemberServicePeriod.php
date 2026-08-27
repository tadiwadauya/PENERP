<?php

namespace App\Models\PensionsAdministration\HistoricalContributions;

use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricalMemberServicePeriod extends Model
{
    protected $table =
        'historical_member_service_periods';

    protected $guarded = [];

    protected $casts = [
        'period_date' =>
            'date',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(
            Member::class,
            'member_id'
        );
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(
            Employer::class,
            'employer_id'
        );
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            HistoricalContributionImportBatch::class,
            'historical_import_batch_id'
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
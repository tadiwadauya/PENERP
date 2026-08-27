<?php

namespace App\Models\PensionsAdministration\HistoricalContributions;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HistoricalContributionImportBatch extends Model
{
    protected $table =
        'historical_contribution_import_batches';

    protected $guarded = [];

    protected $casts = [
        'progress_percentage' =>
            'decimal:2',

        'processing_started_at' =>
            'datetime',

        'validation_completed_at' =>
            'datetime',

        'posting_started_at' =>
            'datetime',

        'completed_at' =>
            'datetime',

        'approved_at' =>
            'datetime',

        'posted_at' =>
            'datetime',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(
            HistoricalContributionImportRow::class,
            'import_batch_id'
        );
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'posted_by'
        );
    }
}
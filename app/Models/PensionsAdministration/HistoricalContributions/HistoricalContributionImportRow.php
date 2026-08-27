<?php

namespace App\Models\PensionsAdministration\HistoricalContributions;

use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricalContributionImportRow extends Model
{
    protected $table =
        'historical_contribution_import_rows';

    protected $guarded = [];

    protected $casts = [
        'period_date' =>
            'date',

        'date_of_birth' =>
            'date',

        'date_joined_fund' =>
            'date',

        'date_joined_employer' =>
            'date',

        'is_new_member' =>
            'boolean',

        'employee_contribution_was_blank' =>
            'boolean',

        'employer_contribution_was_blank' =>
            'boolean',

        'basic_pay' =>
            'decimal:4',

        'employee_rate' =>
            'decimal:6',

        'employer_rate' =>
            'decimal:6',

        'employee_contribution' =>
            'decimal:4',

        'employer_contribution' =>
            'decimal:4',

        'employee_avc' =>
            'decimal:4',

        'employer_avc' =>
            'decimal:4',

        'employee_arrear' =>
            'decimal:4',

        'employer_arrear' =>
            'decimal:4',

        'employee_transfer_in' =>
            'decimal:4',

        'employer_transfer_in' =>
            'decimal:4',

        'employee_late_interest' =>
            'decimal:4',

        'employer_late_interest' =>
            'decimal:4',

        'reviewed_at' =>
            'datetime',

        'posted_at' =>
            'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            HistoricalContributionImportBatch::class,
            'import_batch_id'
        );
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(
            Employer::class,
            'matched_employer_id'
        );
    }

    public function matchedMember(): BelongsTo
    {
        return $this->belongsTo(
            Member::class,
            'matched_member_id'
        );
    }

    public function createdMember(): BelongsTo
    {
        return $this->belongsTo(
            Member::class,
            'created_member_id'
        );
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by'
        );
    }
}
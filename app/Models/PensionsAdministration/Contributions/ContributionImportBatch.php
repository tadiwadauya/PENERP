<?php

namespace App\Models\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContributionImportBatch extends Model
{
    protected $fillable = [
        'import_uuid',

        'contribution_period_id',
        'employer_id',

        'original_filename',
        'stored_filename',
        'file_path',
        'file_extension',
        'file_size',
        'file_hash',

        'source_system',
        'scheme_code',
        'due_date',

        'status',
        'progress_percentage',

        'total_rows',
        'processed_rows',

        'valid_rows',
        'warning_rows',
        'error_rows',

        'existing_member_rows',
        'new_member_rows',
        'nil_contributor_rows',

        'usd_basic_pay_total',

        'usd_employee_contribution_total',
        'usd_employer_contribution_total',

        'usd_employee_avc_total',
        'usd_employer_avc_total',

        'zwg_basic_pay_total',

        'zwg_employee_contribution_total',
        'zwg_employer_contribution_total',

        'zwg_employee_avc_total',
        'zwg_employer_avc_total',

        'failure_reason',

        'processing_started_at',
        'completed_at',

        'uploaded_by',

        'approved_by',
        'approved_at',
        'approval_notes',

        'posted_by',
        'posted_at',
        'posted_rows',
    ];


    protected function casts(): array
    {
        return [
            'due_date' =>
                'date',

            'progress_percentage' =>
                'decimal:2',

            'processing_started_at' =>
                'datetime',

            'completed_at' =>
                'datetime',

            'approved_at' =>
                'datetime',

            'posted_at' =>
                'datetime',

            'posted_rows' =>
                'integer',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Contribution Period
    |--------------------------------------------------------------------------
    */

    public function contributionPeriod(): BelongsTo
    {
        return $this->belongsTo(
            ContributionPeriod::class,
            'contribution_period_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Employer
    |--------------------------------------------------------------------------
    */

    public function employer(): BelongsTo
    {
        return $this->belongsTo(
            Employer::class,
            'employer_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Uploaded By
    |--------------------------------------------------------------------------
    */

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Approved By
    |--------------------------------------------------------------------------
    */

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Posted By
    |--------------------------------------------------------------------------
    */

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'posted_by'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Import Rows
    |--------------------------------------------------------------------------
    */

    public function rows(): HasMany
    {
        return $this->hasMany(
            ContributionImportRow::class,
            'import_batch_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Permanent Contributions
    |--------------------------------------------------------------------------
    */

    public function contributions(): HasMany
    {
        return $this->hasMany(
            MemberContribution::class,
            'import_batch_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Status Label
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {

            'uploaded' =>
                'Uploaded',

            'processing' =>
                'Validating',

            'awaiting_review' =>
                'Awaiting Review',

            'approved' =>
                'Approved',

            'posting' =>
                'Posting',

            'posted' =>
                'Posted',

            'cancelled' =>
                'Cancelled',

            'failed' =>
                'Failed',

            default =>
                ucwords(
                    str_replace(
                        '_',
                        ' ',
                        $this->status
                    )
                ),
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Status Badge
    |--------------------------------------------------------------------------
    */

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {

            'uploaded' =>
                'bg-info',

            'processing' =>
                'bg-primary',

            'awaiting_review' =>
                'bg-warning text-dark',

            'approved' =>
                'bg-success',

            'posting' =>
                'bg-primary',

            'posted' =>
                'bg-success',

            'cancelled' =>
                'bg-secondary',

            'failed' =>
                'bg-danger',

            default =>
                'bg-secondary',
        };
    }
}
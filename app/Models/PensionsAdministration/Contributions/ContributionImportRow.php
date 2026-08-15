<?php

namespace App\Models\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Updates\Member;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContributionImportRow extends Model
{
    protected $fillable = [
        'import_batch_id',

        'row_number',

        'raw_data',
        'normalized_data',

        'matched_member_id',
        'match_type',

        'is_new_member',
        'member_created',
        'created_member_id',

        'validation_status',

        'error_messages',
        'warning_messages',
    ];


    protected function casts(): array
    {
        return [
            'raw_data' =>
                'array',

            'normalized_data' =>
                'array',

            'is_new_member' =>
                'boolean',

            'member_created' =>
                'boolean',

            'error_messages' =>
                'array',

            'warning_messages' =>
                'array',
        ];
    }


    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            ContributionImportBatch::class,
            'import_batch_id'
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


    public function getValidationLabelAttribute(): string
    {
        return match ($this->validation_status) {

            'valid' =>
                'Valid',

            'warning' =>
                'Warning',

            'error' =>
                'Error',

            'pending' =>
                'Pending',

            default =>
                ucfirst(
                    $this->validation_status
                ),
        };
    }


    public function getValidationBadgeAttribute(): string
    {
        return match ($this->validation_status) {

            'valid' =>
                'bg-success',

            'warning' =>
                'bg-warning text-dark',

            'error' =>
                'bg-danger',

            default =>
                'bg-secondary',
        };
    }


    public function getMatchLabelAttribute(): string
    {
        return match ($this->match_type) {

            'penad_number' =>
                'PenAd Number',

            'penerp_number' =>
                'PENERP Number',

            'staff_number' =>
                'Staff Number + Employer',

            'national_id' =>
                'National ID',

            'new_member' =>
                'New Member',

            'conflict' =>
                'Identifier Conflict',

            default =>
                'Not Matched',
        };
    }
}
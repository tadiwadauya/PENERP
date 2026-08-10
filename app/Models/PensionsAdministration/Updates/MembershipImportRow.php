<?php

namespace App\Models\PensionsAdministration\Updates;

use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipImportRow extends Model
{
    protected $table =
        'membership_import_rows';


    protected $fillable = [
        'import_batch_id',
        'row_number',

        'import_action',

        'raw_data',
        'normalized_data',

        'validation_status',

        'error_messages',
        'warning_messages',

        'matched_employer_id',

        'duplicate_status',
        'matched_member_id',
        'duplicate_score',
        'duplicate_reasons',

        'review_decision',
        'review_notes',
        'reviewed_by',
        'reviewed_at',

        'imported_member_id',
        'imported_at',
    ];


    protected function casts(): array
    {
        return [
            'raw_data' =>
                'array',

            'normalized_data' =>
                'array',

            'error_messages' =>
                'array',

            'warning_messages' =>
                'array',

            'duplicate_reasons' =>
                'array',

            'duplicate_score' =>
                'decimal:2',

            'reviewed_at' =>
                'datetime',

            'imported_at' =>
                'datetime',
        ];
    }


    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            MembershipImportBatch::class,
            'import_batch_id'
        );
    }


    public function matchedEmployer(): BelongsTo
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


    public function importedMember(): BelongsTo
    {
        return $this->belongsTo(
            Member::class,
            'imported_member_id'
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
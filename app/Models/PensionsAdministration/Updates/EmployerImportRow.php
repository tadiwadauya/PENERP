<?php

namespace App\Models\PensionsAdministration\Updates;

use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployerImportRow extends Model
{
    protected $table = 'employer_import_rows';

    protected $fillable = [
        'import_batch_id',
        'row_number',
        'import_action',

        'raw_data',
        'normalized_data',

        'validation_status',
        'error_messages',
        'warning_messages',

        'matched_employer_group_id',
        'matched_employer_id',

        'duplicate_status',
        'duplicate_score',
        'duplicate_reasons',

        'review_decision',
        'review_notes',
        'reviewed_by',
        'reviewed_at',

        'imported_employer_id',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'normalized_data' => 'array',

            'error_messages' => 'array',
            'warning_messages' => 'array',
            'duplicate_reasons' => 'array',

            'duplicate_score' => 'decimal:2',

            'reviewed_at' => 'datetime',
            'imported_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            EmployerImportBatch::class,
            'import_batch_id'
        );
    }

    public function matchedEmployerGroup(): BelongsTo
    {
        return $this->belongsTo(
            EmployerGroup::class,
            'matched_employer_group_id'
        );
    }

    public function matchedEmployer(): BelongsTo
    {
        return $this->belongsTo(
            Employer::class,
            'matched_employer_id'
        );
    }

    public function importedEmployer(): BelongsTo
    {
        return $this->belongsTo(
            Employer::class,
            'imported_employer_id'
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
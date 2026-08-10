<?php

namespace App\Models\PensionsAdministration\Updates;

use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployerImportBatch extends Model
{
    protected $table = 'employer_import_batches';

    protected $fillable = [
        'import_uuid',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_extension',
        'file_size',
        'import_type',

        'total_rows',
        'processed_rows',
        'valid_rows',
        'warning_rows',
        'error_rows',
        'duplicate_rows',
        'approved_rows',
        'rejected_rows',
        'imported_rows',

        'progress_percentage',
        'status',
        'failure_reason',

        'uploaded_by',
        'approved_by',

        'started_at',
        'completed_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'progress_percentage' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function rows(): HasMany
    {
        return $this->hasMany(
            EmployerImportRow::class,
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

    public function getStatusLabelAttribute(): string
    {
        return ucwords(
            str_replace('_', ' ', $this->status)
        );
    }

    public function getIsFinishedAttribute(): bool
    {
        return in_array(
            $this->status,
            [
                'completed',
                'failed',
                'cancelled',
            ],
            true
        );
    }
}
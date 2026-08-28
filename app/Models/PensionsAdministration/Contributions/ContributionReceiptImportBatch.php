<?php

namespace App\Models\PensionsAdministration\Contributions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContributionReceiptImportBatch extends Model
{
    protected $fillable = [
        'import_uuid',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_extension',
        'file_size',

        'default_currency',

        'total_rows',
        'processed_rows',
        'valid_rows',
        'error_rows',
        'posted_rows',

        'progress_percentage',
        'status',
        'failure_reason',

        'uploaded_by',
        'posted_by',

        'started_at',
        'completed_at',
        'posted_at',
    ];


    protected $casts = [
        'progress_percentage' => 'decimal:2',

        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'posted_at' => 'datetime',
    ];


    public function rows(): HasMany
    {
        return $this->hasMany(
            ContributionReceiptImportRow::class,
            'import_batch_id'
        );
    }
}
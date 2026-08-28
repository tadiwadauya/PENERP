<?php

namespace App\Models\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Updates\Employer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContributionReceiptImportRow extends Model
{
    protected $fillable = [
        'import_batch_id',
        'row_number',

        'employer_code',
        'matched_employer_id',

        'receipt_date',
        'due_date',
        'contribution_period',

        'currency',
        'original_amount',

        'exchange_rate',
        'exchange_rate_id',
        'amount_zwg',

        'validation_status',
        'error_messages',

        'receipt_fingerprint',

        'imported_receipt_id',
        'imported_at',
    ];


    protected $casts = [
        'receipt_date' => 'date',
        'due_date' => 'date',
        'contribution_period' => 'date',

        'original_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:8',
        'amount_zwg' => 'decimal:2',

        'imported_at' => 'datetime',
    ];


    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            ContributionReceiptImportBatch::class,
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


    public function exchangeRate(): BelongsTo
    {
        return $this->belongsTo(
            ExchangeRate::class,
            'exchange_rate_id'
        );
    }


    public function importedReceipt(): BelongsTo
    {
        return $this->belongsTo(
            ContributionReceipt::class,
            'imported_receipt_id'
        );
    }
}
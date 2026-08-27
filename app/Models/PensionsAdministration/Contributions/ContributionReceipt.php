<?php

namespace App\Models\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Updates\Employer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContributionReceipt extends Model
{
    protected $fillable = [
        'employer_id',

        'receipt_date',
        'due_date',
        'contribution_period',

        'currency',
        'original_amount',

        'exchange_rate',
        'exchange_rate_id',

        'amount_zwg',

        'receipt_fingerprint',

        'source_import_batch_id',
        'source_import_row_id',

        'posted_by',
        'posted_at',
    ];


    protected $casts = [
        'receipt_date' => 'date',
        'due_date' => 'date',
        'contribution_period' => 'date',

        'original_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:8',
        'amount_zwg' => 'decimal:2',

        'posted_at' => 'datetime',
    ];


    public function employer(): BelongsTo
    {
        return $this->belongsTo(
            Employer::class,
            'employer_id'
        );
    }


    public function exchangeRate(): BelongsTo
    {
        return $this->belongsTo(
            ExchangeRate::class,
            'exchange_rate_id'
        );
    }


    public function sourceBatch(): BelongsTo
    {
        return $this->belongsTo(
            ContributionReceiptImportBatch::class,
            'source_import_batch_id'
        );
    }


    public function sourceRow(): BelongsTo
    {
        return $this->belongsTo(
            ContributionReceiptImportRow::class,
            'source_import_row_id'
        );
    }
}
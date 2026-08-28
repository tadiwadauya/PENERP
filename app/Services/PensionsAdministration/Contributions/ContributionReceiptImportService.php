<?php

namespace App\Services\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Contributions\ContributionReceipt;
use App\Models\PensionsAdministration\Contributions\ContributionReceiptImportBatch;
use App\Models\PensionsAdministration\Contributions\ContributionReceiptImportRow;
use App\Models\PensionsAdministration\Contributions\ExchangeRate;
use App\Models\PensionsAdministration\Updates\Employer;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;
use Throwable;

class ContributionReceiptImportService
{
    /*
    |--------------------------------------------------------------------------
    | Create Import Batch
    |--------------------------------------------------------------------------
    */

    public function createBatch(
        UploadedFile $file,
        string $defaultCurrency,
        ?int $userId
    ): ContributionReceiptImportBatch {
        $uuid =
            (string) Str::uuid();

        $extension =
            strtolower(
                $file->getClientOriginalExtension()
            );

        $storedFilename =
            strtolower($uuid)
            . '.'
            . $extension;

        $path =
            $file->storeAs(
                'contribution-receipt-imports',
                $storedFilename
            );

        return ContributionReceiptImportBatch::create([
            'import_uuid' =>
                strtoupper($uuid),

            'original_filename' =>
                $file->getClientOriginalName(),

            'stored_filename' =>
                $storedFilename,

            'file_path' =>
                $path,

            'file_extension' =>
                $extension,

            'file_size' =>
                $file->getSize() ?: 0,

            'default_currency' =>
                strtoupper($defaultCurrency),

            'total_rows' =>
                0,

            'processed_rows' =>
                0,

            'valid_rows' =>
                0,

            'error_rows' =>
                0,

            'posted_rows' =>
                0,

            'progress_percentage' =>
                0,

            'status' =>
                'processing',

            'failure_reason' =>
                null,

            'uploaded_by' =>
                $userId,

            'posted_by' =>
                null,

            'started_at' =>
                now(),

            'completed_at' =>
                null,

            'posted_at' =>
                null,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Process Uploaded Spreadsheet
    |--------------------------------------------------------------------------
    */

    public function process(
        ContributionReceiptImportBatch $batch
    ): void {
        try {
            $fullPath =
                Storage::path(
                    $batch->file_path
                );

            if (
                !file_exists(
                    $fullPath
                )
            ) {
                throw new RuntimeException(
                    'Uploaded receipt file could not be found.'
                );
            }

            $spreadsheet =
                IOFactory::load(
                    $fullPath
                );

            $sheet =
                $spreadsheet
                    ->getActiveSheet();

            $highestRow =
                $sheet
                    ->getHighestDataRow();

            $highestColumn =
                $sheet
                    ->getHighestDataColumn();


            /*
            |--------------------------------------------------------------------------
            | Read Header Row
            |--------------------------------------------------------------------------
            */

            $headerValues =
                $sheet
                    ->rangeToArray(
                        "A1:{$highestColumn}1",
                        null,
                        true,
                        false
                    )[0];

            $headers = [];

            foreach (
                $headerValues
                as $index => $header
            ) {
                $normalised =
                    $this->normaliseHeader(
                        (string) $header
                    );

                if (
                    $normalised !== ''
                ) {
                    $headers[
                        $normalised
                    ] = $index;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Required Columns
            |--------------------------------------------------------------------------
            */

            $requiredColumns = [
                'employer_code',
                'receipt_date',
                'due_date',
                'amount',
            ];

            foreach (
                $requiredColumns
                as $required
            ) {
                if (
                    !array_key_exists(
                        $required,
                        $headers
                    )
                ) {
                    throw new RuntimeException(
                        'Required column ['
                        . $required
                        . '] was not found.'
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Initialise Batch
            |--------------------------------------------------------------------------
            */

            $totalRows =
                max(
                    0,
                    $highestRow - 1
                );

            $batch->update([
                'total_rows' =>
                    $totalRows,

                'processed_rows' =>
                    0,

                'valid_rows' =>
                    0,

                'error_rows' =>
                    0,

                'posted_rows' =>
                    0,

                'progress_percentage' =>
                    0,

                'status' =>
                    'processing',

                'failure_reason' =>
                    null,

                'completed_at' =>
                    null,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Process Rows
            |--------------------------------------------------------------------------
            */

            for (
                $rowNumber = 2;
                $rowNumber <= $highestRow;
                $rowNumber++
            ) {
                $values =
                    $sheet
                        ->rangeToArray(
                            "A{$rowNumber}:{$highestColumn}{$rowNumber}",
                            null,
                            true,
                            false
                        )[0];

                if (
                    $this->rowIsBlank(
                        $values
                    )
                ) {
                    continue;
                }

                $this->processRow(
                    batch: $batch,
                    rowNumber: $rowNumber,
                    values: $values,
                    headers: $headers
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Final Counters
            |--------------------------------------------------------------------------
            */

            $this->refreshCounters(
                $batch
            );

            $processedRows =
                ContributionReceiptImportRow::query()
                    ->where(
                        'import_batch_id',
                        $batch->id
                    )
                    ->count();

            $batch->update([
                'processed_rows' =>
                    $processedRows,

                'progress_percentage' =>
                    100,

                'status' =>
                    'awaiting_review',

                'completed_at' =>
                    now(),
            ]);

            $spreadsheet
                ->disconnectWorksheets();

            unset($spreadsheet);

        } catch (Throwable $e) {
            $batch->update([
                'status' =>
                    'failed',

                'failure_reason' =>
                    $e->getMessage(),

                'completed_at' =>
                    now(),
            ]);

            throw $e;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Process Individual Row
    |--------------------------------------------------------------------------
    */

    private function processRow(
        ContributionReceiptImportBatch $batch,
        int $rowNumber,
        array $values,
        array $headers
    ): void {
        $errors = [];


        /*
        |--------------------------------------------------------------------------
        | Employer Code
        |--------------------------------------------------------------------------
        */

        $employerCode =
            $this->normaliseEmployerCode(
                $this->value(
                    $values,
                    $headers,
                    'employer_code'
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Receipt Date
        |--------------------------------------------------------------------------
        */

        $receiptDate =
            $this->parseDate(
                $this->value(
                    $values,
                    $headers,
                    'receipt_date'
                ),
                $errors,
                'Receipt Date'
            );


        /*
        |--------------------------------------------------------------------------
        | Due Date
        |--------------------------------------------------------------------------
        */

        $dueDate =
            $this->parseDate(
                $this->value(
                    $values,
                    $headers,
                    'due_date'
                ),
                $errors,
                'Due Date'
            );


        /*
        |--------------------------------------------------------------------------
        | Contribution Period
        |--------------------------------------------------------------------------
        |
        | The Due Date determines the contribution month.
        |
        | Example:
        | Due Date = 28 Feb 2025
        | Period   = 01 Feb 2025
        |--------------------------------------------------------------------------
        */

        $contributionPeriod =
            $dueDate
                ? $dueDate
                    ->copy()
                    ->startOfMonth()
                : null;


        /*
        |--------------------------------------------------------------------------
        | Amount
        |--------------------------------------------------------------------------
        */

        $amount =
            $this->parseAmount(
                $this->value(
                    $values,
                    $headers,
                    'amount'
                ),
                $errors
            );


        /*
        |--------------------------------------------------------------------------
        | Currency
        |--------------------------------------------------------------------------
        |
        | If the Excel file later contains a Currency column, the row value
        | overrides the batch default.
        |--------------------------------------------------------------------------
        */

        $excelCurrency =
            strtoupper(
                trim(
                    (string) (
                        $this->value(
                            $values,
                            $headers,
                            'currency'
                        )
                        ?? ''
                    )
                )
            );

        $currency =
            $excelCurrency !== ''
                ? $excelCurrency
                : strtoupper(
                    $batch->default_currency
                );

        if (
            !in_array(
                $currency,
                [
                    'ZWG',
                    'USD',
                ],
                true
            )
        ) {
            $errors[] =
                'Currency must be ZWG or USD.';
        }


        /*
        |--------------------------------------------------------------------------
        | Match Employer
        |--------------------------------------------------------------------------
        */

        $employer = null;

        if (
            $employerCode === ''
        ) {
            $errors[] =
                'Employer Code is required.';
        } else {
            $employer =
                Employer::query()
                    ->where(
                        'employer_number',
                        $employerCode
                    )
                    ->first();

            if (
                !$employer
            ) {
                $errors[] =
                    'Employer Code ['
                    . $employerCode
                    . '] was not found.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Exchange Rate / ZWG Equivalent
        |--------------------------------------------------------------------------
        */

        $exchangeRate =
            null;

        $exchangeRateId =
            null;

        $amountZwg =
            null;


        /*
        |--------------------------------------------------------------------------
        | ZWG Receipt
        |--------------------------------------------------------------------------
        */

        if (
            $currency === 'ZWG'
            &&
            $amount !== null
        ) {
            $exchangeRate =
                1;

            $amountZwg =
                round(
                    $amount,
                    2
                );
        }


        /*
        |--------------------------------------------------------------------------
        | USD Receipt
        |--------------------------------------------------------------------------
        */

        if (
            $currency === 'USD'
            &&
            $receiptDate !== null
            &&
            $amount !== null
        ) {
            $rateRecord =
                ExchangeRate::query()
                    ->whereDate(
                        'rate_date',
                        $receiptDate
                            ->format(
                                'Y-m-d'
                            )
                    )
                    ->where(
                        'from_currency',
                        'USD'
                    )
                    ->where(
                        'to_currency',
                        'ZWG'
                    )
                    ->first();

            if (
                !$rateRecord
            ) {
                $errors[] =
                    'No USD to ZWG exchange rate exists for '
                    . $receiptDate
                        ->format(
                            'd M Y'
                        )
                    . '.';
            } else {
                $exchangeRate =
                    (float) $rateRecord->rate;

                $exchangeRateId =
                    $rateRecord->id;

                $amountZwg =
                    round(
                        $amount
                        *
                        $exchangeRate,
                        2
                    );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Duplicate Handling
        |--------------------------------------------------------------------------
        |
        | IMPORTANT BUSINESS RULE:
        |
        | Receipt values are NOT used to determine duplicates.
        |
        | An employer can legitimately have two or more receipts with exactly
        | the same:
        |
        | - Employer
        | - Receipt Date
        | - Due Date
        | - Contribution Month
        | - Currency
        | - Amount
        |
        | Each Excel row therefore represents its own receipt transaction.
        |
        | The only duplicate protection occurs when posting: source_import_row_id
        | is unique in contribution_receipts, so the SAME STAGING ROW cannot be
        | posted twice.
        |--------------------------------------------------------------------------
        */

        $receiptFingerprint =
            null;


        /*
        |--------------------------------------------------------------------------
        | Validation Status
        |--------------------------------------------------------------------------
        */

        $validationStatus =
            count($errors) > 0
                ? 'error'
                : 'valid';


        /*
        |--------------------------------------------------------------------------
        | Save Staging Row
        |--------------------------------------------------------------------------
        */

        ContributionReceiptImportRow::updateOrCreate(
            [
                'import_batch_id' =>
                    $batch->id,

                'row_number' =>
                    $rowNumber,
            ],
            [
                'employer_code' =>
                    $employerCode !== ''
                        ? $employerCode
                        : null,

                'matched_employer_id' =>
                    $employer?->id,

                'receipt_date' =>
                    $receiptDate
                        ?->format(
                            'Y-m-d'
                        ),

                'due_date' =>
                    $dueDate
                        ?->format(
                            'Y-m-d'
                        ),

                'contribution_period' =>
                    $contributionPeriod
                        ?->format(
                            'Y-m-d'
                        ),

                'currency' =>
                    $currency,

                'original_amount' =>
                    $amount,

                'exchange_rate' =>
                    $exchangeRate,

                'exchange_rate_id' =>
                    $exchangeRateId,

                'amount_zwg' =>
                    $amountZwg,

                'validation_status' =>
                    $validationStatus,

                'error_messages' =>
                    count($errors) > 0
                        ? implode(
                            PHP_EOL,
                            $errors
                        )
                        : null,

                'receipt_fingerprint' =>
                    $receiptFingerprint,

                'imported_receipt_id' =>
                    null,

                'imported_at' =>
                    null,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Progress Update
        |--------------------------------------------------------------------------
        */

        if (
            ($rowNumber - 1) % 50
            ===
            0
        ) {
            $processed =
                ContributionReceiptImportRow::query()
                    ->where(
                        'import_batch_id',
                        $batch->id
                    )
                    ->count();

            $percentage =
                $batch->total_rows > 0
                    ? round(
                        (
                            $processed
                            /
                            $batch->total_rows
                        )
                        *
                        100,
                        2
                    )
                    : 0;

            $batch->update([
                'processed_rows' =>
                    $processed,

                'progress_percentage' =>
                    min(
                        100,
                        $percentage
                    ),
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Refresh Batch Counters
    |--------------------------------------------------------------------------
    */

    public function refreshCounters(
        ContributionReceiptImportBatch $batch
    ): void {
        $base =
            ContributionReceiptImportRow::query()
                ->where(
                    'import_batch_id',
                    $batch->id
                );

        $validRows =
            (clone $base)
                ->where(
                    'validation_status',
                    'valid'
                )
                ->count();

        $errorRows =
            (clone $base)
                ->where(
                    'validation_status',
                    'error'
                )
                ->count();

        $postedRows =
            (clone $base)
                ->whereNotNull(
                    'imported_at'
                )
                ->count();

        $batch->update([
            'valid_rows' =>
                $validRows,

            'error_rows' =>
                $errorRows,

            'posted_rows' =>
                $postedRows,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Post Valid Rows
    |--------------------------------------------------------------------------
    */

    public function postValid(
        ContributionReceiptImportBatch $batch,
        ?int $userId
    ): int {
        if (
            $batch->status === 'posted'
        ) {
            throw new RuntimeException(
                'This receipt batch has already been posted.'
            );
        }

        $batch->update([
            'status' =>
                'posting',

            'failure_reason' =>
                null,
        ]);

        try {
            $postedCount =
                0;

            ContributionReceiptImportRow::query()
                ->where(
                    'import_batch_id',
                    $batch->id
                )
                ->where(
                    'validation_status',
                    'valid'
                )
                ->whereNull(
                    'imported_at'
                )
                ->orderBy(
                    'id'
                )
                ->chunkById(
                    250,
                    function ($rows) use (
                        $batch,
                        $userId,
                        &$postedCount
                    ) {
                        DB::transaction(
                            function () use (
                                $rows,
                                $batch,
                                $userId,
                                &$postedCount
                            ) {
                                foreach (
                                    $rows
                                    as $row
                                ) {
                                    /*
                                    |--------------------------------------------------------------------------
                                    | Validate Posting Data
                                    |--------------------------------------------------------------------------
                                    */

                                    if (
                                        !$row->matched_employer_id
                                    ) {
                                        throw new RuntimeException(
                                            'Receipt row '
                                            . $row->row_number
                                            . ' has no matched employer.'
                                        );
                                    }

                                    if (
                                        !$row->receipt_date
                                    ) {
                                        throw new RuntimeException(
                                            'Receipt row '
                                            . $row->row_number
                                            . ' has no receipt date.'
                                        );
                                    }

                                    if (
                                        !$row->due_date
                                    ) {
                                        throw new RuntimeException(
                                            'Receipt row '
                                            . $row->row_number
                                            . ' has no due date.'
                                        );
                                    }

                                    if (
                                        !$row->contribution_period
                                    ) {
                                        throw new RuntimeException(
                                            'Receipt row '
                                            . $row->row_number
                                            . ' has no contribution period.'
                                        );
                                    }

                                    if (
                                        $row->original_amount === null
                                    ) {
                                        throw new RuntimeException(
                                            'Receipt row '
                                            . $row->row_number
                                            . ' has no original amount.'
                                        );
                                    }

                                    if (
                                        $row->amount_zwg === null
                                    ) {
                                        throw new RuntimeException(
                                            'Receipt row '
                                            . $row->row_number
                                            . ' has no ZWG amount.'
                                        );
                                    }


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Post Receipt
                                    |--------------------------------------------------------------------------
                                    |
                                    | No financial-value duplicate check.
                                    |
                                    | source_import_row_id uniquely identifies the staging row
                                    | and prevents that exact row from being posted twice.
                                    |--------------------------------------------------------------------------
                                    */

                                    $receipt =
                                        ContributionReceipt::firstOrCreate(
                                            [
                                                'source_import_row_id'
                                                    =>
                                                    $row->id,
                                            ],
                                            [
                                                'employer_id'
                                                    =>
                                                    $row
                                                        ->matched_employer_id,

                                                'receipt_date'
                                                    =>
                                                    $row
                                                        ->receipt_date,

                                                'due_date'
                                                    =>
                                                    $row
                                                        ->due_date,

                                                'contribution_period'
                                                    =>
                                                    $row
                                                        ->contribution_period,

                                                'currency'
                                                    =>
                                                    $row
                                                        ->currency,

                                                'original_amount'
                                                    =>
                                                    $row
                                                        ->original_amount,

                                                'exchange_rate'
                                                    =>
                                                    $row
                                                        ->exchange_rate
                                                    ?? 1,

                                                'exchange_rate_id'
                                                    =>
                                                    $row
                                                        ->exchange_rate_id,

                                                'amount_zwg'
                                                    =>
                                                    $row
                                                        ->amount_zwg,

                                                'receipt_fingerprint'
                                                    =>
                                                    null,

                                                'source_import_batch_id'
                                                    =>
                                                    $batch
                                                        ->id,

                                                'posted_by'
                                                    =>
                                                    $userId,

                                                'posted_at'
                                                    =>
                                                    now(),
                                            ]
                                        );


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Mark Staging Row Posted
                                    |--------------------------------------------------------------------------
                                    */

                                    if (
                                        !$row->imported_at
                                    ) {
                                        $row->update([
                                            'imported_receipt_id'
                                                =>
                                                $receipt->id,

                                            'imported_at'
                                                =>
                                                now(),
                                        ]);

                                        $postedCount++;
                                    }
                                }
                            }
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | Update Batch Posted Counter
                        |--------------------------------------------------------------------------
                        */

                        $currentPosted =
                            ContributionReceiptImportRow::query()
                                ->where(
                                    'import_batch_id',
                                    $batch->id
                                )
                                ->whereNotNull(
                                    'imported_at'
                                )
                                ->count();

                        $batch->update([
                            'posted_rows' =>
                                $currentPosted,
                        ]);
                    }
                );


            /*
            |--------------------------------------------------------------------------
            | Refresh Counters
            |--------------------------------------------------------------------------
            */

            $this->refreshCounters(
                $batch
            );

            $batch->refresh();


            /*
            |--------------------------------------------------------------------------
            | Final Status
            |--------------------------------------------------------------------------
            |
            | If there are validation errors, valid rows may still be posted.
            |
            | posted            = all rows valid and posted
            | partially_posted  = valid rows posted, error rows remain
            |--------------------------------------------------------------------------
            */

            $finalStatus =
                $batch->error_rows > 0
                    ? 'partially_posted'
                    : 'posted';

            $batch->update([
                'status' =>
                    $finalStatus,

                'posted_by' =>
                    $userId,

                'posted_at' =>
                    now(),

                'progress_percentage' =>
                    100,
            ]);

            return $postedCount;

        } catch (Throwable $e) {
            $batch->update([
                'status' =>
                    'failed',

                'failure_reason' =>
                    $e->getMessage(),
            ]);

            throw $e;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Normalise Header
    |--------------------------------------------------------------------------
    */

    private function normaliseHeader(
        string $header
    ): string {
        $header =
            strtolower(
                trim(
                    $header
                )
            );

        $header =
            preg_replace(
                '/[^a-z0-9]+/',
                '_',
                $header
            );

        $header =
            trim(
                (string) $header,
                '_'
            );

        return match ($header) {
            /*
            |--------------------------------------------------------------------------
            | Employer Code
            |--------------------------------------------------------------------------
            */

            'employer',
            'employer_code',
            'employer_number',
            'employer_no'
                =>
                'employer_code',


            /*
            |--------------------------------------------------------------------------
            | Receipt Date
            |--------------------------------------------------------------------------
            */

            'receipt_date',
            'date_received',
            'received_date',
            'date_of_receipt'
                =>
                'receipt_date',


            /*
            |--------------------------------------------------------------------------
            | Due Date
            |--------------------------------------------------------------------------
            */

            'due_date',
            'period_due_date',
            'contribution_due_date',
            'contribution_date'
                =>
                'due_date',


            /*
            |--------------------------------------------------------------------------
            | Amount
            |--------------------------------------------------------------------------
            */

            'amount',
            'receipt_amount',
            'amount_received',
            'received_amount'
                =>
                'amount',


            /*
            |--------------------------------------------------------------------------
            | Currency
            |--------------------------------------------------------------------------
            */

            'currency',
            'currency_code'
                =>
                'currency',

            default =>
                $header,
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Get Value By Header
    |--------------------------------------------------------------------------
    */

    private function value(
        array $values,
        array $headers,
        string $key
    ): mixed {
        if (
            !array_key_exists(
                $key,
                $headers
            )
        ) {
            return null;
        }

        return $values[
            $headers[$key]
        ] ?? null;
    }


    /*
    |--------------------------------------------------------------------------
    | Normalise Employer Code
    |--------------------------------------------------------------------------
    */

    private function normaliseEmployerCode(
        mixed $value
    ): string {
        if (
            $value === null
            ||
            trim(
                (string) $value
            ) === ''
        ) {
            return '';
        }

        /*
        |--------------------------------------------------------------------------
        | Excel may return numeric codes as floats
        |--------------------------------------------------------------------------
        |
        | 20.0 becomes "20"
        |--------------------------------------------------------------------------
        */

        if (
            is_numeric(
                $value
            )
        ) {
            $numeric =
                (float) $value;

            if (
                floor($numeric)
                ===
                $numeric
            ) {
                return (string) (
                    (int) $numeric
                );
            }
        }

        return trim(
            (string) $value
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Blank Row
    |--------------------------------------------------------------------------
    */

    private function rowIsBlank(
        array $values
    ): bool {
        foreach (
            $values
            as $value
        ) {
            if (
                $value !== null
                &&
                trim(
                    (string) $value
                ) !== ''
            ) {
                return false;
            }
        }

        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | Parse Date
    |--------------------------------------------------------------------------
    */

    private function parseDate(
        mixed $value,
        array &$errors,
        string $label
    ): ?Carbon {
        if (
            $value === null
            ||
            trim(
                (string) $value
            ) === ''
        ) {
            $errors[] =
                $label
                . ' is required.';

            return null;
        }

        try {
            /*
            |--------------------------------------------------------------------------
            | DateTime Object
            |--------------------------------------------------------------------------
            */

            if (
                $value instanceof \DateTimeInterface
            ) {
                return Carbon::instance(
                    \DateTime::createFromInterface(
                        $value
                    )
                )->startOfDay();
            }


            /*
            |--------------------------------------------------------------------------
            | Excel Numeric Date
            |--------------------------------------------------------------------------
            */

            if (
                is_numeric(
                    $value
                )
            ) {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject(
                        $value
                    )
                )->startOfDay();
            }


            /*
            |--------------------------------------------------------------------------
            | String Date
            |--------------------------------------------------------------------------
            */

            return Carbon::parse(
                trim(
                    (string) $value
                )
            )->startOfDay();

        } catch (Throwable) {
            $errors[] =
                $label
                . ' is invalid.';

            return null;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Parse Amount
    |--------------------------------------------------------------------------
    */

    private function parseAmount(
        mixed $value,
        array &$errors
    ): ?float {
        if (
            $value === null
            ||
            trim(
                (string) $value
            ) === ''
        ) {
            $errors[] =
                'Amount is required.';

            return null;
        }

        $clean =
            str_replace(
                [
                    ',',
                    ' ',
                ],
                '',
                trim(
                    (string) $value
                )
            );

        if (
            !is_numeric(
                $clean
            )
        ) {
            $errors[] =
                'Amount must be numeric.';

            return null;
        }

        $amount =
            (float) $clean;

        if (
            $amount <= 0
        ) {
            $errors[] =
                'Receipt amount must be greater than zero.';

            return null;
        }

        return round(
            $amount,
            2
        );
    }
}
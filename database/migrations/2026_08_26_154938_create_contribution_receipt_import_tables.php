<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Import Batch
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'contribution_receipt_import_batches',
            function (Blueprint $table): void {

                $table->id();

                $table->uuid('import_uuid')
                    ->unique();

                $table->string('original_filename');

                $table->string('stored_filename');

                $table->string('file_path');

                $table->string('file_extension', 10);

                $table->unsignedBigInteger('file_size')
                    ->default(0);


                /*
                |--------------------------------------------------------------------------
                | Currency selected during upload
                |--------------------------------------------------------------------------
                |
                | Sample file does not contain a Currency column.
                |
                */

                $table->string(
                    'default_currency',
                    3
                )->default('ZWG');


                /*
                |--------------------------------------------------------------------------
                | Counters
                |--------------------------------------------------------------------------
                */

                $table->unsignedInteger('total_rows')
                    ->default(0);

                $table->unsignedInteger('processed_rows')
                    ->default(0);

                $table->unsignedInteger('valid_rows')
                    ->default(0);

                $table->unsignedInteger('error_rows')
                    ->default(0);

                $table->unsignedInteger('posted_rows')
                    ->default(0);

                $table->decimal(
                    'progress_percentage',
                    6,
                    2
                )->default(0);


                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                |
                | processing
                | awaiting_review
                | posting
                | posted
                | partially_posted
                | failed
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'status',
                    50
                )->default('processing');

                $table->text('failure_reason')
                    ->nullable();


                /*
                |--------------------------------------------------------------------------
                | Users
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger('uploaded_by')
                    ->nullable();

                $table->unsignedBigInteger('posted_by')
                    ->nullable();


                /*
                |--------------------------------------------------------------------------
                | Dates
                |--------------------------------------------------------------------------
                */

                $table->datetime('started_at')
                    ->nullable();

                $table->datetime('completed_at')
                    ->nullable();

                $table->datetime('posted_at')
                    ->nullable();

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | SQL Server Safe FKs
                |--------------------------------------------------------------------------
                */

                $table->foreign('uploaded_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('no action')
                    ->onUpdate('no action');

                $table->foreign('posted_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('no action')
                    ->onUpdate('no action');
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Import Rows
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'contribution_receipt_import_rows',
            function (Blueprint $table): void {

                $table->id();

                $table->unsignedBigInteger(
                    'import_batch_id'
                );

                $table->unsignedInteger(
                    'row_number'
                );


                /*
                |--------------------------------------------------------------------------
                | Employer
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'employer_code',
                    100
                )->nullable();

                $table->unsignedBigInteger(
                    'matched_employer_id'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Receipt Dates
                |--------------------------------------------------------------------------
                */

                $table->date(
                    'receipt_date'
                )->nullable();

                $table->date(
                    'due_date'
                )->nullable();

                /*
                 * First day of contribution month.
                 *
                 * Example:
                 *
                 * Due Date = 2025-02-28
                 * Contribution Period = 2025-02-01
                 */

                $table->date(
                    'contribution_period'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Amount
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'currency',
                    3
                )->default('ZWG');

                $table->decimal(
                    'original_amount',
                    20,
                    2
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | USD Conversion
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'exchange_rate',
                    20,
                    8
                )->nullable();

                $table->unsignedBigInteger(
                    'exchange_rate_id'
                )->nullable();

                $table->decimal(
                    'amount_zwg',
                    20,
                    2
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Validation
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'validation_status',
                    20
                )->default('pending');

                $table->text(
                    'error_messages'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Duplicate Detection
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'receipt_fingerprint',
                    64
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Posting
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'imported_receipt_id'
                )->nullable();

                $table->datetime(
                    'imported_at'
                )->nullable();

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'import_batch_id',
                        'row_number',
                    ],
                    'receipt_import_batch_row_unique'
                );

                $table->index(
                    [
                        'import_batch_id',
                        'validation_status',
                    ],
                    'receipt_import_validation_idx'
                );

                $table->index(
                    'matched_employer_id',
                    'receipt_import_employer_idx'
                );

                $table->index(
                    'contribution_period',
                    'receipt_import_period_idx'
                );

                $table->index(
                    'receipt_fingerprint',
                    'receipt_import_fingerprint_idx'
                );


                /*
                |--------------------------------------------------------------------------
                | Foreign Keys
                |--------------------------------------------------------------------------
                */

                $table->foreign(
                    'import_batch_id'
                )
                    ->references('id')
                    ->on(
                        'contribution_receipt_import_batches'
                    )
                    ->onDelete('no action')
                    ->onUpdate('no action');


                $table->foreign(
                    'matched_employer_id'
                )
                    ->references('id')
                    ->on('employers')
                    ->onDelete('no action')
                    ->onUpdate('no action');


                $table->foreign(
                    'exchange_rate_id'
                )
                    ->references('id')
                    ->on('exchange_rates')
                    ->onDelete('no action')
                    ->onUpdate('no action');
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'contribution_receipt_import_rows'
        );

        Schema::dropIfExists(
            'contribution_receipt_import_batches'
        );
    }
};
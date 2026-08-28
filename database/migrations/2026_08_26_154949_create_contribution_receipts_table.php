<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'contribution_receipts',
            function (Blueprint $table): void {

                $table->id();


                /*
                |--------------------------------------------------------------------------
                | Employer
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'employer_id'
                );


                /*
                |--------------------------------------------------------------------------
                | Receipt Dates
                |--------------------------------------------------------------------------
                */

                $table->date(
                    'receipt_date'
                );

                $table->date(
                    'due_date'
                );

                $table->date(
                    'contribution_period'
                );


                /*
                |--------------------------------------------------------------------------
                | Cash Received
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'currency',
                    3
                );

                $table->decimal(
                    'original_amount',
                    20,
                    2
                );


                /*
                |--------------------------------------------------------------------------
                | ZWG Equivalent
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'exchange_rate',
                    20,
                    8
                );

                $table->unsignedBigInteger(
                    'exchange_rate_id'
                )->nullable();

                $table->decimal(
                    'amount_zwg',
                    20,
                    2
                );


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
                | Source Import
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'source_import_batch_id'
                );

                $table->unsignedBigInteger(
                    'source_import_row_id'
                );

                $table->unsignedBigInteger(
                    'posted_by'
                )->nullable();

                $table->datetime(
                    'posted_at'
                )->nullable();

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Prevent Same Import Row Being Posted Twice
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    'source_import_row_id',
                    'contribution_receipt_source_row_unique'
                );


                /*
                |--------------------------------------------------------------------------
                | Reporting Indexes
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'employer_id',
                        'contribution_period',
                    ],
                    'receipt_employer_period_idx'
                );

                $table->index(
                    'receipt_date',
                    'receipt_date_idx'
                );

                $table->index(
                    'receipt_fingerprint',
                    'receipt_fingerprint_idx'
                );


                /*
                |--------------------------------------------------------------------------
                | Foreign Keys
                |--------------------------------------------------------------------------
                */

                $table->foreign('employer_id')
                    ->references('id')
                    ->on('employers')
                    ->onDelete('no action')
                    ->onUpdate('no action');

                $table->foreign('exchange_rate_id')
                    ->references('id')
                    ->on('exchange_rates')
                    ->onDelete('no action')
                    ->onUpdate('no action');

                $table->foreign('source_import_batch_id')
                    ->references('id')
                    ->on(
                        'contribution_receipt_import_batches'
                    )
                    ->onDelete('no action')
                    ->onUpdate('no action');

                $table->foreign('source_import_row_id')
                    ->references('id')
                    ->on(
                        'contribution_receipt_import_rows'
                    )
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
        | Add Receipt FK Back To Staging Row
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'contribution_receipt_import_rows',
            function (Blueprint $table): void {

                $table->foreign(
                    'imported_receipt_id'
                )
                    ->references('id')
                    ->on('contribution_receipts')
                    ->onDelete('no action')
                    ->onUpdate('no action');
            }
        );
    }


    public function down(): void
    {
        Schema::table(
            'contribution_receipt_import_rows',
            function (Blueprint $table): void {

                $table->dropForeign([
                    'imported_receipt_id',
                ]);
            }
        );

        Schema::dropIfExists(
            'contribution_receipts'
        );
    }
};
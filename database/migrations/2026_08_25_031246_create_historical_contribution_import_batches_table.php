<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historical_contribution_import_batches', function (Blueprint $table): void {

            /*
            |--------------------------------------------------------------------------
            | Primary Key
            |--------------------------------------------------------------------------
            */

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Import Identification
            |--------------------------------------------------------------------------
            */

            $table->uuid('import_uuid')->unique();

            /*
            |--------------------------------------------------------------------------
            | File Information
            |--------------------------------------------------------------------------
            */

            $table->string('original_filename', 255);

            $table->string('stored_filename', 255);

            $table->string(
                'file_path',
                500
            );

            $table->string(
                'file_extension',
                20
            )->nullable();

            $table->unsignedBigInteger(
                'file_size'
            )->nullable();

            $table->string(
                'file_hash',
                64
            )->nullable()->index();

            /*
            |--------------------------------------------------------------------------
            | Source
            |--------------------------------------------------------------------------
            */

            $table->string(
                'source_system',
                50
            )->default(
                'legacy_historical_excel'
            );

            /*
            |--------------------------------------------------------------------------
            | Historical Migration Scope
            |--------------------------------------------------------------------------
            |
            | Ordinary historical contributions:
            |
            | January 2009 - October 2023
            |
            | TAKE_ON balances are separately treated as January 2009.
            |
            */

            $table->integer(
                'start_year'
            )->default(
                2009
            );

            $table->unsignedTinyInteger(
                'start_month'
            )->default(
                1
            );

            $table->integer(
                'end_year'
            )->default(
                2023
            );

            $table->unsignedTinyInteger(
                'end_month'
            )->default(
                10
            );

            /*
            |--------------------------------------------------------------------------
            | Workflow Status
            |--------------------------------------------------------------------------
            |
            | Expected values include:
            |
            | uploaded
            | queued
            | processing
            | awaiting_review
            | approved
            | posting
            | posted
            | failed
            | posting_failed
            | rejected
            | cancelled
            |
            */

            $table->string(
                'status',
                50
            )
                ->default(
                    'uploaded'
                )
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Progress
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'progress_percentage',
                8,
                2
            )->default(
                0
            );

            /*
            |--------------------------------------------------------------------------
            | Source Workbook Progress
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | 1,200 / 2,041 source member rows processed.
            |
            */

            $table->unsignedInteger(
                'total_source_rows'
            )->default(
                0
            );

            $table->unsignedInteger(
                'processed_source_rows'
            )->default(
                0
            );

            /*
            |--------------------------------------------------------------------------
            | Normalised Transaction Progress
            |--------------------------------------------------------------------------
            |
            | One source/member row may produce many monthly transactions.
            |
            */

            $table->unsignedBigInteger(
                'total_transaction_rows'
            )->default(
                0
            );

            $table->unsignedBigInteger(
                'processed_transaction_rows'
            )->default(
                0
            );

            /*
            |--------------------------------------------------------------------------
            | Validation Results
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger(
                'valid_transaction_rows'
            )->default(
                0
            );

            $table->unsignedBigInteger(
                'warning_transaction_rows'
            )->default(
                0
            );

            $table->unsignedBigInteger(
                'error_transaction_rows'
            )->default(
                0
            );

            /*
            |--------------------------------------------------------------------------
            | Duplicate Transactions
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger(
                'duplicate_transaction_rows'
            )->default(
                0
            );

            /*
            |--------------------------------------------------------------------------
            | Member Matching
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger(
                'matched_member_rows'
            )->default(
                0
            );

            $table->unsignedBigInteger(
                'new_member_rows'
            )->default(
                0
            );

            $table->unsignedBigInteger(
                'ambiguous_member_rows'
            )->default(
                0
            );

            $table->unsignedInteger(
                'new_members_detected'
            )->default(
                0
            );

            $table->unsignedInteger(
                'new_members_created'
            )->default(
                0
            );

            /*
            |--------------------------------------------------------------------------
            | Service History
            |--------------------------------------------------------------------------
            |
            | contributed
            |
            | zero_contribution:
            | Source explicitly contained 0.0000.
            |
            | break_in_service:
            | Source contribution cells were blank.
            |
            */

            $table->unsignedBigInteger(
                'contributed_periods'
            )->default(
                0
            );

            $table->unsignedBigInteger(
                'zero_contribution_periods'
            )->default(
                0
            );

            $table->unsignedBigInteger(
                'break_in_service_periods'
            )->default(
                0
            );

            /*
            |--------------------------------------------------------------------------
            | Posting Progress
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger(
                'posted_transaction_rows'
            )->default(
                0
            );

            $table->unsignedBigInteger(
                'posted_service_period_rows'
            )->default(
                0
            );

            /*
            |--------------------------------------------------------------------------
            | Failure Information
            |--------------------------------------------------------------------------
            */

            $table->text(
                'failure_reason'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Processing Dates
            |--------------------------------------------------------------------------
            */

            $table->dateTime(
                'processing_started_at'
            )->nullable();

            $table->dateTime(
                'validation_completed_at'
            )->nullable();

            $table->dateTime(
                'posting_started_at'
            )->nullable();

            $table->dateTime(
                'completed_at'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Workflow Users
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger(
                'uploaded_by'
            )->nullable();

            $table->unsignedBigInteger(
                'approved_by'
            )->nullable();

            $table->dateTime(
                'approved_at'
            )->nullable();

            $table->text(
                'approval_notes'
            )->nullable();

            $table->unsignedBigInteger(
                'posted_by'
            )->nullable();

            $table->dateTime(
                'posted_at'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | User Foreign Keys
            |--------------------------------------------------------------------------
            |
            | IMPORTANT FOR SQL SERVER:
            |
            | Do NOT use nullOnDelete() here.
            |
            | Multiple SET NULL paths pointing to users can produce:
            |
            | "may cause cycles or multiple cascade paths"
            |
            | Audit history must also remain unchanged when a user account
            | is removed.
            |
            */

            $table->foreign(
                'uploaded_by',
                'hist_contrib_batches_uploaded_by_fk'
            )
                ->references(
                    'id'
                )
                ->on(
                    'users'
                )
                ->noActionOnDelete()
                ->noActionOnUpdate();

            $table->foreign(
                'approved_by',
                'hist_contrib_batches_approved_by_fk'
            )
                ->references(
                    'id'
                )
                ->on(
                    'users'
                )
                ->noActionOnDelete()
                ->noActionOnUpdate();

            $table->foreign(
                'posted_by',
                'hist_contrib_batches_posted_by_fk'
            )
                ->references(
                    'id'
                )
                ->on(
                    'users'
                )
                ->noActionOnDelete()
                ->noActionOnUpdate();

            /*
            |--------------------------------------------------------------------------
            | Performance Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(
                [
                    'status',
                    'created_at',
                ],
                'hist_contrib_batch_status_created_idx'
            );

            $table->index(
                [
                    'uploaded_by',
                    'status',
                ],
                'hist_contrib_batch_uploader_status_idx'
            );

            $table->index(
                [
                    'start_year',
                    'start_month',
                    'end_year',
                    'end_month',
                ],
                'hist_contrib_batch_period_scope_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'historical_contribution_import_batches'
        );
    }
};
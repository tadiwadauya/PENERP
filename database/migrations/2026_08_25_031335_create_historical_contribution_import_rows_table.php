<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historical_contribution_import_rows', function (Blueprint $table): void {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Import Batch
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('import_batch_id');

            /*
            |--------------------------------------------------------------------------
            | Source Position
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('source_row_number');
            $table->string('source_column_reference', 100)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Employer Matching
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('matched_employer_id')->nullable();

            $table->string('penerp_employer_number', 100)->nullable();
            $table->string('penad_employer_number', 100)->nullable();
            $table->string('fundworx_employer_number', 100)->nullable();

            $table->string('employer_name', 255)->nullable();
            $table->string('employer_match_type', 50)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Member Matching
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('matched_member_id')->nullable();
            $table->unsignedBigInteger('created_member_id')->nullable();

            $table->string('member_match_type', 50)->nullable();

            $table->boolean('is_new_member')->default(false);

            /*
            |--------------------------------------------------------------------------
            | Member References
            |--------------------------------------------------------------------------
            */

            $table->string('penerp_member_number', 100)->nullable();
            $table->string('penad_member_number', 100)->nullable();
            $table->string('fundworx_member_number', 100)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Staff / Vote Numbers
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            |
            | Staff Number is NOT globally unique.
            |
            | Member matching must always use:
            |
            | employer_id + staff_number
            |
            */

            $table->string('staff_number', 150)->nullable();
            $table->string('vote_number', 150)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Personal Details
            |--------------------------------------------------------------------------
            */

            $table->string('title', 50)->nullable();

            $table->string('surname', 150)->nullable();
            $table->string('first_names', 200)->nullable();
            $table->string('other_names', 200)->nullable();
            $table->string('maiden_name', 150)->nullable();

            $table->string('national_id', 150)->nullable();
            $table->string('national_id_normalized', 150)->nullable();

            $table->date('date_of_birth')->nullable();

            $table->string('gender', 50)->nullable();
            $table->string('marital_status', 50)->nullable();

            $table->date('date_joined_fund')->nullable();
            $table->date('date_joined_employer')->nullable();

            $table->string('membership_status', 50)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Additional Legacy Details
            |--------------------------------------------------------------------------
            */

            $table->string('occupation', 255)->nullable();

            $table->string('email', 255)->nullable();
            $table->string('secondary_email', 255)->nullable();

            $table->string('cell_number', 100)->nullable();
            $table->string('secondary_cell_number', 100)->nullable();

            $table->string('physical_address_1', 255)->nullable();
            $table->string('physical_address_2', 255)->nullable();
            $table->string('physical_address_3', 255)->nullable();

            $table->string('physical_suburb', 150)->nullable();
            $table->string('physical_city', 150)->nullable();
            $table->string('physical_country', 150)->nullable();

            $table->string('postal_address_1', 255)->nullable();
            $table->string('postal_address_2', 255)->nullable();
            $table->string('postal_address_3', 255)->nullable();

            $table->string('postal_city', 150)->nullable();
            $table->string('postal_country', 150)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Contribution Period
            |--------------------------------------------------------------------------
            */

            $table->integer('period_year');
            $table->unsignedTinyInteger('period_month');

            $table->date('period_date');

            /*
            |--------------------------------------------------------------------------
            | Transaction Type
            |--------------------------------------------------------------------------
            |
            | expected = normal monthly historical contribution
            |
            | take_on  = opening balance treated as January 2009
            |
            | A member can therefore legitimately have:
            |
            | Jan 2009 take_on
            | Jan 2009 expected
            |
            */

            $table->string('transaction_type', 30)->default('expected');

            /*
            |--------------------------------------------------------------------------
            | Service Status
            |--------------------------------------------------------------------------
            |
            | contributed
            |
            | zero_contribution
            |     Source explicitly contained 0.0000.
            |
            | break_in_service
            |     Source contribution cells were blank.
            |
            */

            $table->string('service_status', 50)->index();

            /*
            |--------------------------------------------------------------------------
            | Currency
            |--------------------------------------------------------------------------
            */

            $table->string('currency_code', 20)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Financial Values
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            |
            | Four decimal places are retained.
            |
            | NULL   = source was blank
            | 0.0000 = source explicitly contained zero
            |
            */

            $table->decimal('basic_pay', 19, 4)->nullable();

            $table->decimal('employee_rate', 12, 6)->nullable();
            $table->decimal('employer_rate', 12, 6)->nullable();

            $table->decimal('employee_contribution', 19, 4)->nullable();
            $table->decimal('employer_contribution', 19, 4)->nullable();

            $table->decimal('employee_avc', 19, 4)->nullable();
            $table->decimal('employer_avc', 19, 4)->nullable();

            $table->decimal('employee_arrear', 19, 4)->nullable();
            $table->decimal('employer_arrear', 19, 4)->nullable();

            $table->decimal('employee_transfer_in', 19, 4)->nullable();
            $table->decimal('employer_transfer_in', 19, 4)->nullable();

            $table->decimal('employee_late_interest', 19, 4)->nullable();
            $table->decimal('employer_late_interest', 19, 4)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Original Blank Flags
            |--------------------------------------------------------------------------
            |
            | We retain these so that NULL and zero can never become confused.
            |
            */

            $table->boolean('employee_contribution_was_blank')->default(false);
            $table->boolean('employer_contribution_was_blank')->default(false);

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $table->string('validation_status', 30)->default('pending')->index();

            $table->text('error_messages')->nullable();
            $table->text('warning_messages')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Duplicate Detection
            |--------------------------------------------------------------------------
            */

            $table->string('duplicate_status', 30)->default('none')->index();

            $table->string('duplicate_key', 64)->nullable()->index();

            $table->unsignedBigInteger('duplicate_of_row_id')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Review
            |--------------------------------------------------------------------------
            */

            $table->string('review_decision', 30)->default('pending')->index();

            $table->text('review_notes')->nullable();

            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Posting
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('posted_contribution_id')->nullable();

            $table->dateTime('posted_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Source / Audit
            |--------------------------------------------------------------------------
            */

            $table->string('source_reference', 255)->nullable();
            $table->text('comments')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            |
            | SQL SERVER:
            |
            | Only staging rows cascade when their import batch is deleted.
            |
            | Employer / Member / User relationships use NO ACTION to avoid
            | multiple cascade path errors and preserve audit history.
            |
            */

            $table->foreign(
                'import_batch_id',
                'hist_contrib_rows_batch_fk'
            )
                ->references('id')
                ->on('historical_contribution_import_batches')
                ->cascadeOnDelete()
                ->noActionOnUpdate();

            $table->foreign(
                'matched_employer_id',
                'hist_contrib_rows_employer_fk'
            )
                ->references('id')
                ->on('employers')
                ->noActionOnDelete()
                ->noActionOnUpdate();

            $table->foreign(
                'matched_member_id',
                'hist_contrib_rows_matched_member_fk'
            )
                ->references('id')
                ->on('members')
                ->noActionOnDelete()
                ->noActionOnUpdate();

            $table->foreign(
                'created_member_id',
                'hist_contrib_rows_created_member_fk'
            )
                ->references('id')
                ->on('members')
                ->noActionOnDelete()
                ->noActionOnUpdate();

            $table->foreign(
                'reviewed_by',
                'hist_contrib_rows_reviewed_by_fk'
            )
                ->references('id')
                ->on('users')
                ->noActionOnDelete()
                ->noActionOnUpdate();

            /*
            |--------------------------------------------------------------------------
            | Batch / Source Lookup
            |--------------------------------------------------------------------------
            */

            $table->index(
                [
                    'import_batch_id',
                    'source_row_number',
                ],
                'hist_contrib_batch_source_row_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | Validation Lookup
            |--------------------------------------------------------------------------
            */

            $table->index(
                [
                    'import_batch_id',
                    'validation_status',
                ],
                'hist_contrib_batch_validation_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | Duplicate Lookup
            |--------------------------------------------------------------------------
            */

            $table->index(
                [
                    'import_batch_id',
                    'duplicate_status',
                ],
                'hist_contrib_batch_duplicate_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | Staff Number Matching
            |--------------------------------------------------------------------------
            |
            | Staff Number is only unique within an employer.
            |
            */

            $table->index(
                [
                    'matched_employer_id',
                    'staff_number',
                ],
                'hist_contrib_employer_staff_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | Member Period Lookup
            |--------------------------------------------------------------------------
            */

            $table->index(
                [
                    'matched_member_id',
                    'period_year',
                    'period_month',
                ],
                'hist_contrib_member_period_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | Period / Transaction Type Lookup
            |--------------------------------------------------------------------------
            */

            $table->index(
                [
                    'period_year',
                    'period_month',
                    'transaction_type',
                ],
                'hist_contrib_period_type_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | Batch / Period Lookup
            |--------------------------------------------------------------------------
            */

            $table->index(
                [
                    'import_batch_id',
                    'period_year',
                    'period_month',
                ],
                'hist_contrib_batch_period_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | New Member Lookup
            |--------------------------------------------------------------------------
            */

            $table->index(
                [
                    'import_batch_id',
                    'is_new_member',
                ],
                'hist_contrib_batch_new_member_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'historical_contribution_import_rows'
        );
    }
};
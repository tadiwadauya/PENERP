<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contribution_import_batches', function (Blueprint $table): void {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Import Identification
            |--------------------------------------------------------------------------
            */

            $table->uuid('import_uuid')->unique();

            /*
            |--------------------------------------------------------------------------
            | Period / Employer
            |--------------------------------------------------------------------------
            */

            $table->foreignId('contribution_period_id');

            $table->foreignId('employer_id');

            /*
            |--------------------------------------------------------------------------
            | Uploaded File
            |--------------------------------------------------------------------------
            */

            $table->string('original_filename');

            $table->string('stored_filename');

            $table->string('file_path');

            $table->string('file_extension', 20)->nullable();

            $table->unsignedBigInteger('file_size')->nullable();

            $table->string('file_hash', 64)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Source
            |--------------------------------------------------------------------------
            */

            $table->string('source_system', 50)
                ->default('monthly_excel');

            $table->string('scheme_code', 50)
                ->nullable();

            $table->date('due_date')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status / Progress
            |--------------------------------------------------------------------------
            */

            $table->string('status', 50)
                ->default('uploaded');

            $table->decimal(
                'progress_percentage',
                5,
                2
            )->default(0);

            /*
            |--------------------------------------------------------------------------
            | Row Statistics
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('total_rows')
                ->default(0);

            $table->unsignedInteger('processed_rows')
                ->default(0);

            $table->unsignedInteger('valid_rows')
                ->default(0);

            $table->unsignedInteger('warning_rows')
                ->default(0);

            $table->unsignedInteger('error_rows')
                ->default(0);

            $table->unsignedInteger('existing_member_rows')
                ->default(0);

            $table->unsignedInteger('new_member_rows')
                ->default(0);

            $table->unsignedInteger('nil_contributor_rows')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | USD Totals
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'usd_basic_pay_total',
                18,
                4
            )->default(0);

            $table->decimal(
                'usd_employee_contribution_total',
                18,
                4
            )->default(0);

            $table->decimal(
                'usd_employer_contribution_total',
                18,
                4
            )->default(0);

            $table->decimal(
                'usd_employee_avc_total',
                18,
                4
            )->default(0);

            $table->decimal(
                'usd_employer_avc_total',
                18,
                4
            )->default(0);

            /*
            |--------------------------------------------------------------------------
            | ZWG Totals
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'zwg_basic_pay_total',
                18,
                4
            )->default(0);

            $table->decimal(
                'zwg_employee_contribution_total',
                18,
                4
            )->default(0);

            $table->decimal(
                'zwg_employer_contribution_total',
                18,
                4
            )->default(0);

            $table->decimal(
                'zwg_employee_avc_total',
                18,
                4
            )->default(0);

            $table->decimal(
                'zwg_employer_avc_total',
                18,
                4
            )->default(0);

            /*
            |--------------------------------------------------------------------------
            | Processing
            |--------------------------------------------------------------------------
            */

            $table->text('failure_reason')
                ->nullable();

            $table->timestamp('processing_started_at')
                ->nullable();

            $table->timestamp('completed_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | User
            |--------------------------------------------------------------------------
            */

            $table->foreignId('uploaded_by')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            */

            $table->foreign('contribution_period_id')
                ->references('id')
                ->on('contribution_periods');

            $table->foreign('employer_id')
                ->references('id')
                ->on('employers');

            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users');

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('status');

            $table->index('employer_id');

            $table->index('contribution_period_id');

            $table->index('uploaded_by');

            $table->index('file_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'contribution_import_batches'
        );
    }
};
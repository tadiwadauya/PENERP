<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historical_member_service_periods', function (Blueprint $table): void {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Member / Employer
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('employer_id');

            /*
            |--------------------------------------------------------------------------
            | Period
            |--------------------------------------------------------------------------
            */

            $table->integer('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->date('period_date');

            /*
            |--------------------------------------------------------------------------
            | Service Status
            |--------------------------------------------------------------------------
            |
            | contributed
            | zero_contribution
            | break_in_service
            |
            | IMPORTANT:
            |
            | break_in_service = source contribution cells were BLANK / NULL.
            | zero_contribution = source explicitly contained 0.0000.
            |
            */

            $table->string('service_status', 50);

            /*
            |--------------------------------------------------------------------------
            | Source
            |--------------------------------------------------------------------------
            */

            $table->string('source_system', 50)->default('historical_migration');

            $table->unsignedBigInteger('historical_import_batch_id')->nullable();

            $table->unsignedInteger('source_row_number')->nullable();

            $table->text('reason')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            |
            | SQL SERVER:
            |
            | NO ACTION is deliberately used to avoid multiple cascade path
            | errors and to preserve historical audit records.
            |
            */

            $table->foreign(
                'member_id',
                'hist_service_member_fk'
            )
                ->references('id')
                ->on('members')
                ->noActionOnDelete()
                ->noActionOnUpdate();

            $table->foreign(
                'employer_id',
                'hist_service_employer_fk'
            )
                ->references('id')
                ->on('employers')
                ->noActionOnDelete()
                ->noActionOnUpdate();

            $table->foreign(
                'historical_import_batch_id',
                'hist_service_batch_fk'
            )
                ->references('id')
                ->on('historical_contribution_import_batches')
                ->noActionOnDelete()
                ->noActionOnUpdate();

            $table->foreign(
                'created_by',
                'hist_service_created_by_fk'
            )
                ->references('id')
                ->on('users')
                ->noActionOnDelete()
                ->noActionOnUpdate();

            $table->foreign(
                'updated_by',
                'hist_service_updated_by_fk'
            )
                ->references('id')
                ->on('users')
                ->noActionOnDelete()
                ->noActionOnUpdate();

            /*
            |--------------------------------------------------------------------------
            | One Service Status Per Member / Employer / Month
            |--------------------------------------------------------------------------
            |
            | Staff numbers are unique only within an employer, and service
            | history is likewise maintained against the employer relationship.
            |
            */

            $table->unique(
                [
                    'member_id',
                    'employer_id',
                    'period_year',
                    'period_month',
                ],
                'hist_member_service_period_unique'
            );

            /*
            |--------------------------------------------------------------------------
            | Reporting Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(
                [
                    'member_id',
                    'period_year',
                    'period_month',
                ],
                'hist_member_service_history_idx'
            );

            $table->index(
                [
                    'employer_id',
                    'service_status',
                ],
                'hist_service_employer_status_idx'
            );

            $table->index(
                [
                    'historical_import_batch_id',
                    'service_status',
                ],
                'hist_service_batch_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'historical_member_service_periods'
        );
    }
};
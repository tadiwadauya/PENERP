<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employer_import_rows', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('import_batch_id');
            $table->unsignedInteger('row_number');

            $table->string('import_action', 20)->default('AUTO');

            $table->text('raw_data')->nullable();
            $table->text('normalized_data')->nullable();

            $table->string('validation_status', 30)->default('pending');

            $table->text('error_messages')->nullable();
            $table->text('warning_messages')->nullable();

            $table->unsignedBigInteger('matched_employer_group_id')->nullable();
            $table->unsignedBigInteger('matched_employer_id')->nullable();

            $table->string('duplicate_status', 30)->default('none');
            $table->decimal('duplicate_score', 5, 2)->nullable();
            $table->text('duplicate_reasons')->nullable();

            $table->string('review_decision', 30)->default('pending');
            $table->text('review_notes')->nullable();

            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();

            $table->unsignedBigInteger('imported_employer_id')->nullable();
            $table->dateTime('imported_at')->nullable();

            $table->timestamps();

            $table->foreign('import_batch_id')
                ->references('id')
                ->on('employer_import_batches')
                ->cascadeOnDelete();

            $table->foreign('matched_employer_group_id')
                ->references('id')
                ->on('employer_groups');

            $table->foreign('matched_employer_id')
                ->references('id')
                ->on('employers');

            $table->foreign('imported_employer_id')
                ->references('id')
                ->on('employers');

            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users');

            $table->index([
                'import_batch_id',
                'row_number',
            ]);

            $table->index([
                'import_batch_id',
                'validation_status',
            ]);

            $table->index([
                'import_batch_id',
                'duplicate_status',
            ]);

            $table->index([
                'import_batch_id',
                'review_decision',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employer_import_rows');
    }
};
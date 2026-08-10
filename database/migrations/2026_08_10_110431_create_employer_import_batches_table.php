<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employer_import_batches', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->uuid('import_uuid')->unique();

            $table->string('original_filename', 255);
            $table->string('stored_filename', 255);
            $table->string('file_path', 500);
            $table->string('file_extension', 20)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            $table->string('import_type', 50)->default('employers');

            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('warning_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('approved_rows')->default(0);
            $table->unsignedInteger('rejected_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);

            $table->decimal('progress_percentage', 5, 2)->default(0);

            $table->string('status', 50)->default('uploaded');

            $table->text('failure_reason')->nullable();

            $table->unsignedBigInteger('uploaded_by');
            $table->unsignedBigInteger('approved_by')->nullable();

            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('approved_at')->nullable();

            $table->timestamps();

            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users');

            $table->foreign('approved_by')
                ->references('id')
                ->on('users');

            $table->index('status');
            $table->index('uploaded_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employer_import_batches');
    }
};
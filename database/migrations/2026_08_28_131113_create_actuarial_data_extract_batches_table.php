<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actuarial_data_extract_batches', function (Blueprint $table) {
            $table->id();

            $table->string('batch_number', 50)->unique();

            $table->date('date_from');
            $table->date('date_to');

            $table->unsignedBigInteger('employer_id')->nullable();

            $table->string('status', 30)->default('queued');
            $table->decimal('progress_percentage', 5, 2)->default(0);

            $table->unsignedInteger('active_members')->default(0);
            $table->unsignedInteger('nil_contributors')->default(0);
            $table->unsignedInteger('exited_members')->default(0);

            $table->string('file_path', 1000)->nullable();
            $table->string('file_name', 255)->nullable();

            $table->text('failure_reason')->nullable();

            $table->unsignedBigInteger('requested_by');
            $table->dateTime('processing_started_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            $table->timestamps();

            $table->foreign('employer_id')
                ->references('id')
                ->on('employers')
                ->nullOnDelete();

            $table->foreign('requested_by')
                ->references('id')
                ->on('users');

            $table->index(['status', 'created_at']);
            $table->index(['date_from', 'date_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actuarial_data_extract_batches');
    }
};
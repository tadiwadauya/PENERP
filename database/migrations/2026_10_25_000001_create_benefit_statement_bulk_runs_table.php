<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('benefit_statement_bulk_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('employer_id');
            $table->string('employer_name');
            $table->date('statement_date');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->string('status', 32)->default('queued');
            $table->string('requested_action', 16)->default('download');
            $table->unsignedInteger('total_members')->default(0);
            $table->unsignedInteger('processed_members')->default(0);
            $table->unsignedInteger('failed_members')->default(0);
            $table->unsignedInteger('total_batches')->default(0);
            $table->unsignedInteger('completed_batches')->default(0);
            $table->text('error_message')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
            $table->index(['requested_by', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('benefit_statement_bulk_runs'); }
};

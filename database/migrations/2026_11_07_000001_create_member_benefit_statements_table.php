<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('member_benefit_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->uuid('bulk_run_id');
            $table->date('statement_date');
            $table->string('pdf_path', 500);
            $table->timestamps();
            $table->unique(['bulk_run_id', 'member_id']);
            $table->index(['member_id', 'statement_date']);
        });
    }

    public function down(): void { Schema::dropIfExists('member_benefit_statements'); }
};

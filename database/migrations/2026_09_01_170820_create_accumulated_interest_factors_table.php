<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accumulated_interest_factors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('age_years');
            $table->string('gender', 10);
            $table->decimal('factor', 12, 6);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('source_authority', 255)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['age_years', 'gender', 'effective_from'], 'acc_interest_factor_effective_unique');
            $table->index(['gender', 'age_years']);
            $table->index(['effective_from', 'effective_to', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accumulated_interest_factors');
    }
};

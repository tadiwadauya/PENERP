<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commutation_factors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('age_years');
            $table->unsignedTinyInteger('age_months')->default(0);
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
            $table->unique(['age_years', 'age_months', 'gender', 'effective_from'], 'commutation_factor_effective_unique');
            $table->index(['gender', 'age_years', 'age_months']);
            $table->index(['effective_from', 'effective_to', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commutation_factors');
    }
};

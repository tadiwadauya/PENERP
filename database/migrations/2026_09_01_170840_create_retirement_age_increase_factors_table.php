<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retirement_age_increase_factors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('age_years');
            $table->decimal('increase_percentage', 8, 4);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('source_authority', 255)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['age_years', 'effective_from'], 'retirement_increase_effective_unique');
            $table->index(['age_years', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retirement_age_increase_factors');
    }
};

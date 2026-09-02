<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benefit_tax_tables', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 180);
            $table->string('tax_year', 20);
            $table->string('currency', 10);
            $table->string('benefit_type', 80);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('source_authority', 255)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['tax_year', 'currency', 'benefit_type', 'is_active']);
        });

        Schema::create('benefit_tax_bands', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tax_table_id');
            $table->unsignedSmallInteger('band_order');
            $table->decimal('lower_limit', 20, 4)->default(0);
            $table->decimal('upper_limit', 20, 4)->nullable();
            $table->decimal('rate_percentage', 8, 4)->default(0);
            $table->decimal('fixed_amount', 20, 4)->default(0);
            $table->timestamps();
            $table->foreign('tax_table_id')->references('id')->on('benefit_tax_tables')->cascadeOnDelete();
            $table->unique(['tax_table_id', 'band_order'], 'benefit_tax_band_order_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benefit_tax_bands');
        Schema::dropIfExists('benefit_tax_tables');
    }
};

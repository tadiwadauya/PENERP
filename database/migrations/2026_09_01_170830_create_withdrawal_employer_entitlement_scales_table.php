<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_employer_entitlement_scales', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('minimum_service_months');
            $table->unsignedInteger('maximum_service_months')->nullable();
            $table->decimal('entitlement_percentage', 8, 4);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('source_authority', 255)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['minimum_service_months', 'effective_from'], 'withdrawal_scale_effective_unique');
            $table->index(['minimum_service_months', 'maximum_service_months', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_employer_entitlement_scales');
    }
};

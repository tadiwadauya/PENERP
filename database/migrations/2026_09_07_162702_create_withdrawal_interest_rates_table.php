<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_interest_rates', function (Blueprint $table): void {
            $table->id();
            $table->string('currency',10)->default('ZWG');
            $table->date('rate_date');
            $table->decimal('rate_percentage',20,8);
            $table->string('source_authority',255)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['currency','rate_date'],'withdrawal_interest_rates_currency_date_unique');
            $table->index(['currency','rate_date','is_active'],'withdrawal_interest_rates_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_interest_rates');
    }
};
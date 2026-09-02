<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benefit_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('category', 100);
            $table->string('setting_key', 180);
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->string('value_type', 30)->default('decimal');
            $table->decimal('value_decimal', 20, 8)->nullable();
            $table->bigInteger('value_integer')->nullable();
            $table->string('value_string', 1000)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('source_authority', 255)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['setting_key', 'effective_from'], 'benefit_settings_key_effective_unique');
            $table->index(['category', 'is_active']);
            $table->index(['setting_key', 'effective_from', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benefit_settings');
    }
};

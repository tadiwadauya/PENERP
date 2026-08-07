<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisation_units', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('code', 50)->unique();
            $table->string('name', 150);

            $table->string('unit_type', 30);
            // organisation, office, department, section, branch, unit

            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('dashboard_id')->nullable();

            $table->string('email', 150)->nullable();
            $table->string('telephone', 50)->nullable();
            $table->string('physical_location', 255)->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')
                ->references('id')
                ->on('organisation_units');

            $table->index(['unit_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_units');
    }
};
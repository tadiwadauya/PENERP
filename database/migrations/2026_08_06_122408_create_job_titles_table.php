<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_titles', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('code', 30)->unique();
            $table->string('name', 150);

            $table->unsignedBigInteger('default_grade_id')->nullable();
            $table->unsignedBigInteger('default_organisation_unit_id')->nullable();

            $table->boolean('is_head_of_unit')->default(false);
            $table->boolean('is_active')->default(true);

            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('default_grade_id')
                ->references('id')
                ->on('grades');

            $table->foreign('default_organisation_unit_id')
                ->references('id')
                ->on('organisation_units');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_titles');
    }
};
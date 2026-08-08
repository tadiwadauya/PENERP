<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employer_groups', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();

            $table->boolean('vote_number_required')
                ->default(false);

            $table->boolean('is_active')
                ->default(true);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employer_groups');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_modules', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('parent_id')->nullable();

            $table->string('code', 100)->unique();
            $table->string('name', 150);
            $table->string('route_prefix', 150)->nullable();
            $table->string('icon', 100)->nullable();

            $table->text('description')->nullable();

            $table->integer('display_order')->default(0);
            $table->boolean('show_in_sidebar')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')
                ->on('system_modules');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_modules');
    }
};
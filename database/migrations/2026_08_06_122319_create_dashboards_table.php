<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboards', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('route_name', 150);
            $table->string('icon', 100)->nullable();
            $table->text('description')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('organisation_units', function (Blueprint $table) {
            $table->foreign('dashboard_id')
                ->references('id')
                ->on('dashboards');
        });
    }

    public function down(): void
    {
        Schema::table('organisation_units', function (Blueprint $table) {
            $table->dropForeign(['dashboard_id']);
        });

        Schema::dropIfExists('dashboards');
    }
};
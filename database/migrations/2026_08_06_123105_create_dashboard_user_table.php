<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_user', function (Blueprint $table) {
            $table->unsignedBigInteger('dashboard_id');
            $table->unsignedBigInteger('user_id');

            $table->boolean('is_default')->default(false);
            $table->unsignedBigInteger('assigned_by')->nullable();

            $table->timestamps();

            $table->primary(['dashboard_id', 'user_id']);

            $table->foreign('dashboard_id')
                ->references('id')
                ->on('dashboards');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->foreign('assigned_by')
                ->references('id')
                ->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_user');
    }
};
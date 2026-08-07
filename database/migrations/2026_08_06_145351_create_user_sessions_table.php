<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->uuid('session_uuid')->unique();
            $table->unsignedBigInteger('user_id');

            $table->string('laravel_session_id', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent', 1000)->nullable();
            $table->string('device_name', 255)->nullable();

            $table->timestamp('login_at')->useCurrent();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('logout_at')->nullable();

            $table->string('logout_reason', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('was_forcibly_terminated')->default(false);

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->index(['user_id', 'is_active']);
            $table->index(['last_activity_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
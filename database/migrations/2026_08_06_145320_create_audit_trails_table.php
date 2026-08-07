<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_trails', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->uuid('event_uuid')->unique();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();

            $table->string('event_type', 100);
            $table->string('module', 100)->nullable();
            $table->string('action', 100);

            $table->string('auditable_type', 255)->nullable();
            $table->string('auditable_id', 100)->nullable();

            $table->string('description', 1000)->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();

            $table->string('route_name', 255)->nullable();
            $table->text('url')->nullable();
            $table->string('http_method', 10)->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_identifier', 255)->nullable();

            $table->string('outcome', 30)->default('success');
            $table->text('failure_reason')->nullable();

            $table->timestamp('occurred_at')->useCurrent();

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->index([
                'user_id',
                'occurred_at',
            ]);

            $table->index([
                'module',
                'action',
            ]);

            $table->index([
                'auditable_type',
                'auditable_id',
            ]);

            $table->index([
                'event_type',
                'occurred_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
    }
};
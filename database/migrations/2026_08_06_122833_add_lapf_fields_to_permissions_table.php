<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('system_module_id')->nullable();
            $table->string('display_name', 150)->nullable();
            $table->string('action', 50)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->boolean('is_active')->default(true);

            $table->foreign('system_module_id')
                ->references('id')
                ->on('system_modules');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->string('display_name', 150)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_system_role')->default(false);
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign(['system_module_id']);
            $table->dropColumn([
                'system_module_id',
                'display_name',
                'action',
                'description',
                'is_sensitive',
                'is_active',
            ]);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'description',
                'is_system_role',
                'is_active',
            ]);
        });
    }
};
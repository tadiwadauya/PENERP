<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            if (!Schema::hasColumn('members', 'marital_status')) {
                $table->string('marital_status', 30)->nullable();
            }

            if (!Schema::hasColumn('members', 'cellphone_number')) {
                $table->string('cellphone_number', 50)->nullable();
            }

            if (!Schema::hasColumn('members', 'email_address')) {
                $table->string('email_address', 150)->nullable();
            }

            if (!Schema::hasColumn('members', 'home_address')) {
                $table->text('home_address')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            if (Schema::hasColumn('members', 'marital_status')) {
                $table->dropColumn('marital_status');
            }

            if (Schema::hasColumn('members', 'cellphone_number')) {
                $table->dropColumn('cellphone_number');
            }

            if (Schema::hasColumn('members', 'email_address')) {
                $table->dropColumn('email_address');
            }

            if (Schema::hasColumn('members', 'home_address')) {
                $table->dropColumn('home_address');
            }
        });
    }
};
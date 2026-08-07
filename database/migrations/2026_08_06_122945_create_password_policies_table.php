<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_policies', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name', 100);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->unsignedInteger('minimum_length')->default(10);
            $table->unsignedInteger('maximum_length')->default(128);

            $table->boolean('require_uppercase')->default(true);
            $table->boolean('require_lowercase')->default(true);
            $table->boolean('require_number')->default(true);
            $table->boolean('require_special_character')->default(true);

            $table->unsignedInteger('password_expiry_days')->default(30);
            $table->unsignedInteger('expiry_warning_days')->default(5);
            $table->unsignedInteger('password_history_count')->default(5);

            $table->boolean('allow_password_reuse')->default(false);
            $table->boolean('allow_username_in_password')->default(false);
            $table->boolean('allow_name_in_password')->default(false);

            $table->unsignedInteger('maximum_login_attempts')->default(5);
            $table->unsignedInteger('account_lock_minutes')->default(30);

            $table->unsignedInteger('temporary_password_expiry_hours')
                ->default(24);

            $table->boolean('force_first_login_change')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_policies');
    }
};
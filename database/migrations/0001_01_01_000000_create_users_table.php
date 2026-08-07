<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->string('employee_number', 50)->unique();
            $table->string('title', 20)->nullable();
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('surname', 100);

            $table->string('username', 100)->unique();
            $table->string('email', 150)->unique();
            $table->string('work_email', 150)->nullable();

            $table->string('cell_number', 30)->nullable();
            $table->string('telephone_number', 30)->nullable();
            $table->string('phone_extension', 20)->nullable();

            $table->unsignedBigInteger('organisation_unit_id');
            $table->unsignedBigInteger('job_title_id');
            $table->unsignedBigInteger('grade_id')->nullable();
            $table->unsignedBigInteger('reports_to_user_id')->nullable();

            $table->date('employment_date')->nullable();
            $table->date('termination_date')->nullable();

            // active, suspended, terminated, retired, seconded, leave
            $table->string('employment_status', 30)->default('active');

            // pending, active, locked, suspended, disabled
            $table->string('account_status', 30)->default('pending');

            $table->string('password');
            $table->boolean('must_change_password')->default(true);
            $table->boolean('temporary_password')->default(true);

            $table->timestamp('password_changed_at')->nullable();
            $table->timestamp('password_expires_at')->nullable();

            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('lock_expires_at')->nullable();

            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();

            $table->boolean('is_system_administrator')->default(false);
            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'organisation_unit_id',
                'account_status',
            ]);

            $table->index([
                'job_title_id',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
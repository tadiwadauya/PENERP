<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_employments', function (Blueprint $table) {

            $table->bigIncrements('id');

            /*
            |--------------------------------------------------------------------------
            | Relationships
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('employer_id');


            /*
            |--------------------------------------------------------------------------
            | Employer-Specific Member Numbers
            |--------------------------------------------------------------------------
            |
            | Staff numbers are only unique within an employer/local authority.
            | Vote numbers are particularly important for applicable groups.
            |
            */

            $table->string('staff_number', 100)->nullable();
            $table->string('vote_number', 100)->nullable();


            /*
            |--------------------------------------------------------------------------
            | Employment Details
            |--------------------------------------------------------------------------
            */

            $table->string('branch', 150)->nullable();
            $table->string('department', 150)->nullable();

            $table->date('date_joined_employer')->nullable();

            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();


            /*
            |--------------------------------------------------------------------------
            | Employment Status
            |--------------------------------------------------------------------------
            */

            $table->string(
                'employment_status',
                50
            )->default('active');

            $table->boolean(
                'is_current'
            )->default(true);


            /*
            |--------------------------------------------------------------------------
            | Audit References
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger(
                'created_by'
            )->nullable();

            $table->unsignedBigInteger(
                'updated_by'
            )->nullable();


            $table->timestamps();


            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            */

            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->cascadeOnDelete();

            /*
             * IMPORTANT FOR SQL SERVER:
             *
             * Do not use ->restrictOnDelete().
             *
             * SQL Server's default foreign-key behaviour already prevents
             * deletion when related records exist.
             */

            $table->foreign('employer_id')
                ->references('id')
                ->on('employers');


            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index([
                'employer_id',
                'staff_number',
            ]);

            $table->index([
                'member_id',
                'is_current',
            ]);

            $table->index('vote_number');

            $table->index(
                'employment_status'
            );

            $table->index('is_current');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'member_employments'
        );
    }
};
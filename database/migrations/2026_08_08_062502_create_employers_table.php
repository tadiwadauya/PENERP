<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employers', function (Blueprint $table) {
            $table->bigIncrements('id');

            /*
            |--------------------------------------------------------------------------
            | PENERP / Legacy Numbers
            |--------------------------------------------------------------------------
            */

            $table->string('employer_number', 50)->unique();

            $table->string(
                'penad_employer_number',
                100
            )->nullable();

            $table->string(
                'fundworx_employer_number',
                100
            )->nullable();


            /*
            |--------------------------------------------------------------------------
            | Group
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger(
                'employer_group_id'
            )->nullable();


            /*
            |--------------------------------------------------------------------------
            | Employer Details
            |--------------------------------------------------------------------------
            */

            $table->string('name', 200);

            $table->string(
                'short_name',
                100
            )->nullable();

            $table->string(
                'corporate_form',
                100
            )->nullable();

            $table->string(
                'fund_number',
                100
            )->nullable();

            $table->string(
                'scheme_code',
                100
            )->nullable();

            $table->string(
                'tpin',
                100
            )->nullable();

            $table->string(
                'business_registration_number',
                100
            )->nullable();


            /*
            |--------------------------------------------------------------------------
            | Contact
            |--------------------------------------------------------------------------
            */

            $table->string(
                'email',
                150
            )->nullable();

            $table->string(
                'telephone',
                100
            )->nullable();


            /*
            |--------------------------------------------------------------------------
            | Addresses
            |--------------------------------------------------------------------------
            */

            $table->text(
                'postal_address'
            )->nullable();

            $table->text(
                'physical_address'
            )->nullable();


            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->string(
                'status',
                30
            )->default('active');

            $table->boolean(
                'is_active'
            )->default(true);


            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger(
                'created_by'
            )->nullable();

            $table->unsignedBigInteger(
                'updated_by'
            )->nullable();

            $table->timestamps();
            $table->softDeletes();


            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(
                'penad_employer_number'
            );

            $table->index(
                'fundworx_employer_number'
            );

            $table->index(
                'employer_group_id'
            );

            $table->index(
                'name'
            );

            $table->index(
                'status'
            );


            /*
            |--------------------------------------------------------------------------
            | Foreign Key
            |--------------------------------------------------------------------------
            */

            $table->foreign(
                'employer_group_id'
            )
                ->references('id')
                ->on('employer_groups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employers');
    }
};
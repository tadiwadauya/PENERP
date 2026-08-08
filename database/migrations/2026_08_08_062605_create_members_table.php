<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->bigIncrements('id');


            /*
            |--------------------------------------------------------------------------
            | Membership Numbers
            |--------------------------------------------------------------------------
            |
            | member_number = PENERP generated number.
            |
            | Legacy numbers remain available permanently for migration,
            | searching and reconciliation.
            |
            */

            $table->string(
                'member_number',
                50
            )->unique();

            $table->string(
                'penad_member_number',
                100
            )->nullable();

            $table->string(
                'fundworx_member_number',
                100
            )->nullable();


            /*
            |--------------------------------------------------------------------------
            | Name
            |--------------------------------------------------------------------------
            */

            $table->string(
                'title',
                30
            )->nullable();

            $table->string(
                'surname',
                150
            );

            $table->string(
                'first_names',
                200
            );


            /*
            |--------------------------------------------------------------------------
            | National ID
            |--------------------------------------------------------------------------
            |
            | Keep the original exactly as supplied.
            |
            | normalized version is used for duplicate checking.
            |
            */

            $table->string(
                'national_id',
                100
            )->nullable();

            $table->string(
                'national_id_normalized',
                100
            )->nullable();


            /*
            |--------------------------------------------------------------------------
            | Personal Information
            |--------------------------------------------------------------------------
            */

            $table->date(
                'date_of_birth'
            )->nullable();

            $table->string(
                'gender',
                30
            )->nullable();

            $table->string(
                'marital_status',
                50
            )->nullable();

            $table->string(
                'occupation',
                150
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
                'secondary_email',
                150
            )->nullable();

            $table->string(
                'cell_number',
                100
            )->nullable();

            $table->string(
                'secondary_cell_number',
                100
            )->nullable();


            /*
            |--------------------------------------------------------------------------
            | Physical Address
            |--------------------------------------------------------------------------
            */

            $table->string(
                'physical_address_1',
                200
            )->nullable();

            $table->string(
                'physical_address_2',
                200
            )->nullable();

            $table->string(
                'physical_address_3',
                200
            )->nullable();

            $table->string(
                'physical_suburb',
                150
            )->nullable();

            $table->string(
                'physical_city',
                150
            )->nullable();

            $table->string(
                'physical_country',
                100
            )->nullable();


            /*
            |--------------------------------------------------------------------------
            | Postal Address
            |--------------------------------------------------------------------------
            */

            $table->string(
                'postal_address_1',
                200
            )->nullable();

            $table->string(
                'postal_address_2',
                200
            )->nullable();

            $table->string(
                'postal_address_3',
                200
            )->nullable();

            $table->string(
                'postal_city',
                150
            )->nullable();

            $table->string(
                'postal_country',
                100
            )->nullable();


            /*
            |--------------------------------------------------------------------------
            | Fund
            |--------------------------------------------------------------------------
            */

            $table->date(
                'date_joined_fund'
            )->nullable();


            /*
            |--------------------------------------------------------------------------
            | Membership Status
            |--------------------------------------------------------------------------
            */

            $table->string(
                'membership_status',
                50
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
            | Search / Duplicate Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(
                'penad_member_number'
            );

            $table->index(
                'fundworx_member_number'
            );

            $table->index(
                'national_id'
            );

            $table->index(
                'national_id_normalized'
            );

            $table->index([
                'surname',
                'first_names',
            ]);

            $table->index(
                'date_of_birth'
            );

            $table->index(
                'membership_status'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
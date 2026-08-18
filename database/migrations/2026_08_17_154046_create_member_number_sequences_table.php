<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Run Migration
    |--------------------------------------------------------------------------
    */

    public function up(): void
    {
        Schema::create(
            'member_number_sequences',
            function (Blueprint $table): void {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Sequence Identifier
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'sequence_code',
                    100
                )->unique();


                /*
                |--------------------------------------------------------------------------
                | Last Allocated Number
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'last_number'
                )->default(0);


                /*
                |--------------------------------------------------------------------------
                | Audit Timestamps
                |--------------------------------------------------------------------------
                */

                $table->timestamps();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Rollback Migration
    |--------------------------------------------------------------------------
    */

    public function down(): void
    {
        Schema::dropIfExists(
            'member_number_sequences'
        );
    }
};
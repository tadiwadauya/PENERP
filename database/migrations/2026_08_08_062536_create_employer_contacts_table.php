<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'employer_contacts',
            function (Blueprint $table) {

                $table->bigIncrements('id');

                $table->unsignedBigInteger(
                    'employer_id'
                );

                $table->string(
                    'title',
                    30
                )->nullable();

                $table->string(
                    'first_names',
                    150
                );

                $table->string(
                    'surname',
                    150
                );

                $table->string(
                    'position',
                    150
                )->nullable();

                $table->string(
                    'email',
                    150
                )->nullable();

                $table->string(
                    'telephone',
                    100
                )->nullable();

                $table->string(
                    'cell_number',
                    100
                )->nullable();

                $table->boolean(
                    'is_primary'
                )->default(false);

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->timestamps();

                $table->foreign(
                    'employer_id'
                )
                    ->references('id')
                    ->on('employers')
                    ->cascadeOnDelete();

                $table->index(
                    'employer_id'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'employer_contacts'
        );
    }
};
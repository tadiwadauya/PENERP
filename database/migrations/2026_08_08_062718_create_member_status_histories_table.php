<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'member_status_histories',
            function (Blueprint $table) {

                $table->bigIncrements('id');

                $table->unsignedBigInteger(
                    'member_id'
                );

                $table->string(
                    'old_status',
                    50
                )->nullable();

                $table->string(
                    'new_status',
                    50
                );

                $table->date(
                    'effective_date'
                );

                $table->string(
                    'movement_type',
                    50
                )->nullable();

                /*
                Examples:

                NEW_MEMBER
                REINSTATEMENT
                SUSPENSION
                DORMANT
                INACTIVE
                EXIT
                CORRECTION
                */

                $table->text(
                    'reason'
                )->nullable();

                $table->string(
                    'source',
                    50
                )->default('manual');

                /*
                manual
                excel_import
                migration
                */

                $table->unsignedBigInteger(
                    'changed_by'
                )->nullable();

                $table->timestamps();


                $table->foreign(
                    'member_id'
                )
                    ->references('id')
                    ->on('members')
                    ->cascadeOnDelete();

                $table->index([
                    'member_id',
                    'effective_date',
                ]);

                $table->index(
                    'movement_type'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'member_status_histories'
        );
    }
};
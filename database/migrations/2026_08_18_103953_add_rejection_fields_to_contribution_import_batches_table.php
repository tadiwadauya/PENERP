<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'contribution_import_batches',
            function (Blueprint $table): void {

                $table->unsignedBigInteger(
                    'rejected_by'
                )->nullable();

                $table->dateTime(
                    'rejected_at'
                )->nullable();

                $table->text(
                    'rejection_reason'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | SQL Server
                |--------------------------------------------------------------------------
                |
                | Do not use cascade delete/update here.
                |
                */

                $table->foreign(
                    'rejected_by',
                    'contribution_import_batches_rejected_by_foreign'
                )
                    ->references('id')
                    ->on('users');
            }
        );
    }


    public function down(): void
    {
        Schema::table(
            'contribution_import_batches',
            function (Blueprint $table): void {

                $table->dropForeign(
                    'contribution_import_batches_rejected_by_foreign'
                );

                $table->dropColumn([
                    'rejected_by',
                    'rejected_at',
                    'rejection_reason',
                ]);
            }
        );
    }
};
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

                /*
                |--------------------------------------------------------------------------
                | Rejection Details
                |--------------------------------------------------------------------------
                */

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
                | Foreign Key
                |--------------------------------------------------------------------------
                |
                | SQL Server:
                | No cascade delete/update is used here.
                |
                | Historical contribution batches must remain available even
                | when the related user account is later deactivated.
                |
                */

                $table->foreign(
                    'rejected_by',
                    'contribution_import_batches_rejected_by_foreign'
                )
                    ->references('id')
                    ->on('users');

                /*
                |--------------------------------------------------------------------------
                | Index
                |--------------------------------------------------------------------------
                */

                $table->index(
                    'rejected_by',
                    'contribution_import_batches_rejected_by_index'
                );

                $table->index(
                    'rejected_at',
                    'contribution_import_batches_rejected_at_index'
                );
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

                $table->dropIndex(
                    'contribution_import_batches_rejected_by_index'
                );

                $table->dropIndex(
                    'contribution_import_batches_rejected_at_index'
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
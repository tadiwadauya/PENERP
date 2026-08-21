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
                | Posting User
                |--------------------------------------------------------------------------
                |
                | Identifies the user currently performing the posting operation.
                |
                | This is different from posted_by:
                |
                | posting_user_id = user who claimed/started posting
                | posted_by       = user who successfully completed posting
                |
                */

                $table->unsignedBigInteger(
                    'posting_user_id'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Foreign Key
                |--------------------------------------------------------------------------
                |
                | SQL Server:
                | Do not cascade delete users through contribution batches.
                |
                */

                $table->foreign(
                    'posting_user_id',
                    'contribution_import_batches_posting_user_id_foreign'
                )
                    ->references('id')
                    ->on('users');

                /*
                |--------------------------------------------------------------------------
                | Index
                |--------------------------------------------------------------------------
                */

                $table->index(
                    'posting_user_id',
                    'contribution_import_batches_posting_user_id_index'
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
                    'contribution_import_batches_posting_user_id_foreign'
                );

                $table->dropIndex(
                    'contribution_import_batches_posting_user_id_index'
                );

                $table->dropColumn(
                    'posting_user_id'
                );
            }
        );
    }
};
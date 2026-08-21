<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contribution_import_batches', function (Blueprint $table): void {

            /*
            |--------------------------------------------------------------------------
            | Approval
            |--------------------------------------------------------------------------
            */

            $table->foreignId('approved_by')
                ->nullable();

            $table->timestamp('approved_at')
                ->nullable();

            $table->text('approval_notes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Posting
            |--------------------------------------------------------------------------
            */

            $table->foreignId('posted_by')
                ->nullable();

            $table->timestamp('posted_at')
                ->nullable();

            $table->unsignedInteger('posted_rows')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            */

            $table->foreign('approved_by')
                ->references('id')
                ->on('users');

            $table->foreign('posted_by')
                ->references('id')
                ->on('users');
        });
    }

    public function down(): void
    {
        Schema::table('contribution_import_batches', function (Blueprint $table): void {

            $table->dropForeign([
                'approved_by',
            ]);

            $table->dropForeign([
                'posted_by',
            ]);

            $table->dropColumn([
                'approved_by',
                'approved_at',
                'approval_notes',
                'posted_by',
                'posted_at',
                'posted_rows',
            ]);
        });
    }
};
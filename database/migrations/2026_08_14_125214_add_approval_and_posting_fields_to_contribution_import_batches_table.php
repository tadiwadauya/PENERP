<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(
            'contribution_import_batches',
            function (Blueprint $table): void {

                /*
                |--------------------------------------------------------------------------
                | Approval Information
                |--------------------------------------------------------------------------
                |
                | These fields record who approved the contribution batch
                | and when the approval was performed.
                |
                | We intentionally do not use cascading foreign keys because
                | contribution processing records are historical pension records.
                |
                */

                $table->foreignId(
                    'approved_by'
                )->nullable();

                $table->timestamp(
                    'approved_at'
                )->nullable();

                $table->text(
                    'approval_notes'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Posting Information
                |--------------------------------------------------------------------------
                |
                | A contribution batch is first uploaded and validated,
                | then reviewed and approved, before it can be posted into
                | the permanent member contribution records.
                |
                */

                $table->foreignId(
                    'posted_by'
                )->nullable();

                $table->timestamp(
                    'posted_at'
                )->nullable();

                $table->unsignedInteger(
                    'posted_rows'
                )->default(0);
            }
        );
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(
            'contribution_import_batches',
            function (Blueprint $table): void {

                $table->dropColumn([
                    'approved_by',
                    'approved_at',
                    'approval_notes',
                    'posted_by',
                    'posted_at',
                    'posted_rows',
                ]);
            }
        );
    }
};
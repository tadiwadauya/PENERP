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
                | Upload Currency
                |--------------------------------------------------------------------------
                |
                | PENERP base currency = ZWG
                |
                | Contribution schedules may currently be uploaded in:
                |
                | ZWG
                | USD
                |
                */

                $table->string(
                    'currency_code',
                    3
                )->default('ZWG');

                /*
                |--------------------------------------------------------------------------
                | Exchange Rate
                |--------------------------------------------------------------------------
                |
                | Stored for future reconciliation / reporting requirements.
                |
                | We DO NOT automatically convert expected contributions during
                | the upload.
                |
                */

                $table->decimal(
                    'exchange_rate_to_base',
                    20,
                    8
                )->nullable();
            }
        );
    }


    public function down(): void
    {
        Schema::table(
            'contribution_import_batches',
            function (Blueprint $table): void {

                $table->dropColumn([
                    'currency_code',
                    'exchange_rate_to_base',
                ]);
            }
        );
    }
};
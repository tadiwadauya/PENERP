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
                | PENERP base currency is ZWG.
                |
                | The batch currency identifies the primary currency associated
                | with the uploaded contribution schedule.
                |
                | Contribution row amounts remain stored independently in their
                | USD and ZWG columns. This field must therefore NOT be used to
                | convert or discard amounts belonging to the other currency.
                |
                */

                $table->string(
                    'currency_code',
                    3
                )->default('ZWG');

                /*
                |--------------------------------------------------------------------------
                | Exchange Rate To Base Currency
                |--------------------------------------------------------------------------
                |
                | Optional exchange rate from the batch currency to the PENERP
                | base currency (ZWG).
                |
                | This is retained for reporting/reconciliation requirements.
                | Contribution imports are not automatically converted during
                | validation or posting.
                |
                */

                $table->decimal(
                    'exchange_rate_to_base',
                    20,
                    8
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Index
                |--------------------------------------------------------------------------
                */

                $table->index(
                    'currency_code',
                    'contribution_import_batches_currency_code_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'contribution_import_batches',
            function (Blueprint $table): void {

                $table->dropIndex(
                    'contribution_import_batches_currency_code_index'
                );

                $table->dropColumn([
                    'currency_code',
                    'exchange_rate_to_base',
                ]);
            }
        );
    }
};
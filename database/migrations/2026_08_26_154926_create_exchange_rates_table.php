<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table): void {

            $table->id();

            $table->date('rate_date');

            $table->string('from_currency', 3)
                ->default('USD');

            $table->string('to_currency', 3)
                ->default('ZWG');

            $table->decimal(
                'rate',
                20,
                8
            );

            $table->string('source', 150)
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->unsignedBigInteger('created_by')
                ->nullable();

            $table->unsignedBigInteger('updated_by')
                ->nullable();

            $table->timestamps();


            $table->unique(
                [
                    'rate_date',
                    'from_currency',
                    'to_currency',
                ],
                'exchange_rates_date_currency_unique'
            );


            /*
            |--------------------------------------------------------------------------
            | SQL Server - NO ACTION
            |--------------------------------------------------------------------------
            */

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('no action')
                ->onUpdate('no action');

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('no action')
                ->onUpdate('no action');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
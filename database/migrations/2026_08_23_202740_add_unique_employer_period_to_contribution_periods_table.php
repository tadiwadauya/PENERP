<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'contribution_periods',
            function (Blueprint $table) {

                $table->unique(
                    [
                        'employer_id',
                        'period_year',
                        'period_month',
                    ],
                    'uq_contribution_period_employer_year_month'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::table(
            'contribution_periods',
            function (Blueprint $table) {

                $table->dropUnique(
                    'uq_contribution_period_employer_year_month'
                );
            }
        );
    }
};
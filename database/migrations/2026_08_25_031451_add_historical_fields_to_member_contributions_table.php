<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Currency
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasColumn('member_contributions', 'currency_code')) {
            Schema::table('member_contributions', function (Blueprint $table): void {
                $table->string('currency_code', 20)->nullable();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Basic Pay
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasColumn('member_contributions', 'basic_pay')) {
            Schema::table('member_contributions', function (Blueprint $table): void {
                $table->decimal('basic_pay', 19, 4)->nullable();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Contribution Rates
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasColumn('member_contributions', 'employee_rate')) {
            Schema::table('member_contributions', function (Blueprint $table): void {
                $table->decimal('employee_rate', 12, 6)->nullable();
            });
        }

        if (!Schema::hasColumn('member_contributions', 'employer_rate')) {
            Schema::table('member_contributions', function (Blueprint $table): void {
                $table->decimal('employer_rate', 12, 6)->nullable();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Contributions
        |--------------------------------------------------------------------------
        |
        | Four decimal places are deliberately retained.
        |
        | NULL   = source cell was blank
        | 0.0000 = source explicitly contained zero
        |
        */

        if (!Schema::hasColumn('member_contributions', 'employee_contribution')) {
            Schema::table('member_contributions', function (Blueprint $table): void {
                $table->decimal('employee_contribution', 19, 4)->nullable();
            });
        }

        if (!Schema::hasColumn('member_contributions', 'employer_contribution')) {
            Schema::table('member_contributions', function (Blueprint $table): void {
                $table->decimal('employer_contribution', 19, 4)->nullable();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | AVC
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasColumn('member_contributions', 'employee_avc')) {
            Schema::table('member_contributions', function (Blueprint $table): void {
                $table->decimal('employee_avc', 19, 4)->nullable();
            });
        }

        if (!Schema::hasColumn('member_contributions', 'employer_avc')) {
            Schema::table('member_contributions', function (Blueprint $table): void {
                $table->decimal('employer_avc', 19, 4)->nullable();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Arrears
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasColumn('member_contributions', 'employee_arrear')) {
            Schema::table('member_contributions', function (Blueprint $table): void {
                $table->decimal('employee_arrear', 19, 4)->nullable();
            });
        }

        if (!Schema::hasColumn('member_contributions', 'employer_arrear')) {
            Schema::table('member_contributions', function (Blueprint $table): void {
                $table->decimal('employer_arrear', 19, 4)->nullable();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Transfers In
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasColumn('member_contributions', 'employee_transfer_in')) {
            Schema::table('member_contributions', function (Blueprint $table): void {
                $table->decimal('employee_transfer_in', 19, 4)->nullable();
            });
        }

        if (!Schema::hasColumn('member_contributions', 'employer_transfer_in')) {
            Schema::table('member_contributions', function (Blueprint $table): void {
                $table->decimal('employer_transfer_in', 19, 4)->nullable();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Late Payment Interest
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasColumn('member_contributions', 'employee_late_interest')) {
            Schema::table('member_contributions', function (Blueprint $table): void {
                $table->decimal('employee_late_interest', 19, 4)->nullable();
            });
        }

        if (!Schema::hasColumn('member_contributions', 'employer_late_interest')) {
            Schema::table('member_contributions', function (Blueprint $table): void {
                $table->decimal('employer_late_interest', 19, 4)->nullable();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Historical Import Trace
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasColumn('member_contributions', 'historical_import_batch_id')) {
            Schema::table('member_contributions', function (Blueprint $table): void {
                $table->unsignedBigInteger('historical_import_batch_id')->nullable();
            });
        }

        if (!Schema::hasColumn('member_contributions', 'historical_import_row_id')) {
            Schema::table('member_contributions', function (Blueprint $table): void {
                $table->unsignedBigInteger('historical_import_row_id')->nullable();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Historical Transaction Type
        |--------------------------------------------------------------------------
        |
        | expected = normal monthly contribution
        | take_on  = opening balance recorded against January 2009
        |
        | If your member_contributions table already has transaction_type,
        | this section simply does nothing.
        |
        */

        if (!Schema::hasColumn('member_contributions', 'transaction_type')) {
            Schema::table('member_contributions', function (Blueprint $table): void {
                $table->string('transaction_type', 30)->default('expected');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Source System
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasColumn('member_contributions', 'source_system')) {
            Schema::table('member_contributions', function (Blueprint $table): void {
                $table->string('source_system', 50)->nullable();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Indexes
        |--------------------------------------------------------------------------
        |
        | Named explicitly so SQL Server rollback remains predictable.
        |
        */

        Schema::table('member_contributions', function (Blueprint $table): void {
            $table->index(
                'currency_code',
                'member_contrib_currency_idx'
            );

            $table->index(
                'historical_import_batch_id',
                'member_contrib_hist_batch_idx'
            );

            $table->index(
                'historical_import_row_id',
                'member_contrib_hist_row_idx'
            );

            $table->index(
                [
                    'member_id',
                    'employer_id',
                    'period_year',
                    'period_month',
                    'transaction_type',
                ],
                'member_contrib_hist_duplicate_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Drop Indexes First
        |--------------------------------------------------------------------------
        */

        Schema::table('member_contributions', function (Blueprint $table): void {
            $table->dropIndex(
                'member_contrib_currency_idx'
            );

            $table->dropIndex(
                'member_contrib_hist_batch_idx'
            );

            $table->dropIndex(
                'member_contrib_hist_row_idx'
            );

            $table->dropIndex(
                'member_contrib_hist_duplicate_lookup_idx'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Drop Historical Columns
        |--------------------------------------------------------------------------
        */

        $columns = [
            'currency_code',
            'basic_pay',
            'employee_rate',
            'employer_rate',
            'employee_contribution',
            'employer_contribution',
            'employee_avc',
            'employer_avc',
            'employee_arrear',
            'employer_arrear',
            'employee_transfer_in',
            'employer_transfer_in',
            'employee_late_interest',
            'employer_late_interest',
            'historical_import_batch_id',
            'historical_import_row_id',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('member_contributions', $column)) {
                Schema::table('member_contributions', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Do Not Automatically Remove Shared Existing Fields
        |--------------------------------------------------------------------------
        |
        | transaction_type and source_system may already be used by the monthly
        | contribution module, so they are deliberately NOT removed here.
        |
        */
    }
};
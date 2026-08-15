<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'member_contributions',
            function (Blueprint $table): void {

                $table->id();


                /*
                |--------------------------------------------------------------------------
                | Member
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'member_id'
                );

                $table->foreign(
                    'member_id'
                )
                    ->references('id')
                    ->on('members');


                /*
                |--------------------------------------------------------------------------
                | Employer
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'employer_id'
                );

                $table->foreign(
                    'employer_id'
                )
                    ->references('id')
                    ->on('employers');


                /*
                |--------------------------------------------------------------------------
                | Contribution Period
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'contribution_period_id'
                );

                $table->foreign(
                    'contribution_period_id'
                )
                    ->references('id')
                    ->on('contribution_periods');


                /*
                |--------------------------------------------------------------------------
                | Source Import Batch
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'import_batch_id'
                )->nullable();

                $table->foreign(
                    'import_batch_id'
                )
                    ->references('id')
                    ->on('contribution_import_batches');


                /*
                |--------------------------------------------------------------------------
                | Source Import Row
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'import_row_id'
                )->nullable();

                $table->foreign(
                    'import_row_id'
                )
                    ->references('id')
                    ->on('contribution_import_rows');


                /*
                |--------------------------------------------------------------------------
                | Source Information
                |--------------------------------------------------------------------------
                */

                $table->unsignedInteger(
                    'source_row_number'
                )->nullable();

                $table->string(
                    'source_system',
                    50
                )->default(
                    'PENERP'
                );


                /*
                |--------------------------------------------------------------------------
                | Member Reference Snapshot
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'penerp_member_number',
                    100
                )->nullable();

                $table->string(
                    'penad_member_number',
                    100
                )->nullable();

                $table->string(
                    'fundworx_member_number',
                    100
                )->nullable();

                $table->string(
                    'staff_number',
                    100
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Contribution Period Snapshot
                |--------------------------------------------------------------------------
                */

                $table->date(
                    'period_date'
                );

                $table->unsignedInteger(
                    'period_year'
                );

                $table->unsignedTinyInteger(
                    'period_month'
                );

                $table->date(
                    'due_date'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Scheme
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'scheme_code',
                    50
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Transaction Type
                |--------------------------------------------------------------------------
                |
                | expected
                | adjustment
                | reversal
                |
                */

                $table->string(
                    'transaction_type',
                    30
                )->default(
                    'expected'
                );


                /*
                |--------------------------------------------------------------------------
                | Payment Flag
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'payment_flag',
                    50
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | USD Basic Pay
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'usd_basic_pay',
                    20,
                    4
                )->default(0);


                /*
                |--------------------------------------------------------------------------
                | USD Rates
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'usd_employee_rate',
                    12,
                    6
                )->default(0);

                $table->decimal(
                    'usd_employer_rate',
                    12,
                    6
                )->default(0);


                /*
                |--------------------------------------------------------------------------
                | USD Normal Contributions
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'usd_employee_contribution',
                    20,
                    4
                )->default(0);

                $table->decimal(
                    'usd_employer_contribution',
                    20,
                    4
                )->default(0);


                /*
                |--------------------------------------------------------------------------
                | USD AVC
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'usd_employee_avc',
                    20,
                    4
                )->default(0);

                $table->decimal(
                    'usd_employer_avc',
                    20,
                    4
                )->default(0);


                /*
                |--------------------------------------------------------------------------
                | USD Arrears
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'usd_employee_arrear',
                    20,
                    4
                )->default(0);

                $table->decimal(
                    'usd_employer_arrear',
                    20,
                    4
                )->default(0);


                /*
                |--------------------------------------------------------------------------
                | USD Transfers
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'usd_employee_transfer_in',
                    20,
                    4
                )->default(0);

                $table->decimal(
                    'usd_employer_transfer_in',
                    20,
                    4
                )->default(0);


                /*
                |--------------------------------------------------------------------------
                | USD Late Payment Interest
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'usd_employee_late_interest',
                    20,
                    4
                )->default(0);

                $table->decimal(
                    'usd_employer_late_interest',
                    20,
                    4
                )->default(0);


                /*
                |--------------------------------------------------------------------------
                | ZWG Basic Pay
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'zwg_basic_pay',
                    20,
                    4
                )->default(0);


                /*
                |--------------------------------------------------------------------------
                | ZWG Rates
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'zwg_employee_rate',
                    12,
                    6
                )->default(0);

                $table->decimal(
                    'zwg_employer_rate',
                    12,
                    6
                )->default(0);


                /*
                |--------------------------------------------------------------------------
                | ZWG Normal Contributions
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'zwg_employee_contribution',
                    20,
                    4
                )->default(0);

                $table->decimal(
                    'zwg_employer_contribution',
                    20,
                    4
                )->default(0);


                /*
                |--------------------------------------------------------------------------
                | ZWG AVC
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'zwg_employee_avc',
                    20,
                    4
                )->default(0);

                $table->decimal(
                    'zwg_employer_avc',
                    20,
                    4
                )->default(0);


                /*
                |--------------------------------------------------------------------------
                | ZWG Arrears
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'zwg_employee_arrear',
                    20,
                    4
                )->default(0);

                $table->decimal(
                    'zwg_employer_arrear',
                    20,
                    4
                )->default(0);


                /*
                |--------------------------------------------------------------------------
                | ZWG Transfers
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'zwg_employee_transfer_in',
                    20,
                    4
                )->default(0);

                $table->decimal(
                    'zwg_employer_transfer_in',
                    20,
                    4
                )->default(0);


                /*
                |--------------------------------------------------------------------------
                | ZWG Late Payment Interest
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'zwg_employee_late_interest',
                    20,
                    4
                )->default(0);

                $table->decimal(
                    'zwg_employer_late_interest',
                    20,
                    4
                )->default(0);


                /*
                |--------------------------------------------------------------------------
                | Comments
                |--------------------------------------------------------------------------
                */

                $table->text(
                    'comments'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Posting
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'posted_by'
                )->nullable();

                $table->timestamp(
                    'posted_at'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Audit
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'created_by'
                )->nullable();

                $table->foreignId(
                    'updated_by'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Timestamps
                |--------------------------------------------------------------------------
                */

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'member_id',
                        'period_year',
                        'period_month',
                    ],
                    'member_contributions_member_period_idx'
                );

                $table->index(
                    [
                        'employer_id',
                        'period_year',
                        'period_month',
                    ],
                    'member_contributions_employer_period_idx'
                );

                $table->index(
                    [
                        'contribution_period_id',
                        'transaction_type',
                    ],
                    'member_contributions_period_type_idx'
                );

                $table->index(
                    'penad_member_number',
                    'member_contributions_penad_idx'
                );

                $table->index(
                    'penerp_member_number',
                    'member_contributions_penerp_idx'
                );


                /*
                |--------------------------------------------------------------------------
                | Prevent Duplicate Posting Of Same Import Row
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'import_batch_id',
                        'import_row_id',
                    ],
                    'member_contribution_source_unique'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'member_contributions'
        );
    }
};
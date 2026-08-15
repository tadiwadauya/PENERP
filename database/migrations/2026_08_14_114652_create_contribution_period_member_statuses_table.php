<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'contribution_period_member_statuses',
            function (Blueprint $table): void {

                $table->id();


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
                | Monthly Contribution Status
                |--------------------------------------------------------------------------
                |
                | contributed
                | nil_contributor
                | new_member
                |
                | IMPORTANT:
                |
                | nil_contributor applies to this contribution month only.
                | It does not make the member exited, inactive or suspended.
                |
                */

                $table->string(
                    'contribution_status',
                    30
                );


                /*
                |--------------------------------------------------------------------------
                | Reason
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'reason',
                    500
                )->nullable();


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
                | Timestamps
                |--------------------------------------------------------------------------
                */

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | One Monthly Status Per Member Per Contribution Period
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'contribution_period_id',
                        'member_id',
                    ],
                    'period_member_status_unique'
                );


                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'employer_id',
                        'contribution_status',
                    ],
                    'period_member_employer_status_idx'
                );

                $table->index(
                    'member_id',
                    'period_member_member_idx'
                );

                $table->index(
                    'import_batch_id',
                    'period_member_batch_idx'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'contribution_period_member_statuses'
        );
    }
};
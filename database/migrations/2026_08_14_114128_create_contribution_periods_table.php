<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'contribution_periods',
            function (Blueprint $table): void {

                $table->id();


                /*
                |--------------------------------------------------------------------------
                | Employer
                |--------------------------------------------------------------------------
                |
                | SQL Server defaults to NO ACTION on delete.
                |
                | We deliberately do not cascade deletion because contribution
                | periods are financial/history records and should prevent an
                | employer from being deleted while referenced.
                |
                */

                $table->foreignId(
                    'employer_id'
                )
                    ->constrained(
                        'employers'
                    )
                    ->cascadeOnUpdate();


                /*
                |--------------------------------------------------------------------------
                | Contribution Period
                |--------------------------------------------------------------------------
                */

                $table->date(
                    'period_date'
                );

                $table->date(
                    'due_date'
                )->nullable();

                $table->unsignedInteger(
                    'period_year'
                );

                $table->unsignedTinyInteger(
                    'period_month'
                );

                $table->string(
                    'scheme_code',
                    50
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                |
                | open
                | uploading
                | validating
                | awaiting_review
                | approved
                | posting
                | posted
                | closed
                |
                */

                $table->string(
                    'status',
                    30
                )->default(
                    'open'
                );


                /*
                |--------------------------------------------------------------------------
                | Statistics
                |--------------------------------------------------------------------------
                */

                $table->unsignedInteger(
                    'scheduled_members'
                )->default(0);

                $table->unsignedInteger(
                    'existing_members'
                )->default(0);

                $table->unsignedInteger(
                    'new_members'
                )->default(0);

                $table->unsignedInteger(
                    'nil_contributors'
                )->default(0);


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

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Employer Can Have One Contribution Period Per Month
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'employer_id',
                        'period_year',
                        'period_month',
                    ],
                    'contribution_period_employer_unique'
                );


                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index(
                    'period_date'
                );

                $table->index([
                    'period_year',
                    'period_month',
                ]);

                $table->index(
                    'status'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'contribution_periods'
        );
    }
};
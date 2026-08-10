<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'membership_import_rows',
            function (Blueprint $table) {

                $table->bigIncrements('id');


                /*
                |--------------------------------------------------------------------------
                | Import Batch
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'import_batch_id'
                );


                /*
                |--------------------------------------------------------------------------
                | Excel Row
                |--------------------------------------------------------------------------
                */

                $table->unsignedInteger(
                    'row_number'
                );


                /*
                |--------------------------------------------------------------------------
                | Import Action
                |--------------------------------------------------------------------------
                |
                | AUTO
                | CREATE
                | UPDATE
                |
                */

                $table->string(
                    'import_action',
                    20
                )->default('AUTO');


                /*
                |--------------------------------------------------------------------------
                | Raw Excel Data
                |--------------------------------------------------------------------------
                |
                | Stored as JSON text.
                |
                */

                $table->text(
                    'raw_data'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Normalised Data
                |--------------------------------------------------------------------------
                */

                $table->text(
                    'normalized_data'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Validation
                |--------------------------------------------------------------------------
                |
                | pending
                | valid
                | warning
                | error
                |
                */

                $table->string(
                    'validation_status',
                    30
                )->default('pending');

                $table->text(
                    'error_messages'
                )->nullable();

                $table->text(
                    'warning_messages'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Employer Matching
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'matched_employer_id'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Member Duplicate Detection
                |--------------------------------------------------------------------------
                |
                | none
                | exact
                | probable
                | possible
                |
                */

                $table->string(
                    'duplicate_status',
                    30
                )->default('none');

                $table->unsignedBigInteger(
                    'matched_member_id'
                )->nullable();

                $table->decimal(
                    'duplicate_score',
                    5,
                    2
                )->nullable();

                $table->text(
                    'duplicate_reasons'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Review Decision
                |--------------------------------------------------------------------------
                |
                | pending
                | create
                | update
                | use_existing
                | ignore_warning
                | reject
                |
                */

                $table->string(
                    'review_decision',
                    30
                )->default('pending');

                $table->text(
                    'review_notes'
                )->nullable();

                $table->unsignedBigInteger(
                    'reviewed_by'
                )->nullable();

                $table->dateTime(
                    'reviewed_at'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Final Import
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'imported_member_id'
                )->nullable();

                $table->dateTime(
                    'imported_at'
                )->nullable();


                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Foreign Keys
                |--------------------------------------------------------------------------
                */

                $table->foreign(
                    'import_batch_id'
                )
                    ->references('id')
                    ->on(
                        'membership_import_batches'
                    )
                    ->cascadeOnDelete();


                $table->foreign(
                    'matched_employer_id'
                )
                    ->references('id')
                    ->on('employers');


                $table->foreign(
                    'matched_member_id'
                )
                    ->references('id')
                    ->on('members');


                $table->foreign(
                    'imported_member_id'
                )
                    ->references('id')
                    ->on('members');


                $table->foreign(
                    'reviewed_by'
                )
                    ->references('id')
                    ->on('users');


                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index([
                    'import_batch_id',
                    'row_number',
                ]);


                $table->index([
                    'import_batch_id',
                    'validation_status',
                ]);


                $table->index([
                    'import_batch_id',
                    'duplicate_status',
                ]);


                $table->index([
                    'import_batch_id',
                    'review_decision',
                ]);


                $table->index(
                    'matched_member_id'
                );


                $table->index(
                    'matched_employer_id'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'membership_import_rows'
        );
    }
};
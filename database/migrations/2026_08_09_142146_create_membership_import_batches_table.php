<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'membership_import_batches',
            function (Blueprint $table) {

                $table->bigIncrements('id');

                /*
                |--------------------------------------------------------------------------
                | Public Import Identifier
                |--------------------------------------------------------------------------
                */

                $table->uuid(
                    'import_uuid'
                )->unique();


                /*
                |--------------------------------------------------------------------------
                | File Information
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'original_filename',
                    255
                );

                $table->string(
                    'stored_filename',
                    255
                );

                $table->string(
                    'file_path',
                    500
                );

                $table->string(
                    'file_extension',
                    20
                )->nullable();

                $table->unsignedBigInteger(
                    'file_size'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Import Type
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'import_type',
                    50
                )->default(
                    'static_membership'
                );


                /*
                |--------------------------------------------------------------------------
                | Optional Employer
                |--------------------------------------------------------------------------
                |
                | Null means the workbook may contain members from
                | several employers.
                |
                */

                $table->unsignedBigInteger(
                    'employer_id'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Record Counts
                |--------------------------------------------------------------------------
                */

                $table->unsignedInteger(
                    'total_rows'
                )->default(0);

                $table->unsignedInteger(
                    'processed_rows'
                )->default(0);

                $table->unsignedInteger(
                    'valid_rows'
                )->default(0);

                $table->unsignedInteger(
                    'warning_rows'
                )->default(0);

                $table->unsignedInteger(
                    'error_rows'
                )->default(0);

                $table->unsignedInteger(
                    'duplicate_rows'
                )->default(0);

                $table->unsignedInteger(
                    'approved_rows'
                )->default(0);

                $table->unsignedInteger(
                    'rejected_rows'
                )->default(0);

                $table->unsignedInteger(
                    'imported_rows'
                )->default(0);


                /*
                |--------------------------------------------------------------------------
                | Progress
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'progress_percentage',
                    5,
                    2
                )->default(0);


                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                |
                | uploaded
                | processing
                | validating
                | duplicate_checking
                | awaiting_review
                | importing
                | completed
                | failed
                | cancelled
                |
                */

                $table->string(
                    'status',
                    50
                )->default(
                    'uploaded'
                );


                /*
                |--------------------------------------------------------------------------
                | Failure
                |--------------------------------------------------------------------------
                */

                $table->text(
                    'failure_reason'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Audit Users
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'uploaded_by'
                );

                $table->unsignedBigInteger(
                    'approved_by'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Processing Dates
                |--------------------------------------------------------------------------
                */

                $table->dateTime(
                    'started_at'
                )->nullable();

                $table->dateTime(
                    'completed_at'
                )->nullable();

                $table->dateTime(
                    'approved_at'
                )->nullable();


                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Foreign Keys
                |--------------------------------------------------------------------------
                */

                $table->foreign(
                    'employer_id'
                )
                    ->references('id')
                    ->on('employers');


                $table->foreign(
                    'uploaded_by'
                )
                    ->references('id')
                    ->on('users');


                $table->foreign(
                    'approved_by'
                )
                    ->references('id')
                    ->on('users');


                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index(
                    'status'
                );

                $table->index(
                    'import_type'
                );

                $table->index(
                    'employer_id'
                );

                $table->index(
                    'uploaded_by'
                );

                $table->index(
                    'created_at'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'membership_import_batches'
        );
    }
};
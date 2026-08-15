<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'contribution_import_rows',
            function (Blueprint $table): void {

                $table->id();


                /*
                |--------------------------------------------------------------------------
                | Import Batch
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'import_batch_id'
                );

                $table->foreign(
                    'import_batch_id'
                )
                    ->references('id')
                    ->on('contribution_import_batches');


                /*
                |--------------------------------------------------------------------------
                | Excel Row Number
                |--------------------------------------------------------------------------
                */

                $table->unsignedInteger(
                    'row_number'
                );


                /*
                |--------------------------------------------------------------------------
                | Original Excel Data
                |--------------------------------------------------------------------------
                |
                | raw_data:
                | Exactly what we received from Excel.
                |
                | normalized_data:
                | Cleaned values used during matching and validation.
                |
                */

                $table->json(
                    'raw_data'
                )->nullable();

                $table->json(
                    'normalized_data'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Existing Member Match
                |--------------------------------------------------------------------------
                |
                | Nullable because the row may represent a new member.
                |
                | Do NOT use nullOnDelete() with SQL Server here.
                | The foreign key uses NO ACTION.
                |
                */

                $table->foreignId(
                    'matched_member_id'
                )->nullable();

                $table->foreign(
                    'matched_member_id'
                )
                    ->references('id')
                    ->on('members');


                /*
                |--------------------------------------------------------------------------
                | Match Type
                |--------------------------------------------------------------------------
                |
                | penad_number
                | penerp_number
                | staff_number
                | national_id
                | new_member
                | conflict
                |
                */

                $table->string(
                    'match_type',
                    50
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | New Member Identification
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'is_new_member'
                )->default(false);

                $table->boolean(
                    'member_created'
                )->default(false);


                /*
                |--------------------------------------------------------------------------
                | Newly Created Member
                |--------------------------------------------------------------------------
                |
                | When an unmatched schedule member is created in PENERP,
                | this records the generated member record.
                |
                | Again, NO ACTION is used rather than SET NULL.
                |
                */

                $table->foreignId(
                    'created_member_id'
                )->nullable();

                $table->foreign(
                    'created_member_id'
                )
                    ->references('id')
                    ->on('members');


                /*
                |--------------------------------------------------------------------------
                | Validation Status
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
                )->default(
                    'pending'
                );


                /*
                |--------------------------------------------------------------------------
                | Validation Messages
                |--------------------------------------------------------------------------
                */

                $table->json(
                    'error_messages'
                )->nullable();

                $table->json(
                    'warning_messages'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Timestamps
                |--------------------------------------------------------------------------
                */

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Prevent Duplicate Excel Row Within Same Batch
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'import_batch_id',
                        'row_number',
                    ],
                    'contribution_import_row_unique'
                );


                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'import_batch_id',
                        'validation_status',
                    ],
                    'contribution_rows_batch_status_idx'
                );

                $table->index(
                    [
                        'import_batch_id',
                        'is_new_member',
                    ],
                    'contribution_rows_batch_new_member_idx'
                );

                $table->index(
                    'matched_member_id',
                    'contribution_rows_matched_member_idx'
                );

                $table->index(
                    'created_member_id',
                    'contribution_rows_created_member_idx'
                );

                $table->index(
                    'match_type',
                    'contribution_rows_match_type_idx'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'contribution_import_rows'
        );
    }
};
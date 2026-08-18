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

                if (
                    !Schema::hasColumn(
                        'contribution_import_batches',
                        'posting_user_id'
                    )
                ) {
                    $table
                        ->unsignedBigInteger(
                            'posting_user_id'
                        )
                        ->nullable();
                }
            }
        );
    }


    public function down(): void
    {
        Schema::table(
            'contribution_import_batches',
            function (Blueprint $table): void {

                if (
                    Schema::hasColumn(
                        'contribution_import_batches',
                        'posting_user_id'
                    )
                ) {
                    $table->dropColumn(
                        'posting_user_id'
                    );
                }
            }
        );
    }
};
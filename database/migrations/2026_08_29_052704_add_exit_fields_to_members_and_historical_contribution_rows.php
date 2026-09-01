<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->date('exit_date')
                ->nullable();

            $table->string('exit_reason', 255)
                ->nullable();

            $table->index(
                [
                    'membership_status',
                    'exit_date',
                ],
                'members_status_exit_date_idx'
            );
        });

        Schema::table(
            'historical_contribution_import_rows',
            function (Blueprint $table): void {
                $table->date('exit_date')
                    ->nullable();

                $table->string('exit_reason', 255)
                    ->nullable();
            }
        );
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropIndex(
                'members_status_exit_date_idx'
            );

            $table->dropColumn([
                'exit_date',
                'exit_reason',
            ]);
        });

        Schema::table(
            'historical_contribution_import_rows',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'exit_date',
                    'exit_reason',
                ]);
            }
        );
    }
};
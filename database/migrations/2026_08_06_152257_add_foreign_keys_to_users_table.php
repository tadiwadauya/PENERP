<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreign('organisation_unit_id')
                ->references('id')
                ->on('organisation_units');

            $table->foreign('job_title_id')
                ->references('id')
                ->on('job_titles');

            $table->foreign('grade_id')
                ->references('id')
                ->on('grades');

            $table->foreign('reports_to_user_id')
                ->references('id')
                ->on('users');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign([
                'organisation_unit_id',
            ]);

            $table->dropForeign([
                'job_title_id',
            ]);

            $table->dropForeign([
                'grade_id',
            ]);

            $table->dropForeign([
                'reports_to_user_id',
            ]);
        });
    }
};
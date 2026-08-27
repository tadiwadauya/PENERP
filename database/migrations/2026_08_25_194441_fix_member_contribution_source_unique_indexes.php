<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Remove Existing Monthly Source Unique Index
        |--------------------------------------------------------------------------
        |
        | The old index considers:
        |
        | NULL, NULL
        |
        | to be a unique key combination in SQL Server.
        |
        | Historical contribution rows deliberately have:
        |
        | import_batch_id = NULL
        | import_row_id   = NULL
        |
        | therefore only one historical contribution could be inserted.
        |
        */

        DB::statement("
            IF EXISTS (
                SELECT 1
                FROM sys.indexes
                WHERE name = 'member_contribution_source_unique'
                  AND object_id = OBJECT_ID('dbo.member_contributions')
            )
            DROP INDEX member_contribution_source_unique
            ON dbo.member_contributions
        ");

        /*
        |--------------------------------------------------------------------------
        | Normal Monthly Contribution Import
        |--------------------------------------------------------------------------
        |
        | Uniqueness is enforced only when normal monthly import references
        | actually exist.
        |
        */

        DB::statement("
            CREATE UNIQUE INDEX member_contribution_source_unique
            ON dbo.member_contributions (
                import_batch_id,
                import_row_id
            )
            WHERE import_batch_id IS NOT NULL
              AND import_row_id IS NOT NULL
        ");

        /*
        |--------------------------------------------------------------------------
        | Historical Contribution Import
        |--------------------------------------------------------------------------
        |
        | Every historical staging transaction has its own historical row ID.
        |
        | This prevents:
        |
        | - posting the same historical staging row twice
        | - accidental duplicate posting after a retry
        |
        */

        DB::statement("
            IF NOT EXISTS (
                SELECT 1
                FROM sys.indexes
                WHERE name = 'member_contribution_historical_source_unique'
                  AND object_id = OBJECT_ID('dbo.member_contributions')
            )
            CREATE UNIQUE INDEX member_contribution_historical_source_unique
            ON dbo.member_contributions (
                historical_import_batch_id,
                historical_import_row_id
            )
            WHERE historical_import_batch_id IS NOT NULL
              AND historical_import_row_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Remove Historical Index
        |--------------------------------------------------------------------------
        */

        DB::statement("
            IF EXISTS (
                SELECT 1
                FROM sys.indexes
                WHERE name = 'member_contribution_historical_source_unique'
                  AND object_id = OBJECT_ID('dbo.member_contributions')
            )
            DROP INDEX member_contribution_historical_source_unique
            ON dbo.member_contributions
        ");

        /*
        |--------------------------------------------------------------------------
        | Remove Filtered Monthly Index
        |--------------------------------------------------------------------------
        */

        DB::statement("
            IF EXISTS (
                SELECT 1
                FROM sys.indexes
                WHERE name = 'member_contribution_source_unique'
                  AND object_id = OBJECT_ID('dbo.member_contributions')
            )
            DROP INDEX member_contribution_source_unique
            ON dbo.member_contributions
        ");

        /*
        |--------------------------------------------------------------------------
        | Restore Previous Index
        |--------------------------------------------------------------------------
        */

        DB::statement("
            CREATE UNIQUE INDEX member_contribution_source_unique
            ON dbo.member_contributions (
                import_batch_id,
                import_row_id
            )
        ");
    }
};
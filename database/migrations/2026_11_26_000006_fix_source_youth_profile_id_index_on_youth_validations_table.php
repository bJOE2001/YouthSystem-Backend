<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace standard unique index with a SQL Server filtered unique index
     * so multiple new registrations with source_youth_profile_id = NULL are allowed.
     */
    public function up(): void
    {
        if (! Schema::hasTable('youth_validations') || ! Schema::hasColumn('youth_validations', 'source_youth_profile_id')) {
            return;
        }

        if (DB::getDriverName() === 'sqlsrv') {
            DB::statement('DROP INDEX IF EXISTS youth_validations_source_youth_profile_id_unique ON youth_validations');
            DB::statement('CREATE UNIQUE INDEX youth_validations_source_youth_profile_id_unique ON youth_validations (source_youth_profile_id) WHERE source_youth_profile_id IS NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('youth_validations')) {
            return;
        }

        if (DB::getDriverName() === 'sqlsrv') {
            DB::statement('DROP INDEX IF EXISTS youth_validations_source_youth_profile_id_unique ON youth_validations');
            Schema::table('youth_validations', function (Blueprint $table) {
                $table->index('source_youth_profile_id', 'youth_validations_source_youth_profile_id_index');
            });
        }
    }
};

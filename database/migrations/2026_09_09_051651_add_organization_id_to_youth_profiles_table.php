<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('youth_profiles', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('status')
                ->constrained('organizations')
                ->nullOnDelete();
        });

        $sinagOrgId = DB::table('organizations')
            ->where('name', 'SINAG')
            ->value('id');

        if ($sinagOrgId) {
            DB::table('youth_profiles')
                ->where('sinag_member', true)
                ->update(['organization_id' => $sinagOrgId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('youth_profiles', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });
    }
};

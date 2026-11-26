<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('youth_profiles') && Schema::hasColumn('youth_profiles', 'rejection_reason')) {
            Schema::table('youth_profiles', function (Blueprint $table) {
                $table->dropColumn('rejection_reason');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('youth_profiles') && ! Schema::hasColumn('youth_profiles', 'rejection_reason')) {
            Schema::table('youth_profiles', function (Blueprint $table) {
                $table->text('rejection_reason')->nullable()->after('reviewed_at');
            });
        }
    }
};

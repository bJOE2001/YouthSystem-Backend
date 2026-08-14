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
        if (Schema::hasTable('events') && ! Schema::hasColumn('events', 'certificate_settings')) {
            Schema::table('events', function (Blueprint $table) {
                $table->json('certificate_settings')->nullable()->after('certificate_template_path');
            });
        }

        if (Schema::hasTable('sports_programs') && ! Schema::hasColumn('sports_programs', 'certificate_settings')) {
            Schema::table('sports_programs', function (Blueprint $table) {
                $table->json('certificate_settings')->nullable()->after('certificate_template_path');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('events') && Schema::hasColumn('events', 'certificate_settings')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('certificate_settings');
            });
        }

        if (Schema::hasTable('sports_programs') && Schema::hasColumn('sports_programs', 'certificate_settings')) {
            Schema::table('sports_programs', function (Blueprint $table) {
                $table->dropColumn('certificate_settings');
            });
        }
    }
};

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
        if (Schema::hasTable('events') && ! Schema::hasColumn('events', 'certificate_template_path')) {
            Schema::table('events', function (Blueprint $table) {
                $table->string('certificate_template_path')->nullable()->after('status');
            });
        }

        if (Schema::hasTable('sports_programs') && ! Schema::hasColumn('sports_programs', 'certificate_template_path')) {
            Schema::table('sports_programs', function (Blueprint $table) {
                $table->string('certificate_template_path')->nullable()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('events') && Schema::hasColumn('events', 'certificate_template_path')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('certificate_template_path');
            });
        }

        if (Schema::hasTable('sports_programs') && Schema::hasColumn('sports_programs', 'certificate_template_path')) {
            Schema::table('sports_programs', function (Blueprint $table) {
                $table->dropColumn('certificate_template_path');
            });
        }
    }
};

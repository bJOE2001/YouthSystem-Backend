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
        Schema::table('sports_programs', function (Blueprint $table) {
            $table->boolean('open_to_all_barangays')->default(true)->after('status');
            $table->string('barangay')->nullable()->after('open_to_all_barangays');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sports_programs', function (Blueprint $table) {
            $table->dropColumn(['open_to_all_barangays', 'barangay']);
        });
    }
};

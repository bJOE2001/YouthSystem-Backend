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
        Schema::table('ecespro_grant_release_batches', function (Blueprint $table) {
            $table->string('school_year')->nullable()->after('venue');
            $table->string('semester')->nullable()->after('school_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecespro_grant_release_batches', function (Blueprint $table) {
            $table->dropColumn(['school_year', 'semester']);
        });
    }
};

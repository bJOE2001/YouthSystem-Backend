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
        Schema::table('youth_profiles', function (Blueprint $table) {
            $table->string('facebook_account')->nullable()->after('mobile_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('youth_profiles', function (Blueprint $table) {
            $table->dropColumn('facebook_account');
        });
    }
};

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
        Schema::table('sports_program_user', function (Blueprint $table) {
            $table->string('team_name')->nullable();
            $table->json('teammates')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sports_program_user', function (Blueprint $table) {
            $table->dropColumn('team_name');
            $table->dropColumn('teammates');
        });
    }
};

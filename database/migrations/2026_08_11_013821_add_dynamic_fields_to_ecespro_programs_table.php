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
        Schema::table('ecespro_programs', function (Blueprint $table) {
            $table->json('scholarship_benefits')->nullable();
            $table->json('program_eligibility')->nullable();
            $table->json('application_requirements')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecespro_programs', function (Blueprint $table) {
            $table->dropColumn(['scholarship_benefits', 'program_eligibility', 'application_requirements']);
        });
    }
};

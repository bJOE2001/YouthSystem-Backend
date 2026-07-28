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
        Schema::table('ecespro_applications', function (Blueprint $table) {
            $table->string('father_middle_name')->nullable()->after('father_first_name');
            $table->string('mother_first_name')->nullable()->after('father_educational_attainment');
            $table->string('guardian_first_name')->nullable()->after('mother_educational_attainment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecespro_applications', function (Blueprint $table) {
            $table->dropColumn(['father_middle_name', 'mother_first_name', 'guardian_first_name']);
        });
    }
};

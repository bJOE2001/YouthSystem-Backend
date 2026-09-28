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
            // Drop unused or duplicated columns
            $table->dropColumn([
                'previous_grade_college_year_level',
                'school',
                'gender',
                'school_citizenship',
                'school_zip_code'
            ]);
            
            // Rename school_attended_to_enroll to school_intended_to_enroll
            $table->renameColumn('school_attended_to_enroll', 'school_intended_to_enroll');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecespro_applications', function (Blueprint $table) {
            // Restore dropped columns
            $table->string('previous_grade_college_year_level')->nullable();
            $table->string('school')->nullable();
            $table->string('gender')->nullable();
            $table->string('school_citizenship')->nullable();
            $table->string('school_zip_code')->nullable();

            // Revert rename
            $table->renameColumn('school_intended_to_enroll', 'school_attended_to_enroll');
        });
    }
};

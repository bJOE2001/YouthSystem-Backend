<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecespro_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ecespro_program_id')->constrained()->cascadeOnDelete();

            // Personal Info
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('suffix')->nullable();
            $table->string('gender')->nullable();
            $table->string('birthdate')->nullable();
            $table->string('age')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('sex')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('citizenship')->nullable();
            $table->string('personal_zip_code')->nullable();
            $table->string('ip_or_muslim')->nullable();
            $table->string('type_of_disability')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('email_address')->nullable();
            $table->string('permanent_mailing_address')->nullable();

            // Educational Info
            $table->string('previous_grade_college_year_level')->nullable();
            $table->string('general_average')->nullable();
            $table->string('school_attended_to_enroll')->nullable();
            $table->string('school_address')->nullable();
            $table->string('course_intended_to_enroll')->nullable();
            $table->string('type_of_school')->nullable();
            $table->string('school_year')->nullable();
            $table->string('school_citizenship')->nullable();
            $table->string('school')->nullable();
            $table->string('year_level')->nullable();
            $table->string('course')->nullable();
            $table->string('school_zip_code')->nullable();

            // Father's Info
            $table->string('father_last_name')->nullable();
            $table->string('father_first_name')->nullable();
            $table->string('father_address')->nullable();
            $table->string('father_occupation')->nullable();
            $table->string('father_educational_attainment')->nullable();

            // Mother's Info
            $table->string('mother_maiden_middle_name')->nullable();
            $table->string('mother_maiden_last_name')->nullable();
            $table->string('mother_occupation')->nullable();
            $table->string('mother_educational_attainment')->nullable();

            // Guardian's Info
            $table->string('guardian_maiden_middle_name')->nullable();
            $table->string('guardian_maiden_last_name')->nullable();
            $table->string('guardian_occupation')->nullable();
            $table->string('guardian_educational_attainment')->nullable();

            // Other Family Info
            $table->string('parents_guardian_total_income')->nullable();
            $table->string('number_of_siblings_in_family')->nullable();
            $table->string('parents_marital_status')->nullable();

            // Requirements (File Paths)
            $table->string('certificate_of_indigency')->nullable();
            $table->string('report_card_grades')->nullable();
            $table->string('certificate_of_enrollment')->nullable();
            $table->string('certificate_of_registration')->nullable();
            $table->string('good_moral_certificate')->nullable();
            $table->string('barangay_clearance')->nullable();
            $table->string('other_supporting_documents')->nullable();

            $table->string('application_status')->default('Pending'); // Pending, Under Review, Exam Scheduled, Interview Scheduled, Contract Scheduled, Approved, Rejected
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecespro_applications');
    }
};

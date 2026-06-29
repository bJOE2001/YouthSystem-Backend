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
        Schema::create('youth_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('suffix', 20)->nullable();
            $table->string('gender', 30);
            $table->date('birth_date');
            $table->string('place_of_birth');
            $table->string('mobile_number', 30);
            $table->string('father_first_name', 100)->nullable();
            $table->string('father_middle_name', 100)->nullable();
            $table->string('father_last_name', 100)->nullable();
            $table->string('mother_first_name', 100)->nullable();
            $table->string('mother_middle_name', 100)->nullable();
            $table->string('mother_last_name', 100)->nullable();
            $table->string('parents_contact_number', 30)->nullable();
            $table->string('guardian_first_name', 100)->nullable();
            $table->string('guardian_last_name', 100)->nullable();
            $table->string('guardian_contact_number', 30)->nullable();
            $table->boolean('currently_attending_school');
            $table->boolean('senior_high_graduate');
            $table->string('educational_attainment', 100);
            $table->string('course_strand', 150)->nullable();
            $table->string('ethnicity', 100)->nullable();
            $table->string('religious_affiliation', 100)->nullable();
            $table->boolean('has_disability');
            $table->boolean('overseas_worker');
            $table->boolean('lgbtq_member');
            $table->string('special_youth_sector', 100)->nullable();
            $table->string('attached_id_path')->nullable();
            $table->boolean('birth_registered');
            $table->string('civil_status', 30);
            $table->boolean('solo_parent');
            $table->string('barangay', 100);
            $table->string('purok_sitio', 100);
            $table->string('city', 100);
            $table->string('province', 100);
            $table->string('postal_code', 10);
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['barangay', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youth_profiles');
    }
};

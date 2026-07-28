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
            $table->dropColumn([
                'certificate_of_indigency',
                'report_card_grades',
                'certificate_of_enrollment',
                'certificate_of_registration',
                'good_moral_certificate',
                'barangay_clearance',
                'other_supporting_documents',
            ]);

            $table->json('submitted_requirements')->nullable()->after('parents_marital_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecespro_applications', function (Blueprint $table) {
            $table->dropColumn('submitted_requirements');

            $table->string('certificate_of_indigency')->nullable();
            $table->string('report_card_grades')->nullable();
            $table->string('certificate_of_enrollment')->nullable();
            $table->string('certificate_of_registration')->nullable();
            $table->string('good_moral_certificate')->nullable();
            $table->string('barangay_clearance')->nullable();
            $table->string('other_supporting_documents')->nullable();
        });
    }
};

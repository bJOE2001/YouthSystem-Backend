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
        Schema::create('feedback_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained('feedback_questionnaires')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->noActionOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->noActionOnDelete();
            $table->foreignId('sports_program_id')->nullable()->constrained('sports_programs')->noActionOnDelete();
            $table->string('activity_type')->nullable(); // 'event', 'sport', 'platform'
            $table->string('activity_name')->nullable();
            $table->string('target')->default('admin'); // 'admin', 'sk'
            $table->string('barangay')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('submitted_by_name')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->text('general_remarks')->nullable();
            $table->json('answers');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_responses');
    }
};

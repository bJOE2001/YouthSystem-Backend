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
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('feedback_questionnaire_id')->nullable()->constrained('feedback_questionnaires')->noActionOnDelete();
            $table->timestamp('feedback_questionnaire_assigned_at')->nullable();
        });

        Schema::table('sports_programs', function (Blueprint $table) {
            $table->foreignId('feedback_questionnaire_id')->nullable()->constrained('feedback_questionnaires')->noActionOnDelete();
            $table->timestamp('feedback_questionnaire_assigned_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['feedback_questionnaire_id']);
            $table->dropColumn(['feedback_questionnaire_id', 'feedback_questionnaire_assigned_at']);
        });

        Schema::table('sports_programs', function (Blueprint $table) {
            $table->dropForeign(['feedback_questionnaire_id']);
            $table->dropColumn(['feedback_questionnaire_id', 'feedback_questionnaire_assigned_at']);
        });
    }
};

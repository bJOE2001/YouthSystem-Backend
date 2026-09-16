<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecespro_exam_questionnaires', function (Blueprint $table) {
            $table->string('title')->nullable();
            $table->text('description')->nullable();
        });

        Schema::table('ecespro_exam_questions', function (Blueprint $table) {
            $table->foreignId('questionnaire_id')->nullable()->constrained('ecespro_exam_questionnaires')->onDelete('cascade');
            $table->string('type')->nullable(); // multiple_choice, fill_in_blank
            $table->text('question_text')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('allow_multiple_answers')->default(false);
        });

        Schema::table('ecespro_exam_answer_choices', function (Blueprint $table) {
            $table->foreignId('question_id')->nullable()->constrained('ecespro_exam_questions')->onDelete('cascade');
            $table->text('choice_text')->nullable();
            $table->boolean('is_correct')->default(false);
        });

        Schema::table('ecespro_examination_setups', function (Blueprint $table) {
            $table->foreignId('ecespro_program_id')->nullable()->constrained('ecespro_programs')->onDelete('cascade');
            $table->foreignId('questionnaire_id')->nullable()->constrained('ecespro_exam_questionnaires');
            $table->decimal('passing_percentage', 5, 2)->nullable();
            $table->integer('time_limit_minutes')->nullable();
            $table->boolean('shuffle_questions')->default(false);
        });

        Schema::table('ecespro_applicant_exam_answers', function (Blueprint $table) {
            $table->foreignId('ecespro_examination_id')->nullable()->constrained('ecespro_examinations')->onDelete('cascade');
            $table->foreignId('question_id')->nullable()->constrained('ecespro_exam_questions');
            $table->foreignId('answer_choice_id')->nullable()->constrained('ecespro_exam_answer_choices');
            $table->text('answer_text')->nullable();
            $table->boolean('is_correct')->default(false);
        });
    }

    public function down(): void
    {
        // For development, dropping these columns if rolled back
        Schema::table('ecespro_exam_questionnaires', function (Blueprint $table) {
            $table->dropColumn(['title', 'description']);
        });

        Schema::table('ecespro_exam_questions', function (Blueprint $table) {
            $table->dropForeign(['questionnaire_id']);
            $table->dropColumn(['questionnaire_id', 'type', 'question_text', 'image_path', 'allow_multiple_answers']);
        });

        Schema::table('ecespro_exam_answer_choices', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
            $table->dropColumn(['question_id', 'choice_text', 'is_correct']);
        });

        Schema::table('ecespro_examination_setups', function (Blueprint $table) {
            $table->dropForeign(['ecespro_program_id']);
            $table->dropForeign(['questionnaire_id']);
            $table->dropColumn(['ecespro_program_id', 'questionnaire_id', 'passing_percentage', 'time_limit_minutes', 'shuffle_questions']);
        });

        Schema::table('ecespro_applicant_exam_answers', function (Blueprint $table) {
            $table->dropForeign(['ecespro_examination_id']);
            $table->dropForeign(['question_id']);
            $table->dropForeign(['answer_choice_id']);
            $table->dropColumn(['ecespro_examination_id', 'question_id', 'answer_choice_id', 'answer_text', 'is_correct']);
        });
    }
};

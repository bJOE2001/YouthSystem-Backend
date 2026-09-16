<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecespro_applicant_exam_answers', function (Blueprint $table) {
            $table->integer('awarded_points')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ecespro_applicant_exam_answers', function (Blueprint $table) {
            $table->dropColumn('awarded_points');
        });
    }
};

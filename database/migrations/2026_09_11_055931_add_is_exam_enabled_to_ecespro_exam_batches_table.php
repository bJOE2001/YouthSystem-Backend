<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecespro_exam_batches', function (Blueprint $table) {
            $table->boolean('is_exam_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('ecespro_exam_batches', function (Blueprint $table) {
            $table->dropColumn('is_exam_enabled');
        });
    }
};

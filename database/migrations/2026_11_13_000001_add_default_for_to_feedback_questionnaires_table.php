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
        Schema::table('feedback_questionnaires', function (Blueprint $table) {
            $table->string('default_for', 20)->nullable()->after('is_assigned_general')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback_questionnaires', function (Blueprint $table) {
            $table->dropColumn('default_for');
        });
    }
};


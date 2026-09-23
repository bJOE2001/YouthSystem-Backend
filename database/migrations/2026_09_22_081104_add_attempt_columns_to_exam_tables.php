<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecespro_examination_setups', function (Blueprint $table) {
            $table->integer('attempts_allowed')->default(1)->after('time_limit_minutes');
        });

        Schema::table('ecespro_examinations', function (Blueprint $table) {
            $table->integer('attempts_used')->default(1)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('ecespro_examination_setups', function (Blueprint $table) {
            $table->dropColumn('attempts_allowed');
        });

        Schema::table('ecespro_examinations', function (Blueprint $table) {
            $table->dropColumn('attempts_used');
        });
    }
};

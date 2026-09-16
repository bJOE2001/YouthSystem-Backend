<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecespro_examinations', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('time_extension_minutes')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('ecespro_examinations', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'completed_at', 'time_extension_minutes']);
        });
    }
};

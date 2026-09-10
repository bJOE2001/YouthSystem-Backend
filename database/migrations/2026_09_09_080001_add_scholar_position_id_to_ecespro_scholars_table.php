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
        Schema::table('ecespro_scholars', function (Blueprint $table) {
            $table->foreignId('scholar_position_id')
                ->nullable()
                ->constrained('scholar_positions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecespro_scholars', function (Blueprint $table) {
            $table->dropForeign(['scholar_position_id']);
            $table->dropColumn('scholar_position_id');
        });
    }
};

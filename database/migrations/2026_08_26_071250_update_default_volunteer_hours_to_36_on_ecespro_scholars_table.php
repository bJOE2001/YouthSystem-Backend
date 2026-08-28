<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ecespro_scholars', function (Blueprint $table) {
            $table->decimal('required_volunteer_hours', 6, 2)->nullable()->default(36.00)->change();
        });

        DB::table('ecespro_scholars')
            ->where('required_volunteer_hours', 30.00)
            ->update(['required_volunteer_hours' => 36.00]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecespro_scholars', function (Blueprint $table) {
            $table->decimal('required_volunteer_hours', 6, 2)->default(30.00)->change();
        });
    }
};

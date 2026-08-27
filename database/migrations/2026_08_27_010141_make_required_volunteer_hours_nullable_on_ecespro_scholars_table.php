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
            $table->decimal('required_volunteer_hours', 6, 2)->nullable()->default(null)->change();
        });

        // Ensure default global setting exists in ecespro_settings
        if (! DB::table('ecespro_settings')->where('key', 'required_volunteer_hours')->exists()) {
            DB::table('ecespro_settings')->insert([
                'key' => 'required_volunteer_hours',
                'value' => json_encode(36.00),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecespro_scholars', function (Blueprint $table) {
            $table->decimal('required_volunteer_hours', 6, 2)->nullable()->default(36.00)->change();
        });
    }
};

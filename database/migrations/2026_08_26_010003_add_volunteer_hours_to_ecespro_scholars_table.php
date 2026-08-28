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
            $table->decimal('required_volunteer_hours', 6, 2)->default(30.00)->after('status');
            $table->decimal('total_rendered_hours', 6, 2)->default(0.00)->after('required_volunteer_hours');
            $table->boolean('is_volunteer_completed')->default(false)->after('total_rendered_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecespro_scholars', function (Blueprint $table) {
            $table->dropColumn(['required_volunteer_hours', 'total_rendered_hours', 'is_volunteer_completed']);
        });
    }
};

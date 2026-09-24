<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecespro_applications', function (Blueprint $table) {
            $table->string('father_status')->nullable()->default('Living')->after('father_first_name');
            $table->string('mother_status')->nullable()->default('Living')->after('mother_maiden_last_name');
            $table->string('mother_address')->nullable()->after('mother_status');
            $table->string('guardian_status')->nullable()->default('Living')->after('guardian_maiden_last_name');
            $table->string('guardian_address')->nullable()->after('guardian_status');
        });
    }

    public function down(): void
    {
        Schema::table('ecespro_applications', function (Blueprint $table) {
            $table->dropColumn([
                'father_status',
                'mother_status',
                'mother_address',
                'guardian_status',
                'guardian_address'
            ]);
        });
    }
};

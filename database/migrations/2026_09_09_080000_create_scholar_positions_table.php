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
        Schema::create('scholar_positions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('can_scan')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        if (! DB::table('scholar_positions')->where('name', 'Cluster President')->exists()) {
            DB::table('scholar_positions')->insert([
                'name' => 'Cluster President',
                'can_scan' => true,
                'status' => 'active',
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
        Schema::dropIfExists('scholar_positions');
    }
};

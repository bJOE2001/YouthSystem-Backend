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
        Schema::dropIfExists('ecespro_requirements');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('ecespro_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('Active');
            $table->string('required_status')->default('Required');
            $table->timestamps();
        });
    }
};

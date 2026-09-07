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
        Schema::create('ecespro_grant_releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('ecespro_grant_release_batches')->cascadeOnDelete();
            $table->foreignId('scholar_id')->constrained('ecespro_scholars')->cascadeOnDelete();
            $table->string('status')->default('Pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecespro_grant_releases');
    }
};

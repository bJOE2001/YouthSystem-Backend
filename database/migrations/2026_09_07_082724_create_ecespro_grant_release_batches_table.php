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
        Schema::create('ecespro_grant_release_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_name');
            $table->date('release_date');
            $table->string('time');
            $table->string('venue');
            $table->string('status')->default('Scheduled');
            $table->boolean('notify_scholars')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecespro_grant_release_batches');
    }
};

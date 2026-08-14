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
        Schema::create('ecespro_contract_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_name');
            $table->date('signing_date');
            $table->string('time')->nullable();
            $table->string('venue')->nullable();
            $table->string('status')->default('Upcoming'); // Upcoming, Ongoing, Completed, Cancelled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecespro_contract_batches');
    }
};

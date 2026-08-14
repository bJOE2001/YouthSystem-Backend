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
        Schema::dropIfExists('ecespro_contract_signings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('ecespro_contract_signings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecespro_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ecespro_contract_batch_id')->nullable()->constrained('ecespro_contract_batches')->nullOnDelete();
            $table->string('status')->default('Pending'); // Pending, For Signing, Signed
            $table->timestamps();
        });
    }
};

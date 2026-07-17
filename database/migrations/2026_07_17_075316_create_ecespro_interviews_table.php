<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecespro_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecespro_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ecespro_interview_batch_id')->nullable()->constrained('ecespro_interview_batches')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->string('status')->default('Pending'); // Pending, Passed, Failed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecespro_interviews');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecespro_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecespro_application_id')->constrained()->cascadeOnDelete();
            $table->string('schedule')->nullable();
            $table->string('guardian')->nullable();
            $table->string('documents_status')->nullable();
            $table->string('status')->default('Pending'); // Pending, For Signing, Signed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecespro_contracts');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecespro_scholars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); // Removed cascadeOnDelete to fix SQL Server cycle
            $table->foreignId('ecespro_application_id')->constrained()->cascadeOnDelete();
            $table->string('scholar_no')->nullable();
            $table->string('school')->nullable();
            $table->string('course')->nullable();
            $table->string('compliance_status')->nullable();
            $table->json('requirements_history')->nullable(); // Store semester requirement submissions
            $table->string('status')->default('Active'); // Active, Graduated, Revoked, Inactive
            $table->decimal('allowance_received_amount', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecespro_scholars');
    }
};

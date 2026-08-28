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
        Schema::create('ecespro_volunteer_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholar_id')->constrained('ecespro_scholars')->noActionOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->noActionOnDelete();
            $table->string('activity_type', 50)->default('event_attendance');
            $table->string('duty_title')->nullable();
            $table->timestamp('time_in')->useCurrent();
            $table->timestamp('time_out')->nullable();
            $table->decimal('hours_rendered', 5, 2)->default(0.00);
            $table->string('semester_period', 32)->nullable();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->noActionOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['scholar_id', 'semester_period']);
            $table->index(['scholar_id', 'time_out']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecespro_volunteer_logs');
    }
};

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
        Schema::dropIfExists('feedbacks');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('feedbacks')) {
            Schema::create('feedbacks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->noActionOnDelete();
                $table->foreignId('event_id')->nullable()->constrained('events')->noActionOnDelete();
                $table->foreignId('sports_program_id')->nullable()->constrained('sports_programs')->noActionOnDelete();
                $table->string('type')->default('general');
                $table->string('target')->default('admin');
                $table->string('barangay')->nullable();
                $table->string('category')->nullable();
                $table->string('subject')->nullable();
                $table->text('message')->nullable();
                $table->boolean('is_anonymous')->default(false);
                $table->timestamps();
            });
        }
    }
};


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
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->noActionOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->noActionOnDelete();
            $table->string('type')->default('general'); // 'general' or 'event'
            $table->string('category');
            $table->string('subject');
            $table->text('message');
            $table->boolean('is_anonymous')->default(false);
            $table->timestamps();
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};

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
        Schema::create('sports_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // Program, Project, Activity
            $table->string('strategic_direction');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->string('location')->nullable();
            $table->decimal('budget_allocated', 10, 2)->nullable();
            $table->decimal('budget_utilized', 10, 2)->nullable();
            $table->text('objective_1')->nullable();
            $table->text('objective_2')->nullable();
            $table->text('objective_3')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sports_programs');
    }
};

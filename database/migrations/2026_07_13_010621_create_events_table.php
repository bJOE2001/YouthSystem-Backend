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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('aip_reference_code')->nullable();
            $table->string('ppa_classification')->nullable();
            $table->string('center_of_participation')->nullable();
            $table->string('sustainable_development_goal')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location')->nullable();
            $table->boolean('has_no_allocated_budget')->default(false);
            $table->string('no_budget_reason')->nullable();
            $table->decimal('budget_allocated', 15, 2)->nullable();
            $table->decimal('budget_utilized', 15, 2)->nullable();
            $table->text('performance_indicator')->nullable();
            $table->text('primary_objective_1')->nullable();
            $table->text('primary_objective_2')->nullable();
            $table->text('primary_objective_3')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

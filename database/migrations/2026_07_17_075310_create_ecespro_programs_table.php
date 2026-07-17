<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecespro_programs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('school_year');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('slots')->default(1);
            $table->string('status')->default('Draft'); // Draft, Open, Closed
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecespro_programs');
    }
};

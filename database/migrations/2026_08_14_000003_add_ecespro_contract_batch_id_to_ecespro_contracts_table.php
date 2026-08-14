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
        Schema::table('ecespro_contracts', function (Blueprint $table) {
            $table->foreignId('ecespro_contract_batch_id')
                ->nullable()
                ->constrained('ecespro_contract_batches')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecespro_contracts', function (Blueprint $table) {
            $table->dropForeign(['ecespro_contract_batch_id']);
            $table->dropColumn('ecespro_contract_batch_id');
        });
    }
};

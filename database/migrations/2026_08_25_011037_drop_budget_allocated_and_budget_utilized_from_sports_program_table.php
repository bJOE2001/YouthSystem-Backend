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
        Schema::table('sports_programs', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('sports_programs', 'budget_allocated')) {
                $columnsToDrop[] = 'budget_allocated';
            }
            if (Schema::hasColumn('sports_programs', 'budget_utilized')) {
                $columnsToDrop[] = 'budget_utilized';
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sports_programs', function (Blueprint $table) {
            $table->decimal('budget_allocated', 10, 2)->nullable();
            $table->decimal('budget_utilized', 10, 2)->nullable();
        });
    }
};

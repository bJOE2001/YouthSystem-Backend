<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $usersWithoutToken = DB::table('users')
            ->whereNull('qr_code_token')
            ->orWhere('qr_code_token', '')
            ->select('id')
            ->get();

        foreach ($usersWithoutToken as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['qr_code_token' => (string) Str::uuid()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data migration only; no reversal needed
    }
};

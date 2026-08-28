<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('qr_code_token', 64)->nullable()->after('email');
        });

        $users = DB::table('users')->select('id', 'qr_code_token')->get();
        foreach ($users as $user) {
            if (empty($user->qr_code_token)) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['qr_code_token' => (string) Str::uuid()]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('qr_code_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['qr_code_token']);
            $table->dropColumn('qr_code_token');
        });
    }
};

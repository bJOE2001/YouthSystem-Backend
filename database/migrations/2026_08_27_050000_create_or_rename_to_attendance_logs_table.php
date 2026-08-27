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
        if (Schema::hasTable('event_attendances') && ! Schema::hasTable('attendance_logs')) {
            Schema::rename('event_attendances', 'attendance_logs');
        }

        if (! Schema::hasTable('attendance_logs')) {
            Schema::create('attendance_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('activity_type', 50)->default('event');
                $table->string('activity_title', 255)->nullable();
                $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
                $table->foreignId('sports_program_id')->nullable()->constrained('sports_programs')->nullOnDelete();
                $table->dateTime('time_in')->useCurrent();
                $table->dateTime('time_out')->nullable();
                $table->string('status', 32)->default('attended');
                $table->foreignId('scanned_by_user_id')->nullable()->constrained('users')->noActionOnDelete();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'activity_type']);
                $table->index(['event_id', 'user_id']);
                $table->index(['user_id', 'status']);
            });
        } else {
            Schema::table('attendance_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('attendance_logs', 'activity_type')) {
                    $table->string('activity_type', 50)->default('event')->after('user_id');
                }
                if (! Schema::hasColumn('attendance_logs', 'activity_title')) {
                    $table->string('activity_title', 255)->nullable()->after('activity_type');
                }
                if (! Schema::hasColumn('attendance_logs', 'sports_program_id')) {
                    $table->foreignId('sports_program_id')->nullable()->after('event_id')->constrained('sports_programs')->nullOnDelete();
                }
                if (! Schema::hasColumn('attendance_logs', 'remarks')) {
                    $table->text('remarks')->nullable()->after('scanned_by_user_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('attendance_logs') && ! Schema::hasTable('event_attendances')) {
            Schema::rename('attendance_logs', 'event_attendances');
        }
    }
};

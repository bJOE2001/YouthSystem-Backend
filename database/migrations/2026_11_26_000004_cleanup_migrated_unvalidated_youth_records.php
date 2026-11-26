<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove migrated pending/rejected youth_profiles and strictly audited orphan pending users,
     * while verifying all approved youth_profiles and associated users remain intact.
     */
    public function up(): void
    {
        if (! Schema::hasTable('youth_validations') || ! Schema::hasTable('youth_profiles')) {
            return;
        }

        DB::transaction(function (): void {
            $baselineApproved = DB::table('youth_profiles')
                ->where('status', 'approved')
                ->orderBy('id')
                ->pluck('user_id', 'id')
                ->all();

            $baselineApprovedUserIds = array_values(array_unique(array_filter($baselineApproved)));
            sort($baselineApprovedUserIds);

            $migratedProfiles = DB::table('youth_profiles')
                ->whereIn('status', ['pending', 'rejected'])
                ->select(['id', 'user_id'])
                ->get();

            if ($migratedProfiles->isNotEmpty()) {
                $profileIds = $migratedProfiles->pluck('id')->all();

                $backedUpIds = DB::table('youth_validations')
                    ->whereIn('source_youth_profile_id', $profileIds)
                    ->pluck('source_youth_profile_id')
                    ->all();

                $missingBackupIds = array_diff($profileIds, $backedUpIds);
                if ($missingBackupIds !== []) {
                    throw new RuntimeException(
                        'Refusing cleanup: unvalidated youth_profiles missing from youth_validations: '.implode(', ', $missingBackupIds)
                    );
                }

                DB::table('youth_profiles')
                    ->whereIn('id', $profileIds)
                    ->whereIn('status', ['pending', 'rejected'])
                    ->delete();

                $candidateUserIds = $migratedProfiles->pluck('user_id')->filter()->unique()->all();

                foreach ($candidateUserIds as $userId) {
                    $user = DB::table('users')->where('id', $userId)->first();
                    if (! $user || $user->status !== 'pending' || $user->role !== 'youth') {
                        continue;
                    }

                    if ($this->userHasDependencies((int) $userId, $user->email)) {
                        continue;
                    }

                    if (Schema::hasTable('sessions')) {
                        DB::table('sessions')->where('user_id', $userId)->delete();
                    }
                    if (Schema::hasTable('personal_access_tokens')) {
                        DB::table('personal_access_tokens')
                            ->where('tokenable_type', 'App\\Models\\User')
                            ->where('tokenable_id', $userId)
                            ->delete();
                    }
                    if (Schema::hasTable('notifications')) {
                        DB::table('notifications')
                            ->where('notifiable_type', 'App\\Models\\User')
                            ->where('notifiable_id', $userId)
                            ->delete();
                    }

                    DB::table('users')
                        ->where('id', $userId)
                        ->where('status', 'pending')
                        ->where('role', 'youth')
                        ->delete();
                }
            }

            $postApproved = DB::table('youth_profiles')
                ->where('status', 'approved')
                ->orderBy('id')
                ->pluck('user_id', 'id')
                ->all();

            if ($postApproved !== $baselineApproved) {
                throw new RuntimeException('Approved youth_profiles verification failed after cleanup.');
            }

            if ($baselineApprovedUserIds !== []) {
                $existingUserIds = DB::table('users')
                    ->whereIn('id', $baselineApprovedUserIds)
                    ->orderBy('id')
                    ->pluck('id')
                    ->all();

                if ($existingUserIds !== $baselineApprovedUserIds) {
                    throw new RuntimeException('Associated approved users verification failed after cleanup.');
                }
            }
        });
    }

    private function userHasDependencies(int $userId, ?string $email): bool
    {
        $userColumnChecks = [
            ['youth_profiles', 'user_id'],
            ['youth_profiles', 'reviewed_by'],
            ['youth_validations', 'reviewed_by'],
            ['events', 'user_id'],
            ['event_user', 'user_id'],
            ['sports_programs', 'user_id'],
            ['sports_program_user', 'user_id'],
            ['announcements', 'user_id'],
            ['announcement_user', 'user_id'],
            ['booking_requests', 'user_id'],
            ['ecespro_applications', 'user_id'],
            ['ecespro_scholars', 'user_id'],
            ['ecespro_volunteer_logs', 'verified_by_user_id'],
            ['puroks', 'user_id'],
            ['sk_officials', 'user_id'],
            ['attendance_logs', 'user_id'],
            ['attendance_logs', 'scanned_by_user_id'],
            ['sms_logs', 'user_id'],
            ['feedback_questionnaires', 'user_id'],
            ['feedback_responses', 'user_id'],
        ];

        foreach ($userColumnChecks as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                if (DB::table($table)->where($column, $userId)->exists()) {
                    return true;
                }
            }
        }

        if (! empty($email)) {
            if (Schema::hasTable('sk_officials') && DB::table('sk_officials')->where('email', $email)->exists()) {
                return true;
            }
            if (Schema::hasTable('lydc_members') && DB::table('lydc_members')->where('email', $email)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Never perform destructive restoration or deletion during rollback.
     */
    public function down(): void
    {
        // Intentionally non-destructive.
    }
};

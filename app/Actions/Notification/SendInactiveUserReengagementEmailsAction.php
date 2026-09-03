<?php

namespace App\Actions\Notification;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\InactiveUserReengagementNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SendInactiveUserReengagementEmailsAction
{
    /**
     * Default number of inactive days before triggering re-engagement.
     */
    public const DEFAULT_INACTIVE_DAYS = 30;

    /**
     * Default anti-spam cooldown days between re-engagement emails.
     */
    public const DEFAULT_COOLDOWN_DAYS = 14;

    /**
     * Grace period in days for newly registered users with null last_login_at.
     */
    public const REGISTRATION_GRACE_PERIOD_DAYS = 7;

    /**
     * Execute the action to dispatch re-engagement notifications to inactive users.
     *
     * @return array<string, mixed>
     */
    public function execute(
        int $inactiveDays = self::DEFAULT_INACTIVE_DAYS,
        int $cooldownDays = self::DEFAULT_COOLDOWN_DAYS,
        bool $dryRun = false
    ): array {
        $stats = $this->getStats($inactiveDays, $cooldownDays);

        $sentCount = 0;
        $skippedCooldownCount = 0;

        $inactiveCutoff = now()->subDays($inactiveDays);
        $registrationGraceCutoff = now()->subDays(min(self::REGISTRATION_GRACE_PERIOD_DAYS, $inactiveDays));
        $cooldownCutoff = now()->subDays($cooldownDays);

        $query = $this->getInactiveUsersQuery($inactiveCutoff, $registrationGraceCutoff);

        $query->chunkById(100, function ($users) use (
            $cooldownCutoff,
            $dryRun,
            &$sentCount,
            &$skippedCooldownCount
        ) {
            /** @var User $user */
            foreach ($users as $user) {
                if ($this->isUserInCooldown($user, $cooldownCutoff)) {
                    $skippedCooldownCount++;

                    continue;
                }

                if (! $dryRun) {
                    $referenceDate = $user->last_login_at ?? $user->created_at ?? now();
                    $daysInactive = max(1, (int) now()->diffInDays($referenceDate));
                    $lastLoginFormatted = $user->last_login_at
                        ? Carbon::parse($user->last_login_at)->format('F d, Y')
                        : 'Never logged in';

                    $user->notify(new InactiveUserReengagementNotification(
                        daysInactive: $daysInactive,
                        lastLoginFormatted: $lastLoginFormatted
                    ));
                }

                $sentCount++;
            }
        });

        return [
            'total_youth' => $stats['total_youth'],
            'active_users_count' => $stats['active_users_count'],
            'inactive_users_count' => $stats['inactive_users_count'],
            'eligible_count' => $sentCount,
            'sent_count' => $dryRun ? 0 : $sentCount,
            'cooldown_skipped_count' => $skippedCooldownCount,
            'dry_run' => $dryRun,
            'inactive_threshold_days' => $inactiveDays,
            'cooldown_days' => $cooldownDays,
        ];
    }

    /**
     * Get aggregate statistics on active vs. inactive users and delivery eligibility.
     *
     * @return array<string, mixed>
     */
    public function getStats(
        int $inactiveDays = self::DEFAULT_INACTIVE_DAYS,
        int $cooldownDays = self::DEFAULT_COOLDOWN_DAYS
    ): array {
        $baseYouthQuery = User::query()
            ->where('role', UserRole::Youth->value)
            ->where('status', UserStatus::Active->value)
            ->whereNotNull('email')
            ->where('email', '!=', '');

        $totalYouth = (clone $baseYouthQuery)->count();

        $inactiveCutoff = now()->subDays($inactiveDays);
        $registrationGraceCutoff = now()->subDays(min(self::REGISTRATION_GRACE_PERIOD_DAYS, $inactiveDays));
        $cooldownCutoff = now()->subDays($cooldownDays);

        // Active users: logged in within the cutoff
        $activeUsersCount = (clone $baseYouthQuery)
            ->where('last_login_at', '>=', $inactiveCutoff)
            ->count();

        // Inactive users: logged in before cutoff OR null last_login_at with created_at older than grace period
        $inactiveUsersQuery = $this->getInactiveUsersQuery($inactiveCutoff, $registrationGraceCutoff);
        $inactiveUsersCount = (clone $inactiveUsersQuery)->count();

        // Check cooldown eligibility
        $cooldownSkippedCount = 0;
        $eligibleCount = 0;

        (clone $inactiveUsersQuery)->chunkById(100, function ($users) use ($cooldownCutoff, &$cooldownSkippedCount, &$eligibleCount) {
            foreach ($users as $user) {
                if ($this->isUserInCooldown($user, $cooldownCutoff)) {
                    $cooldownSkippedCount++;
                } else {
                    $eligibleCount++;
                }
            }
        });

        return [
            'total_youth' => $totalYouth,
            'active_users_count' => $activeUsersCount,
            'inactive_users_count' => $inactiveUsersCount,
            'eligible_count' => $eligibleCount,
            'cooldown_skipped_count' => $cooldownSkippedCount,
            'inactive_threshold_days' => $inactiveDays,
            'cooldown_days' => $cooldownDays,
        ];
    }

    /**
     * Build the query for inactive youth users.
     */
    protected function getInactiveUsersQuery(Carbon $inactiveCutoff, Carbon $registrationGraceCutoff): Builder
    {
        return User::query()
            ->where('role', UserRole::Youth->value)
            ->where('status', UserStatus::Active->value)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where(function (Builder $query) use ($inactiveCutoff, $registrationGraceCutoff) {
                $query->where('last_login_at', '<', $inactiveCutoff)
                    ->orWhere(function (Builder $q) use ($registrationGraceCutoff) {
                        $q->whereNull('last_login_at')
                            ->where('created_at', '<=', $registrationGraceCutoff);
                    });
            });
    }

    /**
     * Check whether the user has received a re-engagement notification within the cooldown period.
     */
    protected function isUserInCooldown(User $user, Carbon $cooldownCutoff): bool
    {
        return $user->notifications()
            ->where('type', InactiveUserReengagementNotification::class)
            ->where('created_at', '>=', $cooldownCutoff)
            ->exists();
    }
}

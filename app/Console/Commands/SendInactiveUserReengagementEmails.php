<?php

namespace App\Console\Commands;

use App\Actions\Notification\SendInactiveUserReengagementEmailsAction;
use Illuminate\Console\Command;

class SendInactiveUserReengagementEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-inactive-reengagement-emails
                            {--inactive-days=30 : Inactivity cutoff in days to consider a user inactive}
                            {--cooldown-days=14 : Minimum days between re-engagement emails for the same user}
                            {--dry-run : Simulate execution without dispatching real emails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automated re-engagement email notifications to inactive youth users based on last_login_at.';

    /**
     * Execute the console command.
     */
    public function handle(SendInactiveUserReengagementEmailsAction $action): int
    {
        $inactiveDays = max(1, (int) $this->option('inactive-days'));
        $cooldownDays = max(1, (int) $this->option('cooldown-days'));
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Starting inactive user re-engagement process...');
        if ($dryRun) {
            $this->warn('DRY RUN MODE: No emails will be queued or sent.');
        }

        $result = $action->execute(
            inactiveDays: $inactiveDays,
            cooldownDays: $cooldownDays,
            dryRun: $dryRun
        );

        $this->table(
            ['Metric', 'Count / Value'],
            [
                ['Total Active Youth Accounts', $result['total_youth']],
                ['Active Users (Excluded)', $result['active_users_count']],
                ['Inactive Users (Targeted)', $result['inactive_users_count']],
                ['Eligible Recipients', $result['eligible_count']],
                ['Skipped (In Cooldown)', $result['cooldown_skipped_count']],
                ['Emails Dispatched', $result['sent_count']],
                ['Inactive Threshold', "{$result['inactive_threshold_days']} days"],
                ['Anti-Spam Cooldown', "{$result['cooldown_days']} days"],
                ['Execution Mode', $dryRun ? 'Dry Run (Simulated)' : 'Live Dispatch'],
            ]
        );

        $this->info($dryRun
            ? 'Dry run completed successfully. No emails were sent.'
            : "Re-engagement notifications completed. Queued {$result['sent_count']} emails.");

        return self::SUCCESS;
    }
}

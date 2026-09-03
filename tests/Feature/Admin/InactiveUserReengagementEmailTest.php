<?php

namespace Tests\Feature\Admin;

use App\Actions\Notification\SendInactiveUserReengagementEmailsAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Mail\InactiveUserReengagementEmail;
use App\Models\User;
use App\Notifications\InactiveUserReengagementNotification;
use App\Services\EmailTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InactiveUserReengagementEmailTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin->value,
            'status' => UserStatus::Active->value,
        ]);
    }

    public function test_identifies_and_notifies_inactive_users_based_on_last_login_at(): void
    {
        Notification::fake();

        // 1. Active youth (logged in 3 days ago) -> MUST NOT receive email
        $activeUser = User::factory()->create([
            'role' => UserRole::Youth->value,
            'status' => UserStatus::Active->value,
            'last_login_at' => now()->subDays(3),
        ]);

        // 2. Inactive youth with old login (logged in 45 days ago) -> MUST receive email
        $inactiveWithOldLogin = User::factory()->create([
            'role' => UserRole::Youth->value,
            'status' => UserStatus::Active->value,
            'last_login_at' => now()->subDays(45),
        ]);

        // 3. Inactive youth with no login history (created 20 days ago) -> MUST receive email
        $inactiveNeverLoggedIn = User::factory()->create([
            'role' => UserRole::Youth->value,
            'status' => UserStatus::Active->value,
            'last_login_at' => null,
            'created_at' => now()->subDays(20),
        ]);

        // 4. Freshly created youth with no login (registered 2 days ago within grace period) -> MUST NOT receive email
        $freshUser = User::factory()->create([
            'role' => UserRole::Youth->value,
            'status' => UserStatus::Active->value,
            'last_login_at' => null,
            'created_at' => now()->subDays(2),
        ]);

        $action = app(SendInactiveUserReengagementEmailsAction::class);
        $result = $action->execute(inactiveDays: 30, cooldownDays: 14);

        $this->assertEquals(4, $result['total_youth']);
        $this->assertEquals(1, $result['active_users_count']);
        $this->assertEquals(2, $result['inactive_users_count']);
        $this->assertEquals(2, $result['sent_count']);
        $this->assertEquals(0, $result['cooldown_skipped_count']);

        // Assert inactive users were notified
        Notification::assertSentTo($inactiveWithOldLogin, InactiveUserReengagementNotification::class);
        Notification::assertSentTo($inactiveNeverLoggedIn, InactiveUserReengagementNotification::class);

        // Assert active and fresh users were NOT notified
        Notification::assertNotSentTo($activeUser, InactiveUserReengagementNotification::class);
        Notification::assertNotSentTo($freshUser, InactiveUserReengagementNotification::class);
    }

    public function test_excludes_active_users_who_logged_in_within_threshold(): void
    {
        Notification::fake();

        User::factory()->create([
            'role' => UserRole::Youth->value,
            'status' => UserStatus::Active->value,
            'last_login_at' => now()->subDays(1),
        ]);

        User::factory()->create([
            'role' => UserRole::Youth->value,
            'status' => UserStatus::Active->value,
            'last_login_at' => now()->subDays(15),
        ]);

        User::factory()->create([
            'role' => UserRole::Youth->value,
            'status' => UserStatus::Active->value,
            'last_login_at' => now()->subDays(29),
        ]);

        $action = app(SendInactiveUserReengagementEmailsAction::class);
        $result = $action->execute(inactiveDays: 30);

        $this->assertEquals(3, $result['total_youth']);
        $this->assertEquals(3, $result['active_users_count']);
        $this->assertEquals(0, $result['inactive_users_count']);
        $this->assertEquals(0, $result['sent_count']);

        Notification::assertNothingSent();
    }

    public function test_respects_cooldown_period_and_skips_recently_notified_users(): void
    {
        Notification::fake();

        // Inactive user 1: notified 5 days ago (within 14-day cooldown)
        $recentlyNotified = User::factory()->create([
            'role' => UserRole::Youth->value,
            'status' => UserStatus::Active->value,
            'last_login_at' => now()->subDays(40),
        ]);
        $recentlyNotified->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => InactiveUserReengagementNotification::class,
            'data' => ['title' => 'We Miss You!'],
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        // Inactive user 2: notified 25 days ago (cooldown expired)
        $expiredCooldown = User::factory()->create([
            'role' => UserRole::Youth->value,
            'status' => UserStatus::Active->value,
            'last_login_at' => now()->subDays(50),
        ]);
        $expiredCooldown->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => InactiveUserReengagementNotification::class,
            'data' => ['title' => 'We Miss You!'],
            'created_at' => now()->subDays(25),
            'updated_at' => now()->subDays(25),
        ]);

        $action = app(SendInactiveUserReengagementEmailsAction::class);
        $result = $action->execute(inactiveDays: 30, cooldownDays: 14);

        $this->assertEquals(2, $result['inactive_users_count']);
        $this->assertEquals(1, $result['sent_count']);
        $this->assertEquals(1, $result['cooldown_skipped_count']);

        Notification::assertNotSentTo($recentlyNotified, InactiveUserReengagementNotification::class);
        Notification::assertSentTo($expiredCooldown, InactiveUserReengagementNotification::class);
    }

    public function test_excludes_non_youth_and_inactive_status_users(): void
    {
        Notification::fake();

        // Admin who hasn't logged in for 60 days
        $inactiveAdmin = User::factory()->create([
            'role' => UserRole::Admin->value,
            'status' => UserStatus::Active->value,
            'last_login_at' => now()->subDays(60),
        ]);

        // Suspended/Inactive account youth
        $suspendedYouth = User::factory()->create([
            'role' => UserRole::Youth->value,
            'status' => UserStatus::Suspended->value,
            'last_login_at' => now()->subDays(60),
        ]);

        $action = app(SendInactiveUserReengagementEmailsAction::class);
        $result = $action->execute(inactiveDays: 30);

        $this->assertEquals(0, $result['total_youth']);
        $this->assertEquals(0, $result['inactive_users_count']);
        $this->assertEquals(0, $result['sent_count']);

        Notification::assertNothingSent();
    }

    public function test_dry_run_mode_does_not_queue_notifications(): void
    {
        Notification::fake();

        $inactiveUser = User::factory()->create([
            'role' => UserRole::Youth->value,
            'status' => UserStatus::Active->value,
            'last_login_at' => now()->subDays(45),
        ]);

        $action = app(SendInactiveUserReengagementEmailsAction::class);
        $result = $action->execute(inactiveDays: 30, dryRun: true);

        $this->assertTrue($result['dry_run']);
        $this->assertEquals(1, $result['eligible_count']);
        $this->assertEquals(0, $result['sent_count']);

        Notification::assertNothingSent();
    }

    public function test_artisan_command_executes_successfully(): void
    {
        Notification::fake();

        User::factory()->create([
            'role' => UserRole::Youth->value,
            'status' => UserStatus::Active->value,
            'last_login_at' => now()->subDays(40),
        ]);

        // Test with dry-run
        $this->artisan('app:send-inactive-reengagement-emails', [
            '--dry-run' => true,
            '--inactive-days' => 30,
        ])
            ->expectsOutputToContain('DRY RUN MODE')
            ->expectsOutputToContain('Dry run completed successfully')
            ->assertExitCode(0);

        Notification::assertNothingSent();

        // Test live execution
        $this->artisan('app:send-inactive-reengagement-emails', [
            '--inactive-days' => 30,
        ])
            ->expectsOutputToContain('Re-engagement notifications completed. Queued 1 emails.')
            ->assertExitCode(0);
    }

    public function test_admin_api_endpoints_for_stats_and_sending(): void
    {
        Sanctum::actingAs($this->admin);

        User::factory()->create([
            'role' => UserRole::Youth->value,
            'status' => UserStatus::Active->value,
            'last_login_at' => now()->subDays(45),
        ]);

        User::factory()->create([
            'role' => UserRole::Youth->value,
            'status' => UserStatus::Active->value,
            'last_login_at' => now()->subDays(5),
        ]);

        // Stats endpoint
        $statsResponse = $this->getJson('/api/admin/settings/engagement-emails/stats?inactive_days=30');
        $statsResponse->assertStatus(200)
            ->assertJsonPath('data.total_youth', 2)
            ->assertJsonPath('data.active_users_count', 1)
            ->assertJsonPath('data.inactive_users_count', 1)
            ->assertJsonPath('data.eligible_count', 1);

        // Send endpoint (dry-run)
        $dryRunResponse = $this->postJson('/api/admin/settings/engagement-emails/send', [
            'inactive_days' => 30,
            'dry_run' => true,
        ]);
        $dryRunResponse->assertStatus(200)
            ->assertJsonPath('data.dry_run', true)
            ->assertJsonPath('data.sent_count', 0)
            ->assertJsonPath('data.eligible_count', 1);

        // Send endpoint (live)
        $liveResponse = $this->postJson('/api/admin/settings/engagement-emails/send', [
            'inactive_days' => 30,
            'dry_run' => false,
        ]);
        $liveResponse->assertStatus(200)
            ->assertJsonPath('data.dry_run', false)
            ->assertJsonPath('data.sent_count', 1);
    }

    public function test_inactive_user_reengagement_mailable_and_view_rendering(): void
    {
        $user = User::factory()->create([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'last_login_at' => now()->subDays(50),
        ]);

        $mailable = new InactiveUserReengagementEmail($user, 50, 'January 10, 2026');
        $mailable->assertHasSubject("👋 We Miss You, Juan Dela Cruz! Discover What's New at TCYDO");
        $mailable->assertSeeInHtml('We Miss You, Juan Dela Cruz!');
        $mailable->assertSeeInHtml('ECESPRO Scholarship');
        $mailable->assertSeeInHtml('Log In & Explore');

        // Test with customized template
        $templateService = app(EmailTemplateService::class);
        $templateService->updateTemplate('inactive_user_reengagement', [
            'subject' => 'Special comeback offer for {user_name}',
            'heading' => 'Welcome back, {user_name}!',
            'body' => 'Custom re-engagement message body.',
            'button_text' => 'Reactivate Account',
        ]);

        $customMailable = new InactiveUserReengagementEmail($user);
        $customMailable->assertHasSubject('Special comeback offer for Juan Dela Cruz');
        $customMailable->assertSeeInHtml('Welcome back, Juan Dela Cruz!');
        $customMailable->assertSeeInHtml('Custom re-engagement message body.');
        $customMailable->assertSeeInHtml('Reactivate Account');
    }
}

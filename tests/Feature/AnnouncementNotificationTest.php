<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\EcesproProgram;
use App\Models\User;
use App\Notifications\NewAnnouncementNotification;
use App\Notifications\NewEcesproProgramNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnnouncementNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_announcement_notifies_both_sk_and_youth_in_app(): void
    {
        $admin = User::factory()->admin()->active()->create(['email' => 'city_admin@example.com']);
        $skAdmin = User::factory()->skAdmin()->active()->create(['email' => 'sk_official@example.com']);
        $youth = User::factory()->youth()->active()->create(['email' => 'youth_member@example.com']);
        $otherAdmin = User::factory()->admin()->active()->create(['email' => 'other_admin@example.com']);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/announcements', [
            'title' => 'Important Community Gathering',
            'description' => 'Details about the youth summit.',
        ]);

        $response->assertCreated();

        $announcement = Announcement::where('title', 'Important Community Gathering')->first();
        $this->assertNotNull($announcement);

        // Assert database notifications exist for both SK Admin and Youth
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $skAdmin->id,
            'type' => NewAnnouncementNotification::class,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $youth->id,
            'type' => NewAnnouncementNotification::class,
        ]);

        // Admin users should not receive the notification
        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
            'type' => NewAnnouncementNotification::class,
        ]);

        // Verify that notification channels include both database and mail
        $notifInstance = new NewAnnouncementNotification($announcement);
        $this->assertContains('database', $notifInstance->via($skAdmin));
        $this->assertContains('mail', $notifInstance->via($skAdmin));
        $this->assertContains('database', $notifInstance->via($youth));
        $this->assertContains('mail', $notifInstance->via($youth));

        // Verify toMail returns NewAnnouncementEmail addressed to the respective user
        $skMail = $notifInstance->toMail($skAdmin);
        $this->assertInstanceOf(\App\Mail\NewAnnouncementEmail::class, $skMail);
        $this->assertTrue($skMail->hasTo($skAdmin->email));

        $youthMail = $notifInstance->toMail($youth);
        $this->assertInstanceOf(\App\Mail\NewAnnouncementEmail::class, $youthMail);
        $this->assertTrue($youthMail->hasTo($youth->email));

        // Check payload for SK Admin notification (title and description directly)
        $skNotification = $skAdmin->notifications()->first();
        $this->assertNotNull($skNotification);
        $this->assertEquals('Important Community Gathering', $skNotification->data['title']);
        $this->assertEquals('Details about the youth summit.', $skNotification->data['message']);
        $this->assertEquals($announcement->id, $skNotification->data['announcement_id']);
        $this->assertEquals('/announcements', $skNotification->data['url']);

        // Check payload for Youth notification
        $youthNotification = $youth->notifications()->first();
        $this->assertNotNull($youthNotification);
        $this->assertEquals('Important Community Gathering', $youthNotification->data['title']);
        $this->assertEquals('Details about the youth summit.', $youthNotification->data['message']);
        $this->assertEquals($announcement->id, $youthNotification->data['announcement_id']);
        $this->assertEquals('/youth/announcements', $youthNotification->data['url']);
    }

    public function test_sk_admin_can_view_and_mark_announcement_notification_as_read(): void
    {
        $admin = User::factory()->admin()->active()->create();
        $skAdmin = User::factory()->skAdmin()->active()->create();

        Sanctum::actingAs($admin);
        $this->postJson('/api/announcements', [
            'title' => 'SK Briefing Notice',
            'description' => 'Meeting tomorrow at 10 AM.',
        ])->assertCreated();

        $announcement = Announcement::latest()->first();

        // Switch to SK Admin
        Sanctum::actingAs($skAdmin);

        $unreadCountResponse = $this->getJson('/api/notifications/unread-count');
        $unreadCountResponse->assertOk()->assertJson(['count' => 1]);

        $listResponse = $this->getJson('/api/notifications');
        $listResponse->assertOk()
            ->assertJsonFragment(['title' => 'SK Briefing Notice'])
            ->assertJsonFragment(['message' => 'Meeting tomorrow at 10 AM.'])
            ->assertJsonFragment(['url' => '/announcements']);

        // Mark announcement as read
        $markResponse = $this->postJson("/api/announcements/{$announcement->id}/read");
        $markResponse->assertOk();

        $unreadCountAfter = $this->getJson('/api/notifications/unread-count');
        $unreadCountAfter->assertOk()->assertJson(['count' => 0]);
    }

    public function test_sk_admin_creating_announcement_notifies_other_sk_and_youth(): void
    {
        $skAdminCreator = User::factory()->skAdmin()->active()->create();
        $skAdminReceiver = User::factory()->skAdmin()->active()->create();
        $youth = User::factory()->youth()->active()->create();

        Sanctum::actingAs($skAdminCreator);

        $response = $this->postJson('/api/announcements', [
            'title' => 'Barangay Clean-up Drive',
            'description' => 'Join us this weekend.',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $skAdminReceiver->id,
            'type' => NewAnnouncementNotification::class,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $youth->id,
            'type' => NewAnnouncementNotification::class,
        ]);
    }

    public function test_opening_new_ecespro_program_notifies_both_sk_and_youth_in_app(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->active()->create();
        $skAdmin = User::factory()->skAdmin()->active()->create();
        $youth = User::factory()->youth()->active()->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/ecespro-programs', [
            'title' => 'ECESPRO College Scholarship 2026-2027',
            'school_year' => '2026-2027',
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-31',
            'status' => 'Open',
            'description' => 'Applications are now officially accepted for all college students.',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $skAdmin->id,
            'type' => NewEcesproProgramNotification::class,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $youth->id,
            'type' => NewEcesproProgramNotification::class,
        ]);

        // Admin should not receive the notification
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $admin->id,
            'type' => NewEcesproProgramNotification::class,
        ]);

        Mail::assertNothingSent();

        $skNotif = $skAdmin->notifications()->first();
        $this->assertEquals('ECESPRO College Scholarship 2026-2027', $skNotif->data['title']);
        $this->assertEquals('Applications are now officially accepted for all college students.', $skNotif->data['message']);
        $this->assertEquals('/sk/scholarship/ecespro', $skNotif->data['url']);

        $youthNotif = $youth->notifications()->first();
        $this->assertEquals('ECESPRO College Scholarship 2026-2027', $youthNotif->data['title']);
        $this->assertEquals('/youth/scholarship/ecespro', $youthNotif->data['url']);
    }

    public function test_updating_ecespro_program_to_open_status_triggers_notification(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->active()->create();
        $skAdmin = User::factory()->skAdmin()->active()->create();
        $youth = User::factory()->youth()->active()->create();

        $program = EcesproProgram::create([
            'title' => 'ECESPRO Batch 2',
            'school_year' => '2026-2027',
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-31',
            'status' => 'Draft',
            'description' => 'Second batch opening soon.',
        ]);

        Sanctum::actingAs($admin);

        // Update status to Open
        $response = $this->postJson("/api/admin/ecespro-programs/{$program->id}", [
            'status' => 'Open',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $skAdmin->id,
            'type' => NewEcesproProgramNotification::class,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $youth->id,
            'type' => NewEcesproProgramNotification::class,
        ]);
    }
}

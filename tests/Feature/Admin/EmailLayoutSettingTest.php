<?php

namespace Tests\Feature\Admin;

use App\Mail\NewAnnouncementEmail;
use App\Mail\TestEmailLayout;
use App\Models\Announcement;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmailLayoutSettingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_public_user_can_get_email_layout_settings(): void
    {
        $response = $this->getJson('/api/system-settings/email-layout');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'logo_url',
                    'primary_color',
                    'secondary_color',
                    'header_title',
                    'footer_text',
                    'social_links',
                ],
            ]);
    }

    public function test_admin_can_get_email_layout_settings(): void
    {
        $admin = User::factory()->admin()->active()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/system-settings/email-layout');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'logo_url',
                    'primary_color',
                    'secondary_color',
                    'body_bg_color',
                    'card_bg_color',
                    'card_border_radius',
                    'header_title',
                    'footer_text',
                    'show_logo',
                    'show_footer_contact',
                    'show_social_links',
                    'social_links',
                ],
            ]);
    }

    public function test_admin_can_update_email_layout_settings(): void
    {
        $admin = User::factory()->admin()->active()->create();

        $payload = [
            'header_title' => 'Updated City Youth Development Office',
            'header_subtitle' => 'Tagum City Youth Council',
            'show_header_title' => true,
            'primary_color' => '#15803d',
            'secondary_color' => '#166534',
            'body_bg_color' => '#e2e8f0',
            'card_bg_color' => '#ffffff',
            'card_border_radius' => '16px',
            'footer_text' => 'Youth Office Official Footer',
            'footer_tagline' => 'Inspiring Tomorrow',
            'footer_disclaimer' => 'Automated message.',
            'contact_email' => 'contact@tagumyouth.gov.ph',
            'contact_phone' => '(084) 999-0000',
            'office_address' => 'Youth Center, Tagum City',
            'show_footer_contact' => true,
            'show_social_links' => true,
            'social_links' => [
                ['platform' => 'facebook', 'url' => 'https://facebook.com/tagumyouth'],
            ],
            'copyright_text' => '© {year} Tagum Youth System',
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/system-settings/email-layout', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Email layout settings updated successfully.',
                'data' => [
                    'header_title' => 'Updated City Youth Development Office',
                    'header_subtitle' => 'Tagum City Youth Council',
                    'primary_color' => '#15803d',
                    'secondary_color' => '#166534',
                    'footer_text' => 'Youth Office Official Footer',
                    'contact_email' => 'contact@tagumyouth.gov.ph',
                ],
            ]);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'email_layout',
        ]);
    }

    public function test_admin_can_upload_and_remove_custom_logo_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->active()->create();

        $logoFile = UploadedFile::fake()->image('custom-logo.png', 200, 200);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/system-settings/email-layout', [
                'logo_image' => $logoFile,
                'header_title' => 'Logo Test Office',
            ]);

        $response->assertStatus(200);

        $setting = SystemSetting::where('key', 'email_layout')->first();
        $this->assertNotNull($setting);
        $this->assertNotEmpty($setting->value['image_path']);
        Storage::disk('public')->assertExists($setting->value['image_path']);

        // Now remove the logo
        $removeResponse = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/system-settings/email-layout', [
                'remove_logo' => true,
            ]);

        $removeResponse->assertStatus(200);
        $setting->refresh();
        $this->assertNull($setting->value['image_path']);
    }

    public function test_non_admin_cannot_update_email_layout_settings(): void
    {
        $youth = User::factory()->youth()->active()->create();

        $response = $this->actingAs($youth, 'sanctum')
            ->postJson('/api/admin/system-settings/email-layout', [
                'header_title' => 'Hacked Office',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_preview_email_layout(): void
    {
        $admin = User::factory()->admin()->active()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/system-settings/email-layout/preview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'html',
                'data',
            ]);

        // Direct HTML format preview
        $htmlResponse = $this->actingAs($admin, 'sanctum')
            ->get('/api/admin/system-settings/email-layout/preview?format=html');

        $htmlResponse->assertStatus(200)
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('Email Layout Test');
    }

    public function test_admin_can_send_test_email(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->active()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/system-settings/email-layout/send-test', [
                'email' => 'admin_test@tagum.gov.ph',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Test email successfully sent to admin_test@tagum.gov.ph.',
            ]);

        Mail::assertSent(TestEmailLayout::class, function ($mail) {
            return $mail->hasTo('admin_test@tagum.gov.ph');
        });
    }

    public function test_transactional_email_renders_with_master_layout(): void
    {
        $user = User::factory()->youth()->active()->create(['name' => 'Jane Doe']);
        $announcement = Announcement::create([
            'user_id' => $user->id,
            'title' => 'Youth Leadership Summit 2026',
            'description' => 'Join us for leadership training and networking.',
        ]);

        $mailable = new NewAnnouncementEmail($user, $announcement);
        $rendered = $mailable->render();

        $this->assertStringContainsString('Official Announcement', $rendered);
        $this->assertStringContainsString('Youth Leadership Summit 2026', $rendered);
        $this->assertStringContainsString('Jane Doe', $rendered);
        $this->assertStringContainsString('Tagum City Youth Development Office', $rendered);
    }
}

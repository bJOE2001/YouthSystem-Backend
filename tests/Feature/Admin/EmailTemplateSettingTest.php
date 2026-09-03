<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\EmailTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailTemplateSettingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    public function test_can_fetch_all_email_templates(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/settings/email-templates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'youth_validated' => [
                        'name',
                        'description',
                        'subject',
                        'heading',
                        'body',
                        'button_text',
                        'placeholders',
                        'sample_data',
                    ],
                    'booking_confirmed',
                    'booking_cancelled',
                    'new_announcement',
                    'new_event',
                    'certificate_issued',
                    'new_compliance_schedule',
                    'ecespro_status',
                    'inactive_user_reengagement',
                ],
            ]);
    }

    public function test_can_fetch_single_email_template(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/settings/email-templates/youth_validated');

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Youth Account Validated')
            ->assertJsonPath('data.is_customized', false);
    }

    public function test_can_update_email_template_custom_text(): void
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'subject' => 'Customized Subject for {user_name}',
            'heading' => 'Welcome aboard, {user_name}!',
            'body' => 'Your custom account body message is here.',
            'button_text' => 'Access Your Profile →',
            'security_notice' => 'Custom security alert message.',
        ];

        $response = $this->postJson('/api/admin/settings/email-templates/youth_validated', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_customized', true)
            ->assertJsonPath('data.subject', 'Customized Subject for {user_name}')
            ->assertJsonPath('data.heading', 'Welcome aboard, {user_name}!')
            ->assertJsonPath('data.body', 'Your custom account body message is here.')
            ->assertJsonPath('data.button_text', 'Access Your Profile →');

        // Verify placeholder rendering
        $service = app(EmailTemplateService::class);
        $rendered = $service->render('youth_validated', [
            'user_name' => 'Maria Clara',
        ]);

        $this->assertEquals('Customized Subject for Maria Clara', $rendered['subject']);
        $this->assertEquals('Welcome aboard, Maria Clara!', $rendered['heading']);
    }

    public function test_can_reset_email_template_to_default(): void
    {
        Sanctum::actingAs($this->admin);

        // Customize first
        $this->postJson('/api/admin/settings/email-templates/booking_confirmed', [
            'subject' => 'Custom Booking Subject',
            'heading' => 'Custom Heading',
            'body' => 'Custom Body',
            'button_text' => 'Custom Button',
        ])->assertStatus(200);

        // Reset
        $response = $this->postJson('/api/admin/settings/email-templates/booking_confirmed/reset');

        $response->assertStatus(200)
            ->assertJsonPath('data.is_customized', false)
            ->assertJsonPath('data.heading', 'Facility Booking Confirmed!');
    }

    public function test_returns_404_for_invalid_template_key(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/settings/email-templates/non_existent_key');
        $response->assertStatus(404);

        $updateResponse = $this->postJson('/api/admin/settings/email-templates/non_existent_key', [
            'subject' => 'Test',
        ]);
        $updateResponse->assertStatus(404);
    }
}

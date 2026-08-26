<?php

namespace Tests\Feature\Admin;

use App\Models\EcesproApplication;
use App\Models\EcesproProgram;
use App\Models\EcesproScholar;
use App\Models\EcesproSetting;
use App\Models\EcesproVolunteerLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcesproVolunteerHoursSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_ecespro_settings_with_default_required_volunteer_hours(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/ecespro-settings');

        $response->assertOk()
            ->assertJsonPath('data.required_volunteer_hours', 36);

        // Also test public /api/ecespro/settings alias
        $publicResponse = $this->getJson('/api/ecespro/settings');
        $publicResponse->assertOk()
            ->assertJsonPath('data.required_volunteer_hours', 36);
    }

    public function test_admin_can_update_required_volunteer_hours_setting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/ecespro-settings/required_volunteer_hours', [
                'value' => 45.5,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.value', 45.5);

        $this->assertEquals(45.5, (float) EcesproSetting::get('required_volunteer_hours'));
    }

    public function test_admin_cannot_set_invalid_required_volunteer_hours(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Less than 1
        $responseLow = $this->actingAs($admin)
            ->postJson('/api/admin/ecespro-settings/required_volunteer_hours', [
                'value' => 0,
            ]);
        $responseLow->assertStatus(422)
            ->assertJsonValidationErrors(['value']);

        // Greater than 500
        $responseHigh = $this->actingAs($admin)
            ->postJson('/api/admin/ecespro-settings/required_volunteer_hours', [
                'value' => 501,
            ]);
        $responseHigh->assertStatus(422)
            ->assertJsonValidationErrors(['value']);
    }

    public function test_scholar_volunteer_summary_uses_dynamic_required_hours_setting(): void
    {
        EcesproSetting::set('required_volunteer_hours', 40.00);

        $youthUser = User::factory()->create(['role' => 'youth']);
        $program = EcesproProgram::create([
            'title' => 'ECESPRO 2026',
            'school_year' => '2026-2027',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'status' => 'Open',
        ]);
        $app = EcesproApplication::create([
            'user_id' => $youthUser->id,
            'ecespro_program_id' => $program->id,
            'application_status' => 'Approved',
        ]);

        $scholar = EcesproScholar::create([
            'user_id' => $youthUser->id,
            'ecespro_application_id' => $app->id,
            'scholar_no' => 'SCH-TEST-001',
            'status' => 'Active',
            'required_volunteer_hours' => null, // Default to dynamic setting
            'total_rendered_hours' => 20.00,
            'is_volunteer_completed' => false,
        ]);

        EcesproVolunteerLog::create([
            'scholar_id' => $scholar->id,
            'duty_title' => 'Office Duty',
            'time_in' => now()->subHours(5),
            'time_out' => now(),
            'hours_rendered' => 5.00,
        ]);

        $scholar->recalculateVolunteerHours();
        $scholar->refresh();

        $response = $this->actingAs($youthUser)
            ->getJson('/api/youth/ecespro/volunteer-hours');

        $response->assertOk()
            ->assertJsonPath('is_scholar', true)
            ->assertJsonPath('rendered_hours', 5)
            ->assertJsonPath('required_hours', 40)
            ->assertJsonPath('is_completed', false)
            ->assertJsonPath('required_volunteer_hours', 40)
            ->assertJsonPath('total_rendered_hours', 5);

        // Also test /api/ecespro/scholar/volunteer-summary alias
        $aliasResponse = $this->actingAs($youthUser)
            ->getJson('/api/ecespro/scholar/volunteer-summary');

        $aliasResponse->assertOk()
            ->assertJsonPath('rendered_hours', 5)
            ->assertJsonPath('required_hours', 40)
            ->assertJsonPath('is_completed', false);
    }
}

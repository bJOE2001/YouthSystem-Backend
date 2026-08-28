<?php

namespace Tests\Feature\Admin;

use App\Models\EcesproApplication;
use App\Models\EcesproProgram;
use App\Models\EcesproScholar;
use App\Models\EcesproVolunteerLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcesproScholarVolunteerHoursTest extends TestCase
{
    use RefreshDatabase;

    private function createScholarUser(string $name = 'Scholar User', ?float $overrideHours = null): array
    {
        $user = User::factory()->create([
            'name' => $name,
            'role' => 'youth',
            'status' => 'active',
        ]);

        $program = EcesproProgram::create([
            'title' => 'ECESPRO Tertiary 2026',
            'school_year' => '2026-2027',
            'start_date' => '2026-08-01',
            'end_date' => '2026-12-31',
            'status' => 'Open',
        ]);

        $app = EcesproApplication::create([
            'user_id' => $user->id,
            'ecespro_program_id' => $program->id,
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1] ?? 'Doe',
            'application_status' => 'Approved',
        ]);

        $scholar = EcesproScholar::create([
            'user_id' => $user->id,
            'ecespro_application_id' => $app->id,
            'scholar_no' => 'SCH-'.rand(1000, 9999),
            'school' => 'Tagum City College',
            'course' => 'BS Information Technology',
            'status' => 'Active',
            'compliance_status' => 'Compliant',
            'required_volunteer_hours' => $overrideHours,
            'total_rendered_hours' => 0.00,
            'is_volunteer_completed' => false,
        ]);

        return [$user, $scholar, $app];
    }

    public function test_default_mandated_hours_is_36_when_setting_is_unset(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        [$youthUser, $scholar] = $this->createScholarUser('Juan Luna');

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/ecespro-scholars/{$scholar->id}/volunteer-logs");

        $response->assertOk()
            ->assertJsonPath('required_volunteer_hours', 36)
            ->assertJsonPath('required_hours', 36)
            ->assertJsonPath('total_rendered_hours', 0)
            ->assertJsonPath('remaining_hours', 36)
            ->assertJsonPath('progress_percentage', 0)
            ->assertJsonPath('is_volunteer_completed', false)
            ->assertJsonPath('has_override', false)
            ->assertJsonPath('override_hours', null);
    }

    public function test_admin_settings_update_propagates_to_scholars_without_override(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        [$youthUser1, $scholarWithoutOverride] = $this->createScholarUser('Scholar Alpha', null);
        [$youthUser2, $scholarWithOverride] = $this->createScholarUser('Scholar Beta', 24.00);

        // Update global setting to 48 hours
        $this->actingAs($admin)
            ->postJson('/api/admin/ecespro-settings', [
                'required_volunteer_hours' => 48,
            ])
            ->assertOk();

        // Scholar without override should now require 48 hours
        $res1 = $this->actingAs($admin)
            ->getJson("/api/admin/ecespro-scholars/{$scholarWithoutOverride->id}/volunteer-logs");
        $res1->assertOk()
            ->assertJsonPath('required_volunteer_hours', 48)
            ->assertJsonPath('has_override', false);

        // Scholar with override should remain 24 hours
        $res2 = $this->actingAs($admin)
            ->getJson("/api/admin/ecespro-scholars/{$scholarWithOverride->id}/volunteer-logs");
        $res2->assertOk()
            ->assertJsonPath('required_volunteer_hours', 24)
            ->assertJsonPath('has_override', true)
            ->assertJsonPath('override_hours', 24);
    }

    public function test_admin_can_update_scholar_custom_override_hours(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        [$youthUser, $scholar] = $this->createScholarUser('Scholar Gamma', null);

        // Admin updates scholar with custom required hours = 30
        $response = $this->actingAs($admin)
            ->postJson("/api/admin/ecespro-scholars/{$scholar->id}", [
                'required_volunteer_hours' => 30.00,
            ]);

        $response->assertOk();

        $scholar->refresh();
        $this->assertEquals(30.00, (float) $scholar->required_volunteer_hours);
        $this->assertEquals(30.00, $scholar->effective_required_volunteer_hours);
    }

    public function test_manual_duty_logging_and_recalculation_and_completion_trigger(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        [$youthUser, $scholar] = $this->createScholarUser('Scholar Delta', null);

        // Global default is 36. Log 20 hours for 1st Sem 2025-2026
        $log1Response = $this->actingAs($admin)
            ->postJson("/api/admin/ecespro-scholars/{$scholar->id}/volunteer-logs", [
                'activity_type' => 'office_duty',
                'duty_title' => 'City Library Desk Duty',
                'time_in' => Carbon::now()->subHours(20)->toIso8601String(),
                'time_out' => Carbon::now()->toIso8601String(),
                'hours_rendered' => 20.00,
                'semester_period' => '1st Sem 2025-2026',
                'remarks' => 'Completed full first shift.',
            ]);

        $log1Response->assertStatus(201)
            ->assertJsonPath('scholar.total_rendered_hours', '20.00')
            ->assertJsonPath('scholar.is_volunteer_completed', false);

        $scholar->refresh();
        $this->assertEquals(20.00, (float) $scholar->total_rendered_hours);
        $this->assertEquals(16.00, $scholar->remaining_hours);
        $this->assertFalse($scholar->is_volunteer_completed);

        // Log another 16 hours to reach 36 hours total -> should trigger completion
        $log2Response = $this->actingAs($admin)
            ->postJson("/api/admin/ecespro-scholars/{$scholar->id}/volunteer-logs", [
                'activity_type' => 'community_service',
                'duty_title' => 'Coastal Clean-up Drive',
                'hours_rendered' => 16.00,
                'semester_period' => '1st Sem 2025-2026',
                'remarks' => 'Completed second shift.',
            ]);

        $log2Response->assertStatus(201)
            ->assertJsonPath('scholar.total_rendered_hours', '36.00')
            ->assertJsonPath('scholar.is_volunteer_completed', true);

        $scholar->refresh();
        $this->assertEquals(36.00, (float) $scholar->total_rendered_hours);
        $this->assertEquals(0.00, $scholar->remaining_hours);
        $this->assertEquals(100.0, $scholar->progress_percentage);
        $this->assertTrue($scholar->is_volunteer_completed);

        // Fetch volunteer logs endpoint to verify semester summaries
        $logsSummary = $this->actingAs($admin)
            ->getJson("/api/admin/ecespro-scholars/{$scholar->id}/volunteer-logs");

        $logsSummary->assertOk()
            ->assertJsonPath('required_volunteer_hours', 36)
            ->assertJsonPath('total_rendered_hours', 36)
            ->assertJsonPath('remaining_hours', 0)
            ->assertJsonPath('progress_percentage', 100)
            ->assertJsonPath('is_volunteer_completed', true)
            ->assertJsonCount(1, 'semester_summaries')
            ->assertJsonPath('semester_summaries.0.semester_period', '1st Sem 2025-2026')
            ->assertJsonPath('semester_summaries.0.rendered_hours', 36)
            ->assertJsonPath('semester_summaries.0.is_completed', true)
            ->assertJsonCount(2, 'logs');

        // Delete the second log (16h) -> total rendered goes back to 20h and completion becomes false
        $log2Id = $log2Response->json('log.id');
        $deleteResponse = $this->actingAs($admin)
            ->postJson("/api/admin/ecespro-scholars/{$scholar->id}/volunteer-logs/{$log2Id}/delete");

        $deleteResponse->assertOk()
            ->assertJsonPath('scholar.total_rendered_hours', '20.00')
            ->assertJsonPath('scholar.is_volunteer_completed', false);

        $scholar->refresh();
        $this->assertEquals(20.00, (float) $scholar->total_rendered_hours);
        $this->assertEquals(16.00, $scholar->remaining_hours);
        $this->assertFalse($scholar->is_volunteer_completed);
    }

    public function test_compliance_validations_includes_volunteer_hours_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        [$youthUser, $scholar] = $this->createScholarUser('Scholar Epsilon', 36.00);

        $scholar->requirements_history = [
            [
                'school_year' => '2025-2026',
                'semester' => '1st Semester',
                'general_average' => '1.25',
                'status' => 'Pending',
                'submitted_at' => now()->toDateString(),
                'filePath' => '/storage/doc1.pdf',
            ],
        ];
        $scholar->total_rendered_hours = 36.00;
        $scholar->is_volunteer_completed = true;
        $scholar->save();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/ecespro-compliance-validations');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.scholarId', $scholar->id)
            ->assertJsonPath('data.0.is_volunteer_completed', true)
            ->assertJsonPath('data.0.total_rendered_hours', 36)
            ->assertJsonPath('data.0.required_volunteer_hours', 36);
    }

    public function test_youth_can_view_comprehensive_volunteer_kpis_and_active_session(): void
    {
        [$youthUser, $scholar] = $this->createScholarUser('Scholar Zeta', 36.00);

        // Create completed log (10h)
        EcesproVolunteerLog::create([
            'scholar_id' => $scholar->id,
            'activity_type' => 'office_duty',
            'duty_title' => 'Youth Desk Support',
            'time_in' => now()->subHours(12),
            'time_out' => now()->subHours(2),
            'hours_rendered' => 10.00,
            'semester_period' => '1st Sem 2025-2026',
        ]);

        // Create active (timed-in) session
        EcesproVolunteerLog::create([
            'scholar_id' => $scholar->id,
            'activity_type' => 'community_service',
            'duty_title' => 'Tagum Tree Planting Project',
            'time_in' => now()->subHours(1),
            'time_out' => null,
            'hours_rendered' => 0.00,
            'semester_period' => '1st Sem 2025-2026',
        ]);

        $scholar->recalculateVolunteerHours();

        $response = $this->actingAs($youthUser)
            ->getJson('/api/youth/ecespro/volunteer-hours');

        $response->assertOk()
            ->assertJsonPath('is_scholar', true)
            ->assertJsonPath('scholar_id', $scholar->id)
            ->assertJsonPath('required_volunteer_hours', 36)
            ->assertJsonPath('total_rendered_hours', 10)
            ->assertJsonPath('remaining_hours', 26)
            ->assertJsonPath('progress_percentage', 27.8)
            ->assertJsonPath('is_volunteer_completed', false)
            ->assertJsonPath('active_session.duty_title', 'Tagum Tree Planting Project')
            ->assertJsonCount(2, 'logs');

        // Test alias routes
        $alias1 = $this->actingAs($youthUser)->getJson('/api/youth/ecespro-volunteer-hours');
        $alias1->assertOk()->assertJsonPath('is_scholar', true);

        $alias2 = $this->actingAs($youthUser)->getJson('/api/ecespro/scholar/volunteer-summary');
        $alias2->assertOk()->assertJsonPath('is_scholar', true);
    }
}

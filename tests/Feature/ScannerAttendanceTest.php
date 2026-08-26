<?php

namespace Tests\Feature;

use App\Models\EcesproApplication;
use App\Models\EcesproProgram;
use App\Models\EcesproScholar;
use App\Models\EcesproVolunteerLog;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ScannerAttendanceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_regular_youth_scan_records_single_attendance(): void
    {
        $admin = User::factory()->admin()->active()->create();
        $youth = User::factory()->youth()->active()->create([
            'name' => 'Juan Dela Cruz',
            'qr_code_token' => (string) Str::uuid(),
        ]);

        $event = Event::create([
            'name' => 'Tagum Youth Leadership Summit 2026',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'location' => 'Tagum City Pavilion',
            'user_id' => $admin->id,
            'status' => 'upcoming',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/scanner/record-scan', [
                'qr_code_token' => $youth->qr_code_token,
                'event_id' => $event->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_scholar' => false,
                'scan_type' => 'attendance_only',
                'attendee_name' => 'Juan Dela Cruz',
                'role' => 'Youth',
                'status' => 'attended',
            ])
            ->assertJsonFragment([
                'message' => '✅ Attendance recorded for Juan Dela Cruz!',
            ]);

        $this->assertDatabaseHas('event_attendances', [
            'user_id' => $youth->id,
            'event_id' => $event->id,
            'status' => 'attended',
            'scanned_by_user_id' => $admin->id,
        ]);

        $this->assertTrue($youth->joinedEvents()->where('event_id', $event->id)->exists());
    }

    public function test_scholar_scan_1_records_time_in(): void
    {
        $admin = User::factory()->admin()->active()->create();
        $scholarUser = User::factory()->youth()->active()->create([
            'name' => 'Maria Santos',
            'qr_code_token' => (string) Str::uuid(),
        ]);

        $program = EcesproProgram::create([
            'title' => 'ECESPRO Tertiary 2026',
            'school_year' => '2026-2027',
            'start_date' => '2026-08-01',
            'end_date' => '2026-12-31',
            'status' => 'Open',
        ]);

        $app = EcesproApplication::create([
            'user_id' => $scholarUser->id,
            'ecespro_program_id' => $program->id,
            'application_status' => 'Approved',
        ]);

        $scholar = EcesproScholar::create([
            'user_id' => $scholarUser->id,
            'ecespro_application_id' => $app->id,
            'scholar_no' => 'SCH-0001',
            'status' => 'Active',
            'required_volunteer_hours' => 30.00,
            'total_rendered_hours' => 0.00,
            'is_volunteer_completed' => false,
        ]);

        $event = Event::create([
            'name' => 'Youth Sports Festival',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'user_id' => $admin->id,
            'status' => 'upcoming',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/scanner/record-scan', [
                'qr_code_token' => $scholarUser->qr_code_token,
                'event_id' => $event->id,
                'duty_title' => 'Youth Sports Fest Marshall',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_scholar' => true,
                'scan_type' => 'time_in',
                'attendee_name' => 'Maria Santos',
                'role' => 'Scholar',
                'status' => 'timed_in',
                'duty_title' => 'Youth Sports Fest Marshall',
                'total_rendered_hours' => 0.0,
                'required_volunteer_hours' => 30.0,
                'is_volunteer_completed' => false,
            ])
            ->assertJsonFragment([
                'message' => '🟢 Time-In recorded for Scholar Maria Santos!',
            ]);

        $this->assertDatabaseHas('ecespro_volunteer_logs', [
            'scholar_id' => $scholar->id,
            'event_id' => $event->id,
            'duty_title' => 'Youth Sports Fest Marshall',
            'time_out' => null,
            'hours_rendered' => 0.00,
        ]);

        $this->assertDatabaseHas('event_attendances', [
            'user_id' => $scholarUser->id,
            'event_id' => $event->id,
            'status' => 'timed_in',
        ]);
    }

    public function test_scholar_scan_2_records_time_out_and_calculates_hours(): void
    {
        $admin = User::factory()->admin()->active()->create();
        $scholarUser = User::factory()->youth()->active()->create([
            'name' => 'Maria Santos',
            'qr_code_token' => (string) Str::uuid(),
        ]);

        $program = EcesproProgram::create([
            'title' => 'ECESPRO Tertiary 2026',
            'school_year' => '2026-2027',
            'start_date' => '2026-08-01',
            'end_date' => '2026-12-31',
            'status' => 'Open',
        ]);

        $app = EcesproApplication::create([
            'user_id' => $scholarUser->id,
            'ecespro_program_id' => $program->id,
            'application_status' => 'Approved',
        ]);

        $scholar = EcesproScholar::create([
            'user_id' => $scholarUser->id,
            'ecespro_application_id' => $app->id,
            'scholar_no' => 'SCH-0001',
            'status' => 'Active',
            'required_volunteer_hours' => 30.00,
            'total_rendered_hours' => 10.00,
            'is_volunteer_completed' => false,
        ]);

        EcesproVolunteerLog::create([
            'scholar_id' => $scholar->id,
            'activity_type' => 'office_duty',
            'duty_title' => 'Prior Duty',
            'time_in' => Carbon::now()->subDays(2),
            'time_out' => Carbon::now()->subDays(2)->addHours(10),
            'hours_rendered' => 10.00,
            'semester_period' => '2026-1st-Sem',
            'verified_by_user_id' => $admin->id,
        ]);

        $timeIn = Carbon::now()->subHours(4);
        $log = EcesproVolunteerLog::create([
            'scholar_id' => $scholar->id,
            'activity_type' => 'office_duty',
            'duty_title' => 'TCYDO Front Desk Assistance',
            'time_in' => $timeIn,
            'time_out' => null,
            'hours_rendered' => 0.00,
            'semester_period' => '2026-1st-Sem',
            'verified_by_user_id' => $admin->id,
        ]);

        EventAttendance::create([
            'user_id' => $scholarUser->id,
            'time_in' => $timeIn,
            'status' => 'timed_in',
            'scanned_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/scanner/record-scan', [
                'qr_code_token' => $scholarUser->qr_code_token,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_scholar' => true,
                'scan_type' => 'time_out',
                'attendee_name' => 'Maria Santos',
                'role' => 'Scholar',
                'status' => 'timed_out',
                'hours_rendered' => 4.0,
                'total_rendered_hours' => 14.0,
                'required_volunteer_hours' => 30.0,
                'is_volunteer_completed' => false,
            ]);

        $log->refresh();
        $this->assertNotNull($log->time_out);
        $this->assertEquals(4.00, (float) $log->hours_rendered);

        $scholar->refresh();
        $this->assertEquals(14.00, (float) $scholar->total_rendered_hours);
        $this->assertFalse($scholar->is_volunteer_completed);
    }

    public function test_scholar_completes_volunteer_hours_requirement(): void
    {
        $admin = User::factory()->admin()->active()->create();
        $scholarUser = User::factory()->youth()->active()->create([
            'name' => 'Maria Santos',
            'qr_code_token' => (string) Str::uuid(),
        ]);

        $program = EcesproProgram::create([
            'title' => 'ECESPRO Tertiary 2026',
            'school_year' => '2026-2027',
            'start_date' => '2026-08-01',
            'end_date' => '2026-12-31',
            'status' => 'Open',
        ]);

        $app = EcesproApplication::create([
            'user_id' => $scholarUser->id,
            'ecespro_program_id' => $program->id,
            'application_status' => 'Approved',
        ]);

        $scholar = EcesproScholar::create([
            'user_id' => $scholarUser->id,
            'ecespro_application_id' => $app->id,
            'scholar_no' => 'SCH-0001',
            'status' => 'Active',
            'required_volunteer_hours' => 30.00,
            'total_rendered_hours' => 28.00,
            'is_volunteer_completed' => false,
        ]);

        EcesproVolunteerLog::create([
            'scholar_id' => $scholar->id,
            'activity_type' => 'community_service',
            'duty_title' => 'Prior Duties',
            'time_in' => Carbon::now()->subDays(5),
            'time_out' => Carbon::now()->subDays(5)->addHours(28),
            'hours_rendered' => 28.00,
            'semester_period' => '2026-1st-Sem',
            'verified_by_user_id' => $admin->id,
        ]);

        $timeIn = Carbon::now()->subHours(4);
        EcesproVolunteerLog::create([
            'scholar_id' => $scholar->id,
            'activity_type' => 'community_service',
            'duty_title' => 'Tree Planting Activity',
            'time_in' => $timeIn,
            'time_out' => null,
            'hours_rendered' => 0.00,
            'semester_period' => '2026-1st-Sem',
            'verified_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/scanner/record-scan', [
                'qr_code_token' => $scholarUser->qr_code_token,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_scholar' => true,
                'scan_type' => 'time_out',
                'hours_rendered' => 4.0,
                'total_rendered_hours' => 32.0,
                'is_volunteer_completed' => true,
            ]);

        $scholar->refresh();
        $this->assertTrue($scholar->is_volunteer_completed);
        $this->assertEquals(32.00, (float) $scholar->total_rendered_hours);
    }

    public function test_scan_with_invalid_qr_token_returns_404(): void
    {
        $admin = User::factory()->admin()->active()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/scanner/record-scan', [
                'qr_code_token' => 'invalid-token-12345',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'User not found for this QR code.',
            ]);
    }

    public function test_unauthenticated_or_unauthorized_user_cannot_scan(): void
    {
        $youth = User::factory()->youth()->active()->create();

        $this->postJson('/api/scanner/record-scan', [
            'qr_code_token' => 'any-token',
        ])->assertStatus(401);

        $this->actingAs($youth, 'sanctum')
            ->postJson('/api/scanner/record-scan', [
                'qr_code_token' => 'any-token',
            ])->assertStatus(403);
    }

    public function test_youth_can_view_volunteer_hours_tracker(): void
    {
        $scholarUser = User::factory()->youth()->active()->create();

        $program = EcesproProgram::create([
            'title' => 'ECESPRO Tertiary 2026',
            'school_year' => '2026-2027',
            'start_date' => '2026-08-01',
            'end_date' => '2026-12-31',
            'status' => 'Open',
        ]);

        $app = EcesproApplication::create([
            'user_id' => $scholarUser->id,
            'ecespro_program_id' => $program->id,
            'application_status' => 'Approved',
        ]);

        $scholar = EcesproScholar::create([
            'user_id' => $scholarUser->id,
            'ecespro_application_id' => $app->id,
            'scholar_no' => 'SCH-0001',
            'status' => 'Active',
            'required_volunteer_hours' => 30.00,
            'total_rendered_hours' => 15.00,
            'is_volunteer_completed' => false,
        ]);

        EcesproVolunteerLog::create([
            'scholar_id' => $scholar->id,
            'activity_type' => 'office_duty',
            'duty_title' => 'Office Assistance',
            'time_in' => now()->subHours(5),
            'time_out' => now()->subHours(2),
            'hours_rendered' => 3.00,
            'semester_period' => '2026-1st-Sem',
        ]);

        $response = $this->actingAs($scholarUser, 'sanctum')
            ->getJson('/api/youth/ecespro/volunteer-hours');

        $response->assertStatus(200)
            ->assertJson([
                'is_scholar' => true,
                'scholar_no' => 'SCH-0001',
                'required_volunteer_hours' => 30.0,
                'total_rendered_hours' => 15.0,
                'remaining_hours' => 15.0,
                'progress_percentage' => 50.0,
                'is_volunteer_completed' => false,
            ])
            ->assertJsonCount(1, 'logs');
    }

    public function test_admin_can_manage_scholar_volunteer_logs(): void
    {
        $admin = User::factory()->admin()->active()->create();
        $scholarUser = User::factory()->youth()->active()->create();

        $program = EcesproProgram::create([
            'title' => 'ECESPRO Tertiary 2026',
            'school_year' => '2026-2027',
            'start_date' => '2026-08-01',
            'end_date' => '2026-12-31',
            'status' => 'Open',
        ]);

        $app = EcesproApplication::create([
            'user_id' => $scholarUser->id,
            'ecespro_program_id' => $program->id,
            'application_status' => 'Approved',
        ]);

        $scholar = EcesproScholar::create([
            'user_id' => $scholarUser->id,
            'ecespro_application_id' => $app->id,
            'scholar_no' => 'SCH-0001',
            'status' => 'Active',
            'required_volunteer_hours' => 30.00,
            'total_rendered_hours' => 0.00,
            'is_volunteer_completed' => false,
        ]);

        $createResponse = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/ecespro-scholars/{$scholar->id}/volunteer-logs", [
                'activity_type' => 'community_service',
                'duty_title' => 'Barangay Clean-up Drive',
                'hours_rendered' => 5.5,
                'semester_period' => '2026-1st-Sem',
                'remarks' => 'Completed full morning shift.',
            ]);

        $createResponse->assertStatus(201)
            ->assertJson([
                'message' => 'Volunteer log added successfully.',
                'scholar' => [
                    'total_rendered_hours' => 5.5,
                ],
            ]);

        $logId = $createResponse->json('log.id');

        $getResponse = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/ecespro-scholars/{$scholar->id}/volunteer-logs");

        $getResponse->assertStatus(200)
            ->assertJson([
                'scholar_id' => $scholar->id,
                'total_rendered_hours' => 5.5,
            ])
            ->assertJsonCount(1, 'logs');

        $deleteResponse = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/ecespro-scholars/{$scholar->id}/volunteer-logs/{$logId}/delete");

        $deleteResponse->assertStatus(200)
            ->assertJson([
                'message' => 'Volunteer log deleted successfully.',
                'scholar' => [
                    'total_rendered_hours' => 0.0,
                ],
            ]);

        $this->assertDatabaseMissing('ecespro_volunteer_logs', ['id' => $logId]);
    }
}

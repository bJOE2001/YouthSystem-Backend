<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\EcesproApplication;
use App\Models\EcesproProgram;
use App\Models\EcesproScholar;
use App\Models\EcesproVolunteerLog;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\SkOfficial;
use App\Models\SportsProgram;
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

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $youth->id,
            'event_id' => $event->id,
            'status' => 'attended',
            'activity_type' => 'event',
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

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $scholarUser->id,
            'event_id' => $event->id,
            'status' => 'timed_in',
            'activity_type' => 'event',
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

    public function test_sports_program_scan_records_attendance_log(): void
    {
        $admin = User::factory()->admin()->active()->create();
        $youth = User::factory()->youth()->active()->create([
            'name' => 'Basketball Player',
            'qr_code_token' => (string) Str::uuid(),
        ]);

        $sports = SportsProgram::create([
            'name' => 'Inter-Barangay Summer League 2026',
            'type' => 'Basketball',
            'strategic_direction' => 'Sports Development',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'start_time' => '09:00:00',
            'location' => 'Tagum City Gymnasium',
            'status' => 'ongoing',
            'user_id' => $admin->id,
            'open_to_all_barangays' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/scanner/record-scan', [
                'qr_code_token' => $youth->qr_code_token,
                'event_id' => "sport_{$sports->id}",
                'activity_type' => 'sports',
                'duty_title' => 'Inter-Barangay Summer League 2026',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_scholar' => false,
                'activity_type' => 'sports',
                'status' => 'attended',
            ]);

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $youth->id,
            'sports_program_id' => $sports->id,
            'activity_type' => 'sports',
            'activity_title' => 'Inter-Barangay Summer League 2026',
            'status' => 'attended',
        ]);
    }

    public function test_office_duty_scan_records_attendance_log(): void
    {
        $admin = User::factory()->admin()->active()->create();
        $youth = User::factory()->youth()->active()->create([
            'name' => 'Volunteer Staff',
            'qr_code_token' => (string) Str::uuid(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/scanner/record-scan', [
                'qr_code_token' => $youth->qr_code_token,
                'activity_type' => 'office_duty',
                'duty_title' => 'TCYDO Front Desk Duty',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_scholar' => false,
                'activity_type' => 'office_duty',
                'status' => 'attended',
            ]);

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $youth->id,
            'event_id' => null,
            'sports_program_id' => null,
            'activity_type' => 'office_duty',
            'activity_title' => 'TCYDO Front Desk Duty',
            'status' => 'attended',
        ]);
    }

    public function test_admin_receives_city_level_scanner_activities(): void
    {
        $admin = User::factory()->admin()->active()->create();
        $skUser = User::factory()->skAdmin()->active()->create();

        $adminEvent = Event::create([
            'name' => 'City Leadership Forum 2026',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'user_id' => $admin->id,
            'status' => 'ongoing',
        ]);

        $skEvent = Event::create([
            'name' => 'Barangay SK Internal Assembly',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'user_id' => $skUser->id,
            'status' => 'ongoing',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/scanner/activities');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('role', 'admin')
            ->assertJsonFragment([
                'duty_title' => 'TCYDO In-Office Duty',
                'group' => 'TCYDO Volunteer Duties',
            ])
            ->assertJsonFragment([
                'duty_title' => 'City Leadership Forum 2026',
                'group' => 'Official Events',
            ]);

        $titles = collect($response->json('data'))->pluck('duty_title');
        $this->assertFalse($titles->contains('Barangay SK Internal Assembly'));
    }

    public function test_sk_admin_receives_barangay_specific_scanner_activities(): void
    {
        $skUser = User::factory()->skAdmin()->active()->create();
        SkOfficial::create([
            'user_id' => $skUser->id,
            'name' => 'SK Official Apokon',
            'barangay' => 'Apokon',
            'position' => 'SK Chairperson',
            'committee' => 'Committee on Active Citizenship',
            'term' => '2023 - 2025',
            'responsibilities' => 'Council leadership',
        ]);

        $event = Event::create([
            'name' => 'Apokon Linggo ng Kabataan 2026',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'user_id' => $skUser->id,
            'status' => 'ongoing',
        ]);

        $sports = SportsProgram::create([
            'name' => 'Apokon Barangay Volleyball Cup',
            'type' => 'Volleyball',
            'strategic_direction' => 'Sports',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'status' => 'ongoing',
            'user_id' => $skUser->id,
            'barangay' => 'Apokon',
            'open_to_all_barangays' => false,
        ]);

        $cityAdmin = User::factory()->admin()->active()->create();
        $cityEvent = Event::create([
            'name' => 'City-Wide Youth Summit 2026',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'user_id' => $cityAdmin->id,
            'status' => 'ongoing',
        ]);
        $citySports = SportsProgram::create([
            'name' => 'City-Wide Inter-Barangay Olympics',
            'type' => 'Basketball',
            'strategic_direction' => 'Sports',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'status' => 'ongoing',
            'user_id' => $cityAdmin->id,
            'open_to_all_barangays' => true,
        ]);

        $response = $this->actingAs($skUser, 'sanctum')
            ->getJson('/api/scanner/activities');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('role', 'sk_admin')
            ->assertJsonPath('barangay', 'Apokon')
            ->assertJsonFragment([
                'duty_title' => 'Apokon Linggo ng Kabataan 2026',
                'group' => 'Barangay Events',
            ])
            ->assertJsonFragment([
                'duty_title' => 'Apokon Barangay Volleyball Cup',
                'group' => 'Barangay Sports Programs',
            ]);

        // Generic office duties and city-level events/sports must NOT be present for SK Admins
        $activityTitles = collect($response->json('data'))->pluck('duty_title');
        $this->assertFalse($activityTitles->contains('TCYDO In-Office Duty'));
        $this->assertFalse($activityTitles->contains('City-Wide Youth Summit 2026'));
        $this->assertFalse($activityTitles->contains('City-Wide Inter-Barangay Olympics'));
    }

    public function test_operator_can_fetch_recent_scanner_logs(): void
    {
        $admin = User::factory()->admin()->active()->create();
        $youth = User::factory()->active()->create(['name' => 'Maria Santos']);

        AttendanceLog::create([
            'user_id' => $youth->id,
            'activity_type' => 'office_duty',
            'activity_title' => 'TCYDO In-Office Duty',
            'time_in' => now()->subMinutes(45),
            'time_out' => now(),
            'status' => 'timed_out',
            'scanned_by_user_id' => $admin->id,
            'remarks' => 'Completed office duty',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/scanner/recent-logs');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'attendee_name' => 'Maria Santos',
                'duty_title' => 'TCYDO In-Office Duty',
                'scan_type' => 'time_out',
                'status' => 'timed_out',
            ]);
    }
}

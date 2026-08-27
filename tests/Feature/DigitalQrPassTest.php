<?php

namespace Tests\Feature;

use App\Http\Resources\SkAdmin\SkOfficialResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\YouthProfileResource;
use App\Models\EcesproApplication;
use App\Models\EcesproProgram;
use App\Models\EcesproScholar;
use App\Models\Event;
use App\Models\SkOfficial;
use App\Models\User;
use App\Models\YouthProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalQrPassTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_youth_can_fetch_qr_pass(): void
    {
        $user = User::factory()->create([
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'role' => 'youth',
            'status' => 'active',
        ]);

        YouthProfile::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'barangay' => 'Apokon',
            'profile_picture' => 'https://example.com/photos/maria.jpg',
        ]);

        $response = $this->actingAs($user)->getJson('/api/user/qr-pass');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Maria Santos')
            ->assertJsonPath('data.email', 'maria@example.com')
            ->assertJsonPath('data.role', 'youth')
            ->assertJsonPath('data.role_label', 'Registered Youth')
            ->assertJsonPath('data.is_scholar', false)
            ->assertJsonPath('data.barangay', 'Apokon')
            ->assertJsonPath('data.profile_picture', 'https://example.com/photos/maria.jpg');

        $this->assertNotEmpty($response->json('data.qr_code_token'));

        // Also test alias route
        $aliasResponse = $this->actingAs($user)->getJson('/api/youth/qr-pass');
        $aliasResponse->assertOk()
            ->assertJsonPath('data.name', 'Maria Santos');
    }

    public function test_authenticated_scholar_receives_scholar_pass_metadata(): void
    {
        $user = User::factory()->create([
            'name' => 'Juan Scholar',
            'email' => 'juan.scholar@example.com',
            'role' => 'youth',
            'status' => 'active',
        ]);

        YouthProfile::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'Juan',
            'last_name' => 'Scholar',
            'barangay' => 'Mankilam',
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
            'first_name' => 'Juan',
            'last_name' => 'Scholar',
            'application_status' => 'Approved',
        ]);

        EcesproScholar::create([
            'user_id' => $user->id,
            'ecespro_application_id' => $app->id,
            'scholar_no' => 'SCH-9988',
            'school' => 'Tagum City College',
            'course' => 'BSCS',
            'status' => 'Active',
            'compliance_status' => 'Compliant',
        ]);

        $response = $this->actingAs($user)->getJson('/api/user/qr-pass');

        $response->assertOk()
            ->assertJsonPath('data.role_label', 'Registered Youth')
            ->assertJsonPath('data.is_scholar', true)
            ->assertJsonPath('data.scholar_no', 'SCH-9988')
            ->assertJsonPath('data.barangay', 'Mankilam');
    }

    public function test_authenticated_sk_official_receives_sk_pass_metadata(): void
    {
        $user = User::factory()->create([
            'name' => 'Hon. Alex Reyes',
            'email' => 'alex.reyes@tagumcity.gov.ph',
            'role' => 'sk_admin',
            'status' => 'active',
        ]);

        YouthProfile::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'Alex',
            'last_name' => 'Reyes',
            'barangay' => 'Magugpo North',
            'profile_picture' => 'https://example.com/photos/alex.png',
        ]);

        SkOfficial::create([
            'name' => 'Hon. Alex Reyes',
            'email' => 'alex.reyes@tagumcity.gov.ph',
            'barangay' => 'Magugpo North',
            'position' => 'SK Chairperson',
            'committee' => 'Committee on Youth & Education',
            'term' => '2023 - 2025',
        ]);

        $response = $this->actingAs($user)->getJson('/api/user/qr-pass');

        $response->assertOk()
            ->assertJsonPath('data.role', 'sk_admin')
            ->assertJsonPath('data.role_label', 'SK Chairperson')
            ->assertJsonPath('data.position', 'SK Chairperson')
            ->assertJsonPath('data.committee', 'Committee on Youth & Education')
            ->assertJsonPath('data.term', '2023 - 2025')
            ->assertJsonPath('data.barangay', 'Magugpo North');

        // Test SK alias route
        $aliasResponse = $this->actingAs($user)->getJson('/api/sk/qr-pass');
        $aliasResponse->assertOk()
            ->assertJsonPath('data.role_label', 'SK Chairperson');
    }

    public function test_user_can_regenerate_qr_token(): void
    {
        $user = User::factory()->create([
            'role' => 'youth',
            'status' => 'active',
        ]);

        $oldToken = $user->ensureQrToken();
        $this->assertNotEmpty($oldToken);

        $response = $this->actingAs($user)->postJson('/api/user/qr-pass/regenerate');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $newToken = $response->json('data.qr_code_token');
        $this->assertNotEmpty($newToken);
        $this->assertNotEquals($oldToken, $newToken);

        $user->refresh();
        $this->assertEquals($newToken, $user->qr_code_token);
    }

    public function test_resources_expose_qr_code_token(): void
    {
        $user = User::factory()->create([
            'name' => 'Sample User',
            'email' => 'sample@example.com',
            'role' => 'youth',
            'status' => 'active',
        ]);

        $profile = YouthProfile::factory()->create([
            'user_id' => $user->id,
            'barangay' => 'Visayan Village',
        ]);

        $skOfficial = SkOfficial::create([
            'name' => 'Sample User',
            'email' => 'sample@example.com',
            'barangay' => 'Visayan Village',
            'position' => 'SK Councilor',
        ]);

        // UserResource
        $userArray = (new UserResource($user))->toArray(request());
        $this->assertArrayHasKey('qr_code_token', $userArray);
        $this->assertEquals($user->qr_code_token, $userArray['qr_code_token']);

        // YouthProfileResource
        $profileArray = (new YouthProfileResource($profile))->toArray(request());
        $this->assertArrayHasKey('qr_code_token', $profileArray);
        $this->assertEquals($user->qr_code_token, $profileArray['qr_code_token']);

        // SkOfficialResource
        $officialArray = (new SkOfficialResource($skOfficial))->toArray(request());
        $this->assertArrayHasKey('qr_code_token', $officialArray);
        $this->assertEquals($user->qr_code_token, $officialArray['qr_code_token']);
    }

    public function test_scanner_controller_scans_attendee_via_qr_token(): void
    {
        $scannerOfficial = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $attendee = User::factory()->create(['name' => 'Event Attendee', 'role' => 'youth', 'status' => 'active']);
        $attendeeToken = $attendee->ensureQrToken();

        $event = Event::create([
            'name' => 'Tagum Youth Leadership Summit',
            'status' => 'published',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
        ]);

        $response = $this->actingAs($scannerOfficial)->postJson('/api/scanner/scan', [
            'qr_code_token' => $attendeeToken,
            'event_id' => $event->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attendee_name', 'Event Attendee')
            ->assertJsonPath('status', 'attended');

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $attendee->id,
            'event_id' => $event->id,
            'status' => 'attended',
        ]);
    }
}

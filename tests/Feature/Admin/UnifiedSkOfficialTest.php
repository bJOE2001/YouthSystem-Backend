<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\YouthProfileStatus;
use App\Models\Barangay;
use App\Models\SkOfficial;
use App\Models\User;
use App\Models\YouthProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedSkOfficialTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->active()->create([
            'email' => 'admin@test.com',
        ]);
    }

    public function test_can_list_eligible_youths_filtered_by_barangay(): void
    {
        $apokon = Barangay::firstOrCreate(['name' => 'Apokon']);
        $magugpo = Barangay::firstOrCreate(['name' => 'Magugpo Poblacion']);

        // Eligible youth in Apokon
        $youthUser1 = User::factory()->youth()->active()->create(['email' => 'youth1@test.com']);
        $profile1 = YouthProfile::factory()->create([
            'user_id' => $youthUser1->id,
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'barangay' => 'Apokon',
            'status' => YouthProfileStatus::Approved,
        ]);

        // Eligible youth in Magugpo
        $youthUser2 = User::factory()->youth()->active()->create(['email' => 'youth2@test.com']);
        YouthProfile::factory()->create([
            'user_id' => $youthUser2->id,
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'barangay' => 'Magugpo Poblacion',
            'status' => YouthProfileStatus::Approved,
        ]);

        // Already an SK official - should NOT be eligible
        $skUser = User::factory()->skAdmin()->active()->create(['email' => 'sk@test.com']);
        YouthProfile::factory()->create([
            'user_id' => $skUser->id,
            'first_name' => 'Pedro',
            'last_name' => 'Penduko',
            'barangay' => 'Apokon',
            'status' => YouthProfileStatus::Approved,
        ]);
        SkOfficial::factory()->create([
            'user_id' => $skUser->id,
            'name' => 'Pedro Penduko',
            'email' => 'sk@test.com',
            'barangay' => 'Apokon',
        ]);

        // Query eligible youths in Apokon
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/sk-officials/eligible-youths?barangay=Apokon');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($youthUser1->id, $data[0]['user_id']);
        $this->assertEquals('Apokon', $data[0]['barangay']);
    }

    public function test_admin_can_appoint_eligible_youth_as_sk_official_and_promotes_role(): void
    {
        $youthUser = User::factory()->youth()->active()->create([
            'name' => 'Carlos Yulo',
            'email' => 'carlos@test.com',
        ]);

        $profile = YouthProfile::factory()->create([
            'user_id' => $youthUser->id,
            'first_name' => 'Carlos',
            'middle_name' => null,
            'last_name' => 'Yulo',
            'suffix' => null,
            'barangay' => 'Apokon',
            'mobile_number' => '09123456789',
            'status' => YouthProfileStatus::Approved,
        ]);

        $payload = [
            'user_id' => $youthUser->id,
            'position' => 'SK Chairperson',
            'committee' => 'Committee on Sports',
            'term' => '2023 - 2025',
            'responsibilities' => 'Leading the barangay youth sports and development council.',
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/sk-officials', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Carlos Yulo')
            ->assertJsonPath('data.position', 'SK Chairperson')
            ->assertJsonPath('data.barangay', 'Apokon')
            ->assertJsonPath('data.user_id', $youthUser->id)
            ->assertJsonPath('data.has_youth_profile', true);

        // Check user role got promoted to sk_admin
        $youthUser->refresh();
        $this->assertEquals(UserRole::SkAdmin, $youthUser->role);

        // Check official record in DB
        $this->assertDatabaseHas('sk_officials', [
            'user_id' => $youthUser->id,
            'name' => 'Carlos Yulo',
            'position' => 'SK Chairperson',
            'barangay' => 'Apokon',
        ]);
    }

    public function test_deleting_sk_official_demotes_user_to_youth_while_preserving_youth_profile(): void
    {
        $user = User::factory()->skAdmin()->active()->create(['email' => 'official@test.com']);
        $profile = YouthProfile::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'Official',
            'last_name' => 'Member',
            'barangay' => 'Apokon',
            'status' => YouthProfileStatus::Approved,
        ]);

        $official = SkOfficial::factory()->create([
            'user_id' => $user->id,
            'name' => 'Official Member',
            'email' => 'official@test.com',
            'barangay' => 'Apokon',
            'position' => 'SK Kagawad',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/sk-officials/{$official->id}/delete");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'SK Official removed successfully.',
            ]);

        // Official record deleted
        $this->assertDatabaseMissing('sk_officials', [
            'id' => $official->id,
        ]);

        // User role demoted back to youth
        $user->refresh();
        $this->assertEquals(UserRole::Youth, $user->role);

        // Underlying YouthProfile remains fully intact
        $this->assertDatabaseHas('youth_profiles', [
            'id' => $profile->id,
            'user_id' => $user->id,
            'status' => YouthProfileStatus::Approved->value,
        ]);
    }

    public function test_sk_official_resource_includes_photo_and_personal_demographic_information(): void
    {
        $user = User::factory()->skAdmin()->active()->create(['email' => 'sk_details@test.com']);
        YouthProfile::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'gender' => 'Female',
            'birth_date' => '2001-08-15',
            'barangay' => 'Canocotan',
            'purok_sitio' => 'Purok Mangga',
            'educational_attainment' => 'College Undergraduate',
            'course_strand' => 'BS Information Technology',
            'mobile_number' => '09998887777',
            'status' => YouthProfileStatus::Approved,
        ]);

        $official = SkOfficial::factory()->create([
            'user_id' => $user->id,
            'name' => 'Ana Reyes',
            'email' => 'sk_details@test.com',
            'barangay' => 'Canocotan',
            'position' => 'SK Secretary',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/sk-officials/{$official->id}");

        $response->assertOk()
            ->assertJsonPath('id', $official->id)
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonPath('name', 'Ana Reyes')
            ->assertJsonPath('position', 'SK Secretary')
            ->assertJsonPath('has_youth_profile', true)
            ->assertJsonPath('youth_profile.gender', 'Female')
            ->assertJsonPath('youth_profile.educational_attainment', 'College Undergraduate')
            ->assertJsonPath('youth_profile.course_strand', 'BS Information Technology')
            ->assertJsonPath('youth_profile.purok_sitio', 'Purok Mangga');
    }

    public function test_qr_pass_endpoint_resolves_linked_sk_official_data(): void
    {
        $user = User::factory()->skAdmin()->active()->create([
            'name' => 'Hon. Alex Santos',
            'email' => 'alex_chair@test.com',
        ]);

        YouthProfile::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'Alex',
            'last_name' => 'Santos',
            'barangay' => 'Busaon',
            'status' => YouthProfileStatus::Approved,
        ]);

        SkOfficial::factory()->create([
            'user_id' => $user->id,
            'name' => 'Hon. Alex Santos',
            'email' => 'alex_chair@test.com',
            'position' => 'SK Chairperson',
            'committee' => 'Committee on Governance',
            'barangay' => 'Busaon',
            'term' => '2023 - 2025',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/user/qr-pass');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role', 'sk_admin')
            ->assertJsonPath('data.position', 'SK Chairperson')
            ->assertJsonPath('data.role_label', 'SK Chairperson')
            ->assertJsonPath('data.committee', 'Committee on Governance')
            ->assertJsonPath('data.barangay', 'Busaon')
            ->assertJsonPath('data.term', '2023 - 2025');
    }
}

<?php

namespace Tests\Feature\YouthProfile;

use App\Enums\YouthProfileStatus;
use App\Models\User;
use App\Models\YouthProfile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class YouthProfileApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_youth_user_can_create_a_profile_with_a_private_identification_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->youth()->active()->create();

        $response = $this
            ->actingAs($user, 'web')
            ->post('/api/youth/profile', $this->validPayload(), [
                'Accept' => 'application/json',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.first_name', 'Juan')
            ->assertJsonPath('data.status', YouthProfileStatus::Pending->value)
            ->assertJsonPath('data.has_attached_id', true)
            ->assertJsonMissingPath('data.attached_id_path');

        $youthProfile = $user->youthProfile()->firstOrFail();

        $this->assertModelExists($youthProfile);
        Storage::disk('local')->assertExists($youthProfile->attached_id_path);
    }

    public function test_profile_creation_validates_required_fields(): void
    {
        Storage::fake('local');

        $user = User::factory()->youth()->active()->create();

        $response = $this
            ->actingAs($user, 'web')
            ->postJson('/api/youth/profile');

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'gender',
                'birth_date',
                'attached_id',
                'barangay',
            ]);

        $this->assertFalse($user->youthProfile()->exists());
    }

    public function test_profile_creation_rejects_unsupported_identification_files(): void
    {
        Storage::fake('local');

        $user = User::factory()->youth()->active()->create();
        $payload = $this->validPayload();
        $payload['attached_id'] = UploadedFile::fake()->create(
            'identification.svg',
            20,
            'image/svg+xml',
        );

        $this
            ->actingAs($user, 'web')
            ->post('/api/youth/profile', $payload, ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['attached_id']);
    }

    public function test_youth_user_can_view_their_profile_without_exposing_the_file_path(): void
    {
        $user = User::factory()->youth()->active()->create();
        $youthProfile = YouthProfile::factory()->for($user)->create([
            'attached_id_path' => 'youth-identification/private-id.jpg',
        ]);

        $response = $this
            ->actingAs($user, 'web')
            ->getJson('/api/youth/profile');

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $youthProfile->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.has_attached_id', true)
            ->assertJsonMissingPath('data.attached_id_path');
    }

    public function test_missing_profile_returns_not_found(): void
    {
        $user = User::factory()->youth()->active()->create();

        $this
            ->actingAs($user, 'web')
            ->getJson('/api/youth/profile')
            ->assertNotFound();
    }

    public function test_youth_user_cannot_create_a_second_profile(): void
    {
        Storage::fake('local');

        $user = User::factory()->youth()->active()->create();
        YouthProfile::factory()->for($user)->create();

        $this
            ->actingAs($user, 'web')
            ->post('/api/youth/profile', $this->validPayload(), [
                'Accept' => 'application/json',
            ])
            ->assertForbidden();
    }

    public function test_updating_a_reviewed_profile_replaces_the_id_and_resets_review_status(): void
    {
        Storage::fake('local');

        $user = User::factory()->youth()->active()->create();
        $oldAttachedIdPath = 'youth-identification/old-id.jpg';
        Storage::disk('local')->put($oldAttachedIdPath, 'old identification');

        $youthProfile = YouthProfile::factory()->approved()->for($user)->create([
            'attached_id_path' => $oldAttachedIdPath,
            'reviewed_by' => User::factory()->youthAdmin()->active(),
        ]);

        $response = $this
            ->actingAs($user, 'web')
            ->post('/api/youth/profile', [
                '_method' => 'PATCH',
                'mobile_number' => '09171234568',
                'attached_id' => UploadedFile::fake()->image('new-id.jpg'),
            ], [
                'Accept' => 'application/json',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.mobile_number', '09171234568')
            ->assertJsonPath('data.status', YouthProfileStatus::Pending->value)
            ->assertJsonPath('data.reviewed_at', null);

        $youthProfile->refresh();

        $this->assertNull($youthProfile->reviewed_by);
        $this->assertNull($youthProfile->rejection_reason);
        Storage::disk('local')->assertMissing($oldAttachedIdPath);
        Storage::disk('local')->assertExists($youthProfile->attached_id_path);
    }

    public function test_partial_update_keeps_the_existing_identification_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->youth()->active()->create();
        $attachedIdPath = 'youth-identification/current-id.jpg';
        Storage::disk('local')->put($attachedIdPath, 'current identification');

        $youthProfile = YouthProfile::factory()->for($user)->create([
            'attached_id_path' => $attachedIdPath,
        ]);

        $this
            ->actingAs($user, 'web')
            ->patchJson('/api/youth/profile', [
                'barangay' => 'Apokon',
            ])
            ->assertOk()
            ->assertJsonPath('data.barangay', 'Apokon');

        $this->assertSame($attachedIdPath, $youthProfile->fresh()->attached_id_path);
        Storage::disk('local')->assertExists($attachedIdPath);
    }

    public function test_non_youth_and_inactive_accounts_cannot_access_youth_profile_routes(): void
    {
        $youthAdmin = User::factory()->youthAdmin()->active()->create();
        $pendingYouth = User::factory()->youth()->pending()->create();

        $this
            ->actingAs($youthAdmin, 'web')
            ->getJson('/api/youth/profile')
            ->assertForbidden();

        $this
            ->actingAs($pendingYouth, 'web')
            ->getJson('/api/youth/profile')
            ->assertForbidden();
    }

    public function test_guest_cannot_access_youth_profile_routes(): void
    {
        $this->getJson('/api/youth/profile')->assertUnauthorized();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'suffix' => null,
            'gender' => 'Male',
            'birth_date' => '2004-06-15',
            'place_of_birth' => 'Tagum City',
            'mobile_number' => '09171234567',
            'currently_attending_school' => true,
            'senior_high_graduate' => true,
            'educational_attainment' => 'College Level',
            'course_strand' => 'Information Technology',
            'ethnicity' => 'Cebuano',
            'religious_affiliation' => 'Roman Catholic',
            'has_disability' => false,
            'overseas_worker' => false,
            'lgbtq_member' => false,
            'special_youth_sector' => 'None',
            'attached_id' => UploadedFile::fake()->image('identification.jpg'),
            'birth_registered' => true,
            'civil_status' => 'Single',
            'solo_parent' => false,
            'barangay' => 'Mankilam',
            'purok_sitio' => 'Purok 1',
            'city' => 'Tagum City',
            'province' => 'Davao del Norte',
            'postal_code' => '8100',
        ];
    }
}

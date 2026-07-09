<?php

namespace Tests\Feature\Api\SkAdmin;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\YouthProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResidentYouthControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $skAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skAdmin = User::factory()->skAdmin()->active()->create();
    }

    #[Test]
    public function it_can_list_resident_youth_records()
    {
        $youth = User::factory()->youth()->active()->create();
        YouthProfile::factory()->create([
            'user_id' => $youth->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response = $this->actingAs($this->skAdmin)
            ->getJson('/api/sk/resident-youth');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'contact',
                        'email',
                        'purok',
                        'status',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    #[Test]
    public function it_can_show_resident_youth_details()
    {
        $youth = User::factory()->youth()->active()->create();
        $profile = YouthProfile::factory()->create([
            'user_id' => $youth->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'suffix' => null,
        ]);

        $response = $this->actingAs($this->skAdmin)
            ->getJson("/api/sk/resident-youth/{$profile->id}");

        $response->assertOk()
            ->assertJsonPath('firstName', 'John')
            ->assertJsonPath('lastName', 'Doe');
    }

    #[Test]
    public function it_can_store_a_resident_youth_record()
    {
        Storage::fake('local');

        $data = [
            'email' => 'newyouth@example.com',
            'firstName' => 'New',
            'lastName' => 'Youth',
            'gender' => 'Male',
            'birthdate' => '2000-01-01',
            'placeOfBirth' => 'City',
            'mobileNumber' => '09123456789',
            'currentlyAttendingSchool' => 'no',
            'seniorHighGraduate' => 'yes',
            'educationalAttainment' => 'College Level',
            'hasDisability' => 'no',
            'overseasWorker' => 'no',
            'lgbtqMember' => 'no',
            'birthRegistered' => 'yes',
            'civilStatus' => 'Single',
            'soloParent' => 'no',
            'barangay' => 'Barangay 1',
            'purokSitio' => 'Purok 1',
            'city' => 'City',
            'province' => 'Province',
            'zipcode' => '1234',
            'attachedId' => UploadedFile::fake()->image('id.jpg'),
        ];

        $response = $this->actingAs($this->skAdmin)
            ->postJson('/api/sk/resident-youth', $data);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', [
            'email' => 'newyouth@example.com',
            'role' => UserRole::Youth->value,
        ]);

        $this->assertDatabaseHas('youth_profiles', [
            'first_name' => 'New',
            'last_name' => 'Youth',
        ]);
    }

    #[Test]
    public function it_can_update_a_resident_youth_record()
    {
        $youth = User::factory()->youth()->active()->create([
            'email' => 'old@example.com',
        ]);
        $profile = YouthProfile::factory()->create([
            'user_id' => $youth->id,
            'first_name' => 'Old',
            'last_name' => 'Youth',
            'gender' => 'Male',
            'birth_date' => '2000-01-01',
            'place_of_birth' => 'City',
            'mobile_number' => '09123456789',
            'currently_attending_school' => false,
            'senior_high_graduate' => true,
            'educational_attainment' => 'College Level',
            'has_disability' => false,
            'overseas_worker' => false,
            'lgbtq_member' => false,
            'birth_registered' => true,
            'civil_status' => 'Single',
            'solo_parent' => false,
            'barangay' => 'Barangay 1',
            'purok_sitio' => 'Purok 1',
            'city' => 'City',
            'province' => 'Province',
            'postal_code' => '1234',
        ]);

        $data = [
            'email' => 'updated@example.com',
            'firstName' => 'Updated',
            'lastName' => 'Youth',
            'gender' => 'Male',
            'birthdate' => '2000-01-01',
            'placeOfBirth' => 'City',
            'mobileNumber' => '09123456789',
            'currentlyAttendingSchool' => 'no',
            'seniorHighGraduate' => 'yes',
            'educationalAttainment' => 'College Level',
            'hasDisability' => 'no',
            'overseasWorker' => 'no',
            'lgbtqMember' => 'no',
            'birthRegistered' => 'yes',
            'civilStatus' => 'Single',
            'soloParent' => 'no',
            'barangay' => 'Barangay 1',
            'purokSitio' => 'Purok 1',
            'city' => 'City',
            'province' => 'Province',
            'zipcode' => '1234',
        ];

        $response = $this->actingAs($this->skAdmin)
            ->postJson("/api/sk/resident-youth/{$profile->id}", $data);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', [
            'email' => 'updated@example.com',
        ]);

        $this->assertDatabaseHas('youth_profiles', [
            'first_name' => 'Updated',
        ]);
    }

    #[Test]
    public function it_can_delete_a_resident_youth_record()
    {
        $youth = User::factory()->youth()->active()->create();
        $profile = YouthProfile::factory()->create([
            'user_id' => $youth->id,
        ]);

        $response = $this->actingAs($this->skAdmin)
            ->postJson("/api/sk/resident-youth/{$profile->id}/delete");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('youth_profiles', [
            'id' => $profile->id,
        ]);

        $this->assertDatabaseMissing('users', [
            'id' => $youth->id,
        ]);
    }
}

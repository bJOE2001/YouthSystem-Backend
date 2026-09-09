<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\YouthProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_organizations(): void
    {
        $admin = User::factory()->admin()->create();
        Organization::factory()->create(['name' => 'Tagum Youth Leaders']);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/organizations');

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Tagum Youth Leaders'])
            ->assertJsonFragment(['name' => 'SINAG']);
    }

    public function test_admin_can_create_organization(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/organizations', [
            'name' => 'Youth Action Council',
            'status' => 'active',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Youth Action Council');

        $this->assertDatabaseHas('organizations', [
            'name' => 'Youth Action Council',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_organization(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::factory()->create(['name' => 'Old Name', 'status' => 'active']);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/organizations/{$org->id}", [
            'name' => 'Updated Name',
            'status' => 'inactive',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('organizations', [
            'id' => $org->id,
            'name' => 'Updated Name',
            'status' => 'inactive',
        ]);
    }

    public function test_admin_can_delete_organization_and_detach_youth(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::factory()->create();
        $user = User::factory()->youth()->create();
        $profile = YouthProfile::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/organizations/{$org->id}/delete");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('organizations', ['id' => $org->id]);
        $this->assertNull($profile->fresh()->organization_id);
    }

    public function test_sub_admin_with_youth_records_permission_can_manage_organizations(): void
    {
        $subAdmin = User::factory()->subAdmin(['youth_records'])->create();

        Sanctum::actingAs($subAdmin);

        $response = $this->postJson('/api/admin/organizations', [
            'name' => 'Sub Admin Created Org',
            'status' => 'active',
        ]);

        $response->assertCreated();
    }

    public function test_sub_admin_without_youth_records_permission_cannot_access_organizations(): void
    {
        $subAdmin = User::factory()->subAdmin(['events'])->create();

        Sanctum::actingAs($subAdmin);

        $response = $this->getJson('/api/admin/organizations');

        $response->assertForbidden();
    }

    public function test_admin_can_assign_organization_to_youth_profile(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::firstOrCreate(['name' => 'SINAG'], ['status' => 'active']);
        $user = User::factory()->youth()->create();
        $profile = YouthProfile::factory()->create([
            'user_id' => $user->id,
            'organization_id' => null,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/resident-youth/{$profile->id}/organization", [
            'organization_id' => $org->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $freshProfile = $profile->fresh();
        $this->assertEquals($org->id, $freshProfile->organization_id);
    }

    public function test_admin_can_unassign_organization_from_youth_profile(): void
    {
        $admin = User::factory()->admin()->create();
        $org = Organization::firstOrCreate(['name' => 'SINAG'], ['status' => 'active']);
        $user = User::factory()->youth()->create();
        $profile = YouthProfile::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/resident-youth/{$profile->id}/organization", [
            'organization_id' => null,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $freshProfile = $profile->fresh();
        $this->assertNull($freshProfile->organization_id);
    }

    public function test_sub_admin_without_youth_records_permission_cannot_assign_organization(): void
    {
        $subAdmin = User::factory()->subAdmin(['events'])->create();
        $org = Organization::factory()->create();
        $user = User::factory()->youth()->create();
        $profile = YouthProfile::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($subAdmin);

        $response = $this->postJson("/api/admin/resident-youth/{$profile->id}/organization", [
            'organization_id' => $org->id,
        ]);

        $response->assertForbidden();
    }

    public function test_assign_organization_validates_organization_id(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->youth()->create();
        $profile = YouthProfile::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/resident-youth/{$profile->id}/organization", [
            'organization_id' => 999999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['organization_id']);
    }
}

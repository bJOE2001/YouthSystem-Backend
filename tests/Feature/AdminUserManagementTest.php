<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_admin_can_list_and_create_sub_admin(): void
    {
        $admin = User::factory()->admin()->active()->create([
            'email' => 'admin@test.com',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/users');
        $response->assertOk()
            ->assertJsonStructure([
                'users',
                'available_modules',
            ]);

        $createResponse = $this->postJson('/api/admin/users', [
            'name' => 'Sub Admin Events',
            'email' => 'events_subadmin@test.com',
            'password' => 'secret1234',
            'permissions' => ['events', 'sports_programs'],
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('user.email', 'events_subadmin@test.com')
            ->assertJsonPath('user.role', 'sub_admin')
            ->assertJsonPath('user.permissions', ['events', 'sports_programs']);

        $this->assertDatabaseHas('users', [
            'email' => 'events_subadmin@test.com',
            'role' => 'sub_admin',
        ]);
    }

    public function test_primary_admin_can_update_sub_admin(): void
    {
        $admin = User::factory()->admin()->active()->create();
        $subAdmin = User::factory()->subAdmin(['events'])->active()->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/users/{$subAdmin->id}", [
            'name' => 'Updated Sub Admin',
            'email' => $subAdmin->email,
            'permissions' => ['ecespro', 'facilities'],
        ]);

        $response->assertOk()
            ->assertJsonPath('user.name', 'Updated Sub Admin')
            ->assertJsonPath('user.permissions', ['ecespro', 'facilities']);

        $this->assertEquals(['ecespro', 'facilities'], $subAdmin->fresh()->permissions);
    }

    public function test_primary_admin_can_toggle_sub_admin_status_and_delete(): void
    {
        $admin = User::factory()->admin()->active()->create();
        $subAdmin = User::factory()->subAdmin(['events'])->active()->create();

        Sanctum::actingAs($admin);

        // Toggle to Inactive
        $toggleResponse = $this->postJson("/api/admin/users/{$subAdmin->id}/status");
        $toggleResponse->assertOk()
            ->assertJsonPath('status', 'inactive');
        $this->assertEquals(UserStatus::Inactive, $subAdmin->fresh()->status);

        // Toggle back to Active
        $toggleResponse2 = $this->postJson("/api/admin/users/{$subAdmin->id}/status");
        $toggleResponse2->assertOk()
            ->assertJsonPath('status', 'active');
        $this->assertEquals(UserStatus::Active, $subAdmin->fresh()->status);

        // Delete
        $deleteResponse = $this->postJson("/api/admin/users/{$subAdmin->id}/delete");
        $deleteResponse->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $subAdmin->id]);
    }

    public function test_root_admin_is_protected_from_modification_and_deletion(): void
    {
        $admin = User::factory()->admin()->active()->create([
            'email' => 'admin@test.com',
        ]);

        Sanctum::actingAs($admin);

        // Cannot modify root admin
        $this->postJson("/api/admin/users/{$admin->id}", [
            'name' => 'Hacked Name',
            'email' => 'admin@test.com',
            'permissions' => ['events'],
        ])->assertForbidden();

        // Cannot toggle root admin status
        $this->postJson("/api/admin/users/{$admin->id}/status")
            ->assertForbidden();

        // Cannot delete root admin
        $this->postJson("/api/admin/users/{$admin->id}/delete")
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_sub_admin_cannot_access_user_management_or_system_settings(): void
    {
        $subAdmin = User::factory()->subAdmin(['events'])->active()->create();

        Sanctum::actingAs($subAdmin);

        // Cannot list users
        $this->getJson('/api/admin/users')
            ->assertForbidden();

        // Cannot access system settings
        $this->getJson('/api/admin/system-settings/landing-hero')
            ->assertForbidden();
    }

    public function test_sub_admin_can_access_permitted_module_but_not_unauthorized_module(): void
    {
        $subAdmin = User::factory()->subAdmin(['events'])->active()->create();

        Sanctum::actingAs($subAdmin);

        // Events module is permitted (validation failure 422 proves request reached controller, not 403)
        $eventsResponse = $this->postJson('/api/events', []);
        $this->assertNotEquals(403, $eventsResponse->status());

        // Sports Programs module is NOT permitted -> 403 Forbidden
        $sportsResponse = $this->postJson('/api/sports', []);
        $sportsResponse->assertForbidden();
    }

    public function test_sub_admin_with_settings_permission_can_access_that_setting_endpoint(): void
    {
        $subAdmin = User::factory()->subAdmin(['settings_hero', 'settings_ecespro'])->active()->create();

        Sanctum::actingAs($subAdmin);

        // Permitted settings_hero -> 200 OK
        $this->getJson('/api/admin/system-settings/landing-hero')
            ->assertOk();

        // Permitted settings_ecespro -> 200 OK
        $this->getJson('/api/admin/ecespro-settings')
            ->assertOk();

        // Unpermitted settings_contact -> 403 Forbidden
        $this->getJson('/api/admin/system-settings/contact')
            ->assertForbidden();

        // User management is ALWAYS 403 Forbidden
        $this->getJson('/api/admin/users')
            ->assertForbidden();
    }

    public function test_admin_can_change_sub_admin_email_successfully(): void
    {
        $admin = User::factory()->admin()->create();
        $subAdmin = User::factory()->subAdmin(['events'])->create([
            'email' => 'old.email@test.com',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/users/{$subAdmin->id}/email", [
            'email' => 'new.email@test.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Sub-administrator email updated successfully.')
            ->assertJsonPath('user.email', 'new.email@test.com');

        $this->assertDatabaseHas('users', [
            'id' => $subAdmin->id,
            'email' => 'new.email@test.com',
        ]);
    }

    public function test_admin_cannot_change_root_admin_email_via_sub_admin_endpoint(): void
    {
        $admin = User::factory()->admin()->create();
        $rootAdmin = User::factory()->admin()->create([
            'email' => 'admin@test.com',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/users/{$rootAdmin->id}/email", [
            'email' => 'hacked.email@test.com',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('users', [
            'id' => $rootAdmin->id,
            'email' => 'admin@test.com',
        ]);
    }

    public function test_change_email_validates_input(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = User::factory()->subAdmin()->create([
            'email' => 'taken@test.com',
        ]);
        $subAdmin = User::factory()->subAdmin()->create([
            'email' => 'current@test.com',
        ]);

        Sanctum::actingAs($admin);

        // Same email -> fails
        $this->postJson("/api/admin/users/{$subAdmin->id}/email", [
            'email' => 'current@test.com',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Already taken email -> fails
        $this->postJson("/api/admin/users/{$subAdmin->id}/email", [
            'email' => 'taken@test.com',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Invalid format -> fails
        $this->postJson("/api/admin/users/{$subAdmin->id}/email", [
            'email' => 'not-an-email',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_sub_admin_cannot_call_change_email_endpoint(): void
    {
        $subAdmin1 = User::factory()->subAdmin(['events'])->create();
        $subAdmin2 = User::factory()->subAdmin(['events'])->create();

        Sanctum::actingAs($subAdmin1);

        $response = $this->postJson("/api/admin/users/{$subAdmin2->id}/email", [
            'email' => 'unauthorized@test.com',
        ]);

        $response->assertForbidden();
    }
}

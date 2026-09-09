<?php

namespace Tests\Feature;

use App\Models\SkOfficial;
use App\Models\User;
use App\Models\YouthProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminChangeEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_sk_official_email(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->youth()->create(['email' => 'old.sk@test.com']);
        $official = SkOfficial::factory()->create([
            'user_id' => $user->id,
            'email' => 'old.sk@test.com',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/sk-officials/{$official->id}/email", [
            'email' => 'new.sk@test.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'SK Official email updated successfully.');

        $this->assertDatabaseHas('sk_officials', [
            'id' => $official->id,
            'email' => 'new.sk@test.com',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new.sk@test.com',
        ]);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_sub_admin_with_sk_officials_permission_can_change_sk_official_email(): void
    {
        $subAdmin = User::factory()->subAdmin(['sk_officials'])->create();
        $user = User::factory()->youth()->create(['email' => 'old.subsk@test.com']);
        $official = SkOfficial::factory()->create([
            'user_id' => $user->id,
            'email' => 'old.subsk@test.com',
        ]);

        Sanctum::actingAs($subAdmin);

        $response = $this->postJson("/api/admin/sk-officials/{$official->id}/email", [
            'email' => 'new.subsk@test.com',
        ]);

        $response->assertOk();
    }

    public function test_sub_admin_without_sk_officials_permission_cannot_change_sk_official_email(): void
    {
        $subAdmin = User::factory()->subAdmin(['events'])->create();
        $user = User::factory()->youth()->create(['email' => 'old.unauth@test.com']);
        $official = SkOfficial::factory()->create([
            'user_id' => $user->id,
            'email' => 'old.unauth@test.com',
        ]);

        Sanctum::actingAs($subAdmin);

        $response = $this->postJson("/api/admin/sk-officials/{$official->id}/email", [
            'email' => 'new.unauth@test.com',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_change_resident_youth_email(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = YouthProfile::factory()->create();
        $user = $profile->user;
        $user->update(['email' => 'old.youth@test.com']);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/resident-youth/{$profile->id}/email", [
            'email' => 'new.youth@test.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Resident youth email updated successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new.youth@test.com',
        ]);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_changing_resident_youth_email_syncs_linked_sk_official(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = YouthProfile::factory()->create();
        $user = $profile->user;
        $user->update(['email' => 'sync.old@test.com']);

        $official = SkOfficial::factory()->create([
            'user_id' => $user->id,
            'email' => 'sync.old@test.com',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/resident-youth/{$profile->id}/email", [
            'email' => 'sync.new@test.com',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'sync.new@test.com',
        ]);

        $this->assertDatabaseHas('sk_officials', [
            'id' => $official->id,
            'email' => 'sync.new@test.com',
        ]);
    }

    public function test_sub_admin_with_youth_records_permission_can_change_resident_youth_email(): void
    {
        $subAdmin = User::factory()->subAdmin(['youth_records'])->create();
        $profile = YouthProfile::factory()->create();

        Sanctum::actingAs($subAdmin);

        $response = $this->postJson("/api/admin/resident-youth/{$profile->id}/email", [
            'email' => 'subadmin.youth@test.com',
        ]);

        $response->assertOk();
    }

    public function test_sub_admin_without_youth_records_permission_cannot_change_resident_youth_email(): void
    {
        $subAdmin = User::factory()->subAdmin(['events'])->create();
        $profile = YouthProfile::factory()->create();

        Sanctum::actingAs($subAdmin);

        $response = $this->postJson("/api/admin/resident-youth/{$profile->id}/email", [
            'email' => 'unauth.youth@test.com',
        ]);

        $response->assertForbidden();
    }

    public function test_sk_official_change_email_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = User::factory()->youth()->create(['email' => 'taken.sk@test.com']);
        $user = User::factory()->youth()->create(['email' => 'my.sk@test.com']);
        $official = SkOfficial::factory()->create([
            'user_id' => $user->id,
            'email' => 'my.sk@test.com',
        ]);

        Sanctum::actingAs($admin);

        // Same email -> fails
        $this->postJson("/api/admin/sk-officials/{$official->id}/email", [
            'email' => 'my.sk@test.com',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Duplicate email -> fails
        $this->postJson("/api/admin/sk-officials/{$official->id}/email", [
            'email' => 'taken.sk@test.com',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Invalid format -> fails
        $this->postJson("/api/admin/sk-officials/{$official->id}/email", [
            'email' => 'invalid-email',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}

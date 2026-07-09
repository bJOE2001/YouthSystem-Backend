<?php

namespace Tests\Feature\Api\SkAdmin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\SkOfficial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SkOfficialControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => UserRole::SkAdmin->value,
            'status' => UserStatus::Active->value,
        ]);

        $this->actingAs($this->adminUser, 'sanctum');
    }

    #[Test]
    public function it_can_list_sk_officials()
    {
        SkOfficial::factory()->count(3)->create();

        $response = $this->getJson(route('sk-admin.sk-officials.index'));

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    #[Test]
    public function it_can_create_sk_official()
    {
        $payload = [
            'name' => 'John Doe',
            'initials' => 'JD',
            'barangay' => 'San Jose',
            'contact' => '09123456789',
            'email' => 'john@example.com',
            'committee' => 'Sports',
            'position' => 'Councilor',
            'responsibilities' => 'Manage sports events.',
        ];

        $response = $this->postJson(route('sk-admin.sk-officials.store'), $payload);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'success' => true,
                'message' => 'SK Official added successfully.',
                'name' => 'John Doe',
            ]);

        $this->assertDatabaseHas('sk_officials', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'committee' => 'Sports',
            'term' => '2023 - 2025',
        ]);
    }

    #[Test]
    public function it_can_show_sk_official()
    {
        $official = SkOfficial::factory()->create();

        $response = $this->getJson(route('sk-admin.sk-officials.show', $official));

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => $official->name,
                'email' => $official->email,
            ]);
    }

    #[Test]
    public function it_can_delete_sk_official()
    {
        $official = SkOfficial::factory()->create();

        $response = $this->postJson(route('sk-admin.sk-officials.destroy', $official));

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'message' => 'SK Official removed successfully.',
            ]);

        $this->assertDatabaseMissing('sk_officials', [
            'id' => $official->id,
        ]);
    }
}

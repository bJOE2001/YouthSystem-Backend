<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Purok;
use App\Models\SkOfficial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurokLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected User $skAdmin;

    protected Barangay $apokonBarangay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apokonBarangay = Barangay::create(['name' => 'Apokon']);

        $this->skAdmin = User::factory()->skAdmin()->active()->create([
            'email' => 'sk_apokon@example.com',
        ]);

        SkOfficial::factory()->create([
            'email' => 'sk_apokon@example.com',
            'barangay' => 'Apokon',
        ]);
    }

    public function test_sk_admin_can_list_puroks_of_their_barangay(): void
    {
        Sanctum::actingAs($this->skAdmin);

        Purok::create([
            'name' => 'Purok 1',
            'barangay' => 'Apokon',
            'barangay_id' => $this->apokonBarangay->id,
        ]);
        Purok::create([
            'name' => 'Purok 2',
            'barangay' => 'Apokon',
            'barangay_id' => $this->apokonBarangay->id,
        ]);
        Purok::create([
            'name' => 'Purok Other',
            'barangay' => 'Mankilam',
        ]);

        $response = $this->getJson('/api/sk/purok-library');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertEquals('Purok 1', $data[0]['name']);
        $this->assertEquals('Purok 2', $data[1]['name']);
    }

    public function test_sk_admin_can_search_puroks(): void
    {
        Sanctum::actingAs($this->skAdmin);

        Purok::create([
            'name' => 'Purok Santol',
            'barangay' => 'Apokon',
            'barangay_id' => $this->apokonBarangay->id,
        ]);
        Purok::create([
            'name' => 'Purok Durian',
            'barangay' => 'Apokon',
            'barangay_id' => $this->apokonBarangay->id,
        ]);

        $response = $this->getJson('/api/sk/purok-library?search=Santol');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Purok Santol', $data[0]['name']);
    }

    public function test_sk_admin_can_create_purok(): void
    {
        Sanctum::actingAs($this->skAdmin);

        $response = $this->postJson('/api/sk/purok-library', [
            'name' => 'Purok Maharlika',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Purok created successfully.',
                'data' => [
                    'name' => 'Purok Maharlika',
                    'barangay' => 'Apokon',
                    'barangay_id' => $this->apokonBarangay->id,
                ],
            ]);

        $this->assertDatabaseHas('puroks', [
            'name' => 'Purok Maharlika',
            'barangay' => 'Apokon',
            'barangay_id' => $this->apokonBarangay->id,
            'user_id' => $this->skAdmin->id,
        ]);
    }

    public function test_sk_admin_cannot_create_duplicate_purok_in_same_barangay(): void
    {
        Sanctum::actingAs($this->skAdmin);

        Purok::create([
            'name' => 'Purok Sampaguita',
            'barangay' => 'Apokon',
            'barangay_id' => $this->apokonBarangay->id,
        ]);

        $response = $this->postJson('/api/sk/purok-library', [
            'name' => 'Purok Sampaguita',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_same_purok_name_can_exist_in_different_barangays(): void
    {
        Sanctum::actingAs($this->skAdmin);

        // Already exists in Mankilam
        Purok::create([
            'name' => 'Purok 1',
            'barangay' => 'Mankilam',
        ]);

        // Creating in Apokon should succeed
        $response = $this->postJson('/api/sk/purok-library', [
            'name' => 'Purok 1',
        ]);

        $response->assertStatus(201);
    }

    public function test_sk_admin_can_update_purok_in_their_barangay(): void
    {
        Sanctum::actingAs($this->skAdmin);

        $purok = Purok::create([
            'name' => 'Purok Old',
            'barangay' => 'Apokon',
            'barangay_id' => $this->apokonBarangay->id,
        ]);

        $response = $this->postJson("/api/sk/purok-library/{$purok->id}", [
            'name' => 'Purok New',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Purok updated successfully.',
                'data' => [
                    'id' => $purok->id,
                    'name' => 'Purok New',
                    'barangay' => 'Apokon',
                ],
            ]);

        $this->assertDatabaseHas('puroks', [
            'id' => $purok->id,
            'name' => 'Purok New',
        ]);
    }

    public function test_sk_admin_cannot_update_purok_of_another_barangay(): void
    {
        Sanctum::actingAs($this->skAdmin);

        $otherPurok = Purok::create([
            'name' => 'Purok Other',
            'barangay' => 'Mankilam',
        ]);

        $response = $this->postJson("/api/sk/purok-library/{$otherPurok->id}", [
            'name' => 'Purok Hacked',
        ]);

        $response->assertStatus(403);
    }

    public function test_sk_admin_can_delete_purok_in_their_barangay(): void
    {
        Sanctum::actingAs($this->skAdmin);

        $purok = Purok::create([
            'name' => 'Purok To Delete',
            'barangay' => 'Apokon',
            'barangay_id' => $this->apokonBarangay->id,
        ]);

        $response = $this->postJson("/api/sk/purok-library/{$purok->id}/delete");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Purok deleted successfully.',
            ]);

        $this->assertDatabaseMissing('puroks', [
            'id' => $purok->id,
        ]);
    }

    public function test_sk_admin_cannot_delete_purok_of_another_barangay(): void
    {
        Sanctum::actingAs($this->skAdmin);

        $otherPurok = Purok::create([
            'name' => 'Purok Other',
            'barangay' => 'Mankilam',
        ]);

        $response = $this->postJson("/api/sk/purok-library/{$otherPurok->id}/delete");

        $response->assertStatus(403);
        $this->assertDatabaseHas('puroks', ['id' => $otherPurok->id]);
    }

    public function test_public_endpoint_returns_puroks_for_given_barangay(): void
    {
        Purok::create([
            'name' => 'Purok 1',
            'barangay' => 'Mankilam',
        ]);
        Purok::create([
            'name' => 'Purok AALA',
            'barangay' => 'Mankilam',
        ]);
        Purok::create([
            'name' => 'Purok Apokon 1',
            'barangay' => 'Apokon',
        ]);

        $response = $this->getJson('/api/puroks?barangay=Mankilam');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    ['name' => 'Purok 1', 'barangay' => 'Mankilam'],
                    ['name' => 'Purok AALA', 'barangay' => 'Mankilam'],
                ],
            ]);

        $this->assertCount(2, $response->json('data'));

        // Also verify /api/public/puroks
        $publicRes = $this->getJson('/api/public/puroks?barangay=Mankilam');
        $publicRes->assertStatus(200);
        $this->assertCount(2, $publicRes->json('data'));
    }

    public function test_public_endpoint_returns_empty_when_no_barangay_provided(): void
    {
        Purok::create([
            'name' => 'Purok 1',
            'barangay' => 'Mankilam',
        ]);

        $response = $this->getJson('/api/puroks');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    }

    public function test_unauthenticated_user_cannot_access_sk_library_endpoints(): void
    {
        $res = $this->getJson('/api/sk/purok-library');
        $res->assertStatus(401);
    }

    public function test_youth_user_cannot_access_sk_library_endpoints(): void
    {
        $youth = User::factory()->youth()->active()->create();
        Sanctum::actingAs($youth);

        $res = $this->getJson('/api/sk/purok-library');
        $res->assertStatus(403);
    }
}

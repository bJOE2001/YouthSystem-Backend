<?php

namespace Tests\Feature\Api\SkAdmin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\YouthProfileStatus;
use App\Models\User;
use App\Models\YouthProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YouthValidationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::SkAdmin->value,
            'status' => UserStatus::Active->value,
        ]);
    }

    #[Test]
    public function it_can_fetch_pending_youth_registrations(): void
    {
        YouthProfile::factory()->count(3)->create([
            'status' => YouthProfileStatus::Pending->value,
        ]);

        YouthProfile::factory()->count(2)->create([
            'status' => YouthProfileStatus::Approved->value,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson(route('sk-admin.youth-registration.index'));

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_can_approve_youth_registration(): void
    {
        $youth = YouthProfile::factory()->create([
            'status' => YouthProfileStatus::Pending->value,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('sk-admin.youth-registration.approve', $youth));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('youth_profiles', [
            'id' => $youth->id,
            'status' => YouthProfileStatus::Approved->value,
            'reviewed_by' => $this->admin->id,
        ]);
    }

    #[Test]
    public function it_can_disapprove_youth_registration(): void
    {
        $youth = YouthProfile::factory()->create([
            'status' => YouthProfileStatus::Pending->value,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('sk-admin.youth-registration.disapprove', $youth));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('youth_profiles', [
            'id' => $youth->id,
            'status' => YouthProfileStatus::Rejected->value,
            'reviewed_by' => $this->admin->id,
        ]);
    }
}

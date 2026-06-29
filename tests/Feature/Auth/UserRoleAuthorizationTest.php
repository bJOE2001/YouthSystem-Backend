<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UserRoleAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const PROTECTED_ROUTE = '/api/test/role-protected';

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', 'active', 'role:youth_admin,sk_official'])
            ->get(self::PROTECTED_ROUTE, fn () => response()->noContent());
    }

    public function test_youth_administrator_can_access_an_authorized_route(): void
    {
        $user = User::factory()->youthAdmin()->create();

        $this
            ->actingAs($user, 'web')
            ->getJson(self::PROTECTED_ROUTE)
            ->assertNoContent();
    }

    public function test_sk_official_can_access_an_authorized_route(): void
    {
        $user = User::factory()->skOfficial()->create();

        $this
            ->actingAs($user, 'web')
            ->getJson(self::PROTECTED_ROUTE)
            ->assertNoContent();
    }

    public function test_youth_user_cannot_access_a_staff_route(): void
    {
        $user = User::factory()->youth()->create();

        $this
            ->actingAs($user, 'web')
            ->getJson(self::PROTECTED_ROUTE)
            ->assertForbidden();
    }

    public function test_guest_cannot_access_a_role_protected_route(): void
    {
        $this->getJson(self::PROTECTED_ROUTE)->assertUnauthorized();
    }
}

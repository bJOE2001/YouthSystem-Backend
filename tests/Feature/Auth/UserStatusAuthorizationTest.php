<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UserStatusAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const PROTECTED_ROUTE = '/api/test/active-account';

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', 'active'])
            ->get(self::PROTECTED_ROUTE, fn () => response()->noContent());
    }

    public function test_active_user_can_access_an_active_account_route(): void
    {
        $user = User::factory()->active()->create();

        $this
            ->actingAs($user, 'web')
            ->getJson(self::PROTECTED_ROUTE)
            ->assertNoContent();
    }

    public function test_pending_user_cannot_access_an_active_account_route(): void
    {
        $user = User::factory()->pending()->create();

        $this
            ->actingAs($user, 'web')
            ->getJson(self::PROTECTED_ROUTE)
            ->assertForbidden();
    }

    public function test_suspended_user_cannot_access_an_active_account_route(): void
    {
        $user = User::factory()->suspended()->create();

        $this
            ->actingAs($user, 'web')
            ->getJson(self::PROTECTED_ROUTE)
            ->assertForbidden();
    }

    public function test_guest_cannot_access_an_active_account_route(): void
    {
        $this->getJson(self::PROTECTED_ROUTE)->assertUnauthorized();
    }
}

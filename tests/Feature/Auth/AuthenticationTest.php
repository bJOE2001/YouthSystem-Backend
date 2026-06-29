<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->youthAdmin()->create([
            'password' => 'correct-password',
        ]);

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'correct-password',
            'remember' => true,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.role', UserRole::YouthAdmin->value)
            ->assertJsonPath('user.status', UserStatus::Active->value)
            ->assertJsonMissingPath('user.password');

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_login_requires_valid_input(): void
    {
        $response = $this->postJson('/login');

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_user_cannot_log_in_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => 'correct-password',
        ]);

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest('web');
    }

    public function test_pending_user_cannot_log_in(): void
    {
        $user = User::factory()->pending()->create([
            'password' => 'correct-password',
        ]);

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertJsonValidationErrors(['email']);

        $this->assertGuest('web');
    }

    public function test_suspended_user_cannot_log_in(): void
    {
        $user = User::factory()->suspended()->create([
            'password' => 'correct-password',
        ]);

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertJsonValidationErrors(['email']);

        $this->assertGuest('web');
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create([
            'password' => 'correct-password',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/login', [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    }

    public function test_authenticated_user_can_retrieve_their_account(): void
    {
        $user = User::factory()->skOfficial()->create();

        $response = $this
            ->actingAs($user, 'web')
            ->getJson('/api/user');

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.role', UserRole::SkOfficial->value);
    }

    public function test_guest_cannot_retrieve_an_account(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user, 'web')
            ->postJson('/logout');

        $response->assertNoContent();
        $this->assertGuest('web');
    }
}

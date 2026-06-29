<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserStatusTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_status_values_match_the_application_contract(): void
    {
        $statusValues = array_map(
            fn (UserStatus $status): string => $status->value,
            UserStatus::cases(),
        );

        $this->assertSame([
            'pending',
            'active',
            'suspended',
        ], $statusValues);
    }

    public function test_users_default_to_active_status(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertTrue($user->isActive());
    }

    public function test_factory_states_assign_each_user_status(): void
    {
        $pendingUser = User::factory()->pending()->create();
        $activeUser = User::factory()->active()->create();
        $suspendedUser = User::factory()->suspended()->create();

        $this->assertSame(UserStatus::Pending, $pendingUser->status);
        $this->assertSame(UserStatus::Active, $activeUser->status);
        $this->assertSame(UserStatus::Suspended, $suspendedUser->status);
    }
}

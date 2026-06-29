<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_role_values_match_the_application_contract(): void
    {
        $roleValues = array_map(
            fn (UserRole $role): string => $role->value,
            UserRole::cases(),
        );

        $this->assertSame([
            'youth_admin',
            'sk_official',
            'youth',
        ], $roleValues);
    }

    public function test_users_default_to_the_youth_role(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserRole::Youth, $user->role);
    }

    public function test_factory_states_assign_each_user_role(): void
    {
        $youthAdmin = User::factory()->youthAdmin()->create();
        $skOfficial = User::factory()->skOfficial()->create();
        $youth = User::factory()->youth()->create();

        $this->assertSame(UserRole::YouthAdmin, $youthAdmin->role);
        $this->assertSame(UserRole::SkOfficial, $skOfficial->role);
        $this->assertSame(UserRole::Youth, $youth->role);
    }
}

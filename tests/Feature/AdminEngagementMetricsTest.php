<?php

namespace Tests\Feature;

use App\Actions\Dashboard\GetEngagementMetricsAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminEngagementMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_last_login_at_is_updated_on_successful_login(): void
    {
        $user = User::factory()->create([
            'email' => 'youth_login_test@example.com',
            'password' => bcrypt('password123'),
            'role' => UserRole::Youth->value,
            'status' => UserStatus::Active->value,
            'last_login_at' => null,
        ]);

        $this->assertNull($user->last_login_at);

        $response = $this->postJson('/login', [
            'email' => 'youth_login_test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertTrue($user->last_login_at->isToday());
    }

    public function test_unauthenticated_user_cannot_access_engagement_metrics(): void
    {
        $response = $this->getJson('/api/admin/dashboard/engagement-metrics');

        $response->assertStatus(401);
    }

    public function test_youth_user_cannot_access_engagement_metrics(): void
    {
        $youth = User::factory()->youth()->active()->create();
        Sanctum::actingAs($youth);

        $response = $this->getJson('/api/admin/dashboard/engagement-metrics');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_engagement_metrics_with_correct_structure(): void
    {
        $admin = User::factory()->admin()->active()->create();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/dashboard/engagement-metrics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'summary' => [
                    'weekly_active_users',
                    'weekly_change_pct',
                    'monthly_active_users',
                    'monthly_inactive_users',
                    'monthly_active_pct',
                    'total_youth',
                ],
                'monthly_comparison' => [
                    '*' => [
                        'month',
                        'active',
                        'inactive',
                    ],
                ],
                'weekly_trend' => [
                    '*' => [
                        'week',
                        'label',
                        'count',
                    ],
                ],
            ]);

        $json = $response->json();
        $this->assertCount(6, $json['monthly_comparison']);
        $this->assertCount(8, $json['weekly_trend']);
        $this->assertEquals('W1', $json['weekly_trend'][0]['week']);
        $this->assertEquals('W8', $json['weekly_trend'][7]['week']);
    }

    public function test_metrics_calculation_accuracy(): void
    {
        $admin = User::factory()->admin()->active()->create([
            'last_login_at' => now()->subDays(1), // Admin login should not skew youth metrics
        ]);
        Sanctum::actingAs($admin);

        // Youth 1: logged in 2 days ago (Current WAU + Monthly Active)
        User::factory()->youth()->active()->create([
            'last_login_at' => now()->subDays(2),
            'created_at' => now()->subMonths(3),
        ]);

        // Youth 2: logged in 10 days ago (Previous WAU + Monthly Active)
        User::factory()->youth()->active()->create([
            'last_login_at' => now()->subDays(10),
            'created_at' => now()->subMonths(3),
        ]);

        // Youth 3: logged in 45 days ago (Inactive)
        User::factory()->youth()->active()->create([
            'last_login_at' => now()->subDays(45),
            'created_at' => now()->subMonths(3),
        ]);

        // Youth 4: never logged in (Inactive)
        User::factory()->youth()->active()->create([
            'last_login_at' => null,
            'created_at' => now()->subMonths(3),
        ]);

        $response = $this->getJson('/api/admin/dashboard/engagement-metrics?refresh=1');

        $response->assertStatus(200);

        $summary = $response->json('summary');
        $this->assertEquals(4, $summary['total_youth']);
        $this->assertEquals(1, $summary['weekly_active_users']);
        $this->assertEquals(0.0, $summary['weekly_change_pct']); // (1 - 1) / 1 * 100 = 0.0
        $this->assertEquals(2, $summary['monthly_active_users']);
        $this->assertEquals(2, $summary['monthly_inactive_users']);
        $this->assertEquals(50.0, $summary['monthly_active_pct']);

        $weeklyTrend = $response->json('weekly_trend');
        $this->assertEquals(1, $weeklyTrend[7]['count']); // W8 (current 7 days)
        $this->assertEquals(1, $weeklyTrend[6]['count']); // W7 (8-14 days ago)
        $this->assertEquals(0, $weeklyTrend[0]['count']); // W1
    }

    public function test_metrics_are_cached_and_can_be_refreshed(): void
    {
        $admin = User::factory()->admin()->active()->create();
        Sanctum::actingAs($admin);

        Cache::forget(GetEngagementMetricsAction::CACHE_KEY);

        // Initial request caches metrics (total_youth = 0)
        $res1 = $this->getJson('/api/admin/dashboard/engagement-metrics');
        $res1->assertStatus(200);
        $this->assertEquals(0, $res1->json('summary.total_youth'));

        // Add a new youth user
        User::factory()->youth()->active()->create([
            'last_login_at' => now(),
        ]);

        // Second request should return cached value (0)
        $res2 = $this->getJson('/api/admin/dashboard/engagement-metrics');
        $res2->assertStatus(200);
        $this->assertEquals(0, $res2->json('summary.total_youth'));

        // Request with refresh=1 should invalidate cache and return 1
        $res3 = $this->getJson('/api/admin/dashboard/engagement-metrics?refresh=1');
        $res3->assertStatus(200);
        $this->assertEquals(1, $res3->json('summary.total_youth'));
    }
}

<?php

namespace App\Actions\Dashboard;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class GetEngagementMetricsAction
{
    /**
     * Cache duration in seconds (30 minutes).
     */
    public const CACHE_TTL = 1800;

    /**
     * Cache key for engagement metrics.
     */
    public const CACHE_KEY = 'admin_engagement_metrics';

    /**
     * Execute the action to get aggregated engagement metrics.
     */
    public function execute(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->calculateMetrics();
        });
    }

    /**
     * Calculate dashboard engagement metrics for youth users.
     */
    protected function calculateMetrics(): array
    {
        $baseQuery = User::query()->where('role', UserRole::Youth->value);

        // 1. Summary Metrics
        $totalYouth = (clone $baseQuery)->count();

        $currentWau = (clone $baseQuery)
            ->where('last_login_at', '>=', now()->subDays(7))
            ->count();

        $previousWau = (clone $baseQuery)
            ->where('last_login_at', '>=', now()->subDays(14))
            ->where('last_login_at', '<', now()->subDays(7))
            ->count();

        if ($previousWau > 0) {
            $weeklyChangePct = round((($currentWau - $previousWau) / $previousWau) * 100, 1);
        } elseif ($currentWau > 0) {
            $weeklyChangePct = 100.0;
        } else {
            $weeklyChangePct = 0.0;
        }

        $monthlyActiveUsers = (clone $baseQuery)
            ->where('last_login_at', '>=', now()->subDays(30))
            ->count();

        $monthlyInactiveUsers = (clone $baseQuery)
            ->where(function ($q) {
                $q->where('last_login_at', '<', now()->subDays(30))
                    ->orWhereNull('last_login_at');
            })
            ->count();

        $monthlyActivePct = $totalYouth > 0
            ? round(($monthlyActiveUsers / $totalYouth) * 100, 1)
            : 0.0;

        // 2. 6-Month Monthly Historical Comparison
        $monthlyComparison = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->copy()->subMonths($i);
            $startOfMonth = (clone $monthDate)->startOfMonth();
            $endOfMonth = (clone $monthDate)->endOfMonth();

            // Users who logged in during this month
            $activeInMonth = (clone $baseQuery)
                ->where('last_login_at', '>=', $startOfMonth)
                ->where('last_login_at', '<=', $endOfMonth)
                ->count();

            // Users registered on or before the end of this month
            $totalInMonth = (clone $baseQuery)
                ->where('created_at', '<=', $endOfMonth)
                ->count();

            $inactiveInMonth = max(0, $totalInMonth - $activeInMonth);

            $monthlyComparison[] = [
                'month' => $monthDate->format('M'),
                'active' => $activeInMonth,
                'inactive' => $inactiveInMonth,
            ];
        }

        // 3. 8-Week WAU Trend
        $weeklyTrend = [];
        for ($i = 7; $i >= 0; $i--) {
            $weekNum = 8 - $i;
            $startOfWeek = now()->copy()->subDays(($i + 1) * 7);
            $endOfWeek = $i === 0 ? now() : now()->copy()->subDays($i * 7);

            $count = (clone $baseQuery)
                ->where('last_login_at', '>=', $startOfWeek)
                ->where('last_login_at', '<=', $endOfWeek)
                ->count();

            $weeklyTrend[] = [
                'week' => 'W'.$weekNum,
                'label' => $startOfWeek->format('M d').' - '.$endOfWeek->format('M d'),
                'count' => $count,
            ];
        }

        return [
            'summary' => [
                'weekly_active_users' => $currentWau,
                'weekly_change_pct' => $weeklyChangePct,
                'monthly_active_users' => $monthlyActiveUsers,
                'monthly_inactive_users' => $monthlyInactiveUsers,
                'monthly_active_pct' => $monthlyActivePct,
                'total_youth' => $totalYouth,
            ],
            'monthly_comparison' => $monthlyComparison,
            'weekly_trend' => $weeklyTrend,
        ];
    }
}

<?php

namespace App\Actions\Dashboard;

use App\Enums\YouthProfileStatus;
use App\Models\YouthProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GetYouthParticipationAction
{
    /**
     * Cache duration in seconds (30 minutes).
     */
    public const CACHE_TTL = 1800;

    /**
     * Cache key for youth participation metrics.
     */
    public const CACHE_KEY = 'admin_youth_participation_metrics';

    /**
     * Execute the action to get aggregated youth participation metrics.
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
     * Calculate participation metrics for Community Events and Sports Programs.
     */
    protected function calculateMetrics(): array
    {
        // 1. Total Approved Youth base
        $totalYouth = YouthProfile::query()
            ->where('status', YouthProfileStatus::Approved->value)
            ->count();

        // 2. Unique youth who joined events
        $eventUserIds = DB::table('event_user')
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // 3. Unique youth who joined sports programs
        $sportsUserIds = DB::table('sports_program_user')
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $totalEventParticipants = count($eventUserIds);
        $totalSportsParticipants = count($sportsUserIds);

        // Combined unique youth participating in at least 1 program
        $combinedUserIds = array_unique(array_merge($eventUserIds, $sportsUserIds));
        $overallUniqueParticipants = count($combinedUserIds);

        $participationRate = $totalYouth > 0
            ? round(($overallUniqueParticipants / $totalYouth) * 100, 1)
            : 0.0;

        // 4. Monthly Trend (Past 6 Months)
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->copy()->subMonths($i);
            $startOfMonth = (clone $monthDate)->startOfMonth()->toDateTimeString();
            $endOfMonth = (clone $monthDate)->endOfMonth()->toDateTimeString();
            $monthLabel = $monthDate->format('M');
            $fullMonthLabel = $monthDate->format('M Y');

            // Count event registrations in this month
            $eventCount = DB::table('event_user')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            // Count sports program registrations in this month
            $sportsCount = DB::table('sports_program_user')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            $monthlyTrend[] = [
                'month' => $monthLabel,
                'full_month' => $fullMonthLabel,
                'events' => $eventCount,
                'sports' => $sportsCount,
                'total' => $eventCount + $sportsCount,
            ];
        }

        return [
            'summary' => [
                'total_event_participants' => $totalEventParticipants,
                'total_sports_participants' => $totalSportsParticipants,
                'overall_unique_participants' => $overallUniqueParticipants,
                'total_youth' => $totalYouth,
                'participation_rate' => $participationRate,
            ],
            'monthly_trend' => $monthlyTrend,
        ];
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;

class UpdateEventStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-event-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically update event statuses based on their start and end dates.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now()->toDateString();

        // 1. Upcoming -> Ongoing
        // If an event is 'upcoming' and its start_date is today or in the past, it becomes 'ongoing'
        $ongoingCount = Event::where('status', 'upcoming')
            ->whereNotNull('start_date')
            ->whereDate('start_date', '<=', $today)
            ->update(['status' => 'ongoing']);

        // 2. Ongoing/Upcoming -> Completed
        // If an event is 'ongoing' or 'upcoming' and its end_date is in the past, it becomes 'completed'.
        // If end_date is null, use start_date instead.
        $completedCount = Event::whereIn('status', ['upcoming', 'ongoing'])
            ->where(function ($query) use ($today) {
                $query->whereNotNull('end_date')->whereDate('end_date', '<', $today)
                    ->orWhere(function ($q) use ($today) {
                        $q->whereNull('end_date')->whereNotNull('start_date')->whereDate('start_date', '<', $today);
                    });
            })
            ->update(['status' => 'completed']);

        $this->info("Updated {$ongoingCount} events to 'ongoing'.");
        $this->info("Updated {$completedCount} events to 'completed'.");
    }
}

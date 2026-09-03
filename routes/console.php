<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:update-event-statuses')->daily();

Schedule::command('app:send-inactive-reengagement-emails')
    ->weekly()
    ->mondays()
    ->at('09:00')
    ->withoutOverlapping();

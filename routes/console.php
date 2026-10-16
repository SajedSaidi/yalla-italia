<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Requires the scheduler to be running: `php artisan schedule:work` locally,
// or a cron / Task Scheduler entry calling `php artisan schedule:run` every minute in production.

// Application deadline reminders every Monday at 9 AM (Rome time)
Schedule::command('notify:application-deadlines')
    ->weeklyOn(1, '09:00')
    ->timezone('Europe/Rome')
    ->emailOutputOnFailure('admin@yalla-italia.com');

// Document deadline reminders every Wednesday at 10 AM (Rome time)
Schedule::command('notify:document-deadlines')
    ->weeklyOn(3, '10:00')
    ->timezone('Europe/Rome')
    ->emailOutputOnFailure('admin@yalla-italia.com');

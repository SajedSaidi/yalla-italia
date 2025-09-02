<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\NotifyApplicationDeadlines::class,
        Commands\NotifyDocumentDeadlines::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Send application deadline reminders every Monday at 9 AM
        $schedule->command('notify:application-deadlines')
            ->weeklyOn(1, '09:00')
            ->timezone('Europe/Rome')
            ->emailOutputOnFailure('admin@yalla-italia.com');

        // Send document deadline reminders every Wednesday at 10 AM
        $schedule->command('notify:document-deadlines')
            ->weeklyOn(3, '10:00')
            ->timezone('Europe/Rome')
            ->emailOutputOnFailure('admin@yalla-italia.com');

        // Alternative: Daily checks (uncomment if you prefer daily notifications)
        // $schedule->command('notify:application-deadlines')->dailyAt('09:00');
        // $schedule->command('notify:document-deadlines')->dailyAt('10:00');
    }
}

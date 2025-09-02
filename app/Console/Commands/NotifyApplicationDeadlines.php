<?php

namespace App\Console\Commands;

use App\Mail\ApplicationDeadlineReminder;
use App\Models\Application;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotifyApplicationDeadlines extends Command
{
    protected $signature = 'notify:application-deadlines';
    protected $description = 'Send application deadline reminders to students';

    public function handle()
    {
        try {
            // Fetch all unpaid future-deadline applications, eager-loading student & user
            $applications = Application::with(['student.user', 'program'])
                ->where('payment_status', 'unpaid')
                ->whereHas(
                    'program',
                    fn($q) =>
                    $q->where('application_deadline', '>=', now())->where('application_deadline', '<=', now()->addDays(21))
                )
                ->get();

            // Group by student email
            $byEmail = $applications->groupBy(fn($app) => $app->student->user->email);

            foreach ($byEmail as $email => $apps) {
                // Derive student name from the first record
                $studentName = $apps->first()->student->user->name;

                // Build a Collection of program data
                $programs = $apps->map(function ($app) {
                    $deadline = Carbon::parse($app->program->application_deadline);
                    return [
                        'title'     => $app->program->composite_title,
                        'deadline'  => $deadline,
                        'fee'       => $app->program->application_fee,
                        'days_left' => floor(now()->floatDiffInDays($deadline, false)),
                    ];
                });

                // Queue the mailable
                Mail::to($email)->queue(new ApplicationDeadlineReminder($studentName, $programs));
            }

            Log::info('Application deadline reminders sent to ' . count($byEmail) . ' students.');
        } catch (\Exception $e) {
            Log::error('Failed to send application deadline reminders: ' . $e->getMessage());
        }
    }
}

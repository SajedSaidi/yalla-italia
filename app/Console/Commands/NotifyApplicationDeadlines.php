<?php

namespace App\Console\Commands;

use App\Mail\ApplicationDeadlineReminder;
use App\Models\Application;
use App\Support\SafeMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NotifyApplicationDeadlines extends Command
{
    protected $signature = 'notify:application-deadlines';
    protected $description = 'Send application deadline reminders to students';

    public function handle(): int
    {
        // All unpaid applications whose program deadline is within the next 21 days
        $applications = Application::with(['student.user', 'program.university', 'program.major.languages', 'program.academicYear'])
            ->where('payment_status', 'unpaid')
            ->whereHas(
                'program',
                fn ($q) => $q->where('application_deadline', '>=', now())->where('application_deadline', '<=', now()->addDays(21))
            )
            ->get()
            ->filter(fn (Application $app) => $app->student?->user);

        $sent = 0;
        $failed = 0;

        // One email per student, listing all of their upcoming deadlines
        foreach ($applications->groupBy('student_id') as $apps) {
            $user = $apps->first()->student->user;

            $programs = $apps
                ->map(function (Application $app) {
                    $deadline = Carbon::parse($app->program->application_deadline);

                    return [
                        'title'     => $app->program->composite_title,
                        'deadline'  => $deadline,
                        'fee'       => $app->program->application_fee,
                        'days_left' => (int) floor(now()->floatDiffInDays($deadline, false)),
                    ];
                })
                ->sortBy('deadline')
                ->values();

            SafeMail::send($user->email, new ApplicationDeadlineReminder($user->name, $programs)) ? $sent++ : $failed++;
        }

        $message = "Application deadline reminders sent to {$sent} student(s)" . ($failed ? ", {$failed} failed (see log)." : '.');

        $this->info($message);
        Log::info($message);

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}

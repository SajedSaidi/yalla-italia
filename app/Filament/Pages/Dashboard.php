<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AdminApplicationChart;
use App\Filament\Widgets\DocumentsTimeline;
use App\Filament\Widgets\LatestApplications;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\StudentApplicationChart;
use App\Filament\Widgets\DocumentDeadlinesCalendar;
use App\Filament\Widgets\MissingDocumentsAlert;
use App\Mail\ApplicationDeadlineReminder;
use App\Mail\MissingDocumentsReminder;
use App\Models\Application;
use App\Models\Document;
use App\Models\DocumentDeadline;
use App\Models\Program;
use App\Models\Student;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int $navigationSort = -2;

    protected function getActions(): array
    {
        if (Auth::user()->isStudent()) {
            return [];
        }
        return [
            Action::make('notifyApplicationDeadlines')
                ->label('Notify Application Deadlines')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->action(function () {
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


                        Notification::make()
                            ->title('Email Sent')
                            ->success()
                            ->body('Application deadline reminders have been sent successfully!')
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->danger()
                            ->body('Failed to send email: ' . $e->getMessage())
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Send Test Email')
                ->modalDescription('This will send a test email to your email address.')
                ->modalSubmitActionLabel('Yes, send it'),

            Action::make('notifyDocumentDeadlines')
                ->label('Notify Document Deadlines')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->action(function () {
                    // 1. Fetch all upcoming deadlines (next 30 days)
                    $deadlines = DocumentDeadline::with('documentType')
                        ->whereBetween('deadline', [now(), now()->addDays(30)])
                        ->get()
                        // Group by composite key to lookup per program
                        ->groupBy(fn($d) => implode('-', [
                            $d->academic_year_id,
                            $d->university_id,
                            $d->education_level,
                        ]));

                    // 2. Fetch all pending applications + submitted documents
                    $applications = Application::with([
                        'student.user',
                        'student.documents.documentType',
                        'program' => fn($q) => $q->with(['academicYear', 'university', 'major'])
                    ])
                        ->where('status', 'pending')
                        ->get();

                    $emailsSent = 0;

                    foreach ($applications as $app) {
                        // Build the same composite key for this program
                        $key = implode('-', [
                            $app->program->academic_year_id,
                            $app->program->university_id,
                            $app->program->major->type,
                        ]);

                        // If no deadlines for this program, skip
                        if (! isset($deadlines[$key])) {
                            continue;
                        }

                        // Determine which document_type_ids are required vs submitted
                        $requiredIds = $deadlines[$key]->pluck('document_type_id')->unique();
                        $submittedIds = $app->student->documents
                            ->pluck('document_type_id')
                            ->unique();

                        // Compute the missing ones
                        $missingIds = $requiredIds->diff($submittedIds);
                        if ($missingIds->isEmpty()) {
                            continue;
                        }

                        // Build a list of missing document info for this student
                        $missingDocs = $deadlines[$key]
                            ->whereIn('document_type_id', $missingIds)
                            ->map(fn($d) => [
                                'education_level' => $app->program->major->type,
                                'university'     => $app->program->university->name,
                                'academic_year'  => $app->program->academicYear->name,
                                'document_name'  => $d->documentType->name,
                                'deadline'       => $d->deadline,
                            ]);

                        // dd($missingDocs);
                        // Send a single email per student
                        Mail::to($app->student->user->email)
                            ->queue(new MissingDocumentsReminder(
                                $app->student->user->name,
                                $missingDocs
                            ));

                        $emailsSent++;
                    }

                    Notification::make()
                        ->title("{$emailsSent} email(s) sent")
                        ->success()
                        ->body('All students with missing documents have been notified.')
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Send Missing-Document Notifications')
                ->modalDescription('This will email each student their list of pending documents.')
                ->modalSubmitActionLabel('Yes, notify')
        ];
    }


    public function getHeaderWidgets(): array
    {
        return [
            // StatsOverview::class,
            // // Auth::user()->isStudent() ? StudentApplicationChart::class : AdminApplicationChart::class,
            // DocumentDeadlinesCalendar::class,
            // MissingDocumentsAlert::class,

        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            // DocumentsTimeline::class,
            // LatestApplications::class,
        ];
    }
}

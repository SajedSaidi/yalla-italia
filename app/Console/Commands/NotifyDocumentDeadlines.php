<?php

namespace App\Console\Commands;

use App\Mail\MissingDocumentsReminder;
use App\Models\Application;
use App\Models\DocumentDeadline;
use App\Support\SafeMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NotifyDocumentDeadlines extends Command
{
    protected $signature = 'notify:document-deadlines';
    protected $description = 'Send document deadline reminders to students';

    private const EDUCATION_LEVELS = [
        'single_cycle' => 'Single Cycle',
        'bachelor' => 'Bachelor',
        'master' => 'Master',
        'phd' => 'PhD',
    ];

    public function handle(): int
    {
        // 1. All upcoming deadlines (next 30 days), keyed per academic year / university / education level
        $deadlines = DocumentDeadline::with('documentType')
            ->whereBetween('deadline', [now()->startOfDay(), now()->addDays(30)])
            ->get()
            ->groupBy(fn ($d) => implode('-', [
                $d->academic_year_id,
                $d->university_id,
                $d->education_level,
            ]));

        // 2. All pending applications with the student's submitted documents
        $applications = Application::with([
            'student.user',
            'student.documents',
            'program' => fn ($q) => $q->with(['academicYear', 'university', 'major']),
        ])
            ->where('status', 'pending')
            ->get()
            ->filter(fn (Application $app) => $app->student?->user && $app->program);

        $sent = 0;
        $failed = 0;

        // 3. One email per student, merging the missing documents of all their pending applications
        foreach ($applications->groupBy('student_id') as $apps) {
            $student = $apps->first()->student;
            $submittedIds = $student->documents->pluck('document_type_id')->unique();

            $missingDocs = $apps
                ->flatMap(function (Application $app) use ($deadlines, $submittedIds) {
                    $program = $app->program;
                    $key = implode('-', [$program->academic_year_id, $program->university_id, $program->major?->type]);

                    return ($deadlines[$key] ?? collect())
                        ->reject(fn ($d) => $submittedIds->contains($d->document_type_id))
                        ->map(fn ($d) => [
                            'key'             => "{$d->id}",
                            'education_level' => self::EDUCATION_LEVELS[$program->major?->type] ?? $program->major?->type,
                            'university'      => $program->university?->name,
                            'academic_year'   => $program->academicYear?->name,
                            'document_name'   => $d->documentType?->name,
                            'deadline'        => Carbon::parse($d->deadline)->format('M j, Y'),
                            'sort'            => Carbon::parse($d->deadline)->timestamp,
                        ]);
                })
                ->unique('key')
                ->sortBy('sort')
                ->values();

            if ($missingDocs->isEmpty()) {
                continue;
            }

            SafeMail::send($student->user->email, new MissingDocumentsReminder($student->user->name, $missingDocs)) ? $sent++ : $failed++;
        }

        $message = "Document deadline reminders sent to {$sent} student(s)" . ($failed ? ", {$failed} failed (see log)." : '.');

        $this->info($message);
        Log::info($message);

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}

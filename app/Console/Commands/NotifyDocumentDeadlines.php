<?php

namespace App\Console\Commands;

use App\Mail\MissingDocumentsReminder;
use App\Models\Application;
use App\Models\DocumentDeadline;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotifyDocumentDeadlines extends Command
{
    protected $signature = 'notify:document-deadlines';
    protected $description = 'Send document deadline reminders to students';

    public function handle()
    {
        try {
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

            $this->info("Document deadline reminders sent to {$emailsSent} students.");
            Log::info("Document deadline reminders sent to {$emailsSent} students.");

            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to send document deadline reminders: ' . $e->getMessage());
            Log::error('Failed to send document deadline reminders: ' . $e->getMessage());
            return 1;
        }
    }
}

<?php

namespace Tests\Feature;

use App\Mail\MissingDocumentsReminder;
use App\Models\AcademicYear;
use App\Models\Application;
use App\Models\DocumentDeadline;
use App\Models\DocumentType;
use App\Models\Major;
use App\Models\Program;
use App\Models\University;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DashboardAndCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_has_no_calendar(): void
    {
        $this->assertFalse(class_exists(\Saade\FilamentFullCalendar\FilamentFullCalendarPlugin::class));
        $this->assertFalse(class_exists(\App\Filament\Widgets\DocumentDeadlinesCalendar::class));

        $widgets = collect(Filament::getPanel('admin')->getWidgets());
        $this->assertFalse($widgets->contains(fn ($w) => str_contains($w, 'Calendar')));

        $this->actingAs(User::factory()->manager()->create())
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('fullcalendar', false);

        $this->actingAs($this->makeStudent()->user)
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('fullcalendar', false);
    }

    public function test_reminder_commands_are_scheduled(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('notify:application-deadlines')
            ->expectsOutputToContain('notify:document-deadlines')
            ->assertSuccessful();
    }

    public function test_document_reminder_sends_one_email_per_student_with_all_missing_documents(): void
    {
        Mail::fake();

        $year = AcademicYear::create(['name' => '2026/2027', 'start_year' => 2026, 'end_year' => 2027]);
        $university = University::create(['name' => 'Politecnico di Milano', 'address' => 'Milan', 'email' => 'info@polimi.example', 'phone' => '1', 'website_url' => 'https://polimi.example']);
        $passport = DocumentType::create(['name' => 'Passport']);
        $diploma = DocumentType::create(['name' => 'Diploma']);

        foreach ([$passport, $diploma] as $type) {
            DocumentDeadline::create([
                'academic_year_id' => $year->id,
                'university_id' => $university->id,
                'document_type_id' => $type->id,
                'education_level' => 'bachelor',
                'deadline' => now()->addDays(10)->toDateString(),
            ]);
        }

        $student = $this->makeStudent();

        foreach (['Engineering', 'Architecture'] as $name) {
            $major = Major::create(['name' => $name, 'type' => 'bachelor']);
            $program = Program::create([
                'university_id' => $university->id,
                'major_id' => $major->id,
                'academic_year_id' => $year->id,
                'application_deadline' => now()->addDays(15)->toDateString(),
                'application_fee' => 30,
            ]);
            Application::create(['student_id' => $student->id, 'program_id' => $program->id, 'status' => 'pending']);
        }

        $this->artisan('notify:document-deadlines')->assertSuccessful();

        Mail::assertSent(MissingDocumentsReminder::class, 1);
        Mail::assertSent(MissingDocumentsReminder::class, fn ($mail) => $mail->hasTo($student->user->email)
            && $mail->missingDocs->pluck('document_name')->sort()->values()->all() === ['Diploma', 'Passport']);
    }
}

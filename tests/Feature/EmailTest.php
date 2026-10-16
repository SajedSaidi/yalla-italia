<?php

namespace Tests\Feature;

use App\Mail\ApplicationDeadlineReminder;
use App\Mail\MissingDocumentsReminder;
use App\Mail\NewUserRegistration;
use App\Mail\UserApproved;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Support\SafeMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class EmailTest extends TestCase
{
    use RefreshDatabase;

    private function assertBranded(string $html): void
    {
        $this->assertStringContainsString(asset('images/logo-email.png'), $html);
        $this->assertStringContainsString(config('mail.from.name'), $html);
        $this->assertStringContainsString('#059669', $html, 'Emerald brand colour from the yalla theme.');
        $this->assertStringContainsString('flag-green', $html);
    }

    public function test_every_email_renders_with_the_branded_layout(): void
    {
        $student = $this->makeStudent(['name' => 'Mario Rossi']);

        $mailables = [
            new UserApproved($student->user),
            new NewUserRegistration($student->user),
            new ApplicationDeadlineReminder('Mario Rossi', collect([[
                'title' => 'Politecnico – Engineering',
                'deadline' => now()->addDays(5),
                'fee' => 30,
                'days_left' => 5,
            ]])),
            new MissingDocumentsReminder('Mario Rossi', collect([[
                'education_level' => 'Bachelor',
                'university' => 'Politecnico',
                'academic_year' => '2026/2027',
                'document_name' => 'Passport',
                'deadline' => 'Nov 1, 2026',
            ]])),
        ];

        foreach ($mailables as $mailable) {
            $html = $mailable->render();
            $this->assertBranded($html);
            $this->assertStringContainsString('Mario Rossi', $html);
            $this->assertNotInstanceOf(ShouldQueue::class, $mailable, $mailable::class . ' should be sent immediately.');
        }

        $reset = (new ResetPasswordNotification('token'));
        $reset->url = 'https://example.test/reset';
        $html = (string) $reset->toMail($student->user)->render();
        $this->assertBranded($html);
        $this->assertStringContainsString('https://example.test/reset', $html);
    }

    public function test_registration_email_links_to_the_student_page(): void
    {
        $student = $this->makeStudent();

        $html = (new NewUserRegistration($student->user))->render();

        $this->assertStringContainsString("/admin/students/{$student->id}", $html);
    }

    public function test_sent_emails_embed_the_logo_inline(): void
    {
        $student = $this->makeStudent();

        Mail::to('someone@example.com')->send(new UserApproved($student->user));

        $sent = app('mailer')->getSymfonyTransport()->messages()->last()->getOriginalMessage();

        $this->assertStringContainsString('cid:yalla-logo', $sent->getHtmlBody());
        $this->assertStringNotContainsString(asset('images/logo-email.png'), $sent->getHtmlBody());
        $this->assertCount(1, $sent->getAttachments());
        $this->assertSame('image/png', $sent->getAttachments()[0]->getMediaType() . '/' . $sent->getAttachments()[0]->getMediaSubtype());
    }

    public function test_safe_mail_reports_failures_instead_of_throwing(): void
    {
        $student = $this->makeStudent();
        Mail::shouldReceive('to')->andThrow(new TransportException('SMTP down'));

        $this->assertFalse(SafeMail::send('someone@example.com', new UserApproved($student->user)));
    }

    public function test_approval_email_is_sent_through_the_model_only_on_approval(): void
    {
        Mail::fake();
        $user = User::factory()->student(false)->create();

        $user->update(['name' => 'Just a rename']);
        Mail::assertNothingSent();

        $user->approve();
        Mail::assertSent(UserApproved::class, 1);

        $user->update(['name' => 'Rename again']);
        Mail::assertSent(UserApproved::class, 1);
    }
}

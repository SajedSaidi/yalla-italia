<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Mail\NewUserRegistration;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Filament\Facades\Filament;
use Filament\Pages\Auth\PasswordReset\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_offers_remember_me_and_forgot_password(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Remember me')
            ->assertSee('Forgot password?')
            ->assertSee(route('filament.admin.auth.password-reset.request'), false);
    }

    public function test_remember_me_issues_a_long_lived_remember_cookie(): void
    {
        $user = User::factory()->manager()->create(['remember_token' => null]);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password', 'remember' => true])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect(Filament::getUrl());

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);

        $recaller = collect(app('cookie')->getQueuedCookies())
            ->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));

        $this->assertNotNull($recaller, 'A remember_web_* cookie should be queued.');
        $this->assertGreaterThan(now()->addYear()->timestamp, $recaller->getExpiresTime());
    }

    public function test_login_without_remember_me_does_not_queue_a_remember_cookie(): void
    {
        $user = User::factory()->manager()->create(['remember_token' => null]);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password', 'remember' => false])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->remember_token);
    }

    public function test_unapproved_student_cannot_sign_in_and_sees_why(): void
    {
        $student = $this->makeStudent(approved: false);

        Livewire::test(Login::class)
            ->fillForm(['email' => $student->user->email, 'password' => 'password', 'remember' => true])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->manager()->create();

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'wrong-password'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_forgot_password_sends_the_branded_reset_email(): void
    {
        Notification::fake();
        $user = User::factory()->manager()->create();

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => $user->email])
            ->call('request')
            ->assertNotified('If an account exists for this email, a password reset link is on its way.');

        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
            $mail = $notification->toMail($user);

            return str_contains($notification->url, '/admin/password-reset/reset')
                && $mail->subject === 'Reset your password – ' . config('mail.from.name')
                && $mail->markdown === 'emails.auth.reset-password';
        });

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_forgot_password_does_not_reveal_unknown_emails(): void
    {
        Notification::fake();

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => 'nobody@example.com'])
            ->call('request')
            ->assertNotified('If an account exists for this email, a password reset link is on its way.');

        Notification::assertNothingSent();
    }

    public function test_reset_email_is_sent_immediately_not_queued(): void
    {
        $this->assertNotInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, new ResetPasswordNotification('token'));
    }

    public function test_user_can_reset_password_with_the_emailed_token_and_sign_in(): void
    {
        $user = User::factory()->manager()->create();
        $token = Password::broker()->createToken($user);

        Livewire::test(ResetPassword::class, ['email' => $user->email, 'token' => $token])
            ->fillForm([
                'password' => 'new-secret-password',
                'passwordConfirmation' => 'new-secret-password',
            ])
            ->call('resetPassword')
            ->assertHasNoFormErrors()
            ->assertRedirect(Filament::getLoginUrl());

        $this->assertTrue(Hash::check('new-secret-password', $user->fresh()->password));

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'new-secret-password'])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_always_creates_a_pending_student_and_notifies_staff(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();

        Livewire::test(Register::class)
            ->fillForm([
                // Tampered hidden field: must be ignored.
                'role' => 'admin',
                'name' => 'New Student',
                'email' => 'new.student@example.com',
                'password' => 'password123',
                'phone' => '123456',
                'date_of_birth' => '2001-05-05',
                'place_of_birth' => 'Tripoli',
                'address' => 'Somewhere 1',
                'nationality_id' => $this->nationality()->id,
            ])
            ->call('register')
            ->assertHasNoFormErrors()
            ->assertRedirect(Filament::getLoginUrl());

        $user = User::where('email', 'new.student@example.com')->firstOrFail();

        $this->assertSame('student', $user->role);
        $this->assertFalse($user->is_approved);
        $this->assertNotNull($user->student);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertGuest();

        Mail::assertSent(NewUserRegistration::class, fn ($mail) => $mail->hasTo($admin->email));
        Mail::assertSent(NewUserRegistration::class, fn ($mail) => $mail->hasTo($manager->email));
    }
}

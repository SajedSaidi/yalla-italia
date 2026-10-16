<?php

namespace App\Filament\Pages\Auth;

use App\Notifications\ResetPasswordNotification;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    public function request(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $data = $this->form->getState();

        try {
            $status = Password::broker(Filament::getAuthPasswordBroker())->sendResetLink(
                $data,
                function (CanResetPassword $user, string $token): void {
                    $notification = new ResetPasswordNotification($token);
                    $notification->url = Filament::getResetPasswordUrl($token, $user);

                    $user->notify($notification);
                },
            );
        } catch (TransportExceptionInterface $exception) {
            // The email is sent synchronously: a mail server outage must not become an error page.
            report($exception);

            Notification::make()
                ->title('We could not send the reset email right now. Please try again in a few minutes.')
                ->danger()
                ->send();

            return;
        }

        if (! in_array($status, [Password::RESET_LINK_SENT, Password::INVALID_USER, Password::RESET_THROTTLED], true)) {
            Notification::make()
                ->title(__($status))
                ->danger()
                ->send();

            return;
        }

        // Same answer whether or not the address exists, so the form cannot be used to discover accounts.
        Notification::make()
            ->title('If an account exists for this email, a password reset link is on its way.')
            ->body('Check your inbox (and your spam folder).')
            ->success()
            ->send();

        $this->form->fill();
    }
}

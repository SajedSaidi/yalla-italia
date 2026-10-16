<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Sent synchronously (not queued) so the link arrives right away
 * without depending on a queue worker.
 */
class ResetPasswordNotification extends ResetPassword
{
    public string $url;

    protected function resetUrl($notifiable): string
    {
        return $this->url;
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset your password – ' . config('mail.from.name'))
            ->markdown('emails.auth.reset-password', [
                'name' => $notifiable->name,
                'url' => $this->resetUrl($notifiable),
                'expires' => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire'),
            ]);
    }
}

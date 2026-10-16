<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your account has been approved – ' . config('mail.from.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.user.approved',
            with: [
                'user' => $this->user,
                'loginUrl' => route('filament.admin.auth.login'),
            ],
        );
    }
}

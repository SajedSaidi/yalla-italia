<?php
// filepath: c:\Users\sajed\OneDrive\Desktop\yalla-italia\app\Mail\UserApproved.php

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
            subject: 'Your Account Has Been Approved - Yalla Italia',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user.approved',
            with: [
                'user' => $this->user,
                'loginUrl' => route('filament.admin.auth.login'),
            ],
        );
    }
}

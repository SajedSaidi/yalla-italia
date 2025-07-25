<?php
// filepath: c:\Users\sajed\OneDrive\Desktop\yalla-italia\app\Mail\NewUserRegistration.php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewUserRegistration extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New User Registration Requires Approval - Yalla Italia',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.new-user-registration',
            with: [
                'user' => $this->user,
                'approvalUrl' => route('filament.admin.resources.users.edit', $this->user),
            ],
        );
    }
}

<?php

namespace App\Mail;

use App\Filament\Resources\StudentResource;
use App\Filament\Resources\UserResource;
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
            subject: "New registration awaiting approval: {$this->user->name}",
        );
    }

    public function content(): Content
    {
        // Students are reviewed from the Students page, which both admins and managers can open.
        $approvalUrl = $this->user->student
            ? StudentResource::getUrl('view', ['record' => $this->user->student], panel: 'admin')
            : UserResource::getUrl('edit', ['record' => $this->user], panel: 'admin');

        return new Content(
            markdown: 'emails.admin.new-user-registration',
            with: [
                'user' => $this->user,
                'approvalUrl' => $approvalUrl,
            ],
        );
    }
}

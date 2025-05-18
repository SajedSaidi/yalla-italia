<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class MissingDocumentsReminder extends Mailable
{
    use Queueable, SerializesModels;

    public string $studentName;
    public Collection $missingDocs;

    public function __construct(string $studentName, Collection $missingDocs)
    {
        $this->studentName = $studentName;
        $this->missingDocs = $missingDocs;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Action Required: Missing Application Documents',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.documents.missing',  // your pure-HTML Blade template
            with: [
                'studentName' => $this->studentName,
                'missingDocs' => $this->missingDocs,
            ],
        );
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ApplicationDeadlineReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The student's display name.
     *
     * @var string
     */
    public string $studentName;

    /**
     * Collection of upcoming programs.
     *
     * Each item should be:
     * [
     *   'title'     => (string),
     *   'deadline'  => (\Illuminate\Support\Carbon),
     *   'fee'       => (float),
     *   'days_left' => (int),
     * ]
     *
     * @var Collection
     */
    public Collection $programs;

    /**
     * Create a new message instance.
     *
     * @param  string     $studentName
     * @param  Collection $programs
     * @return void
     */
    public function __construct(string $studentName, Collection $programs)
    {
        $this->studentName = $studentName;
        $this->programs    = $programs;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: Upcoming Application Deadlines – Yalla Italia',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.deadline.reminder',
            with: [
                'studentName' => $this->studentName,
                'programs'    => $this->programs,
            ],
        );
    }

    /**
     * No attachments.
     *
     * @return array<int,\Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

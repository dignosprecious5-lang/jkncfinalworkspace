<?php

namespace App\Mail;

use App\Models\CandidateInterview;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewScheduleMail extends Mailable
{
    use Queueable, SerializesModels;

    public CandidateInterview $interview;

    public function __construct(CandidateInterview $interview)
    {
        $this->interview = $interview;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Interview Schedule - John Kelly & Company (JK&C Inc.)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.interview-schedule',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

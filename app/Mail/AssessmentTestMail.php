<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AssessmentTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $candidateName;
    public $testType;
    public $testUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($candidateName, $testType, $testUrl)
    {
        $this->candidateName = $candidateName;
        $this->testType = $testType;
        $this->testUrl = $testUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Assessment Test Invitation - John Kelly & Company (JK&C Inc.)',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.assessment-test',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

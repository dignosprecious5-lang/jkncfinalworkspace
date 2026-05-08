<?php

namespace App\Mail;

use App\Models\OnboardingChecklist;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChecklistSubmissionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $checklist;
    public $uploadUrl;

    public function __construct(OnboardingChecklist $checklist, string $uploadUrl)
    {
        $this->checklist = $checklist;
        $this->uploadUrl = $uploadUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pre-employment Requirements Upload - John Kelly & Company',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.checklist-submission',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

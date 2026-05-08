<?php

namespace App\Mail;

use App\Models\JobOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PdsInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $jobOffer;
    public $pdsUrl;

    public function __construct(JobOffer $jobOffer, string $pdsUrl)
    {
        $this->jobOffer = $jobOffer;
        $this->pdsUrl = $pdsUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'PDS Form Invitation - John Kelly & Company',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pds-invitation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

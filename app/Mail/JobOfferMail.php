<?php

namespace App\Mail;

use App\Models\CandidateInterview;
use App\Models\JobOffer;
use App\Models\JobPosting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobOfferMail extends Mailable
{
    use Queueable, SerializesModels;

    public $jobOffer;
    public $interview;
    public $jpf;

    public function __construct(JobOffer $jobOffer, ?CandidateInterview $interview = null, ?JobPosting $jpf = null)
    {
        $this->jobOffer = $jobOffer;
        $this->interview = $interview;
        $this->jpf = $jpf;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Job Offer - John Kelly & Company (JK&C Inc.)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.job-offer',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

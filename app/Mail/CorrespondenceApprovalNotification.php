<?php

namespace App\Mail;

use App\Models\Correspondence;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CorrespondenceApprovalNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Correspondence $correspondence,
        public string $level,
        public string $subjectLine,
        public string $messageText,
        public string $actionUrl
    ) {}

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->view('emails.correspondence.approval-notification')
            ->with([
                'correspondence' => $this->correspondence,
                'level' => $this->level,
                'messageText' => $this->messageText,
                'actionUrl' => $this->actionUrl,
            ]);
    }
}

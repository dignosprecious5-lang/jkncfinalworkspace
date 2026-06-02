<?php

namespace App\Mail;

use App\Models\Notice;
use App\Models\NoticeAttendee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NoticeOfMeetingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Notice $notice, public NoticeAttendee $attendee, public string $pdfBinary, public string $filename) {}

    public function build()
    {
        return $this->subject('Notice of Meeting - ' . ($this->notice->notice_number ?: 'Notice'))
            ->view('emails.notice-of-meeting')
            ->with(['notice' => $this->notice, 'attendee' => $this->attendee])
            ->attachData($this->pdfBinary, $this->filename, ['mime' => 'application/pdf']);
    }
}

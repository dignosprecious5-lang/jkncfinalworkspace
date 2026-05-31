<?php

namespace App\Mail;

use App\Models\TownHallCommunication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TownHallPostedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public TownHallCommunication $communication;
    public string $pdfBinary;
    public string $filename;

    public function __construct(TownHallCommunication $communication, string $pdfBinary, string $filename)
    {
        $this->communication = $communication;
        $this->pdfBinary = $pdfBinary;
        $this->filename = $filename;
    }

    public function build()
    {
        return $this
            ->subject('Posted Communication: ' . ($this->communication->subject ?: $this->communication->ref_no))
            ->view('emails.townhall-posted')
            ->attachData($this->pdfBinary, $this->filename, [
                'mime' => 'application/pdf',
            ]);
    }
}

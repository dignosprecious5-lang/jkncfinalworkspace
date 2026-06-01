<?php

namespace App\Mail;

use App\Models\TownHallCommunication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class TownHallApprovalRequestNotification extends Mailable
{
    use Queueable, SerializesModels;

    public TownHallCommunication $communication;
    public string $pdfBinary;
    public string $filename;
    public string $level;
    public int $approverUserId;
    public string $levelLabel;
    public string $approveUrl;
    public string $rejectUrl;
    public string $approvalPageUrl;

    public function __construct(
        TownHallCommunication $communication,
        string $pdfBinary,
        string $filename,
        string $level,
        int $approverUserId
    ) {
        $this->communication = $communication;
        $this->pdfBinary = $pdfBinary;
        $this->filename = $filename;
        $this->level = $level;
        $this->approverUserId = $approverUserId;
        $this->levelLabel = $level === 'executive'
            ? 'Level 2 - From Executive Management'
            : 'Level 1 - From Management';

        $this->approveUrl = URL::signedRoute('townhall.email.approve', [
            'id' => $communication->id,
            'level' => $level,
            'approver' => $approverUserId,
        ]);

        $this->rejectUrl = URL::signedRoute('townhall.email.reject', [
            'id' => $communication->id,
            'level' => $level,
            'approver' => $approverUserId,
        ]);

        $this->approvalPageUrl = route('townhall.show', $communication->id);
    }

    public function build()
    {
        return $this
            ->subject('Approval Request: ' . ($this->communication->subject ?: $this->communication->ref_no))
            ->view('emails.townhall-approval-request')
            ->attachData($this->pdfBinary, $this->filename, [
                'mime' => 'application/pdf',
            ]);
    }
}

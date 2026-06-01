<?php

namespace App\Mail;

use App\Models\FinanceRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SupplierRevertMail extends Mailable
{
    use Queueable, SerializesModels;

    public FinanceRecord $record;
    public string $reason;
    public ?string $revertedByName;
    public ?string $completionUrl;
    public ?string $pdfData;
    public ?string $pdfFilename;

    public function __construct(FinanceRecord $record, string $reason, ?string $revertedByName = null, ?string $completionUrl = null, ?string $pdfData = null, ?string $pdfFilename = null)
    {
        $this->record = $record;
        $this->reason = $reason;
        $this->revertedByName = $revertedByName;
        $this->completionUrl = $completionUrl;
        $this->pdfData = $pdfData;
        $this->pdfFilename = $pdfFilename;
    }

    public function build()
    {
        $mail = $this->subject('Supplier Information Reverted - ' . ($this->record->record_title ?: 'Supplier Record'))
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('emails.finance-supplier-revert')
            ->with([
                'record' => $this->record,
                'reason' => $this->reason,
                'revertedByName' => $this->revertedByName,
                'completionUrl' => $this->completionUrl,
            ]);

        if ($this->pdfData && $this->pdfFilename) {
            $mail->attachData($this->pdfData, $this->pdfFilename, [
                'mime' => 'application/pdf',
            ]);
        }

        foreach ((array) ($this->record->attachments ?? []) as $attachment) {
            $path = (string) data_get($attachment, 'path', '');
            $storedPath = ltrim(str_replace('storage/', '', $path), '/\\');

            if ($storedPath && Storage::disk('public')->exists($storedPath)) {
                $options = [];
                if ($mime = data_get($attachment, 'mime')) {
                    $options['mime'] = $mime;
                }

                $mail->attachFromStorageDisk('public', $storedPath, data_get($attachment, 'name'), $options);
            }
        }

        return $mail;
    }
}

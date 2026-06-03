<?php

namespace App\Mail;

use App\Models\FinanceRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SupplierCompletionMail extends Mailable
{
    use Queueable, SerializesModels;

    public FinanceRecord $record;
    public string $completionUrl;
    public ?string $pdfData;
    public ?string $pdfFilename;
    public array $supplierLabels;

    public function __construct(FinanceRecord $record, string $completionUrl, ?string $pdfData = null, ?string $pdfFilename = null, array $supplierLabels = [])
    {
        $this->record = $record;
        $this->completionUrl = $completionUrl;
        $this->pdfData = $pdfData;
        $this->pdfFilename = $pdfFilename;
        $this->supplierLabels = $supplierLabels;
    }

    public function build()
    {
        $mail = $this->subject('Complete Supplier Information - ' . ($this->record->record_title ?: 'Supplier Record'))
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('emails.finance-supplier-completion', [
                'supplierLabels' => $this->supplierLabels,
                'logoUrl' => 'https://assets.zyrosite.com/cdn-cgi/image/format=auto,w=375,fit=crop,q=95/mjEqWrZkyrh3rqK0/1-mv02o8k9OrfMW0oZ.png',
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

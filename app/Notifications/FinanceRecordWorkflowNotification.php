<?php

namespace App\Notifications;

use App\Models\FinanceRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

class FinanceRecordWorkflowNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $recordId,
        public string $title,
        public string $body,
        public string $buttonLabel,
        public string $url,
        public ?string $reviewNote = null,
        public ?string $pdfData = null,
        public ?string $pdfFilename = null
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = [];

        if (Schema::hasTable('notifications')) {
            $channels[] = 'database';
        }

        if (filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $record = FinanceRecord::query()->find($this->recordId);
        $mail = (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($this->title)
            ->greeting('Hi ' . ($notifiable->name ?: 'there') . ',')
            ->line($this->body)
            ->line('Record: ' . ($record?->record_number ?: 'N/A') . ' - ' . ($record?->record_title ?: $record?->module_key ?: 'Finance Record'))
            ->line('Status: ' . ($record?->workflow_status ?: 'N/A') . ' / ' . ($record?->approval_status ?: 'N/A'));

        if (filled($this->reviewNote)) {
            $mail->line('Review note: ' . $this->reviewNote);
        }

        $mail
            ->action($this->buttonLabel, $this->url)
            ->line('For concerns, please contact the Finance Department of JK&C Inc.');

        if ($this->pdfData && $this->pdfFilename) {
            $mail->attachData($this->pdfData, $this->pdfFilename, [
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'record_id' => $this->recordId,
            'title' => $this->title,
            'body' => $this->body,
            'button_label' => $this->buttonLabel,
            'url' => $this->url,
            'review_note' => $this->reviewNote,
        ];
    }
}

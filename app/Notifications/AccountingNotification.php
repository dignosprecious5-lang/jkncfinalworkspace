<?php

namespace App\Notifications;

use App\Models\Accounting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

class AccountingNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $recordId,
        public string $action,
        public string $title,
        public string $body,
        public string $buttonLabel,
        public string $url,
        public ?string $reviewNote = null
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
        $record = Accounting::query()->find($this->recordId);
        $mail = (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($this->title)
            ->greeting('Hi ' . ($notifiable->name ?: 'there') . ',')
            ->line($this->body)
            ->line('Statement Type: ' . ($record?->statement_type ?: 'N/A'))
            ->line('Client: ' . ($record?->client ?: 'N/A'))
            ->line('Date: ' . optional($record?->date)->format('Y-m-d') ?: 'N/A')
            ->line('Status: ' . ($record?->workflow_status ?: 'N/A') . ' / ' . ($record?->approval_status ?: 'N/A'));

        if (filled($this->reviewNote)) {
            $mail->line('Review Note: ' . $this->reviewNote);
        }

        $mail
            ->action($this->buttonLabel, $this->url)
            ->line('For concerns, please contact the Corporate/Accounting Department of JK&C Inc.');

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'record_id' => $this->recordId,
            'action' => $this->action,
            'title' => $this->title,
            'body' => $this->body,
            'button_label' => $this->buttonLabel,
            'url' => $this->url,
            'review_note' => $this->reviewNote,
        ];
    }
}

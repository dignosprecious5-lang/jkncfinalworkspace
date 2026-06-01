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
        public string $action,
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
        $action = strtolower($this->action);
        [$accentColor, $accentSoftColor, $badgeLabel] = match ($action) {
            'approved' => ['#15803d', '#dcfce7', 'Approved'],
            'partially_approved',
            'submitted',
            'supplier_submitted',
            'updated' => ['#1d4ed8', '#dbeafe', 'For Review'],
            'held' => ['#b45309', '#fef3c7', 'On Hold'],
            'reverted',
            'delete_requested',
            'delete_rejected' => ['#b91c1c', '#fee2e2', 'Needs Attention'],
            'delete_approved',
            'archived',
            'unarchived' => ['#1f2937', '#e5e7eb', 'Finance Update'],
            default => ['#1d4ed8', '#dbeafe', 'Finance Update'],
        };
        $mail = (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($this->title)
            ->view('emails.finance-workflow-notification', [
                'notifiableName' => $notifiable->name ?: 'there',
                'title' => $this->title,
                'body' => $this->body,
                'buttonLabel' => $this->buttonLabel,
                'url' => $this->url,
                'reviewNote' => $this->reviewNote,
                'recordNumber' => $record?->record_number ?: 'N/A',
                'recordTitle' => $record?->record_title ?: $record?->module_key ?: 'Finance Record',
                'workflowStatus' => $record?->workflow_status ?: 'N/A',
                'approvalStatus' => $record?->approval_status ?: 'N/A',
                'accentColor' => $accentColor,
                'accentSoftColor' => $accentSoftColor,
                'badgeLabel' => $badgeLabel,
                'logoUrl' => 'https://assets.zyrosite.com/cdn-cgi/image/format=auto,w=375,fit=crop,q=95/mjEqWrZkyrh3rqK0/1-mv02o8k9OrfMW0oZ.png',
            ]);

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

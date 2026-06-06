<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class CorporateApprovalSubmissionNotification extends SystemRealtimeNotification
{
    public function __construct(
        string $title,
        string $message,
        ?string $url,
        public string $moduleName,
        public string $recordTitle,
        public string $submitterName
    ) {
        parent::__construct($title, $message, $url, 'Corporate', 'fa-building');
    }

    public function via(object $notifiable): array
    {
        $channels = parent::via($notifiable);

        if (filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return array_values(array_unique($channels));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($this->moduleName . ' Submitted for Approval')
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line($this->message)
            ->line('Module: ' . $this->moduleName)
            ->line('Submitted by: ' . $this->submitterName)
            ->when($this->recordTitle !== '', fn (MailMessage $mail) => $mail->line('Record: ' . $this->recordTitle))
            ->action('Open Corporate Approval Dashboard', $this->url ?: route('admin.corporate.dashboard'))
            ->line('Please review the submitted record when available.');
    }

    public function toDatabase(object $notifiable): array
    {
        return array_merge(parent::toDatabase($notifiable), [
            'record_title' => $this->recordTitle,
            'submitted_by_name' => $this->submitterName,
        ]);
    }
}

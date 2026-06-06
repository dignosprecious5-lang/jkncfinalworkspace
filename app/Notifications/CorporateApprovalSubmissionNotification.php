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
        $dashboardUrl = $this->url ?: route('admin.corporate.dashboard');

        return (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($this->moduleName . ' Submitted for Approval')
            ->view('emails.branded-workflow-notification', [
                'logoUrl' => rtrim((string) config('app.url'), '/') . '/images/imaglogo.png',
                'notifiableName' => $notifiable->name ?? 'there',
                'title' => $this->moduleName . ' Submitted for Approval',
                'body' => $this->message,
                'moduleName' => $this->moduleName,
                'recordTitle' => $this->recordTitle,
                'actorName' => $this->submitterName,
                'reviewNote' => null,
                'url' => $dashboardUrl,
                'buttonLabel' => 'Open Corporate Approval Dashboard',
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return array_merge(parent::toDatabase($notifiable), [
            'record_title' => $this->recordTitle,
            'submitted_by_name' => $this->submitterName,
        ]);
    }
}

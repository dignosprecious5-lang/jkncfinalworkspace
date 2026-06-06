<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class HumanCapitalWorkflowNotification extends SystemRealtimeNotification
{
    public function __construct(
        string $title,
        string $message,
        ?string $url,
        public string $humanCapitalModule = 'Human Capital',
        public string $recordTitle = '',
        public string $actorName = ''
    ) {
        parent::__construct($title, $message, $url, $humanCapitalModule, 'fa-users');
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
        $dashboardUrl = $this->url ?: route('admin.human-capital.dashboard');

        return (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($this->title)
            ->view('emails.branded-workflow-notification', [
                'logoUrl' => rtrim((string) config('app.url'), '/') . '/images/imaglogo.png',
                'notifiableName' => $notifiable->name ?? 'there',
                'title' => $this->title,
                'body' => $this->message,
                'moduleName' => $this->humanCapitalModule,
                'recordTitle' => $this->recordTitle,
                'actorName' => $this->actorName,
                'reviewNote' => null,
                'url' => $dashboardUrl,
                'buttonLabel' => 'Open Human Capital Dashboard',
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return array_merge(parent::toDatabase($notifiable), [
            'record_title' => $this->recordTitle,
            'actor_name' => $this->actorName,
        ]);
    }
}

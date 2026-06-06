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
        return (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($this->title)
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line($this->message)
            ->line('Module: ' . $this->humanCapitalModule)
            ->when($this->actorName !== '', fn (MailMessage $mail) => $mail->line('Submitted by: ' . $this->actorName))
            ->when($this->recordTitle !== '', fn (MailMessage $mail) => $mail->line('Record: ' . $this->recordTitle))
            ->action('Open Human Capital Dashboard', $this->url ?: route('admin.human-capital.dashboard'))
            ->line('This notification was also sent to your in-system notifications.');
    }

    public function toDatabase(object $notifiable): array
    {
        return array_merge(parent::toDatabase($notifiable), [
            'record_title' => $this->recordTitle,
            'actor_name' => $this->actorName,
        ]);
    }
}

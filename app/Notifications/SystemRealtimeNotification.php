<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class SystemRealtimeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public ?string $url = null,
        public string $module = 'System',
        public string $icon = 'fa-bell'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'module' => $this->module,
            'icon' => $this->icon,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'module' => $this->module,
            'icon' => $this->icon,
            'created_at' => now()->diffForHumans(),
        ]);
    }

    public function broadcastType(): string
    {
        return 'system.notification';
    }
}
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkspaceUpdatedNotification extends Notification
{
    use Queueable;

    protected string $type;
    protected int|string $recordId;
    protected string $recordName;
    protected string $updatedBy;
    protected string $url;

    public function __construct(
        string $type,
        int|string $recordId,
        string $recordName,
        string $updatedBy,
        string $url
    ) {
        $this->type = $type;
        $this->recordId = $recordId;
        $this->recordName = $recordName;
        $this->updatedBy = $updatedBy;
        $this->url = $url;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => ucfirst($this->type) . ' Workspace Updated',
            'message' => $this->updatedBy
                . ' updated the '
                . $this->type
                . ' workspace: '
                . $this->recordName
                . '.',
            'record_type' => $this->type,
            'record_id' => $this->recordId,
            'url' => $this->url,
        ];
    }
}
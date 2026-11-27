<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\TargetedWorkflowNotification;
use Illuminate\Support\Collection;

class TargetedNotificationService
{
    /**
     * Notify specific assigned user(s) for a task, deal, engagement, or workflow step.
     * GUARANTEE: Only the specified assigned user(s) receive the notification.
     * No broadcast to unrelated users or User::all().
     */
    public static function notifyAssignedUsers(
        mixed $recipients,
        string $title,
        string $message,
        ?string $url = null,
        string $module = 'Workflow',
        ?string $icon = 'fa-user-check',
        ?string $actionLabel = 'Open Assignment',
        ?int $assignedByUserId = null
    ): void {
        $users = collect();

        if ($recipients instanceof User) {
            $users->push($recipients);
        } elseif (is_numeric($recipients)) {
            $u = User::find($recipients);
            if ($u) {
                $users->push($u);
            }
        } elseif (is_array($recipients) || $recipients instanceof Collection) {
            foreach ($recipients as $item) {
                if ($item instanceof User) {
                    $users->push($item);
                } elseif (is_numeric($item)) {
                    $u = User::find($item);
                    if ($u) {
                        $users->push($u);
                    }
                } elseif (is_string($item) && trim($item) !== '') {
                    $trimmed = trim($item);
                    $u = User::where('name', $trimmed)->orWhere('email', $trimmed)->first();
                    if ($u) {
                        $users->push($u);
                    }
                }
            }
        } elseif (is_string($recipients) && trim($recipients) !== '') {
            $trimmed = trim($recipients);
            $u = User::where('name', $trimmed)->orWhere('email', $trimmed)->first();
            if ($u) {
                $users->push($u);
            }
        }

        $users = $users->unique('id')->values();

        if ($users->isEmpty()) {
            return;
        }

        $assignedByName = null;
        if ($assignedByUserId) {
            $assignedByName = User::find($assignedByUserId)?->name;
        } elseif (auth()->check()) {
            $assignedByName = auth()->user()->name;
        }

        foreach ($users as $user) {
            try {
                $user->notify(new TargetedWorkflowNotification([
                    'title' => $title,
                    'message' => $message,
                    'url' => $url ?: '#',
                    'module' => $module,
                    'icon' => $icon ?: 'fa-user-check',
                    'button_label' => $actionLabel ?: 'Open Assignment',
                    'assigned_by' => $assignedByName ?: 'System Administrator',
                ]));
            } catch (\Throwable $e) {
                // Silently skip if notification dispatch fails
            }
        }
    }
}

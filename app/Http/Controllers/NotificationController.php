<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function unread(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notification',
                'message' => $notification->data['message'] ?? ($notification->data['body'] ?? ''),
                'url' => $notification->data['url'] ?? ($notification->data['action_url'] ?? ($notification->data['link'] ?? '#')),
                'module' => $notification->data['module'] ?? ($notification->data['module_name'] ?? 'System'),
                'icon' => $notification->data['icon'] ?? 'fa-bell',
                'button_label' => $notification->data['button_label'] ?? 'Open',
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at?->diffForHumans(),
            ])
            ->values();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $notification): JsonResponse|RedirectResponse
    {
        $item = $request->user()
            ->notifications()
            ->where('id', $notification)
            ->firstOrFail();

        if (is_null($item->read_at)) {
            $item->markAsRead();
        }

        $unreadCount = $request->user()->unreadNotifications()->count();

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'unread_count' => $unreadCount,
                'url' => data_get($item->data, 'url') ?: null,
            ]);
        }

        return redirect(data_get($item->data, 'url') ?: url()->previous());
    }

    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'unread_count' => 0,
            ]);
        }

        return back();
    }
}
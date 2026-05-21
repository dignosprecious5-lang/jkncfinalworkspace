<?php

namespace App\Support;

use App\Models\ContactHistoryEntry;
use Illuminate\Support\Facades\Schema;

class ContactHistoryLogger
{
    public static function log(int $contactId, array $payload): void
    {
        if (! Schema::hasTable('contact_history_entries')) {
            return;
        }

        ContactHistoryEntry::query()->create([
            'contact_id' => $contactId,
            'type' => $payload['type'],
            'title' => $payload['title'],
            'description' => $payload['description'],
            'extra_label' => $payload['extra_label'] ?? null,
            'extra_value' => $payload['extra_value'] ?? null,
            'user_name' => $payload['user_name'],
            'user_initials' => $payload['user_initials'],
            'occurred_at' => $payload['occurred_at'] ?? now(),
        ]);
    }
}

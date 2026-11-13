<?php

namespace App\Models;

use App\Enums\TransmittalEmailDispatchAttemptEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/** Append-only sanitized attempt evidence. No request body, token, or file data. */
class TransmittalEmailDispatchAttempt extends Model
{
    protected $table = 'transmittal_email_dispatch_attempts';

    public $timestamps = false;

    protected $fillable = [
        'event_uuid',
        'attempt_uuid',
        'sequence_no',
        'dispatch_id',
        'event_type',
        'request_fingerprint',
        'provider_http_status',
        'provider_message_id',
        'sanitized_error_code',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => TransmittalEmailDispatchAttemptEvent::class,
            'occurred_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TransmittalEmailDispatchAttempt $event): void {
            $dispatch = TransmittalEmailDispatch::query()
                ->whereKey($event->dispatch_id)
                ->whereHas('transmittal', fn ($query) => $query->where('transmittals.workflow_version', 'v2'))
                ->first();

            if ($dispatch === null) {
                throw new LogicException('Attempt evidence must belong to an existing V2 dispatch.');
            }

            if (! $event->event_type instanceof TransmittalEmailDispatchAttemptEvent) {
                throw new LogicException('A known append-only attempt event type is required.');
            }

            if ($event->request_fingerprint !== null
                && (! is_string($event->request_fingerprint)
                    || preg_match('/\A[a-f0-9]{64}\z/i', $event->request_fingerprint) !== 1)) {
                throw new LogicException('Attempt request evidence must be a SHA-256 fingerprint.');
            }

            if ($event->sanitized_error_code !== null
                && preg_match('/\A[a-zA-Z0-9_.-]{1,80}\z/', (string) $event->sanitized_error_code) !== 1) {
                throw new LogicException('Attempt error evidence must use a sanitized code.');
            }
        });

        static::updating(fn () => throw new LogicException('Attempt evidence is append-only.'));
        static::deleting(fn () => throw new LogicException('Attempt evidence is append-only.'));
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(TransmittalEmailDispatch::class, 'dispatch_id');
    }
}

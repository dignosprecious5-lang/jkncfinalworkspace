<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/** Sanitized provider event; raw bodies and credentials are never retained. */
final class TransmittalProviderEvent extends Model
{
    protected $table = 'transmittal_provider_events';
    public $timestamps = false;
    protected $fillable = ['provider','provider_event_id','dispatch_uuid','provider_message_id','normalized_type',
        'provider_occurred_at','received_at','body_fingerprint','signature_fingerprint'];
    protected function casts(): array { return ['provider_occurred_at' => 'datetime', 'received_at' => 'datetime']; }
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Provider event evidence is append-only.'));
        static::deleting(fn () => throw new LogicException('Provider event evidence is retained.'));
    }
}

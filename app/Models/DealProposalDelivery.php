<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;

/** Mutable outbox state for one immutable issued proposal version. */
class DealProposalDelivery extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SUPERSEDED = 'superseded';

    private const TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_PROCESSING, self::STATUS_SUPERSEDED],
        self::STATUS_PROCESSING => [self::STATUS_PENDING, self::STATUS_SENT, self::STATUS_FAILED, self::STATUS_SUPERSEDED],
        self::STATUS_FAILED => [self::STATUS_PENDING, self::STATUS_SUPERSEDED],
        self::STATUS_SENT => [],
        self::STATUS_SUPERSEDED => [],
    ];

    private const MUTABLE_STATE_FIELDS = [
        'status',
        'attempt_count',
        'next_attempt_at',
        'claimed_at',
        'lease_expires_at',
        'sent_at',
        'last_error_code',
        'last_error_message',
    ];

    protected $fillable = [
        'deal_proposal_version_id',
        'idempotency_key',
        'recipient_email',
        'status',
        'attempt_count',
        'next_attempt_at',
        'claimed_at',
        'lease_expires_at',
        'sent_at',
        'last_error_code',
        'last_error_message',
    ];

    protected $casts = [
        'attempt_count' => 'integer',
        'next_attempt_at' => 'datetime',
        'claimed_at' => 'datetime',
        'lease_expires_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $delivery): void {
            $delivery->status ??= self::STATUS_PENDING;
            $delivery->attempt_count ??= 0;

            if ($delivery->status !== self::STATUS_PENDING || (int) $delivery->attempt_count < 0) {
                throw new DomainException('A new proposal delivery must start pending with a non-negative attempt count.');
            }

            if (filled($delivery->last_error_code)) {
                $delivery->last_error_code = self::sanitizeErrorCode((string) $delivery->last_error_code);
            }
            if (filled($delivery->last_error_message)) {
                $delivery->last_error_message = $delivery->sanitizeErrorMessage((string) $delivery->last_error_message);
            }
        });

        static::updating(static function (self $delivery): void {
            foreach (['deal_proposal_version_id', 'idempotency_key', 'recipient_email'] as $immutableField) {
                if ($delivery->isDirty($immutableField)) {
                    throw new LogicException("Proposal delivery identity field {$immutableField} is immutable.");
                }
            }

            if (! $delivery->isDirty('status')) {
                throw new LogicException('Proposal delivery state must change through a valid status transition.');
            }

            $from = (string) $delivery->getOriginal('status');
            $to = (string) $delivery->status;
            if (! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
                throw new DomainException("Invalid proposal delivery transition from {$from} to {$to}.");
            }
        });

        static::deleting(static function (): never {
            throw new LogicException('Proposal delivery outbox records cannot be deleted through the application.');
        });
    }

    public function proposalVersion(): BelongsTo
    {
        return $this->belongsTo(DealProposalVersion::class, 'deal_proposal_version_id');
    }

    /** Transition the mutable delivery state while keeping version identity fixed. */
    public function transitionTo(string $status, array $attributes = []): self
    {
        $unknownAttributes = array_diff(array_keys($attributes), array_diff(self::MUTABLE_STATE_FIELDS, ['status']));
        if ($unknownAttributes !== []) {
            throw new InvalidArgumentException('Only delivery state and attempt metadata can be changed.');
        }

        if (! in_array($status, self::TRANSITIONS[(string) $this->status] ?? [], true)) {
            throw new DomainException("Invalid proposal delivery transition from {$this->status} to {$status}.");
        }

        if (array_key_exists('last_error_code', $attributes) && $attributes['last_error_code'] !== null) {
            $attributes['last_error_code'] = self::sanitizeErrorCode((string) $attributes['last_error_code']);
        }

        if (array_key_exists('last_error_message', $attributes) && $attributes['last_error_message'] !== null) {
            $attributes['last_error_message'] = $this->sanitizeErrorMessage((string) $attributes['last_error_message']);
        }

        $this->forceFill($attributes);
        $this->status = $status;
        $this->save();

        return $this;
    }

    private function sanitizeErrorMessage(string $message): string
    {
        $message = strip_tags($message);
        $message = preg_replace('/https?:\/\/\S+/i', '[url]', $message) ?? '';
        $message = preg_replace('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', '[email]', $message) ?? '';
        $message = preg_replace('/\b(?:bearer|token|secret|password|authorization)\b\s*[:=]?\s*\S+/i', '[redacted credential]', $message) ?? '';
        $message = preg_replace('/\b[A-Za-z0-9_-]{32,}\b/', '[redacted]', $message) ?? '';
        $message = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $message) ?? '';

        return mb_substr(trim($message), 0, 500);
    }

    private static function sanitizeErrorCode(string $code): string
    {
        $code = strtoupper(trim($code));
        if (preg_match('/\A[A-Z0-9_]{1,80}\z/', $code) !== 1) {
            throw new InvalidArgumentException('Delivery error codes may contain only uppercase letters, digits, and underscores.');
        }

        return $code;
    }
}

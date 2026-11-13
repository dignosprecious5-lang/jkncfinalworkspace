<?php

namespace App\Models;

use App\Enums\TransmittalEmailDispatchState;
use App\Models\Concerns\RequiresTransmittalV2Parent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * V2-only dispatch intent. Sendable payload data is envelope-encrypted;
 * bearer tokens, recipient addresses, message bodies and file bytes are never
 * persisted as plaintext or copied into attempt/provider-event evidence.
 */
class TransmittalEmailDispatch extends Model
{
    use RequiresTransmittalV2Parent;

    private bool $verifiedSafeRetryTransition = false;
    private bool $verifiedProviderEventTransition = false;

    protected $table = 'transmittal_email_dispatches';

    protected $fillable = [
        'dispatch_uuid',
        'transmittal_id',
        'recipient_id',
        'workflow_version',
        'transmission_version',
        'dedupe_fingerprint',
        'payload_fingerprint',
        'attachment_manifest_fingerprint',
        'requested_by',
        'encrypted_payload',
        'wrapped_payload_key',
        'payload_key_id',
        'available_at',
        'locked_until',
        'attempt_count',
        'last_error_code',
        'resend_of_dispatch_id',
        'manual_resend_request_uuid',
        'manual_resend_reason',
    ];

    protected function casts(): array
    {
        return [
            'state' => TransmittalEmailDispatchState::class,
            'available_at' => 'datetime',
            'locked_until' => 'datetime',
            'attempt_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TransmittalEmailDispatch $dispatch): void {
            if ($dispatch->getAttribute('workflow_version') !== 'v2'
                || $dispatch->state !== TransmittalEmailDispatchState::Pending) {
                throw new LogicException('A V2 dispatch must start pending and belong to workflow v2.');
            }

            self::assertFingerprint($dispatch->getAttribute('dedupe_fingerprint'), 'dedupe');
            self::assertFingerprint($dispatch->getAttribute('payload_fingerprint'), 'payload');
            self::assertFingerprint($dispatch->getAttribute('attachment_manifest_fingerprint'), 'attachment manifest');
        });

        static::updating(function (TransmittalEmailDispatch $dispatch): void {
            if ($dispatch->isDirty([
                'dispatch_uuid', 'transmittal_id', 'recipient_id', 'workflow_version',
                'transmission_version', 'dedupe_fingerprint', 'payload_fingerprint',
                'attachment_manifest_fingerprint',
                'encrypted_payload', 'wrapped_payload_key', 'payload_key_id',
                'resend_of_dispatch_id', 'manual_resend_request_uuid', 'manual_resend_reason',
            ])) {
                throw new LogicException('Dispatch identity and immutable fingerprints cannot be reassigned.');
            }

            if ($dispatch->isDirty('state')) {
                $from = TransmittalEmailDispatchState::tryFrom((string) $dispatch->getRawOriginal('state'));
                $to = $dispatch->state;

                if (! $from instanceof TransmittalEmailDispatchState
                    || ! $to instanceof TransmittalEmailDispatchState
                    || ! $from->canTransitionTo($to, $dispatch->verifiedProviderEventTransition, $dispatch->verifiedProviderEventTransition, $dispatch->verifiedSafeRetryTransition)) {
                    throw new LogicException('Dispatch state transition is invalid or requires verified provider evidence.');
                }
            }
        });

        static::deleting(fn () => throw new LogicException('Dispatch evidence is retained and cannot be deleted.'));
    }

    public function authorizeVerifiedSafeRetryTransition(): void
    {
        $this->verifiedSafeRetryTransition = true;
    }

    public function authorizeVerifiedProviderEventTransition(): void
    {
        $this->verifiedProviderEventTransition = true;
    }

    private static function assertFingerprint(mixed $value, string $name): void
    {
        if (! is_string($value) || preg_match('/\A[a-f0-9]{64}\z/i', $value) !== 1) {
            throw new LogicException('A valid SHA-256 '.$name.' fingerprint is required.');
        }
    }

    public function transmittal(): BelongsTo
    {
        return $this->belongsTo(Transmittal::class, 'transmittal_id')
            ->where('transmittals.workflow_version', 'v2');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(TransmittalRecipient::class, 'recipient_id');
    }

    public function attemptEvents(): HasMany
    {
        return $this->hasMany(TransmittalEmailDispatchAttempt::class, 'dispatch_id');
    }
}

<?php

namespace App\Services\TransmittalV2;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Non-persistent normalized provider-event shape. It is not authenticated
 * evidence and cannot transition a dispatch; webhook security and event
 * uniqueness remain blocked pending approval and provider/staging evidence.
 */
final readonly class TransmittalProviderEventContract
{
    private const ALLOWED_EVENTS = [
        'request', 'delivered', 'hard_bounce', 'soft_bounce', 'blocked', 'deferred', 'invalid', 'spam',
    ];

    public function __construct(
        public string $dispatchUuid,
        public ?string $providerMessageId,
        public string $event,
        public DateTimeImmutable $occurredAt,
        public string $eventFingerprint
    ) {
        if (preg_match('/\A[0-9a-f-]{36}\z/i', $dispatchUuid) !== 1
            || ! in_array($event, self::ALLOWED_EVENTS, true)
            || preg_match('/\A[a-f0-9]{64}\z/i', $eventFingerprint) !== 1
            || ($providerMessageId !== null && (strlen($providerMessageId) > 255 || preg_match('/[\r\n]/', $providerMessageId)))) {
            throw new InvalidArgumentException('Provider event contract fields are invalid.');
        }
    }
}

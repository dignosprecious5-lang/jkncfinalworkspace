<?php

namespace App\Enums;

/**
 * State vocabulary for the V2 outbox.
 *
 * `accepted` means the provider accepted a submission; only separately verified
 * provider evidence may establish `delivered`. Unknown is deliberately terminal
 * here: reconciliation/resend transitions require an approved workflow.
 */
enum TransmittalEmailDispatchState: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Accepted = 'accepted';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Unknown = 'unknown';

    public function canTransitionTo(
        self $next,
        bool $verifiedProviderDeliveryEvidence = false,
        bool $verifiedProviderEvent = false,
        bool $verifiedSafeRetry = false
    ): bool {
        return match ($this) {
            self::Pending => $next === self::Processing,
            self::Processing => in_array($next, [self::Accepted, self::Failed, self::Unknown], true)
                || ($next === self::Pending && $verifiedSafeRetry),
            self::Accepted => ($next === self::Delivered && $verifiedProviderDeliveryEvidence)
                || ($next === self::Failed && $verifiedProviderEvent),
            self::Unknown => $verifiedProviderEvent && in_array($next, [self::Delivered, self::Failed], true),
            self::Delivered, self::Failed => false,
        };
    }

    public function isTerminalOrUncertain(): bool
    {
        return in_array($this, [self::Delivered, self::Failed, self::Unknown], true);
    }
}

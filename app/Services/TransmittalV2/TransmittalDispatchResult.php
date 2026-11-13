<?php

namespace App\Services\TransmittalV2;

final readonly class TransmittalDispatchResult
{
    private function __construct(
        public string $outcome,
        public ?string $messageId = null,
        public ?int $httpStatus = null,
        public ?string $errorCode = null,
        public bool $confirmedSafeRetry = false,
    ) {}

    public static function accepted(?string $messageId, ?int $status = 202): self
    {
        return new self('accepted', $messageId, $status);
    }

    public static function rejected(string $safeCode, int $status, bool $confirmedSafeRetry = false): self
    {
        return new self('rejected', null, $status, $safeCode, $confirmedSafeRetry);
    }

    public static function unknown(string $safeCode, ?int $status = null): self
    {
        return new self('unknown', null, $status, $safeCode);
    }
}

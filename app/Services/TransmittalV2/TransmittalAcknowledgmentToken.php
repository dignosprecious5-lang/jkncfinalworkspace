<?php

namespace App\Services\TransmittalV2;

final class TransmittalAcknowledgmentToken
{
    public static function isValid(string $token): bool
    {
        return preg_match('/\A[A-Za-z0-9_-]{32,100}\z/D', $token) === 1;
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}

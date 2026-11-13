<?php

namespace App\Services\TransmittalV2;

use InvalidArgumentException;
use JsonException;

/** Pure deterministic key builder; database uniqueness remains separately blocked. */
final class TransmittalEmailDispatchIdentity
{
    /**
     * Build a stable per-recipient/per-immutable-version dedupe fingerprint.
     * The version is a non-secret SHA-256 fingerprint supplied by a future
     * caller; this class does not render or persist email content.
     *
     * @throws JsonException
     */
    public static function fingerprint(
        int|string $transmittalId,
        int|string $recipientId,
        string $purpose,
        string $transmissionVersionFingerprint
    ): string {
        if (trim((string) $transmittalId) === '' || trim((string) $recipientId) === ''
            || preg_match('/\A[a-z][a-z0-9_]{0,63}\z/', $purpose) !== 1
            || preg_match('/\A[a-f0-9]{64}\z/i', $transmissionVersionFingerprint) !== 1) {
            throw new InvalidArgumentException('Dispatch identity inputs are incomplete or invalid.');
        }

        $canonical = json_encode([
            'workflow' => 'v2',
            'transmittal_id' => (string) $transmittalId,
            'recipient_id' => (string) $recipientId,
            'purpose' => $purpose,
            'transmission_version_fingerprint' => strtolower($transmissionVersionFingerprint),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return hash('sha256', $canonical);
    }

    /** Stable UUIDv5 for the same dispatch fingerprint across request retries. */
    public static function uuidForFingerprint(string $fingerprint): string
    {
        if (preg_match('/\A[a-f0-9]{64}\z/i', $fingerprint) !== 1) {
            throw new InvalidArgumentException('A SHA-256 dispatch fingerprint is required.');
        }

        $namespace = hex2bin('6ba7b8109dad11d180b400c04fd430c8');
        $name = 'jknc:ordo:transmittal-v2:'.$fingerprint;
        $hex = substr(sha1($namespace.$name), 0, 32);
        $hex[12] = '5';
        $hex[16] = dechex((hexdec($hex[16]) & 0x03) | 0x08);

        return substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-'.substr($hex, 12, 4).'-'
            .substr($hex, 16, 4).'-'.substr($hex, 20, 12);
    }
}

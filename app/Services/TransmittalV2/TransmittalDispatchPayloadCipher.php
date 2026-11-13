<?php

namespace App\Services\TransmittalV2;

use Illuminate\Encryption\Encrypter;
use JsonException;
use RuntimeException;

/** Envelope encryption for immutable V2 send payloads. Plaintext is memory-only. */
final class TransmittalDispatchPayloadCipher
{
    /** @return array{ciphertext:string,wrapped_key:string,key_id:string,fingerprint:string} */
    public function encrypt(array $payload): array
    {
        $keyId = config('transmittal_v2.dispatch.current_key_id');
        $key = $this->keyFor($keyId);
        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $exception) {
            throw new RuntimeException('The V2 dispatch payload is not valid JSON data.', previous: $exception);
        }

        $dataKey = random_bytes(32);
        $ciphertext = (new Encrypter($dataKey, 'AES-256-CBC'))->encryptString($json);
        $wrappedKey = (new Encrypter($key, 'AES-256-CBC'))->encryptString($dataKey);

        return [
            'ciphertext' => $ciphertext,
            'wrapped_key' => $wrappedKey,
            'key_id' => (string) $keyId,
            'fingerprint' => hash('sha256', $json),
        ];
    }

    public function decrypt(string $ciphertext, string $wrappedKey, string $keyId): array
    {
        $key = $this->keyFor($keyId);
        try {
            $dataKey = (new Encrypter($key, 'AES-256-CBC'))->decryptString($wrappedKey);
            if (strlen($dataKey) !== 32) {
                throw new RuntimeException('The wrapped V2 dispatch key is invalid.');
            }
            $json = (new Encrypter($dataKey, 'AES-256-CBC'))->decryptString($ciphertext);
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('The encrypted V2 dispatch payload is invalid.', previous: $exception);
        } catch (\Throwable $exception) {
            throw new RuntimeException('The V2 dispatch payload could not be decrypted; sending is stopped.', previous: $exception);
        }

        $canonical = is_array($payload) ? json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : false;
        if (! is_string($canonical) || ! hash_equals(hash('sha256', $json), hash('sha256', $canonical))) {
            throw new RuntimeException('The decrypted V2 dispatch payload failed integrity validation.');
        }

        return $payload;
    }

    private function keyFor(mixed $keyId): string
    {
        $keyId = is_string($keyId) ? $keyId : '';
        if (preg_match('/\A[a-zA-Z0-9_.-]{1,80}\z/', $keyId) !== 1) {
            throw new RuntimeException('V2 dispatch encryption key identifier is invalid; sending is stopped.');
        }
        $keyring = (array) config('transmittal_v2.dispatch.keyring', []);
        $encoded = $keyring[$keyId] ?? null;
        if ($keyId === '' || ! is_string($encoded) || ! str_starts_with($encoded, 'base64:')) {
            throw new RuntimeException('V2 dispatch encryption key is missing or invalid; sending is stopped.');
        }
        $key = base64_decode(substr($encoded, 7), true);
        if (! is_string($key) || strlen($key) !== 32) {
            throw new RuntimeException('V2 dispatch encryption key must decode to exactly 32 bytes; sending is stopped.');
        }

        return $key;
    }
}

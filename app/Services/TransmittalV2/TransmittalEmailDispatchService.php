<?php

namespace App\Services\TransmittalV2;

use App\Enums\TransmittalEmailDispatchState;
use App\Models\Transmittal;
use App\Models\TransmittalEmailDispatch;
use App\Models\TransmittalRecipient;
use App\Models\User;
use App\Services\TransmittalV2Authorization;
use App\Services\TransmittalWorkflowBoundary;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use LogicException;

/**
 * Creates a V2 intent. Optional encrypted envelope data is committed atomically
 * with the immutable intent; plaintext payload data is never accepted here.
 *
 * Database unique constraints remain the final deduplication authority; a
 * uniqueness race is reconciled by fingerprint after its losing transaction
 * rolls back. Sending remains delegated to an isolated worker.
 */
final class TransmittalEmailDispatchService
{
    public function createSendableIntent(
        Transmittal $transmittal,
        TransmittalRecipient $recipient,
        User $actor,
        string $transmissionVersionFingerprint,
        array $sendablePayload,
        TransmittalDispatchPayloadCipher $cipher
    ): TransmittalEmailDispatch {
        $encrypted = $cipher->encrypt($sendablePayload);
        $attachments = $sendablePayload['attachments'] ?? [];
        if (! is_array($attachments)) {
            throw new LogicException('V2 dispatch attachment references must be an array.');
        }
        $manifest = array_map(static fn ($attachment): array => is_array($attachment) ? [
            'disk' => $attachment['disk'] ?? null,
            'path' => $attachment['path'] ?? null,
            'sha256' => $attachment['sha256'] ?? null,
            'name' => $attachment['name'] ?? null,
        ] : [], $attachments);

        return $this->createPendingIntent(
            $transmittal,
            $recipient,
            $actor,
            $transmissionVersionFingerprint,
            $encrypted['fingerprint'],
            hash('sha256', json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: ''),
            $encrypted
        );
    }

    public function createPendingIntent(
        Transmittal $transmittal,
        TransmittalRecipient $recipient,
        User $actor,
        string $transmissionVersionFingerprint,
        string $payloadFingerprint,
        string $attachmentManifestFingerprint,
        ?array $encryptedPayload = null
    ): TransmittalEmailDispatch {
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        TransmittalWorkflowBoundary::assertChild($transmittal, $recipient);

        if (preg_match('/\A[a-f0-9]{64}\z/i', $transmissionVersionFingerprint) !== 1
            || preg_match('/\A[a-f0-9]{64}\z/i', $payloadFingerprint) !== 1
            || preg_match('/\A[a-f0-9]{64}\z/i', $attachmentManifestFingerprint) !== 1) {
            throw new LogicException('Immutable version and SHA-256 fingerprints are required.');
        }
        if ($encryptedPayload !== null && (! is_string($encryptedPayload['ciphertext'] ?? null)
            || ! is_string($encryptedPayload['wrapped_key'] ?? null)
            || ! is_string($encryptedPayload['key_id'] ?? null)
            || ! hash_equals($payloadFingerprint, (string) ($encryptedPayload['fingerprint'] ?? '')))) {
            throw new LogicException('Encrypted payload envelope does not match its immutable fingerprint.');
        }

        $dedupeFingerprint = TransmittalEmailDispatchIdentity::fingerprint(
            $transmittal->getKey(),
            $recipient->getKey(),
            'acknowledgment_request',
            $transmissionVersionFingerprint
        );
        $transmittalId = (int) $transmittal->getKey();
        $recipientId = (int) $recipient->getKey();

        try {
            return DB::transaction(function () use (
                $transmittalId,
                $recipientId,
                $actor,
                $transmissionVersionFingerprint,
                $payloadFingerprint,
                $attachmentManifestFingerprint,
                $dedupeFingerprint,
                $encryptedPayload
            ): TransmittalEmailDispatch {
                $lockedTransmittal = TransmittalWorkflowBoundary::v2Query()
                    ->whereKey($transmittalId)
                    ->lockForUpdate()
                    ->firstOrFail();
                $lockedRecipient = TransmittalWorkflowBoundary::v2Children(TransmittalRecipient::class)
                    ->where('transmittal_id', $lockedTransmittal->getKey())
                    ->whereKey($recipientId)
                    ->lockForUpdate()
                    ->firstOrFail();
                TransmittalWorkflowBoundary::assertChild($lockedTransmittal, $lockedRecipient);

                if (! $actor->exists || $actor->getKey() === null) {
                    throw new AuthorizationException('The actor cannot create a V2 transmission intent.');
                }
                $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
                if (! TransmittalV2Authorization::canApprove($lockedActor)
                    && ! TransmittalV2Authorization::ownsTransmittal($lockedActor, $lockedTransmittal->prepared_by)) {
                    throw new AuthorizationException('The actor cannot create a V2 transmission intent.');
                }

                $existing = TransmittalEmailDispatch::query()
                    ->where('dedupe_fingerprint', $dedupeFingerprint)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    if (! hash_equals((string) $existing->payload_fingerprint, $payloadFingerprint)
                        || ! hash_equals((string) $existing->attachment_manifest_fingerprint, $attachmentManifestFingerprint)) {
                        throw new LogicException('The immutable transmission version conflicts with its existing dispatch fingerprint.');
                    }

                    if ($encryptedPayload !== null && (! is_string($existing->encrypted_payload)
                        || ! is_string($existing->wrapped_payload_key) || ! is_string($existing->payload_key_id))) {
                        throw new LogicException('An existing dispatch cannot be upgraded with different sendable payload evidence.');
                    }

                    return $existing;
                }

                if (! in_array($lockedTransmittal->status, ['approved', 'sent'], true)
                    || $lockedRecipient->sent_at !== null) {
                    throw new LogicException('A new dispatch intent requires an approved/sent parent and an unsent V2 recipient.');
                }

                $dispatch = new TransmittalEmailDispatch([
                    'dispatch_uuid' => TransmittalEmailDispatchIdentity::uuidForFingerprint($dedupeFingerprint),
                    'transmittal_id' => $lockedTransmittal->getKey(),
                    'recipient_id' => $lockedRecipient->getKey(),
                    'workflow_version' => 'v2',
                    'transmission_version' => $transmissionVersionFingerprint,
                    'dedupe_fingerprint' => $dedupeFingerprint,
                    'payload_fingerprint' => $payloadFingerprint,
                    'attachment_manifest_fingerprint' => $attachmentManifestFingerprint,
                    'requested_by' => $lockedActor->getKey(),
                    'encrypted_payload' => $encryptedPayload['ciphertext'] ?? null,
                    'wrapped_payload_key' => $encryptedPayload['wrapped_key'] ?? null,
                    'payload_key_id' => $encryptedPayload['key_id'] ?? null,
                ]);
                $dispatch->state = TransmittalEmailDispatchState::Pending;
                $dispatch->save();

                return $dispatch;
            });
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            $existing = TransmittalEmailDispatch::query()
                ->where('dedupe_fingerprint', $dedupeFingerprint)
                ->first();
            if ($existing === null) {
                throw $exception;
            }
            if (! hash_equals((string) $existing->payload_fingerprint, $payloadFingerprint)
                || ! hash_equals((string) $existing->attachment_manifest_fingerprint, $attachmentManifestFingerprint)) {
                throw new LogicException('The immutable transmission version conflicts with its existing dispatch fingerprint.');
            }
            if ($encryptedPayload !== null && (! is_string($existing->encrypted_payload)
                || ! is_string($existing->wrapped_payload_key) || ! is_string($existing->payload_key_id))) {
                throw new LogicException('An existing dispatch cannot be upgraded with different sendable payload evidence.');
            }

            return $existing;
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);

        return $sqlState === '23505'
            || ($sqlState === '23000' && in_array($driverCode, [19, 1062], true));
    }
}

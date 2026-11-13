<?php

namespace App\Services\TransmittalV2;

use App\Enums\TransmittalEmailDispatchAttemptEvent;
use App\Enums\TransmittalEmailDispatchState;
use App\Jobs\TransmittalV2\DispatchTransmittalEmail;
use App\Models\TransmittalDocument;
use App\Models\TransmittalEmailDispatch;
use App\Models\TransmittalRecipient;
use App\Services\TransmittalWorkflowBoundary;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

/** V2-only worker. The provider must be mock in local/test; sending is opt-in. */
final class TransmittalV2DispatchWorker
{
    public function __construct(
        private readonly TransmittalDispatchProvider $provider,
        private readonly TransmittalDispatchPayloadCipher $cipher,
        private readonly TransmittalEmailDispatchAttemptRecorder $attempts,
    ) {}

    public function handle(int $dispatchId): TransmittalEmailDispatch
    {
        if (! TransmittalDispatchProviderAvailability::isEnabled()) {
            throw new LogicException('V2 dispatch worker is disabled or its provider is not explicitly enabled.');
        }

        $dispatch = TransmittalEmailDispatch::query()->whereKey($dispatchId)->where('workflow_version', 'v2')
            ->whereHas('transmittal', fn ($query) => $query->where('transmittals.workflow_version', 'v2'))->firstOrFail();
        if ($dispatch->state !== TransmittalEmailDispatchState::Pending
            || ($dispatch->available_at !== null && $dispatch->available_at->isFuture())) {
            return $dispatch;
        }
        if (! is_string($dispatch->encrypted_payload) || ! is_string($dispatch->wrapped_payload_key)
            || ! is_string($dispatch->payload_key_id)) {
            throw new LogicException('Sendable payload evidence is incomplete; dispatch was not claimed.');
        }

        $payload = $this->cipher->decrypt($dispatch->encrypted_payload, $dispatch->wrapped_payload_key, $dispatch->payload_key_id);
        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (! is_string($payloadJson) || ! hash_equals((string) $dispatch->payload_fingerprint, hash('sha256', $payloadJson))) {
            throw new LogicException('The encrypted V2 sendable payload fingerprint does not match its immutable intent.');
        }
        $attachmentRefs = array_map(static fn ($attachment): array => is_array($attachment) ? [
            'disk' => $attachment['disk'] ?? null, 'path' => $attachment['path'] ?? null,
            'sha256' => $attachment['sha256'] ?? null, 'name' => $attachment['name'] ?? null,
        ] : [], $payload['attachments'] ?? []);
        $manifestJson = json_encode($attachmentRefs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (! is_string($manifestJson) || ! hash_equals((string) $dispatch->attachment_manifest_fingerprint, hash('sha256', $manifestJson))) {
            throw new LogicException('The V2 attachment manifest does not match its immutable intent.');
        }
        $providerPayload = $this->validatePayloadAndAttachments($dispatch, $payload);
        $attemptUuid = (string) Str::uuid();
        $sequence = ((int) $dispatch->attempt_count) + 1;
        $requestFingerprint = hash('sha256', $dispatch->dispatch_uuid.'|'.$dispatch->payload_fingerprint.'|'.$sequence);
        $this->attempts->beginSubmission($dispatch, $attemptUuid, $sequence, $requestFingerprint);

        try {
            $result = $this->provider->submit((string) $dispatch->dispatch_uuid, $providerPayload);
        } catch (Throwable) {
            $this->attempts->append($dispatch, $attemptUuid, $sequence, TransmittalEmailDispatchAttemptEvent::OutcomeUnknown,
                $requestFingerprint, null, null, 'provider_exception');
            return $dispatch->fresh();
        }

        $event = match ($result->outcome) {
            'accepted' => TransmittalEmailDispatchAttemptEvent::ProviderAccepted,
            'rejected' => TransmittalEmailDispatchAttemptEvent::ProviderRejected,
            default => TransmittalEmailDispatchAttemptEvent::OutcomeUnknown,
        };
        DB::transaction(function () use ($dispatch, $attemptUuid, $sequence, $event, $requestFingerprint, $result): void {
            $parent = TransmittalWorkflowBoundary::v2Query()->whereKey($dispatch->transmittal_id)->lockForUpdate()->firstOrFail();
            $recipient = TransmittalWorkflowBoundary::v2Children(TransmittalRecipient::class)
                ->where('transmittal_id', $parent->getKey())->whereKey($dispatch->recipient_id)->lockForUpdate()->firstOrFail();
            TransmittalWorkflowBoundary::assertChild($parent, $recipient);
            $this->attempts->append($dispatch, $attemptUuid, $sequence, $event, $requestFingerprint,
                $result->httpStatus, $result->messageId, $result->errorCode, $result->confirmedSafeRetry);

            if ($event === TransmittalEmailDispatchAttemptEvent::ProviderAccepted) {
                if ($recipient->sent_at !== null || ! filled($recipient->acknowledgment_token_hash)) {
                    throw new LogicException('V2 recipient transmission projection is invalid; dispatch outcome remains recoverable.');
                }
                $acceptedAt = now();
                $recipient->forceFill(['sent_at' => $acceptedAt])->save();
                $parent->proofs()->create([
                    'recipient_id' => $recipient->getKey(),
                    'sent_by' => $dispatch->requested_by,
                    'channel' => 'email',
                    'status' => 'sent',
                    'provider_message_id' => $result->messageId,
                    'transaction_reference' => 'TXN-'.strtoupper(substr(str_replace('-', '', $dispatch->dispatch_uuid), 0, 18)),
                    'transmitted_at' => $acceptedAt,
                    'provider_metadata' => ['provider' => (string) config('transmittal_v2.dispatch.provider'),
                        'dispatch_uuid' => $dispatch->dispatch_uuid],
                ]);
                $parent->histories()->create([
                    'actor_id' => $dispatch->requested_by,
                    'action' => 'email_dispatch_accepted',
                    'description' => 'The provider accepted the V2 acknowledgment request. Delivery is not yet confirmed.',
                    'metadata' => ['dispatch_uuid' => $dispatch->dispatch_uuid, 'provider_message_id' => $result->messageId],
                    'created_at' => $acceptedAt,
                ]);
                if ($parent->status === 'approved') {
                    $parent->forceFill(['status' => 'sent'])->save();
                }
            }
        });
        $fresh = $dispatch->fresh();
        if ($result->outcome === 'rejected' && $result->confirmedSafeRetry
            && $fresh?->state === TransmittalEmailDispatchState::Pending
            && $fresh->available_at !== null) {
            DB::afterCommit(function () use ($fresh): void {
                $job = (new DispatchTransmittalEmail((int) $fresh->getKey()))
                    ->onQueue('transmittal-v2')->delay($fresh->available_at);
                dispatch($job);
            });
        }
        return $dispatch->fresh();
    }

    private function validatePayloadAndAttachments(TransmittalEmailDispatch $dispatch, array $payload): array
    {
        if (! filter_var(data_get($payload, 'to.email'), FILTER_VALIDATE_EMAIL)
            || ! is_string(data_get($payload, 'subject')) || ! is_string(data_get($payload, 'html'))
            || ! is_array(data_get($payload, 'attachments', []))) {
            throw new LogicException('The encrypted V2 sendable payload is incomplete.');
        }
        $parent = TransmittalWorkflowBoundary::requireV2($dispatch->transmittal);
        $providerPayload = $payload;
        $providerPayload['attachments'] = [];
        $totalBytes = 0;
        foreach ($payload['attachments'] ?? [] as $attachment) {
            if (! is_array($attachment) || ($attachment['disk'] ?? null) !== 'local'
                || ! is_string($attachment['path'] ?? null) || ! is_string($attachment['sha256'] ?? null)
                || preg_match('/\A[a-f0-9]{64}\z/i', $attachment['sha256']) !== 1) {
                throw new LogicException('An encrypted V2 attachment reference is invalid.');
            }
            $document = TransmittalDocument::query()->where('transmittal_id', $parent->getKey())
                ->where('storage_path', $attachment['path'])->first();
            if ($document === null || ! hash_equals((string) $document->sha256, $attachment['sha256'])) {
                throw new LogicException('An encrypted V2 attachment is not owned by this transmittal or has changed.');
            }
            $disk = Storage::disk('local');
            if (! $disk->exists($attachment['path'])) {
                throw new LogicException('An encrypted V2 attachment is missing.');
            }
            $contents = $disk->get($attachment['path']);
            if (! is_string($contents)) {
                throw new LogicException('An encrypted V2 attachment cannot be read.');
            }
            $totalBytes += strlen($contents);
            if ($totalBytes > max(1, (int) config('transmittal_v2.dispatch.max_attachment_bytes', 52428800))) {
                throw new LogicException('V2 attachment payload exceeds the configured worker memory bound.');
            }
            $digest = hash('sha256', $contents);
            if (! hash_equals($attachment['sha256'], $digest)) {
                throw new LogicException('An encrypted V2 attachment failed integrity validation.');
            }
            $providerPayload['attachments'][] = [
                'name' => basename((string) ($attachment['name'] ?? $document->original_name)),
                'content' => base64_encode($contents),
                'sha256' => $digest,
            ];
        }

        return $providerPayload;
    }
}

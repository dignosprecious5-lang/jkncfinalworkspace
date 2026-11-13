<?php

namespace App\Services\TransmittalV2;

use App\Enums\TransmittalEmailDispatchState;
use App\Models\TransmittalEmailDispatch;
use App\Models\User;
use App\Services\TransmittalWorkflowBoundary;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

final class TransmittalEmailDispatchResender
{
    public function __construct(
        private readonly TransmittalManualResendAuthorization $authorization,
        private readonly TransmittalDispatchPayloadCipher $cipher,
    ) {}

    public function request(TransmittalEmailDispatch $source, User $actor, string $reason, string $requestUuid): TransmittalEmailDispatch
    {
        if (! $this->authorization->allows($actor)) {
            abort(403, 'You are not designated to request V2 manual resend.');
        }
        $reason = trim($reason);
        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 1000) {
            throw ValidationException::withMessages(['reason' => 'A resend reason between 10 and 1000 characters is required.']);
        }
        if (! Str::isUuid($requestUuid)) {
            throw ValidationException::withMessages(['request_uuid' => 'A valid unique resend request identifier is required.']);
        }

        return DB::transaction(function () use ($source, $actor, $reason, $requestUuid): TransmittalEmailDispatch {
            $identity = TransmittalEmailDispatch::query()->whereKey($source->getKey())->where('workflow_version', 'v2')
                ->whereHas('transmittal', fn ($query) => $query->where('transmittals.workflow_version', 'v2'))->firstOrFail();
            $parent = TransmittalWorkflowBoundary::v2Query()->whereKey($identity->transmittal_id)->lockForUpdate()->firstOrFail();
            $parent = TransmittalWorkflowBoundary::requireV2($parent);
            $recipient = TransmittalWorkflowBoundary::v2Children(\App\Models\TransmittalRecipient::class)
                ->where('transmittal_id', $parent->getKey())->whereKey($identity->recipient_id)->lockForUpdate()->firstOrFail();
            TransmittalWorkflowBoundary::assertChild($parent, $recipient);
            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            if (! $this->authorization->allows($lockedActor)) {
                abort(403, 'You are not designated to request V2 manual resend.');
            }
            $locked = TransmittalEmailDispatch::query()->whereKey($identity->getKey())
                ->where('transmittal_id', $parent->getKey())->where('recipient_id', $recipient->getKey())
                ->where('workflow_version', 'v2')->lockForUpdate()->firstOrFail();
            if (! in_array($locked->state, [TransmittalEmailDispatchState::Unknown, TransmittalEmailDispatchState::Failed], true)) {
                throw new LogicException('Manual resend is available only after an unknown outcome or confirmed failure.');
            }
            $existing = TransmittalEmailDispatch::query()->where('manual_resend_request_uuid', $requestUuid)->first();
            if ($existing !== null) {
                if ((int) $existing->resend_of_dispatch_id !== (int) $locked->getKey()
                    || (int) $existing->requested_by !== (int) $lockedActor->getKey()
                    || ! hash_equals((string) $existing->manual_resend_reason, $reason)) {
                    throw new LogicException('Resend request identifier conflicts with existing audit evidence.');
                }
                return $existing;
            }
            if (! $locked->encrypted_payload || ! $locked->wrapped_payload_key || ! $locked->payload_key_id) {
                throw new LogicException('The source dispatch does not have an encrypted sendable payload.');
            }
            if (! in_array($parent->status, ['approved', 'sent'], true)) {
                throw new LogicException('A canceled, rejected, or unapproved V2 transmittal cannot be resent.');
            }
            if ($recipient->acknowledgment_revoked_at !== null) {
                throw new LogicException('A revoked acknowledgment link requires separate review before resend.');
            }
            $dedupe = hash('sha256', $locked->dedupe_fingerprint.'|manual-resend|'.$requestUuid);
            $payload = $this->cipher->decrypt($locked->encrypted_payload, $locked->wrapped_payload_key, $locked->payload_key_id);
            $oldToken = $payload['acknowledgment_token'] ?? null;
            if (! is_string($oldToken) || strlen($oldToken) < 32) {
                throw new LogicException('The source dispatch has no recoverable acknowledgment credential.');
            }
            $newToken = Str::random(64);
            $payload['acknowledgment_token'] = $newToken;
            $payload['html'] = str_replace($oldToken, $newToken, (string) ($payload['html'] ?? ''));
            if (! str_contains((string) $payload['html'], $newToken)) {
                throw new LogicException('The source message could not be safely rebuilt with a new acknowledgment credential.');
            }
            $encrypted = $this->cipher->encrypt($payload);
            $copy = new TransmittalEmailDispatch([
                'dispatch_uuid' => (string) Str::uuid(),
                'transmittal_id' => $parent->getKey(),
                'recipient_id' => $recipient->getKey(),
                'workflow_version' => 'v2',
                'transmission_version' => $locked->transmission_version,
                'dedupe_fingerprint' => $dedupe,
                'payload_fingerprint' => $encrypted['fingerprint'],
                'attachment_manifest_fingerprint' => $locked->attachment_manifest_fingerprint,
                'encrypted_payload' => $encrypted['ciphertext'],
                'wrapped_payload_key' => $encrypted['wrapped_key'],
                'payload_key_id' => $encrypted['key_id'],
                'requested_by' => $lockedActor->getKey(),
                'resend_of_dispatch_id' => $locked->getKey(),
                'manual_resend_request_uuid' => $requestUuid,
                'manual_resend_reason' => $reason,
                'available_at' => now(),
            ]);
            $copy->state = TransmittalEmailDispatchState::Pending;
            $copy->save();
            $recipient->forceFill([
                'acknowledgment_token_hash' => hash('sha256', $newToken),
                'acknowledgment_expires_at' => now()->addHours((int) config('transmittal_v2.acknowledgment.hours', 72)),
            ])->save();
            $parent->histories()->create([
                'actor_id' => $lockedActor->getKey(),
                'action' => 'email_dispatch_manual_resend_requested',
                'description' => 'An authorized manual resend was requested; previous outcome evidence was retained.',
                'metadata' => ['source_dispatch_uuid' => $locked->dispatch_uuid, 'resend_dispatch_uuid' => $copy->dispatch_uuid,
                    'request_uuid' => $requestUuid, 'reason' => $reason],
                'created_at' => now(),
            ]);

            return $copy;
        });
    }
}

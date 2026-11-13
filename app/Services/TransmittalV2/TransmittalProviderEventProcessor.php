<?php

namespace App\Services\TransmittalV2;

use App\Enums\TransmittalEmailDispatchState;
use App\Models\TransmittalEmailDispatch;
use App\Models\TransmittalProviderEvent;
use App\Models\TransmittalRecipient;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\TransmittalWorkflowBoundary;
use LogicException;
use RuntimeException;

/** Generic HMAC event contract. It is not asserted to match Brevo webhooks. */
final class TransmittalProviderEventProcessor
{
    public function process(string $rawBody, string $timestamp, string $signature, string $provider = 'mock'): bool
    {
        if (preg_match('/\A[a-zA-Z0-9_.-]{1,40}\z/', $provider) !== 1) {
            throw new RuntimeException('Provider event provider name is invalid.');
        }
        $secret = config('transmittal_v2.dispatch.event_secret');
        if (! is_string($secret) || strlen($secret) < 32 || ! ctype_digit($timestamp)) {
            throw new RuntimeException('Provider event authentication is unavailable.');
        }
        $eventTime = (int) $timestamp;
        if (abs(time() - $eventTime) > max(30, (int) config('transmittal_v2.dispatch.event_clock_skew_seconds', 300))) {
            throw new RuntimeException('Provider event timestamp is outside the accepted replay window.');
        }
        $expected = hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);
        if (! hash_equals($expected, strtolower($signature))) {
            throw new RuntimeException('Provider event signature is invalid.');
        }
        $data = json_decode($rawBody, true);
        if (! is_array($data)
            || ! is_string($data['event_id'] ?? null) || preg_match('/\A[a-zA-Z0-9_.:-]{1,191}\z/', $data['event_id']) !== 1
            || ! is_string($data['dispatch_uuid'] ?? null) || ! Str::isUuid($data['dispatch_uuid'])
            || ! is_string($data['message_id'] ?? null) || $data['message_id'] === ''
            || ! in_array($data['event_type'] ?? null, ['delivered', 'bounced'], true)
            || (isset($data['occurred_at']) && ! is_numeric($data['occurred_at']))) {
            throw new RuntimeException('Provider event data is invalid.');
        }
        $dispatch = TransmittalEmailDispatch::query()->where('dispatch_uuid', $data['dispatch_uuid'])
            ->where('workflow_version', 'v2')
            ->whereHas('transmittal', fn ($query) => $query->where('transmittals.workflow_version', 'v2'))->first();
        if ($dispatch === null) {
            throw new RuntimeException('Provider event does not identify a V2 dispatch.');
        }
        $bodyHash = hash('sha256', $rawBody);
        $signatureHash = hash('sha256', $signature);

        try {
            return DB::transaction(function () use ($provider, $data, $dispatch, $bodyHash, $signatureHash): bool {
                $parent = TransmittalWorkflowBoundary::v2Query()->whereKey($dispatch->transmittal_id)->lockForUpdate()->firstOrFail();
                $recipient = TransmittalWorkflowBoundary::v2Children(TransmittalRecipient::class)
                    ->where('transmittal_id', $parent->getKey())->whereKey($dispatch->recipient_id)->lockForUpdate()->firstOrFail();
                TransmittalWorkflowBoundary::assertChild($parent, $recipient);
                $locked = TransmittalEmailDispatch::query()->whereKey($dispatch->getKey())
                    ->where('transmittal_id', $parent->getKey())->where('recipient_id', $recipient->getKey())
                    ->where('workflow_version', 'v2')
                    ->whereHas('transmittal', fn ($query) => $query->where('transmittals.workflow_version', 'v2'))
                    ->lockForUpdate()->firstOrFail();
                $locked->setRelation('transmittal', $parent);
                $existing = TransmittalProviderEvent::query()->where('provider', $provider)
                    ->where('provider_event_id', $data['event_id'])->lockForUpdate()->first();
                if ($existing !== null) {
                    if (! hash_equals((string) $existing->body_fingerprint, $bodyHash)) {
                        throw new LogicException('A provider event identifier was replayed with conflicting evidence.');
                    }
                    return false;
                }
                if (! in_array($locked->state, [TransmittalEmailDispatchState::Accepted, TransmittalEmailDispatchState::Unknown], true)
                    || ($locked->provider_message_id !== null
                        && ! hash_equals((string) $locked->provider_message_id, (string) $data['message_id']))) {
                    throw new LogicException('Provider event does not match an accepted V2 dispatch.');
                }
                TransmittalProviderEvent::query()->create([
                    'provider' => $provider,
                    'provider_event_id' => $data['event_id'],
                    'dispatch_uuid' => $locked->dispatch_uuid,
                    'provider_message_id' => isset($data['message_id']) ? substr((string) $data['message_id'], 0, 255) : null,
                    'normalized_type' => $data['event_type'],
                    'provider_occurred_at' => isset($data['occurred_at']) ? date('Y-m-d H:i:s', (int) $data['occurred_at']) : null,
                    'received_at' => now(),
                    'body_fingerprint' => $bodyHash,
                    'signature_fingerprint' => $signatureHash,
                ]);
                $locked->authorizeVerifiedProviderEventTransition();
                $locked->state = $data['event_type'] === 'delivered'
                    ? TransmittalEmailDispatchState::Delivered : TransmittalEmailDispatchState::Failed;
                $locked->delivered_at = $data['event_type'] === 'delivered' ? now() : null;
                $locked->failed_at = $data['event_type'] === 'bounced' ? now() : null;
                $locked->save();
                $parent = $locked->transmittal;
                $parent->histories()->create([
                    'actor_id' => null,
                    'action' => $data['event_type'] === 'delivered' ? 'email_delivery_confirmed' : 'email_delivery_failed',
                    'description' => $data['event_type'] === 'delivered'
                        ? 'Authenticated provider evidence confirmed delivery.'
                        : 'Authenticated provider evidence reported a delivery failure.',
                    'metadata' => ['dispatch_uuid' => $locked->dispatch_uuid, 'provider_event_id' => $data['event_id']],
                    'created_at' => now(),
                ]);
                return true;
            });
        } catch (QueryException $exception) {
            $existing = TransmittalProviderEvent::query()->where('provider', $provider)
                ->where('provider_event_id', $data['event_id'])->first();
            if ($existing !== null && hash_equals((string) $existing->body_fingerprint, $bodyHash)) {
                return false;
            }
            throw $exception;
        }
    }
}

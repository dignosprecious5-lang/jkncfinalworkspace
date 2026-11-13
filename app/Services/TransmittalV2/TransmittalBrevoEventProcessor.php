<?php

namespace App\Services\TransmittalV2;

use App\Enums\TransmittalEmailDispatchState;
use App\Models\TransmittalEmailDispatch;
use App\Models\TransmittalProviderEvent;
use App\Models\TransmittalRecipient;
use App\Services\TransmittalWorkflowBoundary;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;

/** Brevo-specific authenticated event parser; persists sanitized evidence only. */
final class TransmittalBrevoEventProcessor
{
    public function process(string $rawBody, string $bearerToken): bool
    {
        $expectedToken = config('transmittal_v2.dispatch.event_secret');
        if (! is_string($expectedToken) || strlen($expectedToken) < 32
            || $bearerToken === '' || ! hash_equals($expectedToken, $bearerToken)) {
            throw new RuntimeException('Brevo webhook authentication failed.');
        }
        if (strlen($rawBody) > 65536) {
            throw new RuntimeException('Brevo webhook payload exceeds the accepted size.');
        }
        try {
            $data = json_decode($rawBody, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException('Brevo webhook payload is invalid.', previous: $exception);
        }
        if (! is_array($data)
            || ! in_array($data['event'] ?? null, ['delivered', 'hard_bounce', 'soft_bounce', 'blocked', 'invalid_email', 'deferred'], true)
            || ! is_string($data['message-id'] ?? null) || trim($data['message-id']) === ''
            || strlen($data['message-id']) > 255 || preg_match('/[\r\n]/', $data['message-id'])
            || ! is_numeric($data['ts_event'] ?? null)) {
            throw new RuntimeException('Brevo webhook event fields are invalid or unsupported.');
        }

        $messageId = trim($data['message-id']);
        $occurredAt = (int) $data['ts_event'];
        if ($occurredAt < 1 || $occurredAt > time() + 86400) {
            throw new RuntimeException('Brevo event timestamp is invalid.');
        }
        $dispatchUuid = $this->taggedDispatchUuid($data['tags'] ?? null);
        $identityQuery = TransmittalEmailDispatch::query()->where('workflow_version', 'v2')
            ->whereHas('transmittal', fn ($query) => $query->where('transmittals.workflow_version', 'v2'));
        if ($dispatchUuid !== null) {
            $identityQuery->where('dispatch_uuid', $dispatchUuid);
        } else {
            $identityQuery->where('provider_message_id', $messageId);
        }
        $matches = $identityQuery->limit(2)->get();
        if ($matches->count() !== 1) {
            throw new RuntimeException('Brevo event cannot be correlated to exactly one V2 dispatch.');
        }
        $identity = $matches->first();
        if ($identity->provider_message_id !== null
            && ! hash_equals((string) $identity->provider_message_id, $messageId)) {
            throw new RuntimeException('Brevo message identifier does not match the V2 dispatch.');
        }

        $eventName = $data['event'];
        $eventId = 'brevo:'.hash('sha256', $messageId."\0".$eventName."\0".$occurredAt);
        $bodyFingerprint = hash('sha256', $rawBody);
        $eventTimestamp = date('Y-m-d H:i:s', $occurredAt);

        try {
            return DB::transaction(function () use ($identity, $eventId, $bodyFingerprint, $messageId, $eventName, $eventTimestamp): bool {
                $parent = TransmittalWorkflowBoundary::v2Query()->whereKey($identity->transmittal_id)->lockForUpdate()->firstOrFail();
                $recipient = TransmittalWorkflowBoundary::v2Children(TransmittalRecipient::class)
                    ->where('transmittal_id', $parent->getKey())->whereKey($identity->recipient_id)->lockForUpdate()->firstOrFail();
                TransmittalWorkflowBoundary::assertChild($parent, $recipient);
                $dispatch = TransmittalEmailDispatch::query()->whereKey($identity->getKey())
                    ->where('transmittal_id', $parent->getKey())->where('recipient_id', $recipient->getKey())
                    ->where('dispatch_uuid', $identity->dispatch_uuid)->where('workflow_version', 'v2')
                    ->whereHas('transmittal', fn ($query) => $query->where('transmittals.workflow_version', 'v2'))
                    ->lockForUpdate()->firstOrFail();
                $dispatch->setRelation('transmittal', $parent);

                $existing = TransmittalProviderEvent::query()->where('provider', 'brevo')
                    ->where('provider_event_id', $eventId)->lockForUpdate()->first();
                if ($existing !== null) {
                    if (! hash_equals((string) $existing->body_fingerprint, $bodyFingerprint)) {
                        throw new LogicException('Brevo event identity was replayed with conflicting evidence.');
                    }
                    return false;
                }
                if (! in_array($dispatch->state, [TransmittalEmailDispatchState::Accepted, TransmittalEmailDispatchState::Unknown], true)) {
                    throw new LogicException('Brevo event does not match an accepted or uncertain V2 dispatch.');
                }

                TransmittalProviderEvent::query()->create([
                    'provider' => 'brevo',
                    'provider_event_id' => $eventId,
                    'dispatch_uuid' => $dispatch->dispatch_uuid,
                    'provider_message_id' => $messageId,
                    'normalized_type' => $eventName,
                    'provider_occurred_at' => $eventTimestamp,
                    'received_at' => now(),
                    'body_fingerprint' => $bodyFingerprint,
                    'signature_fingerprint' => hash('sha256', 'brevo-bearer-authenticated'),
                ]);

                $nextState = match ($eventName) {
                    'delivered' => TransmittalEmailDispatchState::Delivered,
                    'hard_bounce', 'blocked', 'invalid_email' => TransmittalEmailDispatchState::Failed,
                    default => null, // soft_bounce/deferred are evidence only; no resend or delivery claim.
                };
                if ($nextState !== null) {
                    $dispatch->authorizeVerifiedProviderEventTransition();
                    $dispatch->state = $nextState;
                    if ($nextState === TransmittalEmailDispatchState::Delivered) {
                        $dispatch->delivered_at = now();
                    } else {
                        $dispatch->failed_at = now();
                    }
                    $dispatch->save();
                }

                $parent->histories()->create([
                    'actor_id' => null,
                    'action' => $eventName === 'delivered' ? 'email_delivery_confirmed' : 'email_provider_event_observed',
                    'description' => $eventName === 'delivered'
                        ? 'Authenticated Brevo evidence confirmed delivery.'
                        : 'Authenticated Brevo delivery evidence was recorded without an automatic resend.',
                    'metadata' => ['dispatch_uuid' => $dispatch->dispatch_uuid, 'provider_event_id' => $eventId,
                        'provider_event' => $eventName],
                    'created_at' => now(),
                ]);

                return true;
            });
        } catch (QueryException $exception) {
            $existing = TransmittalProviderEvent::query()->where('provider', 'brevo')->where('provider_event_id', $eventId)->first();
            if ($existing !== null && hash_equals((string) $existing->body_fingerprint, $bodyFingerprint)) {
                return false;
            }
            throw $exception;
        }
    }

    private function taggedDispatchUuid(mixed $tags): ?string
    {
        if (! is_array($tags)) {
            return null;
        }
        $matches = [];
        foreach ($tags as $tag) {
            if (is_string($tag) && preg_match('/\Aordo-v2-([0-9a-f-]{36})\z/i', $tag, $match) === 1 && Str::isUuid($match[1])) {
                $matches[] = strtolower($match[1]);
            }
        }
        $matches = array_values(array_unique($matches));
        if (count($matches) > 1) {
            throw new RuntimeException('Brevo webhook contains conflicting V2 dispatch tags.');
        }
        return $matches[0] ?? null;
    }
}

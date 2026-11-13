<?php

namespace App\Services\TransmittalV2;

use App\Enums\TransmittalEmailDispatchAttemptEvent;
use App\Enums\TransmittalEmailDispatchState;
use App\Models\TransmittalEmailDispatch;
use App\Models\TransmittalEmailDispatchAttempt;
use App\Services\TransmittalWorkflowBoundary;
use Illuminate\Support\Facades\DB;
use LogicException;

/** Serializes V2 mock-worker claims and appends sanitized attempt evidence. */
final class TransmittalEmailDispatchAttemptRecorder
{
    /**
     * Atomically claims a pending dispatch and appends its first submission
     * marker. This is an inactive contract for a future V2 worker; it never
     * sends mail or claims an unknown/failed dispatch.
     */
    public function beginSubmission(
        TransmittalEmailDispatch $dispatch,
        string $attemptUuid,
        int $sequence,
        ?string $requestFingerprint = null
    ): TransmittalEmailDispatchAttempt {
        $this->validateIdentity($attemptUuid, $sequence, $requestFingerprint);
        $eventType = TransmittalEmailDispatchAttemptEvent::SubmissionStarted;
        $eventUuid = $this->eventUuid($attemptUuid, $sequence, $eventType);

        if (! $dispatch->exists || $dispatch->getKey() === null) {
            throw new LogicException('Attempt evidence must belong to an existing V2 dispatch.');
        }

        return DB::transaction(function () use ($dispatch, $attemptUuid, $sequence, $requestFingerprint, $eventType, $eventUuid): TransmittalEmailDispatchAttempt {
            $lockedDispatch = $this->lockV2Dispatch($dispatch);
            $existing = TransmittalEmailDispatchAttempt::query()->where('event_uuid', $eventUuid)->lockForUpdate()->first();

            if ($existing !== null) {
                $this->assertSameEvidence($existing, $lockedDispatch, $attemptUuid, $sequence, $eventType, $requestFingerprint, null, null, null);
                return $existing;
            }

            if ($lockedDispatch->state !== TransmittalEmailDispatchState::Pending) {
                throw new LogicException('Only a pending V2 dispatch can be claimed for its first submission.');
            }
            $parent = $lockedDispatch->transmittal;
            $recipient = $lockedDispatch->recipient;
            if (! $parent || ! in_array($parent->status, ['approved', 'sent'], true)
                || ! $recipient || $recipient->sent_at !== null) {
                throw new LogicException('Only an unsent recipient on an approved V2 transmittal can be submitted.');
            }

            $lockedDispatch->state = TransmittalEmailDispatchState::Processing;
            $lockedDispatch->attempt_count = (int) $lockedDispatch->attempt_count + 1;
            $lockedDispatch->locked_until = now()->addSeconds(max(30, (int) config('transmittal_v2.dispatch.lease_seconds', 120)));
            if (! $lockedDispatch->save()) {
                throw new LogicException('The V2 dispatch claim could not be persisted with its start evidence.');
            }

            return TransmittalEmailDispatchAttempt::query()->create([
                'event_uuid' => $eventUuid,
                'attempt_uuid' => $attemptUuid,
                'sequence_no' => $sequence,
                'dispatch_id' => $lockedDispatch->getKey(),
                'event_type' => $eventType,
                'request_fingerprint' => $requestFingerprint,
                'occurred_at' => now(),
            ]);
        });
    }

    public function append(
        TransmittalEmailDispatch $dispatch,
        string $attemptUuid,
        int $sequence,
        TransmittalEmailDispatchAttemptEvent $eventType,
        ?string $requestFingerprint = null,
        ?int $providerHttpStatus = null,
        ?string $providerMessageId = null,
        ?string $sanitizedErrorCode = null,
        bool $confirmedSafeRetry = false
    ): TransmittalEmailDispatchAttempt {
        if ($eventType === TransmittalEmailDispatchAttemptEvent::SubmissionStarted) {
            throw new LogicException('A submission start must atomically claim a pending dispatch through beginSubmission().');
        }

        if (! $dispatch->exists || $dispatch->getKey() === null) {
            throw new LogicException('Attempt evidence must belong to an existing V2 dispatch.');
        }

        $this->validateIdentity($attemptUuid, $sequence, $requestFingerprint);

        if ($providerHttpStatus !== null && ($providerHttpStatus < 100 || $providerHttpStatus > 599)) {
            throw new LogicException('Provider status code is invalid.');
        }

        if ($providerMessageId !== null && (strlen($providerMessageId) > 255 || preg_match('/[\r\n]/', $providerMessageId))) {
            throw new LogicException('Provider message identifier is invalid.');
        }

        if ($sanitizedErrorCode !== null && preg_match('/\A[a-zA-Z0-9_.-]{1,80}\z/', $sanitizedErrorCode) !== 1) {
            throw new LogicException('Error evidence must be a sanitized code.');
        }

        $eventUuid = $this->eventUuid($attemptUuid, $sequence, $eventType);

        return DB::transaction(function () use (
            $dispatch,
            $attemptUuid,
            $sequence,
            $eventType,
            $requestFingerprint,
            $providerHttpStatus,
            $providerMessageId,
            $sanitizedErrorCode,
            $confirmedSafeRetry,
            $eventUuid
        ): TransmittalEmailDispatchAttempt {
            $lockedDispatch = $this->lockV2Dispatch($dispatch);

            $existing = TransmittalEmailDispatchAttempt::query()
                ->where('event_uuid', $eventUuid)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                $this->assertSameEvidence($existing, $lockedDispatch, $attemptUuid, $sequence, $eventType,
                    $requestFingerprint, $providerHttpStatus, $providerMessageId, $sanitizedErrorCode);
                return $existing;
            }

            if ($lockedDispatch->state !== TransmittalEmailDispatchState::Processing) {
                throw new LogicException('Attempt evidence can be appended only while a dispatch is processing.');
            }

            $attempt = TransmittalEmailDispatchAttempt::query()->create([
                'event_uuid' => $eventUuid,
                'attempt_uuid' => $attemptUuid,
                'sequence_no' => $sequence,
                'dispatch_id' => $lockedDispatch->getKey(),
                'event_type' => $eventType,
                'request_fingerprint' => $requestFingerprint,
                'provider_http_status' => $providerHttpStatus,
                'provider_message_id' => $providerMessageId,
                'sanitized_error_code' => $sanitizedErrorCode,
                'occurred_at' => now(),
            ]);

            $outcome = match ($eventType) {
                TransmittalEmailDispatchAttemptEvent::ProviderAccepted => [TransmittalEmailDispatchState::Accepted, 'accepted_at'],
                TransmittalEmailDispatchAttemptEvent::ProviderRejected => [TransmittalEmailDispatchState::Failed, 'failed_at'],
                TransmittalEmailDispatchAttemptEvent::OutcomeUnknown => [TransmittalEmailDispatchState::Unknown, 'unknown_at'],
                TransmittalEmailDispatchAttemptEvent::SubmissionStarted => null,
            };

            $retryTransition = $eventType === TransmittalEmailDispatchAttemptEvent::ProviderRejected
                && $confirmedSafeRetry
                && (int) $lockedDispatch->attempt_count < max(1, (int) config('transmittal_v2.dispatch.max_attempts', 3));
            if ($retryTransition) {
                $outcome = [TransmittalEmailDispatchState::Pending, null];
                $lockedDispatch->authorizeVerifiedSafeRetryTransition();
                $delay = min(3600, 30 * (2 ** max(0, (int) $lockedDispatch->attempt_count - 1)));
                $lockedDispatch->available_at = now()->addSeconds($delay);
                $lockedDispatch->last_error_code = $sanitizedErrorCode;
            } elseif ($eventType === TransmittalEmailDispatchAttemptEvent::ProviderRejected) {
                $lockedDispatch->last_error_code = $sanitizedErrorCode;
            }

            if ($outcome !== null) {
                [$nextState, $timestamp] = $outcome;
                if (! $lockedDispatch->state->canTransitionTo($nextState, false, false, $retryTransition)) {
                    throw new LogicException('Provider outcome cannot transition this dispatch state.');
                }
                $lockedDispatch->state = $nextState;
                if ($timestamp !== null) {
                    $lockedDispatch->{$timestamp} = now();
                }
                if ($eventType === TransmittalEmailDispatchAttemptEvent::ProviderAccepted) {
                    $lockedDispatch->provider_message_id = $providerMessageId;
                }
                $lockedDispatch->locked_until = null;
                if (! $lockedDispatch->save()) {
                    throw new LogicException('Provider outcome state could not be committed with its attempt evidence.');
                }
            }

            return $attempt;
        });
    }

    private function validateIdentity(string $attemptUuid, int $sequence, ?string $requestFingerprint): void
    {
        if (preg_match('/\A[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\z/i', $attemptUuid) !== 1
            || $sequence < 1 || $sequence > 65535) {
            throw new LogicException('Attempt identity is invalid.');
        }

        if ($requestFingerprint !== null && preg_match('/\A[a-f0-9]{64}\z/i', $requestFingerprint) !== 1) {
            throw new LogicException('Request evidence must be a SHA-256 fingerprint.');
        }
    }

    private function eventUuid(string $attemptUuid, int $sequence, TransmittalEmailDispatchAttemptEvent $eventType): string
    {
        return TransmittalEmailDispatchIdentity::uuidForFingerprint(
            hash('sha256', $attemptUuid."\0".$sequence."\0".$eventType->value)
        );
    }

    private function lockV2Dispatch(TransmittalEmailDispatch $dispatch): TransmittalEmailDispatch
    {
        $identity = TransmittalEmailDispatch::query()->whereKey($dispatch->getKey())
            ->where('workflow_version', 'v2')->first();
        if ($identity === null) {
            throw new LogicException('Attempt evidence must belong to an existing V2 dispatch.');
        }
        $parent = TransmittalWorkflowBoundary::v2Query()->whereKey($identity->transmittal_id)->lockForUpdate()->first();
        if ($parent === null) {
            throw new LogicException('Attempt evidence must belong to an existing V2 transmittal.');
        }
        $recipient = TransmittalWorkflowBoundary::v2Children(\App\Models\TransmittalRecipient::class)
            ->where('transmittal_id', $parent->getKey())->whereKey($identity->recipient_id)->lockForUpdate()->first();
        if ($recipient === null) {
            throw new LogicException('Attempt evidence must belong to a recipient of its V2 transmittal.');
        }
        TransmittalWorkflowBoundary::assertChild($parent, $recipient);

        $locked = TransmittalEmailDispatch::query()
            ->whereKey($identity->getKey())
            ->where('transmittal_id', $parent->getKey())
            ->where('recipient_id', $recipient->getKey())
            ->where('workflow_version', 'v2')
            ->whereHas('transmittal', fn ($query) => $query->where('transmittals.workflow_version', 'v2'))
            ->lockForUpdate()
            ->first();

        if ($locked === null) {
            throw new LogicException('Attempt evidence must belong to an existing V2 dispatch.');
        }

        $locked->setRelation('transmittal', $parent);
        $locked->setRelation('recipient', $recipient);

        return $locked;
    }

    private function assertSameEvidence(
        TransmittalEmailDispatchAttempt $existing,
        TransmittalEmailDispatch $dispatch,
        string $attemptUuid,
        int $sequence,
        TransmittalEmailDispatchAttemptEvent $eventType,
        ?string $requestFingerprint,
        ?int $providerHttpStatus,
        ?string $providerMessageId,
        ?string $sanitizedErrorCode
    ): void {
        $sameEvidence = (string) $existing->attempt_uuid === $attemptUuid
            && (int) $existing->sequence_no === $sequence
            && (int) $existing->dispatch_id === (int) $dispatch->getKey()
            && $existing->event_type === $eventType
            && $existing->request_fingerprint === $requestFingerprint
            && ($existing->provider_http_status === null ? null : (int) $existing->provider_http_status) === $providerHttpStatus
            && $existing->provider_message_id === $providerMessageId
            && $existing->sanitized_error_code === $sanitizedErrorCode;

        if (! $sameEvidence) {
            throw new LogicException('Attempt event identity already has different immutable evidence.');
        }
    }
}

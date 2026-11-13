<?php

namespace App\Services\TransmittalV2;

use App\Enums\TransmittalEmailDispatchAttemptEvent;
use App\Enums\TransmittalEmailDispatchState;
use App\Models\TransmittalEmailDispatch;
use App\Models\TransmittalRecipient;
use App\Services\TransmittalWorkflowBoundary;
use Illuminate\Support\Facades\DB;

/** Expired processing becomes unknown; interrupted submissions are never resent. */
final class TransmittalDispatchRecovery
{
    public function markExpiredAsUnknown(int $limit = 100): int
    {
        $ids = TransmittalEmailDispatch::query()->where('workflow_version', 'v2')
            ->where('state', TransmittalEmailDispatchState::Processing->value)
            ->whereNotNull('locked_until')->where('locked_until', '<=', now())
            ->whereHas('transmittal', fn ($query) => $query->where('transmittals.workflow_version', 'v2'))
            ->orderBy('id')->limit(max(1, min($limit, 500)))->pluck('id');
        $recovered = 0;
        $recorder = new TransmittalEmailDispatchAttemptRecorder;

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, $recorder, &$recovered): void {
                $identity = TransmittalEmailDispatch::query()->whereKey($id)->where('workflow_version', 'v2')->first();
                if ($identity === null) {
                    return;
                }
                $parent = TransmittalWorkflowBoundary::v2Query()->whereKey($identity->transmittal_id)->lockForUpdate()->first();
                if ($parent === null) {
                    return;
                }
                $recipient = TransmittalWorkflowBoundary::v2Children(TransmittalRecipient::class)
                    ->where('transmittal_id', $parent->getKey())->whereKey($identity->recipient_id)->lockForUpdate()->first();
                if ($recipient === null) {
                    return;
                }
                TransmittalWorkflowBoundary::assertChild($parent, $recipient);
                $dispatch = TransmittalEmailDispatch::query()->whereKey($id)->where('workflow_version', 'v2')
                    ->where('transmittal_id', $parent->getKey())->where('recipient_id', $recipient->getKey())
                    ->lockForUpdate()->first();
                if ($dispatch === null || $dispatch->state !== TransmittalEmailDispatchState::Processing
                    || $dispatch->locked_until === null || $dispatch->locked_until->isFuture()) {
                    return;
                }
                $start = $dispatch->attemptEvents()->where('event_type', TransmittalEmailDispatchAttemptEvent::SubmissionStarted->value)
                    ->orderByDesc('occurred_at')->first();
                if ($start === null || $dispatch->attemptEvents()->where('attempt_uuid', $start->attempt_uuid)
                    ->whereIn('event_type', [TransmittalEmailDispatchAttemptEvent::ProviderAccepted->value,
                        TransmittalEmailDispatchAttemptEvent::ProviderRejected->value,
                        TransmittalEmailDispatchAttemptEvent::OutcomeUnknown->value])->exists()) {
                    return;
                }
                $recorder->append($dispatch, $start->attempt_uuid, (int) $start->sequence_no,
                    TransmittalEmailDispatchAttemptEvent::OutcomeUnknown, $start->request_fingerprint,
                    null, null, 'worker_interrupted');
                $dispatch->transmittal->histories()->create([
                    'actor_id' => null,
                    'action' => 'email_dispatch_outcome_unknown',
                    'description' => 'A V2 email worker lease expired; the provider outcome remains unknown and was not retried.',
                    'metadata' => ['dispatch_uuid' => $dispatch->dispatch_uuid],
                    'created_at' => now(),
                ]);
                $recovered++;
            });
        }

        return $recovered;
    }
}

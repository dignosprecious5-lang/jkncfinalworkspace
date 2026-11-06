<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\DealProposal;
use App\Models\DealProposalDelivery;
use App\Models\DealProposalVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Throwable;

/**
 * Processes the durable proposal email outbox on explicit invocation.
 *
 * Delivery is at-least-once: a provider may accept an email while its
 * acknowledgement is lost. A retry can then send a duplicate. Claims reduce
 * concurrent duplicates but cannot provide exactly-once SMTP delivery.
 */
class DealProposalDeliveryService
{
    private const DEFAULT_BATCH_SIZE = 25;
    private const MAX_BATCH_SIZE = 100;
    private const MAX_ATTEMPTS = 12;
    private const LEASE_MINUTES = 10;
    private const MAX_BACKOFF_SECONDS = 21600;

    /**
     * @return array{claimed: int, sent: int, failed: int, cancelled: int}
     */
    public function processDue(int $batchSize = self::DEFAULT_BATCH_SIZE): array
    {
        $claims = $this->claimDueDeliveries($batchSize);
        $summary = ['claimed' => count($claims), 'sent' => 0, 'failed' => 0, 'cancelled' => 0];

        foreach ($claims as $claim) {
            $context = $this->validateClaim($claim);
            if (! $context) {
                if (DealProposalDelivery::query()->whereKey($claim['id'])->value('status') === DealProposalDelivery::STATUS_SUPERSEDED) {
                    $summary['cancelled']++;
                }
                continue;
            }

            try {
                if (! $this->sendIssuedProposal($context, $claim)) {
                    $summary['cancelled']++;
                    continue;
                }
                $this->markSent($claim);
                $summary['sent']++;
            } catch (Throwable) {
                $this->markFailure($claim);
                $summary['failed']++;
            }
        }

        return $summary;
    }

    /**
     * Claim only; this method does not send mail. Kept public to let callers
     * perform bounded worker coordination without holding a transaction open.
     *
     * @return list<array{id: int, claimed_at: string, attempt_count: int}>
     */
    public function claimDueDeliveries(int $batchSize = self::DEFAULT_BATCH_SIZE): array
    {
        $this->assertBatchSize($batchSize);

        return DB::transaction(function () use ($batchSize): array {
            $now = now();
            $staleClaims = DealProposalDelivery::query()
                ->where('status', DealProposalDelivery::STATUS_PROCESSING)
                ->whereNotNull('lease_expires_at')
                ->where('lease_expires_at', '<=', $now)
                ->orderBy('id')
                ->lock('for update skip locked')
                ->limit($batchSize)
                ->get();

            foreach ($staleClaims as $delivery) {
                $common = [
                    'claimed_at' => null,
                    'lease_expires_at' => null,
                ];
                if ((int) $delivery->attempt_count >= self::MAX_ATTEMPTS) {
                    $delivery->transitionTo(DealProposalDelivery::STATUS_FAILED, $common + [
                        'last_error_code' => 'DELIVERY_LEASE_EXPIRED',
                        'last_error_message' => 'The delivery worker lease expired.',
                    ]);
                } else {
                    $delivery->transitionTo(DealProposalDelivery::STATUS_PENDING, $common + [
                        'next_attempt_at' => $now,
                    ]);
                }
            }

            $due = DealProposalDelivery::query()
                ->whereIn('status', [DealProposalDelivery::STATUS_PENDING, DealProposalDelivery::STATUS_FAILED])
                ->where('attempt_count', '<', self::MAX_ATTEMPTS)
                ->where(function ($query) use ($now): void {
                    $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', $now);
                })
                ->orderBy('id')
                ->lock('for update skip locked')
                ->limit($batchSize)
                ->get();

            $claims = [];
            foreach ($due as $delivery) {
                if ($delivery->status === DealProposalDelivery::STATUS_FAILED) {
                    $delivery->transitionTo(DealProposalDelivery::STATUS_PENDING, [
                        'next_attempt_at' => $now,
                    ]);
                }

                $claimedAt = now();
                $delivery->transitionTo(DealProposalDelivery::STATUS_PROCESSING, [
                    'attempt_count' => (int) $delivery->attempt_count + 1,
                    'claimed_at' => $claimedAt,
                    'lease_expires_at' => $claimedAt->copy()->addMinutes(self::LEASE_MINUTES),
                    'next_attempt_at' => null,
                    'last_error_code' => null,
                    'last_error_message' => null,
                ]);
                $claims[] = [
                    'id' => (int) $delivery->id,
                    'claimed_at' => $claimedAt->format('Y-m-d H:i:s'),
                    'attempt_count' => (int) $delivery->attempt_count,
                ];
            }

            return $claims;
        });
    }

    private function validateClaim(array $claim): ?array
    {
        $deliveryProbe = DealProposalDelivery::query()->find($claim['id']);
        if (! $deliveryProbe) {
            return null;
        }
        $versionProbe = DealProposalVersion::query()->find($deliveryProbe->deal_proposal_version_id);
        if (! $versionProbe) {
            $this->cancelClaim($claim, 'VERSION_MISSING');
            return null;
        }
        $proposalProbe = DealProposal::query()->find($versionProbe->deal_proposal_id);
        if (! $proposalProbe) {
            $this->cancelClaim($claim, 'PROPOSAL_MISSING');
            return null;
        }
        $dealId = (int) $proposalProbe->deal_id;

        return DB::transaction(function () use ($claim, $versionProbe, $proposalProbe, $dealId): ?array {
            $deal = Deal::query()->lockForUpdate()->find($dealId);
            if (! $deal) {
                $this->cancelClaimWithinTransaction($claim, 'DEAL_MISSING');
                return null;
            }
            $proposal = DealProposal::query()
                ->whereKey($proposalProbe->id)
                ->where('deal_id', $deal->id)
                ->lockForUpdate()
                ->first();
            if (! $proposal) {
                $this->cancelClaimWithinTransaction($claim, 'PROPOSAL_MISSING');
                return null;
            }
            $version = DealProposalVersion::query()
                ->whereKey($versionProbe->id)
                ->where('deal_proposal_id', $proposal->id)
                ->lockForUpdate()
                ->first();
            if (! $version) {
                $this->cancelClaimWithinTransaction($claim, 'VERSION_MISSING');
                return null;
            }
            $delivery = DealProposalDelivery::query()->lockForUpdate()->find($claim['id']);
            if (! $this->ownsActiveClaim($delivery, $claim)) {
                return null;
            }

            $token = (string) $proposal->client_access_token;
            $recipient = mb_strtolower(trim((string) $delivery->recipient_email));
            $issuedRecipient = mb_strtolower(trim((string) $version->issued_to_email));
            $proposalStatus = strtolower(trim((string) $proposal->status));
            $invalidProposal = in_array($proposalStatus, ['approved', 'rejected', 'cancelled', 'invalid', 'expired', 'deleted'], true)
                || filled($proposal->client_approved_at)
                || filled($proposal->quotation_approved_at)
                || strtolower(trim((string) $proposal->quotation_status)) === 'approved';
            $expired = ! $proposal->client_access_expires_at
                || $proposal->client_access_expires_at->isPast()
                || ! $version->client_access_expires_at
                || $version->client_access_expires_at->isPast();

            if (
                $token === ''
                || ! filled($version->issued_token_sha256)
                || ! hash_equals((string) $version->issued_token_sha256, hash('sha256', $token))
                || $expired
                || $recipient === ''
                || $issuedRecipient === ''
                || ! hash_equals($issuedRecipient, $recipient)
                || $invalidProposal
                || strtolower(trim((string) $deal->delete_request_status)) === 'pending'
                || $deal->isArchived()
            ) {
                $delivery->transitionTo(DealProposalDelivery::STATUS_SUPERSEDED, [
                    'claimed_at' => null,
                    'lease_expires_at' => null,
                    'last_error_code' => 'DELIVERY_INVALIDATED',
                    'last_error_message' => 'The issued proposal link is no longer deliverable.',
                ]);

                return null;
            }

            return [
                'delivery_id' => (int) $delivery->id,
                'version_id' => (int) $version->id,
                'recipient' => $recipient,
                'token' => $token,
                'client_name' => $this->clientNameFromSnapshot($version),
                'expires_at' => $version->client_access_expires_at,
                'version_number' => (int) $version->version_number,
                'deal' => $deal,
                'proposal' => $proposal,
            ];
        });
    }

    private function sendIssuedProposal(array $context, array $claim): bool
    {
        return DB::transaction(function () use ($context, $claim): bool {
            $deal = Deal::query()->lockForUpdate()->find($context['deal']->id);
            $delivery = DealProposalDelivery::query()->lockForUpdate()->find($claim['id']);
            if (! $deal || ! $this->ownsActiveClaim($delivery, $claim)) {
                return false;
            }

            if ($deal->isArchived()) {
                $delivery->transitionTo(DealProposalDelivery::STATUS_SUPERSEDED, [
                    'claimed_at' => null,
                    'lease_expires_at' => null,
                    'last_error_code' => 'DEAL_ARCHIVED',
                    'last_error_message' => 'The Deal was archived before proposal delivery.',
                ]);

                return false;
            }

            $clientUrl = route('deals.proposal.client.show', ['token' => $context['token']]);
            $emailHtml = view('emails.deals.proposal-client-link', [
                'deal' => $deal,
                'proposal' => $context['proposal'],
                'clientName' => $context['client_name'],
                'clientUrl' => $clientUrl,
                'expiresAt' => $context['expires_at'],
            ])->render();

            Mail::html($emailHtml, function ($message) use ($context): void {
                $message
                    ->from(config('mail.from.address'), 'John Kelly & Company')
                    ->to($context['recipient'])
                    ->subject('Proposal for '.$context['client_name'].' (Version '.$context['version_number'].')');
            });

            return true;
        });
    }

    private function markSent(array $claim): void
    {
        DB::transaction(function () use ($claim): void {
            $delivery = DealProposalDelivery::query()->lockForUpdate()->find($claim['id']);
            if ($this->ownsActiveClaim($delivery, $claim, false)) {
                $delivery->transitionTo(DealProposalDelivery::STATUS_SENT, [
                    'sent_at' => now(),
                    'claimed_at' => null,
                    'lease_expires_at' => null,
                    'next_attempt_at' => null,
                    'last_error_code' => null,
                    'last_error_message' => null,
                ]);
            }
        });
    }

    private function markFailure(array $claim): void
    {
        DB::transaction(function () use ($claim): void {
            $delivery = DealProposalDelivery::query()->lockForUpdate()->find($claim['id']);
            if (! $this->ownsActiveClaim($delivery, $claim, false)) {
                return;
            }

            $attempts = (int) $delivery->attempt_count;
            $isFinal = $attempts >= self::MAX_ATTEMPTS;
            $retryAt = now()->addSeconds($this->backoffSeconds($attempts));
            $delivery->transitionTo(
                $isFinal ? DealProposalDelivery::STATUS_FAILED : DealProposalDelivery::STATUS_PENDING,
                [
                    'claimed_at' => null,
                    'lease_expires_at' => null,
                    'next_attempt_at' => $isFinal ? null : $retryAt,
                    'last_error_code' => 'MAIL_DELIVERY_FAILED',
                    'last_error_message' => 'The mail provider did not confirm delivery.',
                ],
            );
        });
    }

    private function cancelClaim(array $claim, string $reason): void
    {
        DB::transaction(fn () => $this->cancelClaimWithinTransaction($claim, $reason));
    }

    private function cancelClaimWithinTransaction(array $claim, string $reason): void
    {
        $delivery = DealProposalDelivery::query()->lockForUpdate()->find($claim['id']);
        if (! $this->ownsActiveClaim($delivery, $claim)) {
            return;
        }

        $code = preg_match('/\A[A-Z0-9_]{1,80}\z/', $reason) === 1 ? $reason : 'DELIVERY_INVALIDATED';
        $delivery->transitionTo(DealProposalDelivery::STATUS_SUPERSEDED, [
            'claimed_at' => null,
            'lease_expires_at' => null,
            'last_error_code' => $code,
            'last_error_message' => 'The issued proposal link is no longer deliverable.',
        ]);
    }

    private function ownsActiveClaim(?DealProposalDelivery $delivery, array $claim, bool $requireUnexpiredLease = true): bool
    {
        if (
            ! $delivery
            || $delivery->status !== DealProposalDelivery::STATUS_PROCESSING
            || ! $delivery->claimed_at
            || $delivery->claimed_at->format('Y-m-d H:i:s') !== $claim['claimed_at']
        ) {
            return false;
        }

        return ! $requireUnexpiredLease || ($delivery->lease_expires_at && $delivery->lease_expires_at->isFuture());
    }

    private function clientNameFromSnapshot(DealProposalVersion $version): string
    {
        $resolved = $version->resolved_proposal_data ?? [];
        foreach (['client_name', 'business_name'] as $field) {
            $value = trim((string) ($resolved[$field] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return 'Client';
    }

    private function backoffSeconds(int $attempt): int
    {
        $exponent = max(0, min($attempt - 1, 16));

        return min(30 * (2 ** $exponent), self::MAX_BACKOFF_SECONDS);
    }

    private function assertBatchSize(int $batchSize): void
    {
        if ($batchSize < 1 || $batchSize > self::MAX_BATCH_SIZE) {
            throw new InvalidArgumentException('Delivery batch size must be between 1 and '.self::MAX_BATCH_SIZE.'.');
        }
    }
}

<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\DealProposal;
use App\Models\DealProposalDelivery;
use App\Models\DealProposalVersion;
use App\Models\User;
use App\Policies\DealPolicy;
use DateTimeInterface;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Creates an immutable proposal version, private PDF artifact, outbox row, and
 * active token atomically. The caller must supply data resolved consistently
 * and authorized before invoking this coordinator; this service performs no
 * live Contact, catalog, or template lookups and sends no email.
 */
class DealProposalIssuanceService
{
    private const CLIENT_LINK_TTL_DAYS = 14;

    public function __construct(
        private readonly DealProposalSnapshotService $snapshotService,
        private readonly DealProposalPdfRenderer $pdfRenderer,
        private readonly DealProposalPdfArtifactService $artifactService,
    ) {
    }

    /**
     * @return array{
     *     version: DealProposalVersion,
     *     delivery: DealProposalDelivery,
     *     access_token: ?string,
     *     is_current: bool,
     *     replayed: bool
     * }
     */
    public function issue(
        User $actor,
        int $dealId,
        int $proposalId,
        string $issuanceRequestKey,
        string $recipientEmail,
        array $resolvedDocumentData,
        string $resolvedProposalHtml,
        array $commercialValues,
        array $sourceMetadata,
        array $documentIdentifiers,
        DateTimeInterface $issueDate,
    ): array {
        $issuanceRequestKey = $this->validateRequestKey($issuanceRequestKey);
        $recipientEmail = $this->normalizeRecipient($recipientEmail);
        $this->assertIssueDateMatchesResolvedData($resolvedDocumentData, $issueDate);

        $requestFingerprint = DealProposalVersion::fingerprintIssuanceRequest(
            $proposalId,
            $recipientEmail,
            [
                'issue_date' => $issueDate->format(DateTimeInterface::ATOM),
                'resolved_document_data' => $resolvedDocumentData,
                'resolved_proposal_html' => $resolvedProposalHtml,
                'commercial_values' => $commercialValues,
                'source_metadata' => $sourceMetadata,
                'document_identifiers' => $documentIdentifiers,
            ],
        );

        $artifact = null;
        try {
            return DB::transaction(function () use (
                $actor,
                $dealId,
                $proposalId,
                $issuanceRequestKey,
                $recipientEmail,
                $resolvedDocumentData,
                $resolvedProposalHtml,
                $commercialValues,
                $sourceMetadata,
                $documentIdentifiers,
                $issueDate,
                $requestFingerprint,
                &$artifact,
            ): array {
                // Lock order is Deal -> DealProposal -> existing version -> delivery.
                $deal = Deal::query()->lockForUpdate()->findOrFail($dealId);
                $this->assertIssuanceActor($actor, $deal);

                $proposal = DealProposal::query()
                    ->whereKey($proposalId)
                    ->where('deal_id', $deal->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $priorVersion = DealProposalVersion::query()
                    ->where('issuance_request_key', $issuanceRequestKey)
                    ->lockForUpdate()
                    ->first();

                if ($priorVersion) {
                    $priorVersion = $this->assertMatchingPriorRequest(
                        $priorVersion,
                        $proposalId,
                        $issuanceRequestKey,
                        $requestFingerprint,
                    );
                    $delivery = DealProposalDelivery::query()
                        ->where('deal_proposal_version_id', $priorVersion->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    return $this->resultForExistingVersion($proposal, $priorVersion, $delivery);
                }

                $this->assertIssuable($deal, $proposal);

                $pdfBytes = $this->pdfRenderer->render(
                    $resolvedDocumentData,
                    $resolvedProposalHtml,
                    $issueDate,
                );
                $artifact = $this->artifactService->store((int) $proposal->id, $pdfBytes);
                $snapshot = $this->snapshotService->build(
                    $resolvedDocumentData,
                    $commercialValues,
                    $sourceMetadata,
                    $documentIdentifiers,
                    $resolvedProposalHtml,
                    $artifact['path'],
                    $artifact['sha256'],
                );

                $lastVersionNumber = DealProposalVersion::query()
                    ->where('deal_proposal_id', $proposal->id)
                    ->orderByDesc('version_number')
                    ->lockForUpdate()
                    ->value('version_number');
                $versionNumber = ((int) $lastVersionNumber) + 1;
                $accessToken = Str::random(64);
                $expiresAt = now()->addDays(self::CLIENT_LINK_TTL_DAYS);

                // Every immutable field, including idempotency metadata, is set at creation.
                $version = DealProposalVersion::query()->create(array_merge($snapshot, [
                    'deal_proposal_id' => $proposal->id,
                    'version_number' => $versionNumber,
                    'issued_token_sha256' => hash('sha256', $accessToken),
                    'issued_to_email' => $recipientEmail,
                    'issued_at' => now(),
                    'client_access_expires_at' => $expiresAt,
                    'issued_by_user_id' => $actor->id,
                    'issuance_request_key' => $issuanceRequestKey,
                    'issuance_request_fingerprint' => $requestFingerprint,
                ]));

                $delivery = DealProposalDelivery::query()->create([
                    'deal_proposal_version_id' => $version->id,
                    'idempotency_key' => 'deal-proposal-delivery-'.$version->id,
                    'recipient_email' => $recipientEmail,
                    'status' => DealProposalDelivery::STATUS_PENDING,
                    'attempt_count' => 0,
                    'next_attempt_at' => now(),
                ]);

                // Do not mark the email as sent here. Delivery is processed separately.
                $proposal->forceFill([
                    'client_access_token' => $accessToken,
                    'client_access_expires_at' => $expiresAt,
                ])->save();

                return [
                    'version' => $version,
                    'delivery' => $delivery,
                    'access_token' => $accessToken,
                    'is_current' => true,
                    'replayed' => false,
                ];
            });
        } catch (Throwable $exception) {
            $this->cleanupAfterRollback($artifact);

            if ($exception instanceof QueryException) {
                $retry = $this->resolveUniqueKeyRace($actor, $dealId, $proposalId, $issuanceRequestKey, $requestFingerprint);
                if ($retry !== null) {
                    return $retry;
                }
            }

            throw $exception;
        }
    }

    private function validateRequestKey(string $key): string
    {
        $key = strtolower(trim($key));
        if (preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/', $key) !== 1) {
            throw new InvalidArgumentException('A stable UUID issuance request key is required.');
        }

        return $key;
    }

    private function normalizeRecipient(string $email): string
    {
        $email = mb_strtolower(trim($email));
        if ($email === '' || strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('A valid proposal recipient email address is required.');
        }

        return $email;
    }

    private function assertIssueDateMatchesResolvedData(array $data, DateTimeInterface $issueDate): void
    {
        if (
            ($data['date'] ?? null) !== $issueDate->format('F d, Y')
            || (string) ($data['year'] ?? '') !== $issueDate->format('Y')
        ) {
            throw new InvalidArgumentException('Resolved proposal data must contain the explicit issue date and year.');
        }
    }

    private function assertIssuanceActor(User $actor, Deal $deal): void
    {
        if ($deal->isArchived()) {
            throw new DomainException('Archived Deals cannot issue proposals.');
        }

        if (! app(DealPolicy::class)->update($actor, $deal)) {
            throw new AuthorizationException('You do not have permission to issue a proposal for this Deal.');
        }
    }

    private function assertIssuable(Deal $deal, DealProposal $proposal): void
    {
        if ($deal->isArchived()) {
            throw new DomainException('Archived Deals cannot issue proposals.');
        }

        if (
            filled($proposal->client_approved_at)
            || filled($proposal->quotation_approved_at)
            || strtolower(trim((string) $proposal->status)) === 'approved'
            || strtolower(trim((string) $proposal->quotation_status)) === 'approved'
        ) {
            throw new DomainException('Approved proposals cannot be issued again.');
        }

        if (strtolower(trim((string) $deal->delete_request_status)) === 'pending') {
            throw new DomainException('A Deal with a pending deletion request cannot issue a proposal.');
        }
    }

    private function assertMatchingPriorRequest(
        DealProposalVersion $version,
        int $proposalId,
        string $requestKey,
        string $requestFingerprint,
    ): DealProposalVersion {
        if (
            (int) $version->deal_proposal_id !== $proposalId
            || $version->issuance_request_key !== $requestKey
            || ! filled($version->issuance_request_fingerprint)
            || ! hash_equals((string) $version->issuance_request_fingerprint, $requestFingerprint)
        ) {
            throw new DomainException('This issuance request key was already used with conflicting proposal or recipient details.');
        }

        return $version;
    }

    private function resultForExistingVersion(
        DealProposal $proposal,
        DealProposalVersion $version,
        DealProposalDelivery $delivery,
    ): array {
        $currentToken = (string) $proposal->client_access_token;
        $isCurrent = $currentToken !== ''
            && filled($version->issued_token_sha256)
            && hash_equals((string) $version->issued_token_sha256, hash('sha256', $currentToken));

        return [
            'version' => $version,
            'delivery' => $delivery,
            // Superseded raw tokens are deliberately unrecoverable from version rows.
            'access_token' => $isCurrent ? $currentToken : null,
            'is_current' => $isCurrent,
            'replayed' => true,
        ];
    }

    private function resolveUniqueKeyRace(
        User $actor,
        int $dealId,
        int $proposalId,
        string $requestKey,
        string $fingerprint,
    ): ?array
    {
        try {
            return DB::transaction(function () use ($actor, $dealId, $proposalId, $requestKey, $fingerprint): ?array {
                // Reuse the same global order even when resolving a duplicate-key race.
                $deal = Deal::query()->lockForUpdate()->findOrFail($dealId);
                $this->assertIssuanceActor($actor, $deal);
                $proposal = DealProposal::query()->whereKey($proposalId)->lockForUpdate()->firstOrFail();
                if ((int) $proposal->deal_id !== (int) $deal->id) {
                    throw new DomainException('The issuance request proposal does not belong to the requested Deal.');
                }

                $version = DealProposalVersion::resolveIssuanceRetry($proposalId, $requestKey, $fingerprint);
                if (! $version) {
                    return null;
                }
                $lockedVersion = DealProposalVersion::query()->lockForUpdate()->findOrFail($version->id);
                $delivery = DealProposalDelivery::query()
                    ->where('deal_proposal_version_id', $lockedVersion->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                return $this->resultForExistingVersion($proposal, $lockedVersion, $delivery);
            });
        } catch (DomainException $exception) {
            throw $exception;
        } catch (QueryException) {
            return null;
        }
    }

    private function cleanupAfterRollback(?array $artifact): void
    {
        if (! $artifact) {
            return;
        }

        try {
            $this->artifactService->cleanupUnreferenced(
                $artifact,
                static fn (string $path): bool => DealProposalVersion::query()
                    ->where('artifact_private_path', $path)
                    ->exists(),
            );
        } catch (Throwable) {
            // Fail closed: a cleanup lookup failure leaves the private artifact for reconciliation.
        }
    }
}

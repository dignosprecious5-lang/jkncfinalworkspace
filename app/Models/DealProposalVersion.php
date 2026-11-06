<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use DomainException;
use InvalidArgumentException;
use LogicException;

/**
 * Issued proposal snapshots are append-only through this model.
 * Database administrators and direct SQL remain outside this application-level guarantee.
 */
class DealProposalVersion extends Model
{
    protected $fillable = [
        'deal_proposal_id',
        'version_number',
        'snapshot_schema_version',
        'resolved_proposal_data',
        'commercial_values',
        'source_metadata',
        'document_identifiers',
        'rendered_html',
        'artifact_private_path',
        'artifact_sha256',
        'issued_token_sha256',
        'issued_to_email',
        'issued_at',
        'client_access_expires_at',
        'issued_by_user_id',
        'issuance_request_key',
        'issuance_request_fingerprint',
    ];

    protected $casts = [
        'resolved_proposal_data' => 'array',
        'commercial_values' => 'array',
        'source_metadata' => 'array',
        'document_identifiers' => 'array',
        'issued_at' => 'datetime',
        'client_access_expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Issued Deal proposal versions are immutable.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Issued Deal proposal versions cannot be deleted through the application.');
        });
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(DealProposal::class, 'deal_proposal_id');
    }

    public function approvalEvents(): HasMany
    {
        return $this->hasMany(DealProposalApprovalEvent::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(DealProposalDelivery::class);
    }

    /**
     * Fingerprint a canonical issuance payload together with its proposal and recipient.
     * The caller must include the resolved document inputs in $requestPayload.
     */
    public static function fingerprintIssuanceRequest(
        int $proposalId,
        string $recipientEmail,
        array $requestPayload,
    ): string {
        $recipientEmail = mb_strtolower(trim($recipientEmail));
        if ($proposalId < 1 || $recipientEmail === '') {
            throw new InvalidArgumentException('A proposal ID and recipient are required to fingerprint an issuance request.');
        }

        $canonicalPayload = self::canonicalize($requestPayload);
        $encoded = json_encode([
            'proposal_id' => $proposalId,
            'recipient_email' => $recipientEmail,
            'request_payload' => $canonicalPayload,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash('sha256', $encoded);
    }

    /** Return an identical prior request, or reject reuse of its key for different input. */
    public static function resolveIssuanceRetry(
        int $proposalId,
        string $requestKey,
        string $requestFingerprint,
    ): ?self {
        if ($proposalId < 1 || trim($requestKey) === '' || strlen($requestKey) > 80) {
            throw new InvalidArgumentException('A valid proposal ID and issuance request key are required.');
        }
        if (preg_match('/\A[a-f0-9]{64}\z/i', $requestFingerprint) !== 1) {
            throw new InvalidArgumentException('Issuance request fingerprint must be a SHA-256 digest.');
        }

        $existing = static::query()
            ->where('issuance_request_key', $requestKey)
            ->first();

        if (! $existing) {
            return null;
        }

        if (
            (int) $existing->deal_proposal_id !== $proposalId
            ||
            ! filled($existing->issuance_request_fingerprint)
            || ! hash_equals((string) $existing->issuance_request_fingerprint, strtolower($requestFingerprint))
        ) {
            throw new DomainException('This issuance request key was already used with different proposal or recipient data.');
        }

        return $existing;
    }

    private static function canonicalize(array $value): array
    {
        if (array_is_list($value)) {
            return array_map(
                static fn ($item) => is_array($item) ? self::canonicalize($item) : $item,
                $value,
            );
        }

        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = self::canonicalize($item);
            }
        }

        return $value;
    }
}

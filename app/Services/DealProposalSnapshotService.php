<?php

namespace App\Services;

use InvalidArgumentException;
use JsonException;

/**
 * Builds a snapshot payload only from caller-supplied, already-resolved values.
 * It deliberately does not query or render mutable application records.
 */
class DealProposalSnapshotService
{
    public const SNAPSHOT_SCHEMA_VERSION = 1;

    /**
     * @throws InvalidArgumentException when the immutable artifact metadata or data is invalid
     * @throws JsonException when a supplied snapshot component is not JSON encodable
     */
    public function build(
        array $resolvedProposalData,
        array $commercialValues,
        array $sourceMetadata,
        array $documentIdentifiers,
        string $renderedHtml,
        string $privateArtifactPath,
        string $artifactSha256,
    ): array {
        if (trim($renderedHtml) === '') {
            throw new InvalidArgumentException('Rendered proposal HTML is required for an issued snapshot.');
        }

        $this->assertPrivateArtifactPath($privateArtifactPath);
        $artifactSha256 = strtolower($artifactSha256);
        if (preg_match('/\A[a-f0-9]{64}\z/', $artifactSha256) !== 1) {
            throw new InvalidArgumentException('The PDF artifact SHA-256 digest must contain 64 hexadecimal characters.');
        }

        $commercialValues = $this->normalizeCommercialValues($commercialValues);
        foreach ([$resolvedProposalData, $sourceMetadata, $documentIdentifiers] as $component) {
            json_encode($component, JSON_THROW_ON_ERROR);
            $this->assertNoCredentialFields($component);
        }

        return [
            'snapshot_schema_version' => self::SNAPSHOT_SCHEMA_VERSION,
            'resolved_proposal_data' => $resolvedProposalData,
            'commercial_values' => $commercialValues,
            'source_metadata' => $sourceMetadata,
            'document_identifiers' => $documentIdentifiers,
            'rendered_html' => $renderedHtml,
            'artifact_private_path' => $privateArtifactPath,
            'artifact_sha256' => $artifactSha256,
        ];
    }

    private function normalizeCommercialValues(array $commercialValues): array
    {
        foreach ($commercialValues as $name => $amount) {
            if (! is_string($name) || ! is_string($amount)) {
                throw new InvalidArgumentException('Commercial amounts must be named decimal strings.');
            }

            $commercialValues[$name] = $this->normalizeMoney($amount);
        }

        return $commercialValues;
    }

    private function normalizeMoney(string $amount): string
    {
        $amount = trim($amount);
        if (preg_match('/\A(-?)(\d{1,13})(?:\.(\d{1,2}))?\z/', $amount, $matches) !== 1) {
            throw new InvalidArgumentException('Commercial amounts must be decimal strings with at most 13 integer and 2 fractional digits.');
        }

        $whole = ltrim($matches[2], '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad($matches[3] ?? '', 2, '0');
        $isZero = $whole === '0' && $fraction === '00';
        $sign = ($matches[1] ?? '') === '-' && ! $isZero ? '-' : '';

        return $sign.$whole.'.'.$fraction;
    }

    private function assertPrivateArtifactPath(string $path): void
    {
        if (
            preg_match('/\Adeal-proposal-versions\/[1-9][0-9]*\/[A-Za-z0-9][A-Za-z0-9._-]*\.pdf\z/', $path) !== 1
            || str_contains($path, '..')
            || str_contains($path, '\\')
        ) {
            throw new InvalidArgumentException('PDF artifacts must use a relative deal-proposal-versions/{proposal-id}/ path.');
        }
    }

    private function assertNoCredentialFields(array $data): void
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && preg_match('/(?:token|secret|password)/i', $key) === 1) {
                throw new InvalidArgumentException('Credential fields must not be copied into a proposal snapshot.');
            }

            if (is_array($value)) {
                $this->assertNoCredentialFields($value);
            }
        }
    }
}

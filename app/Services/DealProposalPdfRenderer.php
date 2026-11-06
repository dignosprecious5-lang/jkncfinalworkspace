<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use DateTimeInterface;
use RuntimeException;
use Throwable;

/**
 * Renders an already-resolved Deal proposal into PDF bytes without storage writes.
 * The caller supplies all mutable document content and the explicit issue date.
 */
class DealProposalPdfRenderer
{
    public function render(
        array $resolvedDocumentData,
        string $resolvedProposalHtml,
        DateTimeInterface $issueDate,
    ): string {
        if (trim($resolvedProposalHtml) === '') {
            throw new RuntimeException('Resolved proposal HTML is required for PDF rendering.');
        }

        $expectedDate = $issueDate->format('F d, Y');
        $expectedYear = $issueDate->format('Y');
        if (
            ($resolvedDocumentData['date'] ?? null) !== $expectedDate
            || (string) ($resolvedDocumentData['year'] ?? '') !== $expectedYear
        ) {
            throw new RuntimeException('Resolved proposal data must contain the explicit issue date and year.');
        }

        try {
            $pdf = Pdf::loadView('deals.proposal.pdf', [
                'documentData' => $resolvedDocumentData,
                'proposalHtml' => $resolvedProposalHtml,
            ])->setPaper('a4', 'portrait');
            $bytes = $pdf->output();
        } catch (Throwable $exception) {
            throw new RuntimeException('Unable to render the resolved Deal proposal PDF.', previous: $exception);
        }

        if (! is_string($bytes) || $bytes === '') {
            throw new RuntimeException('The Deal proposal renderer returned no PDF bytes.');
        }

        try {
            DealProposalPdfArtifactService::assertValidPdfBytes($bytes);
        } catch (RuntimeException $exception) {
            throw new RuntimeException('The Deal proposal renderer returned invalid or incomplete PDF bytes.', previous: $exception);
        }

        return $bytes;
    }
}

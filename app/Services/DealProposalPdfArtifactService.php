<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Stores already-rendered proposal PDF bytes on Laravel's private local disk.
 * It does not render documents or expose artifacts over HTTP.
 */
class DealProposalPdfArtifactService
{
    private const PRIVATE_DIRECTORY = 'deal-proposal-versions';

    /** @var array<string, string> Artifacts successfully created by this service instance. */
    private array $createdArtifacts = [];

    /** @return array{path: string, byte_size: int, sha256: string} */
    public function store(int $proposalId, string $pdfBytes): array
    {
        if ($proposalId < 1) {
            throw new RuntimeException('A positive proposal ID is required for private PDF storage.');
        }

        self::assertValidPdfBytes($pdfBytes);

        $disk = Storage::disk('local');
        $directory = self::PRIVATE_DIRECTORY.'/'.$proposalId;

        try {
            $disk->makeDirectory($directory);
        } catch (Throwable $exception) {
            throw new RuntimeException('Unable to prepare private proposal artifact storage.', previous: $exception);
        }

        $expectedSize = strlen($pdfBytes);
        $expectedHash = hash('sha256', $pdfBytes);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $relativePath = $directory.'/'.Str::uuid().'.pdf';
            $absolutePath = $this->resolvePrivatePath($disk, $relativePath);
            $stream = @fopen($absolutePath, 'xb');

            if ($stream === false) {
                if (file_exists($absolutePath)) {
                    continue;
                }

                throw new RuntimeException('Unable to create the private proposal PDF artifact without overwriting a file.');
            }

            $createdByCall = true;
            try {
                $this->writeAll($stream, $pdfBytes);
                if (! fflush($stream)) {
                    throw new RuntimeException('The private proposal PDF could not be flushed to storage.');
                }
            } catch (Throwable $exception) {
                fclose($stream);
                if ($createdByCall) {
                    @unlink($absolutePath);
                }
                throw $exception instanceof RuntimeException
                    ? $exception
                    : new RuntimeException('Unable to write the private proposal PDF artifact.', previous: $exception);
            }
            if (! fclose($stream)) {
                @unlink($absolutePath);
                throw new RuntimeException('The private proposal PDF stream did not close successfully.');
            }

            try {
                $storedExists = $disk->exists($relativePath);
                $storedSize = $storedExists ? $disk->size($relativePath) : false;
                $storedHash = is_file($absolutePath) ? @hash_file('sha256', $absolutePath) : false;
                $verified = $storedExists
                    && $storedSize === $expectedSize
                    && is_string($storedHash)
                    && hash_equals($expectedHash, $storedHash);
            } catch (Throwable $exception) {
                @unlink($absolutePath);
                throw new RuntimeException('Unable to verify the stored private proposal PDF.', previous: $exception);
            }

            if (! $verified) {
                @unlink($absolutePath);
                throw new RuntimeException('The stored private proposal PDF failed byte-count or SHA-256 verification.');
            }

            $this->createdArtifacts[$relativePath] = $expectedHash;

            return [
                'path' => $relativePath,
                'byte_size' => $expectedSize,
                'sha256' => $expectedHash,
            ];
        }

        throw new RuntimeException('Unable to allocate a unique private proposal PDF path after repeated collisions.');
    }

    /**
     * Delete only a verified artifact created by this service instance and only
     * when the caller confirms no committed version references its path.
     */
    public function cleanupUnreferenced(array $artifact, callable $isReferenced): bool
    {
        $path = $artifact['path'] ?? null;
        $sha256 = $artifact['sha256'] ?? null;

        if (
            ! is_string($path)
            || ! is_string($sha256)
            || ! isset($this->createdArtifacts[$path])
            || ! hash_equals($this->createdArtifacts[$path], strtolower($sha256))
        ) {
            return false;
        }

        if ($isReferenced($path) !== false) {
            return false;
        }

        $disk = Storage::disk('local');
        try {
            $absolutePath = $this->resolvePrivatePath($disk, $path);
        } catch (RuntimeException) {
            return false;
        }
        $currentHash = is_file($absolutePath) ? @hash_file('sha256', $absolutePath) : false;
        if (! is_string($currentHash) || ! hash_equals($this->createdArtifacts[$path], $currentHash)) {
            return false;
        }

        if (! @unlink($absolutePath)) {
            return false;
        }

        unset($this->createdArtifacts[$path]);

        return true;
    }

    public static function assertValidPdfBytes(string $bytes): void
    {
        if (strlen($bytes) < 20 || preg_match('/\A%PDF-[0-9]\.[0-9]/', $bytes) !== 1) {
            throw new RuntimeException('Proposal PDF bytes are invalid or incomplete.');
        }

        if (preg_match_all('/startxref\s+(\d+)\s+%%EOF[\x00\x09\x0A\x0C\x0D\x20]*\z/s', $bytes, $matches) < 1) {
            throw new RuntimeException('Proposal PDF bytes are invalid or incomplete.');
        }

        $crossReferenceOffset = (int) end($matches[1]);
        if ($crossReferenceOffset < 0 || $crossReferenceOffset >= strlen($bytes)) {
            throw new RuntimeException('Proposal PDF bytes contain an invalid cross-reference offset.');
        }

        $crossReference = substr($bytes, $crossReferenceOffset);
        $hasCrossReferenceTable = preg_match(
            '/\Axref\s.*?trailer\s*<<(?:(?!startxref).)*>>/s',
            $crossReference,
        ) === 1;
        $hasCrossReferenceStream = false;

        if (! $hasCrossReferenceTable) {
            $streamOffset = false;
            if (preg_match('/(?:\r\n|\n|\r)stream(?:\r\n|\n|\r)/', $crossReference, $streamMatch, PREG_OFFSET_CAPTURE) === 1) {
                $streamOffset = $streamMatch[0][1];
            }
            if ($streamOffset !== false) {
                $dictionary = substr($crossReference, 0, $streamOffset);
                $hasValidObjectHeader = preg_match('/\A\d+\s+\d+\s+obj\b/', $crossReference) === 1;
                $hasXrefType = preg_match('/\/Type\s*\/XRef\b/', $dictionary) === 1;
                $hasDirectLength = preg_match('/\/Length\s+(\d+)\b/', $dictionary, $lengthMatch) === 1;

                if ($hasValidObjectHeader && $hasXrefType && $hasDirectLength) {
                    $length = (int) $lengthMatch[1];
                    $streamDataStart = $streamOffset + strlen($streamMatch[0][0]);
                    $afterStreamData = substr($crossReference, $streamDataStart + $length);
                    $hasCompleteStreamObject = strlen($crossReference) >= $streamDataStart + $length
                        && preg_match('/\A[\x00\x09\x0A\x0C\x0D\x20]*endstream\b[\x00\x09\x0A\x0C\x0D\x20]+endobj\b/s', $afterStreamData) === 1;
                    $hasCrossReferenceStream = $length > 0 && $hasCompleteStreamObject;
                }
            }
        }

        if (! $hasCrossReferenceTable && ! $hasCrossReferenceStream) {
            throw new RuntimeException('Proposal PDF bytes contain no valid cross-reference table or stream.');
        }
    }

    private function resolvePrivatePath($disk, string $relativePath): string
    {
        $root = realpath($disk->path(''));
        $absolutePath = $disk->path($relativePath);
        $parent = realpath(dirname($absolutePath));

        if (
            $root === false
            || $parent === false
            || ! str_starts_with($parent, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)
        ) {
            throw new RuntimeException('The proposal artifact path resolves outside the private local disk root.');
        }

        return $absolutePath;
    }

    /** @param resource $stream */
    private function writeAll($stream, string $bytes): void
    {
        $length = strlen($bytes);
        $offset = 0;

        while ($offset < $length) {
            $written = fwrite($stream, substr($bytes, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('A partial or failed write occurred for the private proposal PDF.');
            }

            $offset += $written;
        }
    }
}

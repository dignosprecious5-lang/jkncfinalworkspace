<?php

declare(strict_types=1);

namespace App\Services\TransmittalV2;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/** Submission-wide limits. Temporary chunks are never submission files. */
final class TransmittalUploadBudget
{
    public const MAX_FILES = 50;
    public const MAX_TOTAL_BYTES = 104857600;
    public const MAX_FILE_BYTES = 52428800;

    /**
     * Accept nested request files and already verified, assembled resumable files.
     * Identity is the temporary path, not a filename or content hash: independent
     * uploads with identical names/content still consume separate budget entries.
     *
     * @param array<array-key, mixed> ...$groups
     */
    public function validate(array ...$groups): void
    {
        $seen = [];
        $count = 0;
        $totalBytes = 0;
        foreach ($this->files($groups) as $file) {
            $path = $file->getRealPath();
            if (! $file->isValid() || ! is_string($path) || $path === '') {
                $this->reject('An attached file could not be verified.');
            }
            $identity = PHP_OS_FAMILY === 'Windows' ? strtolower($path) : $path;
            if (isset($seen[$identity])) {
                continue;
            }
            $seen[$identity] = true;
            $bytes = $file->getSize();
            if (! is_int($bytes) || $bytes <= 0) {
                $this->reject('An attached file has an invalid size.');
            }
            if ($bytes > self::MAX_FILE_BYTES) {
                $this->reject('Each attached file must be at most 52,428,800 bytes (50 MiB).');
            }
            if (++$count > self::MAX_FILES) {
                $this->reject('A maximum of 50 files can be attached per submission.');
            }
            $totalBytes += $bytes;
            if ($totalBytes > self::MAX_TOTAL_BYTES) {
                $this->reject('Attached files must total at most 104,857,600 bytes (100 MiB) per submission.');
            }
        }
    }

    /** @return \Generator<int, UploadedFile> */
    private function files(array $values): \Generator
    {
        foreach ($values as $value) {
            if (is_array($value)) {
                yield from $this->files($value);
            } elseif ($value instanceof UploadedFile) {
                yield $value;
            } elseif ($value !== null) {
                $this->reject('An attachment is not a valid uploaded file.');
            }
        }
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['documents' => $message]);
    }
}

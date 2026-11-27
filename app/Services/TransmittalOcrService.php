<?php

namespace App\Services;

use App\Exceptions\OcrUnavailableException;
use Illuminate\Http\UploadedFile;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;
use Symfony\Component\Process\Process;
use Throwable;

final class TransmittalOcrService
{
    private const MAX_UPLOAD_BYTES = 10 * 1024 * 1024;
    private const MAX_PDF_PAGES = 5;
    private const MAX_TEXT_LENGTH = 25000;
    private const TOTAL_TIMEOUT_SECONDS = 90;
    private const PROCESS_TIMEOUT_SECONDS = 25;

    private const MIME_TYPES = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    /**
     * Analyze an already-validated upload without persisting it or touching application records.
     *
     * @return array<string, mixed>
     */
    public function analyze(UploadedFile $file): array
    {
        if (! $file->isValid() || $file->getSize() > self::MAX_UPLOAD_BYTES) {
            throw new OcrUnavailableException('The uploaded document is unavailable for analysis.');
        }

        $mimeType = $file->getMimeType();
        if (! is_string($mimeType) || ! isset(self::MIME_TYPES[$mimeType])) {
            throw new OcrUnavailableException('The uploaded document type is not supported.');
        }

        $inputPath = $file->getRealPath();
        if (! is_string($inputPath) || ! is_file($inputPath) || ! is_readable($inputPath)) {
            throw new OcrUnavailableException('The uploaded document is unavailable for analysis.');
        }

        $tesseract = $this->configuredExecutable('transmittal_ocr.binary');
        $poppler = $mimeType === 'application/pdf'
            ? $this->configuredExecutable('transmittal_ocr.pdf_binary')
            : null;
        $workspace = $this->createPrivateWorkspace();
        $deadline = hrtime(true) + (self::TOTAL_TIMEOUT_SECONDS * 1_000_000_000);

        try {
            $text = $mimeType === 'application/pdf'
                ? $this->extractPdfText($inputPath, $workspace, $tesseract, $poppler, $deadline)
                : $this->runTesseract($inputPath, $tesseract, $deadline);

            return $this->suggestions(mb_substr(trim($text), 0, self::MAX_TEXT_LENGTH));
        } catch (OcrUnavailableException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new OcrUnavailableException('Document analysis is unavailable.', previous: $exception);
        } finally {
            $this->removePrivateWorkspace($workspace);
        }
    }

    private function configuredExecutable(string $key): string
    {
        $path = config($key);
        if (! is_string($path) || trim($path) === '' || str_contains($path, "\0")) {
            throw new OcrUnavailableException('OCR tools are not configured.');
        }

        $path = trim($path);
        if (! $this->isAbsoluteLocalPath($path) || ! is_file($path) || ! is_executable($path)) {
            throw new OcrUnavailableException('OCR tools are not configured.');
        }

        return $path;
    }

    private function isAbsoluteLocalPath(string $path): bool
    {
        $windows = DIRECTORY_SEPARATOR === '\\';
        if ($windows) {
            if (str_starts_with($path, '\\\\') || str_starts_with($path, '//')) {
                return false;
            }

            $absolute = strlen($path) >= 3
                && ctype_alpha($path[0])
                && $path[1] === ':'
                && ($path[2] === '\\' || $path[2] === '/');
        } else {
            $absolute = str_starts_with($path, '/') && ! str_starts_with($path, '//');
        }

        if (! $absolute) {
            return false;
        }

        $components = explode('/', str_replace('\\', '/', $path));
        foreach ($components as $component) {
            if ($component === '..') {
                return false;
            }
        }

        return true;
    }

    private function createPrivateWorkspace(): string
    {
        $temporaryRoot = realpath(sys_get_temp_dir());
        if ($temporaryRoot === false || ! is_dir($temporaryRoot) || ! is_writable($temporaryRoot)) {
            throw new OcrUnavailableException('A private OCR workspace is unavailable.');
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $candidate = $temporaryRoot.DIRECTORY_SEPARATOR.'jknc-transmittal-ocr-'.bin2hex(random_bytes(16));
            if (! @mkdir($candidate, 0700)) {
                continue;
            }

            if (DIRECTORY_SEPARATOR !== '\\' && ! @chmod($candidate, 0700)) {
                @rmdir($candidate);
                throw new OcrUnavailableException('A private OCR workspace could not be secured.');
            }

            $canonical = realpath($candidate);
            if ($canonical === false || is_link($candidate) || ! $this->samePath(dirname($canonical), $temporaryRoot)) {
                if ($canonical !== false && $this->samePath(dirname($canonical), $temporaryRoot)) {
                    @rmdir($canonical);
                }

                throw new OcrUnavailableException('A private OCR workspace could not be secured.');
            }

            return $canonical;
        }

        throw new OcrUnavailableException('A private OCR workspace could not be created.');
    }

    private function samePath(string $left, string $right): bool
    {
        $left = str_replace('\\', '/', $left);
        $right = str_replace('\\', '/', $right);

        return DIRECTORY_SEPARATOR === '\\'
            ? strcasecmp($left, $right) === 0
            : $left === $right;
    }

    private function extractPdfText(string $inputPath, string $workspace, string $tesseract, ?string $poppler, int $deadline): string
    {
        if ($poppler === null) {
            throw new OcrUnavailableException('PDF OCR is not configured.');
        }

        $prefix = $workspace.DIRECTORY_SEPARATOR.'page';
        $this->runProcess([
            $poppler,
            '-f', '1',
            '-l', (string) self::MAX_PDF_PAGES,
            '-scale-to', '1800',
            '-png',
            $inputPath,
            $prefix,
        ], $deadline);

        $pages = glob($prefix.'-*.png') ?: [];
        sort($pages, SORT_NATURAL);
        $pages = array_slice($pages, 0, self::MAX_PDF_PAGES);
        if ($pages === []) {
            throw new OcrUnavailableException('The PDF did not produce readable pages.');
        }

        $text = [];
        foreach ($pages as $page) {
            if (! is_file($page) || is_link($page)) {
                throw new OcrUnavailableException('The PDF produced an unsafe temporary page.');
            }

            $text[] = $this->runTesseract($page, $tesseract, $deadline);
        }

        return implode("\n", $text);
    }

    private function runTesseract(string $path, string $tesseract, int $deadline): string
    {
        $output = '';
        try {
            $output = $this->runProcess([$tesseract, $path, 'stdout', '--psm', '6'], $deadline);
        } catch (Throwable) {
            $output = '';
        }

        if (trim($output) === '') {
            $output = $this->runProcess([$tesseract, $path, 'stdout'], $deadline);
        }

        return $output;
    }

    /** @param list<string> $arguments */
    private function runProcess(array $arguments, int $deadline): string
    {
        $remainingSeconds = ($deadline - hrtime(true)) / 1_000_000_000;
        if ($remainingSeconds <= 0) {
            throw new OcrUnavailableException('OCR exceeded its time limit.');
        }

        try {
            $process = new Process($arguments);
            $process->setTimeout(min((float) self::PROCESS_TIMEOUT_SECONDS, $remainingSeconds));
            $process->run();
        } catch (Throwable $exception) {
            throw new OcrUnavailableException('OCR tools could not analyze the document.', previous: $exception);
        }

        if (! $process->isSuccessful()) {
            throw new OcrUnavailableException('OCR tools could not analyze the document.');
        }

        return $process->getOutput();
    }

    /** @return array<string, mixed> */
    private function suggestions(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $suggestions = [];
        $date = $this->suggestedDate($text);
        $reference = $this->labeledValue($text, ['reference no', 'reference', 'ref no', 'ref', 'unique id', 'document id', 'doc no'], 120);
        $party = $this->labeledValue($text, ['to', 'recipient', 'recipient name', 'company', 'party name', 'external party', 'party'], 255);
        $recipientName = $this->labeledValue($text, ['attention', 'attn', 'contact', 'recipient name'], 255);
        $office = $this->labeledValue($text, ['from', 'sender', 'office name', 'office', 'department'], 255);
        $address = $this->labeledValue($text, ['delivery address', 'address'], 500);
        $subject = $this->labeledValue($text, ['subject', 'title', 'document title'], 255);
        $purpose = $this->labeledValue($text, ['purpose', 'purpose of transmission'], 500);
        $deliveryType = $this->labeledValue($text, ['delivery type', 'delivery mode', 'delivery method', 'channel'], 100);
        $email = $this->suggestedEmail($text);

        foreach ([
            'transmittal_date' => $date,
            'reference' => $reference,
            'unique_id' => $reference,
            'subject' => $subject,
            'party_name' => $party,
            'recipient_name' => $recipientName,
            'office_name' => $office,
            'address' => $address,
            'purpose' => $purpose,
            'delivery_type' => $deliveryType,
            'recipient_email' => $email,
        ] as $field => $value) {
            if ($value !== null) {
                $suggestions[$field] = $value;
            }
        }

        $tableItems = $this->extractTableItems($text, $reference);
        if ($tableItems !== []) {
            $suggestions['items'] = $tableItems;
        } else {
            $item = [];
            foreach ([
                'particular' => $this->labeledValue($text, ['particular', 'item name', 'document title'], 255),
                'unique_id' => $reference ?? $this->labeledValue($text, ['unique id', 'document id', 'doc no'], 120),
                'qty' => $this->labeledValue($text, ['quantity', 'qty'], 40),
                'description' => $this->labeledValue($text, ['description', 'details'], 500),
                'remarks' => $this->labeledValue($text, ['remarks', 'notes'], 255),
            ] as $field => $value) {
                if ($value !== null) {
                    $item[$field] = $value;
                }
            }

            if ($item !== []) {
                $suggestions['items'] = [$item];
            }
        }

        return $suggestions;
    }

    /** @return list<array{particular: string, unique_id: string, qty: string, description: string, remarks: string}> */
    private function extractTableItems(string $text, ?string $reference): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $items = [];
        $inTable = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }
            if (preg_match('/Document\s+Particulars/i', $trimmed) || preg_match('/Item\s+No/i', $trimmed)) {
                $inTable = true;
                continue;
            }
            if ($inTable) {
                if (preg_match('/^Remarks:/i', $trimmed) || preg_match('/^Total/i', $trimmed)) {
                    $inTable = false;
                    break;
                }
                if (preg_match('/^(?:(\d+)[ \t]+)?(.*?)[ \t]+(\d+)[ \t]*([a-zA-Z]+)?[ \t]*$/', $trimmed, $m)) {
                    $itemParticular = trim($m[2]);
                    if ($itemParticular !== '' && ! preg_match('/^(Item\s+No|Document\s+Particulars|Quantity|Unit)/i', $itemParticular)) {
                        $itemIndex = count($items) + 1;
                        $items[] = [
                            'particular' => $itemParticular,
                            'unique_id' => $reference ? "{$reference}-{$itemIndex}" : '',
                            'qty' => (string) $m[3],
                            'description' => $itemParticular,
                            'remarks' => ! empty($m[4]) ? $m[3].' '.trim($m[4]) : '',
                        ];
                    }
                }
            }
        }

        return $items;
    }

    private function suggestedDate(string $text): ?string
    {
        $iso = $this->suggestedIsoDate($text);
        if ($iso !== null) {
            return $iso;
        }

        $pattern = '/^[ \t]*(?:transmittal[ \t]+)?date[.:#-]*[ \t]*[:#-][ \t]*([A-Za-z]+[ \t]+\d{1,2},?[ \t]+\d{4})[ \t]*$/imu';
        if (preg_match($pattern, $text, $matches) === 1) {
            $timestamp = strtotime(trim($matches[1]));
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
        }

        return null;
    }

    private function labeledValue(string $text, array $labels, int $maximumLength): ?string
    {
        $patterns = [];
        foreach ($labels as $label) {
            $patterns[] = str_replace(' ', '[ \t]+', preg_quote($label, '/'));
        }

        $pattern = '/^[ \t]*(?:'.implode('|', $patterns).')[.:#-]*[ \t]*[:#-][ \t]*([^\r\n]{1,'.(int) $maximumLength.'})[ \t]*$/imu';
        if (preg_match($pattern, $text, $matches) !== 1) {
            return null;
        }

        $value = trim($matches[1]);

        return $value === '' ? null : mb_substr($value, 0, $maximumLength);
    }

    private function suggestedIsoDate(string $text): ?string
    {
        if (preg_match('/^[ \t]*(?:transmittal[ \t]+)?date[ \t]*[:#-][ \t]*(\d{4}-\d{2}-\d{2})[ \t]*$/imu', $text, $matches) !== 1) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $matches[1]);
        $errors = \DateTimeImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $date->format('Y-m-d') === $matches[1] ? $matches[1] : null;
    }

    private function suggestedEmail(string $text): ?string
    {
        if (preg_match('/\b([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})\b/i', $text, $matches) !== 1) {
            return null;
        }

        return mb_substr(trim($matches[1]), 0, 255);
    }

    private function removePrivateWorkspace(string $workspace): void
    {
        $temporaryRoot = realpath(sys_get_temp_dir());
        $canonical = realpath($workspace);
        if ($temporaryRoot === false || $canonical === false || is_link($workspace) || ! $this->samePath(dirname($canonical), $temporaryRoot)) {
            throw new OcrUnavailableException('The temporary OCR workspace could not be safely removed.');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($canonical, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $entry) {
            $entryPath = $entry->getPathname();
            if ($entry->isLink() || $entry->isFile()) {
                if (! @unlink($entryPath)) {
                    throw new OcrUnavailableException('Temporary OCR files could not be removed.');
                }
            } elseif ($entry->isDir() && ! @rmdir($entryPath)) {
                throw new OcrUnavailableException('Temporary OCR directories could not be removed.');
            }
        }

        if (! @rmdir($canonical)) {
            throw new OcrUnavailableException('The temporary OCR workspace could not be removed.');
        }
    }
}

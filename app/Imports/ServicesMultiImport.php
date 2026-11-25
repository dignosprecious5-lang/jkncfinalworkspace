<?php

namespace App\Imports;

use App\Models\Service;
use App\Models\ServiceVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

class ServicesMultiImport
{
    /**
     * Import services from JSON (Service Specification), CSV/TXT, or XLSX.
     *
     * @return array{created: int, updated: int, skipped: int, errors: array<int, string>}
     */
    public function importFile(UploadedFile $file, ?int $actorUserId = null): array
    {
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $path = $file->getRealPath();

        if (! $path || ! is_readable($path)) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['Uploaded file is not readable.']];
        }

        $rows = match ($ext) {
            'json' => $this->parseJson($path),
            'csv', 'txt' => $this->parseCsv($path),
            'xlsx' => $this->parseXlsx($path),
            default => ['error' => "Unsupported file format '.{$ext}'. Supported formats: JSON, CSV, TXT, XLSX."],
        };

        if (isset($rows['error'])) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => [$rows['error']]];
        }

        if ($rows === []) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['No importable service records found in the uploaded file.']];
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($rows, $actorUserId, &$created, &$updated, &$skipped, &$errors) {
            foreach ($rows as $idx => $raw) {
                $rowNum = $idx + 1;
                $name = trim((string) ($raw['name'] ?? $raw['service_name'] ?? ''));
                if ($name === '') {
                    $skipped++;
                    $errors[] = "Record #{$rowNum}: Service name is required.";
                    continue;
                }
                if (mb_strlen($name) > 255) {
                    $skipped++;
                    $errors[] = "Record #{$rowNum}: Service name exceeds 255 characters.";
                    continue;
                }

                $code = trim((string) ($raw['service_code'] ?? $raw['code'] ?? ''));
                $shortName = trim((string) ($raw['short_name'] ?? ''));
                $serviceArea = $raw['service_area'] ?? 'General';
                if (is_array($serviceArea)) {
                    $serviceArea = collect($serviceArea)->filter()->values()->all();
                } else {
                    $serviceArea = trim((string) $serviceArea) !== '' ? [trim((string) $serviceArea)] : ['General'];
                }

                $category = trim((string) ($raw['category'] ?? '')) ?: null;
                $subcategory = trim((string) ($raw['subcategory'] ?? '')) ?: null;
                $behaviorRaw = strtolower(trim((string) ($raw['engagement_behavior'] ?? 'project')));
                $behavior = in_array($behaviorRaw, ['project', 'regular', 'both'], true) ? $behaviorRaw : 'project';

                $priceRaw = $raw['standard_price'] ?? $raw['price_fee'] ?? $raw['price'] ?? null;
                if ($priceRaw !== null && $priceRaw !== '' && (! is_numeric($priceRaw) || (float) $priceRaw < 0)) {
                    $skipped++;
                    $errors[] = "Record #{$rowNum} ({$name}): Price must be a non-negative number.";
                    continue;
                }
                $price = ($priceRaw !== null && $priceRaw !== '') ? (float) $priceRaw : null;

                $existing = null;
                if ($code !== '') {
                    $existing = Service::where('service_code', $code)->first();
                }
                if (! $existing) {
                    $existing = Service::whereRaw('LOWER(COALESCE(name, service_name)) = ?', [Str::lower($name)])->first();
                }

                $attributes = [
                    'name' => $name,
                    'service_name' => $name,
                    'short_name' => $shortName !== '' ? $shortName : ($existing?->short_name),
                    'service_area' => $serviceArea,
                    'category' => $category ?? $existing?->category,
                    'subcategory' => $subcategory ?? $existing?->subcategory,
                    'engagement_behavior' => $behavior,
                ];

                if ($price !== null) {
                    $attributes['price_fee'] = $price;
                }

                if ($existing) {
                    $existing->update($attributes);
                    $service = $existing;
                    $updated++;
                } else {
                    $attributes['service_code'] = $code !== '' ? $code : 'SRV-' . strtoupper(Str::random(6));
                    $attributes['status'] = 'draft';
                    if ($actorUserId !== null) {
                        $attributes['created_by'] = $actorUserId;
                    }
                    $service = Service::create($attributes);
                    $created++;
                }

                // Ensure an active ServiceVersion exists and sync version fields if present
                $versionData = array_filter([
                    'short_name' => $shortName !== '' ? $shortName : null,
                    'standard_price' => $price,
                    'pricing_model' => isset($raw['pricing_model']) ? trim((string) $raw['pricing_model']) : null,
                    'internal_description' => isset($raw['internal_description']) ? trim((string) $raw['internal_description']) : (isset($raw['description']) ? trim((string) $raw['description']) : null),
                    'client_description' => isset($raw['client_description']) ? trim((string) $raw['client_description']) : null,
                    'scope_of_work' => isset($raw['scope_of_work']) ? trim((string) $raw['scope_of_work']) : null,
                    'deliverables' => isset($raw['deliverables']) ? trim((string) $raw['deliverables']) : null,
                ], fn ($v) => $v !== null && $v !== '');

                $version = $service->activeVersion;
                if (! $version) {
                    $version = ServiceVersion::create(array_merge([
                        'service_id' => $service->id,
                        'version_number' => 'V1.0',
                        'status' => 'draft',
                    ], $versionData));
                    $service->update(['active_version_id' => $version->id]);
                } elseif ($versionData !== []) {
                    $version->update($versionData);
                }
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    private function parseJson(string $path): array
    {
        $content = file_get_contents($path);
        $decoded = json_decode((string) $content, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return ['error' => 'Invalid JSON file: ' . json_last_error_msg()];
        }

        if (isset($decoded['services']) && is_array($decoded['services'])) {
            return array_values($decoded['services']);
        }

        if (isset($decoded['name']) || isset($decoded['service_name']) || isset($decoded['service_code'])) {
            return [$decoded];
        }

        if (array_is_list($decoded)) {
            return $decoded;
        }

        return ['error' => 'JSON structure must be a service object, an array of services, or contain a "services" array.'];
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return ['error' => 'Unable to open CSV file.'];
        }

        $headers = fgetcsv($handle, 4096, ',');
        if (! is_array($headers) || $headers === []) {
            fclose($handle);
            return ['error' => 'CSV header row is missing.'];
        }

        $headers = array_map(fn ($h) => Str::snake(strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)))), $headers);
        $rows = [];

        while (($data = fgetcsv($handle, 4096, ',')) !== false) {
            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $assoc = [];
            foreach ($headers as $i => $key) {
                if ($key !== '') {
                    $assoc[$key] = $data[$i] ?? null;
                }
            }
            $rows[] = $assoc;
        }

        fclose($handle);
        return $rows;
    }

    private const MAX_XLSX_ENTRIES = 200;
    private const MAX_XLSX_UNCOMPRESSED_BYTES = 10485760; // 10 MB
    private const MAX_IMPORT_ROWS = 5000;
    private const MAX_IMPORT_COLS = 100;

    private function parseXlsx(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            return ['error' => 'PHP ZipArchive extension is required to read XLSX files.'];
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CHECKCONS) !== true) {
            return ['error' => 'Invalid or corrupted XLSX archive.'];
        }

        if ($zip->numFiles <= 0 || $zip->numFiles > self::MAX_XLSX_ENTRIES) {
            $zip->close();
            return ['error' => 'XLSX archive contains an invalid or excessive number of entries.'];
        }

        $totalUncompressed = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (! is_array($stat)) {
                $zip->close();
                return ['error' => 'Unable to inspect XLSX archive entry metadata.'];
            }
            $entryName = (string) ($stat['name'] ?? '');
            if (str_contains($entryName, '..') || str_starts_with($entryName, '/')) {
                $zip->close();
                return ['error' => 'XLSX archive contains an unsafe entry path.'];
            }
            $totalUncompressed += max(0, (int) ($stat['size'] ?? 0));
            if ($totalUncompressed > self::MAX_XLSX_UNCOMPRESSED_BYTES) {
                $zip->close();
                return ['error' => 'XLSX archive exceeds the maximum allowed decompressed size (10 MB).'];
            }
        }

        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml !== false) {
            if (preg_match('/<!(?:DOCTYPE|ENTITY)/i', $ssXml)) {
                $zip->close();
                return ['error' => 'Unsafe XML directive detected in XLSX shared strings.'];
            }
            $xml = $this->safeLoadXml($ssXml);
            if ($xml === false) {
                $zip->close();
                return ['error' => 'Malformed XLSX sharedStrings.xml content.'];
            }
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string) $si->t;
                } else {
                    $text = '';
                    foreach ($si->r as $r) {
                        $text .= (string) $r->t;
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return ['error' => 'XLSX workbook does not contain xl/worksheets/sheet1.xml.'];
        }

        if (preg_match('/<!(?:DOCTYPE|ENTITY)/i', $sheetXml)) {
            return ['error' => 'Unsafe XML directive detected in XLSX worksheet.'];
        }

        $sheet = $this->safeLoadXml($sheetXml);
        if ($sheet === false || ! isset($sheet->sheetData->row)) {
            return ['error' => 'Unable to parse XLSX worksheet rows.'];
        }

        $matrix = [];
        $rowCount = 0;
        foreach ($sheet->sheetData->row as $row) {
            $rowCount++;
            if ($rowCount > self::MAX_IMPORT_ROWS + 1) {
                return ['error' => 'XLSX worksheet exceeds the maximum allowed row count (' . self::MAX_IMPORT_ROWS . ').'];
            }
            $cells = [];
            $fallbackCol = 0;
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $colLetters = preg_replace('/[^A-Za-z]/', '', $ref);
                $colIdx = $colLetters !== '' ? $this->columnLettersToIndex($colLetters) : $fallbackCol;
                $fallbackCol = $colIdx + 1;
                if ($colIdx >= self::MAX_IMPORT_COLS) {
                    return ['error' => 'XLSX worksheet exceeds the maximum allowed column count (' . self::MAX_IMPORT_COLS . ').'];
                }
                $type = (string) $c['t'];
                if ($type === 's') {
                    $val = $sharedStrings[(int) $c->v] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = (string) ($c->is->t ?? '');
                } elseif ($type === 'b') {
                    $val = ((string) ($c->v ?? '')) === '1' ? 'true' : 'false';
                } elseif ($type === 'e') {
                    $val = '';
                } else {
                    $val = (string) ($c->v ?? '');
                }
                $cells[$colIdx] = $val;
            }
            if ($cells !== []) {
                ksort($cells);
                $max = max(array_keys($cells));
                $normalized = [];
                for ($i = 0; $i <= $max; $i++) {
                    $normalized[] = $cells[$i] ?? '';
                }
                $matrix[] = $normalized;
            }
        }

        if (count($matrix) < 2) {
            return [];
        }

        $headers = array_map(fn ($h) => Str::snake(strtolower(trim((string) $h))), array_shift($matrix));
        $rows = [];
        foreach ($matrix as $data) {
            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $assoc = [];
            foreach ($headers as $i => $key) {
                if ($key !== '') {
                    $assoc[$key] = $data[$i] ?? null;
                }
            }
            $rows[] = $assoc;
        }

        return $rows;
    }

    private function safeLoadXml(string $xmlContent): \SimpleXMLElement|false
    {
        $prev = libxml_use_internal_errors(true);
        try {
            $parsed = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            return $parsed instanceof \SimpleXMLElement ? $parsed : false;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
        }
    }

    private function columnLettersToIndex(string $letters): int
    {
        $index = 0;
        $letters = strtoupper($letters);
        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }

        return max(0, $index - 1);
    }
}

<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;

/** Read adapters only. No catalog models, relationships or business rows are mutated. */
class ServiceCatalogConfiguration
{
    public static function value(Model $model, string $key): mixed
    {
        // Never invoke an optional relationship or an enhanced model accessor.
        $attributes = $model->getAttributes();
        if (! array_key_exists($key, $attributes)) {
            return null;
        }
        // Decode JSON once in list(); an Eloquent array cast would hide malformed JSON as null.
        if (in_array($model->getCasts()[$key] ?? null, ['array', 'json', 'object'], true)) {
            return $attributes[$key];
        }
        return $model->getAttribute($key);
    }

    public static function number(mixed $value): ?float
    {
        return is_numeric($value) && is_finite((float) $value) ? (float) $value : null;
    }

    public static function list(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        if (! is_string($value)) {
            throw ValidationException::withMessages(['catalog' => 'Unsupported catalog list representation.']);
        }
        $text = trim($value);
        if ($text === '') {
            return [];
        }
        if (in_array($text[0], ['[', '{', '"'], true)) {
            try {
                $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw ValidationException::withMessages(['catalog' => 'Malformed catalog JSON; stored content requires review.']);
            }
            if (! is_array($decoded)) {
                throw ValidationException::withMessages(['catalog' => 'Catalog JSON must contain an array, not encoded text.']);
            }
            return $decoded;
        }
        return preg_split('/[\r\n,]+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /** All SQL predicates and ordering columns are checked before query construction. */
    public static function rows(string $table, array $columns, array $where, array &$diagnostics): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }
        $required = array_unique(array_merge(['id'], $columns, array_keys($where)));
        if (! Schema::hasColumns($table, $required)) {
            $diagnostics[] = "Incomplete {$table} schema; required columns: ".implode(', ', $required).'.';
            return [];
        }
        return DB::table($table)->where($where)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    }

    public static function clientGroup(?string $value): ?string
    {
        return match (mb_strtolower(trim((string) $value))) {
            'individual', 'sole', 'sole proprietor', 'natural person', 'sole / natural person / individual' => 'individual',
            'business', 'juridical', 'corporation', 'opc', 'partnership', 'cooperative',
            'juridical entity (corporation / opc / partnership / cooperative)' => 'juridical',
            '', 'all', 'both', 'all client types' => 'all',
            default => null,
        };
    }

    public static function requirements(mixed $raw, ?string $category, ?string $client, string $origin, int $id, array &$diagnostics): array
    {
        try {
            // Legacy free text is line-based; a comma can be part of an obligation.
            $text = is_string($raw) ? ltrim($raw) : '';
            $payload = $text !== '' && ! in_array($text[0], ['[', '{', '"'], true)
                ? (preg_split('/\r\n|\r|\n/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: []) : self::list($raw);
        } catch (ValidationException $error) {
            $diagnostics[] = "{$origin} {$id}: invalid legacy requirements; preserve original and review.";
            return [['origin' => $origin, 'name' => 'Unresolved legacy requirements', 'raw' => $raw, 'applicable' => null]];
        }
        if ($payload === []) {
            return [];
        }
        $groups = $payload['groups'] ?? [($payload['category'] ?? $category ?? '__unclassified__') => ($payload['items'] ?? $payload)];
        if (! is_array($groups)) {
            $diagnostics[] = "{$origin} {$id}: malformed requirement groups.";
            return [['origin' => $origin, 'raw' => $payload, 'name' => 'Unresolved requirement groups', 'applicable' => null]];
        }
        $actual = self::clientGroup($client);
        $result = [];
        foreach ($groups as $group => $lines) {
            $configured = self::clientGroup((string) $group);
            if ($configured === null || $actual === null || $actual === 'all') {
                $diagnostics[] = "{$origin} {$id}: requirement applicability requires review ({$group}).";
            } elseif ($configured !== 'all' && $configured !== $actual) {
                continue;
            }
            foreach (is_array($lines) ? $lines : [$lines] as $line) {
                $name = is_array($line) ? ($line['name'] ?? $line['title'] ?? null) : $line;
                if (! is_scalar($name) || trim((string) $name) === '') {
                    $diagnostics[] = "{$origin} {$id}: unsupported requirement content retained for review.";
                    $result[] = ['origin' => $origin, 'name' => 'Unresolved requirement', 'raw' => $line, 'applicable' => null];
                    continue;
                }
                $result[] = [
                    'origin' => $origin, $origin.'_id' => $id, 'name' => (string) $name,
                    'client_type' => (string) $group, 'source' => 'legacy_master',
                    'applicable' => $configured !== null && $actual !== null && $actual !== 'all' ? true : null,
                    'is_mandatory' => null, 'raw' => $line,
                ];
            }
        }
        return $result;
    }

    public static function mergeRequirements(array $legacy, array $rows, string $origin, ?string $client, array &$diagnostics): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            if (in_array(mb_strtolower(trim((string) $row['status'])), ['inactive', 'disabled', 'archived'], true)) {
                continue;
            }
            $group = self::clientGroup($row['client_type']);
            $actual = self::clientGroup($client);
            if ($group === null || $actual === null || $actual === 'all') {
                $diagnostics[] = "{$origin}: normalized requirement applicability is unresolved.";
            } elseif ($group !== 'all' && $group !== $actual) {
                continue;
            }
            $normalized[] = array_merge($row, ['origin' => $origin, 'source' => 'normalized',
                'name' => $row['requirement_name'] ?? $row['name'] ?? '', 'applicable' => $group !== null && $actual !== null && $actual !== 'all' ? true : null]);
        }
        // Keep both sources when their semantics differ; never silently replace obligations.
        if ($legacy !== [] && $normalized !== []) {
            $diagnostics[] = "{$origin}: both legacy and normalized requirements are populated; reconcile obligations before finalization.";
        }
        return array_merge($legacy, $normalized);
    }

    public function forService(Service $service, ?string $clientType): array
    {
        $diagnostics = [];
        $id = (int) $service->getKey();
        $get = fn ($key) => self::value($service, $key);
        $version = null;
        $versionId = $get('active_version_id');
        if ($versionId !== null) {
            if (! Schema::hasTable('service_versions')) {
                $diagnostics[] = "Service {$id}: referenced version table is absent.";
            } else {
                $versions = self::rows('service_versions', ['service_id'], ['id' => $versionId, 'service_id' => $id], $diagnostics);
                $version = $versions[0] ?? null;
                if ($version === null) {
                    $diagnostics[] = "Service {$id}: active version is unavailable or belongs to another Service.";
                }
            }
        } elseif (Schema::hasTable('service_versions')) {
            $candidates = self::rows('service_versions', ['service_id'], ['service_id' => $id], $diagnostics);
            if ($candidates !== []) {
                $diagnostics[] = "Service {$id}: versions exist without an authoritative active-version reference.";
            }
        }
        $legacy = self::requirements($get('requirements'), $get('requirement_category'), $clientType, 'service', $id, $diagnostics);
        $requirements = $activities = $terms = $unresolvedTerms = [];
        if ($version !== null) {
            $requirements = self::rows('service_requirements', ['service_version_id', 'requirement_name', 'client_type', 'status'], ['service_version_id' => $versionId], $diagnostics);
            $activities = self::rows('service_activities', ['service_version_id', 'name', 'parent_id', 'sequence'], ['service_version_id' => $versionId], $diagnostics);
            // Keep parent_id and every configured child step; readers must not erase sub-activities.
            $terms = self::rows('service_terms', ['service_version_id', 'title', 'content', 'status', 'scope', 'sort_order'], ['service_version_id' => $versionId], $diagnostics);
            $terms = array_values(array_filter($terms, fn ($row) => mb_strtolower(trim((string) $row['status'])) === 'active'));
            foreach ($terms as $term) {
                if (ProductCatalogConfiguration::scope($term['scope']) === '') {
                    $diagnostics[] = "Service term {$term['id']}: unknown scope retained for review.";
                }
            }
            usort($terms, fn ($a, $b) => [$a['sort_order'], $a['id']] <=> [$b['sort_order'], $b['id']]);
            usort($activities, fn ($a, $b) => [$a['sequence'], $a['id']] <=> [$b['sequence'], $b['id']]);
        } else {
            foreach (['service_requirements', 'service_activities', 'service_terms'] as $table) {
                if (Schema::hasTable($table)) {
                    $diagnostics[] = "Service {$id}: {$table} cannot be safely resolved without an owned active version.";
                }
            }
        }
        if (Schema::hasTable('service_term_library')) {
            $diagnostics[] = "Service {$id}: term-library priority/applicability requires a separately reviewed resolver.";
            $unresolvedTerms = self::rows('service_term_library', ['title', 'content'], [], $diagnostics);
        }
        $requirements = self::mergeRequirements($legacy, $requirements, 'service', $clientType, $diagnostics);
        $price = self::number($version['standard_price'] ?? $get('price_fee'));
        if ($price === null) {
            $diagnostics[] = "Service {$id}: price is unavailable.";
        }
        return [
            'type' => 'SERVICE', 'service_id' => $id, 'service_version_id' => $version['id'] ?? null,
            'name' => $get('service_name') ?? $get('name'), 'sku' => $get('service_code') ?? $get('service_id'),
            'scope_of_work' => $version['scope_of_work'] ?? $get('service_description'),
            'deliverables' => $version['deliverables'] ?? $get('service_activity_output'),
            'client_responsibilities' => $version['client_responsibilities'] ?? null, 'exclusions' => $version['exclusions'] ?? null,
            'requirements' => $requirements, 'activities' => $activities, 'activity_output' => $get('service_activity_output'),
            'terms' => $terms, 'unresolved_terms' => $unresolvedTerms, 'engagement_structure' => $get('engagement_structure'),
            'commercial' => ['price' => $price, 'currency' => $version['currency'] ?? $get('currency'),
                'pricing_model' => $version['pricing_model'] ?? null, 'tax_treatment' => $version['tax_treatment'] ?? $get('tax_type'),
                'payment_structure' => $version['payment_structure'] ?? null],
            'diagnostics' => array_values(array_unique($diagnostics)), 'compatible' => $diagnostics === [],
        ];
    }
}

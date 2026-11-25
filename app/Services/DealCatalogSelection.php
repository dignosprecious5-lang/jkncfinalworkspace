<?php

namespace App\Services;

use App\Models\{Deal, Product, Service, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/** Selection and preview only; callers remain responsible for authorized persistence. */
class DealCatalogSelection
{
    public function authorize(?User $user): void
    {
        abort_unless($user && ! $user->isClient() && ! $user->isDisabled()
            && ($user->isAdmin() || $user->isSuperAdmin()
                || ($user->hasPermission('access_deals') && $user->hasPermission('create_sales_marketing'))), 403);
    }

    public function areas(mixed $value, ?string $other = null): array
    {
        $values = [];
        foreach (ServiceCatalogConfiguration::list($value) as $part) {
            if (! is_scalar($part)) {
                throw ValidationException::withMessages(['catalog' => 'Unsupported catalog area content.']);
            }
            if (trim((string) $part) !== '') {
                $values[] = trim((string) $part);
            }
        }
        if ($other !== null && trim($other) !== '') {
            $values = array_values(array_diff($values, ['Others']));
            $values[] = trim($other);
        }
        return array_values(array_unique($values));
    }

    public function existing(?Deal $deal, string $type): array
    {
        if ($deal === null) {
            return [];
        }
        $rows = app(DealProposalAggregationService::class)->sourceRows($deal);
        $result = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! in_array(strtoupper((string) ($row['type'] ?? '')), ['SERVICE', 'PRODUCT'], true)) {
                throw ValidationException::withMessages(['catalog' => 'Stored selection type is ambiguous; review without rewriting the Deal.']);
            }
            if (strtoupper($row['type']) === $type) {
                $result[] = $row;
            }
        }
        return $result;
    }

    public function serviceIdentifications(Service $service, ?\Illuminate\Support\Collection $areas = null): array
    {
        $diagnostics = [];
        $areas ??= collect(ServiceCatalogConfiguration::rows('service_areas', ['name'], [], $diagnostics))->map(fn ($row) => (object) $row);
        $parentId = ServiceCatalogConfiguration::value($service, 'service_area_id');
        $parent = $parentId !== null ? $areas->firstWhere('id', $parentId) : null;
        if ($parent !== null) {
            return [['id' => (int) $parent->id, 'name' => $parent->name]];
        }
        return array_map(function ($name) use ($areas) {
            $match = $areas->first(fn ($area) => mb_strtolower(trim($area->name)) === mb_strtolower($name));
            return ['id' => $match?->id, 'name' => $name];
        }, $this->areas(ServiceCatalogConfiguration::value($service, 'service_area'), ServiceCatalogConfiguration::value($service, 'service_area_other')));
    }

    public function choices(?Deal $deal = null): array
    {
        $diagnostics = [];
        $areaRows = ServiceCatalogConfiguration::rows('service_areas', ['name'], [], $diagnostics);
        $areas = collect($areaRows)->map(fn ($row) => (object) $row);
        $result = ['services' => [], 'products' => [], 'legacy_services' => [], 'legacy_products' => [],
            'service_identifications' => array_map(fn ($row) => ['id' => $row['id'], 'name' => $row['name']], $areaRows)];
        foreach (['SERVICE' => 'services', 'PRODUCT' => 'products'] as $type => $table) {
            $idKey = $type === 'PRODUCT' ? 'product_id' : 'service_id';
            $nameKey = $type === 'PRODUCT' ? 'product_name' : 'service_name';
            $existing = $this->existing($deal, $type);
            $retained = array_map('strval', array_column(array_filter($existing, fn ($row) => empty($row['custom'])), $idKey));
            if (! Schema::hasTable($table)) {
                $diagnostics[] = "{$table}: master table is unavailable.";
            }
            $records = ServiceCatalogConfiguration::rows($table, [$nameKey], [], $diagnostics);
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'status')) {
                $diagnostics[] = "{$table}: status column is absent; no new selections are authorized.";
            }
            foreach ($records as $row) {
                $isRetained = in_array((string) $row['id'], $retained, true);
                $active = isset($row['status']) && mb_strtolower(trim((string) $row['status'])) === 'active';
                if (! $active && ! $isRetained) {
                    continue;
                }
                $model = ($type === 'PRODUCT' ? new Product : new Service)->newFromBuilder($row);
                $configuration = $type === 'PRODUCT' ? app(ProductCatalogConfiguration::class)->forProduct($model, $deal ? ServiceCatalogConfiguration::value($deal, 'customer_type') : null)
                    : app(ServiceCatalogConfiguration::class)->forService($model, $deal ? ServiceCatalogConfiguration::value($deal, 'customer_type') : null);
                $result[$table][] = [
                    'id' => $row['id'], 'type' => $type, 'name' => $row[$nameKey], 'price' => $configuration['commercial']['price'],
                    'desc' => $configuration['scope_of_work'], 'currency' => $configuration['commercial']['currency'], 'unit' => $row['unit'] ?? null,
                    'service_version_id' => $configuration['service_version_id'] ?? null,
                    'service_identifications' => $type === 'SERVICE' ? $this->serviceIdentifications($model, $areas) : [],
                    'linked_service_ids' => $type === 'PRODUCT' ? array_values(array_unique(array_merge(
                        ServiceCatalogConfiguration::list($row['linked_service_ids'] ?? null), isset($row['linked_service_id']) ? [$row['linked_service_id']] : []))) : [],
                    'service_area' => $this->areas($type === 'PRODUCT' ? ($row['product_area'] ?? $row['service_area'] ?? null) : ($row['service_area'] ?? null), $row[$type === 'PRODUCT' ? 'product_area_other' : 'service_area_other'] ?? null),
                    'selectable' => $active, 'retained' => $isRetained, 'diagnostics' => $configuration['diagnostics'],
                ];
            }
            usort($result[$table], fn ($a, $b) => strcasecmp((string) $a['name'], (string) $b['name']));
            $available = array_map('strval', array_column($result[$table], 'id'));
            $result['legacy_'.$table] = array_values(array_filter($existing, fn ($row) => ! empty($row['custom'])
                || ! isset($row[$idKey]) || ! in_array((string) $row[$idKey], $available, true)));
        }
        $result['diagnostics'] = array_values(array_unique($diagnostics));
        return $result;
    }

    public function hasInput(Request $request): bool
    {
        return $request->hasAny(['catalog_selection_present', 'service_ids', 'product_ids', 'services', 'products', 'other_service', 'other_product']);
    }

    public function fromRequest(Request $request, ?Deal $deal = null): array
    {
        $data = $request->validate([
            'catalog_selection_present' => ['sometimes', 'boolean'],
            'service_ids' => ['sometimes', 'nullable', 'array', 'max:200'], 'service_ids.*' => ['required', 'integer', 'min:1'],
            'product_ids' => ['sometimes', 'nullable', 'array', 'max:200'], 'product_ids.*' => ['required', 'integer', 'min:1'],
            'services' => ['sometimes', 'nullable', 'array', 'max:200'], 'services.*' => ['string', 'max:255'],
            'products' => ['sometimes', 'nullable', 'array', 'max:200'], 'products.*' => ['string', 'max:255'],
            'other_service' => ['nullable', 'string', 'max:255'], 'other_product' => ['nullable', 'string', 'max:255'],
        ]);
        $choices = $this->choices($deal);
        $result = [];
        foreach (['SERVICE' => 'services', 'PRODUCT' => 'products'] as $type => $key) {
            $idKey = $type === 'PRODUCT' ? 'product_id' : 'service_id';
            $ids = $type === 'PRODUCT' ? 'product_ids' : 'service_ids';
            $other = $type === 'PRODUCT' ? 'other_product' : 'other_service';
            $old = $this->existing($deal, $type);
            if (! $request->boolean('catalog_selection_present') && ! $request->hasAny([$key, $ids, $other])) {
                $result[$key] = array_map(fn ($row) => ['id' => $row[$idKey] ?? null, 'name' => $row['name'] ?? '',
                    'custom' => $row['custom'] ?? ! isset($row[$idKey]), 'legacy' => true, 'retained_source' => $row], $old);
                continue;
            }
            $selections = [];
            foreach (array_unique($data[$ids] ?? []) as $id) {
                $entry = collect($choices[$key])->first(fn ($row) => (string) $row['id'] === (string) $id);
                if ($entry === null || (! $entry['selectable'] && ! $entry['retained'])) {
                    throw ValidationException::withMessages([$ids => 'Select an active catalog ID; only existing inactive selections may be retained.']);
                }
                $retained = array_values(array_filter($old, fn ($row) => empty($row['custom'])
                    && isset($row[$idKey]) && (string) $row[$idKey] === (string) $id));
                if ($retained === []) {
                    $selections[] = ['id' => $entry['id'], 'name' => $entry['name']];
                } else {
                    foreach ($retained as $row) {
                        $selections[] = ['id' => $entry['id'], 'name' => $row['name'] ?? $entry['name'],
                            'custom' => false, 'legacy' => true, 'retained_source' => $row];
                    }
                }
            }
            foreach ($data[$key] ?? [] as $name) {
                if (array_filter($selections, fn ($entry) => $entry['name'] === $name)) {
                    continue;
                }
                $retained = array_values(array_filter($old, fn ($row) => ($row['name'] ?? null) === $name));
                if ($retained !== []) {
                    foreach ($retained as $row) {
                        $selections[] = ['id' => $row[$idKey] ?? null, 'name' => $name, 'custom' => $row['custom'] ?? ! isset($row[$idKey]),
                            'legacy' => true, 'retained_source' => $row];
                    }
                    continue;
                }
                $matches = array_values(array_filter($choices[$key], fn ($row) => $row['selectable'] && mb_strtolower(trim($row['name'])) === mb_strtolower(trim($name))));
                if (count($matches) !== 1) {
                    throw ValidationException::withMessages([$key => 'Catalog name is unavailable or ambiguous; select by ID or use Other.']);
                }
                $selections[] = ['id' => $matches[0]['id'], 'name' => $matches[0]['name']];
            }
            if (isset($data[$other]) && trim($data[$other]) !== '') {
                $selections[] = ['id' => null, 'name' => trim($data[$other]), 'custom' => true];
            }
            $result[$key] = $selections;
        }
        return $result;
    }

    public function preserveExistingInstances(array $rebuilt, array $existing): array
    {
        $result = [];
        $used = [];
        foreach ($rebuilt as $item) {
            $type = strtoupper((string) ($item['type'] ?? ''));
            $key = $type === 'PRODUCT' ? 'product_id' : 'service_id';
            if (isset($item['id'])) {
                $matches = array_values(array_filter($existing, fn ($row) => is_array($row)
                    && (string) ($row['id'] ?? '') === (string) $item['id']
                    && strtoupper((string) ($row['type'] ?? '')) === $type
                    && (string) ($row[$key] ?? '') === (string) ($item[$key] ?? '')));
                $result[] = count($matches) === 1 ? $matches[0] : $item;
                continue;
            }
            $identity = $type.'|'.(! empty($item['custom']) ? 'custom:'.($item['name'] ?? '') : (string) ($item[$key] ?? 'legacy:'.($item['name'] ?? '')));
            if (isset($used[$identity])) {
                continue; // Rebuilt checkbox duplicates must not multiply retained pricing instances.
            }
            $used[$identity] = true;
            $matches = array_values(array_filter($existing, fn ($row) => is_array($row)
                && strtoupper((string) ($row['type'] ?? '')) === $type
                && (! empty($item['custom']) ? ! empty($row['custom']) && ($row['name'] ?? '') === ($item['name'] ?? '')
                    : empty($row['custom']) && isset($item[$key], $row[$key]) && (string) $row[$key] === (string) $item[$key])));
            array_push($result, ...($matches !== [] ? $matches : [$item]));
        }
        return $result;
    }

    public function configuration(array $selections, string $clientType): array
    {
        $rows = [];
        foreach (['SERVICE' => 'services', 'PRODUCT' => 'products'] as $type => $key) {
            foreach ($selections[$key] ?? [] as $selection) {
                $row = $selection['retained_source'] ?? [];
                $row = array_merge($row, ['type' => $type, 'name' => $selection['name'],
                    'custom' => $selection['custom'] ?? false, $type === 'PRODUCT' ? 'product_id' : 'service_id' => $selection['id'] ?? null]);
                $rows[] = $row;
            }
        }
        $catalog = app(ProductCatalogConfiguration::class)->forSelections($rows, $clientType);
        return ['items' => array_values(array_merge($catalog['services'], $catalog['products'])),
            'conflicts' => $catalog['conflicts'], 'diagnostics' => $catalog['diagnostics'], 'compatible' => $catalog['compatible']];
    }
}

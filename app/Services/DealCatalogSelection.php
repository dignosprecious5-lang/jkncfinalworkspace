<?php

namespace App\Services;

use App\Models\{Deal, Product, Service, ServiceArea, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/** One selection source for the existing New/Edit Deal form and its preview. */
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
        $values = $this->list($value);
        $values = array_values(array_filter(array_map(fn ($area) => is_scalar($area) ? trim((string) $area) : '', $values)));
        if (filled($other)) {
            $values = array_values(array_diff($values, ['Others']));
            $values[] = trim($other);
        }
        return array_values(array_unique($values));
    }

    private function list(mixed $value): array
    {
        if (is_array($value)) { return $value; }
        if (! is_string($value) || trim($value) === '') { return []; }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : preg_split('/[\r\n,]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
    }

    public function existing(?Deal $deal, string $type): array
    {
        if (! $deal) { return []; }
        $rows = $this->list($deal->services_products);
        if ($rows === []) {
            $rows = array_map(fn ($name) => is_array($name) ? array_merge($name, ['type' => $type]) : ['type' => $type, 'name' => $name],
                $this->list($type === 'PRODUCT' ? $deal->products : $deal->services));
        }
        $products = $this->list($deal->products);
        return collect($rows)->map(function ($row) use ($products) {
            if (! is_array($row)) { return ['type' => in_array($row, $products, true) ? 'PRODUCT' : 'SERVICE', 'name' => $row]; }
            return $row;
        })->filter(fn ($row) => strtoupper((string) ($row['type'] ?? 'SERVICE')) === $type && filled($row['name'] ?? null))
            ->values()->all();
    }

    /** The FK is authoritative; older records keep their stored area memberships. */
    public function serviceIdentifications(Service $service, ?\Illuminate\Support\Collection $areas = null): array
    {
        $areas ??= Schema::hasTable('service_areas') ? ServiceArea::all() : collect();
        $parent = $areas->firstWhere('id', $service->service_area_id);
        if ($parent) { return [['id' => (int) $parent->id, 'name' => $parent->name]]; }
        return array_map(function ($name) use ($areas) {
            $area = $areas->first(fn ($area) => mb_strtolower(trim($area->name)) === mb_strtolower($name));
            return ['id' => $area ? (int) $area->id : null, 'name' => $area?->name ?? $name];
        }, $this->areas($service->service_area ?: $service->getRawOriginal('service_area'), $service->service_area_other));
    }

    public function choices(?Deal $deal = null): array
    {
        $areas = Schema::hasTable('service_areas') ? ServiceArea::orderBy('name')->get() : collect();
        $result = ['services' => [], 'products' => [], 'legacy_services' => [], 'legacy_products' => [],
            'service_identifications' => $areas->map(fn ($area) => ['id' => (int) $area->id, 'name' => $area->name])->all()];
        foreach (['SERVICE' => Service::class, 'PRODUCT' => Product::class] as $type => $model) {
            $key = $type === 'PRODUCT' ? 'products' : 'services';
            $idKey = $type === 'PRODUCT' ? 'product_id' : 'service_id';
            $table = $type === 'PRODUCT' ? 'products' : 'services';
            $existing = $this->existing($deal, $type);
            $retainedIds = collect($existing)->filter(fn ($row) => empty($row['custom']))->pluck($idKey)->filter()->unique()->all();
            if ($type === 'SERVICE') {
                $retainedIds = array_values(array_unique(array_merge($retainedIds, collect($this->existing($deal, 'PRODUCT'))
                    ->filter(fn ($row) => empty($row['custom']))->pluck('associated_service_id')->filter()->all())));
            }
            $models = Schema::hasTable($table) ? $model::query()
                ->when($type === 'SERVICE', fn ($query) => $query->with('activeVersion'))
                ->when(Schema::hasColumn($table, 'status'), fn ($query) => $query->where(function ($query) use ($retainedIds) {
                    $query->whereRaw('LOWER(TRIM(status)) = ?', ['active']);
                    if ($retainedIds !== []) { $query->orWhereIn('id', $retainedIds); }
                }))->get() : collect();
            foreach ($models as $record) {
                $name = trim((string) ($record->name ?: ($type === 'PRODUCT' ? $record->product_name : $record->service_name)));
                if ($name === '') { continue; }
                $price = $type === 'SERVICE' ? ($record->activeVersion?->standard_price ?? $record->price_fee) : $record->price;
                $result[$key][] = [
                    'id' => $record->id, 'type' => $type, 'name' => $name,
                    'price' => is_numeric($price) ? (float) $price : null,
                    'desc' => $type === 'SERVICE' ? ($record->activeVersion?->scope_of_work ?: $record->service_description ?: '') : ($record->scope_of_work ?: $record->description ?: $record->product_description ?: ''),
                    'unit' => $type === 'PRODUCT' ? 'document' : 'lot',
                    'service_version_id' => $type === 'SERVICE' ? $record->active_version_id : null,
                    'service_identifications' => $type === 'SERVICE' ? $this->serviceIdentifications($record, $areas) : [],
                    'linked_service_ids' => $type === 'PRODUCT' ? array_values(array_unique(array_filter(array_map('intval', array_merge(
                        $this->list($record->getRawOriginal('linked_service_ids')), [$record->getRawOriginal('linked_service_id')]
                    )), fn ($id) => $id > 0))) : [],
                    'service_area' => $this->areas($record->service_area ?: $record->getRawOriginal('service_area'), $record->service_area_other),
                    'currency' => $type === 'SERVICE' ? $record->activeVersion?->currency : $record->currency,
                    'selectable' => strtolower(trim((string) $record->status)) === 'active',
                    'retained' => in_array($record->id, $retainedIds),
                ];
            }
            usort($result[$key], fn ($a, $b) => strcasecmp($a['name'], $b['name']));
            $availableIds = array_column($result[$key], 'id');
            $result['legacy_'.$key] = array_values(array_filter($existing, fn ($row) => ! empty($row['custom']) || empty($row[$idKey])
                || ! in_array($row[$idKey], $availableIds)));
        }
        $result['service_identifications'] = collect($result['service_identifications'])
            ->concat(collect($result['services'])->flatMap(fn ($service) => $service['service_identifications']))
            ->unique(fn ($area) => $area['id'] ? 'id:'.$area['id'] : 'name:'.mb_strtolower($area['name']))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
        return $result;
    }

    public function hasInput(Request $request): bool
    {
        return $request->hasAny(['catalog_selection_present', 'service_ids', 'product_ids', 'services', 'products', 'other_service', 'other_product']);
    }

    public function fromRequest(Request $request, ?Deal $deal = null): array
    {
        $validated = $request->validate([
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
            $idsField = $type === 'PRODUCT' ? 'product_ids' : 'service_ids';
            $otherField = $type === 'PRODUCT' ? 'other_product' : 'other_service';
            $existing = $this->existing($deal, $type);
            $entries = collect($choices[$key])->keyBy('id');
            $selections = [];
            if (! $request->boolean('catalog_selection_present') && ! $request->hasAny([$idsField, $key, $otherField])) {
                foreach ($existing as $row) {
                    $selections[] = ['id' => isset($row[$idKey]) && $entries->has($row[$idKey]) ? $row[$idKey] : null,
                        'name' => $row['name'], 'custom' => ! empty($row['custom']) || empty($row[$idKey]) || ! $entries->has($row[$idKey]), 'legacy' => true];
                }
            } else {
                foreach (array_unique($validated[$idsField] ?? []) as $index => $id) {
                    $entry = $entries->get((int) $id);
                    if (! $entry) {
                        throw ValidationException::withMessages([$idsField.'.'.$index => 'Select an active catalog item. Only items already on this Deal may be retained while inactive.']);
                    }
                    $selections[] = ['id' => $entry['id'], 'name' => $entry['name']];
                }
                foreach ($validated[$key] ?? [] as $name) {
                    if ($name === 'Others') { continue; }
                    $old = collect($existing)->first(fn ($row) => $row['name'] === $name);
                    if ($old) {
                        $id = $old[$idKey] ?? null;
                        $selections[] = ['id' => $id && $entries->has($id) ? $id : null, 'name' => $name,
                            'custom' => ! empty($old['custom']) || ! $id || ! $entries->has($id), 'legacy' => true];
                        continue;
                    }
                    $matches = collect($choices[$key])->filter(fn ($entry) => mb_strtolower($entry['name']) === mb_strtolower(trim($name)));
                    if ($matches->count() !== 1) {
                        throw ValidationException::withMessages([$key => 'Select a valid active catalog item by ID, or use the existing Other field for a custom item.']);
                    }
                    $entry = $matches->first();
                    $selections[] = ['id' => $entry['id'], 'name' => $entry['name']];
                }
                if (filled($validated[$otherField] ?? null)) {
                    $name = trim($validated[$otherField]);
                    $wasCustom = collect($existing)->contains(fn ($row) => ($row['name'] ?? '') === $name && ! empty($row['custom']));
                    $selections[] = ['id' => null, 'name' => $name, 'custom' => true, 'legacy' => $wasCustom];
                }
            }
            $result[$key] = collect($selections)->unique(fn ($entry) => ! empty($entry['id']) ? 'id:'.$entry['id'] : 'custom:'.mb_strtolower($entry['name']))->values()->all();
        }
        return $result;
    }

    /** A catalog checkbox controls inclusion; it must not collapse existing pricing instances. */
    public function preserveExistingInstances(array $rebuilt, array $existing): array
    {
        $result = [];
        foreach ($rebuilt as $item) {
            $type = strtoupper((string) ($item['type'] ?? 'SERVICE'));
            $idKey = $type === 'PRODUCT' ? 'product_id' : 'service_id';
            $matches = array_values(array_filter($existing, function ($row) use ($item, $type, $idKey) {
                if (! is_array($row) || strtoupper((string) ($row['type'] ?? 'SERVICE')) !== $type) { return false; }
                if (! empty($item[$idKey])) { return (string) ($row[$idKey] ?? '') === (string) $item[$idKey]; }
                return empty($row[$idKey]) && ($row['name'] ?? '') === $item['name'] && ! empty($item['custom']);
            }));
            if (count($matches) < 2) { $result[] = $item; continue; }
            foreach ($matches as $row) {
                $result[] = array_merge($row, ['id' => $row['id'] ?? (string) \Illuminate\Support\Str::uuid(),
                    'type' => $type, 'name' => $item['name'], 'custom' => $item['custom'] ?? false]);
            }
        }
        return $result;
    }

    public function configuration(array $selections, string $clientType): array
    {
        $rows = [];
        foreach (['SERVICE' => 'services', 'PRODUCT' => 'products'] as $type => $key) {
            foreach ($selections[$key] as $selection) {
                $rows[] = ['type' => $type, 'name' => $selection['name'], 'custom' => $selection['custom'] ?? false,
                    $type === 'PRODUCT' ? 'product_id' : 'service_id' => $selection['id'] ?? null];
            }
        }
        $probe = new Deal(['services_products' => $rows, 'customer_type' => $clientType]);
        $catalog = app(ProductCatalogConfiguration::class)->forDeal($probe);
        $items = $catalog['services'] ?? [];
        foreach ($catalog['products'] as $product) {
            $model = Product::find($product['product_id']);
            if (! $model) { throw ValidationException::withMessages(['product_ids' => 'The selected product changed. Refresh the catalog selection.']); }
            $product['type'] = 'PRODUCT';
            $product['terms'] = app(ProductCatalogConfiguration::class)->applicableTerms($model)
                ->map(fn ($term) => ['id' => $term->id, 'title' => $term->title, 'content' => $term->content, 'scope' => $term->scope])->all();
            $product['commercial'] = ['price' => is_numeric($model->price) ? (float) $model->price : null,
                'pricing_model' => $model->pricing_model, 'currency' => $model->currency,
                'tax_treatment' => $model->tax_treatment, 'payment_structure' => $model->payment_structure];
            $items[] = $product;
        }
        return ['items' => array_values($items), 'conflicts' => $catalog['conflicts']];
    }
}

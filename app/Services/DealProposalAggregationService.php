<?php

namespace App\Services;

use App\Models\{Deal, DealProposal, Product, Service};

/** Read-only aggregation. Historical content is never hydrated from live catalogs. */
class DealProposalAggregationService
{
    public function sourceRows(Deal $deal): array
    {
        $attributes = $deal->getAttributes();
        // Explicit empty enhanced selection is not an instruction to resurrect legacy rows.
        if (array_key_exists('services_products', $attributes) && $attributes['services_products'] !== null) {
            return array_values(ServiceCatalogConfiguration::list(ServiceCatalogConfiguration::value($deal, 'services_products')));
        }
        $rows = [];
        foreach (['services' => 'SERVICE', 'products' => 'PRODUCT'] as $field => $type) {
            foreach (ServiceCatalogConfiguration::list(ServiceCatalogConfiguration::value($deal, $field)) as $entry) {
                $rows[] = is_array($entry) ? array_merge($entry, ['type' => $type]) : ['name' => $entry, 'type' => $type];
            }
        }
        return $rows;
    }

    public function resolveDealLineItems(Deal $deal, ?array $sourceItems = null): array
    {
        $diagnostics = [];
        return $this->resolveSelections($sourceItems ?? $this->sourceRows($deal), $diagnostics);
    }

    public function resolveSelections(array $rows, array &$diagnostics): array
    {
        $items = [];
        foreach (array_values($rows) as $index => $source) {
            $issues = [];
            if (! is_array($source)) {
                $source = ['name' => is_scalar($source) ? (string) $source : '', 'raw' => $source];
                $issues[] = 'Selection type is unresolved; choose an explicit Service or Product.';
            }
            $type = is_string($source['type'] ?? null) ? strtoupper($source['type']) : '';
            $name = is_scalar($source['name'] ?? null) ? trim((string) $source['name']) : '';
            if ($name === '') {
                $issues[] = 'Selection has no safely readable name; original retained in source.';
            }
            $model = null;
            $config = [];
            $idKey = $type === 'PRODUCT' ? 'product_id' : 'service_id';
            $catalogId = $source[$idKey] ?? null;
            if (! in_array($type, ['SERVICE', 'PRODUCT'], true)) {
                $issues[] = 'Unknown selection type; no catalog or engagement classification inferred.';
            } elseif (empty($source['custom'])) {
                $table = $type === 'PRODUCT' ? 'products' : 'services';
                $nameColumn = $type === 'PRODUCT' ? 'product_name' : 'service_name';
                if ($catalogId !== null && filter_var($catalogId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                    $issues[] = "{$name}: invalid catalog primary ID; stored selection is retained.";
                    $records = [];
                } else {
                    $records = ServiceCatalogConfiguration::rows($table, [$nameColumn], $catalogId !== null ? ['id' => $catalogId] : [], $issues);
                    if ($catalogId === null) {
                        $records = array_values(array_filter($records, fn ($row) => mb_strtolower(trim((string) $row[$nameColumn])) === mb_strtolower($name)));
                    }
                }
                if (count($records) === 1) {
                    $prototype = $type === 'PRODUCT' ? new Product : new Service;
                    $model = $prototype->newFromBuilder($records[0]);
                    $catalogId = $model->getKey();
                    if ($name === '') {
                        $name = (string) $records[0][$nameColumn];
                    }
                    $config = $type === 'PRODUCT'
                        ? app(ProductCatalogConfiguration::class)->forProduct($model, $source['client_type'] ?? null)
                        : app(ServiceCatalogConfiguration::class)->forService($model, $source['client_type'] ?? null);
                    $issues = array_merge($issues, $config['diagnostics']);
                } else {
                    $issues[] = count($records) > 1 ? "{$name}: ambiguous catalog name; select by ID."
                        : "{$name}: catalog record is unavailable; stored selection is retained.";
                }
            }
            $price = null;
            $saved = false;
            foreach (['unitPrice', 'unit_price', 'price', 'amount'] as $key) {
                if (array_key_exists($key, $source)) {
                    $saved = true;
                    $price = ServiceCatalogConfiguration::number($source[$key]);
                    break;
                }
            }
            if (! $saved) {
                $price = $config['commercial']['price'] ?? null;
            }
            if ($price === null) {
                $issues[] = "{$name}: price is unavailable; zero has not been substituted.";
            }
            $qty = array_key_exists('qty', $source) ? ServiceCatalogConfiguration::number($source['qty']) : null;
            $discount = array_key_exists('discount', $source) ? ServiceCatalogConfiguration::number($source['discount']) : 0.0;
            $tax = array_key_exists('tax', $source) ? ServiceCatalogConfiguration::number($source['tax']) : 0.0;
            $total = array_key_exists('line_total', $source) ? ServiceCatalogConfiguration::number($source['line_total'])
                : ($price !== null && $qty !== null && $discount !== null && $tax !== null ? round($qty * $price - $discount + $tax, 2) : null);
            if ($qty !== null && $qty < 0 || $price !== null && $price < 0 || $total !== null && $total < 0) {
                $issues[] = "{$name}: negative commercial values require review.";
            }
            $route = $source['route'] ?? null;
            if ($route === null && isset($config['engagement_structure'])) {
                $structures = ServiceCatalogConfiguration::list($config['engagement_structure']);
                if (count($structures) === 1 && is_scalar(reset($structures))) {
                    $route = reset($structures);
                } elseif ($structures !== []) {
                    $issues[] = "{$name}: multiple engagement structures require review.";
                }
            }
            $item = array_merge($source, [
                'id' => $source['id'] ?? null, 'index' => $index + 1, 'name' => $name, 'type' => $type,
                'service_id' => $type === 'SERVICE' ? $catalogId : null,
                'product_id' => $type === 'PRODUCT' ? $catalogId : null,
                'item_code' => $source['item_code'] ?? $source['code'] ?? $config['sku'] ?? null,
                'description' => $source['description'] ?? $config['scope_of_work'] ?? null,
                'service_version_id' => $source['service_version_id'] ?? $config['service_version_id'] ?? null,
                'qty' => $qty, 'unit_price' => $price, 'price_available' => $price !== null, 'has_saved_price' => $saved,
                'discount' => $discount, 'tax' => $tax, 'line_total' => $total, 'route' => $route,
                'model' => $model, 'source' => $source, 'diagnostics' => array_values(array_unique($issues)),
            ]);
            // Each input occurrence remains distinct, even for the same catalog ID.
            $items[] = $item;
            $diagnostics = array_merge($diagnostics, $issues);
        }
        return $items;
    }

    private function classification(Deal $deal, array $items, array &$diagnostics): ?string
    {
        $persisted = ServiceCatalogConfiguration::value($deal, 'engagement_type');
        if ($deal->exists && is_string($persisted) && trim($persisted) !== '') {
            // Reporting only. This is not a legacy exemption or activation permission.
            return $persisted;
        }
        $routes = [];
        foreach ($items as $item) {
            $raw = is_string($item['route'] ?? null) ? mb_strtolower(trim($item['route'])) : '';
            $canonical = match ($raw) {
                'project', 'project engagement' => 'Project Engagement',
                'regular', 'regular engagement', 'regular retainer', 'regular (retainer) engagement' => 'Regular (Retainer) Engagement',
                default => null,
            };
            if ($canonical === null) {
                $diagnostics[] = 'New/unknown routing requires review; no default classification or Hybrid exemption inferred.';
            } else {
                $routes[] = $canonical;
            }
        }
        if (count(array_unique($routes)) > 1) {
            $diagnostics[] = 'New mixed Project/Regular routing is not supported.';
            return null;
        }
        return count($routes) === count($items) && $routes !== [] ? $routes[0] : null;
    }

    private function totals(array $items, ?DealProposal $proposal, Deal $deal): array
    {
        $sum = fn ($type) => array_reduce(array_filter($items, fn ($item) => $item['type'] === $type),
            fn ($total, $item) => $total === null || $item['line_total'] === null ? null : $total + $item['line_total'], 0.0);
        $services = $deal->exists ? ServiceCatalogConfiguration::number(ServiceCatalogConfiguration::value($deal, 'total_service_fee')) ?? $sum('SERVICE') : $sum('SERVICE');
        $products = $deal->exists ? ServiceCatalogConfiguration::number(ServiceCatalogConfiguration::value($deal, 'total_product_fee')) ?? $sum('PRODUCT') : $sum('PRODUCT');
        $stored = fn ($key) => $proposal ? ServiceCatalogConfiguration::number(ServiceCatalogConfiguration::value($proposal, $key)) : null;
        $legacyTotal = $deal->exists ? ServiceCatalogConfiguration::number(ServiceCatalogConfiguration::value($deal, 'total_estimated_engagement_value')) : null;
        return ['services_total' => $services, 'products_total' => $products,
            'subtotal' => $stored('price_subtotal'), 'discount' => $stored('price_discount'), 'tax' => $stored('price_tax'),
            'total' => $stored('price_total') ?? $legacyTotal ?? ($services !== null && $products !== null ? $services + $products : null),
            'downpayment' => $stored('price_down'), 'balance' => $stored('price_balance')];
    }

    public function aggregate(Deal $deal, ?DealProposal $proposal = null): array
    {
        $diagnostics = [];
        $get = fn ($key) => ServiceCatalogConfiguration::value($deal, $key);
        $pget = fn ($key) => $proposal ? ServiceCatalogConfiguration::value($proposal, $key) : null;
        $historical = $proposal && (ProductCatalogConfiguration::issued($proposal) || $pget('catalog_snapshot') !== null || $pget('line_items_snapshot') !== null);
        if ($historical) {
            // This path performs no master queries, including for snapshot holes or totals.
            try {
                $items = $pget('line_items_snapshot') !== null ? ServiceCatalogConfiguration::list($pget('line_items_snapshot')) : [];
            } catch (\Illuminate\Validation\ValidationException) {
                $items = [];
                $diagnostics[] = 'Malformed historical item snapshot; original retained in raw_line_items_snapshot.';
            }
            $catalog = app(ProductCatalogConfiguration::class)->forDeal($deal, $proposal);
            foreach (['services', 'products', 'terms'] as $key) {
                if (! isset($catalog[$key]) || ! is_array($catalog[$key])) {
                    $diagnostics[] = "Historical catalog snapshot lacks structured {$key}; use its stored document without reconstruction.";
                }
            }
            if ($pget('line_items_snapshot') === null) {
                $diagnostics[] = 'Historical item snapshot is unavailable; use the stored proposal document.';
            }
            $totals = ['services_total' => null, 'products_total' => null, 'subtotal' => $pget('price_subtotal'),
                'discount' => $pget('price_discount'), 'tax' => $pget('price_tax'), 'total' => $pget('price_total'),
                'downpayment' => $pget('price_down'), 'balance' => $pget('price_balance')];
            $route = $deal->exists ? $get('engagement_type') : null;
        } else {
            $rows = $this->sourceRows($deal);
            $client = $get('customer_type');
            $rows = array_map(fn ($row) => is_array($row) ? array_merge($row, ['client_type' => $client]) : $row, $rows);
            $items = $this->resolveSelections($rows, $diagnostics);
            $catalog = app(ProductCatalogConfiguration::class)->forSelections($rows, $client);
            $totals = $this->totals($items, $proposal, $deal);
            if ($totals['total'] === null) {
                $diagnostics[] = 'Commercial total is unresolved; verify saved quantities and prices.';
            }
            $route = $this->classification($deal, $items, $diagnostics);
        }
        $diagnostics = array_values(array_unique(array_merge($diagnostics, $catalog['diagnostics'] ?? [], $catalog['conflicts'] ?? [])));
        $requirements = $activities = [];
        foreach (array_merge(is_array($catalog['services'] ?? null) ? $catalog['services'] : [], is_array($catalog['products'] ?? null) ? $catalog['products'] : []) as $config) {
            if (! is_array($config)) {
                $diagnostics[] = 'Unstructured catalog snapshot entry retained in catalog; review required.';
                continue;
            }
            foreach (['requirements', 'activities'] as $field) {
                if (isset($config[$field]) && ! is_array($config[$field])) {
                    $diagnostics[] = "Unstructured {$field} retained in catalog snapshot; review required.";
                }
            }
            $requirements = array_merge($requirements, is_array($config['requirements'] ?? null) ? $config['requirements'] : []);
            $activities = array_merge($activities, is_array($config['activities'] ?? null) ? $config['activities'] : []);
        }
        $terms = ['specific_terms' => $catalog['terms'] ?? [], 'standard_terms' => [], 'conflicts' => $catalog['conflicts'] ?? [],
            'payment_terms_text' => $historical ? $pget('terms') : ($pget('terms') ?? $get('payment_terms'))];
        return [
            'overview' => ['deal_code' => $get('deal_code'), 'reference_id' => $pget('reference_id'),
                'version' => $pget('proposal_version'), 'engagement_type' => $route],
            'proposal_content' => ['document_html' => $pget('document_html'),
                'scope_of_work' => $historical ? $pget('scope_of_service') : ($pget('scope_of_service') ?? $get('scope_of_work') ?? ProductCatalogConfiguration::summarize($catalog, 'scope_of_work')),
                'deliverables_text' => $historical ? $pget('what_you_will_receive') : ($pget('what_you_will_receive') ?? ProductCatalogConfiguration::summarize($catalog, 'deliverables'))],
            'requirements' => ['items' => $requirements, 'stored_sole' => $pget('requirements_sole'),
                'stored_juridical' => $pget('requirements_juridical'), 'stored_optional' => $pget('requirements_optional')],
            'workflow' => ['activities' => $activities], 'commercials' => $totals,
            'engagement' => ['classification' => $route, 'deal_id' => $deal->getKey(), 'items' => $items],
            'reporting' => [], 'automation' => [], 'terms' => $terms,
            'line_items' => $items, 'totals' => $totals, 'engagement_route' => $route, 'catalog' => $catalog,
            'raw_line_items_snapshot' => $historical ? $pget('line_items_snapshot') : null,
            'diagnostics' => $diagnostics, 'compatible' => $diagnostics === [], 'historical' => (bool) $historical,
        ];
    }
}

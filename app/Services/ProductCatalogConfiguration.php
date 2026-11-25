<?php

namespace App\Services;

use App\Models\{Deal, DealProposal, Product};

/** Baseline-compatible configuration reads; no catalog creation, backfill or hooks. */
class ProductCatalogConfiguration
{
    public static function scope(?string $scope): string
    {
        return match (mb_strtolower(trim((string) $scope))) {
            'service specific', 'service_specific', 'product_specific' => 'service_specific',
            'global scope', 'global' => 'global',
            'service area scope', 'service_area' => 'service_area',
            'category' => 'category', default => '',
        };
    }

    public function requirementApplies(?string $configured, ?string $clientType): bool
    {
        $group = ServiceCatalogConfiguration::clientGroup($configured);
        $client = ServiceCatalogConfiguration::clientGroup($clientType);
        return $group !== null && $client !== null && $client !== 'all' && ($group === 'all' || $group === $client);
    }

    public static function summarize(array $catalog, string $field): string
    {
        $parts = [];
        foreach (array_merge($catalog['services'] ?? [], $catalog['products'] ?? []) as $item) {
            $value = $item[$field] ?? null;
            if (! is_scalar($value) || trim((string) $value) === '') {
                continue;
            }
            $parts[] = $field === 'scope_of_work'
                ? (isset($item['product_id']) ? 'Product: ' : 'Service: ').($item['name'] ?? '')."\n".$value : (string) $value;
        }
        return implode("\n\n", array_unique($parts));
    }

    public function applicableTerms(Product $product, ?array &$diagnostics = null)
    {
        $diagnostics ??= [];
        $rows = ServiceCatalogConfiguration::rows('product_terms', ['product_id', 'title', 'content', 'status', 'scope', 'sort_order'], [], $diagnostics);
        $terms = [];
        foreach ($rows as $row) {
            if (mb_strtolower(trim((string) $row['status'])) !== 'active') {
                continue;
            }
            $owners = ServiceCatalogConfiguration::rows('products', [], ['id' => $row['product_id']], $diagnostics);
            if ($owners === []) {
                $diagnostics[] = "Product term {$row['id']}: owner is unavailable.";
                continue;
            }
            $scope = self::scope($row['scope']);
            $applies = false;
            if ($scope === 'service_specific') {
                $applies = (string) $row['product_id'] === (string) $product->getKey();
            } elseif ($scope === 'global') {
                $applies = true;
            } elseif (in_array($scope, ['service_area', 'category'], true)) {
                $anchor = $row[$scope === 'service_area' ? 'scope_service_area' : 'scope_category'] ?? null;
                if ($anchor === null || $anchor === '') {
                    $diagnostics[] = "Product term {$row['id']}: missing immutable scope anchor; applicability requires review.";
                    continue;
                }
                $actual = $scope === 'service_area'
                    ? (ServiceCatalogConfiguration::value($product, 'product_area') ?: ServiceCatalogConfiguration::value($product, 'service_area'))
                    : ServiceCatalogConfiguration::value($product, 'category');
                try {
                    $left = ServiceCatalogConfiguration::list($anchor);
                    $right = ServiceCatalogConfiguration::list($actual);
                } catch (\Illuminate\Validation\ValidationException) {
                    $diagnostics[] = "Product term {$row['id']}: malformed scope data requires review.";
                    continue;
                }
                if (count(array_filter(array_merge($left, $right), 'is_scalar')) !== count($left) + count($right)) {
                    $diagnostics[] = "Product term {$row['id']}: unsupported scope data requires review.";
                    continue;
                }
                $left = array_map(fn ($v) => mb_strtolower(trim((string) $v)), $left);
                $right = array_map(fn ($v) => mb_strtolower(trim((string) $v)), $right);
                $applies = array_intersect($left, $right) !== [];
            } else {
                $diagnostics[] = "Product term {$row['id']}: unsupported term scope.";
            }
            if ($applies) {
                $terms[] = (object) array_merge($row, ['scope' => $scope]);
            }
        }
        usort($terms, fn ($a, $b) => [$a->sort_order, $a->id] <=> [$b->sort_order, $b->id]);
        return collect($terms);
    }

    public function forProduct(Product $product, ?string $clientType): array
    {
        $diagnostics = [];
        $get = fn ($key) => ServiceCatalogConfiguration::value($product, $key);
        $id = (int) $product->getKey();
        $legacy = ServiceCatalogConfiguration::requirements($get('requirements'), $get('requirement_category'), $clientType, 'product', $id, $diagnostics);
        $rows = ServiceCatalogConfiguration::rows('product_requirements', ['product_id', 'name', 'client_type', 'status'], ['product_id' => $id], $diagnostics);
        $requirements = ServiceCatalogConfiguration::mergeRequirements($legacy, $rows, 'product', $clientType, $diagnostics);
        $activities = ServiceCatalogConfiguration::rows('product_activities', ['product_id', 'name', 'parent_id', 'sequence'], ['product_id' => $id], $diagnostics);
        // Preserve the complete configured hierarchy, including child steps.
        usort($activities, fn ($a, $b) => [$a['sequence'], $a['id']] <=> [$b['sequence'], $b['id']]);
        $terms = $this->applicableTerms($product, $diagnostics)->map(fn ($term) => (array) $term)->all();
        $price = ServiceCatalogConfiguration::number($get('price'));
        if ($price === null) {
            $diagnostics[] = "Product {$id}: price is unavailable.";
        }
        return [
            'type' => 'PRODUCT', 'product_id' => $id, 'name' => $get('product_name') ?? $get('name'),
            'sku' => $get('sku') ?? $get('product_id'),
            'scope_of_work' => $get('scope_of_work') ?? $get('product_description'),
            'deliverables' => $get('deliverables') ?? $get('product_inclusions'),
            'client_responsibilities' => $get('client_responsibilities'), 'exclusions' => $get('exclusions'),
            'requirements' => $requirements, 'activities' => $activities, 'terms' => $terms,
            'commercial' => ['price' => $price, 'pricing_model' => $get('pricing_type') ?? $get('pricing_model'),
                'currency' => $get('currency'), 'tax_treatment' => $get('tax_treatment') ?? $get('tax_type'),
                'payment_structure' => $get('payment_structure')],
            'diagnostics' => array_values(array_unique($diagnostics)), 'compatible' => $diagnostics === [],
        ];
    }

    /** Explicit arrays avoid temporary Deal mass assignment and serialization changes. */
    public function forSelections(array $rows, ?string $clientType): array
    {
        $diagnostics = [];
        $rows = array_map(fn ($row) => is_array($row) ? array_merge($row, ['client_type' => $clientType]) : $row, $rows);
        $items = app(DealProposalAggregationService::class)->resolveSelections($rows, $diagnostics);
        $result = ['products' => [], 'services' => [], 'terms' => [], 'conflicts' => [], 'diagnostics' => []];
        $headings = [];
        foreach ($items as $item) {
            $model = $item['model'];
            $localDiagnostics = [];
            if ($model === null) {
                // Keep custom/unresolved selections visible, including supplied obligations.
                $config = $item['source'];
                $config['name'] = $item['name'];
                $config['type'] = $item['type'];
                $config['scope_of_work'] = $config['scope_of_work'] ?? $config['description'] ?? null;
                $config['requirements'] = ServiceCatalogConfiguration::requirements($config['requirements'] ?? null,
                    $config['requirement_category'] ?? null, $clientType, strtolower($item['type']), 0, $localDiagnostics);
                if (isset($config['terms']) && ! is_array($config['terms'])) {
                    $localDiagnostics[] = 'Custom selection terms need a reviewed structured representation; original retained in selection_source.';
                    $config['terms'] = [];
                }
                $config['terms'] ??= [];
                $config['commercial'] = ['price' => $item['unit_price']];
                $config['diagnostics'] = $item['diagnostics'];
                $config['compatible'] = $item['diagnostics'] === [];
            } elseif ($item['type'] === 'SERVICE') {
                $config = app(ServiceCatalogConfiguration::class)->forService($model, $clientType);
            } else {
                $config = $this->forProduct($model, $clientType);
            }
            $config['selection_source'] = $item['source'];
            $config['line_item_id'] = $item['id'];
            if ($item['has_saved_price']) {
                $config['commercial']['price'] = $item['unit_price'];
            }
            $config['diagnostics'] = array_values(array_unique(array_merge($config['diagnostics'] ?? [], $item['diagnostics'], $localDiagnostics)));
            $config['compatible'] = $config['diagnostics'] === [];
            $result[$item['type'] === 'PRODUCT' ? 'products' : 'services'][] = $config;
            $diagnostics = array_merge($diagnostics, $config['diagnostics'] ?? []);
            foreach ($config['terms'] ?? [] as $term) {
                if (! is_array($term) || ! isset($term['title'], $term['content']) || ! is_scalar($term['title']) || ! is_scalar($term['content'])) {
                    $diagnostics[] = 'Unstructured term retained in selection_source; review required before finalization.';
                    continue;
                }
                $heading = mb_strtolower(trim((string) ($term['title'] ?? '')));
                $content = (string) ($term['content'] ?? '');
                if (isset($headings[$heading]) && $headings[$heading] !== $content) {
                    $result['conflicts'][] = "Conflicting term heading: {$heading}";
                }
                $headings[$heading] = $content;
                $result['terms'][] = $term;
            }
        }
        $result['diagnostics'] = array_values(array_unique($diagnostics));
        $result['conflicts'] = array_values(array_unique(array_merge($result['conflicts'], $result['diagnostics'])));
        $result['compatible'] = $result['conflicts'] === [];
        return $result;
    }

    public static function issued(DealProposal $proposal): bool
    {
        return (bool) (ServiceCatalogConfiguration::value($proposal, 'client_form_sent_at')
            || ServiceCatalogConfiguration::value($proposal, 'client_approved_at')
            || ServiceCatalogConfiguration::value($proposal, 'client_submitted_at')
            || in_array(mb_strtolower((string) ServiceCatalogConfiguration::value($proposal, 'status')), ['sent', 'approved', 'accepted', 'contracted', 'rejected', 'partially_approved', 'pending_client_review'], true));
    }

    public function forDeal(Deal $deal, ?DealProposal $proposal = null): array
    {
        $snapshot = $proposal ? ServiceCatalogConfiguration::value($proposal, 'catalog_snapshot') : null;
        if ($snapshot !== null) {
            try {
                $snapshot = ServiceCatalogConfiguration::list($snapshot);
            } catch (\Illuminate\Validation\ValidationException) {
                return ['products' => [], 'services' => [], 'terms' => [], 'raw_catalog_snapshot' => $snapshot,
                    'conflicts' => ['Malformed historical catalog snapshot; retain the original document and review.'],
                    'diagnostics' => ['No live catalog reconstruction was attempted.'], 'compatible' => false];
            }
            // Return original snapshot keys/content without querying or enriching masters.
            return $snapshot;
        }
        if ($proposal && (self::issued($proposal) || ServiceCatalogConfiguration::value($proposal, 'line_items_snapshot') !== null)) {
            return ['products' => [], 'services' => [], 'terms' => [], 'conflicts' => ['Issued proposal has no recoverable catalog snapshot; use its stored document.'],
                'diagnostics' => ['Historical configuration must not be reconstructed from current masters.'], 'compatible' => false, 'legacy_issued' => true];
        }
        $rows = app(DealProposalAggregationService::class)->sourceRows($deal);
        return $this->forSelections($rows, ServiceCatalogConfiguration::value($deal, 'customer_type'));
    }
}

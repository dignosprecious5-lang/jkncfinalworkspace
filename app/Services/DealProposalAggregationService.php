<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\DealProposal;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceRequirement;
use App\Models\ProductRequirement;
use App\Models\ServiceActivity;
use App\Models\ProductActivity;
use App\Models\ServiceTerm;
use App\Models\ProductTerm;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DealProposalAggregationService
{
    /**
     * Aggregate the 9 canonical ORDO dimensions from the Deal's line items and catalog models.
     */
    public function aggregate(Deal $deal, ?DealProposal $proposal = null): array
    {
        $lineItems = $this->resolveDealLineItems($deal, ($proposal?->client_form_sent_at || $proposal?->client_approved_at || is_array($proposal?->catalog_snapshot)) && is_array($proposal->line_items_snapshot) ? $proposal->line_items_snapshot : null);
        $totals = $this->calculateTotals($lineItems, $proposal, $deal);
        $engagementRoute = $this->determineEngagementRoute($lineItems, $deal);

        $overview = $this->buildOverviewTab($deal, $proposal, $lineItems, $totals, $engagementRoute);
        $proposalContent = $this->buildProposalContentTab($deal, $proposal, $lineItems);
        $requirements = $this->buildRequirementsTab($deal, $proposal, $lineItems);
        $workflow = $this->buildWorkflowTab($deal, $proposal, $lineItems);
        $commercials = $this->buildCommercialsTab($deal, $proposal, $lineItems, $totals);
        $engagement = $this->buildEngagementTab($deal, $proposal, $lineItems, $totals, $engagementRoute);
        $reporting = $this->buildReportingTab($deal, $proposal, $lineItems, $totals);
        $automation = $this->buildAutomationTab($deal, $proposal, $lineItems);
        $terms = $this->buildTermsTab($deal, $proposal, $lineItems, $totals);

        return [
            'overview' => $overview,
            'proposal_content' => $proposalContent,
            'requirements' => $requirements,
            'workflow' => $workflow,
            'commercials' => $commercials,
            'engagement' => $engagement,
            'reporting' => $reporting,
            'automation' => $automation,
            'terms' => $terms,
            'line_items' => $lineItems,
            'totals' => $totals,
            'engagement_route' => $engagementRoute,
        ];
    }

    /**
     * Resolve all line items from the deal (Add Items and Services as source of truth).
     */
    public function resolveDealLineItems(Deal $deal, ?array $sourceItems = null): array
    {
        $rawItems = $sourceItems ?? $deal->services_products;
        if (is_string($rawItems)) {
            $rawItems = json_decode($rawItems, true) ?: [];
        }

        $items = [];

        if (!empty($rawItems) && is_array($rawItems)) {
            foreach ($rawItems as $index => $item) {
                if (is_array($item)) {
                    $type = strtoupper((string) ($item['type'] ?? $this->dealItemType($item['name'] ?? '', $deal) ?? 'SERVICE'));
                    $items[] = $this->normalizeLineItem(array_merge($item, ['type' => $type]), $index + 1, $deal);
                } elseif (is_string($item) && filled(trim($item))) {
                    $items[] = $this->resolveItemByName($item, $index + 1, $deal, $this->dealItemType($item, $deal));
                }
            }
        }

        if (empty($items) && $sourceItems === null) {
            $idx = 1;
            $servicesList = $this->normalizeList($deal->services);
            foreach ($servicesList as $sName) {
                $items[] = $this->resolveItemByName($sName, $idx++, $deal, 'SERVICE');
            }

            $productsList = $this->normalizeList($deal->products);
            foreach ($productsList as $pName) {
                $items[] = $this->resolveItemByName($pName, $idx++, $deal, 'PRODUCT');
            }
        }

        return $items;
    }

    /**
     * Normalize an existing line item array and link with catalog model.
     */
    protected function normalizeLineItem(array $item, int $index, Deal $deal): array
    {
        $type = strtoupper($item['type'] ?? 'SERVICE');
        $name = trim((string) ($item['name'] ?? 'Commercial Scope Item'));
        $qty = is_numeric($item['qty'] ?? null) ? (float) $item['qty'] : 1.0;
        $savedUnitPrice = null;
        foreach (['unitPrice', 'price', 'unit_price', 'amount'] as $priceKey) {
            if (is_numeric($item[$priceKey] ?? null)) {
                $savedUnitPrice = (float) $item[$priceKey];
                break;
            }
        }
        $unitPrice = $savedUnitPrice ?? 0.0;
        $discount = (float)($item['discount'] ?? 0);
        $tax = (float)($item['tax'] ?? 0);
        $unit = $item['unit'] ?? ($type === 'PRODUCT' ? 'document' : 'lot');
        $billing = $item['billing'] ?? ($deal->payment_terms ?: 'Full Payment Before Service');
        $route = $item['route'] ?? ($type === 'PRODUCT' ? 'Project' : 'Regular');

        $linkedModel = null;
        $serviceId = $item['service_id'] ?? null;
        $productId = $item['product_id'] ?? null;
        if (! empty($item['custom'])) { $serviceId = null; $productId = null; }
        $itemCode = $item['item_code'] ?? ($item['code'] ?? null);
        $description = $item['description'] ?? '';
        $activityOutput = $item['activity_output'] ?? '';
        $frequency = $item['frequency'] ?? '';
        $serviceArea = $item['service_area'] ?? '';
        $serviceVersionId = $item['service_version_id'] ?? null;
        $engagementBehavior = $item['engagement_behavior'] ?? '';
        $taxTreatment = $item['tax_treatment'] ?? '';
        $shortDescription = $item['short_description'] ?? '';
        $deadline = $item['deadline'] ?? '';
        $priceAvailable = $savedUnitPrice !== null;

        if ($type === 'PRODUCT') {
            $product = null;
            if ($productId) {
                $product = Product::query()->find($productId);
            }
            if (!$product && ! $productId && empty($item['custom']) && filled($name)) {
                $product = Product::query()
                    ->where(function ($q) use ($name) {
                        $q->where('product_name', $name)
                          ->orWhere('name', $name);
                    })->first();
            }
            if ($product) {
                $linkedModel = $product;
                $productId = $product->id;
                $itemCode = $itemCode ?: ($product->sku ?: ('PRD-' . str_pad($product->id, 4, '0', STR_PAD_LEFT)));
                if (empty($description)) {
                    $description = $product->product_description ?: ($product->description ?: '');
                }
                if ($savedUnitPrice === null && is_numeric($product->price)) {
                    $unitPrice = (float)$product->price;
                    $priceAvailable = true;
                }
                $activityOutput = $activityOutput ?: ($product->product_activity_output ?? '');
                $frequency = $frequency ?: ($product->frequency ?? '');
                $serviceArea = $serviceArea ?: ($product->product_area ?? $product->service_area ?? '');
                $deadline = $deadline ?: ($product->deadline ?? '');
            }
        } else {
            $service = null;
            if ($serviceId && empty($item['custom'])) {
                $service = Service::query()->find($serviceId);
            }
            if (!$service && ! $serviceId && empty($item['custom']) && filled($name)) {
                $service = Service::query()
                    ->where(function ($q) use ($name) {
                        $q->where('name', $name)
                          ->orWhere('service_name', $name);
                    })->first();
            }
            if ($service) {
                $linkedModel = $service;
                $serviceId = $service->id;
                $itemCode = $itemCode ?: ($service->service_code ?: ('SVC-' . str_pad($service->id, 4, '0', STR_PAD_LEFT)));
                if (empty($description)) {
                    $description = $service->activeVersion?->scope_of_work
                        ?: ($service->service_description ?: ($service->short_description ?: ''));
                }
                if ($savedUnitPrice === null) {
                    $catalogPrice = $service->activeVersion?->standard_price;
                    if ($catalogPrice === null) {
                        $catalogPrice = $service->price_fee;
                    }
                    if (is_numeric($catalogPrice)) {
                        $unitPrice = (float) $catalogPrice;
                        $priceAvailable = true;
                    }
                }
                $activityOutput = $activityOutput ?: ($service->service_activity_output ?? '');
                $frequency = $frequency ?: ($service->frequency ?? '');
                $serviceArea = $serviceArea ?: ($service->service_area ?? '');
                $serviceVersionId = $serviceVersionId ?: $service->active_version_id;
                $engagementBehavior = $engagementBehavior ?: ($service->engagement_behavior ?? $service->engagement_type ?? '');
                $taxTreatment = $taxTreatment ?: ($service->activeVersion?->tax_treatment ?: ($service->tax_type ?? ''));
                $shortDescription = $shortDescription ?: ($service->activeVersion?->client_description ?: ($service->activeVersion?->short_name ?: ($service->short_description ?? '')));
                $deadline = $deadline ?: ($service->deadline ?? '');
                if (empty($item['route'])) {
                    $behavior = strtolower($service->engagement_behavior ?? '');
                    if ($behavior === 'regular') $route = 'Regular';
                    elseif ($behavior === 'project') $route = 'Project';
                    elseif ($behavior === 'both' || $behavior === 'hybrid') $route = 'Hybrid';
                }
            }
        }

        if (empty($itemCode)) {
            $itemCode = ($type === 'PRODUCT' ? 'PRD-' : 'SVC-') . str_pad($index, 4, '0', STR_PAD_LEFT);
        }

        $baseTotal = $qty * $unitPrice;
        $lineTotal = max(0, $baseTotal - $discount + $tax);

        return [
            'id' => $item['id'] ?? $index,
            'index' => $index,
            'type' => $type,
            'name' => $name,
            'item_code' => $itemCode,
            'service_id' => $serviceId,
            'service_version_id' => $serviceVersionId,
            'product_id' => $productId,
            'associated_service_id' => $type === 'PRODUCT' && empty($item['custom']) ? ($item['associated_service_id'] ?? null) : null,
            'description' => ! empty($item['custom'])
                ? (string) $description
                : ($description ?: (string) ($deal->scope_of_work ?: '')),
            'custom' => (bool) ($item['custom'] ?? false),
            'activity_output' => $activityOutput,
            'frequency' => $frequency,
            'service_area' => $this->normalizeCatalogList($serviceArea),
            'engagement_behavior' => $engagementBehavior,
            'tax_treatment' => $taxTreatment,
            'short_description' => $shortDescription,
            'deadline' => $deadline instanceof \DateTimeInterface ? $deadline->format('Y-m-d') : $deadline,
            'qty' => $qty,
            'unit' => $unit,
            'unit_price' => $unitPrice,
            'has_saved_price' => $savedUnitPrice !== null,
            'price_available' => $priceAvailable,
            'discount' => $discount,
            'tax' => $tax,
            'line_total' => $lineTotal,
            'billing' => $billing,
            'route' => $route,
            'model' => $linkedModel,
        ];
    }

    /**
     * Resolve a line item given its name by matching against DB catalog.
     */
    protected function resolveItemByName(string $name, int $index, Deal $deal, ?string $forcedType = null): array
    {
        $name = trim($name);
        $type = $forcedType;

        $service = null;
        $product = null;

        if (!$type || $type === 'SERVICE') {
            $service = Service::query()
                ->where(function ($q) use ($name) {
                    $q->where('name', $name)->orWhere('service_name', $name);
                })->first();
        }

        if (!$service && (!$type || $type === 'PRODUCT')) {
            $product = Product::query()
                ->where(function ($q) use ($name) {
                    $q->where('product_name', $name)->orWhere('name', $name);
                })->first();
        }

        if ($product) {
            $item = $this->normalizeLineItem([
                'id' => $index,
                'type' => 'PRODUCT',
                'name' => $product->name,
                'product_id' => $product->id,
                'item_code' => $product->sku,
                'description' => $product->description ?: $product->product_description,
                'qty' => 1,
                'unit' => 'document',
                'unitPrice' => (float)$product->price,
                'billing' => $deal->payment_terms ?: 'Full Payment Before Service',
                'route' => 'Project',
            ], $index, $deal);
            $item['has_saved_price'] = false;

            return $item;
        }

        if ($service) {
            $behavior = strtolower($service->engagement_behavior ?? 'regular');
            $route = ($behavior === 'project') ? 'Project' : (($behavior === 'both') ? 'Hybrid' : 'Regular');

            $item = $this->normalizeLineItem([
                'id' => $index,
                'type' => 'SERVICE',
                'name' => $service->name,
                'service_id' => $service->id,
                'item_code' => $service->service_code,
                'description' => $service->activeVersion?->scope_of_work ?: $service->service_description,
                'qty' => 1,
                'unit' => 'lot',
                'unitPrice' => (float) ($service->activeVersion?->standard_price ?? $service->price_fee ?? 0),
                'billing' => $deal->payment_terms ?: 'Full Payment Before Service',
                'route' => $route,
            ], $index, $deal);
            $item['has_saved_price'] = false;

            return $item;
        }

        // Generic fallback item
        return $this->normalizeLineItem([
            'id' => $index,
            'type' => $forcedType ?: 'SERVICE',
            'name' => $name,
            'qty' => 1,
            'unit' => 'lot',
            'unitPrice' => 0,
            'billing' => $deal->payment_terms ?: 'Full Payment Before Service',
            'route' => 'Regular',
        ], $index, $deal);
    }

    private function dealItemType(string $name, Deal $deal): ?string
    {
        $key = Str::lower(trim($name));
        if ($key === '') {
            return null;
        }

        $serviceNames = collect($this->normalizeList($deal->services))
            ->map(fn (string $value): string => Str::lower(trim($value)));
        $productNames = collect($this->normalizeList($deal->products))
            ->map(fn (string $value): string => Str::lower(trim($value)));

        if ($productNames->contains($key) && ! $serviceNames->contains($key)) {
            return 'PRODUCT';
        }

        if ($serviceNames->contains($key)) {
            return 'SERVICE';
        }

        return null;
    }

    /**
     * Calculate commercial totals.
     */
    protected function calculateTotals(array $lineItems, ?DealProposal $proposal, Deal $deal): array
    {
        $servicesTotal = 0;
        $productsTotal = 0;
        $lineDiscounts = 0;
        $lineTaxes = 0;

        foreach ($lineItems as $item) {
            $base = (float)$item['qty'] * (float)$item['unit_price'];
            if ($item['type'] === 'PRODUCT') {
                $productsTotal += $base;
            } else {
                $servicesTotal += $base;
            }
            $lineDiscounts += (float)$item['discount'];
            $lineTaxes += (float)$item['tax'];
        }

        // Check if proposal has explicit overrides or deal totals
        $discount = $proposal && $proposal->price_discount !== null
            ? (float)$proposal->price_discount
            : max($lineDiscounts, (float)($deal->discount ?: ($deal->deal_discount ?: 0)));

        $tax = $proposal && $proposal->price_tax !== null
            ? (float)$proposal->price_tax
            : $lineTaxes;

        $subtotal = max(0, ($servicesTotal + $productsTotal) - $discount);
        $total = $subtotal + $tax;

        // If proposal has an override total, preserve it
        if ($proposal && (float)$proposal->price_total > 0 && empty($lineItems)) {
            $total = (float)$proposal->price_total;
        }

        $downpayment = $proposal && (float)$proposal->price_down > 0
            ? (float)$proposal->price_down
            : round($total * 0.5, 2);

        $balance = $proposal && (float)$proposal->price_balance > 0
            ? (float)$proposal->price_balance
            : max(0, $total - $downpayment);

        return [
            'services_total' => $servicesTotal,
            'products_total' => $productsTotal,
            'discount' => $discount,
            'tax' => $tax,
            'subtotal' => $subtotal,
            'total' => $total,
            'downpayment' => $downpayment,
            'balance' => $balance,
        ];
    }

    /**
     * Determine engagement classification: Regular, Project, or Hybrid.
     */
    protected function determineEngagementRoute(array $lineItems, Deal $deal): string
    {
        $hasRegular = false;
        $hasProject = false;

        foreach ($lineItems as $item) {
            $r = strtolower($item['route'] ?? '');
            if (str_contains($r, 'regular') || str_contains($r, 'retainer')) {
                $hasRegular = true;
            }
            if (str_contains($r, 'project')) {
                $hasProject = true;
            }
            if (str_contains($r, 'hybrid') || str_contains($r, 'both')) {
                $hasRegular = true;
                $hasProject = true;
            }
        }

        if ($hasRegular && $hasProject) {
            return 'Hybrid (Regular + Project)';
        }
        if ($hasProject) {
            return 'Project Engagement';
        }
        if ($hasRegular) {
            return 'Regular (Retainer) Engagement';
        }

        if (filled($deal->engagement_type)) {
            return $deal->engagement_type;
        }

        return 'Regular (Retainer) Engagement';
    }

    /*
    |--------------------------------------------------------------------------
    | TAB 1: OVERVIEW
    |--------------------------------------------------------------------------
    */
    protected function buildOverviewTab(Deal $deal, ?DealProposal $proposal, array $lineItems, array $totals, string $engagementRoute): array
    {
        $servicesCount = count(array_filter($lineItems, fn($i) => $i['type'] === 'SERVICE'));
        $productsCount = count(array_filter($lineItems, fn($i) => $i['type'] === 'PRODUCT'));

        $status = 'Draft';
        if ($proposal) {
            if ($proposal->client_approved_at || in_array($deal->proposal_decision, ['Accepted', 'Approved']) || in_array($deal->proposal_status, ['Accepted', 'Approved'])) {
                $status = 'Approved';
            } elseif ($proposal->client_form_sent_at || filled($deal->proposal_sent_at)) {
                $status = 'Sent to Client';
            } elseif ($proposal->status) {
                $status = ucfirst((string)$proposal->status);
            }
        }

        $versionNumber = $proposal?->proposal_version ?: 1;

        return [
            'deal_code' => $deal->deal_code ?: ('CONDEAL-' . $deal->id),
            'deal_title' => $deal->deal_title ?: ($deal->deal_name ?: 'Client Deal'),
            'reference_id' => $proposal?->reference_id ?: ('PROP-' . ($deal->deal_code ?: $deal->id)),
            'client_name' => trim(($deal->first_name ?: $deal->contact?->first_name) . ' ' . ($deal->last_name ?: $deal->contact?->last_name)) ?: 'Valued Client',
            'company_name' => $deal->company_name ?: ($deal->contact?->company_name ?: 'Client Business'),
            'location' => $proposal?->location ?: ($deal->company_address ?: ($deal->address ?: 'Cebu, Philippines')),
            'proposal_date' => $proposal?->proposal_date ? $proposal->proposal_date->format('F d, Y') : now()->format('F d, Y'),
            'prepared_by' => $proposal?->prepared_by_name ?: (auth()->user()?->name ?: 'John Kelly & Company'),
            'item_count' => count($lineItems),
            'services_count' => $servicesCount,
            'products_count' => $productsCount,
            'total_value' => $totals['total'],
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'tax' => $totals['tax'],
            'engagement_type' => $engagementRoute,
            'status' => $status,
            'version' => 'V' . $versionNumber,
            'items_summary' => array_map(fn($it) => [
                'code' => $it['item_code'],
                'name' => $it['name'],
                'type' => $it['type'],
                'qty' => $it['qty'],
                'unit' => $it['unit'],
                'line_total' => $it['line_total'],
            ], $lineItems),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | TAB 2: PROPOSAL CONTENT
    |--------------------------------------------------------------------------
    */
    protected function buildProposalContentTab(Deal $deal, ?DealProposal $proposal, array $lineItems): array
    {
        $itemsContent = []; $scopes = []; $deliverables = []; $responsibilities = []; $exclusions = [];
        $catalog = app(ProductCatalogConfiguration::class)->forDeal($deal, $proposal);
        foreach (array_merge($catalog['services'] ?? [], $catalog['products']) as $configuration) {
            $type = isset($configuration['product_id']) ? 'PRODUCT' : 'SERVICE';
            $scope = (string) ($configuration['scope_of_work'] ?? '');
            if (filled($scope)) { $scopes[] = $configuration['name'].": ".$scope; }
            $itemDeliverables = $this->splitLines($configuration['deliverables'] ?? null);
            $itemResponsibilities = $this->splitLines($configuration['client_responsibilities'] ?? null);
            $itemExclusions = $this->splitLines($configuration['exclusions'] ?? null);
            $itemsContent[] = [
                'name' => $configuration['name'], 'code' => $configuration['sku'] ?? '', 'type' => $type,
                'product_id' => $configuration['product_id'] ?? null, 'service_id' => $configuration['service_id'] ?? null,
                'description' => $scope, 'deliverables' => $itemDeliverables,
                'client_responsibilities' => $itemResponsibilities, 'exclusions' => $itemExclusions,
            ];
            $deliverables = array_merge($deliverables, $itemDeliverables);
            $responsibilities = array_merge($responsibilities, $itemResponsibilities);
            $exclusions = array_merge($exclusions, $itemExclusions);
        }
        foreach ($lineItems as $item) {
            if ($item['model'] !== null) { continue; }
            if (filled($item['description'])) { $scopes[] = $item['name'].": ".$item['description']; }
            $itemsContent[] = ['name' => $item['name'], 'code' => $item['item_code'], 'type' => $item['type'],
                'description' => $item['description'], 'deliverables' => [], 'client_responsibilities' => [], 'exclusions' => []];
        }

        // Combine scopes
        $aggregatedScope = $proposal?->scope_of_service ?: implode("\n\n", $scopes);
        if (empty($aggregatedScope)) {
            $aggregatedScope = $deal->scope_of_work ?: '';
        }

        $allDeliverables = array_values(array_unique($deliverables));
        $allResponsibilities = array_values(array_unique(array_filter($responsibilities)));
        $allExclusions = array_values(array_unique(array_filter($exclusions)));

        return [
            'items' => $itemsContent,
            'scope_of_work' => $aggregatedScope,
            'deliverables' => $allDeliverables,
            'deliverables_text' => $proposal?->what_you_will_receive ?: implode("\n", array_map(fn($d) => "• " . $d, $allDeliverables)),
            'client_responsibilities' => $allResponsibilities,
            'exclusions' => $allExclusions,
            'client_info' => [
                'client_name' => trim(($deal->first_name ?: $deal->contact?->first_name) . ' ' . ($deal->last_name ?: $deal->contact?->last_name)) ?: 'Client',
                'company_name' => $deal->company_name ?: 'Business Entity',
                'email' => $deal->email ?: ($deal->contact?->email ?: ''),
                'mobile' => $deal->mobile_number ?: ($deal->mobile ?: ($deal->contact?->phone ?: '')),
                'address' => $deal->company_address ?: ($deal->address ?: 'Philippines'),
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | TAB 3: REQUIREMENTS
    |--------------------------------------------------------------------------
    */
    protected function buildRequirementsTab(Deal $deal, ?DealProposal $proposal, array $lineItems): array
    {
        $requirements = []; $seen = [];
        $catalog = app(ProductCatalogConfiguration::class)->forDeal($deal, $proposal);
        foreach (array_merge($catalog['services'] ?? [], $catalog['products']) as $configuration) {
            foreach ($configuration['requirements'] as $requirement) {
                $requirements[] = array_merge($requirement, [
                    'item_name' => $configuration['name'], 'item_code' => $configuration['sku'] ?? '',
                    'title' => $requirement['name'], 'type' => ucfirst($requirement['origin']).' Requirement',
                ]);
            }
        }

        // 3. Deal-level client requirements if configured
        if (!empty($deal->client_requirements)) {
            $dealReqs = is_array($deal->client_requirements) ? $deal->client_requirements : [$deal->client_requirements];
            foreach ($dealReqs as $dr) {
                $title = is_array($dr) ? ($dr['name'] ?? $dr['title'] ?? 'Client Requirement') : (string)$dr;
                $key = Str::slug($title);
                if (!isset($seen[$key]) && filled($title)) {
                    $seen[$key] = true;
                    $requirements[] = [
                        'item_name' => 'Deal Scope',
                        'item_code' => $deal->deal_code ?: 'DEAL',
                        'title' => $title,
                        'description' => 'Specific requirement noted during intake or qualification.',
                        'type' => 'Client Engagement Document',
                        'status' => in_array(strtolower((string) $deal->requirements_status), ['completed', 'submitted', 'approved'], true) ? $deal->requirements_status : 'Pending',
                        'is_mandatory' => true,
                    ];
                }
            }
        }

        $pendingCount = count(array_filter($requirements, fn($r) => strtolower($r['status']) === 'pending'));
        $completedCount = count(array_filter($requirements, fn($r) => in_array(strtolower($r['status']), ['completed', 'submitted', 'approved'])));

        return [
            'requirements' => $requirements,
            'total_count' => count($requirements),
            'pending_count' => $pendingCount,
            'completed_count' => $completedCount,
            'status' => $pendingCount === 0 && count($requirements) > 0 ? 'Completed' : 'Pending Submission',
        ];
    }

    protected function buildWorkflowTab(Deal $deal, ?DealProposal $proposal, array $lineItems): array
    {
        $currentStage = $deal->pipeline_stage;
        $proposalDecision = $deal->proposal_decision ?: 'Pending';

        $isSent = (bool)($proposal?->client_form_sent_at || filled($deal->proposal_sent_at));
        $isApproved = (bool)($proposal?->client_approved_at || filled($deal->approval_date) || in_array($proposalDecision, ['Accepted', 'Approved']) || in_array($deal->proposal_status, ['Accepted', 'Approved']));
        $isDeclined = (bool)($proposalDecision === 'Declined' || $proposalDecision === 'Rejected');

        $workflowState = 'Draft Proposal';
        if ($isApproved) {
            $workflowState = 'Client Approved';
        } elseif ($isDeclined) {
            $workflowState = 'Client Declined';
        } elseif ($isSent) {
            $workflowState = 'Awaiting Client Decision';
        } elseif ($proposal?->status === 'approved') {
            $workflowState = 'Admin Approved (Ready to Send)';
        }

        // Compute outstanding actions based on state
        $outstandingActions = [];
        if (!$isSent && !$isApproved) {
            $outstandingActions[] = [
                'title' => 'Admin Approval & Quality Check',
                'status' => ($proposal?->status === 'approved' ? 'Completed' : 'Pending'),
                'description' => 'Proposal reviewed for accurate pricing, scope, and tax treatment.',
            ];
            $outstandingActions[] = [
                'title' => 'Send Proposal to Client',
                'status' => 'Pending',
                'description' => 'Transmit proposal link with digital acceptance form to client email.',
            ];
        } elseif ($isSent && !$isApproved && !$isDeclined) {
            $outstandingActions[] = [
                'title' => 'Client Acceptance Review',
                'status' => 'In Progress',
                'description' => 'Client reviewing document details, commercial terms, and signing Conforme.',
            ];
        } elseif ($isApproved) {
            $outstandingActions[] = [
                'title' => 'Generate Commercial Quotation & Billing Notice',
                'status' => ($proposal?->invoice_status === 'payment_notice_sent' ? 'Completed' : 'Pending'),
                'description' => 'Issue official statement of account and 50% downpayment invoice.',
            ];
            $outstandingActions[] = [
                'title' => 'Initiate START Onboarding & Activation',
                'status' => ($deal->pipeline_stage === 'Activation' || $deal->pipeline_stage === 'Closed Won' ? 'Completed' : 'Pending'),
                'description' => 'Initialize operational task schedule and consultant work assignment.',
            ];
        }

        // Workflow activities aggregated from selected items
        $activities = [];
        foreach ($lineItems as $item) {
            $model = $item['model'];
            if ($model instanceof Service && $model->activeVersion) {
                foreach ($model->activeVersion->mainActivities as $act) {
                    $activities[] = [
                        'item_name' => $item['name'],
                        'title' => $act->name ?: 'Service Activity',
                        'sequence' => $act->sequence ?? 1,
                        'description' => $act->description ?: 'Operational execution step.',
                    ];
                }
            } elseif ($model instanceof Product) {
                foreach ($model->activities as $act) {
                    $activities[] = [
                        'item_name' => $item['name'],
                        'title' => $act->activity_name ?: 'Product Deliverable Activity',
                        'sequence' => $act->sequence ?? 1,
                        'description' => $act->description ?: 'Deliverable preparation step.',
                    ];
                }
            }
        }

        if (empty($activities)) {
            $activities = [
                ['item_name' => 'Phase 1', 'title' => 'Intake & Documentary Verification', 'sequence' => 1, 'description' => 'Review client records against statutory checklist.'],
                ['item_name' => 'Phase 2', 'title' => 'Preparation & Professional Drafting', 'sequence' => 2, 'description' => 'Draft filings, corporate minutes, schedules, and calculations.'],
                ['item_name' => 'Phase 3', 'title' => 'Client Conforme & Government Liaison', 'sequence' => 3, 'description' => 'Execute filings with relevant city or national agencies.'],
                ['item_name' => 'Phase 4', 'title' => 'Deliverable Handover & Engagement Closure', 'sequence' => 4, 'description' => 'Hand over stamped clearances, certificates, or reports to client.'],
            ];
        }

        return [
            'stage' => $currentStage,
            'workflow_state' => $workflowState,
            'client_decision' => $proposalDecision,
            'is_sent' => $isSent,
            'sent_at' => $proposal?->client_form_sent_at ? $proposal->client_form_sent_at->format('M d, Y h:i A') : ($deal->proposal_sent_at ? $deal->proposal_sent_at->format('M d, Y h:i A') : null),
            'is_approved' => $isApproved,
            'approved_at' => $proposal?->client_approved_at ? $proposal->client_approved_at->format('M d, Y') : ($deal->approval_date ? $deal->approval_date->format('M d, Y') : null),
            'approved_by' => $proposal?->client_approved_by_name ?: ($deal->approved_by_name ?: null),
            'is_declined' => $isDeclined,
            'decline_reason' => $deal->decline_reason,
            'outstanding_actions' => $outstandingActions,
            'activities' => $activities,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | TAB 5: COMMERCIALS
    |--------------------------------------------------------------------------
    */
    protected function buildCommercialsTab(Deal $deal, ?DealProposal $proposal, array $lineItems, array $totals): array
    {
        return [
            'line_items' => $lineItems,
            'total_services' => $totals['services_total'],
            'total_products' => $totals['products_total'],
            'discount' => $totals['discount'],
            'tax' => $totals['tax'],
            'subtotal' => $totals['subtotal'],
            'total' => $totals['total'],
            'downpayment' => $totals['downpayment'],
            'balance' => $totals['balance'],
            'payment_terms' => $proposal?->terms ?: ($deal->payment_terms ?: 'Payment terms require confirmation.'),
            'pricing_model' => $deal->pricing_model ?: 'Fixed Professional Fee',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | TAB 6: ENGAGEMENT
    |--------------------------------------------------------------------------
    */
    protected function buildEngagementTab(Deal $deal, ?DealProposal $proposal, array $lineItems, array $totals, string $engagementRoute): array
    {
        $regularItems = array_values(array_filter($lineItems, fn($i) => str_contains(strtolower($i['route']), 'regular')));
        $projectItems = array_values(array_filter($lineItems, fn($i) => str_contains(strtolower($i['route']), 'project')));

        $cadence = 'Per Engagement';
        if (str_contains(strtolower($engagementRoute), 'hybrid')) {
            $cadence = 'Monthly Retainer + Project Milestones';
        } elseif (str_contains(strtolower($engagementRoute), 'regular')) {
            $cadence = 'Monthly Retainer Cycle';
        }

        return [
            'classification' => $engagementRoute,
            'cadence' => $cadence,
            'regular_items' => $regularItems,
            'project_items' => $projectItems,
            'regular_count' => count($regularItems),
            'project_count' => count($projectItems),
            'duration' => $deal->estimated_duration ?: 'As specified in engagement scope',
            'planned_start_date' => $deal->planned_start_date ? $deal->planned_start_date->format('F d, Y') : 'Upon Acceptance',
            'estimated_completion_date' => $deal->estimated_completion_date ? $deal->estimated_completion_date->format('F d, Y') : 'TBD',
            'assigned_consultant' => $deal->lead_consultant ?: ($deal->assigned_consultant ?: 'John Kelly D. Abalde'),
            'assigned_associate' => $deal->lead_associate ?: ($deal->assigned_associate ?: 'Ma. Lourdes T. Mata'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | TAB 7: REPORTING
    |--------------------------------------------------------------------------
    */
    protected function buildReportingTab(Deal $deal, ?DealProposal $proposal, array $lineItems, array $totals): array
    {
        $hasRegular = count(array_filter($lineItems, fn($i) => str_contains(strtolower($i['route']), 'regular'))) > 0;
        $frequency = $hasRegular ? 'Monthly & On-Demand' : 'Upon Completion of Deliverables';

        $reports = [
            [
                'title' => 'Executive Engagement & Progress Summary',
                'cadence' => $frequency,
                'recipients' => 'Client Management & Engagement Team',
                'description' => 'Summary of accomplished activities, active agency liaison items, and timeline updates.',
            ],
            [
                'title' => 'Compliance & Transmittal Ledger',
                'cadence' => 'Per Milestone Filing',
                'recipients' => 'Record Custodian & Corporate Secretary',
                'description' => 'Inventory of lodged documents, official receipts, and government clearances.',
            ],
            [
                'title' => 'Final Engagement Handover Report',
                'cadence' => 'Upon Engagement Completion',
                'recipients' => 'Board / Executive Officers',
                'description' => 'Complete compilation of deliverables, statutory certificates, and post-engagement recommendations.',
            ],
        ];

        return [
            'reporting_frequency' => $frequency,
            'reports' => $reports,
            'metrics' => [
                'total_items' => count($lineItems),
                'total_value' => $totals['total'],
                'commercial_status' => $deal->getResolvedCommercialStatus(),
                'proposal_status' => $deal->getResolvedProposalStatus(),
                'stage' => $deal->pipeline_stage,
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | TAB 8: AUTOMATION
    |--------------------------------------------------------------------------
    */
    protected function buildAutomationTab(Deal $deal, ?DealProposal $proposal, array $lineItems): array
    {
        $isApproved = (bool)($proposal?->client_approved_at || filled($deal->approval_date) || in_array($deal->proposal_decision, ['Accepted', 'Approved']) || in_array($deal->proposal_status, ['Accepted', 'Approved']));
        $hasDownpayment = in_array($deal->pipeline_stage, ['Payment', 'Activation', 'Closed Won']);

        $triggers = [
            [
                'trigger_name' => 'START Operational Provisioning',
                'condition' => 'Triggered when client accepts and signs Proposal',
                'is_active' => true,
                'status' => $isApproved ? 'Triggered / Active' : 'Awaiting Client Acceptance',
                'action' => 'Creates START onboarding memo, allocates team assignments, and prepares initial task ledger.',
            ],
            [
                'trigger_name' => 'Client Requirement Gatekeeper',
                'condition' => 'Blocks active execution until mandatory compliance documents are submitted',
                'is_active' => true,
                'status' => 'Monitoring Uploads',
                'action' => 'Sends automated reminders to client email every 3 days if mandatory files are missing.',
            ],
            [
                'trigger_name' => 'Commercial Billing Notice Automation',
                'condition' => 'Triggered upon proposal acceptance to issue downpayment SOA',
                'is_active' => true,
                'status' => $hasDownpayment ? 'Executed' : ($isApproved ? 'Ready to Trigger' : 'Awaiting Acceptance'),
                'action' => 'Issues 50% initial invoice and notifies finance department.',
            ],
            [
                'trigger_name' => 'Escalation Threshold Rule',
                'condition' => 'Triggered if client review exceeds 7 calendar days',
                'is_active' => true,
                'status' => 'Monitoring Timer',
                'action' => 'Notifies Lead Consultant to initiate direct consultation follow-up.',
            ],
        ];

        return [
            'instantiation_mode' => 'Automatic upon Acceptance',
            'lead_days' => 15,
            'internal_reminder_days' => 3,
            'triggers' => $triggers,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | TAB 9: TERMS
    |--------------------------------------------------------------------------
    */
    protected function buildTermsTab(Deal $deal, ?DealProposal $proposal, array $lineItems, array $totals): array
    {
        $specificTerms = []; $seen = [];
        $productCatalog = app(ProductCatalogConfiguration::class)->forDeal($deal, $proposal);
        foreach ($productCatalog['services'] ?? [] as $service) {
            foreach ($service['terms'] as $term) {
                $key = 'service|'.hash('sha256', strtolower(trim($term['title'])).'|'.trim($term['content']));
                if (isset($seen[$key])) { continue; }
                $seen[$key] = true;
                $specificTerms[] = ['item_name' => $service['name'], 'title' => $term['title'], 'content' => $term['content']];
            }
        }
        foreach ($productCatalog['terms'] as $term) {
            $specificTerms[] = ['item_name' => 'Product Catalog', 'title' => $term['title'], 'content' => $term['content']];
        }

        $standardTerms = [
            [
                'title' => '1. Acceptance, Commencement, and Retainer Terms',
                'paragraphs' => [
                    'Work commences upon formal acceptance of this proposal, subject to the agreed payment terms.',
                ],
            ],
            [
                'title' => "2. Client Responsibilities & Timely Information",
                'paragraphs' => [
                    "To ensure smooth and timely execution, the client agrees to provide authentic, accurate, and complete information, documents, and corporate records required by John Kelly & Company and relevant government agencies.",
                    "Review and provide prompt approval or feedback on draft applications, board resolutions, or financial schedules within three (3) business days from notice.",
                ],
            ],
            [
                'title' => "3. Government Agency Assessments and Outcome Disclaimers",
                'paragraphs' => [
                    "The client acknowledges that all sales figures, tax declarations, and financial statements submitted to government units are provided by the client or derived from client-provided records. John Kelly & Company does not alter, underdeclare, or manipulate figures for assessment purposes.",
                    "All assessments, charges, or statutory processing timelines imposed by regulatory bodies (BIR, SEC, LGU) are determined solely by the respective authorities and are outside our direct control.",
                ],
            ],
            [
                'title' => "4. Settlement of Government and Reimbursable Fees",
                'paragraphs' => [
                    "Official government filing fees, statutory penalties, and approved third-party disbursements are separate from professional consulting fees and shall be reimbursed by the client upon presentation of official receipts or assessments within three (3) calendar days.",
                ],
            ],
            [
                'title' => '5. Confidentiality and Professional Standards',
                'paragraphs' => [
                    "All financial data, corporate resolutions, and proprietary information shared in connection with this engagement shall be maintained in strict confidentiality and processed in accordance with the Data Privacy Act of 2012.",
                ],
            ],
        ];

        return [
            'payment_terms_text' => $proposal?->terms ?: ($deal->payment_terms ?: 'Payment terms require confirmation.'),
            'specific_terms' => $specificTerms,
            'conflicts' => $productCatalog['conflicts'],
            'standard_terms' => $standardTerms,
        ];
    }

    private function normalizeCatalogList(mixed $value): string
    {
        if (is_array($value)) {
            return collect($value)->flatten()->filter(fn ($part): bool => is_scalar($part) && filled($part))
                ->map(fn ($part): string => trim((string) $part))
                ->unique()
                ->implode(', ');
        }

        if (! is_string($value) || trim($value) === '') {
            return '';
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return $this->normalizeCatalogList($decoded);
        }

        return trim($value);
    }

    /**
     * Helper to normalize raw string lists or arrays.
     */
    protected function normalizeList(mixed $val): array
    {
        if (empty($val)) {
            return [];
        }
        if (is_array($val)) {
            return array_values(array_filter(array_map('trim', $val)));
        }
        if (is_string($val)) {
            $trimmed = trim($val);
            if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    return array_values(array_filter(array_map('trim', $decoded)));
                }
            }
            return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $trimmed, -1, PREG_SPLIT_NO_EMPTY))));
        }
        return [];
    }

    /**
     * Helper to split multiline strings into clean array of lines.
     */
    protected function splitLines(?string $val): array
    {
        if (empty($val)) return [];
        return array_values(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", $val)), fn($line) => filled($line)));
    }
}

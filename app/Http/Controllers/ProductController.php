<?php

namespace App\Http\Controllers;

use App\Models\CatalogChangeRequest;
use App\Models\Product;
use App\Models\ProductCustomField;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    private const REQUIREMENT_CATEGORIES = [
        'SOLE / NATURAL PERSON / INDIVIDUAL',
        'JURIDICAL ENTITY (Corporation / OPC / Partnership / Cooperative)',
        'Other',
    ];

    private const DEFAULT_REQUIREMENT_GROUPS = [
        'individual' => [
            'Valid ID',
            'DTI Registration',
        ],
        'juridical' => [
            'SEC Registration',
            'GIS',
            'Articles of Incorporation',
        ],
        'other' => [
            'Special Permit',
        ],
    ];

    public function index(Request $request): View
    {
        $this->ensureSeededProducts();
        $isAdminReviewer = in_array((string) ($request->user()?->role ?? ''), ['Admin', 'SuperAdmin'], true);

        $search = trim((string) $request->query('search', ''));
        $statusFilter = (string) $request->query('status', $request->query('active', 'all'));
        $categoryFilter = (string) $request->query('category', 'all');
        $productTypeFilter = (string) $request->query('product_type', 'all');
        $inventoryTypeFilter = (string) $request->query('inventory_type', 'all');
        $ownerFilter = (string) $request->query('owner_id', 'all');
        $serviceAreaFilter = (string) $request->query('service_area', 'all');
        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [5, 10, 25, 50], true) ? $perPage : 10;

        $owners = $this->ownerOptions();
        $ownerMap = collect($owners)->keyBy('id');
        $serviceOptions = $this->serviceOptions();
        $categoryOptions = $this->categoryOptions();
        $productTypeOptions = $this->productTypeOptions();
        $inventoryTypeOptions = $this->inventoryTypeOptions();
        $productAreaOptions = collect($this->productAreaOptions())
            ->reject(fn ($option) => $option === 'Others')
            ->values()
            ->all();

        $customFields = ProductCustomField::query()
            ->orderBy('sort_order')
            ->orderBy('field_name')
            ->get();

        $productsQuery = Product::query()->orderByDesc('created_at');

        if ($search !== '') {
            $productsQuery->where(function ($query) use ($search) {
                $query
                    ->where('product_name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%')
                    ->orWhere('product_type', 'like', '%'.$search.'%')
                    ->orWhere('category', 'like', '%'.$search.'%')
                    ->orWhere('created_by', 'like', '%'.$search.'%');
            });
        }

        if (in_array($statusFilter, ['Pending Approval', 'Active', 'Inactive', 'Rejected', 'Archived'], true)) {
            $productsQuery->where('status', $statusFilter);
        } else {
            $statusFilter = 'all';
        }

        if (in_array($categoryFilter, $categoryOptions, true)) {
            $productsQuery->where('category', $categoryFilter);
        } else {
            $categoryFilter = 'all';
        }

        if (in_array($productTypeFilter, $productTypeOptions, true)) {
            $productsQuery->where('product_type', $productTypeFilter);
        } else {
            $productTypeFilter = 'all';
        }

        if (in_array($inventoryTypeFilter, $inventoryTypeOptions, true)) {
            $productsQuery->where('inventory_type', $inventoryTypeFilter);
        } else {
            $inventoryTypeFilter = 'all';
        }

        if ($ownerFilter !== 'all' && ctype_digit($ownerFilter)) {
            $productsQuery->where('owner_id', (int) $ownerFilter);
        } else {
            $ownerFilter = 'all';
        }

        if (in_array($serviceAreaFilter, $productAreaOptions, true)) {
            $productsQuery->whereJsonContains('product_area', $serviceAreaFilter);
        } else {
            $serviceAreaFilter = 'all';
        }

        $totalProducts = (clone $productsQuery)->count();
        $totalPages = max((int) ceil(max($totalProducts, 1) / $perPage), 1);
        $currentPage = min(max((int) $request->query('page', 1), 1), $totalPages);
        $products = $productsQuery
            ->forPage($currentPage, $perPage)
            ->get()
            ->map(function (Product $product) use ($ownerMap) {
                $product->owner_name = data_get($ownerMap->get($product->owner_id), 'name', $product->created_by);
                $product->linked_service_names = $this->resolveLinkedServiceNames($product);
                return $product;
            });

        $from = $totalProducts > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
        $to = min($from + $perPage - 1, $totalProducts);
        $defaultOwnerId = (int) old('owner_id', ($owners[0]['id'] ?? 1001));
        $summary = [
            'pending' => Product::query()->where('status', 'Pending Approval')->count()
                + CatalogChangeRequest::query()->where('module', 'product')->where('status', 'Pending Approval')->count(),
            'active' => Product::query()->where('status', 'Active')->count(),
            'rejected' => Product::query()->where('status', 'Rejected')->count(),
        ];
        $pendingChangeRequests = CatalogChangeRequest::query()
            ->with(['submitter', 'reviewer'])
            ->where('module', 'product')
            ->where('status', 'Pending Approval')
            ->latest('updated_at')
            ->get();
        $pendingRequestMap = $pendingChangeRequests->keyBy('record_id');

        return view('products.index', [
            'products' => $products,
            'search' => $search,
            'activeFilter' => $statusFilter,
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
                'category' => $categoryFilter,
                'product_type' => $productTypeFilter,
                'inventory_type' => $inventoryTypeFilter,
                'owner_id' => $ownerFilter,
                'service_area' => $serviceAreaFilter,
                'per_page' => $perPage,
            ],
            'totalProducts' => $totalProducts,
            'perPage' => $perPage,
            'from' => $from,
            'to' => $to,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'owners' => $owners,
            'defaultOwnerId' => $defaultOwnerId,
            'customFields' => $customFields,
            'fieldTypes' => collect($this->fieldTypes()),
            'categoryOptions' => $categoryOptions,
            'productTypeOptions' => $productTypeOptions,
            'productAreaOptions' => $this->productAreaOptions(),
            'productAreaFilterOptions' => $productAreaOptions,
            'pricingTypeOptions' => $this->pricingTypeOptions(),
            'inventoryTypeOptions' => $inventoryTypeOptions,
            'statusOptions' => $this->statusOptions(),
            'unitOptions' => $this->unitOptions(),
            'serviceOptions' => $serviceOptions,
            'requirementTemplateDefaults' => [
                'individual' => implode(PHP_EOL, self::DEFAULT_REQUIREMENT_GROUPS['individual']),
                'juridical' => implode(PHP_EOL, self::DEFAULT_REQUIREMENT_GROUPS['juridical']),
                'other' => implode(PHP_EOL, self::DEFAULT_REQUIREMENT_GROUPS['other']),
            ],
            'isAdminReviewer' => $isAdminReviewer,
            'summary' => $summary,
            'pendingChangeRequests' => $pendingChangeRequests,
            'pendingRequestMap' => $pendingRequestMap,
            'nextSku' => old('sku', $this->generateProductSku()),
            'drawerMeta' => [
                'createdBy' => $this->currentUserDisplay(),
                'createdAtDisplay' => now()->format('F j, Y g:i A'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$customFields, $owner, $validated, $resolvedProductType, $resolvedCategory, $resolvedInventoryType] = $this->validatedProductPayload($request);

        Product::create([
            'product_id' => $this->generateProductId(),
            'product_name' => $validated['product_name'],
            'product_type' => $resolvedProductType,
            'linked_service_id' => $this->primaryLinkedServiceId($validated['linked_service_ids'] ?? []),
            'linked_service_ids' => $this->normalizeLinkedServiceIds($validated['linked_service_ids'] ?? []),
            'deal_id' => $validated['deal_id'] ?? null,
            'product_area' => array_values($validated['product_area']),
            'product_area_other' => in_array('Others', $validated['product_area'], true) ? ($validated['product_area_other'] ?? null) : null,
            'product_description' => $validated['product_description'],
            'product_inclusions' => $this->normalizeBulletLines($validated['product_inclusions'] ?? null),
            'requirements' => $this->normalizeRequirementsByGroups(
                (string) ($validated['requirements_individual'] ?? ''),
                (string) ($validated['requirements_juridical'] ?? ''),
                (string) ($validated['requirements_other'] ?? ''),
                (string) ($validated['requirements'] ?? ''),
                $validated['requirement_category'] ?? null,
            ),
            'requirement_category' => $this->primaryRequirementCategory(
                $this->normalizeRequirementsByGroups(
                    (string) ($validated['requirements_individual'] ?? ''),
                    (string) ($validated['requirements_juridical'] ?? ''),
                    (string) ($validated['requirements_other'] ?? ''),
                    (string) ($validated['requirements'] ?? ''),
                    $validated['requirement_category'] ?? null,
                )
            ),
            'category' => $resolvedCategory,
            'pricing_type' => $validated['pricing_type'],
            'price' => $validated['price'],
            'cost' => $validated['cost'] ?? null,
            'is_discountable' => $request->boolean('is_discountable'),
            'tax_type' => $validated['tax_type'],
            'tax_treatment' => $validated['tax_treatment'] ?? 'Tax Exclusive',
            'sku' => $validated['sku'] ?: $this->generateProductSku(),
            'inventory_type' => $resolvedInventoryType,
            'stock_qty' => $resolvedInventoryType === 'Inventory' ? ($validated['stock_qty'] ?? null) : null,
            'unit' => $validated['unit'] ?? null,
            'status' => 'Pending Approval',
            'owner_id' => (int) $validated['owner_id'],
            'created_by' => $this->currentUserDisplay(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'approved_by' => null,
            'approved_at' => null,
            'custom_field_values' => $this->extractCustomFieldValues($request, $customFields),
        ]);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product created and submitted for admin approval.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $product = Product::query()->where('product_id', $id)->firstOrFail();
        [$customFields, $owner, $validated, $resolvedProductType, $resolvedCategory, $resolvedInventoryType] = $this->validatedProductPayload($request, $product);
        $attributes = [
            'product_name' => $validated['product_name'],
            'product_type' => $resolvedProductType,
            'linked_service_id' => $this->primaryLinkedServiceId($validated['linked_service_ids'] ?? []),
            'linked_service_ids' => $this->normalizeLinkedServiceIds($validated['linked_service_ids'] ?? []),
            'deal_id' => $validated['deal_id'] ?? null,
            'product_area' => array_values($validated['product_area']),
            'product_area_other' => in_array('Others', $validated['product_area'], true) ? ($validated['product_area_other'] ?? null) : null,
            'product_description' => $validated['product_description'],
            'product_inclusions' => $this->normalizeBulletLines($validated['product_inclusions'] ?? null),
            'requirements' => $this->normalizeRequirementsByGroups(
                (string) ($validated['requirements_individual'] ?? ''),
                (string) ($validated['requirements_juridical'] ?? ''),
                (string) ($validated['requirements_other'] ?? ''),
                (string) ($validated['requirements'] ?? ''),
                $validated['requirement_category'] ?? null,
            ),
            'requirement_category' => $this->primaryRequirementCategory(
                $this->normalizeRequirementsByGroups(
                    (string) ($validated['requirements_individual'] ?? ''),
                    (string) ($validated['requirements_juridical'] ?? ''),
                    (string) ($validated['requirements_other'] ?? ''),
                    (string) ($validated['requirements'] ?? ''),
                    $validated['requirement_category'] ?? null,
                )
            ),
            'category' => $resolvedCategory,
            'pricing_type' => $validated['pricing_type'],
            'price' => $validated['price'],
            'cost' => $validated['cost'] ?? null,
            'is_discountable' => $request->boolean('is_discountable'),
            'tax_type' => $validated['tax_type'],
            'tax_treatment' => $validated['tax_treatment'] ?? 'Tax Exclusive',
            'sku' => $validated['sku'] ?: $product->sku ?: $this->generateProductSku(),
            'inventory_type' => $resolvedInventoryType,
            'stock_qty' => $resolvedInventoryType === 'Inventory' ? ($validated['stock_qty'] ?? null) : null,
            'unit' => $validated['unit'] ?? null,
            'owner_id' => (int) $validated['owner_id'],
            'custom_field_values' => $this->extractCustomFieldValues($request, $customFields),
        ];

        if ((string) $product->status === 'Pending Approval' && blank($product->approved_at)) {
            $product->update($attributes);

            return redirect()
                ->route('products.index')
                ->with('success', 'Pending product draft updated successfully.');
        }

        $this->upsertCatalogChangeRequest(
            module: 'product',
            recordId: (int) $product->id,
            recordPublicId: $product->product_id,
            recordName: $product->product_name,
            action: 'update',
            payload: $attributes,
            submittedBy: $request->user()?->id,
        );

        return redirect()
            ->route('products.index')
            ->with('success', 'Product update submitted for admin approval.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $product = Product::query()->where('product_id', $id)->firstOrFail();

        if ((string) $product->status === 'Pending Approval' && blank($product->approved_at)) {
            $product->delete();

            return redirect()
                ->route('products.index')
                ->with('success', 'Pending product deleted successfully.');
        }

        $this->upsertCatalogChangeRequest(
            module: 'product',
            recordId: (int) $product->id,
            recordPublicId: $product->product_id,
            recordName: $product->product_name,
            action: 'delete',
            payload: null,
            submittedBy: $request->user()?->id,
        );

        return redirect()
            ->route('products.index')
            ->with('success', 'Product delete request submitted for admin approval.');
    }

    public function approve(Request $request, string $id): RedirectResponse
    {
        $product = Product::query()->where('product_id', $id)->firstOrFail();

        if ((string) $product->status !== 'Pending Approval') {
            return back()
                ->with('success', 'This product review has already been resolved.');
        }

        $now = now();

        $product->forceFill([
            'status' => 'Active',
            'reviewed_by' => $request->user()?->name ?? 'System User',
            'reviewed_at' => $now,
            'approved_by' => $request->user()?->name ?? 'System User',
            'approved_at' => $now,
        ])->save();

        return back()
            ->with('success', 'Product approved successfully.');
    }

    public function reject(Request $request, string $id): RedirectResponse
    {
        $product = Product::query()->where('product_id', $id)->firstOrFail();

        if ((string) $product->status !== 'Pending Approval') {
            return back()
                ->with('success', 'This product review has already been resolved.');
        }

        $product->forceFill([
            'status' => 'Rejected',
            'reviewed_by' => $request->user()?->name ?? 'System User',
            'reviewed_at' => now(),
            'approved_by' => null,
            'approved_at' => null,
        ])->save();

        return back()
            ->with('success', 'Product rejected successfully.');
    }

    private function upsertCatalogChangeRequest(
        string $module,
        int $recordId,
        ?string $recordPublicId,
        ?string $recordName,
        string $action,
        ?array $payload,
        ?int $submittedBy,
    ): void {
        CatalogChangeRequest::query()
            ->updateOrCreate(
                [
                    'module' => $module,
                    'record_id' => $recordId,
                    'status' => 'Pending Approval',
                ],
                [
                    'record_public_id' => $recordPublicId,
                    'record_name' => $recordName,
                    'action' => $action,
                    'payload' => $payload,
                    'submitted_by' => $submittedBy,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'rejection_notes' => null,
                ]
            );
    }

    public function show(Request $request, string $id): View
    {
        $ownerMap = collect($this->ownerOptions())->keyBy('id');
        $product = Product::query()->where('product_id', $id)->firstOrFail();
        $product->owner_name = data_get($ownerMap->get($product->owner_id), 'name', $product->created_by);
        $product->linked_service_names = $this->resolveLinkedServiceNames($product);

        $tab = strtolower((string) $request->query('tab', 'timeline'));
        $allowedTabs = ['timeline', 'pipelines', 'files', 'tasks'];
        if (! in_array($tab, $allowedTabs, true)) {
            $tab = 'timeline';
        }

        return view('products.show', [
            'product' => $product,
            'tab' => $tab,
            'tabs' => [
                'timeline' => 'Timeline',
                'pipelines' => 'Pipelines',
                'files' => 'Files',
                'tasks' => 'Tasks',
            ],
            'timeline' => $this->mockTimeline($product),
            'pipelines' => [],
            'files' => [],
            'tasks' => [],
            'lastModifiedLabel' => 'Last Modified on '.$product->updated_at?->format('M d, h:i A'),
            'customFields' => ProductCustomField::query()->orderBy('sort_order')->orderBy('field_name')->get(),
        ]);
    }

    public function changeOwner(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'selected_products' => ['required', 'array', 'min:1'],
            'selected_products.*' => ['required', 'string'],
            'owner_id' => ['required', 'integer'],
        ]);

        $owner = collect($this->ownerOptions())->firstWhere('id', (int) $validated['owner_id']);
        if (! $owner) {
            return redirect()
                ->route('products.index')
                ->withErrors(['owner_id' => 'Please select a valid owner.']);
        }

        Product::query()
            ->whereIn('product_id', $validated['selected_products'])
            ->update([
                'owner_id' => $owner['id'],
                'updated_at' => now(),
            ]);

        return redirect()->route('products.index')->with('success', 'Product owner updated successfully.');
    }

    public function storeCustomField(Request $request): RedirectResponse
    {
        $allowedTypes = collect($this->fieldTypes())->pluck('value')->all();

        $validated = $request->validate([
            'field_type' => ['required', 'string', 'in:'.implode(',', $allowedTypes)],
            'field_name' => ['required', 'string', 'max:80'],
            'default_value' => ['nullable', 'string', 'max:255'],
            'required' => ['nullable', 'boolean'],
            'lookup_module' => ['nullable', 'string', 'in:deals,company,contacts,products'],
            'options' => ['nullable', 'array'],
            'options.*' => ['nullable', 'string', 'max:100'],
        ]);

        $fieldName = trim((string) $validated['field_name']);
        $fieldType = (string) $validated['field_type'];

        if (ProductCustomField::query()->whereRaw('LOWER(field_name) = ?', [Str::lower($fieldName)])->exists()) {
            return back()->withErrors(['field_name' => 'Field name already exists for Products.'])->withInput();
        }

        $fieldKey = $this->uniqueCustomFieldKey($fieldName);
        $options = collect($validated['options'] ?? [])
            ->map(fn ($option) => trim((string) $option))
            ->filter()
            ->values()
            ->all();

        if ($fieldType === 'picklist' && count($options) === 0) {
            return back()->withErrors(['options' => 'Picklist fields need at least one option.'])->withInput();
        }

        ProductCustomField::create([
            'field_type' => $fieldType,
            'field_name' => $fieldName,
            'field_key' => $fieldKey,
            'is_required' => (bool) ($validated['required'] ?? false),
            'options' => $fieldType === 'picklist' ? $options : [],
            'lookup_module' => $fieldType === 'lookup' ? ($validated['lookup_module'] ?? null) : null,
            'default_value' => $this->normalizedDefaultValue($fieldType, $validated['default_value'] ?? null),
            'sort_order' => ((int) ProductCustomField::query()->max('sort_order')) + 1,
        ]);

        return redirect()->route('products.index')->with('success', 'Custom field created successfully.');
    }

    private function validationRules(Collection $customFields, ?Product $product = null): array
    {
        $productTypeOptions = $this->productTypeOptions();
        $productAreaOptions = $this->productAreaOptions();
        $categoryOptions = $this->categoryOptions();
        $pricingTypeOptions = $this->pricingTypeOptions();
        $inventoryTypeOptions = $this->inventoryTypeOptions();
        $unitOptions = $this->unitOptions();

        $rules = [
            'product_name' => ['required', 'string', 'max:150'],
            'product_type' => ['required', 'string', Rule::in($productTypeOptions)],
            'product_type_other' => ['nullable', 'string', 'max:100'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product?->id)],
            'linked_service_ids' => ['nullable', 'array'],
            'linked_service_ids.*' => ['integer', 'exists:services,id'],
            'deal_id' => ['nullable', 'integer', 'exists:deals,id'],
            'product_area' => ['required', 'array', 'min:1'],
            'product_area.*' => ['required', 'string', Rule::in($productAreaOptions)],
            'product_area_other' => ['nullable', 'string', 'max:150'],
            'product_description' => ['required', 'string', 'max:5000'],
            'product_inclusions' => ['nullable', 'string', 'max:5000'],
            'requirement_category' => ['nullable', 'string', Rule::in(self::REQUIREMENT_CATEGORIES)],
            'requirements' => ['nullable', 'string'],
            'requirements_individual' => ['nullable', 'string'],
            'requirements_juridical' => ['nullable', 'string'],
            'requirements_other' => ['nullable', 'string'],
            'category' => ['required', 'string', Rule::in($categoryOptions)],
            'category_other' => ['nullable', 'string', 'max:100'],
            'pricing_type' => ['required', 'string', Rule::in($pricingTypeOptions)],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'is_discountable' => ['nullable', 'boolean'],
            'tax_type' => ['required', 'string', Rule::in($this->taxTypeOptions())],
            'tax_treatment' => ['required', 'string', Rule::in($this->taxTreatmentOptions())],
            'inventory_type' => ['required', 'string', Rule::in($inventoryTypeOptions)],
            'inventory_type_other' => ['nullable', 'string', 'max:100'],
            'stock_qty' => ['nullable', 'integer', 'min:0'],
            'unit' => ['nullable', 'string', Rule::in($unitOptions)],
            'owner_id' => ['required', 'integer'],
        ];

        foreach ($customFields as $field) {
            $fieldKey = 'custom_fields.'.$field->field_key;
            $fieldRules = [$field->is_required ? 'required' : 'nullable'];

            switch ($field->field_type) {
                case 'numerical':
                case 'currency':
                    $fieldRules[] = 'numeric';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                default:
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:255';
                    break;
            }

            $rules[$fieldKey] = $fieldRules;
        }

        return $rules;
    }

    private function validatedProductPayload(Request $request, ?Product $product = null): array
    {
        $customFields = ProductCustomField::query()
            ->orderBy('sort_order')
            ->orderBy('field_name')
            ->get();

        $owner = collect($this->ownerOptions())->firstWhere('id', (int) $request->input('owner_id'));
        if (! $owner) {
            throw ValidationException::withMessages([
                'owner_id' => 'Please select a valid owner.',
            ]);
        }

        $validated = $request->validate($this->validationRules($customFields, $product));

        Validator::make([], [])->after(function ($validator) use ($validated) {
            if (in_array('Others', $validated['product_area'] ?? [], true) && blank($validated['product_area_other'] ?? null)) {
                $validator->errors()->add('product_area_other', 'Other Product Area is required when Others is selected.');
            }

            if (($validated['product_type'] ?? null) === 'Other' && blank($validated['product_type_other'] ?? null)) {
                $validator->errors()->add('product_type_other', 'Custom Product Type is required when Other is selected.');
            }

            if (($validated['category'] ?? null) === 'Other' && blank($validated['category_other'] ?? null)) {
                $validator->errors()->add('category_other', 'Custom Category is required when Other is selected.');
            }

            if (($validated['inventory_type'] ?? null) === 'Other' && blank($validated['inventory_type_other'] ?? null)) {
                $validator->errors()->add('inventory_type_other', 'Custom Inventory Type is required when Other is selected.');
            }

            $requirements = collect([
                trim((string) ($validated['requirements'] ?? '')),
                trim((string) ($validated['requirements_individual'] ?? '')),
                trim((string) ($validated['requirements_juridical'] ?? '')),
                trim((string) ($validated['requirements_other'] ?? '')),
            ])->filter(fn ($value) => $value !== '');

            if ($requirements->isNotEmpty()
                && blank($validated['requirement_category'] ?? null)
                && blank($validated['requirements_individual'] ?? null)
                && blank($validated['requirements_juridical'] ?? null)
                && blank($validated['requirements_other'] ?? null)
            ) {
                $validator->errors()->add('requirement_category', 'Requirement Category is required when requirements are provided.');
            }

            $resolvedInventoryType = $this->resolveSelectableValue(
                $validated['inventory_type'] ?? null,
                $validated['inventory_type_other'] ?? null
            );

            if ($resolvedInventoryType === 'Inventory' && blank($validated['stock_qty'] ?? null)) {
                $validator->errors()->add('stock_qty', 'Stock Quantity is required for inventory-managed products.');
            }
        })->validate();

        $resolvedProductType = $this->resolveSelectableValue(
            $validated['product_type'] ?? null,
            $validated['product_type_other'] ?? null
        );
        $resolvedCategory = $this->resolveSelectableValue(
            $validated['category'] ?? null,
            $validated['category_other'] ?? null
        );
        $resolvedInventoryType = $this->resolveSelectableValue(
            $validated['inventory_type'] ?? null,
            $validated['inventory_type_other'] ?? null
        );

        return [$customFields, $owner, $validated, $resolvedProductType, $resolvedCategory, $resolvedInventoryType];
    }

    private function ownerOptions(): array
    {
        $users = User::query()
            ->select(['id', 'name', 'email'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email ?: strtolower(str_replace(' ', '.', $user->name)).'@example.com',
            ])
            ->values()
            ->all();

        if (! empty($users)) {
            return $users;
        }

        return [
            ['id' => 1001, 'name' => 'Shine Florence Padillo', 'email' => 'shinepadi@gmail.com'],
            ['id' => 1002, 'name' => 'John Admin', 'email' => 'john.admin@example.com'],
            ['id' => 1003, 'name' => 'Maria Santos', 'email' => 'maria.santos@example.com'],
            ['id' => 1004, 'name' => 'Juan Dela Cruz', 'email' => 'juan.delacruz@example.com'],
        ];
    }

    private function fieldTypes(): array
    {
        return [
            ['value' => 'picklist', 'label' => 'Picklist', 'icon' => 'fa-list'],
            ['value' => 'text', 'label' => 'Text', 'icon' => 'fa-font'],
            ['value' => 'numerical', 'label' => 'Numerical', 'icon' => 'fa-hashtag'],
            ['value' => 'currency', 'label' => 'Currency', 'icon' => 'fa-peso-sign'],
            ['value' => 'date', 'label' => 'Date', 'icon' => 'fa-calendar-days'],
            ['value' => 'checkbox', 'label' => 'Checkbox', 'icon' => 'fa-square-check'],
            ['value' => 'user', 'label' => 'User', 'icon' => 'fa-user'],
            ['value' => 'lookup', 'label' => 'Lookup', 'icon' => 'fa-link'],
        ];
    }

    private function normalizedDefaultValue(string $fieldType, mixed $defaultValue): string
    {
        $value = trim((string) ($defaultValue ?? ''));

        if ($fieldType === 'checkbox') {
            return in_array(Str::lower($value), ['1', 'yes', 'true', 'checked'], true) ? '1' : '0';
        }

        return $value;
    }

    private function extractCustomFieldValues(Request $request, Collection $customFields): array
    {
        $values = [];
        $submitted = (array) $request->input('custom_fields', []);

        foreach ($customFields as $field) {
            $value = $submitted[$field->field_key] ?? $field->default_value;
            if ($field->field_type === 'checkbox') {
                $value = in_array((string) $value, ['1', 'true', 'yes', 'on'], true) ? '1' : '0';
            }
            $values[$field->field_key] = $value;
        }

        return $values;
    }

    private function generateProductId(): string
    {
        do {
            $productId = (string) random_int(10000, 99999);
        } while (Product::query()->where('product_id', $productId)->exists());

        return $productId;
    }

    private function generateProductSku(): string
    {
        $prefix = 'PRD-REG-';

        $nextNumber = DB::transaction(function () use ($prefix): int {
            $latestSku = Product::query()
                ->where('sku', 'like', $prefix.'%')
                ->whereNotNull('sku')
                ->orderByDesc('sku')
                ->lockForUpdate()
                ->value('sku');

            if (! is_string($latestSku) || preg_match('/^PRD-REG-(\d{3})$/', $latestSku, $matches) !== 1) {
                return 1;
            }

            return ((int) $matches[1]) + 1;
        });

        return $prefix.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }

    private function uniqueCustomFieldKey(string $fieldName): string
    {
        $keyBase = Str::slug($fieldName, '_');
        if ($keyBase === '') {
            $keyBase = 'custom_field';
        }

        $key = 'custom_'.$keyBase;
        $suffix = 1;

        while (ProductCustomField::query()->where('field_key', $key)->exists()) {
            $suffix++;
            $key = 'custom_'.$keyBase.'_'.$suffix;
        }

        return $key;
    }

    private function normalizeBulletLines(?string $value): ?string
    {
        $lines = collect(preg_split('/\r\n|\r|\n/', (string) $value))
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->map(fn ($line) => Str::startsWith($line, '•') ? $line : '• '.$line)
            ->values();

        return $lines->isEmpty() ? null : $lines->implode(PHP_EOL);
    }

    private function normalizeRequirementsByGroups(string $individualText, string $juridicalText, string $otherText, string $legacyRequirementsText = '', ?string $legacyCategory = null): ?array
    {
        $groups = [
            'individual' => $this->normalizeRequirementLines($individualText),
            'juridical' => $this->normalizeRequirementLines($juridicalText),
            'other' => $this->normalizeRequirementLines($otherText),
        ];

        $groups = collect($groups)
            ->filter(fn (array $lines): bool => count($lines) > 0)
            ->all();

        if ($groups === [] && filled($legacyRequirementsText) && filled($legacyCategory)) {
            $legacyLines = $this->normalizeRequirementLines($legacyRequirementsText);
            $legacyKey = match ($legacyCategory) {
                'SOLE / NATURAL PERSON / INDIVIDUAL' => 'individual',
                'JURIDICAL ENTITY (Corporation / OPC / Partnership / Cooperative)' => 'juridical',
                default => 'other',
            };

            if ($legacyLines !== []) {
                $groups[$legacyKey] = $legacyLines;
            }
        }

        if ($groups === []) {
            return null;
        }

        return [
            'groups' => $groups,
        ];
    }

    private function normalizeRequirementLines(string $requirementsText): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $requirementsText) ?: [])
            ->map(fn ($line) => trim(preg_replace('/^[^A-Za-z0-9]+/u', '', (string) $line) ?? ''))
            ->filter()
            ->values()
            ->all();
    }

    private function primaryRequirementCategory(?array $requirements): ?string
    {
        $groups = collect($requirements['groups'] ?? []);

        if (($groups['individual'] ?? []) !== []) {
            return 'SOLE / NATURAL PERSON / INDIVIDUAL';
        }

        if (($groups['juridical'] ?? []) !== []) {
            return 'JURIDICAL ENTITY (Corporation / OPC / Partnership / Cooperative)';
        }

        if (($groups['other'] ?? []) !== []) {
            return 'Other';
        }

        return null;
    }

    private function productTypeOptions(): array
    {
        return ['Service', 'Bundle', 'Package', 'Physical Product', 'Digital Product', 'Other'];
    }

    private function productAreaOptions(): array
    {
        $defaults = [
            'Corporate & Regulatory Advisory',
            'Governance & Policy Advisory',
            'People & Talent Solutions',
            'Strategic Situations Advisory',
            'Accounting & Compliance Advisory',
            'Business Strategy & Process Advisory',
            'Learning & Capability Development',
            'Others',
            'None',
        ];

        $serviceAreas = Service::query()
            ->whereNotNull('service_area')
            ->pluck('service_area')
            ->flatten(1)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return collect(array_merge($defaults, $serviceAreas))
            ->unique()
            ->values()
            ->all();
    }

    private function categoryOptions(): array
    {
        return [
            'Professional Fees',
            'Consulting Revenue',
            'Accounting Services',
            'Tax Services',
            'Corporate Services',
            'HR Services',
            'Training & Development',
            'Other Income',
            'Other',
        ];
    }

    private function pricingTypeOptions(): array
    {
        return ['Fixed', 'Variable', 'Tiered', 'Subscription'];
    }

    private function taxTypeOptions(): array
    {
        return ['VAT', 'Non-VAT', 'Zero-rated', 'Exempt'];
    }

    private function taxTreatmentOptions(): array
    {
        return ['Tax Inclusive', 'Tax Exclusive'];
    }

    private function inventoryTypeOptions(): array
    {
        return ['Non-Inventory', 'Inventory', 'Service', 'Other'];
    }

    private function statusOptions(): array
    {
        return ['Pending Approval', 'Active', 'Inactive', 'Rejected', 'Archived'];
    }

    private function unitOptions(): array
    {
        return ['Unit', 'Package', 'Set', 'Hour', 'Project'];
    }

    private function currentUserDisplay(): string
    {
        return User::query()->orderBy('id')->value('name') ?: 'Admin User';
    }

    private function ensureSeededProducts(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $owner = collect($this->ownerOptions())->first();

        foreach ($this->defaultProductCatalog() as $serviceArea => $products) {
            foreach ($products as $productName) {
                $existingProduct = Product::query()
                    ->where('product_name', $productName)
                    ->first();

                if ($existingProduct) {
                    $existingAreas = collect($existingProduct->product_area ?? [])
                        ->map(fn ($value) => trim((string) $value))
                        ->filter()
                        ->values();
                    $didChange = false;

                    if (! $existingAreas->contains($serviceArea)) {
                        $existingProduct->product_area = $existingAreas
                            ->push($serviceArea)
                            ->unique()
                            ->values()
                            ->all();
                        $didChange = true;
                    }

                    if (in_array((string) $existingProduct->status, ['Pending Approval', 'Rejected', 'Inactive', 'Archived'], true)) {
                        $existingProduct->status = 'Active';
                        $didChange = true;
                    }

                    if (blank($existingProduct->reviewed_by)) {
                        $existingProduct->reviewed_by = $owner['name'] ?? 'John Admin';
                        $didChange = true;
                    }

                    if (blank($existingProduct->reviewed_at)) {
                        $existingProduct->reviewed_at = now();
                        $didChange = true;
                    }

                    if (blank($existingProduct->approved_by)) {
                        $existingProduct->approved_by = $owner['name'] ?? 'John Admin';
                        $didChange = true;
                    }

                    if (blank($existingProduct->approved_at)) {
                        $existingProduct->approved_at = now();
                        $didChange = true;
                    }

                    if ($didChange) {
                        $existingProduct->save();
                    }

                    continue;
                }

                Product::query()->create([
                    'product_id' => $this->generateProductId(),
                    'product_name' => $productName,
                    'product_type' => 'Service',
                    'linked_service_id' => null,
                    'linked_service_ids' => [],
                    'product_area' => [$serviceArea],
                    'product_area_other' => null,
                    'product_description' => $productName,
                    'product_inclusions' => null,
                    'category' => $this->defaultCategoryForServiceArea($serviceArea),
                    'pricing_type' => 'Fixed',
                    'price' => 350,
                    'cost' => null,
                    'is_discountable' => false,
                    'tax_type' => 'VAT',
                    'tax_treatment' => 'Tax Exclusive',
                    'sku' => $this->generateProductSku(),
                    'inventory_type' => 'Service',
                    'stock_qty' => null,
                    'unit' => 'Project',
                    'status' => 'Active',
                    'owner_id' => $owner['id'] ?? null,
                    'created_by' => $owner['name'] ?? 'John Admin',
                    'reviewed_by' => $owner['name'] ?? 'John Admin',
                    'reviewed_at' => now(),
                    'approved_by' => $owner['name'] ?? 'John Admin',
                    'approved_at' => now(),
                    'custom_field_values' => [],
                ]);
            }
        }

        return;

        Product::query()->create([
            'product_id' => $this->generateProductId(),
            'product_name' => 'Business Registration Package',
            'product_type' => 'Service',
            'linked_service_id' => null,
            'linked_service_ids' => [],
            'product_area' => ['None'],
            'product_area_other' => null,
            'product_description' => 'End-to-end business registration service for new clients.',
            'product_inclusions' => "• SEC/DTI filing assistance\n• Basic compliance checklist",
            'category' => 'Corporate Services',
            'pricing_type' => 'Fixed',
            'price' => 5000,
            'cost' => 2500,
            'is_discountable' => true,
            'tax_type' => 'VAT',
            'tax_treatment' => 'Tax Exclusive',
            'sku' => $this->generateProductSku(),
            'inventory_type' => 'Service',
            'stock_qty' => null,
            'unit' => 'Project',
            'status' => 'Pending Approval',
            'owner_id' => $owner['id'] ?? null,
            'created_by' => $owner['name'] ?? 'John Admin',
            'custom_field_values' => [],
        ]);

        Product::query()->create([
            'product_id' => $this->generateProductId(),
            'product_name' => 'Accounting Cleanup Package',
            'product_type' => 'Service',
            'linked_service_id' => null,
            'linked_service_ids' => [],
            'product_area' => ['Accounting & Compliance Advisory'],
            'product_area_other' => null,
            'product_description' => 'Accounting records cleanup and reconciliation support.',
            'product_inclusions' => "• Ledger review\n• Reconciliation summary",
            'category' => 'Accounting Services',
            'pricing_type' => 'Fixed',
            'price' => 2500,
            'cost' => 1200,
            'is_discountable' => false,
            'tax_type' => 'VAT',
            'tax_treatment' => 'Tax Exclusive',
            'sku' => $this->generateProductSku(),
            'inventory_type' => 'Service',
            'stock_qty' => null,
            'unit' => 'Project',
            'status' => 'Pending Approval',
            'owner_id' => $owner['id'] ?? null,
            'created_by' => $owner['name'] ?? 'John Admin',
            'custom_field_values' => [],
        ]);
    }

    private function mockTimeline(Product $product): array
    {
        $owner = $product->created_by ?: 'John Admin';

        return [
            [
                'icon' => 'fa-box-open',
                'title' => 'Product added',
                'description' => $product->product_name,
                'user_name' => $owner,
                'created_at' => optional($product->created_at)->format('M d, Y h:i A'),
            ],
            [
                'icon' => 'fa-pen',
                'title' => 'Product updated',
                'description' => 'Latest product configuration saved.',
                'user_name' => $owner,
                'created_at' => optional($product->updated_at)->format('M d, Y h:i A'),
            ],
        ];
    }

    private function serviceOptions(): array
    {
        if (! Schema::hasTable('services')) {
            return [];
        }

        return Service::query()
            ->orderBy('service_name')
            ->get(['id', 'service_id', 'service_name', 'service_area'])
            ->map(fn (Service $service): array => [
                'id' => $service->id,
                'name' => trim(implode(' | ', array_filter([
                    $service->service_name,
                    $service->service_id,
                ]))),
                'service_name' => $service->service_name,
                'service_id' => $service->service_id,
                'service_area' => array_values(array_filter(array_map(
                    fn ($value) => trim((string) $value),
                    (array) ($service->service_area ?? [])
                ))),
            ])
            ->values()
            ->all();
    }

    private function normalizeLinkedServiceIds(array $linkedServiceIds): array
    {
        return collect($linkedServiceIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function primaryLinkedServiceId(array $linkedServiceIds): ?int
    {
        $normalized = $this->normalizeLinkedServiceIds($linkedServiceIds);

        return $normalized[0] ?? null;
    }

    private function resolveLinkedServiceNames(Product $product): array
    {
        $linkedServiceIds = $this->normalizeLinkedServiceIds(
            (array) ($product->linked_service_ids ?? ($product->linked_service_id ? [$product->linked_service_id] : []))
        );

        if ($linkedServiceIds === []) {
            return [];
        }

        return Service::query()
            ->whereIn('id', $linkedServiceIds)
            ->orderBy('service_name')
            ->get(['service_name', 'service_id'])
            ->map(fn (Service $service): string => trim(implode(' | ', array_filter([
                $service->service_name,
                $service->service_id,
            ]))))
            ->values()
            ->all();
    }

    private function resolveSelectableValue(?string $selectedValue, ?string $otherValue): string
    {
        $selected = trim((string) $selectedValue);
        $other = trim((string) $otherValue);

        if ($other !== '') {
            return $other;
        }

        return $selected;
    }

    private function defaultProductCatalog(): array
    {
        return [
            'Corporate & Regulatory Advisory' => [
                'Printing',
                'Photocopy',
                'Drafting of Letters',
                'Drafting of Notices',
                'Drafting of Demand Letters',
                'Drafting of Emails (Formal / Business)',
            ],
            'Accounting & Compliance Advisory' => [
                'Archive Retrieval',
                'Digital Archive Copy',
                'Drafting of Responses to Letters / Notices',
                'Drafting of Memorandum (Internal / External)',
                'Drafting of Certifications',
                'Drafting of Compliance Documents',
            ],
            'Governance & Policy Advisory' => [
                'Document Delivery (Metro Cebu)',
                'Document Delivery (Outside Metro Cebu/LBC)',
                'Drafting of Affidavits (Non-Legal Advice)',
                'Drafting of Agreements / Simple Contracts',
                'Drafting of Board Resolutions',
                'Drafting of Endorsement / Request Letters',
            ],
            'Business Strategy & Process Advisory' => [
                'Notarization - Simple Documents',
                'Notarization - Complex Documents',
                "Drafting of Secretary's Certificates",
                'Drafting of Policies & Procedures',
                'Drafting of Reports / Formal Documents',
            ],
            'Strategic Situations Advisory' => [
                'Printing',
                'Photocopy',
                'Drafting of Letters',
                'Drafting of Notices',
                'Drafting of Demand Letters',
                'Drafting of Emails (Formal / Business)',
            ],
            'People & Talent Solutions' => [
                'Archive Retrieval',
                'Digital Archive Copy',
                'Drafting of Responses to Letters / Notices',
                'Drafting of Memorandum (Internal / External)',
                'Drafting of Certifications',
                'Drafting of Compliance Documents',
            ],
            'Learning & Capability Development' => [
                'Document Delivery (Metro Cebu)',
                'Document Delivery (Outside Metro Cebu/LBC)',
                'Drafting of Affidavits (Non-Legal Advice)',
                'Drafting of Agreements / Simple Contracts',
                'Drafting of Board Resolutions',
                'Drafting of Endorsement / Request Letters',
            ],
        ];
    }

    private function defaultCategoryForServiceArea(string $serviceArea): string
    {
        return match ($serviceArea) {
            'Accounting & Compliance Advisory' => 'Accounting Services',
            'People & Talent Solutions',
            'Learning & Capability Development' => 'Training & Development',
            default => 'Corporate Services',
        };
    }
}

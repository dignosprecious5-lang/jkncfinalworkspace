@php
    $selectedOwner = collect($owners)->firstWhere('id', (int) $defaultOwnerId) ?: collect($owners)->first();
    $selectedOwnerId = (int) ($selectedOwner['id'] ?? 0);
    $selectedOwnerName = $selectedOwner['name'] ?? 'Select Owner';
    $createdByDisplay = old('created_by', $drawerMeta['createdBy'] ?? 'Admin User');
    $createdAtDisplay = old('created_at_display', $drawerMeta['createdAtDisplay'] ?? now()->format('F j, Y g:i A'));
    $selectedProductAreas = old('product_area', ['None']);
    $selectedLinkedServiceIds = collect(old('linked_service_ids', []))
        ->map(fn ($value) => (string) $value)
        ->values()
        ->all();
@endphp

<div id="createProductModal" class="fixed inset-0 z-[60] hidden" aria-hidden="true">
    <button id="createProductModalOverlay" type="button" aria-label="Close create product panel" class="absolute inset-0 bg-slate-900/45 opacity-0 transition-opacity duration-300"></button>

    <div class="absolute inset-y-0 right-0 flex w-full justify-end overflow-hidden pointer-events-none">
        <div id="createProductPanel" class="pointer-events-auto flex h-full w-full max-w-[720px] translate-x-full flex-col border-l border-gray-200 bg-white shadow-2xl transition-transform duration-300 ease-out sm:max-w-[680px]">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5 sm:px-8">
                <div>
                    <h2 id="createProductModalTitle" class="text-2xl font-semibold text-gray-900">Create Product</h2>
                    <p class="mt-1 text-sm text-gray-500">Capture the complete product setup before linking it to deals and services.</p>
                </div>
                <button id="closeCreateProductModal" type="button" class="text-2xl text-gray-500 hover:text-gray-800">&times;</button>
            </div>

            <form id="createProductForm" method="POST" action="{{ route('products.store') }}" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <input id="createProductFormMethod" type="hidden" name="_method" value="POST">
                <input id="product_owner_id" type="hidden" name="owner_id" value="{{ old('owner_id', $selectedOwnerId) }}">

                <div class="min-h-0 flex-1 space-y-6 overflow-y-auto px-6 py-6 sm:px-8">
                    <section class="rounded-xl border border-gray-200 bg-gray-50/70 p-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-700">Product Intake</p>
                            <p class="mt-1 text-sm text-gray-500">Configure the catalog record before linking it to services and deals. After saving, it will be submitted for admin approval.</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 sm:items-end">
                            <div class="relative">
                                <label for="productOwnerDropdownTrigger" class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Owner</label>
                                <button id="productOwnerDropdownTrigger" type="button" class="inline-flex h-10 w-full items-center justify-between gap-2 rounded-full border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-700 hover:bg-gray-100">
                                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                    <span id="productOwnerSelectedLabel">Owner: {{ $selectedOwnerName }}</span>
                                    <i class="fas fa-chevron-down text-[10px] text-gray-400"></i>
                                </button>

                                <div id="productOwnerDropdownMenu" class="absolute right-0 z-20 mt-2 hidden w-full min-w-0 rounded-xl border border-gray-200 bg-white p-2 shadow-lg sm:w-72">
                                    <div class="relative mb-2">
                                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400"></i>
                                        <input id="productOwnerSearch" type="text" placeholder="Search owner..." class="h-9 w-full rounded-lg border border-gray-200 pl-8 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                    </div>

                                    <div class="max-h-56 space-y-1 overflow-y-auto">
                                        @foreach ($owners as $owner)
                                            @php
                                                $ownerInitials = strtoupper(collect(explode(' ', trim($owner['name'])))
                                                    ->filter()
                                                    ->map(fn ($segment) => mb_substr($segment, 0, 1))
                                                    ->take(2)
                                                    ->implode(''));
                                            @endphp
                                            <button
                                                type="button"
                                                class="product-owner-option flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
                                                data-owner-id="{{ $owner['id'] }}"
                                                data-owner-name="{{ $owner['name'] }}"
                                                data-owner-email="{{ $owner['email'] }}"
                                            >
                                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-[10px] font-semibold text-blue-700">
                                                    {{ $ownerInitials }}
                                                </span>
                                                <span>
                                                    <span class="block text-sm text-gray-700">{{ $owner['name'] }}</span>
                                                    <span class="block text-xs text-gray-500">{{ $owner['email'] }}</span>
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <p class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Created By</p>
                                    <p class="text-sm text-gray-500">{{ $createdByDisplay }}</p>
                                </div>
                                <div>
                                    <p class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Created At</p>
                                    <p class="text-sm text-gray-500"><span id="productCreatedAtLiveValue">{{ $createdAtDisplay }}</span></p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-gray-200 bg-gray-50/70 p-4">
                        <h3 class="text-base font-semibold text-gray-900">Product Information</h3>
                        <p class="mb-4 text-xs text-gray-500">Capture the core product identity and classification.</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="product_name" class="mb-1 block text-sm font-medium text-gray-700">Product Name <span class="text-red-500">*</span></label>
                                <input id="product_name" name="product_name" required value="{{ old('product_name') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div>
                                <label for="product_type" class="mb-1 block text-sm font-medium text-gray-700">Product Type <span class="text-red-500">*</span></label>
                                <select id="product_type" name="product_type" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                    <option value="" disabled @selected(blank(old('product_type'))) >Select product type</option>
                                    @foreach ($productTypeOptions as $option)
                                        <option value="{{ $option }}" @selected(old('product_type') === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                                <div id="productTypeOtherWrap" class="mt-2 {{ old('product_type') === 'Other' ? '' : 'hidden' }}">
                                    <input id="product_type_other" name="product_type_other" value="{{ old('product_type_other') }}" placeholder="Enter custom product type" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                </div>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="sku" class="mb-1 block text-sm font-medium text-gray-700">SKU / Code</label>
                                <input id="sku" name="sku" value="{{ $nextSku ?? old('sku') }}" data-default-sku="{{ $nextSku ?? old('sku') }}" readonly class="h-10 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 text-sm text-gray-600 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <p class="mt-1 text-xs text-gray-500">Auto-generated on create using the format `PRD-REG-001`.</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-gray-200 bg-gray-50/70 p-4">
                        <h3 class="text-base font-semibold text-gray-900">Service Linking</h3>
                        <p class="mb-4 text-xs text-gray-500">Select one or more service areas, then link this product to the matching services.</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-gray-700">Service Area <span class="text-red-500">*</span></label>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @foreach ($productAreaOptions as $option)
                                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                            <input type="checkbox" name="product_area[]" value="{{ $option }}" @checked(in_array($option, $selectedProductAreas, true)) class="product-area-checkbox h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500" @if ($option === 'Others') data-other-target="productAreaOtherWrap" @endif>
                                            <span>{{ $option }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div id="productAreaOtherWrap" class="{{ in_array('Others', $selectedProductAreas, true) ? '' : 'hidden' }} sm:col-span-2">
                                <label for="product_area_other" class="mb-1 block text-sm font-medium text-gray-700">Other Product Area</label>
                                <input id="product_area_other" name="product_area_other" value="{{ old('product_area_other') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-gray-700">Linked Services</label>
                                <div id="linkedServicesEmptyState" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-500">
                                    Select a service area to show matching services.
                                </div>
                                <div id="linkedServicesList" class="hidden grid gap-2"></div>
                                <template id="linkedServiceOptionTemplate">
                                    <label class="linked-service-option flex items-start gap-3 rounded-lg border border-gray-200 bg-white px-3 py-3 text-sm text-gray-700">
                                        <input type="checkbox" name="linked_service_ids[]" class="mt-0.5 h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span>
                                            <span class="linked-service-name block font-medium text-gray-800"></span>
                                            <span class="linked-service-meta mt-0.5 block text-xs text-gray-500"></span>
                                        </span>
                                    </label>
                                </template>
                                <p class="mt-1 text-xs text-gray-500">You can link this product to multiple services under the selected service areas.</p>
                                <div id="selectedLinkedServiceIds" data-selected-service-ids='@json($selectedLinkedServiceIds)'></div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-gray-200 bg-gray-50/70 p-4">
                        <h3 class="text-base font-semibold text-gray-900">Description</h3>
                        <p class="mb-4 text-xs text-gray-500">Provide the main description and inclusions for quoting and internal review.</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="product_description" class="mb-1 block text-sm font-medium text-gray-700">Product Description <span class="text-red-500">*</span></label>
                                <textarea id="product_description" name="product_description" rows="4" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('product_description') }}</textarea>
                            </div>
                            <div>
                                <label for="product_inclusions" class="mb-1 block text-sm font-medium text-gray-700">Product Inclusions</label>
                                <textarea id="product_inclusions" name="product_inclusions" rows="4" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('product_inclusions') }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">Each new line will be saved as a bullet.</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-gray-200 bg-gray-50/70 p-4">
                        <h3 class="text-base font-semibold text-gray-900">Requirements</h3>
                        <p class="mb-4 text-xs text-gray-500">Set the default product requirements by client type, similar to Services.</p>
                        <div class="space-y-4">
                            <div>
                                <label for="product_requirements_individual" class="mb-1 block text-sm font-medium text-gray-700">Individual Requirements</label>
                                <textarea id="product_requirements_individual" name="requirements_individual" rows="4" class="product-requirements-input w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" placeholder="Enter one requirement per line">{{ old('requirements_individual') }}</textarea>
                                <div class="product-requirements-preview mt-2 rounded-lg border border-dashed border-gray-200 bg-white px-3 py-2 hidden"></div>
                            </div>
                            <div>
                                <label for="product_requirements_juridical" class="mb-1 block text-sm font-medium text-gray-700">Juridical Requirements</label>
                                <textarea id="product_requirements_juridical" name="requirements_juridical" rows="4" class="product-requirements-input w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" placeholder="Enter one requirement per line">{{ old('requirements_juridical') }}</textarea>
                                <div class="product-requirements-preview mt-2 rounded-lg border border-dashed border-gray-200 bg-white px-3 py-2 hidden"></div>
                            </div>
                            <div>
                                <label for="product_requirements_other" class="mb-1 block text-sm font-medium text-gray-700">Other Requirements</label>
                                <textarea id="product_requirements_other" name="requirements_other" rows="4" class="product-requirements-input w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" placeholder="Enter one requirement per line">{{ old('requirements_other') }}</textarea>
                                <div class="product-requirements-preview mt-2 rounded-lg border border-dashed border-gray-200 bg-white px-3 py-2 hidden"></div>
                            </div>
                            <input id="product_requirement_category" type="hidden" name="requirement_category" value="{{ old('requirement_category') }}">
                            <input id="product_requirements_legacy" type="hidden" name="requirements" value="{{ old('requirements') }}">
                            @error('requirement_category')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </section>

                    <section class="rounded-xl border border-gray-200 bg-gray-50/70 p-4">
                        <h3 class="text-base font-semibold text-gray-900">Accounting</h3>
                        <p class="mb-4 text-xs text-gray-500">Map the product to the proper accounting category.</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="category" class="mb-1 block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                                <select id="category" name="category" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                    <option value="" disabled @selected(blank(old('category'))) >Select category</option>
                                    @foreach ($categoryOptions as $category)
                                        <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                                    @endforeach
                                </select>
                                <div id="categoryOtherWrap" class="mt-2 {{ old('category') === 'Other' ? '' : 'hidden' }}">
                                    <input id="category_other" name="category_other" value="{{ old('category_other') }}" placeholder="Enter custom category" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-gray-200 bg-gray-50/70 p-4">
                        <h3 class="text-base font-semibold text-gray-900">Pricing</h3>
                        <p class="mb-4 text-xs text-gray-500">Define the pricing model, cost basis, and discount behavior.</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="pricing_type" class="mb-1 block text-sm font-medium text-gray-700">Pricing Type <span class="text-red-500">*</span></label>
                                <select id="pricing_type" name="pricing_type" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                    <option value="" disabled @selected(blank(old('pricing_type'))) >Select pricing type</option>
                                    @foreach ($pricingTypeOptions as $option)
                                        <option value="{{ $option }}" @selected(old('pricing_type') === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="price" class="mb-1 block text-sm font-medium text-gray-700">Price <span class="text-red-500">*</span></label>
                                <input id="price" name="price" type="number" min="0" step="0.01" value="{{ old('price') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div>
                                <label for="cost" class="mb-1 block text-sm font-medium text-gray-700">Cost</label>
                                <input id="cost" name="cost" type="number" min="0" step="0.01" value="{{ old('cost') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div class="flex items-end">
                                <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                    <input type="checkbox" name="is_discountable" value="1" @checked(old('is_discountable')) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span>Discountable</span>
                                </label>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="tax_type" class="mb-1 block text-sm font-medium text-gray-700">Tax Type <span class="text-red-500">*</span></label>
                                <select id="tax_type" name="tax_type" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                    <option value="" disabled @selected(blank(old('tax_type'))) >Select tax type</option>
                                    @foreach (['VAT', 'Non-VAT', 'Zero-rated', 'Exempt'] as $option)
                                        <option value="{{ $option }}" @selected(old('tax_type', 'VAT') === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-gray-700">Tax Treatment <span class="text-red-500">*</span></label>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach (['Tax Inclusive', 'Tax Exclusive'] as $option)
                                        <label class="flex items-center gap-3 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700">
                                            <input
                                                name="tax_treatment"
                                                type="radio"
                                                value="{{ $option }}"
                                                class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                                                @checked(old('tax_treatment', 'Tax Exclusive') === $option)
                                            >
                                            <span>{{ $option }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 p-4">
                        <h3 class="text-base font-semibold text-gray-900">Inventory</h3>
                        <p class="mb-4 text-xs text-gray-500">Only inventory-managed products require stock quantity.</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="inventory_type" class="mb-1 block text-sm font-medium text-gray-700">Inventory Type <span class="text-red-500">*</span></label>
                                <select id="inventory_type" name="inventory_type" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                    <option value="" disabled @selected(blank(old('inventory_type'))) >Select inventory type</option>
                                    @foreach ($inventoryTypeOptions as $option)
                                        <option value="{{ $option }}" @selected(old('inventory_type') === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                                <div id="inventoryTypeOtherWrap" class="mt-2 {{ old('inventory_type') === 'Other' ? '' : 'hidden' }}">
                                    <input id="inventory_type_other" name="inventory_type_other" value="{{ old('inventory_type_other') }}" placeholder="Enter custom inventory type" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                </div>
                            </div>
                            <div id="stockQtyWrap" class="{{ old('inventory_type') === 'Inventory' ? '' : 'hidden' }}">
                                <label for="stock_qty" class="mb-1 block text-sm font-medium text-gray-700">Stock Quantity</label>
                                <input id="stock_qty" name="stock_qty" type="number" min="0" step="1" value="{{ old('stock_qty') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="unit" class="mb-1 block text-sm font-medium text-gray-700">Unit</label>
                                <select id="unit" name="unit" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                    <option value="">Select unit</option>
                                    @foreach ($unitOptions as $option)
                                        <option value="{{ $option }}" @selected(old('unit') === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </section>

                    @if ($customFields->count() > 0)
                        <section class="rounded-2xl border border-gray-200 p-4">
                            <h3 class="text-base font-semibold text-gray-900">Custom Fields</h3>
                            <p class="mb-4 text-xs text-gray-500">Additional product attributes configured for the Products module.</p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                @foreach ($customFields as $field)
                                    @php
                                        $fieldName = 'custom_fields['.$field->field_key.']';
                                        $fieldValue = old('custom_fields.'.$field->field_key, $field->default_value);
                                    @endphp
                                    <div class="{{ $field->field_type === 'lookup' ? 'sm:col-span-2' : '' }}">
                                        <label for="field_{{ $field->field_key }}" class="mb-1 block text-sm font-medium text-gray-700">{{ $field->field_name }} @if($field->is_required)<span class="text-red-500">*</span>@endif</label>
                                        @if ($field->field_type === 'picklist')
                                            <select id="field_{{ $field->field_key }}" name="{{ $fieldName }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                                <option value="">Select {{ strtolower($field->field_name) }}</option>
                                                @foreach ($field->options ?? [] as $option)
                                                    <option value="{{ $option }}" @selected((string) $fieldValue === (string) $option)>{{ $option }}</option>
                                                @endforeach
                                            </select>
                                        @elseif ($field->field_type === 'checkbox')
                                            <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                                <input id="field_{{ $field->field_key }}" type="checkbox" name="{{ $fieldName }}" value="1" @checked((string) $fieldValue === '1') class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <span>Enabled</span>
                                            </label>
                                        @elseif ($field->field_type === 'date')
                                            <input id="field_{{ $field->field_key }}" type="date" name="{{ $fieldName }}" value="{{ $fieldValue }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                        @elseif (in_array($field->field_type, ['currency', 'numerical'], true))
                                            <input id="field_{{ $field->field_key }}" type="number" step="0.01" name="{{ $fieldName }}" value="{{ $fieldValue }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                        @else
                                            <input id="field_{{ $field->field_key }}" type="text" name="{{ $fieldName }}" value="{{ $fieldValue }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if ($errors->any())
                        <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                            {{ $errors->first() }}
                        </div>
                    @endif
                </div>

                <div class="mt-auto flex items-center justify-end gap-3 border-t border-gray-100 bg-white px-6 py-4 sm:px-8">
                    <button id="cancelCreateProductModal" type="button" class="h-10 min-w-[100px] rounded-lg border border-gray-300 px-5 text-sm text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button id="createProductFormSubmit" type="submit" class="h-10 min-w-[100px] rounded-lg bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

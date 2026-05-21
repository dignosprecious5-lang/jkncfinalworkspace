@extends('layouts.app')
@section('title', 'Company Products')

@section('content')
@php
    $totalProductValue = $products->sum(fn ($product) => (float) ($product['price'] ?? 0));
@endphp
<div class="w-full px-4 sm:px-6 lg:px-8 mt-4 pb-8">
    <div class="bg-white border border-gray-100 rounded-md overflow-hidden">
        @include('company.partials.company-header', ['company' => $company])

        <section class="bg-gray-50 p-4 min-h-[760px]">
            <div class="rounded-md border border-gray-200 bg-white overflow-hidden shadow-sm">
                <div class="border-b border-gray-100 px-4 py-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Products Availed</h2>
                            <p class="mt-1 text-sm text-gray-500">Products tied to this company through linked deals.</p>
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $products->count() }} {{ \Illuminate\Support\Str::plural('product', $products->count()) }}
                            <span class="mx-2 text-gray-300">|</span>
                            Total value P{{ number_format($totalProductValue, 2) }}
                        </div>
                    </div>

                    @if (session('products_success'))
                        <div class="mt-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                            {{ session('products_success') }}
                        </div>
                    @endif
                </div>

                <div class="p-4">
                    <div class="border border-gray-200 rounded-md bg-white overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium">Product Name</th>
                                        <th class="px-4 py-3 text-left font-medium">SKU</th>
                                        <th class="px-4 py-3 text-left font-medium">Category</th>
                                        <th class="px-4 py-3 text-left font-medium">Price</th>
                                        <th class="px-4 py-3 text-left font-medium">Pricing Type</th>
                                        <th class="px-4 py-3 text-left font-medium">Status</th>
                                        <th class="px-4 py-3 text-left font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                                    @forelse ($products as $product)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3">
                                                <a href="{{ $product['show_url'] ?? route('products.index') }}" class="font-medium text-gray-900 hover:text-blue-700">{{ $product['name'] }}</a>
                                                @if (! empty($product['description']))
                                                    <div class="mt-1 text-xs text-gray-500">{{ $product['description'] }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">{{ $product['sku'] }}</td>
                                            <td class="px-4 py-3">{{ $product['category'] }}</td>
                                            <td class="px-4 py-3 font-medium text-gray-900">P{{ number_format((float) $product['price'], 2) }}</td>
                                            <td class="px-4 py-3">{{ $product['pricing_type'] }}</td>
                                            <td class="px-4 py-3">
                                                @php($statusClasses = match($product['status']) {
                                                    'Active' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                                    'Inactive' => 'border-amber-200 bg-amber-50 text-amber-700',
                                                    default => 'border-gray-200 bg-gray-100 text-gray-600',
                                                })
                                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium {{ $statusClasses }}">{{ $product['status'] }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center justify-start gap-2">
                                                    <a href="{{ $product['show_url'] ?? route('company.products.show', [$company->id, $product['id']]) }}" class="inline-flex h-8 items-center rounded-full border border-gray-200 px-3 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                                        View
                                                    </a>
                                                    @if (!($product['readonly'] ?? false))
                                                        <button type="button" class="inline-flex h-8 items-center rounded-full border border-gray-200 px-3 text-xs font-medium text-gray-700 hover:bg-gray-50" data-product-edit='@json($product)'>
                                                            Edit
                                                        </button>
                                                        <form method="POST" action="{{ route('company.products.unlink', [$company->id, $product['id']]) }}" onsubmit="return confirm('Unlink this product from the current company?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="inline-flex h-8 items-center rounded-full border border-red-200 px-3 text-xs font-medium text-red-600 hover:bg-red-50">
                                                                Unlink
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500">No availed products found for this company yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="border-t border-gray-100 px-4 py-3 text-sm text-gray-500">
                        Total revenue: <span class="font-semibold text-gray-900">P{{ number_format($totalProductValue, 2) }}</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<x-slide-over id="linkProductModal" width="sm:max-w-[680px] lg:max-w-[820px]">
    <div class="border-b border-gray-100 px-4 py-4 sm:px-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Link Existing Products</h2>
                <p class="mt-1 text-sm text-gray-500">Select products to link to {{ $company->company_name }}. Already linked products are disabled.</p>
            </div>
            <button type="button" data-close-link-product-modal class="h-9 w-9 rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
    </div>

    <form method="POST" action="{{ route('company.products.link', $company->id) }}" class="flex min-h-0 flex-1 flex-col">
        @csrf

        <div class="border-b border-gray-100 px-4 py-4 sm:px-6">
            <div class="relative">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input id="linkProductSearch" type="text" placeholder="Search product catalog..." class="w-full h-10 rounded border border-gray-200 bg-white pl-11 pr-4 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
            </div>
        </div>

        <div id="linkProductList" class="flex-1 overflow-y-auto divide-y divide-gray-100 px-4 sm:px-6"></div>

        <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-close-link-product-modal class="h-9 min-w-[100px] rounded-full border border-gray-200 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" id="submitLinkProductsButton" class="h-9 min-w-[120px] rounded-full bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50" disabled>
                    Link Products
                </button>
            </div>
        </div>
    </form>
</x-slide-over>

<x-slide-over id="productModal" width="sm:max-w-[640px] lg:max-w-[760px]">
    <div class="border-b border-gray-100 px-4 py-4 sm:px-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 id="productModalTitle" class="text-lg font-semibold text-gray-900">New Product</h2>
                <p class="mt-1 text-sm text-gray-500">Products created here are automatically linked to {{ $company->company_name }}.</p>
            </div>
            <button type="button" data-close-product-modal class="h-9 w-9 rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
    </div>

    <form id="productForm" method="POST" action="{{ route('company.products.store', $company->id) }}" class="flex min-h-0 flex-1 flex-col">
        @csrf
        <input type="hidden" id="productFormMethod" name="_method" value="POST">

        <div class="flex-1 overflow-y-auto px-4 py-4 sm:px-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="product_name" class="mb-1 block text-sm font-medium text-gray-700">Product Name <span class="text-red-500">*</span></label>
                    <input id="product_name" name="name" type="text" value="{{ old('name') }}" class="h-9 w-full rounded border border-gray-200 px-4 text-sm text-gray-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100" required>
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="product_sku" class="mb-1 block text-sm font-medium text-gray-700">SKU</label>
                    <input id="product_sku" name="sku" type="text" value="{{ old('sku') }}" class="h-9 w-full rounded border border-gray-200 px-4 text-sm text-gray-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100">
                    @error('sku')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="product_category" class="mb-1 block text-sm font-medium text-gray-700">Category</label>
                    <input id="product_category" name="category" type="text" value="{{ old('category') }}" class="h-9 w-full rounded border border-gray-200 px-4 text-sm text-gray-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100">
                    @error('category')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="product_price" class="mb-1 block text-sm font-medium text-gray-700">Price <span class="text-red-500">*</span></label>
                    <input id="product_price" name="price" type="number" step="0.01" min="0" value="{{ old('price') }}" class="h-9 w-full rounded border border-gray-200 px-4 text-sm text-gray-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100" required>
                    @error('price')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="product_pricing_type" class="mb-1 block text-sm font-medium text-gray-700">Billing Type</label>
                    <select id="product_pricing_type" name="pricing_type" class="h-9 w-full rounded border border-gray-200 bg-white px-4 text-sm text-gray-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100">
                        @foreach (['Recurring', 'One-Time', 'Milestone'] as $pricingType)
                            <option value="{{ $pricingType }}" @selected(old('pricing_type', 'Recurring') === $pricingType)>{{ $pricingType }}</option>
                        @endforeach
                    </select>
                    @error('pricing_type')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="product_status" class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                    <select id="product_status" name="status" class="h-9 w-full rounded border border-gray-200 bg-white px-4 text-sm text-gray-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100">
                        @foreach (['Active', 'Inactive', 'Draft'] as $productStatus)
                            <option value="{{ $productStatus }}" @selected(old('status', 'Active') === $productStatus)>{{ $productStatus }}</option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="product_description" class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="product_description" name="description" rows="3" class="w-full rounded border border-gray-200 px-4 py-3 text-sm text-gray-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="product_notes" class="mb-1 block text-sm font-medium text-gray-700">Notes</label>
                    <textarea id="product_notes" name="notes" rows="3" class="w-full rounded border border-gray-200 px-4 py-3 text-sm text-gray-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

        </div>

        <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-close-product-modal class="h-9 min-w-[100px] rounded-full border border-gray-200 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" id="productFormSubmit" class="h-9 min-w-[100px] rounded-full bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">
                    Save
                </button>
            </div>
        </div>
    </form>
</x-slide-over>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const productCatalog = @json($productCatalog);
        const linkedProducts = @json($products->values());
        const linkProductModal = document.getElementById('linkProductModal');
        const productModal = document.getElementById('productModal');
        const openLinkButtons = [document.getElementById('openLinkProductModal'), document.getElementById('openFirstProductLinkModal')].filter(Boolean);
        const openCreateButton = document.getElementById('openCreateProductModal');
        const closeLinkButtons = document.querySelectorAll('[data-close-link-product-modal]');
        const closeProductButtons = document.querySelectorAll('[data-close-product-modal]');
        const linkProductList = document.getElementById('linkProductList');
        const linkProductSearch = document.getElementById('linkProductSearch');
        const submitLinkProductsButton = document.getElementById('submitLinkProductsButton');
        const productForm = document.getElementById('productForm');
        const productFormMethod = document.getElementById('productFormMethod');
        const productModalTitle = document.getElementById('productModalTitle');
        const productFormSubmit = document.getElementById('productFormSubmit');
        const productEditButtons = document.querySelectorAll('[data-product-edit]');
        const productShowUrlTemplate = @json(route('company.products.update', [$company->id, '__PRODUCT__']));

        const linkedIds = new Set(linkedProducts.map((product) => Number(product.id)));
        const productFields = ['name', 'sku', 'category', 'price', 'pricing_type', 'status', 'description', 'notes'];

        const openModal = (modal) => window.jkncSlideOver.open(modal);
        const closeModal = (modal) => window.jkncSlideOver.close(modal);

        const renderLinkList = () => {
            const searchTerm = String(linkProductSearch.value || '').toLowerCase().trim();
            linkProductList.innerHTML = '';

            const visibleProducts = productCatalog.filter((product) => {
                const haystack = [product.name, product.sku, product.category].join(' ').toLowerCase();
                return haystack.includes(searchTerm);
            });

            visibleProducts.forEach((product) => {
                const disabled = linkedIds.has(Number(product.id));
                const row = document.createElement('label');
                row.className = `flex items-center gap-3 px-4 py-3 ${disabled ? 'bg-gray-50' : 'hover:bg-gray-50'} cursor-pointer`;
                row.innerHTML = `
                    <input type="checkbox" name="product_ids[]" value="${product.id}" class="h-4 w-4 rounded border-gray-300 text-blue-600" ${disabled ? 'disabled' : ''}>
                    <div class="flex-1">
                        <p class="text-sm font-medium ${disabled ? 'text-gray-400' : 'text-gray-800'}">${product.name}</p>
                        <p class="text-xs text-gray-500 mt-0.5">${product.sku || '-'}${product.category ? ' / ' + product.category : ''}</p>
                        ${disabled ? '<p class="text-xs text-gray-400 mt-0.5">Already linked to this company</p>' : ''}
                    </div>
                `;
                linkProductList.appendChild(row);
            });

            bindLinkCheckboxes();
        };

        const bindLinkCheckboxes = () => {
            const checkboxes = linkProductList.querySelectorAll('input[name="product_ids[]"]:not([disabled])');
            const updateButton = () => {
                submitLinkProductsButton.disabled = Array.from(checkboxes).every((checkbox) => !checkbox.checked);
            };

            checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateButton));
            updateButton();
        };

        const resetProductForm = () => {
            productForm.reset();
            productForm.action = @json(route('company.products.store', $company->id));
            productFormMethod.value = 'POST';
            productModalTitle.textContent = 'New Product';
            productFormSubmit.textContent = 'Save';
            document.getElementById('product_pricing_type').value = 'Recurring';
            document.getElementById('product_status').value = 'Active';
        };

        const fillProductForm = (product) => {
            document.getElementById('product_name').value = product.name ?? '';
            document.getElementById('product_sku').value = product.sku ?? '';
            document.getElementById('product_category').value = product.category ?? '';
            document.getElementById('product_price').value = product.price ?? '';
            document.getElementById('product_pricing_type').value = product.pricing_type ?? 'Recurring';
            document.getElementById('product_status').value = product.status ?? 'Active';
            document.getElementById('product_description').value = product.description ?? '';
            document.getElementById('product_notes').value = product.notes ?? '';
        };

        openLinkButtons.forEach((button) => {
            button.addEventListener('click', function () {
                renderLinkList();
                openModal(linkProductModal);
            });
        });

        openCreateButton?.addEventListener('click', function () {
            resetProductForm();
            openModal(productModal);
        });

        closeLinkButtons.forEach((button) => {
            button.addEventListener('click', function () {
                closeModal(linkProductModal);
            });
        });

        closeProductButtons.forEach((button) => {
            button.addEventListener('click', function () {
                closeModal(productModal);
            });
        });

        linkProductSearch?.addEventListener('input', renderLinkList);

        productEditButtons.forEach((button) => {
            button.addEventListener('click', function () {
                const product = JSON.parse(this.dataset.productEdit);
                resetProductForm();
                productForm.action = productShowUrlTemplate.replace('__PRODUCT__', product.id);
                productFormMethod.value = 'PUT';
                productModalTitle.textContent = 'Edit Product';
                productFormSubmit.textContent = 'Update';
                fillProductForm(product);
                openModal(productModal);
            });
        });

        [linkProductModal, productModal].forEach((modal) => {
            modal.addEventListener('click', function (event) {
                if (event.target === modal || event.target.hasAttribute('data-drawer-overlay')) {
                    closeModal(modal);
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                if (!linkProductModal.classList.contains('hidden')) {
                    closeModal(linkProductModal);
                }

                if (!productModal.classList.contains('hidden')) {
                    closeModal(productModal);
                }
            }
        });

        @if ($errors->has('name') || $errors->has('sku') || $errors->has('category') || $errors->has('price') || $errors->has('pricing_type') || $errors->has('status') || $errors->has('description') || $errors->has('notes'))
            resetProductForm();
            openModal(productModal);
        @endif
    });
</script>
@endsection

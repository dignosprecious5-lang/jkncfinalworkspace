@extends('layouts.app')
@section('content')
@php
    /**
     * Products Catalog Dashboard View
     * Standardized Product Module
     */
    // 1. Data Initialization & Fallbacks
    $productList = isset($products) ? $products : [];
    $counts = isset($allProductsCount) ? $allProductsCount : [
        'incomplete' => 0, 'draft' => 0, 'for_approval' => 0, 'active' => 0, 'archived' => 0
    ];
    $insightData = isset($insights) ? $insights : [];
@endphp
{{-- 2. Success Flash Notification --}}
@if (session('success'))
    <div class="bg-emerald-100 border border-emerald-300 text-emerald-800 px-4 py-2.5 rounded text-xs font-semibold shadow-sm mb-4 flex items-center justify-between">
        <span><i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}</span>
    </div>
@endif
{{-- 3. Header Section & Action Buttons --}}
<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Products Catalog</h1>
        <p class="text-sm text-slate-500 mt-1">Standardized product catalog with configurable fields, routing, inventory, and pricing.</p>
        <button
            type="button"
            onclick="document.getElementById('whatIsProductModal').classList.remove('hidden')"
            class="text-sm text-blue-600 font-bold hover:underline mt-1.5 inline-block cursor-pointer"
        >
            What is a Product?
        </button>
    </div>
    <div class="flex items-center space-x-3">
        {{-- REPORTS BUTTON --}}
        <button
            type="button"
            onclick="window.location.href='{{ route('products.reports') }}'"
            class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-sm px-4 py-2.5 rounded-lg shadow-sm transition flex items-center space-x-2 whitespace-nowrap cursor-pointer"
        >
            <i class="fa-solid fa-chart-pie text-blue-600 text-sm"></i>
            <span>Reports</span>
        </button>
        {{-- BULK ACTIONS DROPDOWN MENU --}}
        <div class="relative inline-block text-left">
            <button
                type="button"
                onclick="toggleBulkActionsMenu(event)"
                class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-sm px-4 py-2.5 rounded-lg shadow-sm transition flex items-center space-x-2 whitespace-nowrap cursor-pointer"
            >
                <i class="fa-solid fa-layer-group text-slate-500 text-sm"></i>
                <span>Bulk Actions</span>
                <i class="fa-solid fa-chevron-down text-xs text-slate-400 ml-1"></i>
            </button>
            <div
                id="bulkActionsMenu"
                class="hidden absolute right-0 mt-1.5 w-52 bg-white rounded-lg shadow-xl border border-slate-200 py-1.5 z-50 text-xs font-medium text-slate-700 divide-y divide-slate-100"
            >
                <button
                    type="button"
                    onclick="document.getElementById('importProductModal').classList.remove('hidden'); document.getElementById('bulkActionsMenu').classList.add('hidden')"
                    class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center space-x-2.5 transition text-slate-700 cursor-pointer"
                >
                    <i class="fa-solid fa-file-import text-blue-600 w-4 text-center"></i>
                    <span>Bulk Import Products</span>
                </button>
                <div class="py-1">
                    <a
                        href="{{ Route::has('products.export_all') ? route('products.export_all', ['format' => 'csv']) : url('/products/export-all?format=csv') }}"
                        onclick="document.getElementById('bulkActionsMenu').classList.add('hidden')"
                        class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center space-x-2.5 transition text-slate-700 cursor-pointer block"
                    >
                        <i class="fa-solid fa-file-csv text-emerald-600 w-4 text-center"></i>
                        <span>Export to CSV</span>
                    </a>
                    <a
                        href="{{ Route::has('products.export_all') ? route('products.export_all', ['format' => 'pdf']) : url('/products/export-all?format=pdf') }}"
                        onclick="document.getElementById('bulkActionsMenu').classList.add('hidden')"
                        class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center space-x-2.5 transition text-slate-700 cursor-pointer block"
                    >
                        <i class="fa-solid fa-file-pdf text-rose-600 w-4 text-center"></i>
                        <span>Export to PDF</span>
                    </a>
                </div>
            </div>
        </div>
        {{-- QUICK ADD BUTTON --}}
        <button
            type="button"
            onclick="document.getElementById('quickAddModal').classList.remove('hidden')"
            class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm px-5 py-2.5 rounded-lg shadow-sm transition flex items-center space-x-1.5 whitespace-nowrap cursor-pointer"
        >
            <span>+ Quick Add</span>
        </button>
    </div>
</div>


{{-- 4. Detailed Insights Cards --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Most Purchased Product</span>
        <p class="font-bold text-slate-900 text-sm truncate" title="{{ $mostPurchasedName ?? 'No data yet' }}">{{ $mostPurchasedName ?? 'No data yet' }}</p>
        <div class="text-xs text-slate-700 font-semibold mt-1">{{ $purchasedCount ?? 0 }} Active Units</div>
    </div>
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Highest Priced Product</span>
        <p class="font-bold text-slate-900 text-sm truncate" title="{{ $highestName ?? 'No data yet' }}">{{ $highestName ?? 'No data yet' }}</p>
        <div class="text-xs text-slate-800 font-semibold mt-1">₱{{ $highestPrice ?? '0.00' }}</div>
    </div>
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Most Stock Intensive</span>
        <p class="font-bold text-slate-900 text-sm truncate" title="{{ $laborName ?? 'No data yet' }}">{{ $laborName ?? 'No data yet' }}</p>
        <div class="text-xs text-slate-700 font-semibold mt-1">{{ $laborHours ?? 0 }} Units Stock</div>
    </div>
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Most Discounted Product</span>
        <p class="font-bold text-slate-900 text-sm truncate" title="{{ $discountName ?? 'No data yet' }}">{{ $discountName ?? 'No data yet' }}</p>
        <div class="text-xs text-slate-700 font-semibold mt-1">{{ $maxDiscount ?? '0 Max Discount' }}</div>
    </div>
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Highest Margin Product</span>
        <p class="font-bold text-slate-900 text-sm truncate" title="{{ $marginName ?? 'No data yet' }}">{{ $marginName ?? 'No data yet' }}</p>
        <div class="text-xs text-slate-700 font-semibold mt-1">{{ $marginPct ?? 0 }}% Margin</div>
    </div>
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Fastest Growing & Inactive</span>
        <p class="font-bold text-slate-900 text-sm truncate" title="{{ $fastestName ?? 'No data yet' }}">{{ $fastestName ?? 'No data yet' }}</p>
        <div class="text-xs text-slate-700 font-semibold mt-1">{{ $fastestRate ?? 'No growth data' }} | {{ $decliningName ?? 'No data yet' }}</div>
    </div>
</div>


{{-- 5. Status Metrics Cards --}}
<div class="grid grid-cols-5 gap-3 mb-6">
    @php
        $statusMap = [
            'incomplete'   => ['label' => 'Incomplete', 'color' => 'slate'],
            'draft'        => ['label' => 'Draft', 'color' => 'slate'],
            'for_approval' => ['label' => 'For Approval', 'color' => 'slate'],
            'active'       => ['label' => 'Active', 'color' => 'slate'],
            'archived'     => ['label' => 'Archived', 'color' => 'slate'],
        ];
    @endphp
    @foreach ($statusMap as $key => $meta)
        @php
            $cnt = $counts[$key] ?? 0;
            $isSelected = request('status') === $key
                ? 'ring-2 ring-blue-500 font-bold bg-slate-50'
                : '';
        @endphp
        <a
            href="{{ route('products.index') }}?status={{ $key }}"
            class="bg-white border border-slate-200 hover:bg-slate-50 rounded p-3 transition block shadow-sm {{ $isSelected }}"
        >
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">
                {{ $meta['label'] }}
            </span>
            <div class="text-2xl font-bold text-slate-900 mt-1">
                {{ $cnt }}
            </div>
        </a>
    @endforeach
</div>

{{-- =========================================================
    ROW 1: DASHBOARD FILTERS + DATE RANGE
========================================================== --}}
<form
    method="GET"
    action="{{ route('products.index') }}"
    class="bg-white border border-slate-200 rounded-lg p-4 mb-4 shadow-sm space-y-4"
>

    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">

        <div class="flex items-center space-x-3">

            <span class="font-bold text-slate-800 text-sm">
                Dashboard Filters
            </span>

            <span class="text-slate-500 text-sm font-medium">
                From
            </span>

            <input
                type="date"
                name="from_date"
                value="{{ request('from_date', '') }}"
                class="border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700 bg-white"
            >

            <span class="text-slate-500 text-sm font-medium">
                To
            </span>

            <input
                type="date"
                name="to_date"
                value="{{ request('to_date', '') }}"
                class="border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700 bg-white"
            >

        </div>

    </div>


    {{-- =========================================================
        ROW 2: SEARCH + FILTERS + RESET + APPLY
    ========================================================== --}}

    <div class="flex flex-wrap items-center justify-between gap-3">

        <div class="flex flex-wrap items-center gap-3 flex-1">

            {{-- SEARCH --}}

            <div class="relative flex-1 min-w-[200px] max-w-sm">

                <input
                    type="text"
                    name="search"
                    value="{{ request('search', '') }}"
                    placeholder="Search code or product name..."
                    class="w-full border border-slate-300 rounded-lg pl-10 pr-4 py-2 text-sm text-slate-800 outline-none focus:border-blue-500"
                >

                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-sm"></i>

            </div>


            {{-- STATUS --}}

            <select
                name="status"
                class="border border-slate-300 rounded-lg px-3.5 py-2 text-sm text-slate-800 bg-white"
            >

                <option value="">
                    All Status
                </option>

                <option
                    value="incomplete"
                    {{ request('status') === 'incomplete' ? 'selected' : '' }}
                >
                    Incomplete
                </option>

                <option
                    value="draft"
                    {{ request('status') === 'draft' ? 'selected' : '' }}
                >
                    Draft
                </option>

                <option
                    value="for_approval"
                    {{ request('status') === 'for_approval' ? 'selected' : '' }}
                >
                    For Approval
                </option>

                <option
                    value="active"
                    {{ request('status') === 'active' ? 'selected' : '' }}
                >
                    Active
                </option>

                <option
                    value="archived"
                    {{ request('status') === 'archived' ? 'selected' : '' }}
                >
                    Archived
                </option>

            </select>


            {{-- CATEGORY --}}

            <div
                x-data="{ selectedCategory: '{{ request('category', '') }}' }"
                class="flex items-center space-x-2"
            >

                <select
                    name="category"
                    x-model="selectedCategory"
                    class="border border-slate-300 rounded-lg px-3.5 py-2 text-sm text-slate-800 bg-white"
                >

                    <option value="">
                        Category: All
                    </option>

                    <option value="Professional Fees">
                        Professional Fees
                    </option>

                    <option value="Consulting Revenue">
                        Consulting Revenue
                    </option>

                    <option value="Accounting Services">
                        Accounting Services
                    </option>

                    <option value="Tax Services">
                        Tax Services
                    </option>

                    <option value="Corporate Services">
                        Corporate Services
                    </option>

                    <option value="HR Services">
                        HR Services
                    </option>

                    <option value="Training & Development">
                        Training & Development
                    </option>

                    <option value="Other Income">
                        Other Income
                    </option>

                    <option value="Other">
                        Other
                    </option>

                </select>


                <template x-if="selectedCategory === 'Other'">

                    <input
                        type="text"
                        name="other_category"
                        value="{{ request('other_category') }}"
                        placeholder="Enter new category..."
                        class="border border-slate-300 rounded-lg px-3.5 py-2 text-sm outline-none focus:border-slate-500 bg-white"
                    >

                </template>

            </div>


            {{-- PRODUCT TYPE --}}

            <div
                x-data="{ selectedProductType: '{{ request('product_type', '') }}' }"
                class="flex items-center space-x-2"
            >

                <select
                    name="product_type"
                    x-model="selectedProductType"
                    class="border border-slate-300 rounded-lg px-3.5 py-2 text-sm text-slate-800 bg-white"
                >

                    <option value="">
                        Product Type: All
                    </option>

                    <option value="Service">
                        Service
                    </option>

                    <option value="Bundle">
                        Bundle
                    </option>

                    <option value="Package">
                        Package
                    </option>

                    <option value="Physical Product">
                        Physical Product
                    </option>

                    <option value="Digital Product">
                        Digital Product
                    </option>

                    <option value="Other">
                        Other
                    </option>

                </select>


                <template x-if="selectedProductType === 'Other'">

                    <input
                        type="text"
                        name="other_product_type"
                        value="{{ request('other_product_type') }}"
                        placeholder="Enter new product type..."
                        class="border border-slate-300 rounded-lg px-3.5 py-2 text-sm outline-none focus:border-slate-500 bg-white"
                    >

                </template>

            </div>


            {{-- INVENTORY TYPE --}}

            <select
                name="inventory_type"
                class="border border-slate-300 rounded-lg px-3.5 py-2 text-sm text-slate-800 bg-white"
            >

                <option value="">
                    Inventory: All
                </option>

                <option
                    value="Inventory"
                    {{ request('inventory_type') === 'Inventory' ? 'selected' : '' }}
                >
                    Inventory
                </option>

                <option
                    value="Non-Inventory"
                    {{ request('inventory_type') === 'Non-Inventory' ? 'selected' : '' }}
                >
                    Non-Inventory
                </option>

                <option
                    value="Service"
                    {{ request('inventory_type') === 'Service' ? 'selected' : '' }}
                >
                    Service
                </option>

                <option
                    value="Other"
                    {{ request('inventory_type') === 'Other' ? 'selected' : '' }}
                >
                    Other
                </option>

            </select>

        </div>


        {{-- RESET + APPLY --}}

        <div class="flex items-center space-x-3 ml-auto">

            <a
                href="{{ route('products.index') }}"
                class="px-4 py-2 border border-slate-300 text-slate-700 font-medium text-xs rounded-lg hover:bg-slate-50 transition"
            >
                Reset
            </a>

            <button
                type="submit"
                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs rounded-lg shadow-sm transition cursor-pointer"
            >
                Apply Filters
            </button>

        </div>

    </div>


    {{-- =========================================================
        ROW 3: SERVICE AREA + CREATE FIELD
    ========================================================== --}}

    <div class="flex flex-wrap items-center justify-between pt-3 border-t border-slate-100 gap-3">

        {{-- SERVICE AREA --}}

        <div
            x-data="{ selectedServiceArea: '{{ request('service_area', '') }}' }"
            class="flex items-center space-x-2 w-full md:w-auto"
        >

            <span class="text-xs font-semibold text-slate-700">
                Service Area:
            </span>

            <select
                name="service_area"
                x-model="selectedServiceArea"
                class="border border-slate-300 rounded-lg px-3.5 py-2 text-sm text-slate-800 bg-white w-full md:w-72"
            >

                <option value="">
                    All Service Areas
                </option>

                <option value="Corporate & Regulatory Advisory">
                    Corporate & Regulatory Advisory
                </option>

                <option value="Governance & Policy Advisory">
                    Governance & Policy Advisory
                </option>

                <option value="People & Talent Solutions">
                    People & Talent Solutions
                </option>

                <option value="Strategic Situations Advisory">
                    Strategic Situations Advisory
                </option>

                <option value="Accounting & Compliance Advisory">
                    Accounting & Compliance Advisory
                </option>

                <option value="Business Strategy & Process Advisory">
                    Business Strategy & Process Advisory
                </option>

                <option value="Learning & Capability Development">
                    Learning & Capability Development
                </option>

                <option value="Others">
                    Others
                </option>

            </select>


            <template x-if="selectedServiceArea === 'Others'">

                <input
                    type="text"
                    name="other_service_area"
                    value="{{ request('other_service_area') }}"
                    placeholder="Enter new service area..."
                    class="border border-slate-300 rounded-lg px-3.5 py-2 text-sm outline-none focus:border-slate-500 bg-white"
                >

            </template>

        </div>


        {{-- CREATE FIELD --}}

        <div class="relative inline-block text-left">

            <button
                type="button"
                onclick="document.getElementById('createFieldMenu').classList.toggle('hidden')"
                class="text-blue-600 hover:text-blue-800 font-bold text-sm flex items-center space-x-1.5 transition cursor-pointer py-1.5 px-3 rounded-lg hover:bg-blue-50"
            >

                <span>
                    + Create Field
                </span>

                <i class="fa-solid fa-chevron-down text-[9px]"></i>

            </button>


            {{-- CREATE FIELD MENU --}}

            <div
                id="createFieldMenu"
                class="hidden absolute right-0 mt-1 w-56 bg-white rounded-lg shadow-xl border border-slate-200 py-1.5 z-[9999] text-sm font-medium text-slate-700 max-h-64 overflow-y-auto"
            >

                <button
                    type="button"
                    onclick="openFieldModal('Single Line Text')"
                    class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center space-x-3 cursor-pointer"
                >
                    <i class="fa-solid fa-font text-slate-400 w-4 text-center"></i>
                    <span>Single Line Text</span>
                </button>


                <button
                    type="button"
                    onclick="openFieldModal('Multi Line Text')"
                    class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center space-x-3 cursor-pointer"
                >
                    <i class="fa-solid fa-align-left text-slate-400 w-4 text-center"></i>
                    <span>Multi Line Text</span>
                </button>


                <button
                    type="button"
                    onclick="openFieldModal('Number')"
                    class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center space-x-3 cursor-pointer"
                >
                    <i class="fa-solid fa-hashtag text-slate-400 w-4 text-center"></i>
                    <span>Number</span>
                </button>


                <button
                    type="button"
                    onclick="openFieldModal('Currency')"
                    class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center space-x-3 cursor-pointer"
                >
                    <i class="fa-solid fa-money-bill-wave text-slate-400 w-4 text-center"></i>
                    <span>Currency</span>
                </button>


                <button
                    type="button"
                    onclick="openFieldModal('Picklist')"
                    class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center space-x-3 cursor-pointer"
                >
                    <i class="fa-solid fa-list-check text-slate-400 w-4 text-center"></i>
                    <span>Picklist</span>
                </button>


                <button
                    type="button"
                    onclick="openFieldModal('Checkbox')"
                    class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center space-x-3 cursor-pointer"
                >
                    <i class="fa-regular fa-square-check text-slate-400 w-4 text-center"></i>
                    <span>Checkbox</span>
                </button>


                <button
                    type="button"
                    onclick="openFieldModal('Date')"
                    class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center space-x-3 cursor-pointer"
                >
                    <i class="fa-regular fa-calendar text-slate-400 w-4 text-center"></i>
                    <span>Date</span>
                </button>


                <button
                    type="button"
                    onclick="openFieldModal('Lookup')"
                    class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center space-x-3 cursor-pointer"
                >
                    <i class="fa-solid fa-link text-slate-400 w-4 text-center"></i>
                    <span>Lookup</span>
                </button>

            </div>

        </div>

    </div>

</form>

{{-- DYNAMIC FIELD CREATION MODAL (BLUE THEME) --}}

<div
    id="createFieldModal"
    class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden flex justify-center items-center p-4 z-[9999]"
>
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 text-xs space-y-4 relative">

        {{-- HEADER --}}
        <div class="flex justify-between items-center border-b pb-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900">
                    Create Custom Field
                </h3>

                <p class="text-[11px] text-slate-500 mt-0.5">
                    Type:
                    <span
                        id="modalFieldTypeName"
                        class="font-bold text-blue-600"
                    >
                        Single Line Text
                    </span>
                </p>
            </div>

            <button
                type="button"
                onclick="closeFieldModal()"
                class="text-slate-400 hover:text-blue-600 text-lg cursor-pointer transition"
            >
                &times;
            </button>
        </div>


        {{-- FORM --}}
        <form
            id="createCustomFieldForm"
            action="#"
            method="POST"
            onsubmit="submitCustomField(event)"
            class="space-y-3"
        >
            @csrf

            <input
                type="hidden"
                id="customFieldProductId"
                name="product_id"
                value=""
            >

            {{-- FIELD TYPE --}}
            <input
                type="hidden"
                name="field_type"
                id="modalFieldType"
                value="Single Line Text"
            >


            {{-- FIELD LABEL / NAME --}}
            <div>
                <label class="block font-semibold text-slate-700 mb-1">
                    Field Label *
                </label>

                <input
                    type="text"
                    name="field_name"
                    required
                    placeholder="e.g. Tax Registration ID"
                    class="w-full border border-slate-300 rounded px-3 py-2 text-xs outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                >
            </div>


            {{-- API NAME --}}
            <div>
                <label class="block font-semibold text-slate-700 mb-1">
                    Field API Name
                </label>

                <input
                    type="text"
                    name="field_api_name"
                    placeholder="e.g. tax_registration_id"
                    class="w-full border border-slate-300 rounded px-3 py-2 text-xs outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-slate-50"
                >
            </div>


            {{-- PICKLIST OPTIONS --}}
            <div
                id="picklistOptionsContainer"
                class="hidden"
            >
                <label class="block font-semibold text-slate-700 mb-1">
                    Options (One per line)
                </label>

                <textarea
                    name="options"
                    rows="3"
                    placeholder="Option 1&#10;Option 2&#10;Option 3"
                    class="w-full border border-slate-300 rounded px-3 py-1.5 text-xs outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                ></textarea>
            </div>


            {{-- REQUIRED --}}
            <div class="flex items-center space-x-2 pt-1">
                <input
                    type="hidden"
                    name="is_required"
                    value="0"
                >

                <input
                    type="checkbox"
                    id="isRequired"
                    name="is_required"
                    value="1"
                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                >

                <label
                    for="isRequired"
                    class="text-xs text-slate-700 font-medium cursor-pointer"
                >
                    Mark as Required Field
                </label>
            </div>


            {{-- ACTIONS --}}
            <div class="flex justify-end space-x-2 pt-3 border-t">

                <button
                    type="button"
                    onclick="closeFieldModal()"
                    class="px-3.5 py-1.5 border border-slate-300 text-slate-700 font-medium rounded hover:bg-slate-50 transition cursor-pointer"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded shadow-sm transition cursor-pointer"
                >
                    Create Field
                </button>

            </div>

        </form>
    </div>
</div>

<script>
function openFieldModal(fieldType) {
    document.getElementById('createFieldMenu')?.classList.add('hidden');
    document.getElementById('modalFieldTypeName').innerText = fieldType;
    document.getElementById('modalFieldType').value = fieldType;
    
    const picklistContainer = document.getElementById('picklistOptionsContainer');
    if (fieldType === 'Picklist') {
        picklistContainer.classList.remove('hidden');
    } else {
        picklistContainer.classList.add('hidden');
    }

    document.getElementById('createFieldModal').classList.remove('hidden');
}

function closeFieldModal() {
    document.getElementById('createFieldModal').classList.add('hidden');
}
</script>

{{-- 7. Products Data Table --}}
<div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-x-auto mb-4">
    <table class="w-full text-left border-collapse text-xs border-slate-200 divide-y divide-slate-200">
        <thead>
            <tr class="bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider divide-x divide-slate-200">
                <th class="py-3 px-3 w-10 text-center">
                    <input
                        type="checkbox"
                        id="selectAllCheckbox"
                        onclick="toggleSelectAll(this)"
                        class="rounded border-slate-300"
                    >
                </th>
                <th class="py-3 px-4">PRODUCT NAME & CODE</th>
                <th class="py-3 px-4">PRODUCT TYPE</th>
                <th class="py-3 px-4">CATEGORY</th>
                <th class="py-3 px-4">INVENTORY STOCK</th>
                <th class="py-3 px-4">PRICING TYPE</th>
                <th class="py-3 px-4">PRICE</th>
                <th class="py-3 px-4">TAX TREATMENT</th>
                <th class="py-3 px-4">STATUS</th>
                <th class="py-3 px-4 text-center">ACTIONS</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 text-slate-700 bg-white">
            @if (count($productList) > 0)
                @foreach ($productList as $item)
                    @php
                        $id = is_object($item) ? $item->id : $item['id'];
                        $name = is_object($item) ? $item->name : $item['name'];
                        $code = is_object($item) ? ($item->sku ?? 'PRD-' . sprintf('%04d', $id)) : ($item['sku'] ?? 'PRD-0000');
                        $productType = is_object($item) ? ($item->product_type ?? '-') : ($item['product_type'] ?? '-');
                        $cat = is_object($item) ? ($item->category ?? '-') : ($item['category'] ?? '-');
                        $stock = is_object($item) ? ($item->inventory_stock ?? 0) : ($item['inventory_stock'] ?? 0);
                        $pricing = is_object($item) ? ($item->pricing_type ?? '-') : ($item['pricing_type'] ?? '-');
                        
                        $priceVal = is_object($item) ? ($item->price ?? $item->standard_price ?? 0) : ($item['price'] ?? 0);
                        $price = number_format($priceVal, 2);
                        $tax = is_object($item) ? ($item->tax_treatment ?? '-') : ($item['tax_treatment'] ?? '-');
                        $rawStatus = is_object($item) ? ($item->status ?? 'Draft') : ($item['status'] ?? 'Draft');
                        $status = strtolower($rawStatus);
                        $workspaceUrl = route('products.workspace', $id);
                    @endphp
                    <tr class="hover:bg-slate-50 transition divide-x divide-slate-200">
                        <td class="py-3 px-3 text-center">
                            <input
                                type="checkbox"
                                name="selected_products[]"
                                value="{{ $id }}"
                                class="product-checkbox rounded border-slate-300"
                            >
                        </td>
                        <td class="py-3 px-4">
                            <a
                                href="{{ $workspaceUrl }}"
                                class="font-bold text-slate-900 hover:text-blue-600 hover:underline block leading-tight"
                            >
                                {{ $name }}
                            </a>
                            <span class="text-[11px] text-slate-400 block font-normal mt-0.5">
                                Code:
                                <strong class="text-slate-600">{{ $code }}</strong>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-slate-600 font-medium">
                            {{ $productType }}
                        </td>
                        <td class="py-3 px-4 text-slate-600">
                            {{ $cat }}
                        </td>
                        <td class="py-3 px-4 font-semibold text-slate-900">
                            {{ $stock }} Units
                        </td>
                        <td class="py-3 px-4 text-slate-700">
                            {{ $pricing }}
                        </td>
                        <td class="py-3 px-4 font-mono font-semibold text-slate-900">
                            ₱{{ $price }}
                        </td>
                        <td class="py-3 px-4 text-slate-500">
                            {{ $tax }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2.5 py-1 bg-white text-slate-700 rounded-full text-[10px] font-bold uppercase tracking-wide border border-slate-200">
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center whitespace-nowrap">
                            <div class="inline-flex items-center space-x-1 justify-center">
                                <!-- VIEW BUTTON -->
                                <a
                                    href="{{ route('products.workspace', ['id' => $id, 'mode' => 'view']) }}"
                                    class="border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-medium px-2.5 py-1 rounded text-[11px] transition inline-block shadow-sm"
                                >
                                    View
                                </a>

                                <!-- MORE DROPDOWN MENU -->
                                <div class="relative inline-block text-left">
                                    <button
                                        onclick="toggleProductRowMenu(event, 'productMenu-{{ $id }}')"
                                        type="button"
                                        class="border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-medium px-2 py-1 rounded text-[11px] transition inline-flex items-center space-x-1 shadow-sm cursor-pointer"
                                    >
                                        <span>More</span>
                                        <i class="fa-solid fa-chevron-down text-[9px]"></i>
                                    </button>

                                    <div
                                        id="productMenu-{{ $id }}"
                                        class="product-row-menu hidden absolute right-0 mt-1.5 w-44 bg-white border border-slate-200 rounded-lg shadow-xl py-1 z-50 text-left text-xs font-medium text-slate-700"
                                    >
                                        <a href="{{ route('products.workspace', $id) }}" class="block px-4 py-2 hover:bg-slate-50">
                                            View Workspace
                                        </a>
                                        <a href="{{ route('products.workspace', ['id' => $id, 'mode' => 'edit']) }}" class="block px-4 py-2 hover:bg-slate-50">
                                            Edit / New Revision
                                        </a>

                                        <!-- DUPLICATE FORM -->
                                        <form action="{{ url('/products/' . $id . '/duplicate') }}" method="POST" class="block">
                                            @csrf
                                            <button type="submit" class="w-full text-left px-4 py-2 hover:bg-slate-50 cursor-pointer">
                                                Duplicate Product
                                            </button>
                                        </form>

                                        <a href="{{ route('products.workspace', $id) }}?tab=11" class="block px-4 py-2 hover:bg-slate-50">
                                            Usage &amp; Performance
                                        </a>

                                        <div class="border-t border-slate-100 my-1"></div>

                                        <!-- ARCHIVE FORM -->
                                        <form action="{{ url('/products/' . $id . '/archive') }}" method="POST" class="block" onsubmit="return confirm('Are you sure you want to archive this product?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="w-full text-left px-4 py-2 text-red-600 hover:bg-red-50 font-medium cursor-pointer">
                                                Archive Product
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="10" class="py-8 text-center text-slate-400">
                        No products found in database.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

{{-- Custom Pagination Footer --}}
<div class="bg-white border border-slate-200 rounded-lg px-4 py-3 flex flex-wrap items-center justify-between gap-4 mt-4 shadow-sm text-xs text-slate-700">
    <!-- Results Count Info -->
    <div>
        Showing <span class="font-semibold">{{ $products->firstItem() ?? 0 }}</span> to <span class="font-semibold">{{ $products->lastItem() ?? 0 }}</span> of <span class="font-semibold">{{ $products->total() }}</span> results
    </div>

    <!-- Records per page & Page Numbers -->
    <div class="flex items-center space-x-6">
        <!-- Records per page dropdown -->
        <div class="flex items-center space-x-2">
            <span class="text-slate-500">Records per page</span>
            <select 
                onchange="window.location.href = this.value"
                class="border border-slate-300 rounded px-2 py-1 bg-white text-xs outline-none focus:border-blue-500"
            >
                <option value="{{ $products->appends(['per_page' => 10])->url(1) }}" {{ $products->perPage() == 10 ? 'selected' : '' }}>10</option>
                <option value="{{ $products->appends(['per_page' => 25])->url(1) }}" {{ $products->perPage() == 25 ? 'selected' : '' }}>25</option>
                <option value="{{ $products->appends(['per_page' => 50])->url(1) }}" {{ $products->perPage() == 50 ? 'selected' : '' }}>50</option>
                <option value="{{ $products->appends(['per_page' => 100])->url(1) }}" {{ $products->perPage() == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>

        <!-- Custom Dark Pagination Links -->
        @if ($products->hasPages())
            <div class="inline-flex items-center bg-slate-900 rounded-lg overflow-hidden shadow-sm">
                {{-- Previous Page Button --}}
                @if ($products->onFirstPage())
                    <span class="px-3 py-2 text-slate-500 cursor-not-allowed bg-slate-900 border-r border-slate-800">
                        <i class="fa-solid fa-chevron-left text-[10px]"></i>
                    </span>
                @else
                    <a href="{{ $products->previousPageUrl() }}" class="px-3 py-2 text-white hover:bg-slate-800 transition border-r border-slate-800">
                        <i class="fa-solid fa-chevron-left text-[10px]"></i>
                    </a>
                @endif

                {{-- Page Numbers --}}
                @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                    @if ($page == $products->currentPage())
                        <span class="px-3 py-2 bg-slate-800 text-white font-bold border-r border-slate-700">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}" class="px-3 py-2 text-slate-300 hover:bg-slate-800 hover:text-white transition border-r border-slate-800">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach

                {{-- Next Page Button --}}
                @if ($products->hasMorePages())
                    <a href="{{ $products->nextPageUrl() }}" class="px-3 py-2 text-white hover:bg-slate-800 transition">
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </a>
                @else
                    <span class="px-3 py-2 text-slate-500 cursor-not-allowed bg-slate-900">
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </span>
                @endif
            </div>
        @endif
    </div>
</div>


{{-- 8. WHAT IS A PRODUCT MODAL --}}
<div
    id="whatIsProductModal"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex justify-center items-center p-4 z-[9999]"
>
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-8 text-base space-y-5 relative z-[10000]">
        <div class="flex justify-between items-center border-b pb-4">
            <h3 class="text-xl font-bold text-slate-900">
                What is a Product?
            </h3>
            <button
                type="button"
                onclick="document.getElementById('whatIsProductModal').classList.add('hidden')"
                class="text-slate-400 hover:text-slate-600 text-2xl font-bold cursor-pointer"
            >
                &times;
            </button>
        </div>
        <p class="text-slate-700 leading-relaxed text-base font-normal text-justify">
            A <strong class="font-bold text-slate-900">Product</strong> defines a standardized offering in your catalog with inventory tracking, pricing models, requirements, and workflow activities.
        </p>
        <div class="flex justify-end pt-3 border-t">
            <button
                type="button"
                onclick="document.getElementById('whatIsProductModal').classList.add('hidden')"
                class="px-6 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-semibold text-sm rounded-lg shadow-md cursor-pointer transition"
            >
                Got it
            </button>
        </div>
    </div>
</div>

{{-- 9. QUICK ADD PRODUCT MODAL --}}
<div
    id="quickAddModal"
    class="fixed inset-0 w-screen h-screen bg-slate-900/60 backdrop-blur-sm hidden flex justify-center items-center p-6 z-[9999]"
>
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full p-8 text-sm space-y-5 relative z-[10000] max-h-[92vh] overflow-y-auto">
        <div class="flex justify-between items-center border-b pb-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Quick Add Product</h3>
                <p class="text-xs text-slate-500 mt-1">Create a draft product shell to configure in workspace.</p>
            </div>
            <button
                type="button"
                onclick="document.getElementById('quickAddModal').classList.add('hidden')"
                class="text-slate-400 hover:text-slate-600 text-2xl font-bold cursor-pointer"
            >
                &times;
            </button>
        </div>
        <form
            action="{{ route('products.store') }}"
            method="POST"
            class="space-y-5"
        >
            @csrf
            <div>
                <label class="block font-semibold text-slate-700 mb-1.5 text-xs uppercase tracking-wide">Product Name *</label>
                <input
                    type="text"
                    name="name"
                    required
                    placeholder="e.g. Stock Certificate Printing"
                    class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm outline-none focus:border-blue-500"
                >
            </div>

            {{-- PRODUCT TYPE FIELD (May custom 'Other' option at hidden input) --}}
            <div>
                <label class="block font-semibold text-slate-700 mb-1.5 text-xs uppercase tracking-wide">Product Type *</label>
                <select
                    name="product_type"
                    id="productTypeSelect"
                    onchange="handleProductTypeChange(this)"
                    required
                    class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm outline-none focus:border-blue-500 bg-white"
                >
                    <option value="">Select product type</option>
                    <option value="Service">Service</option>
                    <option value="Bundle">Bundle</option>
                    <option value="Package">Package</option>
                    <option value="Physical Product">Physical Product</option>
                    <option value="Digital Product">Digital Product</option>
                    <option value="Other">Other</option>
                </select>
                
                {{-- Hidden input na lilitaw lang kapag 'Other' ang pinili --}}
                <div id="customProductTypeContainer" class="hidden mt-2">
                    <input
                        type="text"
                        name="custom_product_type"
                        id="customProductTypeInput"
                        placeholder="Enter new product type..."
                        class="w-full border border-slate-300 rounded-lg px-3.5 py-2 text-sm outline-none focus:border-blue-500 bg-slate-50"
                    >
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                {{-- SERVICE AREA --}}
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5 text-xs uppercase tracking-wide">Service Area *</label>
                    <select
                        name="service_area"
                        id="serviceAreaSelect"
                        onchange="handleServiceAreaChange(this)"
                        required
                        class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm outline-none focus:border-blue-500 bg-white"
                    >
                        <option value="">-- Select Service Area --</option>
                        <option value="Corporate & Regulatory Advisory">Corporate & Regulatory Advisory</option>
                        <option value="Governance & Policy Advisory">Governance & Policy Advisory</option>
                        <option value="People & Talent Solutions">People & Talent Solutions</option>
                        <option value="Strategic Situations Advisory">Strategic Situations Advisory</option>
                        <option value="Accounting & Compliance Advisory">Accounting & Compliance Advisory</option>
                        <option value="Business Strategy & Process Advisory">Business Strategy & Process Advisory</option>
                        <option value="Learning & Capability Development">Learning & Capability Development</option>
                        <option value="Others">Others</option>
                        <option value="None">None</option>
                        <option value="Service Add-Ons">Service Add-Ons</option>
                    </select>
                    <div id="customServiceAreaContainer" class="hidden mt-2">
                        <input
                            type="text"
                            name="custom_service_area"
                            id="customServiceAreaInput"
                            placeholder="Enter new service area..."
                            class="w-full border border-slate-300 rounded-lg px-3.5 py-2 text-sm outline-none focus:border-blue-500 bg-slate-50"
                        >
                    </div>
                    <button
                        type="button"
                        onclick="toggleCustomField('serviceAreaSelect', 'customServiceAreaContainer', 'Others')"
                        class="text-blue-600 hover:underline text-[11px] font-semibold mt-1 inline-block cursor-pointer"
                    >
                        + Add New Service Area
                    </button>
                </div>
                {{-- CATEGORY --}}
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5 text-xs uppercase tracking-wide">Category *</label>
                    <select
                        name="category"
                        id="categorySelect"
                        onchange="handleCategoryChange(this)"
                        required
                        class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm outline-none focus:border-blue-500 bg-white"
                    >
                        <option value="">-- Select Category --</option>
                        <option value="Professional Fees">Professional Fees</option>
                        <option value="Consulting Revenue">Consulting Revenue</option>
                        <option value="Accounting Services">Accounting Services</option>
                        <option value="Tax Services">Tax Services</option>
                        <option value="Corporate Services">Corporate Services</option>
                        <option value="HR Services">HR Services</option>
                        <option value="Training & Development">Training & Development</option>
                        <option value="Other Income">Other Income</option>
                        <option value="Other">Other</option>
                    </select>
                    <div id="customCategoryContainer" class="hidden mt-2">
                        <input
                            type="text"
                            name="custom_category"
                            id="customCategoryInput"
                            placeholder="Enter new category..."
                            class="w-full border border-slate-300 rounded-lg px-3.5 py-2 text-sm outline-none focus:border-blue-500 bg-slate-50"
                        >
                    </div>
                    <button
                        type="button"
                        onclick="toggleCustomField('categorySelect', 'customCategoryContainer', 'Other')"
                        class="text-blue-600 hover:underline text-[11px] font-semibold mt-1 inline-block cursor-pointer"
                    >
                        + Add New Category
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5 text-xs uppercase tracking-wide">Pricing Type *</label>
                    <select
                        name="pricing_type"
                        required
                        class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm outline-none focus:border-blue-500 bg-white"
                    >
                        <option value="">-- Select Pricing Type --</option>
                        <option value="Fixed">Fixed</option>
                        <option value="Variable">Variable</option>
                        <option value="Tiered">Tiered</option>
                        <option value="Subscription">Subscription</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5 text-xs uppercase tracking-wide">Tax Treatment *</label>
                    <select
                        name="tax_treatment"
                        required
                        class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm outline-none focus:border-blue-500 bg-white"
                    >
                        <option value="">-- Select Tax Treatment --</option>
                        <option value="VAT Exclusive">VAT Exclusive</option>
                        <option value="VAT Inclusive">VAT Inclusive</option>
                        <option value="No Tax">No Tax</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                {{-- INVENTORY TYPE --}}
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5 text-xs uppercase tracking-wide">Inventory Type *</label>
                    <select
                        name="inventory_type"
                        id="inventoryTypeSelect"
                        onchange="handleInventoryTypeChange(this)"
                        required
                        class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm outline-none focus:border-blue-500 bg-white"
                    >
                        <option value="">Select inventory type</option>
                        <option value="Non-Inventory">Non-Inventory</option>
                        <option value="Inventory">Inventory</option>
                        <option value="Service">Service</option>
                        <option value="Other">Other</option>
                    </select>
                    <div id="customInventoryContainer" class="hidden mt-2">
                        <input
                            type="text"
                            name="custom_inventory_type"
                            id="customInventoryInput"
                            placeholder="Enter new inventory type..."
                            class="w-full border border-slate-300 rounded-lg px-3.5 py-2 text-sm outline-none focus:border-blue-500 bg-slate-50"
                        >
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5 text-xs uppercase tracking-wide">Initial Stock *</label>
                    <input
                        type="number"
                        name="inventory_stock"
                        value="0"
                        min="0"
                        required
                        class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm outline-none focus:border-blue-500 bg-white"
                    >
                </div>
            </div>
            <div>
                <label class="block font-semibold text-slate-700 mb-1.5 text-xs uppercase tracking-wide">Description (Optional)</label>
                <textarea
                    name="description"
                    rows="3"
                    placeholder="Brief summary for internal catalog..."
                    class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm outline-none focus:border-blue-500"
                ></textarea>
            </div>
            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button
                    type="button"
                    onclick="document.getElementById('quickAddModal').classList.add('hidden')"
                    class="px-5 py-2 border border-slate-300 text-slate-700 font-medium text-xs rounded-lg hover:bg-slate-50 transition cursor-pointer"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs rounded-lg shadow-sm transition cursor-pointer"
                >
                    Save Product
                </button>
            </div>
        </form>
    </div>
</div>

{{-- JavaScript para umandar ang pag-hide/show ng custom input kapag pinili ang 'Other' --}}
<script>
    function handleProductTypeChange(select) {
        const container = document.getElementById('customProductTypeContainer');
        const input = document.getElementById('customProductTypeInput');
        if (select.value === 'Other') {
            container.classList.remove('hidden');
            input.required = true;
        } else {
            container.classList.add('hidden');
            input.required = false;
            input.value = '';
        }
    }
</script>


{{-- 10. IMPORT PRODUCT MODAL --}}
<div
    id="importProductModal"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex justify-center items-center p-4 z-[9999]"
>
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 space-y-5 relative z-[10000]">
        <div class="flex justify-between items-center border-b pb-4">
            <h3 class="text-base font-bold text-slate-900">
                Import Product Specification
            </h3>
            <button
                type="button"
                onclick="document.getElementById('importProductModal').classList.add('hidden')"
                class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer"
            >
                &times;
            </button>
        </div>
        <form
            action="{{ route('products.import.store') }}"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-4"
        >
            @csrf
            <div>
                <label class="block font-semibold text-slate-700 mb-1.5 text-xs uppercase tracking-wide">Select File *</label>
                <input
                    type="file"
                    name="file"
                    required
                    class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-300 rounded-lg cursor-pointer"
                >
                <p class="text-[11px] text-slate-500 mt-1">Supported formats: CSV, XLSX, JSON</p>
            </div>
            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button
                    type="button"
                    onclick="document.getElementById('importProductModal').classList.add('hidden')"
                    class="px-4 py-2 border border-slate-300 text-slate-700 font-medium text-xs rounded-lg hover:bg-slate-50 transition cursor-pointer"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs rounded-lg shadow-sm transition cursor-pointer"
                >
                    Upload & Import
                </button>
            </div>
        </form>
    </div>
</div>


<script>
function toggleBulkActionsMenu(event) {
    event.stopPropagation();
    const menu = document.getElementById('bulkActionsMenu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
}
function toggleProductRowMenu(event, menuId) {
    event.stopPropagation();
    
    document.querySelectorAll('.product-row-menu').forEach(menu => {
        if (menu.id !== menuId) {
            menu.classList.add('hidden');
        }
    });
    const targetMenu = document.getElementById(menuId);
    if (targetMenu) {
        targetMenu.classList.toggle('hidden');
    }
}
function toggleSelectAll(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = masterCheckbox.checked;
    });
}
function handleServiceAreaChange(selectElement) {
    const customContainer = document.getElementById('customServiceAreaContainer');
    const customInput = document.getElementById('customServiceAreaInput');
    if (selectElement.value === 'Others') {
        customContainer.classList.remove('hidden');
        customInput.required = true;
    } else {
        customContainer.classList.add('hidden');
        customInput.required = false;
        customInput.value = '';
    }
}
function handleCategoryChange(selectElement) {
    const customContainer = document.getElementById('customCategoryContainer');
    const customInput = document.getElementById('customCategoryInput');
    if (selectElement.value === 'Other') {
        customContainer.classList.remove('hidden');
        customInput.required = true;
    } else {
        customContainer.classList.add('hidden');
        customInput.required = false;
        customInput.value = '';
    }
}
function handleInventoryTypeChange(selectElement) {
    const customContainer = document.getElementById('customInventoryContainer');
    const customInput = document.getElementById('customInventoryInput');
    if (selectElement.value === 'Other') {
        customContainer.classList.remove('hidden');
        customInput.required = true;
    } else {
        customContainer.classList.add('hidden');
        customInput.required = false;
        customInput.value = '';
    }
}
function toggleCustomField(selectId, containerId, otherValue) {
    const select = document.getElementById(selectId);
    const container = document.getElementById(containerId);
    const input = container.querySelector('input');
    
    select.value = otherValue;
    container.classList.remove('hidden');
    input.required = true;
    input.focus();
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('#bulkActionsMenu')) {
        document.getElementById('bulkActionsMenu')?.classList.add('hidden');
    }
    if (!e.target.closest('.product-row-menu') && !e.target.closest('button[onclick*="toggleProductRowMenu"]')) {
        document.querySelectorAll('.product-row-menu').forEach(menu => {
            menu.classList.add('hidden');
        });
    }
});

function openFieldModal(fieldType) {
    document.getElementById('createFieldMenu').classList.add('hidden');

    document.getElementById('modalFieldTypeName').innerText = fieldType;
    document.getElementById('modalFieldType').value = fieldType;

    const picklistContainer = document.getElementById('picklistOptionsContainer');

    if (fieldType === 'Picklist') {
        picklistContainer.classList.remove('hidden');
    } else {
        picklistContainer.classList.add('hidden');
    }

    // Get the selected product from the product checkbox.
    const selectedProduct = document.querySelector('.product-checkbox:checked');

    const productId = selectedProduct
        ? selectedProduct.value
        : '';

    document.getElementById('customFieldProductId').value = productId;

    document.getElementById('createFieldModal').classList.remove('hidden');
}

function closeFieldModal() {
    document.getElementById('createFieldModal').classList.add('hidden');
}

function submitCustomField(event) {
    event.preventDefault();

    const form = document.getElementById('createCustomFieldForm');
    const productId = document.getElementById('customFieldProductId').value;

    if (!productId) {
        alert('Please select a product first.');
        return;
    }

    form.action = `/products/${encodeURIComponent(productId)}/custom-fields`;

    form.submit();
}


</script>
@endsection

@extends('layouts.app')

@section('content')

<div class="w-full max-w-[1320px] mx-auto px-5 space-y-4">

    <!-- ===================================================== -->
    <!-- HEADER -->
    <!-- ===================================================== -->

    <div class="bg-white border border-slate-200 rounded-xl px-5 py-4 shadow-sm">
        <div class="flex justify-between items-center gap-4">

            <div class="min-w-0">
                <div class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider mb-1">
                    PRODUCTS / Reports
                </div>

                <h1 class="text-lg font-bold text-slate-900">
                    Product Performance Report
                </h1>

                <p class="text-[11px] text-slate-500 mt-0.5">
                    System-generated product performance based on catalog configuration and inventory records.
                </p>
            </div>

            <a
                href="{{ route('products.index') }}"
                class="shrink-0 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-[11px] px-3 py-2 rounded-lg shadow-sm transition flex items-center gap-2"
            >
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>

        </div>
    </div>


    <!-- ===================================================== -->
    <!-- REPORT FILTERS -->
    <!-- ===================================================== -->

    <form
        method="GET"
        action="{{ route('products.reports') }}"
        class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden"
    >

        <div class="px-4 py-3 border-b border-slate-200">
            <h3 class="font-semibold text-slate-800 text-xs">
                Report Filters
            </h3>
        </div>

        <div class="p-3 space-y-3">

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">

                <!-- FROM DATE -->
                <div>
                    <label class="block text-[10px] font-medium text-slate-500 mb-1">
                        From Date
                    </label>

                    <input
                        type="date"
                        name="from_date"
                        value="{{ request('from_date', '') }}"
                        class="w-full min-w-0 border border-slate-300 rounded-lg px-2 py-2 text-[11px] text-slate-700 bg-white focus:border-blue-500 outline-none"
                    >
                </div>

                <!-- TO DATE -->
                <div>
                    <label class="block text-[10px] font-medium text-slate-500 mb-1">
                        To Date
                    </label>

                    <input
                        type="date"
                        name="to_date"
                        value="{{ request('to_date', '') }}"
                        class="w-full min-w-0 border border-slate-300 rounded-lg px-2 py-2 text-[11px] text-slate-700 bg-white focus:border-blue-500 outline-none"
                    >
                </div>

                <!-- SERVICE AREA -->
                <div>
                    <label class="block text-[10px] font-medium text-slate-500 mb-1">
                        Service Area
                    </label>

                    <select
                        name="service_area"
                        class="w-full min-w-0 border border-slate-300 rounded-lg px-2 py-2 text-[11px] text-slate-700 bg-white focus:border-blue-500 outline-none"
                    >
                        <option value="">All Service Areas</option>

                        <option value="Corporate & Regulatory Advisory" {{ request('service_area') === 'Corporate & Regulatory Advisory' ? 'selected' : '' }}>Corporate & Regulatory Advisory</option>

                        <option value="Governance & Policy Advisory" {{ request('service_area') === 'Governance & Policy Advisory' ? 'selected' : '' }}>Governance & Policy Advisory</option>

                        <option value="People & Talent Solutions" {{ request('service_area') === 'People & Talent Solutions' ? 'selected' : '' }}>People & Talent Solutions</option>

                        <option value="Strategic Situations Advisory" {{ request('service_area') === 'Strategic Situations Advisory' ? 'selected' : '' }}>Strategic Situations Advisory</option>

                        <option value="Accounting & Compliance Advisory" {{ request('service_area') === 'Accounting & Compliance Advisory' ? 'selected' : '' }}>Accounting & Compliance Advisory</option>

                        <option value="Business Strategy & Process Advisory" {{ request('service_area') === 'Business Strategy & Process Advisory' ? 'selected' : '' }}>Business Strategy & Process Advisory</option>

                        <option value="Learning & Capability Development" {{ request('service_area') === 'Learning & Capability Development' ? 'selected' : '' }}>Learning & Capability Development</option>

                        <option value="Others" {{ request('service_area') === 'Others' ? 'selected' : '' }}>Others</option>

                        <option value="None" {{ request('service_area') === 'None' ? 'selected' : '' }}>None</option>

                        <option value="Service Add-Ons" {{ request('service_area') === 'Service Add-Ons' ? 'selected' : '' }}>Service Add-Ons</option>
                    </select>
                </div>

                <!-- CATEGORY -->
                <div>
                    <label class="block text-[10px] font-medium text-slate-500 mb-1">
                        Category
                    </label>

                    <select
                        name="category"
                        class="w-full min-w-0 border border-slate-300 rounded-lg px-2 py-2 text-[11px] text-slate-700 bg-white focus:border-blue-500 outline-none"
                    >
                        <option value="">All Categories</option>

                        <option value="Professional Fees" {{ request('category') === 'Professional Fees' ? 'selected' : '' }}>Professional Fees</option>

                        <option value="Consulting Revenue" {{ request('category') === 'Consulting Revenue' ? 'selected' : '' }}>Consulting Revenue</option>

                        <option value="Accounting Services" {{ request('category') === 'Accounting Services' ? 'selected' : '' }}>Accounting Services</option>

                        <option value="Tax Services" {{ request('category') === 'Tax Services' ? 'selected' : '' }}>Tax Services</option>

                        <option value="Corporate Services" {{ request('category') === 'Corporate Services' ? 'selected' : '' }}>Corporate Services</option>

                        <option value="HR Services" {{ request('category') === 'HR Services' ? 'selected' : '' }}>HR Services</option>

                        <option value="Training & Development" {{ request('category') === 'Training & Development' ? 'selected' : '' }}>Training & Development</option>

                        <option value="Other Income" {{ request('category') === 'Other Income' ? 'selected' : '' }}>Other Income</option>

                        <option value="Other" {{ request('category') === 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <!-- PRICING TYPE -->
                <div>
                    <label class="block text-[10px] font-medium text-slate-500 mb-1">
                        Pricing Type
                    </label>

                    <select
                        name="pricing_type"
                        class="w-full min-w-0 border border-slate-300 rounded-lg px-2 py-2 text-[11px] text-slate-700 bg-white focus:border-blue-500 outline-none"
                    >
                        <option value="">All Pricing Types</option>

                        <option value="Fixed" {{ request('pricing_type') === 'Fixed' ? 'selected' : '' }}>Fixed</option>

                        <option value="Variable" {{ request('pricing_type') === 'Variable' ? 'selected' : '' }}>Variable</option>

                        <option value="Tiered" {{ request('pricing_type') === 'Tiered' ? 'selected' : '' }}>Tiered</option>

                        <option value="Subscription" {{ request('pricing_type') === 'Subscription' ? 'selected' : '' }}>Subscription</option>
                    </select>
                </div>

                <!-- STATUS -->
                <div>
                    <label class="block text-[10px] font-medium text-slate-500 mb-1">
                        Status
                    </label>

                    <select
                        name="status"
                        class="w-full min-w-0 border border-slate-300 rounded-lg px-2 py-2 text-[11px] text-slate-700 bg-white focus:border-blue-500 outline-none"
                    >
                        <option value="">All Statuses</option>

                        <option value="incomplete" {{ request('status') === 'incomplete' ? 'selected' : '' }}>Incomplete</option>

                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>

                        <option value="for_approval" {{ request('status') === 'for_approval' ? 'selected' : '' }}>For Approval</option>

                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>

                        <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>

            </div>

            <!-- FILTER ACTIONS -->
            <div class="flex justify-end items-center gap-2 pt-2 border-t border-slate-100">

                <a
                    href="{{ route('products.reports') }}"
                    class="px-3 py-2 border border-slate-300 text-slate-600 font-semibold text-[11px] rounded-lg hover:bg-slate-50 transition"
                >
                    Reset
                </a>

                <button
                    type="submit"
                    class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-[11px] rounded-lg shadow-sm transition"
                >
                    Apply
                </button>

            </div>

        </div>

    </form>


    <!-- ===================================================== -->
    <!-- METRICS CARDS -->
    <!-- ===================================================== -->

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

        <!-- TOTAL PRODUCTS -->
        <div class="bg-white border border-slate-200 p-3.5 rounded-xl shadow-sm">
            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">
                Total Products
            </span>

            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl font-bold text-slate-900">
                    {{ $totalProducts ?? 0 }}
                </span>

                <span class="text-[10px] text-slate-500">
                    products
                </span>
            </div>
        </div>

        <!-- ACTIVE PRODUCTS -->
        <div class="bg-white border border-slate-200 p-3.5 rounded-xl shadow-sm">
            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">
                Active Products
            </span>

            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl font-bold text-emerald-600">
                    {{ $activeProducts ?? 0 }}
                </span>

                <span class="text-[10px] text-slate-500">
                    active
                </span>
            </div>
        </div>

        <!-- ARCHIVED PRODUCTS -->
        <div class="bg-white border border-slate-200 p-3.5 rounded-xl shadow-sm">
            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">
                Archived Products
            </span>

            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl font-bold text-slate-700">
                    {{ $archivedProducts ?? 0 }}
                </span>

                <span class="text-[10px] text-slate-500">
                    archived
                </span>
            </div>
        </div>

    </div>


    <!-- ===================================================== -->
    <!-- PRODUCT PERFORMANCE TABLE -->
    <!-- ===================================================== -->

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">

        <!-- TABLE HEADER -->
        <div class="px-4 py-3 border-b border-slate-200 flex justify-between items-center gap-3">

            <div>
                <h3 class="font-bold text-slate-800 text-xs">
                    Product Performance Summary
                </h3>

                <p class="text-[10px] text-slate-400 mt-0.5">
                    System-generated summary of product configuration and inventory.
                </p>
            </div>

            <span class="text-[10px] text-slate-500 shrink-0">
                {{ $products->total() }} total records
            </span>

        </div>

        <!-- TABLE CONTENT -->
        <div class="overflow-x-auto">

            <table class="w-full text-left border-collapse text-[11px] divide-y divide-slate-200">

                <thead>
                    <tr class="bg-slate-50 text-[10px] font-semibold text-slate-500 uppercase tracking-wider divide-x divide-slate-200">

                        <th class="py-2.5 px-3">Product</th>
                        <th class="py-2.5 px-3">Category</th>
                        <th class="py-2.5 px-3">Pricing Type</th>
                        <th class="py-2.5 px-3">Price</th>
                        <th class="py-2.5 px-3">Stock</th>
                        <th class="py-2.5 px-3">Status</th>

                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 text-slate-700 bg-white">

                    @forelse($products as $item)

                        <tr class="hover:bg-slate-50 transition divide-x divide-slate-200">

                            <!-- PRODUCT -->
                            <td class="py-2.5 px-3">
                                <a
                                    href="{{ route('products.workspace', ['id' => $item->id, 'mode' => 'view']) }}"
                                    class="font-bold text-slate-900 hover:text-blue-600 hover:underline block"
                                >
                                    {{ $item->name }}
                                </a>

                                <span class="text-[9px] text-slate-400 block mt-0.5">
                                    Code: {{ $item->sku ?? 'PRD-' . sprintf('%04d', $item->id) }}
                                </span>
                            </td>

                            <!-- CATEGORY -->
                            <td class="py-2.5 px-3 text-slate-600">
                                {{ $item->category ?? '-' }}
                            </td>

                            <!-- PRICING TYPE -->
                            <td class="py-2.5 px-3 text-slate-600">
                                {{ $item->pricing_type ?? '-' }}
                            </td>

                            <!-- PRICE -->
                            <td class="py-2.5 px-3 font-mono font-semibold text-slate-900 whitespace-nowrap">
                                ₱{{ number_format($item->price ?? 0, 2) }}
                            </td>

                            <!-- STOCK -->
                            <td class="py-2.5 px-3 font-semibold text-emerald-600 whitespace-nowrap">
                                {{ $item->inventory_stock ?? 0 }} Units
                            </td>

                            <!-- STATUS -->
                            <td class="py-2.5 px-3">
                                <span class="inline-flex px-2 py-1 bg-slate-100 text-slate-700 rounded-full text-[9px] font-bold uppercase tracking-wide border border-slate-200 whitespace-nowrap">
                                    {{ ucfirst($item->status ?? 'Draft') }}
                                </span>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="py-8 px-4 text-center text-slate-400">
                                No product records found for reports.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <!-- PAGINATION -->
        @if($products->hasPages())
            <div class="p-3 border-t border-slate-200">
                {{ $products->links() }}
            </div>
        @endif

    </div>

</div>

@endsection

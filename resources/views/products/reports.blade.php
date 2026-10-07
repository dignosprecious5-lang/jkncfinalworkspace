@extends('layouts.app')
@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex justify-between items-center">
        <div>
            <div class="text-xs text-slate-400 font-semibold uppercase tracking-wider mb-1">
                <span>PRODUCTS</span> / <span>Reports</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Product Performance Report</h1>
            <p class="text-xs text-slate-500 mt-0.5">System-generated product performance based on catalog configuration and inventory records.</p>
        </div>
        <a
            href="{{ route('products.index') }}"
            class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-xs px-4 py-2 rounded-lg shadow-sm transition flex items-center space-x-1.5"
        >
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back to Dashboard</span>
        </a>
    </div>

    <!-- Report Filters Bar (Updated with Complete Options) -->
    <form
        method="GET"
        action="{{ route('products.reports') }}"
        class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm space-y-4"
    >
        <div class="font-bold text-slate-800 text-xs uppercase tracking-wide">Report Filters</div>
        <div class="grid grid-cols-6 gap-3">
            <div>
                <label class="block text-[11px] font-medium text-slate-500 mb-1">From Date</label>
                <input
                    type="date"
                    name="from_date"
                    value="{{ request('from_date', '') }}"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs text-slate-700 bg-white"
                >
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-500 mb-1">To Date</label>
                <input
                    type="date"
                    name="to_date"
                    value="{{ request('to_date', '') }}"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs text-slate-700 bg-white"
                >
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-500 mb-1">Service Area</label>
                <select
                    name="service_area"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs text-slate-700 bg-white"
                >
                    <option value="">Service Area: All</option>
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
            <div>
                <label class="block text-[11px] font-medium text-slate-500 mb-1">Category</label>
                <select
                    name="category"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs text-slate-700 bg-white"
                >
                    <option value="">Category: All</option>
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
            <div>
                <label class="block text-[11px] font-medium text-slate-500 mb-1">Pricing Type</label>
                <select
                    name="pricing_type"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs text-slate-700 bg-white"
                >
                    <option value="">All Pricing Types</option>
                    <option value="Fixed" {{ request('pricing_type') === 'Fixed' ? 'selected' : '' }}>Fixed</option>
                    <option value="Variable" {{ request('pricing_type') === 'Variable' ? 'selected' : '' }}>Variable</option>
                    <option value="Tiered" {{ request('pricing_type') === 'Tiered' ? 'selected' : '' }}>Tiered</option>
                    <option value="Subscription" {{ request('pricing_type') === 'Subscription' ? 'selected' : '' }}>Subscription</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-500 mb-1">Status</label>
                <select
                    name="status"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs text-slate-700 bg-white"
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
        <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100">
            <a
                href="{{ route('products.reports') }}"
                class="px-4 py-2 border border-slate-300 text-slate-600 font-semibold text-xs rounded-lg hover:bg-slate-50 transition"
            >
                Reset
            </a>
            <button
                type="submit"
                class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-lg shadow-sm transition"
            >
                Apply Filters
            </button>
        </div>
    </form>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white border border-slate-200 p-4 rounded-lg shadow-sm">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">TOTAL PRODUCTS</span>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $totalProducts }} <span class="text-xs font-normal text-slate-500">products</span></div>
        </div>
        <div class="bg-white border border-slate-200 p-4 rounded-lg shadow-sm">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">ACTIVE PRODUCTS</span>
            <div class="text-2xl font-bold text-emerald-600 mt-1">{{ $activeProducts }} <span class="text-xs font-normal text-slate-500">active</span></div>
        </div>
        <div class="bg-white border border-slate-200 p-4 rounded-lg shadow-sm">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">ARCHIVED PRODUCTS</span>
            <div class="text-2xl font-bold text-slate-600 mt-1">{{ $archivedProducts ?? 0 }} <span class="text-xs font-normal text-slate-500">archived</span></div>
        </div>
    </div>

    <!-- Performance Table -->
    <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-x-auto">
        <div class="px-4 py-3 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 text-sm">Product Performance Summary</h3>
            <span class="text-xs text-slate-500">{{ $products->total() }} total records</span>
        </div>
        <table class="w-full text-left border-collapse text-xs divide-y divide-slate-200">
            <thead>
                <tr class="bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider divide-x divide-slate-200">
                    <th class="py-3 px-4">PRODUCT</th>
                    <th class="py-3 px-4">CATEGORY</th>
                    <th class="py-3 px-4">PRICING TYPE</th>
                    <th class="py-3 px-4">PRICE</th>
                    <th class="py-3 px-4">STOCK</th>
                    <th class="py-3 px-4">STATUS</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-slate-700 bg-white">
                @forelse($products as $item)
                    <tr class="hover:bg-slate-50 transition divide-x divide-slate-200">
                        <td class="py-3 px-4">
                            <a
                                href="{{ route('products.workspace', ['id' => $item->id, 'mode' => 'view']) }}"
                                class="font-bold text-slate-900 hover:text-blue-600 hover:underline block"
                            >
                                {{ $item->name }}
                            </a>
                            <span class="text-[10px] text-slate-400 block mt-0.5">Code: {{ $item->sku ?? 'PRD-' . sprintf('%04d', $item->id) }}</span>
                        </td>
                        <td class="py-3 px-4 text-slate-600">{{ $item->category ?? '-' }}</td>
                        <td class="py-3 px-4 text-slate-600">{{ $item->pricing_type ?? '-' }}</td>
                        <td class="py-3 px-4 font-mono font-semibold text-slate-900">₱{{ number_format($item->price ?? 0, 2) }}</td>
                        <td class="py-3 px-4 font-semibold text-emerald-600">{{ $item->inventory_stock ?? 0 }} Units</td>
                        <td class="py-3 px-4">
                            <span class="px-2.5 py-1 bg-slate-100 text-slate-700 rounded-full text-[10px] font-bold uppercase tracking-wide border border-slate-200">
                                {{ ucfirst($item->status ?? 'Draft') }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">No product records found for reports.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($products->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
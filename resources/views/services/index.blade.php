@extends('layouts.app')

@section('content')
@php
    /**
     * Services Catalog Dashboard View
     * Fully Compliant with ORDO Services Module V2 Specification
     */

    // 1. Data Initialization & Fallbacks
    $serviceList = isset($services) ? $services : [];
    $counts = isset($allServicesCount) ? $allServicesCount : [
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
        <h1 class="text-2xl font-bold text-slate-900">Services Catalog</h1>
        <p class="text-xs text-slate-500 mt-0.5">Standardized service catalog with configurable fields, routing, scheduling, and pricing.</p>
        <button type="button" onclick="document.getElementById('whatIsServiceModal').classList.remove('hidden')" class="text-xs text-blue-600 font-semibold hover:underline mt-1 inline-block cursor-pointer">
            What is a Service?
        </button>
    </div>
    
    <div class="flex items-center space-x-2">
        {{-- REPORTS BUTTON --}}
        <button type="button" onclick="window.location.href='{{ route('services.reports') }}'" class="relative z-20 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium text-xs px-3.5 py-2 rounded shadow-sm transition flex items-center space-x-1.5 cursor-pointer">
            <i class="fa-solid fa-chart-pie text-blue-600 text-xs"></i>
            <span>Reports</span>
        </button>

        {{-- BULK ACTIONS DROPDOWN MENU --}}
        <div class="relative inline-block text-left">
            <button type="button" 
                    onclick="toggleBulkActionsMenu(event)" 
                    class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium text-xs px-3.5 py-2 rounded shadow-sm transition flex items-center space-x-1.5 cursor-pointer">
                <i class="fa-solid fa-layer-group text-slate-500 text-xs"></i>
                <span>Bulk Actions</span>
                <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-1"></i>
            </button>

            <div id="bulkActionsMenu" 
                 class="hidden absolute right-0 mt-1.5 w-52 bg-white rounded-lg shadow-xl border border-slate-200 py-1.5 z-50 text-xs font-medium text-slate-700 divide-y divide-slate-100">
                
                <!-- Bulk Import Services Option -->
                <button type="button" 
                        onclick="document.getElementById('importServiceModal').classList.remove('hidden'); document.getElementById('bulkActionsMenu').classList.add('hidden')" 
                        class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center space-x-2.5 transition text-slate-700 cursor-pointer">
                    <i class="fa-solid fa-file-import text-blue-600 w-4 text-center"></i>
                    <span>Bulk Import Services</span>
                </button>

                <!-- Bulk Export Options Group -->
                <div class="py-1">
                    <!-- Export to CSV -->
                    <a href="{{ Route::has('services.export_all') ? route('services.export_all', ['format' => 'csv']) : url('/services/export-all?format=csv') }}" 
                       onclick="document.getElementById('bulkActionsMenu').classList.add('hidden')"
                       class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center space-x-2.5 transition text-slate-700 cursor-pointer block">
                        <i class="fa-solid fa-file-csv text-emerald-600 w-4 text-center"></i>
                        <span>Export to CSV</span>
                    </a>

                    <!-- Export to PDF -->
                    <a href="{{ Route::has('services.export_all') ? route('services.export_all', ['format' => 'pdf']) : url('/services/export-all?format=pdf') }}" 
                       onclick="document.getElementById('bulkActionsMenu').classList.add('hidden')"
                       class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center space-x-2.5 transition text-slate-700 cursor-pointer block">
                        <i class="fa-solid fa-file-pdf text-rose-600 w-4 text-center"></i>
                        <span>Export to PDF</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- QUICK ADD BUTTON --}}
        <button onclick="document.getElementById('quickAddModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-4 py-2 rounded shadow-sm transition flex items-center space-x-1 cursor-pointer">
            <span>+ Quick Add</span>
        </button>
    </div>
</div>

{{-- 4. Detailed Insights Cards (Updated to Grid to display all dynamic metrics) --}}
<div class="grid grid-cols-3 gap-4 mb-6">
    @php
        $mostPurchasedName = $insightData['most_purchased']['name'] ?? 'No data yet';
        $purchasedCount = $insightData['most_purchased']['count'] ?? 0;

        $highestName = $insightData['highest_revenue']['name'] ?? 'No data yet';
        $highestPrice = isset($insightData['highest_revenue']['amount']) ? number_format($insightData['highest_revenue']['amount'], 2) : '0.00';

        $laborName = $insightData['most_labor_intensive']['name'] ?? 'No data yet';
        $laborHours = $insightData['most_labor_intensive']['hours'] ?? 0;

        $discountName = $insightData['most_discounted']['name'] ?? 'No data yet';
        $maxDiscount = $insightData['most_discounted']['events'] ?? 0;

        $marginName = $insightData['highest_margin']['name'] ?? 'No data yet';
        $marginPct = $insightData['highest_margin']['percentage'] ?? 0;

        $fastestName = $insightData['fastest_growing']['name'] ?? 'No data yet';
        $fastestRate = $insightData['fastest_growing']['rate'] ?? 'No growth data';

        $decliningName = $insightData['declining_services']['name'] ?? 'No data yet';
        $decliningTrend = $insightData['declining_services']['trend'] ?? 'No trend data';
    @endphp

    {{-- Most Purchased Service --}}
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Most Purchased Service</span>
        <p class="font-bold text-slate-900 text-sm truncate" title="{{ $mostPurchasedName }}">{{ $mostPurchasedName }}</p>
        <div class="text-xs text-slate-700 font-semibold mt-1">{{ $purchasedCount }} Active Contracts</div>
    </div>

    {{-- Highest Priced Service --}}
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Highest Priced Service</span>
        <p class="font-bold text-slate-900 text-sm truncate" title="{{ $highestName }}">{{ $highestName }}</p>
        <div class="text-xs text-slate-800 font-semibold mt-1">₱{{ $highestPrice }}</div>
    </div>

    {{-- Most Labor Intensive --}}
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Most Labor Intensive</span>
        <p class="font-bold text-slate-900 text-sm truncate" title="{{ $laborName }}">{{ $laborName }}</p>
        <div class="text-xs text-slate-700 font-semibold mt-1">{{ $laborHours }} Expected Hours</div>
    </div>

    {{-- Most Discounted Service --}}
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Most Discounted Service</span>
        <p class="font-bold text-slate-900 text-sm truncate" title="{{ $discountName }}">{{ $discountName }}</p>
        <div class="text-xs text-slate-700 font-semibold mt-1">{{ $maxDiscount }} Max Discount</div>
    </div>

    {{-- Highest Margin Service --}}
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Highest Margin Service</span>
        <p class="font-bold text-slate-900 text-sm truncate" title="{{ $marginName }}">{{ $marginName }}</p>
        <div class="text-xs text-slate-700 font-semibold mt-1">{{ $marginPct }}% Margin</div>
    </div>

    {{-- Fastest Growing / Declining Services --}}
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Fastest Growing & Inactive</span>
        <p class="font-bold text-slate-900 text-sm truncate" title="{{ $fastestName }}">{{ $fastestName }}</p>
        <div class="text-xs text-slate-700 font-semibold mt-1">{{ $fastestRate }} | {{ $decliningName }}</div>
    </div>
</div>

{{-- 5. Status Metrics Cards (5 Columns Filter) --}}
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
            $isSelected = request('status') === $key ? 'ring-2 ring-blue-500 font-bold bg-slate-50' : '';
        @endphp
        <a href="{{ route('services.index') }}?status={{ $key }}" class="bg-white border border-slate-200 hover:bg-slate-50 rounded p-3 transition block shadow-sm {{ $isSelected }}">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">{{ $meta['label'] }}</span>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $cnt }}</div>
        </a>
    @endforeach
</div>

{{-- 6. Dashboard Filters Bar --}}
<form method="GET" action="{{ route('services.index') }}" class="bg-white border border-slate-200 rounded-lg p-4 mb-4 shadow-sm space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
        <div class="flex items-center space-x-3">
            <span class="font-bold text-slate-800 text-sm">Dashboard Filters</span>
            <span class="text-slate-500 text-sm font-medium">From</span>
            <input type="date" name="from_date" value="{{ request('from_date', '') }}" class="border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700 bg-white">
            <span class="text-slate-500 text-sm font-medium">To</span>
            <input type="date" name="to_date" value="{{ request('to_date', '') }}" class="border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700 bg-white">
        </div>
    </div>
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center space-x-3 flex-1">
            <div class="relative flex-1 max-w-sm">
                <input type="text" name="search" value="{{ request('search', '') }}" placeholder="Search code or service name..." class="w-full border border-slate-300 rounded-lg pl-10 pr-4 py-2.5 text-sm text-slate-800 outline-none focus:border-blue-500">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400 text-sm"></i>
            </div>
            <select name="status" class="border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm text-slate-800 bg-white">
                <option value="">All Status</option>
                <option value="incomplete" {{ request('status') === 'incomplete' ? 'selected' : '' }}>Incomplete</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="for_approval" {{ request('status') === 'for_approval' ? 'selected' : '' }}>For Approval</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
            </select>
            <select name="category" class="border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm text-slate-800 bg-white">
                <option value="">All Categories</option>
                <option value="Accountancy Revenue" {{ request('category') === 'Accountancy Revenue' ? 'selected' : '' }}>Accountancy Revenue</option>
                <option value="Share Transfer & Stockholder Compliance" {{ request('category') === 'Share Transfer & Stockholder Compliance' ? 'selected' : '' }}>Share Transfer & Stockholder Compliance</option>
                <option value="Consulting Revenue" {{ request('category') === 'Consulting Revenue' ? 'selected' : '' }}>Consulting Revenue</option>
                <option value="Managed Administrative Support Services" {{ request('category') === 'Managed Administrative Support Services' ? 'selected' : '' }}>Managed Administrative Support Services</option>
                <option value="Compliance Revenue" {{ request('category') === 'Compliance Revenue' ? 'selected' : '' }}>Compliance Revenue</option>
                <option value="LGU and Local Permit Compliance" {{ request('category') === 'LGU and Local Permit Compliance' ? 'selected' : '' }}>LGU and Local Permit Compliance</option>
                <option value="Compliance" {{ request('category') === 'Compliance' ? 'selected' : '' }}>Compliance</option>
            </select>
            <select name="engagement" class="border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm text-slate-800 bg-white">
                <option value="">Engagement</option>
                <option value="project" {{ request('engagement') === 'project' ? 'selected' : '' }}>Project</option>
                <option value="regular" {{ request('engagement') === 'regular' ? 'selected' : '' }}>Regular</option>
                <option value="hybrid" {{ request('engagement') === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
            </select>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('services.index') }}" class="text-slate-500 hover:text-slate-700 text-sm font-medium px-3 py-2">Reset</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-5 py-2.5 rounded-lg shadow-sm cursor-pointer">Apply Filters</button>
        </div>
    </div>

    {{-- 6.1 SERVICE AREA & CREATE FIELD DROPDOWN MENU --}}
    <div class="flex justify-between items-center pt-3 border-t border-slate-100 text-sm text-slate-600">
        <div class="flex items-center space-x-3">
            <span class="font-semibold text-slate-800">Service Area:</span>
            <select name="service_area" onchange="this.form.submit()" class="border border-slate-300 rounded-lg px-3.5 py-2 text-sm text-slate-800 bg-white">
                <option value="">All Service Areas</option>
                <option value="Corporate & Regulatory Advisory" {{ request('service_area') === 'Corporate & Regulatory Advisory' ? 'selected' : '' }}>Corporate & Regulatory Advisory</option>
                <option value="Accounting & Compliance Advisory" {{ request('service_area') === 'Accounting & Compliance Advisory' ? 'selected' : '' }}>Accounting & Compliance Advisory</option>
                <option value="Governance & Policy Advisory" {{ request('service_area') === 'Governance & Policy Advisory' ? 'selected' : '' }}>Governance & Policy Advisory</option>
                <option value="People & Talent Solutions" {{ request('service_area') === 'People & Talent Solutions' ? 'selected' : '' }}>People & Talent Solutions</option>
                <option value="Strategic Situations Advisory" {{ request('service_area') === 'Strategic Situations Advisory' ? 'selected' : '' }}>Strategic Situations Advisory</option>
                <option value="Business Strategy & Process Advisory" {{ request('service_area') === 'Business Strategy & Process Advisory' ? 'selected' : '' }}>Business Strategy & Process Advisory</option>
                <option value="Learning & Capability Development" {{ request('service_area') === 'Learning & Capability Development' ? 'selected' : '' }}>Learning & Capability Development</option>
                <option value="Service Add-Ons" {{ request('service_area') === 'Service Add-Ons' ? 'selected' : '' }}>Service Add-Ons</option>
                <option value="Others" {{ request('service_area') === 'Others' ? 'selected' : '' }}>Others</option>
            </select>
        </div>

        <div class="relative inline-block text-left">
            <button type="button" onclick="toggleCreateFieldMenu(event)" class="text-blue-600 hover:text-blue-800 font-bold text-sm flex items-center space-x-1.5 transition cursor-pointer py-1.5 px-3 rounded-lg hover:bg-blue-50">
                <span>+ Create Field</span>
            </button>

            <div id="createFieldMenu" class="hidden absolute right-0 mt-1 w-56 bg-white rounded-lg shadow-xl border border-slate-200 py-1.5 z-50 text-sm font-medium text-slate-700 max-h-64 overflow-y-auto">
                <button type="button" onclick="openFieldModal('Single Line Text')" class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center space-x-3 transition text-slate-700 cursor-pointer">
                    <i class="fa-solid fa-font text-slate-400 w-4 text-center"></i>
                    <span>Single Line Text</span>
                </button>
                <button type="button" onclick="openFieldModal('Multi Line Text')" class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center space-x-3 transition text-slate-700 cursor-pointer">
                    <i class="fa-solid fa-align-left text-slate-400 w-4 text-center"></i>
                    <span>Multi Line Text</span>
                </button>
                <button type="button" onclick="openFieldModal('Number')" class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center space-x-3 transition text-slate-700 cursor-pointer">
                    <i class="fa-solid fa-hashtag text-slate-400 w-4 text-center"></i>
                    <span>Number</span>
                </button>
                <button type="button" onclick="openFieldModal('Currency')" class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center space-x-3 transition text-slate-700 cursor-pointer">
                    <i class="fa-solid fa-money-bill-wave text-slate-400 w-4 text-center"></i>
                    <span>Currency</span>
                </button>
                <button type="button" onclick="openFieldModal('Picklist')" class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center space-x-3 transition text-slate-700 cursor-pointer">
                    <i class="fa-solid fa-list-check text-slate-400 w-4 text-center"></i>
                    <span>Picklist</span>
                </button>
                <button type="button" onclick="openFieldModal('Checkbox')" class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center space-x-3 transition text-slate-700 cursor-pointer">
                    <i class="fa-regular fa-square-check text-slate-400 w-4 text-center"></i>
                    <span>Checkbox</span>
                </button>
                <button type="button" onclick="openFieldModal('Date')" class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center space-x-3 transition text-slate-700 cursor-pointer">
                    <i class="fa-regular fa-calendar text-slate-400 w-4 text-center"></i>
                    <span>Date</span>
                </button>
                <button type="button" onclick="openFieldModal('Lookup')" class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center space-x-3 transition text-slate-700 cursor-pointer">
                    <i class="fa-solid fa-link text-slate-400 w-4 text-center"></i>
                    <span>Lookup</span>
                </button>
            </div>
        </div>
    </div>
</form>

{{-- DYNAMIC FIELD CREATION MODAL --}}
<div id="createFieldModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden flex justify-center items-center p-4 z-[9999]">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 text-xs space-y-4 relative">
        <div class="flex justify-between items-center border-b pb-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Create Custom Field</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Type: <span id="modalFieldTypeName" class="font-bold text-blue-600">Single Line Text</span></p>
            </div>
            <button type="button" onclick="closeFieldModal()" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
        </div>

        <form action="#" method="POST" onsubmit="event.preventDefault(); alert('Custom field created successfully!'); closeFieldModal();" class="space-y-3">
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Field Label *</label>
                <input type="text" required placeholder="e.g. Tax Registration ID" class="w-full border border-slate-300 rounded px-3 py-2 text-xs outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Field API Name</label>
                <input type="text" placeholder="e.g. tax_registration_id" class="w-full border border-slate-300 rounded px-3 py-2 text-xs outline-none focus:border-blue-500 bg-slate-50">
            </div>
            <div id="picklistOptionsContainer" class="hidden">
                <label class="block font-semibold text-slate-700 mb-1">Options (One per line)</label>
                <textarea rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3" class="w-full border border-slate-300 rounded px-3 py-1.5 text-xs outline-none focus:border-blue-500"></textarea>
            </div>
            <div class="flex items-center space-x-2 pt-1">
                <input type="checkbox" id="isRequired" class="rounded border-slate-300 text-blue-600">
                <label for="isRequired" class="text-xs text-slate-700 font-medium">Mark as Required Field</label>
            </div>
            <div class="flex justify-end space-x-2 pt-3 border-t">
                <button type="button" onclick="closeFieldModal()" class="px-3.5 py-1.5 border border-slate-300 text-slate-700 font-medium rounded hover:bg-slate-50 transition cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded shadow-sm transition cursor-pointer">Create Field</button>
            </div>
        </form>
    </div>
</div>

{{-- 7. Services Data Table with Column Lines & Status Column --}}
<div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-x-auto mb-4">
    <table class="w-full text-left border-collapse text-xs border-slate-200 divide-y divide-slate-200">
        <thead>
            <tr class="bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider divide-x divide-slate-200">
                <th class="py-3 px-3 w-10 text-center"><input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)" class="rounded border-slate-300"></th>
                <th class="py-3 px-4">SERVICE NAME & CODE</th>
                <th class="py-3 px-4">CATEGORY</th>
                <th class="py-3 px-4">COMPANY</th>
                <th class="py-3 px-4">ENGAGEMENT</th>
                <th class="py-3 px-4">STANDARD PRICE</th>
                <th class="py-3 px-4">UNIT / RATE</th> {{-- Idinagdag na Unit / Rate Column --}}
                <th class="py-3 px-4">TAX TREATMENT</th>
                <th class="py-3 px-4">EXPECTED HOURS</th>
                <th class="py-3 px-4">STATUS</th>
                <th class="py-3 px-4 text-center">ACTIONS</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 text-slate-700 bg-white">
            @if (count($serviceList) > 0)
                @foreach ($serviceList as $item)
                    @php
                        $id = is_object($item) ? $item->id : $item['id'];
                        $name = is_object($item) ? $item->name : $item['name'];
                        $code = is_object($item) ? ($item->service_code ?? 'SVC-' . sprintf('%04d', $id)) : ($item['service_code'] ?? 'SVC-0000');
                        $cat = is_object($item) ? ($item->category->name ?? $item->category ?? $item->service_area ?? '-') : ($item['category'] ?? '-');
                        $company = is_object($item) ? ($item->company ?? 'Global Catalog') : ($item['company'] ?? 'Global Catalog');
                        $behavior = is_object($item) ? ($item->engagement_behavior ?? 'regular') : ($item['engagement_behavior'] ?? 'regular');
                        
                        $priceVal = is_object($item) ? ($item->activeVersion->standard_price ?? $item->standard_price ?? 0) : ($item['standard_price'] ?? 0);
                        $price = number_format($priceVal, 2);

                        // Kinukuha ang unit_rate mula sa activeVersion o item data
                        $unitRateVal = is_object($item) ? ($item->activeVersion->unit_rate ?? $item->unit_rate ?? 0) : ($item['unit_rate'] ?? 0);
                        $unitRate = number_format($unitRateVal, 2);
                        
                        $tax = is_object($item) ? ($item->activeVersion->tax_treatment ?? $item->tax_treatment ?? 'VAT Exclusive') : ($item['tax_treatment'] ?? 'VAT Exclusive');
                        $hours = is_object($item) ? ($item->activeVersion->expected_hours ?? $item->expected_hours ?? 0) : ($item['expected_hours'] ?? 0);
                        
                        $rawStatus = is_object($item) ? ($item->status ?? 'incomplete') : ($item['status'] ?? 'incomplete');
                        $status = strtolower($rawStatus);

                        $workspaceUrl = Route::has('services.workspace') ? route('services.workspace', ['service' => $id, 'mode' => 'view']) : (Route::has('services.show') ? route('services.show', $id) : '#');
                        $editUrl = Route::has('services.workspace') ? route('services.workspace', ['service' => $id, 'mode' => 'edit']) : '#';
                        $usageUrl = Route::has('services.workspace') ? route('services.workspace', ['service' => $id, 'tab' => 'usage_performance']) : '#';
                        $archiveRoute = Route::has('services.archive') ? route('services.archive', $id) : url('/services/' . $id . '/archive');
                    @endphp

                    <tr class="hover:bg-slate-50 transition divide-x divide-slate-200">
                        <td class="py-3 px-3 text-center">
                            <input type="checkbox" name="selected_services[]" value="{{ $id }}" class="service-checkbox rounded border-slate-300">
                        </td>
                        <td class="py-3 px-4">
                            <a href="{{ $workspaceUrl }}" class="font-bold text-slate-900 hover:text-blue-600 hover:underline block leading-tight">{{ $name }}</a>
                            <span class="text-[11px] text-slate-400 block font-normal mt-0.5">Code: <strong class="text-slate-600">{{ $code }}</strong></span>
                        </td>
                        <td class="py-3 px-4 text-slate-600">{{ $cat }}</td>
                        <td class="py-3 px-4 text-slate-500">{{ $company }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 border rounded text-[11px] font-medium border-slate-200 text-slate-700 bg-slate-50 capitalize">
                                {{ $behavior }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-mono font-semibold text-slate-900">₱{{ $price }}</td>
                        <td class="py-3 px-4 font-mono font-semibold text-slate-900">
                            @if($unitRateVal > 0)
                                ₱{{ $unitRate }}
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-500">{{ $tax }}</td>
                        <td class="py-3 px-4 text-slate-600 font-mono">{{ $hours }} hrs</td>
                        
                        {{-- STATUS COLUMN WITH NEUTRAL BADGES --}}
                        <td class="py-3 px-4">
                            <span class="px-2.5 py-1 bg-slate-100 text-slate-700 rounded-full text-[10px] font-bold uppercase tracking-wide border border-slate-200">
                                {{ ucfirst($status) }}
                            </span>
                        </td>

                        {{-- ACTIONS COLUMN --}}
                        <td class="py-3 px-4 text-center relative">
                            <div class="inline-flex items-center space-x-1">
                                <a href="{{ $workspaceUrl }}" class="border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-medium px-2.5 py-1 rounded text-[11px] transition inline-block shadow-sm">View</a>
                                
                                <div class="relative inline-block text-left">
                                    <button onclick="toggleServiceDropdown(event, 'svc-menu-{{ $id }}')" type="button" class="border border-slate-300 bg-white hover:bg-slate-50 text-slate-600 px-1.5 py-1 rounded text-[11px] transition cursor-pointer shadow-sm">
                                        More <i class="fa-solid fa-chevron-down text-[9px] ml-0.5"></i>
                                    </button>

                                    <div id="svc-menu-{{ $id }}" class="svc-action-dropdown hidden origin-top-right absolute right-0 mt-1 w-44 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-slate-100 z-50 text-left text-xs py-1">
                                        <div class="py-1">
                                            <a href="{{ $workspaceUrl }}" class="block px-3 py-1.5 text-slate-700 hover:bg-slate-100">View Workspace</a>
                                            <a href="{{ $editUrl }}" class="block px-3 py-1.5 text-slate-700 hover:bg-slate-100">Edit / New Revision</a>
                                            
                                            @if (Route::has('services.duplicate'))
                                                <form action="{{ route('services.duplicate', $id) }}" method="POST" class="m-0 p-0">
                                                    @csrf
                                                    <button type="submit" class="w-full text-left px-3 py-1.5 text-slate-700 hover:bg-slate-100 cursor-pointer">Duplicate Service</button>
                                                </form>
                                            @endif
                                        </div>
                                        
                                    
                                        <div class="py-1">
                                            <form id="archive-form-{{ $id }}" action="{{ $archiveRoute }}" method="POST" class="m-0 p-0" onsubmit="return confirm('Are you sure you want to archive this service?')">
                                                @csrf
                                                <button type="submit" class="w-full text-left px-3 py-1.5 text-rose-600 hover:bg-rose-50 font-semibold cursor-pointer">Archive Service</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            @else
                <tr><td colspan="11" class="py-8 text-center text-slate-400">No services found in database.</td></tr>
            @endif
        </tbody>
    </table>
</div>

{{-- 8. TABLE FOOTER & PAGINATION BAR --}}
@if ($serviceList instanceof \Illuminate\Pagination\LengthAwarePaginator)
    @php
        $total = $serviceList->total();
        $first = $serviceList->firstItem() ?? 0;
        $last = $serviceList->lastItem() ?? 0;
        $perPage = $serviceList->perPage();
    @endphp

    <div class="flex items-center justify-between bg-white border border-slate-200 rounded-lg p-3 shadow-sm text-xs text-slate-500 mb-6">
        <div>
            Showing <span class="font-bold text-slate-800">{{ $first }}</span> to <span class="font-bold text-slate-800">{{ $last }}</span> of <span class="font-bold text-slate-800">{{ $total }}</span> results
        </div>
        
        <div class="flex items-center space-x-6">
            <div class="flex items-center space-x-2">
                <span>Records per page</span>
                <form method="GET" action="{{ route('services.index') }}" class="m-0 p-0">
                    @foreach (request()->except(['per_page', 'page']) as $k => $v)
                        @if ($v) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
                    @endforeach
                    <select name="per_page" onchange="this.form.submit()" class="border border-slate-300 rounded px-2 py-1 text-xs text-slate-700 bg-white">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </form>
            </div>

            <div>
                {{ $serviceList->links() }}
            </div>
        </div>
    </div>
@endif

{{-- 9. Modals --}}
<div id="whatIsServiceModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex justify-center items-center p-4 z-[9999]">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-8 text-base space-y-5 relative z-[10000]">
        <div class="flex justify-between items-center border-b pb-4">
            <h3 class="text-xl font-bold text-slate-900">What is a Service?</h3>
            <button type="button" onclick="document.getElementById('whatIsServiceModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-2xl font-bold cursor-pointer">&times;</button>
        </div>
        <p class="text-slate-700 leading-relaxed text-base font-normal text-justify">
            A <strong class="font-bold text-slate-900">Service</strong> defines a standardized productized offering in your firm’s catalog. It includes workflow activities, default pricing models, client deliverables, required documents, and approval rules used across proposals and active client contracts.
        </p>
        <div class="flex justify-end pt-3 border-t">
            <button type="button" onclick="document.getElementById('whatIsServiceModal').classList.add('hidden')" class="px-6 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-semibold text-sm rounded-lg shadow-md cursor-pointer transition">
                Got it
            </button>
        </div>
    </div>
</div>

<div id="importServiceModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex justify-center items-center p-4 z-[9999]">
    <div class="bg-white rounded-lg shadow-2xl max-w-md w-full p-6 text-xs space-y-4 relative z-[10000]">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-sm font-bold text-slate-900">Import Service Specification</h3>
            <button type="button" onclick="document.getElementById('importServiceModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
        </div>
        <form action="{{ Route::has('services.import') ? route('services.import') : '#' }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Select JSON File *</label>
                <input type="file" name="json_file" accept=".json" required class="w-full border border-slate-300 rounded px-3 py-2 text-xs outline-none focus:border-blue-500">
            </div>
            <div class="flex justify-end space-x-2 pt-2 border-t">
                <button type="button" onclick="document.getElementById('importServiceModal').classList.add('hidden')" class="px-3.5 py-1.5 border border-slate-300 text-slate-700 font-medium rounded cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white font-medium rounded shadow-sm cursor-pointer">Upload & Import</button>
            </div>
        </form>
    </div>
</div>

<div id="quickAddModal" class="fixed inset-0 w-screen h-screen bg-slate-900/60 backdrop-blur-sm hidden flex justify-center items-center p-6 z-[9999]">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full p-8 text-sm space-y-5 relative z-[10000]">
        <div class="flex justify-between items-center border-b pb-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Quick Add Service</h3>
                <p class="text-xs text-slate-500 mt-1">Create a draft service shell to configure in workspace.</p>
            </div>
            <button type="button" onclick="document.getElementById('quickAddModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-2xl font-bold cursor-pointer">&times;</button>
        </div>

        <form action="{{ Route::has('services.store') ? route('services.store') : '/services' }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block font-semibold text-slate-700 mb-1.5 text-xs uppercase tracking-wide">Service Name *</label>
                <input type="text" name="name" required placeholder="e.g. On-site Profit and Loss Review" class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm outline-none focus:border-blue-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5 text-xs uppercase tracking-wide">Service Area *</label>
                    <div id="serviceAreaExistingWrapper">
                        <select name="service_area_select" id="serviceAreaSelect" onchange="handleServiceAreaChange(this)" required class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-blue-500 bg-white">
                            <option value="">-- Select Service Area --</option>
                            @php
                                $quickAddServiceAreas = collect($serviceAreas ?? []);
                                $defaultServiceAreas = collect([
                                    'Corporate & Regulatory Advisory', 'Accounting & Compliance Advisory',
                                    'Governance & Policy Advisory', 'People & Talent Solutions',
                                    'Strategic Situations Advisory', 'Business Strategy & Process Advisory',
                                    'Learning & Capability Development', 'Service Add-Ons', 'Others',
                                ]);
                                $quickAddServiceAreas = $defaultServiceAreas->merge($quickAddServiceAreas)->filter()->unique()->sort()->values();
                            @endphp
                            @foreach($quickAddServiceAreas as $area)
                                <option value="{{ $area }}">{{ $area }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="serviceAreaCustomWrapper" class="hidden">
                        <input type="text" name="service_area" id="serviceAreaCustomInput" placeholder="Enter new service area..." disabled class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-blue-500 bg-white">
                    </div>
                    <div class="mt-1.5 flex items-center">
                        <button type="button" id="addServiceAreaBtn" onclick="enableNewServiceArea()" class="text-[11px] font-semibold text-blue-600 hover:text-blue-800 hover:underline cursor-pointer">+ Add New Service Area</button>
                        <button type="button" id="useExistingServiceAreaBtn" onclick="useExistingServiceArea()" class="hidden text-[11px] font-semibold text-slate-600 hover:text-slate-800 hover:underline cursor-pointer">&larr; Use Existing Service Area</button>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5 text-xs uppercase tracking-wide">Category *</label>
                    <div id="categoryExistingWrapper">
                        <select name="category_select" id="categorySelect" onchange="handleCategoryChange(this)" required class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-blue-500 bg-white">
                            <option value="">-- Select Category --</option>
                            @php
                                $quickAddServiceCategories = collect($serviceCategories ?? []);
                                $defaultServiceCategories = collect([
                                    'Accountancy Revenue', 'Share Transfer & Stockholder Compliance',
                                    'Consulting Revenue', 'Managed Administrative Support Services',
                                    'Compliance Revenue', 'LGU and Local Permit Compliance', 'Compliance', 'Others',
                                ]);
                                $quickAddServiceCategories = $defaultServiceCategories->merge($quickAddServiceCategories)->filter()->unique()->sort()->values();
                            @endphp
                            @foreach($quickAddServiceCategories as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="categoryCustomWrapper" class="hidden">
                        <input type="text" name="category" id="categoryCustomInput" placeholder="Enter new category..." disabled class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-blue-500 bg-white">
                    </div>
                    <div class="mt-1.5 flex items-center">
                        <button type="button" id="addCategoryBtn" onclick="enableNewCategory()" class="text-[11px] font-semibold text-blue-600 hover:text-blue-800 hover:underline cursor-pointer">+ Add New Category</button>
                        <button type="button" id="useExistingCategoryBtn" onclick="useExistingCategory()" class="hidden text-[11px] font-semibold text-slate-600 hover:text-slate-800 hover:underline cursor-pointer">&larr; Use Existing Category</button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5 text-xs uppercase tracking-wide">Engagement Behavior *</label>
                    <select name="engagement_behavior" required class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-blue-500 bg-white">
                        <option value="">-- Select Engagement --</option>
                        <option value="project">Project</option>
                        <option value="regular">Regular</option>
                        <option value="hybrid">Hybrid</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5 text-xs uppercase tracking-wide">Tax Treatment *</label>
                    <select name="tax_treatment" required class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-blue-500 bg-white">
                        <option value="">-- Select Tax Treatment --</option>
                        <option value="VAT Exclusive">VAT Exclusive</option>
                        <option value="VAT Inclusive">VAT Inclusive</option>
                        <option value="No Tax">No Tax</option>
                        <option value="Percentage Tax">Percentage Tax</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1.5 text-xs uppercase tracking-wide">Short Description (Optional)</label>
                <textarea name="short_description" rows="3" placeholder="Brief summary for internal catalog..." class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm outline-none focus:border-blue-500"></textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button type="button" onclick="document.getElementById('quickAddModal').classList.add('hidden')" class="px-5 py-2 border border-slate-300 text-slate-700 font-medium text-xs rounded-lg hover:bg-slate-50 transition cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs rounded-lg shadow-sm transition cursor-pointer">Save Service</button>
            </div>
        </form>
    </div>
</div>

<script>
function enableNewServiceArea() {
    const existingWrapper = document.getElementById('serviceAreaExistingWrapper');
    const customWrapper = document.getElementById('serviceAreaCustomWrapper');
    const select = document.getElementById('serviceAreaSelect');
    const customInput = document.getElementById('serviceAreaCustomInput');
    const addButton = document.getElementById('addServiceAreaBtn');
    const existingButton = document.getElementById('useExistingServiceAreaBtn');

    if (!existingWrapper || !customWrapper || !select || !customInput) return;

    existingWrapper.classList.add('hidden');
    customWrapper.classList.remove('hidden');
    select.required = false;
    select.disabled = true;
    select.removeAttribute('name');
    customInput.disabled = false;
    customInput.required = true;
    customInput.setAttribute('name', 'service_area');
    customInput.focus();
    addButton?.classList.add('hidden');
    existingButton?.classList.remove('hidden');
}

function useExistingServiceArea() {
    const existingWrapper = document.getElementById('serviceAreaExistingWrapper');
    const customWrapper = document.getElementById('serviceAreaCustomWrapper');
    const select = document.getElementById('serviceAreaSelect');
    const customInput = document.getElementById('serviceAreaCustomInput');
    const addButton = document.getElementById('addServiceAreaBtn');
    const existingButton = document.getElementById('useExistingServiceAreaBtn');

    if (!existingWrapper || !customWrapper || !select || !customInput) return;

    customWrapper.classList.add('hidden');
    existingWrapper.classList.remove('hidden');
    customInput.required = false;
    customInput.disabled = true;
    customInput.removeAttribute('name');
    customInput.value = '';
    select.disabled = false;
    select.required = true;
    select.setAttribute('name', 'service_area_select');
    addButton?.classList.remove('hidden');
    existingButton?.classList.add('hidden');
}

function enableNewCategory() {
    const existingWrapper = document.getElementById('categoryExistingWrapper');
    const customWrapper = document.getElementById('categoryCustomWrapper');
    const select = document.getElementById('categorySelect');
    const customInput = document.getElementById('categoryCustomInput');
    const addButton = document.getElementById('addCategoryBtn');
    const existingButton = document.getElementById('useExistingCategoryBtn');

    if (!existingWrapper || !customWrapper || !select || !customInput) return;

    existingWrapper.classList.add('hidden');
    customWrapper.classList.remove('hidden');
    select.required = false;
    select.disabled = true;
    select.removeAttribute('name');
    customInput.disabled = false;
    customInput.required = true;
    customInput.setAttribute('name', 'category');
    customInput.focus();
    addButton?.classList.add('hidden');
    existingButton?.classList.remove('hidden');
}

function useExistingCategory() {
    const existingWrapper = document.getElementById('categoryExistingWrapper');
    const customWrapper = document.getElementById('categoryCustomWrapper');
    const select = document.getElementById('categorySelect');
    const customInput = document.getElementById('categoryCustomInput');
    const addButton = document.getElementById('addCategoryBtn');
    const existingButton = document.getElementById('useExistingCategoryBtn');

    if (!existingWrapper || !customWrapper || !select || !customInput) return;

    customWrapper.classList.add('hidden');
    existingWrapper.classList.remove('hidden');
    customInput.required = false;
    customInput.disabled = true;
    customInput.removeAttribute('name');
    customInput.value = '';
    select.disabled = false;
    select.required = true;
    select.setAttribute('name', 'category_select');
    addButton?.classList.remove('hidden');
    existingButton?.classList.add('hidden');
}

function handleServiceAreaChange(selectElement) {
    if (selectElement.value === 'Others') {
        enableNewServiceArea();
        selectElement.value = '';
    } else {
        selectElement.setAttribute('name', 'service_area');
    }
}

function handleCategoryChange(selectElement) {
    if (selectElement.value === 'Others') {
        enableNewCategory();
        selectElement.value = '';
    } else {
        selectElement.setAttribute('name', 'category');
    }
}

function toggleBulkActionsMenu(event) {
    event.stopPropagation();
    const menu = document.getElementById('bulkActionsMenu');
    menu.classList.toggle('hidden');
}

function toggleCreateFieldMenu(event) {
    event.stopPropagation();
    const menu = document.getElementById('createFieldMenu');
    menu.classList.toggle('hidden');
}

function openFieldModal(fieldType) {
    document.getElementById('createFieldMenu').classList.add('hidden');
    document.getElementById('modalFieldTypeName').innerText = fieldType;
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

function toggleServiceDropdown(event, elementId) {
    event.stopPropagation();
    const dropdowns = document.querySelectorAll('.svc-action-dropdown');
    dropdowns.forEach(menu => {
        if (menu.id !== elementId) menu.classList.add('hidden');
    });
    const targetMenu = document.getElementById(elementId);
    if (targetMenu) targetMenu.classList.toggle('hidden');
}

function toggleSelectAll(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.service-checkbox');
    checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('#bulkActionsMenu')) {
        document.getElementById('bulkActionsMenu')?.classList.add('hidden');
    }
    if (!e.target.closest('#createFieldMenu')) {
        document.getElementById('createFieldMenu')?.classList.add('hidden');
    }
    if (!e.target.closest('.svc-action-dropdown')) {
        const dropdowns = document.querySelectorAll('.svc-action-dropdown');
        dropdowns.forEach(menu => menu.classList.add('hidden'));
    }
});
</script>
@endsection
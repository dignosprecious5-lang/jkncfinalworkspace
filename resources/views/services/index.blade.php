@extends('layouts.app')

@section('content')
<?php
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

// 2. Success Flash Notification
if (session('success')) {
    echo '<div class="bg-emerald-100 border border-emerald-300 text-emerald-800 px-4 py-2.5 rounded text-xs font-semibold shadow-sm mb-4 flex items-center justify-between">';
    echo '    <span><i class="fa-solid fa-circle-check mr-2"></i> ' . htmlspecialchars(session('success')) . '</span>';
    echo '</div>';
}

// 3. Header Section & Action Buttons
echo '<div class="flex justify-between items-start mb-6">';
echo '    <div>';
echo '        <h1 class="text-2xl font-bold text-slate-900">Services Catalog</h1>';
echo '        <p class="text-xs text-slate-500 mt-0.5">Standardized service catalog with configurable fields, routing, scheduling, and pricing.</p>';
echo '        <button type="button" onclick="document.getElementById(\'whatIsServiceModal\').classList.remove(\'hidden\')" class="text-xs text-blue-600 font-semibold hover:underline mt-1 inline-block cursor-pointer">';
echo '            What is a Service?';
echo '        </button>';
echo '    </div>';
echo '    <div class="flex items-center space-x-2">';
echo '        <button type="button" onclick="document.getElementById(\'importServiceModal\').classList.remove(\'hidden\')" class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium text-xs px-3.5 py-2 rounded shadow-sm transition flex items-center space-x-1 cursor-pointer">';
echo '            <i class="fa-solid fa-file-import text-xs mr-1"></i><span>Import Service</span>';
echo '        </button>';
echo '        <a href="' . (Route::has('proposals.index') ? route('proposals.index') : '#') . '" class="bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs px-3.5 py-2 rounded shadow-sm transition flex items-center space-x-1">';
echo '            <i class="fa-solid fa-file-signature text-xs mr-1"></i><span>Client Proposals</span>';
echo '        </a>';
echo '        <a href="' . (Route::has('requirements.index') ? route('requirements.index') : '#') . '" class="bg-slate-800 hover:bg-slate-900 text-white font-medium text-xs px-3.5 py-2 rounded shadow-sm transition flex items-center space-x-1">';
echo '            <i class="fa-solid fa-folder-open text-xs mr-1"></i><span>Manage Requirements</span>';
echo '        </a>';
echo '        <button onclick="document.getElementById(\'quickAddModal\').classList.remove(\'hidden\')" class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-4 py-2 rounded shadow-sm transition flex items-center space-x-1 cursor-pointer">';
echo '            <span>+ Quick Add</span>';
echo '        </button>';
echo '    </div>';
echo '</div>';

// 4. Detailed Insights Cards (4 Columns)
echo '<div class="grid grid-cols-4 gap-4 mb-6">';

$highestName = isset($insightData['highest_revenue']['name']) ? $insightData['highest_revenue']['name'] : 'Corporation Formation & Registration Assistance (L)';
$highestPrice = isset($insightData['highest_revenue']['amount']) ? number_format($insightData['highest_revenue']['amount'], 2) : '100,000.00';
echo '<div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">';
echo '    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Most Purchased Service</span>';
echo '    <p class="font-bold text-slate-900 text-sm truncate">On-site Profit and Loss Review</p>';
echo '    <div class="text-xs text-blue-600 font-semibold">12 Active Contracts</div>';
echo '</div>';

echo '<div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">';
echo '    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Highest Priced Service</span>';
echo '    <p class="font-bold text-slate-900 text-sm truncate">' . htmlspecialchars($highestName) . '</p>';
echo '    <div class="text-xs text-emerald-600 font-semibold">₱' . $highestPrice . '</div>';
echo '</div>';

$laborName = isset($insightData['most_labor_intensive']['name']) ? $insightData['most_labor_intensive']['name'] : 'Corporation Formation & Registration Assistance (L)';
$laborHours = isset($insightData['most_labor_intensive']['hours']) ? $insightData['most_labor_intensive']['hours'] : '80';
echo '<div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">';
echo '    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Most Labor Intensive</span>';
echo '    <p class="font-bold text-slate-900 text-sm truncate">' . htmlspecialchars($laborName) . '</p>';
echo '    <div class="text-xs text-amber-600 font-semibold">' . $laborHours . ' Expected Hours</div>';
echo '</div>';

echo '<div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">';
echo '    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Proposal Conversion Rate</span>';
echo '    <p class="font-bold text-slate-900 text-xl">88.9%</p>';
echo '    <div class="text-[11px] text-slate-500">8 / 9 Proposals Accepted</div>';
echo '</div>';

echo '</div>';

// 5. Status Metrics Cards (5 Columns Filter)
echo '<div class="grid grid-cols-5 gap-3 mb-6">';
$statusMap = [
    'incomplete'   => ['label' => 'Incomplete', 'color' => 'blue'],
    'draft'        => ['label' => 'Draft', 'color' => 'amber'],
    'for_approval' => ['label' => 'For Approval', 'color' => 'purple'],
    'active'       => ['label' => 'Active', 'color' => 'emerald'],
    'archived'     => ['label' => 'Archived', 'color' => 'slate'],
];

foreach ($statusMap as $key => $meta) {
    $cnt = isset($counts[$key]) ? $counts[$key] : 0;
    $isSelected = request('status') === $key ? 'ring-2 ring-blue-500 font-bold' : '';
    echo '<a href="' . route('services.index') . '?status=' . $key . '" class="bg-white border border-' . $meta['color'] . '-200 hover:bg-' . $meta['color'] . '-50/50 rounded p-3 transition block ' . $isSelected . '">';
    echo '    <span class="text-[10px] font-bold text-' . $meta['color'] . '-600 uppercase tracking-wide">' . $meta['label'] . '</span>';
    echo '    <div class="text-2xl font-bold text-slate-800 mt-1">' . $cnt . '</div>';
    echo '</a>';
}
echo '</div>';

// 6. Dashboard Filters Bar
echo '<form method="GET" action="' . route('services.index') . '" class="bg-white border border-slate-200 rounded-lg p-3 mb-4 shadow-sm text-xs space-y-3">';
echo '    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2.5">';
echo '        <div class="flex items-center space-x-2">';
echo '            <span class="font-bold text-slate-700">Dashboard Filters</span>';
echo '            <span class="text-slate-400">From</span>';
echo '            <input type="date" name="from_date" value="' . htmlspecialchars(request('from_date', '')) . '" class="border border-slate-300 rounded px-2 py-1 text-xs text-slate-600">';
echo '            <span class="text-slate-400">To</span>';
echo '            <input type="date" name="to_date" value="' . htmlspecialchars(request('to_date', '')) . '" class="border border-slate-300 rounded px-2 py-1 text-xs text-slate-600">';
echo '            <span class="text-slate-400">Version</span>';
echo '            <select name="version" class="border border-slate-300 rounded px-2 py-1 text-xs text-slate-600">';
echo '                <option value="">All Versions</option>';
echo '                <option value="V1.0"' . (request('version') === 'V1.0' ? ' selected' : '') . '>V1.0</option>';
echo '                <option value="V2.0"' . (request('version') === 'V2.0' ? ' selected' : '') . '>V2.0</option>';
echo '            </select>';
echo '        </div>';
echo '    </div>';
echo '    <div class="flex items-center justify-between gap-2">';
echo '        <div class="flex items-center space-x-2 flex-1">';
echo '            <div class="relative flex-1 max-w-xs">';
echo '                <input type="text" name="search" value="' . htmlspecialchars(request('search', '')) . '" placeholder="Search code or service name..." class="w-full border border-slate-300 rounded pl-8 pr-3 py-1.5 text-xs outline-none focus:border-blue-500">';
echo '                <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-slate-400 text-xs"></i>';
echo '            </div>';
echo '            <select name="status" class="border border-slate-300 rounded px-2.5 py-1.5 text-xs text-slate-600">';
echo '                <option value="">All Status</option>';
echo '                <option value="incomplete"' . (request('status') === 'incomplete' ? ' selected' : '') . '>Incomplete</option>';
echo '                <option value="draft"' . (request('status') === 'draft' ? ' selected' : '') . '>Draft</option>';
echo '                <option value="for_approval"' . (request('status') === 'for_approval' ? ' selected' : '') . '>For Approval</option>';
echo '                <option value="active"' . (request('status') === 'active' ? ' selected' : '') . '>Active</option>';
echo '                <option value="archived"' . (request('status') === 'archived' ? ' selected' : '') . '>Archived</option>';
echo '            </select>';
echo '            <select name="category" class="border border-slate-300 rounded px-2.5 py-1.5 text-xs text-slate-600">';
echo '                <option value="">All Categories</option>';
echo '                <option value="Accountancy Revenue"' . (request('category') === 'Accountancy Revenue' ? ' selected' : '') . '>Accountancy Revenue</option>';
echo '                <option value="Share Transfer & Stockholder Compliance"' . (request('category') === 'Share Transfer & Stockholder Compliance' ? ' selected' : '') . '>Share Transfer & Stockholder Compliance</option>';
echo '                <option value="Consulting Revenue"' . (request('category') === 'Consulting Revenue' ? ' selected' : '') . '>Consulting Revenue</option>';
echo '                <option value="Managed Administrative Support Services"' . (request('category') === 'Managed Administrative Support Services' ? ' selected' : '') . '>Managed Administrative Support Services</option>';
echo '                <option value="Compliance Revenue"' . (request('category') === 'Compliance Revenue' ? ' selected' : '') . '>Compliance Revenue</option>';
echo '                <option value="LGU and Local Permit Compliance"' . (request('category') === 'LGU and Local Permit Compliance' ? ' selected' : '') . '>LGU and Local Permit Compliance</option>';
echo '                <option value="Compliance"' . (request('category') === 'Compliance' ? ' selected' : '') . '>Compliance</option>';
echo '            </select>';
echo '            <select name="engagement" class="border border-slate-300 rounded px-2.5 py-1.5 text-xs text-slate-600">';
echo '                <option value="">Engagement</option>';
echo '                <option value="project"' . (request('engagement') === 'project' ? ' selected' : '') . '>Project</option>';
echo '                <option value="regular"' . (request('engagement') === 'regular' ? ' selected' : '') . '>Regular</option>';
echo '                <option value="both"' . (request('engagement') === 'both' ? ' selected' : '') . '>Both (Hybrid)</option>';
echo '            </select>';
echo '        </div>';
echo '        <div class="flex items-center space-x-2">';
echo '            <a href="' . route('services.index') . '" class="text-slate-500 hover:text-slate-700 text-xs px-2 py-1">Reset</a>';
echo '            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-3.5 py-1.5 rounded shadow-sm cursor-pointer">Apply Filters</button>';
echo '        </div>';
echo '    </div>';
echo '    <div class="flex justify-between items-center pt-2 border-t border-slate-100 text-[11px] text-slate-500">';
echo '        <div class="flex items-center space-x-2">';
echo '            <span>Service Area:</span>';
echo '            <select name="service_area" onchange="this.form.submit()" class="border border-slate-300 rounded px-2 py-1 text-xs text-slate-700">';
echo '                <option value="">All Service Areas</option>';
echo '                <option value="Corporate & Regulatory Advisory"' . (request('service_area') === 'Corporate & Regulatory Advisory' ? ' selected' : '') . '>Corporate & Regulatory Advisory</option>';
echo '                <option value="Accounting & Compliance Advisory"' . (request('service_area') === 'Accounting & Compliance Advisory' ? ' selected' : '') . '>Accounting & Compliance Advisory</option>';
echo '                <option value="Governance & Policy Advisory"' . (request('service_area') === 'Governance & Policy Advisory' ? ' selected' : '') . '>Governance & Policy Advisory</option>';
echo '                <option value="People & Talent Solutions"' . (request('service_area') === 'People & Talent Solutions' ? ' selected' : '') . '>People & Talent Solutions</option>';
echo '                <option value="Strategic Situations Advisory"' . (request('service_area') === 'Strategic Situations Advisory' ? ' selected' : '') . '>Strategic Situations Advisory</option>';
echo '                <option value="Business Strategy & Process Advisory"' . (request('service_area') === 'Business Strategy & Process Advisory' ? ' selected' : '') . '>Business Strategy & Process Advisory</option>';
echo '                <option value="Learning & Capability Development"' . (request('service_area') === 'Learning & Capability Development' ? ' selected' : '') . '>Learning & Capability Development</option>';
echo '                <option value="Service Add-Ons"' . (request('service_area') === 'Service Add-Ons' ? ' selected' : '') . '>Service Add-Ons</option>';
echo '                <option value="Others"' . (request('service_area') === 'Others' ? ' selected' : '') . '>Others</option>';
echo '            </select>';
echo '        </div>';
echo '        <button type="button" class="text-blue-600 hover:underline font-medium cursor-pointer">+ Create Field</button>';
echo '    </div>';
echo '</form>';

// 7. Services Data Table with Multi-Select Checkboxes
echo '<div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-visible mb-4">';
echo '    <table class="w-full text-left border-collapse text-xs">';
echo '        <thead>';
echo '            <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">';
echo '                <th class="py-3 px-3 w-10 text-center"><input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)" class="rounded border-slate-300"></th>';
echo '                <th class="py-3 px-4">SERVICE NAME & CODE</th>';
echo '                <th class="py-3 px-4">CATEGORY</th>';
echo '                <th class="py-3 px-4">COMPANY</th>';
echo '                <th class="py-3 px-4">ENGAGEMENT</th>';
echo '                <th class="py-3 px-4">STANDARD PRICE</th>';
echo '                <th class="py-3 px-4">TAX TREATMENT</th>';
echo '                <th class="py-3 px-4">EXPECTED HOURS</th>';
echo '                <th class="py-3 px-4">VERSION</th>';
echo '                <th class="py-3 px-4">STATUS</th>';
echo '                <th class="py-3 px-4 text-center">ACTIONS</th>';
echo '            </tr>';
echo '        </thead>';
echo '        <tbody class="divide-y divide-slate-100 text-slate-700">';

if (count($serviceList) > 0) {
    foreach ($serviceList as $item) {
        $id = is_object($item) ? $item->id : $item['id'];
        $name = is_object($item) ? $item->name : $item['name'];
        $code = is_object($item) ? ($item->service_code ?? 'SVC-' . sprintf('%04d', $id)) : ($item['service_code'] ?? 'SVC-0000');
        $cat = is_object($item) ? ($item->category ?? $item->service_area ?? '-') : ($item['category'] ?? '-');
        $company = is_object($item) ? ($item->company ?? 'Global Catalog') : ($item['company'] ?? 'Global Catalog');
        $behavior = is_object($item) ? ($item->engagement_behavior ?? 'regular') : ($item['engagement_behavior'] ?? 'regular');
        
        $priceVal = is_object($item) ? ($item->activeVersion->standard_price ?? $item->standard_price ?? 0) : ($item['standard_price'] ?? 0);
        $price = number_format($priceVal, 2);
        
        $tax = is_object($item) ? ($item->activeVersion->tax_treatment ?? $item->tax_treatment ?? 'VAT Exclusive') : ($item['tax_treatment'] ?? 'VAT Exclusive');
        $hours = is_object($item) ? ($item->activeVersion->expected_hours ?? $item->expected_hours ?? 0) : ($item['expected_hours'] ?? 0);
        $version = is_object($item) ? ($item->activeVersion->version_number ?? 'V1.0') : ($item['version'] ?? 'V1.0');
        $status = is_object($item) ? ($item->status ?? 'active') : ($item['status'] ?? 'active');

        $workspaceUrl = Route::has('services.workspace') ? route('services.workspace', ['service' => $id, 'mode' => 'view']) : (Route::has('services.show') ? route('services.show', $id) : '#');
        $editUrl = Route::has('services.workspace') ? route('services.workspace', ['service' => $id, 'mode' => 'edit']) : '#';
        $usageUrl = Route::has('services.workspace') ? route('services.workspace', ['service' => $id, 'tab' => 'usage_performance']) : '#';
        $archiveRoute = Route::has('services.archive') ? route('services.archive', $id) : url('/services/' . $id . '/archive');

        $statusColor = 'emerald';
        if ($status === 'incomplete') $statusColor = 'blue';
        if ($status === 'draft') $statusColor = 'amber';
        if ($status === 'for_approval') $statusColor = 'purple';
        if ($status === 'archived') $statusColor = 'slate';

        echo '<tr class="hover:bg-slate-50 transition">';
        echo '    <td class="py-3 px-3 text-center"><input type="checkbox" name="selected_services[]" value="' . $id . '" class="service-checkbox rounded border-slate-300"></td>';
        echo '    <td class="py-3 px-4">';
        echo '        <a href="' . $workspaceUrl . '" class="font-bold text-slate-800 hover:text-blue-600 hover:underline block leading-tight">' . htmlspecialchars($name) . '</a>';
        echo '        <span class="text-[11px] text-slate-400 block font-normal mt-0.5">Code: <strong class="text-slate-600">' . htmlspecialchars($code) . '</strong></span>';
        echo '    </td>';
        echo '    <td class="py-3 px-4 text-slate-600">' . htmlspecialchars($cat) . '</td>';
        echo '    <td class="py-3 px-4 text-slate-500">' . htmlspecialchars($company) . '</td>';
        echo '    <td class="py-3 px-4"><span class="px-2 py-0.5 border rounded text-[11px] font-medium border-blue-200 text-blue-600 bg-blue-50/50 capitalize">' . htmlspecialchars($behavior) . '</span></td>';
        echo '    <td class="py-3 px-4 font-mono font-semibold text-slate-900">₱' . $price . '</td>';
        echo '    <td class="py-3 px-4 text-slate-500">' . htmlspecialchars($tax) . '</td>';
        echo '    <td class="py-3 px-4 text-slate-600 font-mono">' . $hours . ' hrs</td>';
        echo '    <td class="py-3 px-4 text-slate-500 font-mono">' . htmlspecialchars($version) . '</td>';
        echo '    <td class="py-3 px-4">';
        echo '        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-' . $statusColor . '-100 text-' . $statusColor . '-700">' . htmlspecialchars(str_replace('_', ' ', $status)) . '</span>';
        echo '    </td>';

        // NATIVE JS ISOLATED DROPDOWN MENU
        echo '    <td class="py-3 px-4 text-center relative">';
        echo '        <div class="inline-flex items-center space-x-1">';
        echo '            <a href="' . $workspaceUrl . '" class="border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-medium px-2.5 py-1 rounded text-[11px] transition inline-block">View</a>';
        
        echo '            <div class="relative inline-block text-left">';
        echo '                <button onclick="toggleServiceDropdown(event, \'svc-menu-' . $id . '\')" type="button" class="border border-slate-300 bg-white hover:bg-slate-50 text-slate-600 px-1.5 py-1 rounded text-[11px] transition cursor-pointer">';
        echo '                    More <i class="fa-solid fa-chevron-down text-[9px] ml-0.5"></i>';
        echo '                </button>';

        echo '                <div id="svc-menu-' . $id . '" class="svc-action-dropdown hidden origin-top-right absolute right-0 mt-1 w-44 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-slate-100 z-50 text-left text-xs py-1">';
        echo '                    <div class="py-1">';
        echo '                        <a href="' . $workspaceUrl . '" class="block px-3 py-1.5 text-slate-700 hover:bg-slate-100">View Workspace</a>';
        echo '                        <a href="' . $editUrl . '" class="block px-3 py-1.5 text-slate-700 hover:bg-slate-100">Edit / New Revision</a>';
        
        if (Route::has('services.duplicate')) {
            echo '                    <form action="' . route('services.duplicate', $id) . '" method="POST" class="m-0 p-0">';
            echo '                        ' . csrf_field();
            echo '                        <button type="submit" class="w-full text-left px-3 py-1.5 text-slate-700 hover:bg-slate-100 cursor-pointer">Duplicate Service</button>';
            echo '                    </form>';
        }
        
        echo '                    </div>';
        echo '                    <div class="py-1">';
        echo '                        <a href="' . $usageUrl . '" class="block px-3 py-1.5 text-slate-700 hover:bg-slate-100">Usage & Performance</a>';
        
        if (Route::has('services.export_single')) {
            echo '                    <a href="' . route('services.export_single', $id) . '" class="block px-3 py-1.5 text-slate-700 hover:bg-slate-100">Export Service</a>';
        }

        echo '                    </div>';
        echo '                    <div class="py-1">';
        
        // POST ACTION FORM
        echo '                        <form id="archive-form-' . $id . '" action="' . $archiveRoute . '" method="POST" class="m-0 p-0" onsubmit="return confirm(\'Are you sure you want to archive this service?\')">';
        echo '                            ' . csrf_field();
        echo '                            <button type="submit" class="w-full text-left px-3 py-1.5 text-rose-600 hover:bg-rose-50 font-semibold cursor-pointer">Archive Service</button>';
        echo '                        </form>';

        echo '                    </div>';
        echo '                </div>';
        echo '            </div>';
        echo '        </div>';
        echo '    </td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="11" class="py-8 text-center text-slate-400">No services found in database.</td></tr>';
}

echo '        </tbody>';
echo '    </table>';
echo '</div>';

// 8. TABLE FOOTER & PAGINATION BAR (DYNAMICALLY CALCULATED)
if ($serviceList instanceof \Illuminate\Pagination\LengthAwarePaginator) {
    $total = $serviceList->total();
    $first = $serviceList->firstItem() ?? 0;
    $last = $serviceList->lastItem() ?? 0;
    $perPage = $serviceList->perPage();

    echo '<div class="flex items-center justify-between bg-white border border-slate-200 rounded-lg p-3 shadow-sm text-xs text-slate-500 mb-6">';
    echo '    <div>';
    echo '        Showing <span class="font-bold text-slate-800">' . $first . '</span> to <span class="font-bold text-slate-800">' . $last . '</span> of <span class="font-bold text-slate-800">' . $total . '</span> results';
    echo '    </div>';
    
    echo '    <div class="flex items-center space-x-6">';
    echo '        <div class="flex items-center space-x-2">';
    echo '            <span>Records per page</span>';
    echo '            <form method="GET" action="' . route('services.index') . '" class="m-0 p-0">';
    foreach (request()->except(['per_page', 'page']) as $k => $v) {
        if ($v) echo '<input type="hidden" name="' . htmlspecialchars($k) . '" value="' . htmlspecialchars($v) . '">';
    }
    echo '                <select name="per_page" onchange="this.form.submit()" class="border border-slate-300 rounded px-2 py-1 text-xs text-slate-700 bg-white">';
    echo '                    <option value="10"' . ($perPage == 10 ? ' selected' : '') . '>10</option>';
    echo '                    <option value="25"' . ($perPage == 25 ? ' selected' : '') . '>25</option>';
    echo '                    <option value="50"' . ($perPage == 50 ? ' selected' : '') . '>50</option>';
    echo '                    <option value="100"' . ($perPage == 100 ? ' selected' : '') . '>100</option>';
    echo '                </select>';
    echo '            </form>';
    echo '        </div>';

    // PAGINATION LINKS
    echo '        <div>';
    echo $serviceList->links();
    echo '        </div>';
    echo '    </div>';
    echo '</div>';
}

// 9. Modals (What is a Service - UPDATED BIGGER & JUSTIFIED, Import, Quick Add)
echo '<div id="whatIsServiceModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden flex justify-center items-center p-4 z-50">';
echo '    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-8 text-base space-y-5">';
echo '        <div class="flex justify-between items-center border-b pb-4">';
echo '            <h3 class="text-xl font-bold text-slate-900">What is a Service?</h3>';
echo '            <button type="button" onclick="document.getElementById(\'whatIsServiceModal\').classList.add(\'hidden\')" class="text-slate-400 hover:text-slate-600 text-2xl font-bold cursor-pointer">&times;</button>';
echo '        </div>';
echo '        <p class="text-slate-700 leading-relaxed text-base font-normal text-justify">';
echo '            A <strong class="font-bold text-slate-900">Service</strong> defines a standardized productized offering in your firm’s catalog. It includes workflow activities, default pricing models, client deliverables, required documents, and approval rules used across proposals and active client contracts.';
echo '        </p>';
echo '        <div class="flex justify-end pt-3 border-t">';
echo '            <button type="button" onclick="document.getElementById(\'whatIsServiceModal\').classList.add(\'hidden\')" class="px-6 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-semibold text-sm rounded-lg shadow-md cursor-pointer transition">';
echo '                Got it';
echo '            </button>';
echo '        </div>';
echo '    </div>';
echo '</div>';

echo '<div id="importServiceModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden flex justify-center items-center p-4 z-50">';
echo '    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 text-xs space-y-4">';
echo '        <div class="flex justify-between items-center border-b pb-3">';
echo '            <h3 class="text-sm font-bold text-slate-900">Import Service Specification</h3>';
echo '            <button type="button" onclick="document.getElementById(\'importServiceModal\').classList.add(\'hidden\')" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>';
echo '        </div>';
echo '        <form action="' . (Route::has('services.import') ? route('services.import') : '#') . '" method="POST" enctype="multipart/form-data" class="space-y-4">';
echo '            <input type="hidden" name="_token" value="' . csrf_token() . '">';
echo '            <div>';
echo '                <label class="block font-semibold text-slate-700 mb-1">Select JSON File *</label>';
echo '                <input type="file" name="json_file" accept=".json" required class="w-full border border-slate-300 rounded px-3 py-2 text-xs outline-none focus:border-blue-500">';
echo '            </div>';
echo '            <div class="flex justify-end space-x-2 pt-2 border-t">';
echo '                <button type="button" onclick="document.getElementById(\'importServiceModal\').classList.add(\'hidden\')" class="px-3.5 py-1.5 border border-slate-300 text-slate-700 font-medium rounded cursor-pointer">Cancel</button>';
echo '                <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white font-medium rounded shadow-sm cursor-pointer">Upload & Import</button>';
echo '            </div>';
echo '        </form>';
echo '    </div>';
echo '</div>';

// QUICK ADD MODAL WITH EXACT 3 TAX TREATMENT OPTIONS
echo '<div id="quickAddModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden flex justify-center items-center p-4 z-50">';
echo '    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 text-xs space-y-4">';
echo '        <div class="flex justify-between items-center border-b pb-3">';
echo '            <div>';
echo '                <h3 class="text-sm font-bold text-slate-900">Quick Add Service</h3>';
echo '                <p class="text-[11px] text-slate-500 mt-0.5">Create a draft service shell to configure in workspace.</p>';
echo '            </div>';
echo '            <button type="button" onclick="document.getElementById(\'quickAddModal\').classList.add(\'hidden\')" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>';
echo '        </div>';
echo '        <form action="' . (Route::has('services.store') ? route('services.store') : '/services') . '" method="POST" class="space-y-3">';
echo '            <input type="hidden" name="_token" value="' . csrf_token() . '">';
echo '            <div>';
echo '                <label class="block font-semibold text-slate-700 mb-1">Service Name *</label>';
echo '                <input type="text" name="name" required placeholder="e.g. On-site Profit and Loss Review" class="w-full border border-slate-300 rounded px-3 py-2 text-xs outline-none focus:border-blue-500">';
echo '            </div>';
echo '            <div class="grid grid-cols-2 gap-3">';
echo '                <div>';
echo '                    <label class="block font-semibold text-slate-700 mb-1">Service Area *</label>';
echo '                    <select name="service_area" required class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs text-slate-700 outline-none focus:border-blue-500 bg-white">';
echo '                        <option value="Corporate & Regulatory Advisory">Corporate & Regulatory Advisory</option>';
echo '                        <option value="Accounting & Compliance Advisory">Accounting & Compliance Advisory</option>';
echo '                        <option value="Governance & Policy Advisory">Governance & Policy Advisory</option>';
echo '                        <option value="People & Talent Solutions">People & Talent Solutions</option>';
echo '                        <option value="Strategic Situations Advisory">Strategic Situations Advisory</option>';
echo '                        <option value="Business Strategy & Process Advisory">Business Strategy & Process Advisory</option>';
echo '                        <option value="Learning & Capability Development">Learning & Capability Development</option>';
echo '                        <option value="Service Add-Ons">Service Add-Ons</option>';
echo '                        <option value="Others">Others</option>';
echo '                    </select>';
echo '                </div>';
echo '                <div>';
echo '                    <label class="block font-semibold text-slate-700 mb-1">Category *</label>';
echo '                    <select name="category" required class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs text-slate-700 outline-none focus:border-blue-500 bg-white">';
echo '                        <option value="">-- Select --</option>';
echo '                        <option value="Accountancy Revenue">Accountancy Revenue</option>';
echo '                        <option value="Share Transfer & Stockholder Compliance">Share Transfer & Stockholder Compliance</option>';
echo '                        <option value="Consulting Revenue">Consulting Revenue</option>';
echo '                        <option value="Managed Administrative Support Services">Managed Administrative Support Services</option>';
echo '                        <option value="Compliance Revenue">Compliance Revenue</option>';
echo '                        <option value="LGU and Local Permit Compliance">LGU and Local Permit Compliance</option>';
echo '                        <option value="Compliance">Compliance</option>';
echo '                    </select>';
echo '                </div>';
echo '            </div>';
echo '            <div class="grid grid-cols-2 gap-3">';
echo '                <div>';
echo '                    <label class="block font-semibold text-slate-700 mb-1">Engagement Behavior *</label>';
echo '                    <select name="engagement_behavior" required class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs text-slate-700 outline-none focus:border-blue-500 bg-white">';
echo '                        <option value="project">Project Engagement</option>';
echo '                        <option value="regular">Regular (Retainer)</option>';
echo '                        <option value="both">Both (Hybrid)</option>';
echo '                    </select>';
echo '                </div>';
echo '                <div>';
echo '                    <label class="block font-semibold text-slate-700 mb-1">Tax Treatment *</label>';
echo '                    <select name="tax_treatment" required class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs text-slate-700 outline-none focus:border-blue-500 bg-white">';
echo '                        <option value="VAT Exclusive" selected>VAT Exclusive</option>';
echo '                        <option value="VAT Inclusive">VAT Inclusive</option>';
echo '                        <option value="VAT Exempt">VAT Exempt</option>';
echo '                    </select>';
echo '                </div>';
echo '            </div>';
echo '            <div>';
echo '                <label class="block font-semibold text-slate-700 mb-1">Short Description (Optional)</label>';
echo '                <textarea name="short_description" rows="2" placeholder="Brief summary for internal catalog..." class="w-full border border-slate-300 rounded px-3 py-1.5 text-xs outline-none focus:border-blue-500"></textarea>';
echo '            </div>';
echo '            <div class="flex justify-end space-x-2 pt-3 border-t">';
echo '                <button type="button" onclick="document.getElementById(\'quickAddModal\').classList.add(\'hidden\')" class="px-3.5 py-1.5 border border-slate-300 text-slate-700 font-medium rounded hover:bg-slate-50 transition cursor-pointer">Cancel</button>';
echo '                <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded shadow-sm transition cursor-pointer">Save Service</button>';
echo '            </div>';
echo '        </form>';
echo '    </div>';
echo '</div>';
?>

<script>
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
    if (!e.target.closest('.svc-action-dropdown')) {
        const dropdowns = document.querySelectorAll('.svc-action-dropdown');
        dropdowns.forEach(menu => menu.classList.add('hidden'));
    }
});
</script>
@endsection
@php
    // Mode
    $mode = $mode ?? request('mode', 'view');
    $isViewOnly = $mode === 'view';

    // Logged-in Approver/User Details
    $currentUser = auth()->user();
    $approverName = $currentUser->name ?? 'Manager Test User';

    // Dashboard URL
    $dashboardUrl = Route::has('dashboard') 
        ? route('dashboard') 
        : (Route::has('services.index') ? route('services.index') : url('/'));

    // Current tab parameter mapping
    $requestedTab = strtolower(request('tab', 'overview'));

    if (str_contains($requestedTab, 'wersion') || str_contains($requestedTab, 'version')) {
        $currentTab = 'versions_history';
    } elseif (str_contains($requestedTab, 'usage')) {
        $currentTab = 'usage_performance';
    } elseif (str_contains($requestedTab, 'proposal')) {
        $currentTab = 'proposal_content';
    } elseif (str_contains($requestedTab, 'req')) {
        $currentTab = 'requirements';
    } elseif (str_contains($requestedTab, 'work')) {
        $currentTab = 'workflow';
    } elseif (str_contains($requestedTab, 'comm')) {
        $currentTab = 'commercials';
    } elseif (str_contains($requestedTab, 'engag')) {
        $currentTab = 'engagement';
    } elseif (str_contains($requestedTab, 'report')) {
        $currentTab = 'reporting';
    } elseif (str_contains($requestedTab, 'auto')) {
        $currentTab = 'automation';
    } elseif (str_contains($requestedTab, 'term')) {
        $currentTab = 'terms';
    } else {
        $currentTab = 'overview';
    }

    $activeVersion = $service->activeVersion ?? null;

    // Default Reimbursables Data Setup
    $rawReimbursables = old('reimbursables', $activeVersion->reimbursables ?? []);
    if (is_string($rawReimbursables)) {
        $rawReimbursables = json_decode($rawReimbursables, true) ?? [];
    }
    if (empty($rawReimbursables) || !is_array($rawReimbursables)) {
        $defaultReimbursables = [
            ['category' => 'Government Fees', 'classification' => 'Reimbursable', 'notes' => 'BIR, SEC, or LGU official fees'],
            ['category' => 'Taxes & Statutory Fees', 'classification' => 'Excluded', 'notes' => 'Client statutory liabilities'],
            ['category' => 'Notarial Costs', 'classification' => 'Reimbursable', 'notes' => 'Actual notary charges'],
            ['category' => 'Courier & Transportation', 'classification' => 'Estimate Only', 'notes' => 'Out-of-pocket delivery costs']
        ];
    } else {
        $defaultReimbursables = array_values($rawReimbursables);
    }

    // Main Activities List Setup
    $mainActivitiesList = $activeVersion->mainActivities ?? ($activeVersion->activities ?? []);

    // Section Completeness Checks
    $overviewComplete = !empty($activeVersion) && (
        !empty(trim($activeVersion->internal_description ?? '')) ||
        !empty(trim($activeVersion->client_description ?? '')) ||
        !empty(trim($service->description ?? ''))
    );

    $proposalComplete = !empty($activeVersion) && (
        !empty(trim($activeVersion->scope_of_work ?? '')) ||
        !empty(trim($activeVersion->deliverables ?? ''))
    );

    $reqsComplete = !empty($activeVersion) && isset($activeVersion->requirements) && count($activeVersion->requirements) > 0;
    $workflowComplete = !empty($activeVersion) && isset($mainActivitiesList) && count($mainActivitiesList) > 0;
    $commercialsComplete = !empty($activeVersion) && !empty($activeVersion->pricing_model) && (float)($activeVersion->standard_price ?? 0) > 0;
    $engagementComplete = !empty($service->engagement_behavior) && !empty($activeVersion->recurrence_frequency);
    $reportingComplete = !empty($activeVersion) && !empty($activeVersion->reporting_frequency) && !empty($activeVersion->project_reporting);
    $automationComplete = !empty($activeVersion) && !empty($activeVersion->base_reference_date);
    $termsComplete = !empty($activeVersion->terms_and_conditions) || !empty($activeVersion->terms);

    $allSectionsComplete = true;

    // Route Templates for Alpine JS Binding
    $reqUpdateRouteTemplate = Route::has('services.requirements.update') ? route('services.requirements.update', ':id') : '#';
    $actUpdateRouteTemplate = Route::has('services.activities.update') ? route('services.activities.update', ':id') : '#';
@endphp

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $service->name ?? 'Service' }} - ORDO Service Workspace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>[x-cloak] { display: none !important; }</style>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen overflow-hidden">

    <div class="flex h-screen w-screen overflow-hidden relative">

        <!-- SIDEBAR -->
        <aside class="w-64 bg-white border-r border-slate-200 flex flex-col shrink-0 h-screen fixed top-0 left-0 z-20 select-none">
            <div class="p-5 border-b border-slate-100 shrink-0">
                <h1 class="text-base font-serif font-bold text-slate-900 leading-tight">
                    {{ $approverName }}
                </h1>
                <p class="text-xs font-serif font-semibold text-blue-800 tracking-wide">&amp; Company</p>
            </div>

            <div class="px-5 pt-4 pb-2 flex justify-between items-center text-xs shrink-0">
                <div>
                    <span class="font-bold text-slate-900 block">Enterprise Menu</span>
                    <span class="text-[10px] text-slate-400 block -mt-0.5">Navigation</span>
                </div>
                <i class="fa-solid fa-angles-right text-slate-300 text-[10px]"></i>
            </div>

            <nav class="flex-1 px-3 py-2 space-y-1 text-xs font-medium text-slate-600 overflow-y-auto">
                <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-slate-900 transition">
                    <i class="fa-regular fa-user text-slate-400 w-4 text-center"></i>
                    <span>Admin</span>
                </a>
                <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-slate-900 transition">
                    <i class="fa-regular fa-building text-slate-400 w-4 text-center"></i>
                    <span>Town Hall</span>
                </a>
                <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-slate-900 transition">
                    <i class="fa-regular fa-folder text-slate-400 w-4 text-center"></i>
                    <span>Corporate</span>
                </a>
                <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-slate-900 transition">
                    <i class="fa-regular fa-file-lines text-slate-400 w-4 text-center"></i>
                    <span>Policies</span>
                </a>
                <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-slate-900 transition">
                    <i class="fa-solid fa-calculator text-slate-400 w-4 text-center"></i>
                    <span>Finance</span>
                </a>
                <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-slate-900 transition">
                    <i class="fa-regular fa-address-card text-slate-400 w-4 text-center"></i>
                    <span>Human Capital</span>
                </a>

                <div x-data="{ open: true }" class="space-y-1">
                    <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 rounded-lg bg-blue-50/70 text-blue-600 font-semibold transition cursor-pointer">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-bullhorn text-blue-600 w-4 text-center"></i>
                            <span>Marketing</span>
                        </div>
                        <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="open ? '' : '-rotate-90'"></i>
                    </button>

                    <div x-show="open" class="pl-9 pr-2 space-y-1 pt-1">
                        <a href="#" class="block py-1.5 px-2 rounded text-slate-600 hover:text-slate-900 transition">Product</a>
                        <a href="{{ Route::has('services.index') ? route('services.index') : '#' }}" class="block py-1.5 px-2 rounded font-bold text-blue-600 bg-blue-50/40 transition">Services</a>
                        <a href="#" class="flex items-center justify-between py-1.5 px-2 rounded text-slate-600 hover:text-slate-900 transition">
                            <span>Requirements</span>
                            <span class="bg-blue-100 text-blue-700 text-[9px] font-bold px-1.5 py-0.2 rounded-full">New</span>
                        </a>
                    </div>
                </div>

                <a href="#" class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-slate-900 transition">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid fa-file-signature text-purple-600 w-4 text-center"></i>
                        <span class="font-semibold text-slate-800">Proposals &amp; Contracts</span>
                    </div>
                    <span class="bg-purple-100 text-purple-700 text-[9px] font-bold px-1.5 py-0.2 rounded-full">New</span>
                </a>

                <a href="#" class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-slate-900 transition">
                    <div class="flex items-center space-x-3">
                        <i class="fa-regular fa-credit-card text-slate-400 w-4 text-center"></i>
                        <span>Accounts</span>
                    </div>
                    <i class="fa-solid fa-chevron-right text-[10px] text-slate-300"></i>
                </a>

                <a href="#" class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-slate-900 transition">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid fa-gear text-slate-400 w-4 text-center"></i>
                        <span>Operations</span>
                    </div>
                    <i class="fa-solid fa-chevron-right text-[10px] text-slate-300"></i>
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 h-screen overflow-y-auto bg-slate-50 flex flex-col ml-64 relative z-10">

            <!-- Breadcrumb Header -->
            <div class="sticky top-0 z-20 bg-white border-b border-slate-200 px-8 py-3 flex justify-between items-center text-xs text-slate-500 shrink-0 shadow-sm">
                <div class="flex items-center space-x-2">
                    <span class="font-bold text-slate-900 bg-slate-900 text-white px-2 py-0.5 rounded text-[10px]">OR</span>
                    <a href="{{ Route::has('services.index') ? route('services.index') : '#' }}" class="font-bold text-slate-700 hover:underline">ORDO Services</a>
                    <span>/</span>
                    <span class="text-slate-400 font-mono">{{ $service->service_code ?? 'SVC-0061' }}</span>
                </div>
                <a href="{{ $dashboardUrl }}" class="text-xs text-blue-600 hover:underline flex items-center space-x-1">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Back to Dashboard</span>
                </a>
            </div>

            <!-- Root Workspace Container -->
            <div class="max-w-7xl w-full mx-auto px-8 py-6 space-y-6 flex-1" 
                 x-data="{ 
                    activeTab: '{{ $currentTab }}', 
                    showActivityModal: false, 
                    showBulkImportModal: false,
                    showEditActivityModal: false,
                    showReqModal: false,
                    showEditReqModal: false,
                    selectedActivities: [],
                    selectAll: false,
                    init() {
                        const urlParams = new URLSearchParams(window.location.search);
                        const tabFromUrl = urlParams.get('tab');
                        if (tabFromUrl) {
                            this.activeTab = tabFromUrl;
                        }
                        window.addEventListener('popstate', () => {
                            const params = new URLSearchParams(window.location.search);
                            this.activeTab = params.get('tab') || '{{ $currentTab }}';
                        });
                    },
                    changeTab(tabName) {
                        this.activeTab = tabName;
                        const url = new URL(window.location);
                        url.searchParams.set('tab', tabName);
                        window.history.pushState({}, '', url);
                    },
                    toggleSelectAll() {
                        if (this.selectAll) {
                            let ids = [];
                            document.querySelectorAll('.activity-checkbox').forEach(cb => {
                                cb.checked = true;
                                if (cb.value) ids.push(cb.value);
                            });
                            this.selectedActivities = ids;
                        } else {
                            document.querySelectorAll('.activity-checkbox').forEach(cb => cb.checked = false);
                            this.selectedActivities = [];
                        }
                    },
                    updateSelected() {
                        let ids = [];
                        document.querySelectorAll('.activity-checkbox:checked').forEach(cb => {
                            if (cb.value) ids.push(cb.value);
                        });
                        this.selectedActivities = ids;
                        this.selectAll = ids.length > 0 && ids.length === document.querySelectorAll('.activity-checkbox').length;
                    },
                    expandedActivities: {},
                    standardPrice: {{ old('standard_price', $activeVersion->standard_price ?? 500) }},
                    costOfService: {{ old('cost_of_service', $activeVersion->cost_of_service ?? 0) }},
                    get calculatedTargetMargin() {
                        let price = parseFloat(this.standardPrice) || 0;
                        let cost = parseFloat(this.costOfService) || 0;
                        if (price <= 0) return '100.0';
                        return (((price - cost) / price) * 100).toFixed(1);
                    },
                    reimbursables: {{ json_encode($defaultReimbursables) }},
                    addReimbursable() {
                        this.reimbursables.push({ category: '', classification: 'Reimbursable', notes: '' });
                    },
                    removeReimbursable(index) {
                        this.reimbursables.splice(index, 1);
                    },
                    toggleSubActivities(id) {
                        this.expandedActivities[id] = !this.expandedActivities[id];
                    },
                    addActivityData: { level: 'main', parent_id: '', name: '', description: '', sequence: 1, expected_days: 1, expected_working_hours: 1.0, is_mandatory: 1, is_billable: 1 },
                    editActivityData: { id: '', level: 'main', parent_id: '', name: '', description: '', sequence: 1, expected_days: 1, expected_working_hours: 1.0, is_mandatory: 1, is_billable: 1 },
                    openEditActivityModal(data) { 
                        this.editActivityData = {
                            id: data.id || '',
                            level: data.parent_id ? 'sub' : 'main',
                            parent_id: data.parent_id || '',
                            name: data.name || '',
                            description: data.description || '',
                            sequence: data.sequence || 1,
                            expected_days: data.expected_days || 1,
                            expected_working_hours: data.expected_working_hours || 1.0,
                            is_mandatory: data.is_mandatory ? 1 : 0,
                            is_billable: data.is_billable ? 1 : 0
                        }; 
                        this.showEditActivityModal = true; 
                    },
                    addReqData: { requirement_name: '', client_type: 'All', source: 'Client-supplied', is_mandatory: true, file_required: true, conditional_rule: '', validity_expiration: '', template_link: '', instructions: '' },
                    editReqData: { id: '', requirement_name: '', client_type: 'All', source: 'Client-supplied', is_mandatory: true, file_required: true, conditional_rule: '', validity_expiration: '', template_link: '', instructions: '' },
                    openEditReqModal(data) { 
                        this.editReqData = {
                            id: data.id || '',
                            requirement_name: data.requirement_name || '',
                            client_type: data.client_type || 'All',
                            source: data.source || 'Client-supplied',
                            is_mandatory: data.is_mandatory ? 1 : 0,
                            file_required: data.file_required ? 1 : 0,
                            conditional_rule: data.conditional_rule || '',
                            validity_expiration: data.validity_expiration || '',
                            template_link: data.template_link || '',
                            instructions: data.instructions || ''
                        };
                        this.showEditReqModal = true; 
                    }
                 }">

                <!-- COMPLETENESS GATE BANNER -->
                @if($allSectionsComplete)
                    <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg text-xs flex justify-between items-center shadow-sm relative z-30 pointer-events-auto">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-circle-check text-blue-600 text-sm"></i>
                            <span><strong>Completeness Gate Cleared:</strong> All mandatory sections have been completed!</span>
                        </div>
                        <form action="{{ Route::has('services.submit_approval') && isset($service->id) ? route('services.submit_approval', $service->id) : $dashboardUrl }}" method="POST" class="m-0 p-0">
                            @csrf
                            <input type="hidden" name="redirect_to" value="{{ $dashboardUrl }}">
                            <button type="submit" 
                                    onclick="return confirm('Are you sure you want to submit this service for approval?');"
                                    class="bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-[11px] font-bold px-4 py-2 rounded transition shadow-sm inline-flex items-center space-x-1 cursor-pointer">
                                <i class="fa-solid fa-paper-plane mr-1"></i>
                                <span>Submit for Approval</span>
                            </button>
                        </form>
                    </div>
                @else
                    <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg text-xs flex justify-between items-center shadow-sm">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-triangle-exclamation text-amber-600 text-sm"></i>
                            <span><strong>Completeness Gate Pending:</strong> Please complete all required sections to enable approval submission.</span>
                        </div>
                        <span class="text-[11px] font-bold text-amber-700 uppercase bg-amber-100 px-2 py-1 rounded">Incomplete</span>
                    </div>
                @endif

                <!-- Header Card -->
                <div class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm flex justify-between items-start">
                    <div>
                        <div class="flex items-center space-x-3">
                            <span class="font-mono text-xs px-2.5 py-1 bg-blue-50 text-blue-700 font-semibold rounded border border-blue-200">
                                {{ $service->service_code ?? 'SVC-0061' }}
                            </span>
                            <h1 class="text-2xl font-bold text-slate-900">{{ $service->name ?? 'Digital Transformation' }}</h1>

                            @if($isViewOnly)
                                <span class="bg-slate-100 text-slate-700 border border-slate-300 px-2.5 py-0.5 text-[10px] rounded font-bold uppercase tracking-wider">
                                    <i class="fa-solid fa-eye mr-1"></i> READ ONLY MODE
                                </span>
                            @else
                                <span class="bg-amber-100 text-amber-800 border border-amber-300 px-2.5 py-0.5 text-[10px] rounded font-bold uppercase tracking-wider">
                                    <i class="fa-solid fa-pen-to-square mr-1"></i> EDITING MODE
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-2">
                            Area: <span class="font-semibold text-slate-700">{{ $service->service_area ?? 'Corporate & Regulatory Advisory' }}</span> | 
                            Category: <span class="font-semibold text-slate-700">{{ $service->category ?? 'Advisory Revenue' }}</span> | 
                            Engagement: <span class="font-semibold text-slate-700 capitalize">{{ $service->engagement_behavior ?? 'Project' }}</span> |
                            Expected Turnaround: <span class="font-semibold text-slate-700">{{ $activeVersion->expected_turnaround ?? '5-7 days' }}</span>
                        </p>
                    </div>

                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1 text-xs rounded-full font-bold uppercase bg-amber-100 text-amber-700">
                            {{ str_replace('_', ' ', $service->status ?? 'DRAFT') }}
                        </span>

                        <span class="text-xs font-mono font-semibold text-slate-500 bg-slate-100 px-2.5 py-1 rounded">
                            Version {{ $activeVersion->version_number ?? 'V1.0' }}
                        </span>

                        @if(Route::has('services.workspace') && isset($service->id))
                            @if($isViewOnly)
                                <a href="{{ route('services.workspace', ['service' => $service->id, 'mode' => 'edit', 'tab' => $currentTab]) }}" 
                                   class="bg-amber-500 hover:bg-amber-600 text-white font-semibold text-xs px-3 py-1.5 rounded shadow-sm transition inline-flex items-center space-x-1 cursor-pointer">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    <span>Edit Workspace</span>
                                </a>
                            @else
                                <a href="{{ route('services.workspace', ['service' => $service->id, 'mode' => 'view', 'tab' => $currentTab]) }}" 
                                   class="border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs px-3 py-1.5 rounded transition inline-flex items-center space-x-1 cursor-pointer">
                                    <i class="fa-solid fa-eye"></i>
                                    <span>View Only</span>
                                </a>
                            @endif
                        @endif
                    </div>
                </div>

                <!-- TABS NAVIGATION -->
                <div class="bg-white border border-slate-200 rounded-lg p-2.5 shadow-sm z-10">
                    <div class="flex flex-wrap gap-2 text-xs font-medium text-slate-600">

                        <!-- Tab 1 -->
                        <button type="button" @click="changeTab('overview')" 
                                :class="activeTab === 'overview' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="px-2.5 py-1.5 rounded-lg border transition flex items-center space-x-1 cursor-pointer">
                            <i class="fa-solid fa-circle-info text-[11px]"></i>
                            <span>(1) Overview</span>
                            @if(!empty($overviewComplete))
                                <i class="fa-solid fa-circle-check text-emerald-400 text-[11px] ml-0.5"></i>
                            @endif
                        </button>

                        <!-- Tab 2 -->
                        <button type="button" @click="changeTab('proposal_content')" 
                                :class="activeTab === 'proposal_content' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="px-2.5 py-1.5 rounded-lg border transition flex items-center space-x-1 cursor-pointer">
                            <i class="fa-solid fa-file-contract text-[11px]"></i>
                            <span>(2) Proposal Content</span>
                            @if(!empty($proposalComplete))
                                <i class="fa-solid fa-circle-check text-emerald-400 text-[11px] ml-0.5"></i>
                            @endif
                        </button>

                        <!-- Tab 3 -->
                        <button type="button" @click="changeTab('requirements')" 
                                :class="activeTab === 'requirements' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="px-2.5 py-1.5 rounded-lg border transition flex items-center space-x-1 cursor-pointer">
                            <i class="fa-solid fa-folder-open text-[11px]"></i>
                            <span>(3) Requirements</span>
                            @if(!empty($reqsComplete))
                                <i class="fa-solid fa-circle-check text-emerald-400 text-[11px] ml-0.5"></i>
                            @endif
                        </button>

                        <!-- Tab 4 -->
                        <button type="button" @click="changeTab('workflow')" 
                                :class="activeTab === 'workflow' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="px-2.5 py-1.5 rounded-lg border transition flex items-center space-x-1 cursor-pointer">
                            <i class="fa-solid fa-list-check text-[11px]"></i>
                            <span>(4) Workflow</span>
                            @if(!empty($workflowComplete))
                                <i class="fa-solid fa-circle-check text-emerald-400 text-[11px] ml-0.5"></i>
                            @endif
                        </button>

                        <!-- Tab 5 -->
                        <button type="button" @click="changeTab('commercials')" 
                                :class="activeTab === 'commercials' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="px-2.5 py-1.5 rounded-lg border transition flex items-center space-x-1 cursor-pointer">
                            <i class="fa-solid fa-tags text-[11px]"></i>
                            <span>(5) Commercials</span>
                            @if(!empty($commercialsComplete))
                                <i class="fa-solid fa-circle-check text-emerald-400 text-[11px] ml-0.5"></i>
                            @endif
                        </button>

                        <!-- Tab 6 -->
                        <button type="button" @click="changeTab('engagement')" 
                                :class="activeTab === 'engagement' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="px-2.5 py-1.5 rounded-lg border transition flex items-center space-x-1 cursor-pointer">
                            <i class="fa-solid fa-handshake text-[11px]"></i>
                            <span>(6) Engagement</span>
                            @if(!empty($engagementComplete))
                                <i class="fa-solid fa-circle-check text-emerald-400 text-[11px] ml-0.5"></i>
                            @endif
                        </button>

                        <!-- Tab 7 -->
                        <button type="button" @click="changeTab('reporting')" 
                                :class="activeTab === 'reporting' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="px-2.5 py-1.5 rounded-lg border transition flex items-center space-x-1 cursor-pointer">
                            <i class="fa-solid fa-chart-column text-[11px]"></i>
                            <span>(7) Reporting</span>
                            @if(!empty($reportingComplete))
                                <i class="fa-solid fa-circle-check text-emerald-400 text-[11px] ml-0.5"></i>
                            @endif
                        </button>

                        <!-- Tab 8 -->
                        <button type="button" @click="changeTab('automation')" 
                                :class="activeTab === 'automation' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="px-2.5 py-1.5 rounded-lg border transition flex items-center space-x-1 cursor-pointer">
                            <i class="fa-solid fa-robot text-[11px]"></i>
                            <span>(8) Automation</span>
                            @if(!empty($automationComplete))
                                <i class="fa-solid fa-circle-check text-emerald-400 text-[11px] ml-0.5"></i>
                            @endif
                        </button>

                        <!-- Tab 9 -->
                        <button type="button" @click="changeTab('terms')" 
                                :class="activeTab === 'terms' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="px-2.5 py-1.5 rounded-lg border transition flex items-center space-x-1 cursor-pointer">
                            <i class="fa-solid fa-scale-balanced text-[11px]"></i>
                            <span>(9) Terms</span>
                            @if(!empty($termsComplete))
                                <i class="fa-solid fa-circle-check text-emerald-400 text-[11px] ml-0.5"></i>
                            @endif
                        </button>

                        <!-- Tab 10 -->
                        <button type="button" @click="changeTab('usage_performance')" 
                                :class="activeTab === 'usage_performance' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="px-2.5 py-1.5 rounded-lg border transition flex items-center space-x-1 cursor-pointer">
                            <i class="fa-solid fa-chart-line text-[11px]"></i>
                            <span>Usage &amp; Performance</span>
                        </button>

                        <!-- Tab 11 -->
                        <button type="button" @click="changeTab('versions_history')" 
                                :class="activeTab === 'versions_history' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="px-2.5 py-1.5 rounded-lg border transition flex items-center space-x-1 cursor-pointer">
                            <i class="fa-solid fa-clock-rotate-left text-[11px]"></i>
                            <span>Versions &amp; History</span>
                        </button>

                    </div>
                </div>

                <!-- TAB 1: OVERVIEW -->
                <div x-show="activeTab === 'overview'" x-cloak class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-6">
                    <h2 class="text-sm font-bold text-slate-900 border-b pb-2">Service Overview &amp; Knowledge Base</h2>

                    <form action="{{ Route::has('services.version.update') && isset($service->id) ? route('services.version.update', ['service' => $service->id]) : '#' }}" method="POST" class="space-y-4 text-xs">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="tab" value="overview">
                        <input type="hidden" name="next_tab" value="proposal_content">
                        <input type="hidden" name="mode" value="{{ $mode }}">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Short Name (Display Alias)</label>
                                <input type="text" name="short_name" {{ $isViewOnly ? 'disabled' : '' }} value="{{ old('short_name', $service->short_name ?? '') }}" placeholder="Optional concise display name..." class="w-full border border-slate-300 rounded p-2 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Expected Turnaround Time (SLA)</label>
                                <input type="text" name="expected_turnaround" {{ $isViewOnly ? 'disabled' : '' }} value="{{ old('expected_turnaround', $activeVersion->expected_turnaround ?? '5-7 days') }}" placeholder="e.g. 5-7 days" class="w-full border border-slate-300 rounded p-2 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Effective Date</label>
                                <input type="date" name="effective_date" {{ $isViewOnly ? 'disabled' : '' }} value="{{ old('effective_date', $activeVersion->effective_date ?? '') }}" class="w-full border border-slate-300 rounded p-2 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Internal Description *</label>
                                <textarea name="internal_description" rows="3" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2.5 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}" placeholder="Internal explanation and context for associates...">{{ old('internal_description', $activeVersion->internal_description ?? '') }}</textarea>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Client-Facing Description</label>
                                <textarea name="client_description" rows="3" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2.5 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}" placeholder="Reusable client-facing summary for proposals...">{{ old('client_description', $activeVersion->client_description ?? '') }}</textarea>
                            </div>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    About This Service (Internal Explanation)
                                </label>
                                <span class="bg-slate-200 text-slate-600 text-[10px] font-semibold px-2 py-0.5 rounded">
                                    REFERENCE ONLY
                                </span>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed pt-1">
                                {{ $activeVersion->about_service ?: 'Standardized corporate service execution workflow for associate guidance, operational compliance, and quality assurance standard operating procedures.' }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Purpose</label>
                                <textarea name="purpose" rows="2" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2.5 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}" placeholder="Why the service exists...">{{ old('purpose', $activeVersion->purpose ?? '') }}</textarea>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">When to Use</label>
                                <textarea name="when_to_use" rows="2" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2.5 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}" placeholder="Typical situations where service applies...">{{ old('when_to_use', $activeVersion->when_to_use ?? '') }}</textarea>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">What This Service Is Not</label>
                            <input type="text" name="what_it_is_not" {{ $isViewOnly ? 'disabled' : '' }} value="{{ old('what_it_is_not', $activeVersion->what_it_is_not ?? '') }}" placeholder="Optional note to prevent confusion..." class="w-full border border-slate-300 rounded p-2 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}">
                        </div>

                        <div class="flex justify-end pt-4 border-t">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded text-xs transition shadow-sm cursor-pointer flex items-center space-x-1.5">
                                <span>Save &amp; Continue to Proposal Content</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TAB 2: PROPOSAL CONTENT -->
                <div x-show="activeTab === 'proposal_content'" x-cloak class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 border-b pb-2">Proposal Content Defaults</h2>
                    <form action="{{ Route::has('services.version.update') && isset($service->id) ? route('services.version.update', ['service' => $service->id]) : '#' }}" method="POST" class="space-y-4 text-xs">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="tab" value="proposal_content">
                        <input type="hidden" name="next_tab" value="requirements">
                        <input type="hidden" name="mode" value="{{ $mode }}">

                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Scope of Work *</label>
                            <textarea name="scope_of_work" rows="3" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2.5 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}" placeholder="Detail what the organization agrees to perform...">{{ old('scope_of_work', $activeVersion->scope_of_work ?? '') }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Deliverables / What You Will Receive *</label>
                                <textarea name="deliverables" rows="3" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2.5 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}" placeholder="Key tangible deliverables client receives...">{{ old('deliverables', $activeVersion->deliverables ?? '') }}</textarea>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Client Responsibilities</label>
                                <textarea name="client_responsibilities" rows="3" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2.5 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}" placeholder="Actions or obligations belonging to the client...">{{ old('client_responsibilities', $activeVersion->client_responsibilities ?? '') }}</textarea>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Exclusions / Out of Scope</label>
                            <textarea name="exclusions" rows="2" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2.5 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}" placeholder="What is not included unless separately agreed...">{{ old('exclusions', $activeVersion->exclusions ?? '') }}</textarea>
                        </div>

                        <div class="flex justify-between items-center pt-3 border-t">
                            <button type="button" @click="changeTab('overview')" class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer">&larr; Back to Overview</button>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded text-xs transition shadow-sm cursor-pointer flex items-center space-x-1.5">
                                <span>Save &amp; Continue to Requirements</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TAB 3: REQUIREMENTS -->
                <div x-show="activeTab === 'requirements'" x-cloak class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-4">
                    <div class="flex justify-between items-center border-b pb-3">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Structured Requirements &amp; Operational Checklist</h2>
                            <p class="text-[11px] text-slate-500">Configured requirements convert directly into operational engagement checklists without retyping.</p>
                        </div>
                        @if(!$isViewOnly)
                            <button type="button" @click="showReqModal = true" class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1.5 rounded font-semibold transition flex items-center space-x-1 cursor-pointer">
                                <i class="fa-solid fa-plus"></i>
                                <span>Add Requirement</span>
                            </button>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-semibold">
                                    <th class="py-2.5 px-3">REQUIREMENT NAME</th>
                                    <th class="py-2.5 px-3">CLIENT TYPE</th>
                                    <th class="py-2.5 px-3">STATUS</th>
                                    <th class="py-2.5 px-3">SOURCE</th>
                                    <th class="py-2.5 px-3">EVIDENCE NEEDED</th>
                                    <th class="py-2.5 px-3">CONDITIONAL RULE</th>
                                    <th class="py-2.5 px-3">VALIDITY</th>
                                    <th class="py-2.5 px-3">TEMPLATE</th>
                                    <th class="py-2.5 px-3 text-right">ACTION</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse($activeVersion->requirements ?? [] as $req)
                                    @php
                                        $reqObj = is_array($req) ? (object)$req : $req;
                                        $reqId = $reqObj->id ?? null;
                                    @endphp
                                    <tr class="hover:bg-slate-50">
                                        <td class="py-2.5 px-3 font-medium text-slate-900">{{ $reqObj->requirement_name }}</td>
                                        <td class="py-2.5 px-3"><span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-[10px] font-semibold">{{ $reqObj->client_type ?? 'All' }}</span></td>
                                        <td class="py-2.5 px-3 font-semibold {{ $reqObj->is_mandatory ? 'text-emerald-600' : 'text-amber-600' }}">{{ $reqObj->is_mandatory ? 'Mandatory' : 'Optional' }}</td>
                                        <td class="py-2.5 px-3">{{ $reqObj->source ?? 'Client-supplied' }}</td>
                                        <td class="py-2.5 px-3">{{ $reqObj->file_required ? 'File Upload / Soft Copy' : 'Physical Original' }}</td>
                                        <td class="py-2.5 px-3 text-slate-400 italic">{{ $reqObj->conditional_rule ?: 'None' }}</td>
                                        <td class="py-2.5 px-3 text-slate-400">{{ $reqObj->validity_expiration ?: 'N/A' }}</td>
                                        <td class="py-2.5 px-3 text-slate-400">{{ $reqObj->template_link ?: 'None' }}</td>
                                        <td class="py-2.5 px-3 text-right space-x-2 whitespace-nowrap">
                                            @if(!$isViewOnly)
                                                <button type="button" 
                                                        @click="openEditReqModal({{ json_encode($reqObj) }})" 
                                                        class="text-blue-600 hover:text-blue-800 hover:underline text-xs font-semibold cursor-pointer">
                                                    Edit
                                                </button>

                                                @if($reqId && Route::has('services.requirements.destroy'))
                                                    <form action="{{ route('services.requirements.destroy', $reqId) }}" 
                                                          method="POST" 
                                                          class="inline-block m-0 p-0"
                                                          onsubmit="return confirm('Are you sure you want to delete this requirement?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-rose-600 hover:text-rose-800 hover:underline text-xs font-semibold cursor-pointer">
                                                            Delete
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                <span class="text-slate-400 italic text-[10px]">Read-only</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="py-6 text-center text-slate-400 italic">
                                            No requirements configured for this service yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-between items-center pt-3 border-t">
                        <button type="button" @click="changeTab('proposal_content')" class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer">&larr; Back to Proposal Content</button>
                        <button type="button" @click="changeTab('workflow')" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded text-xs transition shadow-sm cursor-pointer flex items-center space-x-1.5">
                            <span>Continue to Workflow</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </button>
                    </div>

                    <!-- ADD REQUIREMENT MODAL -->
                    <template x-teleport="body">
                        <div x-show="showReqModal" x-cloak class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center p-4 z-[9999] overflow-y-auto">
                            <div @click.away="showReqModal = false" class="bg-white rounded-lg shadow-2xl max-w-lg w-full p-6 text-xs space-y-4 max-h-[90vh] overflow-y-auto relative pointer-events-auto">
                                <div class="flex justify-between items-center border-b pb-3">
                                    <h3 class="text-sm font-bold text-slate-900">Add Service Requirement</h3>
                                    <button type="button" @click="showReqModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 cursor-pointer">&times;</button>
                                </div>

                                <form action="{{ Route::has('services.requirements.store') && isset($service->id) ? route('services.requirements.store', $service->id) : '#' }}" method="POST" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="mode" value="{{ $mode }}">

                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Requirement Name *</label>
                                        <input type="text" name="requirement_name" required placeholder="e.g. BIR Form 2303 / Certificate of Registration" class="w-full border border-slate-300 rounded p-2 outline-none focus:border-blue-500 bg-white text-slate-800">
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Applies to Client Type</label>
                                            <select name="client_type" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                                <option value="All">All Client Types</option>
                                                <option value="Individual">Individual / Sole Person</option>
                                                <option value="Sole Proprietor">Sole Proprietor</option>
                                                <option value="OPC">OPC (One Person Corporation)</option>
                                                <option value="Corporation">Corporation</option>
                                                <option value="Partnership">Partnership</option>
                                                <option value="Cooperative">Cooperative</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Requirement Source</label>
                                            <select name="source" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                                <option value="Client-supplied">Client-supplied</option>
                                                <option value="Internally Prepared">Internally Prepared / Drafted</option>
                                                <option value="Government Portal">Government Portal</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Evidence Type Needed</label>
                                            <select name="file_required" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                                <option value="1">File Upload / Soft Copy</option>
                                                <option value="0">Physical Original / Hard Copy</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Mandatory Level</label>
                                            <select name="is_mandatory" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                                <option value="1">Mandatory (Required)</option>
                                                <option value="0">Optional / Conditional</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Conditional Rule (Optional)</label>
                                        <input type="text" name="conditional_rule" placeholder="e.g. Only required if revenue exceeds ₱3M" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Validity / Expiration Rule</label>
                                            <input type="text" name="validity_expiration" placeholder="e.g. Within last 6 months" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Template Link (Optional)</label>
                                            <input type="url" name="template_link" placeholder="https://..." class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Instructions / Handling Notes</label>
                                        <textarea name="instructions" rows="2" placeholder="Special guidance for associates when validating this requirement..." class="w-full border border-slate-300 rounded p-2.5 outline-none bg-white text-slate-800"></textarea>
                                    </div>

                                    <div class="flex justify-end space-x-2 pt-3 border-t">
                                        <button type="button" @click="showReqModal = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded font-medium cursor-pointer">Cancel</button>
                                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium cursor-pointer">Save Requirement</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </template>

                    <!-- EDIT REQUIREMENT MODAL -->
                    <template x-teleport="body">
                        <div x-show="showEditReqModal" x-cloak class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center p-4 z-[9999] overflow-y-auto">
                            <div @click.away="showEditReqModal = false" class="bg-white rounded-lg shadow-2xl max-w-lg w-full p-6 text-xs space-y-4 max-h-[90vh] overflow-y-auto relative pointer-events-auto">
                                <div class="flex justify-between items-center border-b pb-3">
                                    <h3 class="text-sm font-bold text-slate-900">Edit Service Requirement</h3>
                                    <button type="button" @click="showEditReqModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 cursor-pointer">&times;</button>
                                </div>

                                <form :action="'{{ $reqUpdateRouteTemplate }}'.replace(':id', editReqData.id)" method="POST" class="space-y-4">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="mode" value="{{ $mode }}">

                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Requirement Name *</label>
                                        <input type="text" name="requirement_name" x-model="editReqData.requirement_name" required class="w-full border border-slate-300 rounded p-2 outline-none focus:border-blue-500 bg-white text-slate-800">
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Applies to Client Type</label>
                                            <select name="client_type" x-model="editReqData.client_type" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                                <option value="All">All Client Types</option>
                                                <option value="Individual">Individual / Sole Person</option>
                                                <option value="Sole Proprietor">Sole Proprietor</option>
                                                <option value="OPC">OPC (One Person Corporation)</option>
                                                <option value="Corporation">Corporation</option>
                                                <option value="Partnership">Partnership</option>
                                                <option value="Cooperative">Cooperative</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Requirement Source</label>
                                            <select name="source" x-model="editReqData.source" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                                <option value="Client-supplied">Client-supplied</option>
                                                <option value="Internally Prepared">Internally Prepared / Drafted</option>
                                                <option value="Government Portal">Government Portal</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Evidence Type Needed</label>
                                            <select name="file_required" x-model="editReqData.file_required" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                                <option value="1">File Upload / Soft Copy</option>
                                                <option value="0">Physical Original / Hard Copy</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Mandatory Level</label>
                                            <select name="is_mandatory" x-model="editReqData.is_mandatory" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                                <option value="1">Mandatory (Required)</option>
                                                <option value="0">Optional / Conditional</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Conditional Rule (Optional)</label>
                                        <input type="text" name="conditional_rule" x-model="editReqData.conditional_rule" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Validity / Expiration Rule</label>
                                            <input type="text" name="validity_expiration" x-model="editReqData.validity_expiration" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Template Link (Optional)</label>
                                            <input type="url" name="template_link" x-model="editReqData.template_link" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Instructions / Handling Notes</label>
                                        <textarea name="instructions" x-model="editReqData.instructions" rows="2" class="w-full border border-slate-300 rounded p-2.5 outline-none bg-white text-slate-800"></textarea>
                                    </div>

                                    <div class="flex justify-end space-x-2 pt-3 border-t">
                                        <button type="button" @click="showEditReqModal = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded font-medium cursor-pointer">Cancel</button>
                                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium cursor-pointer">Update Requirement</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- TAB 4: WORKFLOW -->
                <div x-show="activeTab === 'workflow'" x-cloak class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-6">
                    <div class="flex justify-between items-start border-b pb-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Service Activity Workflow</h2>
                            <p class="text-xs text-slate-400 font-normal mt-0.5">2-Level Hierarchy: Main Activity &amp; Sub-Activity</p>
                        </div>
                        @if(!$isViewOnly)
                            <div class="flex items-center space-x-2">
                                <button type="button" @click="showBulkImportModal = true" class="border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs px-3 py-1.5 rounded font-medium transition cursor-pointer">
                                    <i class="fa-solid fa-folder-minus text-blue-600 text-xs mr-1"></i> + Bulk Import
                                </button>
                                <button type="button" @click="showActivityModal = true" class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1.5 rounded font-medium transition cursor-pointer">
                                    <i class="fa-solid fa-plus text-xs mr-1"></i> Add Activity
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- BULK ACTION TOOLBAR -->
                    <div x-show="selectedActivities.length > 0" class="bg-blue-50 border border-blue-200 text-blue-900 px-4 py-2 rounded-lg text-xs flex justify-between items-center shadow-sm">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-check-double text-blue-600"></i>
                            <span class="font-bold" x-text="selectedActivities.length + ' item(s) selected'"></span>
                        </div>
                        <div class="flex items-center space-x-2">
                            @if(Route::has('services.activities.bulk_destroy') && isset($service->id))
                                <form action="{{ route('services.activities.bulk_destroy', $service->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete selected activities?');">
                                    @csrf
                                    @method('DELETE')
                                    <template x-for="id in selectedActivities" :key="id">
                                        <input type="hidden" name="activity_ids[]" :value="id">
                                    </template>
                                    <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-semibold px-3 py-1 rounded text-xs transition cursor-pointer">
                                        <i class="fa-solid fa-trash-can me-1"></i> Delete Selected
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                                    <th class="py-2.5 px-3 w-8">
                                        <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()" class="rounded border-slate-300">
                                    </th>
                                    <th class="py-2.5 px-3 w-16">SEQ #</th>
                                    <th class="py-2.5 px-3">ACTIVITY / SUB-ACTIVITY NAME</th>
                                    <th class="py-2.5 px-3 text-center">EST. DAYS</th>
                                    <th class="py-2.5 px-3 text-center">WORKING HOURS</th>
                                    <th class="py-2.5 px-3 text-center">BILLABLE</th>
                                    <th class="py-2.5 px-3 text-right">ACTION</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($mainActivitiesList as $activity)
                                    @php
                                        $actObj = is_array($activity) ? (object)$activity : $activity;
                                        $actId = $actObj->id ?? null;
                                        $subActivities = $actObj->subActivities ?? ($actObj->children ?? []);
                                    @endphp
                                    <tr class="hover:bg-slate-50 bg-slate-50/40">
                                        <td class="py-2.5 px-3">
                                            <input type="checkbox" class="rounded border-slate-300 activity-checkbox" value="{{ $actId }}" @change="updateSelected()">
                                        </td>
                                        <td class="py-2.5 px-3 font-mono font-bold text-slate-700">{{ $actObj->sequence ?? 1 }}</td>
                                        <td class="py-2.5 px-3 font-semibold text-slate-900">
                                            @if(count($subActivities) > 0)
                                                <button type="button" @click="toggleSubActivities({{ $actId }})" class="mr-1 text-slate-400 hover:text-slate-600">
                                                    <i class="fa-solid" :class="expandedActivities[{{ $actId }}] ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                                </button>
                                            @endif
                                            <span>{{ $actObj->name }}</span>
                                            @if(!empty($actObj->description))
                                                <p class="text-[10px] text-slate-400 font-normal mt-0.5">{{ $actObj->description }}</p>
                                            @endif
                                        </td>
                                        <td class="py-2.5 px-3 text-center font-semibold">{{ $actObj->expected_days ?? 1 }} d</td>
                                        <td class="py-2.5 px-3 text-center font-mono">{{ number_format($actObj->expected_working_hours ?? 1, 1) }} hrs</td>
                                        <td class="py-2.5 px-3 text-center">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $actObj->is_billable ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                                {{ $actObj->is_billable ? 'YES' : 'NO' }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3 text-right space-x-2 whitespace-nowrap">
                                            @if(!$isViewOnly)
                                                <button type="button" @click="openEditActivityModal({{ json_encode($actObj) }})" class="text-blue-600 hover:text-blue-800 hover:underline font-semibold cursor-pointer">Edit</button>
                                                @if($actId && Route::has('services.activities.destroy'))
                                                    <form action="{{ route('services.activities.destroy', $actId) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this activity?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-rose-600 hover:text-rose-800 hover:underline font-semibold cursor-pointer">Delete</button>
                                                    </form>
                                                @endif
                                            @else
                                                <span class="text-slate-400 italic text-[10px]">Read-only</span>
                                            @endif
                                        </td>
                                    </tr>

                                    <!-- SUB ACTIVITIES -->
                                    @foreach($subActivities as $sub)
                                        @php
                                            $subObj = is_array($sub) ? (object)$sub : $sub;
                                            $subId = $subObj->id ?? null;
                                        @endphp
                                        <tr x-show="expandedActivities[{{ $actId }}]" class="hover:bg-slate-50 bg-white">
                                            <td class="py-2.5 px-3">
                                                <input type="checkbox" class="rounded border-slate-300 activity-checkbox" value="{{ $subId }}" @change="updateSelected()">
                                            </td>
                                            <td class="py-2.5 px-3 font-mono text-slate-400 pl-6">{{ $actObj->sequence }}.{{ $subObj->sequence ?? 1 }}</td>
                                            <td class="py-2.5 px-3 text-slate-700 pl-8 flex items-center space-x-2">
                                                <i class="fa-solid fa-turn-up rotate-90 text-slate-300 text-[10px]"></i>
                                                <span>{{ $subObj->name }}</span>
                                            </td>
                                            <td class="py-2.5 px-3 text-center text-slate-600">{{ $subObj->expected_days ?? 1 }} d</td>
                                            <td class="py-2.5 px-3 text-center font-mono text-slate-600">{{ number_format($subObj->expected_working_hours ?? 1, 1) }} hrs</td>
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $subObj->is_billable ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                                    {{ $subObj->is_billable ? 'YES' : 'NO' }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-right space-x-2 whitespace-nowrap">
                                                @if(!$isViewOnly)
                                                    <button type="button" @click="openEditActivityModal({{ json_encode($subObj) }})" class="text-blue-600 hover:text-blue-800 hover:underline font-semibold cursor-pointer">Edit</button>
                                                    @if($subId && Route::has('services.activities.destroy'))
                                                        <form action="{{ route('services.activities.destroy', $subId) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this sub-activity?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-rose-600 hover:text-rose-800 hover:underline font-semibold cursor-pointer">Delete</button>
                                                        </form>
                                                    @endif
                                                @else
                                                    <span class="text-slate-400 italic text-[10px]">Read-only</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-6 text-center text-slate-400 italic">
                                            No activities configured for this service yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-between items-center pt-3 border-t">
                        <button type="button" @click="changeTab('requirements')" class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer">&larr; Back to Requirements</button>
                        <button type="button" @click="changeTab('commercials')" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded text-xs cursor-pointer flex items-center space-x-1">
                            <span>Continue to Commercials</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </button>
                    </div>

                    <!-- BULK IMPORT MODAL -->
                    <template x-teleport="body">
                        <div x-show="showBulkImportModal" x-cloak class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center p-4 z-[9999] overflow-y-auto">
                            <div @click.away="showBulkImportModal = false" class="bg-white rounded-lg shadow-2xl max-w-lg w-full p-6 text-xs space-y-4 max-h-[90vh] overflow-y-auto relative pointer-events-auto">
                                <div class="flex justify-between items-center border-b pb-3">
                                    <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-2">
                                        <i class="fa-solid fa-folder-plus text-blue-600"></i>
                                        <span>Bulk Import Activities</span>
                                    </h3>
                                    <button type="button" @click="showBulkImportModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 cursor-pointer">&times;</button>
                                </div>

                                <form action="{{ Route::has('services.activities.bulk_import') && isset($service->id) ? route('services.activities.bulk_import', $service->id) : '#' }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="mode" value="{{ $mode }}">

                                    <div>
                                        <label class="block font-bold text-slate-800 mb-1">Upload CSV / Text File</label>
                                        <input type="file" name="import_file" accept=".csv,.txt,.xlsx,.xls" class="w-full border border-slate-300 rounded p-2 text-xs bg-white text-slate-700">
                                    </div>

                                    <div class="text-center font-bold text-slate-400 text-xs">— OR —</div>

                                    <div>
                                        <label class="block font-bold text-slate-800 mb-1">Paste Activity Outline Text</label>
                                        <textarea name="outline_text" rows="5" class="w-full border border-slate-300 rounded p-2.5 font-mono text-xs outline-none focus:border-blue-500 bg-white" placeholder="Main Activity 1&#10;&#9;Sub-activity 1.1&#10;&#9;Sub-activity 1.2&#10;Main Activity 2"></textarea>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2 border-t">
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Default Expected Days</label>
                                            <input type="number" name="default_expected_days" value="1" min="0" class="w-full border border-slate-300 rounded p-2 outline-none">
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Default Working Hours</label>
                                            <input type="number" step="0.5" name="default_working_hours" value="1.0" min="0" class="w-full border border-slate-300 rounded p-2 outline-none">
                                        </div>
                                    </div>

                                    <div class="flex justify-end space-x-2 pt-3 border-t">
                                        <button type="button" @click="showBulkImportModal = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded font-medium cursor-pointer">Cancel</button>
                                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium cursor-pointer">Start Bulk Import</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </template>

                    <!-- ADD ACTIVITY MODAL -->
                    <template x-teleport="body">
                        <div x-show="showActivityModal" x-cloak class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center p-4 z-[9999] overflow-y-auto">
                            <div @click.away="showActivityModal = false" class="bg-white rounded-lg shadow-2xl max-w-lg w-full p-6 text-xs space-y-4 max-h-[90vh] overflow-y-auto relative pointer-events-auto">
                                <div class="flex justify-between items-center border-b pb-3">
                                    <h3 class="text-sm font-bold text-slate-900">Add Service Activity</h3>
                                    <button type="button" @click="showActivityModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 cursor-pointer">&times;</button>
                                </div>

                                <form action="{{ Route::has('services.activities.store') && isset($service->id) ? route('services.activities.store', $service->id) : '#' }}" method="POST" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="mode" value="{{ $mode }}">

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Activity Level *</label>
                                            <select name="level" x-model="addActivityData.level" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                                <option value="main">Main Activity</option>
                                                <option value="sub">Sub-Activity</option>
                                            </select>
                                        </div>

                                        <div x-show="addActivityData.level === 'sub'">
                                            <label class="block font-semibold text-slate-700 mb-1">Parent Main Activity *</label>
                                            <select name="parent_id" x-model="addActivityData.parent_id" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                                <option value="">-- Select Parent Main Activity --</option>
                                                @foreach($mainActivitiesList as $mAct)
                                                    @php $mActObj = is_array($mAct) ? (object)$mAct : $mAct; @endphp
                                                    <option value="{{ $mActObj->id ?? '' }}">{{ $mActObj->name ?? '' }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Activity Name *</label>
                                        <input type="text" name="name" required placeholder="e.g. Initial Assessment / Document Verification" class="w-full border border-slate-300 rounded p-2 outline-none focus:border-blue-500 bg-white text-slate-800">
                                    </div>

                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Description / Instructions</label>
                                        <textarea name="description" rows="2" placeholder="Brief execution guidance for associates..." class="w-full border border-slate-300 rounded p-2.5 outline-none bg-white text-slate-800"></textarea>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Sequence #</label>
                                            <input type="number" name="sequence" min="1" value="1" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Expected Days (SLA)</label>
                                            <input type="number" name="expected_days" min="0" value="1" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Working Hours</label>
                                            <input type="number" step="0.5" name="expected_working_hours" min="0" value="1.0" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Billable Status</label>
                                            <select name="is_billable" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                                <option value="1">Billable</option>
                                                <option value="0">Non-Billable</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Mandatory Level</label>
                                            <select name="is_mandatory" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                                <option value="1">Mandatory Activity</option>
                                                <option value="0">Optional / Conditional</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="flex justify-end space-x-2 pt-3 border-t">
                                        <button type="button" @click="showActivityModal = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded font-medium cursor-pointer">Cancel</button>
                                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium cursor-pointer">Save Activity</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </template>

                    <!-- EDIT ACTIVITY MODAL -->
                    <template x-teleport="body">
                        <div x-show="showEditActivityModal" x-cloak class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center p-4 z-[9999] overflow-y-auto">
                            <div @click.away="showEditActivityModal = false" class="bg-white rounded-lg shadow-2xl max-w-lg w-full p-6 text-xs space-y-4 max-h-[90vh] overflow-y-auto relative pointer-events-auto">
                                <div class="flex justify-between items-center border-b pb-3">
                                    <h3 class="text-sm font-bold text-slate-900">Edit Service Activity</h3>
                                    <button type="button" @click="showEditActivityModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 cursor-pointer">&times;</button>
                                </div>

                                <form :action="'{{ $actUpdateRouteTemplate }}'.replace(':id', editActivityData.id)" method="POST" class="space-y-4">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="mode" value="{{ $mode }}">

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Activity Level *</label>
                                            <select name="level" x-model="editActivityData.level" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                                <option value="main">Main Activity</option>
                                                <option value="sub">Sub-Activity</option>
                                            </select>
                                        </div>

                                        <div x-show="editActivityData.level === 'sub'">
                                            <label class="block font-semibold text-slate-700 mb-1">Parent Main Activity *</label>
                                            <select name="parent_id" x-model="editActivityData.parent_id" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                                <option value="">-- Select Parent Main Activity --</option>
                                                @foreach($mainActivitiesList as $mAct)
                                                    @php $mActObj = is_array($mAct) ? (object)$mAct : $mAct; @endphp
                                                    <option value="{{ $mActObj->id ?? '' }}">{{ $mActObj->name ?? '' }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Activity Name *</label>
                                        <input type="text" name="name" x-model="editActivityData.name" required class="w-full border border-slate-300 rounded p-2 outline-none focus:border-blue-500 bg-white text-slate-800">
                                    </div>

                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Description / Instructions</label>
                                        <textarea name="description" x-model="editActivityData.description" rows="2" class="w-full border border-slate-300 rounded p-2.5 outline-none bg-white text-slate-800"></textarea>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Sequence #</label>
                                            <input type="number" name="sequence" x-model="editActivityData.sequence" min="1" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Expected Days (SLA)</label>
                                            <input type="number" name="expected_days" x-model="editActivityData.expected_days" min="0" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Working Hours</label>
                                            <input type="number" step="0.5" name="expected_working_hours" x-model="editActivityData.expected_working_hours" min="0" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Billable Status</label>
                                            <select name="is_billable" x-model="editActivityData.is_billable" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                                <option value="1">Billable</option>
                                                <option value="0">Non-Billable</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Mandatory Level</label>
                                            <select name="is_mandatory" x-model="editActivityData.is_mandatory" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                                <option value="1">Mandatory Activity</option>
                                                <option value="0">Optional / Conditional</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="flex justify-end space-x-2 pt-3 border-t">
                                        <button type="button" @click="showEditActivityModal = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded font-medium cursor-pointer">Cancel</button>
                                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium cursor-pointer">Update Activity</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- TAB 5: COMMERCIALS -->
                <div x-show="activeTab === 'commercials'" x-cloak class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-6">
                    <div class="border-b pb-3">
                        <h2 class="text-base font-bold text-slate-900">Commercials, Pricing and Costing</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Define service pricing models, commercial field rules, payment terms, costing baselines, and reimbursable classifications.</p>
                    </div>

                    <form action="{{ Route::has('services.version.update') && isset($service->id) ? route('services.version.update', ['service' => $service->id]) : '#' }}" method="POST" class="space-y-6 text-xs">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="tab" value="commercials">
                        <input type="hidden" name="next_tab" value="engagement">

                        <div class="space-y-3">
                            <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider flex items-center space-x-1.5">
                                <i class="fa-solid fa-calculator text-blue-600"></i>
                                <span>PRICING MODELS &amp; CORE COMMERCIAL FIELDS</span>
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Pricing Model *</label>
                                    <select name="pricing_model" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                        <option value="Fixed Fee" {{ old('pricing_model', $activeVersion->pricing_model ?? 'Fixed Fee') == 'Fixed Fee' ? 'selected' : '' }}>Fixed Fee</option>
                                        <option value="Monthly Retainer" {{ old('pricing_model', $activeVersion->pricing_model ?? '') == 'Monthly Retainer' ? 'selected' : '' }}>Monthly Retainer</option>
                                        <option value="Per Transaction" {{ old('pricing_model', $activeVersion->pricing_model ?? '') == 'Per Transaction' ? 'selected' : '' }}>Per Transaction</option>
                                        <option value="Per Hour" {{ old('pricing_model', $activeVersion->pricing_model ?? '') == 'Per Hour' ? 'selected' : '' }}>Per Hour</option>
                                        <option value="Per Day" {{ old('pricing_model', $activeVersion->pricing_model ?? '') == 'Per Day' ? 'selected' : '' }}>Per Day</option>
                                        <option value="Per Employee" {{ old('pricing_model', $activeVersion->pricing_model ?? '') == 'Per Employee' ? 'selected' : '' }}>Per Employee</option>
                                        <option value="Per Branch" {{ old('pricing_model', $activeVersion->pricing_model ?? '') == 'Per Branch' ? 'selected' : '' }}>Per Branch</option>
                                        <option value="Per Document" {{ old('pricing_model', $activeVersion->pricing_model ?? '') == 'Per Document' ? 'selected' : '' }}>Per Document</option>
                                        <option value="Per Filing" {{ old('pricing_model', $activeVersion->pricing_model ?? '') == 'Per Filing' ? 'selected' : '' }}>Per Filing</option>
                                        <option value="Per Property" {{ old('pricing_model', $activeVersion->pricing_model ?? '') == 'Per Property' ? 'selected' : '' }}>Per Property</option>
                                        <option value="Per Session" {{ old('pricing_model', $activeVersion->pricing_model ?? '') == 'Per Session' ? 'selected' : '' }}>Per Session</option>
                                        <option value="Milestone" {{ old('pricing_model', $activeVersion->pricing_model ?? '') == 'Milestone' ? 'selected' : '' }}>Milestone</option>
                                        <option value="Percentage" {{ old('pricing_model', $activeVersion->pricing_model ?? '') == 'Percentage' ? 'selected' : '' }}>Percentage</option>
                                        <option value="Custom" {{ old('pricing_model', $activeVersion->pricing_model ?? '') == 'Custom' ? 'selected' : '' }}>Custom</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Currency *</label>
                                    <select name="currency" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                        <option value="PHP" {{ old('currency', $activeVersion->currency ?? 'PHP') == 'PHP' ? 'selected' : '' }}>PHP (₱)</option>
                                        <option value="USD" {{ old('currency', $activeVersion->currency ?? '') == 'USD' ? 'selected' : '' }}>USD ($)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Standard Price *</label>
                                    <input type="number" x-model="standardPrice" name="standard_price" value="{{ old('standard_price', $activeVersion->standard_price ?? 500) }}" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Unit / Rate per Unit</label>
                                    <input type="number" name="unit_rate" value="{{ old('unit_rate', $activeVersion->unit_rate ?? '0.00') }}" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Tax Treatment</label>
                                    <select name="tax_treatment" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                        <option value="VAT Exclusive" {{ old('tax_treatment', $activeVersion->tax_treatment ?? 'VAT Exclusive') == 'VAT Exclusive' ? 'selected' : '' }}>VAT Exclusive</option>
                                        <option value="VAT Inclusive" {{ old('tax_treatment', $activeVersion->tax_treatment ?? '') == 'VAT Inclusive' ? 'selected' : '' }}>VAT Inclusive</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Minimum Price (Floor)</label>
                                    <input type="number" name="min_price" value="{{ old('min_price', $activeVersion->min_price ?? '0.00') }}" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Minimum Cap</label>
                                    <input type="number" name="min_cap" value="{{ old('min_cap', $activeVersion->min_cap ?? '0.00') }}" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3 pt-3 border-t">
                            <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider flex items-center space-x-1.5">
                                <i class="fa-solid fa-chart-line text-amber-500"></i>
                                <span>COMMERCIAL GOVERNANCE &amp; COSTING ECONOMICS</span>
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Discount Allowed</label>
                                    <select name="discount_allowed" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                        <option value="Yes" {{ old('discount_allowed', $activeVersion->discount_allowed ?? 'Yes') == 'Yes' ? 'selected' : '' }}>Yes (Allowed)</option>
                                        <option value="No" {{ old('discount_allowed', $activeVersion->discount_allowed ?? '') == 'No' ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Max Discount W/o Approval (%)</label>
                                    <input type="number" name="max_discount" value="{{ old('max_discount', $activeVersion->max_discount ?? '0.0') }}" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Cost of Service</label>
                                    <input type="number" x-model="costOfService" name="cost_of_service" value="{{ old('cost_of_service', $activeVersion->cost_of_service ?? 0) }}" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Expected Hours</label>
                                    <input type="number" name="expected_hours" value="{{ old('expected_hours', $activeVersion->expected_hours ?? 30) }}" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Target Margin (%)</label>
                                    <input type="text" readonly x-model="calculatedTargetMargin" class="w-full border border-slate-200 bg-slate-50 text-slate-600 rounded p-2 outline-none font-bold">
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3 pt-3 border-t">
                            <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider flex items-center space-x-1.5">
                                <i class="fa-solid fa-file-invoice text-emerald-600"></i>
                                <span>PAYMENT TERMS</span>
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Standard Payment Structure *</label>
                                    <select name="payment_structure" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                        <option value="Full Advance" {{ old('payment_structure', $activeVersion->payment_structure ?? 'Full Advance') == 'Full Advance' ? 'selected' : '' }}>Full Advance</option>
                                        <option value="50/50" {{ old('payment_structure', $activeVersion->payment_structure ?? '') == '50/50' ? 'selected' : '' }}>50% Upon Signing, 50% Upon Completion (50/50)</option>
                                        <option value="Milestone" {{ old('payment_structure', $activeVersion->payment_structure ?? '') == 'Milestone' ? 'selected' : '' }}>Milestone Based</option>
                                        <option value="One-Month Advance" {{ old('payment_structure', $activeVersion->payment_structure ?? '') == 'One-Month Advance' ? 'selected' : '' }}>One-Month Advance</option>
                                        <option value="Recurring Billing" {{ old('payment_structure', $activeVersion->payment_structure ?? '') == 'Recurring Billing' ? 'selected' : '' }}>Recurring Billing</option>
                                        <option value="Security Deposit" {{ old('payment_structure', $activeVersion->payment_structure ?? '') == 'Security Deposit' ? 'selected' : '' }}>Security Deposit</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Payment Schedule &amp; Execution Notes</label>
                                    <input type="text" name="payment_notes" value="{{ old('payment_notes', $activeVersion->payment_notes ?? '') }}" placeholder="e.g. 50% upon signing, 50% upon final delivery of certificate" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3 pt-3 border-t">
                            <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider flex items-center space-x-1.5">
                                <i class="fa-solid fa-receipt text-indigo-600"></i>
                                <span>ADDITIONAL COSTS &amp; REIMBURSABLES</span>
                            </h3>

                            <div class="border border-slate-200 rounded-lg overflow-hidden">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-semibold">
                                            <th class="py-2.5 px-3">COST CATEGORY</th>
                                            <th class="py-2.5 px-3 w-48">CLASSIFICATION</th>
                                            <th class="py-2.5 px-3">NOTES &amp; HANDLING RULE</th>
                                            <th class="py-2.5 px-3 text-right w-16">ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="(item, index) in reimbursables" :key="index">
                                            <tr class="hover:bg-slate-50">
                                                <td class="py-2 px-3"><input type="text" x-model="item.category" class="w-full border border-slate-300 rounded p-1.5 text-xs outline-none bg-white text-slate-800"></td>
                                                <td class="py-2 px-3">
                                                    <select x-model="item.classification" class="w-full border border-slate-300 rounded p-1.5 text-xs outline-none bg-white text-slate-800">
                                                        <option value="Included">Included</option>
                                                        <option value="Excluded">Excluded</option>
                                                        <option value="Reimbursable">Reimbursable</option>
                                                        <option value="Estimate Only">Estimate Only</option>
                                                    </select>
                                                </td>
                                                <td class="py-2 px-3"><input type="text" x-model="item.notes" class="w-full border border-slate-300 rounded p-1.5 text-xs outline-none bg-white text-slate-800"></td>
                                                <td class="py-2 px-3 text-right">
                                                    <button type="button" @click="removeReimbursable(index)" class="text-rose-500 hover:text-rose-700 p-1 cursor-pointer"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <button type="button" @click="addReimbursable()" class="border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-[11px] font-semibold px-3 py-1.5 rounded inline-flex items-center space-x-1 shadow-sm cursor-pointer">
                                <i class="fa-solid fa-plus text-blue-600"></i><span>Add Additional Cost Category</span>
                            </button>
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t">
                            <button type="button" @click="changeTab('workflow')" class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer">&larr; Back to Workflow</button>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded text-xs transition shadow-sm cursor-pointer flex items-center space-x-1.5">
                                <span>Save &amp; Continue to Engagement</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TAB 6: ENGAGEMENT -->
                <div x-show="activeTab === 'engagement'" x-cloak class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-6">
                    <div class="flex justify-between items-start border-b pb-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Engagement Configuration &amp; Recurrence Rules</h2>
                            <p class="text-xs text-slate-400 font-normal mt-0.5">Preset delivery behavior set during Quick Add and automatic operational instantiation rules.</p>
                        </div>
                        <span class="bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold px-2.5 py-1 rounded-md tracking-wider uppercase flex items-center space-x-1">
                            <i class="fa-solid fa-bolt text-amber-500"></i>
                            <span>OPERATIONAL INSTANTIATION ACTIVE</span>
                        </span>
                    </div>

                    <form action="{{ Route::has('services.version.update') && isset($service->id) ? route('services.version.update', ['service' => $service->id]) : '#' }}" method="POST" class="space-y-6 text-xs">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="tab" value="engagement">
                        <input type="hidden" name="next_tab" value="reporting">

                        <div class="border border-slate-200 rounded-lg p-4 bg-slate-50/50 space-y-4">
                            <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider">CONFIGURED ENGAGEMENT SETTINGS</h3>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block font-semibold text-slate-600 mb-1">Engagement Delivery Behavior</label>
                                    <select disabled class="w-full border border-slate-300 bg-slate-100 text-slate-500 rounded p-2 outline-none cursor-not-allowed">
                                        <option value="project" {{ strtolower($service->engagement_behavior ?? 'project') === 'project' ? 'selected' : '' }}>Project (One-time / finite completion)</option>
                                        <option value="regular" {{ strtolower($service->engagement_behavior ?? '') === 'regular' ? 'selected' : '' }}>Regular (Retainer / Recurring)</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-600 mb-1">Payment Structure / Terms</label>
                                    <input type="text" readonly value="{{ old('payment_structure', $activeVersion->payment_structure ?? 'Full Advance / 50-50') }}" class="w-full border border-slate-300 bg-slate-100 text-slate-500 rounded p-2 outline-none cursor-not-allowed">
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-600 mb-1">Instantiation Execution Mode</label>
                                    <select name="instantiation_mode" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                        <option value="automatic" {{ old('instantiation_mode', $activeVersion->instantiation_mode ?? 'automatic') == 'automatic' ? 'selected' : '' }}>Automatic System Instantiation (JIT)</option>
                                        <option value="manual" {{ old('instantiation_mode', $activeVersion->instantiation_mode ?? '') == 'manual' ? 'selected' : '' }}>Manual Approval Required</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="border border-slate-200 rounded-lg p-4 bg-slate-50/50 space-y-4">
                            <div>
                                <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider">Recurrence Cadences &amp; Rules</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
                                <div>
                                    <label class="block font-semibold text-slate-600 mb-1">Recurrence Frequency</label>
                                    <select name="recurrence_frequency" class="w-full border border-slate-300 rounded p-2 text-slate-700 outline-none bg-white focus:border-blue-500">
                                        <option value="Monthly" {{ old('recurrence_frequency', $activeVersion->recurrence_frequency ?? 'Monthly') == 'Monthly' ? 'selected' : '' }}>Monthly (e.g. Bookkeeping, Monthly Reports)</option>
                                        <option value="Bi-Weekly" {{ old('recurrence_frequency', $activeVersion->recurrence_frequency ?? '') == 'Bi-Weekly' ? 'selected' : '' }}>Bi-Weekly / Semi-Monthly</option>
                                        <option value="Quarterly" {{ old('recurrence_frequency', $activeVersion->recurrence_frequency ?? '') == 'Quarterly' ? 'selected' : '' }}>Quarterly</option>
                                        <option value="Semi-Annual" {{ old('recurrence_frequency', $activeVersion->recurrence_frequency ?? '') == 'Semi-Annual' ? 'selected' : '' }}>Semi-Annual (Every 6 Months)</option>
                                        <option value="Annual" {{ old('recurrence_frequency', $activeVersion->recurrence_frequency ?? '') == 'Annual' ? 'selected' : '' }}>Annual / Yearly</option>
                                        <option value="Custom" {{ old('recurrence_frequency', $activeVersion->recurrence_frequency ?? '') == 'Custom' ? 'selected' : '' }}>Custom Cadence</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-600 mb-1">Billing Frequency</label>
                                    <select name="billing_frequency" class="w-full border border-slate-300 rounded p-2 text-slate-700 outline-none bg-white focus:border-blue-500">
                                        <option value="Monthly" {{ old('billing_frequency', $activeVersion->billing_frequency ?? 'Monthly') == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                        <option value="Quarterly" {{ old('billing_frequency', $activeVersion->billing_frequency ?? '') == 'Quarterly' ? 'selected' : '' }}>Quarterly</option>
                                        <option value="Semi-Annual" {{ old('billing_frequency', $activeVersion->billing_frequency ?? '') == 'Semi-Annual' ? 'selected' : '' }}>Semi-Annual (Every 6 Months)</option>
                                        <option value="Annual" {{ old('billing_frequency', $activeVersion->billing_frequency ?? '') == 'Annual' ? 'selected' : '' }}>Annual</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-600 mb-1">Reporting Frequency</label>
                                    <select name="reporting_frequency" class="w-full border border-slate-300 rounded p-2 text-slate-700 outline-none bg-white focus:border-blue-500">
                                        <option value="Monthly" {{ old('reporting_frequency', $activeVersion->reporting_frequency ?? '') == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                        <option value="Quarterly" {{ old('reporting_frequency', $activeVersion->reporting_frequency ?? 'Quarterly') == 'Quarterly' ? 'selected' : '' }}>Quarterly</option>
                                        <option value="Semi-Annual" {{ old('reporting_frequency', $activeVersion->reporting_frequency ?? '') == 'Semi-Annual' ? 'selected' : '' }}>Semi-Annual (Every 6 Months)</option>
                                        <option value="Annual" {{ old('reporting_frequency', $activeVersion->reporting_frequency ?? '') == 'Annual' ? 'selected' : '' }}>Annual</option>
                                        <option value="Progress / Final" {{ old('reporting_frequency', $activeVersion->reporting_frequency ?? '') == 'Progress / Final' ? 'selected' : '' }}>Progress and/or Final (Project-based)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-slate-200/60">
                                <div>
                                    <label class="block font-semibold text-slate-600 mb-1">Advance Instantiation Lead Time (Days)</label>
                                    <input type="number" name="instantiation_lead_days" min="0" value="{{ old('instantiation_lead_days', $activeVersion->instantiation_lead_days ?? 15) }}" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                </div>

                                <div class="flex items-center pt-5">
                                    <label class="flex items-center space-x-2 text-slate-700 cursor-pointer">
                                        <input type="checkbox" name="auto_carryover" value="1" {{ old('auto_carryover', $activeVersion->auto_carryover ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                        <span class="font-semibold text-xs">Auto-carryover incomplete tasks to the next recurring period</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center pt-3 border-t">
                            <button type="button" @click="changeTab('commercials')" class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer">&larr; Back to Commercials</button>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded text-xs transition shadow-sm cursor-pointer flex items-center space-x-1.5">
                                <span>Save &amp; Continue to Reporting</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TAB 7: REPORTING -->
                <div x-show="activeTab === 'reporting'" x-cloak class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-6">
                    <div class="border-b pb-3">
                        <h2 class="text-base font-bold text-slate-900">Reporting Configuration</h2>
                    </div>

                    <form action="{{ Route::has('services.version.update') && isset($service->id) ? route('services.version.update', ['service' => $service->id]) : '#' }}" method="POST" class="space-y-6 text-xs">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="tab" value="reporting">
                        <input type="hidden" name="next_tab" value="automation">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Reporting Frequency *</label>
                                <select name="reporting_frequency" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                    <option value="Monthly" {{ old('reporting_frequency', $activeVersion->reporting_frequency ?? '') == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="Quarterly" {{ old('reporting_frequency', $activeVersion->reporting_frequency ?? 'Quarterly') == 'Quarterly' ? 'selected' : '' }}>Quarterly</option>
                                    <option value="Semiannual" {{ old('reporting_frequency', $activeVersion->reporting_frequency ?? '') == 'Semiannual' ? 'selected' : '' }}>Semiannual</option>
                                    <option value="Annual" {{ old('reporting_frequency', $activeVersion->reporting_frequency ?? '') == 'Annual' ? 'selected' : '' }}>Annual</option>
                                    <option value="Upon Completion" {{ old('reporting_frequency', $activeVersion->reporting_frequency ?? '') == 'Upon Completion' ? 'selected' : '' }}>Upon Completion</option>
                                    <option value="None" {{ old('reporting_frequency', $activeVersion->reporting_frequency ?? '') == 'None' ? 'selected' : '' }}>None</option>
                                    <option value="Custom" {{ old('reporting_frequency', $activeVersion->reporting_frequency ?? '') == 'Custom' ? 'selected' : '' }}>Custom</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Project Reporting Type</label>
                                <select name="project_reporting" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                    <option value="Progress" {{ old('project_reporting', $activeVersion->project_reporting ?? 'Progress') == 'Progress' ? 'selected' : '' }}>Progress</option>
                                    <option value="Final" {{ old('project_reporting', $activeVersion->project_reporting ?? '') == 'Final' ? 'selected' : '' }}>Final</option>
                                </select>
                            </div>
                        </div>

                        <div class="border border-slate-200 rounded-lg p-3.5 bg-slate-50/60 text-slate-600">
                            <p class="text-[11px]">Period-based reporting while preserving lifetime engagement history. Enables continuous tracking across recurring billing and activity cadences.</p>
                        </div>

                        <div class="border border-slate-200 rounded-lg p-4 space-y-3">
                            <label class="block font-bold text-slate-800 text-xs uppercase tracking-wider">REPORT CONTENT SCOPE (SELECT INCLUDED PARAMETERS)</label>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-1">
                                <label class="flex items-center space-x-2 cursor-pointer text-slate-700">
                                    <input type="checkbox" name="report_content[]" value="Activities" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" checked>
                                    <span>Activities</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer text-slate-700">
                                    <input type="checkbox" name="report_content[]" value="Completed tasks" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" checked>
                                    <span>Completed tasks</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer text-slate-700">
                                    <input type="checkbox" name="report_content[]" value="Pending/carry-forward work" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span>Pending/carry-forward work</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer text-slate-700">
                                    <input type="checkbox" name="report_content[]" value="Hours" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span>Hours</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer text-slate-700">
                                    <input type="checkbox" name="report_content[]" value="Deliverables" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" checked>
                                    <span>Deliverables</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer text-slate-700">
                                    <input type="checkbox" name="report_content[]" value="Client requests" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span>Client requests</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer text-slate-700">
                                    <input type="checkbox" name="report_content[]" value="Issues" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span>Issues</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer text-slate-700">
                                    <input type="checkbox" name="report_content[]" value="Expenses" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span>Expenses</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer text-slate-700">
                                    <input type="checkbox" name="report_content[]" value="Recommendations" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span>Recommendations</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer text-slate-700">
                                    <input type="checkbox" name="report_content[]" value="Next-period work" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" checked>
                                    <span>Next-period work</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t">
                            <button type="button" @click="changeTab('engagement')" class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer">&larr; Back to Engagement</button>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded text-xs transition shadow-sm cursor-pointer flex items-center space-x-1.5">
                                <span>Save &amp; Continue to Automation</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TAB 8: AUTOMATION -->
                <div x-show="activeTab === 'automation'" x-cloak class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-6">
                    <div class="border-b pb-3 flex justify-between items-center">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Automation, Instantiation &amp; Governance Engine</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Configure scheduled activity generation, escalation matrices, reminder cadences, and dependency gates based on ORDO Functional Specifications.</p>
                        </div>
                        <span class="bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-semibold px-2.5 py-1 rounded-md inline-flex items-center space-x-1">
                            <i class="fa-solid fa-bolt text-amber-500"></i>
                            <span>Automation Engine Active</span>
                        </span>
                    </div>

                    <form action="{{ Route::has('services.version.update') && isset($service->id) ? route('services.version.update', ['service' => $service->id]) : '#' }}" method="POST" class="space-y-6 text-xs">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="tab" value="automation">
                        <input type="hidden" name="next_tab" value="terms">
                        <input type="hidden" name="mode" value="{{ $mode }}">

                        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/50 space-y-4">
                            <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider flex items-center space-x-2 border-b pb-2">
                                <i class="fa-solid fa-gears text-blue-600"></i>
                                <span>Task &amp; Period Instantiation Engine</span>
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Instantiation Mode *</label>
                                    <select name="instantiation_mode" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                        <option value="automatic" {{ old('instantiation_mode', $activeVersion->instantiation_mode ?? 'automatic') == 'automatic' ? 'selected' : '' }}>Automatic (System Generated)</option>
                                        <option value="manual_approval" {{ old('instantiation_mode', $activeVersion->instantiation_mode ?? '') == 'manual_approval' ? 'selected' : '' }}>Manual (Requires Manager Trigger)</option>
                                        <option value="disabled" {{ old('instantiation_mode', $activeVersion->instantiation_mode ?? '') == 'disabled' ? 'selected' : '' }}>Disabled</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Base Reference Date</label>
                                    <select name="base_reference_date" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                        <option value="period_start" {{ old('base_reference_date', $activeVersion->base_reference_date ?? 'period_start') == 'period_start' ? 'selected' : '' }}>Period Start Date</option>
                                        <option value="engagement_start" {{ old('base_reference_date', $activeVersion->base_reference_date ?? '') == 'engagement_start' ? 'selected' : '' }}>Engagement Start Date</option>
                                        <option value="contract_signing" {{ old('base_reference_date', $activeVersion->base_reference_date ?? '') == 'contract_signing' ? 'selected' : '' }}>Contract Signing Date</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Lead Time Generation (Days)</label>
                                    <input type="number" name="instantiation_lead_days" {{ $isViewOnly ? 'disabled' : '' }} min="0" value="{{ old('instantiation_lead_days', $activeVersion->instantiation_lead_days ?? 15) }}" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                    <span class="text-[10px] text-slate-400 italic mt-0.5 block">Number of days prior to start period.</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-slate-200/60">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Default Auto-Assignment Rule</label>
                                    <select name="default_assignment_rule" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                        <option value="engagement_lead" {{ old('default_assignment_rule', $activeVersion->default_assignment_rule ?? 'engagement_lead') == 'engagement_lead' ? 'selected' : '' }}>Assign to Primary Engagement Lead</option>
                                        <option value="service_area_pool" {{ old('default_assignment_rule', $activeVersion->default_assignment_rule ?? '') == 'service_area_pool' ? 'selected' : '' }}>Unassigned (Service Area Pool)</option>
                                        <option value="previous_assignee" {{ old('default_assignment_rule', $activeVersion->default_assignment_rule ?? '') == 'previous_assignee' ? 'selected' : '' }}>Inherit Previous Period Assignee</option>
                                    </select>
                                </div>

                                <div class="flex items-center pt-4">
                                    <label class="flex items-center space-x-2 text-slate-700 cursor-pointer">
                                        <input type="checkbox" name="auto_carryover" value="1" {{ $isViewOnly ? 'disabled' : '' }} {{ old('auto_carryover', $activeVersion->auto_carryover ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                        <span class="font-semibold text-xs">Auto-carryover incomplete tasks to next recurring period</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/50 space-y-4">
                            <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider flex items-center space-x-2 border-b pb-2">
                                <i class="fa-solid fa-bell text-amber-500"></i>
                                <span>Notification &amp; Reminder Cadence Matrix</span>
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Internal Reminder Trigger (Days Before Due)</label>
                                    <input type="number" name="internal_reminder_days" {{ $isViewOnly ? 'disabled' : '' }} min="1" value="{{ old('internal_reminder_days', $activeVersion->internal_reminder_days ?? 3) }}" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Client Follow-up Cadence</label>
                                    <select name="client_reminder_cadence" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                        <option value="daily" {{ old('client_reminder_cadence', $activeVersion->client_reminder_cadence ?? '') == 'daily' ? 'selected' : '' }}>Daily Automated Email</option>
                                        <option value="every_3_days" {{ old('client_reminder_cadence', $activeVersion->client_reminder_cadence ?? 'every_3_days') == 'every_3_days' ? 'selected' : '' }}>Every 3 Days</option>
                                        <option value="weekly" {{ old('client_reminder_cadence', $activeVersion->client_reminder_cadence ?? '') == 'weekly' ? 'selected' : '' }}>Weekly Summary</option>
                                        <option value="disabled" {{ old('client_reminder_cadence', $activeVersion->client_reminder_cadence ?? '') == 'disabled' ? 'selected' : '' }}>Disabled / Manual Only</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Notification Channel</label>
                                    <select name="notification_channel" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                        <option value="email_and_app" {{ old('notification_channel', $activeVersion->notification_channel ?? 'email_and_app') == 'email_and_app' ? 'selected' : '' }}>Email &amp; In-App Dashboard Notification</option>
                                        <option value="app_only" {{ old('notification_channel', $activeVersion->notification_channel ?? '') == 'app_only' ? 'selected' : '' }}>In-App Dashboard Only</option>
                                        <option value="email_only" {{ old('notification_channel', $activeVersion->notification_channel ?? '') == 'email_only' ? 'selected' : '' }}>Email Only</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/50 space-y-4">
                            <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider flex items-center space-x-2 border-b pb-2">
                                <i class="fa-solid fa-triangle-exclamation text-rose-500"></i>
                                <span>Escalation &amp; Requirement Dependency Controls</span>
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Overdue Escalation Threshold (Days)</label>
                                    <input type="number" name="escalation_threshold_days" {{ $isViewOnly ? 'disabled' : '' }} min="1" value="{{ old('escalation_threshold_days', $activeVersion->escalation_threshold_days ?? 2) }}" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                    <span class="text-[10px] text-slate-400 italic mt-0.5 block">Days overdue prior to system escalation.</span>
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Escalation Recipient Role</label>
                                    <select name="escalation_target_role" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                        <option value="engagement_manager" {{ old('escalation_target_role', $activeVersion->escalation_target_role ?? 'engagement_manager') == 'engagement_manager' ? 'selected' : '' }}>Engagement Manager</option>
                                        <option value="service_area_head" {{ old('escalation_target_role', $activeVersion->escalation_target_role ?? '') == 'service_area_head' ? 'selected' : '' }}>Service Area Head</option>
                                        <option value="quality_reviewer" {{ old('escalation_target_role', $activeVersion->escalation_target_role ?? '') == 'quality_reviewer' ? 'selected' : '' }}>Quality Reviewer</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Missing Requirements Gate Rule</label>
                                    <select name="requirement_gate_rule" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800 focus:border-blue-500">
                                        <option value="block_execution" {{ old('requirement_gate_rule', $activeVersion->requirement_gate_rule ?? 'block_execution') == 'block_execution' ? 'selected' : '' }}>Strict Gate: Block Execution until Mandatory Uploaded</option>
                                        <option value="warn_only" {{ old('requirement_gate_rule', $activeVersion->requirement_gate_rule ?? '') == 'warn_only' ? 'selected' : '' }}>Warning Only: Allow Execution with System Alert</option>
                                        <option value="none" {{ old('requirement_gate_rule', $activeVersion->requirement_gate_rule ?? '') == 'none' ? 'selected' : '' }}>No Restriction</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t">
                            <button type="button" @click="changeTab('reporting')" class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer">&larr; Back to Reporting</button>
                            @if(!$isViewOnly)
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded text-xs transition shadow-sm cursor-pointer inline-flex items-center space-x-1.5">
                                    <span>Save &amp; Continue to Terms</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </button>
                            @else
                                <button type="button" @click="changeTab('terms')" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded text-xs transition shadow-sm cursor-pointer inline-flex items-center space-x-1.5">
                                    <span>Next: Terms</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </button>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- TAB 9: TERMS -->
                <div x-show="activeTab === 'terms'" x-cloak class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-6">
                    <div class="flex justify-between items-start border-b pb-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Shared Content Library &amp; Terms Inheritance</h2>
                            <p class="text-xs text-slate-400 font-normal mt-0.5">Inheritance order: Global &rarr; Service Area &rarr; Category &rarr; Service Specific &rarr; Proposal Override.</p>
                        </div>
                        <span class="bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold px-2.5 py-1 rounded tracking-wider uppercase">Auto-Inherited Clauses</span>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div class="border border-slate-200 rounded-lg p-4 bg-slate-50/30 flex justify-between items-start">
                            <div class="space-y-1">
                                <h4 class="font-bold text-slate-800 text-xs">Confidentiality &amp; Client Data Accuracy</h4>
                                <p class="text-[11px] text-slate-500">Both parties agree that confidential information disclosed during the service engagement shall remain strictly confidential and protected by standard NDA protocols.</p>
                            </div>
                            <div class="flex items-center space-x-2 shrink-0 ml-4">
                                <span class="bg-blue-100 text-blue-700 text-[9px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">GLOBAL SCOPE</span>
                                <span class="text-slate-400 font-mono text-[10px]">v1.0</span>
                            </div>
                        </div>

                        <div class="border border-slate-200 rounded-lg p-4 bg-slate-50/30 flex justify-between items-start">
                            <div class="space-y-1">
                                <h4 class="font-bold text-slate-800 text-xs">Government Processing &amp; Regulatory Disclaimers</h4>
                                <p class="text-[11px] text-slate-500">Government agency processing timelines are estimates only and outside the direct operational control of the firm.</p>
                            </div>
                            <div class="flex items-center space-x-2 shrink-0 ml-4">
                                <span class="bg-amber-100 text-amber-700 text-[9px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">SERVICE AREA SCOPE</span>
                                <span class="text-slate-400 font-mono text-[10px]">v1.0</span>
                            </div>
                        </div>

                        <div class="border border-slate-200 rounded-lg p-4 bg-slate-50/30 flex justify-between items-start">
                            <div class="space-y-1">
                                <h4 class="font-bold text-slate-800 text-xs">Category Specific Terms</h4>
                                <p class="text-[11px] text-slate-500">Standard registration and compliance filing terms inherited based on selected Service Category.</p>
                            </div>
                            <div class="flex items-center space-x-2 shrink-0 ml-4">
                                <span class="bg-purple-100 text-purple-700 text-[9px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">CATEGORY SCOPE</span>
                                <span class="text-slate-400 font-mono text-[10px]">v1.0</span>
                            </div>
                        </div>

                        <div class="border border-slate-200 rounded-lg p-4 bg-slate-50/30 flex justify-between items-start">
                            <div class="space-y-1">
                                <h4 class="font-bold text-slate-800 text-xs">Service-Specific Operating Rules</h4>
                                <p class="text-[11px] text-slate-500">Special regulatory compliance clauses and operational execution terms specific to {{ $service->name ?? 'Digital Transformation' }}.</p>
                            </div>
                            <div class="flex items-center space-x-2 shrink-0 ml-4">
                                <span class="bg-emerald-100 text-emerald-700 text-[9px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">SERVICE SPECIFIC SCOPE</span>
                                <span class="text-slate-400 font-mono text-[10px]">v1.0</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-3 border-t">
                        <button type="button" @click="changeTab('automation')" class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer">&larr; Back to Automation</button>
                        <button type="button" @click="changeTab('usage_performance')" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded text-xs transition shadow-sm cursor-pointer flex items-center space-x-1.5">
                            <span>Continue to Usage &amp; Performance</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </button>
                    </div>
                </div>

                <!-- TAB 10: USAGE & PERFORMANCE -->
                <div x-show="activeTab === 'usage_performance'" x-cloak class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-6">
                    <div class="border-b pb-3 flex justify-between items-center">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Usage &amp; Performance Analytics</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Telemetry, operational health, and historical service performance metrics.</p>
                        </div>
                        <span class="bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-semibold px-2.5 py-1 rounded-md inline-flex items-center space-x-1">
                            <i class="fa-solid fa-chart-line"></i>
                            <span>Live Analytics Active</span>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Active Engagements</span>
                            <div class="text-2xl font-extrabold text-slate-900 mt-1">12</div>
                            <span class="text-[10px] text-emerald-600 font-semibold mt-1 block">↑ 8% from last period</span>
                        </div>
                        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Avg Completion Rate</span>
                            <div class="text-2xl font-extrabold text-slate-900 mt-1">94.2%</div>
                            <span class="text-[10px] text-slate-500 mt-1 block">SLA Compliance Baseline</span>
                        </div>
                        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Avg Turnaround Time</span>
                            <div class="text-2xl font-extrabold text-slate-900 mt-1">4.8 Days</div>
                            <span class="text-[10px] text-blue-600 font-semibold mt-1 block">Target: 5-7 Days</span>
                        </div>
                        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Total Billed Revenue</span>
                            <div class="text-2xl font-extrabold text-slate-900 mt-1">₱30,000</div>
                            <span class="text-[10px] text-slate-500 mt-1 block">Across 12 service instances</span>
                        </div>
                    </div>

                    <div class="border border-slate-200 rounded-lg overflow-hidden space-y-3 p-4 bg-white">
                        <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider">Service Execution Breakdown</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-100 border-b border-slate-200 text-slate-600 text-[10px] font-bold uppercase">
                                        <th class="py-2.5 px-3">Metric Indicator</th>
                                        <th class="py-2.5 px-3">Current Value</th>
                                        <th class="py-2.5 px-3">Benchmark / Target</th>
                                        <th class="py-2.5 px-3">Health Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr>
                                        <td class="py-3 px-3 font-semibold text-slate-800">Workflow Bottleneck Rate</td>
                                        <td class="py-3 px-3">2.1%</td>
                                        <td class="py-3 px-3 text-slate-500">&lt; 5.0%</td>
                                        <td class="py-3 px-3">
                                            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-semibold px-2 py-0.5 rounded">Optimal</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="py-3 px-3 font-semibold text-slate-800">Requirement Submission Delay</td>
                                        <td class="py-3 px-3">1.2 Days</td>
                                        <td class="py-3 px-3 text-slate-500">&lt; 2.0 Days</td>
                                        <td class="py-3 px-3">
                                            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-semibold px-2 py-0.5 rounded">Optimal</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="py-3 px-3 font-semibold text-slate-800">Escalated Overdue Incidents</td>
                                        <td class="py-3 px-3">0 Incidents</td>
                                        <td class="py-3 px-3 text-slate-500">0 Incidents</td>
                                        <td class="py-3 px-3">
                                            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-semibold px-2 py-0.5 rounded">Optimal</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t">
                        <button type="button" @click="changeTab('terms')" class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer">&larr; Back to Terms</button>
                        <button type="button" @click="changeTab('versions_history')" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded text-xs transition shadow-sm cursor-pointer inline-flex items-center space-x-1.5">
                            <span>Continue to Versions &amp; History</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </button>
                    </div>
                </div>

                <!-- TAB 11: VERSIONS & HISTORY -->
                <div x-show="activeTab === 'versions_history'" x-cloak class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-6">
                    <div class="border-b pb-3 flex justify-between items-center">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Versions &amp; Audit History</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Historical snapshots, change tracking log, and version management.</p>
                        </div>
                        <span class="bg-slate-100 text-slate-700 border border-slate-200 text-[10px] font-semibold px-2.5 py-1 rounded-md inline-flex items-center space-x-1">
                            <i class="fa-solid fa-code-branch"></i>
                            <span>Current: Version 1.0 (Draft)</span>
                        </span>
                    </div>

                    <div class="border border-slate-200 rounded-lg overflow-hidden space-y-3 p-4 bg-white">
                        <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider">Version Revision History</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-100 border-b border-slate-200 text-slate-600 text-[10px] font-bold uppercase">
                                        <th class="py-2.5 px-3">Version</th>
                                        <th class="py-2.5 px-3">Status</th>
                                        <th class="py-2.5 px-3">Modified By</th>
                                        <th class="py-2.5 px-3">Date &amp; Time</th>
                                        <th class="py-2.5 px-3">Change Summary</th>
                                        <th class="py-2.5 px-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr class="bg-amber-50/40">
                                        <td class="py-3 px-3 font-bold text-slate-900">v1.0</td>
                                        <td class="py-3 px-3">
                                            <span class="bg-amber-100 text-amber-800 border border-amber-200 text-[10px] font-bold px-2 py-0.5 rounded">DRAFT</span>
                                        </td>
                                        <td class="py-3 px-3 font-medium text-slate-800">{{ $approverName }}</td>
                                        <td class="py-3 px-3 text-slate-500">Sep 10, 2026</td>
                                        <td class="py-3 px-3 text-slate-600">Draft service creation and setup.</td>
                                        <td class="py-3 px-3 text-right">
                                            <span class="text-xs text-slate-400 italic">Editing Mode</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t">
                        <button type="button" @click="changeTab('usage_performance')" class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer">&larr; Back to Usage &amp; Performance</button>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>
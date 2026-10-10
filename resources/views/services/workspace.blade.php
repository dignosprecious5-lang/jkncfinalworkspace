@php
    use App\Models\TermsTemplate;

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

    /*
    |--------------------------------------------------------------------------
    | Active Service Version
    |--------------------------------------------------------------------------
    | Use the version supplied by the controller first.
    | If it is not available, fall back to the service relationship.
    */
    $activeVersion = $activeVersion ?? null;

    if (!$activeVersion && isset($activeVer) && $activeVer) {
        $activeVersion = $activeVer;
    }

    if (!$activeVersion && isset($service)) {
        $activeVersion = $service->activeVersion
            ?? $service->versions()->latest('created_at')->latest('id')->first();
    }

    // Default Reimbursables Data Setup
    $rawReimbursables = old(
        'reimbursables',
        $activeVersion?->reimbursables ?? []
    );

    if (is_string($rawReimbursables)) {
        $rawReimbursables = json_decode($rawReimbursables, true) ?? [];
    }

    if (empty($rawReimbursables) || !is_array($rawReimbursables)) {
        $defaultReimbursables = [
            [
                'category' => 'Government Fees',
                'classification' => 'Reimbursable',
                'notes' => 'BIR, SEC, or LGU official fees'
            ],
            [
                'category' => 'Taxes & Statutory Fees',
                'classification' => 'Excluded',
                'notes' => 'Client statutory liabilities'
            ],
            [
                'category' => 'Notarial Costs',
                'classification' => 'Reimbursable',
                'notes' => 'Actual notary charges'
            ],
            [
                'category' => 'Courier & Transportation',
                'classification' => 'Estimate Only',
                'notes' => 'Out-of-pocket delivery costs'
            ]
        ];
    } else {
        $defaultReimbursables = array_values($rawReimbursables);
    }

    // Main Activities List Setup
    $mainActivitiesList = $activeVersion
        ? ($activeVersion->mainActivities ?? ($activeVersion->activities ?? []))
        : [];

    // -------------------------------------------------------------------------
    // STRICT COMPLETENESS CHECKS
    // -------------------------------------------------------------------------

    // Overview — KEEPING EXISTING LOGIC
    $overviewComplete = !empty($activeVersion) && (
        !empty(trim($activeVersion->internal_description ?? '')) || 
        !empty(trim($activeVersion->client_description ?? '')) ||
        !empty(trim($activeVersion->short_name ?? ''))
    );

    // Proposal Content — KEEPING EXISTING LOGIC
    $proposalComplete = !empty($activeVersion) && (
        !empty(trim($activeVersion->scope_of_work ?? '')) || 
        !empty(trim($activeVersion->deliverables ?? ''))
    );

    /*
    |--------------------------------------------------------------------------
    | Requirements
    |--------------------------------------------------------------------------
    | Complete only when there is at least one configured requirement.
    */
   $reqsComplete = !empty($activeVersion) && $activeVersion->requirements()->count() > 0;

    /*
    |--------------------------------------------------------------------------
    | Workflow
    |--------------------------------------------------------------------------
    | Complete only when there is at least one configured main activity.
    */
    $workflowComplete = !empty($activeVersion)
        && !empty($mainActivitiesList)
        && count($mainActivitiesList) > 0;

    /*
    /*
    |--------------------------------------------------------------------------
    | Commercials
    |--------------------------------------------------------------------------
    | Requires a pricing model AND either a positive standard price or unit rate.
    */
    $pricingModelVal = trim($activeVersion->pricing_model ?? '');
    $unitModels = ['Per Hour', 'Per Day', 'Per Transaction', 'Per Employee', 'Per Branch', 'Per Document', 'Per Filing', 'Per Property', 'Per Session'];
    $isUnitPricing = in_array($pricingModelVal, $unitModels);

    $commercialsComplete = !empty($activeVersion)
        && !empty($pricingModelVal)
        && (
            ($isUnitPricing && !is_null($activeVersion->unit_rate) && $activeVersion->unit_rate !== '' && (float) $activeVersion->unit_rate > 0) ||
            (!$isUnitPricing && !is_null($activeVersion->standard_price) && $activeVersion->standard_price !== '' && (float) $activeVersion->standard_price > 0)
        );
    /*
    |--------------------------------------------------------------------------
    | Engagement
    |--------------------------------------------------------------------------
    | Existing logic retained.
    */
    $engagementComplete = !empty($activeVersion) && (
        !empty(trim($activeVersion->recurrence_frequency ?? ''))
    );

    /*
    |--------------------------------------------------------------------------
    | Reporting
    |--------------------------------------------------------------------------
    | Existing logic retained.
    */
    $reportingComplete = !empty($activeVersion)
        && !empty(trim($activeVersion->reporting_frequency ?? ''))
        && (
            !empty(trim($activeVersion->project_reporting ?? ''))
            || !empty(trim($activeVersion->project_reporting_type ?? ''))
        );

    /*
    |--------------------------------------------------------------------------
    | Automation
    |--------------------------------------------------------------------------
    | Do NOT mark complete simply because instantiation_mode has a value.
    | All configured automation fields must be present.
    */
    $automationComplete = !empty($activeVersion)
        && !empty(trim($activeVersion->instantiation_mode ?? ''))
        && !is_null($activeVersion->instantiation_lead_days)
        && $activeVersion->instantiation_lead_days !== ''
        && !empty(trim($activeVersion->default_assignment_rule ?? ''))
        && !is_null($activeVersion->internal_reminder_days)
        && $activeVersion->internal_reminder_days !== ''
        && !empty(trim($activeVersion->client_reminder_cadence ?? ''))
        && !is_null($activeVersion->escalation_threshold_days)
        && $activeVersion->escalation_threshold_days !== ''
        && !empty(trim($activeVersion->escalation_target_role ?? ''));

    /*
    |--------------------------------------------------------------------------
    | Terms
    |--------------------------------------------------------------------------
    | Existing logic retained.
    */
    $termsComplete = !empty($activeVersion) && (
        (
            method_exists($activeVersion, 'serviceTerms')
            && $activeVersion->serviceTerms()->exists()
        )
        || !empty(trim($activeVersion->terms_and_conditions ?? ''))
    );

    /*
    |--------------------------------------------------------------------------
    | Overall Completion
    |--------------------------------------------------------------------------
    */
    $allSectionsComplete =
        $overviewComplete
        && $proposalComplete
        && $reqsComplete
        && $workflowComplete
        && $commercialsComplete
        && $engagementComplete
        && $reportingComplete
        && $automationComplete;

    // Route Templates for Alpine JS Binding
    $reqUpdateRouteTemplate = Route::has('services.requirements.update')
        ? route('services.requirements.update', ':id')
        : '#';

    $actUpdateRouteTemplate = Route::has('services.activities.update')
        ? route('services.activities.update', ':id')
        : '#';
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
    
    <!-- Brand Header (John Kelly & Company) -->
    <div class="p-5 border-b border-slate-100 shrink-0">
        <a href="/" class="flex flex-col font-serif text-slate-800 hover:opacity-90 leading-tight">
            <span class="text-base font-bold tracking-tight text-slate-900">John Kelly</span>
            <span class="text-xs font-semibold text-slate-900 font-serif -mt-0.5">
                <span class="text-blue-600 font-bold italic font-sans">&</span> Company
            </span>
        </a>
    </div>

    <!-- Enterprise Menu Header -->
    <div class="px-5 pt-4 pb-2 flex justify-between items-center text-xs shrink-0">
        <div>
            <span class="font-bold text-slate-900 block">Enterprise Menu</span>
            <span class="text-[10px] text-slate-400 block -mt-0.5">Navigation</span>
        </div>
        <i class="fa-solid fa-angles-right text-slate-300 text-[10px]"></i>
    </div>

    <!-- Navigation Links -->
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

        <!-- Marketing Dropdown -->
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
            </div>
        </div>

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
                    <span class="text-slate-400 font-mono">{{ $service->service_code ?? 'SVC-0087' }}</span>
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
         showTermModal: false,
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
         standardPrice: {{ old('standard_price', $activeVersion->standard_price ?? 0) }},
         costOfService: {{ old('cost_of_service', $activeVersion->cost_of_service ?? 0) }},
         get calculatedTargetMargin() {
             let price = parseFloat(this.standardPrice) || 0;
             let cost = parseFloat(this.costOfService) || 0;
             if (price <= 0) return '0.0';
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
                 instructions: data.instructions || data.instruction || data.handling_notes || ''
             };
             this.showEditReqModal = true; 
         }
        }">
                <!-- COMPLETENESS GATE BANNER -->
@if($allSectionsComplete)
    <div class="bg-white border border-blue-300 text-slate-800 px-4 py-3 rounded-lg text-xs flex justify-between items-center shadow-sm relative z-30 pointer-events-auto">
        <div class="flex items-center space-x-2">
            <!-- Ginawa nating text-slate-900 para umitim ang check symbol -->
            <i class="fa-solid fa-circle-check text-slate-900 text-sm"></i>
            <span><strong>Completeness Gate Cleared:</strong> All mandatory sections have been completed!</span>
        </div>
        <form action="{{ Route::has('services.submit_approval') && isset($service->id) ? route('services.submit_approval', $service->id) : $dashboardUrl }}" method="POST" class="m-0 p-0">
            @csrf
            @method('PUT') <!-- O kaya POST depende sa iyong route -->
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
    <div class="bg-white border border-slate-200 text-slate-800 px-4 py-3 rounded-lg text-xs flex justify-between items-center shadow-sm">
        <div class="flex items-center space-x-2">
            <!-- Ginawa nating text-slate-900 para umitim ang warning symbol -->
            <i class="fa-solid fa-triangle-exclamation text-slate-900 text-sm"></i>
            <span><strong>Completeness Gate Pending:</strong> Please complete all required sections to enable approval submission.</span>
        </div>
        <span class="text-[11px] font-bold text-slate-700 uppercase bg-slate-100 border border-slate-300 px-2 py-1 rounded">Incomplete</span>
    </div>
@endif

                <!-- Header Card -->
                <div class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm flex justify-between items-start">
                    <div>
                        <div class="flex items-center space-x-3">
                            <span class="font-mono text-xs px-2.5 py-1 bg-blue-50 text-blue-700 font-semibold rounded border border-blue-200">
                                {{ $service->service_code ?? 'SVC-0087' }}
                            </span>
                            <h1 class="text-2xl font-bold text-slate-900">{{ $service->name ?? 'Digital Transformation' }}</h1>

                            @if($isViewOnly)
                                <span class="bg-slate-100 text-slate-700 border border-slate-300 px-2.5 py-0.5 text-[10px] rounded font-bold uppercase tracking-wider">
                                    <i class="fa-solid fa-eye mr-1"></i> READ ONLY MODE
                                </span>
                            @else
                                <span class="bg-white text-slate-700 border border-slate-300 px-2.5 py-1 rounded text-xs font-semibold">
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
    <span class="px-3 py-1 text-xs rounded-full font-bold uppercase bg-white border border-slate-300 text-slate-700">
        {{ str_replace('_', ' ', $service->status ?? 'DRAFT') }}
    </span>

                        <span class="text-xs font-mono font-semibold text-slate-500 bg-slate-100 px-2.5 py-1 rounded">
                            Version {{ $activeVersion->version_number ?? 'V1.0' }}
                        </span>

                        @if(Route::has('services.workspace') && isset($service->id))
                            @if($isViewOnly)
                                <a href="{{ route('services.workspace', ['service' => $service->id, 'mode' => 'edit', 'tab' => $currentTab]) }}" 
                                   class="border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs px-3 py-1.5 rounded-lg transition inline-flex items-center space-x-1 outline-none focus:ring-0 cursor-pointer">
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

                <!-- COMBINED STEPPER WITH CHECKS & NAVIGATION TABS -->
                <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm z-10 w-full mb-6">
                    
                    <!-- 1. DISPLAY-ONLY NUMBERED PROGRESS INDICATORS -->
                    <div class="relative flex items-center justify-between w-full px-6 mb-5 pt-1">
                        
                        <!-- Background Line -->
                        <div class="absolute left-8 right-8 top-1/2 -translate-y-1/2 h-0.5 bg-slate-200 z-0"></div>
                        
                        <!-- Dynamic Progress Line -->
<div class="absolute left-8 top-1/2 -translate-y-1/2 h-0.5 bg-blue-600 transition-all duration-300 z-0"
    :style="{
        width: activeTab === 'overview' ? '0%' :
               activeTab === 'proposal_content' ? '10%' :
               activeTab === 'requirements' ? '20%' :
               activeTab === 'workflow' ? '30%' :
               activeTab === 'commercials' ? '40%' :
               activeTab === 'engagement' ? '50%' :
               activeTab === 'reporting' ? '60%' :
               activeTab === 'automation' ? '70%' :
               activeTab === 'terms' ? '80%' :
               activeTab === 'usage_performance' ? '90%' : '95%'
    }">
</div>
                        <!-- Circle 1: Overview -->
                        <div class="relative z-10 w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition select-none"
                             :class="activeTab === 'overview' ? 'bg-blue-600 text-white ring-4 ring-blue-100 shadow-md scale-105' : '{{ !empty($overviewComplete) ? 'bg-blue-600 text-white ring-2 ring-blue-100' : 'bg-slate-100 text-slate-500 border border-slate-300' }}'">
                            @if(!empty($overviewComplete)) <i class="fa-solid fa-check text-xs"></i> @else 1 @endif
                        </div>

                        <!-- Circle 2: Proposal Content -->
                        <div class="relative z-10 w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition select-none"
                             :class="activeTab === 'proposal_content' ? 'bg-blue-600 text-white ring-4 ring-blue-100 shadow-md scale-105' : '{{ !empty($proposalComplete) ? 'bg-blue-600 text-white ring-2 ring-blue-100' : 'bg-slate-100 text-slate-500 border border-slate-300' }}'">
                            @if(!empty($proposalComplete)) <i class="fa-solid fa-check text-xs"></i> @else 2 @endif
                        </div>

                        <!-- Circle 3: Requirements -->
                        <div class="relative z-10 w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition select-none"
                             :class="activeTab === 'requirements' ? 'bg-blue-600 text-white ring-4 ring-blue-100 shadow-md scale-105' : '{{ !empty($reqsComplete) ? 'bg-blue-600 text-white ring-2 ring-blue-100' : 'bg-slate-100 text-slate-500 border border-slate-300' }}'">
                            @if(!empty($reqsComplete)) <i class="fa-solid fa-check text-xs"></i> @else 3 @endif
                        </div>

                        <!-- Circle 4: Workflow -->
                        <div class="relative z-10 w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition select-none"
                             :class="activeTab === 'workflow' ? 'bg-blue-600 text-white ring-4 ring-blue-100 shadow-md scale-105' : '{{ !empty($workflowComplete) ? 'bg-blue-600 text-white ring-2 ring-blue-100' : 'bg-slate-100 text-slate-500 border border-slate-300' }}'">
                            @if(!empty($workflowComplete)) <i class="fa-solid fa-check text-xs"></i> @else 4 @endif
                        </div>

                        <!-- Circle 5: Commercials -->
                        <div class="relative z-10 w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition select-none"
                             :class="activeTab === 'commercials' ? 'bg-blue-600 text-white ring-4 ring-blue-100 shadow-md scale-105' : '{{ !empty($commercialsComplete) ? 'bg-blue-600 text-white ring-2 ring-blue-100' : 'bg-slate-100 text-slate-500 border border-slate-300' }}'">
                            @if(!empty($commercialsComplete)) <i class="fa-solid fa-check text-xs"></i> @else 5 @endif
                        </div>

                        <!-- Circle 6: Engagement -->
                        <div class="relative z-10 w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition select-none"
                             :class="activeTab === 'engagement' ? 'bg-blue-600 text-white ring-4 ring-blue-100 shadow-md scale-105' : '{{ !empty($engagementComplete) ? 'bg-blue-600 text-white ring-2 ring-blue-100' : 'bg-slate-100 text-slate-500 border border-slate-300' }}'">
                            @if(!empty($engagementComplete)) <i class="fa-solid fa-check text-xs"></i> @else 6 @endif
                        </div>

                        <!-- Circle 7: Reporting -->
                        <div class="relative z-10 w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition select-none"
                             :class="activeTab === 'reporting' ? 'bg-blue-600 text-white ring-4 ring-blue-100 shadow-md scale-105' : '{{ !empty($reportingComplete) ? 'bg-blue-600 text-white ring-2 ring-blue-100' : 'bg-slate-100 text-slate-500 border border-slate-300' }}'">
                            @if(!empty($reportingComplete)) <i class="fa-solid fa-check text-xs"></i> @else 7 @endif
                        </div>

                        <!-- Circle 8: Automation -->
        <div class="relative z-10 w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition select-none"
             :class="activeTab === 'automation' ? 'bg-blue-600 text-white ring-4 ring-blue-100 shadow-md scale-105' : '{{ !empty($automationComplete) ? 'bg-blue-600 text-white ring-2 ring-blue-100' : 'bg-slate-100 text-slate-500 border border-slate-300' }}'">
            @if(!empty($automationComplete)) 
                <i class="fa-solid fa-check text-xs"></i> 
            @else 
                8 
            @endif
        </div>

        <!-- Circle 9: Terms -->
        <div class="relative z-10 w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition select-none"
             :class="activeTab === 'terms' ? 'bg-blue-600 text-white ring-4 ring-blue-100 shadow-md scale-105' : '{{ !empty($termsComplete) ? 'bg-blue-600 text-white ring-2 ring-blue-100' : 'bg-slate-100 text-slate-500 border border-slate-300' }}'">
            @if(!empty($termsComplete)) <i class="fa-solid fa-check text-xs"></i> @else 9 @endif
        </div>

                        <!-- Circle 10: Usage & Performance -->
                        <div class="relative z-10 w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition select-none"
                             :class="activeTab === 'usage_performance' ? 'bg-blue-600 text-white ring-4 ring-blue-100 shadow-md scale-105' : 'bg-slate-100 text-slate-500 border border-slate-300'">
                            10
                        </div>

                        <!-- Circle 11: Versions & History -->
                        <div class="relative z-10 w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition select-none"
                             :class="activeTab === 'versions_history' ? 'bg-blue-600 text-white ring-4 ring-blue-100 shadow-md scale-105' : 'bg-slate-100 text-slate-500 border border-slate-300'">
                            11
                        </div>
                    </div>

                    <!-- 2. NAVIGATION TABS -->
                    <div class="flex items-center justify-between w-full gap-1 text-[11px] font-medium text-slate-600 pt-3 border-t border-slate-100">

                        <button type="button" @click="changeTab('overview')" 
                                :class="activeTab === 'overview' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="flex-1 px-1 py-1 rounded-md border transition flex items-center justify-center space-x-1 cursor-pointer whitespace-nowrap">
                            <i class="fa-solid fa-circle-info text-[10px]"></i>
                            <span>Overview</span>
                            @if(!empty($overviewComplete))<i class="fa-solid fa-circle-check text-blue-600 text-[10px] ml-0.5"></i>@endif
                        </button>

                        <button type="button" @click="changeTab('proposal_content')" 
                                :class="activeTab === 'proposal_content' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="flex-1 px-1 py-1 rounded-md border transition flex items-center justify-center space-x-1 cursor-pointer whitespace-nowrap">
                            <i class="fa-solid fa-file-contract text-[10px]"></i>
                            <span>Proposal Content</span>
                            @if(!empty($proposalComplete))<i class="fa-solid fa-circle-check text-blue-600 text-[10px] ml-0.5"></i>@endif
                        </button>

                        <button type="button" @click="changeTab('requirements')" 
                                :class="activeTab === 'requirements' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="flex-1 px-1 py-1 rounded-md border transition flex items-center justify-center space-x-1 cursor-pointer whitespace-nowrap">
                            <i class="fa-solid fa-folder-open text-[10px]"></i>
                            <span>Requirements</span>
                            @if(!empty($reqsComplete))<i class="fa-solid fa-circle-check text-blue-600 text-[10px] ml-0.5"></i>@endif
                        </button>

                        <button type="button" @click="changeTab('workflow')" 
                                :class="activeTab === 'workflow' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="flex-1 px-1 py-1 rounded-md border transition flex items-center justify-center space-x-1 cursor-pointer whitespace-nowrap">
                            <i class="fa-solid fa-list-check text-[10px]"></i>
                            <span>Workflow</span>
                            @if(!empty($workflowComplete))<i class="fa-solid fa-circle-check text-blue-600 text-[10px] ml-0.5"></i>@endif
                        </button>

                        <button type="button" @click="changeTab('commercials')" 
                                :class="activeTab === 'commercials' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="flex-1 px-1 py-1 rounded-md border transition flex items-center justify-center space-x-1 cursor-pointer whitespace-nowrap">
                            <i class="fa-solid fa-tags text-[10px]"></i>
                            <span>Commercials</span>
                            @if(!empty($commercialsComplete))<i class="fa-solid fa-circle-check text-blue-600 text-[10px] ml-0.5"></i>@endif
                        </button>

                        <button type="button" @click="changeTab('engagement')" 
                                :class="activeTab === 'engagement' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="flex-1 px-1 py-1 rounded-md border transition flex items-center justify-center space-x-1 cursor-pointer whitespace-nowrap">
                            <i class="fa-solid fa-handshake text-[10px]"></i>
                            <span>Engagement</span>
                            @if(!empty($engagementComplete))<i class="fa-solid fa-circle-check text-blue-600 text-[10px] ml-0.5"></i>@endif
                        </button>

                        <button type="button" @click="changeTab('reporting')" 
                                :class="activeTab === 'reporting' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="flex-1 px-1 py-1 rounded-md border transition flex items-center justify-center space-x-1 cursor-pointer whitespace-nowrap">
                            <i class="fa-solid fa-chart-column text-[10px]"></i>
                            <span>Reporting</span>
                            @if(!empty($reportingComplete))<i class="fa-solid fa-circle-check text-blue-600 text-[10px] ml-0.5"></i>@endif
                        </button>

                        <button type="button" @click="changeTab('automation')" 
                                :class="activeTab === 'automation' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="flex-1 px-1 py-1 rounded-md border transition flex items-center justify-center space-x-1 cursor-pointer whitespace-nowrap">
                            <i class="fa-solid fa-robot text-[10px]"></i>
                            <span>Automation</span>
                            @if(!empty($automationComplete))<i class="fa-solid fa-circle-check text-blue-600 text-[10px] ml-0.5"></i>@endif
                        </button>

                        <button type="button" @click="changeTab('terms')" 
                                :class="activeTab === 'terms' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="flex-1 px-1 py-1 rounded-md border transition flex items-center justify-center space-x-1 cursor-pointer whitespace-nowrap">
                            <i class="fa-solid fa-scale-balanced text-[10px]"></i>
                            <span>Terms</span>
                            @if(!empty($termsComplete))<i class="fa-solid fa-circle-check text-blue-600 text-[10px] ml-0.5"></i>@endif
                        </button>

                        <button type="button" @click="changeTab('usage_performance')" 
                                :class="activeTab === 'usage_performance' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="flex-1 px-1 py-1 rounded-md border transition flex items-center justify-center space-x-1 cursor-pointer whitespace-nowrap">
                            <i class="fa-solid fa-chart-line text-[10px]"></i>
                            <span>Usage &amp; Performance</span>
                        </button>

                        <button type="button" @click="changeTab('versions_history')" 
                                :class="activeTab === 'versions_history' ? 'bg-blue-600 text-white shadow-sm font-semibold border-blue-600' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200'" 
                                class="flex-1 px-1 py-1 rounded-md border transition flex items-center justify-center space-x-1 cursor-pointer whitespace-nowrap">
                            <i class="fa-solid fa-clock-rotate-left text-[10px]"></i>
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
                            <!-- Short Name with Icon Inside Input Box (Left Side) -->
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Short Name (Display Alias)</label>
                                <div class="relative flex items-center">
                                    <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                                        <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                            An optional concise display name or alias used for quick identification across system modules.
                                        </div>
                                    </div>
                                    <input type="text" name="short_name" {{ $isViewOnly ? 'disabled' : '' }} value="{{ old('short_name', $activeVersion->short_name ?? $service->short_name ?? '') }}" placeholder="Optional concise display name..." class="w-full border border-slate-300 rounded p-2 pl-8 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}">
                                </div>
                            </div>

                            <!-- Expected Turnaround Time (SLA) with Icon Inside Input Box (Left Side) -->
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Expected Turnaround Time (SLA)</label>
                                <div class="relative flex items-center">
                                    <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                                        <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                            The projected Service Level Agreement (SLA) timeframe required to complete and deliver the service.
                                        </div>
                                    </div>
                                    <input type="text" name="expected_turnaround" {{ $isViewOnly ? 'disabled' : '' }} value="{{ old('expected_turnaround', $activeVersion->expected_turnaround ?? '5-7 days') }}" placeholder="e.g. 5-7 days" class="w-full border border-slate-300 rounded p-2 pl-8 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}">
                                </div>
                            </div>

                            <!-- Effective Date with Icon Inside Input Box (Left Side) -->
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Effective Date</label>
                                <div class="relative flex items-center">
                                    <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                                        <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                            The official starting date when this specific service version becomes active and applicable.
                                        </div>
                                    </div>
                                    <input type="date" name="effective_date" {{ $isViewOnly ? 'disabled' : '' }} value="{{ old('effective_date', isset($activeVersion->effective_date) ? \Carbon\Carbon::parse($activeVersion->effective_date)->format('Y-m-d') : '') }}" class="w-full border border-slate-300 rounded p-2 pl-8 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Internal Description with Icon Inside Textarea Box (Left Side) -->
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Internal Description *</label>
                                <div class="relative">
                                    <div class="absolute top-2.5 left-2.5 group cursor-pointer inline-flex items-center z-10">
                                        <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                            Comprehensive internal explanation, background context, and operational guidelines intended for associates and team members.
                                        </div>
                                    </div>
                                    <textarea name="internal_description" rows="3" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2.5 pl-8 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}" placeholder="Internal explanation and context for associates...">{{ old('internal_description', $activeVersion->internal_description ?? '') }}</textarea>
                                </div>
                            </div>

                            <!-- Client-Facing Description with Icon Inside Textarea Box (Left Side) -->
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Client-Facing Description</label>
                                <div class="relative">
                                    <div class="absolute top-2.5 left-2.5 group cursor-pointer inline-flex items-center z-10">
                                        <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                            A polished, reusable summary of the service designed explicitly to be presented to clients within proposals.
                                        </div>
                                    </div>
                                    <textarea name="client_description" rows="3" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2.5 pl-8 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}" placeholder="Reusable client-facing summary for proposals...">{{ old('client_description', $activeVersion->client_description ?? '') }}</textarea>
                                </div>
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
                                {{ $activeVersion?->about_service ?: 'Standardized corporate service execution workflow for associate guidance, operational compliance, and quality assurance standard operating procedures.' }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Purpose with Icon Inside Textarea Box (Left Side) -->
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Purpose</label>
                                <div class="relative">
                                    <div class="absolute top-2.5 left-2.5 group cursor-pointer inline-flex items-center z-10">
                                        <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                            The primary goal, objective, or reason why this specific service exists within the organization's catalog.
                                        </div>
                                    </div>
                                    <textarea name="purpose" rows="2" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2.5 pl-8 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}" placeholder="Why the service exists...">{{ old('purpose', $activeVersion->purpose ?? '') }}</textarea>
                                </div>
                            </div>

                            <!-- When to Use with Icon Inside Textarea Box (Left Side) -->
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">When to Use</label>
                                <div class="relative">
                                    <div class="absolute top-2.5 left-2.5 group cursor-pointer inline-flex items-center z-10">
                                        <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                            Typical business scenarios, client conditions, or triggers where this service is appropriately applied.
                                        </div>
                                    </div>
                                    <textarea name="when_to_use" rows="2" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2.5 pl-8 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}" placeholder="Typical situations where service applies...">{{ old('when_to_use', $activeVersion->when_to_use ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- What This Service Is Not with Icon Inside Input Box (Left Side) -->
                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">What This Service Is Not</label>
                            <div class="relative flex items-center">
                                <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                                    <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                        Clarifications or boundary notes to prevent confusion regarding closely related services or tasks.
                                    </div>
                                </div>
                                <input type="text" name="what_it_is_not" {{ $isViewOnly ? 'disabled' : '' }} value="{{ old('what_it_is_not', $activeVersion->what_it_is_not ?? '') }}" placeholder="Optional note to prevent confusion..." class="w-full border border-slate-300 rounded p-2 pl-8 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}">
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded text-xs transition shadow-sm cursor-pointer flex items-center space-x-1.5">
                                <span>Save &amp; Continue to Proposal Content</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </button>
                        </div>
                    </form>
                </div>


                 <!-- Tab 5: Commercials -->
<div x-show="activeTab === 'commercials'" x-cloak class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-6">
    <div class="border-b pb-3">
        <h2 class="text-base font-bold text-slate-900">Commercials, Pricing and Costing</h2>
        <p class="text-xs text-slate-500">Define service pricing models, commercial field rules, payment terms, costing baselines, and reimbursable classifications.</p>
    </div>

    @php
        $pricingModelVal = old('pricing_model', $activeVersion->pricing_model ?? '');
        $standardPricingModels = [
            'Fixed Fee', 'Monthly Retainer', 'Per Transaction', 'Per Hour', 
            'Per Day', 'Per Employee', 'Per Branch', 'Per Document', 
            'Per Filing', 'Per Property', 'Per Session', 'Milestone', 'Percentage'
        ];

        $isCustomPricing = !empty($pricingModelVal) && !in_array($pricingModelVal, $standardPricingModels);
        $selectedPricingOption = $isCustomPricing ? 'Custom' : $pricingModelVal;
        $customPricingText = $isCustomPricing ? $pricingModelVal : '';

        $paymentStructureVal = old('payment_structure', $activeVersion->payment_structure ?? '');
        $standardPaymentStructures = [
            'Full Advance', '50% Deposit, 50% Balance', 'Milestone-based', 'Post-delivery / Arrears'
        ];
        $isCustomPayment = !empty($paymentStructureVal) && !in_array($paymentStructureVal, $standardPaymentStructures);
        $selectedPaymentOption = $isCustomPayment ? 'Custom' : $paymentStructureVal;
        $customPaymentText = $isCustomPayment ? $paymentStructureVal : '';

        $discountAllowedVal = old('discount_allowed', $activeVersion->discount_allowed ?? '');
        $currencyVal = old('currency', $activeVersion->currency ?? 'PHP (₱)');
        $taxTreatmentVal = old('tax_treatment', $activeVersion->tax_treatment ?? $service->tax_treatment ?? 'VAT Inclusive');
        
        $minPriceVal = old('min_price', $activeVersion->min_price ?? '');
        $minCapVal = old('min_cap', $activeVersion->min_cap ?? '');
    @endphp

    <form action="{{ Route::has('services.version.update') && isset($service->id) ? route('services.version.update', ['service' => $service->id]) : '#' }}" 
          method="POST" 
          x-data="{ 
              pricingOption: '{{ $selectedPricingOption }}',
              customPricing: '{{ $customPricingText }}',
              paymentOption: '{{ $selectedPaymentOption }}',
              customPayment: '{{ $customPaymentText }}',
              get isUnitBased() {
                  const unitModels = ['Per Hour', 'Per Day', 'Per Transaction', 'Per Employee', 'Per Branch', 'Per Document', 'Per Filing', 'Per Property', 'Per Session'];
                  return unitModels.includes(this.pricingOption);
              }
          }" 
          class="space-y-6 text-xs">
        @csrf
        @method('PUT')
        <input type="hidden" name="tab" value="commercials">
        <input type="hidden" name="next_tab" value="engagement">

        <div class="border border-slate-200 rounded-lg p-4 space-y-4">
            <h3 class="font-bold text-slate-800 uppercase text-[11px] tracking-wider flex items-center space-x-1.5">
                <i class="fa-solid fa-calculator text-blue-600"></i>
                <span>PRICING MODELS &amp; CORE COMMERCIAL FIELDS</span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-start">
                <!-- Pricing Model with Icon Inside Box (Left Side) -->
                <div class="relative transform-gpu">
                    <label class="block font-semibold text-slate-700 mb-1">Pricing Model *</label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                The structural pricing method used to calculate fees for this service (e.g., Fixed Fee, Hourly, Monthly Retainer).
                            </div>
                        </div>
                        <select x-model="pricingOption" required class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                            <option value="">-- Select Pricing Model --</option>
                            <option value="Fixed Fee">Fixed Fee</option>
                            <option value="Monthly Retainer">Monthly Retainer</option>
                            <option value="Per Transaction">Per Transaction</option>
                            <option value="Per Hour">Per Hour</option>
                            <option value="Per Day">Per Day</option>
                            <option value="Per Employee">Per Employee</option>
                            <option value="Per Branch">Per Branch</option>
                            <option value="Per Document">Per Document</option>
                            <option value="Per Filing">Per Filing</option>
                            <option value="Per Property">Per Property</option>
                            <option value="Per Session">Per Session</option>
                            <option value="Milestone">Milestone</option>
                            <option value="Percentage">Percentage</option>
                            <option value="Custom">Custom</option>
                        </select>
                    </div>

                    <input type="hidden" name="pricing_model" :value="pricingOption === 'Custom' ? customPricing : pricingOption">

                    <div x-show="pricingOption === 'Custom'" x-cloak class="mt-2">
                        <input type="text" 
                               x-model="customPricing"
                               placeholder="Specify custom pricing model..." 
                               :required="pricingOption === 'Custom'"
                               class="w-full border border-slate-300 rounded p-2 text-xs outline-none bg-white text-slate-800 focus:border-blue-500" 
                        />
                    </div>
                </div>

                <!-- Currency with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Currency *</label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                The monetary currency denomination used for all pricing computations in this service.
                            </div>
                        </div>
                        <select name="currency" class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                            <option value="PHP (₱)" {{ $currencyVal == 'PHP (₱)' ? 'selected' : '' }}>PHP (₱)</option>
                            <option value="USD ($)" {{ $currencyVal == 'USD ($)' ? 'selected' : '' }}>USD ($)</option>
                        </select>
                    </div>
                </div>

                <!-- Standard Price with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Standard Price *</label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                The primary fixed price or flat rate of this service charged to the client.
                            </div>
                        </div>
                        <input type="number" 
               step="0.01" 
               name="standard_price" 
               value="{{ old('standard_price', $activeVersion->standard_price ?? '0') }}" 
               :disabled="isUnitBased"
               :class="isUnitBased ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-white text-slate-800'"
               class="w-full border border-slate-300 rounded p-2 pl-8 outline-none focus:border-blue-500" />
    </div>
    <!-- Idagdag ang :disabled dito para hindi sumabay sa pag-submit kapag hindi unit-based -->
    <input type="hidden" name="standard_price" value="0" x-show="isUnitBased" :disabled="!isUnitBased">
</div>

                <!-- Unit / Rate per Unit with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Unit / Rate per Unit *</label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                The computed rate per hour, day, document, or specific unit when using unit-based pricing models.
                            </div>
                        </div>
                        <input type="number" 
                               step="0.01" 
                               name="unit_rate" 
                               value="{{ old('unit_rate', $activeVersion->unit_rate ?? '') }}" 
                               :disabled="!isUnitBased"
                               :required="isUnitBased"
                               :class="!isUnitBased ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-white text-slate-800'"
                               class="w-full border border-slate-300 rounded p-2 pl-8 outline-none focus:border-blue-500" />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                <!-- Tax Treatment with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Tax Treatment</label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                Indicates whether government taxes (such as VAT) are already included or excluded from the pricing.
                            </div>
                        </div>
                        <input type="text" 
                               value="{{ $taxTreatmentVal }}" 
                               readonly 
                               class="w-full border border-slate-200 bg-slate-100 rounded p-2 pl-8 text-slate-600 font-medium cursor-not-allowed outline-none" 
                        />
                    </div>
                    <input type="hidden" name="tax_treatment" value="{{ $taxTreatmentVal }}">
                </div>

                <!-- Minimum Price (Floor) with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Minimum Price (Floor)</label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                The absolute lowest threshold price allowed for this service under any circumstance.
                            </div>
                        </div>
                        <input type="number" step="0.01" name="min_price" value="{{ old('min_price', isset($activeVersion) ? $activeVersion->min_price : '') }}" class="w-full border border-slate-300 rounded p-2 pl-8 outline-none text-slate-800 bg-white focus:border-blue-500" />
                    </div>
                </div>

                <!-- Minimum Cap with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Minimum Cap</label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                The minimum limitation ceiling boundary configured for pricing variations.
                            </div>
                        </div>
                        <input type="number" step="0.01" name="min_cap" value="{{ old('min_cap', isset($activeVersion) ? $activeVersion->min_cap : '') }}" class="w-full border border-slate-300 rounded p-2 pl-8 outline-none text-slate-800 bg-white focus:border-blue-500" />
                    </div>
                </div>
            </div>
        </div>

        <div class="border border-slate-200 rounded-lg p-4 space-y-4">
            <h3 class="font-bold text-slate-800 uppercase text-[11px] tracking-wider">SERVICE ECONOMICS</h3>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Discount Allowed with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Discount Allowed *</label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                Specifies whether price discounts are permitted or strictly prohibited for this service.
                            </div>
                        </div>
                        <select name="discount_allowed" required class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                            <option value="">-- Select Discount Policy --</option>
                            <option value="Yes (Allowed)" {{ $discountAllowedVal == 'Yes (Allowed)' ? 'selected' : '' }}>Yes (Allowed)</option>
                            <option value="No (Strict)" {{ $discountAllowedVal == 'No (Strict)' ? 'selected' : '' }}>No (Strict)</option>
                        </select>
                    </div>
                </div>

                <!-- Max Discount W/o Approval with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Max Discount W/o Approval (%)</label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                The maximum discount percentage that agents or staff can grant without requiring executive or management approval.
                            </div>
                        </div>
                        <input type="number" step="0.1" name="max_discount" value="{{ old('max_discount', $activeVersion->max_discount ?? '') }}" class="w-full border border-slate-300 rounded p-2 pl-8 outline-none text-slate-800 focus:border-blue-500" />
                    </div>
                </div>

                <!-- Cost of Service with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Cost of Service</label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                The estimated internal baseline expense or cost incurred by the company to successfully deliver this service.
                            </div>
                        </div>
                        <input type="number" step="0.01" name="cost_of_service" value="{{ old('cost_of_service', $activeVersion->cost_of_service ?? '') }}" class="w-full border border-slate-300 rounded p-2 pl-8 outline-none text-slate-800 focus:border-blue-500" />
                    </div>
                </div>

                <!-- Expected Hours with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Expected Hours</label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                The projected total working hours required by the operating team to complete the engagement.
                            </div>
                        </div>
                        <input type="number" step="0.5" name="expected_hours" value="{{ old('expected_hours', $activeVersion->expected_hours ?? '') }}" class="w-full border border-slate-300 rounded p-2 pl-8 outline-none text-slate-800 focus:border-blue-500" />
                    </div>
                </div>
            </div>
        </div>

        <div class="border border-slate-200 rounded-lg p-4 space-y-4">
            <h3 class="font-bold text-slate-800 uppercase text-[11px] tracking-wider">PAYMENT TERMS</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
               <!-- Standard Payment Structure with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Standard Payment Structure *</label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                             <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                The default payment collection framework or milestone scheme required for the service.
                            </div>
                        </div>
                        <select x-model="paymentOption" required class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                            <option value="">-- Select Payment Structure --</option>
                            <option value="Full Advance">Full Advance</option>
                            <option value="50% Deposit, 50% Balance">50% Deposit, 50% Balance</option>
                            <option value="Milestone-based">Milestone-based</option>
                            <option value="Post-delivery / Arrears">Post-delivery / Arrears</option>
                            <option value="Custom">Custom</option>
                        </select>
                    </div>

                    <input type="hidden" name="payment_structure" :value="paymentOption === 'Custom' ? customPayment : paymentOption">

                    <div x-show="paymentOption === 'Custom'" x-cloak class="mt-2">
                        <input type="text" 
                               x-model="customPayment"
                               placeholder="Specify custom payment terms..." 
                               :required="paymentOption === 'Custom'"
                               class="w-full border border-slate-300 rounded p-2 text-xs outline-none bg-white text-slate-800 focus:border-blue-500" 
                        />
                    </div>
                </div>

                <!-- Payment Schedule & Execution Notes with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Payment Schedule &amp; Execution Notes</label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                Specific billing milestones, installment timelines, or payment execution notes for the client.
                            </div>
                        </div>
                        <input type="text" name="payment_notes" value="{{ old('payment_notes', $activeVersion->payment_notes ?? '') }}" placeholder="e.g. 50% upon signing, 50% upon final delivery of certificate" class="w-full border border-slate-300 rounded p-2 pl-8 outline-none text-slate-800 focus:border-blue-500" />
                    </div>
                </div>
            </div>
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

<!-- TAB 9: TERMS & AGREEMENTS -->
<div
    x-show="activeTab === 'terms'"
    x-cloak
    class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-6"
    x-data="{
        showSaveTemplateModal: false,
        showSelectTemplateModal: false,
        showEditTermModal: false,
        showTermModal: false,
        showTemplateLibraryModal: false,
        showViewTermModal: false,

        saveTemplateTermId: '',
        saveTemplateName: '',

        viewForm: {
            title: '',
            source: '',
            content: ''
        },

        editForm: {
            id: '',
            title: '',
            scope: 'service_specific',
            content: ''
        },

        openView(title, source, content) {
            this.viewForm = {
                title: title,
                source: source,
                content: content
            };

            this.showViewTermModal = true;
        },

        openEdit(id, title, scope, content) {
            this.editForm = {
                id: id,
                title: title,
                scope: scope,
                content: content
            };

            this.showEditTermModal = true;
        },

        openSaveTemplate(id, title) {
            this.saveTemplateTermId = id;
            this.saveTemplateName = title;
            this.showSaveTemplateModal = true;
        }
    }"
>

    <!-- ===================================================== -->
    <!-- HEADER -->
    <!-- ===================================================== -->
    <div class="flex justify-between items-center border-b pb-3">

        <div>
            <h2 class="text-base font-bold text-slate-900">
                Service Terms &amp; Agreements
            </h2>

            <p class="text-xs text-slate-500 mt-0.5">
                Manage custom terms, legal clauses, and conditions applicable to this service version.
            </p>
        </div>

        @if(!$isViewOnly)

            <div class="flex items-center space-x-2">

                <!-- TEMPLATE LIBRARY -->
                <button
                    type="button"
                    @click="showTemplateLibraryModal = true"
                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs px-3 py-1.5 rounded font-semibold transition cursor-pointer"
                >
                    Template Library
                </button>

                
                <!-- SELECT / APPLY EXISTING TEMPLATE -->
                <button
                    type="button"
                    @click="showSelectTemplateModal = true"
                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs px-3 py-1.5 rounded font-semibold transition cursor-pointer"
                >
                    Select Template
                </button>

                <!-- ADD TERM -->
                <button
                    type="button"
                    @click="showTermModal = true"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1.5 rounded font-semibold transition flex items-center space-x-1 cursor-pointer"
                >
                    <i class="fa-solid fa-plus"></i>
                    <span>Add Term</span>
                </button>

            </div>

        @endif

    </div>


    <!-- ===================================================== -->
    <!-- TERMS LIST -->
    <!-- ===================================================== -->

    <div class="space-y-3 text-xs">

        @forelse(($activeVersion?->resolved_terms ?? []) as $term)

            @php

                $termObj = is_array($term)
                    ? (object) $term
                    : $term;

                $termId = $termObj->id ?? null;
$scope = $termObj->scope ?? null;

$source = match($scope) {
    'global'           => 'GLOBAL',
    'service_area'     => 'SERVICE AREA',
    'category'         => 'CATEGORY',
    'service_specific' => 'SERVICE SPECIFIC',
    default            => $termObj->resolved_source ?? 'SERVICE SPECIFIC',
};

                $title = $termObj->title
                    ?? $termObj->name
                    ?? 'Term Clause';

                $content = $termObj->content
                    ?? $termObj->description
                    ?? '';

                /*
                 * CHECK IF THIS TERM IS ALREADY SAVED
                 * AS A TEMPLATE.
                 *
                 * This checks the active template library.
                 */
                $alreadySavedAsTemplate = false;

                if ($termId && class_exists(\App\Models\TermsTemplate::class)) {

                    $alreadySavedAsTemplate = \App\Models\TermsTemplate::query()
                        ->where('status', 'active')
                        ->whereHas('items', function ($q) use ($termId) {
                            $q->where('service_term_id', $termId)
                              ->orWhere('term_id', $termId);
                        })
                        ->exists();
                }

                $badgeClass = match(strtoupper($source)) {

                    'GLOBAL' =>
                        'bg-purple-100 text-purple-700',

                    'SERVICE AREA' =>
                        'bg-indigo-100 text-indigo-700',

                    'CATEGORY' =>
                        'bg-blue-100 text-blue-700',

                    default =>
                        'bg-slate-100 text-slate-700',
                };

            @endphp


<!-- ================================================= -->
            <!-- TERM CARD -->
            <!-- ================================================= -->

            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50/50 space-y-3 shadow-xs">

                <!-- TOP ROW: Title, Badge, at Actions -->
                <div class="flex justify-between items-center">
                    
                    <!-- LEFT: Icon at Title -->
                    <div class="flex items-center space-x-2 min-w-0">
                        <i class="fa-solid fa-file-lines text-slate-400 text-xs"></i>
                        <h4 class="font-bold text-slate-800 text-xs leading-snug">
                            {{ $title }}
                        </h4>
                    </div>

                    <!-- RIGHT: Badge, Version, at Actions -->
                    <div class="flex items-center space-x-3 shrink-0">
                        <span class="{{ $badgeClass }} text-[9px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">
                            {{ $source }}
                        </span>
                        <span class="text-slate-400 font-mono text-[10px]">
                            v1.0
                        </span>

                        @if(!$isViewOnly && $termId)
                            <div class="flex items-center space-x-3 text-xs font-medium pl-3 border-l border-slate-200" @click.stop>
                                <!-- EDIT -->
                                <button
                                    type="button"
                                    @click="openEdit(
                                        @js($termId),
                                        @js($title),
                                        @js($termObj->scope ?? 'service_specific'),
                                        @js($content)
                                    )"
                                    class="text-blue-600 hover:underline cursor-pointer"
                                >
                                    Edit
                                </button>

                                <!-- DUPLICATE -->
                                @if($termId)
                                    <form
                                        action="{{ route('service-terms.duplicate', [$activeVersion, $termId]) }}"
                                        method="POST"
                                        class="inline"
                                    >
                                        @csrf
                                        <button
                                            type="submit"
                                            class="text-slate-600 hover:underline cursor-pointer"
                                        >
                                            Duplicate
                                        </button>
                                    </form>

                                    <!-- SAVE AS TEMPLATE -->
                                    @if($alreadySavedAsTemplate)
                                        <button
                                            type="button"
                                            disabled
                                            class="text-slate-400 cursor-not-allowed"
                                        >
                                            Saved as Template
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            @click="openSaveTemplate(
                                                @js($termId),
                                                @js($title)
                                            )"
                                            class="text-indigo-600 hover:text-indigo-800 hover:underline cursor-pointer"
                                        >
                                            Save as Template
                                        </button>
                                    @endif

                                    <!-- DISABLE -->
                                    <form
                                        action="{{ route('service-terms.disable', $termId) }}"
                                        method="POST"
                                        class="inline"
                                        onsubmit="return confirm('Are you sure you want to delete this term?');"
                                    >
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            class="text-red-600 hover:underline cursor-pointer"
                                        >
                                            Disable
                                        </button>
                                    </form>
                                @else
                                    <span class="text-slate-400 italic">
                                        ID Missing
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>

                </div>

               <!-- BOTTOM ROW: Malaking Text at Naka-justify nang Tuwid -->
                <div class="mt-2 w-full">
                    <p class="text-slate-700 text-[15px] leading-relaxed text-justify m-0 p-0" style="text-justify: inter-word;">{{ trim($content) }}</p>
                </div>

            </div>

        @empty

            <div class="border border-dashed border-slate-200 rounded-lg p-8 text-center text-slate-400 italic">
                No terms and agreements have been resolved or added yet.
            </div>

        @endforelse

    </div>

    <!-- ===================================================== -->
    <!-- NAVIGATION -->
    <!-- ===================================================== -->

    <div class="flex justify-between items-center pt-3 border-t">

        <button
            type="button"
            @click="changeTab('automation')"
            class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer"
        >
            &larr; Back to Automation
        </button>

        <button
            type="button"
            @click="changeTab('usage_performance')"
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded text-xs transition shadow-sm cursor-pointer flex items-center space-x-1.5"
        >
            <span>Continue to Usage &amp; Performance</span>
            <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </button>

    </div>


    <!-- ===================================================== -->
    <!-- SAVE SINGLE TERM AS TEMPLATE MODAL -->
    <!-- ===================================================== -->

    <template x-teleport="body">

        <div
            x-show="showSaveTemplateModal"
            x-cloak
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center p-4 z-[9999] overflow-y-auto"
        >

            <div
                @click.away="showSaveTemplateModal = false"
                class="bg-white rounded-lg shadow-2xl max-w-lg w-full p-6 text-xs space-y-4"
            >

                <div class="flex justify-between items-center border-b pb-3">

                    <h3 class="text-sm font-bold text-slate-900">
                        Save Term as Template
                    </h3>

                    <button
                        type="button"
                        @click="showSaveTemplateModal = false"
                        class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 cursor-pointer"
                    >
                        &times;
                    </button>

                </div>


                <form
                    action="{{ $activeVersion ? route('service-terms.template.store', $activeVersion->id) : '#' }}"
                    method="POST"
                    class="space-y-4"
                >

                    @csrf


                    <!-- TERM ID -->
                    <input
                        type="hidden"
                        name="term_ids[]"
                        :value="saveTemplateTermId"
                    >


                    <!-- TEMPLATE NAME -->
                    <div>

                        <label class="block font-semibold text-slate-700 mb-1">
                            Template Name *
                        </label>

                        <input
                            type="text"
                            name="template_name"
                            x-model="saveTemplateName"
                            required
                            class="w-full border border-slate-300 rounded p-2 bg-white text-slate-800"
                        >

                    </div>


                    <!-- INFORMATION -->
                    <div class="bg-slate-50 border border-slate-200 rounded p-3 text-slate-600">

                        <p>
                            This term will be saved as a reusable template.
                        </p>

                        <p class="mt-1">
                            <strong>Term:</strong>
                            <span x-text="saveTemplateName"></span>
                        </p>

                    </div>


                    <!-- BUTTONS -->
                    <div class="flex justify-end space-x-2 pt-2 border-t">

                        <button
                            type="button"
                            @click="showSaveTemplateModal = false"
                            class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded cursor-pointer"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded cursor-pointer"
                        >
                            Save as Template
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </template>


    <!-- ===================================================== -->
    <!-- ADD TERM MODAL -->
    <!-- ===================================================== -->

    <template x-teleport="body">

        <div
            x-show="showTermModal"
            x-cloak
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center p-4 z-[9999] overflow-y-auto"
        >

            <div
                @click.away="showTermModal = false"
                class="bg-white rounded-lg shadow-2xl max-w-lg w-full p-6 text-xs space-y-4 max-h-[90vh] overflow-y-auto"
            >

                <div class="flex justify-between items-center border-b pb-3">

                    <h3 class="text-sm font-bold text-slate-900">
                        Add Service Term
                    </h3>

                    <button
                        type="button"
                        @click="showTermModal = false"
                        class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 cursor-pointer"
                    >
                        &times;
                    </button>

                </div>


                <form
                    action="{{ Route::has('service-terms.store') && isset($activeVersion) ? route('service-terms.store', $activeVersion) : '#' }}"
                    method="POST"
                    class="space-y-4"
                >

                    @csrf

                    <input
                        type="hidden"
                        name="mode"
                        value="{{ $mode }}"
                    >


                    <div>

                        <label class="block font-semibold text-slate-700 mb-1">
                            Term Title / Heading *
                        </label>

                        <input
                            type="text"
                            name="title"
                            required
                            placeholder="e.g. Intellectual Property Rights"
                            class="w-full border border-slate-300 rounded p-2 outline-none focus:border-blue-500 bg-white text-slate-800"
                        >

                    </div>


                    <div>

                        <label class="block font-semibold text-slate-700 mb-1">
                            Scope
                        </label>

                        <select
                            name="scope"
                            required
                            class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800"
                        >

                            <option value="">
                                Select Scope
                            </option>

                            <option value="service_specific">
                                Service Specific
                            </option>

                            <option value="global">
                                Global Scope
                            </option>

                            <option value="service_area">
                                Service Area Scope
                            </option>

                            <option value="category">
                                Category
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="block font-semibold text-slate-700 mb-1">
                            Term Content / Clause Details *
                        </label>

                        <textarea
                            name="content"
                            rows="4"
                            required
                            placeholder="Specify the full legal or operating clause..."
                            class="w-full border border-slate-300 rounded p-2.5 outline-none bg-white text-slate-800"
                        ></textarea>

                    </div>


                    <div class="flex justify-end space-x-2 pt-3 border-t">

                        <button
                            type="button"
                            @click="showTermModal = false"
                            class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded font-medium cursor-pointer"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium cursor-pointer"
                        >
                            Save Term
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </template>


    <!-- ===================================================== -->
    <!-- SAVE MULTIPLE TERMS AS TEMPLATE -->
    <!-- ===================================================== -->

    <template x-teleport="body">

        <div
            x-show="showSaveTemplateModal && !saveTemplateTermId"
            x-cloak
        >
            <!-- intentionally unused because the single-term modal above
                 handles individual terms -->
        </div>

    </template>


    <!-- ===================================================== -->
    <!-- SELECT TEMPLATE MODAL -->
    <!-- ===================================================== -->

    <template x-teleport="body">

        <div
            x-show="showSelectTemplateModal"
            x-cloak
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center p-4 z-[9999] overflow-y-auto"
        >

            <div
                @click.away="showSelectTemplateModal = false"
                class="bg-white rounded-lg shadow-2xl max-w-lg w-full p-6 text-xs space-y-4"
            >

                <h3 class="text-sm font-bold text-slate-900 border-b pb-2">
                    Apply Terms Template
                </h3>


                <form
                    action="{{ isset($activeVersion) ? route('service-terms.template.apply', $activeVersion) : '#' }}"
                    method="POST"
                    class="space-y-4"
                    @submit="if(document.getElementById('applyModeSelect').value === 'replace') { return confirm('Replacing existing terms will disable current active terms. Continue?'); }"
                >

                    @csrf


                    <div>

                        <label class="block font-semibold text-slate-700 mb-1">
                            Choose Template *
                        </label>

                        <select
                            name="template_id"
                            required
                            class="w-full border border-slate-300 rounded p-2 bg-white text-slate-800"
                        >

                            <option value="">
                                Select Template
                            </option>

                            @php
                                $templates = class_exists(\App\Models\TermsTemplate::class)
                                    ? \App\Models\TermsTemplate::where('status', 'active')->get()
                                    : [];
                            @endphp

                            @foreach($templates as $tpl)

                                <option value="{{ $tpl->id }}">
                                    {{ $tpl->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <div>

                        <label class="block font-semibold text-slate-700 mb-1">
                            Application Mode *
                        </label>

                        <select
                            name="apply_mode"
                            id="applyModeSelect"
                            required
                            class="w-full border border-slate-300 rounded p-2 bg-white text-slate-800"
                        >

                            <option value="add">
                                A. Add to Existing (Keep current terms)
                            </option>

                            <option value="replace">
                                B. Replace Existing (Disable current terms & replace)
                            </option>

                        </select>

                    </div>


                    <div class="flex justify-end space-x-2 pt-2 border-t">

                        <button
                            type="button"
                            @click="showSelectTemplateModal = false"
                            class="bg-slate-100 px-4 py-2 rounded cursor-pointer"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded cursor-pointer"
                        >
                            Apply Template
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </template>


    <!-- ===================================================== -->
    <!-- TEMPLATE LIBRARY -->
    <!-- ===================================================== -->

    <template x-teleport="body">

        <div
            x-show="showTemplateLibraryModal"
            x-cloak
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center p-4 z-[9999] overflow-y-auto"
        >

            <div
                @click.away="showTemplateLibraryModal = false"
                class="bg-white rounded-lg shadow-2xl max-w-2xl w-full p-6 text-xs space-y-4"
            >

                <div class="flex justify-between items-center border-b pb-3">

                    <h3 class="text-sm font-bold text-slate-900">
                        Terms Template Library
                    </h3>

                    <button
                        type="button"
                        @click="showTemplateLibraryModal = false"
                        class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 cursor-pointer"
                    >
                        &times;
                    </button>

                </div>


                <div class="space-y-3 max-h-96 overflow-y-auto">

                    @php

                        $libraryTemplates = class_exists(\App\Models\TermsTemplate::class)
                            ? \App\Models\TermsTemplate::with('items')
                                ->where('status', 'active')
                                ->get()
                            : [];

                    @endphp


                    @forelse($libraryTemplates as $tpl)

                        <div class="border border-slate-200 rounded-lg p-3 flex justify-between items-center bg-slate-50/50">

                            <div class="space-y-1">

                                <h4 class="font-bold text-slate-800 text-xs">
                                    {{ $tpl->name }}
                                </h4>

                                <div class="flex items-center space-x-3 text-[11px] text-slate-500">

                                    <span>
                                        Terms Count:
                                        <strong class="text-slate-700">
                                            {{ $tpl->items->count() }}
                                        </strong>
                                    </span>

                                    <span>
                                        Status:
                                        <strong class="uppercase text-blue-600">
                                            {{ $tpl->status }}
                                        </strong>
                                    </span>

                                    <span>
                                        Created:
                                        {{ $tpl->created_at->format('M d, Y') }}
                                    </span>

                                </div>

                            </div>


                            <div class="flex items-center space-x-2">

                                <button
                                    type="button"
                                    @click="showTemplateLibraryModal = false; showSelectTemplateModal = true;"
                                    class="bg-blue-50 hover:bg-blue-100 text-blue-600 font-semibold px-3 py-1.5 rounded transition cursor-pointer"
                                >
                                    Apply
                                </button>


                                <form
                                    action="{{ route('service-terms.template.disable', $tpl->id) }}"
                                    method="POST"
                                    class="inline"
                                    onsubmit="return confirm('Are you sure you want to disable this template?');"
                                >

                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        class="bg-red-50 hover:bg-red-100 text-red-600 font-semibold px-3 py-1.5 rounded transition cursor-pointer"
                                    >
                                        Disable
                                    </button>

                                </form>

                            </div>

                        </div>

                    @empty

                        <div class="border border-dashed border-slate-200 rounded-lg p-6 text-center text-slate-400 italic">
                            No active templates found in the library.
                        </div>

                    @endforelse

                </div>


                <div class="flex justify-end pt-3 border-t">

                    <button
                        type="button"
                        @click="showTemplateLibraryModal = false"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded font-medium cursor-pointer"
                    >
                        Close
                    </button>

                </div>

            </div>

        </div>

    </template>


    <!-- ===================================================== -->
    <!-- EDIT TERM MODAL -->
    <!-- ===================================================== -->

    <template x-teleport="body">

        <div
            x-show="showEditTermModal"
            x-cloak
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center p-4 z-[9999] overflow-y-auto"
        >

            <div
                @click.away="showEditTermModal = false"
                class="bg-white rounded-lg shadow-2xl max-w-lg w-full p-6 text-xs space-y-4"
            >

                <h3 class="text-sm font-bold text-slate-900 border-b pb-2">
                    Edit Service Term
                </h3>


                <form
                    :action="'/service-terms/' + editForm.id"
                    method="POST"
                    class="space-y-4"
                >

                    @csrf
                    @method('PUT')


                    <div>

                        <label class="block font-semibold text-slate-700 mb-1">
                            Title *
                        </label>

                        <input
                            type="text"
                            name="title"
                            x-model="editForm.title"
                            required
                            class="w-full border border-slate-300 rounded p-2 bg-white text-slate-800"
                        >

                    </div>


                    <div>

                        <label class="block font-semibold text-slate-700 mb-1">
                            Scope *
                        </label>

                        <select
                            name="scope"
                            x-model="editForm.scope"
                            required
                            class="w-full border border-slate-300 rounded p-2 bg-white text-slate-800"
                        >

                            <option value="service_specific">
                                Service Specific
                            </option>

                            <option value="global">
                                Global Scope
                            </option>

                            <option value="service_area">
                                Service Area Scope
                            </option>

                            <option value="category">
                                Category
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="block font-semibold text-slate-700 mb-1">
                            Content *
                        </label>

                        <textarea
                            name="content"
                            x-model="editForm.content"
                            rows="4"
                            required
                            class="w-full border border-slate-300 rounded p-2 bg-white text-slate-800"
                        ></textarea>

                    </div>


                    <div class="flex justify-end space-x-2 pt-2 border-t">

                        <button
                            type="button"
                            @click="showEditTermModal = false"
                            class="bg-slate-100 px-4 py-2 rounded cursor-pointer"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded cursor-pointer"
                        >
                            Update Term
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </template>


    <!-- ===================================================== -->
    <!-- VIEW TERM MODAL -->
    <!-- ===================================================== -->

    <template x-teleport="body">

        <div
            x-show="showViewTermModal"
            x-cloak
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center p-4 z-[9999] overflow-y-auto"
        >

            <div
                @click.away="showViewTermModal = false"
                class="bg-white rounded-lg shadow-2xl max-w-lg w-full p-6 text-xs space-y-4 relative pointer-events-auto"
            >

                <div class="flex justify-between items-center border-b pb-3">

                    <div class="flex items-center space-x-2">

                        <h3
                            class="text-sm font-bold text-slate-950"
                            x-text="viewForm.title"
                        ></h3>

                        <span
                            class="bg-slate-100 text-slate-700 text-[9px] font-bold px-2 py-0.5 rounded uppercase tracking-wider"
                            x-text="viewForm.source"
                        ></span>

                    </div>


                    <button
                        type="button"
                        @click="showViewTermModal = false"
                        class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 cursor-pointer"
                    >
                        &times;
                    </button>

                </div>


                <div class="py-2">

                    <label class="block font-semibold text-slate-500 uppercase tracking-wider text-[10px] mb-1">
                        Clause Content / Details
                    </label>

                    <p
                        class="text-slate-700 leading-relaxed text-xs bg-slate-50 p-3 rounded-md border border-slate-200 whitespace-pre-wrap"
                        x-text="viewForm.content"
                    ></p>

                </div>


                <div class="flex justify-end pt-3 border-t">

                    <button
                        type="button"
                        @click="showViewTermModal = false"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded font-medium cursor-pointer"
                    >
                        Close
                    </button>

                </div>

            </div>

        </div>

    </template>

</div>

<!-- TAB 10: USAGE & PERFORMANCE -->
<div
    x-show="activeTab === 'usage_performance'"
    x-cloak
    class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-6"
>

    <!-- HEADER -->
    <div class="border-b pb-3 flex justify-between items-center">

        <div>
            <h2 class="text-base font-bold text-slate-900">
                Usage &amp; Performance Analytics
            </h2>

            <p class="text-xs text-slate-500 mt-0.5">
                System-generated service usage, commercial, and performance metrics.
            </p>
        </div>

        <span class="bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-semibold px-2.5 py-1 rounded-md inline-flex items-center space-x-1">
            <i class="fa-solid fa-chart-line"></i>
            <span>Live Analytics Active</span>
        </span>

    </div>


    <!-- USAGE & COMMERCIAL PERFORMANCE -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">


        <!-- DEALS -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">

            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Deals
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                {{ $dealsCount ?? 0 }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Connected deal records
            </span>

        </div>


        <!-- PROPOSALS -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">

            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Proposals
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                {{ $proposalsCount ?? 0 }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Connected proposal records
            </span>

        </div>


        <!-- ACCEPTED -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">

            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Accepted
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                {{ $acceptedCount ?? 0 }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Accepted / contracted records
            </span>

        </div>


        <!-- UNIQUE CLIENTS -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">

            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Unique Clients
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                {{ $uniqueClientsCount ?? 0 }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Unique purchasing clients
            </span>

        </div>


        <!-- ENGAGEMENTS -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">

            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Engagements
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                {{ $engagementsCreatedCount ?? 0 }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Service engagements created
            </span>

        </div>


        <!-- PROPOSED VALUE -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">

            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Proposed Value
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                ₱{{ number_format($proposedValue ?? 0, 2) }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Total connected proposed value
            </span>

        </div>


        <!-- ACCEPTED / CONTRACTED VALUE -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">

            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Accepted / Contracted Value
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                ₱{{ number_format($acceptedContractedValue ?? 0, 2) }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Total accepted / contracted value
            </span>

        </div>


        <!-- DISCOUNT -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">

            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Discount
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                ₱{{ number_format($discountValue ?? 0, 2) }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Total connected discount value
            </span>

        </div>


        <!-- BILLED REVENUE -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">

            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Billed Revenue
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                ₱{{ number_format($billedRevenue ?? 0, 2) }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Connected billing records
            </span>

        </div>


        <!-- COLLECTED REVENUE -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">

            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Collected Revenue
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                ₱{{ number_format($collectedRevenue ?? 0, 2) }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Connected collection records
            </span>

        </div>

    </div>


    <!-- ANALYTICS STATUS -->
    <div class="border border-slate-200 rounded-lg overflow-hidden bg-white">

        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">

            <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider">
                Analytics Source Status
            </h3>

        </div>

        <div class="px-4 py-3">

            <div class="flex items-center space-x-2 text-xs text-slate-600">
                <i class="fa-solid fa-link text-slate-400"></i>

                <span>
                    Metrics are intended to be system-generated from connected Deals, Proposals, Clients, Engagements, Discount, Billing, and Collection records.
                </span>
            </div>

        </div>

    </div>


    <!-- NAVIGATION -->
    <div class="flex justify-between items-center pt-4 border-t">

        <button
            type="button"
            @click="changeTab('terms')"
            class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer"
        >
            &larr; Back to Terms
        </button>


        <button
            type="button"
            @click="changeTab('versions_history')"
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded text-xs transition shadow-sm cursor-pointer inline-flex items-center space-x-1.5"
        >
            <span>Continue to Versions &amp; History</span>
            <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </button>

    </div>

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

                        <!-- Scope of Work with Icon Inside Textarea Box (Left Side) -->
                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Scope of Work *</label>
                            <div class="relative">
                                <div class="absolute top-2.5 left-2.5 group cursor-pointer inline-flex items-center z-10">
                                    <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-80 bg-slate-900 text-white text-sm p-3.5 rounded-lg shadow-2xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                        A detailed operational outline of what the organization agrees to perform and deliver for the client under this service.
                                    </div>
                                </div>
                                <textarea name="scope_of_work" rows="3" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2.5 pl-8 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}" placeholder="Detail what the organization agrees to perform...">{{ old('scope_of_work', $activeVersion->scope_of_work ?? '') }}</textarea>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Deliverables with Icon Inside Textarea Box (Left Side) -->
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Deliverables / What You Will Receive *</label>
                                <div class="relative">
                                    <div class="absolute top-2.5 left-2.5 group cursor-pointer inline-flex items-center z-10">
                                        <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-80 bg-slate-900 text-white text-sm p-3.5 rounded-lg shadow-2xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                            The key tangible items, reports, or final results that the client will receive upon completion.
                                        </div>
                                    </div>
                                    <textarea name="deliverables" rows="3" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2.5 pl-8 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}" placeholder="Key tangible deliverables client receives...">{{ old('deliverables', $activeVersion->deliverables ?? '') }}</textarea>
                                </div>
                            </div>

                            <!-- Client Responsibilities with Icon Inside Textarea Box (Left Side) -->
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Client Responsibilities</label>
                                <div class="relative">
                                    <div class="absolute top-2.5 left-2.5 group cursor-pointer inline-flex items-center z-10">
                                        <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-80 bg-slate-900 text-white text-sm p-3.5 rounded-lg shadow-2xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                            Specific obligations, documents, or actions that belong to and must be provided by the client.
                                        </div>
                                    </div>
                                    <textarea name="client_responsibilities" rows="3" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2.5 pl-8 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}" placeholder="Actions or obligations belonging to the client...">{{ old('client_responsibilities', $activeVersion->client_responsibilities ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Exclusions with Icon Inside Textarea Box (Left Side) -->
                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Exclusions / Out of Scope</label>
                            <div class="relative">
                                <div class="absolute top-2.5 left-2.5 group cursor-pointer inline-flex items-center z-10">
                                    <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-80 bg-slate-900 text-white text-sm p-3.5 rounded-lg shadow-2xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                        Tasks and services that are explicitly not included unless separately agreed upon and charged.
                                    </div>
                                </div>
                                <textarea name="exclusions" rows="2" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2.5 pl-8 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}" placeholder="What is not included unless separately agreed...">{{ old('exclusions', $activeVersion->exclusions ?? '') }}</textarea>
                            </div>
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
<div x-show="activeTab === 'requirements'" x-cloak class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-4"
     x-data="{ 
         selectedTemplate: '', 
         customRequirement: '', 
         showTemplateLibraryModal: false, 
         showSelectTemplateModal: false,
         showAddToLibraryModal: false,
         selectedRequirements: [],
         selectAll: false,
         toggleSelectAll() {
             this.selectAll = !this.selectAll;
             if (this.selectAll) {
                 this.selectedRequirements = [
                     @foreach($activeVersion->requirements ?? [] as $req)
                         @php $rObj = is_array($req) ? (object)$req : $req; @endphp
                         {{ $rObj->id ?? '' }},
                     @endforeach
                 ].filter(Boolean);
             } else {
                 this.selectedRequirements = [];
             }
         }
     }">
     
    <div class="flex justify-between items-center border-b pb-3">
        <div>
            <h2 class="text-sm font-bold text-slate-900">Structured Requirements &amp; Operational Checklist</h2>
            <p class="text-[11px] text-slate-500">Configured requirements convert directly into operational engagement checklists without retyping.</p>
        </div>
        @if(!$isViewOnly)
            <div class="flex items-center space-x-2">
    
                <template x-if="selectedRequirements.length > 0">
                    <button type="button" 
                            @click="showAddToLibraryModal = true" 
                            class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1.5 rounded font-semibold transition cursor-pointer flex items-center space-x-1">
                        <i class="fa-solid fa-plus"></i>
                        <span>Add to Template Library (<span x-text="selectedRequirements.length"></span>)</span>
                    </button>
                </template>
                <template x-if="selectedRequirements.length === 0">
                    <button type="button" 
                            @click="showTemplateLibraryModal = true" 
                            class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs px-3 py-1.5 rounded font-semibold transition cursor-pointer flex items-center space-x-1">
                        <i class="fa-solid fa-bookmark text-slate-500"></i>
<span>Template Library ({{ $templateCount }})</span>
                    </button>
                </template>

                <!-- Add Requirement Button -->
                <button type="button" 
                        @click="showReqModal = true" 
                        class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1.5 rounded font-semibold transition flex items-center space-x-1 cursor-pointer">
                    <i class="fa-solid fa-plus"></i>
                    <span>Add Requirement</span>
                </button>
            </div>
        @endif
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs whitespace-nowrap">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-semibold">
                    <th class="py-2.5 px-3 w-10 text-center">
                        <input type="checkbox" @click="toggleSelectAll()" :checked="selectAll" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                    </th>
                    <th class="py-2.5 px-3 w-12 text-center">#</th>
                    <th class="py-2.5 px-3">REQUIREMENT NAME</th>
                    <th class="py-2.5 px-3">CLIENT TYPE</th>
                    <th class="py-2.5 px-3">STATUS</th>
                    <th class="py-2.5 px-3">SOURCE</th>
                    <th class="py-2.5 px-3">EVIDENCE NEEDED</th>
                    <th class="py-2.5 px-3">VALIDITY</th>
                    <th class="py-2.5 px-3 text-right">ACTION</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($activeVersion->requirements ?? [] as $index => $req)
                    @php
                        $reqObj = is_array($req) ? (object)$req : $req;
                        $reqId = $reqObj->id ?? null;
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="py-2.5 px-3 text-center">
                            @if($reqId)
                                <input type="checkbox" value="{{ $reqId }}" x-model.number="selectedRequirements" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                            @endif
                        </td>
                        <td class="py-2.5 px-3 text-center text-slate-400 font-medium">{{ $index + 1 }}</td>
                        <td class="py-2.5 px-3 font-medium text-slate-900">
                            <button type="button" 
                                    @click="openEditReqModal({{ json_encode($reqObj) }})" 
class="text-slate-900 hover:text-black hover:underline cursor-pointer text-left font-medium">
                                {{ $reqObj->requirement_name }}
                            </button>
                        </td>
                        <td class="py-2.5 px-3"><span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-[10px] font-semibold">{{ $reqObj->client_type ?? 'All' }}</span></td>
                        <td class="py-2.5 px-3 font-semibold {{ $reqObj->is_mandatory ? 'text-slate-950' : 'text-amber-600' }}">{{ $reqObj->is_mandatory ? 'Mandatory' : 'Optional' }}</td>
                        <td class="py-2.5 px-3">{{ $reqObj->source ?? 'Client-supplied' }}</td>
                        <td class="py-2.5 px-3">{{ $reqObj->file_required ? 'File Upload / Soft Copy' : 'Physical Original' }}</td>
                        <td class="py-2.5 px-3 text-slate-400">{{ $reqObj->validity_expiration ?: 'N/A' }}</td>
                        <td class="py-2.5 px-3 text-right space-x-2 whitespace-nowrap">
                            @if(!$isViewOnly)
                                <button type="button" 
                                        @click="openEditReqModal({{ json_encode($reqObj) }})" 
                                        class="text-slate-900 hover:text-black hover:underline text-xs font-semibold cursor-pointer">
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
                        <td colspan="10" class="py-6 text-center text-slate-400 italic">
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
            <span>Save & Continue to Workflow</span>
            <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </button>
    </div>

   <!-- ADD TO TEMPLATE LIBRARY CONFIRMATION MODAL -->
    <template x-teleport="body">
        <div x-show="showAddToLibraryModal" x-cloak class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center p-4 z-[9999] overflow-y-auto">
            <div @click.away="showAddToLibraryModal = false" class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 text-xs space-y-4 relative pointer-events-auto">
                <div class="flex justify-between items-center border-b pb-3">
                    <h3 class="text-sm font-bold text-slate-900">Add to Template Library</h3>
                    <button type="button" @click="showAddToLibraryModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 cursor-pointer">&times;</button>
                </div>
                
                <form action="{{ isset($service->id) ? route('services.requirements.bulk-template', $service->id) : '#' }}" method="POST" class="space-y-4">
                    @csrf
                    <p class="text-slate-700 font-medium">Are you sure you want to add this in your template library?</p>
                    <p class="text-[11px] text-slate-500">Bilang ng napili: <span class="font-bold text-slate-800" x-text="selectedRequirements.length"></span> items</p>

                    <div>
                        <template x-for="id in selectedRequirements" :key="id">
                            <input type="hidden" name="requirement_ids[]" :value="id">
                        </template>
                    </div>

                    <div class="flex justify-end space-x-2 pt-3 border-t">
                        <button type="button" @click="showAddToLibraryModal = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded font-medium cursor-pointer">Cancel</button>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium cursor-pointer">Confirm & Save</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- REQUIREMENTS TEMPLATE LIBRARY MODAL -->
    <template x-teleport="body">
        <div x-show="showTemplateLibraryModal" x-cloak class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center p-4 z-[9999] overflow-y-auto">
            <div @click.away="showTemplateLibraryModal = false" class="bg-white rounded-xl shadow-2xl max-w-4xl w-full p-6 text-xs space-y-4 relative pointer-events-auto max-h-[85vh] flex flex-col">
                <div class="flex justify-between items-center border-b pb-3">
                    <h3 class="text-sm font-bold text-slate-900">Requirements Template Library</h3>
                    <button type="button" @click="showTemplateLibraryModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 cursor-pointer">&times;</button>
                </div>
                
                <p class="text-slate-600 text-xs">Manage structural templates, apply them directly, or remove unused items.</p>
                
                <div class="space-y-3 overflow-y-auto flex-1 pr-1">
                    @forelse($globalRequirements ?? [] as $global)
                        <div class="p-4 bg-white rounded-lg border border-slate-200 shadow-sm flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="font-bold text-slate-800 text-sm">{{ $global->requirement_name }}</span>
                                <div class="text-[11px] text-slate-500 flex items-center space-x-2">
                                    <span>Type: <strong class="text-slate-700">{{ $global->client_type ?? 'All' }}</strong></span>
                                    <span>&bull;</span>
                                    <span class="text-emerald-600 font-semibold">Status: ACTIVE</span>
                                </div>
                            </div>
                            <div class="space-x-2 flex items-center">
                                <form action="{{ Route::has('services.requirements.store') && isset($service->id) ? route('services.requirements.store', $service->id) : '#' }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="mode" value="{{ $mode }}">
                                    <input type="hidden" name="requirement_name" value="{{ $global->requirement_name }}">
                                    <input type="hidden" name="client_type" value="{{ $global->client_type ?? 'All' }}">
                                    <input type="hidden" name="source" value="{{ $global->source ?? 'Client-supplied' }}">
                                    <input type="hidden" name="file_required" value="{{ $global->file_required ?? 1 }}">
                                    <input type="hidden" name="is_mandatory" value="{{ $global->is_mandatory ?? 1 }}">
                                    <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-900 px-3 py-1.5 rounded font-semibold transition cursor-pointer">Apply</button>
                                </form>
                              <form action="{{ route('global-requirements.destroy', $global->id) }}"
      method="POST"
      class="inline"
      onsubmit="return confirm('Are you sure you want to delete this template?');">
    @csrf
    @method('DELETE')

    <button type="submit"
            class="bg-rose-50 hover:bg-rose-100 text-rose-600 px-3 py-1.5 rounded font-semibold transition cursor-pointer">
        Delete
    </button>
</form>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 space-y-2">
                            <p class="text-slate-400 italic">Wala pang nakaimbak o naubos na ang mga templates sa library.</p>
                            <p class="text-[11px] text-slate-500">Maaari kang magdagdag ng bago gamit ang "Add Requirement" o i-save ang mga ito mula sa table.</p>
                        </div>
                    @endforelse
                </div>

                <div class="flex justify-between items-center pt-3 border-t">
                    <span class="text-[11px] text-slate-500">Tip: You can apply a template even if the others have run out.</span>
                    <button type="button" @click="showTemplateLibraryModal = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded font-medium cursor-pointer">Close</button>
                </div>
            </div>
        </div>
    </template>
    
    <!-- SELECT FROM TEMPLATE LIBRARY MODAL -->
    <template x-teleport="body">
        <div x-show="showSelectTemplateModal" x-cloak class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center p-4 z-[9999] overflow-y-auto">
            <div @click.away="showSelectTemplateModal = false" class="bg-white rounded-lg shadow-2xl max-w-lg w-full p-6 text-xs space-y-4 relative pointer-events-auto">
                <div class="flex justify-between items-center border-b pb-3">
                    <h3 class="text-sm font-bold text-slate-900">Select From Template Library</h3>
                    <button type="button" @click="showSelectTemplateModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 cursor-pointer">&times;</button>
                </div>
                
                <div class="space-y-3">
                    <label class="block font-semibold text-slate-700">Pumili ng Template:</label>
                    <select class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                        <option value="">-- Pumili mula sa global templates --</option>
                        @foreach($globalRequirements ?? [] as $global)
                            <option value="{{ $global->requirement_name }}">{{ $global->requirement_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end space-x-2 pt-3 border-t">
                    <button type="button" @click="showSelectTemplateModal = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded font-medium cursor-pointer">Cancel</button>
                    <button type="button" @click="showSelectTemplateModal = false" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium cursor-pointer">Apply Template</button>
                </div>
            </div>
        </div>
    </template>

    <!-- ADD REQUIREMENT MODAL -->
    <template x-teleport="body">
        <div x-show="showReqModal" x-cloak class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center p-4 z-[9999] overflow-y-auto"
             x-data="{ 
                 inputName: '',
                 globalList: [
                     @foreach($globalRequirements ?? [] as $global)
                         '{{ addslashes($global->requirement_name) }}',
                     @endforeach
                 ],
                 get isAlreadyExisting() {
                     if (!this.inputName.trim()) return false;
                     return this.globalList.some(item => item.toLowerCase() === this.inputName.trim().toLowerCase());
                 }
             }">
            <div @click.away="showReqModal = false" class="bg-white rounded-lg shadow-2xl max-w-lg w-full p-6 text-xs space-y-4 max-h-[90vh] overflow-y-auto relative pointer-events-auto">
                <div class="flex justify-between items-center border-b pb-3">
                    <h3 class="text-sm font-bold text-slate-900">Add Service Requirement</h3>
                    <button type="button" @click="showReqModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 cursor-pointer">&times;</button>
                </div>

                <form action="{{ Route::has('services.requirements.store') && isset($service->id) ? route('services.requirements.store', $service->id) : '#' }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="mode" value="{{ $mode }}">
                    <input type="hidden" name="save_as_template" value="1">

                    <div class="bg-slate-50 p-3 rounded border border-slate-200 space-y-2">
                        <label class="block font-semibold text-slate-700">Select from Existing / Global Templates</label>
                        <select x-model="selectedTemplate" @change="if(selectedTemplate) { inputName = selectedTemplate; }" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                            <option value="">-- Choose from existing templates or type custom below --</option>
                            @foreach($globalRequirements ?? [] as $global)
                                <option value="{{ $global->requirement_name }}">{{ $global->requirement_name }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center justify-between text-[10px] text-slate-500 pt-1">
                            <span>Automatically saved as a reusable requirement for future use.</span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Requirement Name (Or type custom text) *</label>
                            <input type="text" name="requirement_name" x-model="inputName" placeholder="e.g. Type custom requirement here if not in templates..." class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800" required>
                            
                            <!-- Existing Warning Notification -->
                            <div x-show="isAlreadyExisting" x-cloak class="mt-1.5 p-2 bg-amber-50 border border-amber-200 rounded text-amber-700 text-[11px] flex items-center space-x-1.5">
                                <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
                                <span>Note: This requirement already exists in your Template Library.</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Applies to Client Type</label>
                                <select name="client_type" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                    <option value="all">All Client Types</option>
                                    <option value="individual">Individual</option>
                                    <option value="sole_proprietor">Sole Proprietor</option>
                                    <option value="corporation">Corporation</option>
                                    <option value="partnership">Partnership</option>
                                    <option value="cooperative">Cooperative</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Requirement Source</label>
                                <select name="source" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                    <option value="Client-supplied">Client-supplied</option>
                                    <option value="Internal">Internal</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Evidence Type Needed</label>
                                <select name="file_required" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                    <option value="1">File Upload / Soft Copy</option>
                                    <option value="0">Physical / Hard Copy / Verified</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Mandatory Level</label>
                                <select name="is_mandatory" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                                    <option value="1">Mandatory (Required)</option>
                                    <option value="0">Optional</option>
                                </select>
                            </div>
                        </div>

                        <!-- Validity / Expiration Rule -->
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Validity / Expiration Rule</label>
                            <input type="text" name="validity_expiration" placeholder="e.g. Within last 6 months" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
                        </div>

                        <!-- Instructions / Handling Notes -->
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Instructions / Handling Notes</label>
                            <textarea name="instructions" rows="2" placeholder="Special guidance for associates when validating this requirement..." class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2 pt-3 border-t">
                        <button type="button" @click="showReqModal = false" class="px-4 py-2 border border-slate-300 rounded text-slate-700 hover:bg-slate-100 cursor-pointer">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 cursor-pointer font-semibold">Save Requirement</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
    
    <!-- EDIT / VIEW REQUIREMENT MODAL -->
    <template x-teleport="body">
        <div x-show="showEditReqModal" x-cloak class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center p-4 z-[9999] overflow-y-auto">
            <div @click.away="showEditReqModal = false" class="bg-white rounded-lg shadow-2xl max-w-3xl w-full p-6 text-xs space-y-4 max-h-[90vh] overflow-y-auto relative pointer-events-auto">
                <div class="flex justify-between items-center border-b pb-3">
                    <h3 class="text-sm font-bold text-slate-900" x-text="{{ $isViewOnly ? 'true' : 'false' }} ? 'View Service Requirement' : 'Edit Service Requirement'"></h3>
                    <button type="button" @click="showEditReqModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 cursor-pointer">&times;</button>
                </div>

                @if($isViewOnly)
                    <!-- VIEW ONLY MODE (Pure Read-Only Layout) -->
                    <div class="space-y-3 text-slate-700">
                        <div>
                            <span class="block font-semibold text-slate-500 text-[10px] uppercase">Requirement Name</span>
                            <p class="font-bold text-slate-900 text-sm mt-0.5" x-text="editReqData.requirement_name"></p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 bg-slate-50 p-3 rounded border border-slate-200">
                            <div>
                                <span class="block font-semibold text-slate-500 text-[10px] uppercase">Client Type</span>
                                <p class="font-medium mt-0.5" x-text="editReqData.client_type"></p>
                            </div>
                            <div>
                                <span class="block font-semibold text-slate-500 text-[10px] uppercase">Source</span>
                                <p class="font-medium mt-0.5" x-text="editReqData.source"></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 bg-slate-50 p-3 rounded border border-slate-200">
                            <div>
                                <span class="block font-semibold text-slate-500 text-[10px] uppercase">Evidence Needed</span>
                                <p class="font-medium mt-0.5" x-text="editReqData.file_required == 1 ? 'File Upload / Soft Copy' : 'Physical Original / Hard Copy'"></p>
                            </div>
                            <div>
                                <span class="block font-semibold text-slate-500 text-[10px] uppercase">Mandatory Level</span>
                                <p class="font-medium mt-0.5" x-text="editReqData.is_mandatory == 1 ? 'Mandatory (Required)' : 'Optional / Conditional'"></p>
                            </div>
                        </div>

                        <div>
                            <span class="block font-semibold text-slate-500 text-[10px] uppercase">Validity / Expiration Rule</span>
                            <p class="font-medium mt-0.5 text-slate-800" x-text="editReqData.validity_expiration || 'N/A'"></p>
                        </div>

                        

                        <div>
                            <span class="block font-semibold text-slate-500 text-[10px] uppercase">Instructions / Handling Notes</span>
                            <p class="font-medium mt-0.5 bg-slate-50 p-2.5 rounded border border-slate-200 text-slate-800 whitespace-pre-wrap" x-text="editReqData.instructions || 'No instructions provided.'"></p>
                        </div>

                        <div class="flex justify-end pt-3 border-t">
                            <button type="button" @click="showEditReqModal = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded font-medium cursor-pointer">Close</button>
                        </div>
                    </div>
                @else
                    <!-- EDIT MODE (Standard Editable Form) -->
                    <form :action="'{{ $reqUpdateRouteTemplate ?? '' }}'.replace(':id', editReqData.id)" method="POST" class="space-y-4">
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
                            <label class="block font-semibold text-slate-700 mb-1">Validity / Expiration Rule</label>
                            <input type="text" name="validity_expiration" x-model="editReqData.validity_expiration" class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">
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
                @endif
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
                            <span>Save & Continue to Commercials</span>
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

                <!-- BINAGO: Ginawang 2 columns para maging pantay at malaki ang Expected Days at Working Hours -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                
                
                <!-- TAB 6: ENGAGEMENT -->
                <div x-show="activeTab === 'engagement'" x-cloak class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-6 pb-40">
                    <div class="border-b pb-3">
                        <h2 class="text-base font-bold text-slate-900">Engagement Configuration &amp; Recurrence Rules</h2>
                        <p class="text-xs text-slate-500">Preset delivery behavior set during Quick Add and automatic operational instantiation rules.</p>
                    </div>

                    @php
                        $recFreq = old('recurrence_frequency', $activeVersion->recurrence_frequency ?? '');
                        $standardRecOptions = [
                            'Monthly (e.g. Bookkeeping, Monthly Reports)',
                            'Bi-Weekly / Semi-Monthly',
                            'Semi-Annual (Every 6 Months)',
                            'Annual / Yearly'
                        ];

                        $isCustomRec = !empty($recFreq) && !in_array($recFreq, array_merge($standardRecOptions, ['Quarterly']));
                        $selectedRecOption = ($recFreq === 'Quarterly') ? 'Quarterly' : ($isCustomRec ? 'Custom Cadence' : $recFreq);
                        $customRecText = ($isCustomRec && $recFreq !== 'Quarterly') ? $recFreq : '';

                        $paymentStructureVal = old('payment_structure', $activeVersion->payment_structure ?? '');
                        $standardPaymentTerms = [
                            'Full Advance',
                            '50% Down / 50% Upon Completion',
                            'Monthly Retainer',
                            'Milestone Based'
                        ];
                        $isCustomPaymentTerm = !empty($paymentStructureVal) && !in_array($paymentStructureVal, $standardPaymentTerms);
                        $selectedPaymentTermOption = $isCustomPaymentTerm ? 'Others' : $paymentStructureVal;
                        $customPaymentTermText = $isCustomPaymentTerm ? $paymentStructureVal : '';

                        $billFreq = old('billing_frequency', $activeVersion->billing_frequency ?? '');
                        $standardBillOptions = ['Monthly', 'Semi-Annual', 'Annual', 'Upon Completion'];
                        $isCustomBill = !empty($billFreq) && !in_array($billFreq, array_merge($standardBillOptions, ['Quarterly', 'Custom']));
                        $selectedBillOption = ($billFreq === 'Quarterly') ? 'Quarterly' : ($isCustomBill ? 'Custom' : $billFreq);
                        $customBillText = ($isCustomBill && $billFreq !== 'Quarterly') ? $billFreq : '';

                        $repFreq = old('reporting_frequency', $activeVersion->reporting_frequency ?? '');
                        $standardRepOptions = ['Monthly', 'Semi-Annual', 'Annual'];
                        $isCustomRep = !empty($repFreq) && !in_array($repFreq, array_merge($standardRepOptions, ['Quarterly', 'Custom']));
                        $selectedRepOption = ($repFreq === 'Quarterly') ? 'Quarterly' : ($isCustomRep ? 'Custom' : $repFreq);
                        $customRepText = ($isCustomRep && $repFreq !== 'Quarterly') ? $repFreq : '';
                    @endphp

                    <form action="{{ Route::has('services.version.update') && isset($service->id) ? route('services.version.update', ['service' => $service->id]) : '#' }}" 
                          method="POST" 
                          x-data="{ 
                              recOption: '{{ $selectedRecOption }}',
                              customRecText: '{{ $customRecText }}',
                              paymentTermOption: '{{ $selectedPaymentTermOption }}',
                              customPaymentTermText: '{{ $customPaymentTermText }}',
                              billOption: '{{ $selectedBillOption }}',
                              customBillText: '{{ $customBillText }}',
                              repOption: '{{ $selectedRepOption }}',
                              customRepText: '{{ $customRepText }}'
                          }"
                          class="space-y-6 text-xs pb-20">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="tab" value="engagement">
                        <input type="hidden" name="next_tab" value="reporting">

                        <div class="border border-slate-200 rounded-lg p-4 space-y-4">
                            <h3 class="font-bold text-slate-800 uppercase text-[11px] tracking-wider">CONFIGURED ENGAGEMENT SETTINGS</h3>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Engagement Delivery Behavior with Icon Inside Box (Left Side) -->
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Engagement Delivery Behavior</label>
                                    <div class="relative flex items-center">
                                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                                Defines how the service is structured and delivered to the client over time (e.g., regular retainer or recurring tasks).
                                            </div>
                                        </div>
                                        <input type="text" 
                                               value="{{ ucfirst($service->engagement_behavior ?? 'regular') }} (Retainer / Recurring)" 
                                               readonly 
                                               class="w-full border border-slate-200 bg-slate-100 rounded p-2 pl-8 text-slate-600 font-medium cursor-not-allowed outline-none" 
                                        />
                                    </div>
                                </div>



                                <!-- Instantiation Execution Mode with Icon Inside Box (Left Side) -->
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Instantiation Execution Mode</label>
                                    <div class="relative flex items-center">
                                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                                Specifies whether tasks and periods are generated automatically by the system or triggered via manual review.
                                            </div>
                                        </div>
                                        <select name="instantiation_mode" class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                                            <option value="Automatic System Instantiation (JIT)" {{ old('instantiation_mode', $activeVersion->instantiation_mode ?? '') == 'Automatic System Instantiation (JIT)' ? 'selected' : '' }}>Automatic System Instantiation (JIT)</option>
                                            <option value="Manual Review" {{ old('instantiation_mode', $activeVersion->instantiation_mode ?? '') == 'Manual Review' ? 'selected' : '' }}>Manual Review</option>
                                            <option value="On-Demand" {{ old('instantiation_mode', $activeVersion->instantiation_mode ?? '') == 'On-Demand' ? 'selected' : '' }}>On-Demand</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border border-slate-200 rounded-lg p-4 space-y-4">
                            <h3 class="font-bold text-slate-800 uppercase text-[11px] tracking-wider">RECURRENCE CADENCES &amp; RULES</h3>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                                <!-- Recurrence Frequency with Icon Inside Box (Left Side) -->
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Recurrence Frequency *</label>
                                    <div class="relative flex items-center">
                                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                                The frequency or cycle at which recurring tasks and deliverables are automatically repeated.
                                            </div>
                                        </div>
                                        <select x-model="recOption" class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                                            <option value="">-- Select Recurrence Frequency --</option>
                                            <option value="Monthly (e.g. Bookkeeping, Monthly Reports)">Monthly (e.g. Bookkeeping, Monthly Reports)</option>
                                            <option value="Bi-Weekly / Semi-Monthly">Bi-Weekly / Semi-Monthly</option>
                                            <option value="Quarterly">Quarterly</option>
                                            <option value="Semi-Annual (Every 6 Months)">Semi-Annual (Every 6 Months)</option>
                                            <option value="Annual / Yearly">Annual / Yearly</option>
                                            <option value="Custom Cadence">Custom Cadence</option>
                                        </select>
                                    </div>

                                    <input type="hidden" name="recurrence_frequency" :value="(recOption === 'Custom Cadence' || recOption === 'Quarterly') ? customRecText : recOption">

                                    <div x-show="recOption === 'Custom Cadence' || recOption === 'Quarterly'" x-cloak class="mt-2">
                                        <input type="text" 
                                               x-model="customRecText"
                                               placeholder="Specify details (e.g., Quarterly specifics)..." 
                                               :required="recOption === 'Custom Cadence' || recOption === 'Quarterly'"
                                               class="w-full border border-slate-300 rounded p-2 text-xs outline-none bg-white text-slate-800 focus:border-blue-500" 
                                        />
                                    </div>
                                </div>

                                <!-- Billing Frequency with Icon Inside Box (Left Side) -->
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Billing Frequency *</label>
                                    <div class="relative flex items-center">
                                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                                How often invoices and billing statements are issued to the client for this service.
                                            </div>
                                        </div>
                                        <select x-model="billOption" class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                                            <option value="">-- Select Billing Frequency --</option>
                                            <option value="Monthly">Monthly</option>
                                            <option value="Quarterly">Quarterly</option>
                                            <option value="Semi-Annual">Semi-Annual</option>
                                            <option value="Annual">Annual</option>
                                            <option value="Upon Completion">Upon Completion</option>
                                            <option value="Custom">Custom</option>
                                        </select>
                                    </div>

                                    <input type="hidden" name="billing_frequency" :value="(billOption === 'Custom' || billOption === 'Quarterly') ? customBillText : billOption">

                                    <div x-show="billOption === 'Custom' || billOption === 'Quarterly'" x-cloak class="mt-2">
                                        <input type="text" 
                                               x-model="customBillText"
                                               placeholder="Specify billing details..." 
                                               :required="billOption === 'Custom' || billOption === 'Quarterly'"
                                               class="w-full border border-slate-300 rounded p-2 text-xs outline-none bg-white text-slate-800 focus:border-blue-500" 
                                        />
                                    </div>
                                </div>

                                <!-- Reporting Frequency with Icon Inside Box (Left Side) -->
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Reporting Frequency *</label>
                                    <div class="relative flex items-center">
                                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                                The schedule and cadence for generating progress reports or status summaries for the client.
                                            </div>
                                        </div>
                                        <select x-model="repOption" class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                                            <option value="">-- Select Reporting Frequency --</option>
                                            <option value="Monthly">Monthly</option>
                                            <option value="Quarterly">Quarterly</option>
                                            <option value="Semi-Annual">Semi-Annual</option>
                                            <option value="Annual">Annual</option>
                                            <option value="Custom">Custom</option>
                                        </select>
                                    </div>

                                    <input type="hidden" name="reporting_frequency" :value="(repOption === 'Custom' || repOption === 'Quarterly') ? customRepText : repOption">

                                    <div x-show="repOption === 'Custom' || repOption === 'Quarterly'" x-cloak class="mt-2">
                                        <input type="text" 
                                               x-model="customRepText"
                                               placeholder="Specify reporting details..." 
                                               :required="repOption === 'Custom' || repOption === 'Quarterly'"
                                               class="w-full border border-slate-300 rounded p-2 text-xs outline-none bg-white text-slate-800 focus:border-blue-500" 
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="pt-2 flex items-center space-x-2">
                                <input type="checkbox" 
                                       id="auto_carryover" 
                                       name="auto_carryover" 
                                       value="1" 
                                       {{ old('auto_carryover', $activeVersion->auto_carryover ?? true) ? 'checked' : '' }}
                                       class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" 
                                />
                                <label for="auto_carryover" class="text-xs text-slate-700 cursor-pointer">
                                    Auto-carryover incomplete tasks to the next recurring period
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t">
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

                    @php
                        $isProjectEngagement = (($service->engagement_behavior ?? '') === 'project');
                        $isReadOnlyMode = (request('mode') === 'view');

                        if ($isProjectEngagement) {
                            $repFreq = 'Upon Completion';
                            $projRep = 'Final';
                            $isLocked = true;
                        } else {
                            $repFreq = old('reporting_frequency', $activeVersion->reporting_frequency ?? '');
                            $projRep = old('project_reporting', $activeVersion->project_reporting_type ?? $activeVersion->project_reporting ?? '');
                            $isLocked = $isReadOnlyMode;
                        }

                        $standardOptions = ['Monthly', 'Semiannual', 'Annual', 'Upon Completion', 'None'];
                        $isCustomValue = !empty($repFreq) && !in_array($repFreq, array_merge($standardOptions, ['Quarterly']));
                        $selectedFreqOption = ($repFreq === 'Quarterly') ? 'Quarterly' : ($isCustomValue ? 'Custom' : $repFreq);
                        $customFreqText = ($isCustomValue && $repFreq !== 'Quarterly') ? $repFreq : '';
                    @endphp

                    <form action="{{ Route::has('services.version.update') && isset($service->id) ? route('services.version.update', ['service' => $service->id]) : '#' }}" 
                          method="POST" 
                          x-data="{ 
                            repOption: '{{ $selectedFreqOption }}',
                            customText: '{{ $customFreqText }}'
                          }" 
                          class="space-y-6 text-xs">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="tab" value="reporting">
                        <input type="hidden" name="next_tab" value="automation">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Reporting Frequency with Icon Inside Box (Left Side) -->
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Reporting Frequency *</label>
                                @if($isLocked)
                                    <div class="relative flex items-center">
                                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                                The schedule or frequency at which formal progress reports and status summaries are compiled and transmitted to the client.
                                            </div>
                                        </div>
                                        <input type="text" 
                                               value="{{ $repFreq }}" 
                                               readonly 
                                               class="w-full border border-slate-200 bg-slate-100 rounded p-2 pl-8 text-slate-600 font-medium cursor-not-allowed outline-none" 
                                        />
                                    </div>
                                    <input type="hidden" name="reporting_frequency" value="{{ $repFreq }}">
                                @else
                                    <div class="relative flex items-center">
                                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                                The schedule or frequency at which formal progress reports and status summaries are compiled and transmitted to the client.
                                            </div>
                                        </div>
                                        <select x-model="repOption" class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                                            <option value="">-- Select Reporting Frequency --</option>
                                            <option value="Monthly">Monthly</option>
                                            <option value="Quarterly">Quarterly</option>
                                            <option value="Semiannual">Semiannual</option>
                                            <option value="Annual">Annual</option>
                                            <option value="Upon Completion">Upon Completion</option>
                                            <option value="None">None</option>
                                            <option value="Custom">Custom</option>
                                        </select>
                                    </div>

                                    <input type="hidden" name="reporting_frequency" :value="(repOption === 'Custom' || repOption === 'Quarterly') ? customText : repOption">

                                    <div x-show="repOption === 'Custom' || repOption === 'Quarterly'" x-cloak class="mt-2">
                                        <input type="text" 
                                               x-model="customText"
                                               placeholder="Specify frequency details..." 
                                               :required="repOption === 'Custom' || repOption === 'Quarterly'"
                                               class="w-full border border-slate-300 rounded p-2 text-xs outline-none bg-white text-slate-800 focus:border-blue-500" 
                                        />
                                    </div>
                                @endif
                            </div>

                            <!-- Project Reporting Type with Icon Inside Box (Left Side) -->
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Project Reporting Type *</label>
                                @if($isLocked)
                                    <div class="relative flex items-center">
                                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                                Defines the specific categorization and format type of project reports delivered during or at the end of the engagement.
                                            </div>
                                        </div>
                                        <input type="text" 
                                               value="{{ $projRep }}" 
                                               readonly 
                                               class="w-full border border-slate-200 bg-slate-100 rounded p-2 pl-8 text-slate-600 font-medium cursor-not-allowed outline-none" 
                                        />
                                    </div>
                                    <input type="hidden" name="project_reporting" value="{{ $projRep }}">
                                    <input type="hidden" name="project_reporting_type" value="{{ $projRep }}">
                                @else
                                    <div class="relative flex items-center">
                                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                                Defines the specific categorization and format type of project reports delivered during or at the end of the engagement.
                                            </div>
                                        </div>
                                        <select name="project_reporting" required class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                                            <option value="" {{ empty($projRep) ? 'selected' : '' }}>-- Select Reporting Type --</option>
                                            <option value="Progress" {{ $projRep == 'Progress' ? 'selected' : '' }}>Progress</option>
                                            <option value="Final" {{ $projRep == 'Final' ? 'selected' : '' }}>Final</option>
                                        </select>
                                    </div>
                                @endif
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

        <span class="bg-white text-slate-700 border border-slate-200 text-[10px] font-semibold px-2.5 py-1 rounded-md inline-flex items-center space-x-1 shadow-sm">
            <i class="fa-solid fa-bolt text-slate-500"></i>
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
            <h3 class="font-bold text-slate-900 text-xs uppercase tracking-wider flex items-center space-x-2 border-b pb-2">
                <i class="fa-solid fa-gears text-slate-700"></i>
                <span>Task &amp; Period Instantiation Engine</span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <!-- Instantiation Mode with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Instantiation Mode *</label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                Determines how tasks or recurring periods are automatically or manually generated by the system.
                            </div>
                        </div>
                        <select name="instantiation_mode" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                            <option value="" {{ empty(old('instantiation_mode', $activeVersion->instantiation_mode ?? '')) ? 'selected' : '' }}>
                                --Select instantiation_mode--
                            </option>
                            <option value="automatic" {{ old('instantiation_mode', $activeVersion->instantiation_mode ?? '') == 'automatic' ? 'selected' : '' }}>
                                Automatic (System Generated)
                            </option>
                            <option value="manual_approval" {{ old('instantiation_mode', $activeVersion->instantiation_mode ?? '') == 'manual_approval' ? 'selected' : '' }}>
                                Manual (Requires Manager Trigger)
                            </option>
                            <option value="disabled" {{ old('instantiation_mode', $activeVersion->instantiation_mode ?? '') == 'disabled' ? 'selected' : '' }}>
                                Disabled
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Base Reference Date with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Base Reference Date</label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                The foundational anchor date (such as period start or contract signing) from which task schedules are computed.
                            </div>
                        </div>
                        <select name="base_reference_date" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                            <option value="" {{ empty(old('base_reference_date', $activeVersion->base_reference_date ?? '')) ? 'selected' : '' }}>
                                --Select base_reference_date--
                            </option>
                            <option value="period_start" {{ old('base_reference_date', $activeVersion->base_reference_date ?? '') == 'period_start' ? 'selected' : '' }}>
                                Period Start Date
                            </option>
                            <option value="engagement_start" {{ old('base_reference_date', $activeVersion->base_reference_date ?? '') == 'engagement_start' ? 'selected' : '' }}>
                                Engagement Start Date
                            </option>
                            <option value="contract_signing" {{ old('base_reference_date', $activeVersion->base_reference_date ?? '') == 'contract_signing' ? 'selected' : '' }}>
                                Contract Signing Date
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Lead Time Generation (Days) with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Lead Time Generation (Days)</label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                The number of days prior to the target period when tasks are generated in advance.
                            </div>
                        </div>
                        <input type="number" name="instantiation_lead_days" {{ $isViewOnly ? 'disabled' : '' }} min="0" value="{{ old('instantiation_lead_days', $activeVersion->instantiation_lead_days ?? 15) }}" class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                    </div>
                    <span class="text-[10px] text-slate-400 italic mt-0.5 block">
                        Number of days prior to start period.
                    </span>
                </div>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-slate-200/60">

                <!-- Default Auto-Assignment Rule with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Default Auto-Assignment Rule</label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                The standard logic used to automatically assign generated tasks to team members or operational pools.
                            </div>
                        </div>
                        <select name="default_assignment_rule" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                            <option value="" {{ empty(old('default_assignment_rule', $activeVersion->default_assignment_rule ?? '')) ? 'selected' : '' }}>
                                --Select_assignement_rule--
                            </option>
                            <option value="engagement_lead" {{ old('default_assignment_rule', $activeVersion->default_assignment_rule ?? '') == 'engagement_lead' ? 'selected' : '' }}>
                                Assign to Primary Engagement Lead
                            </option>
                            <option value="service_area_pool" {{ old('default_assignment_rule', $activeVersion->default_assignment_rule ?? '') == 'service_area_pool' ? 'selected' : '' }}>
                                Unassigned (Service Area Pool)
                            </option>
                            <option value="previous_assignee" {{ old('default_assignment_rule', $activeVersion->default_assignment_rule ?? '') == 'previous_assignee' ? 'selected' : '' }}>
                                Inherit Previous Period Assignee
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Auto-carryover Checkbox -->
                <div class="flex items-center pt-4">
                    <label class="flex items-center space-x-2 text-slate-700 cursor-pointer">
                        <input type="checkbox" name="auto_carryover" value="1" {{ $isViewOnly ? 'disabled' : '' }} {{ old('auto_carryover', $activeVersion->auto_carryover ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="font-semibold text-xs">
                            Auto-carryover incomplete tasks to next recurring period
                        </span>
                    </label>
                </div>

            </div>
        </div>

        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/50 space-y-4">

            <h3 class="font-bold text-slate-900 text-xs uppercase tracking-wider flex items-center space-x-2 border-b pb-2">
                <i class="fa-solid fa-bell text-slate-700"></i>
                <span>Notification &amp; Reminder Cadence Matrix</span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <!-- Internal Reminder Trigger (Days Before Due) with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">
                        Internal Reminder Trigger (Days Before Due)
                    </label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                The number of days prior to a due date when internal staff reminder notices are sent out.
                            </div>
                        </div>
                        <input type="number" name="internal_reminder_days" {{ $isViewOnly ? 'disabled' : '' }} min="1" value="{{ old('internal_reminder_days', $activeVersion->internal_reminder_days ?? 3) }}" class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                    </div>
                </div>

                <!-- Client Follow-up Cadence with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">
                        Client Follow-up Cadence
                    </label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                The automated frequency of follow-up notifications sent to clients regarding pending actions.
                            </div>
                        </div>
                        <select name="client_reminder_cadence" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                            <option value="" {{ empty(old('client_reminder_cadence', $activeVersion->client_reminder_cadence ?? '')) ? 'selected' : '' }}>
                                --Select client_reminder--
                            </option>
                            <option value="daily" {{ old('client_reminder_cadence', $activeVersion->client_reminder_cadence ?? '') == 'daily' ? 'selected' : '' }}>
                                Daily Automated Email
                            </option>
                            <option value="every_3_days" {{ old('client_reminder_cadence', $activeVersion->client_reminder_cadence ?? '') == 'every_3_days' ? 'selected' : '' }}>
                                Every 3 Days
                            </option>
                            <option value="weekly" {{ old('client_reminder_cadence', $activeVersion->client_reminder_cadence ?? '') == 'weekly' ? 'selected' : '' }}>
                                Weekly Summary
                            </option>
                            <option value="disabled" {{ old('client_reminder_cadence', $activeVersion->client_reminder_cadence ?? '') == 'disabled' ? 'selected' : '' }}>
                                Disabled / Manual Only
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Notification Channel with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">
                        Notification Channel
                    </label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                The preferred delivery channel (email, in-app dashboard, or both) used for system alerts.
                            </div>
                        </div>
                        <select name="notification_channel" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                            <option value="" {{ empty(old('notification_channel', $activeVersion->notification_channel ?? '')) ? 'selected' : '' }}>
                                --Select Notification_channel--
                            </option>
                            <option value="email_and_app" {{ old('notification_channel', $activeVersion->notification_channel ?? '') == 'email_and_app' ? 'selected' : '' }}>
                                Email &amp; In-App Dashboard Notification
                            </option>
                            <option value="app_only" {{ old('notification_channel', $activeVersion->notification_channel ?? '') == 'app_only' ? 'selected' : '' }}>
                                In-App Dashboard Only
                            </option>
                            <option value="email_only" {{ old('notification_channel', $activeVersion->notification_channel ?? '') == 'email_only' ? 'selected' : '' }}>
                                Email Only
                            </option>
                        </select>
                    </div>
                </div>

            </div>
        </div>

        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/50 space-y-4">

            <h3 class="font-bold text-slate-900 text-xs uppercase tracking-wider flex items-center space-x-2 border-b pb-2">
                <i class="fa-solid fa-triangle-exclamation text-slate-700"></i>
                <span>Escalation &amp; Requirement Dependency Controls</span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <!-- Overdue Escalation Threshold (Days) with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">
                        Overdue Escalation Threshold (Days)
                    </label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                The number of grace days allowed past a due date before official task escalation protocols are triggered.
                            </div>
                        </div>
                        <input type="number" name="escalation_threshold_days" {{ $isViewOnly ? 'disabled' : '' }} min="1" value="{{ old('escalation_threshold_days', $activeVersion->escalation_threshold_days ?? 2) }}" class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                    </div>
                    <span class="text-[10px] text-slate-400 italic mt-0.5 block">
                        Days overdue prior to system escalation.
                    </span>
                </div>

                <!-- Escalation Recipient Role with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">
                        Escalation Recipient Role
                    </label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                The designated management or leadership role responsible for reviewing escalated overdue tasks.
                            </div>
                        </div>
                        <select name="escalation_target_role" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                            <option value="" {{ empty(old('escalation_target_role', $activeVersion->escalation_target_role ?? '')) ? 'selected' : '' }}>
                                --Select Escalation--
                            </option>
                            <option value="engagement_manager" {{ old('escalation_target_role', $activeVersion->escalation_target_role ?? '') == 'engagement_manager' ? 'selected' : '' }}>
                                Engagement Manager
                            </option>
                            <option value="service_area_head" {{ old('escalation_target_role', $activeVersion->escalation_target_role ?? '') == 'service_area_head' ? 'selected' : '' }}>
                                Service Area Head
                            </option>
                            <option value="quality_reviewer" {{ old('escalation_target_role', $activeVersion->escalation_target_role ?? '') == 'quality_reviewer' ? 'selected' : '' }}>
                                Quality Reviewer
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Missing Requirements Gate Rule with Icon Inside Box (Left Side) -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">
                        Missing Requirements Gate Rule
                    </label>
                    <div class="relative flex items-center">
                        <div class="absolute left-2.5 group cursor-pointer inline-flex items-center z-10">
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none">
                                Controls whether workflow progression is strictly blocked or just warned if mandatory requirements remain unsubmitted.
                            </div>
                        </div>
                        <select name="requirement_gate_rule" {{ $isViewOnly ? 'disabled' : '' }} class="w-full border border-slate-300 rounded p-2 pl-8 outline-none bg-white text-slate-800 focus:border-blue-500">
                            <option value="" {{ empty(old('requirement_gate_rule', $activeVersion->requirement_gate_rule ?? '')) ? 'selected' : '' }}>
                                --Select Requirement gate rule--
                            </option>
                            <option value="block_execution" {{ old('requirement_gate_rule', $activeVersion->requirement_gate_rule ?? '') == 'block_execution' ? 'selected' : '' }}>
                                Strict Gate: Block Execution until Mandatory Uploaded
                            </option>
                            <option value="warn_only" {{ old('requirement_gate_rule', $activeVersion->requirement_gate_rule ?? '') == 'warn_only' ? 'selected' : '' }}>
                                Warning Only: Allow Execution with System Alert
                            </option>
                            <option value="none" {{ old('requirement_gate_rule', $activeVersion->requirement_gate_rule ?? '') == 'none' ? 'selected' : '' }}>
                                No Restriction
                            </option>
                        </select>
                    </div>
                </div>

            </div>
        </div>

        <div class="flex justify-between items-center pt-4 border-t">

            <button
                type="button"
                @click="changeTab('reporting')"
                class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer"
            >
                &larr; Back to Reporting
            </button>

            @if(!$isViewOnly)

                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded text-xs transition shadow-sm cursor-pointer inline-flex items-center space-x-1.5"
                >
                    <span>Save &amp; Continue to Terms</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </button>

            @else

                <button
                    type="button"
                    @click="changeTab('terms')"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded text-xs transition shadow-sm cursor-pointer inline-flex items-center space-x-1.5"
                >
                    <span>Next: Terms</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </button>

            @endif

        </div>

    </form>
</div>

<!-- TAB 11: VERSIONS & HISTORY -->
<div x-show="activeTab === 'versions_history'" x-cloak
     class="bg-white border border-slate-200 rounded-lg p-6 shadow-sm space-y-6">

    <div class="border-b pb-3">
        <h2 class="text-base font-bold text-slate-900">
            Versions &amp; Revision History
        </h2>
        <p class="text-xs text-slate-500 mt-0.5">
            Review the current version, submit it for approval, and review revision history.
        </p>
    </div>

    {{-- CURRENT VERSION --}}
    <div class="border border-slate-200 rounded-lg p-5 bg-slate-50">
        <div class="flex items-start justify-between gap-4">

            <div>
                <p class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">
                    Current Version
                </p>

                <h3 class="text-lg font-bold text-slate-900 mt-1">
                    {{ $activeVersion->version_number ?? 'V1.0' }}
                </h3>

                @if($activeVersion)
                    <p class="text-xs text-slate-500 mt-1">
                        Status:
                        <span class="font-semibold text-slate-700">
                            {{ ucfirst($activeVersion->status ?? 'Draft') }}
                        </span>
                    </p>
                @endif
            </div>

            {{-- SUBMIT FOR APPROVAL --}}
@if($activeVersion && in_array($activeVersion->status ?? 'draft', ['draft', 'rejected']))
    <form method="POST" action="{{ route('services.submit_approval', $service->id) }}">
        @csrf
        <button type="submit" disabled
                class="inline-flex items-center px-4 py-2 rounded-md bg-slate-300 text-slate-500 text-xs font-semibold cursor-not-allowed">
            Submit for Approval
        </button>
    </form>
@endif

        </div>
    </div>

    {{-- VERSION & AUDIT HISTORY --}}
    <div class="space-y-6">
        <div>
            <h3 class="text-sm font-bold text-slate-900">
                Version History
            </h3>
            <p class="text-xs text-slate-500">
                Previous versions and their status.
            </p>
        </div>

        @if(isset($versionHistory) && $versionHistory->count())
            <div class="border border-slate-200 rounded-lg overflow-hidden">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600">Version</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600">Status</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach($versionHistory as $version)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $version->version_number }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ ucfirst($version->status ?? 'Draft') }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ optional($version->created_at)->format('M d, Y h:i A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-6 text-center border border-dashed border-slate-300 rounded-lg">
                <p class="text-xs text-slate-400 italic">No previous historical versions found.</p>
            </div>
        @endif

        {{-- AUDIT LOGS SECTION --}}
        <div class="mt-6 pt-6 border-t border-slate-200">
            <h3 class="text-sm font-bold text-slate-900 mb-1">
                Audit History
            </h3>
            <p class="text-xs text-slate-500 mb-3">
                Track user actions and system events.
            </p>

            @if(isset($auditLogs) && $auditLogs->count())
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="text-left px-4 py-3 font-semibold text-slate-600">Action</th>
                                <th class="text-left px-4 py-3 font-semibold text-slate-600">Details</th>
                                <th class="text-left px-4 py-3 font-semibold text-slate-600">User</th>
                                <th class="text-left px-4 py-3 font-semibold text-slate-600">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @foreach($auditLogs as $log)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $log->action ?? $log->title }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $log->details ?? $log->description }}</td>
                                    <td class="px-4 py-3 text-slate-500">{{ $log->user_name ?? 'System User' }}</td>
                                    <td class="px-4 py-3 text-slate-500">{{ optional($log->created_at)->format('M d, Y h:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                   {{-- PAGINATION LINKS NA MAS MALIIT AT KULAY PUTI ANG BACKGROUND --}}
                   <style>
    /* Gawing puti ang background ng mga normal na buttons */
    .white-pagination nav a,
    .white-pagination nav span[aria-disabled="true"] {
        background-color: #ffffff !important;
        color: #475569 !important; /* Kulay gray na text */
        border-color: #e2e8f0 !important;
    }
    
    /* Ibang kulay para sa 'Active' o kasalukuyang page (hal. Page 1) */
    .white-pagination nav [aria-current="page"] span {
        background-color: #f1f5f9 !important; /* Light gray para madaling makita */
        color: #0f172a !important;
        font-weight: bold !important;
    }
</style>

@if(method_exists($auditLogs, 'hasPages') && $auditLogs->hasPages())
    <div class="px-3 py-2 bg-white border-t border-slate-200 white-pagination flex items-center justify-between">
        <div class="text-[10px] text-slate-500">
            Showing {{ $auditLogs->firstItem() }} to {{ $auditLogs->lastItem() }} of {{ $auditLogs->total() }} results
        </div>
        <div class="scale-75 origin-right">
            {{ $auditLogs->appends(['tab' => 'versions_history'])->links() }}
        </div>
    </div>

@endif
                </div>
            @else
                <div class="py-6 text-center border border-dashed border-slate-300 rounded-lg">
                    <p class="text-xs text-slate-400 italic">No audit logs found.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="flex justify-start pt-4 border-t">
        <button type="button"
                @click="changeTab('usage_performance')"
                class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer">
            &larr; Back to Usage &amp; Performance
        </button>
    </div>

</div>

 </main>
    </div>
</body>
</html>
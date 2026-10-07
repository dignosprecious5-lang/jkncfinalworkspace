@extends('layouts.app')
@section('title', 'Regular')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/project-list.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/overview-registry.css') }}">
@endpush

@section('content')
@php
    $phaseBadgeClasses = [
        'RSAT' => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
        'In Progress' => 'bg-blue-50 text-blue-700 border border-blue-200',
        'For NTP Approval' => 'bg-amber-50 text-amber-700 border border-amber-200',
        'Execution' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
        'Reporting' => 'bg-cyan-50 text-cyan-700 border border-cyan-200',
        'Delivery' => 'bg-violet-50 text-violet-700 border border-violet-200',
        'Completed' => 'bg-green-50 text-green-700 border border-green-200',
    ];
    $serviceAreaOptions = $serviceCatalog['serviceAreaOptions'] ?? [];
    $serviceGroups = $serviceCatalog['serviceGroups'] ?? [];
    $productOptionsByServiceArea = $productCatalog['productOptionsByServiceArea'] ?? [];
    $oldSourceMode = old('source_mode', 'manual');
    $selectedServiceAreas = collect(old('service_area_options', preg_split('/,\s*/', (string) old('service_area', '')) ?: []))
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
        ->map(fn ($value): string => trim((string) $value))
        ->values()
        ->all();
    $serviceAreaOtherEntries = collect(old('service_area_other', []))
        ->whenEmpty(function ($collection) use ($selectedServiceAreas) {
            return collect($selectedServiceAreas)
                ->filter(fn ($value): bool => \Illuminate\Support\Str::startsWith($value, 'Others: '))
                ->map(fn ($value): string => trim(\Illuminate\Support\Str::after($value, 'Others: ')))
                ->values();
        })
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
        ->map(fn ($value): string => trim((string) $value))
        ->values()
        ->all();
    $selectedServiceAreas = collect($selectedServiceAreas)
        ->reject(fn ($value): bool => \Illuminate\Support\Str::startsWith($value, 'Others: '))
        ->values()
        ->all();
    if ($serviceAreaOtherEntries !== [] && ! in_array('Others', $selectedServiceAreas, true)) {
        $selectedServiceAreas[] = 'Others';
    }
    $selectedServices = collect(old('service_options', preg_split('/,\s*/', (string) old('services', '')) ?: []))
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '' && ! \Illuminate\Support\Str::startsWith(trim((string) $value), 'Custom: '))
        ->map(fn ($value): string => trim((string) $value))
        ->values()
        ->all();
    $serviceCustomEntries = collect(old('services_other', []))
        ->whenEmpty(function () {
            return collect(preg_split('/,\s*/', (string) old('services', '')) ?: [])
                ->filter(fn ($value): bool => is_string($value) && \Illuminate\Support\Str::startsWith(trim((string) $value), 'Custom: '))
                ->map(fn ($value): string => trim(\Illuminate\Support\Str::after(trim((string) $value), 'Custom: ')))
                ->values();
        })
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
        ->map(fn ($value): string => trim((string) $value))
        ->values()
        ->all();
    $selectedProducts = collect(old('product_options', preg_split('/,\s*/', (string) old('products', '')) ?: []))
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '' && ! \Illuminate\Support\Str::startsWith(trim((string) $value), 'Custom: '))
        ->map(fn ($value): string => trim((string) $value))
        ->values()
        ->all();
    $productCustomEntries = collect(old('products_other_entries', []))
        ->whenEmpty(function () {
            return collect(preg_split('/,\s*/', (string) old('products', '')) ?: [])
                ->filter(fn ($value): bool => is_string($value) && \Illuminate\Support\Str::startsWith(trim((string) $value), 'Custom: '))
                ->map(fn ($value): string => trim(\Illuminate\Support\Str::after(trim((string) $value), 'Custom: ')))
                ->values();
        })
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
        ->map(fn ($value): string => trim((string) $value))
        ->values()
        ->all();
    if ($productCustomEntries !== [] && ! in_array('Others', $selectedProducts, true)) {
        $selectedProducts[] = 'Others';
    }
    $rsatTemplatePreviewData = $rsatTemplates->mapWithKeys(function ($template) {
        $payload = (array) ($template->payload ?? []);
        $requirements = collect($payload['engagement_requirements'] ?? [])
            ->filter(fn ($row) => is_array($row) && filled($row['requirement'] ?? null))
            ->map(fn ($row) => trim((string) ($row['requirement'] ?? '')))
            ->take(3)
            ->values()
            ->all();
        $approvalSteps = collect($payload['approval_steps'] ?? [])
            ->filter(fn ($row) => is_array($row) && filled($row['name'] ?? ($row['label'] ?? null)))
            ->count();
        $clearanceItems = collect($payload['clearance'] ?? [])
            ->filter(fn ($row) => is_array($row) && filled($row['label'] ?? ($row['name'] ?? null)))
            ->count();

        return [
            (string) $template->id => [
                'name' => (string) $template->name,
                'requirement_count' => count($requirements),
                'requirements' => $requirements,
                'approval_step_count' => $approvalSteps,
                'clearance_count' => $clearanceItems,
            ],
        ];
    })->all();
@endphp

<div class="px-6 py-6 lg:px-8">
    <div class="w-full">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">All Regular Services</h1>
                <p class="mt-1 max-w-3xl text-sm text-gray-500">
                    Central overview of every regular service from Work Order and RSAT through Review, NTP, Execution, RSAT Reporting, Delivery, transmittal, and formal completion.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="inline-flex h-10 items-center justify-center rounded-full border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 active:scale-95 transition cursor-pointer"
                    onclick="resetRegularFilters()"
                >
                    Reset Demo
                </button>
                <button
                    type="button"
                    class="inline-flex h-10 items-center justify-center rounded-full bg-[#102d79] px-6 text-sm font-semibold text-white shadow-sm hover:bg-[#0d255f] active:scale-95 transition cursor-pointer"
                    onclick="openCreateRegularModal()"
                >
                    Create Regular
                </button>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif
        @if (isset($errors) && $errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-semibold">Create Regular was not saved.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! empty($catalogWarnings))
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                @foreach ($catalogWarnings as $warning)
                    <p>{{ $warning }}</p>
                @endforeach
            </div>
        @endif

        {{-- RECORD STATUS TABS --}}
        <div class="registry-record-tabs mb-4" id="regularStatusTabs" aria-label="Filter regular services by record status">
            <button type="button" class="active" data-status-tab="Ongoing" onclick="filterRegularStatus('Ongoing', this)">
                Ongoing <strong>{{ $statusCounts['ongoing'] ?? 0 }}</strong>
            </button>
            <button type="button" data-status-tab="Completed" onclick="filterRegularStatus('Completed', this)">
                Completed <strong>{{ $statusCounts['completed'] ?? 0 }}</strong>
            </button>
            <button type="button" data-status-tab="Cancelled" onclick="filterRegularStatus('Cancelled', this)">
                Cancelled <strong>{{ $statusCounts['cancelled'] ?? 0 }}</strong>
            </button>
            <button type="button" data-status-tab="Deleted" onclick="filterRegularStatus('Deleted', this)">
                Deleted <strong>{{ $statusCounts['deleted'] ?? 0 }}</strong>
            </button>
        </div>

        {{-- 10 LIFECYCLE STAGE FILTER GRID --}}
        <div class="stage-filter-grid mb-4" id="stageFilters" aria-label="Filter regular services by lifecycle stage">
            <button type="button" class="stage-filter-card active" data-stage="all" onclick="filterRegularStage('all', this)">
                <span>ALL REGULAR SERVICES</span>
                <strong>{{ $stageCounts['all'] ?? 0 }}</strong>
                <small>Entire registry</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="Work Order" onclick="filterRegularStage('Work Order', this)">
                <span>WORK ORDER</span>
                <strong>{{ $stageCounts['work_order'] ?? 0 }}</strong>
                <small>Projects at this stage</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="RSAT" onclick="filterRegularStage('RSAT', this)">
                <span>RSAT</span>
                <strong>{{ $stageCounts['rsat'] ?? 0 }}</strong>
                <small>Projects at this stage</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="Review" onclick="filterRegularStage('Review', this)">
                <span>REVIEW</span>
                <strong>{{ $stageCounts['review'] ?? 0 }}</strong>
                <small>Projects at this stage</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="NTP" onclick="filterRegularStage('NTP', this)">
                <span>NTP</span>
                <strong>{{ $stageCounts['ntp'] ?? 0 }}</strong>
                <small>Projects at this stage</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="Execution" onclick="filterRegularStage('Execution', this)">
                <span>EXECUTION</span>
                <strong>{{ $stageCounts['execution'] ?? 0 }}</strong>
                <small>Projects at this stage</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="Reporting" onclick="filterRegularStage('Reporting', this)">
                <span>REPORTING</span>
                <strong>{{ $stageCounts['reporting'] ?? 0 }}</strong>
                <small>Projects at this stage</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="Presentation" onclick="filterRegularStage('Presentation', this)">
                <span>PRESENTATION</span>
                <strong>{{ $stageCounts['presentation'] ?? 0 }}</strong>
                <small>Projects at this stage</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="Delivery" onclick="filterRegularStage('Delivery', this)">
                <span>DELIVERY</span>
                <strong>{{ $stageCounts['delivery'] ?? 0 }}</strong>
                <small>Projects at this stage</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="Completion" onclick="filterRegularStage('Completion', this)">
                <span>COMPLETION</span>
                <strong>{{ $stageCounts['completion'] ?? 0 }}</strong>
                <small>Projects at this stage</small>
            </button>
        </div>

        {{-- REGULAR MANAGEMENT REGISTRY CARD --}}
        <div class="card rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden mb-6">
            <div class="card-head flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between p-5 border-b border-gray-100">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Regular Management Registry</h2>
                    <p class="mt-1 text-xs text-slate-500">Current ownership, lifecycle position, health, schedule, and completion status for each regular service.</p>
                </div>
                <div class="registry-head-actions flex items-center gap-2">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                        <input
                            id="q"
                            type="text"
                            placeholder="Search regular service, reference, business, or client..."
                            class="field h-10 w-72 sm:w-80 rounded-xl border border-slate-200 bg-slate-50/50 pl-8 pr-3 text-xs text-slate-900 outline-none focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-100 transition"
                            oninput="filterRegularRows()"
                        >
                    </div>
                    <div class="relative">
                        <button
                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900 shadow-sm transition cursor-pointer"
                            id="registryMenuButton"
                            type="button"
                            onclick="toggleRegistryDropdown()"
                            aria-label="Registry options"
                        >
                            <i class="fas fa-ellipsis-h text-sm"></i>
                        </button>
                        <div class="absolute right-0 top-12 z-50 hidden w-72 rounded-2xl border border-slate-100 bg-white p-4 shadow-xl text-left" id="registryMenu">
                            <button type="button" onclick="exportRegularTable('excel')" class="block w-full py-1.5 text-left text-xs font-semibold text-slate-800 hover:text-blue-700 transition cursor-pointer">
                                Download Excel
                            </button>
                            <button type="button" onclick="window.print()" class="block w-full py-1.5 text-left text-xs font-semibold text-slate-800 hover:text-blue-700 transition cursor-pointer">
                                Download / Print PDF
                            </button>
                            <div class="my-2.5 border-t border-slate-100"></div>
                            <div class="text-[11px] font-bold text-slate-500 mb-2.5">Visible Columns</div>
                            <div class="grid grid-cols-2 gap-x-3 gap-y-2.5">
                                <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer select-none">
                                    <input type="checkbox" checked class="h-4 w-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500 accent-blue-600" onchange="toggleRegistryColumn('project', this.checked)">
                                    <span>Regular Service</span>
                                </label>
                                <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer select-none">
                                    <input type="checkbox" checked class="h-4 w-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500 accent-blue-600" onchange="toggleRegistryColumn('business', this.checked)">
                                    <span>Business / Client</span>
                                </label>
                                <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer select-none">
                                    <input type="checkbox" checked class="h-4 w-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500 accent-blue-600" onchange="toggleRegistryColumn('stage', this.checked)">
                                    <span>Current Stage</span>
                                </label>
                                <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer select-none">
                                    <input type="checkbox" checked class="h-4 w-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500 accent-blue-600" onchange="toggleRegistryColumn('health', this.checked)">
                                    <span>Health</span>
                                </label>
                                <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer select-none">
                                    <input type="checkbox" checked class="h-4 w-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500 accent-blue-600" onchange="toggleRegistryColumn('progress', this.checked)">
                                    <span>Progress</span>
                                </label>
                                <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer select-none">
                                    <input type="checkbox" checked class="h-4 w-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500 accent-blue-600" onchange="toggleRegistryColumn('target', this.checked)">
                                    <span>Target Completion</span>
                                </label>
                                <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer select-none">
                                    <input type="checkbox" checked class="h-4 w-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500 accent-blue-600" onchange="toggleRegistryColumn('lead', this.checked)">
                                    <span>Regular Lead</span>
                                </label>
                                <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer select-none">
                                    <input type="checkbox" checked class="h-4 w-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500 accent-blue-600" onchange="toggleRegistryColumn('associate', this.checked)">
                                    <span>Lead Associate</span>
                                </label>
                                <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer select-none">
                                    <input type="checkbox" checked class="h-4 w-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500 accent-blue-600" onchange="toggleRegistryColumn('assigned', this.checked)">
                                    <span>Assigned Persons</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- FILTER TOOLBAR --}}
            <div class="registry-filters">
                <label>
                    From Date
                    <input type="date" id="filterFrom" onchange="filterRegularRows()">
                </label>
                <label>
                    To Date
                    <input type="date" id="filterTo" onchange="filterRegularRows()">
                </label>
                <label>
                    Current Stage
                    <select id="filterStage" onchange="filterRegularRows()">
                        <option value="">All stages</option>
                        <option value="Work Order">Work Order</option>
                        <option value="RSAT">RSAT</option>
                        <option value="Review">Review</option>
                        <option value="NTP">NTP</option>
                        <option value="Execution">Execution</option>
                        <option value="Reporting">Reporting</option>
                        <option value="Presentation">Presentation</option>
                        <option value="Delivery">Delivery</option>
                        <option value="Completion">Completion</option>
                    </select>
                </label>
                <label>
                    Health
                    <select id="filterHealth" onchange="filterRegularRows()">
                        <option value="">All health statuses</option>
                        <option value="On Track">On Track</option>
                        <option value="Needs Attention">Needs Attention</option>
                        <option value="At Risk">At Risk</option>
                        <option value="Completed">Completed</option>
                    </select>
                </label>
                <label>
                    Progress
                    <select id="filterProgress" onchange="filterRegularRows()">
                        <option value="">All progress</option>
                        <option value="not-started">0% Not started</option>
                        <option value="active">1%–99% In Progress</option>
                        <option value="completed">100% Completed</option>
                    </select>
                </label>
                <label>
                    Regular Lead
                    <select id="filterLead" onchange="filterRegularRows()">
                        <option value="">All regular leads</option>
                        @foreach ($employeeRecords as $emp)
                            <option value="{{ $emp['name'] ?? $emp['label'] ?? '' }}">{{ $emp['name'] ?? $emp['label'] ?? '' }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Lead Associate
                    <select id="filterAssociate" onchange="filterRegularRows()">
                        <option value="">All lead associates</option>
                        @foreach ($employeeRecords as $emp)
                            <option value="{{ $emp['name'] ?? $emp['label'] ?? '' }}">{{ $emp['name'] ?? $emp['label'] ?? '' }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Assigned Persons
                    <select id="filterAssigned" onchange="filterRegularRows()">
                        <option value="">All assigned persons</option>
                        @foreach ($employeeRecords as $emp)
                            <option value="{{ $emp['name'] ?? $emp['label'] ?? '' }}">{{ $emp['name'] ?? $emp['label'] ?? '' }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="registry-filters-clear-wrap">
                    <button class="inline-flex h-9 items-center justify-center rounded-full border border-slate-200 bg-white px-5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition" type="button" id="clearRegistryFilters" onclick="resetRegularFilters()">
                        Clear Filters
                    </button>
                </div>
            </div>

            {{-- RESULT BAR --}}
            {{-- RESULT BAR --}}
            <div class="flex items-center justify-between px-5 py-2.5 bg-white border-b border-slate-100 text-xs text-slate-500 font-normal">
                <span id="registryResultCount">{{ $regulars->count() }} regular service{{ $regulars->count() === 1 ? '' : 's' }}</span>
                <span id="activeRegistryFilter">All lifecycle stages</span>
            </div>

            {{-- REGISTRY TABLE --}}
            <div class="table-wrap w-full overflow-hidden">
                <table class="w-full text-xs" id="regularRegistryTable">
                    <thead class="bg-white text-slate-500 border-b border-slate-100">
                        <tr>
                            <th class="px-3 py-3.5 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[17%]" data-column="project">REGULAR / REFERENCE</th>
                            <th class="px-3 py-3.5 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[13%]" data-column="business">BUSINESS / CLIENT</th>
                            <th class="px-3 py-3.5 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[7%]" data-column="stage">CURRENT STAGE</th>
                            <th class="px-3 py-3.5 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[7%]" data-column="health">HEALTH</th>
                            <th class="px-3 py-3.5 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[6%]" data-column="progress">PROGRESS</th>
                            <th class="px-3 py-3.5 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[8%]" data-column="target">TARGET COMPLETION</th>
                            <th class="px-3 py-3.5 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[8%]" data-column="lead">REGULAR LEAD</th>
                            <th class="px-3 py-3.5 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[8%]" data-column="associate">LEAD ASSOCIATE</th>
                            <th class="px-3 py-3.5 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[18%]" data-column="assigned">ASSIGNED PERSONS</th>
                            <th class="px-3 py-3.5 text-center text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[8%]" data-column="action">ACTION</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700" id="rows">
                        @forelse ($regulars as $regular)
                            @php
                                $contactPerson = trim(collect([$regular->contact?->first_name, $regular->contact?->last_name])->filter()->implode(' ')) ?: ($regular->client_name ?: 'Client not recorded');
                                $businessName = $regular->company?->company_name ?: ($regular->business_name ?: 'No Business Recorded');
                                $isCompleted = in_array(strtolower($regular->status), ['completed', 'completion']) || in_array(strtolower($regular->current_phase ?? ''), ['completed', 'completion']);
                                $isCancelled = in_array(strtolower($regular->status), ['cancelled', 'cancel']);
                                $isDeleted = in_array(strtolower($regular->status), ['deleted', 'delete']);
                                $recordStatus = $isCompleted ? 'Completed' : ($isCancelled ? 'Cancelled' : ($isDeleted ? 'Deleted' : 'Ongoing'));
                                
                                $phaseLabel = $regular->real_stage ?? ($regular->current_phase ?: $regular->status);
                                $progress = $regular->real_progress ?? (data_get($regular->metadata, 'progress') ?? 0);
                                $health = $regular->real_health ?? (data_get($regular->metadata, 'health') ?? 'On Track');

                                $targetDate = $regular->target_completion_date ? \Carbon\Carbon::parse($regular->target_completion_date)->format('M d, Y') : 'Not set';
                                $targetDateRaw = $regular->target_completion_date ? \Carbon\Carbon::parse($regular->target_completion_date)->format('Y-m-d') : '';
                                
                                $regularLead = $regular->assigned_project_manager ?: ($regular->assigned_consultant ?: 'Unassigned');
                                $leadAssociate = $regular->assigned_associate ?: 'Unassigned';

                                $assignedList = collect([
                                    $regular->assigned_project_manager,
                                    $regular->assigned_consultant,
                                    $regular->assigned_associate,
                                    $regular->deal?->assigned_person,
                                    $regular->deal?->assigned_team_members,
                                    data_get($regular->metadata, 'assigned_persons'),
                                    data_get($regular->metadata, 'team_members'),
                                ])
                                ->flatMap(function($item) {
                                    if (is_array($item)) return $item;
                                    if (is_string($item)) {
                                        return preg_split('/[,;\n]+/', $item);
                                    }
                                    return [];
                                })
                                ->map(fn($n) => trim($n))
                                ->filter(fn($n) => !empty($n) && !in_array(strtolower($n), ['unassigned', 'null', '-']))
                                ->unique()
                                ->values();

                                if ($assignedList->isEmpty()) {
                                    $assignedList = collect([$regularLead !== 'Unassigned' ? $regularLead : ($leadAssociate !== 'Unassigned' ? $leadAssociate : 'Unassigned')]);
                                }

                                $badgeClass = match (strtolower($phaseLabel)) {
                                    'completed', 'completion' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-blue-50 text-blue-600 border-blue-200',
                                };
                            @endphp
                            <tr class="regular-data-row hover:bg-slate-50/80 transition"
                                data-id="{{ $regular->id }}"
                                data-status="{{ $recordStatus }}"
                                data-raw-status="{{ $regular->status }}"
                                data-title="{{ strtolower($regular->name) }}"
                                data-ref="{{ strtolower($regular->project_code) }}"
                                data-deal="{{ strtolower($regular->deal?->deal_code ?? '') }}"
                                data-business="{{ strtolower($businessName) }}"
                                data-client="{{ strtolower($contactPerson) }}"
                                data-stage="{{ $phaseLabel }}"
                                data-health="{{ $health }}"
                                data-progress="{{ $progress }}"
                                data-lead="{{ $regularLead }}"
                                data-associate="{{ $leadAssociate }}"
                                data-assigned="{{ strtolower($assignedList->implode(' ')) }}"
                                data-target="{{ $targetDateRaw }}">
                                <td class="px-3 py-3.5" data-column="project">
                                    <div class="font-bold text-slate-900 text-[13px] leading-snug">{{ $regular->name }}</div>
                                    <div class="text-[10px] text-slate-400 font-medium tracking-tight uppercase mt-0.5 whitespace-nowrap">{{ $regular->project_code }} · {{ $regular->deal?->deal_code ?? 'No Deal reference' }}</div>
                                </td>
                                <td class="px-3 py-3.5" data-column="business">
                                    <strong class="font-bold text-slate-900 text-[12px] leading-snug block uppercase">{{ $businessName }}</strong>
                                    <div class="text-[11px] text-slate-500 mt-0.5 whitespace-nowrap">{{ $contactPerson }}</div>
                                </td>
                                <td class="px-3 py-3.5 whitespace-nowrap" data-column="stage">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold border {{ $badgeClass }}">
                                        {{ $phaseLabel }}
                                    </span>
                                </td>
                                <td class="px-3 py-3.5 whitespace-nowrap" data-column="health">
                                    <span class="font-bold text-slate-800 text-[12px]">
                                        {{ $health }}
                                    </span>
                                </td>
                                <td class="px-3 py-3.5 whitespace-nowrap" data-column="progress">
                                    <div>
                                        <div class="font-bold text-slate-800 text-[12px] leading-none mb-1">
                                            {{ $progress }}%
                                        </div>
                                        <div class="h-1.5 w-16 sm:w-20 rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-full rounded-full bg-[#1b3b89]" style="width: {{ $progress }}%;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3.5 font-normal text-slate-600 text-[12px] whitespace-nowrap" data-column="target">
                                    {{ $targetDate }}
                                </td>
                                <td class="px-3 py-3.5 font-medium text-slate-700 text-[12px] leading-snug whitespace-nowrap" data-column="lead">
                                    {{ $regularLead }}
                                </td>
                                <td class="px-3 py-3.5 font-medium text-slate-700 text-[12px] leading-snug whitespace-nowrap" data-column="associate">
                                    {{ $leadAssociate }}
                                </td>
                                <td class="px-3 py-3.5" data-column="assigned">
                                    <div class="flex flex-wrap items-center gap-1">
                                        @foreach ($assignedList as $person)
                                            <span class="inline-flex items-center rounded-full bg-[#ebf1fa] px-2.5 py-0.5 text-[10px] font-medium text-[#334155] whitespace-nowrap">
                                                {{ $person }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-3 py-3.5 text-center whitespace-nowrap" data-column="action">
                                    <a href="{{ route('regular.show', $regular) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-[#1b3b89] px-2.5 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-[#152e6d] transition cursor-pointer">
                                        Open Regular
                                    </a>
                                    <div class="mt-1 flex items-center justify-center gap-1">
                                        <button type="button" onclick="openCancelRegularModal('{{ $regular->id }}', '{{ addslashes($regular->name) }}', '{{ $regular->regular_code }}')" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-2 py-0.5 text-[10.5px] font-medium text-slate-600 shadow-sm hover:bg-slate-50 transition cursor-pointer">
                                            Cancel
                                        </button>
                                        <button type="button" onclick="openSingleRegularDelete('{{ $regular->id }}', '{{ addslashes($regular->name) }}', '{{ $regular->regular_code }}')" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-2 py-0.5 text-[10.5px] font-medium text-red-600 shadow-sm hover:bg-rose-50 hover:border-red-200 transition cursor-pointer">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="noRecordsRow">
                                <td colspan="10" class="px-4 py-12 text-center text-sm text-slate-400">
                                    <i class="fas fa-folder-open text-3xl text-slate-300 block mb-2"></i>
                                    No regular engagements have been recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION FOOTER BAR --}}
            <div class="registry-pagination flex items-center justify-between p-4 border-t border-slate-100 bg-white">
                <div class="flex items-center gap-2 text-xs text-slate-500 font-bold uppercase">
                    <span>SHOW</span>
                    <select id="regularPageSize" class="h-8 rounded-lg border border-slate-200 px-2 text-xs text-slate-700 outline-none" onchange="changeRegularPageSize(this.value)">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div id="regularPaginationSummary" class="text-xs text-slate-500">
                    Showing 1–{{ min($regulars->count(), 10) }} of {{ $regulars->count() }}
                </div>
                <div class="registry-page-buttons flex items-center gap-1.5" id="regularPaginationButtons">
                    <button type="button" id="regularPrevBtn" class="px-3 py-1.5 text-xs text-slate-500 bg-white border border-slate-200 rounded-lg disabled:opacity-40" onclick="changeRegularPage('prev')">Previous</button>
                    <button type="button" class="px-3 py-1.5 text-xs font-bold text-white bg-[#102d79] rounded-lg active">1</button>
                    <button type="button" id="regularNextBtn" class="px-3 py-1.5 text-xs text-slate-500 bg-white border border-slate-200 rounded-lg disabled:opacity-40" onclick="changeRegularPage('next')">Next</button>
                </div>
            </div>
        </div>

        {{-- Cancel Regular Modal (Exact Screenshot Design) --}}
        <div id="regularCancelModal" class="fixed inset-0 z-[70] hidden" aria-hidden="true">
            <button id="regularCancelOverlay" type="button" aria-label="Close cancel regular modal" class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px] transition-opacity" onclick="closeRegularCancelModal()"></button>
            <div class="absolute left-1/2 top-1/2 w-full max-w-xl -translate-x-1/2 -translate-y-1/2 rounded-[28px] bg-white shadow-2xl overflow-hidden border border-slate-100">
                <form id="regularCancelForm" method="POST" action="">
                    @csrf
                    {{-- Modal Header --}}
                    <div class="p-7 pb-4">
                        <div class="text-[11px] font-black uppercase tracking-wider text-[#1e3a8a]">CANCEL REGULAR</div>
                        <div class="flex items-start justify-between mt-1">
                            <div>
                                <h2 class="text-2xl font-black tracking-tight text-[#0f2757]">Cancel regular record</h2>
                                <p class="mt-1 text-xs font-semibold text-slate-500">
                                    <span id="regularCancelRefText">REG-2026-120</span> · <span id="regularCancelNameText">Monthly Accounting Services</span>
                                </p>
                            </div>
                            <button type="button" onclick="closeRegularCancelModal()" class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition cursor-pointer">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Modal Body --}}
                    <div class="px-7 py-3">
                        <div class="flex items-center justify-between mb-2">
                            <label for="regularCancelReason" class="text-xs font-bold text-slate-800">Reason</label>
                            <span class="text-[10px] font-black tracking-wider text-rose-600 uppercase">REQUIRED</span>
                        </div>
                        <textarea
                            id="regularCancelReason"
                            name="reason"
                            required
                            rows="5"
                            class="w-full rounded-2xl border-2 border-blue-400/80 bg-white p-4 text-xs font-medium text-slate-800 placeholder-slate-400 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600 resize-y"
                            placeholder="Explain why this regular service is being cancelled..."
                        ></textarea>
                        <p class="mt-4 text-[11px] text-slate-400 font-normal leading-relaxed">
                            This action is retained with the acting user, date, time, and reason in the regular engagement history.
                        </p>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="border-t border-slate-100 px-7 py-4.5 bg-white flex items-center justify-end gap-3 mt-4">
                        <button type="button" onclick="closeRegularCancelModal()" class="h-11 rounded-full border border-slate-200 bg-white px-7 text-xs font-bold text-slate-600 shadow-sm hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="h-11 rounded-full bg-[#1b3b89] px-7 text-xs font-bold text-white shadow hover:bg-[#152e6d] transition cursor-pointer">
                            Cancel Regular
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Delete Regular Modal (Exact Screenshot Design) --}}
        <div id="regularDeleteSelectedModal" class="fixed inset-0 z-[70] hidden" aria-hidden="true">
            <button id="regularDeleteSelectedOverlay" type="button" aria-label="Close delete regular modal" class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px] transition-opacity" onclick="closeRegularDeleteModal()"></button>
            <div class="absolute left-1/2 top-1/2 w-full max-w-xl -translate-x-1/2 -translate-y-1/2 rounded-[28px] bg-white shadow-2xl overflow-hidden border border-slate-100">
                <form id="regularBulkDeleteForm" method="POST" action="{{ route('regular.bulk-delete') }}">
                    @csrf
                    @method('DELETE')
                    <div id="regularBulkDeleteSelectedItems"></div>

                    {{-- Modal Header --}}
                    <div class="p-7 pb-4">
                        <div class="text-[11px] font-black uppercase tracking-wider text-[#1e3a8a]">DELETE REGULAR</div>
                        <div class="flex items-start justify-between mt-1">
                            <div>
                                <h2 class="text-2xl font-black tracking-tight text-[#0f2757]">Delete regular record</h2>
                                <p class="mt-1 text-xs font-semibold text-slate-500">
                                    <span id="regularDeleteRefText">REG-2026-119</span> · <span id="regularDeleteNameText">Monthly Retainer Service</span>
                                </p>
                            </div>
                            <button type="button" onclick="closeRegularDeleteModal()" class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition cursor-pointer">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Modal Body --}}
                    <div class="px-7 py-3">
                        <div class="flex items-center justify-between mb-2">
                            <label for="regularDeleteReason" class="text-xs font-bold text-slate-800">Reason</label>
                            <span class="text-[10px] font-black tracking-wider text-rose-600 uppercase">REQUIRED</span>
                        </div>
                        <textarea
                            id="regularDeleteReason"
                            name="reason"
                            required
                            rows="5"
                            class="w-full rounded-2xl border-2 border-blue-400/80 bg-white p-4 text-xs font-medium text-slate-800 placeholder-slate-400 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600 resize-y"
                            placeholder="Explain why this regular service is being moved to Deleted..."
                        ></textarea>
                        <p class="mt-4 text-[11px] text-slate-400 font-normal leading-relaxed">
                            This action is retained with the acting user, date, time, and reason in the regular engagement history.
                        </p>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="border-t border-slate-100 px-7 py-4.5 bg-white flex items-center justify-end gap-3 mt-4">
                        <button type="button" onclick="closeRegularDeleteModal()" class="h-11 rounded-full border border-slate-200 bg-white px-7 text-xs font-bold text-slate-600 shadow-sm hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="h-11 rounded-full bg-[#b8323e] hover:bg-[#a12934] px-7 text-xs font-bold text-white shadow transition cursor-pointer">
                            Move to Deleted
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- CREATE REGULAR MODAL (Exact match to target UI/UX) --}}
<div id="createRegularModal" class="fixed inset-0 z-[70] hidden overflow-y-auto" aria-hidden="true">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="closeCreateRegularModal()"></div>
    <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
        <div class="relative w-full max-w-4xl transform rounded-3xl bg-white p-6 sm:p-8 text-left shadow-2xl transition-all border border-slate-100">
            {{-- Header --}}
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">Create Regular</h2>
                    <p class="mt-0.5 text-xs font-medium text-slate-500">How do you want to create this regular engagement?</p>
                </div>
                <button type="button" onclick="closeCreateRegularModal()" class="inline-flex items-center justify-center rounded-full border border-slate-300 px-4 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition">
                    Close
                </button>
            </div>

            <form id="createRegularForm" method="POST" action="{{ route('regular.manual.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="source_mode" id="cr_source_mode" value="deal">
                <input type="hidden" name="contact_id" id="cr_contact_id" value="">
                <input type="hidden" name="company_id" id="cr_company_id" value="">
                <input type="hidden" name="service_area" id="cr_service_area" value="">
                <input type="hidden" name="services" id="cr_services" value="">
                <input type="hidden" name="products" id="cr_products" value="">
                <input type="hidden" name="engagement_type" id="cr_engagement_type" value="Regular Retainer">

                {{-- Main submission hidden fields --}}
                <input type="hidden" name="name" id="cr_main_name" value="">
                <input type="hidden" name="client_name" id="cr_main_client_name" value="">
                <input type="hidden" name="business_name" id="cr_main_business_name" value="">
                <input type="hidden" name="assigned_project_manager" id="cr_main_assigned_project_manager" value="">
                <input type="hidden" name="assigned_associate" id="cr_main_assigned_associate" value="">
                <input type="hidden" name="target_completion_date" id="cr_main_target_completion_date" value="">
                <input type="hidden" name="planned_start_date" id="cr_main_planned_start_date" value="">

                {{-- 3 Creation Mode Selection Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <button type="button" id="crModeCardDeal" onclick="switchCrMode('deal')" class="rounded-2xl border-2 border-blue-600 bg-blue-50/20 p-4 text-left transition relative cursor-pointer">
                        <span class="block text-[13px] font-bold text-slate-900">Link Existing Deal</span>
                        <span class="mt-1 block text-[11px] text-slate-500 leading-snug">Preload its client, business, engagement, source references, staffing, and selected RSAT workstreams.</span>
                    </button>
                    <button type="button" id="crModeCardManual" onclick="switchCrMode('manual')" class="rounded-2xl border border-slate-200 bg-white p-4 text-left transition hover:border-slate-300 relative cursor-pointer">
                        <span class="block text-[13px] font-bold text-slate-900">Manual</span>
                        <span class="mt-1 block text-[11px] text-slate-500 leading-snug">Enter the regular engagement and client information manually.</span>
                    </button>
                    <button type="button" id="crModeCardDuplicate" onclick="switchCrMode('duplicate')" class="rounded-2xl border border-slate-200 bg-white p-4 text-left transition hover:border-slate-300 relative cursor-pointer">
                        <span class="block text-[13px] font-bold text-slate-900">Duplicate Existing Regular</span>
                        <span class="mt-1 block text-[11px] text-slate-500 leading-snug">Copy its planning structure without approvals, timers, reports, or completion evidence.</span>
                    </button>
                </div>

                {{-- MODE 1: Link Existing Deal --}}
                <div id="crPanelDeal" class="space-y-4 pt-2">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-3.5">
                        {{-- Row 1 --}}
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">EXISTING DEAL</label>
                            <select id="crDealSelect" name="deal_id" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600" onchange="onCrDealChange(this.value)">
                                <option value="">Select an existing deal...</option>
                                @foreach ($dealRecords as $deal)
                                    <option value="{{ $deal['id'] }}">{{ $deal['deal_code'] }} — {{ $deal['deal_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">REGULAR TITLE</label>
                            <input type="text" id="crRegularTitleDeal" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50/50 px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600" placeholder="Monthly Tax Compliance & Accounting Retainer">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">BUSINESS / COMPANY</label>
                            <input type="text" id="crBusinessNameDeal" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50/50 px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600" placeholder="X10 REAL ESTATE CORPORATION">
                        </div>

                        {{-- Row 2 --}}
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">CLIENT</label>
                            <input type="text" id="crClientNameDeal" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50/50 px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600" placeholder="May Flor D. Dabatos">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">EPA NO.</label>
                            <input type="text" id="crEpaNoDeal" readonly class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50/50 px-4 text-xs font-bold text-slate-800" placeholder="EPA-2026-065">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">REGULAR LEAD</label>
                            <input type="text" id="crRegularLeadDeal" list="regularEmployeeOptions" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50/50 px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600" placeholder="John Kelly Abalde">
                        </div>

                        {{-- Row 3 --}}
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">LEAD ASSOCIATE</label>
                            <input type="text" id="crLeadAssociateDeal" list="regularEmployeeOptions" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50/50 px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600" placeholder="Rubeca Potayre">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">TARGET COMPLETION</label>
                            <input type="text" id="crTargetCompletionDeal" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50/50 px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600" placeholder="Sep 18, 2026">
                        </div>
                        <div></div>
                    </div>

                    {{-- RSAT Workstreams Section --}}
                    <div id="crWorkstreamsSection" class="rounded-2xl border border-slate-200 overflow-hidden bg-white shadow-sm mt-3">
                        <div class="flex items-center justify-between bg-[#f0f4fb] px-6 py-3.5 border-b border-slate-200">
                            <span class="font-bold text-slate-900 text-xs tracking-wide">RSAT Workstreams</span>
                            <span class="text-[11px] text-slate-500 font-medium">Select workstreams to preload</span>
                        </div>
                        <div id="crWorkstreamsList" class="divide-y divide-slate-100 px-6 py-2 max-h-48 overflow-y-auto">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                </div>

                {{-- MODE 2: Manual --}}
                <div id="crPanelManual" class="space-y-4 pt-2 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-3.5">
                        {{-- Row 1 --}}
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">REGULAR TITLE</label>
                            <input type="text" id="crRegularTitleManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">BUSINESS / COMPANY</label>
                            <input type="text" id="crBusinessNameManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">CLIENT NAME</label>
                            <input type="text" id="crClientNameManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>

                        {{-- Row 2 --}}
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">CLIENT EMAIL</label>
                            <input type="email" id="crClientEmailManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">EPA NO.</label>
                            <input type="text" id="crEpaNoManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">REGULAR LEAD</label>
                            <input type="text" id="crRegularLeadManual" list="regularEmployeeOptions" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>

                        {{-- Row 3 --}}
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">LEAD ASSOCIATE</label>
                            <input type="text" id="crLeadAssociateManual" list="regularEmployeeOptions" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">TARGET START</label>
                            <input type="date" id="cpTargetStartManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">TARGET COMPLETION</label>
                            <input type="date" id="crTargetCompletionManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>

                        {{-- Row 4 --}}
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">SERVICE / PROJECT</label>
                            <input type="text" id="crServicesManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">SERVICE AREA</label>
                            <input type="text" id="crServiceAreaManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">ENGAGEMENT TYPE</label>
                            <select id="crEngagementTypeManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                                <option value="Regular Retainer" selected>Regular Retainer</option>
                                <option value="Hybrid Regular">Hybrid Regular</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- MODE 3: Duplicate Existing Regular --}}
                <div id="crPanelDuplicate" class="space-y-3 pt-2 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-3.5">
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">EXISTING REGULAR</label>
                            <select id="crDuplicateSelect" name="duplicate_regular_id" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600" onchange="onCrDuplicateChange(this.value)">
                                <option value="">Select regular to duplicate...</option>
                                @foreach ($regulars as $reg)
                                    <option value="{{ $reg->id }}">{{ $reg->project_code ?? $reg->id }} — {{ $reg->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">NEW REGULAR TITLE</label>
                            <input type="text" id="crRegularTitleDuplicate" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">NEW TARGET COMPLETION</label>
                            <input type="date" id="crTargetCompletionDuplicate" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-2">Only regular information, assignments, and workstream structure are copied. The new regular begins at Work Order.</p>
                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeCreateRegularModal()" class="inline-flex h-10 items-center justify-center rounded-full border border-slate-200 bg-white px-6 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-full bg-[#1b3b89] px-7 text-xs font-bold text-white shadow hover:bg-[#152e6d] transition">
                        Create Regular
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<datalist id="regularEmployeeOptions">
    @foreach (($employeeRecords ?? []) as $employee)
        <option value="{{ $employee['label'] }}">{{ $employee['position'] ?? '' }}{{ !empty($employee['employee_code']) ? ' - '.$employee['employee_code'] : '' }}</option>
    @endforeach
</datalist>

@php
    $crRegularRecordsData = [];
    if (!empty($regulars)) {
        foreach ($regulars as $r) {
            $crRegularRecordsData[] = [
                'id' => $r->id,
                'code' => $r->project_code ?? $r->id,
                'name' => $r->name,
                'business' => $r->business_name,
                'client' => $r->client_name,
                'lead' => $r->assigned_project_manager ?: $r->assigned_consultant,
                'associate' => $r->assigned_associate,
                'target' => $r->target_completion_date ? $r->target_completion_date->format('Y-m-d') : '',
                'services' => $r->services,
            ];
        }
    }
@endphp

<script>
    window.toggleRegistryDropdown = function() {
        const menu = document.getElementById('registryMenu');
        if (menu) {
            menu.classList.toggle('hidden');
        }
    };

    window.toggleRegistryColumn = function(colName, isVisible) {
        const table = document.getElementById('regularRegistryTable');
        if (!table) return;
        const cells = table.querySelectorAll(`[data-column="${colName}"]`);
        cells.forEach(el => {
            el.style.display = isVisible ? '' : 'none';
        });
    };

    window.exportRegularTable = function(type) {
        const table = document.getElementById('regularRegistryTable');
        if (!table) return;

        let csv = [];
        const rows = table.querySelectorAll('tr');
        rows.forEach(row => {
            if (row.style.display === 'none') return;
            const cols = row.querySelectorAll('th, td');
            let rowData = [];
            cols.forEach(col => {
                if (col.style.display === 'none' || col.getAttribute('data-column') === 'action') return;
                let text = col.innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/\s+/g, ' ').trim();
                rowData.push('"' + text.replace(/"/g, '""') + '"');
            });
            if (rowData.length > 0) csv.push(rowData.join(','));
        });

        const csvContent = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv.join('\n'));
        const link = document.createElement('a');
        link.setAttribute('href', csvContent);
        link.setAttribute('download', 'regular_registry_' + new Date().toISOString().slice(0, 10) + '.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    document.addEventListener('click', function(e) {
        const btn = document.getElementById('registryMenuButton');
        const menu = document.getElementById('registryMenu');
        if (btn && menu && !btn.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });

    const crDealRecords = {!! json_encode($dealRecords ?? []) !!};
    const crContactRecords = {!! json_encode($contactRecords ?? []) !!};
    const crCompanyRecords = {!! json_encode($companyRecords ?? []) !!};
    const crEmployeeRecords = {!! json_encode($employeeRecords ?? []) !!};
    const crRegularRecords = {!! json_encode($crRegularRecordsData) !!};

    window.openCreateRegularModal = function() {
        const modal = document.getElementById('createRegularModal');
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');

        const dealSelect = document.getElementById('crDealSelect');
        if (dealSelect && dealSelect.options.length > 1 && !dealSelect.value) {
            dealSelect.selectedIndex = 1;
            onCrDealChange(dealSelect.value);
        }
    };

    window.closeCreateRegularModal = function() {
        const modal = document.getElementById('createRegularModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
        }
    };

    window.switchCrMode = function(mode) {
        document.getElementById('cr_source_mode').value = mode;

        const cardDeal = document.getElementById('crModeCardDeal');
        const cardManual = document.getElementById('crModeCardManual');
        const cardDuplicate = document.getElementById('crModeCardDuplicate');

        const panelDeal = document.getElementById('crPanelDeal');
        const panelManual = document.getElementById('crPanelManual');
        const panelDuplicate = document.getElementById('crPanelDuplicate');

        const activeClasses = ['border-2', 'border-blue-600', 'bg-blue-50/20'];
        const inactiveClasses = ['border', 'border-slate-200', 'bg-white'];

        [cardDeal, cardManual, cardDuplicate].forEach(c => {
            if (c) {
                c.classList.remove(...activeClasses);
                c.classList.add(...inactiveClasses);
            }
        });

        panelDeal?.classList.add('hidden');
        panelManual?.classList.add('hidden');
        panelDuplicate?.classList.add('hidden');

        if (mode === 'deal') {
            cardDeal?.classList.remove(...inactiveClasses);
            cardDeal?.classList.add(...activeClasses);
            panelDeal?.classList.remove('hidden');
            const dealSelect = document.getElementById('crDealSelect');
            if (dealSelect?.value) {
                onCrDealChange(dealSelect.value);
            }
        } else if (mode === 'manual') {
            cardManual?.classList.remove(...inactiveClasses);
            cardManual?.classList.add(...activeClasses);
            panelManual?.classList.remove('hidden');
        } else if (mode === 'duplicate') {
            cardDuplicate?.classList.remove(...inactiveClasses);
            cardDuplicate?.classList.add(...activeClasses);
            panelDuplicate?.classList.remove('hidden');
            const dupSelect = document.getElementById('crDuplicateSelect');
            if (dupSelect?.value) {
                onCrDuplicateChange(dupSelect.value);
            }
        }
    };

    window.onCrDealChange = function(dealId) {
        if (!dealId) {
            renderCrWorkstreams([]);
            return;
        }
        const deal = crDealRecords.find(d => String(d.id) === String(dealId));
        if (!deal) return;

        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        };

        setVal('crRegularTitleDeal', deal.deal_name || '');
        setVal('crBusinessNameDeal', deal.business_name || '');
        setVal('crClientNameDeal', deal.client_name || '');
        setVal('crEpaNoDeal', deal.deal_code || '');
        setVal('crRegularLeadDeal', deal.assigned_consultant || deal.assigned_project_manager || '');
        setVal('crLeadAssociateDeal', deal.assigned_associate || '');

        let targetFormatted = 'Sep 18, 2026';
        if (deal.target_completion_date) {
            try {
                const d = new Date(deal.target_completion_date);
                if (!isNaN(d.getTime())) {
                    targetFormatted = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                }
            } catch (e) {}
        }
        setVal('crTargetCompletionDeal', targetFormatted);

        setVal('cr_contact_id', deal.contact_id || '');
        setVal('cr_company_id', deal.company_id || '');
        setVal('cr_service_area', deal.service_area || '');
        setVal('cr_services', deal.services || '');
        setVal('cr_products', deal.products || '');

        let workstreams = [];
        if (deal.services) {
            workstreams = deal.services.split(',').map(s => s.trim()).filter(Boolean);
        }
        if (workstreams.length === 0 && deal.service_area) {
            workstreams = [deal.service_area];
        }
        if (workstreams.length === 0) {
            workstreams = ['Share Transfer Documentation', 'BIR Share Transfer Processing'];
        }

        renderCrWorkstreams(workstreams);
    };

    window.onCrDuplicateChange = function(regId) {
        if (!regId) return;
        const reg = crRegularRecords.find(r => String(r.id) === String(regId));
        if (!reg) return;

        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        };

        setVal('crRegularTitleDuplicate', reg.name || '');
        setVal('crTargetCompletionDuplicate', reg.target || '');
    };

    function renderCrWorkstreams(workstreams) {
        const container = document.getElementById('crWorkstreamsList');
        if (!container) return;
        if (!workstreams || workstreams.length === 0) {
            container.innerHTML = `<div class="py-4 text-center text-xs text-slate-400">No workstreams available for this selection.</div>`;
            return;
        }

        container.innerHTML = workstreams.map((ws, idx) => {
            const taskCount = idx === 0 ? 5 : (idx === 1 ? 3 : 4);
            return `
                <label class="flex items-center justify-between py-3 px-3 cursor-pointer hover:bg-slate-50/80 rounded-xl transition">
                    <input type="checkbox" name="workstreams[]" value="${ws}" checked class="h-6 w-6 rounded-md text-blue-600 focus:ring-blue-500 border-slate-300 accent-blue-600">
                    <div class="text-right">
                        <div class="font-bold text-slate-900 text-xs">${ws}</div>
                        <div class="text-[11px] text-slate-500 font-medium">${taskCount} tasks</div>
                    </div>
                </label>
            `;
        }).join('');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('createRegularForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                const mode = document.getElementById('cr_source_mode').value;
                const setMain = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.value = val || '';
                };

                if (mode === 'deal') {
                    setMain('cr_main_name', document.getElementById('crRegularTitleDeal').value);
                    setMain('cr_main_business_name', document.getElementById('crBusinessNameDeal').value);
                    setMain('cr_main_client_name', document.getElementById('crClientNameDeal').value);
                    setMain('cr_main_assigned_project_manager', document.getElementById('crRegularLeadDeal').value);
                    setMain('cr_main_assigned_associate', document.getElementById('crLeadAssociateDeal').value);
                    
                    const deal = crDealRecords.find(d => String(d.id) === String(document.getElementById('crDealSelect').value));
                    setMain('cr_main_target_completion_date', deal?.target_completion_date || '');
                } else if (mode === 'manual') {
                    setMain('cr_main_name', document.getElementById('crRegularTitleManual').value);
                    setMain('cr_main_business_name', document.getElementById('crBusinessNameManual').value);
                    setMain('cr_main_client_name', document.getElementById('crClientNameManual').value);
                    setMain('cr_main_assigned_project_manager', document.getElementById('crRegularLeadManual').value);
                    setMain('cr_main_assigned_associate', document.getElementById('crLeadAssociateManual').value);
                    setMain('cr_main_planned_start_date', document.getElementById('cpTargetStartManual').value);
                    setMain('cr_main_target_completion_date', document.getElementById('crTargetCompletionManual').value);
                    setMain('cr_services', document.getElementById('crServicesManual').value);
                    setMain('cr_service_area', document.getElementById('crServiceAreaManual').value);
                    setMain('cr_engagement_type', document.getElementById('crEngagementTypeManual').value);
                } else if (mode === 'duplicate') {
                    setMain('cr_main_name', document.getElementById('crRegularTitleDuplicate').value);
                    setMain('cr_main_target_completion_date', document.getElementById('crTargetCompletionDuplicate').value);
                }
            });
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        const dealSelect = document.getElementById('crDealSelect');
        if (dealSelect && dealSelect.options.length > 1 && !dealSelect.value) {
            dealSelect.selectedIndex = 1;
            onCrDealChange(dealSelect.value);
        }
    });
</script>

<script>
    (() => {
        // ── Search debounce ──────────────────────────────────────────
        const regularsSearchForm  = document.getElementById('regularsSearchForm');
        const regularsSearchInput = document.getElementById('regularsSearchInput');
        let regularSearchDebounce = null;

        const submitRegularSearch = () => regularsSearchForm?.submit();

        regularsSearchInput?.addEventListener('input', () => {
            clearTimeout(regularSearchDebounce);
            regularSearchDebounce = setTimeout(submitRegularSearch, 700);
        });
        regularsSearchInput?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                clearTimeout(regularSearchDebounce);
                submitRegularSearch();
            }
        });

        // ── Checkbox / Delete Selected ───────────────────────────────
        const selectAllCb         = document.getElementById('regularSelectAll');
        const openDeleteBtn       = document.getElementById('openRegularDeleteSelectedModal');
        const deleteModal         = document.getElementById('regularDeleteSelectedModal');
        const deleteOverlay       = document.getElementById('regularDeleteSelectedOverlay');
        const cancelDeleteBtn     = document.getElementById('cancelRegularDeleteSelectedModal');
        const bulkDeleteItems     = document.getElementById('regularBulkDeleteSelectedItems');
        const bulkDeleteCountText = document.getElementById('regularBulkDeleteCountText');

        const rowCheckboxes = () => Array.from(document.querySelectorAll('.regular-row-checkbox'));
        const selectedIds   = () => rowCheckboxes().filter(cb => cb.checked).map(cb => cb.value);

        const syncDeleteButton = () => {
            const count = selectedIds().length;
            if (openDeleteBtn) {
                openDeleteBtn.classList.toggle('hidden', count === 0);
            }
            if (selectAllCb) {
                const all = rowCheckboxes();
                selectAllCb.indeterminate = count > 0 && count < all.length;
                selectAllCb.checked = all.length > 0 && count === all.length;
            }
        };

        const closeRegularDeleteModal = () => {
            deleteModal?.classList.add('hidden');
            deleteModal?.setAttribute('aria-hidden', 'true');
        };

        const openRegularDeleteModal = () => {
            const ids = selectedIds();
            if (ids.length === 0 || !deleteModal) return;
            if (bulkDeleteItems) {
                bulkDeleteItems.innerHTML = ids.map(id =>
                    `<input type="hidden" name="selected_regulars[]" value="${id}">`
                ).join('');
            }
            if (bulkDeleteCountText) {
                const label = ids.length === 1 ? 'regular engagement' : 'regular engagements';
                bulkDeleteCountText.textContent = `${ids.length} ${label}`;
            }
            deleteModal.classList.remove('hidden');
            deleteModal.setAttribute('aria-hidden', 'false');
        };

        selectAllCb?.addEventListener('change', () => {
            rowCheckboxes().forEach(cb => { cb.checked = selectAllCb.checked; });
            syncDeleteButton();
        });

        rowCheckboxes().forEach(cb => cb.addEventListener('change', syncDeleteButton));

        openDeleteBtn?.addEventListener('click', openRegularDeleteModal);
        cancelDeleteBtn?.addEventListener('click', closeRegularDeleteModal);
        deleteOverlay?.addEventListener('click', closeRegularDeleteModal);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeRegularDeleteModal(); });
    })();

    let currentSelectedRegularStatus = 'Ongoing';
    let currentSelectedStage = 'all';

    function updateStageCardCounts() {
        const rows = document.querySelectorAll('.regular-data-row');
        const counts = {
            'all': 0,
            'work order': 0,
            'rsat': 0,
            'review': 0,
            'ntp': 0,
            'execution': 0,
            'reporting': 0,
            'presentation': 0,
            'delivery': 0,
            'completion': 0
        };

        const stageMap = {
            'work order': ['work order', 'start', 'intake'],
            'rsat': ['rsat', 'rsat preparation', 'sow'],
            'review': ['review', 'internal review'],
            'ntp': ['ntp', 'for ntp approval'],
            'execution': ['execution', 'in progress'],
            'reporting': ['reporting', 'sow reporting', 'rsat reporting'],
            'presentation': ['presentation', 'client review'],
            'delivery': ['delivery', 'turn-over'],
            'completion': ['completion', 'completed']
        };

        rows.forEach(row => {
            const rowStatus = row.getAttribute('data-status') || 'Ongoing';
            if (currentSelectedRegularStatus && rowStatus.toLowerCase() !== currentSelectedRegularStatus.toLowerCase()) {
                return;
            }

            counts['all']++;

            const rowStage = (row.getAttribute('data-stage') || '').toLowerCase();
            const rawStatus = (row.getAttribute('data-raw-status') || '').toLowerCase();

            for (const [stageKey, aliases] of Object.entries(stageMap)) {
                if (aliases.includes(rowStage) || aliases.includes(rawStatus)) {
                    counts[stageKey]++;
                    break;
                }
            }
        });

        document.querySelectorAll('#stageFilters .stage-filter-card').forEach(card => {
            const stageKey = (card.getAttribute('data-stage') || 'all').toLowerCase();
            const strong = card.querySelector('strong');
            if (strong) {
                strong.textContent = counts[stageKey] !== undefined ? counts[stageKey] : 0;
            }
        });
    }

    window.filterRegularStatus = function(statusTab, btn) {
        currentSelectedRegularStatus = statusTab;
        document.querySelectorAll('#regularStatusTabs button').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');

        // Reset stage filter back to 'all' when switching status tab
        currentSelectedStage = 'all';
        document.querySelectorAll('#stageFilters .stage-filter-card').forEach(c => c.classList.remove('active'));
        document.querySelector('#stageFilters .stage-filter-card[data-stage="all"]')?.classList.add('active');
        const stageSelect = document.getElementById('filterStage');
        if (stageSelect) stageSelect.value = '';

        regularCurrentPage = 1;
        updateStageCardCounts();
        filterRegularRows();
    };

    window.filterRegularStage = function(stage, btn) {
        currentSelectedStage = stage;
        document.querySelectorAll('#stageFilters .stage-filter-card').forEach(c => c.classList.remove('active'));
        if (btn) btn.classList.add('active');
        
        const stageSelect = document.getElementById('filterStage');
        if (stageSelect) {
            stageSelect.value = stage === 'all' ? '' : stage;
        }
        regularCurrentPage = 1;
        filterRegularRows();
    };

    window.filterRegularRows = function() {
        updateStageCardCounts();
        const q = (document.getElementById('q')?.value || '').toLowerCase().trim();
        const fromDate = document.getElementById('filterFrom')?.value || '';
        const toDate = document.getElementById('filterTo')?.value || '';
        const stage = document.getElementById('filterStage')?.value || (currentSelectedStage === 'all' ? '' : currentSelectedStage);
        const health = document.getElementById('filterHealth')?.value || '';
        const progress = document.getElementById('filterProgress')?.value || '';
        const lead = document.getElementById('filterLead')?.value || '';
        const associate = document.getElementById('filterAssociate')?.value || '';

        const rows = document.querySelectorAll('.regular-data-row');
        let visibleCount = 0;

        const stageMap = {
            'work order': ['work order', 'start', 'intake'],
            'rsat': ['rsat', 'rsat preparation', 'sow'],
            'review': ['review', 'internal review'],
            'ntp': ['ntp', 'for ntp approval'],
            'execution': ['execution', 'in progress'],
            'reporting': ['reporting', 'sow reporting', 'rsat reporting'],
            'presentation': ['presentation', 'client review'],
            'delivery': ['delivery', 'turn-over'],
            'completion': ['completion', 'completed']
        };

        rows.forEach(row => {
            const title = row.getAttribute('data-title') || '';
            const ref = row.getAttribute('data-ref') || '';
            const deal = row.getAttribute('data-deal') || '';
            const business = row.getAttribute('data-business') || '';
            const client = row.getAttribute('data-client') || '';
            const rowStage = (row.getAttribute('data-stage') || '').toLowerCase();
            const rawStatus = (row.getAttribute('data-raw-status') || '').toLowerCase();
            const rowStatus = row.getAttribute('data-status') || 'Ongoing';
            const rowHealth = row.getAttribute('data-health') || '';
            const rowProgress = parseInt(row.getAttribute('data-progress') || '0', 10);
            const rowLead = row.getAttribute('data-lead') || '';
            const rowAssociate = row.getAttribute('data-associate') || '';
            const rowTarget = row.getAttribute('data-target') || '';

            let match = true;

            if (currentSelectedRegularStatus && rowStatus !== currentSelectedRegularStatus) {
                match = false;
            }

            if (q) {
                const combined = `${title} ${ref} ${deal} ${business} ${client}`.toLowerCase();
                if (!combined.includes(q)) match = false;
            }

            if (stage && stage !== 'all') {
                const searchStageKey = stage.toLowerCase();
                const validStages = stageMap[searchStageKey] || [searchStageKey];
                const matchesStage = validStages.some(s => s === rowStage || s === rawStatus);
                if (!matchesStage) match = false;
            }

            if (health && rowHealth !== health) match = false;

            if (progress) {
                if (progress === 'not-started' && rowProgress !== 0) match = false;
                if (progress === 'active' && (rowProgress <= 0 || rowProgress >= 100)) match = false;
                if (progress === 'completed' && rowProgress < 100) match = false;
            }

            const assigned = document.getElementById('filterAssigned')?.value || '';
            const rowAssigned = row.getAttribute('data-assigned') || '';

            if (assigned && !rowAssigned.toLowerCase().includes(assigned.toLowerCase())) match = false;
            if (lead && !rowLead.toLowerCase().includes(lead.toLowerCase())) match = false;
            if (associate && !rowAssociate.toLowerCase().includes(associate.toLowerCase())) match = false;

            if (fromDate && rowTarget && rowTarget < fromDate) match = false;
            if (toDate && rowTarget && rowTarget > toDate) match = false;

            if (match) {
                row.dataset.filtered = 'true';
                visibleCount++;
            } else {
                row.dataset.filtered = 'false';
                row.style.display = 'none';
            }
        });

        const noRecordsRow = document.getElementById('noRecordsRow');
        if (noRecordsRow) {
            noRecordsRow.style.display = visibleCount === 0 ? '' : 'none';
        }

        applyRegularPagination();

        const countEl = document.getElementById('registryResultCount');
        if (countEl) countEl.textContent = `${visibleCount} regular service${visibleCount === 1 ? '' : 's'}`;

        const filterEl = document.getElementById('activeRegistryFilter');
        if (filterEl) {
            filterEl.textContent = stage && stage !== 'all' ? `Filtered by stage: ${stage}` : (q ? `Filtered by keyword: "${q}"` : 'All lifecycle stages');
        }
    };

    let regularCurrentPage = 1;
    let regularPageSize = 10;

    function applyRegularPagination() {
        const rows = Array.from(document.querySelectorAll('.regular-data-row')).filter(r => r.dataset.filtered === 'true');
        const total = rows.length;
        const totalPages = Math.max(1, Math.ceil(total / regularPageSize));
        if (regularCurrentPage > totalPages) regularCurrentPage = totalPages;
        if (regularCurrentPage < 1) regularCurrentPage = 1;

        const startIdx = (regularCurrentPage - 1) * regularPageSize;
        const endIdx = startIdx + regularPageSize;

        rows.forEach((row, idx) => {
            if (idx >= startIdx && idx < endIdx) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        const summaryEl = document.getElementById('regularPaginationSummary');
        if (summaryEl) {
            if (total === 0) {
                summaryEl.textContent = 'Showing 0–0 of 0';
            } else {
                summaryEl.textContent = `Showing ${startIdx + 1}–${Math.min(endIdx, total)} of ${total}`;
            }
        }

        const prevBtn = document.getElementById('regularPrevBtn');
        const nextBtn = document.getElementById('regularNextBtn');
        if (prevBtn) prevBtn.disabled = regularCurrentPage <= 1;
        if (nextBtn) nextBtn.disabled = regularCurrentPage >= totalPages;
    }

    window.changeRegularPageSize = function(size) {
        regularPageSize = parseInt(size, 10) || 10;
        regularCurrentPage = 1;
        applyRegularPagination();
    };

    window.changeRegularPage = function(direction) {
        if (direction === 'prev') {
            regularCurrentPage = Math.max(1, regularCurrentPage - 1);
        } else if (direction === 'next') {
            regularCurrentPage++;
        }
        applyRegularPagination();
    };

    window.openCancelRegularModal = function(id, name, code) {
        const modal = document.getElementById('regularCancelModal');
        const form = document.getElementById('regularCancelForm');
        const nameText = document.getElementById('regularCancelNameText');
        const refText = document.getElementById('regularCancelRefText');
        const reasonInput = document.getElementById('regularCancelReason');
        if (!modal || !form) return;

        form.action = `/regular/${id}/cancel`;
        if (nameText) nameText.textContent = name;
        if (refText) refText.textContent = code || `REG-${id}`;
        if (reasonInput) {
            reasonInput.value = '';
            setTimeout(() => reasonInput.focus(), 100);
        }
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
    };

    window.closeRegularCancelModal = function() {
        const modal = document.getElementById('regularCancelModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
        }
    };

    window.openSingleRegularDelete = function(id, name, code) {
        const deleteModal = document.getElementById('regularDeleteSelectedModal');
        const bulkDeleteItems = document.getElementById('regularBulkDeleteSelectedItems');
        const nameText = document.getElementById('regularDeleteNameText');
        const refText = document.getElementById('regularDeleteRefText');
        const reasonInput = document.getElementById('regularDeleteReason');
        if (!deleteModal) return;

        if (bulkDeleteItems) {
            bulkDeleteItems.innerHTML = `<input type="hidden" name="selected_regulars[]" value="${id}">`;
        }
        if (nameText) nameText.textContent = name;
        if (refText) refText.textContent = code || `REG-${id}`;
        if (reasonInput) {
            reasonInput.value = '';
            setTimeout(() => reasonInput.focus(), 100);
        }
        deleteModal.classList.remove('hidden');
        deleteModal.setAttribute('aria-hidden', 'false');
    };

    window.closeRegularDeleteModal = function() {
        const deleteModal = document.getElementById('regularDeleteSelectedModal');
        if (deleteModal) {
            deleteModal.classList.add('hidden');
            deleteModal.setAttribute('aria-hidden', 'true');
        }
    };

    window.resetRegularFilters = function() {
        if (window.location.search) {
            window.location.href = window.location.pathname;
            return;
        }

        if (document.getElementById('q')) document.getElementById('q').value = '';
        if (document.getElementById('filterFrom')) document.getElementById('filterFrom').value = '';
        if (document.getElementById('filterTo')) document.getElementById('filterTo').value = '';
        if (document.getElementById('filterStage')) document.getElementById('filterStage').value = '';
        if (document.getElementById('filterHealth')) document.getElementById('filterHealth').value = '';
        if (document.getElementById('filterProgress')) document.getElementById('filterProgress').value = '';
        if (document.getElementById('filterLead')) document.getElementById('filterLead').value = '';
        if (document.getElementById('filterAssociate')) document.getElementById('filterAssociate').value = '';
        if (document.getElementById('filterAssigned')) document.getElementById('filterAssigned').value = '';
        
        currentSelectedRegularStatus = 'Ongoing';
        document.querySelectorAll('#regularStatusTabs button').forEach(b => b.classList.remove('active'));
        document.querySelector('#regularStatusTabs button[data-status-tab="Ongoing"]')?.classList.add('active');

        currentSelectedStage = 'all';
        document.querySelectorAll('.stage-filter-card').forEach(c => c.classList.remove('active'));
        document.querySelector('.stage-filter-card[data-stage="all"]')?.classList.add('active');

        regularCurrentPage = 1;
        filterRegularRows();
    };

    window.exportRegularTable = function(format) {
        const rows = document.querySelectorAll('.regular-data-row');
        let csv = 'REGULAR,REFERENCE,BUSINESS,CLIENT,STAGE,HEALTH,PROGRESS,TARGET,LEAD\n';
        rows.forEach(r => {
            if (r.dataset.filtered === 'true') {
                const title = `"${(r.getAttribute('data-title') || '').replace(/"/g, '""')}"`;
                const ref = `"${(r.getAttribute('data-ref') || '').replace(/"/g, '""')}"`;
                const business = `"${(r.getAttribute('data-business') || '').replace(/"/g, '""')}"`;
                const client = `"${(r.getAttribute('data-client') || '').replace(/"/g, '""')}"`;
                const stage = `"${(r.getAttribute('data-stage') || '').replace(/"/g, '""')}"`;
                const health = `"${(r.getAttribute('data-health') || '').replace(/"/g, '""')}"`;
                const progress = `${r.getAttribute('data-progress') || '0'}%`;
                const target = `"${(r.getAttribute('data-target') || '').replace(/"/g, '""')}"`;
                const lead = `"${(r.getAttribute('data-lead') || '').replace(/"/g, '""')}"`;
                csv += `${title},${ref},${business},${client},${stage},${health},${progress},${target},${lead}\n`;
            }
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.setAttribute('download', `regular_registry_${new Date().toISOString().slice(0, 10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    document.addEventListener('DOMContentLoaded', () => {
        filterRegularRows();
    });
</script>
@endsection

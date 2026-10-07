@extends('layouts.app')
@section('title', 'Project')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/overview-registry.css') }}">
@endpush

@section('content')
@php
    $phaseBadgeClasses = [
        'SOW' => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
        'Start' => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
        'In Progress' => 'bg-blue-50 text-blue-700 border border-blue-200',
        'For NTP Approval' => 'bg-amber-50 text-amber-700 border border-amber-200',
        'Execution' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
        'Reporting' => 'bg-cyan-50 text-cyan-700 border border-cyan-200',
        'Delivery' => 'bg-violet-50 text-violet-700 border border-violet-200',
        'Completed' => 'bg-green-50 text-green-700 border border-green-200',
    ];
    $oldSourceMode = old('source_mode', 'manual');
    $selectedServiceAreas = collect(old('service_area_options', preg_split('/,\s*/', (string) old('service_area', '')) ?: []))
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
        ->map(fn ($value): string => trim((string) $value))
        ->values()
        ->all();
    $serviceAreaOtherEntries = collect(old('service_area_other', []))
        ->whenEmpty(function ($collection) use ($selectedServiceAreas) {
            return collect($selectedServiceAreas)
                ->filter(fn ($value): bool => Str::startsWith($value, 'Others: '))
                ->map(fn ($value): string => trim(Str::after($value, 'Others: ')))
                ->values();
        })
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
        ->map(fn ($value): string => trim((string) $value))
        ->values()
        ->all();
    $selectedServiceAreas = collect($selectedServiceAreas)
        ->reject(fn ($value): bool => Str::startsWith($value, 'Others: '))
        ->values()
        ->all();
    if ($serviceAreaOtherEntries !== [] && ! in_array('Others', $selectedServiceAreas, true)) {
        $selectedServiceAreas[] = 'Others';
    }
    $selectedServices = collect(old('service_options', preg_split('/,\s*/', (string) old('services', '')) ?: []))
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '' && ! Str::startsWith(trim((string) $value), 'Custom: '))
        ->map(fn ($value): string => trim((string) $value))
        ->values()
        ->all();
    $serviceCustomEntries = collect(old('services_other', []))
        ->whenEmpty(function () {
            return collect(preg_split('/,\s*/', (string) old('services', '')) ?: [])
                ->filter(fn ($value): bool => is_string($value) && Str::startsWith(trim((string) $value), 'Custom: '))
                ->map(fn ($value): string => trim(Str::after(trim((string) $value), 'Custom: ')))
                ->values();
        })
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
        ->map(fn ($value): string => trim((string) $value))
        ->values()
        ->all();
    $selectedProducts = collect(old('product_options', preg_split('/,\s*/', (string) old('products', '')) ?: []))
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '' && ! Str::startsWith(trim((string) $value), 'Custom: '))
        ->map(fn ($value): string => trim((string) $value))
        ->values()
        ->all();
    $productCustomEntries = collect(old('products_other_entries', []))
        ->whenEmpty(function () {
            return collect(preg_split('/,\s*/', (string) old('products', '')) ?: [])
                ->filter(fn ($value): bool => is_string($value) && Str::startsWith(trim((string) $value), 'Custom: '))
                ->map(fn ($value): string => trim(Str::after(trim((string) $value), 'Custom: ')))
                ->values();
        })
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
        ->map(fn ($value): string => trim((string) $value))
        ->values()
        ->all();
    if ($productCustomEntries !== [] && ! in_array('Others', $selectedProducts, true)) {
        $selectedProducts[] = 'Others';
    }
    $sowTemplatePreviewData = $sowTemplates->mapWithKeys(function ($template) {
        $payload = (array) ($template->payload ?? []);
        $withinScope = collect($payload['within_scope_items'] ?? [])
            ->filter(fn ($row) => filled($row['main_task_description'] ?? null))
            ->map(function ($row) {
                $main = trim((string) ($row['main_task_description'] ?? ''));
                $sub = trim((string) ($row['sub_task_description'] ?? ''));

                return $sub !== '' ? $main.' - '.$sub : $main;
            })
            ->take(3)
            ->values()
            ->all();
        $outOfScopeCount = collect($payload['out_of_scope_items'] ?? [])
            ->filter(fn ($row) => filled($row['main_task_description'] ?? null))
            ->count();

        return [
            (string) $template->id => [
                'name' => (string) $template->name,
                'version' => (string) ($payload['version_number'] ?? '1.0'),
                'approval_status' => (string) ($payload['approval_status'] ?? 'draft'),
                'ntp_status' => (string) ($payload['ntp_status'] ?? 'pending'),
                'within_scope_count' => count($withinScope),
                'within_scope_items' => $withinScope,
                'out_of_scope_count' => $outOfScopeCount,
            ],
        ];
    })->all();
@endphp

<div class="px-6 py-6 lg:px-8">
    <div class="w-full">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-gray-900">All Projects</h1>
                <p class="mt-1 max-w-3xl text-sm text-gray-500">
                    Central overview of every project from Work Order and SOW through Review, NTP, Execution, SOW Reporting, Delivery, transmittal, and formal completion.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="inline-flex h-10 items-center justify-center rounded-full border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 active:scale-95 transition cursor-pointer"
                    onclick="resetProjectFilters()"
                >
                    Reset Demo
                </button>
                <button
                    type="button"
                    class="inline-flex h-10 items-center justify-center rounded-full bg-[#102d79] px-6 text-sm font-semibold text-white shadow-sm hover:bg-[#0d255f] active:scale-95 transition cursor-pointer"
                    onclick="openCreateProjectModal()"
                >
                    Create Project
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
                <p class="font-semibold">Create Project was not saved.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! empty($catalogWarnings ?? []))
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                @foreach ($catalogWarnings as $warning)
                    <p>{{ $warning }}</p>
                @endforeach
            </div>
        @endif

        {{-- RECORD STATUS TABS --}}
        <div class="registry-record-tabs mb-4" id="projectStatusTabs" aria-label="Filter projects by record status">
            <button type="button" class="active" data-status-tab="Ongoing" onclick="filterProjectStatus('Ongoing', this)">
                Ongoing <strong>{{ $statusCounts['ongoing'] ?? 0 }}</strong>
            </button>
            <button type="button" data-status-tab="Completed" onclick="filterProjectStatus('Completed', this)">
                Completed <strong>{{ $statusCounts['completed'] ?? 0 }}</strong>
            </button>
            <button type="button" data-status-tab="Cancelled" onclick="filterProjectStatus('Cancelled', this)">
                Cancelled <strong>{{ $statusCounts['cancelled'] ?? 0 }}</strong>
            </button>
            <button type="button" data-status-tab="Deleted" onclick="filterProjectStatus('Deleted', this)">
                Deleted <strong>{{ $statusCounts['deleted'] ?? 0 }}</strong>
            </button>
        </div>

        {{-- 10 LIFECYCLE STAGE FILTER GRID --}}
        <div class="stage-filter-grid mb-4" id="stageFilters" aria-label="Filter projects by lifecycle stage">
            <button type="button" class="stage-filter-card active" data-stage="all" onclick="filterProjectStage('all', this)">
                <span>ALL PROJECTS</span>
                <strong>{{ $stageCounts['all'] ?? 0 }}</strong>
                <small>Entire registry</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="Work Order" onclick="filterProjectStage('Work Order', this)">
                <span>WORK ORDER</span>
                <strong>{{ $stageCounts['work_order'] ?? 0 }}</strong>
                <small>Projects at this stage</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="SOW" onclick="filterProjectStage('SOW', this)">
                <span>SOW</span>
                <strong>{{ $stageCounts['sow'] ?? 0 }}</strong>
                <small>Projects at this stage</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="Review" onclick="filterProjectStage('Review', this)">
                <span>REVIEW</span>
                <strong>{{ $stageCounts['review'] ?? 0 }}</strong>
                <small>Projects at this stage</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="NTP" onclick="filterProjectStage('NTP', this)">
                <span>NTP</span>
                <strong>{{ $stageCounts['ntp'] ?? 0 }}</strong>
                <small>Projects at this stage</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="Execution" onclick="filterProjectStage('Execution', this)">
                <span>EXECUTION</span>
                <strong>{{ $stageCounts['execution'] ?? 0 }}</strong>
                <small>Projects at this stage</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="Reporting" onclick="filterProjectStage('Reporting', this)">
                <span>REPORTING</span>
                <strong>{{ $stageCounts['reporting'] ?? 0 }}</strong>
                <small>Projects at this stage</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="Presentation" onclick="filterProjectStage('Presentation', this)">
                <span>PRESENTATION</span>
                <strong>{{ $stageCounts['presentation'] ?? 0 }}</strong>
                <small>Projects at this stage</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="Delivery" onclick="filterProjectStage('Delivery', this)">
                <span>DELIVERY</span>
                <strong>{{ $stageCounts['delivery'] ?? 0 }}</strong>
                <small>Projects at this stage</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="Completion" onclick="filterProjectStage('Completion', this)">
                <span>COMPLETION</span>
                <strong>{{ $stageCounts['completion'] ?? 0 }}</strong>
                <small>Projects at this stage</small>
            </button>
        </div>

        {{-- PROJECT MANAGEMENT REGISTRY CARD --}}
        <div class="card rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden mb-6">
            <div class="card-head flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between p-5 border-b border-gray-100">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Project Management Registry</h2>
                    <p class="mt-1 text-xs text-slate-500">Current ownership, lifecycle position, health, schedule, and completion status for each project engagement.</p>
                </div>
                <div class="registry-head-actions flex items-center gap-2">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                        <input
                            id="q"
                            type="text"
                            placeholder="Search project title, reference, business, or client..."
                            class="field h-10 w-72 sm:w-80 rounded-xl border border-slate-200 bg-slate-50/50 pl-8 pr-3 text-xs text-slate-900 outline-none focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-100 transition"
                            oninput="filterProjectRows()"
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
                            <button type="button" onclick="exportProjectTable('excel')" class="block w-full py-1.5 text-left text-xs font-semibold text-slate-800 hover:text-blue-700 transition cursor-pointer">
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
                                    <span>Project</span>
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
                                    <span>Project Lead</span>
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
                    <input type="date" id="filterFrom" onchange="filterProjectRows()">
                </label>
                <label>
                    To Date
                    <input type="date" id="filterTo" onchange="filterProjectRows()">
                </label>
                <label>
                    Current Stage
                    <select id="filterStage" onchange="filterProjectRows()">
                        <option value="">All stages</option>
                        <option value="Work Order">Work Order</option>
                        <option value="SOW">SOW</option>
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
                    <select id="filterHealth" onchange="filterProjectRows()">
                        <option value="">All health statuses</option>
                        <option value="On Track">On Track</option>
                        <option value="Needs Attention">Needs Attention</option>
                        <option value="At Risk">At Risk</option>
                        <option value="Completed">Completed</option>
                    </select>
                </label>
                <label>
                    Progress
                    <select id="filterProgress" onchange="filterProjectRows()">
                        <option value="">All progress</option>
                        <option value="not-started">0% Not started</option>
                        <option value="active">1%–99% In Progress</option>
                        <option value="completed">100% Completed</option>
                    </select>
                </label>
                <label>
                    Project Lead
                    <select id="filterLead" onchange="filterProjectRows()">
                        <option value="">All project leads</option>
                        @foreach ($employeeRecords as $emp)
                            <option value="{{ $emp['name'] ?? $emp['label'] ?? '' }}">{{ $emp['name'] ?? $emp['label'] ?? '' }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Lead Associate
                    <select id="filterAssociate" onchange="filterProjectRows()">
                        <option value="">All lead associates</option>
                        @foreach ($employeeRecords as $emp)
                            <option value="{{ $emp['name'] ?? $emp['label'] ?? '' }}">{{ $emp['name'] ?? $emp['label'] ?? '' }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Assigned Persons
                    <select id="filterAssigned" onchange="filterProjectRows()">
                        <option value="">All assigned persons</option>
                        @foreach ($employeeRecords as $emp)
                            <option value="{{ $emp['name'] ?? $emp['label'] ?? '' }}">{{ $emp['name'] ?? $emp['label'] ?? '' }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="registry-filters-clear-wrap">
                    <button class="inline-flex h-9 items-center justify-center rounded-full border border-slate-200 bg-white px-5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition" type="button" id="clearRegistryFilters" onclick="resetProjectFilters()">
                        Clear Filters
                    </button>
                </div>
            </div>

            {{-- RESULT BAR --}}
            <div class="flex items-center justify-between px-5 py-2.5 bg-white border-b border-slate-100 text-xs text-slate-500 font-normal">
                <span id="registryResultCount">{{ $projects->count() }} project{{ $projects->count() === 1 ? '' : 's' }}</span>
                <span id="activeRegistryFilter">All lifecycle stages</span>
            </div>

            {{-- REGISTRY TABLE --}}
            <div class="table-wrap w-full overflow-hidden">
                <table class="w-full text-xs" id="projectRegistryTable">
                    <thead class="bg-white text-slate-500 border-b border-slate-100">
                        <tr>
                            <th class="px-3 py-3.5 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[17%]" data-column="project">PROJECT / REFERENCE</th>
                            <th class="px-3 py-3.5 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[13%]" data-column="business">BUSINESS / CLIENT</th>
                            <th class="px-3 py-3.5 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[7%]" data-column="stage">CURRENT STAGE</th>
                            <th class="px-3 py-3.5 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[7%]" data-column="health">HEALTH</th>
                            <th class="px-3 py-3.5 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[6%]" data-column="progress">PROGRESS</th>
                            <th class="px-3 py-3.5 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[8%]" data-column="target">TARGET COMPLETION</th>
                            <th class="px-3 py-3.5 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[8%]" data-column="lead">PROJECT LEAD</th>
                            <th class="px-3 py-3.5 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[8%]" data-column="associate">LEAD ASSOCIATE</th>
                            <th class="px-3 py-3.5 text-left text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[18%]" data-column="assigned">ASSIGNED PERSONS</th>
                            <th class="px-3 py-3.5 text-center text-[10.5px] font-bold uppercase tracking-wider text-slate-500 w-[8%]" data-column="action">ACTION</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700" id="rows">
                        @forelse ($projects as $project)
                            @php
                                $contactPerson = trim(collect([$project->contact?->first_name, $project->contact?->last_name])->filter()->implode(' ')) ?: ($project->client_name ?: 'Client not recorded');
                                $businessName = $project->company?->company_name ?: ($project->business_name ?: 'No Business Recorded');
                                $isCompleted = in_array(strtolower($project->status), ['completed', 'completion']) || in_array(strtolower($project->current_phase ?? ''), ['completed', 'completion']);
                                $isCancelled = in_array(strtolower($project->status), ['cancelled', 'cancel']);
                                $isDeleted = in_array(strtolower($project->status), ['deleted', 'delete']);
                                $recordStatus = $isCompleted ? 'Completed' : ($isCancelled ? 'Cancelled' : ($isDeleted ? 'Deleted' : 'Ongoing'));
                                
                                $phaseLabel = $project->real_stage ?? ($project->current_phase ?: $project->status);
                                $progress = $project->real_progress ?? (data_get($project->metadata, 'progress') ?? 0);
                                $health = $project->real_health ?? (data_get($project->metadata, 'health') ?? 'On Track');

                                $targetDate = $project->target_completion_date ? \Carbon\Carbon::parse($project->target_completion_date)->format('M d, Y') : 'Not set';
                                $targetDateRaw = $project->target_completion_date ? \Carbon\Carbon::parse($project->target_completion_date)->format('Y-m-d') : '';
                                
                                $projectLead = $project->assigned_project_manager ?: ($project->assigned_consultant ?: 'Unassigned');
                                $leadAssociate = $project->assigned_associate ?: 'Unassigned';

                                $assignedList = collect([
                                    $project->assigned_project_manager,
                                    $project->assigned_consultant,
                                    $project->assigned_associate,
                                    $project->deal?->assigned_person,
                                    $project->deal?->assigned_team_members,
                                    data_get($project->metadata, 'assigned_persons'),
                                    data_get($project->metadata, 'team_members'),
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
                                    $assignedList = collect([$projectLead !== 'Unassigned' ? $projectLead : ($leadAssociate !== 'Unassigned' ? $leadAssociate : 'Unassigned')]);
                                }

                                $badgeClass = match (strtolower($phaseLabel)) {
                                    'completed', 'completion' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-blue-50 text-blue-600 border-blue-200',
                                };
                            @endphp
                            <tr class="project-data-row hover:bg-slate-50/80 transition"
                                data-id="{{ $project->id }}"
                                data-status="{{ $recordStatus }}"
                                data-raw-status="{{ $project->status }}"
                                data-title="{{ strtolower($project->name) }}"
                                data-ref="{{ strtolower($project->project_code) }}"
                                data-deal="{{ strtolower($project->deal?->deal_code ?? '') }}"
                                data-business="{{ strtolower($businessName) }}"
                                data-client="{{ strtolower($contactPerson) }}"
                                data-stage="{{ $phaseLabel }}"
                                data-health="{{ $health }}"
                                data-progress="{{ $progress }}"
                                data-lead="{{ $projectLead }}"
                                data-associate="{{ $leadAssociate }}"
                                data-assigned="{{ strtolower($assignedList->implode(' ')) }}"
                                data-target="{{ $targetDateRaw }}">
                                <td class="px-3 py-3.5" data-column="project">
                                    <div class="font-bold text-slate-900 text-[13px] leading-snug">{{ $project->name }}</div>
                                    <div class="text-[10px] text-slate-400 font-medium tracking-tight uppercase mt-0.5 whitespace-nowrap">{{ $project->project_code }} · {{ $project->deal?->deal_code ?? 'No Deal reference' }}</div>
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
                                    {{ $projectLead }}
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
                                    <a href="{{ route('project.show', $project) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-[#1b3b89] px-2.5 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-[#152e6d] transition cursor-pointer">
                                        Open Project
                                    </a>
                                    <div class="mt-1 flex items-center justify-center gap-1">
                                        <button type="button" onclick="openCancelProjectModal('{{ $project->id }}', '{{ addslashes($project->name) }}', '{{ $project->project_code }}')" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-2 py-0.5 text-[10.5px] font-medium text-slate-600 shadow-sm hover:bg-slate-50 transition cursor-pointer">
                                            Cancel
                                        </button>
                                        <button type="button" onclick="openSingleProjectDelete('{{ $project->id }}', '{{ addslashes($project->name) }}', '{{ $project->project_code }}')" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-2 py-0.5 text-[10.5px] font-medium text-red-600 shadow-sm hover:bg-rose-50 hover:border-red-200 transition cursor-pointer">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="noRecordsRow">
                                <td colspan="10" class="px-4 py-12 text-center text-sm text-slate-400">
                                    <i class="fas fa-folder-open text-3xl text-slate-300 block mb-2"></i>
                                    No project engagements have been recorded yet.
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
                    <select id="projectPageSize" class="h-8 rounded-lg border border-slate-200 px-2 text-xs text-slate-700 outline-none" onchange="changeProjectPageSize(this.value)">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div id="projectPaginationSummary" class="text-xs text-slate-500">
                    Showing 1–{{ min($projects->count(), 10) }} of {{ $projects->count() }}
                </div>
                <div class="registry-page-buttons flex items-center gap-1.5" id="projectPaginationButtons">
                    <button type="button" id="projectPrevBtn" class="px-3 py-1.5 text-xs text-slate-500 bg-white border border-slate-200 rounded-lg disabled:opacity-40" onclick="changeProjectPage('prev')">Previous</button>
                    <button type="button" class="px-3 py-1.5 text-xs font-bold text-white bg-[#102d79] rounded-lg active">1</button>
                    <button type="button" id="projectNextBtn" class="px-3 py-1.5 text-xs text-slate-500 bg-white border border-slate-200 rounded-lg disabled:opacity-40" onclick="changeProjectPage('next')">Next</button>
                </div>
            </div>
        </div>

        {{-- Cancel Project Modal (Exact Screenshot Design) --}}
        <div id="projectCancelModal" class="fixed inset-0 z-[70] hidden" aria-hidden="true">
            <button id="projectCancelOverlay" type="button" aria-label="Close cancel project modal" class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px] transition-opacity" onclick="closeProjectCancelModal()"></button>
            <div class="absolute left-1/2 top-1/2 w-full max-w-xl -translate-x-1/2 -translate-y-1/2 rounded-[28px] bg-white shadow-2xl overflow-hidden border border-slate-100">
                <form id="projectCancelForm" method="POST" action="">
                    @csrf
                    {{-- Modal Header --}}
                    <div class="p-7 pb-4">
                        <div class="text-[11px] font-black uppercase tracking-wider text-[#1e3a8a]">CANCEL PROJECT</div>
                        <div class="flex items-start justify-between mt-1">
                            <div>
                                <h2 class="text-2xl font-black tracking-tight text-[#0f2757]">Cancel project record</h2>
                                <p class="mt-1 text-xs font-semibold text-slate-500">
                                    <span id="projectCancelRefText">PROJ-2026-120</span> · <span id="projectCancelNameText">Transfer of Share From Dany and Ronald to X10</span>
                                </p>
                            </div>
                            <button type="button" onclick="closeProjectCancelModal()" class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition cursor-pointer">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Modal Body --}}
                    <div class="px-7 py-3">
                        <div class="flex items-center justify-between mb-2">
                            <label for="projectCancelReason" class="text-xs font-bold text-slate-800">Reason</label>
                            <span class="text-[10px] font-black tracking-wider text-rose-600 uppercase">REQUIRED</span>
                        </div>
                        <textarea
                            id="projectCancelReason"
                            name="reason"
                            required
                            rows="5"
                            class="w-full rounded-2xl border-2 border-blue-400/80 bg-white p-4 text-xs font-medium text-slate-800 placeholder-slate-400 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600 resize-y"
                            placeholder="Explain why this project is being cancelled..."
                        ></textarea>
                        <p class="mt-4 text-[11px] text-slate-400 font-normal leading-relaxed">
                            This action is retained with the acting user, date, time, and reason in the project history.
                        </p>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="border-t border-slate-100 px-7 py-4.5 bg-white flex items-center justify-end gap-3 mt-4">
                        <button type="button" onclick="closeProjectCancelModal()" class="h-11 rounded-full border border-slate-200 bg-white px-7 text-xs font-bold text-slate-600 shadow-sm hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="h-11 rounded-full bg-[#1b3b89] px-7 text-xs font-bold text-white shadow hover:bg-[#152e6d] transition cursor-pointer">
                            Cancel Project
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Delete Project Modal (Exact Screenshot Design) --}}
        <div id="projectDeleteSelectedModal" class="fixed inset-0 z-[70] hidden" aria-hidden="true">
            <button id="projectDeleteSelectedOverlay" type="button" aria-label="Close delete project modal" class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px] transition-opacity" onclick="closeProjectDeleteModal()"></button>
            <div class="absolute left-1/2 top-1/2 w-full max-w-xl -translate-x-1/2 -translate-y-1/2 rounded-[28px] bg-white shadow-2xl overflow-hidden border border-slate-100">
                <form id="projectBulkDeleteForm" method="POST" action="{{ route('project.bulk-delete') }}">
                    @csrf
                    @method('DELETE')
                    <div id="projectBulkDeleteSelectedItems"></div>

                    {{-- Modal Header --}}
                    <div class="p-7 pb-4">
                        <div class="text-[11px] font-black uppercase tracking-wider text-[#1e3a8a]">DELETE PROJECT</div>
                        <div class="flex items-start justify-between mt-1">
                            <div>
                                <h2 class="text-2xl font-black tracking-tight text-[#0f2757]">Delete project record</h2>
                                <p class="mt-1 text-xs font-semibold text-slate-500">
                                    <span id="projectDeleteRefText">PROJ-2026-119</span> · <span id="projectDeleteNameText">Transfer Shares Ronald to Stephan</span>
                                </p>
                            </div>
                            <button type="button" onclick="closeProjectDeleteModal()" class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition cursor-pointer">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Modal Body --}}
                    <div class="px-7 py-3">
                        <div class="flex items-center justify-between mb-2">
                            <label for="projectDeleteReason" class="text-xs font-bold text-slate-800">Reason</label>
                            <span class="text-[10px] font-black tracking-wider text-rose-600 uppercase">REQUIRED</span>
                        </div>
                        <textarea
                            id="projectDeleteReason"
                            name="reason"
                            required
                            rows="5"
                            class="w-full rounded-2xl border-2 border-blue-400/80 bg-white p-4 text-xs font-medium text-slate-800 placeholder-slate-400 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600 resize-y"
                            placeholder="Explain why this project is being moved to Deleted..."
                        ></textarea>
                        <p class="mt-4 text-[11px] text-slate-400 font-normal leading-relaxed">
                            This action is retained with the acting user, date, time, and reason in the project history.
                        </p>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="border-t border-slate-100 px-7 py-4.5 bg-white flex items-center justify-end gap-3 mt-4">
                        <button type="button" onclick="closeProjectDeleteModal()" class="h-11 rounded-full border border-slate-200 bg-white px-7 text-xs font-bold text-slate-600 shadow-sm hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer">
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

{{-- CREATE PROJECT MODAL (Exact match to target UI/UX) --}}
<div id="createProjectModal" class="fixed inset-0 z-[70] hidden overflow-y-auto" aria-hidden="true">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="closeCreateProjectModal()"></div>
    <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
        <div class="relative w-full max-w-4xl transform rounded-3xl bg-white p-6 sm:p-8 text-left shadow-2xl transition-all border border-slate-100">
            {{-- Header --}}
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">Create Project</h2>
                    <p class="mt-0.5 text-xs font-medium text-slate-500">How do you want to create this project?</p>
                </div>
                <button type="button" onclick="closeCreateProjectModal()" class="inline-flex items-center justify-center rounded-full border border-slate-300 px-4 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition">
                    Close
                </button>
            </div>

            <form id="createProjectForm" method="POST" action="{{ route('project.manual.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="source_mode" id="cp_source_mode" value="deal">
                <input type="hidden" name="contact_id" id="cp_contact_id" value="">
                <input type="hidden" name="company_id" id="cp_company_id" value="">
                <input type="hidden" name="service_area" id="cp_service_area" value="">
                <input type="hidden" name="services" id="cp_services" value="">
                <input type="hidden" name="products" id="cp_products" value="">
                <input type="hidden" name="engagement_type" id="cp_engagement_type" value="Project">

                {{-- Main submission hidden fields --}}
                <input type="hidden" name="name" id="cp_main_name" value="">
                <input type="hidden" name="client_name" id="cp_main_client_name" value="">
                <input type="hidden" name="business_name" id="cp_main_business_name" value="">
                <input type="hidden" name="assigned_project_manager" id="cp_main_assigned_project_manager" value="">
                <input type="hidden" name="assigned_associate" id="cp_main_assigned_associate" value="">
                <input type="hidden" name="target_completion_date" id="cp_main_target_completion_date" value="">
                <input type="hidden" name="planned_start_date" id="cp_main_planned_start_date" value="">

                {{-- 3 Creation Mode Selection Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <button type="button" id="cpModeCardDeal" onclick="switchCpMode('deal')" class="rounded-2xl border-2 border-blue-600 bg-blue-50/20 p-4 text-left transition relative cursor-pointer">
                        <span class="block text-[13px] font-bold text-slate-900">Link Existing Deal</span>
                        <span class="mt-1 block text-[11px] text-slate-500 leading-snug">Preload its client, business, engagement, source references, staffing, and selected SOW workstreams.</span>
                    </button>
                    <button type="button" id="cpModeCardManual" onclick="switchCpMode('manual')" class="rounded-2xl border border-slate-200 bg-white p-4 text-left transition hover:border-slate-300 relative cursor-pointer">
                        <span class="block text-[13px] font-bold text-slate-900">Manual</span>
                        <span class="mt-1 block text-[11px] text-slate-500 leading-snug">Enter the project and client information manually.</span>
                    </button>
                    <button type="button" id="cpModeCardDuplicate" onclick="switchCpMode('duplicate')" class="rounded-2xl border border-slate-200 bg-white p-4 text-left transition hover:border-slate-300 relative cursor-pointer">
                        <span class="block text-[13px] font-bold text-slate-900">Duplicate Existing Project</span>
                        <span class="mt-1 block text-[11px] text-slate-500 leading-snug">Copy its planning structure without approvals, timers, reports, or completion evidence.</span>
                    </button>
                </div>

                {{-- MODE 1: Link Existing Deal (Exact Picture 1) --}}
                <div id="cpPanelDeal" class="space-y-4 pt-2">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-3.5">
                        {{-- Row 1 --}}
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">EXISTING DEAL</label>
                            <select id="cpDealSelect" name="deal_id" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600" onchange="onCpDealChange(this.value)">
                                <option value="">Select an existing deal...</option>
                                @foreach ($dealRecords as $deal)
                                    <option value="{{ $deal['id'] }}">{{ $deal['deal_code'] }} — {{ $deal['deal_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">PROJECT TITLE</label>
                            <input type="text" id="cpProjectTitleDeal" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50/50 px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600" placeholder="Transfer of Share From Dany and Ronald to X10">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">BUSINESS / COMPANY</label>
                            <input type="text" id="cpBusinessNameDeal" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50/50 px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600" placeholder="X10 REAL ESTATE CORPORATION">
                        </div>

                        {{-- Row 2 --}}
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">CLIENT</label>
                            <input type="text" id="cpClientNameDeal" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50/50 px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600" placeholder="May Flor D. Dabatos">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">EPA NO.</label>
                            <input type="text" id="cpEpaNoDeal" readonly class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50/50 px-4 text-xs font-bold text-slate-800" placeholder="EPA-2026-065">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">PROJECT LEAD</label>
                            <input type="text" id="cpProjectLeadDeal" list="projectEmployeeOptions" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50/50 px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600" placeholder="John Kelly Abalde">
                        </div>

                        {{-- Row 3 --}}
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">LEAD ASSOCIATE</label>
                            <input type="text" id="cpLeadAssociateDeal" list="projectEmployeeOptions" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50/50 px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600" placeholder="Rubeca Potayre">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">TARGET COMPLETION</label>
                            <input type="text" id="cpTargetCompletionDeal" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50/50 px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600" placeholder="Sep 18, 2026">
                        </div>
                        <div></div>
                    </div>

                    {{-- SOW Workstreams Section --}}
                    <div id="cpWorkstreamsSection" class="rounded-2xl border border-slate-200 overflow-hidden bg-white shadow-sm mt-3">
                        <div class="flex items-center justify-between bg-[#f0f4fb] px-6 py-3.5 border-b border-slate-200">
                            <span class="font-bold text-slate-900 text-xs tracking-wide">SOW Workstreams</span>
                            <span class="text-[11px] text-slate-500 font-medium">Select workstreams to preload</span>
                        </div>
                        <div id="cpWorkstreamsList" class="divide-y divide-slate-100 px-6 py-2 max-h-48 overflow-y-auto">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                </div>

                {{-- MODE 2: Manual (Exact Picture 2) --}}
                <div id="cpPanelManual" class="space-y-4 pt-2 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-3.5">
                        {{-- Row 1 --}}
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">PROJECT TITLE</label>
                            <input type="text" id="cpProjectTitleManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">BUSINESS / COMPANY</label>
                            <input type="text" id="cpBusinessNameManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">CLIENT NAME</label>
                            <input type="text" id="cpClientNameManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>

                        {{-- Row 2 --}}
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">CLIENT EMAIL</label>
                            <input type="email" id="cpClientEmailManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">EPA NO.</label>
                            <input type="text" id="cpEpaNoManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">PROJECT LEAD</label>
                            <input type="text" id="cpProjectLeadManual" list="projectEmployeeOptions" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>

                        {{-- Row 3 --}}
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">LEAD ASSOCIATE</label>
                            <input type="text" id="cpLeadAssociateManual" list="projectEmployeeOptions" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">TARGET START</label>
                            <input type="date" id="cpTargetStartManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">TARGET COMPLETION</label>
                            <input type="date" id="cpTargetCompletionManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>

                        {{-- Row 4 --}}
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">SERVICE / PROJECT</label>
                            <input type="text" id="cpServicesManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">SERVICE AREA</label>
                            <input type="text" id="cpServiceAreaManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">ENGAGEMENT TYPE</label>
                            <select id="cpEngagementTypeManual" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                                <option value="Project" selected>Project</option>
                                <option value="Consultancy">Consultancy</option>
                                <option value="Retainer">Retainer</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- MODE 3: Duplicate Existing Project (Exact Picture 3) --}}
                <div id="cpPanelDuplicate" class="space-y-3 pt-2 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-3.5">
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">EXISTING PROJECT</label>
                            <select id="cpDuplicateSelect" name="duplicate_project_id" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600" onchange="onCpDuplicateChange(this.value)">
                                <option value="">Select project to duplicate...</option>
                                @foreach ($projects as $proj)
                                    <option value="{{ $proj->id }}">{{ $proj->project_code }} — {{ $proj->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">NEW PROJECT TITLE</label>
                            <input type="text" id="cpProjectTitleDuplicate" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-1.5">NEW TARGET COMPLETION</label>
                            <input type="date" id="cpTargetCompletionDuplicate" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-2">Only project information, assignments, and workstream structure are copied. The new project begins at Work Order.</p>
                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeCreateProjectModal()" class="inline-flex h-10 items-center justify-center rounded-full border border-slate-200 bg-white px-6 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-full bg-[#1b3b89] px-7 text-xs font-bold text-white shadow hover:bg-[#152e6d] transition">
                        Create Project
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<datalist id="projectEmployeeOptions">
    @foreach (($employeeRecords ?? []) as $employee)
        <option value="{{ $employee['label'] }}">{{ $employee['position'] ?? '' }}{{ !empty($employee['employee_code']) ? ' - '.$employee['employee_code'] : '' }}</option>
    @endforeach
</datalist>

@php
    $cpProjectRecordsData = [];
    if (!empty($projects)) {
        foreach ($projects as $p) {
            $cpProjectRecordsData[] = [
                'id' => $p->id,
                'code' => $p->project_code,
                'name' => $p->name,
                'business' => $p->business_name,
                'client' => $p->client_name,
                'lead' => $p->assigned_project_manager ?: $p->assigned_consultant,
                'associate' => $p->assigned_associate,
                'target' => $p->target_completion_date ? $p->target_completion_date->format('Y-m-d') : '',
                'services' => $p->services,
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
        const table = document.getElementById('projectRegistryTable');
        if (!table) return;
        const cells = table.querySelectorAll(`[data-column="${colName}"]`);
        cells.forEach(el => {
            el.style.display = isVisible ? '' : 'none';
        });
    };

    window.exportProjectTable = function(type) {
        const table = document.getElementById('projectRegistryTable');
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
        link.setAttribute('download', 'project_registry_' + new Date().toISOString().slice(0, 10) + '.csv');
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

    const cpDealRecords = {!! json_encode($dealRecords ?? []) !!};
    const cpContactRecords = {!! json_encode($contactRecords ?? []) !!};
    const cpCompanyRecords = {!! json_encode($companyRecords ?? []) !!};
    const cpEmployeeRecords = {!! json_encode($employeeRecords ?? []) !!};
    const cpProjectRecords = {!! json_encode($cpProjectRecordsData) !!};

    window.openCreateProjectModal = function() {
        const modal = document.getElementById('createProjectModal');
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');

        const dealSelect = document.getElementById('cpDealSelect');
        if (dealSelect && dealSelect.options.length > 1 && !dealSelect.value) {
            dealSelect.selectedIndex = 1;
            onCpDealChange(dealSelect.value);
        }
    };

    window.closeCreateProjectModal = function() {
        const modal = document.getElementById('createProjectModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
        }
    };

    window.switchCpMode = function(mode) {
        document.getElementById('cp_source_mode').value = mode;

        const cardDeal = document.getElementById('cpModeCardDeal');
        const cardManual = document.getElementById('cpModeCardManual');
        const cardDuplicate = document.getElementById('cpModeCardDuplicate');

        const panelDeal = document.getElementById('cpPanelDeal');
        const panelManual = document.getElementById('cpPanelManual');
        const panelDuplicate = document.getElementById('cpPanelDuplicate');

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
            const dealSelect = document.getElementById('cpDealSelect');
            if (dealSelect?.value) {
                onCpDealChange(dealSelect.value);
            }
        } else if (mode === 'manual') {
            cardManual?.classList.remove(...inactiveClasses);
            cardManual?.classList.add(...activeClasses);
            panelManual?.classList.remove('hidden');
        } else if (mode === 'duplicate') {
            cardDuplicate?.classList.remove(...inactiveClasses);
            cardDuplicate?.classList.add(...activeClasses);
            panelDuplicate?.classList.remove('hidden');
            const dupSelect = document.getElementById('cpDuplicateSelect');
            if (dupSelect?.value) {
                onCpDuplicateChange(dupSelect.value);
            }
        }
    };

    window.onCpDealChange = function(dealId) {
        if (!dealId) {
            renderCpWorkstreams([]);
            return;
        }
        const deal = cpDealRecords.find(d => String(d.id) === String(dealId));
        if (!deal) return;

        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        };

        setVal('cpProjectTitleDeal', deal.deal_name || deal.title || '');
        setVal('cpBusinessNameDeal', deal.business_name || deal.company_name || '');
        setVal('cpClientNameDeal', deal.client_name || [deal.first_name, deal.last_name].filter(Boolean).join(' ') || '');
        setVal('cpEpaNoDeal', deal.deal_code ? deal.deal_code.replace('CONDEAL-', 'EPA-') : '');
        setVal('cpProjectLeadDeal', deal.assigned_consultant || deal.assigned_project_manager || 'John Kelly Abalde');
        setVal('cpLeadAssociateDeal', deal.assigned_associate || 'Rubeca Potayre');
        
        let targetFormatted = 'Sep 18, 2026';
        if (deal.target_completion_date) {
            try {
                const d = new Date(deal.target_completion_date);
                if (!isNaN(d.getTime())) {
                    targetFormatted = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                }
            } catch (e) {}
        }
        setVal('cpTargetCompletionDeal', targetFormatted);

        setVal('cp_contact_id', deal.contact_id || '');
        setVal('cp_company_id', deal.company_id || '');
        setVal('cp_service_area', deal.service_area || '');
        setVal('cp_services', deal.services || '');
        setVal('cp_products', deal.products || '');

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

        renderCpWorkstreams(workstreams);
    };

    window.onCpDuplicateChange = function(projId) {
        if (!projId) return;
        const proj = cpProjectRecords.find(p => String(p.id) === String(projId));
        if (!proj) return;

        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        };

        setVal('cpProjectTitleDuplicate', proj.name || '');
        setVal('cpTargetCompletionDuplicate', proj.target || '');
    };

    function renderCpWorkstreams(workstreams) {
        const listEl = document.getElementById('cpWorkstreamsList');
        if (!listEl) return;

        if (!workstreams || workstreams.length === 0) {
            listEl.innerHTML = `<div class="py-4 text-center text-xs text-slate-400">No workstreams available for this deal.</div>`;
            return;
        }

        listEl.innerHTML = workstreams.map((ws, idx) => {
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
        const form = document.getElementById('createProjectForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                const mode = document.getElementById('cp_source_mode').value;
                const setMain = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.value = val || '';
                };

                if (mode === 'deal') {
                    setMain('cp_main_name', document.getElementById('cpProjectTitleDeal').value);
                    setMain('cp_main_business_name', document.getElementById('cpBusinessNameDeal').value);
                    setMain('cp_main_client_name', document.getElementById('cpClientNameDeal').value);
                    setMain('cp_main_assigned_project_manager', document.getElementById('cpProjectLeadDeal').value);
                    setMain('cp_main_assigned_associate', document.getElementById('cpLeadAssociateDeal').value);
                    
                    const deal = cpDealRecords.find(d => String(d.id) === String(document.getElementById('cpDealSelect').value));
                    setMain('cp_main_target_completion_date', deal?.target_completion_date || '');
                } else if (mode === 'manual') {
                    setMain('cp_main_name', document.getElementById('cpProjectTitleManual').value);
                    setMain('cp_main_business_name', document.getElementById('cpBusinessNameManual').value);
                    setMain('cp_main_client_name', document.getElementById('cpClientNameManual').value);
                    setMain('cp_main_assigned_project_manager', document.getElementById('cpProjectLeadManual').value);
                    setMain('cp_main_assigned_associate', document.getElementById('cpLeadAssociateManual').value);
                    setMain('cp_main_planned_start_date', document.getElementById('cpTargetStartManual').value);
                    setMain('cp_main_target_completion_date', document.getElementById('cpTargetCompletionManual').value);
                    setMain('cp_services', document.getElementById('cpServicesManual').value);
                    setMain('cp_service_area', document.getElementById('cpServiceAreaManual').value);
                    setMain('cp_engagement_type', document.getElementById('cpEngagementTypeManual').value);
                } else if (mode === 'duplicate') {
                    setMain('cp_main_name', document.getElementById('cpProjectTitleDuplicate').value);
                    setMain('cp_main_target_completion_date', document.getElementById('cpTargetCompletionDuplicate').value);
                }
            });
        }
    });

    (() => {

        const setFieldValue = (id, value) => {
            const field = document.getElementById(id);
            if (!field) {
                return;
            }

            field.value = value ?? '';
        };

        const selectedProjectCustomerType = () => document.querySelector('input[name="project_customer_type"]:checked')?.value || '';

        const selectedProjectServiceAreas = () => Array.from(document.querySelectorAll('input[name="service_area_options[]"]:checked'))
            .map((input) => input.value)
            .filter((value) => value !== 'Others');

        const parsePrefixedEntries = (value, prefix) => String(value || '')
            .split(',')
            .map((item) => item.trim())
            .filter((item) => item.startsWith(prefix))
            .map((item) => item.slice(prefix.length).trim())
            .filter(Boolean);

        const parseBaseEntries = (value, excludedPrefixes = [], excludedValues = []) => String(value || '')
            .split(',')
            .map((item) => item.trim())
            .filter((item) => item !== '')
            .filter((item) => !excludedPrefixes.some((prefix) => item.startsWith(prefix)))
            .filter((item) => !excludedValues.includes(item));

        const ensureCustomOption = (container, options) => {
            if (!container || !options.value) {
                return;
            }

            const exists = Array.from(container.querySelectorAll('input[type="checkbox"]'))
                .some((input) => input.value === options.value);
            if (exists) {
                return;
            }

            const label = document.createElement('label');
            label.className = 'flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700';
            label.setAttribute('data-custom-option', '');
            label.innerHTML = `
                <input type="checkbox" name="${options.checkboxName}" value="${options.value}" checked class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="flex-1">${options.value}</span>
                <button type="button" class="text-gray-500 hover:text-gray-700" data-custom-option-remove>&times;</button>
                <input type="hidden" name="${options.hiddenName}" value="${options.value}" data-custom-option-hidden>
            `;
            container.appendChild(label);
            attachCustomOptionRemove(label);
        };

        const attachCustomOptionRemove = (element) => {
            element.querySelector('[data-custom-option-remove]')?.addEventListener('click', () => {
                element.remove();
                syncServiceGroups();
                syncProductOptions();
                syncCompositeFields();
            });
        };

        const initCustomOptionInput = ({ inputId, containerId, checkboxName, hiddenName, isEnabled }) => {
            const input = document.getElementById(inputId);
            const container = document.getElementById(containerId);
            if (!input || !container) {
                return;
            }

            input.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter') {
                    return;
                }

                event.preventDefault();
                if (isEnabled && !isEnabled()) {
                    return;
                }

                const value = String(input.value || '').trim();
                if (!value) {
                    return;
                }

                ensureCustomOption(container, { value, checkboxName, hiddenName });
                input.value = '';
                syncServiceGroups();
                syncProductOptions();
                syncCompositeFields();
            });
        };

        const setCheckedValues = (name, values) => {
            const set = new Set(values || []);
            document.querySelectorAll(`input[name="${name}"]`).forEach((input) => {
                input.checked = set.has(input.value);
            });
        };

        const syncCompositeFields = () => {
            const selectedAreas = Array.from(document.querySelectorAll('input[name="service_area_options[]"]:checked'))
                .map((input) => input.value)
                .filter((value) => value !== 'Others');
            const selectedServices = Array.from(document.querySelectorAll('input[name="service_options[]"]:checked'))
                .map((input) => input.value);
            const selectedProducts = Array.from(document.querySelectorAll('input[name="product_options[]"]:checked'))
                .map((input) => input.value)
                .filter((value) => value !== 'Others');

            setFieldValue('project_service_area', selectedAreas.join(', '));
            setFieldValue('project_services', selectedServices.join(', '));
            setFieldValue('project_products', selectedProducts.join(', '));
        };

        const syncServiceGroups = () => {
            const selectedAreas = Array.from(document.querySelectorAll('input[name="service_area_options[]"]:checked')).map((input) => input.value);
            const serviceEmptyState = document.getElementById('projectServicesEmptyState');
            const serviceGrid = document.getElementById('projectServicesGrid');

            document.querySelectorAll('[data-project-service-group]').forEach((group) => {
                const visible = selectedAreas.includes(group.getAttribute('data-project-service-group'));
                group.classList.toggle('hidden', !visible);
                if (!visible) {
                    group.querySelectorAll('input[name="service_options[]"]').forEach((input) => {
                        if (!input.closest('[data-custom-option]')) {
                            input.checked = false;
                        }
                    });
                }
            });

            serviceEmptyState?.classList.toggle('hidden', selectedAreas.length > 0);
            serviceGrid?.classList.toggle('hidden', selectedAreas.length === 0);
            syncCompositeFields();
        };

        const syncProductOptions = () => {
            const productSearchTerm = '';
            const productEmptyState = document.getElementById('projectProductsEmptyState');
            const selectedAreas = selectedProjectServiceAreas();
            const seenProducts = new Set();

            let visibleCount = 0;
            document.querySelectorAll('[data-project-product-group]').forEach((group) => {
                let groupHasVisibleProduct = false;
                const groupArea = group.getAttribute('data-project-product-group') || '';
                const isUnlinkedGroup = group.dataset.productUnlinkedGroup === 'true';
                const groupMatchesArea = selectedAreas.length === 0
                    ? isUnlinkedGroup
                    : selectedAreas.includes(groupArea);

                group.querySelectorAll('[data-project-product-option]').forEach((option) => {
                    const optionMatchesSearch = productSearchTerm === '' || String(option.dataset.projectProductSearch || '').includes(productSearchTerm);
                    const productValue = String(option.dataset.productValue || option.querySelector('input[name="product_options[]"]')?.value || '').trim().toLowerCase();
                    const isDuplicate = productValue !== '' && seenProducts.has(productValue);
                    const optionVisible = groupMatchesArea && optionMatchesSearch && !isDuplicate;
                    option.classList.toggle('hidden', !optionVisible);
                    groupHasVisibleProduct = groupHasVisibleProduct || optionVisible;
                    if (optionVisible && productValue !== '') {
                        seenProducts.add(productValue);
                    }
                    if (!optionVisible && !option.closest('[data-custom-option]')) {
                        const productInput = option.querySelector('input[name="product_options[]"]');
                        if (productInput) {
                            productInput.checked = false;
                        }
                    }
                });

                group.classList.toggle('hidden', !groupHasVisibleProduct);
                if (groupHasVisibleProduct) {
                    visibleCount += 1;
                }
            });

            productEmptyState?.classList.toggle('hidden', visibleCount > 0);
            syncCompositeFields();
        };

        const hideEmployeeSearchResults = (picker) => {
            picker?.querySelector('[data-employee-search-results]')?.classList.add('hidden');
        };

        const renderEmployeeSearchResults = (picker, keyword = '') => {
            const input = picker?.querySelector('[data-employee-search-input]');
            const results = picker?.querySelector('[data-employee-search-results]');
            if (!input || !results) {
                return;
            }

            const query = String(keyword || '').trim().toLowerCase();
            const matches = employeeRecords.filter((record) => {
                if (query === '') {
                    return true;
                }

                return String(record.search_blob || [
                    record.label,
                    record.name,
                    record.employee_code,
                    record.position,
                    record.email,
                ].filter(Boolean).join(' ')).toLowerCase().includes(query);
            }).slice(0, 8);

            if (matches.length === 0) {
                results.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">No existing employee. You can still type manually.</div>';
                results.classList.remove('hidden');
                return;
            }

            results.replaceChildren(...matches.map((record) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'block w-full border-b border-gray-100 px-3 py-2 text-left last:border-b-0 hover:bg-blue-50';
                button.innerHTML = `<div class="text-sm font-medium text-gray-800">${record.label || record.name || ''}</div><div class="text-xs text-gray-500">${[record.employee_code, record.position, record.email].filter(Boolean).join(' - ')}</div>`;
                button.addEventListener('click', () => {
                    input.value = record.label || record.name || '';
                    hideEmployeeSearchResults(picker);
                    renderProjectTemplatePreview();
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
                return button;
            }));

            results.classList.remove('hidden');
        };

        const initEmployeeSearchPickers = () => {
            Array.from(document.querySelectorAll('[data-employee-picker]')).forEach((picker) => {
                const input = picker.querySelector('[data-employee-search-input]');
                if (!input) {
                    return;
                }

                input.addEventListener('focus', () => renderEmployeeSearchResults(picker, input.value));
                input.addEventListener('input', () => renderEmployeeSearchResults(picker, input.value));
                input.addEventListener('change', renderProjectTemplatePreview);
                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        hideEmployeeSearchResults(picker);
                    }
                });
            });
        };

        const applyServiceSelections = ({ serviceArea = '', services = '', products = '' }) => {
            const serviceAreaBases = parseBaseEntries(serviceArea, ['Others: ']);
            const serviceBases = parseBaseEntries(services, ['Custom: ']);
            const productBases = parseBaseEntries(products, ['Custom: '], ['Others']);

            setCheckedValues('service_area_options[]', serviceAreaBases);
            setCheckedValues('service_options[]', serviceBases);
            setCheckedValues('product_options[]', productBases);

            syncServiceGroups();
            syncProductOptions();
            syncCompositeFields();
        };

        const selectedAreasIncludeOthers = () => Boolean(document.querySelector('input[name="service_area_options[]"][value="Others"]')?.checked);

        const applyProjectDetails = (payload) => {
            setFieldValue('project_name', payload.name || '');
            setFieldValue('project_client_name', payload.client_name || '');
            setFieldValue('project_business_name', payload.business_name || '');
            setFieldValue('project_planned_start_date', payload.planned_start_date || '');
            setFieldValue('project_target_completion_date', payload.target_completion_date || '');
            setFieldValue('project_client_confirmation_name', payload.client_confirmation_name || '');
            setFieldValue('project_assigned_project_manager', payload.assigned_project_manager || '');
            setFieldValue('project_assigned_consultant', payload.assigned_consultant || '');
            setFieldValue('project_assigned_associate', payload.assigned_associate || '');
            setFieldValue('project_sales_marketing', payload.sales_marketing || '');
            setFieldValue('project_finance', payload.finance || '');
            setFieldValue('project_scope_summary', payload.scope_summary || '');
            setFieldValue('project_engagement_requirements_text', payload.engagement_requirements_text || '');
            applyServiceSelections({
                serviceArea: payload.service_area || '',
                services: payload.services || '',
                products: payload.products || '',
            });
            renderProjectTemplatePreview();
        };

        const inferProjectName = ({ dealCode = '', dealName = '', clientName = '', businessName = '' }) => {
            if (dealCode && dealName && dealName !== dealCode) {
                return `${dealCode} - ${dealName}`;
            }

            if (dealCode) {
                return dealCode;
            }

            if (dealName) {
                return dealName;
            }

            if (businessName) {
                return `Project for ${businessName}`;
            }

            if (clientName) {
                return `Project for ${clientName}`;
            }

            return '';
        };

        const updateSourceUi = () => {
            const mode = sourceModeInput.value === 'deal' ? 'deal' : 'manual';

            dealSection?.classList.toggle('hidden', mode !== 'deal');
            manualSection?.classList.toggle('hidden', mode !== 'manual');

            sourceButtons.forEach((button) => {
                const active = button.dataset.projectSourceOption === mode;
                button.classList.toggle('border-[#102d79]', active);
                button.classList.toggle('ring-2', active);
                button.classList.toggle('ring-[#102d79]/10', active);
                button.classList.toggle('border-gray-200', !active);
            });
        };

        const setSourceMode = (mode) => {
            sourceModeInput.value = mode === 'deal' ? 'deal' : 'manual';

            if (mode === 'deal') {
                contactIdInput.value = '';
                companyIdInput.value = '';
                selectedState.contact = null;
                selectedState.company = null;
                manualSummary?.classList.add('hidden');
            } else {
                dealIdInput.value = '';
                selectedState.deal = null;
                dealSummary?.classList.add('hidden');
            }

            updateSourceUi();
        };

        const syncProjectCustomerSearchUi = () => {
            const customerType = selectedProjectCustomerType();
            const isBusiness = customerType === 'business';

            if (projectContactSearchLabel) {
                projectContactSearchLabel.textContent = isBusiness ? 'Search Existing Business / Company' : 'Search Existing Client';
            }

            if (projectSelectionSectionTitle) {
                projectSelectionSectionTitle.textContent = isBusiness ? 'Select Existing Business / Company' : 'Select Existing Contact / Client';
            }

            if (projectSearchHelpText) {
                projectSearchHelpText.textContent = isBusiness
                    ? 'Select a customer type, then search companies by company name, owner, email, or mobile.'
                    : 'Select a customer type, then search contacts by name, company, email, or mobile.';
            }

            if (contactSearch) {
                contactSearch.placeholder = isBusiness
                    ? 'Type company, owner, email, or mobile...'
                    : 'Type name, company, email, or mobile...';
                contactSearch.value = '';
            }

            contactResults?.classList.add('hidden');
            contactIdInput.value = '';
            companyIdInput.value = '';
            selectedState.contact = null;
            selectedState.company = null;
            setManualSummary();
        };

        const setDealSummary = (record) => {
            if (!dealSummary) {
                return;
            }

            if (!record) {
                dealSummary.classList.add('hidden');
                dealSummary.textContent = '';
                return;
            }

            dealSummary.innerHTML = `<strong class="font-semibold">${record.deal_code || record.label}</strong><div class="mt-1 text-xs text-blue-800">${record.client_name || 'No client'} · ${record.business_name || 'No company'}</div>`;
            dealSummary.classList.remove('hidden');
        };

        const setManualSummary = () => {
            if (!manualSummary) {
                return;
            }

            const lines = [];
            if (selectedState.contact) {
                lines.push(`Contact: ${selectedState.contact.label || selectedState.contact.company_name || 'Selected contact'}`);
            }
            if (selectedState.company) {
                lines.push(`Company: ${selectedState.company.company_name || selectedState.company.label || 'Selected company'}`);
            }

            if (lines.length === 0) {
                manualSummary.classList.add('hidden');
                manualSummary.textContent = '';
                return;
            }

            manualSummary.innerHTML = lines.map((line) => `<div>${line}</div>`).join('');
            manualSummary.classList.remove('hidden');
        };

        const applyDealRecord = (record) => {
            selectedState.deal = record;
            dealIdInput.value = record.id ? String(record.id) : '';
            contactIdInput.value = record.contact_id ? String(record.contact_id) : '';

            const linkedCompany = companyRecords.find((item) => (item.company_name || '') === (record.business_name || ''));
            companyIdInput.value = linkedCompany?.id ? String(linkedCompany.id) : '';

            applyProjectDetails({
                name: inferProjectName({
                    dealCode: record.deal_code,
                    dealName: record.deal_name,
                    clientName: record.client_name,
                    businessName: record.business_name,
                }),
                client_name: record.client_name,
                business_name: record.business_name,
                planned_start_date: record.planned_start_date,
                target_completion_date: record.target_completion_date,
                service_area: record.service_area,
                client_confirmation_name: record.client_confirmation_name || record.client_name,
                assigned_project_manager: record.assigned_project_manager,
                assigned_consultant: record.assigned_consultant,
                assigned_associate: record.assigned_associate,
                sales_marketing: record.sales_marketing,
                finance: record.finance,
                services: record.services,
                products: record.products,
                scope_summary: record.scope_summary,
                engagement_requirements_text: record.scope_summary || record.services || '',
            });

            dealSearch.value = record.deal_code || record.label || '';
            dealResults?.classList.add('hidden');
            setDealSummary(record);
        };

        const applyContactRecord = (record) => {
            selectedState.contact = record;
            contactIdInput.value = record.id ? String(record.id) : '';
            if (!companyIdInput.value && record.company_name) {
                const linkedCompany = companyRecords.find((item) => (item.company_name || '') === record.company_name);
                if (linkedCompany) {
                    companyIdInput.value = String(linkedCompany.id);
                    selectedState.company = linkedCompany;
                }
            }

            applyProjectDetails({
                name: inferProjectName({
                    clientName: record.label,
                    businessName: record.company_name,
                }),
                client_name: record.label,
                business_name: record.company_name,
                client_confirmation_name: record.label,
            });

            contactSearch.value = record.label || '';
            contactResults?.classList.add('hidden');
            setManualSummary();
        };

        const applyCompanyRecord = (record) => {
            selectedState.company = record;
            companyIdInput.value = record.id ? String(record.id) : '';

            if (!contactIdInput.value && record.primary_contact_id) {
                const linkedContact = contactRecords.find((item) => Number(item.id) === Number(record.primary_contact_id));
                if (linkedContact) {
                    contactIdInput.value = String(linkedContact.id);
                    selectedState.contact = linkedContact;
                    contactSearch.value = linkedContact.label || '';
                }
            }

            const resolvedClientName = selectedState.contact?.label || record.primary_contact_name || '';
            applyProjectDetails({
                name: inferProjectName({
                    clientName: resolvedClientName,
                    businessName: record.company_name,
                }),
                client_name: resolvedClientName,
                business_name: record.company_name,
                client_confirmation_name: resolvedClientName,
            });

            contactSearch.value = record.company_name || record.label || '';
            contactResults?.classList.add('hidden');
            setManualSummary();
        };

        const renderResults = ({ input, container, records, emptyLabel, renderer, onSelect }) => {
            if (!input || !container) {
                return;
            }

            const keyword = String(input.value || '').trim().toLowerCase();
            const matches = records.filter((record) => {
                if (keyword === '') {
                    return true;
                }

                return String(record.search_blob || record.label || '').toLowerCase().includes(keyword);
            }).slice(0, 20);

            if (matches.length === 0) {
                container.innerHTML = `<div class="px-4 py-3 text-sm text-gray-500">${emptyLabel}</div>`;
                container.classList.remove('hidden');
                return;
            }

            container.replaceChildren(...matches.map((record) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'block w-full border-b border-gray-100 px-4 py-3 text-left last:border-b-0 hover:bg-gray-50';
                button.innerHTML = renderer(record);
                button.addEventListener('click', () => onSelect(record));
                return button;
            }));

            container.classList.remove('hidden');
        };

        const renderProjectTemplatePreview = () => {
            if (!templateSelect || !templatePreview) {
                return;
            }

            const template = sowTemplatePreviewData[String(templateSelect.value || '')];
            if (!template) {
                templatePreviewName.textContent = 'Blank Project Form';
                templatePreviewBadge.textContent = 'Default';
                templateMetaCondeal.textContent = '-';
                templateMetaCode.textContent = 'Auto-generated';
                templateMetaClient.textContent = document.getElementById('project_client_name')?.value || 'Pending selection';
                templateMetaBusiness.textContent = document.getElementById('project_business_name')?.value || 'Pending selection';
                templatePreviewVersion.textContent = '1.0';
                templatePreviewStatuses.textContent = 'Draft template';
                templateSignatureName.textContent = document.getElementById('project_client_confirmation_name')?.value || 'Client representative signature';
                templatePreparedBy.textContent = document.getElementById('project_assigned_associate')?.value || document.getElementById('project_assigned_consultant')?.value || '';
                templateReviewedBy.textContent = document.getElementById('project_assigned_project_manager')?.value || '';
                templateWithinScope.innerHTML = '<tr><td colspan="3" class="border border-slate-900 px-2 py-2 text-center text-slate-500">No within-scope items yet.</td></tr>';
                templateOutScope.textContent = 'No out-of-scope items will be loaded until a saved template is selected.';
                templatePreviewEffect.textContent = 'The project will start from a blank/default SOW structure.';
                return;
            }

            templatePreviewName.textContent = template.name || 'Selected template';
            templatePreviewBadge.textContent = `Version ${template.version || '1.0'}`;
            templateMetaCondeal.textContent = '-';
            templateMetaCode.textContent = 'Auto-generated';
            templateMetaClient.textContent = document.getElementById('project_client_name')?.value || 'Pending selection';
            templateMetaBusiness.textContent = document.getElementById('project_business_name')?.value || 'Pending selection';
            templatePreviewVersion.textContent = template.version || '1.0';
            templatePreviewStatuses.textContent = `Approval ${template.approval_status || 'draft'} | NTP ${template.ntp_status || 'pending'}`;
            templateSignatureName.textContent = document.getElementById('project_client_confirmation_name')?.value || 'Client representative signature';
            templatePreparedBy.textContent = document.getElementById('project_assigned_associate')?.value || document.getElementById('project_assigned_consultant')?.value || '';
            templateReviewedBy.textContent = document.getElementById('project_assigned_project_manager')?.value || '';

            const scopeItems = Array.isArray(template.within_scope_items) ? template.within_scope_items : [];
            if (scopeItems.length === 0) {
                templateWithinScope.innerHTML = '<tr><td colspan="3" class="border border-slate-900 px-2 py-2 text-center text-slate-500">No saved within-scope items in this template.</td></tr>';
            } else {
                templateWithinScope.innerHTML = scopeItems
                    .map((item, index) => {
                        const [mainTask, subTask = ''] = String(item || '').split(' - ');
                        const status = index === 0 ? 'Open' : (index === 1 ? 'In Progress' : 'Pending');

                        return `<tr>
                            <td class="border border-slate-900 px-2 py-1.5 align-top">${mainTask || ''}</td>
                            <td class="border border-slate-900 px-2 py-1.5 align-top">${subTask || ''}</td>
                            <td class="border border-slate-900 px-2 py-1.5 align-top text-center">${status}</td>
                        </tr>`;
                    })
                    .join('');
            }

            templateOutScope.textContent = Number(template.out_of_scope_count || 0) > 0
                ? `${template.out_of_scope_count} saved out-of-scope item(s) will also be loaded.`
                : 'No out-of-scope items saved in this template.';
            templatePreviewEffect.textContent = `This template will prefill ${template.within_scope_count || 0} within-scope row(s) and ${template.out_of_scope_count || 0} out-of-scope row(s) in the SOW form.`;
        };

        sourceButtons.forEach((button) => {
            button.addEventListener('click', () => setSourceMode(button.dataset.projectSourceOption || 'manual'));
        });

        dealSearch?.addEventListener('focus', () => renderResults({
            input: dealSearch,
            container: dealResults,
            records: dealRecords,
            emptyLabel: 'No available deals found.',
            renderer: (record) => `
                <div class="text-sm font-medium text-gray-900">${record.deal_code || record.label}</div>
                <div class="mt-1 text-xs text-gray-500">${record.client_name || 'No client'} · ${record.business_name || 'No company'}</div>
            `,
            onSelect: applyDealRecord,
        }));
        dealSearch?.addEventListener('input', () => renderResults({
            input: dealSearch,
            container: dealResults,
            records: dealRecords,
            emptyLabel: 'No available deals found.',
            renderer: (record) => `
                <div class="text-sm font-medium text-gray-900">${record.deal_code || record.label}</div>
                <div class="mt-1 text-xs text-gray-500">${record.client_name || 'No client'} · ${record.business_name || 'No company'}</div>
            `,
            onSelect: applyDealRecord,
        }));

        const renderProjectCustomerResults = () => {
            const customerType = selectedProjectCustomerType();
            if (!customerType) {
                contactResults.innerHTML = '<div class="px-4 py-3 text-sm text-gray-500">Select a customer type first.</div>';
                contactResults.classList.remove('hidden');
                return;
            }

            if (customerType === 'business') {
                renderResults({
                    input: contactSearch,
                    container: contactResults,
                    records: companyRecords,
                    emptyLabel: 'No matching companies found.',
                    renderer: (record) => `
                        <div class="text-sm font-medium text-gray-900">${record.company_name || 'Unnamed company'}</div>
                        <div class="mt-1 text-xs text-gray-500">${record.primary_contact_name || record.owner_name || '-'} · ${record.email || record.mobile || '-'}</div>
                    `,
                    onSelect: applyCompanyRecord,
                });
                return;
            }

            renderResults({
                input: contactSearch,
                container: contactResults,
                records: contactRecords,
                emptyLabel: 'No matching contacts found.',
                renderer: (record) => `
                    <div class="text-sm font-medium text-gray-900">${record.label || 'Unnamed contact'}</div>
                    <div class="mt-1 text-xs text-gray-500">${record.company_name || '-'} · ${record.email || record.mobile || '-'}</div>
                `,
                onSelect: applyContactRecord,
            });
        };

        contactSearch?.addEventListener('focus', renderProjectCustomerResults);
        contactSearch?.addEventListener('input', renderProjectCustomerResults);
        customerTypeInputs.forEach((input) => {
            input.addEventListener('change', syncProjectCustomerSearchUi);
        });

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Node)) {
                return;
            }

            if (!dealSearch?.contains(target) && !dealResults?.contains(target)) {
                dealResults?.classList.add('hidden');
            }

            if (!contactSearch?.contains(target) && !contactResults?.contains(target)) {
                contactResults?.classList.add('hidden');
            }

            Array.from(document.querySelectorAll('[data-employee-picker]')).forEach((picker) => {
                if (!picker.contains(target)) {
                    hideEmployeeSearchResults(picker);
                }
            });
        });

        document.querySelectorAll('input[name="service_area_options[]"]').forEach((input) => {
            input.addEventListener('change', () => {
                syncServiceGroups();
                syncProductOptions();
            });
        });
        document.querySelectorAll('input[name="service_options[]"], input[name="product_options[]"]').forEach((input) => {
            input.addEventListener('change', syncCompositeFields);
        });
        templateSelect?.addEventListener('change', renderProjectTemplatePreview);
        ['project_client_name', 'project_business_name', 'project_client_confirmation_name', 'project_assigned_project_manager', 'project_assigned_consultant', 'project_assigned_associate', 'project_sales_marketing', 'project_finance'].forEach((id) => {
            document.getElementById(id)?.addEventListener('input', renderProjectTemplatePreview);
        });

        updateSourceUi();
        initEmployeeSearchPickers();
        syncProjectCustomerSearchUi();
        syncServiceGroups();
        syncProductOptions();
        syncCompositeFields();
        setManualSummary();
        renderProjectTemplatePreview();
        @if (isset($errors) && $errors->any())
            window.jkncSlideOver?.open(document.getElementById('projectManualCreateDrawer'));
        @endif
    })();
</script>

<script>
    (() => {
        // ── Search debounce ──────────────────────────────────────────
        const projectsSearchForm  = document.getElementById('projectsSearchForm');
        const projectsSearchInput = document.getElementById('projectsSearchInput');
        let projectSearchDebounce = null;

        const submitProjectSearch = () => projectsSearchForm?.submit();

        projectsSearchInput?.addEventListener('input', () => {
            clearTimeout(projectSearchDebounce);
            projectSearchDebounce = setTimeout(submitProjectSearch, 700);
        });
        projectsSearchInput?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                clearTimeout(projectSearchDebounce);
                submitProjectSearch();
            }
        });

        // ── Checkbox / Delete Selected ───────────────────────────────
        const selectAllCb          = document.getElementById('projectSelectAll');
        const openDeleteBtn        = document.getElementById('openProjectDeleteSelectedModal');
        const deleteModal          = document.getElementById('projectDeleteSelectedModal');
        const deleteOverlay        = document.getElementById('projectDeleteSelectedOverlay');
        const cancelDeleteBtn      = document.getElementById('cancelProjectDeleteSelectedModal');
        const bulkDeleteItems      = document.getElementById('projectBulkDeleteSelectedItems');
        const bulkDeleteCountText  = document.getElementById('projectBulkDeleteCountText');

        const rowCheckboxes = () => Array.from(document.querySelectorAll('.project-row-checkbox'));
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

        const closeProjectDeleteModal = () => {
            deleteModal?.classList.add('hidden');
            deleteModal?.setAttribute('aria-hidden', 'true');
        };

        const openProjectDeleteModal = () => {
            const ids = selectedIds();
            if (ids.length === 0 || !deleteModal) return;
            if (bulkDeleteItems) {
                bulkDeleteItems.innerHTML = ids.map(id =>
                    `<input type="hidden" name="selected_projects[]" value="${id}">`
                ).join('');
            }
            if (bulkDeleteCountText) {
                bulkDeleteCountText.textContent = `${ids.length} ${ids.length === 1 ? 'project' : 'projects'}`;
            }
            deleteModal.classList.remove('hidden');
            deleteModal.setAttribute('aria-hidden', 'false');
        };

        selectAllCb?.addEventListener('change', () => {
            rowCheckboxes().forEach(cb => { cb.checked = selectAllCb.checked; });
            syncDeleteButton();
        });

        rowCheckboxes().forEach(cb => cb.addEventListener('change', syncDeleteButton));

        openDeleteBtn?.addEventListener('click', openProjectDeleteModal);
        cancelDeleteBtn?.addEventListener('click', closeProjectDeleteModal);
        deleteOverlay?.addEventListener('click', closeProjectDeleteModal);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeProjectDeleteModal(); });
    })();

    let currentSelectedProjectStatus = 'Ongoing';
    let currentSelectedProjectStage = 'all';

    function updateStageCardCounts() {
        const rows = document.querySelectorAll('.project-data-row');
        const counts = {
            'all': 0,
            'work order': 0,
            'sow': 0,
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
            'sow': ['sow', 'sow preparation'],
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
            if (currentSelectedProjectStatus && rowStatus.toLowerCase() !== currentSelectedProjectStatus.toLowerCase()) {
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

    window.filterProjectStatus = function(statusTab, btn) {
        currentSelectedProjectStatus = statusTab;
        document.querySelectorAll('#projectStatusTabs button').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');

        // Reset stage filter back to 'all' when switching status tab
        currentSelectedProjectStage = 'all';
        document.querySelectorAll('#stageFilters .stage-filter-card').forEach(c => c.classList.remove('active'));
        document.querySelector('#stageFilters .stage-filter-card[data-stage="all"]')?.classList.add('active');
        const stageSelect = document.getElementById('filterStage');
        if (stageSelect) stageSelect.value = '';

        projectCurrentPage = 1;
        updateStageCardCounts();
        filterProjectRows();
    };

    window.filterProjectStage = function(stage, btn) {
        currentSelectedProjectStage = stage;
        document.querySelectorAll('#stageFilters .stage-filter-card').forEach(c => c.classList.remove('active'));
        if (btn) btn.classList.add('active');
        
        const stageSelect = document.getElementById('filterStage');
        if (stageSelect) {
            stageSelect.value = stage === 'all' ? '' : stage;
        }
        projectCurrentPage = 1;
        filterProjectRows();
    };

    window.filterProjectRows = function() {
        updateStageCardCounts();
        const q = (document.getElementById('q')?.value || '').toLowerCase().trim();
        const fromDate = document.getElementById('filterFrom')?.value || '';
        const toDate = document.getElementById('filterTo')?.value || '';
        const stage = document.getElementById('filterStage')?.value || (currentSelectedProjectStage === 'all' ? '' : currentSelectedProjectStage);
        const health = document.getElementById('filterHealth')?.value || '';
        const progress = document.getElementById('filterProgress')?.value || '';
        const lead = document.getElementById('filterLead')?.value || '';
        const associate = document.getElementById('filterAssociate')?.value || '';

        const rows = document.querySelectorAll('.project-data-row');
        let visibleCount = 0;

        const stageMap = {
            'work order': ['work order', 'start', 'intake'],
            'sow': ['sow', 'sow preparation'],
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

            if (currentSelectedProjectStatus && rowStatus !== currentSelectedProjectStatus) {
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

        applyProjectPagination();

        const countEl = document.getElementById('registryResultCount');
        if (countEl) countEl.textContent = `${visibleCount} project${visibleCount === 1 ? '' : 's'}`;

        const filterEl = document.getElementById('activeRegistryFilter');
        if (filterEl) {
            filterEl.textContent = stage && stage !== 'all' ? `Filtered by stage: ${stage}` : (q ? `Filtered by keyword: "${q}"` : 'All lifecycle stages');
        }
    };

    let projectCurrentPage = 1;
    let projectPageSize = 10;

    function applyProjectPagination() {
        const rows = Array.from(document.querySelectorAll('.project-data-row')).filter(r => r.dataset.filtered === 'true');
        const total = rows.length;
        const totalPages = Math.max(1, Math.ceil(total / projectPageSize));
        if (projectCurrentPage > totalPages) projectCurrentPage = totalPages;
        if (projectCurrentPage < 1) projectCurrentPage = 1;

        const startIdx = (projectCurrentPage - 1) * projectPageSize;
        const endIdx = startIdx + projectPageSize;

        rows.forEach((row, idx) => {
            if (idx >= startIdx && idx < endIdx) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        const summaryEl = document.getElementById('projectPaginationSummary');
        if (summaryEl) {
            if (total === 0) {
                summaryEl.textContent = 'Showing 0–0 of 0';
            } else {
                summaryEl.textContent = `Showing ${startIdx + 1}–${Math.min(endIdx, total)} of ${total}`;
            }
        }

        const prevBtn = document.getElementById('projectPrevBtn');
        const nextBtn = document.getElementById('projectNextBtn');
        if (prevBtn) prevBtn.disabled = projectCurrentPage <= 1;
        if (nextBtn) nextBtn.disabled = projectCurrentPage >= totalPages;
    }

    window.changeProjectPageSize = function(size) {
        projectPageSize = parseInt(size, 10) || 10;
        projectCurrentPage = 1;
        applyProjectPagination();
    };

    window.changeProjectPage = function(direction) {
        if (direction === 'prev') {
            projectCurrentPage = Math.max(1, projectCurrentPage - 1);
        } else if (direction === 'next') {
            projectCurrentPage++;
        }
        applyProjectPagination();
    };

    window.openCancelProjectModal = function(id, name, code) {
        const modal = document.getElementById('projectCancelModal');
        const form = document.getElementById('projectCancelForm');
        const nameText = document.getElementById('projectCancelNameText');
        const refText = document.getElementById('projectCancelRefText');
        const reasonInput = document.getElementById('projectCancelReason');
        if (!modal || !form) return;

        form.action = `/project/${id}/cancel`;
        if (nameText) nameText.textContent = name;
        if (refText) refText.textContent = code || `PROJ-${id}`;
        if (reasonInput) {
            reasonInput.value = '';
            setTimeout(() => reasonInput.focus(), 100);
        }
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
    };

    window.closeProjectCancelModal = function() {
        const modal = document.getElementById('projectCancelModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
        }
    };

    window.openSingleProjectDelete = function(id, name, code) {
        const deleteModal = document.getElementById('projectDeleteSelectedModal');
        const bulkDeleteItems = document.getElementById('projectBulkDeleteSelectedItems');
        const nameText = document.getElementById('projectDeleteNameText');
        const refText = document.getElementById('projectDeleteRefText');
        const reasonInput = document.getElementById('projectDeleteReason');
        if (!deleteModal) return;

        if (bulkDeleteItems) {
            bulkDeleteItems.innerHTML = `<input type="hidden" name="selected_projects[]" value="${id}">`;
        }
        if (nameText) nameText.textContent = name;
        if (refText) refText.textContent = code || `PROJ-${id}`;
        if (reasonInput) {
            reasonInput.value = '';
            setTimeout(() => reasonInput.focus(), 100);
        }
        deleteModal.classList.remove('hidden');
        deleteModal.setAttribute('aria-hidden', 'false');
    };

    window.closeProjectDeleteModal = function() {
        const deleteModal = document.getElementById('projectDeleteSelectedModal');
        if (deleteModal) {
            deleteModal.classList.add('hidden');
            deleteModal.setAttribute('aria-hidden', 'true');
        }
    };

    window.resetProjectFilters = function() {
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
        
        currentSelectedProjectStatus = 'Ongoing';
        document.querySelectorAll('#projectStatusTabs button').forEach(b => b.classList.remove('active'));
        document.querySelector('#projectStatusTabs button[data-status-tab="Ongoing"]')?.classList.add('active');

        currentSelectedProjectStage = 'all';
        document.querySelectorAll('.stage-filter-card').forEach(c => c.classList.remove('active'));
        document.querySelector('.stage-filter-card[data-stage="all"]')?.classList.add('active');

        projectCurrentPage = 1;
        filterProjectRows();
    };

    window.exportProjectTable = function(format) {
        const rows = document.querySelectorAll('.project-data-row');
        let csv = 'PROJECT,REFERENCE,BUSINESS,CLIENT,STAGE,HEALTH,PROGRESS,TARGET,LEAD\n';
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
        link.setAttribute('download', `project_registry_${new Date().toISOString().slice(0, 10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    document.addEventListener('DOMContentLoaded', () => {
        filterProjectRows();
    });
</script>
@endsection

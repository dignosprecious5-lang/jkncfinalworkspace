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
    <div class="mx-auto max-w-[1600px]">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">All Regular Services</h1>
                <p class="mt-1 max-w-3xl text-sm text-gray-500">
                    Central overview of every regular service from Work Order and RSAT through Review, NTP, Execution, RSAT Reporting, Delivery, transmittal, and formal completion.
                </p>
            </div>
            <button
                type="button"
                class="inline-flex h-11 items-center justify-center rounded-xl bg-[#102d79] px-5 text-sm font-bold text-white shadow-sm hover:bg-[#0d255f] transition"
                onclick="window.jkncSlideOver.open(document.getElementById('regularManualCreateDrawer'))"
            >
                + Create Regular
            </button>
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

        {{-- ORDO STAGE FILTER GRID --}}
        <div class="stage-filter-grid mb-4" id="stageFilters" aria-label="Filter regular services by lifecycle stage">
            <button type="button" class="stage-filter-card active" data-stage="all" onclick="filterRegularStage('all', this)">
                <span>All Regular</span>
                <strong>{{ $stats['all'] }}</strong>
                <small>Total records</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="RSAT" onclick="filterRegularStage('RSAT', this)">
                <span>Work Order / RSAT</span>
                <strong>{{ $stats['rsat'] }}</strong>
                <small>Setup & initiation</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="In Progress" onclick="filterRegularStage('In Progress', this)">
                <span>Execution / In Progress</span>
                <strong>{{ $stats['in_progress'] }}</strong>
                <small>Active operational</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="Active" onclick="filterRegularStage('Active', this)">
                <span>Active Lifecycle</span>
                <strong>{{ $stats['active'] }}</strong>
                <small>Pending & ongoing</small>
            </button>
            <button type="button" class="stage-filter-card" data-stage="Completed" onclick="filterRegularStage('Completed', this)">
                <span>Completed</span>
                <strong>{{ $stats['completed'] }}</strong>
                <small>Archived & closed</small>
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
                            class="registry-menu-button flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition"
                            id="registryMenuButton"
                            type="button"
                            onclick="document.getElementById('registryMenu').classList.toggle('open')"
                            aria-label="Registry options"
                        >
                            <i class="fas fa-ellipsis-v text-xs"></i>
                        </button>
                        <div class="registry-menu" id="registryMenu">
                            <button type="button" onclick="exportRegularTable('csv')" class="flex items-center gap-2 text-xs font-semibold text-slate-700 hover:text-blue-700">
                                <i class="fas fa-file-excel text-emerald-600"></i> Download CSV / Excel
                            </button>
                            <button type="button" onclick="window.print()" class="flex items-center gap-2 text-xs font-semibold text-slate-700 hover:text-blue-700">
                                <i class="fas fa-file-pdf text-rose-600"></i> Download / Print PDF
                            </button>
                            <div class="registry-menu-divider"></div>
                            <strong>Quick Actions</strong>
                            <button id="openRegularDeleteSelectedModal" type="button" class="hidden text-xs font-semibold text-rose-600 hover:bg-rose-50 p-2 rounded-lg text-left">
                                <i class="fas fa-trash-alt mr-1"></i> Delete Selected
                            </button>
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
                        <option value="RSAT">RSAT</option>
                        <option value="In Progress">In Progress</option>
                        <option value="For NTP Approval">For NTP Approval</option>
                        <option value="Execution">Execution</option>
                        <option value="Reporting">Reporting</option>
                        <option value="Delivery">Delivery</option>
                        <option value="Completed">Completed</option>
                    </select>
                </label>
                <label>
                    Health
                    <select id="filterHealth" onchange="filterRegularRows()">
                        <option value="">All health statuses</option>
                        <option value="On Track">On Track</option>
                        <option value="Needs Attention">Needs Attention</option>
                        <option value="At Risk">At Risk</option>
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
                            <option value="{{ $emp['name'] }}">{{ $emp['name'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Lead Associate
                    <select id="filterAssociate" onchange="filterRegularRows()">
                        <option value="">All associates</option>
                        @foreach ($employeeRecords as $emp)
                            <option value="{{ $emp['name'] }}">{{ $emp['name'] }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="btn inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition" type="button" id="clearRegistryFilters" onclick="resetRegularFilters()">
                    Clear Filters
                </button>
            </div>

            {{-- RESULT BAR --}}
            <div class="registry-result-bar">
                <span id="registryResultCount">{{ $regulars->count() }} regular services</span>
                <span id="activeRegistryFilter">All lifecycle stages</span>
            </div>

            {{-- REGISTRY TABLE --}}
            <div class="table-wrap overflow-x-auto">
                <table class="min-w-full text-xs" id="regularRegistryTable">
                    <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left w-10" data-column="select">
                                <input id="regularSelectAll" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-4 py-3 text-left font-bold uppercase tracking-wider" data-column="project">REGULAR / REFERENCE</th>
                            <th class="px-4 py-3 text-left font-bold uppercase tracking-wider" data-column="business">BUSINESS / CLIENT</th>
                            <th class="px-4 py-3 text-left font-bold uppercase tracking-wider" data-column="stage">CURRENT STAGE</th>
                            <th class="px-4 py-3 text-left font-bold uppercase tracking-wider" data-column="health">HEALTH</th>
                            <th class="px-4 py-3 text-left font-bold uppercase tracking-wider" data-column="progress">PROGRESS</th>
                            <th class="px-4 py-3 text-left font-bold uppercase tracking-wider" data-column="target">TARGET COMPLETION</th>
                            <th class="px-4 py-3 text-left font-bold uppercase tracking-wider" data-column="lead">REGULAR LEAD</th>
                            <th class="px-4 py-3 text-left font-bold uppercase tracking-wider" data-column="associate">LEAD ASSOCIATE</th>
                            <th class="px-4 py-3 text-left font-bold uppercase tracking-wider" data-column="assigned">ASSIGNED PERSONS</th>
                            <th class="px-4 py-3 text-right font-bold uppercase tracking-wider" data-column="action">ACTION</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700" id="rows">
                        @forelse ($regulars as $regular)
                            @php
                                $contactPerson = trim(collect([$regular->contact?->first_name, $regular->contact?->last_name])->filter()->implode(' ')) ?: ($regular->client_name ?: 'Client not recorded');
                                $businessName = $regular->company?->company_name ?: ($regular->business_name ?: 'No Business Recorded');
                                $isCompleted = $regular->status === 'Completed';
                                $progress = $isCompleted ? 100 : (in_array($regular->status, ['Execution', 'In Progress']) ? 65 : (in_array($regular->status, ['Reporting', 'Delivery']) ? 85 : 25));
                                $health = $isCompleted ? 'Completed' : 'On Track';
                                $targetDate = $regular->target_completion_date ? \Carbon\Carbon::parse($regular->target_completion_date)->format('Y-m-d') : '';
                                $regularLead = $regular->assigned_project_manager ?: ($regular->assigned_consultant ?: 'Unassigned');
                                $leadAssociate = $regular->assigned_associate ?: 'Unassigned';
                            @endphp
                            <tr class="regular-data-row hover:bg-slate-50/80 transition"
                                data-title="{{ strtolower($regular->name) }}"
                                data-ref="{{ strtolower($regular->project_code) }}"
                                data-deal="{{ strtolower($regular->deal?->deal_code ?? '') }}"
                                data-business="{{ strtolower($businessName) }}"
                                data-client="{{ strtolower($contactPerson) }}"
                                data-stage="{{ $regular->status }}"
                                data-health="{{ $health }}"
                                data-progress="{{ $progress }}"
                                data-lead="{{ $regularLead }}"
                                data-associate="{{ $leadAssociate }}"
                                data-target="{{ $targetDate }}">
                                <td class="px-4 py-3.5" data-column="select">
                                    <input type="checkbox" name="regular_checkbox" value="{{ $regular->id }}" class="regular-row-checkbox h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                </td>
                                <td class="px-4 py-3.5" data-column="project">
                                    <div class="font-bold text-slate-900">{{ $regular->name }}</div>
                                    <div class="text-[11px] text-slate-500 font-medium">{{ $regular->project_code }} · {{ $regular->deal?->deal_code ?? 'No Deal reference' }}</div>
                                </td>
                                <td class="px-4 py-3.5" data-column="business">
                                    <strong class="font-bold text-slate-900 block">{{ $businessName }}</strong>
                                    <div class="text-[11px] text-slate-500">{{ $contactPerson }}</div>
                                </td>
                                <td class="px-4 py-3.5" data-column="stage">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold {{ $phaseBadgeClasses[$regular->status] ?? 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                        {{ $regular->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5" data-column="health">
                                    <span class="inline-flex items-center gap-1.5 font-bold {{ $isCompleted ? 'text-emerald-700' : 'text-blue-700' }}">
                                        <span class="h-2 w-2 rounded-full {{ $isCompleted ? 'bg-emerald-500' : 'bg-blue-500' }}"></span>
                                        {{ $health }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5" data-column="progress">
                                    <div class="registry-progress">
                                        <div class="flex items-center justify-between text-[11px] font-bold text-slate-700 mb-1">
                                            <span>{{ $progress }}%</span>
                                        </div>
                                        <div class="h-1.5 w-24 rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-full bg-blue-600 rounded-full" style="width: {{ $progress }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 font-medium text-slate-600" data-column="target">
                                    {{ $regular->target_completion_date ? \Carbon\Carbon::parse($regular->target_completion_date)->format('M d, Y') : 'Not set' }}
                                </td>
                                <td class="px-4 py-3.5 font-medium text-slate-600" data-column="lead">
                                    {{ $regularLead }}
                                </td>
                                <td class="px-4 py-3.5 font-medium text-slate-600" data-column="associate">
                                    {{ $leadAssociate }}
                                </td>
                                <td class="px-4 py-3.5 font-medium text-slate-600" data-column="assigned">
                                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                                        <i class="fas fa-users text-[10px] text-slate-400"></i>
                                        {{ $regularLead !== 'Unassigned' ? $regularLead : 'Team' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right" data-column="action">
                                    <a href="{{ route('regular.show', $regular) }}" class="btn sm inline-flex items-center gap-1.5 rounded-xl bg-blue-700 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-blue-800 transition">
                                        Open Regular <i class="fas fa-arrow-right text-[10px]"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr id="noRecordsRow">
                                <td colspan="11" class="px-4 py-12 text-center text-sm text-slate-400">
                                    <i class="fas fa-folder-open text-3xl text-slate-300 block mb-2"></i>
                                    No regular engagements have been recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Delete Selected Modal --}}
        <div id="regularDeleteSelectedModal" class="fixed inset-0 z-[70] hidden" aria-hidden="true">
            <button id="regularDeleteSelectedOverlay" type="button" aria-label="Close delete regular modal" class="absolute inset-0 bg-slate-900/45"></button>
            <div class="absolute left-1/2 top-1/2 w-full max-w-md -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-xl font-semibold text-gray-900">Delete Selected Regular Engagements</h2>
                <p class="mt-1 text-sm text-gray-500">This action will permanently delete the selected regular engagement records.</p>
                <form id="regularBulkDeleteForm" method="POST" action="{{ route('regular.bulk-delete') }}">
                    @csrf
                    @method('DELETE')
                    <div id="regularBulkDeleteSelectedItems"></div>
                    <p class="mt-4 text-sm text-gray-700">Are you sure you want to delete <span id="regularBulkDeleteCountText" class="font-semibold text-gray-900">0 regular engagements</span>?</p>
                    <div class="mt-5 flex justify-end gap-3">
                        <button id="cancelRegularDeleteSelectedModal" type="button" class="h-10 rounded-lg border border-gray-300 px-4 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="h-10 rounded-lg bg-red-600 px-5 text-sm font-medium text-white hover:bg-red-700">Delete Selected</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<x-slide-over id="regularManualCreateDrawer" width="sm:max-w-[95vw]">
    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Create Regular</h2>
            <p class="mt-1 text-sm text-gray-500">Manually create a regular engagement and open the RSAT form to fill out details, scope, activities, and requirements.</p> 
        </div>
        <button type="button" class="rounded-full p-2 text-gray-500 hover:bg-gray-100" onclick="window.jkncSlideOver.close(document.getElementById('regularManualCreateDrawer'))">
            <span class="sr-only">Close</span>
            <i class="fas fa-times"></i>
        </button>
    </div>

    <form method="POST" action="{{ route('regular.manual.store') }}" class="flex h-full flex-col overflow-hidden">
        @csrf
        <input type="hidden" name="source_mode" id="regular_source_mode" value="{{ $oldSourceMode === 'deal' ? 'deal' : 'manual' }}">
        <input type="hidden" name="deal_id" id="regular_deal_id" value="{{ old('deal_id') }}">
        <input type="hidden" name="contact_id" id="regular_contact_id" value="{{ old('contact_id') }}">
        <input type="hidden" name="company_id" id="regular_company_id" value="{{ old('company_id') }}">
        <div class="flex-1 overflow-y-auto px-6 py-5">
            <div class="grid gap-4 xl:grid-cols-[52%,48%]">
                <aside class="min-w-0 xl:sticky xl:top-0 xl:self-start">
                    <div id="regularTemplatePreview" class="rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-emerald-50 p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">RSAT Form Preview</p>
                                <p id="regularTemplatePreviewName" class="mt-2 text-lg font-semibold text-slate-900">Blank Regular Form</p>
                            </div>
                            <span id="regularTemplatePreviewBadge" class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600">Default</span>
                        </div>
                        <div class="mt-4 max-h-[calc(100vh-220px)] overflow-y-auto rounded-2xl border border-[#d7deea] bg-white p-3 shadow-sm xl:scale-[1.02] xl:origin-top-left">
                            <div class="border-2 border-[#1c4587] bg-white p-3">
                                <div class="grid grid-cols-[88px_minmax(0,1fr)] gap-3 items-start">
                                    <div>
                                        <img src="{{ asset('images/imaglogo.png') }}" alt="John Kelly and Company" class="h-12 w-auto object-contain">
                                    </div>
                                    <div class="text-right">
                                        <div class="font-[Georgia] text-[16px] font-bold uppercase leading-tight text-slate-900">Regular Service Activity Tracker (RSAT)</div>
                                        <div class="mt-1 font-[Georgia] text-[10px] text-slate-500">REG-F-001</div>
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-x-3 gap-y-2 text-[9px] font-[Georgia] text-slate-900">
                                    <div class="grid grid-cols-[82px_minmax(0,1fr)] gap-2 items-end">
                                        <div class="uppercase text-slate-500">Client Name:</div>
                                        <div id="regularTemplateMetaClient" class="border-b border-slate-900 pb-1 font-semibold">Pending selection</div>
                                    </div>
                                    <div class="grid grid-cols-[82px_minmax(0,1fr)] gap-2 items-end">
                                        <div class="uppercase text-slate-500">Business:</div>
                                        <div id="regularTemplateMetaBusiness" class="border-b border-slate-900 pb-1 font-semibold">Pending selection</div>
                                    </div>
                                    <div class="grid grid-cols-[82px_minmax(0,1fr)] gap-2 items-end">
                                        <div class="uppercase text-slate-500">Services:</div>
                                        <div id="regularTemplateMetaServices" class="border-b border-slate-900 pb-1 font-semibold">To be filled</div>
                                    </div>
                                    <div class="grid grid-cols-[82px_minmax(0,1fr)] gap-2 items-end">
                                        <div class="uppercase text-slate-500">Product:</div>
                                        <div id="regularTemplateMetaProducts" class="border-b border-slate-900 pb-1 font-semibold">To be filled</div>
                                    </div>
                                </div>

                                <div class="mt-4 overflow-hidden">
                                    <table class="w-full table-fixed border-collapse text-[8px] font-[Georgia] text-slate-900">
                                        <thead>
                                            <tr>
                                                <th class="border border-slate-900 bg-[#1c4587] px-1 py-1 text-white">#</th>
                                                <th class="border border-slate-900 bg-[#1c4587] px-1 py-1 text-white">Service</th>
                                                <th class="border border-slate-900 bg-[#1c4587] px-1 py-1 text-white">Activity / Output</th>
                                                <th class="border border-slate-900 bg-[#1c4587] px-1 py-1 text-white">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="regularTemplateRequirements"></tbody>
                                    </table>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-3 text-[9px] font-[Georgia] text-slate-900">
                                    <div class="border border-slate-200 px-2 py-2">
                                        <div class="uppercase text-slate-500">Approval Flow</div>
                                        <div id="regularTemplatePreviewStatuses" class="mt-1 font-semibold">0 approval steps</div>
                                    </div>
                                    <div class="border border-slate-200 px-2 py-2">
                                        <div class="uppercase text-slate-500">Clearance</div>
                                        <div id="regularTemplatePreviewClearance" class="mt-1 font-semibold">0 clearance items</div>
                                    </div>
                                </div>

                                <div class="mt-4 bg-[#1c4587] px-2 py-1 text-center font-[Georgia] text-[11px] font-bold uppercase tracking-[0.08em] text-white">Attachments</div>
                                <div class="border border-slate-900 border-t-0 px-3 py-3 font-[Georgia] text-[9px] text-slate-900">
                                    <div class="rounded border border-dashed border-slate-300 px-3 py-2 text-center text-slate-500">Supporting files area</div>
                                </div>

                                <div class="mt-4 border border-slate-900 px-3 py-5 text-center font-[Georgia] text-[9px] text-slate-900">
                                    <div class="mx-auto w-[70%] border-b border-slate-900 pb-1 font-semibold" id="regularTemplateSignatureName">Client fullname & signature</div>
                                    <div class="mt-2 italic">Client Fullname & Signature</div>
                                </div>

                                <div class="mt-4 bg-[#1c4587] px-2 py-1 text-center font-[Georgia] text-[11px] font-bold uppercase tracking-[0.08em] text-white">Internal Approval</div>
                                <div class="grid grid-cols-2 border-l border-r border-b border-slate-900 font-[Georgia] text-[9px] text-slate-900">
                                    <div class="border-r border-slate-900 px-3 py-3">
                                        <div class="text-slate-500 italic">Prepared By</div>
                                        <div id="regularTemplatePreparedBy" class="mt-3 border-b border-slate-900 pb-1 min-h-[18px]"></div>
                                    </div>
                                    <div class="px-3 py-3">
                                        <div class="text-slate-500 italic">Reviewed By</div>
                                        <div id="regularTemplateReviewedBy" class="mt-3 border-b border-slate-900 pb-1 min-h-[18px]"></div>
                                    </div>
                                </div>

                                <div class="mt-4 bg-[#1c4587] px-2 py-1 text-center font-[Georgia] text-[11px] font-bold uppercase tracking-[0.08em] text-white">Record & Clearance</div>
                                <div class="grid grid-cols-2 border-l border-r border-b border-slate-900 font-[Georgia] text-[9px] text-slate-900">
                                    <div class="border-r border-slate-900 px-3 py-3">
                                        <div>Date Recorded: ____________________</div>
                                        <div class="mt-2">Date Signed: ____________________</div>
                                    </div>
                                    <div class="px-3 py-3">
                                        <div>Record Custodian: ____________________</div>
                                        <div class="mt-2">Sales & Marketing: ____________________</div>
                                    </div>
                                </div>

                                <div class="mt-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-3 text-[10px] text-slate-600">
                                    <span id="regularTemplatePreviewEffect">The regular engagement will start from a blank/default RSAT structure.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
                <div class="min-w-0 max-w-[720px] justify-self-end space-y-5">
            <section class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm font-semibold text-gray-900">How do you want to create this regular engagement?</p>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <button type="button" data-regular-source-option="deal" class="regular-source-option rounded-2xl border px-4 py-4 text-left transition {{ $oldSourceMode === 'deal' ? 'border-[#102d79] bg-white ring-2 ring-[#102d79]/10' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                        <span class="block text-sm font-semibold text-gray-900">Link Existing Deal</span>
                        <span class="mt-1 block text-xs text-gray-500">Pick a regular deal and preload its client, company, and staffing details.</span>
                    </button>
                    <button type="button" data-regular-source-option="manual" class="regular-source-option rounded-2xl border px-4 py-4 text-left transition {{ $oldSourceMode !== 'deal' ? 'border-[#102d79] bg-white ring-2 ring-[#102d79]/10' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                        <span class="block text-sm font-semibold text-gray-900">Manual</span>
                        <span class="mt-1 block text-xs text-gray-500">Start manually, then optionally select an existing contact or company.</span>
                    </button>
                </div>
            </section>

            <section id="regularDealLinkSection" class="space-y-3 {{ $oldSourceMode === 'deal' ? '' : 'hidden' }}">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Search Existing Deal</label>
                    <input id="regularDealSearch" type="text" placeholder="Type deal code, deal name, client, or company..." autocomplete="off" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                    <p class="mt-2 text-xs text-gray-500">Only regular deals without a linked regular workspace are shown here.</p>
                </div>
                <div id="regularDealResults" class="hidden max-h-64 overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-sm"></div>
                <div id="regularDealSelectionSummary" class="{{ old('deal_id') ? '' : 'hidden' }} rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900"></div>
            </section>

            <section id="regularManualLinkSection" class="space-y-4 {{ $oldSourceMode === 'deal' ? 'hidden' : '' }}">
                <div class="rounded-2xl border border-gray-200 p-4">
                    <h3 class="text-base font-semibold text-gray-900">Customer Type</h3>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        @foreach (['business' => 'Business', 'individual' => 'Individual'] as $value => $label)
                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                <input type="radio" name="regular_customer_type" value="{{ $value }}" @checked(old('regular_customer_type', 'individual') === $value) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h3 id="regularSelectionSectionTitle" class="text-base font-semibold text-gray-900">Select Existing Contact / Client</h3>
                    <p id="regularSearchHelpText" class="mt-1 text-xs text-gray-500">Select a customer type, then search the matching records.</p>
                </div>
                <div class="relative">
                    <label id="regularContactSearchLabel" class="mb-2 block text-sm font-medium text-gray-700" for="regularContactSearch">Search Existing Client</label>
                    <input id="regularContactSearch" type="text" placeholder="Type name, company, email, or mobile..." autocomplete="off" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                    <div id="regularContactResults" class="mt-2 hidden max-h-64 overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-sm"></div>
                </div>
                <div id="regularManualSelectionSummary" class="{{ old('contact_id') || old('company_id') ? '' : 'hidden' }} rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"></div>
            </section>

            <div class="grid gap-3 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700">RSAT Template</label>
                    <select name="template_id" id="regular_template_id" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                        <option value="">Start from blank/default</option>
                        @foreach ($rsatTemplates as $template)
                            <option value="{{ $template->id }}" @selected((string) old('template_id') === (string) $template->id)>{{ $template->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-500">Choose a saved RSAT template to prefill the first checklist and review flow for this regular engagement.</p>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Regular Name</label>
                    <input name="name" id="regular_name" value="{{ old('name') }}" required class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Client Name</label>
                    <input name="client_name" id="regular_client_name" value="{{ old('client_name') }}" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Business Name</label>
                    <input name="business_name" id="regular_business_name" value="{{ old('business_name') }}" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Planned Start</label>
                    <input type="date" name="planned_start_date" id="regular_planned_start_date" value="{{ old('planned_start_date') }}" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Target Completion</label>
                    <input type="date" name="target_completion_date" id="regular_target_completion_date" value="{{ old('target_completion_date') }}" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Client Confirmation Name</label>
                    <input name="client_confirmation_name" id="regular_client_confirmation_name" value="{{ old('client_confirmation_name') }}" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                </div>
                <div class="relative" data-employee-picker>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Project Manager</label>
                    <input name="assigned_project_manager" id="regular_assigned_project_manager" value="{{ old('assigned_project_manager') }}" autocomplete="off" data-employee-search-input class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                    <div class="absolute z-20 mt-1 hidden w-full rounded-xl border border-gray-200 bg-white shadow-lg" data-employee-search-results></div>
                </div>
                <div class="relative" data-employee-picker>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Lead Consultant</label>
                    <input name="assigned_consultant" id="regular_assigned_consultant" value="{{ old('assigned_consultant') }}" autocomplete="off" data-employee-search-input class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                    <div class="absolute z-20 mt-1 hidden w-full rounded-xl border border-gray-200 bg-white shadow-lg" data-employee-search-results></div>
                </div>
                <div class="relative md:col-span-2" data-employee-picker>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Lead Associate</label>
                    <input name="assigned_associate" id="regular_assigned_associate" value="{{ old('assigned_associate') }}" autocomplete="off" data-employee-search-input class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                    <div class="absolute z-20 mt-1 hidden w-full rounded-xl border border-gray-200 bg-white shadow-lg" data-employee-search-results></div>
                </div>
                <div class="relative md:col-span-2" data-employee-picker>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Sales &amp; Marketing</label>
                    <input name="sales_marketing" id="regular_sales_marketing" value="{{ old('sales_marketing') }}" autocomplete="off" data-employee-search-input class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                    <div class="absolute z-20 mt-1 hidden w-full rounded-xl border border-gray-200 bg-white shadow-lg" data-employee-search-results></div>
                </div>
                <div class="relative md:col-span-2" data-employee-picker>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Finance</label>
                    <input name="finance" id="regular_finance" value="{{ old('finance') }}" autocomplete="off" data-employee-search-input class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                    <div class="absolute z-20 mt-1 hidden w-full rounded-xl border border-gray-200 bg-white shadow-lg" data-employee-search-results></div>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700">RSAT Activities / Requirements</label>
                    <textarea name="engagement_requirements_text" rows="5" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900" placeholder="One requirement per line">{{ old('engagement_requirements_text') }}</textarea>
                </div>
                <input type="hidden" name="service_area" id="regular_service_area" value="{{ old('service_area') }}">
                <textarea name="services" id="regular_services" class="hidden">{{ old('services') }}</textarea>
                <textarea name="products" id="regular_products" class="hidden">{{ old('products') }}</textarea>
            </div>

            <section class="rounded-2xl border border-gray-200 p-4">
                <h3 class="text-base font-semibold text-gray-900">Service Identification</h3>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Service Area</label>
                        <div id="regular-service-area-options-grid" class="grid gap-2 sm:grid-cols-2">
                            @foreach ($serviceAreaOptions as $option)
                                @if ($option !== 'Others')
                                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                        <input type="checkbox" name="service_area_options[]" value="{{ $option }}" @checked(in_array($option, $selectedServiceAreas, true)) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span>{{ $option }}</span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Services</label>
                        <div id="regularServicesGrid" class="mt-3 grid gap-4 lg:grid-cols-2 {{ count($selectedServiceAreas) > 0 ? '' : 'hidden' }}">
                            @foreach ($serviceGroups as $group => $options)
                                <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-3 {{ in_array($group, $selectedServiceAreas, true) ? '' : 'hidden' }}" data-regular-service-group="{{ $group }}">
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-600">{{ $group }}</p>
                                    <div class="space-y-2">
                                        @foreach ($options as $option)
                                            <label class="flex items-start gap-2 text-sm text-gray-700">
                                                <input type="checkbox" name="service_options[]" value="{{ $option }}" @checked(in_array($option, $selectedServices, true)) class="mt-0.5 h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <span>{{ $option }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Products</label>
                        <p class="mt-1 text-xs text-gray-500">Products follow the selected service area. Without a selected service area, only products without a service area are shown.</p>
                        <div id="regularProductsEmptyState" class="mt-3 hidden rounded-xl border border-dashed border-gray-200 bg-gray-50/60 p-4 text-sm text-gray-500">No matching products are available.</div>
                        <div id="regular-product-options-grid" class="mt-3 grid gap-4">
                            @foreach ($productOptionsByServiceArea as $serviceArea => $options)
                                <div data-regular-product-group="{{ $serviceArea }}" data-product-unlinked-group="{{ $serviceArea === 'Products Without Service Area' ? 'true' : 'false' }}">
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-600">{{ $serviceArea }}</p>
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        @foreach ($options as $option)
                                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700" data-regular-product-option data-product-value="{{ $option }}" data-regular-product-search="{{ \Illuminate\Support\Str::lower($option.' '.$serviceArea) }}">
                                                <input type="checkbox" name="product_options[]" value="{{ $option }}" @checked(in_array($option, $selectedProducts, true)) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <span>{{ $option }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-200 px-6 py-4">
            <div class="flex items-center justify-end gap-3">
                <button type="button" class="inline-flex h-11 items-center rounded-full border border-gray-300 px-5 text-sm font-medium text-gray-700 hover:bg-gray-50" onclick="window.jkncSlideOver.close(document.getElementById('regularManualCreateDrawer'))">Cancel</button>
                <button type="submit" class="inline-flex h-11 items-center rounded-full bg-[#102d79] px-5 text-sm font-semibold text-white hover:bg-[#0d255f]">Create</button>
            </div>
        </div>
    </form>
</x-slide-over>

<datalist id="regularEmployeeOptions">
    @foreach (($employeeRecords ?? []) as $employee)
        <option value="{{ $employee['label'] }}">{{ $employee['position'] ?? '' }}{{ !empty($employee['employee_code']) ? ' - '.$employee['employee_code'] : '' }}</option>
    @endforeach
</datalist>

<script>
    (() => {
        const dealRecords = @json($dealRecords ?? []);
        const contactRecords = @json($contactRecords ?? []);
        const companyRecords = @json($companyRecords ?? []);
        const employeeRecords = @json($employeeRecords ?? []);
        const rsatTemplatePreviewData = @json($rsatTemplatePreviewData);
        const sourceModeInput = document.getElementById('regular_source_mode');
        const dealIdInput = document.getElementById('regular_deal_id');
        const contactIdInput = document.getElementById('regular_contact_id');
        const companyIdInput = document.getElementById('regular_company_id');
        const dealSection = document.getElementById('regularDealLinkSection');
        const manualSection = document.getElementById('regularManualLinkSection');
        const dealSearch = document.getElementById('regularDealSearch');
        const dealResults = document.getElementById('regularDealResults');
        const dealSummary = document.getElementById('regularDealSelectionSummary');
        const contactSearch = document.getElementById('regularContactSearch');
        const contactResults = document.getElementById('regularContactResults');
        const templateSelect = document.getElementById('regular_template_id');
        const templatePreview = document.getElementById('regularTemplatePreview');
        const templatePreviewName = document.getElementById('regularTemplatePreviewName');
        const templatePreviewBadge = document.getElementById('regularTemplatePreviewBadge');
        const templateMetaClient = document.getElementById('regularTemplateMetaClient');
        const templateMetaBusiness = document.getElementById('regularTemplateMetaBusiness');
        const templateMetaServices = document.getElementById('regularTemplateMetaServices');
        const templateMetaProducts = document.getElementById('regularTemplateMetaProducts');
        const templatePreviewStatuses = document.getElementById('regularTemplatePreviewStatuses');
        const templatePreviewClearance = document.getElementById('regularTemplatePreviewClearance');
        const templatePreviewEffect = document.getElementById('regularTemplatePreviewEffect');
        const templateSignatureName = document.getElementById('regularTemplateSignatureName');
        const templatePreparedBy = document.getElementById('regularTemplatePreparedBy');
        const templateReviewedBy = document.getElementById('regularTemplateReviewedBy');
        const templateRequirements = document.getElementById('regularTemplateRequirements');
        const manualSummary = document.getElementById('regularManualSelectionSummary');
        const sourceButtons = Array.from(document.querySelectorAll('[data-regular-source-option]'));
        const customerTypeInputs = Array.from(document.querySelectorAll('input[name="regular_customer_type"]'));
        const contactSearchLabel = document.getElementById('regularContactSearchLabel');
        const selectionSectionTitle = document.getElementById('regularSelectionSectionTitle');
        const searchHelpText = document.getElementById('regularSearchHelpText');
        const serviceAreaChecks = Array.from(document.querySelectorAll('input[name="service_area_options[]"]'));
        const serviceChecks = Array.from(document.querySelectorAll('input[name="service_options[]"]'));
        const productChecks = Array.from(document.querySelectorAll('input[name="product_options[]"]'));
        const selectedState = {
            contact: null,
            company: null,
        };

        const setValue = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.value = value ?? '';
        };

        const selectedRegularCustomerType = () => document.querySelector('input[name="regular_customer_type"]:checked')?.value || '';

        const selectedRegularServiceAreas = () => Array.from(document.querySelectorAll('input[name="service_area_options[]"]:checked'))
            .map((input) => input.value)
            .filter((value) => value !== 'Others');

        const applyCommonValues = (record) => {
            setValue('regular_client_name', record.client_name || record.label || '');
            setValue('regular_business_name', record.business_name || record.company_name || '');
            setValue('regular_planned_start_date', record.planned_start_date || '');
            setValue('regular_target_completion_date', record.target_completion_date || '');
            setValue('regular_assigned_project_manager', record.assigned_project_manager || '');
            setValue('regular_assigned_consultant', record.assigned_consultant || '');
            setValue('regular_assigned_associate', record.assigned_associate || '');
            setValue('regular_sales_marketing', record.sales_marketing || '');
            setValue('regular_finance', record.finance || '');
            setValue('regular_client_confirmation_name', record.client_confirmation_name || record.client_name || '');
            setValue('regular_service_area', record.service_area || '');
            setValue('regular_services', record.services || '');
            setValue('regular_products', record.products || '');
            renderRegularTemplatePreview();
        };

        const setSourceMode = (mode) => {
            if (sourceModeInput) sourceModeInput.value = mode;
            dealSection?.classList.toggle('hidden', mode !== 'deal');
            manualSection?.classList.toggle('hidden', mode === 'deal');
            sourceButtons.forEach((button) => {
                const active = button.dataset.regularSourceOption === mode;
                button.classList.toggle('border-[#102d79]', active);
                button.classList.toggle('ring-2', active);
                button.classList.toggle('ring-[#102d79]/10', active);
            });
        };

        const syncRegularCustomerSearchUi = () => {
            const customerType = selectedRegularCustomerType();
            const isBusiness = customerType === 'business';

            if (contactSearchLabel) {
                contactSearchLabel.textContent = isBusiness ? 'Search Existing Business / Company' : 'Search Existing Client';
            }

            if (selectionSectionTitle) {
                selectionSectionTitle.textContent = isBusiness ? 'Select Existing Business / Company' : 'Select Existing Contact / Client';
            }

            if (searchHelpText) {
                searchHelpText.textContent = isBusiness
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
            if (manualSummary) {
                manualSummary.classList.add('hidden');
                manualSummary.textContent = '';
            }
        };

        const renderDealResults = (keyword) => {
            const term = String(keyword || '').trim().toLowerCase();
            const matches = dealRecords.filter((record) => term === '' || String(record.search_blob || '').includes(term)).slice(0, 8);
            if (!dealResults) return;
            if (matches.length === 0) {
                dealResults.classList.add('hidden');
                dealResults.innerHTML = '';
                return;
            }
            dealResults.classList.remove('hidden');
            dealResults.innerHTML = matches.map((record) => `
                <button type="button" class="block w-full border-b border-gray-100 px-4 py-3 text-left hover:bg-gray-50" data-regular-deal-id="${record.id}">
                    <div class="text-sm font-semibold text-gray-900">${record.deal_code || record.label}</div>
                    <div class="text-xs text-gray-500">${record.client_name || ''} ${record.business_name ? '• ' + record.business_name : ''}</div>
                </button>
            `).join('');
            dealResults.querySelectorAll('[data-regular-deal-id]').forEach((button) => {
                button.addEventListener('click', () => {
                    const record = matches.find((item) => String(item.id) === String(button.dataset.regularDealId));
                    if (!record) return;
                    dealIdInput.value = record.id;
                    contactIdInput.value = record.contact_id || '';
                    applyCommonValues(record);
                    if (dealSummary) {
                        dealSummary.classList.remove('hidden');
                        dealSummary.textContent = `${record.deal_code || record.label} linked. Client and service details were prefilled.`;
                    }
                    dealResults.classList.add('hidden');
                });
            });
        };

        const renderContactResults = (keyword) => {
            const term = String(keyword || '').trim().toLowerCase();
            const customerType = selectedRegularCustomerType();
            if (!contactResults) return;

            if (!customerType) {
                contactResults.innerHTML = '<div class="px-4 py-3 text-sm text-gray-500">Select a customer type first.</div>';
                contactResults.classList.remove('hidden');
                return;
            }

            const searchableRecords = customerType === 'business'
                ? companyRecords.map((record) => ({ ...record, record_type: 'company' }))
                : contactRecords.map((record) => ({ ...record, record_type: 'contact' }));
            const matches = searchableRecords.filter((record) => term === '' || String(record.search_blob || record.label || '').toLowerCase().includes(term)).slice(0, 20);
            if (matches.length === 0) {
                contactResults.innerHTML = `<div class="px-4 py-3 text-sm text-gray-500">No matching ${customerType === 'business' ? 'companies' : 'contacts'} found.</div>`;
                contactResults.classList.remove('hidden');
                return;
            }
            contactResults.classList.remove('hidden');
            contactResults.innerHTML = matches.map((record) => `
                <button type="button" class="block w-full border-b border-gray-100 px-4 py-3 text-left hover:bg-gray-50">
                    <div class="text-sm font-semibold text-gray-900">${record.label || record.company_name || ''}</div>
                    <div class="text-xs text-gray-500">${record.record_type === 'company' ? [record.primary_contact_name || record.owner_name, record.email || record.mobile].filter(Boolean).join(' - ') : [record.company_name, record.email || record.mobile].filter(Boolean).join(' - ')}</div>
                </button>
            `).join('');
            contactResults.querySelectorAll('button').forEach((button, index) => {
                button.addEventListener('click', () => {
                    const record = matches[index];
                    if (!record) return;
                    if (record.record_type === 'company') {
                        companyIdInput.value = record.id;
                        selectedState.company = record;
                        if (record.primary_contact_id) {
                            contactIdInput.value = record.primary_contact_id;
                            selectedState.contact = contactRecords.find((item) => Number(item.id) === Number(record.primary_contact_id)) || null;
                        }
                        contactSearch.value = record.company_name || record.label || '';
                    } else {
                        contactIdInput.value = record.id;
                        selectedState.contact = record;
                        const linkedCompany = record.company_name
                            ? companyRecords.find((item) => (item.company_name || '') === record.company_name)
                            : null;
                        companyIdInput.value = linkedCompany?.id || '';
                        selectedState.company = linkedCompany || null;
                        contactSearch.value = record.label || '';
                    }
                    applyCommonValues({
                        client_name: record.record_type === 'company' ? (record.primary_contact_name || '') : record.label,
                        business_name: record.record_type === 'company' ? record.company_name : record.company_name,
                        client_confirmation_name: record.record_type === 'company' ? (record.primary_contact_name || '') : record.label,
                    });
                    if (manualSummary) {
                        manualSummary.classList.remove('hidden');
                        manualSummary.textContent = `${record.label || record.company_name} selected.`;
                    }
                    contactResults.classList.add('hidden');
                });
            });
        };

        const syncSelections = () => {
            const areas = selectedRegularServiceAreas();
            const services = serviceChecks.filter((item) => item.checked).map((item) => item.value);
            const productSearchTerm = '';
            const seenProducts = new Set();
            let visibleProductCount = 0;
            setValue('regular_service_area', areas.join(', '));
            setValue('regular_services', services.join(', '));
            document.querySelectorAll('[data-regular-service-group]').forEach((group) => {
                group.classList.toggle('hidden', !areas.includes(group.dataset.regularServiceGroup));
            });
            document.querySelectorAll('[data-regular-product-group]').forEach((group) => {
                let groupHasVisibleProduct = false;
                const groupArea = group.getAttribute('data-regular-product-group') || '';
                const isUnlinkedGroup = group.dataset.productUnlinkedGroup === 'true';
                const groupMatchesArea = areas.length === 0
                    ? isUnlinkedGroup
                    : areas.includes(groupArea);

                group.querySelectorAll('[data-regular-product-option]').forEach((option) => {
                    const optionMatchesSearch = productSearchTerm === '' || String(option.dataset.regularProductSearch || '').includes(productSearchTerm);
                    const productValue = String(option.dataset.productValue || option.querySelector('input[name="product_options[]"]')?.value || '').trim().toLowerCase();
                    const isDuplicate = productValue !== '' && seenProducts.has(productValue);
                    const optionVisible = groupMatchesArea && optionMatchesSearch && !isDuplicate;
                    option.classList.toggle('hidden', !optionVisible);
                    groupHasVisibleProduct = groupHasVisibleProduct || optionVisible;
                    if (optionVisible && productValue !== '') {
                        seenProducts.add(productValue);
                    }
                    if (!optionVisible) {
                        const productInput = option.querySelector('input[name="product_options[]"]');
                        if (productInput) {
                            productInput.checked = false;
                        }
                    }
                });

                group.classList.toggle('hidden', !groupHasVisibleProduct);
                if (groupHasVisibleProduct) {
                    visibleProductCount += 1;
                }
            });
            document.getElementById('regularProductsEmptyState')?.classList.toggle('hidden', visibleProductCount > 0);
            setValue('regular_products', productChecks.filter((item) => item.checked).map((item) => item.value).join(', '));
            document.getElementById('regularServicesGrid')?.classList.toggle('hidden', areas.length === 0);
            renderRegularTemplatePreview();
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
                    renderRegularTemplatePreview();
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
                input.addEventListener('change', renderRegularTemplatePreview);
                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        hideEmployeeSearchResults(picker);
                    }
                });
            });
        };

        const renderRegularTemplatePreview = () => {
            if (!templateSelect || !templatePreview) {
                return;
            }

            const template = rsatTemplatePreviewData[String(templateSelect.value || '')];
            if (!template) {
                templatePreviewName.textContent = 'Blank Regular Form';
                templatePreviewBadge.textContent = 'Default';
                templateMetaClient.textContent = document.getElementById('regular_client_name')?.value || 'Pending selection';
                templateMetaBusiness.textContent = document.getElementById('regular_business_name')?.value || 'Pending selection';
                templateMetaServices.textContent = document.getElementById('regular_services')?.value || 'To be filled';
                templateMetaProducts.textContent = document.getElementById('regular_products')?.value || 'To be filled';
                templatePreviewStatuses.textContent = '0 approval steps';
                templatePreviewClearance.textContent = '0 clearance items';
                templateSignatureName.textContent = document.getElementById('regular_client_confirmation_name')?.value || 'Client fullname & signature';
                templatePreparedBy.textContent = document.getElementById('regular_assigned_associate')?.value || document.getElementById('regular_assigned_consultant')?.value || '';
                templateReviewedBy.textContent = document.getElementById('regular_assigned_project_manager')?.value || '';
                templateRequirements.innerHTML = '<tr><td colspan="4" class="border border-slate-900 px-2 py-2 text-center text-slate-500">No requirements yet.</td></tr>';
                templatePreviewEffect.textContent = 'The regular engagement will start from a blank/default RSAT structure.';
                return;
            }

            templatePreviewName.textContent = template.name || 'Selected template';
            templatePreviewBadge.textContent = `${template.requirement_count || 0} reqs`;
            templateMetaClient.textContent = document.getElementById('regular_client_name')?.value || 'Pending selection';
            templateMetaBusiness.textContent = document.getElementById('regular_business_name')?.value || 'Pending selection';
            templateMetaServices.textContent = document.getElementById('regular_services')?.value || 'To be filled';
            templateMetaProducts.textContent = document.getElementById('regular_products')?.value || 'To be filled';
            templatePreviewStatuses.textContent = `${template.approval_step_count || 0} approval step(s)`;
            templatePreviewClearance.textContent = `${template.clearance_count || 0} clearance item(s)`;
            templateSignatureName.textContent = document.getElementById('regular_client_confirmation_name')?.value || 'Client fullname & signature';
            templatePreparedBy.textContent = document.getElementById('regular_assigned_associate')?.value || document.getElementById('regular_assigned_consultant')?.value || '';
            templateReviewedBy.textContent = document.getElementById('regular_assigned_project_manager')?.value || '';

            const requirements = Array.isArray(template.requirements) ? template.requirements : [];
            if (requirements.length === 0) {
                templateRequirements.innerHTML = '<tr><td colspan="4" class="border border-slate-900 px-2 py-2 text-center text-slate-500">No saved requirements in this template.</td></tr>';
            } else {
                templateRequirements.innerHTML = requirements
                    .map((item, index) => `<tr>
                        <td class="border border-slate-900 px-1 py-1.5 text-center">${index + 1}</td>
                        <td class="border border-slate-900 px-1 py-1.5">Recurring Service</td>
                        <td class="border border-slate-900 px-1 py-1.5">${item}</td>
                        <td class="border border-slate-900 px-1 py-1.5 text-center">${index === 0 ? 'Open' : (index === 1 ? 'In Progress' : 'Pending')}</td>
                    </tr>`)
                    .join('');
            }

            templatePreviewEffect.textContent = `This template will prefill ${template.requirement_count || 0} requirement line(s), plus ${template.approval_step_count || 0} approval step(s) and ${template.clearance_count || 0} clearance item(s).`;
        };

        sourceButtons.forEach((button) => button.addEventListener('click', () => setSourceMode(button.dataset.regularSourceOption)));
        dealSearch?.addEventListener('focus', () => renderDealResults(dealSearch.value));
        dealSearch?.addEventListener('input', () => renderDealResults(dealSearch.value));
        contactSearch?.addEventListener('focus', () => renderContactResults(contactSearch.value));
        contactSearch?.addEventListener('input', () => renderContactResults(contactSearch.value));
        customerTypeInputs.forEach((input) => input.addEventListener('change', syncRegularCustomerSearchUi));
        serviceAreaChecks.forEach((item) => item.addEventListener('change', syncSelections));
        serviceChecks.forEach((item) => item.addEventListener('change', syncSelections));
        productChecks.forEach((item) => item.addEventListener('change', syncSelections));
        templateSelect?.addEventListener('change', renderRegularTemplatePreview);
        ['regular_client_name', 'regular_business_name', 'regular_client_confirmation_name', 'regular_assigned_project_manager', 'regular_assigned_consultant', 'regular_assigned_associate', 'regular_sales_marketing', 'regular_finance'].forEach((id) => {
            document.getElementById(id)?.addEventListener('input', renderRegularTemplatePreview);
        });

        setSourceMode(sourceModeInput?.value || 'manual');
        syncRegularCustomerSearchUi();
        initEmployeeSearchPickers();
        syncSelections();
        renderRegularTemplatePreview();
        @if (isset($errors) && $errors->any())
            window.jkncSlideOver?.open(document.getElementById('regularManualCreateDrawer'));
        @endif

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
    })();
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

    let currentSelectedStage = 'all';

    window.filterRegularStage = function(stage, btn) {
        currentSelectedStage = stage;
        document.querySelectorAll('.stage-filter-card').forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        
        const stageSelect = document.getElementById('filterStage');
        if (stageSelect) {
            stageSelect.value = stage === 'all' ? '' : stage;
        }
        filterRegularRows();
    };

    window.filterRegularRows = function() {
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

        rows.forEach(row => {
            const title = row.getAttribute('data-title') || '';
            const ref = row.getAttribute('data-ref') || '';
            const deal = row.getAttribute('data-deal') || '';
            const business = row.getAttribute('data-business') || '';
            const client = row.getAttribute('data-client') || '';
            const rowStage = row.getAttribute('data-stage') || '';
            const rowHealth = row.getAttribute('data-health') || '';
            const rowProgress = parseInt(row.getAttribute('data-progress') || '0', 10);
            const rowLead = row.getAttribute('data-lead') || '';
            const rowAssociate = row.getAttribute('data-associate') || '';
            const rowTarget = row.getAttribute('data-target') || '';

            let match = true;

            if (q) {
                const combined = `${title} ${ref} ${deal} ${business} ${client}`.toLowerCase();
                if (!combined.includes(q)) match = false;
            }

            if (stage && stage !== 'all') {
                if (stage === 'Active') {
                    if (!['RSAT', 'In Progress', 'For NTP Approval', 'Execution', 'Reporting', 'Delivery'].includes(rowStage)) match = false;
                } else if (stage === 'RSAT') {
                    if (!['RSAT', 'Work Order'].includes(rowStage)) match = false;
                } else if (stage === 'In Progress') {
                    if (!['In Progress', 'Execution'].includes(rowStage)) match = false;
                } else {
                    if (rowStage.toLowerCase() !== stage.toLowerCase()) match = false;
                }
            }

            if (health && rowHealth !== health) match = false;

            if (progress) {
                if (progress === 'not-started' && rowProgress !== 0) match = false;
                if (progress === 'active' && (rowProgress <= 0 || rowProgress >= 100)) match = false;
                if (progress === 'completed' && rowProgress < 100) match = false;
            }

            if (lead && !rowLead.toLowerCase().includes(lead.toLowerCase())) match = false;
            if (associate && !rowAssociate.toLowerCase().includes(associate.toLowerCase())) match = false;

            if (fromDate && rowTarget && rowTarget < fromDate) match = false;
            if (toDate && rowTarget && rowTarget > toDate) match = false;

            if (match) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const countEl = document.getElementById('registryResultCount');
        if (countEl) countEl.textContent = `${visibleCount} regular service${visibleCount === 1 ? '' : 's'}`;

        const filterEl = document.getElementById('activeRegistryFilter');
        if (filterEl) {
            filterEl.textContent = stage && stage !== 'all' ? `Filtered by stage: ${stage}` : (q ? `Filtered by keyword: "${q}"` : 'All lifecycle stages');
        }
    };

    window.resetRegularFilters = function() {
        if (document.getElementById('q')) document.getElementById('q').value = '';
        if (document.getElementById('filterFrom')) document.getElementById('filterFrom').value = '';
        if (document.getElementById('filterTo')) document.getElementById('filterTo').value = '';
        if (document.getElementById('filterStage')) document.getElementById('filterStage').value = '';
        if (document.getElementById('filterHealth')) document.getElementById('filterHealth').value = '';
        if (document.getElementById('filterProgress')) document.getElementById('filterProgress').value = '';
        if (document.getElementById('filterLead')) document.getElementById('filterLead').value = '';
        if (document.getElementById('filterAssociate')) document.getElementById('filterAssociate').value = '';
        
        currentSelectedStage = 'all';
        document.querySelectorAll('.stage-filter-card').forEach(c => c.classList.remove('active'));
        document.querySelector('.stage-filter-card[data-stage="all"]')?.classList.add('active');

        filterRegularRows();
    };

    window.exportRegularTable = function(format) {
        const rows = document.querySelectorAll('.regular-data-row');
        let csv = 'REGULAR,REFERENCE,BUSINESS,CLIENT,STAGE,HEALTH,PROGRESS,TARGET,LEAD\n';
        rows.forEach(r => {
            if (r.style.display !== 'none') {
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
</script>
@endsection

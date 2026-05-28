@extends('layouts.app')
@section('title', 'Project')

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
    <div class="mx-auto max-w-[1600px]">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-gray-900">Project</h1>
                <p class="mt-1 max-w-3xl text-sm text-gray-500">
                    Approved project and hybrid deals automatically open here, with SOW, NTP, reporting, delivery, and completion tracked inside one record.
                </p>
            </div>
            <button
                type="button"
                class="inline-flex h-11 items-center justify-center rounded-full bg-[#102d79] px-5 text-sm font-semibold text-white shadow-sm hover:bg-[#0d255f]"
                onclick="window.jkncSlideOver.open(document.getElementById('projectManualCreateDrawer'))"
            >
                Create Project
            </button>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-semibold">Create Project was not saved.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!empty($catalogWarnings ?? []))
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                @foreach ($catalogWarnings as $warning)
                    <p>{{ $warning }}</p>
                @endforeach
            </div>
        @endif

        <div class="mb-6 grid gap-3 xl:grid-cols-5">
            <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">All Projects</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['all'] }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">SOW</p>
                <p class="mt-2 text-3xl font-bold text-indigo-700">{{ $stats['start'] }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">In Progress</p>
                <p class="mt-2 text-3xl font-bold text-blue-700">{{ $stats['in_progress'] }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Active</p>
                <p class="mt-2 text-3xl font-bold text-amber-700">{{ $stats['active'] }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Completed</p>
                <p class="mt-2 text-3xl font-bold text-emerald-700">{{ $stats['completed'] }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-xl font-semibold text-gray-900">Project Registry</h2>
                <p class="mt-1 text-sm text-gray-500">This list is now backed by approved deals instead of placeholder data.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Project</th>
                            <th class="px-4 py-3 text-left font-medium">Deal</th>
                            <th class="px-4 py-3 text-left font-medium">Company</th>
                            <th class="px-4 py-3 text-left font-medium">Phase</th>
                            <th class="px-4 py-3 text-left font-medium">Owner</th>
                            <th class="px-4 py-3 text-left font-medium">Target</th>
                            <th class="px-4 py-3 text-right font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                        @forelse ($projects as $project)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900">{{ $project->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $project->project_code }}</p>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $project->deal?->deal_code ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $project->company?->company_name ?: ($project->business_name ?: '-') }}</td>
                                <td class="px-4 py-3">
                                    @php($phaseLabel = $project->status === 'Start' ? 'SOW' : $project->status)
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $phaseBadgeClasses[$phaseLabel] ?? 'bg-gray-100 text-gray-700 border border-gray-200' }}">{{ $phaseLabel }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $project->assigned_project_manager ?: '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ optional($project->target_completion_date)->format('M d, Y') ?: '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('project.show', $project) }}" class="inline-flex h-9 items-center rounded-full border border-gray-200 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500">No approved project engagements have created project records yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<x-slide-over id="projectManualCreateDrawer" width="sm:max-w-[95vw]">
    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Create Project</h2>
            <p class="mt-1 text-sm text-gray-500">Manually create a project record and open the SOW form to fill out details, scope, activities, and requirements.</p> 
        </div>
        <button type="button" class="rounded-full p-2 text-gray-500 hover:bg-gray-100" onclick="window.jkncSlideOver.close(document.getElementById('projectManualCreateDrawer'))">
            <span class="sr-only">Close</span>
            <i class="fas fa-times"></i>
        </button>
    </div>

    <form method="POST" action="{{ route('project.manual.store') }}" class="flex h-full flex-col overflow-hidden">
        @csrf
        <input type="hidden" name="source_mode" id="project_source_mode" value="{{ $oldSourceMode === 'deal' ? 'deal' : 'manual' }}">
        <input type="hidden" name="deal_id" id="project_deal_id" value="{{ old('deal_id') }}">
        <input type="hidden" name="contact_id" id="project_contact_id" value="{{ old('contact_id') }}">
        <input type="hidden" name="company_id" id="project_company_id" value="{{ old('company_id') }}">
        <div class="flex-1 overflow-y-auto px-6 py-5">
            <div class="grid gap-4 xl:grid-cols-[52%,48%]">
                <aside class="min-w-0 xl:sticky xl:top-0 xl:self-start">
                    <div id="projectTemplatePreview" class="rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-blue-50 p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">SOW Form Preview</p>
                                <p id="projectTemplatePreviewName" class="mt-2 text-lg font-semibold text-slate-900">Blank Project Form</p>
                            </div>
                            <span id="projectTemplatePreviewBadge" class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600">Default</span>
                        </div>
                        <div class="mt-4 max-h-[calc(100vh-220px)] overflow-y-auto rounded-2xl border border-[#d7deea] bg-white p-3 shadow-sm xl:scale-[1.02] xl:origin-top-left">
                            <div class="border border-[#163b7a] bg-white">
                                <div class="h-1.5 bg-[#163b7a]"></div>
                                <div class="p-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <img src="{{ asset('images/imaglogo.png') }}" alt="John Kelly and Company" class="h-10 w-auto object-contain">
                                        <div class="text-right">
                                            <div class="font-[Georgia] text-[18px] font-bold uppercase leading-tight text-slate-900">Scope Of Work</div>
                                            <div class="mt-1 text-[10px] uppercase tracking-[0.14em] text-slate-500">PROJ-F-002</div>
                                        </div>
                                    </div>

                                    <div class="mt-3 grid grid-cols-2 gap-2 text-[10px]">
                                        <div class="border border-slate-200 px-2 py-1.5">
                                            <div class="font-semibold uppercase text-slate-500">Condeal Ref No.</div>
                                            <div id="projectTemplateMetaCondeal" class="mt-1 font-semibold text-slate-900">-</div>
                                        </div>
                                        <div class="border border-slate-200 px-2 py-1.5">
                                            <div class="font-semibold uppercase text-slate-500">Project Code</div>
                                            <div id="projectTemplateMetaCode" class="mt-1 font-semibold text-slate-900">Auto-generated</div>
                                        </div>
                                        <div class="border border-slate-200 px-2 py-1.5">
                                            <div class="font-semibold uppercase text-slate-500">Client</div>
                                            <div id="projectTemplateMetaClient" class="mt-1 font-semibold text-slate-900">Pending selection</div>
                                        </div>
                                        <div class="border border-slate-200 px-2 py-1.5">
                                            <div class="font-semibold uppercase text-slate-500">Business</div>
                                            <div id="projectTemplateMetaBusiness" class="mt-1 font-semibold text-slate-900">Pending selection</div>
                                        </div>
                                        <div class="border border-slate-200 px-2 py-1.5">
                                            <div class="font-semibold uppercase text-slate-500">Version</div>
                                            <div id="projectTemplatePreviewVersion" class="mt-1 font-semibold text-slate-900">1.0</div>
                                        </div>
                                        <div class="border border-slate-200 px-2 py-1.5">
                                            <div class="font-semibold uppercase text-slate-500">Status</div>
                                            <div id="projectTemplatePreviewStatuses" class="mt-1 font-semibold text-slate-900">Draft template</div>
                                        </div>
                                    </div>

                                    <div class="mt-3 bg-[#163b7a] px-2 py-1 text-center font-[Georgia] text-[11px] font-bold uppercase tracking-[0.08em] text-white">Within Scope</div>
                                    <table class="w-full table-fixed border-collapse text-[9px] font-[Georgia] text-slate-900">
                                        <thead>
                                            <tr>
                                                <th class="border border-slate-900 px-1 py-1 font-normal">Main Task</th>
                                                <th class="border border-slate-900 px-1 py-1 font-normal">Sub Task</th>
                                                <th class="border border-slate-900 px-1 py-1 font-normal">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="projectTemplateWithinScope"></tbody>
                                    </table>

                                    <div class="mt-3 bg-[#163b7a] px-2 py-1 text-center font-[Georgia] text-[11px] font-bold uppercase tracking-[0.08em] text-white">Out Of Scope</div>
                                    <div id="projectTemplateOutScope" class="border border-slate-900 border-t-0 px-2 py-2 text-[9px] leading-5 font-[Georgia] text-slate-900"></div>

                                    <div class="mt-4 border border-slate-900 border-t-0 px-3 py-5 text-center font-[Georgia] text-[9px] text-slate-900">
                                        <div class="mx-auto w-[70%] border-b border-slate-900 pb-1 font-semibold" id="projectTemplateSignatureName">Client representative signature</div>
                                        <div class="mt-2 italic">Client Fullname & Signature</div>
                                    </div>

                                    <div class="mt-4 bg-[#163b7a] px-2 py-1 text-center font-[Georgia] text-[11px] font-bold uppercase tracking-[0.08em] text-white">Internal Approval</div>
                                    <div class="grid grid-cols-2 border-l border-r border-b border-slate-900 font-[Georgia] text-[9px] text-slate-900">
                                        <div class="border-r border-slate-900 px-3 py-3">
                                            <div class="text-slate-500 italic">Prepared By</div>
                                            <div id="projectTemplatePreparedBy" class="mt-3 border-b border-slate-900 pb-1 min-h-[18px]"></div>
                                            <div class="mt-2 text-[8px] italic">Name / Signature / Date</div>
                                        </div>
                                        <div class="px-3 py-3">
                                            <div class="text-slate-500 italic">Reviewed By</div>
                                            <div id="projectTemplateReviewedBy" class="mt-3 border-b border-slate-900 pb-1 min-h-[18px]"></div>
                                            <div class="mt-2 text-[8px] italic">Name / Signature / Date</div>
                                        </div>
                                    </div>

                                    <div class="mt-4 bg-[#163b7a] px-2 py-1 text-center font-[Georgia] text-[11px] font-bold uppercase tracking-[0.08em] text-white">Records</div>
                                    <div class="grid grid-cols-[1fr_38%] border-l border-r border-b border-slate-900 font-[Georgia] text-[9px] text-slate-900">
                                        <div class="border-r border-slate-900 px-3 py-3">
                                            <div class="mb-2">Date Received: ____________________</div>
                                            <div>Date Returned: ____________________</div>
                                        </div>
                                        <div class="flex items-center justify-center px-3 py-6 italic text-center">Conforme / Record Custodian</div>
                                    </div>

                                    <div class="mt-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-3 text-[10px] text-slate-600">
                                        <span id="projectTemplatePreviewEffect">The project will start from a blank/default SOW structure.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
                <div class="min-w-0 max-w-[720px] justify-self-end space-y-5">
            <section class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm font-semibold text-gray-900">How do you want to create this project?</p>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <button type="button" data-project-source-option="deal" class="project-source-option rounded-2xl border px-4 py-4 text-left transition {{ $oldSourceMode === 'deal' ? 'border-[#102d79] bg-white ring-2 ring-[#102d79]/10' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                        <span class="block text-sm font-semibold text-gray-900">Link Existing Deal</span>
                        <span class="mt-1 block text-xs text-gray-500">Pick an open deal and preload its client, company, scope, and staffing details.</span>
                    </button>
                    <button type="button" data-project-source-option="manual" class="project-source-option rounded-2xl border px-4 py-4 text-left transition {{ $oldSourceMode !== 'deal' ? 'border-[#102d79] bg-white ring-2 ring-[#102d79]/10' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                        <span class="block text-sm font-semibold text-gray-900">Manual</span>
                        <span class="mt-1 block text-xs text-gray-500">Start manually, then optionally select an existing contact or company to fill the client details.</span>
                    </button>
                </div>
            </section>

            <section id="projectDealLinkSection" class="space-y-3 {{ $oldSourceMode === 'deal' ? '' : 'hidden' }}">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Search Existing Deal</label>
                    <input
                        type="text"
                        id="projectDealSearch"
                        value=""
                        placeholder="Type deal code, deal name, client, or company..."
                        autocomplete="off"
                        class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900"
                    >
                    <p class="mt-2 text-xs text-gray-500">Only deals without a linked project are shown here.</p>
                </div>
                <div id="projectDealResults" class="hidden max-h-64 overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-sm"></div>
                <div id="projectDealSelectionSummary" class="{{ old('deal_id') ? '' : 'hidden' }} rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900"></div>
            </section>

            <section id="projectManualLinkSection" class="space-y-4 {{ $oldSourceMode === 'deal' ? 'hidden' : '' }}">
                <div class="rounded-2xl border border-gray-200 p-4">
                    <h3 class="text-base font-semibold text-gray-900">Customer Type</h3>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        @foreach (['business' => 'Business', 'individual' => 'Individual'] as $value => $label)
                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                <input type="radio" name="project_customer_type" value="{{ $value }}" @checked(old('project_customer_type', 'individual') === $value) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h3 id="projectSelectionSectionTitle" class="text-base font-semibold text-gray-900">Select Existing Contact / Client</h3>
                    <p id="projectSearchHelpText" class="mt-1 text-xs text-gray-500">Select a customer type, then search the matching records.</p>
                </div>
                <div class="relative">
                    <label id="projectContactSearchLabel" class="mb-2 block text-sm font-medium text-gray-700" for="projectContactSearch">Search Existing Client</label>
                    <input
                        type="text"
                        id="projectContactSearch"
                        value=""
                        placeholder="Type name, company, email, or mobile..."
                        autocomplete="off"
                        class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900"
                    >
                    <div id="projectContactResults" class="mt-2 hidden max-h-64 overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-sm"></div>
                </div>
                <div id="projectManualSelectionSummary" class="{{ old('contact_id') || old('company_id') ? '' : 'hidden' }} rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"></div>
            </section>

            <div class="grid gap-3 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700">SOW Template</label>
                    <select name="template_id" id="project_template_id" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                        <option value="">Start from blank/default</option>
                        @foreach ($sowTemplates as $template)
                            <option value="{{ $template->id }}" @selected((string) old('template_id') === (string) $template->id)>{{ $template->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-500">Choose a saved SOW template to prefill the first Scope of Work document for this new project.</p>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Project Name</label>
                    <input name="name" id="project_name" value="{{ old('name') }}" required class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Client Name</label>
                    <input name="client_name" id="project_client_name" value="{{ old('client_name') }}" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Business Name</label>
                    <input name="business_name" id="project_business_name" value="{{ old('business_name') }}" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Planned Start</label>
                    <input type="date" name="planned_start_date" id="project_planned_start_date" value="{{ old('planned_start_date') }}" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Target Completion</label>
                    <input type="date" name="target_completion_date" id="project_target_completion_date" value="{{ old('target_completion_date') }}" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Client Confirmation Name</label>
                    <input name="client_confirmation_name" id="project_client_confirmation_name" value="{{ old('client_confirmation_name') }}" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                </div>
                <div class="relative" data-employee-picker>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Project Manager</label>
                    <input name="assigned_project_manager" id="project_assigned_project_manager" value="{{ old('assigned_project_manager') }}" autocomplete="off" data-employee-search-input class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                    <div class="absolute z-20 mt-1 hidden w-full rounded-xl border border-gray-200 bg-white shadow-lg" data-employee-search-results></div>
                </div>
                <div class="relative" data-employee-picker>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Lead Consultant</label>
                    <input name="assigned_consultant" id="project_assigned_consultant" value="{{ old('assigned_consultant') }}" autocomplete="off" data-employee-search-input class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                    <div class="absolute z-20 mt-1 hidden w-full rounded-xl border border-gray-200 bg-white shadow-lg" data-employee-search-results></div>
                </div>
                <div class="relative md:col-span-2" data-employee-picker>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Lead Associate</label>
                    <input name="assigned_associate" id="project_assigned_associate" value="{{ old('assigned_associate') }}" autocomplete="off" data-employee-search-input class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                    <div class="absolute z-20 mt-1 hidden w-full rounded-xl border border-gray-200 bg-white shadow-lg" data-employee-search-results></div>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Scope Summary</label>
                    <textarea name="scope_summary" id="project_scope_summary" rows="3" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900">{{ old('scope_summary') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700">SOW Engagement Requirements</label>
                    <textarea name="engagement_requirements_text" rows="5" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900" placeholder="One requirement per line">{{ old('engagement_requirements_text') }}</textarea>
                </div>
                <input type="hidden" name="service_area" id="project_service_area" value="{{ old('service_area') }}">
                <textarea name="services" id="project_services" class="hidden">{{ old('services') }}</textarea>
                <textarea name="products" id="project_products" class="hidden">{{ old('products') }}</textarea>
            </div>

            <section class="rounded-2xl border border-gray-200 p-4">
                <h3 class="text-base font-semibold text-gray-900">Service Identification</h3>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Service Area</label>
                        <div id="project-service-area-options-grid" class="grid gap-2 sm:grid-cols-2">
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

                    <div class="space-y-4">
                        <label class="block text-sm font-medium text-gray-700">Services</label>
                        <div id="projectServicesEmptyState" class="rounded-xl border border-dashed border-gray-200 bg-gray-50/60 p-4 text-sm text-gray-500 {{ count($selectedServiceAreas) > 0 ? 'hidden' : '' }}">
                            Select a service area first to show matching services.
                        </div>
                        <div id="projectServicesGrid" class="grid gap-4 lg:grid-cols-2 {{ count($selectedServiceAreas) > 0 ? '' : 'hidden' }}">
                            @foreach ($serviceGroups as $group => $options)
                                <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-3 {{ in_array($group, $selectedServiceAreas, true) ? '' : 'hidden' }}" data-project-service-group="{{ $group }}">
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-600">{{ $group }}</p>
                                    <div class="space-y-2">
                                        @foreach ($options as $option)
                                            <label class="flex items-start gap-2 text-sm text-gray-700">
                                                <input type="checkbox" name="service_options[]" value="{{ $option }}" data-project-service-group-option="{{ $group }}" @checked(in_array($option, $selectedServices, true)) class="mt-0.5 h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
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

            <section class="rounded-2xl border border-gray-200 p-4">
                <h3 class="text-base font-semibold text-gray-900">Products</h3>
                <p class="mt-1 text-xs text-gray-500">Products follow the selected service area. Without a selected service area, only products without a service area are shown.</p>
                <div id="projectProductsEmptyState" class="mt-3 rounded-xl border border-dashed border-gray-200 bg-gray-50/60 p-4 text-sm text-gray-500 {{ count($productOptionsByServiceArea) > 0 ? 'hidden' : '' }}">
                    No unlinked products are available.
                </div>
                <div id="project-product-options-grid" class="mt-3 grid gap-4">
                    @foreach ($productOptionsByServiceArea as $serviceArea => $options)
                        <div data-project-product-group="{{ $serviceArea }}" data-product-unlinked-group="{{ $serviceArea === 'Products Without Service Area' ? 'true' : 'false' }}">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-600">{{ $serviceArea }}</p>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($options as $option)
                                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700" data-project-product-option data-service-area-product="{{ $serviceArea }}" data-product-value="{{ $option }}" data-project-product-search="{{ \Illuminate\Support\Str::lower($option.' '.$serviceArea) }}">
                                        <input type="checkbox" name="product_options[]" value="{{ $option }}" @checked(in_array($option, $selectedProducts, true)) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span>{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-200 px-6 py-4">
            <div class="flex items-center justify-end gap-3">
                <button type="button" class="inline-flex h-11 items-center rounded-full border border-gray-300 px-5 text-sm font-medium text-gray-700 hover:bg-gray-50" onclick="window.jkncSlideOver.close(document.getElementById('projectManualCreateDrawer'))">Cancel</button>
                <button type="submit" class="inline-flex h-11 items-center rounded-full bg-[#102d79] px-5 text-sm font-semibold text-white hover:bg-[#0d255f]">Create</button>
            </div>
        </div>
    </form>
</x-slide-over>

<datalist id="projectEmployeeOptions">
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
        const sowTemplatePreviewData = @json($sowTemplatePreviewData);

        const sourceModeInput = document.getElementById('project_source_mode');
        const dealIdInput = document.getElementById('project_deal_id');
        const contactIdInput = document.getElementById('project_contact_id');
        const companyIdInput = document.getElementById('project_company_id');
        const dealSection = document.getElementById('projectDealLinkSection');
        const manualSection = document.getElementById('projectManualLinkSection');
        const dealSearch = document.getElementById('projectDealSearch');
        const contactSearch = document.getElementById('projectContactSearch');
        const templateSelect = document.getElementById('project_template_id');
        const templatePreview = document.getElementById('projectTemplatePreview');
        const templatePreviewName = document.getElementById('projectTemplatePreviewName');
        const templatePreviewBadge = document.getElementById('projectTemplatePreviewBadge');
        const templateMetaCondeal = document.getElementById('projectTemplateMetaCondeal');
        const templateMetaCode = document.getElementById('projectTemplateMetaCode');
        const templateMetaClient = document.getElementById('projectTemplateMetaClient');
        const templateMetaBusiness = document.getElementById('projectTemplateMetaBusiness');
        const templatePreviewVersion = document.getElementById('projectTemplatePreviewVersion');
        const templatePreviewStatuses = document.getElementById('projectTemplatePreviewStatuses');
        const templatePreviewEffect = document.getElementById('projectTemplatePreviewEffect');
        const templateSignatureName = document.getElementById('projectTemplateSignatureName');
        const templatePreparedBy = document.getElementById('projectTemplatePreparedBy');
        const templateReviewedBy = document.getElementById('projectTemplateReviewedBy');
        const templateWithinScope = document.getElementById('projectTemplateWithinScope');
        const templateOutScope = document.getElementById('projectTemplateOutScope');
        const dealResults = document.getElementById('projectDealResults');
        const contactResults = document.getElementById('projectContactResults');
        const dealSummary = document.getElementById('projectDealSelectionSummary');
        const manualSummary = document.getElementById('projectManualSelectionSummary');
        const sourceButtons = Array.from(document.querySelectorAll('[data-project-source-option]'));
        const customerTypeInputs = Array.from(document.querySelectorAll('input[name="project_customer_type"]'));
        const projectContactSearchLabel = document.getElementById('projectContactSearchLabel');
        const projectSelectionSectionTitle = document.getElementById('projectSelectionSectionTitle');
        const projectSearchHelpText = document.getElementById('projectSearchHelpText');

        const selectedState = {
            deal: null,
            contact: null,
            company: null,
        };

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
        ['project_client_name', 'project_business_name', 'project_client_confirmation_name', 'project_assigned_project_manager', 'project_assigned_consultant', 'project_assigned_associate'].forEach((id) => {
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
        @if ($errors->any())
            window.jkncSlideOver?.open(document.getElementById('projectManualCreateDrawer'));
        @endif
    })();
</script>
@endsection

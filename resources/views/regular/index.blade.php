@extends('layouts.app')
@section('title', 'Regular')

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
                <h1 class="text-3xl font-semibold tracking-tight text-gray-900">Regular</h1>
                <p class="mt-1 max-w-3xl text-sm text-gray-500">
                    Regular and retainer deals automatically open here, with RSAT, approvals, execution, reporting, delivery, and continuation tracked inside one record.
                </p>
            </div>
            <button
                type="button"
                class="inline-flex h-11 items-center justify-center rounded-full bg-[#102d79] px-5 text-sm font-semibold text-white shadow-sm hover:bg-[#0d255f]"
                onclick="window.jkncSlideOver.open(document.getElementById('regularManualCreateDrawer'))"
            >
                Create Regular
            </button>
        </div>

        @if (!empty($catalogWarnings ?? []))
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                @foreach ($catalogWarnings as $warning)
                    <p>{{ $warning }}</p>
                @endforeach
            </div>
        @endif

        <div class="mb-6 grid gap-3 xl:grid-cols-5">
            <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">All Regular</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['all'] }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">RSAT</p>
                <p class="mt-2 text-3xl font-bold text-indigo-700">{{ $stats['rsat'] }}</p>
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
                <h2 class="text-xl font-semibold text-gray-900">Regular Registry</h2>
                <p class="mt-1 text-sm text-gray-500">This list is now backed by saved regular engagements instead of placeholder data.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Regular</th>
                            <th class="px-4 py-3 text-left font-medium">Deal</th>
                            <th class="px-4 py-3 text-left font-medium">Company</th>
                            <th class="px-4 py-3 text-left font-medium">Phase</th>
                            <th class="px-4 py-3 text-left font-medium">Owner</th>
                            <th class="px-4 py-3 text-left font-medium">Target</th>
                            <th class="px-4 py-3 text-right font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                        @forelse ($regulars as $regular)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900">{{ $regular->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $regular->project_code }}</p>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $regular->deal?->deal_code ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $regular->company?->company_name ?: ($regular->business_name ?: '-') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $phaseBadgeClasses[$regular->status] ?? 'bg-gray-100 text-gray-700 border border-gray-200' }}">{{ $regular->status }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $regular->assigned_project_manager ?: '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ optional($regular->target_completion_date)->format('M d, Y') ?: '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('regular.show', $regular) }}" class="inline-flex h-9 items-center rounded-full border border-gray-200 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500">No regular engagements have created regular records yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Project Manager</label>
                    <input name="assigned_project_manager" id="regular_assigned_project_manager" value="{{ old('assigned_project_manager') }}" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Lead Consultant</label>
                    <input name="assigned_consultant" id="regular_assigned_consultant" value="{{ old('assigned_consultant') }}" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Lead Associate</label>
                    <input name="assigned_associate" id="regular_assigned_associate" value="{{ old('assigned_associate') }}" class="h-11 w-full rounded-xl border border-gray-300 px-4 text-sm text-gray-900">
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
                                <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                    <input type="checkbox" name="service_area_options[]" value="{{ $option }}" @checked(in_array($option, $selectedServiceAreas, true)) {{ $option === 'Others' ? 'data-other-target=regular-service-area-other-wrapper' : '' }} class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div id="regular-service-area-other-wrapper" class="{{ (in_array('Others', $selectedServiceAreas, true) || count($serviceAreaOtherEntries) > 0) ? '' : 'hidden' }} mt-2">
                            <input id="regular-service-area-other-input" type="text" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" placeholder="Enter custom service area and press Enter">
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
                        <div id="regular-product-options-grid" class="mt-3 grid gap-4">
                            @foreach ($productOptionsByServiceArea as $serviceArea => $options)
                                <div class="{{ in_array($serviceArea, $selectedServiceAreas, true) ? '' : 'hidden' }}" data-regular-product-group="{{ $serviceArea }}">
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-600">{{ $serviceArea }}</p>
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        @foreach ($options as $option)
                                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
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

<script>
    (() => {
        const dealRecords = @json($dealRecords ?? []);
        const contactRecords = @json($contactRecords ?? []);
        const companyRecords = @json($companyRecords ?? []);
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
        const serviceAreaChecks = Array.from(document.querySelectorAll('input[name="service_area_options[]"]'));
        const serviceChecks = Array.from(document.querySelectorAll('input[name="service_options[]"]'));
        const productChecks = Array.from(document.querySelectorAll('input[name="product_options[]"]'));

        const setValue = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.value = value ?? '';
        };

        const applyCommonValues = (record) => {
            setValue('regular_client_name', record.client_name || record.label || '');
            setValue('regular_business_name', record.business_name || record.company_name || '');
            setValue('regular_planned_start_date', record.planned_start_date || '');
            setValue('regular_target_completion_date', record.target_completion_date || '');
            setValue('regular_assigned_project_manager', record.assigned_project_manager || '');
            setValue('regular_assigned_consultant', record.assigned_consultant || '');
            setValue('regular_assigned_associate', record.assigned_associate || '');
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
            const matches = [...contactRecords, ...companyRecords].filter((record) => term === '' || String(record.search_blob || '').includes(term)).slice(0, 8);
            if (!contactResults) return;
            if (matches.length === 0) {
                contactResults.classList.add('hidden');
                contactResults.innerHTML = '';
                return;
            }
            contactResults.classList.remove('hidden');
            contactResults.innerHTML = matches.map((record) => `
                <button type="button" class="block w-full border-b border-gray-100 px-4 py-3 text-left hover:bg-gray-50">
                    <div class="text-sm font-semibold text-gray-900">${record.label || record.company_name || ''}</div>
                    <div class="text-xs text-gray-500">${record.company_name || record.email || ''}</div>
                </button>
            `).join('');
            contactResults.querySelectorAll('button').forEach((button, index) => {
                button.addEventListener('click', () => {
                    const record = matches[index];
                    if (!record) return;
                    if (record.primary_contact_id || record.company_name) {
                        companyIdInput.value = record.id;
                    } else {
                        contactIdInput.value = record.id;
                    }
                    applyCommonValues({
                        client_name: record.label,
                        business_name: record.company_name,
                        client_confirmation_name: record.label,
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
            const areas = serviceAreaChecks.filter((item) => item.checked).map((item) => item.value);
            const services = serviceChecks.filter((item) => item.checked).map((item) => item.value);
            const products = productChecks.filter((item) => item.checked).map((item) => item.value);
            setValue('regular_service_area', areas.join(', '));
            setValue('regular_services', services.join(', '));
            setValue('regular_products', products.join(', '));
            document.querySelectorAll('[data-regular-service-group]').forEach((group) => {
                group.classList.toggle('hidden', !areas.includes(group.dataset.regularServiceGroup));
            });
            document.querySelectorAll('[data-regular-product-group]').forEach((group) => {
                group.classList.toggle('hidden', !areas.includes(group.dataset.regularProductGroup));
            });
            document.getElementById('regularServicesGrid')?.classList.toggle('hidden', areas.length === 0);
            renderRegularTemplatePreview();
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
        dealSearch?.addEventListener('input', () => renderDealResults(dealSearch.value));
        contactSearch?.addEventListener('input', () => renderContactResults(contactSearch.value));
        serviceAreaChecks.forEach((item) => item.addEventListener('change', syncSelections));
        serviceChecks.forEach((item) => item.addEventListener('change', syncSelections));
        productChecks.forEach((item) => item.addEventListener('change', syncSelections));
        templateSelect?.addEventListener('change', renderRegularTemplatePreview);
        ['regular_client_name', 'regular_business_name', 'regular_client_confirmation_name', 'regular_assigned_project_manager', 'regular_assigned_consultant', 'regular_assigned_associate'].forEach((id) => {
            document.getElementById(id)?.addEventListener('input', renderRegularTemplatePreview);
        });

        setSourceMode(sourceModeInput?.value || 'manual');
        syncSelections();
        renderRegularTemplatePreview();
    })();
</script>
@endsection

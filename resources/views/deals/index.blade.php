@extends('layouts.app')
@section('title', 'Deals')

@section('content')
@php
    $formatCurrency = static fn (int $amount): string => 'P'.number_format($amount);
@endphp

<div class="bg-white p-6">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-3xl font-semibold text-gray-900">Deals</h1>
            <p class="text-sm text-gray-500">Track and manage your sales pipeline</p>
        </div>
        <button id="openCreateDealModalBtn" type="button" class="h-10 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
            <i class="fas fa-plus mr-1"></i> Add Deal
        </button>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if (session('deal_access_denied'))
        <div id="dealAccessDeniedModal" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-900/35 px-4">
            <div class="w-full max-w-sm rounded-xl border border-red-100 bg-white p-5 shadow-xl">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                        <i class="fas fa-lock text-sm"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold text-gray-900">Access Restricted</h2>
                        <p class="mt-1 text-sm text-gray-600">{{ session('deal_access_denied') }}</p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end">
                    <button id="dealAccessDeniedClose" type="button" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        OK
                    </button>
                </div>
            </div>
        </div>
    @endif

    <form method="GET" action="{{ route('deals.index') }}" class="mb-4 flex flex-wrap items-center gap-2 text-sm">
        <div class="relative w-full max-w-sm">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400"></i>
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search code, deal, or contact" class="h-9 w-full rounded-lg border border-gray-200 bg-white pl-8 pr-3 text-sm text-gray-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
        </div>
        <select class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
            <option>All Deals</option>
        </select>
        <select class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
            <option>Created Date</option>
        </select>
        <button type="submit" class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 hover:bg-gray-50">Search</button>
        <span id="totalDealsCount" class="text-xs text-gray-500">{{ $totalDeals }} deals</span>
    </form>

    <div id="dealSelectionBar" class="mb-4 hidden items-center justify-between rounded-lg border border-blue-100 bg-blue-50 px-3 py-2">
        <p class="text-sm font-medium text-blue-700"><span id="selectedDealCount">0</span> selected</p>
        <div class="flex items-center gap-2 text-xs">
            <button type="button" class="rounded border border-blue-200 bg-white px-2.5 py-1 text-blue-700 hover:bg-blue-100">Move Stage</button>
            <button type="button" class="rounded border border-red-200 bg-white px-2.5 py-1 text-red-600 hover:bg-red-50">Delete Deals</button>
            <button id="clearDealSelectionBtn" type="button" class="rounded border border-blue-200 bg-white px-2.5 py-1 text-blue-700 hover:bg-blue-100">Clear Selection</button>
        </div>
    </div>

    <div class="overflow-x-auto pb-2">
        <div id="dealsBoard" class="flex min-w-max gap-3">
            @foreach ($stageColumns as $column)
                @php
                    $stageBorderColor = $column['color'] ?: ($column['stage'] === 'Closed Lost' ? '#dc2626' : ($column['stage'] === 'Closed Won' ? '#16a34a' : '#1e293b'));
                    $headerClass = 'bg-slate-800';
                @endphp
                <section class="stage-column w-[230px] overflow-hidden rounded-xl border border-gray-200 bg-gray-50" data-stage-id="{{ $column['id'] ?? '' }}" data-stage-column="{{ $column['stage'] }}" data-stage-color="{{ $stageBorderColor }}">
                    <header class="stage-header group/column rounded-t-xl border-t-4 px-3 py-2 text-white {{ $headerClass }}" style="border-top-color: {{ $stageBorderColor }};">
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="min-w-0">
                                    <h2 class="stage-name cursor-text text-xs font-semibold">{{ $column['stage'] }}</h2>
                                    <input type="text" class="stage-name-input hidden h-5 w-full rounded border border-white/30 bg-white/10 px-1 text-xs font-semibold text-white outline-none focus:border-white/60" value="{{ $column['stage'] }}">
                                    <p class="stage-total text-xs opacity-90">{{ $formatCurrency($column['total_amount']) }} • {{ count($column['deals']) }} {{ count($column['deals']) === 1 ? 'Deal' : 'Deals' }}</p>
                                </div>
                            </div>
                            <div class="relative flex items-start gap-2">
                                <input type="checkbox" class="stage-select-checkbox mt-0.5 h-3.5 w-3.5 rounded border-white/40 bg-white/10 opacity-0 transition group-hover/column:opacity-100">
                                <button type="button" class="stage-actions-trigger opacity-0 transition group-hover/column:opacity-100" data-stage="{{ $column['stage'] }}" title="Stage actions">
                                    <i class="fas fa-ellipsis-vertical text-xs text-white/90"></i>
                                </button>
                                <div class="stage-actions-menu absolute right-0 z-20 mt-2 hidden w-48 rounded-lg border border-gray-200 bg-white py-1 text-xs text-gray-700 shadow-lg" data-stage-menu="{{ $column['stage'] }}">
                                    <button type="button" class="stage-action-item flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-gray-50" data-action="new-stage">New Stage</button>
                                    <button type="button" class="stage-action-item flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-gray-50" data-action="move-left">Move Left</button>
                                    <button type="button" class="stage-action-item flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-gray-50" data-action="move-right">Move Right</button>
                                    <button type="button" class="stage-action-item flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-gray-50" data-action="rename-stage">Rename Stage</button>
                                    <div class="border-t border-gray-100 px-3 py-2">
                                        <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-gray-400">Change Stage Color</p>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach (['#2563eb', '#4f46e5', '#0891b2', '#d97706', '#ea580c', '#059669', '#7c3aed', '#dc2626'] as $colorOption)
                                                <button type="button" class="stage-color-option h-4 w-4 rounded-full border border-gray-200" data-action="change-color" data-color="{{ $colorOption }}" style="background-color: {{ $colorOption }};"></button>
                                            @endforeach
                                        </div>
                                    </div>
                                    <button type="button" class="stage-action-item flex w-full items-center gap-2 px-3 py-2 text-left text-red-600 hover:bg-red-50" data-action="delete-stage">Delete Stage</button>
                                </div>
                            </div>
                        </div>
                    </header>

                    <div class="stage-cards space-y-2 p-2">
                        @forelse ($column['deals'] as $deal)
                            @php
                                $ownerInitials = strtoupper(substr($deal['owner_name'], 0, 1).substr(strrchr(' '.$deal['owner_name'], ' '), 1, 1));
                            @endphp
                            <article
                                class="deal-card group/deal relative rounded-lg border border-gray-200 bg-white p-3 shadow-sm transition hover:cursor-pointer hover:border-blue-300 hover:shadow {{ !($deal['can_access'] ?? false) ? 'opacity-80' : '' }}"
                                data-deal-id="{{ $deal['id'] }}"
                                data-deal-name="{{ $deal['deal_code'] ?? $deal['deal_name'] }}"
                                data-deal-stage="{{ $column['stage'] }}"
                                data-deal-contact="{{ $deal['contact_name'] }}"
                                data-deal-company="{{ $deal['company_name'] }}"
                                data-deal-amount="{{ $deal['amount'] }}"
                                data-deal-expected-close="{{ $deal['expected_close'] }}"
                                data-deal-code="{{ $deal['deal_code'] ?? 'Auto-generated after save' }}"
                                data-deal-created-by="{{ $deal['created_by'] ?? 'System' }}"
                                data-deal-created-at="{{ $deal['created_at_label'] ?? now()->format('F d, Y • h:i:s A') }}"
                                data-view-url="{{ route('deals.show', $deal['id']) }}"
                                data-edit-url="{{ route('deals.update', $deal['id']) }}"
                                data-can-access="{{ ($deal['can_access'] ?? false) ? '1' : '0' }}"
                                >
                                <label class="deal-card-checkbox pointer-events-none absolute right-9 top-2 z-10 flex h-5 w-5 items-center justify-center rounded border border-gray-200 bg-white/95 shadow-sm opacity-0 transition duration-150 group-hover/deal:pointer-events-auto group-hover/deal:opacity-100">
                                    <input type="checkbox" class="deal-select-checkbox h-3.5 w-3.5" data-deal-select="{{ $deal['id'] }}">
                                </label>
                                <div class="deal-quick-actions pointer-events-none absolute right-2 top-2 flex items-center gap-1 opacity-0 transition duration-150 group-hover/deal:pointer-events-auto group-hover/deal:opacity-100">
                                    <button type="button" class="deal-more-btn flex h-6 w-6 items-center justify-center rounded border border-gray-200 bg-white text-gray-500 hover:text-blue-700 hover:bg-blue-50" title="More actions">
                                        <i class="fas fa-ellipsis-h text-[11px]"></i>
                                    </button>
                                    <div class="deal-more-menu absolute right-0 top-7 z-20 hidden w-36 rounded-lg border border-gray-200 bg-white py-1 text-xs text-gray-700 shadow-lg">
                                        <button type="button" class="deal-menu-item flex w-full px-3 py-2 text-left hover:bg-gray-50" data-action="view">View Deal</button>
                                        @if ($deal['can_access'] ?? false)
                                            <button type="button" class="deal-menu-item flex w-full px-3 py-2 text-left hover:bg-gray-50" data-action="edit">Edit Deal</button>
                                        @endif
                                        <button type="button" class="deal-menu-item flex w-full px-3 py-2 text-left text-red-600 hover:bg-red-50" data-action="delete">Delete Deal</button>
                                    </div>
                                </div>

                                <a
                                    id="deal-{{ $deal['id'] }}"
                                    href="{{ route('deals.show', $deal['id']) }}"
                                    class="block"
                                >
                                    <h3 class="pr-16 text-[14px] font-semibold tracking-wide text-blue-700 [overflow-wrap:break-word] line-clamp-2">{{ $deal['deal_code'] ?? 'DEAL' }}</h3>
                                    <p class="mt-2 text-xs text-gray-700">{{ $deal['contact_name'] }}</p>
                                    <p class="text-[11px] text-gray-400">{{ $deal['company_name'] }}</p>
                                    @unless ($deal['can_access'] ?? false)
                                        <p class="mt-2 inline-flex rounded-full bg-amber-50 px-2 py-1 text-[10px] font-medium text-amber-700">Visible only. Access restricted.</p>
                                    @endunless

                                    <div class="mt-3" data-price-wrap data-price-visible="false">
                                        <div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                            <span>Price</span>
                                            <span
                                                role="button"
                                                tabindex="0"
                                                data-price-toggle
                                                data-price-access="{{ ($deal['can_access'] ?? false) ? '1' : '0' }}"
                                                aria-label="Show price"
                                                title="Show price"
                                                class="inline-flex h-5 w-5 items-center justify-center rounded-full text-gray-400 hover:bg-blue-50 hover:text-blue-700 {{ ($deal['can_access'] ?? false) ? '' : 'cursor-not-allowed opacity-50' }}"
                                            >
                                                <i class="fas fa-eye text-[10px]" data-price-icon></i>
                                            </span>
                                        </div>
                                        <p class="text-lg font-semibold text-blue-700">
                                            <span data-price-hidden>••••••</span>
                                            <span data-price-value class="hidden">{{ $formatCurrency($deal['amount']) }}</span>
                                        </p>
                                    </div>
                                    <p class="mt-2 text-[11px] text-gray-400">Expected Close</p>
                                    <p class="text-xs text-gray-700">{{ $deal['expected_close'] }}</p>

                                    <div class="mt-3 flex items-center gap-1.5 border-t border-gray-100 pt-2 text-[11px] text-gray-500">
                                        <span class="flex h-4 w-4 items-center justify-center rounded-full bg-blue-100 text-[9px] font-semibold text-blue-700">{{ $ownerInitials }}</span>
                                        <span>{{ $deal['owner_name'] }}</span>
                                    </div>
                                </a>
                            </article>
                        @empty
                            <div class="rounded-lg border border-dashed border-gray-300 bg-white px-3 py-5 text-center text-xs text-gray-500">
                                No deals in this stage.
                            </div>
                        @endforelse
                    </div>
                    <div class="border-t border-gray-200 px-2 py-2">
                        <p class="stage-description text-[11px] text-gray-500" data-stage-description="{{ $column['stage'] }}">No description yet.</p>
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</div>

<div id="stageMiniModal" class="fixed inset-0 z-[70] hidden" aria-hidden="true">
    <button id="stageMiniModalOverlay" type="button" class="absolute inset-0 bg-slate-900/35"></button>
    <div class="absolute left-1/2 top-1/2 w-full max-w-sm -translate-x-1/2 -translate-y-1/2 rounded-xl border border-gray-200 bg-white p-4 shadow-xl">
        <h3 id="stageMiniModalTitle" class="text-base font-semibold text-gray-900">New Stage</h3>
        <p id="stageMiniModalSubtitle" class="mt-1 text-xs text-gray-500">Add a new stage at the end of the pipeline.</p>
        <textarea id="stageMiniModalInput" rows="3" class="mt-3 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></textarea>
        <p id="stageMiniModalError" class="mt-2 hidden text-xs text-red-600"></p>
        <div class="mt-4 flex items-center justify-end gap-2">
            <button id="stageMiniModalCancel" type="button" class="h-9 rounded-lg border border-gray-300 px-3 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
            <button id="stageMiniModalSave" type="button" class="h-9 rounded-lg bg-blue-600 px-3 text-sm font-medium text-white hover:bg-blue-700">Save</button>
        </div>
    </div>
</div>

<div id="stageDeleteModal" class="fixed inset-0 z-[70] hidden" aria-hidden="true">
    <button id="stageDeleteOverlay" type="button" class="absolute inset-0 bg-slate-900/35"></button>
    <div class="absolute left-1/2 top-1/2 w-full max-w-sm -translate-x-1/2 -translate-y-1/2 rounded-xl border border-gray-200 bg-white p-4 shadow-xl">
        <h3 class="text-base font-semibold text-gray-900">Delete Stage</h3>
        <p id="stageDeleteMessage" class="mt-1 text-xs text-gray-500">Are you sure you want to delete this stage?</p>
        <p id="stageDeleteError" class="mt-2 hidden text-xs text-red-600"></p>
        <div class="mt-4 flex items-center justify-end gap-2">
            <button id="stageDeleteCancel" type="button" class="h-9 rounded-lg border border-gray-300 px-3 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
            <button id="stageDeleteConfirm" type="button" class="h-9 rounded-lg bg-red-600 px-3 text-sm font-medium text-white hover:bg-red-700">Delete</button>
        </div>
    </div>
</div>

@include('deals.partials.create-deal-modal', [
    'stageOptions' => $stageOptions,
    'companyOptions' => $companyOptions,
    'contactOptions' => $contactOptions,
    'contactRecords' => $contactRecords,
    'serviceAreaOptions' => $serviceAreaOptions ?? [],
    'serviceGroups' => $serviceGroups ?? [],
    'servicePricing' => $servicePricing ?? [],
    'serviceRequirementCatalog' => $serviceRequirementCatalog ?? [],
    'productOptionsByServiceArea' => $productOptionsByServiceArea ?? [],
    'productPricing' => $productPricing ?? [],
    'ownerLabel' => $ownerLabel,
    'owners' => $owners,
    'financeUsers' => $financeUsers ?? [],
    'employeeOptions' => $employeeOptions ?? [],
    'defaultOwnerId' => $defaultOwnerId,
    'dealDraft' => $dealDraft ?? [],
    'openDealModal' => $openDealModal ?? false,
])

<script>
document.addEventListener('DOMContentLoaded', () => {
    const board = document.getElementById('dealsBoard');
    const dealAccessDeniedModal = document.getElementById('dealAccessDeniedModal');
    const dealAccessDeniedClose = document.getElementById('dealAccessDeniedClose');
    const selectionBar = document.getElementById('dealSelectionBar');
    const selectedCount = document.getElementById('selectedDealCount');
    const clearBtn = document.getElementById('clearDealSelectionBtn');
    const totalDealsCount = document.getElementById('totalDealsCount');
    const addDealButton = document.getElementById('openCreateDealModalBtn');
    const modal = document.getElementById('createDealModal');
    const form = document.getElementById('createDealForm');
    const formTitle = document.getElementById('dealPanelTitle');
    const formSubtitle = document.getElementById('dealPanelSubtitle');
    const saveDealBtn = document.getElementById('saveDealBtn');
    const stageInput = document.getElementById('stage');
    const dealNameInput = document.getElementById('deal_name');
    const dealInfoCodeInput = document.getElementById('deal_info_code');
    const dealInfoCreatedByInput = document.getElementById('deal_info_created_by');
    const dealInfoCreatedAtInput = document.getElementById('deal_info_created_at');
    const contactSearchInput = document.getElementById('dealContactSearch');
    const contactIdInput = document.getElementById('deal_selected_contact_id');
    const ownerInput = document.getElementById('deal_selected_owner_id');
    const requiredMessage = document.getElementById('dealContactRequiredMessage');
    const dependentSections = document.getElementById('dealDependentSections');
    const stageModal = document.getElementById('stageMiniModal');
    const stageModalOverlay = document.getElementById('stageMiniModalOverlay');
    const stageModalTitle = document.getElementById('stageMiniModalTitle');
    const stageModalSubtitle = document.getElementById('stageMiniModalSubtitle');
    const stageModalInput = document.getElementById('stageMiniModalInput');
    const stageModalSave = document.getElementById('stageMiniModalSave');
    const stageModalCancel = document.getElementById('stageMiniModalCancel');
    const stageModalError = document.getElementById('stageMiniModalError');
    const stageDeleteModal = document.getElementById('stageDeleteModal');
    const stageDeleteOverlay = document.getElementById('stageDeleteOverlay');
    const stageDeleteCancel = document.getElementById('stageDeleteCancel');
    const stageDeleteConfirm = document.getElementById('stageDeleteConfirm');
    const stageDeleteMessage = document.getElementById('stageDeleteMessage');
    const stageDeleteError = document.getElementById('stageDeleteError');
    const dealRecords = @json($dealRecords ?? []);
    const csrfToken = document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || @json(csrf_token());
    let stageModalMode = null;
    let activeStageName = null;
    let activeStageSection = null;
    const defaultDealInfo = {
        code: 'Auto-generated after save',
        createdBy: @json(auth()->user()->name ?? 'System'),
        createdAt: @json(now()->format('F d, Y • h:i:s A')),
    };
    let liveCreatedAtTimer = null;
    let liveCreatedAtStartedAt = new Date();

    const setDealInfo = ({ code, createdBy, createdAt } = {}) => {
        if (dealInfoCodeInput) {
            dealInfoCodeInput.textContent = code || defaultDealInfo.code;
        }
        if (dealInfoCreatedByInput) {
            dealInfoCreatedByInput.textContent = createdBy || defaultDealInfo.createdBy;
        }
        if (dealInfoCreatedAtInput) {
            dealInfoCreatedAtInput.textContent = createdAt || defaultDealInfo.createdAt;
        }
    };

    const formatLiveTimestamp = (date) => {
        const formatter = new Intl.DateTimeFormat('en-US', {
            month: 'long',
            day: '2-digit',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            second: '2-digit',
            hour12: true,
        });

        return formatter.format(date).replace(',', ' •');
    };

    const stopLiveCreatedAt = () => {
        if (liveCreatedAtTimer) {
            window.clearInterval(liveCreatedAtTimer);
            liveCreatedAtTimer = null;
        }
    };

    const startLiveCreatedAt = () => {
        stopLiveCreatedAt();
        liveCreatedAtStartedAt = new Date();
        setDealInfo({
            code: defaultDealInfo.code,
            createdBy: defaultDealInfo.createdBy,
            createdAt: formatLiveTimestamp(liveCreatedAtStartedAt),
        });

        liveCreatedAtTimer = window.setInterval(() => {
            if (modal?.classList.contains('hidden')) {
                stopLiveCreatedAt();
                return;
            }

            if (dealInfoCreatedAtInput) {
                dealInfoCreatedAtInput.textContent = formatLiveTimestamp(new Date());
            }
        }, 1000);
    };

    const setFormModeCreate = () => {
        if (!form) {
            return;
        }
        const currentOwnerId = ownerInput?.value || '';
        form.reset();
        if (ownerInput && currentOwnerId !== '') {
            ownerInput.value = currentOwnerId;
        }
        if (contactIdInput) {
            contactIdInput.value = '';
        }
        form.action = @json(route('deals.store'));
        form.querySelector('input[name="_method"]')?.remove();
        formTitle.textContent = 'Create Deal';
        formSubtitle.textContent = 'Select an existing client, then complete the consulting and deal form.';
        saveDealBtn.textContent = 'Save & View Deal';
        startLiveCreatedAt();
        enableDependentSections(false);
    };

    const ensureStageOption = (stageName) => {
        if (!stageInput || !stageName) {
            return;
        }
        const exists = Array.from(stageInput.options).some((option) => option.value === stageName);
        if (!exists) {
            const option = document.createElement('option');
            option.value = stageName;
            option.textContent = stageName;
            stageInput.appendChild(option);
        }
    };

    const replaceStageOption = (oldName, newName) => {
        if (!stageInput || !oldName || !newName) {
            return;
        }
        const existingOption = Array.from(stageInput.options).find((option) => option.value === oldName);
        if (existingOption) {
            existingOption.value = newName;
            existingOption.textContent = newName;
            return;
        }
        ensureStageOption(newName);
    };

    const removeStageOption = (stageName) => {
        if (!stageInput || !stageName) {
            return;
        }
        const existingOption = Array.from(stageInput.options).find((option) => option.value === stageName);
        if (existingOption) {
            existingOption.remove();
        }
    };

    const setFormModeEdit = (card) => {
        if (!form || !card) {
            return;
        }
        const editUrl = card.dataset.editUrl || '';
        if (!editUrl) {
            return;
        }
        form.action = editUrl;
        let methodInput = form.querySelector('input[name="_method"]');
        if (!methodInput) {
            methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            form.appendChild(methodInput);
        }
        methodInput.value = 'PUT';
        formTitle.textContent = 'Edit Deal';
        formSubtitle.textContent = 'Update the selected deal details.';
        saveDealBtn.textContent = 'Update Deal';
        stopLiveCreatedAt();
        setDealInfo({
            code: card.dataset.dealCode || defaultDealInfo.code,
            createdBy: card.dataset.dealCreatedBy || defaultDealInfo.createdBy,
            createdAt: card.dataset.dealCreatedAt || defaultDealInfo.createdAt,
        });
    };

    const setInputValueByName = (name, value) => {
        const field = form?.querySelector(`[name="${name}"]`);
        if (!field) {
            return;
        }

        if (field.tagName === 'SELECT') {
            const hasOption = Array.from(field.options).some((option) => option.value === String(value ?? ''));
            field.value = hasOption ? String(value ?? '') : '';
            return;
        }

        field.value = value ?? '';
    };

    const setRadioGroupValue = (name, value) => {
        Array.from(form?.querySelectorAll(`input[name="${name}"]`) || []).forEach((input) => {
            input.checked = String(input.value) === String(value ?? '');
        });
    };

    const setCheckboxGroupValues = (name, values) => {
        const normalized = Array.isArray(values) ? values.map((value) => String(value)) : [];
        Array.from(form?.querySelectorAll(`input[name="${name}"]`) || []).forEach((input) => {
            input.checked = normalized.includes(String(input.value));
        });
    };

    const hydrateEditForm = (dealData) => {
        if (!form || !dealData) {
            return;
        }

        form.reset();

        if (ownerInput) {
            ownerInput.value = String(dealData.owner_id ?? ownerInput.value ?? '');
            const selectedOwner = Array.from(document.querySelectorAll('.deal-owner-option'))
                .find((option) => String(option.dataset.ownerId || '') === String(ownerInput.value || ''));
            const ownerLabel = document.getElementById('dealOwnerSelectedLabel');
            if (ownerLabel && selectedOwner) {
                ownerLabel.textContent = `Owner: ${selectedOwner.dataset.ownerName || ''}`;
            }
        }

        if (contactIdInput) {
            contactIdInput.value = String(dealData.contact_id ?? '');
        }

        setRadioGroupValue('customer_type', dealData.customer_type || '');
        setRadioGroupValue('engagement_type', dealData.engagement_type || '');
        setRadioGroupValue('payment_terms', dealData.payment_terms || '');
        setRadioGroupValue('service_complexity', dealData.service_complexity || '');
        setRadioGroupValue('proposal_decision', dealData.proposal_decision || '');

        [
            'deal_name',
            'stage',
            'salutation',
            'first_name',
            'middle_initial',
            'last_name',
            'name_extension',
            'sex',
            'date_of_birth',
            'email',
            'mobile',
            'address',
            'company_name',
            'company_address',
            'position',
            'scope_of_work',
            'estimated_professional_fee',
            'estimated_government_fee',
            'estimated_government_fees',
            'estimated_service_support_fee',
            'total_service_fee',
            'total_product_fee',
            'total_estimated_engagement_value',
            'planned_start_date',
            'estimated_duration',
            'estimated_completion_date',
            'client_preferred_completion_date',
            'confirmed_delivery_date',
            'timeline_notes',
            'complexity_notes',
            'decline_reason',
            'assigned_consultant',
            'assigned_associate',
            'assigned_finance_user_id',
            'service_department_unit',
            'consultant_notes',
            'associate_notes',
            'prepared_by',
            'reviewed_by',
            'internal_name',
            'internal_date',
            'client_fullname_signature',
            'referred_closed_by',
            'internal_sales_marketing',
            'lead_consultant',
            'lead_associate_assigned',
            'internal_finance',
            'internal_president',
            'payment_terms_other',
        ].forEach((name) => {
            if (Object.prototype.hasOwnProperty.call(dealData, name)) {
                setInputValueByName(name, dealData[name] ?? '');
            }
        });

        setCheckboxGroupValues('service_area_options[]', dealData.service_area_options || []);
        setCheckboxGroupValues('service_options[]', dealData.service_options || []);
        setCheckboxGroupValues('product_options[]', dealData.product_options || []);
        setCheckboxGroupValues('required_actions_options[]', dealData.required_actions_options || []);
        setCheckboxGroupValues('support_required_options[]', dealData.support_required_options || []);

        const requirementsMap = dealData.requirements_status_map || {};
        Object.entries(requirementsMap).forEach(([key, status]) => {
            const selector = `input[name="requirements_status[${key}]"][value="${status}"]`;
            const input = form.querySelector(selector);
            if (input) {
                input.checked = true;
            }
        });

        if (otherFeesRows) {
            otherFeesRows.innerHTML = '';
            const fees = Array.isArray(dealData.other_fees) ? dealData.other_fees : [];
            fees.forEach((fee) => {
                otherFeesRows.appendChild(createOtherFeeRow(fee.title || '', fee.amount || ''));
            });
        }

        if (stageInput && dealData.stage) {
            ensureStageOption(dealData.stage);
            stageInput.value = dealData.stage;
        }

        if (dealNameInput) {
            dealNameInput.value = dealData.deal_name || dealData.deal_code || '';
        }

        if (contactSearchInput) {
            const fullName = [dealData.first_name, dealData.last_name].filter(Boolean).join(' ').trim();
            contactSearchInput.value = fullName || dealData.company_name || '';
        }

        document.querySelectorAll('input[name="customer_type"]').forEach((input) => {
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
        document.querySelectorAll('input[name="service_area_options[]"]').forEach((input) => {
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        enableDependentSections(Boolean(dealData.contact_id));
    };

    const enableDependentSections = (enabled) => {
        if (!dependentSections) {
            return;
        }
        dependentSections.classList.toggle('opacity-60', !enabled);
        dependentSections.classList.toggle('pointer-events-none', !enabled);
        requiredMessage?.classList.toggle('hidden', enabled);
        if (saveDealBtn) {
            saveDealBtn.disabled = !enabled;
            saveDealBtn.classList.toggle('opacity-60', !enabled);
            saveDealBtn.classList.toggle('cursor-not-allowed', !enabled);
        }
    };

    const openDealPanel = (options = {}) => {
        addDealButton?.click();
        window.setTimeout(() => {
            if (options.mode === 'edit' && options.card) {
                const card = options.card;
                setFormModeEdit(card);
                const dealData = dealRecords[String(card.dataset.dealId || '')] || null;
                if (dealData) {
                    hydrateEditForm(dealData);
                } else {
                    dealNameInput.value = card.dataset.dealName || '';
                    if (stageInput && card.dataset.dealStage) {
                        ensureStageOption(card.dataset.dealStage);
                        stageInput.value = card.dataset.dealStage;
                    }
                    const amount = Number.parseFloat(card.dataset.dealAmount || '0');
                    const totalField = document.getElementById('total_estimated_engagement_value');
                    if (totalField && amount > 0) {
                        totalField.value = amount.toFixed(2);
                    }
                    const contactName = card.dataset.dealContact || '';
                    if (contactSearchInput) {
                        contactSearchInput.value = contactName;
                    }
                    const matched = contactRecords.find((record) => (record.label || '').toLowerCase().includes(contactName.toLowerCase()));
                    if (contactIdInput && matched) {
                        contactIdInput.value = String(matched.id);
                        enableDependentSections(true);
                    }
                }
            } else {
                setFormModeCreate();
                if (options.stage && stageInput) {
                    ensureStageOption(options.stage);
                    stageInput.value = options.stage;
                }
            }
        }, 80);
    };

    const cards = () => Array.from(document.querySelectorAll('.deal-card'));

    dealAccessDeniedClose?.addEventListener('click', () => {
        dealAccessDeniedModal?.classList.add('hidden');
    });

    document.querySelectorAll('[data-price-toggle]').forEach((toggle) => {
        const handleToggle = (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (toggle.dataset.priceAccess === '0') {
                toggle.setAttribute('aria-label', 'Price access restricted');
                toggle.setAttribute('title', 'Price access restricted');
                return;
            }

            const wrap = toggle.closest('[data-price-wrap]');
            if (!wrap) {
                return;
            }

            const isVisible = wrap.dataset.priceVisible === 'true';
            wrap.dataset.priceVisible = isVisible ? 'false' : 'true';
            wrap.querySelector('[data-price-hidden]')?.classList.toggle('hidden', !isVisible);
            wrap.querySelector('[data-price-value]')?.classList.toggle('hidden', isVisible);

            const icon = wrap.querySelector('[data-price-icon]');
            if (icon) {
                icon.classList.toggle('fa-eye', isVisible);
                icon.classList.toggle('fa-eye-slash', !isVisible);
            }

            toggle.setAttribute('aria-label', isVisible ? 'Show price' : 'Hide price');
            toggle.setAttribute('title', isVisible ? 'Show price' : 'Hide price');
        };

        toggle.addEventListener('click', handleToggle);
        toggle.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                handleToggle(event);
            }
        });
    });
    const checkboxes = () => Array.from(document.querySelectorAll('.deal-select-checkbox'));
    const stageCheckboxes = () => Array.from(document.querySelectorAll('.stage-select-checkbox'));

    const updateTotalDealsCount = () => {
        if (!totalDealsCount) {
            return;
        }
        totalDealsCount.textContent = `${cards().length} deals`;
    };

    const updateStageHeaderMeta = (section) => {
        if (!section) {
            return;
        }
        const stageCards = Array.from(section.querySelectorAll('.deal-card'));
        const total = stageCards.reduce((sum, card) => sum + Number.parseFloat(card.dataset.dealAmount || '0'), 0);
        const stageTotal = section.querySelector('.stage-total');
        if (stageTotal) {
            const formatted = `P${Math.round(total).toLocaleString()}`;
            stageTotal.textContent = `${formatted} • ${stageCards.length} ${stageCards.length === 1 ? 'Deal' : 'Deals'}`;
        }
    };

    const refreshAllStageMeta = () => {
        Array.from(document.querySelectorAll('.stage-column')).forEach((section) => updateStageHeaderMeta(section));
        updateTotalDealsCount();
    };

    const syncStageCheckbox = (section) => {
        const stageCheckbox = section?.querySelector('.stage-select-checkbox');
        const dealCheckboxes = Array.from(section?.querySelectorAll('.deal-select-checkbox') || []);
        if (!stageCheckbox) {
            return;
        }

        const checkedCount = dealCheckboxes.filter((checkbox) => checkbox.checked).length;
        stageCheckbox.checked = dealCheckboxes.length > 0 && checkedCount === dealCheckboxes.length;
        stageCheckbox.indeterminate = checkedCount > 0 && checkedCount < dealCheckboxes.length;
        stageCheckbox.classList.toggle('opacity-100', stageCheckbox.checked || stageCheckbox.indeterminate);
        stageCheckbox.classList.toggle('opacity-0', !stageCheckbox.checked && !stageCheckbox.indeterminate);
    };

    const syncAllStageCheckboxes = () => {
        document.querySelectorAll('.stage-column').forEach((section) => syncStageCheckbox(section));
    };

    const updateSelectionUI = () => {
        const selected = checkboxes().filter((checkbox) => checkbox.checked);
        selectedCount.textContent = String(selected.length);
        selectionBar.classList.toggle('hidden', selected.length === 0);
        selectionBar.classList.toggle('flex', selected.length > 0);

        checkboxes().forEach((checkbox) => {
            const card = checkbox.closest('.deal-card');
            if (!card) {
                return;
            }
            card.classList.toggle('ring-2', checkbox.checked);
            card.classList.toggle('ring-blue-400', checkbox.checked);
            card.classList.toggle('border-blue-300', checkbox.checked);
            const cardCheckbox = card.querySelector('.deal-card-checkbox');
            if (cardCheckbox) {
                cardCheckbox.classList.toggle('opacity-100', checkbox.checked);
                cardCheckbox.classList.toggle('pointer-events-auto', checkbox.checked);
                cardCheckbox.classList.toggle('opacity-0', !checkbox.checked);
                cardCheckbox.classList.toggle('pointer-events-none', !checkbox.checked);
            }
            const quickActions = card.querySelector('.deal-quick-actions');
            if (quickActions && checkbox.checked) {
                quickActions.classList.remove('opacity-0', 'pointer-events-none');
                quickActions.classList.add('opacity-100', 'pointer-events-auto');
            }
        });

        syncAllStageCheckboxes();
    };

    const closeAllMenus = () => {
        document.querySelectorAll('.stage-actions-menu').forEach((menu) => menu.classList.add('hidden'));
        document.querySelectorAll('.deal-more-menu').forEach((menu) => menu.classList.add('hidden'));
    };

    const emptyStateMarkup = '<div class="rounded-lg border border-dashed border-gray-300 bg-white px-3 py-5 text-center text-xs text-gray-500">No deals in this stage.</div>';

    const stageColumnMarkup = (stage) => `
        <section class="stage-column w-[230px] overflow-hidden rounded-xl border border-gray-200 bg-gray-50" data-stage-id="${stage.id}" data-stage-column="${stage.name}" data-stage-color="${stage.color || '#1e293b'}">
            <header class="stage-header group/column rounded-t-xl border-t-4 bg-slate-800 px-3 py-2 text-white" style="border-top-color: ${stage.color || '#1e293b'};">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <div class="min-w-0">
                            <h2 class="stage-name cursor-text text-xs font-semibold">${stage.name}</h2>
                            <input type="text" class="stage-name-input hidden h-5 w-full rounded border border-white/30 bg-white/10 px-1 text-xs font-semibold text-white outline-none focus:border-white/60" value="${stage.name}">
                            <p class="stage-total text-xs opacity-90">P0 • 0 Deals</p>
                        </div>
                    </div>
                    <div class="relative flex items-start gap-2">
                        <input type="checkbox" class="stage-select-checkbox mt-0.5 h-3.5 w-3.5 rounded border-white/40 bg-white/10 opacity-0 transition group-hover/column:opacity-100">
                        <button type="button" class="stage-actions-trigger opacity-0 transition group-hover/column:opacity-100" data-stage="${stage.name}" title="Stage actions">
                            <i class="fas fa-ellipsis-vertical text-xs text-white/90"></i>
                        </button>
                        <div class="stage-actions-menu absolute right-0 z-20 mt-2 hidden w-48 rounded-lg border border-gray-200 bg-white py-1 text-xs text-gray-700 shadow-lg" data-stage-menu="${stage.name}">
                            <button type="button" class="stage-action-item flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-gray-50" data-action="new-stage">New Stage</button>
                            <button type="button" class="stage-action-item flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-gray-50" data-action="move-left">Move Left</button>
                            <button type="button" class="stage-action-item flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-gray-50" data-action="move-right">Move Right</button>
                            <button type="button" class="stage-action-item flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-gray-50" data-action="rename-stage">Rename Stage</button>
                            <div class="border-t border-gray-100 px-3 py-2">
                                <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-gray-400">Change Stage Color</p>
                                <div class="flex flex-wrap gap-2">
                                    ${['#2563eb', '#4f46e5', '#0891b2', '#d97706', '#ea580c', '#059669', '#7c3aed', '#dc2626'].map((color) => `<button type="button" class="stage-color-option h-4 w-4 rounded-full border border-gray-200" data-action="change-color" data-color="${color}" style="background-color: ${color};"></button>`).join('')}
                                </div>
                            </div>
                            <button type="button" class="stage-action-item flex w-full items-center gap-2 px-3 py-2 text-left text-red-600 hover:bg-red-50" data-action="delete-stage">Delete Stage</button>
                        </div>
                    </div>
                </div>
            </header>
            <div class="stage-cards space-y-2 p-2">
                <div class="rounded-lg border border-dashed border-gray-300 bg-white px-3 py-5 text-center text-xs text-gray-500">No deals in this stage.</div>
            </div>
            <div class="border-t border-gray-200 px-2 py-2">
                <p class="stage-description text-[11px] text-gray-500" data-stage-description="${stage.name}">No description yet.</p>
            </div>
        </section>
    `;

    const api = async (url, method = 'GET', body = null) => {
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body ? JSON.stringify(body) : null,
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(payload.message || 'Stage action failed.');
        }
        return payload;
    };

    const beginInlineRename = (section) => {
        const title = section?.querySelector('.stage-name');
        const input = section?.querySelector('.stage-name-input');
        if (!title || !input) {
            return;
        }
        title.classList.add('hidden');
        input.classList.remove('hidden');
        input.value = title.textContent.trim();
        input.focus();
        input.select();
    };

    const finishInlineRename = async (section, shouldSave) => {
        const title = section?.querySelector('.stage-name');
        const input = section?.querySelector('.stage-name-input');
        if (!title || !input) {
            return;
        }

        const originalName = section.dataset.stageColumn || title.textContent.trim();
        const nextName = input.value.trim();

        if (shouldSave && nextName !== '' && nextName !== originalName && section.dataset.stageId) {
            const payload = await api(`{{ url('/deals/stages') }}/${section.dataset.stageId}`, 'PATCH', { name: nextName });
            section.dataset.stageColumn = payload.stage.name;
            title.textContent = payload.stage.name;
            input.value = payload.stage.name;
            syncStageReferences(section, payload.stage.name);
            replaceStageOption(originalName, payload.stage.name);
        } else {
            input.value = originalName;
        }

        title.classList.remove('hidden');
        input.classList.add('hidden');
    };

    const syncStageReferences = (section, stageName) => {
        if (!section || !stageName) {
            return;
        }

        section.dataset.stageColumn = stageName;
        section.querySelector('.stage-actions-trigger')?.setAttribute('data-stage', stageName);
        section.querySelector('.stage-actions-menu')?.setAttribute('data-stage-menu', stageName);
        section.querySelector('.stage-description')?.setAttribute('data-stage-description', stageName);
        section.querySelectorAll('.deal-card').forEach((card) => {
            card.dataset.dealStage = stageName;
        });
    };

    const applyStageColor = (section, color) => {
        if (!section) {
            return;
        }

        const fallbackColor = '#1e293b';
        const appliedColor = color || fallbackColor;
        section.dataset.stageColor = appliedColor;
        const header = section.querySelector('.stage-header');
        if (header) {
            header.style.borderTopColor = appliedColor;
        }
    };

    const ensureEmptyState = (section) => {
        const cardsContainer = section?.querySelector('.stage-cards');
        if (!cardsContainer) {
            return;
        }

        const dealCards = cardsContainer.querySelectorAll('.deal-card');
        const emptyState = cardsContainer.querySelector('.rounded-lg.border-dashed');

        if (dealCards.length === 0 && !emptyState) {
            cardsContainer.insertAdjacentHTML('beforeend', emptyStateMarkup);
        }

        if (dealCards.length > 0 && emptyState) {
            emptyState.remove();
        }

        syncStageCheckbox(section);
    };

    const moveStageColumn = (section, direction) => {
        if (!board || !section) {
            return;
        }

        const sibling = direction === 'left' ? section.previousElementSibling : section.nextElementSibling;
        if (!sibling) {
            return;
        }

        if (direction === 'left') {
            board.insertBefore(section, sibling);
            return;
        }

        board.insertBefore(sibling, section);
    };

    const openDeleteStageModal = (section) => {
        activeStageSection = section;
        stageDeleteError.classList.add('hidden');
        stageDeleteError.textContent = '';
        stageDeleteMessage.textContent = `Delete stage "${section?.dataset.stageColumn || 'this stage'}"?`;
        stageDeleteModal.classList.remove('hidden');
        stageDeleteModal.setAttribute('aria-hidden', 'false');
    };

    const closeDeleteStageModal = () => {
        stageDeleteModal.classList.add('hidden');
        stageDeleteModal.setAttribute('aria-hidden', 'true');
        stageDeleteError.classList.add('hidden');
        stageDeleteError.textContent = '';
        activeStageSection = null;
    };

    const openStageMiniModal = (mode, stageName) => {
        stageModalMode = mode;
        activeStageName = stageName;
        activeStageSection = Array.from(document.querySelectorAll('.stage-column')).find((column) => column.dataset.stageColumn === stageName) || null;
        stageModalError.classList.add('hidden');
        stageModalError.textContent = '';
        stageModal.classList.remove('hidden');
        stageModal.setAttribute('aria-hidden', 'false');
        stageModalTitle.textContent = 'New Stage';
        stageModalSubtitle.textContent = 'Add a new stage at the end of the pipeline.';
        stageModalInput.value = '';
        stageModalInput.rows = 1;
        stageModalInput.focus();
    };

    const closeStageMiniModal = () => {
        stageModal.classList.add('hidden');
        stageModal.setAttribute('aria-hidden', 'true');
        stageModalMode = null;
        activeStageName = null;
    };

    const handleCreateStage = async () => {
        const stageName = stageModalInput.value.trim();
        if (stageName === '') {
            stageModalError.textContent = 'Stage name is required.';
            stageModalError.classList.remove('hidden');
            return;
        }
        const exists = Array.from(document.querySelectorAll('.stage-column')).some((column) => (column.dataset.stageColumn || '').toLowerCase() === stageName.toLowerCase());
        if (exists) {
            stageModalError.textContent = 'That stage already exists.';
            stageModalError.classList.remove('hidden');
            return;
        }

        const payload = await api(`{{ route('deals.stages.store') }}`, 'POST', { name: stageName });
        const wrapper = document.createElement('div');
        wrapper.innerHTML = stageColumnMarkup(payload.stage);
        const column = wrapper.firstElementChild;
        board?.appendChild(column);
        ensureStageOption(payload.stage.name);
        closeStageMiniModal();
        refreshAllStageMeta();
    };

    const deleteCard = (card) => {
        if (!card) {
            return;
        }
        if (!window.confirm('Delete this deal card from the board?')) {
            return;
        }
        const section = card.closest('.stage-column');
        card.remove();
        ensureEmptyState(section);
        updateSelectionUI();
        updateStageHeaderMeta(section);
    };

    addDealButton?.addEventListener('click', () => {
        setFormModeCreate();
    });

    clearBtn?.addEventListener('click', () => {
    checkboxes().forEach((checkbox) => {
        const cardCheckbox = checkbox.closest('.deal-card')?.querySelector('.deal-card-checkbox');
        if (cardCheckbox && !checkbox.checked) {
            cardCheckbox.classList.add('opacity-0', 'pointer-events-none');
            cardCheckbox.classList.remove('opacity-100', 'pointer-events-auto');
        }
    });
    checkboxes().forEach((checkbox) => {
        checkbox.checked = false;
    });
        updateSelectionUI();
    });

    stageModalCancel?.addEventListener('click', closeStageMiniModal);
    stageModalOverlay?.addEventListener('click', closeStageMiniModal);
    stageModalSave?.addEventListener('click', async () => {
        if (stageModalMode === 'new-stage') {
            try {
                await handleCreateStage();
            } catch (error) {
                stageModalError.textContent = error.message || 'Unable to create stage.';
                stageModalError.classList.remove('hidden');
            }
            return;
        }
    });
    stageModalInput?.addEventListener('keydown', async (event) => {
        if (event.key !== 'Enter') {
            return;
        }
        event.preventDefault();
        if (stageModalMode === 'new-stage') {
            try {
                await handleCreateStage();
            } catch (error) {
                stageModalError.textContent = error.message || 'Unable to create stage.';
                stageModalError.classList.remove('hidden');
            }
        }
    });
    stageDeleteCancel?.addEventListener('click', closeDeleteStageModal);
    stageDeleteOverlay?.addEventListener('click', closeDeleteStageModal);
    stageDeleteConfirm?.addEventListener('click', async () => {
        if (!activeStageSection?.dataset.stageId) {
            closeDeleteStageModal();
            return;
        }

        try {
            await api(`{{ url('/deals/stages') }}/${activeStageSection.dataset.stageId}`, 'DELETE');
            const removedStageName = activeStageSection.dataset.stageColumn || '';
            activeStageSection.remove();
            removeStageOption(removedStageName);
            closeDeleteStageModal();
            refreshAllStageMeta();
        } catch (error) {
            stageDeleteError.textContent = error.message || 'Unable to delete stage.';
            stageDeleteError.classList.remove('hidden');
        }
    });

    board?.addEventListener('click', async (event) => {
        const stageTrigger = event.target.closest('.stage-actions-trigger');
        const stageAction = event.target.closest('.stage-action-item');
        const stageColorOption = event.target.closest('.stage-color-option');
        const stageName = event.target.closest('.stage-name');
        const dealMoreBtn = event.target.closest('.deal-more-btn');
        const dealMenuItem = event.target.closest('.deal-menu-item');
        const editBtn = event.target.closest('.deal-edit-btn');
        const viewBtn = event.target.closest('a[title="View"]');

        if (viewBtn) {
            return;
        }

        if (stageTrigger) {
            event.preventDefault();
            event.stopPropagation();
            const stage = stageTrigger.dataset.stage;
            const menu = Array.from(document.querySelectorAll('[data-stage-menu]')).find((item) => item.dataset.stageMenu === stage);
            const isOpen = menu && !menu.classList.contains('hidden');
            closeAllMenus();
            if (menu && !isOpen) {
                menu.classList.remove('hidden');
            }
            return;
        }

        if (stageName) {
            event.preventDefault();
            event.stopPropagation();
            beginInlineRename(stageName.closest('.stage-column'));
            return;
        }

        if (stageColorOption) {
            event.preventDefault();
            event.stopPropagation();
            const section = stageColorOption.closest('.stage-column');
            const color = stageColorOption.dataset.color || '';
            closeAllMenus();

            if (!section?.dataset.stageId) {
                return;
            }

            try {
                const payload = await api(`{{ url('/deals/stages') }}/${section.dataset.stageId}`, 'PATCH', { color });
                applyStageColor(section, payload.stage.color);
            } catch (error) {
                window.alert(error.message || 'Unable to update stage color.');
            }
            return;
        }

        if (stageAction) {
            event.preventDefault();
            const section = stageAction.closest('.stage-column');
            const stage = section?.dataset.stageColumn || stageAction.closest('[data-stage-menu]')?.dataset.stageMenu || '';
            const action = stageAction.dataset.action;
            closeAllMenus();

            if (action === 'new-stage') {
                openStageMiniModal('new-stage', stage);
                return;
            }

            if (!section?.dataset.stageId) {
                return;
            }

            if (action === 'rename-stage') {
                beginInlineRename(section);
                return;
            }

            if (action === 'move-left' || action === 'move-right') {
                try {
                    await api(`{{ url('/deals/stages') }}/${section.dataset.stageId}/move`, 'PATCH', {
                        direction: action === 'move-left' ? 'left' : 'right',
                    });
                    moveStageColumn(section, action === 'move-left' ? 'left' : 'right');
                } catch (error) {
                    window.alert(error.message || 'Unable to move stage.');
                }
                return;
            }

            if (action === 'delete-stage') {
                openDeleteStageModal(section);
            }
            return;
        }

        if (editBtn) {
            event.preventDefault();
            event.stopPropagation();
            const card = editBtn.closest('.deal-card');
            openDealPanel({ mode: 'edit', card });
            return;
        }

        if (dealMoreBtn) {
            event.preventDefault();
            event.stopPropagation();
            const menu = dealMoreBtn.parentElement.querySelector('.deal-more-menu');
            const isOpen = menu && !menu.classList.contains('hidden');
            closeAllMenus();
            if (menu && !isOpen) {
                menu.classList.remove('hidden');
            }
            return;
        }

        if (dealMenuItem) {
            event.preventDefault();
            const card = dealMenuItem.closest('.deal-card');
            const action = dealMenuItem.dataset.action;
            closeAllMenus();
            if (!card) {
                return;
            }

            const canAccess = card.dataset.canAccess === '1';
            if (action === 'view') {
                window.location.href = card.dataset.viewUrl || '#';
                return;
            }
            if (action === 'edit') {
                if (!canAccess) {
                    window.alert("You don't have access to edit this deal.");
                    return;
                }
                openDealPanel({ mode: 'edit', card });
                return;
            }
            if (action === 'delete') {
                deleteCard(card);
                return;
            }
        }
    });

    board?.addEventListener('keydown', async (event) => {
        const input = event.target.closest('.stage-name-input');
        if (!input) {
            return;
        }

        const section = input.closest('.stage-column');
        if (event.key === 'Enter') {
            event.preventDefault();
            try {
                await finishInlineRename(section, true);
                ensureStageOption(section.querySelector('.stage-name')?.textContent.trim() || '');
            } catch (error) {
                window.alert(error.message || 'Unable to rename stage.');
                await finishInlineRename(section, false);
            }
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            await finishInlineRename(section, false);
        }
    });

    board?.addEventListener('focusout', async (event) => {
        const input = event.target.closest('.stage-name-input');
        if (!input || input.classList.contains('hidden')) {
            return;
        }

        const section = input.closest('.stage-column');
        try {
            await finishInlineRename(section, true);
            ensureStageOption(section.querySelector('.stage-name')?.textContent.trim() || '');
        } catch (error) {
            window.alert(error.message || 'Unable to rename stage.');
            await finishInlineRename(section, false);
        }
    });

    document.addEventListener('click', (event) => {
        const clickedStage = event.target.closest('.stage-actions-menu, .stage-actions-trigger');
        const clickedDeal = event.target.closest('.deal-more-menu, .deal-more-btn');
        if (!clickedStage && !clickedDeal) {
            closeAllMenus();
        }
    });

    document.addEventListener('deal-drawer:closed', () => {
        stopLiveCreatedAt();
    });

    cards().forEach((card) => {
        card.addEventListener('click', (event) => {
            const interactive = event.target.closest('a,button,input,label,textarea,select');
            if (interactive) {
                return;
            }
            const viewUrl = card.dataset.viewUrl;
            if (viewUrl) {
                window.location.href = viewUrl;
            }
        });
    });

    board?.addEventListener('change', (event) => {
        const dealCheckbox = event.target.closest('.deal-select-checkbox');
        if (dealCheckbox) {
            updateSelectionUI();
            return;
        }

        const stageCheckbox = event.target.closest('.stage-select-checkbox');
        if (stageCheckbox) {
            const section = stageCheckbox.closest('.stage-column');
            const stageDeals = Array.from(section?.querySelectorAll('.deal-select-checkbox') || []);
            stageDeals.forEach((checkbox) => {
                checkbox.checked = stageCheckbox.checked;
            });
            stageCheckbox.indeterminate = false;
            updateSelectionUI();
        }
    });

    stageCheckboxes().forEach((checkbox) => {
        checkbox.checked = false;
        checkbox.indeterminate = false;
        checkbox.classList.add('opacity-0');
        checkbox.classList.remove('opacity-100');
    });
    document.querySelectorAll('.stage-column').forEach((section) => {
        applyStageColor(section, section.dataset.stageColor || '');
        ensureEmptyState(section);
    });
    refreshAllStageMeta();
});
</script>
@endsection

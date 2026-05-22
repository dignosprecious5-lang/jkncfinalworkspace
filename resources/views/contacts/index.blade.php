@extends('layouts.app')
@section('title', 'Contacts')

@section('content')
@php
    $highlightMatch = function (string $value) use ($search): string {
        if ($search === '') {
            return e($value);
        }

        $escapedSearch = preg_quote($search, '/');

        return preg_replace(
            "/({$escapedSearch})/i",
            '<mark class="rounded bg-yellow-200 px-0.5 text-inherit">$1</mark>',
            e($value)
        ) ?: e($value);
    };

    $statusPillClasses = [
        'Verified' => 'bg-green-100 text-green-700 border border-green-200',
        'Pending Verification' => 'bg-amber-100 text-amber-700 border border-amber-200',
        'Not Submitted' => 'bg-gray-100 text-gray-600 border border-gray-200',
        'Rejected' => 'bg-red-100 text-red-700 border border-red-200',
    ];

    $selectedOwner = collect($owners)->firstWhere('id', (int) $defaultOwnerId) ?: collect($owners)->first();
    $selectedOwnerId = $selectedOwner['id'] ?? null;
    $selectedOwnerName = $selectedOwner['name'] ?? 'Select Owner';
    $shouldOpenCreateModal = request()->boolean('open_create') || old('open_create');
    $prefilledCompanyName = old('company_name', request()->query('company_name', ''));
    $prefillContact = [
        'first_name' => old('first_name', request()->query('first_name', '')),
        'last_name' => old('last_name', request()->query('last_name', '')),
        'email' => old('email', request()->query('email', '')),
        'mobile_number' => old('mobile_number', request()->query('mobile_number', '')),
        'position' => old('position', request()->query('position', '')),
        'contact_address' => old('contact_address', request()->query('contact_address', '')),
    ];
    $authUser = auth()->user();
    $canDeleteRecords = $authUser
        && ($authUser->isAdmin()
            || $authUser->isSuperAdmin()
            || $authUser->hasPermission('approve_corporate')
            || $authUser->hasPermission('access_admin_dashboard'));
@endphp

<div class="px-6 py-6 lg:px-8">
    <div class="mb-5">
        <h1 class="text-3xl font-semibold text-gray-900">Contacts</h1>
        <p class="mt-1 text-sm text-gray-500">Manage your client contacts and deal relationships</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <form id="contactsSearchForm" method="GET" action="{{ route('contacts.index') }}" class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative w-full max-w-md">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400"></i>
            <input
                id="contactsSearchInput"
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Search Contacts..."
                autocomplete="off"
                class="h-10 w-full rounded-lg border border-gray-200 bg-white pl-8 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            >
        </div>

        <select
            name="kyc"
            class="h-10 min-w-[130px] rounded-lg border border-gray-200 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            onchange="this.form.submit()"
        >
            <option value="All" {{ $kycFilter === 'All' ? 'selected' : '' }}>All</option>
            @foreach ($kycStatuses as $status)
                <option value="{{ $status }}" {{ $kycFilter === $status ? 'selected' : '' }}>{{ $status }}</option>
            @endforeach
        </select>
        <button
            type="button"
            id="openCreateContactModal"
            class="ml-auto h-10 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700"
        >
            + Add Contact
        </button>
    </form>

    <div id="selectionActionBar" class="mb-3 hidden rounded-lg border border-blue-100 bg-blue-50 px-3 py-2">
        <div class="flex items-center gap-2 text-sm">
            <span class="font-medium text-gray-800"><span id="selectedCount">0</span> selected</span>
            <button id="openAssignOwnerModal" type="button" class="h-8 rounded-md border border-gray-200 bg-white px-3 hover:bg-gray-50">Assign Owner</button>
            @if ($canDeleteRecords)
                <button id="openDeleteSelectedModal" type="button" class="h-8 rounded-md border border-red-200 bg-white px-3 text-red-600 hover:bg-red-50">Delete Selected</button>
            @endif
            <select class="h-8 rounded-md border border-gray-200 bg-white px-2">
                <option>Mark KYC Status</option>
                <option>Verified</option>
                <option>Pending Verification</option>
                <option>Rejected</option>
            </select>
            <button type="button" id="clearSelection" class="ml-auto text-gray-700 hover:underline">Clear</button>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                    <tr>
                        <th class="w-10 px-3 py-3 text-left"><input id="selectAll" type="checkbox" class="h-4 w-4 rounded border-gray-300"></th>
                        <th class="px-3 py-3 text-left">Contact Name</th>
                        <th class="px-3 py-3 text-left">Company Name</th>
                        <th class="px-3 py-3 text-left">Email</th>
                        <th class="px-3 py-3 text-left">Phone</th>
                        <th class="px-3 py-3 text-left">KYC Status</th>
                        <th class="px-3 py-3 text-left">Contact Owner</th>
                        <th class="px-3 py-3 text-left">Last Activity</th>
                        @foreach ($customFields as $field)
                            <th class="px-3 py-3 text-left">{{ $field['name'] }}</th>
                        @endforeach
                        <th class="px-3 py-3 text-right normal-case">
                            <button id="openCreateFieldDropdown" type="button" class="text-sm font-medium text-blue-600 hover:text-blue-700">+ Create Field</button>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($contacts as $contact)
                        @php
                            $initials = strtoupper(mb_substr($contact->first_name ?? '', 0, 1).mb_substr($contact->last_name ?? '', 0, 1));
                        @endphp
                        <tr class="text-gray-700">
                            <td class="px-3 py-3"><input type="checkbox" value="{{ $contact->id }}" class="row-checkbox h-4 w-4 rounded border-gray-300"></td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">{{ $initials }}</div>
                                    <a href="{{ route('contacts.show', $contact) }}" class="font-medium text-gray-900 hover:text-blue-700">
                                        {!! $highlightMatch(trim($contact->first_name.' '.$contact->last_name)) !!}
                                    </a>
                                </div>
                            </td>
                            <td class="px-3 py-3">{{ $contact->company_name ?: '-' }}</td>
                            <td class="px-3 py-3">{{ $contact->email ?: '-' }}</td>
                            <td class="px-3 py-3">{{ $contact->phone ?: '-' }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $statusPillClasses[$contact->kyc_status] ?? $statusPillClasses['Not Submitted'] }}">
                                    {{ $contact->kyc_status }}
                                </span>
                            </td>
                            <td class="px-3 py-3">{{ $contact->owner_name ?: '-' }}</td>
                            <td class="px-3 py-3">{{ $contact->last_activity_at?->diffForHumans() ?? 'No activity' }}</td>
                            @foreach ($customFields as $field)
                                @php
                                    $customValue = $field['default_value'] ?? '';
                                @endphp
                                <td class="px-3 py-3 text-gray-600">
                                    @if (($field['type'] ?? '') === 'checkbox')
                                        {{ $customValue === '1' ? 'Yes' : 'No' }}
                                    @elseif (($field['type'] ?? '') === 'currency' && $customValue !== '')
                                        P{{ number_format((float) $customValue, 2) }}
                                    @else
                                        {{ $customValue !== '' ? $customValue : '-' }}
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-3 py-3"></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 9 + count($customFields) }}" class="px-3 py-10 text-center text-sm text-gray-500">No contacts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-gray-700">
        <span>Total Contacts: {{ $contacts->total() }}</span>
        <span>Verified: <span class="text-green-600">{{ $statusCounts['Verified'] }}</span></span>
        <span>Pending: <span class="text-amber-600">{{ $statusCounts['Pending Verification'] }}</span></span>
        <span>Not Submitted: {{ $statusCounts['Not Submitted'] }}</span>
        <span>Rejected: <span class="text-red-600">{{ $statusCounts['Rejected'] }}</span></span>
        <div class="ml-auto flex items-center gap-2 text-sm text-gray-600">
            <a href="{{ $contacts->onFirstPage() ? '#' : $contacts->previousPageUrl() }}" class="rounded-md border border-gray-200 px-3 py-1 {{ $contacts->onFirstPage() ? 'pointer-events-none opacity-40' : 'hover:bg-gray-50' }}">Prev</a>
            @foreach ($contacts->getUrlRange(1, $contacts->lastPage()) as $page => $url)
                <a href="{{ $url }}" class="rounded-md border px-3 py-1 {{ $contacts->currentPage() === $page ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 hover:bg-gray-50' }}">{{ $page }}</a>
            @endforeach
            <a href="{{ $contacts->hasMorePages() ? $contacts->nextPageUrl() : '#' }}" class="rounded-md border border-gray-200 px-3 py-1 {{ $contacts->hasMorePages() ? 'hover:bg-gray-50' : 'pointer-events-none opacity-40' }}">Next</a>
        </div>
    </div>
</div>

@include('contacts.partials.create-modal', [
    'owners' => $owners,
    'selectedOwnerId' => $selectedOwnerId,
    'selectedOwnerName' => $selectedOwnerName,
    'createdByDisplay' => $createdByDisplay ?? 'Admin User',
    'createdAtDisplay' => $createdAtDisplay ?? now()->format('F j, Y • g:i A'),
    'defaultBusinessDate' => $defaultBusinessDate ?? now()->toDateString(),
    'prefilledCompanyName' => $prefilledCompanyName,
    'prefillContact' => $prefillContact,
    'employeeOptions' => $employeeOptions ?? [],
])

@if ($canDeleteRecords)
    <div id="deleteSelectedModal" class="fixed inset-0 z-[70] hidden" aria-hidden="true">
        <button id="deleteSelectedOverlay" type="button" aria-label="Close delete contacts modal" class="absolute inset-0 bg-slate-900/45"></button>
        <div class="absolute inset-0 flex items-center justify-center px-4">
            <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
                <div class="border-b border-gray-100 px-6 py-5">
                    <h2 class="text-xl font-semibold text-gray-900">Delete Selected Contacts</h2>
                    <p class="mt-1 text-sm text-gray-500">This action will permanently delete the selected contact records.</p>
                </div>
                <form id="bulkDeleteForm" method="POST" action="{{ route('contacts.bulk-delete') }}">
                    @csrf
                    @method('DELETE')
                    <div id="bulkDeleteSelectedContacts"></div>
                    <div class="px-6 py-5 text-sm text-gray-700">
                        Are you sure you want to delete <span id="bulkDeleteCountText" class="font-semibold text-gray-900">0 contacts</span>?
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                        <button id="cancelDeleteSelectedModal" type="button" class="h-10 rounded-lg border border-gray-300 px-4 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="h-10 rounded-lg bg-red-600 px-5 text-sm font-medium text-white hover:bg-red-700">Delete Selected</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<div id="assignOwnerModal" class="fixed inset-0 z-[70] hidden" aria-hidden="true">
    <button id="assignOwnerOverlay" type="button" aria-label="Close assign owner panel" class="absolute inset-0 bg-slate-900/45 opacity-0 transition-opacity duration-300"></button>
    <div class="absolute inset-y-0 right-0 flex w-full justify-end overflow-hidden pointer-events-none">
        <div id="assignOwnerPanel" class="pointer-events-auto flex h-full w-full max-w-[620px] translate-x-full flex-col border-l border-gray-200 bg-white shadow-2xl transition-transform duration-300 ease-out sm:max-w-[520px]">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5 sm:px-8">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900">Assign Owner</h2>
                    <p id="assignOwnerSelectedCountText" class="text-sm text-gray-500">0 Contacts Selected</p>
                </div>
                <button id="closeAssignOwnerModal" type="button" class="text-2xl leading-none text-gray-500 hover:text-gray-800">&times;</button>
            </div>

            <form id="assignOwnerForm" method="POST" action="{{ route('contacts.assign-owner') }}" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <div id="assignOwnerSelectedContacts"></div>
                <input id="assignOwnerOwnerId" type="hidden" name="assign_owner_id" value="">

                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6 sm:px-8">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Select New Owner</label>
                    <div class="relative mb-3">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400"></i>
                        <input id="assignOwnerSearch" type="text" placeholder="Search Users" class="h-10 w-full rounded-lg border border-gray-200 pl-8 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>

                    <div id="assignOwnerList" class="max-h-[60vh] overflow-y-auto rounded-lg border border-gray-200">
                        @foreach ($owners as $owner)
                            @php
                                $assignOwnerInitials = strtoupper(collect(explode(' ', trim($owner['name'])))
                                    ->filter()
                                    ->map(fn ($segment) => mb_substr($segment, 0, 1))
                                    ->take(2)
                                    ->implode(''));
                            @endphp
                            <button
                                type="button"
                                class="assign-owner-option flex w-full items-center gap-3 border-b border-gray-100 px-3 py-2 text-left text-sm text-gray-700 hover:bg-blue-50"
                                data-owner-id="{{ $owner['id'] }}"
                                data-owner-name="{{ $owner['name'] }}"
                                data-owner-email="{{ $owner['email'] }}"
                            >
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-[10px] font-semibold text-blue-700">
                                    {{ $assignOwnerInitials }}
                                </span>
                                <span>
                                    <span class="block text-sm text-gray-700">{{ $owner['name'] }}</span>
                                    <span class="block text-xs text-gray-500">{{ $owner['email'] }}</span>
                                </span>
                            </button>
                        @endforeach
                    </div>

                    @if ($errors->has('assign_owner_id') || $errors->has('selected_contacts'))
                        <div class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                            {{ $errors->first() }}
                        </div>
                    @endif
                </div>

                <div class="mt-auto flex items-center justify-end gap-3 border-t border-gray-100 bg-white px-6 py-4 sm:px-8">
                    <button id="cancelAssignOwnerModal" type="button" class="h-10 rounded-lg border border-gray-300 px-4 text-sm text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button id="submitAssignOwner" type="submit" disabled class="h-10 rounded-lg bg-blue-600 px-5 text-sm font-medium text-white disabled:cursor-not-allowed disabled:bg-blue-300">
                        Assign Owner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('products.partials.create-field-dropdown', [
    'fieldTypes' => $fieldTypes,
    'dropdownId' => 'createFieldDropdownMenu',
])
@include('products.partials.create-field-modal', [
    'createFieldActionRoute' => route('contacts.custom-fields.store'),
    'lookupModules' => $lookupModules,
])

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('createContactModal');
    const openModalButton = document.getElementById('openCreateContactModal');
    const closeModalButton = document.getElementById('closeCreateContactModal');
    const cancelModalButton = document.getElementById('cancelCreateContactModal');
    const ownerTrigger = document.getElementById('ownerDropdownTrigger');
    const ownerMenu = document.getElementById('ownerDropdownMenu');
    const ownerSearch = document.getElementById('ownerSearch');
    const ownerInput = document.getElementById('owner_id');
    const ownerLabel = document.getElementById('ownerSelectedLabel');
    const ownerOptions = Array.from(document.querySelectorAll('.owner-option'));
    const createdAtLiveValue = document.getElementById('createdAtLiveValue');
    const assignOwnerModal = document.getElementById('assignOwnerModal');
    const openAssignOwnerModalButton = document.getElementById('openAssignOwnerModal');
    const closeAssignOwnerModalButton = document.getElementById('closeAssignOwnerModal');
    const cancelAssignOwnerModalButton = document.getElementById('cancelAssignOwnerModal');
    const assignOwnerOverlay = document.getElementById('assignOwnerOverlay');
    const assignOwnerPanel = document.getElementById('assignOwnerPanel');
    const assignOwnerSearch = document.getElementById('assignOwnerSearch');
    const assignOwnerOptions = Array.from(document.querySelectorAll('.assign-owner-option'));
    const assignOwnerOwnerIdInput = document.getElementById('assignOwnerOwnerId');
    const assignOwnerSelectedContacts = document.getElementById('assignOwnerSelectedContacts');
    const assignOwnerSelectedCountText = document.getElementById('assignOwnerSelectedCountText');
    const submitAssignOwner = document.getElementById('submitAssignOwner');
    const contactPanel = document.getElementById('createContactPanel');
    const contactOverlay = document.getElementById('createContactModalOverlay');
    const openCreateFieldDropdown = document.getElementById('openCreateFieldDropdown');
    const createFieldDropdownMenu = document.getElementById('createFieldDropdownMenu');
    const fieldTypeButtons = Array.from(document.querySelectorAll('.create-field-type-option'));
    const createFieldModal = document.getElementById('createFieldModal');
    const createFieldPanel = document.getElementById('createFieldPanel');
    const createFieldModalOverlay = document.getElementById('createFieldModalOverlay');
    const closeCreateFieldModal = document.getElementById('closeCreateFieldModal');
    const cancelCreateFieldModal = document.getElementById('cancelCreateFieldModal');
    const createFieldTypeInput = document.getElementById('createFieldTypeInput');
    const createFieldTypeLabel = document.getElementById('createFieldTypeLabel');
    const picklistOptionsSection = document.getElementById('picklistOptionsSection');
    const picklistOptionsContainer = document.getElementById('picklistOptionsContainer');
    const addPicklistOption = document.getElementById('addPicklistOption');
    const defaultValueSection = document.getElementById('defaultValueSection');
    const lookupSection = document.getElementById('lookupSection');
    const defaultValueInput = document.getElementById('default_value');
    const oldSelectedContacts = @json(old('selected_contacts', []));
    let createFieldDropdownOpen = false;
    const organizationTypeInputs = Array.from(document.querySelectorAll('input[name="organization_type"]'));
    const ownershipFlagInputs = Array.from(document.querySelectorAll('input[name="ownership_flag"]'));
    const organizationTypeOtherWrap = document.getElementById('organizationTypeOtherWrap');
    const foreignBusinessNatureWrap = document.getElementById('foreignBusinessNatureWrap');
    const conditionalOtherToggles = Array.from(document.querySelectorAll('[data-other-toggle]'));
    const employeeRecords = @json($employeeOptions ?? []);
    const employeePickers = Array.from(document.querySelectorAll('[data-employee-picker]'));

    const selectAll = document.getElementById('selectAll');
    const rowChecks = Array.from(document.querySelectorAll('.row-checkbox'));
    const actionBar = document.getElementById('selectionActionBar');
    const selectedCount = document.getElementById('selectedCount');
    const clearSelection = document.getElementById('clearSelection');
    const deleteSelectedModal = document.getElementById('deleteSelectedModal');
    const deleteSelectedOverlay = document.getElementById('deleteSelectedOverlay');
    const openDeleteSelectedModalButton = document.getElementById('openDeleteSelectedModal');
    const cancelDeleteSelectedModalButton = document.getElementById('cancelDeleteSelectedModal');
    const bulkDeleteSelectedContacts = document.getElementById('bulkDeleteSelectedContacts');
    const bulkDeleteCountText = document.getElementById('bulkDeleteCountText');
    const contactsSearchForm = document.getElementById('contactsSearchForm');
    const contactsSearchInput = document.getElementById('contactsSearchInput');
    let createdAtIntervalId = null;
    let searchDebounceId = null;

    const dateFormatter = new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });

    const timeFormatter = new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
    });

    const formatCreatedAt = (date) => `${dateFormatter.format(date)} • ${timeFormatter.format(date)}`;

    const renderCreatedAtClock = () => {
        if (!createdAtLiveValue) {
            return;
        }
        createdAtLiveValue.textContent = formatCreatedAt(new Date());
    };

    const stopCreatedAtClock = () => {
        if (createdAtIntervalId !== null) {
            window.clearInterval(createdAtIntervalId);
            createdAtIntervalId = null;
        }
    };

    const startCreatedAtClock = () => {
        stopCreatedAtClock();
        renderCreatedAtClock();
        createdAtIntervalId = window.setInterval(renderCreatedAtClock, 1000);
    };

    const openModal = () => {
        if (!modal || !contactPanel) {
            return;
        }
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        startCreatedAtClock();
        requestAnimationFrame(() => {
            contactOverlay?.classList.remove('opacity-0');
            contactPanel.classList.remove('translate-x-full');
        });
    };

    const closeModal = () => {
        if (!modal || !contactPanel) {
            return;
        }
        ownerMenu?.classList.add('hidden');
        contactOverlay?.classList.add('opacity-0');
        contactPanel.classList.add('translate-x-full');
        stopCreatedAtClock();
        document.body.classList.remove('overflow-hidden');
        window.setTimeout(() => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
        }, 300);
    };

    const closeAssignOwnerModal = () => {
        if (!assignOwnerModal || !assignOwnerPanel) {
            return;
        }
        assignOwnerOverlay?.classList.add('opacity-0');
        assignOwnerPanel.classList.add('translate-x-full');
        document.body.classList.remove('overflow-hidden');
        window.setTimeout(() => {
            assignOwnerModal.classList.add('hidden');
            assignOwnerModal.setAttribute('aria-hidden', 'true');
        }, 300);
    };

    const openAssignOwnerModal = () => {
        const selectedContacts = rowChecks.filter((item) => item.checked);
        if (selectedContacts.length === 0) {
            return;
        }

        if (assignOwnerSelectedContacts) {
            assignOwnerSelectedContacts.innerHTML = selectedContacts
                .map((item) => `<input type="hidden" name="selected_contacts[]" value="${item.value}">`)
                .join('');
        }

        if (assignOwnerSelectedCountText) {
            assignOwnerSelectedCountText.textContent = `${selectedContacts.length} ${selectedContacts.length === 1 ? 'Contact' : 'Contacts'} Selected`;
        }

        if (assignOwnerSearch) {
            assignOwnerSearch.value = '';
        }
        if (assignOwnerOwnerIdInput) {
            assignOwnerOwnerIdInput.value = '';
        }
        submitAssignOwner?.setAttribute('disabled', 'disabled');
        assignOwnerOptions.forEach((option) => {
            option.classList.remove('bg-blue-50');
            option.classList.remove('hidden');
        });

        if (!assignOwnerModal || !assignOwnerPanel) {
            return;
        }
        assignOwnerModal.classList.remove('hidden');
        assignOwnerModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        requestAnimationFrame(() => {
            assignOwnerOverlay?.classList.remove('opacity-0');
            assignOwnerPanel.classList.remove('translate-x-full');
        });
    };

    const closeDeleteSelectedModal = () => {
        if (!deleteSelectedModal) {
            return;
        }

        deleteSelectedModal.classList.add('hidden');
        deleteSelectedModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
    };

    const openDeleteSelectedModal = () => {
        const selectedContacts = rowChecks.filter((item) => item.checked);
        if (selectedContacts.length === 0 || !deleteSelectedModal) {
            return;
        }

        if (bulkDeleteSelectedContacts) {
            bulkDeleteSelectedContacts.innerHTML = selectedContacts
                .map((item) => `<input type="hidden" name="selected_contacts[]" value="${item.value}">`)
                .join('');
        }

        if (bulkDeleteCountText) {
            bulkDeleteCountText.textContent = `${selectedContacts.length} ${selectedContacts.length === 1 ? 'contact' : 'contacts'}`;
        }

        deleteSelectedModal.classList.remove('hidden');
        deleteSelectedModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    };

    const submitContactsSearch = () => {
        if (!contactsSearchForm) {
            return;
        }

        contactsSearchForm.submit();
    };

    const openAssignOwnerModalFromIds = (contactIds) => {
        if (!Array.isArray(contactIds) || contactIds.length === 0) {
            return;
        }

        if (assignOwnerSelectedContacts) {
            assignOwnerSelectedContacts.innerHTML = contactIds
                .map((id) => `<input type="hidden" name="selected_contacts[]" value="${id}">`)
                .join('');
        }

        if (assignOwnerSelectedCountText) {
            assignOwnerSelectedCountText.textContent = `${contactIds.length} ${contactIds.length === 1 ? 'Contact' : 'Contacts'} Selected`;
        }

        if (assignOwnerSearch) {
            assignOwnerSearch.value = '';
        }

        assignOwnerOptions.forEach((option) => {
            option.classList.remove('bg-blue-50');
            option.classList.remove('hidden');
        });

        if (assignOwnerOwnerIdInput) {
            assignOwnerOwnerIdInput.value = '';
        }
        submitAssignOwner?.setAttribute('disabled', 'disabled');

        if (!assignOwnerModal || !assignOwnerPanel) {
            return;
        }
        assignOwnerModal.classList.remove('hidden');
        assignOwnerModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        requestAnimationFrame(() => {
            assignOwnerOverlay?.classList.remove('opacity-0');
            assignOwnerPanel.classList.remove('translate-x-full');
        });
    };

    const buildPicklistOptionRow = (value = '') => {
        const row = document.createElement('div');
        row.className = 'flex items-center gap-2';
        row.innerHTML = `
            <input name="options[]" value="${value}" placeholder="Option value" class="h-10 flex-1 rounded-md border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
            <button type="button" class="remove-picklist-option h-8 w-8 rounded-full border border-gray-300 text-gray-500 hover:bg-gray-50">
                <i class="fas fa-minus text-xs"></i>
            </button>
        `;
        return row;
    };

    const ensurePicklistOptionRows = () => {
        if (!picklistOptionsContainer) {
            return;
        }
        if (picklistOptionsContainer.querySelectorAll('input[name="options[]"]').length === 0) {
            picklistOptionsContainer.appendChild(buildPicklistOptionRow(''));
        }
    };

    const applyCreateFieldTypeUI = (type, label) => {
        if (!createFieldTypeInput || !createFieldTypeLabel) {
            return;
        }

        createFieldTypeInput.value = type;
        createFieldTypeLabel.textContent = label;
        picklistOptionsSection?.classList.toggle('hidden', type !== 'picklist');
        lookupSection?.classList.toggle('hidden', type !== 'lookup');
        lookupSection?.classList.toggle('grid', type === 'lookup');
        defaultValueSection?.classList.toggle('hidden', type === 'lookup');
        defaultValueSection?.classList.toggle('grid', type !== 'lookup');

        if (defaultValueInput) {
            defaultValueInput.placeholder = type === 'date' ? 'YYYY-MM-DD' : 'Optional default value';
        }

        if (type === 'picklist') {
            ensurePicklistOptionRows();
        }
    };

    const openCreateFieldModalFn = (type, label) => {
        applyCreateFieldTypeUI(type, label);
        createFieldDropdownMenu?.classList.add('hidden');
        createFieldDropdownOpen = false;
        createFieldModal?.classList.remove('hidden');
        createFieldModal?.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        requestAnimationFrame(() => {
            createFieldModalOverlay?.classList.remove('opacity-0');
            createFieldPanel?.classList.remove('translate-x-full');
        });
    };

    const closeCreateFieldModalFn = () => {
        createFieldModalOverlay?.classList.add('opacity-0');
        createFieldPanel?.classList.add('translate-x-full');
        document.body.classList.remove('overflow-hidden');
        window.setTimeout(() => {
            createFieldModal?.classList.add('hidden');
            createFieldModal?.setAttribute('aria-hidden', 'true');
        }, 300);
    };

    const positionCreateFieldDropdown = () => {
        if (!openCreateFieldDropdown || !createFieldDropdownMenu) {
            return;
        }

        const rect = openCreateFieldDropdown.getBoundingClientRect();
        const dropdownWidth = createFieldDropdownMenu.offsetWidth || 256;
        const viewportPadding = 12;

        let left = rect.left;
        if (left + dropdownWidth > window.innerWidth - viewportPadding) {
            left = rect.right - dropdownWidth;
        }
        if (left < viewportPadding) {
            left = viewportPadding;
        }

        let top = rect.bottom + 8;
        const dropdownHeight = createFieldDropdownMenu.offsetHeight || 320;
        if (top + dropdownHeight > window.innerHeight - viewportPadding) {
            top = Math.max(viewportPadding, rect.top - dropdownHeight - 8);
        }

        createFieldDropdownMenu.style.left = `${left}px`;
        createFieldDropdownMenu.style.top = `${top}px`;
    };

    const openCreateFieldDropdownFn = () => {
        if (!createFieldDropdownMenu) {
            return;
        }
        createFieldDropdownMenu.classList.remove('hidden');
        createFieldDropdownOpen = true;
        positionCreateFieldDropdown();
    };

    const closeCreateFieldDropdownFn = () => {
        createFieldDropdownMenu?.classList.add('hidden');
        createFieldDropdownOpen = false;
    };

    const refreshSelection = () => {
        const count = rowChecks.filter((item) => item.checked).length;
        selectedCount.textContent = count;
        actionBar.classList.toggle('hidden', count === 0);
        if (selectAll) {
            selectAll.checked = rowChecks.length > 0 && count === rowChecks.length;
        }
    };

    const syncOtherFieldVisibility = () => {
        conditionalOtherToggles.forEach((toggle) => {
            const targetId = toggle.dataset.otherToggle;
            if (!targetId) {
                return;
            }

            const target = document.getElementById(targetId);
            if (!target) {
                return;
            }

            const matchingToggles = conditionalOtherToggles.filter((item) => item.dataset.otherToggle === targetId);
            const isVisible = matchingToggles.some((item) => item.checked);
            target.classList.toggle('hidden', !isVisible);
        });
    };

    const syncBusinessConditionalFields = () => {
        const selectedOrganization = organizationTypeInputs.find((input) => input.checked)?.value || '';
        const selectedOwnership = ownershipFlagInputs.find((input) => input.checked)?.value || '';

        organizationTypeOtherWrap?.classList.toggle('hidden', selectedOrganization !== 'Others');
        foreignBusinessNatureWrap?.classList.toggle('hidden', selectedOwnership !== 'Foreign-Owned Business');
    };

    const hideEmployeeSearchResults = (picker) => {
        picker?.querySelector('[data-employee-search-results]')?.classList.add('hidden');
    };

    const syncInternalUseDefaults = () => {
        const referredByInput = document.querySelector('[name="referred_by"]');
        const fallbackName = @js(auth()->user()->name ?? '');

        if (!referredByInput) {
            return;
        }

        if (String(referredByInput.value || '').trim() !== '') {
            return;
        }

        if (String(fallbackName || '').trim() === '') {
            return;
        }

        referredByInput.value = fallbackName;
    };

    const renderEmployeeSearchResults = (picker, keyword = '') => {
        const input = picker?.querySelector('[data-employee-search-input]');
        const results = picker?.querySelector('[data-employee-search-results]');
        if (!input || !results) {
            return;
        }

        const query = keyword.trim().toLowerCase();
        const matches = employeeRecords.filter((record) => {
            if (query === '') {
                return true;
            }

            const blob = [
                record.name,
                record.employee_code,
                record.position,
                record.department,
                record.email,
            ].join(' ').toLowerCase();

            return blob.includes(query);
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
            button.innerHTML = `<div class="text-sm font-medium text-gray-800">${record.name || ''}</div><div class="text-xs text-gray-500">${[record.employee_code, record.position, record.department].filter(Boolean).join(' - ') || (record.email || '')}</div>`;
            button.addEventListener('click', () => {
                input.value = record.name || '';
                hideEmployeeSearchResults(picker);
                syncInternalUseDefaults();
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });

            return button;
        }));

        results.classList.remove('hidden');
    };

    const initEmployeeSearchPickers = () => {
        employeePickers.forEach((picker) => {
            const input = picker.querySelector('[data-employee-search-input]');
            if (!input) {
                return;
            }

            input.addEventListener('focus', () => renderEmployeeSearchResults(picker, input.value));
            input.addEventListener('input', () => renderEmployeeSearchResults(picker, input.value));
            input.addEventListener('change', syncInternalUseDefaults);
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    hideEmployeeSearchResults(picker);
                }
            });
        });
    };

    openModalButton?.addEventListener('click', openModal);
    closeModalButton?.addEventListener('click', closeModal);
    cancelModalButton?.addEventListener('click', closeModal);
    openAssignOwnerModalButton?.addEventListener('click', openAssignOwnerModal);
    openDeleteSelectedModalButton?.addEventListener('click', openDeleteSelectedModal);
    closeAssignOwnerModalButton?.addEventListener('click', closeAssignOwnerModal);
    cancelAssignOwnerModalButton?.addEventListener('click', closeAssignOwnerModal);
    cancelDeleteSelectedModalButton?.addEventListener('click', closeDeleteSelectedModal);

    contactOverlay?.addEventListener('click', closeModal);
    assignOwnerOverlay?.addEventListener('click', closeAssignOwnerModal);
    deleteSelectedOverlay?.addEventListener('click', closeDeleteSelectedModal);

    ownerTrigger?.addEventListener('click', function () {
        ownerMenu.classList.toggle('hidden');
        if (!ownerMenu.classList.contains('hidden')) {
            ownerSearch?.focus();
        }
    });

    ownerSearch?.addEventListener('input', function () {
        const keyword = ownerSearch.value.toLowerCase().trim();
        ownerOptions.forEach((option) => {
            const value = (option.dataset.ownerName || '').toLowerCase();
            option.classList.toggle('hidden', keyword !== '' && !value.includes(keyword));
        });
    });

    ownerOptions.forEach((option) => {
        option.addEventListener('click', function () {
            ownerInput.value = option.dataset.ownerId;
            ownerLabel.textContent = `Owner: ${option.dataset.ownerName}`;
            ownerMenu.classList.add('hidden');
        });
    });

    assignOwnerSearch?.addEventListener('input', function () {
        const keyword = assignOwnerSearch.value.toLowerCase().trim();
        assignOwnerOptions.forEach((option) => {
            const name = (option.dataset.ownerName || '').toLowerCase();
            const email = (option.dataset.ownerEmail || '').toLowerCase();
            option.classList.toggle('hidden', keyword !== '' && !name.includes(keyword) && !email.includes(keyword));
        });
    });

    contactsSearchInput?.addEventListener('input', function () {
        window.clearTimeout(searchDebounceId);
        searchDebounceId = window.setTimeout(submitContactsSearch, 700);
    });

    contactsSearchInput?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            window.clearTimeout(searchDebounceId);
            submitContactsSearch();
        }
    });

    assignOwnerOptions.forEach((option) => {
        option.addEventListener('click', function () {
            if (assignOwnerOwnerIdInput) {
                assignOwnerOwnerIdInput.value = option.dataset.ownerId || '';
            }
            assignOwnerOptions.forEach((item) => item.classList.remove('bg-blue-50'));
            option.classList.add('bg-blue-50');
            submitAssignOwner?.removeAttribute('disabled');
        });
    });

    document.addEventListener('click', function (event) {
        if (ownerMenu && !ownerMenu.classList.contains('hidden')) {
            if (!ownerMenu.contains(event.target) && !ownerTrigger.contains(event.target)) {
                ownerMenu.classList.add('hidden');
            }
        }

        if (createFieldDropdownMenu && createFieldDropdownOpen) {
            const clickedFieldTrigger = openCreateFieldDropdown ? openCreateFieldDropdown.contains(event.target) : false;
            if (!createFieldDropdownMenu.contains(event.target) && !clickedFieldTrigger) {
                closeCreateFieldDropdownFn();
            }
        }

        employeePickers.forEach((picker) => {
            if (!picker.contains(event.target)) {
                hideEmployeeSearchResults(picker);
            }
        });

    });

    openCreateFieldDropdown?.addEventListener('click', function () {
        if (createFieldDropdownOpen) {
            closeCreateFieldDropdownFn();
            return;
        }

        openCreateFieldDropdownFn();
    });

    fieldTypeButtons.forEach((button) => {
        button.addEventListener('click', function () {
            const type = button.dataset.fieldType || 'picklist';
            const label = button.dataset.fieldLabel || 'Picklist';
            openCreateFieldModalFn(type, label);
        });
    });

    closeCreateFieldModal?.addEventListener('click', closeCreateFieldModalFn);
    cancelCreateFieldModal?.addEventListener('click', closeCreateFieldModalFn);
    createFieldModalOverlay?.addEventListener('click', closeCreateFieldModalFn);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
            closeCreateFieldDropdownFn();
            closeCreateFieldModalFn();
            closeAssignOwnerModal();
            closeDeleteSelectedModal();
        }
    });

    window.addEventListener('resize', function () {
        if (createFieldDropdownOpen) {
            positionCreateFieldDropdown();
        }
    });

    document.addEventListener('scroll', function () {
        if (createFieldDropdownOpen) {
            positionCreateFieldDropdown();
        }
    }, true);

    addPicklistOption?.addEventListener('click', function () {
        picklistOptionsContainer?.appendChild(buildPicklistOptionRow(''));
    });

    picklistOptionsContainer?.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-picklist-option');
        if (!button) {
            return;
        }

        button.closest('.flex')?.remove();
        ensurePicklistOptionRows();
    });

    selectAll?.addEventListener('change', function () {
        rowChecks.forEach((check) => { check.checked = selectAll.checked; });
        refreshSelection();
    });

    rowChecks.forEach((check) => check.addEventListener('change', refreshSelection));
    conditionalOtherToggles.forEach((toggle) => toggle.addEventListener('change', syncOtherFieldVisibility));
    organizationTypeInputs.forEach((input) => input.addEventListener('change', syncBusinessConditionalFields));
    ownershipFlagInputs.forEach((input) => input.addEventListener('change', syncBusinessConditionalFields));
    clearSelection?.addEventListener('click', function () {
        rowChecks.forEach((check) => { check.checked = false; });
        if (selectAll) {
            selectAll.checked = false;
        }
        refreshSelection();
    });

    const initialFieldType = createFieldTypeInput ? createFieldTypeInput.value : 'picklist';
    const initialTypeButton = fieldTypeButtons.find((button) => (button.dataset.fieldType || '') === initialFieldType);
    applyCreateFieldTypeUI(initialFieldType, initialTypeButton?.dataset.fieldLabel || 'Picklist');
    syncOtherFieldVisibility();
    syncBusinessConditionalFields();
    initEmployeeSearchPickers();
    syncInternalUseDefaults();
    renderCreatedAtClock();

    @if ($errors->any())
        @if (old('field_type'))
            openCreateFieldModalFn('{{ old('field_type') }}', '{{ $fieldTypes->firstWhere('value', old('field_type'))['label'] ?? 'Picklist' }}');
        @elseif ($errors->has('assign_owner_id') || $errors->has('selected_contacts'))
            openAssignOwnerModalFromIds(oldSelectedContacts);
        @else
            openModal();
        @endif
    @elseif ($shouldOpenCreateModal)
        openModal();
    @endif
});
</script>
@endsection

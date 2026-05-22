@extends('layouts.app')
@section('title', 'Stock Transfer Book – Index')
@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 mt-4" x-data="{ showAddPanel: false }" @keydown.escape.window="showAddPanel = false">

    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-3 px-4 py-4">
            <a href="{{ route('stock-transfer-book') }}" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="text-lg font-semibold">Index</div>

            <div class="flex-1"></div>
            <button type="button" data-open-add-panel @click="showAddPanel = true" class="h-9 px-4 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Add Shareholder
            </button>
        </div>

        <div class="border-t border-gray-100"></div>

        @include('corporate.stock-transfer-book.partials.section-tabs', ['currentStockTransferTab' => 'index'])

        {{-- FILTER SEARCH --}}
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <input type="text" id="index-search" placeholder="Search shareholder..." class="w-full rounded-md border border-gray-300 focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 px-3 py-2 text-sm" />
        </div>

        {{-- INDEX TABLE --}}
        <div class="p-4">
            <div class="overflow-auto">
                <table class="min-w-full" id="index-table">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Index</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Family Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">First Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Middle Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Nationality</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Current Address</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">TIN</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-gray-900" id="index-table-body">
                        @forelse ($ledgers as $index => $ledger)
                            <tr data-search-row class="border-b border-gray-100 hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ route('stock-transfer-book.ledger.show', $ledger) }}'">
                                <td class="px-4 py-3 font-medium">{{ $index + 1 }}</td>
                                <td class="px-4 py-3">{{ $ledger->family_name }}</td>
                                <td class="px-4 py-3">{{ $ledger->first_name }}</td>
                                <td class="px-4 py-3">{{ $ledger->middle_name }}</td>
                                <td class="px-4 py-3">{{ $ledger->nationality }}</td>
                                <td class="px-4 py-3">{{ $ledger->address }}</td>
                                <td class="px-4 py-3">{{ $ledger->tin }}</td>
                            </tr>
                        @empty
                            <tr data-empty-row>
                                <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">No index entries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- ADD INDEX SLIDER --}}
    <div x-cloak>
        <div x-show="showAddPanel" class="fixed inset-0 bg-black/40 z-40" @click="showAddPanel = false"></div>
        <div x-show="showAddPanel" data-add-panel
            class="fixed inset-y-0 right-0 w-full max-w-xl bg-white shadow-2xl z-50 flex flex-col"
            x-transition:enter="transform transition ease-in-out duration-200"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            @click.stop
        >
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="text-lg font-semibold">Add Shareholder</div>
                <div class="flex-1"></div>
                <button class="text-gray-500 hover:text-gray-700" @click="showAddPanel = false">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('stock-transfer-book.ledger.store') }}" enctype="multipart/form-data" class="p-6 overflow-y-auto space-y-4">
                @csrf
                <div class="space-y-4">
                    <div>
                        <input type="text" data-contact-search class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Search contacts...">
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden" data-contact-list-wrap>
                        <div class="px-4 py-3 border-b border-gray-100">
                            <div class="text-sm font-semibold text-gray-900">Existing Contacts</div>
                            <div class="text-xs text-gray-500">Select a contact first before creating the index entry.</div>
                        </div>
                        <div class="h-56 overflow-y-auto overscroll-contain divide-y divide-gray-100" data-contact-list></div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4" data-contact-empty>
                        <div class="text-sm text-gray-500">No contact selected.</div>
                        <a href="{{ route('contacts.index') }}" class="mt-3 inline-flex items-center rounded-lg border border-blue-600 px-3 py-1.5 text-xs font-medium text-blue-600 hover:bg-blue-50">
                            Go to Contacts
                        </a>
                    </div>

                    <div class="hidden rounded-xl border border-gray-200 bg-white p-4" data-contact-card>
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-gray-200"></div>
                            <div>
                                <div class="text-sm font-semibold text-gray-900" data-contact-name></div>
                                <div class="text-xs text-gray-500" data-contact-email></div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="text-xs text-gray-600">Family Name</label>
                            <input type="text" name="family_name" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">First Name</label>
                            <input type="text" name="first_name_display" disabled class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-500">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Middle Name</label>
                            <input type="text" name="middle_name_display" disabled class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-500">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Nationality</label>
                            <input type="text" name="nationality" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Specific Residential Address</label>
                            <input type="text" name="address" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Date Registered</label>
                            <input type="date" name="date_registered" data-default-field="today" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Tax Identification No.</label>
                            <input type="text" name="tin" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Email</label>
                            <input type="email" name="email_display" disabled class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-500">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Phone</label>
                            <input type="text" name="phone" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Number of Shares</label>
                            <input type="number" name="shares" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="1000">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Certificate No.</label>
                            <input type="text" name="certificate_no" data-default-field="stock_number" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="STK-0001">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Status</label>
                            <input type="text" name="status" value="active" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <input type="hidden" name="first_name" data-contact-first>
                    <input type="hidden" name="middle_name" data-contact-middle>
                    <input type="hidden" name="email" data-contact-email-input>
                </div>
                <div class="mt-6 flex items-center gap-2 border-t border-gray-100 px-0 pt-4">
                    <div class="flex-1"></div>
                    <button type="button" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-300" @click="showAddPanel = false">
                        Cancel
                    </button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Add
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
    (function () {
        const searchInput = document.getElementById('index-search');
        const tableBody = document.getElementById('index-table-body');
        if (!searchInput || !tableBody) return;

        const filterRows = () => {
            const query = searchInput.value.trim().toLowerCase();
            const rows = Array.from(tableBody.querySelectorAll('[data-search-row]'));
            const emptyRow = tableBody.querySelector('[data-empty-row]');
            let visibleCount = 0;

            rows.forEach((row) => {
                const matches = query === '' || row.textContent.toLowerCase().includes(query);
                row.style.display = matches ? '' : 'none';
                if (matches) {
                    visibleCount += 1;
                }
            });

            if (emptyRow) {
                emptyRow.style.display = rows.length === 0 || visibleCount === 0 ? '' : 'none';
            }
        };

        searchInput.addEventListener('input', filterRows);
        filterRows();
    })();

    (function () {
        const contacts = @json($contacts ?? []);
        const container = document.currentScript.closest('body');
        const searchInput = container.querySelector('[data-contact-search]');
        const emptyState = container.querySelector('[data-contact-empty]');
        const card = container.querySelector('[data-contact-card]');
        const cardName = container.querySelector('[data-contact-name]');
        const cardEmail = container.querySelector('[data-contact-email]');
        const firstInput = container.querySelector('[data-contact-first]');
        const middleInput = container.querySelector('[data-contact-middle]');
        const emailInput = container.querySelector('[data-contact-email-input]');
        const familyNameInput = container.querySelector('[name="family_name"]');
        const firstNameDisplay = container.querySelector('[name="first_name_display"]');
        const middleNameDisplay = container.querySelector('[name="middle_name_display"]');
        const emailDisplay = container.querySelector('[name="email_display"]');
        const nationalityInput = container.querySelector('[name="nationality"]');
        const addressInput = container.querySelector('[name="address"]');
        const tinInput = container.querySelector('[name="tin"]');
        const phoneInput = container.querySelector('[name="phone"]');
        const addPanel = container.querySelector('[data-add-panel]');
        const contactList = container.querySelector('[data-contact-list]');

        const splitName = (name) => {
            if (!name) return { first: '', middle: '', last: '' };
            if (name.includes(',')) {
                const [last, rest] = name.split(',').map((part) => part.trim());
                const parts = rest.split(' ').filter(Boolean);
                return { first: parts[0] || '', middle: parts.slice(1).join(' '), last: last || '' };
            }
            const parts = name.split(' ').filter(Boolean);
            return {
                first: parts[0] || '',
                middle: parts.slice(1, -1).join(' '),
                last: parts.length > 1 ? parts[parts.length - 1] : '',
            };
        };

        const selectContact = (contact) => {
            const nameParts = {
                first: contact.first_name || '',
                middle: contact.middle_name || '',
                last: contact.last_name || '',
            };

            if (!nameParts.first && !nameParts.last) {
                Object.assign(nameParts, splitName(contact.name));
            }

            firstInput.value = nameParts.first;
            middleInput.value = nameParts.middle;
            emailInput.value = contact.email || '';
            if (familyNameInput) {
                familyNameInput.value = nameParts.last;
            }
            if (firstNameDisplay) {
                firstNameDisplay.value = nameParts.first;
            }
            if (middleNameDisplay) {
                middleNameDisplay.value = nameParts.middle;
            }
            if (emailDisplay) {
                emailDisplay.value = contact.email || '';
            }
            if (nationalityInput) {
                nationalityInput.value = contact.nationality || '';
            }
            if (addressInput) {
                addressInput.value = contact.address || '';
            }
            if (tinInput) {
                tinInput.value = contact.tax_id || '';
            }
            if (phoneInput) {
                phoneInput.value = contact.phone || '';
            }
            cardName.textContent = contact.name;
            cardEmail.textContent = [contact.email, contact.phone].filter(Boolean).join(' | ');
            card.classList.remove('hidden');
            emptyState.classList.add('hidden');
        };

        const renderList = (items) => {
            if (!contactList) return;
            contactList.innerHTML = '';
            if (!items.length) {
                contactList.innerHTML = '<div class="px-4 py-4 text-sm text-gray-500">No matching contacts found.</div>';
                return;
            }
            items.forEach((contact) => {
                const row = document.createElement('button');
                row.type = 'button';
                row.className = 'w-full px-4 py-3 text-left hover:bg-gray-50';
                row.innerHTML = `
                    <div class="text-sm font-medium text-gray-900">${contact.name}</div>
                    <div class="text-xs text-gray-500">${[contact.email, contact.phone, contact.tax_id].filter(Boolean).join(' | ')}</div>
                `;
                row.addEventListener('click', () => {
                    selectContact(contact);
                });
                contactList.appendChild(row);
            });
        };

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                const term = searchInput.value.toLowerCase().trim();
                if (!term) {
                    renderList(contacts);
                    return;
                }
                const matches = contacts.filter((contact) => [
                    contact.name,
                    contact.email,
                    contact.phone,
                    contact.tax_id,
                ].some((value) => String(value || '').toLowerCase().includes(term)));
                renderList(matches);
            });
        }

        renderList(contacts);

        const defaultsButton = container.querySelector('[data-open-add-panel]');
        if (defaultsButton && addPanel) {
            defaultsButton.addEventListener('click', async () => {
                try {
                    const response = await fetch("{{ route('stock-transfer-book.defaults') }}");
                    if (!response.ok) return;
                    const defaults = await response.json();
                    addPanel.querySelectorAll('[data-default-field]').forEach((field) => {
                        const key = field.getAttribute('data-default-field');
                        if (!key) return;
                        if (key in defaults) {
                            field.value = defaults[key];
                        }
                    });
                } catch (error) {
                    // ignore defaults loading errors
                }
            });
        }
    })();
</script>

@endsection

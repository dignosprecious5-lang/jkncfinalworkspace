@php
    $defaultReferredBy = old('referred_by', auth()->user()->name ?? '');
    $defaultPresident = old('president_use_only_name', 'John Kelly Abalde');
    $roleContactOptionsJson = collect($roleContactOptions ?? [])->values()->all();
    $roleContactSearchUrl = route('company.contacts.search');
    $addContactUrl = route('contacts.index');
@endphp

<x-slide-over id="addCompanyModal" width="sm:max-w-[720px] lg:max-w-[820px]">
    <div class="border-b border-gray-100 px-6 py-5 sm:px-8">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">Create Company</h2>
                <p class="mt-1 text-sm text-gray-500">Capture the Business Information Form once and reuse it across Company KYC.</p>
            </div>
            <button type="button" data-close-company-modal class="text-2xl text-gray-500 hover:text-gray-800">&times;</button>
        </div>
    </div>

    <form method="POST" action="{{ route('company.store') }}" class="flex min-h-0 flex-1 flex-col" x-data="companyBifForm()" x-init="init()" @submit="clearCompanyDraft()">
        @csrf
        <input type="hidden" name="client_type" value="new_client">

        <div class="min-h-0 flex-1 space-y-6 overflow-y-auto px-6 py-6 sm:px-8">
            <div class="flex flex-col gap-4 border-b border-gray-100 pb-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Business Information Form</p>
                    <p class="text-xs text-gray-400">This becomes the default company onboarding record and auto-populates KYC.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 sm:items-end">
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">BIF No.</label>
                        <input type="text" name="bif_no" value="{{ old('bif_no') }}" placeholder="Auto-generated after save" class="h-10 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 text-sm text-gray-500 outline-none" readonly>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Date</label>
                        <input type="date" name="bif_date" value="{{ old('bif_date', now()->format('Y-m-d')) }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>
                </div>
            </div>

            <section class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
                <h3 class="text-base font-semibold text-gray-900">Primary Contact Link</h3>
                <p class="mb-4 text-xs text-gray-500">Select the approved contact record to link this company profile.</p>
                @if (!($hasApprovedCompanyCreateContacts ?? false))
                    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        No approved contacts are available yet. Approve a contact CIF first before creating a company.
                    </div>
                @endif
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Contact <span class="text-red-500">*</span></label>
                    <select name="contact_id" x-on:change="hydrateFromContact($event)" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:bg-gray-100 disabled:text-gray-500" required @disabled(!($hasApprovedCompanyCreateContacts ?? false))>
                        <option value="">Select contact</option>
                        @foreach (($companyCreateContacts ?? collect()) as $contact)
                            @php
                                $contactName = trim(collect([$contact->first_name, $contact->last_name])->filter()->implode(' '));
                                $contactCompany = $contact->company_name ? ' • '.$contact->company_name : '';
                            @endphp
                            <option
                                value="{{ $contact->id }}"
                                data-company-name="{{ ($contact->company_autofill['business_name'] ?? null) ?: $contact->company_name }}"
                                data-email="{{ ($contact->company_autofill['authorized_contact_person_email'] ?? null) ?: $contact->email }}"
                                data-phone="{{ ($contact->company_autofill['authorized_contact_person_phone'] ?? null) ?: $contact->phone }}"
                                data-business-phone="{{ ($contact->company_autofill['business_phone'] ?? null) ?: $contact->phone }}"
                                data-mobile="{{ ($contact->company_autofill['mobile_no'] ?? null) ?: $contact->phone }}"
                                data-address="{{ ($contact->company_autofill['business_address'] ?? null) ?: $contact->contact_address }}"
                                data-tin="{{ ($contact->company_autofill['tin_no'] ?? null) ?: $contact->tin }}"
                                data-zip-code="{{ $contact->company_autofill['zip_code'] ?? '' }}"
                                data-nationality-status="{{ $contact->company_autofill['nationality_status'] ?? '' }}"
                                data-business-organization="{{ $contact->company_autofill['business_organization'] ?? '' }}"
                                data-business-organization-other="{{ $contact->company_autofill['business_organization_other'] ?? '' }}"
                                data-office-type="{{ $contact->company_autofill['office_type'] ?? '' }}"
                                data-office-type-other="{{ $contact->company_autofill['office_type_other'] ?? '' }}"
                                data-alternative-business-name="{{ $contact->company_autofill['alternative_business_name'] ?? '' }}"
                                data-contact-name="{{ ($contact->company_autofill['authorized_contact_person_name'] ?? null) ?: ($contact->contact_full_name ?: trim(collect([$contact->first_name, $contact->last_name])->filter()->implode(' '))) }}"
                                data-contact-position="{{ ($contact->company_autofill['authorized_contact_person_position'] ?? null) ?: $contact->position }}"
                                @selected((string) old('contact_id') === (string) $contact->id)
                            >
                                {{ $contactName !== '' ? $contactName : 'Contact #'.$contact->id }}{{ $contactCompany }} • CIF: APPROVED
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-500">Only contacts with approved CIF can be linked to a new company.</p>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
                <h3 class="text-base font-semibold text-gray-900">Business Information</h3>
                <p class="mb-4 text-xs text-gray-500">Store the primary business identity, organization, and registered office details.</p>
                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Business Organization</label>
                        <div class="grid gap-2 sm:grid-cols-3">
                            @foreach (['sole_proprietorship' => 'Sole Proprietorship', 'partnership' => 'Partnership', 'corporation' => 'Corporation', 'cooperative' => 'Cooperative', 'ngo' => 'NGO', 'other' => 'Other'] as $value => $label)
                                <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 hover:border-blue-200 hover:bg-blue-50/40">
                                    <input type="radio" name="business_organization" value="{{ $value }}" x-model="businessOrganization" @checked(old('business_organization') === $value) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Nationality</label>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach (['filipino' => 'Filipino', 'foreign' => 'Foreign'] as $value => $label)
                                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 hover:border-blue-200 hover:bg-blue-50/40">
                                        <input type="radio" name="nationality_status" value="{{ $value }}" @checked(old('nationality_status', 'filipino') === $value) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Type of Office</label>
                            <select name="office_type" x-model="officeType" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <option value="">Select office type</option>
                                <option value="head_office" @selected(old('office_type') === 'head_office')>Head Office</option>
                                <option value="branch" @selected(old('office_type') === 'branch')>Branch</option>
                                <option value="regional_headquarter" @selected(old('office_type') === 'regional_headquarter')>Regional Headquarter</option>
                                <option value="other" @selected(old('office_type') === 'other')>Other</option>
                            </select>
                        </div>
                        <div x-show="businessOrganization === 'other'" x-cloak>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Other Organization</label>
                            <input type="text" name="business_organization_other" value="{{ old('business_organization_other') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        </div>
                        <div x-show="officeType === 'other'" x-cloak>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Other Office Type</label>
                            <input type="text" name="office_type_other" value="{{ old('office_type_other') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-gray-700">Business Name <span class="text-red-500">*</span></label>
                            <input type="text" name="business_name" value="{{ old('business_name') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" required>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Alternative / Business Name / Style</label>
                            <input type="text" name="alternative_business_name" value="{{ old('alternative_business_name') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">ZIP Code</label>
                            <input type="text" name="zip_code" value="{{ old('zip_code') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-gray-700">Business Address</label>
                            <textarea name="business_address" rows="3" placeholder="House/Unit No., Street, Barangay, City/Town, Province" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('business_address') }}</textarea>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
                <h3 class="text-base font-semibold text-gray-900">Contact & Registration</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="mb-2 block text-sm font-medium text-gray-700">Business Phone</label><input type="text" name="business_phone" value="{{ old('business_phone') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                    <div><label class="mb-2 block text-sm font-medium text-gray-700">Mobile No.</label><input type="text" name="mobile_no" value="{{ old('mobile_no') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                    <div><label class="mb-2 block text-sm font-medium text-gray-700">TIN No.</label><input type="text" name="tin_no" value="{{ old('tin_no') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                    <div><label class="mb-2 block text-sm font-medium text-gray-700">Place of Incorporation</label><input type="text" name="place_of_incorporation" value="{{ old('place_of_incorporation') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                    <div><label class="mb-2 block text-sm font-medium text-gray-700">Date of Incorporation</label><input type="date" name="date_of_incorporation" value="{{ old('date_of_incorporation') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
                <h3 class="text-base font-semibold text-gray-900">Industry / Nature of Business</h3>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach (['services' => 'Services', 'export_import' => 'Export/Import', 'education' => 'Education', 'financial_services' => 'Financial Services', 'transportation' => 'Transportation', 'distribution' => 'Distribution', 'manufacturing' => 'Manufacturing', 'government' => 'Government', 'wholesale_retail_trade' => 'Wholesale/Retail Trade', 'other' => 'Other'] as $value => $label)
                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                            <input type="checkbox" name="industry_types[]" value="{{ $value }}" @checked(in_array($value, old('industry_types', []), true)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="mt-4">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Other Industry</label>
                    <input type="text" name="industry_other_text" value="{{ old('industry_other_text') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
                <h3 class="text-base font-semibold text-gray-900">Capital & Employees</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Authorized Capital</label>
                        <div class="space-y-2">
                            @foreach (['micro' => 'Micro (P3M below)', 'small' => 'Small (P3M-P15M)', 'medium' => 'Medium (P15M-P100M)', 'large' => 'Large (P100M above)'] as $value => $label)
                                <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                    <input type="radio" name="capital_category" value="{{ $value }}" @checked(old('capital_category') === $value) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="mb-2 block text-sm font-medium text-gray-700">Male</label><input type="number" min="0" name="employee_male" x-model.number="employees.male" @input="syncEmployeeTotal()" value="{{ old('employee_male') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                        <div><label class="mb-2 block text-sm font-medium text-gray-700">Female</label><input type="number" min="0" name="employee_female" x-model.number="employees.female" @input="syncEmployeeTotal()" value="{{ old('employee_female') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                        <div><label class="mb-2 block text-sm font-medium text-gray-700">PWD</label><input type="number" min="0" name="employee_pwd" x-model.number="employees.pwd" @input="syncEmployeeTotal()" value="{{ old('employee_pwd') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                        <div><label class="mb-2 block text-sm font-medium text-gray-700">Total</label><input type="number" min="0" name="employee_total" x-model.number="employees.total" class="h-10 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 text-sm text-gray-700 outline-none"></div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
                <h3 class="text-base font-semibold text-gray-900">Source of Funds</h3>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach (['revenue_income' => 'Revenue/Income', 'investments' => 'Investments', 'remittance' => 'Remittance', 'fees' => 'Fees', 'other' => 'Other'] as $value => $label)
                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                            <input type="checkbox" name="source_of_funds[]" value="{{ $value }}" @checked(in_array($value, old('source_of_funds', []), true)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="mt-4">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Other Source of Funds</label>
                    <input type="text" name="source_other_text" value="{{ old('source_other_text') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
                <h3 class="text-base font-semibold text-gray-900">Key Officers</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Name of President</label>
                        <div class="relative mb-2">
                            <input type="text" name="president_name" x-model="keyOfficerSelections.president.full_name" @focus="openRoleSearch(keyOfficerSelections.president, 'president')" @input.debounce.250ms="searchRoleContacts(keyOfficerSelections.president, 'president')" @keydown.escape="closeRoleSearch(keyOfficerSelections.president)" placeholder="Search Contact" autocomplete="off" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            <div x-show="keyOfficerSelections.president.search_open" x-cloak @click.outside="closeRoleSearch(keyOfficerSelections.president)" class="absolute z-40 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-xl">
                                <template x-if="roleSearchResults(keyOfficerSelections.president).length > 0">
                                    <div>
                                        <template x-for="contact in roleSearchResults(keyOfficerSelections.president)" :key="'president-result-'+contact.id">
                                            <button type="button" @click="selectKeyOfficer('president_name', keyOfficerSelections.president, contact)" class="block w-full border-b border-gray-100 px-3 py-2 text-left last:border-b-0 hover:bg-blue-50">
                                                <span class="block text-sm font-medium text-gray-800" x-text="contact.label"></span>
                                                <span class="block text-xs text-gray-500" x-text="contactOptionMeta(contact)"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                                <div x-show="keyOfficerSelections.president.search_loading" class="px-3 py-2 text-sm text-gray-500">Searching...</div>
                                <div x-show="!keyOfficerSelections.president.search_loading && roleSearchResults(keyOfficerSelections.president).length === 0" class="px-3 py-3">
                                    <p class="text-sm text-gray-500">No matching contact found.</p>
                                    <button type="button" @click="goToContactCreate(keyOfficerSelections.president)" class="mt-2 inline-flex h-9 items-center rounded-lg bg-blue-600 px-3 text-sm font-medium text-white hover:bg-blue-700">+ Add Contact</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Name of Treasurer</label>
                        <div class="relative mb-2">
                            <input type="text" name="treasurer_name" x-model="keyOfficerSelections.treasurer.full_name" @focus="openRoleSearch(keyOfficerSelections.treasurer, 'treasurer')" @input.debounce.250ms="searchRoleContacts(keyOfficerSelections.treasurer, 'treasurer')" @keydown.escape="closeRoleSearch(keyOfficerSelections.treasurer)" placeholder="Search Contact" autocomplete="off" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            <div x-show="keyOfficerSelections.treasurer.search_open" x-cloak @click.outside="closeRoleSearch(keyOfficerSelections.treasurer)" class="absolute z-40 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-xl">
                                <template x-if="roleSearchResults(keyOfficerSelections.treasurer).length > 0">
                                    <div>
                                        <template x-for="contact in roleSearchResults(keyOfficerSelections.treasurer)" :key="'treasurer-result-'+contact.id">
                                            <button type="button" @click="selectKeyOfficer('treasurer_name', keyOfficerSelections.treasurer, contact)" class="block w-full border-b border-gray-100 px-3 py-2 text-left last:border-b-0 hover:bg-blue-50">
                                                <span class="block text-sm font-medium text-gray-800" x-text="contact.label"></span>
                                                <span class="block text-xs text-gray-500" x-text="contactOptionMeta(contact)"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                                <div x-show="keyOfficerSelections.treasurer.search_loading" class="px-3 py-2 text-sm text-gray-500">Searching...</div>
                                <div x-show="!keyOfficerSelections.treasurer.search_loading && roleSearchResults(keyOfficerSelections.treasurer).length === 0" class="px-3 py-3">
                                    <p class="text-sm text-gray-500">No matching contact found.</p>
                                    <button type="button" @click="goToContactCreate(keyOfficerSelections.treasurer)" class="mt-2 inline-flex h-9 items-center rounded-lg bg-blue-600 px-3 text-sm font-medium text-white hover:bg-blue-700">+ Add Contact</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Authorized Signatories</h3>
                        <p class="text-xs text-gray-500">Add one or more signatories allowed to represent the business.</p>
                    </div>
                    <button type="button" @click="addSignatory()" class="h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 hover:bg-gray-50">+ Add Row</button>
                </div>
                <div class="space-y-4">
                    <template x-for="(row, index) in signatories" :key="'signatory-'+index">
                        <div class="rounded-xl border border-gray-200 bg-white p-3">
                            <div class="mb-3 flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-800">Signatory <span x-text="index + 1"></span></p>
                                <button type="button" @click="removeSignatory(index)" class="text-xs text-red-600 hover:underline" x-show="signatories.length > 1">Remove</button>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="relative sm:col-span-2">
                                    <input type="hidden" :name="`authorized_signatories[${index}][contact_id]`" x-model="row.contact_id">
                                    <input type="text" :name="`authorized_signatories[${index}][full_name]`" x-model="row.full_name" @focus="openRoleSearch(row, 'signatory')" @input.debounce.250ms="searchRoleContacts(row, 'signatory')" @keydown.escape="closeRoleSearch(row)" placeholder="Search Contact" autocomplete="off" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                    <div x-show="row.search_open" x-cloak @click.outside="closeRoleSearch(row)" class="absolute z-40 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-xl">
                                        <template x-if="roleSearchResults(row).length > 0">
                                            <div>
                                                <template x-for="contact in roleSearchResults(row)" :key="'signatory-result-'+index+'-'+contact.id">
                                                    <button type="button" @click="selectRoleContact(row, contact)" class="block w-full border-b border-gray-100 px-3 py-2 text-left last:border-b-0 hover:bg-blue-50">
                                                        <span class="block text-sm font-medium text-gray-800" x-text="contact.label"></span>
                                                        <span class="block text-xs text-gray-500" x-text="contactOptionMeta(contact)"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </template>
                                        <div x-show="row.search_loading" class="px-3 py-2 text-sm text-gray-500">Searching...</div>
                                        <div x-show="!row.search_loading && roleSearchResults(row).length === 0" class="px-3 py-3">
                                            <p class="text-sm text-gray-500">No matching contact found.</p>
                                            <button type="button" @click="goToContactCreate(row)" class="mt-2 inline-flex h-9 items-center rounded-lg bg-blue-600 px-3 text-sm font-medium text-white hover:bg-blue-700">+ Add Contact</button>
                                        </div>
                                    </div>
                                </div>
                                <input :name="`authorized_signatories[${index}][position]`" x-model="row.position" placeholder="Position" class="h-10 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <input :name="`authorized_signatories[${index}][nationality]`" x-model="row.nationality" placeholder="Nationality" class="h-10 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <input :name="`authorized_signatories[${index}][date_of_birth]`" x-model="row.date_of_birth" type="date" class="h-10 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <input :name="`authorized_signatories[${index}][tin]`" x-model="row.tin" placeholder="TIN" class="h-10 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <input :name="`authorized_signatories[${index}][address]`" x-model="row.address" placeholder="Address" class="h-10 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 sm:col-span-2">
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Ultimate Beneficial Owners (UBO)</h3>
                        <p class="text-xs text-gray-500">Record the beneficial owners with significant control or ownership.</p>
                    </div>
                    <button type="button" @click="addUbo()" class="h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 hover:bg-gray-50">+ Add Row</button>
                </div>
                <div class="space-y-4">
                    <template x-for="(row, index) in ubos" :key="'ubo-'+index">
                        <div class="rounded-xl border border-gray-200 bg-white p-3">
                            <div class="mb-3 flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-800">UBO <span x-text="index + 1"></span></p>
                                <button type="button" @click="removeUbo(index)" class="text-xs text-red-600 hover:underline" x-show="ubos.length > 1">Remove</button>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="relative sm:col-span-2">
                                    <input type="hidden" :name="`ubos[${index}][contact_id]`" x-model="row.contact_id">
                                    <input type="text" :name="`ubos[${index}][full_name]`" x-model="row.full_name" @focus="openRoleSearch(row, 'ubo')" @input.debounce.250ms="searchRoleContacts(row, 'ubo')" @keydown.escape="closeRoleSearch(row)" placeholder="Search Contact" autocomplete="off" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                    <div x-show="row.search_open" x-cloak @click.outside="closeRoleSearch(row)" class="absolute z-40 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-xl">
                                        <template x-if="roleSearchResults(row).length > 0">
                                            <div>
                                                <template x-for="contact in roleSearchResults(row)" :key="'ubo-result-'+index+'-'+contact.id">
                                                    <button type="button" @click="selectRoleContact(row, contact)" class="block w-full border-b border-gray-100 px-3 py-2 text-left last:border-b-0 hover:bg-blue-50">
                                                        <span class="block text-sm font-medium text-gray-800" x-text="contact.label"></span>
                                                        <span class="block text-xs text-gray-500" x-text="contactOptionMeta(contact)"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </template>
                                        <div x-show="row.search_loading" class="px-3 py-2 text-sm text-gray-500">Searching...</div>
                                        <div x-show="!row.search_loading && roleSearchResults(row).length === 0" class="px-3 py-3">
                                            <p class="text-sm text-gray-500">No matching contact found.</p>
                                            <button type="button" @click="goToContactCreate(row)" class="mt-2 inline-flex h-9 items-center rounded-lg bg-blue-600 px-3 text-sm font-medium text-white hover:bg-blue-700">+ Add Contact</button>
                                        </div>
                                    </div>
                                </div>
                                <input :name="`ubos[${index}][position]`" x-model="row.position" placeholder="Position" class="h-10 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <input :name="`ubos[${index}][nationality]`" x-model="row.nationality" placeholder="Nationality" class="h-10 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <input :name="`ubos[${index}][date_of_birth]`" x-model="row.date_of_birth" type="date" class="h-10 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <input :name="`ubos[${index}][tin]`" x-model="row.tin" placeholder="TIN" class="h-10 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <input :name="`ubos[${index}][address]`" x-model="row.address" placeholder="Address" class="h-10 rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 sm:col-span-2">
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
                <h3 class="text-base font-semibold text-gray-900">Authorized Contact Person</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="mb-2 block text-sm font-medium text-gray-700">Name</label><input type="text" name="authorized_contact_person_name" value="{{ old('authorized_contact_person_name') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                    <div><label class="mb-2 block text-sm font-medium text-gray-700">Position</label><input type="text" name="authorized_contact_person_position" value="{{ old('authorized_contact_person_position') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                    <div><label class="mb-2 block text-sm font-medium text-gray-700">Email</label><input type="email" name="authorized_contact_person_email" value="{{ old('authorized_contact_person_email') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                    <div><label class="mb-2 block text-sm font-medium text-gray-700">Phone / Mobile</label><input type="text" name="authorized_contact_person_phone" value="{{ old('authorized_contact_person_phone') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
                <h3 class="text-base font-semibold text-gray-900">Acknowledgment</h3>
                <p class="mb-4 text-xs text-gray-500">By submitting this form, the company confirms that all business information provided is true and may be used for onboarding, compliance, and KYC review.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="mb-2 block text-sm font-medium text-gray-700">Signature over Printed Name</label><input type="text" name="signature_printed_name" value="{{ old('signature_printed_name') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                    <div><label class="mb-2 block text-sm font-medium text-gray-700">Position</label><input type="text" name="signature_position" value="{{ old('signature_position') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                    <div><label class="mb-2 block text-sm font-medium text-gray-700">Review Signature over Printed Name</label><input type="text" name="review_signature_printed_name" value="{{ old('review_signature_printed_name') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                    <div><label class="mb-2 block text-sm font-medium text-gray-700">Review Position</label><input type="text" name="review_signature_position" value="{{ old('review_signature_position') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
                <h3 class="text-base font-semibold text-gray-900">Business Onboarding Requirements</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-700" x-show="businessOrganization === 'sole_proprietorship' || !businessOrganization" x-cloak>
                        <p class="mb-2 font-semibold text-gray-900">Sole / Individual</p>
                        <ul class="list-disc space-y-1 pl-5">
                            <li>DTI Certificate of Registration (if Sole Prop)</li>
                            <li>BIR Certificate of Registration (COR)</li>
                            <li>Business Permit / Mayor's Permit</li>
                            <li>Proof of Billing (Residential)</li>
                            <li>Proof of Billing (Business Address if different)</li>
                            <li>Special Power of Attorney (if representative)</li>
                            <li>Representative's 2 Valid IDs (if applicable)</li>
                        </ul>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-700" x-show="['partnership', 'corporation', 'cooperative', 'ngo', 'other'].includes(businessOrganization) || !businessOrganization" x-cloak>
                        <p class="mb-2 font-semibold text-gray-900">Juridical Entity</p>
                        <ul class="list-disc space-y-1 pl-5">
                            <li>SEC / CDA Certificate of Registration</li>
                            <li>BIR Certificate of Registration (COR)</li>
                            <li>Business Permit / Mayor's Permit</li>
                            <li>Articles of Incorporation / Partnership</li>
                            <li>By-Laws</li>
                            <li>Latest General Information Sheet (GIS)</li>
                            <li>Appointment of Officers (for OPC, if applicable)</li>
                            <li>Secretary Certificate OR Board Resolution</li>
                            <li>Ultimate Beneficial Owner (UBO) Declaration</li>
                            <li>Proof of Billing (Company Address)</li>
                            <li>Proof of Billing (Authorized Representative, if applicable)</li>
                        </ul>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
                <h3 class="text-base font-semibold text-gray-900">JK&C Internal Use</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="relative" data-employee-picker>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Referred By</label>
                        <input type="text" name="referred_by" value="{{ $defaultReferredBy }}" autocomplete="off" data-employee-search-input class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        <div class="absolute z-20 mt-1 hidden w-full rounded-lg border border-gray-200 bg-white shadow-lg" data-employee-search-results></div>
                        <p class="mt-1 text-xs text-gray-500">Defaults to the company creator. You can still change it manually.</p>
                    </div>
                    <div class="relative" data-employee-picker>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Consultant Lead</label>
                        <input type="text" name="consultant_lead" value="{{ old('consultant_lead') }}" autocomplete="off" data-employee-search-input class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        <div class="absolute z-20 mt-1 hidden w-full rounded-lg border border-gray-200 bg-white shadow-lg" data-employee-search-results></div>
                        <p class="mt-1 text-xs text-gray-500">Search existing employees from Human Capital. You can still type manually.</p>
                    </div>
                    <div class="relative" data-employee-picker>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Lead Associate</label>
                        <input type="text" name="lead_associate" value="{{ old('lead_associate') }}" autocomplete="off" data-employee-search-input class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        <div class="absolute z-20 mt-1 hidden w-full rounded-lg border border-gray-200 bg-white shadow-lg" data-employee-search-results></div>
                        <p class="mt-1 text-xs text-gray-500">Search existing employees from Human Capital. You can still type manually.</p>
                    </div>
                    <div class="relative" data-employee-picker>
                        <label class="mb-2 block text-sm font-medium text-gray-700">President</label>
                        <input type="text" name="president_use_only_name" value="{{ $defaultPresident }}" autocomplete="off" data-employee-search-input class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        <div class="absolute z-20 mt-1 hidden w-full rounded-lg border border-gray-200 bg-white shadow-lg" data-employee-search-results></div>
                        <p class="mt-1 text-xs text-gray-500">Defaults to John Kelly Abalde.</p>
                    </div>
                </div>
            </section>

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $errors->first() }}</div>
            @endif
        </div>

        <div class="mt-auto border-t border-gray-100 bg-white px-6 py-4 sm:px-8">
            <div class="flex items-center justify-end gap-3">
                <button type="button" data-close-company-modal class="h-10 rounded-lg border border-gray-300 px-4 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="h-10 rounded-lg bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60" @disabled(!($hasApprovedCompanyCreateContacts ?? false))>Save Company</button>
            </div>
        </div>
    </form>
</x-slide-over>

<script>
function companyBifForm() {
    const employeeRecords = @json($employeeOptions ?? []);
    let roleContactRecords = @json($roleContactOptionsJson);
    const roleContactSearchUrl = @js($roleContactSearchUrl);
    const addContactUrl = @js($addContactUrl);
    const draftStorageKey = 'jknc.company.createCompanyDraft';

    return {
        businessOrganization: @js(old('business_organization', '')),
        officeType: @js(old('office_type', '')),
        keyOfficerSelections: {
            president: null,
            treasurer: null,
        },
        employees: {
            male: Number(@js(old('employee_male', 0))) || 0,
            female: Number(@js(old('employee_female', 0))) || 0,
            pwd: Number(@js(old('employee_pwd', 0))) || 0,
            total: Number(@js(old('employee_total', 0))) || 0,
        },
        signatories: @js(old('authorized_signatories', [['contact_id' => '', 'full_name' => '', 'address' => '', 'nationality' => '', 'date_of_birth' => '', 'tin' => '', 'position' => '', 'email' => '', 'phone' => '']])),
        ubos: @js(old('ubos', [['contact_id' => '', 'full_name' => '', 'address' => '', 'nationality' => '', 'date_of_birth' => '', 'tin' => '', 'position' => '', 'email' => '', 'phone' => '']])),
        init() {
            this.syncEmployeeTotal();
            this.keyOfficerSelections.president = this.normalizeRoleRow({ full_name: @js(old('president_name', '')) });
            this.keyOfficerSelections.treasurer = this.normalizeRoleRow({ full_name: @js(old('treasurer_name', '')) });
            if (!Array.isArray(this.signatories) || this.signatories.length === 0) this.signatories = [this.emptyRow()];
            if (!Array.isArray(this.ubos) || this.ubos.length === 0) this.ubos = [this.emptyRow()];
            this.signatories = this.signatories.map((row) => this.normalizeRoleRow(row));
            this.ubos = this.ubos.map((row) => this.normalizeRoleRow(row));
            this.restoreDraftIfRequested();
            this.initEmployeeSearchPickers();
            this.syncInternalUseDefaults();
            const selectedContact = document.querySelector('select[name="contact_id"]');
            if (selectedContact && selectedContact.value) {
                this.hydrateFromContact({ target: selectedContact });
            }
        },
        emptyRow() {
            return this.normalizeRoleRow({});
        },
        normalizeRoleRow(row = {}) {
            const normalized = {
                contact_id: '',
                contact_search: '',
                search_open: false,
                search_loading: false,
                search_searched: false,
                search_results: [],
                full_name: '',
                address: '',
                nationality: '',
                date_of_birth: '',
                tin: '',
                position: '',
                email: '',
                phone: '',
                ...row,
            };
            normalized.contact_id = String(normalized.contact_id || '');
            normalized.contact_search = normalized.contact_search || normalized.full_name || '';
            normalized.search_open = false;
            normalized.search_loading = false;
            normalized.search_searched = false;
            normalized.search_results = Array.isArray(normalized.search_results) ? normalized.search_results : [];

            return normalized;
        },
        filteredRoleContacts() {
            const businessName = String(document.querySelector('[name="business_name"]')?.value || '').trim().toLowerCase();

            if (businessName === '') {
                return roleContactRecords;
            }

            const matching = roleContactRecords.filter((contact) => String(contact.company_name || '').trim().toLowerCase() === businessName);

            return matching.length > 0 ? matching : roleContactRecords;
        },
        contactOptionLabel(contact) {
            const company = String(contact.company_name || '').trim();
            const position = String(contact.position || '').trim();

            return [contact.label, company, position].filter(Boolean).join(' - ');
        },
        contactOptionMeta(contact) {
            return [
                contact.company_name,
                contact.position,
                contact.email,
                contact.phone,
            ].filter(Boolean).join(' - ');
        },
        findRoleContact(contactId) {
            return roleContactRecords.find((contact) => String(contact.id) === String(contactId)) || null;
        },
        hydrateKeyOfficer(fieldName, contactId) {
            const contact = this.findRoleContact(contactId);
            if (!contact) return;
            this.setFieldValue(fieldName, contact.label || '');
        },
        selectKeyOfficer(fieldName, row, contact) {
            if (this.isDuplicateRoleContact(contact.id, row)) return;

            if (!roleContactRecords.some((record) => String(record.id) === String(contact.id))) {
                roleContactRecords.push(contact);
            }

            row.contact_id = String(contact.id || '');
            row.contact_search = contact.label || '';
            row.full_name = contact.label || '';
            row.search_open = false;
            this.setFieldValue(fieldName, contact.label || '');
        },
        hydrateRoleRow(row, contactId) {
            const contact = this.findRoleContact(contactId);
            if (!contact) return;
            if (this.isDuplicateRoleContact(contact.id, row)) return;

            row.contact_id = String(contact.id || '');
            row.contact_search = contact.label || '';
            row.full_name = contact.label || '';
            row.position = contact.position || '';
            row.nationality = contact.nationality || '';
            row.date_of_birth = contact.date_of_birth || '';
            row.tin = contact.tin || '';
            row.address = contact.address || '';
            row.email = contact.email || '';
            row.phone = contact.phone || '';
            row.search_open = false;
        },
        selectedRoleContactIds(exceptRow = null) {
            return [this.keyOfficerSelections.president, this.keyOfficerSelections.treasurer, ...this.signatories, ...this.ubos]
                .filter((row) => row !== exceptRow)
                .filter(Boolean)
                .map((row) => String(row.contact_id || ''))
                .filter(Boolean);
        },
        isDuplicateRoleContact(contactId, currentRow = null) {
            return this.selectedRoleContactIds(currentRow).includes(String(contactId || ''));
        },
        roleSearchResults(row) {
            const results = row.search_searched
                ? row.search_results
                : this.filteredRoleContacts();

            return results.filter((contact) => !this.isDuplicateRoleContact(contact.id, row)).slice(0, 8);
        },
        openRoleSearch(row, roleType = '') {
            row.search_open = true;
            row.search_searched = false;
            row.search_results = this.filteredRoleContacts();
            this.searchRoleContacts(row, roleType);
        },
        closeRoleSearch(row) {
            row.search_open = false;
        },
        async searchRoleContacts(row, roleType = '') {
            row.search_open = true;
            const query = String(row.full_name || row.contact_search || '').trim();

            row.search_loading = true;
            row.search_searched = query !== '';

            const params = new URLSearchParams({
                search: query,
                business_name: document.querySelector('[name="business_name"]')?.value || '',
                role: roleType,
            });

            try {
                const response = await fetch(`${roleContactSearchUrl}?${params.toString()}`, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) throw new Error('Unable to search contacts');

                const payload = await response.json();
                const contacts = Array.isArray(payload.contacts) ? payload.contacts : [];
                contacts.forEach((contact) => {
                    if (!roleContactRecords.some((record) => String(record.id) === String(contact.id))) {
                        roleContactRecords.push(contact);
                    }
                });
                row.search_results = contacts;
            } catch (error) {
                row.search_results = this.filteredRoleContacts().filter((contact) => {
                    const query = String(row.full_name || row.contact_search || '').trim().toLowerCase();
                    if (query === '') return true;

                    return [
                        contact.label,
                        contact.company_name,
                        contact.position,
                        contact.email,
                        contact.phone,
                    ].join(' ').toLowerCase().includes(query);
                });
            } finally {
                row.search_loading = false;
            }
        },
        selectRoleContact(row, contact) {
            if (this.isDuplicateRoleContact(contact.id, row)) return;

            if (!roleContactRecords.some((record) => String(record.id) === String(contact.id))) {
                roleContactRecords.push(contact);
            }

            this.hydrateRoleRow(row, contact.id);
        },
        saveCompanyDraft() {
            const form = document.querySelector('[x-data="companyBifForm()"]');
            if (!form) return;

            const fields = {};
            Array.from(form.elements).forEach((field) => {
                if (!field.name || field.name === '_token') return;
                if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) return;
                if (field.type === 'checkbox') {
                    fields[field.name] = fields[field.name] || [];
                    fields[field.name].push(field.value);
                    return;
                }
                fields[field.name] = field.value;
            });

            window.localStorage.setItem(draftStorageKey, JSON.stringify({
                fields,
                businessOrganization: this.businessOrganization,
                officeType: this.officeType,
                keyOfficerSelections: this.keyOfficerSelections,
                employees: this.employees,
                signatories: this.signatories,
                ubos: this.ubos,
            }));
        },
        clearCompanyDraft() {
            window.localStorage.removeItem(draftStorageKey);
        },
        restoreDraftIfRequested() {
            const params = new URLSearchParams(window.location.search);
            if (!params.has('open_create_company') && !params.has('company_contact_created')) return;

            const rawDraft = window.localStorage.getItem(draftStorageKey);
            if (!rawDraft) return;

            try {
                const draft = JSON.parse(rawDraft);
                this.businessOrganization = draft.businessOrganization || this.businessOrganization;
                this.officeType = draft.officeType || this.officeType;
                if (draft.keyOfficerSelections) {
                    this.keyOfficerSelections.president = this.normalizeRoleRow(draft.keyOfficerSelections.president || {});
                    this.keyOfficerSelections.treasurer = this.normalizeRoleRow(draft.keyOfficerSelections.treasurer || {});
                }
                this.employees = draft.employees || this.employees;
                this.signatories = Array.isArray(draft.signatories) && draft.signatories.length > 0
                    ? draft.signatories.map((row) => this.normalizeRoleRow(row))
                    : this.signatories;
                this.ubos = Array.isArray(draft.ubos) && draft.ubos.length > 0
                    ? draft.ubos.map((row) => this.normalizeRoleRow(row))
                    : this.ubos;

                this.$nextTick(() => {
                    const form = document.querySelector('[x-data="companyBifForm()"]');
                    Object.entries(draft.fields || {}).forEach(([name, value]) => {
                        const fields = Array.from(form?.elements || []).filter((field) => field.name === name);
                        fields.forEach((field) => {
                            if (field.type === 'checkbox' || field.type === 'radio') {
                                const values = Array.isArray(value) ? value : [value];
                                field.checked = values.map(String).includes(String(field.value));
                            } else if (!name.includes('authorized_signatories[') && !name.includes('ubos[')) {
                                field.value = value;
                            }
                            field.dispatchEvent(new Event('input', { bubbles: true }));
                            field.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                    });
                });
            } catch (error) {
                window.localStorage.removeItem(draftStorageKey);
            }
        },
        goToContactCreate(row) {
            this.saveCompanyDraft();

            const [firstName = '', ...lastNameParts] = String(row.full_name || row.contact_search || '').trim().split(/\s+/).filter(Boolean);
            const params = new URLSearchParams({
                open_create: '1',
                return_to_company: '1',
            });
            const prefill = {
                company_name: document.querySelector('[name="business_name"]')?.value || '',
                first_name: firstName || '',
                last_name: lastNameParts.join(' '),
                email: row.email || '',
                mobile_number: row.phone || '',
                position: row.position || '',
                contact_address: row.address || '',
            };

            Object.entries(prefill).forEach(([key, value]) => {
                if (String(value || '').trim() !== '') {
                    params.set(key, value);
                }
            });

            window.location.href = `${addContactUrl}?${params.toString()}`;
        },
        addSignatory() { this.signatories.push(this.emptyRow()); },
        removeSignatory(index) { if (this.signatories.length > 1) this.signatories.splice(index, 1); },
        addUbo() { this.ubos.push(this.emptyRow()); },
        removeUbo(index) { if (this.ubos.length > 1) this.ubos.splice(index, 1); },
        fillIfBlank(fieldName, value) {
            const input = document.querySelector(`[name="${fieldName}"]`);
            if (!input) return;
            if (String(input.value || '').trim() !== '') return;
            if (String(value || '').trim() === '') return;
            input.value = value;
        },
        setFieldValue(fieldName, value) {
            const input = document.querySelector(`[name="${fieldName}"]`);
            if (!input) return;
            const nextValue = String(value || '').trim();
            if (nextValue === '' && String(input.value || '').trim() !== '') return;
            input.value = String(value || '');
        },
        syncInternalUseDefaults() {
            this.fillIfBlank('referred_by', @js(auth()->user()->name ?? ''));
            this.fillIfBlank('president_use_only_name', 'John Kelly Abalde');
        },
        hideEmployeeSearchResults(picker) {
            picker?.querySelector('[data-employee-search-results]')?.classList.add('hidden');
        },
        renderEmployeeSearchResults(picker, keyword = '') {
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
                button.innerHTML = `<div class="text-sm font-medium text-gray-800">${record.name || ''}</div><div class="text-xs text-gray-500">${[record.employee_code, record.position, record.department].filter(Boolean).join(' • ') || (record.email || '')}</div>`;
                button.addEventListener('click', () => {
                    input.value = record.name || '';
                    this.hideEmployeeSearchResults(picker);
                    this.syncInternalUseDefaults();
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });

                return button;
            }));

            results.classList.remove('hidden');
        },
        initEmployeeSearchPickers() {
            Array.from(document.querySelectorAll('[data-employee-picker]')).forEach((picker) => {
                const input = picker.querySelector('[data-employee-search-input]');
                if (!input) {
                    return;
                }

                input.addEventListener('focus', () => this.renderEmployeeSearchResults(picker, input.value));
                input.addEventListener('input', () => this.renderEmployeeSearchResults(picker, input.value));
                input.addEventListener('change', () => this.syncInternalUseDefaults());
                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        this.hideEmployeeSearchResults(picker);
                    }
                });
            });

            document.addEventListener('click', (event) => {
                Array.from(document.querySelectorAll('[data-employee-picker]')).forEach((picker) => {
                    if (!picker.contains(event.target)) {
                        this.hideEmployeeSearchResults(picker);
                    }
                });
            });
        },
        hydrateFromContact(event) {
            const option = event?.target?.selectedOptions?.[0];
            if (!option) return;

            const companyName = option.dataset.companyName || '';
            const email = option.dataset.email || '';
            const phone = option.dataset.phone || '';
            const businessPhone = option.dataset.businessPhone || phone;
            const mobile = option.dataset.mobile || phone;
            const address = option.dataset.address || '';
            const tin = option.dataset.tin || '';
            const zipCode = option.dataset.zipCode || '';
            const nationalityStatus = option.dataset.nationalityStatus || '';
            const businessOrganization = option.dataset.businessOrganization || '';
            const businessOrganizationOther = option.dataset.businessOrganizationOther || '';
            const officeType = option.dataset.officeType || '';
            const officeTypeOther = option.dataset.officeTypeOther || '';
            const alternativeBusinessName = option.dataset.alternativeBusinessName || '';
            const contactName = option.dataset.contactName || '';
            const contactPosition = option.dataset.contactPosition || '';

            this.setFieldValue('business_name', companyName);
            this.setFieldValue('alternative_business_name', alternativeBusinessName);
            this.setFieldValue('authorized_contact_person_email', email);
            this.setFieldValue('authorized_contact_person_phone', phone);
            this.setFieldValue('authorized_contact_person_name', contactName);
            this.setFieldValue('authorized_contact_person_position', contactPosition);
            this.setFieldValue('business_phone', businessPhone);
            this.setFieldValue('mobile_no', mobile);
            this.setFieldValue('business_address', address);
            this.setFieldValue('tin_no', tin);
            this.setFieldValue('zip_code', zipCode);
            if (String(businessOrganization || '').trim() !== '' || String(this.businessOrganization || '').trim() === '') {
                this.businessOrganization = businessOrganization;
            }
            this.setFieldValue('business_organization_other', businessOrganizationOther);
            if (String(officeType || '').trim() !== '' || String(this.officeType || '').trim() === '') {
                this.officeType = officeType;
            }
            this.setFieldValue('office_type_other', officeTypeOther);

            if (nationalityStatus) {
                const nationalityInput = document.querySelector(`[name="nationality_status"][value="${nationalityStatus}"]`);
                if (nationalityInput) {
                    nationalityInput.checked = true;
                }
            }

            this.syncInternalUseDefaults();
        },
        syncEmployeeTotal() {
            this.employees.total = (Number(this.employees.male) || 0) + (Number(this.employees.female) || 0) + (Number(this.employees.pwd) || 0);
        },
    };
}
</script>

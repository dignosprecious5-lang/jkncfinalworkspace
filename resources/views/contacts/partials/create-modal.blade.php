@php
    $editContact = $editContact ?? null;
    $metaCreatedBy = old('created_by', data_get($editContact, 'created_by') ?: ($createdByDisplay ?? 'Admin User'));
    $metaCreatedAt = old('created_at_display', data_get($editContact, 'created_at')
        ? \Illuminate\Support\Carbon::parse(data_get($editContact, 'created_at'))->format('F j, Y • g:i A')
        : ($createdAtDisplay ?? now()->format('F j, Y • g:i A')));
    $metaBusinessDate = old('business_date', data_get($editContact, 'business_date')
        ? \Illuminate\Support\Carbon::parse(data_get($editContact, 'business_date'))->toDateString()
        : ($defaultBusinessDate ?? now()->toDateString()));

    $serviceInquiryOptions = [
        'Business Registration / Entity Formation',
        'Business Permit (New / Renewal)',
        'Tax Compliance / BIR Filing',
        'Accounting / Bookkeeping',
        'Financial Statements Preparation',
        'Corporate Officers Services',
        'Business Advisory / Consultation',
        'Regulatory Compliance',
        'Other',
    ];
    $recommendationOptions = [
        'Proceed to Proposal Preparation',
        'Refer to Senior Consultant',
        'Refer to Subject Matter Expert',
        'For Further Study / Assessment',
        'For Due Diligence / Background Check',
        'Schedule Consultation Meeting',
        'Request Additional Information from Client',
        'Not Suitable for Engagement',
        'Others',
    ];
    $leadSourceOptions = [
        'Facebook', 'Instagram', 'LinkedIn', 'Tiktok', 'Website', 'Google Search',
        'Google Ads', 'Walk-In', 'Referral-Client', 'Referral-Partner', 'Referral-Employee',
        'Email Inquiry', 'Phone Call', 'SMS/Viber', 'WhatsApp', 'Online Market Place',
        'Event Seminar', 'Webinar', 'Trade Show Expo', 'Flyer / Brochure',
        'Radio Advertisement', 'Returning Client', 'Influencer / Content Creator',
        'Television Advertisement', 'Other',
    ];
    $selectedInquiryTypes = old('service_inquiry_types', []);
    $selectedRecommendationOptions = old('recommendation_options', []);
    $selectedLeadSourceOptions = old('lead_source_channels', []);
    $defaultReferredBy = old('referred_by', auth()->user()->name ?? '');
@endphp

<div id="createContactModal" class="fixed inset-0 z-[60] hidden" aria-hidden="true">
    <button id="createContactModalOverlay" type="button" aria-label="Close create contact panel" class="absolute inset-0 bg-slate-900/45 opacity-0 transition-opacity duration-300"></button>
    <div class="absolute inset-y-0 right-0 flex w-full justify-end overflow-hidden pointer-events-none">
        <div id="createContactPanel" class="pointer-events-auto flex h-full w-full max-w-[720px] translate-x-full flex-col border-l border-gray-200 bg-white shadow-2xl transition-transform duration-300 ease-out sm:max-w-[680px]">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5 sm:px-8">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900">Create Contact</h2>
                    <p class="mt-1 text-sm text-gray-500">Store the client master record before creating deals.</p>
                </div>
                <button id="closeCreateContactModal" type="button" class="text-2xl text-gray-500 hover:text-gray-800">&times;</button>
            </div>

            <form method="POST" action="{{ route('contacts.store') }}" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <input id="owner_id" type="hidden" name="owner_id" value="{{ old('owner_id', $selectedOwnerId) }}">

                <div class="min-h-0 flex-1 space-y-6 overflow-y-auto px-6 py-6 sm:px-8">
                    <div class="space-y-4 border-b border-gray-100 pb-5">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Client Intake</p>
                            <p class="text-xs text-gray-400">Use this as the main contact and business record.</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3 sm:items-end">
                            <div>
                                <label for="business_date" class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Business Date</label>
                                <input id="business_date" type="date" name="business_date" value="{{ $metaBusinessDate }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div>
                                <label for="cif_no_preview" class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">CIF No.</label>
                                <input id="cif_no_preview" type="text" value="Auto-generated after save" class="h-10 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 text-sm text-gray-500 outline-none" readonly disabled>
                            </div>
                            <div class="relative">
                                <label for="ownerDropdownTrigger" class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Owner</label>
                                <button id="ownerDropdownTrigger" type="button" class="inline-flex h-10 w-full items-center justify-between gap-2 rounded-full border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-700 hover:bg-gray-100">
                                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                    <span id="ownerSelectedLabel">Owner: {{ $selectedOwnerName }}</span>
                                    <i class="fas fa-chevron-down text-[10px] text-gray-400"></i>
                                </button>

                                <div id="ownerDropdownMenu" class="absolute right-0 z-20 mt-2 hidden w-full min-w-0 rounded-xl border border-gray-200 bg-white p-2 shadow-lg sm:w-72">
                                    <div class="relative mb-2">
                                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400"></i>
                                        <input id="ownerSearch" type="text" placeholder="Search owner..." class="h-9 w-full rounded-lg border border-gray-200 pl-8 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                    </div>
                                    <div class="max-h-56 space-y-1 overflow-y-auto">
                                        @foreach ($owners as $owner)
                                            @php
                                                $ownerInitials = strtoupper(collect(explode(' ', trim($owner['name'])))->filter()->map(fn ($segment) => mb_substr($segment, 0, 1))->take(2)->implode(''));
                                            @endphp
                                            <button type="button" class="owner-option flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-sm text-gray-700 hover:bg-gray-50" data-owner-id="{{ $owner['id'] }}" data-owner-name="{{ $owner['name'] }}">
                                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-[10px] font-semibold text-blue-700">{{ $ownerInitials }}</span>
                                                <span>{{ $owner['name'] }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2 border-t border-gray-100 pt-3">
                            <div>
                                <p class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Created By</p>
                                <p class="text-sm text-gray-500">{{ $metaCreatedBy }}</p>
                            </div>
                            <div>
                                <p class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Created At</p>
                                <p class="text-sm text-gray-500">
                                    <span id="createdAtLiveValue">{{ $metaCreatedAt }}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <section class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
                        <h3 class="text-base font-semibold text-gray-900">Customer Type</h3>
                        <p class="mb-4 text-xs text-gray-500">Classify the record before entering the full client profile.</p>
                        <div class="space-y-3">
                            <div class="grid items-center gap-2 sm:grid-cols-[72px_1fr]">
                                <label class="text-sm font-medium text-gray-700">Type</label>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach (['business' => 'Business', 'individual' => 'Individual'] as $value => $label)
                                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 hover:border-blue-200 hover:bg-blue-50/40">
                                            <input type="radio" name="customer_type" value="{{ $value }}" @checked(old('customer_type') === $value) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 p-4">
                        <h3 class="text-base font-semibold text-gray-900">Contact Information</h3>
                        <p class="mb-4 text-xs text-gray-500">Primary contact and profile details from the client form.</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div><label for="salutation" class="mb-1 block text-sm font-medium text-gray-700">Salutation</label><select id="salutation" name="salutation" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"><option value="" disabled @selected(blank(old('salutation'))) >Select salutation</option>@foreach (['Mr.', 'Ms.', 'Mrs.', 'Atty.', 'CPA', 'Engr.', 'Dr.'] as $option)<option value="{{ $option }}" @selected(old('salutation') === $option)>{{ $option }}</option>@endforeach</select></div>
                            <div><label for="sex" class="mb-1 block text-sm font-medium text-gray-700">Sex</label><select id="sex" name="sex" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"><option value="" disabled @selected(blank(old('sex'))) >Select sex</option>@foreach (['Male', 'Female', 'Prefer not to say'] as $option)<option value="{{ $option }}" @selected(old('sex') === $option)>{{ $option }}</option>@endforeach</select></div>
                            <div><label for="first_name" class="mb-1 block text-sm font-medium text-gray-700">First Name <span class="text-red-500">*</span></label><input id="first_name" name="first_name" required value="{{ $prefillContact['first_name'] ?? old('first_name') }}" placeholder="First Name" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">@error('first_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div><label for="middle_initial" class="mb-1 block text-sm font-medium text-gray-700">Middle Initial</label><input id="middle_initial" name="middle_initial" value="{{ old('middle_initial') }}" placeholder="Middle Initial" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                            <div><label for="middle_name" class="mb-1 block text-sm font-medium text-gray-700">Middle Name</label><input id="middle_name" name="middle_name" value="{{ old('middle_name') }}" placeholder="Middle Name" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                            <div><label for="last_name" class="mb-1 block text-sm font-medium text-gray-700">Last Name <span class="text-red-500">*</span></label><input id="last_name" name="last_name" required value="{{ $prefillContact['last_name'] ?? old('last_name') }}" placeholder="Last Name" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">@error('last_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div><label for="name_extension" class="mb-1 block text-sm font-medium text-gray-700">Name Extension</label><input id="name_extension" name="name_extension" value="{{ old('name_extension') }}" placeholder="Jr./Sr./III" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                            <div><label for="date_of_birth" class="mb-1 block text-sm font-medium text-gray-700">Date of Birth</label><input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" placeholder="Date of Birth (MM/DD/YYYY)" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                            <div><label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email Address <span class="text-red-500">*</span></label><input id="email" name="email" type="email" required value="{{ $prefillContact['email'] ?? old('email') }}" placeholder="Email Address" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">@error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div><label for="mobile_number" class="mb-1 block text-sm font-medium text-gray-700">Mobile Number <span class="text-red-500">*</span></label><input id="mobile_number" name="mobile_number" required value="{{ $prefillContact['mobile_number'] ?? old('mobile_number', old('mobile')) }}" placeholder="Mobile Number" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">@error('mobile_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div class="sm:col-span-2"><label for="contact_address" class="mb-1 block text-sm font-medium text-gray-700">Address</label><textarea id="contact_address" name="contact_address" rows="2" placeholder="House No., Street, Barangay, City, Province, Postal Code" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ $prefillContact['contact_address'] ?? old('contact_address') }}</textarea></div>
                            <div><label for="company_name" class="mb-1 block text-sm font-medium text-gray-700">Company</label><input id="company_name" name="company_name" value="{{ $prefilledCompanyName ?? old('company_name') }}" placeholder="Company Name" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                            <div><label for="position" class="mb-1 block text-sm font-medium text-gray-700">Position / Designation</label><input id="position" name="position" value="{{ $prefillContact['position'] ?? old('position') }}" placeholder="Position / Designation" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                            <div class="sm:col-span-2"><label for="company_address" class="mb-1 block text-sm font-medium text-gray-700">Company Address</label><textarea id="company_address" name="company_address" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('company_address') }}</textarea></div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 p-4">
                        <h3 class="text-base font-semibold text-gray-900">Business Information</h3>
                        <p class="mb-4 text-xs text-gray-500">Capture ownership, structure, and business capacity details.</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div><label for="business_type_organization" class="mb-1 block text-sm font-medium text-gray-700">Business Type / Organization</label><input id="business_type_organization" name="business_type_organization" value="{{ old('business_type_organization') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                            <div class="sm:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-gray-700">Organization Structure</label>
                                <div class="grid gap-2 sm:grid-cols-3">
                                    @foreach (['Sole Proprietorship', 'Partnership', 'Non-Stock', 'Corporation', 'Stock', 'Others'] as $option)
                                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700"><input type="radio" name="organization_type" value="{{ $option }}" @checked(old('organization_type') === $option) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"><span>{{ $option }}</span></label>
                                    @endforeach
                                </div>
                            </div>
                            <div id="organizationTypeOtherWrap" class="{{ old('organization_type') === 'Others' ? '' : 'hidden' }} sm:col-span-2"><label for="organization_type_other" class="mb-1 block text-sm font-medium text-gray-700">Others (Specify)</label><input id="organization_type_other" name="organization_type_other" value="{{ old('organization_type_other') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                            <div><label for="nature_of_business" class="mb-1 block text-sm font-medium text-gray-700">Nature of Business / Industry</label><input id="nature_of_business" name="nature_of_business" value="{{ old('nature_of_business') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                            <div><label for="capitalization_amount" class="mb-1 block text-sm font-medium text-gray-700">Capitalization / Capital Investment</label><input id="capitalization_amount" name="capitalization_amount" value="{{ old('capitalization_amount') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                            <div><label for="ownership_structure" class="mb-1 block text-sm font-medium text-gray-700">Ownership Structure</label><input id="ownership_structure" name="ownership_structure" value="{{ old('ownership_structure') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                            <div><label for="previous_year_revenue" class="mb-1 block text-sm font-medium text-gray-700">Previous Year Total Sales / Revenue</label><input id="previous_year_revenue" name="previous_year_revenue" value="{{ old('previous_year_revenue') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                            <div><label for="years_operating" class="mb-1 block text-sm font-medium text-gray-700">How Long the Business Has Been Operating</label><input id="years_operating" name="years_operating" value="{{ old('years_operating') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                            <div><label for="projected_current_year_revenue" class="mb-1 block text-sm font-medium text-gray-700">Projected Sales / Revenue for the Current Year</label><input id="projected_current_year_revenue" name="projected_current_year_revenue" value="{{ old('projected_current_year_revenue') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                            <div class="sm:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-gray-700">Ownership Nationality</label>
                                <div class="grid gap-2 sm:grid-cols-3">
                                    @foreach (['100% Filipino-Owned', 'With Foreign Ownership', 'Foreign-Owned Business'] as $option)
                                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700"><input type="radio" name="ownership_flag" value="{{ $option }}" @checked(old('ownership_flag') === $option) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"><span>{{ $option }}</span></label>
                                    @endforeach
                                </div>
                            </div>
                            <div id="foreignBusinessNatureWrap" class="{{ old('ownership_flag') === 'Foreign-Owned Business' ? '' : 'hidden' }} sm:col-span-2"><label for="foreign_business_nature" class="mb-1 block text-sm font-medium text-gray-700">Foreign-Owned Business (Specify Nature of Business)</label><textarea id="foreign_business_nature" name="foreign_business_nature" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('foreign_business_nature') }}</textarea></div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 p-4">
                        <h3 class="text-base font-semibold text-gray-900">Service Inquiry Type</h3>
                        <p class="mb-4 text-xs text-gray-500">Select one or more service inquiry categories.</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($serviceInquiryOptions as $option)
                                <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                    <input type="checkbox" name="service_inquiry_types[]" value="{{ $option }}" @checked(in_array($option, $selectedInquiryTypes, true)) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500" @if ($option === 'Other') data-other-toggle="service_inquiry_other_wrap" @endif>
                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div id="service_inquiry_other_wrap" class="{{ in_array('Other', $selectedInquiryTypes, true) ? '' : 'hidden' }} mt-3"><label for="service_inquiry_other" class="mb-1 block text-sm font-medium text-gray-700">Other Service Inquiry</label><input id="service_inquiry_other" name="service_inquiry_other" value="{{ old('service_inquiry_other') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 p-4"><h3 class="text-base font-semibold text-gray-900">Inquiry</h3><p class="mb-4 text-xs text-gray-500">Add the client's inquiry details.</p><textarea id="inquiry" name="inquiry" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('inquiry') }}</textarea></section>

                    <section class="rounded-2xl border border-gray-200 p-4">
                        <h3 class="text-base font-semibold text-gray-900">For JKNC Use Only</h3>
                        <p class="mb-4 text-xs text-gray-500">Internal notes and assignment details.</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2"><label for="jknc_notes" class="mb-1 block text-sm font-medium text-gray-700">Notes</label><textarea id="jknc_notes" name="jknc_notes" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('jknc_notes') }}</textarea></div>
                            <div class="relative sm:col-span-2" data-employee-picker>
                                <label for="sales_marketing" class="mb-1 block text-sm font-medium text-gray-700">Sales & Marketing</label>
                                <textarea id="sales_marketing" name="sales_marketing" rows="2" autocomplete="off" data-employee-search-input class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('sales_marketing') }}</textarea>
                                <div class="absolute z-20 mt-1 hidden w-full rounded-lg border border-gray-200 bg-white shadow-lg" data-employee-search-results></div>
                                <p class="mt-1 text-xs text-gray-500">Search existing employees from Human Capital. You can still type manually.</p>
                            </div>
                            <div class="relative" data-employee-picker>
                                <label for="consultant_lead" class="mb-1 block text-sm font-medium text-gray-700">Consultant Lead</label>
                                <input id="consultant_lead" name="consultant_lead" value="{{ old('consultant_lead') }}" autocomplete="off" data-employee-search-input class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <div class="absolute z-20 mt-1 hidden w-full rounded-lg border border-gray-200 bg-white shadow-lg" data-employee-search-results></div>
                                <p class="mt-1 text-xs text-gray-500">Search existing employees from Human Capital. You can still type manually.</p>
                            </div>
                            <div class="relative" data-employee-picker>
                                <label for="lead_associate" class="mb-1 block text-sm font-medium text-gray-700">Lead Associate</label>
                                <input id="lead_associate" name="lead_associate" value="{{ old('lead_associate') }}" autocomplete="off" data-employee-search-input class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <div class="absolute z-20 mt-1 hidden w-full rounded-lg border border-gray-200 bg-white shadow-lg" data-employee-search-results></div>
                                <p class="mt-1 text-xs text-gray-500">Search existing employees from Human Capital. You can still type manually.</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 p-4">
                        <h3 class="text-base font-semibold text-gray-900">Recommendation</h3>
                        <p class="mb-4 text-xs text-gray-500">Choose one or more recommended next actions.</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($recommendationOptions as $option)
                                <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                    <input type="checkbox" name="recommendation_options[]" value="{{ $option }}" @checked(in_array($option, $selectedRecommendationOptions, true)) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500" @if ($option === 'Others') data-other-toggle="recommendation_other_wrap" @endif>
                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div id="recommendation_other_wrap" class="{{ in_array('Others', $selectedRecommendationOptions, true) ? '' : 'hidden' }} mt-3"><label for="recommendation_other" class="mb-1 block text-sm font-medium text-gray-700">Others (Specify)</label><input id="recommendation_other" name="recommendation_other" value="{{ old('recommendation_other') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 p-4">
                        <h3 class="text-base font-semibold text-gray-900">Lead Source</h3>
                        <p class="mb-4 text-xs text-gray-500">Track all channels that generated this lead.</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($leadSourceOptions as $option)
                                <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                    <input type="checkbox" name="lead_source_channels[]" value="{{ $option }}" @checked(in_array($option, $selectedLeadSourceOptions, true)) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500" @if ($option === 'Other') data-other-toggle="lead_source_other_wrap" @endif>
                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div id="lead_source_other_wrap" class="{{ in_array('Other', $selectedLeadSourceOptions, true) ? '' : 'hidden' }} mt-3"><label for="lead_source_other" class="mb-1 block text-sm font-medium text-gray-700">Other Lead Source</label><input id="lead_source_other" name="lead_source_other" value="{{ old('lead_source_other') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 p-4">
                        <h3 class="text-base font-semibold text-gray-900">Referral Information</h3>
                        <p class="mb-4 text-xs text-gray-500">Who referred this client or lead source details.</p>
                        <div class="relative" data-employee-picker>
                            <label for="referred_by" class="mb-1 block text-sm font-medium text-gray-700">Referred By</label>
                            <input id="referred_by" name="referred_by" value="{{ $defaultReferredBy }}" autocomplete="off" data-employee-search-input class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            <div class="absolute z-20 mt-1 hidden w-full rounded-lg border border-gray-200 bg-white shadow-lg" data-employee-search-results></div>
                            <p class="mt-1 text-xs text-gray-500">Defaults to the contact creator. You can still change it manually.</p>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 p-4">
                        <h3 class="text-base font-semibold text-gray-900">Lead Stage</h3>
                        <p class="mb-4 text-xs text-gray-500">Current stage of the lead in the pipeline.</p>
                        @php
                            $leadStageOptions = ['Inquiry', 'Qualification', 'Consultation', 'Proposal', 'Negotiation', 'Payment', 'Activation', 'Closed Won', 'Closed Lost'];
                            $selectedLeadStage = old('lead_stage', 'Inquiry');
                        @endphp
                        <div>
                            <label for="lead_stage" class="mb-1 block text-sm font-medium text-gray-700">Lead Stage</label>
                            <select id="lead_stage" name="lead_stage" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                @foreach ($leadStageOptions as $option)
                                    <option value="{{ $option }}" @selected($selectedLeadStage === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    </section>

                    @if ($errors->any())
                        <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $errors->first() }}</div>
                    @endif
                </div>

                <div class="mt-auto flex items-center justify-end gap-3 border-t border-gray-100 bg-white px-6 py-4 sm:px-8">
                    <button id="cancelCreateContactModal" type="button" class="h-10 rounded-lg border border-gray-300 px-4 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="h-10 rounded-lg bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700">Save Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>

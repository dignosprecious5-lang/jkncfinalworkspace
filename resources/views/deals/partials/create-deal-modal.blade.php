
@php
    $formAction = $formAction ?? route('deals.store');
    $formMethod = strtoupper($formMethod ?? 'POST');
    $submitLabel = $submitLabel ?? 'Save & View Deal';
    $panelTitle = $panelTitle ?? 'Create Deal';
    $panelSubtitle = $panelSubtitle ?? 'Select an existing client, then complete the consulting and deal form.';
    $draft = $dealDraft ?? [];
    $financeUsers = $financeUsers ?? [];
    $employeeOptions = $employeeOptions ?? [];
    $contactRecords = $contactRecords ?? [];
    $companyRecords = $companyRecords ?? [];
    $serviceRequirementCatalog = $serviceRequirementCatalog ?? [];
    $openDealModal = $openDealModal ?? false;
    $numericFieldValue = static function ($value): string {
        if ($value === null) {
            return '';
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);
        return is_string($normalized) ? trim($normalized) : '';
    };
    $serviceAreaOptions = collect($serviceAreaOptions ?? [])
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
        ->map(fn ($value): string => trim((string) $value))
        ->unique()
        ->values()
        ->all();
    $serviceGroups = collect($serviceGroups ?? [])
        ->map(fn ($values): array => collect($values)
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn ($value): string => trim((string) $value))
            ->unique()
            ->values()
            ->all())
        ->filter(fn ($values): bool => $values !== [])
        ->all();
    $productOptionsByServiceArea = collect($productOptionsByServiceArea ?? [])
        ->map(fn ($values): array => collect($values)
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn ($value): string => trim((string) $value))
            ->unique()
            ->values()
            ->all())
        ->filter(fn ($values): bool => $values !== [])
        ->all();
    $productOptions = collect($productOptionsByServiceArea)
        ->flatten()
        ->push('Others')
        ->unique()
        ->values()
        ->all();
    $servicePricing = collect($servicePricing ?? [])
        ->mapWithKeys(fn ($amount, $service): array => [trim((string) $service) => (float) $amount])
        ->all();
    $productPricing = collect($productPricing ?? [])
        ->mapWithKeys(fn ($amount, $product): array => [trim((string) $product) => (float) $amount])
        ->all();
    $requirementRows = [
        'client_contact_form' => 'Client Contact Form',
        'deal_form' => 'Deal Form',
        'business_information_form' => 'Business Information Form',
        'client_information_form' => 'Client Information Form',
        'service_task_activation_routing_tracker' => 'Service Task Activation & Routing Tracker (Start)',
        'others' => 'Others',
    ];
    $requiredActions = [
        'Document Review',
        'Regulatory Research',
        'Drafting of Documents',
        'Client Consultation',
        'Compliance Check',
        'Financial Analysis',
        'Government Filing / Processing',
        'Internal Approval',
    ];
    $selectedServiceAreas = old('service_area_options', $draft['service_area_options'] ?? []);
    if (! is_array($selectedServiceAreas)) {
        $selectedServiceAreas = [];
    }
    $serviceAreaOtherEntries = old('service_area_other', $draft['service_area_other'] ?? []);
    if (! is_array($serviceAreaOtherEntries)) {
        $serviceAreaOtherEntries = is_string($serviceAreaOtherEntries) && trim($serviceAreaOtherEntries) !== ''
            ? [trim($serviceAreaOtherEntries)]
            : [];
    }
    if (count($serviceAreaOtherEntries) === 0) {
        $serviceAreaOtherEntries = collect($selectedServiceAreas)
            ->filter(fn ($value): bool => is_string($value) && Str::startsWith(trim((string) $value), 'Others: '))
            ->map(fn ($value): string => trim(Str::after(trim((string) $value), 'Others: ')))
            ->filter(fn ($value): bool => $value !== '')
            ->values()
            ->all();
    }
    $serviceAreaOtherEntries = collect($serviceAreaOtherEntries)
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
        ->map(fn ($value): string => trim((string) $value))
        ->unique()
        ->values()
        ->all();
    $selectedServiceAreas = collect($selectedServiceAreas)
        ->filter(fn ($value): bool => is_string($value) && ! Str::startsWith(trim((string) $value), 'Others: '))
        ->map(fn ($value): string => trim((string) $value))
        ->values()
        ->all();
    if (count($serviceAreaOtherEntries) > 0 && ! in_array('Others', $selectedServiceAreas, true)) {
        $selectedServiceAreas[] = 'Others';
    }
    $rawSelectedServices = old('service_options', $draft['service_options'] ?? []);
    if (! is_array($rawSelectedServices)) {
        $rawSelectedServices = [];
    }
    $selectedServices = collect($rawSelectedServices)
        ->filter(fn ($value): bool => is_string($value) && ! Str::startsWith(trim($value), ['Custom: ', 'Others: ']))
        ->values()
        ->all();
    $serviceIdentificationCustomEntries = old('service_identification_custom');
    if (! is_array($serviceIdentificationCustomEntries)) {
        $serviceIdentificationCustomEntries = collect($rawSelectedServices)
            ->filter(fn ($value): bool => is_string($value) && Str::startsWith(trim($value), ['Custom: ', 'Others: ']))
            ->map(function ($value): string {
                $clean = trim((string) $value);
                if (Str::startsWith($clean, 'Custom: ')) {
                    return trim(Str::after($clean, 'Custom: '));
                }

                return trim(Str::after($clean, 'Others: '));
            })
            ->filter(fn ($value): bool => $value !== '')
            ->values()
            ->all();
    }
    $legacyServicesOther = old('services_other', $draft['services_other'] ?? []);
    if (is_string($legacyServicesOther) && trim($legacyServicesOther) !== '') {
        array_unshift($serviceIdentificationCustomEntries, trim($legacyServicesOther));
    } elseif (is_array($legacyServicesOther)) {
        foreach ($legacyServicesOther as $legacyServiceOtherEntry) {
            if (is_string($legacyServiceOtherEntry) && trim($legacyServiceOtherEntry) !== '') {
                array_unshift($serviceIdentificationCustomEntries, trim($legacyServiceOtherEntry));
            }
        }
    }
    $serviceIdentificationCustomEntries = collect($serviceIdentificationCustomEntries)
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
        ->map(fn ($value): string => trim((string) $value))
        ->unique()
        ->values()
        ->all();
    $hasServiceIdentificationCustomEntries = count($serviceIdentificationCustomEntries) > 0;
    $showServiceIdentificationCustomEntries = $hasServiceIdentificationCustomEntries;
    $rawSelectedProducts = old('product_options', $draft['product_options'] ?? []);
    if (! is_array($rawSelectedProducts)) {
        $rawSelectedProducts = [];
    }
    $selectedProducts = collect($rawSelectedProducts)
        ->filter(fn ($value): bool => is_string($value) && ! Str::startsWith(trim($value), ['Custom: ', 'Others: ']))
        ->values()
        ->all();
    $selectedProductCustomEntries = old('products_other_entries');
    if (! is_array($selectedProductCustomEntries)) {
        $selectedProductCustomEntries = collect($rawSelectedProducts)
            ->filter(fn ($value): bool => is_string($value) && Str::startsWith(trim($value), ['Custom: ', 'Others: ']))
            ->map(function ($value): string {
                $clean = trim((string) $value);
                if (Str::startsWith($clean, 'Custom: ')) {
                    return trim(Str::after($clean, 'Custom: '));
                }

                return trim(Str::after($clean, 'Others: '));
            })
            ->filter(fn ($value): bool => $value !== '')
            ->values()
            ->all();
    }
    $legacyProductsOther = old('products_other', $draft['products_other'] ?? '');
    if (is_string($legacyProductsOther) && trim($legacyProductsOther) !== '') {
        array_unshift($selectedProductCustomEntries, trim($legacyProductsOther));
    }
    $selectedProductCustomEntries = collect($selectedProductCustomEntries)
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
        ->map(fn ($value): string => trim((string) $value))
        ->unique()
        ->values()
        ->all();
    $hasCustomProductEntries = count($selectedProductCustomEntries) > 0;
    if ($hasCustomProductEntries && ! in_array('Others', $selectedProducts, true)) {
        $selectedProducts[] = 'Others';
    }
    $selectedRequiredActions = old('required_actions_options', $draft['required_actions_options'] ?? []);
    $requiredActionsCustomEntries = old('required_actions_custom', $draft['required_actions_custom'] ?? []);
    if (! is_array($requiredActionsCustomEntries)) {
        $requiredActionsCustomEntries = [];
    }
    $legacyRequiredActionsOther = old('required_actions_other', $draft['required_actions_other'] ?? '');
    if (is_string($legacyRequiredActionsOther) && trim($legacyRequiredActionsOther) !== '') {
        array_unshift($requiredActionsCustomEntries, trim($legacyRequiredActionsOther));
    }
    $requiredActionsCustomEntries = collect($requiredActionsCustomEntries)
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
        ->map(fn ($value): string => trim((string) $value))
        ->unique()
        ->values()
        ->all();
    $hasRequiredActionsCustomEntries = count($requiredActionsCustomEntries) > 0;
    $showRequiredActionsCustomEntries = $hasRequiredActionsCustomEntries || filled($legacyRequiredActionsOther);
    $clientRequirementsCustomEntries = old('client_requirements_custom', $draft['client_requirements_custom'] ?? []);
    if (! is_array($clientRequirementsCustomEntries)) {
        $clientRequirementsCustomEntries = [];
    }
    $clientRequirementsCustomEntries = collect($clientRequirementsCustomEntries)
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
        ->map(fn ($value): string => trim((string) $value))
        ->unique()
        ->values()
        ->all();
    $hasClientRequirementsCustomEntries = count($clientRequirementsCustomEntries) > 0;
    $clientRequirementsOthersStatus = old(
        'requirements_status.others',
        data_get($draft, 'requirements_status_map.others', data_get($draft, 'requirements_status.others'))
    );
    if ($hasClientRequirementsCustomEntries && ! in_array($clientRequirementsOthersStatus, ['provided', 'pending'], true)) {
        $clientRequirementsOthersStatus = 'pending';
    }
    $showClientRequirementsCustomEntries = in_array($clientRequirementsOthersStatus, ['provided', 'pending'], true) || $hasClientRequirementsCustomEntries;
    $parsedClientRequirementCustomRows = collect($clientRequirementsCustomEntries)->map(function ($entry) {
        $raw = trim((string) $entry);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^Other:\s*(.*?)\s*\|\s*(Provided|Pending)$/i', $raw, $matches) === 1) {
            return [
                'label' => trim($matches[1]),
                'status' => strtolower(trim($matches[2])) === 'provided' ? 'provided' : 'pending',
                'raw' => 'Other: '.trim($matches[1]).' | '.(strtolower(trim($matches[2])) === 'provided' ? 'Provided' : 'Pending'),
            ];
        }

        return [
            'label' => Str::startsWith($raw, 'Other: ') ? trim(Str::after($raw, 'Other: ')) : $raw,
            'status' => 'pending',
            'raw' => Str::startsWith($raw, 'Other: ') ? $raw.' | Pending' : 'Other: '.$raw.' | Pending',
        ];
    })->filter()->values()->all();
    $paymentTermsCustomEntries = old('payment_terms_custom', $draft['payment_terms_custom'] ?? []);
    if (! is_array($paymentTermsCustomEntries)) {
        $paymentTermsCustomEntries = [];
    }
    $legacyPaymentTermsOther = old('payment_terms_other', $draft['payment_terms_other'] ?? '');
    if (is_string($legacyPaymentTermsOther) && trim($legacyPaymentTermsOther) !== '' && ! Str::startsWith(trim($legacyPaymentTermsOther), 'Custom: ')) {
        array_unshift($paymentTermsCustomEntries, trim($legacyPaymentTermsOther));
    }
    $paymentTermsCustomEntries = collect($paymentTermsCustomEntries)
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
        ->map(function ($value): string {
            $clean = trim((string) $value);
            return Str::startsWith($clean, 'Custom: ') ? trim(Str::after($clean, 'Custom: ')) : $clean;
        })
        ->filter(fn ($value): bool => $value !== '')
        ->unique()
        ->values()
        ->all();
    $hasPaymentTermsCustomEntries = count($paymentTermsCustomEntries) > 0;
    $defaultServiceComplexityOptions = ['Standard Service', 'Complex Case'];
    $selectedServiceComplexity = old('service_complexity', $draft['service_complexity'] ?? '');
    $serviceComplexityCustomEntries = old('service_complexity_custom');
    if (! is_array($serviceComplexityCustomEntries)) {
        $serviceComplexityCustomEntries = collect($draft['service_complexity_custom'] ?? [])
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn ($value): string => trim((string) $value))
            ->values()
            ->all();
    }
    if (
        is_string($selectedServiceComplexity)
        && trim($selectedServiceComplexity) !== ''
        && ! in_array(trim($selectedServiceComplexity), $defaultServiceComplexityOptions, true)
    ) {
        $serviceComplexityCustomEntries[] = trim($selectedServiceComplexity);
    }
    $serviceComplexityCustomEntries = collect($serviceComplexityCustomEntries)
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
        ->map(fn ($value): string => trim((string) $value))
        ->unique()
        ->values()
        ->all();
    $hasServiceComplexityCustomEntries = count($serviceComplexityCustomEntries) > 0;
    $selectedSupportRequired = old('support_required_options', $draft['support_required_options'] ?? []);
    if (! is_array($selectedSupportRequired)) {
        $selectedSupportRequired = [];
    }
    $supportRequiredCustomEntries = old('support_required_custom');
    if (! is_array($supportRequiredCustomEntries)) {
        $supportRequiredCustomEntries = collect($draft['support_required_custom'] ?? ($draft['support_required'] ?? []))
            ->filter(fn ($value): bool => is_string($value) && Str::startsWith(trim((string) $value), ['Custom: ', 'Others: ']))
            ->map(function ($value): string {
                $clean = trim((string) $value);
                if (Str::startsWith($clean, 'Custom: ')) {
                    return trim(Str::after($clean, 'Custom: '));
                }

                return trim(Str::after($clean, 'Others: '));
            })
            ->filter(fn ($value): bool => $value !== '')
            ->values()
            ->all();
    }
    $supportRequiredCustomEntries = collect($supportRequiredCustomEntries)
        ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
        ->map(fn ($value): string => trim((string) $value))
        ->unique()
        ->values()
        ->all();
    $hasSupportRequiredCustomEntries = count($supportRequiredCustomEntries) > 0;
    $selectedOwner = collect($owners)->firstWhere('id', (int) old('owner_id', $defaultOwnerId)) ?: collect($owners)->first();
    $selectedOwnerId = (int) ($selectedOwner['id'] ?? $defaultOwnerId ?? 0);
    $selectedOwnerName = $selectedOwner['name'] ?? $ownerLabel ?? 'Select Owner';
    $selectedFinanceUserId = (int) old('assigned_finance_user_id', $draft['assigned_finance_user_id'] ?? 0);
    $currentUserName = auth()->user()->name ?? 'System';
    $draftDealCode = old('deal_code', $draft['deal_code'] ?? 'Auto-generated after save');
    $draftCreatedBy = old('created_by', $draft['created_by'] ?? $currentUserName);
    $draftCreatedAt = old('created_at_label', $draft['created_at_label'] ?? now()->format('F d, Y • h:i:s A'));
    $dealErrorMap = $errors->toArray();
    $dealErrorMessages = $errors->all();
    $dealErrorKeys = $errors->keys();
@endphp

<div id="createDealModal" class="fixed inset-0 z-[60] hidden" aria-hidden="true">
    <button id="createDealModalOverlay" type="button" aria-label="Close create deal panel" class="absolute inset-0 bg-slate-900/45 opacity-0 transition-opacity duration-300"></button>

    <div class="pointer-events-none absolute inset-y-0 right-0 flex w-full justify-end overflow-hidden">
        <div id="createDealPanel" class="pointer-events-auto flex h-full w-full max-w-[860px] translate-x-full flex-col border-l border-gray-200 bg-white shadow-2xl transition-transform duration-300 ease-out sm:max-w-[820px]">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5 sm:px-8">
                <div>
                    <h2 id="dealPanelTitle" class="text-2xl font-semibold text-gray-900">{{ $panelTitle }}</h2>
                    <p id="dealPanelSubtitle" class="mt-1 text-sm text-gray-500">{{ $panelSubtitle }}</p>
                </div>
                <button id="closeCreateDealModal" type="button" class="text-2xl leading-none text-gray-500 hover:text-gray-800">&times;</button>
            </div>

            <form id="createDealForm" method="POST" action="{{ $formAction }}" class="flex min-h-0 flex-1 flex-col">
                @csrf
                @if ($formMethod !== 'POST')
                    @method($formMethod)
                @endif
                <input id="deal_selected_owner_id" type="hidden" name="owner_id" value="{{ old('owner_id', $selectedOwnerId) }}">
                <input id="deal_selected_contact_id" type="hidden" name="contact_id" value="{{ old('contact_id', $draft['contact_id'] ?? '') }}">

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-6 sm:px-8">
                    @if ($errors->any())
                        <div id="dealFormErrorSummary" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert" tabindex="-1">
                            <p class="font-semibold">Please review the highlighted fields before continuing.</p>
                            <p class="mt-1 text-xs text-red-600">{{ count($dealErrorMessages) }} {{ \Illuminate\Support\Str::plural('issue', count($dealErrorMessages)) }} need attention.</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs text-red-700">
                                @foreach (collect($dealErrorMessages)->take(6) as $errorMessage)
                                    <li>{{ $errorMessage }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="flex flex-col gap-3 border-b border-gray-100 pb-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm font-medium text-gray-500">Consulting & Deal Form</p>
                        <div class="relative sm:flex-shrink-0">
                            <button id="dealOwnerDropdownTrigger" type="button" class="inline-flex h-10 w-full items-center justify-between gap-2 rounded-full border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-700 hover:bg-gray-100 sm:w-auto">
                                <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                <span id="dealOwnerSelectedLabel">Owner: {{ $selectedOwnerName }}</span>
                                <i class="fas fa-chevron-down text-[10px] text-gray-400"></i>
                            </button>
                            <div id="dealOwnerDropdownMenu" class="absolute right-0 z-20 mt-2 hidden w-full min-w-0 rounded-xl border border-gray-200 bg-white p-2 shadow-lg sm:w-72">
                                <div class="relative mb-2">
                                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400"></i>
                                    <input id="dealOwnerSearch" type="text" placeholder="Search owner..." class="h-9 w-full rounded-lg border border-gray-200 pl-8 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                </div>
                                <div class="max-h-56 space-y-1 overflow-y-auto">
                                    @foreach ($owners as $owner)
                                        @php
                                            $ownerInitials = strtoupper(collect(explode(' ', trim($owner['name'])))
                                                ->filter()
                                                ->map(fn ($segment) => mb_substr($segment, 0, 1))
                                                ->take(2)
                                                ->implode(''));
                                        @endphp
                                        <button type="button" class="deal-owner-option flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-sm text-gray-700 hover:bg-gray-50" data-owner-id="{{ $owner['id'] }}" data-owner-name="{{ $owner['name'] }}" data-owner-email="{{ $owner['email'] }}">
                                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-[10px] font-semibold text-blue-700">{{ $ownerInitials }}</span>
                                            <span>
                                                <span class="block text-sm text-gray-700">{{ $owner['name'] }}</span>
                                                <span class="block text-xs text-gray-500">{{ $owner['email'] }}</span>
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <section class="rounded-2xl border border-gray-200 p-4">
                        <h3 class="text-base font-semibold text-gray-900">Customer Type</h3>
                        <div class="mt-3 space-y-3">
                            <div class="grid items-center gap-2 sm:grid-cols-[72px_1fr]">
                                <label class="text-sm font-medium text-gray-700">Type</label>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach (['business' => 'Business', 'individual' => 'Individual'] as $value => $label)
                                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                            <input type="radio" name="customer_type" value="{{ $value }}" @checked(old('customer_type', $draft['customer_type'] ?? '') === $value) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 p-4">
                        <h3 class="text-base font-semibold text-gray-900">Deal Information</h3>
                        <p class="mb-4 text-xs text-gray-500">Auto-generated metadata appears here after the deal is saved.</p>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Deal Code</p>
                                <p id="deal_info_code" class="mt-1 text-sm text-gray-700">{{ $draftDealCode }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Created By</p>
                                <p id="deal_info_created_by" class="mt-1 text-sm text-gray-700">{{ $draftCreatedBy }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Created At</p>
                                <p id="deal_info_created_at" class="mt-1 text-sm text-gray-700">{{ $draftCreatedAt }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 p-4">
                    <h3 id="dealSelectionSectionTitle" class="text-base font-semibold text-gray-900">Select Existing Contact / Client</h3>
                        <p id="dealSearchHelpText" class="mb-4 text-xs text-gray-500">Select a customer type, then search the matching records.</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="relative sm:col-span-2">
                                <label id="dealContactSearchLabel" for="dealContactSearch" class="mb-1 block text-sm font-medium text-gray-700">Search Existing Client</label>
                                <i class="fas fa-search pointer-events-none absolute left-3 top-[42px] text-xs text-gray-400"></i>
                                <input id="dealContactSearch" type="text" placeholder="Type name, company, email, or mobile..." class="h-10 w-full rounded-lg border border-gray-300 pl-8 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <div id="dealContactResults" class="absolute left-0 right-0 z-20 mt-1 hidden max-h-56 overflow-y-auto rounded-lg border border-gray-200 bg-white p-1 shadow-lg"></div>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Deal Title</label>
                                <div class="flex h-10 items-center rounded-lg border border-gray-300 bg-gray-50 px-3 text-sm text-gray-500">
                                    CONDEAL-YYYY-###
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Auto-generated and saved by the backend when the deal is created.</p>
                                <input id="deal_name" name="deal_name" type="hidden" value="{{ old('deal_name', $draft['deal_name'] ?? '') }}">
                            </div>
                            <div>
                                <label for="stage" class="mb-1 block text-sm font-medium text-gray-700">Pipeline Stage</label>
                                <select id="stage" name="stage" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                    @foreach ($stageOptions as $stage)
                                        <option value="{{ $stage }}" @selected(old('stage', $draft['stage'] ?? 'Inquiry') === $stage)>{{ $stage }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <p id="dealContactRequiredMessage" class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700">Contact selection is required before completing the rest of the deal form.</p>
                    </section>

                    <div id="dealDependentSections" class="space-y-5">
                        <section class="rounded-2xl border border-gray-200 p-4">
                            <h3 class="text-base font-semibold text-gray-900">Contact Information</h3>
                            <p class="mb-4 text-xs text-gray-500">Fields auto-fill from selected contact and remain editable.</p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="deal_salutation" class="mb-1 block text-sm font-medium text-gray-700">Salutation</label>
                                    <select id="deal_salutation" name="salutation" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                        <option value="">Select salutation</option>
                                        <option value="-" @selected(old('salutation', $draft['salutation'] ?? '') === '-')>-</option>
                                        @foreach (['Mr.', 'Ms.', 'Mrs.', 'Atty.', 'CPA', 'Engr.', 'Dr.'] as $option)
                                            <option value="{{ $option }}" @selected(old('salutation', $draft['salutation'] ?? '') === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="deal_sex" class="mb-1 block text-sm font-medium text-gray-700">Sex</label>
                                    <select id="deal_sex" name="sex" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                        <option value="">Select sex</option>
                                        <option value="-" @selected(old('sex', $draft['sex'] ?? '') === '-')>-</option>
                                        @foreach (['Male', 'Female', 'Prefer not to say'] as $option)
                                            <option value="{{ $option }}" @selected(old('sex', $draft['sex'] ?? '') === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div><label for="deal_first_name" class="mb-1 block text-sm font-medium text-gray-700">First Name</label><input id="deal_first_name" name="first_name" value="{{ old('first_name', $draft['first_name'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="deal_middle_initial" class="mb-1 block text-sm font-medium text-gray-700">Middle Initial</label><input id="deal_middle_initial" name="middle_initial" value="{{ old('middle_initial', $draft['middle_initial'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="deal_last_name" class="mb-1 block text-sm font-medium text-gray-700">Last Name</label><input id="deal_last_name" name="last_name" value="{{ old('last_name', $draft['last_name'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="deal_name_extension" class="mb-1 block text-sm font-medium text-gray-700">Name Extension</label><input id="deal_name_extension" name="name_extension" value="{{ old('name_extension', $draft['name_extension'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="deal_date_of_birth" class="mb-1 block text-sm font-medium text-gray-700">Date of Birth</label><input id="deal_date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', $draft['date_of_birth'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="deal_email" class="mb-1 block text-sm font-medium text-gray-700">Email Address</label><input id="deal_email" type="email" name="email" value="{{ old('email', $draft['email'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="deal_mobile" class="mb-1 block text-sm font-medium text-gray-700">Mobile Number</label><input id="deal_mobile" name="mobile" value="{{ old('mobile', $draft['mobile'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div class="sm:col-span-2"><label for="deal_address" class="mb-1 block text-sm font-medium text-gray-700">Address</label><textarea id="deal_address" name="address" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('address', $draft['address'] ?? '') }}</textarea></div>
                                <div><label for="deal_company_name" class="mb-1 block text-sm font-medium text-gray-700">Company</label><input id="deal_company_name" name="company_name" value="{{ old('company_name', $draft['company_name'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="deal_position" class="mb-1 block text-sm font-medium text-gray-700">Position / Designation</label><input id="deal_position" name="position" value="{{ old('position', $draft['position'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div class="sm:col-span-2"><label for="deal_company_address" class="mb-1 block text-sm font-medium text-gray-700">Company Address</label><textarea id="deal_company_address" name="company_address" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('company_address', $draft['company_address'] ?? '') }}</textarea></div>
                            </div>
                        </section>
                        <section class="rounded-2xl border border-gray-200 p-4">
                            <h3 class="text-base font-semibold text-gray-900">Service Identification</h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700">Service Area</label>
                                    <div id="service-area-options-grid" class="grid gap-2 sm:grid-cols-2">
                                        @foreach ($serviceAreaOptions as $option)
                                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                                <input type="checkbox" name="service_area_options[]" value="{{ $option }}" @checked(in_array($option, $selectedServiceAreas, true)) {{ $option === 'Others' ? 'data-other-target=service-area-other-wrapper' : '' }} class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <span>{{ $option }}</span>
                                            </label>
                                        @endforeach
                                        @foreach ($serviceAreaOtherEntries as $item)
                                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700" data-custom-option>
                                                <input type="checkbox" name="service_area_options[]" value="{{ $item }}" checked class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <span class="flex-1">{{ $item }}</span>
                                                <button type="button" class="text-gray-500 hover:text-gray-700" data-custom-option-remove>&times;</button>
                                                <input type="hidden" name="service_area_other[]" value="{{ $item }}" data-custom-option-hidden>
                                            </label>
                                        @endforeach
                                    </div>
                                    <div id="service-area-other-wrapper" class="other-wrapper {{ (in_array('Others', $selectedServiceAreas, true) || count($serviceAreaOtherEntries) > 0) ? '' : 'hidden' }} mt-2">
                                        <input id="service-area-other-input" type="text" class="other-input h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" data-name="service_area_other[]" placeholder="Enter custom service area and press Enter">
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <label class="block text-sm font-medium text-gray-700">Services</label>
                                    <div id="dealServicesEmptyState" class="rounded-xl border border-dashed border-gray-200 bg-gray-50/60 p-4 text-sm text-gray-500 {{ count($selectedServiceAreas) > 0 ? 'hidden' : '' }}">
                                        Select a service area first to show matching services.
                                    </div>
                                    <div id="dealServicesGrid" class="grid gap-4 lg:grid-cols-2 {{ count($selectedServiceAreas) > 0 ? '' : 'hidden' }}">
                                        @foreach ($serviceGroups as $group => $options)
                                            <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-3 {{ in_array($group, $selectedServiceAreas, true) ? '' : 'hidden' }}" data-service-group="{{ $group }}">
                                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-600">{{ $group }}</p>
                                                <div class="space-y-2">
                                                    @foreach ($options as $option)
                                                        <label class="flex items-start gap-2 text-sm text-gray-700">
                                                            <input type="checkbox" name="service_options[]" value="{{ $option }}" data-service-group-option="{{ $group }}" @checked(in_array($option, $selectedServices, true)) class="mt-0.5 h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                                            <span>{{ $option }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div id="services-custom-options" class="grid gap-2 sm:grid-cols-2">
                                        @foreach ($serviceIdentificationCustomEntries as $customEntry)
                                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700" data-custom-option>
                                                <input type="checkbox" name="service_options[]" value="{{ $customEntry }}" checked class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <span class="flex-1">{{ $customEntry }}</span>
                                                <button type="button" class="text-gray-500 hover:text-gray-700" data-custom-option-remove>&times;</button>
                                                <input type="hidden" name="services_other[]" value="{{ $customEntry }}" data-custom-option-hidden>
                                            </label>
                                        @endforeach
                                    </div>
                                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                        <input id="services-other-toggle" type="checkbox" class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500" {{ $showServiceIdentificationCustomEntries ? 'checked' : '' }}>
                                        <span>Others</span>
                                    </label>
                                    <div id="services-other-wrapper" class="other-wrapper {{ $showServiceIdentificationCustomEntries ? '' : 'hidden' }} mt-2">
                                        <input id="services-other-input" type="text" class="other-input h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" data-name="services_other[]" placeholder="Enter custom service and press Enter">
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-gray-200 p-4">
                            <h3 class="text-base font-semibold text-gray-900">Products</h3>
                            <p id="dealProductsHelpText" class="mt-1 text-xs text-gray-500">Select a service area first to show the matching products offered.</p>
                            <div id="dealProductsEmptyState" class="mt-3 rounded-xl border border-dashed border-gray-200 bg-gray-50/60 p-4 text-sm text-gray-500 {{ count(array_intersect($selectedServiceAreas, array_keys($productOptionsByServiceArea))) > 0 ? 'hidden' : '' }}">
                                Select a matching service area first to show the available products.
                            </div>
                            <div id="product-options-grid" class="mt-3 grid gap-4">
                                @foreach ($productOptionsByServiceArea as $serviceArea => $options)
                                    <div class="{{ in_array($serviceArea, $selectedServiceAreas, true) ? '' : 'hidden' }}" data-product-group="{{ $serviceArea }}">
                                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-600">{{ $serviceArea }}</p>
                                        <div class="grid gap-2 sm:grid-cols-2">
                                            @foreach ($options as $option)
                                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700" data-product-option data-service-area-product="{{ $serviceArea }}" data-product-value="{{ $option }}">
                                                <input type="checkbox" name="product_options[]" value="{{ $option }}" @checked(in_array($option, $selectedProducts, true)) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <span>{{ $option }}</span>
                                            </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                                <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700" data-product-option data-product-value="Others">
                                    <input type="checkbox" name="product_options[]" value="Others" @checked(in_array('Others', $selectedProducts, true)) data-other-target="deal_products_other_wrap" class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span>Others</span>
                                </label>
                                @foreach ($selectedProductCustomEntries as $customEntry)
                                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700" data-custom-option>
                                        <input type="checkbox" name="product_options[]" value="{{ $customEntry }}" checked class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="flex-1">{{ $customEntry }}</span>
                                        <button type="button" class="text-gray-500 hover:text-gray-700" data-custom-option-remove>&times;</button>
                                        <input type="hidden" name="products_other_entries[]" value="{{ $customEntry }}" data-custom-option-hidden>
                                    </label>
                                @endforeach
                            </div>
                            <div id="deal_products_other_wrap" class="other-wrapper {{ (in_array('Others', $selectedProducts, true) || $hasCustomProductEntries) ? '' : 'hidden' }} mt-3">
                                <label class="mb-1 block text-sm font-medium text-gray-700">Others (Custom Product)</label>
                                <input id="products-other-input" type="text" class="other-input h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" data-name="products_other_entries[]" placeholder="Enter custom product and press Enter">
                            </div>
                        </section>

                        <section class="rounded-2xl border border-gray-200 p-4">
                            <h3 class="text-base font-semibold text-gray-900">Scope of Work</h3>
                            <p class="mb-2 text-xs text-gray-500">Describe the detailed scope of the engagement</p>
                            <div>
                                <label for="scope_of_work" class="mb-1 block text-sm font-medium text-gray-700">Scope of Work</label>
                                <textarea id="scope_of_work" name="scope_of_work" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('scope_of_work', $draft['scope_of_work'] ?? '') }}</textarea>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-gray-200 p-4">
                            <h3 class="text-base font-semibold text-gray-900">Engagement Type</h3>
                            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                                @foreach (['Project Engagement', 'Regular (Retainer) Engagement', 'Hybrid Engagement'] as $option)
                                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                        <input type="radio" name="engagement_type" value="{{ $option }}" @checked(old('engagement_type', $draft['engagement_type'] ?? '') === $option) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span>{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </section>

                        <section class="rounded-2xl border border-gray-200 p-4">
                            <h3 class="text-base font-semibold text-gray-900">Client Requirements</h3>
                            <div class="mt-3 overflow-x-auto">
                                <table id="clientRequirementsTable" class="min-w-full border border-gray-200 text-sm">
                                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                                        <tr>
                                            <th class="border border-gray-200 px-3 py-2 text-left">Requirement</th>
                                            <th class="border border-gray-200 px-3 py-2 text-center">Provided</th>
                                            <th class="border border-gray-200 px-3 py-2 text-center">Pending</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($requirementRows as $key => $label)
                                            @php
                                                $current = old(
                                                    "requirements_status.$key",
                                                    data_get($draft, "requirements_status_map.$key", data_get($draft, "requirements_status.$key"))
                                                );
                                                if ($key === 'others' && ! in_array($current, ['provided', 'pending'], true)) {
                                                    $current = $clientRequirementsOthersStatus;
                                                }
                                            @endphp
                                            @if ($key === 'others')
                                                <tr data-client-requirement-row="{{ $key }}" data-client-others-row>
                                            @else
                                                <tr data-client-requirement-row="{{ $key }}">
                                            @endif
                                                <td class="border border-gray-200 px-3 py-2 text-gray-700">{{ $label }}</td>
                                                <td class="border border-gray-200 px-3 py-2 text-center">
                                                    <input type="radio" name="requirements_status[{{ $key }}]" value="provided" @checked($current === 'provided') class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500" data-client-requirement-status="{{ $key }}:provided" {{ $key === 'others' ? 'data-client-others-radio' : '' }}>
                                                </td>
                                                <td class="border border-gray-200 px-3 py-2 text-center">
                                                    <input type="radio" name="requirements_status[{{ $key }}]" value="pending" @checked($current === 'pending') class="h-4 w-4 border-gray-300 text-amber-600 focus:ring-amber-500" data-client-requirement-status="{{ $key }}:pending" {{ $key === 'others' ? 'data-client-others-radio' : '' }}>
                                                </td>
                                            </tr>
                                        @endforeach
                                        @foreach ($parsedClientRequirementCustomRows as $index => $customRow)
                                            <tr data-client-custom-row data-client-custom-label="{{ $customRow['label'] }}">
                                                <td class="border border-gray-200 px-3 py-2 text-gray-700">
                                                    Other: {{ $customRow['label'] }}
                                                    <input type="hidden" name="client_requirements_custom[]" value="{{ $customRow['raw'] }}" data-client-custom-hidden>
                                                    <button type="button" class="ml-2 text-xs text-gray-500 hover:text-gray-700" data-client-custom-remove>&times;</button>
                                                </td>
                                                <td class="border border-gray-200 px-3 py-2 text-center">
                                                    <input type="radio" name="client_requirements_custom_status_{{ $index }}" value="provided" @checked(($customRow['status'] ?? 'pending') === 'provided') class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500" data-client-custom-status="provided">
                                                </td>
                                                <td class="border border-gray-200 px-3 py-2 text-center">
                                                    <input type="radio" name="client_requirements_custom_status_{{ $index }}" value="pending" @checked(($customRow['status'] ?? 'pending') === 'pending') class="h-4 w-4 border-gray-300 text-amber-600 focus:ring-amber-500" data-client-custom-status="pending">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div id="client_requirements_container" class="{{ $showClientRequirementsCustomEntries ? '' : 'hidden' }} mt-3">
                                <input id="client_requirements_input" type="text" placeholder="Enter custom requirement and press Enter" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            </div>
                        </section>

                        <section class="rounded-2xl border border-gray-200 p-4">
                            <h3 class="text-base font-semibold text-gray-900">Required Actions</h3>
                            <div id="required_actions_grid" class="mt-3 grid gap-2 sm:grid-cols-2">
                                @foreach ($requiredActions as $option)
                                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                        <input type="checkbox" name="required_actions_options[]" value="{{ $option }}" @checked(in_array($option, $selectedRequiredActions, true)) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span>{{ $option }}</span>
                                    </label>
                                @endforeach
                                @foreach ($requiredActionsCustomEntries as $customEntry)
                                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700" data-custom-option>
                                        <input type="checkbox" name="required_actions_options[]" value="{{ $customEntry }}" checked class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="flex-1">{{ $customEntry }}</span>
                                        <button type="button" class="text-gray-500 hover:text-gray-700" data-custom-option-remove>&times;</button>
                                        <input type="hidden" name="required_actions_custom[]" value="{{ $customEntry }}" data-custom-option-hidden>
                                    </label>
                                @endforeach
                                <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                    <input id="required_actions_others" type="checkbox" class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500" {{ $showRequiredActionsCustomEntries ? 'checked' : '' }}>
                                    <span>Others</span>
                                </label>
                            </div>
                            <div id="required_actions_container" class="other-wrapper {{ $showRequiredActionsCustomEntries ? '' : 'hidden' }} mt-3">
                                <input id="required_actions_input" type="text" class="other-input h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" data-name="required_actions_custom[]" placeholder="Enter custom required action and press Enter">
                            </div>
                        </section>

                        <section class="rounded-2xl border border-gray-200 p-4">
                            <h3 class="text-base font-semibold text-gray-900">Fees</h3>
                            @php
                                $estimatedProfessionalFeeValue = old(
                                    'estimated_professional_fee',
                                    $draft['estimated_professional_fee'] ?? ''
                                );
                                $estimatedGovernmentFeeValue = old(
                                    'estimated_government_fee',
                                    old('estimated_government_fees', $draft['estimated_government_fee'] ?? ($draft['estimated_government_fees'] ?? ''))
                                );
                                $estimatedServiceSupportFeeValue = old(
                                    'estimated_service_support_fee',
                                    $draft['estimated_service_support_fee'] ?? ''
                                );
                                $dealDiscountValue = old(
                                    'deal_discount',
                                    $draft['deal_discount'] ?? ''
                                );
                                $totalEstimatedValue = old(
                                    'total_estimated_value',
                                    old('total_estimated_engagement_value', $draft['total_estimated_value'] ?? ($draft['total_estimated_engagement_value'] ?? ''))
                                );
                                $otherFees = old('other_fees_titles') !== null || old('other_fees_amounts') !== null
                                    ? null
                                    : collect(data_get($draft, 'other_fees', []))
                                        ->filter(fn ($fee) => is_array($fee))
                                        ->values();
                                $otherFeesTitles = old(
                                    'other_fees_titles',
                                    $otherFees?->map(fn ($fee) => $fee['title'] ?? '')->all() ?? data_get($draft, 'other_fees_titles', [])
                                );
                                $otherFeesAmounts = old(
                                    'other_fees_amounts',
                                    $otherFees?->map(fn ($fee) => $fee['amount'] ?? '')->all() ?? data_get($draft, 'other_fees_amounts', [])
                                );
                                $otherFeesRowCount = max(count((array) $otherFeesTitles), count((array) $otherFeesAmounts));
                            @endphp
                            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                <div><label for="estimated_professional_fee" class="mb-1 block text-sm font-medium text-gray-700">Estimated Professional Fee</label><div class="relative"><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">P</span><input id="estimated_professional_fee" name="estimated_professional_fee" inputmode="decimal" value="{{ $numericFieldValue($estimatedProfessionalFeeValue) }}" class="h-10 w-full rounded-lg border border-gray-300 pl-8 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div></div>
                                <div><label for="estimated_government_fees" class="mb-1 block text-sm font-medium text-gray-700">Estimated Government Fees</label><div class="relative"><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">P</span><input id="estimated_government_fees" name="estimated_government_fees" inputmode="decimal" value="{{ $numericFieldValue($estimatedGovernmentFeeValue) }}" class="h-10 w-full rounded-lg border border-gray-300 pl-8 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div></div>
                                <div><label for="estimated_service_support_fee" class="mb-1 block text-sm font-medium text-gray-700">Estimated Service Support Fee</label><div class="relative"><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">P</span><input id="estimated_service_support_fee" name="estimated_service_support_fee" inputmode="decimal" value="{{ $numericFieldValue($estimatedServiceSupportFeeValue) }}" class="h-10 w-full rounded-lg border border-gray-300 pl-8 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div></div>
                                <div><label for="total_service_fee" class="mb-1 block text-sm font-medium text-gray-700">Total Service Fee</label><div class="relative"><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">P</span><input id="total_service_fee" name="total_service_fee" value="{{ $numericFieldValue(old('total_service_fee', $draft['total_service_fee'] ?? '')) }}" readonly class="h-10 w-full rounded-lg border border-gray-300 bg-gray-50 pl-8 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div></div>
                                <div><label for="total_product_fee" class="mb-1 block text-sm font-medium text-gray-700">Total Product Fee</label><div class="relative"><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">P</span><input id="total_product_fee" name="total_product_fee" value="{{ $numericFieldValue(old('total_product_fee', $draft['total_product_fee'] ?? '')) }}" readonly class="h-10 w-full rounded-lg border border-gray-300 bg-gray-50 pl-8 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div></div>
                                <div><label for="deal_discount" class="mb-1 block text-sm font-medium text-gray-700">Discount</label><div class="relative"><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">P</span><input id="deal_discount" name="deal_discount" inputmode="decimal" value="{{ $numericFieldValue($dealDiscountValue) }}" class="h-10 w-full rounded-lg border border-gray-300 pl-8 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div></div>
                                <div><label for="total_estimated_engagement_value" class="mb-1 block text-sm font-medium text-gray-700">Total Estimated Engagement Value</label><div class="relative"><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">P</span><input id="total_estimated_engagement_value" name="total_estimated_engagement_value" value="{{ $numericFieldValue($totalEstimatedValue) }}" class="h-10 w-full rounded-lg border border-gray-300 bg-gray-50 pl-8 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div></div>
                            </div>
                            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                <div class="overflow-hidden rounded-xl border border-gray-200">
                                    <div class="border-b border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-700">Services Pricing Guide</div>
                                    <table class="w-full text-sm">
                                        <thead class="bg-white text-xs uppercase tracking-wide text-gray-500">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-semibold">Service</th>
                                                <th class="px-3 py-2 text-right font-semibold">Price</th>
                                            </tr>
                                        </thead>
                                        <tbody id="selectedServicesPricingRows" class="divide-y divide-gray-100">
                                            <tr data-empty-row><td colspan="2" class="px-3 py-3 text-center text-xs text-gray-500">Select a service area to show service prices.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="overflow-hidden rounded-xl border border-gray-200">
                                    <div class="border-b border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-700">Products Pricing Guide</div>
                                    <table class="w-full text-sm">
                                        <thead class="bg-white text-xs uppercase tracking-wide text-gray-500">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-semibold">Product</th>
                                                <th class="px-3 py-2 text-right font-semibold">Price</th>
                                            </tr>
                                        </thead>
                                        <tbody id="selectedProductsPricingRows" class="divide-y divide-gray-100">
                                            <tr data-empty-row><td colspan="2" class="px-3 py-3 text-center text-xs text-gray-500">Select a matching service area to show product prices.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div id="otherFeesRows" class="mt-3 space-y-3">
                                @for ($i = 0; $i < $otherFeesRowCount; $i++)
                                    <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]" data-other-fee-row>
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-gray-700">Fee Title</label>
                                            <input name="other_fees_titles[]" value="{{ $otherFeesTitles[$i] ?? '' }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" data-other-fee-title>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-gray-700">Fee Amount</label>
                                            <div class="relative"><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">P</span><input name="other_fees_amounts[]" inputmode="decimal" value="{{ $numericFieldValue($otherFeesAmounts[$i] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 pl-8 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" data-other-fee-amount></div>
                                        </div>
                                        <div class="flex items-end">
                                            <button type="button" class="h-10 rounded-lg border border-gray-300 px-3 text-sm text-gray-700 hover:bg-gray-50" data-other-fee-remove>&times;</button>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                            <div class="mt-3">
                                <button id="addOtherFeeBtn" type="button" class="h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Add Other Fee</button>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-gray-200 p-4">
                            <h3 class="text-base font-semibold text-gray-900">Payment Terms</h3>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                @foreach (['Full Payment Before Service', '50% Downpayment / 50% Completion', 'Milestone-Based Payment', 'Monthly Retainer', 'Others'] as $option)
                                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                        <input type="radio" name="payment_terms" value="{{ $option }}" @checked(old('payment_terms', $draft['payment_terms'] ?? '') === $option) {{ $option === 'Others' ? 'data-other-target=deal_payment_terms_other_wrap' : '' }} class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span>{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div id="deal_payment_terms_other_wrap" class="{{ old('payment_terms', $draft['payment_terms'] ?? '') === 'Others' || $hasPaymentTermsCustomEntries ? '' : 'hidden' }} mt-3">
                                <div id="payment_terms_container">
                                    <input id="payment_terms_input" type="text" placeholder="Enter custom payment term and press Enter" class="other-input h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" data-name="payment_terms_custom[]">
                                    <div id="payment_terms_tags" class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($paymentTermsCustomEntries as $customEntry)
                                            <span class="custom-tag inline-flex items-center gap-1 rounded-full border border-gray-200 bg-gray-100 px-2 py-1 text-xs text-gray-700" data-tag-item>
                                                <span>{{ $customEntry }}</span>
                                                <button type="button" class="remove-tag text-gray-500 hover:text-gray-700" data-tag-remove>&times;</button>
                                                <input type="hidden" name="payment_terms_custom[]" value="{{ $customEntry }}">
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </section>
                        <section class="rounded-2xl border border-gray-200 p-4">
                            <h3 class="text-base font-semibold text-gray-900">Estimated Timeline</h3>
                            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                <div><label for="planned_start_date" class="mb-1 block text-sm font-medium text-gray-700">Planned Start Date</label><input id="planned_start_date" type="date" name="planned_start_date" value="{{ old('planned_start_date', $draft['planned_start_date'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="estimated_duration" class="mb-1 block text-sm font-medium text-gray-700">Estimated Duration (Days)</label><input id="estimated_duration" name="estimated_duration" value="{{ old('estimated_duration', $draft['estimated_duration'] ?? '') }}" readonly class="h-10 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 text-sm text-gray-600 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="estimated_completion_date" class="mb-1 block text-sm font-medium text-gray-700">Estimated Completion Date</label><input id="estimated_completion_date" type="date" name="estimated_completion_date" value="{{ old('estimated_completion_date', $draft['estimated_completion_date'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="client_preferred_completion_date" class="mb-1 block text-sm font-medium text-gray-700">Client Preferred Completion Date</label><input id="client_preferred_completion_date" type="date" name="client_preferred_completion_date" value="{{ old('client_preferred_completion_date', $draft['client_preferred_completion_date'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="confirmed_delivery_date" class="mb-1 block text-sm font-medium text-gray-700">Confirmed Delivery Date</label><input id="confirmed_delivery_date" type="date" name="confirmed_delivery_date" value="{{ old('confirmed_delivery_date', $draft['confirmed_delivery_date'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div class="sm:col-span-2"><label for="timeline_notes" class="mb-1 block text-sm font-medium text-gray-700">Timeline Notes</label><textarea id="timeline_notes" name="timeline_notes" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('timeline_notes', $draft['timeline_notes'] ?? '') }}</textarea></div>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-gray-200 p-4">
                            <h3 class="text-base font-semibold text-gray-900">Service Complexity Assessment</h3>
                            <div id="service_complexity_grid" class="mt-3 grid gap-3 sm:grid-cols-2">
                                @foreach ($defaultServiceComplexityOptions as $option)
                                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                        <input type="radio" name="service_complexity" value="{{ $option }}" @checked($selectedServiceComplexity === $option) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span>{{ $option }}</span>
                                    </label>
                                @endforeach
                                @foreach ($serviceComplexityCustomEntries as $customEntry)
                                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700" data-complexity-custom-option>
                                        <input type="radio" name="service_complexity" value="{{ $customEntry }}" @checked($selectedServiceComplexity === $customEntry) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="flex-1">{{ $customEntry }}</span>
                                        <button type="button" class="text-gray-500 hover:text-gray-700" data-custom-radio-remove>&times;</button>
                                        <input type="hidden" name="service_complexity_custom[]" value="{{ $customEntry }}" data-complexity-custom-hidden>
                                    </label>
                                @endforeach
                                <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                    <input id="service_complexity_others" type="checkbox" class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500" {{ $hasServiceComplexityCustomEntries ? 'checked' : '' }}>
                                    <span>Others</span>
                                </label>
                            </div>
                            <div id="service_complexity_container" class="other-wrapper {{ $hasServiceComplexityCustomEntries ? '' : 'hidden' }} mt-3">
                                <input id="service_complexity_input" type="text" class="other-input h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" data-name="service_complexity_custom[]" placeholder="Enter custom service complexity and press Enter">
                            </div>
                            <label class="mt-4 mb-2 block text-sm font-medium text-gray-700">Professional Support Required</label>
                            <div id="support_required_options_grid" class="grid gap-2 sm:grid-cols-2">
                                @foreach (['Requires Senior Consultant', 'Requires Subject Matter Expert', 'Requires Lawyer / Legal Counsel', 'Requires CPA / Certified Public Accountant'] as $option)
                                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                        <input type="checkbox" name="support_required_options[]" value="{{ $option }}" @checked(in_array($option, $selectedSupportRequired, true)) class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span>{{ $option }}</span>
                                    </label>
                                @endforeach
                                @foreach ($supportRequiredCustomEntries as $customEntry)
                                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700" data-support-custom-option>
                                        <input type="checkbox" name="support_required_options[]" value="{{ $customEntry }}" checked class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="flex-1">{{ $customEntry }}</span>
                                        <button type="button" class="text-gray-500 hover:text-gray-700" data-custom-option-remove>&times;</button>
                                        <input type="hidden" name="support_required_custom[]" value="{{ $customEntry }}" data-support-custom-hidden>
                                    </label>
                                @endforeach
                                <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                    <input id="support_required_others" type="checkbox" class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500" {{ $hasSupportRequiredCustomEntries ? 'checked' : '' }}>
                                    <span>Others</span>
                                </label>
                            </div>
                            <div id="support_required_container" class="other-wrapper {{ $hasSupportRequiredCustomEntries ? '' : 'hidden' }} mt-3">
                                <input id="support_required_input" type="text" class="other-input h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" data-name="support_required_custom[]" placeholder="Enter custom support requirement and press Enter">
                            </div>
                            <div class="mt-3">
                                <label for="complexity_notes" class="mb-1 block text-sm font-medium text-gray-700">Notes / Explanation</label>
                                <textarea id="complexity_notes" name="complexity_notes" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('complexity_notes', $draft['complexity_notes'] ?? '') }}</textarea>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-gray-200 p-4">
                            <h3 class="text-base font-semibold text-gray-900">Proposal Decision</h3>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                @foreach (['Prepare Proposal', 'Prepare Engagement Letter', 'Schedule Client Consultation', 'Request Additional Documents', 'Decline Engagement'] as $option)
                                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                        <input type="radio" name="proposal_decision" value="{{ $option }}" @checked(old('proposal_decision', $draft['proposal_decision'] ?? '') === $option) {{ $option === 'Decline Engagement' ? 'data-other-target=deal_decline_reason_wrap' : '' }} class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span>{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div id="deal_decline_reason_wrap" class="{{ old('proposal_decision', $draft['proposal_decision'] ?? '') === 'Decline Engagement' ? '' : 'hidden' }} mt-3">
                                <label for="decline_reason" class="mb-1 block text-sm font-medium text-gray-700">Reason (if declined)</label>
                                <textarea id="decline_reason" name="decline_reason" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('decline_reason', $draft['decline_reason'] ?? '') }}</textarea>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-gray-200 p-4">
                            <h3 class="text-base font-semibold text-gray-900">Internal Assignment</h3>
                            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                <div><label for="assigned_consultant" class="mb-1 block text-sm font-medium text-gray-700">Assigned Consultant</label><input id="assigned_consultant" name="assigned_consultant" list="deal_employee_options" value="{{ old('assigned_consultant', $draft['assigned_consultant'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="assigned_associate" class="mb-1 block text-sm font-medium text-gray-700">Assigned Associate</label><input id="assigned_associate" name="assigned_associate" list="deal_employee_options" value="{{ old('assigned_associate', $draft['assigned_associate'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div class="sm:col-span-2"><label for="service_department_unit" class="mb-1 block text-sm font-medium text-gray-700">Service Department / Unit</label><input id="service_department_unit" name="service_department_unit" value="{{ old('service_department_unit', $draft['service_department_unit'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-gray-200 p-4">
                            <h3 class="text-base font-semibold text-gray-900">Notes</h3>
                            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                <div><label for="consultant_notes" class="mb-1 block text-sm font-medium text-gray-700">Consultant Notes</label><textarea id="consultant_notes" name="consultant_notes" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('consultant_notes', $draft['consultant_notes'] ?? '') }}</textarea></div>
                                <div><label for="associate_notes" class="mb-1 block text-sm font-medium text-gray-700">Associate Notes</label><textarea id="associate_notes" name="associate_notes" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('associate_notes', $draft['associate_notes'] ?? '') }}</textarea></div>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-gray-200 p-4">
                            <h3 class="text-base font-semibold text-gray-900">Internal Approval</h3>
                            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                <div><label for="prepared_by" class="mb-1 block text-sm font-medium text-gray-700">Prepared By</label><input id="prepared_by" name="prepared_by" list="deal_employee_options" value="{{ old('prepared_by', $draft['prepared_by'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="reviewed_by" class="mb-1 block text-sm font-medium text-gray-700">Reviewed By</label><input id="reviewed_by" name="reviewed_by" list="deal_employee_options" value="{{ old('reviewed_by', $draft['reviewed_by'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="internal_name" class="mb-1 block text-sm font-medium text-gray-700">Name</label><input id="internal_name" name="internal_name" list="deal_employee_options" value="{{ old('internal_name', $draft['internal_name'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="internal_date" class="mb-1 block text-sm font-medium text-gray-700">Date</label><input id="internal_date" type="date" name="internal_date" value="{{ old('internal_date', $draft['internal_date'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div class="sm:col-span-2"><label for="client_fullname_signature" class="mb-1 block text-sm font-medium text-gray-700">Client Fullname & Signature</label><input id="client_fullname_signature" name="client_fullname_signature" value="{{ old('client_fullname_signature', $draft['client_fullname_signature'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="referred_closed_by" class="mb-1 block text-sm font-medium text-gray-700">Referred By / Closed By</label><input id="referred_closed_by" name="referred_closed_by" list="deal_employee_options" value="{{ old('referred_closed_by', $draft['referred_closed_by'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="internal_sales_marketing" class="mb-1 block text-sm font-medium text-gray-700">Sales & Marketing</label><input id="internal_sales_marketing" name="internal_sales_marketing" list="deal_employee_options" value="{{ old('internal_sales_marketing', $draft['internal_sales_marketing'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="lead_consultant" class="mb-1 block text-sm font-medium text-gray-700">Lead Consultant</label><input id="lead_consultant" name="lead_consultant" list="deal_employee_options" value="{{ old('lead_consultant', $draft['lead_consultant'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div><label for="lead_associate_assigned" class="mb-1 block text-sm font-medium text-gray-700">Lead Associate Assigned</label><input id="lead_associate_assigned" name="lead_associate_assigned" list="deal_employee_options" value="{{ old('lead_associate_assigned', $draft['lead_associate_assigned'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                                <div>
                                    <label for="assigned_finance_user_id" class="mb-1 block text-sm font-medium text-gray-700">Finance</label>
                                    <select id="assigned_finance_user_id" name="assigned_finance_user_id" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" onchange="this.form.querySelector('[name=internal_finance]').value = this.options[this.selectedIndex]?.dataset.name || ''">
                                        <option value="">Assign finance</option>
                                        @foreach ($financeUsers as $financeUser)
                                            <option value="{{ $financeUser['id'] }}" data-name="{{ $financeUser['name'] }}" @selected($selectedFinanceUserId === (int) $financeUser['id'])>
                                                {{ $financeUser['name'] }}{{ filled($financeUser['email'] ?? null) ? ' - '.$financeUser['email'] : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="internal_finance" value="{{ old('internal_finance', $draft['internal_finance'] ?? '') }}">
                                </div>
                                <div><label for="internal_president" class="mb-1 block text-sm font-medium text-gray-700">President</label><input id="internal_president" name="internal_president" list="deal_employee_options" value="{{ old('internal_president', $draft['internal_president'] ?? '') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
                            </div>
                        </section>
                    </div>

                </div>

                <div class="mt-auto flex items-center justify-end gap-3 border-t border-gray-100 bg-white px-6 py-4 sm:px-8">
                    <button id="cancelCreateDealModal" type="button" class="h-10 rounded-lg border border-gray-300 px-4 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button id="saveDealBtn" type="submit" class="h-10 rounded-lg bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700">{{ $submitLabel }}</button>
                </div>
            </form>
            <datalist id="deal_employee_options">
                @foreach ($employeeOptions as $employeeOption)
                    @php
                        $employeeMeta = collect([
                            $employeeOption['employee_code'] ?? null,
                            $employeeOption['position'] ?? null,
                            $employeeOption['department'] ?? null,
                            $employeeOption['email'] ?? null,
                        ])->filter()->implode(' - ');
                    @endphp
                    <option value="{{ $employeeOption['name'] ?? '' }}" label="{{ $employeeMeta }}"></option>
                @endforeach
            </datalist>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('createDealModal');
    const panel = document.getElementById('createDealPanel');
    const overlay = document.getElementById('createDealModalOverlay');
    const openBtn = document.getElementById('openCreateDealModalBtn');
    const closeBtn = document.getElementById('closeCreateDealModal');
    const cancelBtn = document.getElementById('cancelCreateDealModal');
    const ownerTrigger = document.getElementById('dealOwnerDropdownTrigger');
    const ownerMenu = document.getElementById('dealOwnerDropdownMenu');
    const ownerSearch = document.getElementById('dealOwnerSearch');
    const ownerInput = document.getElementById('deal_selected_owner_id');
    const ownerLabel = document.getElementById('dealOwnerSelectedLabel');
    const ownerOptions = Array.from(document.querySelectorAll('.deal-owner-option'));
    const contactSearch = document.getElementById('dealContactSearch');
    const contactResults = document.getElementById('dealContactResults');
    const contactIdInput = document.getElementById('deal_selected_contact_id');
    const contactSearchLabel = document.getElementById('dealContactSearchLabel');
    const contactSearchHelpText = document.getElementById('dealSearchHelpText');
    const dependentSections = document.getElementById('dealDependentSections');
    const requiredMessage = document.getElementById('dealContactRequiredMessage');
    const saveBtn = document.getElementById('saveDealBtn');
    const plannedStartDateInput = document.getElementById('planned_start_date');
    const confirmedDeliveryDateInput = document.getElementById('confirmed_delivery_date');
    const estimatedDurationInput = document.getElementById('estimated_duration');
    const feeInputs = [
        document.querySelector('[name="estimated_professional_fee"]'),
        document.querySelector('[name="estimated_government_fee"]') || document.querySelector('[name="estimated_government_fees"]'),
        document.querySelector('[name="estimated_service_support_fee"]'),
        document.querySelector('[name="total_service_fee"]'),
        document.querySelector('[name="total_product_fee"]'),
        document.querySelector('[name="deal_discount"]'),
    ].filter(Boolean);
    const totalInput = document.querySelector('[name="total_estimated_value"]') || document.querySelector('[name="total_estimated_engagement_value"]');
    const otherFeesRows = document.getElementById('otherFeesRows');
    const addOtherFeeBtn = document.getElementById('addOtherFeeBtn');
    const selectedServicesPricingRows = document.getElementById('selectedServicesPricingRows');
    const selectedProductsPricingRows = document.getElementById('selectedProductsPricingRows');
    const contactRecords = @json($contactRecords);
    const companyRecords = @json($companyRecords ?? []);
    const servicePricing = @json($servicePricing);
    const serviceRequirementCatalog = @json($serviceRequirementCatalog ?? []);
    const productPricing = @json($productPricing);
    const productOptionsByServiceArea = @json($productOptionsByServiceArea);
    const invalidFieldMessages = @json($dealErrorMap);
    const invalidFieldKeys = @json($dealErrorKeys);
    const shouldAutoOpenModal = @json($openDealModal || $errors->any());
    const customServicePrice = 2500;
    const customProductPrice = 350;

    const fieldMap = {
        salutation: 'deal_salutation',
        first_name: 'deal_first_name',
        middle_initial: 'deal_middle_initial',
        last_name: 'deal_last_name',
        name_extension: 'deal_name_extension',
        sex: 'deal_sex',
        date_of_birth: 'deal_date_of_birth',
        email: 'deal_email',
        mobile: 'deal_mobile',
        address: 'deal_address',
        company_name: 'deal_company_name',
        company_address: 'deal_company_address',
        position: 'deal_position',
    };

    const closeOwnerMenu = () => ownerMenu?.classList.add('hidden');

    const selectedCustomerType = () => document.querySelector('input[name="customer_type"]:checked')?.value || '';
    let selectedBusinessRecord = null;
    let selectedContactRecord = null;

    const markFieldInvalid = (field, message) => {
        if (!field) {
            return;
        }

        field.classList.remove('border-gray-300', 'focus:border-blue-500', 'focus:ring-blue-100');
        field.classList.add('border-red-400', 'bg-red-50', 'text-red-900', 'focus:border-red-500', 'focus:ring-red-100');
        field.setAttribute('aria-invalid', 'true');

        const wrapper = field.closest('div');
        if (!wrapper || wrapper.querySelector('[data-inline-error-for]')) {
            return;
        }

        const inlineError = document.createElement('p');
        inlineError.className = 'mt-1 text-xs text-red-600';
        inlineError.dataset.inlineErrorFor = field.name || field.id || 'field';
        inlineError.textContent = message;
        wrapper.appendChild(inlineError);
    };

    const applyValidationUi = () => {
        if (!Array.isArray(invalidFieldKeys) || invalidFieldKeys.length === 0) {
            return;
        }

        const firstInvalidField = [];

        invalidFieldKeys.forEach((key) => {
            const proxyTargets = {
                contact_id: contactSearch,
                owner_id: ownerTrigger,
            };
            const bracketName = key.split('.').reduce((carry, segment, index) => {
                if (index === 0) {
                    return segment;
                }

                return `${carry}[${segment}]`;
            }, '');
            const arrayBaseName = `${key.split('.')[0]}[]`;
            const candidates = [
                `[name="${key}"]`,
                `[name="${bracketName}"]`,
                `[name="${arrayBaseName}"]`,
                `[name^="${key.split('.')[0]}["]`,
                `[name="${key.split('.')[0]}"]`,
                `#${key}`,
            ];
            const field = proxyTargets[key] || document.querySelector(candidates.join(', '));

            if (!field) {
                return;
            }

            const fieldErrors = invalidFieldMessages[key];
            const message = Array.isArray(fieldErrors) && fieldErrors.length > 0
                ? fieldErrors[0]
                : (field.getAttribute('data-error-message') || 'Please review this field.');

            markFieldInvalid(field, message);

            if (firstInvalidField.length === 0) {
                firstInvalidField.push(field);
            }
        });

        const summary = document.getElementById('dealFormErrorSummary');
        const focusTarget = firstInvalidField[0] || summary;
        if (focusTarget) {
            focusTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (typeof focusTarget.focus === 'function') {
                focusTarget.focus({ preventScroll: true });
            }
        }
    };

    const openModal = () => {
        if (!modal || !panel) {
            return;
        }
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        document.dispatchEvent(new CustomEvent('deal-drawer:opened'));
        requestAnimationFrame(() => {
            overlay?.classList.remove('opacity-0');
            panel.classList.remove('translate-x-full');
            applyValidationUi();
        });
    };

    const closeModal = () => {
        if (!modal || !panel) {
            return;
        }
        closeOwnerMenu();
        contactResults?.classList.add('hidden');
        overlay?.classList.add('opacity-0');
        panel.classList.add('translate-x-full');
        document.body.classList.remove('overflow-hidden');
        document.dispatchEvent(new CustomEvent('deal-drawer:closed'));
        window.setTimeout(() => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
        }, 300);
    };

    const normalizeCurrency = (value) => {
        const cleaned = String(value || '')
            .replace(/,/g, '')
            .replace(/[^0-9.-]/g, '')
            .trim();
        const parsed = Number.parseFloat(cleaned);
        return Number.isNaN(parsed) ? 0 : parsed;
    };

    const parseDateValue = (value) => {
        if (!value) {
            return null;
        }

        const date = new Date(`${value}T00:00:00`);
        return Number.isNaN(date.getTime()) ? null : date;
    };

    const syncEstimatedDuration = () => {
        if (!estimatedDurationInput) {
            return;
        }

        const plannedStart = parseDateValue(plannedStartDateInput?.value);
        const confirmedDelivery = parseDateValue(confirmedDeliveryDateInput?.value);

        if (!plannedStart || !confirmedDelivery || confirmedDelivery < plannedStart) {
            estimatedDurationInput.value = '';
            return;
        }

        const millisecondsPerDay = 1000 * 60 * 60 * 24;
        const dayCount = Math.round((confirmedDelivery.getTime() - plannedStart.getTime()) / millisecondsPerDay) + 1;
        estimatedDurationInput.value = dayCount > 0 ? String(dayCount) : '';
    };

    const otherFeeAmountInputs = () => Array.from(document.querySelectorAll('[name="other_fees_amounts[]"]'));
    const serviceFeeInput = document.querySelector('[name="total_service_fee"]');
    const productFeeInput = document.querySelector('[name="total_product_fee"]');

    const createOtherFeeRow = (title = '', amount = '') => {
        const row = document.createElement('div');
        row.className = 'grid gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]';
        row.setAttribute('data-other-fee-row', '');

        const titleWrap = document.createElement('div');
        const titleLabel = document.createElement('label');
        titleLabel.className = 'mb-1 block text-sm font-medium text-gray-700';
        titleLabel.textContent = 'Fee Title';
        const titleInput = document.createElement('input');
        titleInput.name = 'other_fees_titles[]';
        titleInput.value = title;
        titleInput.className = 'h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100';
        titleInput.setAttribute('data-other-fee-title', '');
        titleWrap.append(titleLabel, titleInput);

        const amountWrap = document.createElement('div');
        const amountLabel = document.createElement('label');
        amountLabel.className = 'mb-1 block text-sm font-medium text-gray-700';
        amountLabel.textContent = 'Fee Amount';
        const amountFieldWrap = document.createElement('div');
        amountFieldWrap.className = 'relative';
        const amountPrefix = document.createElement('span');
        amountPrefix.className = 'pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500';
        amountPrefix.textContent = 'P';
        const amountInput = document.createElement('input');
        amountInput.name = 'other_fees_amounts[]';
        amountInput.value = amount;
        amountInput.inputMode = 'decimal';
        amountInput.className = 'h-10 w-full rounded-lg border border-gray-300 pl-8 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100';
        amountInput.setAttribute('data-other-fee-amount', '');
        amountFieldWrap.append(amountPrefix, amountInput);
        amountWrap.append(amountLabel, amountFieldWrap);

        const actionWrap = document.createElement('div');
        actionWrap.className = 'flex items-end';
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'h-10 rounded-lg border border-gray-300 px-3 text-sm text-gray-700 hover:bg-gray-50';
        removeButton.setAttribute('data-other-fee-remove', '');
        removeButton.innerHTML = '&times;';
        actionWrap.appendChild(removeButton);

        row.append(titleWrap, amountWrap, actionWrap);

        return row;
    };

    const formatCurrency = (value) => `P${Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;

    const renderPricingRows = (tbody, rows, emptyText) => {
        if (!tbody) {
            return;
        }

        if (rows.length === 0) {
            tbody.innerHTML = `<tr data-empty-row><td colspan="2" class="px-3 py-3 text-center text-xs text-gray-500">${emptyText}</td></tr>`;
            return;
        }

        tbody.replaceChildren(...rows.map((row) => {
            const tr = document.createElement('tr');
            const name = document.createElement('td');
            name.className = 'px-3 py-2 text-gray-700';
            name.textContent = row.name;
            const price = document.createElement('td');
            price.className = 'px-3 py-2 text-right font-medium text-gray-900';
            price.textContent = formatCurrency(row.price);
            tr.append(name, price);
            return tr;
        }));
    };

    const selectedServiceRows = () => {
        const selected = Array.from(document.querySelectorAll('input[name="service_options[]"]:checked'))
            .filter((input) => Object.prototype.hasOwnProperty.call(servicePricing, input.value))
            .map((input) => ({
                name: input.value,
                price: normalizeCurrency(servicePricing[input.value] ?? 0),
            }));
        const custom = Array.from(document.querySelectorAll('input[name="services_other[]"]'))
            .filter((input) => String(input.value || '').trim() !== '')
            .map((input) => ({
                name: input.value,
                price: customServicePrice,
            }));

        return [...selected, ...custom];
    };

    const selectedProductRows = () => {
        const selected = Array.from(document.querySelectorAll('input[name="product_options[]"]:checked'))
            .filter((input) => input.value !== 'Others')
            .filter((input) => Object.prototype.hasOwnProperty.call(productPricing, input.value))
            .map((input) => ({
                name: input.value,
                price: normalizeCurrency(productPricing[input.value] ?? 0),
            }));
        const custom = Array.from(document.querySelectorAll('input[name="products_other_entries[]"]'))
            .filter((input) => String(input.value || '').trim() !== '')
            .map((input) => ({
                name: input.value,
                price: customProductPrice,
            }));

        return [...selected, ...custom];
    };

    const serviceGuideRows = () => {
        const visibleCatalogRows = Array.from(document.querySelectorAll('input[name="service_options[]"][data-service-group-option]'))
            .filter((input) => !input.closest('[data-service-group]')?.classList.contains('hidden'))
            .map((input) => ({
                name: input.value,
                price: normalizeCurrency(servicePricing[input.value] ?? 0),
            }));
        const customRows = Array.from(document.querySelectorAll('input[name="services_other[]"]'))
            .filter((input) => String(input.value || '').trim() !== '')
            .map((input) => ({
                name: input.value,
                price: customServicePrice,
            }));

        return [...visibleCatalogRows, ...customRows];
    };

    const productGuideRows = () => {
        const visibleCatalogRows = Array.from(document.querySelectorAll('input[name="product_options[]"]'))
            .filter((input) => input.value !== 'Others')
            .filter((input) => Object.prototype.hasOwnProperty.call(productPricing, input.value))
            .filter((input) => !input.closest('[data-product-option]')?.classList.contains('hidden'))
            .map((input) => ({
                name: input.value,
                price: normalizeCurrency(productPricing[input.value] ?? 0),
            }));
        const customRows = Array.from(document.querySelectorAll('input[name="products_other_entries[]"]'))
            .filter((input) => String(input.value || '').trim() !== '')
            .map((input) => ({
                name: input.value,
                price: customProductPrice,
            }));

        return [...visibleCatalogRows, ...customRows];
    };

    const renderSelectedPricingGuides = () => {
        renderPricingRows(selectedServicesPricingRows, serviceGuideRows(), 'Select a service area to show service prices.');
        renderPricingRows(selectedProductsPricingRows, productGuideRows(), 'Select a matching service area to show product prices.');
    };

    const recalculateSectionTotals = () => {
        const selectedServices = selectedServiceRows().reduce((sum, row) => sum + normalizeCurrency(row.price), 0);
        const selectedProducts = selectedProductRows().reduce((sum, row) => sum + normalizeCurrency(row.price), 0);

        if (serviceFeeInput) {
            serviceFeeInput.value = selectedServices > 0 ? selectedServices.toFixed(2) : '';
        }

        if (productFeeInput) {
            productFeeInput.value = selectedProducts > 0 ? selectedProducts.toFixed(2) : '';
        }

        renderSelectedPricingGuides();
    };

    const recalculateTotal = () => {
        if (!totalInput) {
            return;
        }

        recalculateSectionTotals();

        const professional = normalizeCurrency(document.querySelector('[name="estimated_professional_fee"]')?.value);
        const government = normalizeCurrency(
            document.querySelector('[name="estimated_government_fee"]')?.value
            ?? document.querySelector('[name="estimated_government_fees"]')?.value
        );
        const support = normalizeCurrency(document.querySelector('[name="estimated_service_support_fee"]')?.value);
        const totalServiceFee = normalizeCurrency(document.querySelector('[name="total_service_fee"]')?.value);
        const totalProductFee = normalizeCurrency(document.querySelector('[name="total_product_fee"]')?.value);
        const discount = normalizeCurrency(document.querySelector('[name="deal_discount"]')?.value);

        let otherTotal = 0;
        otherFeeAmountInputs().forEach((input) => {
            otherTotal += normalizeCurrency(input.value);
        });

        const total = Math.max((professional + government + support + totalServiceFee + totalProductFee + otherTotal) - discount, 0);
        totalInput.value = total > 0 ? total.toFixed(2) : '';
    };

    plannedStartDateInput?.addEventListener('change', syncEstimatedDuration);
    confirmedDeliveryDateInput?.addEventListener('change', syncEstimatedDuration);
    syncEstimatedDuration();

    const setDependentDisabled = (isDisabled) => {
        if (!dependentSections) {
            return;
        }
        dependentSections.classList.toggle('opacity-60', isDisabled);
        dependentSections.classList.toggle('pointer-events-none', isDisabled);
        requiredMessage?.classList.toggle('hidden', !isDisabled);
        if (saveBtn) {
            saveBtn.disabled = isDisabled;
            saveBtn.classList.toggle('opacity-60', isDisabled);
            saveBtn.classList.toggle('cursor-not-allowed', isDisabled);
        }
    };

    const applyOtherFieldToggles = () => {
        const toggleInputs = Array.from(document.querySelectorAll('[data-other-target]'));
        const targets = [...new Set(toggleInputs.map((item) => item.dataset.otherTarget).filter(Boolean))];
        targets.forEach((targetId) => {
            const target = document.getElementById(targetId);
            if (!target) {
                return;
            }
            const visible = toggleInputs.some((item) => item.dataset.otherTarget === targetId && item.checked);
            target.classList.toggle('hidden', !visible);
        });
    };

    const syncServiceGroups = () => {
        const selectedAreas = Array.from(document.querySelectorAll('input[name="service_area_options[]"]:checked'))
            .map((input) => String(input.value || '').trim())
            .filter((value) => value !== '' && value !== 'Others');
        const serviceGroups = Array.from(document.querySelectorAll('[data-service-group]'));
        const serviceEmptyState = document.getElementById('dealServicesEmptyState');
        const serviceGrid = document.getElementById('dealServicesGrid');

        let visibleCount = 0;

        serviceGroups.forEach((group) => {
            const isVisible = selectedAreas.includes(group.dataset.serviceGroup || '');
            group.classList.toggle('hidden', !isVisible);

            if (!isVisible) {
                Array.from(group.querySelectorAll('input[name="service_options[]"]')).forEach((input) => {
                    input.checked = false;
                });
                return;
            }

            visibleCount += 1;
        });

        serviceEmptyState?.classList.toggle('hidden', visibleCount > 0);
        serviceGrid?.classList.toggle('hidden', visibleCount === 0);
        recalculateTotal();
    };

    const syncProductOptions = () => {
        const selectedAreas = Array.from(document.querySelectorAll('input[name="service_area_options[]"]:checked'))
            .map((input) => String(input.value || '').trim())
            .filter((value) => value !== '' && value !== 'Others');
        const allowedProducts = new Set(
            selectedAreas.flatMap((area) => productOptionsByServiceArea[area] || [])
        );
        const productOptions = Array.from(document.querySelectorAll('[data-product-option]'));
        const productGroups = Array.from(document.querySelectorAll('[data-product-group]'));
        const productEmptyState = document.getElementById('dealProductsEmptyState');

        let visibleCount = 0;

        productGroups.forEach((group) => {
            const serviceArea = group.dataset.productGroup || '';
            const isVisible = selectedAreas.includes(serviceArea);
            group.classList.toggle('hidden', !isVisible);
        });

        productOptions.forEach((option) => {
            const productValue = option.dataset.productValue || '';
            const serviceArea = option.dataset.serviceAreaProduct || '';
            const isOthers = productValue === 'Others';
            const isVisible = isOthers || (serviceArea !== '' && allowedProducts.has(productValue));

            option.classList.toggle('hidden', !isVisible);

            if (!isVisible) {
                const input = option.querySelector('input[name="product_options[]"]');
                if (input) {
                    input.checked = false;
                }
            } else {
                visibleCount += 1;
            }
        });

        productEmptyState?.classList.toggle('hidden', visibleCount > 1 || allowedProducts.size > 0);
        applyOtherFieldToggles();
        recalculateTotal();
    };

    const createTag = (value, inputName) => {
        const wrapper = document.createElement('span');
        wrapper.className = 'custom-tag inline-flex items-center gap-1 rounded-full border border-gray-200 bg-gray-100 px-2 py-1 text-xs text-gray-700';
        wrapper.setAttribute('data-tag-item', '');

        const text = document.createElement('span');
        text.textContent = value;
        wrapper.appendChild(text);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-tag text-gray-500 hover:text-gray-700';
        removeBtn.setAttribute('data-tag-remove', '');
        removeBtn.textContent = '×';
        removeBtn.addEventListener('click', () => {
            wrapper.remove();
            recalculateTotal();
        });
        wrapper.appendChild(removeBtn);

        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = inputName;
        hidden.value = value;
        wrapper.appendChild(hidden);

        return wrapper;
    };

    const createSelectableOption = ({ value, optionInputName, hiddenInputName, optionDataAttribute = 'data-custom-option' }) => {
        const label = document.createElement('label');
        label.className = 'flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700';
        label.setAttribute(optionDataAttribute, '');

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = optionInputName;
        checkbox.value = value;
        checkbox.checked = true;
        checkbox.className = 'h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500';

        const text = document.createElement('span');
        text.className = 'flex-1';
        text.textContent = value;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'text-gray-500 hover:text-gray-700';
        removeBtn.setAttribute('data-custom-option-remove', '');
        removeBtn.textContent = '×';

        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = hiddenInputName;
        hidden.value = value;
        hidden.setAttribute('data-custom-option-hidden', '');

        removeBtn.addEventListener('click', () => {
            label.remove();
            recalculateTotal();
        });

        label.appendChild(checkbox);
        label.appendChild(text);
        label.appendChild(removeBtn);
        label.appendChild(hidden);

        return label;
    };

    const createSelectableRadioOption = ({ value, optionInputName, hiddenInputName, optionDataAttribute = 'data-custom-radio-option' }) => {
        const label = document.createElement('label');
        label.className = 'flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700';
        label.setAttribute(optionDataAttribute, '');

        const radio = document.createElement('input');
        radio.type = 'radio';
        radio.name = optionInputName;
        radio.value = value;
        radio.checked = true;
        radio.className = 'h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500';

        const text = document.createElement('span');
        text.className = 'flex-1';
        text.textContent = value;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'text-gray-500 hover:text-gray-700';
        removeBtn.setAttribute('data-custom-radio-remove', '');
        removeBtn.textContent = '×';

        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = hiddenInputName;
        hidden.value = value;
        hidden.setAttribute('data-custom-radio-hidden', '');

        removeBtn.addEventListener('click', () => {
            const wasChecked = radio.checked;
            label.remove();
            if (wasChecked) {
                const fallback = document.querySelector(`input[name="${optionInputName}"]:not([value="${value}"])`);
                if (fallback instanceof HTMLInputElement) {
                    fallback.checked = true;
                }
            }
        });

        label.appendChild(radio);
        label.appendChild(text);
        label.appendChild(removeBtn);
        label.appendChild(hidden);

        return label;
    };

    const initOthersTagInput = ({
        triggerElements,
        container,
        input,
        tagsContainer,
        inputName,
        isEnabled,
    }) => {
        if (!container || !input || !tagsContainer) {
            return;
        }
        const resolvedInputName = inputName || input.dataset.name;
        if (!resolvedInputName) {
            return;
        }

        const existingTagRemovers = Array.from(tagsContainer.querySelectorAll('[data-tag-remove]'));
        existingTagRemovers.forEach((button) => {
            button.addEventListener('click', () => {
                button.closest('[data-tag-item]')?.remove();
                recalculateTotal();
            });
        });

        const syncVisibility = () => {
            const enabled = isEnabled();
            container.classList.toggle('hidden', !enabled);
            if (!enabled) {
                input.value = '';
                Array.from(tagsContainer.querySelectorAll('[data-tag-item]')).forEach((tag) => tag.remove());
            }
        };

        input.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') {
                return;
            }
            event.preventDefault();

            const value = String(input.value || '').trim();
            if (value === '' || !isEnabled()) {
                return;
            }

            const existingValues = Array.from(tagsContainer.querySelectorAll(`input[name="${resolvedInputName}"]`))
                .map((hidden) => String(hidden.value || '').trim().toLowerCase());
            if (existingValues.includes(value.toLowerCase())) {
                input.value = '';
                return;
            }

            tagsContainer.appendChild(createTag(value, resolvedInputName));
            input.value = '';
            recalculateTotal();
        });

        triggerElements.forEach((trigger) => {
            trigger.addEventListener('change', syncVisibility);
        });

        syncVisibility();
    };

    const initOthersSelectableOptions = ({
        triggerElements,
        container,
        input,
        optionsContainer,
        hiddenInputName,
        optionInputName,
        isEnabled,
        insertBeforeElement,
        optionDataAttribute = 'data-custom-option',
    }) => {
        if (!container || !input || !optionsContainer) {
            return;
        }

        const customOptionSelector = `[${optionDataAttribute}]`;

        const syncVisibility = () => {
            const hasCustomOptions = optionsContainer.querySelector(customOptionSelector) !== null;
            container.classList.toggle('hidden', !(isEnabled() || hasCustomOptions));
            if (!isEnabled() && !hasCustomOptions) {
                input.value = '';
            }
        };

        Array.from(optionsContainer.querySelectorAll('[data-custom-option-remove]')).forEach((button) => {
            button.addEventListener('click', () => {
                button.closest(customOptionSelector)?.remove();
                syncVisibility();
                recalculateTotal();
            });
        });

        input.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();
            const value = String(input.value || '').trim();
            if (value === '' || !isEnabled()) {
                return;
            }

            const existingValues = Array.from(optionsContainer.querySelectorAll(`input[name="${optionInputName}"]`))
                .map((item) => String(item.value || '').trim().toLowerCase());
            if (existingValues.includes(value.toLowerCase())) {
                input.value = '';
                return;
            }

            const option = createSelectableOption({
                value,
                optionInputName,
                hiddenInputName,
                optionDataAttribute,
            });

            if (insertBeforeElement) {
                optionsContainer.insertBefore(option, insertBeforeElement);
            } else {
                optionsContainer.appendChild(option);
            }

            input.value = '';
            syncVisibility();
            recalculateTotal();
        });

        triggerElements.forEach((trigger) => {
            trigger?.addEventListener('change', syncVisibility);
        });

        syncVisibility();
    };

    const initOthersSelectableRadioOptions = ({
        triggerElements,
        container,
        input,
        optionsContainer,
        hiddenInputName,
        optionInputName,
        isEnabled,
        insertBeforeElement,
        optionDataAttribute = 'data-custom-radio-option',
    }) => {
        if (!container || !input || !optionsContainer) {
            return;
        }

        const customOptionSelector = `[${optionDataAttribute}]`;

        const syncVisibility = () => {
            const hasCustomOptions = optionsContainer.querySelector(customOptionSelector) !== null;
            container.classList.toggle('hidden', !(isEnabled() || hasCustomOptions));
            if (!isEnabled() && !hasCustomOptions) {
                input.value = '';
            }
        };

        Array.from(optionsContainer.querySelectorAll('[data-custom-radio-remove]')).forEach((button) => {
            button.addEventListener('click', () => {
                const option = button.closest(customOptionSelector);
                const radio = option?.querySelector(`input[name="${optionInputName}"]`);
                const wasChecked = radio instanceof HTMLInputElement && radio.checked;
                option?.remove();
                if (wasChecked) {
                    const fallback = optionsContainer.querySelector(`input[name="${optionInputName}"]`);
                    if (fallback instanceof HTMLInputElement) {
                        fallback.checked = true;
                    }
                }
                syncVisibility();
            });
        });

        input.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();
            const value = String(input.value || '').trim();
            if (value === '' || !isEnabled()) {
                return;
            }

            const existingValues = Array.from(optionsContainer.querySelectorAll(`input[name="${optionInputName}"]`))
                .map((item) => String(item.value || '').trim().toLowerCase());
            if (existingValues.includes(value.toLowerCase())) {
                const existingOption = Array.from(optionsContainer.querySelectorAll(`input[name="${optionInputName}"]`))
                    .find((item) => String(item.value || '').trim().toLowerCase() === value.toLowerCase());
                if (existingOption instanceof HTMLInputElement) {
                    existingOption.checked = true;
                }
                input.value = '';
                return;
            }

            const option = createSelectableRadioOption({
                value,
                optionInputName,
                hiddenInputName,
                optionDataAttribute,
            });

            if (insertBeforeElement) {
                optionsContainer.insertBefore(option, insertBeforeElement);
            } else {
                optionsContainer.appendChild(option);
            }

            input.value = '';
            syncVisibility();
        });

        triggerElements.forEach((trigger) => {
            trigger?.addEventListener('change', syncVisibility);
        });

        syncVisibility();
    };

    const normalizeClientRequirementRowHidden = (row) => {
        const hiddenInput = row.querySelector('input[data-client-custom-hidden]');
        if (!hiddenInput) {
            return;
        }
        const label = String(row.dataset.clientCustomLabel || '').trim();
        if (label === '') {
            return;
        }
        const selectedStatus = row.querySelector('input[data-client-custom-status]:checked')?.value === 'provided'
            ? 'Provided'
            : 'Pending';
        hiddenInput.value = `Other: ${label} | ${selectedStatus}`;
    };

    const syncClientRequirementRowState = (row) => {
        if (!(row instanceof HTMLElement)) {
            return;
        }

        const providedChecked = row.querySelector('input[type="radio"][value="provided"]')?.checked === true;
        const pendingChecked = row.querySelector('input[type="radio"][value="pending"]')?.checked === true;

        row.classList.remove('bg-emerald-50', 'bg-amber-50');

        if (providedChecked) {
            row.classList.add('bg-emerald-50');
            return;
        }

        if (pendingChecked) {
            row.classList.add('bg-amber-50');
        }
    };

    const applyClientRequirementStatusMap = (statusMap = {}) => {
        if (!statusMap || typeof statusMap !== 'object') {
            return;
        }

        Object.entries(statusMap).forEach(([key, status]) => {
            if (!key || !status) {
                return;
            }

            const target = document.querySelector(`input[data-client-requirement-status="${key}:${status}"]`);
            if (target instanceof HTMLInputElement) {
                target.checked = true;
                const row = target.closest('[data-client-requirement-row]');
                syncClientRequirementRowState(row);
            }
        });
    };

    const syncAllClientRequirementRows = () => {
        Array.from(document.querySelectorAll('[data-client-requirement-row]')).forEach((row) => {
            syncClientRequirementRowState(row);
        });
    };

    const attachClientRequirementRowHandlers = (row) => {
        Array.from(row.querySelectorAll('input[data-client-custom-status]')).forEach((radio) => {
            radio.addEventListener('change', () => {
                normalizeClientRequirementRowHidden(row);
                syncClientRequirementRowState(row);
            });
        });
        row.querySelector('[data-client-custom-remove]')?.addEventListener('click', () => {
            row.remove();
        });
        normalizeClientRequirementRowHidden(row);
        syncClientRequirementRowState(row);
    };

    const selectedServiceRequirementGroup = () => {
        if (selectedCustomerType() === 'business') {
            const organization = String(selectedBusinessRecord?.business_organization || '').trim().toLowerCase();
            return organization === 'sole_proprietorship' ? 'individual' : 'juridical';
        }

        return 'individual';
    };

    const createDerivedRequirementRow = (label) => {
        const row = document.createElement('tr');
        row.setAttribute('data-client-custom-row', '');
        row.setAttribute('data-service-derived-row', '');
        row.dataset.clientCustomLabel = label;

        const uid = `client_requirements_service_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`;

        const requirementCell = document.createElement('td');
        requirementCell.className = 'border border-gray-200 px-3 py-2 text-gray-700';
        requirementCell.textContent = `Other: ${label}`;

        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'client_requirements_custom[]';
        hidden.setAttribute('data-client-custom-hidden', '');
        requirementCell.appendChild(hidden);

        const providedCell = document.createElement('td');
        providedCell.className = 'border border-gray-200 px-3 py-2 text-center';
        const providedRadio = document.createElement('input');
        providedRadio.type = 'radio';
        providedRadio.name = uid;
        providedRadio.value = 'provided';
        providedRadio.className = 'h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500';
        providedRadio.setAttribute('data-client-custom-status', 'provided');
        providedCell.appendChild(providedRadio);

        const pendingCell = document.createElement('td');
        pendingCell.className = 'border border-gray-200 px-3 py-2 text-center';
        const pendingRadio = document.createElement('input');
        pendingRadio.type = 'radio';
        pendingRadio.name = uid;
        pendingRadio.value = 'pending';
        pendingRadio.checked = true;
        pendingRadio.className = 'h-4 w-4 border-gray-300 text-amber-600 focus:ring-amber-500';
        pendingRadio.setAttribute('data-client-custom-status', 'pending');
        pendingCell.appendChild(pendingRadio);

        row.appendChild(requirementCell);
        row.appendChild(providedCell);
        row.appendChild(pendingCell);

        return row;
    };

    const syncServiceRequirementRows = () => {
        const tableBody = document.querySelector('#clientRequirementsTable tbody');
        if (!tableBody) {
            return;
        }

        Array.from(tableBody.querySelectorAll('[data-service-derived-row]')).forEach((row) => row.remove());

        const selectedServices = Array.from(document.querySelectorAll('input[name="service_options[]"]:checked'))
            .map((input) => String(input.value || '').trim())
            .filter((value) => value !== '');
        const requirementGroup = selectedServiceRequirementGroup();
        const labels = [];

        selectedServices.forEach((serviceName) => {
            const serviceRequirements = serviceRequirementCatalog?.[serviceName] || {};
            const requirements = Array.isArray(serviceRequirements?.[requirementGroup]) ? serviceRequirements[requirementGroup] : [];
            requirements.forEach((requirement) => {
                const cleanRequirement = String(requirement || '').trim();
                if (cleanRequirement !== '') {
                    labels.push(`${serviceName}: ${cleanRequirement}`);
                }
            });
        });

        Array.from(new Set(labels)).forEach((label) => {
            const row = createDerivedRequirementRow(label);
            tableBody.appendChild(row);
            attachClientRequirementRowHandlers(row);
        });

        const othersPending = document.querySelector('input[data-client-requirement-status="others:pending"]');
        if (labels.length > 0 && othersPending instanceof HTMLInputElement) {
            othersPending.checked = true;
        }

        syncAllClientRequirementRows();
    };

    const initClientRequirementsOthers = () => {
        const othersRadios = Array.from(document.querySelectorAll('input[data-client-others-radio]'));
        const container = document.getElementById('client_requirements_container');
        const input = document.getElementById('client_requirements_input');
        const tableBody = document.querySelector('#clientRequirementsTable tbody');
        if (!container || !input || !tableBody || othersRadios.length === 0) {
            return;
        }

        const isEnabled = () => othersRadios.some((radio) => radio.checked);

        const syncVisibility = () => {
            const hasCustomRows = tableBody.querySelector('[data-client-custom-row]') !== null;
            container.classList.toggle('hidden', !(isEnabled() || hasCustomRows));
            if (!isEnabled() && !hasCustomRows) {
                input.value = '';
            }
        };

        const createCustomRow = (label) => {
            const row = document.createElement('tr');
            row.setAttribute('data-client-custom-row', '');
            row.dataset.clientCustomLabel = label;

            const uid = `client_requirements_custom_status_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`;

            const requirementCell = document.createElement('td');
            requirementCell.className = 'border border-gray-200 px-3 py-2 text-gray-700';
            requirementCell.textContent = `Other: ${label}`;

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'client_requirements_custom[]';
            hidden.setAttribute('data-client-custom-hidden', '');
            requirementCell.appendChild(hidden);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'ml-2 text-xs text-gray-500 hover:text-gray-700';
            removeBtn.setAttribute('data-client-custom-remove', '');
            removeBtn.textContent = '×';
            requirementCell.appendChild(removeBtn);

            const providedCell = document.createElement('td');
            providedCell.className = 'border border-gray-200 px-3 py-2 text-center';
            const providedRadio = document.createElement('input');
            providedRadio.type = 'radio';
            providedRadio.name = uid;
            providedRadio.value = 'provided';
            providedRadio.className = 'h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500';
            providedRadio.setAttribute('data-client-custom-status', 'provided');
            providedCell.appendChild(providedRadio);

            const pendingCell = document.createElement('td');
            pendingCell.className = 'border border-gray-200 px-3 py-2 text-center';
            const pendingRadio = document.createElement('input');
            pendingRadio.type = 'radio';
            pendingRadio.name = uid;
            pendingRadio.value = 'pending';
            pendingRadio.checked = true;
            pendingRadio.className = 'h-4 w-4 border-gray-300 text-amber-600 focus:ring-amber-500';
            pendingRadio.setAttribute('data-client-custom-status', 'pending');
            pendingCell.appendChild(pendingRadio);

            row.appendChild(requirementCell);
            row.appendChild(providedCell);
            row.appendChild(pendingCell);

            return row;
        };

        input.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') {
                return;
            }
            event.preventDefault();

            const value = String(input.value || '').trim();
            if (value === '' || !isEnabled()) {
                return;
            }

            const existingLabels = Array.from(tableBody.querySelectorAll('[data-client-custom-row]'))
                .map((row) => String(row.dataset.clientCustomLabel || '').trim().toLowerCase());
            if (existingLabels.includes(value.toLowerCase())) {
                input.value = '';
                return;
            }

            const row = createCustomRow(value);
            tableBody.appendChild(row);
            attachClientRequirementRowHandlers(row);
            input.value = '';
            syncVisibility();
        });

        othersRadios.forEach((radio) => {
            radio.addEventListener('change', syncVisibility);
        });

        Array.from(tableBody.querySelectorAll('[data-client-custom-row]')).forEach((row) => {
            attachClientRequirementRowHandlers(row);
        });

        syncVisibility();
    };

    const setFieldValue = (fieldId, value, fallback = '') => {
        const field = document.getElementById(fieldId);
        if (!field) {
            return;
        }
        const normalizedValue = value === null || value === undefined || String(value).trim() === ''
            ? fallback
            : value;
        if (field.tagName === 'SELECT') {
            const hasOption = Array.from(field.options).some((option) => option.value === (normalizedValue || ''));
            field.value = hasOption ? (normalizedValue || '') : '';
            return;
        }
        field.value = normalizedValue || '';
    };

    const applyBusinessRecord = (record) => {
        selectedBusinessRecord = record;
        selectedContactRecord = null;
        const linkedContact = contactRecords.find((item) => Number(item.id) === Number(record.primary_contact_id))
            || contactRecords.find((item) => (item.company_name || '') === (record.company_name || ''));
        contactIdInput.value = linkedContact ? String(linkedContact.id) : '';
        contactSearch.value = record.company_name || '';
        setFieldValue('deal_salutation', record.authorized_contact_salutation || linkedContact?.salutation || '', '-');
        setFieldValue('deal_sex', record.authorized_contact_sex || linkedContact?.sex || '', '-');
        setFieldValue('deal_first_name', record.authorized_contact_first_name || linkedContact?.first_name || '', '-');
        setFieldValue('deal_last_name', record.authorized_contact_last_name || linkedContact?.last_name || '', '-');
        setFieldValue(
            'deal_middle_initial',
            record.authorized_contact_middle_initial
                || linkedContact?.middle_initial
                || (linkedContact?.middle_name ? String(linkedContact.middle_name).charAt(0) : ''),
            '-'
        );
        setFieldValue('deal_name_extension', record.authorized_contact_name_extension || linkedContact?.name_extension || '', '-');
        setFieldValue('deal_date_of_birth', record.authorized_contact_date_of_birth || linkedContact?.date_of_birth || '');
        setFieldValue('deal_company_name', record.company_name || '');
        setFieldValue('deal_company_address', record.company_address || '');
        setFieldValue('deal_email', record.authorized_contact_email || linkedContact?.email || record.email || '');
        setFieldValue('deal_mobile', record.authorized_contact_mobile || linkedContact?.mobile || record.mobile || '', '-');
        setFieldValue('deal_address', record.authorized_contact_address || linkedContact?.address || '', '-');
        setFieldValue('deal_position', record.authorized_contact_position || linkedContact?.position || '', '-');
        applyClientRequirementStatusMap({
            ...(linkedContact?.client_requirement_status_map || {}),
            ...(record.client_requirement_status_map || {}),
        });
        syncServiceRequirementRows();
        setDependentDisabled(false);
        contactResults.classList.add('hidden');
    };

    const applyContactRecord = (record) => {
        selectedContactRecord = record;
        selectedBusinessRecord = null;
        contactIdInput.value = String(record.id);
        contactSearch.value = record.label || '';
        setFieldValue('deal_first_name', record.first_name || '');
        setFieldValue('deal_last_name', record.last_name || '');
        setFieldValue('deal_middle_initial', record.middle_initial || (record.middle_name ? String(record.middle_name).charAt(0) : ''));

        Object.entries(fieldMap).forEach(([key, fieldId]) => {
            if (['first_name', 'middle_initial', 'last_name'].includes(key)) {
                return;
            }
            setFieldValue(fieldId, record[key] || '');
        });

        applyClientRequirementStatusMap(record.client_requirement_status_map || {});
        syncServiceRequirementRows();
        setDependentDisabled(false);
        contactResults.classList.add('hidden');
    };

    const syncCustomerSearchUi = () => {
        const customerType = selectedCustomerType();
        const isBusiness = customerType === 'business';

        const selectionSectionTitle = document.getElementById('dealSelectionSectionTitle');

        if (contactSearchLabel) {
            contactSearchLabel.textContent = isBusiness ? 'Search Existing Business / Company' : 'Search Existing Client';
        }

        if (selectionSectionTitle) {
            selectionSectionTitle.textContent = isBusiness ? 'Select Existing Business / Company' : 'Select Existing Contact / Client';
        }

        if (contactSearchHelpText) {
            contactSearchHelpText.textContent = isBusiness
                ? 'Search by company name, owner, email, or mobile number.'
                : 'Search by contact name, company, email, or mobile number.';
        }

        if (contactSearch) {
            contactSearch.placeholder = isBusiness
                ? 'Type company, owner, email, or mobile...'
                : 'Type name, company, email, or mobile...';
            contactSearch.value = '';
        }

        contactResults?.classList.add('hidden');
        contactIdInput.value = '';
        selectedBusinessRecord = null;
        selectedContactRecord = null;
        syncServiceRequirementRows();
        setDependentDisabled(customerType === '');
    };

    const initSupportRequiredCustomOptions = () => {
        const toggle = document.getElementById('support_required_others');
        const container = document.getElementById('support_required_container');
        const input = document.getElementById('support_required_input');
        const grid = document.getElementById('support_required_options_grid');

        if (!toggle || !container || !input || !grid) {
            return;
        }

        initOthersSelectableOptions({
            triggerElements: [toggle].filter(Boolean),
            container,
            input,
            optionsContainer: grid,
            hiddenInputName: 'support_required_custom[]',
            optionInputName: 'support_required_options[]',
            isEnabled: () => Boolean(toggle?.checked),
            insertBeforeElement: toggle?.closest('label') ?? null,
            optionDataAttribute: 'data-support-custom-option',
        });
    };

    const createContactResultButton = (record, customerType) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'deal-contact-result block w-full rounded-md px-3 py-2 text-left hover:bg-blue-50';
        button.dataset.recordId = String(record.id ?? '');

        const label = document.createElement('p');
        label.className = 'text-sm font-medium text-gray-800';
        label.textContent = record.label || (customerType === 'business' ? 'Unnamed company' : 'Unnamed contact');

        const company = document.createElement('p');
        company.className = 'text-xs text-gray-500';
        company.textContent = customerType === 'business'
            ? (record.owner_name || record.company_name || '-')
            : (record.company_name || '-');

        const meta = document.createElement('p');
        meta.className = 'text-[11px] text-gray-400';
        meta.textContent = `${record.email || '-'} · ${record.mobile || '-'}`;

        button.append(label, company, meta);

        return button;
    };

    const renderContactResults = (keyword) => {
        if (!contactResults) {
            return;
        }

        const customerType = selectedCustomerType();
        if (customerType === '') {
            contactResults.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Select a customer type first.</div>';
            contactResults.classList.remove('hidden');
            return;
        }

        const query = keyword.trim().toLowerCase();
        const records = customerType === 'business' ? companyRecords : contactRecords;
        const matches = records
            .filter((record) => {
                if (query === '') {
                    return true;
                }
                const blob = (record.search_blob || '').toLowerCase();
                const label = (record.label || '').toLowerCase();
                return blob.includes(query) || label.includes(query);
            })
            .slice(0, 30);

        if (matches.length === 0) {
            const emptyState = document.createElement('div');
            emptyState.className = 'px-3 py-2 text-sm text-gray-500';
            emptyState.textContent = `No matching ${customerType === 'business' ? 'companies' : 'contacts'} found.`;
            contactResults.replaceChildren(emptyState);
            contactResults.classList.remove('hidden');
            return;
        }

        contactResults.replaceChildren(...matches.map((record) => createContactResultButton(record, customerType)));

        contactResults.classList.remove('hidden');

        Array.from(contactResults.querySelectorAll('.deal-contact-result')).forEach((button) => {
            button.addEventListener('click', () => {
                const selectedId = Number.parseInt(button.dataset.recordId || '', 10);
                const record = records.find((item) => Number(item.id) === selectedId);
                if (!record) {
                    return;
                }

                if (customerType === 'business') {
                    applyBusinessRecord(record);
                    return;
                }

                applyContactRecord(record);
            });
        });
    };

    ownerTrigger?.addEventListener('click', () => {
        ownerMenu?.classList.toggle('hidden');
        if (ownerMenu && !ownerMenu.classList.contains('hidden')) {
            ownerSearch?.focus();
        }
    });

    ownerSearch?.addEventListener('input', () => {
        const keyword = ownerSearch.value.trim().toLowerCase();
        ownerOptions.forEach((option) => {
            const name = (option.dataset.ownerName || '').toLowerCase();
            const email = (option.dataset.ownerEmail || '').toLowerCase();
            const matches = keyword === '' || name.includes(keyword) || email.includes(keyword);
            option.classList.toggle('hidden', !matches);
        });
    });

    ownerOptions.forEach((option) => {
        option.addEventListener('click', () => {
            ownerInput.value = option.dataset.ownerId || '';
            ownerLabel.textContent = `Owner: ${option.dataset.ownerName || ''}`;
            closeOwnerMenu();
        });
    });

    contactSearch?.addEventListener('focus', () => renderContactResults(contactSearch.value));
    contactSearch?.addEventListener('input', () => renderContactResults(contactSearch.value));
    Array.from(document.querySelectorAll('input[name="customer_type"]')).forEach((input) => {
        input.addEventListener('change', syncCustomerSearchUi);
    });

    feeInputs.forEach((field) => field.addEventListener('input', recalculateTotal));
    document.addEventListener('input', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement)) {
            return;
        }

        if (
            target.name === 'estimated_professional_fee' ||
            target.name === 'estimated_government_fee' ||
            target.name === 'estimated_government_fees' ||
            target.name === 'estimated_service_support_fee' ||
            target.name === 'deal_discount' ||
            target.name === 'service_options[]' ||
            target.name === 'product_options[]' ||
            target.name === 'services_other[]' ||
            target.name === 'products_other_entries[]' ||
            target.name === 'other_fees_amounts[]'
        ) {
            recalculateTotal();
        }
    });
    otherFeesRows?.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement) || !target.matches('[data-other-fee-remove]')) {
            return;
        }

        target.closest('[data-other-fee-row]')?.remove();
        recalculateTotal();
    });
    addOtherFeeBtn?.addEventListener('click', () => {
        if (!otherFeesRows) {
            return;
        }

        otherFeesRows.appendChild(createOtherFeeRow());
        recalculateTotal();
    });

    Array.from(document.querySelectorAll('[data-other-target]')).forEach((input) => {
        input.addEventListener('change', applyOtherFieldToggles);
    });
    Array.from(document.querySelectorAll('[data-client-requirement-status]')).forEach((input) => {
        input.addEventListener('change', () => {
            syncClientRequirementRowState(input.closest('[data-client-requirement-row]'));
        });
    });
    document.querySelectorAll('.other-input').forEach((input) => {
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
            }
        });
    });
    document.getElementById('services-other-toggle')?.addEventListener('change', function () {
        document.getElementById('services-other-wrapper')?.classList.toggle('hidden', !this.checked);
    });
    Array.from(document.querySelectorAll('input[name="service_area_options[]"]')).forEach((input) => {
        input.addEventListener('change', () => {
            syncServiceGroups();
            syncProductOptions();
        });
    });
    Array.from(document.querySelectorAll('input[name="service_options[]"]')).forEach((input) => {
        input.addEventListener('change', syncServiceRequirementRows);
    });
    const serviceAreaOthersCheckbox = document.querySelector('input[name="service_area_options[]"][value="Others"]');
    initOthersSelectableOptions({
        triggerElements: [serviceAreaOthersCheckbox].filter(Boolean),
        container: document.getElementById('service-area-other-wrapper'),
        input: document.getElementById('service-area-other-input'),
        optionsContainer: document.getElementById('service-area-options-grid'),
        hiddenInputName: 'service_area_other[]',
        optionInputName: 'service_area_options[]',
        isEnabled: () => Boolean(serviceAreaOthersCheckbox?.checked),
        insertBeforeElement: serviceAreaOthersCheckbox?.closest('label') ?? null,
    });
    initOthersSelectableOptions({
        triggerElements: [document.getElementById('services-other-toggle')].filter(Boolean),
        container: document.getElementById('services-other-wrapper'),
        input: document.getElementById('services-other-input'),
        optionsContainer: document.getElementById('services-custom-options'),
        hiddenInputName: 'services_other[]',
        optionInputName: 'service_options[]',
        isEnabled: () => Boolean(document.getElementById('services-other-toggle')?.checked),
    });
    const productOthersCheckbox = document.querySelector('input[name="product_options[]"][value="Others"]');
    initOthersSelectableOptions({
        triggerElements: [productOthersCheckbox].filter(Boolean),
        container: document.getElementById('deal_products_other_wrap'),
        input: document.getElementById('products-other-input'),
        optionsContainer: document.getElementById('product-options-grid'),
        hiddenInputName: 'products_other_entries[]',
        optionInputName: 'product_options[]',
        isEnabled: () => Boolean(productOthersCheckbox?.checked),
        insertBeforeElement: productOthersCheckbox?.closest('label') ?? null,
    });
    initOthersSelectableOptions({
        triggerElements: [document.getElementById('required_actions_others')].filter(Boolean),
        container: document.getElementById('required_actions_container'),
        input: document.getElementById('required_actions_input'),
        optionsContainer: document.getElementById('required_actions_grid'),
        hiddenInputName: 'required_actions_custom[]',
        optionInputName: 'required_actions_options[]',
        isEnabled: () => Boolean(document.getElementById('required_actions_others')?.checked),
        insertBeforeElement: document.getElementById('required_actions_others')?.closest('label') ?? null,
    });
    initSupportRequiredCustomOptions();
    initOthersSelectableRadioOptions({
        triggerElements: [document.getElementById('service_complexity_others')].filter(Boolean),
        container: document.getElementById('service_complexity_container'),
        input: document.getElementById('service_complexity_input'),
        optionsContainer: document.getElementById('service_complexity_grid'),
        hiddenInputName: 'service_complexity_custom[]',
        optionInputName: 'service_complexity',
        isEnabled: () => Boolean(document.getElementById('service_complexity_others')?.checked),
        insertBeforeElement: document.getElementById('service_complexity_others')?.closest('label') ?? null,
        optionDataAttribute: 'data-complexity-custom-option',
    });
    initClientRequirementsOthers();
    syncServiceRequirementRows();
    syncProductOptions();
    initOthersTagInput({
        triggerElements: Array.from(document.querySelectorAll('input[name="payment_terms"]')),
        container: document.getElementById('deal_payment_terms_other_wrap'),
        input: document.getElementById('payment_terms_input'),
        tagsContainer: document.getElementById('payment_terms_tags'),
        inputName: 'payment_terms_custom[]',
        isEnabled: () => Boolean(document.querySelector('input[name="payment_terms"][value="Others"]')?.checked),
    });

    openBtn?.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);
    overlay?.addEventListener('click', closeModal);

    document.addEventListener('click', (event) => {
        if (ownerMenu && !ownerMenu.classList.contains('hidden')) {
            if (!ownerMenu.contains(event.target) && !ownerTrigger?.contains(event.target)) {
                closeOwnerMenu();
            }
        }

        if (contactResults && !contactResults.classList.contains('hidden')) {
            if (!contactResults.contains(event.target) && !contactSearch?.contains(event.target)) {
                contactResults.classList.add('hidden');
            }
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    syncCustomerSearchUi();
    syncServiceGroups();
    syncProductOptions();
    syncServiceRequirementRows();
    syncAllClientRequirementRows();
    if (contactIdInput?.value) {
        setDependentDisabled(false);
    }
    applyOtherFieldToggles();
    recalculateTotal();

    if (shouldAutoOpenModal) {
        openModal();
    }
});
</script>

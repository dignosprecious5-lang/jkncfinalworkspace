<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Completion | JK&C INC.</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">
@php
    $data = $record['data'] ?? [];
    $isCompleted = filled($record['supplier_completed_at'] ?? null);
    $dataValue = fn ($key, $default = '') => old("data.$key", data_get($data, $key, $default));
    $supplierLabels = $supplierLabels ?? [];
    $supplierLabel = fn ($key, $default) => $supplierLabels[$key] ?? $default;
    $fieldClass = 'w-full rounded-md border border-gray-300 p-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100';
    $entityOptions = ['Sole Proprietorship', 'Partnership', 'Corporation', 'One Person Corporation (OPC)', 'Cooperative', 'Freelancer / Individual Professional', 'Independent Contractor', 'Government Agency', 'Non-Profit Organization', 'Foreign Company', 'Others'];
    $corporationTypes = ['Domestic Stock Corporation', 'Domestic Non-Stock Corporation', 'Close Corporation', 'Foreign Corporation', 'Branch Office', 'Representative Office', 'Regional Headquarters', 'Regional Operating Headquarters'];
    $vatStatuses = ['VAT Registered', 'Non-VAT', 'Percentage Tax', 'Tax Exempt'];
    $supplierCategories = ['Product Supplier', 'Service Provider', 'Contractor', 'Consultant', 'Marketing Agency', 'IT / Software Provider', 'Logistics Provider', 'Printing Supplier', 'Professional Services', 'Outsourcing Partner', 'Equipment Supplier', 'Office Supplies Supplier', 'Others'];
    $paymentTerms = ['Cash on Delivery', 'Upon Order', 'Upon Completion', 'Weekly', 'Monthly', '7 Days', '15 Days', '30 Days', '45 Days', '60 Days', 'Progress Billing', 'Retainer-Based', 'Others'];
    $paymentMethods = ['Bank Transfer', 'Check', 'Online Payment', 'Others'];
    $idTypes = ['Passport', "Driver's License", 'National ID', 'PRC ID', 'Company ID', 'UMID', 'SSS ID', 'PhilHealth ID', "Voter's ID", 'Postal ID', 'Others'];
    $attachmentRules = [
        'Corporation' => ['SEC Certificate of Registration', 'BIR 2303 Certificate of Registration', "Mayor's Permit / Business Permit", 'Valid ID of Authorized Representative', 'Company Profile', 'Contract / Agreement'],
        'One Person Corporation (OPC)' => ['SEC Certificate of Registration', 'BIR 2303 Certificate of Registration', "Mayor's Permit / Business Permit", 'Valid ID of Authorized Representative', 'Company Profile', 'Contract / Agreement'],
        'Partnership' => ['SEC Certificate of Registration', 'BIR 2303 Certificate of Registration', "Mayor's Permit / Business Permit", 'Valid ID of Authorized Representative', 'Company Profile', 'Contract / Agreement'],
        'Foreign Company' => ['SEC Certificate of Registration', 'BIR 2303 Certificate of Registration', "Mayor's Permit / Business Permit", 'Valid ID of Authorized Representative', 'Company Profile', 'Contract / Agreement'],
        'Sole Proprietorship' => ['DTI Certificate of Registration', 'BIR 2303 Certificate of Registration', "Mayor's Permit / Business Permit", 'Valid ID of Owner / Authorized Representative', 'Business Profile / Company Profile', 'Contract / Agreement'],
        'Cooperative' => ['CDA Certificate of Registration', 'BIR 2303 Certificate of Registration', "Mayor's Permit / Business Permit", 'Valid ID of Authorized Representative', 'Cooperative Profile', 'Contract / Agreement'],
        'Freelancer / Individual Professional' => ['Resume', 'Valid Government ID', 'TIN / BIR Registration, if applicable', 'Resume / Portfolio, if applicable', 'Professional License, if applicable', 'Signed Contract / Agreement'],
        'Independent Contractor' => ['Resume', 'Valid Government ID', 'TIN / BIR Registration, if applicable', 'Resume / Portfolio, if applicable', 'Professional License, if applicable', 'Signed Contract / Agreement'],
        'Government Agency' => ['Agency Profile / Official Agency Information', 'Authorized Representative ID', 'Authority to Transact / Authorization Letter, if applicable', 'Contract / Agreement / Purchase Order'],
        'Non-Profit Organization' => ['SEC Registration / Relevant Registration Certificate', 'BIR 2303 Certificate of Registration, if applicable', "Mayor's Permit / Business Permit, if applicable", 'Valid ID of Authorized Representative', 'Organization Profile', 'Contract / Agreement'],
        'Others' => ['Valid Registration Document, if applicable', 'Valid ID of Authorized Representative', 'Supplier Profile', 'Contract / Agreement', 'Other supporting documents required by the Company'],
    ];
    $selectedCategories = old('data.supplier_category', data_get($data, 'supplier_category', []));
    $selectedCategories = is_array($selectedCategories) ? $selectedCategories : array_filter(array_map('trim', explode(',', (string) $selectedCategories)));
    $existingCategories = collect($record['attachments'] ?? [])->map(fn ($attachment) => $attachment['category'] ?? '')->filter()->values()->all();
    $legalStatements = [
        'legal_acknowledgment' => ['Legal Acknowledgment, Consent, and Electronic Signature', 'I acknowledge that by completing, submitting, and/or electronically signing this Supplier Completion Form, I am confirming that all information and documents submitted are true, correct, complete, authentic, and updated. I further certify that I am duly authorized to submit this form on behalf of the supplier, entity, organization, company, or individual identified herein.'],
        'electronic_signature_consent' => ['Electronic Submission and Signature Consent', 'I agree that my submission of this form, including any typed name, uploaded signature, checked acknowledgment box, uploaded ID, email confirmation, or electronic submission, shall be treated as my valid signature and confirmation, pursuant to the Electronic Commerce Act of 2000, Republic Act No. 8792, which recognizes electronic documents and electronic signatures.'],
        'data_privacy_consent' => ['Data Privacy Consent', 'I consent to the collection, use, processing, verification, storage, retention, and sharing of the submitted personal information, business information, and documents for supplier accreditation, due diligence, procurement, payment processing, compliance, audit, legal, security, and business purposes, in accordance with the Data Privacy Act of 2012, Republic Act No. 10173.'],
        'confidentiality_undertaking' => ['Confidentiality and NDA Undertaking', 'I agree that all confidential, proprietary, client, operational, financial, technical, legal, business, and company information obtained from the Company shall remain strictly confidential and shall not be disclosed, copied, transferred, shared, or used without prior written authority from the Company.'],
        'company_policy_compliance' => ['Compliance with Company Policies', 'I agree that the Supplier shall comply with all applicable laws, rules, regulations, contracts, procurement policies, internal procedures, company memoranda, confidentiality obligations, data privacy requirements, and lawful instructions issued by the Company.'],
        'false_information_penalty' => ['Penalty for False Information', 'I understand that any false statement, concealment, misrepresentation, falsification, fraudulent document, or unauthorized submission may result in denial of accreditation, suspension, blacklisting, termination of engagement, withholding of payment, recovery of damages, and appropriate civil, criminal, administrative, or legal action, including liability for perjury or false testimony under applicable law. Article 183 of the Revised Penal Code covers false testimony in other cases and perjury in solemn affirmation.'],
    ];
@endphp

    <div class="mx-auto max-w-6xl px-4 py-8">
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b bg-gray-50 px-6 py-5 sm:flex-row sm:items-center">
                <img src="{{ asset('images/imaglogo.png') }}" alt="John Kelly & Company" class="h-14 w-auto object-contain">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-gray-500">John Kelly &amp; Company</p>
                    <h1 class="mt-2 text-2xl font-semibold text-gray-900">Supplier Completion Form</h1>
                    <p class="mt-2 text-sm text-gray-600">Complete the supplier accreditation details and submit the required documents for internal review.</p>
                </div>
            </div>

            @if(session('success') || $isCompleted)
                <div class="mx-6 mt-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') ?: 'Supplier information has already been submitted and saved.' }}
                </div>
            @endif

            @if($errors->any())
                <div class="mx-6 mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <p class="font-semibold">Please review the highlighted fields and submit again.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="supplierCompletionForm" method="POST" action="{{ route('finance.supplier.completion.submit', $record['share_token'] ?? '') }}" enctype="multipart/form-data" class="space-y-8 px-6 py-6">
                @csrf
                <fieldset class="space-y-8" @disabled($isCompleted)>

                <section>
                    <h2 class="text-base font-semibold text-gray-900">Business Registration</h2>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('record_number_label', 'Supplier Code') }}</label>
                            <input type="text" name="record_number" value="{{ old('record_number', $record['record_number'] ?? '') }}" class="{{ $fieldClass }} bg-gray-100" readonly>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('record_date_label', 'Date Accomplished') }}</label>
                            <input type="date" name="record_date" value="{{ old('record_date', $record['record_date'] ?? now()->toDateString()) }}" class="{{ $fieldClass }}">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('record_title_label', 'Registered Business Name') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="record_title" value="{{ old('record_title', ($record['record_title'] ?? '') === $supplierLabel('record_title_label', 'Registered Business Name') ? '' : ($record['record_title'] ?? '')) }}" class="{{ $fieldClass }}" required>
                            @if($errors->first('record_title')) <p class="mt-1 text-xs text-red-600">{{ $errors->first('record_title') }}</p> @endif
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('trade_name', 'Trade Name / Brand Name') }}</label>
                            <input type="text" name="data[trade_name]" value="{{ $dataValue('trade_name') }}" class="{{ $fieldClass }}">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('entity_type', 'Entity Type') }} <span class="text-red-500">*</span></label>
                            <select id="entityTypeInput" name="data[entity_type]" class="{{ $fieldClass }}" required>
                                <option value="">Select entity type</option>
                                @foreach($entityOptions as $option)
                                    <option value="{{ $option }}" @selected($dataValue('entity_type') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @if($errors->first('data.entity_type')) <p class="mt-1 text-xs text-red-600">{{ $errors->first('data.entity_type') }}</p> @endif
                        </div>
                        <div id="entityTypeOtherWrap" class="{{ $dataValue('entity_type') === 'Others' ? '' : 'hidden' }}">
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('entity_type_other', 'Specify Other Entity Type') }}</label>
                            <input type="text" name="data[entity_type_other]" value="{{ $dataValue('entity_type_other') }}" class="{{ $fieldClass }}">
                        </div>
                        <div id="corporationTypeWrap" class="{{ in_array($dataValue('entity_type'), ['Corporation', 'One Person Corporation (OPC)', 'Foreign Company'], true) ? '' : 'hidden' }}">
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('corporation_type', 'If Corporation, Specify Corporation Type') }}</label>
                            <select name="data[corporation_type]" class="{{ $fieldClass }}">
                                <option value="">Select corporation type</option>
                                @foreach($corporationTypes as $option)
                                    <option value="{{ $option }}" @selected($dataValue('corporation_type') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label id="registrationNumberLabel" class="mb-1 block text-sm font-medium">{{ $supplierLabel('registration_number', 'Registration Number') }}</label>
                            <input type="text" name="data[registration_number]" value="{{ $dataValue('registration_number') }}" class="{{ $fieldClass }}">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('tin', 'Tax Identification Number (TIN)') }}</label>
                            <input type="text" name="data[tin]" value="{{ $dataValue('tin') }}" class="{{ $fieldClass }}">
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-gray-900">Business Details</h2>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('vat_status', 'VAT Status') }}</label>
                            <select name="data[vat_status]" class="{{ $fieldClass }}">
                                <option value="">Select VAT status</option>
                                @foreach($vatStatuses as $option)
                                    <option value="{{ $option }}" @selected($dataValue('vat_status') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('business_permit_number', 'Business Permit Number') }}</label>
                            <input type="text" name="data[business_permit_number]" value="{{ $dataValue('business_permit_number') }}" class="{{ $fieldClass }}">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('permit_expiry_date', 'Permit Expiry Date') }}</label>
                            <input type="date" name="data[permit_expiry_date]" value="{{ $dataValue('permit_expiry_date') }}" class="{{ $fieldClass }}">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('years_in_operation', 'Years in Operation') }}</label>
                            <input type="number" name="data[years_in_operation]" value="{{ $dataValue('years_in_operation') }}" class="{{ $fieldClass }}" min="0" step="1">
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('nature_of_business', 'Nature of Business') }}</label>
                            <textarea name="data[nature_of_business]" rows="3" class="{{ $fieldClass }}">{{ $dataValue('nature_of_business') }}</textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('products_services_offered', 'Products / Services Offered') }}</label>
                            <textarea name="data[products_services_offered]" rows="3" class="{{ $fieldClass }}">{{ $dataValue('products_services_offered') }}</textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium">{{ $supplierLabel('supplier_category', 'Supplier Category') }}</label>
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
                                @foreach($supplierCategories as $option)
                                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                        <input type="checkbox" name="data[supplier_category][]" value="{{ $option }}" @checked(in_array($option, $selectedCategories, true)) class="rounded border-gray-300">
                                        <span>{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div id="supplierCategoryOtherWrap" class="{{ in_array('Others', $selectedCategories, true) ? '' : 'hidden' }}">
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('supplier_category_other', 'Specify Other Supplier Category') }}</label>
                            <input type="text" name="data[supplier_category_other]" value="{{ $dataValue('supplier_category_other') }}" class="{{ $fieldClass }}">
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-gray-900">Addresses and Contacts</h2>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        @foreach([
                            'registered_address' => $supplierLabel('registered_address', 'Registered Address'),
                            'office_address' => $supplierLabel('office_address', 'Office Address'),
                            'warehouse_address' => $supplierLabel('warehouse_address', 'Warehouse Address'),
                            'billing_address' => $supplierLabel('billing_address', 'Billing Address'),
                        ] as $name => $label)
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium">{{ $label }}</label>
                                <textarea name="data[{{ $name }}]" rows="3" class="{{ $fieldClass }}">{{ $dataValue($name) }}</textarea>
                            </div>
                        @endforeach
                        @foreach([
                            'telephone_number' => [$supplierLabel('telephone_number', 'Telephone Number'), 'text'],
                            'mobile_number' => [$supplierLabel('mobile_number', 'Mobile Number'), 'text'],
                            'email_address' => [$supplierLabel('email_address', 'Official Email Address'), 'email'],
                            'website_social_media' => [$supplierLabel('website_social_media', 'Website / Social Media'), 'text'],
                            'representative_full_name' => [$supplierLabel('representative_full_name', 'Authorized Representative Full Name'), 'text'],
                            'designation' => [$supplierLabel('designation', 'Authorized Representative Position / Designation'), 'text'],
                            'phone_number' => [$supplierLabel('phone_number', 'Authorized Representative Mobile Number'), 'text'],
                            'representative_email_address' => [$supplierLabel('representative_email_address', 'Authorized Representative Email Address'), 'email'],
                            'accounting_contact_person' => [$supplierLabel('accounting_contact_person', 'Accounting Contact Person'), 'text'],
                            'accounting_contact_number' => [$supplierLabel('accounting_contact_number', 'Accounting Contact Number'), 'text'],
                            'accounting_email_address' => [$supplierLabel('accounting_email_address', 'Accounting Email Address'), 'email'],
                        ] as $name => [$label, $type])
                            <div>
                                <label class="mb-1 block text-sm font-medium">{{ $label }} @if(in_array($name, ['email_address', 'representative_full_name', 'phone_number'], true))<span class="text-red-500">*</span>@endif</label>
                                <input type="{{ $type }}" name="data[{{ $name }}]" value="{{ $dataValue($name) }}" class="{{ $fieldClass }}" @if(in_array($name, ['email_address', 'representative_full_name', 'phone_number'], true)) required @endif>
                                @if($errors->first('data.'.$name)) <p class="mt-1 text-xs text-red-600">{{ $errors->first('data.'.$name) }}</p> @endif
                            </div>
                        @endforeach
                    </div>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-gray-900">Payment and Banking</h2>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('payment_terms', 'Payment Terms') }}</label>
                            <select name="data[payment_terms]" class="{{ $fieldClass }}">
                                <option value="">Select payment terms</option>
                                @foreach($paymentTerms as $option)
                                    <option value="{{ $option }}" @selected($dataValue('payment_terms') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="paymentTermsOtherWrap" class="{{ $dataValue('payment_terms') === 'Others' ? '' : 'hidden' }}">
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('payment_terms_other', 'Specify Other Payment Terms') }}</label>
                            <input type="text" name="data[payment_terms_other]" value="{{ $dataValue('payment_terms_other') }}" class="{{ $fieldClass }}">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('preferred_payment_method', 'Preferred Payment Method') }}</label>
                            <select id="paymentMethodInput" name="data[preferred_payment_method]" class="{{ $fieldClass }}">
                                <option value="">Select payment method</option>
                                @foreach($paymentMethods as $option)
                                    <option value="{{ $option }}" @selected($dataValue('preferred_payment_method') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="onlinePaymentWrap" class="{{ $dataValue('preferred_payment_method') === 'Online Payment' ? '' : 'hidden' }}">
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('online_payment_details', 'Online Payment Details') }}</label>
                            <input type="text" name="data[online_payment_details]" value="{{ $dataValue('online_payment_details') }}" class="{{ $fieldClass }}">
                        </div>
                        <div id="paymentMethodOtherWrap" class="{{ $dataValue('preferred_payment_method') === 'Others' ? '' : 'hidden' }}">
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('preferred_payment_method_other', 'Specify Other Payment Method') }}</label>
                            <input type="text" name="data[preferred_payment_method_other]" value="{{ $dataValue('preferred_payment_method_other') }}" class="{{ $fieldClass }}">
                        </div>
                        @foreach([
                            'bank_name' => $supplierLabel('bank_name', 'Bank Name'),
                            'bank_branch' => $supplierLabel('bank_branch', 'Bank Branch'),
                            'bank_account_name' => $supplierLabel('bank_account_name', 'Bank Account Name'),
                            'bank_account_number' => $supplierLabel('bank_account_number', 'Bank Account Number'),
                            'swift_code' => $supplierLabel('swift_code', 'Swift Code'),
                        ] as $name => $label)
                            <div>
                                <label class="mb-1 block text-sm font-medium">{{ $label }}</label>
                                <input type="text" name="data[{{ $name }}]" value="{{ $dataValue($name) }}" class="{{ $fieldClass }}">
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-lg border border-blue-100 bg-blue-50/40 p-4">
                    <h2 class="text-base font-semibold text-blue-900">Required Attachments</h2>
                    <p class="mt-1 text-xs text-gray-600">The required uploads change based on the selected entity type. Each document has a separate file upload field.</p>
                    <div id="requiredAttachmentFields" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2"></div>

                    @if(!empty($record['attachments']))
                        <div class="mt-5 space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Uploaded Files</p>
                            @foreach($record['attachments'] as $attachment)
                                <a href="{{ $attachment['url'] ?? '#' }}" target="_blank" class="flex items-center justify-between gap-3 rounded-md border border-blue-100 bg-white px-3 py-2 text-sm hover:bg-blue-50">
                                    <span class="truncate">{{ $attachment['name'] ?? 'Attachment' }}</span>
                                    <span class="shrink-0 text-xs text-blue-700">{{ $attachment['category'] ?? 'Supporting Document' }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section>
                    <h2 class="text-base font-semibold text-gray-900">Acknowledgment and Signature</h2>
                    <div class="mt-4 space-y-3">
                        @foreach($legalStatements as $name => [$title, $statement])
                            <div class="rounded-lg border border-gray-200 p-3">
                                <label class="flex items-start gap-3">
                                    <input type="checkbox" name="data[{{ $name }}]" value="1" @checked(old('data.'.$name, data_get($data, $name)) == 1) class="mt-1 rounded border-gray-300" required>
                                    <span>
                                        <span class="block text-sm font-semibold text-gray-900">{{ $title }} <span class="text-red-500">*</span></span>
                                        <span class="mt-1 block text-sm leading-6 text-gray-700">{{ $statement }}</span>
                                    </span>
                                </label>
                                @if($errors->first('data.'.$name)) <p class="mt-1 text-xs text-red-600">{{ $errors->first('data.'.$name) }}</p> @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('person_accomplishing_full_name', 'Person Accomplishing the Form Full Name') }}</label>
                            <input type="text" name="data[person_accomplishing_full_name]" value="{{ $dataValue('person_accomplishing_full_name') }}" class="{{ $fieldClass }}">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('person_accomplishing_position', 'Position / Designation') }}</label>
                            <input type="text" name="data[person_accomplishing_position]" value="{{ $dataValue('person_accomplishing_position') }}" class="{{ $fieldClass }}">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('id_type', 'ID Type') }}</label>
                            <select id="idTypeInput" name="data[id_type]" class="{{ $fieldClass }}">
                                <option value="">Select ID type</option>
                                @foreach($idTypes as $option)
                                    <option value="{{ $option }}" @selected($dataValue('id_type') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="idTypeOtherWrap" class="{{ $dataValue('id_type') === 'Others' ? '' : 'hidden' }}">
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('id_type_other', 'Specify Other ID Type') }}</label>
                            <input type="text" name="data[id_type_other]" value="{{ $dataValue('id_type_other') }}" class="{{ $fieldClass }}">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('id_number', 'ID Number') }}</label>
                            <input type="text" name="data[id_number]" value="{{ $dataValue('id_number') }}" class="{{ $fieldClass }}">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">{{ $supplierLabel('date_signed', 'Date Signed') }}</label>
                            <input type="text" name="data[date_signed]" value="{{ old('data.date_signed', now()->format('Y-m-d H:i')) }}" class="{{ $fieldClass }} bg-gray-100" readonly>
                        </div>
                    </div>
                </section>

                <div class="flex items-center justify-between gap-4 border-t pt-5">
                    <p class="text-sm text-gray-500">Supplier-facing completion page for the Finance Operations module.</p>
                    <button id="supplierCompletionSubmit" type="submit" class="rounded-md px-5 py-2 text-white transition {{ $isCompleted ? 'cursor-not-allowed bg-gray-400' : 'bg-blue-600 hover:bg-blue-700' }}">
                        {{ $isCompleted ? 'Submitted' : 'Submit Completion' }}
                    </button>
                </div>
                </fieldset>
            </form>
        </div>
    </div>

    <script>
        const attachmentRules = @js($attachmentRules);
        const existingCategories = @js($existingCategories);
        const entityTypeInput = document.getElementById('entityTypeInput');
        const paymentMethodInput = document.getElementById('paymentMethodInput');
        const idTypeInput = document.getElementById('idTypeInput');
        const supplierCategoryOtherWrap = document.getElementById('supplierCategoryOtherWrap');
        const attachmentTarget = document.getElementById('requiredAttachmentFields');

        function attachmentSlug(label) {
            return String(label || '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        }

        function setWrapVisibility(id, show) {
            const wrap = document.getElementById(id);
            if (!wrap) return;
            wrap.classList.toggle('hidden', !show);
            wrap.querySelectorAll('input, select, textarea').forEach((input) => {
                input.disabled = !show;
            });
        }

        function registrationLabel(entityType) {
            if (['Corporation', 'One Person Corporation (OPC)', 'Partnership', 'Foreign Company', 'Non-Profit Organization'].includes(entityType)) {
                return 'SEC Registration No.';
            }
            if (entityType === 'Sole Proprietorship') return 'DTI Registration No.';
            if (entityType === 'Cooperative') return 'CDA Registration No.';
            if (['Freelancer / Individual Professional', 'Independent Contractor'].includes(entityType)) return 'Professional License No.';
            if (entityType === 'Government Agency') return 'Government ID No.';
            return 'Registration Number';
        }

        function syncConditionalFields() {
            const entityType = entityTypeInput?.value || '';
            const paymentMethod = paymentMethodInput?.value || '';
            const idType = idTypeInput?.value || '';
            const supplierCategories = Array.from(document.querySelectorAll('input[name="data[supplier_category][]"]:checked')).map((input) => input.value);
            const registrationNumberLabel = document.getElementById('registrationNumberLabel');
            setWrapVisibility('corporationTypeWrap', ['Corporation', 'One Person Corporation (OPC)', 'Foreign Company'].includes(entityType));
            setWrapVisibility('entityTypeOtherWrap', entityType === 'Others');
            setWrapVisibility('supplierCategoryOtherWrap', supplierCategories.includes('Others'));
            setWrapVisibility('paymentTermsOtherWrap', (document.querySelector('select[name="data[payment_terms]"]')?.value || '') === 'Others');
            setWrapVisibility('onlinePaymentWrap', paymentMethod === 'Online Payment');
            setWrapVisibility('paymentMethodOtherWrap', paymentMethod === 'Others');
            setWrapVisibility('idTypeOtherWrap', idType === 'Others');
            if (registrationNumberLabel) {
                registrationNumberLabel.textContent = registrationLabel(entityType);
            }
        }

        function renderAttachmentFields() {
            if (!attachmentTarget) return;
            const labels = attachmentRules[entityTypeInput?.value || ''] || [];
            attachmentTarget.innerHTML = labels.length
                ? labels.map((label) => {
                    const slug = attachmentSlug(label);
                    const uploaded = existingCategories.includes(label);
                    return `
                        <div class="rounded-lg border border-blue-100 bg-white p-3">
                            <label class="block text-sm font-medium text-gray-900">${label}${uploaded ? ' <span class="text-xs font-normal text-green-600">(uploaded)</span>' : ''}</label>
                            <input type="hidden" name="attachment_labels[${slug}]" value="${label}">
                            <input type="file" name="attachments[${slug}]" class="mt-2 w-full rounded-md border border-blue-200 bg-blue-50 p-2 text-sm">
                        </div>
                    `;
                }).join('')
                : '<p class="text-sm text-gray-500 md:col-span-2">Select an entity type to show the required document uploads.</p>';
        }

        entityTypeInput?.addEventListener('change', () => {
            syncConditionalFields();
            renderAttachmentFields();
        });
        paymentMethodInput?.addEventListener('change', syncConditionalFields);
        idTypeInput?.addEventListener('change', syncConditionalFields);
        document.querySelector('select[name="data[payment_terms]"]')?.addEventListener('change', syncConditionalFields);
        document.querySelectorAll('input[name="data[supplier_category][]"]').forEach((input) => {
            input.addEventListener('change', syncConditionalFields);
        });
        syncConditionalFields();
        renderAttachmentFields();

        document.getElementById('supplierCompletionForm')?.addEventListener('submit', (event) => {
            const form = event.currentTarget;
            if (!form.checkValidity()) return;

            const submitButton = document.getElementById('supplierCompletionSubmit');
            if (!submitButton || submitButton.disabled) {
                event.preventDefault();
                return;
            }

            submitButton.disabled = true;
            submitButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            submitButton.classList.add('cursor-not-allowed', 'bg-gray-400');
            submitButton.textContent = 'Submitting...';
        });
    </script>
</body>
</html>

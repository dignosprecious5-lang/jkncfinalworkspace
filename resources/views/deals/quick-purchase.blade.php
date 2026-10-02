@php
    $employeeList = ($employees ?? collect())->map(function($emp) {
        $pos = $emp->employeeProfile?->position ?? $emp->role;
        $dept = '';
        $sub = implode(' • ', array_filter([$emp->employee_id, $pos, $dept]));
        return [
            'id' => $emp->id,
            'name' => $emp->name,
            'subtitle' => $sub ?: 'Employee',
        ];
    })->values();

    $dealServiceMap = [
        'Accounting & Compliance Advisory' => [
            'AFS Preparation',
            'Accounting Services',
            'Audit Support / Coordination',
            'BIR RDO Compliance Representative',
            'BIR Registration Assistance (L)',
            'BIR Registration Assistance (M)',
            'BIR Registration Assistance (S)',
            'BIR Registration- Update/ Change Information',
            'Bir Open Case Resolution',
            'Bookkeeping Services',
            'Business Permit',
            'On-site Profit and Loss Review',
            'SAWT Preparation and eSubmission Validation',
            'Tax Filing & Compliance (BIR)',
            'Transfer of BIR Registration from RDO 80 to RDO 81',
            'Transfer of Shares of Stock Assistance',
        ],
        'Corporate & Regulatory Advisory' => [
            'AMLC Registration and Compliance Officer Setup Assistance',
            'BIR Registration Assistance (MM)',
            'Bank Opening',
            'Business Registration (SEC / DTI / BIR)',
            'Corporate Secretary Services (M)',
            'Corporation Formation & Registration Assistance (L)',
            'Corporation Formation & Registration Assistance (M)',
            'Corporation Formation & Registration Assistance (MM)',
            'Corporation Formation & Registration Assistance (S)',
            'Foreign Business Entry Support',
            'LGU Compliance Representative',
            'Loan Application Assistance',
            'New / Renewal LGU City/Municipality Business Registration Assistance — Complex',
            'New LGU City/Municipality Business Registration Assistance — Non-Complex',
            'Regulatory Compliance',
            'SEC Compliance Representative',
        ],
        'Learning & Capability Development' => [
            'Accounting & Compliance Training',
            'Business & Strategy Training',
            'Client Capability Development Programs',
            'Corporate Governance Workshops',
            'JKNC Academy Courses',
        ],
        'Service Add-Ons' => [
            'Travel Credits — Cebu City, Mandaue City & Lapu-Lapu City',
            'Travel Credits — Metro Cebu',
        ],
        'Business Strategy & Process Advisory' => [
            'Digital Transformation',
            'Domain Purchase and Setup Assistance',
            'Financial Planning & Analysis',
            'Organizational Structuring',
            'Process Improvement / SOP Development',
        ],
        'Governance & Policy Advisory' => [
            'Board Resolutions & Minutes',
            'Corporate Officers Services',
            'Corporate Secretary Services',
            'Middle Management Advisory Consultation',
            'Policy Development (HR, Finance, Ops)',
            'Risk & Internal Control Setup',
        ],
        'People & Talent Solutions' => [
            'Executive / Virtual Assistant Support',
            'HR Documentation & Contracts',
            'HR Structuring & Organization Design',
            'KPI & Performance Management Systems',
            'Managed Administrative Support Services',
            'Recruitment & Hiring Support',
        ],
        'Strategic Situations Advisory' => [
            'Business Restructuring Strategy',
            'Corporate Deadlock Resolution',
            'Crisis Assessment & Stabilization',
            'High-Risk / Complex Case Advisory',
            'Middle Management Advisory Consultation',
            'Stakeholder Negotiation Support',
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Add Inquiry</title>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        if (window.self !== window.top) {
            document.documentElement.classList.add('in-iframe');
        }
    </script>

    <style>
        * {
            box-sizing: border-box;
        }

        [x-cloak] {
            display: none !important;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #ffffff;
            color: #172033;
        }

        .in-iframe .header {
            display: none !important;
        }

        .page {
            min-height: 100vh;
            background: #ffffff;
        }

        .header {
            height: 84px;
            border-top: 3px solid #3f3f46;
            border-bottom: 1px solid #e5e7eb;
            padding: 20px 32px;
            position: sticky;
            top: 0;
            background: #ffffff;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-title {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            color: #0f172a;
        }

        .header-subtitle {
            margin-top: 3px;
            color: #64748b;
            font-size: 13px;
        }

        .close {
            color: #64748b;
            font-size: 24px;
            text-decoration: none;
            line-height: 1;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.15s ease;
        }

        .close:hover {
            color: #0f172a;
            background: #f1f5f9;
        }

        .content {
            padding: 24px 32px 90px;
            max-width: 1000px;
            margin: auto;
        }

        .form-shell {
            width: 100%;
        }

        /* SECTION STYLES MATCHING ORDO DEALS */
        .section {
            border: 1px solid #dfe5ec;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 18px;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
        }

        .deal-section {
            width: 100%;
        }

        .section-title {
            font-size: 15px;
            color: #172033;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .section-description {
            font-size: 11px;
            color: #94a3b8;
            margin-bottom: 15px;
        }

        .label {
            font-size: 12px;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 6px;
            font-weight: 500;
            line-height: 1.2;
        }

        .customer-type {
            margin-bottom: 0;
        }

        .radio-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            width: 100%;
        }

        .choice {
            border: 1px solid #dfe5ec;
            border-radius: 8px;
            padding: 11px 12px;
            min-height: 38px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #64748b;
            cursor: pointer;
            background: #ffffff;
            transition: all 0.15s ease;
        }

        .choice:hover {
            border-color: #b8c8df;
        }

        .choice input[type="radio"] {
            width: auto;
            margin: 0;
            accent-color: #2563eb;
        }

        .choice:has(input[type="radio"]:checked) {
            border-color: #2563eb;
            background: #f0f7ff;
            color: #1e293b;
            font-weight: 500;
        }

        .disabled-box {
            border: 1px dashed #dbe1e8;
            background: #fafafa;
            border-radius: 9px;
            padding: 16px;
            color: #a0a9b5;
            font-size: 11px;
        }

        .sub-label {
            color: #64748b;
            font-size: 12px;
            margin: 15px 0 8px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .required {
            color: #dc2626;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="number"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            border: 1px solid #d9dfe7;
            border-radius: 8px;
            background: #ffffff;
            padding: 10px 12px;
            color: #334155;
            outline: none;
            font-family: inherit;
            font-size: 12px;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        select {
            height: 38px;
            padding: 0 12px;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #93b4f5;
            box-shadow: 0 0 0 2px #eff6ff;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            width: 100%;
        }

        .metadata {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            width: 100%;
        }

        .metadata-item .meta-label {
            color: #94a3b8;
            text-transform: uppercase;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-height: 18px;
            line-height: 1.2;
        }

        .metadata-item .meta-value {
            color: #475569;
            font-size: 12px;
        }

        .full {
            grid-column: 1 / -1;
            width: 100%;
        }

        .service-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 7px;
            width: 100%;
        }

        .check {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            min-height: 36px;
            padding: 8px 11px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            font-size: 12px;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .check:hover {
            border-color: #cbd5e1;
        }

        .check input {
            width: auto;
            margin: 0;
            accent-color: #2563eb;
        }

        .check:has(input[type="checkbox"]:checked) {
            border-color: #2563eb;
            background: #f0f7ff;
            color: #1e293b;
        }

        .footer {
            height: 66px;
            background: #ffffff;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 0 32px;
            gap: 10px;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 30;
            box-shadow: 0 -4px 14px rgba(0, 0, 0, 0.05);
        }

        .btn {
            height: 38px;
            border-radius: 7px;
            padding: 0 16px;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            transition: all 0.15s ease;
        }

        .btn-cancel {
            background: white;
            border: 1px solid #d1d9e3;
            color: #334155;
            text-decoration: none;
        }

        .btn-cancel:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .btn-save {
            border: none;
            background: #2563eb;
            color: white;
            font-weight: 600;
        }

        .btn-save:hover {
            background: #1d4ed8;
        }

        .btn-save:disabled {
            background: #93c5fd;
            cursor: not-allowed;
        }

        /* Tooltip */
        .ordo-tooltip-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #64748b;
            font-size: 9.5px;
            font-weight: 700;
            font-style: italic;
            font-family: serif;
            cursor: help;
            line-height: 1;
        }

        /* Modal Overlay */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(2px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 16px;
        }

        .modal-box {
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            width: 100%;
            max-width: 540px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
        }

        .modal-header {
            padding: 18px 22px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .modal-body {
            padding: 22px;
        }

        .modal-footer {
            padding: 14px 22px;
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
            border-bottom-left-radius: 14px;
            border-bottom-right-radius: 14px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .alert-success-badge {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .alert-error-badge {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 14px;
        }

        /* Multi-column grid preserved in Drawer mode matching ORDO Deals */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .radio-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .metadata {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .service-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        @media (max-width: 800px) {
            .content {
                padding: 16px 20px 82px;
            }
            .header {
                padding: 16px 20px;
            }
            .footer {
                padding: 0 20px;
            }
        }
    </style>
</head>
<body>

<div
    class="page"
    x-data="addInquiryFormState(
        @js($accounts ?? collect()),
        @js($companies ?? collect()),
        @js($contacts ?? collect()),
        @js($dealServiceMap ?? []),
        @js(old('account_id', $selectedAccountId ?? ''))
    )"
>

    @if(!request()->boolean('drawer'))
        {{-- TOP HEADER (Only for standalone full-page view) --}}
        <header class="header">
            <div>
                <h1 class="header-title">Add Inquiry</h1>
                <div class="header-subtitle">Create a new inquiry for a client.</div>
            </div>
            <a href="{{ route('deals.index') }}" class="close" title="Close" @click="handleCancel($event)">&times;</a>
        </header>
    @endif

    {{-- MAIN INQUIRY FORM --}}
    <form
        method="POST"
        action="{{ route('deals.quick-purchase.store') }}"
        id="addInquiryForm"
        @submit="handleSubmit($event)"
    >
        @csrf

        @if(request()->boolean('drawer'))
            <input type="hidden" name="drawer" value="1">
        @endif

        @if(request('return_to'))
            <input type="hidden" name="return_to" value="{{ request('return_to') }}">
        @endif

        {{-- Automatic Pipeline Stage = Inquiry --}}
        <input type="hidden" name="pipeline_stage" value="Inquiry">

        <main class="content form-shell">

            {{-- SUCCESS NOTIFICATION AFTER ACCOUNT CREATION --}}
            <div
                x-show="successMessage"
                x-cloak
                x-transition
                class="alert-success-badge"
            >
                <svg style="width:16px; height:16px; flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span x-text="successMessage"></span>
            </div>

            @if($errors->any())
                <div class="alert-error-badge">
                    <ul style="margin:0; padding-left:18px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- =========================================================
                 1. CUSTOMER & ACCOUNT
            ========================================================== -->
            <section class="section deal-section client-account bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Customer &amp; Account
                </div>

                <div class="section-description">
                    Select the customer and account for this deal.
                </div>

                <!-- DEAL TYPE -->
                <div class="customer-type" style="margin-bottom: 0;">
                    <label class="label" data-tooltip="Identifies whether this deal is for an individual or a business client.">
                        Deal Type <span class="ordo-tooltip-icon" tabindex="0">i</span>
                    </label>

                    <div class="radio-grid-2">
                        <label class="choice" data-tooltip="For an existing business or company account.">
                            <input
                                type="radio"
                                name="customer_type"
                                value="Business"
                                x-model="customerType"
                                @change="handleCustomerTypeChange()"
                            >
                            Business
                        </label>

                        <label class="choice" data-tooltip="For an existing individual client.">
                            <input
                                type="radio"
                                name="customer_type"
                                value="Individual"
                                x-model="customerType"
                                @change="handleCustomerTypeChange()"
                            >
                            Individual
                        </label>
                    </div>
                </div>

                <!-- BUSINESS -->
                <div x-show="customerType === 'Business'" x-cloak>

                    <div style="margin-top: 18px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <label class="label" style="margin:0;" data-tooltip="Select the existing account associated with this deal.">
                                    Account <span style="color:#dc2626;">*</span> <span class="ordo-tooltip-icon" tabindex="0">i</span>
                                </label>
                                <span style="font-size:10px; font-weight:600; letter-spacing:.04em; color:#475569; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:999px; padding:4px 8px;">
                                    BUSINESS ACCOUNT
                                </span>
                            </div>

                            <button
                                type="button"
                                class="btn"
                                style="height:30px; padding:0 10px; border:1px solid #d1d9e3; background:#fff; color:#334155; font-size:11px; cursor:pointer;"
                                @click.prevent=""
                            >
                                + Create Account
                            </button>
                        </div>

                        <select
                            name="account_id"
                            x-model="accountId"
                            @change="loadAccount()"
                            x-bind:disabled="customerType !== 'Business'"
                            x-bind:required="customerType === 'Business'"
                            data-tooltip="Select the existing account associated with this deal."
                        >
                            <option value="">Select Account</option>

                            @foreach($accounts ?? collect() as $account)
                                @php
                                    $type = strtolower((string) ($account->account_type ?? ''));
                                    $code = (string) ($account->account_code ?? '');
                                    $isBusiness = ($type === 'business' || str_contains($type, 'business') || str_contains($type, 'company') || str_starts_with($code, 'ACC-BUS') || str_starts_with($code, 'BUS-') || !empty($account->company_id)) && $type !== 'individual' && !str_starts_with($code, 'ACC-IND');
                                @endphp
                                @if($isBusiness)
                                    <option value="{{ $account->id }}">
                                        {{ $account->account_code }} — {{ $account->account_name }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <input type="hidden" name="company_id" x-model="companyId">

                    <div style="margin-top:18px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:8px;">
                            <label class="label" style="margin:0;" data-tooltip="Select the main contact responsible for this business account.">
                                Primary Contact <span style="color:#dc2626;">*</span> <span class="ordo-tooltip-icon" tabindex="0">i</span>
                            </label>

                            <button
                                type="button"
                                class="btn"
                                style="height:30px; padding:0 10px; border:1px solid #d1d9e3; background:#fff; color:#334155; font-size:11px; cursor:pointer;"
                                @click.prevent=""
                            >
                                + Add Contact
                            </button>
                        </div>

                        <select
                            name="contact_id"
                            x-model="contactId"
                            @change="loadContact()"
                            x-bind:disabled="customerType !== 'Business'"
                            x-bind:required="customerType === 'Business'"
                            data-tooltip="Select the main contact responsible for this business account."
                        >
                            <option value="">Select Contact</option>

                            <template x-for="contact in filteredContacts()" :key="contact.id">
                                <option
                                    :value="contact.id"
                                    x-text="contact.contact_code + ' — ' + contact.first_name + ' ' + contact.last_name"
                                ></option>
                            </template>
                        </select>
                    </div>

                    <!-- COMPANY INFORMATION -->
                    <div
                        style="margin-top:18px; border:1px solid #e2e8f0; border-radius:11px; padding:14px; background:#fafbfc;"
                        x-show="companyId"
                        x-cloak
                    >
                        <div style="font-size:12px; font-weight:600; color:#334155; margin-bottom:12px;" data-tooltip="Shows the selected company information from the client record.">
                            Company Information <span class="ordo-tooltip-icon" tabindex="0">i</span>
                        </div>

                        <div class="grid-2">
                            <div>
                                <div class="metadata-item">
                                    <div class="meta-label" data-tooltip="Select the company associated with this business deal.">Company</div>
                                    <div class="meta-value" x-text="findCompany()?.company_name || '—'"></div>
                                </div>
                            </div>

                            <div>
                                <div class="metadata-item">
                                    <div class="meta-label">Company Code</div>
                                    <div class="meta-value" x-text="findCompany()?.company_code || '—'"></div>
                                </div>
                            </div>

                            <div class="full">
                                <div class="metadata-item">
                                    <div class="meta-label">Address</div>
                                    <div class="meta-value" x-text="findCompany()?.address || '—'"></div>
                                </div>
                            </div>

                            <div>
                                <div class="metadata-item">
                                    <div class="meta-label">Email</div>
                                    <div class="meta-value" x-text="findCompany()?.email || '—'"></div>
                                </div>
                            </div>

                            <div>
                                <div class="metadata-item">
                                    <div class="meta-label">Phone</div>
                                    <div class="meta-value" x-text="findCompany()?.phone || '—'"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- INDIVIDUAL -->
                <div x-show="customerType === 'Individual'" x-cloak>

                    <div style="margin-top: 18px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <label class="label" style="margin:0;" data-tooltip="Select the existing account associated with this deal.">
                                    Account <span style="color:#dc2626;">*</span> <span class="ordo-tooltip-icon" tabindex="0">i</span>
                                </label>
                                <span style="font-size:10px; font-weight:600; letter-spacing:.04em; color:#475569; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:999px; padding:4px 8px;">
                                    INDIVIDUAL ACCOUNT
                                </span>
                            </div>

                            <button
                                type="button"
                                class="btn"
                                style="height:30px; padding:0 10px; border:1px solid #d1d9e3; background:#fff; color:#334155; font-size:11px; cursor:pointer;"
                                @click.prevent=""
                            >
                                + Create Account
                            </button>
                        </div>

                        <select
                            name="account_id"
                            x-model="accountId"
                            @change="loadIndividualAccount()"
                            x-bind:disabled="customerType !== 'Individual'"
                            x-bind:required="customerType === 'Individual'"
                            data-tooltip="Select the existing account associated with this deal."
                        >
                            <option value="">Select Account</option>

                            @foreach($accounts ?? collect() as $account)
                                @php
                                    $type = strtolower((string) ($account->account_type ?? ''));
                                    $code = (string) ($account->account_code ?? '');
                                    $isIndividual = ($type === 'individual' || str_contains($type, 'individual') || str_starts_with($code, 'ACC-IND') || str_starts_with($code, 'IND-')) && $type !== 'business' && !str_starts_with($code, 'ACC-BUS');
                                @endphp
                                @if($isIndividual)
                                    <option value="{{ $account->id }}">
                                        {{ $account->account_code }} — {{ $account->account_name }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <input type="hidden" name="contact_id" x-model="contactId">

                    <!-- INDIVIDUAL CONTACT INFORMATION -->
                    <div
                        style="margin-top:18px; border:1px solid #e2e8f0; border-radius:11px; padding:14px; background:#fafbfc;"
                        x-show="accountId"
                        x-cloak
                    >
                        <div style="font-size:12px; font-weight:600; color:#334155; margin-bottom:12px;" data-tooltip="Shows the selected contact's current information from the client record.">
                            Contact Information <span class="ordo-tooltip-icon" tabindex="0">i</span>
                        </div>

                        <div class="metadata">
                            <div class="metadata-item">
                                <div class="meta-label">Name</div>
                                <div class="meta-value">
                                    <span x-text="selectedContactName() || '—'"></span>
                                </div>
                            </div>

                            <div class="metadata-item">
                                <div class="meta-label">Email</div>
                                <div class="meta-value" x-text="findContact()?.email || '—'"></div>
                            </div>

                            <div class="metadata-item">
                                <div class="meta-label">Mobile</div>
                                <div class="meta-value" x-text="findContact()?.mobile_number || '—'"></div>
                            </div>

                            <div class="metadata-item">
                                <div class="meta-label">Address</div>
                                <div class="meta-value" x-text="findContact()?.address || '—'"></div>
                            </div>
                        </div>
                    </div>

                </div>

                <div
                    x-show="!customerType"
                    x-cloak
                    class="disabled-box"
                    style="margin-top:14px;"
                >
                    Select Business or Individual to continue.
                </div>

            </section>

            <!-- =========================================================
                 2. DEAL INFORMATION
            ========================================================== -->
            <section class="section deal-section deal-information bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Deal Information
                </div>

                <div class="section-description">
                    Auto-generated metadata appears here after the deal is saved.
                </div>

                <div class="metadata">

                    <div class="metadata-item">
                        <div class="meta-label" data-tooltip="System-generated identifier used to track this deal.">
                            Deal Code <span class="ordo-tooltip-icon" tabindex="0">i</span>
                        </div>
                        <div class="meta-value">
                            @if(isset($deal) && $deal->deal_code)
                                {{ $deal->deal_code }}
                            @else
                                Auto-generated after save
                            @endif
                        </div>
                    </div>

                    <div class="metadata-item">
                        <div class="meta-label">
                            Created By
                        </div>
                        <div class="meta-value">
                            {{ optional($deal ?? null)->created_by ?: (auth()->user()?->name ?? 'Administrator') }}
                        </div>
                    </div>

                    <div class="metadata-item">
                        <div class="meta-label">
                            Created At
                        </div>
                        <div class="meta-value">
                            @if(isset($deal) && $deal->created_at)
                                {{ $deal->created_at->timezone('Asia/Manila')->format('F d • Y \a\t h:i:s A') }}
                            @else
                                <span
                                    x-data="{
                                        now: '',
                                        formatDate() {
                                             const d = new Date();
                                             const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                                             const month = months[d.getMonth()];
                                             const day = String(d.getDate()).padStart(2, '0');
                                             const year = d.getFullYear();
                                             let hours = d.getHours();
                                             const minutes = String(d.getMinutes()).padStart(2, '0');
                                             const seconds = String(d.getSeconds()).padStart(2, '0');
                                             const ampm = hours >= 12 ? 'PM' : 'AM';
                                             hours = hours % 12;
                                             hours = hours ? String(hours).padStart(2, '0') : '12';
                                             this.now = `${month} ${day} • ${year} at ${hours}:${minutes}:${seconds} ${ampm}`;
                                        }
                                    }"
                                    x-init="formatDate(); setInterval(() => formatDate(), 1000)"
                                    x-text="now"
                                >{{ now()->timezone('Asia/Manila')->format('F d • Y \a\t h:i:s A') }}</span>
                            @endif
                        </div>
                    </div>

                </div>

            </section>

            <!-- =========================================================
                 3. CUSTOMER INQUIRY
            ========================================================== -->
            <section class="section deal-section customer-inquiry bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">
                <div class="section-title">
                    Customer Inquiry
                </div>

                <div class="section-description">
                    Record the customer's inquiry, request, or initial concern.
                </div>

                <div>
                    <label class="label">
                        What is the customer inquiring about?
                        <span class="ordo-tooltip-icon" tabindex="0" data-tooltip="Describe what the customer is asking about or looking for.">i</span>
                    </label>
                    <textarea
                        name="scope_of_work"
                        x-model="scopeOfWork"
                        placeholder="Describe what the customer is asking about or looking for..."
                        style="min-height: 100px;"
                    ></textarea>
                </div>
            </section>

        </main>

        {{-- FOOTER ACTIONS --}}
        <footer class="footer">
            <a
                href="{{ route('deals.index') }}"
                class="btn btn-cancel"
                @click="handleCancel($event)"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="btn btn-save"
                :disabled="isSubmitting || !accountId || (customerType === 'Business' && !contactId)"
            >
                <span x-show="!isSubmitting">Create Inquiry</span>
                <span x-show="isSubmitting" x-cloak>Creating Inquiry...</span>
            </button>
        </footer>
    </form>

    {{-- =============================================================
         MODAL: ADD ACCOUNT (+ Add Account Workflow)
    ============================================================== --}}


    {{-- =============================================================
         MODAL: ADD CONTACT (+ Add Contact Workflow)
    ============================================================== --}}
    <div
        class="modal-overlay"
        x-show="contactModalOpen"
        x-cloak
        x-transition
        @click.self="contactModalOpen = false"
    >
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">Create Contact</h3>
                <button
                    type="button"
                    class="close"
                    @click="contactModalOpen = false"
                    style="border:none; background:transparent; cursor:pointer;"
                >&times;</button>
            </div>

            <div class="modal-body">
                <div x-show="contactError" x-cloak class="alert-error-badge" x-text="contactError"></div>

                <div class="grid-2" style="margin-bottom:14px;">
                    <div>
                        <label class="label">First Name <span class="required">*</span></label>
                        <input
                            type="text"
                            x-model="newContact.first_name"
                            placeholder="First Name"
                            required
                        >
                    </div>
                    <div>
                        <label class="label">Last Name <span class="required">*</span></label>
                        <input
                            type="text"
                            x-model="newContact.last_name"
                            placeholder="Last Name"
                            required
                        >
                    </div>
                </div>

                <div class="grid-2" style="margin-bottom:14px;">
                    <div>
                        <label class="label">Middle Name</label>
                        <input
                            type="text"
                            x-model="newContact.middle_name"
                            placeholder="Middle Name"
                        >
                    </div>
                    <div>
                        <label class="label">Position</label>
                        <input
                            type="text"
                            x-model="newContact.position"
                            placeholder="e.g. Operations Manager"
                        >
                    </div>
                </div>

                <div class="grid-2" style="margin-bottom:14px;">
                    <div>
                        <label class="label">Email Address</label>
                        <input
                            type="email"
                            x-model="newContact.email"
                            placeholder="e.g. contact@email.com"
                        >
                    </div>
                    <div>
                        <label class="label">Mobile Number</label>
                        <input
                            type="tel"
                            x-model="newContact.mobile_number"
                            placeholder="e.g. 0917 123 4567"
                        >
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <label class="label">Address</label>
                    <input
                        type="text"
                        x-model="newContact.address"
                        placeholder="Address"
                    >
                </div>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-cancel"
                    @click="contactModalOpen = false"
                >Cancel</button>

                <button
                    type="button"
                    class="btn btn-save"
                    :disabled="contactSaving"
                    @click="submitCreateContact()"
                >
                    <span x-show="!contactSaving">Save Contact</span>
                    <span x-show="contactSaving" x-cloak>Saving...</span>
                </button>
            </div>
        </div>
    </div>

</div>

{{-- AlpineJS State Controller --}}
<script>
    function addInquiryFormState(initialAccounts, initialCompanies, initialContacts, serviceMap, initialAccountId) {
        return {
            accounts: initialAccounts || [],
            companies: initialCompanies || [],
            contacts: initialContacts || [],
            serviceMap: serviceMap || {},

            customerType: '{{ old('customer_type', 'Business') }}',
            accountId: initialAccountId || '',
            companyId: '',
            contactId: '',
            selectedServiceAreas: [],
            selectedServices: [],
            scopeOfWork: '',

            isSubmitting: false,
            successMessage: '',

            // Account Modal state
            accountModalOpen: false,
            accountSaving: false,
            accountError: '',
            newAccount: {
                company_name: '',
                industry: '',
                company_email: '',
                company_phone: '',
                company_address: '',
                salutation: '',
                first_name: '',
                middle_name: '',
                last_name: '',
                name_extension: '',
                contact_email: '',
                contact_mobile: '',
                contact_position: '',
                contact_address: '',
            },

            // Contact Modal state
            contactModalOpen: false,
            contactSaving: false,
            contactError: '',
            newContact: {
                salutation: '',
                first_name: '',
                middle_name: '',
                last_name: '',
                name_extension: '',
                email: '',
                mobile_number: '',
                position: '',
                address: '',
            },

            init() {
                if (!this.customerType) {
                    this.customerType = 'Business';
                }
                if (this.accountId) {
                    const acc = this.findAccount();
                    if (acc) {
                        const type = String(acc.account_type || '').toLowerCase();
                        if (type.includes('individual') || acc.individual_contact_id) {
                            this.customerType = 'Individual';
                            this.loadIndividualAccount();
                        } else {
                            this.customerType = 'Business';
                            this.loadAccount();
                        }
                    }
                }
            },

            goToCreateAccount(type = 'Business') {
                const isDrawer = new URLSearchParams(window.location.search).get('drawer') === '1' || (window.self !== window.top);
                let targetUrl = "{{ route('deals.create') }}";
                if (isDrawer) {
                    targetUrl += "?drawer=1&customer_type=" + encodeURIComponent(type);
                    if (window.parent && window.parent.document) {
                        const titleEl = window.parent.document.getElementById('drawerTitle');
                        if (titleEl) titleEl.textContent = 'Create Deal';
                        const subEl = window.parent.document.getElementById('drawerSubtitle');
                        if (subEl) subEl.textContent = 'Select an existing client, then complete the consulting and deal form.';
                    }
                } else {
                    targetUrl += "?customer_type=" + encodeURIComponent(type);
                }
                window.location.href = targetUrl;
            },

            handleCustomerTypeChange() {
                this.accountId = '';
                this.contactId = '';
                this.companyId = '';
            },

            findAccount() {
                return this.accounts.find(a => String(a.id) === String(this.accountId));
            },

            findCompany() {
                return this.companies.find(c => String(c.id) === String(this.companyId));
            },

            findContact() {
                return this.contacts.find(c => String(c.id) === String(this.contactId));
            },

            filteredAccounts() {
                if (this.customerType === 'Business') {
                    return this.accounts.filter(a => {
                        const type = String(a.account_type || '').toLowerCase();
                        const code = String(a.account_code || '');
                        return (type.includes('business') || type.includes('company') || code.startsWith('ACC-BUS') || code.startsWith('BUS-') || a.company_id) && !type.includes('individual') && !code.startsWith('ACC-IND');
                    });
                } else if (this.customerType === 'Individual') {
                    return this.accounts.filter(a => {
                        const type = String(a.account_type || '').toLowerCase();
                        const code = String(a.account_code || '');
                        return (type.includes('individual') || code.startsWith('ACC-IND') || code.startsWith('IND-')) && !type.includes('business') && !code.startsWith('ACC-BUS');
                    });
                }
                return this.accounts;
            },

            loadAccount() {
                const account = this.findAccount();
                if (!account) {
                    this.companyId = '';
                    this.contactId = '';
                    return;
                }

                this.companyId = account.company_id || '';
                const matchingContacts = this.contacts.filter(c => String(c.company_id) === String(this.companyId));
                if (matchingContacts.length >= 1) {
                    this.contactId = matchingContacts[0].id;
                } else {
                    this.contactId = '';
                }
            },

            loadIndividualAccount() {
                const account = this.findAccount();
                if (!account) {
                    this.contactId = '';
                    this.companyId = '';
                    return;
                }

                this.companyId = '';
                if (account.individual_contact_id) {
                    this.contactId = account.individual_contact_id;
                } else {
                    const matchingContact = this.contacts.find(c =>
                        c.first_name && account.account_name && account.account_name.toLowerCase().includes(c.first_name.toLowerCase())
                    );
                    this.contactId = matchingContact ? matchingContact.id : '';
                }
            },

            loadContact() {
                // contact loaded
            },

            filteredContacts() {
                if (this.customerType === 'Business') {
                    if (this.companyId) {
                        return this.contacts.filter(c => String(c.company_id) === String(this.companyId));
                    }
                    return this.contacts.filter(c => c.company_id);
                } else {
                    if (this.contactId) {
                        return this.contacts.filter(c => String(c.id) === String(this.contactId));
                    }
                    return this.contacts;
                }
            },

            selectedContactName() {
                const con = this.findContact();
                if (!con) return '';
                return [con.salutation, con.first_name, con.middle_name, con.last_name, con.name_extension]
                    .filter(Boolean)
                    .join(' ');
            },

            // ==========================================
            // ACCOUNT MODAL WORKFLOW
            // ==========================================
            openAddAccountModal() {
                this.accountError = '';
                this.newAccount = {
                    company_name: '',
                    industry: '',
                    company_email: '',
                    company_phone: '',
                    company_address: '',
                    salutation: '',
                    first_name: '',
                    middle_name: '',
                    last_name: '',
                    name_extension: '',
                    contact_email: '',
                    contact_mobile: '',
                    contact_position: '',
                    contact_address: '',
                };
                this.accountModalOpen = true;
            },

            async submitCreateAccount() {
                this.accountError = '';

                if (this.customerType === 'Business' && !this.newAccount.company_name.trim()) {
                    this.accountError = 'Company name is required.';
                    return;
                }

                if (this.customerType === 'Individual' && (!this.newAccount.first_name.trim() || !this.newAccount.last_name.trim())) {
                    this.accountError = 'First name and Last name are required.';
                    return;
                }

                this.accountSaving = true;

                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                    const payload = {
                        account_type: this.customerType,
                        ...this.newAccount
                    };

                    const response = await fetch('{{ route('accounts.quick-store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        this.accountError = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Unable to create account.');
                        this.accountSaving = false;
                        return;
                    }

                    // Success: Add newly created account & records
                    const createdAccount = data.account;
                    this.accounts.push(createdAccount);

                    if (data.company) {
                        this.companies.push(data.company);
                    }
                    if (data.contact) {
                        this.contacts.push(data.contact);
                    }

                    // Auto-select newly created account
                    this.accountId = createdAccount.id;
                    if (this.customerType === 'Business') {
                        this.loadAccount();
                    } else {
                        this.loadIndividualAccount();
                    }

                    if (data.contact) {
                        this.contactId = data.contact.id;
                    }

                    this.accountModalOpen = false;
                    this.accountSaving = false;
                    this.successMessage = 'Account created successfully.';

                    setTimeout(() => {
                        this.successMessage = '';
                    }, 4000);

                } catch (err) {
                    console.error(err);
                    this.accountError = 'An error occurred while saving account.';
                    this.accountSaving = false;
                }
            },

            // ==========================================
            // CONTACT MODAL WORKFLOW
            // ==========================================
            openAddContactModal(type = 'Business') {
                this.contactError = '';
                this.newContact = {
                    salutation: '',
                    first_name: '',
                    middle_name: '',
                    last_name: '',
                    name_extension: '',
                    email: '',
                    mobile_number: '',
                    position: '',
                    address: '',
                };
                this.contactModalOpen = true;
            },

            async submitCreateContact() {
                this.contactError = '';

                if (!this.newContact.first_name.trim() || !this.newContact.last_name.trim()) {
                    this.contactError = 'First name and Last name are required.';
                    return;
                }

                this.contactSaving = true;

                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                    const payload = {
                        contact_type: this.customerType,
                        company_id: this.companyId || null,
                        ...this.newContact
                    };

                    const response = await fetch('{{ route('contacts.quick-store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        this.contactError = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Unable to create contact.');
                        this.contactSaving = false;
                        return;
                    }

                    const createdContact = data.contact;
                    this.contacts.push(createdContact);

                    // Auto-select newly created contact
                    this.contactId = createdContact.id;

                    this.contactModalOpen = false;
                    this.contactSaving = false;
                    this.successMessage = 'Contact added successfully.';

                    setTimeout(() => {
                        this.successMessage = '';
                    }, 4000);

                } catch (err) {
                    console.error(err);
                    this.contactError = 'An error occurred while saving contact.';
                    this.contactSaving = false;
                }
            },

            handleCancel(e) {
                if (window.parent && window.parent !== window && window.parent.closeAddDealDrawer) {
                    e.preventDefault();
                    window.parent.closeAddDealDrawer();
                }
            },

            handleSubmit(e) {
                if (this.isSubmitting) {
                    e.preventDefault();
                    return false;
                }
                this.isSubmitting = true;
            }
        };
    }
</script>

<x-ordo-tooltip />

</body>
</html>

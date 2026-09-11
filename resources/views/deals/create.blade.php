<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Deal</title>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

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

        .page {
            min-height: 100vh;
            background: #ffffff;
        }

        .header {
            height: 94px;
            border-top: 3px solid #3f3f46;
            border-bottom: 1px solid #e5e7eb;
            padding: 22px 32px;
            position: sticky;
            top: 0;
            background: #ffffff;
            z-index: 20;
        }

        /*
        |--------------------------------------------------------------------------
        | DRAWER LAYOUT
        |--------------------------------------------------------------------------
        */

        @if(request()->boolean('drawer'))

    /* ============================================================
       DRAWER / FULL-SCREEN FORM
       Desktop: keep compact multi-column layout
       Mobile: switch to one-column full-screen layout
       ============================================================ */

    .content {
        width: 100%;
        max-width: 1000px;
        margin-left: auto;
        margin-right: auto;
        padding: 24px 32px 90px;
    }

    .grid-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .grid-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .grid-4 {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .radio-grid-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .radio-grid-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .metadata {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .pricing {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .approval-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .service-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .top-info {
        align-items: center;
        flex-direction: row;
    }

    .customer-type-row {
        align-items: center;
        flex-direction: row;
        gap: 24px;
    }

    .customer-type-row > .label {
        width: 64px;
        flex: 0 0 64px;
    }


    /* ============================================================
       DRAWER MOBILE
       ============================================================ */

    @media (max-width: 800px) {

        .page {
            width: 100%;
            min-height: 100vh;
        }

        .page > form {
            width: 100%;
            min-height: 100vh;
        }

        .page > form .content {
            width: 100%;
            max-width: none;
            padding: 16px 14px 82px;
            overflow-y: auto;
        }

        .section {
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 12px;
        }

        .grid-2,
        .grid-3,
        .grid-4,
        .radio-grid-2,
        .radio-grid-3,
        .metadata,
        .pricing,
        .approval-grid,
        .service-grid {
            grid-template-columns: 1fr;
        }

        .service-subservices {
            grid-template-columns: 1fr !important;
        }

        .top-info {
            align-items: flex-start;
            flex-direction: column;
            gap: 10px;
            padding-bottom: 14px;
            margin-bottom: 14px;
        }

        .top-info-title {
            width: 100%;
        }

        .owner {
            width: 100%;
            margin-left: 0;
            justify-content: flex-start;
        }

        .owner select {
            flex: 1;
        }

        .customer-type-row {
            align-items: stretch;
            flex-direction: column;
            gap: 8px;
        }

        .customer-type-row > .label {
            width: auto;
            flex: none;
        }

        .customer-type-row .radio-grid-2 {
            width: 100%;
        }

        .choice {
            min-height: 42px;
            padding: 10px;
        }

        .check {
            min-height: 40px;
            padding: 9px 10px;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            min-width: 620px;
        }

        .footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 30;
            height: 64px;
            padding: 0 14px;
            justify-content: stretch;
            background: #ffffff;
        }

        .footer .btn {
            flex: 1;
            justify-content: center;
        }

        .btn {
            height: 40px;
        }

        textarea {
            min-height: 90px;
        }

        .pricing-box {
            width: 100%;
        }

        .disabled-box {
            padding: 13px;
        }

        .close {
            right: 16px;
            top: 24px;
        }

    }

@endif

        .header-title {
            font-size: 23px;
            font-weight: 600;
            margin: 0;
        }

        .header-subtitle {
            margin-top: 4px;
            color: #64748b;
            font-size: 12px;
        }

        .close {
            position: absolute;
            right: 30px;
            top: 32px;
            color: #64748b;
            font-size: 22px;
            text-decoration: none;
        }

        .content {
            padding: 24px 32px 90px;
            max-width: 1000px;
            margin: auto;
        }

        .form-shell {
            width: 100%;
        }

        .top-info {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .top-info-title {
            flex: 1;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
        }

        .owner {
            border: 1px solid #dce2ea;
            border-radius: 22px;
            padding: 9px 13px;
            font-size: 11px;
            color: #334155;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            margin-left: auto;
        }

        .owner-dot {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #3b82f6;
            margin-right: 7px;
        }

        .owner select {
            width: auto;
            height: auto;
            padding: 0;
            border: 0;
            outline: 0;
            background: transparent;
            color: inherit;
            font: inherit;
        }

        .section {
            border: 1px solid #dfe5ec;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 18px;
            background: #ffffff;
        }

        .deal-section {
            width: 100%;
        }

        .deal-section > .section-title {
            color: #111827;
            font-size: 16px;
            font-weight: 600;
        }

        .deal-section > .section-description {
            color: #6b7280;
            font-size: 12px;
        }

        .deal-section input:not([type="radio"]):not([type="checkbox"]),
        .deal-section select,
        .deal-section textarea {
            border-color: #d1d5db;
            border-radius: 8px;
            font-size: 12px;
        }

        .deal-section input:not([type="radio"]):not([type="checkbox"]):focus,
        .deal-section select:focus,
        .deal-section textarea:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px #eef2ff;
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
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
        }

        input,
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

        /*
        |--------------------------------------------------------------------------
        | FORM GRID LAYOUTS
        |--------------------------------------------------------------------------
        */

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            width: 100%;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            width: 100%;
        }

        .grid-4 {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            width: 100%;
        }

        .radio-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            width: 100%;
        }

        .radio-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
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
        }

        .choice:hover {
            border-color: #b8c8df;
        }

        .choice input {
            width: auto;
            margin: 0;
        }

        .customer-type {
            margin-bottom: 18px;
        }

        .customer-type-row {
            display: flex;
            align-items: center;
            gap: 24px;
            margin-top: 14px;
        }

        .customer-type-row > .label {
            width: 64px;
            margin-bottom: 0;
            flex: 0 0 64px;
        }

        .customer-type-row .radio-grid-2 {
            flex: 1;
            min-width: 0;
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
            margin-bottom: 5px;
        }

        .metadata-item .meta-value {
            color: #475569;
            font-size: 12px;
        }

        .warning {
            margin-top: 8px;
            border: 1px solid #fbbf24;
            background: #fffbeb;
            color: #d97706;
            border-radius: 7px;
            padding: 9px 12px;
            font-size: 11px;
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
        }

        .check input {
            width: auto;
            margin: 0;
        }

        .sub-label {
            color: #64748b;
            font-size: 12px;
            margin: 15px 0 8px;
        }

        .disabled-box {
            border: 1px dashed #dbe1e8;
            background: #fafafa;
            border-radius: 9px;
            padding: 16px;
            color: #a0a9b5;
            font-size: 11px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        th,
        td {
            border: 1px solid #e5e7eb;
            padding: 9px 10px;
            text-align: left;
        }

        th {
            color: #94a3b8;
            font-size: 10px;
            text-transform: uppercase;
            background: #fafbfc;
        }

        td {
            color: #64748b;
        }

        td.center,
        th.center {
            text-align: center;
        }

        .fee-input {
            position: relative;
        }

        .fee-input span {
            position: absolute;
            left: 12px;
            top: 10px;
            color: #94a3b8;
            font-size: 12px;
            z-index: 2;
        }

        .fee-input input {
            padding-left: 28px;
        }

        .pricing {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-top: 16px;
            width: 100%;
        }

        .pricing-box {
            border: 1px solid #e2e8f0;
            border-radius: 11px;
            overflow: hidden;
            min-width: 0;
        }

        .pricing-title {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
            color: #64748b;
        }

        .pricing-content {
            padding: 12px;
            min-height: 62px;
            color: #94a3b8;
            font-size: 11px;
        }

        .other-fee {
            margin-top: 12px;
            border: 1px solid #d9dfe7;
            background: #ffffff;
            border-radius: 7px;
            padding: 8px 12px;
            font-size: 11px;
            color: #64748b;
            cursor: pointer;
        }

        .other-fee:hover {
            border-color: #b8c8df;
        }

        .notes {
            margin-top: 14px;
        }

        .approval-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            width: 100%;
        }

        .full {
            grid-column: 1 / -1;
            width: 100%;
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
        }

        .btn {
            height: 38px;
            border-radius: 7px;
            padding: 0 16px;
            font-size: 12px;
            cursor: pointer;
        }

        .btn-cancel {
            background: white;
            border: 1px solid #d1d9e3;
            color: #334155;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-save {
            border: none;
            background: #78a0f4;
            color: white;
            font-weight: 600;
        }

        .btn-save:hover {
            background: #5f8df0;
        }

        /*
        |--------------------------------------------------------------------------
        | SMALL SCREEN ONLY
        |--------------------------------------------------------------------------
        |
        | Drawer mode is excluded so drawer keeps the intended grid layout.
        |
        */

        @if(!request()->boolean('drawer'))

        @media (max-width: 800px) {

            .content {
                padding-left: 16px;
                padding-right: 16px;
            }

            .grid-2,
            .grid-3,
            .grid-4,
            .radio-grid-2,
            .radio-grid-3,
            .metadata,
            .pricing,
            .approval-grid {
                grid-template-columns: 1fr;
            }

            .service-grid {
                grid-template-columns: 1fr;
            }

            .header {
                padding-left: 18px;
            }

            .top-info {
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
            }

            .customer-type-row {
                align-items: stretch;
                flex-direction: column;
                gap: 8px;
            }

            .customer-type-row > .label {
                width: auto;
                flex: none;
            }
        }

        @endif

        /*
        |--------------------------------------------------------------------------
        | DRAWER GRID OVERRIDE
        |--------------------------------------------------------------------------
        */

        @if(request()->boolean('drawer'))

            .content {
                width: 100%;
                max-width: 1000px;
                margin-left: auto;
                margin-right: auto;
            }

            .grid-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .grid-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .grid-4 {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .radio-grid-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .radio-grid-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .metadata {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .pricing {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .approval-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .service-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .top-info {
                align-items: center;
                flex-direction: row;
            }

            .customer-type-row {
                align-items: center;
                flex-direction: row;
                gap: 24px;
            }

            .customer-type-row > .label {
                width: 64px;
                flex: 0 0 64px;
            }

        @endif
    </style>
</head>

<body>

<div class="page">

    
    <form
        method="POST"
        action="{{ isset($deal) ? route('deals.update', $deal->id) : route('deals.store') }}"
    >

        @csrf

        <input
            type="hidden"
            name="return_to"
            value="{{ request('return_to', route('deals.index')) }}"
        >

        @if(isset($deal))
            @method('PUT')
        @endif


        <main
            class="content form-shell"
            x-data="dealFormState()"
        >

            <!-- TOP INFORMATION -->
            <div class="top-info">

                <div class="top-info-title">
                    Consulting & Deal Form
                </div>

                <label class="owner">

                    <span class="owner-dot"></span>

                    <select name="owner_name" aria-label="Deal owner">

                        <option value="">
                            Owner
                        </option>

                        @foreach($owners ?? collect() as $owner)

                            <option
                                value="{{ $owner->name }}"
                                @selected(
                                    old(
                                        'owner_name',
                                        $deal->owner_name ?? optional(auth()->user())->name
                                    ) === $owner->name
                                )
                            >
                                Owner: {{ $owner->name }}
                            </option>

                        @endforeach

                    </select>

                </label>

            </div>
            <!-- ACCOUNT -->
<section class="section deal-section bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

    <div class="section-title">
        Account
    </div>

    <div>
        <label class="label">
            Account
        </label>

        <select
            name="account_id"
            required
        >
            <option value="">
                Select Account
            </option>

            @foreach($accounts ?? collect() as $account)

                <option
                    value="{{ $account->id }}"
                    @selected(
                        old(
                            'account_id',
                            $deal->account_id ?? ''
                        ) == $account->id
                    )
                >
                    {{ $account->account_code }} — {{ $account->account_name }}
                </option>

            @endforeach

        </select>

    </div>

</section>

            <!-- CUSTOMER TYPE -->
            <section class="section deal-section customer-type bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Customer Type
                </div>

                <div class="customer-type-row">

                    <label class="label">
                        Type
                    </label>

                    <div class="radio-grid-2">

                        <label class="choice">

                            <input
                                type="radio"
                                name="customer_type"
                                value="Business"
                                x-model="customerType"
                                {{ old('customer_type', $deal->customer_type ?? '') == 'Business' ? 'checked' : '' }}
                            >

                            Business

                        </label>


                        <label class="choice">

                            <input
                                type="radio"
                                name="customer_type"
                                value="Individual"
                                x-model="customerType"
                                {{ old('customer_type', $deal->customer_type ?? '') == 'Individual' ? 'checked' : '' }}
                            >

                            Individual

                        </label>

                    </div>

                </div>

            </section>


            <!-- DEAL INFORMATION -->
            <section class="section deal-section deal-information bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Deal Information
                </div>

                <div class="section-description">
                    Auto-generated metadata appears here after the deal is saved.
                </div>

                <div class="metadata">

                    <div class="metadata-item">

                        <div class="meta-label">
                            Deal Code
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
                            {{ optional($deal ?? null)->created_by ?: optional(auth()->user())->name }}
                        </div>

                    </div>


                    <div class="metadata-item">

                        <div class="meta-label">
                            Created At
                        </div>

                        <div class="meta-value">

                            @if(isset($deal) && $deal->created_at)

                                {{ $deal->created_at->timezone('Asia/Manila')->format('F d, Y \a\t h:i:s A') }}

                            @else

                                Generated on save

                            @endif

                        </div>

                    </div>

                </div>

            </section>


            <!-- CLIENT SELECTION -->
            <section class="section deal-section client-selection bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Select Existing Contact / Client
                </div>

                <div class="section-description">
                    Search by contact name, company, email, or mobile number.
                </div>

            <label class="label">
                 Select Master Contact
                    </label>

                    <select
                        name="contact_id"
                        x-model="contactId"
                        @change="loadContact()"
                    >
                        <option value="">
                            Select Contact
                        </option>

                        @foreach($contacts ?? collect() as $contact)
                            <option
                                value="{{ $contact->id }}"
                                x-show="customerType === 'Individual' || companyId == '{{ $contact->company_id }}'"
                            >
                                {{ $contact->contact_code }} — {{ $contact->first_name }} {{ $contact->last_name }}
                            </option>
                        @endforeach
                    </select>

                <label class="label">
                    Search Existing Client
                </label>

                <input
                    type="text"
                    name="client_search"
                    value="{{ old('client_search', $deal->client_search ?? '') }}"
                    placeholder="⌕  Type name, company, email, or mobile..."
                    list="deal-client-options"
                >

                <datalist id="deal-client-options">

                    @foreach($clients ?? collect() as $client)

                        <option
                            value="{{ $client->primary_contact_name ?: $client->company ?: $client->email ?: $client->mobile_number }}"
                        >
                            {{ implode(' | ', array_filter([
                                $client->company,
                                $client->email,
                                $client->mobile_number
                            ])) }}
                        </option>

                    @endforeach

                </datalist>


                <div
                    class="grid-2"
                    style="margin-top: 14px;"
                >

                    <div>

                        <label class="label">
                            Deal Title
                        </label>

                        <input
                            type="text"
                            name="deal_title"
                            value="{{ old('deal_title', $deal->deal_title ?? '') }}"
                            placeholder="CONDEAL-YYYY-###"
                        >

                        <div
                            class="section-description"
                            style="margin-top: 6px; margin-bottom: 0;"
                        >
                            Auto-generated and saved by the backend when the deal is created.
                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Pipeline Stage
                        </label>

                        <select name="pipeline_stage">

                            @foreach([
                                'Inquiry',
                                'Qualification',
                                'Consultation',
                                'Proposal',
                                'Negotiation',
                                'Payment',
                                'Activation',
                                'Closed Won',
                                'Closed Lost'
                            ] as $stage)

                                <option
                                    value="{{ $stage }}"
                                    {{ old('pipeline_stage', $deal->pipeline_stage ?? 'Inquiry') == $stage ? 'selected' : '' }}
                                >
                                    {{ $stage }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>


                <div class="warning">
                    Contact selection is required before completing the rest of the deal form.
                </div>

            </section>


            <!-- CONTACT INFORMATION -->
            <section class="section deal-section contact-information bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Contact Information
                </div>

                <div class="section-description">
                    Fields auto-fill from selected contact and remain editable.
                </div>


                <div class="grid-2">

                    <div>

                        <label class="label">
                            Salutation
                        </label>

                        <select name="salutation">

                            <option value="">
                                Select salutation
                            </option>

                            <option
                                value="Mr."
                                {{ old('salutation', $deal->salutation ?? '') == 'Mr.' ? 'selected' : '' }}
                            >
                                Mr.
                            </option>

                            <option
                                value="Ms."
                                {{ old('salutation', $deal->salutation ?? '') == 'Ms.' ? 'selected' : '' }}
                            >
                                Ms.
                            </option>

                            <option
                                value="Mrs."
                                {{ old('salutation', $deal->salutation ?? '') == 'Mrs.' ? 'selected' : '' }}
                            >
                                Mrs.
                            </option>

                            <option
                                value="Dr."
                                {{ old('salutation', $deal->salutation ?? '') == 'Dr.' ? 'selected' : '' }}
                            >
                                Dr.
                            </option>

                            <option
                                value="Atty."
                                {{ old('salutation', $deal->salutation ?? '') == 'Atty.' ? 'selected' : '' }}
                            >
                                Atty.
                            </option>

                            <option
                                value="CPA."
                                {{ old('salutation', $deal->salutation ?? '') == 'CPA.' ? 'selected' : '' }}
                            >
                                CPA.
                            </option>

                            <option
                                value="Eng."
                                {{ old('salutation', $deal->salutation ?? '') == 'Eng.' ? 'selected' : '' }}
                            >
                                Eng.
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            Sex
                        </label>

                        <select name="sex">

                            <option value="">
                                Select sex
                            </option>

                            <option
                                value="Male"
                                {{ old('sex', $deal->sex ?? '') == 'Male' ? 'selected' : '' }}
                            >
                                Male
                            </option>

                            <option
                                value="Female"
                                {{ old('sex', $deal->sex ?? '') == 'Female' ? 'selected' : '' }}
                            >
                                Female
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="label">
                            First Name
                        </label>

                        <input
                            type="text"
                            name="first_name"
                            value="{{ old('first_name', $deal->first_name ?? '') }}"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Middle Initial
                        </label>

                        <input
                            type="text"
                            name="middle_initial"
                            value="{{ old('middle_initial', $deal->middle_initial ?? '') }}"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Last Name
                        </label>

                        <input
                            type="text"
                            name="last_name"
                            value="{{ old('last_name', $deal->last_name ?? '') }}"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Name Extension
                        </label>

                        <input
                            type="text"
                            name="name_extension"
                            value="{{ old('name_extension', $deal->name_extension ?? '') }}"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Date of Birth
                        </label>

                        <input
                            type="date"
                            name="date_of_birth"
                            value="{{ old('date_of_birth', $deal->date_of_birth ?? '') }}"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Email Address
                        </label>

                        <input
                            type="email"
                            name="email"
                            value="{{ old('email', $deal->email ?? '') }}"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Mobile Number
                        </label>

                        <input
                            type="text"
                            name="mobile_number"
                            value="{{ old('mobile_number', $deal->mobile_number ?? '') }}"
                        >

                    </div>


                    <div class="full">

                        <label class="label">
                            Address
                        </label>

                        <textarea name="address">{{ old('address', $deal->address ?? '') }}</textarea>

                    </div>


                    <div>

                        <div>

    <label class="label">
        Company
    </label>

    <select
        name="company_id"
        x-model="companyId"
        x-bind:disabled="customerType === 'Individual'"
    >

        <option value="">
            Select Company
        </option>

        @foreach($companies ?? collect() as $company)

            <option
                value="{{ $company->id }}"
            >
                {{ $company->company_code }} — {{ $company->company_name }}
            </option>

        @endforeach

    </select>

</div>


                    <div>

                        <label class="label">
                            Position / Designation
                        </label>

                        <input
                            type="text"
                            name="position"
                            value="{{ old('position', $deal->position ?? '') }}"
                            x-bind:disabled="customerType === 'Individual'"
                            x-bind:class="customerType === 'Individual' ? 'bg-gray-100 cursor-not-allowed' : ''"
                        >

                    </div>


                    <div class="full">

                        <label class="label">
                            Company Address
                        </label>

                        <textarea
                            name="company_address"
                            x-bind:disabled="customerType === 'Individual'"
                            x-bind:class="customerType === 'Individual' ? 'bg-gray-100 cursor-not-allowed' : ''"
                        >{{ old('company_address', $deal->company_address ?? '') }}</textarea>

                    </div>

                </div>

            </section>


            <!-- SERVICE IDENTIFICATION -->
            <section class="section deal-section service-identification bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Service Identification
                </div>


                <div class="sub-label">
                    Service Area
                </div>


                <div class="service-grid">

                    @foreach([
                        'Accounting & Compliance Advisory',
                        'Business Strategy & Process Advisory',
                        'Corporate & Regulatory Advisory',
                        'Governance & Policy Advisory',
                        'Learning & Capability Development',
                        'People & Talent Solutions',
                        'Service Add-Ons',
                        'Strategic Situations Advisory'
                    ] as $service)

                        <label class="check">

                            <input
                                type="checkbox"
                                name="service_area[]"
                                value="{{ $service }}"
                                x-model="selectedServiceAreas"
                                {{ in_array($service, old('service_area', $deal->service_area ?? [])) ? 'checked' : '' }}
                            >

                            {{ $service }}

                        </label>

                    @endforeach

                </div>


                <div class="sub-label">
                    Services
                </div>

                <div class="disabled-box">
                    Select a service area first to show matching services.
                </div>


                <div
                    class="service-subservices"
                    x-show="selectedServiceAreas.length"
                    x-cloak
                    style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; width: 100%;"
                >

                    <template
                        x-for="serviceArea in selectedServiceAreas"
                        :key="serviceArea"
                    >

                        <div class="section service-subservice-card">

                            <div
                                class="section-title"
                                x-text="serviceArea"
                            ></div>

                            <div class="service-grid">

                                <template
                                    x-for="subService in serviceMap[serviceArea] || []"
                                    :key="subService"
                                >

                                    <label class="check">

                                        <input
                                            type="checkbox"
                                            name="services[]"
                                            :value="subService"
                                        >

                                        <span x-text="subService"></span>

                                    </label>

                                </template>

                            </div>

                        </div>

                    </template>

                </div>


                <div style="margin-top: 8px;">

                    <label class="check">

                        <input
                            type="checkbox"
                            name="services[]"
                            value="Others"
                            {{ in_array('Others', old('services', $deal->services ?? [])) ? 'checked' : '' }}
                        >

                        Others

                    </label>

                </div>

            </section>


            <!-- PRODUCTS -->
            <section class="section deal-section products bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Products
                </div>

                <div class="section-description">
                    Select a service area to narrow matching products. Products without a linked service remain available below.
                </div>


                <div class="sub-label">
                    UNLINKED PRODUCTS
                </div>


                <div>

                    <label class="check">

                        <input
                            type="checkbox"
                            name="products[]"
                            value="Stock Certificate Printing"
                            {{ in_array('Stock Certificate Printing', old('products', $deal->products ?? [])) ? 'checked' : '' }}
                        >

                        Stock Certificate Printing

                    </label>


                    <label
                        class="check"
                        style="margin-top: 8px;"
                    >

                        <input
                            type="checkbox"
                            name="products[]"
                            value="Others"
                            {{ in_array('Others', old('products', $deal->products ?? [])) ? 'checked' : '' }}
                        >

                        Others

                    </label>

                </div>

            </section>


            <!-- SCOPE -->
            <section class="section deal-section scope-of-work bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Scope of Work
                </div>

                <div class="section-description">
                    Describe the detailed scope of the engagement
                </div>

                <label class="label">
                    Scope of Work
                </label>

                <textarea
                    name="scope_of_work"
                    style="min-height: 90px;"
                >{{ old('scope_of_work', $deal->scope_of_work ?? '') }}</textarea>

            </section>


            <!-- ENGAGEMENT TYPE -->
            <section class="section deal-section engagement-type bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Engagement Type
                </div>


                <div
                    class="radio-grid-3"
                    style="margin-top: 14px;"
                >

                    <label class="choice">

                        <input
                            type="radio"
                            name="engagement_type"
                            value="Project Engagement"
                            {{ old('engagement_type', $deal->engagement_type ?? '') == 'Project Engagement' ? 'checked' : '' }}
                        >

                        Project Engagement

                    </label>


                    <label class="choice">

                        <input
                            type="radio"
                            name="engagement_type"
                            value="Regular (Retainer) Engagement"
                            {{ old('engagement_type', $deal->engagement_type ?? '') == 'Regular (Retainer) Engagement' ? 'checked' : '' }}
                        >

                        Regular (Retainer) Engagement

                    </label>


                    <label class="choice">

                        <input
                            type="radio"
                            name="engagement_type"
                            value="Hybrid Engagement"
                            {{ old('engagement_type', $deal->engagement_type ?? '') == 'Hybrid Engagement' ? 'checked' : '' }}
                        >

                        Hybrid Engagement

                    </label>

                </div>

            </section>


            <!-- CLIENT REQUIREMENTS -->
            <section class="section deal-section client-requirements bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Client Requirements
                </div>


                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Requirement
                                </th>

                                <th class="center">
                                    Provided
                                </th>

                                <th class="center">
                                    Pending
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @foreach([
                                'Client Contact Form',
                                'Deal Form',
                                'Business Information Form',
                                'Client Information Form',
                                'Service Task Activation & Routing Tracker (Start)',
                                'Others'
                            ] as $requirement)

                                <tr>

                                    <td>
                                        {{ $requirement }}
                                    </td>


                                    <td class="center">

                                        <input
                                            type="radio"
                                            name="requirements[{{ $loop->index }}]"
                                            value="provided"
                                            {{ old(
                                                'requirements.' . $loop->index,
                                                $deal->requirements[$loop->index] ?? ''
                                            ) == 'provided' ? 'checked' : '' }}
                                        >

                                    </td>


                                    <td class="center">

                                        <input
                                            type="radio"
                                            name="requirements[{{ $loop->index }}]"
                                            value="pending"
                                            {{ old(
                                                'requirements.' . $loop->index,
                                                $deal->requirements[$loop->index] ?? ''
                                            ) == 'pending' ? 'checked' : '' }}
                                        >

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </section>


            <!-- REQUIRED ACTIONS -->
            <section class="section deal-section required-actions bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Required Actions
                </div>


                <div
                    class="service-grid"
                    style="margin-top: 14px;"
                >

                    @foreach([
                        'Document Review',
                        'Regulatory Research',
                        'Drafting of Documents',
                        'Client Consultation',
                        'Compliance Check',
                        'Financial Analysis',
                        'Government Filing / Processing',
                        'Internal Approval',
                        'Others'
                    ] as $action)

                        <label class="check">

                            <input
                                type="checkbox"
                                name="required_actions[]"
                                value="{{ $action }}"
                                {{ in_array(
                                    $action,
                                    old(
                                        'required_actions',
                                        $deal->required_actions ?? []
                                    )
                                ) ? 'checked' : '' }}
                            >

                            {{ $action }}

                        </label>

                    @endforeach

                </div>

            </section>


            <!-- FEES -->
            <section class="section deal-section fees bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Fees
                </div>


                <div
                    class="grid-2"
                    style="margin-top: 14px;"
                >

                    @foreach([
                        'Estimated Professional Fee' => 'estimated_professional_fee',
                        'Estimated Government Fees' => 'estimated_government_fees',
                        'Estimated Service Support Fee' => 'estimated_service_support_fee',
                        'Total Service Fee' => 'total_service_fee',
                        'Total Product Fee' => 'total_product_fee',
                        'Discount' => 'discount',
                        'Total Estimated Engagement Value' => 'total_estimated_engagement_value'
                    ] as $label => $name)

                        <div>

                            <label class="label">
                                {{ $label }}
                            </label>

                            <div class="fee-input">

                                <span>
                                    ₱
                                </span>

                                <input
                                    type="number"
                                    step="0.01"
                                    name="{{ $name }}"
                                    value="{{ old($name, $deal->$name ?? '') }}"
                                >

                            </div>

                        </div>

                    @endforeach

                </div>


                <div class="pricing">

                    <div class="pricing-box">

                        <div class="pricing-title">
                            Services Pricing Guide
                        </div>

                        <div class="pricing-content">

                            SERVICE

                            <br><br>

                            Select a service area to show service prices.

                        </div>

                    </div>


                    <div class="pricing-box">

                        <div class="pricing-title">
                            Products Pricing Guide
                        </div>

                        <div class="pricing-content">

                            PRODUCT

                            <br><br>

                            Stock Certificate Printing

                            <span style="float: right;">
                                ₱300.00
                            </span>

                        </div>

                    </div>

                </div>


                <button
                    type="button"
                    class="other-fee"
                    onclick="addOtherFee()"
                >
                    Add Other Fee
                </button>


                <div id="other-fees"></div>

            </section>


            <!-- PAYMENT TERMS -->
            <section class="section deal-section payment-terms bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Payment Terms
                </div>


                <div
                    class="radio-grid-2"
                    style="margin-top: 14px;"
                >

                    @foreach([
                        'Full Payment Before Service',
                        '50% Downpayment / 50% Completion',
                        'Milestone-Based Payment',
                        'Monthly Retainer',
                        'Others'
                    ] as $paymentTerm)

                        <label class="choice">

                            <input
                                type="radio"
                                name="payment_terms"
                                value="{{ $paymentTerm }}"
                                {{ old(
                                    'payment_terms',
                                    $deal->payment_terms ?? ''
                                ) == $paymentTerm ? 'checked' : '' }}
                            >

                            {{ $paymentTerm }}

                        </label>

                    @endforeach

                </div>

            </section>


            <!-- ESTIMATED TIMELINE -->
            <section class="section deal-section estimated-timeline bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Estimated Timeline
                </div>


                <div
                    class="grid-2"
                    style="margin-top: 14px;"
                >

                    <div>

                        <label class="label">
                            Planned Start Date
                        </label>

                        <input
                            type="date"
                            name="planned_start_date"
                            value="{{ old(
                                'planned_start_date',
                                $deal->planned_start_date ?? ''
                            ) }}"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Estimated Duration (Days)
                        </label>

                        <input
                            type="number"
                            name="estimated_duration"
                            value="{{ old(
                                'estimated_duration',
                                $deal->estimated_duration ?? ''
                            ) }}"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Estimated Completion Date
                        </label>

                        <input
                            type="date"
                            name="estimated_completion_date"
                            value="{{ old(
                                'estimated_completion_date',
                                $deal->estimated_completion_date ?? ''
                            ) }}"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Client Preferred Completion Date
                        </label>

                        <input
                            type="date"
                            name="client_preferred_completion_date"
                            value="{{ old(
                                'client_preferred_completion_date',
                                $deal->client_preferred_completion_date ?? ''
                            ) }}"
                        >

                    </div>


                    <div>

                        <label class="label">
                            Confirmed Delivery Date
                        </label>

                        <input
                            type="date"
                            name="confirmed_delivery_date"
                            value="{{ old(
                                'confirmed_delivery_date',
                                $deal->confirmed_delivery_date ?? ''
                            ) }}"
                        >

                    </div>


                    <div class="full">

                        <label class="label">
                            Timeline Notes
                        </label>

                        <textarea name="timeline_notes">{{ old(
                            'timeline_notes',
                            $deal->timeline_notes ?? ''
                        ) }}</textarea>

                    </div>

                </div>

            </section>


            <!-- COMPLEXITY -->
            <section class="section deal-section service-complexity bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Service Complexity Assessment
                </div>


                <div
                    class="radio-grid-2"
                    style="margin-top: 14px;"
                >

                    @foreach([
                        'Standard Service',
                        'Complex Case',
                        'Others'
                    ] as $complexity)

                        <label class="choice">

                            <input
                                type="radio"
                                name="complexity"
                                value="{{ $complexity }}"
                                {{ old(
                                    'complexity',
                                    $deal->complexity ?? ''
                                ) == $complexity ? 'checked' : '' }}
                            >

                            {{ $complexity }}

                        </label>

                    @endforeach

                </div>


                <div class="sub-label">
                    Professional Support Required
                </div>


                <div class="service-grid">

                    @foreach([
                        'Requires Senior Consultant',
                        'Requires Subject Matter Expert',
                        'Requires Lawyer / Legal Counsel',
                        'Requires CPA / Certified Public Accountant',
                        'Others'
                    ] as $support)

                        <label class="check">

                            <input
                                type="checkbox"
                                name="professional_support[]"
                                value="{{ $support }}"
                                {{ in_array(
                                    $support,
                                    old(
                                        'professional_support',
                                        $deal->professional_support ?? []
                                    )
                                ) ? 'checked' : '' }}
                            >

                            {{ $support }}

                        </label>

                    @endforeach

                </div>


                <div class="notes">

                    <label class="label">
                        Notes / Explanation
                    </label>

                    <textarea name="complexity_notes">{{ old(
                        'complexity_notes',
                        $deal->complexity_notes ?? ''
                    ) }}</textarea>

                </div>

            </section>


            <!-- PROPOSAL DECISION -->
            <section class="section deal-section proposal-decision bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Proposal Decision
                </div>


                <div
                    class="radio-grid-2"
                    style="margin-top: 14px;"
                >

                    @foreach([
                        'Prepare Proposal',
                        'Prepare Engagement Letter',
                        'Schedule Client Consultation',
                        'Request Additional Documents',
                        'Decline Engagement'
                    ] as $decision)

                        <label class="choice">

                            <input
                                type="radio"
                                name="proposal_decision"
                                value="{{ $decision }}"
                                {{ old(
                                    'proposal_decision',
                                    $deal->proposal_decision ?? ''
                                ) == $decision ? 'checked' : '' }}
                            >

                            {{ $decision }}

                        </label>

                    @endforeach

                </div>

            </section>


            <!-- INTERNAL ASSIGNMENT -->
            <section class="section deal-section internal-assignment bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Internal Assignment
                </div>


                <div
                    class="grid-2"
                    style="margin-top: 14px;"
                >

                    <div>

                        <label class="label">
                            Assigned Consultant
                        </label>

                        <input
                            type="text"
                            name="assigned_consultant"
                            value="{{ old(
                                'assigned_consultant',
                                $deal->assigned_consultant ?? ''
                            ) }}"
                        >

                        <div
                            class="section-description"
                            style="margin-top: 5px;"
                        >
                            Search existing employees. If none appears, you can still type manually.
                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Assigned Associate
                        </label>

                        <input
                            type="text"
                            name="assigned_associate"
                            value="{{ old(
                                'assigned_associate',
                                $deal->assigned_associate ?? ''
                            ) }}"
                        >

                        <div
                            class="section-description"
                            style="margin-top: 5px;"
                        >
                            Search existing employees. If none appears, you can still type manually.
                        </div>

                    </div>


                    <div class="full">

                        <label class="label">
                            Service Department / Unit
                        </label>

                        <input
                            type="text"
                            name="service_department"
                            value="{{ old(
                                'service_department',
                                $deal->service_department ?? ''
                            ) }}"
                        >

                    </div>

                </div>

            </section>


            <!-- NOTES -->
            <section class="section deal-section notes bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Notes
                </div>


                <div
                    class="grid-2"
                    style="margin-top: 14px;"
                >

                    <div>

                        <label class="label">
                            Consultant Notes
                        </label>

                        <textarea name="consultant_notes">{{ old(
                            'consultant_notes',
                            $deal->consultant_notes ?? ''
                        ) }}</textarea>

                    </div>


                    <div>

                        <label class="label">
                            Associate Notes
                        </label>

                        <textarea name="associate_notes">{{ old(
                            'associate_notes',
                            $deal->associate_notes ?? ''
                        ) }}</textarea>

                    </div>

                </div>

            </section>


            <!-- INTERNAL APPROVAL -->
            <section class="section deal-section internal-approval bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Internal Approval
                </div>


                <div
                    class="approval-grid"
                    style="margin-top: 14px;"
                >

                    <div>

                        <label class="label">
                            Prepared By
                        </label>

                        <input
                            type="text"
                            name="prepared_by"
                            value="{{ old(
                                    'prepared_by',
                                    $deal->owner_name ?? optional(auth()->user())->name
                                ) }}"
                        >

                        <div
                            class="section-description"
                            style="margin-top: 5px;"
                        >
                            Auto-filled from the internal Deal Owner. You can still change it manually.
                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Reviewed By
                        </label>

                        <input
                            type="text"
                            name="reviewed_by"
                            value="{{ old(
                                'reviewed_by',
                                $deal->reviewed_by ?? ''
                            ) }}"
                        >

                        <div
                            class="section-description"
                            style="margin-top: 5px;"
                        >
                            This auto-fills from the approver once the deal is approved.
                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Name
                        </label>

                        <input
                            type="text"
                            name="approval_name"
                            value="{{ old(
                                'approval_name',
                                $deal->approval_name ?? ''
                            ) }}"
                        >

                        <div
                            class="section-description"
                            style="margin-top: 5px;"
                        >
                            Search existing employees or type manually.
                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Date
                        </label>

                        <input
                            type="date"
                            name="approval_date"
                            value="{{ old(
                                'approval_date',
                                $deal->approval_date ?? date('Y-m-d')
                            ) }}"
                        >

                    </div>


                    <div class="full">

                        <label class="label">
                            Client Fullname & Signature
                        </label>

                        <input
                            type="text"
                            name="client_fullname_signature"
                            value="{{ old(
                                'client_fullname_signature',
                                $deal->client_fullname_signature ?? ''
                            ) }}"
                        >

                        <div
                            class="section-description"
                            style="margin-top: 5px;"
                        >
                            Auto-filled from the selected CIF/BIF contact details.
                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Referred By / Closed By
                        </label>

                        <input
                            type="text"
                            name="referred_by"
                            value="{{ old(
                                'referred_by',
                                $deal->referred_by ?? ''
                            ) }}"
                        >

                        <div
                            class="section-description"
                            style="margin-top: 5px;"
                        >
                            Search existing employees or type manually.
                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Sales & Marketing
                        </label>

                        <input
                            type="text"
                            name="sales_marketing"
                            value="{{ old(
                                'sales_marketing',
                                $deal->sales_marketing ?? ''
                            ) }}"
                        >

                        <div
                            class="section-description"
                            style="margin-top: 5px;"
                        >
                            Search existing employees or type manually.
                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Lead Consultant
                        </label>

                        <input
                            type="text"
                            name="lead_consultant"
                            value="{{ old(
                                'lead_consultant',
                                $deal->lead_consultant ?? ''
                            ) }}"
                        >

                        <div
                            class="section-description"
                            style="margin-top: 5px;"
                        >
                            Defaults to the assigned consultant if left blank.
                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Lead Associate Assigned
                        </label>

                        <input
                            type="text"
                            name="lead_associate"
                            value="{{ old(
                                'lead_associate',
                                $deal->lead_associate ?? ''
                            ) }}"
                        >

                        <div
                            class="section-description"
                            style="margin-top: 5px;"
                        >
                            Defaults to the assigned associate if left blank.
                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Finance
                        </label>

                        <input
                            type="text"
                            name="finance"
                            value="{{ old(
                                'finance',
                                $deal->finance ?? ''
                            ) }}"
                            placeholder="Search user or type email manually"
                        >

                        <div
                            class="section-description"
                            style="margin-top: 5px;"
                        >
                            Search existing registered users. If none appears, you can still type email manually.
                        </div>

                    </div>


                    <div>

                        <label class="label">
                            President
                        </label>

                        <input
                            type="text"
                            name="president"
                            value="{{ old(
                                'president',
                                $deal->president ?? 'John Kelly'
                            ) }}"
                        >

                        <div
                            class="section-description"
                            style="margin-top: 5px;"
                        >
                            Defaults to John Kelly.
                        </div>

                    </div>

                </div>

            </section>

        </main>


        <!-- FOOTER -->
        <footer class="footer">

            <a
                href="{{ route('deals.index') }}"
                class="btn btn-cancel"
            >
                Cancel
            </a>


            <button
                type="submit"
                class="btn btn-save"
            >
                {{ isset($deal) ? 'Save Changes' : 'Save & View Deal' }}
            </button>

        </footer>

    </form>

</div>


{{-- ================================================================
     SERVICE MAP
     IMPORTANT:
     Keep this outside the large @json([...]) expression.
     This avoids the Blade parser error.
================================================================ --}}

@php

    $dealServiceMap = [

        'Accounting & Compliance Advisory' => [
            'AFS Preparation',
            'Accounting Services',
            'Audit Support / Coordination',
            'BIR RDO Compliance Representative',
            'BIR Registration Assistance (L/M/S)',
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
            'Corporation Formation & Registration Assistance (L/M/MM/S)',
            'Foreign Business Entry Support',
            'LGU Compliance Representative',
            'Loan Application Assistance',
            'New / Renewal LGU City/Municipality Business Registration Assistance — Complex / Non-Complex',
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


<script>
    const dealServiceMap = @json($dealServiceMap);


    function dealFormState() {
    return {
        contactId: @js(old('contact_id', '')),
        companyId: @js(old('company_id', '')),
        contacts: @js(
            ($contacts ?? collect())->map(function ($contact) {
                return [
                    'id' => $contact->id,
                    'salutation' => $contact->salutation,
                    'first_name' => $contact->first_name,
                    'middle_name' => $contact->middle_name,
                    'last_name' => $contact->last_name,
                    'name_extension' => $contact->name_extension,
                    'date_of_birth' => optional($contact->date_of_birth)->format('Y-m-d'),
                    'sex' => $contact->sex,
                    'email' => $contact->email,
                    'mobile_number' => $contact->mobile_number,
                    'address' => $contact->address,
                    'position' => $contact->position,
                ];
            })->values()
        ),

        customerType: @js(
            old(
                'customer_type',
                $deal->customer_type ?? ''
            )
        ),

        selectedServiceAreas: @js(
            old(
                'service_area',
                $deal->service_areas ?? []
            )
        ),

        serviceMap: dealServiceMap,

        loadContact() {
            const contact = this.contacts.find(
                contact => String(contact.id) === String(this.contactId)
            );

            if (!contact) {
                return;
            }

            const fields = {
                salutation: contact.salutation,
                first_name: contact.first_name,
                last_name: contact.last_name,
                name_extension: contact.name_extension,
                date_of_birth: contact.date_of_birth,
                sex: contact.sex,
                email: contact.email,
                mobile_number: contact.mobile_number,
                address: contact.address,
                position: contact.position,
            };

            Object.entries(fields).forEach(([name, value]) => {
                const field = document.querySelector(
                    `[name="${name}"]`
                );

                if (field) {
                    field.value = value ?? '';
                    field.dispatchEvent(
                        new Event('input', { bubbles: true })
                    );
                    field.dispatchEvent(
                        new Event('change', { bubbles: true })
                    );
                }
            });
        },
    };
}
</script>


<script>

    function addOtherFee() {

        const container = document.getElementById('other-fees');

        const wrapper = document.createElement('div');

        wrapper.style.marginTop = '10px';

        wrapper.innerHTML = `
            <div class="grid-2">

                <div>

                    <label class="label">
                        Other Fee Description
                    </label>

                    <input
                        type="text"
                        name="other_fee_description[]"
                        placeholder="Fee description"
                    >

                </div>


                <div>

                    <label class="label">
                        Amount
                    </label>

                    <div class="fee-input">

                        <span>
                            ₱
                        </span>

                        <input
                            type="number"
                            step="0.01"
                            name="other_fee_amount[]"
                        >

                    </div>

                </div>

            </div>
        `;

        container.appendChild(wrapper);
    }

</script>

</body>
</html>
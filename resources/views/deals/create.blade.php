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
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Deal</title>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        window._employeeList = @json($employeeList);

        function employeeDropdown(initialValue = '') {
            return {
                open: false,
                value: initialValue,
                get employees() {
                    return window._employeeList || [];
                },
                get filtered() {
                    if (!this.value) return this.employees;
                    const q = this.value.toLowerCase().trim();
                    return this.employees.filter(e => 
                        (e.name && e.name.toLowerCase().includes(q)) || 
                        (e.subtitle && e.subtitle.toLowerCase().includes(q))
                    );
                },
                select(name) {
                    this.value = name;
                    this.open = false;
                }
            };
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

        /* Clean scrollbars without arrow buttons */
        .custom-scrollbar-no-arrows::-webkit-scrollbar,
        .custom-scrollbar-no-arrows *::-webkit-scrollbar {
            width: 5px !important;
            height: 0px !important;
        }

        .custom-scrollbar-no-arrows::-webkit-scrollbar-track,
        .custom-scrollbar-no-arrows *::-webkit-scrollbar-track {
            background: transparent !important;
        }

        .custom-scrollbar-no-arrows::-webkit-scrollbar-thumb,
        .custom-scrollbar-no-arrows *::-webkit-scrollbar-thumb {
            background: #cbd5e1 !important;
            border-radius: 9999px !important;
        }

        .custom-scrollbar-no-arrows::-webkit-scrollbar-thumb:hover,
        .custom-scrollbar-no-arrows *::-webkit-scrollbar-thumb:hover {
            background: #94a3b8 !important;
        }

        .custom-scrollbar-no-arrows::-webkit-scrollbar-button,
        .custom-scrollbar-no-arrows *::-webkit-scrollbar-button {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }

        .custom-scrollbar-no-arrows {
            scrollbar-width: thin !important;
            scrollbar-color: #cbd5e1 transparent !important;
            overflow-x: hidden !important;
            overflow-y: auto !important;
        }

        .owner-item,
        .account-item {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            flex-wrap: nowrap !important;
            gap: 10px !important;
            padding: 7px 8px !important;
            border-radius: 8px !important;
            cursor: pointer !important;
            text-align: left !important;
            width: 100% !important;
            box-sizing: border-box !important;
            transition: background 0.15s ease !important;
        }

        .owner-item:hover,
        .account-item:hover {
            background-color: #f1f5f9 !important;
        }

        .owner-avatar,
        .account-avatar {
            width: 32px !important;
            height: 32px !important;
            min-width: 32px !important;
            min-height: 32px !important;
            border-radius: 8px !important;
            background: #dbeafe !important;
            color: #2563eb !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            flex-shrink: 0 !important;
            line-height: 1 !important;
        }

        .owner-details,
        .account-details {
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            min-width: 0 !important;
            flex: 1 1 auto !important;
            text-align: left !important;
        }

        .owner-name,
        .account-name {
            font-size: 12px !important;
            font-weight: 500 !important;
            color: #1e293b !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            line-height: 1.3 !important;
            display: block !important;
        }

        .owner-email,
        .account-email {
            font-size: 10.5px !important;
            color: #64748b !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            line-height: 1.3 !important;
            display: block !important;
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
            position: sticky;
            bottom: 0;
            z-index: 30;
            box-shadow: 0 -4px 14px rgba(0, 0, 0, 0.05);
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
            justify-content: center;
            transition: all 0.15s ease;
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
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .btn-save:hover {
            background: #1d4ed8;
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
        x-data="dealFormState()"
        @submit="if (isSubmitting) { $event.preventDefault(); return false; } isSubmitting = true;"
    >

        @csrf

        @if(request('return_to'))
            <input
                type="hidden"
                name="return_to"
                value="{{ request('return_to') }}"
            >
        @endif

        @if(isset($deal))
            @method('PUT')
        @endif


        <main
            class="content form-shell"
        >

            <!-- TOP INFORMATION -->
            <div class="top-info">

                <div class="top-info-title">
                    Consulting & Deal Form
                </div>

                <div 
                    class="owner-dropdown-container"
                    x-data="ownerSelector(
                        @js(old('owner_name', $deal->owner_name ?? optional(auth()->user())->name ?? ($owners->first()->name ?? ''))),
                        @js(
                            ($owners ?? collect())->map(function($o) {
                                return [
                                    'id' => $o->id,
                                    'name' => $o->name,
                                    'email' => $o->email ?? '',
                                ];
                            })->values()
                        )
                    )"
                    @click.outside="isOpen = false"
                    style="position: relative; margin-left: auto;"
                >
                    <input type="hidden" name="owner_name" :value="selectedOwner">
                    
                    <button
                        type="button"
                        class="owner"
                        @click="isOpen = !isOpen"
                        style="cursor: pointer; background: #ffffff; border: 1px solid #dce2ea; border-radius: 22px; padding: 9px 13px; font-size: 11px; color: #334155; display: inline-flex; align-items: center; white-space: nowrap;"
                    >
                        <span class="owner-dot"></span>
                        <span x-text="selectedOwner ? 'Owner: ' + selectedOwner : 'Owner'"></span>
                    </button>

                    <div
                        x-show="isOpen"
                        x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 transform scale-100"
                        x-transition:leave-end="opacity-0 transform scale-95"
                        style="position: absolute; right: 0; top: calc(100% + 6px); width: 280px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); padding: 10px; z-index: 50;"
                    >
                        <div style="position: relative; margin-bottom: 8px;">
                            <svg style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: #94a3b8; pointer-events: none;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input
                                type="text"
                                x-model="search"
                                placeholder="Search owner..."
                                style="width: 100%; box-sizing: border-box; padding: 7px 10px 7px 32px; border: 1.5px solid #3b82f6; border-radius: 8px; font-size: 12px; outline: none; background: #ffffff; color: #1e293b;"
                                @keydown.escape="isOpen = false"
                                x-ref="searchInput"
                                x-effect="if (isOpen) $nextTick(() => $refs.searchInput && $refs.searchInput.focus())"
                            >
                        </div>

                        <div class="custom-scrollbar-no-arrows" style="max-height: 220px; overflow-y: auto; overflow-x: hidden; display: flex; flex-direction: column; gap: 2px;">
                            <template x-for="owner in filteredOwners" :key="owner.id || owner.name">
                                <div
                                    @click="selectOwner(owner.name)"
                                    :style="selectedOwner === owner.name ? 'background: #f1f5f9;' : ''"
                                    class="owner-item"
                                >
                                    <div
                                        class="owner-avatar"
                                        x-text="getInitials(owner.name)"
                                    ></div>
                                    <div class="owner-details">
                                        <div class="owner-name" x-text="owner.name"></div>
                                        <div class="owner-email" x-text="owner.email || ''"></div>
                                    </div>
                                </div>
                            </template>
                            <template x-if="filteredOwners.length === 0">
                                <div style="padding: 12px; text-align: center; color: #94a3b8; font-size: 12px;">
                                    No owner found
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

            </div>
            <!-- CUSTOMER & ACCOUNT -->
            <section class="section deal-section client-account bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Customer &amp; Account
                </div>

                <div class="section-description">
                    Select the customer and account for this deal.
                </div>                <!-- DEAL TYPE -->
                <div class="customer-type" style="margin-bottom: 0;">
                    <label class="label" data-tooltip="Identifies whether this deal is for an individual or a business client.">Deal Type <span class="ordo-tooltip-icon" tabindex="0">i</span></label>

                    <div class="radio-grid-2">
                        <label class="choice" data-tooltip="For an existing business or company account.">
                            <input
                                type="radio"
                                name="customer_type"
                                value="Business"
                                x-model="customerType"
                                @change="handleCustomerTypeChange()"
                                {{ old('customer_type', $deal->customer_type ?? '') == 'Business' ? 'checked' : '' }}
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
                                {{ old('customer_type', $deal->customer_type ?? '') == 'Individual' ? 'checked' : '' }}
                            >
                            Individual
                        </label>
                    </div>
                </div>

                <!-- BUSINESS -->
                <div x-show="customerType === 'Business'" x-cloak>

                    <div style="margin-top: 18px;">
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                            <label class="label" style="margin:0;" data-tooltip="Select the existing account associated with this deal.">Account <span style="color:#dc2626;">*</span> <span class="ordo-tooltip-icon" tabindex="0">i</span></label>
                            <span style="font-size:10px; font-weight:600; letter-spacing:.04em; color:#475569; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:999px; padding:4px 8px;">
                                BUSINESS ACCOUNT
                            </span>
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
                                    <option
                                        value="{{ $account->id }}"
                                        @selected(old('account_id', $deal->account_id ?? '') == $account->id)
                                    >
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
                                style="height:34px; padding:0 12px; border:1px solid #d1d9e3; background:#fff; color:#334155;"
                                @click="openAddContact('Business')"
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
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                            <label class="label" style="margin:0;" data-tooltip="Select the existing account associated with this deal.">Account <span style="color:#dc2626;">*</span> <span class="ordo-tooltip-icon" tabindex="0">i</span></label>
                            <span style="font-size:10px; font-weight:600; letter-spacing:.04em; color:#475569; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:999px; padding:4px 8px;">
                                INDIVIDUAL ACCOUNT
                            </span>
                        </div>

                        <select
                            name="account_id"
                            x-model="accountId"
                            @change="loadAccount()"
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
                                    <option
                                        value="{{ $account->id }}"
                                        @selected(old('account_id', $deal->account_id ?? '') == $account->id)
                                    >
                                        {{ $account->account_code }} — {{ $account->account_name }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div style="margin-top:18px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:8px;">
                            <label class="label" style="margin:0;" data-tooltip="Select the individual contact who will be associated with this deal.">
                                Individual Contact <span style="color:#dc2626;">*</span> <span class="ordo-tooltip-icon" tabindex="0">i</span>
                            </label>

                            <button
                                type="button"
                                class="btn"
                                style="height:34px; padding:0 12px; border:1px solid #d1d9e3; background:#fff; color:#334155;"
                                @click="openAddContact('Individual')"
                            >
                                + Add Contact
                            </button>
                        </div>

                        <select
                            name="contact_id"
                            x-model="contactId"
                            @change="loadContact()"
                            x-bind:disabled="customerType !== 'Individual'"
                            x-bind:required="customerType === 'Individual'"
                            data-tooltip="Select the individual contact who will be associated with this deal."
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

                    <!-- INDIVIDUAL CONTACT INFORMATION -->
                    <div
                        style="margin-top:18px; border:1px solid #e2e8f0; border-radius:11px; padding:14px; background:#fafbfc;"
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

                <!-- Hidden/transaction snapshot fields -->
                <div style="display:none;">
                    <input type="text" name="client_search" value="{{ old('client_search', $deal->client_search ?? '') }}">
                    <input type="text" name="salutation" value="{{ old('salutation', $deal->salutation ?? '') }}">
                    <input type="text" name="first_name" value="{{ old('first_name', $deal->first_name ?? '') }}">
                    <input type="text" name="middle_initial" value="{{ old('middle_initial', $deal->middle_initial ?? '') }}">
                    <input type="text" name="last_name" value="{{ old('last_name', $deal->last_name ?? '') }}">
                    <input type="text" name="name_extension" value="{{ old('name_extension', $deal->name_extension ?? '') }}">
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $deal->date_of_birth ?? '') }}">
                    <input type="text" name="sex" value="{{ old('sex', $deal->sex ?? '') }}">
                    <input type="email" name="email" value="{{ old('email', $deal->email ?? '') }}">
                    <input type="text" name="mobile_number" value="{{ old('mobile_number', $deal->mobile_number ?? '') }}">
                    <input type="text" name="address" value="{{ old('address', $deal->address ?? '') }}">
                    <input type="text" name="position" value="{{ old('position', $deal->position ?? '') }}">
                    <input type="text" name="company" value="{{ old('company', $deal->company ?? '') }}">
                    <input type="text" name="company_address" value="{{ old('company_address', $deal->company_address ?? '') }}">
                    <input type="text" name="primary_contact_name" value="{{ old('primary_contact_name', $deal->primary_contact_name ?? '') }}">
                    <input type="text" name="company_name" value="{{ old('company_name', $deal->company_name ?? '') }}">
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


            <!-- DEAL STAGE & PARAMETERS -->
            <section class="section deal-section select-contact bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Deal Stage &amp; Parameters
                </div>

                <div class="section-description">
                    Configure initial deal code and pipeline stage.
                </div>

                <div class="grid-2" style="margin-top: 14px;">

                    <div>
                        <label class="label" data-tooltip="Enter a clear name that identifies the purpose of this deal.">Deal Code / Title <span class="ordo-tooltip-icon" tabindex="0">i</span></label>
                        <input
                            type="text"
                            name="deal_title"
                            value="{{ old('deal_title', $deal->deal_title ?? '') }}"
                            placeholder="Auto-generated (e.g. CONDEAL-{{ date('Y') }}-###)"
                            readonly
                            style="background-color: #f8fafc; color: #64748b; cursor: not-allowed;"
                            data-tooltip="System-generated identifier used to track this deal."
                        >
                        <div style="font-size: 11px; color: #64748b; margin-top: 6px; line-height: 1.4;">
                            Auto-generated sequential CONDEAL code assigned upon saving.
                        </div>
                    </div>

                    <div>
                        <label class="label" data-tooltip="Shows the current commercial stage of the deal.">Pipeline Stage <span class="ordo-tooltip-icon" tabindex="0">i</span></label>
                        <select name="pipeline_stage" data-tooltip="Shows the current commercial stage of the deal.">
                            @foreach([
                                'Inquiry' => "Initial stage where the client's request or opportunity is recorded.",
                                'Qualification' => "Confirm whether the client, need, and opportunity meet the applicable qualification requirements.",
                                'Consultation' => "Stage for discussing the client's requirements and proposed approach.",
                                'Proposal' => "Stage where the applicable services and commercial proposal are prepared or reviewed.",
                                'Negotiation' => "Stage where commercial terms or requested changes are being discussed.",
                                'Payment' => "Stage for completing the applicable payment requirements.",
                                'Activation' => "Stage where the approved service moves into activation.",
                                'Closed Won' => "Deal has been successfully completed and won.",
                                'Closed Lost' => "Deal has been closed without proceeding."
                            ] as $stage => $stageDesc)
                                <option
                                    value="{{ $stage }}"
                                    data-tooltip="{{ $stageDesc }}"
                                    {{ old('pipeline_stage', $deal->pipeline_stage ?? 'Inquiry') == $stage ? 'selected' : '' }}
                                >
                                    {{ $stage }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

            </section>


            <!-- SERVICE IDENTIFICATION -->
            <section class="section deal-section service-identification bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title">
                    Service Identification
                </div>


                <div class="sub-label" data-tooltip="Select the service category for this deal.">
                    Service Area <span class="ordo-tooltip-icon" tabindex="0">i</span>
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

                        <label class="check" data-tooltip="Select {{ $service }}">

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


                <div class="sub-label" data-tooltip="Select the specific service being purchased.">
                    Services <span class="ordo-tooltip-icon" tabindex="0">i</span>
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

                                    <label class="check" :data-tooltip="'Select ' + subService">

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


                <div x-data="{ showOtherService: {{ in_array('Others', old('services', $deal->services ?? [])) ? 'true' : 'false' }} }" style="margin-top: 8px;">

                    <label class="check">

                        <input
                            type="checkbox"
                            name="services[]"
                            value="Others"
                            x-model="showOtherService"
                            {{ in_array('Others', old('services', $deal->services ?? [])) ? 'checked' : '' }}
                        >

                        Others

                    </label>

                    <div x-show="showOtherService" x-cloak style="margin-top: 8px;">
                        <input
                            type="text"
                            name="other_service"
                            value="{{ old('other_service', $deal->other_service ?? '') }}"
                            placeholder="Enter custom support requirement and press Enter"
                            @keydown.enter.prevent
                        >
                    </div>

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


                    <div x-data="{ showOtherProduct: {{ in_array('Others', old('products', $deal->products ?? [])) ? 'true' : 'false' }} }" style="margin-top: 8px;">

                        <label class="check">

                            <input
                                type="checkbox"
                                name="products[]"
                                value="Others"
                                x-model="showOtherProduct"
                                {{ in_array('Others', old('products', $deal->products ?? [])) ? 'checked' : '' }}
                            >

                            Others

                        </label>

                        <div x-show="showOtherProduct" x-cloak style="margin-top: 8px;">
                            <input
                                type="text"
                                name="other_product"
                                value="{{ old('other_product', $deal->other_product ?? '') }}"
                                placeholder="Enter custom support requirement and press Enter"
                                @keydown.enter.prevent
                            >
                        </div>

                    </div>

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

                <label class="label" data-tooltip="Enter the quantity or scope required for this service, when applicable.">
                    Scope of Work <span class="ordo-tooltip-icon" tabindex="0">i</span>
                </label>

                <textarea
                    name="scope_of_work"
                    style="min-height: 90px;"
                    data-tooltip="Enter the quantity or scope required for this service, when applicable."
                >{{ old('scope_of_work', $deal->scope_of_work ?? '') }}</textarea>

            </section>


            <!-- ENGAGEMENT TYPE -->
            <section class="section deal-section engagement-type bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title" data-tooltip="Defines how the service will be delivered: Project, Regular Retainer, or Hybrid.">
                    Engagement Type <span class="ordo-tooltip-icon" tabindex="0">i</span>
                </div>


                <div
                    class="radio-grid-3"
                    style="margin-top: 14px;"
                >

                    <label class="choice" data-tooltip="Use for a defined engagement with a specific scope and deliverables.">

                        <input
                            type="radio"
                            name="engagement_type"
                            value="Project Engagement"
                            {{ old('engagement_type', $deal->engagement_type ?? '') == 'Project Engagement' ? 'checked' : '' }}
                        >

                        Project Engagement

                    </label>


                    <label class="choice" data-tooltip="Use for an ongoing recurring service.">

                        <input
                            type="radio"
                            name="engagement_type"
                            value="Regular (Retainer) Engagement"
                            {{ old('engagement_type', $deal->engagement_type ?? '') == 'Regular (Retainer) Engagement' ? 'checked' : '' }}
                        >

                        Regular (Retainer) Engagement

                    </label>


                    <label class="choice" data-tooltip="Use when the engagement combines project work with an ongoing retainer.">

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


                <div x-data="{ showOtherAction: {{ in_array('Others', old('required_actions', $deal->required_actions ?? [])) ? 'true' : 'false' }} }">

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
                            'Internal Approval'
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

                        <label class="check">

                            <input
                                type="checkbox"
                                name="required_actions[]"
                                value="Others"
                                x-model="showOtherAction"
                                {{ in_array(
                                    'Others',
                                    old(
                                        'required_actions',
                                        $deal->required_actions ?? []
                                    )
                                ) ? 'checked' : '' }}
                            >

                            Others

                        </label>

                    </div>

                    <div x-show="showOtherAction" x-cloak style="margin-top: 10px;">
                        <input
                            type="text"
                            name="other_required_action"
                            value="{{ old('other_required_action', $deal->other_required_action ?? '') }}"
                            placeholder="Enter custom support requirement and press Enter"
                            @keydown.enter.prevent
                        >
                    </div>

                </div>

            </section>


            <!-- FEES -->
            <section class="section deal-section fees bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title" data-tooltip="Shows the applicable fees and commercial values for this deal.">
                    Fees <span class="ordo-tooltip-icon" tabindex="0">i</span>
                </div>


                <div
                    class="grid-2"
                    style="margin-top: 14px;"
                >

                    @php
                        $feeTooltips = [
                            'Estimated Professional Fee' => 'Shows the applicable fees for the selected service.',
                            'Estimated Government Fees' => 'Estimated mandatory regulatory or government agency fees.',
                            'Estimated Service Support Fee' => 'Estimated logistics, support, or incidental expenses.',
                            'Total Service Fee' => 'Calculated sum of all service-related fees.',
                            'Total Product Fee' => 'Calculated sum of all product fees.',
                            'Discount' => 'Enter a discount only when it has been requested or approved. Discounts may require approval depending on the applicable rules.',
                            'Total Estimated Engagement Value' => 'Shows the commercial value of the deal based on the applicable pricing or approved line items.'
                        ];
                    @endphp

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

                            <label class="label" data-tooltip="{{ $feeTooltips[$label] ?? $label }}">
                                {{ $label }} <span class="ordo-tooltip-icon" tabindex="0">i</span>
                            </label>

                            <div class="fee-input">

                                <span>
                                    P
                                </span>

                                <input
                                    type="number"
                                    step="0.01"
                                    name="{{ $name }}"
                                    value="{{ old($name, $deal->$name ?? '') }}"
                                    data-tooltip="{{ $feeTooltips[$label] ?? $label }}"
                                >

                            </div>

                        </div>

                    @endforeach

                </div>


                <div class="pricing">

                    <div class="pricing-box">

                        <div class="pricing-title" style="font-weight: 600; color: #334155; font-size: 13px; padding: 10px 14px; background: #ffffff; border-bottom: 1px solid #e2e8f0;" data-tooltip="Standard published rates for consulting services.">
                            Services Pricing Guide <span class="ordo-tooltip-icon" tabindex="0">i</span>
                        </div>

                        <div class="pricing-content" style="padding: 14px; font-size: 12px;">

                            <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 600; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">
                                <span>SERVICE</span>
                                <span>PRICE</span>
                            </div>

                            <template x-if="!selectedServiceAreas || selectedServiceAreas.length === 0">
                                <div style="color: #94a3b8; font-size: 12px; padding: 12px 0;">
                                    Select a service area to show service prices.
                                </div>
                            </template>

                            <template x-if="selectedServiceAreas && selectedServiceAreas.length > 0">
                                <div style="display: flex; flex-direction: column; gap: 0;">
                                    <template x-for="area in selectedServiceAreas" :key="area">
                                        <template x-for="(item, idx) in (servicePriceMap[area] || [])" :key="area + '-' + idx">
                                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 7px 0; border-bottom: 1px solid #f8fafc; font-size: 12px;">
                                                <span style="color: #334155; font-weight: 400; padding-right: 12px;" x-text="item.name"></span>
                                                <span style="color: #0f172a; font-weight: 700; white-space: nowrap;" x-text="'P' + Number(item.price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
                                            </div>
                                        </template>
                                    </template>
                                </div>
                            </template>

                        </div>

                    </div>


                    <div class="pricing-box">

                        <div class="pricing-title" style="font-weight: 600; color: #334155; font-size: 13px; padding: 10px 14px; background: #ffffff; border-bottom: 1px solid #e2e8f0;" data-tooltip="Standard published rates for product add-ons.">
                            Products Pricing Guide <span class="ordo-tooltip-icon" tabindex="0">i</span>
                        </div>

                        <div class="pricing-content" style="padding: 14px; font-size: 12px;">

                            <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 600; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">
                                <span>PRODUCT</span>
                                <span>PRICE</span>
                            </div>

                            <template x-if="!selectedServiceAreas || selectedServiceAreas.length === 0">
                                <div style="color: #94a3b8; font-size: 12px; padding: 12px 0;">
                                    Select a service area to show product prices.
                                </div>
                            </template>

                            <template x-if="selectedServiceAreas && selectedServiceAreas.length > 0">
                                <div style="display: flex; flex-direction: column; gap: 0;">
                                    <template x-for="(product, pIdx) in getFilteredProducts()" :key="pIdx">
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 7px 0; border-bottom: 1px solid #f8fafc; font-size: 12px;">
                                            <span style="color: #334155; font-weight: 400; padding-right: 12px;" x-text="product.name"></span>
                                            <span style="color: #0f172a; font-weight: 700; white-space: nowrap;" x-text="'P' + Number(product.price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>

                        </div>

                    </div>

                </div>


                <div id="other-fees" style="margin-top: 14px; display: flex; flex-direction: column; gap: 12px;"></div>


                <button
                    type="button"
                    class="other-fee"
                    onclick="addOtherFee()"
                    style="margin-top: 14px;"
                    data-tooltip="Add an additional custom fee line item to this deal."
                >
                    Add Other Fee
                </button>

            </section>


            <!-- PAYMENT TERMS -->
            <section class="section deal-section payment-terms bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title" data-tooltip="Defines when and how the client will make payment.">
                    Payment Terms <span class="ordo-tooltip-icon" tabindex="0">i</span>
                </div>


                <div x-data="{ selectedPaymentTerm: '{{ old('payment_terms', $deal->payment_terms ?? '') }}' }" style="margin-top: 14px;">

                    <div class="radio-grid-2">

                        @foreach([
                            'Full Payment Before Service',
                            '50% Downpayment / 50% Completion',
                            'Milestone-Based Payment',
                            'Monthly Retainer',
                            'Others'
                        ] as $paymentTerm)

                            <label class="choice" data-tooltip="Payment schedule: {{ $paymentTerm }}">

                                <input
                                    type="radio"
                                    name="payment_terms"
                                    value="{{ $paymentTerm }}"
                                    x-model="selectedPaymentTerm"
                                    {{ old(
                                        'payment_terms',
                                        $deal->payment_terms ?? ''
                                    ) == $paymentTerm ? 'checked' : '' }}
                                >

                                {{ $paymentTerm }}

                            </label>

                        @endforeach

                    </div>

                    <div x-show="selectedPaymentTerm === 'Others'" x-cloak style="margin-top: 10px;">
                        <input
                            type="text"
                            name="other_payment_terms"
                            value="{{ old('other_payment_terms', $deal->other_payment_terms ?? '') }}"
                            placeholder="Enter custom support requirement and press Enter"
                            @keydown.enter.prevent
                        >
                    </div>

                </div>

            </section>


            <!-- ESTIMATED TIMELINE -->
            <section class="section deal-section estimated-timeline bg-white rounded-xl border border-gray-200 p-6 space-y-4 shadow-sm mb-6">

                <div class="section-title" data-tooltip="Defines the expected duration or delivery period.">
                    Estimated Timeline <span class="ordo-tooltip-icon" tabindex="0">i</span>
                </div>


                <div
                    class="grid-2"
                    style="margin-top: 14px;"
                >

                    <div>

                        <label class="label" data-tooltip="Select the planned start date for the engagement.">
                            Planned Start Date <span class="ordo-tooltip-icon" tabindex="0">i</span>
                        </label>

                        <input
                            type="date"
                            name="planned_start_date"
                            data-tooltip="Select the planned start date for the engagement."
                            value="{{ old(
                                'planned_start_date',
                                $deal->planned_start_date ?? ''
                            ) }}"
                        >

                    </div>


                    <div>

                        <label class="label" data-tooltip="Defines the expected duration or delivery period.">
                            Estimated Duration (Days) <span class="ordo-tooltip-icon" tabindex="0">i</span>
                        </label>

                        <input
                            type="number"
                            name="estimated_duration"
                            data-tooltip="Defines the expected duration or delivery period."
                            value="{{ old(
                                'estimated_duration',
                                $deal->estimated_duration ?? ''
                            ) }}"
                        >

                    </div>


                    <div>

                        <label class="label" data-tooltip="Estimated completion date calculated from start date and duration.">
                            Estimated Completion Date <span class="ordo-tooltip-icon" tabindex="0">i</span>
                        </label>

                        <input
                            type="date"
                            name="estimated_completion_date"
                            data-tooltip="Estimated completion date calculated from start date and duration."
                            value="{{ old(
                                'estimated_completion_date',
                                $deal->estimated_completion_date ?? ''
                            ) }}"
                        >

                    </div>


                    <div>

                        <label class="label" data-tooltip="The client's preferred target completion deadline.">
                            Client Preferred Completion Date <span class="ordo-tooltip-icon" tabindex="0">i</span>
                        </label>

                        <input
                            type="date"
                            name="client_preferred_completion_date"
                            data-tooltip="The client's preferred target completion deadline."
                            value="{{ old(
                                'client_preferred_completion_date',
                                $deal->client_preferred_completion_date ?? ''
                            ) }}"
                        >

                    </div>


                    <div>

                        <label class="label" data-tooltip="Final agreed delivery date committed to client.">
                            Confirmed Delivery Date <span class="ordo-tooltip-icon" tabindex="0">i</span>
                        </label>

                        <input
                            type="date"
                            name="confirmed_delivery_date"
                            data-tooltip="Final agreed delivery date committed to client."
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


                <div x-data="{ selectedComplexity: '{{ old('complexity', $deal->complexity ?? '') }}' }" style="margin-top: 14px;">

                    <div class="radio-grid-2">

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
                                    x-model="selectedComplexity"
                                    {{ old(
                                        'complexity',
                                        $deal->complexity ?? ''
                                    ) == $complexity ? 'checked' : '' }}
                                >

                                {{ $complexity }}

                            </label>

                        @endforeach

                    </div>

                    <div x-show="selectedComplexity === 'Others'" x-cloak style="margin-top: 10px;">
                        <input
                            type="text"
                            name="other_complexity"
                            value="{{ old('other_complexity', $deal->other_complexity ?? '') }}"
                            placeholder="Enter custom support requirement and press Enter"
                            @keydown.enter.prevent
                        >
                    </div>

                </div>


                <div class="sub-label">
                    Professional Support Required
                </div>


                <div x-data="{ showOtherSupport: {{ in_array('Others', old('professional_support', $deal->professional_support ?? [])) ? 'true' : 'false' }} }">

                    <div class="service-grid">

                        @foreach([
                            'Requires Senior Consultant',
                            'Requires Subject Matter Expert',
                            'Requires Lawyer / Legal Counsel',
                            'Requires CPA / Certified Public Accountant'
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

                        <label class="check">

                            <input
                                type="checkbox"
                                name="professional_support[]"
                                value="Others"
                                x-model="showOtherSupport"
                                {{ in_array(
                                    'Others',
                                    old(
                                        'professional_support',
                                        $deal->professional_support ?? []
                                    )
                                ) ? 'checked' : '' }}
                            >

                            Others

                        </label>

                    </div>

                    <div x-show="showOtherSupport" x-cloak style="margin-top: 10px;">
                        <input
                            type="text"
                            name="other_professional_support"
                            value="{{ old('other_professional_support', $deal->other_professional_support ?? '') }}"
                            placeholder="Enter custom support requirement and press Enter"
                            @keydown.enter.prevent
                        >
                    </div>

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

                        <div
                            x-data="employeeDropdown('{{ old('assigned_consultant', $deal->assigned_consultant ?? '') }}')"
                            @click.outside="open = false"
                            style="position: relative;"
                        >
                            <input
                                type="text"
                                name="assigned_consultant"
                                x-model="value"
                                @focus="open = true"
                                @input="open = true"
                                autocomplete="off"
                            >

                            <div
                                x-show="open && filtered.length"
                                x-cloak
                                class="custom-scrollbar-no-arrows"
                                style="position: absolute; left: 0; right: 0; top: calc(100% + 4px); background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.06); max-height: 260px; overflow-y: auto; z-index: 50;"
                            >
                                <template x-for="emp in filtered" :key="'ac-' + emp.id + '-' + emp.name">
                                    <div
                                        @click="select(emp.name)"
                                        style="padding: 9px 12px; border-bottom: 1px solid #f1f5f9; cursor: pointer; text-align: left; transition: background 0.12s ease;"
                                        onmouseover="this.style.background='#f8fafc'"
                                        onmouseout="this.style.background='#ffffff'"
                                    >
                                        <div style="font-size: 13px; font-weight: 600; color: #1e293b; line-height: 1.3;" x-text="emp.name"></div>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px; line-height: 1.3;" x-text="emp.subtitle"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

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

                        <div
                            x-data="employeeDropdown('{{ old('assigned_associate', $deal->assigned_associate ?? '') }}')"
                            @click.outside="open = false"
                            style="position: relative;"
                        >
                            <input
                                type="text"
                                name="assigned_associate"
                                x-model="value"
                                @focus="open = true"
                                @input="open = true"
                                autocomplete="off"
                            >

                            <div
                                x-show="open && filtered.length"
                                x-cloak
                                class="custom-scrollbar-no-arrows"
                                style="position: absolute; left: 0; right: 0; top: calc(100% + 4px); background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.06); max-height: 260px; overflow-y: auto; z-index: 50;"
                            >
                                <template x-for="emp in filtered" :key="'aa-' + emp.id + '-' + emp.name">
                                    <div
                                        @click="select(emp.name)"
                                        style="padding: 9px 12px; border-bottom: 1px solid #f1f5f9; cursor: pointer; text-align: left; transition: background 0.12s ease;"
                                        onmouseover="this.style.background='#f8fafc'"
                                        onmouseout="this.style.background='#ffffff'"
                                    >
                                        <div style="font-size: 13px; font-weight: 600; color: #1e293b; line-height: 1.3;" x-text="emp.name"></div>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px; line-height: 1.3;" x-text="emp.subtitle"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

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

                        <div
                            x-data="employeeDropdown('{{ old('prepared_by', $deal->owner_name ?? optional(auth()->user())->name) }}')"
                            @click.outside="open = false"
                            style="position: relative;"
                        >
                            <input
                                type="text"
                                name="prepared_by"
                                x-model="value"
                                @focus="open = true"
                                @input="open = true"
                                autocomplete="off"
                            >

                            <div
                                x-show="open && filtered.length"
                                x-cloak
                                class="custom-scrollbar-no-arrows"
                                style="position: absolute; left: 0; right: 0; top: calc(100% + 4px); background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.06); max-height: 260px; overflow-y: auto; z-index: 50;"
                            >
                                <template x-for="emp in filtered" :key="'pb-' + emp.id + '-' + emp.name">
                                    <div
                                        @click="select(emp.name)"
                                        style="padding: 9px 12px; border-bottom: 1px solid #f1f5f9; cursor: pointer; text-align: left; transition: background 0.12s ease;"
                                        onmouseover="this.style.background='#f8fafc'"
                                        onmouseout="this.style.background='#ffffff'"
                                    >
                                        <div style="font-size: 13px; font-weight: 600; color: #1e293b; line-height: 1.3;" x-text="emp.name"></div>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px; line-height: 1.3;" x-text="emp.subtitle"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

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

                        <div
                            x-data="employeeDropdown('{{ old('reviewed_by', $deal->reviewed_by ?? '') }}')"
                            @click.outside="open = false"
                            style="position: relative;"
                        >
                            <input
                                type="text"
                                name="reviewed_by"
                                x-model="value"
                                @focus="open = true"
                                @input="open = true"
                                autocomplete="off"
                            >

                            <div
                                x-show="open && filtered.length"
                                x-cloak
                                class="custom-scrollbar-no-arrows"
                                style="position: absolute; left: 0; right: 0; top: calc(100% + 4px); background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.06); max-height: 260px; overflow-y: auto; z-index: 50;"
                            >
                                <template x-for="emp in filtered" :key="'rb-' + emp.id + '-' + emp.name">
                                    <div
                                        @click="select(emp.name)"
                                        style="padding: 9px 12px; border-bottom: 1px solid #f1f5f9; cursor: pointer; text-align: left; transition: background 0.12s ease;"
                                        onmouseover="this.style.background='#f8fafc'"
                                        onmouseout="this.style.background='#ffffff'"
                                    >
                                        <div style="font-size: 13px; font-weight: 600; color: #1e293b; line-height: 1.3;" x-text="emp.name"></div>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px; line-height: 1.3;" x-text="emp.subtitle"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

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

                        <div
                            x-data="employeeDropdown('{{ old('approval_name', $deal->approval_name ?? '') }}')"
                            @click.outside="open = false"
                            style="position: relative;"
                        >
                            <input
                                type="text"
                                name="approval_name"
                                x-model="value"
                                @focus="open = true"
                                @input="open = true"
                                autocomplete="off"
                            >

                            <div
                                x-show="open && filtered.length"
                                x-cloak
                                class="custom-scrollbar-no-arrows"
                                style="position: absolute; left: 0; right: 0; top: calc(100% + 4px); background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.06); max-height: 260px; overflow-y: auto; z-index: 50;"
                            >
                                <template x-for="emp in filtered" :key="'an-' + emp.id + '-' + emp.name">
                                    <div
                                        @click="select(emp.name)"
                                        style="padding: 9px 12px; border-bottom: 1px solid #f1f5f9; cursor: pointer; text-align: left; transition: background 0.12s ease;"
                                        onmouseover="this.style.background='#f8fafc'"
                                        onmouseout="this.style.background='#ffffff'"
                                    >
                                        <div style="font-size: 13px; font-weight: 600; color: #1e293b; line-height: 1.3;" x-text="emp.name"></div>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px; line-height: 1.3;" x-text="emp.subtitle"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

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
                            Referred By
                        </label>

                        <div
                            x-data="employeeDropdown('{{ old('referred_by', $deal->referred_by ?? '') }}')"
                            @click.outside="open = false"
                            style="position: relative;"
                        >
                            <input
                                type="text"
                                name="referred_by"
                                x-model="value"
                                @focus="open = true"
                                @input="open = true"
                                autocomplete="off"
                            >

                            <div
                                x-show="open && filtered.length"
                                x-cloak
                                class="custom-scrollbar-no-arrows"
                                style="position: absolute; left: 0; right: 0; top: calc(100% + 4px); background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.06); max-height: 260px; overflow-y: auto; z-index: 50;"
                            >
                                <template x-for="emp in filtered" :key="'ref-' + emp.id + '-' + emp.name">
                                    <div
                                        @click="select(emp.name)"
                                        style="padding: 9px 12px; border-bottom: 1px solid #f1f5f9; cursor: pointer; text-align: left; transition: background 0.12s ease;"
                                        onmouseover="this.style.background='#f8fafc'"
                                        onmouseout="this.style.background='#ffffff'"
                                    >
                                        <div style="font-size: 13px; font-weight: 600; color: #1e293b; line-height: 1.3;" x-text="emp.name"></div>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px; line-height: 1.3;" x-text="emp.subtitle"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div
                            class="section-description"
                            style="margin-top: 5px;"
                        >
                            Search existing employees or type manually.
                        </div>

                    </div>


                    <div>

                        <label class="label">
                            Closed By
                        </label>

                        <div
                            x-data="employeeDropdown('{{ old('closed_by', $deal->closed_by ?? '') }}')"
                            @click.outside="open = false"
                            style="position: relative;"
                        >
                            <input
                                type="text"
                                name="closed_by"
                                x-model="value"
                                @focus="open = true"
                                @input="open = true"
                                autocomplete="off"
                            >

                            <div
                                x-show="open && filtered.length"
                                x-cloak
                                class="custom-scrollbar-no-arrows"
                                style="position: absolute; left: 0; right: 0; top: calc(100% + 4px); background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.06); max-height: 260px; overflow-y: auto; z-index: 50;"
                            >
                                <template x-for="emp in filtered" :key="'cl-' + emp.id + '-' + emp.name">
                                    <div
                                        @click="select(emp.name)"
                                        style="padding: 9px 12px; border-bottom: 1px solid #f1f5f9; cursor: pointer; text-align: left; transition: background 0.12s ease;"
                                        onmouseover="this.style.background='#f8fafc'"
                                        onmouseout="this.style.background='#ffffff'"
                                    >
                                        <div style="font-size: 13px; font-weight: 600; color: #1e293b; line-height: 1.3;" x-text="emp.name"></div>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px; line-height: 1.3;" x-text="emp.subtitle"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

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

                        <div
                            x-data="employeeDropdown('{{ old('sales_marketing', $deal->sales_marketing ?? '') }}')"
                            @click.outside="open = false"
                            style="position: relative;"
                        >
                            <input
                                type="text"
                                name="sales_marketing"
                                x-model="value"
                                @focus="open = true"
                                @input="open = true"
                                autocomplete="off"
                            >

                            <div
                                x-show="open && filtered.length"
                                x-cloak
                                class="custom-scrollbar-no-arrows"
                                style="position: absolute; left: 0; right: 0; top: calc(100% + 4px); background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.06); max-height: 260px; overflow-y: auto; z-index: 50;"
                            >
                                <template x-for="emp in filtered" :key="'sm-' + emp.id + '-' + emp.name">
                                    <div
                                        @click="select(emp.name)"
                                        style="padding: 9px 12px; border-bottom: 1px solid #f1f5f9; cursor: pointer; text-align: left; transition: background 0.12s ease;"
                                        onmouseover="this.style.background='#f8fafc'"
                                        onmouseout="this.style.background='#ffffff'"
                                    >
                                        <div style="font-size: 13px; font-weight: 600; color: #1e293b; line-height: 1.3;" x-text="emp.name"></div>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px; line-height: 1.3;" x-text="emp.subtitle"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

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

                        <div
                            x-data="employeeDropdown('{{ old('lead_consultant', $deal->lead_consultant ?? '') }}')"
                            @click.outside="open = false"
                            style="position: relative;"
                        >
                            <input
                                type="text"
                                name="lead_consultant"
                                x-model="value"
                                @focus="open = true"
                                @input="open = true"
                                autocomplete="off"
                            >

                            <div
                                x-show="open && filtered.length"
                                x-cloak
                                class="custom-scrollbar-no-arrows"
                                style="position: absolute; left: 0; right: 0; top: calc(100% + 4px); background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.06); max-height: 260px; overflow-y: auto; z-index: 50;"
                            >
                                <template x-for="emp in filtered" :key="'lc-' + emp.id + '-' + emp.name">
                                    <div
                                        @click="select(emp.name)"
                                        style="padding: 9px 12px; border-bottom: 1px solid #f1f5f9; cursor: pointer; text-align: left; transition: background 0.12s ease;"
                                        onmouseover="this.style.background='#f8fafc'"
                                        onmouseout="this.style.background='#ffffff'"
                                    >
                                        <div style="font-size: 13px; font-weight: 600; color: #1e293b; line-height: 1.3;" x-text="emp.name"></div>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px; line-height: 1.3;" x-text="emp.subtitle"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

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

                        <div
                            x-data="employeeDropdown('{{ old('lead_associate', $deal->lead_associate ?? '') }}')"
                            @click.outside="open = false"
                            style="position: relative;"
                        >
                            <input
                                type="text"
                                name="lead_associate"
                                x-model="value"
                                @focus="open = true"
                                @input="open = true"
                                autocomplete="off"
                            >

                            <div
                                x-show="open && filtered.length"
                                x-cloak
                                class="custom-scrollbar-no-arrows"
                                style="position: absolute; left: 0; right: 0; top: calc(100% + 4px); background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.06); max-height: 260px; overflow-y: auto; z-index: 50;"
                            >
                                <template x-for="emp in filtered" :key="'la-' + emp.id + '-' + emp.name">
                                    <div
                                        @click="select(emp.name)"
                                        style="padding: 9px 12px; border-bottom: 1px solid #f1f5f9; cursor: pointer; text-align: left; transition: background 0.12s ease;"
                                        onmouseover="this.style.background='#f8fafc'"
                                        onmouseout="this.style.background='#ffffff'"
                                    >
                                        <div style="font-size: 13px; font-weight: 600; color: #1e293b; line-height: 1.3;" x-text="emp.name"></div>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px; line-height: 1.3;" x-text="emp.subtitle"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

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

                        <div
                            x-data="employeeDropdown('{{ old('finance', $deal->finance ?? '') }}')"
                            @click.outside="open = false"
                            style="position: relative;"
                        >
                            <input
                                type="text"
                                name="finance"
                                x-model="value"
                                @focus="open = true"
                                @input="open = true"
                                placeholder="Search user or type email manually"
                                autocomplete="off"
                            >

                            <div
                                x-show="open && filtered.length"
                                x-cloak
                                class="custom-scrollbar-no-arrows"
                                style="position: absolute; left: 0; right: 0; top: calc(100% + 4px); background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.06); max-height: 260px; overflow-y: auto; z-index: 50;"
                            >
                                <template x-for="emp in filtered" :key="'fin-' + emp.id + '-' + emp.name">
                                    <div
                                        @click="select(emp.name)"
                                        style="padding: 9px 12px; border-bottom: 1px solid #f1f5f9; cursor: pointer; text-align: left; transition: background 0.12s ease;"
                                        onmouseover="this.style.background='#f8fafc'"
                                        onmouseout="this.style.background='#ffffff'"
                                    >
                                        <div style="font-size: 13px; font-weight: 600; color: #1e293b; line-height: 1.3;" x-text="emp.name"></div>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px; line-height: 1.3;" x-text="emp.subtitle"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

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

                        <div
                            x-data="employeeDropdown('{{ old('president', $deal->president ?? 'John Kelly') }}')"
                            @click.outside="open = false"
                            style="position: relative;"
                        >
                            <input
                                type="text"
                                name="president"
                                x-model="value"
                                @focus="open = true"
                                @input="open = true"
                                autocomplete="off"
                            >

                            <div
                                x-show="open && filtered.length"
                                x-cloak
                                class="custom-scrollbar-no-arrows"
                                style="position: absolute; left: 0; right: 0; top: calc(100% + 4px); background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.06); max-height: 260px; overflow-y: auto; z-index: 50;"
                            >
                                <template x-for="emp in filtered" :key="'pres-' + emp.id + '-' + emp.name">
                                    <div
                                        @click="select(emp.name)"
                                        style="padding: 9px 12px; border-bottom: 1px solid #f1f5f9; cursor: pointer; text-align: left; transition: background 0.12s ease;"
                                        onmouseover="this.style.background='#f8fafc'"
                                        onmouseout="this.style.background='#ffffff'"
                                    >
                                        <div style="font-size: 13px; font-weight: 600; color: #1e293b; line-height: 1.3;" x-text="emp.name"></div>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px; line-height: 1.3;" x-text="emp.subtitle"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div
                            class="section-description"
                            style="margin-top: 5px;"
                        >
                            Defaults to John Kelly.
                        </div>

                    </div>

                </div>

            </section>

            <!-- ADD CONTACT MODAL (BUSINESS & INDIVIDUAL) -->
            <div
                x-show="contactModalOpen"
                x-cloak
                style="position: fixed; inset: 0; z-index: 1000; display: flex; align-items: center; justify-content: center; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); padding: 16px;"
                @keydown.escape.window="if (!contactSaving) contactModalOpen = false"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="contactModalType === 'Business' ? 'modal-biz-title' : 'modal-ind-title'"
            >
                <div
                    @click.outside="if (!contactSaving) contactModalOpen = false"
                    style="background: #ffffff; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); width: 100%; max-width: 580px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; border: 1px solid #e2e8f0;"
                >
                    <!-- Modal Header -->
                    <div style="padding: 18px 24px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; background: #ffffff; flex-shrink: 0;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: #eff6ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg style="width: 20px; height: 20px; color: #2563eb;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3
                                    :id="contactModalType === 'Business' ? 'modal-biz-title' : 'modal-ind-title'"
                                    style="margin: 0; font-size: 17px; font-weight: 700; color: #0f172a; line-height: 1.2;"
                                    x-text="contactModalType === 'Business' ? 'Add Business Contact' : 'Add Individual Contact'"
                                ></h3>
                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;" x-text="contactModalType === 'Business' ? 'Register a company representative or primary corporate contact.' : 'Register a direct individual client or personal contact.'"></div>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="contactModalOpen = false"
                            :disabled="contactSaving"
                            aria-label="Close modal"
                            style="background: transparent; border: 0; font-size: 22px; color: #94a3b8; cursor: pointer; padding: 4px; line-height: 1; border-radius: 6px; transition: color 0.15s ease;"
                            onmouseover="this.style.color='#475569'"
                            onmouseout="this.style.color='#94a3b8'"
                        >&times;</button>
                    </div>

                    <!-- Modal Body -->
                    <div class="custom-scrollbar-no-arrows" style="padding: 22px 24px; overflow-y: auto; flex: 1; display: flex; flex-direction: column;">
                        
                        <!-- Top Error Alert -->
                        <div
                            x-show="contactError"
                            x-cloak
                            style="padding: 10px 14px; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 8px; font-size: 12px; line-height: 1.4; display: flex; align-items: flex-start; gap: 8px; margin-bottom: 16px;"
                        >
                            <svg style="width: 16px; height: 16px; color: #dc2626; flex-shrink: 0; margin-top: 1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <span x-text="contactError"></span>
                        </div>

                        <!-- Required Fields Indicator Note -->
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 20px;">
                            Fields marked with <span style="color: #ef4444; font-weight: 700;">*</span> are required.
                        </div>

                        <!-- ==============================================
                             BUSINESS CONTACT FORM
                        ============================================== -->
                        <div x-show="contactModalType === 'Business'" style="display: flex; flex-direction: column;">
                            
                            <!-- SECTION 1: REPRESENTATIVE INFORMATION -->
                            <div style="margin-bottom: 24px;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px;">
                                    <svg style="width: 14px; height: 14px; color: #2563eb;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #1e293b;">
                                        REPRESENTATIVE INFORMATION
                                    </span>
                                </div>

                                <!-- Row 1: Salutation | First Name * -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <label for="biz_salutation" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                            Salutation
                                        </label>
                                        <select
                                            id="biz_salutation"
                                            x-model="newContact.salutation"
                                            style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #334155; background: #ffffff; box-sizing: border-box; margin: 0;"
                                        >
                                            <option value="">None</option>
                                            <option value="Mr.">Mr.</option>
                                            <option value="Ms.">Ms.</option>
                                            <option value="Mrs.">Mrs.</option>
                                            <option value="Atty.">Atty.</option>
                                            <option value="Dr.">Dr.</option>
                                            <option value="Engr.">Engr.</option>
                                        </select>
                                    </div>

                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <label for="biz_first_name" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                            First Name <span style="color: #ef4444; font-weight: 700; margin-left: 2px;">*</span>
                                        </label>
                                        <input
                                            id="biz_first_name"
                                            type="text"
                                            x-model="newContact.first_name"
                                            placeholder="First name"
                                            aria-required="true"
                                            :style="fieldErrors.first_name ? 'border-color: #ef4444; background: #fffaf0;' : ''"
                                            style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #1e293b; background: #ffffff; box-sizing: border-box; margin: 0;"
                                            @input="fieldErrors.first_name = false"
                                        >
                                    </div>
                                </div>

                                <!-- Row 2: Middle Name | Last Name * -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <label for="biz_middle_name" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                            Middle Name
                                        </label>
                                        <input
                                            id="biz_middle_name"
                                            type="text"
                                            x-model="newContact.middle_name"
                                            placeholder="Middle name"
                                            style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #1e293b; background: #ffffff; box-sizing: border-box; margin: 0;"
                                        >
                                    </div>

                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <label for="biz_last_name" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                            Last Name <span style="color: #ef4444; font-weight: 700; margin-left: 2px;">*</span>
                                        </label>
                                        <input
                                            id="biz_last_name"
                                            type="text"
                                            x-model="newContact.last_name"
                                            placeholder="Last name"
                                            aria-required="true"
                                            :style="fieldErrors.last_name ? 'border-color: #ef4444; background: #fffaf0;' : ''"
                                            style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #1e293b; background: #ffffff; box-sizing: border-box; margin: 0;"
                                            @input="fieldErrors.last_name = false"
                                        >
                                    </div>
                                </div>

                                <!-- Row 3: Extension -->
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    <label for="biz_extension" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                        Extension
                                    </label>
                                    <input
                                        id="biz_extension"
                                        type="text"
                                        x-model="newContact.name_extension"
                                        placeholder="Jr., III..."
                                        style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #1e293b; background: #ffffff; box-sizing: border-box; margin: 0;"
                                    >
                                </div>
                            </div>

                            <!-- SECTION 2: COMPANY POSITION -->
                            <div style="margin-bottom: 24px;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px;">
                                    <svg style="width: 14px; height: 14px; color: #2563eb;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #1e293b;">
                                        COMPANY POSITION
                                    </span>
                                </div>

                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    <label for="biz_position" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                        Position in Company
                                    </label>
                                    <input
                                        id="biz_position"
                                        type="text"
                                        x-model="newContact.position"
                                        placeholder="e.g. Managing Director, CEO, Manager"
                                        style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #1e293b; background: #ffffff; box-sizing: border-box; margin: 0;"
                                    >
                                </div>
                            </div>

                            <!-- SECTION 3: CORPORATE CONTACT DETAILS -->
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px;">
                                    <svg style="width: 14px; height: 14px; color: #2563eb;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #1e293b;">
                                        CORPORATE CONTACT DETAILS
                                    </span>
                                </div>

                                <!-- Row 1: Work Email | Work / Mobile Number -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <label for="biz_email" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                            Work Email
                                        </label>
                                        <input
                                            id="biz_email"
                                            type="email"
                                            x-model="newContact.email"
                                            placeholder="representative@company.com"
                                            style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #1e293b; background: #ffffff; box-sizing: border-box; margin: 0;"
                                        >
                                    </div>

                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <label for="biz_mobile" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                            Work / Mobile Number
                                        </label>
                                        <input
                                            id="biz_mobile"
                                            type="text"
                                            x-model="newContact.mobile_number"
                                            placeholder="09123456789"
                                            style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #1e293b; background: #ffffff; box-sizing: border-box; margin: 0;"
                                        >
                                    </div>
                                </div>

                                <!-- Row 2: Office / Business Address -->
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    <label for="biz_address" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                        Office / Business Address
                                    </label>
                                    <textarea
                                        id="biz_address"
                                        x-model="newContact.address"
                                        rows="2"
                                        placeholder="Enter corporate office address..."
                                        style="width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 13px; color: #1e293b; background: #ffffff; min-height: 58px; resize: vertical; box-sizing: border-box; font-family: inherit; margin: 0;"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- ==============================================
                             INDIVIDUAL CONTACT FORM
                        ============================================== -->
                        <div x-show="contactModalType === 'Individual'" style="display: flex; flex-direction: column;">
                            
                            <!-- SECTION 1: INDIVIDUAL INFORMATION -->
                            <div style="margin-bottom: 24px;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px;">
                                    <svg style="width: 14px; height: 14px; color: #2563eb;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #1e293b;">
                                        INDIVIDUAL INFORMATION
                                    </span>
                                </div>

                                <!-- Row 1: Salutation | First Name * -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <label for="ind_salutation" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                            Salutation
                                        </label>
                                        <select
                                            id="ind_salutation"
                                            x-model="newContact.salutation"
                                            style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #334155; background: #ffffff; box-sizing: border-box; margin: 0;"
                                        >
                                            <option value="">None</option>
                                            <option value="Mr.">Mr.</option>
                                            <option value="Ms.">Ms.</option>
                                            <option value="Mrs.">Mrs.</option>
                                            <option value="Atty.">Atty.</option>
                                            <option value="Dr.">Dr.</option>
                                            <option value="Engr.">Engr.</option>
                                        </select>
                                    </div>

                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <label for="ind_first_name" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                            First Name <span style="color: #ef4444; font-weight: 700; margin-left: 2px;">*</span>
                                        </label>
                                        <input
                                            id="ind_first_name"
                                            type="text"
                                            x-model="newContact.first_name"
                                            placeholder="First name"
                                            aria-required="true"
                                            :style="fieldErrors.first_name ? 'border-color: #ef4444; background: #fffaf0;' : ''"
                                            style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #1e293b; background: #ffffff; box-sizing: border-box; margin: 0;"
                                            @input="fieldErrors.first_name = false"
                                        >
                                    </div>
                                </div>

                                <!-- Row 2: Middle Name | Last Name * -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <label for="ind_middle_name" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                            Middle Name
                                        </label>
                                        <input
                                            id="ind_middle_name"
                                            type="text"
                                            x-model="newContact.middle_name"
                                            placeholder="Middle name"
                                            style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #1e293b; background: #ffffff; box-sizing: border-box; margin: 0;"
                                        >
                                    </div>

                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <label for="ind_last_name" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                            Last Name <span style="color: #ef4444; font-weight: 700; margin-left: 2px;">*</span>
                                        </label>
                                        <input
                                            id="ind_last_name"
                                            type="text"
                                            x-model="newContact.last_name"
                                            placeholder="Last name"
                                            aria-required="true"
                                            :style="fieldErrors.last_name ? 'border-color: #ef4444; background: #fffaf0;' : ''"
                                            style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #1e293b; background: #ffffff; box-sizing: border-box; margin: 0;"
                                            @input="fieldErrors.last_name = false"
                                        >
                                    </div>
                                </div>

                                <!-- Row 3: Extension -->
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    <label for="ind_extension" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                        Extension
                                    </label>
                                    <input
                                        id="ind_extension"
                                        type="text"
                                        x-model="newContact.name_extension"
                                        placeholder="Jr., III..."
                                        style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #1e293b; background: #ffffff; box-sizing: border-box; margin: 0;"
                                    >
                                </div>
                            </div>

                            <!-- SECTION 2: PROFESSION & PERSONAL DETAILS -->
                            <div style="margin-bottom: 24px;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px;">
                                    <svg style="width: 14px; height: 14px; color: #2563eb;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #1e293b;">
                                        PROFESSION &amp; PERSONAL DETAILS
                                    </span>
                                </div>

                                <!-- Row 1: Profession / Occupation -->
                                <div style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px;">
                                    <label for="ind_position" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                        Profession / Occupation
                                    </label>
                                    <input
                                        id="ind_position"
                                        type="text"
                                        x-model="newContact.position"
                                        placeholder="e.g. Consultant, Architect, Freelancer"
                                        style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #1e293b; background: #ffffff; box-sizing: border-box; margin: 0;"
                                    >
                                </div>

                                <!-- Row 2: Sex | Date of Birth -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <label for="ind_sex" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                            Sex
                                        </label>
                                        <select
                                            id="ind_sex"
                                            x-model="newContact.sex"
                                            style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #334155; background: #ffffff; box-sizing: border-box; margin: 0;"
                                        >
                                            <option value="">Select Sex</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>

                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <label for="ind_dob" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                            Date of Birth
                                        </label>
                                        <input
                                            id="ind_dob"
                                            type="date"
                                            x-model="newContact.date_of_birth"
                                            style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #1e293b; background: #ffffff; box-sizing: border-box; margin: 0;"
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION 3: PERSONAL CONTACT DETAILS -->
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px;">
                                    <svg style="width: 14px; height: 14px; color: #2563eb;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #1e293b;">
                                        PERSONAL CONTACT DETAILS
                                    </span>
                                </div>

                                <!-- Row 1: Email Address | Mobile Number -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <label for="ind_email" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                            Email Address
                                        </label>
                                        <input
                                            id="ind_email"
                                            type="email"
                                            x-model="newContact.email"
                                            placeholder="personal@example.com"
                                            style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #1e293b; background: #ffffff; box-sizing: border-box; margin: 0;"
                                        >
                                    </div>

                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <label for="ind_mobile" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                            Mobile Number
                                        </label>
                                        <input
                                            id="ind_mobile"
                                            type="text"
                                            x-model="newContact.mobile_number"
                                            placeholder="09123456789"
                                            style="width: 100%; height: 38px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; font-size: 13px; color: #1e293b; background: #ffffff; box-sizing: border-box; margin: 0;"
                                        >
                                    </div>
                                </div>

                                <!-- Row 2: Residential Address -->
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    <label for="ind_address" style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2;">
                                        Residential Address
                                    </label>
                                    <textarea
                                        id="ind_address"
                                        x-model="newContact.address"
                                        rows="2"
                                        placeholder="Enter home / residential address..."
                                        style="width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 13px; color: #1e293b; background: #ffffff; min-height: 58px; resize: vertical; box-sizing: border-box; font-family: inherit; margin: 0;"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Modal Footer (Always Sticky & Accessible) -->
                    <div style="padding: 14px 24px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: flex-end; gap: 10px; background: #ffffff; flex-shrink: 0;">
                        <button
                            type="button"
                            @click="contactModalOpen = false"
                            :disabled="contactSaving"
                            class="btn"
                            style="height: 38px; padding: 0 20px; border: 1px solid #d1d5db; background: #ffffff; color: #334155; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.15s ease;"
                            onmouseover="if (!this.disabled) this.style.background='#f8fafc'"
                            onmouseout="this.style.background='#ffffff'"
                        >Cancel</button>

                        <button
                            type="button"
                            @click="submitQuickContact()"
                            :disabled="contactSaving || !newContact.first_name || !newContact.last_name"
                            class="btn"
                            :style="(contactSaving || !newContact.first_name || !newContact.last_name) ? 'height: 38px; padding: 0 20px; border: 0; background: #93c5fd; color: #ffffff; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: not-allowed; display: inline-flex; align-items: center; gap: 8px;' : 'height: 38px; padding: 0 20px; border: 0; background: #2563eb; color: #ffffff; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: background 0.15s ease;'"
                            onmouseover="if (!this.disabled) this.style.background='#1d4ed8'"
                            onmouseout="if (!this.disabled) this.style.background='#2563eb'"
                        >
                            <svg x-show="contactSaving" style="width: 16px; height: 16px; animation: spin 1s linear infinite;" fill="none" viewBox="0 0 24 24">
                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-show="contactSaving">Saving...</span>
                            <span x-show="!contactSaving" x-text="contactModalType === 'Business' ? 'Save Business Contact' : 'Save Individual Contact'"></span>
                        </button>
                    </div>
                </div>
            </div>

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
                :disabled="isSubmitting"
                :style="isSubmitting ? 'opacity: 0.7; cursor: not-allowed; display: inline-flex; align-items: center; gap: 8px;' : 'display: inline-flex; align-items: center; gap: 8px;'"
            >
                <svg x-show="isSubmitting" style="width: 15px; height: 15px; animation: spin 1s linear infinite;" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="isSubmitting ? 'Saving Deal...' : '{{ isset($deal) ? 'Save Changes' : 'Save & View Deal' }}'"></span>
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
            'hgh',
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

    $servicePriceMap = [
        'Accounting & Compliance Advisory' => [
            ['name' => 'AFS Preparation', 'price' => 2500],
            ['name' => 'Accounting Services', 'price' => 2500],
            ['name' => 'Audit Support / Coordination', 'price' => 2500],
            ['name' => 'BIR RDO Compliance Representative', 'price' => 5000],
            ['name' => 'BIR Registration Assistance (L)', 'price' => 100000],
            ['name' => 'BIR Registration Assistance (M)', 'price' => 15000],
            ['name' => 'BIR Registration Assistance (S)', 'price' => 20000],
            ['name' => 'BIR Registration- Update/ Change Information', 'price' => 3000],
            ['name' => 'Bir Open Case Resolution', 'price' => 5000],
            ['name' => 'Bookkeeping Services', 'price' => 5000],
            ['name' => 'Business Permit', 'price' => 3000],
            ['name' => 'On-site Profit and Loss Review', 'price' => 3000],
            ['name' => 'SAWT Preparation and eSubmission Validation', 'price' => 0],
            ['name' => 'Tax Filing & Compliance (BIR)', 'price' => 2500],
            ['name' => 'Transfer of BIR Registration from RDO 80 to RDO 81', 'price' => 15000],
            ['name' => 'Transfer of Shares of Stock Assistance', 'price' => 25000],
        ],
        'Corporate & Regulatory Advisory' => [
            ['name' => 'AMLC Registration and Compliance Officer Setup Assistance', 'price' => 5000],
            ['name' => 'BIR Registration Assistance (MM)', 'price' => 30000],
            ['name' => 'Bank Opening', 'price' => 5000],
            ['name' => 'Business Registration (SEC / DTI / BIR)', 'price' => 2500],
            ['name' => 'Corporate Secretary Services (M)', 'price' => 5000],
            ['name' => 'Corporation Formation & Registration Assistance (L)', 'price' => 100000],
            ['name' => 'Corporation Formation & Registration Assistance (M)', 'price' => 15000],
            ['name' => 'Corporation Formation & Registration Assistance (MM)', 'price' => 30000],
            ['name' => 'Corporation Formation & Registration Assistance (S)', 'price' => 20000],
            ['name' => 'Foreign Business Entry Support', 'price' => 2500],
            ['name' => 'LGU Compliance Representative', 'price' => 5000],
            ['name' => 'Loan Application Assistance', 'price' => 2500],
            ['name' => 'New / Renewal LGU City/Municipality Business Registration Assistance — Complex', 'price' => 25000],
            ['name' => 'New LGU City/Municipality Business Registration Assistance — Non-Complex', 'price' => 15000],
            ['name' => 'Regulatory Compliance', 'price' => 2500],
            ['name' => 'SEC Compliance Representative', 'price' => 5000],
            ['name' => 'hgh', 'price' => 565],
        ],
        'Learning & Capability Development' => [
            ['name' => 'Accounting & Compliance Training', 'price' => 5000],
            ['name' => 'Business & Strategy Training', 'price' => 5000],
            ['name' => 'Client Capability Development Programs', 'price' => 10000],
            ['name' => 'Corporate Governance Workshops', 'price' => 7500],
            ['name' => 'JKNC Academy Courses', 'price' => 3500],
        ],
        'Service Add-Ons' => [
            ['name' => 'Travel Credits — Cebu City, Mandaue City & Lapu-Lapu City', 'price' => 1500],
            ['name' => 'Travel Credits — Metro Cebu', 'price' => 2500],
        ],
        'Business Strategy & Process Advisory' => [
            ['name' => 'Digital Transformation', 'price' => 2500],
            ['name' => 'Domain Purchase and Setup Assistance', 'price' => 5000],
            ['name' => 'Financial Planning & Analysis', 'price' => 2500],
            ['name' => 'Organizational Structuring', 'price' => 2500],
            ['name' => 'Process Improvement / SOP Development', 'price' => 2500],
        ],
        'Governance & Policy Advisory' => [
            ['name' => 'Board Resolutions & Minutes', 'price' => 2500],
            ['name' => 'Corporate Officers Services', 'price' => 2500],
            ['name' => 'Corporate Secretary Services', 'price' => 2500],
            ['name' => 'Middle Management Advisory Consultation', 'price' => 11000],
            ['name' => 'Policy Development (HR, Finance, Ops)', 'price' => 2500],
            ['name' => 'Risk & Internal Control Setup', 'price' => 2500],
        ],
        'People & Talent Solutions' => [
            ['name' => 'Executive / Virtual Assistant Support', 'price' => 2500],
            ['name' => 'HR Documentation & Contracts', 'price' => 2500],
            ['name' => 'HR Structuring & Organization Design', 'price' => 2500],
            ['name' => 'KPI & Performance Management Systems', 'price' => 25000],
            ['name' => 'Managed Administrative Support Services', 'price' => 35000],
            ['name' => 'Recruitment & Hiring Support', 'price' => 2500],
        ],
        'Strategic Situations Advisory' => [
            ['name' => 'Business Restructuring Strategy', 'price' => 2500],
            ['name' => 'Corporate Deadlock Resolution', 'price' => 2500],
            ['name' => 'Crisis Assessment & Stabilization', 'price' => 2500],
            ['name' => 'High-Risk / Complex Case Advisory', 'price' => 2500],
            ['name' => 'Middle Management Advisory Consultation', 'price' => 11000],
            ['name' => 'Stakeholder Negotiation Support', 'price' => 2500],
        ],
    ];

    $productPriceMap = [
        'Accounting & Compliance Advisory' => [
            ['name' => 'Archive Retrieval', 'price' => 350],
            ['name' => 'Digital Archive Copy', 'price' => 350],
            ['name' => 'Drafting of Certifications', 'price' => 350],
            ['name' => 'Drafting of Compliance Documents', 'price' => 350],
            ['name' => 'Drafting of Memorandum (Internal / External)', 'price' => 350],
            ['name' => 'Drafting of Responses to Letters / Notices', 'price' => 350],
            ['name' => 'Stock Certificate Printing', 'price' => 300],
        ],
        'Corporate & Regulatory Advisory' => [
            ['name' => 'Drafting of Demand Letters', 'price' => 350],
            ['name' => 'Drafting of Emails (Formal / Business)', 'price' => 350],
            ['name' => 'Drafting of Letters', 'price' => 350],
            ['name' => 'Drafting of Notices', 'price' => 350],
            ['name' => 'Photocopy', 'price' => 350],
            ['name' => 'Printing', 'price' => 350],
            ['name' => 'Stock Certificate Printing', 'price' => 300],
        ],
        'Learning & Capability Development' => [
            ['name' => 'Archive Retrieval', 'price' => 350],
            ['name' => 'Digital Archive Copy', 'price' => 350],
            ['name' => 'Drafting of Certifications', 'price' => 350],
            ['name' => 'Drafting of Compliance Documents', 'price' => 350],
            ['name' => 'Drafting of Memorandum (Internal / External)', 'price' => 350],
            ['name' => 'Drafting of Responses to Letters / Notices', 'price' => 350],
            ['name' => 'Drafting of Demand Letters', 'price' => 350],
            ['name' => 'Drafting of Emails (Formal / Business)', 'price' => 350],
            ['name' => 'Drafting of Letters', 'price' => 350],
            ['name' => 'Drafting of Notices', 'price' => 350],
            ['name' => 'Photocopy', 'price' => 350],
            ['name' => 'Printing', 'price' => 350],
            ['name' => 'Stock Certificate Printing', 'price' => 300],
        ],
        'Business Strategy & Process Advisory' => [
            ['name' => 'Drafting of Policies & Procedures', 'price' => 350],
            ['name' => 'Drafting of Reports / Formal Documents', 'price' => 350],
            ['name' => 'Drafting of Secretary\'s Certificates', 'price' => 350],
            ['name' => 'Notarization - Complex Documents', 'price' => 350],
            ['name' => 'Notarization - Simple Documents', 'price' => 350],
            ['name' => 'Stock Certificate Printing', 'price' => 300],
        ],
        'Governance & Policy Advisory' => [
            ['name' => 'Document Delivery (Metro Cebu)', 'price' => 350],
            ['name' => 'Document Delivery (Outside Metro Cebu/LBC)', 'price' => 350],
            ['name' => 'Drafting of Affidavits (Non-Legal Advice)', 'price' => 350],
            ['name' => 'Drafting of Agreements / Simple Contracts', 'price' => 350],
            ['name' => 'Drafting of Board Resolutions', 'price' => 350],
            ['name' => 'Drafting of Endorsement / Request Letters', 'price' => 350],
            ['name' => 'Document Delivery (Metro Cebu)', 'price' => 350],
            ['name' => 'Document Delivery (Outside Metro Cebu/LBC)', 'price' => 350],
            ['name' => 'Drafting of Affidavits (Non-Legal Advice)', 'price' => 350],
            ['name' => 'Drafting of Agreements / Simple Contracts', 'price' => 350],
            ['name' => 'Drafting of Board Resolutions', 'price' => 350],
            ['name' => 'Drafting of Endorsement / Request Letters', 'price' => 350],
            ['name' => 'Stock Certificate Printing', 'price' => 300],
        ],
        'People & Talent Solutions' => [
            ['name' => 'Archive Retrieval', 'price' => 350],
            ['name' => 'Digital Archive Copy', 'price' => 350],
            ['name' => 'Drafting of Certifications', 'price' => 350],
            ['name' => 'Drafting of Compliance Documents', 'price' => 350],
            ['name' => 'Drafting of Memorandum (Internal / External)', 'price' => 350],
            ['name' => 'Drafting of Responses to Letters / Notices', 'price' => 350],
            ['name' => 'Archive Retrieval', 'price' => 350],
            ['name' => 'Digital Archive Copy', 'price' => 350],
            ['name' => 'Drafting of Certifications', 'price' => 350],
            ['name' => 'Drafting of Compliance Documents', 'price' => 350],
            ['name' => 'Drafting of Memorandum (Internal / External)', 'price' => 350],
            ['name' => 'Drafting of Responses to Letters / Notices', 'price' => 350],
            ['name' => 'Stock Certificate Printing', 'price' => 300],
        ],
        'Strategic Situations Advisory' => [
            ['name' => 'Drafting of Demand Letters', 'price' => 350],
            ['name' => 'Drafting of Emails (Formal / Business)', 'price' => 350],
            ['name' => 'Drafting of Letters', 'price' => 350],
            ['name' => 'Drafting of Notices', 'price' => 350],
            ['name' => 'Photocopy', 'price' => 350],
            ['name' => 'Printing', 'price' => 350],
            ['name' => 'Drafting of Demand Letters', 'price' => 350],
            ['name' => 'Drafting of Emails (Formal / Business)', 'price' => 350],
            ['name' => 'Drafting of Letters', 'price' => 350],
            ['name' => 'Drafting of Notices', 'price' => 350],
            ['name' => 'Photocopy', 'price' => 350],
            ['name' => 'Printing', 'price' => 350],
            ['name' => 'Stock Certificate Printing', 'price' => 300],
        ],
    ];

    $productsList = [
        ['name' => 'Archive Retrieval', 'price' => 350],
        ['name' => 'Digital Archive Copy', 'price' => 350],
        ['name' => 'Drafting of Certifications', 'price' => 350],
        ['name' => 'Drafting of Compliance Documents', 'price' => 350],
        ['name' => 'Drafting of Memorandum (Internal / External)', 'price' => 350],
        ['name' => 'Drafting of Responses to Letters / Notices', 'price' => 350],
        ['name' => 'Drafting of Demand Letters', 'price' => 350],
        ['name' => 'Drafting of Emails (Formal / Business)', 'price' => 350],
        ['name' => 'Drafting of Letters', 'price' => 350],
        ['name' => 'Drafting of Notices', 'price' => 350],
        ['name' => 'Photocopy', 'price' => 350],
        ['name' => 'Printing', 'price' => 350],
        ['name' => 'Stock Certificate Printing', 'price' => 300],
    ];

@endphp


<script>
    const dealServiceMap = @json($dealServiceMap);
    const dealServicePriceMap = @json($servicePriceMap);
    const dealProductPriceMap = @json($productPriceMap);
    const dealProductsList = @json($productsList);


    function dealFormState() {
        return {
            isSubmitting: false,
            accountId: @js(old('account_id', $deal->account_id ?? '')),
            contactId: @js(old('contact_id', $deal->contact_id ?? '')),
            companyId: @js(old('company_id', $deal->company_id ?? '')),

            accounts: @js(
                ($accounts ?? collect())->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'account_code' => $account->account_code,
                        'account_type' => $account->account_type,
                        'account_name' => $account->account_name,
                        'company_id' => $account->company_id,
                        'individual_contact_id' => $account->individual_contact_id,
                        'status' => $account->status,
                    ];
                })->values()
            ),

            companies: @js(
                ($companies ?? collect())->map(function ($company) {
                    return [
                        'id' => $company->id,
                        'company_code' => $company->company_code,
                        'company_name' => $company->company_name,
                        'industry' => $company->industry,
                        'address' => $company->address,
                        'email' => $company->email,
                        'phone' => $company->phone,
                        'status' => $company->status,
                    ];
                })->values()
            ),

            contacts: @js(
                ($contacts ?? collect())->map(function ($contact) {
                    return [
                        'id' => $contact->id,
                        'contact_code' => $contact->contact_code,
                        'contact_type' => $contact->contact_type,
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
                        'company_id' => $contact->company_id,
                        'position' => $contact->position,
                        'status' => $contact->status,
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
            servicePriceMap: dealServicePriceMap,
            productPriceMap: dealProductPriceMap,
            productsList: dealProductsList,

            getFilteredProducts() {
                if (!this.selectedServiceAreas || this.selectedServiceAreas.length === 0) {
                    return [];
                }
                let list = [];
                this.selectedServiceAreas.forEach(area => {
                    if (this.productPriceMap && this.productPriceMap[area]) {
                        list.push(...this.productPriceMap[area]);
                    }
                });
                return list;
            },

            init() {
                if (this.accountId) {
                    this.loadAccount(false);
                    return;
                }

                if (this.companyId) {
                    this.loadCompany(false);
                }

                if (this.contactId) {
                    this.loadContact();
                }
            },

            filteredContacts() {
                if (this.customerType === 'Individual') {
                    const account = this.findAccount();

                    if (account && account.individual_contact_id) {
                        return this.contacts.filter(contact =>
                            String(contact.id) === String(account.individual_contact_id) ||
                            String(contact.id) === String(this.contactId)
                        );
                    }

                    return this.contacts.filter(contact =>
                        contact.contact_type === 'Individual' || !contact.company_id || String(contact.id) === String(this.contactId)
                    );
                }

                if (!this.companyId) {
                    return this.contacts.filter(contact =>
                        contact.contact_type === 'Business' || contact.company_id || String(contact.id) === String(this.contactId)
                    );
                }

                return this.contacts.filter(contact =>
                    String(contact.company_id) === String(this.companyId) || String(contact.id) === String(this.contactId)
                );
            },

            selectedContactName() {
                const contact = this.findContact();

                if (!contact) {
                    return '';
                }

                return [
                    contact.salutation,
                    contact.first_name,
                    contact.middle_name,
                    contact.last_name,
                    contact.name_extension,
                ].filter(Boolean).join(' ');
            },

            handleCustomerTypeChange() {
                const account = this.findAccount();

                if (account) {
                    const accountType = String(account.account_type || '').toLowerCase();
                    const isIndividual = Boolean(account.individual_contact_id) || accountType.includes('individual');
                    const isBusiness = !isIndividual && (Boolean(account.company_id) || accountType.includes('business') || accountType.includes('company'));

                    if ((this.customerType === 'Business' && isBusiness) || (this.customerType === 'Individual' && isIndividual)) {
                        this.loadAccount(false);
                        return;
                    }
                }

                this.accountId = '';
                this.contactId = '';
                this.companyId = '';
                this.clearContactFields();
                this.clearCompanyFields();
            },

            contactModalOpen: false,
            contactModalType: 'Business',
            contactSaving: false,
            contactError: '',
            fieldErrors: {
                first_name: false,
                last_name: false,
            },
            newContact: {
                salutation: '',
                first_name: '',
                middle_name: '',
                last_name: '',
                name_extension: '',
                sex: '',
                date_of_birth: '',
                email: '',
                mobile_number: '',
                address: '',
                company_id: '',
                position: '',
            },

            openAddContact(type = null) {
                this.contactModalType = type || this.customerType || 'Business';
                this.contactError = '';
                this.fieldErrors = {
                    first_name: false,
                    last_name: false,
                };
                this.newContact = {
                    salutation: '',
                    first_name: '',
                    middle_name: '',
                    last_name: '',
                    name_extension: '',
                    sex: '',
                    date_of_birth: '',
                    email: '',
                    mobile_number: '',
                    address: '',
                    company_id: this.contactModalType === 'Business' ? (this.companyId || '') : '',
                    position: '',
                };
                this.contactModalOpen = true;
            },

            async submitQuickContact() {
                this.fieldErrors = {
                    first_name: !this.newContact.first_name || !this.newContact.first_name.trim(),
                    last_name: !this.newContact.last_name || !this.newContact.last_name.trim(),
                };

                if (this.fieldErrors.first_name && this.fieldErrors.last_name) {
                    this.contactError = 'First name and Last name are required.';
                    return;
                }
                if (this.fieldErrors.first_name) {
                    this.contactError = 'First name is required.';
                    return;
                }
                if (this.fieldErrors.last_name) {
                    this.contactError = 'Last name is required.';
                    return;
                }

                this.contactSaving = true;
                this.contactError = '';

                try {
                    const payload = {
                        contact_type: this.contactModalType,
                        salutation: this.newContact.salutation || null,
                        first_name: this.newContact.first_name.trim(),
                        middle_name: this.newContact.middle_name ? this.newContact.middle_name.trim() : null,
                        last_name: this.newContact.last_name.trim(),
                        name_extension: this.newContact.name_extension || null,
                        sex: this.newContact.sex || null,
                        date_of_birth: this.newContact.date_of_birth || null,
                        email: this.newContact.email ? this.newContact.email.trim() : null,
                        mobile_number: this.newContact.mobile_number ? this.newContact.mobile_number.trim() : null,
                        address: this.newContact.address ? this.newContact.address.trim() : null,
                        company_id: this.contactModalType === 'Business' ? (this.newContact.company_id || this.companyId || null) : null,
                        position: this.newContact.position ? this.newContact.position.trim() : null,
                    };

                    const response = await fetch('{{ route('contacts.quick-store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        this.contactError = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Failed to save contact.');
                        this.contactSaving = false;
                        return;
                    }

                    const savedContact = data.contact;
                    this.contacts.push(savedContact);

                    if (this.contactModalType === 'Business' && savedContact.company_id && !this.companyId) {
                        this.companyId = savedContact.company_id;
                        this.loadCompany(false);
                    }

                    this.contactId = savedContact.id;
                    this.loadContact();

                    this.contactModalOpen = false;
                    this.contactSaving = false;
                } catch (err) {
                    this.contactError = err.message || 'An unexpected error occurred while saving the contact.';
                    this.contactSaving = false;
                }
            },

            findAccount() {
                return this.accounts.find(account =>
                    String(account.id) === String(this.accountId)
                );
            },

            findCompany() {
                return this.companies.find(company =>
                    String(company.id) === String(this.companyId)
                );
            },

            findContact() {
                return this.contacts.find(contact =>
                    String(contact.id) === String(this.contactId)
                );
            },

            loadAccount(clearPrevious = true) {
                const account = this.findAccount();

                if (!account) {
                    return;
                }

                const accountType = String(account.account_type || '').toLowerCase();
                const isIndividual = Boolean(account.individual_contact_id) ||
                    accountType.includes('individual');
                const isBusiness = !isIndividual && (
                    Boolean(account.company_id) ||
                    accountType.includes('business') ||
                    accountType.includes('company')
                );

                if (isIndividual) {
                    this.customerType = 'Individual';
                    this.companyId = '';

                    if (clearPrevious) {
                        this.clearCompanyFields();
                    }

                    this.contactId = account.individual_contact_id || '';
                    this.loadContact();
                    return;
                }

                if (isBusiness) {
                    this.customerType = 'Business';
                    this.companyId = account.company_id || '';
                    this.loadCompany(clearPrevious);

                    const matchingContacts = this.contacts.filter(contact =>
                        String(contact.company_id) === String(this.companyId)
                    );

                    if (matchingContacts.length === 1) {
                        this.contactId = matchingContacts[0].id;
                        this.loadContact();
                    } else {
                        this.contactId = '';
                        this.clearContactFields();
                    }
                }
            },

            loadCompany(clearFields = true) {
                const company = this.findCompany();

                if (!company) {
                    if (clearFields) {
                        this.clearCompanyFields();
                    }
                    return;
                }

                const fields = {
                    company: company.company_name,
                    company_name: company.company_name,
                    company_address: company.address,
                };

                Object.entries(fields).forEach(([name, value]) => {
                    const field = document.querySelector(`[name="${name}"]`);

                    if (field) {
                        field.value = value ?? '';
                        field.dispatchEvent(new Event('input', { bubbles: true }));
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            },

            loadContact() {
                const contact = this.findContact();

                if (!contact) {
                    return;
                }

                const fields = {
                    salutation: contact.salutation,
                    first_name: contact.first_name,
                    middle_initial: contact.middle_name,
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
                    const field = document.querySelector(`[name="${name}"]`);

                    if (field) {
                        field.value = value ?? '';
                        field.dispatchEvent(new Event('input', { bubbles: true }));
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            },

            clearContactFields() {
                const fields = [
                    'salutation',
                    'first_name',
                    'middle_initial',
                    'last_name',
                    'name_extension',
                    'date_of_birth',
                    'sex',
                    'email',
                    'mobile_number',
                    'address',
                    'position',
                ];

                fields.forEach(name => {
                    const field = document.querySelector(`[name="${name}"]`);
                    if (field) {
                        field.value = '';
                        field.dispatchEvent(new Event('input', { bubbles: true }));
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            },

            clearCompanyFields() {
                const fields = ['company', 'company_name', 'company_address', 'primary_contact_name'];

                fields.forEach(name => {
                    const field = document.querySelector(`[name="${name}"]`);
                    if (field) {
                        field.value = '';
                        field.dispatchEvent(new Event('input', { bubbles: true }));
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            },
        };
    }

    function ownerSelector(initialOwner, ownersList) {
        return {
            isOpen: false,
            search: '',
            selectedOwner: initialOwner || '',
            owners: ownersList || [],
            get filteredOwners() {
                const q = this.search.toLowerCase().trim();
                if (!q) return this.owners;
                return this.owners.filter(o => 
                    (o.name && o.name.toLowerCase().includes(q)) ||
                    (o.email && o.email.toLowerCase().includes(q))
                );
            },
            selectOwner(name) {
                this.selectedOwner = name;
                this.isOpen = false;
            },
            getInitials(name) {
                if (!name) return 'OW';
                const parts = name.trim().split(/\s+/);
                if (parts.length >= 2) {
                    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
                }
                return name.substring(0, 2).toUpperCase();
            }
        };
    }
</script>


<script>

    function addOtherFee() {
        const container = document.getElementById('other-fees');
        const wrapper = document.createElement('div');
        wrapper.className = 'other-fee-row';
        wrapper.style.display = 'grid';
        wrapper.style.gridTemplateColumns = 'repeat(2, minmax(0, 1fr))';
        wrapper.style.gap = '14px';
        wrapper.style.alignItems = 'flex-start';

        wrapper.innerHTML = `
            <div>
                <label class="label">
                    Fee Title
                </label>
                <input
                    type="text"
                    name="other_fee_description[]"
                    placeholder=""
                >
            </div>

            <div>
                <label class="label">
                    Fee Amount
                </label>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div class="fee-input" style="flex: 1; position: relative;">
                        <span>
                            P
                        </span>
                        <input
                            type="number"
                            step="0.01"
                            name="other_fee_amount[]"
                        >
                    </div>
                    <button
                        type="button"
                        onclick="this.closest('.other-fee-row').remove()"
                        style="width: 38px; height: 38px; border: 1px solid #d9dfe7; border-radius: 8px; background: #ffffff; color: #64748b; font-size: 18px; line-height: 1; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; padding: 0; transition: background 0.15s ease;"
                        onmouseover="this.style.background='#f1f5f9'"
                        onmouseout="this.style.background='#ffffff'"
                        title="Remove Fee"
                    >&times;</button>
                </div>
            </div>
        `;

        container.appendChild(wrapper);
    }

</script>

<x-ordo-tooltip />
</body>
</html>
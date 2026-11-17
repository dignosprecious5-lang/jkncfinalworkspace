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

    $normalizeArray = function ($value): array {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && trim($value) !== '') {
            $trimmed = trim($value);
            if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
            if (str_contains($trimmed, ',') || str_contains($trimmed, "\n")) {
                return array_map('trim', preg_split('/[\n,]+/', $trimmed, -1, PREG_SPLIT_NO_EMPTY));
            }
            return [$trimmed];
        }
        return [];
    };

    $activeServiceAreas = $normalizeArray(old('service_area', $deal->service_areas ?? ($deal->service_area ?? [])));
    $activeServices = $normalizeArray(old('services', $deal->services ?? ($deal->services_products ?? [])));
    $activeServiceIds = $normalizeArray(old('service_ids', old('catalog_selection_present') !== null ? [] : collect($deal->services_products ?? [])
        ->filter(fn ($item) => is_array($item) && empty($item['custom']) && strtoupper((string) ($item['type'] ?? 'SERVICE')) === 'SERVICE')
        ->pluck('service_id')->filter()->values()->all()));
    $activeProductIds = $normalizeArray(old('product_ids', old('catalog_selection_present') !== null ? [] : collect($deal->services_products ?? [])
        ->filter(fn ($item) => is_array($item) && empty($item['custom']) && strtoupper((string) ($item['type'] ?? '')) === 'PRODUCT')
        ->pluck('product_id')->filter()->values()->all()));
    $activeProducts = $normalizeArray(old('products', $deal->products ?? []));
    $availableServiceIds = collect($regularDealServiceMap ?? [])->flatten(1)->merge($unassignedDealServices ?? [])->pluck('id')->map('strval')->all();
    $availableProductIds = array_map('strval', array_column($dealProductCatalog ?? [], 'id'));
    $activeServiceIds = array_values(array_filter($activeServiceIds, fn ($id) => in_array((string) $id, $availableServiceIds, true)));
    $activeProductIds = array_values(array_filter($activeProductIds, fn ($id) => in_array((string) $id, $availableProductIds, true)));

    if (old('catalog_selection_present') === null) {
        foreach (($regularDealServiceMap ?? []) as $area => $entries) {
            if (collect($entries)->contains(fn ($entry) => in_array((string) $entry['id'], array_map('strval', $activeServiceIds), true))) {
                $activeServiceAreas[] = $area;
            }
        }
        $activeServiceAreas = array_values(array_unique($activeServiceAreas));
    }

    $activeRequiredActions = $normalizeArray(old('required_actions', $deal->required_actions ?? []));
    $activeProfessionalSupport = $normalizeArray(old('professional_support', $deal->professional_support ?? ($deal->professional_support_required ?? [])));

    $resolvedCustomerType = old(
        'customer_type',
        $deal->customer_type ?? (
            !empty($deal->company_id) || !empty($deal->company) || !empty($deal->company_name)
                ? 'Business'
                : (!empty($deal->account_id) && optional($deal->account)->account_type === 'Individual' ? 'Individual' : 'Business')
        )
    );

    $formatDateInput = function ($val): string {
        if (!$val) {
            return '';
        }
        if ($val instanceof \DateTimeInterface) {
            return $val->format('Y-m-d');
        }
        try {
            return \Carbon\Carbon::parse($val)->format('Y-m-d');
        } catch (\Throwable $e) {
            return (string) $val;
        }
    };
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
        <input type="hidden" name="catalog_selection_present" value="1">
        @foreach ($activeProductIds as $productId)
            <input type="hidden" name="product_ids[]" value="{{ $productId }}">
        @endforeach
        <input type="hidden" name="catalog_generated_scope" value="{{ old('catalog_generated_scope', '') }}" :value="catalogLastScope">
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
                                {{ ($resolvedCustomerType ?? '') == 'Business' ? 'checked' : '' }}
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
                                {{ ($resolvedCustomerType ?? '') == 'Individual' ? 'checked' : '' }}
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

                            <span class="text-xs text-slate-500">Select an existing business contact.</span>
                        </div>

                        <select
                            name="contact_id"
                            x-model="contactId"
                            @change="loadContact(true)"
                            x-bind:disabled="customerType !== 'Business'"
                            x-bind:required="customerType === 'Business'"
                            data-tooltip="Select the main contact responsible for this business account."
                        >
                            <option value="">Select Contact</option>

                            @if(isset($deal) && $deal->contact)
                                <option value="{{ $deal->contact->id }}" @selected(old('contact_id', $deal->contact_id) == $deal->contact->id)>
                                    {{ $deal->contact->contact_code }} — {{ $deal->contact->first_name }} {{ $deal->contact->last_name }}
                                </option>
                            @endif

                            <template x-for="contact in filteredContacts()" :key="contact.id">
                                <option
                                    :value="contact.id"
                                    :selected="String(contact.id) === String(contactId)"
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

                            <span class="text-xs text-slate-500">Select an existing individual contact.</span>
                        </div>

                        <select
                            name="contact_id"
                            x-model="contactId"
                            @change="loadContact(true)"
                            x-bind:disabled="customerType !== 'Individual'"
                            x-bind:required="customerType === 'Individual'"
                            data-tooltip="Select the individual contact who will be associated with this deal."
                        >
                            <option value="">Select Contact</option>

                            @if(isset($deal) && $deal->contact)
                                <option value="{{ $deal->contact->id }}" @selected(old('contact_id', $deal->contact_id) == $deal->contact->id)>
                                    {{ $deal->contact->contact_code }} — {{ $deal->contact->first_name }} {{ $deal->contact->last_name }}
                                </option>
                            @endif

                            <template x-for="contact in filteredContacts()" :key="contact.id">
                                <option
                                    :value="contact.id"
                                    :selected="String(contact.id) === String(contactId)"
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
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $formatDateInput($deal->date_of_birth ?? null)) }}">
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

                    @foreach($serviceAreaOptions ?? [] as $service)

                        <label class="check" data-tooltip="Select {{ $service }}">

                            <input
                                type="checkbox"
                                name="service_area[]"
                                value="{{ $service }}"
                                x-model="selectedServiceAreas"
                                {{ in_array($service, $activeServiceAreas) ? 'checked' : '' }}
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
                                    :key="subService.id"
                                >

                                    <label class="check" :data-tooltip="'Select ' + subService.name">

                                        <input
                                            type="checkbox"
                                            name="service_ids[]"
                                            :value="String(subService.id)"
                                            x-model="selectedServiceIds"
                                        >

                                        <span x-text="subService.name"></span>

                                    </label>

                                </template>

                            </div>

                        </div>

                    </template>

                </div>


                @if (!empty($unassignedDealServices))
                    <div class="sub-label">Services without a Service Area</div>
                    <div class="service-grid">
                        @foreach ($unassignedDealServices as $service)
                            <label class="check"><input type="checkbox" name="service_ids[]" value="{{ $service['id'] }}" x-model="selectedServiceIds">{{ $service['name'] }}</label>
                        @endforeach
                    </div>
                @endif
                    @foreach (($legacyDealServices ?? []) as $legacy)
                        <label class="check"><input type="checkbox" name="services[]" value="{{ $legacy['name'] }}" {{ in_array($legacy['name'], old('catalog_selection_present') !== null ? old('services', []) : array_column($legacyDealServices, 'name')) ? 'checked' : '' }}>{{ $legacy['name'] }} <span class="text-xs text-slate-500">(Existing unlinked item)</span></label>
                    @endforeach

                <div x-data="{ showOtherService: {{ in_array('Others', $activeServices) ? 'true' : 'false' }} }" style="margin-top: 8px;">

                    <label class="check">

                        <input
                            type="checkbox"
                            name="services[]"
                            value="Others"
                            x-model="showOtherService"
                            {{ in_array('Others', $activeServices) ? 'checked' : '' }}
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
                <div class="section-title">Products</div>
                    @foreach (($legacyDealProducts ?? []) as $legacy)
                        <label class="check"><input type="checkbox" name="products[]" value="{{ $legacy['name'] }}" {{ in_array($legacy['name'], old('catalog_selection_present') !== null ? old('products', []) : array_column($legacyDealProducts, 'name')) ? 'checked' : '' }}>{{ $legacy['name'] }} <span class="text-xs text-slate-500">(Existing unlinked item)</span></label>
                    @endforeach

                    <div x-data="{ showOtherProduct: {{ in_array('Others', $activeProducts) ? 'true' : 'false' }} }" style="margin-top: 8px;">

                        <label class="check">

                            <input
                                type="checkbox"
                                name="products[]"
                                value="Others"
                                x-model="showOtherProduct"
                                {{ in_array('Others', $activeProducts) ? 'checked' : '' }}
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
                    @input="catalogScopeEdited()"
                    style="min-height: 90px;"
                    data-tooltip="Enter the quantity or scope required for this service, when applicable."
                >{{ old('scope_of_work', $deal->scope_of_work ?? ($deal->inquiry_details ?? '')) }}</textarea>

                <div aria-live="polite" class="space-y-3 text-xs">
                    <p x-show="catalogLoading" x-cloak>Loading selected catalog configuration...</p>
                    <p x-show="catalogError" x-text="catalogError" x-cloak class="text-red-600" role="alert"></p>
                    <template x-for="item in catalogItems" :key="item.type + '-' + (item.product_id || item.service_id)">
                        <details class="border border-gray-200 rounded-lg p-3">
                            <summary class="font-semibold cursor-pointer" x-text="item.name"></summary>
                            <div class="space-y-2 mt-3">
                                <template x-for="field in [{key:'scope_of_work',label:'Scope'}, {key:'deliverables',label:'Deliverables / What You Will Receive'}, {key:'client_responsibilities',label:'Client Responsibilities'}, {key:'exclusions',label:'Exclusions / Out of Scope'}]" :key="field.key">
                                    <div><strong x-text="field.label"></strong><p style="white-space:pre-line;overflow-wrap:anywhere" x-text="item[field.key] || 'Not configured.'"></p></div>
                                </template>
                                <div><strong>Catalog unit price</strong> <span x-text="item.commercial && item.commercial.price !== null ? (item.commercial.currency || '') + ' ' + Number(item.commercial.price).toLocaleString('en-US', {minimumFractionDigits:2}) : 'Needs confirmation'"></span></div>
                                <div><strong>Catalog requirements</strong><p>Configuration only; client completion remains separate.</p>
                                    <template x-for="(requirement,index) in item.requirements || []" :key="index"><p x-text="requirement.name + (requirement.evidence_type ? ' - ' + requirement.evidence_type : '') + (requirement.validity_expiration ? ' - Validity: ' + requirement.validity_expiration : '')"></p></template>
                                    <p x-show="!(item.requirements || []).length">No applicable configured requirements.</p>
                                </div>
                                <div><strong>Applicable terms</strong>
                                    <template x-for="(term,index) in item.terms || []" :key="index"><div><strong x-text="term.title"></strong><p style="white-space:pre-line;overflow-wrap:anywhere" x-text="term.content"></p></div></template>
                                    <p x-show="!(item.terms || []).length">No applicable configured terms.</p>
                                </div>
                            </div>
                        </details>
                    </template>
                </div>

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
                            {{ in_array(old('engagement_type', $deal->engagement_type ?? ''), ['Project Engagement', 'Project', 'Per Project']) ? 'checked' : '' }}
                        >

                        Project Engagement

                    </label>


                    <label class="choice" data-tooltip="Use for an ongoing recurring service.">

                        <input
                            type="radio"
                            name="engagement_type"
                            value="Regular (Retainer) Engagement"
                            {{ in_array(old('engagement_type', $deal->engagement_type ?? ''), ['Regular (Retainer) Engagement', 'Regular', 'Retainer']) ? 'checked' : '' }}
                        >

                        Regular (Retainer) Engagement

                    </label>


                    <label class="choice" data-tooltip="Use when the engagement combines project work with an ongoing retainer.">

                        <input
                            type="radio"
                            name="engagement_type"
                            value="Hybrid Engagement"
                            {{ in_array(old('engagement_type', $deal->engagement_type ?? ''), ['Hybrid Engagement', 'Hybrid']) ? 'checked' : '' }}
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


                <div x-data="{ showOtherAction: {{ in_array('Others', $activeRequiredActions) ? 'true' : 'false' }} }">

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
                                    {{ in_array($action, $activeRequiredActions) ? 'checked' : '' }}
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
                                {{ in_array('Others', $activeRequiredActions) ? 'checked' : '' }}
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
                                                <span style="color: #0f172a; font-weight: 700; white-space: nowrap;" x-text="item.price === null ? 'Needs confirmation' : (item.currency ? item.currency + ' ' : '') + Number(item.price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
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

                            <template x-if="getFilteredProducts().length === 0">
                                <div style="color: #94a3b8; font-size: 12px; padding: 12px 0;">
                                    No matching active product prices are available.
                                </div>
                            </template>

                            <template x-if="getFilteredProducts().length > 0">
                                <div style="display: flex; flex-direction: column; gap: 0;">
                                    <template x-for="(product, pIdx) in getFilteredProducts()" :key="pIdx">
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 7px 0; border-bottom: 1px solid #f8fafc; font-size: 12px;">
                                            <span style="color: #334155; font-weight: 400; padding-right: 12px;" x-text="product.name"></span>
                                            <span style="color: #0f172a; font-weight: 700; white-space: nowrap;" x-text="product.price === null ? 'Needs confirmation' : (product.currency ? product.currency + ' ' : '') + Number(product.price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
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
                                $formatDateInput($deal->planned_start_date ?? null)
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
                                $deal->estimated_duration ?? ($deal->estimated_duration_days ?? '')
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
                                $formatDateInput($deal->estimated_completion_date ?? null)
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
                                $formatDateInput($deal->client_preferred_completion_date ?? null)
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
                                $formatDateInput($deal->confirmed_delivery_date ?? null)
                            ) }}"
                        >

                    </div>


                    <div>

                        <label class="label" data-tooltip="The target date when this deal is expected to close. Set by the user based on client timeline.">
                            Expected Close Date <span class="ordo-tooltip-icon" tabindex="0">i</span>
                        </label>

                        <input
                            type="date"
                            name="expected_close"
                            data-tooltip="The target date when this deal is expected to close. Set by the user based on client timeline."
                            value="{{ old(
                                'expected_close',
                                $formatDateInput($deal->expected_close ?? null)
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


                <div x-data="{ showOtherSupport: {{ in_array('Others', $activeProfessionalSupport) ? 'true' : 'false' }} }">

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
                                    {{ in_array($support, $activeProfessionalSupport) ? 'checked' : '' }}
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
                                {{ in_array('Others', $activeProfessionalSupport) ? 'checked' : '' }}
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
                                $formatDateInput($deal->approval_date ?? null) ?: date('Y-m-d')
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
    $dealServiceMap = $regularDealServiceMap ?? [];
    $servicePriceMap = $regularServicePriceMap ?? [];
    $productsList = $dealProductCatalog ?? [];
    $productPriceMap = [];
    foreach ($productsList as $catalogProduct) {
        foreach ($catalogProduct['service_area'] ?? [] as $area) { $productPriceMap[$area][] = $catalogProduct; }
    }
@endphp


<script src="{{ asset('js/deal-catalog-selection.js') }}"></script>
<script>
    const dealServiceMap = @json($dealServiceMap ?? []);
    const dealServicePriceMap = @json($servicePriceMap ?? []);
    const dealProductPriceMap = @json($productPriceMap);
    const dealProductsList = @json($productsList);


    function dealFormState() {
        return {
            ...window.ordoDealCatalogSelection({
                url: @js(route('deals.catalog-configuration')),
                dealId: @js($deal->id ?? null),
                productIds: @js($activeProductIds),
                initialScope: @js(old('scope_of_work', $deal->scope_of_work ?? '')),
                generatedScope: @js(old('catalog_generated_scope', '')),
            }),
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
                        'primary_contact_id' => $company->primary_contact_id ?? null,
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
                    $compIds = $contact->companies ? $contact->companies->pluck('id')->all() : [];
                    if (!empty($contact->company_name)) {
                        $matchedCompId = \App\Models\Company::where('company_name', $contact->company_name)->value('id');
                        if ($matchedCompId && !in_array($matchedCompId, $compIds)) {
                            $compIds[] = $matchedCompId;
                        }
                    }
                    return [
                        'id' => $contact->id,
                        'contact_code' => $contact->contact_code,
                        'contact_type' => $contact->customer_type ?? $contact->contact_type ?? 'Individual',
                        'salutation' => $contact->salutation,
                        'first_name' => $contact->first_name,
                        'middle_name' => $contact->middle_name ?? $contact->middle_initial,
                        'last_name' => $contact->last_name,
                        'name_extension' => $contact->name_extension,
                        'date_of_birth' => optional($contact->date_of_birth)->format('Y-m-d'),
                        'sex' => $contact->sex,
                        'email' => $contact->email,
                        'mobile_number' => $contact->mobile_number ?? $contact->phone,
                        'address' => $contact->contact_address ?? $contact->address,
                        'company_ids' => $compIds,
                        'company_id' => $compIds[0] ?? null,
                        'position' => $contact->position,
                        'status' => $contact->status,
                    ];
                })->values()
            ),

            customerType: @js($resolvedCustomerType),

            selectedServiceAreas: @js($activeServiceAreas),
            selectedServiceIds: (@js($activeServiceIds)).map(String),

            serviceMap: dealServiceMap,
            servicePriceMap: dealServicePriceMap,
            productPriceMap: dealProductPriceMap,
            productsList: @js($dealProductCatalog ?? []),

            getFilteredProducts() {
                if (!this.selectedServiceAreas || this.selectedServiceAreas.length === 0) { return []; }
                return this.productsList.filter(product => !product.service_area.length || product.service_area.some(area => this.selectedServiceAreas.includes(area)));
            },

            init() {
                this.initCatalogSelection();
                if (this.accountId) {
                    this.loadAccount(false);
                } else if (this.companyId) {
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

                return this.contacts.filter(contact => {
                    if (String(contact.id) === String(this.contactId)) return true;
                    if (contact.company_ids && Array.isArray(contact.company_ids)) {
                        return contact.company_ids.map(String).includes(String(this.companyId));
                    }
                    return String(contact.company_id) === String(this.companyId);
                });
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

                    // Preserve existing contactId if set, otherwise fallback to account.individual_contact_id
                    if (!this.contactId || clearPrevious) {
                        this.contactId = this.contactId || account.individual_contact_id || '';
                    }
                    if (this.contactId) {
                        this.loadContact();
                    }
                    return;
                }

                if (isBusiness) {
                    this.customerType = 'Business';
                    this.companyId = account.company_id || this.companyId || '';
                    this.loadCompany(clearPrevious);

                    // PRESERVE existing contactId if it is set!
                    if (this.contactId) {
                        this.loadContact();
                        return;
                    }

                    const matchingContacts = this.contacts.filter(contact => {
                        if (contact.company_ids && Array.isArray(contact.company_ids)) {
                            return contact.company_ids.map(String).includes(String(this.companyId));
                        }
                        return String(contact.company_id) === String(this.companyId);
                    });

                    const companyObj = this.findCompany();
                    const primaryContactId = companyObj?.primary_contact_id || account.primary_contact_id;

                    if (primaryContactId) {
                        this.contactId = primaryContactId;
                        this.loadContact();
                    } else if (matchingContacts.length >= 1) {
                        this.contactId = matchingContacts[0].id;
                        this.loadContact();
                    } else if (clearPrevious) {
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

            loadContact(force = false) {
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
                        if (force || !field.value || field.value.trim() === '') {
                            field.value = value ?? '';
                            field.dispatchEvent(new Event('input', { bubbles: true }));
                            field.dispatchEvent(new Event('change', { bubbles: true }));
                        }
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

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $record->record_number ?: $record->record_title ?: $moduleLabel }}</title>
    <style>
        @page {
            size: letter;
            margin: 0;
        }
        :root {
            --blue: #1d4ed8;
            --border: #dbe2ea;
            --muted: #6b7280;
            --bg: #f8fafc;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            background: #eef2ff;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            justify-content: flex-end;
            padding: 10px 12px;
            background: rgba(238,242,255,.95);
            border-bottom: 1px solid #d1d5db;
        }
        .toolbar button {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #1f2937;
            padding: 8px 14px;
            border-radius: 999px;
            cursor: pointer;
        }
        .page {
            width: 816px;
            min-height: 1056px;
            margin: 10px auto;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 18px;
            overflow: hidden;
        }
        .header {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(90deg, #fff 0%, #fff 75%, #eff6ff 100%);
        }
        .brand {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .logo {
            width: 52px;
            height: 52px;
            border: 1px solid #dbeafe;
            border-radius: 10px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }
        .logo img {
            width: 44px;
            height: 44px;
            object-fit: contain;
        }
        .eyebrow {
            margin: 0 0 6px;
            text-transform: uppercase;
            letter-spacing: .24em;
            font-size: 8px;
            color: var(--muted);
        }
        .brand h1 {
            margin: 0;
            font-size: 17px;
            line-height: 1.1;
        }
        .brand .sub {
            margin: 5px 0 0;
            font-size: 9px;
            font-weight: 700;
            color: var(--blue);
        }
        .brand .meta {
            margin: 4px 0 0;
            font-size: 8.5px;
            color: var(--muted);
        }
        .status {
            text-align: right;
        }
        .status .title {
            margin: 0 0 6px;
            text-transform: uppercase;
            letter-spacing: .24em;
            font-size: 8px;
            color: var(--muted);
        }
        .status p {
            margin: 0;
            font-size: 9px;
            font-weight: 700;
            line-height: 1.5;
        }
        .section-title {
            margin: 0;
            background: var(--blue);
            color: #fff;
            padding: 6px 9px;
            text-transform: uppercase;
            letter-spacing: .24em;
            font-size: 8.5px;
            font-weight: 700;
        }
        .summary,
        .details,
        .simple-table,
        .line-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary td,
        .details td,
        .two-col td,
        .line-table th,
        .line-table td {
            border: 1px solid var(--border);
            vertical-align: top;
            padding: 6px 7px;
        }
        .summary td { width: 25%; height: 42px; }
        .label {
            margin: 0;
            text-transform: uppercase;
            letter-spacing: .18em;
            color: var(--muted);
            font-size: 7.5px;
        }
        .value {
            margin: 3px 0 0;
            font-weight: 700;
            font-size: 10px;
            word-break: break-word;
        }
        .block {
            padding: 7px;
        }
        .block-title {
            margin: 0;
            padding: 6px 8px;
            background: var(--blue);
            color: #fff;
            text-transform: uppercase;
            letter-spacing: .24em;
            font-size: 8.5px;
            font-weight: 700;
        }
        .box {
            margin: 7px;
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }
        .note {
            color: var(--muted);
            font-size: 9px;
        }
        .attachments {
            padding: 14px;
        }
        .attachments a {
            display: block;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 12px;
            text-decoration: none;
            color: #111827;
            margin-bottom: 10px;
        }
        .attachments strong,
        .attachments span,
        .attachments small {
            display: block;
            word-break: break-word;
        }
        .attachments span {
            margin-top: 4px;
            color: var(--blue);
            font-size: 9px;
            font-weight: 700;
        }
        .attachments small {
            margin-top: 3px;
            color: var(--muted);
            font-size: 8px;
        }
        .attachments a:hover { background: #f9fafb; }
        .note-card {
            border: 1px solid #fde68a;
            border-radius: 8px;
            background: #fffbeb;
            padding: 7px;
        }
        .note-card-header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            font-size: 8.5px;
            color: #6b7280;
        }
        .note-card-author {
            font-weight: 700;
            color: #1f2937;
        }
        .note-card-label {
            margin-top: 4px;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: .16em;
            color: #b45309;
        }
        .note-card-body {
            margin-top: 4px;
            font-size: 9px;
            color: #111827;
            white-space: pre-line;
        }

        .pr-line-list {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .pr-line-card {
            border: 1px solid #dbe2ea;
            border-radius: 8px;
            background: #f8fafc;
            padding: 6px 7px;
        }

        .pr-line-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }

        .pr-line-index {
            width: 20px;
            height: 20px;
            border-radius: 999px;
            background: #1d4ed8;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            font-weight: 700;
            flex: 0 0 auto;
        }

        .pr-line-title {
            margin: 0;
            font-size: 9.5px;
            font-weight: 700;
            color: #111827;
        }

        .pr-line-meta {
            margin: 3px 0 0;
            font-size: 7.5px;
            color: #6b7280;
        }

        .pr-line-total {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            padding: 3px 6px;
            font-size: 8.5px;
            font-weight: 700;
            white-space: nowrap;
        }

        .pr-line-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 280px;
            gap: 6px;
            margin-top: 5px;
            align-items: start;
        }

        .pr-line-fields {
            display: grid;
            grid-template-columns: 1fr;
            gap: 4px;
        }

        .pr-field-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            padding: 5px 6px;
        }

        .pr-field-label {
            margin: 0;
            text-transform: uppercase;
            letter-spacing: .18em;
            font-size: 7px;
            color: #6b7280;
        }

        .pr-field-value {
            margin: 2px 0 0;
            font-size: 8.5px;
            font-weight: 700;
            color: #111827;
            word-break: break-word;
        }

        .pr-summary-panel {
            border: 1px solid #dbeafe;
            border-radius: 8px;
            background: #fff;
            padding: 6px;
        }

        .pr-summary-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }

        .pr-summary-title {
            margin: 0;
            text-transform: uppercase;
            letter-spacing: .24em;
            font-size: 7.5px;
            font-weight: 700;
            color: #2563eb;
        }

        .pr-summary-subtitle {
            margin: 4px 0 0;
            font-size: 7.5px;
            color: #6b7280;
        }

        .pr-summary-stack {
            display: grid;
            grid-template-columns: 1fr;
            gap: 4px;
            margin-top: 5px;
        }

        .pr-summary-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 4px;
        }

        .pr-summary-full {
            width: 100%;
        }

        .pr-summary-formula {
            margin-top: 5px;
            font-size: 8px;
            font-weight: 700;
            color: #111827;
        }

        .lr-report {
            border: 1px solid #dbe2ea;
            border-radius: 8px;
            background: #f8fafc;
            padding: 7px;
        }

        .lr-report-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }

        .lr-report-eyebrow {
            margin: 0;
            text-transform: uppercase;
            letter-spacing: .24em;
            font-size: 7.5px;
            font-weight: 700;
            color: #2563eb;
        }

        .lr-report-title {
            margin: 4px 0 0;
            font-size: 12px;
            font-weight: 700;
            color: #111827;
        }

        .lr-report-subtitle {
            margin: 4px 0 0;
            font-size: 8.5px;
            color: #6b7280;
        }

        .lr-report-badge {
            display: inline-flex;
            padding: 3px 6px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: 700;
        }

        .lr-report-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(0, .85fr);
            gap: 7px;
            margin-top: 7px;
        }

        .lr-report-panel {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            padding: 6px;
        }

        .lr-report-metrics {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 5px;
        }

        .lr-metric {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f8fafc;
            padding: 5px 6px;
        }

        .lr-metric-label {
            margin: 0;
            text-transform: uppercase;
            letter-spacing: .18em;
            font-size: 7px;
            color: #6b7280;
        }

        .lr-metric-value {
            margin: 5px 0 0;
            font-size: 9px;
            font-weight: 700;
            color: #111827;
            word-break: break-word;
        }

        .lr-calc-band {
            margin-top: 6px;
            border: 1px dashed #bfdbfe;
            border-radius: 8px;
            background: rgba(219,234,254,.55);
            padding: 6px;
        }

        .lr-calc-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 5px;
            margin-top: 5px;
        }

        .lr-note-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f8fafc;
            padding: 5px 6px;
        }

        .lr-note-stack {
            display: grid;
            gap: 5px;
        }

        .pr-notes-wrap {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 4px;
        }

        .po-supplier-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .po-supplier-card {
            border: 1px solid #dbe2ea;
            border-radius: 8px;
            background: #f8fafc;
            padding: 6px;
        }

        .po-supplier-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 5px;
        }

        .asset-tag-card {
            border: 1px solid #dbe2ea;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
        }

        .asset-tag-head {
            padding: 6px 7px;
            border-bottom: 1px solid #dbe2ea;
            background: #f8fafc;
            text-align: center;
        }

        .asset-tag-company {
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.32em;
            font-size: 8px;
            color: #6b7280;
            font-weight: 700;
        }

        .asset-tag-title {
            margin: 4px 0 0;
            font-size: 14px;
            line-height: 1.1;
            font-weight: 900;
            letter-spacing: 0.22em;
            color: #111827;
        }

        .asset-tag-grid {
            display: grid;
            grid-template-columns: 92px 1fr;
        }

        .asset-tag-label,
        .asset-tag-value {
            border-bottom: 1px solid #dbe2ea;
            padding: 5px 6px;
        }

        .asset-tag-label {
            background: #f8fafc;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: #6b7280;
            font-weight: 700;
        }

        .asset-tag-value {
            font-size: 9px;
            font-weight: 700;
            color: #111827;
            word-break: break-word;
        }

        .asset-tag-barcode {
            grid-column: 1 / -1;
            padding: 6px;
        }

        .asset-tag-barcode-box {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 5px;
            background: #fff;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page { margin: 0; border: 0; border-radius: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <div class="page">
        @php
            $noteAuthor = data_get($record, 'approval_actor_names.0')
                ?: data_get($record, 'approved_by_name')
                ?: data_get($record, 'submitted_by_name')
                ?: data_get($record, 'user')
                ?: 'Finance Team';
            $noteDate = data_get($record, 'approved_at')
                ?: data_get($record, 'submitted_at')
                ?: data_get($record, 'updated_at');
        @endphp
        <div class="header">
            <div class="brand">
                @if($companyLogo)
                    <div class="logo"><img src="{{ $companyLogo }}" alt="{{ $companyName }}"></div>
                @endif
                <div>
                    <p class="eyebrow">Official Finance Form</p>
                    <h1>{{ $companyName }}</h1>
                    <p class="sub">{{ $companyLegalName }} | {{ $moduleLabel }}</p>
                    <p class="meta">{{ $record->record_number ?: 'N/A' }} - {{ $recordTitleLabel ?: 'Name' }}: {{ $record->record_title ?: 'N/A' }}</p>
                </div>
            </div>

            <div class="status">
                <p class="title">Document Status</p>
                <p>Workflow: {{ $record->workflow_status ?: 'N/A' }}</p>
                <p>Approval: {{ $record->approval_status ?: 'N/A' }}</p>
                <p>Status: {{ $record->status ?: 'N/A' }}</p>
            </div>
        </div>

        <table class="summary">
            @foreach(array_chunk($summaryCards, 4) as $row)
                <tr>
                    @foreach($row as $card)
                        <td>
                            <p class="label">{{ $card['label'] }}</p>
                            <p class="value">{{ $card['value'] }}</p>
                        </td>
                    @endforeach
                    @for($i = count($row); $i < 4; $i++)
                        <td></td>
                    @endfor
                </tr>
            @endforeach
        </table>

        @foreach($previewSections as $section)
            <div class="box">
                <div class="block">
                    @if(data_get($section, 'type') === 'supplier_send' && blank($record->supplier_completed_at))
                        <table class="details">
                            <tr>
                                <td colspan="2">
                                    <p class="label">Supplier Completion Dispatch</p>
                                    <p class="value">A completion form will be sent to the supplier email address.</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p class="label">Email Address</p>
                                    <p class="value">{{ data_get($record->data, 'email_address') ?: 'N/A' }}</p>
                                </td>
                                <td>
                                    <p class="label">Completion Mode</p>
                                    <p class="value">Send to Supplier</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p class="label">Representative Full Name</p>
                                    <p class="value">{{ data_get($record->data, 'representative_full_name') ?: 'N/A' }}</p>
                                </td>
                                <td>
                                    <p class="label">Phone Number</p>
                                    <p class="value">{{ data_get($record->data, 'phone_number') ?: 'N/A' }}</p>
                                </td>
                            </tr>
                        </table>
                    @elseif(data_get($section, 'type') === 'dv_line_items')
                        @php
                            $dvLineItems = array_values(array_filter((array) data_get($record->data, 'line_items', []), fn ($item) => is_array($item) && collect($item)->contains(fn ($value) => !blank($value))));
                        @endphp
                        @if(count($dvLineItems))
                            <table class="details">
                                <tr>
                                    <td><p class="label">Description</p></td>
                                    <td><p class="label">Account Code</p></td>
                                    <td><p class="label">Debit</p></td>
                                    <td><p class="label">Credit</p></td>
                                </tr>
                                @foreach($dvLineItems as $item)
                                    <tr>
                                        <td><p class="value">{{ data_get($item, 'description') ?: 'N/A' }}</p></td>
                                        <td><p class="value">{{ data_get($item, 'account_code') ?: 'N/A' }}</p></td>
                                        <td><p class="value">{{ number_format((float) data_get($item, 'debit', 0), 2) }}</p></td>
                                        <td><p class="value">{{ number_format((float) data_get($item, 'credit', 0), 2) }}</p></td>
                                    </tr>
                                @endforeach
                            </table>
                        @else
                            <p class="muted">No line items added.</p>
                        @endif
                    @elseif(data_get($section, 'type') === 'line_items')
                        @if(count($lineItems))
                            @php
                                $supplierGroups = $record->module_key === 'po' && !empty($poSupplierGroups) ? $poSupplierGroups : [['supplier_label' => null, 'group_total' => null, 'items' => $lineItems]];
                            @endphp
                            <div class="{{ $record->module_key === 'po' ? 'po-supplier-list' : 'pr-line-list' }}">
                                @foreach($supplierGroups as $group)
                                    @if($record->module_key === 'po')
                                        <div class="po-supplier-card">
                                            <div class="po-supplier-head">
                                                <div>
                                                    <p class="pr-summary-title">Supplier</p>
                                                    <div class="pr-line-title">{{ $group['supplier_label'] ?: 'Unspecified Supplier' }}</div>
                                                    <div class="pr-line-meta">{{ $group['items_count'] ?? count($group['items'] ?? []) }} item(s) in this purchase order section</div>
                                                </div>
                                                <div class="pr-line-total">{{ $group['group_total'] ?? '0.00' }}</div>
                                            </div>
                                            <div class="pr-line-list">
                                    @endif
                                    @foreach(($group['items'] ?? []) as $index => $item)
                                        <div class="pr-line-card">
                                            <div class="pr-line-head">
                                                <div style="display:flex; gap:10px; align-items:flex-start;">
                                                    <div class="pr-line-index">{{ $index + 1 }}</div>
                                                    <div>
                                                        <div class="pr-line-title">{{ $item['item'] }}</div>
                                                        <div class="pr-line-meta">{{ $item['category'] }} | Qty: {{ $item['quantity'] }}</div>
                                                    </div>
                                                </div>
                                                <div class="pr-line-total">{{ $item['total'] }}</div>
                                            </div>
                                            <div class="pr-line-grid">
                                                <div class="pr-line-fields">
                                                    <div class="pr-field-card">
                                                        <p class="pr-field-label">Description</p>
                                                        <p class="pr-field-value">{{ $item['description'] }}</p>
                                                    </div>
                                                    <div class="pr-field-card">
                                                        <p class="pr-field-label">Unit Cost</p>
                                                        <p class="pr-field-value">{{ $item['amount'] }}</p>
                                                    </div>
                                                    <div class="pr-field-card">
                                                        <p class="pr-field-label">Line Total</p>
                                                        <p class="pr-field-value">{{ $item['total'] }}</p>
                                                    </div>
                                                    @if($record->module_key === 'pr')
                                                        <div class="pr-field-card">
                                                            <p class="pr-field-label">Supplier</p>
                                                            <p class="pr-field-value">{{ $item['supplier_label'] ?? '' }}</p>
                                                        </div>
                                                        <div class="pr-field-card">
                                                            <p class="pr-field-label">Client</p>
                                                            <p class="pr-field-value">{{ $item['client_label'] ?? '' }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="pr-summary-panel">
                                                    <div class="pr-summary-head">
                                                        <div>
                                                            <p class="pr-summary-title">Cost Summary</p>
                                                            <p class="pr-summary-subtitle">Each item has its own adjustment values.</p>
                                                        </div>
                                                    </div>
                                                    <div class="pr-summary-stack">
                                                        <div class="pr-summary-full pr-field-card">
                                                            <p class="pr-field-label">Subtotal</p>
                                                            <p class="pr-field-value">{{ $item['subtotal'] ?? $item['total'] }}</p>
                                                        </div>
                                                        <div class="pr-summary-row">
                                                            <div class="pr-field-card">
                                                                <p class="pr-field-label">Discount</p>
                                                                <p class="pr-field-value">{{ $item['discount'] ?? '0%' }}</p>
                                                            </div>
                                                            <div class="pr-field-card">
                                                                <p class="pr-field-label">Discount Amount</p>
                                                                <p class="pr-field-value">{{ $item['discount_amount'] ?? '0.00' }}</p>
                                                            </div>
                                                        </div>
                                                        <div class="pr-summary-row">
                                                            <div class="pr-field-card">
                                                                <p class="pr-field-label">Shipping</p>
                                                                <p class="pr-field-value">{{ $item['shipping_amount'] ?? '0.00' }}</p>
                                                            </div>
                                                            <div class="pr-field-card">
                                                                <p class="pr-field-label">Tax (VAT/Non-VAT/N/A)</p>
                                                                <p class="pr-field-value">{{ $item['tax_type'] ?? 'N/A' }}</p>
                                                            </div>
                                                        </div>
                                                        <div class="pr-summary-row">
                                                            <div class="pr-field-card">
                                                                <p class="pr-field-label">Tax Amount</p>
                                                                <p class="pr-field-value">{{ $item['tax_amount'] ?? '0.00' }}</p>
                                                            </div>
                                                            <div class="pr-field-card">
                                                                <p class="pr-field-label">WHT</p>
                                                                <p class="pr-field-value">{{ $item['wht_amount'] ?? '0.00' }}</p>
                                                            </div>
                                                        </div>
                                                        <div class="pr-summary-full pr-field-card">
                                                            <p class="pr-field-label">Grand Total</p>
                                                            <p class="pr-field-value">{{ $item['total'] }}</p>
                                                        </div>
                                                        <div class="pr-summary-formula">{{ $item['quantity'] }} x {{ $item['amount'] }} = {{ $item['total'] }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    @if($record->module_key === 'po')
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <p class="note">No line items added yet.</p>
                        @endif
                    @elseif(data_get($section, 'type') === 'liquidation_report')
                        @if(!empty($liquidationReport))
                            @php
                                $statusClass = str_contains(strtolower((string) ($liquidationReport['status_label'] ?? 'Balanced')), 'shortage')
                                    ? 'background:#fef2f2;color:#b91c1c;'
                                    : (str_contains(strtolower((string) ($liquidationReport['status_label'] ?? 'Balanced')), 'overage')
                                        ? 'background:#ecfdf5;color:#047857;'
                                        : 'background:#eff6ff;color:#1d4ed8;');
                            @endphp
                            <div class="lr-report">
                                <div class="lr-report-head">
                                    <div>
                                        <p class="lr-report-eyebrow">{{ data_get($section, 'title') ?: 'Liquidation Report' }}</p>
                                        <div class="lr-report-title">{{ $liquidationReport['status_label'] ?? 'Balanced' }}</div>
                                        <p class="lr-report-subtitle">Built from the liquidation fields in the slider form.</p>
                                    </div>
                                    <span class="lr-report-badge" style="{{ $statusClass }}">{{ $liquidationReport['variance_indicator'] ?? 'Balanced' }}</span>
                                </div>

                                <div class="lr-report-grid">
                                    <div class="lr-report-panel">
                                        <div class="lr-report-metrics">
                                            <div class="lr-metric">
                                                <p class="lr-metric-label">CA Reference No.</p>
                                                <p class="lr-metric-value">{{ $liquidationReport['ca_reference_no'] ?? 'N/A' }}</p>
                                            </div>
                                            <div class="lr-metric">
                                                <p class="lr-metric-label">CA Amount</p>
                                                <p class="lr-metric-value">{{ $liquidationReport['ca_amount'] ?? '0.00' }}</p>
                                            </div>
                                            <div class="lr-metric">
                                                <p class="lr-metric-label">Line Items Total</p>
                                                <p class="lr-metric-value">{{ $liquidationReport['line_items_total'] ?? '0.00' }}</p>
                                            </div>
                                            <div class="lr-metric">
                                                <p class="lr-metric-label">For Client?</p>
                                                <p class="lr-metric-value">{{ $liquidationReport['for_client'] ?? 'N/A' }}</p>
                                            </div>
                                            <div class="lr-metric">
                                                <p class="lr-metric-label">Client Name(s)</p>
                                                <p class="lr-metric-value">{{ $liquidationReport['client_names'] ?? 'N/A' }}</p>
                                            </div>
                                            <div class="lr-metric">
                                                <p class="lr-metric-label">Actual Expenses</p>
                                                <p class="lr-metric-value">{{ $liquidationReport['actual_expenses'] ?? '0.00' }}</p>
                                            </div>
                                        </div>

                                        <div class="lr-calc-band">
                                            <p class="lr-metric-label" style="color:#1d4ed8;">Calculation Band</p>
                                            <div class="lr-calc-grid">
                                                <div>
                                                    <p class="lr-metric-label">CA Amount</p>
                                                    <p class="lr-metric-value">{{ $liquidationReport['ca_amount'] ?? '0.00' }}</p>
                                                </div>
                                                <div>
                                                    <p class="lr-metric-label">Less Actual Expenses</p>
                                                    <p class="lr-metric-value">- {{ $liquidationReport['actual_expenses'] ?? '0.00' }}</p>
                                                </div>
                                                <div>
                                                    <p class="lr-metric-label">Variance</p>
                                                    <p class="lr-metric-value">{{ $liquidationReport['variance'] ?? '0.00' }}</p>
                                                </div>
                                            </div>
                                            <p class="lr-metric-value" style="margin-top:10px;">Line Items Total = Sum of all item totals</p>
                                        </div>
                                    </div>

                                    <div class="lr-report-panel">
                                        <div class="lr-note-stack">
                                            <div class="lr-note-card">
                                                <p class="lr-metric-label">Variance Indicator</p>
                                                <p class="lr-metric-value">{{ $liquidationReport['variance_indicator'] ?? 'Balanced' }}</p>
                                            </div>
                                            <div class="lr-note-card">
                                                <p class="lr-metric-label">Purpose / Business Need</p>
                                                <p class="lr-metric-value">{{ $liquidationReport['purpose'] ?? 'N/A' }}</p>
                                            </div>
                                            <div class="lr-note-card">
                                                <p class="lr-metric-label">Remarks</p>
                                                <p class="lr-metric-value">{{ $liquidationReport['remarks'] ?? 'N/A' }}</p>
                                            </div>
                                            <div class="lr-note-card">
                                                <p class="lr-metric-label">Requested By</p>
                                                <p class="lr-metric-value">{{ $liquidationReport['employee_name'] ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <p class="note">No liquidation report details available.</p>
                        @endif
                    @elseif(data_get($section, 'type') === 'ca_payment_tracking')
                        @if(!empty($cashAdvancePaymentTracking))
                            <table class="details">
                                @foreach(array_chunk($cashAdvancePaymentTracking['summary'] ?? [], 2) as $pair)
                                    <tr>
                                        @foreach($pair as $detail)
                                            <td>
                                                <p class="label">{{ $detail['label'] }}</p>
                                                <p class="value">{{ $detail['value'] }}</p>
                                            </td>
                                        @endforeach
                                        @for($i = count($pair); $i < 2; $i++)
                                            <td></td>
                                        @endfor
                                    </tr>
                                @endforeach
                            </table>
                            <table class="line-table" style="margin-top: 8px;">
                                <tr>
                                    <th>Release</th>
                                    <th>Scheduled Date</th>
                                    <th>Amount</th>
                                    <th>Paid</th>
                                    <th>Payment Date</th>
                                    <th>Status</th>
                                </tr>
                                @foreach(($cashAdvancePaymentTracking['rows'] ?? []) as $row)
                                    <tr>
                                        <td>{{ $row['no'] }}</td>
                                        <td>{{ $row['scheduled_date'] }}</td>
                                        <td>{{ $row['scheduled_amount'] }}</td>
                                        <td>{{ $row['paid_amount'] }}</td>
                                        <td>{{ $row['payment_date'] }}</td>
                                        <td>{{ $row['status'] }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @else
                            <p class="note">No cash advance payment tracking available.</p>
                        @endif
                    @elseif(data_get($section, 'type') === 'asset_tag')
                        <div class="asset-tag-card">
                            <div class="asset-tag-head">
                                <p class="asset-tag-company">JK&amp;C INC.</p>
                                <div class="asset-tag-title">ASSET TAG</div>
                            </div>
                            <div class="asset-tag-grid">
                                <div class="asset-tag-label">Asset Code</div>
                                <div class="asset-tag-value">{{ data_get($section, 'asset_code') ?: 'N/A' }}</div>
                                <div class="asset-tag-label">Location</div>
                                <div class="asset-tag-value">{{ data_get($section, 'location') ?: 'N/A' }}</div>
                                <div class="asset-tag-label">Serial Number</div>
                                <div class="asset-tag-value">{{ data_get($section, 'serial_number') ?: 'N/A' }}</div>
                                <div class="asset-tag-label">Barcode</div>
                                <div class="asset-tag-value">
                                    <div class="asset-tag-barcode-box">
                                        {!! data_get($section, 'barcode_svg') !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif(data_get($section, 'type') === 'notes')
                        @if(trim((string) $record->review_note) !== '')
                            <div class="note-card">
                                <div class="note-card-header">
                                    <div>
                                        <span class="note-card-author">{{ $noteAuthor }}</span>
                                    </div>
                                    <div>{{ $noteDate ? $noteDate->timezone('Asia/Manila')->format('M d, Y h:i A') : '' }}</div>
                                </div>
                                <div class="note-card-label">Review Note</div>
                                <div class="note-card-body">{{ $record->review_note }}</div>
                            </div>
                        @else
                            <p class="note">No review notes yet.</p>
                        @endif
                    @else
                        @php
                            $rows = data_get($section, 'rows', []);
                        @endphp
                        @if(count($rows))
                            <table class="details">
                                @foreach(array_chunk($rows, 2) as $pair)
                                    <tr>
                                        @foreach($pair as $detail)
                                            <td>
                                                <p class="label">{{ $detail['label'] }}</p>
                                                <p class="value">{{ $detail['value'] }}</p>
                                            </td>
                                        @endforeach
                                        @for($i = count($pair); $i < 2; $i++)
                                            <td></td>
                                        @endfor
                                    </tr>
                                @endforeach
                            </table>
                        @else
                            <p class="note">No additional details provided.</p>
                        @endif
                    @endif
                </div>
            </div>
        @endforeach

        @if(count($attachments))
            <div class="box">
                <div class="block-title">Attachments</div>
                <div class="attachments">
                    @foreach($attachments as $attachment)
                        <a href="{{ data_get($attachment, 'url') ?: data_get($attachment, 'path') }}" target="_blank">
                            <strong>{{ data_get($attachment, 'name') ?: data_get($attachment, 'path') ?: 'Attachment' }}</strong>
                            <span>{{ data_get($attachment, 'category') ?: 'Supporting Document' }}</span>
                            @if(data_get($attachment, 'uploaded_by') || data_get($attachment, 'uploaded_at'))
                                <small>{{ collect([data_get($attachment, 'uploaded_by'), data_get($attachment, 'uploaded_at')])->filter()->implode(' • ') }}</small>
                            @endif
                            <small>{{ data_get($attachment, 'path') }}</small>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</body>
</html>

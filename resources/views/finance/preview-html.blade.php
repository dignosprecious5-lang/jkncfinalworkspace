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
        .audit-banner {
            margin: 10px 12px 0;
            border: 1px solid #c7d2fe;
            border-radius: 14px;
            background: linear-gradient(135deg, #eff6ff 0%, #ffffff 55%, #eef2ff 100%);
            padding: 12px 14px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        .audit-banner-head {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
        }
        .audit-banner-title {
            margin: 0;
            text-transform: uppercase;
            letter-spacing: .24em;
            font-size: 8px;
            color: var(--muted);
            font-weight: 700;
        }
        .audit-banner-copy {
            margin: 4px 0 0;
            font-size: 9px;
            color: #4b5563;
            max-width: 62ch;
        }
        .audit-badges {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 6px;
        }
        .audit-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 8px;
            font-weight: 700;
            background: #e0e7ff;
            color: #3730a3;
        }
        .audit-badge.locked {
            background: #111827;
            color: #fff;
        }
        .audit-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
            margin-top: 10px;
        }
        .audit-grid td {
            width: 33.333%;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff;
            padding: 8px 9px;
        }
        .audit-grid .label {
            font-size: 7.5px;
        }
        .audit-grid .value {
            font-size: 10px;
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
            break-inside: avoid;
            page-break-inside: avoid;
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
            break-inside: avoid;
            page-break-inside: avoid;
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

        .asset-tag-subtitle {
            margin: 8px 0 0;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.24em;
            color: #6b7280;
            font-weight: 700;
        }

        .asset-tag-layout {
            display: table;
            width: 100%;
            table-layout: fixed;
            border-top: 1px solid #dbe2ea;
        }

        .asset-tag-left-pane,
        .asset-tag-right-pane {
            display: table-cell;
            vertical-align: top;
            padding: 12px;
        }

        .asset-tag-left-pane {
            width: 58%;
            border-right: 1px solid #dbe2ea;
        }

        .asset-tag-box {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            padding: 10px 12px;
        }

        .asset-tag-box + .asset-tag-box {
            margin-top: 10px;
        }

        .asset-tag-code-box {
            background: #f8fafc;
            text-align: center;
        }

        .asset-tag-box-label {
            margin: 0;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.22em;
            color: #6b7280;
            font-weight: 700;
        }

        .asset-tag-box-value {
            margin: 8px 0 0;
            font-size: 11px;
            font-weight: 700;
            color: #111827;
            word-break: break-word;
        }

        .asset-tag-code-value {
            margin: 10px 0 0;
            font-size: 22px;
            font-weight: 900;
            letter-spacing: 0.18em;
            color: #111827;
            word-break: break-word;
        }

        .asset-tag-note-box {
            margin-top: 10px;
            border: 1px dashed #dbe2ea;
            border-radius: 12px;
            background: #f8fafc;
            padding: 10px 12px;
            text-align: center;
        }

        .asset-tag-note-title {
            margin: 0;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.22em;
            color: #6b7280;
            font-weight: 700;
        }

        .asset-tag-note-copy {
            margin: 6px 0 0;
            font-size: 8px;
            color: #4b5563;
        }

        .asset-tag-barcode-box {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px;
            background: #fff;
        }

        .asset-tag-barcode-box svg {
            display: block;
            width: 100%;
            height: auto;
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
            $data = $record->data ?? [];
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
                    <p class="meta">
                        {{ $record->record_number ?: 'N/A' }}
                        @if(!in_array($record->module_key, ['pr', 'err', 'crf', 'ca'], true))
                            - {{ $recordTitleLabel ?: 'Name' }}: {{ $record->record_title ?: 'N/A' }}
                        @endif
                    </p>
                </div>
            </div>

            @if(!$isTemplatePreview)
                <div class="status">
                    <p class="title">Document Status</p>
                    <p>Workflow: {{ $record->workflow_status ?: 'N/A' }}</p>
                    <p>Approval: {{ $record->approval_status ?: 'N/A' }}</p>
                    <p>Status: {{ $record->status ?: 'N/A' }}</p>
                </div>
            @endif
        </div>

        @php
            $data = $record->data ?? [];
            $previewHistory = (array) data_get($data, 'history', []);
            $previewRelationshipStatus = data_get($data, 'relationship_status') ?: 'In Progress';
            $previewIsLocked = in_array($record->status ?? '', ['Disbursed', 'Completed', 'Closed'], true) || !($record->can_edit ?? true);
        @endphp
        @if(!$isTemplatePreview)
            <div class="audit-banner">
                <div class="audit-banner-head">
                    <div>
                        <p class="audit-banner-title">Audit / Control Status</p>
                        <p class="audit-banner-copy">System-managed status, relationship updates, validations, and audit entries. Users manage the transaction while the system manages the controls.</p>
                    </div>
                    <div class="audit-badges">
                        <span class="audit-badge {{ $previewIsLocked ? 'locked' : '' }}">{{ $previewIsLocked ? 'Read-only' : 'Editable' }}</span>
                        <span class="audit-badge">{{ $record->status ?: 'N/A' }}</span>
                        <span class="audit-badge">{{ $previewRelationshipStatus }}</span>
                    </div>
                </div>
                <table class="audit-grid">
                    <tr>
                        <td>
                            <p class="label">Current Status</p>
                            <p class="value">{{ $record->status ?: 'N/A' }}</p>
                        </td>
                        <td>
                            <p class="label">Relationship</p>
                            <p class="value">{{ $previewRelationshipStatus }}</p>
                        </td>
                        <td>
                            <p class="label">History Entries</p>
                            <p class="value">{{ count($previewHistory) }}</p>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        <table class="summary">
            @foreach(array_chunk($summaryCards, 2) as $row)
                <tr>
                    @foreach($row as $card)
                        <td>
                            <p class="label">{{ $card['label'] }}</p>
                            <p class="value">{{ $card['value'] }}</p>
                        </td>
                    @endforeach
                    @for($i = count($row); $i < 2; $i++)
                        <td></td>
                    @endfor
                </tr>
            @endforeach
        </table>

        @if(!$isTemplatePreview && !empty($approvalTrailRows))
            <div class="box">
                <div class="block-title">Approval Trail</div>
                <div class="block">
                    <table class="details">
                        <tr>
                            <th>Step</th>
                            <th>Role</th>
                            <th>Approver</th>
                            <th>Approved At</th>
                            <th>Status</th>
                        </tr>
                        @foreach($approvalTrailRows as $row)
                            <tr>
                                <td>{{ $row['step'] ?: '-' }}</td>
                                <td>{{ $row['role'] ?: '-' }}</td>
                                <td>{{ $row['approver'] ?: '-' }}</td>
                                <td>{{ $row['approved_at'] ?: '-' }}</td>
                                <td>{{ $row['status'] ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        @endif

        @if(!$isTemplatePreview && !empty($completeDataRows))
            <div class="box">
                <div class="block-title">Complete Record Data</div>
                <div class="block">
                    <table class="details">
                        @foreach(array_chunk($completeDataRows, 2) as $pair)
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
                </div>
            </div>
        @endif

        @if(in_array($record->module_key, ['lr', 'err', 'dv', 'crf'], true))
            <div class="box">
                <div class="block-title">Itemization</div>
                <div class="block">
                    @if(count($itemizationLineItems ?? []))
                        <table class="line-table">
                            <tr>
                                <th>Item</th>
                                <th>Item Description</th>
                                <th>Category</th>
                                <th>Qty</th>
                                <th>Unit Cost / Amount</th>
                                <th>Supplier</th>
                            </tr>
                            @foreach(($itemizationLineItems ?? []) as $item)
                                <tr>
                                    <td>{{ $item['item'] ?? 'N/A' }}</td>
                                    <td>{{ $item['description'] ?? 'N/A' }}</td>
                                    <td>{{ $item['category'] ?? 'N/A' }}</td>
                                    <td>{{ $item['quantity'] ?? '0' }}</td>
                                    <td>{{ $item['amount'] ?? '0.00' }}</td>
                                    <td>{{ $item['supplier_label'] ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </table>
                    @else
                        <p class="note">No itemized rows were found for this record yet.</p>
                    @endif
                </div>
            </div>
        @endif

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
                    @elseif(data_get($section, 'type') === 'next_action_callout')
                        <div style="border:1px solid #c7d2fe; border-radius:12px; overflow:hidden; background:linear-gradient(135deg,#eef2ff 0%,#ffffff 100%);">
                            <div style="padding:10px 12px; border-bottom:1px solid #e0e7ff; background:rgba(255,255,255,.8);">
                                <p class="label" style="color:#4f46e5;">Next Action</p>
                                <p class="value" style="font-size:14px;">{{ data_get($section, 'next_action') ?: 'Create Disbursement Voucher' }}</p>
                            </div>
                            <div style="padding:12px;">
                                <table class="details">
                                    <tr>
                                        <td>
                                            <p class="label">Relationship Status</p>
                                            <p class="value">{{ data_get($section, 'relationship_status') ?: 'In Progress' }}</p>
                                        </td>
                                        <td>
                                            <p class="label">Status Note</p>
                                            <p class="value">{{ data_get($section, 'description') ?: 'This record is ready for the next step in the workflow.' }}</p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    @elseif(data_get($section, 'type') === 'history')
                        @php
                            $historyEntries = array_values(array_filter((array) data_get($record->data, 'history', []), fn ($entry) => is_array($entry)));
                        @endphp
                        @if(count($historyEntries))
                            @if($record->module_key === 'crf')
                                <div class="lr-report">
                                    <table class="details" style="margin-bottom: 8px;">
                                        <tr>
                                            <td colspan="2"><p class="label">Record History / Audit Trail</p></td>
                                        </tr>
                                    </table>
                                    @foreach(array_reverse($historyEntries) as $entry)
                                        <div class="lr-report-panel" style="margin-bottom: 10px; border-left: 3px solid #f59e0b; padding-left: 10px;">
                                            <div class="lr-report-title">{{ data_get($entry, 'action') ?: 'Action' }}</div>
                                            <div class="lr-report-subtitle">{{ data_get($entry, 'changed_by') ?: 'System' }} | {{ data_get($entry, 'changed_at') ?: 'N/A' }} | {{ data_get($entry, 'module') ?: $record->module_key }}</div>
                                            @if(data_get($entry, 'reason'))
                                                <div style="margin-top:4px; color:#92400e; font-size:12px;">{{ data_get($entry, 'reason') }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="lr-report">
                                    <table class="details" style="margin-bottom: 8px;">
                                        <tr>
                                            <td colspan="2"><p class="label">Record History / Audit Trail</p></td>
                                        </tr>
                                    </table>
                                    @foreach(array_reverse($historyEntries) as $entry)
                                        <div class="lr-report-panel" style="margin-bottom: 10px;">
                                            <div class="lr-report-title">{{ data_get($entry, 'action') ?: 'Action' }}</div>
                                            <div class="lr-report-subtitle">{{ data_get($entry, 'changed_by') ?: 'System' }} | {{ data_get($entry, 'changed_at') ?: 'N/A' }} | {{ data_get($entry, 'module') ?: $record->module_key }}</div>
                                            @if(data_get($entry, 'reason'))
                                                <div style="margin-top:6px; color:#92400e; font-size:12px;">Reason: {{ data_get($entry, 'reason') }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    @elseif(data_get($section, 'type') === 'attachments')
                        @php
                            $attachmentEntries = array_values(array_filter((array) $attachments, fn ($attachment) => is_array($attachment)));
                        @endphp
                        @if(count($attachmentEntries))
                            <div class="attachments">
                                @php
                                    $isImageAttachment = fn ($attachment) => str_starts_with(strtolower((string) data_get($attachment, 'mime', '')), 'image/')
                                        || in_array(strtolower(pathinfo((string) data_get($attachment, 'name', data_get($attachment, 'path', '')), PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true)
                                        || strtolower((string) data_get($attachment, 'category', '')) === 'asset photo';
                                    $imageAttachments = array_values(array_filter($attachmentEntries, $isImageAttachment));
                                @endphp
                                @if(count($imageAttachments))
                                    <div class="attachments-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:12px;">
                                        @foreach($imageAttachments as $attachment)
                                            @php
                                                $previewUrl = data_get($attachment, 'image_data_uri') ?: data_get($attachment, 'url') ?: data_get($attachment, 'path');
                                            @endphp
                                            <a href="{{ data_get($attachment, 'url') ?: data_get($attachment, 'path') }}" target="_blank" style="padding:0;overflow:hidden;">
                                                <img src="{{ $previewUrl }}" alt="{{ data_get($attachment, 'name') ?: 'Attachment' }}" style="width:100%;height:140px;object-fit:cover;display:block;">
                                                <div style="padding:10px 12px;">
                                                    <strong>{{ data_get($attachment, 'name') ?: data_get($attachment, 'path') ?: 'Attachment' }}</strong>
                                                    <span>{{ data_get($attachment, 'category') ?: 'Asset Photo' }}</span>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                                @foreach($attachmentEntries as $attachment)
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
                        @else
                            <p class="note">No attachments uploaded yet.</p>
                        @endif
                    @elseif(data_get($section, 'type') === 'dv_line_items')
                        @php
                            $dvLineItems = array_values(array_filter((array) data_get($record->data, 'line_items', []), fn ($item) => is_array($item) && collect($item)->contains(fn ($value) => !blank($value))));
                            $dvSourceType = strtolower((string) ($dvSourceDocumentType ?? data_get($record->data, 'source_document_type', '')));
                            $dvSourceItems = array_values(array_filter((array) ($dvSourceLineItems ?? []), fn ($item) => is_array($item)));
                        @endphp
                        @if($dvSourceType === 'po')
                            <p class="note">Displaying the original Purchase Order itemized layout from the linked source document.</p>
                            @if(count($dvSourceItems))
                                <div class="pr-line-list">
                                    @foreach($dvSourceItems as $index => $item)
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
                                                    <div class="pr-field-card">
                                                        <p class="pr-field-label">Tax Classification</p>
                                                        <p class="pr-field-value">{{ $item['tax_type'] ?? 'N/A' }}</p>
                                                    </div>
                                                </div>
                                                <div class="pr-summary-panel">
                                                    <div class="pr-summary-head">
                                                        <div>
                                                            <p class="pr-summary-title">Cost Summary</p>
                                                            <p class="pr-summary-subtitle">Each item keeps its original PO values.</p>
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
                                                                <p class="pr-field-label">Tax Amount</p>
                                                                <p class="pr-field-value">{{ $item['tax_amount'] ?? '0.00' }}</p>
                                                            </div>
                                                        </div>
                                                        <div class="pr-summary-row">
                                                            <div class="pr-field-card">
                                                                <p class="pr-field-label">WHT</p>
                                                                <p class="pr-field-value">{{ $item['wht_amount'] ?? '0.00' }}</p>
                                                            </div>
                                                            <div class="pr-field-card">
                                                                <p class="pr-field-label">Item Total</p>
                                                                <p class="pr-field-value">{{ $item['total'] }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="muted">No line items were found on the linked Purchase Order.</p>
                            @endif
                        @elseif(count($dvLineItems))
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
                                $varianceIndicator = (string) ($liquidationReport['variance_indicator'] ?? 'Balanced');
                                $routeActionLabel = $varianceIndicator === 'Shortage'
                                    ? 'Create ERR'
                                    : ($varianceIndicator === 'Overage' ? 'Create CRF' : '');
                                $routeActionHelp = $varianceIndicator === 'Shortage'
                                    ? 'Shortage detected. Route this liquidation to ERR.'
                                    : ($varianceIndicator === 'Overage'
                                        ? 'Overage detected. Route this liquidation to CRF.'
                                        : 'Balanced liquidation. No follow-up request needed.');
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

                                <div class="lr-report-panel" style="margin-bottom:12px;">
                                    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;">
                                        <div>
                                            <p class="lr-metric-label">Next Action</p>
                                            <p class="lr-metric-value" style="font-size:12px;font-weight:500;">{{ $routeActionHelp }}</p>
                                        </div>
                                        @if($varianceIndicator !== 'Balanced')
                                            <button
                                                type="button"
                                                onclick="window.financeModule.openPendingLiquidationBranch()"
                                                style="border:none;border-radius:10px;background:#2563eb;color:#fff;padding:10px 16px;font-size:12px;font-weight:700;cursor:pointer;"
                                            >
                                                {{ $routeActionLabel }}
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <div class="lr-report-grid">
                                    <div class="lr-report-panel">
                                        <div class="lr-report-metrics">
                                            <div class="lr-metric">
                                                <p class="lr-metric-label">CA Reference No.</p>
                                                <p class="lr-metric-value">{{ $liquidationReport['ca_reference_no'] ?? 'N/A' }}</p>
                                            </div>
                                            <div class="lr-metric">
                                                <p class="lr-metric-label">Requested By</p>
                                                <p class="lr-metric-value">{{ $liquidationReport['employee_name'] ?? 'N/A' }}</p>
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
                                <p class="asset-tag-subtitle">Asset Identification Plate</p>
                            </div>
                            <div class="asset-tag-layout">
                                <div class="asset-tag-left-pane">
                                    <div class="asset-tag-box asset-tag-code-box">
                                        <p class="asset-tag-box-label">Asset Code</p>
                                        <p class="asset-tag-code-value">{{ data_get($section, 'asset_code') ?: 'N/A' }}</p>
                                    </div>
                                    <div class="asset-tag-box">
                                        <p class="asset-tag-box-label">Location</p>
                                        <p class="asset-tag-box-value">{{ data_get($section, 'location') ?: 'N/A' }}</p>
                                    </div>
                                    <div class="asset-tag-box">
                                        <p class="asset-tag-box-label">Serial Number</p>
                                        <p class="asset-tag-box-value">{{ data_get($section, 'serial_number') ?: 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="asset-tag-right-pane">
                                    <div class="asset-tag-barcode-box">
                                        {!! data_get($section, 'barcode_svg') !!}
                                    </div>
                                    <div class="asset-tag-note-box">
                                        <p class="asset-tag-note-title">System Tag Preview</p>
                                        <p class="asset-tag-note-copy">Print layout is optimized separately for the tape label.</p>
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

        @php
            $hasAttachmentSection = collect($previewSections ?? [])->contains(fn ($section) => data_get($section, 'type') === 'attachments');
        @endphp
        @if(count($attachments) && ! $hasAttachmentSection)
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

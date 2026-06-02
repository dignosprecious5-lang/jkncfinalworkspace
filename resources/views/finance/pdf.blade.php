<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: letter;
            margin: 10px 12px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 8.5px;
            line-height: 1.18;
            margin: 0;
        }

        .page {
            width: 100%;
        }

        .header-table {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 14px;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
        }

        .header-cell {
            vertical-align: top;
            padding: 7px 9px;
            border-right: 1px solid #e5e7eb;
        }

        .header-cell:last-child {
            border-right: 0;
        }

        .logo-wrap {
            width: 48px;
            height: 48px;
            border: 1px solid #dbeafe;
            border-radius: 10px;
            display: inline-block;
            text-align: center;
            line-height: 48px;
            margin-right: 8px;
            vertical-align: middle;
        }

        .logo-wrap img {
            width: 40px;
            height: 40px;
            object-fit: contain;
            vertical-align: middle;
        }

        .brand-block {
            display: inline-block;
            vertical-align: middle;
            max-width: 70%;
        }

        .eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.28em;
            font-size: 7px;
            color: #6b7280;
            margin: 0 0 6px;
        }

        .brand-name {
            font-size: 15px;
            line-height: 1.1;
            margin: 0;
            color: #111827;
            font-weight: 700;
        }

        .brand-subtitle {
            margin: 4px 0 0;
            font-size: 8.5px;
            color: #1d4ed8;
            font-weight: 700;
            letter-spacing: 0.04em;
        }

        .brand-note {
            margin: 4px 0 0;
            color: #6b7280;
            font-size: 8px;
        }

        .status-box {
            text-align: right;
        }

        .status-title {
            margin: 0 0 6px;
            text-transform: uppercase;
            letter-spacing: 0.28em;
            font-size: 7px;
            color: #6b7280;
        }

        .status-line {
            margin: 0;
            font-size: 8.5px;
            font-weight: 700;
            color: #111827;
        }

        .audit-banner {
            margin-top: 8px;
            border: 1px solid #c7d2fe;
            border-radius: 10px;
            background: linear-gradient(135deg, #eff6ff 0%, #ffffff 55%, #eef2ff 100%);
            padding: 7px 8px;
        }

        .audit-banner-head {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            align-items: flex-start;
        }

        .audit-banner-title {
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.22em;
            font-size: 7px;
            color: #4b5563;
            font-weight: 700;
        }

        .audit-banner-copy {
            margin: 3px 0 0;
            font-size: 7.5px;
            color: #4b5563;
        }

        .audit-badges {
            text-align: right;
        }

        .audit-badge {
            display: inline-block;
            margin-left: 3px;
            margin-bottom: 3px;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 7px;
            font-weight: 700;
            background: #e0e7ff;
            color: #3730a3;
        }

        .audit-badge.locked {
            background: #111827;
            color: #ffffff;
        }

        .audit-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 4px;
            margin-top: 5px;
        }

        .audit-grid td {
            width: 33.333%;
            border: 1px solid #dbe2ea;
            border-radius: 8px;
            background: #ffffff;
            padding: 4px 5px;
        }

        .audit-label {
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            font-size: 6.5px;
            color: #6b7280;
        }

        .audit-value {
            margin: 2px 0 0;
            font-size: 8px;
            font-weight: 700;
            color: #111827;
            word-break: break-word;
        }

        .section-title {
            margin: 7px 0 0;
            padding: 5px 7px;
            background: #1d4ed8;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.28em;
            font-size: 8px;
            font-weight: 700;
            border-radius: 5px;
        }

        .summary-table,
        .detail-table,
        .line-table,
        .simple-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td,
        .detail-table td,
        .line-table th,
        .line-table td,
        .simple-table td {
            border: 1px solid #dbe2ea;
            vertical-align: top;
        }

        .summary-table td {
            width: 25%;
            padding: 5px 6px;
            height: 36px;
        }

        .summary-label,
        .detail-label,
        .table-head {
            text-transform: uppercase;
            letter-spacing: 0.22em;
            font-size: 7px;
            color: #6b7280;
        }

        .summary-value,
        .detail-value {
            margin-top: 3px;
            font-size: 9.5px;
            color: #111827;
            font-weight: 700;
            word-break: break-word;
        }

        .detail-table td {
            width: 50%;
            padding: 5px 6px;
        }

        .detail-value {
            font-weight: 600;
        }

        .section-box {
            margin-top: 6px;
            border: 1px solid #dbe2ea;
            border-radius: 8px;
            overflow: hidden;
        }

        .section-box .section-title {
            margin: 0;
            border-radius: 0;
        }

        .section-body {
            padding: 6px;
        }

        .line-table th,
        .line-table td {
            padding: 4px 5px;
        }

        .line-table th {
            background: #f8fafc;
            font-size: 8px;
            text-align: left;
        }

        .line-table td {
            font-size: 8px;
        }

        .pr-line-list {
            display: block;
        }

        .pr-line-card {
            border: 1px solid #dbe2ea;
            border-radius: 8px;
            background: #f8fafc;
            padding: 5px 6px;
            margin-bottom: 4px;
        }

        .pr-line-head {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: flex-start;
        }

        .pr-line-index {
            display: inline-block;
            width: 22px;
            height: 22px;
            line-height: 22px;
            border-radius: 999px;
            background: #1d4ed8;
            color: #ffffff;
            text-align: center;
            font-size: 10px;
            font-weight: 700;
            margin-right: 8px;
        }

        .pr-line-title {
            font-size: 9px;
            font-weight: 700;
            color: #111827;
        }

        .pr-line-meta {
            margin-top: 2px;
            color: #6b7280;
            font-size: 7.5px;
        }

        .pr-line-total {
            border: 1px solid #e5e7eb;
            background: #ffffff;
            border-radius: 999px;
            padding: 3px 6px;
            font-size: 8px;
            font-weight: 700;
            white-space: nowrap;
        }

        .pr-line-grid {
            display: table;
            width: 100%;
            border-spacing: 3px;
            margin-top: 4px;
        }

        .pr-line-fields {
            display: table-cell;
            vertical-align: top;
            width: 60%;
        }

        .pr-line-summary {
            display: table-cell;
            vertical-align: top;
            width: 40%;
        }

        .pr-line-field {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #ffffff;
            padding: 4px 5px;
            margin-bottom: 3px;
        }

        .pr-field-label {
            text-transform: uppercase;
            letter-spacing: 0.18em;
            font-size: 7px;
            color: #6b7280;
            margin: 0;
        }

        .pr-field-value {
            margin-top: 2px;
            font-size: 8.5px;
            font-weight: 700;
            color: #111827;
            word-break: break-word;
        }

        .pr-summary-panel {
            border: 1px solid #dbeafe;
            border-radius: 8px;
            background: #ffffff;
            padding: 5px;
        }

        .pr-summary-title {
            text-transform: uppercase;
            letter-spacing: 0.22em;
            font-size: 7px;
            color: #1d4ed8;
            margin: 0;
            font-weight: 700;
        }

        .pr-summary-subtitle {
            margin: 4px 0 0;
            font-size: 7px;
            color: #6b7280;
        }

        .pr-summary-stack {
            margin-top: 4px;
        }

        .pr-summary-row {
            display: table;
            width: 100%;
            border-spacing: 3px;
            margin-top: 3px;
        }

        .pr-summary-cell {
            display: table-cell;
            vertical-align: top;
            width: 50%;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #ffffff;
            padding: 4px 5px;
        }

        .pr-summary-full {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #ffffff;
            padding: 4px 5px;
        }

        .pr-summary-formula {
            margin-top: 4px;
            font-size: 7.5px;
            font-weight: 700;
            color: #111827;
        }

        .po-supplier-card {
            border: 1px solid #dbe2ea;
            border-radius: 8px;
            background: #f8fafc;
            padding: 5px 6px;
            margin-bottom: 5px;
        }

        .po-supplier-head {
            width: 100%;
            margin-bottom: 4px;
        }

        .lr-report {
            border: 1px solid #dbe2ea;
            border-radius: 8px;
            background: #f8fafc;
            padding: 6px;
        }

        .lr-report-head {
            width: 100%;
        }

        .lr-report-title {
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.22em;
            font-size: 7px;
            color: #2563eb;
            font-weight: 700;
        }

        .lr-report-name {
            margin: 4px 0 0;
            font-size: 11px;
            font-weight: 700;
            color: #111827;
        }

        .lr-report-subtitle {
            margin: 4px 0 0;
            font-size: 8px;
            color: #6b7280;
        }

        .lr-report-badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 999px;
            font-size: 7px;
            font-weight: 700;
        }

        .lr-report-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 4px;
            margin-top: 5px;
        }

        .lr-report-grid td {
            vertical-align: top;
        }

        .lr-report-panel {
            border: 1px solid #e5e7eb;
            border-radius: 7px;
            background: #ffffff;
            padding: 5px;
        }

        .lr-report-metrics {
            width: 100%;
            border-collapse: separate;
            border-spacing: 3px;
        }

        .lr-report-metrics td {
            border: 1px solid #e5e7eb;
            border-radius: 7px;
            background: #f8fafc;
            padding: 4px 5px;
            width: 50%;
        }

        .lr-metric-label {
            text-transform: uppercase;
            letter-spacing: 0.18em;
            font-size: 7px;
            color: #6b7280;
            margin: 0;
        }

        .lr-metric-value {
            margin-top: 4px;
            font-size: 8.5px;
            font-weight: 700;
            color: #111827;
            word-break: break-word;
        }

        .lr-calc-band {
            margin-top: 4px;
            border: 1px dashed #bfdbfe;
            border-radius: 7px;
            background: #eff6ff;
            padding: 4px 5px;
        }

        .lr-calc-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 3px;
            margin-top: 3px;
        }

        .lr-calc-grid td {
            border: 1px solid #e5e7eb;
            border-radius: 7px;
            background: #ffffff;
            padding: 4px 5px;
            width: 33.333%;
        }

        .lr-note-stack {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 3px;
        }

        .lr-note-stack td {
            border: 1px solid #e5e7eb;
            border-radius: 7px;
            background: #f8fafc;
            padding: 4px 5px;
        }

        .pr-notes-wrap {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 3px;
        }

        .muted {
            color: #6b7280;
        }

        .attachment-list {
            margin: 0;
            padding-left: 16px;
        }

        .attachment-list li {
            margin: 0 0 2px;
        }

        .two-column {
            width: 100%;
            border-collapse: collapse;
        }

        .two-column td {
            width: 50%;
            vertical-align: top;
            border: 1px solid #dbe2ea;
            padding: 5px 6px;
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
    </style>
</head>
<body>
    <div class="page">
        <table class="header-table">
            <tr>
                <td class="header-cell" style="width: 62%;">
                    <div>
                        @if($companyLogo)
                            <span class="logo-wrap">
                                <img src="{{ $companyLogo }}" alt="{{ $companyName }}">
                            </span>
                        @endif
                        <div class="brand-block">
                            <p class="eyebrow">Official Finance Form</p>
                            <h1 class="brand-name">{{ $companyName }}</h1>
                            <p class="brand-subtitle">{{ $companyLegalName }} | {{ $moduleLabel }}</p>
                            <p class="brand-note">{{ $record->record_number ?: 'N/A' }} - {{ $recordTitleLabel ?: 'Name' }}: {{ $record->record_title ?: 'N/A' }}</p>
                        </div>
                    </div>
                </td>
                <td class="header-cell" style="width: 38%;">
                    <div class="status-box">
                        <p class="status-title">Document Status</p>
                        <p class="status-line">Workflow: {{ $record->workflow_status ?: 'N/A' }}</p>
                        <p class="status-line">Approval: {{ $record->approval_status ?: 'N/A' }}</p>
                        <p class="status-line">Status: {{ $record->status ?: 'N/A' }}</p>
                    </div>
                </td>
            </tr>
        </table>

        @php
            $auditHistory = (array) data_get($data, 'history', []);
            $relationshipStatus = data_get($data, 'relationship_status') ?: 'In Progress';
            $isLocked = in_array($record->status ?? '', ['Disbursed', 'Completed', 'Closed'], true) || !($record->can_edit ?? true);
        @endphp
        <div class="audit-banner">
            <div class="audit-banner-head">
                <div>
                    <p class="audit-banner-title">Audit / Control Status</p>
                    <p class="audit-banner-copy">System-managed status, relationship updates, validations, and history. Users manage the transaction, while the system manages the control state.</p>
                </div>
                <div class="audit-badges">
                    <span class="audit-badge {{ $isLocked ? 'locked' : '' }}">{{ $isLocked ? 'Read-only' : 'Editable' }}</span>
                    <span class="audit-badge">{{ $record->status ?: 'N/A' }}</span>
                    <span class="audit-badge">{{ $relationshipStatus }}</span>
                </div>
            </div>
            <table class="audit-grid">
                <tr>
                    <td>
                        <p class="audit-label">Current Status</p>
                        <p class="audit-value">{{ $record->status ?: 'N/A' }}</p>
                    </td>
                    <td>
                        <p class="audit-label">Relationship</p>
                        <p class="audit-value">{{ $relationshipStatus }}</p>
                    </td>
                    <td>
                        <p class="audit-label">History Entries</p>
                        <p class="audit-value">{{ count($auditHistory) }}</p>
                    </td>
                </tr>
            </table>
        </div>

        <table class="summary-table" style="margin-top: 12px;">
            @foreach(array_chunk($summaryCards, 4) as $row)
                <tr>
                    @foreach($row as $card)
                        <td>
                            <div class="summary-label">{{ $card['label'] }}</div>
                            <div class="summary-value">{{ $card['value'] }}</div>
                        </td>
                    @endforeach
                    @for($pad = count($row); $pad < 4; $pad++)
                        <td></td>
                    @endfor
                </tr>
            @endforeach
        </table>

        @if(!empty($approvalTrailRows))
            <div class="section-box">
                <div class="section-title">Approval Trail</div>
                <div class="section-body">
                    <table class="line-table">
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

        @if(!empty($attachmentSummary['items'] ?? []))
            <div class="section-box">
                <div class="section-title">Attachment Summary</div>
                <div class="section-body">
                    <table class="detail-table">
                        <tr>
                            <td>
                                <div class="detail-label">Total Attachments</div>
                                <div class="detail-value">{{ $attachmentSummary['total_count'] ?? 0 }}</div>
                            </td>
                            <td>
                                <div class="detail-label">Photo Attachments</div>
                                <div class="detail-value">{{ $attachmentSummary['image_count'] ?? 0 }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="detail-label">Document Attachments</div>
                                <div class="detail-value">{{ $attachmentSummary['document_count'] ?? 0 }}</div>
                            </td>
                            <td>
                                <div class="detail-label">Attachment Types</div>
                                <div class="detail-value">{{ !empty($attachmentSummary['type_summary']) ? implode('; ', $attachmentSummary['type_summary']) : 'N/A' }}</div>
                            </td>
                        </tr>
                    </table>
                    <table class="line-table" style="margin-top: 8px;">
                        <tr>
                            <th>File</th>
                            <th>Category</th>
                            <th>Uploaded By</th>
                            <th>Uploaded At</th>
                        </tr>
                        @foreach(($attachmentSummary['items'] ?? []) as $attachment)
                            <tr>
                                <td>{{ $attachment['name'] ?? '-' }}</td>
                                <td>{{ $attachment['category'] ?? '-' }}</td>
                                <td>{{ $attachment['uploaded_by'] ?: '-' }}</td>
                                <td>{{ $attachment['uploaded_at'] ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        @endif

        @if(!empty($completeDataRows))
            <div class="section-box">
                <div class="section-title">Complete Record Data</div>
                <div class="section-body">
                    <table class="detail-table">
                        @foreach(array_chunk($completeDataRows, 2) as $pair)
                            <tr>
                                @foreach($pair as $detail)
                                    <td>
                                        <div class="detail-label">{{ $detail['label'] }}</div>
                                        <div class="detail-value">{{ $detail['value'] }}</div>
                                    </td>
                                @endforeach
                                @for($pad = count($pair); $pad < 2; $pad++)
                                    <td></td>
                                @endfor
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        @endif

        @if(empty($isTemplatePreview) && !empty($transactionProgress))
            <div class="section-box">
                <div class="section-title">Transaction Progress Tracker</div>
                <div class="section-body">
                    <table class="line-table">
                        <tr>
                            <th>Step</th>
                            <th>Status</th>
                        </tr>
                        @foreach($transactionProgress as $step)
                            <tr>
                                <td>{{ data_get($step, 'label') ?: 'Step' }}</td>
                                <td>{{ data_get($step, 'completed') ? 'Completed' : (data_get($step, 'state') === 'current' ? 'Current' : 'Pending') }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        @endif

        @if($record->module_key === 'ca')
            <div class="section-box">
                <div class="section-title">Cash Advance Payment Tracking</div>
                <div class="section-body">
                    @if(!empty($cashAdvancePaymentTracking))
                        <table class="detail-table">
                            @foreach(array_chunk($cashAdvancePaymentTracking['summary'] ?? [], 2) as $pair)
                                <tr>
                                    @foreach($pair as $detail)
                                        <td>
                                            <div class="detail-label">{{ $detail['label'] }}</div>
                                            <div class="detail-value">{{ $detail['value'] }}</div>
                                        </td>
                                    @endforeach
                                    @for($pad = count($pair); $pad < 2; $pad++)
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
                        <div class="muted">No cash advance payment tracking available.</div>
                    @endif
                </div>
            </div>
        @endif

        @php
            $specialPreviewSections = collect($previewSections ?? [])
                ->filter(fn ($section) => in_array(data_get($section, 'type'), ['attachments', 'history', 'next_action_callout'], true))
                ->values();
            $hasAttachmentSection = $specialPreviewSections->contains(fn ($section) => data_get($section, 'type') === 'attachments');
            $hasHistorySection = $specialPreviewSections->contains(fn ($section) => data_get($section, 'type') === 'history');
        @endphp

        @if($specialPreviewSections->isNotEmpty())
            @foreach($specialPreviewSections as $section)
                @if(data_get($section, 'type') === 'attachments')
                    @if(count($attachments))
                        <div class="section-box">
                            <div class="section-title">Attachments</div>
                            <div class="section-body">
                                @php
                                    $isImageAttachment = fn ($attachment) => str_starts_with(strtolower((string) data_get($attachment, 'mime', '')), 'image/')
                                        || in_array(strtolower(pathinfo((string) data_get($attachment, 'name', data_get($attachment, 'path', '')), PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true)
                                        || strtolower((string) data_get($attachment, 'category', '')) === 'asset photo';
                                    $imageAttachments = array_values(array_filter($attachments, $isImageAttachment));
                                @endphp
                                @if(count($imageAttachments))
                                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:12px;">
                                        @foreach($imageAttachments as $attachment)
                                            @php
                                                $previewUrl = data_get($attachment, 'image_data_uri') ?: data_get($attachment, 'url') ?: data_get($attachment, 'path');
                                            @endphp
                                            <div style="border:1px solid #dbe2ea;border-radius:8px;overflow:hidden;background:#fff;">
                                                <img src="{{ $previewUrl }}" alt="{{ data_get($attachment, 'name') ?: 'Attachment' }}" style="width:100%;height:140px;object-fit:cover;display:block;">
                                                <div style="padding:8px 10px;">
                                                    <div class="detail-value">{{ data_get($attachment, 'name') ?: data_get($attachment, 'path') ?: 'Attachment' }}</div>
                                                    <div class="detail-label">{{ data_get($attachment, 'category') ?: 'Asset Photo' }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                <table class="detail-table">
                                    @foreach($attachments as $index => $attachment)
                                        <tr>
                                            <td>
                                                <div class="detail-label">File {{ $index + 1 }}</div>
                                                <div class="detail-value">{{ data_get($attachment, 'name') ?: data_get($attachment, 'path') ?: 'Attachment' }}</div>
                                            </td>
                                            <td>
                                                <div class="detail-label">{{ data_get($attachment, 'category') ?: 'Supporting Document' }}</div>
                                                <div class="detail-value">{{ data_get($attachment, 'path') ?: 'N/A' }}</div>
                                                @if(data_get($attachment, 'uploaded_by') || data_get($attachment, 'uploaded_at'))
                                                    <div class="detail-label" style="margin-top:4px;">Uploaded</div>
                                                    <div class="detail-value" style="font-weight:500;">{{ collect([data_get($attachment, 'uploaded_by'), data_get($attachment, 'uploaded_at')])->filter()->implode(' • ') }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    @endif
                @elseif(data_get($section, 'type') === 'next_action_callout')
                    <div class="section-box" style="border-color:#c7d2fe; background:linear-gradient(135deg,#eef2ff 0%,#ffffff 100%);">
                        <div class="section-title" style="background:#4338ca;">Next Action</div>
                        <div class="section-body">
                            <table class="detail-table">
                                <tr>
                                    <td colspan="2">
                                        <div class="detail-label">Action</div>
                                        <div class="detail-value" style="font-size:14px;">{{ data_get($section, 'next_action') ?: 'Create Disbursement Voucher' }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="detail-label">Relationship Status</div>
                                        <div class="detail-value">{{ data_get($section, 'relationship_status') ?: 'In Progress' }}</div>
                                    </td>
                                    <td>
                                        <div class="detail-label">Status Note</div>
                                        <div class="detail-value">{{ data_get($section, 'description') ?: 'This record is ready for the next step in the workflow.' }}</div>
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
                        <div class="section-box">
                            <div class="section-title">Record History / Audit Trail</div>
                            <div class="section-body">
                                @foreach(array_reverse($historyEntries) as $entry)
                                    <table class="detail-table" style="margin-bottom: 8px;">
                                        <tr>
                                            <td>
                                                <div class="detail-label">Action</div>
                                                <div class="detail-value">{{ data_get($entry, 'action') ?: 'Action' }}</div>
                                            </td>
                                            <td>
                                                <div class="detail-label">Changed By</div>
                                                <div class="detail-value">{{ data_get($entry, 'changed_by') ?: 'System' }}</div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="detail-label">Changed At</div>
                                                <div class="detail-value">{{ data_get($entry, 'changed_at') ?: 'N/A' }}</div>
                                            </td>
                                            <td>
                                                <div class="detail-label">Reason</div>
                                                <div class="detail-value">{{ data_get($entry, 'reason') ?: 'N/A' }}</div>
                                            </td>
                                        </tr>
                                    </table>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            @endforeach
        @endif

        @php
            $fieldPreviewSections = collect($previewSections ?? [])
                ->filter(fn ($section) => data_get($section, 'type') === 'fields')
                ->values();
        @endphp

        @if($fieldPreviewSections->isNotEmpty())
            @foreach($fieldPreviewSections as $section)
                <div class="section-box">
                    <div class="section-title">{{ data_get($section, 'title', 'Details') }}</div>
                    <div class="section-body">
                        @php $rows = data_get($section, 'rows', []); @endphp
                        @if(count($rows))
                            <table class="detail-table">
                                @foreach(array_chunk($rows, 2) as $pair)
                                    <tr>
                                        @foreach($pair as $detail)
                                            <td>
                                                <div class="detail-label">{{ $detail['label'] }}</div>
                                                <div class="detail-value">{{ $detail['value'] }}</div>
                                            </td>
                                        @endforeach
                                        @for($pad = count($pair); $pad < 2; $pad++)
                                            <td></td>
                                        @endfor
                                    </tr>
                                @endforeach
                            </table>
                        @else
                            <div class="muted">No details provided.</div>
                        @endif
                    </div>
                </div>
            @endforeach
        @elseif(count($detailRows ?? []))
            <div class="section-box">
                <div class="section-title">Details</div>
                <div class="section-body">
                    <table class="detail-table">
                        @foreach(array_chunk($detailRows, 2) as $pair)
                            <tr>
                                @foreach($pair as $detail)
                                    <td>
                                        <div class="detail-label">{{ $detail['label'] }}</div>
                                        <div class="detail-value">{{ $detail['value'] }}</div>
                                    </td>
                                @endforeach
                                @for($pad = count($pair); $pad < 2; $pad++)
                                    <td></td>
                                @endfor
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        @else
            <div class="section-box">
                <div class="section-title">Details</div>
                <div class="section-body">
                    <div class="muted">No additional details provided.</div>
                </div>
            </div>
        @endif

        @php
            $historyEntries = array_values(array_filter((array) data_get($record->data, 'history', []), fn ($entry) => is_array($entry)));
        @endphp
        @if(count($historyEntries) && ! $hasHistorySection)
            <div class="section-box">
                <div class="section-title">Record History / Audit Trail</div>
                <div class="section-body">
                    @foreach(array_reverse($historyEntries) as $entry)
                        <table class="detail-table" style="margin-bottom: 8px;">
                            <tr>
                                <td>
                                    <div class="detail-label">Action</div>
                                    <div class="detail-value">{{ data_get($entry, 'action') ?: 'Action' }}</div>
                                </td>
                                <td>
                                    <div class="detail-label">Changed By</div>
                                    <div class="detail-value">{{ data_get($entry, 'changed_by') ?: 'System' }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="detail-label">Changed At</div>
                                    <div class="detail-value">{{ data_get($entry, 'changed_at') ?: 'N/A' }}</div>
                                </td>
                                <td>
                                    <div class="detail-label">Reason</div>
                                    <div class="detail-value">{{ data_get($entry, 'reason') ?: 'N/A' }}</div>
                                </td>
                            </tr>
                        </table>
                    @endforeach
                </div>
            </div>
        @endif

        @if($record->module_key === 'dv')
            @php
                $dvLineItems = array_values(array_filter((array) data_get($record->data, 'line_items', []), fn ($item) => is_array($item) && collect($item)->contains(fn ($value) => !blank($value))));
            @endphp
            <div class="section-box">
                <div class="section-title">Breakdown / Line Items</div>
                <div class="section-body">
                    @if(count($dvLineItems))
                        <table class="detail-table">
                            <tr>
                                <td><div class="detail-label">Description</div></td>
                                <td><div class="detail-label">Account Code</div></td>
                                <td><div class="detail-label">Debit</div></td>
                                <td><div class="detail-label">Credit</div></td>
                            </tr>
                            @foreach($dvLineItems as $item)
                                <tr>
                                    <td><div class="detail-value">{{ data_get($item, 'description') ?: 'N/A' }}</div></td>
                                    <td><div class="detail-value">{{ data_get($item, 'account_code') ?: 'N/A' }}</div></td>
                                    <td><div class="detail-value">{{ number_format((float) data_get($item, 'debit', 0), 2) }}</div></td>
                                    <td><div class="detail-value">{{ number_format((float) data_get($item, 'credit', 0), 2) }}</div></td>
                                </tr>
                            @endforeach
                        </table>
                    @else
                        <div class="muted">No line items added.</div>
                    @endif
                </div>
            </div>
        @endif

        @if($record->module_key === 'lr')
            <div class="section-box">
                <div class="section-title">Liquidation Report</div>
                <div class="section-body">
                    @if(!empty($liquidationReport))
                        <div class="lr-report">
                            <table class="detail-table" style="margin-bottom: 8px;">
                                <tr>
                                    <td style="width: 78%;">
                                        <div class="lr-report-title">Liquidation Value Statement</div>
                                        <div class="lr-report-name">{{ $liquidationReport['status_label'] ?? 'Balanced' }}</div>
                                        <div class="lr-report-subtitle">Built from the liquidation fields in the slider form.</div>
                                    </td>
                                    <td style="text-align:right;">
                                        <span class="lr-report-badge" style="background: {{ str_contains(strtolower((string) ($liquidationReport['status_label'] ?? 'Balanced')), 'shortage') ? '#fee2e2;color:#991b1b;' : (str_contains(strtolower((string) ($liquidationReport['status_label'] ?? 'Balanced')), 'overage') ? '#dcfce7;color:#065f46;' : '#dbeafe;color:#1d4ed8;') }}">{{ $liquidationReport['variance_indicator'] ?? 'Balanced' }}</span>
                                    </td>
                                </tr>
                            </table>

                            <table class="lr-report-grid">
                                <tr>
                                    <td style="width:60%;">
                                        <div class="lr-report-panel">
                                            <table class="lr-report-metrics">
                                                <tr>
                                                    <td>
                                                        <div class="lr-metric-label">CA Reference No.</div>
                                                        <div class="lr-metric-value">{{ $liquidationReport['ca_reference_no'] ?? 'N/A' }}</div>
                                                    </td>
                                                    <td>
                                                        <div class="lr-metric-label">CA Amount</div>
                                                        <div class="lr-metric-value">{{ $liquidationReport['ca_amount'] ?? '0.00' }}</div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="lr-metric-label">Line Items Total</div>
                                                        <div class="lr-metric-value">{{ $liquidationReport['line_items_total'] ?? '0.00' }}</div>
                                                    </td>
                                                    <td>
                                                        <div class="lr-metric-label">For Client?</div>
                                                        <div class="lr-metric-value">{{ $liquidationReport['for_client'] ?? 'N/A' }}</div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="lr-metric-label">Client Name(s)</div>
                                                        <div class="lr-metric-value">{{ $liquidationReport['client_names'] ?? 'N/A' }}</div>
                                                    </td>
                                                    <td>
                                                        <div class="lr-metric-label">Actual Expenses</div>
                                                        <div class="lr-metric-value">{{ $liquidationReport['actual_expenses'] ?? '0.00' }}</div>
                                                    </td>
                                                </tr>
                                            </table>

                                            <div class="lr-calc-band">
                                                <div class="lr-metric-label">Calculation Band</div>
                                                <table class="lr-calc-grid">
                                                    <tr>
                                                        <td>
                                                            <div class="lr-metric-label">CA Amount</div>
                                                            <div class="lr-metric-value">{{ $liquidationReport['ca_amount'] ?? '0.00' }}</div>
                                                        </td>
                                                        <td>
                                                            <div class="lr-metric-label">Less Actual Expenses</div>
                                                            <div class="lr-metric-value">- {{ $liquidationReport['actual_expenses'] ?? '0.00' }}</div>
                                                        </td>
                                                        <td>
                                                            <div class="lr-metric-label">Variance</div>
                                                            <div class="lr-metric-value">{{ $liquidationReport['variance'] ?? '0.00' }}</div>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <div class="lr-metric-value" style="margin-top:8px;">Line Items Total = Sum of all item totals</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="width:40%;">
                                        <div class="lr-report-panel">
                                            <table class="lr-note-stack">
                                                <tr>
                                                    <td>
                                                        <div class="lr-metric-label">Variance Indicator</div>
                                                        <div class="lr-metric-value">{{ $liquidationReport['variance_indicator'] ?? 'Balanced' }}</div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="lr-metric-label">Status Note</div>
                                                        <div class="lr-metric-value">{{ $liquidationReport['status_label'] ?? 'Balanced' }}</div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="lr-metric-label">Purpose / Business Need</div>
                                                        <div class="lr-metric-value">{{ $liquidationReport['purpose'] ?? 'N/A' }}</div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="lr-metric-label">Remarks</div>
                                                        <div class="lr-metric-value">{{ $liquidationReport['remarks'] ?? 'N/A' }}</div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="lr-metric-label">Requested By</div>
                                                        <div class="lr-metric-value">{{ $liquidationReport['employee_name'] ?? 'N/A' }}</div>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    @else
                        <div class="muted">No liquidation report details available.</div>
                    @endif
                </div>
            </div>
        @endif

        @if($record->module_key === 'pr')
            <div class="section-box">
                <div class="section-title">Items / Cost Details</div>
                <div class="section-body">
                    @if(count($lineItems))
                        <div class="pr-line-list">
                            @foreach($lineItems as $index => $item)
                                <div class="pr-line-card">
                                    <div class="pr-line-head">
                                        <div>
                                            <span class="pr-line-index">{{ $index + 1 }}</span>
                                            <span class="pr-line-title">{{ $item['item'] }}</span>
                                            <div class="pr-line-meta">{{ $item['category'] }} | Qty: {{ $item['quantity'] }}</div>
                                        </div>
                                        <div class="pr-line-total">{{ $item['total'] }}</div>
                                    </div>
                                    <div class="pr-line-grid">
                                        <div class="pr-line-fields">
                                            <div class="pr-line-field">
                                                <div class="pr-field-label">Description</div>
                                                <div class="pr-field-value">{{ $item['description'] }}</div>
                                            </div>
                                            <div class="pr-line-field">
                                                <div class="pr-field-label">Unit Cost</div>
                                                <div class="pr-field-value">{{ $item['amount'] }}</div>
                                            </div>
                                            <div class="pr-line-field">
                                                <div class="pr-field-label">Line Total</div>
                                                <div class="pr-field-value">{{ $item['total'] }}</div>
                                            </div>
                                            <div class="pr-line-field">
                                                <div class="pr-field-label">Supplier</div>
                                                <div class="pr-field-value">{{ $item['supplier_label'] ?? '' }}</div>
                                            </div>
                                            <div class="pr-line-field">
                                                <div class="pr-field-label">Client</div>
                                                <div class="pr-field-value">{{ $item['client_label'] ?? '' }}</div>
                                            </div>
                                        </div>
                                        <div class="pr-line-summary">
                                            <div class="pr-summary-panel">
                                                <div class="pr-summary-title">Cost Summary</div>
                                                <div class="pr-summary-subtitle">Each item has its own adjustment values.</div>
                                                <div class="pr-summary-stack">
                                                    <div class="pr-summary-full">
                                                        <div class="pr-field-label">Subtotal</div>
                                                        <div class="pr-field-value">{{ $item['subtotal'] ?? $item['total'] }}</div>
                                                    </div>
                                                    <div class="pr-summary-row">
                                                        <div class="pr-summary-cell">
                                                            <div class="pr-field-label">Discount</div>
                                                            <div class="pr-field-value">{{ $item['discount'] ?? '0%' }}</div>
                                                        </div>
                                                        <div class="pr-summary-cell">
                                                            <div class="pr-field-label">Discount Amount</div>
                                                            <div class="pr-field-value">{{ $item['discount_amount'] ?? '0.00' }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="pr-summary-row">
                                                        <div class="pr-summary-cell">
                                                            <div class="pr-field-label">Shipping</div>
                                                            <div class="pr-field-value">{{ $item['shipping_amount'] ?? '0.00' }}</div>
                                                        </div>
                                                        <div class="pr-summary-cell">
                                                            <div class="pr-field-label">Tax (VAT/Non-VAT/N/A)</div>
                                                            <div class="pr-field-value">{{ $item['tax_type'] ?? 'N/A' }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="pr-summary-row">
                                                        <div class="pr-summary-cell">
                                                            <div class="pr-field-label">Tax Amount</div>
                                                            <div class="pr-field-value">{{ $item['tax_amount'] ?? '0.00' }}</div>
                                                        </div>
                                                        <div class="pr-summary-cell">
                                                            <div class="pr-field-label">WHT</div>
                                                            <div class="pr-field-value">{{ $item['wht_amount'] ?? '0.00' }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="pr-summary-full">
                                                        <div class="pr-field-label">Grand Total</div>
                                                        <div class="pr-field-value">{{ $item['total'] }}</div>
                                                    </div>
                                                    <div class="pr-summary-formula">{{ $item['quantity'] }} x {{ $item['amount'] }} = {{ $item['total'] }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="muted">No line items added yet.</div>
                    @endif

                    <div class="section-title" style="margin-top: 12px;">Purpose & Notes</div>
                    <div class="pr-notes-wrap" style="margin-top: 8px;">
                        <div class="pr-summary-card">
                            <div class="pr-field-label">Purpose / Justification</div>
                            <div class="pr-field-value">{{ data_get($record->data, 'purpose') ?: 'N/A' }}</div>
                        </div>
                        <div class="pr-summary-card">
                            <div class="pr-field-label">Remarks</div>
                            <div class="pr-field-value">{{ data_get($record->data, 'remarks') ?: 'N/A' }}</div>
                        </div>
                        <div class="pr-summary-card">
                            <div class="pr-field-label">Chart of Account</div>
                            <div class="pr-field-value">{{ $chartAccountLabel }}</div>
                        </div>
                    </div>

                    <div class="section-title" style="margin-top: 12px;">Account Allocation</div>
                    <table class="two-column" style="margin-top: 8px;">
                        <tr>
                            <td>
                                <div class="detail-label">Requester Option</div>
                                <div class="detail-value">{{ data_get($record->data, 'requester_mode') === 'own_request' ? 'Own Request' : (data_get($record->data, 'requester_mode') === 'request_for_another' ? 'Request for Another' : 'N/A') }}</div>
                            </td>
                            <td>
                                <div class="detail-label">Requestor</div>
                                <div class="detail-value">{{ data_get($record->data, 'requestor') ?: 'N/A' }}</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        @endif

        @if($record->module_key === 'po')
            <div class="section-box">
                <div class="section-title">Items / Cost Details</div>
                <div class="section-body">
                    @if(count($lineItems))
                        @foreach($poSupplierGroups as $group)
                            <div class="po-supplier-card">
                                <table class="po-supplier-head">
                                    <tr>
                                        <td style="width:78%;">
                                            <div class="pr-summary-title">Supplier</div>
                                            <div class="pr-line-title">{{ $group['supplier_label'] ?: 'Unspecified Supplier' }}</div>
                                            <div class="pr-line-meta">{{ $group['items_count'] ?? count($group['items'] ?? []) }} item(s) in this purchase order section</div>
                                        </td>
                                        <td style="width:22%; text-align:right;">
                                            <div class="pr-line-total">{{ $group['group_total'] ?? '0.00' }}</div>
                                        </td>
                                    </tr>
                                </table>

                                <div class="pr-line-list">
                                    @foreach(($group['items'] ?? []) as $index => $item)
                                        <div class="pr-line-card">
                                            <div class="pr-line-head">
                                                <div>
                                                    <span class="pr-line-index">{{ $index + 1 }}</span>
                                                    <span class="pr-line-title">{{ $item['item'] }}</span>
                                                    <div class="pr-line-meta">{{ $item['category'] }} | Qty: {{ $item['quantity'] }}</div>
                                                </div>
                                                <div class="pr-line-total">{{ $item['total'] }}</div>
                                            </div>
                                            <div class="pr-line-grid">
                                                <div class="pr-line-fields">
                                                    <div class="pr-line-field">
                                                        <div class="pr-field-label">Description</div>
                                                        <div class="pr-field-value">{{ $item['description'] }}</div>
                                                    </div>
                                                    <div class="pr-line-field">
                                                        <div class="pr-field-label">Unit Cost</div>
                                                        <div class="pr-field-value">{{ $item['amount'] }}</div>
                                                    </div>
                                                    <div class="pr-line-field">
                                                        <div class="pr-field-label">Line Total</div>
                                                        <div class="pr-field-value">{{ $item['total'] }}</div>
                                                    </div>
                                                </div>
                                                <div class="pr-line-summary">
                                                    <div class="pr-summary-panel">
                                                        <div class="pr-summary-title">Cost Summary</div>
                                                        <div class="pr-summary-subtitle">Each item has its own adjustment values.</div>
                                                        <div class="pr-summary-stack">
                                                            <div class="pr-summary-full">
                                                                <div class="pr-field-label">Subtotal</div>
                                                                <div class="pr-field-value">{{ $item['subtotal'] ?? $item['total'] }}</div>
                                                            </div>
                                                            <div class="pr-summary-row">
                                                                <div class="pr-summary-cell">
                                                                    <div class="pr-field-label">Discount</div>
                                                                    <div class="pr-field-value">{{ $item['discount'] ?? '0%' }}</div>
                                                                </div>
                                                                <div class="pr-summary-cell">
                                                                    <div class="pr-field-label">Discount Amount</div>
                                                                    <div class="pr-field-value">{{ $item['discount_amount'] ?? '0.00' }}</div>
                                                                </div>
                                                            </div>
                                                            <div class="pr-summary-row">
                                                                <div class="pr-summary-cell">
                                                                    <div class="pr-field-label">Shipping</div>
                                                                    <div class="pr-field-value">{{ $item['shipping_amount'] ?? '0.00' }}</div>
                                                                </div>
                                                                <div class="pr-summary-cell">
                                                                    <div class="pr-field-label">Tax (VAT/Non-VAT/N/A)</div>
                                                                    <div class="pr-field-value">{{ $item['tax_type'] ?? 'N/A' }}</div>
                                                                </div>
                                                            </div>
                                                            <div class="pr-summary-row">
                                                                <div class="pr-summary-cell">
                                                                    <div class="pr-field-label">Tax Amount</div>
                                                                    <div class="pr-field-value">{{ $item['tax_amount'] ?? '0.00' }}</div>
                                                                </div>
                                                                <div class="pr-summary-cell">
                                                                    <div class="pr-field-label">WHT</div>
                                                                    <div class="pr-field-value">{{ $item['wht_amount'] ?? '0.00' }}</div>
                                                                </div>
                                                            </div>
                                                            <div class="pr-summary-full">
                                                                <div class="pr-field-label">Grand Total</div>
                                                                <div class="pr-field-value">{{ $item['total'] }}</div>
                                                            </div>
                                                            <div class="pr-summary-formula">{{ $item['quantity'] }} x {{ $item['amount'] }} = {{ $item['total'] }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="muted">No line items added yet.</div>
                    @endif

                    <div class="section-title" style="margin-top: 12px;">Purpose & Notes</div>
                    <div class="pr-notes-wrap" style="margin-top: 8px;">
                        <div class="pr-summary-card">
                            <div class="pr-field-label">Primary Supplier</div>
                            <div class="pr-field-value">{{ data_get($record->data, 'supplier_id') ?: 'N/A' }}</div>
                        </div>
                        <div class="pr-summary-card">
                            <div class="pr-field-label">Purpose</div>
                            <div class="pr-field-value">{{ data_get($record->data, 'purpose') ?: 'N/A' }}</div>
                        </div>
                        <div class="pr-summary-card">
                            <div class="pr-field-label">Remarks</div>
                            <div class="pr-field-value">{{ data_get($record->data, 'remarks') ?: 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($record->module_key === 'arf')
            <div class="section-box">
                <div class="section-title">Asset Tag</div>
                <div class="section-body">
                    <div class="asset-tag-card">
                        <div class="asset-tag-head">
                            <p class="asset-tag-company">JK&amp;C INC.</p>
                            <div class="asset-tag-title">ASSET TAG</div>
                        </div>
                        <div class="asset-tag-grid">
                            <div class="asset-tag-label">Asset Code</div>
                            <div class="asset-tag-value">{{ data_get($record->data, 'asset_code') ?: $record->record_number ?: 'N/A' }}</div>
                            <div class="asset-tag-label">Location</div>
                            <div class="asset-tag-value">{{ data_get($record->data, 'location') ?: 'N/A' }}</div>
                            <div class="asset-tag-label">Serial Number</div>
                            <div class="asset-tag-value">{{ data_get($record->data, 'serial_number') ?: 'N/A' }}</div>
                            <div class="asset-tag-label">Barcode</div>
                            <div class="asset-tag-value">
                                <div class="asset-tag-barcode-box">
                                    {!! data_get($assetTag, 'barcode_svg') !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @php
            $historyEntries = collect(data_get($record->data ?? [], 'history', []))
                ->filter(fn ($entry) => is_array($entry))
                ->reverse()
                ->take(10)
                ->values();

            $historyValue = function ($value) {
                if (is_null($value) || $value === '') {
                    return 'Blank';
                }

                if (is_array($value)) {
                    return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }

                return (string) $value;
            };
        @endphp

        @if(empty($isTemplatePreview) && $historyEntries->isNotEmpty())
            <div class="section-box">
                <div class="section-title">Record History / Audit Trail</div>
                <div class="section-body">
                    @foreach($historyEntries as $entry)
                        @php
                            $entryChanges = (array) data_get($entry, 'changes', []);
                            $changes = collect($entryChanges)
                                ->filter(fn ($change) => is_array($change))
                                ->take(6)
                                ->values();
                            $remainingChanges = max(count($entryChanges) - $changes->count(), 0);
                        @endphp
                        <div style="border:1px solid #dbe2ea;border-radius:8px;padding:7px;margin-bottom:7px;page-break-inside:avoid;">
                            <div class="detail-value">{{ data_get($entry, 'action') ?: 'Action' }}</div>
                            <div class="muted">{{ data_get($entry, 'changed_by') ?: 'System' }} | {{ data_get($entry, 'changed_at') ?: 'N/A' }} | {{ data_get($entry, 'module') ?: $record->module_key }}</div>
                            @if(data_get($entry, 'reason'))
                                <div class="muted" style="color:#92400e;margin-top:3px;">Reason: {{ data_get($entry, 'reason') }}</div>
                            @endif

                            @if($changes->isNotEmpty())
                                <table class="detail-table" style="margin-top:6px;">
                                    <tr>
                                        <td><div class="detail-label">Field</div></td>
                                        <td><div class="detail-label">Old Value</div></td>
                                        <td><div class="detail-label">New Value</div></td>
                                    </tr>
                                    @foreach($changes as $change)
                                        <tr>
                                            <td><div class="detail-value">{{ str_replace('_', ' ', data_get($change, 'field') ?: 'Field') }}</div></td>
                                            <td><div class="detail-value">{{ $historyValue(data_get($change, 'old_value')) }}</div></td>
                                            <td><div class="detail-value">{{ $historyValue(data_get($change, 'new_value')) }}</div></td>
                                        </tr>
                                    @endforeach
                                </table>
                                @if($remainingChanges > 0)
                                    <div class="muted" style="margin-top:4px;">+{{ $remainingChanges }} more change(s)</div>
                                @endif
                            @else
                                <div class="muted" style="margin-top:4px;">No field-level changes were captured for this action.</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if(count($attachments) && ! $hasAttachmentSection)
            <div class="section-box">
                <div class="section-title">Attachments</div>
                <div class="section-body">
                    <table class="detail-table">
                        @foreach($attachments as $index => $attachment)
                            <tr>
                                <td>
                                    <div class="detail-label">File {{ $index + 1 }}</div>
                                    <div class="detail-value">{{ data_get($attachment, 'name') ?: data_get($attachment, 'path') ?: 'Attachment' }}</div>
                                </td>
                                <td>
                                    <div class="detail-label">{{ data_get($attachment, 'category') ?: 'Supporting Document' }}</div>
                                    <div class="detail-value">{{ data_get($attachment, 'path') ?: 'N/A' }}</div>
                                    @if(data_get($attachment, 'uploaded_by') || data_get($attachment, 'uploaded_at'))
                                        <div class="detail-label" style="margin-top:4px;">Uploaded</div>
                                        <div class="detail-value" style="font-weight:500;">{{ collect([data_get($attachment, 'uploaded_by'), data_get($attachment, 'uploaded_at')])->filter()->implode(' • ') }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        @endif
    </div>
</body>
</html>

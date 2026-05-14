<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transmittal Preview PDF</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 10mm 10mm 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            color: #111827;
            margin: 0;
            padding: 0;
            font-size: 13px;
        }

        .transmittal-doc-page {
            width: 100%;
            box-sizing: border-box;
            background: #fff;
        }

        .tm-title {
            text-align: center;
            margin-bottom: 16px;
        }

        .tm-title-main {
            font-size: 22px;
            font-weight: bold;
            line-height: 1.1;
        }

        .tm-top-block {
            font-size: 13px;
            margin-bottom: 14px;
        }

        .tm-top-row {
            width: 100%;
            margin-bottom: 6px;
            clear: both;
        }

        .tm-top-row-first-left,
        .tm-top-row-first-right {
            display: inline-block;
            vertical-align: top;
            width: 49%;
        }

        .tm-label {
            display: inline-block;
            width: 74px;
            font-weight: bold;
            vertical-align: top;
        }

        .tm-line {
            display: inline-block;
            width: calc(100% - 86px);
            border-bottom: 1px solid #9ca3af;
            min-height: 18px;
            padding-bottom: 2px;
        }

        .tm-meta-grid {
            width: 100%;
            font-size: 12px;
            margin-bottom: 14px;
        }

        .tm-meta-grid-row {
            margin-bottom: 6px;
        }

        .tm-meta-half {
            display: inline-block;
            width: 49%;
            vertical-align: top;
        }

        .tm-meta-label {
            font-weight: bold;
        }

        .tm-section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .tm-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 10.5px;
            margin-bottom: 14px;
        }

        .tm-table th,
        .tm-table td {
            border: 1px solid #9ca3af;
            padding: 6px 7px;
            vertical-align: top;
            text-align: left;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .tm-table th {
            font-weight: bold;
            background: #fff;
        }

        .col-no { width: 5%; }
        .col-particular { width: 15%; }
        .col-uid { width: 13%; }
        .col-qty { width: 6%; }
        .col-description { width: 25%; }
        .col-remarks { width: 16%; }
        .col-attachment { width: 20%; }

        .tm-footer-code {
            width: 100%;
            font-size: 11px;
            margin-bottom: 16px;
        }

        .tm-footer-left {
            float: left;
        }

        .tm-footer-right {
            float: right;
        }

        .tm-clear {
            clear: both;
        }

        .tm-signatures {
            width: 100%;
            margin-top: 10px;
            font-size: 12px;
        }

        .tm-sign-col {
            display: inline-block;
            width: 48%;
            vertical-align: top;
        }

        .tm-sign-block {
            margin-bottom: 14px;
        }

        .tm-sign-label {
            margin-bottom: 10px;
        }

        .tm-sign-line {
            border-bottom: 1px solid #9ca3af;
            min-height: 18px;
            padding-bottom: 2px;
            font-weight: bold;
        }

        .tm-sign-sub {
            margin-top: 3px;
            line-height: 1.15;
            font-size: 11px;
        }

        .attachment-name {
            word-break: break-word;
        }
    </style>
</head>
@php
    $transmittalText = strtolower($transmittal->items->map(fn ($item) => trim(($item->particular ?? '').' '.($item->remarks ?? '').' '.($item->description ?? '')))->implode(' '));
    $transmittalFormCode = str_contains($transmittalText, 'rsat') ? 'REG-F-004' : (str_contains($transmittalText, 'sow') ? 'PROJ-F-006' : '');
@endphp
<body>
    <div class="transmittal-doc-page">
        <div class="tm-title">
            <div class="tm-title-main">Transmittal Form</div>
            @if ($transmittalFormCode !== '')
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: .12em; color: #6b7280;">{{ $transmittalFormCode }}</div>
            @endif
        </div>

        <div class="tm-top-block">
            <div class="tm-top-row">
                <div class="tm-top-row-first-left">
                    <span class="tm-label">Ref No</span>
                    <span class="tm-line">{{ $transmittal->transmittal_no ?? '' }}</span>
                </div>
                <div class="tm-top-row-first-right">
                    <span class="tm-label">Date</span>
                    <span class="tm-line">
                        {{ $transmittal->transmittal_date ? \Carbon\Carbon::parse($transmittal->transmittal_date)->format('Y-m-d') : '' }}
                    </span>
                </div>
            </div>

            <div class="tm-top-row">
                <span class="tm-label">Mode</span>
                <span class="tm-line">{{ $transmittal->mode ?? '' }}</span>
            </div>

            <div class="tm-top-row">
                <span class="tm-label">From</span>
                <span class="tm-line">
                    {{ $transmittal->mode === 'SEND' ? ($transmittal->office_name ?? '') : ($transmittal->party_name ?? '') }}
                </span>
            </div>

            <div class="tm-top-row">
                <span class="tm-label">To</span>
                <span class="tm-line">
                    {{ $transmittal->mode === 'SEND' ? ($transmittal->party_name ?? '') : ($transmittal->office_name ?? '') }}
                </span>
            </div>

            <div class="tm-top-row">
                <span class="tm-label">Address</span>
                <span class="tm-line">{{ $transmittal->address ?? '' }}</span>
            </div>
        </div>

        <div class="tm-meta-grid">
            <div class="tm-meta-grid-row">
                <div class="tm-meta-half">
                    <span class="tm-meta-label">Delivery Type:</span>
                    @if(($transmittal->delivery_type ?? '') === 'By Person')
                        {{ $transmittal->by_person_who ? 'By Person - ' . $transmittal->by_person_who : 'By Person' }}
                    @elseif(($transmittal->delivery_type ?? '') === 'Registered Mail')
                        {{ $transmittal->registered_mail_provider ? 'Registered Mail - ' . $transmittal->registered_mail_provider : 'Registered Mail' }}
                    @elseif(($transmittal->delivery_type ?? '') === 'Electronic')
                        {{ $transmittal->electronic_method ? 'Electronic - ' . $transmittal->electronic_method : 'Electronic' }}
                    @else
                        —
                    @endif
                </div>
                <div class="tm-meta-half">
                    <span class="tm-meta-label">Actions:</span>
                    {{ collect([
                        $transmittal->action_delivery ? 'Delivery' : null,
                        $transmittal->action_pick_up ? 'Pick Up' : null,
                        $transmittal->action_drop_off ? 'Drop Off' : null,
                        $transmittal->action_email ? 'Email' : null,
                    ])->filter()->implode(', ') ?: '—' }}
                </div>
            </div>

            <div class="tm-meta-grid-row">
                <div class="tm-meta-half">
                    <span class="tm-meta-label">Recipient Email:</span>
                    {{ $transmittal->recipient_email ?: '—' }}
                </div>
                <div class="tm-meta-half">
                    <span class="tm-meta-label">Electronic Method:</span>
                    {{ $transmittal->electronic_method ?: '—' }}
                </div>
            </div>
        </div>

        <div class="tm-section-title">List of Items</div>

        <table class="tm-table">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-particular">Particular</th>
                    <th class="col-uid">Unique ID</th>
                    <th class="col-qty">Qty.</th>
                    <th class="col-description">Description</th>
                    <th class="col-remarks">Remarks</th>
                    <th class="col-attachment">Attachment</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $rows = $transmittal->items->take(5);
                @endphp

                @forelse($rows as $index => $item)
                    <tr>
                        <td>{{ $item->item_no ?? ($index + 1) }}</td>
                        <td>{{ $item->particular ?? '' }}</td>
                        <td>{{ $item->unique_id ?? '' }}</td>
                        <td>{{ $item->qty ?? '' }}</td>
                        <td>{{ $item->description ?? '' }}</td>
                        <td>{{ $item->remarks ?? '' }}</td>
                        <td class="attachment-name">
                            {{ !empty($item->attachment_path) ? basename($item->attachment_path) : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td>1</td>
                        <td colspan="6"></td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="tm-footer-code">
            <div class="tm-footer-left">JKNC-TF-GS-V.1-2025</div>
            <div class="tm-footer-right">Page 1 of 1</div>
            <div class="tm-clear"></div>
        </div>

        <div class="tm-signatures">
            <div class="tm-sign-col">
                <div class="tm-sign-block">
                    <div class="tm-sign-label">Prepared by:</div>
                    <div class="tm-sign-line">{{ $transmittal->prepared_by_name ?? ' ' }}</div>
                    <div class="tm-sign-sub">
                        {{ $transmittal->prepared_at ? \Carbon\Carbon::parse($transmittal->prepared_at)->format('Y-m-d H:i:s') : '' }}
                    </div>
                </div>

                <div class="tm-sign-block">
                    <div class="tm-sign-label">Approved by:</div>
                    <div class="tm-sign-line">{{ $transmittal->approved_by_name ?? ' ' }}</div>
                    <div class="tm-sign-sub">{{ $transmittal->approved_position ?? '' }}</div>
                    <div class="tm-sign-sub">
                        {{ $transmittal->approved_at ? \Carbon\Carbon::parse($transmittal->approved_at)->format('Y-m-d H:i:s') : '' }}
                    </div>
                </div>

                <div class="tm-sign-block">
                    <div class="tm-sign-line">{{ $transmittal->document_custodian ?? ' ' }}</div>
                    <div class="tm-sign-sub">Document Custodian</div>
                </div>
            </div>

            <div class="tm-sign-col" style="float:right;">
                <div class="tm-sign-block">
                    <div class="tm-sign-label">Delivered by:</div>
                    <div class="tm-sign-line">{{ $transmittal->delivered_by ?? ' ' }}</div>
                </div>

                <div class="tm-sign-block">
                    <div class="tm-sign-label">Received by:</div>
                    <div class="tm-sign-line">{{ $transmittal->received_by ?? ' ' }}</div>
                </div>

                <div class="tm-sign-block">
                    <div class="tm-sign-label">Affiliated to / Company:</div>
                    <div class="tm-sign-line">{{ $transmittal->receiver_affiliation ?? ' ' }}</div>
                </div>

                <div class="tm-sign-block">
                    <div class="tm-sign-label">Date and Time:</div>
                    <div class="tm-sign-line">
                        {{ $transmittal->received_at ? \Carbon\Carbon::parse($transmittal->received_at)->format('Y-m-d H:i:s') : ' ' }}
                    </div>
                </div>
            </div>

            <div class="tm-clear"></div>
        </div>
    </div>
</body>
</html>

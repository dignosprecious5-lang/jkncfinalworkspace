<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transmittal Form</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 16mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #111827;
            margin: 0;
        }

        .page {
            min-height: 265mm;
            display: flex;
            flex-direction: column;
        }

        .title {
            text-align: center;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 18px;
        }

        .top-row {
            display: table;
            width: 100%;
            margin-bottom: 6px;
        }

        .cell {
            display: table-cell;
            vertical-align: bottom;
        }

        .label {
            font-weight: 700;
            width: 70px;
        }

        .line {
            border-bottom: 1px solid #9ca3af;
            min-height: 16px;
            padding-bottom: 2px;
        }

        .date-label {
            width: 42px;
            text-align: right;
            padding-right: 8px;
        }

        .date-line {
            width: 130px;
        }

        .meta {
            display: table;
            width: 100%;
            margin: 14px 0;
        }

        .meta-row {
            display: table-row;
        }

        .meta-cell {
            display: table-cell;
            width: 50%;
            padding: 3px 14px 3px 0;
        }

        .section-title {
            font-weight: 700;
            margin: 10px 0 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #9ca3af;
            padding: 5px;
            vertical-align: top;
            word-break: break-word;
        }

        th {
            font-weight: 700;
        }

        .signatures {
            display: table;
            width: 100%;
            margin-top: 24px;
        }

        .sign-col {
            display: table-cell;
            width: 50%;
            padding-right: 28px;
            vertical-align: top;
        }

        .sign-label {
            margin-bottom: 20px;
        }

        .sign-line {
            border-bottom: 1px solid #9ca3af;
            min-height: 18px;
            padding-bottom: 2px;
            font-weight: 700;
        }

        .sign-sub {
            margin-top: 4px;
        }

        .gap {
            margin-top: 28px;
        }

        .small-gap {
            margin-top: 20px;
        }

        .footer-spacer {
            flex: 1;
        }

        .code-footer {
            margin-top: auto;
            padding-top: 24px;
            display: table;
            width: 100%;
            font-size: 10px;
        }

        .code-footer div {
            display: table-cell;
        }

        .code-footer div:last-child {
            text-align: right;
        }
    </style>
</head>
<body>
@php
    $approvedByName = $transmittal->approved_by_name;

    if (!$approvedByName && $transmittal->approved_by) {
        $approvedByName = optional(\App\Models\User::find($transmittal->approved_by))->name;
    }
@endphp

<div class="page">
    <div class="title">Transmittal Form</div>

    <div class="top-row">
        <div class="cell label">Ref No</div>
        <div class="cell line">{{ $transmittal->transmittal_no }}</div>

        <div class="cell label date-label">Date</div>
        <div class="cell line date-line">{{ optional($transmittal->transmittal_date)->format('Y-m-d') }}</div>
    </div>

    <div class="top-row">
        <div class="cell label">Mode</div>
        <div class="cell line">{{ $transmittal->mode }}</div>
    </div>

    <div class="top-row">
        <div class="cell label">From</div>
        <div class="cell line">{{ $transmittal->from_value }}</div>
    </div>

    <div class="top-row">
        <div class="cell label">To</div>
        <div class="cell line">{{ $transmittal->to_value }}</div>
    </div>

    <div class="top-row">
        <div class="cell label">Address</div>
        <div class="cell line">{{ $transmittal->address }}</div>
    </div>

    <div class="meta">
        <div class="meta-row">
            <div class="meta-cell">
                <strong>Delivery Type:</strong> {{ $transmittal->delivery_summary ?: '—' }}
            </div>
            <div class="meta-cell">
                <strong>Actions:</strong> {{ $transmittal->actions_summary ?: '—' }}
            </div>
        </div>

        <div class="meta-row">
            <div class="meta-cell">
                <strong>Recipient Email:</strong> {{ $transmittal->recipient_email ?: '—' }}
            </div>
            <div class="meta-cell">
                <strong>Electronic Method:</strong> {{ $transmittal->electronic_method ?: '—' }}
            </div>
        </div>
    </div>

    <div class="section-title">List of Items</div>

    <table>
        <thead>
            <tr>
                <th style="width: 34px;">No</th>
                <th style="width: 100px;">Particular</th>
                <th style="width: 85px;">Unique ID</th>
                <th style="width: 40px;">Qty.</th>
                <th>Description</th>
                <th style="width: 85px;">Remarks</th>
                <th style="width: 90px;">Attachment</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transmittal->items as $item)
                <tr>
                    <td>{{ $item->item_no }}</td>
                    <td>{{ $item->particular }}</td>
                    <td>{{ $item->unique_id }}</td>
                    <td>{{ $item->qty }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->remarks }}</td>
                    <td>{{ $item->attachment_path ? basename($item->attachment_path) : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center; color:#6b7280;">
                        No items listed.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signatures">
        <div class="sign-col">
            <div class="sign-label">Prepared by:</div>
            <div class="sign-line">{{ $transmittal->prepared_by_name ?: ' ' }}</div>
            <div class="sign-sub">{{ optional($transmittal->prepared_at)->format('Y-m-d h:i A') }}</div>

            <div class="sign-label gap">Approved by:</div>
            <div class="sign-line">{{ $approvedByName ?: ' ' }}</div>

            <div class="sign-label small-gap">Operations Manager:</div>
            <div class="sign-line">{{ $transmittal->approved_position ?: ' ' }}</div>
        </div>

        <div class="sign-col">
            <div class="sign-label">Delivered by:</div>
            <div class="sign-line">{{ $transmittal->delivered_by ?: ' ' }}</div>

            <div class="sign-label gap">Received by:</div>
            <div class="sign-line">{{ $transmittal->received_by ?: ' ' }}</div>

            <div class="sign-label small-gap">Affiliated to / Company:</div>
            <div class="sign-line">{{ $transmittal->receiver_affiliation ?: ' ' }}</div>
        </div>
    </div>

    <div class="footer-spacer"></div>

    <div class="code-footer">
        <div>JKNC-TF-GS-V.1-2025</div>
        <div>Page 1 of 1</div>
    </div>
</div>
</body>
</html>
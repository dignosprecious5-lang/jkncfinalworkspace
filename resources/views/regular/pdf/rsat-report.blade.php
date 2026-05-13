@php
    $fmt = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('M d, Y') : '-';
    $reportRows = collect($report?->within_scope_items ?? []);
    $reportApproval = (array) ($report?->internal_approval ?? []);
    $filledRows = $reportRows->filter(fn ($item) => filled($item['service'] ?? null) || filled($item['activity_output'] ?? null))->values();
    $logoFile = public_path('images/imaglogo.png');
    $logoDataUri = null;

    if (is_file($logoFile)) {
        $logoMime = function_exists('mime_content_type') ? mime_content_type($logoFile) : 'image/png';
        $logoDataUri = 'data:'.$logoMime.';base64,'.base64_encode((string) file_get_contents($logoFile));
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RSAT Report {{ $report->report_number }}</title>
    <style>
        @page { size: A4 portrait; margin: 8mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; margin: 0; font-size: 8px; }
        .sheet { border: 1px solid #d8e1ee; background: #fff; }
        .form { border: 2px solid #1c4587; padding: 16px 18px 20px; }
        .head { display: table; width: 100%; }
        .head-cell { display: table-cell; vertical-align: top; }
        .head-cell.right { text-align: right; }
        .logo { height: 66px; }
        .title { font-family: "Times New Roman", serif; font-weight: 700; font-size: 20px; line-height: 1.03; color: #111827; text-transform: uppercase; }
        .form-code { margin-top: 3px; font-family: "Times New Roman", serif; font-size: 8px; color: #64748b; }
        .section-title { margin-top: 16px; background: #1c4587; padding: 7px 10px; text-align: center; font-family: "Times New Roman", serif; font-size: 10px; font-weight: 700; letter-spacing: 0.05em; color: #fff; text-transform: uppercase; }
        .meta-grid { width: 100%; border-collapse: separate; border-spacing: 0 6px; margin-top: 10px; }
        .meta-grid td { vertical-align: bottom; font-family: "Times New Roman", serif; font-size: 8px; }
        .meta-label { width: 108px; color: #64748b; }
        .line-value { border-bottom: 1px solid #111827; padding: 3px 0 4px; color: #111827; }
        .report-grid { width: 100%; border-collapse: separate; border-spacing: 10px 0; margin-top: 12px; }
        .report-box { border: 1px solid #dbe3f0; background: #f8fbff; padding: 10px; }
        .report-box span { display: block; font-size: 7px; font-weight: 700; text-transform: uppercase; color: #64748b; }
        .report-box strong { display: block; margin-top: 6px; font-size: 12px; }
        .matrix { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 14px; }
        .matrix th, .matrix td { border: 1px solid #111827; padding: 0; vertical-align: middle; }
        .matrix th { background: #1c4587; color: #fff; padding: 6px 4px; text-align: center; font-size: 7px; font-weight: 700; }
        .cell { min-height: 28px; padding: 6px 8px; font-size: 7.6px; color: #111827; }
        .cell.center { text-align: center; }
        .empty { padding: 10px; text-align: center; color: #64748b; font-size: 7.4px; }
        .signature { margin-top: 18px; text-align: center; font-family: "Times New Roman", serif; }
        .signature-line { width: 300px; margin: 0 auto; border-bottom: 1px solid #111827; min-height: 24px; line-height: 24px; font-size: 8px; }
        .signature-label { margin-top: 6px; font-style: italic; font-size: 7.8px; }
        .approval-grid { width: 100%; border-collapse: separate; border-spacing: 0 6px; margin-top: 8px; }
        .approval-grid td { vertical-align: bottom; font-family: "Times New Roman", serif; font-size: 8px; }
        .approval-label { width: 110px; color: #64748b; }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="form">
            <div class="head">
                <div class="head-cell">
                    @if ($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="John Kelly and Company" class="logo">
                    @endif
                </div>
                <div class="head-cell right">
                    <div class="title">Regular Service Activity<br>Tracker Report (RSAT Report)</div>
                    <div class="form-code">REG-F-003</div>
                </div>
            </div>

            <div class="section-title">Report Information</div>
            <table class="meta-grid">
                <tr>
                    <td class="meta-label">Report No.:</td><td class="line-value">{{ $report->report_number ?: '-' }}</td>
                    <td class="meta-label">Report Date:</td><td class="line-value">{{ $fmt($report->date_prepared) }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Client Name:</td><td class="line-value">{{ $contactName }}</td>
                    <td class="meta-label">Business Name:</td><td class="line-value">{{ $regular->business_name ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Condeal Ref No.:</td><td class="line-value">{{ $regular->deal?->deal_code ?: '-' }}</td>
                    <td class="meta-label">Services:</td><td class="line-value">{{ $regular->services ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Service Area:</td><td class="line-value">{{ $regular->service_area ?: '-' }}</td>
                    <td class="meta-label">Product:</td><td class="line-value">{{ $regular->products ?: '-' }}</td>
                </tr>
            </table>

            <table class="report-grid">
                <tr>
                    @foreach (['report_period' => 'Report Period', 'prepared_by' => 'Prepared By', 'reviewed_by' => 'Reviewed By'] as $field => $label)
                        <td class="report-box">
                            <span>{{ $label }}</span>
                            <strong>{{ $reportApproval[$field] ?? '-' }}</strong>
                        </td>
                    @endforeach
                </tr>
            </table>

            <table class="matrix">
                <thead>
                    <tr>
                        <th style="width: 8%;">Item #</th>
                        <th style="width: 20%;">Service</th>
                        <th style="width: 28%;">Activity / Output</th>
                        <th style="width: 18%;">Frequency</th>
                        <th style="width: 16%;">Reminder Lead Time</th>
                        <th style="width: 10%;">Deadline</th>
                        <th style="width: 10%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filledRows as $index => $item)
                        <tr>
                            <td><div class="cell center">{{ $index + 1 }}</div></td>
                            <td><div class="cell">{{ $item['service'] ?? '' }}</div></td>
                            <td><div class="cell">{{ $item['activity_output'] ?? '' }}</div></td>
                            <td><div class="cell">{{ $item['frequency'] ?? '' }}</div></td>
                            <td><div class="cell">{{ $item['reminder_lead_time'] ?? '' }}</div></td>
                            <td><div class="cell">{{ $item['deadline'] ?? '' }}</div></td>
                            <td><div class="cell center">{{ \Illuminate\Support\Str::of((string) ($item['status'] ?? 'open'))->replace('_', ' ')->title() }}</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty">No RSAT report rows recorded yet.</div></td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="signature">
                <div class="signature-line">{{ $report->client_confirmation_name ?: '-' }}</div>
                <div class="signature-label">Client Fullname &amp; Signature</div>
            </div>

            <div class="section-title">Internal Approval</div>
            <table class="approval-grid">
                <tr>
                    <td class="approval-label">Prepared By:</td><td class="line-value">{{ $reportApproval['prepared_by'] ?? '-' }}</td>
                    <td class="approval-label">Reviewed By:</td><td class="line-value">{{ $reportApproval['reviewed_by'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="approval-label">Sales &amp; Marketing:</td><td class="line-value">{{ $reportApproval['sales_marketing'] ?? '-' }}</td>
                    <td class="approval-label">Lead Consultant:</td><td class="line-value">{{ $reportApproval['lead_consultant'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="approval-label">Lead Associate Assigned:</td><td class="line-value">{{ $reportApproval['lead_associate_assigned'] ?? '-' }}</td>
                    <td class="approval-label">Record Custodian:</td><td class="line-value">{{ $reportApproval['record_custodian'] ?? '-' }}</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>

@php
    $formDate = optional($rsat?->form_date ?? $rsat?->created_at)->format('m/d/Y');
    $dateStarted = optional($rsat?->date_started)->format('m/d/Y');
    $dateCompleted = optional($rsat?->date_completed)->format('m/d/Y');
    $contactName = trim(collect([$regular->contact?->first_name, $regular->contact?->last_name])->filter()->implode(' ')) ?: ($regular->client_name ?: '-');
    $clearance = (array) ($rsatClearance ?? []);
    $requirements = collect($rsatRequirements ?? [])
        ->filter(fn ($item) => filled($item['purpose'] ?? null) || filled($item['requirement'] ?? null))
        ->values();
    $recordedDate = ! empty($clearance['date_recorded']) ? \Illuminate\Support\Carbon::parse($clearance['date_recorded'])->format('m/d/Y') : '';
    $signedDate = ! empty($clearance['date_signed']) ? \Illuminate\Support\Carbon::parse($clearance['date_signed'])->format('m/d/Y') : '';
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
    <title>RSAT Form</title>
    <style>
        @page { size: A4 portrait; margin: 5mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 8px; margin: 0; }
        .sheet { border: 1px solid #d7deea; background: #fff; min-height: 276mm; }
        .form-shell { border: 2px solid #1c4587; padding: 14px 16px 18px; min-height: 270mm; box-sizing: border-box; }
        .head { display: table; width: 100%; }
        .head-cell { display: table-cell; vertical-align: top; }
        .head-cell.right { text-align: right; }
        .logo { height: 64px; }
        .title { font-family: "Times New Roman", serif; font-weight: 700; font-size: 19px; line-height: 1.02; color: #111827; text-transform: uppercase; }
        .form-code { margin-top: 3px; font-family: "Times New Roman", serif; font-size: 8px; color: #64748b; }
        .meta { width: 100%; border-collapse: separate; border-spacing: 0 6px; margin-top: 10px; }
        .meta td { vertical-align: bottom; font-family: "Times New Roman", serif; font-size: 8px; }
        .meta-label { width: 96px; text-transform: uppercase; letter-spacing: 0.03em; color: #334155; }
        .meta-value { border-bottom: 1px solid #111827; padding: 3px 0 4px; color: #111827; }
        .client-row { width: 100%; border-collapse: separate; border-spacing: 0 6px; margin-top: 4px; }
        .client-row td { vertical-align: bottom; font-family: "Times New Roman", serif; font-size: 8px; }
        .section-title {
            margin-top: 16px;
            background: #1c4587;
            color: #fff;
            text-align: center;
            padding: 7px 10px;
            font-family: "Times New Roman", serif;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .matrix { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .matrix th, .matrix td { border: 1px solid #111827; padding: 0; vertical-align: middle; }
        .matrix th {
            background: #1c4587;
            color: #fff;
            padding: 5px 3px;
            text-align: center;
            font-size: 6.4px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        .matrix-cell { min-height: 28px; padding: 6px 6px; font-size: 7.2px; color: #111827; line-height: 1.25; }
        .matrix-cell.center { text-align: center; }
        .empty { padding: 10px; text-align: center; color: #64748b; font-size: 7.4px; }
        .signature { margin-top: 18px; text-align: center; font-family: "Times New Roman", serif; }
        .signature-line {
            width: 300px;
            margin: 0 auto;
            border-bottom: 1px solid #111827;
            min-height: 24px;
            line-height: 24px;
            font-size: 8px;
        }
        .signature-label { margin-top: 6px; font-style: italic; font-size: 7.8px; }
        .approval-grid, .footer-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 6px;
            margin-top: 8px;
        }
        .approval-grid td, .footer-grid td {
            vertical-align: bottom;
            font-family: "Times New Roman", serif;
            font-size: 8px;
        }
        .approval-label, .footer-label { width: 110px; color: #64748b; }
        .line-value { border-bottom: 1px solid #111827; padding: 3px 0 4px; color: #111827; }
        .record-grid { width: 100%; border-collapse: separate; border-spacing: 0 6px; margin-top: 8px; }
        .record-grid td { vertical-align: bottom; font-family: "Times New Roman", serif; font-size: 8px; }
        .record-box {
            border-bottom: 1px solid #111827;
            min-height: 22px;
            text-align: center;
            font-style: italic;
            color: #111827;
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="form-shell">
            <div class="head">
                <div class="head-cell">
                    @if ($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="John Kelly and Company" class="logo">
                    @endif
                </div>
                <div class="head-cell right">
                    <div class="title">REGULAR SERVICE<br>ACTIVITY TRACKER (RSAT)</div>
                    <div class="form-code">REG-F-001</div>
                </div>
            </div>

            <table class="meta">
                <tr>
                    <td class="meta-label">Client Name:</td>
                    <td class="meta-value">{{ $contactName }}</td>
                    <td class="meta-label">Date Created:</td>
                    <td class="meta-value">{{ $formDate }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Business Name:</td>
                    <td class="meta-value">{{ $regular->business_name ?: '' }}</td>
                    <td class="meta-label">Engagement Type:</td>
                    <td class="meta-value">{{ $regular->engagement_type ?: '' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Condeal Ref No.:</td>
                    <td class="meta-value">{{ $regular->deal?->deal_code ?: '' }}</td>
                    <td class="meta-label">Services:</td>
                    <td class="meta-value">{{ $regular->services ?: '' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Service Area:</td>
                    <td class="meta-value">{{ $regular->service_area ?: '' }}</td>
                    <td class="meta-label">Product:</td>
                    <td class="meta-value">{{ $regular->products ?: '' }}</td>
                </tr>
            </table>

            <table class="client-row">
                <tr>
                    <td class="meta-label" style="width: 70px;">BIF No.</td>
                    <td class="meta-value">{{ $regular->company?->latestBif?->bif_no ?? '' }}</td>
                    <td class="meta-label" style="width: 90px;">Date Started:</td>
                    <td class="meta-value">{{ $dateStarted }}</td>
                    <td class="meta-label" style="width: 100px;">Date Completed:</td>
                    <td class="meta-value">{{ $dateCompleted }}</td>
                </tr>
            </table>

            <table class="matrix" style="margin-top: 12px;">
                <thead>
                    <tr>
                        <th style="width: 5%;">Item #</th>
                        <th style="width: 18%;">Service</th>
                        <th style="width: 24%;">Activity / Output</th>
                        <th style="width: 14%;">Frequency</th>
                        <th style="width: 16%;">Reminder Lead Time</th>
                        <th style="width: 11%;">Deadline</th>
                        <th style="width: 12%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requirements as $index => $item)
                        <tr>
                            <td><div class="matrix-cell center">{{ $index + 1 }}</div></td>
                            <td><div class="matrix-cell">{{ $item['purpose'] ?? '' }}</div></td>
                            <td><div class="matrix-cell">{{ $item['requirement'] ?? '' }}</div></td>
                            <td><div class="matrix-cell">{{ $item['notes'] ?? '' }}</div></td>
                            <td><div class="matrix-cell">{{ $item['timeline'] ?? '' }}</div></td>
                            <td><div class="matrix-cell">{{ $item['submitted_to'] ?? '' }}</div></td>
                            <td><div class="matrix-cell center">{{ \Illuminate\Support\Str::of((string) ($item['status'] ?? 'open'))->replace('_', ' ')->title() }}</div></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"><div class="empty">No RSAT rows recorded.</div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="signature">
                <div class="signature-line">{{ $regular->client_name ?: $contactName }}</div>
                <div class="signature-label">Client Fullname &amp; Signature</div>
            </div>

            <div class="section-title">Internal Approval</div>

            <table class="approval-grid">
                <tr>
                    <td class="approval-label">Prepared By:</td>
                    <td class="line-value">{{ $clearance['assigned_team_lead'] ?? '' }}</td>
                    <td class="approval-label">Reviewed By:</td>
                    <td class="line-value">{{ $clearance['lead_consultant_confirmed'] ?? '' }}</td>
                </tr>
                <tr>
                    <td class="approval-label">Name:</td>
                    <td class="line-value"></td>
                    <td class="approval-label">Name:</td>
                    <td class="line-value"></td>
                </tr>
                <tr>
                    <td class="approval-label">Date:</td>
                    <td class="line-value"></td>
                    <td class="approval-label">Date:</td>
                    <td class="line-value"></td>
                </tr>
            </table>

            <table class="footer-grid">
                <tr>
                    <td class="footer-label">Referred By/Closed By:</td>
                    <td class="line-value">{{ $rsat?->rejection_reason ?? '' }}</td>
                    <td class="footer-label">Sales &amp; Marketing:</td>
                    <td class="line-value">{{ $clearance['sales_marketing'] ?? '' }}</td>
                </tr>
                <tr>
                    <td class="footer-label">Lead Consultant:</td>
                    <td class="line-value">{{ $regular->assigned_consultant ?? '' }}</td>
                    <td class="footer-label">Lead Associate Assigned:</td>
                    <td class="line-value">{{ $clearance['lead_associate_assigned'] ?? '' }}</td>
                </tr>
                <tr>
                    <td class="footer-label">Finance:</td>
                    <td class="line-value"></td>
                    <td class="footer-label">President:</td>
                    <td class="line-value"></td>
                </tr>
            </table>

            <table class="record-grid">
                <tr>
                    <td style="width: 42%;">Record Custodian ( Name and Signature )</td>
                    <td class="record-box" style="width: 28%;">{{ $clearance['record_custodian_name'] ?? '' }}</td>
                    <td class="footer-label" style="width: 12%;">Date Recorded:</td>
                    <td class="line-value" style="width: 18%;">{{ $recordedDate }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td class="footer-label">Date Signed:</td>
                    <td class="line-value">{{ $signedDate }}</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>

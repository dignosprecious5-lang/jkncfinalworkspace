@php
    $fmt = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('M d, Y') : '-';
    $reportRows = collect($report?->within_scope_items ?? []);
    $reportApproval = (array) ($report?->internal_approval ?? []);
    $filledRows = $reportRows->filter(fn ($item) => filled($item['service'] ?? null) || filled($item['activity_output'] ?? null))->values();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSAT Report Approval</title>
    <style>
        body {
            margin: 0;
            background:
                radial-gradient(circle at top left, rgba(13, 70, 140, 0.08), transparent 28%),
                linear-gradient(180deg, #f2f6fc 0%, #fbfcfe 26%, #fbfcfe 100%);
            font-family: Arial, Helvetica, sans-serif;
            color: #0f172a;
        }
        .page { max-width: 1320px; margin: 0 auto; padding: 40px 24px 56px; }
        .sheet { border: 1px solid #d8e1ee; background: rgba(255, 255, 255, 0.96); box-shadow: 0 16px 34px rgba(15, 23, 42, 0.05); }
        .rsat-form { border: 2px solid #1c4587; padding: 34px 42px 42px; }
        .rsat-header { display: grid; grid-template-columns: 220px minmax(0, 1fr); gap: 28px; align-items: start; }
        .rsat-logo-wrap { display: flex; align-items: flex-start; }
        .rsat-title-wrap { text-align: right; }
        .rsat-title { font-family: Georgia, "Times New Roman", serif; font-weight: 700; font-size: 2rem; line-height: 1.08; letter-spacing: 0.02em; color: #111827; text-align: right; }
        .rsat-form-code, .rsat-meta-label, .rsat-approval-label { color: #64748b; }
        .rsat-success { margin-top: 14px; padding: 14px 16px; font-size: 0.88rem; }
        .rsat-success { border: 1px solid #b7dfc5; background: #edf9f1; color: #166534; }
        .rsat-meta-grid, .rsat-approval-grid, .rsat-footer-grid { display: grid; gap: 18px 36px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .rsat-meta-item, .rsat-approval-pair, .rsat-footer-pair { display: grid; align-items: end; gap: 10px; font-family: Georgia, "Times New Roman", serif; font-size: 0.88rem; }
        .rsat-meta-item { grid-template-columns: 150px minmax(0, 1fr); }
        .rsat-approval-pair { grid-template-columns: 145px minmax(0, 1fr); }
        .rsat-footer-pair { grid-template-columns: 180px minmax(0, 1fr); }
        .rsat-line-value, .rsat-line-input { min-height: 34px; border: 0; border-bottom: 1px solid #111827; background: transparent; padding: 7px 0 6px; color: #111827; width: 100%; display: flex; align-items: flex-end; }
        .rsat-line-input:focus { outline: none; border-bottom-color: #1c4587; box-shadow: inset 0 -1px 0 #1c4587; }
        .rsat-client-row { display: grid; grid-template-columns: 220px repeat(3, minmax(0, 1fr)); gap: 18px; align-items: end; }
        .rsat-report-info-grid { margin-top: 22px; display: grid; gap: 18px 24px; grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .rsat-report-table-wrap { margin-top: 24px; overflow-x: auto; }
        .rsat-report-table { width: 100%; min-width: 980px; border-collapse: collapse; table-layout: fixed; font-family: Georgia, "Times New Roman", serif; }
        .rsat-report-table th, .rsat-report-table td { border: 1px solid #111827; padding: 0; vertical-align: middle; }
        .rsat-report-table th { background: #1c4587; color: #fff; padding: 6px 4px; text-align: center; font-size: 0.76rem; font-weight: 700; }
        .rsat-report-table td { min-height: 34px; padding: 8px 10px; font-size: 0.82rem; color: #111827; line-height: 1.35; }
        .rsat-report-table td.center { text-align: center; }
        .rsat-signature { margin-top: 34px; display: grid; gap: 8px; justify-items: center; }
        .rsat-signature-line { width: min(100%, 420px); border-bottom: 1px solid #111827; min-height: 36px; text-align: center; display: flex; align-items: flex-end; justify-content: center; padding-bottom: 4px; }
        .rsat-signature-label { font-family: Georgia, "Times New Roman", serif; font-style: italic; font-size: 0.92rem; color: #111827; }
        .rsat-section-title { margin-top: 36px; background: #1c4587; padding: 10px 16px; font-family: Georgia, "Times New Roman", serif; font-size: 1.15rem; font-weight: 700; letter-spacing: 0.05em; color: #fff; text-align: center; }
        .approval-body { border-left: 1px solid #111827; border-right: 1px solid #111827; border-bottom: 1px solid #111827; padding: 24px 18px 22px; background: #fff; }
        .field { margin-bottom: 22px; }
        .field:last-child { margin-bottom: 0; }
        .field label { display: block; margin-bottom: 8px; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #475569; }
        .input, .textarea, .file { width: 100%; border: 1px solid #cbd5e1; background: #fff; padding: 10px 12px; font-size: 0.92rem; color: #0f172a; box-sizing: border-box; }
        .textarea { min-height: 120px; resize: vertical; }
        .help { margin-top: 8px; font-size: 0.78rem; color: #64748b; }
        .error { margin-top: 6px; font-size: 0.82rem; color: #dc2626; }
        .checkbox { display: flex; gap: 10px; align-items: flex-start; font-size: 0.92rem; color: #334155; line-height: 1.6; }
        .checkbox input { margin-top: 4px; }
        .actions { margin-top: 18px; display: flex; justify-content: space-between; gap: 12px; }
        .button { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; border: 1px solid #1c4587; background: #1c4587; padding: 0 18px; font-size: 0.9rem; font-weight: 700; color: #fff; cursor: pointer; }
        @media (max-width: 900px) {
            .page { padding: 24px 14px 40px; }
            .rsat-form { padding: 24px 18px 28px; }
            .rsat-header,
            .rsat-meta-grid,
            .rsat-report-info-grid,
            .rsat-client-row { grid-template-columns: 1fr; }
            .rsat-title,
            .rsat-title-wrap { text-align: left; }
            .rsat-meta-item { grid-template-columns: 1fr; gap: 6px; }
        }
    </style>
</head>
<body>
    <div class="page">
        <section class="sheet">
            <div class="rsat-form">
                <div class="rsat-header">
                    <div class="rsat-logo-wrap"><img src="{{ asset('images/imaglogo.png') }}" alt="John Kelly and Company" class="h-24 w-auto object-contain" style="height:96px;width:auto;object-fit:contain;"></div>
                    <div class="rsat-title-wrap">
                        <div class="rsat-title">REGULAR SERVICE ACTIVITY<br>TRACKER REPORT (RSAT REPORT)</div>
                        <div class="rsat-form-code">REG-F-003</div>
                    </div>
                </div>

                @if (session('success'))
                    <div class="rsat-success">{{ session('success') }}</div>
                @endif

                <div class="rsat-section-title">REPORT INFORMATION</div>
                <div class="rsat-report-info-grid">
                    <div class="rsat-meta-item" style="grid-template-columns: 120px minmax(0, 1fr);"><div class="rsat-meta-label">Report No.:</div><div class="rsat-line-value">{{ $report->report_number ?: '-' }}</div></div>
                    <div class="rsat-meta-item" style="grid-template-columns: 135px minmax(0, 1fr);"><div class="rsat-meta-label">Report Date:</div><div class="rsat-line-value">{{ $fmt($report->date_prepared) }}</div></div>
                    <div class="rsat-meta-item" style="grid-template-columns: 140px minmax(0, 1fr);"><div class="rsat-meta-label">Report Period:</div><div class="rsat-line-value">{{ $reportApproval['report_period'] ?? '-' }}</div></div>
                </div>

                <div class="mt-8 rsat-meta-grid">
                    <div class="rsat-meta-item"><div class="rsat-meta-label">Client Name:</div><div class="rsat-line-value">{{ $contactName }}</div></div>
                    <div class="rsat-meta-item"><div class="rsat-meta-label">Business Name:</div><div class="rsat-line-value">{{ $regular->business_name ?: '-' }}</div></div>
                    <div class="rsat-meta-item"><div class="rsat-meta-label">Condeal Ref No.:</div><div class="rsat-line-value">{{ $regular->deal?->deal_code ?: '-' }}</div></div>
                    <div class="rsat-meta-item"><div class="rsat-meta-label">Services:</div><div class="rsat-line-value">{{ $regular->services ?: '-' }}</div></div>
                    <div class="rsat-meta-item"><div class="rsat-meta-label">Service Area:</div><div class="rsat-line-value">{{ $regular->service_area ?: '-' }}</div></div>
                    <div class="rsat-meta-item"><div class="rsat-meta-label">Product:</div><div class="rsat-line-value">{{ $regular->products ?: '-' }}</div></div>
                </div>

                <div class="rsat-report-table-wrap">
                    <table class="rsat-report-table">
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
                                    <td class="center">{{ $index + 1 }}</td>
                                    <td>{{ $item['service'] ?? '' }}</td>
                                    <td>{{ $item['activity_output'] ?? '' }}</td>
                                    <td>{{ $item['frequency'] ?? '' }}</td>
                                    <td>{{ $item['reminder_lead_time'] ?? '' }}</td>
                                    <td>{{ $item['deadline'] ?? '' }}</td>
                                    <td>{{ \Illuminate\Support\Str::of((string) ($item['status'] ?? 'open'))->replace('_', ' ')->title() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="center" style="padding:14px 12px;color:#64748b;font-family:Arial,Helvetica,sans-serif;">No RSAT report rows recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="rsat-signature">
                    <div class="rsat-signature-line">{{ old('client_approval_name', $report->client_approved_name ?: $contactName) }}</div>
                    <div class="rsat-signature-label">Client Fullname &amp; Signature</div>
                </div>

                <div class="rsat-section-title">APPROVAL ACTION</div>
                <div class="approval-body">
                    <form method="POST" action="{{ $clientFormAction }}" enctype="multipart/form-data">
                        @csrf
                        <div class="field">
                            <label for="client_approval_name">Printed Name</label>
                            <input id="client_approval_name" type="text" name="client_approval_name" value="{{ old('client_approval_name', $report->client_approved_name ?: $contactName) }}" class="input">
                            @error('client_approval_name')<div class="error">{{ $message }}</div>@enderror
                        </div>
                        <div class="field">
                            <label for="client_response_notes">Notes / Comments</label>
                            <textarea id="client_response_notes" name="client_response_notes" rows="4" class="textarea">{{ old('client_response_notes', $report->client_response_notes) }}</textarea>
                            @error('client_response_notes')<div class="error">{{ $message }}</div>@enderror
                        </div>
                        <div class="field">
                            <label for="client_attachment">Attachment</label>
                            <input id="client_attachment" type="file" name="client_attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="file">
                            @if ($report->client_attachment_path)
                                <div class="help">An attachment has already been uploaded for this report.</div>
                            @endif
                            @error('client_attachment')<div class="error">{{ $message }}</div>@enderror
                        </div>
                        <div class="field">
                            <label class="checkbox">
                                <input type="checkbox" name="client_approval" value="1">
                                <span>I confirm that I have reviewed this RSAT Report and approve it on behalf of the client.</span>
                            </label>
                            @error('client_approval')<div class="error">{{ $message }}</div>@enderror
                        </div>
                        <div class="actions">
                            <a href="{{ route('regular.report.client.download', ['token' => request()->route('token')]) }}" class="button" style="background:#fff;color:#1c4587;">Download PDF</a>
                            <button type="submit" class="button">Approve Report</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</body>
</html>

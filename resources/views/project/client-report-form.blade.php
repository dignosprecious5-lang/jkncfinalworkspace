<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOW Report Approval</title>
    <style>
        body {
            margin: 0;
            background:
                radial-gradient(circle at top left, rgba(13, 70, 140, 0.08), transparent 28%),
                linear-gradient(180deg, #f2f6fc 0%, #fbfcfe 26%, #fbfcfe 100%);
            font-family: Arial, Helvetica, sans-serif;
            color: #0f172a;
        }
        .page {
            max-width: 1180px;
            margin: 0 auto;
            padding: 32px 18px 48px;
        }
        .sheet {
            border: 1px solid #d7deea;
            background: #fff;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.08);
        }
        .form-shell {
            border: 2px solid #1c4587;
            padding: 28px 30px 34px;
            background: #fff;
        }
        .head {
            display: grid;
            grid-template-columns: 180px minmax(0, 1fr);
            gap: 18px;
            align-items: start;
        }
        .logo {
            width: 170px;
            height: auto;
            object-fit: contain;
        }
        .title {
            text-align: right;
            font-family: Georgia, "Times New Roman", serif;
        }
        .title h1 {
            margin: 0;
            font-size: 2rem;
            line-height: 1.04;
            font-weight: 700;
            color: #111827;
            letter-spacing: 0.02em;
        }
        .success {
            margin-top: 14px;
            border: 1px solid #b7dfc5;
            background: #edf9f1;
            padding: 14px 16px;
            font-size: 0.88rem;
            color: #166534;
        }
        .meta {
            margin-top: 18px;
            display: grid;
            gap: 8px 28px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .meta-row {
            display: grid;
            grid-template-columns: 190px minmax(0, 1fr);
            align-items: end;
            gap: 10px;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 0.9rem;
            color: #111827;
        }
        .meta-label {
            color: #334155;
        }
        .line {
            min-height: 32px;
            border: 0;
            border-bottom: 1px solid #111827;
            background: transparent;
            padding: 4px 0 5px;
            color: #111827;
            width: 100%;
            display: inline-flex;
            align-items: end;
        }
        .section {
            margin-top: 18px;
        }
        .section-title {
            background: #1c4587;
            border: 2px solid #1c4587;
            padding: 9px 16px;
            text-align: center;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: #fff;
        }
        .table-wrap {
            overflow-x: auto;
        }
        .sow-table {
            width: 100%;
            min-width: 1080px;
            border-collapse: collapse;
            table-layout: fixed;
            font-family: Georgia, "Times New Roman", serif;
        }
        .sow-table th,
        .sow-table td {
            border: 1px solid #111827;
            padding: 0;
            vertical-align: middle;
        }
        .sow-table th {
            background: #fff;
            padding: 8px 6px;
            text-align: center;
            font-size: 0.78rem;
            font-weight: 400;
            color: #111827;
        }
        .cell {
            min-height: 34px;
            padding: 6px 8px;
            font-size: 0.82rem;
            color: #111827;
            display: flex;
            align-items: center;
            background: #fff;
        }
        .cell.center {
            justify-content: center;
            text-align: center;
        }
        .empty {
            padding: 14px 12px;
            text-align: center;
            color: #64748b;
            font-size: 0.82rem;
        }
        .summary-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            padding: 18px;
            border-left: 1px solid #dbe3f0;
            border-right: 1px solid #dbe3f0;
            border-bottom: 1px solid #dbe3f0;
            background: #fff;
        }
        .summary-box {
            border: 1px solid #dbe3f0;
            background: #f8fbff;
            padding: 12px;
        }
        .summary-box span {
            display: block;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
        }
        .summary-box strong {
            display: block;
            margin-top: 8px;
            font-size: 1.4rem;
            color: #0f172a;
        }
        .signature-box {
            border: 1px solid #111827;
            border-top: 0;
            min-height: 66px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 0.8rem;
            font-style: italic;
            color: #111827;
            text-align: center;
        }
        .signature-name {
            min-width: min(100%, 420px);
            border-bottom: 1px solid #111827;
            padding: 0 12px 6px;
            font-size: 0.95rem;
            font-style: normal;
            text-align: center;
            margin-bottom: 8px;
        }
        .approval-body {
            border-left: 1px solid #111827;
            border-right: 1px solid #111827;
            border-bottom: 1px solid #111827;
            padding: 18px;
            background: #fff;
        }
        .field {
            margin-bottom: 16px;
        }
        .field:last-child {
            margin-bottom: 0;
        }
        .field label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #475569;
        }
        .input,
        .textarea,
        .file {
            width: 100%;
            border: 1px solid #cbd5e1;
            background: #fff;
            padding: 10px 12px;
            font-size: 0.92rem;
            color: #0f172a;
            box-sizing: border-box;
        }
        .textarea {
            min-height: 110px;
            resize: vertical;
        }
        .help {
            margin-top: 8px;
            font-size: 0.78rem;
            color: #64748b;
        }
        .error {
            margin-top: 6px;
            font-size: 0.82rem;
            color: #dc2626;
        }
        .checkbox {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            font-size: 0.92rem;
            color: #334155;
            line-height: 1.6;
        }
        .checkbox input {
            margin-top: 4px;
        }
        .actions {
            margin-top: 18px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            border: 1px solid #1c4587;
            background: #1c4587;
            padding: 0 18px;
            font-size: 0.9rem;
            font-weight: 700;
            color: #fff;
            cursor: pointer;
        }
        @media (max-width: 900px) {
            .head,
            .meta,
            .summary-grid {
                grid-template-columns: minmax(0, 1fr);
            }
            .title {
                text-align: left;
            }
            .meta-row {
                grid-template-columns: minmax(0, 1fr);
            }
            .form-shell {
                padding: 20px 18px 24px;
            }
        }
    </style>
</head>
<body>
    @php
        $fmt = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('M d, Y') : '-';
        $within = collect($report->within_scope_items ?? [])->filter(fn ($row) => filled($row['main_task_description'] ?? null))->values();
        $out = collect($report->out_of_scope_items ?? [])->filter(fn ($row) => filled($row['main_task_description'] ?? null))->values();
        $summary = (array) ($report->status_summary ?? []);
    @endphp

    <div class="page">
        <section class="sheet">
            <div class="form-shell">
                <div class="head">
                    <img src="{{ asset('images/imaglogo.png') }}" alt="John Kelly and Company" class="logo">
                    <div class="title">
                        <h1>SCOPE OF WORK REPORT</h1>
                        <p>PROJ-F-004</p>
                    </div>
                </div>

                @if (session('success'))
                    <div class="success">{{ session('success') }}</div>
                @endif

                <div class="meta">
                    <div class="meta-row">
                        <span class="meta-label">Condeal Reference No.:</span>
                        <span class="line">{{ $project->deal?->deal_code ?: '-' }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Report No.:</span>
                        <span class="line">{{ $report->report_number ?: '-' }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Business Name:</span>
                        <span class="line">{{ $project->business_name ?: '-' }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Version No.:</span>
                        <span class="line">{{ $report->version_number ?: '-' }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Client Name:</span>
                        <span class="line">{{ $contactName ?: '-' }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Date of Reporting:</span>
                        <span class="line">{{ $fmt($report->date_prepared) }}</span>
                    </div>
                </div>

                @foreach ([
                    'WITHIN SCOPE' => $within,
                    'OUT OF SCOPE' => $out,
                ] as $label => $rows)
                    <div class="section">
                        <div class="section-title">{{ $label }}</div>
                        <div class="table-wrap">
                            <table class="sow-table">
                                <thead>
                                    <tr>
                                        <th style="width: 23%;">MAIN TASK</th>
                                        <th style="width: 22%;">SUB TASK</th>
                                        <th style="width: 13%;">RESPONSIBLE</th>
                                        <th style="width: 8%;">DURATION</th>
                                        <th style="width: 10%;">START DATE</th>
                                        <th style="width: 10%;">END DATE</th>
                                        <th style="width: 7%;">STATUS</th>
                                        <th style="width: 7%;">REMARKS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rows as $row)
                                        <tr>
                                            <td><div class="cell">{{ $row['main_task_description'] ?? '' }}</div></td>
                                            <td><div class="cell">{{ $row['sub_task_description'] ?? '' }}</div></td>
                                            <td><div class="cell">{{ $row['responsible'] ?? '' }}</div></td>
                                            <td><div class="cell center">{{ $row['duration'] ?? '' }}</div></td>
                                            <td><div class="cell center">{{ $fmt($row['start_date'] ?? null) }}</div></td>
                                            <td><div class="cell center">{{ $fmt($row['end_date'] ?? null) }}</div></td>
                                            <td><div class="cell center">{{ \Illuminate\Support\Str::of((string) ($row['status'] ?? ''))->replace('_', ' ')->title() }}</div></td>
                                            <td><div class="cell">{{ $row['remarks'] ?? '' }}</div></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8"><div class="empty">No {{ strtolower($label) }} items recorded.</div></td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                <div class="section">
                    <div class="section-title">PROJECT STATUS SUMMARY</div>
                    <div class="summary-grid">
                        @foreach (['total_main_tasks' => 'Total Main Tasks', 'open' => 'Open', 'in_progress' => 'In Progress', 'delayed' => 'Delayed', 'completed' => 'Completed', 'on_hold' => 'On Hold'] as $field => $label)
                            <div class="summary-box">
                                <span>{{ $label }}</span>
                                <strong>{{ (int) ($summary[$field] ?? 0) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="section">
                    <div class="section-title">CLIENT CONFIRMATION</div>
                    <div class="signature-box">
                        <div class="signature-name">{{ old('client_approval_name', $report->client_approved_name ?: $contactName) }}</div>
                        <div>Client Fullname &amp; Signature</div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title">APPROVAL ACTION</div>
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
                                    <span>I confirm that I have reviewed this Scope of Work Report and approve it on behalf of the client.</span>
                                </label>
                                @error('client_approval')<div class="error">{{ $message }}</div>@enderror
                            </div>

                            <div class="actions">
                                <a href="{{ route('project.report.client.download', ['token' => request()->route('token')]) }}" class="button" style="background:#fff;color:#1c4587;">Download PDF</a>
                                <button type="submit" class="button">Approve Report</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
</body>
</html>

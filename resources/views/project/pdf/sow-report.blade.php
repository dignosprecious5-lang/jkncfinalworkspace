@php
    $fmt = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('M d, Y') : '-';
    $within = collect($report->within_scope_items ?? [])->filter(fn ($row) => filled($row['main_task_description'] ?? null))->values();
    $out = collect($report->out_of_scope_items ?? [])->filter(fn ($row) => filled($row['main_task_description'] ?? null))->values();
    $approval = (array) ($report->internal_approval ?? []);
    $summary = (array) ($report->status_summary ?? []);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SOW Report {{ $report->report_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #0f172a; margin: 24px; }
        .shell { border: 1px solid #cbd5e1; background: #fff; }
        .topbar { height: 8px; background: #102d79; }
        .header { padding: 18px 22px; border-bottom: 1px solid #dbe3f0; background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); }
        .brand { display: flex; justify-content: space-between; gap: 18px; align-items: flex-start; }
        .brand img { height: 46px; }
        .title { text-align: right; font-family: "Times New Roman", Georgia, serif; }
        .title h1 { margin: 0; font-size: 30px; text-transform: uppercase; }
        .title p { margin: 4px 0 0; font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b; }
        .meta { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-top: 16px; }
        .meta-box { border: 1px solid #dbe3f0; padding: 8px 10px; min-height: 58px; }
        .meta-box span { display: block; font-size: 10px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; }
        .meta-box strong { display: block; margin-top: 8px; font-size: 14px; }
        .section { margin: 18px 22px 0; border: 1px solid #dbe3f0; }
        .section-title { background: #102d79; color: #fff; padding: 10px 14px; font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
        .section-body { padding: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #dbe3f0; padding: 8px; font-size: 12px; vertical-align: top; }
        th { background: #eef4ff; text-transform: uppercase; letter-spacing: 0.05em; font-size: 10px; color: #334155; }
        .summary { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 10px; }
        .summary-box { border: 1px solid #dbe3f0; background: #f8fbff; padding: 10px; }
        .summary-box span { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; color: #64748b; }
        .summary-box strong { display: block; margin-top: 6px; font-size: 20px; }
        .footer { margin: 18px 22px 22px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .footer-box { border: 1px solid #dbe3f0; padding: 12px; min-height: 86px; }
        .footer-box span { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; color: #64748b; }
        .footer-box strong { display: block; margin-top: 8px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="shell">
        <div class="topbar"></div>
        <div class="header">
            <div class="brand">
                <img src="{{ asset('images/imaglogo.png') }}" alt="John Kelly and Company">
                <div class="title">
                    <h1>Scope Of Work Report</h1>
                    <p>PROJ-F-004</p>
                </div>
            </div>
            <div class="meta">
                <div class="meta-box"><span>Report No.</span><strong>{{ $report->report_number ?: '-' }}</strong></div>
                <div class="meta-box"><span>Date of Reporting</span><strong>{{ $fmt($report->date_prepared) }}</strong></div>
                <div class="meta-box"><span>Client</span><strong>{{ $contactName }}</strong></div>
                <div class="meta-box"><span>Business</span><strong>{{ $project->business_name ?: '-' }}</strong></div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Scope Snapshot</div>
            <div class="section-body">
                <table>
                    <thead>
                        <tr>
                            <th>Main Task</th>
                            <th>Sub Task</th>
                            <th>Responsible</th>
                            <th>Duration</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($within as $row)
                            <tr>
                                <td>{{ $row['main_task_description'] ?? '' }}</td>
                                <td>{{ $row['sub_task_description'] ?? '' }}</td>
                                <td>{{ $row['responsible'] ?? '' }}</td>
                                <td>{{ $row['duration'] ?? '' }}</td>
                                <td>{{ $fmt($row['start_date'] ?? null) }}</td>
                                <td>{{ $fmt($row['end_date'] ?? null) }}</td>
                                <td>{{ $row['status'] ?? '' }}</td>
                                <td>{{ $row['remarks'] ?? '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8">No within-scope items recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Status Summary</div>
            <div class="section-body">
                <div class="summary">
                    @foreach (['total_main_tasks' => 'Total Tasks', 'open' => 'Open', 'in_progress' => 'In Progress', 'delayed' => 'Delayed', 'completed' => 'Completed', 'on_hold' => 'On Hold'] as $field => $label)
                        <div class="summary-box">
                            <span>{{ $label }}</span>
                            <strong>{{ (int) ($summary[$field] ?? 0) }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-box">
                <span>Prepared By</span>
                <strong>{{ $approval['prepared_by'] ?? '-' }}</strong>
            </div>
            <div class="footer-box">
                <span>Client Confirmation</span>
                <strong>{{ $report->client_confirmation_name ?: '-' }}</strong>
            </div>
        </div>
    </div>
</body>
</html>

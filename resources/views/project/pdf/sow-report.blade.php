@php
    $fmt = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('M d, Y') : '-';
    $within = collect($report->within_scope_items ?? [])->filter(fn ($row) => filled($row['main_task_description'] ?? null))->values();
    $out = collect($report->out_of_scope_items ?? [])->filter(fn ($row) => filled($row['main_task_description'] ?? null))->values();
    $summary = (array) ($report->status_summary ?? []);
    
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
    <title>SOW Report {{ $report->report_number }}</title>
    <style>
        @page { size: A4 portrait; margin: 8mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; margin: 0; font-size: 8px; }
        .sheet {
            border: 1px solid #163b7a;
            min-height: 279mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }
        .topbar { height: 6px; background: #163b7a; }
        .header { padding: 12px 14px; border-bottom: 1px solid #cbd5e1; }
        .brand { display: table; width: 100%; }
        .brand-cell { display: table-cell; vertical-align: top; }
        .brand-cell.right { text-align: right; }
        .brand img { height: 44px; }
        .title { font-family: "Times New Roman", serif; font-size: 22px; font-weight: 700; line-height: 1.05; text-transform: uppercase; }
        .subtitle { font-size: 8.5px; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b; margin-top: 2px; }
        .meta { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .meta td { border: 1px solid #dbe3f0; padding: 6px 7px; vertical-align: top; width: 33.33%; }
        .meta-label { display: block; font-size: 7px; font-weight: 700; text-transform: uppercase; color: #64748b; }
        .meta-value { display: block; margin-top: 4px; font-size: 8.5px; font-weight: 700; color: #111827; }
        .section-title { background: #163b7a; color: #fff; text-align: center; padding: 6px; font-family: "Times New Roman", serif; font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
        .matrix { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .matrix th, .matrix td { border: 1px solid #111827; padding: 5px 4px; vertical-align: top; }
        .matrix th { background: #eef4ff; font-size: 7px; text-transform: uppercase; }
        .matrix td { font-size: 7.6px; }
        .summary-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .summary-grid td {
            border: 1px solid #dbe3f0;
            padding: 6px 7px;
            width: 16.66%;
        }
        .summary-label { display: block; font-size: 6.5px; text-transform: uppercase; color: #64748b; font-weight: 700; }
        .summary-value { display: block; margin-top: 4px; font-size: 14px; font-weight: 700; }
        .within-space {
            border-left: 1px solid #111827;
            border-right: 1px solid #111827;
            border-bottom: 1px solid #111827;
            background: #fff;
        }
        .section-gap { height: 10px; }
        .signature-box {
            border: 1px solid #111827;
            border-top: 0;
            min-height: 66px;
            padding: 10px 16px;
            text-align: center;
            font-family: "Times New Roman", serif;
        }
        .signature-name {
            font-size: 9px;
            border-bottom: 1px solid #111827;
            display: inline-block;
            min-width: 240px;
            padding: 0 12px 6px;
            font-weight: 700;
        }
        .signature-label {
            margin-top: 8px;
            font-size: 8px;
            font-style: italic;
            color: #111827;
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="topbar"></div>
        <div class="header">
            <div class="brand">
                <div class="brand-cell">
                    @if ($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="John Kelly and Company">
                    @endif
                </div>
                <div class="brand-cell right">
                    <div class="title">Scope Of Work Report</div>
                    <div class="subtitle">PROJ-F-004</div>
                </div>
            </div>

            <table class="meta">
                <tr>
                    <td><span class="meta-label">Condeal Reference No.</span><span class="meta-value">{{ $project->deal?->deal_code ?: '-' }}</span></td>
                    <td><span class="meta-label">Report No.</span><span class="meta-value">{{ $report->report_number ?: '-' }}</span></td>
                    <td><span class="meta-label">Business Name</span><span class="meta-value">{{ $project->business_name ?: '-' }}</span></td>
                </tr>
                <tr>
                    <td><span class="meta-label">Version No.</span><span class="meta-value">{{ $report->version_number ?: '-' }}</span></td>
                    <td><span class="meta-label">Client Name</span><span class="meta-value">{{ $contactName ?: '-' }}</span></td>
                    <td><span class="meta-label">Date of Reporting</span><span class="meta-value">{{ $fmt($report->date_prepared) }}</span></td>
                </tr>
            </table>
        </div>

        @php
            $sections = [
                'WITHIN SCOPE' => $within,
                'OUT OF SCOPE' => $out,
            ];
        @endphp
        @foreach ($sections as $label => $rows)
            <div class="section-title">{{ $label }}</div>
            <table class="matrix">
                <thead>
                    <tr>
                        <th style="width: 23%;">Main Task</th>
                        <th style="width: 22%;">Sub Task</th>
                        <th style="width: 13%;">Responsible</th>
                        <th style="width: 8%;">Duration</th>
                        <th style="width: 10%;">Start Date</th>
                        <th style="width: 10%;">End Date</th>
                        <th style="width: 7%;">Status</th>
                        <th style="width: 7%;">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row['main_task_description'] ?? '' }}</td>
                            <td>{{ $row['sub_task_description'] ?? '' }}</td>
                            <td>{{ $row['responsible'] ?? '' }}</td>
                            <td style="text-align: center;">{{ $row['duration'] ?? '' }}</td>
                            <td style="text-align: center;">{{ $fmt($row['start_date'] ?? null) }}</td>
                            <td style="text-align: center;">{{ $fmt($row['end_date'] ?? null) }}</td>
                            <td style="text-align: center;">{{ \Illuminate\Support\Str::of((string) ($row['status'] ?? ''))->replace('_', ' ')->title() }}</td>
                            <td>{{ $row['remarks'] ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; color: #64748b;">No {{ strtolower($label) }} items recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="within-space" style="margin-bottom: 10px;"></div>
        @endforeach

        <div class="section-title">PROJECT STATUS SUMMARY</div>
        <table class="summary-grid">
            <tr>
                @foreach (['total_main_tasks' => 'Total Main Tasks', 'open' => 'Open', 'in_progress' => 'In Progress', 'delayed' => 'Delayed', 'completed' => 'Completed', 'on_hold' => 'On Hold'] as $field => $label)
                    <td>
                        <span class="summary-label">{{ $label }}</span>
                        <span class="summary-value">{{ (int) ($summary[$field] ?? 0) }}</span>
                    </td>
                @endforeach
            </tr>
        </table>

        <div class="section-gap"></div>

        <div class="section-title">CLIENT CONFIRMATION</div>
        <div class="signature-box">
            <div class="signature-name">{{ $report->client_approved_name ?: $contactName }}</div>
            <div class="signature-label">Client Fullname &amp; Signature</div>
        </div>
    </div>
</body>
</html>

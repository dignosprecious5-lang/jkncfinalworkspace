@php
    $fmt = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('m/d/Y') : '-';
    $approval = (array) ($sow?->internal_approval ?? []);
    $clientConfirmationName = $sow?->client_confirmation_name ?: ($project->client_name ?: $contactName);
    $withinRows = $within->values();
    $outRows = $out->values();
    $statusSummary = [
        'total_main_tasks' => $within->concat($out)->count(),
        'open' => $within->concat($out)->where('status', 'open')->count(),
        'in_progress' => $within->concat($out)->where('status', 'in_progress')->count(),
        'delayed' => $within->concat($out)->where('status', 'delayed')->count(),
        'completed' => $within->concat($out)->where('status', 'completed')->count(),
        'on_hold' => $within->concat($out)->where('status', 'on_hold')->count(),
    ];
    $statusLabel = function ($value): string {
        return match (strtolower(trim((string) $value))) {
            'open', 'pending' => 'Open',
            'in_progress' => 'In Progress',
            'delayed' => 'Delayed',
            'completed' => 'Completed',
            'on_hold' => 'On Hold',
            default => trim((string) $value) !== '' ? (string) \Illuminate\Support\Str::of((string) $value)->replace('_', ' ')->title() : '',
        };
    };
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
    <title>Scope Of Work {{ $project->project_code }}</title>
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
        .meta td { border: 1px solid #dbe3f0; padding: 6px 7px; vertical-align: top; width: 25%; }
        .meta-label { display: block; font-size: 7px; font-weight: 700; text-transform: uppercase; color: #64748b; }
        .meta-value { display: block; margin-top: 4px; font-size: 8.5px; font-weight: 700; color: #111827; }
        .section-title { background: #163b7a; color: #fff; text-align: center; padding: 6px; font-family: "Times New Roman", serif; font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
        .matrix { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .matrix th, .matrix td { border: 1px solid #111827; padding: 5px 4px; vertical-align: top; }
        .matrix th { background: #eef4ff; font-size: 7px; text-transform: uppercase; }
        .matrix td { font-size: 7.6px; }
        .summary { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .summary td { border: 1px solid #dbe3f0; padding: 6px 7px; width: 20%; }
        .summary-label { display: block; font-size: 6.5px; text-transform: uppercase; color: #64748b; font-weight: 700; }
        .summary-value { display: block; margin-top: 4px; font-size: 8.5px; font-weight: 700; }
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
        .within-space {
            min-height: 18mm;
            border-left: 1px solid #dbe3f0;
            border-right: 1px solid #dbe3f0;
            border-bottom: 1px solid #dbe3f0;
            background: #fff;
        }
        .out-space {
            min-height: 12mm;
            border-left: 1px solid #dbe3f0;
            border-right: 1px solid #dbe3f0;
            border-bottom: 1px solid #dbe3f0;
            background: #fff;
        }
        .section-gap { height: 10px; }
        .matrix tbody tr td { min-height: 20px; }
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
        }
        .signature-label {
            margin-top: 8px;
            font-size: 8px;
            font-style: italic;
            color: #111827;
        }
        .approval-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .approval-grid td {
            border: 1px solid #111827;
            padding: 8px 10px;
            width: 50%;
            font-family: "Times New Roman", serif;
            font-size: 8px;
            vertical-align: bottom;
        }
        .approval-label {
            display: inline-block;
            min-width: 110px;
            font-style: italic;
            color: #334155;
        }
        .approval-value {
            display: inline-block;
            min-height: 14px;
            min-width: 120px;
            border-bottom: 1px solid #111827;
            padding: 0 4px 2px;
            color: #111827;
        }
        .record-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .record-grid td {
            border: 1px solid #111827;
            padding: 8px 10px;
            font-family: "Times New Roman", serif;
            font-size: 8px;
            vertical-align: bottom;
        }
        .record-box {
            width: 60%;
            text-align: center;
            font-style: italic;
            vertical-align: middle;
            min-height: 44px;
        }
        .record-date-row {
            margin-bottom: 8px;
        }
        .record-date-row:last-child {
            margin-bottom: 0;
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
                    <div class="title">Scope of Work</div>
                    <div class="subtitle">PROJ-F-002</div>
                </div>
            </div>

            <table class="meta">
                <tr>
                    <td><span class="meta-label">Condeal Ref No.</span><span class="meta-value">{{ $project->deal?->deal_code ?: '-' }}</span></td>
                    <td><span class="meta-label">Project Code</span><span class="meta-value">{{ $project->project_code ?: '-' }}</span></td>
                    <td><span class="meta-label">Client</span><span class="meta-value">{{ $contactName }}</span></td>
                    <td><span class="meta-label">Business</span><span class="meta-value">{{ $project->business_name ?: '-' }}</span></td>
                </tr>
                <tr>
                    <td><span class="meta-label">Date Prepared</span><span class="meta-value">{{ $fmt($sow?->date_prepared) }}</span></td>
                    <td><span class="meta-label">Version</span><span class="meta-value">{{ $sow?->version_number ?: '-' }}</span></td>
                    <td><span class="meta-label">Project Start</span><span class="meta-value">{{ $fmt($project->planned_start_date) }}</span></td>
                    <td><span class="meta-label">Target Completion</span><span class="meta-value">{{ $fmt($project->target_completion_date) }}</span></td>
                </tr>
            </table>
        </div>

        <div class="section-title">Within Scope</div>
        <table class="matrix">
            <thead>
                <tr>
                    <th style="width: 19%;">Main Task</th>
                    <th style="width: 19%;">Sub Task</th>
                    <th style="width: 10%;">Responsible</th>
                    <th style="width: 7%;">Duration</th>
                    <th style="width: 10%;">Start</th>
                    <th style="width: 10%;">End</th>
                    <th style="width: 9%;">Status</th>
                    <th style="width: 16%;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($withinRows as $row)
                    <tr>
                        <td>{{ $row['main_task_description'] ?? '' }}</td>
                        <td>{{ $row['sub_task_description'] ?? '' }}</td>
                        <td>{{ $row['responsible'] ?? '' }}</td>
                        <td>{{ $row['duration'] ?? '' }}</td>
                        <td>{{ $fmt($row['start_date'] ?? null) }}</td>
                        <td>{{ $fmt($row['end_date'] ?? null) }}</td>
                        <td>{{ $statusLabel($row['status'] ?? '') }}</td>
                        <td>{{ $row['remarks'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8">No within-scope items recorded.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="within-space"></div>

        <div class="section-title">Out Of Scope</div>
        <table class="matrix">
            <thead>
                <tr>
                    <th style="width: 19%;">Main Task</th>
                    <th style="width: 19%;">Sub Task</th>
                    <th style="width: 10%;">Responsible</th>
                    <th style="width: 7%;">Duration</th>
                    <th style="width: 10%;">Start</th>
                    <th style="width: 10%;">End</th>
                    <th style="width: 9%;">Status</th>
                    <th style="width: 16%;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($outRows as $row)
                    <tr>
                        <td>{{ $row['main_task_description'] ?? '' }}</td>
                        <td>{{ $row['sub_task_description'] ?? '' }}</td>
                        <td>{{ $row['responsible'] ?? '' }}</td>
                        <td>{{ $row['duration'] ?? '' }}</td>
                        <td>{{ $fmt($row['start_date'] ?? null) }}</td>
                        <td>{{ $fmt($row['end_date'] ?? null) }}</td>
                        <td>{{ $statusLabel($row['status'] ?? '') }}</td>
                        <td>{{ $row['remarks'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8">No out-of-scope items recorded.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="out-space"></div>

        <div class="section-gap"></div>

        <div class="section-title">Project Status Summary</div>
            <table class="summary-grid">
                <tr>
                    @foreach (['total_main_tasks' => 'Total Tasks', 'open' => 'Open', 'in_progress' => 'In Progress', 'delayed' => 'Delayed', 'completed' => 'Completed', 'on_hold' => 'On Hold'] as $field => $label)
                        <td>
                            <span class="summary-label">{{ $label }}</span>
                            <span class="summary-value">{{ $statusSummary[$field] ?? 0 }}</span>
                        </td>
                    @endforeach
                </tr>
            </table>

            <table class="summary">
                <tr>
                    <td><span class="summary-label">Approval Status</span><span class="summary-value">{{ \Illuminate\Support\Str::of((string) ($sow?->approval_status ?? 'draft'))->replace('_', ' ')->title() }}</span></td>
                    <td><span class="summary-label">NTP Status</span><span class="summary-value">{{ \Illuminate\Support\Str::of((string) ($sow?->ntp_status ?? 'pending'))->replace('_', ' ')->title() }}</span></td>
                    <td><span class="summary-label">Service Area</span><span class="summary-value">{{ $project->service_area ?: '-' }}</span></td>
                    <td><span class="summary-label">Services</span><span class="summary-value">{{ $project->services ?: '-' }}</span></td>
                    <td><span class="summary-label">Products</span><span class="summary-value">{{ $project->products ?: '-' }}</span></td>
                </tr>
            </table>

            <div class="signature-box">
                <div class="signature-name">{{ $clientConfirmationName ?: '-' }}</div>
                <div class="signature-label">Client Fullname &amp; Signature</div>
            </div>

            <div class="section-gap"></div>

            <div class="section-title">Internal Approval</div>
            <table class="approval-grid">
                <tr>
                    <td><span class="approval-label">Prepared By:</span><span class="approval-value">{{ $approval['prepared_by'] ?? '-' }}</span></td>
                    <td><span class="approval-label">Reviewed By:</span><span class="approval-value">{{ $approval['reviewed_by'] ?? '-' }}</span></td>
                </tr>
                <tr>
                    <td><span class="approval-label">Referred By/Closed By:</span><span class="approval-value">{{ $approval['referred_by_closed_by'] ?? '-' }}</span></td>
                    <td><span class="approval-label">Sales &amp; Marketing:</span><span class="approval-value">{{ $approval['sales_marketing'] ?? '-' }}</span></td>
                </tr>
                <tr>
                    <td><span class="approval-label">Lead Consultant:</span><span class="approval-value">{{ $approval['lead_consultant'] ?? '-' }}</span></td>
                    <td><span class="approval-label">Lead Associate Assigned:</span><span class="approval-value">{{ $approval['lead_associate_assigned'] ?? '-' }}</span></td>
                </tr>
                <tr>
                    <td><span class="approval-label">Finance:</span><span class="approval-value">{{ $approval['finance'] ?? '-' }}</span></td>
                    <td><span class="approval-label">President:</span><span class="approval-value">{{ $approval['president'] ?? '-' }}</span></td>
                </tr>
            </table>

            <table class="record-grid">
                <tr>
                    <td class="record-box">Record Custodian ( Name and Signature )</td>
                    <td>
                        <div class="record-date-row">
                            <span class="approval-label">Date Recorded :</span>
                            <span class="approval-value">{{ $fmt($approval['date_recorded'] ?? null) }}</span>
                        </div>
                        <div class="record-date-row">
                            <span class="approval-label">Date Signed :</span>
                            <span class="approval-value">{{ $fmt($approval['date_signed'] ?? null) }}</span>
                        </div>
                    </td>
                </tr>
            </table>
    </div>
</body>
</html>

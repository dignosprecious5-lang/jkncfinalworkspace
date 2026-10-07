@php
    // SOW Report Data Preparation
    $projectRef = $project->project_code ?: ('PROJ-' . date('Y') . '-' . str_pad($project->id, 3, '0', STR_PAD_LEFT));
    $workOrderNo = 'PROJ-WO-' . date('Y') . '-' . str_pad($project->id, 3, '0', STR_PAD_LEFT);
    $epaNo = 'EPA-' . date('Y') . '-' . str_pad($project->id, 3, '0', STR_PAD_LEFT);
    $serviceMemo = 'SM-' . date('Y') . '-' . str_pad($project->id, 3, '0', STR_PAD_LEFT);
    $startRef = 'START-' . date('Y') . '-' . str_pad($project->id, 3, '0', STR_PAD_LEFT);
    $dealRef = $project->deal?->deal_code ?: ('CONDEAL-' . date('Y') . '-' . str_pad($project->id, 3, '0', STR_PAD_LEFT));
    
    $clientName = $contactName ?: ($project->client_name ?: ($project->contact?->full_name ?: 'May Flor D. Dabatos'));
    $businessName = $project->business_name ?: ($project->company?->company_name ?: 'X10 REAL ESTATE CORPORATION');
    $serviceTitle = $project->project_title ?: 'Transfer of Share From Dany and Ronald to X10';
    $serviceArea = $project->service_area ?: 'Corporate Services';
    $engagementType = $project->engagement_type ?: 'Project';
    
    $targetStart = $project->planned_start_date ? \Carbon\Carbon::parse($project->planned_start_date)->format('M d, Y') : 'Aug 17, 2026';
    $targetEnd = $project->target_completion_date ? \Carbon\Carbon::parse($project->target_completion_date)->format('M d, Y') : 'Sep 18, 2026';
    
    $ntpNoDisplay = $ntpRecord?->reference_no ?: '—';
    $isNtpApproved = (bool)($ntpRecord?->client_approved_at || $project->status === 'Completed');

    // Parse tasks for Within Scope
    $rawWithin = collect($sowWithin ?? [])->filter(fn($x) => filled(data_get($x, 'main_task_description') ?: data_get($x, 'sub_task_description')));
    
    if ($rawWithin->isEmpty()) {
        $rawWithin = collect([
            [
                'main_task' => 'Share Transfer Documentation',
                'sub_task' => 'Review existing corporate and share ownership records',
                'responsibility' => 'Responsible',
                'assigned' => 'Rubeca Potayre',
                'status' => 'Done',
                'updates' => 0,
            ],
            [
                'main_task' => 'Share Transfer Documentation',
                'sub_task' => 'Verify shares to be transferred by Dany and Ronald',
                'responsibility' => 'Responsible',
                'assigned' => 'Rubeca Potayre',
                'status' => 'Done',
                'updates' => 0,
            ],
            [
                'main_task' => 'Share Transfer Documentation',
                'sub_task' => 'Prepare applicable share transfer documents',
                'responsibility' => 'Responsible',
                'assigned' => 'Rubeca Potayre',
                'status' => 'In Progress',
                'updates' => 0,
            ],
            [
                'main_task' => 'Share Transfer Documentation',
                'sub_task' => 'Coordinate documentary requirements with transferors',
                'responsibility' => 'Responsible',
                'assigned' => 'Rubeca Potayre',
                'status' => 'On Hold',
                'updates' => 0,
            ],
            [
                'main_task' => 'Share Transfer Documentation',
                'sub_task' => 'Facilitate execution and signing of transfer documents',
                'responsibility' => 'Responsible',
                'assigned' => 'Rubeca Potayre',
                'status' => 'In Progress',
                'updates' => 0,
            ],
            [
                'main_task' => 'BIR Share Transfer Processing',
                'sub_task' => 'Prepare and organize applicable BIR requirements',
                'responsibility' => 'Responsible',
                'assigned' => 'Rubeca Potayre',
                'status' => 'In Progress',
                'updates' => 0,
            ],
            [
                'main_task' => 'BIR Share Transfer Processing',
                'sub_task' => 'Prepare ONETT / documentary stamp requirements',
                'responsibility' => 'Responsible',
                'assigned' => 'John Kelly Abalde',
                'status' => 'In Progress',
                'updates' => 0,
            ],
            [
                'main_task' => 'BIR Share Transfer Processing',
                'sub_task' => 'Submit and monitor BIR share transfer processing',
                'responsibility' => 'Responsible',
                'assigned' => 'Rubeca Potayre',
                'status' => 'In Progress',
                'updates' => 0,
            ],
        ]);
    } else {
        $rawWithin = $rawWithin->map(function($item, $idx) use ($project) {
            $arr = is_array($item) ? $item : (array)$item;
            $st = !empty($arr['status']) ? $arr['status'] : 'In Progress';
            if (in_array(strtolower($st), ['completed', 'done'])) $st = 'Done';
            elseif (in_array(strtolower($st), ['on hold', 'pending', 'pending client'])) $st = 'On Hold';
            else $st = 'In Progress';
            
            return [
                'main_task' => !empty($arr['main_task_description']) ? $arr['main_task_description'] : 'General Scope Task',
                'sub_task' => !empty($arr['sub_task_description']) ? $arr['sub_task_description'] : ($arr['task'] ?? 'Scope Activity'),
                'responsibility' => !empty($arr['responsibility']) ? $arr['responsibility'] : 'Responsible',
                'assigned' => !empty($arr['responsible']) ? $arr['responsible'] : (!empty($arr['assignee']) ? $arr['assignee'] : ($project->assigned_consultant ?: 'Rubeca Potayre')),
                'status' => $st,
                'updates' => (int)($arr['updates'] ?? 0),
            ];
        });
    }

    // Group within scope by workstream
    $withinWorkstreams = $rawWithin->groupBy('main_task');
    $withinMainCount = $withinWorkstreams->count();
    $withinChildCount = $rawWithin->count();
    $withinDoneCount = $rawWithin->where('status', 'Done')->count();
    $withinProgressCount = $rawWithin->where('status', 'In Progress')->count();
    $withinHoldCount = $rawWithin->where('status', 'On Hold')->count();

    // Parse Out of Scope tasks
    $rawOut = collect($sowOut ?? [])->filter(fn($x) => filled(data_get($x, 'main_task_description') ?: data_get($x, 'sub_task_description')));
    $outWorkstreams = $rawOut->groupBy(fn($x) => data_get($x, 'main_task_description') ?: 'Out of Scope Stream');
    $outMainCount = $outWorkstreams->isEmpty() ? 0 : $outWorkstreams->count();
    $outChildCount = $rawOut->count();
    $outDoneCount = $rawOut->filter(fn($x) => in_array(strtolower(data_get($x, 'status', '')), ['done', 'completed']))->count();
    $outProgressCount = $rawOut->filter(fn($x) => strtolower(data_get($x, 'status', '')) === 'in progress')->count();
    $outHoldCount = $rawOut->filter(fn($x) => in_array(strtolower(data_get($x, 'status', '')), ['on hold', 'pending']))->count();

    // Narratives
    $defaultNarrative = (array) data_get($project->metadata ?? [], 'sow_report_narrative', []);
    $narrativeIssues = $defaultNarrative['issues'] ?? 'Client execution copies were received in batches.';
    $narrativeRecommendations = $defaultNarrative['recommendations'] ?? 'Maintain updated corporate records with the closing package.';
    $narrativeWay = $defaultNarrative['summary_way_forward'] ?? 'Complete remaining processing and corporate record updates, then transmit the final package and issue the COC.';
@endphp

<style>
/* SOW Report Scoped Design System - 100% Desktop View */
.sow-report-container {
    display: grid;
    grid-template-columns: 280px minmax(0, 1fr);
    gap: 20px;
    align-items: start;
    font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: #1e293b;
    width: 100%;
    box-sizing: border-box;
}

.sow-report-container > * {
    min-width: 0;
}

@media (max-width: 1200px) {
    .sow-report-container {
        grid-template-columns: 260px minmax(0, 1fr);
        gap: 16px;
    }
}

@media (max-width: 1024px) {
    .sow-report-container {
        grid-template-columns: 1fr;
    }
    .sow-sidecard {
        position: static !important;
        top: auto !important;
    }
}

/* Sidebar: STAGE MANAGEMENT */
.sow-sidecard {
    background: #ffffff;
    border: 1px solid #dce4f2;
    border-radius: 14px;
    padding: 18px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    position: sticky;
    top: 20px;
    z-index: 10;
    box-sizing: border-box;
}

.sow-side-title {
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 0.06em;
    color: #1e3a8a;
    text-transform: uppercase;
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
}

.sow-side-section {
    margin-bottom: 20px;
}

.sow-side-section:last-child {
    margin-bottom: 0;
}

.sow-side-section h4 {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.05em;
    color: #64748b;
    text-transform: uppercase;
    margin: 0 0 8px 0;
}

.sow-status-card {
    background: #ffffff;
    border: 1px solid #dce4f2;
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 13px;
    font-weight: 800;
    color: #1e293b;
    display: flex;
    align-items: center;
    min-height: 44px;
    box-sizing: border-box;
}

.sow-handling-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f4f8fd;
    border: 1px solid #dce4f2;
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 12px;
    gap: 12px;
}

.sow-handling-card span {
    font-size: 11px;
    color: #475569;
    font-weight: 700;
    line-height: 1.35;
}

.sow-handling-card strong {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 22px;
    font-weight: 800;
    color: #1e3a8a;
    white-space: nowrap;
    letter-spacing: 0.05em;
}

.sow-key-value-list {
    display: grid;
    gap: 6px;
    font-size: 11px;
    margin: 0;
    padding: 0 2px;
}

.sow-key-value-list > div {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sow-key-value-list dt {
    color: #64748b;
    font-weight: 600;
    margin: 0;
}

.sow-key-value-list dd {
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.sow-key-value-list dd.mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.sow-controls-stack {
    display: grid;
    gap: 8px;
}

.sow-btn-primary {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 42px;
    border-radius: 12px;
    background: #1e3a8a;
    color: #ffffff;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: background-color 0.15s ease;
    box-sizing: border-box;
}

.sow-btn-primary:hover {
    background: #172554;
    color: #ffffff;
}

.sow-btn-secondary {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 42px;
    border-radius: 12px;
    background: #ffffff;
    color: #1e293b;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid #dce4f0;
    cursor: pointer;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.sow-btn-secondary:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.sow-sidebar-note {
    background: transparent;
    padding: 4px 2px 0;
    font-size: 10px;
    color: #94a3b8;
    line-height: 1.45;
}

/* RIGHT DOCUMENT: RSAT REPORT Card */
.sow-document-card {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    overflow: hidden;
    min-width: 0;
    max-width: 100%;
    width: 100%;
    box-sizing: border-box;
}

.sow-doc-topbar {
    display: none;
}

.sow-doc-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 24px 28px 18px;
    background: #ffffff;
}

.sow-doc-brand {
    font-family: Georgia, serif;
    font-size: 22px;
    font-weight: 700;
    color: #111827;
    line-height: 1.15;
}

.sow-doc-brand small {
    display: block;
    font-family: Inter, sans-serif;
    font-size: 11px;
    color: #64748b;
    font-weight: 500;
    margin-top: 4px;
}

.sow-doc-title-block {
    text-align: right;
}

.sow-doc-title-block h1 {
    margin: 0;
    font-family: Georgia, serif;
    font-size: 24px;
    font-weight: 800;
    color: #102d79;
    letter-spacing: 0.05em;
    line-height: 1.1;
}

.sow-doc-title-block small {
    display: block;
    font-size: 11px;
    color: #64748b;
    font-weight: 500;
    margin-top: 4px;
}

.sow-doc-divider {
    height: 3px;
    background: #102d79;
    width: calc(100% - 56px);
    margin: 0 28px;
}

/* Project Information Section */
.sow-info-heading {
    padding: 18px 28px 10px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.sow-info-heading strong {
    font-family: Georgia, serif;
    font-size: 16px;
    font-weight: 700;
    color: #102d79;
}

.sow-info-heading span {
    font-size: 11px;
    color: #64748b;
}

.sow-info-grid {
    display: grid;
    grid-template-columns: 200px 1fr 200px 1fr;
    font-size: 11px;
    margin: 0 28px 24px;
    width: calc(100% - 56px);
    border: 1px solid #e2e8f0;
    box-sizing: border-box;
}

@media (max-width: 900px) {
    .sow-info-grid {
        grid-template-columns: 140px 1fr;
        margin: 0 16px 20px;
        width: calc(100% - 32px);
    }
}

.sow-info-cell-lbl {
    background: #ffffff;
    color: #475569;
    font-weight: 600;
    padding: 9px 14px;
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}

.sow-info-cell-val {
    background: #ffffff;
    color: #0f172a;
    font-weight: 700;
    padding: 9px 14px;
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}

/* Document Body & Tables */
.sow-doc-body {
    padding: 0 28px 28px;
    width: 100%;
    box-sizing: border-box;
}

@media (max-width: 900px) {
    .sow-doc-body {
        padding: 0 16px 20px;
    }
}

.sow-scope-banner {
    background: #102d79;
    color: #ffffff;
    font-family: Georgia, serif;
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-align: center;
    padding: 10px 16px;
    border-radius: 0;
    margin-top: 8px;
    width: 100%;
    box-sizing: border-box;
}

.sow-scope-banner.out-scope {
    margin-top: 24px;
}

.sow-table-wrap {
    overflow-x: auto;
    border: 1px solid #e2e8f0;
    border-top: 0;
    width: 100%;
    box-sizing: border-box;
}

.sow-table {
    width: 100%;
    min-width: 880px;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 11px;
}

.sow-table th {
    padding: 10px 12px;
    background: #ffffff;
    color: #475569;
    border: 1px solid #e2e8f0;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    text-align: left;
}

.sow-table td {
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    color: #334155;
    vertical-align: middle;
    line-height: 1.4;
}

.sow-table td:last-child {
    border-right: none;
}

.sow-table tr.main-task td {
    background: #ffffff;
    font-weight: 600;
    color: #0f172a;
}

.sow-table tr.child-task td {
    background: #ffffff;
}

.sow-update-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 5px 12px;
    border: 1px solid #bfdbfe;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 10.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}

.sow-update-pill:hover {
    background: #dbeafe;
    border-color: #93c5fd;
}

.sow-status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 600;
    white-space: nowrap;
}

.sow-status-badge.done {
    background: #dcfce7;
    color: #166534;
}

.sow-status-badge.in-progress {
    background: #f3e8ff;
    color: #6b21a8;
}

.sow-status-badge.on-hold {
    background: #fef3c7;
    color: #92400e;
}

/* SOW Report Summary */
.sow-summary-title {
    margin: 22px 0 6px;
    font-size: 11.5px;
    font-weight: 900;
    color: #1e3a8a;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.sow-summary-sublabel {
    margin: 10px 0 6px;
    font-size: 10.5px;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.sow-summary-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 12px;
}

@media (max-width: 820px) {
    .sow-summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

.sow-summary-card {
    padding: 12px 14px;
    border: 1px solid #dce4f2;
    border-radius: 10px;
    background: linear-gradient(145deg, #ffffff, #f6f8fc);
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.02);
}

.sow-summary-card span {
    display: block;
    color: #8791a4;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 0.45px;
    text-transform: uppercase;
}

.sow-summary-card strong {
    display: block;
    margin-top: 4px;
    color: #1e3a8a;
    font-size: 20px;
    font-weight: 800;
    line-height: 1.1;
}

/* Final Report Narrative */
.sow-narrative-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-top: 10px;
}

@media (max-width: 820px) {
    .sow-narrative-grid {
        grid-template-columns: 1fr;
    }
}

.sow-narrative-field label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #475569;
    margin-bottom: 6px;
}

.sow-narrative-field textarea {
    width: 100%;
    min-height: 90px;
    border: 1px solid #dce4f2;
    border-radius: 10px;
    padding: 10px 12px;
    font-size: 11.5px;
    font-family: inherit;
    color: #1e293b;
    line-height: 1.5;
    background: #ffffff;
    resize: vertical;
    box-sizing: border-box;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.sow-narrative-field textarea:focus {
    outline: none;
    border-color: #1e3a8a;
    box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.08);
}

/* Client Updates Modal */
.sow-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(17, 28, 52, 0.58);
    backdrop-filter: blur(2px);
}

.sow-modal-overlay.open {
    display: flex;
}

.sow-modal-dialog {
    width: min(780px, 95vw);
    max-height: 85vh;
    border-radius: 14px;
    background: #ffffff;
    box-shadow: 0 24px 60px rgba(15, 26, 50, 0.28);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid #dce4f2;
}

.sow-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    background: #fafbfc;
}

.sow-modal-header h3 {
    margin: 0;
    font-size: 14px;
    font-weight: 800;
    color: #1e3a8a;
}

.sow-modal-header p {
    margin: 2px 0 0;
    font-size: 11px;
    color: #64748b;
}

.sow-modal-close-btn {
    border: 1px solid #dce4f0;
    background: #ffffff;
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
    padding: 6px 14px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.sow-modal-close-btn:hover {
    background: #f1f5f9;
    color: #1e293b;
}

.sow-modal-content {
    padding: 20px;
    overflow-y: auto;
    display: grid;
    gap: 12px;
}

.sow-report-empty-state {
    text-align: center;
    padding: 30px 16px;
    color: #94a3b8;
    font-style: italic;
    font-size: 12px;
}

.sow-client-update-entry {
    border: 1px solid #dce4f2;
    border-radius: 10px;
    padding: 14px;
    background: #f8fafc;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 14px;
    align-items: start;
}

.sow-client-update-entry h5 {
    margin: 0 0 6px;
    font-size: 12px;
    font-weight: 800;
    color: #1e3a8a;
}

.sow-delivery-proof {
    display: flex;
    flex-wrap: wrap;
    gap: 6px 12px;
    font-size: 10px;
    color: #64748b;
}

.sow-delivery-proof .status-pill {
    color: #166534;
    font-weight: 800;
}

@media print {
    body {
        background: #ffffff !important;
        padding: 0 !important;
    }
    header, aside, .topbar, .rail, .breadcrumb, .workspace-head, .lifecycle, .sow-sidecard, .sow-modal-overlay, #app-navbar {
        display: none !important;
    }
    .sow-report-container {
        display: block !important;
        grid-template-columns: 1fr !important;
    }
    .sow-document-card {
        border: none !important;
        box-shadow: none !important;
        max-width: 100% !important;
        width: 100% !important;
    }
}
</style>

<div class="sow-report-container">
    <!-- LEFT SIDEBAR: STAGE MANAGEMENT -->
    <aside class="sow-sidecard">
        <div class="sow-side-title">STAGE MANAGEMENT</div>

        <!-- STAGE STATUS -->
        <div class="sow-side-section">
            <h4>STAGE STATUS</h4>
            <div class="sow-status-card">
                Receiving Execution Updates
            </div>
        </div>

        <!-- STAGE HANDLING -->
        <div class="sow-side-section">
            <h4>STAGE HANDLING</h4>
            <div class="sow-handling-card">
                <span>Total Stage<br>Handling Time</span>
                <strong id="sowLiveHandlingTimer">00:00:00</strong>
            </div>
            <dl class="sow-key-value-list">
                <div><dt>In Progress</dt><dd class="mono" id="sowLiveProgressTimer">00:00:00</dd></div>
                <div><dt>On Hold</dt><dd class="mono">00:00:00</dd></div>
                <div><dt>Waiting</dt><dd class="mono">00:00:00</dd></div>
                <div><dt>Responsible</dt><dd>Unassigned</dd></div>
                <div><dt>Waiting On</dt><dd>Completion of Execution tasks</dd></div>
                <div><dt>Started</dt><dd>—</dd></div>
                <div><dt>Completed</dt><dd>—</dd></div>
            </dl>
        </div>

        <!-- REPORT RECORD -->
        <div class="sow-side-section">
            <h4>REPORT RECORD</h4>
            <dl class="sow-key-value-list">
                <div><dt>Reports Issued</dt><dd>{{ $project->sowReports()->count() }}</dd></div>
                <div><dt>Latest Electronic Report</dt><dd>{{ $project->sowReports()->latest()->first()?->created_at?->format('M d, Y') ?: 'Not sent' }}</dd></div>
                <div><dt>Uploaded Copy</dt><dd>None</dd></div>
            </dl>
        </div>

        <!-- STAGE CONTROLS -->
        <div class="sow-side-section">
            <h4>STAGE CONTROLS</h4>
            <div class="sow-controls-stack">
                <a href="{{ route('project.show', ['project' => $project, 'tab' => 'execution']) }}" class="sow-btn-primary">
                    Open Execution
                </a>
                <button type="button" class="sow-btn-secondary" onclick="printOfficialSowReport()">
                    Print
                </button>
                <button type="button" class="sow-btn-secondary" onclick="downloadOfficialSowReport()">
                    Download
                </button>
                <div class="sow-sidebar-note">
                    Complete every Execution task before completing the SOW Report.
                </div>
            </div>
        </div>
    </aside>

    <!-- RIGHT CONTENT: SOW REPORT DOCUMENT -->
    <main class="sow-document-card">
        <!-- Document Header -->
        <header class="sow-doc-header">
            <div class="sow-doc-brand">
                John Kelly<br>&amp; Company
                <small>Operational Consulting &amp; Advisory</small>
            </div>
            <div class="sow-doc-title-block">
                <h1>SOW REPORT</h1>
                <small>Live execution reporting record</small>
            </div>
        </header>

        <div class="sow-doc-divider"></div>

        <!-- Project Information -->
        <section>
            <div class="sow-info-heading">
                <strong>Project Information</strong>
                <span>Auto-filled from the Deal, START, and issued Service Memo.</span>
            </div>

            <div class="sow-info-grid">
                <div class="sow-info-cell-lbl">NTP No. (All Notices to Proceed)</div>
                <div class="sow-info-cell-val">{{ $ntpNoDisplay }}</div>
                <div class="sow-info-cell-lbl">Engagement Proposal Agreement (EPA) No.</div>
                <div class="sow-info-cell-val">{{ $epaNo }}</div>

                <div class="sow-info-cell-lbl">Work Order No.</div>
                <div class="sow-info-cell-val">{{ $workOrderNo }}</div>
                <div class="sow-info-cell-lbl">Project Ref No.</div>
                <div class="sow-info-cell-val">{{ $projectRef }}</div>

                <div class="sow-info-cell-lbl">Source Service Memo</div>
                <div class="sow-info-cell-val">{{ $serviceMemo }}</div>
                <div class="sow-info-cell-lbl">Source START</div>
                <div class="sow-info-cell-val">{{ $startRef }}</div>

                <div class="sow-info-cell-lbl">Source Deal</div>
                <div class="sow-info-cell-val">{{ $dealRef }}</div>
                <div class="sow-info-cell-lbl">Client</div>
                <div class="sow-info-cell-val">{{ $clientName }}</div>

                <div class="sow-info-cell-lbl">Business / Company</div>
                <div class="sow-info-cell-val">{{ $businessName }}</div>
                <div class="sow-info-cell-lbl">Service / Project</div>
                <div class="sow-info-cell-val">{{ $serviceTitle }}</div>

                <div class="sow-info-cell-lbl">Service Area</div>
                <div class="sow-info-cell-val">{{ $serviceArea }}</div>
                <div class="sow-info-cell-lbl">Engagement Type</div>
                <div class="sow-info-cell-val">{{ $engagementType }}</div>

                <div class="sow-info-cell-lbl">Target Start Date</div>
                <div class="sow-info-cell-val">{{ $targetStart }}</div>
                <div class="sow-info-cell-lbl">Target Project End Date</div>
                <div class="sow-info-cell-val">{{ $targetEnd }}</div>
            </div>
        </section>

        <!-- Document Body -->
        <section class="sow-doc-body">
            <!-- WITHIN SCOPE TABLE -->
            <div class="sow-scope-banner">WITHIN SCOPE — EXECUTION STATUS &amp; CLIENT UPDATES</div>
            <div class="sow-table-wrap">
                <table class="sow-table">
                    <thead>
                        <tr>
                            <th style="width: 5%">ITEM</th>
                            <th style="width: 19%">MAIN TASK DESCRIPTION</th>
                            <th style="width: 25%">SUB TASK DESCRIPTION</th>
                            <th style="width: 13%">RESPONSIBILITY</th>
                            <th style="width: 15%">ASSIGNED PERSON</th>
                            <th style="width: 12%">UPDATES SENT TO CLIENT</th>
                            <th style="width: 11%">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $workstreamIndex = 1; @endphp
                        @foreach ($withinWorkstreams as $workstreamTitle => $tasks)
                            {{-- Workstream Parent Row --}}
                            @php
                                $streamStatuses = $tasks->pluck('status');
                                $streamStatus = 'In Progress';
                                if ($streamStatuses->count() > 0 && $streamStatuses->every(fn($s) => $s === 'Done')) {
                                    $streamStatus = 'Done';
                                } elseif ($streamStatuses->contains('On Hold')) {
                                    $streamStatus = 'On Hold';
                                }
                                $streamUpdatesCount = $tasks->sum('updates');
                            @endphp
                            <tr class="main-task">
                                <td style="text-align: center; font-weight: 700;">{{ $workstreamIndex }}</td>
                                <td><strong>{{ $workstreamTitle }}</strong></td>
                                <td style="color: #64748b; font-style: italic;">Main RSAT task / workstream</td>
                                <td><span style="color: #102d79; font-weight: 700;">Responsible</span></td>
                                <td>Project Manager</td>
                                <td>
                                    <button type="button" class="sow-update-pill" onclick="openSowClientUpdates('{{ addslashes($workstreamTitle) }}', {{ $streamUpdatesCount }})">
                                        {{ $streamUpdatesCount }} updates
                                    </button>
                                </td>
                                <td>
                                    <span class="sow-status-badge {{ Str::slug($streamStatus) }}">
                                        {{ $streamStatus }}
                                    </span>
                                </td>
                            </tr>

                            {{-- Workstream Child Task Rows --}}
                            @foreach ($tasks as $taskIndex => $task)
                                @php
                                    $childItemNumber = $workstreamIndex . '.' . ($taskIndex + 1);
                                    $taskUpdates = (int)($task['updates'] ?? 0);
                                @endphp
                                <tr class="child-task">
                                    <td style="text-align: center; font-weight: 700;">{{ $childItemNumber }}</td>
                                    <td><span style="color: #94a3b8; margin-right: 8px; display: inline-block;">└</span>{{ $workstreamTitle }}</td>
                                    <td>{{ $task['sub_task'] }}</td>
                                    <td><span style="color: #102d79; font-weight: 700;">{{ $task['responsibility'] ?? 'Responsible' }}</span></td>
                                    <td>{{ $task['assigned'] ?? 'Rubeca Potayre' }}</td>
                                    <td>
                                        <button type="button" class="sow-update-pill" onclick="openSowClientUpdates('{{ addslashes($task['sub_task']) }}', {{ $taskUpdates }})">
                                            {{ $taskUpdates }} updates
                                        </button>
                                    </td>
                                    <td>
                                        <span class="sow-status-badge {{ Str::slug($task['status']) }}">
                                            {{ $task['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach

                            @php $workstreamIndex++; @endphp
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- OUT OF SCOPE TABLE -->
            <div class="sow-scope-banner out-scope">OUT OF SCOPE — EXECUTION STATUS &amp; CLIENT UPDATES</div>
            <div class="sow-table-wrap">
                <table class="sow-table">
                    <thead>
                        <tr>
                            <th style="width: 5%">ITEM</th>
                            <th style="width: 19%">MAIN TASK DESCRIPTION</th>
                            <th style="width: 25%">SUB TASK DESCRIPTION</th>
                            <th style="width: 13%">RESPONSIBILITY</th>
                            <th style="width: 15%">ASSIGNED PERSON</th>
                            <th style="width: 12%">UPDATES SENT TO CLIENT</th>
                            <th style="width: 11%">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($outWorkstreams->isEmpty())
                            <tr>
                                <td colspan="7" class="sow-report-empty-state">
                                    No approved Out of Scope tasks are recorded.
                                </td>
                            </tr>
                        @else
                            @php $outIndex = 1; @endphp
                            @foreach ($outWorkstreams as $outTitle => $outTasks)
                                <tr class="main-task">
                                    <td>OOS-{{ $outIndex }}</td>
                                    <td>{{ $outTitle }}</td>
                                    <td>Out of Scope workstream</td>
                                    <td><span style="color: #1e3a8a; font-weight: 700;">Responsible</span></td>
                                    <td>Project Manager</td>
                                    <td>
                                        <button type="button" class="sow-update-pill" onclick="openSowClientUpdates('{{ addslashes($outTitle) }}', 0)">
                                            0 updates
                                        </button>
                                    </td>
                                    <td><span class="sow-status-badge in-progress">In Progress</span></td>
                                </tr>
                                @foreach ($outTasks as $outChildIdx => $outChild)
                                    <tr class="child-task">
                                        <td>OOS-{{ $outIndex }}.{{ $outChildIdx + 1 }}</td>
                                        <td><span style="color: #64748b;">↳</span> {{ $outTitle }}</td>
                                        <td>{{ data_get($outChild, 'sub_task_description') ?: data_get($outChild, 'task', 'Out of scope task') }}</td>
                                        <td>Responsible</td>
                                        <td>{{ data_get($outChild, 'responsible', 'Unassigned') }}</td>
                                        <td>
                                            <button type="button" class="sow-update-pill" onclick="openSowClientUpdates('{{ addslashes(data_get($outChild, 'sub_task_description') ?: 'Task') }}', 0)">
                                                0 updates
                                            </button>
                                        </td>
                                        <td><span class="sow-status-badge in-progress">In Progress</span></td>
                                    </tr>
                                @endforeach
                                @php $outIndex++; @endphp
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- SOW REPORT SUMMARY -->
            <div class="sow-summary-title">SOW Report Summary</div>

            <div class="sow-summary-sublabel">Within Scope</div>
            <div class="sow-summary-grid">
                <div class="sow-summary-card">
                    <span>MAIN TASKS</span>
                    <strong>{{ $withinMainCount }}</strong>
                </div>
                <div class="sow-summary-card">
                    <span>CHILD TASKS</span>
                    <strong>{{ $withinChildCount }}</strong>
                </div>
                <div class="sow-summary-card">
                    <span>DONE</span>
                    <strong>{{ $withinDoneCount }}</strong>
                </div>
                <div class="sow-summary-card">
                    <span>IN PROGRESS</span>
                    <strong>{{ $withinProgressCount }}</strong>
                </div>
                <div class="sow-summary-card">
                    <span>ON HOLD</span>
                    <strong>{{ $withinHoldCount }}</strong>
                </div>
            </div>

            <div class="sow-summary-sublabel">Out of Scope</div>
            <div class="sow-summary-grid">
                <div class="sow-summary-card">
                    <span>MAIN TASKS</span>
                    <strong>{{ $outMainCount }}</strong>
                </div>
                <div class="sow-summary-card">
                    <span>CHILD TASKS</span>
                    <strong>{{ $outChildCount }}</strong>
                </div>
                <div class="sow-summary-card">
                    <span>DONE</span>
                    <strong>{{ $outDoneCount }}</strong>
                </div>
                <div class="sow-summary-card">
                    <span>IN PROGRESS</span>
                    <strong>{{ $outProgressCount }}</strong>
                </div>
                <div class="sow-summary-card">
                    <span>ON HOLD</span>
                    <strong>{{ $outHoldCount }}</strong>
                </div>
            </div>

            <!-- FINAL REPORT NARRATIVE -->
            <div class="sow-summary-title">Final Report Narrative</div>
            <div class="sow-narrative-grid">
                <div class="sow-narrative-field">
                    <label for="sowNarrativeIssues">Issues &amp; Observations</label>
                    <textarea id="sowNarrativeIssues">{{ $narrativeIssues }}</textarea>
                </div>
                <div class="sow-narrative-field">
                    <label for="sowNarrativeRecs">Recommendations</label>
                    <textarea id="sowNarrativeRecs">{{ $narrativeRecommendations }}</textarea>
                </div>
                <div class="sow-narrative-field">
                    <label for="sowNarrativeWay">Summary / Way Forward</label>
                    <textarea id="sowNarrativeWay">{{ $narrativeWay }}</textarea>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- CLIENT UPDATES MODAL -->
<div id="sowClientUpdatesModal" class="sow-modal-overlay" onclick="closeSowClientUpdatesOnBackdrop(event)">
    <div class="sow-modal-dialog">
        <div class="sow-modal-header">
            <div>
                <h3 id="sowUpdatesModalTitle">Client Updates</h3>
                <p id="sowUpdatesModalMeta">0 client reports recorded for this SOW item</p>
            </div>
            <button type="button" class="sow-modal-close-btn" onclick="closeSowClientUpdates()">Close</button>
        </div>
        <div id="sowUpdatesModalBody" class="sow-modal-content">
            <p class="sow-report-empty-state">No updates have been sent to the client for this item.</p>
        </div>
    </div>
</div>

<script>
// Live ticking handling timer
(function() {
    let secondsElapsed = 0;
    const timerElem = document.getElementById('sowLiveHandlingTimer');
    const progressElem = document.getElementById('sowLiveProgressTimer');

    function fmt(sec) {
        const h = String(Math.floor(sec / 3600)).padStart(2, '0');
        const m = String(Math.floor((sec % 3600) / 60)).padStart(2, '0');
        const s = String(sec % 60).padStart(2, '0');
        return `${h}:${m}:${s}`;
    }

    setInterval(function() {
        secondsElapsed++;
        const str = fmt(secondsElapsed);
        if (timerElem) timerElem.textContent = str;
        if (progressElem) progressElem.textContent = str;
    }, 1000);
})();

// Client Updates Modal handler
function openSowClientUpdates(title, count) {
    const modal = document.getElementById('sowClientUpdatesModal');
    const titleElem = document.getElementById('sowUpdatesModalTitle');
    const metaElem = document.getElementById('sowUpdatesModalMeta');
    const bodyElem = document.getElementById('sowUpdatesModalBody');

    if (!modal) return;

    if (titleElem) titleElem.textContent = title;
    if (metaElem) metaElem.textContent = `${count} client ${count === 1 ? 'report' : 'reports'} recorded for this SOW item`;

    if (bodyElem) {
        if (count > 0) {
            bodyElem.innerHTML = `
                <article class="sow-client-update-entry">
                    <div>
                        <h5>Execution Update &mdash; ${escapeHtml(title)}</h5>
                        <div class="sow-delivery-proof">
                            <span class="status-pill">Sent to Client</span>
                            <span>To: {{ $clientName }}</span>
                            <span>Proof ID: REPORT-{{ $project->id }}-01</span>
                        </div>
                        <p style="margin: 8px 0 0; font-size: 11.5px; color: #334155;">Task progress update dispatched to client.</p>
                    </div>
                    <button type="button" class="sow-modal-close-btn" style="color: #1e3a8a; font-weight: 800;" onclick="alert('Update resent to client email.')">
                        Resend
                    </button>
                </article>
            `;
        } else {
            bodyElem.innerHTML = '<p class="sow-report-empty-state">No updates have been sent to the client for this item.</p>';
        }
    }

    modal.classList.add('open');
}

function closeSowClientUpdates() {
    const modal = document.getElementById('sowClientUpdatesModal');
    if (modal) modal.classList.remove('open');
}

function closeSowClientUpdatesOnBackdrop(event) {
    if (event.target.id === 'sowClientUpdatesModal') {
        closeSowClientUpdates();
    }
}

function escapeHtml(str) {
    return str.replace(/[&<>'"]/g, tag => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#39;',
        '"': '&quot;'
    }[tag] || tag));
}

// Print Official SOW Report function
function printOfficialSowReport() {
    window.print();
}

// Download Official SOW Report function
function downloadOfficialSowReport() {
    const docCard = document.querySelector('.sow-document-card');
    if (!docCard) return;

    const clone = docCard.cloneNode(true);

    const issuesVal = document.getElementById('sowNarrativeIssues')?.value || '';
    const recsVal = document.getElementById('sowNarrativeRecs')?.value || '';
    const wayVal = document.getElementById('sowNarrativeWay')?.value || '';

    const cloneIssues = clone.querySelector('#sowNarrativeIssues');
    if (cloneIssues) cloneIssues.textContent = issuesVal;
    const cloneRecs = clone.querySelector('#sowNarrativeRecs');
    if (cloneRecs) cloneRecs.textContent = recsVal;
    const cloneWay = clone.querySelector('#sowNarrativeWay');
    if (cloneWay) cloneWay.textContent = wayVal;

    let pageStyles = '';
    document.querySelectorAll('style, link[rel="stylesheet"]').forEach(el => {
        pageStyles += el.outerHTML + '\n';
    });

    const docTitle = escapeHtml('{{ $projectRef }} SOW Report');
    const fullHtml = [
        '<!DOCTYPE html>',
        '<html lang="en">',
        '<head>',
        '<meta charset="UTF-8">',
        '<meta name="viewport" content="width=device-width, initial-scale=1">',
        '<title>' + docTitle + '</title>',
        pageStyles,
        '</' + 'head>',
        '<body style="background:#f8fafc; padding:28px 16px; display:flex; justify-content:center; margin:0;">',
        clone.outerHTML,
        '</' + 'body>',
        '</' + 'html>'
    ].join('\n');

    const blob = new Blob([fullHtml], { type: 'text/html;charset=utf-8' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = '{{ $projectRef }}-sow-report.html';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    setTimeout(function() { URL.revokeObjectURL(link.href); }, 1000);
}
</script>

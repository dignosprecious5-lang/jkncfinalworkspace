@php
    $ntpRefNo = $ntpRecord?->reference_no ?: ($ntpRecord?->payload['ntp_number'] ?? ('NTP-' . date('Y') . '-' . str_pad($regular->id, 3, '0', STR_PAD_LEFT)));
    $epaNo = 'EPA-' . date('Y') . '-' . str_pad($regular->id, 3, '0', STR_PAD_LEFT);
    $regNo = $regular->project_code;
    $dealRef = $regular->deal?->deal_code ?: ('CONDEAL-' . date('Y') . '-' . str_pad($regular->id, 3, '0', STR_PAD_LEFT));
    $clientNameVal = $contactName ?: ($regular->client_name ?: ($regular->contact?->full_name ?: 'May Flor D. Dabatos'));
    $businessNameVal = $regular->business_name ?: ($regular->company?->company_name ?: 'X10 REAL ESTATE CORPORATION');
    $rsatRefNo = $rsat?->rsat_number ?: ('RSAT-' . date('Y') . '-' . str_pad($regular->id, 3, '0', STR_PAD_LEFT));
    
    $approvedStartVal = $regular->planned_start_date ? \Carbon\Carbon::parse($regular->planned_start_date)->format('m/d/Y') : date('m/d/Y');
    $targetEndVal = $regular->target_completion_date ? \Carbon\Carbon::parse($regular->target_completion_date)->format('m/d/Y') : date('m/d/Y', strtotime('+30 days'));
    $dateIssuedVal = optional($ntpRecord?->created_at ?? now())->format('m/d/Y');

    $leadConsultant = $approvalLeadConsultant ?: ($regular->assigned_consultant ?: 'John Kelly Abalde');
    $leadAssociate = $approvalLeadAssociate ?: ($regular->lead_associate ?: 'Rubeca Potayre');

    $isNtpApproved = (bool)($ntpApproved || $ntpRecord?->client_approved_at || $regular->status === 'Completed');

    $withinList = collect($rsatRequirements ?? [])->filter(fn($x) => filled(data_get($x, 'main_task_description') ?: data_get($x, 'requirement') ?: data_get($x, 'sub_task_description')));
    if ($withinList->isEmpty()) {
        $withinList = collect([
            ['main_task_description' => 'Share Transfer Documentation', 'sub_task_description' => 'Review existing corporate and share ownership records', 'responsible' => 'Rubeca Potayre', 'duration' => '1h'],
            ['main_task_description' => 'Share Transfer Documentation', 'sub_task_description' => 'Verify shares to be transferred by Dany and Ronald', 'responsible' => 'Rubeca Potayre', 'duration' => '1h'],
            ['main_task_description' => 'Share Transfer Documentation', 'sub_task_description' => 'Prepare applicable share transfer documents', 'responsible' => 'Rubeca Potayre', 'duration' => '2h'],
            ['main_task_description' => 'Share Transfer Documentation', 'sub_task_description' => 'Coordinate documentary requirements with transferors', 'responsible' => 'Rubeca Potayre', 'duration' => '2h'],
            ['main_task_description' => 'Share Transfer Documentation', 'sub_task_description' => 'Facilitate execution and signing of transfer documents', 'responsible' => 'Rubeca Potayre', 'duration' => '1h'],
            ['main_task_description' => 'BIR Share Transfer Processing', 'sub_task_description' => 'Prepare and organize applicable BIR requirements', 'responsible' => 'Rubeca Potayre', 'duration' => '2h'],
            ['main_task_description' => 'BIR Share Transfer Processing', 'sub_task_description' => 'Prepare ONETT / documentary stamp requirements', 'responsible' => 'John Kelly Abalde', 'duration' => '2h'],
            ['main_task_description' => 'BIR Share Transfer Processing', 'sub_task_description' => 'Submit and monitor BIR share transfer processing', 'responsible' => 'Rubeca Potayre', 'duration' => '4h'],
        ]);
    }

    $groupedWithinItems = $withinList->groupBy(fn($x) => trim(data_get($x, 'main_task_description') ?: 'Main Scope Task'));
    $totalChildTasksCount = $withinList->count();
    $totalMainTasksCount = $groupedWithinItems->count();

    $totalHoursSum = 0;
    foreach($withinList as $wIt) {
        $dur = strtolower((string)data_get($wIt, 'duration') ?: data_get($wIt, 'timeline'));
        if (preg_match('/(\d+(\.\d+)?)\s*h/', $dur, $m)) {
            $totalHoursSum += (float)$m[1];
        }
    }
    if ($totalHoursSum === 0) $totalHoursSum = 15;

    $outList = collect([
        ['main_task_description' => 'Activities outside approved SOW', 'sub_task_description' => 'Any material work outside the approved Scope of Work requires controlled Project Change / SOW revision.', 'responsible' => 'Project Manager', 'duration' => '—']
    ]);
@endphp

<style>
/* Scoped Styles for Official Regular NTP Document & Interactive Form */
.ntp-canvas-wrap {
    display: flex;
    flex-direction: column;
    gap: 20px;
    width: 100%;
    background: #eff3f8;
    padding: 20px;
    border-radius: 12px;
    border: 1px solid #dce4ee;
    box-sizing: border-box;
}
.ntp-preview-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 14px 20px;
    background: #ffffff;
    border: 1px solid #dce4ef;
    border-radius: 8px;
    margin-bottom: 4px;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
    font-family: Inter, "Segoe UI", Arial, sans-serif;
}
.ntp-preview-heading div { display: grid; gap: 2px; }
.ntp-preview-heading strong { color: #0f172a; font-size: 15px; font-weight: 800; }
.ntp-preview-heading span { color: #64748b; font-size: 10px; font-weight: 600; }
.ntp-preview-format { 
    padding: 5px 12px; 
    border-radius: 9999px; 
    background: #eff6ff; 
    color: #1d4ed8; 
    font-size: 11px;
    font-weight: 800; 
    letter-spacing: 0.3px;
    display: inline-flex;
    align-items: center;
}
.ntp-page-card {
    background: #ffffff;
    border: 1px solid #34425d;
    border-radius: 0px;
    box-shadow: 0 10px 30px rgba(37, 55, 91, 0.12);
    padding: 18px 20px 16px;
    font-family: Georgia, "Times New Roman", serif !important;
    color: #111111;
    box-sizing: border-box;
    width: 210mm;
    min-height: 297mm;
    max-width: 100%;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
}
.ntp-page-card * {
    font-family: Georgia, "Times New Roman", serif !important;
}
.ntp-paper-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
    min-height: 84px;
}
.ntp-paper-brand {
    font-size: 24px;
    line-height: .9;
    padding: 5px 0 0 16px;
    color: #111827;
}
.ntp-paper-brand small {
    display: block;
    margin-left: 28px;
    font-size: 18px;
    font-weight: 400;
}
.ntp-title-box {
    text-align: right;
    margin: 0;
}
.ntp-title-box h1 {
    margin: 0;
    font-size: 22px;
    letter-spacing: 0.2px;
    font-weight: 900;
    color: #111827;
}
.document-confidential {
    display: block;
    margin-top: 7px;
    color: #c00000 !important;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1.4px;
}
.ntp-date-row {
    font-size: 12px;
    font-weight: 700;
    margin: -24px 0 10px auto;
    width: max-content;
    text-align: right;
}
.ntp-date-input {
    border: 0;
    border-bottom: 1px solid #8893a3;
    background: transparent;
    font-family: Georgia, serif !important;
    font-size: 12px;
    font-weight: 700;
    padding: 2px 4px;
    width: 130px;
    color: #111;
}
.ntp-date-input:focus {
    outline: none;
    border-bottom-color: #203a7c;
    background: #f8fafc;
}
.ntp-section-bar {
    background: #203a7c;
    color: #ffffff;
    text-align: center;
    font-size: 10px;
    font-weight: 700;
    padding: 6px 8px;
    letter-spacing: .25px;
    text-transform: uppercase;
    margin-top: 10px;
}
.ntp-info-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 14px;
    table-layout: fixed;
}
.ntp-info-table td {
    border: 1px solid #515966;
    padding: 8px 9px;
    font-size: 10px;
    vertical-align: middle;
}
.ntp-info-table strong {
    font-weight: 700;
    color: #111;
}
.ntp-inline-input {
    border: 0;
    background: transparent;
    width: auto;
    max-width: 70%;
    font-family: Georgia, serif !important;
    font-size: 10px;
    font-weight: 700;
    padding: 0 2px;
    color: #111;
}
.ntp-inline-input:focus {
    outline: none;
    background: #f8fafc;
    border-radius: 2px;
}
.ntp-body-content {
    font-size: 11px;
    line-height: 1.55;
    text-align: justify;
    margin: 0 0 14px;
    border: 1px solid #515966;
    border-top: 0;
    padding: 13px 15px 3px;
    hyphens: auto;
}
.ntp-body-content p {
    margin: 0 0 16px;
}
.ntp-body-content strong {
    font-weight: 700;
}
.ntp-signatures-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    margin-top: 0;
    margin-bottom: 14px;
}
.ntp-signatures-table td {
    border: 1px solid #444444;
    padding: 8px;
    vertical-align: top;
}
.ntp-signatures-table .sig-head {
    font-size: 12px;
    font-weight: 700;
    padding: 7px 9px;
    background: #ffffff;
}
.ntp-signatures-table .sig-box {
    text-align: center;
    height: 54px;
    font-size: 10px;
    line-height: 1.4;
}
.ntp-signatures-table .sig-box.tall {
    height: 112px;
}
.ntp-sig-input {
    border: 0;
    border-bottom: 1px solid #94a3b8;
    background: transparent;
    font-family: Georgia, serif !important;
    font-size: 10px;
    font-weight: 700;
    text-align: center;
    padding: 2px 4px;
    width: 85%;
    color: #111;
}
.ntp-page-footer-official {
    font-size: 7px;
    line-height: 1.35;
    margin-top: auto;
    color: #303846;
    border-top: 2px solid #203a7c;
    padding-top: 8px;
    display: block;
}
.confidential-notice-official {
    margin: 0 0 8px;
    text-align: justify;
    line-height: 1.4;
    color: #4b5568;
}
.footer-bottom-official {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
}
.footer-bottom-official > strong {
    white-space: nowrap;
    font-size: 8px;
    color: #203a7c;
}
</style>

<div class="ntp-layout" style="display: grid; grid-template-columns: 280px minmax(0, 1fr); gap: 20px; align-items: start; width: 100%;">
    <!-- STAGE MANAGEMENT SIDEBAR -->
    <aside class="sidecard" aria-live="polite" style="position: sticky; top: 16px; align-self: start; background: #ffffff; border: 1px solid #dce4f2; border-radius: 16px; padding: 18px 16px; box-sizing: border-box; width: 100%; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04); overflow: visible !important; font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        <div class="side-title" style="font-size: 13px; font-weight: 900; color: #1e3a8a; text-transform: uppercase; letter-spacing: 0.05em; padding: 0 0 16px 0; margin: 0;">STAGE MANAGEMENT</div>
        
        <div class="stage-management-panel wo-management">
            <!-- STAGE STATUS -->
            <section style="margin-bottom: 20px; padding-top: 0;">
                <h4 style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; margin: 0 0 8px 0; letter-spacing: 0.05em;">STAGE STATUS</h4>
                <div class="stage-status-value {{ $isNtpApproved ? 'completed' : 'locked' }}" style="background: #ffffff; border: 1px solid #dce4f2; border-radius: 12px; padding: 12px 16px; font-size: 14px; font-weight: 800; color: #1e293b; display: flex; align-items: center; min-height: 48px; box-sizing: border-box;">
                    {{ $isNtpApproved ? 'Approved' : 'Locked' }}
                </div>
            </section>

            <!-- STAGE HANDLING -->
            <section style="margin-bottom: 20px; padding-top: 0;">
                <h4 style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; margin: 0 0 8px 0; letter-spacing: 0.05em;">STAGE HANDLING</h4>
                <div class="stage-total" style="display: flex; justify-content: space-between; align-items: center; background: #f4f8fd; border: 1px solid #dce4f2; border-radius: 12px; padding: 12px 16px; margin-bottom: 12px; width: 100%; box-sizing: border-box; gap: 10px;">
                    <span style="font-size: 11px; color: #475569; font-weight: 700; line-height: 1.3; flex-shrink: 0;">Total Stage<br>Handling<br>Time</span>
                    <strong class="clock" id="ntpTimerClock" style="font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 22px; font-weight: 800; color: #1e3a8a; white-space: nowrap; flex-shrink: 0; letter-spacing: 0.05em;">00:00:43</strong>
                </div>
                <dl class="stage-handling" style="display: grid; gap: 6px; font-size: 11px; margin: 0; padding: 0 2px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;"><dt style="color: #64748b; font-weight: 600; margin: 0;">In Progress</dt><dd style="font-weight: 700; color: #1e293b; margin: 0; font-family: ui-monospace, SFMono-Regular, Consolas, monospace;" id="ntpProgressTime">00:00:43</dd></div>
                    <div style="display: flex; justify-content: space-between; align-items: center;"><dt style="color: #64748b; font-weight: 600; margin: 0;">On Hold</dt><dd style="font-weight: 700; color: #475569; margin: 0; font-family: ui-monospace, SFMono-Regular, Consolas, monospace;">00:00:00</dd></div>
                    <div style="display: flex; justify-content: space-between; align-items: center;"><dt style="color: #64748b; font-weight: 600; margin: 0;">Waiting</dt><dd style="font-weight: 700; color: #475569; margin: 0; font-family: ui-monospace, SFMono-Regular, Consolas, monospace;">00:00:00</dd></div>
                    <div style="display: flex; justify-content: space-between; align-items: center;"><dt style="color: #64748b; font-weight: 600; margin: 0;">Responsible</dt><dd style="font-weight: 700; color: #1e3a8a; margin: 0;">Unassigned</dd></div>
                    <div style="display: flex; justify-content: space-between; align-items: center;"><dt style="color: #64748b; font-weight: 600; margin: 0;">Executor</dt><dd style="font-weight: 700; color: #1e3a8a; margin: 0;">Unassigned</dd></div>
                    <div style="display: flex; justify-content: space-between; align-items: center;"><dt style="color: #64748b; font-weight: 600; margin: 0;">Coordinator</dt><dd style="font-weight: 700; color: #1e3a8a; margin: 0;">Unassigned</dd></div>
                    <div style="display: flex; justify-content: space-between; align-items: center;"><dt style="color: #64748b; font-weight: 600; margin: 0;">Support</dt><dd style="font-weight: 700; color: #1e3a8a; margin: 0;">Unassigned</dd></div>
                    <div style="display: flex; justify-content: space-between; align-items: center;"><dt style="color: #64748b; font-weight: 600; margin: 0;">Waiting On</dt><dd style="font-weight: 700; color: #475569; margin: 0;">&mdash;</dd></div>
                    <div style="display: flex; justify-content: space-between; align-items: center;"><dt style="color: #64748b; font-weight: 600; margin: 0;">Started</dt><dd style="font-weight: 700; color: #475569; margin: 0;">{{ optional($ntpRecord?->created_at ?? $regular->created_at)->format('m/d/Y, H:i:s') }}</dd></div>
                    <div style="display: flex; justify-content: space-between; align-items: center;"><dt style="color: #64748b; font-weight: 600; margin: 0;">Completed</dt><dd style="font-weight: 700; color: #475569; margin: 0;">{{ $isNtpApproved ? optional($ntpRecord?->created_at ?? $regular->created_at)->format('m/d/Y, H:i:s') : '&mdash;' }}</dd></div>
                </dl>
            </section>

            <!-- REQUIRED APPROVALS -->
            <section style="margin-bottom: 20px; padding-top: 0;">
                <h4 style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; margin: 0 0 8px 0; letter-spacing: 0.05em;">REQUIRED APPROVALS</h4>
                <div style="display: grid; gap: 8px;">
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px;">
                        <strong style="font-size: 12px; font-weight: 800; color: #1e293b; display: block; line-height: 1.3; margin-bottom: 2px;">{{ $clientNameVal }} &mdash; {{ $isNtpApproved ? 'Approved' : 'Pending' }}</strong>
                        <span style="font-size: 10.5px; color: #64748b; font-weight: 500; display: block;">{{ $isNtpApproved ? 'Approval recorded' : 'Waiting for approval evidence' }}</span>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px;">
                        <strong style="font-size: 12px; font-weight: 800; color: #1e293b; display: block; line-height: 1.3; margin-bottom: 2px;">{{ $leadConsultant }} &mdash; {{ $isNtpApproved ? 'Approved' : 'Pending' }}</strong>
                        <span style="font-size: 10.5px; color: #64748b; font-weight: 500; display: block;">{{ $isNtpApproved ? 'Approval recorded' : 'Waiting for approval evidence' }}</span>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px;">
                        <strong style="font-size: 12px; font-weight: 800; color: #1e293b; display: block; line-height: 1.3; margin-bottom: 2px;">{{ $leadAssociate }} &mdash; {{ $isNtpApproved ? 'Approved' : 'Pending' }}</strong>
                        <span style="font-size: 10.5px; color: #64748b; font-weight: 500; display: block;">{{ $isNtpApproved ? 'Approval recorded' : 'Waiting for approval evidence' }}</span>
                    </div>
                </div>
            </section>

            <!-- STAGE CONTROLS -->
            <section style="margin-bottom: 20px; padding-top: 0;">
                <h4 style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; margin: 0 0 8px 0; letter-spacing: 0.05em;">STAGE CONTROLS</h4>
                <div style="display: grid; gap: 8px;">
                    @if(!$isNtpApproved)
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; font-size: 11px; color: #64748b; line-height: 1.45;">
                        Complete the Work Order before this stage becomes available.
                    </div>
                    @endif

                    <a href="{{ route('regular.ntp.submission', $regular) }}" style="display: flex; align-items: center; justify-content: flex-start; width: 100%; height: 42px; border-radius: 12px; border: 1px solid #dce4f0; background: #ffffff; font-size: 12px; font-weight: 700; color: {{ $isNtpApproved ? '#1e3a8a' : '#8fa0b5' }}; text-decoration: none; {{ $isNtpApproved ? '' : 'pointer-events: none;' }} padding: 0 16px; box-sizing: border-box;">
                        Send to Client
                    </a>
                    <button type="button" onclick="window.print()" style="display: flex; align-items: center; justify-content: flex-start; width: 100%; height: 42px; border-radius: 12px; border: 1px solid #dce4f0; background: #ffffff; font-size: 12px; font-weight: 700; color: #8fa0b5; cursor: pointer; padding: 0 16px; box-sizing: border-box;">
                        Print
                    </button>
                    <a href="{{ route('regular.ntp.download', $regular) }}" style="display: flex; align-items: center; justify-content: flex-start; width: 100%; height: 42px; border-radius: 12px; border: 1px solid #dce4f0; background: #ffffff; font-size: 12px; font-weight: 700; color: #8fa0b5; text-decoration: none; box-sizing: border-box; padding: 0 16px;">
                        Download
                    </a>
                </div>
            </section>

            <!-- EXECUTION GATE -->
            <section style="padding-top: 0;">
                <h4 style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; margin: 0 0 8px 0; letter-spacing: 0.05em;">EXECUTION GATE</h4>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; font-size: 11px; color: #64748b; line-height: 1.45; margin-bottom: 10px;">
                    Execution remains locked until Client, Lead Consultant, and Lead Associate approvals are recorded.
                </div>
                <a href="{{ route('regular.show', ['regular' => $regular, 'tab' => 'execution']) }}" style="display: flex; align-items: center; justify-content: center; width: 100%; height: 44px; border-radius: 12px; background: {{ $isNtpApproved ? '#1e3a8a' : '#f8fafc' }}; color: {{ $isNtpApproved ? '#ffffff' : '#b0bec5' }}; border: {{ $isNtpApproved ? 'none' : '1px solid #e2e8f0' }}; font-size: 12px; font-weight: 800; text-decoration: none; {{ $isNtpApproved ? '' : 'pointer-events: none;' }} padding: 0 16px; box-sizing: border-box;">
                    Proceed to Execution
                </a>
            </section>
        </div>
    </aside>

    <!-- RIGHT CONTENT: 3-PAGE OFFICIAL NTP FORM & PREVIEW -->
    <section class="content">
        <div class="ntp-banner" style="font-family:Inter, sans-serif; margin:0 0 12px; padding:12px 16px; border-left:4px solid #2563eb; background:#f4f7fe; color:#334155; font-size:11px; border-radius:6px; line-height:1.4;">
            <strong>NTP Preparation:</strong> Review is complete. Review the Notice to Proceed with the approved RSAT and obtain the required authorizations before Execution.
        </div>

        <div class="ntp-context-bar" style="display:flex; gap:14px; align-items:center; justify-content:flex-start; padding:12px 20px; margin:0 0 16px; border:1px solid #e2e8f0; background:#ffffff; border-radius:12px; font-family:Inter, sans-serif; box-shadow:0 1px 3px rgba(0,0,0,0.02);">
            <label style="font-size:11.5px; font-weight:800; color:#475569;">NTP Type</label>
            <select id="ntpTypeSelect" class="ntp-type-select" style="height:38px; border:1px solid #d1d5db; border-radius:8px; background:#ffffff; padding:0 14px; font-size:11px; color:#1e293b; font-weight:600;">
                <option value="original">Regular NTP</option>
                <option value="supplemental">Supplemental NTP</option>
            </select>
            <span class="ntp-context-pill" style="padding:5px 14px; border-radius:9999px; background:#eff6ff; color:#1d4ed8; font-size:10.5px; font-weight:800; letter-spacing:0.5px;">REGULAR NTP</span>
        </div>

        <form id="ntpInteractiveForm" method="POST" action="{{ route('regular.ntp.manual-approve', $regular) }}">
            @csrf
            <div class="ntp-canvas-wrap">

                <div class="ntp-preview-heading">
                    <div>
                        <strong>Notice to Proceed</strong>
                        <span>Document preview</span>
                    </div>
                    <span class="ntp-preview-format">A4 &middot; Live Preview</span>
                </div>

                <!-- PAGE 1 OF 3: NOTICE TO PROCEED & AUTHORIZATION FORM -->
                <div class="ntp-page-card">
                    <div class="ntp-paper-head">
                        <div class="ntp-paper-brand">
                            John Kelly
                            <small>&amp; Company</small>
                        </div>
                        <div class="ntp-title-box">
                            <h1>NOTICE TO PROCEED</h1>
                            <span class="document-confidential">STRICTLY CONFIDENTIAL</span>
                        </div>
                    </div>

                    <div class="ntp-date-row">
                        Date Issued: 
                        <input type="text" name="date_issued" value="{{ $dateIssuedVal }}" class="ntp-date-input">
                    </div>

                    <div class="ntp-section-bar">NOTICE TO PROCEED INFORMATION</div>
                    <table class="ntp-info-table">
                        <tr>
                            <td style="width: 50%">
                                <strong>NTP No.:</strong> 
                                <input type="text" name="ntp_number" value="{{ $ntpRefNo }}" class="ntp-inline-input">
                            </td>
                            <td style="width: 50%">
                                <strong>Engagement Type:</strong> Regular
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Engagement Proposal Agreement (EPA) No.:</strong> 
                                <input type="text" name="epa_number" value="{{ $epaNo }}" class="ntp-inline-input">
                            </td>
                            <td>
                                <strong>Regular No.:</strong> {{ $regNo }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Condeal Reference No.:</strong> 
                                <input type="text" name="condeal_reference" value="{{ $dealRef }}" class="ntp-inline-input">
                            </td>
                            <td>
                                <strong>Client Name:</strong> 
                                <input type="text" name="client_name" value="{{ $clientNameVal }}" class="ntp-inline-input">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Business Name:</strong> 
                                <input type="text" name="business_name" value="{{ $businessNameVal }}" class="ntp-inline-input">
                            </td>
                            <td>
                                <strong>RSAT Ref No.:</strong> 
                                <input type="text" name="rsat_ref_no" value="{{ $rsatRefNo }}" class="ntp-inline-input">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Approved Start Date:</strong> 
                                <input type="text" name="approved_start_date" value="{{ $approvedStartVal }}" class="ntp-inline-input">
                            </td>
                            <td>
                                <strong>Target Completion Date:</strong> 
                                <input type="text" name="target_completion_date" value="{{ $targetEndVal }}" class="ntp-inline-input">
                            </td>
                        </tr>
                    </table>

                    <div class="ntp-section-bar">AUTHORIZATION TO PROCEED</div>
                    <div class="ntp-body-content">
                        <p>
                            This Notice to Proceed (the “NTP”) is issued under <strong>NTP No. {{ $ntpRefNo }}</strong>, under <strong>EPA No. {{ $epaNo }}</strong>, forms an integral part thereof, and applies to <strong>RSAT Ref No. {{ $rsatRefNo }}</strong>. It confirms that <strong>{{ $clientNameVal }}</strong>, acting personally or through a duly authorized representative of <strong>{{ $businessNameVal }}</strong>, has reviewed the engagement details and authorizes <strong>John Kelly &amp; Company (JK&amp;C Inc.)</strong> to commence only the approved scope identified in the attached RSAT.
                        </p>
                        <p>
                            The Client acknowledges the Approved Start Date and Target Completion Date stated above; agrees to comply with the applicable policies, procedures, engagement conditions, and lawful instructions of John Kelly &amp; Company that form part of or are properly incorporated into the EPA and RSAT; and expressly authorizes John Kelly &amp; Company to proceed after this NTP has received every required approval and the approval evidence has been recorded. Services performed thereafter within the incorporated RSAT, schedule, deliverables, and commercial terms shall be treated as authorized under this NTP and the EPA.
                        </p>
                        <p>
                            The Target Completion Date is a good-faith regular service target and may be reasonably adjusted when actual regular service conditions, operational requirements, dependencies, regulatory processing, scope changes, client action or inaction, or other circumstances affect the schedule. John Kelly &amp; Company shall inform the Client of a material schedule adjustment, the reason for it, and the revised target date as soon as reasonably practicable. A schedule adjustment does not by itself amend the authorized scope or commercial terms.
                        </p>
                        <p>
                            Neither party shall be responsible, to the extent permitted by applicable law, for delay or failure caused by events beyond its reasonable control, including natural disasters, typhoons, earthquakes, floods, fire, epidemics, acts of God, war, civil disturbance, government action, official public holidays, special non-working days, government proclamations, government office closures or service suspensions, utility or telecommunications failure, or comparable force-majeure events. Any resulting delay shall not constitute a breach by the affected party to the extent caused by such event. The affected party shall give reasonable notice, take commercially reasonable steps to reduce the effect of the event, and resume performance when reasonably possible.
                        </p>
                        <p>
                            No oral statement, informal message, or later request changes this authorization. Any addition, exclusion, variation, delay requiring client action, or other change must be documented through the applicable out-of-scope or formal change-control process, assigned its own controlled version, and separately approved before work begins.
                        </p>
                        <p>
                            By signing manually or approving through the authorized electronic process, the approving person represents that they are the Client named above or a duly authorized representative of the identified Business; confirms that the referenced NTP and attached RSAT were available for review; adopts the recorded signature or electronic action as their approval; and authorizes commencement strictly in accordance with those records.
                        </p>
                    </div>

                    <div class="ntp-section-bar">APPROVAL &amp; SIGNATURES</div>
                    <table class="ntp-signatures-table">
                        <tr>
                            <td class="sig-head" style="width: 50%">FOR THE CLIENT</td>
                            <td class="sig-head" style="width: 50%">FOR JOHN KELLY &amp; COMPANY</td>
                        </tr>
                        <tr>
                            <td rowspan="2" class="sig-box tall">
                                <strong>Name, Signature and Date</strong><br>
                                <span style="font-weight: 700; display: block; margin-top: 2px;">Authorized Representative</span>
                                <span style="color: #64748b; font-size: 8px;">Name/Client</span>
                                <div style="margin-top: 24px;">
                                    <input type="text" name="client_signatory_name" value="{{ $clientNameVal }}" class="ntp-sig-input"><br>
                                    <span style="font-size: 8px; color:#64748b;">Authorized Representative</span>
                                </div>
                            </td>
                            <td class="sig-box">
                                <strong>Name, Signature and Date</strong><br>
                                <span style="font-weight: 700; display: block; margin-top: 2px;">Lead Consultant</span>
                                <div style="margin-top: 6px;">
                                    <input type="text" name="lead_consultant_name" value="{{ $leadConsultant }}" class="ntp-sig-input">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="sig-box">
                                <strong>Name, Signature and Date</strong><br>
                                <span style="font-weight: 700; display: block; margin-top: 2px;">Lead Associate</span>
                                <div style="margin-top: 6px;">
                                    <input type="text" name="lead_associate_name" value="{{ $leadAssociate }}" class="ntp-sig-input">
                                </div>
                            </td>
                        </tr>
                    </table>

                    <div class="ntp-page-footer-official">
                        <div class="confidential-notice-official"><strong>CONFIDENTIALITY NOTICE:</strong> This document and its contents are intended only for the Client named herein, a duly authorized representative of the identified Business, and authorized personnel of John Kelly &amp; Company acting for legitimate regular service administration. Only the named Client or a duly authorized representative of the identified Business may provide client approval. Unauthorized access, approval, use, reproduction, alteration, or disclosure is prohibited except as permitted by company policy and applicable law.</div>
                        <div class="footer-bottom-official">
                            <div>John Kelly &amp; Company &middot; 3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000 &middot; Email: start@jknc.io &middot; Phone: 0995-535-8729</div>
                            <strong>Page 1 of 3</strong>
                        </div>
                    </div>
                </div>

                <!-- PAGE 2 OF 3: RSAT WITHIN SCOPE APPENDIX -->
                <div class="ntp-page-card">
                    <div class="ntp-paper-head">
                        <div class="ntp-paper-brand">
                            John Kelly
                            <small>&amp; Company</small>
                        </div>
                        <div class="ntp-title-box">
                            <h1>RSAT</h1>
                            <span class="document-confidential">STRICTLY CONFIDENTIAL</span>
                        </div>
                    </div>

                    <table class="ntp-info-table" style="margin-top: 12px;">
                        <tr>
                            <td style="width: 50%"><strong>Condeal Reference No.:</strong> {{ $dealRef }}</td>
                            <td style="width: 50%"><strong>Version No.:</strong> 1.0</td>
                        </tr>
                        <tr>
                            <td><strong>Business Name:</strong> {{ $businessNameVal }}</td>
                            <td><strong>RSAT No.:</strong> {{ $rsatRefNo }}</td>
                        </tr>
                        <tr>
                            <td><strong>Client Name:</strong> {{ $clientNameVal }}</td>
                            <td><strong>Date Prepared:</strong> {{ $dateIssuedVal }}</td>
                        </tr>
                    </table>

                    <div class="ntp-section-bar">WITHIN SCOPE</div>
                    <table class="ntp-info-table">
                        <tr style="background: #f0f3f8; font-weight: 700;">
                            <td style="width: 9%; text-align: center;">Item</td>
                            <td style="width: 53%;">Scope / Deliverable</td>
                            <td style="width: 26%;">Assigned Person</td>
                            <td style="width: 12%;">Duration</td>
                        </tr>
                        @php $mIdx = 1; @endphp
                        @foreach($groupedWithinItems as $mainTitle => $children)
                            @php $cIdx = 1; @endphp
                            @foreach($children as $child)
                                <tr>
                                    <td style="text-align: center; font-weight: 700;">{{ $mIdx }}.{{ $cIdx }}</td>
                                    <td>
                                        <strong>{{ $mainTitle }}</strong><br>
                                        <span style="font-size: 8px; color: #475569;">{{ data_get($child, 'sub_task_description') ?: data_get($child, 'requirement') }}</span>
                                    </td>
                                    <td>{{ data_get($child, 'responsible') ?: $leadAssociate }}</td>
                                    <td>{{ data_get($child, 'duration') ?: data_get($child, 'timeline') ?: '1h' }}</td>
                                </tr>
                                @php $cIdx++; @endphp
                            @endforeach
                            @php $mIdx++; @endphp
                        @endforeach
                    </table>

                    <div class="ntp-section-bar">RSAT SUMMARY</div>
                    <table class="ntp-info-table">
                        <tr style="background: #f0f3f8; font-weight: 700;">
                            <td style="width: 25%">MAIN ITEMS</td>
                            <td style="width: 25%">SUB-ITEMS</td>
                            <td style="width: 25%">PLANNED DURATION</td>
                            <td style="width: 25%">STATUS</td>
                        </tr>
                        <tr>
                            <td><strong>{{ $totalMainTasksCount }}</strong></td>
                            <td><strong>{{ $totalChildTasksCount }}</strong></td>
                            <td><strong>{{ $totalHoursSum }}h</strong></td>
                            <td><strong>Included in RSAT</strong></td>
                        </tr>
                    </table>

                    <div class="ntp-page-footer-official">
                        <div class="confidential-notice-official"><strong>CONFIDENTIALITY NOTICE:</strong> Only the Client named herein or a duly authorized representative of the identified Business may provide client approval. Access and use are otherwise restricted to those persons and authorized personnel of John Kelly &amp; Company. Unauthorized access, approval, use, reproduction, alteration, or disclosure is prohibited except as permitted by company policy and applicable law.</div>
                        <div class="footer-bottom-official">
                            <div>RSAT &middot; Controlled regular service record &middot; {{ $regNo }}</div>
                            <strong>Page 2 of 3</strong>
                        </div>
                    </div>
                </div>

                <!-- PAGE 3 OF 3: RSAT OUT OF SCOPE APPENDIX -->
                <div class="ntp-page-card">
                    <div class="ntp-paper-head">
                        <div class="ntp-paper-brand">
                            John Kelly
                            <small>&amp; Company</small>
                        </div>
                        <div class="ntp-title-box">
                            <h1>RSAT</h1>
                            <span class="document-confidential">STRICTLY CONFIDENTIAL</span>
                        </div>
                    </div>

                    <table class="ntp-info-table" style="margin-top: 12px;">
                        <tr>
                            <td style="width: 50%"><strong>Condeal Reference No.:</strong> {{ $dealRef }}</td>
                            <td style="width: 50%"><strong>Version No.:</strong> 1.0</td>
                        </tr>
                        <tr>
                            <td><strong>Business Name:</strong> {{ $businessNameVal }}</td>
                            <td><strong>RSAT No.:</strong> {{ $rsatRefNo }}</td>
                        </tr>
                        <tr>
                            <td><strong>Client Name:</strong> {{ $clientNameVal }}</td>
                            <td><strong>Date Prepared:</strong> {{ $dateIssuedVal }}</td>
                        </tr>
                    </table>

                    <div class="ntp-section-bar">OUT OF SCOPE</div>
                    <table class="ntp-info-table">
                        <tr style="background: #f0f3f8; font-weight: 700;">
                            <td style="width: 9%; text-align: center;">Item</td>
                            <td style="width: 53%;">Scope / Exclusion</td>
                            <td style="width: 26%;">Assigned Person</td>
                            <td style="width: 12%;">Duration</td>
                        </tr>
                        @forelse($outList as $oIndex => $outItem)
                            <tr>
                                <td style="text-align: center; font-weight: 700;">{{ $oIndex + 1 }}.1</td>
                                <td>
                                    <strong>{{ data_get($outItem, 'main_task_description') ?: 'Activities outside approved SOW' }}</strong><br>
                                    <span style="font-size: 8px; color: #475569;">{{ data_get($outItem, 'sub_task_description') ?: 'Any material work outside the approved Scope of Work requires controlled Project Change / SOW revision.' }}</span>
                                </td>
                                <td>{{ data_get($outItem, 'responsible') ?: 'Project Manager' }}</td>
                                <td>—</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; color: #64748b; padding: 14px;">No Out of Scope items recorded.</td>
                            </tr>
                        @endforelse
                    </table>

                    <div class="ntp-section-bar">RSAT SUMMARY</div>
                    <table class="ntp-info-table">
                        <tr style="background: #f0f3f8; font-weight: 700;">
                            <td style="width: 25%">MAIN ITEMS</td>
                            <td style="width: 25%">SUB-ITEMS</td>
                            <td style="width: 25%">PLANNED DURATION</td>
                            <td style="width: 25%">STATUS</td>
                        </tr>
                        <tr>
                            <td><strong>1</strong></td>
                            <td><strong>1</strong></td>
                            <td><strong>—</strong></td>
                            <td><strong>Included in RSAT</strong></td>
                        </tr>
                    </table>

                    <div class="ntp-page-footer-official">
                        <div class="confidential-notice-official"><strong>CONFIDENTIALITY NOTICE:</strong> Only the Client named herein or a duly authorized representative of the identified Business may provide client approval. Access and use are otherwise restricted to those persons and authorized personnel of John Kelly &amp; Company. Unauthorized access, approval, use, reproduction, alteration, or disclosure is prohibited except as permitted by company policy and applicable law.</div>
                        <div class="footer-bottom-official">
                            <div>Out of Scope &middot; Controlled regular service record &middot; {{ $regNo }}</div>
                            <strong>Page 3 of 3</strong>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </section>
</div>

<script>
(function() {
    function initNtpClock() {
        var clockEl = document.getElementById('ntpTimerClock');
        var progressEl = document.getElementById('ntpProgressTime');
        if (!clockEl) return;

        function parseSecs(txt) {
            var parts = (txt || '').trim().split(':');
            if (parts.length === 3) {
                return (parseInt(parts[0], 10) || 0) * 3600 + (parseInt(parts[1], 10) || 0) * 60 + (parseInt(parts[2], 10) || 0);
            }
            return 43;
        }

        function fmtSecs(secs) {
            var h = String(Math.floor(secs / 3600)).padStart(2, '0');
            var m = String(Math.floor((secs % 3600) / 60)).padStart(2, '0');
            var s = String(Math.floor(secs % 60)).padStart(2, '0');
            return h + ':' + m + ':' + s;
        }

        var totalSecs = parseSecs(clockEl.textContent);

        if (window._ntpTimerInterval) {
            clearInterval(window._ntpTimerInterval);
        }

        window._ntpTimerInterval = setInterval(function() {
            totalSecs++;
            var formatted = fmtSecs(totalSecs);
            if (clockEl) clockEl.textContent = formatted;
            if (progressEl) progressEl.textContent = formatted;
        }, 1000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNtpClock);
    } else {
        initNtpClock();
    }
})();
</script>

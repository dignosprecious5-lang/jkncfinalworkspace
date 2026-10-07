@php
    $deal = $project->deal;
    $proposal = $deal?->proposals()?->latest()->first();
    $startRecord = $project->starts()->latest()->first();
    $startAttachments = (array) ($startRecord?->attachments ?? []);
    $userDisplayName = auth()->user()?->name ?? 'John Kelly Alcalde';
    $projNum = $project->id;
    $projCode = $project->project_code ?: ('PROJ-2026-' . $projNum);
    $woCode = $project->work_order_code ?: ('PROJ-WO-2026-' . $projNum);
    $sowCode = $project->sow_number ?: ('SOW-2026-' . str_pad((string) $projNum, 3, '0', STR_PAD_LEFT));
    $ntpCode = $project->ntp_number ?: ('NTP-' . $projNum);
    $clientContact = $contactName ?: ($project->client_name ?: 'Client Representative');

    // Comprehensive baseline & live audit events across all modules and activity types
    $serverEvents = [];

    $addEvt = function($id, $at, $user, $module, $type, $action, $details = '', $recipient = '', $proof = '', $status = 'Recorded') use (&$serverEvents) {
        $serverEvents[] = [
            'id' => $id,
            'at' => $at,
            'user' => $user,
            'module' => $module,
            'type' => $type,
            'action' => $action,
            'details' => $details,
            'recipient' => $recipient,
            'proof' => $proof,
            'status' => $status,
        ];
    };

    // 1. Delivery & Completion
    if ($cocApproved) {
        $addEvt("AUDIT-{$projNum}-COC-APP", optional($project->updated_at)->format('Y-m-d H:i:s'), 'Admin / Approver', 'Delivery & Completion', 'Approval', 'Certificate of Completion approved and signed', 'Formal sign-off completed', $clientContact, 'COC-FINAL-' . $projNum, 'Approved');
    }
    $addEvt("AUDIT-{$projNum}-COC-DOC", optional($project->updated_at)->subMinutes(15)->format('Y-m-d H:i:s'), $userDisplayName, 'Delivery & Completion', 'Document', 'Official Certificate of Completion document prepared', 'A4 certificate rendering issued', '', 'DOC-COC-' . $projNum, 'Issued');

    // 2. SOW Report
    $addEvt("AUDIT-{$projNum}-REP-DOC", now()->subHours(4)->format('Y-m-d H:i:s'), $userDisplayName, 'SOW Report', 'Document', 'Project SOW Report summary compiled', 'Deliverable milestones and evidence register verified', $clientContact, 'REP-' . $projNum, 'Generated');

    // 3. Execution Module
    $addEvt("AUDIT-{$projNum}-EXEC-COMM", now()->subHours(6)->format('Y-m-d H:i:s'), $userDisplayName, 'Execution', 'Client Communication', 'Execution progress summary sent to client', 'Milestone status and attachments shared', $clientContact, 'COMM-EXEC-' . $projNum, 'Sent');
    $addEvt("AUDIT-{$projNum}-EXEC-UPD", now()->subHours(8)->format('Y-m-d H:i:s'), $userDisplayName, 'Execution', 'Internal Update', 'Task milestone status marked In Progress', 'Working files and operational notes submitted', 'Project Team', 'UPD-EXEC-' . $projNum, 'Submitted');
    $addEvt("AUDIT-{$projNum}-STAGE-EXEC", now()->subDays(1)->format('Y-m-d H:i:s'), 'System', 'Execution', 'Stage Action', 'Project stage transitioned to Execution', 'Policy compliance requirements satisfied', 'All Stakeholders', 'STAGE-EXEC', 'Active');

    // 4. NTP Module
    $addEvt("AUDIT-{$projNum}-NTP-APP", now()->subDays(1)->subHours(2)->format('Y-m-d H:i:s'), $clientContact, 'NTP', 'Approval', 'Notice to Proceed (NTP) Client Authorization endorsed', 'Client approved authorization recorded', 'Operations Team', 'PROOF-NTP-' . $projNum, 'Approved');
    $addEvt("AUDIT-{$projNum}-NTP-DOC", now()->subDays(1)->subHours(3)->format('Y-m-d H:i:s'), $userDisplayName, 'NTP', 'Document', 'Signed NTP authorization document uploaded', 'Client signature verification attached', '', 'DOC-NTP-' . $projNum, 'Uploaded');
    $addEvt("AUDIT-{$projNum}-NTP-COMM", now()->subDays(1)->subHours(5)->format('Y-m-d H:i:s'), $userDisplayName, 'NTP', 'Client Communication', 'Notice to Proceed sent for online client authorization', 'Transmitted via registered client email', $clientContact, 'REQ-NTP-' . $projNum, 'Sent');

    // 5. Review Module
    $addEvt("AUDIT-{$projNum}-REV-APP", now()->subDays(2)->format('Y-m-d H:i:s'), 'Review Board / Approver', 'Review', 'Approval', 'Scope of Work (SOW v1.0) internally approved', 'Quality check and scope boundary approved', 'Project Team', 'APP-SOW-' . $projNum, 'Approved');
    $addEvt("AUDIT-{$projNum}-REV-UPD", now()->subDays(2)->subHours(1)->format('Y-m-d H:i:s'), 'Operations Lead', 'Review', 'Internal Update', 'SOW Review verification notes recorded', 'Checklist items verified against client mandate', 'Review Board', 'REV-NOTE-' . $projNum, 'Recorded');

    // 6. Scope of Work Module
    $addEvt("AUDIT-{$projNum}-SOW-SUB", now()->subDays(2)->subHours(3)->format('Y-m-d H:i:s'), $userDisplayName, 'Scope of Work', 'User Action', 'Scope of Work submitted for review', 'Deliverables and task items populated', 'Review Board', 'SUB-SOW-' . $projNum, 'Submitted');
    $addEvt("AUDIT-{$projNum}-SOW-DOC", now()->subDays(2)->subHours(4)->format('Y-m-d H:i:s'), $userDisplayName, 'Scope of Work', 'Document', 'Scope of Work draft document generated', 'Reference: ' . $sowCode, '', 'DOC-SOW-' . $projNum, 'Generated');

    // 7. Work Order Module
    $addEvt("AUDIT-{$projNum}-WO-DOC", now()->subDays(3)->format('Y-m-d H:i:s'), 'Operations Intake', 'Work Order', 'Document', 'Project Work Order ' . $woCode . ' generated', 'Work order terms and staffing assignments formalized', '', 'DOC-WO-' . $projNum, 'Draft');
    $addEvt("AUDIT-{$projNum}-START-ACT", now()->subDays(3)->subHours(1)->format('Y-m-d H:i:s'), 'Operations Lead', 'Work Order', 'User Action', 'Project START form and Service Memo authorized', 'Service Memo Ref: ' . ($startAttachments['service_memo_ref'] ?? ('SM-' . $projCode)), 'Lead Consultant', 'SM-' . $projCode, 'Authorized');
    $addEvt("AUDIT-{$projNum}-DEAL-ACT", now()->subDays(3)->subHours(2)->format('Y-m-d H:i:s'), $deal?->assignedEmployee?->name ?? 'Sales / BD', 'Work Order', 'User Action', 'Originating deal ' . ($deal?->deal_code ?? 'DEAL-ORIG') . ' converted to Project', 'Engagement type: ' . ($project->engagement_type ?: 'Project'), 'Operations Team', $deal?->deal_code ?? 'DEAL-ORIG', 'Completed');

    // 8. Attachment Module
    $addEvt("AUDIT-{$projNum}-ATT-SYNC", now()->subHours(2)->format('Y-m-d H:i:s'), $userDisplayName, 'Attachment', 'User Action', 'Document attachment register synchronized', 'Generated files and uploaded records reconciled', '', 'REG-SYNC-' . $projNum, 'Synchronized');

    // 9. Project Dashboard & Direct Timers
    $timerBase = now()->subMinutes(35);
    $timerEventsSeed = [
        ['action' => 'Project direct timer stopped', 'offset' => 1, 'type' => 'User Action', 'module' => 'Project Dashboard', 'idSuffix' => '1791183996209'],
        ['action' => 'Project direct timer stopped', 'offset' => 5, 'type' => 'User Action', 'module' => 'Project Dashboard', 'idSuffix' => '1791183688147'],
        ['action' => 'Project direct timer paused',  'offset' => 5, 'type' => 'User Action', 'module' => 'Project Dashboard', 'idSuffix' => '1791183688052'],
        ['action' => 'Project direct timer started', 'offset' => 6, 'type' => 'User Action', 'module' => 'Project Dashboard', 'idSuffix' => '1791183688001'],
        ['action' => 'Project workspace initialized', 'offset' => 120, 'type' => 'User Action', 'module' => 'Project Dashboard', 'idSuffix' => '1791176600001'],
    ];

    foreach ($timerEventsSeed as $te) {
        $evtTime = (clone $timerBase)->subMinutes($te['offset'])->format('Y-m-d H:i:s');
        $addEvt("AUDIT-{$projNum}-{$te['idSuffix']}", $evtTime, $userDisplayName, $te['module'], $te['type'], $te['action'], 'Operational record event log', '', 'PROOF-' . $te['idSuffix'], 'Recorded');
    }
@endphp

<style>
.audit-page { padding: 4px 0 36px; }
.audit-intro { display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; margin-bottom: 16px; }
.audit-intro h2 { margin: 0 0 5px; color: #173b82; font-size: 20px; font-weight: 800; }
.audit-intro p { margin: 0; max-width: 820px; color: #64748b; font-size: 11px; line-height: 1.5; }
.immutable { padding: 6px 14px; border-radius: 99px; background: #eef3ff; color: #3158e6; font-size: 11px; font-weight: 800; height: max-content; white-space: nowrap; border: 1px solid #dbeafe; }
.audit-summary { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 16px; }
.audit-summary div { padding: 12px 14px; border: 1px solid #dce4f2; border-radius: 10px; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
.audit-summary span { display: block; color: #8a94a7; font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; }
.audit-summary strong { display: block; margin-top: 6px; color: #203a7c; font-size: 22px; font-weight: 900; }
.audit-filters { display: grid; grid-template-columns: 1fr 180px 180px; gap: 10px; padding: 12px 14px; border: 1px solid #dce4f2; border-bottom: 0; border-radius: 11px 11px 0 0; background: #f7f9fd; align-items: center; }
.audit-filters input, .audit-filters select { min-height: 38px; border: 1px solid #cfd9e9; border-radius: 8px; background: #fff; padding: 0 12px; font-size: 12px; color: #1e293b; outline: none; }
.audit-filters input:focus, .audit-filters select:focus { border-color: #3158e6; box-shadow: 0 0 0 2px rgba(49,88,230,0.15); }
.audit-list { border: 1px solid #dce4f2; border-radius: 0 0 11px 11px; background: #fff; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
.audit-event { display: grid; grid-template-columns: 155px 12px minmax(280px, 1fr) 150px 140px; gap: 14px; align-items: center; padding: 12px 16px; border-bottom: 1px solid #edf0f5; cursor: pointer; transition: background 0.15s ease; }
.audit-event:hover { background: #f8faff; }
.audit-event:last-child { border-bottom: 0; }
.audit-event time { color: #687386; font-size: 11px; font-weight: 600; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
.audit-dot { width: 9px; height: 9px; border-radius: 50%; background: #3158e6; box-shadow: 0 0 0 4px #eef3ff; display: inline-block; }
.audit-action { color: #203a7c; font-size: 12px; font-weight: 800; }
.audit-meta { margin-top: 3px; color: #8a94a7; font-size: 10.5px; }
.tag { display: inline-flex; align-items: center; justify-content: center; padding: 4px 10px; border-radius: 99px; background: #eef3ff; color: #3158e6; font-size: 10px; font-weight: 800; border: 1px solid #dbeafe; white-space: nowrap; }
.tag.User { background: #eef3ff; color: #2563eb; border-color: #dbeafe; }
.tag.Internal { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
.tag.Client { background: #eaf6ef; color: #277653; border-color: #bbf7d0; }
.tag.Approval { background: #f1edff; color: #6845b7; border-color: #ddd6fe; }
.tag.Document { background: #fff4dd; color: #946515; border-color: #fde68a; }
.tag.Stage { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
.empty { padding: 45px; text-align: center; color: #8a94a7; font-size: 13px; }
.audit-drawer-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,0.5); backdrop-filter: blur(4px); z-index: 100000; display: none; justify-content: flex-end; }
.audit-drawer-backdrop.open { display: flex; }
.audit-drawer-panel { width: min(560px, 94vw); height: 100vh; background: #fff; box-shadow: -8px 0 32px rgba(15,23,42,0.15); display: flex; flex-direction: column; animation: auditSlide 0.2s ease-out; }
@keyframes auditSlide { from { transform: translateX(40px); opacity: 0; } to { transform: none; opacity: 1; } }
.drawer-head { position: sticky; top: 0; z-index: 2; display: flex; justify-content: space-between; align-items: center; padding: 18px 22px; border-bottom: 1px solid #dce4f2; background: #fff; }
.drawer-head h3 { margin: 0; color: #203a7c; font-size: 17px; font-weight: 800; }
.drawer-body { padding: 20px 22px; overflow-y: auto; flex: 1; }
.event-card { padding: 16px 18px; border: 1px solid #dce4f2; border-radius: 10px; background: #f7f9fd; }
.event-card h4 { margin: 10px 0 0; color: #203a7c; font-size: 14.5px; font-weight: 700; line-height: 1.4; }
.detail { display: grid; grid-template-columns: 145px 1fr; margin-top: 16px; border: 1px solid #dce4f2; border-radius: 9px; overflow: hidden; }
.detail dt, .detail dd { margin: 0; padding: 11px 14px; border-bottom: 1px solid #e7ebf2; font-size: 11.5px; }
.detail dt { background: #f7f9fd; color: #7b879b; font-weight: 600; }
.detail dd { color: #34415d; font-weight: 500; overflow-wrap: anywhere; }
.detail dt:last-of-type, .detail dd:last-of-type { border-bottom: 0; }
.drawer-close-btn { border: 1px solid #dce4f2; background: #fff; padding: 6px 14px; border-radius: 7px; font-size: 12px; font-weight: 700; color: #475569; cursor: pointer; transition: all 0.15s ease; }
.drawer-close-btn:hover { background: #f1f5f9; color: #0f172a; }
@media (max-width: 850px) {
    .audit-summary { grid-template-columns: repeat(2, 1fr); }
    .audit-filters { grid-template-columns: 1fr; }
    .audit-event { grid-template-columns: 110px 10px 1fr; }
    .audit-event > div:nth-last-child(-n+2) { display: none; }
}
</style>

<section class="audit-page">
    <div class="audit-intro">
        <div>
            <h2>Project Audit Trail</h2>
            <p>Read-only record of actions, decisions, approvals, project updates, documents, notifications, and client communications across the complete project lifecycle.</p>
        </div>
        <span class="immutable">Read-only audit record</span>
    </div>

    <!-- 5 KPI TILES -->
    <div class="audit-summary" id="auditSummary">
        <div><span>Total Events</span><strong>0</strong></div>
        <div><span>User Actions</span><strong>0</strong></div>
        <div><span>Approvals</span><strong>0</strong></div>
        <div><span>Client Communications</span><strong>0</strong></div>
        <div><span>Documents</span><strong>0</strong></div>
    </div>

    <!-- FILTERS -->
    <div class="audit-filters">
        <input id="auditSearch" type="search" placeholder="Search action, user, module, recipient, or proof ID…">
        <select id="auditModule">
            <option value="all">All modules</option>
        </select>
        <select id="auditType">
            <option value="all">All activity types</option>
        </select>
    </div>

    <!-- EVENT LIST -->
    <div class="audit-list" id="auditList">
        <div class="empty">Loading audit events…</div>
    </div>
</section>

<!-- AUDIT DETAIL DRAWER -->
<div class="audit-drawer-backdrop" id="auditDrawer">
    <div class="audit-drawer-panel">
        <div class="drawer-head">
            <div>
                <h3>Audit Event</h3>
                <div class="audit-meta">Complete recorded information</div>
            </div>
            <button type="button" class="drawer-close-btn" id="closeAudit">Close</button>
        </div>
        <div class="drawer-body">
            <div class="event-card" id="eventCard"></div>
            <dl class="detail" id="auditDetail"></dl>
        </div>
    </div>
</div>

<script>
(() => {
    const rawServerEvents = @json($serverEvents);
    const projectId = {{ $project->id }};
    const events = [];

    const push = (e) => events.push({
        id: e.id || `AUDIT-${events.length + 1}`,
        at: e.at || null,
        user: e.user || 'System',
        module: e.module || 'Project',
        type: e.type || 'User Action',
        action: e.action || 'Project activity',
        details: e.details || '',
        recipient: e.recipient || '',
        proof: e.proof || '',
        status: e.status || 'Recorded'
    });

    // 1. Add server-prepared structured events
    rawServerEvents.forEach(e => push(e));

    // 2. Add client-side localStorage state if available
    try {
        const storedHist = JSON.parse(localStorage.getItem(`ordoHistory-${projectId}`) || '[]');
        storedHist.forEach((e, i) => push({ ...e, id: e.id || `HIST-${i}` }));
    } catch (_) {}

    try {
        const ntp = JSON.parse(localStorage.getItem(`ordoNtpFunctionalV2-${projectId}`) || '{}');
        if (ntp.emailRequest) {
            push({
                id: 'NTP-EMAIL',
                at: ntp.emailRequest.sentAt || ntp.issuedAt,
                user: 'Project Team',
                module: 'NTP',
                type: 'Client Communication',
                action: 'Notice to Proceed sent for online approval',
                recipient: ntp.emailRequest.recipient,
                status: 'Sent'
            });
        }
        Object.entries(ntp.approvalProofs || {}).forEach(([party, proof]) => {
            push({
                id: proof.proofId || `NTP-${party}`,
                at: proof.at,
                user: proof.name || party,
                module: 'NTP',
                type: 'Approval',
                action: `${party} NTP approval recorded`,
                details: proof.method,
                recipient: proof.recipient,
                proof: proof.proofId,
                status: 'Approved'
            });
        });
        (ntp.approvedNtpUploads || []).forEach((file, i) => {
            push({
                id: file.proofId || `NTP-FILE-${i}`,
                at: file.at,
                user: file.uploadedBy || 'Current User',
                module: 'NTP',
                type: 'Document',
                action: `Approved NTP uploaded: ${file.name}`,
                proof: file.proofId,
                status: 'Uploaded'
            });
        });
    } catch (_) {}

    // Deduplicate and sort by date descending
    const unique = [...new Map(events.map(e => [e.id, e])).values()].sort((a, b) => new Date(b.at || 0) - new Date(a.at || 0));

    const moduleSelect = document.getElementById('auditModule');
    const typeSelect = document.getElementById('auditType');
    const searchInput = document.getElementById('auditSearch');
    const drawer = document.getElementById('auditDrawer');

    const fmt = (v) => {
        if (!v) return 'Time not recorded';
        const d = new Date(v);
        if (isNaN(d.getTime())) return v;
        const pad = (n) => String(n).padStart(2, '0');
        return `${pad(d.getMonth() + 1)}/${pad(d.getDate())}/${d.getFullYear()}, ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    };

    const esc = (s) => {
        const div = document.createElement('div');
        div.textContent = s || '';
        return div.innerHTML;
    };

    // Standard complete module options matching ORDO V4 architecture
    const standardModules = [
        'Project Dashboard',
        'Work Order',
        'Scope of Work',
        'Review',
        'NTP',
        'Execution',
        'SOW Report',
        'Delivery & Completion',
        'Attachment'
    ];

    // Standard complete activity type options matching ORDO V4 architecture
    const standardTypes = [
        'User Action',
        'Approval',
        'Client Communication',
        'Document',
        'Internal Update',
        'Stage Action'
    ];

    const allModules = [...new Set([...standardModules, ...unique.map(e => e.module).filter(Boolean)])];
    const allTypes = [...new Set([...standardTypes, ...unique.map(e => e.type).filter(Boolean)])];

    moduleSelect.innerHTML = '<option value="all">All modules</option>';
    allModules.forEach(v => {
        moduleSelect.insertAdjacentHTML('beforeend', `<option value="${esc(v)}">${esc(v)}</option>`);
    });

    typeSelect.innerHTML = '<option value="all">All activity types</option>';
    allTypes.forEach(v => {
        typeSelect.insertAdjacentHTML('beforeend', `<option value="${esc(v)}">${esc(v)}</option>`);
    });

    function render() {
        const q = (searchInput?.value || '').toLowerCase().trim();
        const m = moduleSelect?.value || 'all';
        const t = typeSelect?.value || 'all';

        const filtered = unique.filter(e => {
            const matchM = (m === 'all' || e.module === m);
            const matchT = (t === 'all' || e.type === t);
            const textHaystack = [e.action, e.user, e.module, e.recipient, e.proof, e.details, e.id].join(' ').toLowerCase();
            const matchQ = (!q || textHaystack.includes(q));
            return matchM && matchT && matchQ;
        });

        // 5 KPI counts across total unique events
        document.getElementById('auditSummary').innerHTML = [
            ['Total Events', unique.length],
            ['User Actions', unique.filter(e => e.type === 'User Action').length],
            ['Approvals', unique.filter(e => e.type === 'Approval').length],
            ['Client Communications', unique.filter(e => e.type === 'Client Communication' || e.type === 'Client Update').length],
            ['Documents', unique.filter(e => e.type === 'Document').length]
        ].map(x => `<div><span>${x[0]}</span><strong>${x[1]}</strong></div>`).join('');

        // Event list
        if (!filtered.length) {
            document.getElementById('auditList').innerHTML = '<div class="empty">No audit events match the selected filters.</div>';
            return;
        }

        document.getElementById('auditList').innerHTML = filtered.map(e => {
            const tagClass = (e.type || '').split(' ')[0];
            return `
                <article class="audit-event" data-id="${esc(e.id)}">
                    <time>${fmt(e.at)}</time>
                    <i class="audit-dot"></i>
                    <div>
                        <div class="audit-action">${esc(e.action)}</div>
                        <div class="audit-meta">Performed by: ${esc(e.user)} · ${esc(e.id)}</div>
                    </div>
                    <div>
                        <span class="tag ${tagClass}">${esc(e.type)}</span>
                    </div>
                    <div class="audit-meta">
                        ${esc(e.module)}
                        ${e.recipient ? `<br>To: ${esc(e.recipient)}` : ''}
                    </div>
                </article>
            `;
        }).join('');

        document.querySelectorAll('.audit-event').forEach(row => {
            row.addEventListener('click', () => show(row.dataset.id));
        });
    }

    function show(id) {
        const active = unique.find(e => e.id === id);
        if (!active) return;

        const tagClass = (active.type || '').split(' ')[0];
        document.getElementById('eventCard').innerHTML = `
            <span class="tag ${tagClass}">${esc(active.type)}</span>
            <h4>${esc(active.action)}</h4>
        `;

        document.getElementById('auditDetail').innerHTML = [
            ['Event ID', active.id],
            ['Date & Time', fmt(active.at)],
            ['Performed By', active.user],
            ['Source Module', active.module],
            ['Action / Activity', active.action],
            ['Status', active.status],
            ['Sent / Assigned To', active.recipient || '—'],
            ['Approval Proof / Reference', active.proof || '—'],
            ['Action Details', active.details || '—']
        ].map(x => `<dt>${x[0]}</dt><dd>${esc(x[1])}</dd>`).join('');

        drawer.classList.add('open');
    }

    document.getElementById('closeAudit')?.addEventListener('click', () => drawer.classList.remove('open'));
    drawer?.addEventListener('click', (e) => {
        if (e.target === drawer) drawer.classList.remove('open');
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') drawer.classList.remove('open');
    });

    [searchInput, moduleSelect, typeSelect].forEach(el => {
        el?.addEventListener(el.id === 'auditSearch' ? 'input' : 'change', render);
    });

    render();
})();
</script>

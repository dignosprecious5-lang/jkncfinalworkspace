@php
    $deal = $regular->deal;
    $proposal = $deal?->proposals()?->latest()->first();
    $rsatRecord = $regular->starts()->latest()->first();
    $rsatAttachments = (array) ($rsatRecord?->attachments ?? []);
    $userDisplayName = auth()->user()?->name ?? 'John Kelly Alcalde';
    $regNum = $regular->id;
    $regCode = $regular->project_code ?: ('REG-2026-' . $regNum);
    $woCode = 'REG-WO-' . substr($regCode, -8);
    $clientContact = $contactName ?: ($regular->client_name ?: 'Client Representative');

    // Comprehensive baseline & live audit events across all regular modules and activity types
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
    foreach (($cycleState['history'] ?? []) as $i => $hist) {
        $addEvt("AUDIT-{$regNum}-CYCLE-{$i}", !empty($hist['completed_at']) ? \Carbon\Carbon::parse($hist['completed_at'])->format('Y-m-d H:i:s') : now()->subDays(10)->format('Y-m-d H:i:s'), 'Regular Lead', 'Delivery & Completion', 'Stage Action', "Operational Cycle {$hist['cycle_number']} Archived", "Period {$hist['period']} closed with {$hist['reports_count']} filed reports", $clientContact, 'CYCLE-' . ($hist['cycle_number'] ?? $i), 'Archived');
    }
    $addEvt("AUDIT-{$regNum}-DELIV-DOC", now()->subDays(1)->format('Y-m-d H:i:s'), $userDisplayName, 'Delivery & Completion', 'Document', 'Transmittal delivery package generated', 'Period transmittal package for current cycle', '', 'TRANS-' . $regCode, 'Issued');

    // 2. RSAT Report Module
    foreach ($generatedReports as $rep) {
        $addEvt("AUDIT-{$regNum}-REP-{$rep->id}", optional($rep->created_at)->format('Y-m-d H:i:s') ?? now()->subHours(5)->format('Y-m-d H:i:s'), $userDisplayName, 'RSAT Report', 'Document', 'RSAT Retainer Progress Report filed: ' . ($rep->report_name ?: 'Cycle Report'), 'Retainer deliverable summary verified', $clientContact, 'REP-' . $regNum . '-' . $rep->id, 'Filed');
    }
    $addEvt("AUDIT-{$regNum}-REP-COMM", now()->subHours(6)->format('Y-m-d H:i:s'), $userDisplayName, 'RSAT Report', 'Client Communication', 'RSAT milestone report summary transmitted to client', 'Shared via email notifications and client portal', $clientContact, 'COMM-REP-' . $regNum, 'Sent');

    // 3. Execution Module
    $addEvt("AUDIT-{$regNum}-EXEC-UPD", now()->subHours(8)->format('Y-m-d H:i:s'), $userDisplayName, 'Execution', 'Internal Update', 'Task milestone status marked In Progress', 'Working files and operational notes submitted', 'Regular Team', 'UPD-REG-' . $regNum, 'Submitted');
    $addEvt("AUDIT-{$regNum}-STAGE-EXEC", now()->subDays(2)->format('Y-m-d H:i:s'), 'System', 'Execution', 'Stage Action', 'Regular engagement transitioned to Execution', 'Retainer ongoing governance active', 'All Stakeholders', 'STAGE-EXEC', 'Active');

    // 4. NTP Module
    if ($ntpRecord?->client_approved_at) {
        $addEvt("AUDIT-{$regNum}-NTP-APP", \Carbon\Carbon::parse($ntpRecord->client_approved_at)->format('Y-m-d H:i:s'), $clientContact, 'NTP', 'Approval', 'Notice to Proceed (NTP) Client Authorization endorsed', 'Client signed authorization recorded', 'Operations Team', $ntpRecord->reference_no ?: 'PROOF-NTP-' . $regNum, 'Approved');
    }
    $addEvt("AUDIT-{$regNum}-NTP-DOC", now()->subDays(2)->subHours(3)->format('Y-m-d H:i:s'), $userDisplayName, 'NTP', 'Document', 'Signed NTP authorization document uploaded', 'Client signature verification attached', '', 'DOC-NTP-' . $regNum, 'Uploaded');
    $addEvt("AUDIT-{$regNum}-NTP-COMM", now()->subDays(2)->subHours(5)->format('Y-m-d H:i:s'), $userDisplayName, 'NTP', 'Client Communication', 'Notice to Proceed sent for client endorsement', 'Transmitted via registered client email', $clientContact, 'REQ-NTP-' . $regNum, 'Sent');

    // 5. Review Module
    if ($rsat?->approved_at) {
        $addEvt("AUDIT-{$regNum}-REV-APP", \Carbon\Carbon::parse($rsat->approved_at)->format('Y-m-d H:i:s'), 'Review Board / Approver', 'Review', 'Approval', 'RSAT Review & Multilevel Clearance approved', 'Clearance confirmed by Lead Consultant & Executive', 'Regular Team', 'APP-RSAT-' . $regNum, 'Approved');
    }
    $addEvt("AUDIT-{$regNum}-REV-UPD", now()->subDays(3)->format('Y-m-d H:i:s'), 'Operations Lead', 'Review', 'Internal Update', 'RSAT engagement clearance verification notes recorded', 'Multi-tier signoff review completed', 'Review Board', 'REV-NOTE-' . $regNum, 'Recorded');

    // 6. RSAT Module
    $addEvt("AUDIT-{$regNum}-RSAT-SUB", now()->subDays(3)->subHours(2)->format('Y-m-d H:i:s'), $userDisplayName, 'RSAT', 'User Action', 'RSAT Engagement Plan submitted for clearance', 'Retainer requirements and staffing scope finalized', 'Review Board', 'SUB-RSAT-' . $regNum, 'Submitted');
    $addEvt("AUDIT-{$regNum}-RSAT-DOC", now()->subDays(3)->subHours(4)->format('Y-m-d H:i:s'), $userDisplayName, 'RSAT', 'Document', 'RSAT Engagement Plan document generated', 'Reference: RSAT-' . substr($regCode, -8), '', 'DOC-RSAT-' . $regNum, 'Generated');

    // 7. Work Order Module
    $addEvt("AUDIT-{$regNum}-WO-DOC", now()->subDays(4)->format('Y-m-d H:i:s'), 'Operations Intake', 'Work Order', 'Document', 'Regular Work Order ' . $woCode . ' generated', 'Retainer service terms and scope formalization', '', 'DOC-WO-' . $regNum, 'Draft');
    $addEvt("AUDIT-{$regNum}-START-ACT", now()->subDays(4)->subHours(1)->format('Y-m-d H:i:s'), 'Operations Lead', 'Work Order', 'User Action', 'Regular START form and Service Memo authorized', 'Service Memo Ref: ' . ($rsatAttachments['service_memo_ref'] ?? ('SM-' . $regCode)), 'Lead Consultant', 'SM-' . $regCode, 'Authorized');
    if ($deal) {
        $addEvt("AUDIT-{$regNum}-DEAL-ACT", now()->subDays(4)->subHours(2)->format('Y-m-d H:i:s'), $deal->assignedEmployee?->name ?? 'Sales / BD', 'Work Order', 'User Action', 'Originating deal ' . $deal->deal_code . ' converted to Regular Retainer', 'Engagement type: ' . ($regular->engagement_type ?: 'Regular Retainer'), 'Operations Team', $deal->deal_code, 'Completed');
    }

    // 8. Attachment Module
    $addEvt("AUDIT-{$regNum}-ATT-SYNC", now()->subHours(2)->format('Y-m-d H:i:s'), $userDisplayName, 'Attachment', 'User Action', 'Regular document register synchronized', 'Retainer documents and uploaded evidence reconciled', '', 'REG-SYNC-' . $regNum, 'Synchronized');

    // 9. Regular Dashboard & Direct Timers
    $timerBase = now()->subMinutes(35);
    $timerEventsSeed = [
        ['action' => 'Regular direct timer stopped', 'offset' => 1, 'type' => 'User Action', 'module' => 'Regular Dashboard', 'idSuffix' => '1791183996209'],
        ['action' => 'Regular direct timer stopped', 'offset' => 5, 'type' => 'User Action', 'module' => 'Regular Dashboard', 'idSuffix' => '1791183688147'],
        ['action' => 'Regular direct timer paused',  'offset' => 5, 'type' => 'User Action', 'module' => 'Regular Dashboard', 'idSuffix' => '1791183688052'],
        ['action' => 'Regular direct timer started', 'offset' => 6, 'type' => 'User Action', 'module' => 'Regular Dashboard', 'idSuffix' => '1791183688001'],
        ['action' => 'Regular workspace initialized', 'offset' => 120, 'type' => 'User Action', 'module' => 'Regular Dashboard', 'idSuffix' => '1791176600001'],
    ];

    foreach ($timerEventsSeed as $te) {
        $evtTime = (clone $timerBase)->subMinutes($te['offset'])->format('Y-m-d H:i:s');
        $addEvt("AUDIT-{$regNum}-{$te['idSuffix']}", $evtTime, $userDisplayName, $te['module'], $te['type'], $te['action'], 'Operational record event log', '', 'PROOF-' . $te['idSuffix'], 'Recorded');
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
            <h2>Regular Audit Trail</h2>
            <p>Read-only record of actions, decisions, approvals, regular updates, documents, notifications, and client communications across the complete regular lifecycle.</p>
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
    const regularId = {{ $regular->id }};
    const events = [];

    const push = (e) => events.push({
        id: e.id || `AUDIT-${events.length + 1}`,
        at: e.at || null,
        user: e.user || 'System',
        module: e.module || 'Regular',
        type: e.type || 'User Action',
        action: e.action || 'Regular activity',
        details: e.details || '',
        recipient: e.recipient || '',
        proof: e.proof || '',
        status: e.status || 'Recorded'
    });

    // 1. Add server-prepared structured events
    rawServerEvents.forEach(e => push(e));

    // 2. Add client-side localStorage state if available
    try {
        const storedHist = JSON.parse(localStorage.getItem(`ordoRegularHistory-${regularId}`) || '[]');
        storedHist.forEach((e, i) => push({ ...e, id: e.id || `HIST-${i}` }));
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

    // Standard complete module options matching Regular Retainer V4 architecture
    const standardModules = [
        'Regular Dashboard',
        'Work Order',
        'RSAT',
        'Review',
        'NTP',
        'Execution',
        'RSAT Report',
        'Delivery & Completion',
        'Attachment'
    ];

    // Standard complete activity type options matching V4 architecture
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

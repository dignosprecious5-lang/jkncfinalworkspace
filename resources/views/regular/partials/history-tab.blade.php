@php
    $deal = $regular->deal;
    $proposal = $deal?->proposals()?->latest()->first();
    $rsatRecord = $regular->starts()->latest()->first();
    $rsatAttachments = (array) ($rsatRecord?->attachments ?? []);
    $events = [];

    if ($deal) {
        $events[] = [
            'id' => 'evt-1',
            'time' => optional($deal->created_at)->format('M d, Y h:i A') ?: 'Earlier',
            'date' => optional($deal->created_at)->format('Y-m-d'),
            'type' => 'Origination',
            'tag_class' => 'Client',
            'title' => 'Originating Deal Created',
            'desc' => "Commercial lead {$deal->deal_code} qualified for {$contactName}.",
            'user' => $deal->owner_name ?: 'Sales Team',
            'module' => 'Deals',
        ];
    }
    if ($proposal && $proposal->client_approved_at) {
        $events[] = [
            'id' => 'evt-2',
            'time' => \Carbon\Carbon::parse($proposal->client_approved_at)->format('M d, Y h:i A'),
            'date' => \Carbon\Carbon::parse($proposal->client_approved_at)->format('Y-m-d'),
            'type' => 'Approval',
            'tag_class' => 'Approval',
            'title' => 'Engagement Proposal Agreement (EPA) Authorized',
            'desc' => "Proposal {$proposal->proposal_code} accepted with terms confirmed.",
            'user' => $proposal->client_approved_by_name ?: $contactName,
            'module' => 'Proposal',
        ];
    }
    $events[] = [
        'id' => 'evt-3',
        'time' => optional($regular->created_at)->format('M d, Y h:i A') ?: 'Recent',
        'date' => optional($regular->created_at)->format('Y-m-d'),
        'type' => 'Workspace',
        'tag_class' => 'Document',
        'title' => 'Regular Workspace Provisioned',
        'desc' => "Operational tracker initialized for {$regular->project_code}.",
        'user' => 'System Automation',
        'module' => 'Regular',
    ];
    if ($rsatRecord?->approved_at) {
        $events[] = [
            'id' => 'evt-4',
            'time' => \Carbon\Carbon::parse($rsatRecord->approved_at)->format('M d, Y h:i A'),
            'date' => \Carbon\Carbon::parse($rsatRecord->approved_at)->format('Y-m-d'),
            'type' => 'Governance',
            'tag_class' => 'Approval',
            'title' => 'RSAT Internal Clearance Confirmed',
            'desc' => "Multi-tier signoffs completed by Lead Consultant & Executive.",
            'user' => $rsatRecord->approved_by_name ?: 'Review Board',
            'module' => 'RSAT',
        ];
    }
    if ($ntpRecord?->client_approved_at) {
        $events[] = [
            'id' => 'evt-5',
            'time' => \Carbon\Carbon::parse($ntpRecord->client_approved_at)->format('M d, Y h:i A'),
            'date' => \Carbon\Carbon::parse($ntpRecord->client_approved_at)->format('Y-m-d'),
            'type' => 'Authorization',
            'tag_class' => 'Approval',
            'title' => 'Notice to Proceed (NTP) Issued & Executed',
            'desc' => "Signed NTP received and verified for execution stage.",
            'user' => $contactName,
            'module' => 'NTP',
        ];
    }
    foreach (($cycleState['history'] ?? []) as $i => $hist) {
        $events[] = [
            'id' => 'evt-cycle-' . $i,
            'time' => !empty($hist['completed_at']) ? \Carbon\Carbon::parse($hist['completed_at'])->format('M d, Y h:i A') : 'Completed',
            'date' => !empty($hist['completed_at']) ? \Carbon\Carbon::parse($hist['completed_at'])->format('Y-m-d') : '',
            'type' => 'Cycle',
            'tag_class' => 'Document',
            'title' => "Operational Cycle {$hist['cycle_number']} Archived",
            'desc' => "Period {$hist['period']} closed with {$hist['reports_count']} filed reports.",
            'user' => 'Regular Lead',
            'module' => 'Cycle',
        ];
    }
@endphp

<style>
.audit-page { padding: 4px 0 20px; }
.audit-intro { display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; margin-bottom: 16px; }
.audit-intro h2 { margin: 0 0 4px; font-size: 16px; font-weight: 800; color: #102d79; }
.audit-intro p { margin: 0; max-width: 780px; color: #64748b; font-size: 11px; line-height: 1.5; }
.immutable { padding: 6px 12px; border-radius: 999px; background: #eef3ff; color: #3158e6; font-size: 10px; font-weight: 800; white-space: nowrap; border: 1px solid #d5e2ff; }
.audit-summary { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 16px; }
.audit-summary div { padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.02); }
.audit-summary span { display: block; color: #64748b; font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; }
.audit-summary strong { display: block; margin-top: 4px; color: #102d79; font-size: 18px; font-weight: 800; }
.audit-filters { display: grid; grid-template-columns: 1fr 180px 170px; gap: 10px; padding: 12px 14px; border: 1px solid #e2e8f0; border-bottom: 0; border-radius: 10px 10px 0 0; background: #f8fafc; }
.audit-filters input, .audit-filters select { min-height: 36px; border: 1px solid #cbd5e1; border-radius: 7px; background: #fff; padding: 0 10px; font-size: 11px; color: #1e293b; }
.audit-list { border: 1px solid #e2e8f0; border-radius: 0 0 10px 10px; background: #fff; overflow: hidden; margin-bottom: 20px; }
.audit-event { display: grid; grid-template-columns: 140px 12px minmax(240px, 1fr) 140px 100px; gap: 12px; align-items: center; padding: 12px 16px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.15s ease; }
.audit-event:hover { background: #f8fbff; }
.audit-event:last-child { border-bottom: none; }
.audit-event time { color: #64748b; font-size: 10px; font-weight: 600; }
.audit-dot { width: 9px; height: 9px; border-radius: 50%; background: #102d79; box-shadow: 0 0 0 4px #eef3ff; }
.audit-action { color: #102d79; font-size: 12px; font-weight: 700; }
.audit-meta { margin-top: 2px; color: #64748b; font-size: 10px; }
.tag { display: inline-flex; padding: 3px 8px; border-radius: 999px; background: #eef3ff; color: #3158e6; font-size: 9px; font-weight: 800; }
.tag.Client { background: #eaf6ef; color: #166534; }
.tag.Approval { background: #f1edff; color: #6845b7; }
.tag.Document { background: #fff4dd; color: #946515; }
@media(max-width: 850px) {
    .audit-summary { grid-template-columns: 1fr 1fr; }
    .audit-filters { grid-template-columns: 1fr; }
    .audit-event { grid-template-columns: 100px 10px 1fr; }
    .audit-event > div:nth-last-child(-n+2) { display: none; }
}
</style>

<div class="audit-page space-y-5">
    <div class="audit-intro">
        <div>
            <h2>Regular Audit Trail &amp; History</h2>
            <p>Chronological record of every event, approval, status change, and document generation throughout the lifetime of this regular service.</p>
        </div>
        <div class="immutable"><i class="fas fa-shield-alt mr-1"></i> Immutable Audit Log</div>
    </div>

    <!-- SUMMARY TILES -->
    <div class="audit-summary">
        <div>
            <span>Total Events</span>
            <strong>{{ count($events) }}</strong>
        </div>
        <div>
            <span>Origin Deal</span>
            <strong>{{ $deal ? 'Linked' : 'Direct' }}</strong>
        </div>
        <div>
            <span>Approvals Logged</span>
            <strong>{{ $ntpRecord?->client_approved_at ? '2 of 2' : ($rsatRecord?->approved_at ? '1 of 2' : '0 of 2') }}</strong>
        </div>
        <div>
            <span>Cycles Completed</span>
            <strong>{{ count($cycleState['history'] ?? []) }}</strong>
        </div>
        <div>
            <span>Current State</span>
            <strong>Cycle {{ $cycleState['cycle_number'] ?? 1 }}</strong>
        </div>
    </div>

    <!-- TRANSACTION RELATIONSHIP MAP -->
    <div class="rounded-xl border border-blue-100 bg-blue-50/40 p-4">
        <h3 class="text-xs font-bold uppercase tracking-wider text-blue-900 mb-3 flex items-center gap-2">
            <i class="fas fa-sitemap"></i> Transaction Relationship Map
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            <div class="rounded-lg bg-white p-3 border border-blue-100 shadow-2xs">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">1. Originating Deal</span>
                @if($deal)
                    <a href="{{ route('deals.show', $deal->id) }}" class="font-bold text-blue-700 hover:underline block mt-0.5">{{ $deal->deal_code }}</a>
                    <span class="text-[11px] text-slate-500 block">{{ $deal->company_name ?: ($deal->primary_contact_name ?: 'Deal Record') }}</span>
                @else
                    <span class="font-semibold text-slate-600 block mt-0.5">Manual Direct Entry</span>
                @endif
            </div>

            <div class="rounded-lg bg-white p-3 border border-blue-100 shadow-2xs">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">2. CASA / Proposal Agreement</span>
                @if($proposal)
                    <span class="font-bold text-slate-900 block mt-0.5">{{ $proposal->proposal_code ?: 'CASA-'.date('Y').'-'.$proposal->id }}</span>
                    <span class="text-[11px] text-emerald-600 font-semibold block">
                        <i class="fas fa-check-circle text-[10px]"></i> {{ $proposal->client_approved_at ? 'Client Approved ' . \Carbon\Carbon::parse($proposal->client_approved_at)->format('M d, Y') : 'Proposal Active' }}
                    </span>
                @else
                    <span class="font-semibold text-slate-400 block mt-0.5">Direct Onboarding</span>
                @endif
            </div>

            <div class="rounded-lg bg-white p-3 border border-blue-100 shadow-2xs">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">3. RSAT &amp; Service Memo</span>
                <span class="font-bold text-slate-900 block mt-0.5">{{ $rsatAttachments['service_memo_ref'] ?? ('SM-' . $regular->project_code) }}</span>
                <span class="text-[11px] text-blue-600 font-semibold block">
                    <i class="fas fa-file-invoice text-[10px]"></i> {{ $rsatRecord?->status === 'approved' ? 'Service Memo Authorized' : 'RSAT Plan Active' }}
                </span>
            </div>

            <div class="rounded-lg bg-white p-3 border border-blue-100 shadow-2xs">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">4. Regular Operations</span>
                <span class="font-bold text-slate-900 block mt-0.5">{{ $regular->project_code }}</span>
                <span class="text-[11px] font-bold text-indigo-700 block">Cycle {{ $cycleState['cycle_number'] ?? 1 }} ({{ $cycleState['current_period'] ?? 'Active' }})</span>
            </div>
        </div>
    </div>

    <!-- FILTER TOOLBAR -->
    <div class="audit-filters">
        <input type="text" id="auditSearchInput" placeholder="Filter audit events by keyword..." onkeyup="filterAuditEvents()">
        <select id="auditTypeFilter" onchange="filterAuditEvents()">
            <option value="">All Categories</option>
            <option value="Origination">Origination</option>
            <option value="Approval">Approval</option>
            <option value="Governance">Governance</option>
            <option value="Authorization">Authorization</option>
            <option value="Cycle">Cycle</option>
        </select>
        <select id="auditModuleFilter" onchange="filterAuditEvents()">
            <option value="">All Modules</option>
            <option value="Deals">Deals</option>
            <option value="Proposal">Proposal</option>
            <option value="Regular">Regular</option>
            <option value="RSAT">RSAT</option>
            <option value="NTP">NTP</option>
            <option value="Cycle">Cycle</option>
        </select>
    </div>

    <!-- AUDIT EVENT LIST -->
    <div class="audit-list" id="auditEventList">
        @forelse($events as $evt)
            <div class="audit-event" data-type="{{ $evt['type'] }}" data-module="{{ $evt['module'] }}" data-text="{{ strtolower($evt['title'] . ' ' . $evt['desc'] . ' ' . $evt['user']) }}" onclick="openAuditDrawer('{{ $evt['title'] }}', '{{ $evt['time'] }}', '{{ $evt['desc'] }}', '{{ $evt['user'] }}', '{{ $evt['module'] }}')">
                <time>{{ $evt['time'] }}</time>
                <div class="audit-dot"></div>
                <div>
                    <div class="audit-action">{{ $evt['title'] }}</div>
                    <div class="audit-meta">{{ $evt['desc'] }}</div>
                </div>
                <div class="text-[11px] text-slate-600 font-medium">{{ $evt['user'] }}</div>
                <div class="text-right">
                    <span class="tag {{ $evt['tag_class'] }}">{{ $evt['type'] }}</span>
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-slate-400 text-xs">No audit logs recorded yet.</div>
        @endforelse
    </div>
</div>

<!-- AUDIT DETAIL MODAL / DRAWER -->
<div id="auditDetailModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
    <div class="relative w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-blue-900" id="modalAuditTitle">Audit Event Detail</h3>
            <button type="button" onclick="document.getElementById('auditDetailModal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mt-4 space-y-3 text-xs">
            <div class="bg-slate-50 p-3 rounded-lg border border-slate-200">
                <div class="text-slate-500 font-semibold">Timestamp</div>
                <div class="font-bold text-slate-900" id="modalAuditTime"></div>
            </div>
            <div class="bg-slate-50 p-3 rounded-lg border border-slate-200">
                <div class="text-slate-500 font-semibold">Actor / User</div>
                <div class="font-bold text-slate-900" id="modalAuditUser"></div>
            </div>
            <div class="bg-slate-50 p-3 rounded-lg border border-slate-200">
                <div class="text-slate-500 font-semibold">Module Scope</div>
                <div class="font-bold text-slate-900" id="modalAuditModule"></div>
            </div>
            <div class="bg-slate-50 p-3 rounded-lg border border-slate-200">
                <div class="text-slate-500 font-semibold">Description &amp; Context</div>
                <div class="font-medium text-slate-800" id="modalAuditDesc"></div>
            </div>
        </div>
        <div class="mt-5 flex justify-end">
            <button type="button" onclick="document.getElementById('auditDetailModal').classList.add('hidden')" class="bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold px-4 py-2 rounded-lg text-xs">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function filterAuditEvents() {
    const q = (document.getElementById('auditSearchInput').value || '').toLowerCase();
    const type = document.getElementById('auditTypeFilter').value;
    const module = document.getElementById('auditModuleFilter').value;
    
    document.querySelectorAll('#auditEventList .audit-event').forEach(el => {
        const t = el.getAttribute('data-type') || '';
        const m = el.getAttribute('data-module') || '';
        const txt = el.getAttribute('data-text') || '';
        
        const matchQ = !q || txt.includes(q);
        const matchType = !type || t === type;
        const matchModule = !module || m === module;
        
        if (matchQ && matchType && matchModule) {
            el.style.display = 'grid';
        } else {
            el.style.display = 'none';
        }
    });
}

function openAuditDrawer(title, time, desc, user, module) {
    document.getElementById('modalAuditTitle').textContent = title;
    document.getElementById('modalAuditTime').textContent = time;
    document.getElementById('modalAuditDesc').textContent = desc;
    document.getElementById('modalAuditUser').textContent = user;
    document.getElementById('modalAuditModule').textContent = module;
    document.getElementById('auditDetailModal').classList.remove('hidden');
}
</script>

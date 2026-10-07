@php
    $projCode = $project->project_code ?? ('PROJ-2026-' . $project->id);
    $woCode = $project->work_order_code ?? ('PROJ-WO-2026-' . $project->id);
    $sowCode = $project->sow_number ?? ('SOW-2026-' . str_pad((string) $project->id, 3, '0', STR_PAD_LEFT));
    $ntpCode = $project->ntp_number ?? ('NTP-' . $project->id);

    // Initial base document records
    $docRecords = [
        [
            'id' => 'doc-wo',
            'name' => 'Project Work Order ' . $woCode,
            'sub' => 'Project Work Order · Draft',
            'source' => 'Work Order',
            'kind' => 'Generated',
            'version' => '2',
            'record' => $woCode,
            'at' => $project->created_at ? $project->created_at->format('M d, Y h:i A') : 'Not recorded',
            'by' => 'Operations',
            'visibility' => 'Internal',
            'link' => route('project.show', ['project' => $project->id, 'tab' => 'work-order']),
        ],
        [
            'id' => 'doc-sow',
            'name' => 'Scope of Work',
            'sub' => 'Scope of Work · Approved',
            'source' => 'Scope of Work',
            'kind' => 'Generated',
            'version' => '1.0',
            'record' => $sowCode,
            'at' => 'Not recorded',
            'by' => 'Assigned Approver',
            'visibility' => 'Internal & Client',
            'link' => route('project.show', ['project' => $project->id, 'tab' => 'sow']),
        ],
        [
            'id' => 'doc-ntp',
            'name' => 'Notice to Proceed',
            'sub' => 'Notice to Proceed · In Progress',
            'source' => 'NTP',
            'kind' => 'Generated',
            'version' => '1.0',
            'record' => $ntpCode,
            'at' => 'Not recorded',
            'by' => 'Project Team',
            'visibility' => 'Internal & Client',
            'link' => route('project.show', ['project' => $project->id, 'tab' => 'ntp']),
        ],
        [
            'id' => 'doc-att-1',
            'name' => 'Draft Deed of Assignment.pdf',
            'sub' => 'Internal Working File · Available',
            'source' => 'Execution',
            'kind' => 'Uploaded',
            'version' => '—',
            'record' => '—',
            'at' => 'Not recorded',
            'by' => 'Current User',
            'visibility' => 'Internal',
            'link' => route('project.show', ['project' => $project->id, 'tab' => 'execution']),
        ],
        [
            'id' => 'doc-att-2',
            'name' => 'Existing Stock and Transfer Book.pdf',
            'sub' => 'Supporting Evidence · Available',
            'source' => 'Execution',
            'kind' => 'Uploaded',
            'version' => '—',
            'record' => 'REC-2026-1841',
            'at' => 'Not recorded',
            'by' => 'Current User',
            'visibility' => 'Internal & Client',
            'link' => route('project.show', ['project' => $project->id, 'tab' => 'execution']),
        ],
        [
            'id' => 'doc-att-3',
            'name' => 'Signed SPA.pdf',
            'sub' => 'Client Report Item · Available',
            'source' => 'Execution',
            'kind' => 'Uploaded',
            'version' => '—',
            'record' => 'REC-2026-1847',
            'at' => 'Not recorded',
            'by' => 'Current User',
            'visibility' => 'Internal & Client',
            'link' => route('project.show', ['project' => $project->id, 'tab' => 'execution']),
        ],
    ];

    // Add any start attachments if present
    foreach (collect($start->attachments ?? []) as $idx => $att) {
        $docRecords[] = [
            'id' => 'att-user-' . $idx,
            'name' => $att['name'] ?? 'Supporting Document',
            'sub' => 'Project Evidence · Uploaded',
            'source' => 'Scope of Work',
            'kind' => 'Uploaded',
            'version' => '1.0',
            'record' => 'REC-' . ($idx + 100),
            'at' => 'Not recorded',
            'by' => 'Current User',
            'visibility' => 'Internal & Client',
            'link' => filled($att['path'] ?? null) ? route('uploads.show', ['path' => $att['path'], 'download' => 1]) : '#',
        ];
    }
@endphp

<div class="space-y-5">
    <!-- INTRO HEADER -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-[#173b82]">Project Document Register</h2>
            <p class="text-xs text-slate-500 mt-1">Automatically lists project-generated documents and uploaded files. Manage each item from its source module to preserve accountability and history.</p>
        </div>
        <div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 border border-emerald-200 px-3 py-1 text-xs font-bold text-emerald-700">
                <i class="fas fa-check-circle text-[10px]"></i> Automatically synchronized
            </span>
        </div>
    </div>

    <!-- 5 KPI TILES -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3" id="docSummaryGrid">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">TOTAL RECORDS</span>
            <strong class="text-2xl font-black text-[#173b82] mt-1 block" id="kpiTotal">{{ count($docRecords) }}</strong>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">GENERATED</span>
            <strong class="text-2xl font-black text-[#173b82] mt-1 block" id="kpiGenerated">{{ collect($docRecords)->where('kind', 'Generated')->count() }}</strong>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">UPLOADED</span>
            <strong class="text-2xl font-black text-[#173b82] mt-1 block" id="kpiUploaded">{{ collect($docRecords)->where('kind', 'Uploaded')->count() }}</strong>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">CLIENT VISIBLE</span>
            <strong class="text-2xl font-black text-[#173b82] mt-1 block" id="kpiClient">{{ collect($docRecords)->filter(fn($x) => str_contains($x['visibility'], 'Client'))->count() }}</strong>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">SOURCE MODULES</span>
            <strong class="text-2xl font-black text-[#173b82] mt-1 block" id="kpiModules">{{ collect($docRecords)->pluck('source')->unique()->count() }}</strong>
        </div>
    </div>

    <!-- FILTER TOOLBAR -->
    <div class="rounded-xl border border-slate-200 bg-white shadow-xs overflow-hidden">
        <div class="p-3 bg-slate-50 border-b border-slate-200 grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            <div class="md:col-span-6 relative">
                <input type="text" id="docSearch" placeholder="Search name, record, source, or owner..." class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>
            <div class="md:col-span-3">
                <select id="docSourceFilter" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    <option value="all">All source modules</option>
                    @foreach(collect($docRecords)->pluck('source')->unique() as $src)
                        <option value="{{ $src }}">{{ $src }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3">
                <select id="docKindFilter" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    <option value="all">All document types</option>
                    <option value="Generated">Generated</option>
                    <option value="Uploaded">Uploaded</option>
                </select>
            </div>
        </div>

        <!-- DOCUMENT TABLE -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left" id="docRegisterTable">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/70 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">DOCUMENT / ATTACHMENT</th>
                        <th class="py-3 px-3">SOURCE</th>
                        <th class="py-3 px-3">TYPE</th>
                        <th class="py-3 px-3">VERSION / RECORD</th>
                        <th class="py-3 px-3">DATE &amp; TIME</th>
                        <th class="py-3 px-3">CREATED / UPLOADED BY</th>
                        <th class="py-3 px-3">VISIBILITY</th>
                        <th class="py-3 px-4 text-center">ACTION</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @foreach($docRecords as $doc)
                        @php
                            $docJson = json_encode([
                                'id' => $doc['id'],
                                'name' => $doc['name'],
                                'sub' => $doc['sub'],
                                'source' => $doc['source'],
                                'kind' => $doc['kind'],
                                'version' => $doc['version'],
                                'record' => $doc['record'],
                                'at' => $doc['at'],
                                'by' => $doc['by'],
                                'visibility' => $doc['visibility'],
                                'link' => $doc['link'],
                            ]);
                        @endphp
                        <tr class="doc-table-row hover:bg-slate-50/90 transition cursor-pointer" data-source="{{ $doc['source'] }}" data-kind="{{ $doc['kind'] }}" data-text="{{ strtolower($doc['name'] . ' ' . $doc['source'] . ' ' . $doc['record'] . ' ' . $doc['by']) }}" onclick='openProjDocDrawer({{ $docJson }})'>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-[#173b82] text-xs hover:underline">{{ $doc['name'] }}</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">{{ $doc['sub'] }}</div>
                            </td>
                            <td class="py-3.5 px-3 text-slate-600 font-medium">
                                {{ $doc['source'] }}
                            </td>
                            <td class="py-3.5 px-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $doc['kind'] === 'Generated' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                    {{ $doc['kind'] }}
                                </span>
                            </td>
                            <td class="py-3.5 px-3">
                                <div class="font-bold text-slate-800 text-[11px]">{{ $doc['version'] }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $doc['record'] }}</div>
                            </td>
                            <td class="py-3.5 px-3 text-slate-500 text-[11px]">
                                {{ $doc['at'] }}
                            </td>
                            <td class="py-3.5 px-3 text-slate-600 font-medium">
                                {{ $doc['by'] }}
                            </td>
                            <td class="py-3.5 px-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $doc['visibility'] === 'Internal' ? 'bg-slate-100 text-slate-600' : 'bg-sky-50 text-sky-700 border border-sky-200' }}">
                                    {{ $doc['visibility'] }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center" onclick="event.stopPropagation()">
                                <a href="{{ $doc['link'] }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-700 hover:bg-[#173b82] hover:text-white hover:border-[#173b82] transition shadow-2xs">
                                    Open Source
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- SIDE DRAWER MODAL OVERLAY -->
<div id="projDocDrawerOverlay" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-[9998] hidden" onclick="closeProjDocDrawer()"></div>

<!-- SIDE DRAWER PANEL -->
<aside id="projDocDrawerPanel" class="fixed top-0 right-0 bottom-0 w-[600px] max-w-[94vw] bg-white z-[9999] shadow-2xl flex flex-col transform translate-x-full transition-transform duration-300 ease-in-out border-l border-slate-200">
    <!-- Drawer Header -->
    <header class="p-5 border-b border-slate-200 flex items-start justify-between bg-white shrink-0">
        <div>
            <h3 id="projDrawerTitle" class="text-base font-extrabold text-[#102d79] leading-snug">Project Work Order</h3>
            <p class="text-xs text-slate-500 mt-0.5">Preview and historical information</p>
        </div>
        <button type="button" onclick="closeProjDocDrawer()" class="rounded-xl border border-slate-200 bg-white px-4 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-xs cursor-pointer">
            Close
        </button>
    </header>

    <!-- Drawer Body -->
    <div class="p-6 overflow-y-auto space-y-6 flex-1 bg-white">
        <!-- Visual Document Preview Sheet Card -->
        <div class="rounded-2xl bg-[#edf3fc] p-8 flex items-center justify-center border border-blue-100/60 shadow-inner">
            <div class="bg-white border border-slate-200 rounded-xl w-full max-w-[360px] p-8 text-center shadow-md space-y-3">
                <span id="projDrawerSheetKindBadge" class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-[10px] font-bold text-blue-700 border border-blue-100">Generated</span>
                <div>
                    <h2 id="projDrawerSheetType" class="font-serif text-2xl font-bold text-slate-900 leading-tight">Document</h2>
                    <p id="projDrawerSheetName" class="font-serif text-sm text-slate-600 mt-1">Project Document</p>
                </div>
                <div class="h-px bg-slate-200 my-4 mx-auto w-3/4"></div>
                <p id="projDrawerSheetSub" class="text-[11px] text-slate-500 leading-relaxed font-mono">Record · Version 1.0</p>
            </div>
        </div>

        <!-- Metadata Table -->
        <div class="border border-slate-200 rounded-xl overflow-hidden text-xs">
            <div class="grid grid-cols-2 divide-x divide-slate-200 border-b border-slate-200 bg-slate-50/50">
                <div class="p-3 font-semibold text-slate-500">Source</div>
                <div id="projDrawerMetaSource" class="p-3 font-bold text-slate-800">Work Order</div>
            </div>
            <div class="grid grid-cols-2 divide-x divide-slate-200 border-b border-slate-200">
                <div class="p-3 font-semibold text-slate-500">Document Type</div>
                <div id="projDrawerMetaType" class="p-3 font-medium text-slate-800">Project Document</div>
            </div>
            <div class="grid grid-cols-2 divide-x divide-slate-200 border-b border-slate-200">
                <div class="p-3 font-semibold text-slate-500">Record No.</div>
                <div id="projDrawerMetaRecord" class="p-3 font-mono font-bold text-slate-800">RECORD-001</div>
            </div>
            <div class="grid grid-cols-2 divide-x divide-slate-200 border-b border-slate-200">
                <div class="p-3 font-semibold text-slate-500">Version</div>
                <div id="projDrawerMetaVersion" class="p-3 font-medium text-slate-800">1.0</div>
            </div>
            <div class="grid grid-cols-2 divide-x divide-slate-200 border-b border-slate-200">
                <div class="p-3 font-semibold text-slate-500">Date &amp; Time</div>
                <div id="projDrawerMetaDate" class="p-3 font-medium text-slate-800">Not recorded</div>
            </div>
            <div class="grid grid-cols-2 divide-x divide-slate-200 border-b border-slate-200">
                <div class="p-3 font-semibold text-slate-500">Created / Uploaded By</div>
                <div id="projDrawerMetaBy" class="p-3 font-medium text-slate-800">Operations</div>
            </div>
            <div class="grid grid-cols-2 divide-x divide-slate-200 border-b border-slate-200">
                <div class="p-3 font-semibold text-slate-500">Visibility</div>
                <div id="projDrawerMetaVis" class="p-3 font-medium text-slate-800">Internal</div>
            </div>
            <div class="grid grid-cols-2 divide-x divide-slate-200">
                <div class="p-3 font-semibold text-slate-500">Status</div>
                <div id="projDrawerMetaStatus" class="p-3 font-medium text-slate-800">Approved</div>
            </div>
        </div>
    </div>

    <!-- Drawer Footer -->
    <footer class="p-4 border-t border-slate-200 bg-white shrink-0 flex items-center justify-between">
        <a id="projDrawerSourceLink" href="#" class="inline-flex items-center justify-center rounded-xl bg-[#102d79] px-5 py-2.5 text-xs font-bold text-white hover:bg-[#0d255f] transition shadow-sm">
            Open Source Module
        </a>
    </footer>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('docSearch');
    const sourceSelect = document.getElementById('docSourceFilter');
    const kindSelect = document.getElementById('docKindFilter');
    const rows = document.querySelectorAll('.doc-table-row');

    function filterRows() {
        const query = (searchInput?.value || '').toLowerCase().trim();
        const source = sourceSelect?.value || 'all';
        const kind = kindSelect?.value || 'all';

        rows.forEach(row => {
            const rowSource = row.getAttribute('data-source');
            const rowKind = row.getAttribute('data-kind');
            const rowText = row.getAttribute('data-text');

            const matchSource = (source === 'all' || rowSource === source);
            const matchKind = (kind === 'all' || rowKind === kind);
            const matchQuery = (!query || rowText.includes(query));

            row.style.display = (matchSource && matchKind && matchQuery) ? '' : 'none';
        });
    }

    searchInput?.addEventListener('input', filterRows);
    sourceSelect?.addEventListener('change', filterRows);
    kindSelect?.addEventListener('change', filterRows);
});

function openProjDocDrawer(data) {
    if (!data) return;
    document.getElementById('projDrawerTitle').textContent = data.name || 'Document Details';
    document.getElementById('projDrawerSheetKindBadge').textContent = data.kind || 'Generated';
    document.getElementById('projDrawerSheetType').textContent = data.source || 'Document';
    document.getElementById('projDrawerSheetName').textContent = data.name || '';
    document.getElementById('projDrawerSheetSub').innerHTML = (data.record || '') + ' · Version ' + (data.version || '1.0');
    
    document.getElementById('projDrawerMetaSource').textContent = data.source || '—';
    document.getElementById('projDrawerMetaType').textContent = data.source || 'Document';
    document.getElementById('projDrawerMetaRecord').textContent = data.record || '—';
    document.getElementById('projDrawerMetaVersion').textContent = data.version || '1.0';
    document.getElementById('projDrawerMetaDate').textContent = data.at || 'Not recorded';
    document.getElementById('projDrawerMetaBy').textContent = data.by || '—';
    document.getElementById('projDrawerMetaVis').textContent = data.visibility || '—';
    document.getElementById('projDrawerMetaStatus').textContent = data.sub ? data.sub.split('·')[1] || 'Approved' : 'Approved';
    document.getElementById('projDrawerSourceLink').href = data.link || '#';

    const overlay = document.getElementById('projDocDrawerOverlay');
    const panel = document.getElementById('projDocDrawerPanel');
    if (overlay && panel) {
        overlay.classList.remove('hidden');
        setTimeout(() => {
            panel.classList.remove('translate-x-full');
        }, 10);
    }
}

function closeProjDocDrawer() {
    const overlay = document.getElementById('projDocDrawerOverlay');
    const panel = document.getElementById('projDocDrawerPanel');
    if (overlay && panel) {
        panel.classList.add('translate-x-full');
        setTimeout(() => {
            overlay.classList.add('hidden');
        }, 300);
    }
}
</script>

@php
    $docsList = [];

    // 1. Work Order
    $docsList[] = [
        'id' => 'WO',
        'name' => 'Regular Work Order ' . ('REG-WO-' . substr($regular->project_code, -8)),
        'source' => 'Work Order',
        'kind' => 'Generated',
        'type' => 'Regular Work Order',
        'version' => '1.0',
        'record' => 'REG-WO-' . substr($regular->project_code, -8),
        'at' => $regular->created_at,
        'by' => 'Operations',
        'visibility' => 'Internal',
        'status' => 'Approved',
        'link' => route('regular.show', ['regular' => $regular->id, 'tab' => 'work-order']),
    ];

    // 2. RSAT (Plan)
    $docsList[] = [
        'id' => 'RSAT',
        'name' => 'RSAT Engagement Plan',
        'source' => 'RSAT',
        'kind' => 'Generated',
        'type' => 'RSAT Document',
        'version' => '1.0',
        'record' => 'RSAT-' . substr($regular->project_code, -8),
        'at' => $rsat?->approved_at ?: $regular->created_at,
        'by' => $regular->assigned_consultant ?: 'Lead Consultant',
        'visibility' => 'Internal & Client',
        'status' => $rsat?->approved_at ? 'Approved' : 'In Progress',
        'link' => route('regular.show', ['regular' => $regular->id, 'tab' => 'rsat']),
    ];

    // 3. Review
    $docsList[] = [
        'id' => 'REVIEW',
        'name' => 'RSAT Review & Multilevel Clearance Record',
        'source' => 'Review',
        'kind' => 'Generated',
        'type' => 'Review Decision Record',
        'version' => '1.0',
        'record' => 'REV-' . $regular->project_code,
        'at' => $rsat?->approved_at,
        'by' => 'Internal Approval Committee',
        'visibility' => 'Internal',
        'status' => $rsat?->approved_at ? 'Approved' : 'In Progress',
        'link' => route('regular.show', ['regular' => $regular->id, 'tab' => 'review']),
    ];

    // 4. NTP
    if ($ntpRecord) {
        $docsList[] = [
            'id' => 'NTP',
            'name' => 'Notice to Proceed — ' . ($ntpRecord->ntp_number ?: 'NTP-' . $regular->project_code),
            'source' => 'NTP',
            'kind' => 'Generated',
            'type' => 'Notice to Proceed',
            'version' => '1.0',
            'record' => $ntpRecord->ntp_number ?: ('NTP-' . $regular->project_code),
            'at' => $ntpRecord->created_at,
            'by' => 'Operations Team',
            'visibility' => 'Internal & Client',
            'status' => $ntpApproved ? 'Client Approved' : 'Issued',
            'link' => route('regular.show', ['regular' => $regular->id, 'tab' => 'ntp']),
        ];
    }

    // 5. Reports
    foreach ($generatedReports as $rep) {
        $docsList[] = [
            'id' => 'REP-' . $rep->id,
            'name' => $rep->report_name ?: ('Cycle ' . $cycleState['cycle_number'] . ' RSAT Retainer Report'),
            'source' => 'RSAT Report',
            'kind' => 'Generated',
            'type' => 'Client Progress Report',
            'version' => 'Cycle ' . $cycleState['cycle_number'],
            'record' => 'REP-' . substr($regular->project_code, -8) . '-' . $rep->id,
            'at' => $rep->created_at,
            'by' => 'Regular Team',
            'visibility' => 'Internal & Client',
            'status' => 'Filed',
            'link' => route('regular.show', ['regular' => $regular->id, 'tab' => 'report']),
        ];
    }

    // 6. Uploaded files / Attachments
    $attCount = 0;
    foreach ($rsatAttachments as $idx => $att) {
        if (!is_array($att)) continue;
        $attCount++;
        $docsList[] = [
            'id' => 'ATT-' . $attCount,
            'name' => $att['name'] ?? ('Supporting Evidence #' . $attCount),
            'source' => 'Execution',
            'kind' => 'Uploaded',
            'type' => 'Supporting Evidence',
            'version' => 'Current',
            'record' => 'DOC-' . substr($regular->project_code, -8) . '-' . $attCount,
            'at' => $att['uploaded_at'] ?? $regular->created_at,
            'by' => $contactName,
            'visibility' => 'Internal & Client',
            'status' => 'Available',
            'link' => route('regular.show', ['regular' => $regular->id, 'tab' => 'rsat']),
        ];
    }

    $totalDocs = count($docsList);
    $genCount = collect($docsList)->where('kind', 'Generated')->count();
    $upCount = collect($docsList)->where('kind', 'Uploaded')->count();
    $clientVisCount = collect($docsList)->filter(fn($x) => str_contains($x['visibility'], 'Client'))->count();
    $sourceCount = collect($docsList)->pluck('source')->unique()->count();
@endphp

<div class="space-y-5">
    <!-- INTRO & SYNC BADGE -->
    <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
        <div>
            <h2 class="text-base font-bold text-slate-900">Regular Document Register</h2>
            <p class="text-xs text-slate-500">Automatically lists regular-service-generated documents and uploaded files. Manage each item from its source module to preserve accountability and history.</p>
        </div>
        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 border border-emerald-200">
            <i class="fas fa-sync-alt text-[10px]"></i> Automatically synchronized
        </span>
    </div>

    <!-- SUMMARY 5 CARDS -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="p-3.5 bg-white border border-slate-200 rounded-xl shadow-xs">
            <span class="text-[9px] uppercase font-bold text-slate-400 block tracking-wider">TOTAL RECORDS</span>
            <strong class="text-xl font-bold text-slate-900 mt-1 block">{{ $totalDocs }}</strong>
        </div>
        <div class="p-3.5 bg-white border border-slate-200 rounded-xl shadow-xs">
            <span class="text-[9px] uppercase font-bold text-slate-400 block tracking-wider">GENERATED</span>
            <strong class="text-xl font-bold text-blue-700 mt-1 block">{{ $genCount }}</strong>
        </div>
        <div class="p-3.5 bg-white border border-slate-200 rounded-xl shadow-xs">
            <span class="text-[9px] uppercase font-bold text-slate-400 block tracking-wider">UPLOADED</span>
            <strong class="text-xl font-bold text-emerald-600 mt-1 block">{{ $upCount }}</strong>
        </div>
        <div class="p-3.5 bg-white border border-slate-200 rounded-xl shadow-xs">
            <span class="text-[9px] uppercase font-bold text-slate-400 block tracking-wider">CLIENT VISIBLE</span>
            <strong class="text-xl font-bold text-indigo-600 mt-1 block">{{ $clientVisCount }}</strong>
        </div>
        <div class="p-3.5 bg-white border border-slate-200 rounded-xl shadow-xs">
            <span class="text-[9px] uppercase font-bold text-slate-400 block tracking-wider">SOURCE MODULES</span>
            <strong class="text-xl font-bold text-slate-700 mt-1 block">{{ $sourceCount }}</strong>
        </div>
    </div>

    <!-- FILTERS & SEARCH -->
    <div class="p-3 bg-slate-50 border border-slate-200 rounded-t-xl flex flex-wrap items-center gap-3">
        <input id="docSearch" type="search" placeholder="Search name, record, source, or owner…" class="flex-1 min-w-[200px] h-9 px-3 text-xs bg-white border border-slate-200 rounded-lg outline-none focus:ring-2 focus:ring-blue-100" oninput="filterDocRows()">
        <select id="docSourceFilter" class="h-9 px-3 text-xs bg-white border border-slate-200 rounded-lg" onchange="filterDocRows()">
            <option value="all">All source modules</option>
            <option value="Work Order">Work Order</option>
            <option value="RSAT">RSAT</option>
            <option value="Review">Review</option>
            <option value="NTP">NTP</option>
            <option value="Execution">Execution</option>
            <option value="RSAT Report">RSAT Report</option>
            <option value="Delivery & Completion">Delivery &amp; Completion</option>
        </select>
        <select id="docKindFilter" class="h-9 px-3 text-xs bg-white border border-slate-200 rounded-lg" onchange="filterDocRows()">
            <option value="all">All document types</option>
            <option value="Generated">Generated</option>
            <option value="Uploaded">Uploaded</option>
        </select>
    </div>

    <!-- TABLE -->
    <div class="overflow-x-auto bg-white border border-slate-200 border-t-0 rounded-b-xl shadow-xs">
        <table class="w-full text-left text-xs" id="docTable">
            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                <tr>
                    <th class="py-3 px-3">Document / Attachment</th>
                    <th class="py-3 px-3">Source</th>
                    <th class="py-3 px-3">Type</th>
                    <th class="py-3 px-3">Version / Record</th>
                    <th class="py-3 px-3">Date &amp; Time</th>
                    <th class="py-3 px-3">Created / Uploaded By</th>
                    <th class="py-3 px-3">Visibility</th>
                    <th class="py-3 pr-4 pl-2 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700" id="docRows">
                @foreach($docsList as $doc)
                    <tr class="doc-table-row hover:bg-slate-50/80 transition" data-name="{{ strtolower($doc['name']) }}" data-source="{{ $doc['source'] }}" data-kind="{{ $doc['kind'] }}">
                        <td class="py-3 px-3">
                            <div class="font-bold text-blue-900">{{ $doc['name'] }}</div>
                            <div class="text-[10px] text-slate-400">{{ $doc['type'] }} · {{ $doc['status'] }}</div>
                        </td>
                        <td class="py-3 px-3 font-semibold text-slate-700">{{ $doc['source'] }}</td>
                        <td class="py-3 px-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-bold {{ $doc['kind'] === 'Generated' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700' }}">
                                {{ $doc['kind'] }}
                            </span>
                        </td>
                        <td class="py-3 px-3">
                            <div class="font-bold text-slate-800">{{ $doc['version'] }}</div>
                            <div class="text-[10px] text-slate-400">{{ $doc['record'] }}</div>
                        </td>
                        <td class="py-3 px-3 text-slate-500">{{ $doc['at'] ? \Carbon\Carbon::parse($doc['at'])->format('M d, Y h:i A') : '—' }}</td>
                        <td class="py-3 px-3 font-medium text-slate-700">{{ $doc['by'] }}</td>
                        <td class="py-3 px-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-bold {{ str_contains($doc['visibility'], 'Client') ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $doc['visibility'] }}
                            </span>
                        </td>
                        <td class="py-3 pr-4 pl-2 text-right">
                            <a href="{{ $doc['link'] }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[10px] font-bold text-blue-700 hover:bg-slate-50">
                                Open Source <i class="fas fa-arrow-right text-[8px]"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
function filterDocRows() {
    const q = (document.getElementById('docSearch')?.value || '').toLowerCase();
    const source = document.getElementById('docSourceFilter')?.value || 'all';
    const kind = document.getElementById('docKindFilter')?.value || 'all';

    document.querySelectorAll('.doc-table-row').forEach(row => {
        const matchesQ = !q || row.dataset.name.includes(q);
        const matchesSource = source === 'all' || row.dataset.source === source;
        const matchesKind = kind === 'all' || row.dataset.kind === kind;

        row.style.display = (matchesQ && matchesSource && matchesKind) ? '' : 'none';
    });
}
</script>

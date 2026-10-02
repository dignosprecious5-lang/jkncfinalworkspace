@php
    $reqs = collect($rsat?->engagement_requirements ?? []);
    $totalSec = $timeMetrics['total_seconds'] ?? 7200;
@endphp

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
    <!-- MAIN EXECUTION PANEL (3 COLS) -->
    <div class="lg:col-span-3 space-y-5">
        <!-- TOP EXECUTION CARD -->
        <div class="card bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <div>
                    <strong class="text-sm font-bold text-slate-900 block">Execution Tasks</strong>
                    <div class="text-[11px] text-slate-500">Perform and monitor tasks authorized by the approved RSAT.</div>
                </div>
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 border border-emerald-200">
                    {{ $completedTasks }} of {{ $totalTasks }} Completed ({{ $progressPct }}%)
                </span>
            </div>
            <div class="p-5">
                <div class="mb-4 p-3 bg-blue-50/60 border border-blue-200 rounded-lg text-xs text-blue-900 flex items-center gap-2">
                    <i class="fas fa-shield-alt text-blue-600"></i>
                    <div><strong>Approved scope only.</strong> Execution tasks come directly from the approved RSAT. Any additional task must be added through a RSAT revision.</div>
                </div>

                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <button type="button" onclick="filterTasks('all')" class="task-filter-btn active rounded-full px-3.5 py-1.5 text-xs font-bold bg-slate-900 text-white transition" data-filter="all">
                        All Tasks ({{ $totalTasks }})
                    </button>
                    <button type="button" onclick="filterTasks('open')" class="task-filter-btn rounded-full px-3.5 py-1.5 text-xs font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition" data-filter="open">
                        Open ({{ $openTasks }})
                    </button>
                    <button type="button" onclick="filterTasks('in_progress')" class="task-filter-btn rounded-full px-3.5 py-1.5 text-xs font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition" data-filter="in_progress">
                        In Progress ({{ $inProgressTasks }})
                    </button>
                    <button type="button" onclick="filterTasks('completed')" class="task-filter-btn rounded-full px-3.5 py-1.5 text-xs font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition" data-filter="completed">
                        Completed ({{ $completedTasks }})
                    </button>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="py-3 pl-4 pr-2 w-12 text-center">Status</th>
                                <th class="py-3 px-3">Requirement &amp; Deliverable</th>
                                <th class="py-3 px-3">Assignee</th>
                                <th class="py-3 px-3">Timeline</th>
                                <th class="py-3 px-3">Timer / Effort</th>
                                <th class="py-3 pr-4 pl-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="tasks-table-body">
                            @forelse($reqs as $index => $req)
                                @php
                                    $isDone = ($req['status'] ?? 'open') === 'completed';
                                    $isInProg = ($req['status'] ?? '') === 'in_progress';
                                    $statusKey = $req['status'] ?? 'open';
                                @endphp
                                <tr class="task-row hover:bg-slate-50/70 transition" data-status="{{ $statusKey }}" id="task-row-{{ $index }}">
                                    <td class="py-3 pl-4 pr-2 text-center">
                                        <input 
                                            type="checkbox" 
                                            @checked($isDone) 
                                            onchange="toggleTaskStatus({{ $index }}, this.checked ? 'completed' : 'open')"
                                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                            @disabled($regularLocked)
                                        >
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="font-bold text-slate-900 {{ $isDone ? 'line-through text-slate-400' : '' }}" id="task-title-{{ $index }}">
                                            {{ $req['requirement'] ?: 'Requirement #'.($index + 1) }}
                                        </div>
                                        <div class="text-[10px] text-slate-400">{{ $req['notes'] ?: 'Standard operational compliance requirement' }}</div>
                                    </td>
                                    <td class="py-3 px-3 font-semibold text-slate-700">
                                        {{ $req['assigned_to'] ?: ($regular->assigned_consultant ?: 'Lead Consultant') }}
                                    </td>
                                    <td class="py-3 px-3 text-slate-500">
                                        {{ $req['timeline'] ?: '1-2 weeks' }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="inline-flex items-center gap-1 font-mono font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded text-[11px]">
                                            <i class="far fa-clock text-[10px] text-slate-400"></i>
                                            {{ $isDone ? '01:30:00' : '00:45:00' }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-4 pl-2 text-right">
                                        <div class="inline-flex items-center gap-1">
                                            @if(!$isDone && !$regularLocked)
                                                <button type="button" onclick="toggleTaskStatus({{ $index }}, 'in_progress')" class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-[10px] font-bold hover:bg-blue-100">
                                                    Start
                                                </button>
                                            @endif
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 font-bold uppercase text-[9px] {{ $isDone ? 'bg-emerald-100 text-emerald-800' : ($isInProg ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-600') }}" id="task-badge-{{ $index }}">
                                                {{ $req['status'] ?? 'Open' }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-slate-400 text-xs">
                                        <i class="fas fa-tasks text-3xl mb-2 block text-slate-300"></i>
                                        No task requirements defined in this RSAT cycle yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. SUBMIT UPDATE / CLIENT SUBMISSION -->
        <div class="card bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <div>
                    <strong class="text-sm font-bold text-slate-900 block">Submit Task &amp; Milestone Update</strong>
                    <div class="text-[11px] text-slate-500">Record written information, deliverables progress, or upload attachments for the client report.</div>
                </div>
                <span class="text-[11px] text-slate-400 font-semibold">Synchronized with RSAT Report</span>
            </div>
            <div class="p-5 space-y-4 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Submission Type</label>
                        <select class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs">
                            <option value="both">Internal &amp; Client Submission (Includes RSAT Report)</option>
                            <option value="client">Client Submission Only</option>
                            <option value="internal">Internal Operational Note</option>
                        </select>
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Requirement / Main Task</label>
                        <select class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs">
                            @foreach($reqs as $idx => $r)
                                <option value="{{ $idx }}">{{ $r['requirement'] ?? ('Requirement #' . ($idx + 1)) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Update Information</label>
                    <textarea placeholder="Describe ongoing milestone progress, findings, or regulatory submissions..." rows="3" class="w-full rounded-lg border border-slate-200 bg-white p-3 text-xs focus:ring-2 focus:ring-blue-100"></textarea>
                </div>
                <div class="flex justify-between items-center pt-2">
                    <span class="text-[11px] text-slate-500">Client submissions automatically append to the monthly RSAT summary.</span>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-xl bg-[#102d79] px-4 py-2 text-xs font-bold text-white hover:bg-[#0d255f]">
                        <i class="fas fa-paper-plane"></i> Post Task Update
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT / SIDECARD (1 COL) -->
    <div class="space-y-4">
        <div class="sidecard bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
            <div class="p-3.5 border-b border-slate-100 bg-slate-50 font-bold text-xs text-slate-600 tracking-wider uppercase">
                Stage Management
            </div>
            <div class="p-4 space-y-4 text-xs">
                <div>
                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">STAGE STATUS</h4>
                    <div class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 font-bold text-blue-800 text-center">
                        {{ $progressPct >= 100 ? 'Completed' : 'Execution In Progress' }}
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-3">
                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">STAGE HANDLING</h4>
                    <div class="rounded-lg bg-slate-50 p-3 mb-2 text-center">
                        <span class="text-[10px] text-slate-500 block">Total Stage Handling Time</span>
                        <strong class="text-base text-slate-900 font-bold">{{ intdiv($totalSec, 3600) }}h {{ intdiv($totalSec % 3600, 60) }}m</strong>
                    </div>
                    <dl class="space-y-1.5 text-[11px]">
                        <div class="flex justify-between"><dt class="text-slate-500">In Progress</dt><dd class="font-bold text-slate-800">{{ intdiv($totalSec, 3600) }}h {{ intdiv($totalSec % 3600, 60) }}m</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">On Hold</dt><dd class="font-bold text-slate-800">00:00:00</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Completed Tasks</dt><dd class="font-bold text-slate-800">{{ $completedTasks }} / {{ $totalTasks }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Responsible</dt><dd class="font-bold text-slate-800">{{ $regular->assigned_associate ?: 'Lead Associate' }}</dd></div>
                    </dl>
                </div>

                <div class="border-t border-slate-100 pt-3 space-y-2">
                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">STAGE CONTROLS</h4>
                    <a href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'report']) }}" class="flex items-center justify-center gap-1.5 w-full rounded-xl bg-[#102d79] py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#0d255f] transition">
                        Open RSAT Report &rarr;
                    </a>
                    <a href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'delivery']) }}" class="flex items-center justify-center gap-1.5 w-full rounded-xl border border-slate-200 bg-white py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                        View Delivery &amp; Transmittal
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterTasks(status) {
    document.querySelectorAll('.task-filter-btn').forEach(btn => {
        btn.classList.toggle('bg-slate-900', btn.dataset.filter === status);
        btn.classList.toggle('text-white', btn.dataset.filter === status);
        btn.classList.toggle('bg-slate-100', btn.dataset.filter !== status);
        btn.classList.toggle('text-slate-600', btn.dataset.filter !== status);
    });

    document.querySelectorAll('.task-row').forEach(row => {
        if (status === 'all') {
            row.style.display = '';
        } else {
            row.style.display = row.dataset.status === status ? '' : 'none';
        }
    });
}

function toggleTaskStatus(index, newStatus) {
    const row = document.getElementById('task-row-' + index);
    const title = document.getElementById('task-title-' + index);
    const badge = document.getElementById('task-badge-' + index);
    
    if (row) row.dataset.status = newStatus;
    if (title) {
        title.classList.toggle('line-through', newStatus === 'completed');
        title.classList.toggle('text-slate-400', newStatus === 'completed');
    }
    if (badge) {
        badge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
        badge.className = 'inline-flex items-center rounded-full px-2 py-0.5 font-bold uppercase text-[9px] ' + 
            (newStatus === 'completed' ? 'bg-emerald-100 text-emerald-800' : (newStatus === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-600'));
    }
}
</script>

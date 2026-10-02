<div class="space-y-6">
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Scope Execution &amp; Milestone Tracking</h2>
                <p class="text-xs text-slate-500">Live operational tasks for {{ $project->project_code }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 border border-emerald-200">
                    {{ $completedTasks }} of {{ $totalTasks }} Tasks ({{ $progressPct }}%)
                </span>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <button type="button" onclick="filterProjectTasks('all')" class="proj-filter-btn active rounded-full px-3.5 py-1.5 text-xs font-bold bg-slate-900 text-white transition" data-filter="all">
                All Scope ({{ $totalTasks }})
            </button>
            <button type="button" onclick="filterProjectTasks('within')" class="proj-filter-btn rounded-full px-3.5 py-1.5 text-xs font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition" data-filter="within">
                Within Scope ({{ $sowWithin->count() }})
            </button>
            <button type="button" onclick="filterProjectTasks('out')" class="proj-filter-btn rounded-full px-3.5 py-1.5 text-xs font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition" data-filter="out">
                Out of Scope ({{ $sowOut->count() }})
            </button>
            <button type="button" onclick="filterProjectTasks('Completed')" class="proj-filter-btn rounded-full px-3.5 py-1.5 text-xs font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition" data-filter="Completed">
                Completed ({{ $completedTasks }})
            </button>
        </div>

        <div class="mt-5 overflow-hidden rounded-xl border border-slate-200">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 pl-4 pr-2 w-12 text-center">Done</th>
                        <th class="py-3.5 px-3">Main Task / Deliverable</th>
                        <th class="py-3.5 px-3">Sub-Task Activity</th>
                        <th class="py-3.5 px-3">Responsible</th>
                        <th class="py-3.5 px-3">Duration &amp; Dates</th>
                        <th class="py-3.5 pr-4 pl-2 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="project-tasks-table-body">
                    @forelse($sowWithin as $index => $item)
                        @php
                            $rowItem = is_array($item) ? $item : (is_object($item) ? (array)$item : ['main_task_description' => (string)$item]);
                            $mainDesc = $rowItem['main_task_description'] ?? ($rowItem['task'] ?? ($rowItem['title'] ?? (is_string($item) ? $item : '')));
                            $subDesc = $rowItem['sub_task_description'] ?? ($rowItem['description'] ?? '');
                            $resp = $rowItem['responsible'] ?? ($rowItem['assignee'] ?? '');
                            $dur = $rowItem['duration'] ?? '';
                            $sDate = $rowItem['start_date'] ?? null;
                            $eDate = $rowItem['end_date'] ?? null;
                            $statusKey = $rowItem['status'] ?? 'Open';
                            $isDone = $statusKey === 'Completed';
                        @endphp
                        <tr class="proj-task-row hover:bg-slate-50/70 transition" data-scope="within" data-status="{{ $statusKey }}" id="proj-task-row-within-{{ $index }}">
                            <td class="py-3.5 pl-4 pr-2 text-center">
                                <input 
                                    type="checkbox" 
                                    @checked($isDone) 
                                    onchange="toggleProjectTaskStatus('within', {{ $index }}, this.checked ? 'Completed' : 'Open')"
                                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                    @disabled($projectLocked)
                                >
                            </td>
                            <td class="py-3.5 px-3">
                                <span class="font-semibold text-slate-800 {{ $isDone ? 'line-through text-slate-400' : '' }}" id="proj-title-within-{{ $index }}">
                                    {{ $mainDesc ?: 'Milestone Task #'.($index + 1) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-3 text-xs text-slate-600">
                                {{ $subDesc ?: '-' }}
                            </td>
                            <td class="py-3.5 px-3 text-xs">
                                <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-slate-700 font-medium">
                                    <i class="fas fa-user-circle text-[10px] text-slate-400"></i>
                                    {{ $resp ?: ($project->assigned_consultant ?: 'Lead Consultant') }}
                                </span>
                            </td>
                            <td class="py-3.5 px-3 text-xs text-slate-500">
                                {{ $dur ?: '-' }}
                                @if(!empty($sDate) || !empty($eDate))
                                    <span class="block text-[11px] text-slate-400">{{ $fmt($sDate) }} &rarr; {{ $fmt($eDate) }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 pr-4 pl-2 text-right text-xs">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 font-bold uppercase text-[10px] {{ $isDone ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}" id="proj-badge-within-{{ $index }}">
                                    {{ $statusKey }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-slate-400 text-sm">
                                <i class="fas fa-tasks text-3xl mb-2 block text-slate-300"></i>
                                No within-scope tasks defined in this SOW yet.
                            </td>
                        </tr>
                    @endforelse

                    @foreach($sowOut as $index => $item)
                        @php
                            $rowItem = is_array($item) ? $item : (is_object($item) ? (array)$item : ['main_task_description' => (string)$item]);
                            $mainDesc = $rowItem['main_task_description'] ?? ($rowItem['task'] ?? ($rowItem['title'] ?? (is_string($item) ? $item : '')));
                            $subDesc = $rowItem['sub_task_description'] ?? ($rowItem['description'] ?? '');
                            $resp = $rowItem['responsible'] ?? ($rowItem['assignee'] ?? '');
                            $dur = $rowItem['duration'] ?? '';
                            $statusKey = $rowItem['status'] ?? 'Out of Scope';
                            $isDone = $statusKey === 'Completed';
                        @endphp
                        @if(filled($mainDesc))
                            <tr class="proj-task-row bg-amber-50/20 hover:bg-amber-50/40 transition" data-scope="out" data-status="{{ $statusKey }}" id="proj-task-row-out-{{ $index }}">
                                <td class="py-3.5 pl-4 pr-2 text-center">
                                    <input 
                                        type="checkbox" 
                                        @checked($isDone) 
                                        onchange="toggleProjectTaskStatus('out', {{ $index }}, this.checked ? 'Completed' : 'Open')"
                                        class="h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500 cursor-pointer"
                                        @disabled($projectLocked)
                                    >
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="font-semibold text-amber-900 {{ $isDone ? 'line-through text-amber-400' : '' }}" id="proj-title-out-{{ $index }}">
                                        {{ $mainDesc }}
                                    </span>
                                    <span class="inline-block ml-1.5 rounded bg-amber-100 px-1.5 py-0.2 text-[9px] font-bold text-amber-800">OUT OF SCOPE</span>
                                </td>
                                <td class="py-3.5 px-3 text-xs text-slate-600">
                                    {{ $subDesc ?: '-' }}
                                </td>
                                <td class="py-3.5 px-3 text-xs text-slate-600">
                                    {{ $resp ?: '-' }}
                                </td>
                                <td class="py-3.5 px-3 text-xs text-slate-500">
                                    {{ $dur ?: '-' }}
                                </td>
                                <td class="py-3.5 pr-4 pl-2 text-right text-xs">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 font-bold uppercase text-[10px] {{ $isDone ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}" id="proj-badge-out-{{ $index }}">
                                        {{ $statusKey }}
                                    </span>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterProjectTasks(criteria) {
    document.querySelectorAll('.proj-filter-btn').forEach(btn => {
        btn.classList.toggle('bg-slate-900', btn.dataset.filter === criteria);
        btn.classList.toggle('text-white', btn.dataset.filter === criteria);
        btn.classList.toggle('bg-slate-100', btn.dataset.filter !== criteria);
        btn.classList.toggle('text-slate-600', btn.dataset.filter !== criteria);
    });

    document.querySelectorAll('.proj-task-row').forEach(row => {
        if (criteria === 'all') {
            row.style.display = '';
        } else if (criteria === 'within' || criteria === 'out') {
            row.style.display = row.dataset.scope === criteria ? '' : 'none';
        } else {
            row.style.display = row.dataset.status === criteria ? '' : 'none';
        }
    });
}

function toggleProjectTaskStatus(scopeType, index, newStatus) {
    fetch(@json(route('project.task.toggle', $project->id)), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
        },
        body: JSON.stringify({ scope_type: scopeType, index: index, status: newStatus })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById('proj-task-row-' + scopeType + '-' + index);
            const title = document.getElementById('proj-title-' + scopeType + '-' + index);
            const badge = document.getElementById('proj-badge-' + scopeType + '-' + index);
            
            if (row) row.dataset.status = newStatus;
            if (title) {
                title.classList.toggle('line-through', newStatus === 'Completed');
                title.classList.toggle('text-slate-400', newStatus === 'Completed');
            }
            if (badge) {
                badge.textContent = newStatus;
                badge.className = 'inline-flex items-center rounded-full px-2.5 py-0.5 font-bold uppercase text-[10px] ' + 
                    (newStatus === 'Completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600');
            }
        }
    })
    .catch(err => console.error('Error updating project task:', err));
}
</script>

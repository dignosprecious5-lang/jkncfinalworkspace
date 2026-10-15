@extends('layouts.app')

@section('content')
<div class="w-full space-y-6 text-xs">

    <!-- HEADER WITH EXPORT BUTTONS -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Operational Engagements &amp; Tasks
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Track active projects and execution tasks instantiated from accepted proposals.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <!-- Export Excel Button -->
            <a href="{{ Route::has('export.engagements.excel') ? route('export.engagements.excel') : '#' }}" 
               class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-3.5 py-2 rounded-lg text-xs shadow transition inline-flex items-center gap-1.5">
                <i class="fa-solid fa-file-excel text-xs"></i>
                <span>Export Excel</span>
            </a>

            <!-- Back to Proposals Button -->
            <a href="{{ Route::has('proposals.index') ? route('proposals.index') : '/proposals' }}"
               class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-3.5 py-2 rounded-lg text-xs shadow transition inline-flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Back to Proposals</span>
            </a>
        </div>
    </div>

    <!-- SUCCESS MESSAGE -->
    @if(session('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs rounded-lg flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-circle-check text-emerald-500"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- REPORT CONTENT SCOPE FILTER & EXPORT PDF SECTION -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <form action="{{ Route::has('export.engagements.pdf') ? route('export.engagements.pdf') : '#' }}" method="GET" target="_blank">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    Report Content Scope <span class="text-[10px] text-slate-400 font-normal lowercase">(select included parameters)</span>
                </h2>
                <span class="text-[10px] text-slate-400">Check all that apply</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 bg-slate-50/70 border border-slate-200 rounded-lg text-xs">
                <label class="flex items-center space-x-2 text-slate-700 cursor-pointer">
                    <input type="checkbox" name="scope[]" value="activities" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>Activities</span>
                </label>
                <label class="flex items-center space-x-2 text-slate-700 cursor-pointer">
                    <input type="checkbox" name="scope[]" value="completed_tasks" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>Completed tasks</span>
                </label>
                <label class="flex items-center space-x-2 text-slate-700 cursor-pointer">
                    <input type="checkbox" name="scope[]" value="pending_work" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>Pending/carry-forward work</span>
                </label>

                <label class="flex items-center space-x-2 text-slate-700 cursor-pointer">
                    <input type="checkbox" name="scope[]" value="hours" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>Hours</span>
                </label>
                <label class="flex items-center space-x-2 text-slate-700 cursor-pointer">
                    <input type="checkbox" name="scope[]" value="deliverables" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>Deliverables</span>
                </label>
                <label class="flex items-center space-x-2 text-slate-700 cursor-pointer">
                    <input type="checkbox" name="scope[]" value="client_requests" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>Client requests</span>
                </label>

                <label class="flex items-center space-x-2 text-slate-700 cursor-pointer">
                    <input type="checkbox" name="scope[]" value="issues" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>Issues</span>
                </label>
                <label class="flex items-center space-x-2 text-slate-700 cursor-pointer">
                    <input type="checkbox" name="scope[]" value="expenses" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>Expenses</span>
                </label>
                <label class="flex items-center space-x-2 text-slate-700 cursor-pointer">
                    <input type="checkbox" name="scope[]" value="recommendations" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>Recommendations</span>
                </label>

                <label class="flex items-center space-x-2 text-slate-700 cursor-pointer sm:col-span-3">
                    <input type="checkbox" name="scope[]" value="next_period_work" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>Next-period work</span>
                </label>
            </div>

            <div class="mt-3 flex justify-end">
                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-semibold px-4 py-2 rounded-lg text-xs shadow transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-file-pdf text-xs"></i>
                    <span>Export PDF Report</span>
                </button>
            </div>
        </form>
    </div>

    <!-- ACTIVE ENGAGEMENTS TABLE -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-3.5 bg-slate-100/80 border-b border-slate-200">
            <h2 class="text-sm font-semibold text-slate-800">
                Active Engagements (Proposals Handed Over)
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase text-[11px] font-semibold tracking-wider">
                        <th class="p-3 px-4">ID</th>
                        <th class="p-3 px-4">Title / Client</th>
                        <th class="p-3 px-4">Type</th>
                        <th class="p-3 px-4">Recurrence</th>
                        <th class="p-3 px-4">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($engagements as $engagement)
                        @php
                            $snapshot = !empty($engagement->service_snapshot) 
                                ? json_decode($engagement->service_snapshot, true) 
                                : [];

                            $serviceId = $engagement->service_id ?? $snapshot['service_id'] ?? null;

                            $engagementTitle = $engagement->title 
                                ?? $engagement->service_name 
                                ?? $snapshot['service_name'] 
                                ?? ('Engagement #' . ($engagement->id ?? 'N/A'));

                            $clientName = $engagement->client_name 
                                ?? $engagement->proposal_client 
                                ?? $engagement->client 
                                ?? $snapshot['client_name'] 
                                ?? 'No client specified';

                            $engagementType = $engagement->type ?? 'Project';
                            $recurrence = $engagement->recurrence_rule ?? 'One-time';
                            $engagementStatus = $engagement->status ?? 'Active';
                        @endphp

                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3 px-4 font-mono text-indigo-600 font-bold">
                                #{{ $engagement->id ?? 'N/A' }}
                            </td>
                            <td class="p-3 px-4">
                                <div class="font-semibold text-slate-800">
                                    @if($serviceId)
                                        <a href="{{ Route::has('services.workspace') ? route('services.workspace', ['service' => $serviceId, 'mode' => 'view']) : '/services/' . $serviceId . '/workspace' }}" 
                                           class="text-blue-600 hover:text-blue-800 hover:underline inline-flex items-center gap-1 font-bold">
                                            <span>{{ $engagementTitle }}</span>
                                            <i class="fa-solid fa-arrow-up-right-from-square text-[9px] opacity-75"></i>
                                        </a>
                                    @else
                                        <span>{{ $engagementTitle }}</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    {{ $clientName }}
                                </div>
                            </td>
                            <td class="p-3 px-4">
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-blue-100 text-blue-700 capitalize">
                                    {{ $engagementType }}
                                </span>
                            </td>
                            <td class="p-3 px-4 text-slate-600">
                                {{ $recurrence }}
                            </td>
                            <td class="p-3 px-4">
                                @php $statusLower = strtolower((string) $engagementStatus); @endphp

                                @if(in_array($statusLower, ['active', 'approved', 'ongoing']))
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-700 capitalize">
                                        {{ $engagementStatus }}
                                    </span>
                                @elseif(in_array($statusLower, ['completed', 'complete']))
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-100 text-blue-700 capitalize">
                                        {{ $engagementStatus }}
                                    </span>
                                @elseif(in_array($statusLower, ['cancelled', 'canceled', 'rejected']))
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-100 text-red-700 capitalize">
                                        {{ $engagementStatus }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-700 capitalize">
                                        {{ $engagementStatus }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400">
                                No active engagements found yet. Accept or contract a proposal to auto-instantiate operational engagements!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- OPERATIONAL EXECUTION TASKS TABLE -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-3 bg-slate-100/80 border-b border-slate-200 flex flex-wrap justify-between items-center gap-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">
                    Operational Execution Tasks
                </h2>
                <p class="text-[11px] text-slate-500 mt-0.5">Filter tasks instantly using the search box below</p>
            </div>

            <!-- SEARCH BAR -->
            <div class="relative min-w-[260px]">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                <input type="text" 
                       id="taskSearchInput" 
                       placeholder="Search task title, description, or #ID..." 
                       class="w-full pl-8 pr-3 py-1 text-xs bg-white border border-slate-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition"
                       onkeyup="filterOperationalTasks()">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs" id="tasksTable">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase text-[11px] font-semibold tracking-wider">
                        <th class="p-3 px-4">TASK ID &amp; ENGAGEMENT</th>
                        <th class="p-3 px-4">TASK TITLE</th>
                        <th class="p-3 px-4">EXPECTED TIME</th>
                        <th class="p-3 px-4">BILLING / RULE</th>
                        <th class="p-3 px-4">STATUS</th>
                        <th class="p-3 px-4 text-right">ACTIONS</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($tasks as $task)
                        <tr class="hover:bg-slate-50 transition task-row">
                            <td class="p-3 px-4 font-mono text-xs font-bold text-indigo-600">
                                Task #{{ $task->id ?? 'N/A' }}
                                <div class="text-[10px] text-slate-400 font-normal mt-0.5">
                                    (Eng #{{ $task->engagement_id ?? 'N/A' }})
                                </div>
                            </td>

                            <td class="p-3 px-4">
                                <div class="font-bold text-slate-800 text-xs">
                                    {{ $task->title ?? ('Task #' . ($task->id ?? 'N/A')) }}
                                </div>

                                @if(!empty($task->description))
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        {{ $task->description }}
                                    </div>
                                @endif
                            </td>

                            <td class="p-3 px-4 text-slate-600 font-medium">
                                {{ $task->expected_days ?? 0 }} days
                                <span class="text-slate-400">(</span>{{ $task->expected_working_hours ?? 0 }} hrs<span class="text-slate-400">)</span>
                            </td>

                            <td class="p-3 px-4">
                                @if(!empty($task->is_billable))
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-emerald-100 text-emerald-800 uppercase">
                                        Billable
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-slate-100 text-slate-600 uppercase">
                                        Non-billable
                                    </span>
                                @endif
                            </td>

                            <td class="p-3 px-4">
                                @if(strtolower($task->status ?? '') === 'completed')
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800 uppercase">
                                        Completed
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 uppercase">
                                        {{ $task->status ?? 'Pending' }}
                                    </span>
                                @endif
                            </td>

                            <td class="p-3 px-4 text-right">
                                @if(strtolower($task->status ?? '') !== 'completed')
                                    <form action="{{ Route::has('engagements.tasks.updateStatus') ? route('engagements.tasks.updateStatus', $task->id) : '/engagements/tasks/' . $task->id . '/status' }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="text-[11px] bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-2.5 py-1 rounded transition shadow-sm cursor-pointer">
                                            Mark Completed
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[11px] text-slate-400 italic">Done</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400 font-medium">
                                No operational tasks generated yet.
                            </td>
                        </tr>
                    @endforelse

                    <tr id="noResultsRow" class="hidden">
                        <td colspan="6" class="p-8 text-center text-slate-400 font-medium">
                            No matching tasks found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ADD CUSTOM OPERATIONAL TASK FORM -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <div class="mb-4">
            <h3 class="text-sm font-bold text-slate-900">+ Add Custom Operational Task</h3>
            <p class="text-[11px] text-slate-500">Add an extra execution task to any active engagement directly.</p>
        </div>

        <form action="{{ Route::has('engagements.tasks.store') ? route('engagements.tasks.store') : '/engagements/tasks/store' }}" method="POST" class="space-y-4 text-xs">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Target Engagement</label>
                    <select name="engagement_id" class="w-full p-2 border border-slate-300 rounded-md text-xs bg-white outline-none focus:border-indigo-500" required>
                        <option value="" disabled selected>Select Engagement ID</option>
                        @foreach($engagements as $eng)
                            <option value="{{ $eng->id }}">Engagement #{{ $eng->id }} - {{ $eng->title ?? $eng->client_name ?? 'Active Engagement' }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Task Title</label>
                    <input type="text" name="title" required placeholder="e.g. Kick-off Meeting &amp; Requirement Verification" class="w-full p-2 border border-slate-300 rounded-md text-xs outline-none focus:border-indigo-500">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Description (Optional)</label>
                <textarea name="description" rows="2" placeholder="Brief details about what needs to be executed..." class="w-full p-2 border border-slate-300 rounded-md text-xs outline-none focus:border-indigo-500"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Expected Days</label>
                    <input type="number" name="expected_days" value="2" min="1" required class="w-full p-2 border border-slate-300 rounded-md text-xs outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Working Hours</label>
                    <input type="number" name="expected_working_hours" value="16" min="1" required class="w-full p-2 border border-slate-300 rounded-md text-xs outline-none focus:border-indigo-500">
                </div>

                <div class="flex items-center gap-2 pt-4">
                    <input type="checkbox" name="is_billable" id="is_billable" value="1" checked class="rounded text-indigo-600">
                    <label for="is_billable" class="text-xs text-slate-700 font-medium">Mark as Billable Task</label>
                </div>
            </div>

            <div class="flex justify-end pt-2 border-t border-slate-100">
                <button type="submit" class="px-4 py-1.5 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-md shadow-sm transition cursor-pointer">
                    Save New Task
                </button>
            </div>
        </form>
    </div>

</div>

<script>
    function filterOperationalTasks() {
        const input = document.getElementById('taskSearchInput');
        const filter = input.value.toLowerCase().trim();
        const rows = document.querySelectorAll('.task-row');
        let hasVisibleRows = false;

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(filter)) {
                row.style.display = '';
                hasVisibleRows = true;
            } else {
                row.style.display = 'none';
            }
        });

        const noResultsRow = document.getElementById('noResultsRow');
        if (noResultsRow) {
            if (!hasVisibleRows && rows.length > 0) {
                noResultsRow.classList.remove('hidden');
            } else {
                noResultsRow.classList.add('hidden');
            }
        }
    }
</script>
@endsection
@php
    // Prepare task list from $sowWithin and $sowOut, or fallback demo data matching reference image
    $rawTasks = collect();
    
    if (isset($sowWithin) && count($sowWithin) > 0) {
        foreach ($sowWithin as $idx => $item) {
            $arr = is_array($item) ? $item : (is_object($item) ? (array)$item : ['main_task_description' => (string)$item]);
            $rawTasks->push([
                'id' => 'within-' . $idx,
                'scope' => 'within',
                'index' => $idx,
                'main_task' => !empty($arr['main_task_description']) ? $arr['main_task_description'] : 'Share Transfer Documentation',
                'sub_task' => !empty($arr['sub_task_description']) ? $arr['sub_task_description'] : ($arr['task'] ?? ($arr['title'] ?? 'Review existing corporate and share ownership records')),
                'responsible' => !empty($arr['responsible']) ? $arr['responsible'] : ($arr['assignee'] ?? ($project->assigned_consultant ?: 'Rubeca Potayre')),
                'duration' => !empty($arr['duration']) ? $arr['duration'] : '1h',
                'start_date' => $arr['start_date'] ?? null,
                'end_date' => $arr['end_date'] ?? null,
                'status' => !empty($arr['status']) ? $arr['status'] : 'Completed',
                'schedule_status' => $arr['schedule_status'] ?? 'Completed on time',
                'actual_time' => $arr['actual_time'] ?? '48m',
                'work_time' => $arr['work_time'] ?? '48m',
                'hold_time' => $arr['hold_time'] ?? '0m',
            ]);
        }
    }
    
    if (isset($sowOut) && count($sowOut) > 0) {
        foreach ($sowOut as $idx => $item) {
            $arr = is_array($item) ? $item : (is_object($item) ? (array)$item : ['main_task_description' => (string)$item]);
            $rawTasks->push([
                'id' => 'out-' . $idx,
                'scope' => 'out',
                'index' => $idx,
                'main_task' => !empty($arr['main_task_description']) ? $arr['main_task_description'] : 'Out of Scope SOW Tasks',
                'sub_task' => !empty($arr['sub_task_description']) ? $arr['sub_task_description'] : ($arr['task'] ?? ($arr['title'] ?? 'Additional out-of-scope task')),
                'responsible' => !empty($arr['responsible']) ? $arr['responsible'] : ($arr['assignee'] ?? 'Rubeca Potayre'),
                'duration' => !empty($arr['duration']) ? $arr['duration'] : '1h',
                'start_date' => $arr['start_date'] ?? null,
                'end_date' => $arr['end_date'] ?? null,
                'status' => !empty($arr['status']) ? $arr['status'] : 'Open',
                'schedule_status' => $arr['schedule_status'] ?? 'Not Started',
                'actual_time' => $arr['actual_time'] ?? '0m',
                'work_time' => $arr['work_time'] ?? '0m',
                'hold_time' => $arr['hold_time'] ?? '0m',
            ]);
        }
    }

    // Fallback default tasks matching reference images precisely
    if ($rawTasks->isEmpty()) {
        $rawTasks = collect([
            [
                'id' => 'demo-1',
                'scope' => 'within',
                'index' => 0,
                'main_task' => 'Share Transfer Documentation',
                'sub_task' => 'Review existing corporate and share ownership records',
                'responsible' => 'Rubeca Potayre',
                'duration' => '1h',
                'start_date' => null,
                'end_date' => null,
                'status' => 'Completed',
                'schedule_status' => 'Completed on time',
                'actual_time' => '40m',
                'work_time' => '40m',
                'hold_time' => '0m',
            ],
            [
                'id' => 'demo-2',
                'scope' => 'within',
                'index' => 1,
                'main_task' => 'Share Transfer Documentation',
                'sub_task' => 'Verify shares to be transferred by Dany and Ronald',
                'responsible' => 'Rubeca Potayre',
                'duration' => '1h',
                'start_date' => null,
                'end_date' => null,
                'status' => 'Completed',
                'schedule_status' => 'Completed on time',
                'actual_time' => '53m',
                'work_time' => '53m',
                'hold_time' => '0m',
            ],
            [
                'id' => 'demo-3',
                'scope' => 'within',
                'index' => 2,
                'main_task' => 'Share Transfer Documentation',
                'sub_task' => 'Prepare applicable share transfer documents',
                'responsible' => 'Rubeca Potayre',
                'duration' => '2h',
                'start_date' => null,
                'end_date' => null,
                'status' => 'In Progress',
                'schedule_status' => 'Not Started',
                'actual_time' => '1h 10m',
                'work_time' => '1h 10m',
                'hold_time' => '0m',
            ],
            [
                'id' => 'demo-4',
                'scope' => 'within',
                'index' => 3,
                'main_task' => 'Share Transfer Documentation',
                'sub_task' => 'Coordinate documentary requirements with transferors',
                'responsible' => 'Rubeca Potayre',
                'duration' => '2h',
                'start_date' => null,
                'end_date' => null,
                'status' => 'Pending Client',
                'schedule_status' => 'Not Started',
                'actual_time' => '30m',
                'work_time' => '30m',
                'hold_time' => '0m',
            ],
            [
                'id' => 'demo-5',
                'scope' => 'within',
                'index' => 4,
                'main_task' => 'Share Transfer Documentation',
                'sub_task' => 'Facilitate execution and signing of transfer documents',
                'responsible' => 'Rubeca Potayre',
                'duration' => '1h',
                'start_date' => null,
                'end_date' => null,
                'status' => 'Ready',
                'schedule_status' => 'Not Started',
                'actual_time' => '0m',
                'work_time' => '0m',
                'hold_time' => '0m',
            ],
            [
                'id' => 'demo-6',
                'scope' => 'within',
                'index' => 5,
                'main_task' => 'BIR Share Transfer Processing',
                'sub_task' => 'Prepare and organize applicable BIR requirements',
                'responsible' => 'Rubeca Potayre',
                'duration' => '2h',
                'start_date' => null,
                'end_date' => null,
                'status' => 'Ready',
                'schedule_status' => 'Not Started',
                'actual_time' => '0m',
                'work_time' => '0m',
                'hold_time' => '0m',
            ],
            [
                'id' => 'demo-7',
                'scope' => 'within',
                'index' => 6,
                'main_task' => 'BIR Share Transfer Processing',
                'sub_task' => 'Prepare ONETT / documentary stamp requirements',
                'responsible' => 'John Kelly Abalde',
                'duration' => '2h',
                'start_date' => null,
                'end_date' => null,
                'status' => 'Ready',
                'schedule_status' => 'Not Started',
                'actual_time' => '0m',
                'work_time' => '0m',
                'hold_time' => '0m',
            ],
            [
                'id' => 'demo-8',
                'scope' => 'within',
                'index' => 7,
                'main_task' => 'BIR Share Transfer Processing',
                'sub_task' => 'Submit and monitor BIR share transfer processing',
                'responsible' => 'Rubeca Potayre',
                'duration' => '4h',
                'start_date' => null,
                'end_date' => null,
                'status' => 'Ready',
                'schedule_status' => 'Not Started',
                'actual_time' => '0m',
                'work_time' => '0m',
                'hold_time' => '0m',
            ],
        ]);
    }

    $groupedTasks = $rawTasks->groupBy('main_task');
@endphp

<style>
/* EXACT PROTOTYPE TASK WORKSPACE MODAL STYLES */
.task-workspace-modal {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
    background: rgba(20, 31, 54, 0.45);
    backdrop-filter: blur(2px);
}
.task-workspace-modal.open {
    display: flex;
}
.task-workspace-modal .modalbox {
    width: min(1060px, 95vw);
    max-height: 92vh;
    overflow-y: auto;
    border-radius: 12px;
    border: 1px solid #dce4f2;
    background: #ffffff;
    box-shadow: 0 20px 60px rgba(24, 42, 77, 0.2);
    padding: 20px 24px;
}
.task-workspace-head {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding-bottom: 14px;
    border-bottom: 1px solid #dce4f2;
}
.task-workspace-head-main h3 {
    margin: 0;
    color: #1e3a8a;
    font-size: 15px;
    font-weight: 800;
    line-height: 1.3;
}
.task-workspace-head-main .imeta {
    margin-top: 3px;
    color: #8a94a7;
    font-size: 8.5px;
    font-weight: 500;
}
.task-workspace-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
}
.task-workspace-meta span {
    padding: 4px 10px;
    border: 1px solid #dce4f2;
    border-radius: 999px;
    color: #64748b;
    font-size: 8px;
    background: #f8fafc;
    font-weight: 500;
}
.task-workspace-meta b {
    padding: 3px 8px;
    border-radius: 999px;
    background: #eef3ff;
    color: #3158e6;
    font-size: 7.5px;
    font-weight: 800;
}
.task-workspace-head-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
}
.task-workspace-head-actions {
    display: flex;
    gap: 6px;
}
.task-workspace-head-actions .btn {
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 8.5px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    border: 1px solid #cfd9e9;
    background: #fff;
    color: #3158e6;
}
.task-workspace-head-actions .demo-reset-task {
    background: #fffdf5;
    border: 1px solid #efd29a;
    color: #83570f;
}
.task-workspace-controls {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 6px;
    padding: 7px 9px;
    border: 1px solid #dce4f2;
    border-radius: 9px;
    background: #f7f9fd;
}
.task-workspace-control {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 36px;
    padding: 0 16px;
    border: 1px solid #cfd9e9;
    border-radius: 7px;
    background: #fff;
    color: #40506d;
    font-family: inherit;
    font-size: 8.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease;
}
.task-workspace-control svg {
    width: 13px;
    height: 13px;
    fill: currentColor;
}
.task-workspace-control.primary {
    border-color: #8da2c0;
    background: #8da2c0;
    color: #fff;
}
.task-workspace-control.success {
    border-color: #bedfcf;
    background: #eef8f2;
    color: #277653;
}
.task-control-legend {
    grid-column: 1 / -1;
    margin: 2px 2px 0;
    color: #7a8599;
    font-size: 7px;
    line-height: 1.45;
}
.task-schedule-panel {
    margin-top: 10px;
    padding: 10px;
    border: 1px solid #dce4f2;
    border-radius: 9px;
    background: #fff;
}
.task-schedule-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
}
.task-schedule-grid div {
    padding: 8px 10px;
    border-radius: 7px;
    background: #f7f9fd;
}
.task-schedule-grid span {
    display: block;
    color: #8a94a7;
    font-size: 7px;
    text-transform: uppercase;
    font-weight: 700;
}
.task-schedule-grid strong {
    display: block;
    margin-top: 3px;
    color: #34415d;
    font-size: 9px;
    font-weight: 800;
}
.task-update-side {
    display: grid;
    grid-template-columns: minmax(280px, 0.85fr) minmax(360px, 1.15fr);
    gap: 12px;
    margin-top: 10px;
}
.task-submit-panel, .task-history-panel {
    border: 1px solid #dce4f2;
    border-radius: 9px;
    overflow: hidden;
    background: #fff;
}
.task-activity-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 9px 12px;
    background: #f5f7fb;
    border-bottom: 1px solid #dce4f2;
}
.task-activity-head h4 {
    margin: 0;
    color: #203a7c;
    font-size: 8.5px;
    font-weight: 800;
    letter-spacing: 0.35px;
}
.task-activity-head .imeta {
    margin-top: 2px;
    color: #8a94a7;
    font-size: 7px;
}
.task-update-composer {
    padding: 12px;
    background: #fff;
}
.task-update-composer textarea {
    width: 100%;
    min-height: 80px;
    padding: 8px;
    border: 1px solid #cfd9e9;
    border-radius: 6px;
    font-size: 8px;
    color: #34415d;
    font-family: inherit;
    resize: vertical;
}
.task-update-composer input[type="file"] {
    width: 100%;
    padding: 6px;
    border: 1px solid #dce4f2;
    border-radius: 6px;
    background: #fff;
    font-size: 8px;
    font-family: inherit;
}
.task-update-composer button#addTaskUpdate {
    width: 100%;
    min-height: 36px;
    background: #203a7c;
    color: #fff;
    font-size: 8.5px;
    font-weight: 700;
    border-radius: 6px;
    border: 0;
    cursor: pointer;
    margin-top: 6px;
    font-family: inherit;
}
.task-update-composer button#addTaskUpdate:hover {
    background: #172d63;
}
.task-timeline {
    padding: 35px 12px;
    text-align: center;
    color: #8a94a7;
    font-size: 8px;
}
.timeline-load {
    display: flex;
    justify-content: center;
    padding: 8px;
    border-top: 1px solid #edf0f5;
}
.timeline-load button {
    padding: 4px 12px;
    border: 1px solid #cfd9e9;
    border-radius: 5px;
    background: #fff;
    color: #40506d;
    font-size: 8px;
    font-weight: 700;
    cursor: pointer;
}
</style>

<div class="grid grid-cols-1 lg:grid-cols-[240px_minmax(0,1fr)] gap-4 items-start font-sans text-slate-800">
    <!-- LEFT SIDEBAR: STAGE MANAGEMENT PANEL (STEADY) -->
    <div class="sticky top-4 rounded-xl border border-slate-200 bg-white p-4 shadow-2xs space-y-4 self-start">
        <div>
            <h3 class="text-[11px] font-bold text-slate-800 tracking-wider uppercase mb-3">STAGE MANAGEMENT</h3>
            <h4 class="text-[10px] font-bold text-slate-400 tracking-wider uppercase mb-1.5">STAGE STATUS</h4>
            <div class="rounded-lg border border-blue-200 bg-blue-50/70 px-3 py-2 text-center text-xs font-bold text-blue-900">
                In Progress
            </div>
        </div>

        <div class="border-t border-slate-100 pt-3">
            <h4 class="text-[10px] font-bold text-slate-400 tracking-wider uppercase mb-2">STAGE HANDLING</h4>
            <div class="rounded-lg bg-slate-50 border border-slate-100 p-2.5 mb-2.5 flex items-center justify-between">
                <span class="text-[10px] text-slate-500 font-medium leading-tight">Total Stage<br>Handling Time</span>
                <span class="text-base font-bold text-slate-900 font-mono">20:40:31</span>
            </div>
            <div class="space-y-1 text-[11px]">
                <div class="flex justify-between items-center"><span class="text-slate-500">In Progress</span><span class="font-semibold text-slate-800 font-mono">20:40:31</span></div>
                <div class="flex justify-between items-center"><span class="text-slate-500">On Hold</span><span class="font-semibold text-slate-800 font-mono">00:00:00</span></div>
                <div class="flex justify-between items-center"><span class="text-slate-500">Started</span><span class="font-medium text-slate-700 text-[10px]">05/10/2026, 14:23:29</span></div>
                <div class="flex justify-between items-center"><span class="text-slate-500">Completed</span><span class="text-slate-400">—</span></div>
                <div class="flex justify-between items-center"><span class="text-slate-500">Canceled</span><span class="text-slate-400">—</span></div>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-3 space-y-2">
            <h4 class="text-[10px] font-bold text-slate-400 tracking-wider uppercase mb-1.5">STAGE CONTROLS</h4>
            <button type="button" class="w-full rounded-lg border border-slate-200 bg-white py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                Put Execution On Hold
            </button>
            <button type="button" class="w-full rounded-lg bg-[#102d79] py-1.5 text-xs font-semibold text-white hover:bg-[#0c2461] transition shadow-2xs">
                Complete Execution
            </button>
            <button type="button" class="w-full rounded-lg border border-slate-200 bg-white py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 hover:border-rose-200 transition shadow-2xs">
                Cancel Execution
            </button>
        </div>

        <div class="border-t border-slate-100 pt-3">
            <h4 class="text-[10px] font-bold text-slate-400 tracking-wider uppercase mb-1">STAGE HISTORY</h4>
            <p class="text-[11px] text-slate-400">No stage activity yet.</p>
        </div>
    </div>

    <!-- RIGHT CONTENT AREA: EXECUTION TASKS (WRAPPED IN OUTER BOX) -->
    <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-2xs space-y-4">
        <!-- EXECUTION HEADER -->
        <div class="border-b border-slate-200 pb-3">
            <h2 class="text-base font-bold text-slate-900 leading-tight">Execution Tasks</h2>
            <p class="text-[11px] text-slate-500 mt-0.5">Perform and monitor tasks authorized by the approved SOW.</p>
        </div>

        <!-- APPROVED SCOPE INFORMATION BANNER -->
        <div class="rounded-lg border border-slate-200 bg-slate-50/80 px-3.5 py-2.5 text-xs text-slate-700 flex items-start gap-2">
            <div class="text-[11px] leading-relaxed">
                <strong class="font-bold text-slate-900">Approved scope only.</strong>
                <span class="text-slate-600">Execution tasks come directly from the approved SOW. Any additional task must be added through a SOW revision or an approved Out-of-Scope SOW.</span>
            </div>
        </div>

        <!-- MAIN TASK CARDS -->
        @foreach($groupedTasks as $mainTaskTitle => $subtasks)
            @php
                $completedCount = $subtasks->where('status', 'Completed')->count();
                $totalSub = $subtasks->count();
                $plannedHours = $subtasks->sum(function($t) { return (int)preg_replace('/[^0-9]/', '', $t['duration']); });
                if ($plannedHours === 0) {
                    $plannedHours = ($mainTaskTitle === 'BIR Share Transfer Processing') ? 8 : 7;
                }
            @endphp
            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-2xs">
                <!-- MAIN TASK HEADER BAR -->
                <div class="bg-[#f4f7fc] border-b border-slate-200 px-4 py-2.5 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 leading-tight">{{ $mainTaskTitle }}</h3>
                        <p class="text-[10px] font-medium text-slate-500">Main Task · Responsible · Assigned: Unassigned</p>
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[10px] font-semibold text-slate-600">
                            Target Not set – Not set
                        </span>
                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[10px] font-semibold text-slate-600">
                            Planned {{ $plannedHours }}h
                        </span>
                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[10px] font-semibold text-slate-600">
                            Actual {{ $mainTaskTitle === 'BIR Share Transfer Processing' ? '0m' : '3h 21m' }}
                        </span>
                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[10px] font-semibold text-slate-600">
                            {{ $completedCount }}/{{ $totalSub }} completed
                        </span>
                        <span class="inline-flex items-center rounded-full border border-emerald-300 bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700">
                            On schedule
                        </span>
                    </div>
                </div>

                <!-- SUBTASK HORIZONTAL TABLE -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/50 text-[9.5px] uppercase font-extrabold text-slate-500 tracking-wider">
                                <th class="py-2.5 px-4 w-[34%]">SUB TASK DESCRIPTION</th>
                                <th class="py-2.5 px-3 w-[20%]">RESPONSIBILITY / ASSIGNED PERSONS</th>
                                <th class="py-2.5 px-3 w-[15%]">APPROVED SCHEDULE</th>
                                <th class="py-2.5 px-3 w-[12%]">ACTUAL TIME</th>
                                <th class="py-2.5 px-3 w-[11%]">TASK STATUS</th>
                                <th class="py-2.5 pr-4 pl-2 text-right w-[8%]">ACTION</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @foreach($subtasks as $sub)
                                @php
                                    $st = $sub['status'];
                                    $statusClass = match($st) {
                                        'Completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'In Progress' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'Pending Client' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'Ready' => 'bg-emerald-50/80 text-emerald-800 border-emerald-300',
                                        default => 'bg-slate-50 text-slate-600 border-slate-200'
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50/60 transition">
                                    <!-- SUB TASK DESCRIPTION -->
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-900 leading-snug">{{ $sub['sub_task'] }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">Planned duration {{ $sub['duration'] }}</div>
                                    </td>

                                    <!-- RESPONSIBILITY / ASSIGNED PERSONS -->
                                    <td class="py-3 px-3 align-top">
                                        <div class="text-[10px] font-bold text-slate-900">Responsible</div>
                                        <div class="text-[11px] text-slate-600 font-medium">{{ $sub['responsible'] }}</div>
                                    </td>

                                    <!-- APPROVED SCHEDULE -->
                                    <td class="py-3 px-3 align-top">
                                        <div class="text-[10px] text-slate-400">Not set</div>
                                        <div class="text-[10px] text-slate-400">to Not set</div>
                                        <div class="mt-1">
                                            @if($st === 'Completed')
                                                <span class="inline-block rounded-full bg-emerald-50 px-2 py-0.5 text-[9.5px] font-bold text-emerald-700 border border-emerald-200">
                                                    Completed on time
                                                </span>
                                            @else
                                                <span class="inline-block rounded-full bg-slate-100 px-2 py-0.5 text-[9.5px] font-medium text-slate-500">
                                                    Not Started
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- ACTUAL TIME -->
                                    <td class="py-3 px-3 align-top">
                                        <div class="text-xs font-bold text-slate-900 font-mono">{{ $sub['actual_time'] }}</div>
                                        <div class="text-[9.5px] text-slate-400 mt-0.5">Work {{ $sub['work_time'] }} · Hold {{ $sub['hold_time'] }}</div>
                                    </td>

                                    <!-- TASK STATUS -->
                                    <td class="py-3 px-3 align-top">
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $statusClass }}">
                                            {{ $st }}
                                        </span>
                                    </td>

                                    <!-- ACTION -->
                                    <td class="py-3 pr-4 pl-2 text-right align-top">
                                        <button type="button" 
                                                onclick="openTaskDetailModal({
                                                    title: '{{ addslashes($sub['sub_task']) }}',
                                                    mainTask: '{{ addslashes($mainTaskTitle) }}',
                                                    responsible: '{{ addslashes($sub['responsible']) }}',
                                                    status: '{{ $sub['status'] }}',
                                                    planned: '{{ $sub['duration'] }}',
                                                    working: '{{ $sub['work_time'] }}',
                                                    hold: '{{ $sub['hold_time'] }}',
                                                    total: '{{ $sub['actual_time'] }}',
                                                    schedule: '{{ $sub['schedule_status'] }}'
                                                })"
                                                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition whitespace-nowrap">
                                            Open Task
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- EXACT PROTOTYPE TASK WORKSPACE MODAL -->
<div class="modal task-workspace-modal" id="taskWorkspaceModal" role="dialog" aria-modal="true" aria-labelledby="taskWorkspaceTitle">
    <div class="modalbox">
        <div class="task-workspace-head">
            <div class="task-workspace-head-main">
                <h3 id="taskWorkspaceTitle">Review existing corporate and share ownership records</h3>
                <div class="imeta" id="taskWorkspacePath">Share Transfer Documentation · Approved SOW task</div>
                <div class="task-workspace-meta" id="taskWorkspaceMeta">
                    <span>Assigned Persons: <strong id="taskModalAssignee" style="color:#203a7c;font-weight:700">Rubeca Potayre</strong></span>
                    <span>Responsibility:</span>
                    <b style="padding:3px 8px;border-radius:999px;background:#eef3ff;color:#3158e6;font-size:7px;font-weight:800;display:inline-flex;align-items:center;">Responsible</b>
                </div>
            </div>
            <div class="task-workspace-head-right">
                <div class="task-workspace-head-actions">
                    <button class="btn demo-reset-task" type="button" id="resetTaskForTesting" onclick="resetTaskTesting()">Reset Task for Testing</button>
                    <button class="btn" type="button" id="closeTaskWorkspace" onclick="closeTaskDetailModal()">Close</button>
                </div>
                <div class="task-workspace-controls" id="taskWorkspaceControls">
                    <button class="task-workspace-control primary" type="button" id="taskPlay" onclick="taskAction('start')">
                        <svg viewBox="0 0 24 24" aria-hidden="true" style="width:13px;height:13px;fill:currentColor;"><path d="M8 5v14l11-7z"/></svg><span>Start</span>
                    </button>
                    <button class="task-workspace-control" type="button" id="taskPause" onclick="taskAction('pause')">
                        <svg viewBox="0 0 24 24" aria-hidden="true" style="width:13px;height:13px;fill:currentColor;"><path d="M6 5h4v14H6zm8 0h4v14h-4z"/></svg><span>Pause</span>
                    </button>
                    <button class="task-workspace-control" type="button" id="taskStop" onclick="taskAction('stop')">
                        <svg viewBox="0 0 24 24" aria-hidden="true" style="width:13px;height:13px;fill:currentColor;"><path d="M6 6h12v12H6z"/></svg><span>Stop Session</span>
                    </button>
                    <button class="task-workspace-control success" type="button" id="taskDone" onclick="taskAction('done')">
                        <svg viewBox="0 0 24 24" aria-hidden="true" style="width:13px;height:13px;fill:currentColor;"><path d="m9 16.2-3.5-3.5L4.1 14.1 9 19 20.3 7.7l-1.4-1.4z"/></svg><span>Done</span>
                    </button>
                    <div class="task-control-legend">Each action automatically creates an Internal &amp; Client Submission. Pause and Stop open a separate reason form.</div>
                </div>
            </div>
        </div>
        
        <div class="task-execution-layout">
            <div class="task-execution-primary">
                <section class="task-schedule-panel">
                    <div class="task-schedule-grid" id="taskScheduleSummary">
                        <div><span>Task Status</span><strong id="modalScheduleStatus">Completed</strong></div>
                        <div><span>Target Start</span><strong id="modalScheduleStart">Not set</strong></div>
                        <div><span>Target End</span><strong id="modalScheduleEnd">Not set</strong></div>
                        <div><span>Planned Duration</span><strong id="modalSchedulePlanned">1h</strong></div>
                        <div><span>Working Time</span><strong id="modalScheduleWorking">40m</strong></div>
                        <div><span>On Hold Time</span><strong id="modalScheduleHold">0m</strong></div>
                        <div><span>Total Time</span><strong id="modalScheduleTotal">40m</strong></div>
                        <div><span>Actual Elapsed</span><strong id="modalScheduleElapsed">Not started</strong></div>
                        <div>
                            <span>Schedule</span>
                            <span id="modalScheduleBadge" style="display:inline-block;margin-top:4px;padding:3px 7px;border-radius:999px;background:#eef8f2;color:#277653;font-size:7px;font-weight:800;text-transform:uppercase;">COMPLETED ON TIME</span>
                        </div>
                    </div>
                </section>
            </div>

            <div class="task-update-side">
                <section class="task-submit-panel">
                    <div class="task-activity-head">
                        <div>
                            <h4>SUBMIT UPDATE</h4>
                            <div class="imeta">Add written information, files, or both.</div>
                        </div>
                    </div>
                    <div class="task-update-composer">
                        <div class="task-update-composer-grid" style="display:grid;gap:9px;">
                            <div class="form-group">
                                <label for="taskSubmissionType" style="display:block;font-size:8px;font-weight:700;color:#34415d;margin-bottom:3px;">Submission Type</label>
                                <select id="taskSubmissionType" style="width:100%;height:32px;border:1px solid #cfd9e9;border-radius:6px;background:#fff;padding:0 8px;font-size:8px;color:#34415d;">
                                    <option value="internal">Internal Submission</option>
                                    <option value="client">Client Submission</option>
                                    <option value="both">Internal &amp; Client Submission</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="taskUpdateText" style="display:block;font-size:8px;font-weight:700;color:#34415d;margin-bottom:3px;">Update Information (optional)</label>
                                <textarea id="taskUpdateText" placeholder="Progress, result, issue, next action, or client information..." style="width:100%;min-height:80px;border:1px solid #cfd9e9;border-radius:6px;padding:8px;font-size:8px;color:#34415d;resize:vertical;"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="taskUpdateFiles" style="display:block;font-size:8px;font-weight:700;color:#34415d;margin-bottom:3px;">Attachments (optional)</label>
                                <input type="file" id="taskUpdateFiles" multiple style="width:100%;padding:6px;border:1px solid #cfd9e9;border-radius:6px;background:#fff;font-size:8px;" />
                            </div>
                            <button class="btn primary" type="button" id="addTaskUpdate">Submit Update</button>
                        </div>
                        <div class="imeta" style="margin-top:7px;font-size:7px;color:#8a94a7;line-height:1.4;">Internal submissions stay within the project. Client and combined submissions are also added to the client SOW Report.</div>
                    </div>
                </section>

                <section class="task-history-panel">
                    <div class="task-activity-head">
                        <div>
                            <h4>SUBMISSION HISTORY</h4>
                            <div class="imeta">Permanent task update and attachment record.</div>
                        </div>
                        <select id="taskActivityFilter" aria-label="Filter submission history" style="height:26px;border:1px solid #cfd9e9;border-radius:5px;background:#fff;padding:0 6px;font-size:8px;color:#34415d;">
                            <option value="all">All submissions</option>
                            <option value="updates">Written updates</option>
                            <option value="files">With files</option>
                            <option value="extensions">Extensions</option>
                        </select>
                    </div>
                    <div class="task-timeline" id="taskUpdateList">
                        No activity matches this filter.
                    </div>
                    <div class="timeline-load" id="taskTimelineLoad">
                        <button class="btn" type="button" id="loadMoreTaskUpdates">Load More</button>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<script>
function openTaskDetailModal(data) {
    document.getElementById('taskWorkspaceTitle').textContent = data.title || 'Task Execution';
    document.getElementById('taskWorkspacePath').textContent = (data.mainTask || 'Share Transfer Documentation') + ' · Approved SOW task';
    document.getElementById('taskModalAssignee').textContent = data.responsible || 'Rubeca Potayre';
    
    document.getElementById('modalScheduleStatus').textContent = data.status || 'Completed';
    document.getElementById('modalSchedulePlanned').textContent = data.planned || '1h';
    document.getElementById('modalScheduleWorking').textContent = data.working || '40m';
    document.getElementById('modalScheduleHold').textContent = data.hold || '0m';
    document.getElementById('modalScheduleTotal').textContent = data.total || '40m';
    
    const schedEl = document.getElementById('modalScheduleBadge');
    if (schedEl) {
        if (data.status === 'Completed' || data.schedule === 'Completed on time') {
            schedEl.textContent = 'COMPLETED ON TIME';
            schedEl.style.background = '#eef8f2';
            schedEl.style.color = '#277653';
        } else if (data.status === 'In Progress') {
            schedEl.textContent = 'ON SCHEDULE';
            schedEl.style.background = '#eef8f2';
            schedEl.style.color = '#277653';
        } else {
            schedEl.textContent = 'NOT STARTED';
            schedEl.style.background = '#f1f5f9';
            schedEl.style.color = '#64748b';
        }
    }
    
    const modal = document.getElementById('taskWorkspaceModal');
    if (modal) {
        modal.classList.add('open');
    }
}

function closeTaskDetailModal() {
    const modal = document.getElementById('taskWorkspaceModal');
    if (modal) {
        modal.classList.remove('open');
    }
}

function resetTaskTesting() {
    document.getElementById('modalScheduleWorking').textContent = '0m';
    document.getElementById('modalScheduleHold').textContent = '0m';
    document.getElementById('modalScheduleTotal').textContent = '0m';
    alert('Task reset for testing.');
}

function taskAction(action) {
    if (action === 'start') {
        document.getElementById('modalScheduleStatus').textContent = 'In Progress';
        const badge = document.getElementById('modalScheduleBadge');
        if (badge) {
            badge.textContent = 'ON SCHEDULE';
            badge.style.background = '#eef8f2';
            badge.style.color = '#277653';
        }
    } else if (action === 'pause') {
        const reason = prompt('Explain why this task is being paused:');
        if (reason) {
            document.getElementById('modalScheduleStatus').textContent = 'On Hold';
        }
    } else if (action === 'stop') {
        const reason = prompt('Explain why this session is being stopped:');
        if (reason) {
            document.getElementById('modalScheduleStatus').textContent = 'Stopped';
        }
    } else if (action === 'done') {
        document.getElementById('modalScheduleStatus').textContent = 'Completed';
        const badge = document.getElementById('modalScheduleBadge');
        if (badge) {
            badge.textContent = 'COMPLETED ON TIME';
            badge.style.background = '#eef8f2';
            badge.style.color = '#277653';
        }
    }
}

function taskSubmitUpdate() {
    const textEl = document.getElementById('taskUpdateText');
    const typeEl = document.getElementById('taskSubmissionType');
    if (textEl && textEl.value.trim()) {
        const listEl = document.getElementById('taskUpdateList');
        if (listEl) {
            const timeStr = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            const typeText = typeEl ? typeEl.options[typeEl.selectedIndex].text : 'Internal Submission';
            const newItem = `<div style="padding:10px 12px;border-bottom:1px solid #edf0f5;text-align:left;width:100%;"><div style="font-size:7px;color:#8a94a7;margin-bottom:2px;">${timeStr} · ${typeText}</div><div style="font-size:8.5px;color:#34415d;">${textEl.value.trim()}</div></div>`;
            if (listEl.textContent.includes('No activity matches this filter')) {
                listEl.innerHTML = newItem;
                listEl.style.display = 'block';
            } else {
                listEl.insertAdjacentHTML('afterbegin', newItem);
            }
        }
        textEl.value = '';
        alert('Update submitted successfully.');
    } else {
        alert('Please enter update information or select attachments.');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('taskWorkspaceModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeTaskDetailModal();
        });
    }
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeTaskDetailModal();
});

function toggleProjectTaskStatus(scopeType, index, newStatus) {
    if (typeof scopeType === 'string' && scopeType.startsWith('demo')) {
        alert('Demo task status updated locally.');
        return;
    }
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
            window.location.reload();
        }
    })
    .catch(err => console.error('Error updating project task:', err));
}
</script>

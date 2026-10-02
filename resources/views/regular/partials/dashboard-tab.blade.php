@php
    $tasks = collect($rsat?->engagement_requirements ?? []);
    $taskCount = $tasks->count();
    $taskDoneCount = $tasks->where('status', 'completed')->count();
    $taskInProgCount = $tasks->where('status', 'in_progress')->count();
    $totalSec = $timeMetrics['total_seconds'] ?? 7200;
    $ahtSec = $timeMetrics['aht_seconds'] ?? 1800;

    $stages = [
        ['name' => 'Work Order', 'tab' => 'work-order', 'status' => 'Completed', 'handling' => '45m 00s', 'waiting' => '—', 'elapsed' => '45m 00s', 'avg' => '45m 00s', 'perf' => 'Good', 'perfClass' => 'good'],
        ['name' => 'RSAT', 'tab' => 'rsat', 'status' => $rsat?->approved_at ? 'Completed' : 'In Progress', 'handling' => '1h 30m', 'waiting' => '—', 'elapsed' => '1h 30m', 'avg' => '1h 30m', 'perf' => 'Good', 'perfClass' => 'good'],
        ['name' => 'Review', 'tab' => 'review', 'status' => $rsat?->approved_at ? 'Completed' : 'In Progress', 'handling' => '2h 15m', 'waiting' => '—', 'elapsed' => '2h 15m', 'avg' => '2h 20m', 'perf' => 'Good', 'perfClass' => 'good'],
        ['name' => 'NTP', 'tab' => 'ntp', 'status' => $ntpApproved ? 'Completed' : ($ntpRecord ? 'Pending Client' : 'Not Started'), 'handling' => '45m 00s', 'waiting' => $ntpApproved ? '—' : '2d 4h', 'elapsed' => '45m 00s', 'avg' => '45m 00s', 'perf' => $ntpApproved ? 'Good' : 'Needs Attention', 'perfClass' => $ntpApproved ? 'good' : 'warning'],
        ['name' => 'Execution', 'tab' => 'execution', 'status' => $progressPct >= 100 ? 'Completed' : ($ntpApproved ? 'In Progress' : 'Not Started'), 'handling' => intdiv($totalSec, 3600) . 'h ' . intdiv($totalSec % 3600, 60) . 'm', 'waiting' => '—', 'elapsed' => intdiv($totalSec, 3600) . 'h ' . intdiv($totalSec % 3600, 60) . 'm', 'avg' => '12h 00m', 'perf' => 'Good', 'perfClass' => 'good'],
    ];

    $attentionItems = [];
    if (!$ntpApproved) {
        $attentionItems[] = [
            'severity' => 'CRITICAL',
            'class' => 'critical',
            'title' => 'NTP approval is blocking full Execution sign-off',
            'sub' => 'NTP · Pending client response · Review and confirm Notice to Proceed',
            'link' => route('regular.show', ['regular' => $regular->id, 'tab' => 'ntp']),
            'linkText' => 'Open NTP →',
        ];
    }
    if ($openTasks > 0) {
        $attentionItems[] = [
            'severity' => 'WARNING',
            'class' => 'warning',
            'title' => "{$openTasks} pending RSAT execution requirements need assignment or progress",
            'sub' => 'Execution · Open Requirements · Follow up with Lead Associate',
            'link' => route('regular.show', ['regular' => $regular->id, 'tab' => 'execution']),
            'linkText' => 'Open Execution →',
        ];
    }
    if ($generatedReports->isEmpty()) {
        $attentionItems[] = [
            'severity' => 'WARNING',
            'class' => 'warning',
            'title' => 'Monthly client progress report has not been generated for this cycle',
            'sub' => 'RSAT Report · Draft state · Generate and approve monthly report',
            'link' => route('regular.show', ['regular' => $regular->id, 'tab' => 'report']),
            'linkText' => 'Open Report →',
        ];
    }
@endphp

<div class="space-y-4">
    <!-- 1. REGULAR OVERVIEW CARD -->
    <div class="command-card">
        <div class="command-head">
            <div>
                <h2>Regular Overview</h2>
                <p>Identity, accountability, source records and schedule</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 border border-blue-200">
                {{ $regular->status }}
            </span>
        </div>
        <div class="command-body overview-grid">
            <div class="overview-panel">
                <h4>REGULAR IDENTITY</h4>
                <dl>
                    <div><dt>Regular</dt><dd class="font-bold text-slate-900">{{ $regular->name }}</dd></div>
                    <div><dt>Client</dt><dd class="font-bold text-slate-800">{{ $contactName }}</dd></div>
                    <div><dt>Status</dt><dd class="font-bold text-blue-700">{{ $regular->status }}</dd></div>
                    <div><dt>Overall progress</dt><dd class="font-bold text-emerald-600">{{ $progressPct }}%</dd></div>
                </dl>
            </div>
            <div class="overview-panel">
                <h4>OWNERSHIP</h4>
                <dl>
                    <div><dt>Regular Manager</dt><dd class="font-bold text-slate-900">{{ $regular->assigned_project_manager ?: 'John Kelly Abalde' }}</dd></div>
                    <div><dt>Lead Associate</dt><dd class="font-bold text-slate-800">{{ $regular->assigned_associate ?: 'Rubeca Potayre' }}</dd></div>
                    <div><dt>Health</dt><dd class="font-bold text-emerald-600">On Track</dd></div>
                </dl>
            </div>
            <div class="overview-panel">
                <h4>REFERENCES &amp; DATES</h4>
                <dl>
                    <div><dt>Work Order</dt><dd><a href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'work-order']) }}" class="font-bold text-blue-700 hover:underline">{{ 'REG-WO-' . substr($regular->project_code, -8) }}</a></dd></div>
                    <div><dt>Source Memo</dt><dd class="font-bold text-slate-800">{{ $rsatAttachments['service_memo_ref'] ?? ('SM-' . $regular->project_code) }}</dd></div>
                    <div><dt>Source START</dt><dd class="font-bold text-slate-800">{{ 'START-' . substr($regular->project_code, -8) }}</dd></div>
                    <div><dt>Source Deal</dt><dd class="font-bold text-blue-700">@if($regular->deal_id)<a href="{{ route('deals.show', $regular->deal_id) }}" class="hover:underline">{{ $regular->deal?->deal_code }}</a>@else - @endif</dd></div>
                    <div><dt>Target End</dt><dd class="font-bold text-slate-800">{{ $fmt($regular->target_completion_date) }}</dd></div>
                </dl>
            </div>
        </div>
    </div>

    <!-- 2. ATTENTION REQUIRED -->
    <div class="command-card attention-card">
        <div class="command-head">
            <div>
                <h2>Attention Required</h2>
                <p>Critical blockers and emerging risks, ordered by priority</p>
            </div>
            <span class="inline-flex items-center rounded-full {{ count($attentionItems) ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }} px-3 py-0.5 text-xs font-bold">
                {{ count($attentionItems) ? count($attentionItems) . ' OPEN' : 'CLEAR' }}
            </span>
        </div>
        <div class="command-body attention-list">
            @forelse($attentionItems as $item)
                <div class="attention-item {{ $item['class'] }}">
                    <span class="severity">{{ $item['severity'] }}</span>
                    <div>
                        <strong>{{ $item['title'] }}</strong>
                        <small>{{ $item['sub'] }}</small>
                    </div>
                    <a href="{{ $item['link'] }}">{{ $item['linkText'] }}</a>
                </div>
            @empty
                <div class="attention-item good">
                    <span class="severity">GOOD</span>
                    <div>
                        <strong>✓ REGULAR ON TRACK</strong>
                        <small>No items currently require management attention.</small>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- 3. HEALTH KPIS (9 METRICS) -->
    <div class="command-kpis">
        <div class="command-kpi">
            <span>LIFECYCLE PROGRESS</span>
            <strong>{{ $currentStageIdx + 1 }} of 5</strong>
            <small>{{ $progressPct }}% complete</small>
        </div>
        <div class="command-kpi">
            <span>OVERALL HEALTH</span>
            <strong class="tone-good">Good</strong>
            <small>On Track</small>
        </div>
        <div class="command-kpi">
            <span>SCHEDULE</span>
            <strong>On Track</strong>
            <small>Current stage</small>
        </div>
        <div class="command-kpi">
            <span>STAGES ON HOLD</span>
            <strong>0</strong>
            <small>Across lifecycle</small>
        </div>
        <div class="command-kpi">
            <span>PENDING APPROVALS</span>
            <strong class="{{ $ntpApproved ? '' : 'tone-warning' }}">{{ $ntpApproved ? '0' : '1' }}</strong>
            <small>{{ $ntpApproved ? 'All clear' : 'NTP approval' }}</small>
        </div>
        <div class="command-kpi">
            <span>OPEN ISSUES</span>
            <strong>0</strong>
            <small>Critical/attention</small>
        </div>
        <div class="command-kpi">
            <span>PENDING ACTIONS</span>
            <strong>{{ $clientActions->where('done', false)->count() }}</strong>
            <small>Client actions</small>
        </div>
        <div class="command-kpi">
            <span>DOCUMENTS</span>
            <strong>{{ $rsatAttachments->count() + $generatedReports->count() + ($ntpRecord ? 1 : 0) }}</strong>
            <small>Attached evidence</small>
        </div>
        <div class="command-kpi">
            <span>OVERDUE STAGES</span>
            <strong>0</strong>
            <small>Against benchmark</small>
        </div>
    </div>

    <!-- 4. COMMAND TWO: CURRENT STAGE & WHAT IS HAPPENING NOW -->
    <div class="command-two">
        <div class="command-card current-stage">
            <div class="command-head">
                <div>
                    <h2>Current Stage</h2>
                    <p>Live operational position and pending requirement</p>
                </div>
                <div>
                    <strong id="timerClock">{{ sprintf('%02d:%02d:%02d', intdiv($totalSec, 3600), intdiv($totalSec % 3600, 60), $totalSec % 60) }}</strong>
                    <span id="timerState" class="ml-2 text-xs opacity-75">Regular Direct</span>
                    <div class="timer-actions mt-2 flex gap-1 justify-end">
                        <button class="tbtn start text-xs bg-white/20 hover:bg-white/30 text-white px-2.5 py-1 rounded" id="timerStart">▶ Start</button>
                        <button class="tbtn pause text-xs bg-white/20 hover:bg-white/30 text-white px-2.5 py-1 rounded" id="timerPause">Ⅱ Pause</button>
                        <button class="tbtn stop text-xs bg-white/20 hover:bg-white/30 text-white px-2.5 py-1 rounded" id="timerStop">■ Stop</button>
                    </div>
                </div>
            </div>
            <div class="command-body">
                <div class="stage-hero">
                    <span>CURRENT STAGE</span>
                    <strong>{{ $stageNames[$currentStageIdx] ?? 'Execution' }}</strong>
                    <b class="stage-status">{{ $regular->status }}</b>
                    <p class="mt-2 text-xs opacity-80">Started<br><b>{{ $fmt($regular->planned_start_date) }}</b></p>
                </div>
                <div class="time-list">
                    <div><span>TOTAL HANDLING</span><strong>{{ intdiv($totalSec, 3600) }}h {{ intdiv($totalSec % 3600, 60) }}m</strong></div>
                    <div><span>IN PROGRESS</span><strong>{{ intdiv($totalSec, 3600) }}h {{ intdiv($totalSec % 3600, 60) }}m</strong></div>
                    <div><span>ON HOLD</span><strong>0s</strong></div>
                    <div><span>WAITING</span><strong>—</strong></div>
                    <div><span>ELAPSED</span><strong>{{ intdiv($totalSec, 3600) }}h {{ intdiv($totalSec % 3600, 60) }}m</strong></div>
                </div>
                <div class="pending-box">
                    <b>Pending:</b> {{ $ntpApproved ? ($completedTasks < $totalTasks ? 'Complete RSAT execution deliverables' : 'Finalize and transmittal') : 'Client approval of Notice to Proceed' }}
                    <a class="stage-link" href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => $stageNames[$currentStageIdx] == 'NTP' ? 'ntp' : 'execution']) }}">View Stage →</a>
                </div>
            </div>
        </div>

        <div class="command-card">
            <div class="command-head">
                <div>
                    <h2>What Is Happening Now</h2>
                    <p>Owner, activity, dependency and next move</p>
                </div>
            </div>
            <div class="command-body now-grid">
                <div><span>Current Stage</span><strong>{{ $stageNames[$currentStageIdx] ?? 'Execution' }}</strong></div>
                <div><span>Currently Being Handled By</span><strong>{{ $regular->assigned_project_manager ?: 'John Kelly Abalde' }}</strong></div>
                <div><span>Current Activity</span><strong>{{ $inProgressTasks > 0 ? "Executing {$inProgressTasks} approved RSAT requirements" : 'Active retainer management and compliance' }}</strong></div>
                <div><span>Waiting For</span><strong>{{ $ntpApproved ? 'Ongoing task completion' : 'Signed Notice to Proceed confirmation' }}</strong></div>
                <div><span>Waiting Since</span><strong>{{ $fmt($regular->planned_start_date) }}</strong></div>
                <div><span>Next Expected Stage</span><strong>{{ $currentStageIdx < 4 ? $stageNames[$currentStageIdx + 1] : 'Delivery & Transmittal' }}</strong></div>
                <div><span>Next Required Action</span><strong>{{ $completedTasks < $totalTasks ? 'Execute open requirements' : 'Issue cycle transmittal' }}</strong></div>
            </div>
        </div>
    </div>

    <!-- 5. REGULAR TIME & PERFORMANCE -->
    <div class="command-card">
        <div class="command-head">
            <div>
                <h2>Regular Time &amp; Performance</h2>
                <p>Aggregated from the existing Stage Management records</p>
            </div>
            <div class="indicator-key">
                <span class="tone-good">● Good</span>
                <span class="tone-warning">● Warning</span>
                <span class="tone-critical">● Critical</span>
            </div>
        </div>
        <div class="command-body command-kpis">
            <div class="command-kpi">
                <span>TOTAL HANDLING</span>
                <strong>{{ intdiv($totalSec, 3600) }}h {{ intdiv($totalSec % 3600, 60) }}m</strong>
                <small>In Progress + On Hold</small>
            </div>
            <div class="command-kpi">
                <span>TOTAL IN PROGRESS</span>
                <strong>{{ intdiv($totalSec, 3600) }}h {{ intdiv($totalSec % 3600, 60) }}m</strong>
                <small>Active work</small>
            </div>
            <div class="command-kpi">
                <span>TOTAL ON HOLD</span>
                <strong>0m</strong>
                <small>Paused work</small>
            </div>
            <div class="command-kpi">
                <span>TOTAL WAITING</span>
                <strong>—</strong>
                <small>Pending actions</small>
            </div>
            <div class="command-kpi">
                <span>TOTAL ELAPSED</span>
                <strong>{{ intdiv($totalSec, 3600) }}h {{ intdiv($totalSec % 3600, 60) }}m</strong>
                <small>Clock time</small>
            </div>
            <div class="command-kpi">
                <span>AVERAGE STAGE</span>
                <strong>{{ round($totalSec / max(1, $currentStageIdx + 1) / 60) }}m</strong>
                <small>Handling average</small>
            </div>
            <div class="command-kpi">
                <span>HISTORICAL AVERAGE</span>
                <strong>4h 30m</strong>
                <small>Configured benchmark</small>
            </div>
            <div class="command-kpi">
                <span>DIFFERENCE</span>
                <strong class="tone-good">−2h 30m</strong>
                <small>Versus historical</small>
            </div>
            <div class="command-kpi">
                <span>PERFORMANCE</span>
                <strong class="tone-good">Good</strong>
                <small>Than average</small>
            </div>
        </div>
    </div>

    <!-- 6. STAGE PERFORMANCE TABLE -->
    <div class="command-card">
        <div class="command-head">
            <div>
                <h2>Stage Performance</h2>
                <p>See which lifecycle stage is creating delay</p>
            </div>
        </div>
        <div class="command-body table-scroll">
            <table class="performance-table">
                <thead>
                    <tr>
                        <th>Stage</th>
                        <th>Status</th>
                        <th>Handling</th>
                        <th>Waiting</th>
                        <th>Elapsed</th>
                        <th>Historical Avg.</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stages as $st)
                        <tr>
                            <td>
                                <a href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => $st['tab']]) }}" class="font-bold text-blue-700 hover:underline">
                                    {{ $st['name'] }}
                                </a>
                            </td>
                            <td>{{ $st['status'] }}</td>
                            <td>{{ $st['handling'] }}</td>
                            <td>{{ $st['waiting'] }}</td>
                            <td>{{ $st['elapsed'] }}</td>
                            <td>{{ $st['avg'] }}</td>
                            <td>
                                <span class="perf {{ $st['perfClass'] }}">{{ $st['perf'] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- 7. EXECUTION PERFORMANCE -->
    <div class="command-card execution-command">
        <div class="command-head">
            <div>
                <h2>Execution Performance</h2>
                <p>Approved RSAT task progress, actual effort, schedule condition and responsibility</p>
            </div>
            <a class="stage-link font-bold text-blue-700 hover:underline text-xs" href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'execution']) }}">Open Execution →</a>
        </div>
        <div class="command-body">
            <div class="execution-kpis">
                <div class="execution-kpi">
                    <span>TOTAL REQUIREMENTS</span>
                    <strong>{{ $totalTasks }}</strong>
                    <small>Defined in RSAT</small>
                </div>
                <div class="execution-kpi good">
                    <span>COMPLETED</span>
                    <strong>{{ $completedTasks }}</strong>
                    <small>{{ $progressPct }}% complete</small>
                </div>
                <div class="execution-kpi">
                    <span>IN PROGRESS</span>
                    <strong>{{ $inProgressTasks }}</strong>
                    <small>Active execution</small>
                </div>
                <div class="execution-kpi">
                    <span>OPEN</span>
                    <strong>{{ $openTasks }}</strong>
                    <small>Queued</small>
                </div>
                <div class="execution-kpi">
                    <span>TOTAL TIME</span>
                    <strong>{{ intdiv($totalSec, 3600) }}h {{ intdiv($totalSec % 3600, 60) }}m</strong>
                    <small>Accumulated</small>
                </div>
                <div class="execution-kpi">
                    <span>AHT / REQ</span>
                    <strong>{{ round($ahtSec / 60) }}m</strong>
                    <small>Average handling</small>
                </div>
            </div>
            <div class="execution-progress-wrap">
                <div>
                    <strong>Approved RSAT Execution Progress</strong>
                    <span>{{ $progressPct }}% Complete ({{ $completedTasks }}/{{ $totalTasks }})</span>
                </div>
                <div class="execution-progress">
                    <i style="width: {{ $progressPct }}%"></i>
                </div>
            </div>
            <div class="table-scroll">
                <table class="performance-table execution-performance-table">
                    <thead>
                        <tr>
                            <th>Main Task</th>
                            <th>Task Progress</th>
                            <th>Planned</th>
                            <th>Working</th>
                            <th>On Hold</th>
                            <th>Total</th>
                            <th>Schedule</th>
                            <th>Client Updates</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tasks->take(5) as $idx => $t)
                            @php
                                $tDone = ($t['status'] ?? '') === 'completed';
                            @endphp
                            <tr>
                                <td><strong>{{ $t['requirement'] ?? ('Requirement #' . ($idx + 1)) }}</strong></td>
                                <td>
                                    <div class="execution-task-split">
                                        <span class="{{ $tDone ? 'done' : 'active' }}">{{ $tDone ? '100%' : '50%' }}</span>
                                    </div>
                                </td>
                                <td>{{ $t['timeline'] ?? '1w' }}</td>
                                <td>{{ $tDone ? '1h 30m' : '45m' }}</td>
                                <td>0m</td>
                                <td>{{ $tDone ? '1h 30m' : '45m' }}</td>
                                <td><span class="perf good">On Track</span></td>
                                <td>{{ $t['status'] ?? 'Open' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-slate-400">No RSAT requirements defined yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 8. EXECUTION REPORTING -->
    <div class="command-card execution-report-command">
        <div class="command-head">
            <div>
                <h2>Execution Reporting</h2>
                <p>Permanent updates, attachments, client delivery and acknowledgment evidence</p>
            </div>
            <a class="stage-link font-bold text-blue-700 hover:underline text-xs" href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'report']) }}">Open RSAT Report →</a>
        </div>
        <div class="command-body">
            <div class="execution-report-kpis">
                <div class="execution-kpi">
                    <span>GENERATED REPORTS</span>
                    <strong>{{ $generatedReports->count() }}</strong>
                    <small>Snapshots</small>
                </div>
                <div class="execution-kpi good">
                    <span>DELIVERABLES READY</span>
                    <strong>{{ $deliverables->where('ready', true)->count() }} / {{ $deliverables->count() }}</strong>
                    <small>Milestone evidence</small>
                </div>
                <div class="execution-kpi">
                    <span>EVIDENCE ATTACHED</span>
                    <strong>{{ $rsatAttachments->count() }}</strong>
                    <small>Files uploaded</small>
                </div>
                <div class="execution-kpi">
                    <span>CLIENT TRANSMITTAL</span>
                    <strong>{{ $metadata['transmittal_status'] ?? 'Pending' }}</strong>
                    <small>Formal delivery</small>
                </div>
            </div>
            <div class="execution-report-grid">
                <div>
                    <h4 class="font-bold text-xs uppercase tracking-wider text-slate-500 mb-2">Latest Client Reports</h4>
                    <div class="execution-report-list">
                        @forelse($generatedReports->take(2) as $rep)
                            <div class="execution-report-item good">
                                <strong>{{ $rep->report_name ?: 'Monthly RSAT Retainer Report' }}</strong>
                                <time>{{ $rep->created_at?->format('M d, Y h:i A') }}</time>
                                <p>{{ Str::limit($rep->summary_notes ?: 'Monthly executive update and deliverables status report.', 90) }}</p>
                            </div>
                        @empty
                            <div class="execution-report-item">
                                <strong>Cycle {{ $cycleState['cycle_number'] }} Retainer Report Draft</strong>
                                <time>{{ now()->format('M d, Y') }}</time>
                                <p>Report draft prepared from current RSAT task progress.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div>
                    <h4 class="font-bold text-xs uppercase tracking-wider text-slate-500 mb-2">Reporting Attention</h4>
                    <div class="execution-report-list">
                        @if($generatedReports->isEmpty())
                            <div class="execution-report-item warning">
                                <strong>Cycle Report Required</strong>
                                <p>Generate the formal monthly report for Cycle {{ $cycleState['cycle_number'] }} before closing.</p>
                            </div>
                        @else
                            <div class="execution-report-item good">
                                <strong>Report Generated</strong>
                                <p>Latest cycle report is saved and ready for client delivery.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 9. REGULAR WORK SUMMARY -->
    <div class="command-card">
        <div class="command-head">
            <div>
                <h2>Regular Work Summary</h2>
                <p>Completion, documents, approvals, issues and actions</p>
            </div>
        </div>
        <div class="command-body summary-links">
            <a href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'execution']) }}">
                <span>TASKS</span>
                <strong>{{ $completedTasks }} / {{ $totalTasks }}</strong>
            </a>
            <a href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'delivery']) }}">
                <span>DELIVERABLES</span>
                <strong>{{ $deliverables->where('ready', true)->count() }} / {{ $deliverables->count() }}</strong>
            </a>
            <a href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'attachments']) }}">
                <span>DOCUMENTS</span>
                <strong>{{ $rsatAttachments->count() + $generatedReports->count() + ($ntpRecord ? 1 : 0) }}</strong>
            </a>
            <a href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'review']) }}">
                <span>PENDING APPROVALS</span>
                <strong>{{ $ntpApproved ? '0' : '1' }}</strong>
            </a>
            <a href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'history']) }}">
                <span>OPEN ISSUES</span>
                <strong>0</strong>
            </a>
            <a href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'client-actions']) }}">
                <span>PENDING ACTIONS</span>
                <strong>{{ $clientActions->where('done', false)->count() }}</strong>
            </a>
        </div>
    </div>

    <!-- 10. RECENT ACTIVITY -->
    <div class="command-card">
        <div class="command-head">
            <div>
                <h2>Recent Activity</h2>
                <p>Latest meaningful regular service events</p>
            </div>
            <a class="stage-link font-bold text-blue-700 hover:underline text-xs" href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'history']) }}">View All Activity →</a>
        </div>
        <div class="command-body activity-list">
            <div class="activity-row">
                <time>{{ now()->format('M d, g:i A') }}</time>
                <i></i>
                <span>RSAT Retainer Workspace synchronized with {{ $regular->project_code }}</span>
            </div>
            @if($ntpApproved)
                <div class="activity-row">
                    <time>{{ \Carbon\Carbon::parse($ntpRecord->client_approved_at)->format('M d, g:i A') }}</time>
                    <i></i>
                    <span>Notice to Proceed confirmed by client ({{ $contactName }})</span>
                </div>
            @endif
            @if($rsat?->approved_at)
                <div class="activity-row">
                    <time>{{ \Carbon\Carbon::parse($rsat->approved_at)->format('M d, g:i A') }}</time>
                    <i></i>
                    <span>RSAT approved internally by Lead Consultant</span>
                </div>
            @endif
            <div class="activity-row">
                <time>{{ $regular->created_at?->format('M d, g:i A') }}</time>
                <i></i>
                <span>Regular engagement initiated from originating Deal {{ $regular->deal?->deal_code ?? '' }}</span>
            </div>
        </div>
    </div>
</div>

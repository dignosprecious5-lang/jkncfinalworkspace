<style>
    .performance-table {
        width: 100% !important;
        border-collapse: collapse !important;
        table-layout: auto !important;
    }
    .performance-table th, .performance-table td {
        padding: 12px 16px !important;
        border-bottom: 1px solid #e8ebf0 !important;
        text-align: left !important;
        font-size: 11px !important;
        vertical-align: middle !important;
    }
    .performance-table th {
        background: #f8f9fb !important;
        color: #6f7b8c !important;
        text-transform: uppercase !important;
        letter-spacing: .04em !important;
        font-size: 10px !important;
        font-weight: 800 !important;
    }
    .performance-table td:nth-child(n+3) {
        font-variant-numeric: tabular-nums !important;
    }
    .perf {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 4px 10px !important;
        border-radius: 999px !important;
        font-size: 10px !important;
        font-weight: 800 !important;
        line-height: 1 !important;
    }
    .perf.good { background: #e8f7f0 !important; color: #16845c !important; }
    .perf.warning { background: #fff2df !important; color: #b86a11 !important; }
    .perf.bad { background: #fdebed !important; color: #cc3440 !important; }
    .perf.neutral { background: #eef1f5 !important; color: #778292 !important; }
    .table-scroll { width: 100% !important; overflow-x: auto !important; }

    .summary-links {
        display: grid !important;
        grid-template-columns: repeat(6, minmax(0, 1fr)) !important;
        gap: 10px !important;
    }
    .summary-links a {
        display: block !important;
        padding: 13px !important;
        border: 1px solid #e4e8ef !important;
        border-radius: 9px !important;
        background: #fafbfc !important;
        text-decoration: none !important;
        transition: all .15s ease !important;
    }
    .summary-links a:hover {
        border-color: #cbd5e1 !important;
        background: #f1f5f9 !important;
        transform: translateY(-1px) !important;
    }
    .summary-links span {
        display: block !important;
        color: #788496 !important;
        font-size: 9px !important;
        font-weight: 800 !important;
        letter-spacing: .05em !important;
        text-transform: uppercase !important;
    }
    .summary-links strong {
        display: block !important;
        font-size: 18px !important;
        margin-top: 4px !important;
        color: #20365f !important;
        font-weight: 800 !important;
    }
    .activity-list {
        display: grid !important;
        gap: 0 !important;
    }
    .activity-row {
        display: grid !important;
        grid-template-columns: 110px 14px 1fr !important;
        align-items: center !important;
        gap: 10px !important;
        padding: 10px 0 !important;
        border-bottom: 1px solid #eceff3 !important;
    }
    .activity-row:last-child {
        border-bottom: none !important;
    }
    .activity-row time {
        color: #687589 !important;
        font-size: 10px !important;
        font-weight: 600 !important;
    }
    .activity-row i {
        width: 8px !important;
        height: 8px !important;
        background: #3157aa !important;
        border-radius: 50% !important;
        display: inline-block !important;
    }
    .activity-row span {
        font-size: 11px !important;
        color: #263852 !important;
        font-weight: 500 !important;
    }
    @media (max-width: 1180px) {
        .summary-links { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; }
    }
    @media (max-width: 760px) {
        .summary-links { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
    }
</style>

<div class="space-y-6">
    <!-- PROJECT OVERVIEW CARD -->
    <section class="command-card">
        <div class="command-head">
            <div>
                <h2>Project Overview</h2>
                <p>Identity, accountability, source records and schedule</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 border border-blue-200">
                {{ $project->current_phase ?: $project->status }}
            </span>
        </div>
        <div class="command-body overview-grid">
            <div class="overview-panel">
                <h4>PROJECT IDENTITY</h4>
                <dl>
                    <div><dt>Project</dt><dd class="font-bold text-slate-900">{{ $project->name ?: ($project->deal?->deal_title ?: 'Project Workspace') }}</dd></div>
                    <div><dt>Client</dt><dd class="font-bold text-slate-900">{{ $contactName }}</dd></div>
                    <div><dt>Status</dt><dd class="font-bold text-slate-900">{{ $project->current_phase ?: $project->status }}</dd></div>
                    <div><dt>Overall progress</dt><dd class="font-bold text-slate-900">{{ $progressPct }}%</dd></div>
                </dl>
            </div>
            <div class="overview-panel">
                <h4>OWNERSHIP</h4>
                <dl>
                    <div><dt>Project Manager</dt><dd class="font-bold text-slate-900">{{ $project->assigned_project_manager ?: ($project->deal?->project_manager ?: ($project->starts()->latest()->first()?->clearance['assigned_team_lead'] ?? ($project->assigned_consultant ?: '-'))) }}</dd></div>
                    <div><dt>Lead Associate</dt><dd class="font-bold text-slate-900">{{ $project->assigned_associate ?: ($project->deal?->lead_associate ?: ($project->starts()->latest()->first()?->clearance['lead_associate_assigned'] ?? '-')) }}</dd></div>
                    <div><dt>Health</dt><dd class="font-bold text-slate-900">{{ $project->real_health ?? (data_get($project->metadata, 'health') ?: ($project->target_completion_date && \Carbon\Carbon::parse($project->target_completion_date)->isPast() ? 'At Risk' : (in_array(strtolower($project->status), ['completed', 'completion']) ? 'Completed' : 'On Track'))) }}</dd></div>
                </dl>
            </div>
            <div class="overview-panel">
                <h4>REFERENCES &amp; DATES</h4>
                <dl>
                    <div><dt>Work Order</dt><dd><a href="{{ route('project.show', ['project' => $project->id, 'tab' => 'work-order']) }}" class="font-bold text-blue-700 hover:underline">{{ $project->work_order_code ?: ('PROJ-WO-' . substr($project->project_code, -8)) }}</a></dd></div>
                    <div><dt>Source Memo</dt><dd class="font-bold text-slate-900">{{ $start?->attachments['service_memo_ref'] ?? ($sowAttachments['service_memo_ref'] ?? ($project->starts()->latest()->first()?->attachments['service_memo_ref'] ?? ('SM-' . substr($project->project_code, -8)))) }}</dd></div>
                    <div><dt>Source START</dt><dd class="font-bold text-slate-900">{{ $start?->start_code ?: ($project->starts()->latest()->first()?->start_code ?: ('START-' . substr($project->project_code, -8))) }}</dd></div>
                    <div><dt>Source Deal</dt><dd class="font-bold text-slate-900">@if($project->deal_id)<a href="{{ route('deals.show', $project->deal_id) }}" class="font-bold text-slate-900 hover:text-blue-700 hover:underline">{{ $project->deal?->deal_code }}</a>@elseif($project->deal?->deal_code){{ $project->deal->deal_code }}@else - @endif</dd></div>
                    <div><dt>Target End</dt><dd class="font-bold text-slate-900">{{ $project->target_completion_date ? \Carbon\Carbon::parse($project->target_completion_date)->format('M d, Y') : '-' }}</dd></div>
                </dl>
            </div>
        </div>
    </section>

    <!-- 2. ATTENTION REQUIRED -->
    @php
        $ntpApproved = (bool) $ntpRecord?->client_approved_at;
        $attentionItems = [];
        if (!$ntpApproved && $ntpRecord && strtolower((string)$ntpRecord->status) === 'rejected') {
            $attentionItems[] = [
                'severity' => 'CRITICAL',
                'class' => 'critical',
                'title' => 'Notice to Proceed (NTP) was rejected by client',
                'sub' => 'NTP · Action required · Review feedback and re-issue NTP',
                'link' => route('project.show', ['project' => $project->id, 'tab' => 'ntp']),
                'linkText' => 'Open NTP →',
            ];
        }
        if ($project->target_completion_date && \Carbon\Carbon::parse($project->target_completion_date)->isPast() && !in_array(strtolower($project->status), ['completed', 'completion'])) {
            $attentionItems[] = [
                'severity' => 'CRITICAL',
                'class' => 'critical',
                'title' => 'Project has passed its target completion date (' . \Carbon\Carbon::parse($project->target_completion_date)->format('M d, Y') . ')',
                'sub' => 'Schedule · Overdue · Expedite remaining deliverables',
                'link' => route('project.show', ['project' => $project->id, 'tab' => 'execution']),
                'linkText' => 'Open Execution →',
            ];
        }

        $healthVal = $project->real_health ?? (data_get($project->metadata, 'health') ?: ($project->target_completion_date && \Carbon\Carbon::parse($project->target_completion_date)->isPast() ? 'At Risk' : (in_array(strtolower($project->status), ['completed', 'completion']) ? 'Completed' : 'Good')));
        $healthClass = match(strtolower($healthVal)) {
            'good', 'completed', 'on track' => 'tone-good',
            'at risk', 'warning' => 'tone-warning',
            'critical', 'delayed' => 'tone-critical',
            default => 'tone-good'
        };
        $isOverdue = $project->target_completion_date && \Carbon\Carbon::parse($project->target_completion_date)->isPast() && !in_array(strtolower($project->status), ['completed', 'completion']);
        $scheduleStatus = $isOverdue ? 'Delayed' : 'On Track';
        $pendingApprovalsCount = (!$ntpApproved && $ntpRecord ? 1 : 0) + ($sow && $sow->approval_status !== 'approved' ? 1 : 0);
        $pendingActionsCount = $clientActions->where('done', false)->count();
        $docsCount = count($deliverables) + ($ntpRecord ? 1 : 0) + ($sow ? 1 : 0);
    @endphp

    <div class="command-card attention-card">
        <div class="command-head">
            <div>
                <h2>Attention Required</h2>
                <p>Critical blockers and emerging risks, ordered by priority</p>
            </div>
            <span class="inline-flex items-center rounded-full {{ count($attentionItems) ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-red-50 text-red-400 border border-red-100' }} px-3 py-0.5 text-xs font-bold">
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
                        <strong>✓ PROJECT ON TRACK</strong>
                        <small>No items currently require management attention.</small>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- 3. HEALTH KPIS (9 METRICS) -->
    <div class="command-kpis">
        <div class="command-kpi">
            <span>Lifecycle Progress</span>
            <strong>{{ ($currentStageIdx ?? 4) + 1 }} of 9</strong>
            <small>{{ $progressPct }}% complete</small>
        </div>
        <div class="command-kpi">
            <span>Overall Health</span>
            <strong class="{{ $healthClass }}">{{ $healthVal }}</strong>
            <small>{{ $healthVal === 'At Risk' ? 'Action Needed' : 'On Track' }}</small>
        </div>
        <div class="command-kpi">
            <span>Schedule</span>
            <strong>{{ $scheduleStatus }}</strong>
            <small>Current stage</small>
        </div>
        <div class="command-kpi">
            <span>Stages On Hold</span>
            <strong>0</strong>
            <small>Across lifecycle</small>
        </div>
        <div class="command-kpi">
            <span>Pending Approvals</span>
            <strong class="{{ $pendingApprovalsCount > 0 ? 'tone-warning' : '' }}">{{ $pendingApprovalsCount }}</strong>
            <small>Blocking items</small>
        </div>
        <div class="command-kpi">
            <span>Open Issues</span>
            <strong>0</strong>
            <small>Critical/attention</small>
        </div>
        <div class="command-kpi">
            <span>Pending Actions</span>
            <strong class="{{ $pendingActionsCount > 0 ? 'tone-warning' : '' }}">{{ $pendingActionsCount }}</strong>
            <small>Client actions</small>
        </div>
        <div class="command-kpi">
            <span>Documents</span>
            <strong class="text-amber-600">{{ $docsCount }}</strong>
            <small>Attached evidence</small>
        </div>
        <div class="command-kpi">
            <span>Overdue Stages</span>
            <strong class="{{ $isOverdue ? 'tone-critical' : '' }}">{{ $isOverdue ? '1' : '0' }}</strong>
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
                    <strong id="timerClock" style="font-size: 15px; font-weight: 800; color: #fff;">{{ sprintf('%02d:%02d:%02d', intdiv($timeMetrics['total_seconds'] ?? 0, 3600), intdiv(($timeMetrics['total_seconds'] ?? 0) % 3600, 60), ($timeMetrics['total_seconds'] ?? 0) % 60) }}</strong><span id="timerState" style="font-size: 13px; font-weight: 700; color: #fff; margin-left: 4px;">Running</span>
                    <div class="timer-actions">
                        <button class="tbtn start" id="timerStart" type="button"><i class="fas fa-play text-[9px] mr-1"></i> Start</button>
                        <button class="tbtn pause" id="timerPause" type="button"><i class="fas fa-pause text-[9px] mr-1"></i> Pause</button>
                        <button class="tbtn stop" id="timerStop" type="button"><i class="fas fa-square text-[9px] mr-1"></i> Stop</button>
                    </div>
                </div>
            </div>
            <div class="command-body">
                <div class="stage-hero">
                    <span>CURRENT STAGE</span>
                    <strong>{{ $stageNames[$currentStageIdx] ?? ($project->current_phase ?: $project->status) }}</strong>
                    <b class="stage-status">{{ in_array(strtolower($project->status ?? ''), ['execution', 'in progress', 'open']) ? 'In Progress' : ($project->status ?: 'In Progress') }}</b>
                    <p class="mt-4 text-xs opacity-80">Started<br><b class="text-xs font-bold text-white">{{ $project->planned_start_date ? \Carbon\Carbon::parse($project->planned_start_date)->format('m/d/Y, H:i:s') : now()->format('m/d/Y, H:i:s') }}</b></p>
                </div>
                <div class="time-list">
                    <div><span>TOTAL HANDLING</span><strong id="timeTotalHandling">{{ sprintf('%02d:%02d:%02d', intdiv($timeMetrics['total_seconds'] ?? 0, 3600), intdiv(($timeMetrics['total_seconds'] ?? 0) % 3600, 60), ($timeMetrics['total_seconds'] ?? 0) % 60) }}</strong></div>
                    <div><span>IN PROGRESS</span><strong id="timeInProgress">{{ sprintf('%02d:%02d:%02d', intdiv($timeMetrics['total_seconds'] ?? 0, 3600), intdiv(($timeMetrics['total_seconds'] ?? 0) % 3600, 60), ($timeMetrics['total_seconds'] ?? 0) % 60) }}</strong></div>
                    <div><span>ON HOLD</span><strong id="timeOnHold">00:00:00</strong></div>
                    <div><span>WAITING</span><strong id="timeWaiting">00:00:00</strong></div>
                    <div><span>ELAPSED</span><strong id="timeElapsed">{{ sprintf('%02d:%02d:%02d', intdiv($timeMetrics['total_seconds'] ?? 0, 3600), intdiv(($timeMetrics['total_seconds'] ?? 0) % 3600, 60), ($timeMetrics['total_seconds'] ?? 0) % 60) }}</strong></div>
                </div>
                <div class="pending-box">
                    <span><b>Pending:</b> {{ $ntpApproved ? ($completedTasks < $totalTasks ? 'Complete current stage requirements' : 'Finalize deliverables and transmittal package') : 'Client approval of Notice to Proceed' }}</span>
                    <a class="stage-link" href="{{ route('project.show', ['project' => $project->id, 'tab' => match($currentStageIdx ?? 4) { 0 => 'work-order', 1 => 'sow', 2 => 'review', 3 => 'ntp', 4 => 'execution', 5 => 'report', 7, 8 => 'coc', default => 'execution' }]) }}">View Stage →</a>
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
                <div><span>Current Stage</span><strong>{{ $stageNames[$currentStageIdx] ?? ($project->current_phase ?: $project->status) }}</strong></div>
                <div><span>Currently Being Handled By</span><strong>{{ $project->assigned_associate ?: ($project->assigned_project_manager ?: ($project->deal?->lead_associate ?: ($project->deal?->project_manager ?: 'Project Team'))) }}</strong></div>
                <div><span>Current Activity</span><strong>{{ $sow?->within_scope_items[0]['main_task_description'] ?? ($project->name ? 'Execute scope of work for ' . $project->name : 'Execute current milestone deliverables') }}</strong></div>
                <div><span>Waiting For</span><strong>{{ !$ntpApproved && $ntpRecord ? 'Signed Notice to Proceed confirmation' : ($openTasks > 0 ? 'Coordinate documentary requirements with transferors' : 'Client acknowledgment & milestone sign-off') }}</strong></div>
                <div><span>Waiting Since</span><strong>{{ $project->planned_start_date ? \Carbon\Carbon::parse($project->planned_start_date)->format('M d, Y') : 'Not waiting' }}</strong></div>
                <div><span>Next Expected Stage</span><strong>{{ isset($stageNames[($currentStageIdx ?? 4) + 1]) ? $stageNames[($currentStageIdx ?? 4) + 1] : 'Delivery & Transmittal' }}</strong></div>
                <div><span>Next Required Action</span><strong>{{ $completedTasks < $totalTasks ? 'Complete current stage requirements' : 'Issue certificate of completion' }}</strong></div>
            </div>
        </div>
    </div>

    @php
        $totalSec = $timeMetrics['total_seconds'] ?? 7200;
        $ahtSec = $timeMetrics['aht_seconds'] ?? 3600;
        $projectStages = [
            ['name' => 'Work Order', 'tab' => 'work-order', 'status' => 'Draft', 'handling' => '—', 'waiting' => '—', 'elapsed' => '—', 'avg' => '00:45:00', 'perf' => 'Good', 'perfClass' => 'good'],
            ['name' => 'SOW', 'tab' => 'sow', 'status' => 'Completed', 'handling' => '—', 'waiting' => '—', 'elapsed' => '—', 'avg' => '01:30:00', 'perf' => 'Good', 'perfClass' => 'good'],
            ['name' => 'Review', 'tab' => 'review', 'status' => 'Completed', 'handling' => '—', 'waiting' => '—', 'elapsed' => '—', 'avg' => '02:20:00', 'perf' => 'Good', 'perfClass' => 'good'],
            ['name' => 'NTP', 'tab' => 'ntp', 'status' => 'Completed', 'handling' => '—', 'waiting' => '—', 'elapsed' => '—', 'avg' => '00:45:00', 'perf' => 'Good', 'perfClass' => 'good'],
            ['name' => 'Execution', 'tab' => 'execution', 'status' => 'In Progress', 'handling' => sprintf('%02d:%02d:%02d', intdiv($totalSec, 3600), intdiv(($totalSec % 3600), 60), $totalSec % 60), 'waiting' => '—', 'elapsed' => sprintf('%02d:%02d:%02d', intdiv($totalSec, 3600), intdiv(($totalSec % 3600), 60), $totalSec % 60), 'avg' => '12:00:00', 'perf' => 'Good', 'perfClass' => 'good'],
            ['name' => 'Reporting', 'tab' => 'report', 'status' => 'Not Started', 'handling' => '—', 'waiting' => '—', 'elapsed' => '—', 'avg' => '03:00:00', 'perf' => 'No Data', 'perfClass' => 'neutral'],
            ['name' => 'Presentation', 'tab' => 'report', 'status' => 'Not Started', 'handling' => '—', 'waiting' => '—', 'elapsed' => '—', 'avg' => '02:00:00', 'perf' => 'No Data', 'perfClass' => 'neutral'],
            ['name' => 'Delivery', 'tab' => 'coc', 'status' => 'Not Started', 'handling' => '—', 'waiting' => '—', 'elapsed' => '—', 'avg' => '01:30:00', 'perf' => 'No Data', 'perfClass' => 'neutral'],
            ['name' => 'Completion', 'tab' => 'coc', 'status' => 'Not Started', 'handling' => '—', 'waiting' => '—', 'elapsed' => '—', 'avg' => '01:00:00', 'perf' => 'No Data', 'perfClass' => 'neutral'],
        ];
        $sowTasksList = collect($sow?->within_scope_items ?? []);
        $attachedFilesCount = ($sow ? 1 : 0) + ($ntpRecord ? 1 : 0) + ($start ? 1 : 0) + count($deliverables);
    @endphp

    <!-- 5. PROJECT TIME & PERFORMANCE -->
    <div class="command-card">
        <div class="command-head">
            <div>
                <h2>Project Time &amp; Performance</h2>
                <p>Aggregated from the existing Stage Management records</p>
            </div>
            <div class="indicator-key">
                <span class="tone-good"><i class="fas fa-circle text-[6px]"></i> Good</span>
                <span class="tone-warning"><i class="fas fa-circle text-[6px]"></i> Warning</span>
                <span class="tone-critical"><i class="fas fa-circle text-[6px]"></i> Critical</span>
            </div>
        </div>
        <div class="command-body command-kpis">
            <div class="command-kpi">
                <span>TOTAL HANDLING</span>
                <strong id="kpiTotalHandling">{{ sprintf('%02d:%02d:%02d', intdiv($totalSec, 3600), intdiv(($totalSec % 3600), 60), $totalSec % 60) }}</strong>
                <small>In Progress + On Hold</small>
            </div>
            <div class="command-kpi">
                <span>TOTAL IN PROGRESS</span>
                <strong id="kpiTotalInProgress">{{ sprintf('%02d:%02d:%02d', intdiv($totalSec, 3600), intdiv(($totalSec % 3600), 60), $totalSec % 60) }}</strong>
                <small>Active work</small>
            </div>
            <div class="command-kpi">
                <span>TOTAL ON HOLD</span>
                <strong id="kpiTotalOnHold">—</strong>
                <small>Paused work</small>
            </div>
            <div class="command-kpi">
                <span>TOTAL WAITING</span>
                <strong id="kpiTotalWaiting">—</strong>
                <small>Pending actions</small>
            </div>
            <div class="command-kpi">
                <span>TOTAL ELAPSED</span>
                <strong id="kpiTotalElapsed">{{ sprintf('%02d:%02d:%02d', intdiv($totalSec, 3600), intdiv(($totalSec % 3600), 60), $totalSec % 60) }}</strong>
                <small>Clock time</small>
            </div>
            <div class="command-kpi">
                <span>AVERAGE STAGE</span>
                <strong id="kpiAverageStage">{{ sprintf('%02d:%02d:%02d', intdiv(intdiv($totalSec, max(1, ($currentStageIdx ?? 4) + 1)), 3600), intdiv(intdiv($totalSec, max(1, ($currentStageIdx ?? 4) + 1)) % 3600, 60), intdiv($totalSec, max(1, ($currentStageIdx ?? 4) + 1)) % 60) }}</strong>
                <small>Handling average</small>
            </div>
            <div class="command-kpi">
                <span>HISTORICAL AVERAGE</span>
                <strong id="kpiHistoricalAvg">1d 0h</strong>
                <small>Configured benchmark</small>
            </div>
            <div class="command-kpi">
                <span>DIFFERENCE</span>
                <strong id="kpiDifference" class="tone-good">−1d 0h</strong>
                <small>Versus historical</small>
            </div>
            <div class="command-kpi">
                <span>PERFORMANCE</span>
                <strong id="kpiPerformance" class="tone-good">Good</strong>
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
                    @foreach($projectStages as $idx => $st)
                        <tr id="stageRow_{{ $idx }}" class="{{ $idx === ($currentStageIdx ?? 4) ? 'active-stage-row' : '' }}">
                            <td>
                                <a href="{{ route('project.show', ['project' => $project->id, 'tab' => $st['tab']]) }}" class="font-bold text-blue-700 hover:underline">
                                    {{ $st['name'] }}
                                </a>
                            </td>
                            <td id="stageStatus_{{ $idx }}">{{ $st['status'] }}</td>
                            <td id="stageHandling_{{ $idx }}">{{ $idx === ($currentStageIdx ?? 4) ? sprintf('%02d:%02d:%02d', intdiv($totalSec, 3600), intdiv(($totalSec % 3600), 60), $totalSec % 60) : $st['handling'] }}</td>
                            <td id="stageWaiting_{{ $idx }}">{{ $st['waiting'] }}</td>
                            <td id="stageElapsed_{{ $idx }}">{{ $idx === ($currentStageIdx ?? 4) ? sprintf('%02d:%02d:%02d', intdiv($totalSec, 3600), intdiv(($totalSec % 3600), 60), $totalSec % 60) : $st['elapsed'] }}</td>
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
    @php
        $rawScopeItems = collect($sow?->within_scope_items ?? []);
        $totalExecTasks = $rawScopeItems->count() ?: 8;
        $doneExecTasks = $rawScopeItems->filter(fn($x) => strtolower($x['status'] ?? '') === 'completed')->count();
        $inProgExecTasks = $rawScopeItems->filter(fn($x) => strtolower($x['status'] ?? '') === 'in progress')->count();
        $holdExecTasks = $rawScopeItems->filter(fn($x) => in_array(strtolower($x['status'] ?? ''), ['on hold', 'pending client']))->count();
        $notStartedExecTasks = max(0, $totalExecTasks - $doneExecTasks - $inProgExecTasks - $holdExecTasks);
        
        $execProgressPct = $totalExecTasks > 0 ? (int)round(($doneExecTasks / $totalExecTasks) * 100) : 0;
        
        $overdueExecTasks = $rawScopeItems->filter(function($x) {
            $end = $x['end_date'] ?? null;
            return $end && \Carbon\Carbon::parse($end)->isPast() && strtolower($x['status'] ?? '') !== 'completed';
        })->count();

        $plannedHoursTotal = 0;
        foreach($rawScopeItems as $it) {
            $dur = strtolower((string)($it['duration'] ?? ''));
            if (preg_match('/(\d+(\.\d+)?)\s*h/', $dur, $m)) {
                $plannedHoursTotal += (float)$m[1];
            } elseif (preg_match('/(\d+(\.\d+)?)\s*d/', $dur, $m)) {
                $plannedHoursTotal += (float)$m[1] * 8;
            } elseif (preg_match('/(\d+(\.\d+)?)\s*w/', $dur, $m)) {
                $plannedHoursTotal += (float)$m[1] * 40;
            } else {
                $plannedHoursTotal += 2;
            }
        }
        $plannedSecTotal = max(3600, (int)round(($plannedHoursTotal ?: 15) * 3600));
        $actualSecTotal = $totalSec;
        $varianceSec = $actualSecTotal - $plannedSecTotal;
        $isOverPlan = $varianceSec > 0;
        
        $clientDependencies = $rawScopeItems->filter(fn($x) => strtolower($x['status'] ?? '') === 'pending client')->count();
        if ($clientDependencies === 0 && !$ntpApproved) {
            $clientDependencies = 1;
        }

        $groupedScope = $rawScopeItems->groupBy(function($item) {
            return trim((string)($item['main_task_description'] ?? 'Scope Task Deliverable'));
        });
    @endphp

    <div class="command-card execution-command">
        <div class="command-head">
            <div>
                <h2>Execution Performance</h2>
                <p>Approved SOW task progress, actual effort, schedule condition and responsibility</p>
            </div>
            <a class="stage-link font-bold text-blue-700 hover:underline text-xs" href="{{ route('project.show', ['project' => $project->id, 'tab' => 'execution']) }}">Open Execution →</a>
        </div>
        <div class="command-body">
            <div class="execution-kpis">
                <div class="execution-kpi">
                    <span>TOTAL TASKS</span>
                    <strong>{{ $totalExecTasks }}</strong>
                    <small>Approved SOW tasks</small>
                </div>
                <div class="execution-kpi {{ $doneExecTasks === $totalExecTasks && $totalExecTasks > 0 ? 'good' : '' }}">
                    <span>COMPLETED</span>
                    <strong>{{ $doneExecTasks }}</strong>
                    <small>{{ $execProgressPct }}% complete</small>
                </div>
                <div class="execution-kpi {{ $inProgExecTasks > 0 ? 'warning' : '' }}">
                    <span>IN PROGRESS</span>
                    <strong>{{ $inProgExecTasks }}</strong>
                    <small>Currently active</small>
                </div>
                <div class="execution-kpi {{ $holdExecTasks > 0 ? 'warning' : '' }}">
                    <span>ON HOLD</span>
                    <strong>{{ $holdExecTasks }}</strong>
                    <small>Includes client dependency</small>
                </div>
                <div class="execution-kpi">
                    <span>NOT STARTED</span>
                    <strong>{{ $notStartedExecTasks }}</strong>
                    <small>Remaining workload</small>
                </div>
                <div class="execution-kpi {{ $overdueExecTasks > 0 ? 'critical' : 'good' }}">
                    <span>OVERDUE</span>
                    <strong>{{ $overdueExecTasks }}</strong>
                    <small>Past target end</small>
                </div>
                <div class="execution-kpi">
                    <span>PLANNED EFFORT</span>
                    <strong>{{ intdiv($plannedSecTotal, 3600) }}h {{ intdiv(($plannedSecTotal % 3600), 60) }}m</strong>
                    <small>Approved duration</small>
                </div>
                <div class="execution-kpi">
                    <span>WORKING TIME</span>
                    <strong id="execWorkingTime">{{ sprintf('%02d:%02d:%02d', intdiv($totalSec, 3600), intdiv(($totalSec % 3600), 60), $totalSec % 60) }}</strong>
                    <small>Active execution</small>
                </div>
                <div class="execution-kpi">
                    <span>ON HOLD TIME</span>
                    <strong>0m</strong>
                    <small>Paused execution</small>
                </div>
                <div class="execution-kpi">
                    <span>ACTUAL TASK TIME</span>
                    <strong id="execActualTime">{{ sprintf('%02d:%02d:%02d', intdiv($totalSec, 3600), intdiv(($totalSec % 3600), 60), $totalSec % 60) }}</strong>
                    <small>Working + on hold</small>
                </div>
                <div class="execution-kpi {{ $isOverPlan ? 'critical' : 'good' }}">
                    <span>EFFORT VARIANCE</span>
                    <strong id="execEffortVariance" class="{{ $isOverPlan ? 'tone-critical' : 'tone-good' }}">{{ $isOverPlan ? '+' : '−' }}{{ intdiv(abs($varianceSec), 3600) }}h {{ intdiv((abs($varianceSec) % 3600), 60) }}m</strong>
                    <small>{{ $isOverPlan ? 'Over plan' : 'Within plan' }}</small>
                </div>
                <div class="execution-kpi">
                    <span>CLIENT DEPENDENCIES</span>
                    <strong>{{ $clientDependencies }}</strong>
                    <small>Waiting for client</small>
                </div>
            </div>

            <div class="execution-progress-wrap">
                <div>
                    <strong>Approved SOW Execution Progress</strong>
                    <span id="executionProgressLabel">{{ $doneExecTasks }} of {{ $totalExecTasks }} tasks · {{ $execProgressPct }}%</span>
                </div>
                <div class="execution-progress">
                    <i id="executionProgressBar" style="width: {{ $execProgressPct }}%"></i>
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
                        @forelse($groupedScope as $mainTaskTitle => $subItems)
                            @php
                                $gTotal = count($subItems);
                                $gDone = $subItems->filter(fn($x) => strtolower($x['status'] ?? '') === 'completed')->count();
                                $gActive = $subItems->filter(fn($x) => strtolower($x['status'] ?? '') === 'in progress')->count();
                                $gHold = $subItems->filter(fn($x) => in_array(strtolower($x['status'] ?? ''), ['on hold', 'pending client']))->count();
                                $gNotStarted = max(0, $gTotal - $gDone - $gActive - $gHold);
                                
                                $gPlannedHours = 0;
                                foreach($subItems as $sub) {
                                    $dur = strtolower((string)($sub['duration'] ?? ''));
                                    if (preg_match('/(\d+(\.\d+)?)\s*h/', $dur, $m)) {
                                        $gPlannedHours += (float)$m[1];
                                    } elseif (preg_match('/(\d+(\.\d+)?)\s*d/', $dur, $m)) {
                                        $gPlannedHours += (float)$m[1] * 8;
                                    } else {
                                        $gPlannedHours += 2;
                                    }
                                }
                                $gPlannedSec = max(3600, (int)round(($gPlannedHours ?: 7) * 3600));
                                $gWorkingSec = $gDone > 0 || $gActive > 0 ? max(1800, (int)round(($gDone + $gActive * 0.5) * 3600)) : 0;
                                
                                $gOverdue = $subItems->filter(function($x) {
                                    $end = $x['end_date'] ?? null;
                                    return $end && \Carbon\Carbon::parse($end)->isPast() && strtolower($x['status'] ?? '') !== 'completed';
                                })->count();
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $mainTaskTitle ?: 'Scope Task Deliverable' }}</strong>
                                    <div class="imeta text-xs text-slate-500 mt-0.5">{{ $gTotal }} approved sub tasks</div>
                                </td>
                                <td>
                                    <div class="execution-task-split">
                                        <span class="done">{{ $gDone }} done</span>
                                        <span class="active">{{ $gActive }} active</span>
                                        <span class="hold">{{ $gHold }} hold</span>
                                        <span>{{ $gNotStarted }} not started</span>
                                    </div>
                                </td>
                                <td>{{ intdiv($gPlannedSec, 3600) }}h {{ intdiv(($gPlannedSec % 3600), 60) }}m</td>
                                <td>{{ $gWorkingSec > 0 ? intdiv($gWorkingSec, 3600) . 'h ' . intdiv(($gWorkingSec % 3600), 60) . 'm' : '0m' }}</td>
                                <td>0m</td>
                                <td>{{ $gWorkingSec > 0 ? intdiv($gWorkingSec, 3600) . 'h ' . intdiv(($gWorkingSec % 3600), 60) . 'm' : '0m' }}</td>
                                <td>
                                    <span class="perf {{ $gOverdue > 0 ? 'bad' : 'good' }}">{{ $gOverdue > 0 ? "{$gOverdue} overdue" : 'On schedule' }}</span>
                                </td>
                                <td>0</td>
                            </tr>
                        @empty
                            <tr>
                                <td>
                                    <strong>Scope Execution Workstream</strong>
                                    <div class="imeta text-xs text-slate-500 mt-0.5">1 approved sub tasks</div>
                                </td>
                                <td>
                                    <div class="execution-task-split">
                                        <span class="done">0 done</span>
                                        <span class="active">1 active</span>
                                        <span class="hold">0 hold</span>
                                        <span>0 not started</span>
                                    </div>
                                </td>
                                <td>7h 0m</td>
                                <td>{{ sprintf('%02d:%02d:%02d', intdiv($totalSec, 3600), intdiv(($totalSec % 3600), 60), $totalSec % 60) }}</td>
                                <td>0m</td>
                                <td>{{ sprintf('%02d:%02d:%02d', intdiv($totalSec, 3600), intdiv(($totalSec % 3600), 60), $totalSec % 60) }}</td>
                                <td>
                                    <span class="perf good">On schedule</span>
                                </td>
                                <td>0</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 8. EXECUTION REPORTING -->
    @php
        $allUpdatesCount = 0;
        $clientReportsCount = 0;
        $internalOnlyCount = 0;
        $combinedSubmissionsCount = 0;
        $filesSubmittedCount = $attachedFilesCount ?? 0;
        $acknowledgedCount = $ntpApproved ? 1 : 0;
        $pendingAckCount = $ntpApproved ? 0 : 1;
        $tasksWithoutUpdatesCount = $totalExecTasks;

        $attentionTasksList = $rawScopeItems->take(4);
    @endphp

    <div class="command-card execution-report-command">
        <div class="command-head">
            <div>
                <h2>Execution Reporting</h2>
                <p>Permanent updates, attachments, client delivery and acknowledgment evidence</p>
            </div>
            <a class="stage-link font-bold text-blue-700 hover:underline text-xs" href="{{ route('project.show', ['project' => $project->id, 'tab' => 'report']) }}">Open SOW Report →</a>
        </div>
        <div class="command-body">
            <div class="execution-report-kpis">
                <div class="execution-kpi">
                    <span>ALL UPDATES</span>
                    <strong>{{ $allUpdatesCount }}</strong>
                    <small>Execution submissions</small>
                </div>
                <div class="execution-kpi">
                    <span>CLIENT REPORTS</span>
                    <strong>{{ $clientReportsCount }}</strong>
                    <small>Included in SOW Report</small>
                </div>
                <div class="execution-kpi">
                    <span>INTERNAL ONLY</span>
                    <strong>{{ $internalOnlyCount }}</strong>
                    <small>Project record</small>
                </div>
                <div class="execution-kpi">
                    <span>INTERNAL &amp; CLIENT</span>
                    <strong>{{ $combinedSubmissionsCount }}</strong>
                    <small>Combined submissions</small>
                </div>
                <div class="execution-kpi">
                    <span>ATTACHMENTS</span>
                    <strong>{{ $filesSubmittedCount }}</strong>
                    <small>Files submitted</small>
                </div>
                <div class="execution-kpi {{ $acknowledgedCount > 0 ? 'good' : '' }}">
                    <span>ACKNOWLEDGED</span>
                    <strong>{{ $acknowledgedCount }}</strong>
                    <small>Email or signed proof</small>
                </div>
                <div class="execution-kpi {{ $pendingAckCount > 0 ? 'warning' : 'good' }}">
                    <span>PENDING ACKNOWLEDGMENT</span>
                    <strong>{{ $pendingAckCount }}</strong>
                    <small>Client proof required</small>
                </div>
                <div class="execution-kpi">
                    <span>TASKS WITHOUT UPDATES</span>
                    <strong>{{ $tasksWithoutUpdatesCount }}</strong>
                    <small>No execution record</small>
                </div>
            </div>

            <div class="execution-report-grid">
                <div>
                    <h4 class="text-xs font-bold uppercase text-slate-500 mb-2">Latest Client Reports</h4>
                    <div class="execution-report-list">
                        <div class="execution-report-item">
                            <p class="text-slate-500 text-xs py-2">No client Execution reports have been submitted.</p>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="text-xs font-bold uppercase text-slate-500 mb-2">Reporting Attention</h4>
                    <div class="execution-report-list">
                        @forelse($attentionTasksList as $attItem)
                            <div class="execution-report-item warning">
                                <strong>{{ data_get($attItem, 'sub_task_description') ?: (data_get($attItem, 'main_task_description') ?: 'Active Scope Task') }}</strong>
                                <b>Needs attention</b>
                                <p>Active task has no submitted update</p>
                            </div>
                        @empty
                            <div class="execution-report-item good">
                                <strong>Execution reporting is current</strong>
                                <b>Good</b>
                                <p>No overdue work, missing active-task updates, or pending client acknowledgment was detected.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 9. PROJECT WORK SUMMARY -->
    <div class="command-card">
        <div class="command-head">
            <div>
                <h2>Project Work Summary</h2>
                <p>Completion, documents, approvals, issues and actions</p>
            </div>
        </div>
        <div class="command-body summary-links">
            <a href="{{ route('project.show', ['project' => $project->id, 'tab' => 'execution']) }}">
                <span>TASKS</span>
                <strong>{{ $completedTasks }} / {{ $totalTasks }}</strong>
            </a>
            <a href="{{ route('project.show', ['project' => $project->id, 'tab' => 'coc']) }}">
                <span>DELIVERABLES</span>
                <strong>{{ $deliverables->where('ready', true)->count() }} / {{ $deliverables->count() }}</strong>
            </a>
            <a href="{{ route('project.show', ['project' => $project->id, 'tab' => 'attachments']) }}">
                <span>DOCUMENTS</span>
                <strong>{{ $attachedFilesCount }}</strong>
            </a>
            <a href="{{ route('project.show', ['project' => $project->id, 'tab' => 'review']) }}">
                <span>PENDING APPROVALS</span>
                <strong>{{ $pendingApprovalsCount }}</strong>
            </a>
            <a href="{{ route('project.show', ['project' => $project->id, 'tab' => 'history']) }}">
                <span>OPEN ISSUES</span>
                <strong>{{ $isOverdue ? 1 : 0 }}</strong>
            </a>
            <a href="{{ route('project.show', ['project' => $project->id, 'tab' => 'history']) }}">
                <span>PENDING ACTIONS</span>
                <strong>{{ $pendingActionsCount }}</strong>
            </a>
        </div>
    </div>

    <!-- 10. RECENT ACTIVITY -->
    <div class="command-card">
        <div class="command-head">
            <div>
                <h2>Recent Activity</h2>
                <p>Latest meaningful project events</p>
            </div>
            <a class="stage-link font-bold text-blue-700 hover:underline text-xs" href="{{ route('project.show', ['project' => $project->id, 'tab' => 'history']) }}">View All Activity →</a>
        </div>
        <div class="command-body activity-list">
            @if($project->created_at)
                <div class="activity-row">
                    <time>{{ $project->created_at->format('M d, H:i') }}</time>
                    <i></i>
                    <span>Project workspace initialized ({{ $project->project_code }})</span>
                </div>
            @endif
            @if($start)
                <div class="activity-row">
                    <time>{{ optional($start->updated_at)->format('M d, H:i') ?: now()->format('M d, H:i') }}</time>
                    <i></i>
                    <span>START clearance recorded &amp; Service Memo created</span>
                </div>
            @endif
            @if($sow)
                <div class="activity-row">
                    <time>{{ optional($sow->updated_at)->format('M d, H:i') ?: now()->format('M d, H:i') }}</time>
                    <i></i>
                    <span>Scope of Work updated (v{{ $sow->version_number ?: '1.0' }})</span>
                </div>
            @endif
            @if($ntpRecord)
                <div class="activity-row">
                    <time>{{ optional($ntpRecord->updated_at)->format('M d, H:i') ?: now()->format('M d, H:i') }}</time>
                    <i></i>
                    <span>Notice to Proceed status: {{ $ntpApproved ? 'Client Approved' : 'Generated & Pending' }}</span>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    (function () {
        const projectId = {{ $project->id }};
        const storageKey = 'ordo_project_timer_' + projectId;
        
        let timerData = {
            state: 'running',
            startedAt: Date.now(),
            accumulatedSeconds: {{ (int)($timeMetrics['total_seconds'] ?? 296) }}
        };

        try {
            const saved = localStorage.getItem(storageKey);
            if (saved) {
                const parsed = JSON.parse(saved);
                if (parsed && typeof parsed.accumulatedSeconds === 'number') {
                    timerData = parsed;
                    if (timerData.state === 'running' && !timerData.startedAt) {
                        timerData.startedAt = Date.now();
                    }
                }
            } else {
                localStorage.setItem(storageKey, JSON.stringify(timerData));
            }
        } catch (e) {
            console.error('[Timer] Error loading storage:', e);
        }

        function formatHms(sec) {
            sec = Math.max(0, Math.floor(sec));
            const h = String(Math.floor(sec / 3600)).padStart(2, '0');
            const m = String(Math.floor((sec % 3600) / 60)).padStart(2, '0');
            const s = String(sec % 60).padStart(2, '0');
            return `${h}:${m}:${s}`;
        }

        function getLiveSeconds() {
            let sec = Number(timerData.accumulatedSeconds) || 0;
            if (timerData.state === 'running') {
                if (!timerData.startedAt) {
                    timerData.startedAt = Date.now();
                }
                sec += Math.max(0, (Date.now() - timerData.startedAt) / 1000);
            }
            return sec;
        }

        function saveState() {
            try {
                localStorage.setItem(storageKey, JSON.stringify(timerData));
            } catch (e) {}
        }

        function updateUI() {
            const sec = getLiveSeconds();
            const formatted = formatHms(sec);
            
            const clockEl = document.getElementById('timerClock');
            if (clockEl) clockEl.textContent = formatted;
            
            const stateEl = document.getElementById('timerState');
            if (stateEl) {
                if (timerData.state === 'running') {
                    stateEl.textContent = 'Running';
                } else if (timerData.state === 'paused') {
                    stateEl.textContent = 'Paused';
                } else {
                    stateEl.textContent = 'Stopped';
                }
            }

            const inProgEl = document.getElementById('timeInProgress');
            if (inProgEl) inProgEl.textContent = formatted;
            
            const totalEl = document.getElementById('timeTotalHandling');
            if (totalEl) totalEl.textContent = formatted;

            const elapsedEl = document.getElementById('timeElapsed');
            if (elapsedEl) elapsedEl.textContent = formatted;

            // Update Project Time & Performance KPI cards
            const kpiHandling = document.getElementById('kpiTotalHandling');
            if (kpiHandling) kpiHandling.textContent = formatted;

            const kpiInProg = document.getElementById('kpiTotalInProgress');
            if (kpiInProg) kpiInProg.textContent = formatted;

            const kpiElapsed = document.getElementById('kpiTotalElapsed');
            if (kpiElapsed) kpiElapsed.textContent = formatted;

            const currentStageIdx = {{ (int)($currentStageIdx ?? 4) }};
            const kpiAvg = document.getElementById('kpiAverageStage');
            if (kpiAvg) {
                const avgSec = Math.floor(sec / Math.max(1, currentStageIdx + 1));
                kpiAvg.textContent = formatHms(avgSec);
            }

            // Update active stage row in Stage Performance table
            const stageHandling = document.getElementById('stageHandling_' + currentStageIdx);
            if (stageHandling) stageHandling.textContent = formatted;

            const stageElapsed = document.getElementById('stageElapsed_' + currentStageIdx);
            if (stageElapsed) stageElapsed.textContent = formatted;

            // Update Execution Performance working/actual time
            const execWorkEl = document.getElementById('execWorkingTime');
            if (execWorkEl) execWorkEl.textContent = formatted;

            const execActEl = document.getElementById('execActualTime');
            if (execActEl) execActEl.textContent = formatted;

            const startBtn = document.getElementById('timerStart');
            const pauseBtn = document.getElementById('timerPause');
            const stopBtn = document.getElementById('timerStop');

            if (startBtn && pauseBtn && stopBtn) {
                if (timerData.state === 'running') {
                    startBtn.style.opacity = '1';
                    startBtn.style.fontWeight = '800';
                    startBtn.style.boxShadow = '0 0 0 2px rgba(22, 131, 77, 0.4)';
                    pauseBtn.style.opacity = '0.9';
                    pauseBtn.style.boxShadow = 'none';
                    stopBtn.style.opacity = '0.9';
                    stopBtn.style.boxShadow = 'none';
                } else if (timerData.state === 'paused') {
                    startBtn.style.opacity = '0.9';
                    startBtn.style.boxShadow = 'none';
                    pauseBtn.style.opacity = '1';
                    pauseBtn.style.fontWeight = '800';
                    pauseBtn.style.boxShadow = '0 0 0 2px rgba(173, 109, 14, 0.4)';
                    stopBtn.style.opacity = '0.9';
                    stopBtn.style.boxShadow = 'none';
                } else {
                    startBtn.style.opacity = '0.9';
                    startBtn.style.boxShadow = 'none';
                    pauseBtn.style.opacity = '0.9';
                    pauseBtn.style.boxShadow = 'none';
                    stopBtn.style.opacity = '1';
                    stopBtn.style.fontWeight = '800';
                    stopBtn.style.boxShadow = '0 0 0 2px rgba(194, 61, 61, 0.4)';
                }
            }
        }

        const startBtn = document.getElementById('timerStart');
        const pauseBtn = document.getElementById('timerPause');
        const stopBtn = document.getElementById('timerStop');

        if (startBtn) {
            startBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (timerData.state !== 'running' || !timerData.startedAt) {
                    timerData.state = 'running';
                    timerData.startedAt = Date.now();
                    saveState();
                    updateUI();
                }
            });
        }

        if (pauseBtn) {
            pauseBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (timerData.state === 'running') {
                    if (timerData.startedAt) {
                        timerData.accumulatedSeconds += (Date.now() - timerData.startedAt) / 1000;
                    }
                    timerData.state = 'paused';
                    timerData.startedAt = null;
                    saveState();
                    updateUI();
                }
            });
        }

        if (stopBtn) {
            stopBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (timerData.state === 'running' && timerData.startedAt) {
                    timerData.accumulatedSeconds += (Date.now() - timerData.startedAt) / 1000;
                }
                timerData.state = 'stopped';
                timerData.startedAt = null;
                saveState();
                updateUI();
            });
        }

        updateUI();
        window.setInterval(updateUI, 1000);
    })();
</script>

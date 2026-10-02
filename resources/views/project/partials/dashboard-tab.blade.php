<div class="space-y-6">
    <!-- PROJECT OVERVIEW CARD (MATCHING SCREENSHOT 2) -->
    <section class="command-card">
        <div class="command-head">
            <div>
                <h2>Project Overview</h2>
                <p>Identity, accountability, source records and schedule</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 border border-blue-200">
                {{ $project->status }}
            </span>
        </div>
        <div class="command-body overview-grid">
            <div class="overview-panel">
                <h4>PROJECT IDENTITY</h4>
                <dl>
                    <div><dt>Project</dt><dd class="font-bold text-slate-900">{{ $project->name }}</dd></div>
                    <div><dt>Client</dt><dd class="font-bold text-slate-800">{{ $contactName }}</dd></div>
                    <div><dt>Status</dt><dd class="font-bold text-blue-700">{{ $project->status }}</dd></div>
                    <div><dt>Overall progress</dt><dd class="font-bold text-emerald-600">{{ $progressPct }}%</dd></div>
                </dl>
            </div>
            <div class="overview-panel">
                <h4>OWNERSHIP</h4>
                <dl>
                    <div><dt>Project Manager</dt><dd class="font-bold text-slate-900">{{ $project->assigned_project_manager ?: 'John Kelly Abalde' }}</dd></div>
                    <div><dt>Lead Associate</dt><dd class="font-bold text-slate-800">{{ $project->assigned_associate ?: 'Rubeca Potayre' }}</dd></div>
                    <div><dt>Health</dt><dd class="font-bold text-emerald-600">On Track</dd></div>
                </dl>
            </div>
            <div class="overview-panel">
                <h4>REFERENCES &amp; DATES</h4>
                <dl>
                    <div><dt>Work Order</dt><dd><a href="{{ route('project.show', ['project' => $project->id, 'tab' => 'work-order']) }}" class="font-bold text-blue-700 hover:underline">{{ 'PROJ-WO-' . substr($project->project_code, -8) }}</a></dd></div>
                    <div><dt>Source Memo</dt><dd class="font-bold text-slate-800">{{ $sowAttachments['service_memo_ref'] ?? ('SM-' . $project->project_code) }}</dd></div>
                    <div><dt>Source START</dt><dd class="font-bold text-slate-800">{{ 'START-' . substr($project->project_code, -8) }}</dd></div>
                    <div><dt>Source Deal</dt><dd class="font-bold text-blue-700">@if($project->deal_id)<a href="{{ route('deals.show', $project->deal_id) }}" class="hover:underline">{{ $project->deal?->deal_code }}</a>@else - @endif</dd></div>
                    <div><dt>Target End</dt><dd class="font-bold text-slate-800">{{ $fmt($project->target_completion_date) }}</dd></div>
                </dl>
            </div>
        </div>
    </section>

    <!-- TOP KPI CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Project Phase</span>
                <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                    {{ $project->current_phase ?: $project->status }}
                </span>
            </div>
            <div class="mt-3 text-2xl font-bold text-slate-900">{{ $project->status }}</div>
            <p class="mt-1 text-xs text-slate-500">{{ $project->project_code }} &bull; SOW v{{ $sow?->version_number ?: '1.0' }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Milestone Progress</span>
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                    {{ $progressPct }}% Complete
                </span>
            </div>
            <div class="mt-3 text-2xl font-bold text-slate-900">{{ $completedTasks }} / {{ $totalTasks }}</div>
            <div class="mt-2 w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $progressPct }}%"></div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Handling Time</span>
                <span class="inline-flex items-center rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-semibold text-purple-700">
                    Active
                </span>
            </div>
            <div class="mt-3 text-2xl font-bold text-slate-900">
                {{ intdiv($timeMetrics['total_seconds'] ?? 7200, 3600) }}h {{ intdiv(($timeMetrics['total_seconds'] ?? 7200) % 3600, 60) }}m
            </div>
            <p class="mt-1 text-xs text-slate-500">AHT / Milestone: {{ round(($timeMetrics['aht_seconds'] ?? 3600) / 60) }} min</p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">COC Status</span>
                <span class="inline-flex items-center rounded-full {{ $cocApproved ? 'bg-emerald-50 text-emerald-700' : ($cocGenerated ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600') }} px-2.5 py-0.5 text-xs font-semibold">
                    {{ $cocApproved ? 'Approved' : ($cocGenerated ? 'Generated' : 'Pending') }}
                </span>
            </div>
            <div class="mt-3 text-2xl font-bold text-slate-900">{{ $cocApproved ? 'Completed' : 'In Progress' }}</div>
            <p class="mt-1 text-xs text-slate-500">{{ $cocGenerated ? 'COC Package ready' : 'Requires full delivery' }}</p>
        </div>
    </div>

    <!-- MAIN DASHBOARD CONTENT -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Project Operations Lifecycle</h2>
                        <p class="text-xs text-slate-500">Milestone execution and delivery milestones</p>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                    <div class="rounded-xl border {{ $sow?->approval_status === 'approved' ? 'border-emerald-200 bg-emerald-50/50 text-emerald-800' : 'border-slate-200 bg-slate-50/50 text-slate-600' }} p-3.5">
                        <div class="text-xs font-bold uppercase tracking-wider">1. SOW Plan</div>
                        <div class="mt-1 text-sm font-semibold">{{ $sow?->approval_status === 'approved' ? 'Approved' : 'Draft / Review' }}</div>
                    </div>
                    <div class="rounded-xl border {{ $ntpApproved ? 'border-emerald-200 bg-emerald-50/50 text-emerald-800' : 'border-slate-200 bg-slate-50/50 text-slate-600' }} p-3.5">
                        <div class="text-xs font-bold uppercase tracking-wider">2. NTP</div>
                        <div class="mt-1 text-sm font-semibold">{{ $ntpApproved ? 'Client Approved' : 'Pending' }}</div>
                    </div>
                    <div class="rounded-xl border {{ $progressPct > 0 ? 'border-emerald-200 bg-emerald-50/50 text-emerald-800' : 'border-slate-200 bg-slate-50/50 text-slate-600' }} p-3.5">
                        <div class="text-xs font-bold uppercase tracking-wider">3. Execution</div>
                        <div class="mt-1 text-sm font-semibold">{{ $completedTasks }}/{{ $totalTasks }} Tasks</div>
                    </div>
                    <div class="rounded-xl border {{ $cocGenerated ? 'border-emerald-200 bg-emerald-50/50 text-emerald-800' : 'border-slate-200 bg-slate-50/50 text-slate-600' }} p-3.5">
                        <div class="text-xs font-bold uppercase tracking-wider">4. COC</div>
                        <div class="mt-1 text-sm font-semibold">{{ $cocGenerated ? 'Ready / Signed' : 'Pending' }}</div>
                    </div>
                </div>

                <div class="mt-6 border-t border-slate-100 pt-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="text-xs text-slate-500">
                        Milestones and scope tasks are tracked live across technical and commercial tracks.
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('project.show', ['project' => $project->id, 'tab' => 'sow']) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                            <i class="fas fa-file-contract text-slate-400"></i> Scope of Work
                        </a>
                        <a href="{{ route('project.show', ['project' => $project->id, 'tab' => 'execution']) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-blue-700 px-4 py-2 text-xs font-bold text-white hover:bg-blue-800 transition">
                            <i class="fas fa-tasks"></i> Go to Execution
                        </a>
                    </div>
                </div>
            </div>

            <!-- Deliverables Checklist -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Project Deliverables</h2>
                        <p class="text-xs text-slate-500">Mandatory package outputs and completion readiness</p>
                    </div>
                    <a href="{{ route('project.show', ['project' => $project->id, 'tab' => 'coc']) }}" class="text-xs font-bold text-blue-700 hover:text-blue-800">
                        View COC &rarr;
                    </a>
                </div>

                <div class="mt-4 space-y-2.5">
                    @foreach($deliverables as $deliv)
                        <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/50 p-3.5">
                            <div class="flex items-center gap-3">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full {{ $deliv['ready'] ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-200 text-slate-400' }}">
                                    <i class="fas {{ $deliv['ready'] ? 'fa-check' : 'fa-circle' }} text-[10px]"></i>
                                </span>
                                <span class="text-sm font-semibold text-slate-800">{{ $deliv['name'] }}</span>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $deliv['ready'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                                {{ $deliv['ready'] ? 'Ready' : 'In Progress' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right Side Team & Client Actions -->
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Project Leadership</h2>
                <div class="mt-4 space-y-3.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Project Manager</span>
                        <span class="font-bold text-slate-800">{{ $project->assigned_project_manager ?: 'Not assigned' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Lead Consultant</span>
                        <span class="font-bold text-slate-800">{{ $project->assigned_consultant ?: 'Not assigned' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Associate Consultant</span>
                        <span class="font-bold text-slate-800">{{ $project->assigned_associate ?: 'Not assigned' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Client Signatory</span>
                        <span class="font-bold text-slate-800">{{ $contactName }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Client Checkpoints</h2>
                <div class="mt-4 space-y-2.5">
                    @foreach($clientActions as $action)
                        <div class="flex items-start gap-2.5 text-xs">
                            <i class="fas {{ $action['done'] ? 'fa-check-circle text-emerald-600' : 'fa-hourglass-half text-amber-500' }} mt-0.5"></i>
                            <span class="{{ $action['done'] ? 'text-slate-500 line-through' : 'font-medium text-slate-800' }}">{{ $action['subject'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

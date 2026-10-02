<div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-6">
    <div class="border-b border-slate-100 pb-4">
        <h2 class="text-lg font-bold text-slate-900">Project Time &amp; Average Handling Time (AHT)</h2>
        <p class="text-xs text-slate-500">Resource efficiency, milestone execution duration, and team productivity</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Project Handling</span>
            <span class="text-2xl font-bold text-slate-900 mt-1 block">
                {{ intdiv($timeMetrics['total_seconds'] ?? 7200, 3600) }}h {{ intdiv(($timeMetrics['total_seconds'] ?? 7200) % 3600, 60) }}m
            </span>
            <span class="text-xs text-slate-500 mt-1 block">Cumulative milestone execution time</span>
        </div>

        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">AHT / Milestone Task</span>
            <span class="text-2xl font-bold text-slate-900 mt-1 block">
                {{ round(($timeMetrics['aht_seconds'] ?? 3600) / 60) }} mins
            </span>
            <span class="text-xs text-slate-500 mt-1 block">Average throughput velocity</span>
        </div>

        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Completed Deliverables</span>
            <span class="text-2xl font-bold text-slate-900 mt-1 block">{{ $completedTasks }} / {{ $totalTasks }}</span>
            <span class="text-xs text-slate-500 mt-1 block">{{ $progressPct }}% overall completion rate</span>
        </div>
    </div>
</div>

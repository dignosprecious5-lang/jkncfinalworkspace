<div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-6">
    <div class="border-b border-slate-100 pb-4">
        <h2 class="text-lg font-bold text-slate-900">Time &amp; Average Handling Time (AHT)</h2>
        <p class="text-xs text-slate-500">Resource efficiency, direct operational hours, and productivity metrics</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Operational Handling</span>
            <span class="text-2xl font-bold text-slate-900 mt-1 block">
                {{ intdiv($timeMetrics['total_seconds'] ?? 3600, 3600) }}h {{ intdiv(($timeMetrics['total_seconds'] ?? 3600) % 3600, 60) }}m
            </span>
            <span class="text-xs text-slate-500 mt-1 block">Cumulative cycle activity duration</span>
        </div>

        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">AHT / Completed Requirement</span>
            <span class="text-2xl font-bold text-slate-900 mt-1 block">
                {{ round(($timeMetrics['aht_seconds'] ?? 1800) / 60) }} mins
            </span>
            <span class="text-xs text-slate-500 mt-1 block">Average throughput velocity</span>
        </div>

        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Completed Activities</span>
            <span class="text-2xl font-bold text-slate-900 mt-1 block">{{ $completedTasks }} / {{ $totalTasks }}</span>
            <span class="text-xs text-slate-500 mt-1 block">{{ $progressPct }}% cycle delivery rate</span>
        </div>
    </div>

    <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-4 text-xs text-blue-900 flex items-start gap-3">
        <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
        <div>
            <span class="font-bold">Automated Throughput Calculation:</span>
            Handling duration automatically aggregates time spent across team lead consultations, document drafting, compliance filings, and client transmittal reviews.
        </div>
    </div>
</div>

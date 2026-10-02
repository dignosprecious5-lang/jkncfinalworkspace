@php
    $totalSec = $timeMetrics['total_seconds'] ?? 7200;
@endphp

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
    <!-- MAIN DELIVERY PANEL (3 COLS) -->
    <div class="lg:col-span-3 space-y-5">
        <!-- TOP DELIVERY CARD -->
        <div class="card bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <strong class="text-sm font-bold text-slate-900 block">Delivery &amp; Cycle Completion</strong>
                    <div class="text-[11px] text-slate-500">Deliverable packages, transmittals, and period closing for Cycle {{ $cycleState['cycle_number'] }} ({{ $cycleState['current_period'] }})</div>
                </div>
                <div class="flex items-center gap-2">
                    @if (! $regularLocked)
                    <button type="button" onclick="document.getElementById('advance-cycle-modal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 rounded-xl bg-[#102d79] px-4 py-2 text-xs font-bold text-white hover:bg-[#0d255f] transition shadow-sm">
                        <i class="fas fa-redo-alt"></i> Advance to Next Cycle
                    </button>
                    @endif
                </div>
            </div>

            <div class="p-5 space-y-5">
                <!-- METRIC TILES -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Period Execution</span>
                        <span class="text-lg font-bold text-slate-900 mt-1 block">{{ $completedTasks }} / {{ $totalTasks }} Tasks</span>
                        <span class="text-xs text-slate-500">{{ $progressPct }}% overall completion</span>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">RSAT Report Status</span>
                        <span class="text-lg font-bold text-slate-900 mt-1 block">{{ $generatedReports->isNotEmpty() ? 'Generated & Filed' : 'Draft / Pending' }}</span>
                        <span class="text-xs text-slate-500">{{ $generatedReports->count() }} formal reports in archive</span>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Transmittal Package</span>
                        <span class="text-lg font-bold text-slate-900 mt-1 block">{{ $cycleState['current_period'] }}</span>
                        <span class="text-xs text-slate-500">Transmittal issuance ready</span>
                    </div>
                </div>

                <!-- RECORD DELIVERY REFERENCE -->
                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <strong class="text-xs font-bold text-slate-800">Transmittal / Delivery Evidence Reference</strong>
                        <span class="text-[11px] text-slate-400">Formal proof ID</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <input type="text" id="deliveryReference" value="{{ $rsatAttachments['service_memo_ref'] ?? ('TRANS-' . $regular->project_code . '-C' . $cycleState['cycle_number']) }}" class="flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-800" placeholder="e.g. TRANS-2026-001">
                        <button type="button" class="rounded-lg bg-[#102d79] px-4 py-2 text-xs font-bold text-white hover:bg-[#0d255f]">
                            Record Delivery Proof
                        </button>
                    </div>
                </div>

                <!-- DELIVERABLES LIST -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Deliverables &amp; Proof Checklist</h3>
                    @foreach($deliverables as $deliv)
                        <div class="flex items-center justify-between rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
                            <div class="flex items-center gap-3">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full {{ $deliv['ready'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400' }}">
                                    <i class="fas {{ $deliv['ready'] ? 'fa-check' : 'fa-clock' }} text-xs"></i>
                                </span>
                                <div>
                                    <span class="text-xs font-bold text-slate-800">{{ $deliv['name'] }}</span>
                                    <p class="text-[11px] text-slate-500">Required item for regular cycle completion and archival</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold {{ $deliv['ready'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                                {{ $deliv['ready'] ? 'Ready for Transmittal' : 'Pending Tasks' }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <!-- ACTION BUTTONS -->
                <div class="border-t border-slate-100 pt-4 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('transmittal.create.regular', $regular) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                            <i class="fas fa-paper-plane text-slate-400"></i> Generate Transmittal Letter
                        </a>
                    </div>
                    @if (! $regularLocked)
                    <button type="button" onclick="document.getElementById('advance-cycle-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2 text-xs font-bold uppercase tracking-wider text-white hover:bg-blue-800 shadow-md transition">
                        <i class="fas fa-check-double"></i> Complete &amp; Rollover Cycle
                    </button>
                    @endif
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
                    <div class="space-y-1.5">
                        <div class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded border border-emerald-200">✓ RSAT approved</div>
                        <div class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded border border-emerald-200">✓ NTP approved</div>
                        <div class="text-[11px] font-bold text-blue-700 bg-blue-50 px-2.5 py-1 rounded border border-blue-200">● Execution in progress</div>
                        <div class="text-[11px] font-bold text-slate-600 bg-slate-50 px-2.5 py-1 rounded border border-slate-200">○ Delivery &amp; Transmittal</div>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-3">
                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">STAGE HANDLING</h4>
                    <div class="rounded-lg bg-slate-50 p-3 mb-2 text-center">
                        <span class="text-[10px] text-slate-500 block">Total Stage Handling Time</span>
                        <strong class="text-base text-slate-900 font-bold">{{ intdiv($totalSec, 3600) }}h {{ intdiv($totalSec % 3600, 60) }}m</strong>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-3 space-y-2">
                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">STAGE CONTROLS</h4>
                    <a href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'dashboard']) }}" class="flex items-center justify-center gap-1.5 w-full rounded-xl bg-[#102d79] py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#0d255f] transition">
                        Dashboard
                    </a>
                    <a href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'report']) }}" class="flex items-center justify-center gap-1.5 w-full rounded-xl border border-slate-200 bg-white py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                        RSAT Report
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

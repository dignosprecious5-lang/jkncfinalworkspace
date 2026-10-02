<div class="space-y-6">
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Certificate of Completion (COC) &amp; Delivery</h2>
                <p class="text-xs text-slate-500">Formal handover package and project signoff for {{ $project->project_code }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if (! $projectLocked && ! $cocGenerated)
                    <form method="POST" action="{{ route('project.coc.generate', $project) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-700 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white hover:bg-blue-800 shadow-md transition">
                            <i class="fas fa-award"></i> Generate Official COC
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Scope Completion</span>
                <span class="text-lg font-bold text-slate-900 mt-1 block">{{ $completedTasks }} / {{ $totalTasks }} Tasks</span>
                <span class="text-xs text-slate-500">{{ $progressPct }}% completed milestone scope</span>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">COC Document Status</span>
                <span class="text-lg font-bold text-slate-900 mt-1 block">{{ $cocGenerated ? 'Generated' : 'Pending Generation' }}</span>
                <span class="text-xs text-slate-500">{{ $cocApproved ? 'Fully signed & approved' : 'Awaiting completion approval' }}</span>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Transmittal Linkage</span>
                <span class="text-lg font-bold text-slate-900 mt-1 block">TRN-{{ $project->project_code }}</span>
                <span class="text-xs text-slate-500">Delivery transmittal package</span>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-5">
            <h3 class="text-sm font-bold text-slate-900 mb-3">Project Deliverables Checklist</h3>
            <div class="space-y-3">
                @foreach($deliverables as $deliv)
                    <div class="flex items-center justify-between rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
                        <div class="flex items-center gap-3">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full {{ $deliv['ready'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400' }}">
                                <i class="fas {{ $deliv['ready'] ? 'fa-check' : 'fa-clock' }} text-xs"></i>
                            </span>
                            <div>
                                <span class="text-sm font-bold text-slate-800">{{ $deliv['name'] }}</span>
                                <p class="text-xs text-slate-500">Milestone deliverable item</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold {{ $deliv['ready'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                            {{ $deliv['ready'] ? 'Ready' : 'Pending' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="border-t border-slate-100 pt-5 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <a href="{{ route('transmittal.create.project', $project) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                    <i class="fas fa-paper-plane text-slate-400"></i> Generate Delivery Transmittal
                </a>
                @if ($cocGenerated)
                    <a href="{{ route('project.coc.preview', $project) }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white hover:bg-slate-800 transition">
                        <i class="fas fa-eye"></i> Preview COC Document
                    </a>
                    <a href="{{ route('project.coc.download', $project) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                        <i class="fas fa-file-pdf text-rose-500"></i> Download COC PDF
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-6">
    <div class="border-b border-slate-100 pb-4">
        <h2 class="text-lg font-bold text-slate-900">Client Checkpoints &amp; Action Items</h2>
        <p class="text-xs text-slate-500">Prerequisites and required authorizations from {{ $contactName }}</p>
    </div>

    <div class="space-y-3">
        @foreach($clientActions as $ca)
            <div class="flex items-center justify-between rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 transition hover:bg-slate-50">
                <div class="flex items-center gap-3">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full {{ $ca['done'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        <i class="fas {{ $ca['done'] ? 'fa-check' : 'fa-hourglass-half' }} text-xs"></i>
                    </span>
                    <div>
                        <span class="text-sm font-bold text-slate-800 {{ $ca['done'] ? 'line-through text-slate-400' : '' }}">{{ $ca['subject'] }}</span>
                        <p class="text-xs text-slate-400">Client Milestone Checkpoint</p>
                    </div>
                </div>
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold {{ $ca['done'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                    {{ $ca['done'] ? 'Completed' : 'Pending Action' }}
                </span>
            </div>
        @endforeach
    </div>
</div>

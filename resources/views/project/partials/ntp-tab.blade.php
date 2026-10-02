<div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-4">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Project Notice to Proceed (NTP)</h2>
            <p class="text-xs text-slate-500">Execution authorization and client confirmation status</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-2 rounded-full border {{ $ntpApproved ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-600' }} px-3 py-1.5 text-xs font-bold">
                <i class="{{ $ntpApproved ? 'fas fa-check-circle text-emerald-600' : 'fas fa-hourglass-half text-amber-500' }}"></i>
                {{ $ntpStatusLabel }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">NTP Reference</span>
            <span class="font-bold text-slate-900 text-sm mt-1 block">{{ $ntpRecord?->reference_no ?: 'NTP-'.$project->project_code }}</span>
        </div>
        <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Client Response</span>
            <span class="font-bold text-slate-900 text-sm mt-1 block capitalize">{{ str_replace('_', ' ', $ntpRecord?->client_response_status ?: 'Pending signed upload') }}</span>
        </div>
        <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Approved Date</span>
            <span class="font-bold text-slate-900 text-sm mt-1 block">{{ $ntpRecord?->client_approved_at ? \Carbon\Carbon::parse($ntpRecord->client_approved_at)->format('M d, Y h:i A') : 'Awaiting client' }}</span>
        </div>
    </div>

    <div class="border-t border-slate-100 pt-5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <a href="{{ route('project.ntp.download', $project) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                <i class="fas fa-file-pdf text-rose-500"></i> Download Official NTP Form
            </a>
            <a href="{{ route('project.ntp.submission', $project) }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-xs font-bold text-white hover:bg-blue-800 transition">
                <i class="fas fa-eye"></i> View NTP Viewer
            </a>
        </div>
    </div>
</div>

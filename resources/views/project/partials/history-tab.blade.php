@php
    $deal = $project->deal;
    $proposal = $deal?->proposals()?->latest()->first();
    $startRecord = $project->starts()->latest()->first();
    $startAttachments = (array) ($startRecord?->attachments ?? []);
    $dealHistories = $deal ? $deal->histories()->latest()->take(10)->get() : collect();
@endphp

<div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-4">
        <div>
            <h2 class="text-lg font-bold text-slate-900">End-to-End Transaction Traceability &amp; Audit Trail</h2>
            <p class="text-xs text-slate-500">Connected origin records and milestone history for {{ $project->project_code }}</p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 border border-emerald-200">
            <i class="fas fa-link text-[10px]"></i> Fully Connected Transaction
        </span>
    </div>

    {{-- ORIGIN TRACEABILITY CHAIN --}}
    <div class="rounded-xl border border-blue-100 bg-blue-50/40 p-4">
        <h3 class="text-xs font-bold uppercase tracking-wider text-blue-900 mb-3 flex items-center gap-2">
            <i class="fas fa-sitemap"></i> Transaction Relationship Map
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            <div class="rounded-lg bg-white p-3 border border-blue-100 shadow-2xs">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">1. Originating Deal</span>
                @if($deal)
                    <a href="{{ route('deals.show', $deal->id) }}" class="font-bold text-blue-700 hover:underline block mt-0.5">{{ $deal->deal_code }}</a>
                    <span class="text-[11px] text-slate-500 block">{{ $deal->company_name ?: ($deal->primary_contact_name ?: 'Deal Record') }}</span>
                @else
                    <span class="font-semibold text-slate-600 block mt-0.5">Manual Direct Entry</span>
                @endif
            </div>

            <div class="rounded-lg bg-white p-3 border border-blue-100 shadow-2xs">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">2. CASA / Proposal Agreement</span>
                @if($proposal)
                    <span class="font-bold text-slate-900 block mt-0.5">{{ $proposal->proposal_code ?: 'CASA-'.date('Y').'-'.$proposal->id }}</span>
                    <span class="text-[11px] text-emerald-600 font-semibold block">
                        <i class="fas fa-check-circle text-[10px]"></i> {{ $proposal->client_approved_at ? 'Client Approved ' . \Carbon\Carbon::parse($proposal->client_approved_at)->format('M d, Y') : 'Proposal Active' }}
                    </span>
                @else
                    <span class="font-semibold text-slate-400 block mt-0.5">Direct Onboarding</span>
                @endif
            </div>

            <div class="rounded-lg bg-white p-3 border border-blue-100 shadow-2xs">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">3. START &amp; Service Memo</span>
                <span class="font-bold text-slate-900 block mt-0.5">{{ $startAttachments['service_memo_ref'] ?? ('SM-' . $project->project_code) }}</span>
                <span class="text-[11px] text-blue-600 font-semibold block">
                    <i class="fas fa-file-invoice text-[10px]"></i> {{ $startRecord?->status === 'approved' ? 'Service Memo Authorized' : 'START Record Form' }}
                </span>
            </div>

            <div class="rounded-lg bg-white p-3 border border-blue-100 shadow-2xs">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">4. Operations Workspace</span>
                <span class="font-bold text-slate-900 block mt-0.5">{{ $project->project_code }}</span>
                <span class="text-[11px] font-bold text-indigo-700 block">Phase: {{ $project->status }}</span>
            </div>
        </div>
    </div>

    {{-- MILESTONE TIMELINE --}}
    <div class="space-y-3">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Milestone &amp; Governance Timeline</h3>

        @if($deal)
            <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-sm text-slate-900 flex items-center gap-2">
                        <i class="fas fa-handshake text-blue-600"></i> Originating Deal Initialized
                    </span>
                    <span class="text-xs text-slate-500">{{ optional($deal->created_at)->format('M d, Y h:i A') }}</span>
                </div>
                <p class="mt-1 text-xs text-slate-600">Deal {{ $deal->deal_code }} qualified for {{ $deal->company_name ?: $contactName }} under {{ $project->engagement_type }}.</p>
            </div>
        @endif

        @if($proposal?->client_approved_at)
            <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-sm text-slate-900 flex items-center gap-2">
                        <i class="fas fa-file-signature text-emerald-600"></i> Client Advisory &amp; Service Agreement (CASA) Approved
                    </span>
                    <span class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($proposal->client_approved_at)->format('M d, Y h:i A') }}</span>
                </div>
                <p class="mt-1 text-xs text-slate-600">Proposal {{ $proposal->proposal_code }} signed and accepted by {{ $proposal->client_approved_by_name ?: $contactName }}.</p>
            </div>
        @endif

        <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4">
            <div class="flex items-center justify-between">
                <span class="font-bold text-sm text-slate-900 flex items-center gap-2">
                    <i class="fas fa-layer-group text-indigo-600"></i> Project Operations Workspace Activated
                </span>
                <span class="text-xs text-slate-500">{{ optional($project->created_at)->format('M d, Y h:i A') }}</span>
            </div>
            <p class="mt-1 text-xs text-slate-600">Workspace {{ $project->project_code }} initiated with Service Memo and START governance structure.</p>
        </div>

        @if($sow?->approved_at)
            <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-sm text-slate-900 flex items-center gap-2">
                        <i class="fas fa-tasks text-blue-600"></i> Scope of Work (SOW) Approved
                    </span>
                    <span class="text-xs text-slate-500">{{ optional($sow->approved_at)->format('M d, Y h:i A') }}</span>
                </div>
                <p class="mt-1 text-xs text-slate-600">SOW v{{ $sow->version_number ?: '1.0' }} approved by internal review board.</p>
            </div>
        @endif

        @if($ntpRecord?->client_approved_at)
            <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-sm text-slate-900 flex items-center gap-2">
                        <i class="fas fa-check-double text-emerald-600"></i> Notice to Proceed (NTP) Client Authorization
                    </span>
                    <span class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($ntpRecord->client_approved_at)->format('M d, Y h:i A') }}</span>
                </div>
                <p class="mt-1 text-xs text-slate-600">Client signed authorization recorded for {{ $ntpRecord->reference_no ?: $project->project_code }}.</p>
            </div>
        @endif

        @if($cocApproved)
            <div class="rounded-xl border border-slate-200/80 bg-emerald-50/50 p-4 border-emerald-200">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-sm text-emerald-900 flex items-center gap-2">
                        <i class="fas fa-award text-emerald-600"></i> Certificate of Completion (COC) Formalized
                    </span>
                    <span class="text-xs text-slate-500">{{ optional($project->updated_at)->format('M d, Y h:i A') }}</span>
                </div>
                <p class="mt-1 text-xs text-emerald-700">Official COC verification document compiled and project archived as completed.</p>
            </div>
        @endif
    </div>
</div>

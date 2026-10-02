@php
    $withinCount = count($rsatRequirements ?? []);
    $leadConsultant = $approvalLeadConsultant ?: ($regular->assigned_consultant ?: 'John Kelly Abalde');
    $leadAssociate = $approvalLeadAssociate ?: ($regular->lead_associate ?: 'Rubeca Potayre');
@endphp

<style>
.report-summary { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; margin-bottom: 16px; }
.report-summary-card { padding: 12px 14px; border: 1px solid #dce4f2; border-radius: 10px; background: linear-gradient(145deg, #fff, #f6f8fc); }
.report-summary-card span { display: block; color: #8791a4; font-size: 8px; font-weight: 800; letter-spacing: .45px; text-transform: uppercase; }
.report-summary-card strong { display: block; margin-top: 4px; color: #102d79; font-size: 18px; font-weight: 800; }
.report-document { border: 1px solid #d8dfeb; background: #fff; box-shadow: 0 4px 14px rgba(33,51,91,.06); border-radius: 12px; overflow: hidden; }
.report-dochead { display: flex; justify-content: space-between; align-items: flex-start; padding: 20px 24px; border-bottom: 3px solid #102d79; background: #fafbfc; }
.report-docbrand { color: #102d79; font-size: 18px; font-weight: 800; }
.report-doctitle { text-align: right; color: #102d79; }
.report-doctitle h1 { margin: 0; font-size: 22px; font-weight: 800; }
.report-doctitle small { color: #64748b; font-size: 10px; font-weight: 600; }
.report-docmeta { display: grid; grid-template-columns: 140px 1fr 140px 1fr; border-bottom: 1px solid #d8dfeb; font-size: 11px; }
.report-docmeta > div { padding: 9px 12px; border-right: 1px solid #e2e7ef; border-bottom: 1px solid #e2e7ef; }
.report-docmeta .lbl { background: #f8fafc; color: #64748b; font-weight: 600; }
.report-docmeta .val { color: #0f172a; font-weight: 700; }
.report-body { padding: 20px 24px; }
.report-scope-title { margin: 0; padding: 9px 14px; background: #102d79; color: #fff; font-size: 12px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; border-radius: 8px 8px 0 0; }
.report-table-wrap { overflow-x: auto; border: 1px solid #cfd7e5; border-radius: 0 0 8px 8px; }
.report-table { width: 100%; min-width: 900px; border-collapse: collapse; }
.report-table th { padding: 8px 10px; background: #f0f3f9; color: #46546d; border: 1px solid #d8dfeb; font-size: 10px; font-weight: 800; text-transform: uppercase; text-align: left; }
.report-table td { padding: 8px 10px; border: 1px solid #e0e5ed; color: #334155; font-size: 11px; }
@media(max-width: 850px) {
    .report-summary { grid-template-columns: 1fr 1fr; }
    .report-docmeta { grid-template-columns: 1fr 1fr; }
}
</style>

<div class="space-y-6">
    <!-- TOP REPORT SUMMARY CARDS -->
    <div class="report-summary">
        <div class="report-summary-card">
            <span>Cycle Number</span>
            <strong>Cycle {{ $cycleState['cycle_number'] ?? 1 }}</strong>
        </div>
        <div class="report-summary-card">
            <span>Deliverables</span>
            <strong>{{ $withinCount }} Items</strong>
        </div>
        <div class="report-summary-card">
            <span>Overall Progress</span>
            <strong>{{ $progressPct }}%</strong>
        </div>
        <div class="report-summary-card">
            <span>Reports Generated</span>
            <strong>{{ $generatedReports->count() }}</strong>
        </div>
        <div class="report-summary-card">
            <span>Health Status</span>
            <strong class="text-emerald-700">On Track</strong>
        </div>
    </div>

    <!-- GENERATED RSAT REPORT REGISTRY -->
    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-xs">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-6 py-4 bg-slate-50/50">
            <div>
                <h3 class="text-base font-bold text-slate-900">Generated RSAT Reports</h3>
                <p class="text-xs text-slate-500">Official execution progress reports prepared for client submission</p>
            </div>
            @if (!$regularLocked)
                <form method="POST" action="{{ route('regular.report.generate', $regular) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-blue-700 px-4 py-2 text-xs font-bold text-white hover:bg-blue-800 transition">
                        <i class="fas fa-file-invoice"></i> Generate New RSAT Report
                    </button>
                </form>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-[0.1em] text-slate-500">
                    <tr>
                        <th class="px-6 py-3.5 text-left">Report No.</th>
                        <th class="px-6 py-3.5 text-left">Date Prepared</th>
                        <th class="px-6 py-3.5 text-left">Generated On</th>
                        <th class="px-6 py-3.5 text-left">Client Approval</th>
                        <th class="px-6 py-3.5 text-left">Status</th>
                        <th class="px-6 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($generatedReports as $item)
                        @php
                            $isApproved = $item->client_response_status === 'approved' && $item->client_approved_at;
                            $statusLabel = $isApproved ? 'Approved' : 'Pending Client Review';
                            $statusClass = $isApproved ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200';
                            $previewUrl = route('regular.report.preview', ['regular' => $regular->id, 'report' => $item->id]);
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-3.5 font-bold text-blue-800">{{ $item->report_number ?: ('Report-' . $item->id) }}</td>
                            <td class="px-6 py-3.5 text-slate-600">{{ optional($item->date_prepared)->format('M d, Y') ?: '-' }}</td>
                            <td class="px-6 py-3.5 text-slate-600">{{ optional($item->created_at)->format('M d, Y') ?: '-' }}</td>
                            <td class="px-6 py-3.5 text-slate-600">{{ optional($item->client_approved_at)->format('M d, Y') ?: 'Pending' }}</td>
                            <td class="px-6 py-3.5">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold border {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <a href="{{ $previewUrl }}" class="inline-flex items-center gap-1 font-bold text-xs text-blue-700 hover:text-blue-900">
                                    <i class="fas fa-eye"></i> View Report
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-xs text-slate-400">
                                No generated reports yet. Click "Generate New RSAT Report" to record a report.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- LIVE REPORT DOCUMENT PREVIEW -->
    <article class="report-document">
        <header class="report-dochead">
            <div class="report-docbrand">
                John Kelly &amp; Company
                <div class="text-[11px] text-slate-500 font-normal">Operational Consulting &amp; Advisory</div>
            </div>
            <div class="report-doctitle">
                <h1>RSAT REPORT</h1>
                <small>Specification {{ $regular->project_code }} &middot; Cycle {{ $cycleState['cycle_number'] ?? 1 }}</small>
            </div>
        </header>

        <div class="report-docmeta">
            <div class="lbl">Notice to Proceed (NTP):</div>
            <div class="val">{{ $ntpRecord?->reference_no ?? ('NTP-' . $regular->project_code) }}</div>
            <div class="lbl">Regular Ref No.:</div>
            <div class="val">{{ $regular->project_code }}</div>

            <div class="lbl">Source Deal:</div>
            <div class="val">{{ $regular->deal?->deal_code ?: 'Direct Regular' }}</div>
            <div class="lbl">Business Name:</div>
            <div class="val">{{ $regular->business_name ?: ($regular->company?->company_name ?: '-') }}</div>

            <div class="lbl">Client Representative:</div>
            <div class="val">{{ $contactName }}</div>
            <div class="lbl">Reporting Period:</div>
            <div class="val">{{ $cycleState['current_period'] ?? date('F Y') }}</div>

            <div class="lbl">Lead Consultant:</div>
            <div class="val">{{ $leadConsultant }}</div>
            <div class="lbl">Lead Associate:</div>
            <div class="val">{{ $leadAssociate }}</div>
        </div>

        <div class="report-body">
            <div class="report-scope-title">Execution Deliverables &amp; Progress Matrix</div>
            <div class="report-table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 5%">#</th>
                            <th style="width: 22%">Service Item</th>
                            <th style="width: 33%">Activity / Deliverable</th>
                            <th style="width: 14%">Frequency</th>
                            <th style="width: 13%">Target Date</th>
                            <th style="width: 13%">Delivery Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rsatRequirements as $idx => $req)
                            <tr>
                                <td class="font-bold">{{ $idx + 1 }}</td>
                                <td class="font-bold text-blue-950">{{ $req['purpose'] ?? 'Recurring Scope' }}</td>
                                <td>{{ $req['requirement'] ?? $req['notes'] ?? '-' }}</td>
                                <td><span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700">{{ $req['timeline'] ?? 'Monthly' }}</span></td>
                                <td>{{ $req['submitted_to'] ? \Carbon\Carbon::parse($req['submitted_to'])->format('M d, Y') : 'Scheduled' }}</td>
                                <td>
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold {{ ($req['status'] ?? 'open') === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ ucfirst(str_replace('_', ' ', $req['status'] ?? 'open')) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-6 text-slate-400">No deliverables recorded for this cycle.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </article>
</div>

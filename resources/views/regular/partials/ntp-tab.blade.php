@php
    $ntpNo = $ntpRecord?->payload['ntp_number'] ?? ('NTP-' . $regular->project_code);
    $condealRef = $regular->deal?->deal_code ?? 'CONDEAL-2026';
    $leadConsultant = $approvalLeadConsultant ?: ($regular->assigned_consultant ?: 'John Kelly Abalde');
    $leadAssociate = $approvalLeadAssociate ?: ($regular->lead_associate ?: 'Rubeca Potayre');
    $president = $approvalPresident ?? 'John Kelly Abalde';
    $isNtpApproved = !empty($ntpApproved);
@endphp

<style>
.ntp-layout {
    display: grid;
    grid-template-columns: 290px minmax(0, 1fr);
    gap: 20px;
    align-items: start;
}
@media (max-width: 1024px) {
    .ntp-layout {
        grid-template-columns: 1fr;
    }
}
.ntp-sidecard {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
}
.ntp-side-title {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.1em;
    color: #64748b;
    text-transform: uppercase;
    margin-bottom: 14px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f1f5f9;
}
.ntp-side-section {
    margin-bottom: 16px;
    padding-bottom: 14px;
    border-bottom: 1px solid #f1f5f9;
}
.ntp-side-section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}
.ntp-side-section h4 {
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 0.08em;
    color: #475569;
    text-transform: uppercase;
    margin: 0 0 10px;
}
.ntp-timer-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px;
}
.ntp-timer-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}
.ntp-timer-row span {
    font-size: 11px;
    font-weight: 700;
    color: #1e293b;
}
.ntp-timer-row .clock {
    font-family: monospace;
    font-size: 14px;
    font-weight: 800;
    color: #102d79;
}
.ntp-status-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px dashed #e2e8f0;
    font-size: 11px;
}
.ntp-status-row:last-child {
    border-bottom: none;
}
.ntp-status-pill {
    font-size: 9px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 999px;
    background: #eff6ff;
    color: #1e40af;
}
.ntp-status-pill.approved {
    background: #dcfce7;
    color: #166534;
}
.ntp-doc-container {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 30px;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
}
.ntp-paper {
    border: 2px solid #102d79;
    padding: 30px;
    border-radius: 8px;
    background: #fff;
}
.ntp-doc-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 2px solid #102d79;
    padding-bottom: 16px;
    margin-bottom: 20px;
}
.ntp-doc-header h1 {
    font-size: 20px;
    font-weight: 800;
    color: #102d79;
    margin: 0;
}
.ntp-doc-header small {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
}
.ntp-doc-meta-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    font-size: 11px;
}
.ntp-doc-meta-table td {
    border: 1px solid #cbd5e1;
    padding: 8px 10px;
}
.ntp-doc-meta-table .lbl {
    background: #f8fafc;
    font-weight: 700;
    color: #475569;
    width: 25%;
}
.ntp-doc-meta-table .val {
    color: #0f172a;
    font-weight: 600;
}
.ntp-body-text {
    font-size: 12px;
    line-height: 1.6;
    color: #334155;
    margin-bottom: 24px;
}
.ntp-body-text p {
    margin-bottom: 12px;
}
.ntp-sig-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-top: 30px;
    border-top: 1px solid #e2e8f0;
    padding-top: 20px;
}
.ntp-sig-box {
    border: 1px dashed #cbd5e1;
    border-radius: 8px;
    padding: 14px;
    background: #fafbfc;
}
.ntp-sig-box strong {
    display: block;
    font-size: 11px;
    color: #1e293b;
    margin-bottom: 40px;
}
.ntp-sig-box .line {
    border-top: 1px solid #475569;
    padding-top: 4px;
    font-size: 11px;
    font-weight: 700;
    color: #0f172a;
}
.ntp-appendix {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px dashed #cbd5e1;
}
.ntp-appendix-title {
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.08em;
    color: #102d79;
    text-transform: uppercase;
    margin-bottom: 12px;
}
</style>

<div class="ntp-layout">
    <!-- STAGE MANAGEMENT SIDEBAR -->
    <aside class="ntp-sidecard">
        <div class="ntp-side-title">STAGE MANAGEMENT</div>

        <div class="ntp-side-section">
            <h4>STAGE HANDLING</h4>
            <div class="ntp-timer-card">
                <div class="ntp-timer-row">
                    <span>NTP Issuance Gate</span>
                    <span class="clock">{{ $isNtpApproved ? 'APPROVED' : 'ACTIVE' }}</span>
                </div>
                <div class="text-[10px] text-slate-500 mt-1">
                    Cycle No.: <strong>{{ $cycleState['cycle_number'] ?? 1 }}</strong><br>
                    Generated: <strong>{{ optional($ntpRecord?->created_at ?? $regular->created_at)->format('M d, Y') }}</strong>
                </div>
            </div>
        </div>

        <div class="ntp-side-section">
            <h4>STAGE STATUS</h4>
            <div class="ntp-status-row">
                <span>Review Gate</span>
                <span class="ntp-status-pill approved">Complete</span>
            </div>
            <div class="ntp-status-row">
                <span>NTP Status</span>
                <span class="ntp-status-pill {{ $isNtpApproved ? 'approved' : '' }}">{{ $isNtpApproved ? 'Issued' : 'Ready' }}</span>
            </div>
            <div class="ntp-status-row">
                <span>Client Signature</span>
                <span class="ntp-status-pill {{ $isNtpApproved ? 'approved' : '' }}">{{ $isNtpApproved ? 'Confirmed' : 'Pending Upload' }}</span>
            </div>
        </div>

        <div class="ntp-side-section">
            <h4>SCOPE CHANGE PACKAGE</h4>
            <div class="text-xs text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-200 space-y-1">
                <div class="flex justify-between font-semibold">
                    <span>Target Period</span>
                    <span class="text-blue-900">{{ $fmt($regular->target_completion_date) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Total Deliverables</span>
                    <span class="font-bold">{{ count($rsatRequirements) }} Line Items</span>
                </div>
            </div>
        </div>

        @if (!$regularLocked)
        <div class="ntp-side-section">
            <h4>NTP VERIFICATION &amp; UPLOAD</h4>
            <form method="POST" action="{{ route('regular.ntp.manual-approve', $regular) }}" enctype="multipart/form-data" class="space-y-2">
                @csrf
                <label class="block text-[10px] font-bold text-slate-600">Upload Signed NTP Document</label>
                <input type="file" name="signed_document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required class="block w-full text-xs text-slate-600 border border-slate-300 rounded p-1">
                <input type="text" name="approval_note" placeholder="Approval reference note..." class="w-full text-xs border border-slate-300 rounded p-1.5">
                <button type="submit" class="w-full bg-blue-900 hover:bg-blue-800 text-white font-bold text-xs py-2 px-3 rounded shadow transition">
                    <i class="fas fa-upload mr-1"></i> Confirm &amp; Authorize NTP
                </button>
            </form>
        </div>
        @endif

        <div class="ntp-side-section">
            <a href="{{ route('regular.ntp.download', $regular) }}" class="block w-full text-center bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs py-2 px-3 rounded border border-slate-300 transition">
                <i class="fas fa-download mr-1"></i> Download NTP Document
            </a>
        </div>
    </aside>

    <!-- MAIN NTP LETTERHEAD -->
    <section class="content">
        <div class="ntp-doc-container">
            <div class="ntp-paper">
                <div class="ntp-doc-header">
                    <div>
                        <div class="text-xl font-bold text-blue-900">John Kelly &amp; Company</div>
                        <div class="text-xs text-slate-500 font-semibold">Operational Consulting &amp; Management Advisory</div>
                    </div>
                    <div class="text-right">
                        <h1>NOTICE TO PROCEED</h1>
                        <small>Ref: {{ $ntpNo }}</small>
                    </div>
                </div>

                <table class="ntp-doc-meta-table">
                    <tr>
                        <td class="lbl">To (Client):</td>
                        <td class="val">{{ $contactName }}</td>
                        <td class="lbl">Notice Date:</td>
                        <td class="val">{{ $fmt($formDate ?: now()) }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Business / Company:</td>
                        <td class="val">{{ $regular->business_name ?: ($regular->company?->company_name ?: '-') }}</td>
                        <td class="lbl">Condeal Ref No.:</td>
                        <td class="val">{{ $condealRef }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Service Engagement:</td>
                        <td class="val">{{ $regular->name ?: 'Regular Retainer Services' }}</td>
                        <td class="lbl">Effective Start Date:</td>
                        <td class="val">{{ $fmt($regular->planned_start_date) }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Lead Consultant:</td>
                        <td class="val">{{ $leadConsultant }}</td>
                        <td class="lbl">Target Completion:</td>
                        <td class="val">{{ $fmt($regular->target_completion_date) }}</td>
                    </tr>
                </table>

                <div class="ntp-body-text">
                    <p>Dear <strong>{{ $contactName }}</strong>,</p>
                    <p>
                        This official <strong>Notice to Proceed (NTP)</strong> confirms that all preliminary administrative, legal, and operational clearances have been duly verified for your ongoing regular retainer with <strong>John Kelly &amp; Company</strong>.
                    </p>
                    <p>
                        Pursuant to the approved Regular Service Activity Tracker (RSAT Specification <strong>{{ $regular->project_code }}</strong>), our designated operational advisory team is hereby authorized to commence the execution of the deliverables listed in the attached Appendix.
                    </p>
                    <p>
                        All work performed under this authorization is governed by the Engagement Proposal Agreement (EPA) and established service-level frameworks.
                    </p>
                </div>

                <div class="ntp-sig-grid">
                    <div class="ntp-sig-box">
                        <strong>Issued &amp; Authorized By:</strong>
                        <div class="line">
                            {{ $president }}<br>
                            <span class="text-[10px] text-slate-500 font-normal">President / Managing Partner &middot; John Kelly &amp; Company</span>
                        </div>
                    </div>
                    <div class="ntp-sig-box">
                        <strong>Acknowledged &amp; Confirmed By:</strong>
                        <div class="line">
                            {{ $contactName }}<br>
                            <span class="text-[10px] text-slate-500 font-normal">Authorized Client Representative &middot; {{ $regular->business_name ?: 'Client' }}</span>
                        </div>
                    </div>
                </div>

                <!-- APPENDIX: WITHIN SCOPE -->
                <div class="ntp-appendix">
                    <div class="ntp-appendix-title">Appendix A: Authorized Scope of Work</div>
                    <table class="scope-table w-full">
                        <thead>
                            <tr>
                                <th style="width: 5%">#</th>
                                <th style="width: 25%">Service Module</th>
                                <th style="width: 45%">Deliverable Requirement</th>
                                <th style="width: 25%">Frequency</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rsatRequirements as $idx => $req)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td class="font-bold">{{ $req['purpose'] ?? 'Recurring Retainer' }}</td>
                                    <td>{{ $req['requirement'] ?? $req['notes'] ?? '-' }}</td>
                                    <td><span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700">{{ $req['timeline'] ?? 'Monthly' }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-slate-400">Regular scope deliverables as defined in the master RSAT framework.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

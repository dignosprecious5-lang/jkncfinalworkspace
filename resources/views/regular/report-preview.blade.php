@extends('layouts.app')

@section('content')
@php
    $fmt = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('M d, Y') : '-';
    $contactName = trim(collect([$regular->contact?->first_name, $regular->contact?->last_name])->filter()->implode(' ')) ?: '-';
    $reportRows = collect($report?->within_scope_items ?? []);
    $reportApproval = (array) ($report?->internal_approval ?? []);
    $filledRows = $reportRows->filter(fn ($item) => filled($item['service'] ?? null) || filled($item['activity_output'] ?? null))->values();
    $clientApprovalStatus = $report->client_response_status ?: 'pending';
    $regularLocked = strcasecmp((string) $regular->status, 'Completed') === 0;
@endphp

<style>
    .rsat-workspace {
        background:
            radial-gradient(circle at top left, rgba(13, 70, 140, 0.08), transparent 28%),
            linear-gradient(180deg, #f2f6fc 0%, #fbfcfe 26%, #fbfcfe 100%);
    }
    .rsat-top-card, .rsat-sheet {
        border: 1px solid #d8e1ee;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.05);
    }
    .rsat-form {
        border: 2px solid #1c4587;
        padding: 28px 30px 34px;
    }
    .rsat-title {
        font-family: Georgia, "Times New Roman", serif;
        font-weight: 700;
        font-size: 2rem;
        line-height: 1.05;
        letter-spacing: 0.02em;
        color: #111827;
        text-align: right;
    }
    .rsat-form-code, .rsat-meta-label, .rsat-approval-label { color: #64748b; }
    .rsat-intro {
        margin-top: 16px;
        font-size: 0.95rem;
        line-height: 1.7;
        color: #334155;
    }
    .rsat-notice {
        margin-top: 14px;
        border: 1px solid #dbe7f6;
        background: #f8fbff;
        padding: 14px 16px;
        font-size: 0.88rem;
        color: #334155;
    }
    .rsat-meta-grid, .rsat-approval-grid, .rsat-footer-grid { display: grid; gap: 12px 26px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .rsat-meta-item, .rsat-approval-pair, .rsat-footer-pair {
        display: grid;
        align-items: end;
        gap: 10px;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 0.88rem;
    }
    .rsat-meta-item { grid-template-columns: 160px minmax(0, 1fr); }
    .rsat-approval-pair { grid-template-columns: 145px minmax(0, 1fr); }
    .rsat-footer-pair { grid-template-columns: 180px minmax(0, 1fr); }
    .rsat-line-value {
        min-height: 34px;
        border-bottom: 1px solid #111827;
        padding: 6px 0 5px;
        color: #111827;
    }
    .rsat-client-row {
        display: grid;
        grid-template-columns: 220px repeat(3, minmax(0, 1fr));
        gap: 18px;
        align-items: end;
    }
    .rsat-check-group { display: flex; align-items: center; gap: 8px; font-family: Georgia, "Times New Roman", serif; font-size: 0.88rem; color: #111827; white-space: nowrap; }
    .rsat-check { display: inline-flex; height: 16px; width: 16px; align-items: center; justify-content: center; border: 1px solid #111827; font-size: 11px; line-height: 1; }
    .rsat-report-info-grid {
        margin-top: 18px;
        display: grid;
        gap: 10px 18px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .rsat-report-table-wrap {
        margin-top: 18px;
        overflow-x: auto;
    }
    .rsat-report-table {
        width: 100%;
        min-width: 980px;
        border-collapse: collapse;
        table-layout: fixed;
        font-family: Georgia, "Times New Roman", serif;
    }
    .rsat-report-table th,
    .rsat-report-table td {
        border: 1px solid #111827;
        padding: 0;
        vertical-align: middle;
    }
    .rsat-report-table th {
        background: #1c4587;
        color: #fff;
        padding: 6px 4px;
        text-align: center;
        font-size: 0.76rem;
        font-weight: 700;
    }
    .rsat-report-table td {
        min-height: 28px;
        padding: 6px 8px;
        font-size: 0.82rem;
        color: #111827;
    }
    .rsat-report-table td.center {
        text-align: center;
    }
    .rsat-signature { margin-top: 28px; display: grid; gap: 8px; justify-items: center; }
    .rsat-signature-line { width: min(100%, 420px); border-bottom: 1px solid #111827; min-height: 36px; text-align: center; }
    .rsat-signature-label { font-family: Georgia, "Times New Roman", serif; font-style: italic; font-size: 0.92rem; color: #111827; }
    .rsat-section-title { margin-top: 30px; background: #1c4587; padding: 10px 16px; font-family: Georgia, "Times New Roman", serif; font-size: 1.15rem; font-weight: 700; letter-spacing: 0.05em; color: #fff; text-align: center; }
    @media (max-width: 900px) {
        .rsat-meta-grid, .rsat-approval-grid, .rsat-footer-grid, .rsat-client-row { grid-template-columns: 1fr; }
        .rsat-title { text-align: left; }
    }
</style>

<div class="rsat-workspace p-6">
    <div class="mx-auto max-w-[1320px] space-y-4">
        <div class="rsat-top-card rounded-2xl px-5 py-4 text-sm text-gray-600">
            <a href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'report']) }}" class="hover:text-blue-700"><i class="fas fa-arrow-left mr-1"></i>RSAT Reports</a>
            <span class="mx-1">/</span><span class="font-medium text-gray-900">{{ $report->report_number ?: 'Preview' }}</span>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
            @if ($regularLocked)
                <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    This completed regular engagement report is view-only.
                </div>
            @endif
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Client Approval Delivery</h2>
                    <p class="mt-1 text-sm text-slate-500">Send or resend the secure client approval link for this generated RSAT report.</p>
                </div>
                @if (! $regularLocked)
                <form method="POST" action="{{ route('regular.report.send', ['regular' => $regular->id, 'report' => $report->id]) }}" class="flex w-full max-w-xl flex-col gap-3 sm:flex-row sm:items-end">
                    @csrf
                    <div class="flex-1">
                        <label class="mb-2 block text-sm font-medium text-slate-700">Recipient Email</label>
                        <input type="email" name="recipient_email" value="{{ old('recipient_email', $clientEmail) }}" class="h-11 w-full rounded-xl border border-slate-300 px-4 text-sm text-slate-900">
                        @error('recipient_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-[#21409a] px-5 text-sm font-semibold text-white hover:bg-[#1b367d]">Send Link</button>
                </form>
                @endif
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ \Illuminate\Support\Str::of($clientApprovalStatus)->replace('_', ' ')->title() }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sent To</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $report->client_form_sent_to_email ?: '-' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sent At</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ optional($report->client_form_sent_at)->format('M d, Y h:i A') ?: '-' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Approved At</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ optional($report->client_approved_at)->format('M d, Y h:i A') ?: '-' }}</p>
                </div>
            </div>

            @php($manualReportApprovalDisabled = $regularLocked || $clientApprovalStatus === 'approved')
                <form method="POST" action="{{ route('regular.report.manual-approve', ['regular' => $regular->id, 'report' => $report->id]) }}" enctype="multipart/form-data" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
                    @csrf
                    <p class="text-sm font-semibold text-amber-900">Manual Approve RSAT</p>
                    <p class="mt-1 text-xs text-amber-800">Use this when the client signed outside the portal. Upload the signed RSAT report to approve and continue.</p>
                    <div class="mt-3 grid gap-3 md:grid-cols-[minmax(0,1fr)_220px_auto] md:items-end">
                        <input type="file" name="signed_document" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="block w-full rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm" @disabled($manualReportApprovalDisabled)>
                        <input type="text" name="approval_name" placeholder="Approver name" class="h-10 rounded-lg border border-amber-200 bg-white px-3 text-sm" @disabled($manualReportApprovalDisabled)>
                        <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-amber-600 px-4 text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-60" @disabled($manualReportApprovalDisabled)>Upload & Approve</button>
                    </div>
                </form>
        </div>

        <section class="rsat-sheet overflow-hidden p-6">
            <div class="rsat-form">
                <div class="grid gap-6 lg:grid-cols-[220px_1fr]">
                    <div>
                        <img src="{{ asset('images/imaglogo.png') }}" alt="John Kelly and Company" class="h-24 w-auto object-contain">
                    </div>
                    <div class="space-y-2">
                        <div class="rsat-title">REGULAR SERVICE ACTIVITY<br>TRACKER REPORT (RSAT REPORT)</div>
                        <div class="rsat-form-code">REG-F-003</div>
                    </div>
                </div>

                <div class="rsat-intro">
                    This generated RSAT report follows the same formal document layout and branding used throughout the regular service workflow.
                </div>

                <div class="rsat-notice">
                    Please review the report details below before sharing, printing, or using it for client-facing confirmation.
                </div>

                <div class="rsat-section-title">REPORT INFORMATION</div>

                <div class="rsat-report-info-grid">
                    <div class="rsat-meta-item" style="grid-template-columns: 120px minmax(0, 1fr);">
                        <div class="rsat-meta-label">Report No.:</div>
                        <div class="rsat-line-value">{{ $report->report_number ?: '-' }}</div>
                    </div>
                    <div class="rsat-meta-item" style="grid-template-columns: 135px minmax(0, 1fr);">
                        <div class="rsat-meta-label">Report Date:</div>
                        <div class="rsat-line-value">{{ $fmt($report->date_prepared) }}</div>
                    </div>
                    <div class="rsat-meta-item" style="grid-template-columns: 140px minmax(0, 1fr);">
                        <div class="rsat-meta-label">Report Period:</div>
                        <div class="rsat-line-value">{{ $reportApproval['report_period'] ?? '-' }}</div>
                    </div>
                </div>

                <div class="mt-8 rsat-meta-grid">
                    <div class="rsat-meta-item"><div class="rsat-meta-label">Client Name:</div><div class="rsat-line-value">{{ $contactName }}</div></div>
                    <div class="rsat-meta-item"><div class="rsat-meta-label">Date Created:</div><div class="rsat-line-value">{{ optional($rsat?->form_date ?? $rsat?->created_at)->format('m/d/Y') ?: '-' }}</div></div>
                    <div class="rsat-meta-item"><div class="rsat-meta-label">Business Name:</div><div class="rsat-line-value">{{ $regular->business_name ?: '-' }}</div></div>
                    <div class="rsat-meta-item"><div class="rsat-meta-label">Engagement Type:</div><div class="rsat-line-value">{{ $regular->engagement_type ?: '-' }}</div></div>
                    <div class="rsat-meta-item"><div class="rsat-meta-label">Condeal Ref No.:</div><div class="rsat-line-value">{{ $regular->deal?->deal_code ?: '-' }}</div></div>
                    <div class="rsat-meta-item"><div class="rsat-meta-label">Services:</div><div class="rsat-line-value">{{ $regular->services ?: '-' }}</div></div>
                    <div class="rsat-meta-item"><div class="rsat-meta-label">Service Area:</div><div class="rsat-line-value">{{ $regular->service_area ?: '-' }}</div></div>
                    <div class="rsat-meta-item"><div class="rsat-meta-label">Product:</div><div class="rsat-line-value">{{ $regular->products ?: '-' }}</div></div>
                </div>

                <div class="mt-5 rsat-client-row">
                    <div class="rsat-meta-item" style="grid-template-columns: 90px minmax(0, 1fr);">
                        <div class="rsat-meta-label">BIF No.</div>
                        <div class="rsat-line-value">{{ $regular->company?->latestBif?->bif_no ?? '-' }}</div>
                    </div>
                </div>

                <div class="rsat-report-table-wrap">
                    <table class="rsat-report-table">
                        <thead>
                            <tr>
                                <th style="width: 8%;">Item #</th>
                                <th style="width: 20%;">Service</th>
                                <th style="width: 28%;">Activity / Output</th>
                                <th style="width: 18%;">Frequency</th>
                                <th style="width: 16%;">Reminder Lead Time</th>
                                <th style="width: 10%;">Deadline</th>
                                <th style="width: 10%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($filledRows as $index => $item)
                                <tr>
                                    <td class="center">{{ $index + 1 }}</td>
                                    <td>{{ $item['service'] ?? '' }}</td>
                                    <td>{{ $item['activity_output'] ?? '' }}</td>
                                    <td>{{ $item['frequency'] ?? '' }}</td>
                                    <td>{{ $item['reminder_lead_time'] ?? '' }}</td>
                                    <td>{{ $item['deadline'] ?? '' }}</td>
                                    <td>{{ \Illuminate\Support\Str::of((string) ($item['status'] ?? 'open'))->replace('_', ' ')->title() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="center" style="padding: 14px 12px; color: #64748b; font-family: Arial, Helvetica, sans-serif;">
                                        No RSAT report rows recorded yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="rsat-signature">
                    <div class="rsat-signature-line">{{ $report->client_confirmation_name ?: '-' }}</div>
                    <div class="rsat-signature-label">Client Fullname &amp; Signature</div>
                </div>

                <div class="mt-4 rsat-meta-grid">
                    <div class="rsat-meta-item">
                        <div class="rsat-meta-label">Client Attachment:</div>
                        <div class="rsat-line-value">
                            @if ($report->client_attachment_path)
                                <a href="{{ route('uploads.show', ['path' => $report->client_attachment_path, 'download' => 1]) }}" class="text-blue-700 hover:text-blue-800">Download uploaded attachment</a>
                            @else
                                -
                            @endif
                        </div>
                    </div>
                </div>

                <div class="rsat-section-title">INTERNAL APPROVAL</div>

                <div class="rsat-approval-grid">
                    <div class="rsat-approval-pair"><div class="rsat-approval-label">Prepared By:</div><div class="rsat-line-value">{{ $reportApproval['prepared_by'] ?? '-' }}</div></div>
                    <div class="rsat-approval-pair"><div class="rsat-approval-label">Reviewed By:</div><div class="rsat-line-value">{{ $reportApproval['reviewed_by'] ?? '-' }}</div></div>
                    <div class="rsat-approval-pair"><div class="rsat-approval-label">Name:</div><div class="rsat-line-value">{{ $reportApproval['prepared_by_name'] ?? '-' }}</div></div>
                    <div class="rsat-approval-pair"><div class="rsat-approval-label">Name:</div><div class="rsat-line-value">{{ $reportApproval['reviewed_by_name'] ?? '-' }}</div></div>
                    <div class="rsat-approval-pair"><div class="rsat-approval-label">Date:</div><div class="rsat-line-value">{{ $reportApproval['prepared_by_date'] ?? '-' }}</div></div>
                    <div class="rsat-approval-pair"><div class="rsat-approval-label">Date:</div><div class="rsat-line-value">{{ $reportApproval['reviewed_by_date'] ?? '-' }}</div></div>
                </div>

                <div class="rsat-footer-grid">
                    <div class="rsat-footer-pair"><div>Referred By/Closed By:</div><div class="rsat-line-value">{{ $reportApproval['referred_by_closed_by'] ?? '-' }}</div></div>
                    <div class="rsat-footer-pair"><div>Sales &amp; Marketing:</div><div class="rsat-line-value">{{ $reportApproval['sales_marketing'] ?? '-' }}</div></div>
                    <div class="rsat-footer-pair"><div>Lead Consultant:</div><div class="rsat-line-value">{{ $reportApproval['lead_consultant'] ?? '-' }}</div></div>
                    <div class="rsat-footer-pair"><div>Lead Associate Assigned:</div><div class="rsat-line-value">{{ $reportApproval['lead_associate_assigned'] ?? '-' }}</div></div>
                    <div class="rsat-footer-pair"><div>Finance:</div><div class="rsat-line-value">{{ $reportApproval['finance'] ?? '-' }}</div></div>
                    <div class="rsat-footer-pair"><div>President:</div><div class="rsat-line-value">{{ $reportApproval['president'] ?? '-' }}</div></div>
                </div>

                <div class="mt-4 rsat-footer-grid">
                    <div class="rsat-footer-pair" style="grid-template-columns: 230px minmax(0, 1fr);">
                        <div>Record Custodian ( Name and Signature)</div>
                        <div class="rsat-line-value">{{ $reportApproval['record_custodian'] ?? '-' }}</div>
                    </div>
                    <div class="space-y-3">
                        <div class="rsat-footer-pair"><div>Date Recorded:</div><div class="rsat-line-value">{{ $fmt($reportApproval['date_recorded'] ?? null) }}</div></div>
                        <div class="rsat-footer-pair"><div>Date Signed:</div><div class="rsat-line-value">{{ $fmt($reportApproval['date_signed'] ?? null) }}</div></div>
                    </div>
                </div>

                <div class="mt-8 border-t border-slate-200 pt-6">
                    <div class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Transmittal Reference</div>
                    <div class="mt-4 rsat-meta-grid">
                        <div class="rsat-meta-item"><div class="rsat-meta-label">Transmittal No.:</div><div class="rsat-line-value">{{ $reportApproval['transmittal_no'] ?? '-' }}</div></div>
                        <div class="rsat-meta-item" style="grid-template-columns: 250px minmax(0, 1fr);"><div class="rsat-meta-label">Date Submitted For Transmittal:</div><div class="rsat-line-value">{{ $fmt($reportApproval['date_submitted_for_transmittal'] ?? null) }}</div></div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

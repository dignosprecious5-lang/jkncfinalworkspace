@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/project-workspace.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/dashboard-execution.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/delivery-completion.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/rsat-form.css') }}">
@endpush

@section('content')
@php
    $fmt = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('M d, Y') : '-';
    $dateInput = function ($v) {
        $value = trim((string) $v);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    };
    $contactName = trim(collect([$regular->contact?->first_name, $regular->contact?->last_name])->filter()->implode(' ')) ?: '-';
    $rsatRequirements = collect($rsat?->engagement_requirements ?? [])->whenEmpty(fn () => collect([['number' => 1, 'requirement' => '', 'notes' => '', 'purpose' => '', 'provided_by' => '', 'submitted_to' => '', 'assigned_to' => '', 'timeline' => '', 'status' => 'open']]));
    $rsatClearance = (array) ($rsat?->clearance ?? []);
    $formDate = old('form_date', optional($rsat?->form_date ?? $rsat?->created_at)->format('Y-m-d'));
    $dateStarted = old('date_started', optional($rsat?->date_started)->format('Y-m-d'));
    $dateCompleted = old('date_completed', optional($rsat?->date_completed)->format('Y-m-d'));
    $approvalPreparedBy = old('clearance_assigned_team_lead', $rsatClearance['assigned_team_lead'] ?? '');
    $approvalReviewedBy = old('clearance_lead_consultant_confirmed', $rsatClearance['lead_consultant_confirmed'] ?? '');
    $approvalReferredBy = old('rejection_reason', $rsat?->rejection_reason);
    $approvalSalesMarketing = old('clearance_sales_marketing', $rsatClearance['sales_marketing'] ?? '');
    if (blank($approvalSalesMarketing) || $approvalSalesMarketing === 'Sales & Marketing') {
        $approvalSalesMarketing = data_get($regular->metadata ?? [], 'internal_assignments.sales_marketing', $approvalSalesMarketing);
    }
    $approvalLeadAssociate = old('clearance_lead_associate_assigned', $rsatClearance['lead_associate_assigned'] ?? '');
    $savedLeadConsultantApproval = data_get($rsat->approval_steps ?? [], '0.requirement') === 'Lead Consultant'
        ? data_get($rsat->approval_steps ?? [], '0.responsible_person')
        : null;
    $savedFinanceApproval = data_get($rsat->approval_steps ?? [], '1.requirement') === 'Finance'
        ? data_get($rsat->approval_steps ?? [], '1.responsible_person')
        : null;
    $approvalLeadConsultant = old('approval_responsible_person.0', $savedLeadConsultantApproval ?? ($regular->assigned_consultant ?? ''));
    $approvalFinance = old('approval_responsible_person.1', $savedFinanceApproval ?? data_get($regular->metadata ?? [], 'internal_assignments.finance', ''));
    if ($approvalFinance === 'Finance') {
        $approvalFinance = data_get($regular->metadata ?? [], 'internal_assignments.finance', $approvalFinance);
    }
    $approvalPresident = old('approval_name_and_signature.0', data_get($rsat->approval_steps ?? [], '0.name_and_signature'));
    if (blank($approvalPresident) || $approvalPresident === 'President') {
        $approvalPresident = 'John Kelly Abalde';
    }
    $recordCustodian = old('clearance_record_custodian_name', $rsatClearance['record_custodian_name'] ?? '');
    $recordedDate = old('clearance_date_recorded', $rsatClearance['date_recorded'] ?? '');
    $signedDate = old('clearance_date_signed', $rsatClearance['date_signed'] ?? '');
    $generatedReports = $generatedReports ?? collect();
    $rsatAttachments = collect($rsat?->attachments ?? []);
    $ntpApproved = $ntpRecord?->client_response_status === 'approved_to_proceed' && $ntpRecord?->client_approved_at;
    $ntpStatusLabel = $ntpApproved
        ? 'Client approved NTP'
        : ($ntpRecord ? 'NTP generated, waiting for signed upload' : 'NTP not generated');
    $regularLocked = $regularLocked ?? ($regular->status === 'Completed');
@endphp

<style>
    :root {
        --navy: #102d79;
        --navy2: #1e3a8a;
        --line: #e2e8f0;
        --soft: #64748b;
    }
    .rsat-workspace {
        background:
            radial-gradient(circle at top left, rgba(13, 70, 140, 0.05), transparent 28%),
            linear-gradient(180deg, #f4f7fb 0%, #fbfcfe 26%, #fbfcfe 100%);
        min-height: 100vh;
    }
    .workspace-breadcrumb {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 18px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
        font-size: 12px;
        color: #64748b;
    }
    .workspace-breadcrumb a {
        color: #102d79;
        font-weight: 700;
        text-decoration: none;
    }
    .workspace-head {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 20px 22px 18px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
    }
    .workspace-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
    }
    .eyebrow {
        font-size: 10px;
        letter-spacing: 2px;
        color: #76839a;
        font-weight: 800;
        text-transform: uppercase;
    }
    .workspace-title {
        font-size: 22px;
        font-weight: 700;
        color: #0f172a;
        margin: 6px 0 4px;
    }
    .refline {
        font-size: 12px;
        color: #64748b;
    }
    .chips {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .chip {
        border: 1px solid #dce3ed;
        border-radius: 999px;
        padding: 7px 13px;
        background: #fff;
        color: #59667a;
        font-size: 10px;
        white-space: nowrap;
    }
    .chip strong {
        color: #1e293b;
        margin-left: 4px;
    }
    .primary-tabs {
        display: flex;
        gap: 4px;
        flex-wrap: nowrap;
        margin-top: 18px;
        padding: 4px;
        border: 1px solid #e6ebf3;
        border-radius: 12px;
        background: #f8fafc;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .primary-tabs::-webkit-scrollbar {
        display: none;
    }
    .ptab {
        min-height: 36px;
        flex: 1 1 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid transparent;
        border-radius: 8px;
        background: transparent;
        color: #52627a;
        font-size: 10px;
        letter-spacing: 0.5px;
        font-weight: 700;
        padding: 0 14px;
        text-decoration: none;
        white-space: nowrap;
        transition: all 140ms ease;
    }
    .ptab:hover {
        border-color: #d5dfef;
        background: #fff;
        color: #102d79;
    }
    .ptab.active {
        border-color: #102d79;
        background: #102d79;
        color: #fff;
        box-shadow: 0 4px 10px rgba(16, 45, 121, 0.2);
    }
    /* Stepper Lifecycle Card */
    .lifecycle {
        background: #fff;
        border: 1px solid #e4e9f1;
        border-radius: 14px;
        padding: 18px 24px;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
        display: grid;
        grid-template-columns: 1fr 210px;
        gap: 24px;
        align-items: stretch;
        margin-bottom: 14px;
    }
    .lifecycle-main {
        flex: 1 1 auto;
        min-width: 0;
    }
    .life-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
    }
    .life-title strong {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
    }
    .life-title span {
        font-size: 9px;
        font-weight: 500;
        color: #94a3b8;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        padding: 3px 10px;
    }
    .life-track {
        display: flex !important;
        align-items: stretch;
        position: relative;
        width: 100%;
        padding: 0;
        margin: 0;
    }
    .life-stage-col {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 14px 4px;
        border-radius: 8px;
        position: relative;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .life-stage-col.current {
        background: #edf3fc;
    }
    .life-circle-row {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    .life-line-left, .life-line-right {
        flex: 1;
        height: 2px;
        background: #cbd5e1;
    }
    .life-line-left.done, .life-line-right.done {
        background: #102d79;
    }
    .life-line-spacer {
        flex: 1;
        height: 2px;
        visibility: hidden;
    }
    .life-circle {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        position: relative;
        z-index: 2;
        background: #fff;
        border: 2px solid #cbd5e1;
        transition: all 0.15s ease;
    }
    .life-circle.done {
        background: #102d79;
        border-color: #102d79;
        color: #fff;
        font-size: 12px;
    }
    .life-circle.current {
        background: #fff;
        border: 2.5px solid #102d79;
        box-shadow: 0 0 0 3px #edf3fc, 0 0 0 5px #102d79;
        color: #102d79;
    }
    .life-label {
        margin-top: 10px;
        font-size: 10px;
        text-align: center;
        white-space: nowrap;
        text-decoration: none;
        display: block;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #94a3b8;
        font-weight: 500;
        transition: color 0.15s ease;
    }
    .life-label.done {
        color: #102d79;
        font-weight: 700;
    }
    .life-label.current {
        color: #102d79;
        font-weight: 800;
    }
    .life-side {
        border-left: 1px solid #e2e8f0;
        padding-left: 20px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .life-side-kicker {
        font-size: 9px;
        letter-spacing: 1px;
        font-weight: 800;
        color: #94a3b8;
        text-transform: uppercase;
    }
    .life-side-stage {
        font-size: 20px;
        font-weight: 800;
        color: #0f172a;
        margin: 2px 0 3px;
        line-height: 1.15;
    }
    .life-side-pct {
        font-size: 12px;
        font-weight: 700;
        color: #102d79;
        margin-bottom: 5px;
    }
    .life-side-health {
        font-size: 11px;
        color: #64748b;
        margin-top: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .life-side-tag {
        font-weight: 700;
        color: #0f172a;
    }
    @media (max-width: 1100px) {
        .lifecycle { grid-template-columns: 1fr; }
        .life-side { border-left: 0; border-top: 1px solid #eef2f7; padding-left: 0; padding-top: 14px; }
    }
    /* Command Overview Card */
    .command-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }
    .command-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 20px;
        border-bottom: 1px solid #eef2f6;
    }
    .command-head h2 {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }
    .command-head p {
        font-size: 11px;
        color: #64748b;
        margin: 2px 0 0;
    }
    .command-body {
        padding: 18px 20px;
    }
    .overview-grid {
        display: grid;
        grid-template-columns: 1.15fr 1fr 1.15fr;
        gap: 14px;
    }
    .overview-panel {
        padding: 14px;
        background: #f8fafc;
        border: 1px solid #e8ecf4;
        border-radius: 10px;
    }
    .overview-panel h4 {
        margin: 0 0 10px;
        font-size: 9px;
        letter-spacing: 0.08em;
        font-weight: 800;
        color: #64748b;
        text-transform: uppercase;
    }
    .overview-panel dl {
        display: grid;
        gap: 8px;
        margin: 0;
    }
    .overview-panel dl div {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        border-bottom: 1px dashed #e2e8f0;
        padding-bottom: 6px;
        font-size: 11px;
    }
    .overview-panel dt {
        color: #64748b;
    }
    .overview-panel dd {
        margin: 0;
        text-align: right;
    }
    .attention-card { border-left: 5px solid #df404b; }
    .attention-list { display: grid; gap: 9px; }
    .attention-item { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid #edd1d4; background: #fff7f7; border-radius: 9px; }
    .attention-item.warning { border-color: #f0d2a8; background: #fff9f0; }
    .attention-item.good { border-color: #bde2d1; background: #f1faf6; }
    .severity { padding: 4px 8px; border-radius: 999px; background: #df404b; color: #fff; font-size: 9px; font-weight: 800; letter-spacing: .05em; }
    .warning .severity { background: #d68118; }
    .good .severity { background: #17845d; }
    .attention-item strong { display: block; font-size: 12px; color: #0f172a; }
    .attention-item small { color: #64748b; font-size: 10px; }
    .attention-item a { font-weight: 800; color: #102d79; font-size: 11px; text-decoration: none; }
    .command-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; }
    .command-kpi { padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; }
    .command-kpi span { display: block; color: #64748b; font-size: 9px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
    .command-kpi strong { display: block; margin-top: 4px; font-size: 16px; font-weight: 800; color: #0f172a; }
    .command-kpi small { font-size: 9px; color: #94a3b8; }
    .tone-good { color: #166534 !important; }
    .tone-warning { color: #b45309 !important; }
    .tone-critical { color: #dc2626 !important; }
    .command-two { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .current-stage { background: linear-gradient(135deg, #102d79, #1e40af); color: #fff; }
    .current-stage .command-head { border-color: rgba(255,255,255,.15); }
    .current-stage .command-head h2, .current-stage .command-head p { color: #fff; }
    .current-stage .command-body { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .stage-hero span { font-size: 9px; opacity: .8; font-weight: 700; letter-spacing: 0.05em; }
    .stage-hero strong { display: block; font-size: 22px; margin: 2px 0 6px; font-weight: 800; }
    .stage-status { display: inline-block; padding: 3px 8px; background: rgba(255,255,255,.18); border-radius: 999px; font-size: 10px; font-weight: 700; }
    .time-list { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .time-list div { padding: 8px; background: rgba(255,255,255,.1); border-radius: 6px; }
    .time-list span { display: block; font-size: 8px; opacity: .8; font-weight: 700; }
    .time-list strong { font-size: 12px; }
    .pending-box { grid-column: 1/-1; padding: 10px 12px; background: #fff; color: #1e293b; border-radius: 8px; font-size: 11px; }
    .stage-link { float: right; color: #102d79; font-weight: 800; text-decoration: none; }
    .now-grid { display: grid; gap: 8px; margin: 0; }
    .now-grid div { display: flex; justify-content: space-between; gap: 12px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 6px; font-size: 11px; }
    .now-grid span { color: #64748b; }
    .indicator-key { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .indicator-key span {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 10px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 999px;
        background: #f1f3f6;
        line-height: 1.3;
    }
    .indicator-key span.tone-good { color: #16845c !important; }
    .indicator-key span.tone-warning { color: #c57616 !important; }
    .indicator-key span.tone-critical { color: #d53e49 !important; }
    .performance-table { width: 100%; border-collapse: collapse; }
    .performance-table th, .performance-table td { padding: 9px 12px; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 11px; }
    .performance-table th { background: #f8fafc; color: #64748b; text-transform: uppercase; letter-spacing: .04em; font-size: 9px; font-weight: 800; }
    .perf { display: inline-block; padding: 3px 8px; border-radius: 999px; font-size: 9px; font-weight: 800; }
    .perf.good { background: #dcfce7; color: #166534; }
    .perf.warning { background: #fef3c7; color: #92400e; }
    .perf.bad { background: #fee2e2; color: #991b1b; }
    .summary-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; }
    .summary-links a { display: block; padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fafbfc; text-decoration: none; transition: all 0.15s ease; }
    .summary-links a:hover { border-color: #102d79; transform: translateY(-1px); }
    .summary-links span { display: block; color: #64748b; font-size: 9px; font-weight: 800; letter-spacing: .04em; }
    .summary-links strong { display: block; font-size: 16px; margin-top: 4px; color: #0f172a; }
    .activity-list { display: grid; }
    .activity-row { display: grid; grid-template-columns: 120px 16px 1fr; gap: 8px; padding: 10px 0; border-bottom: 1px solid #e2e8f0; align-items: center; font-size: 11px; }
    .activity-row time { color: #64748b; font-size: 10px; }
    .activity-row i { width: 8px; height: 8px; background: #102d79; border-radius: 50%; display: inline-block; }
    .table-scroll { overflow-x: auto; }
    .execution-kpis, .execution-report-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 9px; }
    .execution-kpi { padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; }
    .execution-kpi span { display: block; color: #64748b; font-size: 8px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
    .execution-kpi strong { display: block; margin-top: 3px; color: #0f172a; font-size: 15px; font-weight: 800; }
    .execution-kpi small { display: block; margin-top: 2px; color: #94a3b8; font-size: 8px; }
    .execution-kpi.good strong { color: #166534; }
    .execution-progress-wrap { margin: 12px 0; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }
    .execution-progress-wrap > div:first-child { display: flex; justify-content: space-between; color: #475569; font-size: 11px; font-weight: 600; }
    .execution-progress { height: 8px; margin-top: 8px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
    .execution-progress i { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #102d79, #3b82f6); transition: width .25s; }
    .execution-report-grid { display: grid; grid-template-columns: 1.35fr 1fr; gap: 14px; margin-top: 14px; }
    .execution-report-list { display: grid; gap: 8px; }
    .execution-report-item { display: grid; grid-template-columns: 1fr auto; gap: 6px; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fafbfc; }
    .execution-report-item strong { font-size: 11px; color: #0f172a; }
    .execution-report-item p { grid-column: 1/-1; margin: 0; color: #64748b; font-size: 10px; line-height: 1.4; }
    .execution-report-item time { font-size: 9px; color: #94a3b8; }
    .execution-report-item.good { border-color: #bbf7d0; background: #f0fdf4; }
    .execution-report-item.warning { border-color: #fde68a; background: #fffbeb; }
    @media(max-width:900px){
        .overview-grid, .command-two, .execution-report-grid { grid-template-columns: 1fr; }
    }
    .rsat-top-card {
        border: 1px solid #d8e1ee;
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.05);
    }
    .rsat-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        border: 1px solid #cfd9e7;
        background: #fff;
        padding: 10px 16px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #1e3a5f;
    }
    .rsat-pill .label {
        color: #94a3b8;
        font-weight: 700;
    }
    .rsat-tab-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        border: 1px solid #cfd9e7;
        background: #fff;
        padding: 10px 18px;
        font-size: 0.84rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #1e3a5f;
        transition: all 0.16s ease;
    }
    .rsat-tab-link.active {
        border-color: #1c4587;
        background: #1c4587;
        color: #fff;
        box-shadow: 0 10px 22px rgba(28, 69, 135, 0.18);
    }
    .rsat-tab-link:hover {
        border-color: #9eb2cf;
        color: #1c4587;
    }
    .rsat-linked-card {
        border: 1px solid #d8e1ee;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.04);
    }
    .rsat-linked-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .rsat-work-grid { display: grid; grid-template-columns: 240px minmax(0, 1fr); gap: 24px; align-items: start; }
    .rsat-quick-actions { border: 1px solid #d8e1ee; background: rgba(255, 255, 255, 0.96); box-shadow: 0 14px 30px rgba(15, 23, 42, 0.04); }
    .rsat-quick-title { font-size: 0.78rem; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b; }
    .rsat-quick-grid { display: grid; gap: 12px; margin-top: 14px; }
    .rsat-quick-group { border: 1px solid #e2e8f0; border-radius: 16px; padding: 12px; background: #fff; }
    .rsat-quick-label { font-size: 0.72rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #94a3b8; }
    .rsat-quick-stack { display: grid; gap: 10px; margin-top: 10px; }
    .rsat-doc-action {
        display: inline-flex;
        align-items: center;
        border: 1px solid #cbd5e1;
        background: #fff;
        padding: 9px 12px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #334155;
    }
    .rsat-doc-action-approved { border-color: #86efac; background: #dcfce7; color: #166534; }
    .rsat-doc-primary {
        display: inline-flex;
        align-items: center;
        background: #21409a;
        color: #fff;
        padding: 10px 14px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .rsat-sheet {
        border: 1px solid #cbd5e1;
        background: #fff;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.06);
    }
    .rsat-form {
        border: 1px solid #cbd5e1;
        border-radius: 0 !important;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        padding: 28px 32px 36px;
    }

    /* 1. Sharp 90-Degree Corners strictly for internal document report items inside RSAT Form Tab */
    .rsat-form table,
    .rsat-form table th,
    .rsat-form table td,
    .rsat-form input,
    .rsat-form select,
    .rsat-form textarea,
    .rsat-form .rsat-section-title,
    .rsat-form .rsat-summary-banner,
    .rsat-form .rsat-summary-card {
        border-radius: 0 !important;
    }
    .rsat-top-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding-bottom: 12px;
        border-bottom: 2px solid #102d79;
    }
    .rsat-brand {
        font-family: Georgia, "Times New Roman", serif;
        font-weight: 700;
        font-size: 1.6rem;
        line-height: 1.1;
        color: #102d79;
    }
    .rsat-doc-type-right {
        text-align: right;
    }
    .rsat-doc-type-right h1 {
        font-family: Georgia, "Times New Roman", serif;
        font-weight: 800;
        font-size: 2.3rem;
        line-height: 1;
        letter-spacing: -0.01em;
        color: #102d79;
        margin: 0;
    }
    .rsat-doc-type-right span {
        font-family: Georgia, "Times New Roman", serif;
        font-size: 0.68rem;
        color: #64748b;
        display: block;
        margin-top: 3px;
    }
    .rsat-meta-grid {
        display: grid;
        gap: 8px 28px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-top: 18px;
        margin-bottom: 20px;
    }
    .rsat-meta-item {
        display: grid;
        grid-template-columns: max-content minmax(0, 1fr);
        align-items: end;
        gap: 8px;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 0.8rem;
    }
    .rsat-meta-label {
        font-weight: 700;
        color: #1e293b;
        white-space: nowrap;
        padding-bottom: 2px;
    }
    .rsat-line-value,
    .rsat-line-input {
        min-height: 24px;
        border: 0;
        border-bottom: 1px solid #64748b;
        background: transparent;
        padding: 2px 4px;
        color: #1e293b;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 0.8rem;
        width: 100%;
        line-height: 1.3;
    }
    .rsat-line-input:focus {
        outline: none;
        border-bottom-color: #102d79;
    }
    .rsat-table-wrap {
        margin-top: 18px;
        overflow-x: auto;
        border: 1px solid #102d79;
    }
    .rsat-table {
        width: 100%;
        min-width: 1000px;
        border-collapse: collapse;
        table-layout: fixed;
        font-family: Georgia, "Times New Roman", serif;
    }
    .rsat-table th {
        background: #102d79;
        color: #ffffff;
        padding: 9px 8px;
        text-align: left;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        border-right: 1px solid rgba(255, 255, 255, 0.2);
    }
    .rsat-table th:last-child {
        border-right: 0;
    }
    .rsat-table td {
        border: 1px solid #cbd5e1;
        padding: 8px;
        vertical-align: top;
        background: #fff;
    }
    .rsat-matrix-row:nth-child(even) td {
        background: #f8fafc;
    }
    .rsat-cell-card {
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 5px 8px;
        background: #fff;
        font-size: 0.78rem;
        min-height: 36px;
        width: 100%;
    }
    .rsat-cell-input {
        width: 100%;
        border: 0;
        background: transparent;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 0.78rem;
        color: #1e293b;
        padding: 0;
    }
    .rsat-cell-input:focus {
        outline: none;
    }
    .rsat-sublabel {
        font-size: 0.64rem;
        color: #64748b;
        margin-bottom: 3px;
        font-family: system-ui, -apple-system, sans-serif;
    }
    .rsat-select-box {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 4px 6px;
        font-size: 0.76rem;
        background: #fff;
        color: #1e293b;
        font-family: system-ui, -apple-system, sans-serif;
    }
    .rsat-subnote {
        font-size: 0.62rem;
        color: #94a3b8;
        margin-top: 3px;
        font-family: system-ui, -apple-system, sans-serif;
    }
    .rsat-dash-link {
        display: inline-block;
        margin-top: 5px;
        font-size: 0.64rem;
        color: #3b82f6;
        text-decoration: none;
        cursor: pointer;
        font-family: system-ui, -apple-system, sans-serif;
    }
    .rsat-dash-link:hover {
        text-decoration: underline;
    }
    .rsat-action-flex {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        width: 100%;
    }
    .rsat-btn-square {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #ffffff;
        color: #64748b;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.15s ease;
        line-height: 1;
        padding: 0;
    }
    .rsat-btn-square:hover {
        border-color: #94a3b8;
        background: #f8fafc;
        color: #0f172a;
    }
    .rsat-btn-square.delete:hover {
        border-color: #fca5a5;
        background: #fef2f2;
        color: #dc2626;
    }
    .rsat-summary-banner {
        background: #102d79;
        color: #ffffff;
        padding: 9px 16px;
        text-align: center;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 1.05rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        margin-top: 26px;
        text-transform: uppercase;
    }
    .rsat-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
        margin-top: 12px;
    }
    .rsat-summary-card {
        border: 1px solid #cbd5e1;
        padding: 12px 16px;
        background: #fff;
    }
    .rsat-summary-card .label {
        font-size: 0.65rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        color: #64748b;
        text-transform: uppercase;
        font-family: system-ui, -apple-system, sans-serif;
    }
    .rsat-summary-card .val {
        font-size: 1.5rem;
        font-weight: 800;
        color: #0f172a;
        margin-top: 4px;
        font-family: Georgia, serif;
    }
    .rsat-footer-note-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.65rem;
        color: #94a3b8;
        margin-top: 16px;
        padding-top: 8px;
        border-top: 1px solid #e2e8f0;
        font-family: system-ui, -apple-system, sans-serif;
    }
    .rsat-tool-box {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #fff;
        padding: 14px;
        margin-bottom: 16px;
        font-family: system-ui, -apple-system, sans-serif;
    }
    .rsat-tool-title {
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #1e293b;
        margin-bottom: 12px;
    }
    .rsat-tool-label {
        font-size: 0.65rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: 4px;
    }
    .rsat-tool-input {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 6px 8px;
        font-size: 0.75rem;
        color: #475569;
        background: #f8fafc;
    }
    .rsat-btn-tool {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #fff;
        padding: 7px 10px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #334155;
        text-align: center;
        cursor: pointer;
        transition: all 0.15s ease;
        display: inline-block;
    }
    .rsat-btn-tool:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }
    .rsat-approval-grid {
        margin-top: 18px;
        display: grid;
        gap: 12px 26px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .rsat-approval-pair {
        display: grid;
        grid-template-columns: 145px minmax(0, 1fr);
        align-items: end;
        gap: 10px;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 0.88rem;
    }
    .rsat-approval-label {
        color: #334155;
        font-style: italic;
    }
    .rsat-footer-grid {
        margin-top: 12px;
        display: grid;
        gap: 12px 26px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .rsat-footer-pair {
        display: grid;
        grid-template-columns: 180px minmax(0, 1fr);
        align-items: end;
        gap: 10px;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 0.88rem;
    }
    .rsat-footer-note {
        display: grid;
        gap: 8px;
    }
    .rsat-tab {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        border: 1px solid #cfd9e7;
        background: #fff;
        padding: 10px 16px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #475569;
        transition: all 0.2s ease;
    }
    .rsat-tab.is-active {
        border-color: #1c4587;
        background: #1c4587;
        color: #fff;
        box-shadow: 0 10px 24px rgba(28, 69, 135, 0.18);
    }
    .rsat-settings-trigger { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border: 1px solid #cbd5e1; background: #fff; color: #475569; cursor: pointer; }
    .rsat-settings-modal { position: fixed; inset: 0; z-index: 75; display: none; }
    .rsat-settings-modal.is-open { display: block; }
    .rsat-settings-overlay { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.5); }
    .rsat-settings-frame { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; padding: 16px; }
    .rsat-settings-card { width: 100%; max-width: 430px; background: #fff; border: 1px solid #dbe3f0; box-shadow: 0 18px 36px rgba(15, 23, 42, 0.2); }
    .rsat-ntp-modal { position: fixed; inset: 0; z-index: 70; display: none; }
    .rsat-ntp-modal.is-open { display: block; }
    .rsat-ntp-overlay { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.55); }
    .rsat-ntp-frame { position: absolute; inset: 0; overflow-y: auto; padding: 28px 16px; }
    .rsat-ntp-shell { position: relative; max-width: 1060px; margin: 0 auto; }
    .rsat-doc-view-shell { border: 1px solid #d8e1ee; background: #fff; box-shadow: 0 16px 34px rgba(15, 23, 42, 0.05); overflow: hidden; }
    .rsat-doc-view-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; padding: 18px 22px; border-bottom: 1px solid #dbe3f0; background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%); }
    .rsat-doc-view-eyebrow { font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #64748b; }
    .rsat-doc-view-title { margin-top: 6px; font-size: 1.2rem; font-weight: 700; color: #0f172a; }
    .rsat-doc-view-copy { margin-top: 6px; max-width: 620px; font-size: .88rem; line-height: 1.45; color: #64748b; }
    .rsat-doc-view-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 10px; }
    .rsat-doc-view-action { display: inline-flex; align-items: center; justify-content: center; min-width: 132px; border: 1px solid #cbd5e1; background: #fff; padding: 10px 14px; font-size: .84rem; font-weight: 700; color: #0f172a; text-decoration: none; }
    .rsat-doc-view-body { max-height: calc(100vh - 210px); overflow-y: auto; padding: 24px; background: linear-gradient(180deg, #eef4ff 0%, #f8fbff 100%); }
    .rsat-doc-view-body .project-ntp-sheet { border: 1px solid #d8e1ee; background: #fff; box-shadow: 0 16px 34px rgba(15, 23, 42, 0.05); }
    .rsat-doc-view-body .project-ntp-doc { padding: 32px 34px 36px; border: 2px solid #1c4587; }
    .rsat-doc-view-body .project-ntp-title { font-family: Georgia, "Times New Roman", serif; font-size: 18pt; font-weight: 700; line-height: 1.05; }
    .rsat-doc-view-body .project-ntp-code { margin-bottom: 24px; font-family: Georgia, "Times New Roman", serif; font-size: 8pt; font-weight: 700; }
    .rsat-doc-view-body .project-ntp-issued { margin: 18px 0 12px; font-family: Georgia, "Times New Roman", serif; font-size: 12pt; font-weight: 700; }
    .rsat-doc-view-body .project-ntp-light { font-weight: 400; }
    .rsat-doc-view-body .project-ntp-meta, .rsat-doc-view-body .project-ntp-signatures { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .rsat-doc-view-body .project-ntp-meta td { border: 1px solid #000; padding: 8px 10px; vertical-align: top; font-family: Georgia, "Times New Roman", serif; font-size: 11pt; font-weight: 700; }
    .rsat-doc-view-body .project-ntp-copy { margin-top: 22px; font-size: 11pt; line-height: 1.35; }
    .rsat-doc-view-body .project-ntp-copy p { margin: 0 0 18px; text-align: justify; }
    .rsat-doc-view-body .project-ntp-signatures { margin-top: 34px; }
    .rsat-doc-view-body .project-ntp-signatures td { border: 1px solid #000; padding: 8px 10px; vertical-align: top; }
    .rsat-doc-view-body .project-ntp-sign-head { font-family: Georgia, "Times New Roman", serif; font-size: 12pt; font-weight: 700; }
    .rsat-doc-view-body .project-ntp-sign-box { height: 96px; text-align: center; vertical-align: middle; font-family: Georgia, "Times New Roman", serif; font-size: 11pt; font-weight: 700; }
    @media (max-width: 1024px) {
        .rsat-work-grid { grid-template-columns: 1fr; }
        .rsat-quick-grid { grid-template-columns: 1fr; }
        .rsat-meta-grid,
        .rsat-approval-grid,
        .rsat-footer-grid {
            grid-template-columns: 1fr;
        }
        .rsat-client-row {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 820px) {
        .rsat-form {
            padding: 20px 18px 24px;
        }
    }
    /* ==========================================================================
       RSAT FORM TAB - FORMAL BUSINESS REPORT STYLING ONLY
       (Strictly scoped to [data-tab-panel="rsat"] and .rsat-form so all other tabs remain untouched)
       ========================================================================== */

    /* 1. Internal Document Tables & Controls - Sharp 90-Degree Corners */
    [data-tab-panel="rsat"] table,
    [data-tab-panel="rsat"] table th,
    [data-tab-panel="rsat"] table td,
    [data-tab-panel="rsat"] input,
    [data-tab-panel="rsat"] select,
    [data-tab-panel="rsat"] textarea {
        border-radius: 0 !important;
    }

    /* 2. Dark Navy Banners (#102d79) strictly inside RSAT Form Tab */
    [data-tab-panel="rsat"] .rsat-summary-banner,
    [data-tab-panel="rsat"] .rsat-section-title,
    .rsat-form .rsat-summary-banner,
    .rsat-form .rsat-section-title {
        background: #102d79 !important;
        color: #ffffff !important;
        padding: 10px 16px !important;
        font-family: Georgia, "Times New Roman", serif !important;
        font-size: 1.05rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.08em !important;
        text-transform: uppercase !important;
        border-radius: 0 !important;
        text-align: center !important;
        box-shadow: none !important;
    }

    /* 3. RSAT Form Activity Tables - Sharp 1px Grid Lines & Navy Header */
    [data-tab-panel="rsat"] table,
    .rsat-form table {
        width: 100% !important;
        border-collapse: collapse !important;
        border-radius: 0 !important;
        font-family: Georgia, "Times New Roman", serif !important;
    }
    [data-tab-panel="rsat"] table th,
    .rsat-form table th {
        background: #102d79 !important;
        color: #ffffff !important;
        font-weight: 800 !important;
        font-size: 0.72rem !important;
        letter-spacing: 0.05em !important;
        text-transform: uppercase !important;
        border: 1px solid #102d79 !important;
        border-right: 1px solid rgba(255, 255, 255, 0.2) !important;
        border-radius: 0 !important;
        padding: 9px 8px !important;
    }
    [data-tab-panel="rsat"] table td,
    .rsat-form table td {
        border: 1px solid #cbd5e1 !important;
        border-radius: 0 !important;
        padding: 8px !important;
        font-size: 0.78rem !important;
    }

    /* 4. RSAT Form Summary Cards - Bordered Square Boxes */
    [data-tab-panel="rsat"] .rsat-summary-card,
    .rsat-form .rsat-summary-card {
        border: 1px solid #cbd5e1 !important;
        border-radius: 0 !important;
        background: #ffffff !important;
        padding: 12px 16px !important;
        box-shadow: none !important;
    }
    [data-tab-panel="rsat"] .rsat-summary-card .label,
    .rsat-form .rsat-summary-card .label {
        font-size: 0.65rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.06em !important;
        color: #64748b !important;
        text-transform: uppercase !important;
        font-family: system-ui, -apple-system, sans-serif !important;
    }
    [data-tab-panel="rsat"] .rsat-summary-card .val,
    .rsat-form .rsat-summary-card .val {
        font-size: 1.5rem !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        margin-top: 4px !important;
        font-family: Georgia, serif !important;
    }

    /* 5. Inputs & Select Controls strictly inside RSAT Form Tab */
    [data-tab-panel="rsat"] input[type="text"],
    [data-tab-panel="rsat"] input[type="date"],
    [data-tab-panel="rsat"] select,
    [data-tab-panel="rsat"] textarea,
    .rsat-form input[type="text"],
    .rsat-form input[type="date"],
    .rsat-form select,
    .rsat-form textarea {
        border-radius: 0 !important;
    }
</style>

<div class="rsat-workspace p-6">
    <div class="w-full space-y-3">
        <!-- BREADCRUMB -->
        <div class="workspace-breadcrumb">
            &larr; <a href="{{ route('regular.index') }}">Regular</a> &nbsp;/&nbsp;
            <strong>{{ $regular->project_code }}</strong>
        </div>

        <!-- WORKSPACE HEAD -->
        <section class="workspace-head">
            <div class="workspace-top">
                <div>
                    <div class="eyebrow">{{ in_array($tab, ['attachments', 'history'], true) ? 'REGULAR RECORD' : 'REGULAR WORKSPACE' }}</div>
                    <div class="workspace-title">{{ $tab === 'attachments' ? 'Documents & Attachments' : ($tab === 'history' ? 'History & Audit Trail' : ($regular->name ?: ($regular->deal?->deal_title ?: 'Regular Retainer'))) }}</div>
                    <div class="refline">
                        @if ($tab === 'attachments')
                            Historical register of generated documents and uploaded files
                        @elseif ($tab === 'history')
                            Complete chronological record of regular engagement activity
                        @else
                            {{ $regular->project_code }} &middot; {{ $regular->deal?->deal_code ?? 'No linked deal' }} &middot; {{ $rsatAttachments['service_memo_ref'] ?? ('SM-' . $regular->project_code) }}
                        @endif
                    </div>
                </div>
                <div class="chips">
                    @if (in_array($tab, ['attachments', 'history'], true))
                        <span class="chip">Regular <strong>{{ $regular->project_code }}</strong></span>
                        <span class="chip">Business <strong>{{ $regular->business_name ?: ($regular->company?->company_name ?: '-') }}</strong></span>
                    @else
                        <span class="chip">Business <strong>{{ $regular->business_name ?: ($regular->company?->company_name ?: '-') }}</strong></span>
                        <span class="chip">Client <strong>{{ $contactName }}</strong></span>
                        <span class="chip">Planned Start <strong>{{ $fmt($regular->planned_start_date) }}</strong></span>
                        <span class="chip">Target Completion <strong>{{ $fmt($regular->target_completion_date) }}</strong></span>
                    @endif
                </div>
            </div>
            <div class="primary-tabs">
                <a class="ptab {{ $tab === 'dashboard' ? 'active' : '' }}" href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'dashboard']) }}">REGULAR DASHBOARD</a>
                <a class="ptab {{ $tab === 'work-order' ? 'active' : '' }}" href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'work-order']) }}">WORK ORDER</a>
                <a class="ptab {{ $tab === 'rsat' ? 'active' : '' }}" href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'rsat']) }}">RSAT</a>
                <a class="ptab {{ $tab === 'review' ? 'active' : '' }}" href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'review']) }}">REVIEW</a>
                <a class="ptab {{ $tab === 'ntp' ? 'active' : '' }}" href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'ntp']) }}">NTP</a>
                <a class="ptab {{ $tab === 'execution' ? 'active' : '' }}" href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'execution']) }}">EXECUTION</a>
                <a class="ptab {{ $tab === 'report' ? 'active' : '' }}" href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'report']) }}">RSAT REPORT</a>
                <a class="ptab {{ $tab === 'delivery' ? 'active' : '' }}" href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'delivery']) }}">DELIVERY &amp; COMPLETION</a>
                <a class="ptab {{ $tab === 'attachments' ? 'active' : '' }}" href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'attachments']) }}">ATTACHMENT</a>
                <a class="ptab {{ $tab === 'history' ? 'active' : '' }}" href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'history']) }}">HISTORY</a>
            </div>
        </section>

        @php
            $stageNames = ['Work Order', 'Plan', 'Review', 'NTP', 'Execution'];

            $phaseKey = strtolower($regular->current_phase ?: $regular->status);
            $stageIdxFromStatus = match($phaseKey) {
                'work order', 'intake', 'start' => 0,
                'plan', 'rsat', 'sow', 'preparation' => 1,
                'review', 'internal review' => 2,
                'ntp', 'for ntp approval' => 3,
                'execution', 'in progress' => 4,
                'completed', 'completion' => 4,
                default => null
            };

            if ($stageIdxFromStatus !== null) {
                $currentStageIdx = $stageIdxFromStatus;
            } else {
                $currentStageIdx = match($tab) {
                    'work-order' => 0,
                    'rsat' => 1,
                    'review' => 2,
                    'ntp' => 3,
                    'execution', 'report', 'delivery' => 4,
                    default => ($progressPct >= 100 ? 4 : ($progressPct > 0 ? 4 : ($ntpApproved ? 3 : ($ntpRecord ? 3 : ($rsat?->approved_at ? 2 : 1)))))
                };
            }

            $selectedTabIdx = match($tab) {
                'work-order' => 0,
                'rsat' => 1,
                'review' => 2,
                'ntp' => 3,
                'execution', 'report', 'delivery' => 4,
                default => null
            };
        @endphp

        @if ($tab !== 'attachments' && $tab !== 'history')
        <!-- MODERN REGULAR LIFECYCLE STEPPER -->
        <div class="lifecycle mb-4">
            <div class="lifecycle-main">
                <div class="life-title">
                    <strong>Regular Lifecycle</strong>
                    <span>Policy-controlled process</span>
                </div>
                <div class="life-track">
                    @foreach($stageNames as $i => $sname)
                        @php
                            $isDone = $i < $currentStageIdx;
                            $isCurrent = $i === $currentStageIdx;
                            $isSelected = $i === $selectedTabIdx;
                            $tabTarget = match($i) {
                                0 => 'work-order',
                                1 => 'rsat',
                                2 => 'review',
                                3 => 'ntp',
                                4 => 'execution',
                            };
                        @endphp
                        <div class="life-stage-col {{ $isCurrent ? 'current' : '' }} {{ $isSelected ? 'selected' : '' }}">
                            <div class="life-circle-row">
                                @if($i > 0)
                                    <div class="life-line-left {{ $i <= $currentStageIdx ? 'done' : '' }}"></div>
                                @else
                                    <div class="life-line-spacer"></div>
                                @endif

                                <a href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => $tabTarget]) }}" class="life-circle {{ $isDone ? 'done' : ($isCurrent ? 'current' : 'upcoming') }} {{ $isSelected ? 'ring-2 ring-blue-400' : '' }}">
                                    @if($isDone)
                                        <i class="fas fa-check"></i>
                                    @endif
                                </a>

                                @if($i < count($stageNames) - 1)
                                    <div class="life-line-right {{ $i < $currentStageIdx ? 'done' : '' }}"></div>
                                @else
                                    <div class="life-line-spacer"></div>
                                @endif
                            </div>
                            <a href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => $tabTarget]) }}" class="life-label {{ $isDone ? 'done' : ($isCurrent ? 'current' : 'upcoming') }} {{ $isSelected ? 'font-black underline' : '' }}">
                                {{ $sname }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="life-side">
                <div class="life-side-kicker">CURRENT STAGE</div>
                <div class="life-side-stage">{{ $stageNames[$currentStageIdx] ?? 'Execution' }}</div>
                <div class="life-side-pct">{{ $progressPct }}% Complete</div>
                <div class="life-side-health">
                    <span>Status</span>
                    <strong class="life-side-tag">On Track</strong>
                </div>
            </div>
        </div>
        @endif

        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif
        @if ($regularLocked)
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                This regular engagement is completed. Documents are view-only; editing, generating, and approval uploads are locked.
            </div>
        @endif

        <div class="rsat-linked-card rounded-2xl px-5 py-4 text-sm text-gray-600">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex flex-wrap gap-x-8 gap-y-2">
                    <p>
                        Deal:
                        @if ($regular->deal_id)
                            <a href="{{ route('deals.show', $regular->deal_id) }}" class="font-medium text-blue-700 hover:text-blue-800">{{ $regular->deal?->deal_code ?? 'View linked deal' }}</a>
                        @else
                            <span class="font-medium text-gray-500">No linked deal</span>
                        @endif
                    </p>
                    @if ($regular->company_id)
                        <p>Company: <a href="{{ route('company.show', $regular->company_id) }}" class="font-medium text-blue-700 hover:text-blue-800">{{ $regular->company?->company_name ?? 'View company' }}</a></p>
                    @endif
                    @if ($regular->contact_id)
                        <p>Contact: <a href="{{ route('contacts.show', $regular->contact_id) }}" class="font-medium text-blue-700 hover:text-blue-800">{{ $contactName }}</a></p>
                    @endif
                </div>
                <div class="rsat-linked-actions"></div>
            </div>
        </div>

        @if ($tab === 'dashboard')
            @include('regular.partials.dashboard-tab')
        @elseif ($tab === 'work-order')
            @include('regular.partials.work-order-tab')
        @elseif ($tab === 'review')
            @include('regular.partials.review-tab')
        @elseif ($tab === 'ntp')
            @include('regular.partials.ntp-tab')
        @elseif ($tab === 'execution')
            @include('regular.partials.execution-tab')
        @elseif ($tab === 'delivery')
            @include('regular.partials.delivery-tab')
        @elseif ($tab === 'attachments')
            @include('regular.partials.attachments-tab')
        @elseif ($tab === 'time-aht')
            @include('regular.partials.time-aht-tab')
        @elseif ($tab === 'client-actions')
            @include('regular.partials.client-actions-tab')
        @elseif ($tab === 'history')
            @include('regular.partials.history-tab')
        @elseif ($tab === 'report')
            @include('regular.partials.sow-report-tab')
        @elseif ($tab === 'rsat')
        <div class="rsat-work-grid" data-tab-panel="rsat">
            <aside class="w-60 flex-shrink-0 sticky top-6">
                <!-- STAGE MANAGEMENT -->
                <div class="rsat-tool-box">
                    <p class="rsat-tool-title">STAGE MANAGEMENT</p>
                    
                    <div class="space-y-3">
                        <div>
                            <p class="rsat-tool-label">STAGE STATUS</p>
                            <input type="text" value="{{ $regularLocked ? 'Locked' : 'In Planning' }}" readonly class="rsat-tool-input font-medium">
                        </div>

                        <div>
                            <p class="rsat-tool-label">ALIGNMENT REVIEW</p>
                            <div class="rounded border border-slate-200 bg-slate-50 p-2 text-xs space-y-1 text-slate-600">
                                <div class="flex justify-between">
                                    <span class="text-slate-400 text-[10px]">Reviewed</span>
                                    <span class="font-medium text-[11px]">Marlon Santos</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400 text-[10px]">Approved</span>
                                    <span class="font-medium text-[11px]">Kimber Saill Teo</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <p class="rsat-tool-label">PLANNING TIME</p>
                            <p class="font-mono text-sm font-bold text-slate-800">00:00:00</p>
                        </div>

                        <div>
                            <p class="rsat-tool-label">STAGE CONTROLS</p>
                            <button type="submit" form="regular-rsat-form" class="rsat-btn-tool font-semibold text-slate-700">
                                Complete Work Order First
                            </button>
                            <div class="mt-2 rounded border border-slate-200 bg-slate-50 p-2 text-[10px] leading-relaxed text-slate-500">
                                Stage activities locked during planning. Alignment approvals from lead/team leads required.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RSAT TOOLS -->
                <div class="rsat-tool-box">
                    <p class="rsat-tool-title">RSAT TOOLS</p>
                    <div class="space-y-2">
                        <button type="submit" form="regular-rsat-form" class="rsat-btn-tool">Service &amp; Activity Builder</button>
                        <button type="button" class="rsat-btn-tool text-left" data-add-row="regular-requirements">+ Add Service</button>
                        <a href="{{ route('regular.rsat.download', $regular) }}" class="rsat-btn-tool block text-center">Print RSAT</a>
                    </div>
                </div>
            </aside>

        <form id="regular-rsat-form" method="POST" action="{{ route('regular.rsat.update', $regular) }}" enctype="multipart/form-data" class="rsat-sheet min-w-0 overflow-hidden p-6" data-tab-panel="rsat">
            @csrf
            <fieldset {{ $regularLocked ? 'disabled' : '' }}>
            <input type="hidden" name="template_name" value="">
            <input type="hidden" name="status" value="{{ old('status', $rsat?->status ?? 'pending') }}">
            <input type="hidden" name="form_date" value="{{ $formDate }}">
            <input type="hidden" name="clearance_assigned_team_lead_signature" value="{{ old('clearance_assigned_team_lead_signature', $rsatClearance['assigned_team_lead_signature'] ?? '') }}">
            <input type="hidden" name="clearance_lead_consultant_signature" value="{{ old('clearance_lead_consultant_signature', $rsatClearance['lead_consultant_signature'] ?? '') }}">
            <input type="hidden" name="clearance_lead_associate_signature" value="{{ old('clearance_lead_associate_signature', $rsatClearance['lead_associate_signature'] ?? '') }}">
            <input type="hidden" name="clearance_sales_marketing_signature" value="{{ old('clearance_sales_marketing_signature', $rsatClearance['sales_marketing_signature'] ?? '') }}">
            <input type="hidden" name="clearance_record_custodian_signature" value="{{ old('clearance_record_custodian_signature', $rsatClearance['record_custodian_signature'] ?? '') }}">

            <div class="rsat-form">
                <!-- TOP REPORT HEADER -->
                <div class="rsat-top-header mb-4">
                    <div>
                        <div class="rsat-brand">John Kelly<br>&amp; Company</div>
                    </div>
                    <div class="rsat-doc-type-right">
                        <h1>RSAT REPORT</h1>
                        <span class="text-xs text-slate-500 font-sans">Live execution reporting record</span>
                    </div>
                </div>

                <!-- REGULAR INFORMATION SECTION -->
                <div class="mt-4 mb-6">
                    <h3 class="text-base font-bold font-serif text-[#102d79] mb-0.5">Regular Information</h3>
                    <p class="text-xs text-slate-500 mb-3 font-sans">Auto-filled from the Deal, START, and issued Service Memo.</p>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs border-collapse border border-slate-300">
                            <tbody>
                                <tr class="border-b border-slate-300">
                                    <td class="bg-slate-50 font-normal text-slate-600 px-3 py-2 border-r border-slate-300 w-1/4">NTP No. (All Notices to Proceed)</td>
                                    <td class="font-bold text-slate-900 px-3 py-2 border-r border-slate-300 w-1/4">{{ $ntpRecord?->ntp_number ?? '—' }}</td>
                                    <td class="bg-slate-50 font-normal text-slate-600 px-3 py-2 border-r border-slate-300 w-1/4">Engagement Proposal Agreement (EPA) No.</td>
                                    <td class="font-bold text-slate-900 px-3 py-2 w-1/4">{{ $rsatAttachments['epa_ref'] ?? ($regular->deal?->deal_code ? 'EPA-'.substr($regular->deal->deal_code, -8) : '—') }}</td>
                                </tr>
                                <tr class="border-b border-slate-300">
                                    <td class="bg-slate-50 font-normal text-slate-600 px-3 py-2 border-r border-slate-300">Work Order No.</td>
                                    <td class="font-bold text-slate-900 px-3 py-2 border-r border-slate-300">REG-WO-{{ substr($regular->project_code, -8) }}</td>
                                    <td class="bg-slate-50 font-normal text-slate-600 px-3 py-2 border-r border-slate-300">Regular Ref No.</td>
                                    <td class="font-bold text-slate-900 px-3 py-2">REG-{{ substr($regular->project_code, -8) }}</td>
                                </tr>
                                <tr class="border-b border-slate-300">
                                    <td class="bg-slate-50 font-normal text-slate-600 px-3 py-2 border-r border-slate-300">Source Service Memo</td>
                                    <td class="font-bold text-slate-900 px-3 py-2 border-r border-slate-300">{{ $rsatAttachments['service_memo_ref'] ?? ('SM-'.substr($regular->project_code, -8)) }}</td>
                                    <td class="bg-slate-50 font-normal text-slate-600 px-3 py-2 border-r border-slate-300">Source START</td>
                                    <td class="font-bold text-slate-900 px-3 py-2">START-{{ substr($regular->project_code, -8) }}</td>
                                </tr>
                                <tr class="border-b border-slate-300">
                                    <td class="bg-slate-50 font-normal text-slate-600 px-3 py-2 border-r border-slate-300">Source Deal</td>
                                    <td class="font-bold text-slate-900 px-3 py-2 border-r border-slate-300">{{ $regular->deal?->deal_code ?: '—' }}</td>
                                    <td class="bg-slate-50 font-normal text-slate-600 px-3 py-2 border-r border-slate-300">Client</td>
                                    <td class="font-bold text-slate-900 px-3 py-2">{{ $contactName ?: '—' }}</td>
                                </tr>
                                <tr class="border-b border-slate-300">
                                    <td class="bg-slate-50 font-normal text-slate-600 px-3 py-2 border-r border-slate-300">Business / Company</td>
                                    <td class="font-bold text-slate-900 px-3 py-2 border-r border-slate-300">{{ $regular->business_name ?: ($regular->company?->company_name ?: '—') }}</td>
                                    <td class="bg-slate-50 font-normal text-slate-600 px-3 py-2 border-r border-slate-300">Service / Regular</td>
                                    <td class="font-bold text-slate-900 px-3 py-2">{{ $regular->name ?: '—' }}</td>
                                </tr>
                                <tr class="border-b border-slate-300">
                                    <td class="bg-slate-50 font-normal text-slate-600 px-3 py-2 border-r border-slate-300">Service Area</td>
                                    <td class="font-bold text-slate-900 px-3 py-2 border-r border-slate-300">{{ $regular->service_area ?: '—' }}</td>
                                    <td class="bg-slate-50 font-normal text-slate-600 px-3 py-2 border-r border-slate-300">Engagement Type</td>
                                    <td class="font-bold text-slate-900 px-3 py-2">{{ $regular->engagement_type ?: 'Standard' }}</td>
                                </tr>
                                <tr>
                                    <td class="bg-slate-50 font-normal text-slate-600 px-3 py-2 border-r border-slate-300">Target Start Date</td>
                                    <td class="font-bold text-slate-900 px-3 py-2 border-r border-slate-300">{{ $regular->planned_start_date ? \Illuminate\Support\Carbon::parse($regular->planned_start_date)->format('M d, Y') : '—' }}</td>
                                    <td class="bg-slate-50 font-normal text-slate-600 px-3 py-2 border-r border-slate-300">Target Regular End Date</td>
                                    <td class="font-bold text-slate-900 px-3 py-2">{{ $regular->target_completion_date ? \Illuminate\Support\Carbon::parse($regular->target_completion_date)->format('M d, Y') : '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SECTION BANNER -->
                <div class="rsat-section-title text-center text-white bg-[#102d79] py-2 px-4 font-bold text-xs tracking-wider uppercase font-serif mb-4">
                    WITHIN SCOPE — EXECUTION STATUS &amp; CLIENT UPDATES
                </div>

                <div class="rsat-table-wrap">
                    <table class="rsat-table">
                        <thead>
                            <tr>
                                <th style="width: 4%;">ITEM</th>
                                <th style="width: 15%;">SERVICE</th>
                                <th style="width: 22%;">ACTIVITY / PURPOSE</th>
                                <th style="width: 16%;">FREQUENCY <i class="fas fa-info-circle text-[10px] opacity-75"></i></th>
                                <th style="width: 16%;">STANDARD LEAD TIME <i class="fas fa-info-circle text-[10px] opacity-75"></i></th>
                                <th style="width: 13%;">DELIVERY <i class="fas fa-info-circle text-[10px] opacity-75"></i></th>
                                <th style="width: 14%; text-align: center;">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="regular-requirements">
                            @php
                                $currentServiceKey = null;
                                $serviceIndexMap = [];
                                $currentServiceIdx = 0;
                                $activityIdx = 0;
                            @endphp
                            @foreach ($rsatRequirements as $index => $item)
                                @php
                                    $rawService = trim($item['purpose'] ?? '');
                                    $sKey = $rawService ?: '__default__';
                                    if (!isset($serviceIndexMap[$sKey])) {
                                        $currentServiceIdx++;
                                        $serviceIndexMap[$sKey] = $currentServiceIdx;
                                        $activityIdx = 1;
                                    } else {
                                        if ($currentServiceKey !== $sKey) {
                                            $activityIdx = 1;
                                        } else {
                                            $activityIdx++;
                                        }
                                    }
                                    $currentServiceKey = $sKey;
                                    $formattedItemNumber = $item['number'] ?? ($serviceIndexMap[$sKey] . '.' . $activityIdx);
                                @endphp
                                <tr class="rsat-matrix-row">
                                    <td class="rsat-index-col">
                                        <div class="rsat-index">{{ $formattedItemNumber }}</div>
                                    </td>
                                    <td>
                                        <div class="rsat-cell-card">
                                            <input name="engagement_purpose[]" value="{{ old('engagement_purpose.'.$index, $item['purpose'] ?? '') }}" class="rsat-cell-input" placeholder="Service name">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="rsat-cell-card">
                                            <input name="engagement_requirement[]" value="{{ old('engagement_requirement.'.$index, $item['requirement'] ?? '') }}" class="rsat-cell-input" placeholder="Activity / Purpose details">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="rsat-sublabel">Schedule 1</div>
                                        <select name="engagement_notes[]" class="rsat-select-box">
                                            <option value="Monthly" @selected(old('engagement_notes.'.$index, $item['notes'] ?? '') === 'Monthly')>Monthly</option>
                                            <option value="Quarterly" @selected(old('engagement_notes.'.$index, $item['notes'] ?? '') === 'Quarterly')>Quarterly</option>
                                            <option value="Annual" @selected(old('engagement_notes.'.$index, $item['notes'] ?? '') === 'Annual')>Annual</option>
                                            <option value="Semi-Annual" @selected(old('engagement_notes.'.$index, $item['notes'] ?? '') === 'Semi-Annual')>Semi-Annual</option>
                                            <option value="One-Time" @selected(old('engagement_notes.'.$index, $item['notes'] ?? '') === 'One-Time')>One-Time</option>
                                        </select>
                                        <div class="rsat-subnote">{{ old('engagement_notes.'.$index, $item['notes'] ?? 'Monthly') }}</div>
                                        <div class="border-t border-dashed border-slate-200 my-1"></div>
                                        <span class="rsat-dash-link">+ Configure schedule</span>
                                    </td>
                                    <td>
                                        <div class="rsat-sublabel">Notice lead time · Schedule 1</div>
                                        <select name="engagement_timeline[]" class="rsat-select-box">
                                            <option value="7 calendar days before" @selected(old('engagement_timeline.'.$index, $item['timeline'] ?? '') === '7 calendar days before')>7 calendar days before</option>
                                            <option value="14 calendar days before" @selected(old('engagement_timeline.'.$index, $item['timeline'] ?? '') === '14 calendar days before')>14 calendar days before</option>
                                            <option value="30 calendar days before" @selected(old('engagement_timeline.'.$index, $item['timeline'] ?? '') === '30 calendar days before')>30 calendar days before</option>
                                            <option value="Immediate" @selected(old('engagement_timeline.'.$index, $item['timeline'] ?? '') === 'Immediate')>Immediate</option>
                                        </select>
                                        <div class="rsat-subnote">7 calendar days buffer</div>
                                    </td>
                                    <td>
                                        <div class="rsat-sublabel">Schedule 1</div>
                                        <input type="date" name="engagement_submitted_to[]" value="{{ $dateInput(old('engagement_submitted_to.'.$index, $item['submitted_to'] ?? '')) }}" class="rsat-select-box">
                                        <div class="rsat-subnote">Configure execution date schedule</div>
                                    </td>
                                    <td>
                                        @if (! $regularLocked)
                                        <div class="rsat-action-flex">
                                            <button type="button" class="rsat-btn-square" data-add-row-below title="Add Row Below">+</button>
                                            <button type="button" class="rsat-btn-square" data-duplicate-row title="Duplicate Row"><i class="far fa-clone text-[11px]"></i></button>
                                            <button type="button" class="rsat-btn-square" data-move-up title="Move Up"><i class="fas fa-arrow-up text-[10px]"></i></button>
                                            <button type="button" class="rsat-btn-square" data-move-down title="Move Down"><i class="fas fa-arrow-down text-[10px]"></i></button>
                                            <button type="button" class="rsat-btn-square delete" data-delete-row title="Delete">&times;</button>
                                        </div>
                                        @endif
                                    </td>
                                    <input type="hidden" name="engagement_status[]" value="{{ old('engagement_status.'.$index, $item['status'] ?? 'open') }}">
                                    <input type="hidden" name="engagement_provided_by[]" value="{{ old('engagement_provided_by.'.$index, $item['provided_by'] ?? '') }}">
                                    <input type="hidden" name="engagement_assigned_to[]" value="{{ old('engagement_assigned_to.'.$index, $item['assigned_to'] ?? '') }}">
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if (! $regularLocked)
                <div class="mt-4 flex justify-end">
                    <button type="button" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" data-add-row="regular-requirements">+ Add RSAT Row</button>
                </div>
                @endif

                <div class="rsat-summary-banner">RSAT REPORT SUMMARY</div>
                <div class="rsat-summary-grid">
                    <div class="rsat-summary-card">
                        <div class="label">SERVICES</div>
                        <div class="val">{{ count(array_filter(array_column($rsatRequirements->toArray(), 'purpose'))) ?: count($rsatRequirements) }}</div>
                    </div>
                    <div class="rsat-summary-card">
                        <div class="label">ACTIVITIES</div>
                        <div class="val">{{ count($rsatRequirements) }}</div>
                    </div>
                    <div class="rsat-summary-card">
                        <div class="label">RECURRING SCHEDULES</div>
                        <div class="val">{{ count($rsatRequirements) }}</div>
                    </div>
                </div>

                <div class="rsat-footer-note-bar">
                    <div>{{ count($rsatRequirements) }} activities · {{ count($rsatRequirements) }} schedule(s)</div>
                    <div>Read only · approval controls apply</div>
                </div>

                <!-- Hidden inputs for backend form processing -->
                <input type="hidden" name="clearance_assigned_team_lead" value="{{ $approvalPreparedBy }}">
                <input type="hidden" name="clearance_lead_consultant_confirmed" value="{{ $approvalReviewedBy }}">
                <input type="hidden" name="rejection_reason" value="{{ $approvalReferredBy }}">
                <input type="hidden" name="clearance_sales_marketing" value="{{ $approvalSalesMarketing }}">
                <input type="hidden" name="approval_responsible_person[]" value="{{ $approvalLeadConsultant }}">
                <input type="hidden" name="clearance_lead_associate_assigned" value="{{ $approvalLeadAssociate }}">
                <input type="hidden" name="approval_responsible_person[]" value="{{ $approvalFinance }}">
                <input type="hidden" name="approval_name_and_signature[]" value="{{ $approvalPresident }}">
                <input type="hidden" name="clearance_record_custodian_name" value="{{ $recordCustodian }}">
                <input type="hidden" name="clearance_date_recorded" value="{{ $recordedDate }}">
                <input type="hidden" name="clearance_date_signed" value="{{ $signedDate }}">
                <input type="hidden" name="approval_requirement[]" value="{{ old('approval_requirement.0', 'Lead Consultant') }}">
                <input type="hidden" name="approval_requirement[]" value="{{ old('approval_requirement.1', 'Finance') }}">
                <input type="hidden" name="approval_name_and_signature[]" value="{{ old('approval_name_and_signature.1', '') }}">
                <input type="hidden" name="approval_date_time_done[]" value="{{ old('approval_date_time_done.0', '') }}">
                <input type="hidden" name="approval_date_time_done[]" value="{{ old('approval_date_time_done.1', '') }}">
            </div>

            </fieldset>
        </form>
        </div>
        @endif

    </div>
</div>

@if ($tab === 'rsat' && $ntpRecord)
<div id="regularNtpModal" class="rsat-ntp-modal" aria-hidden="true">
    <button id="regularNtpOverlay" type="button" class="rsat-ntp-overlay" aria-label="Close NTP view"></button>
    <div class="rsat-ntp-frame">
        <div class="rsat-ntp-shell">
            <div class="rsat-doc-view-shell">
                <div class="rsat-doc-view-header">
                    <div>
                        <p class="rsat-doc-view-eyebrow">Regular Document Viewer</p>
                        <h2 class="rsat-doc-view-title">Notice to Proceed</h2>
                        <p class="rsat-doc-view-copy">Review the NTP in the same branded viewer used across the regular workspace. The original document structure is preserved.</p>
                    </div>
                    <div class="rsat-doc-view-actions">
                        <button id="regularNtpClose" type="button" class="rsat-doc-view-action">Close View</button>
                    </div>
                </div>
                <div class="rsat-doc-view-body">
                    @include('project.partials.approved-ntp-document', ['ntp' => $ntpRecord->payload ?? [], 'ntpRecord' => $ntpRecord, 'contactName' => $contactName])
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div id="regularRsatAutoSettingsModal" class="rsat-settings-modal" aria-hidden="true">
    <button id="regularRsatAutoSettingsOverlay" type="button" class="rsat-settings-overlay" aria-label="Close auto-report settings"></button>
    <div class="rsat-settings-frame">
        <div class="rsat-settings-card p-5">
            <h3 class="text-lg font-semibold text-slate-900">RSAT Auto-Generate Report</h3>
            <p class="mt-1 text-sm text-slate-500">Set a monthly day when RSAT reports auto-generate.</p>
            <form method="POST" action="{{ route('regular.rsat.auto-settings', $regular) }}" class="mt-4 space-y-4">
                @csrf
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="enabled" value="0">
                    <input type="checkbox" name="enabled" value="1" class="h-4 w-4" {{ !empty($rsatAutoReportSettings['enabled']) ? 'checked' : '' }}>
                    Enable monthly auto-generation
                </label>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Day of Month</label>
                    <input type="number" min="1" max="31" name="day_of_month" value="{{ (int) ($rsatAutoReportSettings['day_of_month'] ?? 30) }}" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm">
                </div>
                <p class="text-xs text-slate-500">If a month has fewer days, it runs on the last day of that month.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" id="regularRsatAutoSettingsClose" class="rsat-doc-action">Cancel</button>
                    <button type="submit" class="rsat-doc-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if (! $regularLocked)
<div id="regularReportDeleteModal" class="fixed inset-0 z-[70] hidden" aria-hidden="true">
    <button id="regularReportDeleteOverlay" type="button" aria-label="Close delete reports modal" class="absolute inset-0 bg-slate-900/45"></button>
    <div class="absolute inset-0 flex items-center justify-center px-4">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
            <div class="border-b border-slate-100 px-6 py-5">
                <h2 class="text-xl font-semibold text-slate-900">Delete Selected RSAT Reports</h2>
                <p class="mt-1 text-sm text-slate-500">This action will permanently delete the selected report records.</p>
            </div>
            <form id="regularReportBulkDeleteForm" method="POST" action="{{ route('regular.report.bulk-delete', $regular) }}">
                @csrf
                @method('DELETE')
                <div id="regularReportDeleteSelectedInputs"></div>
                <div class="px-6 py-5 text-sm text-slate-700">
                    Are you sure you want to delete <span id="regularReportDeleteCountText" class="font-semibold text-slate-900">0 reports</span>?
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button id="regularReportCancelDeleteModal" type="button" class="h-10 rounded-lg border border-slate-300 px-4 text-sm text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="h-10 rounded-lg bg-red-600 px-5 text-sm font-medium text-white hover:bg-red-700">Delete Selected</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<template id="regular-requirement-row-template">
    <tr class="rsat-matrix-row">
        <td class="rsat-index-col">
            <div class="rsat-index"></div>
        </td>
        <td>
            <div class="rsat-cell-card">
                <input name="engagement_purpose[]" class="rsat-cell-input" placeholder="Service name">
            </div>
        </td>
        <td>
            <div class="rsat-cell-card">
                <input name="engagement_requirement[]" class="rsat-cell-input" placeholder="Activity / Purpose details">
            </div>
        </td>
        <td>
            <div class="rsat-sublabel">Schedule 1</div>
            <select name="engagement_notes[]" class="rsat-select-box">
                <option value="Monthly" selected>Monthly</option>
                <option value="Quarterly">Quarterly</option>
                <option value="Annual">Annual</option>
                <option value="Semi-Annual">Semi-Annual</option>
                <option value="One-Time">One-Time</option>
            </select>
            <div class="rsat-subnote">Monthly</div>
            <div class="border-t border-dashed border-slate-200 my-1"></div>
            <span class="rsat-dash-link">+ Configure schedule</span>
        </td>
        <td>
            <div class="rsat-sublabel">Notice lead time · Schedule 1</div>
            <select name="engagement_timeline[]" class="rsat-select-box">
                <option value="7 calendar days before" selected>7 calendar days before</option>
                <option value="14 calendar days before">14 calendar days before</option>
                <option value="30 calendar days before">30 calendar days before</option>
                <option value="Immediate">Immediate</option>
            </select>
            <div class="rsat-subnote">7 calendar days buffer</div>
        </td>
        <td>
            <div class="rsat-sublabel">Schedule 1</div>
            <input type="date" name="engagement_submitted_to[]" class="rsat-select-box">
            <div class="rsat-subnote">Configure execution date schedule</div>
        </td>
        <td>
            <div class="rsat-action-flex">
                <button type="button" class="rsat-btn-square" data-add-row-below title="Add Row Below">+</button>
                <button type="button" class="rsat-btn-square" data-duplicate-row title="Duplicate Row"><i class="far fa-clone text-[11px]"></i></button>
                <button type="button" class="rsat-btn-square" data-move-up title="Move Up"><i class="fas fa-arrow-up text-[10px]"></i></button>
                <button type="button" class="rsat-btn-square" data-move-down title="Move Down"><i class="fas fa-arrow-down text-[10px]"></i></button>
                <button type="button" class="rsat-btn-square delete" data-delete-row title="Delete">&times;</button>
            </div>
        </td>
        <input type="hidden" name="engagement_status[]" value="open">
        <input type="hidden" name="engagement_provided_by[]" value="">
        <input type="hidden" name="engagement_assigned_to[]" value="">
    </tr>
</template>

<template id="rsat-attachment-input-template">
    <div class="flex items-center gap-3" data-attachment-input-row>
        <input
            type="file"
            name="attachments[]"
            accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt"
            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700"
        >
        <button type="button" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700 hover:bg-rose-100" data-remove-attachment-input>
            Remove
        </button>
    </div>
</template>

<!-- ADVANCE CYCLE MODAL -->
<div id="advance-cycle-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm">
    <div class="relative w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Advance Operational Cycle</h3>
                <p class="text-xs text-slate-500">Archive Cycle {{ $cycleState['cycle_number'] ?? 1 }} and start the next period</p>
            </div>
            <button type="button" onclick="document.getElementById('advance-cycle-modal').classList.add('hidden')" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form action="{{ route('regular.cycle.advance', $regular->id) }}" method="POST" class="mt-5 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Recurrence Frequency</label>
                <select name="recurrence" class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-medium text-slate-800 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                    <option value="Monthly" @selected(($cycleState['recurrence'] ?? 'Monthly') === 'Monthly')>Monthly</option>
                    <option value="Quarterly" @selected(($cycleState['recurrence'] ?? '') === 'Quarterly')>Quarterly</option>
                    <option value="Semi-Annual" @selected(($cycleState['recurrence'] ?? '') === 'Semi-Annual')>Semi-Annual</option>
                    <option value="Annual" @selected(($cycleState['recurrence'] ?? '') === 'Annual')>Annual</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Target Next Period Label (Optional override)</label>
                <input type="text" name="next_period" placeholder="e.g. November 2026 or Q4 2026" class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                <p class="mt-1 text-[11px] text-slate-500">Leave blank to automatically calculate based on selected frequency.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Cycle Completion Notes</label>
                <textarea name="notes" rows="2" placeholder="Summary notes for this cycle archive..." class="mt-1.5 block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600"></textarea>
            </div>

            <div class="space-y-2 rounded-xl bg-slate-50 p-3.5 text-xs text-slate-700">
                <label class="flex items-center gap-2 font-medium">
                    <input type="checkbox" name="reset_approvals" value="1" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>Reset RSAT internal approvals for the new cycle period</span>
                </label>
                <label class="flex items-center gap-2 font-medium">
                    <input type="checkbox" name="reset_all_requirements" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>Re-open completed tasks as recurring action items</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('advance-cycle-modal').classList.add('hidden')" class="rounded-xl border border-slate-300 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-700 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white hover:bg-blue-800 shadow-md transition">
                    <i class="fas fa-check"></i> Advance to Cycle {{ ($cycleState['cycle_number'] ?? 1) + 1 }}
                </button>
            </div>
        </form>
    </div>
</div>

<!-- CYCLE HISTORY MODAL -->
<div id="cycle-history-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm">
    <div class="relative w-full max-w-3xl max-h-[85vh] flex flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 p-5">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Operational Cycle Archives</h3>
                <p class="text-xs text-slate-500">Historical snapshots and records for {{ $regular->project_code }}</p>
            </div>
            <button type="button" onclick="document.getElementById('cycle-history-modal').classList.add('hidden')" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-5 space-y-4">
            @forelse(($cycleState['history'] ?? []) as $hist)
                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 transition hover:bg-slate-50">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/80 pb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="inline-flex items-center rounded-lg bg-blue-100 px-2.5 py-1 text-xs font-extrabold text-blue-900">
                                Cycle {{ $hist['cycle_number'] ?? '-' }}
                            </span>
                            <span class="font-bold text-sm text-slate-900">{{ $hist['period'] ?? '-' }}</span>
                            <span class="text-xs text-slate-500">({{ $hist['recurrence'] ?? 'Monthly' }})</span>
                        </div>
                        <span class="text-xs text-slate-500">
                            Completed {{ !empty($hist['completed_at']) ? \Carbon\Carbon::parse($hist['completed_at'])->format('M d, Y h:i A') : '-' }} by {{ $hist['completed_by'] ?? 'Operator' }}
                        </span>
                    </div>

                    @if(!empty($hist['notes']))
                        <p class="mt-2.5 text-xs italic text-slate-600 bg-white rounded-lg border border-slate-100 p-2.5">
                            "{{ $hist['notes'] }}"
                        </p>
                    @endif

                    <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                        <div class="rounded-lg bg-white p-2.5 border border-slate-100">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">RSAT Status</span>
                            <span class="font-semibold text-slate-700 capitalize">{{ $hist['rsat_snapshot']['status'] ?? 'Completed' }}</span>
                        </div>
                        <div class="rounded-lg bg-white p-2.5 border border-slate-100">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">NTP Status</span>
                            <span class="font-semibold text-slate-700 capitalize">{{ $hist['ntp_snapshot']['status'] ?? 'Approved' }}</span>
                        </div>
                        <div class="rounded-lg bg-white p-2.5 border border-slate-100">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Transmittal Ref</span>
                            <span class="font-semibold text-slate-700">{{ $hist['transmittal_ref'] ?? 'N/A' }}</span>
                        </div>
                        <div class="rounded-lg bg-white p-2.5 border border-slate-100">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Reports Filed</span>
                            <span class="font-semibold text-slate-700">{{ $hist['reports_count'] ?? 0 }} Report(s)</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-slate-400 text-sm">
                    <i class="fas fa-history text-3xl mb-2 block text-slate-300"></i>
                    No archived cycles yet. Cycle 1 is currently active.
                </div>
            @endforelse
        </div>

        <div class="border-t border-slate-100 p-4 flex justify-end">
            <button type="button" onclick="document.getElementById('cycle-history-modal').classList.add('hidden')" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                Close
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const requirementsContainer = document.getElementById('regular-requirements');
    const rowTemplate = document.getElementById('regular-requirement-row-template');
    const attachmentInputsContainer = document.getElementById('rsat-attachments-inputs');
    const attachmentInputTemplate = document.getElementById('rsat-attachment-input-template');
    const addAttachmentInputButton = document.getElementById('add-rsat-attachment-input');
    const tabButtons = Array.from(document.querySelectorAll('[data-tab-button]'));
    const tabPanels = Array.from(document.querySelectorAll('[data-tab-panel]'));
    const autoSettingsOpen = document.getElementById('regularRsatAutoSettingsOpen');
    const autoSettingsModal = document.getElementById('regularRsatAutoSettingsModal');
    const autoSettingsOverlay = document.getElementById('regularRsatAutoSettingsOverlay');
    const autoSettingsClose = document.getElementById('regularRsatAutoSettingsClose');
    const regularNtpAction = document.getElementById('regularNtpAction');
    const regularNtpModal = document.getElementById('regularNtpModal');
    const regularNtpOverlay = document.getElementById('regularNtpOverlay');
    const regularNtpClose = document.getElementById('regularNtpClose');

    const syncRowNumbers = (container) => {
        if (!container) {
            return;
        }
        let currentServiceKey = null;
        const servicesMap = new Map();
        let serviceIndex = 0;
        let activityIndex = 0;

        Array.from(container.querySelectorAll('.rsat-matrix-row')).forEach((row) => {
            const serviceInput = row.querySelector('input[name="engagement_purpose[]"]');
            const rawService = serviceInput ? serviceInput.value.trim() : '';
            const serviceKey = rawService || '__default__';

            if (!servicesMap.has(serviceKey)) {
                serviceIndex++;
                servicesMap.set(serviceKey, serviceIndex);
                activityIndex = 1;
            } else {
                if (currentServiceKey !== serviceKey) {
                    activityIndex = 1;
                } else {
                    activityIndex++;
                }
            }
            currentServiceKey = serviceKey;

            const sIdx = servicesMap.get(serviceKey);
            const formattedItemNumber = `${sIdx}.${activityIndex}`;

            const indexCell = row.querySelector('.rsat-index');
            if (indexCell) {
                indexCell.textContent = formattedItemNumber;
            }
        });
    };

    requirementsContainer?.addEventListener('input', (event) => {
        if (event.target.matches('input[name="engagement_purpose[]"]')) {
            syncRowNumbers(requirementsContainer);
        }
    });

    document.querySelectorAll('[data-add-row]').forEach((button) => {
        button.addEventListener('click', () => {
            if (button.dataset.addRow === 'regular-requirements') {
                requirementsContainer.insertAdjacentHTML('beforeend', rowTemplate.innerHTML);
                syncRowNumbers(requirementsContainer);
                return;
            }

        });
    });

    requirementsContainer?.addEventListener('click', (event) => {
        const deleteBtn = event.target.closest('[data-delete-row]');
        if (deleteBtn) {
            const row = deleteBtn.closest('tr');
            if (row) {
                row.remove();
                syncRowNumbers(requirementsContainer);
            }
            return;
        }

        const moveUpBtn = event.target.closest('[data-move-up]');
        if (moveUpBtn) {
            const row = moveUpBtn.closest('tr');
            if (row && row.previousElementSibling) {
                row.parentNode.insertBefore(row, row.previousElementSibling);
                syncRowNumbers(requirementsContainer);
            }
            return;
        }

        const moveDownBtn = event.target.closest('[data-move-down]');
        if (moveDownBtn) {
            const row = moveDownBtn.closest('tr');
            if (row && row.nextElementSibling) {
                row.parentNode.insertBefore(row.nextElementSibling, row);
                syncRowNumbers(requirementsContainer);
            }
            return;
        }

        const addBelowBtn = event.target.closest('[data-add-row-below]');
        if (addBelowBtn) {
            const row = addBelowBtn.closest('tr');
            if (row) {
                row.insertAdjacentHTML('afterend', rowTemplate.innerHTML);
                syncRowNumbers(requirementsContainer);
            }
            return;
        }

        const duplicateBtn = event.target.closest('[data-duplicate-row]');
        if (duplicateBtn) {
            const row = duplicateBtn.closest('tr');
            if (row) {
                const clone = row.cloneNode(true);
                row.parentNode.insertBefore(clone, row.nextElementSibling);
                syncRowNumbers(requirementsContainer);
            }
            return;
        }
    });

    addAttachmentInputButton?.addEventListener('click', () => {
        attachmentInputsContainer?.insertAdjacentHTML('beforeend', attachmentInputTemplate.innerHTML);
    });

    attachmentInputsContainer?.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-remove-attachment-input]');
        if (!trigger) {
            return;
        }

        const row = trigger.closest('[data-attachment-input-row]');
        if (!row) {
            return;
        }

        const rows = attachmentInputsContainer.querySelectorAll('[data-attachment-input-row]');
        if (rows.length <= 1) {
            const input = row.querySelector('input[type="file"]');
            if (input) {
                input.value = '';
            }
            return;
        }

        row.remove();
    });

    const activateTab = (tabKey) => {
        tabButtons.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.tabButton === tabKey);
        });
        tabPanels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.dataset.tabPanel !== tabKey);
        });
    };

    const openAutoSettingsModal = () => {
        if (!autoSettingsModal) return;
        autoSettingsModal.classList.add('is-open');
        autoSettingsModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    };

    const closeAutoSettingsModal = () => {
        if (!autoSettingsModal) return;
        autoSettingsModal.classList.remove('is-open');
        autoSettingsModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
    };

    const openRegularNtpModal = () => {
        if (!regularNtpModal) return;
        regularNtpModal.classList.add('is-open');
        regularNtpModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    };

    const closeRegularNtpModal = () => {
        if (!regularNtpModal) return;
        regularNtpModal.classList.remove('is-open');
        regularNtpModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
    };

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => activateTab(button.dataset.tabButton));
    });
    autoSettingsOpen?.addEventListener('click', openAutoSettingsModal);
    autoSettingsOverlay?.addEventListener('click', closeAutoSettingsModal);
    autoSettingsClose?.addEventListener('click', closeAutoSettingsModal);
    regularNtpAction?.addEventListener('click', (event) => {
        if (regularNtpAction.textContent.trim() === 'View NTP' && regularNtpModal) {
            event.preventDefault();
            openRegularNtpModal();
        }
    });
    regularNtpOverlay?.addEventListener('click', closeRegularNtpModal);
    regularNtpClose?.addEventListener('click', closeRegularNtpModal);

    (() => {
        const searchInput = document.getElementById('regularReportSearch');
        const rows = Array.from(document.querySelectorAll('#regularReportTableBody tr[data-report-search]'));
        const makeTemplateButton = document.getElementById('regularMakeTemplateButton');
        const rsatForm = document.getElementById('regular-rsat-form');
        const selectAll = document.getElementById('regularReportSelectAll');
        const rowChecks = Array.from(document.querySelectorAll('.regular-report-row-checkbox'));
        const selectionBar = document.getElementById('regularReportSelectionBar');
        const selectedCount = document.getElementById('regularReportSelectedCount');
        const clearSelection = document.getElementById('regularReportClearSelection');
        const openDeleteModalButton = document.getElementById('regularReportOpenDeleteModal');
        const deleteModal = document.getElementById('regularReportDeleteModal');
        const deleteOverlay = document.getElementById('regularReportDeleteOverlay');
        const cancelDeleteModalButton = document.getElementById('regularReportCancelDeleteModal');
        const deleteSelectedInputs = document.getElementById('regularReportDeleteSelectedInputs');
        const deleteCountText = document.getElementById('regularReportDeleteCountText');

        if (searchInput && rows.length > 0) {
            searchInput.addEventListener('input', () => {
                const keyword = String(searchInput.value || '').trim().toLowerCase();

                rows.forEach((row) => {
                    const blob = String(row.dataset.reportSearch || '').toLowerCase();
                    row.classList.toggle('hidden', keyword !== '' && !blob.includes(keyword));
                });
            });
        }

        const syncSelectionUi = () => {
            const selected = rowChecks.filter((item) => item.checked);

            if (selectionBar) {
                selectionBar.classList.toggle('hidden', selected.length === 0);
            }

            if (selectedCount) {
                selectedCount.textContent = String(selected.length);
            }

            if (selectAll) {
                selectAll.checked = rowChecks.length > 0 && selected.length === rowChecks.length;
                selectAll.indeterminate = selected.length > 0 && selected.length < rowChecks.length;
            }
        };

        const closeDeleteModal = () => {
            if (!deleteModal) {
                return;
            }

            deleteModal.classList.add('hidden');
            deleteModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
        };

        const openDeleteModal = () => {
            const selected = rowChecks.filter((item) => item.checked);

            if (selected.length === 0 || !deleteModal) {
                return;
            }

            if (deleteSelectedInputs) {
                deleteSelectedInputs.innerHTML = selected
                    .map((item) => `<input type="hidden" name="selected_reports[]" value="${item.value}">`)
                    .join('');
            }

            if (deleteCountText) {
                deleteCountText.textContent = `${selected.length} ${selected.length === 1 ? 'report' : 'reports'}`;
            }

            deleteModal.classList.remove('hidden');
            deleteModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
        };

        selectAll?.addEventListener('change', () => {
            rowChecks.forEach((item) => {
                item.checked = selectAll.checked;
            });
            syncSelectionUi();
        });

        rowChecks.forEach((item) => {
            item.addEventListener('change', syncSelectionUi);
        });

        clearSelection?.addEventListener('click', () => {
            rowChecks.forEach((item) => {
                item.checked = false;
            });
            if (selectAll) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
            syncSelectionUi();
        });

        openDeleteModalButton?.addEventListener('click', openDeleteModal);
        deleteOverlay?.addEventListener('click', closeDeleteModal);
        cancelDeleteModalButton?.addEventListener('click', closeDeleteModal);

        makeTemplateButton?.addEventListener('click', () => {
            if (!rsatForm) {
                return;
            }

            const templateName = window.prompt('Template name');
            if (!templateName || templateName.trim() === '') {
                return;
            }

            const templateInput = rsatForm.querySelector('input[name="template_name"]');

            if (templateInput) {
                templateInput.value = templateName.trim();
            }

            rsatForm.setAttribute('action', @json(route('regular.rsat.templates.store', $regular)));
            rsatForm.requestSubmit();
        });

        syncSelectionUi();
    })();

    syncRowNumbers(requirementsContainer);
    activateTab(@json($tab));
});
</script>
@endsection

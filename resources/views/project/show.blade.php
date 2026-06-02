@extends('layouts.app')
@section('title', $project->project_title ?: 'Project Details')

@section('content')
@php
    $fmt = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('M d, Y') : '-';
    $contactName = trim(collect([$project->contact?->first_name, $project->contact?->last_name])->filter()->implode(' ')) ?: ($project->client_name ?: '-');
    $tabs = ['sow' => 'Scope of Work', 'report' => 'SOW Report'];
    $sowWithin = collect($sow?->within_scope_items ?? [])->whenEmpty(fn () => collect([['main_task_description' => '', 'sub_task_description' => '', 'responsible' => '', 'duration' => '', 'start_date' => '', 'end_date' => '', 'status' => '', 'remarks' => '']]));
    $sowOut = collect($sow?->out_of_scope_items ?? [])->whenEmpty(fn () => collect([['main_task_description' => '', 'sub_task_description' => '', 'responsible' => '', 'duration' => '', 'start_date' => '', 'end_date' => '', 'status' => '', 'remarks' => '']]));
    $repWithin = collect($report?->within_scope_items ?? [])->whenEmpty(fn () => collect([['main_task_description' => '', 'sub_task_description' => '', 'responsible' => '', 'duration' => '', 'start_date' => '', 'end_date' => '', 'status' => '', 'remarks' => '']]));
    $repOut = collect($report?->out_of_scope_items ?? [])->whenEmpty(fn () => collect([['main_task_description' => '', 'sub_task_description' => '', 'responsible' => '', 'duration' => '', 'start_date' => '', 'end_date' => '', 'status' => '', 'remarks' => '']]));
    $sowApproval = (array) ($sow?->internal_approval ?? []);
    $repApproval = (array) ($report?->internal_approval ?? []);
    $repSummary = (array) ($report?->status_summary ?? []);
    $logoPath = asset('images/imaglogo.png');
    $ntpApproved = $ntpRecord?->client_response_status === 'approved_to_proceed' && $ntpRecord?->client_approved_at;
    $ntpStatusLabel = $ntpApproved
        ? 'Client approved NTP'
        : ($ntpRecord ? 'NTP generated, waiting for signed upload' : 'NTP not generated');
    $cocMeta = (array) data_get($project->metadata ?? [], 'coc', []);
    $cocApproved = ($project->status === 'Completed') || (($cocMeta['approval_status'] ?? null) === 'approved');
    $projectLocked = $projectLocked ?? ($project->status === 'Completed');
@endphp

<style>
    .project-workspace {
        background:
            radial-gradient(circle at top left, rgba(13, 70, 140, 0.08), transparent 28%),
            linear-gradient(180deg, #f2f6fc 0%, #fbfcfe 26%, #fbfcfe 100%);
    }
    .project-top-card {
        border: 1px solid #d8e1ee;
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.05);
    }
    .project-pill {
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
    .project-tab-link {
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
    .project-tab-link.active {
        border-color: #1c4587;
        background: #1c4587;
        color: #fff;
        box-shadow: 0 10px 22px rgba(28, 69, 135, 0.18);
    }
    .project-tab-link:hover {
        border-color: #9eb2cf;
        color: #1c4587;
    }
    .project-linked-card {
        border: 1px solid #d8e1ee;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.04);
    }
    .project-linked-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .project-work-grid { display: grid; gap: 20px; align-items: start; }
    .project-quick-actions { border: 1px solid #d8e1ee; background: rgba(255, 255, 255, 0.96); box-shadow: 0 14px 30px rgba(15, 23, 42, 0.04); }
    .project-quick-title { font-size: 0.78rem; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b; }
    .project-quick-grid { display: grid; gap: 12px; margin-top: 14px; }
    .project-quick-group { border: 1px solid #e2e8f0; border-radius: 16px; padding: 12px; background: #fff; }
    .project-quick-label { font-size: 0.72rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #94a3b8; }
    .project-quick-stack { display: grid; gap: 10px; margin-top: 10px; }
    .project-doc-shell { border: 1px solid #cbd5e1; background: #fff; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06); }
    .project-doc-topbar { height: 8px; background: #102d79; }
    .project-doc-header { display: grid; gap: 18px; padding: 18px 22px; border-bottom: 1px solid #dbe3f0; background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); }
    .project-doc-brand { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; }
    .project-doc-brand img { height: 46px; width: auto; object-fit: contain; }
    .project-doc-title { text-align: right; font-family: "Times New Roman", Georgia, serif; }
    .project-doc-title h2 { font-size: 2rem; line-height: 1.05; font-weight: 700; color: #0f172a; text-transform: uppercase; }
    .project-doc-title p { margin-top: 4px; font-size: 0.74rem; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b; font-family: Arial, sans-serif; }
    .project-doc-grid { display: grid; gap: 12px; grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .project-doc-meta { border: 1px solid #dbe3f0; background: #fff; padding: 8px 10px; min-height: 62px; }
    .project-doc-meta-label { display: block; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; }
    .project-doc-meta-value { display: block; margin-top: 8px; font-size: .98rem; font-weight: 600; color: #0f172a; }
    .project-doc-section { margin: 18px 24px 0; border: 1px solid #dbe3f0; background: #fff; }
    .project-doc-section-title { background: #102d79; color: #fff; padding: 10px 14px; font-size: 0.8rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
    .project-doc-section-body { padding: 18px; background: #fff; }
    .project-doc-table { min-width: 1100px; border-collapse: collapse; }
    .project-doc-table thead th { background: #eef4ff; color: #334155; font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 700; }
    .project-doc-table th, .project-doc-table td { border: 1px solid #dbe3f0; padding: 8px; vertical-align: top; }
    .project-doc-input, .project-doc-select, .project-doc-textarea { width: 100%; border: 1px solid #cbd5e1; background: #fff; padding: 9px 11px; font-size: 0.9rem; color: #0f172a; }
    .project-doc-input[readonly] { background: #f8fafc; color: #475569; }
    .project-doc-textarea { min-height: 96px; resize: vertical; }
    .project-doc-label { display: block; margin-bottom: 6px; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #475569; }
    .project-doc-total { margin-top: 10px; font-size: 0.78rem; font-weight: 700; color: #475569; text-transform: uppercase; }
    .project-doc-action { display: inline-flex; align-items: center; border: 1px solid #cbd5e1; background: #fff; padding: 9px 12px; font-size: 0.82rem; font-weight: 600; color: #334155; }
    .project-doc-action i { margin-right: 8px; }
    .project-doc-action-approved { border-color: #86efac; background: #dcfce7; color: #166534; }
    .project-doc-status-chip { display: inline-flex; align-items: center; gap: 8px; border-radius: 999px; border: 1px solid #dbe3f0; background: #fff; padding: 9px 14px; font-size: 0.78rem; font-weight: 700; color: #475569; }
    .project-doc-status-chip.approved { border-color: #86efac; background: #dcfce7; color: #166534; }
    .project-doc-primary { display: inline-flex; align-items: center; background: #21409a; color: #fff; padding: 10px 14px; font-size: 0.85rem; font-weight: 600; }
    .project-doc-summary-grid { display: grid; gap: 12px; grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .project-doc-summary-box { border: 1px solid #dbe3f0; background: #f8fbff; padding: 12px; }
    .project-doc-summary-box span { display: block; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; }
    .project-doc-summary-box strong { display: block; margin-top: 8px; font-size: 1.4rem; color: #0f172a; }
    .project-settings-trigger { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border: 1px solid #cbd5e1; background: #fff; color: #475569; cursor: pointer; }
    .project-settings-modal { position: fixed; inset: 0; z-index: 75; display: none; }
    .project-settings-modal.is-open { display: block; }
    .project-settings-overlay { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.5); }
    .project-settings-frame { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; padding: 16px; }
    .project-settings-card { width: 100%; max-width: 430px; background: #fff; border: 1px solid #dbe3f0; box-shadow: 0 18px 36px rgba(15, 23, 42, 0.2); }
    .project-ntp-modal { position: fixed; inset: 0; z-index: 70; display: none; }
    .project-ntp-modal.is-open { display: block; }
    .project-ntp-overlay { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.55); }
    .project-ntp-frame { position: absolute; inset: 0; overflow-y: auto; padding: 28px 16px; }
    .project-ntp-shell { position: relative; max-width: 1060px; margin: 0 auto; }
    .project-ntp-toolbar { display: flex; justify-content: flex-end; margin-bottom: 14px; }
    .project-ntp-close { display: inline-flex; align-items: center; justify-content: center; min-width: 120px; border: 1px solid #cbd5e1; background: #fff; padding: 10px 14px; font-size: .84rem; font-weight: 700; color: #0f172a; }
    .project-ntp-sheet { border: 1px solid #d8e1ee; background: #fff; box-shadow: 0 16px 34px rgba(15, 23, 42, 0.05); }
    .project-ntp-doc { padding: 32px 34px 36px; border: 2px solid #1c4587; }
    .project-doc-view-shell { border: 1px solid #d8e1ee; background: #fff; box-shadow: 0 16px 34px rgba(15, 23, 42, 0.05); overflow: hidden; }
    .project-doc-view-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; padding: 18px 22px; border-bottom: 1px solid #dbe3f0; background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%); }
    .project-doc-view-eyebrow { font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #64748b; }
    .project-doc-view-title { margin-top: 6px; font-size: 1.2rem; font-weight: 700; color: #0f172a; }
    .project-doc-view-copy { margin-top: 6px; max-width: 620px; font-size: .88rem; line-height: 1.45; color: #64748b; }
    .project-doc-view-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 10px; }
    .project-doc-view-action { display: inline-flex; align-items: center; justify-content: center; min-width: 132px; border: 1px solid #cbd5e1; background: #fff; padding: 10px 14px; font-size: .84rem; font-weight: 700; color: #0f172a; text-decoration: none; }
    .project-doc-view-action.primary { border-color: #1c4587; background: #1c4587; color: #fff; }
    .project-doc-view-body { max-height: calc(100vh - 210px); overflow-y: auto; padding: 24px; background: linear-gradient(180deg, #eef4ff 0%, #f8fbff 100%); }
    .project-doc-view-sheet { display: flex; justify-content: center; }
    .project-doc-view-paper { width: min(100%, 860px); border: 1px solid #d8e1ee; background: #fff; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08); padding: 30px 34px 34px; }
    .project-ntp-title { font-family: Georgia, "Times New Roman", serif; font-size: 18pt; font-weight: 700; line-height: 1.05; }
    .project-ntp-code { margin-bottom: 24px; font-family: Georgia, "Times New Roman", serif; font-size: 8pt; font-weight: 700; }
    .project-ntp-issued { margin: 18px 0 12px; font-family: Georgia, "Times New Roman", serif; font-size: 12pt; font-weight: 700; }
    .project-ntp-light { font-weight: 400; }
    .project-ntp-meta, .project-ntp-signatures { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .project-ntp-meta td { border: 1px solid #000; padding: 8px 10px; vertical-align: top; font-family: Georgia, "Times New Roman", serif; font-size: 11pt; font-weight: 700; }
    .project-ntp-copy { margin-top: 22px; font-size: 11pt; line-height: 1.35; }
    .project-ntp-copy p { margin: 0 0 18px; text-align: justify; }
    .project-ntp-signatures { margin-top: 34px; }
    .project-ntp-signatures td { border: 1px solid #000; padding: 8px 10px; vertical-align: top; }
    .project-ntp-sign-head { font-family: Georgia, "Times New Roman", serif; font-size: 12pt; font-weight: 700; }
    .project-ntp-sign-box { height: 96px; text-align: center; vertical-align: middle; font-family: Georgia, "Times New Roman", serif; font-size: 11pt; font-weight: 700; }
    .project-ntp-panel { margin-top: 28px; border: 1px solid #dbe3f0; background: #f8fbff; padding: 18px; }
    .project-ntp-grid { display: grid; gap: 14px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .project-ntp-label { display: block; margin-bottom: 6px; font-size: .74rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #475569; }
    .project-ntp-value-box { min-height: 44px; border: 1px solid #cbd5e1; background: #fff; padding: 10px 12px; font-size: .95rem; box-sizing: border-box; }
    .project-ntp-attachment-link { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; border: 1px solid #1c4587; background: #1c4587; padding: 0 18px; font-size: .9rem; font-weight: 700; color: #fff; text-decoration: none; }
    @media (max-width: 1280px) {
        .project-work-grid { grid-template-columns: 1fr; }
        .project-quick-grid { grid-template-columns: 1fr; }
        .project-doc-grid, .project-doc-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (min-width: 1281px) {
        .project-work-grid { grid-template-columns: 232px minmax(0, 1fr); }
        .project-quick-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .project-doc-grid, .project-doc-summary-grid { grid-template-columns: minmax(0, 1fr); }
        .project-doc-brand { flex-direction: column; }
        .project-doc-title { text-align: left; }
        .project-ntp-doc { padding: 20px 18px 24px; }
        .project-doc-view-header { flex-direction: column; }
        .project-doc-view-actions { width: 100%; justify-content: flex-start; }
        .project-doc-view-body { padding: 14px; }
        .project-doc-view-paper { padding: 20px 18px 22px; }
        .project-ntp-grid { grid-template-columns: minmax(0, 1fr); }
    }
</style>

<div class="project-workspace p-6">
    <div class="mx-auto max-w-[1600px] space-y-4">
        <div class="project-top-card rounded-2xl px-5 py-4 text-sm text-gray-600">
            <a href="{{ route('project.index') }}" class="hover:text-blue-700"><i class="fas fa-arrow-left mr-1"></i>Project</a>
            <span class="mx-1">/</span><span class="font-medium text-gray-900">{{ $project->project_code }}</span>
        </div>
        <div class="project-top-card rounded-2xl px-5 py-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Project Workspace</p>
                    <h1 class="mt-2 text-2xl font-semibold text-gray-900">{{ $project->name }}</h1>
                    <p class="mt-2 text-sm text-gray-500">{{ $project->project_code }} - {{ $project->deal?->deal_code ?? 'No linked deal code' }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="project-pill"><span class="text-slate-400">Business</span> {{ $project->business_name ?: '-' }}</span>
                    <span class="project-pill"><span class="text-slate-400">Client</span> {{ $contactName }}</span>
                    <span class="project-pill"><span class="text-slate-400">Planned Start</span> {{ $fmt($project->planned_start_date) }}</span>
                    <span class="project-pill"><span class="text-slate-400">Target Completion</span> {{ $fmt($project->target_completion_date) }}</span>
                </div>
            </div>
            <div class="mt-5 flex flex-wrap gap-2">
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('project.show', ['project' => $project->id, 'tab' => $key]) }}" class="project-tab-link {{ $tab === $key ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif
        @if ($projectLocked)
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                This project is completed. Documents are view-only; editing, generating, and approval uploads are locked.
            </div>
        @endif
        <div class="project-linked-card rounded-2xl px-5 py-4 text-sm text-gray-600">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex flex-wrap gap-x-8 gap-y-2">
                    <p>
                        Deal:
                        @if ($project->deal_id)
                            <a href="{{ route('deals.show', $project->deal_id) }}" class="font-medium text-blue-700 hover:text-blue-800">{{ $project->deal?->deal_code ?? 'View linked deal' }}</a>
                        @else
                            <span class="font-medium text-gray-500">No linked deal</span>
                        @endif
                    </p>
                    @if ($project->company_id)
                        <p>Company: <a href="{{ route('company.show', $project->company_id) }}" class="font-medium text-blue-700 hover:text-blue-800">{{ $project->company?->company_name ?? 'View company' }}</a></p>
                    @endif
                    @if ($project->contact_id)
                        <p>Contact: <a href="{{ route('contacts.show', $project->contact_id) }}" class="font-medium text-blue-700 hover:text-blue-800">{{ $contactName }}</a></p>
                    @endif
                </div>
                <div class="project-linked-actions"></div>
            </div>
        </div>
        @if ($tab === 'sow')
            <div class="project-work-grid">
                <aside class="project-quick-actions rounded-2xl px-4 py-4 xl:sticky xl:top-6">
                    <p class="project-quick-title">Quick Actions</p>
                    <div class="project-quick-grid">
                        <div class="project-quick-group">
                            <p class="project-quick-label">Status</p>
                            <div class="project-quick-stack">
                                <span id="projectNtpStatusChip" class="project-doc-status-chip {{ $ntpApproved ? 'approved' : '' }}">
                                    <i id="projectNtpStatusIcon" class="{{ $ntpApproved ? 'fas fa-check-circle' : 'fas fa-hourglass-half' }}"></i>
                                    <span id="projectNtpStatusText">{{ $ntpStatusLabel }}</span>
                                </span>
                                <span class="project-doc-status-chip {{ $cocApproved ? 'approved' : '' }}">
                                    <i class="{{ $cocApproved ? 'fas fa-check-circle' : 'fas fa-file-circle-check' }}"></i>
                                    <span>{{ $cocApproved ? 'COC approved, project completed' : 'COC pending completion approval' }}</span>
                                </span>
                            </div>
                        </div>
                        <div class="project-quick-group">
                            <div class="flex items-center justify-between gap-2">
                                <p class="project-quick-label">Document Actions</p>
                                @if (! $projectLocked)
                                    <button type="button" id="projectSowAutoSettingsOpen" class="project-settings-trigger" title="Auto-report settings">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                @endif
                            </div>
                            <div class="project-quick-stack">
                                @if (! $projectLocked)
                                    <button type="submit" form="project-sow-form" class="project-doc-primary">Save Scope of Work</button>
                                    <button type="submit" form="project-sow-form" formaction="{{ route('project.sow.generate', $project) }}" class="project-doc-action">Generate SOW Report</button>
                                    <button type="button" id="projectCocAction" class="project-doc-action">Generate COC</button>
                                    <a href="{{ route('transmittal.create.project', $project) }}" class="project-doc-action">Generate Transmittal</a>
                                @elseif ($coc)
                                    <button type="button" id="projectCocAction" class="project-doc-action">View COC</button>
                                @endif
                                @if (! $projectLocked || $ntpApproved)
                                    <a
                                        id="projectNtpAction"
                                        href="{{ $ntpRecord ? route('project.ntp.submission', $project) : route('project.ntp.download', $project) }}"
                                        data-approved-view="{{ $ntpApproved ? 'true' : 'false' }}"
                                        data-status-url="{{ route('project.ntp.status', $project) }}"
                                        class="{{ $ntpApproved ? 'project-doc-action project-doc-action-approved' : 'project-doc-action' }}"
                                    ><span id="projectNtpActionText">{{ $ntpRecord ? 'View NTP' : 'Generate NTP' }}</span></a>
                                @endif
                                <a href="{{ route('project.sow.download', $project) }}" class="project-doc-action">Download PDF</a>
                            </div>
                        </div>
                        <div class="project-quick-group">
                            <p class="project-quick-label">Manual Approve SOW</p>
                            <form method="POST" action="{{ route('project.sow.manual-approve', $project) }}" enctype="multipart/form-data" class="project-quick-stack">
                                @csrf
                                <input type="file" name="signed_document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="w-full text-xs text-slate-600" @disabled($projectLocked)>
                                <input type="text" name="approval_note" placeholder="Approval note" class="border border-slate-300 px-3 py-2 text-sm" @disabled($projectLocked)>
                                <button type="submit" class="project-doc-primary" @disabled($projectLocked)>Manual Approve SOW</button>
                            </form>
                            @if ($sow?->approved_at)
                                <p class="text-xs text-slate-500">Approved {{ optional($sow->approved_at)->format('M d, Y h:i A') }} by {{ $sow->approved_by_name ?: 'Manual Override' }}.</p>
                            @endif
                        </div>
                        @if (! $projectLocked && ! $ntpApproved)
                            <div class="project-quick-group">
                                <p class="project-quick-label">Manual NTP Approval</p>
                                <form method="POST" action="{{ route('project.ntp.manual-approve', $project) }}" enctype="multipart/form-data" class="project-quick-stack">
                                    @csrf
                                    <input type="file" name="signed_document" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="w-full text-xs text-slate-600">
                                    <input type="text" name="approval_note" placeholder="Approval note" class="border border-slate-300 px-3 py-2 text-sm">
                                    <button type="submit" class="project-doc-primary">Upload Signed NTP & Approve</button>
                                </form>
                            </div>
                        @endif
                        <div class="project-quick-group">
                            <p class="project-quick-label">COC Completion</p>
                            @if (!empty($cocMeta['signed_attachment_path']))
                                <a href="{{ route('uploads.show', ['path' => $cocMeta['signed_attachment_path'], 'download' => 1]) }}" class="project-doc-action">Download Signed COC</a>
                            @endif
                            @if (! $projectLocked && ! $cocApproved)
                                <form method="POST" action="{{ route('project.coc.approve', $project) }}" enctype="multipart/form-data" class="project-quick-stack">
                                    @csrf
                                    <input type="file" name="signed_document" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="w-full text-xs text-slate-600">
                                    <input type="text" name="approval_name" placeholder="Approver name" class="border border-slate-300 px-3 py-2 text-sm">
                                    <button type="submit" class="project-doc-primary">Upload Signed COC & Complete</button>
                                </form>
                            @endif
                        </div>
                        @if (! $projectLocked)
                        <div class="project-quick-group">
                            <p class="project-quick-label">Templates</p>
                            <div class="project-quick-stack">
                                <button type="button" id="projectMakeTemplateButton" class="project-doc-action">Make a Template</button>
                                @if ($sowTemplates->isNotEmpty())
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-xs text-slate-600">
                                        {{ $sowTemplates->count() }} saved SOW template{{ $sowTemplates->count() === 1 ? '' : 's' }} available in Create Project.
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </aside>
                <div class="min-w-0">
                    @include('project.partials.tab-'.$tab, compact('project', 'sow', 'report', 'contactName', 'sowWithin', 'sowOut', 'repWithin', 'repOut', 'sowApproval', 'repApproval', 'repSummary'))
                </div>
            </div>
        @else
            <div class="space-y-4">
                @include('project.partials.tab-'.$tab, compact('project', 'sow', 'report', 'contactName', 'sowWithin', 'sowOut', 'repWithin', 'repOut', 'sowApproval', 'repApproval', 'repSummary'))
            </div>
        @endif
    </div>
</div>
@if ($tab === 'sow' && $ntpRecord)
<div id="projectApprovedNtpModal" class="project-ntp-modal" aria-hidden="true">
    <button id="projectApprovedNtpOverlay" type="button" class="project-ntp-overlay" aria-label="Close NTP view"></button>
    <div class="project-ntp-frame">
        <div class="project-ntp-shell">
            <div class="project-doc-view-shell">
                <div class="project-doc-view-header">
                    <div>
                        <p class="project-doc-view-eyebrow">Project Document Viewer</p>
                        <h2 class="project-doc-view-title">Notice to Proceed</h2>
                        <p class="project-doc-view-copy">Review the NTP in the same branded viewer used across the project workspace. The original document structure is preserved.</p>
                    </div>
                    <div class="project-doc-view-actions">
                        <button id="projectApprovedNtpClose" type="button" class="project-doc-view-action">Close View</button>
                    </div>
                </div>
                <div class="project-doc-view-body">
                    @include('project.partials.approved-ntp-document', ['ntp' => $ntpRecord->payload ?? [], 'ntpRecord' => $ntpRecord, 'contactName' => $contactName])
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@if ($tab === 'sow')
<div id="projectCocModal" class="project-ntp-modal" aria-hidden="true">
    <button id="projectCocOverlay" type="button" class="project-ntp-overlay" aria-label="Close certificate preview"></button>
    <div class="project-ntp-frame">
        <div class="project-ntp-shell">
            <div class="project-doc-view-shell">
                <div class="project-doc-view-header">
                    <div>
                        <p class="project-doc-view-eyebrow">Project Document Viewer</p>
                        <h2 class="project-doc-view-title">Certificate of Completion</h2>
                        <p class="project-doc-view-copy">Preview the completion certificate with the same workspace styling and branding while keeping the certificate form itself unchanged.</p>
                    </div>
                    <div class="project-doc-view-actions">
                        <a href="{{ route('project.coc.download', $project) }}" class="project-doc-view-action primary">Download PDF</a>
                        <button id="projectCocClose" type="button" class="project-doc-view-action">Close View</button>
                    </div>
                </div>
                <div class="project-doc-view-body">
                    @include('project.partials.approved-coc-document', ['coc' => $coc ?? []])
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@if ($tab === 'sow')
<div id="projectSowAutoSettingsModal" class="project-settings-modal" aria-hidden="true">
    <button id="projectSowAutoSettingsOverlay" type="button" class="project-settings-overlay" aria-label="Close auto-report settings"></button>
    <div class="project-settings-frame">
        <div class="project-settings-card p-5">
            <h3 class="text-lg font-semibold text-slate-900">SOW Auto-Generate Report</h3>
            <p class="mt-1 text-sm text-slate-500">Set a monthly day when SOW reports auto-generate.</p>
            <form method="POST" action="{{ route('project.sow.auto-settings', $project) }}" class="mt-4 space-y-4">
                @csrf
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="enabled" value="0">
                    <input type="checkbox" name="enabled" value="1" class="h-4 w-4" {{ !empty($sowAutoReportSettings['enabled']) ? 'checked' : '' }}>
                    Enable monthly auto-generation
                </label>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Day of Month</label>
                    <input type="number" min="1" max="31" name="day_of_month" value="{{ (int) ($sowAutoReportSettings['day_of_month'] ?? 30) }}" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm">
                </div>
                <p class="text-xs text-slate-500">If a month has fewer days, it runs on the last day of that month.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" id="projectSowAutoSettingsClose" class="project-doc-action">Cancel</button>
                    <button type="submit" class="project-doc-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    (() => {
        const action = document.getElementById('projectNtpAction');
        const cocAction = document.getElementById('projectCocAction');
        const statusChip = document.getElementById('projectNtpStatusChip');
        const statusIcon = document.getElementById('projectNtpStatusIcon');
        const statusText = document.getElementById('projectNtpStatusText');
        const actionText = document.getElementById('projectNtpActionText');
        const approvedNtpModal = document.getElementById('projectApprovedNtpModal');
        const approvedNtpOverlay = document.getElementById('projectApprovedNtpOverlay');
        const approvedNtpClose = document.getElementById('projectApprovedNtpClose');
        const cocModal = document.getElementById('projectCocModal');
        const cocOverlay = document.getElementById('projectCocOverlay');
        const cocClose = document.getElementById('projectCocClose');
        const makeTemplateButton = document.getElementById('projectMakeTemplateButton');
        const sowForm = document.getElementById('project-sow-form');
        const autoSettingsOpen = document.getElementById('projectSowAutoSettingsOpen');
        const autoSettingsModal = document.getElementById('projectSowAutoSettingsModal');
        const autoSettingsOverlay = document.getElementById('projectSowAutoSettingsOverlay');
        const autoSettingsClose = document.getElementById('projectSowAutoSettingsClose');

        const openApprovedNtpModal = () => {
            if (!approvedNtpModal) {
                return;
            }

            approvedNtpModal.classList.add('is-open');
            approvedNtpModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
        };

        const closeApprovedNtpModal = () => {
            if (!approvedNtpModal) {
                return;
            }

            approvedNtpModal.classList.remove('is-open');
            approvedNtpModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
        };

        const openCocModal = () => {
            if (!cocModal) {
                return;
            }

            cocModal.classList.add('is-open');
            cocModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
        };

        const closeCocModal = () => {
            if (!cocModal) {
                return;
            }

            cocModal.classList.remove('is-open');
            cocModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
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

        const applyState = (payload) => {
            if (!action || !statusChip || !statusIcon || !statusText || !actionText) {
                return;
            }

            if (!payload) {
                return;
            }

            action.href = payload.action_url || action.href;
            action.className = payload.button_class || 'project-doc-action';
            actionText.textContent = payload.button_label || 'Generate NTP';
            action.dataset.approvedView = payload.is_approved ? 'true' : 'false';

            statusText.textContent = payload.status_label || 'NTP not generated';
            statusIcon.className = payload.is_approved ? 'fas fa-check-circle' : 'fas fa-hourglass-half';
            statusChip.classList.toggle('approved', Boolean(payload.is_approved));
        };

        const pollStatus = async () => {
            if (!action) {
                return;
            }

            try {
                const response = await fetch(action.dataset.statusUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                applyState(payload);

                if (payload.is_approved) {
                    window.clearInterval(intervalId);
                }
            } catch (error) {
                console.error('Unable to refresh NTP status.', error);
            }
        };

        action?.addEventListener('click', (event) => {
            if (actionText?.textContent?.trim() === 'View NTP' && approvedNtpModal) {
                event.preventDefault();
                openApprovedNtpModal();
            }
        });

        cocAction?.addEventListener('click', () => {
            openCocModal();
        });

        makeTemplateButton?.addEventListener('click', () => {
            if (!sowForm) {
                return;
            }

            const templateName = window.prompt('Template name');
            if (!templateName || templateName.trim() === '') {
                return;
            }

            const templateInput = sowForm.querySelector('input[name="template_name"]');

            if (templateInput) {
                templateInput.value = templateName.trim();
            }

            sowForm.setAttribute('action', @json(route('project.sow.templates.store', $project)));
            sowForm.requestSubmit();
        });
        autoSettingsOpen?.addEventListener('click', openAutoSettingsModal);
        autoSettingsOverlay?.addEventListener('click', closeAutoSettingsModal);
        autoSettingsClose?.addEventListener('click', closeAutoSettingsModal);

        approvedNtpOverlay?.addEventListener('click', closeApprovedNtpModal);
        approvedNtpClose?.addEventListener('click', closeApprovedNtpModal);
        cocOverlay?.addEventListener('click', closeCocModal);
        cocClose?.addEventListener('click', closeCocModal);

        const intervalId = action ? window.setInterval(pollStatus, 15000) : null;
    })();
</script>
@endif
@endsection

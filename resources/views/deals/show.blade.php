@extends('layouts.app')
@section('title', 'Deal Details')

@section('content')
@php
    $formatCurrency = static fn ($amount): string => 'P'.number_format((float) $amount, 2);
    $stageBadgeClasses = [
        'Inquiry' => 'bg-blue-100 text-blue-700 border border-blue-200',
        'Qualification' => 'bg-indigo-100 text-indigo-700 border border-indigo-200',
        'Consultation' => 'bg-cyan-100 text-cyan-700 border border-cyan-200',
        'Proposal' => 'bg-amber-100 text-amber-700 border border-amber-200',
        'Negotiation' => 'bg-orange-100 text-orange-700 border border-orange-200',
        'Payment' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
        'Activation' => 'bg-violet-100 text-violet-700 border border-violet-200',
        'Closed Won' => 'bg-green-100 text-green-700 border border-green-200',
        'Closed Lost' => 'bg-red-100 text-red-700 border border-red-200',
    ];
    $dealStatusClasses = [
        'Pending' => 'bg-amber-100 text-amber-700 border border-amber-200',
        'Approved' => 'bg-green-100 text-green-700 border border-green-200',
        'Rejected' => 'bg-red-100 text-red-700 border border-red-200',
    ];
    $initials = strtoupper(substr((string) ($deal['contact_name'] ?? 'C'), 0, 1).substr(strrchr(' '.($deal['contact_name'] ?? 'C'), ' '), 1, 1));
    $progressCurrentStage = data_get($detail, 'progress.current_stage', []);
    $progressCurrentStagePosition = (int) (data_get($progressCurrentStage, 'position') ?? data_get($progressCurrentStage, 'order', 0));
    $stageBadgeClassesJson = $stageBadgeClasses;
    $currentStageNameJson = $deal['stage'] ?? data_get($progressCurrentStage, 'name');
    $currentStagePositionJson = $progressCurrentStagePosition;
    $stageDataJson = collect($stages ?? [])->values()->all();
    $currentStageIdJson = $deal['stage_id'] ?? null;
    $startSectionAvailable = isset($project) && $project && ! str_contains(strtolower(trim((string) $project->engagement_type)), 'regular');
    $serviceMemo = $serviceMemo ?? null;
    $dealServiceMemoUrl = null;
    $dealServiceMemoDownloadUrl = null;
    $clientQuotationEmail = old('recipient_email', $detail['email_address'] ?? '');
    $assignedFinanceName = data_get($detail, 'ownership.finance');
    $assignedFinanceEmail = data_get($detail, 'ownership.finance_email');
    $proposalRecord = $proposalRecord ?? data_get($detail, 'proposal');
@endphp

<style>
    .deal-workspace {
        background:
            radial-gradient(circle at top left, rgba(13, 70, 140, 0.08), transparent 28%),
            linear-gradient(180deg, #f2f6fc 0%, #fbfcfe 26%, #fbfcfe 100%);
    }
    .deal-top-card {
        border: 1px solid #d8e1ee;
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.05);
    }
    .deal-quick-actions {
        border: 1px solid #d8e1ee;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.04);
    }
    .deal-quick-title {
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #64748b;
    }
    .deal-quick-grid {
        display: grid;
        gap: 12px;
        margin-top: 14px;
    }
    .deal-quick-group {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 12px;
        background: #fff;
    }
    .deal-quick-label {
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #94a3b8;
    }
    .deal-quick-stack {
        display: grid;
        gap: 10px;
        margin-top: 10px;
    }
    .deal-action-btn {
        display: flex;
        min-height: 2.25rem;
        align-items: center;
        border-radius: 0.5rem;
        border: 1px solid #cbd5e1;
        background: #fff;
        padding: 0.5rem 0.75rem;
        text-align: left;
        font-size: 0.875rem;
        font-weight: 600;
        color: #334155;
        transition: all 0.15s ease;
    }
    .deal-action-btn:hover {
        border-color: #9eb2cf;
        background: #f8fbff;
        color: #1c4587;
    }
    .deal-action-primary {
        border-color: #1c4587;
        background: #1c4587;
        color: #fff;
        box-shadow: 0 10px 22px rgba(28, 69, 135, 0.16);
    }
    .deal-action-primary:hover {
        border-color: #17376d;
        background: #17376d;
        color: #fff;
    }
    .deal-flow-card {
        border: 1px solid #d8e1ee;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.04);
    }
    .deal-flow-form {
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        background: #fff;
        padding: 0.75rem;
    }
    .project-doc-view-body { max-height: calc(100vh - 210px); overflow-y: auto; padding: 24px; background: linear-gradient(180deg, #eef4ff 0%, #f8fbff 100%); }
    .project-doc-view-sheet { display: flex; justify-content: center; }
    .project-doc-view-paper { width: min(100%, 860px); border: 1px solid #d8e1ee; background: #fff; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08); padding: 0; }
    .project-ntp-sheet { border: 1px solid #d8e1ee; background: #fff; box-shadow: 0 16px 34px rgba(15, 23, 42, 0.05); }
    .project-ntp-doc { padding: 32px 34px 36px; border: 2px solid #1c4587; }
    .project-ntp-title { font-family: Georgia, "Times New Roman", serif; font-size: 18pt; font-weight: 700; line-height: 1.05; }
    .project-ntp-code { margin-bottom: 24px; font-family: Georgia, "Times New Roman", serif; font-size: 8pt; font-weight: 700; }
    .project-ntp-light { font-weight: 400; }
    .project-ntp-meta, .project-ntp-signatures { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .project-ntp-meta td { border: 1px solid #000; padding: 8px 10px; vertical-align: top; font-family: Georgia, "Times New Roman", serif; font-size: 11pt; font-weight: 700; }
    .project-ntp-copy { margin-top: 22px; font-size: 11pt; line-height: 1.35; }
    .project-ntp-copy p { margin: 0 0 18px; text-align: justify; }
    .project-ntp-signatures { margin-top: 34px; }
    .project-ntp-signatures td { border: 1px solid #000; padding: 8px 10px; vertical-align: top; }
    .project-ntp-sign-box { height: 96px; text-align: center; vertical-align: middle; font-family: Georgia, "Times New Roman", serif; font-size: 11pt; font-weight: 700; }
    .project-service-memo-doc { min-height: 1030px; padding: 28px 32px 22px; display: flex; flex-direction: column; }
    .project-service-memo-meta { margin-top: 18px; }
    .project-service-memo-meta td { padding: 6px 9px; font-size: 10pt; }
    .project-service-memo-copy { margin-top: 18px; font-size: 10.5pt; line-height: 1.3; }
    .project-service-memo-copy p { margin-bottom: 12px; }
    .project-service-memo-signatures { margin-top: 18px; }
    .project-service-memo-signatures .project-ntp-sign-box { height: 78px; font-size: 10pt; }
    .project-service-memo-footer { margin-top: auto; padding-top: 16px; font-family: Georgia, "Times New Roman", serif; font-size: 8pt; line-height: 1.25; color: #0f172a; }
    .project-service-memo-footer p { margin: 0; }
    @media (max-width: 768px) {
        .project-doc-view-body { padding: 14px; }
        .project-ntp-doc { padding: 20px 18px 24px; }
        .project-service-memo-doc { min-height: 980px; }
        .project-ntp-meta td, .project-ntp-sign-box { font-size: 9pt; }
    }
</style>

<div class="deal-workspace p-6">
    <div class="mx-auto max-w-[1500px] space-y-4">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
                @if (session('proposal_client_link'))
                    <a href="{{ session('proposal_client_link') }}" target="_blank" class="ml-2 font-medium underline">Open client link</a>
                @endif
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="deal-top-card rounded-xl px-5 py-4 text-sm text-gray-600">
            <a href="{{ route('deals.index') }}" class="hover:text-blue-700"><i class="fas fa-arrow-left mr-1"></i>Deals</a>
            <span class="mx-1">/</span>
            <span class="font-medium text-gray-900">{{ $deal['deal_code'] ?? 'DEAL' }}</span>
        </div>

        <div class="deal-top-card rounded-xl px-5 py-4">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-semibold text-gray-900">{{ $deal['deal_code'] ?? 'DEAL' }}</h1>
                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $dealStatusClasses[$detail['deal_status'] ?? 'Pending'] ?? 'bg-gray-100 text-gray-700 border border-gray-200' }}">{{ $detail['deal_status'] ?? 'Pending' }}</span>
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span id="dealStageBadge" class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $stageBadgeClasses[$deal['stage']] ?? 'bg-gray-100 text-gray-700 border border-gray-200' }}">{{ $deal['stage'] }}</span>
                <span class="inline-flex items-center gap-1.5 text-lg font-semibold text-gray-900" data-price-wrap data-price-visible="false">
                    <span data-price-hidden>••••••</span>
                    <span data-price-value class="hidden">{{ $formatCurrency($deal['amount'] ?? 0) }}</span>
                    <button type="button" data-price-toggle aria-label="Show deal value" title="Show deal value" class="inline-flex h-6 w-6 items-center justify-center rounded-full text-gray-400 hover:bg-blue-50 hover:text-blue-700">
                        <i class="fas fa-eye text-[11px]" data-price-icon></i>
                    </button>
                </span>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1.85fr_0.85fr]">
            <div class="space-y-4">
                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 class="mb-4 text-xl font-semibold text-gray-900">Deal Information</h2>
                    <div class="grid gap-4 text-sm md:grid-cols-2">
                        <div><p class="text-xs text-gray-500">Deal Code</p><p class="font-medium text-gray-800">{{ $deal['deal_code'] ?? '-' }}</p></div>
                        <div><p class="text-xs text-gray-500">Company Name</p><p class="font-medium text-gray-800">{{ $detail['related_company'] ?? '-' }}</p></div>
                        <div><p class="text-xs text-gray-500">Contact Person Name</p><p class="font-medium text-gray-800">{{ $detail['contact_person_name'] ?? '-' }}</p></div>
                        <div><p class="text-xs text-gray-500">Contact Person Position</p><p class="font-medium text-gray-800">{{ $detail['contact_person_position'] ?? '-' }}</p></div>
                        <div><p class="text-xs text-gray-500">Email Address</p><p class="font-medium text-gray-800">{{ $detail['email_address'] ?? '-' }}</p></div>
                        <div><p class="text-xs text-gray-500">Contact Number</p><p class="font-medium text-gray-800">{{ $detail['contact_number'] ?? '-' }}</p></div>
                        <div><p class="text-xs text-gray-500">Client Type</p><p class="font-medium text-gray-800">{{ $detail['client_type'] ?? '-' }}</p></div>
                        <div><p class="text-xs text-gray-500">Industry</p><p class="font-medium text-gray-800">{{ $detail['industry'] ?? '-' }}</p></div>
                        <div><p class="text-xs text-gray-500">Qualification Result</p><p class="font-medium text-gray-800">{{ $detail['qualification_result'] ?? '-' }}</p></div>
                        <div><p class="text-xs text-gray-500">Qualification Notes</p><p class="font-medium text-gray-800">{{ $detail['qualification_notes'] ?? '-' }}</p></div>
                        <div><p class="text-xs text-gray-500">Deal Stage</p><p id="dealStageText" class="font-medium text-gray-800">{{ $detail['deal_stage'] ?? '-' }}</p></div>
                        <div><p class="text-xs text-gray-500">Expected Close Date</p><p class="font-medium text-gray-800">{{ $detail['expected_close_date'] ?? '-' }}</p></div>
                    </div>
                </article>

                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 class="mb-4 text-xl font-semibold text-gray-900">Service and Engagement Details</h2>
                    <div class="grid gap-4 text-sm md:grid-cols-2">
                        <div><p class="text-xs text-gray-500">Service Type</p><p class="font-medium text-gray-800">{{ data_get($detail, 'service.service_type', '-') }}</p></div>
                        <div><p class="text-xs text-gray-500">Product Type</p><p class="font-medium text-gray-800">{{ data_get($detail, 'service.product_type', '-') }}</p></div>
                        <div><p class="text-xs text-gray-500">Engagement Type</p><p class="font-medium text-gray-800">{{ data_get($detail, 'service.engagement_type', '-') }}</p></div>
                        <div><p class="text-xs text-gray-500">Deal Code</p><p class="font-medium text-gray-800">{{ $deal['deal_code'] ?? '-' }}</p></div>
                        <div><p class="text-xs text-gray-500">Engagement Duration</p><p class="font-medium text-gray-800">{{ data_get($detail, 'service.engagement_duration', '-') }}</p></div>
                    </div>
                </article>

                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 class="mb-4 text-xl font-semibold text-gray-900">Financial Details</h2>
                    <div class="grid gap-4 text-sm md:grid-cols-2">
                        <div data-price-wrap data-price-visible="false">
                            <p class="flex items-center gap-1.5 text-xs text-gray-500">
                                <span>Deal Value</span>
                                <button type="button" data-price-toggle aria-label="Show deal value" title="Show deal value" class="inline-flex h-5 w-5 items-center justify-center rounded-full text-gray-400 hover:bg-blue-50 hover:text-blue-700">
                                    <i class="fas fa-eye text-[10px]" data-price-icon></i>
                                </button>
                            </p>
                            <p class="font-medium text-gray-800">
                                <span data-price-hidden>••••••</span>
                                <span data-price-value class="hidden">{{ $formatCurrency(data_get($detail, 'financial.deal_value', 0)) }}</span>
                            </p>
                        </div>
                        <div><p class="text-xs text-gray-500">Pricing Model</p><p class="font-medium text-gray-800">{{ data_get($detail, 'financial.pricing_model', '-') }}</p></div>
                        <div><p class="text-xs text-gray-500">Payment Terms</p><p class="font-medium text-gray-800">{{ data_get($detail, 'financial.payment_terms', '-') }}</p></div>
                        <div><p class="text-xs text-gray-500">Commission Applicable</p><p class="font-medium text-gray-800">{{ data_get($detail, 'financial.commission_applicable', '-') }}</p></div>
                    </div>
                </article>

                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 class="mb-4 text-xl font-semibold text-gray-900">Referral and Lead Source</h2>
                    <div class="grid gap-4 text-sm md:grid-cols-2">
                        <div><p class="text-xs text-gray-500">Lead Source</p><p class="font-medium text-gray-800">{{ data_get($detail, 'referral.lead_source', '-') }}</p></div>
                        <div><p class="text-xs text-gray-500">Referred By</p><p class="font-medium text-gray-800">{{ data_get($detail, 'referral.referred_by', '-') }}</p></div>
                        <div><p class="text-xs text-gray-500">Referral Type</p><p class="font-medium text-gray-800">{{ data_get($detail, 'referral.referral_type', '-') }}</p></div>
                    </div>
                </article>

                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 class="mb-4 text-xl font-semibold text-gray-900">Deal Ownership and Team Assignment</h2>
                    <div class="grid gap-4 text-sm md:grid-cols-2">
                        <div><p class="text-xs text-gray-500">Lead Consultant</p><p class="font-medium text-gray-800">{{ data_get($detail, 'ownership.lead_consultant', '-') }}</p></div>
                        <div><p class="text-xs text-gray-500">Lead Associate</p><p class="font-medium text-gray-800">{{ data_get($detail, 'ownership.lead_associate', '-') }}</p></div>
                        <div><p class="text-xs text-gray-500">Finance</p><p class="font-medium text-gray-800">{{ data_get($detail, 'ownership.finance', '-') }}</p></div>
                        <div><p class="text-xs text-gray-500">Handling Team</p><p class="font-medium text-gray-800">{{ data_get($detail, 'ownership.handling_team', '-') }}</p></div>
                        <div>
                            <p class="text-xs text-gray-500">Assigned Team Members</p>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                @forelse ((array) data_get($detail, 'ownership.assigned_members', []) as $member)
                                    <span class="rounded-full border border-gray-200 bg-gray-100 px-2 py-0.5 text-xs text-gray-700">{{ $member }}</span>
                                @empty
                                    <span class="text-sm text-gray-500">-</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </article>

                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Deal Form Preview</h2>
                    @include('deals.partials.deal-form-document', ['dealFormData' => $dealFormData])
                </article>

                @if ($startSectionAvailable)
                    <article id="deal-start-form" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-100 px-4 py-4">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">START Form</h2>
                                <p class="mt-1 text-sm text-gray-500">Manage the project START intake directly from this deal.</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('project.start.download', $project) }}" class="deal-action-btn">
                                    <i class="fas fa-file-arrow-down mr-1"></i>Download START PDF
                                </a>
                                <button type="button" class="deal-action-btn" data-open-start-drawer="project-start-drawer-{{ $project->id }}">
                                    <i class="far fa-pen-to-square mr-1"></i>Edit START
                                </button>
                                @if (strtolower((string) ($start?->status ?? 'pending')) === 'approved' && $start?->approved_at)
                                    @php
                                        $dealServiceMemoPath = (string) data_get($start?->attachments, 'service_memo_pdf_path', '');
                                        $dealServiceMemoUrl = $dealServiceMemoPath !== '' ? route('uploads.show', ['path' => $dealServiceMemoPath]) : null;
                                        $dealServiceMemoDownloadUrl = $dealServiceMemoPath !== '' ? route('uploads.show', ['path' => $dealServiceMemoPath, 'download' => 1]) : null;
                                    @endphp
                                    @if ($dealServiceMemoUrl)
                                        <button id="dealServiceMemoViewButton" type="button" class="deal-action-btn">
                                            <i class="fas fa-file-lines mr-1"></i>View Service Memo
                                        </button>
                                        <a href="{{ $dealServiceMemoDownloadUrl }}" class="deal-action-btn">
                                            <i class="fas fa-file-arrow-down mr-1"></i>Download Service Memo
                                        </a>
                                    @else
                                        <a href="{{ route('project.service-memo.download', $project) }}" class="deal-action-btn">
                                            <i class="fas fa-file-lines mr-1"></i>Generate Service Memo
                                        </a>
                                    @endif
                                @endif
                                <a href="{{ route('project.show', ['project' => $project->id, 'tab' => 'start']) }}" class="deal-action-btn">
                                    <i class="fas fa-up-right-from-square mr-1"></i>Open Full Project
                                </a>
                            </div>
                        </div>
                        <div class="p-4">
                            @include('project.partials.tab-start', [
                                'project' => $project,
                                'start' => $start,
                                'startChecklist' => $startChecklist,
                                'startKycOrganization' => $startKycOrganization,
                                'startKycSole' => $startKycSole,
                                'startKycJuridical' => $startKycJuridical,
                                'startReqs' => $startReqs,
                                'startApprovalSteps' => $startApprovalSteps,
                                'startClearance' => $startClearance,
                                'routing' => $routing,
                                'startRedirectUrl' => route('deals.show', $deal['id']).'#deal-start-form',
                            ])
                        </div>
                    </article>

                    @if ($dealServiceMemoUrl && $serviceMemo)
                        <div id="dealServiceMemoModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
                            <button id="dealServiceMemoOverlay" type="button" class="absolute inset-0 bg-slate-950/60" aria-label="Close service memo preview"></button>
                            <div class="absolute inset-0 overflow-y-auto p-4">
                                <div class="mx-auto max-w-[1080px] rounded-[28px] border border-slate-200 bg-white shadow-[0_24px_60px_rgba(15,23,42,0.12)]">
                                    <div class="flex flex-col gap-4 border-b border-slate-200 bg-slate-50 px-6 py-5 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="max-w-2xl">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Project Document Viewer</p>
                                            <h2 class="mt-2 text-xl font-semibold text-slate-950">Service Memo</h2>
                                            <p class="mt-2 text-sm leading-6 text-slate-600">Review the approved service memo in the same branded workspace viewer while preserving the original document structure.</p>
                                        </div>
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            <a href="{{ $dealServiceMemoDownloadUrl }}" class="inline-flex h-11 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-900 hover:bg-slate-50">
                                                <i class="fas fa-file-arrow-down mr-2"></i>Download PDF
                                            </a>
                                            <button id="dealServiceMemoCloseButton" type="button" class="inline-flex h-11 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-900 hover:bg-slate-50">
                                                Close View
                                            </button>
                                        </div>
                                    </div>
                                    <div class="project-doc-view-body">
                                        <div class="project-doc-view-sheet">
                                            <div class="project-doc-view-paper">
                                                @include('project.partials.service-memo-document', ['serviceMemo' => $serviceMemo])
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 class="mb-4 text-xl font-semibold text-gray-900">Deal Stage Progress</h2>
                    <div id="dealStageProgress" class="flex flex-wrap items-start justify-between gap-y-4 overflow-x-auto pb-1">
                        @foreach (($stages ?? []) as $stage)
                            @php
                                $stagePosition = (int) (data_get($stage, 'position') ?? data_get($stage, 'order', 0));
                                $stageId = data_get($stage, 'id');
                                $dealStageId = data_get($deal, 'stage_id');
                                $isCompleted = (
                                    filled($stageId)
                                    && filled($dealStageId)
                                    && $stagePosition <= $progressCurrentStagePosition
                                ) || (
                                    blank($dealStageId)
                                    && $stagePosition <= $progressCurrentStagePosition
                                );
                                $circleClass = $isCompleted
                                    ? 'bg-blue-600 text-white border-blue-600'
                                    : 'bg-gray-100 text-gray-300 border-gray-200';
                                $lineClass = $stagePosition < $progressCurrentStagePosition
                                    ? 'bg-blue-600'
                                    : 'bg-gray-200';
                            @endphp
                            <div class="flex min-w-[88px] flex-1 items-start" data-stage-progress-item data-stage-id="{{ data_get($stage, 'id') }}" data-stage-position="{{ $stagePosition }}">
                                <div class="flex w-full flex-col items-center text-center">
                                    <span class="mx-auto flex h-8 w-8 items-center justify-center rounded-full border text-xs {{ $circleClass }}">
                                        @if ($isCompleted)
                                            <i class="fas fa-check text-sm"></i>
                                        @endif
                                    </span>
                                    <p class="mt-2 text-xs text-gray-600">{{ data_get($stage, 'name') }}</p>
                                </div>
                                @if (! $loop->last)
                                    <span class="mt-4 hidden h-0.5 flex-1 rounded-full lg:block {{ $lineClass }}"></span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100">
                        <div class="flex flex-wrap gap-1 p-2">
                            @foreach (['timeline' => 'Timeline', 'notes' => 'Notes', 'activities' => 'Activities', 'emails' => 'Emails', 'stage-history' => 'Stage History', 'files' => 'Files', 'products' => 'Products'] as $tabKey => $label)
                                <button type="button" data-tab-button="{{ $tabKey }}" class="deal-tab-btn rounded-lg px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-100 {{ $tabKey === 'timeline' ? 'bg-blue-50 text-blue-700' : '' }}">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="p-4 text-sm">
                        <div data-tab-panel="timeline" class="deal-tab-panel space-y-3">
                            @foreach ((array) data_get($detail, 'timeline', []) as $entry)
                                <div class="flex items-start gap-3 rounded-lg border border-gray-100 p-3">
                                    <span class="mt-0.5 flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                        <i class="fas {{ $entry['icon'] ?? 'fa-clock' }} text-xs"></i>
                                    </span>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $entry['title'] ?? '-' }}</p>
                                        <p class="text-xs text-gray-500">{{ $entry['timestamp'] ?? '-' }} - {{ $entry['user'] ?? '-' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div data-tab-panel="notes" class="deal-tab-panel hidden py-6 text-center text-sm text-gray-500">No notes added yet</div>
                        <div data-tab-panel="activities" class="deal-tab-panel hidden py-6 text-center text-sm text-gray-500">No activities added yet</div>
                        <div data-tab-panel="emails" class="deal-tab-panel hidden py-6 text-center text-sm text-gray-500">No emails found for this deal</div>

                        <div data-tab-panel="stage-history" class="deal-tab-panel hidden">
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Stage</th>
                                            <th class="px-3 py-2 text-left">Amount</th>
                                            <th class="px-3 py-2 text-left">Stage Duration</th>
                                            <th class="px-3 py-2 text-left">Modified By</th>
                                            <th class="px-3 py-2 text-left">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ((array) data_get($detail, 'stage_history', []) as $history)
                                            <tr>
                                                <td class="px-3 py-2 text-gray-800">{{ $history['stage'] ?? '-' }}</td>
                                                <td class="px-3 py-2 font-medium text-blue-700">{{ $formatCurrency($history['amount'] ?? 0) }}</td>
                                                <td class="px-3 py-2 text-gray-700">{{ $history['duration'] ?? '-' }}</td>
                                                <td class="px-3 py-2 text-gray-700">{{ $history['modified_by'] ?? '-' }}</td>
                                                <td class="px-3 py-2 text-gray-700">{{ $history['date'] ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div data-tab-panel="files" class="deal-tab-panel hidden py-6 text-center text-sm text-gray-500">No files uploaded yet.</div>
                        <div data-tab-panel="products" class="deal-tab-panel hidden py-6 text-center text-sm text-gray-500">No associated products yet.</div>
                    </div>
                </article>
            </div>

            <aside>
                <div class="space-y-4 xl:sticky xl:top-6">
                    <section class="deal-quick-actions rounded-2xl px-4 py-4">
                        <p class="deal-quick-title">Quick Actions</p>
                        <div class="deal-quick-grid">
                            <div class="deal-quick-group">
                                <p class="deal-quick-label">Deal Actions</p>
                                <div class="deal-quick-stack">
                                    @if ($startSectionAvailable)
                                        <a href="#deal-start-form" class="deal-action-btn deal-action-primary">
                                            <i class="fas fa-clipboard-check mr-1"></i>START Form
                                        </a>
                                    @endif
                                    @if (($deal['stage'] ?? '') === 'Proposal' || (($hasSavedProposal ?? false) === true))
                                        <a href="{{ route('deals.proposal.show', $deal['id']) }}" class="deal-action-btn">
                                            @if (($hasSavedProposal ?? false) === true)
                                                <i class="fas fa-eye mr-1"></i>View Proposal
                                            @else
                                                <i class="fas fa-file-signature mr-1"></i>Create Proposal
                                            @endif
                                        </a>
                                    @endif
                                    <button id="openStageUpdateModalBtn" type="button" class="deal-action-btn">
                                        <i class="fas fa-arrow-up-right-dots mr-1"></i>Update Stage
                                    </button>
                                    <button id="openCreateDealModalBtn" type="button" class="deal-action-btn">
                                        <i class="far fa-pen-to-square mr-1"></i>Edit Deal
                                    </button>
                                </div>
                            </div>
                            <div class="deal-quick-group">
                                <p class="deal-quick-label">Workspaces</p>
                                <div class="deal-quick-stack">
                                    @if (data_get($detail, 'project.id'))
                                        <a href="{{ route('project.show', data_get($detail, 'project.id')) }}" class="deal-action-btn">
                                            <i class="fas fa-diagram-project mr-1"></i>Open Project Workspace
                                        </a>
                                    @endif
                                    @if (data_get($detail, 'regular_project.id'))
                                        <a href="{{ route('regular.show', data_get($detail, 'regular_project.id')) }}" class="deal-action-btn">
                                            <i class="fas fa-clipboard-list mr-1"></i>Open Regular
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="deal-quick-group">
                                <p class="deal-quick-label">Exports</p>
                                <div class="deal-quick-stack">
                                    <a href="{{ route('deals.download-pdf', ['id' => $deal['id'], 'autoprint' => 1]) }}" target="_blank" class="deal-action-btn">
                                        <i class="fas fa-file-pdf mr-1"></i>Download PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    </section>

                    @if (in_array(($deal['stage'] ?? ''), ['Negotiation', 'Payment', 'Activation'], true) && (($hasSavedProposal ?? false) === true))
                    <section class="deal-flow-card rounded-2xl px-4 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="deal-quick-title">Quotation</p>
                                <p class="mt-1 text-xs text-slate-500">Send the approved proposal as the quotation for this deal.</p>
                            </div>
                            <a href="{{ route('deals.quotation.finance', $deal['id']) }}" class="deal-action-btn whitespace-nowrap">
                                <i class="fas fa-file-invoice-dollar mr-1"></i>View
                            </a>
                        </div>
                        <div class="mt-4 grid gap-3">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-semibold">Status</span>
                                    <span class="rounded-full bg-white px-2 py-1 text-xs font-bold uppercase tracking-wide text-slate-600">
                                        {{ str_replace('_', ' ', $proposalRecord?->quotation_status ?: 'not started') }}
                                    </span>
                                </div>
                                @if ($proposalRecord?->quotation_finance_started_at)
                                    <div class="mt-1 text-xs text-slate-500">Started {{ optional($proposalRecord->quotation_finance_started_at)->format('F j, Y g:i A') }}</div>
                                @endif
                                @if ($proposalRecord?->quotation_approved_at)
                                    <div class="mt-1 text-xs text-slate-500">Approved by {{ $proposalRecord->quotation_approved_by_name ?: 'Finance' }} on {{ optional($proposalRecord->quotation_approved_at)->format('F j, Y g:i A') }}</div>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('deals.quotation.send-client', $deal['id']) }}" class="deal-flow-form">
                                @csrf
                                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Client Email</label>
                                <input type="email" name="recipient_email" value="{{ $clientQuotationEmail }}" placeholder="client@email.com" class="h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" required>
                                <button type="submit" class="deal-action-btn deal-action-primary mt-2 w-full justify-center">
                                    <i class="fas fa-paper-plane mr-1"></i>Send to Client
                                </button>
                            </form>
                            <form method="POST" action="{{ route('deals.quotation.send-finance', $deal['id']) }}" class="deal-flow-form">
                                @csrf
                                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Finance Email</label>
                                @if (filled($assignedFinanceEmail))
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                        <div class="font-semibold">{{ $assignedFinanceName ?: 'Assigned Finance' }}</div>
                                        <div class="text-xs text-slate-500">{{ $assignedFinanceEmail }}</div>
                                    </div>
                                @else
                                    <input type="email" name="recipient_email" value="{{ old('recipient_email') }}" placeholder="finance@email.com" class="h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                    <p class="mt-1 text-xs text-slate-500">No finance user assigned yet. Edit the deal to assign one.</p>
                                @endif
                                <button type="submit" class="deal-action-btn mt-2 w-full justify-center">
                                    <i class="fas fa-building-columns mr-1"></i>Send to Finance
                                </button>
                            </form>
                        </div>
                    </section>
                    @endif

                    @if (in_array(($deal['stage'] ?? ''), ['Payment', 'Activation'], true) && (($hasSavedProposal ?? false) === true))
                    <section class="deal-flow-card rounded-2xl px-4 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="deal-quick-title">Payment / Invoice</p>
                                <p class="mt-1 text-xs text-slate-500">Generate or upload the invoice and confirm payment.</p>
                            </div>
                            <a href="{{ route('deals.invoice.payment', $deal['id']) }}" class="deal-action-btn whitespace-nowrap">
                                <i class="fas fa-file-invoice mr-1"></i>Open
                            </a>
                        </div>
                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-semibold">Status</span>
                                <span class="rounded-full bg-white px-2 py-1 text-xs font-bold uppercase tracking-wide text-slate-600">
                                    {{ str_replace('_', ' ', $proposalRecord?->invoice_status ?: 'not started') }}
                                </span>
                            </div>
                            @if ($proposalRecord?->invoice_uploaded_at)
                                <div class="mt-1 text-xs text-slate-500">Invoice uploaded {{ optional($proposalRecord->invoice_uploaded_at)->format('F j, Y g:i A') }}</div>
                            @endif
                            @if ($proposalRecord?->payment_confirmed_at)
                                <div class="mt-1 text-xs text-slate-500">Payment confirmed by {{ $proposalRecord->payment_confirmed_by_name ?: 'Finance' }} on {{ optional($proposalRecord->payment_confirmed_at)->format('F j, Y g:i A') }}</div>
                            @endif
                        </div>
                    </section>
                    @endif

                    <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <h3 class="mb-3 text-base font-semibold text-gray-900">Related Contact</h3>
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">{{ $initials }}</span>
                        <div>
                            <p class="font-medium text-gray-900">{{ $deal['contact_name'] ?? '-' }}</p>
                            <p class="text-sm text-gray-500">{{ $deal['company_name'] ?? '-' }}</p>
                            <p class="mt-2 text-xs text-gray-600"><i class="far fa-envelope mr-1"></i>{{ $detail['email_address'] ?? '-' }}</p>
                            <p class="mt-1 text-xs text-gray-600"><i class="fas fa-phone mr-1"></i>{{ $detail['contact_number'] ?? '-' }}</p>
                        </div>
                    </div>
                    </article>

                    <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <h3 class="mb-2 text-base font-semibold text-gray-900">Tags</h3>
                        <button type="button" class="text-sm font-medium text-blue-600 hover:text-blue-700"><i class="fas fa-plus mr-1"></i>Add Tag</button>
                    </article>
                </div>
            </aside>
        </div>
    </div>
</div>

<div id="dealStageModal" class="fixed inset-0 z-[70] hidden" aria-hidden="true">
    <button id="dealStageModalOverlay" type="button" class="absolute inset-0 bg-slate-900/35"></button>
    <div class="absolute left-1/2 top-1/2 w-full max-w-sm -translate-x-1/2 -translate-y-1/2 rounded-xl border border-gray-200 bg-white p-4 shadow-xl">
        <h3 class="text-base font-semibold text-gray-900">Update Deal Stage</h3>
        <p class="mt-1 text-xs text-gray-500">Select the next stage for this deal.</p>
        <form id="dealStageForm" method="POST" action="{{ route('deals.stage.update', $deal['id']) }}">
            @csrf
            @method('PATCH')
            <div class="mt-4">
                <label for="dealStageSelect" class="mb-1 block text-sm font-medium text-gray-700">Select Stage</label>
                <select id="dealStageSelect" name="stage_id" required data-current-stage-name="{{ $deal['stage'] ?? data_get($progressCurrentStage, 'name', '') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    @foreach (($stages ?? []) as $stage)
                        @php
                            $optionStageId = data_get($stage, 'id');
                            $optionStageName = (string) data_get($stage, 'name', '');
                            $optionValue = filled($optionStageId) ? (string) $optionStageId : $optionStageName;
                            $selectedById = filled($deal['stage_id'] ?? null) && filled($optionStageId) && (int) $optionStageId === (int) ($deal['stage_id'] ?? null);
                            $selectedByName = blank($deal['stage_id'] ?? null) && $optionStageName !== '' && $optionStageName === (string) ($deal['stage'] ?? data_get($progressCurrentStage, 'name', ''));
                            $isSelected = $selectedById || $selectedByName;
                        @endphp
                        <option value="{{ $optionValue }}" data-stage-id="{{ $optionStageId }}" data-stage-name="{{ $optionStageName }}" @selected($isSelected)>{{ $optionStageName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mt-4 flex items-center justify-end gap-2">
                <button id="dealStageModalCancel" type="button" class="h-9 rounded-lg border border-gray-300 px-3 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                <button id="dealStageModalSave" type="submit" class="h-9 rounded-lg bg-blue-600 px-3 text-sm font-medium text-white hover:bg-blue-700">Save</button>
            </div>
        </form>
    </div>
</div>

<div id="dealStageToast" class="pointer-events-none fixed right-6 top-6 z-[80] {{ session('stage_success') ? '' : 'hidden' }} rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 shadow-lg">
    {{ session('stage_success', 'Stage updated successfully.') }}
</div>

@include('deals.partials.create-deal-modal', [
    'stageOptions' => $stageOptions,
    'companyOptions' => $companyOptions,
    'contactOptions' => $contactOptions,
    'contactRecords' => $contactRecords,
    'productOptionsByServiceArea' => $productOptionsByServiceArea ?? [],
    'ownerLabel' => $ownerLabel,
    'owners' => $owners,
    'financeUsers' => $financeUsers ?? [],
    'employeeOptions' => $employeeOptions ?? [],
    'defaultOwnerId' => $defaultOwnerId,
    'dealDraft' => $dealFormData,
    'openDealModal' => $openDealModal ?? false,
    'formAction' => route('deals.update', $deal['id']),
    'formMethod' => 'PUT',
    'panelTitle' => 'Edit Deal',
    'panelSubtitle' => 'Update the selected deal details.',
    'submitLabel' => 'Update Deal',
])

<div
    id="dealShowScriptData"
    data-current-stage-name="{{ e((string) ($currentStageNameJson ?? '')) }}"
    data-current-stage-position="{{ (int) ($currentStagePositionJson ?? 0) }}"
    data-current-stage-id="{{ e((string) ($currentStageIdJson ?? '')) }}"
></div>
<script id="dealShowStageBadgeClasses" type="application/json">@json($stageBadgeClassesJson)</script>
<script id="dealShowStageData" type="application/json">@json($stageDataJson)</script>
@if ($startSectionAvailable)
    <template id="start-requirement-row-template"><tr><td class="border border-slate-900 px-2 py-1 text-center"></td><td class="border border-slate-900 px-1"><input name="engagement_requirement[]" class="w-full border-0 px-1 py-1 text-[11px]"></td><td class="border border-slate-900 px-1"><input name="engagement_notes[]" class="w-full border-0 px-1 py-1 text-[11px]"></td><td class="border border-slate-900 px-1"><input name="engagement_purpose[]" class="w-full border-0 px-1 py-1 text-[11px]"></td><td class="border border-slate-900 px-1"><input name="engagement_provided_by[]" class="w-full border-0 px-1 py-1 text-[11px]"></td><td class="border border-slate-900 px-1"><input name="engagement_submitted_to[]" class="w-full border-0 px-1 py-1 text-[11px]"></td><td class="border border-slate-900 px-1"><input name="engagement_assigned_to[]" class="w-full border-0 px-1 py-1 text-[11px]"></td><td class="border border-slate-900 px-1"><input name="engagement_timeline[]" class="w-full border-0 px-1 py-1 text-[11px]"></td></tr></template>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dealShowScriptData = document.getElementById('dealShowScriptData');
    const stageBadgeClassesNode = document.getElementById('dealShowStageBadgeClasses');
    const stageDataNode = document.getElementById('dealShowStageData');
    const stagePrimary = document.getElementById('openStageUpdateModalBtn');
    const stageModal = document.getElementById('dealStageModal');
    const stageModalOverlay = document.getElementById('dealStageModalOverlay');
    const stageModalCancel = document.getElementById('dealStageModalCancel');
    const stageSelect = document.getElementById('dealStageSelect');
    const stageToast = document.getElementById('dealStageToast');
    const stageBadge = document.getElementById('dealStageBadge');
    const stageText = document.getElementById('dealStageText');
    const stageProgress = document.getElementById('dealStageProgress');
    const stageBadgeClasses = stageBadgeClassesNode ? JSON.parse(stageBadgeClassesNode.textContent || '{}') : {};
    const currentStageName = dealShowScriptData?.dataset.currentStageName || '';
    const currentStagePosition = Number(dealShowScriptData?.dataset.currentStagePosition || 0);
    let stageData = stageDataNode ? JSON.parse(stageDataNode.textContent || '[]') : [];
    let currentStageId = dealShowScriptData?.dataset.currentStageId || null;
    let toastTimer = null;

    document.querySelectorAll('[data-price-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const wrap = toggle.closest('[data-price-wrap]');
            if (!wrap) {
                return;
            }

            const isVisible = wrap.dataset.priceVisible === 'true';
            wrap.dataset.priceVisible = isVisible ? 'false' : 'true';
            wrap.querySelector('[data-price-hidden]')?.classList.toggle('hidden', !isVisible);
            wrap.querySelector('[data-price-value]')?.classList.toggle('hidden', isVisible);

            const icon = wrap.querySelector('[data-price-icon]');
            if (icon) {
                icon.classList.toggle('fa-eye', isVisible);
                icon.classList.toggle('fa-eye-slash', !isVisible);
            }

            toggle.setAttribute('aria-label', isVisible ? 'Show deal value' : 'Hide deal value');
            toggle.setAttribute('title', isVisible ? 'Show deal value' : 'Hide deal value');
        });
    });

    const buttons = Array.from(document.querySelectorAll('[data-tab-button]'));
    const panels = Array.from(document.querySelectorAll('[data-tab-panel]'));
    const activate = (tabKey) => {
        buttons.forEach((button) => {
            const isActive = button.dataset.tabButton === tabKey;
            button.classList.toggle('bg-blue-50', isActive);
            button.classList.toggle('text-blue-700', isActive);
            button.classList.toggle('text-gray-600', !isActive);
        });
        panels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.dataset.tabPanel !== tabKey);
        });
    };
    buttons.forEach((button) => button.addEventListener('click', () => activate(button.dataset.tabButton)));
    document.querySelectorAll('[data-add-row]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.getElementById(button.dataset.addRow);
            const template = document.getElementById('start-requirement-row-template');
            if (!target || !template) {
                return;
            }
            target.insertAdjacentHTML('beforeend', template.innerHTML);
        });
    });

    const getStagePosition = (stage) => Number(stage?.position ?? stage?.order ?? 0);

    const isCurrentStage = (stage, selectedId) => {
        const stageId = stage?.id;
        if (stageId !== null && stageId !== undefined && selectedId !== null && selectedId !== undefined && selectedId !== '') {
            return Number(stageId) === Number(selectedId);
        }
        return getStagePosition(stage) === Number(currentStagePosition || 0) && String(stage?.name || '') === String(currentStageName || '');
    };

    const getActiveStage = (selectedId) => {
        return stageData.find((item) => isCurrentStage(item, selectedId))
            || stageData.find((item) => String(item?.name || '') === String(currentStageName || ''));
    };

    const isCompletedStage = (stage, selectedId) => {
        const activePosition = getStagePosition(getActiveStage(selectedId));

        return activePosition > 0 && getStagePosition(stage) <= activePosition;
    };

    const openStageModal = () => {
        const stageNameFromSelect = stageSelect?.dataset.currentStageName || '';
        const currentStage = stageData.find((stage) => isCurrentStage(stage, currentStageId))
            || stageData.find((stage) => String(stage.name) === stageNameFromSelect);
        if (stageSelect) {
            if (currentStage) {
                stageSelect.value = String(currentStage.id);
                if (stageSelect.value !== String(currentStage.id)) {
                    const matchedOption = Array.from(stageSelect.options).find((option) => option.text.trim() === currentStage.name);
                    if (matchedOption) {
                        stageSelect.value = matchedOption.value;
                    }
                }
            } else if (stageData.length > 0) {
                stageSelect.value = String(stageData[0].id);
            }
        }
        stageModal?.classList.remove('hidden');
        stageModal?.setAttribute('aria-hidden', 'false');
    };

    const closeStageModal = () => {
        stageModal?.classList.add('hidden');
        stageModal?.setAttribute('aria-hidden', 'true');
    };

    const showStageToast = (message = 'Stage updated successfully.') => {
        if (!stageToast) {
            return;
        }
        stageToast.textContent = message;
        stageToast.classList.remove('hidden');
        if (toastTimer) {
            window.clearTimeout(toastTimer);
        }
        toastTimer = window.setTimeout(() => {
            stageToast.classList.add('hidden');
        }, 2400);
    };

    const renderStageProgress = (stages, currentStageId) => {
        if (!stageProgress) {
            return;
        }

        const activePosition = getStagePosition(getActiveStage(currentStageId));

        stageProgress.innerHTML = stages.map((stage, index) => {
            const isCompleted = isCompletedStage(stage, currentStageId);
            const circleClass = isCompleted
                ? 'bg-blue-600 text-white border-blue-600'
                : 'bg-gray-100 text-gray-300 border-gray-200';
            const lineClass = getStagePosition(stage) < activePosition ? 'bg-blue-600' : 'bg-gray-200';

            return `
                <div class="flex min-w-[88px] flex-1 items-start" data-stage-progress-item data-stage-id="${stage.id}" data-stage-position="${getStagePosition(stage)}">
                    <div class="flex w-full flex-col items-center text-center">
                        <span class="mx-auto flex h-8 w-8 items-center justify-center rounded-full border text-xs ${circleClass}">
                            ${isCompleted ? '<i class="fas fa-check text-sm"></i>' : ''}
                        </span>
                        <p class="mt-2 text-xs text-gray-600">${stage.name}</p>
                    </div>
                    ${index < stages.length - 1 ? `<span class="mt-4 hidden h-0.5 flex-1 rounded-full lg:block ${lineClass}"></span>` : ''}
                </div>
            `;
        }).join('');
    };

    const applyStageDisplay = (stage) => {
        if (!stage) {
            return;
        }

        if (stageBadge) {
            stageBadge.className = `inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${stageBadgeClasses[stage.name] || 'bg-gray-100 text-gray-700 border border-gray-200'}`;
            stageBadge.textContent = stage.name;
        }

        if (stageText) {
            stageText.textContent = stage.name;
        }

        renderStageProgress(stageData, stage.id);
    };

    stagePrimary?.addEventListener('click', openStageModal);
    stageModalOverlay?.addEventListener('click', closeStageModal);
    stageModalCancel?.addEventListener('click', closeStageModal);

    const dealServiceMemoViewButton = document.getElementById('dealServiceMemoViewButton');
    const dealServiceMemoModal = document.getElementById('dealServiceMemoModal');
    const dealServiceMemoOverlay = document.getElementById('dealServiceMemoOverlay');
    const dealServiceMemoCloseButton = document.getElementById('dealServiceMemoCloseButton');

    const openDealServiceMemoModal = () => {
        if (!dealServiceMemoModal) {
            return;
        }
        dealServiceMemoModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const closeDealServiceMemoModal = () => {
        if (!dealServiceMemoModal) {
            return;
        }
        dealServiceMemoModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    dealServiceMemoViewButton?.addEventListener('click', openDealServiceMemoModal);
    dealServiceMemoOverlay?.addEventListener('click', closeDealServiceMemoModal);
    dealServiceMemoCloseButton?.addEventListener('click', closeDealServiceMemoModal);

    if (!stageToast?.classList.contains('hidden')) {
        showStageToast(stageToast.textContent.trim() || 'Stage updated successfully.');
    }
});
</script>
@endsection

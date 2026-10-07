@extends('layouts.app')
    @php
        $primaryContact = $deal->dealContacts->firstWhere('is_primary', true) ?: $deal->dealContacts->first();
        $contactEmail = $deal->email ?: ($primaryContact?->email ?: ($deal->contact?->email ?: ''));
        $contactMobile = $deal->mobile_number ?: ($primaryContact?->mobile_number ?: ($deal->contact?->phone ?: ''));
        $meaningfulDealTitle = $deal->deal_title ?: ($deal->company_name ?: ($deal->primary_contact_name ?: 'Deal #' . $deal->id));
        $listValue = function($val) {
            if (is_array($val)) return implode(', ', array_filter($val));
            if (is_string($val)) {
                $d = json_decode($val, true);
                if (is_array($d)) return implode(', ', array_filter($d));
            }
            return (string)$val;
        };
    @endphp

@section('content')

@php
    $users = $users ?? \App\Models\User::with(['employeeProfile'])->get();

    $stages = [
        'Inquiry',
        'Qualification',
        'Consultation',
        'Proposal',
        'Negotiation',
        'Payment',
        'Activation',
        'Closed Won',
        'Closed Lost'
    ];

    $currentStage = $deal->pipeline_stage ?: 'Inquiry';

    $stageIndex = array_search($currentStage, $stages, true);
    $stageIndex = $stageIndex === false ? 0 : $stageIndex;

    $contactName = trim(
        implode(' ', array_filter([
            $deal->salutation,
            $deal->first_name,
            $deal->middle_initial,
            $deal->last_name,
            $deal->name_extension
        ]))
    ) ?: ($deal->primary_contact_name ?: 'Not provided');

    $serviceAreas = $deal->service_areas ?: [];
    $servicesProducts = $deal->services_products ?: [];
    $requirements = $deal->client_requirements ?: [];
    $support = $deal->professional_support_required ?: [];

    $display = fn ($value) =>
        filled($value) ? $value : '-';

    $money = fn ($value) =>
        filled($value)
            ? '₱' . number_format((float) $value, 2)
            : '-';

    $date = fn ($value) =>
        $value ? $value->format('M d, Y') : '-';

    $listValue = function ($value) {
        if (is_array($value)) {
            $items = array_map(function ($item) {
                if (is_array($item)) {
                    return $item['name'] ?? $item['title'] ?? $item['action'] ?? json_encode($item);
                }
                return (string) $item;
            }, $value);

            $filtered = array_filter($items, fn ($i) => filled($i));

            return count($filtered)
                ? implode(', ', $filtered)
                : '-';
        }

        return filled($value) ? $value : '-';
    };

    $totalValue =
        $deal->total_estimated_engagement_value
        ?: $deal->amount;

    $plannedStart =
        $deal->planned_start_date
        ?: null;

    $estimatedCompletion =
        $deal->estimated_completion_date
        ?: null;

    $proposal = $proposal ?? (\App\Models\Proposal::firstOrNew(['deal_id' => $deal->id]));
    $proposalAreas = $deal->service_areas ?: [];
    $proposalItems = $deal->services_products ?: [];

    $proposalServicesTotal = (float) (
        $deal->total_service_fee
        ?: (
            ($deal->est_professional_fee ?: 0)
            + ($deal->est_government_fee ?: 0)
            + ($deal->est_service_support_fee ?: 0)
        )
    );

    $proposalProductsTotal = (float) ($deal->total_product_fee ?: 0);
    $proposalDiscount = (float) ($proposal->discount ?? $deal->discount ?? 0);
    $proposalTax = (float) ($proposal->tax ?? 0);

    $proposalSubtotal = $proposalServicesTotal + $proposalProductsTotal - $proposalDiscount;
    $proposalTotal = $proposalSubtotal + $proposalTax;

    $proposalItemNames = array_values(
        array_filter(
            array_map(
                fn ($item) => is_array($item)
                    ? ($item['name'] ?? null)
                    : $item,
                $proposalItems
            ),
            fn ($value) => filled($value)
        )
    );

    $defaultServiceName = (!empty($proposalItemNames) && count($proposalItemNames))
        ? $proposalItemNames[0]
        : (!empty($proposalAreas) && count($proposalAreas)
            ? (is_array($proposalAreas[0]) ? ($proposalAreas[0]['name'] ?? ($deal->engagement_type ?: 'General Services')) : $proposalAreas[0])
            : ($deal->engagement_type ?: ($deal->deal_title ?: 'General Services')));

    $dealProposalItems = !empty($proposalItemNames) 
        ? $proposalItemNames 
        : ($deal->dealLineItems?->pluck('name')->filter()->values()->toArray() 
            ?: (is_array($deal->services_products) ? $deal->services_products : (json_decode($deal->services_products ?? '', true) ?: [])));

    $startBatchesJson = $deal->startRecords->map(function($record) {
        return [
            'id' => $record->id,
            'start_code' => $record->start_code ?? ('ST-' . date('Y') . '-' . str_pad($record->id, 3, '0', STR_PAD_LEFT)),
            'batch_name' => $record->batch_name ?? 'START Batch',
            'status' => $record->status ?? 'Draft',
            'opened_date' => $record->created_at ? $record->created_at->format('M d, Y') : date('M d, Y'),
            'opened_time' => $record->created_at ? $record->created_at->format('h:i A') : date('h:i A'),
            'activated_items' => $record->activated_items ?? [],
            'service_memo_ref' => $record->service_memo_ref,
            'service_memo_data' => $record->service_memo_data,
            'service_memo_revisions' => $record->service_memo_revisions ?? [],
            'history_logs' => $record->history_logs ?? [],
            'authorized_by' => $record->authorized_by,
            'created_by' => $record->created_by,
            'assignments' => $record->assignments->map(function($a) {
                return [
                    'id' => $a->id,
                    'role' => $a->role,
                    'assignee' => $a->assignee ?? $a->assigned_to,
                    'user_id' => $a->user_id,
                    'status' => $a->status ?? 'Pending',
                    'acknowledged_at' => $a->acknowledged_at ? (is_string($a->acknowledged_at) ? $a->acknowledged_at : $a->acknowledged_at->format('M d, Y h:i A')) : null,
                    'response' => $a->response,
                    'decline_reason' => $a->decline_reason,
                    'is_required' => (bool)$a->is_required,
                    'notes' => $a->notes,
                ];
            })->values(),
        ];
    })->values();

    $startBatchRecordForClearance = $deal->startRecords?->sortByDesc('id')->first();
    $startAssignmentsListForClearance = $startBatchRecordForClearance?->assignments ?? collect();
    if ($startAssignmentsListForClearance->isEmpty() && $startBatchRecordForClearance?->engagementGroups) {
        $startAssignmentsListForClearance = $startBatchRecordForClearance->engagementGroups->flatMap->assignments;
    }

    $clearanceLeadConsultant = $deal->lead_consultant 
        ?: ($startAssignmentsListForClearance->firstWhere('role', 'Lead Consultant')?->assigned_to
            ?? $startAssignmentsListForClearance->firstWhere('role', 'Lead Consultant')?->assignee
            ?? $deal->owner_name 
            ?? 'John Kelly Abalde');

    $clearanceLeadAssociate = $deal->lead_associate 
        ?: ($startAssignmentsListForClearance->firstWhere('role', 'Lead Associate')?->assigned_to
            ?? $startAssignmentsListForClearance->firstWhere('role', 'Lead Associate')?->assignee
            ?? $deal->assigned_associate
            ?? 'Ma. Lourdes T. Mata');

    $clearanceSalesMarketing = $deal->sales_marketing 
        ?: ($startAssignmentsListForClearance->firstWhere('role', 'Sales & Marketing')?->assigned_to
            ?? $startAssignmentsListForClearance->firstWhere('role', 'Sales & Marketing')?->assignee
            ?? $deal->owner_name 
            ?? 'CRISTY ESTRELLA');
@endphp


<style>

    /* =========================================================
       DEAL DETAIL PAGE
       ========================================================= */

    .deal-detail-page {
        min-height: 100%;
        padding: 24px;
        background: #f8fafc;
        color: #172033;
        font-family: inherit;
        overflow-x: hidden;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }

    /* =========================================================
       BREADCRUMB
       ========================================================= */

    .deal-detail-breadcrumb {
        width: 100%;
        margin: 0 0 16px 0;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 18px;
        font-size: 13px;
        color: #475569;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }

    .deal-detail-breadcrumb a {
        color: #334155;
        text-decoration: none;
        font-weight: 600;
    }

    .deal-detail-breadcrumb a:hover {
        color: #1d4ed8;
    }


    /* =========================================================
       HEADER
       ========================================================= */

    /* =========================================================
       DEAL HEADER & SUMMARY CARD (CONDEAL DESIGN)
       ========================================================= */

    .deal-detail-header-wrap {
        width: 100%;
        margin: 0 0 16px 0;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    /* 1. TOP SUMMARY CARD */
    .deal-detail-header-top {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .dh-top-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 24px;
    }

    .dh-left-block {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-width: 0;
    }

    .dh-deal-label {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        letter-spacing: 0.6px;
        text-transform: uppercase;
        margin-bottom: 2px;
        line-height: 1.2;
    }

    .dh-deal-id {
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
        letter-spacing: -0.2px;
    }

    .dh-client-name {
        margin-top: 4px;
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.3;
    }

    .dh-type-badge {
        display: inline-block;
        width: fit-content;
        margin-top: 6px;
        font-size: 11px;
        font-weight: 600;
        color: #0f172a;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        padding: 2px 10px;
        border-radius: 9999px;
        line-height: 1.35;
    }

    /* 2. UNIFIED STATUS ROW */
    .dh-status-row {
        margin-top: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .dh-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 500;
        line-height: 1.3;
        white-space: nowrap;
        background: #f8fafc;
        color: #0f172a;
        border: 1px solid #e2e8f0;
    }

    .dh-badge svg {
        width: 13px;
        height: 13px;
        flex-shrink: 0;
        color: #64748b;
    }

    /* 3. TOP RIGHT TIMER BOX */
    .dh-timer-box {
        background: #f8fbff;
        border: 1px solid #d0e1fd;
        border-radius: 10px;
        padding: 10px 18px;
        min-width: 165px;
        text-align: center;
        flex-shrink: 0;
        box-shadow: 0 1px 3px rgba(37, 99, 235, 0.05);
    }

    .dh-timer-stage {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        margin-bottom: 2px;
    }

    .dh-timer-display {
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
        letter-spacing: 0.5px;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .dh-timer-date {
        margin-top: 4px;
        font-size: 11.5px;
        font-weight: 500;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
    }

    .dh-timer-date svg {
        width: 12px;
        height: 12px;
        color: #64748b;
        flex-shrink: 0;
    }

    /* 4. 5-COLUMN CARDS GRID */
    .dh-cards-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 12px;
    }

    .dh-card {
        background: #f8fbff;
        border: 1px solid #d0e1fd;
        border-radius: 10px;
        padding: 14px 18px;
        box-shadow: 0 1px 3px rgba(37, 99, 235, 0.05);
        display: flex;
        flex-direction: column;
        min-height: 98px;
        box-sizing: border-box;
    }

    .dh-card-header {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 5px;
        line-height: 1.2;
    }

    .dh-card-title {
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }

    .dh-card-sub {
        font-size: 11.5px;
        font-weight: 400;
        color: #64748b;
        margin-top: 3px;
        line-height: 1.35;
    }

    .dh-card-meta {
        font-size: 11.5px;
        font-weight: 400;
        color: #64748b;
        margin-top: 2px;
        line-height: 1.35;
    }

    .dh-card-value {
        font-size: 19px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.25;
        margin-top: 3px;
        font-variant-numeric: tabular-nums;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .dh-value-toggle-btn {
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 2px;
        border-radius: 4px;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: color 0.15s ease, background-color 0.15s ease;
    }

    .dh-value-toggle-btn:hover {
        color: #0f172a;
        background: rgba(100, 116, 139, 0.12);
    }

    .dh-eye-icon {
        width: 14px;
        height: 14px;
        stroke-width: 2;
    }

    /* 5. DEAL STAGE PROGRESS CARD */
    .dh-stage-progress-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px 24px 22px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .dh-stage-progress-title {
        font-size: 11px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        margin-bottom: 22px;
    }

    .dh-pipeline-container {
        width: 100%;
        overflow-x: auto;
        padding: 4px 0 6px;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .dh-pipeline-container::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }

    .dh-pipeline {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        width: 100%;
        position: relative;
    }

    .dh-pipe-step {
        position: relative;
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    /* Horizontal connector line */
    .dh-pipe-step:not(:last-child)::after {
        content: "";
        position: absolute;
        top: 13px;
        left: 50%;
        width: 100%;
        height: 2px;
        background: #e2e8f0;
        z-index: 1;
    }

    .dh-pipe-step.is-complete:not(:last-child)::after {
        background: #2563eb;
    }

    .dh-pipe-node {
        position: relative;
        z-index: 2;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        background: #ffffff;
        border: 2px solid #cbd5e1;
        color: #64748b;
        transition: all 0.2s ease;
    }

    .dh-pipe-current-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: #2563eb;
        display: block;
    }

    .dh-pipe-step.is-complete .dh-pipe-node {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
        font-size: 13px;
    }

    .dh-pipe-step.is-current .dh-pipe-node {
        background: #ffffff;
        border: 2.5px solid #2563eb;
        color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
    }

    .dh-pipe-label {
        margin-top: 8px;
        font-size: 11.5px;
        font-weight: 500;
        color: #64748b;
        white-space: nowrap;
    }

    .dh-pipe-duration {
        margin-top: 2px;
        font-size: 11px;
        font-weight: 500;
        color: #64748b;
        white-space: nowrap;
    }

    .dh-pipe-step.is-complete .dh-pipe-label {
        font-weight: 600;
        color: #1e293b;
    }

    .dh-pipe-step.is-current .dh-pipe-label {
        font-weight: 700;
        color: #2563eb;
    }

    .dh-pipe-step.is-current .dh-pipe-duration {
        font-weight: 600;
        color: #2563eb;
    }

    /* RESPONSIVE */
    @media (max-width: 1024px) {
        .dh-cards-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .dh-top-row {
            flex-direction: column;
        }
        .dh-timer-box {
            width: 100%;
        }
        .dh-cards-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 600px) {
        .deal-detail-header-top,
        .dh-stage-progress-card {
            padding: 16px;
        }
        .dh-cards-grid {
            grid-template-columns: 1fr;
        }
    }


    /* =========================================================
       DEAL NAVIGATION TABS (STANDALONE CARD)
       ========================================================= */

    .deal-nav-tabs-wrapper {
        width: 100%;
        margin: 0 0 16px 0;
        display: flex;
        align-items: stretch;
    }

    .deal-nav-bar-container {
        display: flex;
        align-items: center;
        width: 100%;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 6px 12px 0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        position: relative;
        box-sizing: border-box;
    }

    .deal-nav-bar-scroll {
        overflow-x: auto;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        display: flex;
        align-items: flex-end;
        flex: 1 1 auto;
        width: 100%;
        min-width: 0;
    }

    .deal-nav-bar-scroll::-webkit-scrollbar {
        display: none;
    }

    
    /* DEAL TAB PANELS */
    .deal-tab-panel {
        display: none;
    }
    .deal-tab-panel.active {
        display: block;
    }

    .deal-nav-bar {
        display: flex;
        align-items: flex-end;
        width: 100%;
        min-width: max-content;
        gap: 2px;
        flex-wrap: nowrap;
        position: relative;
    }

    .deal-tab-arrow {
        display: none;
        appearance: none;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #475569;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        margin-bottom: 4px;
        padding: 0;
        transition: all 0.15s ease;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        z-index: 2;
    }

    .deal-tab-arrow:hover {
        background: #f1f5f9;
        color: #1e293b;
        border-color: #cbd5e1;
    }

    .deal-tab-arrow-left {
        margin-right: 6px;
    }

    .deal-tab-arrow-right {
        margin-left: 6px;
    }

    .deal-nav-item {
        appearance: none;
        background: transparent;
        border: none;
        outline: none;
        cursor: pointer;
        padding: 9px 16px 10px;
        font-size: 13.5px;
        font-weight: 500;
        color: #3b506c;
        border-radius: 6px 6px 0 0;
        position: relative;
        transition: all 0.15s ease-in-out;
        white-space: nowrap;
        user-select: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        border-bottom: 2.5px solid transparent;
        flex: 1 1 0px;
        min-width: max-content;
        text-align: center;
    }

    .deal-nav-item:hover {
        color: #1e293b;
        background: #f8fafc;
    }

    .deal-nav-item.active {
        background: #e8f1fd;
        color: #2563eb;
        font-weight: 600;
        border-bottom: 2.5px solid #2563eb;
    }

    /* =========================================================
       INQUIRY RECORDS SECTION & MODALS
       ========================================================= */

    /* INQUIRY QUICK ACTIONS */
    .inquiry-quick-actions-row {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn-inquiry-qa {
        appearance: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 9px 18px;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        flex: 1 1 auto;
        min-width: 120px;
        text-decoration: none;
        box-sizing: border-box;
    }

    .btn-inquiry-qa.primary {
        background: #2563eb;
        color: #ffffff;
        border: 1px solid #2563eb;
        box-shadow: 0 1px 2px rgba(37, 99, 235, 0.2);
    }

    .btn-inquiry-qa.primary:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    .btn-inquiry-qa.secondary {
        background: #ffffff;
        color: #334155;
        border: 1px solid #cbd5e1;
    }

    .btn-inquiry-qa.secondary:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
    }

    /* =========================================================
       INQUIRY RECORDS SECTION (MATCHED DESIGN SYSTEM)
       ========================================================= */

    #tab-panel-inquiry {
        color: #0f172a;
    }

    .inquiry-records-wrapper {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px 28px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .inquiry-main-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    .inquiry-main-title {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        line-height: 1.3;
    }

    .inquiry-main-subtitle {
        font-size: 13.5px;
        font-weight: 400;
        color: #64748b;
        margin: 4px 0 0;
        line-height: 1.4;
    }

    .btn-add-inquiry-primary {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #2563eb;
        color: #ffffff;
        border: 1px solid #2563eb;
        border-radius: 6px;
        padding: 8px 16px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(37, 99, 235, 0.15);
        transition: all 0.15s ease;
    }

    .btn-add-inquiry-primary:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    /* SEARCH & FILTER BAR */
    .inquiry-controls-bar {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    .inquiry-search-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 9px 14px;
        flex: 1;
        min-width: 220px;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .inquiry-search-wrapper:focus-within {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
    }

    .inquiry-search-input {
        border: none;
        outline: none;
        background: transparent;
        font-size: 14px;
        font-weight: 400;
        color: #0f172a;
        width: 100%;
    }

    .inquiry-search-input::placeholder {
        font-size: 14px;
        font-weight: 400;
        color: #94a3b8;
    }

    .inquiry-type-filter-select {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 9px 16px;
        font-size: 14px;
        font-weight: 400;
        color: #334155;
        outline: none;
        cursor: pointer;
        min-width: 165px;
        transition: border-color 0.15s ease;
    }

    .inquiry-type-filter-select option {
        font-size: 14px;
        font-weight: 400;
        color: #334155;
    }

    .inquiry-type-filter-select:focus {
        border-color: #2563eb;
    }

    /* INQUIRY CARDS */
    .inquiry-cards-container {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .inquiry-record-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 20px 22px;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        position: relative;
    }

    .inquiry-record-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
    }

    .inquiry-card-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-bottom: 2px;
    }

    .inquiry-card-title-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .inquiry-card-title {
        font-size: 16px;
        font-weight: 700;
        line-height: 1.3;
        color: #0f172a;
        margin: 0;
    }

    .inquiry-type-tag {
        font-size: 12px;
        font-weight: 600;
        padding: 2px 10px;
        border-radius: 9999px;
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid #dbeafe;
        line-height: 1.4;
    }

    .inquiry-type-tag.tag-product {
        background: #f0fdf4;
        color: #16a34a;
        border-color: #dcfce7;
    }

    .inquiry-type-tag.tag-support {
        background: #fdf4ff;
        color: #9333ea;
        border-color: #fae8ff;
    }

    .inquiry-type-tag.tag-general {
        background: #f8fafc;
        color: #475569;
        border-color: #e2e8f0;
    }

    .inquiry-card-subline {
        font-size: 13.5px;
        font-weight: 400;
        color: #64748b;
        margin-top: 4px;
        margin-bottom: 10px;
    }

    .inquiry-card-content {
        font-size: 14px;
        font-weight: 400;
        line-height: 1.5;
        color: #334155;
        margin-bottom: 12px;
        white-space: pre-wrap;
    }

    .inquiry-card-meta-row {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 14px;
        flex-wrap: wrap;
        font-size: 13.5px;
        color: #475569;
    }

    .inquiry-meta-item {
        font-size: 13.5px;
        color: #475569;
    }

    .inquiry-meta-item strong {
        font-weight: 700;
        color: #0f172a;
        margin-right: 4px;
    }

    .inquiry-card-footer-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding-top: 14px;
        border-top: 1px solid #f1f5f9;
        flex-wrap: wrap;
    }

    .inquiry-card-author-text {
        font-size: 13px;
        font-weight: 400;
        color: #64748b;
    }

    .inquiry-card-btn-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-inquiry-view-action {
        appearance: none;
        background: #2563eb;
        color: #ffffff;
        border: 1px solid #2563eb;
        border-radius: 6px;
        padding: 7px 18px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .btn-inquiry-view-action:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    .btn-inquiry-edit-action {
        appearance: none;
        background: #ffffff;
        color: #334155;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 6px 14px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .btn-inquiry-edit-action:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }

    .btn-inquiry-more-action {
        appearance: none;
        background: #ffffff;
        color: #64748b;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.15s ease;
        line-height: 1;
        padding: 0;
    }

    .btn-inquiry-more-action:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #0f172a;
    }

    /* EMPTY STATE */
    .inquiry-empty-box {
        text-align: center;
        padding: 48px 24px;
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .inquiry-empty-title {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 6px 0;
    }

    .inquiry-empty-desc {
        font-size: 13.5px;
        color: #64748b;
        margin: 0;
        max-width: 420px;
    }

    /* ANCHORED CONTEXT MENU */
    .inquiry-context-menu {
        position: fixed;
        background: #ffffff;
        border: 1px solid #dce5f0;
        border-radius: 8px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
        padding: 5px;
        min-width: 130px;
        z-index: 1050;
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-family: inherit;
    }

    .inquiry-context-menu-item {
        appearance: none;
        background: transparent;
        border: none;
        border-radius: 5px;
        padding: 8px 12px;
        text-align: left;
        font-family: inherit;
        font-size: 16px;
        font-weight: 400;
        line-height: 26px;
        color: #334155;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background 0.12s ease;
    }

    .inquiry-context-menu-item:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .inquiry-context-menu-item.danger {
        color: #dc2626;
    }

    .inquiry-context-menu-item.danger:hover {
        background: #fee2e2;
        color: #b91c1c;
    }

    /* MODALS */
    .inquiry-modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(2px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 16px;
    }

    .inquiry-modal-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        width: 100%;
        max-width: 580px;
        max-height: 90vh;
        overflow-y: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
        display: flex;
        flex-direction: column;
        animation: modalFadeIn 0.15s ease-out;
    }

    .inquiry-modal-card::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }

    .inquiry-modal-body-content {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .inquiry-modal-body-content::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }

    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.97); }
        to { opacity: 1; transform: scale(1); }
    }

    .inquiry-modal-card.confirm-dialog {
        max-width: 420px;
    }

    .inquiry-modal-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 22px;
        border-bottom: 1px solid #f1f5f9;
    }

    .inquiry-modal-heading {
        font-size: 17px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }

    .inquiry-modal-close-btn {
        background: transparent;
        border: none;
        font-size: 20px;
        line-height: 1;
        color: #94a3b8;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: color 0.15s ease;
    }

    .inquiry-modal-close-btn:hover {
        color: #0f172a;
    }

    .inquiry-modal-body-content {
        padding: 22px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .inquiry-form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .inquiry-form-label {
        font-size: 13px;
        font-weight: 600;
        color: #1e293b;
    }

    .inquiry-form-label .req-star {
        color: #ef4444;
    }

    .inquiry-form-input,
    .inquiry-form-select,
    .inquiry-form-textarea {
        width: 100%;
        border: 1px solid #dce5f0;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 13.5px;
        color: #0f172a;
        background: #ffffff;
        outline: none;
        font-family: inherit;
        box-sizing: border-box;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .inquiry-form-input:focus,
    .inquiry-form-select:focus,
    .inquiry-form-textarea:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .inquiry-form-select optgroup {
        font-weight: 700;
        font-style: normal;
        color: #0f172a;
        background: #f8fafc;
        padding: 5px 6px;
    }

    .inquiry-form-select option {
        font-weight: 400;
        color: #334155;
        background: #ffffff;
        padding: 5px 12px;
    }

    .inquiry-form-textarea {
        resize: vertical;
        min-height: 90px;
    }

    .inquiry-modal-grid-2 {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    @media (max-width: 600px) {
        .inquiry-modal-grid-2 {
            grid-template-columns: 1fr;
        }
    }

    .inquiry-modal-foot {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
        padding: 16px 22px;
        border-top: 1px solid #f1f5f9;
        background: #f8fafc;
        border-radius: 0 0 12px 12px;
    }

    .btn-modal-cancel {
        appearance: none;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #334155;
        border-radius: 8px;
        padding: 8px 18px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .btn-modal-cancel:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }

    .btn-modal-save {
        appearance: none;
        background: #2563eb;
        border: 1px solid #2563eb;
        color: #ffffff;
        border-radius: 8px;
        padding: 8px 20px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(37, 99, 235, 0.2);
        transition: all 0.15s ease;
    }

    .btn-modal-save:hover {
        background: #1d4ed8;
    }

    .btn-modal-danger {
        appearance: none;
        background: #dc2626;
        border: 1px solid #dc2626;
        color: #ffffff;
        border-radius: 8px;
        padding: 8px 20px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .btn-modal-danger:hover {
        background: #b91c1c;
    }

    /* VIEW DETAILS GROUP */
    .inquiry-view-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .inquiry-view-label {
        font-size: 11.5px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .inquiry-view-value {
        font-size: 14px;
        color: #0f172a;
        font-weight: 500;
    }

    .inquiry-view-value.highlight {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
    }

    /* =========================================================
       CONSULTATION TAB STYLES
       ========================================================= */

    .consultation-main-wrapper {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .consultation-records-wrapper,
    .consultation-attachments-wrapper {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px 28px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .consultation-qa-top-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 20px;
        padding-bottom: 18px;
        border-bottom: 1px solid #f1f5f9;
    }

    .btn-consultation-qa {
        appearance: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 18px;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
        box-sizing: border-box;
        font-family: inherit;
    }

    .btn-consultation-qa.primary {
        background: #2563eb;
        color: #ffffff;
        border: 1px solid #2563eb;
        box-shadow: 0 1px 2px rgba(37, 99, 235, 0.2);
    }

    .btn-consultation-qa.primary:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    .btn-consultation-qa.secondary {
        background: #ffffff;
        color: #334155;
        border: 1px solid #cbd5e1;
    }

    .btn-consultation-qa.secondary:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
    }

    .consultation-section-title {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 16px;
        letter-spacing: -0.1px;
    }

    .consultation-cards-container {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .consultation-record-card {
        border: 1px solid #dce5f0;
        border-radius: 10px;
        background: #ffffff;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .consultation-record-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.04);
    }

    .consultation-card-header-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 6px;
    }

    .consultation-card-title {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        line-height: 1.3;
    }

    .consultation-card-subline {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 14px;
    }

    .consultation-notes-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px 16px;
        margin-bottom: 14px;
    }

    .consultation-notes-title {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-bottom: 6px;
    }

    .consultation-notes-body {
        font-size: 13.5px;
        line-height: 1.55;
        color: #334155;
        white-space: pre-wrap;
    }

    .consultation-card-files {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
    }

    .consultation-file-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 12px;
        font-weight: 500;
        color: #1e293b;
    }

    .consultation-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 12px;
        border-top: 1px solid #f1f5f9;
        flex-wrap: wrap;
        gap: 10px;
    }

    .consultation-meta-author {
        font-size: 12px;
        color: #94a3b8;
    }

    .consultation-card-actions {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .btn-consultation-action {
        appearance: none;
        background: #2563eb;
        border: 1px solid #2563eb;
        border-radius: 6px;
        padding: 6px 14px;
        font-size: 12.5px;
        font-weight: 600;
        color: #ffffff;
        cursor: pointer;
        transition: all 0.15s ease;
        font-family: inherit;
        line-height: 1.3;
    }

    .btn-consultation-action:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #ffffff;
    }

    .btn-consultation-more {
        appearance: none;
        background: #ffffff;
        border: 1px solid #dce5f0;
        border-radius: 6px;
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        font-weight: 700;
        color: #64748b;
        cursor: pointer;
        transition: all 0.15s ease;
        line-height: 1;
        padding: 0;
    }

    .btn-consultation-more:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #0f172a;
    }

    /* ATTACHMENTS LIST */
    .attachment-items-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .attachment-row-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        gap: 16px;
        transition: border-color 0.15s ease;
    }

    .attachment-row-card:hover {
        border-color: #cbd5e1;
    }

    .attachment-left {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .attachment-file-icon {
        width: 34px;
        height: 34px;
        background: #e0f2fe;
        color: #0284c7;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .attachment-details {
        min-width: 0;
    }

    .attachment-filename {
        font-size: 13.5px;
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 3px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .attachment-meta {
        font-size: 12px;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn-attachment-download {
        appearance: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 6px 14px;
        font-size: 12.5px;
        font-weight: 600;
        color: #2563eb;
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
        flex-shrink: 0;
        font-family: inherit;
    }

    .btn-attachment-download:hover {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #1d4ed8;
    }

    .upload-dropzone {
        border: 2px dashed #cbd5e1;
        border-radius: 8px;
        padding: 24px;
        text-align: center;
        background: #f8fafc;
        cursor: pointer;
        transition: border-color 0.15s ease, background 0.15s ease;
    }

    /* =========================================================
       SERVICES & PRICING TAB (DEAL LINE ITEMS)
       ========================================================= */

    .deal-line-items-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px 28px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .dli-header-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    .dli-title-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .dli-title {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        line-height: 1.3;
    }

    .dli-subtitle {
        font-size: 13.5px;
        font-weight: 400;
        color: #64748b;
        margin: 4px 0 0;
        line-height: 1.4;
    }

    .btn-add-line-item {
        appearance: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #2563eb;
        color: #ffffff;
        border: 1px solid #2563eb;
        border-radius: 8px;
        padding: 8px 18px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        box-shadow: 0 1px 2px rgba(37, 99, 235, 0.2);
        font-family: inherit;
    }

    .btn-add-line-item:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    /* TABLE LAYOUT */
    .dli-table-responsive {
        width: 100%;
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 24px;
        background: #ffffff;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE / Edge */
    }

    .dli-table-responsive::-webkit-scrollbar {
        display: none; /* Chrome, Safari, Opera */
        width: 0;
        height: 0;
    }

    .dli-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 820px;
        font-size: 12.5px;
        text-align: left;
    }

    .dli-table thead tr {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }

    .dli-table th {
        padding: 11px 8px;
        font-size: 11px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
        vertical-align: middle;
        box-sizing: border-box;
    }

    .dli-table td {
        padding: 12px 8px;
        color: #1e293b;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        box-sizing: border-box;
        line-height: 1.35;
    }

    .dli-table tbody tr:last-child td {
        border-bottom: none;
    }

    .dli-table tbody tr:hover {
        background: #fbfcfe;
    }

    /* Column Specific Alignments & Widths */
    .dli-col-type {
        text-align: center !important;
        width: 80px;
    }

    .dli-col-name {
        text-align: left !important;
        font-weight: 600;
        color: #0f172a;
        min-width: 130px;
        max-width: 190px;
    }

    .dli-col-desc {
        text-align: left !important;
        color: #64748b;
        min-width: 90px;
        max-width: 150px;
    }

    .dli-col-qty {
        text-align: center !important;
        width: 65px;
        white-space: nowrap;
        font-weight: 500;
    }

    .dli-col-price {
        text-align: right !important;
        width: 95px;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
        font-weight: 500;
    }

    .dli-col-discount {
        text-align: right !important;
        width: 85px;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
        color: #64748b;
    }

    .dli-col-tax {
        text-align: right !important;
        width: 65px;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
        color: #64748b;
    }

    .dli-col-total {
        text-align: right !important;
        width: 95px;
        white-space: nowrap;
        font-weight: 700;
        color: #0f172a;
        font-variant-numeric: tabular-nums;
    }

    .dli-col-billing {
        text-align: left !important;
        min-width: 110px;
        max-width: 140px;
        font-size: 12px;
        color: #475569;
    }

    .dli-col-route {
        text-align: center !important;
        width: 75px;
    }

    .dli-col-actions {
        text-align: center !important;
        width: 105px;
        white-space: nowrap;
    }

    /* Badges */
    .dli-type-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .dli-type-badge.service {
        background: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
    }

    .dli-type-badge.product {
        background: #fef3c7;
        color: #b45309;
        border: 1px solid #fde68a;
    }

    .dli-route-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }

    .dli-route-badge.project {
        background: #ede9fe;
        color: #6d28d9;
        border-color: #ddd6fe;
    }

    /* Action Buttons */
    .btn-dli-edit {
        appearance: none;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #334155;
        border-radius: 5px;
        padding: 4px 9px;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        margin-right: 4px;
        font-family: inherit;
    }

    .btn-dli-edit:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
    }

    .btn-dli-delete {
        appearance: none;
        background: #ffffff;
        border: 1px solid #fecaca;
        color: #dc2626;
        border-radius: 5px;
        padding: 4px 9px;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        font-family: inherit;
    }

    .btn-dli-delete:hover {
        background: #fef2f2;
        border-color: #f87171;
        color: #b91c1c;
    }

    /* PRICING SUMMARY 4-GRID */
    .dli-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        border-top: 1px solid #f1f5f9;
        padding-top: 20px;
    }

    .dli-summary-block {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .dli-summary-block.total-block {
        background: #f8fafc;
        border-color: #e2e8f0;
    }

    .dli-summary-label {
        font-size: 10.5px;
        font-weight: 800;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .dli-summary-block.total-block .dli-summary-label {
        color: #64748b;
    }

    .dli-summary-value {
        font-size: 17px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.2px;
        font-variant-numeric: tabular-nums;
    }

    .dli-summary-block.total-block .dli-summary-value {
        color: #0f172a;
    }

    .dli-summary-block.discount-block .dli-summary-value {
        color: #0f172a;
    }

    @media (max-width: 900px) {
        .dli-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 500px) {
        .dli-summary-grid {
            grid-template-columns: 1fr;
        }
    }

    /* =========================================================
       PROPOSAL WORKSPACE INTEGRATION CARD (TOP BANNER)
       ========================================================= */
    .proposal-workspace-integration-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px 28px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        box-sizing: border-box;
        width: 100%;
    }

    .pwi-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    .pwi-title-group h2 {
        margin: 0;
        color: #0f172a;
        font-size: 18px;
        font-weight: 700;
        line-height: 1.3;
    }

    .pwi-title-group p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 13.5px;
        font-weight: 400;
        line-height: 1.4;
    }

    .pwi-btn-workspace {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: #1e4f95;
        color: #ffffff !important;
        padding: 9px 18px;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.15s ease;
        box-shadow: 0 1px 3px rgba(30, 79, 149, 0.25);
    }

    .pwi-btn-workspace:hover {
        background: #163e75;
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(30, 79, 149, 0.3);
    }

    .pwi-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px 36px;
        margin-bottom: 24px;
        padding-bottom: 22px;
        border-bottom: 1px solid #edf2f7;
    }

    .pwi-meta-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .pwi-meta-label {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
    }

    .pwi-meta-value {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pwi-meta-value.price {
        color: #0f172a;
        font-size: 14px;
        font-weight: 700;
    }

    .pwi-badge-version {
        display: inline-block;
        background: #1e4f95;
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 6px;
        letter-spacing: 0.5px;
        line-height: 1.2;
    }

    .pwi-badge-status {
        display: inline-block;
        background: #fff4c7;
        border: 1px solid #f4d46b;
        color: #9a6700;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 12px;
        border-radius: 14px;
        line-height: 1.2;
    }

    .pwi-ledger-title {
        font-size: 12px;
        font-weight: 800;
        color: #475569;
        letter-spacing: 0.6px;
        text-transform: uppercase;
        margin: 0 0 14px;
    }

    .pwi-ledger-table-wrap {
        width: 100%;
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .pwi-ledger-table-wrap::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }

    .pwi-ledger-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
    }

    .pwi-ledger-table th {
        padding: 10px 12px;
        text-align: left;
        color: #64748b;
        font-weight: 700;
        font-size: 11.5px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }

    .pwi-ledger-table th.th-amount {
        text-align: right;
    }

    .pwi-ledger-table th.th-center {
        text-align: center;
    }

    .pwi-ledger-table td {
        padding: 14px 12px;
        border-bottom: 1px solid #edf2f7;
        color: #1e293b;
        vertical-align: middle;
    }

    .pwi-ledger-table td.td-amount {
        text-align: right;
        font-weight: 700;
        color: #0f172a;
        font-size: 13.5px;
    }

    .pwi-ledger-table td.td-center {
        text-align: center;
    }

    .pwi-item-name {
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 4px;
        font-size: 13.5px;
    }

    .pwi-item-code {
        font-size: 11.5px;
        font-weight: 500;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-decoration: none;
        transition: color 0.15s ease;
    }

    .pwi-item-code:hover {
        color: #0f172a;
        text-decoration: underline;
    }

    .pwi-pill-payment {
        display: inline-block;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-size: 11px;
        font-weight: 600;
        padding: 4px 11px;
        border-radius: 12px;
    }

    .pwi-pill-business {
        display: inline-block;
        background: #e0f2fe;
        border: 1px solid #bae6fd;
        color: #0369a1;
        font-size: 11.5px;
        font-weight: 700;
        padding: 5px 14px;
        border-radius: 14px;
    }

    .pwi-pill-activation {
        display: inline-block;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #64748b;
        font-size: 11px;
        font-weight: 600;
        padding: 4px 11px;
        border-radius: 12px;
    }

    /* =========================================================
       UNIVERSAL CLIENT ACTIONS (REFINED UI/UX)
       ========================================================= */
    .universal-client-actions-card {
        background: #ffffff;
        border: 1px solid #dce5f0;
        border-radius: 12px;
        padding: 22px 26px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        box-sizing: border-box;
        width: 100%;
    }

    .uca-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    .uca-title-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .uca-title-row {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .uca-header-icon-box {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        color: #1e4f95;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .uca-title-group h2 {
        margin: 0;
        color: #07162d;
        font-size: 19px;
        font-weight: 700;
        letter-spacing: -0.2px;
        line-height: 1.2;
    }

    .uca-title-group p {
        margin: 0;
        color: #64748b;
        font-size: 12.5px;
        font-weight: 500;
        padding-left: 41px;
    }

    .uca-btn-dispatch {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #244f91;
        color: #ffffff !important;
        padding: 8px 16px;
        border: 1px solid #244f91;
        border-radius: 7px;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        box-shadow: 0 1px 3px rgba(36, 79, 145, 0.2);
        font-family: inherit;
    }

    .uca-btn-dispatch:hover {
        background: #1d437d;
        border-color: #1d437d;
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(36, 79, 145, 0.25);
    }

    /* EMPTY STATE */
    .uca-empty-panel {
        background: #f8fbff;
        border: 1px solid #e0ecfb;
        border-radius: 10px;
        padding: 44px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    .uca-empty-icon-circle {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
    }

    .uca-empty-heading {
        margin: 0 0 5px;
        color: #0f2747;
        font-size: 15px;
        font-weight: 700;
    }

    .uca-empty-subtext {
        margin: 0;
        color: #64748b;
        font-size: 13px;
        font-weight: 500;
    }

    /* ACTION CARDS LIST */
    .uca-actions-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        width: 100%;
    }

    .uca-action-card {
        background: #ffffff;
        border: 1px solid #dce5f0;
        border-radius: 9px;
        padding: 16px 18px;
        transition: all 0.15s ease;
        display: flex;
        flex-direction: column;
        gap: 10px;
        box-sizing: border-box;
    }

    .uca-action-card:hover {
        border-color: #bcd0e8;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
    }

    .uca-action-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .uca-action-code {
        font-size: 11.5px;
        font-weight: 700;
        color: #64748b;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .uca-badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        font-size: 11px;
        font-weight: 700;
        border-radius: 12px;
        line-height: 1.2;
    }

    .uca-badge-pending {
        background: #fef3c7;
        border: 1px solid #fde68a;
        color: #92400e;
    }

    .uca-badge-completed {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
    }

    .uca-badge-progress {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
    }

    .uca-action-card-title {
        font-size: 15px;
        font-weight: 700;
        color: #07162d;
        line-height: 1.3;
    }

    .uca-action-card-body {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
    }

    .uca-meta-group {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .uca-meta-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #475569;
        font-size: 12px;
        font-weight: 500;
    }

    .uca-meta-item svg {
        color: #64748b;
        flex: 0 0 auto;
    }

    .uca-action-card-btns {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-left: auto;
    }

    .uca-btn-view {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #ffffff;
        color: #334155;
        border: 1px solid #cbd8e8;
        border-radius: 6px;
        padding: 6px 14px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        font-family: inherit;
    }

    .uca-btn-view:hover {
        background: #f8fbff;
        border-color: #94b6e8;
        color: #1e4f95;
    }

    .uca-btn-more {
        appearance: none;
        background: #ffffff;
        border: 1px solid #cbd8e8;
        border-radius: 6px;
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        font-weight: 700;
        color: #64748b;
        cursor: pointer;
        transition: all 0.15s ease;
        line-height: 1;
        padding: 0;
    }

    .uca-btn-more:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #0f172a;
    }

    @media (max-width: 640px) {
        .uca-title-group p {
            padding-left: 0;
        }

        .uca-action-card-body {
            flex-direction: column;
            align-items: flex-start;
        }

        .uca-action-card-btns {
            margin-left: 0;
            width: 100%;
            justify-content: flex-end;
        }
    }

    /* =========================================================
       FINANCE TAB & MODULE STYLES (MATCHING DEAL DESIGN SYSTEM)
       ========================================================= */
    .finance-details-card,
    .finance-allocation-card,
    .finance-ledger-card {
        background: #ffffff;
        border: 1px solid #dce5f0;
        border-radius: 12px;
        padding: 22px 26px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        box-sizing: border-box;
        width: 100%;
    }

    .finance-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .finance-title-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .finance-title-row {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .finance-header-icon-box {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        color: #2563eb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .finance-title-row h2 {
        margin: 0;
        color: #0f172a;
        font-size: 18px;
        font-weight: 700;
        line-height: 1.3;
    }

    .finance-title-group p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 13.5px;
        font-weight: 400;
        line-height: 1.4;
        padding-left: 0;
    }

    .finance-btn-record-ledger {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #1d4ed8;
        color: #ffffff !important;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        font-family: inherit;
        box-shadow: 0 1px 3px rgba(29, 78, 216, 0.2);
    }

    .finance-btn-record-ledger:hover {
        background: #1e40af;
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(29, 78, 216, 0.3);
    }

    .finance-method-pill {
        display: inline-block;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        color: #2563eb;
        font-size: 12px;
        font-weight: 600;
        padding: 5px 14px;
        border-radius: 16px;
        white-space: nowrap;
        line-height: 1.2;
    }

    .finance-btn-delete-ledger {
        background: transparent;
        border: none;
        color: #ef4444;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        padding: 0;
        transition: color 0.15s ease;
        font-family: inherit;
        white-space: nowrap;
    }

    .finance-btn-delete-ledger:hover {
        color: #dc2626;
        text-decoration: underline;
    }

    /* PAYMENT ALLOCATION RECORDS LEDGER - STABLE TABLE LAYOUT */
    .finance-ledger-table-wrap {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scroll-behavior: smooth;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .finance-ledger-table-wrap::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }

    .finance-ledger-table {
        width: 100%;
        min-width: 1020px;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .finance-ledger-table th {
        padding: 13px 14px;
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
        white-space: nowrap;
        vertical-align: middle;
        box-sizing: border-box;
    }

    .finance-ledger-table th.th-action {
        text-align: center;
    }

    .finance-ledger-table td {
        padding: 14px 14px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        box-sizing: border-box;
        font-size: 12.5px;
        color: #1e293b;
    }

    .finance-ledger-table tr:last-child td {
        border-bottom: none;
    }

    .finance-ledger-table tr:hover td {
        background: #fcfdfe;
    }

    /* Column Sizing Strategy */
    .col-ledger-date { width: 110px; min-width: 110px; }
    .col-ledger-ref { width: 160px; min-width: 160px; }
    .col-ledger-scope { width: 190px; min-width: 190px; }
    .col-ledger-method { width: 150px; min-width: 150px; }
    .col-ledger-amount { width: 160px; min-width: 160px; }
    .col-ledger-recorded { width: 160px; min-width: 160px; }
    .col-ledger-notes { width: 100px; min-width: 100px; }
    .col-ledger-action { width: 90px; min-width: 90px; }

    @media (max-width: 640px) {
        .finance-title-group p {
            padding-left: 0;
        }
    }

    /* NOTIFY FINANCE WORKFLOW DROPDOWN & STATUS */
    .finance-notify-wrapper {
        position: relative;
        display: inline-block;
    }

    .finance-notify-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 7px;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        box-shadow: 0 1px 3px rgba(36, 79, 145, 0.2);
        font-family: inherit;
        border: 1px solid transparent;
    }

    .finance-notify-btn.btn-notify-initial {
        background: #244f91;
        color: #ffffff !important;
        border-color: #244f91;
    }

    .finance-notify-btn.btn-notify-initial:hover {
        background: #1d437d;
        border-color: #1d437d;
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(36, 79, 145, 0.25);
    }

    .finance-notify-btn.btn-notify-sent {
        background: #ecfdf5;
        color: #065f46 !important;
        border-color: #a7f3d0;
        box-shadow: 0 1px 2px rgba(6, 95, 70, 0.1);
    }

    .finance-notify-btn.btn-notify-sent:hover {
        background: #d1fae5;
    }

    .finance-notify-btn.btn-notify-payment-ready {
        background: #1e4f95;
        color: #ffffff !important;
        border-color: #1e4f95;
        animation: pulseSubtle 2s infinite ease-in-out;
    }

    .finance-notify-btn.btn-notify-payment-ready:hover {
        background: #163e75;
        transform: translateY(-1px);
    }

    .finance-notify-btn.btn-notify-payment-sent {
        background: #f0fdf4;
        color: #15803d !important;
        border-color: #bbf7d0;
    }

    .finance-notify-btn.btn-notify-payment-sent:hover {
        background: #dcfce7;
    }

    @keyframes pulseSubtle {
        0%, 100% { box-shadow: 0 1px 3px rgba(30, 79, 149, 0.25); }
        50% { box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2); }
    }

    .finance-notify-menu {
        position: absolute;
        right: 0;
        top: calc(100% + 6px);
        width: 290px;
        background: #ffffff;
        border: 1px solid #dce5f0;
        border-radius: 10px;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.12), 0 8px 10px -6px rgba(15, 23, 42, 0.08);
        z-index: 100;
        overflow: hidden;
        display: none;
    }

    .finance-notify-item {
        width: 100%;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 12px 15px;
        text-align: left;
        background: #ffffff;
        border: none;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        transition: background 0.15s ease;
        font-family: inherit;
        box-sizing: border-box;
    }

    .finance-notify-item:last-child {
        border-bottom: none;
    }

    .finance-notify-item:hover:not(.disabled) {
        background: #f8fbff;
    }

    .finance-notify-item.disabled {
        opacity: 0.65;
        cursor: not-allowed;
        background: #fafbfc;
    }

    .finance-notify-item-icon {
        width: 20px;
        height: 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .finance-notify-item-text {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .finance-notify-item-title {
        font-size: 13px;
        font-weight: 700;
        color: #07162d;
    }

    .finance-notify-item-sub {
        font-size: 11px;
        color: #64748b;
        line-height: 1.35;
    }

    .finance-notice-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 14px;
        border-radius: 8px;
        margin-bottom: 18px;
        font-size: 12.5px;
        animation: fadeInNotice 0.2s ease-in;
    }

    @keyframes fadeInNotice {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .finance-notice-banner.notice-info {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
    }

    .finance-notice-banner.notice-success {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
    }

    .finance-notice-banner.notice-amber {
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
    }

    .finance-notice-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .finance-notice-left svg {
        flex-shrink: 0;
    }

    .finance-notice-time {
        font-size: 11.5px;
        color: #64748b;
        font-weight: 500;
        white-space: nowrap;
    }

    /* ITEM-LEVEL ALLOCATION TABLE */
    .finance-table-wrap {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scroll-behavior: smooth;
        border: 1px solid #edf2f7;
        border-radius: 8px;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .finance-table-wrap::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }

    .finance-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        table-layout: auto;
    }

    .finance-table th {
        padding: 9px 10px;
        text-align: left;
        color: #64748b;
        font-weight: 700;
        font-size: 11px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }

    .finance-table th.th-amount,
    .finance-table td.td-amount {
        text-align: right;
    }

    .finance-table th.th-center,
    .finance-table td.td-center {
        text-align: center;
    }

    .finance-table td {
        padding: 10px 10px;
        border-bottom: 1px solid #edf2f7;
        color: #1e293b;
        vertical-align: middle;
        font-size: 12px;
    }

    .finance-table tr:last-child td {
        border-bottom: none;
    }

    .finance-table tr:hover td {
        background: #fafcff;
    }

    .finance-item-title {
        font-weight: 700;
        color: #0f172a;
        font-size: 13.5px;
        margin-bottom: 4px;
    }

    .finance-item-code {
        font-size: 11.5px;
        font-weight: 500;
        color: #64748b;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: color 0.15s ease;
    }

    .finance-item-code:hover {
        color: #0f172a;
        text-decoration: underline;
    }

    .finance-pill-unpaid {
        display: inline-block;
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #b91c1c;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 12px;
        white-space: nowrap;
    }

    .finance-pill-partial {
        display: inline-block;
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #b45309;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 12px;
        white-space: nowrap;
    }

    .finance-pill-paid {
        display: inline-block;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #047857;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 12px;
        white-space: nowrap;
    }

    .finance-pill-pending {
        display: inline-block;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #64748b;
        font-size: 11px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 12px;
        white-space: nowrap;
    }

    .finance-pill-ready {
        display: inline-block;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 12px;
        white-space: nowrap;
    }

    .finance-pill-active {
        display: inline-block;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #15803d;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 12px;
        white-space: nowrap;
    }

    .finance-btn-allocate {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #ffffff;
        border: 1px solid #cbd8e8;
        color: #1e4f95;
        font-weight: 700;
        font-size: 12px;
        padding: 5px 12px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s ease;
        font-family: inherit;
    }

    .finance-btn-allocate:hover {
        background: #244f91;
        border-color: #244f91;
        color: #ffffff;
    }

    .finance-btn-allocate svg {
        flex-shrink: 0;
    }

    /* MODAL: RECORD FLEXIBLE PAYMENT ALLOCATION (COMPACT & PROPORTIONAL) */
    .finance-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 16px;
        box-sizing: border-box;
    }

    .finance-modal-dialog {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        width: 100%;
        max-width: 590px;
        max-height: 88vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.28);
        overflow: hidden;
        animation: modalScaleIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        margin: auto;
    }

    #financePaymentForm {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
        overflow: hidden;
        margin: 0;
    }

    .finance-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        flex-shrink: 0;
    }

    .finance-modal-title-box {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .finance-modal-title-box h3 {
        margin: 0;
        color: #0f2747;
        font-size: 16px;
        font-weight: 700;
        letter-spacing: -0.2px;
    }

    .finance-modal-subtitle {
        margin: 2px 0 0 0;
        color: #64748b;
        font-size: 11.5px;
        font-weight: 400;
    }

    .finance-modal-btn-close {
        background: transparent;
        border: none;
        color: #94a3b8;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
        padding: 4px 6px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
    }

    .finance-modal-btn-close:hover {
        color: #0f172a;
        background: #f1f5f9;
    }

    .finance-modal-body {
        padding: 16px 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 13px;
        flex: 1;
        min-height: 0;
    }

    .finance-modal-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        padding: 12px 20px;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
        flex-shrink: 0;
    }

    /* SCOPE SELECTION PILLS (COMPACT 3x2 GRID) */
    .finance-scope-selector-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        margin-top: 4px;
    }

    .finance-scope-label {
        display: flex;
        align-items: center;
        gap: 7px;
        padding: 8px 10px;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        background: #ffffff;
        font-size: 11.5px;
        font-weight: 600;
        color: #1e293b;
        cursor: pointer;
        transition: all 0.15s ease;
        user-select: none;
        box-sizing: border-box;
    }

    .finance-scope-label:hover {
        border-color: #93c5fd;
        background: #f8fbff;
    }

    .finance-scope-label.active {
        border-color: #2563eb;
        background: #eff6ff;
        color: #1d4ed8;
        box-shadow: 0 0 0 1px #2563eb;
    }

    .finance-scope-label input[type="radio"] {
        accent-color: #2563eb;
        width: 14px;
        height: 14px;
        margin: 0;
        cursor: pointer;
    }

    /* UPLOAD PANEL */
    .finance-upload-card {
        border: 1px solid #dbeafe;
        border-radius: 8px;
        padding: 10px 12px;
        background: #f8fbff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        transition: all 0.15s ease;
        box-sizing: border-box;
    }

    .finance-upload-card:hover {
        border-color: #93c5fd;
        background: #f0f7ff;
    }

    .finance-upload-title {
        font-size: 12px;
        font-weight: 700;
        color: #0f2747;
        margin-top: 2px;
        margin-bottom: 1px;
    }

    .finance-upload-hint {
        font-size: 10.5px;
        color: #64748b;
        margin-bottom: 8px;
    }

    .finance-upload-btns {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .finance-btn-upload-file,
    .finance-btn-upload-image {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        border: 1px solid #d1d5db;
        background: #ffffff;
        color: #2563eb;
        font-family: inherit;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }

    .finance-btn-upload-file:hover,
    .finance-btn-upload-image:hover {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #1d4ed8;
    }

    .finance-file-preview {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 7px 10px;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        background: #ffffff;
        width: 100%;
        box-sizing: border-box;
    }

    .finance-file-preview-left {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }

    .finance-file-thumb {
        width: 32px;
        height: 32px;
        border-radius: 5px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        flex-shrink: 0;
    }

    .finance-file-icon-box {
        width: 32px;
        height: 32px;
        border-radius: 5px;
        background: #eff6ff;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border: 1px solid #bfdbfe;
    }

    .finance-file-meta {
        display: flex;
        flex-direction: column;
        min-width: 0;
        text-align: left;
    }

    .finance-file-name {
        font-size: 11.5px;
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 160px;
    }

    .finance-file-size {
        font-size: 10.5px;
        color: #64748b;
    }

    .finance-file-actions {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-shrink: 0;
    }

    .finance-btn-file-replace,
    .finance-btn-file-remove {
        background: transparent;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        padding: 3px 7px;
        font-size: 10.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        font-family: inherit;
    }

    .finance-btn-file-replace {
        color: #1e4f95;
    }

    .finance-btn-file-replace:hover {
        background: #eff6ff;
        border-color: #94b6e8;
    }

    .finance-btn-file-remove {
        color: #b91c1c;
    }

    .finance-btn-file-remove:hover {
        background: #fef2f2;
        border-color: #fca5a5;
    }

    /* RECORDED BY DISPLAY BOX */
    .finance-recorded-by-box {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0 12px;
        border: 1px solid #e2e8f0;
        border-radius: 7px;
        background: #f8fafc;
        color: #0f2747;
        font-size: 12.5px;
        font-weight: 600;
        height: 38px;
        box-sizing: border-box;
    }

    .finance-recorded-by-box svg {
        color: #475569;
        flex-shrink: 0;
    }

    .finance-btn-cancel-modal {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #475569;
        font-weight: 600;
        font-size: 12.5px;
        padding: 7px 18px;
        border-radius: 7px;
        cursor: pointer;
        transition: all 0.15s ease;
        font-family: inherit;
    }

    .finance-btn-cancel-modal:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        color: #0f172a;
    }

    .finance-btn-save-allocation {
        background: #2563eb;
        border: 1px solid #2563eb;
        color: #ffffff;
        font-weight: 600;
        font-size: 12.5px;
        padding: 7px 20px;
        border-radius: 7px;
        cursor: pointer;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: inherit;
        box-shadow: 0 1px 3px rgba(37, 99, 235, 0.25);
    }

    .finance-btn-save-allocation:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    /* EMPTY STATES */
    .finance-empty-panel {
        background: #f8fbff;
        border: 1px solid #e0ecfb;
        border-radius: 10px;
        padding: 38px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    .finance-empty-icon-circle {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
    }

    .finance-empty-heading {
        margin: 0 0 4px;
        color: #0f2747;
        font-size: 15px;
        font-weight: 700;
    }

    .finance-empty-subtext {
        margin: 0;
        color: #64748b;
        font-size: 12.5px;
        font-weight: 500;
    }

    /* =========================================================
       ORIGINAL START FORM (PAPER DOCUMENT) STYLES
       ========================================================= */
    .start-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 13px;
    }

    .start-header h2 {
        margin-bottom: 4px;
        font-size: 19px;
        font-weight: 800;
        color: #07162d;
    }

    .start-description {
        margin: 0;
        color: #64748b;
        font-size: 11px;
    }

    .start-actions {
        display: flex;
        gap: 7px;
        flex-wrap: wrap;
    }

    .start-action {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 8px 10px;
        border: 1px solid #cbd8e8;
        border-radius: 6px;
        background: #ffffff;
        color: #334155;
        text-decoration: none;
        font-size: 10px;
        font-weight: 600;
        transition: all 0.15s ease;
    }

    .start-action:hover {
        color: #1e4f95;
        border-color: #94b6e8;
        background: #f8fafc;
    }

    .start-paper {
        border: 2px solid #24498c;
        padding: 10px;
        background: #ffffff;
        box-sizing: border-box;
    }

    .start-paper .start-status-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px;
        border: 1px solid #dce5f0;
        background: #f8fafc;
        font-size: 10px;
    }

    .start-paper .start-status {
        display: inline-block;
        padding: 3px 9px;
        border-radius: 15px;
        background: #e5edf7;
        color: #53657c;
        font-weight: 700;
    }

    .start-paper .approval-required {
        color: #64748b;
        font-size: 9px;
        font-weight: 700;
    }

    .start-paper .start-brand-title {
        text-align: center;
        padding: 12px 5px;
        font-family: Georgia, "Times New Roman", serif;
        color: #07162d;
    }

    .start-paper .start-company {
        font-size: 19px;
        font-weight: 700;
        margin-bottom: 3px;
    }

    .start-paper .start-title {
        font-size: 20px;
        line-height: 1.15;
        font-weight: 800;
    }

    .start-paper .start-subtitle {
        margin-top: 4px;
        font-size: 8px;
        color: #64748b;
    }

    .start-paper .start-info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        border: 1px solid #1e293b;
    }

    .start-paper .start-info-cell {
        padding: 5px;
        border-right: 1px solid #1e293b;
        border-bottom: 1px solid #1e293b;
        font-size: 8px;
    }

    .start-paper .start-info-cell:nth-child(2n) {
        border-right: 0;
    }

    .start-paper .start-label {
        font-weight: 700;
        text-transform: uppercase;
        font-size: 7px;
        color: #334155;
    }

    .start-paper .start-value {
        font-size: 8px;
        margin-top: 2px;
        color: #0f172a;
    }

    .start-paper .start-section-title {
        padding: 5px;
        text-align: center;
        background: #24498c;
        color: #ffffff;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 13px;
        font-weight: 700;
        border: 1px solid #1e293b;
        border-top: 0;
    }

    .start-paper .start-empty {
        padding: 13px;
        border: 1px solid #1e293b;
        border-top: 0;
        color: #7890aa;
        font-size: 9px;
        font-style: italic;
        text-align: center;
    }

    .start-paper .start-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8px;
        border: 1px solid #1e293b;
        border-top: 0;
    }

    .start-paper .start-table th,
    .start-paper .start-table td {
        border: 1px solid #1e293b;
        padding: 5px;
        vertical-align: top;
        font-size: 8px;
    }

    .start-paper .start-table th {
        text-align: center;
        background: #eef4ff;
        font-weight: 700;
        color: #07162d;
    }

    .start-paper .start-table td {
        background: #ffffff;
        color: #1e293b;
    }

    .start-paper .clearance-title {
        padding: 7px;
        border: 1px solid #1e293b;
        border-top: 0;
        text-align: center;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 16px;
        font-weight: 700;
        background: #ffffff;
    }

    .clearance-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
    }

    .clearance-cell {
        min-height: 100px;
        border: 1px solid #1e293b;
        border-top: 0;
        padding: 7px;
        text-align: center;
        font-size: 8px;
        box-sizing: border-box;
        background: #ffffff;
    }

    .signature-line {
        margin-top: 40px;
        border-top: 1px solid #1e293b;
        padding-top: 5px;
        font-size: 7px;
    }

    .record-row {
        display: grid;
        grid-template-columns: 2fr 1fr;
    }

    .record-cell {
        min-height: 65px;
        border: 1px solid #1e293b;
        border-top: 0;
        padding: 7px;
        font-size: 8px;
        box-sizing: border-box;
        background: #ffffff;
    }

    .rejection-box {
        border: 1px solid #1e293b;
        border-top: 0;
        padding: 7px;
        box-sizing: border-box;
        background: #ffffff;
    }

    .rejection-label {
        font-size: 8px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
    }

    .rejection-input {
        min-height: 35px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        background: #ffffff;
    }

    /* =========================================================
       PROGRESSIVE START ACTIVATION BATCHES (EXACT SCREENSHOT UI)
       ========================================================= */
    #tab-panel-start {
        background: transparent;
    }

    .start-batches-card,
    .start-details-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px 28px;
        width: 100%;
        max-width: 1250px;
        margin: 0 auto 24px auto;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        box-sizing: border-box;
        font-family: inherit;
    }

    .start-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    .start-title-with-icon {
        display: flex;
        align-items: center;
        gap: 14px;
        flex: 1 1 auto;
        min-width: 0;
    }

    .start-title-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
    }

    .start-section-title {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        line-height: 1.3;
        font-family: inherit;
    }

    .start-section-subtitle {
        font-size: 13.5px;
        color: #64748b;
        margin: 0;
        font-weight: 400;
        line-height: 1.4;
        font-family: inherit;
    }

    .start-btn-primary {
        appearance: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: #0066FF;
        color: #ffffff !important;
        border: none;
        border-radius: 6px;
        height: 38px;
        padding: 0 18px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        font-family: inherit;
        text-decoration: none;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .start-btn-primary:hover {
        background: #0052cc;
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(0, 102, 255, 0.3);
    }

    /* 4 KPI SUMMARY STAT CARDS */
    .start-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 22px;
    }

    .start-stat-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        gap: 6px;
        box-sizing: border-box;
    }

    .start-stat-top {
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
    }

    .start-stat-label {
        font-size: 10.5px;
        font-weight: 800;
        color: #64748b;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        font-family: inherit;
        text-align: center;
    }

    .start-stat-value {
        font-size: 20px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
        font-family: inherit;
        text-align: center;
    }

    .start-stat-value.start-text-green,
    .start-stat-value.start-text-orange,
    .start-stat-value.start-text-blue {
        color: #0f172a;
    }

    /* RESPONSIVE TABLE WRAPPER (MATCHING SCREENSHOT) */
    .start-table-wrap {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        background: #ffffff;
        box-sizing: border-box;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .start-table-wrap::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }

    .start-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 13.5px;
        table-layout: auto;
        font-family: inherit;
    }

    .start-table th {
        padding: 14px 16px;
        font-size: 13px;
        font-weight: 600;
        color: #5A6C84;
        background: #F0F6FD;
        border-bottom: 1px solid #E2E8F0;
        border-right: 1px solid #E5EDF6;
        text-align: left;
        white-space: nowrap;
        vertical-align: middle;
        box-sizing: border-box;
        font-family: inherit;
        height: 48px;
    }

    .start-table th:last-child {
        border-right: none;
    }

    .start-table td {
        padding: 18px 16px;
        border-bottom: 1px solid #F1F5F9;
        border-right: 1px solid #F8FAFC;
        vertical-align: middle;
        box-sizing: border-box;
        font-size: 13.5px;
        color: #1E293B;
        background: #ffffff;
        font-family: inherit;
    }

    .start-table td:last-child {
        border-right: none;
    }

    .start-table tr:last-child td {
        border-bottom: none;
    }

    .start-table tr:hover td {
        background: #FAFBFD;
    }

    /* TABLE COLUMNS SPECIFIC SIZING & TYPOGRAPHY */
    .start-code-title {
        font-size: 14px;
        font-weight: 700;
        color: #0F172A;
        font-family: inherit;
        display: block;
        white-space: nowrap;
    }

    .start-code-subtitle {
        font-size: 12px;
        color: #64748B;
        margin-top: 2px;
        font-weight: 400;
        font-family: inherit;
        display: block;
        white-space: nowrap;
    }

    .start-scope-cell {
        min-width: 0;
    }

    .start-scope-name {
        font-size: 14px;
        font-weight: 600;
        color: #0F172A;
        line-height: 1.35;
        white-space: nowrap;
        font-family: inherit;
        display: block;
    }

    .start-scope-sub {
        font-size: 12px;
        color: #64748B;
        margin-top: 2px;
        font-family: inherit;
        display: block;
        line-height: 1.3;
        white-space: nowrap;
    }

    /* STATUS BADGES */
    .start-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        height: auto;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
        line-height: 1;
        font-family: inherit;
        box-sizing: border-box;
    }

    .start-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .start-badge-draft {
        background: #F1F5F9;
        color: #475569;
    }
    .start-badge-draft .start-dot { background: #64748B; }

    .start-badge-structuring {
        background: #EFF6FF;
        color: #1E40AF;
    }
    .start-badge-structuring .start-dot { background: #2563EB; }

    .start-badge-confirmation {
        background: #FEF3C7;
        color: #92400E;
    }
    .start-badge-confirmation .start-dot { background: #D97706; }

    .start-badge-partial {
        background: #FFEDD5;
        color: #C2410C;
    }
    .start-badge-partial .start-dot { background: #EA580C; }

    .start-badge-reassignment {
        background: #FFE4E6;
        color: #BE123C;
    }
    .start-badge-reassignment .start-dot { background: #E11D48; }

    .start-badge-confirmed,
    .start-badge-completed,
    .start-badge-activated {
        background: #DCFCE7;
        color: #15803D;
    }
    .start-badge-confirmed .start-dot,
    .start-badge-completed .start-dot,
    .start-badge-activated .start-dot { background: #22C55E; }

    .start-badge-review {
        background: #EDE9FE;
        color: #5B21B6;
    }
    .start-badge-review .start-dot { background: #7C3AED; }

    .start-badge-returned {
        background: #FEE2E2;
        color: #991B1B;
    }
    .start-badge-returned .start-dot { background: #DC2626; }

    .start-badge-cancelled {
        background: #F8FAFC;
        color: #64748B;
    }
    .start-badge-cancelled .start-dot { background: #94A3B8; }

    .start-status-desc {
        font-size: 12px;
        color: #64748B;
        margin-top: 4px;
        line-height: 1.35;
        font-family: inherit;
    }

    /* MEMO PILLS & SOFT BUTTONS */
    .start-memo-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
        font-family: inherit;
    }

    .start-memo-pill-na {
        background: #F8FAFC;
        color: #64748B;
        border: 1px solid #E2E8F0;
    }

    .start-memo-btn-soft {
        background: #EFF6FF;
        color: #1D4ED8;
        border: 1px solid #DBEAFE;
        height: 34px;
        padding: 0 14px;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: inherit;
        white-space: nowrap;
        text-decoration: none;
        box-sizing: border-box;
    }

    .start-memo-btn-soft:hover {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .start-date-main {
        font-weight: 600;
        color: #0F172A;
        font-size: 13px;
        line-height: 1.3;
        white-space: nowrap;
        font-family: inherit;
        display: block;
    }

    .start-date-sub {
        color: #64748B;
        font-size: 12px;
        font-weight: 400;
        white-space: nowrap;
        font-family: inherit;
        margin-top: 2px;
        display: block;
    }

    .start-action-cell {
        white-space: nowrap;
        text-align: left;
    }

    .start-btn-details-soft {
        background: #EFF6FF;
        color: #1D4ED8;
        border: 1px solid #DBEAFE;
        height: 34px;
        padding: 0 14px;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-family: inherit;
        white-space: nowrap;
        text-decoration: none;
        box-sizing: border-box;
    }

    .start-btn-details-soft:hover {
        background: #DBEAFE;
        color: #1E40AF;
    }

    /* EMPTY STATE */
    .start-empty-panel {
        background: #ffffff;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        padding: 50px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        font-family: inherit;
    }

    .start-empty-icon-circle {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
    }

    .start-empty-heading {
        margin: 0 0 4px;
        color: #07162d;
        font-size: 15px;
        font-weight: 700;
        font-family: inherit;
    }

    .start-empty-subtext {
        margin: 0 0 16px;
        color: #64748b;
        font-size: 13px;
        font-weight: 400;
        max-width: 420px;
        font-family: inherit;
    }

    /* =========================================================
       START BATCH DETAILS MODAL & INNER TABS
       ========================================================= *    /* =========================================================
       START BATCH DETAILS MODAL & INNER TABS (INTER / READER-FRIENDLY)
       ========================================================= */
    .start-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(2px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    .start-modal-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        width: 100%;
        max-width: 860px;
        height: 640px;
        max-height: 88vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
        overflow: hidden;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        animation: startModalPop 0.2s ease-out;
        box-sizing: border-box;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .start-modal-card::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }

    @keyframes startModalPop {
        from { opacity: 0; transform: scale(0.98) translateY(4px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    .start-modal-head {
        padding: 24px 28px 16px;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        background: #ffffff;
        flex-shrink: 0;
    }

    .start-modal-title-left {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .start-modal-header-top-line {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .start-modal-main-title {
        font-size: 20px;
        line-height: 28px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }

    .start-modal-code-badge {
        font-size: 12px;
        line-height: 16px;
        font-weight: 600;
        color: #1d4ed8;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        padding: 3px 8px;
        border-radius: 4px;
    }

    .start-modal-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        line-height: 16px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 9999px;
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid #bfdbfe;
    }

    .start-modal-status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #2563eb;
        display: inline-block;
    }

    .start-modal-subtitle {
        font-size: 13px;
        line-height: 18px;
        color: #64748b;
        margin: 0;
        font-weight: 400;
    }

    .start-modal-close-btn {
        background: transparent;
        border: none;
        color: #64748b;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
        padding: 0 4px;
        transition: color 0.15s ease;
    }

    .start-modal-close-btn:hover {
        color: #0f172a;
    }

    /* INNER TAB NAVIGATION */
    .start-modal-tabs-track {
        display: flex;
        align-items: center;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        padding: 0 28px;
        gap: 24px;
        overflow-x: auto;
        flex-shrink: 0;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .start-modal-tabs-track::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }

    .start-modal-tab-btn {
        appearance: none;
        background: transparent;
        border: none;
        outline: none;
        cursor: pointer;
        padding: 12px 0 14px;
        font-size: 13.5px;
        line-height: 20px;
        font-weight: 500;
        color: #64748b;
        border-bottom: 2px solid transparent;
        transition: all 0.15s ease;
        white-space: nowrap;
        font-family: inherit;
    }

    .start-modal-tab-btn:hover {
        color: #0f172a;
    }

    .start-modal-tab-btn.active {
        color: #0f172a;
        font-weight: 700;
        border-bottom-color: #2563eb;
    }

    .start-modal-body-scroll {
        padding: 24px 28px;
        overflow-y: auto;
        flex: 1 1 auto;
        min-height: 0;
        background: #ffffff;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .start-modal-body-scroll::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }

    /* CURRENT STATUS & STATUS DETAILS SECTION */
    .start-status-banner-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 20px;
    }

    .smsb-left-group {
        display: flex;
        align-items: center;
        gap: 20px;
        flex: 1;
        min-width: 0;
    }

    .smsb-current-block {
        display: flex;
        flex-direction: column;
        gap: 3px;
        flex-shrink: 0;
    }

    .smsb-label {
        font-size: 10.5px;
        line-height: 14px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .smsb-status-val {
        font-size: 14px;
        line-height: 20px;
        font-weight: 700;
        color: #0f172a;
    }

    .smsb-divider {
        border-left: 1px solid #e2e8f0;
        height: 28px;
        flex-shrink: 0;
    }

    .smsb-details-block {
        display: flex;
        flex-direction: column;
        gap: 3px;
        flex: 1;
        min-width: 0;
    }

    .smsb-details-text {
        font-size: 13px;
        line-height: 1.4;
        font-weight: 400;
        color: #334155;
    }

    .smsb-actions-group {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    .btn-start-memo-primary {
        background: #2563eb;
        color: #ffffff;
        border: 1px solid #2563eb;
        border-radius: 6px;
        padding: 7px 14px;
        font-size: 13px;
        line-height: 18px;
        font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        transition: background 0.15s ease;
        white-space: nowrap;
    }

    .btn-start-memo-primary:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    .btn-start-amend-outline {
        background: #ffffff;
        color: #2563eb;
        border: 1px solid #2563eb;
        border-radius: 6px;
        padding: 7px 14px;
        font-size: 13px;
        line-height: 18px;
        font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
    }

    .btn-start-amend-outline:hover {
        background: #eff6ff;
    }

    /* 2-COLUMN OVERVIEW INFORMATION */
    .start-details-grid-rows {
        display: flex;
        flex-direction: column;
    }

    .sm-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px 28px;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .sm-col {
        display: flex;
        flex-direction: column;
        gap: 3px;
        min-width: 0;
    }

    .sm-label {
        font-size: 10.5px;
        line-height: 14px;
        font-weight: 700;
        color: #64748b;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .sm-value {
        font-size: 13.5px;
        line-height: 1.35;
        font-weight: 700;
        color: #0f172a;
        overflow-wrap: anywhere;
    }

    .sm-value-important {
        font-weight: 700;
        color: #0f172a;
    }

    /* SCOPE DELIVERABLES & NOTES SECTION */
    .start-scope-notes-section {
        margin-top: 18px;
    }

    .start-scope-notes-accordion {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 10px 14px;
        cursor: pointer;
        font-size: 13px;
        line-height: 18px;
        font-weight: 600;
        color: #0f172a;
        transition: background 0.15s ease;
    }

    .start-scope-notes-accordion:hover {
        background: #f1f5f9;
    }

    .start-scope-notes-panel {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-top: none;
        border-radius: 0 0 6px 6px;
        padding: 14px 16px;
        font-size: 14px;
        line-height: 22px;
        font-weight: 400;
        color: #334155;
    }

    /* ASSIGNMENTS & ACKNOWLEDGEMENT TRACKER STYLES */
    .btn-start-add-role {
        background: #ffffff;
        color: #2563eb;
        border: 1px solid #2563eb;
        border-radius: 6px;
        padding: 7px 14px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
        font-family: inherit;
    }

    .btn-start-add-role:hover {
        background: #eff6ff;
    }

    .start-ack-progress-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px 18px;
        margin-bottom: 20px;
    }

    .start-ack-progress-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .start-ack-progress-label {
        font-size: 10.5px;
        font-weight: 800;
        color: #64748b;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .start-ack-progress-value {
        font-size: 12.5px;
        font-weight: 700;
        color: #0f172a;
    }

    .start-ack-track {
        width: 100%;
        height: 8px;
        background: #e2e8f0;
        border-radius: 9999px;
        overflow: hidden;
    }

    .start-ack-fill {
        width: 100%;
        height: 100%;
        background: #2563eb;
        border-radius: 9999px;
        transition: width 0.3s ease;
    }

    .start-assign-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        background: transparent;
        border: none;
    }

    .start-assign-table th {
        background: #f8fafc;
        color: #64748b;
        font-weight: 700;
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 10px 14px;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
        white-space: nowrap;
    }

    .start-assign-table td {
        padding: 14px;
        border-bottom: 1px solid #f1f5f9;
        color: #0f172a;
        vertical-align: middle;
    }

    .start-assign-table tr:last-child td {
        border-bottom: none;
    }

    .start-assign-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        color: #2563eb;
        padding: 3px 10px;
        border-radius: 9999px;
        font-size: 11.5px;
        font-weight: 600;
        line-height: 1.35;
        white-space: nowrap;
    }

    .start-assign-status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #2563eb;
        display: inline-block;
    }

    /* =========================================================
       FILES TAB & CLIENT ACTION REQUESTS STYLES
       ========================================================= */
    .files-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        padding: 24px 28px;
        margin-bottom: 24px;
        box-sizing: border-box;
    }

    .files-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    .files-title-with-icon {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .files-title {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        line-height: 1.3;
    }

    .files-subtitle {
        font-size: 13.5px;
        color: #64748b;
        margin: 4px 0 0;
        font-weight: 400;
        line-height: 1.4;
    }

    .files-table-wrap {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        background: #ffffff;
    }

    .files-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 13px;
    }

    .files-table th {
        background: #f8fafc;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 10px 16px;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }

    .files-table td {
        padding: 10px 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #1e293b;
        vertical-align: middle;
    }

    .files-table tr:last-child td {
        border-bottom: none;
    }

    .files-table tr:hover td {
        background: #f8fafc;
    }

    .client-action-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .client-action-pill-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .client-action-pill-pending .pill-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #f59e0b;
    }

    .client-action-pill-completed {
        background: #dcfce7;
        color: #166534;
    }

    .client-action-pill-completed .pill-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #22c55e;
    }

    .client-action-pill-inprogress {
        background: #e0e7ff;
        color: #3730a3;
    }

    .client-action-pill-inprogress .pill-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #4f46e5;
    }

    .client-action-pill-declined {
        background: #fee2e2;
        color: #991b1b;
    }

    .client-action-pill-declined .pill-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #ef4444;
    }

    .files-action-btn-view {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 6px;
        background: #ffffff;
        color: #334155;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid #cbd5e1;
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
        white-space: nowrap;
        flex-shrink: 0;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }

    .files-action-btn-view:hover {
        background: #f8fafc;
        color: #0f172a;
        border-color: #94a3b8;
    }

    .client-action-empty-wrap {
        padding: 48px 24px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }

    /* =========================================================
       SERVICE MEMO GENERATOR - EXACT TEMPLATE STYLES
       ========================================================= */
    .start-memo-paper {
        background: #ffffff;
        border: 1px solid #8ab8ea;
        box-shadow: 0 4px 20px rgba(0, 43, 102, 0.08);
        border-radius: 4px;
        padding: 40px 48px;
        margin: 0 auto 24px;
        max-width: 840px;
        color: #002b66;
        position: relative;
        font-family: Georgia, "Times New Roman", Times, serif;
        box-sizing: border-box;
    }

    .start-memo-brand-header {
        margin-bottom: 2px;
    }

    .start-memo-brand-title {
        font-family: Georgia, "Times New Roman", Times, serif;
        color: #002b66;
        line-height: 1.05;
        letter-spacing: -0.5px;
    }

    .start-memo-brand-line1,
    .start-memo-brand-line2 {
        font-size: 26px;
        font-weight: 800;
        color: #002b66;
        font-family: Georgia, "Times New Roman", Times, serif;
        letter-spacing: -0.3px;
        line-height: 1.1;
    }

    .start-memo-brand-title .amp {
        color: #1d68e1;
        font-style: italic;
        font-family: Georgia, "Times New Roman", Times, serif;
        font-size: 29px;
        font-weight: 700;
        margin-right: 2px;
    }

    .start-memo-top-rule {
        border-top: 1.5px solid #1d68e1;
        margin: 10px 0 14px 0;
    }

    .start-memo-main-heading {
        font-size: 28px;
        font-weight: 800;
        color: #002b66;
        letter-spacing: 2px;
        margin: 0 0 14px 0;
        font-family: Georgia, "Times New Roman", Times, serif;
        text-transform: uppercase;
    }

    .start-memo-meta-list {
        display: flex;
        flex-direction: column;
        gap: 5px;
        margin-bottom: 12px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
    }

    .start-memo-meta-item {
        display: flex;
        align-items: baseline;
        font-size: 13px;
        line-height: 1.4;
    }

    .start-memo-meta-label {
        font-weight: 800;
        color: #002b66;
        width: 100px;
        flex-shrink: 0;
        letter-spacing: 0.5px;
    }

    .start-memo-meta-colon {
        font-weight: 800;
        color: #002b66;
        width: 24px;
        flex-shrink: 0;
        text-align: center;
    }

    .start-memo-meta-value {
        color: #002b66;
        font-weight: 500;
        flex: 1 1 auto;
    }

    .start-memo-mid-rule {
        border-top: 1.5px solid #1d68e1;
        margin: 12px 0 14px 0;
    }

    .start-memo-intro-text {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        font-size: 12.5px;
        line-height: 1.55;
        color: #002b66;
        margin-bottom: 16px;
    }

    .start-memo-intro-text p {
        margin: 0 0 6px 0;
    }

    .start-memo-section-title {
        font-family: Georgia, "Times New Roman", Times, serif;
        font-size: 13.5px;
        font-weight: 800;
        color: #002b66;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin: 18px 0 8px 0;
        border-bottom: 1.5px solid #1d68e1;
        padding-bottom: 2px;
    }

    .start-memo-grid-table {
        width: 100%;
        border-collapse: collapse;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        font-size: 11.5px;
        margin-bottom: 14px;
        border: 1px solid #8ab8ea;
    }

    .start-memo-grid-table th,
    .start-memo-grid-table td {
        border: 1px solid #8ab8ea;
        padding: 6px 10px;
        vertical-align: top;
        box-sizing: border-box;
    }

    .start-memo-grid-table th {
        background: #ebf3fc;
        color: #002b66;
        font-weight: 700;
        font-size: 11px;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        text-align: left;
    }

    .start-memo-grid-table td {
        background: #ffffff;
        color: #002b66;
        font-size: 11.5px;
    }

    .start-memo-special-box {
        background: #ebf3fc;
        border: 1px solid #8ab8ea;
        border-radius: 2px;
        padding: 10px 14px;
        font-size: 11.5px;
        color: #002b66;
        min-height: 36px;
        line-height: 1.5;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        margin-bottom: 14px;
    }

    .start-memo-no-engagement-box {
        background: #ebf3fc;
        border: 1px solid #8ab8ea;
        color: #002b66;
        text-align: center;
        padding: 8px 12px;
        font-weight: 600;
        font-size: 12px;
        margin-bottom: 14px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
    }

    .start-memo-signatures-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-top: 10px;
        margin-bottom: 14px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
    }

    .start-memo-sig-col {
        display: flex;
        flex-direction: column;
    }

    .start-memo-sig-col.right-col {
        border-left: 1.5px solid #8ab8ea;
        padding-left: 24px;
    }

    .start-memo-sig-label {
        font-size: 11px;
        font-weight: 800;
        color: #002b66;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 24px;
    }

    .start-memo-sig-line {
        border-bottom: 1px solid #002b66;
        margin-bottom: 4px;
        width: 85%;
    }

    .start-memo-sig-name {
        font-size: 11.5px;
        font-weight: 700;
        color: #002b66;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .start-memo-sig-sub {
        font-size: 10px;
        font-weight: 700;
        color: #002b66;
        text-transform: uppercase;
        margin-top: 2px;
    }

    .start-memo-footer-rule {
        border-top: 1.5px solid #1d68e1;
        margin: 14px 0 4px 0;
    }

    .start-memo-footer-caption {
        font-size: 10px;
        color: #002b66;
        text-align: center;
        font-style: italic;
        font-family: Georgia, "Times New Roman", Times, serif;
    }

    .start-memo-watermark {
        position: absolute;
        top: 40%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 64px;
        font-weight: 900;
        color: rgba(220, 38, 38, 0.08);
        text-transform: uppercase;
        letter-spacing: 6px;
        pointer-events: none;
        user-select: none;
    }

    .start-memo-issued-seal {
        position: absolute;
        top: 28px;
        right: 48px;
        border: 2px solid #16a34a;
        color: #16a34a;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 1px;
        text-transform: uppercase;
        transform: rotate(8deg);
        pointer-events: none;
    }

    @media print {
        body * {
            visibility: hidden !important;
        }
        #startMemoPaperContainer,
        #startMemoPaperContainer * {
            visibility: visible !important;
        }
        #startMemoPaperContainer {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            padding: 20px !important;
            box-shadow: none !important;
            border: none !important;
        }
        #startMemoWatermark,
        #startMemoIssuedSeal {
            display: none !important;
        }
    }

    /* HISTORY TIMELINE */
    .start-history-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        position: relative;
        padding-left: 24px;
    }

    .start-history-list::before {
        content: '';
        position: absolute;
        left: 7px;
        top: 10px;
        bottom: 10px;
        width: 2px;
        background: #e2e8f0;
    }

    .start-history-item {
        position: relative;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px 16px;
    }

    .start-history-item::before {
        content: '';
        position: absolute;
        left: -21px;
        top: 16px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #2563eb;
        border: 2px solid #ffffff;
        box-shadow: 0 0 0 2px #bfdbfe;
    }

    .start-history-head,
    .start-history-event-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 4px;
        gap: 12px;
        flex-wrap: wrap;
    }

    .start-history-event,
    .start-history-event-name {
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
    }

    .start-history-time,
    .start-history-timestamp {
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
    }

    .start-history-notes,
    .start-history-details {
        font-size: 13px;
        color: #334155;
        line-height: 1.5;
        margin-top: 3px;
    }

    .start-history-actor {
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
        margin-top: 6px;
    }

    .start-history-actor strong {
        color: #0f172a;
        font-weight: 700;
    }

    /* RESPONSIVE BREAKPOINTS */
    @media (max-width: 900px) {
        .start-stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .start-info-grid-2 {
            grid-template-columns: 1fr;
        }
        .start-memo-paper {
            padding: 24px 20px;
        }
    }

    @media (max-width: 600px) {
        .start-stats-grid {
            grid-template-columns: 1fr;
        }
        .start-header-row {
            flex-direction: column;
            align-items: stretch;
        }
        .start-btn-primary {
            justify-content: center;
        }
    }

    /* =========================================================
       INTERNAL PROPOSAL HEADER & VERSION HISTORY BAR
       ========================================================= */
    .internal-proposal-header-bar {
        background: #ffffff;
        border: 1px solid #dce5f0;
        border-radius: 12px;
        padding: 16px 22px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    }

    .iph-title-group {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .iph-code {
        font-size: 18px;
        font-weight: 800;
        color: #07162d;
        letter-spacing: -0.2px;
    }

    .iph-divider {
        color: #94a3b8;
        font-weight: 300;
        font-size: 18px;
    }

    .iph-version {
        font-size: 18px;
        font-weight: 800;
        color: #1e4f95;
    }

    .iph-actions-group {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .proposal-versions-card {
        background: #ffffff;
        border: 1px solid #dce5f0;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 18px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        box-sizing: border-box;
        width: 100%;
    }

    .pvh-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 12px;
        flex-wrap: wrap;
    }

    .pvh-title-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .pvh-tag {
        background: #eff6ff;
        color: #1e4f95;
        border: 1px solid #bfdbfe;
        font-size: 10.5px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 4px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .pvh-title {
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        letter-spacing: 0.3px;
    }

    .pvh-actions-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pvh-btn-add {
        background: #244f91;
        border: 1px solid #244f91;
        color: #ffffff;
        padding: 6px 13px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 6px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.15s ease;
        font-family: inherit;
    }

    .pvh-btn-add:hover {
        background: #1a3c70;
        border-color: #1a3c70;
    }

    .pvh-pills-track {
        display: flex;
        align-items: center;
        gap: 8px;
        overflow-x: auto;
        padding: 4px 2px 8px;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f8fafc;
    }

    .pvh-pills-track::-webkit-scrollbar {
        height: 5px;
    }

    .pvh-pills-track::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .pvh-version-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 7px 14px;
        font-size: 12px;
        font-weight: 600;
        color: #334155;
        cursor: pointer;
        white-space: nowrap;
        flex: 0 0 auto;
        transition: all 0.15s ease;
        font-family: inherit;
    }

    .pvh-version-pill:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        color: #0f172a;
    }

    .pvh-version-pill.active {
        background: #1e4f95;
        border-color: #1e4f95;
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(30, 79, 149, 0.25);
    }

    .pvh-pill-status {
        font-size: 10.5px;
        font-weight: 600;
        padding: 1px 7px;
        border-radius: 10px;
        background: #e2e8f0;
        color: #475569;
    }

    .pvh-version-pill.active .pvh-pill-status {
        background: rgba(255, 255, 255, 0.22);
        color: #ffffff;
    }

    .pvh-pill-current {
        font-size: 9.5px;
        font-weight: 800;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        padding: 2px 6px;
        border-radius: 4px;
        background: #10b981;
        color: #ffffff;
    }

    .pvh-version-pill.active .pvh-pill-current {
        background: #34d399;
        color: #064e3b;
    }

    /* =========================================================
       PROPOSAL VERSIONS TOP BAR
       ========================================================= */
    .proposal-versions-topbar {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 14px 18px;
        margin-bottom: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }

    .pvt-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
        gap: 12px;
    }

    .pvt-title {
        font-size: 13.5px;
        font-weight: 800;
        letter-spacing: 0.6px;
        color: #0f172a;
        margin: 0;
        text-transform: uppercase;
    }

    /* =========================================================
       PROPOSAL VERSIONS TOP BAR & PILLS
       ========================================================= */
    .pvt-btn-new-revision {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #1e4f95;
        border: 1px solid #1e4f95;
        color: #ffffff;
        font-size: 12px;
        font-weight: 700;
        padding: 5px 13px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
        font-family: inherit;
    }

    .pvt-btn-new-revision:hover {
        background: #173d75;
        border-color: #173d75;
        box-shadow: 0 2px 6px rgba(30, 79, 149, 0.25);
    }

    .pvt-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #ffffff;
        border: 1px solid #d0dbe7;
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 12px;
        font-weight: 600;
        color: #0f172a;
        cursor: pointer;
        transition: all 0.15s ease;
        font-family: inherit;
        white-space: nowrap;
    }

    .pvt-pill:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
    }

    .pvt-pill.selected {
        background: #1e4f95;
        border-color: #1e4f95;
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(30, 79, 149, 0.25);
    }

    .pvt-pill-name {
        font-weight: 700;
    }

    .pvt-pill-status {
        font-size: 11.5px;
        font-weight: 500;
        color: #475569;
    }

    .pvt-pill.selected .pvt-pill-status {
        color: rgba(255, 255, 255, 0.95);
    }

    .pvt-pill-tag-current {
        font-size: 9.5px;
        font-weight: 800;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        padding: 2px 7px;
        border-radius: 10px;
        background: #10b981;
        color: #ffffff;
        margin-left: 2px;
    }

    .pvt-pill.selected .pvt-pill-tag-current {
        background: #10b981;
        color: #ffffff;
    }

    .pvt-historical-alert {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
        padding: 8px 14px;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 600;
        margin-top: 10px;
    }

    /* =========================================================
       PROPOSAL WORKSPACE MODAL (SIDE-BY-SIDE 2-PANEL MODAL)
       ========================================================= */
    .pwm-modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(3px);
        z-index: 2900;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        box-sizing: border-box;
    }

    .pwm-modal-dialog {
        width: 95vw;
        max-width: 1560px;
        height: 90vh;
        max-height: 92vh;
        background: #ffffff;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.3);
        border: 1px solid #cbd5e1;
        overflow: hidden;
        animation: modalFadeIn 0.15s ease-out;
    }

    .pwm-modal-header {
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 10px 18px;
        flex-shrink: 0;
    }

    .pwm-header-top-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        width: 100%;
    }

    .pwm-header-left {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .pwm-badge {
        background: #eff6ff;
        color: #1e40af;
        font-size: 11px;
        font-weight: 700;
        padding: 4px 9px;
        border-radius: 5px;
        letter-spacing: 0.5px;
        border: 1px solid #bfdbfe;
        white-space: nowrap;
    }

    .pwm-title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pwm-btn-close {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        border: 1px solid transparent;
        background: transparent;
        color: #64748b;
        font-size: 22px;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .pwm-btn-close:hover {
        background: #fee2e2;
        color: #b91c1c;
    }

    .pwm-version-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        width: 100%;
        padding-top: 6px;
        border-top: 1px solid #f1f5f9;
    }

    .pwm-version-nav-section {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        flex: 1;
    }

    .pwm-version-label {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.6px;
        color: #475569;
        text-transform: uppercase;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .pwm-version-pills-scroll {
        display: flex;
        align-items: center;
        overflow-x: auto;
        padding: 2px 0;
        scrollbar-width: thin;
        flex: 1;
    }

    .pwm-version-pills-scroll::-webkit-scrollbar {
        height: 4px;
    }

    .pwm-version-pills-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .pwm-version-pills {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        flex-wrap: nowrap;
    }

    .pwm-version-actions {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pwm-modal-body {
        flex: 1;
        overflow: hidden;
        padding: 14px 16px;
        background: #e2e8f0;
        display: flex;
    }

    .pwm-modal-body .proposal-workspace-layout {
        display: flex !important;
        flex-direction: row;
        gap: 14px;
        width: 100%;
        height: 100%;
        padding: 0;
        margin: 0;
        box-sizing: border-box;
        overflow: hidden;
    }

    .pwm-modal-body .proposal-panel {
        background: #ffffff;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        display: flex !important;
        flex-direction: column;
        height: 100%;
        max-height: 100%;
        overflow: hidden;
    }

    .pwm-modal-body .proposal-form-panel {
        flex: 0 0 45%;
        min-width: 440px;
        max-width: 580px;
        display: flex !important;
    }

    .pwm-modal-body .proposal-preview-panel {
        flex: 1;
        min-width: 520px;
        display: flex !important;
    }

    .pwm-modal-body .proposal-panel-header {
        display: flex !important;
        flex-direction: column;
        padding: 12px 18px;
        border-bottom: 1px solid #f1f5f9;
        background: #ffffff;
        flex-shrink: 0;
    }

    .pwm-modal-body .proposal-panel-header h1 {
        font-size: 15px;
        font-weight: 700;
        margin: 0 0 3px;
        color: #0f172a;
    }

    .pwm-modal-body .proposal-panel-header p {
        font-size: 12px;
        color: #64748b;
        margin: 0;
    }

    .pwm-modal-body .proposal-form-scroll {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        background: #f8fafc;
    }

    .pwm-modal-body .proposal-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 16px 18px;
        margin-bottom: 14px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .pwm-modal-body .proposal-card h2 {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid #f1f5f9;
    }

    .pwm-modal-body .proposal-preview-scroll {
        flex: 1;
        overflow-y: auto;
        padding: 20px 14px;
        background: #e5e9f0;
    }

    .pwm-modal-footer {
        height: 56px;
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        padding: 0 20px;
        flex-shrink: 0;
    }

    .pwm-btn-cancel {
        padding: 8px 18px;
        border-radius: 7px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .pwm-btn-cancel:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }

    .pwm-btn-save {
        padding: 8px 20px;
        border-radius: 7px;
        border: 1px solid #2563eb;
        background: #2563eb;
        color: #ffffff;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .pwm-btn-save:hover {
        background: #1d4ed8;
    }

    /* =========================================================
       CLIENT REVIEW PORTAL — PROFESSIONAL DOCUMENT REVIEWER
       ========================================================= */
    .crp-modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 3000;
        padding: 16px;
        box-sizing: border-box;
    }

    .crp-modal-container {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25);
        width: 100%;
        max-width: 96vw;
        height: 94vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        position: relative;
    }

    .crp-topbar {
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 26px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
        flex: 0 0 auto;
    }

    .crp-left-group {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .crp-company-name {
        font-size: 11px;
        font-weight: 800;
        color: #1e4f95;
        letter-spacing: 0.8px;
        text-transform: uppercase;
    }

    .crp-title {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        line-height: 1.2;
    }

    .crp-client-name {
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
    }

    .crp-center-group {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .crp-page-nav {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .crp-page-indicator {
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        min-width: 84px;
        text-align: center;
    }

    .crp-nav-btn {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #334155;
        border-radius: 6px;
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s ease;
        padding: 0;
    }

    .crp-nav-btn:hover:not(:disabled) {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
    }

    .crp-nav-btn:disabled {
        opacity: 0.35;
        cursor: not-allowed;
    }

    .crp-right-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .crp-btn-comment {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #334155;
        border-radius: 7px;
        padding: 7px 14px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
        font-family: inherit;
    }

    .crp-btn-comment:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
    }

    .crp-btn-approve {
        background: #2563eb;
        border: 1px solid #2563eb;
        color: #ffffff;
        border-radius: 7px;
        padding: 7px 16px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
        font-family: inherit;
        box-shadow: 0 1px 2px rgba(37, 99, 235, 0.2);
    }

    .crp-btn-approve:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    .crp-btn-approved-badge {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
        border-radius: 7px;
        padding: 7px 14px;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: default;
    }

    .crp-btn-close {
        background: transparent;
        border: none;
        color: #64748b;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border-radius: 6px;
        transition: all 0.15s ease;
        padding: 0;
    }

    .crp-btn-close:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .crp-main-view {
        flex: 1 1 auto;
        display: flex;
        overflow: hidden;
        position: relative;
        background: #f1f5f9;
    }

    .crp-doc-scroll-view {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        gap: 28px;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 32px 20px 48px;
        scroll-behavior: smooth;
        align-items: center;
        box-sizing: border-box;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    }

    .crp-doc-scroll-view::-webkit-scrollbar {
        width: 7px;
    }

    .crp-doc-scroll-view::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    .crp-doc-scroll-view::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .crp-doc-scroll-view .proposal-paper {
        flex: 0 0 auto;
        width: min(100%, 780px);
        min-height: 980px;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        background: #ffffff;
        margin: 0 auto;
        padding: 58px 56px;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .crp-comments-panel {
        width: 320px;
        flex: 0 0 320px;
        background: #ffffff;
        border-left: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        transition: all 0.2s ease;
        box-shadow: -2px 0 10px rgba(15, 23, 42, 0.04);
    }

    .crp-comments-panel.closed {
        display: none;
    }

    .crp-comments-head {
        padding: 14px 18px;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .crp-comments-head h4 {
        margin: 0;
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
    }

    .crp-comments-list {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .crp-comment-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px 14px;
    }

    .crp-comment-header {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-bottom: 6px;
    }

    .crp-comment-author {
        font-size: 12.5px;
        font-weight: 700;
        color: #0f172a;
    }

    .crp-comment-time {
        font-size: 11px;
        color: #94a3b8;
    }

    .crp-comment-body {
        font-size: 12.5px;
        color: #334155;
        line-height: 1.45;
    }

    .crp-comments-foot {
        padding: 14px 16px;
        border-top: 1px solid #e2e8f0;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    /* =========================================================
       PROPOSAL WORKSPACE TAB (FORM & PREVIEW - STACKED)
       ========================================================= */
    .proposal-workspace-layout {
        display: flex;
        flex-direction: column;
        gap: 20px;
        width: 100%;
        box-sizing: border-box;
    }

    .proposal-panel {
        background: #ffffff;
        border: 1px solid #dce5f0;
        border-radius: 10px;
        width: 100%;
        min-width: 0;
        height: 520px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .proposal-panel-header {
        padding: 16px 20px 14px;
        border-bottom: 1px solid #e5ebf3;
        background: #ffffff;
        flex: 0 0 auto;
    }

    .proposal-panel-header h1 {
        margin: 0 0 4px;
        color: #07162d;
        font-size: 16.5px;
        font-weight: 700;
        line-height: 1.25;
    }

    .proposal-panel-header p {
        margin: 0;
        color: #64748b;
        font-size: 11.5px;
        line-height: 1.4;
    }

    .proposal-form-scroll,
    .proposal-preview-scroll {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        overscroll-behavior: contain;
        scrollbar-width: thin;
        scrollbar-color: #94a3b8 #f1f5f9;
    }

    .proposal-form-scroll::-webkit-scrollbar,
    .proposal-preview-scroll::-webkit-scrollbar {
        width: 7px;
    }

    .proposal-form-scroll::-webkit-scrollbar-track,
    .proposal-preview-scroll::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 6px;
    }

    .proposal-form-scroll::-webkit-scrollbar-thumb,
    .proposal-preview-scroll::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 6px;
    }

    .proposal-form-scroll::-webkit-scrollbar-thumb:hover,
    .proposal-preview-scroll::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }

    .proposal-form-scroll {
        padding: 16px 20px 24px;
    }

    .proposal-info,
    .approval-notice {
        padding: 11px 12px;
        border-radius: 7px;
        font-size: 10.5px;
        line-height: 1.45;
    }

    .proposal-info {
        margin-bottom: 14px;
        border: 1px solid #b9d5ff;
        background: #eff6ff;
        color: #24518f;
    }

    .approval-notice {
        border: 1px solid #f3d18a;
        background: #fff8e6;
        color: #966b13;
        margin-top: 14px;
    }

    .proposal-card {
        margin-bottom: 14px;
        padding: 16px;
        border: 1px solid #dce5f0;
        border-radius: 8px;
        background: #ffffff;
    }

    .proposal-card h2 {
        margin: 0 0 12px;
        color: #334155;
        font-size: 12.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .proposal-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .proposal-field.full {
        grid-column: 1 / -1;
    }

    .proposal-field label {
        display: block;
        margin-bottom: 5px;
        color: #64748b;
        font-size: 10.5px;
        font-weight: 700;
    }

    .proposal-field input,
    .proposal-field textarea {
        width: 100%;
        border: 1px solid #d5dfeb;
        border-radius: 6px;
        padding: 8px 10px;
        color: #334155;
        font: inherit;
        font-size: 11px;
        box-sizing: border-box;
        background: #ffffff;
        outline: none;
        transition: border-color 0.15s ease;
    }

    .proposal-field input:focus,
    .proposal-field textarea:focus {
        border-color: #2563eb;
    }

    .proposal-field input[readonly] {
        background: #f8fafc;
        color: #64748b;
        cursor: not-allowed;
    }

    .proposal-field textarea {
        min-height: 68px;
        resize: vertical;
    }

    .proposal-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10.5px;
    }

    .proposal-table th,
    .proposal-table td {
        padding: 8px 7px;
        border: 1px solid #dce5f0;
        text-align: left;
        color: #475569;
    }

    .proposal-table th {
        background: #f8fafc;
        color: #64748b;
        font-weight: 700;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        padding: 7px 0;
        border-bottom: 1px solid #edf1f5;
        color: #64748b;
        font-size: 11px;
    }

    .total-row strong {
        color: #172033;
        font-variant-numeric: tabular-nums;
    }

    .total-row.total {
        border-bottom: 0;
        color: #172033;
        font-weight: 700;
    }

    .form-actions {
        display: flex;
        gap: 8px;
        margin-bottom: 14px;
    }

    .form-actions .proposal-button {
        flex: 1;
    }

    .proposal-button {
        min-height: 33px;
        padding: 7px 11px;
        border: 1px solid #cbd8e8;
        border-radius: 6px;
        background: #ffffff;
        color: #334155;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        font-family: inherit;
    }

    .proposal-button:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }

    .proposal-button.primary {
        background: #244f91;
        border-color: #244f91;
        color: #ffffff;
    }

    .proposal-button.primary:hover {
        background: #1d437d;
    }

    /* PREVIEW TOOLBAR */
    .proposal-preview-panel .preview-toolbar {
        flex: 0 0 auto;
        margin: 12px 20px 0;
        padding: 6px 10px;
        border: 1px solid #dce5f0;
        border-radius: 8px;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        box-sizing: border-box;
        overflow-x: auto;
    }

    .proposal-preview-panel .preview-toolbar::-webkit-scrollbar {
        height: 4px;
    }

    .proposal-preview-panel .preview-toolbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .proposal-preview-panel .preview-toolbar-group {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        flex-shrink: 0;
    }

    .proposal-preview-panel .preview-tool-divider {
        display: inline-block;
        width: 1px;
        height: 16px;
        background: #e2e8f0;
        margin: 0 3px;
        vertical-align: middle;
        flex-shrink: 0;
    }

    .proposal-preview-panel .preview-toolbar button,
    .proposal-preview-panel .preview-tool-btn {
        height: 27px;
        padding: 0 8px;
        border: 1px solid #d1d9e4;
        border-radius: 5px;
        background: #ffffff;
        color: #334155;
        font-size: 11px;
        font-weight: 500;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.15s ease;
        font-family: inherit;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        line-height: 1;
        user-select: none;
        flex-shrink: 0;
        vertical-align: middle;
    }

    .proposal-preview-panel .preview-tool-btn span,
    .proposal-preview-panel .preview-tool-btn strong,
    .proposal-preview-panel .preview-tool-btn em,
    .proposal-preview-panel .preview-tool-btn u {
        display: inline-flex;
        align-items: center;
        line-height: 1;
        vertical-align: middle;
    }

    .proposal-preview-panel .preview-tool-btn svg {
        display: inline-block;
        vertical-align: middle;
        flex-shrink: 0;
    }

    .proposal-preview-panel .preview-toolbar button:hover,
    .proposal-preview-panel .preview-tool-btn:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
    }

    .proposal-preview-panel .preview-toolbar button:active,
    .proposal-preview-panel .preview-tool-btn:active {
        background: #e2e8f0;
    }

    .proposal-preview-panel .preview-toolbar .template-button {
        height: 27px;
        padding: 0 10px;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 600;
        border-radius: 5px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
        margin-left: auto;
        flex-shrink: 0;
    }

    .proposal-preview-panel .preview-toolbar .template-button:hover {
        background: #dbeafe;
        border-color: #93c5fd;
        color: #1e40af;
    }

    /* Editable styling in preview */
    .preview-workspace [contenteditable="true"] {
        outline: none;
        transition: background-color 0.15s ease, box-shadow 0.15s ease;
        border-radius: 3px;
        cursor: text;
    }

    .preview-workspace [contenteditable="true"]:hover {
        background-color: rgba(37, 99, 235, 0.04);
        box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.15);
    }

    .preview-workspace [contenteditable="true"]:focus {
        background-color: rgba(37, 99, 235, 0.06);
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.35);
    }

    /* PREVIEW PAPER & PDF PAGES */
    .preview-workspace {
        min-height: 100%;
        padding: 24px 20px 48px;
        background: #eef2f7;
        margin-top: 14px;
        border-top: 1px solid #e2e8f0;
    }

    .proposal-paper {
        width: min(100%, 780px);
        min-height: 980px;
        margin: 0 auto 28px;
        padding: 56px 54px 48px;
        background: #ffffff;
        border: 1px solid #d7e0eb;
        box-shadow: 0 4px 14px rgba(30, 48, 75, 0.08);
        color: #172033;
        box-sizing: border-box;
        border-radius: 4px;
        display: flex;
        flex-direction: column;
        position: relative;
    }

    .proposal-paper-content {
        flex: 1 0 auto;
    }

    .proposal-paper-running-header {
        text-align: right;
        font-size: 9.5px;
        color: #64748b;
        margin-bottom: 22px;
        font-weight: 600;
        font-family: Arial, sans-serif;
    }

    .proposal-paper-running-footer {
        margin-top: 36px;
        padding-top: 10px;
        border-top: 1px solid #cbd5e1;
        text-align: center;
        font-size: 8.5px;
        color: #64748b;
        line-height: 1.5;
        flex-shrink: 0;
        font-family: Arial, sans-serif;
    }

    .proposal-paper-running-footer strong {
        color: #07162d;
        display: block;
        font-size: 9.5px;
        margin-bottom: 2px;
    }

    /* COVER PAGE STYLING */
    .pdf-brand-title {
        font-family: Georgia, "Times New Roman", serif;
        font-size: 38px;
        font-weight: 700;
        color: #07162d;
        line-height: 1.1;
        letter-spacing: -0.5px;
    }

    .pdf-brand-amp {
        color: #1e3a8a;
        font-style: italic;
    }

    .pdf-cover-year {
        margin-top: 110px;
        font-size: 42px;
        font-weight: 800;
        color: #1e3a8a;
        font-style: italic;
        font-family: Georgia, "Times New Roman", serif;
        line-height: 1;
    }

    .pdf-cover-service {
        margin-top: 14px;
        font-size: 19px;
        color: #1e3a8a;
        font-style: italic;
        font-family: Georgia, "Times New Roman", serif;
        font-weight: 600;
    }

    .pdf-cover-date {
        margin-top: 48px;
        font-size: 11.5px;
        color: #475569;
        font-style: italic;
        font-family: Georgia, "Times New Roman", serif;
    }

    .pdf-cover-presented-box {
        margin-top: 48px;
    }

    .pdf-cover-presented-label {
        font-size: 11px;
        color: #64748b;
        font-style: italic;
        font-family: Georgia, "Times New Roman", serif;
        margin-bottom: 8px;
    }

    .pdf-cover-client-name {
        font-size: 13.5px;
        font-weight: 700;
        color: #1e3a8a;
        font-style: italic;
        font-family: Georgia, "Times New Roman", serif;
        line-height: 1.4;
    }

    .pdf-cover-business-name {
        font-size: 12.5px;
        color: #1e3a8a;
        font-style: italic;
        font-family: Georgia, "Times New Roman", serif;
        margin-top: 4px;
        font-weight: 600;
    }

    .pdf-cover-address {
        font-size: 11.5px;
        color: #1e3a8a;
        font-style: italic;
        font-family: Georgia, "Times New Roman", serif;
        margin-top: 4px;
    }

    .pdf-cover-footer-bar {
        margin-top: auto;
        padding-top: 20px;
        border-top: 1px solid #cbd5e1;
        font-size: 9px;
        color: #64748b;
        text-align: center;
        line-height: 1.5;
    }

    .pdf-cover-meta-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 6px;
        font-size: 9px;
        color: #475569;
    }

    /* SECTION HEADINGS */
    .doc-section-heading {
        font-family: Georgia, "Times New Roman", serif;
        font-size: 15px;
        font-weight: 700;
        color: #1e3a8a;
        margin: 0 0 14px;
        display: flex;
        align-items: baseline;
        gap: 8px;
    }

    .doc-section-heading i {
        font-style: italic;
        color: #1e3a8a;
    }

    .doc-paragraph {
        font-size: 11px;
        line-height: 1.65;
        color: #334155;
        margin-bottom: 14px;
        text-align: justify;
    }

    .doc-paragraph strong {
        color: #0f172a;
    }

    /* DOCUMENT TABLES */
    .doc-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10px;
        margin-bottom: 16px;
        color: #1e293b;
    }

    .doc-table th,
    .doc-table td {
        border: 1px solid #475569;
        padding: 6px 8px;
        text-align: left;
        vertical-align: top;
    }

    .doc-table th {
        background: #f8fafc;
        font-weight: 700;
        color: #0f172a;
    }

    .doc-table-clean th,
    .doc-table-clean td {
        border: 1px solid #cbd5e1;
    }

    /* 4-GRID HIGHLIGHTS */
    .doc-highlights-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px 24px;
        margin: 20px 0;
    }

    .doc-highlight-card h4 {
        margin: 0 0 6px;
        font-size: 12px;
        font-weight: 700;
        color: #0f172a;
    }

    .doc-highlight-card p {
        margin: 0;
        font-size: 10.5px;
        line-height: 1.55;
        color: #475569;
    }

    /* SIGNATURE BLOCKS */
    .doc-signature-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 40px;
        margin-top: 40px;
    }

    .doc-signature-block {
        display: flex;
        flex-direction: column;
    }

    .doc-signature-title {
        font-size: 11.5px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 40px;
    }

    .doc-signature-line {
        border-top: 1.5px solid #0f172a;
        padding-top: 8px;
    }

    .doc-signatory-name {
        font-size: 11.5px;
        font-weight: 700;
        color: #0f172a;
    }

    .doc-signatory-role {
        font-size: 10px;
        color: #64748b;
        margin-top: 2px;
    }

    .editable {
        padding: 3px;
        border-radius: 3px;
        outline: 1px dashed transparent;
    }

    .editable:hover,
    .editable:focus {
        outline-color: #8bb8f2;
        background: #eff6ff;
    }

    .paper-summary td:last-child {
        text-align: right;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }

    .paper-footer {
        margin-top: 50px;
        color: #94a3b8;
        font-size: 9px;
        text-align: center;
    }

    @media (max-width: 990px) {
        .proposal-workspace-layout {
            height: auto;
            grid-template-columns: 1fr;
        }
        .proposal-panel {
            min-height: 480px;
        }
    }

    /* =========================================================
       MAIN LAYOUT
       ========================================================= */

    .detail-layout {
        width: 100%;
        margin: 0;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 340px;
        gap: 16px;
        align-items: start;
    }

    .detail-main {
        min-width: 0;
        max-height: calc(100vh - 90px);
        overflow-y: auto;
        overflow-x: hidden;
        scrollbar-width: thin;
        scrollbar-color: #94a3b8 #f1f5f9;
        padding-right: 6px;
        scroll-behavior: smooth;
    }

    .detail-main::-webkit-scrollbar {
        width: 7px;
    }

    .detail-main::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 6px;
    }

    .detail-main::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 6px;
    }

    .detail-main::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }

    .detail-layout aside {
        position: sticky;
        top: 75px;
        align-self: start;
        max-height: calc(100vh - 90px);
        overflow-y: auto;
        overflow-x: hidden;
        display: flex;
        flex-direction: column;
        gap: 14px;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }

    .detail-layout aside::-webkit-scrollbar {
        width: 5px;
    }

    .detail-layout aside::-webkit-scrollbar-track {
        background: transparent;
    }

    .detail-layout aside::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .detail-layout aside::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    @media (max-width: 900px) {
        .detail-layout {
        width: 100%;
        margin: 0;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 340px;
        gap: 16px;
        align-items: start;
    }
        .detail-main {
            max-height: none;
            overflow-y: visible;
            padding-right: 0;
        }
        .detail-layout aside {
            position: static;
            max-height: none;
            overflow-y: visible;
        }
    }


    /* =========================================================
       CARDS
       ========================================================= */

    .detail-section,
    .related-card,
    .detail-tabs {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .detail-section {
        padding: 22px 24px;
        margin-bottom: 16px;
        scroll-margin-top: 85px;
    }

    .detail-section h2 {
        margin: 0 0 18px;
        font-size: 20px;
        line-height: 1.3;
        color: #0f172a;
        font-weight: 700;
        letter-spacing: -0.01em;
    }


    /* =========================================================
       TWO COLUMN INFORMATION
       ========================================================= */

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 40px;
        row-gap: 18px;
    }

    .detail-item {
        min-width: 0;
    }

    .detail-label {
        margin-bottom: 4px;
        color: #64748b;
        font-size: 12px;
        font-weight: 400;
        line-height: 1.3;
    }

    .detail-value {
        color: #0f172a;
        font-size: 14px;
        line-height: 1.4;
        font-weight: 500;
        overflow-wrap: anywhere;
    }

    .detail-value.muted-value {
        color: #64748b;
        font-weight: 400;
    }


    /* =========================================================
       QUICK ACTIONS
       ========================================================= */

    .quick-actions {
        padding: 14px;
        margin-bottom: 0;
    }

    .quick-actions-title {
        margin: 0 0 13px;
        color: #60718b;
        font-size: 13px;
        line-height: 1;
        letter-spacing: 1.2px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .quick-group {
        border: 1px solid #dce5f0;
        border-radius: 12px;
        padding: 9px;
        margin-bottom: 10px;
        background: #ffffff;
    }

    .quick-group:last-child {
        margin-bottom: 0;
    }

    .quick-group-title {
        margin: 0 0 8px;
        color: #91a0b5;
        font-size: 10px;
        letter-spacing: .8px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .quick-action {
        width: 100%;
        min-height: 34px;
        display: flex;
        align-items: center;
        gap: 7px;
        border: 1px solid #cbd8e8;
        border-radius: 7px;
        background: #ffffff;
        color: #40516a;
        padding: 7px 10px;
        margin-bottom: 7px;
        text-align: left;
        text-decoration: none;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: .15s ease;
        box-sizing: border-box;
        font-family: inherit;
        line-height: 1.3;
    }

    .quick-action:last-child {
        margin-bottom: 0;
    }

    .quick-action:hover {
        border-color: #94b6e8;
        color: #1e4f95;
        background: #f8fbff;
    }

    .quick-action.primary {
        background: #ffffff;
        border-color: #cbd8e8;
        color: #40516a;
    }

    .quick-action.primary:hover {
        border-color: #94b6e8;
        color: #1e4f95;
        background: #f8fbff;
    }

    .stage-update-form {
        position: relative;
    }

    .stage-toggle {
        position: relative;
        text-align: left;
    }

    .stage-options {
        display: none;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }

    .stage-options.is-open {
        display: block;
    }

    .quick-icon {
        width: 14px;
        min-width: 14px;
        text-align: center;
        font-size: 11px;
    }

    .op-engagements-subtitle {
        font-size: 11px;
        color: #64748b;
        margin: -4px 0 10px 0;
        font-weight: 500;
        line-height: 1.35;
    }

    .op-workspaces-stack {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .op-workspace-card {
        border: 1px solid #dce5f0;
        border-radius: 9px;
        padding: 10px 11px;
        background: #f8fafc;
        transition: all .15s ease;
        box-sizing: border-box;
    }

    .op-workspace-card:hover {
        border-color: #94b6e8;
        background: #ffffff;
        box-shadow: 0 2px 8px rgba(30, 79, 149, 0.05);
    }

    .op-workspace-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 4px;
    }

    .op-workspace-icon-box {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .op-workspace-icon-box.project {
        background: #f0fdf4;
        color: #16a34a;
    }

    .op-workspace-icon-box.regular {
        background: #eff6ff;
        color: #2563eb;
    }

    .op-workspace-title {
        font-size: 12px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }

    .op-workspace-desc {
        font-size: 11px;
        color: #64748b;
        line-height: 1.35;
        margin: 0 0 9px 0;
    }

    .op-workspace-btn {
        width: 100%;
        min-height: 32px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        border: 1px solid #cbd8e8;
        border-radius: 7px;
        background: #ffffff;
        color: #1e4f95;
        padding: 6px 10px;
        text-align: left;
        text-decoration: none;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: .15s ease;
        box-sizing: border-box;
        font-family: inherit;
        line-height: 1.3;
    }

    .op-workspace-btn:hover {
        background: #1e4f95;
        border-color: #1e4f95;
        color: #ffffff;
    }

    .op-workspace-btn:hover .op-btn-arrow {
        transform: translateX(3px);
    }

    .op-btn-arrow {
        font-size: 13px;
        line-height: 1;
        transition: transform .15s ease;
    }

    .op-workspace-empty {
        padding: 12px 10px;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        font-size: 11px;
        color: #64748b;
        text-align: center;
        line-height: 1.4;
    }


    /* =========================================================
       RELATED CONTACT
       ========================================================= */

    .related-card {
        padding: 15px;
        margin-top: 0;
    }

    .related-card h2 {
        margin: 0 0 12px;
        color: #172033;
        font-size: 14px;
        font-weight: 700;
    }

    .contact-card {
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .contact-avatar {
        width: 34px;
        height: 34px;
        min-width: 34px;
        border-radius: 50%;
        background: #dce9ff;
        color: #3869d7;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
    }

    .contact-name {
        font-size: 13px;
        font-weight: 700;
        color: #172033;
        margin-bottom: 3px;
    }

    .contact-position {
        font-size: 10px;
        color: #64748b;
        margin-bottom: 7px;
    }

    .contact-line {
        color: #64748b;
        font-size: 10px;
        line-height: 1.6;
    }

    .contact-line i {
        width: 13px;
        color: #475569;
    }


    /* =========================================================
       TAGS
       ========================================================= */

    .tag-link {
        display: inline-block;
        color: #2563eb;
        font-size: 11px;
        font-weight: 600;
        text-decoration: none;
    }

    .tag-link:hover {
        text-decoration: underline;
    }


    /* =========================================================
       DEAL FORM PREVIEW
       ========================================================= */

    .deal-form-preview {
        border: 1px solid #1e293b;
        background: #ffffff;
        overflow: hidden;
    }

    .deal-form-title {
        text-align: center;
        font-size: 20px;
        font-weight: 800;
        color: #050b17;
        padding: 9px 8px;
        border-bottom: 1px solid #1e293b;
    }

    .deal-form-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
    }

    .form-cell {
        min-height: 48px;
        padding: 7px;
        border-right: 1px solid #1e293b;
        border-bottom: 1px solid #1e293b;
        font-size: 9px;
        color: #111827;
    }

    .form-cell:nth-child(4n) {
        border-right: 0;
    }

    .form-cell-label {
        font-size: 8px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .form-cell-value {
        font-size: 9px;
        line-height: 1.3;
    }

    .form-section-header {
        grid-column: 1 / -1;
        padding: 5px;
        text-align: center;
        color: #ffffff;
        background: #24498c;
        border-bottom: 1px solid #1e293b;
        font-size: 10px;
        font-weight: 700;
    }

    .form-full-cell {
        grid-column: 1 / -1;
        min-height: 48px;
        padding: 7px;
        border-bottom: 1px solid #1e293b;
        font-size: 9px;
    }

    .form-half-cell {
        grid-column: span 2;
        min-height: 48px;
        padding: 7px;
        border-right: 1px solid #1e293b;
        border-bottom: 1px solid #1e293b;
        font-size: 9px;
    }

    .form-half-cell:nth-last-child(1) {
        border-right: 0;
    }

    .form-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8px;
    }

    .form-table th,
    .form-table td {
        border: 1px solid #1e293b;
        padding: 5px;
        text-align: left;
        vertical-align: top;
    }

    .form-table th {
        font-weight: 800;
        text-align: center;
    }


    /* =========================================================
       START FORM
       ========================================================= */

    .start-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 13px;
    }

    .start-header h2 {
        margin-bottom: 4px;
    }

    .start-description {
        margin: 0;
        color: #64748b;
        font-size: 11px;
    }

    .start-actions {
        display: flex;
        gap: 7px;
        flex-wrap: wrap;
    }

    .start-action {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 8px 10px;
        border: 1px solid #cbd8e8;
        border-radius: 6px;
        background: #ffffff;
        color: #334155;
        text-decoration: none;
        font-size: 10px;
        font-weight: 600;
    }

    .start-action:hover {
        color: #1e4f95;
        border-color: #94b6e8;
    }




    /* =========================================================
       STAGE PROGRESS
       ========================================================= */

    .stage-progress {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 3px;
        overflow-x: auto;
        padding: 8px 4px 4px;
    }

    .stage-step {
        position: relative;
        flex: 1;
        min-width: 75px;
        text-align: center;
        color: #64748b;
        font-size: 9px;
        white-space: nowrap;
    }

    .stage-step:not(:last-child)::after {
        content: "";
        position: absolute;
        top: 11px;
        left: calc(50% + 12px);
        right: calc(-50% + 12px);
        height: 2px;
        background: #e2e8f0;
        z-index: 0;
    }

    .stage-step.complete:not(:last-child)::after {
        background: #2563eb;
    }

    .stage-dot {
        position: relative;
        z-index: 2;
        width: 28px;
        height: 28px;
        margin: 0 auto 6px;
        border-radius: 50%;
        border: 1px solid #dbe3ed;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        color: transparent;
        font-size: 12px;
        font-weight: 800;
    }

    .stage-step.complete .stage-dot {
        background: #2f6bea;
        border-color: #2f6bea;
        color: #ffffff;
    }

    .stage-step.complete .stage-dot::before {
        content: "✓";
    }

    .stage-step.current {
        color: #2563eb;
        font-weight: 700;
    }

    .stage-step.current .stage-dot {
        background: #ffffff;
        border: 3px solid #2563eb;
    }


    /* =========================================================
       TABS
       ========================================================= */

    .detail-tabs {
        padding: 5px;
        display: flex;
        gap: 3px;
        flex-wrap: wrap;
    }

    .detail-tabs a {
        color: #64748b;
        text-decoration: none;
        padding: 8px 10px;
        border-radius: 7px;
        font-size: 10px;
        font-weight: 500;
    }

    .detail-tabs a:first-child {
        background: #eff6ff;
        color: #2563eb;
    }

    .detail-tabs a:hover {
        background: #eff6ff;
        color: #2563eb;
    }

    .timeline-entry {
        margin-top: 12px;
        border: 1px solid #e5eaf1;
        border-radius: 9px;
        padding: 12px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .timeline-icon {
        width: 27px;
        height: 27px;
        min-width: 27px;
        border-radius: 50%;
        background: #e2edff;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
    }

    .timeline-title {
        font-size: 12px;
        font-weight: 700;
        color: #172033;
    }

    .timeline-meta {
        margin-top: 3px;
        color: #64748b;
        font-size: 9px;
    }


    /* =========================================================
       TABLES
       ========================================================= */

    .detail-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10px;
    }

    .detail-table th,
    .detail-table td {
        border: 1px solid #d7e0eb;
        padding: 8px;
        text-align: left;
        color: #475569;
    }

    .detail-table th {
        color: #334155;
        background: #f8fafc;
        font-weight: 700;
    }


    /* =========================================================
       RESPONSIVE
       ========================================================= */

    @media (max-width: 1100px) {
        .detail-layout {
        width: 100%;
        margin: 0;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 340px;
        gap: 16px;
        align-items: start;
    }
    }

    @media (max-width: 900px) {
        .deal-detail-page {
        min-height: 100%;
        padding: 24px;
        background: #f8fafc;
        color: #172033;
        font-family: inherit;
    }

        .detail-layout {
        width: 100%;
        margin: 0;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 340px;
        gap: 16px;
        align-items: start;
    }

        .quick-actions {
            position: static;
        }

        .detail-grid {
            column-gap: 25px;
        }

        .clearance-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 650px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }

        .deal-form-grid {
            grid-template-columns: 1fr 1fr;
        }

        .form-cell:nth-child(4n) {
            border-right: 1px solid #1e293b;
        }

        .start-header {
            display: block;
        }

        .start-actions {
            margin-top: 10px;
        }

        .clearance-grid {
            grid-template-columns: 1fr;
        }

        .start-info-grid {
            grid-template-columns: 1fr;
        }

        .start-info-cell:nth-child(2n) {
            border-right: 1px solid #1e293b;
        }

        .stage-step {
            min-width: 65px;
        }
    }

    /* =========================================================
       HISTORY & TRACEABILITY - NOTIFICATION / ACTIVITY FEED
       ========================================================= */
    #tab-panel-history {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    .history-container-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 310px;
        gap: 18px;
        align-items: flex-start;
    }

    .history-main-column {
        min-width: 0;
    }

    .history-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 20px 22px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .history-header-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        padding-bottom: 14px;
        margin-bottom: 14px;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
    }

    .history-title-group h2 {
        font-size: 17px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 2px 0;
        letter-spacing: -0.2px;
    }

    .history-title-group p {
        margin: 0;
        font-size: 12px;
        color: #64748b;
        line-height: 1.4;
    }

    .history-actions-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .history-search-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .history-search-icon {
        position: absolute;
        left: 10px;
        color: #94a3b8;
        pointer-events: none;
    }

    .history-search-input {
        height: 34px;
        border: 1px solid #dbe2ea;
        border-radius: 6px;
        padding: 0 10px 0 30px;
        font-size: 12.5px;
        color: #0f172a;
        background: #ffffff;
        width: 190px;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        outline: none;
    }

    .history-search-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
    }

    .history-btn-add-note {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #ffffff;
        color: #334155;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        height: 34px;
        padding: 0 12px;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        font-family: inherit;
        white-space: nowrap;
    }

    .history-btn-add-note:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
    }

    /* SCROLLABLE NOTIFICATION FEED */
    .history-feed-container {
        max-height: 540px;
        overflow-y: auto;
        padding-right: 6px;
        display: flex;
        flex-direction: column;
        gap: 2px;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f8fafc;
    }

    .history-feed-container::-webkit-scrollbar {
        width: 6px;
    }

    .history-feed-container::-webkit-scrollbar-track {
        background: #f8fafc;
        border-radius: 4px;
    }

    .history-feed-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .history-feed-container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* COMPACT NOTIFICATION ROW */
    .history-feed-item {
        position: relative;
        padding: 10px 12px 10px 24px;
        border-bottom: 1px solid #f1f5f9;
        border-radius: 6px;
        transition: background 0.15s ease;
    }

    .history-feed-item:hover {
        background: #f8fafc;
    }

    .history-feed-item.is-latest {
        background: #f8fbff;
        border-left: 3px solid #2563eb;
        padding-left: 21px;
    }

    /* SMALL CIRCULAR MARKER */
    .history-feed-dot {
        position: absolute;
        left: 9px;
        top: 15px;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #94a3b8;
    }

    .history-feed-item.is-latest .history-feed-dot {
        background: #2563eb;
        box-shadow: 0 0 0 2px #bfdbfe;
    }

    .history-feed-top-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
    }

    .history-feed-type {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
    }

    .history-feed-curr-tag {
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid #bfdbfe;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .history-feed-body {
        margin: 4px 0 4px 0;
        font-size: 13px;
        color: #334155;
        line-height: 1.45;
        font-weight: 500;
    }

    .history-feed-transition {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        font-size: 13px;
        color: #0f172a;
        margin: 3px 0;
    }

    .history-feed-arrow {
        color: #94a3b8;
    }

    .history-feed-doc {
        font-size: 13px;
        color: #0f172a;
        font-weight: 600;
        margin: 3px 0;
    }

    .history-feed-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
        color: #64748b;
        margin-top: 4px;
        flex-wrap: wrap;
        gap: 6px;
    }

    .history-feed-subline {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
    }

    .history-feed-view-btn {
        background: none;
        border: none;
        color: #2563eb;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        padding: 0;
        font-family: inherit;
        text-decoration: none;
    }

    .history-feed-view-btn:hover {
        text-decoration: underline;
    }

    .history-expanded-box {
        margin-top: 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 10px 12px;
        font-size: 11.5px;
        color: #334155;
    }

    .history-expanded-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 8px 12px;
    }

    .history-expanded-cell-label {
        font-size: 10px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .history-expanded-cell-val {
        font-size: 11.5px;
        font-weight: 600;
        color: #0f172a;
        margin-top: 1px;
        word-break: break-word;
    }

    /* RIGHT SIDEBAR */
    .history-sidebar-column {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .history-sidebar-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 16px 18px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .history-sidebar-heading {
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 12px 0;
        letter-spacing: -0.1px;
    }

    .history-filter-group {
        margin-bottom: 10px;
    }

    .history-filter-label {
        font-size: 10.5px;
        font-weight: 600;
        color: #475569;
        text-transform: uppercase;
        margin-bottom: 3px;
        display: block;
        letter-spacing: 0.3px;
    }

    .history-filter-select, .history-filter-date {
        width: 100%;
        height: 34px;
        border: 1px solid #dbe2ea;
        border-radius: 6px;
        padding: 0 8px;
        font-size: 12.5px;
        color: #0f172a;
        background: #ffffff;
        box-sizing: border-box;
        outline: none;
    }

    .history-filter-select:focus, .history-filter-date:focus {
        border-color: #2563eb;
    }

    .history-filter-btn-group {
        display: flex;
        gap: 6px;
        margin-top: 12px;
    }

    .history-btn-apply {
        flex: 1;
        background: #2563eb;
        color: #ffffff;
        border: 1px solid #2563eb;
        border-radius: 5px;
        height: 33px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .history-btn-apply:hover {
        background: #1d4ed8;
    }

    .history-btn-reset {
        background: #ffffff;
        color: #475569;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        height: 33px;
        padding: 0 12px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
    }

    .history-btn-reset:hover {
        background: #f8fafc;
        color: #0f172a;
    }

    /* STAGE PROGRESSION COMPACT LIST */
    .stage-prog-list {
        position: relative;
        padding-left: 18px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .stage-prog-list::before {
        content: "";
        position: absolute;
        top: 6px;
        bottom: 6px;
        left: 3px;
        width: 1.5px;
        background: #e2e8f0;
    }

    .stage-prog-item {
        position: relative;
        font-size: 12px;
        line-height: 1.35;
    }

    .stage-prog-dot {
        position: absolute;
        top: 4px;
        left: -18px;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #cbd5e1;
        border: 2px solid #ffffff;
        box-shadow: 0 0 0 1px #cbd5e1;
        z-index: 2;
    }

    .stage-prog-item.completed .stage-prog-dot {
        background: #22c55e;
        box-shadow: 0 0 0 1px #22c55e;
    }

    .stage-prog-item.current .stage-prog-dot {
        background: #2563eb;
        box-shadow: 0 0 0 2px #bfdbfe;
    }

    .stage-prog-title {
        font-weight: 600;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .stage-prog-status {
        font-size: 11px;
        color: #64748b;
        margin-top: 1px;
    }

    .stage-prog-badge-curr {
        background: #2563eb;
        color: #ffffff;
        font-size: 9px;
        font-weight: 700;
        padding: 1px 5px;
        border-radius: 4px;
        text-transform: uppercase;
    }

    /* TOTAL ACTIVITY COMPACT */
    .history-stat-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .history-stat-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 12px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    .history-stat-count {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
        text-align: center;
    }

    .history-stat-label {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        margin-top: 2px;
        text-align: center;
    }

    .history-load-more-wrap {
        text-align: center;
        padding: 10px 0 4px 0;
    }

    .history-btn-load-more {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #334155;
        border-radius: 5px;
        height: 32px;
        padding: 0 16px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .history-btn-load-more:hover {
        background: #f8fafc;
        color: #0f172a;
    }

    @media (max-width: 950px) {
        .history-container-layout {
            grid-template-columns: 1fr;
        }
    }

    /* =========================================================
       STAGE WORKFLOW TOAST NOTIFICATION STYLES
       ========================================================= */
    .stage-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: #0f172a;
        color: #ffffff;
        border-radius: 8px;
        padding: 12px 18px;
        font-size: 13px;
        font-weight: 500;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: 380px;
        animation: toastSlideUp 0.25s ease-out;
    }

    .stage-toast.toast-warning {
        background: #1e293b;
        border-left: 4px solid #f59e0b;
    }

    .stage-toast.toast-success {
        background: #064e3b;
        border-left: 4px solid #10b981;
    }

    .stage-toast.toast-error {
        background: #7f1d1d;
        border-left: 4px solid #ef4444;
    }

    @keyframes toastSlideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>


<div class="deal-detail-page">

    {{-- =====================================================
         BREADCRUMB
    ====================================================== --}}

    <div class="deal-detail-breadcrumb">
        <a href="{{ route('deals.index') }}">
            ← Deals
        </a>

        <span style="margin:0 7px;">/</span>

        <strong>
            {{ $display($deal->deal_code) }}
        </strong>
    </div>


    {{-- =====================================================
         DEAL HEADER
    ====================================================== --}}

    @php
        $headerExpDate = $deal->expected_close ?: $deal->estimated_completion_date;
        $headerDaysRemaining = $headerExpDate ? (int) now()->startOfDay()->diffInDays($headerExpDate->startOfDay(), false) : null;
        $dealCodeDisplay = $deal->deal_code ?: ('CONDEAL-' . date('Y') . '-' . str_pad($deal->id, 3, '0', STR_PAD_LEFT));
        $totalValNumber = (float) ($deal->total_estimated_engagement_value ?: ($deal->amount ?: 0));
        $clientDisplay = $deal->company_name ?: ($deal->company ?: ($contactName !== 'Not provided' ? $contactName : ($deal->deal_title ?: 'Untitled Deal')));
        $primaryContactDisplay = $contactName !== 'Not provided' ? $contactName : ($deal->primary_contact_name ?: ($deal->first_name ? trim($deal->first_name . ' ' . $deal->last_name) : 'Not provided'));
        // Dynamic Next Action derivation: Single most important pending action with owner/due date
        $resolvedNextAction = null;
        $resolvedNextOwner = null;
        $resolvedNextDue = null;

        // 1. Check for active/pending Client Action Requests on the deal
        $pendingClientAction = $deal->pendingClientActionRequests?->first()
            ?? $deal->clientActionRequests?->firstWhere(fn($ca) => in_array($ca->status, ['pending', 'awaiting_client', 'draft', 'dispatched', 'in_progress'], true));

        if ($pendingClientAction) {
            $resolvedNextAction = $pendingClientAction->title
                ?: ($pendingClientAction->action_type ? ucwords(str_replace('_', ' ', $pendingClientAction->action_type)) : 'Pending Client Action');
            $resolvedNextOwner = $pendingClientAction->assigned_to
                ?: ($pendingClientAction->requested_by
                ?: ($deal->owner_name ?: ($deal->assigned_consultant ?: ($deal->user?->name ?: 'Unassigned'))));
            $resolvedNextDue = $pendingClientAction->due_date
                ? $pendingClientAction->due_date->format('M d, Y')
                : ($deal->client_preferred_completion_date ? $deal->client_preferred_completion_date->format('M d, Y') : ($deal->expected_close ? $deal->expected_close->format('M d, Y') : 'TBD'));
        }

        // 2. Check explicitly defined required actions on the deal
        if (!$resolvedNextAction && !empty($deal->required_actions) && is_array($deal->required_actions)) {
            foreach ($deal->required_actions as $reqAct) {
                $actName = is_array($reqAct) ? ($reqAct['action'] ?? ($reqAct['name'] ?? null)) : (is_string($reqAct) ? $reqAct : null);
                $isCompleted = is_array($reqAct) ? (!empty($reqAct['completed']) || ($reqAct['status'] ?? '') === 'completed') : false;
                if ($actName && !$isCompleted) {
                    $resolvedNextAction = $actName;
                    $resolvedNextOwner = is_array($reqAct) ? ($reqAct['owner'] ?? null) : null;
                    $resolvedNextDue = is_array($reqAct) ? ($reqAct['due_date'] ?? null) : null;
                    break;
                }
            }
        }

        // 3. If still empty, derive based on current workflow/stage business rules & pending milestones
        if (!$resolvedNextAction) {
            $currentStage = $deal->pipeline_stage ?: 'Inquiry';

            if (in_array($currentStage, ['Closed Won', 'Closed Lost'], true)) {
                $resolvedNextAction = null; // No pending action
            } elseif ($currentStage === 'Inquiry') {
                if (empty($deal->qualification_result) && empty($deal->client_need)) {
                    $resolvedNextAction = 'Lead Qualification & Requirements Intake';
                } else {
                    $resolvedNextAction = 'Schedule Initial Consultation';
                }
            } elseif ($currentStage === 'Qualification') {
                if (empty($deal->decision_maker) || empty($deal->qualification_notes)) {
                    $resolvedNextAction = 'Confirm Decision Maker & Scope';
                } else {
                    $resolvedNextAction = 'Schedule Consultation Session';
                }
            } elseif ($currentStage === 'Consultation') {
                if (empty($deal->consultation_date) && empty($deal->requirements_confirmed)) {
                    $resolvedNextAction = 'Conduct Consultation & Confirm Requirements';
                } else {
                    $resolvedNextAction = 'Draft Commercial Proposal';
                }
            } elseif ($currentStage === 'Proposal') {
                $propStatus = strtolower($deal->proposal?->status ?? ($deal->proposal_status ?? ($deal->proposal_decision ?? 'draft')));
                if (in_array($propStatus, ['draft', 'pending', 'in review', ''])) {
                    $resolvedNextAction = 'Finalize & Send Proposal to Client';
                } elseif (in_array($propStatus, ['sent', 'awaiting_decision', 'delivered'])) {
                    $resolvedNextAction = 'Follow-up on Client Proposal Decision';
                } else {
                    $resolvedNextAction = 'Proceed to Terms Negotiation';
                }
            } elseif ($currentStage === 'Negotiation') {
                if (empty($deal->final_deal_value) || empty($deal->pricing_model)) {
                    $resolvedNextAction = 'Finalize Commercial Terms & Pricing';
                } else {
                    $resolvedNextAction = 'Request Initial Payment';
                }
            } elseif ($currentStage === 'Payment') {
                $payStatus = strtolower($deal->payment_status ?? '');
                if (!in_array($payStatus, ['completed', 'verified', 'paid', 'verified / confirmed'])) {
                    $resolvedNextAction = 'Verify Payment & Issue Official Receipt';
                } else {
                    $resolvedNextAction = 'Issue Service Activation Memo';
                }
            } elseif ($currentStage === 'Activation') {
                $hasActiveStart = ($deal->startRecords && $deal->startRecords->count() > 0);
                if (!$hasActiveStart) {
                    $resolvedNextAction = 'Generate START Batch & Assign Team';
                } else {
                    $resolvedNextAction = 'Execute Service Delivery Milestones';
                }
            } else {
                $resolvedNextAction = $deal->next_action ?: null;
            }
        }

        // Fallback owner & due date
        if ($resolvedNextAction) {
            $resolvedNextOwner = $resolvedNextOwner ?: ($deal->owner_name ?: ($deal->assigned_consultant ?: ($deal->assigned_associate ?: ($deal->user?->name ?: 'Unassigned'))));
            $resolvedNextDue = $resolvedNextDue ?: ($deal->client_preferred_completion_date ? $deal->client_preferred_completion_date->format('M d, Y') : ($deal->expected_close ? $deal->expected_close->format('M d, Y') : 'TBD'));
        }
        // Accurate dynamic status badges
        $kycStatus = $deal->kyc_status ?: ($deal->account?->kyc_status ?: 'Pending');

        if (!empty($deal->payment_status)) {
            $paymentStatus = $deal->payment_status;
        } elseif (in_array($currentStage, ['Activation', 'Closed Won'])) {
            $paymentStatus = 'Completed';
        } elseif ($currentStage === 'Payment') {
            $paymentStatus = 'Pending Verification';
        } else {
            $paymentStatus = 'Pending';
        }

        $hasStartRecords = ($deal->startRecords && $deal->startRecords->count() > 0);
        $startStatus = $deal->start_status ?: ($hasStartRecords || in_array($currentStage, ['Activation', 'Closed Won']) ? 'Ready' : 'Not Ready');

        if ($deal->proposal && !empty($deal->proposal->status)) {
            $proposalStatus = ucfirst($deal->proposal->status);
        } elseif (!empty($deal->proposal_status)) {
            $proposalStatus = ucfirst($deal->proposal_status);
        } elseif (!empty($deal->proposal_decision)) {
            $proposalStatus = ucfirst($deal->proposal_decision);
        } elseif (in_array($currentStage, ['Negotiation', 'Payment', 'Activation', 'Closed Won'])) {
            $proposalStatus = 'Approved';
        } elseif ($currentStage === 'Proposal') {
            $proposalStatus = 'In Review';
        } else {
            $proposalStatus = 'Draft';
        }

        $commercialStatus = $deal->commercial_stage ?: ($currentStage ?: 'Inquiry');
        $casaStatus = $deal->casa_stage ?: ($currentStage ?: 'Inquiry');

        // Persistent Stage Duration & Start Time Calculation
        $stageEnteredAt = $deal->current_stage_started_at;
        $stageStartedAtManila = $stageEnteredAt->copy()->timezone('Asia/Manila');
        $stageStartedAtFormatted = $stageStartedAtManila->format('M d, Y · g:i A');
        $stageStartTimestampMs = $stageStartedAtManila->getTimestamp() * 1000;

        $isStageActive = !in_array($currentStage, ['Closed Won', 'Closed Lost']);
        $initialElapsedSeconds = max(0, $stageStartedAtManila->diffInSeconds(now()->timezone('Asia/Manila')));
        $initialTimerDisplay = \App\Models\DealStageHistory::formatDuration($initialElapsedSeconds);

        // Stage durations map from persistent histories
        $stageDurations = $deal->getStageDurationsMap();
    @endphp

    <header class="deal-detail-header-wrap">

        {{-- 1. TOP SUMMARY CARD --}}
        <div class="deal-detail-header-top">
            <div class="dh-top-row">

                <div class="dh-left-block">
                    {{-- Small uppercase label --}}
                    <div class="dh-deal-label">
                        DEAL
                    </div>

                    {{-- Deal ID --}}
                    <div class="dh-deal-id">
                        {{ $dealCodeDisplay }}
                    </div>

                    {{-- Client / Company Name --}}
                    <div class="dh-client-name">
                        {{ $clientDisplay }}
                    </div>

                    {{-- Client Type Pill --}}
                    <div class="dh-type-badge">
                        {{ $deal->customer_type ?: 'Business' }}
                    </div>

                    {{-- Unified Status Row --}}
                    <div class="dh-status-row">
                        <span class="dh-badge">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            KYC: {{ $kycStatus }}
                        </span>

                        <span class="dh-badge">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                            Payment: {{ $paymentStatus }}
                        </span>

                        <span class="dh-badge">
                            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                            START: {{ $startStatus }}
                        </span>

                        <span class="dh-badge">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            Proposal: {{ $proposalStatus }}
                        </span>

                        <span class="dh-badge">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="2" y1="21" x2="22" y2="21"></line><line x1="6" y1="10" x2="6" y2="18"></line><line x1="10" y1="10" x2="10" y2="18"></line><line x1="14" y1="10" x2="14" y2="18"></line><line x1="18" y1="10" x2="18" y2="18"></line><polygon points="12 2 2 7 22 7 12 2"></polygon></svg>
                            Commercial: {{ $commercialStatus }}
                        </span>

                        <span class="dh-badge">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            CASA: {{ $casaStatus }}
                        </span>
                    </div>
                </div>

                {{-- Stage Timer Widget (Right Side) --}}
                <div class="dh-timer-box"
                     id="dealStageTimerBox"
                     data-stage-start-ms="{{ $stageStartTimestampMs }}"
                     data-stage-name="{{ $currentStage }}"
                     data-is-active="{{ $isStageActive ? 'true' : 'false' }}"
                     data-tooltip="Shows the live elapsed duration this deal has spent in the {{ $currentStage }} stage.">
                    <div class="dh-timer-stage" id="dealStageTimerStage">
                        {{ strtoupper($currentStage) }}
                    </div>
                    <div class="dh-timer-display" id="dealStageTimerDisplay">
                        {{ $initialTimerDisplay }}
                    </div>
                    <div class="dh-timer-date">
                        <span id="dealStageDateDisplay">Since {{ $stageStartedAtFormatted }}</span>
                    </div>
                </div>

            </div>
        </div>

        {{-- 2. FIVE-CARD GRID SECTION --}}
        <div class="dh-cards-grid">

            {{-- 1. CLIENT --}}
            <div class="dh-card" data-tooltip="Shows the client or company associated with this deal.">
                <div class="dh-card-header">CLIENT</div>
                <div class="dh-card-title">{{ $clientDisplay }}</div>
                <div class="dh-card-sub">{{ $deal->customer_type ?: 'Individual' }}</div>
            </div>

            {{-- 2. PRIMARY CONTACT --}}
            <div class="dh-card" data-tooltip="Shows the main contact responsible for this business account.">
                <div class="dh-card-header">PRIMARY CONTACT</div>
                <div class="dh-card-title">{{ $primaryContactDisplay }}</div>
                <div class="dh-card-sub">{{ $deal->position ?: ($deal->primaryContact?->job_title ?: ($deal->customer_type ?: 'Principal Legal & Tax Counsel')) }}</div>
            </div>

            {{-- 3. EXPECTED CLOSE --}}
            <div class="dh-card" data-tooltip="Shows the expected date when the deal is expected to close.">
                <div class="dh-card-header">EXPECTED CLOSE</div>
                <div class="dh-card-title">{{ $headerExpDate ? $headerExpDate->format('F d, Y') : 'TBD' }}</div>
                <div class="dh-card-sub">
                    @if($headerDaysRemaining !== null)
                        @if($headerDaysRemaining > 0)
                            {{ $headerDaysRemaining }} {{ $headerDaysRemaining === 1 ? 'day remaining' : 'days remaining' }}
                        @elseif($headerDaysRemaining === 0)
                            Due today
                        @else
                            {{ abs($headerDaysRemaining) }} {{ abs($headerDaysRemaining) === 1 ? 'day overdue' : 'days overdue' }}
                        @endif
                    @else
                        TBD
                    @endif
                </div>
            </div>

            {{-- 4. NEXT ACTION --}}
            <div class="dh-card" data-tooltip="Shows the single most important pending action required to move the deal forward.">
                <div class="dh-card-header">NEXT ACTION</div>
                @if($resolvedNextAction)
                    <div class="dh-card-title">{{ $resolvedNextAction }}</div>
                    <div class="dh-card-meta">Owner: {{ $resolvedNextOwner }}</div>
                    <div class="dh-card-meta">Due: {{ $resolvedNextDue }}</div>
                @else
                    <div class="dh-card-title" style="color: #64748b; font-weight: 500;">No pending action</div>
                @endif
            </div>

            {{-- 5. DEAL VALUE --}}
            <div class="dh-card" data-tooltip="Shows the commercial value of the deal based on the applicable pricing or approved line items.">
                <div class="dh-card-header" style="display:flex; align-items:center; justify-content:space-between;">
                    <span>DEAL VALUE</span>
                    <button type="button" 
                            class="dh-value-toggle-btn" 
                            id="dealValueToggleBtn" 
                            onclick="toggleDealValuePrivacy(event)" 
                            title="Toggle deal value visibility"
                            aria-label="Toggle deal value visibility">
                        <svg id="dealValueEyeOpen" class="dh-eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg id="dealValueEyeClosed" class="dh-eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
                <div class="dh-card-value" id="dealValueDisplay">
                    <span class="dh-value-real">₱{{ number_format($totalValNumber, 2) }}</span>
                    <span class="dh-value-masked" style="display:none; letter-spacing: 2px;">••••••</span>
                </div>
            </div>

        </div>

        {{-- 3. DEAL STAGE PROGRESS CARD --}}
        <div class="dh-stage-progress-card">
            <div class="dh-stage-progress-title">
                DEAL STAGE PROGRESS
            </div>
            <div class="dh-pipeline-container">
                <div class="dh-pipeline" id="dealPipelineIndicator">
                    @php
                        $stageExplanationMap = [
                            'Inquiry' => "Initial stage where the client's request or opportunity is recorded.",
                            'Qualification' => "Confirm whether the client, need, and opportunity meet the applicable qualification requirements.",
                            'Consultation' => "Stage for discussing the client's requirements and proposed approach.",
                            'Proposal' => "Stage where the applicable services and commercial proposal are prepared or reviewed.",
                            'Negotiation' => "Stage where commercial terms or requested changes are being discussed.",
                            'Payment' => "Stage for completing the applicable payment requirements.",
                            'Activation' => "Stage where the approved service moves into activation.",
                            'Closed Won' => "Deal has been successfully completed and won.",
                            'Closed Lost' => "Deal has been closed without proceeding."
                        ];
                    @endphp
                    @foreach($stages as $idx => $stg)
                        @php
                            $isOutcomeStage = in_array($currentStage, ['Closed Won', 'Closed Lost']);
                            if ($isOutcomeStage) {
                                $isComplete = ($idx < 7) || ($stg === $currentStage);
                                $isCurrent = ($stg === $currentStage);
                                $isLocked = ($idx >= 7 && $stg !== $currentStage);
                            } else {
                                $isComplete = ($idx < $stageIndex);
                                $isCurrent = ($idx === $stageIndex);
                                $isLocked = ($idx > $stageIndex);
                            }
                            $stepClass = $isComplete ? 'is-complete' : ($isCurrent ? 'is-current' : 'is-upcoming is-locked');
                            $stageTooltip = $stageExplanationMap[$stg] ?? '';
                            if ($isLocked) {
                                $stageTooltip .= ' (Locked: complete earlier stages first)';
                            } elseif ($isCurrent) {
                                $stageTooltip .= ' (Current active stage)';
                            }
                        @endphp
                        <div class="dh-pipe-step {{ $stepClass }}"
                             id="pipe-step-{{ Str::slug($stg) }}"
                             data-stage="{{ $stg }}"
                             data-index="{{ $idx }}"
                             data-status="{{ $isComplete ? 'completed' : ($isCurrent ? 'current' : 'locked') }}"
                             data-tooltip="{{ $stageTooltip }}"
                             onclick="handlePipelineStepClick('{{ $stg }}', {{ $idx }})"
                             style="cursor: pointer;">
                            <div class="dh-pipe-node">
                                @if($isComplete)
                                    ✓
                                @elseif($isCurrent)
                                    <span class="dh-pipe-current-dot"></span>
                                @endif
                            </div>
                            <div class="dh-pipe-label">
                                {{ $stg }}
                            </div>
                            <div class="dh-pipe-duration">
                                {{ $stageDurations[$stg] ?? '-' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </header>


    {{-- =====================================================
         DEAL TABS (STANDALONE - NOT CONNECTED TO CONDEAL HEADER)
    ====================================================== --}}
    <div class="deal-nav-tabs-wrapper">
        <div class="deal-nav-bar-container" id="dealNavBarContainer">
            <button
                type="button"
                class="deal-tab-arrow deal-tab-arrow-left"
                id="dealTabArrowLeft"
                aria-label="Scroll tabs left"
                onclick="scrollDealTabs('left')"
            >
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>

            <div class="deal-nav-bar-scroll" id="dealNavBarScroll">
                <nav class="deal-nav-bar" aria-label="Deal Tabs">
                    <button type="button" class="deal-nav-item active" data-tab="overview" onclick="switchDealNavTab('overview', this)">
                        Overview
                    </button>
                    <button type="button" class="deal-nav-item" data-tab="inquiry" onclick="switchDealNavTab('inquiry', this)">
                        Inquiry
                    </button>
                    <button type="button" class="deal-nav-item" data-tab="consultation" onclick="switchDealNavTab('consultation', this)">
                        Consultation
                    </button>
                    <button type="button" class="deal-nav-item" data-tab="services-pricing" onclick="switchDealNavTab('services-pricing', this)">
                        Services &amp; Pricing
                    </button>
                    <button type="button" class="deal-nav-item" data-tab="proposal" onclick="switchDealNavTab('proposal', this)">
                        Proposal
                    </button>
                    <button type="button" class="deal-nav-item" data-tab="finance" onclick="switchDealNavTab('finance', this)">
                        Finance
                    </button>
                    <button type="button" class="deal-nav-item" data-tab="start" onclick="switchDealNavTab('start', this)">
                        START
                    </button>
                    <button type="button" class="deal-nav-item" data-tab="files" onclick="switchDealNavTab('files', this)">
                        Files
                    </button>
                    <button type="button" class="deal-nav-item" data-tab="engagment" onclick="switchDealNavTab('engagment', this)">
                        Engagement
                    </button>
                    <button type="button" class="deal-nav-item" data-tab="history" onclick="switchDealNavTab('history', this)">
                        History &amp; Traceability
                    </button>
                </nav>
            </div>

            <button
                type="button"
                class="deal-tab-arrow deal-tab-arrow-right"
                id="dealTabArrowRight"
                aria-label="Scroll tabs right"
                onclick="scrollDealTabs('right')"
            >
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        </div>
    </div>


    {{-- =====================================================
         MAIN LAYOUT
    ====================================================== --}}

    <div class="detail-layout">

        <main class="detail-main">

            {{-- TAB PANEL: OVERVIEW --}}
            <div class="deal-tab-panel active" id="tab-panel-overview" style="display: block;">

            {{-- =================================================
                 DEAL INFORMATION
            ================================================== --}}

            <section class="detail-section" id="deal-information">

                <h2>
                    Deal Information
                </h2>

                <div class="detail-grid">

                    <div class="detail-item">
                        <div class="detail-label">Deal Code</div>
                        <div class="detail-value">
                            {{ $display($deal->deal_code) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Company Name</div>
                        <div class="detail-value">
                            {{ $display($deal->company) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Contact Person Name</div>
                        <div class="detail-value">
                            {{ $contactName }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Contact Person Position</div>
                        <div class="detail-value">
                            {{ $display($deal->position) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Email Address</div>
                        <div class="detail-value">
                            {{ $display($deal->email) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Contact Number</div>
                        <div class="detail-value">
                            {{ $display($deal->mobile_number) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Client Type</div>
                        <div class="detail-value">
                            {{ $display($deal->customer_type) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Industry</div>
                        <div class="detail-value">
                            {{ $display($deal->companyRecord?->industry ?? null) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Qualification Result</div>
                        <div class="detail-value">
                            {{ $display($deal->qualification_result ?? null) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Qualification Notes</div>
                        <div class="detail-value">
                            {{ $display($deal->qualification_notes ?? null) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Deal Stage</div>
                        <div class="detail-value">
                            {{ $currentStage }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Expected Close Date</div>
                        <div class="detail-value">
                            {{ $date($deal->expected_close) }}
                        </div>
                    </div>

                </div>

            </section>


            {{-- =================================================
                 SERVICE AND ENGAGEMENT DETAILS
            ================================================== --}}

            <section class="detail-section" id="service-details">

                <h2>
                    Service and Engagement Details
                </h2>

                <div class="detail-grid">

                    <div class="detail-item">
                        <div class="detail-label">Service Type</div>
                        <div class="detail-value {{ empty($deal->service_type) ? 'muted-value' : '' }}">
                            {{ filled($deal->service_type) ? $deal->service_type : 'Not specified' }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Product Type</div>
                        <div class="detail-value {{ empty($deal->product_type) ? 'muted-value' : '' }}">
                            {{ filled($deal->product_type) ? $deal->product_type : 'Not specified' }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Engagement Type</div>
                        <div class="detail-value {{ empty($deal->engagement_type) ? 'muted-value' : '' }}">
                            {{ filled($deal->engagement_type) ? $deal->engagement_type : 'Not specified' }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Deal Code</div>
                        <div class="detail-value">
                            {{ filled($deal->deal_code) ? $deal->deal_code : ('CONDEAL-' . date('Y') . '-' . str_pad($deal->id, 3, '0', STR_PAD_LEFT)) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Engagement Duration</div>
                        <div class="detail-value {{ empty($deal->estimated_duration_days) ? 'muted-value' : '' }}">
                            @if(filled($deal->estimated_duration_days))
                                {{ is_numeric($deal->estimated_duration_days) ? $deal->estimated_duration_days . ' days' : $deal->estimated_duration_days }}
                            @else
                                Not specified
                            @endif
                        </div>
                    </div>

                </div>

            </section>


            {{-- =================================================
                 FINANCIAL DETAILS
            ================================================== --}}

            <section class="detail-section" id="financial-details">

                <h2>
                    Financial Details
                </h2>

                <div class="detail-grid">

                    <div class="detail-item">
                        <div class="detail-label">
                            Deal Value
                        </div>

                        <div class="detail-value">
                            {{ $money($deal->amount ?: $deal->total_estimated_engagement_value) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">
                            Pricing Model
                        </div>

                        <div class="detail-value">
                            {{ $display($deal->pricing_model ?? $deal->engagement_type) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">
                            Payment Terms
                        </div>

                        <div class="detail-value">
                            {{ $display($deal->payment_terms) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">
                            Commission Applicable
                        </div>

                        <div class="detail-value">
                            {{ $display($deal->commission_applicable ?? null) }}
                        </div>
                    </div>

                </div>

            </section>


            {{-- =================================================
                 REFERRAL
            ================================================== --}}

            <section class="detail-section">

                <h2>
                    Referral and Lead Source
                </h2>

                <div class="detail-grid">

                    <div class="detail-item">
                        <div class="detail-label">
                            Lead Source
                        </div>

                        <div class="detail-value">
                            {{ $display($deal->client_search ?: ($deal->inquiry_source ?: 'Client Referral')) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">
                            Referred By
                        </div>

                        <div class="detail-value">
                            {{ $display($deal->referred_by ?: ($deal->created_by ?: ($deal->owner_name ?: '-'))) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">
                            Referral Type
                        </div>

                        <div class="detail-value">
                            {{ $display($deal->referral_type ?: ($deal->customer_type ? ($deal->customer_type . ' Direct') : 'Direct Referral')) }}
                        </div>
                    </div>

                </div>

            </section>


            {{-- =================================================
                 OWNERSHIP
            ================================================== --}}

            <section class="detail-section">

                <h2>
                    Deal Ownership and Team Assignment
                </h2>

                <div class="detail-grid">

                    <div class="detail-item">
                        <div class="detail-label">
                            Lead Consultant
                        </div>

                        <div class="detail-value">
                            {{ $display($deal->lead_consultant ?: $deal->assigned_consultant) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">
                            Lead Associate
                        </div>

                        <div class="detail-value">
                            {{ $display($deal->lead_associate ?: $deal->assigned_associate) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">
                            Finance
                        </div>

                        <div class="detail-value">
                            {{ $display($deal->finance) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">
                            Handling Team
                        </div>

                        <div class="detail-value">
                            {{ $display($deal->handling_team ?: ($deal->service_department ?: $deal->assigned_team)) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">
                            Assigned Team Members
                        </div>

                        <div class="detail-value">
                            {{ $display($deal->assigned_team_members ?: ($deal->assigned_person ?: (array_filter([$deal->assigned_consultant, $deal->assigned_associate]) ? implode(', ', array_filter([$deal->assigned_consultant, $deal->assigned_associate])) : null))) }}
                        </div>
                    </div>

                </div>

            </section>


            {{-- =================================================
                 DEAL FORM PREVIEW
            ================================================== --}}

            <section class="detail-section" id="deal-form-preview">

                <h2>
                    Deal Form Preview
                </h2>

                <div class="deal-form-preview">

                    <div class="deal-form-title">
                        Consulting &amp; Deal Form
                    </div>

                    <div class="deal-form-grid">

                        <div class="form-cell">
                            <div class="form-cell-label">Deal Name</div>
                            <div class="form-cell-value">
                                {{ $display($deal->deal_code) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Stage</div>
                            <div class="form-cell-value">
                                {{ $currentStage }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Engagement Type</div>
                            <div class="form-cell-value">
                                {{ $display($deal->engagement_type) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Total Value</div>
                            <div class="form-cell-value">
                                {{ $money($totalValue) }}
                            </div>
                        </div>


                        <div class="form-section-header">
                            Contact Information
                        </div>


                        <div class="form-cell">
                            <div class="form-cell-label">Salutation</div>
                            <div class="form-cell-value">
                                {{ $display($deal->salutation) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">First Name</div>
                            <div class="form-cell-value">
                                {{ $display($deal->first_name) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Middle Initial</div>
                            <div class="form-cell-value">
                                {{ $display($deal->middle_initial) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Last Name</div>
                            <div class="form-cell-value">
                                {{ $display($deal->last_name) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Name Extension</div>
                            <div class="form-cell-value">
                                {{ $display($deal->name_extension) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Sex</div>
                            <div class="form-cell-value">
                                {{ $display($deal->sex ?? null) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Date of Birth</div>
                            <div class="form-cell-value">
                                {{ $date($deal->date_of_birth ?? null) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Email Address</div>
                            <div class="form-cell-value">
                                {{ $display($deal->email) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Mobile Number</div>
                            <div class="form-cell-value">
                                {{ $display($deal->mobile_number) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Position / Designation</div>
                            <div class="form-cell-value">
                                {{ $display($deal->position) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Address</div>
                            <div class="form-cell-value">
                                {{ $display($deal->address ?? null) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Company</div>
                            <div class="form-cell-value">
                                {{ $display($deal->company) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Company Address</div>
                            <div class="form-cell-value">
                                {{ $display($deal->company_address ?? null) }}
                            </div>
                        </div>


                        <div class="form-section-header">
                            Service Identification
                        </div>


                        <div class="form-full-cell">
                            <div class="form-cell-label">
                                Service Area
                            </div>

                            <div class="form-cell-value">
                                {{ $listValue($serviceAreas) }}
                            </div>
                        </div>

                        <div class="form-full-cell">
                            <div class="form-cell-label">
                                Services
                            </div>

                            <div class="form-cell-value">
                                {{ $listValue($servicesProducts) }}
                            </div>
                        </div>


                        <div class="form-section-header">
                            Products
                        </div>


                        <div class="form-full-cell">
                            <div class="form-cell-label">
                                Products / Deliverables
                            </div>

                            <div class="form-cell-value">
                                {{ $listValue($servicesProducts) }}
                            </div>
                        </div>

                        <div class="form-full-cell">
                            <div class="form-cell-label">
                                Scope of Work
                            </div>

                            <div class="form-cell-value">
                                {{ $display($deal->scope_of_work) }}
                            </div>
                        </div>


                        <div class="form-section-header">
                            Client Requirements
                        </div>

                        <div class="form-full-cell" style="padding:0;">

                            <table class="form-table">

                                <thead>
                                    <tr>
                                        <th>Requirement</th>
                                        <th>Provided</th>
                                        <th>Pending</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @forelse($requirements as $requirement => $status)

                                        <tr>

                                            <td>
                                                {{ is_numeric($requirement)
                                                    ? $requirement + 1
                                                    : $requirement }}
                                            </td>

                                            <td style="text-align:center;">
                                                {{ $status === 'provided' ? 'Yes' : '-' }}
                                            </td>

                                            <td style="text-align:center;">
                                                {{ $status === 'pending' ? 'Yes' : '-' }}
                                            </td>

                                        </tr>

                                    @empty

                                        <tr>
                                            <td colspan="3" style="text-align:center;">
                                                No requirements recorded.
                                            </td>
                                        </tr>

                                    @endforelse

                                </tbody>

                            </table>

                        </div>


                        <div class="form-section-header">
                            Fees &amp; Payment
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Professional Fee
                            </div>
                            <div class="form-cell-value">
                                {{ $money($deal->est_professional_fee) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Government Fees
                            </div>
                            <div class="form-cell-value">
                                {{ $money($deal->est_government_fee) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Service Support Fee
                            </div>
                            <div class="form-cell-value">
                                {{ $money($deal->est_service_support_fee) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Discount
                            </div>
                            <div class="form-cell-value">
                                {{ $money($deal->discount) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Total Value
                            </div>
                            <div class="form-cell-value">
                                {{ $money($totalValue) }}
                            </div>
                        </div>

                        <div class="form-full-cell">
                            <div class="form-cell-label">
                                Payment Terms
                            </div>

                            <div class="form-cell-value">
                                {{ $display($deal->payment_terms) }}
                            </div>
                        </div>


                        <div class="form-section-header">
                            Timeline &amp; Assessment
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Planned Start Date
                            </div>
                            <div class="form-cell-value">
                                {{ $date($plannedStart) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Estimated Duration
                            </div>
                            <div class="form-cell-value">
                                {{ filled($deal->estimated_duration_days)
                                    ? $deal->estimated_duration_days
                                    : '-' }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Estimated Completion Date
                            </div>
                            <div class="form-cell-value">
                                {{ $date($estimatedCompletion) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Confirmed Delivery Date
                            </div>
                            <div class="form-cell-value">
                                {{ $date($deal->confirmed_delivery_date) }}
                            </div>
                        </div>

                        <div class="form-half-cell">
                            <div class="form-cell-label">
                                Client Preferred Completion Date
                            </div>

                            <div class="form-cell-value">
                                {{ $date($deal->client_preferred_completion_date) }}
                            </div>
                        </div>

                        <div class="form-half-cell">
                            <div class="form-cell-label">
                                Confirmed Delivery Date
                            </div>

                            <div class="form-cell-value">
                                {{ $date($deal->confirmed_delivery_date) }}
                            </div>
                        </div>

                        <div class="form-full-cell">
                            <div class="form-cell-label">
                                Timeline Notes
                            </div>

                            <div class="form-cell-value">
                                {{ $display($deal->timeline_notes) }}
                            </div>
                        </div>

                        <div class="form-half-cell">
                            <div class="form-cell-label">
                                Service Complexity
                            </div>

                            <div class="form-cell-value">
                                {{ $display($deal->service_complexity) }}
                            </div>
                        </div>

                        <div class="form-half-cell">
                            <div class="form-cell-label">
                                Professional Support Required
                            </div>

                            <div class="form-cell-value">
                                {{ $listValue($support) }}
                            </div>
                        </div>

                        <div class="form-full-cell">
                            <div class="form-cell-label">
                                Notes / Explanation
                            </div>

                            <div class="form-cell-value">
                                {{ $display($deal->complexity_notes) }}
                            </div>
                        </div>


                        <div class="form-section-header">
                            Proposal &amp; Internal Assignment
                        </div>

                        <div class="form-half-cell">
                            <div class="form-cell-label">
                                Proposal Decision
                            </div>

                            <div class="form-cell-value">
                                {{ $display($deal->proposal_decision) }}
                            </div>
                        </div>

                        <div class="form-half-cell">
                            <div class="form-cell-label">
                                Decline Reason
                            </div>

                            <div class="form-cell-value">
                                {{ $display($deal->decline_reason ?? null) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Assigned Consultant
                            </div>

                            <div class="form-cell-value">
                                {{ $display($deal->assigned_consultant) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Assigned Associate
                            </div>

                            <div class="form-cell-value">
                                {{ $display($deal->assigned_associate) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Service Department / Unit
                            </div>

                            <div class="form-cell-value">
                                {{ $display($deal->service_department) }}
                            </div>
                        </div>


                        <div class="form-section-header">
                            Notes &amp; Approval
                        </div>

                        <div class="form-half-cell">
                            <div class="form-cell-label">
                                Consultant Notes
                            </div>

                            <div class="form-cell-value">
                                {{ $display($deal->consultant_notes) }}
                            </div>
                        </div>

                        <div class="form-half-cell">
                            <div class="form-cell-label">
                                Associate Notes
                            </div>

                            <div class="form-cell-value">
                                {{ $display($deal->associate_notes) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Prepared By
                            </div>

                            <div class="form-cell-value">
                                {{ $display($deal->owner_name) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Reviewed By
                            </div>

                            <div class="form-cell-value">
                                {{ $display($deal->reviewed_by) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Approval Date
                            </div>

                            <div class="form-cell-value">
                                {{ $date($deal->approval_date) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Client Fullname &amp; Signature
                            </div>

                            <div class="form-cell-value">
                                {{ $display($deal->client_fullname_signature) }}
                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                President
                            </div>

                            <div class="form-cell-value">
                                {{ $display($deal->president) }}
                            </div>
                        </div>

                    </div>

                </div>

            </section>


            {{-- =================================================
                 START FORM
            ================================================== --}}

            <section class="detail-section" id="start-form">

                <div class="start-header">

                    <div>
                        <h2>
                            START Form
                        </h2>

                        <p class="start-description">
                            Manage the project START intake directly from this deal.
                        </p>
                    </div>

                    <div class="start-actions">

                        <a href="#start-form"
                           class="start-action">
                            <span>▣</span>
                            Download START PDF
                        </a>

                        <a href="#start-form"
                           class="start-action">
                            <span>✎</span>
                            Edit START
                        </a>

                    </div>

                </div>


                <div class="start-paper">

                    <div class="start-status-row">

                        <div>
                            START Status:
                            <span class="start-status">
                                Draft
                            </span>
                        </div>

                        <div class="approval-required">
                            ADMIN APPROVAL REQUIRED
                        </div>

                    </div>


                    <div class="start-brand-title">

                        <div class="start-company">
                            John Kelly
                            <br>
                            Company
                        </div>

                        <div class="start-title">
                            SERVICE TASK ACTIVATION AND
                            <br>
                            ROUTING TRACKER (START)
                        </div>

                        <div class="start-subtitle">
                            CASA-F-01-v1.0.03-16.26
                        </div>

                    </div>


                    <div class="start-info-grid">

                        <div class="start-info-cell">
                            <div class="start-label">
                                Client Name
                            </div>

                            <div class="start-value">
                                {{ $contactName }}
                            </div>
                        </div>

                        <div class="start-info-cell">
                            <div class="start-label">
                                Product
                            </div>

                            <div class="start-value">
                                {{ $display($deal->product_type ?? null) }}
                            </div>
                        </div>

                        <div class="start-info-cell">
                            <div class="start-label">
                                Business Name
                            </div>

                            <div class="start-value">
                                {{ $display($deal->company) }}
                            </div>
                        </div>

                        <div class="start-info-cell">
                            <div class="start-label">
                                Services
                            </div>

                            <div class="start-value">
                                {{ $listValue($servicesProducts) }}
                            </div>
                        </div>

                        <div class="start-info-cell">
                            <div class="start-label">
                                CONDEAL Ref No.
                            </div>

                            <div class="start-value">
                                {{ $display($deal->deal_code) }}
                            </div>
                        </div>

                        <div class="start-info-cell">
                            <div class="start-label">
                                Date
                            </div>

                            <div class="start-value">
                                {{ now()->format('m/d/Y') }}
                            </div>
                        </div>

                        <div class="start-info-cell">
                            <div class="start-label">
                                Service Area
                            </div>

                            <div class="start-value">
                                {{ $listValue($serviceAreas) }}
                            </div>
                        </div>

                        <div class="start-info-cell">
                            <div class="start-label">
                                Engagement Type
                            </div>

                            <div class="start-value">
                                {{ $display($deal->engagement_type) }}
                            </div>
                        </div>

                        <div class="start-info-cell">
                            <div class="start-label">
                                Date Started
                            </div>

                            <div class="start-value">
                                {{ $date($plannedStart) }}
                            </div>
                        </div>

                        <div class="start-info-cell">
                            <div class="start-label">
                                Date Completed
                            </div>

                            <div class="start-value">
                                {{ $date($estimatedCompletion) }}
                            </div>
                        </div>

                    </div>


                    <div class="start-section-title">
                        Client Due Diligence (KYC) Documents
                    </div>

                    <div style="
                        padding:5px;
                        text-align:center;
                        background:#eef4ff;
                        border-left:1px solid #1e293b;
                        border-right:1px solid #1e293b;
                        font-size:8px;
                        font-weight:700;
                    ">
                        JURIDICAL ENTITY
                        <i>(Corporation / OPC / Partnership / Cooperative)</i>
                    </div>

                    <div class="start-empty">
                        No KYC requirements available for this business organization yet.
                    </div>


                    <div class="start-section-title">
                        Engagement-Specific Requirements
                    </div>

                    <table class="start-table">

                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Requirement / Document</th>
                                <th>Notes</th>
                                <th>Purpose</th>
                                <th>Provided By</th>
                                <th>Submitted To</th>
                                <th>Assigned To</th>
                                <th>Timeline</th>
                            </tr>
                        </thead>

                        <tbody>

                            <tr>
                                <td>1</td>

                                <td>
                                    Initial intake and supporting documents
                                </td>

                                <td>-</td>

                                <td>
                                    START intake
                                </td>

                                <td>
                                    Client
                                </td>

                                <td>
                                    Sales &amp; Marketing
                                </td>

                                <td>
                                    {{ $display($deal->assigned_associate) }}
                                </td>

                                <td>
                                    To be scheduled
                                </td>
                            </tr>

                        </tbody>

                    </table>


                    <div class="clearance-title">
                        CLEARANCE
                    </div>

                    <div class="clearance-grid">

                        <div class="clearance-cell">
                            ASSIGNED TO REGULAR/PROJECT TEAM LEAD

                            <div style="margin-top:20px; font-weight: 600; text-transform: uppercase;">
                                {{ $clearanceLeadConsultant }}
                            </div>

                            <div class="signature-line">
                                Signature over Printed Name
                            </div>
                        </div>

                        <div class="clearance-cell">
                            LEAD CONSULTANT CONFIRMED

                            <div style="margin-top:20px; font-weight: 600; text-transform: uppercase;">
                                {{ $clearanceLeadConsultant }}
                            </div>

                            <div class="signature-line">
                                Signature over Printed Name
                            </div>
                        </div>

                        <div class="clearance-cell">
                            LEAD ASSOCIATE ASSIGNED

                            <div style="margin-top:20px; font-weight: 600; text-transform: uppercase;">
                                {{ $clearanceLeadAssociate }}
                            </div>

                            <div class="signature-line">
                                Signature over Printed Name
                            </div>
                        </div>

                        <div class="clearance-cell">
                            SALES &amp; MARKETING

                            <div style="margin-top:20px; font-weight: 600; text-transform: uppercase;">
                                {{ $clearanceSalesMarketing }}
                            </div>

                            <div class="signature-line">
                                Signature over Printed Name
                            </div>
                        </div>

                    </div>


                    <div class="record-row">

                        <div class="record-cell">

                            <div style="text-align:center;">
                                <i>
                                    Record Custodian (Name and Signature)
                                </i>
                            </div>

                            <div class="signature-line">
                                Record Custodian
                            </div>

                        </div>

                        <div class="record-cell">

                            <div>
                                Date Recorded:
                                {{ now()->format('m/d/Y') }}
                            </div>

                            <div style="margin-top:20px;">
                                Date Signed:
                                __________________
                            </div>

                        </div>

                    </div>


                    <div class="rejection-box">

                        <div class="rejection-label">
                            REJECTION / HOLD REASON
                        </div>

                        <div class="rejection-input"></div>

                    </div>

                </div>

            </section>





            </div>{{-- END TAB PANEL: OVERVIEW --}}


            {{-- =====================================================
                 TAB PANEL: INQUIRY (INQUIRY RECORDS)
            ====================================================== --}}
            <div class="deal-tab-panel" id="tab-panel-inquiry" style="display: none;">

                <div class="inquiry-records-wrapper">
                    {{-- Header --}}
                    <div class="inquiry-main-header">
                        <h2 class="inquiry-main-title">Inquiry Records</h2>
                        <p class="inquiry-main-subtitle">View and manage all inquiries from this client.</p>
                    </div>

                    {{-- Search and Filter Controls --}}
                    <div class="inquiry-controls-bar">
                        <div class="inquiry-search-wrapper">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text" id="inquirySearchInput" class="inquiry-search-input" placeholder="Search inquiries..." oninput="filterInquiries()">
                        </div>
                        <select id="inquiryTypeFilter" class="inquiry-type-filter-select" onchange="filterInquiries()">
                            <option value="ALL">Filter: All Types</option>
                            <option value="Product">Product</option>
                            <option value="Service">Service</option>
                        </select>
                    </div>

                    {{-- Inquiry Cards List Container --}}
                    <div id="inquiryCardsList" class="inquiry-cards-container">
                        {{-- Rendered dynamically via JavaScript --}}
                    </div>

                    {{-- Empty State --}}
                    <div id="inquiryEmptyState" class="inquiry-empty-box" style="display: none;">
                        <h3 class="inquiry-empty-title">No inquiry records yet.</h3>
                        <p class="inquiry-empty-desc">No inquiry has been recorded for this client.</p>
                    </div>
                </div>

            </div>{{-- END TAB PANEL: INQUIRY --}}


            {{-- =====================================================
                 TAB PANEL: CONSULTATION (CONSULTATION RECORDS & FILES)
            ====================================================== --}}
            <div class="deal-tab-panel" id="tab-panel-consultation" style="display: none;">

                <div class="consultation-main-wrapper">

                    {{-- 1. CONSULTATION RECORDS SECTION --}}
                    <div class="consultation-records-wrapper">

                        <div class="inquiry-main-header">
                            <div>
                                <h2 class="inquiry-main-title">Consultation Records</h2>
                                <p class="inquiry-main-subtitle">View and manage all consultation records and meetings with this client.</p>
                            </div>
                        </div>

                        {{-- Consultation Cards Container --}}
                        <div id="consultationCardsList" class="consultation-cards-container">
                            {{-- Rendered dynamically via JavaScript --}}
                        </div>

                        {{-- Empty State for Consultations --}}
                        <div id="consultationEmptyState" class="inquiry-empty-box" style="display: none;">
                            <h3 class="inquiry-empty-title">No consultations yet.</h3>
                            <p class="inquiry-empty-desc">No consultation has been recorded for this client.</p>
                        </div>

                    </div>

                    {{-- 2. ATTACHMENTS SECTION --}}
                    <div class="consultation-attachments-wrapper">

                        <div class="inquiry-main-header">
                            <div>
                                <h2 class="inquiry-main-title">Attachments</h2>
                                <p class="inquiry-main-subtitle">Supporting documents and files uploaded for this consultation.</p>
                            </div>
                        </div>

                        {{-- Attachments List Container --}}
                        <div id="consultationAttachmentsList" class="attachment-items-list">
                            {{-- Rendered dynamically via JavaScript --}}
                        </div>

                        {{-- Empty State for Attachments --}}
                        <div id="attachmentsEmptyState" class="inquiry-empty-box" style="display: none;">
                            <h3 class="inquiry-empty-title">No files attached yet.</h3>
                            <p class="inquiry-empty-desc">No files have been attached for this client.</p>
                        </div>

                    </div>

                </div>

            </div>{{-- END TAB PANEL: CONSULTATION --}}


            {{-- =====================================================
                 TAB PANEL: SERVICES & PRICING (DEAL LINE ITEMS)
            ====================================================== --}}
            <div class="deal-tab-panel" id="tab-panel-services-pricing" style="display: none;">

                <div class="deal-line-items-card">

                    {{-- Header Row: Deal Line Items | Single Commercial Source of Truth --}}
                    <div class="inquiry-main-header">
                        <div>
                            <h2 class="inquiry-main-title">Deal Line Items</h2>
                            <p class="inquiry-main-subtitle">Single Commercial Source of Truth</p>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="dli-table-responsive">
                        <table class="dli-table" id="dealLineItemsTable">
                            <thead>
                                <tr>
                                    <th class="dli-col-type">TYPE</th>
                                    <th class="dli-col-name">ITEM / NAME</th>
                                    <th class="dli-col-desc">DESCRIPTION / SCOPE</th>
                                    <th class="dli-col-qty">QTY</th>
                                    <th class="dli-col-price">UNIT PRICE</th>
                                    <th class="dli-col-discount">DISCOUNT</th>
                                    <th class="dli-col-tax">TAX</th>
                                    <th class="dli-col-total">TOTAL</th>
                                    <th class="dli-col-billing">BILLING</th>
                                    <th class="dli-col-route">ROUTE</th>
                                    <th class="dli-col-actions">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="dealLineItemsTableBody">
                                {{-- Rendered dynamically via JavaScript --}}
                            </tbody>
                        </table>
                    </div>

                    {{-- Empty State (if no line items) --}}
                    <div id="dliEmptyState" class="inquiry-empty-box" style="display: none; margin-bottom: 24px;">
                        <h3 class="inquiry-empty-title">No line items added yet.</h3>
                        <p class="inquiry-empty-desc">Add services or products from Quick Actions to define the commercial pricing structure for this deal.</p>
                    </div>

                    {{-- PRICING SUMMARY 4-GRID --}}
                    <div class="dli-summary-grid">
                        <div class="dli-summary-block">
                            <div class="dli-summary-label">SERVICES FEE</div>
                            <div class="dli-summary-value" id="summaryServicesFee">₱0.00</div>
                        </div>

                        <div class="dli-summary-block">
                            <div class="dli-summary-label">PRODUCTS FEE</div>
                            <div class="dli-summary-value" id="summaryProductsFee">₱0.00</div>
                        </div>

                        <div class="dli-summary-block discount-block">
                            <div class="dli-summary-label">TOTAL DISCOUNT</div>
                            <div class="dli-summary-value" id="summaryTotalDiscount">- ₱0.00</div>
                        </div>

                        <div class="dli-summary-block total-block">
                            <div class="dli-summary-label">COMMERCIAL TOTAL VALUE</div>
                            <div class="dli-summary-value" id="summaryCommercialTotal">₱0.00</div>
                        </div>
                    </div>

                </div>

            </div>{{-- END TAB PANEL: SERVICES & PRICING --}}


            {{-- =====================================================
                 TAB PANEL: PROPOSAL (CREATE PROPOSAL FORM & PREVIEW)
            ====================================================== --}}
            <div class="deal-tab-panel" id="tab-panel-proposal" style="display: none;">

                @php
                    $proposalStatusText = 'Approved';
                    if ($deal->pipeline_stage === 'Inquiry' || $deal->pipeline_stage === 'Qualification' || $deal->pipeline_stage === 'Consultation') {
                        $proposalStatusText = 'Draft';
                    } elseif ($deal->pipeline_stage === 'Proposal') {
                        $proposalStatusText = 'Approved';
                    } elseif ($deal->pipeline_stage === 'Negotiation') {
                        $proposalStatusText = 'Under Client Review';
                    } elseif ($deal->pipeline_stage === 'Payment' || $deal->pipeline_stage === 'Activation' || $deal->pipeline_stage === 'Closed Won') {
                        $proposalStatusText = 'Accepted / Signed';
                    } elseif ($deal->pipeline_stage === 'Closed Lost') {
                        $proposalStatusText = 'Declined';
                    }

                    $proposalSentTimestamp = null;
                    $proposalSentVersion = 'Not Sent Yet';
                    $hasProposalBeenSent = filled($deal->proposal_date) || in_array($deal->pipeline_stage, ['Negotiation', 'Payment', 'Activation', 'Closed Won']) || in_array($deal->proposal_decision, ['Accepted', 'Approved', 'Declined']);
                    if ($hasProposalBeenSent) {
                        $sentDateObj = $deal->proposal_date ?: ($deal->updated_at ?: $deal->created_at);
                        $proposalSentTimestamp = $sentDateObj ? $sentDateObj->format('M d, Y h:i A') : date('M d, Y h:i A');
                        $proposalSentVersion = 'V1 (Current)';
                    }

                    $serverSentSnapshotData = $proposalSentTimestamp ? [
                        'version' => 'V1',
                        'versionNumber' => 1,
                        'sentAt' => $proposalSentTimestamp,
                        'recipientEmail' => $proposal->recipient_email ?: ($deal->email ?: ''),
                        'isApproved' => in_array($deal->proposal_decision, ['Accepted', 'Approved']) || in_array($deal->pipeline_stage, ['Negotiation', 'Payment', 'Activation', 'Closed Won']),
                        'approvedAt' => $deal->approval_date ? $deal->approval_date->format('M d, Y') : $proposalSentTimestamp,
                        'approvedBy' => $deal->approval_name ?: ($deal->primary_contact_name ?: null)
                    ] : null;
                @endphp

                {{-- TOP BANNER: PROPOSAL WORKSPACE INTEGRATION --}}
                <div class="proposal-workspace-integration-card">
                    <div class="pwi-header">
                        <div class="pwi-title-group">
                            <h2>Proposal Workspace Integration</h2>
                            <p>Versioned Proposal Output &amp; Commercial Snapshot</p>
                        </div>
                    </div>

                    <div class="pwi-meta-grid">
                        <div class="pwi-meta-item">
                            <span class="pwi-meta-label">Active Version</span>
                            <div class="pwi-meta-value">
                                <span class="pwi-badge-version" id="pwiActiveVersionBadge">V1</span>
                            </div>
                        </div>

                        <div class="pwi-meta-item">
                            <span class="pwi-meta-label">Proposal Status</span>
                            <div class="pwi-meta-value">
                                <span class="pwi-badge-status" id="pwiStatusBadge">{{ $proposalStatusText }}</span>
                            </div>
                        </div>

                        <div class="pwi-meta-item">
                            <span class="pwi-meta-label">Sent to Client (Snapshot)</span>
                            <div class="pwi-meta-value">
                                <span id="pwiSentVersionText">{{ $proposalSentVersion }}</span>
                            </div>
                        </div>

                        <div class="pwi-meta-item">
                            <span class="pwi-meta-label">Sent Timestamp</span>
                            <div class="pwi-meta-value" style="font-size: 13px; color: #475569;">
                                <span id="pwiSentAtText">{{ $proposalSentTimestamp ?: '—' }}</span>
                            </div>
                        </div>

                        <div class="pwi-meta-item">
                            <span class="pwi-meta-label">Recipient Email</span>
                            <div class="pwi-meta-value">
                                {{ $proposal->recipient_email ?: ($deal->email ?: '-') }}
                            </div>
                        </div>

                        <div class="pwi-meta-item">
                            <span class="pwi-meta-label">Prepared By</span>
                            <div class="pwi-meta-value">
                                {{ $deal->owner_name ?: '-' }}
                            </div>
                        </div>

                        <div class="pwi-meta-item">
                            <span class="pwi-meta-label">Approved By</span>
                            <div class="pwi-meta-value">
                                {{ $deal->approval_name ?: '-' }}
                            </div>
                        </div>

                        <div class="pwi-meta-item">
                            <span class="pwi-meta-label">Snapshot Total</span>
                            <div class="pwi-meta-value price" id="pwiSnapshotTotal">
                                {{ $money($proposalTotal > 0 ? $proposalTotal : ($deal->total_estimated_engagement_value ?: $deal->amount ?: 0)) }}
                            </div>
                        </div>
                    </div>

                    <div class="pwi-ledger-title" id="pwiLedgerTitleVersion">
                        PROPOSAL ITEM DECISION LEDGER (V1)
                    </div>

                    <div class="pwi-ledger-table-wrap">
                        <table class="pwi-ledger-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="th-amount">Amount</th>
                                    <th class="th-center">Payment State</th>
                                    <th class="th-center">Business State</th>
                                    <th class="th-center">Activation</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($proposalItems as $index => $item)
                                    @php
                                        $itemName = is_array($item) ? ($item['name'] ?? 'Commercial Scope Item') : $item;
                                        $itemPrice = is_array($item) && isset($item['price']) && (float)$item['price'] > 0
                                            ? (float)$item['price']
                                            : (is_array($item) && isset($item['amount']) && (float)$item['amount'] > 0
                                                ? (float)$item['amount']
                                                : ($proposalTotal > 0 && count($proposalItems)
                                                    ? $proposalTotal / count($proposalItems)
                                                    : ((float)($deal->amount ?: $deal->total_estimated_engagement_value ?: 0) / (count($proposalItems) ?: 1))));
                                        $itemCode = $deal->deal_code ?: ('START-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT));
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="pwi-item-name">{{ $itemName }}</div>
                                            <a href="#" onclick="openProposalWorkspace(); return false;" class="pwi-item-code">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                                                <span>{{ $itemCode }}</span>
                                            </a>
                                        </td>
                                        <td class="td-amount">
                                            {{ $money($itemPrice) }}
                                        </td>
                                        <td class="td-center">
                                            <span class="pwi-pill-payment">
                                                {{ $deal->pipeline_stage === 'Payment' || $deal->pipeline_stage === 'Closed Won' ? 'Billed' : 'Not Billed' }}
                                            </span>
                                        </td>
                                        <td class="td-center">
                                            <span class="pwi-pill-business">
                                                {{ $deal->pipeline_stage === 'Closed Won' || $deal->pipeline_stage === 'Activation' ? 'Completed / Active' : 'Completed / Ongoing' }}
                                            </span>
                                        </td>
                                        <td class="td-center">
                                            <span class="pwi-pill-activation">
                                                {{ $deal->pipeline_stage === 'Activation' || $deal->pipeline_stage === 'Closed Won' ? 'Active' : 'Ongoing' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td>
                                            <div class="pwi-item-name">{{ $defaultServiceName }}</div>
                                            <a href="#" onclick="openProposalWorkspace(); return false;" class="pwi-item-code">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                                                <span>{{ $deal->deal_code ?: 'START-004' }}</span>
                                            </a>
                                        </td>
                                        <td class="td-amount">
                                            {{ $money($proposalTotal > 0 ? $proposalTotal : ($deal->total_estimated_engagement_value ?: $deal->amount ?: 0)) }}
                                        </td>
                                        <td class="td-center">
                                            <span class="pwi-pill-payment">Not Billed</span>
                                        </td>
                                        <td class="td-center">
                                            <span class="pwi-pill-business">Completed / Ongoing</span>
                                        </td>
                                        <td class="td-center">
                                            <span class="pwi-pill-activation">Ongoing</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- SECOND BANNER: UNIVERSAL CLIENT ACTIONS (REFINED UI/UX) --}}
                <div class="universal-client-actions-card">
                    <div class="uca-header">
                        <div class="uca-title-group">
                            <div class="uca-title-row">
                                <span class="uca-header-icon-box">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <polyline points="16 11 18 13 22 9"></polyline>
                                    </svg>
                                </span>
                                <h2>Universal Client Actions</h2>
                            </div>
                            <p>Send and manage requests that require client action.</p>
                        </div>
                    </div>

                    <div id="ucaContentContainer">
                        {{-- STATE 1: EMPTY STATE --}}
                        <div class="uca-empty-panel" id="ucaEmptyState">
                            <div class="uca-empty-icon-circle">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                            </div>
                            <h3 class="uca-empty-heading">No client actions yet</h3>
                            <p class="uca-empty-subtext">Client action requests will appear here.</p>
                        </div>

                        {{-- STATE 2: ACTION CARDS LIST --}}
                        <div class="uca-actions-list" id="ucaActionsList" style="display: none;"></div>
                    </div>
                </div>

                {{-- PROPOSAL WORKSPACE MODAL (INLINE MODAL) --}}
                <div id="proposalWorkspaceModal" class="pwm-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeProposalWorkspaceModal()">
                    <div class="pwm-modal-dialog">
                        <div class="pwm-modal-header">
                            <div class="pwm-header-top-row">
                                <div class="pwm-header-left">
                                    <span class="pwm-badge">PROPOSAL WORKSPACE</span>
                                    <h2 class="pwm-title" id="pwmModalTitle">{{ $deal->deal_code ?: 'CONDEAL-2026-068' }} — {{ $contactName ?: ($deal->primary_contact_name ?: ($deal->deal_title ?: 'Proposal')) }}</h2>
                                </div>
                                <button type="button" class="pwm-btn-close" onclick="closeProposalWorkspaceModal()" aria-label="Close">&times;</button>
                            </div>

                            <div class="pwm-version-bar">
                                <div class="pwm-version-nav-section">
                                    <span class="pwm-version-label">PROPOSAL VERSIONS</span>
                                    <div class="pwm-version-pills-scroll">
                                        <div class="pwm-version-pills" id="modalProposalVersionPillsWrap">
                                            {{-- Dynamic version pills from JS/DB --}}
                                        </div>
                                    </div>
                                </div>
                                <div class="pwm-version-actions">
                                    <button type="button" class="pvt-btn-new-revision" onclick="openAddRevisionModal()">
                                        + New Revision (V2)
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="pwm-modal-body">
                            <div class="proposal-workspace-layout modal-version">

                    {{-- LEFT COLUMN: CREATE PROPOSAL FORM --}}
                    <section class="proposal-panel proposal-form-panel">

                        <div class="proposal-panel-header">
                            <h1>Create Proposal Form</h1>
                            <p>The form is auto-filled from the deal. Edit any field and the right-side preview regenerates for the final PDF output.</p>
                        </div>

                        <div class="proposal-form-scroll">

                            <div id="modalPvtHistoricalNotice" class="pvt-historical-alert" style="display: none; margin: 0 0 12px 0;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                <span>Historical snapshot — create a new revision to make changes.</span>
                            </div>

                            <div class="proposal-info">
                                You can now edit key proposal content directly in the preview on the right. Click the blue-highlighted text blocks to edit them, and the saved proposal fields will stay in sync.
                            </div>

                            <form method="POST" action="{{ route('deals.proposal.store', $deal) }}" id="deal-proposal-form">
                                @csrf

                                <div class="proposal-card">
                                    <h2>Client Information</h2>
                                    <div class="proposal-grid">
                                        <div class="proposal-field">
                                            <label>Deal Code</label>
                                            <input value="{{ $deal->deal_code ?: '-' }}" readonly>
                                        </div>

                                        <div class="proposal-field">
                                            <label>Client Type</label>
                                            <input value="{{ $deal->customer_type ?: '-' }}" readonly>
                                        </div>

                                        <div class="proposal-field">
                                            <label>Client Name</label>
                                            <input value="{{ $contactName ?: '-' }}" readonly>
                                        </div>

                                        <div class="proposal-field">
                                            <label>Company</label>
                                            <input value="{{ $deal->company ?: '-' }}" readonly>
                                        </div>

                                        <div class="proposal-field">
                                            <label>Email Address</label>
                                            <input value="{{ $deal->email ?: '-' }}" readonly>
                                        </div>

                                        <div class="proposal-field">
                                            <label>Mobile Number</label>
                                            <input value="{{ $deal->mobile_number ?: '-' }}" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="proposal-card">
                                    <h2>Proposal Details</h2>
                                    <div class="proposal-grid">
                                        <div class="proposal-field full">
                                            <label for="proposal_subject">Proposal Title</label>
                                            <input id="proposal_subject" name="subject" value="{{ old('subject', $proposal->subject ?: $deal->deal_title) }}">
                                        </div>

                                        <div class="proposal-field">
                                            <label>Pipeline Stage</label>
                                            <input value="{{ $deal->pipeline_stage ?: '-' }}" readonly>
                                        </div>

                                        <div class="proposal-field">
                                            <label>Engagement Type</label>
                                            <input value="{{ $deal->engagement_type ?: '-' }}" readonly>
                                        </div>

                                        <div class="proposal-field full">
                                            <label for="proposal_introduction">Introduction</label>
                                            <textarea id="proposal_introduction" name="introduction">{{ old('introduction', $proposal->introduction ?: 'We are pleased to present this proposal for the engagement described below.') }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="proposal-card">
                                    <h2>Products &amp; Services</h2>
                                    <table class="proposal-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th style="text-align:right">Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($proposalItems as $index => $item)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ is_array($item) ? ($item['name'] ?? 'Service') : $item }}</td>
                                                    <td style="text-align:right">-</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" style="text-align:center; color: #94a3b8;">
                                                        No products or services selected.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="proposal-card">
                                    <h2>COMPUTED TOTALS</h2>
                                    <div class="total-row">
                                        <span>Total Services</span>
                                        <strong>{{ $money($proposalServicesTotal) }}</strong>
                                    </div>
                                    <div class="total-row">
                                        <span>Total Products</span>
                                        <strong>{{ $money($proposalProductsTotal) }}</strong>
                                    </div>
                                    <div class="total-row">
                                        <span>Discount</span>
                                        <strong id="displayProposalDiscount">{{ $money($proposalDiscount) }}</strong>
                                    </div>
                                    <div class="total-row">
                                        <span>Tax</span>
                                        <strong id="displayProposalTax">{{ $money($proposalTax) }}</strong>
                                    </div>
                                    <div class="total-row">
                                        <span>Subtotal</span>
                                        <strong id="displayProposalSubtotal">{{ $money($proposalSubtotal) }}</strong>
                                    </div>
                                    <div class="total-row total">
                                        <span>Total Engagement Value</span>
                                        <strong id="displayProposalTotal">{{ $money($proposalTotal) }}</strong>
                                    </div>
                                </div>

                                <div class="proposal-card">
                                    <h2>Proposal Content</h2>
                                    <div class="proposal-grid">
                                        <div class="proposal-field full">
                                            <label for="proposal_scope">Scope of Work</label>
                                            <textarea id="proposal_scope" name="scope">{{ old('scope', $proposal->scope ?: $deal->scope_of_work) }}</textarea>
                                        </div>

                                        <div class="proposal-field full">
                                            <label for="proposal_terms">Payment Terms</label>
                                            <textarea id="proposal_terms" name="terms">{{ old('terms', $proposal->terms ?: $deal->payment_terms) }}</textarea>
                                        </div>

                                        <div class="proposal-field">
                                            <label for="proposal_discount">Discount (₱)</label>
                                            <input id="proposal_discount" name="discount" type="number" min="0" step="0.01" value="{{ old('discount', $proposal->discount ?? $deal->discount) }}">
                                        </div>

                                        <div class="proposal-field">
                                            <label for="proposal_tax">Tax (₱)</label>
                                            <input id="proposal_tax" name="tax" type="number" min="0" step="0.01" value="{{ old('tax', $proposal->tax) }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button class="proposal-button primary" type="submit">
                                        Save Proposal
                                    </button>
                                    <button class="proposal-button" type="button" onclick="refreshProposalPreview()">
                                        Refresh Preview
                                    </button>
                                </div>
                            </form>

                            <div class="approval-notice">
                                A global proposal template update is waiting for admin approval. New deals continue using the last approved template until the pending template is approved.
                            </div>

                        </div>
                    </section>

                    {{-- RIGHT COLUMN: PROPOSAL PREVIEW --}}
                    <section class="proposal-panel proposal-preview-panel">

                        <div class="proposal-panel-header">
                            <h1>Proposal Preview</h1>
                            <p>This preview is aligned with the downloadable PDF output.</p>
                        </div>

                        <div class="preview-toolbar">
                            <div class="preview-toolbar-group">
                                <button type="button" class="preview-tool-btn" onmousedown="event.preventDefault()" onclick="formatProposalPreview('bold')" title="Bold (Ctrl+B)">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path></svg>
                                    <span><strong>Bold</strong></span>
                                </button>
                                <button type="button" class="preview-tool-btn" onmousedown="event.preventDefault()" onclick="formatProposalPreview('italic')" title="Italic (Ctrl+I)">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="4" x2="10" y2="4"></line><line x1="14" y1="20" x2="5" y2="20"></line><line x1="15" y1="4" x2="9" y2="20"></line></svg>
                                    <span><em>Italic</em></span>
                                </button>
                                <button type="button" class="preview-tool-btn" onmousedown="event.preventDefault()" onclick="formatProposalPreview('underline')" title="Underline (Ctrl+U)">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3v7a6 6 0 0 0 12 0V3"></path><line x1="4" y1="21" x2="20" y2="21"></line></svg>
                                    <span><u>Underline</u></span>
                                </button>
                                <span class="preview-tool-divider"></span>
                                <button type="button" class="preview-tool-btn" onmousedown="event.preventDefault()" onclick="formatProposalPreview('insertUnorderedList')" title="Bullet List">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                                    <span>Bullets</span>
                                </button>
                                <button type="button" class="preview-tool-btn" onmousedown="event.preventDefault()" onclick="formatProposalPreview('insertOrderedList')" title="Numbered List">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="10" y1="6" x2="21" y2="6"></line><line x1="10" y1="12" x2="21" y2="12"></line><line x1="10" y1="18" x2="21" y2="18"></line><path d="M4 6h1v4"></path><path d="M4 10h2"></path><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"></path></svg>
                                    <span>Numbering</span>
                                </button>
                                <span class="preview-tool-divider"></span>
                                <button type="button" class="preview-tool-btn" onmousedown="event.preventDefault()" onclick="formatProposalPreview('justifyLeft')" title="Align Left">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="17" y1="10" x2="3" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="17" y1="18" x2="3" y2="18"></line></svg>
                                    <span>Left</span>
                                </button>
                                <button type="button" class="preview-tool-btn" onmousedown="event.preventDefault()" onclick="formatProposalPreview('justifyCenter')" title="Align Center">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="10" x2="6" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="18" y1="18" x2="6" y2="18"></line></svg>
                                    <span>Center</span>
                                </button>
                                <button type="button" class="preview-tool-btn" onmousedown="event.preventDefault()" onclick="formatProposalPreview('justifyRight')" title="Align Right">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="21" y1="10" x2="7" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="21" y1="18" x2="7" y2="18"></line></svg>
                                    <span>Right</span>
                                </button>
                                <span class="preview-tool-divider"></span>
                                <button type="button" class="preview-tool-btn" onmousedown="event.preventDefault()" onclick="formatProposalPreview('removeFormat')" title="Clear Formatting">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20H7L3 16C2 15 2 13 3 12L13 2L22 11L14 19"></path><line x1="18" y1="12" x2="7" y2="23"></line></svg>
                                    <span>Clear Format</span>
                                </button>
                            </div>
                            <button class="template-button" type="button" onclick="saveProposalTemplateNotice()" title="Save as Global Proposal Template">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                <span>Save Global Template</span>
                            </button>
                        </div>

                        <div class="proposal-preview-scroll">
                            <div class="preview-workspace">

                                {{-- =========================================================
                                     PAGE 1: COVER PAGE
                                     ========================================================= --}}
                                <article class="proposal-paper">
                                    <div class="proposal-paper-content" style="display: flex; flex-direction: column; height: 100%;">
                                        <div class="pdf-brand-title">
                                            John Kelly<br><span class="pdf-brand-amp">&amp;</span> Company
                                        </div>

                                        <div class="pdf-cover-year">
                                            {{ now()->format('Y') }}
                                        </div>

                                        <div class="pdf-cover-service editable" contenteditable="true" data-source="proposal_subject">
                                            {{ $proposal->subject ?: (implode(' & ', $proposalAreas) ?: ($deal->deal_title ?: 'Accounting & Compliance Advisory')) }}
                                        </div>

                                        <div class="pdf-cover-date">
                                            {{ now()->format('F d, Y') }}
                                        </div>

                                        <div class="pdf-cover-presented-box">
                                            <div class="pdf-cover-presented-label">
                                                Presented For:
                                            </div>
                                            <div class="pdf-cover-client-name">
                                                {{ $contactName ?: ($deal->primary_contact_name ?: '-') }}
                                            </div>
                                            <div class="pdf-cover-business-name">
                                                {{ $deal->company ?: ($deal->company_name ?: ($deal->deal_title ?: '-')) }}
                                            </div>
                                            <div class="pdf-cover-address">
                                                {{ $deal->address ?: ($deal->company_address ?: '-') }}
                                            </div>
                                        </div>

                                        <div class="pdf-cover-footer-bar">
                                            <div class="pdf-cover-meta-row">
                                                <span>{{ $deal->mobile_number ?: '-' }}</span>
                                                <span>start@jknc.io</span>
                                                <span>jknc.io</span>
                                                <span>{{ $deal->deal_code ?: '-' }}</span>
                                                <span>Confidential</span>
                                            </div>
                                            <div>
                                                3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000.
                                            </div>
                                        </div>
                                    </div>
                                </article>


                                {{-- =========================================================
                                     PAGE 2: I. EXECUTIVE SUMMARY & II. OUR ROLE AND VALUE
                                     ========================================================= --}}
                                <article class="proposal-paper">
                                    <div class="proposal-paper-running-header">
                                        Page 1 of 11
                                    </div>

                                    <div class="proposal-paper-content">
                                        <h2 class="doc-section-heading">
                                            <i>I.</i> Executive Summary
                                        </h2>

                                        <p class="doc-paragraph">
                                            <strong>John Kelly &amp; Company</strong> is a management consulting and corporate advisory company that assists businesses in growing, improving how they operate, and making better decisions for the future. With over 30 years of combined experience across the public and private sectors, the company works closely with organizations to strengthen systems, improve management discipline, and support compliance and governance needs with clarity and professionalism. John Kelly &amp; Company is supported by a multidisciplinary team with legal, financial, operational, and governance expertise, including Atty. Jose B. Ogang, CPA, MMPSM, former Mediator-Arbiter of the Department of Labor and Employment (DOLE); Jose Tomayo Rio, MM-BA, CPA, former Municipal Accountant of LGU Madridejos, Cebu; Lyndon Earl P. Rio, RN, CB, with extensive experience as an accountant and bookkeeper across various industries; and John Kelly Abalde, CLSSBB, CPM, a corporate secretary and board director serving organizations across multiple industries—working together to support clients in managing their businesses effectively and sustainably.
                                        </p>

                                        <p class="doc-paragraph">
                                            Our vision is to build a world where people, systems, and ideas work together where management is disciplined, individuals are empowered, and progress is shared. By 2030, we aim to be a global example of how well-managed and forward-thinking businesses can create lasting, positive change.
                                        </p>

                                        <p class="doc-paragraph">
                                            Our mission is simple: to build future-ready businesses today. We do this by helping leaders and teams become more organized, capable, and confident — creating strong foundations that make growth sustainable, innovation achievable, and success possible for everyone involved.
                                        </p>

                                        <h2 class="doc-section-heading" style="margin-top: 28px;">
                                            <i>II.</i> Our Role and Value to You
                                        </h2>

                                        <p class="doc-paragraph">
                                            As your trusted partner, John Kelly &amp; Company is here to guide and support you in keeping your business well-managed, transparent, and compliant. We believe that good management is not just about following rules, it's about building trust, responsibility, and confidence in how a business is run.
                                        </p>

                                        <p class="doc-paragraph">
                                            You can count on us to handle the details carefully while keeping communication open and simple. We work closely with you to make sure every step is clear and well-coordinated. Our goal is to help your business stay organized, compliant, and ready to grow with the assurance that everything is managed properly and with integrity.
                                        </p>
                                    </div>

                                    <div class="proposal-paper-running-footer">
                                        <strong>John Kelly &amp; Company</strong>
                                        3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000.<br>
                                        Email: start@jknc.io • Website: jknc.io • Phone: 0995-535-8729
                                    </div>
                                </article>


                                {{-- =========================================================
                                     PAGE 3: III. WHY JOHN KELLY & COMPANY IS THE RIGHT PARTNER
                                     ========================================================= --}}
                                <article class="proposal-paper">
                                    <div class="proposal-paper-running-header">
                                        Page 2 of 11
                                    </div>

                                    <div class="proposal-paper-content">
                                        <h2 class="doc-section-heading">
                                            <i>III.</i> Why John Kelly &amp; Company is the Right Partner
                                        </h2>

                                        <p class="doc-paragraph">
                                            At John Kelly &amp; Company, we believe in helping businesses grow with clarity, honesty, and purpose. Over the years, we've worked closely with trusted mentors, lawyers, and certified public accountants who share our goal of helping businesses run better and stronger. Through these partnerships and our hands-on approach, we continue to guide companies toward steady growth and lasting success.
                                        </p>

                                        <p class="doc-paragraph">
                                            Our range of services is designed to help businesses stay organized, compliant, and ready for growth through practical guidance, reliable support, and professional care which include the following areas of support:
                                        </p>

                                        <table class="doc-table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 35px; text-align: center;">No.</th>
                                                    <th style="width: 200px;">Service Area</th>
                                                    <th>Scope of Support</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td style="text-align: center;">1</td>
                                                    <td><strong>Accounting &amp; Compliance Advisory</strong></td>
                                                    <td>
                                                        a. Accounting Services<br>
                                                        b. AFS Preparation<br>
                                                        c. Audit Support / Coordination<br>
                                                        d. BIR Open Case Resolution<br>
                                                        e. BIR RDO Compliance Representative<br>
                                                        f. BIR Registration Assistance (L / M / S)<br>
                                                        g. BIR Registration - Update / Change Information<br>
                                                        h. Bookkeeping Services<br>
                                                        i. Business Permit<br>
                                                        j. On-site Profit and Loss Review<br>
                                                        k. SAWT Preparation and eSubmission Validation<br>
                                                        l. Tax Filing &amp; Compliance (BIR)<br>
                                                        m. Transfer of BIR Registration / Shares of Stock Assistance
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="text-align: center;">2</td>
                                                    <td><strong>Business Strategy &amp; Process Advisory</strong></td>
                                                    <td>
                                                        a. Digital Transformation<br>
                                                        b. Domain Purchase and Setup Assistance<br>
                                                        c. Financial Planning &amp; Analysis<br>
                                                        d. Organizational Structuring<br>
                                                        e. Process Improvement / SOP Development
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="text-align: center;">3</td>
                                                    <td><strong>Corporate &amp; Regulatory Advisory</strong></td>
                                                    <td>
                                                        a. AMLC Registration and Compliance Officer Setup Assistance<br>
                                                        b. Bank Opening Assistance<br>
                                                        c. Business Registration (SEC / DTI / BIR)<br>
                                                        d. Corporate Secretary Services<br>
                                                        e. Corporation Formation &amp; Registration Assistance<br>
                                                        f. Foreign Business Entry Support<br>
                                                        g. LGU &amp; SEC Compliance Representative<br>
                                                        h. New / Renewal LGU Business Registration (Complex / Non-Complex)
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="proposal-paper-running-footer">
                                        <strong>John Kelly &amp; Company</strong>
                                        3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000.<br>
                                        Email: start@jknc.io • Website: jknc.io • Phone: 0995-535-8729
                                    </div>
                                </article>


                                {{-- =========================================================
                                     PAGE 4: III. PRODUCTS OFFERED
                                     ========================================================= --}}
                                <article class="proposal-paper">
                                    <div class="proposal-paper-running-header">
                                        Page 3 of 11
                                    </div>

                                    <div class="proposal-paper-content">
                                        <h2 class="doc-section-heading">
                                            <i>III.</i> Products Offered
                                        </h2>

                                        <table class="doc-table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 35px; text-align: center;">No.</th>
                                                    <th style="width: 200px;">Product Area</th>
                                                    <th>Products Offered</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td style="text-align: center;">1</td>
                                                    <td><strong>Accounting &amp; Compliance Advisory</strong></td>
                                                    <td>
                                                        a. Archive Retrieval<br>
                                                        b. Digital Archive Copy<br>
                                                        c. Drafting of Certifications<br>
                                                        d. Drafting of Compliance Documents<br>
                                                        e. Drafting of Memorandum (Internal / External)<br>
                                                        f. Drafting of Responses to Letters / Notices
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="text-align: center;">2</td>
                                                    <td><strong>Business Strategy &amp; Process Advisory</strong></td>
                                                    <td>
                                                        a. Drafting of Policies &amp; Procedures<br>
                                                        b. Drafting of Reports / Formal Documents<br>
                                                        c. Drafting of Secretary's Certificates<br>
                                                        d. Notarization - Complex Documents / Simple Documents
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="text-align: center;">3</td>
                                                    <td><strong>Corporate &amp; Regulatory Advisory</strong></td>
                                                    <td>
                                                        a. Drafting of Demand Letters<br>
                                                        b. Drafting of Emails (Formal / Business)<br>
                                                        c. Drafting of Letters &amp; Notices<br>
                                                        d. Photocopy &amp; Printing
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="text-align: center;">4</td>
                                                    <td><strong>Governance &amp; Policy Advisory</strong></td>
                                                    <td>
                                                        a. Document Delivery (Metro Cebu / Outside Metro Cebu / LBC)<br>
                                                        b. Drafting of Affidavits (Non-Legal Advice)<br>
                                                        c. Drafting of Agreements / Simple Contracts<br>
                                                        d. Drafting of Board Resolutions / Endorsement Letters
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="proposal-paper-running-footer">
                                        <strong>John Kelly &amp; Company</strong>
                                        3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000.<br>
                                        Email: start@jknc.io • Website: jknc.io • Phone: 0995-535-8729
                                    </div>
                                </article>


                                {{-- =========================================================
                                     PAGE 5: IV. HIGHLIGHTS & V. COMMITMENT
                                     ========================================================= --}}
                                <article class="proposal-paper">
                                    <div class="proposal-paper-running-header">
                                        Page 4 of 11
                                    </div>

                                    <div class="proposal-paper-content">
                                        <h2 class="doc-section-heading">
                                            <i>IV.</i> Highlights from Our Proposal and Approach
                                        </h2>

                                        <p class="doc-paragraph">
                                            At <strong>John Kelly &amp; Company</strong>, we believe that real progress begins with clear communication, teamwork, and trust. Our goal is to make every step of the process simple, organized, and meaningful for your business.
                                        </p>

                                        <div class="doc-highlights-grid">
                                            <div class="doc-highlight-card">
                                                <h4>Guided Support</h4>
                                                <p>We're here to help your business stay on track by providing steady guidance and keeping things well-prepared and easy to follow.</p>
                                            </div>

                                            <div class="doc-highlight-card">
                                                <h4>Personalized Approach</h4>
                                                <p>Every business is different, so we take the time to understand your needs and adjust our process to fit what works best for you.</p>
                                            </div>

                                            <div class="doc-highlight-card">
                                                <h4>Open Communication</h4>
                                                <p>We keep you informed at all times so decisions are clear, updates are timely, and everyone moves forward together with confidence.</p>
                                            </div>

                                            <div class="doc-highlight-card">
                                                <h4>Integrity and Care</h4>
                                                <p>We value honesty, respect, and responsibility in everything we do - ensuring that our work always reflects the trust you place in us.</p>
                                            </div>
                                        </div>

                                        <h2 class="doc-section-heading" style="margin-top: 36px;">
                                            <i>V.</i> Our Commitment
                                        </h2>

                                        <p class="doc-paragraph">
                                            Every business we work with deserves clarity, respect, and dependable support. That is why we make it our promise to handle every task with honesty, care, and attention to detail. We understand that behind every document and process are people working hard to build something meaningful and we aim to make their work easier, more organized, and more secure.
                                        </p>

                                        <p class="doc-paragraph">
                                            Our approach is guided by consistency and genuine partnership. Over the years, we have worked hand in hand with trusted mentors, lawyers, and accountants who share our vision of helping businesses grow the right way - with discipline, transparency, and integrity at every step.
                                        </p>
                                    </div>

                                    <div class="proposal-paper-running-footer">
                                        <strong>John Kelly &amp; Company</strong>
                                        3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000.<br>
                                        Email: start@jknc.io • Website: jknc.io • Phone: 0995-535-8729
                                    </div>
                                </article>


                                {{-- =========================================================
                                     PAGE 6: VI. OUR PROPOSAL (SERVICES & PRODUCTS AVAILED)
                                     ========================================================= --}}
                                <article class="proposal-paper">
                                    <div class="proposal-paper-running-header">
                                        Page 5 of 11
                                    </div>

                                    <div class="proposal-paper-content">
                                        <h2 class="doc-section-heading">
                                            <i>VI.</i> Our Proposal
                                        </h2>

                                        <p class="doc-paragraph">
                                            We are pleased to submit this proposal for your consideration. John Kelly &amp; Company will provide the required advisory, preparation, coordination, and compliance support aligned with your engagement requirements.
                                        </p>

                                        <h3 style="font-size: 12px; font-weight: 700; color: #1e3a8a; font-style: italic; margin: 18px 0 8px;">
                                            Services Availed
                                        </h3>

                                        <table class="doc-table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 50px; text-align: center;">Item #</th>
                                                    <th>Name</th>
                                                    <th>Description</th>
                                                    <th>Activity/Output</th>
                                                    <th>Frequency</th>
                                                    <th>Deadline</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($proposalItems as $index => $item)
                                                    <tr>
                                                        <td style="text-align: center;">{{ $index + 1 }}</td>
                                                        <td><strong>{{ is_array($item) ? ($item['name'] ?? 'Service') : $item }}</strong></td>
                                                        <td>{{ is_array($item) ? ($item['description'] ?? ($deal->scope_of_work ?: 'Advisory & compliance coordination')) : ($deal->scope_of_work ?: 'Advisory & compliance coordination') }}</td>
                                                        <td>Execution / Filing / Advisory</td>
                                                        <td>As scheduled</td>
                                                        <td>{{ $deal->estimated_completion_date ? $deal->estimated_completion_date->format('M d, Y') : 'TBD' }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td style="text-align: center;">1</td>
                                                        <td>No service selected</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>TBD</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>

                                        <h3 style="font-size: 12px; font-weight: 700; color: #1e3a8a; font-style: italic; margin: 24px 0 8px;">
                                            Products Availed
                                        </h3>

                                        <table class="doc-table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 50px; text-align: center;">Item #</th>
                                                    <th>Name</th>
                                                    <th>Description</th>
                                                    <th>Activity/Output</th>
                                                    <th>Frequency</th>
                                                    <th>Deadline</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td style="text-align: center;">1</td>
                                                    <td>{{ $deal->product_type ?: 'Standard Corporate Documentation' }}</td>
                                                    <td>{{ $deal->scope_of_work ?: 'Complete filing and statutory binder' }}</td>
                                                    <td>Digital &amp; Physical copy</td>
                                                    <td>One-time</td>
                                                    <td>{{ $deal->estimated_completion_date ? $deal->estimated_completion_date->format('M d, Y') : 'TBD' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="proposal-paper-running-footer">
                                        <strong>John Kelly &amp; Company</strong>
                                        3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000.<br>
                                        Email: start@jknc.io • Website: jknc.io • Phone: 0995-535-8729
                                    </div>
                                </article>


                                {{-- =========================================================
                                     PAGE 7: VI. OUR PROPOSAL - REQUIREMENTS
                                     ========================================================= --}}
                                <article class="proposal-paper">
                                    <div class="proposal-paper-running-header">
                                        Page 6 of 11
                                    </div>

                                    <div class="proposal-paper-content">
                                        <h2 class="doc-section-heading">
                                            <i>VI.</i> Our Proposal - Requirements
                                        </h2>

                                        <h3 style="font-size: 12px; font-weight: 700; color: #1e3a8a; font-style: italic; margin: 0 0 6px;">
                                            What We Need From You
                                        </h3>

                                        <p class="doc-paragraph">
                                            To proceed smoothly, we may request the following:
                                        </p>

                                        <table class="doc-table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 50px; text-align: center;">Item #</th>
                                                    <th style="width: 130px;">Name</th>
                                                    <th>For Sole Proprietor / Professional / Individual;</th>
                                                    <th>For Juridical / Corporation / Partnership;</th>
                                                    <th>Optional / If Applicable;</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td style="text-align: center;">1</td>
                                                    <td><strong>Client documentary requirements</strong></td>
                                                    <td>
                                                        • Client Contact Form<br>
                                                        • Client Information Form<br>
                                                        • TIN ID<br>
                                                        • Business Permit / Mayor's Permit<br>
                                                        • BIR Certificate of Registration
                                                    </td>
                                                    <td>
                                                        • Special Power of Attorney<br>
                                                        • Board Resolution / Secretary's Certificate
                                                    </td>
                                                    <td>
                                                        • Additional supporting compliance records as may be required
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <p class="doc-paragraph" style="font-style: italic; font-size: 10px; color: #64748b; margin-top: 20px;">
                                            Additional requirements not listed above may be requested depending on the specific circumstances of the business and the requirements of the relevant government agency. Any such requirements will be communicated if and when identified. These requirements are determined by the applicable authority and are outside our control.
                                        </p>
                                    </div>

                                    <div class="proposal-paper-running-footer">
                                        <strong>John Kelly &amp; Company</strong>
                                        3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000.<br>
                                        Email: start@jknc.io • Website: jknc.io • Phone: 0995-535-8729
                                    </div>
                                </article>


                                {{-- =========================================================
                                     PAGE 8: VI. OUR PROPOSAL - FEES & FEE SUMMARY
                                     ========================================================= --}}
                                <article class="proposal-paper">
                                    <div class="proposal-paper-running-header">
                                        Page 7 of 11
                                    </div>

                                    <div class="proposal-paper-content">
                                        <h2 class="doc-section-heading">
                                            <i>VI.</i> Our Proposal - Fees
                                        </h2>

                                        <h3 style="font-size: 12px; font-weight: 700; color: #1e3a8a; font-style: italic; margin: 0 0 6px;">
                                            Fees
                                        </h3>

                                        <div style="font-size: 11px; font-weight: 700; color: #1e3a8a; font-style: italic; margin-bottom: 6px;">
                                            Services
                                        </div>

                                        <table class="doc-table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 50px; text-align: center;">Item #</th>
                                                    <th>Name</th>
                                                    <th style="width: 120px;">Service ID</th>
                                                    <th style="width: 100px; text-align: right;">Price</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($proposalItems as $index => $item)
                                                    <tr>
                                                        <td style="text-align: center;">{{ $index + 1 }}</td>
                                                        <td>{{ is_array($item) ? ($item['name'] ?? 'Service') : $item }}</td>
                                                        <td>SRV-{{ str_pad($index + 1, 3, '0', STR_PAD_LEFT) }}</td>
                                                        <td style="text-align: right;">-</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td style="text-align: center;">1</td>
                                                        <td>{{ $defaultServiceName }}</td>
                                                        <td>SRV-001</td>
                                                        <td style="text-align: right;">{{ number_format($proposalServicesTotal, 2) }}</td>
                                                    </tr>
                                                @endforelse
                                                <tr style="font-weight: 700; background: #f8fafc;">
                                                    <td colspan="3" style="text-align: right;">Total</td>
                                                    <td style="text-align: right;">{{ number_format($proposalServicesTotal, 2) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <div style="font-size: 11px; font-weight: 700; color: #1e3a8a; font-style: italic; margin: 18px 0 6px;">
                                            Products
                                        </div>

                                        <table class="doc-table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 50px; text-align: center;">Item #</th>
                                                    <th>Name</th>
                                                    <th style="width: 120px;">Service ID</th>
                                                    <th style="width: 100px; text-align: right;">Price</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td style="text-align: center;">1</td>
                                                    <td>{{ $deal->product_type ?: 'Compliance Documentation Package' }}</td>
                                                    <td>PRD-001</td>
                                                    <td style="text-align: right;">{{ number_format($proposalProductsTotal, 2) }}</td>
                                                </tr>
                                                <tr style="font-weight: 700; background: #f8fafc;">
                                                    <td colspan="3" style="text-align: right;">Total</td>
                                                    <td style="text-align: right;">{{ number_format($proposalProductsTotal, 2) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <h2 class="doc-section-heading" style="margin-top: 28px;">
                                            <i>VI.</i> Our Proposal - Fee Summary
                                        </h2>

                                        <table class="doc-table">
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th style="width: 150px; text-align: right;">Amount (₱)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Total Services</td>
                                                    <td style="text-align: right;">{{ number_format($proposalServicesTotal, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Total Product</td>
                                                    <td style="text-align: right;">{{ number_format($proposalProductsTotal, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Discount</td>
                                                    <td style="text-align: right;" id="docFeeDiscount">{{ number_format($proposalDiscount, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Subtotal (After Discount)</td>
                                                    <td style="text-align: right;" id="docFeeSubtotal">{{ number_format($proposalSubtotal, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Tax (if applicable)</td>
                                                    <td style="text-align: right;" id="docFeeTax">{{ number_format($proposalTax, 2) }}</td>
                                                </tr>
                                                <tr style="font-weight: 700; color: #1e3a8a; background: #eff6ff;">
                                                    <td><strong>Total Fees</strong></td>
                                                    <td style="text-align: right;"><strong id="docFeeTotal">{{ number_format($proposalTotal, 2) }}</strong></td>
                                                </tr>
                                                <tr>
                                                    <td>Down Payment (50%)</td>
                                                    <td style="text-align: right;" id="docFeeDownpayment">{{ number_format($proposalTotal * 0.5, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Balance Payable Upon Completion (50%)</td>
                                                    <td style="text-align: right;" id="docFeeBalance">{{ number_format($proposalTotal * 0.5, 2) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <div style="font-size: 10px; font-style: italic; color: #64748b;">
                                            (All rates are exclusive of VAT and/or withholding tax, if applicable)
                                        </div>
                                    </div>

                                    <div class="proposal-paper-running-footer">
                                        <strong>John Kelly &amp; Company</strong>
                                        3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000.<br>
                                        Email: start@jknc.io • Website: jknc.io • Phone: 0995-535-8729
                                    </div>
                                </article>


                                {{-- =========================================================
                                     PAGE 9: VII. AGREEMENT INCLUSIONS / EXCLUSIONS & SUPPLEMENTAL FEES
                                     ========================================================= --}}
                                <article class="proposal-paper">
                                    <div class="proposal-paper-running-header">
                                        Page 8 of 11
                                    </div>

                                    <div class="proposal-paper-content">
                                        <h2 class="doc-section-heading">
                                            <i>VII.</i> Agreement Inclusions and Exclusions
                                        </h2>

                                        <div style="font-size: 11px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">
                                            Agreement Inclusions
                                        </div>
                                        <ul style="font-size: 10.5px; line-height: 1.6; color: #334155; margin: 0 0 16px 18px; padding: 0;">
                                            <li>Permit facilitation services performed strictly within the scope of this Agreement and solely for its implementation, including lawful coordination, submission, and follow-up with the government offices covered by and coordinated under this Agreement.</li>
                                            <li>Printing, photocopying, mailing, and preparation of basic documentation as may be reasonably required within the scope of this Agreement for its proper performance and execution in relation to the government offices coordinated with.</li>
                                            <li>Limited transportation (fuel) expenses necessarily and actually incurred within the scope of this Agreement, exclusively for permit-related acts undertaken pursuant hereto in connection with the government offices coordinated with, and confined to the applicable jurisdiction.</li>
                                        </ul>

                                        <div style="font-size: 11px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">
                                            Agreement Exclusions
                                        </div>
                                        <ul style="font-size: 10.5px; line-height: 1.6; color: #334155; margin: 0 0 20px 18px; padding: 0;">
                                            <li>Transportation and accommodation expenses for off-site work.</li>
                                            <li>Government permit, license, and filing fees, including fees imposed by national and local government agencies.</li>
                                            <li>Notarization fees, penalties, surcharges, or late fees, if any.</li>
                                            <li>Costs of physical certificates, official forms, and certificate paper, when required.</li>
                                            <li>Third-party service fees, including courier services, document authentication, translation, printing, or similar incidental expenses.</li>
                                            <li>Rush processing fees or special handling requests required to meet accelerated timelines.</li>
                                            <li>Other incidental matters, such as document authentication, translation, rush requests, third-party service fees, etc., that may be required to complete a task or filing. Additional similar expenses not listed but reasonably necessary for task completion may also apply as agreed upon by both parties.</li>
                                        </ul>

                                        <div style="font-size: 11px; font-weight: 700; color: #1e3a8a; font-style: italic; margin-bottom: 8px;">
                                            Supplemental Fees
                                        </div>

                                        <table class="doc-table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 180px;">Service</th>
                                                    <th>Description</th>
                                                    <th style="width: 120px;">Fee</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><strong>Printing of Client Document</strong></td>
                                                    <td>Printing of any client-provided document on A4, Legal, or Short Bond paper.</td>
                                                    <td>₱5 per page</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Archive Fee</strong></td>
                                                    <td>Fee for retrieving a document from archives and reissuing it to clients or stakeholders.</td>
                                                    <td>₱50 per document</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Delivery Fee (Metro Cebu)</strong></td>
                                                    <td>Delivery from JK&amp;C office to a designated drop-off point within Metro Cebu.</td>
                                                    <td>₱250 per drop-off</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Photocopy</strong></td>
                                                    <td>Photocopying of documents in A4, Legal, or Short Bond paper.</td>
                                                    <td>₱5 per page</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Digital Archive Copy</strong></td>
                                                    <td>Providing a digital copy of a document from the archive, sent to the client digitally.</td>
                                                    <td>₱50 per document</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Notarization of Simple Documents</strong></td>
                                                    <td>Notarization of simple documents, as defined by the IBP — routine in nature, not involving property rights or financial obligations.</td>
                                                    <td>₱800 per document</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Notarization of Complex Documents</strong></td>
                                                    <td>Involving property transfers, corporate acts, financial transactions, or multiple parties.</td>
                                                    <td>Subject to Evaluation</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="proposal-paper-running-footer">
                                        <strong>John Kelly &amp; Company</strong>
                                        3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000.<br>
                                        Email: start@jknc.io • Website: jknc.io • Phone: 0995-535-8729
                                    </div>
                                </article>


                                {{-- =========================================================
                                     PAGE 10: VIII. TERMS AND CONDITIONS
                                     ========================================================= --}}
                                <article class="proposal-paper">
                                    <div class="proposal-paper-running-header">
                                        Page 9 of 11
                                    </div>

                                    <div class="proposal-paper-content">
                                        <h2 class="doc-section-heading">
                                            <i>VIII.</i> Terms and Conditions
                                        </h2>

                                        <div style="font-size: 11px; line-height: 1.6; color: #334155;">
                                            <p style="margin-bottom: 10px;">
                                                <strong>1. Acceptance and Commencement:</strong> Work will commence upon the client's formal acceptance of this proposal and receipt of fifty percent (50%) of the agreed professional fee as an initial payment. The remaining fifty percent (50%) balance shall be due and payable upon completion of the agreed scope of work, prior to the release of final documents or deliverables.
                                            </p>
                                            <p style="margin-bottom: 10px;">
                                                <strong>2. Client's Responsibilities:</strong> Provide accurate, complete, and timely information and documents; complete and submit all required municipal forms; review, confirm, and provide prompt feedback on drafts; settle all professional fees within three (3) calendar days from billing.
                                            </p>
                                            <p style="margin-bottom: 10px;">
                                                <strong>3. John Kelly &amp; Company's Responsibilities:</strong> Perform all agreed tasks with care, honesty, and professionalism in accordance with the Scope of Service; review available documents; prepare and assist in completing required forms; coordinate and follow up with relevant government offices; provide progress updates.
                                            </p>
                                            <p style="margin-bottom: 10px;">
                                                <strong>4. Client Compliance and Regulatory Delays:</strong> Processing and issuance of registrations or filings are subject to compliance with requirements imposed by the BIR and government agencies. JK&amp;C does not guarantee approval timelines determined solely by government authorities. Delays arising from client failure to comply or supply documents shall not be attributable to JK&amp;C.
                                            </p>
                                            <p style="margin-bottom: 10px;">
                                                <strong>5. No Responsibility for Government Assessment:</strong> All financial declarations submitted are provided by the client. JK&amp;C does not alter, underdeclare, or adjust figures for assessment purposes. Assessments and penalties imposed by authorities remain the client's sole responsibility.
                                            </p>
                                            <p style="margin-bottom: 10px;">
                                                <strong>6. Release, Waiver, and Quitclaim:</strong> The client acknowledges JK&amp;C is engaged solely for coordination and advisory support, and fully releases and discharges JK&amp;C from claims arising from client omission, government sanctions, or outside compliance matters.
                                            </p>
                                            <p style="margin-bottom: 10px;">
                                                <strong>7. Communication &amp; Coordination:</strong> Official communication will proceed through designated representatives and official email.
                                            </p>
                                            <p style="margin-bottom: 10px;">
                                                <strong>8. Severability &amp; Termination:</strong> Any provision declared invalid shall not affect remaining provisions. Either party may end the engagement with thirty (30) days written notice upon settling pending obligations.
                                            </p>
                                            <p style="margin-bottom: 10px;">
                                                <strong>9. Confidentiality &amp; Governing Law:</strong> All shared documents will be kept strictly confidential. This agreement shall be governed by the laws of the Republic of the Philippines, and disputes submitted to the proper courts of Cebu City.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="proposal-paper-running-footer">
                                        <strong>John Kelly &amp; Company</strong>
                                        3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000.<br>
                                        Email: start@jknc.io • Website: jknc.io • Phone: 0995-535-8729
                                    </div>
                                </article>


                                {{-- =========================================================
                                     PAGE 11: IX. CLIENT ENGAGEMENT TEAM & X. CONFORME
                                     ========================================================= --}}
                                <article class="proposal-paper">
                                    <div class="proposal-paper-running-header">
                                        Page 10 of 11
                                    </div>

                                    <div class="proposal-paper-content">
                                        <h2 class="doc-section-heading">
                                            <i>IX.</i> Client Engagement Team
                                        </h2>

                                        <p class="doc-paragraph">
                                            John Kelly &amp; Company assigns a team of consultants and associates who collectively take responsibility for overseeing the project engagement, ensuring consistent guidance, clear communication, and smooth coordination throughout the duration of the engagement.
                                        </p>

                                        <table class="doc-table">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Designation</th>
                                                    <th>Branch</th>
                                                    <th>Email</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><strong>Mr. John Kelly D. Abalde</strong></td>
                                                    <td>Senior Consultant</td>
                                                    <td>Cebu City HQ Branch</td>
                                                    <td>john.abalde@jknc.io</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Mr. Lyndon Earl Rio</strong></td>
                                                    <td>Senior Consultant</td>
                                                    <td>Cebu City HQ Branch</td>
                                                    <td>l.rio@jknc.io</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Ms. Ma. Lourdes T. Mata</strong></td>
                                                    <td>Associate</td>
                                                    <td>Cebu City HQ Branch</td>
                                                    <td>m.mata@jknc.io</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Ms. Rubeca Potayre</strong></td>
                                                    <td>Associate</td>
                                                    <td>Cebu City HQ Branch</td>
                                                    <td>r.potayre@jknc.io</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Ms. Immaculate Espina</strong></td>
                                                    <td>Associate</td>
                                                    <td>Lapu-Lapu Branch</td>
                                                    <td>-</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Ms. Carmela Ortiz</strong></td>
                                                    <td>Associate</td>
                                                    <td>Cebu City HQ Branch</td>
                                                    <td>-</td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <div style="font-size: 8.5px; line-height: 1.5; color: #64748b; font-style: italic; margin: 20px 0;">
                                            <em>-End-</em><br>
                                            This proposal is system-generated through the John Kelly &amp; Company BIR Compliance Services Management System and was generated electronically under Reference ID: <strong>{{ $deal->deal_code ?: 'DEAL-54' }}</strong> in the ordinary course of business; no handwritten or electronic signature is required for its validity, and this document shall be considered legally valid, binding for reference and evaluation purposes, and admissible as an official business record pursuant to applicable Philippine laws on electronic documents and electronic transactions, with any subsequent approval, payment, or engagement arising from this proposal to be governed by the final service agreement, official receipt, or written confirmation issued by John Kelly &amp; Company.
                                        </div>

                                        <h2 class="doc-section-heading" style="margin-top: 24px;">
                                            <i>X.</i> Conforme and Acceptance
                                        </h2>

                                        <p class="doc-paragraph">
                                            By signing below, the parties acknowledge and accept the terms and conditions outlined in this proposal, which shall constitute a binding agreement.
                                        </p>

                                        <div class="doc-signature-grid">
                                            <div class="doc-signature-block">
                                                <div class="doc-signature-title">For the Client</div>
                                                <div class="doc-signature-line">
                                                    <div class="doc-signatory-name">{{ $contactName ?: ($deal->primary_contact_name ?: '-') }}</div>
                                                    <div class="doc-signatory-role">{{ $deal->company ?: ($deal->company_name ?: '-') }}</div>
                                                </div>
                                            </div>

                                            <div class="doc-signature-block">
                                                <div class="doc-signature-title">For John Kelly &amp; Company</div>
                                                <div class="doc-signature-line">
                                                    <div class="doc-signatory-name">{{ $deal->lead_consultant ?: ($deal->owner_name ?: 'John Kelly Abalde') }}</div>
                                                    <div class="doc-signatory-role">Managing Consultant / Principal</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="proposal-paper-running-footer">
                                        <strong>John Kelly &amp; Company</strong>
                                        3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000.<br>
                                        Email: start@jknc.io • Website: jknc.io • Phone: 0995-535-8729
                                    </div>
                                </article>

                            </div>
                        </div>

                    </section>

                            </div>
                        </div>
                        <div class="pwm-modal-footer">
                            <button type="button" class="pwm-btn-cancel" onclick="closeProposalWorkspaceModal()">Cancel</button>
                            <button type="submit" form="deal-proposal-form" class="pwm-btn-save">Save Proposal</button>
                        </div>
                    </div>
                </div>

            </div>{{-- END TAB PANEL: PROPOSAL --}}


            {{-- =====================================================
                 TAB PANEL: FINANCE (REDESIGNED UI/UX)
            ====================================================== --}}
            <div class="deal-tab-panel" id="tab-panel-finance" style="display: none;">

                @php
                    $financeLoggedUser = auth()->check() ? auth()->user()->name : ($deal->finance ?: ($deal->owner_name ?: 'Juan Dela Cruz'));
                    $isQuotationCompleted = in_array($deal->pipeline_stage, ['Proposal', 'Negotiation', 'Payment', 'Activation', 'Closed Won']) || ($proposal->recipient_email ? true : false);

                    $financeProposalItems = [];
                    if (isset($proposalItems) && count($proposalItems)) {
                        foreach ($proposalItems as $idx => $item) {
                            $fName = is_array($item) ? ($item['name'] ?? 'Commercial Scope Item') : $item;
                            $fPrice = is_array($item) && isset($item['price']) && (float)$item['price'] > 0
                                ? (float)$item['price']
                                : (is_array($item) && isset($item['amount']) && (float)$item['amount'] > 0
                                    ? (float)$item['amount']
                                    : ($proposalTotal > 0 && count($proposalItems)
                                        ? $proposalTotal / count($proposalItems)
                                        : ((float)($deal->amount ?: $deal->total_estimated_engagement_value ?: 0) / (count($proposalItems) ?: 1))));
                            $fCode = $deal->deal_code ? ($deal->deal_code . '-' . str_pad($idx + 1, 2, '0', STR_PAD_LEFT)) : ('START-' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT));
                            $financeProposalItems[] = [
                                'id' => $idx + 1,
                                'name' => $fName,
                                'code' => $fCode,
                                'price' => (float)$fPrice,
                            ];
                        }
                    } else {
                        $fallbackPrice = $proposalTotal > 0 ? $proposalTotal : ($deal->total_estimated_engagement_value ?: $deal->amount ?: 0);
                        $financeProposalItems[] = [
                            'id' => 1,
                            'name' => $defaultServiceName,
                            'code' => $deal->deal_code ? ($deal->deal_code . '-01') : 'START-001',
                            'price' => (float)$fallbackPrice,
                        ];
                    }
                @endphp

                {{-- CARD 1: FINANCIAL DETAILS & COMMERCIAL TERMS --}}
                <div class="finance-details-card">
                    <div class="finance-header">
                        <div class="finance-title-group">
                            <div class="finance-title-row">
                                <h2>Financial Details &amp; Commercial Terms</h2>
                            </div>
                            <p>View deal financial details, payment terms, and commercial conditions.</p>
                        </div>

                        {{-- SEQUENTIAL NOTIFY FINANCE ACTION & DROPDOWN --}}
                        <div class="finance-notify-wrapper" id="financeNotifyWrapper">
                                <button type="button" 
                                        class="finance-notify-btn btn-notify-initial" 
                                        id="financeNotifyTriggerBtn" 
                                        onclick="toggleFinanceNotifyDropdown(event)" 
                                        aria-haspopup="true" 
                                        aria-expanded="false">
                                    <span id="financeNotifyBtnIcon">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                        </svg>
                                    </span>
                                    <span id="financeNotifyBtnLabel">Notify Finance</span>
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </button>

                            <div class="finance-notify-menu" id="financeNotifyMenu" role="menu">
                                {{-- OPTION 1: SEND QUOTATION NOTICE --}}
                                <button type="button" 
                                        class="finance-notify-item" 
                                        id="financeOptionQuotationNotice" 
                                        onclick="handleSendFinanceNotice('quotation')"
                                        role="menuitem">
                                    <span class="finance-notify-item-icon" style="color: #2563eb;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="22" y1="2" x2="11" y2="13"></line>
                                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                        </svg>
                                    </span>
                                    <div class="finance-notify-item-text">
                                        <span class="finance-notify-item-title" id="financeOptionQuotationTitle">Send Quotation Notice</span>
                                        <span class="finance-notify-item-sub" id="financeOptionQuotationSub">Dispatch quotation dispatch notice to Finance desk.</span>
                                    </div>
                                </button>

                                {{-- OPTION 2: SEND PAYMENT NOTICE (LOCKED UNTIL QUOTATION IS COMPLETED) --}}
                                <button type="button" 
                                        class="finance-notify-item disabled" 
                                        id="financeOptionPaymentNotice" 
                                        onclick="handleSendFinanceNotice('payment')"
                                        role="menuitem"
                                        disabled>
                                    <span class="finance-notify-item-icon" id="financeOptionPaymentIcon" style="color: #94a3b8;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                        </svg>
                                    </span>
                                    <div class="finance-notify-item-text">
                                        <span class="finance-notify-item-title" id="financeOptionPaymentTitle">Send Payment Notice</span>
                                        <span class="finance-notify-item-sub" id="financeOptionPaymentSub" style="color: #94a3b8;">Available after quotation is completed</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- WORKFLOW STATUS NOTICE BANNER --}}
                    <div id="financeNoticeBanner" class="finance-notice-banner notice-info" style="display: none;">
                        <div class="finance-notice-left">
                            <span id="financeNoticeIcon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                            </span>
                            <span id="financeNoticeText">Quotation notice has been sent. Waiting for quotation to be processed.</span>
                        </div>
                        <div class="finance-notice-time" id="financeNoticeTime"></div>
                    </div>

                    @php
                        $resolvedDealValue = $proposalTotal > 0 ? $proposalTotal : ($deal->amount ?: $deal->total_estimated_engagement_value);
                        $resolvedProfFee = $deal->est_professional_fee ?: $resolvedDealValue;
                        $resolvedPricingModel = $deal->pricing_model ?? $deal->engagement_type ?? 'Fixed Price';
                        $resolvedPaymentTerms = $deal->payment_terms ?: (in_array($deal->pipeline_stage, ['Closed Won', 'Activation', 'Payment']) ? '100% Full Payment (Fully Paid)' : '100% Advance Payment');
                        $resolvedCommission = $deal->commission_applicable ?: 'None';
                    @endphp

                    {{-- TWO COLUMN FINANCIAL DETAILS GRID --}}
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Deal Value / Engagement Total</div>
                            <div class="detail-value" id="financeGridDealValue">
                                {{ $money($resolvedDealValue) }}
                            </div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Pricing Model</div>
                            <div class="detail-value" id="financeGridPricingModel">
                                {{ $display($resolvedPricingModel) }}
                            </div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Payment Terms</div>
                            <div class="detail-value" id="financeGridPaymentTerms">
                                {{ $display($resolvedPaymentTerms) }}
                            </div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Commission Applicable</div>
                            <div class="detail-value" id="financeGridCommission">
                                {{ $display($resolvedCommission) }}
                            </div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Estimated Professional Fee</div>
                            <div class="detail-value" id="financeGridProfFee">
                                {{ $money($resolvedProfFee) }}
                            </div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Estimated Government Fees</div>
                            <div class="detail-value" id="financeGridGovFee">
                                {{ $money($deal->est_government_fee) }}
                            </div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Estimated Service Support Fee</div>
                            <div class="detail-value" id="financeGridSupportFee">
                                {{ $money($deal->est_service_support_fee) }}
                            </div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Applied Discount</div>
                            <div class="detail-value" id="financeGridDiscount">
                                {{ (float)($proposal->discount ?? $deal->discount ?? 0) > 0 ? ('- ' . $money($proposal->discount ?? $deal->discount)) : '-' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CARD 2: ITEM-LEVEL PAYMENT ALLOCATION & PROGRESSIVE ACTIVATION READINESS --}}
                <div class="finance-allocation-card">
                    <div class="finance-header">
                        <div class="finance-title-group">
                            <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0;">Item-Level Payment Allocation &amp; Progressive Activation Readiness</h2>
                            <p style="font-size: 13.5px; color: #64748b; margin-top: 4px; font-weight: 400;">Track payment allocation and activation readiness for each service item.</p>
                        </div>
                    </div>

                    <div class="finance-table-wrap">
                        <table class="finance-table" id="financeAllocationTable">
                            <thead>
                                <tr>
                                    <th>Service / Proposal Item</th>
                                    <th class="th-amount">Fee</th>
                                    <th class="th-amount">Allocated</th>
                                    <th class="th-amount">Balance</th>
                                    <th class="th-center">Payment State</th>
                                    <th class="th-center">Activation Readiness</th>
                                    <th class="th-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="financeAllocationTableBody">
                                @foreach($financeProposalItems as $item)
                                    <tr data-item-id="{{ $item['id'] }}">
                                        <td>
                                            <div class="finance-item-title">{{ $item['name'] }}</div>
                                            <a href="#" onclick="openProposalWorkspace(); return false;" class="finance-item-code">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                                                <span>{{ $item['code'] }}</span>
                                            </a>
                                        </td>
                                        <td class="td-amount" style="font-weight: 700; color: #0f172a;">
                                            {{ $money($item['price']) }}
                                        </td>
                                        <td class="td-amount finance-col-allocated" style="font-weight: 700; color: #0f172a;">
                                            ₱0.00
                                        </td>
                                        <td class="td-amount finance-col-balance" style="font-weight: 700; color: #0f172a;">
                                            {{ $money($item['price']) }}
                                        </td>
                                        <td class="td-center finance-col-payment-state">
                                            <span class="finance-pill-unpaid">Unpaid</span>
                                        </td>
                                        <td class="td-center finance-col-activation-readiness">
                                            <span class="finance-pill-pending">Pending Payment</span>
                                        </td>
                                        <td class="td-center">
                                            <button type="button" 
                                                    class="finance-btn-allocate" 
                                                    onclick="openRecordPaymentModal({scope: 'item', itemId: {{ $item['id'] }}})">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                                </svg>
                                                <span>Allocate</span>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- CARD 3: PAYMENT ALLOCATION RECORDS LEDGER --}}
                <div class="finance-ledger-card">
                    <div class="finance-header" style="align-items: flex-start; margin-bottom: 20px;">
                        <div class="finance-title-group">
                            <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0;">Payment Allocation Records Ledger</h2>
                            <p style="font-size: 13.5px; color: #64748b; margin-top: 4px; font-weight: 400;">View and manage payment allocation records and transaction history.</p>
                        </div>
                    </div>

                    <div id="financeLedgerContainer">
                        {{-- EMPTY STATE --}}
                        <div class="finance-empty-panel" id="financeLedgerEmptyState">
                            <div class="finance-empty-icon-circle">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                    <line x1="2" y1="10" x2="22" y2="10"></line>
                                </svg>
                            </div>
                            <h3 class="finance-empty-heading">No payment allocations recorded yet</h3>
                            <p class="finance-empty-subtext">Payment allocations and collection receipts will appear here once recorded.</p>
                        </div>

                        {{-- RECORDS LEDGER TABLE --}}
                        <div class="finance-ledger-table-wrap" id="financeLedgerTableWrap" style="display: none;">
                            <table class="finance-ledger-table" id="financeLedgerTable">
                                <colgroup>
                                    <col class="col-ledger-date">
                                    <col class="col-ledger-ref">
                                    <col class="col-ledger-scope">
                                    <col class="col-ledger-method">
                                    <col class="col-ledger-amount">
                                    <col class="col-ledger-recorded">
                                    <col class="col-ledger-notes">
                                    <col class="col-ledger-action">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th class="col-ledger-date">Date</th>
                                        <th class="col-ledger-ref">Ref / Collection</th>
                                        <th class="col-ledger-scope">Scope / Item</th>
                                        <th class="col-ledger-method">Method</th>
                                        <th class="col-ledger-amount">Allocated Amount</th>
                                        <th class="col-ledger-recorded">Recorded By</th>
                                        <th class="col-ledger-notes">Notes</th>
                                        <th class="col-ledger-action th-action">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="financeLedgerTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>{{-- END TAB PANEL: FINANCE --}}


            {{-- =====================================================
                 TAB PANEL: START (PROGRESSIVE START ACTIVATION BATCHES)
            ====================================================== --}}
            <div class="deal-tab-panel" id="tab-panel-start" style="display: none;">

                @php
                    $dealStartBatches = $deal->startRecords ?? collect();
                    $totalBatchesCount = $dealStartBatches->count();
                    $activatedBatchesCount = $dealStartBatches->filter(fn($r) => in_array($r->status, ['Completed', 'Activated', 'Active', 'Issued']))->count();
                    $confirmedAssignmentsCount = $dealStartBatches->flatMap(fn($b) => $b->assignments ?? collect())->filter(fn($a) => in_array($a->status, ['Confirmed', 'Acknowledged', 'Completed']))->count();
                    $teamConfirmationsCount = $confirmedAssignmentsCount > 0 
                        ? $confirmedAssignmentsCount 
                        : $dealStartBatches->filter(fn($r) => in_array($r->status, ['Team Confirmed', 'Activated', 'Active', 'Completed', 'Issued']))->count();
                    $readyBatchesCount = $dealStartBatches->filter(fn($r) => in_array($r->status, ['Draft', 'Ready', 'For Final Review', 'Team Confirmed']))->count();
                @endphp

                {{-- =====================================================
                     1. PROGRESSIVE START ACTIVATION BATCHES
                ====================================================== --}}
                <div class="start-batches-card" style="margin-bottom: 24px;">
                    {{-- 1. SECTION HEADER --}}
                    <div class="start-header-row">
                        <div class="start-title-group">
                            <h2 class="start-section-title">Progressive START Activation Batches</h2>
                            <p class="start-section-subtitle">A single deal may progressively activate services across multiple structured START batches.</p>
                        </div>
                    </div>

                    {{-- 2. 4 KPI STAT CARDS --}}
                    <div class="start-stats-grid">
                        <div class="start-stat-card">
                            <span class="start-stat-label">TOTAL BATCHES</span>
                            <div class="start-stat-value" id="startStatTotalBatches">{{ $totalBatchesCount }}</div>
                        </div>

                        <div class="start-stat-card">
                            <span class="start-stat-label">ACTIVATED &amp; ISSUED</span>
                            <div class="start-stat-value" id="startStatActivated">{{ $activatedBatchesCount }}</div>
                        </div>

                        <div class="start-stat-card">
                            <span class="start-stat-label">TEAM CONFIRMATIONS</span>
                            <div class="start-stat-value" id="startStatConfirmed">{{ $teamConfirmationsCount }}</div>
                        </div>

                        <div class="start-stat-card">
                            <span class="start-stat-label">READY FOR ACTIVATION</span>
                            <div class="start-stat-value" id="startStatReady">{{ $readyBatchesCount }}</div>
                        </div>
                    </div>

                    {{-- 3. BATCHES TABLE CONTAINER --}}
                    <div id="startBatchesContainer">
                        @if($totalBatchesCount === 0)
                            {{-- Empty State --}}
                            <div class="start-empty-panel" id="startBatchesEmptyState">
                                <div class="start-empty-icon-circle">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                    </svg>
                                </div>
                                <h3 class="start-empty-heading">No START batches created yet.</h3>
                                <p class="start-empty-subtext">No START batches have been generated for this deal.</p>
                            </div>
                        @else
                            {{-- Table Container --}}
                            <div class="start-table-wrap" id="startBatchesTableWrap">
                                <table class="start-table" id="startBatchesTable">
                                    <colgroup>
                                        <col style="width: 20%;">
                                        <col style="width: 22%;">
                                        <col style="width: 14%;">
                                        <col style="width: 20%;">
                                        <col style="width: 13%;">
                                        <col style="width: 11%;">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>START Code</th>
                                            <th>Activated Items / Scope</th>
                                            <th>Status</th>
                                            <th>Service Memo</th>
                                            <th>Opened Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="startBatchesTableBody">
                                        @foreach($dealStartBatches as $batch)
                                            <tr>
                                                <td style="white-space: nowrap;">
                                                    <div class="start-code-title">{{ $batch->start_code ?: ('ST-' . $batch->id) }}</div>
                                                    <div class="start-code-subtitle">{{ $batch->start_title ?: 'START Batch' }}</div>
                                                </td>
                                                <td>
                                                    <div class="start-scope-cell">
                                                        <div class="start-scope-name">{{ $batch->scope ?: 'Active Scope' }}</div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="start-badge start-badge-activated"><span class="start-dot"></span>{{ $batch->status ?: 'Active' }}</span>
                                                </td>
                                                <td style="white-space: nowrap;">
                                                    <button type="button" class="start-memo-btn-soft" onclick="openStartBatchMemoTab({{ $batch->id }})">
                                                        View Service Memo
                                                    </button>
                                                </td>
                                                <td style="white-space: nowrap;">
                                                    <div class="start-date-main">{{ $batch->created_at ? $batch->created_at->format('M d, Y') : '-' }}</div>
                                                </td>
                                                <td class="start-action-cell" style="white-space: nowrap; display: flex; align-items: center; gap: 6px;">
                                                    <button type="button" class="start-btn-details-soft" onclick="openStartBatchDetails({{ $batch->id }})">
                                                        View Details
                                                    </button>
                                                    <button type="button" class="btn-consultation-more" title="Delete START Batch" style="color: #ef4444; border-color: #fecaca; background: #fff; width: 28px; height: 28px;" onclick="handleDeleteStartBatch(event, {{ $batch->id }})">
                                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <polyline points="3 6 5 6 21 6"></polyline>
                                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- =====================================================
                     2. START FORM (ORIGINAL)
                ====================================================== --}}
                <section class="detail-section" id="start-form">

                    <div class="start-header">

                        <div>
                            <h2>
                                START Form
                            </h2>

                            <p class="start-description">
                                Manage the project START intake directly from this deal.
                            </p>
                        </div>

                        <div class="start-actions">

                            <a href="#start-form"
                               onclick="downloadStartPdf(); return false;"
                               class="start-action">
                                <span>▣</span>
                                Download START PDF
                            </a>

                            <a href="#start-form"
                               onclick="openCreateStartBatchModal(); return false;"
                               class="start-action">
                                <span>✎</span>
                                Edit START
                            </a>

                        </div>

                    </div>


                    <div class="start-paper" id="startPaperContainer">

                        <div class="start-status-row">

                            <div>
                                START Status:
                                <span class="start-status">
                                    Draft
                                </span>
                            </div>

                            <div class="approval-required">
                                ADMIN APPROVAL REQUIRED
                            </div>

                        </div>


                        <div class="start-brand-title">

                            <div class="start-company">
                                John Kelly
                                <br>
                                Company
                            </div>

                            <div class="start-title">
                                SERVICE TASK ACTIVATION AND
                                <br>
                                ROUTING TRACKER (START)
                            </div>

                            <div class="start-subtitle">
                                CASA-F-01-v1.0.03-16.26
                            </div>

                        </div>


                        <div class="start-info-grid">

                            <div class="start-info-cell">
                                <div class="start-label">
                                    Client Name
                                </div>

                                <div class="start-value">
                                    {{ $contactName }}
                                </div>
                            </div>

                            <div class="start-info-cell">
                                <div class="start-label">
                                    Product
                                </div>

                                <div class="start-value">
                                    {{ $display($deal->product_type ?? null) }}
                                </div>
                            </div>

                            <div class="start-info-cell">
                                <div class="start-label">
                                    Business Name
                                </div>

                                <div class="start-value">
                                    {{ $display($deal->company) }}
                                </div>
                            </div>

                            <div class="start-info-cell">
                                <div class="start-label">
                                    Services
                                </div>

                                <div class="start-value">
                                    {{ $listValue($servicesProducts) }}
                                </div>
                            </div>

                            <div class="start-info-cell">
                                <div class="start-label">
                                    CONDEAL Ref No.
                                </div>

                                <div class="start-value">
                                    {{ $display($deal->deal_code) }}
                                </div>
                            </div>

                            <div class="start-info-cell">
                                <div class="start-label">
                                    Date
                                </div>

                                <div class="start-value">
                                    {{ now()->format('m/d/Y') }}
                                </div>
                            </div>

                            <div class="start-info-cell">
                                <div class="start-label">
                                    Service Area
                                </div>

                                <div class="start-value">
                                    {{ $listValue($serviceAreas) }}
                                </div>
                            </div>

                            <div class="start-info-cell">
                                <div class="start-label">
                                    Engagement Type
                                </div>

                                <div class="start-value">
                                    {{ $display($deal->engagement_type) }}
                                </div>
                            </div>

                            <div class="start-info-cell">
                                <div class="start-label">
                                    Date Started
                                </div>

                                <div class="start-value">
                                    {{ $date($plannedStart) }}
                                </div>
                            </div>

                            <div class="start-info-cell">
                                <div class="start-label">
                                    Date Completed
                                </div>

                                <div class="start-value">
                                    {{ $date($estimatedCompletion) }}
                                </div>
                            </div>

                        </div>


                        <div class="start-section-title">
                            Client Due Diligence (KYC) Documents
                        </div>

                        <div style="
                            padding:5px;
                            text-align:center;
                            background:#eef4ff;
                            border-left:1px solid #1e293b;
                            border-right:1px solid #1e293b;
                            font-size:8px;
                            font-weight:700;
                        ">
                            JURIDICAL ENTITY
                            <i>(Corporation / OPC / Partnership / Cooperative)</i>
                        </div>

                        <div class="start-empty">
                            No KYC requirements available for this business organization yet.
                        </div>


                        <div class="start-section-title">
                            Engagement-Specific Requirements
                        </div>

                        <table class="start-table">

                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Requirement / Document</th>
                                    <th>Notes</th>
                                    <th>Purpose</th>
                                    <th>Provided By</th>
                                    <th>Submitted To</th>
                                    <th>Assigned To</th>
                                    <th>Timeline</th>
                                </tr>
                            </thead>

                            <tbody>

                                <tr>
                                    <td>1</td>

                                    <td>
                                        Initial intake and supporting documents
                                    </td>

                                    <td>-</td>

                                    <td>
                                        START intake
                                    </td>

                                    <td>
                                        Client
                                    </td>

                                    <td>
                                        Sales &amp; Marketing
                                    </td>

                                    <td>
                                        {{ $display($deal->assigned_associate) }}
                                    </td>

                                    <td>
                                        To be scheduled
                                    </td>
                                </tr>

                            </tbody>

                        </table>


                        <div class="clearance-title">
                            CLEARANCE
                        </div>

                        <div class="clearance-grid">

                            <div class="clearance-cell">
                                ASSIGNED TO REGULAR/PROJECT TEAM LEAD

                                <div style="margin-top:20px; font-weight: 600; text-transform: uppercase;">
                                    {{ $clearanceLeadConsultant }}
                                </div>

                                <div class="signature-line">
                                    Signature over Printed Name
                                </div>
                            </div>

                            <div class="clearance-cell">
                                LEAD CONSULTANT CONFIRMED

                                <div style="margin-top:20px; font-weight: 600; text-transform: uppercase;">
                                    {{ $clearanceLeadConsultant }}
                                </div>

                                <div class="signature-line">
                                    Signature over Printed Name
                                </div>
                            </div>

                            <div class="clearance-cell">
                                LEAD ASSOCIATE ASSIGNED

                                <div style="margin-top:20px; font-weight: 600; text-transform: uppercase;">
                                    {{ $clearanceLeadAssociate }}
                                </div>

                                <div class="signature-line">
                                    Signature over Printed Name
                                </div>
                            </div>

                            <div class="clearance-cell">
                                SALES &amp; MARKETING

                                <div style="margin-top:20px; font-weight: 600; text-transform: uppercase;">
                                    {{ $clearanceSalesMarketing }}
                                </div>

                                <div class="signature-line">
                                    Signature over Printed Name
                                </div>
                            </div>

                        </div>


                        <div class="record-row">

                            <div class="record-cell">

                                <div style="text-align:center;">
                                    <i>
                                        Record Custodian (Name and Signature)
                                    </i>
                                </div>

                                <div class="signature-line">
                                    Record Custodian
                                </div>

                            </div>

                            <div class="record-cell">

                                <div>
                                    Date Recorded:
                                    {{ now()->format('m/d/Y') }}
                                </div>

                                <div style="margin-top:20px;">
                                    Date Signed:
                                    __________________
                                </div>

                            </div>

                        </div>


                        <div class="rejection-box">

                            <div class="rejection-label">
                                REJECTION / HOLD REASON
                            </div>

                            <div class="rejection-input"></div>

                        </div>

                    </div>

                </section>

            </div>{{-- END TAB PANEL: START --}}

            {{-- =====================================================
                 TAB PANEL: FILES (DEAL FILES & CLIENT ACTION REQUESTS)
            ====================================================== --}}
            <div class="deal-tab-panel" id="tab-panel-files" style="display: none;">

                {{-- 1. DEAL FILES & DOCUMENTS --}}
                <div class="files-card">
                    <div class="files-header-row">
                        <div>
                            <h2 class="files-title">Deal Files &amp; Documents</h2>
                            <p class="files-subtitle">Client files, proposals, and signed documents for this deal.</p>
                        </div>
                    </div>

                    {{-- Deal Files Table --}}
                    <div class="files-table-wrap">
                        <table class="files-table">
                            <thead>
                                <tr>
                                    <th style="width: 38%;">DOCUMENT</th>
                                    <th style="width: 20%;">TYPE</th>
                                    <th style="width: 18%;">REFERENCE / STATUS</th>
                                    <th style="width: 11%;">UPDATED</th>
                                    <th style="width: 13%; text-align: right;">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="dealFilesTableBody">
                                <tr>
                                    <td>
                                        <div>
                                            <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;" id="dealFilesActiveProposalLabel">Proposal V1</div>
                                            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Official commercial proposal</div>
                                        </div>
                                    </td>
                                    <td><span style="font-size: 13px; color: #334155; font-weight: 500;">Commercial Proposal</span></td>
                                    <td><span class="client-action-pill client-action-pill-inprogress" id="dealFilesActiveProposalVer"><span class="pill-dot"></span><span>V1 · Current</span></span></td>
                                    <td style="font-size: 13px; color: #64748b; white-space: nowrap;">{{ $deal->proposal_date ? $deal->proposal_date->format('M d, Y') : ($deal->created_at ? $deal->created_at->format('M d, Y') : now()->format('M d, Y')) }}</td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <button type="button" class="files-action-btn-view" onclick="openProposalPdfFromFiles()">
                                            View Proposal
                                        </button>
                                    </td>
                                </tr>
                                @if(isset($deal->startRecords) && $deal->startRecords->count())
                                    @foreach($deal->startRecords as $stRecord)
                                    <tr>
                                        <td>
                                            <div>
                                                <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">START Intake &amp; Routing Tracker</div>
                                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">START activation and routing document</div>
                                            </div>
                                        </td>
                                        <td><span style="font-size: 13px; color: #334155; font-weight: 500;">Operational START</span></td>
                                        <td><span class="client-action-pill client-action-pill-completed"><span class="pill-dot"></span><span>{{ $stRecord->start_code ?: 'ST-Batch' }}</span></span></td>
                                        <td style="font-size: 13px; color: #64748b; white-space: nowrap;">{{ $stRecord->created_at ? $stRecord->created_at->format('M d, Y') : '-' }}</td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <button type="button" class="files-action-btn-view" onclick="printStartForm()">
                                                Open START
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div>
                                                <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">Service Memo {{ $stRecord->start_code ?: 'SM-Batch' }}</div>
                                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Official service activation memorandum</div>
                                            </div>
                                        </td>
                                        <td><span style="font-size: 13px; color: #334155; font-weight: 500;">Service Memo</span></td>
                                        <td><span class="client-action-pill client-action-pill-completed"><span class="pill-dot"></span><span>{{ $stRecord->status ?: 'Issued' }}</span></span></td>
                                        <td style="font-size: 13px; color: #64748b; white-space: nowrap;">{{ $stRecord->created_at ? $stRecord->created_at->format('M d, Y') : '-' }}</td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <button type="button" class="files-action-btn-view" onclick="openStartMemoViewer({{ $stRecord->id }})">
                                                View Memo
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 2. CLIENT ACTION REQUESTS --}}
                <div class="files-card">
                    <div class="files-header-row">
                        <div>
                            <h2 class="files-title">Client Action Requests</h2>
                            <p class="files-subtitle">Requests sent to the client for review, approval, signing, document submission, or confirmation.</p>
                        </div>
                    </div>

                    {{-- Client Action Table --}}
                    <div class="files-table-wrap" id="clientActionTableWrap">
                        <table class="files-table" id="clientActionTable">
                            <thead>
                                <tr>
                                    <th style="width: 28%;">REQUEST</th>
                                    <th style="width: 22%;">DOCUMENT</th>
                                    <th style="width: 20%;">DELIVERY METHOD</th>
                                    <th style="width: 12%;">STATUS</th>
                                    <th style="width: 10%;">REQUESTED</th>
                                    <th style="width: 8%; text-align: right;">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="clientActionTableBody">
                                {{-- Rendered dynamically via JS --}}
                            </tbody>
                        </table>
                    </div>

                    {{-- Empty State --}}
                    <div class="client-action-empty-wrap" id="clientActionEmptyState" style="display: none;">
                        <div class="start-empty-icon-circle" style="background: #eff6ff; color: #2563eb; margin-bottom: 12px;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                        </div>
                        <h3 class="start-empty-heading" style="font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">No client action requests yet</h3>
                        <p class="start-empty-subtext" style="font-size: 13px; color: #64748b; margin-bottom: 16px;">Requests sent to the client will appear here.</p>
                        <button type="button" class="start-btn-primary" onclick="openClientActionModal()">
                            + Request Client Action
                        </button>
                    </div>
                </div>

            </div>{{-- END TAB PANEL: FILES --}}

            {{-- =====================================================
                 TAB PANEL: ENGAGEMENT (SERVICE & ENGAGEMENT DETAILS)
            ====================================================== --}}
            <div class="deal-tab-panel" id="tab-panel-engagment" style="display: none;">

                <div class="engagement-details-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px 28px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02); margin-bottom: 20px;">
                    
                    {{-- Header --}}
                    <div style="margin-bottom: 20px;">
                        <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0; line-height: 1.3;">
                            Service and Engagement Details
                        </h2>
                    </div>

                    {{-- 2-Column Clean Key-Value Grid --}}
                    <div class="detail-grid" style="row-gap: 18px; column-gap: 40px;">

                        {{-- 1. Service Type --}}
                        <div class="detail-item">
                            <div class="detail-label">Service Type</div>
                            <div class="detail-value {{ empty($deal->service_type) ? 'muted-value' : '' }}">
                                {{ filled($deal->service_type) ? $deal->service_type : 'Not specified' }}
                            </div>
                        </div>

                        {{-- 2. Product Type --}}
                        <div class="detail-item">
                            <div class="detail-label">Product Type</div>
                            <div class="detail-value {{ empty($deal->product_type) ? 'muted-value' : '' }}">
                                {{ filled($deal->product_type) ? $deal->product_type : 'Not specified' }}
                            </div>
                        </div>

                        {{-- 3. Engagement Type --}}
                        <div class="detail-item">
                            <div class="detail-label">Engagement Type</div>
                            <div class="detail-value {{ empty($deal->engagement_type) ? 'muted-value' : '' }}">
                                {{ filled($deal->engagement_type) ? $deal->engagement_type : 'Not specified' }}
                            </div>
                        </div>

                        {{-- 4. Deal Code --}}
                        <div class="detail-item">
                            <div class="detail-label">Deal Code</div>
                            <div class="detail-value">
                                {{ filled($deal->deal_code) ? $deal->deal_code : ('CONDEAL-' . date('Y') . '-' . str_pad($deal->id, 3, '0', STR_PAD_LEFT)) }}
                            </div>
                        </div>

                        {{-- 5. Engagement Duration --}}
                        <div class="detail-item">
                            <div class="detail-label">Engagement Duration</div>
                            <div class="detail-value {{ empty($deal->estimated_duration_days) ? 'muted-value' : '' }}">
                                @if(filled($deal->estimated_duration_days))
                                    {{ is_numeric($deal->estimated_duration_days) ? $deal->estimated_duration_days . ' days' : $deal->estimated_duration_days }}
                                @else
                                    Not specified
                                @endif
                            </div>
                        </div>

                    </div>

                </div>

            </div>{{-- END TAB PANEL: ENGAGEMENT --}}

            {{-- =====================================================
                 TAB PANEL: HISTORY & TRACEABILITY
            ====================================================== --}}
            <div class="deal-tab-panel" id="tab-panel-history" style="display: none;">
                <div class="history-container-layout">
                    
                    {{-- LEFT COLUMN: NOTIFICATION FEED --}}
                    <div class="history-main-column">
                        <div class="history-card">
                            
                            {{-- Header Bar --}}
                            <div class="history-header-bar">
                                <div class="history-title-group">
                                    <h2>History &amp; Traceability</h2>
                                    <p>Recent activity and changes for this deal.</p>
                                </div>

                                <div class="history-actions-bar">
                                    <div class="history-search-wrapper">
                                        <svg class="history-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                        <input type="text" id="historySearchInput" class="history-search-input" placeholder="Search history..." oninput="handleHistorySearch(this.value)">
                                    </div>
                                    <button type="button" class="history-btn-add-note" onclick="openAddNoteModal()">
                                        <span>＋ Add Note</span>
                                    </button>
                                </div>
                            </div>

                            {{-- Scrollable Notification Feed --}}
                            <div class="history-feed-container" id="dealHistoryTimeline">
                                @forelse($deal->histories()->latestFirst()->take(20)->get() as $idx => $history)
                                    @php
                                        $isLatest = ($idx === 0);
                                    @endphp
                                    <div class="history-feed-item {{ $isLatest ? 'is-latest' : '' }}" id="histItem_{{ $history->id }}" data-type="{{ $history->activity_type }}" data-user="{{ $history->user_id }}" data-date="{{ $history->created_at ? $history->created_at->format('Y-m-d') : '' }}">
                                        <div class="history-feed-dot"></div>

                                        {{-- Top Row: Type & CURRENT pill --}}
                                        <div class="history-feed-top-row">
                                            <div class="history-feed-type">
                                                {{ $history->type_label }}
                                            </div>
                                            @if($isLatest)
                                                <span class="history-feed-curr-tag">
                                                    CURRENT / LATEST
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Body: Stage change / Document / Note snippet / Description --}}
                                        @if($history->from_stage && $history->to_stage)
                                            <div class="history-feed-transition">
                                                <span>{{ $history->from_stage }}</span>
                                                <span class="history-feed-arrow">→</span>
                                                <span style="font-weight: 700; color: #0f172a;">{{ $history->to_stage }}</span>
                                            </div>
                                        @elseif($history->activity_type === 'deal_updated' && $history->from_value && $history->to_value)
                                            <div class="history-feed-body" style="font-weight: 600;">
                                                {{ $history->field_name ?: 'Value' }}: {{ $history->from_value }} → {{ $history->to_value }}
                                            </div>
                                        @elseif($history->activity_type === 'contact_updated' && $history->from_value && $history->to_value)
                                            <div class="history-feed-body" style="font-weight: 600;">
                                                {{ $history->from_value }} → {{ $history->to_value }}
                                            </div>
                                        @elseif($history->document_name)
                                            <div class="history-feed-doc">
                                                {{ $history->document_name }}
                                            </div>
                                        @elseif($history->description)
                                            <div class="history-feed-body">
                                                {{ $history->description }}
                                            </div>
                                        @endif

                                        {{-- Footer: Timestamp, User on left, [View Details] on right --}}
                                        <div class="history-feed-meta">
                                            <div class="history-feed-subline">
                                                <span>{{ $history->created_at ? $history->created_at->format('M d, Y · h:i A') : '' }}</span>
                                                <span style="color: #cbd5e1;">•</span>
                                                <span style="font-weight: 600; color: #0f172a;">{{ $history->user_name ?: ($deal->owner_name ?: 'System') }}</span>
                                            </div>
                                            <button type="button" class="history-feed-view-btn" onclick="toggleHistoryDetails({{ $history->id }})">
                                                <span id="histToggleText_{{ $history->id }}">[View Details]</span>
                                            </button>
                                        </div>

                                        {{-- Collapsible Details Box --}}
                                        <div class="history-expanded-box" id="histDetails_{{ $history->id }}" style="display: none;">
                                            <div class="history-expanded-grid">
                                                @if($history->from_stage || $history->to_stage)
                                                    <div>
                                                        <div class="history-expanded-cell-label">Previous Stage</div>
                                                        <div class="history-expanded-cell-val">{{ $history->from_stage ?: 'None' }}</div>
                                                    </div>
                                                    <div>
                                                        <div class="history-expanded-cell-label">New Stage</div>
                                                        <div class="history-expanded-cell-val" style="color: #0f172a; font-weight: 700;">{{ $history->to_stage ?: 'None' }}</div>
                                                    </div>
                                                @endif

                                                @if($history->old_values && $history->new_values && is_array($history->old_values))
                                                    @foreach($history->new_values as $fKey => $newV)
                                                        @php
                                                            $oldV = $history->old_values[$fKey] ?? 'None';
                                                            $labelF = ucwords(str_replace('_', ' ', $fKey));
                                                        @endphp
                                                        <div>
                                                            <div class="history-expanded-cell-label">{{ $labelF }} (Old)</div>
                                                            <div class="history-expanded-cell-val" style="color: #b91c1c;">{{ is_array($oldV) ? json_encode($oldV) : ($oldV ?: 'None') }}</div>
                                                        </div>
                                                        <div>
                                                            <div class="history-expanded-cell-label">{{ $labelF }} (New)</div>
                                                            <div class="history-expanded-cell-val" style="color: #15803d;">{{ is_array($newV) ? json_encode($newV) : ($newV ?: 'None') }}</div>
                                                        </div>
                                                    @endforeach
                                                @endif

                                                @if($history->notes)
                                                    <div style="grid-column: 1 / -1;">
                                                        <div class="history-expanded-cell-label">Full Note</div>
                                                        <div class="history-expanded-cell-val" style="font-style: italic;">"{{ $history->notes }}"</div>
                                                    </div>
                                                @endif

                                                <div>
                                                    <div class="history-expanded-cell-label">Action By</div>
                                                    <div class="history-expanded-cell-val">{{ $history->user_name ?: ($deal->owner_name ?: 'System') }}</div>
                                                </div>
                                                <div>
                                                    <div class="history-expanded-cell-label">Timestamp</div>
                                                    <div class="history-expanded-cell-val">{{ $history->created_at ? $history->created_at->format('M d, Y · h:i:s A') : '-' }}</div>
                                                </div>
                                                <div>
                                                    <div class="history-expanded-cell-label">IP Address</div>
                                                    <div class="history-expanded-cell-val">{{ $history->ip_address ?: '127.0.0.1' }}</div>
                                                </div>
                                                <div>
                                                    <div class="history-expanded-cell-label">Trace Ref</div>
                                                    <div class="history-expanded-cell-val">#{{ str_pad($history->id, 5, '0', STR_PAD_LEFT) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div id="historyEmptyState" style="text-align: center; padding: 36px 20px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
                                        <h3 style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin: 0 0 4px;">No activity yet</h3>
                                        <p style="font-size: 12px; color: #64748b; margin: 0 0 14px;">There are no recorded activities for this Deal.</p>
                                        <button type="button" class="history-btn-add-note" onclick="openAddNoteModal()">
                                            + Add Note
                                        </button>
                                    </div>
                                @endforelse
                            </div>

                            {{-- Filter Empty State fallback --}}
                            <div id="historyFilterEmptyState" style="display: none; text-align: center; padding: 28px 20px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; margin-top: 10px;">
                                <h3 style="font-size: 13px; font-weight: 700; color: #0f172a; margin: 0 0 4px;">No matching activities found</h3>
                                <p style="font-size: 11.5px; color: #64748b; margin: 0 0 10px;">Try adjusting your filter settings or search keywords.</p>
                                <button type="button" class="history-btn-reset" onclick="resetHistoryFilters()">
                                    Reset Filters
                                </button>
                            </div>

                            {{-- Load More wrap --}}
                            <div class="history-load-more-wrap" id="historyLoadMoreWrap" style="{{ ($deal->histories()->count() > 20) ? '' : 'display: none;' }}">
                                <button type="button" class="history-btn-load-more" id="historyLoadMoreBtn" onclick="loadMoreHistories()">
                                    Load More Activities
                                </button>
                            </div>

                        </div>
                    </div>

                    {{-- RIGHT SIDEBAR: FILTERS, COMPACT STAGE TRACKER & TOTAL ACTIVITY --}}
                    <div class="history-sidebar-column">
                        
                        {{-- 1. Filter Card --}}
                        <div class="history-sidebar-card">
                            <h3 class="history-sidebar-heading">
                                <span>Filter History</span>
                            </h3>

                            <form id="historyFilterForm" onsubmit="handleApplyHistoryFilters(event)">
                                {{-- Date Range Quick Select --}}
                                <div class="history-filter-group">
                                    <label class="history-filter-label">Date Range</label>
                                    <select id="histFilterDateRange" class="history-filter-select" onchange="toggleCustomDateInputs(this.value)">
                                        <option value="all">All Time</option>
                                        <option value="today">Today</option>
                                        <option value="yesterday">Yesterday</option>
                                        <option value="this_week">This Week</option>
                                        <option value="this_month">This Month</option>
                                        <option value="custom">Custom Date Range</option>
                                    </select>
                                </div>

                                {{-- Custom Date Inputs --}}
                                <div id="histCustomDateGroup" style="display: none; margin-bottom: 10px;">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                                        <div>
                                            <label style="font-size: 9.5px; color: #64748b; text-transform: uppercase;">From</label>
                                            <input type="date" id="histFilterStartDate" class="history-filter-date">
                                        </div>
                                        <div>
                                            <label style="font-size: 9.5px; color: #64748b; text-transform: uppercase;">To</label>
                                            <input type="date" id="histFilterEndDate" class="history-filter-date">
                                        </div>
                                    </div>
                                </div>

                                {{-- Activity Type --}}
                                <div class="history-filter-group">
                                    <label class="history-filter-label">Activity Type</label>
                                    <select id="histFilterActivityType" class="history-filter-select">
                                        <option value="all">All Activities</option>
                                        <option value="deal_created">Deal Created</option>
                                        <option value="stage_changed">Stage Changed</option>
                                        <option value="deal_updated">Deal Updated</option>
                                        <option value="contact_updated">Contact Updated</option>
                                        <option value="note_added">Note Added</option>
                                        <option value="document_uploaded">Document Uploaded</option>
                                        <option value="document_deleted">Document Deleted</option>
                                        <option value="proposal_generated">Proposal Generated</option>
                                        <option value="assignment_changed">Assignment Changed</option>
                                    </select>
                                </div>

                                {{-- User / Actor --}}
                                <div class="history-filter-group">
                                    <label class="history-filter-label">User</label>
                                    <select id="histFilterUser" class="history-filter-select">
                                        <option value="all">All Users</option>
                                        @foreach($users as $u)
                                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="history-filter-btn-group">
                                    <button type="submit" class="history-btn-apply">Apply Filter</button>
                                    <button type="button" class="history-btn-reset" onclick="resetHistoryFilters()">Reset</button>
                                </div>
                            </form>
                        </div>


                        {{-- 3. Total Activity Summary Card (2x2 Grid) --}}
                        <div class="history-sidebar-card">
                            <h3 class="history-sidebar-heading">
                                <span>Total Activity</span>
                            </h3>

                            <div class="history-stat-grid">
                                <div class="history-stat-box">
                                    <div class="history-stat-count" id="statTotalCount">{{ $activityCounts['total'] ?? $deal->histories()->count() }}</div>
                                    <div class="history-stat-label">Activities</div>
                                </div>
                                <div class="history-stat-box">
                                    <div class="history-stat-count" id="statStageChanges">{{ $activityCounts['stage_changes'] ?? 0 }}</div>
                                    <div class="history-stat-label">Stage Changes</div>
                                </div>
                                <div class="history-stat-box">
                                    <div class="history-stat-count" id="statNotesCount">{{ $activityCounts['notes'] ?? 0 }}</div>
                                    <div class="history-stat-label">Notes</div>
                                </div>
                                <div class="history-stat-box">
                                    <div class="history-stat-count" id="statDocumentsCount">{{ ($activityCounts['documents'] ?? 0) + ($activityCounts['proposals'] ?? 0) }}</div>
                                    <div class="history-stat-label">Files</div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>{{-- END TAB PANEL: HISTORY & TRACEABILITY --}}

        </main>


        {{-- =====================================================
             RIGHT SIDEBAR
        ====================================================== --}}

        <aside>


            {{-- =================================================
                 QUICK ACTIONS (OVERVIEW TAB)
            ================================================== --}}
            <div id="sidebar-qa-overview" class="sidebar-qa-panel">
                <div class="quick-actions">

                    <h2 class="quick-actions-title">
                        Quick Actions
                    </h2>

                    @php
                        $currentStage = $deal->pipeline_stage ?? 'Inquiry';
                        $continueLabel = match($currentStage) {
                            'Inquiry' => 'Continue Inquiry',
                            'Qualification' => 'Continue Qualification',
                            'Consultation' => 'Continue Consultation',
                            'Proposal' => 'Continue Proposal',
                            'Negotiation' => 'Continue Negotiation',
                            'Payment' => 'Continue Payment',
                            'Activation' => 'Continue Activation',
                            'Closed Won' => 'Deal Completed',
                            'Closed Lost' => 'Deal Closed',
                            default => 'Continue ' . $currentStage,
                        };
                        $hasProposal = (bool) ($deal->proposal || !empty($deal->proposal_decision) || !empty($deal->proposal_title) || in_array($currentStage, ['Proposal', 'Negotiation', 'Payment', 'Activation', 'Closed Won', 'Closed Lost']));
                        $isProposalReadyForReview = $hasProposal && in_array($currentStage, ['Proposal', 'Negotiation', 'Payment', 'Activation', 'Closed Won']);
                    @endphp

                    {{-- CARD 1: DEAL ACTIONS --}}
                    <div class="quick-group">
                        <div class="quick-group-title">
                            Deal Actions
                        </div>

                        {{-- Dynamic Continue Action --}}
                        <button type="button"
                                class="quick-action"
                                id="btnContinueCurrentStage"
                                data-tooltip="Resume the deal from its current stage and continue the next required step."
                                style="border: 1px solid #2563eb; color: #2563eb; background: #ffffff; font-weight: 700;"
                                onclick="handleDynamicContinueAction('{{ $currentStage }}')">
                            <span class="quick-icon" style="color: #2563eb;">
                                @if($currentStage === 'Closed Won')
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                @elseif($currentStage === 'Closed Lost')
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                                @else
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                @endif
                            </span>
                            {{ $continueLabel }}
                        </button>

                        {{-- Edit Deal --}}
                        <button type="button"
                                class="quick-action"
                                data-tooltip="Update the deal information that is still editable."
                                onclick="openDealDrawer('edit', {{ $deal->id }})">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </span>
                            Edit Deal
                        </button>

                        {{-- Add Note --}}
                        <button type="button"
                                class="quick-action"
                                data-tooltip="Add a private or team note to this deal record."
                                onclick="openAddNoteModal()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                            </span>
                            Add Note
                        </button>

                        {{-- Upload File --}}
                        <button type="button"
                                class="quick-action"
                                data-tooltip="Upload attachments or documents related to this deal."
                                onclick="openUploadFileModal()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            </span>
                            Upload File
                        </button>

                        {{-- View History --}}
                        <button type="button"
                                class="quick-action"
                                data-tooltip="View previous transactions, engagements, and relevant deal activity."
                                style="margin-bottom: 0;"
                                onclick="switchDealNavTab('history')">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            </span>
                            View History
                        </button>
                    </div>

                    {{-- CARD 2: WORKSPACES --}}
                    <div class="quick-group">
                        <div class="quick-group-title">
                            Workspaces
                        </div>

                        @php
                            $rawEngagementType = trim((string) ($deal->engagement_type ?? ''));
                            $normType = strtolower($rawEngagementType);

                            $hasProject = false;
                            $hasRegular = false;

                            if ($normType === 'project engagement' || $normType === 'project') {
                                $hasProject = true;
                            } elseif ($normType === 'regular retainer' || $normType === 'regular (retainer) engagement' || $normType === 'regular engagement' || $normType === 'regular' || $normType === 'retainer') {
                                $hasRegular = true;
                            } elseif ($normType === 'hybrid' || $normType === 'hybrid engagement') {
                                $hasProject = true;
                                $hasRegular = true;
                            } elseif (!empty($normType)) {
                                if (str_contains($normType, 'hybrid')) {
                                    $hasProject = true;
                                    $hasRegular = true;
                                } elseif (str_contains($normType, 'regular') || str_contains($normType, 'retainer')) {
                                    $hasRegular = true;
                                } elseif (str_contains($normType, 'project')) {
                                    $hasProject = true;
                                }
                            }
                        @endphp

                        @if(!$hasProject && !$hasRegular)
                            <div class="op-workspace-empty" style="padding: 10px; font-size: 11px; color: #64748b; text-align: center;">
                                Engagement type has not been defined.
                            </div>
                        @else
                            @if($hasRegular)
                                <a href="{{ route('deals.regular', $deal->id) }}" class="quick-action" data-tooltip="Open the workspace for managing the ongoing recurring engagement." style="{{ ($hasProject && $hasRegular) ? 'margin-bottom: 7px;' : 'margin-bottom: 0;' }}">
                                    <span class="quick-icon">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                             <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                                        </svg>
                                    </span>
                                    Open Regular Retainer Workspace
                                </a>
                            @endif

                            @if($hasProject)
                                <a href="{{ route('deals.project', $deal->id) }}" class="quick-action" data-tooltip="Open the workspace for managing this project's scope and delivery." style="margin-bottom: 0;">
                                    <span class="quick-icon">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                                            <polyline points="2 17 12 22 22 17"></polyline>
                                            <polyline points="2 12 12 17 22 12"></polyline>
                                        </svg>
                                    </span>
                                    Open Project Workspace
                                </a>
                            @endif
                        @endif
                    </div>

                    {{-- CARD 3: EXPORTS / PROPOSAL --}}
                    @if($hasProposal)
                    <div class="quick-group">
                        <div class="quick-group-title">
                            Exports
                        </div>

                        {{-- View Proposal --}}
                        <button type="button"
                                class="quick-action"
                                data-tooltip="Open the current proposal and its available revisions."
                                onclick="switchDealNavTab('proposal')">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                            </span>
                            View Proposal
                        </button>

                        {{-- Client Review (Conditional) --}}
                        @if($isProposalReadyForReview)
                            <button type="button"
                                    class="quick-action"
                                    data-tooltip="Open the client-facing proposal review and approval experience."
                                    onclick="openClientReviewPortalModal()">
                                <span class="quick-icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </span>
                                Client Review
                            </button>
                        @endif

                        {{-- Download PDF --}}
                        <button type="button"
                                class="quick-action"
                                data-tooltip="Download the proposal document as a PDF."
                                style="margin-bottom: 0;"
                                onclick="printProposalPDF()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                            </span>
                            Download PDF
                        </button>
                    </div>
                    @endif

                </div>
            </div>

            {{-- =================================================
                 QUICK ACTIONS (INQUIRY TAB)
            ================================================== --}}
            <div id="sidebar-qa-inquiry" class="sidebar-qa-panel" style="display: none;">
                <div class="quick-actions">

                    <h2 class="quick-actions-title">
                        Quick Actions
                    </h2>

                    <div class="quick-group">

                        <div class="quick-group-title">
                            Inquiry Actions
                        </div>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="Record a new client inquiry or requirement."
                                onclick="openAddInquiryModal()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </span>
                            Add Inquiry
                        </button>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="Add a private or team note to this deal record."
                                onclick="openAddNoteModal()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                            </span>
                            Add Note
                        </button>

                        @php
                            $isInquiryCompleted = $stageIndex > 0 || in_array($deal->pipeline_stage, ['Qualification', 'Consultation', 'Proposal', 'Negotiation', 'Payment', 'Activation', 'Closed Won', 'Closed Lost']);
                        @endphp
                        <button type="button"
                                class="quick-action"
                                id="btnDoneInquiry"
                                data-tooltip="Complete the inquiry review and advance to qualification."
                                style="margin-top: 6px; border: 1px solid #2563eb; color: #2563eb; background: #ffffff; font-weight: 700; {{ $isInquiryCompleted ? 'opacity: 0.85;' : '' }}"
                                onclick="handleDoneInquiry()">
                            <span class="quick-icon" style="color: #2563eb;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                            Done Inquiry
                        </button>

                    </div>

                </div>
            </div>

            {{-- =================================================
                 QUICK ACTIONS (CONSULTATION TAB)
            ================================================== --}}
            <div id="sidebar-qa-consultation" class="sidebar-qa-panel" style="display: none;">
                <div class="quick-actions">

                    <h2 class="quick-actions-title">
                        Quick Actions
                    </h2>

                    <div class="quick-group">

                        <div class="quick-group-title">
                            Consultation Actions
                        </div>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="Schedule or record a new consultation meeting."
                                onclick="openAddConsultationModal()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </span>
                            New Consultation
                        </button>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="Upload consultation notes or documents."
                                onclick="openUploadFileModal()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            </span>
                            Upload File
                        </button>

                        @php
                            $isQualCompleted = $stageIndex > 1 || in_array($deal->pipeline_stage, ['Consultation', 'Proposal', 'Negotiation', 'Payment', 'Activation', 'Closed Won', 'Closed Lost']);
                        @endphp
                        <button type="button"
                                class="quick-action"
                                id="btnClientQualifiedConsultation"
                                data-tooltip="Confirm whether the client, need, and opportunity meet the applicable qualification requirements."
                                style="margin-top: 6px; border: 1px solid #2563eb; color: #2563eb; background: #ffffff; font-weight: 700; {{ $isQualCompleted ? 'opacity: 0.85;' : '' }}"
                                onclick="handleClientQualified()">
                            <span class="quick-icon" style="color: #2563eb;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                            Client is Qualified
                        </button>

                    </div>

                </div>
            </div>

            {{-- =================================================
                 QUICK ACTIONS (SERVICES & PRICING TAB)
            ================================================== --}}
            <div id="sidebar-qa-services-pricing" class="sidebar-qa-panel" style="display: none;">
                <div class="quick-actions">

                    <h2 class="quick-actions-title">
                        Quick Actions
                    </h2>

                    <div class="quick-group">

                        <div class="quick-group-title">
                            Pricing Actions
                        </div>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="Add another service to the client's current or new transaction."
                                onclick="openAddLineItemModal()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </span>
                            Add Line Item
                        </button>

                        @php
                            $isProposalCompleted = $stageIndex > 2 || in_array($deal->pipeline_stage, ['Proposal', 'Negotiation', 'Payment', 'Activation', 'Closed Won', 'Closed Lost']);
                        @endphp
                        <button type="button"
                                class="quick-action"
                                id="btnCreateProposalServicesPricing"
                                data-tooltip="Create a proposal for the selected services and commercial terms."
                                style="margin-top: 6px; border: 1px solid #2563eb; color: #2563eb; background: #ffffff; font-weight: 700; {{ $isProposalCompleted ? 'opacity: 0.85;' : '' }}"
                                onclick="handleCreateProposalFromPricing()">
                            <span class="quick-icon" style="color: #2563eb;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            </span>
                            Create Proposal
                        </button>

                    </div>

                </div>
            </div>

            {{-- =================================================
                 QUICK ACTIONS (PROPOSAL TAB)
            ================================================== --}}
            <div id="sidebar-qa-proposal" class="sidebar-qa-panel" style="display: none;">
                <div class="quick-actions">

                    <h2 class="quick-actions-title">
                        Quick Actions
                    </h2>

                    <div class="quick-group">

                        <div class="quick-group-title">
                            Proposal Actions
                        </div>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="Open the proposal editor to adjust terms and line items."
                                onclick="openProposalWorkspace()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            </span>
                            Open Proposal Form
                        </button>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="Open the client-facing proposal review and approval experience."
                                onclick="openClientReviewPortalModal()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </span>
                            Proposal Preview
                        </button>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="Send an action request to the client or team member."
                                onclick="openDispatchActionRequestModal()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"></path><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"></path><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"></path><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"></path></svg>
                            </span>
                            Dispatch Action Request
                        </button>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="Record or update the client's decision on this proposal."
                                onclick="openUpdateDecisionModal()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                            </span>
                            Update Decision
                        </button>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="Send the proposal directly to the client."
                                onclick="openSendProposalModal()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                            </span>
                            Send Proposal
                        </button>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="Download the proposal document as a PDF."
                                onclick="printProposalPDF()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            </span>
                            Download PDF
                        </button>

                        @php
                            $isNegotiationReached = $stageIndex > 3 || in_array($deal->pipeline_stage, ['Negotiation', 'Payment', 'Activation', 'Closed Won']);
                        @endphp
                        <button type="button"
                                class="quick-action"
                                id="btnProposalApproved"
                                data-tooltip="Approve the current proposal according to the applicable approval rules."
                                style="margin-top: 6px; border: 1px solid #2563eb; color: #2563eb; background: #ffffff; font-weight: 700; {{ $isNegotiationReached ? 'opacity: 0.85;' : '' }}"
                                onclick="handleProposalApproved()">
                            <span class="quick-icon" style="color: #2563eb;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                            Proposal Approved
                        </button>

                    </div>

                </div>
            </div>

            {{-- =================================================
                 QUICK ACTIONS (FINANCE TAB)
            ================================================== --}}
            <div id="sidebar-qa-finance" class="sidebar-qa-panel" style="display: none;">
                <div class="quick-actions">

                    <h2 class="quick-actions-title">
                        Quick Actions
                    </h2>

                    <div class="quick-group">

                        <div class="quick-group-title">
                            Finance Actions
                        </div>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="View or continue the payment workflow associated with this transaction."
                                onclick="openRecordPaymentModal({scope: 'entire'})">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                            </span>
                            Record Payment Allocation
                        </button>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="Preview the quotation with commercial terms."
                                onclick="openPreviewQuotationModal()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            </span>
                            Preview Quotation
                        </button>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="View the uploaded payment receipt / proof file directly."
                                onclick="openFinanceAttachmentViewer(0)">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            </span>
                            View Uploaded Proof / Receipt
                        </button>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="Preview the payment notice for the client."
                                onclick="openPreviewPaymentNoticeModal()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line><line x1="6" y1="14" x2="10" y2="14"></line></svg>
                            </span>
                            Preview Payment Notice
                        </button>


                        @php
                            $isNegotiationDone = $stageIndex > 4 || in_array($deal->pipeline_stage, ['Payment', 'Activation', 'Closed Won']);
                            $isPaymentDone = $stageIndex > 5 || in_array($deal->pipeline_stage, ['Activation', 'Closed Won']);
                        @endphp

                        {{-- 1. Done Negotiation Button (First Action) --}}
                        <button type="button"
                                class="quick-action"
                                id="btnDoneNegotiation"
                                data-tooltip="Complete the commercial negotiation and finalize deal terms."
                                style="margin-top: 6px; border: 1px solid #2563eb; color: #2563eb; background: #ffffff; font-weight: 700; {{ $isNegotiationDone ? 'opacity: 0.85;' : '' }}"
                                onclick="handleDoneNegotiation()">
                            <span class="quick-icon" style="color: #2563eb;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                            {{ $isNegotiationDone ? 'Done Negotiation' : 'Done Negotiation' }}
                        </button>

                        {{-- 2. Received Payment Button (Second Action) --}}
                        <button type="button"
                                class="quick-action"
                                id="btnReceivedPayment"
                                data-tooltip="Record that payment has been received and verified."
                                style="margin-top: 6px; border: 1px solid #059669; color: #059669; background: #ffffff; font-weight: 700; {{ $isPaymentDone ? 'opacity: 0.85;' : '' }}"
                                onclick="handleReceivedPayment()">
                            <span class="quick-icon" style="color: #059669;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                            </span>
                            Received Payment
                        </button>

                        {{-- 3. Ready to START Button (Active after Payment) --}}
                        <button type="button"
                                class="quick-action"
                                id="btnReadyToStart"
                                data-tooltip="Open the START activation workflow for the selected engagement."
                                style="margin-top: 6px; border: 1px solid #2563eb; color: #2563eb; background: #ffffff; font-weight: 700; {{ $isPaymentDone ? '' : 'display: none;' }}"
                                onclick="handleReadyToStart()">
                            <span class="quick-icon" style="color: #2563eb;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"></path><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"></path><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"></path><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"></path></svg>
                            </span>
                            Ready to START
                        </button>

                    </div>

                </div>
            </div>

            {{-- =================================================
                 QUICK ACTIONS (START TAB)
            ================================================== --}}
            <div id="sidebar-qa-start" class="sidebar-qa-panel" style="display: none;">
                <div class="quick-actions">

                    <h2 class="quick-actions-title">
                        Quick Actions
                    </h2>

                    <div class="quick-group">

                        <div class="quick-group-title">
                            START Actions
                        </div>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="Create a progressive start batch for service delivery."
                                onclick="openCreateStartBatchModal()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </span>
                            Create Progressive Start
                        </button>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="Update the START activation settings and assignments."
                                onclick="openActiveBatchDetails()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </span>
                            Edit START
                        </button>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="Download the START record as a PDF."
                                onclick="downloadStartPdf()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            </span>
                            Download START PDF
                        </button>



                        <button type="button"
                                class="quick-action"
                                data-tooltip="Open the Service Memo associated with the activated service."
                                onclick="openActiveBatchServiceMemo()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            </span>
                            View Service Memo
                        </button>

                    </div>

                </div>
            </div>

            {{-- =================================================
                 QUICK ACTIONS (FILES TAB)
            ================================================== --}}
            <div id="sidebar-qa-files" class="sidebar-qa-panel" style="display: none;">
                <div class="quick-actions">

                    <h2 class="quick-actions-title">
                        Quick Actions
                    </h2>

                    <div class="quick-group">

                        <div class="quick-group-title">
                            Client Action
                        </div>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="Send a formal request for information or documents to the client."
                                onclick="openCreateClientActionModal ? openCreateClientActionModal() : openClientActionModal()">
                            <span class="quick-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </span>
                            Request Client Action
                        </button>

                    </div>

                </div>
            </div>





            {{-- =================================================
                 QUICK ACTIONS (ENGAGEMENT TAB)
            ================================================== --}}
            <div id="sidebar-qa-engagment" class="sidebar-qa-panel" style="display: none;">
                <div class="quick-actions">

                    <h2 class="quick-actions-title">
                        Operational Workspace
                    </h2>

                    <div class="quick-group">
                        <div class="quick-group-title">
                            Workspaces
                        </div>

                        @if(!$hasProject && !$hasRegular)
                            <div class="op-workspace-empty" style="padding: 10px; font-size: 11px; color: #64748b; text-align: center;">
                                Engagement type has not been defined.
                            </div>
                        @else
                            @if($hasRegular)
                                <a href="{{ route('deals.regular', $deal->id) }}" class="quick-action" data-tooltip="Open the workspace for managing the ongoing recurring engagement." style="{{ ($hasProject && $hasRegular) ? 'margin-bottom: 7px;' : 'margin-bottom: 0;' }}">
                                    <span class="quick-icon">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                             <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                                        </svg>
                                    </span>
                                    Open Regular Retainer Workspace
                                </a>
                            @endif

                            @if($hasProject)
                                <a href="{{ route('deals.project', $deal->id) }}" class="quick-action" data-tooltip="Open the workspace for managing this project's scope and delivery." style="margin-bottom: 0;">
                                    <span class="quick-icon">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                                            <polyline points="2 17 12 22 22 17"></polyline>
                                            <polyline points="2 12 12 17 22 12"></polyline>
                                        </svg>
                                    </span>
                                    Open Project Workspace
                                </a>
                            @endif
                        @endif
                    </div>

                </div>
            </div>

            {{-- =================================================
                 RELATED CONTACT
            ================================================== --}}

            <div class="related-card">

                <h2>
                    Related Contact
                </h2>

                <div class="contact-card">

                    <div class="contact-avatar">

                        @php
                            $initials = collect(
                                preg_split('/\s+/', trim($contactName))
                            )
                            ->filter()
                            ->take(2)
                            ->map(fn ($name) => strtoupper(substr($name, 0, 1)))
                            ->implode('');
                        @endphp

                        {{ $initials ?: 'C' }}

                    </div>


                    <div>

                        <div class="contact-name">
                            {{ $contactName }}
                        </div>

                        <div class="contact-position">
                            {{ $display($deal->position) }}
                        </div>

                        <div class="contact-line">
                            ✉ {{ $display($deal->email) }}
                        </div>

                        <div class="contact-line">
                            ☎ {{ $display($deal->mobile_number) }}
                        </div>

                    </div>

                </div>

            </div>



        </aside>

    </div>

</div>



{{-- MODAL: ADD NOTE --}}
<div id="inquiryAddNoteModal" class="inquiry-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeAddNoteModal()">
    <div class="inquiry-modal-card confirm-dialog">
        <div class="inquiry-modal-head">
            <h3 class="inquiry-modal-heading">Add Note</h3>
            <button type="button" class="inquiry-modal-close-btn" onclick="closeAddNoteModal()">&times;</button>
        </div>
        <form id="inquiryAddNoteForm" onsubmit="handleSaveNote(event)">
            <div class="inquiry-modal-body-content">
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Note <span class="req-star">*</span></label>
                    <textarea id="inquiryNoteContent" class="inquiry-form-textarea" rows="4" placeholder="Enter note about this inquiry or deal..." required></textarea>
                </div>
            </div>
            <div class="inquiry-modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeAddNoteModal()">Cancel</button>
                <button type="submit" class="btn-modal-save">Save Note</button>
            </div>
        </form>
    </div>
</div>

{{-- ANCHORED CONTEXT MENU (⋮) --}}
<div id="inquiryContextMenu" class="inquiry-context-menu" style="display: none;">
    <button type="button" class="inquiry-context-menu-item" onclick="handleContextMenuAction('view')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
        View
    </button>
    <button type="button" class="inquiry-context-menu-item danger" onclick="handleContextMenuAction('delete')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
        Delete
    </button>
</div>

{{-- MODAL: ADD / EDIT INQUIRY --}}
<div id="inquiryFormModal" class="inquiry-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeInquiryFormModal()">
    <div class="inquiry-modal-card">
        <div class="inquiry-modal-head">
            <h3 id="inquiryFormModalTitle" class="inquiry-modal-heading">Add Inquiry</h3>
            <button type="button" class="inquiry-modal-close-btn" onclick="closeInquiryFormModal()">&times;</button>
        </div>
        <form id="inquiryForm" onsubmit="handleSaveInquiry(event)">
            <input type="hidden" id="inquiryFormId" value="">
            <div class="inquiry-modal-body-content">
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Inquiry Subject <span class="req-star">*</span></label>
                    <input type="text" id="inquiryFormSubject" class="inquiry-form-input" placeholder="e.g. Website Development" required>
                </div>

                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Inquiry Type <span class="req-star">*</span></label>
                    <select id="inquiryFormType" class="inquiry-form-select" required>
                        <option value="" disabled selected>Select inquiry type</option>
                        <option value="Product">Product</option>
                        <option value="Service">Service</option>
                    </select>
                </div>

                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Client Inquiry <span class="req-star">*</span></label>
                    <textarea id="inquiryFormClientInquiry" class="inquiry-form-textarea" rows="4" placeholder="Enter the client's inquiry or request..." required></textarea>
                </div>

                <div class="inquiry-modal-grid-2">
                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Expected Budget</label>
                        <input type="text" id="inquiryFormBudget" class="inquiry-form-input" placeholder="e.g. ₱150,000">
                    </div>

                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Target Date</label>
                        <input type="date" id="inquiryFormTargetDate" class="inquiry-form-input">
                    </div>
                </div>

                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Additional Notes</label>
                    <textarea id="inquiryFormNotes" class="inquiry-form-textarea" rows="3" placeholder="Enter any additional information..."></textarea>
                </div>
            </div>

            <div class="inquiry-modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeInquiryFormModal()">Cancel</button>
                <button type="submit" id="inquiryFormSubmitBtn" class="btn-modal-save">Save Inquiry</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: VIEW INQUIRY --}}
<div id="inquiryViewModal" class="inquiry-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeInquiryViewModal()">
    <div class="inquiry-modal-card">
        <div class="inquiry-modal-head">
            <h3 class="inquiry-modal-heading">Inquiry Details</h3>
            <button type="button" class="inquiry-modal-close-btn" onclick="closeInquiryViewModal()">&times;</button>
        </div>
        <div class="inquiry-modal-body-content">
            <div class="inquiry-view-group">
                <div class="inquiry-view-label">Inquiry Subject</div>
                <div id="viewInquirySubject" class="inquiry-view-value highlight"></div>
            </div>

            <div class="inquiry-view-group">
                <div class="inquiry-view-label">Inquiry Type</div>
                <div id="viewInquiryType" class="inquiry-view-value"></div>
            </div>

            <div class="inquiry-view-group">
                <div class="inquiry-view-label">Client Inquiry</div>
                <div id="viewInquiryClientInquiry" class="inquiry-view-value text-block"></div>
            </div>

            <div class="inquiry-modal-grid-2">
                <div class="inquiry-view-group">
                    <div class="inquiry-view-label">Expected Budget</div>
                    <div id="viewInquiryBudget" class="inquiry-view-value"></div>
                </div>

                <div class="inquiry-view-group">
                    <div class="inquiry-view-label">Target Date</div>
                    <div id="viewInquiryTargetDate" class="inquiry-view-value"></div>
                </div>
            </div>

            <div class="inquiry-view-group">
                <div class="inquiry-view-label">Additional Notes</div>
                <div id="viewInquiryNotes" class="inquiry-view-value text-block"></div>
            </div>

            <div class="inquiry-modal-grid-2">
                <div class="inquiry-view-group">
                    <div class="inquiry-view-label">Added By</div>
                    <div id="viewInquiryCreatedBy" class="inquiry-view-value"></div>
                </div>

                <div class="inquiry-view-group">
                    <div class="inquiry-view-label">Created</div>
                    <div id="viewInquiryCreatedAt" class="inquiry-view-value"></div>
                </div>
            </div>
        </div>

        <div class="inquiry-modal-foot">
            <button type="button" class="btn-modal-cancel" onclick="closeInquiryViewModal()">Close</button>
        </div>
    </div>
</div>

{{-- MODAL: DELETE INQUIRY CONFIRMATION --}}
<div id="inquiryDeleteModal" class="inquiry-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeInquiryDeleteModal()">
    <div class="inquiry-modal-card confirm-dialog">
        <div class="inquiry-modal-head">
            <h3 class="inquiry-modal-heading">Delete Inquiry</h3>
            <button type="button" class="inquiry-modal-close-btn" onclick="closeInquiryDeleteModal()">&times;</button>
        </div>
        <div class="inquiry-modal-body-content">
            <p style="margin: 0; color: #475569; font-size: 14px; line-height: 1.5;">
                Are you sure you want to delete this inquiry record? This action cannot be undone.
            </p>
        </div>
        <div class="inquiry-modal-foot">
            <button type="button" class="btn-modal-cancel" onclick="closeInquiryDeleteModal()">Cancel</button>
            <button type="button" class="btn-modal-danger" onclick="handleConfirmDelete()">Delete</button>
        </div>
    </div>
</div>

{{-- =====================================================
     CONSULTATION MODALS & CONTEXT MENUS
====================================================== --}}

{{-- ANCHORED CONTEXT MENU FOR CONSULTATION (⋮) --}}
<div id="consultationContextMenu" class="inquiry-context-menu" style="display: none;">
    <button type="button" class="inquiry-context-menu-item" onclick="handleConsultationMenuAction('view')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
        View
    </button>
    <button type="button" class="inquiry-context-menu-item danger" onclick="handleConsultationMenuAction('delete')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
        Delete
    </button>
</div>

{{-- ANCHORED CONTEXT MENU FOR ACTION REQUESTS (⋮) --}}
<div id="ucaContextMenu" class="inquiry-context-menu" style="display: none;">
    <button type="button" class="inquiry-context-menu-item" onclick="handleUcaContextMenuAction('view')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
        View
    </button>
    <button type="button" class="inquiry-context-menu-item danger" onclick="handleUcaContextMenuAction('delete')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
        Delete
    </button>
</div>

{{-- MODAL: NEW CONSULTATION --}}
<div id="consultationFormModal" class="inquiry-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeConsultationModal()">
    <div class="inquiry-modal-card">
        <div class="inquiry-modal-head">
            <h3 id="consultationModalTitle" class="inquiry-modal-heading">NEW CONSULTATION</h3>
            <button type="button" class="inquiry-modal-close-btn" onclick="closeConsultationModal()">&times;</button>
        </div>
        <form id="consultationForm" onsubmit="handleSaveConsultation(event)">
            <input type="hidden" id="consultationFormId" value="">
            <div class="inquiry-modal-body-content">
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Consultation Title <span class="req-star">*</span></label>
                    <input type="text" id="consultationTitleInput" class="inquiry-form-input" placeholder="e.g. Website Development Consultation" required>
                </div>

                <div class="inquiry-modal-grid-2">
                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Consultation Date <span class="req-star">*</span></label>
                        <input type="date" id="consultationDateInput" class="inquiry-form-input" required>
                    </div>

                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Consultant</label>
                        <select id="consultationConsultantSelect" class="inquiry-form-select">
                            @if(isset($users) && count($users))
                                @foreach($users as $user)
                                    <option value="{{ $user->name }}" @selected($user->name === ($deal->lead_consultant ?: ($deal->owner_name ?: (auth()->user()?->name ?? ''))))>{{ $user->name }}</option>
                                @endforeach
                            @elseif(auth()->check() && auth()->user())
                                <option value="{{ auth()->user()->name }}" selected>{{ auth()->user()->name }}</option>
                            @endif
                        </select>
                    </div>
                </div>

                <div class="inquiry-modal-grid-2">
                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Assigned Associate</label>
                        <select id="consultationAssociateSelect" class="inquiry-form-select">
                            <option value="">-- None / Select Associate --</option>
                            @if(isset($users) && count($users))
                                @foreach($users as $user)
                                    <option value="{{ $user->name }}" @selected($user->name === ($deal->lead_associate ?: $deal->assigned_associate))>{{ $user->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Prepared By</label>
                        <select id="consultationPreparedBySelect" class="inquiry-form-select">
                            @if(isset($users) && count($users))
                                @foreach($users as $user)
                                    <option value="{{ $user->name }}" @selected($user->name === (auth()->user()?->name ?? $deal->owner_name))>{{ $user->name }}</option>
                                @endforeach
                            @elseif(auth()->check() && auth()->user())
                                <option value="{{ auth()->user()->name }}" selected>{{ auth()->user()->name }}</option>
                            @endif
                        </select>
                    </div>
                </div>

                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Consultation Notes <span class="req-star">*</span></label>
                    <textarea id="consultationNotesInput" class="inquiry-form-textarea" rows="5" placeholder="Type the consultation details here..." required></textarea>
                </div>

                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Upload Supporting Files / Documents</label>
                    <input type="file" id="consultationFormFileInput" style="display: none;" multiple onchange="onConsultationFormFilesSelected(event)">
                    <div class="upload-dropzone" onclick="document.getElementById('consultationFormFileInput').click()" style="padding: 16px 14px; cursor: pointer; background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 8px; text-align: center;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 4px; display: inline-block;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <div style="font-size: 13px; font-weight: 600; color: #1e293b;">Click to browse or attach files</div>
                        <div style="font-size: 11.5px; color: #64748b;">PDF, DOCX, images, spreadsheets (multiple allowed)</div>
                    </div>
                    <div id="consultationFormFileList" style="margin-top: 8px; display: flex; flex-direction: column; gap: 6px;"></div>
                </div>
            </div>

            <div class="inquiry-modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeConsultationModal()">Cancel</button>
                <button type="submit" id="consultationSubmitBtn" class="btn-modal-save">Save Consultation</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: UPLOAD FILE --}}
<div id="consultationUploadModal" class="inquiry-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeUploadModal()">
    <div class="inquiry-modal-card">
        <div class="inquiry-modal-head">
            <h3 class="inquiry-modal-heading">UPLOAD FILE</h3>
            <button type="button" class="inquiry-modal-close-btn" onclick="closeUploadModal()">&times;</button>
        </div>
        <form id="consultationUploadForm" onsubmit="handleUploadFileSubmit(event)">
            <div class="inquiry-modal-body-content">
                
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Supporting File <span class="req-star">*</span></label>
                    <input type="file" id="consultationUploadInput" style="display: none;" onchange="onConsultationFilePicked(event)">
                    
                    <div class="upload-dropzone" onclick="document.getElementById('consultationUploadInput').click()">
                        <div id="uploadDropzonePrompt">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 8px;">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <div style="font-size: 13.5px; font-weight: 600; color: #1e293b; margin-bottom: 4px;">Click to choose a file</div>
                            <div style="font-size: 12px; color: #64748b;">PDF, DOCX, XLSX, images, or documents</div>
                        </div>

                        <div id="uploadSelectedFileBox" style="display: none;">
                            <div style="font-size: 14px; font-weight: 700; color: #0284c7; margin-bottom: 2px;">
                                📄 <span id="uploadSelectedFileName"></span>
                            </div>
                            <div style="font-size: 12px; color: #64748b;" id="uploadSelectedFileSize"></div>
                            <div style="font-size: 11px; color: #2563eb; margin-top: 6px; font-weight: 600;">(Click to choose a different file)</div>
                        </div>
                    </div>
                </div>

                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Associate With Consultation</label>
                    <select id="uploadLinkedConsultationSelect" class="inquiry-form-select">
                        <option value="">General Consultation File (All)</option>
                    </select>
                </div>

            </div>

            <div class="inquiry-modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeUploadModal()">Cancel</button>
                <button type="submit" class="btn-modal-save">Upload File</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: VIEW CONSULTATION DETAILS --}}
<div id="consultationViewModal" class="inquiry-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeConsultationViewModal()">
    <div class="inquiry-modal-card">
        <div class="inquiry-modal-head">
            <h3 class="inquiry-modal-heading">CONSULTATION DETAILS</h3>
            <button type="button" class="inquiry-modal-close-btn" onclick="closeConsultationViewModal()">&times;</button>
        </div>
        <div class="inquiry-modal-body-content">
            <div class="inquiry-view-group">
                <div class="inquiry-view-label">Consultation Title</div>
                <div id="viewConsultationTitle" class="inquiry-view-value highlight"></div>
            </div>

            <div class="inquiry-modal-grid-2">
                <div class="inquiry-view-group">
                    <div class="inquiry-view-label">Date</div>
                    <div id="viewConsultationDate" class="inquiry-view-value"></div>
                </div>

                <div class="inquiry-view-group">
                    <div class="inquiry-view-label">Consultant</div>
                    <div id="viewConsultationConsultant" class="inquiry-view-value"></div>
                </div>
            </div>

            <div class="inquiry-modal-grid-2">
                <div class="inquiry-view-group">
                    <div class="inquiry-view-label">Assigned Associate</div>
                    <div id="viewConsultationAssociate" class="inquiry-view-value"></div>
                </div>

                <div class="inquiry-view-group">
                    <div class="inquiry-view-label">Prepared By</div>
                    <div id="viewConsultationPreparedBy" class="inquiry-view-value"></div>
                </div>
            </div>

            <div class="inquiry-view-group">
                <div class="inquiry-view-label">Consultation Notes</div>
                <div id="viewConsultationNotes" class="inquiry-view-value text-block"></div>
            </div>

            <div class="inquiry-view-group">
                <div class="inquiry-view-label">Attachments</div>
                <div id="viewConsultationAttachments" style="margin-top: 6px;"></div>
            </div>
        </div>

        <div class="inquiry-modal-foot">
            <button type="button" class="btn-modal-cancel" onclick="closeConsultationViewModal()">Close</button>
        </div>
    </div>
</div>

{{-- MODAL: DELETE CONSULTATION CONFIRMATION --}}
<div id="consultationDeleteModal" class="inquiry-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeDeleteConsultationModal()">
    <div class="inquiry-modal-card confirm-dialog">
        <div class="inquiry-modal-head">
            <h3 class="inquiry-modal-heading">Delete Consultation?</h3>
            <button type="button" class="inquiry-modal-close-btn" onclick="closeDeleteConsultationModal()">&times;</button>
        </div>
        <div class="inquiry-modal-body-content">
            <p style="margin: 0; color: #475569; font-size: 14px; line-height: 1.5;">
                Are you sure you want to delete this consultation? This action cannot be undone.
            </p>
        </div>
        <div class="inquiry-modal-foot">
            <button type="button" class="btn-modal-cancel" onclick="closeDeleteConsultationModal()">Cancel</button>
            <button type="button" class="btn-modal-danger" onclick="handleConfirmDeleteConsultation()">Delete</button>
        </div>
    </div>
</div>

{{-- MODAL: ADD / EDIT DEAL LINE ITEM --}}
<div id="lineItemFormModal" class="inquiry-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeLineItemModal()">
    <div class="inquiry-modal-card">
        <div class="inquiry-modal-head">
            <h3 id="lineItemFormModalTitle" class="inquiry-modal-heading">Add Line Item</h3>
            <button type="button" class="inquiry-modal-close-btn" onclick="closeLineItemModal()">&times;</button>
        </div>
        <form id="lineItemForm" onsubmit="handleSaveLineItem(event)">
            <input type="hidden" id="lineItemFormId" value="">
            <div class="inquiry-modal-body-content">
                <div class="inquiry-modal-grid-2">
                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Type <span class="req-star">*</span></label>
                        <input type="text" id="lineItemType" class="inquiry-form-input" value="SERVICE" readonly style="background: #f8fafc; color: #475569; cursor: not-allowed; border-color: #e2e8f0;" required>
                    </div>

                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Route <span class="req-star">*</span></label>
                        <input type="text" id="lineItemRoute" class="inquiry-form-input" value="Regular" readonly style="background: #f8fafc; color: #475569; cursor: not-allowed; border-color: #e2e8f0;" required>
                    </div>
                </div>

                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Select Item / Name <span class="req-star">*</span></label>
                    <select id="lineItemNameSelect" class="inquiry-form-select" onchange="handleLineItemSelectChange(this.value)" required>
                        <option value="">-- Choose Service Item --</option>
                    </select>
                    <input type="hidden" id="lineItemName" value="">
                </div>

                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Description / Scope</label>
                    <textarea id="lineItemDescription" class="inquiry-form-textarea" rows="2" readonly style="background: #f8fafc; color: #475569; cursor: not-allowed; border-color: #e2e8f0;" placeholder="Detailed scope or specification..."></textarea>
                </div>

                <div class="inquiry-modal-grid-2">
                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Quantity <span class="req-star">*</span></label>
                        <input type="number" id="lineItemQty" class="inquiry-form-input" min="0.01" step="any" value="1" required style="background: #ffffff; border-color: #3b82f6; font-weight: 600; color: #0f172a;">
                    </div>

                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Unit</label>
                        <input type="text" id="lineItemUnit" class="inquiry-form-input" placeholder="e.g. lot, unit, month, hour" value="lot" style="background: #ffffff; border-color: #3b82f6; font-weight: 600; color: #0f172a;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Unit Price (₱) <span class="req-star">*</span></label>
                        <input type="number" id="lineItemUnitPrice" class="inquiry-form-input" min="0" step="0.01" value="0.00" readonly style="background: #f8fafc; color: #475569; cursor: not-allowed; border-color: #e2e8f0;">
                    </div>

                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Discount (₱)</label>
                        <input type="number" id="lineItemDiscount" class="inquiry-form-input" min="0" step="0.01" value="0.00" readonly style="background: #f8fafc; color: #475569; cursor: not-allowed; border-color: #e2e8f0;">
                    </div>

                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Tax (₱)</label>
                        <input type="number" id="lineItemTax" class="inquiry-form-input" min="0" step="0.01" value="0.00" readonly style="background: #f8fafc; color: #475569; cursor: not-allowed; border-color: #e2e8f0;">
                    </div>
                </div>

                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Billing Terms / Schedule</label>
                    <input type="text" id="lineItemBilling" class="inquiry-form-input" readonly style="background: #f8fafc; color: #475569; cursor: not-allowed; border-color: #e2e8f0;" value="{{ $deal->payment_terms ?: 'Full Payment Before Service' }}">
                </div>
            </div>

            <div class="inquiry-modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeLineItemModal()">Cancel</button>
                <button type="submit" id="lineItemFormSubmitBtn" class="btn-modal-save">Save Line Item</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: DELETE DEAL LINE ITEM --}}
<div id="lineItemDeleteModal" class="inquiry-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeDeleteLineItemModal()">
    <div class="inquiry-modal-card confirm-dialog">
        <div class="inquiry-modal-head">
            <h3 class="inquiry-modal-heading">Delete Line Item?</h3>
            <button type="button" class="inquiry-modal-close-btn" onclick="closeDeleteLineItemModal()">&times;</button>
        </div>
        <div class="inquiry-modal-body-content">
            <p style="margin: 0; color: #475569; font-size: 14px; line-height: 1.5;">
                Are you sure you want to delete this line item? This will update the commercial pricing totals.
            </p>
        </div>
        <div class="inquiry-modal-foot">
            <button type="button" class="btn-modal-cancel" onclick="closeDeleteLineItemModal()">Cancel</button>
            <button type="button" class="btn-modal-danger" onclick="handleConfirmDeleteLineItem()">Delete</button>
        </div>
    </div>
</div>

{{-- MODAL: SAVE GLOBAL PROPOSAL TEMPLATE --}}
<div id="proposalTemplateModal" class="inquiry-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeProposalTemplateModal()">
    <div class="inquiry-modal-card confirm-dialog">
        <div class="inquiry-modal-head">
            <h3 class="inquiry-modal-heading">Save Global Template</h3>
            <button type="button" class="inquiry-modal-close-btn" onclick="closeProposalTemplateModal()">&times;</button>
        </div>
        <div class="inquiry-modal-body-content">
            <p style="margin: 0; color: #475569; font-size: 13.5px; line-height: 1.5;">
                Save the current proposal formatting and content as the new global proposal template?
            </p>
        </div>
        <div class="inquiry-modal-foot">
            <button type="button" class="btn-modal-cancel" onclick="closeProposalTemplateModal()">Cancel</button>
            <button type="button" class="btn-modal-save" onclick="confirmSaveProposalTemplate()">Save Template</button>
        </div>
    </div>
</div>

{{-- MODAL: SEND PROPOSAL EMAIL --}}
<div id="proposalSendModal" class="inquiry-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeSendProposalModal()">
    <div class="inquiry-modal-card">
        <div class="inquiry-modal-head">
            <h3 class="inquiry-modal-heading">SEND PROPOSAL</h3>
            <button type="button" class="inquiry-modal-close-btn" onclick="closeSendProposalModal()">&times;</button>
        </div>
        <form method="POST" action="{{ route('deals.proposal.send', $deal) }}" onsubmit="handleSendProposalSubmit(event, this)">
            @csrf
            <div class="inquiry-modal-body-content">
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Recipient Email <span class="req-star">*</span></label>
                    <input type="email" name="recipient_email" class="inquiry-form-input" value="{{ old('recipient_email', $proposal->recipient_email ?: $deal->email) }}" placeholder="e.g. client@company.com" required>
                </div>
                <div style="font-size: 12px; color: #64748b; line-height: 1.45;">
                    The proposal document for <strong>{{ $deal->deal_code ?: 'this deal' }}</strong> will be emailed to the client. This will record the current proposal version as the active client-facing snapshot.
                </div>
            </div>
            <div class="inquiry-modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeSendProposalModal()">Cancel</button>
                <button type="submit" class="btn-modal-save">Send Email</button>
            </div>
        </form>
    </div>
</div>



{{-- MODAL: CLIENT REVIEW PORTAL (PROFESSIONAL DOCUMENT REVIEWER) --}}
<div id="proposalClientPortalModal" class="crp-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeClientReviewPortalModal()">
    <div class="crp-modal-container">
        {{-- TOP BAR --}}
        <div class="crp-topbar">
            {{-- LEFT: CLIENT-FACING HEADER (NO INTERNAL VERSION) --}}
            <div class="crp-left-group">
                <div class="crp-company-name">John Kelly &amp; Company</div>
                <h3 class="crp-title">Proposal</h3>
                <div class="crp-client-name">Prepared for: {{ $contactName ?: ($deal->primary_contact_name ?: ($deal->company ?: 'Client Representative')) }}</div>
            </div>

            {{-- CENTER: PAGE NAVIGATION --}}
            <div class="crp-center-group">
                <div class="crp-page-nav">
                    <span class="crp-page-indicator" id="crpPageIndicator">Page 1 of 1</span>
                    <button type="button" id="crpPrevBtn" class="crp-nav-btn" onclick="crpPrevPage()" title="Previous Page">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </button>
                    <button type="button" id="crpNextBtn" class="crp-nav-btn" onclick="crpNextPage()" title="Next Page">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                </div>
            </div>

            {{-- RIGHT: COMMENTS, APPROVAL & CLOSE --}}
            <div class="crp-right-group">
                <button type="button" class="crp-btn-comment" onclick="crpToggleComments()" id="crpCommentsToggleBtn" title="Toggle Feedback Panel">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                    <span id="crpCommentBtnText">Comments</span>
                </button>
                <button type="button" id="crpApproveBtn" class="crp-btn-approve" onclick="crpApproveProposal()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Approve Proposal</span>
                </button>
                <button type="button" class="crp-btn-close" onclick="closeClientReviewPortalModal()" title="Close Portal">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
        </div>

        {{-- MAIN DOCUMENT VIEWER & COMMENTS PANEL --}}
        <div class="crp-main-view">
            {{-- VERTICAL SCROLL DOCUMENT VIEW --}}
            <div class="crp-doc-scroll-view" id="crpPagesTrack">
                {{-- Cloned proposal papers from preview-workspace dynamically inserted --}}
            </div>

            {{-- COMMENTS PANEL (DRAWER) --}}
            <div class="crp-comments-panel closed" id="crpCommentsPanel">
                <div class="crp-comments-head">
                    <h4>Client Feedback</h4>
                    <button type="button" class="crp-btn-close" onclick="crpToggleComments()" title="Close Feedback Panel">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                <div class="crp-comments-list" id="crpCommentsList">
                    {{-- Rendered comments --}}
                </div>
                <form class="crp-comments-foot" onsubmit="crpAddComment(event)">
                    <textarea id="crpCommentInput" class="inquiry-form-textarea" rows="3" placeholder="Write feedback, question, or change request..." style="font-size: 12.5px; min-height: 72px;" required></textarea>
                    <button type="submit" class="btn-modal-save" style="width: 100%; padding: 8px 14px; font-size: 13px; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                        <span>Post Comment</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: APPROVE / ADJUST DISCOUNT --}}
<div id="proposalDiscountModal" class="inquiry-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeAdjustDiscountModal()">
    <div class="inquiry-modal-card">
        <div class="inquiry-modal-head" style="display: flex; justify-content: space-between; align-items: flex-start; padding: 18px 22px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: #eff6ff; border: 1px solid #dbeafe; color: #1e4f95; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; margin-top: 2px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                        <line x1="7" y1="7" x2="7.01" y2="7"></line>
                    </svg>
                </div>
                <div>
                    <h3 class="inquiry-modal-heading" style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0; line-height: 1.25;">Approve / Adjust Discount</h3>
                    <p style="margin: 0; font-size: 12.5px; color: #64748b; line-height: 1.4;">Review and approve or adjust the proposed discount amount.</p>
                </div>
            </div>
            <button type="button" class="inquiry-modal-close-btn" onclick="closeAdjustDiscountModal()">&times;</button>
        </div>
        <form id="proposalDiscountForm" onsubmit="handleSaveAdjustDiscount(event)">
            <div class="inquiry-modal-body-content">
                {{-- DISCOUNT SCOPE RADIO --}}
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">How should the discount be applied?</label>
                    <div style="display: flex; gap: 24px; align-items: center; margin-top: 2px;">
                        <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 500; color: #1e293b; cursor: pointer;">
                            <input type="radio" name="discount_scope" value="item" id="discountScopeItem" checked onchange="handleDiscountScopeChange('item')" style="accent-color: #2563eb; width: 16px; height: 16px; cursor: pointer; margin: 0;">
                            <span>One Service / Item</span>
                        </label>
                        <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 500; color: #1e293b; cursor: pointer;">
                            <input type="radio" name="discount_scope" value="proposal" id="discountScopeProposal" onchange="handleDiscountScopeChange('proposal')" style="accent-color: #2563eb; width: 16px; height: 16px; cursor: pointer; margin: 0;">
                            <span>Entire Proposal</span>
                        </label>
                    </div>
                </div>

                {{-- ITEM SELECTION --}}
                <div class="inquiry-form-group" id="discountItemSelectContainer">
                    <label class="inquiry-form-label">Which service or item should be discounted?</label>
                    <select id="modalDiscountItemSelect" class="inquiry-form-select">
                        @php
                            $discountItemsList = [];
                            if (isset($proposalItems) && count($proposalItems)) {
                                foreach ($proposalItems as $idx => $propItem) {
                                    $pName = is_array($propItem) ? ($propItem['name'] ?? 'Service Item') : $propItem;
                                    $pPrice = is_array($propItem) && isset($propItem['price']) && (float)$propItem['price'] > 0
                                        ? (float)$propItem['price']
                                        : (is_array($propItem) && isset($propItem['amount']) && (float)$propItem['amount'] > 0
                                            ? (float)$propItem['amount']
                                            : ($proposalTotal > 0 && count($proposalItems)
                                                ? $proposalTotal / count($proposalItems)
                                                : ((float)($deal->amount ?: $deal->total_estimated_engagement_value ?: 0) / (count($proposalItems) ?: 1))));
                                    $discountItemsList[] = [
                                        'id' => $idx + 1,
                                        'name' => '#' . ($idx + 1) . ' — ' . $pName . ($pPrice > 0 ? ' (' . $money($pPrice) . ')' : ''),
                                        'price' => (float)$pPrice,
                                    ];
                                }
                            } else {
                                $fallbackPrice = $proposalTotal > 0 ? $proposalTotal : ($deal->total_estimated_engagement_value ?: $deal->amount ?: 0);
                                $discountItemsList[] = [
                                    'id' => 1,
                                    'name' => '#1 — ' . $defaultServiceName . ($fallbackPrice > 0 ? ' (' . $money($fallbackPrice) . ')' : ''),
                                    'price' => (float)$fallbackPrice,
                                ];
                            }
                        @endphp
                        @foreach($discountItemsList as $opt)
                            <option value="{{ $opt['id'] }}" data-price="{{ $opt['price'] }}">{{ $opt['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- TWO-COLUMN: DISCOUNT TYPE & VALUE --}}
                <div class="inquiry-modal-grid-2">
                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Discount Type</label>
                        <select id="modalDiscountTypeSelect" class="inquiry-form-select" onchange="handleDiscountTypeChange(this.value)">
                            <option value="amount" selected>Fixed Amount (₱)</option>
                            <option value="percentage">Percentage (%)</option>
                        </select>
                    </div>

                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label" id="modalDiscountValueLabel">Discount Amount</label>
                        <input type="number" id="modalAdjustDiscountValue" class="inquiry-form-input" min="0" step="0.01" placeholder="e.g. 5,000" required>
                    </div>
                </div>

                {{-- REASON FOR DISCOUNT --}}
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Reason for Discount</label>
                    <textarea id="modalDiscountRemarks" class="inquiry-form-textarea" rows="3" placeholder="Explain why this discount is being approved..."></textarea>
                </div>
            </div>
            <div class="inquiry-modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeAdjustDiscountModal()">Cancel</button>
                <button type="submit" id="modalDiscountSubmitBtn" class="btn-modal-save">Approve Discount &amp; Create Revision</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: UPDATE DECISION --}}
<div id="proposalDecisionModal" class="inquiry-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeUpdateDecisionModal()">
    <div class="inquiry-modal-card">
        <div class="inquiry-modal-head">
            <h3 class="inquiry-modal-heading">UPDATE PROPOSAL DECISION</h3>
            <button type="button" class="inquiry-modal-close-btn" onclick="closeUpdateDecisionModal()">&times;</button>
        </div>
        <form id="proposalDecisionForm" onsubmit="handleSaveProposalDecision(event)">
            <div class="inquiry-modal-body-content">
                <div class="inquiry-modal-grid-2">
                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Decision Status <span class="req-star">*</span></label>
                        <select id="proposalDecisionStatus" class="inquiry-form-select" required>
                            <option value="Accepted / Signed">Accepted / Signed</option>
                            <option value="Under Client Review" selected>Under Client Review</option>
                            <option value="Revision Requested">Revision Requested</option>
                            <option value="Internal Approved">Internal Approved</option>
                            <option value="Pending Decision">Pending Decision</option>
                            <option value="Declined / Lost">Declined / Lost</option>
                        </select>
                    </div>

                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Decision Date <span class="req-star">*</span></label>
                        <input type="date" id="proposalDecisionDate" class="inquiry-form-input" required>
                    </div>
                </div>

                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Decision Maker / Client Representative</label>
                    <input type="text" id="proposalDecisionMaker" class="inquiry-form-input" value="{{ $contactName ?: ($deal->primary_contact_name ?: '') }}" placeholder="e.g. Wilfredo Dublin (CEO / Owner)">
                </div>

                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Decision Notes &amp; Next Steps</label>
                    <textarea id="proposalDecisionNotes" class="inquiry-form-textarea" rows="4" placeholder="Enter notes regarding client confirmation, signed contract notes, or feedback..."></textarea>
                </div>
            </div>
            <div class="inquiry-modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeUpdateDecisionModal()">Cancel</button>
                <button type="submit" class="btn-modal-save">Update Decision</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: + DISPATCH ACTION REQUEST --}}
<div id="proposalDispatchModal" class="inquiry-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeDispatchActionRequestModal()">
    <div class="inquiry-modal-card">
        <div class="inquiry-modal-head">
            <h3 class="inquiry-modal-heading">+ DISPATCH ACTION REQUEST</h3>
            <button type="button" class="inquiry-modal-close-btn" onclick="closeDispatchActionRequestModal()">&times;</button>
        </div>
        <form id="proposalDispatchForm" onsubmit="handleSaveDispatchRequest(event)">
            <div class="inquiry-modal-body-content">
                <div class="inquiry-modal-grid-2">
                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Action Type <span class="req-star">*</span></label>
                        <select id="dispatchRequestType" class="inquiry-form-select" required>
                            <optgroup label="Proposal / Commercial">
                                <option value="Review &amp; Accept Proposal" selected>Review &amp; Accept Proposal</option>
                                <option value="Select Proposal Items">Select Proposal Items</option>
                                <option value="Request Discount / Price Adjustment">Request Discount / Price Adjustment</option>
                                <option value="Request Scope / Terms Change">Request Scope / Terms Change</option>
                                <option value="Commercial Revision / Addendum">Commercial Revision / Addendum</option>
                            </optgroup>
                            <optgroup label="Notice to Proceed (NTP)">
                                <option value="Approve Notice to Proceed (NTP)">Approve Notice to Proceed (NTP)</option>
                                <option value="Request NTP Revision">Request NTP Revision</option>
                            </optgroup>
                            <optgroup label="Project &amp; Retainer Scope">
                                <option value="Approve Project SOW Revision">Approve Project SOW Revision</option>
                                <option value="Approve Recurring Retainer Scope">Approve Recurring Retainer Scope</option>
                            </optgroup>
                            <optgroup label="Delivery &amp; Completion">
                                <option value="Acknowledge Transmittal Receipt">Acknowledge Transmittal Receipt</option>
                                <option value="Confirm Completion &amp; Sign-Off">Confirm Completion &amp; Sign-Off</option>
                            </optgroup>
                            <optgroup label="General / Legal">
                                <option value="Digital / Online Sign">Digital / Online Sign</option>
                                <option value="Upload Required Document">Upload Required Document</option>
                                <option value="Acknowledge Terms">Acknowledge Terms</option>
                                <option value="Confirm Details">Confirm Details</option>
                                <option value="Controlled Form Declaration">Controlled Form Declaration</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Delivery Channel <span class="req-star">*</span></label>
                        <select id="dispatchRequestChannel" class="inquiry-form-select" required>
                            <option value="Secure No-Login Link" selected>Secure No-Login Link</option>
                            <option value="Client Portal">Client Portal</option>
                            <option value="Email Tracked Request">Email Tracked Request</option>
                            <option value="Manual / Wet Signature">Manual / Wet Signature</option>
                            <option value="External Signing">External Signing</option>
                        </select>
                    </div>
                </div>

                <div class="inquiry-modal-grid-2">
                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Assignee / Department <span class="req-star">*</span></label>
                        <select id="dispatchRequestDepartment" class="inquiry-form-select" required>
                            <option value="Operations &amp; Delivery" selected>Operations &amp; Delivery Team</option>
                            <option value="Legal &amp; Advisory">Legal &amp; Advisory</option>
                            <option value="Finance &amp; Billing">Finance &amp; Billing</option>
                            <option value="Client Relationship Team">Client Relationship Team</option>
                        </select>
                    </div>

                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Priority Level <span class="req-star">*</span></label>
                        <select id="dispatchRequestPriority" class="inquiry-form-select" required>
                            <option value="High" selected>High Priority</option>
                            <option value="Urgent">Urgent</option>
                            <option value="Medium">Medium Priority</option>
                            <option value="Low">Low Priority</option>
                        </select>
                    </div>
                </div>

                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Target Completion Date <span class="req-star">*</span></label>
                    <input type="date" id="dispatchRequestDueDate" class="inquiry-form-input" required>
                </div>

                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Dispatch Instructions / Remarks <span class="req-star">*</span></label>
                    <textarea id="dispatchRequestDetails" class="inquiry-form-textarea" rows="4" placeholder="Specify instructions, contract deliverables, or specific client requests to execute..." required></textarea>
                </div>
            </div>
            <div class="inquiry-modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeDispatchActionRequestModal()">Cancel</button>
                <button type="submit" class="btn-modal-save">Dispatch Request</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: RECORD FLEXIBLE PAYMENT ALLOCATION --}}
<div id="financeRecordPaymentModal" class="finance-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeRecordPaymentModal()">
    <div class="finance-modal-dialog">
        <div class="finance-modal-header">
            <div class="finance-modal-title-box">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 1px;">
                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                    <line x1="2" y1="10" x2="22" y2="10"></line>
                    <line x1="6" y1="14" x2="10" y2="14"></line>
                </svg>
                <div>
                    <h3>Record Flexible Payment Allocation</h3>
                    <div class="finance-modal-subtitle" style="margin: 3px 0 0 0;">Record and allocate payment for the selected deal.</div>
                </div>
            </div>
            <button type="button" class="finance-modal-btn-close" onclick="closeRecordPaymentModal()" aria-label="Close">&times;</button>
        </div>

        <form id="financePaymentForm" onsubmit="handleSavePaymentAllocation(event)">
            <div class="finance-modal-body">

                {{-- 1. PAYMENT ALLOCATION SCOPE --}}
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Payment Allocation Scope <span class="req-star">*</span></label>
                    <div class="finance-scope-selector-grid">
                        <label class="finance-scope-label" onclick="handleFinanceScopeChange('entire')">
                            <input type="radio" name="finance_allocation_scope" value="entire">
                            <span>Entire Proposal</span>
                        </label>
                        <label class="finance-scope-label active" onclick="handleFinanceScopeChange('item')">
                            <input type="radio" name="finance_allocation_scope" value="item" checked>
                            <span>Specific Proposal Item</span>
                        </label>
                        <label class="finance-scope-label" onclick="handleFinanceScopeChange('downpayment')">
                            <input type="radio" name="finance_allocation_scope" value="downpayment">
                            <span>Agreed Downpayment</span>
                        </label>
                        <label class="finance-scope-label" onclick="handleFinanceScopeChange('retainer')">
                            <input type="radio" name="finance_allocation_scope" value="retainer">
                            <span>Retainer Period</span>
                        </label>
                        <label class="finance-scope-label" onclick="handleFinanceScopeChange('milestone')">
                            <input type="radio" name="finance_allocation_scope" value="milestone">
                            <span>Milestone Payment</span>
                        </label>
                    </div>
                </div>

                {{-- 2. SELECT TARGET PROPOSAL ITEM --}}
                <div class="inquiry-form-group" id="financeTargetItemContainer">
                    <label class="inquiry-form-label">Select Target Proposal Item <span class="req-star">*</span></label>
                    <select id="financeTargetItemSelect" class="inquiry-form-select" onchange="handleTargetItemChange(this.value)" style="height: 38px; border-radius: 7px; font-size: 12.5px;">
                        @foreach($financeProposalItems as $fItem)
                            <option value="{{ $fItem['id'] }}" data-price="{{ $fItem['price'] }}">
                                #{{ $fItem['id'] }} - {{ $fItem['name'] }} ({{ $money($fItem['price']) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 3. PAYMENT AMOUNT & PAYMENT METHOD --}}
                <div class="inquiry-modal-grid-2">
                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Payment Amount (₱) <span class="req-star">*</span></label>
                        <input type="number" id="financePaymentAmount" class="inquiry-form-input" min="0.01" step="0.01" placeholder="e.g. 13,440.00" style="height: 38px; border-radius: 7px; font-size: 12.5px;" required>
                    </div>

                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Payment Method <span class="req-star">*</span></label>
                        <select id="financePaymentMethod" class="inquiry-form-select" style="height: 38px; border-radius: 7px; font-size: 12.5px;" required>
                            <option value="Bank Transfer" selected>Bank Transfer</option>
                            <option value="Online Banking">Online Banking</option>
                            <option value="GCash / E-Wallet">GCash / E-Wallet</option>
                            <option value="Check">Check</option>
                            <option value="Cash">Cash</option>
                            <option value="Credit / Debit Card">Credit / Debit Card</option>
                        </select>
                    </div>
                </div>

                {{-- 4. COLLECTION / OFFICIAL RECEIPT REF & RECORDED BY --}}
                <div class="inquiry-modal-grid-2">
                    {{-- LEFT: DUAL UPLOAD CARD UNDER COLLECTION / OFFICIAL RECEIPT REF --}}
                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Collection / Official Receipt Ref</label>
                        <div class="finance-upload-card">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"></path>
                                <path d="M12 12v9"></path>
                                <path d="m16 16-4-4-4 4"></path>
                            </svg>
                            <div class="finance-upload-title">Attach Receipt / Proof</div>
                            <div class="finance-upload-hint">PDF, JPG, PNG • Max 10MB</div>

                            <div class="finance-upload-btns" id="financeUploadBtns">
                                <button type="button" class="finance-btn-upload-file" onclick="triggerFinanceUpload('file')">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                    <span>Send File</span>
                                </button>
                                <button type="button" class="finance-btn-upload-image" onclick="triggerFinanceUpload('image')">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                    <span>Send Image</span>
                                </button>
                            </div>

                            <input type="file" id="financeFileInput" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" style="display: none;" onchange="handleFinanceFileSelected(event, false)">
                            <input type="file" id="financeImageInput" accept="image/png,image/jpeg,image/jpg" style="display: none;" onchange="handleFinanceFileSelected(event, true)">

                            <div class="finance-file-preview" id="financeFilePreviewBox" style="display: none;">
                                <div class="finance-file-preview-left" id="financeFilePreviewLeft"></div>
                                <div class="finance-file-actions">
                                    <button type="button" class="finance-btn-file-replace" onclick="triggerFinanceUpload(currentUploadedFinanceFile?.isImage ? 'image' : 'file')">Replace</button>
                                    <button type="button" class="finance-btn-file-remove" onclick="removeFinanceFile()">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT: RECORDED BY --}}
                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Recorded By</label>
                        <div class="finance-recorded-by-box">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span>{{ $financeLoggedUser }}</span>
                        </div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 5px;">
                            Auto-filled from verified user session.
                        </div>
                    </div>
                </div>

                {{-- 5. NOTES / INSTRUCTIONS --}}
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Notes / Instructions</label>
                    <textarea id="financePaymentNotes" class="inquiry-form-textarea" rows="2" placeholder="Optional notes on payment confirmation..." style="border-radius: 7px; min-height: 52px; font-size: 12px;"></textarea>
                </div>

            </div>

            <div class="finance-modal-footer">
                <button type="button" class="finance-btn-cancel-modal" onclick="closeRecordPaymentModal()">Cancel</button>
                <button type="submit" class="finance-btn-save-allocation">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>Save Payment Allocation</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- =========================================================
     MODAL 1: START BATCH DETAILS MODAL (EXACT SCREENSHOT VIEW)
     ========================================================= --}}
<div id="startBatchDetailsModal" class="start-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeStartBatchDetailsModal()">
    <div class="start-modal-card">
        {{-- Header --}}
        <div class="start-modal-head">
            <div class="start-modal-title-left">
                <div class="start-modal-header-top-line">
                    <h3 class="start-modal-main-title">START Batch Details</h3>
                    <span class="start-modal-code-badge" id="startModalBatchCode">ST-2026-001</span>
                    <span id="startModalBatchStatusBadge" class="start-modal-status-pill">
                        <span id="startModalBatchStatusText">Completed</span>
                    </span>
                </div>
                <p class="start-modal-subtitle">Review activation status, assignments, acknowledgement, and Service Memo.</p>
            </div>

            <button type="button" class="start-modal-close-btn" onclick="closeStartBatchDetailsModal()">&times;</button>
        </div>

        {{-- Inner Tabs Bar --}}
        <div class="start-modal-tabs-track">
            <button type="button" class="start-modal-tab-btn active" data-start-tab="overview" onclick="switchStartModalTab('overview')">
                Overview
            </button>
            <button type="button" class="start-modal-tab-btn" data-start-tab="scope" onclick="switchStartModalTab('scope')">
                Activated Items / Scope
            </button>
            <button type="button" class="start-modal-tab-btn" data-start-tab="assignments" onclick="switchStartModalTab('assignments')">
                Assignments &amp; Acknowledgement
            </button>
            <button type="button" class="start-modal-tab-btn" data-start-tab="history" onclick="switchStartModalTab('history')">
                History
            </button>
        </div>

        {{-- Body Content --}}
        <div class="start-modal-body-scroll">

            {{-- TAB 1: OVERVIEW --}}
            <div id="startModalTabOverview" class="start-modal-tab-content">
                {{-- Status Highlight Banner --}}
                <div class="start-status-banner-card">
                    <div class="smsb-left-group">
                        <div class="smsb-current-block">
                            <div class="smsb-label">Current Status</div>
                            <div class="smsb-status-val" id="startModalBannerStatus">Completed</div>
                        </div>
                        <div class="smsb-divider"></div>
                        <div class="smsb-details-block">
                            <div class="smsb-label">Status Details</div>
                            <div class="smsb-details-text" id="startModalBannerDetails">Service Memo SM-2026-001 issued. Document is immutable.</div>
                        </div>
                    </div>
                    <div class="smsb-actions-group" id="startModalBannerActions">
                        <button type="button" class="btn-start-memo-primary" onclick="openStartBatchMemoTab(activeStartBatchId)">View Service Memo</button>
                        <button type="button" class="btn-start-amend-outline" onclick="openStartMemoAmendmentModal()">Create Amendment</button>
                    </div>
                </div>

                {{-- 2-Column Overview Information --}}
                <div class="start-details-grid-rows">
                    {{-- Row 1 --}}
                    <div class="sm-row">
                        <div class="sm-col">
                            <div class="sm-label">START CODE</div>
                            <div class="sm-value sm-value-important" id="startInfoCode">ST-2026-001</div>
                        </div>
                        <div class="sm-col">
                            <div class="sm-label">OPENED DATE &amp; TIME</div>
                            <div class="sm-value" id="startInfoDate">Sep 17, 2026 at 03:19 PM</div>
                        </div>
                    </div>

                    {{-- Row 2 --}}
                    <div class="sm-row">
                        <div class="sm-col">
                            <div class="sm-label">STATUS</div>
                            <div class="sm-value sm-value-important" id="startInfoStatus">Completed</div>
                        </div>
                        <div class="sm-col">
                            <div class="sm-label">SERVICE MEMO STATUS</div>
                            <div class="sm-value sm-value-important" id="startInfoMemoStatus">Issued &amp; Activated</div>
                        </div>
                    </div>

                    {{-- Row 3 --}}
                    <div class="sm-row">
                        <div class="sm-col">
                            <div class="sm-label">SCOPE / ACTIVATED ITEMS COUNT</div>
                            <div class="sm-value" id="startInfoScopeCount">3 Services Included</div>
                        </div>
                        <div class="sm-col">
                            <div class="sm-label">DEAL / PROJECT REFERENCE</div>
                            <div class="sm-value" id="startInfoDealRef">{{ $deal->deal_code ?: ('CONDEAL-' . date('Y') . '-' . str_pad($deal->id, 3, '0', STR_PAD_LEFT)) }} ({{ $deal->company ?: ($deal->company_name ?: ($deal->deal_title ?: 'BANH MI KITCHEN SERVICES INC.')) }})</div>
                        </div>
                    </div>

                    {{-- Row 4 --}}
                    <div class="sm-row">
                        <div class="sm-col">
                            <div class="sm-label">CREATED BY</div>
                            <div class="sm-value" id="startInfoCreatedBy">{{ $deal->owner_name ?: 'Administrator' }}</div>
                        </div>
                        <div class="sm-col">
                            <div class="sm-label">AUTHORIZED BY</div>
                            <div class="sm-value" id="startInfoAuthorizedBy">{{ $deal->lead_consultant ?: 'Pending Authorization' }}</div>
                        </div>
                    </div>
                </div>

                {{-- Scope Deliverables & Notes --}}
                <div class="start-scope-notes-section">
                    <div class="start-scope-notes-accordion" onclick="toggleStartNotesCollapse()">
                        <span class="start-notes-title">Scope Deliverables &amp; Notes</span>
                    </div>
                    <div id="startScopeNotesPanel" class="start-scope-notes-panel" style="display: none;">
                        <div class="start-notes-body" id="startInfoNotes">
                            {{ $deal->scope_of_work ?: 'Standard advisory and compliance filing deliverables.' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 2: ACTIVATED ITEMS / SCOPE --}}
            <div id="startModalTabScope" class="start-modal-tab-content" style="display: none;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h4 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Activated Services / Proposal Items</h4>
                        <p style="font-size: 13px; color: #64748b; margin: 3px 0 0;">Each item in this START batch has its own readiness and operational lifecycle.</p>
                    </div>
                </div>

                <div id="startScopeItemsList" style="display: flex; flex-direction: column; gap: 10px;">
                    {{-- Dynamically populated --}}
                </div>
            </div>

            {{-- TAB 3: ASSIGNMENTS & ACKNOWLEDGEMENT --}}
            <div id="startModalTabAssignments" class="start-modal-tab-content" style="display: none;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <h4 style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0;">Assignments &amp; Acknowledgement Tracker</h4>
                        <p style="font-size: 13.5px; color: #64748b; margin: 3px 0 0;">All required assignees must acknowledge their role before START can become Team Confirmed.</p>
                    </div>

                    <button type="button" class="btn-start-add-role" onclick="openStartAddAssignmentModal()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Add Role Assignment</span>
                    </button>
                </div>

                {{-- Acknowledgement Progress Bar Card --}}
                <div class="start-ack-progress-card">
                    <div class="start-ack-progress-header">
                        <span class="start-ack-progress-label">ACKNOWLEDGEMENT PROGRESS</span>
                        <span class="start-ack-progress-value" id="startAckProgressText">3 of 3 Acknowledged (100%)</span>
                    </div>
                    <div class="start-ack-track">
                        <div id="startAckProgressBar" class="start-ack-fill" style="width: 100%;"></div>
                    </div>
                </div>

                <div class="start-table-wrap">
                    <table class="start-assign-table" id="startAssignmentsTable">
                        <thead>
                            <tr>
                                <th>SERVICE / ROLE</th>
                                <th>ASSIGNEE</th>
                                <th>STATUS</th>
                                <th>ACKNOWLEDGED DATE</th>
                                <th>NOTES / RESPONSE</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="startAssignmentsTableBody">
                            {{-- Dynamically populated --}}
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB 4: HISTORY --}}
            <div id="startModalTabHistory" class="start-modal-tab-content" style="display: none;">
                <div style="margin-bottom: 16px;">
                    <h4 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">START Lifecycle Audit Trail</h4>
                    <p style="font-size: 13px; color: #64748b; margin: 3px 0 0;">Complete chronological history of events, confirmations, state changes, and issuances.</p>
                </div>

                <div class="start-history-list" id="startHistoryTimelineList">
                    {{-- Dynamically populated --}}
                </div>
            </div>

        </div>
    </div>
</div>

{{-- =========================================================
     MODAL: DEDICATED SERVICE MEMO DOCUMENT VIEWER (VERTICAL READING VIEW)
     ========================================================= --}}
<div id="startServiceMemoViewerModal" class="start-modal-backdrop" style="display: none; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); z-index: 100010;" onclick="if(event.target === this) closeStartMemoViewer()">
    <div class="start-modal-card" style="max-width: 900px; background: #f1f5f9; border-radius: 12px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35); overflow: hidden; display: flex; flex-direction: column; max-height: 94vh; width: 95%;">
        {{-- Clean Reader Head --}}
        <div class="start-modal-head" style="background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 12px 24px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="start-header-icon-box" style="width: 32px; height: 32px; border-radius: 8px; background: #eff6ff; color: #1d68e1;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                </span>
                <div>
                    <h3 style="font-size: 15px; font-weight: 800; color: #002b66; margin: 0; display: inline-flex; align-items: center; gap: 8px;">
                        <span>Service Memo</span>
                        <span id="startMemoViewerCodeBadge" class="start-modal-code-badge" style="font-size: 11.5px;">SM-2026-001</span>
                    </h3>
                </div>
            </div>

            <div class="start-modal-head-actions">
                <button type="button" class="start-btn-primary" onclick="printStartMemo()" style="background: #002b66; border-color: #002b66; padding: 6px 14px; font-size: 12.5px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    <span>Print</span>
                </button>

                <button type="button" class="start-modal-close-btn" onclick="closeStartMemoViewer()">&times;</button>
            </div>
        </div>

        {{-- Scrollable Vertical Reading Area --}}
        <div class="start-modal-body-scroll" style="background: #f1f5f9; padding: 24px 20px; overflow-y: auto;">
            {{-- Pure Service Memo Paper --}}
            <div class="start-memo-paper" id="startMemoPaperContainer" style="margin: 0 auto; box-shadow: 0 4px 20px rgba(0, 43, 102, 0.08); background: #ffffff;">
                <div id="startMemoWatermark" class="start-memo-watermark">DRAFT</div>
                <div id="startMemoIssuedSeal" class="start-memo-issued-seal" style="display: none;">✓ ISSUED &amp; ACTIVATED</div>

                {{-- BRAND HEADER --}}
                <div class="start-memo-brand-header">
                    <div class="start-memo-brand-title">
                        <div class="start-memo-brand-line1">John Kelly</div>
                        <div class="start-memo-brand-line2"><span class="amp">&amp;</span> Company</div>
                    </div>
                </div>

                <div class="start-memo-top-rule"></div>

                {{-- MEMO TITLE --}}
                <h1 class="start-memo-main-heading">SERVICE MEMO</h1>

                {{-- MEMO METADATA LIST --}}
                <div class="start-memo-meta-list">
                    <div class="start-memo-meta-item">
                        <span class="start-memo-meta-label">TO</span>
                        <span class="start-memo-meta-colon">:</span>
                        <span class="start-memo-meta-value" id="smHeaderTo">{{ $deal->company ?: $deal->deal_title }}</span>
                    </div>
                    <div class="start-memo-meta-item">
                        <span class="start-memo-meta-label">FROM</span>
                        <span class="start-memo-meta-colon">:</span>
                        <span class="start-memo-meta-value" id="smHeaderFrom">John Kelly &amp; Company / Management Team</span>
                    </div>
                    <div class="start-memo-meta-item">
                        <span class="start-memo-meta-label">DATE</span>
                        <span class="start-memo-meta-colon">:</span>
                        <span class="start-memo-meta-value" id="smHeaderDate">{{ date('F d, Y') }}</span>
                    </div>
                    <div class="start-memo-meta-item">
                        <span class="start-memo-meta-label">SUBJECT</span>
                        <span class="start-memo-meta-colon">:</span>
                        <span class="start-memo-meta-value" id="smHeaderSubject">SERVICE TASK ACTIVATION AND ROUTING TRACKER (START)</span>
                    </div>
                </div>

                <div class="start-memo-mid-rule"></div>

                {{-- INTRO TEXT --}}
                <div class="start-memo-intro-text">
                    <p><strong>Dear Team,</strong></p>
                    <p>This service memo is to inform you of the recent updates and important reminders regarding our system and processes. Please take a moment to review the following details:</p>
                </div>

                {{-- 1. MEMO DETAILS GRID TABLE --}}
                <table class="start-memo-grid-table">
                    <tr>
                        <th style="width: 22%;">MEMO REFERENCE</th>
                        <td style="width: 28%; font-weight: 700;" id="smCellMemoRef">SM-2026-001</td>
                        <th style="width: 22%;">START BATCH REF</th>
                        <td style="width: 28%; font-weight: 700;" id="smCellStartRef">ST-2026-001</td>
                    </tr>
                    <tr>
                        <th>CLIENT / ACCOUNT</th>
                        <td id="smCellClient">{{ $deal->company ?: $deal->deal_title }}</td>
                        <th>PRIMARY CONTACT</th>
                        <td id="smCellContact">{{ $contactName }}</td>
                    </tr>
                    <tr>
                        <th>SOURCE DEAL / PROPOSAL</th>
                        <td id="smCellDealSource">{{ $deal->deal_code ?: ('CONDEAL-' . date('Y') . '-' . str_pad($deal->id, 3, '0', STR_PAD_LEFT)) }}</td>
                        <th>ENGAGEMENT STRUCTURE</th>
                        <td id="smCellEngagementType">{{ $deal->engagement_type ?: 'Regular Retainer Engagement' }}</td>
                    </tr>
                    <tr>
                        <th>ACTIVATION SUMMARY</th>
                        <td colspan="3" id="smCellActivatedItems">
                            Multiple Services (3 items)
                        </td>
                    </tr>
                    <tr>
                        <th>COMMERCIAL REFERENCE</th>
                        <td colspan="3" id="smCellCommercialRef">
                            Total Estimated Engagement Value: <strong>{{ $money($deal->amount ?: $deal->total_estimated_engagement_value) }}</strong> &bull; Payment Terms: {{ $deal->payment_terms ?: 'Full Payment Before Service' }}
                        </td>
                    </tr>
                    <tr>
                        <th>SPECIAL INSTRUCTIONS</th>
                        <td colspan="3" id="smCellSpecialInstructionsRow">
                            {{ $deal->scope_of_work ?: 'Commence operational service onboarding and statutory compliance execution.' }}
                        </td>
                    </tr>
                </table>

                {{-- 2. REGULAR DIVISION SECTION --}}
                <div id="smRegularDivisionSection">
                    <div class="start-memo-section-title">R E G U L A R &nbsp; D I V I S I O N</div>
                    <table class="start-memo-grid-table" id="smRegularDivisionTable">
                        <thead>
                            <tr>
                                <th style="width: 25%;">REGULAR TITLE</th>
                                <th style="width: 35%;">SCOPE / SERVICES</th>
                                <th style="width: 20%;">START / FREQUENCY</th>
                                <th style="width: 20%;">ASSIGNED TEAM</th>
                            </tr>
                        </thead>
                        <tbody id="smRegularDivisionTableBody">
                            {{-- Dynamically populated --}}
                        </tbody>
                    </table>
                </div>

                {{-- 3. PROJECT DIVISION SECTION --}}
                <div id="smProjectDivisionSection">
                    <div class="start-memo-section-title">P R O J E C T &nbsp; D I V I S I O N</div>
                    <table class="start-memo-grid-table" id="smProjectDivisionTable" style="display: none;">
                        <thead>
                            <tr>
                                <th style="width: 25%;">PROJECT TITLE</th>
                                <th style="width: 35%;">SCOPE / SERVICES</th>
                                <th style="width: 20%;">TARGET COMPLETION</th>
                                <th style="width: 20%;">ASSIGNED TEAM</th>
                            </tr>
                        </thead>
                        <tbody id="smProjectDivisionTableBody">
                            {{-- Dynamically populated --}}
                        </tbody>
                    </table>
                    <div class="start-memo-no-engagement-box" id="smProjectDivisionNoEngagement">
                        No Project Engagement
                    </div>
                </div>

                {{-- 4. COMMERCIAL / PAYMENT REFERENCE --}}
                <div class="start-memo-section-title">C O M M E R C I A L &nbsp; / &nbsp; P A Y M E N T &nbsp; R E F E R E N C E</div>
                <table class="start-memo-grid-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">TOTAL ESTIMATED ENGAGEMENT VALUE</th>
                            <th style="width: 50%;">PAYMENT TERMS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="font-weight: 700; color: #002b66;" id="smCellTotalValue">
                                {{ $money($deal->amount ?: $deal->total_estimated_engagement_value) }}
                            </td>
                            <td id="smCellPaymentTerms">
                                {{ $deal->payment_terms ?: 'Full Payment Before Service' }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                {{-- 5. ASSIGNMENT CONFIRMATION --}}
                <div class="start-memo-section-title">A S S I G N M E N T &nbsp; C O N F I R M A T I O N</div>
                <table class="start-memo-grid-table">
                    <thead>
                        <tr>
                            <th style="width: 25%;">ROLE</th>
                            <th style="width: 30%;">ASSIGNED MEMBER</th>
                            <th style="width: 25%;">CONFIRMATION STATUS</th>
                            <th style="width: 20%;">TIMESTAMP</th>
                        </tr>
                    </thead>
                    <tbody id="smAssignmentsSummaryTableBody">
                        {{-- Dynamically populated --}}
                    </tbody>
                </table>

                {{-- 6. AUTHORIZED BY / ISSUED BY / TIMESTAMPS --}}
                <div class="start-memo-section-title">A U T H O R I Z E D &nbsp; B Y &nbsp; / &nbsp; I S S U E D &nbsp; B Y &nbsp; / &nbsp; T I M E S T A M P S</div>
                <div class="start-memo-signatures-grid">
                    <div class="start-memo-sig-col">
                        <div class="start-memo-sig-label">AUTHORIZED BY</div>
                        <div class="start-memo-sig-line"></div>
                        <div class="start-memo-sig-name" id="smSignAuthorizedBy">{{ $deal->lead_consultant ?: 'John Kelly Abalde' }}</div>
                        <div class="start-memo-sig-sub">MANAGING PARTNER</div>
                    </div>
                    <div class="start-memo-sig-col right-col">
                        <div class="start-memo-sig-label">ISSUED BY</div>
                        <div class="start-memo-sig-line"></div>
                        <div class="start-memo-sig-name" id="smSignIssuedBy">{{ $financeLoggedUser }}</div>
                        <div class="start-memo-sig-sub" id="smSignIssuedAt">MANAGEMENT TEAM</div>
                    </div>
                </div>

                {{-- 7. SPECIAL INSTRUCTIONS --}}
                <div class="start-memo-section-title">S P E C I A L &nbsp; I N S T R U C T I O N S</div>
                <div class="start-memo-special-box" id="smSpecialInstructionsBox">
                    {{ $deal->scope_of_work ?: 'Commence operational service onboarding and statutory compliance execution in accordance with approved proposal terms.' }}
                </div>

                {{-- FOOTER --}}
                <div class="start-memo-footer-rule"></div>
                <div class="start-memo-footer-caption">
                    Official Service Memo generated from START. Any amendment requires the approved amendment/revision process.
                </div>
            </div>
        </div>
    </div>
</div>

{{-- =========================================================
     MODAL 2: CREATE PROGRESSIVE START BATCH MODAL
     ========================================================= --}}
<div id="createStartBatchModal" class="start-modal-backdrop" style="display: none; position: fixed; inset: 0; z-index: 100050; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(2px); align-items: center; justify-content: center; padding: 20px; box-sizing: border-box; overflow: hidden;" onclick="if(event.target === this) closeCreateStartBatchModal()">
    <div class="start-modal-card" style="max-width: 720px; width: 100%; max-height: 90vh; background: #ffffff; border-radius: 12px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25); border: 1px solid #e2e8f0;">
        <div class="start-modal-head" style="padding: 18px 24px 16px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; background: #ffffff;">
            <h3 class="start-modal-main-title" style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">+ Create Progressive START Batch</h3>
            <button type="button" class="start-modal-close-btn" onclick="closeCreateStartBatchModal()">&times;</button>
        </div>

        <form id="createStartBatchForm" onsubmit="handleSaveNewStartBatch(event)" style="display: flex; flex-direction: column; overflow: hidden; flex: 1; margin: 0;">
            <div class="start-modal-body-scroll" style="padding: 20px 24px; overflow-y: auto; flex: 1; overscroll-behavior: contain; max-height: calc(90vh - 135px);">

                <div class="inquiry-modal-grid-2">
                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">START Code <span class="req-star">*</span></label>
                        <input type="text" id="csbCodeInput" class="inquiry-form-input" style="height: 38px; border-radius: 7px; font-size: 12.5px;" required readonly>
                    </div>

                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Batch Title / Name <span class="req-star">*</span></label>
                        <input type="text" id="csbTitleInput" class="inquiry-form-input" placeholder="e.g. Batch 1 — Regular Retainer Services" style="height: 38px; border-radius: 7px; font-size: 12.5px;" required>
                    </div>
                </div>

                {{-- Activated Items Checklist --}}
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Select Services / Items to Activate in this Batch <span class="req-star">*</span></label>
                    <div id="csbServicesChecklist" style="display: flex; flex-direction: column; gap: 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px;">
                        {{-- Checkboxes populated from Deal line items --}}
                    </div>
                </div>

                <div class="inquiry-modal-grid-2">
                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Engagement Type</label>
                        <select id="csbEngagementType" class="inquiry-form-select" style="height: 38px; border-radius: 7px; font-size: 12.5px;">
                            <option value="Regular Engagement" selected>Regular Engagement</option>
                            <option value="Project Engagement">Project Engagement</option>
                        </select>
                    </div>

                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Planned Start Date</label>
                        <input type="date" id="csbStartDate" class="inquiry-form-input" value="{{ $deal->planned_start_date ? $deal->planned_start_date->format('Y-m-d') : date('Y-m-d') }}" style="height: 38px; border-radius: 7px; font-size: 12.5px;">
                    </div>
                </div>

                <div class="inquiry-modal-grid-2">
                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Target Completion Date</label>
                        <input type="date" id="csbTargetDate" class="inquiry-form-input" value="{{ $deal->estimated_completion_date ? $deal->estimated_completion_date->format('Y-m-d') : '' }}" style="height: 38px; border-radius: 7px; font-size: 12.5px;">
                    </div>

                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Estimated Duration (Days)</label>
                        <input type="number" id="csbDurationDays" class="inquiry-form-input" min="1" placeholder="e.g. 30" value="{{ $deal->estimated_duration_days ?: 30 }}" style="height: 38px; border-radius: 7px; font-size: 12.5px;">
                    </div>
                </div>

                {{-- Default Roles Setup --}}
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Required Roles &amp; Initial Assignees</label>
                    <div class="inquiry-modal-grid-2" style="margin-bottom: 8px;">
                        <div>
                            <span style="font-size: 11px; font-weight: 600; color: #475569;">Lead Consultant</span>
                            <select id="csbAssigneeConsultant" class="inquiry-form-select" style="height: 36px; border-radius: 6px; font-size: 12px; margin-top: 3px;">
                                @foreach($users as $usr)
                                    <option value="{{ $usr->name }}" @selected($usr->name === ($deal->lead_consultant ?: $deal->owner_name ?: 'John Kelly Abalde'))>{{ $usr->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <span style="font-size: 11px; font-weight: 600; color: #475569;">Lead Associate</span>
                            <select id="csbAssigneeAssociate" class="inquiry-form-select" style="height: 36px; border-radius: 6px; font-size: 12px; margin-top: 3px;">
                                @foreach($users as $usr)
                                    <option value="{{ $usr->name }}" @selected($usr->name === ($deal->lead_associate ?: 'Ma. Lourdes T. Mata'))>{{ $usr->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Special Operational Notes / Instructions</label>
                    <textarea id="csbNotesInput" class="inquiry-form-textarea" rows="2" placeholder="Specific guidelines or deliverables for this batch..." style="border-radius: 7px; font-size: 12px;"></textarea>
                </div>

            </div>

            <div class="finance-modal-footer" style="padding: 14px 24px; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: flex-end; gap: 10px; background: #ffffff; flex-shrink: 0;">
                <button type="button" class="finance-btn-cancel-modal" onclick="closeCreateStartBatchModal()">Cancel</button>
                <button type="submit" class="start-btn-primary" style="padding: 8px 22px; font-size: 13px; font-weight: 600;">Create</button>
            </div>
        </form>
    </div>
</div>

{{-- =========================================================
     MODAL 3: DECLINE / CANNOT ACCEPT ASSIGNMENT MODAL
     ========================================================= --}}
<div id="startDeclineModal" class="start-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeStartDeclineModal()">
    <div class="start-modal-card confirm-dialog" style="max-width: 480px;">
        <div class="start-modal-head">
            <h3 class="start-modal-main-title" style="color: #be123c;">Cannot Accept Assignment</h3>
            <button type="button" class="start-modal-close-btn" onclick="closeStartDeclineModal()">&times;</button>
        </div>
        <form id="startDeclineForm" onsubmit="handleConfirmDeclineAssignment(event)">
            <input type="hidden" id="startDeclineAssignmentId" value="">
            <div class="start-modal-body-scroll">
                <p style="font-size: 13px; color: #475569; margin: 0 0 14px; line-height: 1.4;">
                    Please select the reason you cannot accept this assignment.
                </p>
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Reason for Declining Assignment <span class="req-star">*</span></label>
                    <select id="startDeclineReasonSelect" class="inquiry-form-select" required onchange="handleDeclineReasonSelectChange(this)">
                        <option value="" disabled selected>Select a reason</option>
                        <option value="Schedule Conflict">Schedule Conflict</option>
                        <option value="Current Workload / Capacity">Current Workload / Capacity</option>
                        <option value="Out of Office / Leave">Out of Office / Leave</option>
                        <option value="Required Skills or Expertise Not Available">Required Skills or Expertise Not Available</option>
                        <option value="Role or Responsibility Conflict">Role or Responsibility Conflict</option>
                        <option value="Conflict of Interest">Conflict of Interest</option>
                        <option value="Assignment Timing Not Suitable">Assignment Timing Not Suitable</option>
                        <option value="Already Assigned to Another Priority">Already Assigned to Another Priority</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="inquiry-form-group" id="startDeclineOtherReasonWrapper" style="display: none; margin-top: 14px;">
                    <label class="inquiry-form-label">Please specify <span class="req-star">*</span></label>
                    <textarea id="startDeclineOtherReasonInput" class="inquiry-form-textarea" rows="3" placeholder="Provide a brief explanation..."></textarea>
                </div>
            </div>
            <div class="finance-modal-footer">
                <button type="button" class="finance-btn-cancel-modal" onclick="closeStartDeclineModal()">Cancel</button>
                <button type="submit" class="btn-assign-decline">Confirm Decline</button>
            </div>
        </form>
    </div>
</div>

{{-- =========================================================
     MODAL 4: REASSIGN ROLE MODAL
     ========================================================= --}}
<div id="startReassignModal" class="start-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeStartReassignModal()">
    <div class="start-modal-card" style="max-width: 480px;">
        <div class="start-modal-head">
            <h3 class="start-modal-main-title">Reassign Role</h3>
            <button type="button" class="start-modal-close-btn" onclick="closeStartReassignModal()">&times;</button>
        </div>
        <form id="startReassignForm" onsubmit="handleConfirmReassign(event)">
            <input type="hidden" id="startReassignAssignmentId" value="">
            <div class="start-modal-body-scroll">
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Role</label>
                    <input type="text" id="startReassignRoleLabel" class="inquiry-form-input" readonly style="background: #f8fafc; font-weight: 600;">
                </div>
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Select New Assignee <span class="req-star">*</span></label>
                    <select id="startReassignNewUserSelect" class="inquiry-form-select" required style="height: 38px;">
                        @foreach($users as $usr)
                            <option value="{{ $usr->name }}">{{ $usr->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Reassignment Notes</label>
                    <textarea id="startReassignNotesInput" class="inquiry-form-textarea" rows="2" placeholder="Optional notes for the new assignee..."></textarea>
                </div>
            </div>
            <div class="finance-modal-footer">
                <button type="button" class="finance-btn-cancel-modal" onclick="closeStartReassignModal()">Cancel</button>
                <button type="submit" class="start-btn-primary">Reassign Role</button>
            </div>
        </form>
    </div>
</div>

{{-- =========================================================
     MODAL 5: RETURN START BATCH MODAL
     ========================================================= --}}
<div id="startReturnModal" class="start-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeStartReturnModal()">
    <div class="start-modal-card" style="max-width: 480px;">
        <div class="start-modal-head">
            <h3 class="start-modal-main-title" style="color: #991b1b;">Return START for Correction</h3>
            <button type="button" class="start-modal-close-btn" onclick="closeStartReturnModal()">&times;</button>
        </div>
        <form id="startReturnForm" onsubmit="handleConfirmReturnBatch(event)">
            <div class="start-modal-body-scroll">
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Correction Details / Feedback <span class="req-star">*</span></label>
                    <textarea id="startReturnReasonInput" class="inquiry-form-textarea" rows="3" placeholder="Specify items or assignments that need correction..." required></textarea>
                </div>
            </div>
            <div class="finance-modal-footer">
                <button type="button" class="finance-btn-cancel-modal" onclick="closeStartReturnModal()">Cancel</button>
                <button type="submit" class="start-btn-primary" style="background: #dc2626; border-color: #dc2626;">Return START</button>
            </div>
        </form>
    </div>
</div>

{{-- =========================================================
     MODAL 6: CANCEL START BATCH MODAL
     ========================================================= --}}
<div id="startCancelModal" class="start-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeStartCancelModal()">
    <div class="start-modal-card" style="max-width: 480px;">
        <div class="start-modal-head">
            <h3 class="start-modal-main-title" style="color: #475569;">Cancel START Batch</h3>
            <button type="button" class="start-modal-close-btn" onclick="closeStartCancelModal()">&times;</button>
        </div>
        <form id="startCancelForm" onsubmit="handleConfirmCancelBatch(event)">
            <div class="start-modal-body-scroll">
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Cancellation Reason <span class="req-star">*</span></label>
                    <textarea id="startCancelReasonInput" class="inquiry-form-textarea" rows="3" placeholder="Provide reason for cancelling activation..." required></textarea>
                </div>
            </div>
            <div class="finance-modal-footer">
                <button type="button" class="finance-btn-cancel-modal" onclick="closeStartCancelModal()">Cancel</button>
                <button type="submit" class="start-btn-secondary" style="color: #dc2626 !important;">Cancel Activation</button>
            </div>
        </form>
    </div>
</div>

{{-- =========================================================
     MODAL 7: SERVICE MEMO AMENDMENT / REVISION MODAL
     ========================================================= --}}
<div id="startMemoAmendmentModal" class="start-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeStartMemoAmendmentModal()">
    <div class="start-modal-card" style="max-width: 540px;">
        <div class="start-modal-head">
            <h3 class="start-modal-main-title">Create Service Memo Amendment</h3>
            <button type="button" class="start-modal-close-btn" onclick="closeStartMemoAmendmentModal()">&times;</button>
        </div>
        <form id="startMemoAmendmentForm" onsubmit="handleConfirmMemoAmendment(event)">
            <div class="start-modal-body-scroll">
                <p style="font-size: 12.5px; color: #64748b; margin: 0 0 14px;">
                    Issued Service Memos are immutable. An amendment will create a new tracked revision while preserving the original issued document.
                </p>
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Reason for Amendment <span class="req-star">*</span></label>
                    <textarea id="startAmendmentReasonInput" class="inquiry-form-textarea" rows="3" placeholder="e.g. Scope adjustment or timeline update requested by client..." required></textarea>
                </div>
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Updated Special Instructions</label>
                    <textarea id="startAmendmentInstructionsInput" class="inquiry-form-textarea" rows="2" placeholder="Updated instructions if any..."></textarea>
                </div>
            </div>
            <div class="finance-modal-footer">
                <button type="button" class="finance-btn-cancel-modal" onclick="closeStartMemoAmendmentModal()">Cancel</button>
                <button type="submit" class="start-btn-primary">Save Amendment</button>
            </div>
        </form>
    </div>
</div>

{{-- =========================================================
     MODAL 8: ADD ASSIGNMENT ROLE MODAL
     ========================================================= --}}
<div id="startAddAssignmentModal" class="start-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeStartAddAssignmentModal()">
    <div class="start-modal-card" style="max-width: 480px;">
        <div class="start-modal-head">
            <h3 class="start-modal-main-title">Add Role Assignment</h3>
            <button type="button" class="start-modal-close-btn" onclick="closeStartAddAssignmentModal()">&times;</button>
        </div>
        <form id="startAddAssignmentForm" onsubmit="handleSaveNewAssignment(event)">
            <div class="start-modal-body-scroll">
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Role / Service Responsibility <span class="req-star">*</span></label>
                    <input type="text" id="startNewAssignRoleInput" class="inquiry-form-input" placeholder="e.g. Audit Support / Coordination" required>
                </div>
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Assignee Name <span class="req-star">*</span></label>
                    <select id="startNewAssigneeSelect" class="inquiry-form-select" required style="height: 38px;">
                        @foreach($users as $usr)
                            <option value="{{ $usr->name }}">{{ $usr->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="inquiry-form-group">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155; cursor: pointer;">
                        <input type="checkbox" id="startNewAssignRequired" checked>
                        <span>Required Assignment (Mandatory for Team Confirmation)</span>
                    </label>
                </div>
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Notes</label>
                    <textarea id="startNewAssignNotes" class="inquiry-form-textarea" rows="2" placeholder="Optional notes on assignment responsibilities..."></textarea>
                </div>
            </div>
            <div class="finance-modal-footer">
                <button type="button" class="finance-btn-cancel-modal" onclick="closeStartAddAssignmentModal()">Cancel</button>
                <button type="submit" class="start-btn-primary">Add Assignment</button>
            </div>
        </form>
    </div>
</div>

{{-- =========================================================
     MODAL 9: REQUEST CLIENT ACTION MODAL
     ========================================================= --}}
<div id="clientActionModal" class="start-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeClientActionModal()">
    <div class="start-modal-card" style="max-width: 600px;">
        <div class="start-modal-head">
            <h3 class="start-modal-main-title">Request Client Action</h3>
            <button type="button" class="start-modal-close-btn" onclick="closeClientActionModal()">&times;</button>
        </div>
        <form id="clientActionForm" onsubmit="handleClientActionSubmit(event)">
            <div class="start-modal-body-scroll" style="padding: 20px 24px;">
                
                {{-- Request Type --}}
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Request Type <span class="req-star">*</span></label>
                    <select id="clientActionRequestTypeSelect" class="inquiry-form-select" required onchange="updateClientActionDocumentOptions()">
                        <optgroup label="Proposal &amp; Commercial">
                            <option value="Review &amp; Accept Proposal">Review &amp; Accept Proposal</option>
                            <option value="Select Proposal Items">Select Proposal Items</option>
                            <option value="Request Discount / Price Adjustment">Request Discount / Price Adjustment</option>
                            <option value="Request Scope / Terms Change">Request Scope / Terms Change</option>
                            <option value="Commercial Revision / Addendum">Commercial Revision / Addendum</option>
                        </optgroup>
                        <optgroup label="Notice to Proceed">
                            <option value="Approve Notice to Proceed (NTP)">Approve Notice to Proceed (NTP)</option>
                            <option value="Request NTP Revision">Request NTP Revision</option>
                        </optgroup>
                        <optgroup label="Project &amp; Retainer Scope">
                            <option value="Approve Project SOW Revision">Approve Project SOW Revision</option>
                            <option value="Approve Recurring Retainer Scope">Approve Recurring Retainer Scope</option>
                        </optgroup>
                        <optgroup label="Delivery &amp; Completion">
                            <option value="Acknowledge Transmittal Receipt">Acknowledge Transmittal Receipt</option>
                            <option value="Confirm Completion &amp; Sign-Off">Confirm Completion &amp; Sign-Off</option>
                        </optgroup>
                        <optgroup label="General &amp; Legal">
                            <option value="Digital / Online Sign">Digital / Online Sign</option>
                            <option value="Upload Required Document">Upload Required Document</option>
                            <option value="Acknowledge Terms">Acknowledge Terms</option>
                            <option value="Confirm Details">Confirm Details</option>
                            <option value="Form Declaration">Form Declaration</option>
                        </optgroup>
                    </select>
                </div>

                {{-- Document --}}
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Document <span class="req-star">*</span></label>
                    <div id="clientActionDocSelectWrap">
                        <select id="clientActionDocSelect" class="inquiry-form-select" required>
                            {{-- Dynamically populated --}}
                        </select>
                    </div>
                </div>

                {{-- Delivery Method --}}
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Delivery Method <span class="req-star">*</span></label>
                    <select id="clientActionDeliveryMethodSelect" class="inquiry-form-select" required>
                        <option value="Secure No-Login Link">Secure No-Login Link</option>
                        <option value="Client Portal">Client Portal</option>
                        <option value="Email Tracked Request">Email Tracked Request</option>
                        <option value="Manual / Wet Signature">Manual / Wet Signature</option>
                        <option value="External Signing">External Signing</option>
                    </select>
                </div>

                {{-- Message to Client --}}
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Message to Client</label>
                    <textarea id="clientActionMessageInput" class="inquiry-form-textarea" rows="3" placeholder="Enter personalized instructions or context for the client..."></textarea>
                </div>

                {{-- Due Date --}}
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Due Date</label>
                    <input type="date" id="clientActionDueDateInput" class="inquiry-form-input" value="{{ now()->addDays(7)->format('Y-m-d') }}">
                </div>

                {{-- Additional Notes --}}
                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Additional Notes</label>
                    <textarea id="clientActionNotesInput" class="inquiry-form-textarea" rows="2" placeholder="Internal tracking notes or remarks..."></textarea>
                </div>

            </div>
            <div class="finance-modal-footer">
                <button type="button" class="finance-btn-cancel-modal" onclick="closeClientActionModal()">Cancel</button>
                <button type="submit" class="start-btn-primary">Send Request</button>
            </div>
        </form>
    </div>
</div>

{{-- =========================================================
     MODAL 10: CLIENT ACTION DETAILS MODAL
     ========================================================= --}}
<div id="clientActionDetailsModal" class="start-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeClientActionDetailsModal()">
    <div class="start-modal-card" style="max-width: 620px; border-radius: 12px; overflow: hidden;">
        <div class="start-modal-head" style="padding: 20px 24px 16px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <h3 class="start-modal-main-title" id="cadModalTitle" style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0;">Client Action Request</h3>
                <span id="cadModalStatusBadge" class="client-action-pill client-action-pill-pending">Pending</span>
            </div>
            <button type="button" class="start-modal-close-btn" onclick="closeClientActionDetailsModal()">&times;</button>
        </div>
        <div class="start-modal-body-scroll" style="padding: 20px 24px;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px; margin-bottom: 16px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px 24px; font-size: 13px;">
                    <div>
                        <div style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Request Type</div>
                        <div id="cadDetailRequestType" style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin-top: 3px;">-</div>
                    </div>
                    <div>
                        <div style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Document</div>
                        <div id="cadDetailDocument" style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin-top: 3px;">-</div>
                    </div>
                    <div>
                        <div style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Delivery Method</div>
                        <div id="cadDetailDeliveryMethod" style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin-top: 3px;">-</div>
                    </div>
                    <div>
                        <div style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Requested Date</div>
                        <div id="cadDetailRequestedDate" style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin-top: 3px;">-</div>
                    </div>
                </div>
            </div>

            {{-- Status Control Row --}}
            <div style="margin-bottom: 16px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; gap: 14px;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">Request Status</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Change or update the lifecycle status.</div>
                </div>
                <select id="cadStatusSelect" class="inquiry-form-select" style="width: auto; min-width: 140px; font-weight: 700; padding: 6px 12px; font-size: 13px; border-radius: 6px; border: 1px solid #cbd5e1;" onchange="handleClientActionStatusChange(this.value)">
                    <option value="Pending">● Pending</option>
                    <option value="In Progress">● In Progress</option>
                    <option value="Completed">● Completed</option>
                    <option value="Declined">● Declined</option>
                </select>
            </div>

            {{-- Secure Link Access Box --}}
            <div style="margin-bottom: 16px;">
                <label style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;">Client Access Link</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" id="cadDetailLinkInput" readonly style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 12px; font-family: monospace; font-size: 12px; color: #334155; flex: 1; outline: none;">
                    <button type="button" class="files-action-btn-view" onclick="copyClientActionLink()" style="white-space: nowrap; height: 34px; padding: 0 14px; font-size: 12.5px; font-weight: 600;">Copy Link</button>
                </div>
            </div>

            {{-- Message to Client --}}
            <div style="margin-bottom: 14px;">
                <div style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Message to Client</div>
                <div id="cadDetailMessage" style="font-size: 13px; color: #1e293b; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; min-height: 40px; line-height: 1.45;">-</div>
            </div>

            {{-- Notes --}}
            <div style="margin-bottom: 14px;">
                <div style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Internal Notes</div>
                <div id="cadDetailNotes" style="font-size: 13px; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; line-height: 1.45;">-</div>
            </div>
        </div>
        <div class="finance-modal-footer" style="padding: 14px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
            <button type="button" class="files-action-btn-view" style="color: #dc2626; border-color: #fecaca; background: #fef2f2; font-size: 12.5px; font-weight: 600; padding: 7px 14px;" onclick="handleDeleteCurrentClientAction()">Delete Request</button>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="files-action-btn-view" id="cadToggleStatusBtn" onclick="toggleClientActionStatus()" style="font-size: 12.5px; font-weight: 600; padding: 7px 16px;">Mark as Completed</button>
                <button type="button" class="start-btn-primary" onclick="closeClientActionDetailsModal()" style="font-size: 12.5px; font-weight: 600; padding: 7px 20px;">Done</button>
            </div>
        </div>
    </div>
</div>

{{-- =========================================================
     MODAL: PREVIEW QUOTATION DOCUMENT
     ========================================================= --}}
<div id="previewQuotationModal" class="start-modal-backdrop" style="display: none; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); z-index: 100010;" onclick="if(event.target === this) closePreviewQuotationModal()">
    <div class="start-modal-card" style="max-width: 880px; background: #f1f5f9; border-radius: 12px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35); overflow: hidden; display: flex; flex-direction: column; max-height: 94vh; width: 95%;">
        {{-- Header Bar --}}
        <div class="start-modal-head" style="background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 12px 24px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="start-header-icon-box" style="width: 32px; height: 32px; border-radius: 8px; background: #eff6ff; color: #1d68e1;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                </span>
                <div>
                    <h3 style="font-size: 15px; font-weight: 800; color: #002b66; margin: 0; display: inline-flex; align-items: center; gap: 8px;">
                        <span>Official Quotation</span>
                        <span class="start-modal-code-badge" style="font-size: 11.5px;">{{ $deal->deal_code ?: ('QUOT-' . date('Y') . '-' . str_pad($deal->id, 3, '0', STR_PAD_LEFT)) }}</span>
                    </h3>
                </div>
            </div>

            <div class="start-modal-head-actions" style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="start-btn-primary" onclick="printQuotationDocument()" style="background: #002b66; border-color: #002b66; padding: 6px 14px; font-size: 12.5px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    <span>Print Quotation</span>
                </button>
                <button type="button" class="start-modal-close-btn" onclick="closePreviewQuotationModal()">&times;</button>
            </div>
        </div>

        {{-- Paper Content --}}
        <div class="start-modal-body-scroll" style="background: #f1f5f9; padding: 24px 20px; overflow-y: auto;">
            <div id="previewQuotationPaperContainer" style="margin: 0 auto; max-width: 800px; box-shadow: 0 4px 20px rgba(0, 43, 102, 0.08); background: #ffffff; padding: 36px 40px; border-radius: 8px; border: 1px solid #e2e8f0;">
                {{-- Brand Header --}}
                <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #002b66; padding-bottom: 20px; margin-bottom: 24px;">
                    <div>
                        <div style="font-size: 22px; font-weight: 900; color: #002b66; letter-spacing: -0.5px;">ORDO CONSULTING</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Professional Tax, Accounting &amp; Advisory Services</div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Unit 1204, Tower One, Ayala Triangle, Makati City, Philippines</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 20px; font-weight: 800; color: #1e4f95; text-transform: uppercase;">QUOTATION</div>
                        <div style="font-size: 12px; font-weight: 700; color: #0f172a; margin-top: 4px;">Ref #: {{ $deal->deal_code ?: ('QUOT-' . date('Y') . '-' . str_pad($deal->id, 3, '0', STR_PAD_LEFT)) }}</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Date: {{ date('M d, Y') }}</div>
                        <div style="font-size: 12px; color: #64748b;">Valid Until: {{ date('M d, Y', strtotime('+30 days')) }}</div>
                    </div>
                </div>

                {{-- Client Info Grid --}}
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px;">
                    <div>
                        <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Client / Recipient</div>
                        <div style="font-size: 14px; font-weight: 700; color: #0f172a;">{{ $deal->company_name ?: ($deal->deal_title ?: 'Client Account') }}</div>
                        <div style="font-size: 12px; color: #475569; margin-top: 2px;">Attention: {{ $contactName ?: ($deal->contact_name ?: 'Authorized Representative') }}</div>
                        @if($deal->contact_email)
                            <div style="font-size: 12px; color: #475569;">Email: {{ $deal->contact_email }}</div>
                        @endif
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Engagement Overview</div>
                        <div style="font-size: 13px; font-weight: 600; color: #0f172a;">Deal: {{ $deal->deal_title }}</div>
                        <div style="font-size: 12px; color: #475569; margin-top: 2px;">Pricing Model: {{ $display($deal->pricing_model ?? $deal->engagement_type ?? 'Fixed Scope') }}</div>
                        <div style="font-size: 12px; color: #475569;">Payment Terms: {{ $display($deal->payment_terms ?? 'Progressive Allocation') }}</div>
                    </div>
                </div>

                {{-- Quotation Items Table --}}
                <div style="margin-bottom: 24px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #002b66; color: #ffffff;">
                                <th style="padding: 10px 12px; font-size: 12px; font-weight: 700; text-align: center; width: 40px; border-top-left-radius: 6px;">#</th>
                                <th style="padding: 10px 12px; font-size: 12px; font-weight: 700; text-align: left;">Scope / Deliverable Description</th>
                                <th style="padding: 10px 12px; font-size: 12px; font-weight: 700; text-align: center; width: 80px;">Qty</th>
                                <th style="padding: 10px 12px; font-size: 12px; font-weight: 700; text-align: right; width: 130px;">Unit Fee</th>
                                <th style="padding: 10px 12px; font-size: 12px; font-weight: 700; text-align: right; width: 140px; border-top-right-radius: 6px;">Total Fee (PHP)</th>
                            </tr>
                        </thead>
                        <tbody id="previewQuotationTableBody">
                            @foreach($financeProposalItems as $idx => $pItem)
                                <tr>
                                    <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569; text-align: center;">{{ $idx + 1 }}</td>
                                    <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 600; color: #0f172a;">
                                        {{ $pItem['name'] }}
                                        <div style="font-size: 11px; color: #64748b; font-weight: normal; margin-top: 2px;">Code: {{ $pItem['code'] }}</div>
                                    </td>
                                    <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569; text-align: center;">1 Lot</td>
                                    <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 700; color: #07162d; text-align: right;">{{ $money($pItem['price']) }}</td>
                                    <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 700; color: #1e4f95; text-align: right;">{{ $money($pItem['price']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Totals Summary --}}
                <div style="display: flex; justify-content: flex-end; margin-bottom: 24px;">
                    <div style="width: 320px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #475569; margin-bottom: 8px;">
                            <span>Subtotal Fee:</span>
                            <span id="previewQuotationSubtotal" style="font-weight: 600; color: #0f172a;">{{ $money($proposalTotal > 0 ? $proposalTotal : ($deal->amount ?: $deal->total_estimated_engagement_value)) }}</span>
                        </div>
                        @if((float)($deal->discount ?: 0) > 0)
                            <div style="display: flex; justify-content: space-between; font-size: 13px; color: #dc2626; margin-bottom: 8px;">
                                <span>Discount:</span>
                                <span style="font-weight: 600;">- {{ $money($deal->discount) }}</span>
                            </div>
                        @endif
                        <div style="display: flex; justify-content: space-between; font-size: 15px; font-weight: 800; color: #002b66; border-top: 2px solid #cbd5e1; padding-top: 10px; margin-top: 6px;">
                            <span>Grand Total:</span>
                            <span id="previewQuotationGrandTotal" style="color: #1e4f95;">{{ $money($proposalTotal > 0 ? $proposalTotal : ($deal->amount ?: $deal->total_estimated_engagement_value)) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Terms & Conditions Footer --}}
                <div style="border-top: 1px solid #e2e8f0; padding-top: 18px; font-size: 11.5px; color: #64748b; line-height: 1.6;">
                    <div style="font-weight: 700; color: #334155; margin-bottom: 4px; text-transform: uppercase;">Quotation Notes &amp; Commercial Terms:</div>
                    <div>1. This quotation is valid for 30 calendar days from the date of issue.</div>
                    <div>2. Fees quoted are subject to standard payment schedules and engagement confirmation upon signature.</div>
                    <div>3. Progressive START activations are commenced upon payment allocation for specific scope items.</div>
                </div>

                <div style="display: flex; justify-content: space-between; margin-top: 36px; padding-top: 20px; border-top: 1px dashed #cbd5e1;">
                    <div>
                        <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;">Prepared By:</div>
                        <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 24px;">{{ $financeLoggedUser }}</div>
                        <div style="font-size: 11px; color: #64748b;">Finance / Deal Engagement Team</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;">Client Conforme / Approved By:</div>
                        <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 24px;">{{ $contactName ?: ($deal->contact_name ?: 'Authorized Signatory') }}</div>
                        <div style="font-size: 11px; color: #64748b;">{{ $deal->company_name ?: ($deal->deal_title ?: 'Client Company') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- =========================================================
     MODAL: PREVIEW PAYMENT NOTICE DOCUMENT
     ========================================================= --}}
<div id="previewPaymentNoticeModal" class="start-modal-backdrop" style="display: none; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); z-index: 100010;" onclick="if(event.target === this) closePreviewPaymentNoticeModal()">
    <div class="start-modal-card" style="max-width: 880px; background: #f1f5f9; border-radius: 12px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35); overflow: hidden; display: flex; flex-direction: column; max-height: 94vh; width: 95%;">
        {{-- Header Bar --}}
        <div class="start-modal-head" style="background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 12px 24px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="start-header-icon-box" style="width: 32px; height: 32px; border-radius: 8px; background: #f0fdf4; color: #16a34a;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                        <line x1="6" y1="14" x2="10" y2="14"></line>
                    </svg>
                </span>
                <div>
                    <h3 style="font-size: 15px; font-weight: 800; color: #002b66; margin: 0; display: inline-flex; align-items: center; gap: 8px;">
                        <span>Payment Notice &amp; Allocation Advice</span>
                        <span class="start-modal-code-badge" style="font-size: 11.5px; background: #ecfdf5; color: #059669; border-color: #a7f3d0;">{{ $deal->deal_code ? ('PN-' . $deal->deal_code) : ('PN-' . date('Y') . '-' . str_pad($deal->id, 3, '0', STR_PAD_LEFT)) }}</span>
                    </h3>
                </div>
            </div>

            <div class="start-modal-head-actions" style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="start-btn-primary" onclick="printPaymentNoticeDocument()" style="background: #002b66; border-color: #002b66; padding: 6px 14px; font-size: 12.5px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    <span>Print Notice</span>
                </button>
                <button type="button" class="start-modal-close-btn" onclick="closePreviewPaymentNoticeModal()">&times;</button>
            </div>
        </div>

        {{-- Paper Content --}}
        <div class="start-modal-body-scroll" style="background: #f1f5f9; padding: 24px 20px; overflow-y: auto;">
            <div id="previewPaymentNoticePaperContainer" style="margin: 0 auto; max-width: 800px; box-shadow: 0 4px 20px rgba(0, 43, 102, 0.08); background: #ffffff; padding: 36px 40px; border-radius: 8px; border: 1px solid #e2e8f0;">
                {{-- Brand Header --}}
                <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #002b66; padding-bottom: 20px; margin-bottom: 24px;">
                    <div>
                        <div style="font-size: 22px; font-weight: 900; color: #002b66; letter-spacing: -0.5px;">ORDO FINANCE DESK</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Billing, Collections &amp; Payment Verification</div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Email: finance@ordo.ph • Phone: +63 (2) 8888-0000</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 20px; font-weight: 800; color: #059669; text-transform: uppercase;">PAYMENT NOTICE</div>
                        <div style="font-size: 12px; font-weight: 700; color: #0f172a; margin-top: 4px;">Notice #: {{ $deal->deal_code ? ('PN-' . $deal->deal_code) : ('PN-' . date('Y') . '-' . str_pad($deal->id, 3, '0', STR_PAD_LEFT)) }}</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Issue Date: {{ date('M d, Y') }}</div>
                        <div style="font-size: 12px; color: #dc2626; font-weight: 600;">Payment Terms: {{ $display($deal->payment_terms ?? 'Progressive Allocation') }}</div>
                    </div>
                </div>

                {{-- Outstanding Amount Highlight Banner --}}
                <div style="background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 11px; font-weight: 700; color: #15803d; text-transform: uppercase;">Payment Notice For</div>
                        <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 2px;">{{ $deal->company_name ?: ($deal->deal_title ?: 'Client Account') }}</div>
                        <div style="font-size: 12px; color: #475569;">Deal Ref: {{ $deal->deal_code ?: ('DEAL-' . $deal->id) }} • Attn: {{ $contactName ?: ($deal->contact_name ?: 'Billing Contact') }}</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 11px; font-weight: 700; color: #15803d; text-transform: uppercase;">Current Balance Due</div>
                        <div id="previewPnDueAmountBanner" style="font-size: 22px; font-weight: 900; color: #16a34a; margin-top: 2px;">{{ $money($proposalTotal > 0 ? $proposalTotal : ($deal->amount ?: $deal->total_estimated_engagement_value)) }}</div>
                    </div>
                </div>

                {{-- Allocation & Outstanding Schedule Table --}}
                <div style="margin-bottom: 24px;">
                    <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Item-Level Fee &amp; Payment Status Breakdown:</div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #002b66; color: #ffffff;">
                                <th style="padding: 10px 12px; font-size: 12px; font-weight: 700; text-align: center; width: 40px; border-top-left-radius: 6px;">#</th>
                                <th style="padding: 10px 12px; font-size: 12px; font-weight: 700; text-align: left;">Service Deliverable</th>
                                <th style="padding: 10px 12px; font-size: 12px; font-weight: 700; text-align: right; width: 120px;">Total Fee</th>
                                <th style="padding: 10px 12px; font-size: 12px; font-weight: 700; text-align: right; width: 120px;">Allocated / Paid</th>
                                <th style="padding: 10px 12px; font-size: 12px; font-weight: 700; text-align: right; width: 120px;">Balance Due</th>
                                <th style="padding: 10px 12px; font-size: 12px; font-weight: 700; text-align: center; width: 120px; border-top-right-radius: 6px;">Status</th>
                            </tr>
                        </thead>
                        <tbody id="previewPaymentNoticeTableBody">
                            @foreach($financeProposalItems as $idx => $pItem)
                                <tr>
                                    <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569; text-align: center;">{{ $idx + 1 }}</td>
                                    <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 600; color: #0f172a;">
                                        {{ $pItem['name'] }}
                                        <div style="font-size: 11px; color: #64748b; font-weight: normal; margin-top: 2px;">Ref: {{ $pItem['code'] }}</div>
                                    </td>
                                    <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 600; color: #475569; text-align: right;">{{ $money($pItem['price']) }}</td>
                                    <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 700; color: #047857; text-align: right;">₱0.00</td>
                                    <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 700; color: #dc2626; text-align: right;">{{ $money($pItem['price']) }}</td>
                                    <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; text-align: center;">
                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700; background: #fef2f2; color: #dc2626;">Payment Pending</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Totals Row --}}
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                    {{-- Official Bank Payment Details --}}
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                        <div style="font-size: 12px; font-weight: 700; color: #002b66; text-transform: uppercase; margin-bottom: 8px;">Official Bank Payment Channels:</div>
                        <div style="font-size: 12px; color: #334155; line-height: 1.6;">
                            <div><strong>Bank:</strong> Bank of the Philippine Islands (BPI)</div>
                            <div><strong>Account Name:</strong> Ordo Business Advisory Inc.</div>
                            <div><strong>Account Number:</strong> 0012-3456-78</div>
                            <div style="margin-top: 6px; font-size: 11px; color: #64748b;">Please send proof of transfer/deposit to <strong>finance@ordo.ph</strong> or upload via Finance Tab.</div>
                        </div>
                    </div>

                    {{-- Summary Box --}}
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #475569; margin-bottom: 8px;">
                            <span>Total Engagement Fee:</span>
                            <span id="previewPnTotalFee" style="font-weight: 600; color: #0f172a;">{{ $money($proposalTotal > 0 ? $proposalTotal : ($deal->amount ?: $deal->total_estimated_engagement_value)) }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #047857; margin-bottom: 8px;">
                            <span>Total Paid / Allocated:</span>
                            <span id="previewPnTotalAllocated" style="font-weight: 700;">₱0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 15px; font-weight: 800; color: #dc2626; border-top: 2px solid #cbd5e1; padding-top: 10px; margin-top: 6px;">
                            <span>Outstanding Balance:</span>
                            <span id="previewPnTotalBalance" style="color: #dc2626;">{{ $money($proposalTotal > 0 ? $proposalTotal : ($deal->amount ?: $deal->total_estimated_engagement_value)) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Attached Uploaded Payment Proofs & Pictures --}}
                <div id="previewPnAttachmentsBox" style="margin-bottom: 24px; padding: 14px 18px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                    <div style="font-size: 12.5px; font-weight: 700; color: #0f172a; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                        <span>Attached Payment Proofs &amp; Uploaded Receipts:</span>
                    </div>
                    <div id="previewPnAttachmentsList" style="display: flex; flex-wrap: wrap; gap: 10px;">
                        {{-- Populated dynamically --}}
                    </div>
                </div>

                {{-- Sign-off --}}
                <div style="display: flex; justify-content: space-between; margin-top: 30px; padding-top: 18px; border-top: 1px dashed #cbd5e1;">
                    <div>
                        <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;">Issued By Finance Desk:</div>
                        <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 20px;">{{ $financeLoggedUser }}</div>
                        <div style="font-size: 11px; color: #64748b;">Finance Officer / Credit &amp; Collections</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;">Deal / Relationship Lead:</div>
                        <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 20px;">{{ $deal->owner_name ?: 'Account Manager' }}</div>
                        <div style="font-size: 11px; color: #64748b;">Client Relations Department</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- =========================================================
     MODAL: FINANCE / PAYMENT RECEIPT ATTACHMENT VIEWER
     ========================================================= --}}
<div id="financeAttachmentViewerModal" class="start-modal-backdrop" style="display: none; background: rgba(15, 23, 42, 0.88); backdrop-filter: blur(8px); z-index: 100050; align-items: center; justify-content: center; position: fixed; inset: 0;" onclick="if(event.target === this) closeFinanceAttachmentViewer()">
    <div class="start-modal-card" style="max-width: 1080px; width: 95%; background: #ffffff; border-radius: 16px; box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.5); overflow: hidden; display: flex; flex-direction: column; max-height: 94vh; border: 1px solid rgba(255,255,255,0.15);">
        {{-- Modal Header Toolbar --}}
        <div style="background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 10px 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                <span style="width: 36px; height: 36px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 2px 6px rgba(37,99,235,0.15);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                </span>
                <div style="min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <h3 id="financeViewerTitle" style="font-size: 14.5px; font-weight: 700; color: #0f172a; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Official Receipt Attachment</h3>
                        <span id="financeViewerCounterBadge" style="display: none; font-size: 11px; font-weight: 700; color: #2563eb; background: #eff6ff; border: 1px solid #bfdbfe; padding: 1px 7px; border-radius: 9999px;">1 / 1</span>
                    </div>
                    <p id="financeViewerSubtitle" style="font-size: 11.5px; color: #64748b; margin: 2px 0 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Proof of payment preview</p>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 5px; flex-wrap: wrap;">
                {{-- Zoom Controls for Images --}}
                <div id="financeViewerZoomControls" style="display: flex; align-items: center; gap: 2px; background: #f1f5f9; padding: 2px 4px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <button type="button" onclick="changeFinanceViewerZoom(-0.2)" title="Zoom Out (-)" style="background: transparent; border: none; padding: 4px 6px; border-radius: 4px; cursor: pointer; color: #475569; display: flex; align-items: center;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"></circle><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                    </button>
                    <button type="button" id="financeViewerZoomLabel" onclick="resetFinanceViewerZoom()" title="Click to Reset Zoom (0)" style="background: transparent; border: none; padding: 3px 6px; font-size: 11px; font-weight: 700; border-radius: 4px; cursor: pointer; color: #1e293b; min-width: 38px; text-align: center;">
                        100%
                    </button>
                    <button type="button" onclick="changeFinanceViewerZoom(0.2)" title="Zoom In (+)" style="background: transparent; border: none; padding: 4px 6px; border-radius: 4px; cursor: pointer; color: #475569; display: flex; align-items: center;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"></circle><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                    </button>
                    <div style="width: 1px; height: 14px; background: #cbd5e1; margin: 0 2px;"></div>
                    <button type="button" onclick="rotateFinanceViewerImage()" title="Rotate 90° Clockwise (R)" style="background: transparent; border: none; padding: 4px 6px; border-radius: 4px; cursor: pointer; color: #475569; display: flex; align-items: center;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                    </button>
                    <button type="button" onclick="flipFinanceViewerImage()" title="Flip Horizontal (F)" style="background: transparent; border: none; padding: 4px 6px; border-radius: 4px; cursor: pointer; color: #475569; display: flex; align-items: center;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M8 3H5a2 2 0 0 0-2 2v14c0 1.1.9 2 2 2h3m8-18h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3M12 2v20"/></svg>
                    </button>
                    <button type="button" id="financeViewerFilterBtn" onclick="toggleFinanceViewerFilter()" title="Toggle High Clarity Scanner Filter (C)" style="background: transparent; border: none; padding: 4px 6px; border-radius: 4px; cursor: pointer; color: #475569; display: flex; align-items: center;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                    </button>
                </div>

                {{-- Toggle Details Panel --}}
                <button type="button" id="financeViewerInfoBtn" onclick="toggleFinanceViewerInfoPanel()" title="Toggle Receipt & Payment Details (I)" style="display: inline-flex; align-items: center; gap: 4px; background: #f8fafc; border: 1px solid #cbd5e1; color: #334155; padding: 5px 9px; border-radius: 8px; font-size: 11.5px; font-weight: 600; cursor: pointer; transition: all 0.15s ease;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    <span>Details</span>
                </button>

                {{-- Replace / Upload Direct Photo --}}
                <input type="file" id="financeViewerDirectUploadInput" accept="image/*,application/pdf" style="display: none;" onchange="handleFinanceViewerDirectUpload(event)">
                <button type="button" onclick="document.getElementById('financeViewerDirectUploadInput').click()" title="Replace / Upload Proof Photo" style="display: inline-flex; align-items: center; gap: 4px; background: #f8fafc; border: 1px solid #cbd5e1; color: #334155; padding: 5px 9px; border-radius: 8px; font-size: 11.5px; font-weight: 600; cursor: pointer; transition: all 0.15s ease;" onmouseenter="this.style.background='#eff6ff'; this.style.borderColor='#93c5fd'; this.style.color='#1e40af';" onmouseleave="this.style.background='#f8fafc'; this.style.borderColor='#cbd5e1'; this.style.color='#334155';">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    <span>Replace</span>
                </button>

                {{-- Copy Reference --}}
                <button type="button" onclick="copyFinanceViewerRef()" title="Copy Reference & Payment Details" style="display: inline-flex; align-items: center; gap: 4px; background: #f8fafc; border: 1px solid #cbd5e1; color: #334155; padding: 5px 9px; border-radius: 8px; font-size: 11.5px; font-weight: 600; cursor: pointer;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    <span id="financeViewerCopyLabel">Copy Ref</span>
                </button>

                {{-- Print --}}
                <button type="button" onclick="printFinanceViewerProof()" title="Print Receipt Proof (P)" style="display: inline-flex; align-items: center; gap: 4px; background: #f8fafc; border: 1px solid #cbd5e1; color: #334155; padding: 5px 9px; border-radius: 8px; font-size: 11.5px; font-weight: 600; cursor: pointer;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    <span>Print</span>
                </button>

                {{-- Download --}}
                <button type="button" id="financeViewerDownloadBtn" onclick="downloadFinanceViewerFile()" title="Download File (D)" style="display: inline-flex; align-items: center; gap: 4px; background: #f8fafc; border: 1px solid #cbd5e1; color: #0f172a; padding: 5px 9px; border-radius: 8px; font-size: 11.5px; font-weight: 600; cursor: pointer;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    <span>Download</span>
                </button>

                {{-- Fullscreen in New Tab --}}
                <button type="button" onclick="openFinanceViewerInNewTab()" title="Open in Full Screen Tab" style="display: inline-flex; align-items: center; gap: 4px; background: #eff6ff; border: 1px solid #bfdbfe; color: #2563eb; padding: 5px 9px; border-radius: 8px; font-size: 11.5px; font-weight: 600; cursor: pointer;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                    <span>Full Screen</span>
                </button>

                {{-- Close Modal --}}
                <button type="button" onclick="closeFinanceAttachmentViewer()" style="background: transparent; border: none; font-size: 20px; line-height: 1; color: #94a3b8; cursor: pointer; padding: 2px 6px; border-radius: 6px; margin-left: 2px;" onmouseenter="this.style.color='#0f172a'; this.style.background='#f1f5f9';" onmouseleave="this.style.color='#94a3b8'; this.style.background='transparent';">&times;</button>
            </div>
        </div>

        {{-- Modal Content Body with Main Canvas & Collapsible Details Drawer --}}
        <div style="display: flex; flex: 1; position: relative; width: 100%; min-height: 440px; max-height: 70vh; overflow: hidden; background: #0b1120;">
            {{-- Main Canvas Area --}}
            <div style="flex: 1; position: relative; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; overflow: hidden;"
                 ondragover="handleFinanceViewerDragOver(event)" 
                 ondragleave="handleFinanceViewerDragLeave(event)" 
                 ondrop="handleFinanceViewerDrop(event)">
                
                {{-- Drag and Drop Upload Overlay --}}
                <div id="financeViewerDropOverlay" style="display: none; position: absolute; inset: 12px; background: rgba(37, 99, 235, 0.85); border: 2px dashed #ffffff; border-radius: 12px; z-index: 50; align-items: center; justify-content: center; flex-direction: column; color: #ffffff; pointer-events: none; backdrop-filter: blur(4px);">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 8px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    <div style="font-size: 16px; font-weight: 700;">Drop receipt image to update proof</div>
                    <div style="font-size: 12px; opacity: 0.85; margin-top: 4px;">Supports PNG, JPG, WEBP, PDF</div>
                </div>

                {{-- Prev Arrow --}}
                <button type="button" id="financeViewerPrevBtn" onclick="navigateFinanceViewer(-1)" title="Previous Proof (Left Arrow)" style="display: none; position: absolute; left: 16px; top: 50%; transform: translateY(-50%); z-index: 10; width: 40px; height: 40px; border-radius: 50%; background: rgba(15, 23, 42, 0.75); border: 1px solid rgba(255,255,255,0.2); color: #ffffff; cursor: pointer; align-items: center; justify-content: center; backdrop-filter: blur(4px); transition: all 0.15s ease;" onmouseenter="this.style.background='rgba(37, 99, 235, 0.9)'; this.style.borderColor='#93c5fd';" onmouseleave="this.style.background='rgba(15, 23, 42, 0.75)'; this.style.borderColor='rgba(255,255,255,0.2)';">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>

                {{-- Image / File Container --}}
                <div id="financeViewerContent" style="width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; overflow: hidden; padding: 16px; box-sizing: border-box;">
                </div>

                {{-- Next Arrow --}}
                <button type="button" id="financeViewerNextBtn" onclick="navigateFinanceViewer(1)" title="Next Proof (Right Arrow)" style="display: none; position: absolute; right: 16px; top: 50%; transform: translateY(-50%); z-index: 10; width: 40px; height: 40px; border-radius: 50%; background: rgba(15, 23, 42, 0.75); border: 1px solid rgba(255,255,255,0.2); color: #ffffff; cursor: pointer; align-items: center; justify-content: center; backdrop-filter: blur(4px); transition: all 0.15s ease;" onmouseenter="this.style.background='rgba(37, 99, 235, 0.9)'; this.style.borderColor='#93c5fd';" onmouseleave="this.style.background='rgba(15, 23, 42, 0.75)'; this.style.borderColor='rgba(255,255,255,0.2)';">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>

                {{-- Canvas Helper Pill --}}
                <div style="position: absolute; bottom: 12px; left: 16px; z-index: 10; background: rgba(15, 23, 42, 0.65); border: 1px solid rgba(255,255,255,0.15); color: #94a3b8; font-size: 10.5px; padding: 3px 8px; border-radius: 6px; backdrop-filter: blur(4px); pointer-events: none;">
                    Drag to Pan • Scroll to Zoom • Drop Image to Replace
                </div>
            </div>

            {{-- Collapsible Details Sidebar Drawer --}}
            <div id="financeViewerDetailsSidebar" style="display: none; width: 310px; flex-shrink: 0; background: #ffffff; border-left: 1px solid #e2e8f0; overflow-y: auto; flex-direction: column; z-index: 20;">
                <div style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                    <div style="font-size: 13px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        <span>Payment &amp; Proof Details</span>
                    </div>
                    <button type="button" onclick="toggleFinanceViewerInfoPanel()" style="background: transparent; border: none; color: #94a3b8; cursor: pointer; padding: 2px 4px; font-size: 16px;" onmouseenter="this.style.color='#0f172a';" onmouseleave="this.style.color='#94a3b8';">&times;</button>
                </div>
                <div id="financeViewerSidebarBody">
                    {{-- Populated dynamically --}}
                </div>
            </div>
        </div>

        {{-- Bottom Multi-Proof Thumbnail Strip --}}
        <div id="financeViewerThumbStrip" style="display: none; background: #ffffff; border-top: 1px solid #e2e8f0; padding: 8px 16px; align-items: center; gap: 8px; overflow-x: auto;">
        </div>

        {{-- Bottom Shortcuts Info Bar --}}
        <div style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 6px 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; font-size: 11px; color: #64748b;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <span><strong style="color: #334155;">[← / →]</strong> Navigate</span>
                <span><strong style="color: #334155;">[+ / - / Wheel]</strong> Zoom</span>
                <span><strong style="color: #334155;">[R]</strong> Rotate</span>
                <span><strong style="color: #334155;">[F]</strong> Flip</span>
                <span><strong style="color: #334155;">[C]</strong> Clarity</span>
                <span><strong style="color: #334155;">[I]</strong> Details</span>
                <span><strong style="color: #334155;">[P]</strong> Print</span>
                <span><strong style="color: #334155;">[D]</strong> Download</span>
                <span><strong style="color: #334155;">[Esc]</strong> Close</span>
            </div>
            <div style="color: #94a3b8; font-size: 10.5px;">
                Antigravity Finance Viewer
            </div>
        </div>
    </div>
</div>

@include('components.deal-drawer', ['returnTo' => url()->current()])

<script>
    (function() {
        function formatDurationHms(totalSeconds) {
            if (totalSeconds < 0) totalSeconds = 0;
            const days = Math.floor(totalSeconds / 86400);
            const rem = totalSeconds % 86400;
            const hours = Math.floor(rem / 3600);
            const minutes = Math.floor((rem % 3600) / 60);
            const seconds = rem % 60;

            const h = String(hours).padStart(2, '0');
            const m = String(minutes).padStart(2, '0');
            const s = String(seconds).padStart(2, '0');
            const hms = `${h}:${m}:${s}`;

            if (days > 0) {
                return `${days}d ${hms}`;
            }
            return hms;
        }

        function formatHumanDuration(totalSeconds) {
            if (totalSeconds <= 0) return '0s';
            const days = Math.floor(totalSeconds / 86400);
            const rem = totalSeconds % 86400;
            const hours = Math.floor(rem / 3600);
            const minutes = Math.floor((rem % 3600) / 60);
            const seconds = rem % 60;

            if (days > 0) {
                return `${days}d ${hours}h ${minutes}m`;
            }
            if (hours > 0) {
                return `${hours}h ${minutes}m ${seconds}s`;
            }
            if (minutes > 0) {
                return `${minutes}m ${seconds}s`;
            }
            return `${seconds}s`;
        }

        let stageTimerInterval = null;

        function initLiveStageTimer() {
            const timerBox = document.getElementById('dealStageTimerBox');
            const timerDisplay = document.getElementById('dealStageTimerDisplay');
            if (!timerBox || !timerDisplay) return;

            const startMs = parseInt(timerBox.getAttribute('data-stage-start-ms'), 10);
            const isActive = timerBox.getAttribute('data-is-active') === 'true';

            if (stageTimerInterval) {
                clearInterval(stageTimerInterval);
                stageTimerInterval = null;
            }

            function tick() {
                if (!startMs || isNaN(startMs)) return;
                const nowMs = Date.now();
                const elapsedSeconds = Math.max(0, Math.floor((nowMs - startMs) / 1000));
                const formattedHms = formatDurationHms(elapsedSeconds);
                const formattedHuman = formatHumanDuration(elapsedSeconds);

                timerDisplay.textContent = formattedHms;

                // Also keep current active pipeline step duration updated in the pipeline bar
                const activePipeDuration = document.querySelector('.dh-pipe-step.is-current .dh-pipe-duration');
                if (activePipeDuration) {
                    activePipeDuration.textContent = formattedHuman;
                }
            }

            tick();
            if (isActive) {
                stageTimerInterval = setInterval(tick, 1000);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initLiveStageTimer);
        } else {
            initLiveStageTimer();
        }

        window.__initLiveStageTimer = initLiveStageTimer;
    })();

    /* =========================================================
       PROPOSAL PREVIEW & GLOBAL TEMPLATE WORKFLOW
       ========================================================= */
    let lastActiveProposalEditable = null;

    function initProposalPreviewEditor() {
        const workspace = document.querySelector('.preview-workspace');
        if (!workspace) return;

        const textElements = workspace.querySelectorAll('p, h1, h2, h3, h4, h5, h6, .pdf-cover-service, .pdf-cover-client-name, .pdf-cover-business-name, .pdf-cover-address, .doc-paragraph, .doc-section-heading, li, td, th');
        textElements.forEach(el => {
            if (!el.hasAttribute('contenteditable')) {
                el.setAttribute('contenteditable', 'true');
            }
            el.addEventListener('focus', function() {
                lastActiveProposalEditable = this;
            });
            el.addEventListener('input', function() {
                syncProposalPreviewToForm(this);
            });
            el.addEventListener('mouseup', function() {
                rememberProposalSelection();
            });
            el.addEventListener('keyup', function() {
                rememberProposalSelection();
            });
        });
    }

    function formatProposalPreview(command, value = null) {
        const workspace = document.querySelector('.preview-workspace');
        if (!workspace) return;

        // Restore saved selection if available
        if (typeof restoreProposalSelection === 'function') {
            restoreProposalSelection();
        }

        const selection = window.getSelection();
        let isInside = false;

        if (selection && selection.rangeCount > 0) {
            let node = selection.anchorNode;
            while (node) {
                if (node === workspace || (node.classList && node.classList.contains('preview-workspace'))) {
                    isInside = true;
                    break;
                }
                node = node.parentNode;
            }
        }

        if (!isInside && lastActiveProposalEditable && workspace.contains(lastActiveProposalEditable)) {
            lastActiveProposalEditable.focus();
        } else if (!isInside) {
            const firstEditable = workspace.querySelector('[contenteditable="true"]');
            if (firstEditable) {
                firstEditable.focus();
            }
        }

        document.execCommand(command, false, value);
        if (typeof rememberProposalSelection === 'function') {
            rememberProposalSelection();
        }
    }

    window.__formatProposalPreviewImpl = formatProposalPreview;

    function saveProposalTemplateNotice() {
        const modal = document.getElementById('proposalTemplateModal');
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    function closeProposalTemplateModal() {
        const modal = document.getElementById('proposalTemplateModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function confirmSaveProposalTemplate() {
        const workspace = document.querySelector('.preview-workspace');
        if (workspace) {
            try {
                localStorage.setItem('ordo_global_proposal_template_' + {{ $deal->id }}, workspace.innerHTML);
                localStorage.setItem('ordo_global_proposal_template_master', workspace.innerHTML);
            } catch(e) {}
        }
        closeProposalTemplateModal();
        showProposalToast('Global proposal template saved successfully.');
    }

    function refreshProposalPreview() {
        const form = document.getElementById('deal-proposal-form');
        if (!form) return;

        const subjectInput = form.querySelector('[name="subject"]');
        const coverService = document.querySelector('.pdf-cover-service');
        if (coverService && subjectInput && subjectInput.value) {
            coverService.textContent = subjectInput.value;
        }

        showProposalToast('Proposal preview refreshed.');
    }

    function syncProposalPreviewToForm(editableEl) {
        const form = document.getElementById('deal-proposal-form');
        if (!form) return;

        const dataSource = editableEl.getAttribute('data-source');
        if (dataSource === 'proposal_subject') {
            const subj = form.querySelector('[name="subject"]');
            if (subj) subj.value = editableEl.textContent.trim();
        }
    }

    function showProposalToast(message) {
        const existing = document.getElementById('proposalLiveToast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.id = 'proposalLiveToast';
        toast.style.cssText = 'position: fixed; top: 24px; right: 24px; z-index: 9999999; background: #16a34a; color: #ffffff; padding: 12px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.25); display: flex; align-items: center; gap: 8px; transition: opacity 0.3s ease;';
        toast.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>${message}</span>`;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    function toggleDealValuePrivacy(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const realEl = document.querySelector('.dh-value-real');
        const maskedEl = document.querySelector('.dh-value-masked');
        const eyeOpen = document.getElementById('dealValueEyeOpen');
        const eyeClosed = document.getElementById('dealValueEyeClosed');
        if (!realEl || !maskedEl || !eyeOpen || !eyeClosed) return;

        const isCurrentlyMasked = (maskedEl.style.display !== 'none');
        if (isCurrentlyMasked) {
            maskedEl.style.display = 'none';
            realEl.style.display = 'inline';
            eyeOpen.style.display = 'inline-block';
            eyeClosed.style.display = 'none';
            try { localStorage.setItem('ordo_deal_value_masked', '0'); } catch(err){}
        } else {
            realEl.style.display = 'none';
            maskedEl.style.display = 'inline';
            eyeOpen.style.display = 'none';
            eyeClosed.style.display = 'inline-block';
            try { localStorage.setItem('ordo_deal_value_masked', '1'); } catch(err){}
        }
    }

    function initDealValuePrivacy() {
        try {
            if (localStorage.getItem('ordo_deal_value_masked') === '1') {
                const realEl = document.querySelector('.dh-value-real');
                const maskedEl = document.querySelector('.dh-value-masked');
                const eyeOpen = document.getElementById('dealValueEyeOpen');
                const eyeClosed = document.getElementById('dealValueEyeClosed');
                if (realEl && maskedEl && eyeOpen && eyeClosed) {
                    realEl.style.display = 'none';
                    maskedEl.style.display = 'inline';
                    eyeOpen.style.display = 'none';
                    eyeClosed.style.display = 'inline-block';
                }
            }
        } catch(err){}
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initProposalPreviewEditor();
            initDealValuePrivacy();
        });
    } else {
        initProposalPreviewEditor();
        initDealValuePrivacy();
    }
</script>

@php
    $flashType = session('success') ? 'success' : (session('info') ? 'info' : (session('warning') ? 'warning' : (session('error') ? 'error' : null)));
    $flashMsg = session('success') ?? session('info') ?? session('warning') ?? session('error');
    
    $flashConfig = match($flashType) {
        'error' => [
            'iconBg' => '#fef2f2',
            'iconColor' => '#dc2626',
            'border' => '#fecaca',
            'title' => 'Error',
            'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
            'barColor' => '#ef4444',
        ],
        'warning' => [
            'iconBg' => '#fffbeb',
            'iconColor' => '#d97706',
            'border' => '#fde68a',
            'title' => 'Warning',
            'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
            'barColor' => '#f59e0b',
        ],
        'info' => [
            'iconBg' => '#eff6ff',
            'iconColor' => '#2563eb',
            'border' => '#bfdbfe',
            'title' => 'Notice',
            'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
            'barColor' => '#3b82f6',
        ],
        default => [
            'iconBg' => '#f0fdf4',
            'iconColor' => '#16a34a',
            'border' => '#bbf7d0',
            'title' => 'Success',
            'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
            'barColor' => '#22c55e',
        ],
    };
@endphp

@if($flashMsg)
    <div
        id="flashMessage"
        class="ordo-toast-notification"
        style="position: fixed; top: 24px; right: 24px; z-index: 99999; background: #ffffff; color: #0f172a; border: 1px solid {{ $flashConfig['border'] }}; border-radius: 12px; box-shadow: 0 12px 30px -4px rgba(15, 23, 42, 0.15), 0 4px 12px -2px rgba(15, 23, 42, 0.08); display: flex; align-items: flex-start; gap: 12px; padding: 14px 16px; min-width: 320px; max-width: 440px; animation: ordoToastSlideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1); overflow: hidden;"
    >
        <div style="flex-shrink: 0; width: 34px; height: 34px; border-radius: 50%; background: {{ $flashConfig['iconBg'] }}; color: {{ $flashConfig['iconColor'] }}; display: flex; align-items: center; justify-content: center;">
            {!! $flashConfig['icon'] !!}
        </div>
        <div style="flex: 1; min-width: 0; padding-top: 1px;">
            <div style="font-size: 13px; font-weight: 600; color: #0f172a; line-height: 1.3;">{{ $flashConfig['title'] }}</div>
            <div style="font-size: 12.5px; font-weight: 400; color: #475569; margin-top: 2px; line-height: 1.4; word-break: break-word;">{{ $flashMsg }}</div>
        </div>
        <button
            type="button"
            onclick="dismissOrdoToast(document.getElementById('flashMessage'))"
            style="flex-shrink: 0; background: transparent; border: none; padding: 4px; cursor: pointer; color: #94a3b8; border-radius: 6px; display: flex; align-items: center; justify-content: center; margin-top: -2px; margin-right: -4px;"
            onmouseenter="this.style.color='#475569'; this.style.background='#f1f5f9';"
            onmouseleave="this.style.color='#94a3b8'; this.style.background='transparent';"
            aria-label="Close"
        >
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: #f1f5f9;">
            <div id="flashProgressBar" style="height: 100%; background: {{ $flashConfig['barColor'] }}; width: 100%; transition: width 4s linear;"></div>
        </div>
    </div>

    <style>
        @keyframes ordoToastSlideIn {
            from {
                opacity: 0;
                transform: translateY(-16px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        @keyframes ordoToastFadeOut {
            from {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
            to {
                opacity: 0;
                transform: translateY(-10px) scale(0.96);
            }
        }
    </style>

    <script>
        function dismissOrdoToast(toast) {
            if (!toast) return;
            toast.style.animation = 'ordoToastFadeOut 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards';
            setTimeout(() => toast.remove(), 250);
        }

        setTimeout(function() {
            const bar = document.getElementById('flashProgressBar');
            if (bar) bar.style.width = '0%';
        }, 50);

        setTimeout(function() {
            const flash = document.getElementById('flashMessage');
            dismissOrdoToast(flash);
        }, 4000);
    </script>
@endif

@endsection

@php
    $dealCalculatedRoute = 'Regular';
    if (!empty($deal->engagement_type)) {
        $etL = strtolower($deal->engagement_type);
        if (str_contains($etL, 'hybrid')) {
            $dealCalculatedRoute = 'Hybrid';
        } elseif (str_contains($etL, 'project')) {
            $dealCalculatedRoute = 'Project';
        } elseif (str_contains($etL, 'regular') || str_contains($etL, 'retainer')) {
            $dealCalculatedRoute = 'Regular';
        }
    }
    $dealDefaultPaymentTerms = $deal->payment_terms ?: 'Full Payment Before Service';
    $dealDefaultScopeOfWork = $deal->scope_of_work ?: '';
    
    $dealAvailedItems = [];
    if (!empty($deal->services_products) && is_array($deal->services_products)) {
        foreach ($deal->services_products as $sp) {
            $spName = is_array($sp) ? ($sp['name'] ?? '') : (string)$sp;
            if (filled($spName)) {
                $dealAvailedItems[] = [
                    'name' => $spName,
                    'type' => is_array($sp) && isset($sp['type']) ? $sp['type'] : 'SERVICE',
                    'price' => is_array($sp) && isset($sp['price']) ? (float)$sp['price'] : (is_array($sp) && isset($sp['amount']) ? (float)$sp['amount'] : 0),
                    'unit' => is_array($sp) && isset($sp['unit']) ? $sp['unit'] : 'lot',
                    'description' => is_array($sp) && isset($sp['description']) ? $sp['description'] : ($dealDefaultScopeOfWork ?: 'Scope item description')
                ];
            }
        }
    }

    $dealLineItems = [];
    if (!empty($deal->services_products) && is_array($deal->services_products)) {
        foreach ($deal->services_products as $idx => $item) {
            $dealLineItems[] = [
                'id' => $idx + 1,
                'type' => is_array($item) && isset($item['type']) ? $item['type'] : 'SERVICE',
                'name' => is_array($item) ? ($item['name'] ?? 'Commercial Scope Item') : $item,
                'description' => is_array($item) ? ($item['description'] ?? ($dealDefaultScopeOfWork ?: '')) : ($dealDefaultScopeOfWork ?: ''),
                'qty' => is_array($item) && isset($item['qty']) ? (float)$item['qty'] : 1,
                'unit' => is_array($item) && isset($item['unit']) ? $item['unit'] : 'lot',
                'unitPrice' => (float) (is_array($item) && isset($item['price']) && (float)$item['price'] > 0 ? $item['price'] : (is_array($item) && isset($item['amount']) && (float)$item['amount'] > 0 ? $item['amount'] : ($proposalTotal > 0 ? $proposalTotal / max(1, count($deal->services_products)) : (($deal->amount ?: $deal->total_estimated_engagement_value ?: 0) / max(1, count($deal->services_products)))))),
                'discount' => is_array($item) && isset($item['discount']) ? (float)$item['discount'] : 0,
                'tax' => is_array($item) && isset($item['tax']) ? (float)$item['tax'] : 0,
                'billing' => $deal->payment_terms ?: 'Full Payment Before Service',
                'route' => is_array($item) && isset($item['route']) ? $item['route'] : $dealCalculatedRoute,
            ];
        }
    }

    $wfDefaults = [
        'InquirySource' => $deal->inquiry_source ?: 'Direct Client Inquiry',
        'InquiryDate' => $deal->inquiry_date ? $deal->inquiry_date->format('Y-m-d') : date('Y-m-d'),
        'FirstName' => $deal->first_name ?: ($deal->primary_contact_name ?: ($deal->company_name ?: '')),
        'LastName' => $deal->last_name ?: '',
        'Email' => $deal->email ?: ($primaryContact?->email ?: ($deal->contact?->email ?: '')),
        'Mobile' => $deal->mobile_number ?: ($primaryContact?->mobile_number ?: ($deal->contact?->phone ?: '')),
        'DealTitle' => $deal->deal_title ?: ($deal->company_name ? $deal->company_name . ' Deal' : 'General Deal'),
        'InquiryDetails' => $deal->inquiry_details ?: ($deal->scope_of_work ?: 'Client inquiry initiated.'),

        'QualBudget' => (float) ($deal->qualification_budget ?: ($deal->amount ?: ($deal->total_estimated_engagement_value ?: 0))),
        'QualTimeline' => $deal->qualification_timeline ?: '1-3 months',
        'QualAuth' => $deal->qualification_authority ?: 'Decision Maker',
        'QualNeed' => $deal->qualification_need ?: 'Standard Business Engagement',
        'QualNotes' => $deal->qualification_notes ?: 'Client qualification verified.',

        'ConsultDate' => $deal->consultation_date ? $deal->consultation_date->format('Y-m-d') : date('Y-m-d'),
        'ConsultType' => $deal->consultation_type ?: 'Initial Consultation',
        'ReqConfirmed' => $deal->requirements_confirmed ?: 'Yes',
        'ScopeOfWork' => $deal->scope_of_work ?: '',
        'ConsultNotes' => $deal->consultant_notes ?: '',

        'PropNum' => $deal->proposal_number ?: ('PROP-' . date('Y') . '-' . str_pad($deal->id, 3, '0', STR_PAD_LEFT)),
        'PropDate' => $deal->proposal_date ? $deal->proposal_date->format('Y-m-d') : date('Y-m-d'),
        'PropValue' => (float) ($deal->proposal_value ?: ($deal->amount ?: ($deal->total_estimated_engagement_value ?: 0))),
        'PropValidUntil' => $deal->proposal_valid_until ? $deal->proposal_valid_until->format('Y-m-d') : '',
        'PropStatus' => $deal->proposal_status ?: 'Approved',
        'PropNotes' => $deal->proposal_notes ?: '',

        'NegoStatus' => $deal->negotiation_status ?: 'Agreed',
        'FinalDealVal' => (float) ($deal->final_deal_value ?: ($deal->amount ?: ($deal->total_estimated_engagement_value ?: 0))),
        'PayTerms' => $deal->payment_terms ?: 'Standard terms',
        'PricingModel' => $deal->pricing_model ?: 'Fixed Fee',
        'NegoNotes' => $deal->negotiation_notes ?: '',

        'PayMethod' => $deal->payment_method ?: 'Bank Transfer',
        'PayAmount' => (float) ($deal->payment_amount ?: ($deal->amount ?: ($deal->total_estimated_engagement_value ?: 0))),
        'PayDate' => $deal->payment_date ? $deal->payment_date->format('Y-m-d') : date('Y-m-d'),
        'PayStatus' => $deal->payment_status ?: 'Completed',
        'PayRef' => $deal->payment_reference ?: ('PAY-' . date('Ymd') . '-' . str_pad($deal->id, 3, '0', STR_PAD_LEFT)),
    ];

    $meaningfulDealTitle = (filled($deal->deal_title) && $deal->deal_title !== 'CONDEAL-YYYY-###' && !str_contains($deal->deal_title, 'YYYY-###') && !str_starts_with($deal->deal_title, 'CONDEAL-'))
        ? $deal->deal_title
        : (!empty($proposalItemNames) && count($proposalItemNames)
            ? $proposalItemNames[0]
            : ($deal->dealLineItems?->isNotEmpty()
                ? $deal->dealLineItems->first()->name
                : (filled($listValue($deal->services_products))
                    ? $listValue($deal->services_products)
                    : (filled($listValue($deal->service_areas))
                        ? $listValue($deal->service_areas)
                        : (filled($deal->engagement_type)
                            ? $deal->engagement_type
                            : (filled($deal->company_name)
                                ? ($deal->company_name . ' Service')
                                : (filled($deal->primary_contact_name)
                                    ? ($deal->primary_contact_name . ' Service')
                                    : 'General Services')))))));

    $dealInquiries = [];
    if (!empty($deal->inquiry_records) && is_array($deal->inquiry_records) && count($deal->inquiry_records) > 0) {
        $dealInquiries = $deal->inquiry_records;
    } else {
        $resolvedInquiryDetails = $deal->inquiry_details;
        if (blank($resolvedInquiryDetails)) {
            $resolvedInquiryDetails = $deal->scope_of_work
                ?: (filled($listValue($deal->client_requirements)) ? $listValue($deal->client_requirements)
                    : (filled($listValue($deal->services_products)) ? $listValue($deal->services_products)
                        : (filled($listValue($deal->service_areas)) ? $listValue($deal->service_areas)
                            : ($deal->consultant_notes ?: ('Initial Client Inquiry for ' . ($deal->company_name ?: ($deal->primary_contact_name ?: 'Services')))))));
        }

        $dealInquiries[] = [
            'id' => 1,
            'subject' => $meaningfulDealTitle,
            'type' => (in_array($deal->inquiry_source, ['Service', 'Product']) ? $deal->inquiry_source : (in_array($deal->pipeline_stage, ['Closed Won', 'Payment', 'Activation']) ? 'Product' : 'Service')),
            'clientInquiry' => $resolvedInquiryDetails,
            'budget' => (string) ($deal->total_estimated_engagement_value ?: ($deal->amount ?: '')),
            'targetDate' => $deal->expected_close ? $deal->expected_close->format('Y-m-d') : ($deal->estimated_completion_date ? $deal->estimated_completion_date->format('Y-m-d') : ''),
            'notes' => !empty($deal->client_requirements) ? ('Requirements: ' . $listValue($deal->client_requirements)) : '',
            'createdAt' => $deal->inquiry_date ? $deal->inquiry_date->format('M d, Y') : ($deal->created_at ? $deal->created_at->format('M d, Y') : now()->format('M d, Y')),
            'createdBy' => $deal->owner_name ?: ($deal->created_by ?: optional(auth()->user())->name ?: 'System'),
        ];
    }

    $dealConsultations = [];
    if (!empty($deal->consultation_records) && is_array($deal->consultation_records)) {
        $dealConsultations = $deal->consultation_records;
    } elseif (filled($deal->consultant_notes) || filled($deal->consultation_date)) {
        $consultationTitlePrefix = $meaningfulDealTitle;
        $finalConsultationTitle = str_contains(strtolower($consultationTitlePrefix), 'consultation')
            ? $consultationTitlePrefix
            : ($consultationTitlePrefix . ' Consultation');

        $dealConsultations[] = [
            'id' => 1,
            'title' => $finalConsultationTitle,
            'date' => $deal->consultation_date ? $deal->consultation_date->format('Y-m-d') : ($deal->planned_start_date ? $deal->planned_start_date->format('Y-m-d') : now()->format('Y-m-d')),
            'consultant' => $deal->assigned_consultant ?: ($deal->owner_name ?: 'Consultant'),
            'associate' => $deal->assigned_associate ?: '',
            'preparedBy' => $deal->prepared_by ?: ($deal->owner_name ?: 'Consultant'),
            'notes' => $deal->consultant_notes ?: '',
            'createdAt' => $deal->created_at ? $deal->created_at->format('M d, Y') : now()->format('M d, Y'),
            'createdBy' => $deal->owner_name ?: ($deal->created_by ?: optional(auth()->user())->name ?: 'Consultant'),
            'attachments' => []
        ];
    }

    $dealBootstrap = [
        'dealId' => $deal->id,
        'dealCode' => $deal->deal_code ?: ('CONDEAL-' . date('Y') . '-' . str_pad($deal->id, 3, '0', STR_PAD_LEFT)),
        'dealNumber' => $deal->deal_number ?: ('D-' . $deal->id),
        'clientName' => $contactName ?: ($deal->primary_contact_name ?: ($deal->company_name ?: 'Client Representative')),
        'clientEmail' => $deal->email ?: ($primaryContact?->email ?: ($deal->contact?->email ?: '')),
        'currentStage' => $currentStage ?? 'Inquiry',
        'proposalDecision' => $deal->proposal_decision ?: 'Draft',
        'dealAmount' => (float) ($deal->total_estimated_engagement_value ?: ($deal->amount ?: 0)),
        'dealCalculatedRoute' => $dealCalculatedRoute,
        'dealDefaultPaymentTerms' => $dealDefaultPaymentTerms,
        'dealDefaultScopeOfWork' => $dealDefaultScopeOfWork,
        'dealAvailedItems' => $dealAvailedItems,
        'dealLineItems' => $dealLineItems,
        'dealInquiries' => $dealInquiries,
        'dealConsultations' => $dealConsultations,
        'dealProposalItems' => $dealProposalItems ?? [],
        'proposalItems' => $proposalItems ?? [],
        'startBatches' => $startBatchesJson ?? [],
        'financeProposalItems' => $financeProposalItems ?? [],
        'serverSentSnapshotData' => $serverSentSnapshotData ?? null,
        'isQuotationCompleted' => (bool) ($isQuotationCompleted ?? false),
        'hasMoreHistories' => ($deal->histories()->count() > 20),
        'currentUser' => auth()->check() ? auth()->user()->name : ($deal->finance ?: ($deal->owner_name ?: 'System Administrator')),
        'ownerName' => $deal->owner_name ?: 'System Administrator',
        'leadConsultant' => $deal->lead_consultant ?: ($deal->owner_name ?: 'Consultant'),
        'engagementType' => $deal->engagement_type ?: '',
        'scopeOfWork' => $deal->scope_of_work ?: '',
        'paymentTerms' => $deal->payment_terms ?: '',
        'proposalTotals' => [
            'services' => (float) ($proposalServicesTotal ?? 0),
            'products' => (float) ($proposalProductsTotal ?? 0),
            'discount' => (float) ($proposalDiscount ?? 0),
            'tax' => (float) ($proposalTax ?? 0),
        ],
        'discountItemsList' => $discountItemsList ?? [],
        'defaults' => $wfDefaults,
        'routes' => [
            'stageWorkflowSave' => route('deals.stage-workflow.save', $deal->id),
            'stageUpdate' => route('deals.stage.update', $deal->id),
            'inquiriesStore' => route('deals.inquiries.store', $deal->id),
            'consultationsStore' => route('deals.consultations.store', $deal->id),
            'lineItemsStore' => route('deals.line-items.store', $deal->id),
            'inquiriesBase' => url('/deals/' . $deal->id . '/inquiries'),
            'consultationsBase' => url('/deals/' . $deal->id . '/consultations'),
            'lineItemsBase' => url('/deals/' . $deal->id . '/line-items'),
        ]
    ];
@endphp

<script>
    window.DEAL_BOOTSTRAP = {!! json_encode($dealBootstrap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};

    window.openProposalWorkspace = function() {
        var modal = document.getElementById('proposalWorkspaceModal');
        if (modal) {
            modal.style.display = 'flex';
        }
    };
    window.closeProposalWorkspaceModal = function() {
        var modal = document.getElementById('proposalWorkspaceModal');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    window.openClientReviewPortalModal = function() {
        var modal = document.getElementById('proposalClientPortalModal');
        if (modal) {
            modal.style.display = 'flex';
        }
    };
    window.closeClientReviewPortalModal = function() {
        var modal = document.getElementById('proposalClientPortalModal');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    window.openDispatchActionRequestModal = function() {
        var modal = document.getElementById('proposalDispatchModal');
        if (modal) {
            modal.style.display = 'flex';
        }
    };
    window.closeDispatchActionRequestModal = function() {
        var modal = document.getElementById('proposalDispatchModal');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    window.openUpdateDecisionModal = function() {
        var modal = document.getElementById('proposalDecisionModal');
        if (modal) {
            modal.style.display = 'flex';
        }
    };
    window.closeUpdateDecisionModal = function() {
        var modal = document.getElementById('proposalDecisionModal');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    window.openSendProposalModal = function() {
        var modal = document.getElementById('proposalSendModal');
        if (modal) {
            modal.style.display = 'flex';
        }
    };
    window.closeSendProposalModal = function() {
        var modal = document.getElementById('proposalSendModal');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    window.openAdjustDiscountModal = function() {
        var modal = document.getElementById('proposalDiscountModal');
        if (modal) {
            modal.style.display = 'flex';
        }
    };
    window.closeAdjustDiscountModal = function() {
        var modal = document.getElementById('proposalDiscountModal');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    window.printProposalPDF = function() {
        var modal = document.getElementById('proposalWorkspaceModal');
        if (modal && modal.style.display !== 'none') {
            window.print();
        } else {
            window.print();
        }
    };
</script>
<script src="{{ asset('js/deals-engine.js') }}?v={{ time() }}"></script>


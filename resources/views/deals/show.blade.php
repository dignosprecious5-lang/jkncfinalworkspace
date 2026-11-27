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
    ) ?: ($deal->primary_contact_name ?: ($deal->contact?->full_name ?: ''));

    $serviceAreas = $deal->service_areas ?: [];
    $servicesProducts = $deal->services_products ?: [];
    $requirements = $deal->client_requirements ?: [];
    $support = $deal->professional_support_required ?: [];

    $dealStartBatches = $deal->startRecords ?? collect();
    $isStartCompleted = $deal->isStartCompleted();
    $hasProject = $deal->hasProjectEngagement();
    $hasRegular = $deal->hasRegularEngagement();
    $isHybrid = $deal->isHybridEngagement();
    $engagementWorkspacesComplete = $deal->areEngagementWorkspacesComplete();

    $display = fn ($value) =>
        filled($value) ? $value : 'Not provided';

    $money = fn ($value) =>
        filled($value)
            ? '₱' . number_format((float) $value, 2)
            : 'Not provided';

    $date = fn ($value) =>
        $value ? $value->format('M d, Y') : 'Not provided';

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

    $proposal = $proposal ?? new \App\Models\DealProposal([
        'deal_id' => $deal->id,
        'proposal_version' => 1,
        'is_current' => true,
        'status' => 'Draft',
    ]);
    $proposalVersions = $proposalVersions ?? collect();
    $proposalItemResponseHistory = $proposalItemResponseHistory ?? collect();
    $proposalDecisionLedger = collect();
    foreach ($proposalVersions as $proposalVersion) {
        $versionResponses = $proposalItemResponseHistory->get($proposalVersion->id, collect());
        $snapshotItems = collect($proposalVersion->line_items_snapshot ?? [])->values();
        $matchedResponseIds = [];

        foreach ($snapshotItems as $index => $snapshotItem) {
            $snapshotItem = is_array($snapshotItem) ? $snapshotItem : ['name' => (string) $snapshotItem];
            $lineItemId = (string) ($snapshotItem['id'] ?? ('legacy-'.($index + 1)));
            $itemResponses = $versionResponses->where('line_item_id', $lineItemId);
            $itemName = (string) ($snapshotItem['name'] ?? 'Commercial Scope Item');

            if ($itemResponses->isNotEmpty()) {
                foreach ($itemResponses as $response) {
                    $matchedResponseIds[] = $response->id;
                    $decision = strtolower((string) $response->decision);
                    $proposalDecisionLedger->push([
                        'name' => $response->line_item_name ?: $itemName,
                        'type' => strtoupper((string) ($snapshotItem['type'] ?? '')),
                        'decision' => match ($decision) {
                            'accepted', 'approved' => 'Approved',
                            'declined', 'rejected' => 'Rejected',
                            default => ucfirst($decision),
                        },
                        'decision_key' => in_array($decision, ['accepted', 'approved'], true)
                            ? 'approved'
                            : (in_array($decision, ['declined', 'rejected'], true) ? 'rejected' : 'pending'),
                        'comment' => $response->comment,
                        'representative' => $response->client_name,
                        'responded_at' => $response->responded_at,
                        'version' => (int) ($proposalVersion->proposal_version ?: 1),
                        'reference' => $proposalVersion->reference_id ?: $proposalVersion->crud_id,
                    ]);
                }
            } elseif (filled($proposalVersion->client_form_sent_at)) {
                $proposalDecisionLedger->push([
                    'name' => $itemName,
                    'type' => strtoupper((string) ($snapshotItem['type'] ?? '')),
                    'decision' => 'Pending',
                    'decision_key' => 'pending',
                    'comment' => null,
                    'representative' => null,
                    'responded_at' => null,
                    'version' => (int) ($proposalVersion->proposal_version ?: 1),
                    'reference' => $proposalVersion->reference_id ?: $proposalVersion->crud_id,
                ]);
            }
        }

        foreach ($versionResponses->whereNotIn('id', $matchedResponseIds) as $response) {
            $decision = strtolower((string) $response->decision);
            $proposalDecisionLedger->push([
                'name' => $response->line_item_name ?: 'Commercial Scope Item',
                'type' => '',
                'decision' => match ($decision) {
                    'accepted', 'approved' => 'Approved',
                    'declined', 'rejected' => 'Rejected',
                    default => ucfirst($decision),
                },
                'decision_key' => in_array($decision, ['accepted', 'approved'], true)
                    ? 'approved'
                    : (in_array($decision, ['declined', 'rejected'], true) ? 'rejected' : 'pending'),
                'comment' => $response->comment,
                'representative' => $response->client_name,
                'responded_at' => $response->responded_at,
                'version' => (int) ($proposalVersion->proposal_version ?: 1),
                'reference' => $proposalVersion->reference_id ?: $proposalVersion->crud_id,
            ]);
        }
    }
    $proposalAreas = $deal->service_areas ?: [];
    $proposalItems = $deal->services_products ?: [];
    $resolvedProposalItems = collect($dealResolvedLineItems ?? []);
    $proposalServiceItems = $resolvedProposalItems->where('type', 'SERVICE')->values();
    $proposalProductItems = $resolvedProposalItems->where('type', 'PRODUCT')->values();
    $proposalLineAmount = static fn (array $item): ?float => ! empty($item['price_available'])
        ? round((float) ($item['qty'] ?? 1) * (float) ($item['unit_price'] ?? 0), 2)
        : null;
    $proposalItemsHavePrices = static fn ($items): bool => $items->isNotEmpty()
        && $items->every(static fn (array $item): bool => ! empty($item['price_available']));
    $proposalFallbackTotal = static function (...$values): ?float {
        foreach ($values as $value) {
            if (is_numeric($value) && (float) $value >= 0) {
                return round((float) $value, 2);
            }
        }

        return null;
    };
    $proposalMoney = static fn ($value): string => is_numeric($value)
        ? number_format((float) $value, 2)
        : 'Needs confirmation';
    $proposalCatalogCode = static function (array $item): string {
        $model = $item['model'] ?? null;
        $catalogCode = $model instanceof \App\Models\Service
            ? $model->service_id
            : ($model instanceof \App\Models\Product ? $model->product_id : null);

        return filled($catalogCode) ? (string) $catalogCode : (string) ($item['item_code'] ?? '-');
    };

    $proposalServicesTotal = $proposalItemsHavePrices($proposalServiceItems)
        ? round($proposalServiceItems->sum(static fn (array $item): float => (float) $item['qty'] * (float) $item['unit_price']), 2)
        : $proposalFallbackTotal($deal->total_service_fee, $deal->estimated_professional_fee, $deal->est_professional_fee);

    $proposalProductsTotal = $proposalItemsHavePrices($proposalProductItems)
        ? round($proposalProductItems->sum(static fn (array $item): float => (float) $item['qty'] * (float) $item['unit_price']), 2)
        : $proposalFallbackTotal($deal->total_product_fee);
    $proposalDiscount = (float) ($proposal->discount ?? $deal->discount ?? 0);
    $proposalTax = (float) ($proposal->tax ?? 0);

    $proposalSubtotal = is_numeric($proposalServicesTotal) && is_numeric($proposalProductsTotal)
        ? max($proposalServicesTotal + $proposalProductsTotal - $proposalDiscount, 0)
        : null;
    $proposalTotal = $proposalSubtotal !== null ? $proposalSubtotal + $proposalTax : null;

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
            ? (is_array($proposalAreas[0]) ? ($proposalAreas[0]['name'] ?? '') : $proposalAreas[0])
            : ($deal->engagement_type ?: ''));

    $dealProposalItems = !empty($proposalItemNames) 
        ? $proposalItemNames 
        : ($deal->dealLineItems?->pluck('name')->filter()->values()->toArray() 
            ?: (is_array($deal->services_products) ? $deal->services_products : (json_decode($deal->services_products ?? '', true) ?: [])));

    $startBatchesJson = collect($deal->start_batches ?? [])->map(function($record) use ($deal) {
        $record = (array)$record;
        $assignments = collect($record['assignments'] ?? [])->map(function($a) {
            $a = (array)$a;
            $ack = $a['acknowledged_at'] ?? null;
            if ($ack && !is_string($ack) && method_exists($ack, 'format')) {
                $ack = $ack->format('M d, Y h:i A');
            }
            return [
                'id' => $a['id'] ?? 1,
                'role' => $a['role'] ?? '',
                'assignee' => $a['assignee'] ?? $a['assigned_to'] ?? '',
                'user_id' => $a['user_id'] ?? null,
                'status' => $a['status'] ?? 'Pending',
                'acknowledged_at' => $ack,
                'response' => $a['response'] ?? null,
                'decline_reason' => $a['decline_reason'] ?? null,
                'is_required' => !array_key_exists('is_required', $a) || (bool) $a['is_required'],
                'notes' => $a['notes'] ?? '',
            ];
        })->values();

        $createdAt = $record['created_at'] ?? null;
        $openedDate = date('M d, Y');
        $openedTime = date('h:i A');
        if ($createdAt) {
            if (is_string($createdAt)) {
                $openedDate = date('M d, Y', strtotime($createdAt));
                $openedTime = date('h:i A', strtotime($createdAt));
            } elseif (method_exists($createdAt, 'format')) {
                $openedDate = $createdAt->format('M d, Y');
                $openedTime = $createdAt->format('h:i A');
            }
        }

        $recId = $record['id'] ?? 1;

        return [
            'id' => $recId,
            'start_code' => $record['start_code'] ?? ('ST-' . date('Y') . '-' . str_pad($recId, 3, '0', STR_PAD_LEFT)),
            'batch_name' => $record['batch_name'] ?? 'START Batch',
            'status' => $deal->resolveStartBatchTeamStatus($record),
            'opened_date' => $openedDate,
            'opened_time' => $openedTime,
            'activated_items' => $record['activated_items'] ?? [],
            'service_memo_ref' => $record['service_memo_ref'] ?? null,
            'service_memo_data' => $record['service_memo_data'] ?? null,
            'service_memo_revisions' => $record['service_memo_revisions'] ?? [],
            'history_logs' => $record['history_logs'] ?? [],
            'authorized_by' => $record['authorized_by'] ?? null,
            'created_by' => $record['created_by'] ?? null,
            'assignments' => $assignments,
        ];
    })->values();

    $startBatchRecordForClearance = collect($deal->start_batches ?? [])->sortByDesc(function($item) {
        $item = (array)$item;
        return $item['id'] ?? 0;
    })->first();
    
    $startAssignmentsListForClearance = collect();
    if ($startBatchRecordForClearance) {
        $startBatchRecordForClearance = (array)$startBatchRecordForClearance;
        $startAssignmentsListForClearance = collect($startBatchRecordForClearance['assignments'] ?? []);
    }

    $getClearanceAssignee = function($role) use ($startAssignmentsListForClearance) {
        $item = $startAssignmentsListForClearance->first(function($a) use ($role) {
            $a = (array)$a;
            return ($a['role'] ?? '') === $role;
        });
        if (!$item) return null;
        $item = (array)$item;
        return $item['assignee'] ?? $item['assigned_to'] ?? null;
    };

    $clearanceLeadConsultant = $deal->lead_consultant 
        ?: ($getClearanceAssignee('Lead Consultant')
            ?? $deal->owner_name 
            ?? 'John Kelly Abalde');

    $clearanceLeadAssociate = $deal->lead_associate 
        ?: ($getClearanceAssignee('Lead Associate')
            ?? $deal->assigned_associate
            ?? 'Ma. Lourdes T. Mata');

    $clearanceSalesMarketing = $deal->sales_marketing 
        ?: ($getClearanceAssignee('Sales & Marketing')
            ?? $deal->owner_name 
            ?? 'CRISTY ESTRELLA');
@endphp


@push('styles')
    <link rel="stylesheet" href="{{ asset('css/deals-show.css') }}?v={{ file_exists(public_path('css/deals-show.css')) ? filemtime(public_path('css/deals-show.css')) : '1.0' }}">
@endpush


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
        // Accurate dynamic status badges derived from actual business workflow
        $kycStatus = $deal->getResolvedKycStatus();
        $paymentStatus = $deal->getResolvedPaymentStatus();
        $startStatus = $deal->getResolvedStartStatus();
        $proposalStatus = $deal->getResolvedProposalStatus();
        $commercialStatus = $deal->getResolvedCommercialStatus();
        $casaStatus = $deal->getResolvedCasaStatus();

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
                        <span class="dh-badge" id="dhBadgeKyc">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            KYC: <span id="dhBadgeKycVal">{{ $kycStatus }}</span>
                        </span>

                        <span class="dh-badge" id="dhBadgePayment">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                            Payment: <span id="dhBadgePaymentVal">{{ $paymentStatus }}</span>
                        </span>

                        <span class="dh-badge" id="dhBadgeStart">
                            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                            START: <span id="dhBadgeStartVal">{{ $startStatus }}</span>
                        </span>

                        <span class="dh-badge" id="dhBadgeProposal">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            Proposal: <span id="dhBadgeProposalVal">{{ $proposalStatus }}</span>
                        </span>

                        <span class="dh-badge" id="dhBadgeCommercial">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="2" y1="21" x2="22" y2="21"></line><line x1="6" y1="10" x2="6" y2="18"></line><line x1="10" y1="10" x2="10" y2="18"></line><line x1="14" y1="10" x2="14" y2="18"></line><line x1="18" y1="10" x2="18" y2="18"></line><polygon points="12 2 2 7 22 7 12 2"></polygon></svg>
                            Commercial: <span id="dhBadgeCommercialVal">{{ $commercialStatus }}</span>
                        </span>

                        <span class="dh-badge" id="dhBadgeCasa">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            CASA: <span id="dhBadgeCasaVal">{{ $casaStatus }}</span>
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
                                $isComplete = ($idx < $stageIndex) || ($stg === 'Activation' && $isStartCompleted);
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
                    <button 
                        type="button" 
                        class="deal-nav-item" 
                        data-tab="engagment" 
                        id="dealNavTabEngagement"
                        data-locked="{{ !$isStartCompleted ? 'true' : 'false' }}"
                        title="{{ !$isStartCompleted ? 'Please complete the START workflow before accessing Engagement.' : 'Engagement' }}"
                        onclick="if (this.getAttribute('data-locked') === 'true') { return window.handleLockedEngagementTab(event); } else { return switchDealNavTab('engagment', this); }"
                    >
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
                            {{ $display($deal->company ?: ($deal->company_name ?: ($deal->customer_type === 'Individual' ? 'Not applicable' : null))) }}
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
                            {{ $deal->getResolvedContactPosition() }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Email Address</div>
                        <div class="detail-value">
                            {{ $display($deal->email ?: ($deal->contact?->email ?: null)) }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Contact Number</div>
                        <div class="detail-value">
                            {{ $deal->getResolvedContactNumber() }}
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
                            {{ $deal->getResolvedIndustry() }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Qualification Result</div>
                        <div class="detail-value">
                            {{ $deal->getResolvedQualificationResult() }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Qualification Notes</div>
                        <div class="detail-value">
                            {{ $display($deal->getResolvedQualificationNotes()) }}
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

                    <div class="detail-value" id="dealFinancialPaymentTerms">
                            {{ $display($deal->resolved_payment_terms) }}
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

            {{-- =================================================
                 DEAL FORM PREVIEW (CONSULTING & DEAL FORM TEMPLATE)
            ================================================== --}}

            @php
                $dfContact = $deal->contact ?: ($primaryContact ?: ($deal->dealContacts?->firstWhere('is_primary', true) ?: $deal->dealContacts?->first()));
                $dfCompany = $deal->companyRecord ?: $deal->account;

                $dfApprovedCif = $dfContact && in_array(strtolower((string) ($dfContact->cif_status ?: $dfContact->kyc_status)), ['approved', 'active', 'verified', 'completed', 'submitted'], true);
                $dfApprovedBif = $dfCompany && in_array(strtolower((string) ($dfCompany->status ?? '')), ['approved', 'active', 'verified'], true);
                if (! $dfApprovedBif && $dfCompany && filled($dfCompany->company_name)) {
                    $dfApprovedBif = true;
                }

                $dfStatusMap = is_array($deal->requirements_status)
                    ? $deal->requirements_status
                    : (is_string($deal->requirements_status) ? (json_decode($deal->requirements_status, true) ?: []) : []);
                $dfActionsMap = is_array($deal->required_actions)
                    ? $deal->required_actions
                    : (is_string($deal->required_actions) ? (json_decode($deal->required_actions, true) ?: []) : []);

                $dfStartProvided = $deal->isStartCompleted() || (is_array($deal->start_batches) && count($deal->start_batches) > 0) || ($deal->startRecords && $deal->startRecords->count() > 0);

                $dfRequirements = [
                    [
                        'requirement' => 'Client Contact Form',
                        'provided' => ($dfStatusMap['client_contact_form'] ?? null) === 'provided' || filled($dfContact?->id) || filled($deal->inquiry_details) ? 'Yes' : '-',
                        'pending' => ($dfStatusMap['client_contact_form'] ?? null) === 'pending' || (! filled($dfContact?->id) && ! filled($deal->inquiry_details)) ? 'Yes' : '-',
                        'required_action' => $dfActionsMap['client_contact_form'] ?? '-',
                    ],
                    [
                        'requirement' => 'Deal Form',
                        'provided' => ($dfStatusMap['deal_form'] ?? null) === 'provided' || true ? 'Yes' : '-',
                        'pending' => ($dfStatusMap['deal_form'] ?? null) === 'pending' ? 'Yes' : '-',
                        'required_action' => $dfActionsMap['deal_form'] ?? '-',
                    ],
                    [
                        'requirement' => 'Business Information Form',
                        'provided' => ($dfStatusMap['business_information_form'] ?? null) === 'provided' || $dfApprovedBif ? 'Yes' : '-',
                        'pending' => ($dfStatusMap['business_information_form'] ?? null) === 'pending' || (! $dfApprovedBif && $deal->customer_type !== 'Individual') ? 'Yes' : '-',
                        'required_action' => $dfActionsMap['business_information_form'] ?? '-',
                    ],
                    [
                        'requirement' => 'Client Information Form',
                        'provided' => ($dfStatusMap['client_information_form'] ?? null) === 'provided' || $dfApprovedCif ? 'Yes' : '-',
                        'pending' => ($dfStatusMap['client_information_form'] ?? null) === 'pending' || ! $dfApprovedCif ? 'Yes' : '-',
                        'required_action' => $dfActionsMap['client_information_form'] ?? '-',
                    ],
                    [
                        'requirement' => 'Service Task Activation & Routing Tracker (Start)',
                        'provided' => ($dfStatusMap['service_task_activation_routing_tracker'] ?? null) === 'provided' || $dfStartProvided ? 'Yes' : '-',
                        'pending' => ($dfStatusMap['service_task_activation_routing_tracker'] ?? null) === 'pending' || ! $dfStartProvided ? 'Yes' : '-',
                        'required_action' => $dfActionsMap['service_task_activation_routing_tracker'] ?? '-',
                    ],
                ];

                $dfMiddleInitial = $deal->middle_initial;
                if (! filled($dfMiddleInitial) && filled($deal->middle_name)) {
                    $dfMiddleInitial = strtoupper(substr(trim($deal->middle_name), 0, 1)) . '.';
                }
                if (! filled($dfMiddleInitial) && $dfContact) {
                    $dfMiddleInitial = $dfContact->middle_initial;
                    if (! filled($dfMiddleInitial) && filled($dfContact->middle_name)) {
                        $dfMiddleInitial = strtoupper(substr(trim($dfContact->middle_name), 0, 1)) . '.';
                    }
                }

                $dfClientSignature = $deal->client_fullname_signature ?: ($contactName !== 'Not provided' ? $contactName : ($deal->first_name ? trim($deal->first_name . ' ' . $deal->last_name) : null));
            @endphp

            <section class="detail-section" id="deal-form-preview">


                <div class="deal-form-preview" style="border: 1.5px solid #203354; background: #ffffff; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">

                    <!-- 1. HEADER -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border-bottom: 1.5px solid #203354; background: #ffffff;">
                        <div style="flex: 1;">
                            <img src="{{ asset('images/imaglogo.png') }}" style="max-height: 44px; width: auto; display: block;" alt="John Kelly & Company" onerror="this.style.display='none'; document.getElementById('df-logo-fallback').style.display='block';">
                            <div id="df-logo-fallback" style="display: none;">
                                <div style="font-family: 'Times New Roman', Georgia, serif; font-size: 20px; font-weight: bold; color: #0b1d3a; line-height: 1;">John Kelly</div>
                                <div style="font-family: 'Times New Roman', Georgia, serif; font-size: 14px; font-style: italic; color: #0b1d3a; line-height: 1;">&amp; Company</div>
                            </div>
                        </div>
                        <div style="flex: 1; text-align: right;">
                            <div style="font-size: 17px; font-weight: 800; color: #0f172a; letter-spacing: 0.5px; text-transform: uppercase; line-height: 1.1;">CONSULTING &amp; DEAL FORM</div>
                            <div style="margin-top: 5px; font-size: 12px; color: #1e293b;">
                                Deal No. <strong style="font-weight: 700; color: #000;">{{ $deal->deal_code ?: ('CONDEAL-' . sprintf('%04d', $deal->id)) }}</strong> &nbsp;&nbsp;&nbsp;&nbsp; Date <strong style="font-weight: 700; color: #000;">{{ $deal->created_at ? $deal->created_at->format('m/d/Y') : date('m/d/Y') }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- 2. DEAL SUMMARY -->
                    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                        <tr>
                            <td style="width: 33%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 10px; vertical-align: top;">
                                <span style="display: block; font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 3px;">Deal Name</span>
                                <div style="font-size: 12px; color: #0f172a; min-height: 16px;">{{ $display($deal->deal_name ?: ($deal->deal_title ?: ($deal->deal_code ?: null))) }}</div>
                            </td>
                            <td style="width: 20%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 10px; vertical-align: top;">
                                <span style="display: block; font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 3px;">Stage</span>
                                <div style="font-size: 12px; color: #0f172a; min-height: 16px;">{{ $currentStage }}</div>
                            </td>
                            <td style="width: 27%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 10px; vertical-align: top;">
                                <span style="display: block; font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 3px;">Engagement Type</span>
                                <div style="font-size: 12px; color: #0f172a; min-height: 16px;">{{ $display($deal->engagement_type ?: ($deal->engagement_term ?: ($deal->billing_type ?: null))) }}</div>
                            </td>
                            <td style="width: 20%; border-bottom: 1px solid #203354; padding: 6px 10px; vertical-align: top;">
                                <span style="display: block; font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 3px;">Total Value</span>
                                <div style="font-size: 12px; color: #0f172a; min-height: 16px; font-weight: 600;">{{ $money($totalValue) }}</div>
                            </td>
                        </tr>
                    </table>

                    <!-- 3. CONTACT INFORMATION -->
                    <div style="background: #0b2a68; color: #ffffff; text-align: center; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 0; border-bottom: 1px solid #203354;">Contact Information</div>
                    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                        <tr>
                            <td style="width: 12%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Salutation</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($deal->salutation ?: ($dfContact?->salutation ?: null)) }}</div>
                            </td>
                            <td style="width: 22%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">First Name</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($deal->first_name ?: ($dfContact?->first_name ?: null)) }}</div>
                            </td>
                            <td style="width: 13%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Middle Initial</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($dfMiddleInitial) }}</div>
                            </td>
                            <td style="width: 21%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Last Name</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($deal->last_name ?: ($dfContact?->last_name ?: null)) }}</div>
                            </td>
                            <td style="width: 16%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Name Extension</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($deal->name_extension ?: ($dfContact?->name_extension ?: null)) }}</div>
                            </td>
                            <td style="width: 16%; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Sex</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($deal->sex ?: ($dfContact?->sex ?: null)) }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 18%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Date of Birth</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $date($deal->date_of_birth ?: ($dfContact?->date_of_birth ?: null)) }}</div>
                            </td>
                            <td style="width: 32%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Email Address</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($deal->email ?: ($dfContact?->email ?: null)) }}</div>
                            </td>
                            <td style="width: 22%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Mobile Number</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $deal->getResolvedContactNumber() ?: ($dfContact?->phone ?: 'Not provided') }}</div>
                            </td>
                            <td style="width: 28%; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;" colspan="3">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Position / Designation</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $deal->getResolvedContactPosition() ?: ($dfContact?->position ?: 'Not provided') }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 34%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;" colspan="2">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Address</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($deal->address ?: ($dfContact?->contact_address ?: ($dfContact?->address ?: null))) }}</div>
                            </td>
                            <td style="width: 30%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;" colspan="2">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Company</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($deal->company ?: ($deal->company_name ?: ($dfCompany?->company_name ?: null))) }}</div>
                            </td>
                            <td style="width: 36%; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;" colspan="2">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Company Address</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($deal->company_address ?: ($dfCompany?->address ?: ($dfContact?->company_address ?: null))) }}</div>
                            </td>
                        </tr>
                    </table>

                    <!-- 4. SERVICE IDENTIFICATION -->
                    <div style="background: #0b2a68; color: #ffffff; text-align: center; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 0; border-bottom: 1px solid #203354;">Service Identification</div>
                    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                        <tr>
                            <td style="width: 50%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 10px; vertical-align: top;">
                                <span style="display: block; font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Service Area</span>
                                <div style="font-size: 11.5px; color: #0f172a; min-height: 16px;">{{ $listValue($deal->service_area ?: ($deal->service_areas ?: ($deal->proposal?->service_type ?: null))) }}</div>
                            </td>
                            <td style="width: 50%; border-bottom: 1px solid #203354; padding: 6px 10px; vertical-align: top;">
                                <span style="display: block; font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Services</span>
                                <div style="font-size: 11.5px; color: #0f172a; min-height: 16px;">{{ $listValue($deal->services ?: ($deal->services_needed ?: ($deal->services_products ?: ($deal->proposal?->package_name ?: null)))) }}</div>
                            </td>
                        </tr>
                    </table>

                    <!-- 5. PRODUCTS -->
                    <div style="background: #0b2a68; color: #ffffff; text-align: center; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 0; border-bottom: 1px solid #203354;">Products</div>
                    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                        <tr>
                            <td style="width: 50%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 10px; vertical-align: top;">
                                <span style="display: block; font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Products / Deliverables</span>
                                <div style="font-size: 11.5px; color: #0f172a; min-height: 16px;">{{ $listValue($deal->products ?: ($deal->products_needed ?: ($deal->proposal?->what_you_will_receive ?: null))) }}</div>
                            </td>
                            <td style="width: 50%; border-bottom: 1px solid #203354; padding: 6px 10px; vertical-align: top;">
                                <span style="display: block; font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Scope of Work</span>
                                <div style="font-size: 11.5px; color: #0f172a; min-height: 16px;">{{ $display($deal->scope_of_work ?: ($deal->proposal?->scope_of_service ?: null)) }}</div>
                            </td>
                        </tr>
                    </table>

                    <!-- 6. CLIENT REQUIREMENTS -->
                    <div style="background: #163b7a; color: #ffffff; text-align: center; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 0; border-bottom: 1px solid #203354;">Client Requirements</div>
                    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid #203354;">
                                <th style="width: 44%; border-right: 1px solid #203354; padding: 5px 8px; text-align: left; font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #000;">Requirement</th>
                                <th style="width: 18%; border-right: 1px solid #203354; padding: 5px 8px; text-align: center; font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #000;">Provided</th>
                                <th style="width: 18%; border-right: 1px solid #203354; padding: 5px 8px; text-align: center; font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #000;">Pending</th>
                                <th style="width: 20%; padding: 5px 8px; text-align: center; font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #000;">Required Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dfRequirements as $req)
                                <tr style="border-bottom: 1px solid #203354;">
                                    <td style="border-right: 1px solid #203354; padding: 4px 8px; font-size: 11px; color: #0f172a;">{{ $req['requirement'] }}</td>
                                    <td style="border-right: 1px solid #203354; padding: 4px 8px; text-align: center; font-size: 11px; color: #0f172a;">{{ $req['provided'] }}</td>
                                    <td style="border-right: 1px solid #203354; padding: 4px 8px; text-align: center; font-size: 11px; color: #0f172a;">{{ $req['pending'] }}</td>
                                    <td style="padding: 4px 8px; text-align: center; font-size: 11px; color: #0f172a;">{{ $req['required_action'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- 7. TIMELINE & ASSESSMENT -->
                    <div style="background: #163b7a; color: #ffffff; text-align: center; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 0; border-bottom: 1px solid #203354;">Timeline &amp; Assessment</div>
                    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                        <tr>
                            <td style="width: 25%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Planned Start Date</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $date($plannedStart) }}</div>
                            </td>
                            <td style="width: 25%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Estimated Duration</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ filled($deal->estimated_duration_days) ? (is_numeric($deal->estimated_duration_days) ? $deal->estimated_duration_days . ' days' : $deal->estimated_duration_days) : $display($deal->estimated_duration) }}</div>
                            </td>
                            <td style="width: 25%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Estimated Completion Date</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $date($estimatedCompletion) }}</div>
                            </td>
                            <td style="width: 25%; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Confirmed Delivery Date</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $date($deal->confirmed_delivery_date) }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 34%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Timeline Notes</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($deal->timeline_notes) }}</div>
                            </td>
                            <td style="width: 28%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Service Complexity</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($deal->service_complexity) }}</div>
                            </td>
                            <td style="width: 38%; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;" colspan="2">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Professional Support Required</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $listValue($support) }}</div>
                            </td>
                        </tr>
                    </table>

                    <!-- 8. FEES & PAYMENT -->
                    <div style="background: #0b2a68; color: #ffffff; text-align: center; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 0; border-bottom: 1px solid #203354;">Fees &amp; Payment</div>
                    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                        <tr>
                            <td style="width: 25%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Professional Fee</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $money($deal->estimated_professional_fee ?? ($deal->est_professional_fee ?? null)) }}</div>
                            </td>
                            <td style="width: 25%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Government Fees</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $money($deal->estimated_government_fees ?? ($deal->est_government_fee ?? 0)) }}</div>
                            </td>
                            <td style="width: 25%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Service Support Fee</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $money($deal->estimated_service_support_fee ?? ($deal->est_service_support_fee ?? 0)) }}</div>
                            </td>
                            <td style="width: 25%; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Payment Terms</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($deal->payment_terms ?: ($deal->proposal?->payment_terms ?: null)) }}</div>
                            </td>
                        </tr>
                    </table>

                    <!-- 9. PROPOSAL & INTERNAL ASSIGNMENT -->
                    <div style="background: #0b2a68; color: #ffffff; text-align: center; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 0; border-bottom: 1px solid #203354;">Proposal &amp; Internal Assignment</div>
                    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                        <tr>
                            <td style="width: 34%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Proposal Decision</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($deal->proposal_decision ?: ($deal->proposal?->status ?: null)) }}</div>
                            </td>
                            <td style="width: 33%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Decline Reason</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($deal->decline_reason ?: ($deal->proposal?->rejection_reason ?: null)) }}</div>
                            </td>
                            <td style="width: 33%; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Service Department / Unit</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($deal->service_department_unit ?: ($deal->service_department ?: ($deal->department ?: null))) }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 50%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Assigned Consultant</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($deal->assigned_consultant ?: $deal->lead_consultant) }}</div>
                            </td>
                            <td style="width: 50%; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;" colspan="2">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Assigned Associate</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($deal->assigned_associate ?: ($deal->lead_associate_assigned ?: $deal->lead_associate)) }}</div>
                            </td>
                        </tr>
                    </table>

                    <!-- 10. NOTES & APPROVAL -->
                    <div style="background: #0b2a68; color: #ffffff; text-align: center; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 0; border-bottom: 1px solid #203354;">Notes &amp; Approval</div>
                    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                        <tr>
                            <td style="width: 50%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 10px; vertical-align: top;">
                                <span style="display: block; font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Consultant Notes</span>
                                <div style="font-size: 11.5px; color: #0f172a; min-height: 24px;">{{ $display($deal->consultant_notes) }}</div>
                            </td>
                            <td style="width: 50%; border-bottom: 1px solid #203354; padding: 6px 10px; vertical-align: top;">
                                <span style="display: block; font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Associate Notes</span>
                                <div style="font-size: 11.5px; color: #0f172a; min-height: 24px;">{{ $display($deal->associate_notes) }}</div>
                            </td>
                        </tr>
                    </table>

                    <!-- 11. SIGNATURES AND APPROVAL RECORDS -->
                    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                        <tr>
                            <td style="width: 25%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Prepared By</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($deal->prepared_by ?: ($deal->created_by ?: $deal->owner_name)) }}</div>
                            </td>
                            <td style="width: 25%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Reviewed By</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($deal->reviewed_by ?: $deal->approved_by_name) }}</div>
                            </td>
                            <td style="width: 22%; border-right: 1px solid #203354; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Date</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $date($deal->internal_date ? \Illuminate\Support\Carbon::parse($deal->internal_date) : ($deal->approved_at ?: ($deal->approval_date ?: $deal->created_at))) }}</div>
                            </td>
                            <td style="width: 28%; border-bottom: 1px solid #203354; padding: 6px 8px; vertical-align: top;">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Client Fullname &amp; Signature</span>
                                <div style="font-size: 11px; color: #0f172a; min-height: 15px;">{{ $display($dfClientSignature) }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 17%; border-right: 1px solid #203354; padding: 6px 6px; vertical-align: top;">
                                <span style="display: block; font-size: 8.5px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Referred / Closed By</span>
                                <div style="font-size: 10.5px; color: #0f172a; min-height: 15px;">{{ $display($deal->referred_closed_by ?: ($deal->referred_by ?: $deal->owner_name)) }}</div>
                            </td>
                            <td style="width: 17%; border-right: 1px solid #203354; padding: 6px 6px; vertical-align: top;">
                                <span style="display: block; font-size: 8.5px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Sales &amp; Marketing</span>
                                <div style="font-size: 10.5px; color: #0f172a; min-height: 15px;">{{ $display($deal->internal_sales_marketing ?: ($deal->sales_marketing ?: $deal->sales_marketing_partner)) }}</div>
                            </td>
                            <td style="width: 17%; border-right: 1px solid #203354; padding: 6px 6px; vertical-align: top;">
                                <span style="display: block; font-size: 8.5px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Lead Consultant</span>
                                <div style="font-size: 10.5px; color: #0f172a; min-height: 15px;">{{ $display($deal->lead_consultant ?: $deal->assigned_consultant) }}</div>
                            </td>
                            <td style="width: 16%; border-right: 1px solid #203354; padding: 6px 6px; vertical-align: top;">
                                <span style="display: block; font-size: 8.5px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Lead Associate</span>
                                <div style="font-size: 10.5px; color: #0f172a; min-height: 15px;">{{ $display($deal->lead_associate_assigned ?: ($deal->lead_associate ?: $deal->assigned_associate)) }}</div>
                            </td>
                            <td style="width: 16%; border-right: 1px solid #203354; padding: 6px 6px; vertical-align: top;">
                                <span style="display: block; font-size: 8.5px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">Finance</span>
                                <div style="font-size: 10.5px; color: #0f172a; min-height: 15px;">{{ $display($deal->internal_finance ?: ($deal->finance ?: ($deal->assignedFinance?->name ?: $deal->finance_officer))) }}</div>
                            </td>
                            <td style="width: 17%; padding: 6px 6px; vertical-align: top;">
                                <span style="display: block; font-size: 8.5px; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 2px;">President</span>
                                <div style="font-size: 10.5px; color: #0f172a; min-height: 15px;">{{ $display($deal->internal_president ?: ($deal->president ?: 'John Kelly Abalde')) }}</div>
                            </td>
                        </tr>
                    </table>

                    <!-- 12. FOOTER -->
                    <div style="border-top: 1px solid #203354; padding: 5px 12px; text-align: right; font-size: 10px; font-style: italic; color: #64748b;">
                        John Kelly &amp; Company
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
                        @if ($canConfigureKyc ?? false)
                            <button type="button" class="start-action" onclick="openClientKycRequirementsModal()">Configure KYC</button>
                        @endif

                    </div>

                </div>

                @include('deals.partials.start-paper')

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
                    $proposalStatusText = $deal->getResolvedProposalStatus();

                    // Find the primary proposal to display in the banner
                    $displayProposal = $proposal;
                    if ($proposalVersions->isNotEmpty()) {
                        $approvedVer = $proposalVersions->first(fn ($p) => filled($p->client_approved_at) || strtolower((string) $p->status) === 'approved');
                        if ($approvedVer) {
                            $displayProposal = $approvedVer;
                        } elseif (!filled($displayProposal?->client_form_sent_at)) {
                            $sentVer = $proposalVersions->first(fn ($p) => filled($p->client_form_sent_at));
                            if ($sentVer) {
                                $displayProposal = $sentVer;
                            }
                        }
                    }

                    $proposalSentTimestamp = null;
                    $proposalSentVersion = 'Not sent yet';
                    $isProposalSent = false;

                    if ($displayProposal && $displayProposal->exists && filled($displayProposal->client_form_sent_at)) {
                        $isProposalSent = true;
                        $sentDateObj = $displayProposal->client_form_sent_at->copy()->timezone('Asia/Manila');
                        $proposalSentTimestamp = $sentDateObj->format('M d, Y h:i A');
                        $proposalSentVersion = 'V' . ($displayProposal->proposal_version ?: 1) . ' (Sent)';
                    } elseif (filled($deal->proposal_sent_at)) {
                        $isProposalSent = true;
                        $sentDateObj = $deal->proposal_sent_at->copy()->timezone('Asia/Manila');
                        $proposalSentTimestamp = $sentDateObj->format('M d, Y h:i A');
                        $proposalSentVersion = 'V' . ($displayProposal->proposal_version ?: 1) . ' (Sent)';
                    }

                    $hasApproval = filled($displayProposal?->client_approved_at) || strtolower((string) $displayProposal?->status) === 'approved' || filled($deal->approval_date) || in_array($deal->proposal_decision, ['Accepted', 'Approved']);

                    $approvedByName = $displayProposal?->client_approved_by_name ?: ($hasApproval ? ($deal->approval_name ?: 'Client') : '-');

                    $recipientEmailVal = $displayProposal?->client_form_sent_to_email ?: ($displayProposal?->recipient_email ?: ($deal->email ?: ($primaryContact?->email ?: '—')));

                    $displayTotalVal = (float) ($displayProposal?->price_total ?: ($deal->total_estimated_engagement_value ?: ($deal->amount ?: $proposalTotal)));

                    $serverSentSnapshotData = $isProposalSent ? [
                        'version' => $proposalSentVersion,
                        'versionNumber' => (int) ($displayProposal->proposal_version ?: 1),
                        'sentAt' => $proposalSentTimestamp,
                        'recipientEmail' => $recipientEmailVal !== '—' ? $recipientEmailVal : '',
                        'snapshotTotal' => $displayTotalVal,
                        'isApproved' => $hasApproval,
                        'approvedAt' => $displayProposal?->client_approved_at ? $displayProposal->client_approved_at->copy()->timezone('Asia/Manila')->format('M d, Y h:i A') : ($deal->approval_date ? $deal->approval_date->format('M d, Y') : null),
                        'approvedBy' => $approvedByName !== '-' ? $approvedByName : null
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
                                <span class="pwi-badge-version" id="pwiActiveVersionBadge">V{{ $displayProposal->proposal_version ?: 1 }}</span>
                            </div>
                        </div>

                        <div class="pwi-meta-item">
                            <span class="pwi-meta-label">Proposal Status</span>
                            <div class="pwi-meta-value">
                                <span class="pwi-badge-status" id="pwiStatusBadge">{{ \App\Services\DealProposalDecisions::LABELS[$displayProposal->client_decision_status ?? ''] ?? ($displayProposal->status ?: $proposalStatusText) }}</span>
                            </div>
                        </div>

                        <div class="pwi-meta-item">
                            <span class="pwi-meta-label">Sent to Client (Snapshot)</span>
                            <div class="pwi-meta-value">
                                <span id="pwiSentVersionText">{{ $isProposalSent ? $proposalSentVersion : 'Not sent yet' }}</span>
                            </div>
                        </div>

                        <div class="pwi-meta-item">
                            <span class="pwi-meta-label">Sent Timestamp</span>
                            <div class="pwi-meta-value" style="font-size: 13px; color: #475569;">
                                <span id="pwiSentAtText">{{ $isProposalSent ? $proposalSentTimestamp : '—' }}</span>
                            </div>
                        </div>

                        <div class="pwi-meta-item">
                            <span class="pwi-meta-label">Recipient Email</span>
                            <div class="pwi-meta-value" id="pwiRecipientEmail">
                                {{ $recipientEmailVal }}
                            </div>
                        </div>

                        <div class="pwi-meta-item">
                            <span class="pwi-meta-label">Prepared By</span>
                            <div class="pwi-meta-value" id="pwiPreparedBy">
                                {{ $displayProposal->prepared_by_name ?: ($deal->owner_name ?: '-') }}
                            </div>
                        </div>

                        <div class="pwi-meta-item">
                            <span class="pwi-meta-label">Approved By</span>
                            <div class="pwi-meta-value" id="pwiApprovedBy">
                                {{ $approvedByName }}
                            </div>
                        </div>

                        <div class="pwi-meta-item">
                            <span class="pwi-meta-label">Snapshot Total</span>
                            <div class="pwi-meta-value price" id="pwiSnapshotTotal">
                                {{ ($isProposalSent || $hasApproval) ? $money($displayTotalVal) : ($displayTotalVal > 0 ? $money($displayTotalVal) : '—') }}
                            </div>
                        </div>
                    </div>

                    @if ($displayProposal)
                        @include('deals.proposal.partials.decision-summary', ['proposal' => $displayProposal])
                    @endif
                    <div class="pwi-ledger-title" id="pwiLedgerTitleVersion">
                        PROPOSAL ITEM DECISION LEDGER
                    </div>

                    <div class="pwi-ledger-table-wrap">
                        <table class="pwi-ledger-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Type</th>
                                    <th>Client Decision</th>
                                    <th>Client Comment</th>
                                    <th>Authorized Representative</th>
                                    <th>Decision Date &amp; Time</th>
                                    <th>Proposal Version / Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($proposalDecisionLedger as $ledgerItem)
                                    <tr>
                                        <td>
                                            <div class="pwi-item-name">{{ $ledgerItem['name'] }}</div>
                                        </td>
                                        <td>
                                            {{ $ledgerItem['type'] ?: '—' }}
                                        </td>
                                        <td>
                                            <span class="pidl-decision-badge {{ $ledgerItem['decision_key'] }}">
                                                {{ $ledgerItem['decision'] }}
                                            </span>
                                        </td>
                                        <td class="pidl-comment">
                                            {{ filled($ledgerItem['comment']) ? $ledgerItem['comment'] : '—' }}
                                        </td>
                                        <td>
                                            {{ filled($ledgerItem['representative']) ? $ledgerItem['representative'] : '—' }}
                                        </td>
                                        <td>
                                            {{ $ledgerItem['responded_at'] ? $ledgerItem['responded_at']->copy()->timezone('Asia/Manila')->format('M d, Y h:i A') : '—' }}
                                        </td>
                                        <td>
                                            V{{ $ledgerItem['version'] }}{{ filled($ledgerItem['reference']) ? ' / '.$ledgerItem['reference'] : '' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="pidl-empty-state">No issued proposal item decisions are available for this Deal.</td>
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
                                    <h2 class="pwm-title" id="pwmModalTitle">{{ $deal->deal_code ?: '—' }} — {{ $contactName ?: ($deal->deal_title ?: 'Proposal') }}</h2>
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
                                            <input id="proposal_subject" name="subject" value="{{ old('subject', $proposal->subject ?? $deal->deal_title) }}">
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
                                            <textarea id="proposal_introduction" name="introduction">{{ old('introduction', $proposal->introduction) }}</textarea>
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
                                            @forelse($resolvedProposalItems as $index => $item)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $item['name'] }}</td>
                                                    <td style="text-align:right">{{ $proposalMoney($proposalLineAmount($item)) }}</td>
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
                                                @forelse($proposalServiceItems as $index => $item)
                                                    <tr>
                                                        <td style="text-align: center;">{{ $index + 1 }}</td>
                                                        <td>{{ $item['name'] }}</td>
                                                        <td>{{ $proposalCatalogCode($item) }}</td>
                                                        <td style="text-align: right;">{{ $proposalMoney($proposalLineAmount($item)) }}</td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="4" style="text-align: center;">No service selected</td></tr>
                                                @endforelse
                                                <tr style="font-weight: 700; background: #f8fafc;">
                                                    <td colspan="3" style="text-align: right;">Total</td>
                                                    <td style="text-align: right;">{{ $proposalMoney($proposalServicesTotal) }}</td>
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
                                                @forelse($proposalProductItems as $index => $item)
                                                    <tr>
                                                        <td style="text-align: center;">{{ $index + 1 }}</td>
                                                        <td>{{ $item['name'] }}</td>
                                                        <td>{{ $proposalCatalogCode($item) }}</td>
                                                        <td style="text-align: right;">{{ $proposalMoney($proposalLineAmount($item)) }}</td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="4" style="text-align: center;">No product selected</td></tr>
                                                @endforelse
                                                <tr style="font-weight: 700; background: #f8fafc;">
                                                    <td colspan="3" style="text-align: right;">Total</td>
                                                    <td style="text-align: right;">{{ $proposalMoney($proposalProductsTotal) }}</td>
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
                                                    <td style="text-align: right;">{{ $proposalMoney($proposalServicesTotal) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Total Product</td>
                                                    <td style="text-align: right;">{{ $proposalMoney($proposalProductsTotal) }}</td>
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

                    $financePaymentState = app(\App\Services\DealPaymentEligibility::class)->state($deal);
                    $financeProposalItems = $financePaymentState['items'];
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
                        $resolvedDealValue = $financePaymentState['approved_total'];
                        $resolvedProfFee = $resolvedDealValue;
                        $resolvedPricingModel = $deal->pricing_model ?? $deal->engagement_type ?? 'Fixed Price';
                        $resolvedPaymentTerms = $deal->resolved_payment_terms ?: (in_array($deal->pipeline_stage, ['Closed Won', 'Activation', 'Payment']) ? '100% Full Payment (Fully Paid)' : '100% Advance Payment');
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
                                                    onclick="openRecordPaymentModal({scope: 'item', itemId: {{ Illuminate\Support\Js::from((string) $item['id']) }}})">
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
                    $dealStartBatches = collect($deal->startRecords ?? []);
                    $totalBatchesCount = $dealStartBatches->count();
                    $activatedBatchesCount = $dealStartBatches->filter(function($r) {
                        $st = is_array($r) ? ($r['status'] ?? '') : ($r->status ?? '');
                        return in_array($st, ['Completed', 'Activated', 'Active', 'Issued']);
                    })->count();
                    $confirmedAssignmentsCount = $dealStartBatches->flatMap(function($b) {
                        $assignments = is_array($b) ? ($b['assignments'] ?? []) : ($b->assignments ?? []);
                        return collect($assignments);
                    })->filter(function($a) {
                        $st = is_array($a) ? ($a['status'] ?? '') : ($a->status ?? '');
                        return in_array($st, ['Confirmed', 'Acknowledged', 'Completed']);
                    })->count();
                    $teamConfirmationsCount = $confirmedAssignmentsCount > 0 
                        ? $confirmedAssignmentsCount 
                        : $dealStartBatches->filter(function($r) {
                            $st = is_array($r) ? ($r['status'] ?? '') : ($r->status ?? '');
                            return in_array($st, ['Team Confirmed', 'Activated', 'Active', 'Completed', 'Issued']);
                        })->count();
                    $readyBatchesCount = $dealStartBatches->filter(function($r) {
                        $st = is_array($r) ? ($r['status'] ?? '') : ($r->status ?? '');
                        return in_array($st, ['Draft', 'Ready', 'For Final Review', 'Team Confirmed']);
                    })->count();
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
                                            @php
                                                $bObj = is_array($batch) ? (object)$batch : $batch;
                                                $bId = $bObj->id ?? 1;
                                                $bCode = $bObj->start_code ?? ('ST-' . $bId);
                                                $bTitle = $bObj->start_title ?? ($bObj->batch_name ?? 'START Batch');
                                                $bScope = $bObj->scope ?? ($bObj->scope_of_work ?? 'Active Scope');
                                                $bStatus = $bObj->status ?? 'Active';
                                                $bDate = '-';
                                                if (isset($bObj->created_at)) {
                                                    $bDate = is_string($bObj->created_at) ? date('M d, Y', strtotime($bObj->created_at)) : (method_exists($bObj->created_at, 'format') ? $bObj->created_at->format('M d, Y') : '-');
                                                }
                                            @endphp
                                            <tr>
                                                <td style="white-space: nowrap;">
                                                    <div class="start-code-title">{{ $bCode }}</div>
                                                    <div class="start-code-subtitle">{{ $bTitle }}</div>
                                                </td>
                                                <td>
                                                    <div class="start-scope-cell">
                                                        <div class="start-scope-name">{{ $bScope }}</div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="start-badge start-badge-activated"><span class="start-dot"></span>{{ $bStatus }}</span>
                                                </td>
                                                <td style="white-space: nowrap;">
                                                    <button type="button" class="start-memo-btn-soft" onclick="openStartBatchMemoTab({{ $bId }})">
                                                        View Service Memo
                                                    </button>
                                                </td>
                                                <td style="white-space: nowrap;">
                                                    <div class="start-date-main">{{ $bDate }}</div>
                                                </td>
                                                <td class="start-action-cell" style="white-space: nowrap; display: flex; align-items: center; gap: 6px;">
                                                    <button type="button" class="start-btn-details-soft" onclick="openStartBatchDetails({{ $bId }})">
                                                        View Details
                                                    </button>
                                                    <button type="button" class="btn-consultation-more" title="Delete START Batch" style="color: #ef4444; border-color: #fecaca; background: #fff; width: 28px; height: 28px;" onclick="handleDeleteStartBatch(event, {{ $bId }})">
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
                            @if ($canConfigureKyc ?? false)
                                <button type="button" class="start-action" onclick="openClientKycRequirementsModal()">Configure KYC</button>
                            @endif

                        </div>

                    </div>


                    @include('deals.partials.start-paper')

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
                                    @php
                                        $stObj = is_array($stRecord) ? (object)$stRecord : $stRecord;
                                        $stId = $stObj->id ?? 1;
                                        $stCode = $stObj->start_code ?? ('ST-' . $stId);
                                        $stStatus = $stObj->status ?? 'Issued';
                                        $stDate = '-';
                                        if (isset($stObj->created_at)) {
                                            $stDate = is_string($stObj->created_at) ? date('M d, Y', strtotime($stObj->created_at)) : (method_exists($stObj->created_at, 'format') ? $stObj->created_at->format('M d, Y') : '-');
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <div>
                                                <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">START Intake &amp; Routing Tracker</div>
                                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">START activation and routing document</div>
                                            </div>
                                        </td>
                                        <td><span style="font-size: 13px; color: #334155; font-weight: 500;">Operational START</span></td>
                                        <td><span class="client-action-pill client-action-pill-completed"><span class="pill-dot"></span><span>{{ $stCode }}</span></span></td>
                                        <td style="font-size: 13px; color: #64748b; white-space: nowrap;">{{ $stDate }}</td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <button type="button" class="files-action-btn-view" onclick="printStartForm()">
                                                Open START
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div>
                                                <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">Service Memo {{ $stCode }}</div>
                                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Official service activation memorandum</div>
                                            </div>
                                        </td>
                                        <td><span style="font-size: 13px; color: #334155; font-weight: 500;">Service Memo</span></td>
                                        <td><span class="client-action-pill client-action-pill-completed"><span class="pill-dot"></span><span>{{ $stStatus }}</span></span></td>
                                        <td style="font-size: 13px; color: #64748b; white-space: nowrap;">{{ $stDate }}</td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <button type="button" class="files-action-btn-view" onclick="openStartMemoViewer({{ $stId }})">
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

                {{-- STATE 1: START INCOMPLETE --}}
                <div id="engagementStartRequiredCard" class="engagement-locked-card" style="background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 40px 24px; text-align: center; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: {{ !$isStartCompleted ? 'block' : 'none' }};">
                    <div style="width: 48px; height: 48px; background: #eff6ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; color: #2563eb;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 11 12 14 22 4"></polyline>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                    </div>
                    <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 6px;">START Workflow Required</h3>
                    <p id="engagementStartRequirements" style="font-size: 13.5px; color: #64748b; max-width: 520px; margin: 0 auto 18px; line-height: 1.5;">
                        Please complete the START workflow before accessing Engagement. Operational workspaces and downstream delivery tools are accessible once the START form or activation batch has been completed.
                    </p>
                    <button type="button" class="btn btn-primary" style="font-size: 13px; font-weight: 600; padding: 9px 20px; border-radius: 8px; background: #2563eb; color: #fff; border: none; cursor: pointer; box-shadow: 0 1px 2px rgba(37,99,235,0.2);" onclick="switchDealNavTab('start')">
                        Complete START Workflow →
                    </button>
                </div>

                {{-- STATE 2: START COMPLETED --}}
                <div id="engagementWorkspacesContainer" style="display: {{ $isStartCompleted ? 'block' : 'none' }};">
                    
                    {{-- OPERATIONAL WORKSPACES CARD --}}
                   
                            <div>
                                
                                
                                           {{-- WORKSPACE LAUNCHERS GRID --}}
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 6px;">


                            @if($hasRegular)
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px 20px; display: flex; flex-direction: column; justify-content: space-between;">
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                            <div style="width: 34px; height: 34px; border-radius: 8px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center;">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Regular Retainer Workspace</h3>
                                                <span style="font-size: 11px; color: #64748b;">Recurring / Retainer Execution</span>
                                            </div>
                                        </div>
                                        <p style="font-size: 12.5px; color: #64748b; line-height: 1.45; margin: 0 0 16px 0;">
                                            Manage recurring RSAT scopes, continuous tasks, regular work orders, time tracking, and periodic client reporting.
                                        </p>
                                    </div>
                                    <a href="{{ route('deals.regular', $deal->id) }}" class="btn btn-success" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; font-size: 13px; font-weight: 600; padding: 9px 16px; border-radius: 8px; background: #16a34a; color: #fff;">
                                        Open Regular Retainer Workspace →
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- DETAILS CARD --}}
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
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                @elseif($currentStage === 'Closed Lost')
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                                @else
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
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
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </span>
                            Edit Deal
                        </button>

                        {{-- Upload File --}}
                        <button type="button"
                                class="quick-action"
                                data-tooltip="Upload attachments or documents related to this deal."
                                onclick="openUploadFileModal()">
                            <span class="quick-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
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
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            </span>
                            View History
                        </button>
                    </div>

                    {{-- CARD 2: WORKSPACES (DEPENDS STRICTLY ON START COMPLETION) --}}
                    @php
                        $showWorkspaces = $isStartCompleted && ($hasProject || $hasRegular);
                    @endphp
                    <div class="quick-group quick-group-workspaces" id="quick-group-workspaces">
                        <div class="quick-group-title">
                            Workspaces
                        </div>

                        @if($showWorkspaces)
                            @if($hasRegular)
                                <a href="{{ route('deals.regular', $deal->id) }}" class="quick-action qa-workspace-btn" data-tooltip="Open the workspace for managing the ongoing recurring engagement." style="{{ ($hasProject && $hasRegular) ? 'margin-bottom: 8px;' : 'margin-bottom: 0;' }}">
                                    <span class="quick-icon">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                             <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                                        </svg>
                                    </span>
                                    Open Regular Retainer Workspace
                                </a>
                            @endif

                            @if($hasProject)
                                <a href="{{ route('deals.project', $deal->id) }}" class="quick-action qa-workspace-btn" data-tooltip="Open the workspace for managing this project's scope and delivery." style="margin-bottom: 0;">
                                    <span class="quick-icon">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

                    {{-- CARD 3: EXPORTS / DOCUMENT DOWNLOADS --}}
                    <div class="quick-group">
                        <div class="quick-group-title">
                            Exports
                        </div>

                        {{-- Download Consulting & Deal Form --}}
                        <a href="{{ route('deals.deal-form.download', $deal->id) }}"
                           class="quick-action"
                           data-tooltip="Download the official Consulting &amp; Deal Form document with accurate client and commercial records.">
                            <span class="quick-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="12" y1="18" x2="12" y2="12"></line>
                                    <line x1="9" y1="15" x2="15" y2="15"></line>
                                </svg>
                            </span>
                            Download Consulting &amp; Deal Form
                        </a>

                        {{-- Download Start Form --}}
                        <button type="button"
                                class="quick-action"
                                data-tooltip="Download or preview the official START document for this deal."
                                onclick="handleDownloadStart()">
                            <span class="quick-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                </svg>
                            </span>
                            Download Start Form
                        </button>

                        @if(filled($deal->consultant_notes) || filled($deal->consultation_date))
                            <button type="button"
                                    class="quick-action"
                                    data-tooltip="Download the consulting document or consultation form for this deal."
                                    onclick="handleDownloadConsulting()">
                                <span class="quick-icon">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                                    </svg>
                                </span>
                                Download Consulting
                            </button>
                        @endif

                        {{-- Proposal Actions (Conditional if Proposal exists) --}}
                        @if($hasProposal)
                            {{-- View Proposal --}}
                            <button type="button"
                                    class="quick-action"
                                    data-tooltip="Open the current proposal and its available revisions."
                                    onclick="switchDealNavTab('proposal')">
                                <span class="quick-icon">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                </span>
                                View Proposal
                            </button>

                            @if($isProposalReadyForReview)
                                <button type="button"
                                        class="quick-action"
                                        data-tooltip="Open the client-facing proposal review and approval experience."
                                        onclick="openClientReviewPortalModal()">
                                    <span class="quick-icon">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </span>
                                    Client Review
                                </button>
                            @endif

                            {{-- Download PDF --}}
                            <button type="button"
                                    class="quick-action"
                                    data-tooltip="Download the proposal document as a PDF."
                                    style="margin-bottom: 0;"
                                    onclick="downloadProposalPdf()">
                                <span class="quick-icon">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                                </span>
                                Download Proposal PDF
                            </button>
                        @endif
                    </div>

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

                        {{-- Edit Deal --}}
                        <button type="button"
                                class="quick-action"
                                data-tooltip="Update the deal information that is still editable."
                                onclick="openDealDrawer('edit', {{ $deal->id }})">
                            <span class="quick-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </span>
                            Edit Deal
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

                        {{-- Edit Deal --}}
                        <button type="button"
                                class="quick-action"
                                data-tooltip="Update the deal information that is still editable."
                                onclick="openDealDrawer('edit', {{ $deal->id }})">
                            <span class="quick-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </span>
                            Edit Deal
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

                        {{-- Edit Deal --}}
                        <button type="button"
                                class="quick-action"
                                data-tooltip="Update the deal information that is still editable."
                                onclick="openDealDrawer('edit', {{ $deal->id }})">
                            <span class="quick-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </span>
                            Edit Deal
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

                        {{-- Edit Deal --}}
                        <button type="button"
                                class="quick-action"
                                data-tooltip="Update the deal information that is still editable."
                                onclick="openDealDrawer('edit', {{ $deal->id }})">
                            <span class="quick-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </span>
                            Edit Deal
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
                                onclick="downloadProposalPdf()">
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

                        @if(app(\App\Services\DealFinanceDocuments::class)->canUpload(auth()->user()))
                        <button type="button" class="quick-action" onclick="openFinanceDocumentUpload()" data-tooltip="Choose Upload Quotation or Upload Payment Notice.">
                            <span class="quick-icon" aria-hidden="true"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 16V4m-5 5 5-5 5 5M4 16v4h16v-4"/></svg></span> Upload Finance Document
                        </button>
                        @endif
                        {{-- Edit Deal --}}
                        <button type="button"
                                class="quick-action"
                                data-tooltip="Update the deal information that is still editable."
                                onclick="openDealDrawer('edit', {{ $deal->id }})">
                            <span class="quick-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </span>
                            Edit Deal
                        </button>

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

                        @if($isStartCompleted)
                            <button type="button"
                                    class="quick-action"
                                    id="btnStartCompleted"
                                    data-tooltip="Confirm completed START requirements and move the pipeline to Activation."
                                    style="border: 1px solid #2563eb; color: #2563eb; background: #ffffff; font-weight: 700;"
                                    onclick="handleStartCompletedTransition()">
                                START Completed
                            </button>
                        @endif

                        {{-- Edit Deal --}}
                        <button type="button"
                                class="quick-action"
                                data-tooltip="Update the deal information that is still editable."
                                onclick="openDealDrawer('edit', {{ $deal->id }})">
                            <span class="quick-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </span>
                            Edit Deal
                        </button>

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

                        {{-- Edit Deal --}}
                        <button type="button"
                                class="quick-action"
                                data-tooltip="Update the deal information that is still editable."
                                onclick="openDealDrawer('edit', {{ $deal->id }})">
                            <span class="quick-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </span>
                            Edit Deal
                        </button>

                        <button type="button"
                                class="quick-action"
                                data-tooltip="Upload attachments or documents related to this deal."
                                onclick="openUploadFileModal()">
                            <span class="quick-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            </span>
                            Upload File
                        </button>

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
                        Quick Actions
                    </h2>

                    <div class="quick-group">
                        <div class="quick-group-title">
                            Engagement Actions
                        </div>
                                           
                                {{-- Closed Won and Closed Lost --}}
                                @if(
                                    strtolower(trim((string) $currentStage)) === 'activation'
                                    && filter_var($isStartCompleted, FILTER_VALIDATE_BOOLEAN)
                                )
                                    <div class="engagement-outcome-actions">
                                        <button
                                            type="button"
                                            class="quick-action"
                                            onclick="handleFinalizeDealOutcome('Closed Won')"
                                            data-tooltip="Mark this deal as successfully won."
                                            style="color: #16805b;"
                                        >
                                            Closed Won
                                        </button>

                                        <button
                                            type="button"
                                            class="quick-action"
                                            onclick="handleFinalizeDealOutcome('Closed Lost')"
                                            data-tooltip="Mark this deal as lost."
                                            style="color: #b91c1c;"
                                        >
                                            Closed Lost
                                        </button>
                                    </div>
                                @endif

                            

                        {{-- Edit Deal --}}
                        <button type="button"
                                class="quick-action"
                                data-tooltip="Update the deal information that is still editable."
                                onclick="openDealDrawer('edit', {{ $deal->id }})">
                            <span class="quick-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </span>
                            Edit Deal
                        </button>

                        @if($currentStage === 'Activation' && $isStartCompleted && $engagementWorkspacesComplete)
                            <button type="button" class="quick-action" onclick="handleFinalizeDealOutcome('Closed Won')" data-tooltip="Confirm that the engagement was successfully completed." style="color:#16805b;">Closed Won</button>
                            <button type="button" class="quick-action" onclick="handleFinalizeDealOutcome('Closed Lost')" data-tooltip="Confirm the unsuccessful business outcome and provide a reason." style="color:#b91c1c; margin-bottom:0;">Closed Lost</button>
                        @endif
                    </div>

                    @php
                        $showWorkspacesEngagement = $isStartCompleted && ($hasProject || $hasRegular);
                    @endphp
                    <div class="quick-group quick-group-workspaces" id="quick-group-workspaces-engagement" style="{{ $showWorkspacesEngagement ? 'display: block;' : 'display: none;' }}">
                        <div class="quick-group-title">
                            Workspaces
                        </div>

                        @if($hasRegular)
                            <a href="{{ route('deals.regular', $deal->id) }}" class="quick-action qa-workspace-btn" data-tooltip="Open the workspace for managing the ongoing recurring engagement." style="{{ ($hasProject && $hasRegular) ? 'margin-bottom: 7px;' : 'margin-bottom: 0;' }}">
                                <span class="quick-icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                         <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                                    </svg>
                                </span>
                                Open Regular Retainer Workspace
                            </a>
                        @endif

                        @if($hasProject)
                            <a href="{{ route('deals.project', $deal->id) }}" class="quick-action qa-workspace-btn" data-tooltip="Open the workspace for managing this project's scope and delivery." style="margin-bottom: 0;">
                                <span class="quick-icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                                        <polyline points="12 2 2 7 12 12 22 7 12 2"></polyline>
                                        <polyline points="2 12 12 17 22 12"></polyline>
                                    </svg>
                                </span>
                                Open Project Workspace
                            </a>
                        @endif
                    </div>

                </div>
            </div>

            {{-- =================================================
                 QUICK ACTIONS (HISTORY TAB)
            ================================================== --}}
            <div id="sidebar-qa-history" class="sidebar-qa-panel" style="display: none;">
                <div class="quick-actions">

                    <h2 class="quick-actions-title">
                        Quick Actions
                    </h2>

                    <div class="quick-group">
                        <div class="quick-group-title">
                            History Actions
                        </div>

                        {{-- Edit Deal --}}
                        <button type="button"
                                class="quick-action"
                                data-tooltip="Update the deal information that is still editable."
                                onclick="openDealDrawer('edit', {{ $deal->id }})">
                            <span class="quick-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </span>
                            Edit Deal
                        </button>

                        {{-- Refresh Timeline --}}
                        <button type="button"
                                class="quick-action"
                                data-tooltip="Refresh the history activity timeline."
                                onclick="if(typeof initHistoryTab === 'function') { initHistoryTab(); }">
                            <span class="quick-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                            </span>
                            Refresh History
                        </button>
                    </div>

                </div>
            </div>

            {{-- =================================================
                 RELATED CONTACT
            ================================================== --}}

            <div class="related-card">
                @php
                    $isBusinessDeal = strtolower((string)($deal->customer_type ?: ($deal->account?->account_type ?: ''))) === 'business';

                    if ($isBusinessDeal) {
                        // Business Deal:
                        // Primary name: Associated business or company name
                        $primaryDisplayName = $deal->companyRecord?->company_name
                            ?: ($deal->company_name
                                ?: ($deal->account?->company?->company_name
                                    ?: ($deal->company
                                        ?: ($deal->account?->account_name ?: 'Not provided'))));

                        // Associated contact person (secondary)
                        $contactModel = $deal->contact
                            ?: ($deal->primaryContact
                                ?: ($deal->companyRecord?->primaryContact
                                    ?: ($deal->companyRecord?->contacts?->first()
                                        ?: ($deal->dealContacts->first() ?: null))));

                        $secondaryContactName = $contactModel
                            ? trim(($contactModel->first_name ?? '') . ' ' . ($contactModel->last_name ?? ''))
                            : trim(implode(' ', array_filter([$deal->salutation, $deal->first_name, $deal->middle_initial, $deal->last_name, $deal->name_extension])) ?: ($deal->primary_contact_name ?: ''));

                        $secondaryPosition = $contactModel?->position
                            ?: ($deal->contact_position ?: null);
                        if (blank($secondaryPosition)) {
                            $resolvedSecPos = $deal->getResolvedContactPosition();
                            $secondaryPosition = ($resolvedSecPos !== 'Not provided') ? $resolvedSecPos : null;
                        }

                        if (filled($secondaryContactName)) {
                            $positionDisplay = $secondaryContactName . (filled($secondaryPosition) ? ' • ' . $secondaryPosition : '');
                        } else {
                            $positionDisplay = filled($secondaryPosition) ? $secondaryPosition : '-';
                        }

                        $relatedEmail = $contactModel?->email
                            ?: ($deal->email
                                ?: ($deal->companyRecord?->email
                                    ?: ($deal->dealContacts->first()?->email ?: null)));

                        $rawPhone = $contactModel?->phone
                            ?: ($deal->getResolvedContactNumber()
                                ?: ($deal->mobile_number
                                    ?: ($deal->phone
                                        ?: ($deal->companyRecord?->phone ?: null))));

                        $words = array_values(array_filter(preg_split('/\s+/', trim((string)$primaryDisplayName))));
                        if (count($words) >= 2) {
                            $avatarInitials = strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
                        } elseif (count($words) === 1 && mb_strlen($words[0]) >= 2) {
                            $avatarInitials = strtoupper(mb_substr($words[0], 0, 2));
                        } elseif (count($words) === 1) {
                            $avatarInitials = strtoupper(mb_substr($words[0], 0, 1));
                        } else {
                            $avatarInitials = 'B';
                        }
                    } else {
                        // Individual Deal:
                        // Primary name: Individual's actual full name
                        $contactModel = $deal->contact
                            ?: ($deal->primaryContact
                                ?: ($deal->account?->individualContact
                                    ?: ($deal->dealContacts->first() ?: null)));

                        $individualName = $contactModel
                            ? trim(($contactModel->first_name ?? '') . ' ' . ($contactModel->last_name ?? ''))
                            : '';

                        if (blank($individualName)) {
                            $individualName = trim(implode(' ', array_filter([
                                $deal->salutation,
                                $deal->first_name,
                                $deal->middle_initial,
                                $deal->last_name,
                                $deal->name_extension
                            ])));
                        }

                        if (blank($individualName)) {
                            $individualName = $deal->primary_contact_name ?: ($deal->contact_name ?: ($deal->account?->account_name ?: 'Not provided'));
                        }

                        $primaryDisplayName = $individualName;

                        $pos = $contactModel?->position
                            ?: ($deal->contact_position ?: null);
                        if (blank($pos)) {
                            $resolvedPos = $deal->getResolvedContactPosition();
                            $pos = ($resolvedPos !== 'Not provided') ? $resolvedPos : null;
                        }
                        $positionDisplay = filled($pos) ? $pos : '-';

                        $relatedEmail = $contactModel?->email
                            ?: ($deal->email
                                ?: ($deal->dealContacts->first()?->email ?: null));

                        $rawPhone = $contactModel?->phone
                            ?: ($deal->getResolvedContactNumber()
                                ?: ($deal->mobile_number
                                    ?: ($deal->phone ?: null)));

                        $words = array_values(array_filter(preg_split('/\s+/', trim((string)$primaryDisplayName))));
                        if (count($words) >= 2) {
                            $avatarInitials = strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[count($words) - 1], 0, 1));
                        } elseif (count($words) === 1 && mb_strlen($words[0]) >= 2) {
                            $avatarInitials = strtoupper(mb_substr($words[0], 0, 2));
                        } elseif (count($words) === 1) {
                            $avatarInitials = strtoupper(mb_substr($words[0], 0, 1));
                        } else {
                            $avatarInitials = 'C';
                        }
                    }

                    $formattedPhone = filled($rawPhone) ? \App\Services\PhoneFormatter::format($rawPhone) : null;
                @endphp

                <h2 class="related-card-title">
                    Related Contact
                </h2>

                <div class="related-contact-body">
                    <div class="related-avatar" title="{{ $primaryDisplayName }}">
                        {{ $avatarInitials }}
                    </div>

                    <div class="related-details">
                        <div class="related-name" title="{{ $primaryDisplayName }}">
                            {{ $primaryDisplayName }}
                        </div>

                        <div class="related-position">
                            {{ $positionDisplay }}
                        </div>

                        <div class="related-row">
                            <span class="related-row-icon" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                            </span>
                            <span class="related-row-text">
                                {{ $relatedEmail ?: 'Not provided' }}
                            </span>
                        </div>

                        <div class="related-row">
                            <span class="related-row-icon" aria-hidden="true">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                            </span>
                            <span class="related-row-text">
                                {{ $formattedPhone ?: 'Not provided' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>



        </aside>

    </div>

</div>

{{-- MODAL: UPDATE DEAL STAGE --}}
<div id="dealUpdateStageModal" class="inquiry-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeUpdateStageModal()">
    <div class="inquiry-modal-card" style="max-width: 480px;">
        <div class="inquiry-modal-head">
            <h3 class="inquiry-modal-heading">Update Deal Stage</h3>
            <button type="button" class="inquiry-modal-close-btn" onclick="closeUpdateStageModal()">&times;</button>
        </div>
        <form id="dealUpdateStageForm" onsubmit="handleSaveStageUpdate(event)">
            <div class="inquiry-modal-body-content">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; font-size: 13px; color: #475569;">
                    Current Active Stage: <strong style="color: #1e3a8a;">{{ $currentStage }}</strong>
                </div>

                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Select New Stage <span class="req-star">*</span></label>
                    <select id="dealUpdateStageSelect" class="inquiry-form-select" required style="width: 100%; height: 40px; font-size: 13px;">
                        @foreach($stages as $stg)
                            <option value="{{ $stg }}" @selected($stg === $currentStage)>{{ $stg }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="inquiry-form-group" style="margin-top: 14px;">
                    <label class="inquiry-form-label">Stage Update Notes / Reason</label>
                    <textarea id="dealUpdateStageNotes" class="inquiry-form-textarea" rows="3" placeholder="Explain the reason for advancing or updating the deal stage..."></textarea>
                </div>
            </div>
            <div class="inquiry-modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeUpdateStageModal()">Cancel</button>
                <button type="submit" id="btnSaveStageUpdate" class="btn-modal-save">Update Stage</button>
            </div>
        </form>
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
    <button type="button" class="inquiry-context-menu-item" onclick="handleContextMenuAction('edit')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"></path></svg>
        Edit
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

{{-- MODAL: CLIENT-SPECIFIC KYC REQUIREMENTS --}}
@if ($canConfigureKyc ?? false)
<div id="clientKycRequirementsModal" class="inquiry-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeClientKycRequirementsModal()">
    <div class="inquiry-modal-card" role="dialog" aria-modal="true" aria-labelledby="clientKycRequirementsTitle">
        <div class="inquiry-modal-head">
            <div>
                <h3 id="clientKycRequirementsTitle" class="inquiry-modal-heading">Configure Client KYC Requirements</h3>
                <p class="mt-1 text-xs text-slate-500">{{ $deal->company ?: ($deal->primaryContact?->full_name ?: $deal->primary_contact_name ?: 'Selected client') }} - {{ $clientKycOrganization === 'sole_proprietorship' ? 'Sole Proprietorship' : ($clientKycOrganization === 'individual' ? 'Individual' : 'Juridical Entity') }}</p>
            </div>
            <button type="button" class="inquiry-modal-close-btn" onclick="closeClientKycRequirementsModal()">&times;</button>
        </div>
        <form id="clientKycRequirementsForm" onsubmit="saveClientKycRequirements(event)">
            <input type="hidden" id="clientKycOrganizationType" value="{{ $clientKycOrganization }}">
            <div class="inquiry-modal-body-content">
                <p class="mb-3 text-sm text-slate-600">Choose only the documents required for this client. Assignment does not mark a document as submitted or verified.</p>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach ($clientKycCatalog as $kycItem)
                        @php $kycLabel = (string) ($kycItem['label'] ?? ''); @endphp
                        @if ($kycLabel !== '')
                            <label class="flex min-w-0 items-start gap-2 rounded border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                <input type="checkbox" class="client-kyc-requirement-option mt-0.5" value="{{ $kycLabel }}" {{ in_array($kycLabel, $clientKycAssignedLabels, true) ? 'checked' : '' }}>
                                <span class="min-w-0 break-words">{{ $kycLabel }}</span>
                            </label>
                        @endif
                    @endforeach
                </div>
                <div class="mt-4">
                    <label for="clientKycCustomRequirement" class="inquiry-form-label">Add another requirement</label>
                    <div class="flex gap-2">
                        <input type="text" id="clientKycCustomRequirement" class="inquiry-form-input" maxlength="255" placeholder="Enter a client-specific document">
                        <button type="button" class="btn-add-line-item shrink-0" onclick="addClientKycCustomRequirement()">Add</button>
                    </div>
                    <div id="clientKycCustomRequirementList" class="mt-2 flex flex-wrap gap-2"></div>
                </div>
                <p id="clientKycRequirementsError" class="mt-3 hidden text-sm text-red-600" role="alert"></p>
            </div>
            <div class="inquiry-modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeClientKycRequirementsModal()">Cancel</button>
                <button type="submit" id="clientKycRequirementsSave" class="btn-modal-primary">Save Requirements</button>
            </div>
        </form>
    </div>
</div>
@endif

<style>
    .line-item-picker { position: relative; width: 100%; }
    .line-item-picker-options { position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 20; max-height: min(320px, 45vh); overflow-y: auto; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 4px 12px #0f172a14; }
    .line-item-picker-option { display: block; width: 100%; border: 0; border-radius: 0; padding: 9px 12px; background: #fff; color: #334155; font: inherit; font-size: 13px; text-align: left; cursor: pointer; white-space: normal; overflow-wrap: anywhere; }
    .line-item-picker-option:hover, .line-item-picker-option.is-active { background: #eff6ff; }
    .line-item-picker-service { padding-left: 28px; }
    .line-item-picker-identification { padding: 8px 12px; font-size: 12px; font-weight: 600; color: #475569; border-top: 1px solid #f1f5f9; }
    .line-item-picker-product { padding-left: 28px; }
    .line-item-picker-heading, .line-item-picker-empty, .line-item-picker-context { padding: 7px 12px; color: #64748b; font-size: 12px; }
    .line-item-picker-heading { font-weight: 600; background: #f8fafc; }
    .line-item-picker-context { padding: 5px 0 0; }
    @media (max-width: 640px) { .line-item-picker-option { padding-top: 12px; padding-bottom: 12px; } }
</style>

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
                        <select id="lineItemType" class="inquiry-form-select" required onchange="handleLineItemTypeChange()">
                            <option value="SERVICE">Service</option>
                            <option value="PRODUCT">Product</option>
                        </select>
                    </div>

                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Route <span class="req-star">*</span></label>
                        <select id="lineItemRoute" class="inquiry-form-select" required disabled>
                            <option value="Project">Project</option>
                            <option value="Regular">Regular</option>
                        </select>
                    </div>
                </div>

                <div class="inquiry-form-group" id="lineItemCatalogEntryGroup">
                    <label for="lineItemNameSelect" class="inquiry-form-label">Select Item / Name <span class="req-star">*</span></label>
                    <div class="line-item-picker">
                        <input type="text" id="lineItemNameSelect" class="inquiry-form-input" maxlength="255" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="lineItemCatalogChoices" placeholder="Search Services and Products or type a custom name" onfocus="openLineItemPicker()" oninput="handleLineItemSelectChange(this.value)" onkeydown="handleLineItemPickerKeydown(event)" onblur="if (!document.getElementById('lineItemCatalogEntryGroup').contains(event.relatedTarget)) closeLineItemPicker()" required>
                        <div id="lineItemCatalogChoices" class="line-item-picker-options" role="listbox" aria-label="Services and Products" style="display: none;" onmousedown="event.preventDefault()" onclick="handleLineItemPickerClick(event)"></div>
                    </div>
                    <div id="lineItemServiceContext" class="line-item-picker-context" role="status" aria-label="Associated Service" style="display: none;"></div>
                    <input type="hidden" id="lineItemName" value="">
                </div>

                <div class="inquiry-form-group">
                    <label class="inquiry-form-label">Description / Scope</label>
                    <textarea id="lineItemDescription" class="inquiry-form-textarea" rows="2" placeholder="Detailed scope or specification..."></textarea>
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

                <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px;">
                    <div class="inquiry-form-group">
                        <label class="inquiry-form-label">Unit Price (₱) <span class="req-star">*</span></label>
                        <input type="number" id="lineItemUnitPrice" class="inquiry-form-input" min="0" step="0.01" value="0.00" required readonly style="background: #f8fafc; color: #475569; cursor: not-allowed; border-color: #e2e8f0;">
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
                    <input type="hidden" id="lineItemBilling" value="">
                    <div class="line-item-billing-options" role="radiogroup" aria-label="Billing Terms / Schedule">
                        <label class="line-item-billing-card">
                            <input type="radio" name="lineItemBillingChoice" value="Full Payment Before Service" required>
                            <span>Full Payment Before Service</span>
                        </label>
                        <label class="line-item-billing-card">
                            <input type="radio" name="lineItemBillingChoice" value="50% Downpayment / 50% Completion">
                            <span>50% Downpayment / 50% Completion</span>
                        </label>
                        <label class="line-item-billing-card">
                            <input type="radio" name="lineItemBillingChoice" value="Milestone-Based Payment">
                            <span>Milestone-Based Payment</span>
                        </label>
                        <label class="line-item-billing-card">
                            <input type="radio" name="lineItemBillingChoice" value="Monthly Retainer">
                            <span>Monthly Retainer</span>
                        </label>
                        <label class="line-item-billing-card">
                            <input type="radio" name="lineItemBillingChoice" value="Others">
                            <span>Others</span>
                        </label>
                    </div>
                    <input type="text" id="lineItemBillingOther" class="inquiry-form-input" placeholder="Enter other billing terms" aria-label="Other billing terms" style="display: none;">
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
            <p id="lineItemDeleteConfirmationText" style="margin: 0; color: #475569; font-size: 14px; line-height: 1.5;">
                Are you sure you want to delete this line item? This will update the commercial pricing totals.
                Any issued proposal versions and client decision history will be retained.
            </p>
        </div>
        <div class="inquiry-modal-foot">
            <button type="button" class="btn-modal-cancel" onclick="closeDeleteLineItemModal()">Cancel</button>
            <button type="button" class="btn-modal-danger" onclick="handleConfirmDeleteLineItem()">Delete</button>
        </div>
    </div>
</div>

{{-- MODAL: VIEW DEAL LINE ITEM --}}
<div id="lineItemViewModal" class="inquiry-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeLineItemViewModal()">
    <div class="inquiry-modal-card">
        <div class="inquiry-modal-head">
            <h3 class="inquiry-modal-heading">LINE ITEM DETAILS</h3>
            <button type="button" class="inquiry-modal-close-btn" onclick="closeLineItemViewModal()">&times;</button>
        </div>
        <div class="inquiry-modal-body-content">
            <div class="inquiry-view-group">
                <div class="inquiry-view-label">Item / Name</div>
                <div id="viewLineItemName" class="inquiry-view-value highlight"></div>
            </div>
            <div class="inquiry-modal-grid-2">
                <div class="inquiry-view-group">
                    <div class="inquiry-view-label">Type</div>
                    <div id="viewLineItemType" class="inquiry-view-value"></div>
                </div>
                <div class="inquiry-view-group">
                    <div class="inquiry-view-label">Quantity</div>
                    <div id="viewLineItemQuantity" class="inquiry-view-value"></div>
                </div>
            </div>
            <div class="inquiry-view-group">
                <div class="inquiry-view-label">Description / Scope</div>
                <div id="viewLineItemDescription" class="inquiry-view-value text-block"></div>
            </div>
            <div class="inquiry-modal-grid-2">
                <div class="inquiry-view-group">
                    <div class="inquiry-view-label">Unit Price</div>
                    <div id="viewLineItemUnitPrice" class="inquiry-view-value"></div>
                </div>
                <div class="inquiry-view-group">
                    <div class="inquiry-view-label">Total</div>
                    <div id="viewLineItemTotal" class="inquiry-view-value highlight"></div>
                </div>
            </div>
            <div class="inquiry-modal-grid-2">
                <div class="inquiry-view-group">
                    <div class="inquiry-view-label">Discount</div>
                    <div id="viewLineItemDiscount" class="inquiry-view-value"></div>
                </div>
                <div class="inquiry-view-group">
                    <div class="inquiry-view-label">Tax</div>
                    <div id="viewLineItemTax" class="inquiry-view-value"></div>
                </div>
            </div>
            <div class="inquiry-modal-grid-2">
                <div class="inquiry-view-group">
                    <div class="inquiry-view-label">Billing Terms</div>
                    <div id="viewLineItemBilling" class="inquiry-view-value"></div>
                </div>
                <div class="inquiry-view-group">
                    <div class="inquiry-view-label">Route</div>
                    <div id="viewLineItemRoute" class="inquiry-view-value"></div>
                </div>
            </div>
        </div>
        <div class="inquiry-modal-foot">
            <button type="button" class="btn-modal-cancel" onclick="closeLineItemViewModal()">Close</button>
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

            <div class="start-modal-head-actions" style="display: flex; align-items: center; gap: 8px;">
                <a id="startMemoDownloadPdfBtn" href="#" class="start-btn-primary" style="background: #002b66; border-color: #002b66; padding: 6px 14px; font-size: 12.5px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;" target="_blank">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span>Download PDF</span>
                </a>

                <button type="button" class="start-btn-primary" onclick="printStartMemo()" style="background: #ffffff; color: #002b66; border-color: #cbd5e1; padding: 6px 14px; font-size: 12.5px;">
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
                        <span class="start-memo-meta-value" id="smHeaderFrom">START</span>
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
     MODAL 2.5: ASSIGNMENT ACTION PROMPT (FOR ASSIGNED USER)
     ========================================================= --}}
<div id="startAssignmentActionModal" class="start-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeStartAssignmentActionModal()">
    <div class="start-modal-card confirm-dialog" style="max-width: 500px;">
        <div class="start-modal-head">
            <div>
                <h3 class="start-modal-main-title" style="color: #0f172a; font-size: 18px;">Respond to START Assignment</h3>
                <p class="start-modal-subtitle" style="font-size: 12.5px; color: #64748b; margin-top: 3px;">Confirm whether you accept or decline this operational role assignment.</p>
            </div>
            <button type="button" class="start-modal-close-btn" onclick="closeStartAssignmentActionModal()">&times;</button>
        </div>
        <div class="start-modal-body-scroll" style="padding: 18px 24px;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                    <div>
                        <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; color: #64748b;">ASSIGNED ROLE</span>
                        <div id="saamRoleLabel" style="font-size: 16px; font-weight: 700; color: #1e3a8a; margin-top: 2px;">Role Name</div>
                    </div>
                    <span id="saamRequiredBadge" class="start-modal-code-badge" style="background: #eff6ff; color: #1d4ed8;">Required Role</span>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 12.5px; color: #475569; border-top: 1px solid #e2e8f0; padding-top: 10px;">
                    <div>
                        <span style="color: #94a3b8; font-size: 11px; display: block;">START BATCH:</span>
                        <strong id="saamBatchCode" style="color: #0f172a;">ST-2026-001</strong>
                    </div>
                    <div>
                        <span style="color: #94a3b8; font-size: 11px; display: block;">DEAL:</span>
                        <strong style="color: #0f172a;">{{ $deal->deal_code ?: ('CONDEAL-' . $deal->id) }}</strong>
                    </div>
                </div>
            </div>
            <p style="font-size: 13px; color: #334155; line-height: 1.5; margin: 0;">
                Please indicate your decision. Accepting confirms that you are ready and available to execute the deliverables assigned to this role.
            </p>
        </div>
        <div class="finance-modal-footer" style="padding: 14px 24px; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; gap: 10px; background: #ffffff;">
                <button type="button" class="btn-assign-decline" onclick="handleActionModalDecline()" style="min-width: 150px; height: 36px;">
                    Decline
                </button>
                <button type="button" class="start-btn-primary" onclick="handleActionModalAccept()" style="padding: 8px 18px; min-width: 150px; height: 36px; font-size: 13px;">
                    Accept Assignment
                </button>
        </div>
    </div>
</div>

{{-- =========================================================
     MODAL 3: DECLINE / CANNOT ACCEPT ASSIGNMENT MODAL
     ========================================================= --}}
<div id="startDeclineModal" class="start-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeStartDeclineModal()">
    <div class="start-modal-card confirm-dialog" style="max-width: 480px;">
        <div class="start-modal-head">
            <h3 class="start-modal-main-title" style="color: #be123c;">Decline Assignment</h3>
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
                    <select id="startNewAssignRoleInput" class="inquiry-form-select" required style="height: 38px;">
                        <option value="" selected disabled>Select a role</option>
                        <option value="Sales &amp; Marketing">Sales &amp; Marketing</option>
                        <option value="Lead Consultant">Lead Consultant</option>
                        <option value="Lead Associate Assigned">Lead Associate Assigned</option>
                        <option value="Consultant">Consultant</option>
                        <option value="Associate">Associate</option>
                    </select>
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
    $dealDefaultPaymentTerms = $deal->resolved_payment_terms ?: 'Full Payment Before Service';
    $dealDefaultScopeOfWork = $deal->scope_of_work ?: '';
    
    $dealAvailedItems = [];
    if (!empty($deal->services_products) && is_array($deal->services_products)) {
        foreach ($deal->services_products as $idx => $sp) {
            $resolvedItem = $dealResolvedLineItems[$idx] ?? [];
            $spName = is_array($sp) ? ($sp['name'] ?? $resolvedItem['name'] ?? '') : (string)($resolvedItem['name'] ?? $sp);
            if (filled($spName)) {
                $dealAvailedItems[] = [
                    'name' => $spName,
                    'type' => is_array($sp) && isset($sp['type']) ? $sp['type'] : ($resolvedItem['type'] ?? 'SERVICE'),
                    'price' => (float) ($resolvedItem['unit_price'] ?? (is_array($sp) ? ($sp['unitPrice'] ?? $sp['unit_price'] ?? $sp['price'] ?? $sp['amount'] ?? 0) : 0)),
                    'unit' => is_array($sp) && isset($sp['unit']) ? $sp['unit'] : ($resolvedItem['unit'] ?? 'lot'),
                    'description' => is_array($sp) && isset($sp['description']) ? $sp['description'] : ($resolvedItem['description'] ?? ($dealDefaultScopeOfWork ?: 'Scope item description'))
                ];
            }
        }
    }

    $dealLineItems = [];
    if (!empty($deal->services_products) && is_array($deal->services_products)) {
        foreach (array_values($deal->services_products) as $idx => $item) {
            $resolvedItem = $dealResolvedLineItems[$idx] ?? [];
            $lineItemId = is_array($item) && filled($item['id'] ?? null)
                ? $item['id']
                : 'legacy-'.($idx + 1);
            $dealLineItems[] = [
                'id' => $lineItemId,
                'service_id' => is_array($item) ? ($item['service_id'] ?? null) : null,
                'associated_service_id' => is_array($item) ? ($item['associated_service_id'] ?? null) : null,
                'product_id' => is_array($item) ? ($item['product_id'] ?? null) : null,
                'type' => is_array($item) && isset($item['type']) ? $item['type'] : ($resolvedItem['type'] ?? 'SERVICE'),
                'name' => is_array($item) ? ($item['name'] ?? $resolvedItem['name'] ?? 'Commercial Scope Item') : ($resolvedItem['name'] ?? $item),
                'description' => is_array($item) ? ($item['description'] ?? $resolvedItem['description'] ?? ($dealDefaultScopeOfWork ?: '')) : ($resolvedItem['description'] ?? ($dealDefaultScopeOfWork ?: '')),
                'qty' => is_array($item) && isset($item['qty']) ? (float)$item['qty'] : (float) ($resolvedItem['qty'] ?? 1),
                'unit' => is_array($item) && isset($item['unit']) ? $item['unit'] : ($resolvedItem['unit'] ?? 'lot'),
                'unitPrice' => (float) ($resolvedItem['unit_price'] ?? (is_array($item)
                    ? ($item['unitPrice'] ?? $item['unit_price'] ?? $item['price'] ?? $item['amount'] ?? 0)
                    : 0)),
                'discount' => is_array($item) && isset($item['discount']) ? (float)$item['discount'] : 0,
                'tax' => is_array($item) && isset($item['tax']) ? (float)$item['tax'] : 0,
                'billing' => is_array($item) && filled($item['billing'] ?? null)
                    ? $item['billing']
                    : ($deal->resolved_payment_terms ?: 'Full Payment Before Service'),
                'route' => is_array($item) && isset($item['route']) ? $item['route'] : $dealCalculatedRoute,
                'custom' => is_array($item) && ! empty($item['custom']),
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

        'QualResult' => $deal->getResolvedQualificationResult(),
        'QualBudget' => (float) ($deal->qualification_budget ?: ($deal->amount ?: ($deal->total_estimated_engagement_value ?: 0))),
        'Amount' => (float) ($deal->amount ?: ($deal->total_estimated_engagement_value ?: 0)),
        'DecisionMaker' => $deal->decision_maker ?: 'Yes',
        'ClientNeed' => $deal->client_need ?: ($deal->qualification_need ?: 'Standard Business Engagement'),
        'ExpectedClose' => $deal->expected_close ? $deal->expected_close->format('Y-m-d') : '',
        'QualTimeline' => $deal->qualification_timeline ?: '1-3 months',
        'QualAuth' => $deal->qualification_authority ?: 'Decision Maker',
        'QualNeed' => $deal->qualification_need ?: 'Standard Business Engagement',
        'QualNotes' => $deal->getResolvedQualificationNotes(),

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
        'PayTerms' => $deal->resolved_payment_terms ?: 'Standard terms',
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
        'csrfToken' => csrf_token(),
        'dealId' => $deal->id,
        'dealCode' => $deal->deal_code ?: '',
        'dealNumber' => $deal->deal_number ?: ('D-' . $deal->id),
        'clientName' => $contactName ?: ($deal->primary_contact_name ?: ($deal->company_name ?: '')),
        'clientEmail' => $deal->email ?: ($primaryContact?->email ?: ($deal->contact?->email ?: '')),
        'currentStage' => $currentStage ?? 'Inquiry',
        'activeVersionCode' => 'V' . ($displayProposal->proposal_version ?: 1),
        'proposalVersions' => $proposalVersions->map(function ($version) use ($deal, $primaryContact) {
            $isSent = filled($version->client_form_sent_at);
            $sentAtFormatted = $isSent
                ? $version->client_form_sent_at->copy()->timezone('Asia/Manila')->format('M d, Y h:i A')
                : null;
            $isApproved = filled($version->client_approved_at) || strtolower((string) $version->status) === 'approved';
            $approvedAtFormatted = $version->client_approved_at
                ? $version->client_approved_at->copy()->timezone('Asia/Manila')->format('M d, Y h:i A')
                : null;
            $approvedBy = $version->client_approved_by_name ?: ($isApproved ? ($deal->approval_name ?: 'Client') : null);
            $recipientEmail = $version->client_form_sent_to_email ?: ($version->recipient_email ?: ($deal->email ?: ($primaryContact?->email ?: '')));
            $preparedBy = $version->prepared_by_name ?: ($deal->owner_name ?: '-');
            $snapshotTotal = (float) ($version->price_total ?: ($deal->total_estimated_engagement_value ?: ($deal->amount ?: 0)));

            return [
                'id' => $version->id,
                'version' => 'V' . $version->proposal_version,
                'versionNumber' => (int) $version->proposal_version,
                'status' => $version->status ?: 'Draft',
                'isCurrent' => (bool) $version->is_current,
                'createdAt' => $version->created_at?->toIso8601String(),
                'isSent' => $isSent,
                'sentVersion' => $isSent ? ('V' . $version->proposal_version . ' (Sent)') : 'Not sent yet',
                'sentAt' => $sentAtFormatted,
                'recipientEmail' => $recipientEmail,
                'preparedBy' => $preparedBy,
                'isApproved' => $isApproved,
                'approvedBy' => $approvedBy,
                'approvedAt' => $approvedAtFormatted,
                'snapshotTotal' => $snapshotTotal,
                'formData' => [
                    'subject' => $version->subject ?? '',
                    'introduction' => $version->introduction ?? '',
                    'scope' => $version->scope ?? '',
                    'terms' => $version->terms ?? '',
                    'discount' => (float) ($version->discount ?? 0),
                    'tax' => (float) ($version->tax ?? 0),
                ],
            ];
        })->values(),
        'dealAmount' => (float) ($deal->total_estimated_engagement_value ?: ($deal->amount ?: 0)),
        'dealCalculatedRoute' => $dealCalculatedRoute,
        'dealDefaultPaymentTerms' => $dealDefaultPaymentTerms,
        'dealDefaultScopeOfWork' => $dealDefaultScopeOfWork,
        'dealAvailedItems' => $dealAvailedItems,
        'lineItemCatalog' => collect($lineItemChoices = app(\App\Services\DealCatalogSelection::class)->choices($deal))->only(['services', 'products'])->flatten(1)->values()->all(),
        'serviceIdentifications' => $lineItemChoices['service_identifications'],
        'dealLineItems' => $dealLineItems,
        'dealInquiries' => $dealInquiries,
        'dealConsultations' => $dealConsultations,
        'dealProposalItems' => $dealProposalItems ?? [],
        'proposalItems' => $proposalItems ?? [],
        'startBatches' => $startBatchesJson ?? [],
        'isStartCompleted' => (bool) $isStartCompleted,
        'startStatus' => $startStatus,
        'proposalStatus' => $proposalStatus,
        'commercialStatus' => $commercialStatus,
        'casaStatus' => $casaStatus,
        'paymentStatus' => $paymentStatus,
        'kycStatus' => $kycStatus,
        'hasProject' => (bool) $hasProject,
        'hasRegular' => (bool) $hasRegular,
        'isHybrid' => (bool) $isHybrid,
        'projectWorkspaceUrl' => route('deals.project', $deal->id),
        'regularWorkspaceUrl' => route('deals.regular', $deal->id),
        'financeProposalItems' => $financeProposalItems ?? [],
        'financePaymentState' => $financePaymentState,
        'financePaymentsUrl' => route('deals.payment-allocations.index', $deal->id),
        'serverSentSnapshotData' => $serverSentSnapshotData ?? null,
        'isProposalSent' => (bool) ($isProposalSent ?? false),
        'isQuotationCompleted' => (bool) ($isQuotationCompleted ?? false),
        'isPaymentNoticeSent' => (bool) ($proposal?->invoice_status === 'payment_notice_sent'),
        'isQuotationNoticeSent' => (bool) ($proposal?->quotation_status === 'quotation_notice_sent'),
        'financeDocuments' => app(\App\Services\DealFinanceDocuments::class)->canView(auth()->user()) ? app(\App\Services\DealFinanceDocuments::class)->documents($deal) : [],
        'financeDocumentsUrl' => route('deals.finance-documents.index', $deal->id),
        'financeDocumentsUploadUrl' => route('deals.finance-documents.store', $deal->id),
        'quotationFinanceFileUrl' => $proposal?->quotation_finance_file_path ? route('uploads.show', ['path' => $proposal->quotation_finance_file_path]) : null,
        'quotationClientFileUrl' => $proposal?->quotation_client_file_path ? route('uploads.show', ['path' => $proposal->quotation_client_file_path]) : null,
        'invoiceFileUrl' => $proposal?->invoice_file_path ? route('uploads.show', ['path' => $proposal->invoice_file_path]) : null,
        'hasMoreHistories' => ($deal->histories()->count() > 20),
        'currentUser' => auth()->check() ? auth()->user()->name : ($deal->finance ?: ($deal->owner_name ?: 'System Administrator')),
        'currentUserId' => auth()->id(),
        'currentUserEmail' => auth()->check() ? auth()->user()->email : '',
        'currentUserRole' => auth()->check() ? (auth()->user()->role ?? '') : '',
        'ownerName' => $deal->owner_name ?: 'System Administrator',
        'leadConsultant' => $deal->lead_consultant ?: ($deal->owner_name ?: 'Consultant'),
        'engagementType' => $deal->engagement_type ?: '',
        'scopeOfWork' => $deal->scope_of_work ?: '',
        'paymentTerms' => $deal->resolved_payment_terms ?: '',
        'proposalTotals' => [
            'services' => (float) ($proposalServicesTotal ?? 0),
            'products' => (float) ($proposalProductsTotal ?? 0),
            'discount' => (float) ($proposalDiscount ?? 0),
            'tax' => (float) ($proposalTax ?? 0),
        ],
        'discountItemsList' => $discountItemsList ?? [],
        'defaults' => $wfDefaults,
        'routes' => [
            'startDownload' => route('deals.start.download', $deal->id),
            'proposalRevisionStore' => route('deals.proposal.revisions.store', $deal->id),
            'stageWorkflowSave' => route('deals.stage-workflow.save', $deal->id),
        'finalOutcome' => route('deals.final-outcome', $deal->id),
            'stageUpdate' => route('deals.stage.update', $deal->id),
            'inquiriesStore' => route('deals.inquiries.store', $deal->id),
            'consultationsStore' => route('deals.consultations.store', $deal->id),
            'lineItemsStore' => route('deals.line-items.store', $deal->id),
            'kycRequirementsStore' => route('deals.start.kyc-requirements.update', $deal->id),
            'inquiriesBase' => url('/deals/' . $deal->id . '/inquiries'),
            'consultationsBase' => url('/deals/' . $deal->id . '/consultations'),
            'lineItemsBase' => url('/deals/' . $deal->id . '/line-items'),
        ]
    ];
@endphp

<script>
    window.DEAL_BOOTSTRAP = {!! json_encode($dealBootstrap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};

    window.clientKycCustomRequirements = [];
    window.openClientKycRequirementsModal = function() {
        const modal = document.getElementById('clientKycRequirementsModal');
        if (!modal) return;
        window.clientKycCustomRequirements = [];
        window.renderClientKycCustomRequirements();
        const error = document.getElementById('clientKycRequirementsError');
        if (error) error.classList.add('hidden');
        modal.style.display = 'flex';
    };
    window.closeClientKycRequirementsModal = function() {
        const modal = document.getElementById('clientKycRequirementsModal');
        if (modal) modal.style.display = 'none';
    };
    window.addClientKycCustomRequirement = function() {
        const input = document.getElementById('clientKycCustomRequirement');
        const label = (input?.value || '').trim();
        if (!label) return;
        const existsInCatalog = Array.from(document.querySelectorAll('.client-kyc-requirement-option'))
            .some(option => option.value.trim().toLowerCase() === label.toLowerCase());
        const existsAsCustom = window.clientKycCustomRequirements.some(item => item.toLowerCase() === label.toLowerCase());
        if (!existsInCatalog && !existsAsCustom) window.clientKycCustomRequirements.push(label);
        if (input) input.value = '';
        window.renderClientKycCustomRequirements();
    };
    window.renderClientKycCustomRequirements = function() {
        const container = document.getElementById('clientKycCustomRequirementList');
        if (!container) return;
        container.replaceChildren();
        window.clientKycCustomRequirements.forEach((label, index) => {
            const chip = document.createElement('span');
            chip.className = 'inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs text-blue-800';
            chip.append(document.createTextNode(label));
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'font-bold';
            remove.setAttribute('aria-label', `Remove ${label}`);
            remove.textContent = '×';
            remove.addEventListener('click', () => {
                window.clientKycCustomRequirements.splice(index, 1);
                window.renderClientKycCustomRequirements();
            });
            chip.append(remove);
            container.append(chip);
        });
    };
    window.saveClientKycRequirements = async function(event) {
        event.preventDefault();
        const button = document.getElementById('clientKycRequirementsSave');
        const error = document.getElementById('clientKycRequirementsError');
        const buttonText = button?.textContent || 'Save Requirements';
        if (button) { button.disabled = true; button.textContent = 'Saving…'; }
        if (error) { error.textContent = ''; error.classList.add('hidden'); }
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const requirements = Array.from(document.querySelectorAll('.client-kyc-requirement-option:checked')).map(option => option.value);
            const response = await fetch(window.DEAL_BOOTSTRAP?.routes?.kycRequirementsStore || '', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    organization_type: document.getElementById('clientKycOrganizationType')?.value || '',
                    requirements,
                    custom_requirements: window.clientKycCustomRequirements || []
                })
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.success !== true) throw new Error(data.message || 'Unable to save KYC requirements.');
            window.location.reload();
        } catch (saveError) {
            if (error) { error.textContent = saveError.message || 'Unable to save KYC requirements.'; error.classList.remove('hidden'); }
        } finally {
            if (button) { button.disabled = false; button.textContent = buttonText; }
        }
    };
    document.getElementById('clientKycCustomRequirement')?.addEventListener('keydown', event => {
        if (event.key === 'Enter') { event.preventDefault(); window.addClientKycCustomRequirement(); }
    });

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
        if (!window.DEAL_BOOTSTRAP?.isProposalSent) {
            alert('This proposal is currently in Draft. Please send the proposal to the client before dispatching client action requests.');
            return;
        }
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

    window.openUpdateStageModal = function() {
        var modal = document.getElementById('dealUpdateStageModal');
        if (modal) {
            modal.style.display = 'flex';
        }
    };

    window.closeUpdateStageModal = function() {
        var modal = document.getElementById('dealUpdateStageModal');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    window.handleSaveStageUpdate = async function(event) {
        event.preventDefault();
        var select = document.getElementById('dealUpdateStageSelect');
        var notesInput = document.getElementById('dealUpdateStageNotes');
        var btn = document.getElementById('btnSaveStageUpdate');
        var newStage = select ? select.value : '';
        var notes = notesInput ? notesInput.value.trim() : '';

        if (!newStage) return;

        if (btn) {
            btn.disabled = true;
            btn.innerText = 'Updating...';
        }

        try {
            var url = "{{ route('deals.stage.update', $deal->id) }}";
            var csrf = "{{ csrf_token() }}";
            var response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    pipeline_stage: newStage,
                    notes: notes
                })
            });

            var data = await response.json();
            if (response.ok && data.success !== false) {
                closeUpdateStageModal();
                window.location.reload();
            } else {
                alert(data.message || 'Failed to update stage.');
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = 'Update Stage';
                }
            }
        } catch (e) {
            window.location.reload();
        }
    };

    window.handleDownloadStart = function() {
        window.downloadStartPdf();
    };

    window.handleDownloadConsulting = function() {
        var hasConsultations = Boolean(window.DEAL_BOOTSTRAP?.dealConsultations && window.DEAL_BOOTSTRAP.dealConsultations.length > 0);
        @if(filled($deal->consultant_notes) || filled($deal->consultation_date))
            var hasNotes = true;
        @else
            var hasNotes = false;
        @endif

        if (!hasConsultations && !hasNotes) {
            if (typeof showWorkflowToast === 'function') {
                showWorkflowToast('Consulting document is not available yet. Please record a consultation session in the Consultation tab first.', 'warning');
            } else {
                alert('Consulting document is not available yet. Please record a consultation session in the Consultation tab first.');
            }
            return;
        }
        window.location.href = "{{ route('deals.consulting.download', $deal->id) }}";
    };

    window.handleDownloadDealForm = function() {
        window.location.href = "{{ route('deals.deal-form.download', $deal->id) }}";
    };

    window.downloadProposalPdf = function() {
        window.location.href = "{{ route('deals.proposal.download', $deal->id) }}";
    };

    window.downloadStartPdf = function() {
        window.location.href = window.DEAL_BOOTSTRAP?.routes?.startDownload || "{{ route('deals.start.download', $deal->id) }}";
    };

    window.printProposalPDF = function() {
        var modal = document.getElementById('proposalWorkspaceModal');
        if (modal && modal.style.display !== 'none') {
            window.print();
        } else {
            window.open("{{ route('deals.proposal.download', $deal->id) }}", "_blank");
        }
    };
</script>

{{-- MODAL: START Workflow Required --}}
<div id="engagementStartRequiredModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(2px);">
    <div style="background: #ffffff; border-radius: 14px; max-width: 440px; width: 92%; padding: 26px 24px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
            <div style="width: 40px; height: 40px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 11 12 14 22 4"></polyline>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
            </div>
            <div>
                <h3 id="engagementStartModalTitle" style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">START Workflow Required</h3>
                <span id="engagementStartModalSubtitle" style="font-size: 12px; color: #64748b;">Engagement Access Precondition</span>
            </div>
        </div>
        <p id="engagementStartModalText" style="font-size: 13.5px; color: #334155; line-height: 1.5; margin: 0 0 20px 0;">
            Please complete the START workflow before accessing Engagement.
        </p>
        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
            <button type="button" class="btn btn-secondary" style="font-size: 13px; font-weight: 500; padding: 8px 16px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #475569; cursor: pointer;" onclick="closeEngagementStartRequiredModal()">
                Dismiss
            </button>
            <button type="button" class="btn btn-primary" style="font-size: 13px; font-weight: 600; padding: 8px 18px; border-radius: 8px; background: #2563eb; color: #fff; border: none; cursor: pointer; box-shadow: 0 1px 2px rgba(37,99,235,0.2);" onclick="closeEngagementStartRequiredModal(); switchDealNavTab('start');">
                Go to START Workflow →
            </button>
        </div>
    </div>
</div>

<script src="{{ asset('js/deal-finance-documents.js') }}?v={{ filemtime(public_path('js/deal-finance-documents.js')) }}"></script>
<script src="{{ asset('js/deals-engine.js') }}?v={{ file_exists(public_path('js/deals-engine.js')) ? filemtime(public_path('js/deals-engine.js')) : '1.0' }}"></script>

@php
    $startKycData = app(\App\Services\ProjectProvisioner::class)->resolveDealStartKyc($deal);
    $startOrgType = $startKycData['organization_type'] ?? 'juridical';
    $isSoleKyc = in_array($startOrgType, ['sole_proprietorship', 'sole'], true);
    $isIndividualKyc = $startOrgType === 'individual';
    $activeKycGroup = $isSoleKyc ? 'sole' : ($isIndividualKyc ? 'individual' : 'juridical');
    $activeKycList = collect($startKycData[$activeKycGroup] ?? [])
        ->filter(fn ($item) => is_array($item) && (bool) ($item['required'] ?? false))
        ->values()
        ->all();

    $dealContactName = trim(
        implode(' ', array_filter([
            $deal->salutation,
            $deal->first_name,
            $deal->middle_initial,
            $deal->last_name,
            $deal->name_extension
        ]))
    ) ?: ($deal->primary_contact_name ?: ($deal->contact?->full_name ?: '—'));

    $dealBusinessName = $deal->company ?: '—';
    $dealRefNo = $deal->deal_code ?: '—';

    // Service areas
    $serviceAreasText = is_array($deal->service_areas) ? implode(', ', array_filter($deal->service_areas)) : ($deal->service_areas ?: '—');

    // Products
    $productsText = is_array($deal->products) ? implode(', ', array_filter($deal->products)) : ($deal->products ?: ($deal->product_type ?: '—'));

    // Services
    $servicesText = is_array($deal->services) ? implode(', ', array_filter($deal->services)) : ($deal->services ?: '');
    if (empty($servicesText) && !empty($deal->services_products)) {
        $servicesText = is_array($deal->services_products) 
            ? implode(', ', array_map(fn($item) => is_array($item) ? ($item['name'] ?? $item['title'] ?? '') : (string)$item, array_filter($deal->services_products))) 
            : (string)$deal->services_products;
    }
    if (empty($servicesText)) {
        $servicesText = '—';
    }

    // Engagement Type
    $engagementTypeText = $deal->engagement_type ?: ($deal->hasProjectEngagement() ? 'Project' : ($deal->hasRegularEngagement() ? 'Retainer' : 'Project'));

    // Dates
    $dateFormStr = optional($deal->created_at)->format('m/d/Y') ?: now()->format('m/d/Y');
    $dateStartedStr = optional($deal->planned_start_date)->format('m/d/Y') ?: '';
    $dateCompletedStr = optional($deal->estimated_completion_date)->format('m/d/Y') ?: '';

    // START status
    $dealStartBatches = collect($deal->startRecords ?? [])->whenEmpty(fn () => collect($deal->start_batches ?? []));
    $currentStartStatus = 'Draft';
    if ($deal->isStartCompleted()) {
        $currentStartStatus = 'Completed';
    } elseif ($dealStartBatches->isNotEmpty()) {
        $latestBatch = $dealStartBatches->sortByDesc('id')->first();
        $currentStartStatus = data_get($latestBatch, 'status') ?: 'Draft';
    }

    // Engagement Requirements
    $startReqRows = collect();
    if ($dealStartBatches->isNotEmpty()) {
        foreach ($dealStartBatches as $batch) {
            $items = data_get($batch, 'activated_items', []);
            if (!empty($items) && (is_array($items) || $items instanceof \Traversable)) {
                foreach ($items as $item) {
                    $startReqRows->push([
                        'req' => data_get($item, 'name') ?: (data_get($item, 'requirement') ?: (is_string($item) ? $item : 'Requirement')),
                        'notes' => data_get($batch, 'notes') ?: (data_get($batch, 'status') ?: '—'),
                        'purpose' => 'START intake',
                        'provided_by' => 'Client',
                        'submitted_to' => 'Sales & Marketing',
                        'assigned_to' => data_get($batch, 'assignee') ?: ($deal->assigned_associate ?? 'Project Team'),
                        'timeline' => data_get($batch, 'opened_date') ? \Illuminate\Support\Carbon::parse(data_get($batch, 'opened_date'))->format('m/d/Y') : 'To be scheduled',
                    ]);
                }
            } else {
                $startReqRows->push([
                    'req' => data_get($batch, 'batch_name') ?: (data_get($batch, 'start_code') ?: 'START intake'),
                    'notes' => data_get($batch, 'notes') ?: (data_get($batch, 'status') ?: '—'),
                    'purpose' => 'START intake',
                    'provided_by' => 'Client',
                    'submitted_to' => 'Sales & Marketing',
                    'assigned_to' => data_get($batch, 'assignee') ?: ($deal->assigned_associate ?? 'Project Team'),
                    'timeline' => data_get($batch, 'opened_date') ? \Illuminate\Support\Carbon::parse(data_get($batch, 'opened_date'))->format('m/d/Y') : 'To be scheduled',
                ]);
            }
        }
    }
    if ($startReqRows->isEmpty()) {
        $startReqRows->push([
            'req' => 'Initial intake and supporting documents',
            'notes' => '',
            'purpose' => 'START intake',
            'provided_by' => 'Client',
            'submitted_to' => 'Sales & Marketing',
            'assigned_to' => $clearanceLeadConsultant ?? 'John Kelly Abalde',
            'timeline' => 'To be scheduled',
        ]);
    }

    $cLeadConsultant = $clearanceLeadConsultant ?? ($deal->lead_consultant ?: ($deal->assigned_consultant ?: 'John Kelly Abalde'));
    $cLeadAssociate = $clearanceLeadAssociate ?? ($deal->lead_associate ?: ($deal->assigned_associate ?: 'John Kelly Abalde'));
    $cSalesMarketing = $clearanceSalesMarketing ?? ($deal->internal_sales_marketing ?: ($deal->sales_marketing ?: 'Mari Louise Chua'));
    $cTeamLead = $deal->team_lead ?? '';
@endphp

<div class="start-paper" id="startPaperContainer">
    {{-- TOP STATUS BAR --}}
    <div class="start-status-row">
        <div>
            START Status:
            <span class="start-status">
                {{ $currentStartStatus }}
            </span>
        </div>
        <div class="approval-required">
            ADMIN APPROVAL REQUIRED
        </div>
    </div>

    {{-- HEADER --}}
    <table class="start-header-table">
        <tr>
            <td style="width: 25%; vertical-align: middle;">
                <img src="{{ asset('images/imaglogo.png') }}" alt="John Kelly &amp; Company" style="height: 44px; width: auto; max-width: 100%; display: block;">
            </td>
            <td class="start-title-center" style="width: 50%; vertical-align: middle;">
                <div class="start-title">
                    SERVICE TASK ACTIVATION AND<br>ROUTING TRACKER (START)
                </div>
                <div class="start-subtitle">
                    CASA-F-010-v1.0-07.16.26
                </div>
            </td>
            <td style="width: 25%;"></td>
        </tr>
    </table>

    {{-- METADATA 3-COLUMN GRID --}}
    <table class="start-meta-grid">
        <tr>
            {{-- Column 1 --}}
            <td style="width: 38%;">
                <div class="start-meta-row">
                    <span class="start-meta-label">CLIENT NAME:</span>
                    <span class="start-meta-value">{{ $dealContactName }}</span>
                </div>
                <div class="start-meta-row">
                    <span class="start-meta-label">BUSINESS NAME:</span>
                    <span class="start-meta-value">{{ $dealBusinessName }}</span>
                </div>
                <div class="start-meta-row">
                    <span class="start-meta-label">CONDEAL REF NO.:</span>
                    <span class="start-meta-value">{{ $dealRefNo }}</span>
                </div>
                <div class="start-meta-row">
                    <span class="start-meta-label">SERVICE AREA:</span>
                    <span class="start-meta-value">{{ $serviceAreasText }}</span>
                </div>
            </td>

            {{-- Column 2 --}}
            <td style="width: 34%;">
                <div class="start-meta-row">
                    <span class="start-meta-label">PRODUCT:</span>
                    <span class="start-meta-value">{{ $productsText }}</span>
                </div>
                <div class="start-meta-row" style="align-items: flex-start;">
                    <span class="start-meta-label" style="padding-top: 1px;">SERVICES:</span>
                    <span class="start-meta-value">{{ $servicesText }}</span>
                </div>
                <div class="start-meta-row">
                    <span class="start-meta-label">ENGAGEMENT TYPE:</span>
                    <span class="start-meta-value">{{ $engagementTypeText }}</span>
                </div>
            </td>

            {{-- Column 3 --}}
            <td style="width: 28%;">
                <div class="start-meta-row">
                    <span class="start-meta-label">DATE:</span>
                    <span class="start-meta-value">{{ $dateFormStr }}</span>
                </div>
                <div class="start-meta-row">
                    <span class="start-meta-label">DATE STARTED:</span>
                    <span class="start-meta-value">{{ $dateStartedStr }}</span>
                </div>
                <div class="start-meta-row">
                    <span class="start-meta-label">DATE COMPLETED:</span>
                    <span class="start-meta-value">{{ $dateCompletedStr }}</span>
                </div>
            </td>
        </tr>
    </table>

    {{-- SECTION: CLIENT DUE DILIGENCE (KYC) DOCUMENTS --}}
    <div class="start-section-title">
        Client Due Diligence (KYC) Documents
    </div>
    <div class="start-subhead">
        {{ $isSoleKyc ? 'SOLE / NATURAL PERSON / INDIVIDUAL' : ($isIndividualKyc ? 'INDIVIDUAL' : 'JURIDICAL ENTITY (Corporation / OPC / Partnership / Cooperative)') }}
    </div>

    <div class="start-kyc-container">
        <div class="start-kyc-grid">
            @foreach($activeKycList as $kycItem)
                @php
                    $isProvided = ($kycItem['status'] ?? '') === 'provided';
                @endphp
                <div class="start-kyc-item">
                    <span class="start-kyc-chk {{ $isProvided ? 'checked' : '' }}">
                        @if($isProvided) ✓ @endif
                    </span>
                    <span>{{ $kycItem['label'] ?? '' }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- SECTION: ENGAGEMENT-SPECIFIC REQUIREMENTS --}}
    <div class="start-section-title">
        Engagement-Specific Requirements
    </div>

    <table class="start-req-table">
        <thead>
            <tr>
                <th style="width: 4%;">No.</th>
                <th style="width: 26%;">Requirement / Document</th>
                <th style="width: 7%;">Notes</th>
                <th style="width: 12%;">Purpose</th>
                <th style="width: 10%;">Provided By</th>
                <th style="width: 13%;">Submitted To</th>
                <th style="width: 14%;">Assigned To</th>
                <th style="width: 14%;">Timeline</th>
            </tr>
        </thead>
        <tbody>
            @foreach($startReqRows as $idx => $row)
                <tr>
                    <td class="center">{{ $idx + 1 }}</td>
                    <td>{{ $row['req'] }}</td>
                    <td class="center">{{ $row['notes'] ?: '—' }}</td>
                    <td>{{ $row['purpose'] }}</td>
                    <td>{{ $row['provided_by'] }}</td>
                    <td>{{ $row['submitted_to'] }}</td>
                    <td>{{ $row['assigned_to'] }}</td>
                    <td>{{ $row['timeline'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- SECTION: CLEARANCE --}}
    <div class="clearance-header">
        CLEARANCE
    </div>

    <table class="clearance-table">
        <tr>
            <td style="width: 25%;">
                <div class="clearance-box">
                    <div class="clearance-role-title">ASSIGNED TO REGULAR/PROJECT TEAM LEAD</div>
                    <div class="clearance-person-name">{{ $cTeamLead ?: '—' }}</div>
                    <div class="clearance-sig-note">Signature over Printed Name</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="clearance-box">
                    <div class="clearance-role-title">LEAD CONSULTANT CONFIRMED</div>
                    <div class="clearance-person-name">{{ $cLeadConsultant }}</div>
                    <div class="clearance-sig-note">Signature over Printed Name</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="clearance-box">
                    <div class="clearance-role-title">LEAD ASSOCIATE ASSIGNED</div>
                    <div class="clearance-person-name">{{ $cLeadAssociate }}</div>
                    <div class="clearance-sig-note">Signature over Printed Name</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="clearance-box">
                    <div class="clearance-role-title">SALES &amp; MARKETING</div>
                    <div class="clearance-person-name">{{ $cSalesMarketing }}</div>
                    <div class="clearance-sig-note">Signature over Printed Name</div>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="3" class="custodian-left">
                <div class="custodian-title">Record Custodian (Name and Signature)</div>
                <div class="custodian-name">Record Custodian</div>
                <div class="clearance-sig-note" style="width: 60%; margin: 6px auto 0 auto;"></div>
            </td>
            <td class="custodian-dates">
                <div class="custodian-date-row">
                    <span style="font-weight: 700; font-size: 7.5px;">Date Recorded:</span>
                    <span class="custodian-date-val">{{ $dateFormStr }}</span>
                </div>
                <div class="custodian-date-row" style="margin-top: 10px;">
                    <span style="font-weight: 700; font-size: 7.5px;">Date Signed:</span>
                    <span class="custodian-date-val"></span>
                </div>
            </td>
        </tr>
    </table>

    {{-- SECTION: REJECTION / HOLD REASON --}}
    <div class="rejection-section">
        <div class="rejection-label">
            REJECTION / HOLD REASON
        </div>
        <div class="rejection-input-box">
            @if(filled($deal->rejection_reason ?? null))
                <div style="padding: 6px; font-size: 8px; color: #dc2626;">{{ $deal->rejection_reason }}</div>
            @elseif(filled($deal->hold_reason ?? null))
                <div style="padding: 6px; font-size: 8px; color: #d97706;">{{ $deal->hold_reason }}</div>
            @endif
        </div>
    </div>
</div>

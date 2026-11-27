@extends('layouts.app')

@section('title', 'Deals | John Kelly & Company')

@push('styles')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/deals-kanban.css') }}?v={{ file_exists(public_path('css/deals-kanban.css')) ? filemtime(public_path('css/deals-kanban.css')) : '1.0' }}">
@endpush
@endpush

@section('content')
<main class="main"> 
 
            <section class="page"> 
 
 
                {{-- ================================================= 
                     PAGE HEADER 
                ================================================== --}} 
 
                <div class="page-header"> 
 
                    <div> 
 
                        <h1 class="page-title"> 
                            Deals 
                        </h1> 
 
                        <div class="page-subtitle"> 
                            Track and manage your sales pipeline 
                        </div> 
 
                    </div> 
 
 
                    <div style="display: flex; align-items: center; gap: 8px;">
                        {{-- ADD INQUIRY --}}
                        <button 
                            type="button" 
                            class="quick-purchase-button" 
                            id="quickPurchaseButton"
                            data-tooltip="Create a new inquiry for a client."
                        >
                            Add Inquiry
                        </button>

                        {{-- ADD DEAL --}} 
                        <button 
                            type="button" 
                            class="primary-button" 
                            id="addDealButton" 
                            data-tooltip="Create a new deal record."
                        > 
                            <svg 
                                viewBox="0 0 24 24" 
                                fill="none" 
                                stroke="currentColor" 
                                stroke-width="2" 
                            > 
                                <path d="M12 5v14"></path> 
                                <path d="M5 12h14"></path> 
                            </svg> 
                            Add Deal 
                        </button> 
                    </div>

                </div> 
 
 
                {{-- ================================================= 
                     FILTERS 
                ================================================== --}} 
 
                <form 
                    method="GET" 
                    action="{{ route('deals.index') }}" 
                    class="filters" 
                    id="dealsFilterForm"
                > 

                    <div class="search-box-wrapper" id="dealSearchWrapper">
                        <div class="search-input-inner">
                            <svg class="search-icon" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                            </svg>
                            <input 
                                type="text" 
                                name="search" 
                                id="dealSearchInput"
                                class="search-box" 
                                value="{{ request('search') }}" 
                                placeholder="Search code, deal, or contact" 
                                autocomplete="off"
                            >
                            <button type="button" class="search-clear-btn" id="searchClearBtn" style="display: {{ request('search') ? 'flex' : 'none' }};" title="Clear search" aria-label="Clear search">&times;</button>
                            <span class="search-spinner" id="searchSpinner" style="display: none;"></span>
                        </div>

                        <!-- Typeahead Dropdown -->
                        <div class="search-typeahead-dropdown" id="searchTypeaheadDropdown" style="display: none;">
                            <div class="typeahead-header">
                                <span>SUGGESTED CLIENTS</span>
                                <span class="typeahead-hint">↑↓ to navigate, Enter to view</span>
                            </div>
                            <div class="typeahead-results" id="typeaheadResultsList">
                                <!-- Dynamic Items rendered via JS -->
                            </div>
                            <div class="typeahead-footer" id="typeaheadFooter">
                                <a href="javascript:void(0)" class="typeahead-footer-action" id="typeaheadSearchAllBtn">
                                    <span>Press <kbd>Enter</kbd> to search all deals for "<strong id="typeaheadQueryLabel"></strong>"</span>
                                </a>
                            </div>
                        </div>
                    </div> 


                    <select 
                        name="deal_filter" 
                        class="filter-select" 
                        onchange="this.form.submit()"
                    > 

                        <option value=""> 
                            All Stage Deals 
                        </option> 

                        <option 
                            value="my_deals" 
                            {{ request('deal_filter') === 'my_deals' ? 'selected' : '' }} 
                        > 
                            My Deals 
                        </option> 

                        @foreach($stages as $stage)
                            <option 
                                value="{{ $stage }}" 
                                {{ request('deal_filter') === $stage ? 'selected' : '' }}
                            >
                                {{ $stage }}
                            </option>
                        @endforeach

                    </select> 


                    <select 
                        name="date_filter" 
                        class="filter-select" 
                        onchange="this.form.submit()"
                    > 

                        <option value="created_date" {{ request('date_filter', 'created_date') === 'created_date' ? 'selected' : '' }}> 
                            Created Date 
                        </option> 

                        <option 
                            value="updated_date" 
                            {{ request('date_filter') === 'updated_date' ? 'selected' : '' }} 
                        > 
                            Last Updated 
                        </option> 

                        <option 
                            value="today" 
                            {{ request('date_filter') === 'today' ? 'selected' : '' }} 
                        > 
                            Today 
                        </option> 

                        <option 
                            value="this_week" 
                            {{ request('date_filter') === 'this_week' ? 'selected' : '' }} 
                        > 
                            This Week 
                        </option> 

                        <option 
                            value="this_month" 
                            {{ request('date_filter') === 'this_month' ? 'selected' : '' }} 
                        > 
                            This Month 
                        </option> 

                        <option 
                            value="this_year" 
                            {{ request('date_filter') === 'this_year' ? 'selected' : '' }} 
                        > 
                            This Year 
                        </option> 

                    </select> 


                    <button 
                        type="submit" 
                        class="filter-button" 
                    > 
                        Search 
                    </button> 


                    <span class="deal-count" id="totalDealsCount"> 

                        {{ $deals->count() }} 

                        {{ $deals->count() === 1 ? 'deal' : 'deals' }} 

                    </span> 

                </form> 
 
 
                {{-- ================================================= 
                     KANBAN 
                ================================================== --}} 
 
                <div class="kanban-scroll">
                 {{-- MOBILE STAGE TABS --}}
                        <div class="mobile-stage-tabs">

                            @foreach($stages as $stage)
                                <button
                                    type="button"
                                    class="mobile-stage-tab"
                                    onclick="showMobileStage(@js($stage), this)"
                                >
                                    {{ $stage }}
                                </button>
                            @endforeach

                        </div>
 
                    <div class="kanban"> 
 
                        @php 
 
                            $stageColors = [ 
 
                                'Inquiry'       => '#2458d7', 
                                'Qualification' => '#5d43d7', 
                                'Consultation'  => '#0d8797', 
                                'Proposal'      => '#d87b10', 
                                'Negotiation'   => '#dd5b21', 
                                'Payment'       => '#c43d71', 
                                'Activation'    => '#23834b', 
                                'Closed Won'    => '#16805b', 
                                'Closed Lost'   => '#64748b', 
 
                            ]; 

                            $stageTooltipsIndex = [
                                'Inquiry'       => "Initial stage where the client's request or opportunity is recorded.",
                                'Qualification' => "Confirm whether the client, need, and opportunity meet the applicable qualification requirements.",
                                'Consultation'  => "Stage for discussing the client's requirements and proposed approach.",
                                'Proposal'      => "Stage where the applicable services and commercial proposal are prepared or reviewed.",
                                'Negotiation'   => "Stage where commercial terms or requested changes are being discussed.",
                                'Payment'       => "Stage for completing the applicable payment requirements.",
                                'Activation'    => "Stage where the approved service moves into activation.",
                                'Closed Won'    => "Deal has been successfully completed and won.",
                                'Closed Lost'   => "Deal has been closed without proceeding.",
                            ];
 
                        @endphp 
 
 
                        @foreach($stages as $stage) 
 
                            @php 
 
                                $stageDeals = 
                                    $deals->filter(function ($deal) use ($stage) { 
                                        $st = $deal->pipeline_stage ?: ($deal->stage ?: '');
                                        if ($st === $stage) return true;
                                        return match($stage) {
                                            'Inquiry' => in_array($st, ['Inquiry', 'Inquiry / Consultation', 'Intake']),
                                            'Proposal' => in_array($st, ['Proposal', 'Solutioning / Proposal']),
                                            'Negotiation' => in_array($st, ['Negotiation', 'Negotiation / Finalizing']),
                                            'Activation' => in_array($st, ['Activation', 'Activated']),
                                            'Closed Won' => in_array($st, ['Closed Won', 'approved']),
                                            'Closed Lost' => in_array($st, ['Closed Lost', 'rejected']),
                                            default => $st === $stage,
                                        };
                                    }); 
 
 
                                $stageTotal = 
                                    $stageDeals->sum(function ($deal) { 
 
                                        return (float) ( 
 
                                            $deal->total_estimated_engagement_value 
                                            ?? $deal->amount 
                                            ?? 0 
 
                                        ); 
 
                                    }); 
 
                            @endphp 
 
 
                            <div class="kanban-column" data-stage="{{ $stage }}"> 
 
 
                                {{-- STAGE HEADER --}} 
 
                                <div 
                                    class="column-header" 
                                    data-tooltip="{{ $stageTooltipsIndex[$stage] ?? '' }}"
                                    style=" 
                                        border-top-color: 
                                        {{ $stageColors[$stage] ?? '#2458d7' }}; 
                                    " 
                                > 
 
                                    <div class="column-title-row"> 
 
                                        <div class="column-title"> 
                                            {{ $stage }} 
                                        </div> 
 
                                        <div class="stage-header-actions"> 
 
                                            <input 
                                                type="checkbox" 
                                                class="stage-checkbox" 
                                                onclick="event.stopPropagation();" 
                                                aria-label="Select stage" 
                                            > 
 
                                            <div class="stage-menu-wrap"> 
 
                                                <button 
                                                    type="button" 
                                                    class="dots-button stage-dots" 
                                                    aria-label="Stage actions" 
                                                > 
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"> 
                                                        <circle cx="12" cy="5" r="2.2"></circle> 
                                                        <circle cx="12" cy="12" r="2.2"></circle> 
                                                        <circle cx="12" cy="19" r="2.2"></circle> 
                                                    </svg> 
                                                </button> 
 
 
                                                <div class="stage-menu"> 
 
                                                    <button 
                                                        type="button" 
                                                        onclick="closeAllMenus()" 
                                                    > 
                                                        New Stage 
                                                    </button> 
 
                                                    <button 
                                                        type="button" 
                                                        onclick="moveStageLeft(this); closeAllMenus()" 
                                                    > 
                                                        Move Left 
                                                    </button> 
 
                                                    <button 
                                                        type="button" 
                                                        onclick="moveStageRight(this); closeAllMenus()" 
                                                    > 
                                                        Move Right 
                                                    </button> 
 
                                                    <button 
                                                        type="button" 
                                                        onclick="renameStage(this); closeAllMenus()" 
                                                    > 
                                                        Rename Stage 
                                                    </button> 
 
                                                    <div class="stage-menu-section-label"> 
                                                        CHANGE STAGE COLOR 
                                                    </div> 
 
                                                    <div class="stage-color-palette"> 
                                                        <button type="button" class="color-dot" style="background: #2563eb;" onclick="setStageColor(this, '#2563eb')" title="Blue" aria-label="Blue"></button> 
                                                        <button type="button" class="color-dot" style="background: #5d43d7;" onclick="setStageColor(this, '#5d43d7')" title="Indigo" aria-label="Indigo"></button> 
                                                        <button type="button" class="color-dot" style="background: #0d8797;" onclick="setStageColor(this, '#0d8797')" title="Cyan" aria-label="Cyan"></button> 
                                                        <button type="button" class="color-dot" style="background: #d87b10;" onclick="setStageColor(this, '#d87b10')" title="Amber" aria-label="Amber"></button> 
                                                        <button type="button" class="color-dot" style="background: #dd5b21;" onclick="setStageColor(this, '#dd5b21')" title="Orange" aria-label="Orange"></button> 
                                                        <button type="button" class="color-dot" style="background: #23834b;" onclick="setStageColor(this, '#23834b')" title="Green" aria-label="Green"></button> 
                                                        <button type="button" class="color-dot" style="background: #8b5cf6;" onclick="setStageColor(this, '#8b5cf6')" title="Purple" aria-label="Purple"></button> 
                                                        <button type="button" class="color-dot" style="background: #dc2626;" onclick="setStageColor(this, '#dc2626')" title="Red" aria-label="Red"></button> 
                                                    </div> 
 
                                                    <div class="stage-menu-divider"></div> 
 
                                                    <button 
                                                        type="button" 
                                                        class="delete-action" 
                                                        onclick="closeAllMenus()" 
                                                    > 
                                                        Delete Stage 
                                                    </button> 
 
                                                </div> 
 
                                            </div> 
 
                                        </div> 
 
                                    </div> 
 
 
                                    <div class="column-meta" data-stage-meta="{{ $stage }}"> 
                                        P{{ number_format($stageTotal, 0) }} • {{ $stageDeals->count() }} {{ $stageDeals->count() === 1 ? 'Deal' : 'Deals' }} 
                                    </div> 
 
                                </div> 
 
 
                                {{-- STAGE CONTENT --}} 
 
                                <div class="column-content" data-stage="{{ $stage }}"> 
 
                                    @forelse($stageDeals as $deal) 
                                        @php
                                            $cardCompany = trim((string)($deal->company_name ?: $deal->company));
                                            $cardContact = trim(($deal->first_name ?? '') . ' ' . ($deal->last_name ?? ''));
                                            if (!$cardContact) {
                                                $cardContact = trim((string)($deal->primary_contact_name ?: ($deal->client_search ?: '')));
                                            }
                                            $cardMainClient = $cardCompany ?: ($cardContact ?: ($deal->account?->account_name ?: ''));
                                            $cardDealCode = $deal->deal_code ?: ('CONDEAL-' . date('Y') . '-' . str_pad($deal->id, 3, '0', STR_PAD_LEFT));
                                            $rawTerms = [
                                                $cardDealCode,
                                                $deal->deal_code,
                                                (string)$deal->id,
                                                str_pad((string)$deal->id, 3, '0', STR_PAD_LEFT),
                                                $deal->deal_title,
                                                $deal->deal_name,
                                                $cardMainClient,
                                                $cardCompany,
                                                $cardContact,
                                                $deal->first_name,
                                                $deal->last_name,
                                                $deal->account?->account_name,
                                                $deal->services_products,
                                                $deal->services,
                                                $deal->products,
                                                $deal->service_area,
                                                $deal->service_areas,
                                                $deal->scope_of_work,
                                                $deal->email,
                                                $deal->mobile,
                                                $deal->mobile_number,
                                                $deal->owner_name,
                                                $stage,
                                                $deal->stage,
                                                $deal->pipeline_stage,
                                            ];
                                            $flattenedTerms = [];
                                            array_walk_recursive($rawTerms, function($item) use (&$flattenedTerms) {
                                                if (is_scalar($item) && $item !== '' && $item !== null) {
                                                    $flattenedTerms[] = (string)$item;
                                                }
                                            });
                                            $cardSearchTerms = array_unique($flattenedTerms);
                                        @endphp

                                        <div 
                                            class="deal-card" 
                                            draggable="true" 
                                            data-deal-card 
                                            data-deal-id="{{ $deal->id }}" 
                                            data-stage="{{ $stage }}" 
                                            data-deal-amount="{{ (float)($deal->total_estimated_engagement_value ?? $deal->amount ?? 0) }}" 
                                            data-customer-name="{{ strtolower($cardMainClient) }}"
                                            data-customer-search="{{ strtolower(implode(' | ', $cardSearchTerms)) }}"
                                            data-search="{{ strtolower(implode(' ', $cardSearchTerms)) }}" 
                                            onclick="openDeal('{{ route('deals.show', $deal->id) }}', event)" 
                                        > 
 
 
                                            {{-- TOP ROW: CODE + RIGHT ACTIONS --}} 
                                            <div class="deal-card-top-row"> 
                                                <div class="deal-code"> 
                                                    {{ $deal->deal_code ?: 'CONDEAL-' . date('Y') . '-' . str_pad($deal->id, 3, '0', STR_PAD_LEFT) }} 
                                                </div> 
 
                                                <div class="deal-card-actions"> 
                                                    <input 
                                                        type="checkbox" 
                                                        class="deal-card-checkbox" 
                                                        onclick="event.stopPropagation();" 
                                                        aria-label="Select deal" 
                                                    > 
 
                                                    <div class="deal-menu-wrap"> 
                                                        <button 
                                                            type="button" 
                                                            class="deal-dots" 
                                                            onclick="toggleDealMenu(event, this)" 
                                                            aria-label="Deal actions" 
                                                        > 
                                                            ... 
                                                        </button> 
 
                                                        <div class="deal-menu"> 
                                                            <a 
                                                                href="{{ route('deals.show', $deal->id) }}" 
                                                                data-tooltip="Open the complete deal record and its current status."
                                                                onclick="event.stopPropagation();" 
                                                            > 
                                                                View Deal 
                                                            </a> 

                                                            <button 
                                                                type="button" 
                                                                data-tooltip="Start a new inquiry for an existing client using verified client data."
                                                                onclick="openQuickPurchaseDrawer({{ $deal->account_id ?? 'null' }}); event.stopPropagation();"
                                                                style="color: #2563eb; font-weight: 600;"
                                                            > 
                                                                Add Inquiry 
                                                            </button> 

                                                            <button 
                                                                type="button" 
                                                                data-tooltip="Update the deal information that is still editable."
                                                                onclick="openEditDealDrawer({{ $deal->id }}, event);" 
                                                            > 
                                                                Edit Deal 
                                                            </button> 

                                                            <button 
                                                                type="button" 
                                                                class="delete-action" 
                                                                onclick=" 
                                                                    event.stopPropagation(); 
                                                                    openDeleteModal( 
                                                                        @js($deal->id), 
                                                                        @js($deal->deal_title ?: $deal->deal_code ?: 'this deal') 
                                                                    ); 
                                                                " 
                                                            > 
                                                                Delete Deal 
                                                            </button> 
                                                        </div> 
                                                    </div> 
                                                </div> 
                                            </div> 
 
 
                                            {{-- CONTACT NAME --}} 
                                            <div class="deal-contact-name"> 
                                                @if($deal->primary_contact_name) 
                                                    {{ $deal->primary_contact_name }} 
                                                @elseif($deal->first_name || $deal->last_name) 
                                                    {{ trim(($deal->first_name ?? '') . ' ' . ($deal->last_name ?? '')) }} 
                                                @elseif($deal->deal_title) 
                                                    {{ $deal->deal_title }} 
                                                @else 
                                                    Untitled Deal 
                                                @endif 
                                            </div> 
 
 
                                            {{-- COMPANY --}} 
                                            <div class="deal-company"> 
                                                @if($deal->company_name) 
                                                    {{ $deal->company_name }} 
                                                @elseif($deal->company) 
                                                    {{ $deal->company }} 
                                                @endif 
                                            </div> 
 
 
                                            {{-- PRICE --}} 
                                            <div class="deal-price-section"> 
                                                <div class="deal-price-label"> 
                                                    <span> 
                                                        PRICE 
                                                    </span> 
 
                                                    <button 
                                                        type="button" 
                                                        class="price-eye-btn" 
                                                        onclick="togglePriceMask(event, this)" 
                                                        title="Show/Hide Price" 
                                                        aria-label="Toggle price visibility" 
                                                    > 
                                                        <svg 
                                                            viewBox="0 0 24 24" 
                                                            fill="none" 
                                                            stroke="currentColor" 
                                                            stroke-width="1.8" 
                                                        > 
                                                            <path 
                                                                d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z" 
                                                            ></path> 
                                                            <circle 
                                                                cx="12" 
                                                                cy="12" 
                                                                r="2" 
                                                            ></circle> 
                                                        </svg> 
                                                    </button> 
                                                </div> 
 
                                                <div class="deal-price-value"> 
                                                    <div class="price-masked">••••••</div> 
                                                    <div class="price-numeric" style="display: none;"> 
                                                        P{{ number_format( 
                                                            (float) ( 
                                                                $deal->total_estimated_engagement_value 
                                                                ?? $deal->amount 
                                                                ?? 0 
                                                            ), 
                                                            0 
                                                        ) }} 
                                                    </div> 
                                                </div> 
                                            </div> 
 
 
                                            {{-- EXPECTED CLOSE --}} 
                                            <div class="deal-expected"> 
                                                <div class="deal-expected-label"> 
                                                    Expected Close 
                                                </div> 
 
                                                <div class="deal-expected-value"> 
                                                    @if($deal->expected_close) 
                                                        {{ \Carbon\Carbon::parse($deal->expected_close)->format('M d, Y') }} 
                                                    @else 
                                                        TBD 
                                                    @endif 
                                                </div> 
                                            </div> 
 
 
                                            {{-- OWNER --}} 
                                            <div class="deal-owner"> 
                                                <span class="owner-avatar"> 
                                                    {{ strtoupper(substr($deal->owner_name ?: ($deal->user?->name ?: 'UN'), 0, 2)) }} 
                                                </span> 
 
                                                <span class="owner-name"> 
                                                    {{ $deal->owner_name ?: ($deal->user?->name ?: 'Unassigned') }} 
                                                </span> 
                                            </div> 
 
                                        </div> 
 
                                    @empty 
 
                                        <div class="empty-stage"> 
                                            No deals in this stage. 
                                        </div> 
 
                                    @endforelse 
 
                                </div> 
 
 
                                 
 
                            </div> 
 
                        @endforeach 
 
                    </div> 
 
                </div> 
 
            </section> 
 
        </main> 
 
    </div> 
 
</div> 
 
 
 
{{-- ============================================================= 
     ADD DEAL DRAWER OVERLAY 
============================================================= --}} 
 
<div 
    class="drawer-overlay" 
    id="drawerOverlay" 
></div> 
 
 
 
{{-- ============================================================= 
     ADD DEAL DRAWER 
============================================================= --}} 
 
<aside 
    class="add-deal-drawer" 
    id="addDealDrawer" 
> 
 
    <div class="drawer-header"> 
        <div class="drawer-header-left"> 
            <div> 
                <h2 class="drawer-title" id="drawerTitle">Create Deal</h2> 
                <div class="drawer-subtitle" id="drawerSubtitle">Select an existing client, then complete the consulting and deal form.</div> 
            </div> 
        </div> 
 
        <button 
                type="button" 
                class="drawer-close" 
                id="closeDrawerButton" 
                aria-label="Close drawer" 
            > 
 
                <svg 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="1.8" 
                > 
 
                <path d="M6 6l12 12"></path> 
 
                <path d="M18 6L6 18"></path> 
 
            </svg> 
 
        </button> 
 
    </div> 
 
 
    {{-- ========================================================= 
         DRAWER BODY 
    ========================================================== --}} 
 
    <div class="drawer-body"> 
 
        <iframe 
            id="addDealFrame" 
            class="drawer-frame" 
            src="about:blank" 
            title="Create Deal Form" 
        ></iframe> 
 
    </div> 
 
</aside> 
 
 
 
 



{{-- ============================================================= 
     DELETE MODAL 
============================================================= --}} 

<div 
    class="modal-backdrop" 
    id="deleteModal" 
> 
 
    <div class="confirm-modal"> 
 
        <div class="confirm-title"> 
            Delete Deal 
        </div> 
 
 
        <div class="confirm-text"> 
 
            Are you sure you want to delete 
 
            <strong id="deleteDealName"></strong>? 
 
            This action cannot be undone. 
 
        </div> 
 
 
        <div class="confirm-actions"> 
 
            <button 
                type="button" 
                class="confirm-cancel" 
                onclick="closeDeleteModal()" 
            > 
                Cancel 
            </button> 
 
 
            <form 
                method="POST" 
                id="deleteDealForm" 
            > 
 
                @csrf 
 
                @method('DELETE') 
 
                <button 
                    type="submit" 
                    class="confirm-delete" 
                > 
                    Delete Deal 
                </button> 
 
            </form> 
 
        </div> 
 
    </div> 
 
</div> 
 
 
 
{{-- ============================================================= 
     JAVASCRIPT 
============================================================= --}} 
 
<script> 
 
 
    /* ========================================================= 
       ELEMENTS 
    ========================================================= */ 
 
    const addDealButton = 
        document.getElementById('addDealButton'); 
 
    const addDealDrawer = 
        document.getElementById('addDealDrawer'); 
 
    const drawerOverlay = 
        document.getElementById('drawerOverlay'); 
 
    const closeDrawerButton = 
        document.getElementById('closeDrawerButton'); 
 
    const addDealFrame = 
        document.getElementById('addDealFrame'); 
 
    const notificationButton = 
        document.getElementById('notificationButton'); 
 
    const notificationPanel = 
        document.getElementById('notificationPanel'); 
 
 
    /* ========================================================= 
       QUICK PURCHASE DRAWER (EXISTING CLIENTS)
    ========================================================= */ 
    const quickPurchaseButton = document.getElementById('quickPurchaseButton'); 


    function openQuickPurchaseDrawer(accountId, contactId) { 
        closeAllMenus(); 
        let url = "{{ route('deals.quick-purchase') }}?drawer=1"; 
        if (accountId) { 
            url += '&account_id=' + encodeURIComponent(accountId); 
        } 
        if (contactId) { 
            url += '&contact_id=' + encodeURIComponent(contactId); 
        } 
        addDealFrame.src = url; 
        document.getElementById('drawerTitle').textContent = 'Add Inquiry'; 
        const drawerSub = document.getElementById('drawerSubtitle');
        if (drawerSub) drawerSub.textContent = 'Create a new inquiry for a client.';
        addDealDrawer.classList.add('show'); 
        drawerOverlay.classList.add('show'); 
        document.body.style.overflow = 'hidden'; 
    } 

    if (quickPurchaseButton) { 
        quickPurchaseButton.addEventListener('click', function(e) { 
            e.preventDefault(); 
            openQuickPurchaseDrawer(); 
        }); 
    } 

    @if(request()->boolean('open_inquiry') || request()->has('open_inquiry'))
        document.addEventListener('DOMContentLoaded', function() {
            openQuickPurchaseDrawer(@json(request('account_id')), @json(request('contact_id')));
        });
    @endif 
 


    /* ========================================================= 
       OPEN ADD DEAL DRAWER 
    ========================================================= */ 

    function openAddDealDrawer() { 

        addDealFrame.src = 
            "{{ route('deals.create') }}?drawer=1"; 

        document.getElementById('drawerTitle').textContent = 'Create Deal'; 
        const drawerSub = document.getElementById('drawerSubtitle');
        if (drawerSub) drawerSub.textContent = 'Select an existing client, then complete the consulting and deal form.'; 

        addDealDrawer.classList.add('show'); 

        drawerOverlay.classList.add('show'); 

        document.body.style.overflow = 'hidden'; 

        closeAllMenus(); 

    } 
 
 
    function openEditDealDrawer(dealId, event) { 
 
        event.preventDefault(); 
        event.stopPropagation(); 
 
        addDealFrame.src = 
            "{{ route('deals.create') }}?deal=" + encodeURIComponent(dealId) + '&drawer=1'; 
 
        document.getElementById('drawerTitle').textContent = 'Edit Deal'; 
 
        addDealDrawer.classList.add('show'); 
        drawerOverlay.classList.add('show'); 
        document.body.style.overflow = 'hidden'; 
 
        closeAllMenus(); 
 
    } 
 
 
    const editHash = 
        window.location.hash.match(/^#edit-(\d+)$/); 
 
    if (editHash) { 
 
        window.history.replaceState( 
            null, 
            '', 
            window.location.pathname + window.location.search 
        ); 
 
        openEditDealDrawer(editHash[1], { 
            preventDefault: function() {}, 
            stopPropagation: function() {} 
        }); 
 
    } 
 
 
    /* ========================================================= 
       CLOSE ADD DEAL DRAWER 
    ========================================================= */ 
 
    function closeAddDealDrawer() { 
 
        addDealDrawer.classList.remove('show'); 
 
        drawerOverlay.classList.remove('show'); 
 
        document.body.style.overflow = ''; 
 
        setTimeout(function () { 
 
            if (!addDealDrawer.classList.contains('show')) { 
 
                addDealFrame.src = 'about:blank'; 
 
            } 
 
        }, 250); 
 
    } 
 
 
    /* ========================================================= 
       ADD DEAL BUTTON 
    ========================================================= */ 
 
    if (addDealButton) { 
 
        addDealButton.addEventListener( 
            'click', 
            openAddDealDrawer 
        ); 
 
    } 
 
 
    /* ========================================================= 
       CLOSE X BUTTON 
    ========================================================= */ 
 
    if (closeDrawerButton) { 
 
        closeDrawerButton.addEventListener( 
            'click', 
            function(event) { 
 
                event.preventDefault(); 
 
                event.stopPropagation(); 
 
                closeAddDealDrawer(); 
 
            } 
        ); 
 
    } 
 
 
    /* ========================================================= 
       CLOSE WHEN CLICKING OVERLAY 
    ========================================================= */ 
 
    if (drawerOverlay) { 
 
        drawerOverlay.addEventListener( 
            'click', 
            function() { 
 
                closeAddDealDrawer(); 
 
            } 
        ); 
 
    } 
 
 
    /* ========================================================= 
       ESC CLOSES DRAWER 
    ========================================================= */ 
 
    document.addEventListener( 
        'keydown', 
        function(event) { 
 
            if (event.key === 'Escape') { 
 
                if ( 
                    addDealDrawer && 
                    addDealDrawer.classList.contains('show') 
                ) { 
 
                    closeAddDealDrawer(); 
 
                } 
 
                closeAllMenus(); 
 
            } 
 
        } 
    ); 
 
 
    /* ========================================================= 
       CREATE FORM INSIDE DRAWER 
    ========================================================= */ 
 
    if (addDealFrame) { 
 
        addDealFrame.addEventListener( 
            'load', 
            function() { 
 
                if ( 
                    !addDealDrawer.classList.contains('show') 
                ) { 
 
                    return; 
 
                } 
 
 
                try { 
 
                    const currentUrl = 
                        addDealFrame.contentWindow.location.href; 
 
 
                    if ( 
                        currentUrl !== 'about:blank' && 
                        !currentUrl.includes('/deals/create') &&
                        !currentUrl.includes('/deals/quick-purchase')
                    ) { 
 
                        closeAddDealDrawer(); 
 
                        setTimeout(function() { 
 
                            window.location.reload(); 
 
                        }, 200); 
 
                    } 
 
                } catch (error) { 
 
                    /* 
                     * Ignore cross-frame access errors. 
                     */ 
 
                } 
 
            } 
        ); 
 
    } 
 
 
    /* ========================================================= 
       NOTIFICATIONS 
    ========================================================= */ 
 
    if (notificationButton) { 
 
        notificationButton.addEventListener( 
            'click', 
            function(event) { 
 
                event.stopPropagation(); 
 
                closeAllMenus(); 
 
                notificationPanel.classList.toggle('show'); 
 
            } 
        ); 
 
    } 
 
 
    if (notificationPanel) { 
 
        notificationPanel.addEventListener( 
            'click', 
            function(event) { 
 
                event.stopPropagation(); 
 
            } 
        ); 
 
    } 
 
 
    /* ========================================================= 
       DEAL CARD 
    ========================================================= */ 
 
    function openDeal(url, event) { 
        if (typeof isDraggingDeal !== 'undefined' && isDraggingDeal) { 
            return; 
        } 

        if ( 
            event.target.closest('.deal-menu-wrap') || 
            event.target.closest('.deal-card-checkbox') || 
            event.target.closest('.price-eye-btn') || 
            event.target.closest('.deal-dots') || 
            event.target.closest('.deal-menu') 
        ) { 
            return; 
        } 

        window.location.href = url; 
    } 
 
 
    /* ========================================================= 
       DEAL MENU 
    ========================================================= */ 
 
    function toggleDealMenu(event, button) { 
 
        event.preventDefault(); 
 
        event.stopPropagation(); 
 
 
        const menu = 
            button.parentElement.querySelector('.deal-menu'); 
 
 
        closeAllMenus(); 
 
 
        menu.classList.toggle('show'); 
 
    } 
 
 
    /* ========================================================= 
       STAGE MENUS 
    ========================================================= */ 
    document 
        .querySelectorAll('.stage-dots') 
        .forEach(function(button) { 
            button.addEventListener( 
                'click', 
                function(event) { 
                    event.preventDefault(); 
                    event.stopPropagation(); 
 
                    const menu = button.parentElement.querySelector('.stage-menu'); 
                    if (!menu) return; 
 
                    const isShown = menu.classList.contains('show'); 
                    closeAllMenus(); 
 
                    if (!isShown) { 
                        menu.classList.add('show'); 
                        menu.classList.remove('position-above'); 
 
                        const rect = menu.getBoundingClientRect(); 
                        if (rect.bottom > window.innerHeight - 12) { 
                            menu.classList.add('position-above'); 
                        } 
 
                        const col = button.closest('.kanban-column'); 
                        if (col) { 
                            col.style.zIndex = '50'; 
                        } 
                    } 
                } 
            ); 
        }); 
 
 
    /* ========================================================= 
       CLOSE MENUS 
    ========================================================= */ 
    function closeAllMenus() { 
        document 
            .querySelectorAll('.deal-menu.show, .stage-menu.show') 
            .forEach(function(menu) { 
                menu.classList.remove('show'); 
                menu.classList.remove('position-above'); 
            }); 
 
        document 
            .querySelectorAll('.kanban-column') 
            .forEach(function(col) { 
                col.style.zIndex = ''; 
            }); 
 
        if (notificationPanel) { 
            notificationPanel.classList.remove('show'); 
        } 
    } 
 
 
    document.addEventListener( 
        'click', 
        function() { 
            closeAllMenus(); 
        } 
    ); 
 
    document.addEventListener( 
        'keydown', 
        function(event) { 
            if (event.key === 'Escape') { 
                closeAllMenus(); 
            } 
        } 
    ); 
 
 
    /* ========================================================= 
       DELETE MODAL 
    ========================================================= */ 
 
    function openDeleteModal(id, name) { 
 
        const modal = 
            document.getElementById('deleteModal'); 
 
        const form = 
            document.getElementById('deleteDealForm'); 
 
        const dealName = 
            document.getElementById('deleteDealName'); 
 
 
        form.action = 
            "{{ url('/deals') }}/" + id; 
 
 
        dealName.textContent = 
            name; 
 
 
        modal.classList.add('show'); 
 
 
        closeAllMenus(); 
 
    } 
 
 
    function closeDeleteModal() { 
 
        document 
            .getElementById('deleteModal') 
            .classList.remove('show'); 
 
    } 
 
 
    document 
        .getElementById('deleteModal') 
        .addEventListener( 
            'click', 
            function(event) { 
 
                if (event.target === this) { 
 
                    closeDeleteModal(); 
 
                } 
 
            } 
        ); 
    /* =========================================================
       STAGE FILTER
    ========================================================= */

    function filterStage(stage) {

        const cards =
            document.querySelectorAll('[data-deal-card]');

        cards.forEach(function(card) {

            if (card.dataset.stage === stage) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }

        });

        closeAllMenus();

    }



    /* =========================================================
       MOBILE STAGE TABS
    ========================================================= */

    function showMobileStage(stage, button) {

        const columns =
            document.querySelectorAll('.kanban-column');

        columns.forEach(function(column) {

            const title =
                column.querySelector('.column-title');

            if (title && title.textContent.trim() === stage) {
                column.classList.add('mobile-active');
            } else {
                column.classList.remove('mobile-active');
            }

        });

        document.querySelectorAll('.mobile-stage-tab').forEach(function(tab) {
            tab.classList.remove('active');
        });

        if (button) {
            button.classList.add('active');
        }

        closeAllMenus();

    }



    /* =========================================================
       DEFAULT MOBILE STAGE
    ========================================================= */

    document.addEventListener('DOMContentLoaded', function() {
        const selectedStage = @js(request('deal_filter'));
        let targetTab = null;
        if (selectedStage) {
            document.querySelectorAll('.mobile-stage-tab').forEach(function(tab) {
                if (tab.textContent.trim().toLowerCase() === selectedStage.toLowerCase()) {
                    targetTab = tab;
                }
            });
        }
        if (!targetTab) {
            targetTab = document.querySelector('.mobile-stage-tab');
        }
        if (targetTab) {
            showMobileStage(targetTab.textContent.trim(), targetTab);
        }

        // On desktop, scroll to the column if a stage filter was applied
        if (selectedStage && selectedStage !== 'my_deals') {
            const targetCol = document.querySelector(`.kanban-column[data-stage="${selectedStage}"]`);
            if (targetCol) {
                setTimeout(() => {
                    targetCol.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                }, 150);
            }
        }
    });


         /* =========================================================
                  LIVE SEARCH
        ========================================================= */
 
    function updateKanbanLiveCounts() {
        let totalVisible = 0;
        document.querySelectorAll('.column-content').forEach(function(col) {
            const stage = col.dataset.stage;
            const meta = document.querySelector(`[data-stage-meta="${stage}"]`);
            const cards = col.querySelectorAll('[data-deal-card]');
            let visibleCount = 0;
            let visibleTotal = 0;

            cards.forEach(function(card) {
                if (card.style.display !== 'none') {
                    visibleCount++;
                    totalVisible++;
                    const amt = parseFloat(card.dataset.dealAmount) || 0;
                    visibleTotal += amt;
                }
            });

            if (meta) {
                meta.textContent = `₱${visibleTotal.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })} • ${visibleCount} ${visibleCount === 1 ? 'Deal' : 'Deals'}`;
            }
        });

        const totalBadge = document.getElementById('totalDealsCount') || document.querySelector('.deal-count');
        if (totalBadge) {
            totalBadge.textContent = `${totalVisible} ${totalVisible === 1 ? 'deal' : 'deals'}`;
        }
    }

    /* ========================================================= 
       SEARCH AUTOCOMPLETE & TYPEAHEAD
    ========================================================= */ 
    const dealSearchWrapper = document.getElementById('dealSearchWrapper');
    const searchInput = document.getElementById('dealSearchInput') || document.querySelector('.search-box');
    const searchClearBtn = document.getElementById('searchClearBtn');
    const searchSpinner = document.getElementById('searchSpinner');
    const typeaheadDropdown = document.getElementById('searchTypeaheadDropdown');
    const typeaheadResultsList = document.getElementById('typeaheadResultsList');
    const typeaheadFooter = document.getElementById('typeaheadFooter');
    const typeaheadQueryLabel = document.getElementById('typeaheadQueryLabel');
    const typeaheadSearchAllBtn = document.getElementById('typeaheadSearchAllBtn');
    const dealsFilterForm = document.getElementById('dealsFilterForm');

    let typeaheadDebounceTimer = null;
    let typeaheadAbortController = null;
    let currentTypeaheadResults = [];
    let activeResultIndex = -1;

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function highlightMatch(text, query) {
        if (!text || !query) return escapeHtml(text || '');
        const qTrim = query.trim();
        if (!qTrim) return escapeHtml(text);

        const tokens = qTrim.split(/\s+/).filter(t => t.length > 0);
        if (tokens.length === 0) return escapeHtml(text);

        const safeText = String(text);
        // Sort tokens longest first
        tokens.sort((a, b) => b.length - a.length);

        const escapedTokens = tokens.map(t => t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
        // Match word-start boundary (start of string or preceded by non-alphanumeric character)
        const pattern = new RegExp('(^|[^a-zA-Z0-9])(' + escapedTokens.join('|') + ')', 'gi');

        let lastIndex = 0;
        let result = '';
        let match;

        while ((match = pattern.exec(safeText)) !== null) {
            const prefixBoundary = match[1];
            const matchedText = match[2];
            const matchStart = match.index + prefixBoundary.length;
            const matchEnd = matchStart + matchedText.length;

            result += escapeHtml(safeText.substring(lastIndex, matchStart));
            result += '<span class="typeahead-highlight">' + escapeHtml(matchedText) + '</span>';
            lastIndex = matchEnd;
            pattern.lastIndex = matchEnd;
        }

        result += escapeHtml(safeText.substring(lastIndex));
        return result || escapeHtml(safeText);
    }

    function hideTypeahead() {
        if (typeaheadDropdown) {
            typeaheadDropdown.style.display = 'none';
        }
        activeResultIndex = -1;
    }

    function showTypeahead() {
        if (typeaheadDropdown && searchInput && searchInput.value.trim().length >= 1) {
            typeaheadDropdown.style.display = 'flex';
        }
    }

    function renderTypeaheadResults(results, query) {
        if (!typeaheadResultsList) return;
        currentTypeaheadResults = results || [];
        activeResultIndex = -1;

        if (typeaheadQueryLabel) {
            typeaheadQueryLabel.textContent = query;
        }

        if (!results || results.length === 0) {
            typeaheadResultsList.innerHTML = `
                <div class="typeahead-empty">
                    <div>No deals found for "<strong>${escapeHtml(query)}</strong>"</div>
                </div>
            `;
            if (typeaheadFooter) typeaheadFooter.style.display = 'block';
            showTypeahead();
            return;
        }

        let html = '';
        results.forEach((deal, idx) => {
            const stageColor = deal.stage_color || '#2458d7';
            const highlightedClient = highlightMatch(deal.client_name, query);
            const highlightedCode = highlightMatch(deal.deal_code || '', query);
            const highlightedTitle = highlightMatch(deal.deal_title || '', query);

            html += `
                <div 
                    class="typeahead-item" 
                    data-index="${idx}" 
                    data-url="${deal.url}"
                    onclick="selectTypeaheadItem('${deal.url}')"
                >
                    <div class="typeahead-client-primary" title="${escapeHtml(deal.client_name)}">${highlightedClient}</div>
                    <div class="typeahead-meta">
                        <span class="typeahead-secondary-info">${highlightedCode}${deal.deal_title ? ' · ' + highlightedTitle : ''}</span>
                        <span class="typeahead-stage-badge" style="background-color: ${stageColor};">
                            ${escapeHtml(deal.stage)}
                        </span>
                    </div>
                </div>
            `;
        });

        typeaheadResultsList.innerHTML = html;
        if (typeaheadFooter) typeaheadFooter.style.display = 'block';
        showTypeahead();
    }

    function selectTypeaheadItem(url) {
        if (url) {
            window.location.href = url;
        }
    }

    function updateActiveTypeaheadItem() {
        if (!typeaheadResultsList) return;
        const items = typeaheadResultsList.querySelectorAll('.typeahead-item');
        items.forEach((item, idx) => {
            if (idx === activeResultIndex) {
                item.classList.add('active');
                item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else {
                item.classList.remove('active');
            }
        });
    }

    async function fetchTypeahead(query) {
        if (typeaheadAbortController) {
            typeaheadAbortController.abort();
        }
        typeaheadAbortController = new AbortController();

        if (searchSpinner) searchSpinner.style.display = 'block';
        if (searchClearBtn) searchClearBtn.style.display = 'none';

        try {
            const url = "{{ route('deals.autocomplete') }}?q=" + encodeURIComponent(query);
            const response = await fetch(url, {
                signal: typeaheadAbortController.signal,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Search failed');
            const data = await response.json();
            renderTypeaheadResults(data, query);
        } catch (err) {
            if (err.name !== 'AbortError') {
                console.error('Autocomplete error:', err);
            }
        } finally {
            if (searchSpinner) searchSpinner.style.display = 'none';
            if (searchClearBtn && searchInput.value.length > 0) searchClearBtn.style.display = 'flex';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const rawSearch = this.value.trim().toLowerCase();
            const searchTokens = rawSearch.split(/\s+/).filter(t => t.length > 0);

            // 1. Live filter the Kanban cards instantly across all search fields
            document.querySelectorAll('[data-deal-card]').forEach(function(card) {
                if (searchTokens.length === 0) {
                    card.style.display = '';
                    return;
                }

                const searchableText = (
                    (card.dataset.search || '') + ' ' + 
                    (card.dataset.customerSearch || '') + ' ' + 
                    (card.dataset.customerName || '')
                ).toLowerCase();

                // Every token typed should be present in the card's text
                const matches = searchTokens.every(function(token) {
                    return searchableText.includes(token);
                });

                card.style.display = matches ? '' : 'none';
            });
            updateKanbanLiveCounts();

            // 2. Toggle clear button
            if (searchClearBtn) {
                searchClearBtn.style.display = rawSearch.length > 0 ? 'flex' : 'none';
            }

            // 3. Typeahead query
            clearTimeout(typeaheadDebounceTimer);
            if (rawSearch.length >= 1) {
                typeaheadDebounceTimer = setTimeout(() => {
                    fetchTypeahead(rawSearch);
                }, 180);
            } else {
                hideTypeahead();
            }
        });

        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 1 && currentTypeaheadResults.length > 0) {
                showTypeahead();
            }
        });

        searchInput.addEventListener('keydown', function(e) {
            const items = typeaheadResultsList ? typeaheadResultsList.querySelectorAll('.typeahead-item') : [];

            if (e.key === 'ArrowDown') {
                if (typeaheadDropdown.style.display !== 'none' && items.length > 0) {
                    e.preventDefault();
                    activeResultIndex = (activeResultIndex + 1) % items.length;
                    updateActiveTypeaheadItem();
                }
            } else if (e.key === 'ArrowUp') {
                if (typeaheadDropdown.style.display !== 'none' && items.length > 0) {
                    e.preventDefault();
                    activeResultIndex = (activeResultIndex - 1 + items.length) % items.length;
                    updateActiveTypeaheadItem();
                }
            } else if (e.key === 'Enter') {
                if (typeaheadDropdown.style.display !== 'none' && activeResultIndex >= 0 && items[activeResultIndex]) {
                    e.preventDefault();
                    const url = items[activeResultIndex].dataset.url;
                    selectTypeaheadItem(url);
                } else {
                    hideTypeahead();
                }
            } else if (e.key === 'Escape') {
                hideTypeahead();
            }
        });
    }

    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (searchInput) {
                const hadServerSearch = new URLSearchParams(window.location.search).has('search');
                searchInput.value = '';
                // Reset live filter
                document.querySelectorAll('[data-deal-card]').forEach(function(card) {
                    card.style.display = '';
                });
                updateKanbanLiveCounts();
                searchClearBtn.style.display = 'none';
                hideTypeahead();
                if (hadServerSearch && dealsFilterForm) {
                    dealsFilterForm.submit();
                } else {
                    searchInput.focus();
                }
            }
        });
    }

    if (typeaheadSearchAllBtn) {
        typeaheadSearchAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            hideTypeahead();
            if (dealsFilterForm) {
                dealsFilterForm.submit();
            }
        });
    }

    // Dismiss dropdown on outside click
    document.addEventListener('click', function(e) {
        if (dealSearchWrapper && !dealSearchWrapper.contains(e.target)) {
            hideTypeahead();
        }
    }); 
 
 
    /* ========================================================= 
       PRICE MASK TOGGLE 
    ========================================================= */ 
    function togglePriceMask(event, button) { 
        event.preventDefault(); 
        event.stopPropagation(); 
 
        const section = button.closest('.deal-price-section'); 
        if (!section) return; 
 
        const masked = section.querySelector('.price-masked'); 
        const numeric = section.querySelector('.price-numeric'); 
 
        if (masked && numeric) { 
            if (masked.style.display === 'none') { 
                masked.style.display = 'block'; 
                numeric.style.display = 'none'; 
            } else { 
                masked.style.display = 'none'; 
                numeric.style.display = 'block'; 
            } 
        } 
    } 
 
 
    /* ========================================================= 
       STAGE COLOR & ACTIONS 
    ========================================================= */ 
    function setStageColor(button, color) { 
        const column = button.closest('.kanban-column'); 
        if (column) { 
            const header = column.querySelector('.column-header'); 
            if (header) { 
                header.style.borderTopColor = color; 
            } 
        } 
        closeAllMenus(); 
    } 
 
    function moveStageLeft(button) { 
        const column = button.closest('.kanban-column'); 
        if (column && column.previousElementSibling && column.previousElementSibling.classList.contains('kanban-column')) { 
            column.parentNode.insertBefore(column, column.previousElementSibling); 
        } 
    } 
 
    function moveStageRight(button) { 
        const column = button.closest('.kanban-column'); 
        if (column && column.nextElementSibling && column.nextElementSibling.classList.contains('kanban-column')) { 
            column.parentNode.insertBefore(column.nextElementSibling, column); 
        } 
    } 
 
    function renameStage(button) { 
        const column = button.closest('.kanban-column'); 
        if (column) { 
            const titleEl = column.querySelector('.column-title'); 
            const currentName = titleEl ? titleEl.textContent.trim() : ''; 
            const newName = prompt('Enter new stage name:', currentName); 
            if (newName && newName.trim() && titleEl) { 
                titleEl.textContent = newName.trim(); 
            } 
        } 
    } 

    /* ========================================================= 
       DRAG AND DROP KANBAN ENGINE 
    ========================================================= */ 
    var draggedDealCard = null; 
    var dragSourceColumn = null; 
    var dragSourceStage = null; 
    var isDraggingDeal = false; 
    var dropPlaceholder = null; 

    const kanbanScrollEl = document.querySelector('.kanban-scroll'); 

    function showToast(message, type = 'info', duration = 4000) { 
        let toast = document.getElementById('kanbanToast'); 
        if (!toast) { 
            toast = document.createElement('div'); 
            toast.id = 'kanbanToast'; 
            toast.className = 'kanban-toast'; 
            document.body.appendChild(toast); 
        } 
        
        const configs = {
            success: {
                title: 'Success',
                iconBg: '#f0fdf4',
                iconColor: '#16a34a',
                border: '#bbf7d0',
                svg: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'
            },
            error: {
                title: 'Error',
                iconBg: '#fef2f2',
                iconColor: '#dc2626',
                border: '#fecaca',
                svg: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
            },
            warning: {
                title: 'Warning',
                iconBg: '#fffbeb',
                iconColor: '#d97706',
                border: '#fde68a',
                svg: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>'
            },
            info: {
                title: 'Notice',
                iconBg: '#eff6ff',
                iconColor: '#2563eb',
                border: '#bfdbfe',
                svg: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
            }
        };

        const cfg = configs[type] || configs.info;
        toast.className = 'kanban-toast toast-' + type;
        toast.style.borderColor = cfg.border;
        toast.innerHTML = `
            <div style="flex-shrink: 0; width: 32px; height: 32px; border-radius: 50%; background: ${cfg.iconBg}; color: ${cfg.iconColor}; display: flex; align-items: center; justify-content: center;">
                ${cfg.svg}
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 13px; font-weight: 600; color: #0f172a; line-height: 1.3;">${cfg.title}</div>
                <div style="font-size: 12.5px; font-weight: 400; color: #475569; margin-top: 2px; line-height: 1.4; word-break: break-word;">${message}</div>
            </div>
            <button type="button" onclick="this.parentElement.classList.remove('show')" style="flex-shrink: 0; background: transparent; border: none; padding: 4px; cursor: pointer; color: #94a3b8; border-radius: 6px; display: flex; align-items: center; justify-content: center;" onmouseenter="this.style.color='#475569'" onmouseleave="this.style.color='#94a3b8'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        `;
        toast.classList.add('show'); 

        clearTimeout(toast._timeout); 
        toast._timeout = setTimeout(function() { 
            toast.classList.remove('show'); 
        }, duration); 
    } 

    function updateColumnMeta(stage, metaData) { 
        const metaEl = document.querySelector(`.column-meta[data-stage-meta="${stage}"]`); 
        if (metaEl && metaData) { 
            metaEl.textContent = metaData.label || `${metaData.formatted_total} • ${metaData.count} ${metaData.count === 1 ? 'Deal' : 'Deals'}`; 
        } 
    } 

    function syncEmptyState(columnContent) { 
        if (!columnContent) return; 
        const cards = columnContent.querySelectorAll('.deal-card'); 
        let emptyEl = columnContent.querySelector('.empty-stage'); 
        if (cards.length === 0) { 
            if (!emptyEl) { 
                emptyEl = document.createElement('div'); 
                emptyEl.className = 'empty-stage'; 
                emptyEl.textContent = 'No deals in this stage.'; 
                columnContent.appendChild(emptyEl); 
            } else { 
                emptyEl.style.display = ''; 
            } 
        } else { 
            if (emptyEl) { 
                emptyEl.remove(); 
            } 
        } 
    } 

    function initKanbanDragDrop() { 
        const cards = document.querySelectorAll('.deal-card[draggable="true"]'); 
        const columns = document.querySelectorAll('.kanban-column'); 

        cards.forEach(function(card) { 
            card.removeEventListener('dragstart', handleCardDragStart); 
            card.removeEventListener('dragend', handleCardDragEnd); 
            card.addEventListener('dragstart', handleCardDragStart); 
            card.addEventListener('dragend', handleCardDragEnd); 
        }); 

        columns.forEach(function(column) { 
            column.removeEventListener('dragover', handleColumnDragOver); 
            column.removeEventListener('dragenter', handleColumnDragEnter); 
            column.removeEventListener('dragleave', handleColumnDragLeave); 
            column.removeEventListener('drop', handleColumnDrop); 

            column.addEventListener('dragover', handleColumnDragOver); 
            column.addEventListener('dragenter', handleColumnDragEnter); 
            column.addEventListener('dragleave', handleColumnDragLeave); 
            column.addEventListener('drop', handleColumnDrop); 
        }); 

        if (kanbanScrollEl) { 
            kanbanScrollEl.removeEventListener('dragover', handleKanbanAutoScroll); 
            kanbanScrollEl.addEventListener('dragover', handleKanbanAutoScroll); 
        } 
    } 

    function handleCardDragStart(e) { 
        if ( 
            e.target.closest('.deal-card-checkbox') || 
            e.target.closest('.deal-menu-wrap') || 
            e.target.closest('.price-eye-btn') || 
            e.target.closest('.deal-dots') || 
            e.target.closest('.deal-menu') 
        ) { 
            e.preventDefault(); 
            return false; 
        } 

        draggedDealCard = this; 
        dragSourceColumn = this.closest('.column-content'); 
        dragSourceStage = this.getAttribute('data-stage') || (dragSourceColumn ? dragSourceColumn.getAttribute('data-stage') : null); 
        isDraggingDeal = true; 

        e.dataTransfer.effectAllowed = 'move'; 
        e.dataTransfer.setData('text/plain', this.getAttribute('data-deal-id') || ''); 

        if (!dropPlaceholder) { 
            dropPlaceholder = document.createElement('div'); 
            dropPlaceholder.className = 'kanban-drop-placeholder'; 
            dropPlaceholder.textContent = 'Drop deal here'; 
        } 

        setTimeout(() => { 
            if (draggedDealCard) { 
                draggedDealCard.classList.add('is-dragging'); 
            } 
        }, 0); 
    } 

    function handleCardDragEnd(e) { 
        if (draggedDealCard) { 
            draggedDealCard.classList.remove('is-dragging'); 
        } 
        document.querySelectorAll('.column-content.drag-over').forEach(function(col) { 
            col.classList.remove('drag-over'); 
        }); 
        if (dropPlaceholder && dropPlaceholder.parentNode) { 
            dropPlaceholder.parentNode.removeChild(dropPlaceholder); 
        } 

        draggedDealCard = null; 
        dragSourceColumn = null; 
        dragSourceStage = null; 

        setTimeout(function() { 
            isDraggingDeal = false; 
        }, 150); 
    } 

    function handleColumnDragOver(e) { 
        e.preventDefault(); 
        e.dataTransfer.dropEffect = 'move'; 

        const content = this.querySelector('.column-content'); 
        if (!content || !draggedDealCard) return; 

        if (!content.classList.contains('drag-over')) { 
            content.classList.add('drag-over'); 
        } 

        if (dropPlaceholder && dropPlaceholder.parentNode !== content) { 
            const afterElement = getDragAfterElement(content, e.clientY); 
            if (afterElement == null) { 
                content.appendChild(dropPlaceholder); 
            } else { 
                content.insertBefore(dropPlaceholder, afterElement); 
            } 
        } 
    } 

    function handleColumnDragEnter(e) { 
        e.preventDefault(); 
        const content = this.querySelector('.column-content'); 
        if (content && draggedDealCard) { 
            content.classList.add('drag-over'); 
        } 
    } 

    function handleColumnDragLeave(e) { 
        const content = this.querySelector('.column-content'); 
        if (content && !this.contains(e.relatedTarget)) { 
            content.classList.remove('drag-over'); 
            if (dropPlaceholder && dropPlaceholder.parentNode === content) { 
                dropPlaceholder.remove(); 
            } 
        } 
    } 

    function getDragAfterElement(container, y) { 
        const draggableElements = [...container.querySelectorAll('.deal-card:not(.is-dragging)')]; 

        return draggableElements.reduce((closest, child) => { 
            const box = child.getBoundingClientRect(); 
            const offset = y - box.top - box.height / 2; 
            if (offset < 0 && offset > closest.offset) { 
                return { offset: offset, element: child }; 
            } else { 
                return closest; 
            } 
        }, { offset: Number.NEGATIVE_INFINITY }).element; 
    } 

    function handleKanbanAutoScroll(e) { 
        if (!isDraggingDeal || !kanbanScrollEl) return; 

        const rect = kanbanScrollEl.getBoundingClientRect(); 
        const mouseX = e.clientX; 
        const edgeThreshold = 80; 
        const scrollStep = 18; 

        if (mouseX > rect.right - edgeThreshold) { 
            kanbanScrollEl.scrollLeft += scrollStep; 
        } else if (mouseX < rect.left + edgeThreshold) { 
            kanbanScrollEl.scrollLeft -= scrollStep; 
        } 
    } 

    async function handleColumnDrop(e) { 
        e.preventDefault(); 
        e.stopPropagation(); 

        const targetColumn = this; 
        const targetContent = targetColumn.querySelector('.column-content'); 
        const targetStage = targetColumn.getAttribute('data-stage') || (targetContent ? targetContent.getAttribute('data-stage') : null); 
        const card = draggedDealCard; 
        const sourceCol = dragSourceColumn; 
        const sourceStage = dragSourceStage; 

        document.querySelectorAll('.column-content.drag-over').forEach(c => c.classList.remove('drag-over')); 
        if (dropPlaceholder && dropPlaceholder.parentNode) { 
            dropPlaceholder.parentNode.removeChild(dropPlaceholder); 
        } 

        if (!card || !targetContent || !targetStage) return; 

        const dealId = card.getAttribute('data-deal-id'); 
        if (!dealId) return; 

        // Dropped in the same column
        if (targetStage === sourceStage) { 
            const afterElement = getDragAfterElement(targetContent, e.clientY); 
            if (afterElement == null) { 
                targetContent.appendChild(card); 
            } else { 
                targetContent.insertBefore(card, afterElement); 
            } 
            return; 
        } 

        // Dropped in a different stage column -> AJAX Backend stage change
        if (card.classList.contains('is-updating')) { 
            return; 
        } 

        card.classList.add('is-updating'); 

        try { 
            const csrfMeta = document.querySelector('meta[name="csrf-token"]'); 
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '{{ csrf_token() }}'; 

            const response = await fetch(`{{ url('/deals') }}/${dealId}/stage`, { 
                method: 'PATCH', 
                headers: { 
                    'Content-Type': 'application/json', 
                    'Accept': 'application/json', 
                    'X-CSRF-TOKEN': csrfToken 
                }, 
                body: JSON.stringify({ 
                    pipeline_stage: targetStage 
                }) 
            }); 

            const data = await response.json(); 

            if (!response.ok || !data.success) { 
                const errorMsg = (data && data.message) ? data.message : 'Unable to update deal stage. Please try again.'; 
                showToast(errorMsg, 'error', 4500); 
                card.classList.remove('is-updating'); 
                return; 
            } 

            // SUCCESS: Move card element into destination column
            const afterElement = getDragAfterElement(targetContent, e.clientY); 
            if (afterElement == null) { 
                targetContent.appendChild(card); 
            } else { 
                targetContent.insertBefore(card, afterElement); 
            } 

            card.setAttribute('data-stage', targetStage); 
            card.classList.remove('is-updating'); 

            // Update empty states
            syncEmptyState(sourceCol); 
            syncEmptyState(targetContent); 

            // Update column aggregates & totals dynamically
            if (data.stages) { 
                Object.keys(data.stages).forEach(function(st) { 
                    updateColumnMeta(st, data.stages[st]); 
                }); 
            } 

            showToast(data.message || `Deal moved to ${targetStage}.`, 'success', 3000); 

        } catch (err) { 
            console.error('Drag and drop stage update error:', err); 
            showToast('Unable to update the deal stage. Please try again.', 'error', 4500); 
            card.classList.remove('is-updating'); 
        } 
    } 

    document.addEventListener('DOMContentLoaded', function() { 
        initKanbanDragDrop(); 
    }); 

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

<div id="kanbanToast" class="kanban-toast"></div>

<x-ordo-tooltip />


@endsection

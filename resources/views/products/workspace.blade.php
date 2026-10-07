@extends('layouts.app')

@section('content')

<div class="w-full px-6 space-y-6">
{{-- =========================================================
        1. TOP RIGHT: Maliit na "Back to Dashboard" sa pinakataas
    ========================================================== --}}
    <div class="flex justify-end mb-[-12px]">
        <a href="{{ route('products.index') }}"
           class="text-blue-600 hover:text-blue-800 text-xs font-semibold flex items-center space-x-1">
            <span>&larr; Back to Dashboard</span>
        </a>
    </div>

{{-- =========================================================
    COMPLETENESS GATE & SUBMIT FOR APPROVAL BANNER
========================================================== --}}
<div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-4 mb-6">
    
    <!-- Message Container -->
    <div class="flex items-center space-x-2" id="completeness-message-container">
        <i id="completeness-icon" class="fa-solid fa-triangle-exclamation text-slate-900 text-lg"></i>
        <span id="completeness-text" class="text-slate-700 text-sm">
            Completeness Gate Pending: Please complete all required sections to enable approval submission.
        </span>
    </div>

    <!-- Action / Button Container -->
    <div>
        <span id="incomplete-badge" class="px-3 py-1.5 bg-slate-100 text-slate-500 font-bold text-xs rounded-lg border border-slate-200 uppercase tracking-wider">
            Incomplete
        </span>

        <form id="submit-approval-form" action="{{ route('products.submit-approval', $product->id ?? 1) }}" method="POST" style="display: none;">
            @csrf
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg transition shadow-sm flex items-center space-x-1.5 cursor-pointer">
                <i class="fa-solid fa-paper-plane"></i>
                <span>Submit for Approval</span>
            </button>
        </form>
    </div>

</div>


    {{-- =========================================================
        3. PRODUCT WORKSPACE HEADER (May Dynamic View/Edit Mode Toggle)
    ========================================================== --}}
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <span class="bg-blue-50 text-blue-700 border border-blue-200 px-3 py-1 rounded-md text-xs font-mono font-bold tracking-wider">
                    {{ $product->sku ?? 'PRD-0001' }}
                </span>

                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    {{ $product->name ?? 'Product Name' }}
                </h1>

                {{-- Dynamic Badge: Nagbabago depende sa kasalukuyang mode --}}
                @if(request('mode') === 'view')
                    <span class="bg-slate-100 text-slate-700 border border-slate-300 px-2.5 py-1 rounded text-[11px] font-semibold flex items-center">
                        <i class="fa-solid fa-eye text-[9px] mr-1"></i>
                        View Only Mode
                    </span>
                @else
                    <span class="bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-1 rounded text-[11px] font-semibold flex items-center">
                        <i class="fa-solid fa-pen-to-square text-[9px] mr-1"></i>
                        Editing Mode
                    </span>
                @endif
            </div>

            {{-- Status, Version Badge, at Dynamic Toggle Buttons --}}
            <div class="flex items-center space-x-2">
                <span class="px-3 py-1 bg-white text-slate-700 rounded font-bold text-xs border border-slate-200 uppercase">
                    {{ $product->status ?? 'Draft' }}
                </span>

                <span class="px-3 py-1 bg-slate-50 text-slate-600 rounded font-semibold text-xs border border-slate-200">
                    Version V1.0
                </span>

                @if(request('mode') === 'view')
                    {{-- Kung nasa View Only mode, ang lalabas ay button para lumipat sa Edit Mode --}}
                    <a href="?mode=edit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-lg transition shadow-sm inline-flex items-center space-x-1 cursor-pointer">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <span>Edit Workspace</span>
                    </a>
                @else
                    {{-- Kung nasa Editing mode, ang lalabas ay button para lumipat sa View Only --}}
                    <a href="?mode=view" class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-xs rounded-lg transition shadow-sm inline-flex items-center space-x-1 cursor-pointer">
                        <i class="fa-solid fa-eye text-slate-400"></i>
                        <span>View Only</span>
                    </a>
                @endif
            </div>
        </div>

        <p class="text-xs text-slate-500 font-medium pt-1 border-t border-slate-100">
            Area:
            <strong class="text-slate-700">
                {{ $product->service_area ?? 'N/A' }}
            </strong>

            &bull;

            Category:
            <strong class="text-slate-700">
                {{ $product->category ?? 'N/A' }}
            </strong>

            &bull;

            Product Type:
            <strong class="text-slate-700">
                {{ $product->product_type ?? 'Product' }}
            </strong>

            &bull;

            Inventory:
            <strong class="text-emerald-600 font-bold">
                {{ $product->inventory_stock ?? 0 }} Units Available
            </strong>
        </p>
    </div>

</div>




{{-- =========================================================
        12-STEP WORKSPACE PIPELINE & NAVIGATION TABS
    ========================================================== --}}
    @php
        $pipeline = [
            1 => 'Overview',
            2 => 'Product Catalog',
            3 => 'Inventory Specs',
            4 => 'Requirements',
            5 => 'Workflow',
            6 => 'Commercials',
            7 => 'Engagement',
            8 => 'Reporting',
            9 => 'Automation',
            10 => 'Terms',
            11 => 'Usage & Performance',
            12 => 'Version & History',
        ];

        $tabIcons = [
            1 => 'fa-house',
            2 => 'fa-file-lines',
            3 => 'fa-boxes-stacked',
            4 => 'fa-list-check',
            5 => 'fa-diagram-project',
            6 => 'fa-tags',
            7 => 'fa-handshake',
            8 => 'fa-chart-pie',
            9 => 'fa-robot',
            10 => 'fa-scale-balanced',
            11 => 'fa-chart-line',
            12 => 'fa-clock-rotate-left',
        ];
    @endphp


{{-- PIPELINE SECTION (Buo mula 1 hanggang 12 sa iisang hilera) --}}
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
        <div class="flex items-center justify-between min-w-[1200px] px-6 relative">
            <div class="absolute left-12 right-12 top-1/2 -translate-y-1/2 h-0.5 bg-slate-200 z-0"></div>

            @foreach($pipeline as $step => $label)
                @php
                    $isCompleted = false; 
                @endphp

                <div class="relative z-10 flex flex-col items-center group flex-1">
                    <div id="pipe-step-{{ $step }}"
                         class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition shadow-sm
                         @if($isCompleted)
                             bg-blue-600 text-white
                         @else
                             bg-white border-2 border-slate-300 text-slate-600
                         @endif">
                        @if($isCompleted)
                            <i class="fa-solid fa-check text-xs"></i>
                        @else
                            {{ $step }}
                        @endif
                    </div>
                    <span class="text-[10px] font-semibold text-slate-500 mt-1.5 whitespace-nowrap text-center">
                        {{ $label }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

{{-- NAVIGATION TABS SECTION (Naka-grid para perpektong align ang 6 columns) --}}
@php
    $firstTabRow = array_slice($pipeline, 0, 6, true);   // Tabs 1 to 6
    $secondTabRow = array_slice($pipeline, 6, 6, true);  // Tabs 7 to 12
@endphp

<div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm space-y-3 overflow-x-auto">
    {{-- Row 1: Tabs 1 to 6 --}}
    <div class="grid grid-cols-6 gap-2 min-w-[1200px]">
        @foreach($firstTabRow as $step => $label)
            <button type="button"
                    onclick="switchWorkspaceTab({{ $step }})"
                    id="tab-btn-{{ $step }}"
                    class="workspace-tab-btn px-3 py-2.5 w-full justify-center
                    {{ (request('tab', 1) == $step)
                        ? 'bg-blue-600 text-white shadow-sm'
                        : 'hover:bg-slate-100 text-slate-600' }}
                    rounded-lg flex items-center space-x-1.5 cursor-pointer transition">
                <i class="fa-solid {{ $tabIcons[$step] }} text-[11px]"></i>
                <span class="whitespace-nowrap">
                    {{ $label }}
                </span>
            </button>
        @endforeach
    </div>

    {{-- Row 2: Tabs 7 to 12 --}}
    <div class="grid grid-cols-6 gap-2 min-w-[1200px] pt-2 border-t border-slate-100">
        @foreach($secondTabRow as $step => $label)
            <button type="button"
                    onclick="switchWorkspaceTab({{ $step }})"
                    id="tab-btn-{{ $step }}"
                    class="workspace-tab-btn px-3 py-2.5 w-full justify-center
                    {{ (request('tab', 1) == $step)
                        ? 'bg-blue-600 text-white shadow-sm'
                        : 'hover:bg-slate-100 text-slate-600' }}
                    rounded-lg flex items-center space-x-1.5 cursor-pointer transition">
                <i class="fa-solid {{ $tabIcons[$step] }} text-[11px]"></i>
                <span class="whitespace-nowrap">
                    {{ $label }}
                </span>
            </button>
        @endforeach
    </div>
</div>

    {{-- =========================================================
    STEP 1 — PRODUCT OVERVIEW
========================================================== --}}

<div
    id="workspace-panel-1"
    class="workspace-panel bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-6"
>

    <!-- ===================================================== -->
    <!-- HEADER -->
    <!-- ===================================================== -->

    <div class="border-b border-slate-100 pb-3">

        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Product Overview &amp; Knowledge Base
        </h3>

        <p class="text-xs text-slate-500 mt-0.5">
            Maintain the core product identity, explanation, purpose, and usage guidance.
        </p>

    </div>


    <!-- ===================================================== -->
    <!-- PRODUCT OVERVIEW FORM -->
    <!-- ===================================================== -->

    <form
        action="{{ Route::has('products.overview.update') ? route('products.overview.update', $product->id) : '#' }}"
        method="POST"
        class="space-y-4 text-xs"
    >

        @csrf
        @method('PUT')

        <input
            type="hidden"
            name="tab"
            value="overview"
        >

        <input
            type="hidden"
            name="mode"
            value="{{ $mode }}"
        >


        <!-- ===================================================== -->
        <!-- BASIC PRODUCT INFORMATION -->
        <!-- ===================================================== -->

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <!-- SHORT NAME -->

            <div>

                <label class="block font-semibold text-slate-600 mb-1">

                    <span class="inline-flex items-center gap-1">

                        <span>
                            Short Name / Display Alias
                        </span>

                        <span class="relative inline-flex items-center group cursor-pointer">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                            <!-- TOOLTIP OUTSIDE FIELD -->

                            <span
                                class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                An optional concise display name or alias used for quick identification across system modules.
                            </span>

                        </span>

                    </span>

                </label>


                <input
                    type="text"
                    name="short_name"
                    {{ $isViewOnly ? 'disabled' : '' }}
                    value="{{ old('short_name', $product->short_name ?? '') }}"
                    placeholder="Optional concise display name..."
                    class="w-full border border-slate-300 rounded p-2 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}"
                >

            </div>


            <!-- EXPECTED TURNAROUND -->

            <div>

                <label class="block font-semibold text-slate-600 mb-1">

                    <span class="inline-flex items-center gap-1">

                        <span>
                            Expected Turnaround Time
                        </span>

                        <span class="relative inline-flex items-center group cursor-pointer">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                            <!-- TOOLTIP OUTSIDE FIELD -->

                            <span
                                class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                The projected timeframe required to complete, fulfill, or deliver the product under normal operating conditions.
                            </span>

                        </span>

                    </span>

                </label>


                <input
                    type="text"
                    name="expected_turnaround"
                    {{ $isViewOnly ? 'disabled' : '' }}
                    value="{{ old('expected_turnaround', $product->expected_turnaround ?? '5-7 days') }}"
                    placeholder="e.g. 5-7 days"
                    class="w-full border border-slate-300 rounded p-2 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}"
                >

            </div>


            <!-- EFFECTIVE DATE -->

            <div>

                <label class="block font-semibold text-slate-600 mb-1">

                    <span class="inline-flex items-center gap-1">

                        <span>
                            Effective Date
                        </span>

                        <span class="relative inline-flex items-center group cursor-pointer">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                            <!-- TOOLTIP OUTSIDE FIELD -->

                            <span
                                class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                The official starting date when this specific product definition becomes active and applicable.
                            </span>

                        </span>

                    </span>

                </label>


                <input
                    type="date"
                    name="effective_date"
                    {{ $isViewOnly ? 'disabled' : '' }}
                    value="{{ old('effective_date', isset($product->effective_date) ? \Carbon\Carbon::parse($product->effective_date)->format('Y-m-d') : '') }}"
                    class="w-full border border-slate-300 rounded p-2 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}"
                >

            </div>

        </div>


        <!-- ===================================================== -->
        <!-- PRODUCT DESCRIPTIONS -->
        <!-- ===================================================== -->

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <!-- INTERNAL DESCRIPTION -->

            <div>

                <label class="block font-semibold text-slate-600 mb-1">

                    <span class="inline-flex items-center gap-1">

                        <span>
                            Internal Description *
                        </span>

                        <span class="relative inline-flex items-center group cursor-pointer">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                            <!-- TOOLTIP OUTSIDE FIELD -->

                            <span
                                class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Comprehensive internal explanation, background context, and operational guidelines intended for associates and team members.
                            </span>

                        </span>

                    </span>

                </label>


                <textarea
                    name="internal_description"
                    rows="3"
                    required
                    {{ $isViewOnly ? 'disabled' : '' }}
                    class="w-full border border-slate-300 rounded p-2.5 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}"
                    placeholder="Internal explanation and context for associates..."
                >{{ old('internal_description', $product->internal_description ?? '') }}</textarea>

            </div>


            <!-- CLIENT-FACING DESCRIPTION -->

            <div>

                <label class="block font-semibold text-slate-600 mb-1">

                    <span class="inline-flex items-center gap-1">

                        <span>
                            Client-Facing Description
                        </span>

                        <span class="relative inline-flex items-center group cursor-pointer">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                            <!-- TOOLTIP OUTSIDE FIELD -->

                            <span
                                class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                A polished, reusable summary of the product designed explicitly to be presented to clients within proposals, catalogs, quotations, and other client-facing materials.
                            </span>

                        </span>

                    </span>

                </label>


                <textarea
                    name="client_description"
                    rows="3"
                    {{ $isViewOnly ? 'disabled' : '' }}
                    class="w-full border border-slate-300 rounded p-2.5 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}"
                    placeholder="Reusable client-facing summary for proposals and catalog materials..."
                >{{ old('client_description', $product->client_description ?? '') }}</textarea>

            </div>

        </div>


        <!-- ===================================================== -->
        <!-- ABOUT THIS PRODUCT -->
        <!-- ===================================================== -->

        <div class="p-4 bg-slate-50 rounded-lg border border-slate-200 space-y-1">

            <div class="flex items-center justify-between">

                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                    About This Product (Internal Explanation)
                </label>

                <span class="bg-slate-200 text-slate-600 text-[10px] font-semibold px-2 py-0.5 rounded">
                    REFERENCE ONLY
                </span>

            </div>

            <p class="text-xs text-slate-600 leading-relaxed pt-1">
                Standardized product definition for associate guidance,
                operational consistency, product positioning, and quality assurance.
            </p>

        </div>


        <!-- ===================================================== -->
        <!-- PURPOSE & WHEN TO USE -->
        <!-- ===================================================== -->

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <!-- PURPOSE -->

            <div>

                <label class="block font-semibold text-slate-600 mb-1">

                    <span class="inline-flex items-center gap-1">

                        <span>
                            Purpose
                        </span>

                        <span class="relative inline-flex items-center group cursor-pointer">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                            <!-- TOOLTIP OUTSIDE FIELD -->

                            <span
                                class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                The primary goal, objective, or reason why this specific product exists within the organization's catalog.
                            </span>

                        </span>

                    </span>

                </label>


                <textarea
                    name="purpose"
                    rows="2"
                    {{ $isViewOnly ? 'disabled' : '' }}
                    class="w-full border border-slate-300 rounded p-2.5 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}"
                    placeholder="Why the product exists..."
                >{{ old('purpose', $product->purpose ?? '') }}</textarea>

            </div>


            <!-- WHEN TO USE -->

            <div>

                <label class="block font-semibold text-slate-600 mb-1">

                    <span class="inline-flex items-center gap-1">

                        <span>
                            When to Use
                        </span>

                        <span class="relative inline-flex items-center group cursor-pointer">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                            <!-- TOOLTIP OUTSIDE FIELD -->

                            <span
                                class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Typical business scenarios, client conditions, or triggers where this product is appropriately applied, recommended, or offered.
                            </span>

                        </span>

                    </span>

                </label>


                <textarea
                    name="when_to_use"
                    rows="2"
                    {{ $isViewOnly ? 'disabled' : '' }}
                    class="w-full border border-slate-300 rounded p-2.5 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}"
                    placeholder="Typical situations where product applies..."
                >{{ old('when_to_use', $product->when_to_use ?? '') }}</textarea>

            </div>

        </div>


        <!-- ===================================================== -->
        <!-- WHAT THIS PRODUCT IS NOT -->
        <!-- ===================================================== -->

        <div>

            <label class="block font-semibold text-slate-600 mb-1">

                <span class="inline-flex items-center gap-1">

                    <span>
                        What This Product Is Not
                    </span>

                    <span class="relative inline-flex items-center group cursor-pointer">

                        <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                        <!-- TOOLTIP OUTSIDE FIELD -->

                        <span
                            class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                        >
                            Clarifications or boundary notes to prevent confusion regarding closely related products, offerings, or use cases.
                        </span>

                    </span>

                </span>

            </label>


            <input
                type="text"
                name="what_product_is_not"
                {{ $isViewOnly ? 'disabled' : '' }}
                value="{{ old('what_product_is_not', $product->what_product_is_not ?? '') }}"
                placeholder="Optional note to prevent confusion..."
                class="w-full border border-slate-300 rounded p-2 outline-none {{ $isViewOnly ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'focus:border-blue-500 bg-white' }}"
            >

        </div>


        <!-- ===================================================== -->
        <!-- SAVE / CONTINUE -->
        <!-- ===================================================== -->

        @if(!$isViewOnly)

            <div class="flex justify-end pt-4 border-t">

                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded text-xs transition shadow-sm cursor-pointer flex items-center space-x-1.5"
                >
                    <span>Save &amp; Continue to Catalog</span>

                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </button>

            </div>

        @endif

    </form>

</div>


  {{-- =========================================================
    STEP 2 — PRODUCT CATALOG
========================================================== --}}

<div
    id="workspace-panel-2"
    class="workspace-panel bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-6 hidden"
>

    <div class="border-b border-slate-100 pb-3">

        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Product Catalog Content
        </h3>

        <p class="text-xs text-slate-500 mt-0.5">
            Define the standard catalog content, scope, deliverables, client responsibilities, and exclusions for this product.
        </p>

    </div>


    <form
        action="{{ route('products.catalog.update', $product->id) }}"
        method="POST"
        class="space-y-6"
    >

        @csrf
        @method('PUT')


        <!-- ===================================================== -->
        <!-- SCOPE OF PRODUCT -->
        <!-- ===================================================== -->

        <div>

            <label class="block font-semibold text-slate-700 mb-1 text-xs">

                <span class="inline-flex items-center gap-1">

                    <span>
                        Scope of Product
                    </span>

                    <span class="text-red-500">*</span>

                    <span class="relative inline-flex items-center group cursor-pointer">

                        <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                        <!-- TOOLTIP OUTSIDE FIELD -->

                        <span
                            class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                        >
                            Defines what the organization agrees to provide as part of this product, including the expected scope, coverage, and standard service boundaries.
                        </span>

                    </span>

                </span>

            </label>


            <textarea
                name="scope_of_work"
                rows="5"
                required
                placeholder="Detail what the organization agrees to provide as part of this product..."
                class="w-full border border-slate-300 rounded-lg p-3 bg-white outline-none focus:border-blue-500 text-xs"
            >{{ old('scope_of_work', $product->scope_of_work ?? '') }}</textarea>


            @error('scope_of_work')

                <p class="text-red-500 text-[11px] mt-1">
                    {{ $message }}
                </p>

            @enderror

        </div>


        <!-- ===================================================== -->
        <!-- DELIVERABLES / CLIENT RESPONSIBILITIES -->
        <!-- ===================================================== -->

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">


            <!-- DELIVERABLES -->

            <div>

                <label class="block font-semibold text-slate-700 mb-1 text-xs">

                    <span class="inline-flex items-center gap-1">

                        <span>
                            Deliverables / What You Will Receive
                        </span>

                        <span class="text-red-500">*</span>

                        <span class="relative inline-flex items-center group cursor-pointer">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                            <!-- TOOLTIP OUTSIDE FIELD -->

                            <span
                                class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Describes the tangible outputs, components, results, or benefits that the client is expected to receive from this product.
                            </span>

                        </span>

                    </span>

                </label>


                <textarea
                    name="deliverables"
                    rows="6"
                    required
                    placeholder="Key tangible deliverables, outputs, components, or benefits the client receives..."
                    class="w-full border border-slate-300 rounded-lg p-3 bg-white outline-none focus:border-blue-500 text-xs"
                >{{ old('deliverables', $product->deliverables ?? '') }}</textarea>


                @error('deliverables')

                    <p class="text-red-500 text-[11px] mt-1">
                        {{ $message }}
                    </p>

                @enderror

            </div>


            <!-- CLIENT RESPONSIBILITIES -->

            <div>

                <label class="block font-semibold text-slate-700 mb-1 text-xs">

                    <span class="inline-flex items-center gap-1">

                        <span>
                            Client Responsibilities
                        </span>

                        <span class="relative inline-flex items-center group cursor-pointer">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                            <!-- TOOLTIP OUTSIDE FIELD -->

                            <span
                                class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Specifies the information, actions, approvals, access, cooperation, or other obligations that must be provided or completed by the client.
                            </span>

                        </span>

                    </span>

                </label>


                <textarea
                    name="client_responsibilities"
                    rows="6"
                    placeholder="Actions, information, approvals, access, or obligations belonging to the client..."
                    class="w-full border border-slate-300 rounded-lg p-3 bg-white outline-none focus:border-blue-500 text-xs"
                >{{ old('client_responsibilities', $product->client_responsibilities ?? '') }}</textarea>


                @error('client_responsibilities')

                    <p class="text-red-500 text-[11px] mt-1">
                        {{ $message }}
                    </p>

                @enderror

            </div>

        </div>


        <!-- ===================================================== -->
        <!-- EXCLUSIONS -->
        <!-- ===================================================== -->

        <div>

            <label class="block font-semibold text-slate-700 mb-1 text-xs">

                <span class="inline-flex items-center gap-1">

                    <span>
                        Exclusions / Out of Scope
                    </span>

                    <span class="relative inline-flex items-center group cursor-pointer">

                        <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                        <!-- TOOLTIP OUTSIDE FIELD -->

                        <span
                            class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                        >
                            Clarifies what is specifically excluded from the standard product scope and would require a separate agreement, product, or additional arrangement.
                        </span>

                    </span>

                </span>

            </label>


            <textarea
                name="exclusions"
                rows="5"
                placeholder="What is not included unless separately agreed..."
                class="w-full border border-slate-300 rounded-lg p-3 bg-white outline-none focus:border-blue-500 text-xs"
            >{{ old('exclusions', $product->exclusions ?? '') }}</textarea>


            @error('exclusions')

                <p class="text-red-500 text-[11px] mt-1">
                    {{ $message }}
                </p>

            @enderror

        </div>


        <!-- ===================================================== -->
        <!-- NAVIGATION -->
        <!-- ===================================================== -->

        <div class="flex justify-between items-center pt-4 border-t border-slate-100">

            <button
                type="button"
                onclick="switchWorkspaceTab(1)"
                class="text-xs font-semibold text-slate-500 hover:text-blue-600"
            >
                ← Back to Overview
            </button>


            <button
                type="submit"
                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm transition"
            >
                Save &amp; Continue to Inventory Specs

                <i class="fa-solid fa-arrow-right ml-1"></i>
            </button>

        </div>

    </form>

</div>


{{-- =========================================================
    STEP 3 — INVENTORY SPECS
========================================================== --}}

@php
    $isNonInventory = ($product->inventory_type ?? '') === 'Non-Inventory';

    $readOnlyState = ($isViewOnly || $isNonInventory)
        ? 'disabled bg-slate-100 text-slate-400 cursor-not-allowed'
        : 'bg-white text-slate-700';
@endphp


<div
    id="workspace-panel-3"
    class="workspace-panel bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-5 hidden"
>

    <form
        action="{{ route('products.inventory.update', $product->id) }}"
        method="POST"
        class="space-y-5"
    >

        @csrf
        @method('PUT')


        {{-- ===================================================== --}}
        {{-- HIDDEN VALUES FOR NON-INVENTORY --}}
        {{-- ===================================================== --}}

        @if($isNonInventory)

            <input type="hidden" name="inventory_type" value="{{ $product->inventory_type }}">
            <input type="hidden" name="unit_measure" value="{{ $product->unit_measure ?? 'Unit' }}">
            <input type="hidden" name="stock_tracking" value="{{ $product->stock_tracking ?? 'None' }}">
            <input type="hidden" name="reorder_level" value="0">
            <input type="hidden" name="inventory_stock" value="0">
            <input type="hidden" name="minimum_stock" value="0">
            <input type="hidden" name="maximum_stock" value="0">
            <input type="hidden" name="warehouse_location" value="Not Applicable">
            <input type="hidden" name="storage_location" value="">
            <input type="hidden" name="stock_status" value="Not Applicable">

        @endif


        {{-- ===================================================== --}}
        {{-- HEADER --}}
        {{-- ===================================================== --}}

        <div class="border-b border-slate-100 pb-3">

            <h3 class="text-sm font-bold text-slate-900">
                Product Inventory Specifications
            </h3>

            <p class="text-xs text-slate-500 mt-0.5">
                Configure stock tracking, warehouse allocation, units, and inventory controls.
            </p>

        </div>


        {{-- ===================================================== --}}
        {{-- INVENTORY CLASSIFICATION --}}
        {{-- ===================================================== --}}

        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">


                {{-- ================================================= --}}
                {{-- INVENTORY TYPE --}}
                {{-- ================================================= --}}

                <div>

                    <label class="block font-bold text-slate-800 mb-1">

                        <span class="inline-flex items-center gap-1">

                            INVENTORY TYPE *

                            <span class="relative inline-flex items-center group">

                                <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] cursor-pointer"></i>

                                <span
                                    class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-[11px] p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                                >
                                    Defines the classification of the product for inventory management purposes, indicating whether the product is physically stocked, not tracked as inventory, treated as a service, or falls under another applicable classification.
                                </span>

                            </span>

                        </span>

                    </label>


                    <select
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-slate-100 text-slate-700 outline-none cursor-not-allowed"
                        disabled
                    >

                        <option
                            value=""
                            disabled
                            {{ empty($product->inventory_type) ? 'selected' : '' }}
                        >
                            Select Inventory Type
                        </option>

                        <option
                            value="Inventory"
                            {{ ($product->inventory_type ?? '') === 'Inventory' ? 'selected' : '' }}
                        >
                            Inventory
                        </option>

                        <option
                            value="Non-Inventory"
                            {{ $isNonInventory ? 'selected' : '' }}
                        >
                            Non-Inventory
                        </option>

                        <option
                            value="Service"
                            {{ ($product->inventory_type ?? '') === 'Service' ? 'selected' : '' }}
                        >
                            Service
                        </option>

                        <option
                            value="Other"
                            {{
                                !in_array(
                                    $product->inventory_type ?? '',
                                    ['Inventory', 'Non-Inventory', 'Service']
                                ) && !empty($product->inventory_type)
                                    ? 'selected'
                                    : ''
                            }}
                        >
                            {{ $product->inventory_type ?? 'Other' }}
                        </option>

                    </select>


                    <input
                        type="hidden"
                        name="inventory_type"
                        value="{{ $product->inventory_type ?? '' }}"
                    >

                </div>


                {{-- ================================================= --}}
                {{-- UNIT OF MEASURE --}}
                {{-- ================================================= --}}

                <div>

                    <label class="block font-bold text-slate-800 mb-1">

                        <span class="inline-flex items-center gap-1">

                            UNIT OF MEASURE *

                            <span class="relative inline-flex items-center group">

                                <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] cursor-pointer"></i>

                                <span
                                    class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-[11px] p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                                >
                                    Defines the standard unit used to measure, count, store, sell, or otherwise represent the quantity of the product throughout inventory and operational records.
                                </span>

                            </span>

                        </span>

                    </label>


                    <select
                        name="unit_measure"
                        required
                        {{ ($isViewOnly || $isNonInventory) ? 'disabled' : '' }}
                        class="w-full border border-slate-300 rounded-lg p-2.5 {{ $readOnlyState }} outline-none focus:border-blue-500"
                    >

                        <option
                            value=""
                            disabled
                            {{ empty($product->unit_measure) ? 'selected' : '' }}
                        >
                            Select Unit of Measure
                        </option>

                        <option
                            value="Unit"
                            {{ ($product->unit_measure ?? '') === 'Unit' ? 'selected' : '' }}
                        >
                            Unit
                        </option>

                        <option
                            value="Piece"
                            {{ ($product->unit_measure ?? '') === 'Piece' ? 'selected' : '' }}
                        >
                            Piece
                        </option>

                        <option
                            value="Set"
                            {{ ($product->unit_measure ?? '') === 'Set' ? 'selected' : '' }}
                        >
                            Set
                        </option>

                        <option
                            value="Box"
                            {{ ($product->unit_measure ?? '') === 'Box' ? 'selected' : '' }}
                        >
                            Box
                        </option>

                        <option
                            value="Package"
                            {{ ($product->unit_measure ?? '') === 'Package' ? 'selected' : '' }}
                        >
                            Package
                        </option>

                        <option
                            value="Service"
                            {{ ($product->unit_measure ?? '') === 'Service' ? 'selected' : '' }}
                        >
                            Service
                        </option>

                    </select>

                </div>


                {{-- ================================================= --}}
                {{-- STOCK TRACKING --}}
                {{-- ================================================= --}}

                <div>

                    <label class="block font-bold text-slate-800 mb-1">

                        <span class="inline-flex items-center gap-1">

                            STOCK TRACKING *

                            <span class="relative inline-flex items-center group">

                                <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] cursor-pointer"></i>

                                <span
                                    class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-[11px] p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                                >
                                    Defines how inventory quantities or individual items are monitored and identified, such as by total quantity, serial number, batch or lot, or not tracked at all.
                                </span>

                            </span>

                        </span>

                    </label>


                    <select
                        name="stock_tracking"
                        required
                        {{ ($isViewOnly || $isNonInventory) ? 'disabled' : '' }}
                        class="w-full border border-slate-300 rounded-lg p-2.5 {{ $readOnlyState }} outline-none focus:border-blue-500"
                    >

                        <option
                            value=""
                            disabled
                            {{ empty($product->stock_tracking) ? 'selected' : '' }}
                        >
                            Select Stock Tracking
                        </option>

                        <option
                            value="Quantity"
                            {{ ($product->stock_tracking ?? '') === 'Quantity' ? 'selected' : '' }}
                        >
                            Quantity
                        </option>

                        <option
                            value="Serial Number"
                            {{ ($product->stock_tracking ?? '') === 'Serial Number' ? 'selected' : '' }}
                        >
                            Serial Number
                        </option>

                        <option
                            value="Batch"
                            {{ ($product->stock_tracking ?? '') === 'Batch' ? 'selected' : '' }}
                        >
                            Batch / Lot
                        </option>

                        <option
                            value="None"
                            {{ ($product->stock_tracking ?? '') === 'None' ? 'selected' : '' }}
                        >
                            Not Tracked
                        </option>

                    </select>

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- STOCK LEVELS --}}
        {{-- ===================================================== --}}

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">


            {{-- CURRENT STOCK --}}

            <div>

                <label class="block font-semibold text-slate-700 mb-1">

                    <span class="inline-flex items-center gap-1">

                        Current Stock *

                        <span class="relative inline-flex items-center group">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] cursor-pointer"></i>

                            <span
                                class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-[11px] p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Shows the current quantity of this product available in inventory.
                            </span>

                        </span>

                    </span>

                </label>


                <input
                    type="number"
                    name="inventory_stock"
                    min="0"
                    value="{{ $product->inventory_stock ?? 0 }}"
                    {{ ($isViewOnly || $isNonInventory) ? 'disabled' : '' }}
                    class="w-full border border-slate-300 rounded-lg p-2.5 {{ $readOnlyState }} font-bold outline-none focus:border-blue-500 workspace-input"
                >

            </div>


            {{-- REORDER LEVEL --}}

            <div>

                <label class="block font-semibold text-slate-700 mb-1">

                    <span class="inline-flex items-center gap-1">

                        Reorder Level

                        <span class="relative inline-flex items-center group">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] cursor-pointer"></i>

                            <span
                                class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-[11px] p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Defines the stock quantity at which the organization should consider replenishing or purchasing additional units to avoid reaching insufficient inventory levels.
                            </span>

                        </span>

                    </span>

                </label>


                <input
                    type="number"
                    name="reorder_level"
                    min="0"
                    value="{{ $product->reorder_level ?? 0 }}"
                    {{ ($isViewOnly || $isNonInventory) ? 'disabled' : '' }}
                    class="w-full border border-slate-300 rounded-lg p-2.5 {{ $readOnlyState }} outline-none focus:border-blue-500"
                >

            </div>


            {{-- MINIMUM STOCK --}}

            <div>

                <label class="block font-semibold text-slate-700 mb-1">

                    <span class="inline-flex items-center gap-1">

                        Minimum Stock

                        <span class="relative inline-flex items-center group">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] cursor-pointer"></i>

                            <span
                                class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-[11px] p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Defines the lowest desired quantity of stock that should normally be maintained to support operational continuity and reduce the risk of stock shortages.
                            </span>

                        </span>

                    </span>

                </label>


                <input
                    type="number"
                    name="minimum_stock"
                    min="0"
                    value="{{ $product->minimum_stock ?? 0 }}"
                    {{ ($isViewOnly || $isNonInventory) ? 'disabled' : '' }}
                    class="w-full border border-slate-300 rounded-lg p-2.5 {{ $readOnlyState }} outline-none focus:border-blue-500"
                >

            </div>


            {{-- MAXIMUM STOCK --}}

            <div>

                <label class="block font-semibold text-slate-700 mb-1">

                    <span class="inline-flex items-center gap-1">

                        Maximum Stock

                        <span class="relative inline-flex items-center group">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] cursor-pointer"></i>

                            <span
                                class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-[11px] p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Defines the highest quantity of stock that should normally be maintained to prevent unnecessary overstocking, storage pressure, or excess inventory.
                            </span>

                        </span>

                    </span>

                </label>


                <input
                    type="number"
                    name="maximum_stock"
                    min="0"
                    value="{{ $product->maximum_stock ?? 0 }}"
                    {{ ($isViewOnly || $isNonInventory) ? 'disabled' : '' }}
                    class="w-full border border-slate-300 rounded-lg p-2.5 {{ $readOnlyState }} outline-none focus:border-blue-500"
                >

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- STORAGE & LOCATION --}}
        {{-- ===================================================== --}}

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">


            {{-- WAREHOUSE LOCATION --}}

            <div>

                <label class="block font-semibold text-slate-700 mb-1">

                    <span class="inline-flex items-center gap-1">

                        Warehouse Location *

                        <span class="relative inline-flex items-center group">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] cursor-pointer"></i>

                            <span
                                class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-[11px] p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Identifies the primary warehouse, stock area, or inventory classification where this product's available stock is assigned or maintained.
                            </span>

                        </span>

                    </span>

                </label>


                <select
                    name="warehouse_location"
                    required
                    {{ ($isViewOnly || $isNonInventory) ? 'disabled' : '' }}
                    class="w-full border border-slate-300 rounded-lg p-2.5 {{ $readOnlyState }} outline-none focus:border-blue-500"
                >

                    <option
                        value=""
                        disabled
                        {{ empty($product->warehouse_location) ? 'selected' : '' }}
                    >
                        Select Warehouse Location
                    </option>

                    <option
                        value="Main Warehouse"
                        {{ ($product->warehouse_location ?? '') === 'Main Warehouse' ? 'selected' : '' }}
                    >
                        Main Warehouse
                    </option>

                    <option
                        value="Branch Office Stock"
                        {{ ($product->warehouse_location ?? '') === 'Branch Office Stock' ? 'selected' : '' }}
                    >
                        Branch Office Stock
                    </option>

                    <option
                        value="Reserved Stock"
                        {{ ($product->warehouse_location ?? '') === 'Reserved Stock' ? 'selected' : '' }}
                    >
                        Reserved Stock
                    </option>

                </select>

            </div>


            {{-- STORAGE LOCATION --}}

            <div>

                <label class="block font-semibold text-slate-700 mb-1">

                    <span class="inline-flex items-center gap-1">

                        Storage Location

                        <span class="relative inline-flex items-center group">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] cursor-pointer"></i>

                            <span
                                class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-[11px] p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Specifies the more precise physical storage position of the product within the selected warehouse or stock area, such as a rack, shelf, bin, aisle, or designated storage section.
                            </span>

                        </span>

                    </span>

                </label>


                <input
                    type="text"
                    name="storage_location"
                    value="{{ $product->storage_location ?? '' }}"
                    placeholder="e.g. Rack A-12"
                    {{ ($isViewOnly || $isNonInventory) ? 'disabled' : '' }}
                    class="w-full border border-slate-300 rounded-lg p-2.5 {{ $readOnlyState }} outline-none focus:border-blue-500"
                >

            </div>


            {{-- STOCK STATUS --}}

            <div>

                <label class="block font-semibold text-slate-700 mb-1">

                    <span class="inline-flex items-center gap-1">

                        Stock Status *

                        <span class="relative inline-flex items-center group">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] cursor-pointer"></i>

                            <span
                                class="absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-[11px] p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Indicates the current inventory condition of the product based on its available stock, such as adequately stocked, approaching shortage, completely unavailable, or not applicable for inventory tracking.
                            </span>

                        </span>

                    </span>

                </label>


                <select
                    name="stock_status"
                    required
                    {{ ($isViewOnly || $isNonInventory) ? 'disabled' : '' }}
                    class="w-full border border-slate-300 rounded-lg p-2.5 {{ $readOnlyState }} outline-none focus:border-blue-500"
                >

                    <option
                        value=""
                        disabled
                        {{ empty($product->stock_status) ? 'selected' : '' }}
                    >
                        Select Stock Status
                    </option>

                    <option
                        value="In Stock"
                        {{ ($product->stock_status ?? '') === 'In Stock' ? 'selected' : '' }}
                    >
                        In Stock
                    </option>

                    <option
                        value="Low Stock"
                        {{ ($product->stock_status ?? '') === 'Low Stock' ? 'selected' : '' }}
                    >
                        Low Stock
                    </option>

                    <option
                        value="Out of Stock"
                        {{ ($product->stock_status ?? '') === 'Out of Stock' ? 'selected' : '' }}
                    >
                        Out of Stock
                    </option>

                    <option
                        value="Not Applicable"
                        {{ ($product->stock_status ?? '') === 'Not Applicable' ? 'selected' : '' }}
                    >
                        Not Applicable
                    </option>

                </select>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- NAVIGATION --}}
        {{-- ===================================================== --}}

        <div class="flex justify-between items-center pt-4 border-t border-slate-100">

            <button
                type="button"
                onclick="switchWorkspaceTab(4)"
                class="text-xs font-semibold text-slate-500 hover:text-blue-600 cursor-pointer"
            >
                ← Back to Requirements (o Catalog)
            </button>


            <button
                type="submit"
                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm transition cursor-pointer"
            >
                Save &amp; Continue to Requirements

                <i class="fa-solid fa-arrow-right ml-1"></i>
            </button>

        </div>

    </form>

</div>

  {{-- =========================================================
    STEP 4 — REQUIREMENTS
========================================================== --}}
<div id="workspace-panel-4"
     class="workspace-panel bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-5 hidden">

    <div class="flex items-center justify-between border-b border-slate-100 pb-3">

        <div>

            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                Structured Requirements &amp; Operational Checklist
            </h3>

            <p class="text-xs text-slate-500 mt-0.5">
                Configured requirements convert directly into operational engagement checklists without retyping.
            </p>

        </div>


        <div class="flex items-center space-x-2">

            {{-- =====================================================
                ADD SELECTED TO TEMPLATE LIBRARY
            ====================================================== --}}

            <button
                type="button"
                id="addToLibraryBtn"
                onclick="addSelectedToTemplateLibrary()"
                class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-sm transition cursor-pointer hidden"
            >

                <i class="fa-solid fa-plus mr-1"></i>

                Add to Template Library

                (
                <span id="selectedCount">0</span>
                )

            </button>


            {{-- =====================================================
                TEMPLATE LIBRARY
            ====================================================== --}}

            <button
                type="button"
                id="templateLibraryBtn"
                onclick="openTemplateLibraryModal()"
                class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-semibold shadow-sm cursor-pointer flex items-center space-x-1.5"
            >

                <i class="fa-solid fa-book-bookmark text-slate-400"></i>

                <span>
                    Template Library
                    (
                    <span id="templateCount">
                        {{ $templates->count() }}
                    </span>
                    )
                </span>

            </button>


            {{-- =====================================================
                ADD REQUIREMENT
            ====================================================== --}}

            <button
                type="button"
                onclick="openRequirementModal()"
                class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-sm transition cursor-pointer"
            >

                <i class="fa-solid fa-plus mr-1"></i>

                Add Requirement

            </button>

        </div>

    </div>


 {{-- =====================================================
                edit REQUIREMENT
            ====================================================== --}}

<!-- Edit Requirement Modal -->
<div id="editRequirementModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden border border-slate-100">
        
        <!-- Modal Header -->
        <div class="flex justify-between items-center px-6 py-4 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">Edit Requirement</h3>
            <button type="button" onclick="closeEditRequirementModal()" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form id="editRequirementForm" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            @method('PUT')

            <!-- Requirement Name (Dapat 'name' ang pangalan para sa controller) -->
            <div>
                <label class="block font-bold text-slate-800 mb-1">Requirement Name *</label>
                <input type="text" id="edit_requirement_name" name="name" required class="w-full border border-slate-300 rounded-lg p-2.5 outline-none focus:border-blue-500">
            </div>

            <!-- Client Type & Source -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-800 mb-1">Applies to Client Type</label>
                    <select id="edit_client_type" name="client_type" onchange="checkEditClientTypeOther(this)" class="w-full border border-slate-300 rounded-lg p-2.5 outline-none focus:border-blue-500 bg-white">
                        <option value="All Client Types">All Client Types</option>
                        <option value="Individual">Individual</option>
                        <option value="Sole Proprietor">Sole Proprietor</option>
                        <option value="Corporation">Corporation</option>
                        <option value="Partnership">Partnership</option>
                        <option value="Cooperative">Cooperative</option>
                        <option value="Other">Other</option>
                    </select>
                    <input 
        type="text" 
        id="edit_other_client_type" 
        name="other_client_type" 
        placeholder="Please specify client type..." 
        class="w-full mt-2 border border-slate-300 rounded-lg p-2.5 outline-none focus:border-blue-500 hidden"
    >
</div>


                <div>
                    <label class="block font-bold text-slate-800 mb-1">Requirement Source</label>
                    <!-- (Dapat 'source' ang pangalan para sa controller) -->
                    <select id="edit_requirement_source" name="source" class="w-full border border-slate-300 rounded-lg p-2.5 outline-none focus:border-blue-500 bg-white">
                        <option value="Client-supplied">Client-supplied</option>
                        <option value="Internal">Internally prepared/Drafted</option>
                        <option value="Government">Government Portal</option>
                    </select>
                </div>
            </div>

            <!-- Evidence Needed & Mandatory Level -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-800 mb-1">Evidence Type Needed</label>
                    <select id="edit_evidence_needed" name="evidence_type" class="w-full border border-slate-300 rounded-lg p-2.5 outline-none focus:border-blue-500 bg-white">
                        <option value="File Upload / Soft Copy">File Upload / Soft Copy</option>
                        <option value="Hard Copy">Physical Original/Hard Copy</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-800 mb-1">Mandatory Level</label>
                    <!-- (Dapat 'is_mandatory' o boolean ang salo ng controller, pero lagyan natin ng angkop na value) -->
                    <select id="edit_status" name="is_mandatory" class="w-full border border-slate-300 rounded-lg p-2.5 outline-none focus:border-blue-500 bg-white">
                        <option value="1">Mandatory (Required)</option>
                        <option value="0">Optional</option>
                    </select>
                </div>
            </div>

            <!-- Validity (Dapat 'validity_expiration' para tumugma sa controller mo) -->
            <div>
                <label class="block font-bold text-slate-800 mb-1">Validity / Expiration Rule</label>
                <input type="text" id="edit_validity" name="validity_expiration" placeholder="e.g. 3 months" class="w-full border border-slate-300 rounded-lg p-2.5 outline-none focus:border-blue-500">
            </div>

            <!-- Instructions -->
            <div>
                <label class="block font-bold text-slate-800 mb-1">Instructions / Handling Notes</label>
                <textarea id="edit_instructions" name="instructions" rows="3" placeholder="Special guidance..." class="w-full border border-slate-300 rounded-lg p-2.5 outline-none focus:border-blue-500 resize-none"></textarea>
            </div>

            <!-- Footer Buttons -->
            <div class="flex justify-end items-center space-x-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeEditRequirementModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-sm cursor-pointer">
                    Update Requirement
                </button>
            </div>
        </form>

    </div>
</div>

<script>
function openEditRequirementModal(requirement) {
    const form = document.getElementById('editRequirementForm');
    form.action = `/products/{{ $product->id }}/requirements/${requirement.id}`;

    document.getElementById('edit_requirement_name').value = requirement.requirement_name || requirement.name || '';
    
    const clientTypeSelect = document.getElementById('edit_client_type');
    const otherInput = document.getElementById('edit_other_client_type');
    
    const standardTypes = ['All Client Types', 'Individual', 'Sole Proprietor', 'Corporation', 'Partnership', 'Cooperative'];
    if (standardTypes.includes(requirement.client_type)) {
        clientTypeSelect.value = requirement.client_type;
        otherInput.classList.add('hidden');
        otherInput.value = '';
    } else if (requirement.client_type) {
        clientTypeSelect.value = 'Other';
        otherInput.classList.remove('hidden');
        otherInput.value = requirement.client_type;
    } else {
        clientTypeSelect.value = 'All Client Types';
        otherInput.classList.add('hidden');
    }

document.getElementById('edit_requirement_source').value = requirement.source || 'Client-supplied';
    document.getElementById('edit_evidence_needed').value = requirement.evidence_type || 'File Upload / Soft Copy';
    document.getElementById('edit_status').value = requirement.status !== undefined ? requirement.status : '1';
    document.getElementById('edit_validity').value = requirement.validity_expiration || '';
    document.getElementById('edit_instructions').value = requirement.instructions || '';

    document.getElementById('editRequirementModal').classList.remove('hidden');
}

function closeEditRequirementModal() {
    document.getElementById('editRequirementModal').classList.add('hidden');
}

function checkEditClientTypeOther(select) {
    const otherInput = document.getElementById('edit_other_client_type');
    if (select.value === 'Other') {
        otherInput.classList.remove('hidden');
        otherInput.required = true;
    } else {
        otherInput.classList.add('hidden');
        otherInput.required = false;
        otherInput.value = '';


        
    }
}
</script>

    {{-- =========================================================
        REQUIREMENTS TABLE
    ========================================================== --}}

    <div class="overflow-x-auto border border-slate-200 rounded-lg">

        <table class="w-full text-left text-xs">

            <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">

                <tr>

                    <th class="py-3 px-3 w-10 text-center">

                        <input
                            type="checkbox"
                            id="selectAllRequirements"
                            class="rounded border-slate-300"
                            onchange="toggleAllRequirements(this)"
                        >

                    </th>


                    <th class="py-3 px-3 w-12">
                        #
                    </th>


                    <th class="py-3 px-3">
                        Requirement Name
                    </th>


                    <th class="py-3 px-3">
                        Client Type
                    </th>


                    <th class="py-3 px-3">
                        Status
                    </th>


                    <th class="py-3 px-3">
                        Source
                    </th>


                    <th class="py-3 px-3">
                        Evidence Needed
                    </th>


                    <th class="py-3 px-3">
                        Validity
                    </th>


                    <th class="py-3 px-3 text-right">
                        Action
                    </th>

                </tr>

            </thead>

           <tbody id="requirementTableBody" class="divide-y divide-slate-100">
    @foreach($product->requirements as $index => $requirement)
                    <tr>
                        {{-- Checkbox --}}
                        <td class="py-3 px-3 text-center">
                            <input type="checkbox" class="rounded border-slate-300 requirement-checkbox" value="{{ $requirement->id }}">
                        </td>

                        {{-- Index --}}
                        <td class="py-3 px-3 font-semibold text-slate-700">
                            {{ $index + 1 }}
                        </td>

                        {{-- Requirement Name (Clickable - may dalang data papuntang modal) --}}
                        <td class="py-3 px-3 font-medium text-slate-800">
                            <button
                                type="button"
                                onclick="openEditRequirementModal({
                                    id: {{ $requirement->id }},
                                    requirement_name: '{{ addslashes($requirement->requirement_name ?? $requirement->name ?? '') }}',
                                    client_type: '{{ addslashes($requirement->client_type ?? '') }}',
                                    requirement_source: '{{ addslashes($requirement->requirement_source ?? '') }}',
                                    evidence_needed: '{{ addslashes($requirement->evidence_needed ?? '') }}',
                                    status: '{{ addslashes($requirement->status ?? '') }}',
                                   validity_expiration: '{{ addslashes($requirement->validity_expiration ?? '') }}',
                                    instructions: '{{ addslashes($requirement->instructions ?? '') }}'
                                })"
                                class="text-blue-600 hover:underline text-left cursor-pointer"
                            >
                                {{ $requirement->requirement_name ?? $requirement->name }}
                            </button>
                        </td>

                        {{-- Client Type --}}
                        <td class="py-3 px-3 text-slate-600">
                            <span class="px-2 py-0.5 bg-slate-100 rounded text-[11px]">{{ $requirement->client_type }}</span>
                        </td>

                        {{-- Status --}}
                        <td class="py-3 px-3 font-semibold text-slate-800">
                            {{ $requirement->status }}
                        </td>

                        {{-- Source --}}
<td class="py-3 px-3 text-slate-600">
    {{ $requirement->source ?? 'N/A' }}
</td>

                        {{-- Evidence Needed --}}
<td class="py-3 px-3 text-slate-600">
    {{ $requirement->evidence_type ?? 'N/A' }}
</td>


{{-- Validity --}}
<td class="py-3 px-3 text-slate-600">
    {{ $requirement->validity_expiration ?? 'N/A' }}
</td>

                        {{-- Action (Edit & Delete) --}}
                        <td class="py-3 px-3 text-right space-x-3">
                            <button
                                type="button"
                                onclick="openEditRequirementModal({
                                    id: {{ $requirement->id }},
                                    requirement_name: '{{ addslashes($requirement->requirement_name ?? $requirement->name ?? '') }}',
                                    client_type: '{{ addslashes($requirement->client_type ?? '') }}',
                                    source: '{{ addslashes($requirement->source ?? '') }}',
                                    evidence_needed: '{{ addslashes($requirement->evidence_type ?? '') }}',
                                    status: '{{ addslashes($requirement->status ?? '') }}',
                                    validity: '{{ addslashes($requirement->validity ?? '') }}',
                                    instructions: '{{ addslashes($requirement->instructions ?? '') }}'
                                })"
                                class="text-blue-600 hover:underline font-semibold cursor-pointer"
                            >
                                Edit
                            </button>

                            <form
                                action="{{ route('products.requirements.destroy', [$product->id, $requirement->id]) }}"
                                method="POST"
                                class="inline"
                                onsubmit="return confirm('Are you sure you want to delete this requirement?');"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="text-red-600 hover:underline font-semibold cursor-pointer"
                                >
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>

        </table>

    </div>

           


    {{-- =========================================================
        FOOTER
    ========================================================== --}}

    <div class="flex justify-between items-center pt-4 border-t border-slate-100">

        <button
            type="button"
            onclick="switchWorkspaceTab(3)"
            class="text-xs font-semibold text-slate-500 hover:text-blue-600 cursor-pointer"
        >

            &larr; Back to Inventory Specs

        </button>


        <button
            type="button"
            onclick="switchWorkspaceTab(5)"
            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm transition cursor-pointer"
        >

            Save &amp; Continue to Workflow

            <i class="fa-solid fa-arrow-right ml-1"></i>

        </button>

    </div>

</div>


{{-- =========================================================
    MODAL: REQUIREMENTS TEMPLATE LIBRARY
========================================================== --}}

<div
    id="templateLibraryModal"
    class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-[99999] flex items-center justify-center hidden"
>

    <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden border border-slate-200 m-4 relative z-[100000]"
    >

        {{-- HEADER --}}

        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50">

            <div>

                <h3 class="text-sm font-bold text-slate-900">
                    Requirements Template Library
                </h3>

                <p class="text-[11px] text-slate-500">
                    Manage structural templates, apply them directly, or remove unused items.
                </p>

            </div>


            <button
                type="button"
                onclick="closeTemplateLibraryModal()"
                class="text-slate-400 hover:text-slate-600 text-sm cursor-pointer"
            >

                <i class="fa-solid fa-xmark text-base"></i>

            </button>

        </div>


        {{-- TEMPLATE LIST --}}

        <div
            id="templateLibraryList"
            class="p-6 space-y-3 text-xs max-h-[70vh] overflow-y-auto bg-slate-50/50"
        >

            @forelse($templates ?? [] as $template)

                <div
                    class="bg-white border border-slate-200 p-4 rounded-xl shadow-sm flex items-center justify-between template-card"
                >

                    <div class="space-y-1">

                        <h4 class="font-bold text-slate-900 template-name">
                            {{ $template->name }}
                        </h4>

                        <p class="text-[11px] text-slate-500">

                            Type:

                            <span class="font-semibold text-slate-700">
                                {{ $template->client_type ?? 'All Client Types' }}
                            </span>

                            &bull;

                            Status:

                            <span class="text-emerald-600 font-bold">
                                ACTIVE
                            </span>

                        </p>

                    </div>


                    <div class="flex items-center space-x-2">

                        <button
                            type="button"
                            onclick="applyLibraryTemplate(
                                this,
                                @js($template->name),
                                @js($template->client_type ?? 'All Client Types'),
                                'Mandatory (Required)',
                                'Client-supplied',
                                'File Upload / Soft Copy',
                                'N/A'
                            )"
                            class="px-3 py-1.5 bg-slate-100 hover:bg-blue-600 hover:text-white text-slate-700 rounded-lg font-semibold transition cursor-pointer border border-slate-200"
                        >

                            Apply

                        </button>


                        <button
                            type="button"
                            onclick="deleteLibraryTemplate(this)"
                            class="px-3 py-1.5 bg-red-50 hover:bg-red-600 hover:text-white text-red-600 rounded-lg font-semibold transition cursor-pointer border border-red-100"
                        >

                            Delete

                        </button>

                    </div>

                </div>

            @empty

                <div
                    id="emptyTemplateLibraryMessage"
                    class="text-center py-8 text-slate-400"
                >

                    <i class="fa-solid fa-book-bookmark text-2xl mb-2 text-slate-300"></i>

                    <div>
                        No templates found in the library.
                    </div>

                </div>

            @endforelse

        </div>


        {{-- FOOTER --}}

        <div class="flex items-center justify-between px-6 py-3 border-t border-slate-100 bg-slate-50">

            <span class="text-[11px] text-slate-500">
                Tip: You can apply a template even if the others have run out.
            </span>


            <button
                type="button"
                onclick="closeTemplateLibraryModal()"
                class="px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg text-xs font-semibold hover:bg-slate-50 cursor-pointer"
            >

                Close

            </button>

        </div>

    </div>

</div>


{{-- =========================================================
    ADD PRODUCT REQUIREMENT MODAL
========================================================== --}}

<div
    id="requirementModal"
    class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-[99999] flex items-center justify-center hidden"
>

    <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden border border-slate-200 m-4 relative z-[100000]"
    >

        {{-- HEADER --}}

        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50">

            <h3 class="text-sm font-bold text-slate-900">
                Add Product Requirement
            </h3>


            <button
                type="button"
                onclick="closeRequirementModal()"
                class="text-slate-400 hover:text-slate-600 text-sm cursor-pointer"
            >

                <i class="fa-solid fa-xmark text-base"></i>

            </button>

        </div>


        {{-- BODY --}}

        <div class="p-6 space-y-4 text-xs max-h-[75vh] overflow-y-auto">

            {{-- TEMPLATE --}}

            <div>

                <label class="block font-semibold text-slate-700 mb-1">
                    Select from Existing / Global Templates
                </label>


                <select
                    id="templateDropdown"
                    onchange="checkExistingTemplate(this)"
                    class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500 text-slate-600"
                >

                    <option value="">
                        -- Choose from existing templates or type custom below --
                    </option>


                    @foreach($templates ?? [] as $template)

                        <option value="{{ $template->name }}">
                            {{ $template->name }}
                        </option>

                    @endforeach

                </select>


                <p class="text-[10px] text-slate-400 mt-1">
                    Automatically saved as a reusable requirement for future use.
                </p>

            </div>


            {{-- REQUIREMENT NAME --}}

            <div>

                <label class="block font-semibold text-slate-700 mb-1">

                    Requirement Name (Or type custom text)

                    <span class="text-red-500">*</span>

                </label>


                <input
                    type="text"
                    id="modalReqName"
                    oninput="checkRequirementNameInput(this)"
                    placeholder="e.g. Type custom requirement here if not in templates..."
                    class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                >


                <div
                    id="existingNotice"
                    class="mt-1.5 text-[11px] font-semibold text-amber-600 hidden flex items-center space-x-1"
                >

                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>

                    <span>
                        Note: This requirement already exists in your Template Library.
                    </span>

                </div>

            </div>


            {{-- CLIENT / SOURCE --}}

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">
                        Applies to Client Type
                    </label>


                    <select
                        id="modalClientType"
                        onchange="checkClientTypeOther(this)"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                    >

                        <option value="All Client Types">
                            All Client Types
                        </option>

                        <option value="Individual">
                            Individual
                        </option>

                        <option value="Sole Proprietor">
                            Sole Proprietor
                        </option>

                        <option value="Corporation">
                            Corporation
                        </option>

                        <option value="Partnership">
                            Partnership
                        </option>

                        <option value="Cooperative">
                            Cooperative
                        </option>

                        <option value="Other">
                            Other
                        </option>

                    </select>


                    <div
                        id="otherClientTypeWrapper"
                        class="mt-2 hidden"
                    >

                        <input
                            type="text"
                            id="modalOtherClientType"
                            placeholder="Specify custom client type..."
                            class="w-full border border-slate-300 rounded-lg p-2 bg-white outline-none focus:border-blue-500"
                        >

                    </div>

                </div>


                <div>

                    <label class="block font-semibold text-slate-700 mb-1">
                        Requirement Source
                    </label>


                    <select
                        id="modalSource"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                    >

                        <option value="Client-supplied">
                            Client-supplied
                        </option>

                        <option value="Internal">
                            Internal
                        </option>

                        <option value="Government">
                            Government
                        </option>

                    </select>

                </div>

            </div>


            {{-- EVIDENCE / MANDATORY --}}

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">
                        Evidence Type Needed
                    </label>


                    <select
                        id="modalEvidence"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                    >

                        <option value="File Upload / Soft Copy">
                            File Upload / Soft Copy
                        </option>

                        <option value="Physical Copy">
                            Physical Copy
                        </option>

                    </select>

                </div>


                <div>

                    <label class="block font-semibold text-slate-700 mb-1">
                        Mandatory Level
                    </label>


                    <select
                        id="modalMandatory"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                    >

                        <option value="Mandatory (Required)">
                            Mandatory (Required)
                        </option>

                        <option value="Optional">
                            Optional
                        </option>

                    </select>

                </div>

            </div>


            {{-- VALIDITY --}}

            <div>

                <label class="block font-semibold text-slate-700 mb-1">
                    Validity / Expiration Rule
                </label>


                <input
                    type="text"
                    id="modalValidity"
                    placeholder="e.g. Within last 6 months"
                    class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                >

            </div>


            {{-- INSTRUCTIONS --}}

            <div>

                <label class="block font-semibold text-slate-700 mb-1">
                    Instructions / Handling Notes
                </label>


                <textarea
                    id="modalNotes"
                    rows="3"
                    placeholder="Special guidance for associates when validating this requirement..."
                    class="w-full border border-slate-300 rounded-lg p-3 bg-white outline-none focus:border-blue-500"
                ></textarea>

            </div>

        </div>


        {{-- FOOTER --}}

        <div class="flex items-center justify-end px-6 py-3 border-t border-slate-100 bg-slate-50 space-x-2">

            <button
                type="button"
                onclick="closeRequirementModal()"
                class="px-4 py-2 border border-slate-300 bg-white text-slate-700 rounded-lg text-xs font-semibold hover:bg-slate-50 cursor-pointer"
            >

                Cancel

            </button>


            <button
                type="button"
                onclick="saveRequirementItem()"
                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-sm cursor-pointer"
            >

                Save Requirement

            </button>

        </div>

    </div>

</div>


<script>

    // =========================================================
    // OPEN TEMPLATE LIBRARY
    // =========================================================

    function openTemplateLibraryModal() {

        document
            .getElementById('templateLibraryModal')
            .classList
            .remove('hidden');

    }


    // =========================================================
    // CLOSE TEMPLATE LIBRARY
    // =========================================================

    function closeTemplateLibraryModal() {

        document
            .getElementById('templateLibraryModal')
            .classList
            .add('hidden');

    }


    // =========================================================
    // APPLY TEMPLATE
    // =========================================================

    function applyLibraryTemplate(
        buttonElement,
        name,
        clientType,
        mandatory,
        source,
        evidence,
        validity
    ) {

        addRequirementRow(
            name,
            clientType,
            mandatory,
            source,
            evidence,
            validity
        );

        closeTemplateLibraryModal();

    }


    // =========================================================
    // DELETE TEMPLATE FROM CURRENT LIBRARY DISPLAY
    // =========================================================

    function deleteLibraryTemplate(buttonElement) {

        const card =
            buttonElement.closest('.template-card');


        if (!card) {
            return;
        }


        card.remove();


        updateTemplateCount();


        const list =
            document.getElementById('templateLibraryList');


        const remainingCards =
            list.querySelectorAll('.template-card').length;


        if (remainingCards === 0) {

            if (!document.getElementById('emptyTemplateLibraryMessage')) {

                const emptyMessage =
                    document.createElement('div');


                emptyMessage.id =
                    'emptyTemplateLibraryMessage';


                emptyMessage.className =
                    'text-center py-8 text-slate-400';


                emptyMessage.innerHTML = `
                    <i class="fa-solid fa-book-bookmark text-2xl mb-2 text-slate-300"></i>
                    <div>No templates found in the library.</div>
                `;


                list.appendChild(emptyMessage);

            }

        }

    }


    // =========================================================
    // UPDATE TEMPLATE COUNT
    // =========================================================

    function updateTemplateCount() {

        const list =
            document.getElementById('templateLibraryList');


        const count =
            list.querySelectorAll('.template-card').length;


        const badge =
            document.getElementById('templateCount');


        if (badge) {

            badge.textContent =
                count;

        }

    }


    // =========================================================
    // ADD SELECTED REQUIREMENTS TO TEMPLATE LIBRARY
    // =========================================================

    function addSelectedToTemplateLibrary() {

        const tbody =
            document.getElementById('requirementTableBody');


        if (!tbody) {
            return;
        }


        const selected =
            tbody.querySelectorAll(
                'input.requirement-checkbox:checked'
            );


        if (selected.length === 0) {
            return;
        }


        const templateList =
            document.getElementById('templateLibraryList');


        const emptyMessage =
            document.getElementById('emptyTemplateLibraryMessage');


        if (emptyMessage) {
            emptyMessage.remove();
        }


        selected.forEach(function (checkbox) {

            const row =
                checkbox.closest('tr');


            if (!row) {
                return;
            }


            const cells =
                row.querySelectorAll('td');


            const requirementName =
                cells[2]
                    ? cells[2].innerText.trim()
                    : '';


            const clientType =
                cells[3]
                    ? cells[3].innerText.trim()
                    : 'All Client Types';


            const mandatory =
                cells[4]
                    ? cells[4].innerText.trim()
                    : 'Mandatory (Required)';


            const source =
                cells[5]
                    ? cells[5].innerText.trim()
                    : 'Client-supplied';


            const evidence =
                cells[6]
                    ? cells[6].innerText.trim()
                    : 'File Upload / Soft Copy';


            const validity =
                cells[7]
                    ? cells[7].innerText.trim()
                    : 'N/A';


            if (!requirementName) {
                return;
            }


            const card =
                document.createElement('div');


            card.className =
                'bg-white border border-slate-200 p-4 rounded-xl shadow-sm flex items-center justify-between template-card';


            card.innerHTML = `

                <div class="space-y-1">

                    <h4 class="font-bold text-slate-900 template-name">
                        ${escapeTemplateHtml(requirementName)}
                    </h4>

                    <p class="text-[11px] text-slate-500">

                        Type:

                        <span class="font-semibold text-slate-700">
                            ${escapeTemplateHtml(clientType)}
                        </span>

                        &bull;

                        Status:

                        <span class="text-emerald-600 font-bold">
                            ACTIVE
                        </span>

                    </p>

                </div>


                <div class="flex items-center space-x-2">

                    <button
                        type="button"
                        class="template-apply-btn px-3 py-1.5 bg-slate-100 hover:bg-blue-600 hover:text-white text-slate-700 rounded-lg font-semibold transition cursor-pointer border border-slate-200"
                    >
                        Apply
                    </button>


                    <button
                        type="button"
                        onclick="deleteLibraryTemplate(this)"
                        class="px-3 py-1.5 bg-red-50 hover:bg-red-600 hover:text-white text-red-600 rounded-lg font-semibold transition cursor-pointer border border-red-100"
                    >
                        Delete
                    </button>

                </div>

            `;


            const applyButton =
                card.querySelector('.template-apply-btn');


            applyButton.addEventListener(
                'click',
                function () {

                    applyLibraryTemplate(
                        this,
                        requirementName,
                        clientType,
                        mandatory,
                        source,
                        evidence,
                        validity
                    );

                }
            );


            templateList.appendChild(card);

        });


        updateTemplateCount();


        selected.forEach(function (checkbox) {

            checkbox.checked = false;

        });


        const selectAll =
            document.getElementById(
                'selectAllRequirements'
            );


        if (selectAll) {
            selectAll.checked = false;
        }


        updateSelectedRequirements();


        openTemplateLibraryModal();

    }


    // =========================================================
    // ESCAPE HTML
    // =========================================================

    function escapeTemplateHtml(value) {

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

    }


    // =========================================================
    // OPEN REQUIREMENT MODAL
    // =========================================================

    function openRequirementModal() {

        document
            .getElementById('requirementModal')
            .classList
            .remove('hidden');

    }


    // =========================================================
    // CLOSE REQUIREMENT MODAL
    // =========================================================

    function closeRequirementModal() {

        document
            .getElementById('requirementModal')
            .classList
            .add('hidden');

    }


    // =========================================================
    // EXISTING TEMPLATE DROPDOWN
    // =========================================================

    function checkExistingTemplate(selectElement) {

        const reqNameInput =
            document.getElementById('modalReqName');


        const notice =
            document.getElementById('existingNotice');


        if (selectElement.value) {

            reqNameInput.value =
                selectElement.value;


            notice.classList.remove('hidden');

        } else {

            reqNameInput.value =
                '';


            notice.classList.add('hidden');

        }

    }


    // =========================================================
    // CHECK REQUIREMENT NAME
    // =========================================================

    function checkRequirementNameInput(inputElement) {

        const templateDropdown =
            document.getElementById('templateDropdown');


        const notice =
            document.getElementById('existingNotice');


        const typedValue =
            inputElement.value
                .trim()
                .toLowerCase();


        const matchFound =
            Array.from(templateDropdown.options).some(
                opt =>
                    opt.value &&
                    opt.value.toLowerCase() === typedValue
            );


        if (matchFound && typedValue !== '') {

            notice.classList.remove('hidden');

        } else {

            notice.classList.add('hidden');

        }

    }


    // =========================================================
    // OTHER CLIENT TYPE
    // =========================================================

    function checkClientTypeOther(selectElement) {

        const otherWrapper =
            document.getElementById(
                'otherClientTypeWrapper'
            );


        if (selectElement.value === 'Other') {

            otherWrapper.classList.remove('hidden');

        } else {

            otherWrapper.classList.add('hidden');


            document.getElementById(
                'modalOtherClientType'
            ).value = '';

        }

    }


    // =========================================================
    // SAVE REQUIREMENT ITEM
    // =========================================================

    function saveRequirementItem() {

        const name =
            document.getElementById('modalReqName')
                .value
                .trim();


        if (!name) {

            alert('Please enter a Requirement Name.');

            return;

        }


        let clientType =
            document.getElementById(
                'modalClientType'
            ).value;


        if (clientType === 'Other') {

            const customOther =
                document.getElementById(
                    'modalOtherClientType'
                ).value.trim();


            clientType =
                customOther
                    ? customOther
                    : 'Other';

        }


        const source =
            document.getElementById(
                'modalSource'
            ).value;


        const evidence =
            document.getElementById(
                'modalEvidence'
            ).value;


        const mandatory =
            document.getElementById(
                'modalMandatory'
            ).value;


        const validity =
            document.getElementById(
                'modalValidity'
            ).value;


        const instructions =
            document.getElementById(
                'modalNotes'
            ).value.trim();


        // =====================================================
        // CONVERT UI VALUES TO DATABASE BOOLEAN VALUES
        // =====================================================

        const fileRequired =
            /file|upload|soft/i.test(evidence)
                ? '1'
                : '0';


        const isMandatory =
            /mandatory|required/i.test(mandatory)
                ? '1'
                : '0';


        // =====================================================
        // CREATE REAL LARAVEL POST FORM
        // =====================================================

        const form =
            document.createElement('form');


        form.method =
            'POST';


        form.action =
            "{{ route('products.requirements.store', $product->id) }}";


        // =====================================================
        // CSRF
        // =====================================================

        const csrf =
            document.createElement('input');


        csrf.type =
            'hidden';


        csrf.name =
            '_token';


        csrf.value =
            "{{ csrf_token() }}";


        form.appendChild(csrf);


        // =====================================================
        // MODE
        // =====================================================

        const mode =
            document.createElement('input');


        mode.type =
            'hidden';


        mode.name =
            'mode';


        mode.value =
            "{{ $mode }}";


        form.appendChild(mode);


        // =====================================================
        // REQUIREMENT TYPE
        // =====================================================

        const requirementType =
            document.createElement('input');


        requirementType.type =
            'hidden';


        requirementType.name =
            'requirement_type';


        requirementType.value =
            'product_specific';


        form.appendChild(requirementType);


        // =====================================================
        // NAME
        // =====================================================

        const requirementName =
            document.createElement('input');


        requirementName.type =
            'hidden';


        requirementName.name =
            'name';


        requirementName.value =
            name;


        form.appendChild(requirementName);


        // =====================================================
        // CLIENT TYPE
        // =====================================================

        const clientTypeInput =
            document.createElement('input');


        clientTypeInput.type =
            'hidden';


        clientTypeInput.name =
            'client_type';


        clientTypeInput.value =
            clientType;


        form.appendChild(clientTypeInput);


        // =====================================================
        // SOURCE
        // =====================================================

        const sourceInput =
            document.createElement('input');


        sourceInput.type =
            'hidden';


        sourceInput.name =
            'source';


        sourceInput.value =
            source;


        form.appendChild(sourceInput);


        // =====================================================
        // FILE REQUIRED
        // =====================================================

        const fileRequiredInput =
            document.createElement('input');


        fileRequiredInput.type =
            'hidden';


        fileRequiredInput.name =
            'file_required';


        fileRequiredInput.value =
            fileRequired;


        form.appendChild(fileRequiredInput);


        // =====================================================
        // MANDATORY
        // =====================================================

        const mandatoryInput =
            document.createElement('input');


        mandatoryInput.type =
            'hidden';


        mandatoryInput.name =
            'is_mandatory';


        mandatoryInput.value =
            isMandatory;


        form.appendChild(mandatoryInput);


        // =====================================================
        // VALIDITY
        // =====================================================

        const validityInput =
            document.createElement('input');


        validityInput.type =
            'hidden';


        validityInput.name =
            'validity_expiration';


        validityInput.value =
            validity;


        form.appendChild(validityInput);


        // =====================================================
        // INSTRUCTIONS
        // =====================================================

        const instructionsInput =
            document.createElement('input');


        instructionsInput.type =
            'hidden';


        instructionsInput.name =
            'instructions';


        instructionsInput.value =
            instructions;


        form.appendChild(instructionsInput);


        // =====================================================
        // SUBMIT TO LARAVEL
        // =====================================================

        document.body.appendChild(form);


        form.submit();

    }


    // =========================================================
    // ADD REQUIREMENT ROW
    // =========================================================
    //
    // NOTE:
    // This remains for the Template Library's temporary
    // client-side Apply behavior.
    //
    // New saved requirements are rendered by Laravel
    // directly from the database after redirect/refresh.
    // =========================================================

    function addRequirementRow(
        name,
        clientType,
        mandatory,
        source,
        evidence,
        validity
    ) {

        const tbody =
            document.getElementById(
                'requirementTableBody'
            );


        if (!tbody) {
            return;
        }


        const emptyRow =
            document.getElementById(
                'emptyRequirementRow'
            );


        if (emptyRow) {
            emptyRow.remove();
        }


        const rowCount =
            tbody.querySelectorAll(
                'tr[data-requirement-id]'
            ).length + 1;


        const tr =
            document.createElement('tr');


        tr.className =
            'hover:bg-slate-50/60 transition';


        tr.innerHTML = `

            <td class="py-3 px-3 text-center">

                <input
                    type="checkbox"
                    class="requirement-checkbox rounded border-slate-300"
                    onchange="updateSelectedRequirements()"
                >

            </td>


            <td class="py-3 px-3 font-semibold text-slate-700">

                ${rowCount}

            </td>


            <td class="py-3 px-3 font-medium text-slate-900">

                ${escapeTemplateHtml(name)}

            </td>


            <td class="py-3 px-3">

                <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded text-[10px] font-semibold">

                    ${escapeTemplateHtml(clientType)}

                </span>

            </td>


            <td class="py-3 px-3 font-bold text-slate-800">

                ${escapeTemplateHtml(mandatory)}

            </td>


            <td class="py-3 px-3 text-slate-600">

                ${escapeTemplateHtml(source)}

            </td>


            <td class="py-3 px-3 text-slate-600">

                ${escapeTemplateHtml(evidence)}

            </td>


            <td class="py-3 px-3 text-slate-600">

                ${escapeTemplateHtml(validity || 'N/A')}

            </td>


            <td class="py-3 px-3 text-right">

                <span class="text-slate-400 text-[10px]">
                    Pending Save
                </span>

            </td>

        `;


        tbody.appendChild(tr);

    }


    // =========================================================
    // REQUIREMENT CHECKBOX SELECTION
    // =========================================================

    function updateSelectedRequirements() {

        const tbody =
            document.getElementById(
                'requirementTableBody'
            );


        if (!tbody) {
            return;
        }


        const checkboxes =
            tbody.querySelectorAll(
                'input.requirement-checkbox'
            );


        const selected =
            tbody.querySelectorAll(
                'input.requirement-checkbox:checked'
            );


        const count =
            selected.length;


        const selectedCount =
            document.getElementById(
                'selectedCount'
            );


        if (selectedCount) {

            selectedCount.textContent =
                count;

        }


        const addToLibraryBtn =
            document.getElementById(
                'addToLibraryBtn'
            );


        if (addToLibraryBtn) {

            if (count > 0) {

                addToLibraryBtn.classList.remove(
                    'hidden'
                );

            } else {

                addToLibraryBtn.classList.add(
                    'hidden'
                );

            }

        }


        const selectAll =
            document.getElementById(
                'selectAllRequirements'
            );


        if (selectAll) {

            selectAll.checked =
                checkboxes.length > 0 &&
                selected.length === checkboxes.length;

        }

    }


    // =========================================================
    // SELECT / UNSELECT ALL REQUIREMENTS
    // =========================================================

    function toggleAllRequirements(
        selectAllCheckbox
    ) {

        const checkboxes =
            document.querySelectorAll(
                '#requirementTableBody input.requirement-checkbox'
            );


        checkboxes.forEach(function (checkbox) {

            checkbox.checked =
                selectAllCheckbox.checked;

        });


        updateSelectedRequirements();

    }

</script>

{{-- =========================================================
    STEP 5 — WORKFLOW
========================================================== --}}

<div id="workspace-panel-5"
     class="workspace-panel bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-5 hidden">

    {{-- =====================================================
        HEADER
    ====================================================== --}}

    <div class="flex justify-between items-start border-b border-slate-100 pb-3">

        <div>

            <h2 class="text-base font-bold text-slate-900">
                Product Activity Workflow
            </h2>

            <p class="text-xs text-slate-400 font-normal mt-0.5">
                2-Level Hierarchy: Main Activity &amp; Sub-Activity
            </p>

        </div>


        @if(!$isViewOnly)

            <div class="flex items-center space-x-2">

                {{-- BULK IMPORT --}}
                <button type="button"
                        onclick="openProductBulkImportModal()"
                        class="border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs px-3 py-1.5 rounded font-medium transition cursor-pointer">

                    <i class="fa-solid fa-folder-plus text-blue-600 text-xs mr-1"></i>

                    + Bulk Import

                </button>


                {{-- ADD ACTIVITY --}}
                <button type="button"
                        onclick="openProductActivityModal()"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1.5 rounded font-medium transition cursor-pointer">

                    <i class="fa-solid fa-plus text-xs mr-1"></i>

                    Add Activity

                </button>

            </div>

        @endif

    </div>


    {{-- =====================================================
        ACTIVITY TABLE
    ====================================================== --}}

    <div class="overflow-x-auto">

        <table class="w-full text-left border-collapse text-xs">

            <thead>

                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">

                    {{-- SELECT --}}
                    <th class="py-2.5 px-3 w-8">

                        <input type="checkbox"
                               id="productSelectAllActivities"
                               class="rounded border-slate-300"
                               onchange="toggleAllProductActivities(this)">

                    </th>


                    {{-- SEQUENCE --}}
                    <th class="py-2.5 px-3 text-center w-16">

                        SEQ

                    </th>


                    {{-- NAME --}}
                    <th class="py-2.5 px-3">

                        ACTIVITY / SUB-ACTIVITY NAME

                    </th>


                    {{-- DAYS --}}
                    <th class="py-2.5 px-3 text-center">

                        EST. DAYS

                    </th>


                    {{-- HOURS --}}
                    <th class="py-2.5 px-3 text-center">

                        WORKING HOURS

                    </th>


                    {{-- BILLABLE --}}
                    <th class="py-2.5 px-3 text-center">

                        BILLABLE

                    </th>


                    {{-- ACTION --}}
                    <th class="py-2.5 px-3 text-right">

                        ACTION

                    </th>

                </tr>

            </thead>


            <tbody id="productActivityTableBody"
                   class="divide-y divide-slate-100">

                @forelse($mainActivitiesList as $activity)

                    @php

                        $actObj = is_array($activity)
                            ? (object)$activity
                            : $activity;

                        $actId = $actObj->id ?? null;

                        $subActivities =
                            $actObj->children
                            ?? $actObj->subActivities
                            ?? [];

                    @endphp


                    {{-- =================================================
                        MAIN ACTIVITY
                    ================================================== --}}

                    <tr id="product-activity-row-{{ $actId }}"
                        class="hover:bg-slate-50 bg-slate-50/40">

                        {{-- CHECKBOX --}}
                        <td class="py-2.5 px-3">

                            <input type="checkbox"
                                   class="rounded border-slate-300 product-activity-checkbox"
                                   value="{{ $actId }}"
                                   onchange="updateSelectedProductActivities()">

                        </td>


                        {{-- SEQUENCE --}}
                        <td class="py-2.5 px-3 text-center">

                            <span class="inline-flex items-center justify-center
                                         min-w-[28px] h-6 px-2
                                         rounded-md bg-slate-100
                                         text-slate-700
                                         font-bold font-mono">

                                {{ $actObj->sequence ?? '' }}

                            </span>

                        </td>


                        {{-- NAME --}}
                        <td class="py-2.5 px-3 font-semibold text-slate-900">

                            @if(count($subActivities) > 0)

                                <button type="button"
                                        onclick="toggleProductSubActivities({{ $actId }})"
                                        class="mr-1 text-slate-400 hover:text-slate-600 cursor-pointer">

                                    <i id="product-chevron-{{ $actId }}"
                                       class="fa-solid fa-chevron-right"></i>

                                </button>

                            @endif


                            <span>
                                {{ $actObj->name }}
                            </span>


                            @if(!empty($actObj->description))

                                <p class="text-[10px] text-slate-400 font-normal mt-0.5">

                                    {{ $actObj->description }}

                                </p>

                            @endif

                        </td>


                        {{-- EXPECTED DAYS --}}
                        <td class="py-2.5 px-3 text-center font-semibold">

                            {{ $actObj->expected_days ?? 1 }} d

                        </td>


                        {{-- WORKING HOURS --}}
                        <td class="py-2.5 px-3 text-center font-mono">

                            {{ number_format(
                                $actObj->working_hours
                                ?? $actObj->expected_working_hours
                                ?? 1,
                                1
                            ) }} hrs

                        </td>


                        {{-- BILLABLE --}}
                        <td class="py-2.5 px-3 text-center">

                            <span class="px-2 py-0.5 rounded text-[10px] font-bold
                                {{ $actObj->is_billable
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-slate-100 text-slate-500' }}">

                                {{ $actObj->is_billable ? 'YES' : 'NO' }}

                            </span>

                        </td>


                        {{-- ACTION --}}
                        <td class="py-2.5 px-3 text-right space-x-2 whitespace-nowrap">

                            @if(!$isViewOnly)

                                <button type="button"
                                        onclick='openProductEditActivityModal(@json($actObj))'
                                        class="text-blue-600 hover:text-blue-800 hover:underline font-semibold cursor-pointer">

                                    Edit

                                </button>


                                @if($actId && Route::has('products.activities.destroy'))

                                    <form action="{{ route('products.activities.destroy', [$product->id, $actId]) }}"
                                          method="POST"
                                          class="inline-block"
                                          onsubmit="return confirm('Are you sure you want to delete this activity?');">

                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="text-rose-600 hover:text-rose-800 hover:underline font-semibold cursor-pointer">

                                            Delete

                                        </button>

                                    </form>

                                @endif

                            @else

                                <span class="text-slate-400 italic text-[10px]">
                                    Read-only
                                </span>

                            @endif

                        </td>

                    </tr>


                    {{-- =================================================
                        SUB ACTIVITIES
                    ================================================== --}}

                    @foreach($subActivities as $sub)

                        @php

                            $subObj = is_array($sub)
                                ? (object)$sub
                                : $sub;

                            $subId = $subObj->id ?? null;

                        @endphp


                        <tr id="product-subactivity-row-{{ $subId }}"
                            class="product-subactivity-row hidden hover:bg-slate-50 bg-white">

                            {{-- CHECKBOX --}}
                            <td class="py-2.5 px-3">

                                <input type="checkbox"
                                       class="rounded border-slate-300 product-activity-checkbox"
                                       value="{{ $subId }}"
                                       onchange="updateSelectedProductActivities()">

                            </td>


                            {{-- SEQUENCE --}}
                            <td class="py-2.5 px-3 text-center">

                                <span class="inline-flex items-center justify-center
                                             min-w-[28px] h-6 px-2
                                             rounded-md bg-slate-50
                                             text-slate-600
                                             font-bold font-mono">

                                    {{ $subObj->sequence ?? '' }}

                                </span>

                            </td>


                            {{-- NAME --}}
                            <td class="py-2.5 px-3 text-slate-700 pl-8">

                                <div class="flex items-center space-x-2">

                                    <i class="fa-solid fa-turn-up rotate-90 text-slate-300 text-[10px]"></i>

                                    <div>

                                        <span>
                                            {{ $subObj->name }}
                                        </span>


                                        @if(!empty($subObj->description))

                                            <p class="text-[10px] text-slate-400 mt-0.5">

                                                {{ $subObj->description }}

                                            </p>

                                        @endif

                                    </div>

                                </div>

                            </td>


                            {{-- EXPECTED DAYS --}}
                            <td class="py-2.5 px-3 text-center text-slate-600">

                                {{ $subObj->expected_days ?? 1 }} d

                            </td>


                            {{-- WORKING HOURS --}}
                            <td class="py-2.5 px-3 text-center font-mono text-slate-600">

                                {{ number_format(
                                    $subObj->working_hours
                                    ?? $subObj->expected_working_hours
                                    ?? 1,
                                    1
                                ) }} hrs

                            </td>


                            {{-- BILLABLE --}}
                            <td class="py-2.5 px-3 text-center">

                                <span class="px-2 py-0.5 rounded text-[10px] font-bold
                                    {{ $subObj->is_billable
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-slate-100 text-slate-500' }}">

                                    {{ $subObj->is_billable ? 'YES' : 'NO' }}

                                </span>

                            </td>


                            {{-- ACTION --}}
                            <td class="py-2.5 px-3 text-right space-x-2 whitespace-nowrap">

                                @if(!$isViewOnly)

                                    <button type="button"
                                            onclick='openProductEditActivityModal(@json($subObj))'
                                            class="text-blue-600 hover:text-blue-800 hover:underline font-semibold cursor-pointer">

                                        Edit

                                    </button>


                                    @if($subId && Route::has('products.activities.destroy'))

                                        <form action="{{ route('products.activities.destroy', [$product->id, $subId]) }}"
                                              method="POST"
                                              class="inline-block"
                                              onsubmit="return confirm('Are you sure you want to delete this sub-activity?');">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="text-rose-600 hover:text-rose-800 hover:underline font-semibold cursor-pointer">

                                                Delete

                                            </button>

                                        </form>

                                    @endif

                                @else

                                    <span class="text-slate-400 italic text-[10px]">
                                        Read-only
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @endforeach


                @empty

                    <tr>

                        <td colspan="7"
                            class="py-8 text-center text-slate-400 italic">

                            No activities configured for this product yet.

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    {{-- =====================================================
        SELECTED TOOLBAR
    ====================================================== --}}

    <div id="productSelectedActivitiesToolbar"
         class="hidden bg-blue-50 border border-blue-200 text-blue-900 px-4 py-2 rounded-lg text-xs flex justify-between items-center shadow-sm">

        <div class="flex items-center space-x-2">

            <i class="fa-solid fa-check-double text-blue-600"></i>

            <span class="font-bold">

                <span id="productSelectedActivityCount">
                    0
                </span>

                item(s) selected

            </span>

        </div>


        <div class="text-[10px] text-blue-600">

            Selected activities are ready for bulk action.

        </div>

    </div>


    {{-- =====================================================
        FOOTER
    ====================================================== --}}

    <div class="flex justify-between items-center pt-3 border-t">

        <button type="button"
                onclick="switchWorkspaceTab(4)"
                class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer">

            &larr; Back to Requirements

        </button>


        <button type="button"
                onclick="switchWorkspaceTab(6)"
                class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded text-xs cursor-pointer flex items-center space-x-1">

            <span>
                Save &amp; Continue to Commercials
            </span>

            <i class="fa-solid fa-arrow-right text-[10px]"></i>

        </button>

    </div>

</div>



{{-- =========================================================
    MODAL — BULK IMPORT PRODUCT ACTIVITIES
========================================================== --}}

<div id="productBulkImportModal"
     class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center p-4 z-[9999] overflow-y-auto hidden">

    <div class="bg-white rounded-lg shadow-2xl max-w-lg w-full p-6 text-xs space-y-4 max-h-[90vh] overflow-y-auto relative pointer-events-auto">

        {{-- HEADER --}}
        <div class="flex justify-between items-center border-b pb-3">

            <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-2">

                <i class="fa-solid fa-folder-plus text-blue-600"></i>

                <span>
                    Bulk Import Product Activities
                </span>

            </h3>


            <button type="button"
                    onclick="closeProductBulkImportModal()"
                    class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 cursor-pointer">

                &times;

            </button>

        </div>


        {{-- FORM --}}
        <form action="{{ Route::has('products.activities.bulk-import') && isset($product->id)
            ? route('products.activities.bulk-import', $product->id)
            : '#' }}"
              method="POST"
              enctype="multipart/form-data"
              class="space-y-4">

            @csrf


            <input type="hidden"
                   name="mode"
                   value="{{ $mode }}">


            {{-- FILE INPUT --}}
            <div>

                <label class="block font-bold text-slate-800 mb-1">

                    Upload CSV / Text File

                </label>


                <input type="file"
                       name="activity_file"
                       accept=".csv,.txt"
                       class="w-full border border-slate-300 rounded p-2 text-xs bg-white text-slate-700">

            </div>


            {{-- OR --}}
            <div class="text-center font-bold text-slate-400 text-xs">

                — OR —

            </div>


            {{-- TEXT INPUT --}}
            <div>

                <label class="block font-bold text-slate-800 mb-1">

                    Paste Activity Outline Text

                </label>


                <textarea name="outline_text"
                          rows="5"
                          class="w-full border border-slate-300 rounded p-2.5 font-mono text-xs outline-none focus:border-blue-500 bg-white text-slate-800"
                          placeholder="Main Activity 1&#10;&#9;Sub-activity 1.1&#10;&#9;Sub-activity 1.2&#10;Main Activity 2"></textarea>

            </div>


            {{-- DEFAULT VALUES --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2 border-t">

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        Default Expected Days

                    </label>


                    <input type="number"
                           name="default_expected_days"
                           value="1"
                           min="0"
                           step="0.5"
                           class="w-full border border-slate-300 rounded p-2 outline-none">

                </div>


                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        Default Working Hours

                    </label>


                    <input type="number"
                           name="default_working_hours"
                           value="1.0"
                           min="0"
                           step="0.5"
                           class="w-full border border-slate-300 rounded p-2 outline-none">

                </div>

            </div>


            {{-- BUTTONS --}}
            <div class="flex justify-end space-x-2 pt-3 border-t">

                <button type="button"
                        onclick="closeProductBulkImportModal()"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded font-medium cursor-pointer">

                    Cancel

                </button>


                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium cursor-pointer">

                    Start Bulk Import

                </button>

            </div>

        </form>

    </div>

</div>



{{-- =========================================================
    MODAL — ADD PRODUCT ACTIVITY
========================================================== --}}

<div id="productActivityModal"
     class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center p-4 z-[9999] overflow-y-auto hidden">

    <div class="bg-white rounded-lg shadow-2xl max-w-lg w-full p-6 text-xs space-y-4 max-h-[90vh] overflow-y-auto relative pointer-events-auto">

        <div class="flex justify-between items-center border-b pb-3">

            <h3 class="text-sm font-bold text-slate-900">
                Add Product Activity
            </h3>

            <button type="button"
                    onclick="closeProductActivityModal()"
                    class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 cursor-pointer">

                &times;

            </button>

        </div>


        <form action="{{ route('products.activities.store', $product->id) }}"
              method="POST"
              class="space-y-4">

            @csrf


            <input type="hidden"
                   name="mode"
                   value="{{ $mode }}">


            {{-- =====================================================
                ACTIVITY LEVEL + PARENT
            ====================================================== --}}

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">
                        Activity Level *
                    </label>

                    <select name="activity_level"
                            id="productAddActivityLevel"
                            onchange="toggleProductAddParent()"
                            required
                            class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">

                        <option value="Main Activity">
                            Main Activity
                        </option>

                        <option value="Sub-Activity">
                            Sub-Activity
                        </option>

                    </select>

                </div>


                <div id="productAddParentWrapper"
                     class="hidden">

                    <label class="block font-semibold text-slate-700 mb-1">

                        Parent Main Activity *

                    </label>

                    <select name="parent_id"
                            id="productAddParent"
                            class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">

                        <option value="">
                            -- Select Parent Main Activity --
                        </option>

                        @foreach($mainActivitiesList as $mAct)

                            @php

                                $mActObj = is_array($mAct)
                                    ? (object) $mAct
                                    : $mAct;

                            @endphp

                            <option value="{{ $mActObj->id ?? '' }}">

                                {{ $mActObj->name ?? '' }}

                            </option>

                        @endforeach

                    </select>

                </div>

            </div>


            {{-- =====================================================
                NAME
            ====================================================== --}}

            <div>

                <label class="block font-semibold text-slate-700 mb-1">

                    Activity Name *

                </label>

                <input type="text"
                       name="name"
                       required
                       placeholder="e.g. Initial Assessment / Document Verification"
                       class="w-full border border-slate-300 rounded p-2 outline-none focus:border-blue-500 bg-white text-slate-800">

            </div>


            {{-- =====================================================
                DESCRIPTION
            ====================================================== --}}

            <div>

                <label class="block font-semibold text-slate-700 mb-1">

                    Description / Instructions

                </label>

                <textarea name="description"
                          rows="2"
                          placeholder="Brief execution guidance for associates..."
                          class="w-full border border-slate-300 rounded p-2.5 outline-none bg-white text-slate-800"></textarea>

            </div>


            {{-- =====================================================
                EXPECTED DAYS / WORKING HOURS
                SEQUENCE IS AUTOMATIC
            ====================================================== --}}

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        Expected Days (SLA)

                    </label>

                    <input type="number"
                           name="expected_days"
                           min="0"
                           step="0.01"
                           value="1"
                           required
                           class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">

                </div>


                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        Working Hours

                    </label>

                    <input type="number"
                           name="working_hours"
                           step="0.5"
                           min="0"
                           value="1.0"
                           required
                           class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">

                </div>

            </div>


            {{-- =====================================================
                BILLABLE / MANDATORY
            ====================================================== --}}

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        Billable Status

                    </label>

                    <select name="is_billable"
                            required
                            class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">

                        <option value="1">
                            Billable
                        </option>

                        <option value="0">
                            Non-Billable
                        </option>

                    </select>

                </div>


                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        Mandatory Level

                    </label>

                    <select name="is_mandatory"
                            required
                            class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">

                        <option value="1">
                            Mandatory Activity
                        </option>

                        <option value="0">
                            Optional / Conditional
                        </option>

                    </select>

                </div>

            </div>


            {{-- =====================================================
                BUTTONS
            ====================================================== --}}

            <div class="flex justify-end space-x-2 pt-3 border-t">

                <button type="button"
                        onclick="closeProductActivityModal()"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded font-medium cursor-pointer">

                    Cancel

                </button>


                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium cursor-pointer">

                    Save Activity

                </button>

            </div>

        </form>

    </div>

</div>

{{-- =========================================================
    MODAL — EDIT PRODUCT ACTIVITY
========================================================== --}}

<div id="productEditActivityModal"
     class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center p-4 z-[9999] overflow-y-auto hidden">

    <div class="bg-white rounded-lg shadow-2xl max-w-lg w-full p-6 text-xs space-y-4 max-h-[90vh] overflow-y-auto relative pointer-events-auto">

        <div class="flex justify-between items-center border-b pb-3">

            <h3 class="text-sm font-bold text-slate-900">

                Edit Product Activity

            </h3>


            <button type="button"
                    onclick="closeProductEditActivityModal()"
                    class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 cursor-pointer">

                &times;

            </button>

        </div>


        <form id="productEditActivityForm"
              method="POST"
              class="space-y-4">

            @csrf
            @method('PUT')


            <input type="hidden"
                   name="mode"
                   value="{{ $mode }}">


            {{-- LEVEL + PARENT --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        Activity Level *

                    </label>


                    <select name="level"
                            id="productEditActivityLevel"
                            onchange="toggleProductEditParent()"
                            class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">

                        <option value="main">
                            Main Activity
                        </option>

                        <option value="sub">
                            Sub-Activity
                        </option>

                    </select>

                </div>


                <div id="productEditParentWrapper"
                     class="hidden">

                    <label class="block font-semibold text-slate-700 mb-1">

                        Parent Main Activity *

                    </label>


                    <select name="parent_id"
                            id="productEditParent"
                            class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">

                        <option value="">
                            -- Select Parent Main Activity --
                        </option>

                        @foreach($mainActivitiesList as $mAct)

                            @php

                                $mActObj = is_array($mAct)
                                    ? (object)$mAct
                                    : $mAct;

                            @endphp

                            <option value="{{ $mActObj->id ?? '' }}">

                                {{ $mActObj->name ?? '' }}

                            </option>

                        @endforeach

                    </select>

                </div>

            </div>


            {{-- NAME --}}
            <div>

                <label class="block font-semibold text-slate-700 mb-1">

                    Activity Name *

                </label>


                <input type="text"
                       name="name"
                       id="productEditActivityName"
                       required
                       class="w-full border border-slate-300 rounded p-2 outline-none focus:border-blue-500 bg-white text-slate-800">

            </div>


            {{-- DESCRIPTION --}}
            <div>

                <label class="block font-semibold text-slate-700 mb-1">

                    Description / Instructions

                </label>


                <textarea name="description"
                          id="productEditActivityDescription"
                          rows="2"
                          class="w-full border border-slate-300 rounded p-2.5 outline-none bg-white text-slate-800"></textarea>

            </div>


            {{-- EXPECTED DAYS / WORKING HOURS --}}
            {{-- SEQUENCE IS NOT EDITABLE --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        Expected Days (SLA)

                    </label>


                    <input type="number"
                           name="expected_days"
                           id="productEditActivityExpectedDays"
                           min="0"
                           class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">

                </div>


                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        Working Hours

                    </label>


                    <input type="number"
                           name="expected_working_hours"
                           id="productEditActivityWorkingHours"
                           step="0.5"
                           min="0"
                           class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">

                </div>

            </div>


            {{-- BILLABLE / MANDATORY --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        Billable Status

                    </label>


                    <select name="is_billable"
                            id="productEditActivityBillable"
                            class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">

                        <option value="1">
                            Billable
                        </option>

                        <option value="0">
                            Non-Billable
                        </option>

                    </select>

                </div>


                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        Mandatory Level

                    </label>


                    <select name="is_mandatory"
                            id="productEditActivityMandatory"
                            class="w-full border border-slate-300 rounded p-2 outline-none bg-white text-slate-800">

                        <option value="1">
                            Mandatory Activity
                        </option>

                        <option value="0">
                            Optional / Conditional
                        </option>

                    </select>

                </div>

            </div>


            {{-- BUTTONS --}}
            <div class="flex justify-end space-x-2 pt-3 border-t">

                <button type="button"
                        onclick="closeProductEditActivityModal()"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded font-medium cursor-pointer">

                    Cancel

                </button>


                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium cursor-pointer">

                    Update Activity

                </button>

            </div>

        </form>

    </div>

</div>



{{-- =========================================================
    PRODUCT WORKFLOW JAVASCRIPT
========================================================== --}}

<script>

    /* =========================================================
       BULK IMPORT MODAL
    ========================================================== */

    function openProductBulkImportModal() {

        const modal =
            document.getElementById(
                'productBulkImportModal'
            );

        if (modal) {

            modal.classList.remove('hidden');

        }

    }


    function closeProductBulkImportModal() {

        const modal =
            document.getElementById(
                'productBulkImportModal'
            );

        if (modal) {

            modal.classList.add('hidden');

        }

    }


    /* =========================================================
       ADD ACTIVITY MODAL
    ========================================================== */

    function openProductActivityModal() {

        const modal =
            document.getElementById(
                'productActivityModal'
            );

        if (modal) {

            modal.classList.remove('hidden');

        }

    }


    function closeProductActivityModal() {

        const modal =
            document.getElementById(
                'productActivityModal'
            );

        if (modal) {

            modal.classList.add('hidden');

        }

    }


    /* =========================================================
       ADD ACTIVITY — PARENT TOGGLE
    ========================================================== */

    function toggleProductAddParent() {

        const level =
            document.getElementById(
                'productAddActivityLevel'
            );

        const wrapper =
            document.getElementById(
                'productAddParentWrapper'
            );

        const parent =
            document.getElementById(
                'productAddParent'
            );


        if (!level || !wrapper) {

            return;

        }


        if (level.value === 'sub') {

            wrapper.classList.remove('hidden');

            if (parent) {

                parent.required = true;

            }

        }

        else {

            wrapper.classList.add('hidden');

            if (parent) {

                parent.required = false;
                parent.value = '';

            }

        }

    }


    /* =========================================================
       EDIT ACTIVITY MODAL
    ========================================================== */

    function openProductEditActivityModal(activity) {

        const modal =
            document.getElementById(
                'productEditActivityModal'
            );

        const form =
            document.getElementById(
                'productEditActivityForm'
            );


        if (!modal || !form || !activity) {

            return;

        }


        const activityId =
            activity.id;


        const updateUrl =
            @json(route(
                'products.activities.update',
                [
                    'id' => $product->id,
                    'activityId' => '__ACTIVITY_ID__'
                ]
            ));


        form.action =
            updateUrl.replace(
                '__ACTIVITY_ID__',
                activityId
            );


        const levelSelect =
            document.getElementById(
                'productEditActivityLevel'
            );


        const activityLevel =
            activity.activity_level
            || 'Main Activity';


        levelSelect.value =
            activityLevel === 'Sub-Activity'
                ? 'sub'
                : 'main';


        const parentSelect =
            document.getElementById(
                'productEditParent'
            );


        if (parentSelect) {

            parentSelect.value =
                activity.parent_id ?? '';

        }


        document.getElementById(
            'productEditActivityName'
        ).value =
            activity.name ?? '';


        document.getElementById(
            'productEditActivityDescription'
        ).value =
            activity.description ?? '';


        document.getElementById(
            'productEditActivityExpectedDays'
        ).value =
            activity.expected_days ?? 1;


        document.getElementById(
            'productEditActivityWorkingHours'
        ).value =
            activity.working_hours
            ?? activity.expected_working_hours
            ?? 1;


        document.getElementById(
            'productEditActivityBillable'
        ).value =
            activity.is_billable ? '1' : '0';


        document.getElementById(
            'productEditActivityMandatory'
        ).value =
            activity.is_mandatory ? '1' : '0';


        toggleProductEditParent();


        modal.classList.remove('hidden');

    }


    function closeProductEditActivityModal() {

        const modal =
            document.getElementById(
                'productEditActivityModal'
            );

        if (modal) {

            modal.classList.add('hidden');

        }

    }


    /* =========================================================
       EDIT ACTIVITY — PARENT TOGGLE
    ========================================================== */

    function toggleProductEditParent() {

        const level =
            document.getElementById(
                'productEditActivityLevel'
            );

        const wrapper =
            document.getElementById(
                'productEditParentWrapper'
            );

        const parent =
            document.getElementById(
                'productEditParent'
            );


        if (!level || !wrapper) {

            return;

        }


        if (level.value === 'sub') {

            wrapper.classList.remove('hidden');

            if (parent) {

                parent.required = true;

            }

        }

        else {

            wrapper.classList.add('hidden');

            if (parent) {

                parent.required = false;
                parent.value = '';

            }

        }

    }


    /* =========================================================
       EXPAND / COLLAPSE SUB-ACTIVITIES
    ========================================================== */

    function toggleProductSubActivities(activityId) {

        const mainRow =
            document.getElementById(
                'product-activity-row-' +
                activityId
            );


        const chevron =
            document.getElementById(
                'product-chevron-' +
                activityId
            );


        if (!mainRow) {

            return;

        }


        let nextRow =
            mainRow.nextElementSibling;


        let isOpening = false;


        if (
            nextRow &&
            nextRow.classList.contains(
                'product-subactivity-row'
            )
        ) {

            isOpening =
                nextRow.classList.contains(
                    'hidden'
                );

        }


        while (
            nextRow &&
            nextRow.classList.contains(
                'product-subactivity-row'
            )
        ) {

            if (isOpening) {

                nextRow.classList.remove(
                    'hidden'
                );

            }

            else {

                nextRow.classList.add(
                    'hidden'
                );

            }


            nextRow =
                nextRow.nextElementSibling;

        }


        if (chevron) {

            if (isOpening) {

                chevron.classList.remove(
                    'fa-chevron-right'
                );

                chevron.classList.add(
                    'fa-chevron-down'
                );

            }

            else {

                chevron.classList.remove(
                    'fa-chevron-down'
                );

                chevron.classList.add(
                    'fa-chevron-right'
                );

            }

        }

    }


    /* =========================================================
       SELECT ALL
    ========================================================== */

    function toggleAllProductActivities(
        selectAllCheckbox
    ) {

        const checkboxes =
            document.querySelectorAll(
                '.product-activity-checkbox'
            );


        checkboxes.forEach(
            function(checkbox) {

                checkbox.checked =
                    selectAllCheckbox.checked;

            }
        );


        updateSelectedProductActivities();

    }


    /* =========================================================
       UPDATE SELECTED COUNT
    ========================================================== */

    function updateSelectedProductActivities() {

        const selected =
            document.querySelectorAll(
                '.product-activity-checkbox:checked'
            );


        const count =
            selected.length;


        const toolbar =
            document.getElementById(
                'productSelectedActivitiesToolbar'
            );


        const countElement =
            document.getElementById(
                'productSelectedActivityCount'
            );


        if (countElement) {

            countElement.textContent =
                count;

        }


        if (toolbar) {

            if (count > 0) {

                toolbar.classList.remove(
                    'hidden'
                );

            }

            else {

                toolbar.classList.add(
                    'hidden'
                );

            }

        }


        const selectAll =
            document.getElementById(
                'productSelectAllActivities'
            );


        const checkboxes =
            document.querySelectorAll(
                '.product-activity-checkbox'
            );


        if (selectAll) {

            selectAll.checked =
                checkboxes.length > 0 &&
                selected.length ===
                checkboxes.length;

        }

    }


    /* =========================================================
       CLICK OUTSIDE MODALS
    ========================================================== */

    document.addEventListener(
        'click',
        function(event) {

            const bulkModal =
                document.getElementById(
                    'productBulkImportModal'
                );


            const activityModal =
                document.getElementById(
                    'productActivityModal'
                );


            const editModal =
                document.getElementById(
                    'productEditActivityModal'
                );


            if (
                event.target === bulkModal
            ) {

                closeProductBulkImportModal();

            }


            if (
                event.target === activityModal
            ) {

                closeProductActivityModal();

            }


            if (
                event.target === editModal
            ) {

                closeProductEditActivityModal();

            }

        }
    );


    /* =========================================================
       ESC KEY — CLOSE MODALS
    ========================================================== */

    document.addEventListener(
        'keydown',
        function(event) {

            if (event.key !== 'Escape') {

                return;

            }


            closeProductBulkImportModal();

            closeProductActivityModal();

            closeProductEditActivityModal();

        }
    );

</script>


{{-- =========================================================
    STEP 6 — COMMERCIALS
========================================================== --}}

<div id="workspace-panel-6"
     class="workspace-panel bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-5 hidden">

    <form action="{{ route('products.commercials.update', $product->id) }}" method="POST">

        @csrf
        @method('PUT')


        <!-- ===================================================== -->
        <!-- HEADER -->
        <!-- ===================================================== -->

        <div class="border-b border-slate-100 pb-3">

            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                Product Commercials, Pricing and Costing
            </h3>

            <p class="text-xs text-slate-500 mt-0.5">
                Define product pricing models, tax treatment, cost basis, margins, and payment terms.
            </p>

        </div>


        <!-- ===================================================== -->
        <!-- PRICING MODELS -->
        <!-- ===================================================== -->

        <div class="border border-slate-200 rounded-xl p-4 space-y-4">

            <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-800">

                <i class="fa-solid fa-tags text-blue-600 mr-1"></i>

                Pricing Models &amp; Core Commercial Fields

            </h4>


            <!-- PRICING MODEL / CURRENCY / STANDARD PRICE -->

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">


                <!-- PRICING MODEL -->

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        <span class="relative inline-flex items-center group cursor-pointer">

                            Pricing Model *

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] ml-1"></i>

                            <span
                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Defines how the product price is determined and charged, such as fixed, variable, tiered, subscription, or cost-plus pricing.
                            </span>

                        </span>

                    </label>


                    <select
                        name="pricing_model"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                    >

                        <option value="Fixed Price" {{ ($product->pricing_model ?? 'Fixed Price') === 'Fixed Price' ? 'selected' : '' }}>
                            Fixed Price
                        </option>

                        <option value="Variable Price" {{ ($product->pricing_model ?? '') === 'Variable Price' ? 'selected' : '' }}>
                            Variable Price
                        </option>

                        <option value="Tiered Price" {{ ($product->pricing_model ?? '') === 'Tiered Price' ? 'selected' : '' }}>
                            Tiered Price
                        </option>

                        <option value="Subscription" {{ ($product->pricing_model ?? '') === 'Subscription' ? 'selected' : '' }}>
                            Subscription
                        </option>

                        <option value="Cost Plus" {{ ($product->pricing_model ?? '') === 'Cost Plus' ? 'selected' : '' }}>
                            Cost Plus
                        </option>

                    </select>

                </div>


                <!-- CURRENCY -->

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        <span class="relative inline-flex items-center group cursor-pointer">

                            Currency *

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] ml-1"></i>

                            <span
                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                The currency used to express the product price, costs, margins, and other monetary commercial values.
                            </span>

                        </span>

                    </label>


                    <select
                        name="currency"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                    >

                        <option value="PHP" {{ ($product->currency ?? 'PHP') === 'PHP' ? 'selected' : '' }}>
                            PHP (₱)
                        </option>

                        <option value="USD" {{ ($product->currency ?? '') === 'USD' ? 'selected' : '' }}>
                            USD ($)
                        </option>

                        <option value="EUR" {{ ($product->currency ?? '') === 'EUR' ? 'selected' : '' }}>
                            EUR (€)
                        </option>

                    </select>

                </div>


                <!-- STANDARD PRICE -->

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        <span class="relative inline-flex items-center group cursor-pointer">

                            Standard Price *

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] ml-1"></i>

                            <span
                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                The standard or default selling price of the product before applicable discounts, adjustments, or special pricing rules.
                            </span>

                        </span>

                    </label>


                    <input
                        type="number"
                        name="price"
                        step="0.01"
                        value="{{ $product->price ?? '0' }}"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                    >

                </div>

            </div>


            <!-- TAX / MINIMUM / MAXIMUM -->

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">


                <!-- TAX TREATMENT -->

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        <span class="relative inline-flex items-center group cursor-pointer">

                            Tax Treatment *

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] ml-1"></i>

                            <span
                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Specifies how applicable taxes are treated in the product price, including whether tax is included, excluded, or not applicable.
                            </span>

                        </span>

                    </label>


                    <select
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-slate-100 text-slate-700 outline-none cursor-not-allowed"
                        disabled
                    >

                        <option value="VAT Exclusive" {{ ($product->tax_treatment ?? '') === 'VAT Exclusive' ? 'selected' : '' }}>
                            VAT Exclusive
                        </option>

                        <option value="VAT Inclusive" {{ ($product->tax_treatment ?? '') === 'VAT Inclusive' ? 'selected' : '' }}>
                            VAT Inclusive
                        </option>

                        <option value="No Tax" {{ ($product->tax_treatment ?? '') === 'No Tax' ? 'selected' : '' }}>
                            No Tax
                        </option>

                        <option value="Percentage Tax" {{ ($product->tax_treatment ?? '') === 'Percentage Tax' ? 'selected' : '' }}>
                            Percentage Tax
                        </option>

                        <option value="Other">
                            {{ $product->tax_treatment ?? 'Other' }}
                        </option>

                    </select>


                    <input
                        type="hidden"
                        name="tax_treatment"
                        value="{{ $product->tax_treatment ?? '' }}"
                    >

                </div>


                <!-- MINIMUM PRICE -->

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        <span class="relative inline-flex items-center group cursor-pointer">

                            Minimum Price

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] ml-1"></i>

                            <span
                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                The lowest acceptable selling price for the product before an additional approval, exception, or special commercial arrangement is required.
                            </span>

                        </span>

                    </label>


                    <input
                        type="number"
                        name="minimum_price"
                        step="0.01"
                        value="{{ $product->minimum_price ?? '0.00' }}"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                    >

                </div>


                <!-- MAXIMUM PRICE -->

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        <span class="relative inline-flex items-center group cursor-pointer">

                            Maximum Price

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] ml-1"></i>

                            <span
                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                The highest standard selling price allowed for the product under normal commercial rules.
                            </span>

                        </span>

                    </label>


                    <input
                        type="number"
                        name="maximum_price"
                        step="0.01"
                        value="{{ $product->maximum_price ?? '0.00' }}"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                    >

                </div>

            </div>

        </div>


       <!-- ===================================================== -->
        <!-- PRODUCT ECONOMICS -->
        <!-- ===================================================== -->

        <div class="border border-slate-200 rounded-xl p-4 space-y-4">

            <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-800">
                Product Economics
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">

                <!-- DISCOUNT ALLOWED -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">
                        <span class="relative inline-flex items-center group cursor-pointer">
                            Discount Allowed *
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] ml-1"></i>
                            <span
                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Defines whether discounts may be applied to the product and whether approval is required before granting one.
                            </span>
                        </span>
                    </label>

                    <select
                        name="discount_allowed"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                    >
                        <option value="Not Allowed" {{ ($product->discount_allowed ?? 'Not Allowed') === 'Not Allowed' ? 'selected' : '' }}>
                            Not Allowed
                        </option>
                        <option value="Allowed" {{ ($product->discount_allowed ?? '') === 'Allowed' ? 'selected' : '' }}>
                            Allowed
                        </option>
                        <option value="Approval Required" {{ ($product->discount_allowed ?? '') === 'Approval Required' ? 'selected' : '' }}>
                            Approval Required
                        </option>
                    </select>
                </div>

                <!-- MAX DISCOUNT W/O APPROVAL (%) - Katabi ng Discount Allowed -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">
                        <span class="relative inline-flex items-center group cursor-pointer">
                            Max Discount W/o Approval (%)
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] ml-1"></i>
                            <span
                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                The maximum percentage discount that can be granted without requiring formal managerial approval.
                            </span>
                        </span>
                    </label>

                    <input
                        type="number"
                        name="max_discount_without_approval"
                        step="0.01"
                        value="{{ $product->max_discount_without_approval ?? '0' }}"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                    >
                </div>

                <!-- EXPECTED MARGIN -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">
                        <span class="relative inline-flex items-center group cursor-pointer">
                            Expected Margin (%)
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] ml-1"></i>
                            <span
                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                The target percentage of revenue expected to remain after accounting for applicable costs.
                            </span>
                        </span>
                    </label>

                    <input
                        type="number"
                        name="expected_margin"
                        step="0.01"
                        value="{{ $product->expected_margin ?? '0' }}"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                    >
                </div>

                <!-- EXPECTED HOURS -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">
                        <span class="relative inline-flex items-center group cursor-pointer">
                            Expected Hours
                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] ml-1"></i>
                            <span
                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                The estimated labor or service hours needed to complete one unit or instance of the product.
                            </span>
                        </span>
                    </label>

                    <input
                        type="number"
                        name="expected_hours"
                        step="0.5"
                        value="{{ $product->expected_hours ?? '0' }}"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                    >
                </div>

            </div>

        </div>


        <!-- ===================================================== -->
        <!-- PAYMENT TERMS -->
        <!-- ===================================================== -->

        <div class="border border-slate-200 rounded-xl p-4 space-y-4">

            <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-800">
                PAYMENT TERMS
            </h4>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">


                <!-- STANDARD PAYMENT STRUCTURE -->

                <div class="space-y-2">

                    <label class="block font-semibold text-slate-700">

                        <span class="relative inline-flex items-center group cursor-pointer">

                            Standard Payment Structure *

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] ml-1"></i>

                            <span
                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Defines the standard timing and arrangement for collecting payment from the client for this product.
                            </span>

                        </span>

                    </label>


                    <select
                        id="standardPaymentSelect"
                        name="payment_structure"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                    >

                        <option value="">-- Select Payment Structure --</option>

                        <option value="Full Advance" {{ ($product->payment_structure ?? '') === 'Full Advance' ? 'selected' : '' }}>
                            Full Advance
                        </option>

                        <option value="50% Deposit, 50% Balance" {{ ($product->payment_structure ?? '') === '50% Deposit, 50% Balance' ? 'selected' : '' }}>
                            50% Deposit, 50% Balance
                        </option>

                        <option value="Milestone-based" {{ ($product->payment_structure ?? '') === 'Milestone-based' ? 'selected' : '' }}>
                            Milestone-based
                        </option>

                        <option value="Post-delivery / Arrears" {{ ($product->payment_structure ?? '') === 'Post-delivery / Arrears' ? 'selected' : '' }}>
                            Post-delivery / Arrears
                        </option>

                        <option value="Custom" {{ ($product->payment_structure ?? '') === 'Custom' ? 'selected' : '' }}>
                            Custom
                        </option>

                    </select>


                    <!-- CUSTOM PAYMENT -->

                    <div id="customPaymentWrapper" class="hidden pt-1">

                        <label class="block font-semibold text-slate-700 mb-1">

                            <span class="relative inline-flex items-center group cursor-pointer">

                                Custom Payment Terms

                                <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] ml-1"></i>

                                <span
                                    class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                                >
                                    Allows a custom payment arrangement to be specified when the standard payment structures do not apply.
                                </span>

                            </span>

                        </label>


                        <input
                            type="text"
                            id="customPaymentField"
                            name="payment_structure_custom"
                            value="{{ $product->payment_structure_custom ?? '' }}"
                            placeholder="Specify custom payment terms..."
                            class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500 text-xs"
                        >

                    </div>

                </div>


                <!-- PAYMENT NOTES -->

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        <span class="relative inline-flex items-center group cursor-pointer">

                            Payment &amp; Execution Notes

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px] ml-1"></i>

                            <span
                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Additional instructions or operational notes regarding payment handling, release conditions, approvals, or execution requirements.
                            </span>

                        </span>

                    </label>


                    <input
                        type="text"
                        name="payment_notes"
                        value="{{ $product->payment_notes ?? '' }}"
                        placeholder="e.g. Full payment required before release..."
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                    >

                </div>

            </div>

        </div>


        <!-- ===================================================== -->
        <!-- NAVIGATION -->
        <!-- ===================================================== -->

        <div class="flex justify-between items-center pt-4 border-t border-slate-100">

            <button
                type="button"
                onclick="switchWorkspaceTab(5)"
                class="text-xs font-semibold text-slate-500 hover:text-blue-600"
            >
                ← Back to Workflow
            </button>


            <button
                type="submit"
                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm transition"
            >
                Save &amp; Continue to Engagement

                <i class="fa-solid fa-arrow-right ml-1"></i>

            </button>

        </div>

    </form>

</div>


{{-- =========================================================
        STEP 7 — ENGAGEMENT
    ========================================================== --}}

@php

    /*
    |--------------------------------------------------------------------------
    | STEP 7 — LOAD SAVED ENGAGEMENT VALUES
    |--------------------------------------------------------------------------
    */

    $paymentStructure = old(
        'payment_structure',
        $product->payment_structure
    );

    $paymentStructureCustom = old(
        'payment_structure_custom',
        $product->payment_structure_custom
    );

    $instantiationExecutionMode = old(
        'instantiation_execution_mode',
        $product->instantiation_execution_mode
    );

    $recurrenceFrequency = old(
        'recurrence_frequency',
        $product->recurrence_frequency
    );

    $recurrenceFrequencyCustom = old(
        'recurrence_frequency_custom',
        $product->recurrence_frequency_custom
    );

    $billingFrequency = old(
        'billing_frequency',
        $product->billing_frequency
    );

    $billingFrequencyCustom = old(
        'billing_frequency_custom',
        $product->billing_frequency_custom
    );

    $reportingFrequency = old(
        'reporting_frequency',
        $product->reporting_frequency
    );

    $reportingFrequencyCustom = old(
        'reporting_frequency_custom',
        $product->reporting_frequency_custom
    );

    $autoCarryover = old(
        'auto_carryover',
        $product->auto_carryover
    );


    /*
    |--------------------------------------------------------------------------
    | CUSTOM FIELD VISIBILITY
    |--------------------------------------------------------------------------
    */

    $paymentCustomVisible = in_array(
        $paymentStructure,
        ['Quarterly', 'Custom', 'Others'],
        true
    );

    $recurrenceCustomVisible = in_array(
        $recurrenceFrequency,
        ['Quarterly', 'Custom', 'Others'],
        true
    );

    $billingCustomVisible = in_array(
        $billingFrequency,
        ['Custom', 'Others'],
        true
    );

    $reportingCustomVisible = in_array(
        $reportingFrequency,
        ['Custom', 'Others'],
        true
    );

@endphp


<div id="workspace-panel-7"
     class="workspace-panel bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-5 hidden">

    <form
        action="{{ route('products.updateEngagement', $product->id) }}"
        method="POST"
        class="space-y-5"
    >

        @csrf
        @method('PUT')


        <!-- ===================================================== -->
        <!-- HEADER -->
        <!-- ===================================================== -->

        <div class="border-b border-slate-100 pb-3">

            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                Engagement Configuration &amp; Recurrence Rules
            </h3>

            <p class="text-xs text-slate-500 mt-0.5">
                Preset delivery behavior set during Quick Add and automatic operational instantiation rules.
            </p>

        </div>


        <!-- ===================================================== -->
        <!-- CONFIGURED ENGAGEMENT SETTINGS -->
        <!-- ===================================================== -->

        <div class="border border-slate-200 rounded-xl p-4 space-y-4 text-xs">

            <h4 class="font-bold text-slate-800 uppercase text-[11px] tracking-wider">
                CONFIGURED ENGAGEMENT SETTINGS
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">


                <!-- ================================================= -->
                <!-- ENGAGEMENT DELIVERY BEHAVIOR -->
                <!-- ================================================= -->

                <div>

                    <label class="flex items-center gap-1 font-semibold text-slate-700 mb-1">

                        <span>
                            Engagement Delivery Behavior
                        </span>

                        <span class="relative inline-flex items-center group cursor-pointer">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                            <span
                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Defines the standard delivery behavior used when this product is instantiated as an engagement.
                            </span>

                        </span>

                    </label>

                    <input
                        type="text"
                        value="Project (Retainer / Recurring)"
                        readonly
                        class="w-full border border-slate-200 bg-slate-100 rounded-lg p-2.5 text-slate-600 font-medium cursor-not-allowed outline-none"
                    >

                </div>



                <!-- ================================================= -->
                <!-- INSTANTIATION EXECUTION MODE -->
                <!-- ================================================= -->

                <div>

                    <label class="flex items-center gap-1 font-semibold text-slate-700 mb-1">

                        <span>
                            Instantiation Execution Mode
                        </span>

                        <span class="relative inline-flex items-center group cursor-pointer">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                            <span
                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Defines whether engagement instances are automatically created, reviewed manually, or triggered on demand.
                            </span>

                        </span>

                    </label>

                    <select
                        name="instantiation_execution_mode"
                        class="w-full border border-slate-300 rounded-lg p-2.5 outline-none bg-white text-slate-800 focus:border-blue-500"
                    >

                        <option
                            value="Automatic System Instantiation (JIT)"
                            {{ $instantiationExecutionMode === 'Automatic System Instantiation (JIT)' ? 'selected' : '' }}
                        >
                            Automatic System Instantiation (JIT)
                        </option>

                        <option
                            value="Manual Review"
                            {{ $instantiationExecutionMode === 'Manual Review' ? 'selected' : '' }}
                        >
                            Manual Review
                        </option>

                        <option
                            value="On-Demand"
                            {{ $instantiationExecutionMode === 'On-Demand' ? 'selected' : '' }}
                        >
                            On-Demand
                        </option>

                    </select>

                </div>

            </div>

        </div>


        <!-- ===================================================== -->
        <!-- RECURRENCE CADENCES & RULES -->
        <!-- ===================================================== -->

        <div class="border border-slate-200 rounded-xl p-4 space-y-4 text-xs">

            <h4 class="font-bold text-slate-800 uppercase text-[11px] tracking-wider">
                RECURRENCE CADENCES &amp; RULES
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">


                <!-- ================================================= -->
                <!-- RECURRENCE FREQUENCY -->
                <!-- ================================================= -->

                <div>

                    <label class="flex items-center gap-1 font-semibold text-slate-700 mb-1">

                        <span>
                            Recurrence Frequency *
                        </span>

                        <span class="relative inline-flex items-center group cursor-pointer">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                            <span
                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Defines how frequently the engagement work is repeated or renewed.
                            </span>

                        </span>

                    </label>

                    <select
                        name="recurrence_frequency"
                        id="recurrence_frequency_select"
                        onchange="toggleCustomInput(this, 'recurrence_custom_wrapper')"
                        required
                        class="w-full border border-slate-300 rounded-lg p-2.5 outline-none bg-white text-slate-800 focus:border-blue-500"
                    >

                        <option value="">
                            -- Select Recurrence Frequency --
                        </option>

                        <option
                            value="Monthly"
                            {{ $recurrenceFrequency === 'Monthly' ? 'selected' : '' }}
                        >
                            Monthly
                        </option>

                        <option
                            value="Bi-Weekly / Semi-Monthly"
                            {{ $recurrenceFrequency === 'Bi-Weekly / Semi-Monthly' ? 'selected' : '' }}
                        >
                            Bi-Weekly / Semi-Monthly
                        </option>

                        <option
                            value="Quarterly"
                            {{ $recurrenceFrequency === 'Quarterly' ? 'selected' : '' }}
                        >
                            Quarterly
                        </option>

                        <option
                            value="Semi-Annual"
                            {{ $recurrenceFrequency === 'Semi-Annual' ? 'selected' : '' }}
                        >
                            Semi-Annual (Every 6 Months)
                        </option>

                        <option
                            value="Annual"
                            {{ $recurrenceFrequency === 'Annual' ? 'selected' : '' }}
                        >
                            Annual / Yearly
                        </option>

                        <option
                            value="Custom"
                            {{ $recurrenceFrequency === 'Custom' ? 'selected' : '' }}
                        >
                            Custom Cadence
                        </option>

                        <option
                            value="Others"
                            {{ $recurrenceFrequency === 'Others' ? 'selected' : '' }}
                        >
                            Others
                        </option>

                    </select>


                    <!-- Custom Input -->

                    <div
                        class="mt-2 {{ $recurrenceCustomVisible ? '' : 'hidden' }}"
                        id="recurrence_custom_wrapper"
                    >

                        <label class="flex items-center gap-1 font-semibold text-slate-700 mb-1">

                            <span>
                                Custom Recurrence Detail
                            </span>

                            <span class="relative inline-flex items-center group cursor-pointer">

                                <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                                <span
                                    class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                                >
                                    Specify the custom recurrence schedule or cadence for the engagement.
                                </span>

                            </span>

                        </label>

                        <input
                            type="text"
                            name="recurrence_frequency_custom"
                            value="{{ $recurrenceFrequencyCustom }}"
                            placeholder="Specify custom recurrence detail..."
                            class="w-full border border-slate-300 rounded-lg p-2.5 outline-none text-slate-800 focus:border-blue-500"
                        >

                    </div>

                </div>


                <!-- ================================================= -->
                <!-- BILLING FREQUENCY -->
                <!-- ================================================= -->

                <div>

                    <label class="flex items-center gap-1 font-semibold text-slate-700 mb-1">

                        <span>
                            Billing Frequency *
                        </span>

                        <span class="relative inline-flex items-center group cursor-pointer">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                            <span
                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Defines how frequently billing is processed or charged for the engagement.
                            </span>

                        </span>

                    </label>

                    <select
                        name="billing_frequency"
                        id="billing_frequency_select"
                        onchange="toggleCustomInput(this, 'billing_custom_wrapper')"
                        required
                        class="w-full border border-slate-300 rounded-lg p-2.5 outline-none bg-white text-slate-800 focus:border-blue-500"
                    >

                        <option value="">
                            -- Select Billing Frequency --
                        </option>

                        <option
                            value="Monthly"
                            {{ $billingFrequency === 'Monthly' ? 'selected' : '' }}
                        >
                            Monthly
                        </option>

                        <option
                            value="Quarterly"
                            {{ $billingFrequency === 'Quarterly' ? 'selected' : '' }}
                        >
                            Quarterly
                        </option>

                        <option
                            value="Semi-Annual"
                            {{ $billingFrequency === 'Semi-Annual' ? 'selected' : '' }}
                        >
                            Semi-Annual
                        </option>

                        <option
                            value="Annual"
                            {{ $billingFrequency === 'Annual' ? 'selected' : '' }}
                        >
                            Annual
                        </option>

                        <option
                            value="Upon Completion"
                            {{ $billingFrequency === 'Upon Completion' ? 'selected' : '' }}
                        >
                            Upon Completion
                        </option>

                        <option
                            value="Custom"
                            {{ $billingFrequency === 'Custom' ? 'selected' : '' }}
                        >
                            Custom
                        </option>

                        <option
                            value="Others"
                            {{ $billingFrequency === 'Others' ? 'selected' : '' }}
                        >
                            Others
                        </option>

                    </select>


                    <!-- Custom Input -->

                    <div
                        class="mt-2 {{ $billingCustomVisible ? '' : 'hidden' }}"
                        id="billing_custom_wrapper"
                    >

                        <label class="flex items-center gap-1 font-semibold text-slate-700 mb-1">

                            <span>
                                Custom Billing Detail
                            </span>

                            <span class="relative inline-flex items-center group cursor-pointer">

                                <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                                <span
                                    class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                                >
                                    Specify the custom billing schedule or billing instructions.
                                </span>

                            </span>

                        </label>

                        <input
                            type="text"
                            name="billing_frequency_custom"
                            value="{{ $billingFrequencyCustom }}"
                            placeholder="Specify custom billing detail..."
                            class="w-full border border-slate-300 rounded-lg p-2.5 outline-none text-slate-800 focus:border-blue-500"
                        >

                    </div>

                </div>


                <!-- ================================================= -->
                <!-- REPORTING FREQUENCY -->
                <!-- ================================================= -->

                <div>

                    <label class="flex items-center gap-1 font-semibold text-slate-700 mb-1">

                        <span>
                            Reporting Frequency *
                        </span>

                        <span class="relative inline-flex items-center group cursor-pointer">

                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                            <span
                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                            >
                                Defines how frequently engagement reports are prepared or delivered.
                            </span>

                        </span>

                    </label>

                    <select
                        name="reporting_frequency"
                        id="reporting_frequency_select"
                        onchange="toggleCustomInput(this, 'reporting_custom_wrapper')"
                        required
                        class="w-full border border-slate-300 rounded-lg p-2.5 outline-none bg-white text-slate-800 focus:border-blue-500"
                    >

                        <option value="">
                            -- Select Reporting Frequency --
                        </option>

                        <option
                            value="Monthly"
                            {{ $reportingFrequency === 'Monthly' ? 'selected' : '' }}
                        >
                            Monthly
                        </option>

                        <option
                            value="Quarterly"
                            {{ $reportingFrequency === 'Quarterly' ? 'selected' : '' }}
                        >
                            Quarterly
                        </option>

                        <option
                            value="Semi-Annual"
                            {{ $reportingFrequency === 'Semi-Annual' ? 'selected' : '' }}
                        >
                            Semi-Annual
                        </option>

                        <option
                            value="Annual"
                            {{ $reportingFrequency === 'Annual' ? 'selected' : '' }}
                        >
                            Annual
                        </option>

                        <option
                            value="Custom"
                            {{ $reportingFrequency === 'Custom' ? 'selected' : '' }}
                        >
                            Custom
                        </option>

                        <option
                            value="Others"
                            {{ $reportingFrequency === 'Others' ? 'selected' : '' }}
                        >
                            Others
                        </option>

                    </select>


                    <!-- Custom Input -->

                    <div
                        class="mt-2 {{ $reportingCustomVisible ? '' : 'hidden' }}"
                        id="reporting_custom_wrapper"
                    >

                        <label class="flex items-center gap-1 font-semibold text-slate-700 mb-1">

                            <span>
                                Custom Reporting Detail
                            </span>

                            <span class="relative inline-flex items-center group cursor-pointer">

                                <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                                <span
                                    class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                                >
                                    Specify the custom reporting schedule or reporting instructions.
                                </span>

                            </span>

                        </label>

                        <input
                            type="text"
                            name="reporting_frequency_custom"
                            value="{{ $reportingFrequencyCustom }}"
                            placeholder="Specify custom reporting detail..."
                            class="w-full border border-slate-300 rounded-lg p-2.5 outline-none text-slate-800 focus:border-blue-500"
                        >

                    </div>

                </div>

            </div>


            <!-- ================================================= -->
            <!-- AUTO CARRYOVER -->
            <!-- ================================================= -->

            <div class="pt-2">

                <label class="flex items-center gap-2 cursor-pointer">

                    <input
                        type="checkbox"
                        name="auto_carryover"
                        value="1"
                        {{ $autoCarryover ? 'checked' : '' }}
                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    >

                    <span class="font-semibold text-slate-700">
                        Auto-carryover incomplete tasks to the next recurring period
                    </span>

                    <span class="relative inline-flex items-center group cursor-pointer">

                        <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                        <span
                            class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                        >
                            When enabled, incomplete tasks are automatically carried over to the next recurring period.
                        </span>

                    </span>

                </label>

            </div>

        </div>


        <!-- ===================================================== -->
        <!-- VALIDATION ERRORS -->
        <!-- ===================================================== -->

        @if ($errors->any())

            <div class="border border-red-200 bg-red-50 rounded-lg p-3 text-xs text-red-700">

                <div class="font-bold mb-1">
                    Please correct the following:
                </div>

                <ul class="list-disc ml-5 space-y-1">

                    @foreach ($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        @endif


        <!-- ===================================================== -->
        <!-- NAVIGATION -->
        <!-- ===================================================== -->

        <div class="flex justify-between items-center pt-4 border-t border-slate-100">

            <button
                type="button"
                onclick="switchWorkspaceTab(6)"
                title="Bumalik sa Product Commercials."
                class="text-xs font-semibold text-slate-500 hover:text-blue-600"
            >
                ← Back to Commercials
            </button>


            <button
                type="submit"
                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm transition"
            >
                Save &amp; Continue to Reporting

                <i class="fa-solid fa-arrow-right ml-1"></i>
            </button>

        </div>

    </form>

</div>


<!-- ===================================================== -->
<!-- CUSTOM INPUT TOGGLE -->
<!-- ===================================================== -->

<script>

    function toggleCustomInput(selectElement, wrapperId) {

        const wrapper = document.getElementById(wrapperId);

        if (!wrapper) {
            return;
        }

        const selectedValue = selectElement.value;

        if (
            selectedValue === 'Quarterly' ||
            selectedValue === 'Custom' ||
            selectedValue === 'Others'
        ) {

            wrapper.classList.remove('hidden');

        } else {

            wrapper.classList.add('hidden');

            const input = wrapper.querySelector('input');

            if (input) {
                input.value = '';
            }

        }

    }

</script>

{{-- =========================================================
    STEP 8 — REPORTING
========================================================== --}}

@php
    $reportingFrequency = old(
        'reporting_frequency',
        $product->reporting_frequency ?? 'Upon Completion'
    );

    $projectReportingType = old(
        'project_reporting_type',
        $product->project_reporting_type ?? 'Final'
    );

    $reportScope = old(
        'report_scope',
        $product->report_scope ?? []
    );

    if (is_string($reportScope)) {
        $decodedReportScope = json_decode($reportScope, true);

        $reportScope = is_array($decodedReportScope)
            ? $decodedReportScope
            : [];
    }

    if (!is_array($reportScope)) {
        $reportScope = [];
    }
@endphp


<div id="workspace-panel-8"
     class="workspace-panel bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-5 hidden">

    <form
        action="{{ route('products.updateReporting', $product->id) }}"
        method="POST"
        class="space-y-5"
    >

        @csrf
        @method('PUT')


        {{-- =====================================================
            HEADER
        ====================================================== --}}

        <div class="border-b border-slate-100 pb-3">

            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                Reporting Configuration
            </h3>

            <p class="text-xs text-slate-500 mt-0.5">
                Configure automated reporting schedules and metrics.
            </p>

        </div>


        {{-- =====================================================
            REPORTING CONFIGURATION
        ====================================================== --}}

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">


            {{-- REPORTING FREQUENCY --}}

            <div>

                <label class="flex items-center gap-1 font-semibold text-slate-700 mb-1">

                    <span>
                        Reporting Frequency *
                    </span>

                    <span class="relative inline-flex items-center group cursor-pointer">

                        <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                        <span
                            class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                        >
                            Defines how frequently reporting is generated or delivered for the product, such as upon completion, monthly, quarterly, or annually.
                        </span>

                    </span>

                </label>

                <select
                    name="reporting_frequency"
                    required
                    class="w-full border border-slate-300 rounded-lg p-2.5 outline-none bg-white text-slate-800 focus:border-blue-500"
                >

                    <option
                        value="Upon Completion"
                        {{ $reportingFrequency === 'Upon Completion' ? 'selected' : '' }}
                    >
                        Upon Completion
                    </option>

                    <option
                        value="Monthly"
                        {{ $reportingFrequency === 'Monthly' ? 'selected' : '' }}
                    >
                        Monthly
                    </option>

                    <option
                        value="Quarterly"
                        {{ $reportingFrequency === 'Quarterly' ? 'selected' : '' }}
                    >
                        Quarterly
                    </option>

                    <option
                        value="Annual"
                        {{ $reportingFrequency === 'Annual' ? 'selected' : '' }}
                    >
                        Annual
                    </option>

                </select>

            </div>


            {{-- PROJECT REPORTING TYPE --}}

            <div>

                <label class="flex items-center gap-1 font-semibold text-slate-700 mb-1">

                    <span>
                        Project Reporting Type *
                    </span>

                    <span class="relative inline-flex items-center group cursor-pointer">

                        <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                        <span
                            class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none"
                        >
                            Defines the type of project report produced, such as a final report or an ongoing progress report.
                        </span>

                    </span>

                </label>

                <select
                    name="project_reporting_type"
                    required
                    class="w-full border border-slate-300 rounded-lg p-2.5 outline-none bg-white text-slate-800 focus:border-blue-500"
                >

                    <option
                        value="Final"
                        {{ $projectReportingType === 'Final' ? 'selected' : '' }}
                    >
                        Final
                    </option>

                    <option
                        value="Progress"
                        {{ $projectReportingType === 'Progress' ? 'selected' : '' }}
                    >
                        Progress
                    </option>

                </select>

            </div>

        </div>


        {{-- =====================================================
            REPORTING INFORMATION
        ====================================================== --}}

        <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 text-xs text-slate-600">

            Period-based reporting while preserving lifetime engagement history.
            Enables continuous tracking across recurring billing and activity cadences.

        </div>


        {{-- =====================================================
            REPORT CONTENT SCOPE
        ====================================================== --}}

        <div class="border border-slate-200 rounded-xl p-4 space-y-4 text-xs">

            <h4 class="flex items-center gap-1 font-bold text-slate-800 uppercase text-[11px] tracking-wider">

                <span>
                    REPORT CONTENT SCOPE
                </span>

                <span class="relative inline-flex items-center group cursor-pointer">

                    <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[11px]"></i>

                    <span
                        class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-72 max-w-[calc(100vw-2rem)] bg-slate-900 text-white text-xs p-2.5 rounded-lg shadow-xl z-[9999] text-center font-normal leading-relaxed pointer-events-none normal-case tracking-normal font-normal"
                    >
                        Select the information, work activity, financial data, and outcomes that should be included in generated product reports.
                    </span>

                </span>

            </h4>


            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">


                {{-- ACTIVITIES --}}

                <label class="flex items-center gap-2 cursor-pointer">

                    <input
                        type="checkbox"
                        name="report_scope[]"
                        value="Activities"
                        {{ in_array('Activities', $reportScope, true) ? 'checked' : '' }}
                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    >

                    <span class="font-medium text-slate-700">
                        Activities
                    </span>

                </label>


                {{-- COMPLETED TASKS --}}

                <label class="flex items-center gap-2 cursor-pointer">

                    <input
                        type="checkbox"
                        name="report_scope[]"
                        value="Completed tasks"
                        {{ in_array('Completed tasks', $reportScope, true) ? 'checked' : '' }}
                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    >

                    <span class="font-medium text-slate-700">
                        Completed tasks
                    </span>

                </label>


                {{-- PENDING / CARRY-FORWARD WORK --}}

                <label class="flex items-center gap-2 cursor-pointer">

                    <input
                        type="checkbox"
                        name="report_scope[]"
                        value="Pending/carry-forward work"
                        {{ in_array('Pending/carry-forward work', $reportScope, true) ? 'checked' : '' }}
                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    >

                    <span class="font-medium text-slate-700">
                        Pending/carry-forward work
                    </span>

                </label>


                {{-- HOURS --}}

                <label class="flex items-center gap-2 cursor-pointer">

                    <input
                        type="checkbox"
                        name="report_scope[]"
                        value="Hours"
                        {{ in_array('Hours', $reportScope, true) ? 'checked' : '' }}
                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    >

                    <span class="font-medium text-slate-700">
                        Hours
                    </span>

                </label>


                {{-- DELIVERABLES --}}

                <label class="flex items-center gap-2 cursor-pointer">

                    <input
                        type="checkbox"
                        name="report_scope[]"
                        value="Deliverables"
                        {{ in_array('Deliverables', $reportScope, true) ? 'checked' : '' }}
                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    >

                    <span class="font-medium text-slate-700">
                        Deliverables
                    </span>

                </label>


                {{-- CLIENT REQUESTS --}}

                <label class="flex items-center gap-2 cursor-pointer">

                    <input
                        type="checkbox"
                        name="report_scope[]"
                        value="Client requests"
                        {{ in_array('Client requests', $reportScope, true) ? 'checked' : '' }}
                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    >

                    <span class="font-medium text-slate-700">
                        Client requests
                    </span>

                </label>


                {{-- ISSUES --}}

                <label class="flex items-center gap-2 cursor-pointer">

                    <input
                        type="checkbox"
                        name="report_scope[]"
                        value="Issues"
                        {{ in_array('Issues', $reportScope, true) ? 'checked' : '' }}
                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    >

                    <span class="font-medium text-slate-700">
                        Issues
                    </span>

                </label>


                {{-- EXPENSES --}}

                <label class="flex items-center gap-2 cursor-pointer">

                    <input
                        type="checkbox"
                        name="report_scope[]"
                        value="Expenses"
                        {{ in_array('Expenses', $reportScope, true) ? 'checked' : '' }}
                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    >

                    <span class="font-medium text-slate-700">
                        Expenses
                    </span>

                </label>


                {{-- RECOMMENDATIONS --}}

                <label class="flex items-center gap-2 cursor-pointer">

                    <input
                        type="checkbox"
                        name="report_scope[]"
                        value="Recommendations"
                        {{ in_array('Recommendations', $reportScope, true) ? 'checked' : '' }}
                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    >

                    <span class="font-medium text-slate-700">
                        Recommendations
                    </span>

                </label>


                {{-- NEXT-PERIOD WORK --}}

                <label class="flex items-center gap-2 cursor-pointer md:col-span-3 pt-1">

                    <input
                        type="checkbox"
                        name="report_scope[]"
                        value="Next-period work"
                        {{ in_array('Next-period work', $reportScope, true) ? 'checked' : '' }}
                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    >

                    <span class="font-medium text-slate-700">
                        Next-period work
                    </span>

                </label>

            </div>

        </div>


        {{-- =====================================================
            NAVIGATION
        ====================================================== --}}

        <div class="flex justify-between items-center pt-4 border-t border-slate-100">

            <button
                type="button"
                onclick="switchWorkspaceTab(7)"
                class="text-xs font-semibold text-slate-500 hover:text-blue-600"
            >
                ← Back to Engagement
            </button>


            <div class="flex items-center gap-2">

                <button
                    type="submit"
                    class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm transition"
                >
                    Save &amp; Continue to Automation

                    <i class="fa-solid fa-arrow-right ml-1"></i>
                </button>

            </div>

        </div>

    </form>

</div>

{{-- =========================================================
    STEP 9 — AUTOMATION
========================================================== --}}

<div id="workspace-panel-9"
     class="workspace-panel bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-5 hidden">

    @php
        $baseReferenceDate = old(
            'base_reference_date',
            $product->base_reference_date
        );

        $leadTimeGeneration = old(
            'lead_time_generation',
            $product->lead_time_generation
        );

        $defaultAutoAssignmentRule = old(
            'default_auto_assignment_rule',
            $product->default_auto_assignment_rule
        );

        $autoCarryoverAutomation = old(
            'auto_carryover_automation',
            $product->auto_carryover_automation
        );

        $internalReminderTrigger = old(
            'internal_reminder_trigger',
            $product->internal_reminder_trigger
        );

        $clientFollowupCadence = old(
            'client_followup_cadence',
            $product->client_followup_cadence
        );

        $notificationChannel = old(
            'notification_channel',
            $product->notification_channel
        );

        $overdueEscalationThreshold = old(
            'overdue_escalation_threshold',
            $product->overdue_escalation_threshold
        );

        $escalationRecipientRole = old(
            'escalation_recipient_role',
            $product->escalation_recipient_role
        );

        $missingRequirementsGateRule = old(
            'missing_requirements_gate_rule',
            $product->missing_requirements_gate_rule
        );
    @endphp


    <form
        action="{{ route('products.updateAutomation', $product->id) }}"
        method="POST"
    >

        @csrf
        @method('PUT')


        <!-- ===================================================== -->
        <!-- HEADER -->
        <!-- ===================================================== -->

        <div class="border-b border-slate-100 pb-3 flex justify-between items-center">

            <div>

                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                    Automation, Instantiation &amp; Governance Engine
                </h3>

                <p class="text-xs text-slate-500 mt-0.5">
                    Configure scheduled activity generation, escalation matrices, reminder cadences, and dependency gates.
                </p>

            </div>

            <span class="bg-blue-50 text-blue-700 border border-blue-200 px-3 py-1 rounded-md text-[11px] font-semibold">

                <i class="fa-solid fa-bolt mr-1"></i>

                Automation Engine Active

            </span>

        </div>


        <!-- ===================================================== -->
        <!-- TASK & PERIOD INSTANTIATION ENGINE -->
        <!-- ===================================================== -->

        <div class="border border-slate-200 rounded-xl p-4 space-y-4 text-xs mt-4">

            <h4 class="font-bold text-slate-800 uppercase text-[11px] tracking-wider">

                <i class="fa-solid fa-gears text-blue-600 mr-1"></i>

                TASK &amp; PERIOD INSTANTIATION ENGINE

            </h4>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">


                <!-- BASE REFERENCE DATE -->

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        <span class="inline-flex items-center gap-1">

                            Base Reference Date

                            <span class="relative inline-flex items-center group">

                                <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[10px] cursor-pointer"></i>

                                <span
                                    class="absolute left-full top-1/2 -translate-y-1/2 ml-2 hidden group-hover:block w-80 bg-slate-900 text-white text-[11px] px-3 py-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed pointer-events-none"
                                >
                                    Determines which date the system will use as the starting reference when generating scheduled activities and recurring periods.
                                </span>

                            </span>

                        </span>

                    </label>

                    <select
                        name="base_reference_date"
                        class="w-full border border-slate-300 rounded-lg p-2.5 outline-none bg-white text-slate-800 focus:border-blue-500"
                    >

                        <option value="">
                            --Select Base Reference Date--
                        </option>

                        <option
                            value="period_start"
                            {{ $baseReferenceDate === 'period_start' ? 'selected' : '' }}
                        >
                            Period Start Date
                        </option>

                        <option
                            value="engagement_start"
                            {{ $baseReferenceDate === 'engagement_start' ? 'selected' : '' }}
                        >
                            Engagement Start Date
                        </option>

                        <option
                            value="contract_signing"
                            {{ $baseReferenceDate === 'contract_signing' ? 'selected' : '' }}
                        >
                            Contract Signing Date
                        </option>

                    </select>

                </div>


                <!-- LEAD TIME GENERATION -->

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        <span class="inline-flex items-center gap-1">

                            Lead Time Generation (Days)

                            <span class="relative inline-flex items-center group">

                                <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[10px] cursor-pointer"></i>

                                <span
                                    class="absolute left-full top-1/2 -translate-y-1/2 ml-2 hidden group-hover:block w-80 bg-slate-900 text-white text-[11px] px-3 py-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed"
                                >
                                    Defines how many days before the reference date the system should generate scheduled activities or tasks.
                                </span>

                            </span>

                        </span>

                    </label>

                    <input
                        type="number"
                        name="lead_time_generation"
                        value="{{ $leadTimeGeneration }}"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                    >

                </div>

            </div>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">


                <!-- DEFAULT AUTO ASSIGNMENT -->

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        <span class="inline-flex items-center gap-1">

                            Default Auto-Assignment Rule

                            <span class="relative inline-flex items-center group">

                                <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[10px] cursor-pointer"></i>

                                <span
                                    class="absolute left-full top-1/2 -translate-y-1/2 ml-2 hidden group-hover:block w-80 bg-slate-900 text-white text-[11px] px-3 py-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed"
                                >
                                    Determines who will automatically receive newly generated activities or tasks.
                                </span>

                            </span>

                        </span>

                    </label>

                    <select
                        name="default_auto_assignment_rule"
                        class="w-full border border-slate-300 rounded-lg p-2.5 outline-none bg-white text-slate-800 focus:border-blue-500"
                    >

                        <option value="">
                            --Select Assignment Rule--
                        </option>

                        <option
                            value="engagement_lead"
                            {{ $defaultAutoAssignmentRule === 'engagement_lead' ? 'selected' : '' }}
                        >
                            Assign to Primary Engagement Lead
                        </option>

                        <option
                            value="service_area_pool"
                            {{ $defaultAutoAssignmentRule === 'service_area_pool' ? 'selected' : '' }}
                        >
                            Unassigned (Service Area Pool)
                        </option>

                        <option
                            value="previous_assignee"
                            {{ $defaultAutoAssignmentRule === 'previous_assignee' ? 'selected' : '' }}
                        >
                            Inherit Previous Period Assignee
                        </option>

                    </select>

                </div>


                <!-- AUTO CARRYOVER -->

                <div class="flex items-center pt-5">

                    <label class="flex items-center gap-2 cursor-pointer">

                        <input
                            type="checkbox"
                            name="auto_carryover_automation"
                            value="1"
                            {{ $autoCarryoverAutomation ? 'checked' : '' }}
                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        >

                        <span class="font-semibold text-slate-700 inline-flex items-center gap-1">

                            Auto-carryover incomplete tasks to next recurring period

                            <span class="relative inline-flex items-center group">

                                <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[10px] cursor-pointer"></i>

                                <span
                                    class="absolute left-full top-1/2 -translate-y-1/2 ml-2 hidden group-hover:block w-80 bg-slate-900 text-white text-[11px] px-3 py-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed"
                                >
                                    Automatically moves unfinished tasks into the next recurring period instead of leaving them behind.
                                </span>

                            </span>

                        </span>

                    </label>

                </div>

            </div>

        </div>


        <!-- ===================================================== -->
        <!-- NOTIFICATION & REMINDER CADENCE MATRIX -->
        <!-- ===================================================== -->

        <div class="border border-slate-200 rounded-xl p-4 space-y-4 text-xs mt-4">

            <h4 class="font-bold text-slate-800 uppercase text-[11px] tracking-wider">

                <i class="fa-solid fa-bell text-blue-600 mr-1"></i>

                NOTIFICATION &amp; REMINDER CADENCE MATRIX

            </h4>


            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">


                <!-- INTERNAL REMINDER -->

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        <span class="inline-flex items-center gap-1">

                            Internal Reminder Trigger

                            <span class="relative inline-flex items-center group">

                                <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[10px] cursor-pointer"></i>

                                <span
                                    class="absolute left-full top-1/2 -translate-y-1/2 ml-2 hidden group-hover:block w-80 bg-slate-900 text-white text-[11px] px-3 py-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed"
                                >
                                    Specifies how many days before the applicable event the system should send an internal reminder.
                                </span>

                            </span>

                        </span>

                    </label>

                    <input
                        type="number"
                        name="internal_reminder_trigger"
                        value="{{ $internalReminderTrigger }}"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                    >

                </div>


                <!-- CLIENT FOLLOW-UP -->

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        <span class="inline-flex items-center gap-1">

                            Client Follow-up Cadence

                            <span class="relative inline-flex items-center group">

                                <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[10px] cursor-pointer"></i>

                                <span
                                    class="absolute left-full top-1/2 -translate-y-1/2 ml-2 hidden group-hover:block w-80 bg-slate-900 text-white text-[11px] px-3 py-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed"
                                >
                                    Controls how frequently the system sends automated follow-up reminders to the client.
                                </span>

                            </span>

                        </span>

                    </label>

                    <select
                        name="client_followup_cadence"
                        class="w-full border border-slate-300 rounded-lg p-2.5 outline-none bg-white text-slate-800 focus:border-blue-500"
                    >

                        <option value="">
                            --Select Client Reminder--
                        </option>

                        <option
                            value="daily"
                            {{ $clientFollowupCadence === 'daily' ? 'selected' : '' }}
                        >
                            Daily Automated Email
                        </option>

                        <option
                            value="every_3_days"
                            {{ $clientFollowupCadence === 'every_3_days' ? 'selected' : '' }}
                        >
                            Every 3 Days
                        </option>

                        <option
                            value="weekly"
                            {{ $clientFollowupCadence === 'weekly' ? 'selected' : '' }}
                        >
                            Weekly Summary
                        </option>

                        <option
                            value="disabled"
                            {{ $clientFollowupCadence === 'disabled' ? 'selected' : '' }}
                        >
                            Disabled / Manual Only
                        </option>

                    </select>

                </div>


                <!-- NOTIFICATION CHANNEL -->

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        <span class="inline-flex items-center gap-1">

                            Notification Channel

                            <span class="relative inline-flex items-center group">

                                <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[10px] cursor-pointer"></i>

                                <span
                                    class="absolute left-full top-1/2 -translate-y-1/2 ml-2 hidden group-hover:block w-80 bg-slate-900 text-white text-[11px] px-3 py-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed"
                                >
                                    Determines where automated system notifications and reminders will be delivered.
                                </span>

                            </span>

                        </span>

                    </label>

                    <select
                        name="notification_channel"
                        class="w-full border border-slate-300 rounded-lg p-2.5 outline-none bg-white text-slate-800 focus:border-blue-500"
                    >

                        <option value="">
                            --Select Notification Channel--
                        </option>

                        <option
                            value="email_and_app"
                            {{ $notificationChannel == 'email_and_app' ? 'selected' : '' }}
                        >
                            Email &amp; In-App Dashboard Notification
                        </option>

                        <option
                            value="app_only"
                            {{ $notificationChannel == 'app_only' ? 'selected' : '' }}
                        >
                            In-App Dashboard Only
                        </option>

                        <option
                            value="email_only"
                            {{ $notificationChannel == 'email_only' ? 'selected' : '' }}
                        >
                            Email Only
                        </option>

                    </select>

                </div>

            </div>

        </div>


        <!-- ===================================================== -->
        <!-- ESCALATION & REQUIREMENT DEPENDENCY CONTROLS -->
        <!-- ===================================================== -->

        <div class="border border-slate-200 rounded-xl p-4 space-y-4 text-xs mt-4">

            <h4 class="font-bold text-slate-800 uppercase text-[11px] tracking-wider">

                <i class="fa-solid fa-triangle-exclamation text-blue-600 mr-1"></i>

                ESCALATION &amp; REQUIREMENT DEPENDENCY CONTROLS

            </h4>


            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">


                <!-- OVERDUE ESCALATION -->

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        <span class="inline-flex items-center gap-1">

                            Overdue Escalation Threshold (Days)

                            <span class="relative inline-flex items-center group">

                                <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[10px] cursor-pointer"></i>

                                <span
                                    class="absolute left-full top-1/2 -translate-y-1/2 ml-2 hidden group-hover:block w-80 bg-slate-900 text-white text-[11px] px-3 py-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed"
                                >
                                    Defines how many days an activity may remain overdue before the system triggers an escalation.
                                </span>

                            </span>

                        </span>

                    </label>

                    <input
                        type="number"
                        name="overdue_escalation_threshold"
                        value="{{ $overdueEscalationThreshold }}"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"
                    >

                </div>


                <!-- ESCALATION RECIPIENT -->

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        <span class="inline-flex items-center gap-1">

                            Escalation Recipient Role

                            <span class="relative inline-flex items-center group">

                                <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[10px] cursor-pointer"></i>

                                <span
                                    class="absolute left-full top-1/2 -translate-y-1/2 ml-2 hidden group-hover:block w-80 bg-slate-900 text-white text-[11px] px-3 py-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed"
                                >
                                    Identifies the organizational role that should receive escalated notifications for overdue activities.
                                </span>

                            </span>

                        </span>

                    </label>

                    <select
                        name="escalation_recipient_role"
                        class="w-full border border-slate-300 rounded-lg p-2.5 outline-none bg-white text-slate-800 focus:border-blue-500"
                    >

                        <option value="">
                            --Select Escalation--
                        </option>

                        <option
                            value="engagement_manager"
                            {{ $escalationRecipientRole == 'engagement_manager' ? 'selected' : '' }}
                        >
                            Engagement Manager
                        </option>

                        <option
                            value="service_area_head"
                            {{ $escalationRecipientRole == 'service_area_head' ? 'selected' : '' }}
                        >
                            Service Area Head
                        </option>

                        <option
                            value="quality_reviewer"
                            {{ $escalationRecipientRole == 'quality_reviewer' ? 'selected' : '' }}
                        >
                            Quality Reviewer
                        </option>

                    </select>

                </div>


                <!-- REQUIREMENT GATE -->

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">

                        <span class="inline-flex items-center gap-1">

                            Missing Requirements Gate Rule

                            <span class="relative inline-flex items-center group">

                                <i class="fa-solid fa-circle-info text-slate-400 hover:text-slate-600 text-[10px] cursor-pointer"></i>

                                <span
                                    class="absolute left-full top-1/2 -translate-y-1/2 ml-2 hidden group-hover:block w-80 bg-slate-900 text-white text-[11px] px-3 py-2.5 rounded-lg shadow-xl z-50 text-center font-normal leading-relaxed"
                                >
                                    Determines whether execution should be blocked or allowed when mandatory client requirements are still missing.
                                </span>

                            </span>

                        </span>

                    </label>

                    <select
                        name="missing_requirements_gate_rule"
                        class="w-full border border-slate-300 rounded-lg p-2.5 outline-none bg-white text-slate-800 focus:border-blue-500"
                    >

                        <option value="">
                            --Select Requirement Gate Rule--
                        </option>

                        <option
                            value="block_execution"
                            {{ $missingRequirementsGateRule == 'block_execution' ? 'selected' : '' }}
                        >
                            Strict Gate: Block Execution until Mandatory Uploaded
                        </option>

                        <option
                            value="warn_only"
                            {{ $missingRequirementsGateRule == 'warn_only' ? 'selected' : '' }}
                        >
                            Warning Only: Allow Execution with System Alert
                        </option>

                        <option
                            value="none"
                            {{ $missingRequirementsGateRule == 'none' ? 'selected' : '' }}
                        >
                            No Restriction
                        </option>

                    </select>

                </div>

            </div>

        </div>


        <!-- ===================================================== -->
        <!-- NAVIGATION -->
        <!-- ===================================================== -->

        <div class="flex justify-between items-center pt-4 border-t border-slate-100 mt-4">

            <button
                type="button"
                onclick="switchWorkspaceTab(8)"
                class="text-xs font-semibold text-slate-500 hover:text-blue-600"
            >
                ← Back to Reporting
            </button>


            <button
                type="submit"
                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm transition"
            >

                Save &amp; Continue to Terms

                <i class="fa-solid fa-arrow-right ml-1"></i>

            </button>

        </div>

    </form>

</div>

{{-- =========================================================
    STEP 10 — PRODUCT TERMS & AGREEMENTS TAB CONTENT
========================================================== --}}
<div
    id="workspace-panel-10"
    class="workspace-panel bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-6 hidden"
>
    <!-- Header with Action Buttons -->
    <div class="flex justify-between items-center border-b border-slate-100 pb-4">
        <div>
            <h3 class="text-sm font-bold text-slate-800">Product Terms & Agreements</h3>
            <p class="text-xs text-slate-500 mt-0.5">Manage custom terms, legal clauses, and conditions applicable to this product.</p>
        </div>
        <div class="flex items-center space-x-2">
            <button 
                type="button" 
                onclick="document.getElementById('termsTemplateLibraryModal').classList.remove('hidden')" 
                class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium text-xs px-3 py-2 rounded-lg shadow-sm transition cursor-pointer"
            >
                Template Library
            </button>
            <button
                type="button"
                onclick="document.getElementById('selectTemplateModal').classList.remove('hidden')"
                class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium text-xs px-3 py-2 rounded-lg shadow-sm transition cursor-pointer"
            >
                Select Template
            </button>
            <button
                type="button"
                onclick="document.getElementById('addTermModal').classList.remove('hidden')"
                class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-4 py-2 rounded-lg shadow-sm transition flex items-center space-x-1.5 cursor-pointer"
            >
                <i class="fa-solid fa-plus text-[10px]"></i>
                <span>Add Term</span>
            </button>
        </div>
    </div>
    
<!-- Terms Cards List -->
<div class="space-y-3">
    @forelse($product->terms ?? [] as $term)
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-2 hover:border-slate-300 transition">
            <div class="flex justify-between items-start">
                <div class="flex items-center space-x-2">
                    <span class="text-slate-400">📄</span>
                    <h4 class="font-bold text-slate-800 text-sm">{{ $term->title }}</h4>
                </div>
                
                <div class="flex items-center space-x-3 text-xs">
                    <span class="px-2 py-0.5 bg-slate-100 rounded text-[10px] font-semibold text-slate-600 border border-slate-200 uppercase tracking-wider">
                        {{ str_replace('_', ' ', $term->scope ?? 'Service Specific') }}
                    </span>
                    <span class="text-slate-400">v1.0</span>
                    
                    <!-- Edit Button -->
                    <button 
                        type="button" 
                        onclick="openEditTermModal({{ $term->id }}, {{ json_encode($term->title) }}, {{ json_encode($term->scope) }}, {{ json_encode($term->content) }}, '{{ route('product-terms.update', $term->id) }}')" 
                        class="text-blue-600 hover:underline font-medium cursor-pointer"
                    >
                        Edit
                    </button>
                    
                    <!-- Duplicate Button -->
                    <form action="{{ route('product-terms.duplicate', [$product->id, $term->id]) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-blue-600 hover:underline font-medium cursor-pointer">
                            Duplicate
                        </button>
                    </form>

                    <!-- Save as Template Button -->
                    <button 
                        type="button" 
                        data-title="{{ $term->title }}"
                        data-content="{{ $term->content ?? '' }}"
                        data-scope="{{ $term->scope ?? 'global' }}"
                        data-url="{{ route('product-terms.save-as-template', [$product->id, $term->id]) }}"
                        class="save-template-btn text-blue-600 hover:underline font-medium cursor-pointer text-xs"
                    >
                        Save as Template
                    </button>

                    <!-- Disable Button -->
                    <button 
                        type="button"
                        onclick="if(confirm('Are you sure you want to delete this term?')) { document.getElementById('delete-term-{{ $term->id }}').submit(); }"
                      class="text-red-600 hover:underline font-medium cursor-pointer"
                  >
                      Disable
                </button>
                    
                    <form id="delete-term-{{ $term->id }}" action="{{ route('product-terms.destroy', $term->id) }}" method="POST" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
            
            <div class="text-xs text-slate-600 pl-6">
                {{ $term->content }}
            </div>
        </div>
    @empty
        <div class="bg-white border border-slate-200 rounded-xl p-8 text-center text-slate-400 text-xs">
            No terms and agreements have been resolved or added yet.
        </div>
    @endforelse
</div>

<!-- Footer Navigation Buttons -->
<div class="flex justify-between items-center bg-white p-4 rounded-xl border border-slate-200 shadow-sm mt-6">
    <a href="{{ route('products.workspace', ['id' => $product->id, 'tab' => 9, 'mode' => 'edit']) }}" class="text-xs text-slate-600 hover:text-slate-900 font-medium flex items-center space-x-1">
        <span>&larr; Back to Automation</span>
    </a>
    <a href="{{ route('products.workspace', ['id' => $product->id, 'tab' => 11, 'mode' => 'edit']) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-4 py-2 rounded-lg shadow-sm transition flex items-center space-x-1">
        <span>Save & Continue to Usage & Performance</span>
        <span>&rarr;</span>
    </a>
</div>



<!-- ========================================== -->
<!-- ADD PRODUCT TERM MODAL                     -->
<!-- ========================================== -->
<div id="addTermModal" class="fixed inset-0 bg-slate-900/65 backdrop-blur-sm hidden flex justify-center items-center p-4 z-[9999]">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 space-y-4">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="font-bold text-slate-800 text-sm">Add Product Term</h3>
            <button 
                type="button" 
                onclick="document.getElementById('addTermModal').classList.add('hidden')" 
                class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer"
            >
                &times;
            </button>
        </div>
        
        <form action="{{ route('product-terms.store', $product->id) }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Term Title / Heading *</label>
                <input 
                    type="text" 
                    name="title" 
                    required 
                    placeholder="e.g. Intellectual Property Rights" 
                    class="w-full border border-slate-300 rounded-lg px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500 outline-none bg-white text-slate-700"
                >
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Scope</label>
                <select 
                    name="scope" 
                    class="w-full border border-slate-300 rounded-lg px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500 outline-none bg-white text-slate-700 cursor-pointer"
                >
                    <option value="Select Scope">Select Scope</option>
                    <option value="Service Specific">Service Specific</option>
                    <option value="Global Scope">Global Scope</option>
                    <option value="Service Area Scope">Service Area Scope</option>
                    <option value="Category">Category</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Term Content / Clause Details *</label>
                <textarea 
                    name="content" 
                    rows="4" 
                    required 
                    placeholder="Specify the full legal or operating clause..." 
                    class="w-full border border-slate-300 rounded-lg px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500 outline-none bg-white text-slate-700"
                ></textarea>
            </div>
            <div class="flex justify-end space-x-2 pt-3 border-t">
                <button 
                    type="button" 
                    onclick="document.getElementById('addTermModal').classList.add('hidden')" 
                    class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-medium rounded-lg hover:bg-slate-50 cursor-pointer"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg shadow-sm cursor-pointer"
                >
                    Save Term
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- APPLY TERMS TEMPLATE MODAL                 -->
<!-- ========================================== -->
<div id="selectTemplateModal" class="fixed inset-0 bg-slate-900/65 backdrop-blur-sm hidden flex justify-center items-center p-4 z-[9999]">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 space-y-4">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="font-bold text-slate-800 text-sm">Apply Terms Template</h3>
            <button 
                type="button" 
                onclick="document.getElementById('selectTemplateModal').classList.add('hidden')" 
                class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer"
            >
                &times;
            </button>
        </div>
        
        <form action="{{ route('product-terms.apply-template', $product->id) }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Choose Template *</label>
                <select 
                    name="template_id" 
                    required 
                    class="w-full border border-slate-300 rounded-lg px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500 outline-none bg-white text-slate-700 cursor-pointer"
                >
                    <option value="">Select Template</option>
                    @foreach($termsTemplates ?? [] as $template)
                        <option value="{{ $template->id ?? $template->uuid ?? '' }}">{{ $template->name ?? $template->title ?? 'Untitled Template' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Application Mode *</label>
                <select 
                    name="application_mode" 
                    required 
                    class="w-full border border-slate-300 rounded-lg px-3.5 py-2 text-xs focus:ring-2 focus:ring-blue-500 outline-none bg-white text-slate-700 cursor-pointer"
                >
                    <option value="add">A. Add to Existing (Keep current terms)</option>
                    <option value="replace">B. Replace Existing (Disable current terms & replace)</option>
                </select>
            </div>
            <div class="flex justify-end space-x-2 pt-3 border-t">
                <button 
                    type="button" 
                    onclick="document.getElementById('selectTemplateModal').classList.add('hidden')" 
                    class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-medium rounded-lg hover:bg-slate-50 cursor-pointer"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg shadow-sm cursor-pointer"
                >
                    Apply Template
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- TERMS TEMPLATE LIBRARY MODAL               -->
<!-- ========================================== -->
<div id="termsTemplateLibraryModal" class="fixed inset-0 bg-slate-900/65 backdrop-blur-sm hidden flex justify-center items-center p-4 z-[9999]">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6 space-y-4 max-h-[90vh] flex flex-col">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="font-bold text-slate-800 text-base">Terms Template Library</h3>
            <button 
                type="button" 
                onclick="document.getElementById('termsTemplateLibraryModal').classList.add('hidden')" 
                class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer"
            >
                &times;
            </button>
        </div>

        <!-- List Container -->
        <div class="overflow-y-auto space-y-3 pr-1 flex-1">
            @forelse($termsTemplates ?? [] as $template)
                <div class="border border-slate-200 rounded-lg p-4 flex justify-between items-center bg-white shadow-sm hover:border-slate-300 transition">
                    <div class="space-y-1">
                        <h4 class="font-semibold text-slate-800 text-sm">{{ $template->name ?? 'Untitled Template' }}</h4>
                        <div class="flex items-center space-x-3 text-xs text-slate-500">
                            <span>Terms Count: <strong class="text-slate-700">{{ $template->items->count() ?? 0 }}</strong></span>
                            <span>•</span>
                            <span>Status: <strong class="uppercase text-slate-700">{{ $template->status ?? 'ACTIVE' }}</strong></span>
                            <span>•</span>
                            <span>Created: {{ $template->created_at ? $template->created_at->format('M d, Y') : 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <!-- Apply Button -->
                        <button 
                            type="button"
                            class="px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 font-medium text-xs rounded-md cursor-pointer transition"
                            onclick="document.getElementById('selectTemplateModal').classList.remove('hidden'); document.getElementById('termsTemplateLibraryModal').classList.add('hidden');"
                        >
                            Apply
                        </button>

                        <!-- Disable Button -->
                        <button 
                            type="button"
                            onclick="if(confirm('Are you sure you want to disable this template?')) { document.getElementById('disable-form-{{ $template->id }}').submit(); }"
                            class="px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 font-medium text-xs rounded-md cursor-pointer transition"
                        >
                            Disable
                        </button>

                        <form id="disable-form-{{ $template->id }}" action="{{ route('terms-templates.disable', $template->id) }}" method="POST" style="display: none;">
                            @csrf
                            @method('PATCH')
                        </form>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-slate-400 text-xs">
                    No active terms templates found.
                </div>
            @endforelse
        </div>

        <div class="flex justify-end pt-3 border-t">
            <button 
                type="button" 
                onclick="document.getElementById('termsTemplateLibraryModal').classList.add('hidden')" 
                class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-medium rounded-lg hover:bg-slate-50 cursor-pointer"
            >
                Close
            </button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- DISABLE TEMPLATE CONFIRMATION MODAL        -->
<!-- ========================================== -->
<div id="disableTemplateModal" class="fixed inset-0 bg-slate-900/65 backdrop-blur-sm hidden flex justify-center items-center p-4 z-[10000]">
    <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full p-6 space-y-4 text-center">
        <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto text-xl font-bold">
            !
        </div>
        <div class="space-y-1">
            <h3 class="font-bold text-slate-800 text-sm">Are you sure you want to disable this template?</h3>
            <p id="disableTemplateName" class="text-xs text-slate-500 font-medium"></p>
        </div>

        <form id="disableTemplateForm" method="POST" class="flex justify-center space-x-2 pt-2">
            @csrf
            @method('PATCH')
            <button 
                type="button" 
                onclick="document.getElementById('disableTemplateModal').classList.add('hidden')" 
                class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-medium rounded-lg hover:bg-slate-50 cursor-pointer"
            >
                Cancel
            </button>
            <button 
                type="submit" 
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg shadow-sm cursor-pointer"
            >
                Yes, Disable
            </button>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- EDIT TERM MODAL                            -->
<!-- ========================================== -->
<div id="editTermModal" class="fixed inset-0 bg-slate-900/65 backdrop-blur-sm hidden flex justify-center items-center p-4 z-[9999]">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 space-y-4">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="font-bold text-slate-800 text-base">Edit Product Term</h3>
            <button 
                type="button" 
                onclick="document.getElementById('editTermModal').classList.add('hidden')" 
                class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer"
            >
                &times;
            </button>
        </div>

        <form id="editTermForm" action="" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Title *</label>
                <input type="text" id="edit_title" name="title" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Scope *</label>
                <select id="edit_scope" name="scope" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-blue-500 bg-white">
                    <option value="global">Global Scope</option>
                    <option value="service_specific">Service Specific</option>
                    <option value="service_area">Service Area Scope</option>
                    <option value="category">Category</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Content *</label>
                <textarea id="edit_content" name="content" rows="4" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-blue-500"></textarea>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t">
                <button 
                    type="button" 
                    onclick="document.getElementById('editTermModal').classList.add('hidden')" 
                    class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-medium rounded-lg hover:bg-slate-50 cursor-pointer"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg shadow-sm cursor-pointer"
                >
                    Update Term
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===================================================== -->
<!-- SAVE AS TEMPLATE MODAL -->
<!-- ===================================================== -->
<div id="saveAsTemplateModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 hidden">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b pb-3">
            <h3 class="text-sm font-bold text-slate-800">Save Term as Template</h3>
            <button type="button" onclick="closeSaveTemplateModal()" class="text-slate-400 hover:text-slate-600 font-bold text-sm cursor-pointer">
                &times;
            </button>
        </div>

        <form id="saveAsTemplateForm" method="POST" class="space-y-4 text-xs">
            @csrf
            <input type="hidden" id="template_scope" name="scope">
            <input type="hidden" id="template_content" name="content">

            <div>
                <label class="block font-semibold text-slate-600 mb-1">Template Name *</label>
                <input type="text" id="template_name" name="name" required class="w-full border border-slate-300 rounded p-2 outline-none focus:border-blue-500">
            </div>

            <div class="p-3 bg-slate-50 border border-slate-200 rounded text-slate-600 space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">This term will be saved as a reusable template.</span>
                <div><strong>Term:</strong> <span id="template_term_title_preview"></span></div>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t">
                <button type="button" onclick="closeSaveTemplateModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded transition cursor-pointer">
                    Save as Template
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===================================================== -->
<!-- SCRIPTS -->
<!-- ===================================================== -->
<script>
// Close function para sa Save as Template modal
function closeSaveTemplateModal() {
    const modal = document.getElementById('saveAsTemplateModal');
    if (modal) modal.classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    // 1. Save as Template Button Listener
    document.querySelectorAll('.save-template-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const title = this.getAttribute('data-title') || '';
            const content = this.getAttribute('data-content') || '';
            const scope = this.getAttribute('data-scope') || 'global';
            const url = this.getAttribute('data-url') || '';

            const nameInput = document.getElementById('template_name');
            const titlePreview = document.getElementById('template_term_title_preview');
            const contentInput = document.getElementById('template_content');
            const scopeInput = document.getElementById('template_scope');
            const form = document.getElementById('saveAsTemplateForm');
            const modal = document.getElementById('saveAsTemplateModal');

            if (nameInput) nameInput.value = title;
            if (titlePreview) titlePreview.innerText = title;
            if (contentInput) contentInput.value = content;
            if (scopeInput) scopeInput.value = scope;
            if (form) form.action = url;
            if (modal) modal.classList.remove('hidden');
        });
    });
});

// 2. Para sa Disable Template Modal
function openDisableTemplateModal(templateId, templateName) {
    document.getElementById('disableTemplateName').innerText = templateName;
    document.getElementById('disableTemplateForm').action = `/terms-templates/${templateId}/disable`;
    document.getElementById('disableTemplateModal').classList.remove('hidden');
}

// 3. Para sa Edit Term Modal
function openEditTermModal(termId, title, scope, content, updateUrl) {
    document.getElementById('edit_title').value = title || '';
    document.getElementById('edit_scope').value = scope || 'global';
    document.getElementById('edit_content').value = content || '';
    
    const form = document.getElementById('editTermForm');
    if (form) form.action = updateUrl;
    
    const modal = document.getElementById('editTermModal');
    if (modal) modal.classList.remove('hidden');
}
</script>
</div>


{{-- =========================================================
    STEP 12 — VERSION & HISTORY TAB CONTENT
========================================================== --}}
<div
    id="workspace-panel-12"
    class="workspace-panel bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-6 hidden"
>
    <!-- HEADER -->
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                Versions &amp; Revision History
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
                Review the current version, submit it for approval, and review revision history.
            </p>
        </div>
        
        @if($isComplete ?? false)
            <form action="{{ route('products.submit-approval', $product->id ?? 1) }}" method="POST">
                @csrf
                <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg transition shadow-sm flex items-center space-x-1.5 cursor-pointer">
                    <span>Submit for Approval</span>
                </button>
            </form>
        @else
            <button type="button" disabled class="px-3 py-1.5 bg-slate-100 text-slate-400 font-bold text-xs rounded-lg border border-slate-200 cursor-not-allowed">
                Submit for Approval
            </button>
        @endif
    </div>

    <!-- CURRENT VERSION SECTION -->
    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 flex items-center justify-between">
        <div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Current Version</span>
            <div class="text-base font-extrabold text-slate-800 mt-0.5">
                {{ is_object($currentVersion) ? ($currentVersion->version_number ?? 'V1.0') : ($currentVersion ?? 'V1.0') }}
            </div>
            <span class="text-xs text-slate-500">
                Status: {{ is_object($currentVersion) ? ($currentVersion->status ?? 'Draft') : 'Draft' }}
            </span>
        </div>
    </div>

    <!-- VERSION HISTORY TABLE -->
    <div class="space-y-2">
        <h4 class="text-xs font-bold text-slate-700">Version History</h4>
        <div class="overflow-x-auto border border-slate-200 rounded-xl bg-white">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                        <th class="p-3 font-semibold">Version</th>
                        <th class="p-3 font-semibold">Status</th>
                        <th class="p-3 font-semibold">Updated By</th>
                        <th class="p-3 font-semibold">Date Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($versionHistory ?? [] as $ver)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="p-3 font-bold text-slate-800">{{ is_object($ver) ? ($ver->version_number ?? '-') : $ver }}</td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded-full font-semibold text-[10px] bg-amber-100 text-amber-700">
                                    {{ is_object($ver) ? ($ver->status ?? 'Draft') : 'Draft' }}
                                </span>
                            </td>
                            <td class="p-3 text-slate-600">{{ is_object($ver) ? ($ver->user_name ?? 'System User') : 'System User' }}</td>
                            <td class="p-3 text-slate-500">{{ now()->format('M d, Y h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-slate-400 italic">
                                No previous historical versions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- AUDIT HISTORY -->
<div class="mt-6 pt-6 border-t border-slate-200">

    <h3 class="text-sm font-bold text-slate-900 mb-1">
        Audit History
    </h3>

    <p class="text-xs text-slate-500 mb-3">
        Track user actions and system events.
    </p>

    @if(isset($auditLogs) && $auditLogs->count())

        <div class="border border-slate-200 rounded-lg overflow-hidden">

            <table class="w-full text-xs">

                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">
                            Action
                        </th>

                        <th class="text-left px-4 py-3 font-semibold text-slate-600">
                            Details
                        </th>

                        <th class="text-left px-4 py-3 font-semibold text-slate-600">
                            User
                        </th>

                        <th class="text-left px-4 py-3 font-semibold text-slate-600">
                            Date
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 bg-white">

                    @foreach($auditLogs as $log)

                        <tr>

                            <td class="px-4 py-3 font-semibold text-slate-900">
                                {{ $log->action ?? 'WORKSPACE_ACTIVITY' }}
                            </td>

                            <td class="px-4 py-3 text-slate-600">
                                {{ $log->details ?? 'Activity performed in workspace' }}
                            </td>

                            <td class="px-4 py-3 text-slate-500">
                                {{ $log->user_name ?? 'System User' }}
                            </td>

                            <td class="px-4 py-3 text-slate-500">
                                {{ optional($log->created_at)->format('M d, Y h:i A') }}
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

@if($auditLogs instanceof \Illuminate\Pagination\LengthAwarePaginator)

    <div class="px-4 py-3 border-t border-slate-200 bg-slate-50">
        {{ $auditLogs->links() }}
    </div>

@endif

        </div>

    @else

        <div class="py-6 text-center border border-dashed border-slate-300 rounded-lg">

            <p class="text-xs text-slate-400 italic">
                No audit logs found.
            </p>

        </div>

    @endif

</div>

</div>


{{-- =========================================================
    STEP 11 — USAGE & PERFORMANCE TAB CONTENT
========================================================== --}}
<div
    id="workspace-panel-11"
    class="workspace-panel bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-6"
>

    <!-- HEADER -->
    <div class="border-b pb-3 flex justify-between items-center">

        <div>
            <h2 class="text-base font-bold text-slate-900">
                Usage &amp; Performance Analytics
            </h2>

            <p class="text-xs text-slate-500 mt-0.5">
                System-generated product usage, commercial, and performance metrics.
            </p>
        </div>

        <span class="bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-semibold px-2.5 py-1 rounded-md inline-flex items-center space-x-1">
            <i class="fa-solid fa-chart-line"></i>
            <span>Live Analytics Active</span>
        </span>

    </div>


    <!-- USAGE & COMMERCIAL PERFORMANCE -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

        <!-- DEALS -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Deals
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                {{ $dealsCount ?? 0 }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Connected deal records
            </span>
        </div>


        <!-- PROPOSALS -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Proposals
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                {{ $proposalsCount ?? 0 }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Connected proposal records
            </span>
        </div>


        <!-- ACCEPTED -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Accepted
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                {{ $acceptedCount ?? 0 }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Accepted / contracted records
            </span>
        </div>


        <!-- UNIQUE CLIENTS -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Unique Clients
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                {{ $uniqueClientsCount ?? 0 }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Unique purchasing clients
            </span>
        </div>


        <!-- ENGAGEMENTS -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Engagements
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                {{ $engagementsCreatedCount ?? 0 }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Product engagements created
            </span>
        </div>


        <!-- PROPOSED VALUE -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Proposed Value
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                ₱{{ number_format($proposedValue ?? 0, 2) }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Total connected proposed value
            </span>
        </div>


        <!-- ACCEPTED / CONTRACTED VALUE -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Accepted / Contracted Value
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                ₱{{ number_format($acceptedContractedValue ?? 0, 2) }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Total accepted / contracted value
            </span>
        </div>


        <!-- DISCOUNT -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Discount
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                ₱{{ number_format($discountValue ?? 0, 2) }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Total connected discount value
            </span>
        </div>


        <!-- BILLED REVENUE -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Billed Revenue
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                ₱{{ number_format($billedRevenue ?? 0, 2) }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Connected billing records
            </span>
        </div>


        <!-- COLLECTED REVENUE -->
        <div class="p-4 border border-slate-200 rounded-lg bg-slate-50/70">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                Collected Revenue
            </span>

            <div class="text-2xl font-extrabold text-slate-900 mt-1">
                ₱{{ number_format($collectedRevenue ?? 0, 2) }}
            </div>

            <span class="text-[10px] text-slate-500 mt-1 block">
                Connected collection records
            </span>
        </div>

    </div>


    <!-- ANALYTICS STATUS -->
    <div class="border border-slate-200 rounded-lg overflow-hidden bg-white">

        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">

            <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider">
                Analytics Source Status
            </h3>

        </div>

        <div class="px-4 py-3">

            <div class="flex items-center space-x-2 text-xs text-slate-600">

                <i class="fa-solid fa-link text-slate-400"></i>

                <span>
                    Metrics are intended to be system-generated from connected Deals,
                    Proposals, Clients, Engagements, Discount, Billing, and Collection records.
                </span>

            </div>

        </div>

    </div>


    <!-- NAVIGATION -->
    <div class="flex justify-between items-center pt-4 border-t">

        <button
            type="button"
            onclick="switchWorkspaceTab(10)"
            class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer"
        >
            &larr; Back to Terms
        </button>

        <button
            type="button"
            onclick="switchWorkspaceTab(12)"
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded text-xs transition shadow-sm cursor-pointer inline-flex items-center space-x-1.5"
        >
            <span>Continue to Versions &amp; History</span>
            <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </button>

    </div>



{{-- =========================================================
    ADD PRODUCT ACTIVITY MODAL
========================================================== --}}

<div id="productActivityModal"
     class="hidden fixed inset-0 z-[9999] overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">

    <div class="bg-white rounded-xl max-w-lg w-full p-6 shadow-2xl relative">

        <div class="flex justify-between items-center border-b border-slate-100 pb-3">

            <div>

                <h3 class="font-bold text-slate-900 text-sm">
                    Add Product Activity
                </h3>

                <p class="text-xs text-slate-500 mt-0.5">
                    Define a main activity or sub-activity for this product.
                </p>

            </div>


            <button type="button"
                    onclick="closeProductActivityModal()"
                    class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">

                &times;

            </button>

        </div>


        <div class="space-y-4 mt-4 text-xs">

            <div>

                <label class="block font-semibold text-slate-700 mb-1">
                    Activity Level *
                </label>

                <select id="activityLevel"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500">

                    <option value="Main Activity">Main Activity</option>
                    <option value="Sub-Activity">Sub-Activity</option>

                </select>

            </div>


            <div>

                <label class="block font-semibold text-slate-700 mb-1">
                    Activity Name *
                </label>

                <input type="text"
                       id="activityName"
                       placeholder="e.g. Product Preparation / Quality Check"
                       class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500">

            </div>


            <div>

                <label class="block font-semibold text-slate-700 mb-1">
                    Description / Instructions
                </label>

                <textarea id="activityDescription"
                          rows="3"
                          placeholder="Brief execution guidance..."
                          class="w-full border border-slate-300 rounded-lg p-2.5 bg-white outline-none focus:border-blue-500"></textarea>

            </div>


            <div class="grid grid-cols-3 gap-3">

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">
                        Sequence #
                    </label>

                    <input type="number"
                           id="activitySequence"
                           value="1"
                           min="1"
                           class="w-full border border-slate-300 rounded-lg p-2.5">

                </div>


                <div>

                    <label class="block font-semibold text-slate-700 mb-1">
                        Expected Days
                    </label>

                    <input type="number"
                           id="activityDays"
                           value="1"
                           min="0"
                           step="0.5"
                           class="w-full border border-slate-300 rounded-lg p-2.5">

                </div>


                <div>

                    <label class="block font-semibold text-slate-700 mb-1">
                        Working Hours
                    </label>

                    <input type="number"
                           id="activityHours"
                           value="1"
                           min="0"
                           step="0.5"
                           class="w-full border border-slate-300 rounded-lg p-2.5">

                </div>

            </div>


            <div class="grid grid-cols-2 gap-3">

                <div>

                    <label class="block font-semibold text-slate-700 mb-1">
                        Billable Status
                    </label>

                    <select id="activityBillable"
                            class="w-full border border-slate-300 rounded-lg p-2.5">

                        <option>Billable</option>
                        <option>Non-Billable</option>

                    </select>

                </div>


                <div>

                    <label class="block font-semibold text-slate-700 mb-1">
                        Mandatory Level
                    </label>

                    <select id="activityMandatory"
                            class="w-full border border-slate-300 rounded-lg p-2.5">

                        <option>Mandatory Activity</option>
                        <option>Optional Activity</option>

                    </select>

                </div>

            </div>

        </div>


        <div class="flex justify-end space-x-2 pt-4 mt-4 border-t border-slate-100">

            <button type="button"
                    onclick="closeProductActivityModal()"
                    class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg font-semibold hover:bg-slate-50 text-xs cursor-pointer">

                Cancel

            </button>


            <button type="button"
                    onclick="saveProductActivity()"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold text-xs cursor-pointer">

                Save Activity

            </button>

        </div>

    </div>

</div>


{{-- =========================================================
    BULK IMPORT ACTIVITIES MODAL
========================================================== --}}

<div id="bulkImportModal"
     class="hidden fixed inset-0 z-[99999] overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">

    <div class="bg-white rounded-xl max-w-lg w-full p-6 shadow-2xl relative space-y-4">

        <div class="flex justify-between items-center border-b border-slate-100 pb-3">

            <div class="flex items-center space-x-2">

                <i class="fa-solid fa-file-arrow-up text-blue-600"></i>

                <h3 class="font-bold text-slate-900 text-sm">
                    Bulk Import Activities
                </h3>

            </div>

            <button type="button"
                    onclick="closeBulkImportModal()"
                    class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">

                &times;

            </button>

        </div>


        <form action="{{ route('products.activities.bulk-import', $product->id) }}"
              method="POST"
              enctype="multipart/form-data"
              class="space-y-4">

            @csrf


            {{-- =====================================================
                CSV / TXT FILE
            ====================================================== --}}

            <div>

                <label for="activity_file"
                       class="block font-semibold text-slate-700 text-xs mb-1">

                    Upload CSV / Text File

                </label>

                <input type="file"
                       id="activity_file"
                       name="activity_file"
                       accept=".csv,.txt"
                       required
                       class="w-full border border-slate-300 rounded-lg p-2 bg-white text-slate-600
                              file:mr-4 file:py-1 file:px-3 file:rounded-md
                              file:border-0 file:text-xs file:font-semibold
                              file:bg-blue-50 file:text-blue-700
                              hover:file:bg-blue-100">

                <p class="text-[10px] text-slate-400 mt-1">
                    CSV or TXT only. Maximum file size: 2 MB.
                </p>

            </div>


            {{-- =====================================================
                FORMAT INFORMATION
            ====================================================== --}}

            <div class="rounded-lg border border-blue-100 bg-blue-50 p-3">

                <div class="flex items-start space-x-2">

                    <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>

                    <div class="text-[10px] text-blue-800 w-full">

                        <div class="font-bold mb-1">
                            CSV format:
                        </div>

                        <div class="font-mono leading-relaxed break-all">
                            activity_level,parent_sequence,name,description,expected_days,working_hours,is_billable,is_mandatory
                        </div>

                        <div class="mt-2 font-semibold">
                            Example:
                        </div>

                        <div class="font-mono leading-relaxed mt-1 whitespace-pre-wrap">
main,,Main A,,1,2,1,1
sub,1,Sub A1,,1,1,1,1
sub,1,Sub A2,,1,1,1,1
main,,Main B,,2,3,1,1
sub,2,Sub B1,,1,1,1,1
main,,Main C,,1,2,1,1
sub,3,Sub C1,,1,1,1,1
                        </div>

                        <div class="mt-2 text-blue-700">
                            <strong>Note:</strong>
                            Sequence is automatic. Do not include a sequence column.
                        </div>

                    </div>

                </div>

            </div>


            {{-- =====================================================
                ACTIONS
            ====================================================== --}}

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">

                <button type="button"
                        onclick="closeBulkImportModal()"
                        class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg font-semibold hover:bg-slate-50 text-xs cursor-pointer">

                    Cancel

                </button>


                <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold text-xs cursor-pointer">

                    <i class="fa-solid fa-upload mr-1"></i>

                    Start Bulk Import

                </button>

            </div>

        </form>

    </div>

</div>

{{-- =========================================================
    JAVASCRIPT
========================================================== --}}

<script>

    /*
    |--------------------------------------------------------------------------
    | Workspace Tab Switching
    |--------------------------------------------------------------------------
    */

    function switchWorkspaceTab(stepId) {

    // Ilagay mo dito sa pinakataas:
        const url = new URL(window.location);
        url.searchParams.set('tab', stepId);
        window.history.pushState({}, '', url);

        document.querySelectorAll('.workspace-panel').forEach(panel => {

            panel.classList.add('hidden');

        });


        const activePanel =
            document.getElementById(`workspace-panel-${stepId}`);


        if (activePanel) {

            activePanel.classList.remove('hidden');

        }


        /*
        |--------------------------------------------------------------------------
        | Pipeline
        |--------------------------------------------------------------------------
        */

        for (let i = 1; i <= 12; i++) {

            const circle =
                document.getElementById(`pipe-step-${i}`);


            if (!circle) {
                continue;
            }


            if (i === stepId) {

                circle.className =
                    "w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition shadow-sm bg-blue-600 text-white ring-4 ring-blue-100";

            }

            else if (i < stepId) {

                circle.className =
                    "w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition shadow-sm bg-blue-50 border-2 border-blue-300 text-blue-600";

            }

            else {

                circle.className =
                    "w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition shadow-sm bg-white border-2 border-slate-300 text-slate-600 hover:border-blue-500";

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Navigation Tabs
        |--------------------------------------------------------------------------
        */

        document.querySelectorAll('.workspace-tab-btn').forEach((btn, index) => {

            const currentStep = index + 1;


            if (currentStep === stepId) {

                btn.className =
                    "workspace-tab-btn px-4 py-2 bg-blue-600 text-white rounded-lg shadow-sm flex items-center space-x-1.5 cursor-pointer transition";

            }

            else {

                btn.className =
                    "workspace-tab-btn px-4 py-2 hover:bg-slate-100 text-slate-600 rounded-lg transition flex items-center space-x-1.5 cursor-pointer";

            }

        });


        /*
        |--------------------------------------------------------------------------
        | Scroll to Workspace Content
        |--------------------------------------------------------------------------
        */

        const workspacePanel =
            document.getElementById(`workspace-panel-${stepId}`);


        if (workspacePanel) {

            workspacePanel.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Requirement Edit Modal
    |--------------------------------------------------------------------------
    */

    function openRequirementEditModal(
        id,
        type,
        name,
        description,
        isMandatory,
        sequence,
        status
    ) {

        const modal =
            document.getElementById('requirementEditModal');

        const form =
            document.getElementById('requirementEditForm');


        if (!modal || !form) {
            return;
        }


        form.action =
            "{{ url('/products/' . $product->id . '/requirements') }}/" + id;


        document.getElementById('editRequirementType').value =
            type ?? '';


        document.getElementById('editRequirementName').value =
            name ?? '';


        document.getElementById('editRequirementDescription').value =
            description ?? '';


        document.getElementById('editRequirementMandatory').value =
            isMandatory ? '1' : '0';


        document.getElementById('editRequirementSequence').value =
            sequence ?? 1;


        document.getElementById('editRequirementStatus').value =
            status ?? 'Active';


        modal.classList.remove('hidden');

    }


    function closeRequirementEditModal() {

        const modal =
            document.getElementById('requirementEditModal');


        if (modal) {

            modal.classList.add('hidden');

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Activity Modal
    |--------------------------------------------------------------------------
    */

    function openProductActivityModal() {

        const modal =
            document.getElementById('productActivityModal');


        if (modal) {

            modal.classList.remove('hidden');

        }

    }


    function closeProductActivityModal() {

        const modal =
            document.getElementById('productActivityModal');


        if (modal) {

            modal.classList.add('hidden');

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Bulk Import Modal
    |--------------------------------------------------------------------------
    */

    function openBulkImportModal() {

        const modal =
            document.getElementById('bulkImportModal');


        if (modal) {

            modal.classList.remove('hidden');

        }

    }


    function closeBulkImportModal() {

        const modal =
            document.getElementById('bulkImportModal');


        if (modal) {

            modal.classList.add('hidden');

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Save Product Activity
    |--------------------------------------------------------------------------
    */

    /*
|--------------------------------------------------------------------------
| Save Product Activity
|--------------------------------------------------------------------------
*/

function saveProductActivity() {

    const name =
        document.getElementById('activityName').value.trim();


    const description =
        document.getElementById('activityDescription').value.trim();


    const level =
        document.getElementById('activityLevel').value;


    const days =
        document.getElementById('activityDays').value;


    const hours =
        document.getElementById('activityHours').value;


    const billable =
        document.getElementById('activityBillable').value;


    if (!name) {

        alert('Please enter an Activity Name.');

        return;

    }


    /*
    |--------------------------------------------------------------------------
    | REMOVE EMPTY WORKFLOW ROW
    |--------------------------------------------------------------------------
    */

    const emptyRow =
        document.getElementById('emptyWorkflowRow');


    if (emptyRow) {

        emptyRow.remove();

    }


    /*
    |--------------------------------------------------------------------------
    | WORKFLOW TABLE
    |--------------------------------------------------------------------------
    */

    const table =
        document.getElementById('productWorkflowTable');


    if (!table) {

        closeProductActivityModal();

        return;

    }


    /*
    |--------------------------------------------------------------------------
    | AUTOMATIC DISPLAY SEQUENCE
    |--------------------------------------------------------------------------
    |
    | No manual Sequence # input.
    | The next row gets the next number automatically.
    |
    */

    const existingRows =
        table.querySelectorAll(
            'tr:not(#emptyWorkflowRow)'
        );


    const sequence =
        existingRows.length + 1;


    /*
    |--------------------------------------------------------------------------
    | CREATE WORKFLOW ROW
    |--------------------------------------------------------------------------
    */

    const row =
        document.createElement('tr');


    row.className =
        'border-t border-slate-200 hover:bg-slate-50 transition';


    row.innerHTML = `

        <td class="py-3 px-3 text-center">

            <input type="checkbox"
                   class="rounded border-slate-300">

        </td>


        <td class="py-3 px-3 font-mono font-semibold">

            ${sequence}

        </td>


        <td class="py-3 px-3">

            <div class="font-semibold text-slate-800">

                ${escapeHtml(name)}

            </div>


            <div class="text-[10px] text-slate-400 mt-0.5">

                ${escapeHtml(level)}

            </div>


            ${
                description
                    ? `
                        <div class="text-[10px] text-slate-400 mt-1">
                            ${escapeHtml(description)}
                        </div>
                    `
                    : ''
            }

        </td>


        <td class="py-3 px-3">

            ${escapeHtml(days)}

        </td>


        <td class="py-3 px-3">

            ${escapeHtml(hours)}

        </td>


        <td class="py-3 px-3">

            <span
                class="px-2 py-1 rounded-full text-[10px] font-bold
                ${
                    billable === 'Billable'
                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                        : 'bg-slate-100 text-slate-600 border border-slate-200'
                }">

                ${escapeHtml(billable)}

            </span>

        </td>


        <td class="py-3 px-3 text-right">

            <button
                type="button"
                onclick="removeWorkflowRow(this)"
                class="px-2.5 py-1 border border-slate-300 rounded text-[10px] text-slate-600 hover:bg-slate-100 cursor-pointer">

                Remove

            </button>

        </td>

    `;


    table.appendChild(row);


    /*
    |--------------------------------------------------------------------------
    | RESET ACTIVITY FORM
    |--------------------------------------------------------------------------
    */

    document.getElementById('activityName').value = '';

    document.getElementById('activityDescription').value = '';

    document.getElementById('activityDays').value = '1';

    document.getElementById('activityHours').value = '1';


    /*
    |--------------------------------------------------------------------------
    | CLOSE MODAL
    |--------------------------------------------------------------------------
    */

    closeProductActivityModal();

}


/*
|--------------------------------------------------------------------------
| Remove Workflow Row
|--------------------------------------------------------------------------
*/

function removeWorkflowRow(button) {

    const row =
        button.closest('tr');


    if (row) {

        row.remove();

    }


    const table =
        document.getElementById('productWorkflowTable');


    if (
        table &&
        table.querySelectorAll(
            'tr:not(#emptyWorkflowRow)'
        ).length === 0
    ) {

        table.innerHTML = `

            <tr id="emptyWorkflowRow">

                <td
                    colspan="7"
                    class="py-10 text-center text-slate-400">

                    <i
                        class="fa-solid fa-list-check text-slate-300 text-xl mb-2">
                    </i>

                    <div>

                        No activities configured
                        for this product yet.

                    </div>

                </td>

            </tr>

        `;

    }

}


/*
|--------------------------------------------------------------------------
| Escape HTML
|--------------------------------------------------------------------------
*/

function escapeHtml(value) {

    const div =
        document.createElement('div');


    div.textContent =
        value ?? '';


    return div.innerHTML;

}


/*
|--------------------------------------------------------------------------
| Payment Structure - Custom Field
|--------------------------------------------------------------------------
*/

function initializePaymentStructure() {

    const paymentSelect =
        document.getElementById(
            'standardPaymentSelect'
        );


    const customWrapper =
        document.getElementById(
            'customPaymentWrapper'
        );


    if (
        !paymentSelect ||
        !customWrapper
    ) {

        return;

    }


    function toggleCustomPayment() {

        if (
            paymentSelect.value === 'Custom'
        ) {

            customWrapper.classList.remove(
                'hidden'
            );

        }

        else {

            customWrapper.classList.add(
                'hidden'
            );

        }

    }


    paymentSelect.addEventListener(
        'change',
        toggleCustomPayment
    );


    toggleCustomPayment();

}


/*
|--------------------------------------------------------------------------
| Close Modals When Clicking Outside
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'click',
    function(event) {

        const requirementModal =
            document.getElementById(
                'requirementEditModal'
            );


        const productModal =
            document.getElementById(
                'productActivityModal'
            );


        const bulkModal =
            document.getElementById(
                'bulkImportModal'
            );


        /*
        |--------------------------------------------------------------------------
        | Requirement Edit Modal
        |--------------------------------------------------------------------------
        */

        if (
            requirementModal &&
            event.target === requirementModal
        ) {

            closeRequirementEditModal();

        }


        /*
        |--------------------------------------------------------------------------
        | Product Activity Modal
        |--------------------------------------------------------------------------
        */

        if (
            productModal &&
            event.target === productModal
        ) {

            closeProductActivityModal();

        }


        /*
        |--------------------------------------------------------------------------
        | Bulk Import Modal
        |--------------------------------------------------------------------------
        */

        if (
            bulkModal &&
            event.target === bulkModal
        ) {

            closeBulkImportModal();

        }

    }
);
    /*
    |--------------------------------------------------------------------------
    | Default Workspace
    |--------------------------------------------------------------------------
    */
    document.addEventListener('DOMContentLoaded', function() {
    const params =
        new URLSearchParams(window.location.search);
    const requestedTab =
        parseInt(
            params.get('tab') || '1',
            10
        );
    switchWorkspaceTab(requestedTab);
    initializePaymentStructure();
});

   /* =========================================================
    UPDATED CHECKMARK & APPROVAL GATE (Steps 1 to 10 Only)
========================================================== */
document.addEventListener('DOMContentLoaded', function() {
    function evaluateWorkspaceSteps() {
        // STEP 1: Overview
        const step1Field = document.querySelector('textarea[name="internal_description"]');
        if (step1Field && step1Field.value.trim() !== '') {
            markStepAsCompleted(1);
        }

        // STEP 2: Product Catalog
        const step2Field = document.querySelector('textarea[name="scope_of_work"]');
        if (step2Field && step2Field.value.trim() !== '') {
            markStepAsCompleted(2);
        }

        // STEP 3: Inventory Specs (Hindi magse-select kung blangko o may 'Select')
        const step3Field = document.querySelector('select[name="unit_measure"]');
        if (step3Field && step3Field.value.trim() !== '' && !step3Field.value.toLowerCase().includes('select')) {
            markStepAsCompleted(3);
        }

        // STEP 4: Requirements
        const reqRows = document.querySelectorAll('#requirementTableBody tr:not(#emptyRequirementRow):not(:has(td[colspan]))');
        if (reqRows.length > 0) {
            markStepAsCompleted(4);
        }

        // STEP 5: Workflow
        const actRows = document.querySelectorAll('#productActivityTableBody tr:not(:has(td[colspan]))');
        if (actRows.length > 0) {
            markStepAsCompleted(5);
        }

        // STEP 6: Commercials
        const step6Field = document.querySelector('input[name="price"]');
        if (step6Field && parseFloat(step6Field.value) > 0) {
            markStepAsCompleted(6);
        }

        // STEP 7: Engagement
        const step7Field = document.getElementById('payment_structure_select');
        if (step7Field && step7Field.value.trim() !== '' && !step7Field.value.toLowerCase().includes('select')) {
            markStepAsCompleted(7);
        }

        // STEP 8: Reporting
        const step8Field = document.querySelector('select[name="reporting_frequency"]');
        if (step8Field && step8Field.value.trim() !== '' && !step8Field.value.toLowerCase().includes('select')) {
            markStepAsCompleted(8);
        }

        // STEP 9: Automation
        const step9Field = document.querySelector('select[name="base_reference_date"]');
        if (step9Field && step9Field.value.trim() !== '' && !step9Field.value.toLowerCase().includes('select')) {
            markStepAsCompleted(9);
        }

        // STEP 10: Terms
        const termsCards = document.querySelectorAll('#workspace-panel-10 .space-y-3 > div:not(.text-center)');
        const emptyMsg = document.querySelector('#workspace-panel-10 .space-y-3 .text-center');
        if (termsCards.length > 0 && !emptyMsg) {
            markStepAsCompleted(10);
        }

        // Susuriin ngayon kung tapos na ang lahat mula 1 hanggang 10 para sa Submit for Approval
        checkAllStepsFinished();
    }

    function markStepAsCompleted(stepNum) {
        const pipeCircle = document.getElementById(`pipe-step-${stepNum}`);
        if (pipeCircle && !pipeCircle.classList.contains('bg-blue-600')) {
            pipeCircle.className = "w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition shadow-sm bg-blue-600 text-white";
            pipeCircle.innerHTML = '<i class="fa-solid fa-check text-xs"></i>';
        }

        const tabBtn = document.getElementById(`tab-btn-${stepNum}`);
        if (tabBtn && !tabBtn.querySelector('.fa-circle-check')) {
            const checkIcon = document.createElement('i');
            checkIcon.className = "fa-solid fa-circle-check text-blue-500 ml-1.5 text-[10px]";
            tabBtn.appendChild(checkIcon);
        }
    }

    function checkAllStepsFinished() {
        let allDone = true;
        for (let i = 1; i <= 10; i++) {
            const circle = document.getElementById(`pipe-step-${i}`);
            if (!circle || !circle.classList.contains('bg-blue-600')) {
                allDone = false;
                break;
            }
        }

        // Mga elemento mula sa iyong bagong banner HTML
        const icon = document.getElementById('completeness-icon');
        const text = document.getElementById('completeness-text');
        const incompleteBadge = document.getElementById('incomplete-badge');
        const approvalForm = document.getElementById('submit-approval-form');

        if (allDone) {
            if (icon) icon.className = "fa-solid fa-circle-check text-slate-900 text-lg";
            if (text) text.textContent = "All required sections have been completed! You can now submit this for approval.";
            if (incompleteBadge) incompleteBadge.style.display = 'none';
            if (approvalForm) approvalForm.style.display = 'block';
        } else {
            if (icon) icon.className = "fa-solid fa-triangle-exclamation text-slate-900 text-lg";
            if (text) text.textContent = "Completeness Gate Pending: Please complete all required sections to enable approval submission.";
            if (incompleteBadge) incompleteBadge.style.display = 'inline-block';
            if (approvalForm) approvalForm.style.display = 'none';
        }
    }

    evaluateWorkspaceSteps();
});


    /* =========================================================
    VIEW ONLY VS EDITING MODE - AUTOMATIC FORM LOCKER
========================================================== */
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const mode = urlParams.get('mode');

    if (mode === 'view') {
        // 1. Kunin ang lahat ng inputs, textareas, at selects sa workspace at i-disable
        const fields = document.querySelectorAll('input, textarea, select');
        fields.forEach(field => {
            field.disabled = true;
            field.classList.add('bg-slate-50', 'text-slate-500', 'cursor-not-allowed');
        });

        // 2. Itago ang mga action buttons, save, update, submit, at mga add/edit buttons sa view mode
        const actionButtons = document.querySelectorAll(
            'button[type="submit"], .save-btn, .btn-primary, ' +
            'button:has(.fa-plus), button:has(.fa-file-import), ' +
            'button[onclick*="Modal"], a[onclick*="Modal"], ' +
            'tr td button, tr td a, .workspace-panel button, ' +
            'a.text-blue-600, button.text-blue-600'
        );
        
        actionButtons.forEach(btn => {
            const text = btn.textContent || '';
            if (
                !btn.id.startsWith('tab-btn-') && 
                !text.includes('Back') && 
                !text.includes('Close') &&
                !btn.classList.contains('workspace-tab')
            ) {
                btn.style.display = 'none';
            }
        });

        // 3. Itago ang mga table action links sa Requirements, Workflow, at Terms
        const tableActionLinks = document.querySelectorAll('td.action-column, td:last-child a, td:last-child button');
        tableActionLinks.forEach(el => {
            const content = el.textContent.toLowerCase();
            if (content.includes('edit') || content.includes('delete') || content.includes('disable') || content.includes('save as template') || content.includes('duplicate')) {
                el.style.display = 'none';
            }
        });
    }
});

</script>
@endsection

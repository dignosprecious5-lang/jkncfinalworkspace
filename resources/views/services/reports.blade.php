@extends('layouts.app')

@section('content')

<div class="min-h-screen bg-slate-50">

    <div class="max-w-7xl mx-auto px-5 py-6">

        {{-- ============================================================
            HEADER
        ============================================================= --}}
        <div class="mb-5 bg-white border border-slate-200 rounded-xl px-5 py-4 shadow-sm flex items-center justify-between">

            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-blue-600">
                        Services
                    </span>
                    <span class="text-[10px] text-slate-400">
                        / Reports
                    </span>
                </div>

                <h1 class="text-xl font-semibold text-slate-900">
                    Service Performance Report
                </h1>

                <p class="mt-1 text-xs text-slate-500">
                    System-generated service performance based on connected service and engagement records.
                </p>
            </div>

            <div>
                <a href="{{ route('services.index') }}"
                   style="color:#2563eb; text-decoration:none; font-size:13px; font-weight:600;"
                   class="hover:underline">
                    ← Back to Dashboard
                </a>
            </div>

        </div>


        {{-- ============================================================
            FILTERS
        ============================================================= --}}
        <form
            method="GET"
            action="{{ route('services.reports') }}"
            class="bg-white border border-slate-200 rounded-xl shadow-sm mb-5"
        >

            <div class="px-4 py-3 border-b border-slate-200">

                <h2 class="text-xs font-semibold text-slate-800">
                    Report Filters
                </h2>

            </div>

            <div class="p-4">

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-7 gap-3">

                    {{-- From Date --}}
                    <div>

                        <label class="block text-[11px] font-medium text-slate-600 mb-1">
                            From Date
                        </label>

                        <input
                            type="date"
                            name="from_date"
                            value="{{ request('from_date') }}"
                            class="w-full h-9 rounded-lg border border-slate-300 px-3 text-xs text-slate-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-100"
                        >

                    </div>


                    {{-- To Date --}}
                    <div>

                        <label class="block text-[11px] font-medium text-slate-600 mb-1">
                            To Date
                        </label>

                        <input
                            type="date"
                            name="to_date"
                            value="{{ request('to_date') }}"
                            class="w-full h-9 rounded-lg border border-slate-300 px-3 text-xs text-slate-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-100"
                        >

                    </div>


                    {{-- Service Area --}}
                    <div>

                        <label class="block text-[11px] font-medium text-slate-600 mb-1">
                            Service Area
                        </label>

                        <select
                            name="service_area"
                            class="w-full h-9 rounded-lg border border-slate-300 px-3 text-xs text-slate-700 bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-100"
                        >

                            <option value="">
                                All Service Areas
                            </option>

                            @foreach(($serviceAreas ?? []) as $area)

                                <option
                                    value="{{ $area }}"
                                    {{ request('service_area') === $area ? 'selected' : '' }}
                                >
                                    {{ $area }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- Category --}}
                    <div>

                        <label class="block text-[11px] font-medium text-slate-600 mb-1">
                            Category
                        </label>

                        <select
                            name="category"
                            class="w-full h-9 rounded-lg border border-slate-300 px-3 text-xs text-slate-700 bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-100"
                        >

                            <option value="">
                                All Categories
                            </option>

                            @foreach(($categories ?? []) as $category)

                                <option
                                    value="{{ $category }}"
                                    {{ request('category') === $category ? 'selected' : '' }}
                                >
                                    {{ $category }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- Engagement Type --}}
                    <div>

                        <label class="block text-[11px] font-medium text-slate-600 mb-1">
                            Engagement Type
                        </label>

                        <select
                            name="engagement"
                            class="w-full h-9 rounded-lg border border-slate-300 px-3 text-xs text-slate-700 bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-100"
                        >

                            <option value="">
                                All Engagement Types
                            </option>

                            @foreach(($engagementTypes ?? []) as $engagementType)

                                @php
                                    $engagementLabel = match (strtolower(trim($engagementType))) {
                                        'project' => 'Project',
                                        'regular' => 'Regular',
                                        'hybrid' => 'Hybrid',
                                        'both' => 'Hybrid',
                                        default => ucfirst($engagementType),
                                    };
                                @endphp

                                <option
                                    value="{{ $engagementType }}"
                                    {{ request('engagement') === $engagementType ? 'selected' : '' }}
                                >
                                    {{ $engagementLabel }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- Status --}}
                    <div>

                        <label class="block text-[11px] font-medium text-slate-600 mb-1">
                            Service Status
                        </label>

                        <select
                            name="status"
                            class="w-full h-9 rounded-lg border border-slate-300 px-3 text-xs text-slate-700 bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-100"
                        >

                            <option value="">
                                All Statuses
                            </option>

                            @foreach(($statuses ?? []) as $status)

                                <option
                                    value="{{ $status }}"
                                    {{ request('status') === $status ? 'selected' : '' }}
                                >
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- Actions --}}
                    <div class="flex items-end gap-2">

                        <a
                            href="{{ route('services.reports') }}"
                            class="h-9 px-3 inline-flex items-center rounded-lg border border-slate-300 text-[11px] font-medium text-slate-600 hover:bg-slate-50"
                        >
                            Reset
                        </a>

                        <button
                            type="submit"
                            class="h-9 px-3 inline-flex items-center rounded-lg bg-slate-900 text-[11px] font-semibold text-white hover:bg-slate-800"
                        >
                            Apply
                        </button>

                    </div>

                </div>

            </div>

        </form>


        {{-- ============================================================
            SUMMARY CARDS
        ============================================================= --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">


            {{-- Total Services --}}
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm px-4 py-3">

                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                    Total Services
                </p>

                <div class="mt-2 flex items-baseline gap-2">

                    <span class="text-2xl font-semibold text-slate-900">
                        {{ number_format($totalServices) }}
                    </span>

                    <span class="text-[10px] text-slate-400">
                        services
                    </span>

                </div>

            </div>


            {{-- Active Services --}}
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm px-4 py-3">

                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                    Active Services
                </p>

                <div class="mt-2 flex items-baseline gap-2">

                    <span class="text-2xl font-semibold text-slate-900">
                        {{ number_format($activeServices) }}
                    </span>

                    <span class="text-[10px] text-slate-400">
                        active
                    </span>

                </div>

            </div>


            {{-- Engagements --}}
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm px-4 py-3">

                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                    Engagements
                </p>

                <div class="mt-2 flex items-baseline gap-2">

                    <span class="text-2xl font-semibold text-slate-900">
                        {{ number_format($totalEngagements) }}
                    </span>

                    <span class="text-[10px] text-slate-400">
                        connected
                    </span>

                </div>

            </div>

        </div>


        {{-- ============================================================
            SERVICE PERFORMANCE TABLE
        ============================================================= --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">

            <div class="px-4 py-3 border-b border-slate-200">

                <div class="flex items-center justify-between">

                    <div>

                        <h2 class="text-sm font-semibold text-slate-900">
                            Service Performance
                        </h2>

                        <p class="mt-0.5 text-[11px] text-slate-400">
                            System-generated summary of service configuration and connected engagement usage.
                        </p>

                    </div>

                    <span class="text-[10px] text-slate-400">
                        {{ $services->total() }} total records
                    </span>

                </div>

            </div>


            {{-- TABLE --}}
            <div class="overflow-x-auto">

                <table class="min-w-full text-xs">

                    <thead class="bg-slate-50 border-b border-slate-200">

                        <tr class="text-[10px] uppercase tracking-wide text-slate-500">

                            <th class="px-4 py-2.5 text-left font-semibold">
                                Service
                            </th>

                            <th class="px-3 py-2.5 text-left font-semibold">
                                Category
                            </th>

                            <th class="px-3 py-2.5 text-left font-semibold">
                                Engagement
                            </th>

                            <th class="px-3 py-2.5 text-left font-semibold">
                                Frequency
                            </th>

                            <th class="px-3 py-2.5 text-right font-semibold">
                                Standard Price
                            </th>

                            {{-- Dito natin idinagdag ang Unit / Rate Kolum --}}
                            <th class="px-3 py-2.5 text-right font-semibold">
                                Unit / Rate
                            </th>

                            <th class="px-3 py-2.5 text-right font-semibold">
                                Expected Hours
                            </th>

                            <th class="px-3 py-2.5 text-center font-semibold">
                                Version
                            </th>

                            <th class="px-3 py-2.5 text-center font-semibold">
                                Engagements
                            </th>

                            <th class="px-4 py-2.5 text-left font-semibold">
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-200">

                        @forelse($services as $service)

                            @php
                                $engagementType = strtolower(
                                    trim(
                                        $service->engagement_behavior ?? ''
                                    )
                                );

                                $engagementLabel = match($engagementType) {
                                    'project' => 'Project',
                                    'regular' => 'Regular',
                                    'hybrid' => 'Hybrid',
                                    'both' => 'Hybrid',
                                    default => '—',
                                };

                                $serviceStatus = strtolower(
                                    trim(
                                        $service->status ?? ''
                                    )
                                );

                                $standardPrice = $service->report_standard_price;
                                
                                // Kinukuha ang unit rate mula sa active version o catalog equivalent nito
                                $unitRate = $service->report_unit_rate ?? $service->activeVersion->unit_rate ?? null;

                                $expectedHours = $service->report_expected_hours;
                            @endphp


                            <tr class="hover:bg-slate-50 transition-colors">


                                {{-- Service --}}
                                <td class="px-4 py-3">

                                    <a
                                        href="{{ route('services.workspace', $service->id) }}"
                                        class="font-semibold text-slate-900 hover:text-blue-600"
                                    >
                                        {{ $service->name }}
                                    </a>

                                    <div class="mt-0.5 text-[10px] text-slate-400">
                                        {{ $service->service_code ?? 'SVC-' . sprintf('%04d', $service->id) }}
                                    </div>

                                </td>


                                {{-- Category --}}
                                <td class="px-3 py-3 text-slate-600">
                                    {{ $service->category ?: '—' }}
                                </td>


                                {{-- Engagement --}}
                                <td class="px-3 py-3">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-[10px] font-medium text-slate-600">
                                        {{ $engagementLabel }}
                                    </span>
                                </td>


                                {{-- Frequency --}}
                                <td class="px-3 py-3 text-slate-600">
                                    {{ $service->report_reporting_frequency ?: '—' }}
                                </td>


                                {{-- Standard Price --}}
                                <td class="px-3 py-3 text-right whitespace-nowrap">
                                    @if($standardPrice !== null)
                                        <span class="font-medium text-slate-800">
                                            ₱{{ number_format((float) $standardPrice, 2) }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">
                                            —
                                        </span>
                                    @endif
                                </td>


                                {{-- Unit / Rate (Idinagdag na Data) --}}
                                <td class="px-3 py-3 text-right whitespace-nowrap">
                                    @if($unitRate !== null && $unitRate > 0)
                                        <span class="font-medium text-slate-800">
                                            ₱{{ number_format((float) $unitRate, 2) }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">
                                            —
                                        </span>
                                    @endif
                                </td>


                                {{-- Expected Hours --}}
                                <td class="px-3 py-3 text-right whitespace-nowrap">
                                    @if($expectedHours !== null)
                                        <span class="font-medium text-slate-700">
                                            {{ number_format((float) $expectedHours, 1) }}
                                        </span>
                                        <span class="text-[10px] text-slate-400">
                                            hrs
                                        </span>
                                    @else
                                        <span class="text-slate-400">
                                            —
                                        </span>
                                    @endif
                                </td>


                                {{-- Version --}}
                                <td class="px-3 py-3 text-center text-slate-600">
                                    {{ $service->report_version }}
                                </td>


                                {{-- Engagements --}}
                                <td class="px-3 py-3 text-center">
                                    @if($service->report_engagements > 0)
                                        <span class="font-semibold text-slate-800">
                                            {{ number_format($service->report_engagements) }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">
                                            0
                                        </span>
                                    @endif
                                </td>


                                {{-- Status --}}
                                <td class="px-4 py-3">
                                    @if($serviceStatus === 'active')
                                        <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Active
                                        </span>
                                    @elseif($serviceStatus)
                                        <span class="text-[10px] font-medium text-slate-500">
                                            {{ ucfirst(str_replace('_', ' ', $serviceStatus)) }}
                                        </span>
                                    @else
                                        <span class="text-[10px] text-slate-400">
                                            —
                                        </span>
                                    @endif
                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="10"
                                    class="px-4 py-12 text-center"
                                >

                                    <div class="text-xs font-medium text-slate-700">
                                        No service records found
                                    </div>

                                    <div class="mt-1 text-[10px] text-slate-400">
                                        Try changing the selected report filters.
                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- Pagination --}}
            @if($services->hasPages())

                <div class="px-4 py-3 border-t border-slate-200 bg-slate-50/50">

                    {{ $services->links() }}

                </div>

            @endif

        </div>


        {{-- ============================================================
            REPORT INFORMATION
        ============================================================= --}}
        <div class="mt-4 bg-white border border-slate-200 rounded-xl px-4 py-3">

            <h3 class="text-[11px] font-semibold text-slate-800">
                Report Information
            </h3>

            <p class="mt-1 text-[10px] leading-5 text-slate-500">
                Service configuration values are read from the Service and Service Version records.
                Engagement totals are calculated from connected engagement records.
                Operational metrics such as actual hours, cost, and deliverables should only be shown
                when their corresponding source records are available.
            </p>

        </div>

    </div>

</div>

@endsection
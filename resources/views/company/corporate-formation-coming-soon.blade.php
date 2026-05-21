@extends('layouts.app')
@section('title', $title)

@section('content')
@php
    $tabConfig = [
        ['key' => 'sec-coi', 'label' => 'SEC-COI', 'href' => route('company.corporate-formation.sec-coi', $company->id)],
        ['key' => 'sec-aoi', 'label' => 'SEC-AOI', 'href' => route('company.corporate-formation.sec-aoi', $company->id)],
        ['key' => 'bylaws', 'label' => 'Bylaws', 'href' => route('company.corporate-formation.bylaws', $company->id)],
        ['key' => 'gis', 'label' => 'GIS', 'href' => route('company.corporate-formation.gis', $company->id)],
        ['key' => 'notices', 'label' => 'Notices of Meeting...', 'href' => route('company.corporate-formation.notices', $company->id)],
        ['key' => 'minutes', 'label' => 'Minutes of Meeting...', 'href' => route('company.corporate-formation.minutes', $company->id)],
        ['key' => 'resolution', 'label' => 'Resolution', 'href' => route('company.corporate-formation.resolutions', $company->id)],
        ['key' => 'secretary', 'label' => 'Secretary...', 'href' => route('company.corporate-formation.secretary-certificates', $company->id)],
    ];
    $activeRibbonIndex = collect($tabConfig)->search(fn ($item) => $item['key'] === $activeTab);
    $initialRibbonScroll = $activeRibbonIndex === false ? 0 : max(0, ($activeRibbonIndex - 1) * 180);
@endphp

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4 pb-8">
    <div class="bg-white border border-gray-100 rounded-md overflow-hidden">
        @include('company.partials.company-header', ['company' => $company])

        <section class="bg-gray-50 p-4 min-h-[760px]">
            <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
                <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 bg-white">
                    <div x-data="{
                            scrollStep() {
                                const ribbon = this.$refs.ribbon;
                                const card = ribbon?.querySelector('[data-ribbon-card]');
                                return card ? card.getBoundingClientRect().width * 3 : 540;
                            },
                            prev() {
                                this.$refs.ribbon?.scrollBy({ left: -this.scrollStep(), behavior: 'smooth' });
                            },
                            next() {
                                this.$refs.ribbon?.scrollBy({ left: this.scrollStep(), behavior: 'smooth' });
                            }
                        }"
                        class="flex items-center justify-between gap-3 w-full min-w-0">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <button type="button" class="h-9 w-9 shrink-0 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition flex items-center justify-center" @click="prev()">
                                <i class="fas fa-chevron-left text-xs"></i>
                            </button>
                            <div x-ref="ribbon" x-init="$nextTick(() => { $el.scrollLeft = {{ $initialRibbonScroll }}; })" class="min-w-0 flex-1 overflow-x-auto whitespace-nowrap scroll-smooth no-scrollbar">
                                <div class="flex items-stretch min-w-max">
                                    @foreach ($tabConfig as $tabItem)
                                        <a href="{{ $tabItem['href'] }}" data-ribbon-card class="shrink-0 w-[180px] px-4 py-3 text-sm font-medium text-center border-t border-b border-r border-gray-200 first:border-l {{ $activeTab === $tabItem['key'] ? 'bg-blue-50 text-blue-700 border-blue-500' : 'bg-white text-gray-800 hover:bg-gray-50' }}">
                                            <span class="block truncate">{{ $tabItem['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                            <button type="button" class="h-9 w-9 shrink-0 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition flex items-center justify-center" @click="next()">
                                <i class="fas fa-chevron-right text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
                        <div class="text-base font-semibold">{{ $title }} for Company Module</div>
                        <p class="mt-2">
                            This section now stays inside the Company module URL. The full company-scoped record flow for {{ strtolower($title) }}
                            still needs dedicated company-specific data handling, because the current Corporate-module implementation is global and shares linked records across notices, minutes, resolutions, and secretary certificates.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

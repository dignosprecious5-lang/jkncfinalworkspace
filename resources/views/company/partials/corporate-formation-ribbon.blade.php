@php
    $activeTab = $activeTab ?? 'gis';
    $topButtonLabel = $topButtonLabel ?? 'SEC-GIS';
    $companyEntity = $company ?? ($companyRecord ?? null);
    $companyId = $companyEntity->id ?? null;

    $items = [
        ['key' => 'sec-coi', 'label' => 'SEC-COI', 'href' => $companyId ? route('company.corporate-formation.sec-coi', $companyId) : '#'],
        ['key' => 'sec-aoi', 'label' => 'SEC-AOI', 'href' => $companyId ? route('company.corporate-formation.sec-aoi', $companyId) : '#'],
        ['key' => 'bylaws', 'label' => 'Bylaws', 'href' => $companyId ? route('company.corporate-formation.bylaws', $companyId) : '#'],
        ['key' => 'gis', 'label' => 'GIS', 'href' => $companyId ? route('company.corporate-formation.gis', $companyId) : '#'],
        ['key' => 'notices', 'label' => 'Notices of Meeting...', 'href' => $companyId ? route('company.corporate-formation.notices', $companyId) : '#'],
        ['key' => 'minutes', 'label' => 'Minutes of Meeting...', 'href' => $companyId ? route('company.corporate-formation.minutes', $companyId) : '#'],
        ['key' => 'resolution', 'label' => 'Resolution', 'href' => $companyId ? route('company.corporate-formation.resolutions', $companyId) : '#'],
        ['key' => 'secretary', 'label' => 'Secretary...', 'href' => $companyId ? route('company.corporate-formation.secretary-certificates', $companyId) : '#'],
    ];

    $activeIndex = collect($items)->search(fn ($item) => $item['key'] === $activeTab);
    $initialScrollLeft = $activeIndex === false ? 0 : max(0, ($activeIndex - 1) * 180);
@endphp

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
        <button type="button"
                class="h-9 w-9 shrink-0 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition flex items-center justify-center"
                @click="prev()"
                aria-label="Scroll ribbon left">
            <i class="fas fa-chevron-left text-xs"></i>
        </button>

        <div x-ref="ribbon"
             x-init="$nextTick(() => { $el.scrollLeft = {{ $initialScrollLeft }}; })"
             class="min-w-0 flex-1 overflow-x-auto whitespace-nowrap scroll-smooth no-scrollbar">
            <div class="flex items-stretch min-w-max">
                @foreach ($items as $item)
                    <a href="{{ $item['href'] }}"
                       data-ribbon-card
                       class="shrink-0 w-[180px] px-4 py-3 text-sm font-medium text-center border-t border-b border-r border-gray-200 first:border-l {{ $activeTab === $item['key'] ? 'bg-blue-50 text-blue-700 border-blue-500' : 'bg-white text-gray-800 hover:bg-gray-50' }}">
                        <span class="block truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <button type="button"
                class="h-9 w-9 shrink-0 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition flex items-center justify-center"
                @click="next()"
                aria-label="Scroll ribbon right">
            <i class="fas fa-chevron-right text-xs"></i>
        </button>
    </div>

    <div class="flex items-center gap-2 shrink-0">
        <button type="button" class="w-9 h-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
            <i class="fas fa-bars text-sm"></i>
        </button>

        <button type="button" class="w-9 h-9 rounded-full border border-gray-200 text-gray-500 flex items-center justify-center hover:bg-gray-50">
            <i class="fas fa-table-cells-large text-sm"></i>
        </button>

        <div class="flex items-center">
            <button type="button" @click="openPanel=true"
                    class="px-4 h-9 rounded-l-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium flex items-center gap-2">
                <span class="text-base leading-none">+</span>
                {{ $topButtonLabel }}
            </button>

            <button type="button" class="w-10 h-9 rounded-r-full bg-blue-600 hover:bg-blue-700 text-white flex items-center justify-center border-l border-white/20">
                <i class="fas fa-caret-down text-xs"></i>
            </button>
        </div>

        <button type="button" class="w-9 h-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
            <i class="fas fa-ellipsis-v text-sm"></i>
        </button>
    </div>
</div>

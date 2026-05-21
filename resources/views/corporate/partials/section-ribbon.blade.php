@php
    $activeTab = $activeTab ?? 'gis';
    $topButtonLabel = $topButtonLabel ?? null;

    $items = [
        ['key' => 'formation', 'label' => 'SEC-COI', 'href' => route('corporate.formation')],
        ['key' => 'sec_aoi', 'label' => 'SEC-AOI', 'href' => route('corporate.sec_aoi')],
        ['key' => 'bylaws', 'label' => 'Bylaws', 'href' => route('corporate.bylaws')],
        ['key' => 'gis', 'label' => 'GIS', 'href' => route('corporate.gis')],
        ['key' => 'notices', 'label' => 'Notices of Meeting', 'href' => route('notices')],
        ['key' => 'minutes', 'label' => 'Minutes of Meeting', 'href' => route('minutes')],
        ['key' => 'resolution', 'label' => 'Resolution', 'href' => route('resolutions')],
        ['key' => 'secretary', 'label' => 'Secretary Certificate', 'href' => route('secretary-certificates')],
    ];

    $activeIndex = collect($items)->search(fn ($item) => $item['key'] === $activeTab);
    $initialScrollLeft = $activeIndex === false ? 0 : max(0, ($activeIndex - 1) * 180);

    /*
        Show the top + button only for SEC filing pages.
        For Notices, Minutes, Resolution, and Secretary Certificate,
        we hide it because those pages already have their own Add button inside the page.
    */
    $showTopAddButton = in_array($activeTab, ['formation', 'sec_aoi', 'bylaws', 'gis'], true)
        && !empty($topButtonLabel);
@endphp

<div
    x-data="{
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
    class="flex items-center justify-between gap-3 w-full min-w-0"
>
    <div class="flex items-center gap-2 flex-1 min-w-0">
        <button
            type="button"
            class="h-9 w-9 shrink-0 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition flex items-center justify-center"
            @click="prev()"
            aria-label="Scroll ribbon left"
        >
            <i class="fas fa-chevron-left text-xs"></i>
        </button>

        <div
            x-ref="ribbon"
            x-init="$nextTick(() => { $el.scrollLeft = {{ $initialScrollLeft }}; })"
            class="min-w-0 flex-1 overflow-x-auto whitespace-nowrap scroll-smooth no-scrollbar"
        >
            <div class="flex items-stretch min-w-max">
                @foreach ($items as $item)
                    <a
                        href="{{ $item['href'] }}"
                        data-ribbon-card
                        class="shrink-0 w-[180px] px-4 py-3 text-sm font-medium text-center border-t border-b border-r border-gray-200 first:border-l {{ $activeTab === $item['key'] ? 'bg-blue-50 text-blue-700 border-blue-500' : 'bg-white text-gray-800 hover:bg-gray-50' }}"
                    >
                        <span class="block truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <button
            type="button"
            class="h-9 w-9 shrink-0 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition flex items-center justify-center"
            @click="next()"
            aria-label="Scroll ribbon right"
        >
            <i class="fas fa-chevron-right text-xs"></i>
        </button>
    </div>

    @if ($showTopAddButton)
        <div class="flex items-center gap-2 shrink-0">
            <button
                type="button"
                @click="openPanel = true"
                class="px-5 h-9 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium flex items-center gap-2 shadow-sm"
            >
                <span class="text-base leading-none">+</span>
                {{ $topButtonLabel }}
            </button>
        </div>
    @endif
</div>
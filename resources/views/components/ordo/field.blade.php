@props(['label' => null, 'help' => null, 'required' => false, 'full' => false])

<div @class(['flex flex-col gap-[5px]', 'col-span-full' => $full])>
    @if ($label)
        <label class="text-[10px] font-semibold text-ordo-muted">
            {{ $label }}
            @if ($required)<span class="text-ordo-danger">*</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if ($help)
        <p class="mt-1 text-[10px] leading-[1.45] text-[#98a2b3]">{{ $help }}</p>
    @endif
</div>

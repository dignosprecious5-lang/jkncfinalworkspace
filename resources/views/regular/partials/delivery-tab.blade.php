@php
    $defaultRef = $rsatAttachments['service_memo_ref'] ?? '';
@endphp

<div class="py-2 px-1">
    <!-- Title -->
    <h2 class="text-2xl font-bold text-slate-900 mb-6" style="font-family: Inter, -apple-system, BlinkMacSystemFont, sans-serif;">Delivery &amp; Completion</h2>

    <!-- Form Row -->
    <div class="flex flex-wrap items-center gap-2 text-sm text-slate-800 mb-4">
        <label for="deliveryReference" class="font-normal text-slate-700">Transmittal / delivery evidence reference</label>
        <input type="text" id="deliveryReference" value="{{ $defaultRef }}" class="rounded-sm border border-slate-400 bg-white px-2 py-0.5 text-xs text-slate-900 w-52 focus:outline-none focus:border-slate-600 shadow-inner">
        <button type="button" onclick="recordRegularDelivery()" class="rounded-sm border border-slate-500 bg-gradient-to-b from-slate-50 to-slate-200 px-3 py-0.5 text-xs font-medium text-slate-800 hover:bg-slate-300 shadow-xs cursor-pointer">
            Record Delivery
        </button>
    </div>

    <!-- Quick Action Links -->
    <div class="flex items-center gap-2 text-xs font-medium text-purple-900 pt-1">
        <a href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'report']) }}" class="text-purple-800 underline hover:text-purple-950">Open RSAT Report</a>
        <span class="text-slate-400">-</span>
        <a href="{{ route('regular.show', ['regular' => $regular->id, 'tab' => 'attachments']) }}" class="text-purple-800 underline hover:text-purple-950">View delivery attachments</a>
    </div>
</div>

<script>
function recordRegularDelivery() {
    const input = document.getElementById('deliveryReference');
    const val = input ? input.value.trim() : '';
    if (!val) {
        alert('Please enter a transmittal or delivery evidence reference.');
        return;
    }
    alert('Delivery reference "' + val + '" recorded successfully.');
}
</script>

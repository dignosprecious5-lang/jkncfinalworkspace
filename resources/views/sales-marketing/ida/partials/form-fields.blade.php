@php
    $isEdit = $mode === 'edit';

    $dealModel = $isEdit ? 'editSelectedDealId' : 'selectedDealId';
    $fillDealMethod = $isEdit ? 'fillEditDealInfo()' : 'fillDealInfo()';
    $formName = $isEdit ? 'editForm' : 'form';
    $allocationsName = $isEdit ? 'editAllocations' : 'allocations';
    $addMethod = $isEdit ? 'addEditAllocation()' : 'addAllocation()';
    $removeMethod = $isEdit ? 'removeEditAllocation(index)' : 'removeAllocation(index)';
    $computeMethod = $isEdit ? 'computeEditAmount(index)' : 'computeAmount(index)';
    $manualMethod = $isEdit ? 'manualEditAmount(index)' : 'manualAmount(index)';
    $recomputeMethod = $isEdit ? 'recomputeEditAll()' : 'recomputeAll()';
    $totalMethod = $isEdit ? 'totalEditCommission()' : 'totalCommission()';
@endphp

<!-- DEAL DETAILS -->
<div class="bg-gray-50 border border-gray-200 rounded-2xl p-5 space-y-4">
    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
        Deal Information
    </h3>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Select Deal</label>
        <select
            x-model="{{ $dealModel }}"
            @change="{{ $fillDealMethod }}"
            class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none"
        >
            <option value="">Select Deal</option>
            @foreach($deals as $deal)
                <option value="{{ $deal['id'] }}">
                    {{ $deal['deal_code'] }} - {{ $deal['business_name'] ?: $deal['client_name'] }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Condeal Ref No.</label>
            <input
                name="condeal_ref_no"
                x-model="{{ $formName }}.condeal_ref_no"
                class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm"
                placeholder="Condeal Ref No."
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Client Name</label>
            <input
                name="client_name"
                x-model="{{ $formName }}.client_name"
                class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm"
                placeholder="Client Name"
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Business Name</label>
            <input
                name="business_name"
                x-model="{{ $formName }}.business_name"
                class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm"
                placeholder="Business Name"
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Service Area</label>
            <input
                name="service_area"
                x-model="{{ $formName }}.service_area"
                class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm"
                placeholder="Service Area"
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Product Engagement Structure</label>
            <input
                name="product_engagement_structure"
                x-model="{{ $formName }}.product_engagement_structure"
                class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm"
                placeholder="Product Engagement Structure"
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Deal Value</label>
            <input
                type="number"
                step="0.01"
                min="0"
                name="deal_value"
                x-model.number="{{ $formName }}.deal_value"
                @input="{{ $recomputeMethod }}"
                class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm"
                placeholder="0.00"
            >
        </div>
    </div>
</div>

<!-- ALLOCATIONS -->
<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
            Allocation Table
        </h3>

        <button
            type="button"
            @click="{{ $addMethod }}"
            class="inline-flex items-center gap-2 px-3 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 text-sm transition"
        >
            <i class="fas fa-plus"></i>
            Add Row
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-3 font-semibold w-12">#</th>
                    <th class="px-4 py-3 font-semibold min-w-[200px]">Earner</th>
                    <th class="px-4 py-3 font-semibold min-w-[150px]">Role</th>
                    <th class="px-4 py-3 font-semibold min-w-[170px]">Category</th>
                    <th class="px-4 py-3 font-semibold min-w-[140px]">Type</th>
                    <th class="px-4 py-3 font-semibold min-w-[120px]">Rate %</th>
                    <th class="px-4 py-3 font-semibold min-w-[150px]">Amount</th>
                    <th class="px-4 py-3 font-semibold min-w-[120px]">Status</th>
                    <th class="px-4 py-3 font-semibold w-20">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                <template x-for="(row, index) in {{ $allocationsName }}" :key="index">
                    <tr>
                        <td class="px-4 py-3 text-gray-500" x-text="index + 1"></td>

                        <td class="px-4 py-3">
                            <select
                                x-model="row.earner_id"
                                :name="`allocations[${index}][earner_id]`"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                            >
                                <option value="">Select Earner</option>
                                @foreach($earners as $earner)
                                    <option value="{{ $earner->id }}">
                                        {{ $earner->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        <td class="px-4 py-3">
                            <input
                                x-model="row.role"
                                :name="`allocations[${index}][role]`"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                placeholder="Role"
                            >
                        </td>

                        <td class="px-4 py-3">
                            <select
                                x-model="row.commission_category"
                                :name="`allocations[${index}][commission_category]`"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                            >
                                <option value="">Select Category</option>
                                <option value="Sales">Sales</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Lead Generation">Lead Generation</option>
                                <option value="Referral">Referral</option>
                                <option value="Consultant">Consultant</option>
                                <option value="Other">Other</option>
                            </select>
                        </td>

                        <td class="px-4 py-3">
                            <select
                                x-model="row.commission_type"
                                @change="{{ $computeMethod }}"
                                :name="`allocations[${index}][commission_type]`"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                            >
                                <option value="Percentage">Percentage</option>
                                <option value="Fixed">Fixed</option>
                            </select>
                        </td>

                        <td class="px-4 py-3">
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                x-model.number="row.commission_rate"
                                @input="{{ $computeMethod }}"
                                :name="`allocations[${index}][commission_rate]`"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                placeholder="0.00"
                                :readonly="row.commission_type === 'Fixed'"
                                :class="row.commission_type === 'Fixed' ? 'bg-gray-100 text-gray-400' : ''"
                            >
                        </td>

                        <td class="px-4 py-3">
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                x-model.number="row.commission_amount"
                                @input="{{ $manualMethod }}"
                                :name="`allocations[${index}][commission_amount]`"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                placeholder="0.00"
                                :readonly="row.commission_type === 'Percentage'"
                                :class="row.commission_type === 'Percentage' ? 'bg-gray-100 text-gray-600' : ''"
                            >
                        </td>

                        <td class="px-4 py-3">
                            <select
                                x-model="row.status"
                                :name="`allocations[${index}][status]`"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                            >
                                <option value="Pending">Pending</option>
                                <option value="For Payout">For Payout</option>
                                <option value="Paid">Paid</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </td>

                        <td class="px-4 py-3">
                            <button
                                type="button"
                                @click="{{ $removeMethod }}"
                                class="text-red-500 hover:text-red-700 text-sm"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>

<!-- TOTAL -->
<div class="flex justify-end">
    <div class="bg-blue-50 border border-blue-100 rounded-2xl px-5 py-4 min-w-[260px]">
        <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide">
            Total Commission
        </p>
        <p class="text-2xl font-bold text-blue-900 mt-1">
            ₱ <span x-text="formatMoney({{ $totalMethod }})"></span>
        </p>
    </div>
</div>
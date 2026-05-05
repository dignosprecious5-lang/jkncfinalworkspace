@extends('layouts.app')

@section('title', 'Sales & Marketing | IDA Records')

@section('content')
<div class="flex-1 overflow-y-auto p-6" x-data="idaPage()">
    <div class="max-w-7xl mx-auto space-y-6">

        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-semibold mb-1">Please fix the following:</p>
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- HEADER -->
        <div class="bg-white border border-gray-200 rounded-2xl p-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Transactions (IDA)</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Connect deals to commission earners and compute commissions automatically.
                </p>
            </div>

            @if(auth()->user()->hasPermission('create_sales_marketing'))
                <button
                    type="button"
                    @click="openAdd = true"
                    class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-xl hover:bg-blue-700 transition text-sm font-medium"
                >
                    <i class="fas fa-plus"></i>
                    Add IDA
                </button>
            @endif
        </div>

        <!-- TABLE -->
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                    IDA Transaction List
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-gray-600">
                            <th class="px-4 py-3 font-semibold">Condeal</th>
                            <th class="px-4 py-3 font-semibold">Client</th>
                            <th class="px-4 py-3 font-semibold">Business</th>
                            <th class="px-4 py-3 font-semibold">Service Area</th>
                            <th class="px-4 py-3 font-semibold">Deal Value</th>
                            <th class="px-4 py-3 font-semibold">Earner</th>
                            <th class="px-4 py-3 font-semibold">Role</th>
                            <th class="px-4 py-3 font-semibold">Category</th>
                            <th class="px-4 py-3 font-semibold">Type</th>
                            <th class="px-4 py-3 font-semibold">Rate</th>
                            <th class="px-4 py-3 font-semibold">Amount</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse($idas as $ida)
                            @if($ida->allocations->count())
                                @foreach($ida->allocations as $allocation)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">
                                            {{ $ida->condeal_ref_no ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $ida->client_name ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $ida->business_name ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $ida->service_area ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            ₱ {{ number_format((float) $ida->deal_value, 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ optional($allocation->earner)->full_name ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $allocation->role ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $allocation->commission_category ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $allocation->commission_type ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if($allocation->commission_type === 'Percentage')
                                                {{ number_format((float) $allocation->commission_rate, 2) }}%
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap font-semibold text-gray-900">
                                            ₱ {{ number_format((float) $allocation->commission_amount, 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700 border border-yellow-100">
                                                {{ $allocation->status ?? 'Pending' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('sales-marketing.ida.show', $ida) }}"
                                                   class="text-blue-600 hover:text-blue-800 font-medium">
                                                    View
                                                </a>

                                                @if(auth()->user()->hasPermission('create_sales_marketing'))
                                                    <button
                                                        type="button"
                                                        @click="openEditModal({{ $ida->id }})"
                                                        class="text-amber-600 hover:text-amber-800 font-medium"
                                                    >
                                                        Edit
                                                    </button>

                                                    <form
                                                        method="POST"
                                                        action="{{ route('sales-marketing.ida.destroy', $ida) }}"
                                                        onsubmit="return confirm('Delete this IDA record? This will also delete its allocation rows.');"
                                                    >
                                                        @csrf
                                                        @method('DELETE')

                                                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium">
                                                            Delete
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">
                                        {{ $ida->condeal_ref_no ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        {{ $ida->client_name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        {{ $ida->business_name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        {{ $ida->service_area ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        ₱ {{ number_format((float) $ida->deal_value, 2) }}
                                    </td>
                                    <td colspan="7" class="px-4 py-3 text-gray-400">
                                        No allocation rows
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('sales-marketing.ida.show', $ida) }}"
                                               class="text-blue-600 hover:text-blue-800 font-medium">
                                                View
                                            </a>

                                            @if(auth()->user()->hasPermission('create_sales_marketing'))
                                                <button
                                                    type="button"
                                                    @click="openEditModal({{ $ida->id }})"
                                                    class="text-amber-600 hover:text-amber-800 font-medium"
                                                >
                                                    Edit
                                                </button>

                                                <form
                                                    method="POST"
                                                    action="{{ route('sales-marketing.ida.destroy', $ida) }}"
                                                    onsubmit="return confirm('Delete this IDA record? This will also delete its allocation rows.');"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="13" class="px-6 py-10 text-center text-gray-400">
                                    No IDA transactions yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ADD IDA MODAL -->
    <div
        x-show="openAdd"
        x-transition
        x-cloak
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 p-4"
    >
        <div
            @click.outside="openAdd = false"
            class="bg-white w-full max-w-6xl rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto"
        >
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Add IDA Record</h2>
                    <p class="text-sm text-gray-500">Select a deal and add commission allocation rows.</p>
                </div>

                <button type="button" @click="openAdd = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('sales-marketing.ida.store') }}" class="p-6 space-y-6">
                @csrf

                <input type="hidden" name="deal_id" x-model="form.deal_id">

                @include('sales-marketing.ida.partials.form-fields', [
                    'mode' => 'add',
                    'deals' => $deals,
                    'earners' => $earners,
                ])

                <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                    <button
                        type="button"
                        @click="openAdd = false"
                        class="px-4 py-2 border border-gray-300 rounded-xl text-sm text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700"
                    >
                        Save IDA
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT IDA MODAL -->
    <div
        x-show="openEdit"
        x-transition
        x-cloak
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 p-4"
    >
        <div
            @click.outside="openEdit = false"
            class="bg-white w-full max-w-6xl rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto"
        >
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Edit IDA Record</h2>
                    <p class="text-sm text-gray-500">Update deal information and allocation rows.</p>
                </div>

                <button type="button" @click="openEdit = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" :action="editActionUrl" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <input type="hidden" name="deal_id" x-model="editForm.deal_id">

                @include('sales-marketing.ida.partials.form-fields', [
                    'mode' => 'edit',
                    'deals' => $deals,
                    'earners' => $earners,
                ])

                <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                    <button
                        type="button"
                        @click="openEdit = false"
                        class="px-4 py-2 border border-gray-300 rounded-xl text-sm text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700"
                    >
                        Update IDA
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function idaPage() {
    return {
        openAdd: false,
        openEdit: false,

        selectedDealId: '',
        editSelectedDealId: '',

        deals: @json($deals),
        idasForEdit: @json($idasForEdit),

        editActionUrl: '',

        form: {
            deal_id: '',
            condeal_ref_no: '',
            client_name: '',
            business_name: '',
            service_area: '',
            product_engagement_structure: '',
            deal_value: 0,
        },

        editForm: {
            deal_id: '',
            condeal_ref_no: '',
            client_name: '',
            business_name: '',
            service_area: '',
            product_engagement_structure: '',
            deal_value: 0,
        },

        allocations: [
            {
                earner_id: '',
                role: '',
                commission_category: '',
                commission_type: 'Percentage',
                commission_rate: 0,
                commission_amount: 0,
                status: 'Pending',
            }
        ],

        editAllocations: [],

        fillDealInfo() {
            let deal = this.deals.find(d => String(d.id) === String(this.selectedDealId));

            if (!deal) {
                this.form.deal_id = '';
                this.form.condeal_ref_no = '';
                this.form.client_name = '';
                this.form.business_name = '';
                this.form.service_area = '';
                this.form.product_engagement_structure = '';
                this.form.deal_value = 0;
                this.recomputeAll();
                return;
            }

            this.form.deal_id = deal.id;
            this.form.condeal_ref_no = deal.deal_code || '';
            this.form.client_name = deal.client_name || '';
            this.form.business_name = deal.business_name || '';
            this.form.service_area = deal.service_area || '';
            this.form.product_engagement_structure = deal.product_engagement_structure || '';
            this.form.deal_value = Number(deal.deal_value || 0);

            this.recomputeAll();
        },

        fillEditDealInfo() {
            let deal = this.deals.find(d => String(d.id) === String(this.editSelectedDealId));

            if (!deal) {
                this.editForm.deal_id = '';
                this.editForm.condeal_ref_no = '';
                this.editForm.client_name = '';
                this.editForm.business_name = '';
                this.editForm.service_area = '';
                this.editForm.product_engagement_structure = '';
                this.editForm.deal_value = 0;
                this.recomputeEditAll();
                return;
            }

            this.editForm.deal_id = deal.id;
            this.editForm.condeal_ref_no = deal.deal_code || '';
            this.editForm.client_name = deal.client_name || '';
            this.editForm.business_name = deal.business_name || '';
            this.editForm.service_area = deal.service_area || '';
            this.editForm.product_engagement_structure = deal.product_engagement_structure || '';
            this.editForm.deal_value = Number(deal.deal_value || 0);

            this.recomputeEditAll();
        },

        addAllocation() {
            this.allocations.push({
                earner_id: '',
                role: '',
                commission_category: '',
                commission_type: 'Percentage',
                commission_rate: 0,
                commission_amount: 0,
                status: 'Pending',
            });
        },

        removeAllocation(index) {
            if (this.allocations.length > 1) {
                this.allocations.splice(index, 1);
            }
        },

        computeAmount(index) {
            let row = this.allocations[index];
            let dealValue = Number(this.form.deal_value || 0);
            let rate = Number(row.commission_rate || 0);

            if (row.commission_type === 'Percentage') {
                row.commission_amount = Number((dealValue * (rate / 100)).toFixed(2));
            }

            if (row.commission_type === 'Fixed') {
                row.commission_rate = 0;
            }
        },

        manualAmount(index) {
            let row = this.allocations[index];

            if (row.commission_type === 'Percentage') {
                this.computeAmount(index);
            }
        },

        recomputeAll() {
            this.allocations.forEach((row, index) => {
                this.computeAmount(index);
            });
        },

        totalCommission() {
            return this.allocations.reduce((total, row) => {
                return total + Number(row.commission_amount || 0);
            }, 0);
        },

        openEditModal(idaId) {
            let ida = this.idasForEdit.find(item => Number(item.id) === Number(idaId));

            if (!ida) {
                alert('IDA record not found.');
                return;
            }

            this.editActionUrl = ida.update_url;
            this.editSelectedDealId = ida.deal_id || '';

            this.editForm = {
                deal_id: ida.deal_id || '',
                condeal_ref_no: ida.condeal_ref_no || '',
                client_name: ida.client_name || '',
                business_name: ida.business_name || '',
                service_area: ida.service_area || '',
                product_engagement_structure: ida.product_engagement_structure || '',
                deal_value: Number(ida.deal_value || 0),
            };

            if (ida.allocations && ida.allocations.length > 0) {
                this.editAllocations = ida.allocations.map(row => {
                    return {
                        earner_id: row.earner_id || '',
                        role: row.role || '',
                        commission_category: row.commission_category || '',
                        commission_type: row.commission_type || 'Percentage',
                        commission_rate: Number(row.commission_rate || 0),
                        commission_amount: Number(row.commission_amount || 0),
                        status: row.status || 'Pending',
                    };
                });
            } else {
                this.editAllocations = [
                    {
                        earner_id: '',
                        role: '',
                        commission_category: '',
                        commission_type: 'Percentage',
                        commission_rate: 0,
                        commission_amount: 0,
                        status: 'Pending',
                    }
                ];
            }

            this.openEdit = true;
        },

        addEditAllocation() {
            this.editAllocations.push({
                earner_id: '',
                role: '',
                commission_category: '',
                commission_type: 'Percentage',
                commission_rate: 0,
                commission_amount: 0,
                status: 'Pending',
            });
        },

        removeEditAllocation(index) {
            if (this.editAllocations.length > 1) {
                this.editAllocations.splice(index, 1);
            }
        },

        computeEditAmount(index) {
            let row = this.editAllocations[index];
            let dealValue = Number(this.editForm.deal_value || 0);
            let rate = Number(row.commission_rate || 0);

            if (row.commission_type === 'Percentage') {
                row.commission_amount = Number((dealValue * (rate / 100)).toFixed(2));
            }

            if (row.commission_type === 'Fixed') {
                row.commission_rate = 0;
            }
        },

        manualEditAmount(index) {
            let row = this.editAllocations[index];

            if (row.commission_type === 'Percentage') {
                this.computeEditAmount(index);
            }
        },

        recomputeEditAll() {
            this.editAllocations.forEach((row, index) => {
                this.computeEditAmount(index);
            });
        },

        totalEditCommission() {
            return this.editAllocations.reduce((total, row) => {
                return total + Number(row.commission_amount || 0);
            }, 0);
        },

        formatMoney(value) {
            return Number(value || 0).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    }
}
</script>
@endpush
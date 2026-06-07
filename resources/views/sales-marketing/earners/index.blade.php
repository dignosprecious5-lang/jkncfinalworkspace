@extends('layouts.app')

@section('title', 'Incentive Management | Commission Earners')

@section('content')
<div class="flex-1 overflow-y-auto p-6" x-data="earnersPage()">
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

        <div class="bg-white border border-gray-200 rounded-2xl p-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Commission Earners</h1>
                <p class="text-sm text-gray-500 mt-1">Master list of all Incentive Management commission earners.</p>
            </div>

            @if(auth()->user()->hasPermission('create_sales_marketing'))
                <button
                    type="button"
                    @click="openAdd = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 text-sm font-medium"
                >
                    <i class="fas fa-plus"></i>
                    Add Earner
                </button>
            @endif
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Master List</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-gray-600">
                            <th class="px-4 py-3 font-semibold">Name</th>
                            <th class="px-4 py-3 font-semibold">Source</th>
                            <th class="px-4 py-3 font-semibold">Email</th>
                            <th class="px-4 py-3 font-semibold">Mobile</th>
                            <th class="px-4 py-3 font-semibold">Transactions</th>
                            <th class="px-4 py-3 font-semibold">Total Commission</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse($earners as $earner)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap">
                                    {{ $earner->full_name ?: '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ ucfirst($earner->source_type) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ $earner->email ?: '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ $earner->mobile_number ?: '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ $earner->transaction_count ?? 0 }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-semibold">
                                    ₱ {{ number_format((float) ($earner->total_commission ?? 0), 2) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $earner->status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $earner->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('sales-marketing.earners.show', $earner) }}"
                                           class="text-blue-600 hover:text-blue-800 font-medium">
                                            View
                                        </a>

                                        @if(auth()->user()->hasPermission('create_sales_marketing'))
                                            <form method="POST"
                                                  action="{{ route('sales-marketing.earners.destroy', $earner) }}"
                                                  onsubmit="return confirm('Delete this earner?');">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                        class="text-red-600 hover:text-red-800 font-medium">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-gray-400">
                                    No earners yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ADD MODAL -->
    <div
        x-show="openAdd"
        x-cloak
        x-transition
        class="fixed inset-0 z-[9999] bg-black/40 flex items-center justify-center p-4"
    >
        <div
            @click.outside="openAdd = false"
            class="bg-white w-full max-w-3xl rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto"
        >
            <form method="POST" action="{{ route('sales-marketing.earners.store') }}">
                @csrf

                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Add Commission Earner</h2>
                        <p class="text-sm text-gray-500">Add manually or link from contacts/employees.</p>
                    </div>

                    <button type="button" @click="openAdd = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Source Type</label>
                        <select
                            name="source_type"
                            x-model="form.source_type"
                            class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm"
                        >
                            <option value="manual">Manual</option>
                            <option value="contact">Contact</option>
                            <option value="employee">Employee</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select
                            name="status"
                            class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm"
                        >
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <template x-if="form.source_type === 'contact'">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select Contact</label>
                            <select name="source_id" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm">
                                <option value="">Select Contact</option>
                                @foreach($contacts as $contact)
                                    <option value="{{ $contact->id }}">
                                        {{ trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? '')) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </template>

                    <template x-if="form.source_type === 'employee'">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select Employee</label>
                            <select name="source_id" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm">
                                <option value="">Select Employee</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">
                                        {{ $employee->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </template>

                    <template x-if="form.source_type === 'manual'">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <input
                                type="text"
                                name="full_name"
                                class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm"
                                placeholder="Full Name"
                            >
                        </div>
                    </template>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input
                            type="email"
                            name="email"
                            class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm"
                            placeholder="Email"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                        <input
                            type="text"
                            name="mobile_number"
                            class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm"
                            placeholder="Mobile Number"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                        <input
                            type="text"
                            name="bank_name"
                            class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm"
                            placeholder="Bank Name"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
                        <input
                            type="text"
                            name="account_name"
                            class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm"
                            placeholder="Account Name"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Account Number</label>
                        <input
                            type="text"
                            name="account_number"
                            class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm"
                            placeholder="Account Number"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">TIN</label>
                        <input
                            type="text"
                            name="tin"
                            class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm"
                            placeholder="TIN"
                        >
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-2">
                    <button
                        type="button"
                        @click="openAdd = false"
                        class="px-4 py-2 border border-gray-300 rounded-xl text-sm text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>

                    <button class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-blue-700">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function earnersPage() {
    return {
        openAdd: false,
        form: {
            source_type: 'manual'
        }
    }
}
</script>
@endpush

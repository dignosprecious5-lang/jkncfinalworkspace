@extends('layouts.app')

@section('title', 'Sales & Marketing | Earner Profile')

@section('content')
<div class="flex-1 overflow-y-auto p-6">
    <div class="max-w-7xl mx-auto space-y-6">

        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">{{ $earner->full_name }}</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Earner Profile and IDA transaction history.
                    </p>
                </div>

                <a href="{{ route('sales-marketing.earners.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-sm font-medium rounded-xl hover:bg-gray-50 transition">
                    <i class="fas fa-arrow-left"></i>
                    Back
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Transactions</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">{{ $allocations->count() }}</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Commission</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">₱ {{ number_format((float) $totalCommission, 2) }}</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">For Payout</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">₱ {{ number_format((float) $forPayoutCommission, 2) }}</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Paid</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">₱ {{ number_format((float) $paidCommission, 2) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 bg-white border border-gray-200 rounded-2xl p-6 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Basic Information</h2>

                <div>
                    <p class="text-xs text-gray-500">Full Name</p>
                    <p class="text-sm text-gray-900 font-medium">{{ $earner->full_name ?: '—' }}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Source Type</p>
                    <p class="text-sm text-gray-900 capitalize">{{ $earner->source_type ?: '—' }}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Email</p>
                    <p class="text-sm text-gray-900">{{ $earner->email ?: '—' }}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Mobile Number</p>
                    <p class="text-sm text-gray-900">{{ $earner->mobile_number ?: '—' }}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">TIN</p>
                    <p class="text-sm text-gray-900">{{ $earner->tin ?: '—' }}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Status</p>
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $earner->status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                        {{ $earner->status }}
                    </span>
                </div>
            </div>

            <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl p-6 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Bank Details</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500">Bank Name</p>
                        <p class="text-sm text-gray-900">{{ $earner->bank_name ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500">Account Name</p>
                        <p class="text-sm text-gray-900">{{ $earner->account_name ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500">Account Number</p>
                        <p class="text-sm text-gray-900">{{ $earner->account_number ?: '—' }}</p>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100">
                    <p class="text-xs text-gray-500">Pending Commission</p>
                    <p class="text-lg font-semibold text-gray-900">
                        ₱ {{ number_format((float) $pendingCommission, 2) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">IDA Transactions</h2>
                    <p class="text-xs text-gray-500 mt-1">
                        All IDA allocation rows connected to this commission earner.
                    </p>
                </div>

                <button
                    type="button"
                    disabled
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-500 text-sm font-medium rounded-xl cursor-not-allowed"
                >
                    <i class="fas fa-wallet"></i>
                    Request for Payout
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-gray-600">
                            <th class="px-4 py-3 font-semibold">Condeal</th>
                            <th class="px-4 py-3 font-semibold">Client</th>
                            <th class="px-4 py-3 font-semibold">Business</th>
                            <th class="px-4 py-3 font-semibold">Deal Value</th>
                            <th class="px-4 py-3 font-semibold">Role</th>
                            <th class="px-4 py-3 font-semibold">Category</th>
                            <th class="px-4 py-3 font-semibold">Type</th>
                            <th class="px-4 py-3 font-semibold">Rate</th>
                            <th class="px-4 py-3 font-semibold">Commission</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse($allocations as $allocation)
                            @php
                                $ida = $allocation->ida;
                            @endphp

                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">
                                    {{ optional($ida)->condeal_ref_no ?? '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ optional($ida)->client_name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ optional($ida)->business_name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    ₱ {{ number_format((float) (optional($ida)->deal_value ?? 0), 2) }}
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
                                    @php
                                        $statusClass = match($allocation->status) {
                                            'Paid' => 'bg-green-100 text-green-700',
                                            'For Payout' => 'bg-blue-100 text-blue-700',
                                            'Cancelled' => 'bg-red-100 text-red-700',
                                            default => 'bg-yellow-100 text-yellow-700',
                                        };
                                    @endphp

                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                        {{ $allocation->status ?? 'Pending' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($ida)
                                        <a href="{{ route('sales-marketing.ida.show', $ida) }}"
                                           class="text-blue-600 hover:text-blue-800 font-medium">
                                            View IDA
                                        </a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-6 py-10 text-center text-gray-400">
                                    No IDA transactions yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if($allocations->count())
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="8" class="px-4 py-3 text-right font-semibold text-gray-700">
                                    Total Commission
                                </td>
                                <td class="px-4 py-3 font-bold text-gray-900">
                                    ₱ {{ number_format((float) $totalCommission, 2) }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
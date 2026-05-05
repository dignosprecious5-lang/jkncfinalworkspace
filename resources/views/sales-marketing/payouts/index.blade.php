@extends('layouts.app')

@section('title', 'Sales & Marketing | Payout Requests')

@section('content')
<div class="flex-1 overflow-y-auto p-6">
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

        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Payout Requests</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Review commission payout requests and mark approved payouts as paid.
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">For Payout Requests</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">{{ $payouts->count() }}</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total For Payout</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">
                    ₱ {{ number_format((float) $totalForPayout, 2) }}
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Paid</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">
                    ₱ {{ number_format((float) $totalPaid, 2) }}
                </p>
            </div>
        </div>

        {{-- PENDING PAYOUT REQUESTS --}}
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                    Pending Payout Requests
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    These are accepted IDA allocations requested for payout.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-gray-600">
                            <th class="px-4 py-3 font-semibold">Earner</th>
                            <th class="px-4 py-3 font-semibold">Condeal</th>
                            <th class="px-4 py-3 font-semibold">Client</th>
                            <th class="px-4 py-3 font-semibold">Business</th>
                            <th class="px-4 py-3 font-semibold">Role</th>
                            <th class="px-4 py-3 font-semibold">Category</th>
                            <th class="px-4 py-3 font-semibold">Amount</th>
                            <th class="px-4 py-3 font-semibold">Bank</th>
                            <th class="px-4 py-3 font-semibold">Account Name</th>
                            <th class="px-4 py-3 font-semibold">Account No.</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Requested At</th>
                            <th class="px-4 py-3 font-semibold">Requested By</th>
                            <th class="px-4 py-3 font-semibold">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse($payouts as $payout)
                            @php
                                $earner = $payout->earner;
                                $ida = $payout->ida;
                            @endphp

                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">
                                    @if($earner)
                                        <a href="{{ route('sales-marketing.earners.show', $earner) }}"
                                           class="text-blue-600 hover:text-blue-800 font-medium">
                                            {{ $earner->full_name }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ optional($ida)->condeal_ref_no ?? '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ optional($ida)->client_name ?? '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ optional($ida)->business_name ?? '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ $payout->role ?? '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ $payout->commission_category ?? '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap font-semibold text-gray-900">
                                    ₱ {{ number_format((float) $payout->commission_amount, 2) }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ optional($earner)->bank_name ?? '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ optional($earner)->account_name ?? '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ optional($earner)->account_number ?? '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                        {{ $payout->status }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ $payout->requested_at ? $payout->requested_at->format('M d, Y h:i A') : '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ optional($payout->requestedBy)->name ?? '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        @if($ida)
                                            <a href="{{ route('sales-marketing.ida.show', $ida) }}"
                                               class="text-blue-600 hover:text-blue-800 font-medium">
                                                View IDA
                                            </a>
                                        @endif

                                        <form method="POST"
                                              action="{{ route('sales-marketing.payouts.mark-paid', $payout) }}"
                                              onsubmit="return confirm('Mark this payout as paid?');">
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="text-green-600 hover:text-green-800 font-medium"
                                            >
                                                Mark Paid
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="px-6 py-10 text-center text-gray-400">
                                    No payout requests yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if($payouts->count())
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-right font-semibold text-gray-700">
                                    Total For Payout
                                </td>
                                <td class="px-4 py-3 font-bold text-gray-900">
                                    ₱ {{ number_format((float) $totalForPayout, 2) }}
                                </td>
                                <td colspan="7"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- RECENT PAID PAYOUTS --}}
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                    Recent Paid Payouts
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Latest payout records marked as paid.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-gray-600">
                            <th class="px-4 py-3 font-semibold">Earner</th>
                            <th class="px-4 py-3 font-semibold">Condeal</th>
                            <th class="px-4 py-3 font-semibold">Client</th>
                            <th class="px-4 py-3 font-semibold">Business</th>
                            <th class="px-4 py-3 font-semibold">Amount</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Requested At</th>
                            <th class="px-4 py-3 font-semibold">Requested By</th>
                            <th class="px-4 py-3 font-semibold">Paid At</th>
                            <th class="px-4 py-3 font-semibold">Paid By</th>
                            <th class="px-4 py-3 font-semibold">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse($paidPayouts as $paid)
                            @php
                                $earner = $paid->earner;
                                $ida = $paid->ida;
                            @endphp

                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">
                                    @if($earner)
                                        <a href="{{ route('sales-marketing.earners.show', $earner) }}"
                                           class="text-blue-600 hover:text-blue-800 font-medium">
                                            {{ $earner->full_name }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ optional($ida)->condeal_ref_no ?? '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ optional($ida)->client_name ?? '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ optional($ida)->business_name ?? '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap font-semibold text-gray-900">
                                    ₱ {{ number_format((float) $paid->commission_amount, 2) }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        {{ $paid->status }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ $paid->requested_at ? $paid->requested_at->format('M d, Y h:i A') : '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ optional($paid->requestedBy)->name ?? '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ $paid->paid_at ? $paid->paid_at->format('M d, Y h:i A') : '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ optional($paid->paidBy)->name ?? '—' }}
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
                                    No paid payouts yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
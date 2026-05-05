@extends('layouts.app')

@section('title', 'Sales & Marketing | View IDA')

@section('content')
@php
    $workflowStatus = $ida->workflow_status ?: 'Uploaded';

    $workflowClass = match($workflowStatus) {
        'Accepted' => 'bg-green-100 text-green-700 border-green-200',
        'Submitted' => 'bg-blue-100 text-blue-700 border-blue-200',
        'Reverted' => 'bg-red-100 text-red-700 border-red-200',
        default => 'bg-yellow-100 text-yellow-700 border-yellow-200',
    };

    $totalCommission = $ida->allocations->sum('commission_amount');
@endphp

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
                    <h1 class="text-2xl font-semibold text-gray-900">IDA Record</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        View Incentive Distribution & Allocation details.
                    </p>

                    <div class="mt-3">
                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold border {{ $workflowClass }}">
                            {{ $workflowStatus }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if(auth()->user()->hasPermission('create_sales_marketing') && in_array($workflowStatus, ['Uploaded', 'Reverted'], true))
                        <form method="POST" action="{{ route('sales-marketing.ida.submit', $ida) }}">
                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 transition"
                            >
                                <i class="fas fa-paper-plane"></i>
                                Submit
                            </button>
                        </form>
                    @endif

                    @if(auth()->user()->hasPermission('approve_sales_marketing') && $workflowStatus === 'Submitted')
                        <form method="POST" action="{{ route('sales-marketing.ida.accept', $ida) }}">
                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-xl hover:bg-green-700 transition"
                            >
                                <i class="fas fa-check"></i>
                                Accept
                            </button>
                        </form>

                        <form method="POST" action="{{ route('sales-marketing.ida.revert', $ida) }}">
                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-xl hover:bg-red-700 transition"
                            >
                                <i class="fas fa-rotate-left"></i>
                                Revert
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('sales-marketing.ida.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-sm font-medium rounded-xl hover:bg-gray-50 transition">
                        <i class="fas fa-arrow-left"></i>
                        Back
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Deal Value</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">
                    ₱ {{ number_format((float) $ida->deal_value, 2) }}
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Commission</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">
                    ₱ {{ number_format((float) $totalCommission, 2) }}
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Allocations</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">
                    {{ $ida->allocations->count() }}
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Workflow Status</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">
                    {{ $workflowStatus }}
                </p>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-5">
                Deal Information
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-sm">
                <div>
                    <p class="text-xs text-gray-500">Condeal Ref No.</p>
                    <p class="font-medium text-gray-900">{{ $ida->condeal_ref_no ?: '—' }}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Client Name</p>
                    <p class="font-medium text-gray-900">{{ $ida->client_name ?: '—' }}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Business Name</p>
                    <p class="font-medium text-gray-900">{{ $ida->business_name ?: '—' }}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Service Area</p>
                    <p class="font-medium text-gray-900">{{ $ida->service_area ?: '—' }}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Product Engagement Structure</p>
                    <p class="font-medium text-gray-900">{{ $ida->product_engagement_structure ?: '—' }}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Deal Value</p>
                    <p class="font-medium text-gray-900">₱ {{ number_format((float) $ida->deal_value, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                    Allocation Table
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-gray-600">
                            <th class="px-4 py-3 font-semibold">#</th>
                            <th class="px-4 py-3 font-semibold">Earner</th>
                            <th class="px-4 py-3 font-semibold">Role</th>
                            <th class="px-4 py-3 font-semibold">Category</th>
                            <th class="px-4 py-3 font-semibold">Type</th>
                            <th class="px-4 py-3 font-semibold">Rate</th>
                            <th class="px-4 py-3 font-semibold">Amount</th>
                            <th class="px-4 py-3 font-semibold">Payout Status</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse($ida->allocations as $allocation)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ $loop->iteration }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">
                                    {{ optional($allocation->earner)->full_name ?? '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ $allocation->role ?: '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ $allocation->commission_category ?: '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ $allocation->commission_type ?: '—' }}
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
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                        {{ $allocation->status ?: 'Pending' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-gray-400">
                                    No allocation rows found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if($ida->allocations->count())
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-right font-semibold text-gray-700">
                                    Total Commission
                                </td>
                                <td class="px-4 py-3 font-bold text-gray-900">
                                    ₱ {{ number_format((float) $totalCommission, 2) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
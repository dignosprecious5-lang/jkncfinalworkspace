@extends('layouts.app')

@section('title', 'Sales & Marketing | IDA Sheet Preview')

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

    $preparedBy = $ida->creator ?? null;

    if (!$preparedBy && !empty($ida->created_by)) {
        $preparedBy = \App\Models\User::find($ida->created_by);
    }

    $submittedBy = $ida->submittedBy ?? null;
    $submittedAt = $ida->submitted_at ?? null;

    $acceptedBy = $ida->acceptedBy ?? null;
    $acceptedAt = $ida->accepted_at ?? null;

    $revertedBy = $ida->revertedBy ?? null;
    $revertedAt = $ida->reverted_at ?? null;

    $reviewedBy = $acceptedBy ?: $revertedBy;
    $reviewedAt = $acceptedAt ?: $revertedAt;

    $salesMarketingBy = $acceptedBy;
    $dateSigned = $acceptedAt;

    $rowsNeeded = max(0, 5 - $ida->allocations->count());

    $statusBadgeClass = function ($status) {
        return match($status) {
            'Paid' => 'bg-green-100 text-green-700 border-green-200',
            'For Payout' => 'bg-blue-100 text-blue-700 border-blue-200',
            'Cancelled' => 'bg-red-100 text-red-700 border-red-200',
            default => 'bg-yellow-100 text-yellow-700 border-yellow-200',
        };
    };
@endphp

@push('styles')
<style>
    @page {
        size: A4 landscape;
        margin: 10mm;
    }

    .ida-print-sheet {
        width: 1120px;
        max-width: 100%;
        margin: 0 auto;
        background: #ffffff;
        color: #000000;
        font-family: "Times New Roman", Times, serif;
    }

    .sheet-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .sheet-table th,
    .sheet-table td {
        border: 1px solid #000;
        padding: 5px 6px;
        font-size: 12px;
        line-height: 1.15;
        vertical-align: middle;
    }

    .sheet-table th {
        font-weight: 700;
        text-align: center;
    }

    .sheet-no-border td,
    .sheet-no-border th {
        border: none;
    }

    .sheet-label {
        font-weight: 700;
        font-size: 11px;
    }

    .sheet-value {
        font-weight: 400;
        font-size: 12px;
    }

    .signature-line {
        border-bottom: 1px solid #000;
        min-height: 18px;
        padding-top: 2px;
    }

    @media print {
        body {
            background: white !important;
        }

        header,
        aside,
        .print-hidden {
            display: none !important;
        }

        main {
            overflow: visible !important;
        }

        .ida-print-wrapper {
            padding: 0 !important;
            margin: 0 !important;
            background: white !important;
        }

        .ida-print-card {
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            padding: 0 !important;
        }

        .ida-print-sheet {
            width: 100% !important;
            max-width: none !important;
        }
    }
</style>
@endpush

<div class="flex-1 overflow-y-auto p-6 ida-print-wrapper" x-data="{ viewMode: 'summary' }">
    <div class="max-w-7xl mx-auto space-y-6">

        @if(session('success'))
            <div class="print-hidden rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="print-hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-semibold mb-1">Please fix the following:</p>
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- PAGE HEADER --}}
        <div class="print-hidden bg-white border border-gray-200 rounded-2xl p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        Sales & Marketing
                    </p>

                    <h1 class="text-2xl font-semibold text-gray-900 mt-1">
                        Incentive Distribution & Allocation Sheet
                    </h1>

                    <p class="text-sm text-gray-500 mt-1">
                        IDA Details / Sheet Preview
                    </p>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold border {{ $workflowClass }}">
                            {{ $workflowStatus }}
                        </span>

                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold border bg-gray-100 text-gray-700 border-gray-200">
                            {{ $ida->condeal_ref_no ?: 'No Condeal Ref' }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2">
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

                    <button
                        type="button"
                        x-show="viewMode === 'printable'"
                        onclick="window.print()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-xl hover:bg-gray-800 transition"
                    >
                        <i class="fas fa-file-pdf"></i>
                        Print / Save PDF
                    </button>

                    <a href="{{ route('sales-marketing.ida.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-sm font-medium rounded-xl hover:bg-gray-50 transition">
                        <i class="fas fa-arrow-left"></i>
                        Back
                    </a>
                </div>
            </div>
        </div>

        {{-- VIEW OPTIONS --}}
        <div class="print-hidden bg-white border border-gray-200 rounded-2xl p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">View Options</h2>
                    <p class="text-xs text-gray-500 mt-1">
                        Choose between the system summary view and the printable IDA sheet.
                    </p>
                </div>

                <div class="inline-flex rounded-xl border border-gray-200 bg-gray-50 p-1">
                    <button
                        type="button"
                        @click="viewMode = 'summary'"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition"
                        :class="viewMode === 'summary' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-white'"
                    >
                        <i class="fas fa-table-list mr-1"></i>
                        Summary View
                    </button>

                    <button
                        type="button"
                        @click="viewMode = 'printable'"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition"
                        :class="viewMode === 'printable' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-white'"
                    >
                        <i class="fas fa-file-pdf mr-1"></i>
                        Printable Sheet
                    </button>
                </div>
            </div>
        </div>

        {{-- SUMMARY VIEW --}}
        <div x-show="viewMode === 'summary'" x-cloak class="space-y-6 print-hidden">
            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900 uppercase tracking-wide">
                                IDA Sheet
                            </h2>
                            <p class="text-sm text-gray-500 mt-1">
                                Incentive Distribution & Allocation Record
                            </p>
                        </div>

                        <div class="text-sm text-gray-700 md:text-right">
                            <p>
                                <span class="font-semibold">Date Recorded:</span>
                                {{ $ida->created_at ? $ida->created_at->format('M d, Y h:i A') : '—' }}
                            </p>
                            <p>
                                <span class="font-semibold">Record No.:</span>
                                IDA-{{ str_pad($ida->id, 5, '0', STR_PAD_LEFT) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 border-b border-gray-200">
                    <div class="p-5 border-b md:border-b-0 md:border-r border-gray-200">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Deal Value</p>
                        <p class="text-xl font-bold text-gray-900 mt-2">
                            ₱ {{ number_format((float) $ida->deal_value, 2) }}
                        </p>
                    </div>

                    <div class="p-5 border-b md:border-b-0 md:border-r border-gray-200">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Commission</p>
                        <p class="text-xl font-bold text-gray-900 mt-2">
                            ₱ {{ number_format((float) $totalCommission, 2) }}
                        </p>
                    </div>

                    <div class="p-5 border-b md:border-b-0 md:border-r border-gray-200">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Allocations</p>
                        <p class="text-xl font-bold text-gray-900 mt-2">
                            {{ $ida->allocations->count() }}
                        </p>
                    </div>

                    <div class="p-5">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Workflow Status</p>
                        <div class="mt-2">
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold border {{ $workflowClass }}">
                                {{ $workflowStatus }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wide mb-4">
                        IDA Details
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-0 border border-gray-200 rounded-xl overflow-hidden text-sm">
                        <div class="grid grid-cols-3 border-b md:border-r border-gray-200">
                            <div class="bg-gray-50 px-4 py-3 font-semibold text-gray-600">
                                Condeals Ref No.
                            </div>
                            <div class="col-span-2 px-4 py-3 text-gray-900 font-medium">
                                {{ $ida->condeal_ref_no ?: '—' }}
                            </div>
                        </div>

                        <div class="grid grid-cols-3 border-b border-gray-200">
                            <div class="bg-gray-50 px-4 py-3 font-semibold text-gray-600">
                                Client Name
                            </div>
                            <div class="col-span-2 px-4 py-3 text-gray-900 font-medium">
                                {{ $ida->client_name ?: '—' }}
                            </div>
                        </div>

                        <div class="grid grid-cols-3 border-b md:border-r border-gray-200">
                            <div class="bg-gray-50 px-4 py-3 font-semibold text-gray-600">
                                Business Name
                            </div>
                            <div class="col-span-2 px-4 py-3 text-gray-900 font-medium">
                                {{ $ida->business_name ?: '—' }}
                            </div>
                        </div>

                        <div class="grid grid-cols-3 border-b border-gray-200">
                            <div class="bg-gray-50 px-4 py-3 font-semibold text-gray-600">
                                Service Area
                            </div>
                            <div class="col-span-2 px-4 py-3 text-gray-900 font-medium">
                                {{ $ida->service_area ?: '—' }}
                            </div>
                        </div>

                        <div class="grid grid-cols-3 border-b md:border-r md:border-b-0 border-gray-200">
                            <div class="bg-gray-50 px-4 py-3 font-semibold text-gray-600">
                                Engagement Structure
                            </div>
                            <div class="col-span-2 px-4 py-3 text-gray-900 font-medium">
                                {{ $ida->product_engagement_structure ?: '—' }}
                            </div>
                        </div>

                        <div class="grid grid-cols-3">
                            <div class="bg-gray-50 px-4 py-3 font-semibold text-gray-600">
                                Value Amount
                            </div>
                            <div class="col-span-2 px-4 py-3 text-gray-900 font-bold">
                                ₱ {{ number_format((float) $ida->deal_value, 2) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wide mb-4">
                        Allocation Table
                    </h3>

                    <div class="overflow-x-auto border border-gray-200 rounded-xl">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-gray-600">
                                    <th class="px-4 py-3 font-semibold border-b border-gray-200">Item #</th>
                                    <th class="px-4 py-3 font-semibold border-b border-gray-200">Full Name</th>
                                    <th class="px-4 py-3 font-semibold border-b border-gray-200">Role</th>
                                    <th class="px-4 py-3 font-semibold border-b border-gray-200">Commission Category</th>
                                    <th class="px-4 py-3 font-semibold border-b border-gray-200">Commission Type</th>
                                    <th class="px-4 py-3 font-semibold border-b border-gray-200">Commission Rate</th>
                                    <th class="px-4 py-3 font-semibold border-b border-gray-200">Commission Amount</th>
                                    <th class="px-4 py-3 font-semibold border-b border-gray-200">Payout Status</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                @forelse($ida->allocations as $allocation)
                                    @php
                                        $payoutStatus = $allocation->status ?: 'Pending';
                                        $payoutClass = $statusBadgeClass($payoutStatus);
                                    @endphp

                                    <tr>
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
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium border {{ $payoutClass }}">
                                                {{ $payoutStatus }}
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
                                        <td colspan="6" class="px-4 py-3 text-right font-bold text-gray-700 border-t border-gray-200">
                                            Total Commission
                                        </td>
                                        <td class="px-4 py-3 font-bold text-gray-900 border-t border-gray-200">
                                            ₱ {{ number_format((float) $totalCommission, 2) }}
                                        </td>
                                        <td class="border-t border-gray-200"></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>

                <div class="p-6">
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wide mb-4">
                        Prepared By / Workflow Details
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                        <div class="border border-gray-200 rounded-xl p-4 min-h-[110px]">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Prepared By</p>
                            <p class="text-base font-semibold text-gray-900 mt-4">
                                {{ optional($preparedBy)->name ?? '—' }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ $ida->created_at ? $ida->created_at->format('M d, Y h:i A') : '—' }}
                            </p>
                        </div>

                        <div class="border border-gray-200 rounded-xl p-4 min-h-[110px]">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Submitted By</p>
                            <p class="text-base font-semibold text-gray-900 mt-4">
                                {{ optional($submittedBy)->name ?? '—' }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ $submittedAt ? $submittedAt->format('M d, Y h:i A') : '—' }}
                            </p>
                        </div>

                        <div class="border border-gray-200 rounded-xl p-4 min-h-[110px]">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Reviewed By</p>
                            <p class="text-base font-semibold text-gray-900 mt-4">
                                {{ optional($reviewedBy)->name ?? '—' }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ $reviewedAt ? $reviewedAt->format('M d, Y h:i A') : '—' }}
                            </p>
                        </div>

                        <div class="border border-gray-200 rounded-xl p-4 min-h-[110px]">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Record Status</p>
                            <div class="mt-4">
                                <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold border {{ $workflowClass }}">
                                    {{ $workflowStatus }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                Last updated:
                                {{ $ida->updated_at ? $ida->updated_at->format('M d, Y h:i A') : '—' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- PRINTABLE SHEET VIEW --}}
        <div x-show="viewMode === 'printable'" x-cloak>
            <div class="print-hidden bg-blue-50 border border-blue-100 rounded-2xl p-4 text-sm text-blue-800">
                This is the printable sheet view. Click <strong>Print / Save PDF</strong>, then choose <strong>Save as PDF</strong>.
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-6 ida-print-card">
                <div class="ida-print-sheet">

                    {{-- MAIN TITLE --}}
                    <div style="text-align:center; margin: 8px 0 42px 0;">
                        <h2 style="font-size: 24px; font-weight: 700; line-height: 1.08; margin: 0;">
                            Incentive Distribution &amp; Allocation (IDA)<br>
                            Sheet
                        </h2>

                        <p style="font-size: 12px; margin-top: 8px;">
                            DA-F-001-V1.0-{{ $ida->created_at ? $ida->created_at->format('m.d.y') : now()->format('m.d.y') }}
                        </p>
                    </div>

                    {{-- DETAILS AREA --}}
                    <table class="sheet-table sheet-no-border mb-1">
                        <tr>
                            <td style="width: 14%;" class="sheet-label">CLIENT NAME:</td>
                            <td style="width: 26%;" class="sheet-value">{{ $ida->client_name ?: '—' }}</td>
                            <td style="width: 14%;"></td>
                            <td style="width: 16%;" class="sheet-label">BUSINESS NAME:</td>
                            <td style="width: 30%;" class="sheet-value">{{ $ida->business_name ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="sheet-label">SERVICE AREA:</td>
                            <td class="sheet-value">{{ $ida->service_area ?: '—' }}</td>
                            <td></td>
                            <td class="sheet-label">CONDEAL REF NO.:</td>
                            <td class="sheet-value">{{ $ida->condeal_ref_no ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="sheet-value" style="padding-top: 6px;">{{ $ida->service_area ?: 'Services' }}</td>
                            <td></td>
                            <td></td>
                            <td class="sheet-label">Product Engagement Structure</td>
                            <td class="sheet-value">{{ $ida->product_engagement_structure ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="sheet-label" style="padding-top: 12px;">Value Amount</td>
                            <td class="sheet-value" style="padding-top: 12px;">₱ {{ number_format((float) $ida->deal_value, 2) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </table>

                    {{-- ALLOCATION TABLE --}}
                    <table class="sheet-table mb-1">
                        <colgroup>
                            <col style="width: 12%;">
                            <col style="width: 14%;">
                            <col style="width: 14%;">
                            <col style="width: 14%;">
                            <col style="width: 14%;">
                            <col style="width: 14%;">
                            <col style="width: 14%;">
                            <col style="width: 14%;">
                        </colgroup>

                        <thead>
                            <tr>
                                <th>Item #</th>
                                <th>Full Name</th>
                                <th>Role</th>
                                <th>Commission<br>Category</th>
                                <th>Commission<br>Type</th>
                                <th>Commission<br>Rate</th>
                                <th>Commission<br>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($ida->allocations as $allocation)
                                <tr>
                                    <td style="text-align:right;">{{ $loop->iteration }}</td>
                                    <td>{{ optional($allocation->earner)->full_name ?? '—' }}</td>
                                    <td>{{ $allocation->role ?: '—' }}</td>
                                    <td>{{ $allocation->commission_category ?: '—' }}</td>
                                    <td>{{ $allocation->commission_type ?: '—' }}</td>
                                    <td style="text-align:center;">
                                        @if($allocation->commission_type === 'Percentage')
                                            {{ number_format((float) $allocation->commission_rate, 2) }}%
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td style="text-align:right;">₱ {{ number_format((float) $allocation->commission_amount, 2) }}</td>
                                    <td style="text-align:center;">{{ $allocation->status ?: 'Pending' }}</td>
                                </tr>
                            @endforeach

                            @for($i = 0; $i < $rowsNeeded; $i++)
                                <tr>
                                    <td style="text-align:right;">{{ $ida->allocations->count() + $i + 1 }}</td>
                                    <td>&nbsp;</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            @endfor
                        </tbody>

                        <tfoot>
                            <tr>
                                <td colspan="5" style="border-left: none; border-bottom: none;"></td>
                                <td style="text-align:center; font-weight:700;">Total</td>
                                <td style="text-align:right; font-weight:700;">₱ {{ number_format((float) $totalCommission, 2) }}</td>
                                <td style="border-right: none; border-bottom: none;"></td>
                            </tr>
                        </tfoot>
                    </table>

                    {{-- SIGNATURE / APPROVAL SECTION --}}
                    <table class="sheet-table sheet-no-border" style="margin-top: 14px;">
                        <colgroup>
                            <col style="width: 16%;">
                            <col style="width: 30%;">
                            <col style="width: 10%;">
                            <col style="width: 16%;">
                            <col style="width: 28%;">
                        </colgroup>

                        <tr>
                            <td class="sheet-label">Prepared By:</td>
                            <td></td>
                            <td></td>
                            <td class="sheet-label">Reviewed By:</td>
                            <td></td>
                        </tr>

                        <tr>
                            <td class="sheet-label">Name:</td>
                            <td class="signature-line">{{ optional($preparedBy)->name ?? '—' }}</td>
                            <td></td>
                            <td class="sheet-label">Name:</td>
                            <td class="signature-line">{{ optional($reviewedBy)->name ?? '—' }}</td>
                        </tr>

                        <tr>
                            <td class="sheet-label">Date:</td>
                            <td class="signature-line">{{ $ida->created_at ? $ida->created_at->format('M d, Y') : '—' }}</td>
                            <td></td>
                            <td class="sheet-label">Date:</td>
                            <td class="signature-line">{{ $reviewedAt ? $reviewedAt->format('M d, Y') : '—' }}</td>
                        </tr>

                        <tr>
                            <td class="sheet-label">Lead Consultant:</td>
                            <td class="signature-line">—</td>
                            <td></td>
                            <td class="sheet-label">Sales &amp; Marketing:</td>
                            <td class="signature-line">{{ optional($salesMarketingBy)->name ?? '—' }}</td>
                        </tr>

                        <tr>
                            <td class="sheet-label">Finance:</td>
                            <td class="signature-line">—</td>
                            <td></td>
                            <td class="sheet-label">Lead Associate Assigned:</td>
                            <td class="signature-line">—</td>
                        </tr>

                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="sheet-label">President:</td>
                            <td class="signature-line">—</td>
                        </tr>

                        <tr>
                            <td colspan="3"></td>
                            <td class="sheet-label">Date Recorded:</td>
                            <td class="signature-line">{{ $ida->created_at ? $ida->created_at->format('M d, Y h:i A') : '—' }}</td>
                        </tr>

                        <tr>
                            <td colspan="3"></td>
                            <td class="sheet-label">Date Signed:</td>
                            <td class="signature-line">{{ $dateSigned ? $dateSigned->format('M d, Y h:i A') : '—' }}</td>
                        </tr>

                        <tr>
                            <td colspan="5" style="height: 22px;"></td>
                        </tr>

                        <tr>
                            <td colspan="2"></td>
                            <td colspan="2" style="text-align:center;" class="signature-line"></td>
                            <td></td>
                        </tr>

                        <tr>
                            <td colspan="2"></td>
                            <td colspan="2" style="text-align:center; font-weight:700;">
                                Record Custodian (Name and Signature)
                            </td>
                            <td></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <p class="print-hidden text-xs text-gray-400 text-center pb-4">
            Use <strong>Summary View</strong> for system review, or <strong>Printable Sheet</strong> for PDF/printing.
        </p>
    </div>
</div>
@endsection
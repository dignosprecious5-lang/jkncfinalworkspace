@extends('layouts.app')
@section('title', 'TownHall Acknowledgment Report')

@section('content')
<div class="w-full min-h-screen bg-slate-50 px-6 py-5">
    <div class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="relative px-6 py-6">
            <div class="absolute inset-0 bg-gradient-to-r from-green-50 via-white to-blue-50"></div>

            <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-green-100 bg-green-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-green-700">
                        <i class="fas fa-clipboard-check text-[10px]"></i>
                        Management Report
                    </div>

                    <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Read & Acknowledgment Tracking</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Monitor recipient views and acknowledgments for posted TownHall communications.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.townhall.audit-trail') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        <i class="fas fa-history text-xs"></i>
                        Audit Trail
                    </a>

                    <a href="{{ route('admin.dashboard') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                        <i class="fas fa-arrow-left text-xs"></i>
                        Back to Admin Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-5 shadow-sm">
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Total Recipients</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $summary['total'] ?? 0 }}</p>
        </div>

        <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-5">
            <p class="text-xs font-semibold tracking-wide text-blue-600 uppercase">Viewed</p>
            <p class="mt-2 text-2xl font-bold text-blue-700">{{ $summary['viewed'] ?? 0 }}</p>
        </div>

        <div class="rounded-2xl border border-green-100 bg-green-50 px-4 py-5">
            <p class="text-xs font-semibold tracking-wide text-green-600 uppercase">Acknowledged</p>
            <p class="mt-2 text-2xl font-bold text-green-700">{{ $summary['acknowledged'] ?? 0 }}</p>
        </div>

        <div class="rounded-2xl border border-yellow-100 bg-yellow-50 px-4 py-5">
            <p class="text-xs font-semibold tracking-wide text-yellow-600 uppercase">Not Viewed</p>
            <p class="mt-2 text-2xl font-bold text-yellow-700">{{ $summary['not_viewed'] ?? 0 }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.townhall.acknowledgement-report') }}" class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
            <div class="lg:col-span-3">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Communication</label>
                <select name="communication_id" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                    <option value="">All Communications</option>
                    @foreach($communicationOptions as $communication)
                        <option value="{{ $communication->id }}" {{ (string) request('communication_id') === (string) $communication->id ? 'selected' : '' }}>
                            {{ $communication->ref_no }} — {{ $communication->subject ?: 'No Subject' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-3">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Search Communication</label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Ref no, subject, requestor..."
                       class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
            </div>

            <div class="lg:col-span-3">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Recipient</label>
                <input type="text"
                       name="recipient"
                       value="{{ request('recipient') }}"
                       placeholder="Recipient name or email..."
                       class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
            </div>

            <div class="lg:col-span-2">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Status</label>
                <select name="tracking_status" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                    <option value="">All Status</option>
                    @foreach(['Not Viewed', 'Viewed', 'Acknowledged'] as $status)
                        <option value="{{ $status }}" {{ request('tracking_status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-1 flex items-end">
                <button type="submit"
                        class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                    Filter
                </button>
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <a href="{{ route('admin.townhall.acknowledgement-report') }}"
               class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Reset
            </a>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Recipient Tracking Records</h2>
            <p class="mt-1 text-sm text-slate-500">Shows intended recipients, view date/time, and acknowledgment date/time.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1250px] w-full border-collapse text-sm text-slate-700">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Ref No.</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Recipient Name</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Date Viewed</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Time Viewed</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Date Acknowledged</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Time Acknowledged</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Open</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($reportRows as $row)
                        @php
                            $statusClass = match($row->status) {
                                'Acknowledged' => 'bg-green-50 text-green-700 ring-green-100',
                                'Viewed' => 'bg-blue-50 text-blue-700 ring-blue-100',
                                default => 'bg-yellow-50 text-yellow-700 ring-yellow-100',
                            };
                        @endphp

                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs font-semibold">{{ $row->communication->ref_no }}</td>
                            <td class="px-4 py-3 max-w-[260px]">
                                <div class="truncate">{{ $row->communication->subject ?: 'No Subject' }}</div>
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $row->recipient_name }}</td>
                            <td class="px-4 py-3">{{ $row->recipient_email ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $row->viewed_at ? \Carbon\Carbon::parse($row->viewed_at)->format('M d, Y') : '-' }}</td>
                            <td class="px-4 py-3">{{ $row->viewed_at ? \Carbon\Carbon::parse($row->viewed_at)->format('h:i A') : '-' }}</td>
                            <td class="px-4 py-3">{{ $row->acknowledged_at ? \Carbon\Carbon::parse($row->acknowledged_at)->format('M d, Y') : '-' }}</td>
                            <td class="px-4 py-3">{{ $row->acknowledged_at ? \Carbon\Carbon::parse($row->acknowledged_at)->format('h:i A') : '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $statusClass }}">
                                    {{ $row->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('townhall.show', $row->communication->id) }}"
                                   class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-slate-500">
                                No acknowledgment tracking records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($reportRows, 'links'))
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $reportRows->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'TownHall Audit Trail Reporting')

@section('content')
<div class="w-full min-h-screen bg-slate-50 px-6 py-5">
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="relative px-6 py-6">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-50 via-white to-indigo-50"></div>

            <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-700">
                        <i class="fas fa-chart-line text-[10px]"></i>
                        TownHall Reporting
                    </div>

                    <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Audit Trail History</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        View all approval, rejection, revision, posting, archive, and resubmission actions.
                    </p>
                </div>

                <a href="{{ route('admin.dashboard') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    <i class="fas fa-arrow-left text-xs"></i>
                    Back to Admin Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 mb-5">
        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-5 shadow-sm">
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Total Actions</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $summary['total'] ?? 0 }}</p>
        </div>

        <div class="rounded-2xl border border-green-100 bg-green-50 px-4 py-5">
            <p class="text-xs font-semibold tracking-wide text-green-600 uppercase">Approved</p>
            <p class="mt-2 text-2xl font-bold text-green-700">{{ $summary['approved'] ?? 0 }}</p>
        </div>

        <div class="rounded-2xl border border-red-100 bg-red-50 px-4 py-5">
            <p class="text-xs font-semibold tracking-wide text-red-600 uppercase">Rejected</p>
            <p class="mt-2 text-2xl font-bold text-red-700">{{ $summary['rejected'] ?? 0 }}</p>
        </div>

        <div class="rounded-2xl border border-yellow-100 bg-yellow-50 px-4 py-5">
            <p class="text-xs font-semibold tracking-wide text-yellow-600 uppercase">Revision</p>
            <p class="mt-2 text-2xl font-bold text-yellow-700">{{ $summary['revision'] ?? 0 }}</p>
        </div>

        <div class="rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-5">
            <p class="text-xs font-semibold tracking-wide text-indigo-600 uppercase">Posted</p>
            <p class="mt-2 text-2xl font-bold text-indigo-700">{{ $summary['posted'] ?? 0 }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.townhall.audit-trail') }}" class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
            <div class="lg:col-span-4">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Search</label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Ref no, subject, requestor, approver, remarks..."
                       class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
            </div>

            <div class="lg:col-span-2">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Action</label>
                <select name="action" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                    <option value="">All Actions</option>
                    @foreach($actionOptions as $option)
                        <option value="{{ $option }}" {{ request('action') === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Level</label>
                <select name="approval_level" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                    <option value="">All Levels</option>
                    @foreach($levelOptions as $option)
                        <option value="{{ $option }}" {{ request('approval_level') === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Status</label>
                <select name="approval_status" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                    <option value="">All Status</option>
                    @foreach($statusOptions as $option)
                        <option value="{{ $option }}" {{ request('approval_status') === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-1">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">From</label>
                <input type="date"
                       name="date_from"
                       value="{{ request('date_from') }}"
                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
            </div>

            <div class="lg:col-span-1">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">To</label>
                <input type="date"
                       name="date_to"
                       value="{{ request('date_to') }}"
                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
            </div>
        </div>

        <div class="mt-4 flex justify-end gap-2">
            <a href="{{ route('admin.townhall.audit-trail') }}"
               class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Reset
            </a>

            <button type="submit"
                    class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                Apply Filter
            </button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Audit Records</h2>
            <p class="mt-1 text-sm text-slate-500">Showing latest audit actions first.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1500px] w-full border-collapse text-sm text-slate-700">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Date / Time</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Ref No.</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Requestor</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Approval Level</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Approver Name</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Position</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Department</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Remarks</th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Open</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($audits as $audit)
                        @php
                            $actionClass = match($audit->action) {
                                'Approved', 'Approved and Posted' => 'bg-green-50 text-green-700 ring-green-100',
                                'Rejected' => 'bg-red-50 text-red-700 ring-red-100',
                                'Returned for Revision', 'Resubmitted' => 'bg-yellow-50 text-yellow-700 ring-yellow-100',
                                'Archived', 'Unarchived' => 'bg-slate-100 text-slate-700 ring-slate-200',
                                default => 'bg-blue-50 text-blue-700 ring-blue-100',
                            };
                        @endphp

                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ $audit->acted_at ? $audit->acted_at->format('M d, Y h:i A') : optional($audit->created_at)->format('M d, Y h:i A') }}
                            </td>

                            <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-800">
                                {{ $audit->communication_ref_no ?? '-' }}
                            </td>

                            <td class="px-4 py-3 max-w-[260px]">
                                <div class="truncate font-medium text-slate-900">{{ $audit->communication_subject ?? '-' }}</div>
                            </td>

                            <td class="px-4 py-3">{{ $audit->requestor_name ?? '-' }}</td>

                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $actionClass }}">
                                    {{ $audit->action }}
                                </span>
                            </td>

                            <td class="px-4 py-3">{{ $audit->approval_level ?? '-' }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $audit->approver_name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $audit->approver_position ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $audit->approver_department ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $audit->approval_status ?? '-' }}</td>
                            <td class="px-4 py-3 max-w-[340px]">
                                <div class="truncate" title="{{ $audit->remarks }}">{{ $audit->remarks ?? '-' }}</div>
                            </td>

                            <td class="px-4 py-3 text-center">
                                @if($audit->communication)
                                    <a href="{{ route('townhall.show', $audit->communication->id) }}"
                                       class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                        View
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-6 py-12 text-center text-slate-500">
                                No audit trail records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($audits, 'links'))
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $audits->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Account Audit Trail')

@section('content')
<div class="w-full min-h-screen bg-slate-50 px-6 py-5">
    <div class="space-y-5">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="relative px-6 py-6">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-50 via-white to-indigo-50"></div>

                <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-700">
                            <i class="fas fa-shield-halved text-[10px]"></i>
                            Super Admin
                        </div>

                        <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Account Audit Trail</h1>
                        <p class="mt-1 text-sm text-slate-500">
                            View user account activity, password assistance requests, password resets, and account control actions.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-white/80 px-4 py-3 shadow-sm">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Total Logs</p>
                            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $logs->total() }}</p>
                        </div>

                        <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-blue-500">Showing</p>
                            <p class="mt-1 text-2xl font-bold text-blue-700">{{ $logs->count() }}</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white/80 px-4 py-3 shadow-sm col-span-2 sm:col-span-1">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Range</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">
                                {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                <i class="fas fa-circle-check mr-2"></i>{{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                <i class="fas fa-circle-exclamation mr-2"></i>{{ session('error') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <form method="GET" class="border-b border-slate-200 px-5 py-4">
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_230px_170px_170px_auto] lg:items-end">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-1">Search</label>
                        <div class="relative">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                            <input
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Search user, email, action, IP, or remarks..."
                                class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-11 pr-4 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-1">Action</label>
                        <select name="action" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                            <option value="">All Actions</option>
                            @foreach($actions as $action)
                                <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-1">From</label>
                        <input
                            type="date"
                            name="date_from"
                            value="{{ request('date_from') }}"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-1">To</label>
                        <input
                            type="date"
                            name="date_to"
                            value="{{ request('date_to') }}"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <div class="flex gap-2">
                        <a
                            href="{{ route('admin.account-audit-trail') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Clear
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                        >
                            Apply
                        </button>
                    </div>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-[1100px] w-full border-collapse text-sm text-slate-700">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50">
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Date & Time</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Action</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">User Affected</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Performed By</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">IP Address</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Remarks</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($logs as $log)
                            @php
                                $badgeColor = match($log->action_performed) {
                                    'User Created' => 'bg-green-50 text-green-700 ring-green-100',
                                    'User Edited' => 'bg-blue-50 text-blue-700 ring-blue-100',
                                    'User Disabled' => 'bg-red-50 text-red-700 ring-red-100',
                                    'User Enabled' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                                    'User Archived' => 'bg-slate-100 text-slate-700 ring-slate-200',
                                    'Password Assistance Request Submitted' => 'bg-yellow-50 text-yellow-700 ring-yellow-100',
                                    'Password Reset Email Sent' => 'bg-indigo-50 text-indigo-700 ring-indigo-100',
                                    'Password Changed' => 'bg-purple-50 text-purple-700 ring-purple-100',
                                    'First Login Password Change Completed' => 'bg-teal-50 text-teal-700 ring-teal-100',
                                    default => 'bg-slate-100 text-slate-700 ring-slate-200',
                                };
                            @endphp

                            <tr class="transition hover:bg-slate-50">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="font-semibold text-slate-900">{{ optional($log->created_at)->format('M d, Y') }}</p>
                                    <p class="text-xs text-slate-400">{{ optional($log->created_at)->format('h:i A') }}</p>
                                </td>

                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $badgeColor }}">
                                        {{ $log->action_performed }}
                                    </span>
                                </td>

                                <td class="px-5 py-4">
                                    @if($log->affectedUser)
                                        <p class="font-semibold text-slate-900">{{ $log->affectedUser->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $log->affectedUser->email }}</p>
                                        <p class="text-[11px] text-slate-400">User #{{ $log->affectedUser->id }}</p>
                                    @else
                                        <p class="font-semibold text-slate-500">Guest / N/A</p>
                                    @endif
                                </td>

                                <td class="px-5 py-4">
                                    @if($log->performedBy)
                                        <p class="font-semibold text-slate-900">{{ $log->performedBy->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $log->performedBy->email }}</p>
                                        <p class="text-[11px] text-slate-400">User #{{ $log->performedBy->id }}</p>
                                    @else
                                        <p class="font-semibold text-slate-500">Guest / System</p>
                                    @endif
                                </td>

                                <td class="px-5 py-4 font-mono text-xs text-slate-600">
                                    {{ $log->ip_address ?: '—' }}
                                </td>

                                <td class="px-5 py-4 max-w-[340px]">
                                    <p class="break-words text-slate-600">{{ $log->remarks ?: '—' }}</p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-14 text-center">
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                        <i class="fas fa-clipboard-list text-lg"></i>
                                    </div>
                                    <h3 class="mt-4 text-base font-semibold text-slate-800">No audit logs found</h3>
                                    <p class="mt-1 text-sm text-slate-500">Account activities will appear here after actions are recorded.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-5 py-4">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

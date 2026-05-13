@extends('layouts.app')

@section('title', 'Deal Proposal Templates')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-5 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Deal Proposal Templates</h1>
            <p class="mt-1 text-sm text-gray-500">Review submitted global proposal template changes before they affect new deals.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Admin Dashboard</a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Template</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Submitted</th>
                    <th class="px-4 py-3">Reviewed</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($templates as $template)
                    @php($status = strtolower((string) ($template->status ?? 'approved')))
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-900">{{ $template->name }}</div>
                            @if ($template->review_note)
                                <div class="mt-1 text-xs text-gray-500">{{ $template->review_note }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $status === 'pending' ? 'bg-amber-50 text-amber-700' : ($status === 'approved' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600') }}">
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ optional($template->created_at)->format('M j, Y g:i A') }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ optional($template->reviewed_at)->format('M j, Y g:i A') ?: '-' }}</td>
                        <td class="px-4 py-3">
                            @if ($status === 'pending')
                                <div class="flex justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.deal-proposal-templates.approve', $template) }}">
                                        @csrf
                                        <button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.deal-proposal-templates.reject', $template) }}">
                                        @csrf
                                        <input type="hidden" name="review_note" value="Rejected by admin">
                                        <button class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">Reject</button>
                                    </form>
                                </div>
                            @else
                                <div class="text-right text-xs text-gray-400">No action</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">No global proposal template requests yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col">
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b shrink-0 gap-4">
            <div class="flex items-center flex-1 min-w-0">
                <h1 class="text-lg font-semibold text-gray-900">Memos</h1>
            </div>

            @if($isAdmin)
                <a href="{{ route('townhall') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded text-sm shrink-0">
                    + Add
                </a>
            @endif
        </div>

        {{-- Admin / SuperAdmin Employee Filter --}}
        @if($isAdmin)
            <div class="px-4 py-3 border-b bg-gray-50">
                <form method="GET" action="{{ route('human-capital.memos') }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            View memos received by employee
                        </label>

                        <select name="employee_id"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm w-72">
                            <option value="">All Employees / All Memos</option>

                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}"
                                    {{ (string) $selectedEmployee === (string) $employee->id ? 'selected' : '' }}>
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit"
                            class="bg-gray-900 hover:bg-gray-800 text-white px-4 py-2 rounded-md text-sm">
                        Filter
                    </button>

                    <a href="{{ route('human-capital.memos') }}"
                       class="bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm">
                        Reset
                    </a>
                </form>
            </div>
        @endif

        {{-- Memo List --}}
        <div class="flex-grow overflow-y-auto p-4">
            @if($communications->count())
                <div class="space-y-3">
                    @foreach($communications as $memo)
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="font-semibold text-gray-900 text-base">
                                            {{ $memo->subject ?? 'No Subject' }}
                                        </h2>

                                        @if($memo->priority === 'High')
                                            <span class="px-2 py-1 rounded-full bg-red-100 text-red-700 text-xs">
                                                High Priority
                                            </span>
                                        @else
                                            <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-600 text-xs">
                                                Low Priority
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-gray-500">
                                        <span>
                                            Ref No: {{ $memo->ref_no ?? 'N/A' }}
                                        </span>

                                        <span>•</span>

                                        <span>
                                            {{ $memo->recipient_label ?? 'To' }}:
                                            @if(($memo->recipient_type ?? 'all') === 'all')
                                                All Employees
                                            @else
                                                {{ $memo->recipientUser->name ?? $memo->to_for ?? 'Selected Employee' }}
                                            @endif
                                        </span>

                                        <span>•</span>

                                        <span>
                                            From: {{ $memo->from_name ?? 'TownHall' }}
                                        </span>

                                        <span>•</span>

                                        <span>
                                            {{ $memo->communication_date
                                                ? \Carbon\Carbon::parse($memo->communication_date)->format('M d, Y')
                                                : $memo->created_at->format('M d, Y') }}
                                        </span>
                                    </div>
                                </div>

                                <a href="{{ route('townhall.show', $memo->id) }}"
                                   class="px-3 py-1.5 text-xs rounded bg-blue-100 text-blue-700 hover:bg-blue-200 shrink-0">
                                    View
                                </a>
                            </div>

                            <div class="mt-3 text-sm text-gray-700 leading-relaxed line-clamp-3 memo-body-block">
                                {!! $memo->message !!}
                            </div>

                            @if($memo->attachment)
                                <div class="mt-3 text-xs text-gray-500">
                                    Attachment available
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $communications->links() }}
                </div>
            @else
                <div class="h-full flex items-center justify-center text-center">
                    <div>
                        <p class="text-gray-500 text-sm">No memos found.</p>

                        @if(!$isAdmin)
                            <p class="text-gray-400 text-xs mt-1">
                                You currently have no memorandum assigned to you.
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection

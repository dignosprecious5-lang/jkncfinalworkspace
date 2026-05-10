@extends('layouts.app')

@section('content')
@php
    $nextPunchAction = $todayAttendance->next_punch_action;
    $nextPunchLabel = $todayAttendance->next_punch_label;
    $nextPunchIcon = $todayAttendance->next_punch_icon;
    $statusStyles = [
        'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
        'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'rejected' => 'bg-rose-50 text-rose-700 border-rose-100',
    ];
    $formatDateTimeInput = fn ($value) => $value ? $value->format('Y-m-d\TH:i') : '';
@endphp

<div class="w-full px-6 py-5 space-y-5">
    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Human Capital</p>
                    <h1 class="mt-1 text-xl font-semibold text-gray-900">Attendance Records</h1>
                    <p class="mt-1 text-sm text-gray-500">Daily timekeeping, summaries, and full attendance history.</p>
                </div>

                <form method="POST" action="{{ route('human-capital.attendance.clock') }}">
                    @csrf
                    <input type="hidden" name="action" value="{{ $nextPunchAction }}">
                    <button
                        type="submit"
                        @disabled($nextPunchAction === null)
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold shadow-sm transition
                            {{ $nextPunchAction === 'clock_out'
                                ? 'bg-gray-900 text-white hover:bg-gray-800'
                                : ($nextPunchAction === null
                                    ? 'cursor-not-allowed bg-gray-100 text-gray-400'
                                    : 'bg-blue-600 text-white hover:bg-blue-700') }}">
                        <i class="fas {{ $nextPunchIcon }}"></i>
                        {{ $nextPunchLabel }}
                    </button>
                </form>
            </div>
        </div>

        <div class="grid gap-4 px-5 py-4 md:grid-cols-4">
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Today</p>
                <p class="mt-2 text-lg font-semibold text-gray-900">
                    {{ $todayAttendance->time_in ? $todayAttendance->time_in->format('h:i A') : 'Not clocked in' }}
                </p>
                <p class="mt-1 text-xs text-gray-500">
                    {{ $todayAttendance->time_out ? 'Out at '.$todayAttendance->time_out->format('h:i A') : 'Current shift status' }}
                </p>
            </div>

            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Now</p>
                <p class="mt-2 text-lg font-semibold text-gray-900">{{ $todayAttendance->current_punch_status }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $nextPunchAction ? 'Next: '.$nextPunchLabel : 'No more punches today' }}</p>
            </div>

            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Work Hours</p>
                <p class="mt-2 text-lg font-semibold text-gray-900">{{ number_format($summary['hours'], 2) }}</p>
                <p class="mt-1 text-xs text-gray-500">Computed from clock-in/out</p>
            </div>

            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pending</p>
                <p class="mt-2 text-lg font-semibold text-gray-900">{{ number_format($summary['pending']) }}</p>
                <p class="mt-1 text-xs text-gray-500">Awaiting review</p>
            </div>
        </div>

        <form method="GET" action="{{ route('human-capital.attendance') }}" class="border-t border-gray-100 px-5 py-4">
            <div class="grid gap-3 md:grid-cols-4 lg:grid-cols-5">
                @if($canManageAttendance)
                    <select name="employee_id" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                        <option value="">All employees</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) request('employee_id') === (string) $employee->id)>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <select name="period" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                    <option value="daily" @selected($period === 'daily')>Daily</option>
                    <option value="weekly" @selected($period === 'weekly')>Weekly</option>
                    <option value="monthly" @selected($period === 'monthly')>Monthly</option>
                    <option value="payroll" @selected($period === 'payroll')>Payroll Period</option>
                </select>

                <input
                    type="date"
                    name="date"
                    value="{{ $selectedDate->toDateString() }}"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm"
                >

                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                    <i class="fas fa-filter"></i>
                    Filter
                </button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1180px] text-left text-sm">
                <thead class="border-y border-gray-100 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3 text-center">Time In</th>
                        <th class="px-4 py-3 text-center">Break 1</th>
                        <th class="px-4 py-3 text-center">Lunch</th>
                        <th class="px-4 py-3 text-center">Break 2</th>
                        <th class="px-4 py-3 text-center">Time Out</th>
                        <th class="px-4 py-3 text-center">Break</th>
                        <th class="px-4 py-3 text-center">Lunch</th>
                        <th class="px-4 py-3 text-center">Hours</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        @if($canManageAttendance)
                            <th class="px-4 py-3 text-center">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($attendances as $attendance)
                        @php($updateFormId = 'attendance-update-'.$attendance->id)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                @if($canManageAttendance)
                                    <input
                                        form="{{ $updateFormId }}"
                                        type="date"
                                        name="date"
                                        value="{{ $attendance->date->toDateString() }}"
                                        class="w-36 rounded-md border border-gray-300 px-2 py-1 text-xs"
                                    >
                                @else
                                    {{ $attendance->date->format('M d, Y') }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $attendance->employee_name ?: optional($attendance->user)->name }}</td>
                            <td class="px-4 py-3 text-center text-gray-700">
                                @if($canManageAttendance)
                                    <input form="{{ $updateFormId }}" type="datetime-local" name="time_in" value="{{ $formatDateTimeInput($attendance->time_in) }}" class="w-40 rounded-md border border-gray-300 px-2 py-1 text-xs">
                                @else
                                    {{ $attendance->time_in?->format('h:i A') ?? '-' }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-500">
                                @if($canManageAttendance)
                                    <div class="space-y-1">
                                        <input form="{{ $updateFormId }}" type="datetime-local" name="break_1_start" value="{{ $formatDateTimeInput($attendance->break_1_start) }}" class="w-40 rounded-md border border-gray-300 px-2 py-1 text-xs">
                                        <input form="{{ $updateFormId }}" type="datetime-local" name="break_1_end" value="{{ $formatDateTimeInput($attendance->break_1_end) }}" class="w-40 rounded-md border border-gray-300 px-2 py-1 text-xs">
                                    </div>
                                @else
                                    {{ $attendance->break_1_start?->format('h:i A') ?? '-' }}
                                    @if($attendance->break_1_end) - {{ $attendance->break_1_end->format('h:i A') }} @endif
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-500">
                                @if($canManageAttendance)
                                    <div class="space-y-1">
                                        <input form="{{ $updateFormId }}" type="datetime-local" name="lunch_start" value="{{ $formatDateTimeInput($attendance->lunch_start) }}" class="w-40 rounded-md border border-gray-300 px-2 py-1 text-xs">
                                        <input form="{{ $updateFormId }}" type="datetime-local" name="lunch_end" value="{{ $formatDateTimeInput($attendance->lunch_end) }}" class="w-40 rounded-md border border-gray-300 px-2 py-1 text-xs">
                                    </div>
                                @else
                                    {{ $attendance->lunch_start?->format('h:i A') ?? '-' }}
                                    @if($attendance->lunch_end) - {{ $attendance->lunch_end->format('h:i A') }} @endif
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-500">
                                @if($canManageAttendance)
                                    <div class="space-y-1">
                                        <input form="{{ $updateFormId }}" type="datetime-local" name="break_2_start" value="{{ $formatDateTimeInput($attendance->break_2_start) }}" class="w-40 rounded-md border border-gray-300 px-2 py-1 text-xs">
                                        <input form="{{ $updateFormId }}" type="datetime-local" name="break_2_end" value="{{ $formatDateTimeInput($attendance->break_2_end) }}" class="w-40 rounded-md border border-gray-300 px-2 py-1 text-xs">
                                    </div>
                                @else
                                    {{ $attendance->break_2_start?->format('h:i A') ?? '-' }}
                                    @if($attendance->break_2_end) - {{ $attendance->break_2_end->format('h:i A') }} @endif
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">
                                @if($canManageAttendance)
                                    <input form="{{ $updateFormId }}" type="datetime-local" name="time_out" value="{{ $formatDateTimeInput($attendance->time_out) }}" class="w-40 rounded-md border border-gray-300 px-2 py-1 text-xs">
                                @else
                                    {{ $attendance->time_out?->format('h:i A') ?? '-' }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-blue-700">{{ $attendance->total_break_label }}</td>
                            <td class="px-4 py-3 text-center text-blue-700">{{ $attendance->total_lunch_label }}</td>
                            <td class="px-4 py-3 text-center font-semibold text-emerald-700">{{ number_format((float) $attendance->total_working_hours, 2) }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($canManageAttendance)
                                    <select form="{{ $updateFormId }}" name="status" class="rounded-md border border-gray-300 px-2 py-1 text-xs">
                                        <option value="pending" @selected($attendance->status === 'pending')>Pending</option>
                                        <option value="approved" @selected($attendance->status === 'approved')>Approved</option>
                                        <option value="rejected" @selected($attendance->status === 'rejected')>Rejected</option>
                                    </select>
                                @else
                                    <span class="inline-flex rounded-full border px-2 py-1 text-[11px] font-semibold uppercase {{ $statusStyles[$attendance->status] ?? 'bg-gray-50 text-gray-600 border-gray-100' }}">
                                        {{ $attendance->status }}
                                    </span>
                                @endif
                            </td>
                            @if($canManageAttendance)
                                <td class="px-4 py-3">
                                    <div class="flex flex-col items-stretch gap-2">
                                        <form id="{{ $updateFormId }}" method="POST" action="{{ route('human-capital.attendance.update', $attendance) }}">
                                            @csrf
                                            @method('PUT')
                                        </form>
                                        <button form="{{ $updateFormId }}" type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-800">
                                            <i class="fas fa-save"></i>
                                            Save
                                        </button>
                                        <div class="grid grid-cols-2 gap-2">
                                            <form method="POST" action="{{ route('human-capital.attendance.approve', $attendance) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="w-full rounded-md bg-emerald-600 px-2 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('human-capital.attendance.reject', $attendance) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="w-full rounded-md bg-rose-600 px-2 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">Reject</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManageAttendance ? 12 : 11 }}" class="px-4 py-12 text-center text-sm text-gray-500">
                                No attendance records found for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 px-5 py-4">
            {{ $attendances->links() }}
        </div>
    </div>
</div>
@endsection

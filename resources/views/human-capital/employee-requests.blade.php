@extends('layouts.app')

@section('content')
@php
    $canManageEmployeeRequests = $canManageEmployeeRequests ?? false;
    $employeeRequests = $employeeRequests ?? collect();
    $myEmployeeRequests = $myEmployeeRequests ?? collect();
@endphp

<div class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col">

    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">

        {{-- Header --}}
        <div class="px-4 pt-3 border-b shrink-0">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-900">Employee Requests</h1>

                <button type="button"
                    onclick="showRequestTab('overtime')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded text-sm">
                    + Add
                </button>
            </div>

            {{-- Top Tabs --}}
            <div class="flex items-center gap-6 mt-3 overflow-x-auto text-sm">
                <button type="button" onclick="showRequestTab('overtime')" id="tab-overtime"
                    class="request-tab py-3 border-b-2 border-blue-600 text-blue-600 font-medium whitespace-nowrap">
                    Overtime Request
                </button>

                <button type="button" onclick="showRequestTab('leave')" id="tab-leave"
                    class="request-tab py-3 border-b-2 border-transparent text-gray-600 hover:text-blue-600 whitespace-nowrap">
                    Leave Application
                </button>

                <button type="button" onclick="showRequestTab('attendance')" id="tab-attendance"
                    class="request-tab py-3 border-b-2 border-transparent text-gray-600 hover:text-blue-600 whitespace-nowrap">
                    Attendance Correction
                </button>

                <button type="button" onclick="showRequestTab('undertime')" id="tab-undertime"
                    class="request-tab py-3 border-b-2 border-transparent text-gray-600 hover:text-blue-600 whitespace-nowrap">
                    Undertime / Absence
                </button>

                <button type="button" onclick="showRequestTab('coe')" id="tab-coe"
                    class="request-tab py-3 border-b-2 border-transparent text-gray-600 hover:text-blue-600 whitespace-nowrap">
                    COE Request
                </button>

                <button type="button" onclick="showRequestTab('my-requests')" id="tab-my-requests"
                    class="request-tab py-3 border-b-2 border-transparent text-gray-600 hover:text-blue-600 whitespace-nowrap">
                    My Requests
                </button>

                @if($canManageEmployeeRequests)
                    <button type="button" onclick="showRequestTab('list')" id="tab-list"
                        class="request-tab py-3 border-b-2 border-transparent text-gray-600 hover:text-blue-600 whitespace-nowrap">
                        List of Requests
                    </button>
                @endif
            </div>
        </div>

        {{-- Content --}}
        <div class="p-4 flex-grow overflow-auto bg-gray-50">

            @if(session('success'))
                <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Overtime Request --}}
            <div id="content-overtime" class="request-content bg-white border rounded-lg p-4">
                <h2 class="text-base font-semibold text-gray-900 mb-1">Overtime Request Form</h2>
                <p class="text-sm text-gray-500 mb-4">
                    Request form for employees rendering work beyond regular working hours.
                </p>

                <form method="POST" action="{{ route('human-capital.employee-requests.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    @csrf

                    <input type="hidden" name="request_type" value="Overtime Request">

                    <div>
                        <label class="block mb-1 text-gray-700">Employee Name</label>
                        <input type="text"
                            name="employee_name"
                            value="{{ auth()->user()->name ?? '' }}"
                            readonly
                            class="w-full border-gray-300 rounded-md text-sm bg-gray-100 text-gray-700 cursor-not-allowed">
                    </div>

                    <div>
                        <label class="block mb-1 text-gray-700">Department</label>
                        <input type="text"
                            name="department"
                            class="w-full border-gray-300 rounded-md text-sm"
                            placeholder="Enter department">
                    </div>

                    <div>
                        <label class="block mb-1 text-gray-700">Overtime Date</label>
                        <input type="date"
                            name="overtime_date"
                            class="w-full border-gray-300 rounded-md text-sm">
                    </div>

                    <div>
                        <label class="block mb-1 text-gray-700">Total Hours</label>
                        <input type="number"
                            name="total_hours"
                            step="0.01"
                            class="w-full border-gray-300 rounded-md text-sm"
                            placeholder="0.00">
                    </div>

                    <div>
                        <label class="block mb-1 text-gray-700">Start Time</label>
                        <input type="time"
                            name="start_time"
                            class="w-full border-gray-300 rounded-md text-sm">
                    </div>

                    <div>
                        <label class="block mb-1 text-gray-700">End Time</label>
                        <input type="time"
                            name="end_time"
                            class="w-full border-gray-300 rounded-md text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-1 text-gray-700">Reason / Purpose</label>
                        <textarea rows="3"
                            name="reason"
                            class="w-full border-gray-300 rounded-md text-sm"
                            placeholder="Enter reason for overtime"></textarea>
                    </div>

                    <div class="md:col-span-2 flex justify-end gap-2 pt-2">
                        <button type="reset" class="px-4 py-2 border rounded text-sm text-gray-700 hover:bg-gray-50">
                            Clear
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>

            {{-- Leave Application --}}
            <div id="content-leave" class="request-content hidden bg-white border rounded-lg p-4">
                <h2 class="text-base font-semibold text-gray-900 mb-1">Leave Application Request Form</h2>
                <p class="text-sm text-gray-500 mb-4">
                    Request form for filing vacation, sick, emergency, or other types of leave.
                </p>

                <form method="POST" action="{{ route('human-capital.employee-requests.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    @csrf

                    <input type="hidden" name="request_type" value="Leave Application Request">

                    <div>
                        <label class="block mb-1 text-gray-700">Employee Name</label>
                        <input type="text"
                            name="employee_name"
                            value="{{ auth()->user()->name ?? '' }}"
                            readonly
                            class="w-full border-gray-300 rounded-md text-sm bg-gray-100 text-gray-700 cursor-not-allowed">
                    </div>

                    <div>
                        <label class="block mb-1 text-gray-700">Leave Type</label>
                        <select name="leave_type" class="w-full border-gray-300 rounded-md text-sm">
                            <option value="">Select leave type</option>
                            <option value="Sick Leave">Sick Leave</option>
                            <option value="Vacation Leave">Vacation Leave</option>
                            <option value="Emergency Leave">Emergency Leave</option>
                            <option value="Maternity Leave">Maternity Leave</option>
                            <option value="Paternity Leave">Paternity Leave</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1 text-gray-700">Start Date</label>
                        <input type="date"
                            name="start_date"
                            class="w-full border-gray-300 rounded-md text-sm">
                    </div>

                    <div>
                        <label class="block mb-1 text-gray-700">End Date</label>
                        <input type="date"
                            name="end_date"
                            class="w-full border-gray-300 rounded-md text-sm">
                    </div>

                    <div>
                        <label class="block mb-1 text-gray-700">Number of Days</label>
                        <input type="number"
                            name="number_of_days"
                            step="0.5"
                            class="w-full border-gray-300 rounded-md text-sm"
                            placeholder="0">
                    </div>

                    <div>
                        <label class="block mb-1 text-gray-700">With Pay?</label>
                        <select name="with_pay" class="w-full border-gray-300 rounded-md text-sm">
                            <option value="">Select option</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-1 text-gray-700">Reason</label>
                        <textarea rows="3"
                            name="reason"
                            class="w-full border-gray-300 rounded-md text-sm"
                            placeholder="Enter reason for leave"></textarea>
                    </div>

                    <div class="md:col-span-2 flex justify-end gap-2 pt-2">
                        <button type="reset" class="px-4 py-2 border rounded text-sm text-gray-700 hover:bg-gray-50">
                            Clear
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>

            {{-- Attendance Correction --}}
            <div id="content-attendance" class="request-content hidden bg-white border rounded-lg p-4">
                <h2 class="text-base font-semibold text-gray-900 mb-1">Attendance Correction Request Form</h2>
                <p class="text-sm text-gray-500 mb-4">
                    Request form for correcting incorrect, missing, or incomplete attendance records.
                </p>

                <form method="POST" action="{{ route('human-capital.employee-requests.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    @csrf

                    <input type="hidden" name="request_type" value="Attendance Correction Request">

                    <div>
                        <label class="block mb-1 text-gray-700">Employee Name</label>
                        <input type="text"
                            name="employee_name"
                            value="{{ auth()->user()->name ?? '' }}"
                            readonly
                            class="w-full border-gray-300 rounded-md text-sm bg-gray-100 text-gray-700 cursor-not-allowed">
                    </div>

                    <div>
                        <label class="block mb-1 text-gray-700">Attendance Date</label>
                        <input type="date"
                            name="attendance_date"
                            class="w-full border-gray-300 rounded-md text-sm">
                    </div>

                    <div>
                        <label class="block mb-1 text-gray-700">Correction Type</label>
                        <select name="correction_type" class="w-full border-gray-300 rounded-md text-sm">
                            <option value="">Select correction type</option>
                            <option value="Time In">Time In</option>
                            <option value="Time Out">Time Out</option>
                            <option value="Break Time">Break Time</option>
                            <option value="Lunch Time">Lunch Time</option>
                            <option value="Whole Attendance Record">Whole Attendance Record</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1 text-gray-700">Correct Time</label>
                        <input type="time"
                            name="correct_time"
                            class="w-full border-gray-300 rounded-md text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-1 text-gray-700">Reason for Correction</label>
                        <textarea rows="3"
                            name="reason"
                            class="w-full border-gray-300 rounded-md text-sm"
                            placeholder="Enter reason for correction"></textarea>
                    </div>

                    <div class="md:col-span-2 flex justify-end gap-2 pt-2">
                        <button type="reset" class="px-4 py-2 border rounded text-sm text-gray-700 hover:bg-gray-50">
                            Clear
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>

            {{-- Undertime / Absence --}}
            <div id="content-undertime" class="request-content hidden bg-white border rounded-lg p-4">
                <h2 class="text-base font-semibold text-gray-900 mb-1">Undertime / Absence Request Form</h2>
                <p class="text-sm text-gray-500 mb-4">
                    Request form for filing undertime, absence, or work schedule exceptions.
                </p>

                <form method="POST" action="{{ route('human-capital.employee-requests.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    @csrf

                    <input type="hidden" name="request_type" value="Undertime / Absence Request">

                    <div>
                        <label class="block mb-1 text-gray-700">Employee Name</label>
                        <input type="text"
                            name="employee_name"
                            value="{{ auth()->user()->name ?? '' }}"
                            readonly
                            class="w-full border-gray-300 rounded-md text-sm bg-gray-100 text-gray-700 cursor-not-allowed">
                    </div>

                    <div>
                        <label class="block mb-1 text-gray-700">Request Type</label>
                        <select name="absence_type" class="w-full border-gray-300 rounded-md text-sm">
                            <option value="">Select request type</option>
                            <option value="Undertime">Undertime</option>
                            <option value="Absence">Absence</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1 text-gray-700">Date</label>
                        <input type="date"
                            name="request_date"
                            class="w-full border-gray-300 rounded-md text-sm">
                    </div>

                    <div>
                        <label class="block mb-1 text-gray-700">Time Affected</label>
                        <input type="time"
                            name="time_affected"
                            class="w-full border-gray-300 rounded-md text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-1 text-gray-700">Reason</label>
                        <textarea rows="3"
                            name="reason"
                            class="w-full border-gray-300 rounded-md text-sm"
                            placeholder="Enter reason"></textarea>
                    </div>

                    <div class="md:col-span-2 flex justify-end gap-2 pt-2">
                        <button type="reset" class="px-4 py-2 border rounded text-sm text-gray-700 hover:bg-gray-50">
                            Clear
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>

            {{-- COE Request --}}
            <div id="content-coe" class="request-content hidden bg-white border rounded-lg p-4">
                <h2 class="text-base font-semibold text-gray-900 mb-1">COE Request Form</h2>
                <p class="text-sm text-gray-500 mb-4">
                    Request form for Certificate of Employment issuance and processing.
                </p>

                <form method="POST" action="{{ route('human-capital.employee-requests.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    @csrf

                    <input type="hidden" name="request_type" value="COE Request Form">

                    <div>
                        <label class="block mb-1 text-gray-700">Employee Name</label>
                        <input type="text"
                            name="employee_name"
                            value="{{ auth()->user()->name ?? '' }}"
                            readonly
                            class="w-full border-gray-300 rounded-md text-sm bg-gray-100 text-gray-700 cursor-not-allowed">
                    </div>

                    <div>
                        <label class="block mb-1 text-gray-700">Purpose</label>
                        <select name="purpose" class="w-full border-gray-300 rounded-md text-sm">
                            <option value="">Select purpose</option>
                            <option value="Employment Requirement">Employment Requirement</option>
                            <option value="Loan Application">Loan Application</option>
                            <option value="Visa / Travel">Visa / Travel</option>
                            <option value="School Requirement">School Requirement</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1 text-gray-700">Date Needed</label>
                        <input type="date"
                            name="date_needed"
                            class="w-full border-gray-300 rounded-md text-sm">
                    </div>

                    <div>
                        <label class="block mb-1 text-gray-700">Number of Copies</label>
                        <input type="number"
                            name="number_of_copies"
                            min="1"
                            class="w-full border-gray-300 rounded-md text-sm"
                            placeholder="1">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-1 text-gray-700">Remarks</label>
                        <textarea rows="3"
                            name="remarks"
                            class="w-full border-gray-300 rounded-md text-sm"
                            placeholder="Enter remarks"></textarea>
                    </div>

                    <div class="md:col-span-2 flex justify-end gap-2 pt-2">
                        <button type="reset" class="px-4 py-2 border rounded text-sm text-gray-700 hover:bg-gray-50">
                            Clear
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>

            {{-- My Requests --}}
            <div id="content-my-requests" class="request-content hidden bg-white border rounded-lg p-4">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">My Requests</h2>
                        <p class="text-sm text-gray-500">
                            View your submitted requests and revise requests sent back by admin.
                        </p>
                    </div>
                </div>

                <div class="border rounded-md overflow-auto">
                    <table class="w-full text-sm table-fixed border-collapse">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="w-32 p-3 text-left font-medium">Request No.</th>
                                <th class="p-3 text-left font-medium">Request Type</th>
                                <th class="w-36 p-3 text-left font-medium">Date Filed</th>
                                <th class="w-36 p-3 text-left font-medium">Status</th>
                                <th class="p-3 text-left font-medium">Admin Note</th>
                                <th class="w-48 p-3 text-left font-medium">Action</th>
                            </tr>
                        </thead>

                        <tbody class="bg-white text-gray-700">
                            @forelse($myEmployeeRequests as $request)
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="p-3">
                                        REQ-{{ str_pad($request->id, 4, '0', STR_PAD_LEFT) }}
                                    </td>

                                    <td class="p-3">
                                        {{ $request->request_type }}
                                    </td>

                                    <td class="p-3">
                                        {{ $request->created_at->format('Y-m-d') }}
                                    </td>

                                    <td class="p-3">
                                        @if($request->status === 'Approved')
                                            <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">Approved</span>
                                        @elseif($request->status === 'Declined')
                                            <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">Declined</span>
                                        @elseif($request->status === 'For Revision')
                                            <span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-700">For Revision</span>
                                        @else
                                            <span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-700">Pending</span>
                                        @endif
                                    </td>

                                    <td class="p-3">
                                        {{ $request->admin_note ?? 'N/A' }}
                                    </td>

                                    <td class="p-3">
                                        <div x-data="{ openReviseForm: false }" class="flex items-center gap-2">
                                            @if($request->status === 'For Revision')
                                                <button type="button"
                                                    @click="openReviseForm = true"
                                                    class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold text-white hover:bg-gray-900">
                                                    Revise
                                                </button>
                                            @else
                                                <span class="text-xs text-gray-400">No action</span>
                                            @endif

                                            {{-- Employee Revision Modal --}}
                                            <div x-show="openReviseForm"
                                                x-cloak
                                                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">

                                                <div @click.outside="openReviseForm = false"
                                                    class="w-full max-w-2xl rounded-lg bg-white shadow-lg">

                                                    <div class="flex items-center justify-between border-b px-5 py-3">
                                                        <h3 class="text-base font-semibold text-gray-900">
                                                            Revise Request
                                                        </h3>

                                                        <button type="button"
                                                            @click="openReviseForm = false"
                                                            class="text-gray-400 hover:text-gray-600">
                                                            ✕
                                                        </button>
                                                    </div>

                                                    <form method="POST" action="{{ route('human-capital.employee-requests.update-revision', $request->id) }}">
                                                        @csrf

                                                        <div class="max-h-[70vh] overflow-y-auto p-5">
                                                            <div class="mb-4 rounded-md border border-blue-200 bg-blue-50 p-3 text-sm text-blue-700">
                                                                <strong>Admin Note:</strong>
                                                                {{ $request->admin_note ?? 'Please revise your request.' }}
                                                            </div>

                                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                                                <div>
                                                                    <label class="block mb-1 text-gray-700">Department</label>
                                                                    <input type="text"
                                                                        name="department"
                                                                        value="{{ $request->department }}"
                                                                        class="w-full border-gray-300 rounded-md text-sm">
                                                                </div>

                                                                @if($request->request_type === 'Overtime Request')
                                                                    <div>
                                                                        <label class="block mb-1 text-gray-700">Overtime Date</label>
                                                                        <input type="date"
                                                                            name="overtime_date"
                                                                            value="{{ $request->overtime_date }}"
                                                                            class="w-full border-gray-300 rounded-md text-sm">
                                                                    </div>

                                                                    <div>
                                                                        <label class="block mb-1 text-gray-700">Start Time</label>
                                                                        <input type="time"
                                                                            name="start_time"
                                                                            value="{{ $request->start_time }}"
                                                                            class="w-full border-gray-300 rounded-md text-sm">
                                                                    </div>

                                                                    <div>
                                                                        <label class="block mb-1 text-gray-700">End Time</label>
                                                                        <input type="time"
                                                                            name="end_time"
                                                                            value="{{ $request->end_time }}"
                                                                            class="w-full border-gray-300 rounded-md text-sm">
                                                                    </div>

                                                                    <div>
                                                                        <label class="block mb-1 text-gray-700">Total Hours</label>
                                                                        <input type="number"
                                                                            step="0.01"
                                                                            name="total_hours"
                                                                            value="{{ $request->total_hours }}"
                                                                            class="w-full border-gray-300 rounded-md text-sm">
                                                                    </div>
                                                                @endif

                                                                @if($request->request_type === 'Leave Application Request')
                                                                    <div>
                                                                        <label class="block mb-1 text-gray-700">Leave Type</label>
                                                                        <select name="leave_type" class="w-full border-gray-300 rounded-md text-sm">
                                                                            <option value="">Select leave type</option>
                                                                            <option value="Sick Leave" {{ $request->leave_type === 'Sick Leave' ? 'selected' : '' }}>Sick Leave</option>
                                                                            <option value="Vacation Leave" {{ $request->leave_type === 'Vacation Leave' ? 'selected' : '' }}>Vacation Leave</option>
                                                                            <option value="Emergency Leave" {{ $request->leave_type === 'Emergency Leave' ? 'selected' : '' }}>Emergency Leave</option>
                                                                            <option value="Maternity Leave" {{ $request->leave_type === 'Maternity Leave' ? 'selected' : '' }}>Maternity Leave</option>
                                                                            <option value="Paternity Leave" {{ $request->leave_type === 'Paternity Leave' ? 'selected' : '' }}>Paternity Leave</option>
                                                                            <option value="Others" {{ $request->leave_type === 'Others' ? 'selected' : '' }}>Others</option>
                                                                        </select>
                                                                    </div>

                                                                    <div>
                                                                        <label class="block mb-1 text-gray-700">Start Date</label>
                                                                        <input type="date"
                                                                            name="start_date"
                                                                            value="{{ $request->start_date }}"
                                                                            class="w-full border-gray-300 rounded-md text-sm">
                                                                    </div>

                                                                    <div>
                                                                        <label class="block mb-1 text-gray-700">End Date</label>
                                                                        <input type="date"
                                                                            name="end_date"
                                                                            value="{{ $request->end_date }}"
                                                                            class="w-full border-gray-300 rounded-md text-sm">
                                                                    </div>

                                                                    <div>
                                                                        <label class="block mb-1 text-gray-700">Number of Days</label>
                                                                        <input type="number"
                                                                            step="0.5"
                                                                            name="number_of_days"
                                                                            value="{{ $request->number_of_days }}"
                                                                            class="w-full border-gray-300 rounded-md text-sm">
                                                                    </div>

                                                                    <div>
                                                                        <label class="block mb-1 text-gray-700">With Pay?</label>
                                                                        <select name="with_pay" class="w-full border-gray-300 rounded-md text-sm">
                                                                            <option value="">Select option</option>
                                                                            <option value="Yes" {{ $request->with_pay === 'Yes' ? 'selected' : '' }}>Yes</option>
                                                                            <option value="No" {{ $request->with_pay === 'No' ? 'selected' : '' }}>No</option>
                                                                        </select>
                                                                    </div>
                                                                @endif

                                                                @if($request->request_type === 'Attendance Correction Request')
                                                                    <div>
                                                                        <label class="block mb-1 text-gray-700">Attendance Date</label>
                                                                        <input type="date"
                                                                            name="attendance_date"
                                                                            value="{{ $request->attendance_date }}"
                                                                            class="w-full border-gray-300 rounded-md text-sm">
                                                                    </div>

                                                                    <div>
                                                                        <label class="block mb-1 text-gray-700">Correction Type</label>
                                                                        <select name="correction_type" class="w-full border-gray-300 rounded-md text-sm">
                                                                            <option value="">Select correction type</option>
                                                                            <option value="Time In" {{ $request->correction_type === 'Time In' ? 'selected' : '' }}>Time In</option>
                                                                            <option value="Time Out" {{ $request->correction_type === 'Time Out' ? 'selected' : '' }}>Time Out</option>
                                                                            <option value="Break Time" {{ $request->correction_type === 'Break Time' ? 'selected' : '' }}>Break Time</option>
                                                                            <option value="Lunch Time" {{ $request->correction_type === 'Lunch Time' ? 'selected' : '' }}>Lunch Time</option>
                                                                            <option value="Whole Attendance Record" {{ $request->correction_type === 'Whole Attendance Record' ? 'selected' : '' }}>Whole Attendance Record</option>
                                                                        </select>
                                                                    </div>

                                                                    <div>
                                                                        <label class="block mb-1 text-gray-700">Correct Time</label>
                                                                        <input type="time"
                                                                            name="correct_time"
                                                                            value="{{ $request->correct_time }}"
                                                                            class="w-full border-gray-300 rounded-md text-sm">
                                                                    </div>
                                                                @endif

                                                                @if($request->request_type === 'Undertime / Absence Request')
                                                                    <div>
                                                                        <label class="block mb-1 text-gray-700">Request Type</label>
                                                                        <select name="absence_type" class="w-full border-gray-300 rounded-md text-sm">
                                                                            <option value="">Select request type</option>
                                                                            <option value="Undertime" {{ $request->absence_type === 'Undertime' ? 'selected' : '' }}>Undertime</option>
                                                                            <option value="Absence" {{ $request->absence_type === 'Absence' ? 'selected' : '' }}>Absence</option>
                                                                        </select>
                                                                    </div>

                                                                    <div>
                                                                        <label class="block mb-1 text-gray-700">Date</label>
                                                                        <input type="date"
                                                                            name="request_date"
                                                                            value="{{ $request->request_date }}"
                                                                            class="w-full border-gray-300 rounded-md text-sm">
                                                                    </div>

                                                                    <div>
                                                                        <label class="block mb-1 text-gray-700">Time Affected</label>
                                                                        <input type="time"
                                                                            name="time_affected"
                                                                            value="{{ $request->time_affected }}"
                                                                            class="w-full border-gray-300 rounded-md text-sm">
                                                                    </div>
                                                                @endif

                                                                @if($request->request_type === 'COE Request Form')
                                                                    <div>
                                                                        <label class="block mb-1 text-gray-700">Purpose</label>
                                                                        <select name="purpose" class="w-full border-gray-300 rounded-md text-sm">
                                                                            <option value="">Select purpose</option>
                                                                            <option value="Employment Requirement" {{ $request->purpose === 'Employment Requirement' ? 'selected' : '' }}>Employment Requirement</option>
                                                                            <option value="Loan Application" {{ $request->purpose === 'Loan Application' ? 'selected' : '' }}>Loan Application</option>
                                                                            <option value="Visa / Travel" {{ $request->purpose === 'Visa / Travel' ? 'selected' : '' }}>Visa / Travel</option>
                                                                            <option value="School Requirement" {{ $request->purpose === 'School Requirement' ? 'selected' : '' }}>School Requirement</option>
                                                                            <option value="Others" {{ $request->purpose === 'Others' ? 'selected' : '' }}>Others</option>
                                                                        </select>
                                                                    </div>

                                                                    <div>
                                                                        <label class="block mb-1 text-gray-700">Date Needed</label>
                                                                        <input type="date"
                                                                            name="date_needed"
                                                                            value="{{ $request->date_needed }}"
                                                                            class="w-full border-gray-300 rounded-md text-sm">
                                                                    </div>

                                                                    <div>
                                                                        <label class="block mb-1 text-gray-700">Number of Copies</label>
                                                                        <input type="number"
                                                                            min="1"
                                                                            name="number_of_copies"
                                                                            value="{{ $request->number_of_copies }}"
                                                                            class="w-full border-gray-300 rounded-md text-sm">
                                                                    </div>
                                                                @endif

                                                                <div class="md:col-span-2">
                                                                    <label class="block mb-1 text-gray-700">Reason</label>
                                                                    <textarea rows="3"
                                                                        name="reason"
                                                                        class="w-full border-gray-300 rounded-md text-sm">{{ $request->reason }}</textarea>
                                                                </div>

                                                                <div class="md:col-span-2">
                                                                    <label class="block mb-1 text-gray-700">Remarks</label>
                                                                    <textarea rows="3"
                                                                        name="remarks"
                                                                        class="w-full border-gray-300 rounded-md text-sm">{{ $request->remarks }}</textarea>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="flex justify-end gap-2 border-t px-5 py-3">
                                                            <button type="button"
                                                                @click="openReviseForm = false"
                                                                class="rounded border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                                Cancel
                                                            </button>

                                                            <button type="submit"
                                                                class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                                                                Resubmit Request
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t">
                                    <td class="p-3 text-gray-400" colspan="6">
                                        You have not submitted any employee requests yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($canManageEmployeeRequests)
                {{-- List of Requests --}}
                <div id="content-list" class="request-content hidden bg-white border rounded-lg p-4">
                    <div class="flex items-center justify-between gap-4 mb-4">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">List of Employee Requests</h2>
                            <p class="text-sm text-gray-500">
                                Summary list of submitted employee requests with their approval status.
                            </p>
                        </div>
                    </div>

                    <div class="border rounded-md overflow-auto">
                        <table class="w-full text-sm table-fixed border-collapse">
                            <thead class="bg-gray-50 text-gray-600">
                                <tr>
                                    <th class="w-32 p-3 text-left font-medium">Request No.</th>
                                    <th class="w-48 p-3 text-left font-medium">Employee</th>
                                    <th class="p-3 text-left font-medium">Request Type</th>
                                    <th class="w-36 p-3 text-left font-medium">Date Filed</th>
                                    <th class="w-32 p-3 text-left font-medium">Status</th>
                                    <th class="w-72 p-3 text-left font-medium">Action</th>
                                </tr>
                            </thead>

                            <tbody class="bg-white text-gray-700">
                                @forelse($employeeRequests as $request)
                                    <tr class="border-t hover:bg-gray-50">
                                        <td class="p-3">
                                            REQ-{{ str_pad($request->id, 4, '0', STR_PAD_LEFT) }}
                                        </td>

                                        <td class="p-3">
                                            {{ $request->employee_name }}
                                        </td>

                                        <td class="p-3">
                                            {{ $request->request_type }}
                                        </td>

                                        <td class="p-3">
                                            {{ $request->created_at->format('Y-m-d') }}
                                        </td>

                                        <td class="p-3">
                                            @if($request->status === 'Approved')
                                                <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">Approved</span>
                                            @elseif($request->status === 'Declined')
                                                <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">Declined</span>
                                            @elseif($request->status === 'For Revision')
                                                <span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-700">For Revision</span>
                                            @else
                                                <span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-700">Pending</span>
                                            @endif
                                        </td>

                                        <td class="p-3">
                                            <div x-data="{ openView: false, openReject: false, openRevise: false }" class="flex items-center gap-2">
                                                @if($request->status === 'Pending')
                                                    <form method="POST" action="{{ route('human-capital.employee-requests.approve', $request->id) }}">
                                                        @csrf

                                                        <button type="submit"
                                                            onclick="return confirm('Approve this employee request?')"
                                                            class="rounded-md bg-green-600 px-4 py-2 text-xs font-semibold text-white hover:bg-green-700">
                                                            Approve
                                                        </button>
                                                    </form>

                                                    <button type="button"
                                                        @click="openReject = true"
                                                        class="rounded-md bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">
                                                        Reject
                                                    </button>

                                                    <button type="button"
                                                        @click="openRevise = true"
                                                        class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold text-white hover:bg-gray-900">
                                                        Revise
                                                    </button>
                                                @endif

                                                <button type="button"
                                                    @click="openView = true"
                                                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                    View
                                                </button>

                                                {{-- Admin View Modal --}}
                                                <div x-show="openView"
                                                    x-cloak
                                                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">

                                                    <div @click.outside="openView = false"
                                                        class="w-full max-w-2xl rounded-lg bg-white shadow-lg">

                                                        <div class="flex items-center justify-between border-b px-5 py-3">
                                                            <h3 class="text-base font-semibold text-gray-900">
                                                                Request Details
                                                            </h3>

                                                            <button type="button"
                                                                @click="openView = false"
                                                                class="text-gray-400 hover:text-gray-600">
                                                                ✕
                                                            </button>
                                                        </div>

                                                        <div class="max-h-[70vh] overflow-y-auto p-5 text-sm">
                                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                                <div>
                                                                    <p class="text-gray-500">Request No.</p>
                                                                    <p class="font-medium text-gray-800">
                                                                        REQ-{{ str_pad($request->id, 4, '0', STR_PAD_LEFT) }}
                                                                    </p>
                                                                </div>

                                                                <div>
                                                                    <p class="text-gray-500">Status</p>
                                                                    <p class="font-medium text-gray-800">{{ $request->status }}</p>
                                                                </div>

                                                                <div>
                                                                    <p class="text-gray-500">Employee</p>
                                                                    <p class="font-medium text-gray-800">{{ $request->employee_name }}</p>
                                                                </div>

                                                                <div>
                                                                    <p class="text-gray-500">Request Type</p>
                                                                    <p class="font-medium text-gray-800">{{ $request->request_type }}</p>
                                                                </div>

                                                                <div>
                                                                    <p class="text-gray-500">Department</p>
                                                                    <p class="font-medium text-gray-800">{{ $request->department ?? 'N/A' }}</p>
                                                                </div>

                                                                <div>
                                                                    <p class="text-gray-500">Date Filed</p>
                                                                    <p class="font-medium text-gray-800">{{ $request->created_at->format('Y-m-d') }}</p>
                                                                </div>

                                                                @if($request->overtime_date)
                                                                    <div>
                                                                        <p class="text-gray-500">Overtime Date</p>
                                                                        <p class="font-medium text-gray-800">{{ $request->overtime_date }}</p>
                                                                    </div>
                                                                @endif

                                                                @if($request->start_time)
                                                                    <div>
                                                                        <p class="text-gray-500">Start Time</p>
                                                                        <p class="font-medium text-gray-800">{{ $request->start_time }}</p>
                                                                    </div>
                                                                @endif

                                                                @if($request->end_time)
                                                                    <div>
                                                                        <p class="text-gray-500">End Time</p>
                                                                        <p class="font-medium text-gray-800">{{ $request->end_time }}</p>
                                                                    </div>
                                                                @endif

                                                                @if($request->total_hours)
                                                                    <div>
                                                                        <p class="text-gray-500">Total Hours</p>
                                                                        <p class="font-medium text-gray-800">{{ $request->total_hours }}</p>
                                                                    </div>
                                                                @endif

                                                                @if($request->leave_type)
                                                                    <div>
                                                                        <p class="text-gray-500">Leave Type</p>
                                                                        <p class="font-medium text-gray-800">{{ $request->leave_type }}</p>
                                                                    </div>
                                                                @endif

                                                                @if($request->start_date)
                                                                    <div>
                                                                        <p class="text-gray-500">Start Date</p>
                                                                        <p class="font-medium text-gray-800">{{ $request->start_date }}</p>
                                                                    </div>
                                                                @endif

                                                                @if($request->end_date)
                                                                    <div>
                                                                        <p class="text-gray-500">End Date</p>
                                                                        <p class="font-medium text-gray-800">{{ $request->end_date }}</p>
                                                                    </div>
                                                                @endif

                                                                @if($request->number_of_days)
                                                                    <div>
                                                                        <p class="text-gray-500">Number of Days</p>
                                                                        <p class="font-medium text-gray-800">{{ $request->number_of_days }}</p>
                                                                    </div>
                                                                @endif

                                                                @if($request->with_pay)
                                                                    <div>
                                                                        <p class="text-gray-500">With Pay</p>
                                                                        <p class="font-medium text-gray-800">{{ $request->with_pay }}</p>
                                                                    </div>
                                                                @endif

                                                                @if($request->attendance_date)
                                                                    <div>
                                                                        <p class="text-gray-500">Attendance Date</p>
                                                                        <p class="font-medium text-gray-800">{{ $request->attendance_date }}</p>
                                                                    </div>
                                                                @endif

                                                                @if($request->correction_type)
                                                                    <div>
                                                                        <p class="text-gray-500">Correction Type</p>
                                                                        <p class="font-medium text-gray-800">{{ $request->correction_type }}</p>
                                                                    </div>
                                                                @endif

                                                                @if($request->correct_time)
                                                                    <div>
                                                                        <p class="text-gray-500">Correct Time</p>
                                                                        <p class="font-medium text-gray-800">{{ $request->correct_time }}</p>
                                                                    </div>
                                                                @endif

                                                                @if($request->absence_type)
                                                                    <div>
                                                                        <p class="text-gray-500">Absence / Undertime Type</p>
                                                                        <p class="font-medium text-gray-800">{{ $request->absence_type }}</p>
                                                                    </div>
                                                                @endif

                                                                @if($request->request_date)
                                                                    <div>
                                                                        <p class="text-gray-500">Request Date</p>
                                                                        <p class="font-medium text-gray-800">{{ $request->request_date }}</p>
                                                                    </div>
                                                                @endif

                                                                @if($request->time_affected)
                                                                    <div>
                                                                        <p class="text-gray-500">Time Affected</p>
                                                                        <p class="font-medium text-gray-800">{{ $request->time_affected }}</p>
                                                                    </div>
                                                                @endif

                                                                @if($request->purpose)
                                                                    <div>
                                                                        <p class="text-gray-500">Purpose</p>
                                                                        <p class="font-medium text-gray-800">{{ $request->purpose }}</p>
                                                                    </div>
                                                                @endif

                                                                @if($request->date_needed)
                                                                    <div>
                                                                        <p class="text-gray-500">Date Needed</p>
                                                                        <p class="font-medium text-gray-800">{{ $request->date_needed }}</p>
                                                                    </div>
                                                                @endif

                                                                @if($request->number_of_copies)
                                                                    <div>
                                                                        <p class="text-gray-500">Number of Copies</p>
                                                                        <p class="font-medium text-gray-800">{{ $request->number_of_copies }}</p>
                                                                    </div>
                                                                @endif
                                                            </div>

                                                            <div class="mt-4 space-y-3">
                                                                @if($request->reason)
                                                                    <div>
                                                                        <p class="text-gray-500">Reason</p>
                                                                        <p class="rounded-md bg-gray-50 p-3 text-gray-800">{{ $request->reason }}</p>
                                                                    </div>
                                                                @endif

                                                                @if($request->remarks)
                                                                    <div>
                                                                        <p class="text-gray-500">Remarks</p>
                                                                        <p class="rounded-md bg-gray-50 p-3 text-gray-800">{{ $request->remarks }}</p>
                                                                    </div>
                                                                @endif

                                                                @if($request->admin_note)
                                                                    <div>
                                                                        <p class="text-gray-500">Admin Note</p>
                                                                        <p class="rounded-md bg-yellow-50 p-3 text-yellow-800">{{ $request->admin_note }}</p>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="flex justify-end border-t px-5 py-3">
                                                            <button type="button"
                                                                @click="openView = false"
                                                                class="rounded border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                                Close
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Reject Modal --}}
                                                <div x-show="openReject"
                                                    x-cloak
                                                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">

                                                    <div @click.outside="openReject = false"
                                                        class="w-full max-w-md rounded-lg bg-white shadow-lg">

                                                        <div class="border-b px-5 py-3">
                                                            <h3 class="text-base font-semibold text-gray-900">
                                                                Reject Request
                                                            </h3>
                                                        </div>

                                                        <form method="POST" action="{{ route('human-capital.employee-requests.reject', $request->id) }}">
                                                            @csrf

                                                            <div class="p-5">
                                                                <label class="mb-1 block text-sm text-gray-700">
                                                                    Reason / Note
                                                                </label>

                                                                <textarea name="admin_note"
                                                                    rows="4"
                                                                    class="w-full rounded-md border-gray-300 text-sm"
                                                                    placeholder="Enter reason for rejection"></textarea>
                                                            </div>

                                                            <div class="flex justify-end gap-2 border-t px-5 py-3">
                                                                <button type="button"
                                                                    @click="openReject = false"
                                                                    class="rounded border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                                    Cancel
                                                                </button>

                                                                <button type="submit"
                                                                    class="rounded bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">
                                                                    Reject
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>

                                                {{-- Admin Send Back for Revision Modal --}}
                                                <div x-show="openRevise"
                                                    x-cloak
                                                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">

                                                    <div @click.outside="openRevise = false"
                                                        class="w-full max-w-md rounded-lg bg-white shadow-lg">

                                                        <div class="border-b px-5 py-3">
                                                            <h3 class="text-base font-semibold text-gray-900">
                                                                Send Back for Revision
                                                            </h3>
                                                        </div>

                                                        <form method="POST" action="{{ route('human-capital.employee-requests.revise', $request->id) }}">
                                                            @csrf

                                                            <div class="p-5">
                                                                <label class="mb-1 block text-sm text-gray-700">
                                                                    Revision Note
                                                                </label>

                                                                <textarea name="admin_note"
                                                                    rows="4"
                                                                    required
                                                                    class="w-full rounded-md border-gray-300 text-sm"
                                                                    placeholder="Tell the employee what needs to be revised"></textarea>
                                                            </div>

                                                            <div class="flex justify-end gap-2 border-t px-5 py-3">
                                                                <button type="button"
                                                                    @click="openRevise = false"
                                                                    class="rounded border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                                    Cancel
                                                                </button>

                                                                <button type="submit"
                                                                    class="rounded bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-900">
                                                                    Send Back
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="border-t">
                                        <td class="p-3 text-gray-400" colspan="6">
                                            No employee requests submitted yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>

<script>
    function showRequestTab(tabName) {
        const contents = document.querySelectorAll('.request-content');
        const tabs = document.querySelectorAll('.request-tab');

        contents.forEach(content => {
            content.classList.add('hidden');
        });

        tabs.forEach(tab => {
            tab.classList.remove('border-blue-600', 'text-blue-600', 'font-medium');
            tab.classList.add('border-transparent', 'text-gray-600');
        });

        const content = document.getElementById('content-' + tabName);
        if (content) {
            content.classList.remove('hidden');
        }

        const activeTab = document.getElementById('tab-' + tabName);
        if (activeTab) {
            activeTab.classList.remove('border-transparent', 'text-gray-600');
            activeTab.classList.add('border-blue-600', 'text-blue-600', 'font-medium');
        }
    }

    @if(session('success'))
        @if($canManageEmployeeRequests)
            showRequestTab('list');
        @else
            showRequestTab('my-requests');
        @endif
    @endif
</script>
@endsection

@extends('layouts.app')

@section('content')
<div class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col">
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">

        {{-- HEADER & FILTERS --}}
        <div class="flex flex-wrap items-center justify-between px-4 py-3 border-b gap-4">
            <h1 class="text-lg font-semibold text-gray-900">Attendance Records</h1>

            <div class="flex items-center gap-2">
                @if(auth()->user()->is_admin) {{-- Example Admin Check --}}
                <select class="border rounded px-3 py-1 text-sm bg-gray-50">
                    <option value="">All Employees</option>
                    {{-- Loop employees here --}}
                </select>
                @endif

                <select class="border rounded px-3 py-1 text-sm">
                    <option>Daily</option>
                    <option>Weekly</option>
                    <option>Monthly</option>
                    <option>Payroll Period</option>
                </select>

                <input type="date" class="border rounded px-3 py-1 text-sm">

                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded text-sm transition">
                    Filter
                </button>
            </div>
        </div>

        {{-- TABLE AREA --}}
        <div class="p-4 flex-grow overflow-hidden">
            <div class="border rounded-md h-full overflow-auto bg-white shadow-sm">
                <table class="w-full text-[11px] lg:text-xs table-auto border-collapse">
                    <thead class="bg-gray-100 text-gray-700 sticky top-0 z-20 shadow-sm">
                        <tr class="uppercase tracking-wider">
                            <th class="p-3 text-left border-b">Date</th>
                            <th class="p-3 text-left border-b">Employee</th>
                            <th class="p-3 border-b">Time In</th>
                            <th class="p-3 border-b">Break 1</th>
                            <th class="p-3 border-b">Lunch</th>
                            <th class="p-3 border-b">Break 2</th>
                            <th class="p-3 border-b">Time Out</th>
                            <th class="p-3 border-b text-blue-700">Total Break (Min)</th>
                            <th class="p-3 border-b text-blue-700">Total Lunch (Min)</th>
                            <th class="p-3 border-b font-bold text-gray-900">Work Hours</th>
                            <th class="p-3 border-b">Status</th>
                            <th class="p-3 border-b text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        {{-- Placeholder for Data --}}
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-3 font-medium">May 06, 2026</td>
                            <td class="p-3">Juan Dela Cruz</td>
                            <td class="p-3 text-center">08:00 AM</td>
                            <td class="p-3 text-center text-gray-500">15m</td> {{-- Duration logic --}}
                            <td class="p-3 text-center text-gray-500">60m</td>
                            <td class="p-3 text-center text-gray-500">15m</td>
                            <td class="p-3 text-center">05:00 PM</td>
                            <td class="p-3 text-center bg-blue-50">30</td>
                            <td class="p-3 text-center bg-blue-50">60</td>
                            <td class="p-3 text-center font-bold bg-green-50 text-green-700">8.00</td>
                            <td class="p-3 text-center">
                                <span class="px-2 py-1 rounded-full text-[10px] bg-yellow-100 text-yellow-700 font-semibold uppercase">Pending</span>
                            </td>
                            <td class="p-3 text-center">
                                @if(auth()->user()->is_admin)
                                <button class="bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded shadow-sm transition">
                                    Approve
                                </button>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@extends('layouts.app')

@section('content')
<div class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col">
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">

        {{-- HEADER --}}
        <div class="flex items-center justify-between px-4 py-3 border-b gap-4">
            <h1 class="text-lg font-semibold text-gray-900">Attendance</h1>

            <div class="flex items-center gap-2">
                {{-- FILTERS --}}
                <select class="border rounded px-3 py-1 text-sm">
                    <option>Daily</option>
                    <option>Weekly</option>
                    <option>Monthly</option>
                    <option>Payroll</option>
                </select>

                <input type="date" class="border rounded px-3 py-1 text-sm">

                <button class="bg-blue-600 text-white px-4 py-1.5 rounded text-sm">
                    Filter
                </button>

                <button class="bg-green-600 text-white px-4 py-1.5 rounded text-sm">
                    + Add
                </button>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="p-4 flex-grow overflow-hidden">
            <div class="border rounded-md h-full overflow-auto bg-white">
                <table class="w-full text-xs table-auto border-collapse">
                    <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                        <tr>
                            <th class="p-2 text-left">Date</th>
                            <th class="p-2 text-left">Employee</th>
                            <th class="p-2">Time In</th>
                            <th class="p-2">Time Out</th>
                            <th class="p-2">Break In</th>
                            <th class="p-2">Break Out</th>
                            <th class="p-2">Lunch In</th>
                            <th class="p-2">Lunch Out</th>
                            <th class="p-2">Late</th>
                            <th class="p-2">Working Hours</th>
                            <th class="p-2">Lunch Total</th>
                            <th class="p-2">Break Total</th>
                            <th class="p-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        {{-- STATIC ROWS --}}
                        <tr class="border-t">
                            <td class="p-2">2026-05-05</td>
                            <td class="p-2">Juan Dela Cruz</td>
                            <td class="p-2 text-center">08:00 AM</td>
                            <td class="p-2 text-center">05:00 PM</td>
                            <td class="p-2 text-center">10:00 AM</td>
                            <td class="p-2 text-center">10:15 AM</td>
                            <td class="p-2 text-center">12:00 PM</td>
                            <td class="p-2 text-center">01:00 PM</td>
                            <td class="p-2 text-center text-red-500">5 mins</td>
                            <td class="p-2 text-center">8.00</td>
                            <td class="p-2 text-center">1.00</td>
                            <td class="p-2 text-center">0.25</td>
                            <td class="p-2 text-yellow-600 text-center">Pending</td>
                        </tr>

                        <tr class="border-t">
                            <td class="p-2">2026-05-05</td>
                            <td class="p-2">Maria Santos</td>
                            <td class="p-2 text-center">08:10 AM</td>
                            <td class="p-2 text-center">05:00 PM</td>
                            <td class="p-2 text-center">10:00 AM</td>
                            <td class="p-2 text-center">10:15 AM</td>
                            <td class="p-2 text-center">12:00 PM</td>
                            <td class="p-2 text-center">01:00 PM</td>
                            <td class="p-2 text-center text-red-500">10 mins</td>
                            <td class="p-2 text-center">7.83</td>
                            <td class="p-2 text-center">1.00</td>
                            <td class="p-2 text-center">0.25</td>
                            <td class="p-2 text-green-600 text-center">Approved</td>
                        </tr>

                        {{-- EMPTY STATE --}}
                        <tr class="border-t">
                            <td colspan="13" class="p-4 text-center text-gray-400">
                                No data yet
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
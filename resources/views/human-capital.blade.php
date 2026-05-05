@extends('layouts.app')

@section('content')
<div class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col">
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">

        <div class="flex items-center justify-between px-4 py-3 border-b shrink-0 gap-4">
            <div class="flex items-center flex-1 min-w-0">
                <h1 class="text-lg font-semibold text-gray-900">Human Capital</h1>
            </div>

            <button type="button" class="bg-blue-600 text-white px-6 py-2 rounded text-sm shrink-0">
                + Add
            </button>
        </div>

        <div class="p-4 flex-grow overflow-hidden">
            <div class="border rounded-md h-full overflow-auto bg-white">
                <table class="w-full text-sm table-fixed border-collapse">
                    <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                        <tr>
                            <th class="p-3 text-left">Module</th>
                            <th class="p-3 text-left">Description</th>
                            <th class="w-40 p-3 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3 text-gray-900">Organizational</td>
                            <td class="p-3 text-gray-600">Manage address, office, branch, department, division, unit, and position.</td>
                            <td class="p-3">
                                <a href="{{ route('human-capital.organizational') }}" class="text-blue-600 hover:underline">Open</a>
                            </td>
                        </tr>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3 text-gray-900">Payroll</td>
                            <td class="p-3 text-gray-600">Manage payroll structure, salary setup, benefits, deductions, and reports.</td>
                            <td class="p-3">
                                <a href="{{ route('human-capital.payroll') }}" class="text-blue-600 hover:underline">Open</a>
                            </td>
                        </tr>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3 text-gray-900">Employee Profile</td>
                            <td class="p-3 text-gray-600">Manage employee master list and employee-specific records.</td>
                            <td class="p-3">
                                <a href="{{ route('human-capital.employee-profile') }}" class="text-blue-600 hover:underline">Open</a>
                            </td>
                        </tr>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3 text-gray-900">Recruitment</td>
                            <td class="p-3 text-gray-600">Manage hiring flow, applications, onboarding, and deployment.</td>
                            <td class="p-3">
                                <a href="{{ route('human-capital.recruitment') }}" class="text-blue-600 hover:underline">Open</a>
                            </td>
                        </tr>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3 text-gray-900">Attendance</td>
                            <td class="p-3 text-gray-600">Manage attendance logs, summaries, reports, and official business forms.</td>
                            <td class="p-3">
                                <a href="{{ route('human-capital.attendance') }}" class="text-blue-600 hover:underline">Open</a>
                            </td>
                        </tr>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3 text-gray-900">Employee Requests</td>
                            <td class="p-3 text-gray-600">Manage leave, overtime, correction, absence, and COE requests.</td>
                            <td class="p-3">
                                <a href="{{ route('human-capital.employee-requests') }}" class="text-blue-600 hover:underline">Open</a>
                            </td>
                        </tr>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3 text-gray-900">Employee Relations</td>
                            <td class="p-3 text-gray-600">Manage incident, grievance, mediation, and memo records.</td>
                            <td class="p-3">
                                <a href="{{ route('human-capital.employee-relations') }}" class="text-blue-600 hover:underline">Open</a>
                            </td>
                        </tr>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3 text-gray-900">Training</td>
                            <td class="p-3 text-gray-600">Manage training records, certificates, and employee development files.</td>
                            <td class="p-3">
                                <a href="{{ route('human-capital.training') }}" class="text-blue-600 hover:underline">Open</a>
                            </td>
                        </tr>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3 text-gray-900">Performance</td>
                            <td class="p-3 text-gray-600">Manage evaluations, PIP, and awards.</td>
                            <td class="p-3">
                                <a href="{{ route('human-capital.performance') }}" class="text-blue-600 hover:underline">Open</a>
                            </td>
                        </tr>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3 text-gray-900">OffBoarding</td>
                            <td class="p-3 text-gray-600">Manage resignation, termination, clearance, and final pay process.</td>
                            <td class="p-3">
                                <a href="{{ route('human-capital.offboarding') }}" class="text-blue-600 hover:underline">Open</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@extends('layouts.app')

@section('content')
<div class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col">

    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">

        <!-- HEADER -->
        <div class="flex items-center justify-between px-4 py-3 border-b shrink-0">
            <h1 class="text-lg font-semibold text-gray-900">Performance</h1>

            <button onclick="openSlider()"
                class="bg-blue-600 text-white px-5 py-2 rounded text-sm">
                + Add
            </button>
        </div>

        <!-- TABS -->
        <div class="flex gap-6 px-4 py-3 border-b text-sm font-medium">
            <button onclick="switchTab('employee-evaluation')" id="tab-eval"
                class="text-blue-600 border-b-2 border-blue-600 pb-2">
                Employee Evaluation
            </button>

            <button onclick="switchTab('pip')" id="tab-pip"
                class="text-gray-500 hover:text-gray-700">
                Performance Improvement Plan
            </button>
        </div>

        <!-- CONTENT -->
        <div class="p-4 text-sm text-gray-600">

            <!-- EMPLOYEE EVALUATION TABLE -->
            <div id="employee-evaluation-section">
                <table class="w-full border text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="p-2 border">Employee</th>
                            <th class="p-2 border">Reviewer</th>
                            <th class="p-2 border">Rating</th>
                            <th class="p-2 border">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="p-2 border">John Doe</td>
                            <td class="p-2 border">HR Manager</td>
                            <td class="p-2 border">4</td>
                            <td class="p-2 border">2026-05-12</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PERFORMANCE IMPROVEMENT PLAN TABLE -->
            <div id="pip-section" class="hidden">
                <table class="w-full border text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="p-2 border">Employee</th>
                            <th class="p-2 border">Issue</th>
                            <th class="p-2 border">Start Date</th>
                            <th class="p-2 border">End Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="p-2 border">Jane Doe</td>
                            <td class="p-2 border">Late submissions</td>
                            <td class="p-2 border">2026-05-01</td>
                            <td class="p-2 border">2026-06-01</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<!-- ================= RIGHT SLIDER ================= -->
<div id="slider"
     class="fixed top-0 right-0 w-[500px] h-full bg-white shadow-xl border-l hidden flex flex-col">

    <!-- HEADER -->
    <div class="flex justify-between items-center p-4 border-b">
        <h2 class="font-semibold text-lg" id="slider-title">Add Record</h2>
        <button onclick="closeSlider()">✕</button>
    </div>

    <!-- BODY -->
    <div class="p-4 overflow-y-auto space-y-3">

        <!-- EMPLOYEE EVALUATION FORM -->
        <div id="employee-evaluation-form" class="space-y-3">

            <p class="text-xs text-gray-500">
                Employee Evaluation Form (Full Performance Review Template)
            </p>

            <input class="w-full border rounded p-2" placeholder="Employee Name">
            <input class="w-full border rounded p-2" placeholder="Reviewer">

            <div class="grid grid-cols-2 gap-2">
                <input type="date" class="border rounded p-2">
                <input type="number" placeholder="Rating (1-5)" class="border rounded p-2">
            </div>

            <textarea class="w-full border rounded p-2" placeholder="Key Strengths"></textarea>
            <textarea class="w-full border rounded p-2" placeholder="Areas for Improvement"></textarea>
            <textarea class="w-full border rounded p-2" placeholder="Manager Comments"></textarea>
        </div>

        <!-- PERFORMANCE IMPROVEMENT PLAN FORM -->
        <div id="pip-form" class="space-y-3 hidden">

            <p class="text-xs text-gray-500">
                Performance Improvement Plan (PIP) – Short Record Form
            </p>

            <input class="w-full border rounded p-2" placeholder="Employee Name">

            <textarea class="w-full border rounded p-2" placeholder="Performance Issue Summary"></textarea>

            <div class="grid grid-cols-2 gap-2">
                <input type="date" class="border rounded p-2">
                <input type="date" class="border rounded p-2">
            </div>

            <textarea class="w-full border rounded p-2" placeholder="Action Plan (Short)"></textarea>
        </div>

    </div>

    <!-- FOOTER -->
    <div class="p-4 border-t flex justify-end gap-2">
        <button onclick="closeSlider()" class="px-4 py-2 border rounded text-sm">Cancel</button>
        <button class="px-4 py-2 bg-blue-600 text-white rounded text-sm">Save</button>
    </div>
</div>

<!-- ================= JS ================= -->
<script>
let currentTab = 'employee-evaluation';

function switchTab(tab) {
    currentTab = tab;

    document.getElementById('employee-evaluation-section')
        .classList.toggle('hidden', tab !== 'employee-evaluation');

    document.getElementById('pip-section')
        .classList.toggle('hidden', tab !== 'pip');

    document.getElementById('tab-eval')
        .classList.toggle('text-blue-600', tab === 'employee-evaluation');

    document.getElementById('tab-pip')
        .classList.toggle('text-blue-600', tab === 'pip');
}

function openSlider() {
    document.getElementById('slider').classList.remove('hidden');

    document.getElementById('employee-evaluation-form')
        .classList.toggle('hidden', currentTab !== 'employee-evaluation');

    document.getElementById('pip-form')
        .classList.toggle('hidden', currentTab !== 'pip');

    document.getElementById('slider-title').innerText =
        currentTab === 'employee-evaluation'
            ? 'Add Employee Evaluation'
            : 'Add Performance Improvement Plan';
}

function closeSlider() {
    document.getElementById('slider').classList.add('hidden');
}
</script>
@endsection
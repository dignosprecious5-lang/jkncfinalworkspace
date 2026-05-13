@extends('layouts.app')

@section('content')
<div class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col">

    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">

        {{-- TOP BAR --}}
        <div class="flex items-center justify-between px-4 py-3 border-b shrink-0 gap-4">
            <div class="flex items-center flex-1 min-w-0">
                <h1 class="text-lg font-semibold text-gray-900">OffBoarding</h1>
            </div>

            <button
                type="button"
                onclick="openAddSection()"
                class="bg-blue-600 text-white px-6 py-2 rounded text-sm shrink-0 hover:bg-blue-700 transition"
            >
                + Add
            </button>
        </div>

        {{-- TABS --}}
        <div class="px-4 py-3 border-b bg-white shrink-0 overflow-x-auto">
            <div class="inline-flex min-w-max rounded-md border border-gray-200 overflow-hidden">

                <button
                    type="button"
                    class="offboarding-tab-btn px-4 py-2 text-sm border-r border-gray-200 bg-blue-50 text-blue-700 font-medium"
                    onclick="changeTab('termination', this)"
                >
                    Termination
                </button>

                <button
                    type="button"
                    class="offboarding-tab-btn px-4 py-2 text-sm border-r border-gray-200 bg-white text-gray-600 hover:bg-gray-50"
                    onclick="changeTab('resignation', this)"
                >
                    Resignation
                </button>

                <button
                    type="button"
                    class="offboarding-tab-btn px-4 py-2 text-sm border-r border-gray-200 bg-white text-gray-600 hover:bg-gray-50"
                    onclick="changeTab('exit-interview', this)"
                >
                    Exit Interview Form
                </button>

                <button
                    type="button"
                    class="offboarding-tab-btn px-4 py-2 text-sm border-r border-gray-200 bg-white text-gray-600 hover:bg-gray-50"
                    onclick="changeTab('clearance', this)"
                >
                    Clearance Form
                </button>

                <button
                    type="button"
                    class="offboarding-tab-btn px-4 py-2 text-sm border-r border-gray-200 bg-white text-gray-600 hover:bg-gray-50"
                    onclick="changeTab('turnover', this)"
                >
                    Turnover Checklist
                </button>

                <button
                    type="button"
                    class="offboarding-tab-btn px-4 py-2 text-sm border-r border-gray-200 bg-white text-gray-600 hover:bg-gray-50"
                    onclick="changeTab('final-pay', this)"
                >
                    Final Pay Computation Sheet
                </button>

                <button
                    type="button"
                    class="offboarding-tab-btn px-4 py-2 text-sm bg-white text-gray-600 hover:bg-gray-50"
                    onclick="changeTab('quitclaim', this)"
                >
                    Quitclaim
                </button>

            </div>
        </div>

        {{-- TABLE --}}
        <div class="p-4 flex-grow overflow-hidden">

            <div class="border rounded-md h-full overflow-auto bg-white">

                <table class="w-full text-sm table-fixed border-collapse">

                    <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                        <tr id="tableHeadRow">
                            <th class="px-4 py-3 text-left font-medium">Employee</th>
                            <th class="px-4 py-3 text-left font-medium">Reason</th>
                            <th class="px-4 py-3 text-left font-medium">Date</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                        </tr>
                    </thead>

                    <tbody id="tableBody" class="bg-white">

                        <tr class="border-t">
                            <td colspan="4" class="px-4 py-10 text-center text-gray-400">
                                No records found.
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

        {{-- SLIDE OVER --}}
        <div id="addSection" class="hidden fixed inset-0 z-50">

            <div
                class="absolute inset-0 bg-black/40"
                onclick="closeAddSection()"
            ></div>

            <div
                id="addPanel"
                class="absolute top-0 right-0 h-full w-full max-w-[700px] bg-white shadow-2xl flex flex-col overflow-hidden transform translate-x-full transition-transform duration-300 ease-in-out"
            >

                {{-- HEADER --}}
                <div class="p-6 border-b flex items-center justify-between shrink-0">

                    <h2
                        id="formTitle"
                        class="font-bold text-lg text-gray-900"
                    >
                        Add Termination
                    </h2>

                    <button
                        type="button"
                        onclick="closeAddSection()"
                        class="text-sm text-gray-500 hover:text-gray-700"
                    >
                        Close
                    </button>

                </div>

                {{-- BODY --}}
                <div class="flex-1 overflow-y-auto px-6 py-6">

                    <div class="space-y-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Employee Name
                            </label>

                            <input
                                type="text"
                                class="w-full border rounded-md p-2"
                                placeholder="Enter employee name"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Status
                            </label>

                            <select class="w-full border rounded-md p-2">
                                <option>Pending</option>
                                <option>Processing</option>
                                <option>Completed</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Remarks
                            </label>

                            <textarea
                                rows="5"
                                class="w-full border rounded-md p-2"
                                placeholder="Enter remarks"
                            ></textarea>
                        </div>

                    </div>

                </div>

                {{-- FOOTER --}}
                <div class="p-6 border-t flex gap-2 shrink-0 bg-white">

                    <button
                        type="button"
                        onclick="closeAddSection()"
                        class="flex-1 border rounded py-2"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="flex-1 bg-blue-600 text-white rounded py-2 hover:bg-blue-700 transition"
                    >
                        Save
                    </button>

                </div>

            </div>

        </div>

    </div>

</div>
@endsection


@push('scripts')
<script>

    let currentTab = 'termination';

    function changeTab(tab, button) {

        currentTab = tab;

        // RESET BUTTONS
        document.querySelectorAll('.offboarding-tab-btn').forEach(btn => {

            btn.classList.remove(
                'bg-blue-50',
                'text-blue-700',
                'font-medium'
            );

            btn.classList.add(
                'bg-white',
                'text-gray-600'
            );

        });

        // ACTIVE BUTTON
        button.classList.remove(
            'bg-white',
            'text-gray-600'
        );

        button.classList.add(
            'bg-blue-50',
            'text-blue-700',
            'font-medium'
        );

        const tableHeadRow = document.getElementById('tableHeadRow');
        const formTitle = document.getElementById('formTitle');

        // TERMINATION
        if (tab === 'termination') {

            formTitle.innerText = 'Add Termination';

            tableHeadRow.innerHTML = `
                <th class="px-4 py-3 text-left font-medium">Employee</th>
                <th class="px-4 py-3 text-left font-medium">Reason</th>
                <th class="px-4 py-3 text-left font-medium">Date</th>
                <th class="px-4 py-3 text-left font-medium">Status</th>
            `;
        }

        // RESIGNATION
        if (tab === 'resignation') {

            formTitle.innerText = 'Add Resignation';

            tableHeadRow.innerHTML = `
                <th class="px-4 py-3 text-left font-medium">Employee</th>
                <th class="px-4 py-3 text-left font-medium">Effectivity</th>
                <th class="px-4 py-3 text-left font-medium">Reason</th>
                <th class="px-4 py-3 text-left font-medium">Status</th>
            `;
        }

        // EXIT INTERVIEW
        if (tab === 'exit-interview') {

            formTitle.innerText = 'Add Exit Interview Form';

            tableHeadRow.innerHTML = `
                <th class="px-4 py-3 text-left font-medium">Employee</th>
                <th class="px-4 py-3 text-left font-medium">Interview Date</th>
                <th class="px-4 py-3 text-left font-medium">Interviewer</th>
                <th class="px-4 py-3 text-left font-medium">Status</th>
            `;
        }

        // CLEARANCE
        if (tab === 'clearance') {

            formTitle.innerText = 'Add Clearance Form';

            tableHeadRow.innerHTML = `
                <th class="px-4 py-3 text-left font-medium">Employee</th>
                <th class="px-4 py-3 text-left font-medium">Department</th>
                <th class="px-4 py-3 text-left font-medium">Status</th>
                <th class="px-4 py-3 text-left font-medium">Date</th>
            `;
        }

        // TURNOVER
        if (tab === 'turnover') {

            formTitle.innerText = 'Add Turnover Checklist';

            tableHeadRow.innerHTML = `
                <th class="px-4 py-3 text-left font-medium">Employee</th>
                <th class="px-4 py-3 text-left font-medium">Assigned To</th>
                <th class="px-4 py-3 text-left font-medium">Completion</th>
                <th class="px-4 py-3 text-left font-medium">Status</th>
            `;
        }

        // FINAL PAY
        if (tab === 'final-pay') {

            formTitle.innerText = 'Add Final Pay Computation Sheet';

            tableHeadRow.innerHTML = `
                <th class="px-4 py-3 text-left font-medium">Employee</th>
                <th class="px-4 py-3 text-left font-medium">Amount</th>
                <th class="px-4 py-3 text-left font-medium">Release Date</th>
                <th class="px-4 py-3 text-left font-medium">Status</th>
            `;
        }

        // QUITCLAIM
        if (tab === 'quitclaim') {

            formTitle.innerText = 'Add Quitclaim';

            tableHeadRow.innerHTML = `
                <th class="px-4 py-3 text-left font-medium">Employee</th>
                <th class="px-4 py-3 text-left font-medium">Document</th>
                <th class="px-4 py-3 text-left font-medium">Signed Date</th>
                <th class="px-4 py-3 text-left font-medium">Status</th>
            `;
        }
    }

    function openAddSection() {

        const section = document.getElementById('addSection');
        const panel = document.getElementById('addPanel');

        section.classList.remove('hidden');

        setTimeout(() => {
            panel.classList.remove('translate-x-full');
        }, 10);
    }

    function closeAddSection() {

        const section = document.getElementById('addSection');
        const panel = document.getElementById('addPanel');

        panel.classList.add('translate-x-full');

        setTimeout(() => {
            section.classList.add('hidden');
        }, 300);
    }

</script>
@endpush
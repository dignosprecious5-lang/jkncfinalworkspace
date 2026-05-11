@extends('layouts.app')

@section('content')
<div class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col" x-data="onboardingPage()">

    {{-- TABS --}}
    <div class="flex items-center border-b border-gray-200 mb-4 gap-1">
        <template x-for="tab in tabs" :key="tab.key">
            <button
                type="button"
                @click="activeTab = tab.key; closeAllModals()"
                :class="activeTab === tab.key
                    ? 'border-b-2 border-blue-600 text-blue-600 font-semibold'
                    : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 text-sm transition-colors -mb-px focus:outline-none"
                x-text="tab.label"
            ></button>
        </template>
    </div>

    {{-- TOOLBAR --}}
    <div class="flex items-center gap-3 mb-4">
        <div class="relative flex-1 max-w-md">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" x-model="search" :placeholder="'Search ' + activeTab + '...'"
                class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400 bg-white">
        </div>

        <button type="button" class="flex items-center gap-2 px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-700 transition">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M6 12h12M10 18h4"/></svg>
            Filter
        </button>

        <button type="button" @click="downloadCSV()"
            class="flex items-center gap-2 px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-700 transition">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/></svg>
            Download CSV
        </button>

        <div x-show="activeTab === 'PDS'" class="flex items-center border border-blue-200 rounded-lg bg-blue-50/50">
            <a href="{{ route('careers.pds') }}" target="_blank" class="flex items-center gap-2 px-4 py-2 text-sm text-blue-700 font-semibold hover:bg-blue-100 transition rounded-l-lg shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                Open Careers Page
            </a>
            <div class="w-px h-4 bg-blue-200"></div>
            <button type="button" @click="navigator.clipboard.writeText(window.location.protocol + '//' + window.location.host + '/careers/pds'); alert('Careers public link copied to clipboard!');" class="flex items-center gap-2 px-4 py-2 text-sm text-blue-700 font-semibold hover:bg-blue-100 transition rounded-r-lg shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                Copy Link
            </button>
        </div>

        {{-- ADD NEW - PDS --}}
<button
    x-show="activeTab === 'PDS'"
    type="button"
    @click="openModal()"
    class="flex items-center gap-2 px-5 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-medium shadow-sm"
>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path d="M12 5v14M5 12h14"/>
    </svg>
    Add New
</button>

{{-- ADD NEW - CHECKLIST --}}
<button
    x-show="activeTab === 'Checklist Submission'"
    type="button"
    @click="
        showPdsModal = false;
        showEmpRegModal = false;
        showTrainingModal = false;
        openChecklistModal();
    "
    class="flex items-center gap-2 px-5 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-medium shadow-sm"
>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path d="M12 5v14M5 12h14"/>
    </svg>
    Add New
</button>

{{-- ADD NEW - EMPLOYEE REGISTRATION --}}
<button
    x-show="activeTab === 'Employee Registration'"
    type="button"
    @click="openEmpRegModal()"
    class="flex items-center gap-2 px-5 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-medium shadow-sm"
>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path d="M12 5v14M5 12h14"/>
    </svg>
    Add New
</button>

{{-- ADD NEW - TRAINING --}}
<button
    x-show="activeTab === 'Training'"
    type="button"
    @click="
        showPdsModal = false;
        showChecklistModal = false;
        showEmpRegModal = false;
        trainingForm = {
            employeeName: '',
            program: '',
            startDate: '',
            dueDate: '',
            trainer: '',
            description: ''
        };
        showTrainingModal = true;
    "
    class="flex items-center gap-2 px-5 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-medium shadow-sm"
>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path d="M12 5v14M5 12h14"/>
    </svg>
    Add New
</button>
    </div>

    {{-- TABLE --}}
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0 overflow-hidden">
        <div class="overflow-auto flex-grow">

            {{-- PDS TABLE --}}
            <table class="w-full text-sm border-collapse" x-show="activeTab === 'PDS'">
                <thead class="bg-gray-50 text-gray-600 sticky top-0 z-10">
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-3 text-left font-semibold">Name</th>
                        <th class="px-4 py-3 text-left font-semibold">Position</th>
                        <th class="px-4 py-3 text-left font-semibold">Email</th>
                        <th class="px-4 py-3 text-left font-semibold">Phone</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-left font-semibold">Date Submitted</th>
                        <th class="px-4 py-3 text-left font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="filteredRows.length === 0">
                        <tr><td colspan="7" class="px-4 py-16 text-center text-gray-400"><div class="flex flex-col items-center gap-2"><svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg><span class="text-sm">No PDS records found. Click + Add New to create one.</span></div></td></tr>
                    </template>
                    <template x-for="(row, i) in filteredRows" :key="i">
                        <tr class="border-t border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-gray-800 font-medium" x-text="row.fullName"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="row.position"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="row.email"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="row.phone"></td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Submitted</span></td>
                            <td class="px-4 py-3 text-gray-500" x-text="row.submittedDate"></td>
                            <td class="px-4 py-3">
    <button @click="deletePds(row.db_id)" class="text-xs text-red-500 hover:underline">
        Delete
    </button>
</td>
                        </tr>
                    </template>
                </tbody>
            </table>

            
{{-- CHECKLIST TABLE --}}
<table class="w-full text-sm border-collapse" x-show="activeTab === 'Checklist Submission'">
    <thead class="bg-gray-50 text-gray-600 sticky top-0 z-10">
        <tr class="border-b border-gray-200">
            <th class="px-4 py-3 text-left font-semibold">Employee Name</th>
            <th class="px-4 py-3 text-left font-semibold">Position</th>
            <th class="px-4 py-3 text-left font-semibold">Submitted</th>
            <th class="px-4 py-3 text-left font-semibold">Approved</th>
            <th class="px-4 py-3 text-left font-semibold">Status</th>
            <th class="px-4 py-3 text-left font-semibold">Date</th>
            <th class="px-4 py-3 text-left font-semibold">Actions</th>
        </tr>
    </thead>
    <tbody>
        <template x-if="filteredRows.length === 0">
            <tr>
                <td colspan="7" class="px-4 py-16 text-center text-gray-400">
                    <div class="flex flex-col items-center gap-2">
                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>
                        <span class="text-sm">No checklist records found. Click + Add New to create one.</span>
                    </div>
                </td>
            </tr>
        </template>
        <template x-for="(row, i) in filteredRows" :key="row.id || i">
            <tr class="border-t border-gray-100 hover:bg-gray-50 transition">
                <td class="px-4 py-3 text-gray-800 font-medium" x-text="row.employeeName"></td>
                <td class="px-4 py-3 text-gray-600" x-text="row.position || '—'"></td>
                <td class="px-4 py-3 text-gray-600" x-text="`${row.docsSubmitted || 0} / ${row.totalDocs || 0}`"></td>
                <td class="px-4 py-3 text-gray-600" x-text="`${row.docsApproved || 0} / ${row.totalDocs || 0}`"></td>
                <td class="px-4 py-3">
                    <span :class="checklistStatusClass(row.status)" class="px-2 py-0.5 rounded-full text-xs font-medium" x-text="row.status || 'For Review'"></span>
                </td>
                <td class="px-4 py-3 text-gray-500" x-text="row.submittedDate"></td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <button @click="viewChecklist(row)" class="text-xs text-blue-600 hover:underline mr-2">Review</button>
                    <button @click="deleteChecklist(i)" class="text-xs text-red-500 hover:underline">Delete</button>
                </td>
            </tr>
        </template>
    </tbody>
</table>

{{-- EMPLOYEE REGISTRATION TABLE --}}
            <table class="w-full text-sm border-collapse" x-show="activeTab === 'Employee Registration'">
                <thead class="bg-gray-50 text-gray-600 sticky top-0 z-10">
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-3 text-left font-semibold">Full Name</th>
                        <th class="px-4 py-3 text-left font-semibold">Employee ID</th>
                        <th class="px-4 py-3 text-left font-semibold">Department</th>
                        <th class="px-4 py-3 text-left font-semibold">Start Date</th>
                        <th class="px-4 py-3 text-left font-semibold">Work Email</th>
                        <th class="px-4 py-3 text-left font-semibold">Manager</th>
                        <th class="px-4 py-3 text-left font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="filteredRows.length === 0">
                        <tr><td colspan="7" class="px-4 py-16 text-center text-gray-400"><div class="flex flex-col items-center gap-2"><svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg><span class="text-sm">No employee registrations found. Click + Add New to create one.</span></div></td></tr>
                    </template>
                    <template x-for="(row, i) in filteredRows" :key="i">
                        <tr class="border-t border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-gray-800 font-medium" x-text="row.fullName"></td>
                            <td class="px-4 py-3 text-blue-600 font-medium" x-text="row.employeeId"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="row.department"></td>
                            <td class="px-4 py-3 text-gray-500" x-text="row.startDate"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="row.workEmail"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="row.manager"></td>
                            <td class="px-4 py-3"><button @click="deleteEmpReg(i)" class="text-xs text-red-500 hover:underline">Delete</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>

            {{-- TRAINING TABLE --}}
            <table class="w-full text-sm border-collapse" x-show="activeTab === 'Training'">
                <thead class="bg-gray-50 text-gray-600 sticky top-0 z-10">
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-3 text-left font-semibold">Employee</th>
                        <th class="px-4 py-3 text-left font-semibold">Training Program</th>
                        <th class="px-4 py-3 text-left font-semibold">Trainer</th>
                        <th class="px-4 py-3 text-left font-semibold">Start Date</th>
                        <th class="px-4 py-3 text-left font-semibold">Due Date</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-left font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="filteredRows.length === 0">
                        <tr><td colspan="7" class="px-4 py-16 text-center text-gray-400"><div class="flex flex-col items-center gap-2"><svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg><span class="text-sm">No training records found. Click + Add New to create one.</span></div></td></tr>
                    </template>
                    <template x-for="(row, i) in filteredRows" :key="i">
                        <tr class="border-t border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-gray-800 font-medium" x-text="row.employeeName"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="trainings.find(t => t.id == row.training_id || t.id == row.trainingId)?.title || row.program || row.training_id || row.trainingId"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="row.trainer"></td>
                            <td class="px-4 py-3 text-gray-500" x-text="row.startDate"></td>
                            <td class="px-4 py-3 text-gray-500" x-text="row.dueDate"></td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Scheduled</span></td>
                            <td class="px-4 py-3"><button @click="deleteTraining(i)" class="text-xs text-red-500 hover:underline">Delete</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>

        </div>
    </div>

    {{-- ===================== PDS SLIDE-OVER ===================== --}}
    <div
        x-show="showPdsModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex justify-end bg-black/50 backdrop-blur-sm"
        style="display:none;"
        @click.self="showPdsModal = false"
    >
        <div
            x-show="showPdsModal"
            x-transition:enter="transform transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="bg-gray-100 shadow-2xl w-[95vw] h-full flex flex-col overflow-hidden"
        >
            {{-- Top bar --}}
            <div class="flex items-center justify-between px-6 py-3 bg-white border-b shrink-0">
                <h2 class="text-sm font-bold text-gray-800 uppercase tracking-widest">New Personal Data Sheet (PDS)</h2>
                <button @click="showPdsModal = false" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Split body --}}
            <div class="flex flex-1 overflow-hidden gap-4 p-4">

                {{-- RIGHT: INPUT FORM --}}
<div class="w-[48%] bg-white rounded-xl shadow border border-gray-200 flex flex-col overflow-hidden shrink-0 order-last">
    <div class="px-5 py-3 border-b bg-blue-700 rounded-t-xl">
        <p class="text-xs font-bold text-white uppercase tracking-wider">Fill Up Form</p>
    </div>
    <form @submit.prevent="submitPds()" class="flex-1 overflow-y-auto px-5 py-4 space-y-5">

        <div>
            <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-3 pb-1 border-b border-blue-100">I. Personal Information</p>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Surname</label><input type="text" x-model="pdsForm.surname" @input="pdsForm.fullName = pdsForm.surname + ', ' + pdsForm.firstName" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">First Name</label><input type="text" x-model="pdsForm.firstName" @input="pdsForm.fullName = pdsForm.surname + ', ' + pdsForm.firstName" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Middle Name</label><input type="text" x-model="pdsForm.middleName" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Name Extension</label><input type="text" x-model="pdsForm.nameExt" placeholder="Jr., Sr., III, etc." class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
            </div>
            <div class="grid grid-cols-3 gap-3 mb-3">
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Date of Birth</label><input type="date" x-model="pdsForm.dob" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none bg-white"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Place of Birth</label><input type="text" x-model="pdsForm.pob" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Citizenship</label><input type="text" x-model="pdsForm.citizenship" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Sex</label><select x-model="pdsForm.sex" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none bg-white"><option value="">Select</option><option>Male</option><option>Female</option></select></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Civil Status</label><select x-model="pdsForm.civilStatus" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none bg-white"><option value="">Select</option><option>Single</option><option>Married</option><option>Widowed</option><option>Legally Separated</option></select></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Height (meters)</label><input type="text" x-model="pdsForm.height" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Weight (kilograms)</label><input type="text" x-model="pdsForm.weight" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div class="col-span-2"><label class="block text-xs font-semibold text-gray-600 mb-1">Blood Type</label><input type="text" x-model="pdsForm.bloodType" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">SSS Number</label><input type="text" x-model="pdsForm.sss" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">PhilHealth Number</label><input type="text" x-model="pdsForm.philhealth" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Pag-IBIG ID Number</label><input type="text" x-model="pdsForm.pagibig" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">TIN Number</label><input type="text" x-model="pdsForm.tin" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div class="col-span-2"><label class="block text-xs font-bold text-gray-600">Residential Address</label></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">House / Block / Lot No.</label><input type="text" x-model="pdsForm.resHouse" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Street</label><input type="text" x-model="pdsForm.resStreet" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Subdivision / Village</label><input type="text" x-model="pdsForm.resSubdiv" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Barangay</label><input type="text" x-model="pdsForm.resBrgy" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">City / Municipality</label><input type="text" x-model="pdsForm.resCity" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Province</label><input type="text" x-model="pdsForm.resProv" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div class="col-span-2"><label class="block text-xs font-semibold text-gray-600 mb-1">ZIP Code</label><input type="text" x-model="pdsForm.resZip" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div class="col-span-2 flex items-center justify-between">
                    <label class="block text-xs font-bold text-gray-600">Permanent Address</label>
                    <label class="flex items-center gap-1 cursor-pointer"><input type="checkbox" x-model="pdsForm.permSameAsRes" @change="if(pdsForm.permSameAsRes){ pdsForm.permHouse=pdsForm.resHouse; pdsForm.permStreet=pdsForm.resStreet; pdsForm.permSubdiv=pdsForm.resSubdiv; pdsForm.permBrgy=pdsForm.resBrgy; pdsForm.permCity=pdsForm.resCity; pdsForm.permProv=pdsForm.resProv; pdsForm.permZip=pdsForm.resZip; }"><span class="text-[10px] text-gray-500">Same as residential</span></label>
                </div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">House / Block / Lot No.</label><input type="text" x-model="pdsForm.permHouse" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Street</label><input type="text" x-model="pdsForm.permStreet" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Subdivision / Village</label><input type="text" x-model="pdsForm.permSubdiv" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Barangay</label><input type="text" x-model="pdsForm.permBrgy" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">City / Municipality</label><input type="text" x-model="pdsForm.permCity" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Province</label><input type="text" x-model="pdsForm.permProv" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div class="col-span-2"><label class="block text-xs font-semibold text-gray-600 mb-1">ZIP Code</label><input type="text" x-model="pdsForm.permZip" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div class="col-span-2"><label class="block text-xs font-bold text-gray-600">Contact Information</label></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Telephone Number</label><input type="text" x-model="pdsForm.telNo" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Mobile Number</label><input type="text" x-model="pdsForm.mobileNo" @input="pdsForm.phone = pdsForm.mobileNo" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
                <div class="col-span-2"><label class="block text-xs font-semibold text-gray-600 mb-1">Email</label><input type="email" x-model="pdsForm.email" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none"></div>
            </div>
        </div>

        <div>
            <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-3 pb-1 border-b border-blue-100">II. Family Background</p>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Spouse's Surname</label><input type="text" x-model="pdsForm.spouseSurname" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Spouse's First Name</label><input type="text" x-model="pdsForm.spouseFirstName" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Spouse's Middle Name</label><input type="text" x-model="pdsForm.spouseMiddleName" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Spouse's Name Ext.</label><input type="text" x-model="pdsForm.spouseNameExt" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Spouse's Occupation</label><input type="text" x-model="pdsForm.spouseOccupation" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Employer / Business</label><input type="text" x-model="pdsForm.spouseEmployer" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Business Address</label><input type="text" x-model="pdsForm.spouseBusinessAddress" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Telephone Number</label><input type="text" x-model="pdsForm.spouseTelNo" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
            </div>
            <div class="mb-3">
                <label class="block text-xs font-bold text-gray-600 mb-2">Children</label>
                <template x-for="(child, idx) in pdsForm.children" :key="idx">
                    <div class="grid grid-cols-3 gap-2 mb-2">
                        <input type="text" x-model="child.name" placeholder="Full Name" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded">
                        <select x-model="child.gender" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded bg-white"><option value="">Gender</option><option>Male</option><option>Female</option></select>
                        <input type="date" x-model="child.dob" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded bg-white">
                    </div>
                </template>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div class="col-span-2"><label class="block text-xs font-bold text-gray-600">Father's Name</label></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Surname</label><input type="text" x-model="pdsForm.fatherSurname" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">First Name</label><input type="text" x-model="pdsForm.fatherFirstName" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Middle Name</label><input type="text" x-model="pdsForm.fatherMiddleName" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Name Extension</label><input type="text" x-model="pdsForm.fatherNameExt" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                
                <div class="col-span-2"><label class="block text-xs font-bold text-gray-600 mt-2">Mother's Maiden Name</label></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Surname</label><input type="text" x-model="pdsForm.motherMaidenSurname" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">First Name</label><input type="text" x-model="pdsForm.motherFirstName" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
                <div class="col-span-2"><label class="block text-xs font-semibold text-gray-600 mb-1">Middle Name</label><input type="text" x-model="pdsForm.motherMiddleName" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded"></div>
            </div>
        </div>

        <div>
            <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-3 pb-1 border-b border-blue-100">III. Educational Background</p>
            <div class="mb-3">
                <label class="block text-xs font-bold text-gray-600 mb-2">A. Formal Education</label>
                <div class="grid grid-cols-4 gap-2 mb-2">
                    <div class="text-xs pt-2 font-bold text-gray-500">Elementary</div>
                    <input type="text" x-model="pdsForm.educElemSchool" placeholder="School" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <input type="text" x-model="pdsForm.educElemFrom" placeholder="From" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <input type="text" x-model="pdsForm.educElemTo" placeholder="To" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                </div>
                <div class="grid grid-cols-4 gap-2 mb-2">
                    <div class="text-xs pt-2 font-bold text-gray-500">Secondary</div>
                    <input type="text" x-model="pdsForm.educSecSchool" placeholder="School" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <input type="text" x-model="pdsForm.educSecFrom" placeholder="From" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <input type="text" x-model="pdsForm.educSecTo" placeholder="To" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                </div>
                <div class="grid grid-cols-4 gap-2 mb-2">
                    <div class="text-xs pt-2 font-bold text-gray-500">College</div>
                    <input type="text" x-model="pdsForm.educCollSchool" placeholder="School" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <input type="text" x-model="pdsForm.educCollDegree" placeholder="Degree" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <div class="flex gap-1">
                        <input type="text" x-model="pdsForm.educCollFrom" placeholder="Fr" class="w-1/2 text-sm px-1 py-1.5 border border-gray-300 rounded">
                        <input type="text" x-model="pdsForm.educCollTo" placeholder="To" class="w-1/2 text-sm px-1 py-1.5 border border-gray-300 rounded">
                    </div>
                </div>
                <div class="grid grid-cols-4 gap-2 mb-2">
                    <div class="text-xs pt-2 font-bold text-gray-500">Masters</div>
                    <input type="text" x-model="pdsForm.educMastSchool" placeholder="School" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <input type="text" x-model="pdsForm.educMastDegree" placeholder="Degree" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <div class="flex gap-1">
                        <input type="text" x-model="pdsForm.educMastFrom" placeholder="Fr" class="w-1/2 text-sm px-1 py-1.5 border border-gray-300 rounded">
                        <input type="text" x-model="pdsForm.educMastTo" placeholder="To" class="w-1/2 text-sm px-1 py-1.5 border border-gray-300 rounded">
                    </div>
                </div>
                <div class="grid grid-cols-4 gap-2 mb-2">
                    <div class="text-xs pt-2 font-bold text-gray-500">Doctorate</div>
                    <input type="text" x-model="pdsForm.educDoctSchool" placeholder="School" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <input type="text" x-model="pdsForm.educDoctDegree" placeholder="Degree" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                    <div class="flex gap-1">
                        <input type="text" x-model="pdsForm.educDoctFrom" placeholder="Fr" class="w-1/2 text-sm px-1 py-1.5 border border-gray-300 rounded">
                        <input type="text" x-model="pdsForm.educDoctTo" placeholder="To" class="w-1/2 text-sm px-1 py-1.5 border border-gray-300 rounded">
                    </div>
                </div>
            </div>
            <div class="mb-3 mt-4">
                <label class="block text-xs font-bold text-gray-600 mb-2">B. Learning & Development</label>
                <template x-for="(l, idx) in pdsForm.lnd" :key="idx">
                    <div class="grid grid-cols-4 gap-2 mb-2">
                        <input type="text" x-model="l.title" placeholder="Title" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                        <input type="text" x-model="l.conductedBy" placeholder="Conducted By" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded">
                        <input type="text" x-model="l.date" placeholder="Date" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded bg-white">
                        <select x-model="l.cert" class="w-full text-sm px-2 py-1.5 border border-gray-300 rounded bg-white"><option value="">Cert?</option><option>Yes</option><option>No</option></select>
                    </div>
                </template>
            </div>
        </div>

        <div>
            <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-3 pb-1 border-b border-blue-100">IV. Declaration</p>
            <label class="flex items-start gap-2 cursor-pointer mt-2 border rounded p-3 bg-gray-50 border-gray-200">
                <input type="checkbox" x-model="pdsForm.consent" required class="mt-1 w-4 h-4 text-blue-600 rounded cursor-pointer">
                <span class="text-xs text-gray-700 leading-tight">I certify that the information provided herein is true, complete, and accurate. I understand and consent to the data privacy policy. *</span>
            </label>
        </div>

        <div class="flex justify-end gap-3 pt-2 pb-1">
            <button type="button" @click="showPdsModal = false" class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancel</button>
            <button type="submit" class="px-5 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium shadow-sm transition">Submit PDS</button>
        </div>
    </form>
</div>


                {{-- LEFT: LIVE PDS DOCUMENT PREVIEW --}}
<div class="flex-1 bg-white rounded-xl shadow border border-gray-200 flex flex-col overflow-hidden">
    <div class="px-5 py-3 border-b bg-gray-50 rounded-t-xl shrink-0 flex items-center justify-between">
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Live Preview</p>
        <button type="button" @click="downloadPdsPdf()" class="text-xs px-3 py-1.5 bg-white hover:bg-gray-50 border border-gray-300 rounded text-gray-700 font-semibold flex items-center gap-1 transition shadow-sm">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Download PDF
        </button>
    </div>
    
    <div class="flex-1 overflow-y-auto p-5 bg-gray-100" id="pdf-container">
        <div id="pds-doc-preview" class="bg-white mx-auto shadow-md" style="width: 210mm; min-height: 297mm; padding: 15mm; font-family: sans-serif;">
            
            <!-- Header -->
            <div class="flex items-start justify-between border-b pb-2 mb-4 border-gray-200">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('images/imaglogo.png') }}" onerror="this.src='{{ asset('images/imag1logo.jpg') }}'" class="h-10">
                    <div class="leading-tight text-blue-900 font-serif">
                        <span class="text-xl font-bold">John Kelly</span><br>
                        <span class="text-sm italic">&amp; Company</span>
                    </div>
                </div>
                <div class="text-[8px] text-gray-600 text-right leading-tight max-w-[200mm]">
                    <p class="font-bold">Atty. Jose B. Ogang, CPA, MMPSM • Jose Tomayo Rio, MM-BA, CPA • Lyndon Earl P. Rio, RN, CB • John Kelly Abalde, CLSSBB, CPM</p>
                    <p>3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000</p>
                    <p>Email: start@jknc.io Website: https://jknc.io/ Phone Number: 0995-535-8729</p>
                    <p class="font-bold text-[7px] mt-1 text-gray-500">Form Code: PDS-001 Version: 1.0 Effective Date: December 1, 2025 Issued By: Office of the President</p>
                </div>
            </div>
            
            <h1 class="text-center text-blue-800 font-bold text-lg mb-4">Personal Data Sheet</h1>
            
            <!-- PAGE 1 -->
            <div class="border border-gray-200 rounded-lg mb-4 text-[10px] pb-2">
                <div class="px-4 py-1.5 mb-2 -mt-2 bg-white inline-block text-blue-800 font-bold uppercase tracking-wider text-xs ml-4">I. PERSONAL INFORMATION</div>
                
                <div class="px-4 mb-2">
                    <div class="font-bold mb-1">A. Identification</div>
                    <div class="grid grid-cols-4 gap-2">
                        <div><label class="text-gray-600 block mb-0.5">Surname:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.surname"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">First Name:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.firstName"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">Middle Name:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.middleName"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">Name Extension:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white text-gray-400"><span x-show="pdsForm.nameExt" x-text="pdsForm.nameExt" class="text-black"></span><span x-show="!pdsForm.nameExt">Jr., Sr., III, etc.</span></div></div>
                    </div>
                </div>

                <div class="px-4 mb-2">
                    <div class="font-bold mb-1">B. Birth &amp; Citizenship</div>
                    <div class="grid grid-cols-3 gap-2">
                        <div><label class="text-gray-600 block mb-0.5">Date of Birth:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center justify-between bg-white"><span x-text="pdsForm.dob || 'dd/mm/yyyy'" :class="pdsForm.dob ? 'text-black' : 'text-gray-400'"></span><svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg></div></div>
                        <div><label class="text-gray-600 block mb-0.5">Place of Birth:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.pob"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">Citizenship:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.citizenship"></div></div>
                    </div>
                </div>

                <div class="px-4 mb-2">
                    <div class="font-bold mb-1">C. Personal Details</div>
                    <div class="grid grid-cols-4 gap-2">
                        <div><label class="text-gray-600 block mb-0.5">Sex:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center justify-between bg-white"><span x-text="pdsForm.sex || 'Select'" :class="pdsForm.sex ? 'text-black' : 'text-gray-400'"></span><svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg></div></div>
                        <div><label class="text-gray-600 block mb-0.5">Civil Status:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center justify-between bg-white"><span x-text="pdsForm.civilStatus || 'Select'" :class="pdsForm.civilStatus ? 'text-black' : 'text-gray-400'"></span><svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg></div></div>
                        <div><label class="text-gray-600 block mb-0.5">Height (meters):</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.height"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">Weight (kilograms):</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.weight"></div></div>
                    </div>
                    <div class="mt-2">
                        <label class="text-gray-600 block mb-0.5">Blood Type:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.bloodType"></div>
                    </div>
                </div>
                
                <div class="px-4 mb-2">
                    <div class="font-bold mb-1">D. Government Identification Numbers</div>
                    <div class="grid grid-cols-4 gap-2">
                        <div><label class="text-gray-600 block mb-0.5">SSS Number:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.sss"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">PhilHealth Number:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.philhealth"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">Pag-IBIG ID Number:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.pagibig"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">TIN Number:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.tin"></div></div>
                    </div>
                </div>

                <div class="px-4 mb-2">
                    <div class="font-bold mb-1">E. Residential Address</div>
                    <div class="grid grid-cols-4 gap-2 mb-2">
                        <div><label class="text-gray-600 block mb-0.5">House / Block / Lot No.:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.resHouse"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">Street:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.resStreet"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">Subdivision / Village:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.resSubdiv"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">Barangay:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.resBrgy"></div></div>
                    </div>
                    <div class="grid grid-cols-4 gap-2">
                        <div class="col-span-2"><label class="text-gray-600 block mb-0.5">City / Municipality:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.resCity"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">Province:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.resProv"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">ZIP Code:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.resZip"></div></div>
                    </div>
                </div>

                <div class="px-4 mb-2 relative">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="font-bold">F. Permanent Address</div>
                        <div class="flex items-center gap-1 text-gray-500"><div class="w-3 h-3 border border-gray-400 rounded-sm flex items-center justify-center"><svg x-show="pdsForm.permSameAsRes" class="w-2 h-2 text-black" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"></path></svg></div><span class="text-[9px]">Same as residential address</span></div>
                    </div>
                    <div class="grid grid-cols-4 gap-2 mb-2">
                        <div><label class="text-gray-600 block mb-0.5">House / Block / Lot No.:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.permHouse"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">Street:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.permStreet"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">Subdivision / Village:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.permSubdiv"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">Barangay:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.permBrgy"></div></div>
                    </div>
                    <div class="grid grid-cols-4 gap-2">
                        <div class="col-span-2"><label class="text-gray-600 block mb-0.5">City / Municipality:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.permCity"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">Province:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.permProv"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">ZIP Code:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.permZip"></div></div>
                    </div>
                </div>
                
                <div class="px-4 mb-2">
                    <div class="font-bold mb-1">G. Contact Information</div>
                    <div class="grid grid-cols-3 gap-2">
                        <div><label class="text-gray-600 block mb-0.5">Telephone Number (if any):</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.telNo"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">Mobile Number:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.mobileNo"></div></div>
                        <div><label class="text-gray-600 block mb-0.5">Email Address:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.email"></div></div>
                    </div>
                </div>
            </div>
            
            <!-- PAGE 2 equivalent -->
            <div class="border border-gray-200 rounded-lg mb-4 text-[10px] pb-2 mt-4" style="page-break-before: always;">
                <div class="px-4 py-1.5 mb-2 -mt-2 bg-white inline-block text-blue-800 font-bold uppercase tracking-wider text-xs ml-4">II. FAMILY BACKGROUND</div>
                
                <div class="px-4 mb-2 grid grid-cols-4 gap-2">
                    <div><label class="text-gray-600 block mb-0.5">Spouse's Surname:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.spouseSurname"></div></div>
                    <div><label class="text-gray-600 block mb-0.5">Spouse's First Name:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.spouseFirstName"></div></div>
                    <div><label class="text-gray-600 block mb-0.5">Spouse's Middle Name:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.spouseMiddleName"></div></div>
                    <div><label class="text-gray-600 block mb-0.5">Spouse's Name Extension:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.spouseNameExt"></div></div>
                </div>
                <div class="px-4 mb-3 grid grid-cols-4 gap-2">
                    <div><label class="text-gray-600 block mb-0.5">Spouse's Occupation:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.spouseOccupation"></div></div>
                    <div><label class="text-gray-600 block mb-0.5">Employer / Business Name:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.spouseEmployer"></div></div>
                    <div><label class="text-gray-600 block mb-0.5">Business Address:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.spouseBusinessAddress"></div></div>
                    <div><label class="text-gray-600 block mb-0.5">Telephone Number:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.spouseTelNo"></div></div>
                </div>

                <div class="px-4 mb-3">
                    <div class="font-bold mb-1">Children</div>
                    <div class="border border-blue-200 rounded overflow-hidden">
                        <div class="grid grid-cols-3 bg-blue-50 text-blue-900 font-bold p-1 text-center divide-x divide-blue-200 border-b border-blue-200 bg-opacity-50">
                            <div>Full Name</div><div>Gender</div><div>Date of Birth</div>
                        </div>
                        <template x-for="(child, idx) in pdsForm.children" :key="'c'+idx">
                            <div class="grid grid-cols-3 p-1 gap-1 border-b border-blue-100 last:border-b-0">
                                <div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white text-gray-500"><span x-show="!child.name" class="text-gray-300">Full Name</span><span x-show="child.name" x-text="child.name" class="text-black"></span></div>
                                <div class="border border-gray-300 rounded h-7 px-2 flex items-center justify-between bg-white text-gray-500"><span x-show="!child.gender" class="text-gray-300">Select</span><span x-show="child.gender" x-text="child.gender" class="text-black"></span><svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg></div>
                                <div class="border border-gray-300 rounded h-7 px-2 flex items-center justify-between bg-white text-gray-500"><span x-show="!child.dob" class="text-gray-300">dd/mm/yyyy</span><span x-show="child.dob" x-text="child.dob" class="text-black"></span><svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg></div>
                            </div>
                        </template>
                    </div>
                </div>
                
                <div class="px-4 mb-2 grid grid-cols-4 gap-2">
                    <div><label class="text-gray-600 block mb-0.5">Father's Surname:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.fatherSurname"></div></div>
                    <div><label class="text-gray-600 block mb-0.5">Father's First Name:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.fatherFirstName"></div></div>
                    <div><label class="text-gray-600 block mb-0.5">Father's Middle Name:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.fatherMiddleName"></div></div>
                    <div><label class="text-gray-600 block mb-0.5">Father's Name Extension:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.fatherNameExt"></div></div>
                </div>
                <div class="px-4 mb-2 grid grid-cols-4 gap-2">
                    <div class="col-span-2"><label class="text-gray-600 block mb-0.5">Mother's Maiden Surname:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.motherMaidenSurname"></div></div>
                    <div><label class="text-gray-600 block mb-0.5">Mother's First Name:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.motherFirstName"></div></div>
                    <div><label class="text-gray-600 block mb-0.5">Mother's Middle Name:</label><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.motherMiddleName"></div></div>
                </div>
            </div>

            <!-- PAGE 3 equivalent -->
            <div class="border border-gray-200 rounded-lg mb-4 text-[10px] pb-2 mt-4" style="page-break-before: always;">
                <div class="px-4 py-1.5 mb-2 -mt-2 bg-white inline-block text-blue-800 font-bold uppercase tracking-wider text-xs ml-4">III. EDUCATIONAL BACKGROUND</div>
                
                <div class="px-4 mb-3">
                    <div class="font-bold mb-1 mt-1">A. Formal Education</div>
                    <div class="border border-blue-200 rounded overflow-hidden">
                        <div class="grid grid-cols-5 bg-blue-50 text-blue-900 font-bold p-1 text-center divide-x divide-blue-200 border-b border-blue-200 bg-opacity-50 text-[9px] uppercase">
                            <div>Level</div><div class="col-span-2">School / Basic Education/Degree/Course</div><div>From</div><div>To</div>
                        </div>
                        <!-- Elem -->
                        <div class="grid grid-cols-5 p-1 gap-1 border-b border-blue-100 items-center">
                            <div class="uppercase text-gray-500 font-semibold px-2">Elementary</div>
                            <div class="col-span-2"><div class="border border-gray-300 rounded h-7 px-2 w-full flex items-center bg-white" x-text="pdsForm.educElemSchool + (pdsForm.educElemDegree ? ' - ' + pdsForm.educElemDegree : '')"></div></div>
                            <div><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.educElemFrom"></div></div>
                            <div><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.educElemTo"></div></div>
                        </div>
                        <!-- Sec -->
                        <div class="grid grid-cols-5 p-1 gap-1 border-b border-blue-100 items-center">
                            <div class="uppercase text-gray-500 font-semibold px-2">Secondary</div>
                            <div class="col-span-2"><div class="border border-gray-300 rounded h-7 px-2 w-full flex items-center bg-white" x-text="pdsForm.educSecSchool + (pdsForm.educSecDegree ? ' - ' + pdsForm.educSecDegree : '')"></div></div>
                            <div><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.educSecFrom"></div></div>
                            <div><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.educSecTo"></div></div>
                        </div>
                        <!-- College -->
                        <div class="grid grid-cols-5 p-1 gap-1 border-b border-blue-100 items-center">
                            <div class="uppercase text-gray-500 font-semibold px-2">College</div>
                            <div class="col-span-2"><div class="border border-gray-300 rounded h-7 px-2 w-full flex items-center bg-white" x-text="pdsForm.educCollSchool + (pdsForm.educCollDegree ? ' - ' + pdsForm.educCollDegree : '')"></div></div>
                            <div><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.educCollFrom"></div></div>
                            <div><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.educCollTo"></div></div>
                        </div>
                        <!-- Masters -->
                        <div class="grid grid-cols-5 p-1 gap-1 border-b border-blue-100 items-center">
                            <div class="uppercase text-gray-500 font-semibold px-2">Masters</div>
                            <div class="col-span-2"><div class="border border-gray-300 rounded h-7 px-2 w-full flex items-center bg-white" x-text="pdsForm.educMastSchool + (pdsForm.educMastDegree ? ' - ' + pdsForm.educMastDegree : '')"></div></div>
                            <div><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.educMastFrom"></div></div>
                            <div><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.educMastTo"></div></div>
                        </div>
                        <!-- Doct -->
                        <div class="grid grid-cols-5 p-1 gap-1 items-center">
                            <div class="uppercase text-gray-500 font-semibold px-2">Doctorate</div>
                            <div class="col-span-2"><div class="border border-gray-300 rounded h-7 px-2 w-full flex items-center bg-white" x-text="pdsForm.educDoctSchool + (pdsForm.educDoctDegree ? ' - ' + pdsForm.educDoctDegree : '')"></div></div>
                            <div><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.educDoctFrom"></div></div>
                            <div><div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white" x-text="pdsForm.educDoctTo"></div></div>
                        </div>
                    </div>
                </div>
                
                <div class="px-4 mb-2">
                    <div class="font-bold mb-1">B. Learning &amp; Development</div>
                    <div class="border border-blue-200 rounded overflow-hidden">
                        <div class="grid grid-cols-4 bg-blue-50 text-blue-900 font-bold p-1 text-center divide-x divide-blue-200 border-b border-blue-200 bg-opacity-50 text-[9px]">
                            <div>Training / Seminar / Workshop Title</div><div>Conducted By</div><div>Date / Period</div><div>Certificate Received</div>
                        </div>
                        <template x-for="(l, idx) in pdsForm.lnd" :key="'l'+idx">
                            <div class="grid grid-cols-4 p-1 gap-1 border-b border-blue-100 last:border-b-0">
                                <div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white text-gray-500"><span x-show="!l.title" class="text-gray-300">Title</span><span x-show="l.title" x-text="l.title" class="text-black"></span></div>
                                <div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white text-gray-500"><span x-show="!l.conductedBy" class="text-gray-300">Conducted By</span><span x-show="l.conductedBy" x-text="l.conductedBy" class="text-black"></span></div>
                                <div class="border border-gray-300 rounded h-7 px-2 flex items-center bg-white text-gray-500"><span x-show="!l.date" class="text-gray-300">Date</span><span x-show="l.date" x-text="l.date" class="text-black"></span></div>
                                <div class="border border-gray-300 rounded h-7 px-2 flex items-center justify-between bg-white text-gray-500"><span x-show="!l.cert" class="text-gray-300">Select</span><span x-show="l.cert" x-text="l.cert" class="text-black"></span><svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="border border-gray-200 rounded-lg mb-4 text-[10px] pb-4 mt-4 bg-gray-50 px-4 pt-3">
                <div class="text-blue-800 font-bold uppercase tracking-wider text-xs mb-2">IV. DECLARATION AND DATA PRIVACY CONSENT</div>
                <p class="mb-2 text-gray-600">I certify that the information provided herein is true, complete, and accurate to the best of my knowledge.</p>
                <p class="mb-3 text-gray-600">I understand and consent that pursuant to Republic Act No. 10173 (Data Privacy Act of 2012), the information provided in this Personal Data Sheet will be collected, processed, and retained by John Kelly & Company and its authorized representatives for lawful employment and compliance purposes.</p>
                
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 border border-gray-400 rounded-sm flex items-center justify-center bg-white">
                        <svg x-show="pdsForm.consent" class="w-2 h-2 text-black" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"></path></svg>
                    </div>
                    <span class="text-gray-700">I have read and agree to the Data Privacy Consent above. <span class="text-red-500">*</span></span>
                </div>
            </div>

        </div>{{-- end pds-doc-preview --}}
    </div>
</div>{{-- end live preview --}}


            {{-- end split body --}}
        </div>
    </div>
</div>{{-- end PDS outer overlay --}}

    
{{-- ===================== CHECKLIST SUBMISSION SLIDE-OVER ===================== --}}
<div
    x-show="showChecklistModal"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex justify-end bg-black/50 backdrop-blur-sm"
    style="display:none;"
    @click.self="showChecklistModal = false"
>
    <div
        x-show="showChecklistModal"
        x-transition:enter="transform transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="bg-white shadow-2xl w-full max-w-3xl h-full flex flex-col overflow-hidden"
    >
        <div class="flex items-center justify-between px-6 py-3 bg-blue-700 shrink-0">
            <h2 class="text-sm font-bold text-white uppercase tracking-widest">Checklist Submission</h2>
            <button @click="showChecklistModal = false" class="text-blue-200 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form @submit.prevent="submitChecklist()" class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Employee Name from Submitted PDS <span class="text-red-500">*</span></label>
                    <select x-model="checklistForm.pdsId" @change="onChecklistPdsChange()" required
                        class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none bg-white">
                        <option value="">Select submitted PDS...</option>
                        <template x-for="pds in pdsApplicantsForChecklist" :key="pds.id">
                            <option :value="pds.id" x-text="`${pds.fullName || 'Unnamed'} - ${pds.position || 'No position'} (${pds.email || 'No email'})`"></option>
                        </template>
                    </select>
                    <p class="text-[11px] text-gray-500 mt-1">Only applicants with submitted PDS records appear here.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Position</label>
                    <input type="text" x-model="checklistForm.position" readonly class="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-gray-700 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Email</label>
                    <input type="text" x-model="checklistForm.employeeEmail" readonly class="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-gray-700 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-3">Upload Required Documents</label>
                <div class="grid grid-cols-1 gap-3">
                    <template x-for="doc in checklistDocs" :key="doc.key">
                        <div class="border border-gray-200 rounded-xl p-4 bg-gray-50 hover:bg-blue-50/40 transition">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-gray-800" x-text="doc.label"></p>
                                    <p class="text-[11px] text-gray-500">PDF, JPG, PNG, DOC, or DOCX. Max 10MB.</p>
                                    <p x-show="checklistForm.files[doc.key]" class="text-[11px] text-blue-600 font-semibold mt-1" x-text="checklistForm.files[doc.key]?.name"></p>
                                </div>
                                <label class="px-3 py-2 bg-white border border-blue-200 text-blue-700 text-xs font-bold rounded-lg cursor-pointer hover:bg-blue-50 transition">
                                    Upload
                                    <input type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" @change="handleChecklistFile($event, doc.key)">
                                </label>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="pt-4 border-t flex justify-end gap-3 pb-2">
                <button type="button" @click="showChecklistModal = false"
                    class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancel</button>
                <button type="submit"
                    class="px-5 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium shadow-sm transition">Save Checklist</button>
            </div>
        </form>
    </div>
</div>

{{-- ===================== CHECKLIST REVIEW SLIDE-OVER ===================== --}}
<div
    x-show="showChecklistViewModal"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[60] flex justify-end bg-black/50 backdrop-blur-sm"
    style="display:none;"
    @click.self="showChecklistViewModal = false"
>
    <div
        x-show="showChecklistViewModal"
        x-transition:enter="transform transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="bg-white shadow-2xl w-full max-w-4xl h-full flex flex-col overflow-hidden"
    >
        <div class="flex items-center justify-between px-6 py-3 bg-indigo-700 shrink-0">
            <h2 class="text-sm font-bold text-white uppercase tracking-widest">Review Checklist Documents</h2>
            <button @click="showChecklistViewModal = false" class="text-indigo-200 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <template x-if="viewChecklistData">
            <div class="flex-1 overflow-y-auto p-6 space-y-5">
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Employee</p>
                        <p class="text-sm font-bold text-gray-800" x-text="viewChecklistData.employeeName"></p>
                    </div>
                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Position</p>
                        <p class="text-sm font-bold text-gray-800" x-text="viewChecklistData.position || '—'"></p>
                    </div>
                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Status</p>
                        <span :class="checklistStatusClass(viewChecklistData.status)" class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-bold" x-text="viewChecklistData.status"></span>
                    </div>
                </div>

                <div class="space-y-3">
                    <template x-for="doc in viewChecklistData.documents" :key="doc.key">
                        <div class="border border-gray-200 rounded-xl p-4 bg-white shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="font-bold text-gray-900 text-sm" x-text="doc.label"></p>
                                    <p class="text-xs text-gray-500 mt-1" x-text="doc.original_name || 'No file uploaded'"></p>
                                    <p x-show="doc.remarks" class="text-xs text-red-600 mt-2 font-semibold" x-text="'Remarks: ' + doc.remarks"></p>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <a x-show="doc.file_url" :href="doc.file_url" target="_blank"
                                       class="px-3 py-1.5 text-xs font-bold rounded-lg border border-blue-200 text-blue-700 hover:bg-blue-50 transition">
                                        View File
                                    </a>
                                    <span :class="documentStatusClass(doc.status)" class="px-2 py-1 rounded-full text-[11px] font-bold" x-text="doc.status"></span>
                                </div>
                            </div>

                            {{-- Review actions --}}
                            <div class="mt-4">
                                {{-- Missing: HR cannot approve/reject because applicant has not uploaded anything yet --}}
                                <div x-show="doc.status === 'Missing'" class="flex items-center justify-between gap-3 rounded-lg border border-yellow-200 bg-yellow-50 px-3 py-2">
                                    <p class="text-xs font-semibold text-yellow-700">
                                        Waiting for applicant to upload this document.
                                    </p>
                                    <span class="text-[11px] font-bold text-yellow-700 bg-yellow-100 px-2 py-1 rounded-full">
                                        No action available
                                    </span>
                                </div>

                                {{-- Approved: no action needed --}}
                                <div x-show="doc.status === 'Approved'" class="flex items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-3 py-2">
                                    <p class="text-xs font-semibold text-green-700">
                                        This document has already been approved.
                                    </p>
                                    <span class="text-[11px] font-bold text-green-700 bg-green-100 px-2 py-1 rounded-full">
                                        Approved
                                    </span>
                                </div>

                                {{-- Needs Re-upload: applicant must replace/correct the file from the checklist link --}}
                                <div x-show="doc.status === 'Needs Re-upload'" class="flex items-center justify-between gap-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2">
                                    <div>
                                        <p class="text-xs font-semibold text-red-700">
                                            Waiting for applicant to upload a corrected document.
                                        </p>
                                        <p x-show="doc.remarks" class="text-[11px] text-red-600 mt-1" x-text="'Remarks: ' + doc.remarks"></p>
                                    </div>
                                    <span class="text-[11px] font-bold text-red-700 bg-red-100 px-2 py-1 rounded-full">
                                        Needs Re-upload
                                    </span>
                                </div>

                                {{-- Submitted: HR can approve or request re-upload --}}
                                <div x-show="doc.status === 'Submitted'" class="grid grid-cols-[1fr_auto_auto] gap-2 items-center">
                                    <input type="text" :value="doc.remarks || ''" @input="doc.reviewRemarks = $event.target.value" placeholder="Remarks if needs re-upload..."
                                        class="w-full text-xs px-3 py-2 border border-gray-200 rounded-lg outline-none focus:border-blue-400">
                                    <button type="button" @click="reviewChecklistDocument(doc, 'Approved')"
                                        class="px-3 py-2 text-xs font-bold bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                        Approve
                                    </button>
                                    <button type="button" @click="reviewChecklistDocument(doc, 'Needs Re-upload')"
                                        class="px-3 py-2 text-xs font-bold bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                                        Re-upload
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
</div>

{{-- ===================== EMPLOYEE REGISTRATION SLIDE-OVER ===================== --}}
    <div
        x-show="showEmpRegModal"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex justify-end bg-black/50 backdrop-blur-sm"
        style="display:none;"
        @click.self="showEmpRegModal = false"
    >
        <div
            x-show="showEmpRegModal"
            x-transition:enter="transform transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="bg-white shadow-2xl w-full max-w-xl h-full flex flex-col overflow-hidden"
        >
            <div class="flex items-center justify-between px-6 py-3 bg-blue-700 shrink-0">
                <h2 class="text-sm font-bold text-white uppercase tracking-widest">New Employee Registration</h2>
                <button @click="showEmpRegModal = false" class="text-blue-200 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form @submit.prevent="submitEmpReg()" class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                
<div class="grid grid-cols-2 gap-4">
    <div class="col-span-2">
        <label class="block text-xs font-semibold text-gray-600 mb-1">
            Applicant with Completed Checklist <span class="text-red-500">*</span>
        </label>
        <select x-model="empRegForm.checklistId" @change="onEmpRegChecklistChange()" required
            class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none bg-white">
            <option value="">Select completed checklist...</option>
            <template x-for="checklist in completedChecklistsForRegistration" :key="checklist.id">
                <option :value="checklist.id" x-text="`${checklist.employeeName} - ${checklist.position || 'No position'}`"></option>
            </template>
        </select>
        <p class="text-[11px] text-gray-500 mt-1">
            Only applicants with Completed checklist and not yet registered will appear here.
        </p>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Full Name <span class="text-red-500">*</span></label>
        <input type="text" x-model="empRegForm.fullName" required readonly
            class="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-gray-700 outline-none">
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Employee ID <span class="ml-1 text-[10px] font-normal text-blue-500">Auto-generated</span></label>
        <input type="text" x-model="empRegForm.employeeId" readonly
            class="w-full text-sm px-3 py-2 border border-blue-200 rounded-lg bg-blue-50 text-blue-700 font-mono font-semibold outline-none cursor-default tracking-wide">
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Position</label>
        <input type="text" x-model="empRegForm.position" readonly
            class="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-gray-700 outline-none">
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Personal Email</label>
        <input type="email" x-model="empRegForm.personalEmail" readonly
            class="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-gray-700 outline-none">
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Department</label>
        <input type="text" x-model="empRegForm.department" placeholder="e.g. Human Capital Department"
            class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Start Date</label>
        <input type="date" x-model="empRegForm.startDate"
            class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
    </div>

    <div class="col-span-2">
        <label class="block text-xs font-semibold text-gray-600 mb-1">Work Email</label>
        <input type="email" x-model="empRegForm.workEmail" placeholder="employee@company.com"
            class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Salary from Job Offer</label>
        <input type="text" x-model="empRegForm.salary" readonly
            class="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-gray-700 outline-none">
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Employment Type from Job Offer</label>
        <input type="text" x-model="empRegForm.employmentType" readonly
            class="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-gray-700 outline-none">
    </div>

    <div class="col-span-2">
        <label class="block text-xs font-semibold text-gray-600 mb-1">Reporting Manager</label>
        <input type="text" x-model="empRegForm.manager"
            class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
    </div>
</div>
<div class="pt-4 border-t flex justify-end gap-3 pb-2">
                    <button type="button" @click="showEmpRegModal = false"
                        class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit"
                        class="px-5 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium shadow-sm transition">Register Employee</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== TRAINING SLIDE-OVER ===================== --}}
    <div
        x-show="showTrainingModal"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex justify-end bg-black/50 backdrop-blur-sm"
        style="display:none;"
        @click.self="showTrainingModal = false"
    >
        <div
            x-show="showTrainingModal"
            x-transition:enter="transform transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="bg-white shadow-2xl w-full max-w-xl h-full flex flex-col overflow-hidden"
        >
            <div class="flex items-center justify-between px-6 py-3 bg-blue-700 shrink-0">
                <h2 class="text-sm font-bold text-white uppercase tracking-widest">New Training Assignment</h2>
                <button @click="showTrainingModal = false" class="text-blue-200 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form @submit.prevent="submitTraining()" class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Employee Name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="trainingForm.employeeName" required
                            class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Training Program <span class="text-red-500">*</span></label>
                        <select x-model="trainingForm.trainingId" required
                            class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none bg-white">
                            <option value="">-- Select Training Program --</option>
                            <template x-for="training in trainings" :key="training.id">
                                <option :value="training.id" x-text="training.title"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Start Date</label>
                        <input type="date" x-model="trainingForm.startDate"
                            class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Due Date</label>
                        <input type="date" x-model="trainingForm.dueDate"
                            class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Trainer / Instructor</label>
                    <input type="text" x-model="trainingForm.trainer"
                        class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Training Description</label>
                    <textarea x-model="trainingForm.description" rows="5" placeholder="Enter training details..."
                        class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none resize-none bg-gray-50"></textarea>
                </div>
                <div class="pt-4 border-t flex justify-end gap-3 pb-2">
                    <button type="button" @click="showTrainingModal = false"
                        class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit"
                        class="px-5 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium shadow-sm transition">Assign Training</button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
function onboardingPage() {
    return {
        activeTab: 'PDS',
        search: '',

        showPdsModal: false,
        showChecklistModal: false,
        showChecklistViewModal: false,
        showEmpRegModal: false,
        showTrainingModal: false,
        viewChecklistData: null,

        tabs: [
            { key: 'PDS', label: 'PDS' },
            { key: 'Checklist Submission', label: 'Checklist Submission' },
            { key: 'Employee Registration', label: 'Employee Registration' },
            { key: 'Training', label: 'Training' },
        ],

        trainings: [],

        data: {
            'PDS': @json($pdsData ?? []),
            'Checklist Submission': [],
            'Employee Registration': [],
            'Training': [],
        },

        checklistDocs: [
            { key: 'valid_id', label: 'Valid ID (Government-issued)' },
            { key: 'birth_certificate', label: 'Birth Certificate' },
            { key: 'sss', label: 'SSS Number / E-1 / Static Info' },
            { key: 'philhealth', label: 'PhilHealth MDR' },
            { key: 'pagibig', label: 'Pag-IBIG MDF / MID' },
            { key: 'tin_bir', label: 'TIN / BIR Form' },
            { key: 'nbi_clearance', label: 'NBI Clearance' },
            { key: 'medical_certificate', label: 'Medical Certificate' },
            { key: 'tor_diploma', label: 'Transcript of Records / Diploma' },
            { key: 'signed_job_offer', label: 'Signed Job Offer' },
            { key: 'two_by_two_picture', label: '2x2 Picture' },
        ],

        pdsForm: {},

        checklistForm: {
            pdsId: '',
            employeeName: '',
            employeeEmail: '',
            position: '',
            files: {},
        },

        empRegForm: {
            checklistId: '',
            fullName: '',
            employeeId: '',
            department: '',
            position: '',
            personalEmail: '',
            salary: '',
            employmentType: '',
            startDate: '',
            workEmail: '',
            manager: '',
        },

        trainingForm: {
            employeeName: '',
            trainingId: '',
            startDate: '',
            dueDate: '',
            trainer: '',
            description: '',
        },

        init() {
            this.loadLocalOnboardingData();
            this.resetPdsForm();
            this.fetchTrainingPrograms();
        },

        loadLocalOnboardingData() {
            this.fetchOnboardingRecords();
        },

        async fetchTrainingPrograms() {
            try {
                const response = await fetch('{{ route("human-capital.training") }}', {
                    headers: {
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch training programs');
                }

                this.trainings = await response.json();
            } catch (error) {
                console.error('Error fetching training programs:', error);
            }
        },



        saveLocalOnboardingData() {
            // Database saving is now handled by Laravel routes.
            // No localStorage needed anymore.
        },

        async fetchOnboardingRecords() {
            try {
                const response = await fetch('{{ route("human-capital.onboarding.records") }}', {
                    headers: {
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error('Unable to load onboarding records.');
                }

                const result = await response.json();

                this.data['PDS'] = result.pdsApplicants ?? this.data['PDS'];
                this.data['Checklist Submission'] = result.checklists ?? [];
                this.data['Employee Registration'] = result.employees ?? [];
                this.data['Training'] = result.trainings ?? [];
            } catch (error) {
                console.error(error);
                alert('Unable to load onboarding records from database. Please check your routes/controller/migrations.');
            }
        },

        closeAllModals() {
            this.showPdsModal = false;
            this.showChecklistModal = false;
            this.showEmpRegModal = false;
            this.showTrainingModal = false;
        },

        get filteredRows() {
            const rows = this.data[this.activeTab] ?? [];

            if (!this.search.trim()) {
                return rows;
            }

            const q = this.search.toLowerCase();

            return rows.filter(row =>
                Object.values(row).some(value =>
                    String(value ?? '').toLowerCase().includes(q)
                )
            );
        },

        openModal() {
            this.closeAllModals();

            const tab = String(this.activeTab).trim();

            if (tab === 'PDS') {
                this.resetPdsForm();
                this.showPdsModal = true;
                return;
            }

            if (tab === 'Checklist Submission') {
                this.openChecklistModal();
                return;
            }

            if (tab === 'Employee Registration') {
                this.openEmpRegModal();
                return;
            }

            if (tab === 'Training') {
                this.trainingForm = {
                    employeeName: '',
                    program: '',
                    startDate: '',
                    dueDate: '',
                    trainer: '',
                    description: '',
                };
                this.showTrainingModal = true;
                return;
            }

            alert('No form found for this tab.');
        },

        resetPdsForm() {
            this.pdsForm = {
                fullName: '',
                position: '',
                email: '',
                phone: '',

                surname: '',
                firstName: '',
                middleName: '',
                nameExt: '',

                dob: '',
                pob: '',
                citizenship: '',
                sex: '',
                civilStatus: '',
                height: '',
                weight: '',
                bloodType: '',

                sss: '',
                philhealth: '',
                pagibig: '',
                tin: '',

                resHouse: '',
                resStreet: '',
                resSubdiv: '',
                resBrgy: '',
                resCity: '',
                resProv: '',
                resZip: '',

                permSameAsRes: false,
                permHouse: '',
                permStreet: '',
                permSubdiv: '',
                permBrgy: '',
                permCity: '',
                permProv: '',
                permZip: '',

                telNo: '',
                mobileNo: '',

                spouseSurname: '',
                spouseFirstName: '',
                spouseMiddleName: '',
                spouseNameExt: '',
                spouseOccupation: '',
                spouseEmployer: '',
                spouseBusinessAddress: '',
                spouseTelNo: '',

                children: [
                    { name: '', gender: '', dob: '' },
                    { name: '', gender: '', dob: '' },
                    { name: '', gender: '', dob: '' },
                ],

                fatherSurname: '',
                fatherFirstName: '',
                fatherMiddleName: '',
                fatherNameExt: '',

                motherMaidenSurname: '',
                motherFirstName: '',
                motherMiddleName: '',

                educElemSchool: '',
                educElemDegree: '',
                educElemFrom: '',
                educElemTo: '',

                educSecSchool: '',
                educSecDegree: '',
                educSecFrom: '',
                educSecTo: '',

                educCollSchool: '',
                educCollDegree: '',
                educCollFrom: '',
                educCollTo: '',

                educMastSchool: '',
                educMastDegree: '',
                educMastFrom: '',
                educMastTo: '',

                educDoctSchool: '',
                educDoctDegree: '',
                educDoctFrom: '',
                educDoctTo: '',

                lnd: [
                    { title: '', conductedBy: '', date: '', cert: '' },
                    { title: '', conductedBy: '', date: '', cert: '' },
                    { title: '', conductedBy: '', date: '', cert: '' },
                ],

                consent: false,
                signaturePreview: '',
                signatureName: '',
                submittedDate: '',
            };
        },

        handleSignature(event) {
            const file = event.target.files[0];

            if (!file) {
                return;
            }

            this.pdsForm.signatureName = file.name;

            const reader = new FileReader();

            reader.onload = (e) => {
                this.pdsForm.signaturePreview = e.target.result;
            };

            reader.readAsDataURL(file);
        },

        submitPds() {
            this.pdsForm.fullName = `${this.pdsForm.surname || ''}, ${this.pdsForm.firstName || ''}`.trim();
            this.pdsForm.phone = this.pdsForm.mobileNo || this.pdsForm.phone;
            this.pdsForm.submittedDate = new Date().toLocaleDateString('en-PH');

            fetch('{{ route("careers.pds.submit") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(this.pdsForm)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to save PDS.');
                }

                window.location.reload();
            })
            .catch(error => {
                console.error(error);
                alert('PDS was not saved. Please check your route/controller.');
            });
        },

        async deletePds(id) {
    if (!id) {
        alert('This PDS record has no database ID.');
        return;
    }

    if (!confirm('Delete this PDS record permanently?')) {
        return;
    }

    try {
        const response = await fetch(`/human-capital/onboarding/pds/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        });

        if (!response.ok) {
            throw new Error('Failed to delete PDS.');
        }

        this.data['PDS'] = this.data['PDS'].filter(item => item.db_id !== id);
    } catch (error) {
        console.error(error);
        alert('PDS was not deleted from database.');
    }
},


get pdsApplicantsForChecklist() {
    const usedPdsIds = (this.data['Checklist Submission'] || [])
        .map(item => String(item.pdsId || ''))
        .filter(Boolean);

    return (this.data['PDS'] || []).filter(pds => {
        const status = String(pds.status || '').toLowerCase();

        return pds.id && !usedPdsIds.includes(String(pds.id)) && !status.includes('checklist completed');
    });
},

openChecklistModal() {
    this.closeAllModals();

    this.checklistForm = {
        pdsId: '',
        employeeName: '',
        employeeEmail: '',
        position: '',
        files: {},
    };

    this.showChecklistModal = true;
},

onChecklistPdsChange() {
    const selected = (this.data['PDS'] || []).find(pds => String(pds.id) === String(this.checklistForm.pdsId));

    if (!selected) {
        this.checklistForm.employeeName = '';
        this.checklistForm.employeeEmail = '';
        this.checklistForm.position = '';
        return;
    }

    this.checklistForm.employeeName = selected.fullName || '';
    this.checklistForm.employeeEmail = selected.email || '';
    this.checklistForm.position = selected.position || '';
},

handleChecklistFile(event, key) {
    const file = event.target.files[0];

    if (!file) {
        return;
    }

    this.checklistForm.files = {
        ...this.checklistForm.files,
        [key]: file,
    };
},

viewChecklist(row) {
    this.viewChecklistData = JSON.parse(JSON.stringify(row));
    this.showChecklistViewModal = true;
},

checklistStatusClass(status) {
    const map = {
        'Completed': 'bg-green-100 text-green-700',
        'For Review': 'bg-blue-100 text-blue-700',
        'Needs Re-upload': 'bg-red-100 text-red-700',
        'Pending Documents': 'bg-yellow-100 text-yellow-700',
    };

    return map[status] || 'bg-gray-100 text-gray-700';
},

documentStatusClass(status) {
    const map = {
        'Approved': 'bg-green-100 text-green-700',
        'Submitted': 'bg-blue-100 text-blue-700',
        'Needs Re-upload': 'bg-red-100 text-red-700',
        'Missing': 'bg-yellow-100 text-yellow-700',
    };

    return map[status] || 'bg-gray-100 text-gray-700';
},

async reviewChecklistDocument(doc, status) {
    if (!this.viewChecklistData || !this.viewChecklistData.id) {
        alert('Checklist record was not found.');
        return;
    }

    if (status === 'Needs Re-upload' && !String(doc.reviewRemarks || doc.remarks || '').trim()) {
        alert('Please add remarks so the applicant/employee knows which document to re-upload.');
        return;
    }

    try {
        const response = await fetch(`/human-capital/onboarding/checklists/${this.viewChecklistData.id}/review-document`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                documentKey: doc.key,
                status: status,
                remarks: doc.reviewRemarks || doc.remarks || '',
            })
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error(errorText);
            throw new Error('Failed to update document review.');
        }

        const result = await response.json();

        const idx = this.data['Checklist Submission'].findIndex(item => item.id === result.record.id);

        if (idx !== -1) {
            this.data['Checklist Submission'][idx] = result.record;
        }

        this.viewChecklistData = JSON.parse(JSON.stringify(result.record));
        alert(result.message || 'Document review updated.');
    } catch (error) {
        console.error(error);
        alert('Document review was not updated.');
    }
},

        async submitChecklist() {
            if (!this.checklistForm.pdsId) {
                alert('Please select an employee from submitted PDS.');
                return;
            }

            const formData = new FormData();
            formData.append('pdsId', this.checklistForm.pdsId);
            formData.append('employeeName', this.checklistForm.employeeName || '');
            formData.append('employeeEmail', this.checklistForm.employeeEmail || '');
            formData.append('position', this.checklistForm.position || '');

            Object.entries(this.checklistForm.files || {}).forEach(([key, file]) => {
                if (file) {
                    formData.append(`documents[${key}]`, file);
                }
            });

            try {
                const response = await fetch('{{ route("human-capital.onboarding.checklists.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error(errorText);
                    throw new Error('Failed to save checklist.');
                }

                const result = await response.json();

                this.data['Checklist Submission'].unshift(result.record);
                await this.fetchOnboardingRecords();
                this.showChecklistModal = false;
            } catch (error) {
                console.error(error);
                alert('Checklist was not saved. Please check controller, routes, and migration.');
            }
        },

        async deleteChecklist(index) {
            const row = this.filteredRows[index];

            if (!row || !row.id) {
                alert('Unable to delete this checklist record. Missing database ID.');
                return;
            }

            if (!confirm('Delete this checklist record?')) {
                return;
            }

            try {
                const response = await fetch(`/human-capital/onboarding/checklists/${row.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error(errorText);
                    throw new Error('Failed to delete checklist.');
                }

                this.data['Checklist Submission'] = this.data['Checklist Submission'].filter(item => item.id !== row.id);
            } catch (error) {
                console.error(error);
                alert('Checklist was not deleted.');
            }
        },


get completedChecklistsForRegistration() {
    const registeredChecklistIds = (this.data['Employee Registration'] || [])
        .map(item => String(item.checklistId || ''))
        .filter(Boolean);

    return (this.data['Checklist Submission'] || []).filter(item => {
        return item.status === 'Completed'
            && item.id
            && !registeredChecklistIds.includes(String(item.id));
    });
},

openEmpRegModal() {
    this.closeAllModals();

    if (this.completedChecklistsForRegistration.length === 0) {
        alert('No applicant is ready for Employee Registration yet. Checklist must be Completed first.');
        return;
    }

    this.empRegForm = {
        checklistId: '',
        fullName: '',
        employeeId: this.generateEmployeeId(),
        department: '',
        position: '',
        personalEmail: '',
        startDate: '',
        workEmail: '',
        manager: '',
    };

    this.showEmpRegModal = true;
},

onEmpRegChecklistChange() {
    const selected = (this.data['Checklist Submission'] || []).find(item =>
        String(item.id) === String(this.empRegForm.checklistId)
    );

    if (!selected) {
        this.empRegForm.fullName = '';
        this.empRegForm.position = '';
        this.empRegForm.personalEmail = '';
        return;
    }

    this.empRegForm.fullName = selected.employeeName || '';
    this.empRegForm.position = selected.position || '';
    this.empRegForm.personalEmail = selected.employeeEmail || '';
    this.empRegForm.workEmail = selected.employeeEmail || '';
    this.empRegForm.department = selected.jobOfferDepartment || selected.position || this.empRegForm.department;

            this.empRegForm.startDate = selected.startDate || '';
            this.empRegForm.salary = selected.jobOfferSalary || '';
            this.empRegForm.employmentType = selected.jobOfferEmploymentType || '';},

        async submitEmpReg() {
            if (!this.empRegForm.checklistId) {
                alert('Please select an applicant with a Completed checklist.');
                return;
            }

            try {
                const response = await fetch('{{ route("human-capital.onboarding.employees.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.empRegForm)
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error(errorText);

                    let message = 'Employee registration was not saved.';
                    try {
                        const parsed = JSON.parse(errorText);
                        message = parsed.message || message;
                    } catch (e) {}

                    throw new Error(message);
                }

                const result = await response.json();

                this.data['Employee Registration'].unshift(result.record);
                await this.fetchOnboardingRecords();
                this.showEmpRegModal = false;
                alert(result.message || 'Employee registration saved successfully.');
            } catch (error) {
                console.error(error);
                alert(error.message || 'Employee registration was not saved. Check if Employee ID is already used, or check controller/routes/migration.');
            }
        },

        async deleteEmpReg(index) {
            const row = this.filteredRows[index];

            if (!row || !row.id) {
                alert('Unable to delete this employee registration. Missing database ID.');
                return;
            }

            if (!confirm('Delete this employee registration?')) {
                return;
            }

            try {
                const response = await fetch(`/human-capital/onboarding/employees/${row.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error(errorText);
                    throw new Error('Failed to delete employee registration.');
                }

                this.data['Employee Registration'] = this.data['Employee Registration'].filter(item => item.id !== row.id);
            } catch (error) {
                console.error(error);
                alert('Employee registration was not deleted.');
            }
        },

        async submitTraining() {
            try {
                const response = await fetch('{{ route("human-capital.onboarding.trainings.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.trainingForm)
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error(errorText);
                    throw new Error('Failed to save training.');
                }

                const result = await response.json();

                this.data['Training'].unshift(result.record);
                this.showTrainingModal = false;
            } catch (error) {
                console.error(error);
                alert('Training assignment was not saved. Please check controller, routes, and migration.');
            }
        },

        async deleteTraining(index) {
            const row = this.filteredRows[index];

            if (!row || !row.id) {
                alert('Unable to delete this training record. Missing database ID.');
                return;
            }

            if (!confirm('Delete this training record?')) {
                return;
            }

            try {
                const response = await fetch(`/human-capital/onboarding/trainings/${row.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error(errorText);
                    throw new Error('Failed to delete training.');
                }

                this.data['Training'] = this.data['Training'].filter(item => item.id !== row.id);
            } catch (error) {
                console.error(error);
                alert('Training assignment was not deleted.');
            }
        },

        generateEmployeeId() {
            const year = new Date().getFullYear();
            const existing = this.data['Employee Registration'] ?? [];
            const seq = existing.length + 1;

            return 'EMP-' + year + '-' + String(seq).padStart(3, '0');
        },

        downloadPdsPdf() {
            const el = document.getElementById('pds-doc-preview');

            if (!el) {
                alert('PDS preview not found.');
                return;
            }

            const name = this.pdsForm.fullName
                ? this.pdsForm.fullName.replace(/\s+/g, '_')
                : 'PDS';

            html2pdf().set({
                margin: 0,
                filename: `PDS_${name}.pdf`,
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2,
                    useCORS: true
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait'
                }
            }).from(el).save();
        },

        downloadCSV() {
            const tab = this.activeTab;
            const rows = this.data[tab] ?? [];

            if (rows.length === 0) {
                alert('No records to export for "' + tab + '".');
                return;
            }

            const csvHeaders = {
                'PDS': ['Full Name', 'Position', 'Email', 'Phone', 'Date Submitted'],
                'Checklist Submission': ['Employee Name', 'Docs Submitted', 'Total Docs', 'Checked Documents', 'Date Submitted'],
                'Employee Registration': ['Full Name', 'Employee ID', 'Department', 'Start Date', 'Work Email', 'Reporting Manager'],
                'Training': ['Employee Name', 'Training Program', 'Start Date', 'Due Date', 'Trainer', 'Description'],
            };

            const csvRows = {
                'PDS': rows.map(r => [
                    r.fullName,
                    r.position,
                    r.email,
                    r.phone,
                    r.submittedDate
                ]),

                'Checklist Submission': rows.map(r => [
                    r.employeeName,
                    r.docsSubmitted,
                    r.totalDocs,
                    (r.checked || []).map(k => this.checklistDocs.find(d => d.key === k)?.label || k).join('; '),
                    r.submittedDate
                ]),

                'Employee Registration': rows.map(r => [
                    r.fullName,
                    r.employeeId,
                    r.department,
                    r.startDate,
                    r.workEmail,
                    r.manager
                ]),

                'Training': rows.map(r => [
                    r.employeeName,
                    r.program,
                    r.startDate,
                    r.dueDate,
                    r.trainer,
                    r.description
                ]),
            };

            const escape = value => '"' + String(value ?? '').replace(/"/g, '""') + '"';

            const csvContent = [
                csvHeaders[tab].map(escape).join(','),
                ...csvRows[tab].map(row => row.map(escape).join(','))
            ].join('\n');

            const blob = new Blob([csvContent], {
                type: 'text/csv;charset=utf-8;'
            });

            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');

            a.href = url;
            a.download = tab.replace(/\s+/g, '_') + '_' + new Date().toISOString().slice(0, 10) + '.csv';
            a.click();

            URL.revokeObjectURL(url);
        },
    };
}
</script>
@endpush
@endsection
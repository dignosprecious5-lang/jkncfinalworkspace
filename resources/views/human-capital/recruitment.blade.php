@extends('layouts.app')

@section('content')
<div class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col" x-data="recruitmentPage(
    {{ $mrfData->toJson() }},
    {{ $jpfData->toJson() }},
    {{ $cafData->toJson() }},
    {{ $assessmentData->toJson() }},
    {{ $interviewData->toJson() }},
    {{ $jobOfferData->toJson() }},
    {{ $organizationalAddresses->toJson() }},
    {{ $branches->toJson() }},
    {{ $offices->toJson() }},
    {{ $departments->toJson() }},
    {{ $divisions->toJson() }},
    {{ $units->toJson() }},
    {{ $positions->toJson() }},
    {{ $salaryGrades->toJson() }},
    {{ $payrollLevels->toJson() }},
    {{ $approvalUsers->toJson() }}
)" x-init="startAssessmentPolling()">

    {{-- TABS --}}
    <div class="flex items-center border-b border-gray-200 mb-4 gap-1">
        <template x-for="tab in tabs" :key="tab.key">
            <button
                type="button"
                @click="activeTab = tab.key; if (tab.key === 'Job Offer') refreshJobOffers()"
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

        <select x-show="activeTab === 'CAF'" x-model="filterPosition" @change="currentPage = 1"
            class="px-3 py-2 text-sm border border-gray-200 rounded-lg bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-200">
            <option value="All">All Positions</option>
            <template x-for="position in uniqueCafPositions" :key="position">
                <option :value="position" x-text="position"></option>
            </template>
        </select>

        <div x-show="activeTab === 'JPF'" class="flex items-center gap-1 bg-white border border-gray-200 rounded-lg p-1 shadow-sm">
            <button
                type="button"
                @click="jpfListView = 'all'; currentPage = 1"
                class="px-3 py-1.5 rounded-md text-xs font-bold transition"
                :class="jpfListView === 'all' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'"
            >
                All JPF
            </button>
            <button
                type="button"
                @click="jpfListView = 'mine'; currentPage = 1"
                class="px-3 py-1.5 rounded-md text-xs font-bold transition flex items-center gap-2"
                :class="jpfListView === 'mine' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'"
            >
                My Pending Approvals
                <span class="inline-flex min-w-[18px] h-[18px] items-center justify-center rounded-full text-[10px]"
                    :class="jpfListView === 'mine' ? 'bg-white text-blue-700' : 'bg-blue-50 text-blue-700'"
                    x-text="myPendingJpfCount()"></span>
            </button>
        </div>
        <div class="relative" @click.away="showFilter = false">
            <button type="button" @click="showFilter = !showFilter" 
                class="flex items-center gap-2 px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-700 transition"
                :class="filterStatus !== 'All' ? 'border-blue-500 bg-blue-50' : ''">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M6 12h12M10 18h4"/></svg>
                Filter <span x-show="filterStatus !== 'All'" class="ml-1 text-blue-600 font-bold" x-text="'('+filterStatus+')'"></span>
            </button>
            
            <div x-show="showFilter" x-transition class="absolute left-0 mt-2 w-48 bg-white border border-gray-100 rounded-xl shadow-xl z-30 p-2">
                <p class="px-2 py-1.5 text-[10px] uppercase font-bold text-gray-400 tracking-wider">Filter by Status</p>
                <div class="space-y-1">
                    <template x-for="st in ['All', 'Pending', 'Open', 'Filled', 'Hold', 'Cancelled', 'Disapproved']">
                        <button @click="filterStatus = st; showFilter = false" 
                            class="w-full text-left px-3 py-2 rounded-lg text-sm transition font-medium"
                            :class="filterStatus === st ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'"
                            x-text="st"></button>
                    </template>
                </div>
                <div class="mt-2 pt-2 border-t border-gray-100">
                    <button @click="filterStatus = 'All'; showFilter = false" class="w-full text-center text-xs text-gray-400 hover:text-gray-600 font-medium">Clear all filters</button>
                </div>
            </div>
        </div>
        <button type="button" @click="downloadCSV()" x-show="activeTab !== 'Assessment'"
            class="flex items-center gap-2 px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-700 transition">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/></svg>
            Download CSV
        </button>
        <div x-show="activeTab === 'CAF'" class="flex items-center gap-1 bg-blue-50 border border-blue-200 rounded-lg p-0.5">
            <a href="{{ route('homepage.public') }}" target="_blank"
                class="flex items-center gap-2 px-4 py-2 text-sm text-blue-700 hover:bg-blue-100/50 rounded-md transition font-bold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                Open Careers Page
            </a>
            <div class="w-px h-6 bg-blue-200 mx-0.5"></div>
            <button type="button" 
                @click="navigator.clipboard.writeText('{{ route('homepage.public') }}'); linkCopied = true; setTimeout(() => linkCopied = false, 2000)"
                class="flex items-center gap-2 px-4 py-2 text-sm text-blue-700 hover:bg-blue-100/50 rounded-md transition font-bold relative">
                <svg x-show="!linkCopied" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                <svg x-show="linkCopied" class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                <span x-text="linkCopied ? 'Copied!' : 'Copy Link'"></span>
            </button>
        </div>
        <button type="button" @click="openModal()"
            class="flex items-center gap-2 px-5 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-medium shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Add New
        </button>
    </div>

    {{-- TABLE --}}
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0 overflow-hidden">
        <div class="overflow-auto flex-grow">

            {{-- MANPOWER REQUEST FORM TABLE --}}
            <table class="w-full text-sm border-collapse" x-show="activeTab === 'MRF'">
                <thead class="bg-white text-gray-600 sticky top-0 z-10">
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-3 text-left font-semibold">Request ID</th>
                        <th class="px-4 py-3 text-left font-semibold">Position</th>
                        <th class="px-4 py-3 text-left font-semibold">Department</th>
                        <th class="px-4 py-3 text-left font-semibold">Headcount</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-left font-semibold">Date</th>
                        <th class="px-4 py-3 text-left font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="filteredRows.length === 0">
                        <tr><td colspan="7" class="px-4 py-16 text-center text-gray-400">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>
                                <span class="text-sm">No MRF records found. Click <strong>+ Add New</strong> to create one.</span>
                            </div>
                        </td></tr>
                    </template>
                    <template x-for="(row, i) in paginatedRows" :key="i">
                        <tr class="border-t border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-blue-600 font-medium" x-text="row.request_id"></td>
                            <td class="px-4 py-3 text-gray-800" x-text="row.position"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="row.department"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="row.headcount"></td>
                            <td class="px-4 py-3">
                                <span x-text="row.request_status" :class="statusClass(row.request_status)" class="px-2 py-0.5 rounded-full text-xs font-medium"></span>
                            </td>
                            <td class="px-4 py-3 text-gray-500" x-text="row.date_requested"></td>
                            <td class="px-4 py-3">
                                <button @click="viewMRF(row)" class="text-xs text-blue-600 hover:underline mr-2">View</button>
                                <button @click="editMRF(row)" class="text-xs text-amber-600 hover:underline mr-2">Edit</button>
                                <button @click="deleteMRF(row.id)" class="text-xs text-red-500 hover:underline">Delete</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            {{-- JOB PLACEMENT FORM TABLE --}}
            <table class="w-full text-sm border-collapse" x-show="activeTab === 'JPF'">
                <thead class="bg-white text-gray-600 sticky top-0 z-10">
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-3 text-left font-semibold w-[120px]">Job ID</th>
                        <th class="px-4 py-3 text-left font-semibold">Position</th>
                        <th class="px-4 py-3 text-left font-semibold w-[150px]">Status</th>
                        <th class="px-4 py-3 text-left font-semibold w-[150px]">Approval</th>
                        <th class="px-4 py-3 text-left font-semibold w-[220px]">Pending / Next Action</th>
                        <th class="px-4 py-3 text-left font-semibold w-[150px]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="filteredRows.length === 0">
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center text-gray-400">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                                    </svg>
                                    <span class="text-sm" x-text="jpfListView === 'mine' ? 'No JPF records currently need your approval.' : 'No ' + activeTab + ' records found.'"></span>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-for="(row, i) in paginatedRows" :key="i">
                        <tr class="border-t border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-4 py-3 align-top">
                                <div class="text-blue-600 font-semibold leading-tight" x-text="row.job_id"></div>
                                <div class="mt-1 text-[11px] text-gray-400" x-text="row.employment_type || '—'"></div>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="font-semibold text-gray-900 leading-tight" x-text="row.position || '—'"></div>
                                <div class="mt-1 text-xs text-gray-500 line-clamp-2" x-text="row.location || 'No location set'"></div>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <span x-text="row.status" :class="statusClass(row.status)" class="px-2 py-0.5 rounded-full text-xs font-semibold"></span>

                                <div class="mt-1 text-[11px] text-gray-500"
                                    x-show="['Posted','Screening','Interviewing','Offer Stage','Filled','Closed'].includes(row.status)">
                                    <span>Posted </span><span x-text="formatDisplayDate(row.posted_date)"></span>
                                </div>

                                <div class="mt-1 text-[11px] text-amber-600 font-semibold" x-show="row.status === 'Approved'">
                                    Ready to post
                                </div>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="flex items-center gap-2">
                                    <div class="w-20 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-600 rounded-full" :style="`width: ${(jpfApprovalProgress(row) / 4) * 100}%`"></div>
                                    </div>
                                    <span class="text-[11px] font-bold text-gray-600" x-text="jpfApprovalProgress(row) + '/4'"></span>
                                </div>
                                <div class="mt-1 text-[11px] text-gray-500" x-text="jpfApprovalProgress(row) === 4 ? 'All approvals completed' : (4 - jpfApprovalProgress(row)) + ' pending'"></div>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <template x-if="currentPendingApprovalFor(row)">
                                    <div>
                                        <p class="text-[11px] font-bold text-gray-800" x-text="currentPendingApprovalFor(row).label"></p>
                                        <p class="text-[11px] text-gray-500" x-text="approvalDisplayName(currentPendingApprovalFor(row).data)"></p>
                                        <p x-show="pendingApprovalsFor(row).length > 1" class="text-[10px] text-blue-600 font-semibold mt-0.5" x-text="'+' + (pendingApprovalsFor(row).length - 1) + ' more pending'"></p>
                                    </div>
                                </template>

                                <template x-if="!currentPendingApprovalFor(row)">
                                    <span class="text-xs text-gray-400" x-text="row.status === 'Approved' ? 'Ready for posting' : '—'"></span>
                                </template>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <button @click="viewJPF(row)" class="text-xs text-blue-600 hover:underline mr-2" x-text="jpfNeedsMyApproval(row) ? 'Review' : 'View'"></button>
                                <button x-show="row.status === 'Approved'" @click="quickPostJPF(row)" class="text-xs text-green-600 hover:underline mr-2">Post Job</button>
                                <button @click="editJPF(row)" class="text-xs text-amber-600 hover:underline mr-2">Edit</button>
                                <button @click="deleteJPF(row.id)" class="text-xs text-red-500 hover:underline">Delete</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            {{-- CANDIDATE APPLICATION FORM TABLE --}}
            <table class="w-full text-sm border-collapse" x-show="activeTab === 'CAF'">
                <thead class="bg-white text-gray-600 sticky top-0 z-10">
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-3 text-left font-semibold">Name</th>
                        <th class="px-4 py-3 text-left font-semibold">Position</th>
                        <th class="px-4 py-3 text-left font-semibold">Email</th>
                        <th class="px-4 py-3 text-left font-semibold">Phone</th>
                        <th class="px-4 py-3 text-left font-semibold">Type</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-left font-semibold">Applied</th>
                        <th class="px-4 py-3 text-left font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="filteredRows.length === 0">
                        <tr><td colspan="7" class="px-4 py-16 text-center text-gray-400"><div class="flex flex-col items-center gap-2"><svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg><span class="text-sm" x-text="'No ' + activeTab + ' records found.'"></span></div></td></tr>
                    </template>
                    <template x-for="(row, i) in paginatedRows" :key="i">
                        <tr class="border-t border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-gray-800 font-medium" x-text="row.name"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="row.position"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="row.email"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="row.phone"></td>
                            <td class="px-4 py-3">
                                <span
                                    x-text="row.applicant_type || 'New Applicant'"
                                    :class="String(row.applicant_type || '').toLowerCase().includes('existing') ? 'bg-indigo-50 text-indigo-700' : 'bg-gray-100 text-gray-700'"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold"
                                ></span>
                            </td>
                            <td class="px-4 py-3"><span x-text="row.status" :class="statusClass(row.status)" class="px-2 py-0.5 rounded-full text-xs font-medium"></span></td>
                            <td class="px-4 py-3 text-gray-500" x-text="row.applied_date || row.applied"></td>
                            <td class="px-4 py-3">
                                <button @click="viewCAF(row)" class="text-xs text-blue-600 hover:underline mr-2">View</button>
                                <button @click="editCAF(row)" class="text-xs text-amber-600 hover:underline mr-2">Edit</button>
                                <button @click="deleteCAF(row.id)" class="text-xs text-red-500 hover:underline">Delete</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            {{-- ASSESSMENT TABLE --}}
            {{-- ASSESSMENT KANBAN --}}
            <div x-show="activeTab === 'Assessment'" class="flex-1 overflow-x-auto p-4 bg-gray-50/50">
                <div class="flex gap-4 h-full min-w-max">
                    <template x-for="col in [
                        { label: 'Pending Assessment', key: 'Pending Assessment', bg: 'bg-yellow-50', border: 'border-yellow-100', text: 'text-yellow-800' },
                        { label: 'In Progress', key: 'In Progress', bg: 'bg-blue-50', border: 'border-blue-100', text: 'text-blue-800' },
                        { label: 'Passed', key: 'Passed', bg: 'bg-green-50', border: 'border-green-100', text: 'text-green-800' },
                        { label: 'Failed', key: 'Failed', bg: 'bg-red-50', border: 'border-red-100', text: 'text-red-800' }
                    ]" :key="col.key">
                        <div class="w-80 flex flex-col shrink-0">
                            {{-- Column Header --}}
                            <div :class="col.bg + ' ' + col.border" class="px-4 py-3 border rounded-t-xl flex items-center justify-between shrink-0 mb-2">
                                <h3 :class="col.text" class="font-bold text-[13px] uppercase tracking-wider" x-text="col.label"></h3>
                                <span class="bg-white/80 px-2 py-0.5 rounded-full text-[10px] font-black text-gray-500 shadow-sm border border-gray-100" x-text="data['Assessment'].filter(a => a.status === col.key).length"></span>
                            </div>
                            
                            {{-- Column Body --}}
                            <div 
                                class="flex-1 p-1 space-y-3 overflow-y-auto rounded-b-xl min-h-[500px] transition-colors"
                                :class="draggedItem ? 'bg-blue-50/30 border-2 border-dashed border-blue-200' : ''"
                                @dragover.prevent
                                @drop="onDrop(col.key)"
                            >
                                <template x-for="item in data['Assessment'].filter(a => a.status === col.key)" :key="item.name">
                                    <div 
                                        class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition group relative cursor-move"
                                        draggable="true"
                                        @dragstart="onDragStart(item)"
                                    >
                                        <div 
                                            class="absolute top-5 right-5 flex flex-col gap-0.5 opacity-20 group-hover:opacity-60 transition cursor-pointer hover:scale-110 active:scale-95 z-20 bg-gray-100 p-1 rounded-md"
                                            @click.stop="viewAssessment(item)"
                                        >
                                            <div class="flex gap-0.5"><span class="w-1 h-1 bg-gray-900 rounded-full"></span><span class="w-1 h-1 bg-gray-900 rounded-full"></span></div>
                                            <div class="flex gap-0.5"><span class="w-1 h-1 bg-gray-900 rounded-full"></span><span class="w-1 h-1 bg-gray-900 rounded-full"></span></div>
                                            <div class="flex gap-0.5"><span class="w-1 h-1 bg-gray-900 rounded-full"></span><span class="w-1 h-1 bg-gray-900 rounded-full"></span></div>
                                        </div>

                                        <button 
                                            class="absolute top-5 right-12 opacity-0 group-hover:opacity-100 transition-all hover:text-red-600 text-gray-400 p-1 z-20"
                                            @click.stop="deleteAssessment(item.id)"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>

                                        <div class="space-y-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 bg-gray-50 rounded-lg flex items-center justify-center border border-gray-100 shrink-0">
                                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                </div>
                                                <h4 class="font-bold text-gray-900 text-[15px] tracking-tight truncate pr-6" x-text="item.name"></h4>
                                            </div>

                                            <div class="space-y-2.5 ml-1">
                                                <div class="flex items-center gap-3 text-gray-500">
                                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                    <span class="text-[12px] font-medium" x-text="item.position"></span>
                                                </div>
                                                <div class="flex items-center gap-3 text-gray-500">
                                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>
                                                    <span class="text-[12px] font-medium" x-text="item.test_type || item.test"></span>
                                                </div>
                                                <div class="flex items-center gap-3 text-gray-500">
                                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z"/></svg>
                                                    <span class="text-[12px] font-medium" x-text="item.assessment_date || item.date"></span>
                                                </div>
                                                <div x-show="item.score" class="flex items-center gap-2.5 bg-gray-50 px-2.5 py-1.5 rounded-lg w-fit border border-gray-100">
                                                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                                                    <span class="text-[12px] font-black text-gray-700 uppercase" x-text="'Score: ' + item.score"></span>
                                                </div>
                                        </div>

                                        <div x-show="item.status === 'Passed'" class="mt-4 pt-4 border-t border-gray-50 flex justify-end">
                                            <button @click.stop="scheduleInterviewFromAssessment(item)" 
                                                class="bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold px-4 py-2 rounded-xl transition active:scale-95 shadow-lg shadow-blue-100 uppercase tracking-[0.1em] flex items-center gap-2">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
                                                Interview
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- INTERVIEW TABLE --}}
            <table class="w-full text-sm border-collapse" x-show="activeTab === 'Interview'">
                <thead class="bg-white text-gray-600 sticky top-0 z-10">
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-3 text-left font-semibold">Name</th>
                        <th class="px-4 py-3 text-left font-semibold">Position</th>
                        <th class="px-4 py-3 text-left font-semibold">Type</th>
                        <th class="px-4 py-3 text-left font-semibold">Interviewer</th>
                        <th class="px-4 py-3 text-left font-semibold">Date & Time</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-left font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="filteredRows.length === 0">
                        <tr><td colspan="7" class="px-4 py-16 text-center text-gray-400"><div class="flex flex-col items-center gap-2"><svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg><span class="text-sm" x-text="'No ' + activeTab + ' records found.'"></span></div></td></tr>
                    </template>
                    <template x-for="(row, i) in paginatedRows" :key="i">
                        <tr class="border-t border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-gray-800 font-medium" x-text="row.name"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="row.position"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="row.type || row.round"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="row.interviewer"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="row.interview_date || row.date"></td>
                            <td class="px-4 py-3"><span x-text="row.status" :class="statusClass(row.status)" class="px-2 py-0.5 rounded-full text-xs font-medium"></span></td>
                            <td class="px-4 py-3">
                                <button @click="viewInterview(row)" class="text-xs text-blue-600 hover:underline mr-2">View</button>
                                <button @click="deleteInterview(row.id)" class="text-xs text-red-500 hover:underline">Delete</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            {{-- JOB OFFER TABLE --}}
            <table class="w-full text-sm border-collapse" x-show="activeTab === 'Job Offer'">
                <thead class="bg-white text-gray-600 sticky top-0 z-10">
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-3 text-left font-semibold">Name</th>
                        <th class="px-4 py-3 text-left font-semibold">Position</th>
                        <th class="px-4 py-3 text-left font-semibold">Salary</th>
                        <th class="px-4 py-3 text-left font-semibold">Start Date</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-left font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="filteredRows.length === 0">
                        <tr><td colspan="5" class="px-4 py-16 text-center text-gray-400"><div class="flex flex-col items-center gap-2"><svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg><span class="text-sm" x-text="'No ' + activeTab + ' records found.'"></span></div></td></tr>
                    </template>
                    <template x-for="(row, i) in paginatedRows" :key="i">
                        <tr class="border-t border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-gray-800 font-medium" x-text="row.name"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="row.position"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="row.salary"></td>
                            <td class="px-4 py-3 text-gray-500" x-text="row.startDate || row.start_date"></td>
                            <td class="px-4 py-3"><span x-text="row.status" :class="statusClass(row.status)" class="px-2 py-0.5 rounded-full text-xs font-medium"></span></td>
                            <td class="px-4 py-3 text-left whitespace-nowrap">
                                <button @click="viewJobOffer(row)" class="text-xs text-blue-600 hover:underline mr-2">View</button>
                                <button @click="deleteJobOffer(row.id)" class="text-xs text-red-500 hover:underline">Delete</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

        </div>

        {{-- PAGINATION FOOTER --}}
        <div class="px-4 py-2 border-t border-gray-100 bg-blue-50/30 flex items-center justify-end text-[13px] font-semibold text-blue-600 gap-4" x-show="activeTab !== 'Assessment'">
            <div class="flex items-center gap-2">
                <span>Records per page</span>
                <div class="relative">
                    <select x-model="perPage" @change="currentPage = 1"
                        class="bg-transparent border-none focus:ring-0 cursor-pointer pr-5 appearance-none">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <svg class="w-3 h-3 absolute right-0 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                </div>
            </div>
            
            <div class="h-4 w-px bg-blue-200"></div>

            <div class="flex items-center gap-4">
                <span x-text="`${startRange} - ${endRange} of ${filteredRows.length}`"></span>
                <div class="flex items-center gap-1">
                    <button @click="prevPage()" :disabled="currentPage === 1" 
                        class="p-1 hover:bg-blue-100 rounded transition disabled:opacity-30 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="m15 19-7-7 7-7"/></svg>
                    </button>
                    <button @click="nextPage()" :disabled="currentPage === totalPages"
                        class="p-1 hover:bg-blue-100 rounded transition disabled:opacity-30 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="m9 5 7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== MANPOWER REQUEST FORM MODAL (SPLIT PANEL) ===================== --}}
    <div
        x-show="showModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex justify-end bg-black/50 backdrop-blur-sm"
        style="display:none;"
        @click.self="showModal = false"
    >
        <div
            x-show="showModal"
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
                <h2 class="text-sm font-bold text-gray-800 uppercase tracking-widest" x-text="isEditing ? 'Edit Manpower Request' : 'New Manpower Request'"></h2>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Split body --}}
            <div class="flex flex-1 overflow-hidden gap-4 p-4">

                {{-- RIGHT: INPUT FORM --}}
                <div class="w-[42%] bg-white rounded-xl shadow border border-gray-200 flex flex-col overflow-hidden shrink-0 order-last">
                    <div class="px-5 py-3 border-b bg-blue-700 rounded-t-xl">
                        <p class="text-xs font-bold text-white uppercase tracking-wider">Fill Up Form</p>
                    </div>
                    <form @submit.prevent="submitMRF()" class="flex-1 overflow-y-auto px-5 py-4 space-y-4">

                        <div class="border border-blue-100 bg-blue-50/40 rounded-xl p-4 space-y-3">
                            <div>
                                <p class="text-xs font-bold text-blue-700 uppercase tracking-wider">Organizational Assignment</p>
                                <p class="text-[11px] text-gray-500 mt-1">
                                    Select from Organizational structure. This auto-fills the MRF department and position fields.
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Address / Work Location</label>
                                    <select x-model="form.orgAddressId" @change="onOrgAddressChange()"
                                        class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none bg-white">
                                        <option value="">Select Address</option>
                                        <template x-for="address in organizationalAddresses" :key="address.id">
                                            <option :value="address.id" x-text="address.full_address"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Branch</label>
                                    <select x-model="form.orgBranchId" @change="onOrgBranchChange()"
                                        class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none bg-white">
                                        <option value="">Select Branch</option>
                                        <template x-for="branch in filteredBranches" :key="branch.id">
                                            <option :value="branch.id" x-text="branch.branch_name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Office</label>
                                    <select x-model="form.orgOfficeId" @change="onOrgOfficeChange()"
                                        class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none bg-white">
                                        <option value="">Select Office</option>
                                        <template x-for="office in filteredOffices" :key="office.id">
                                            <option :value="office.id" x-text="office.office_name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Department <span class="text-red-500">*</span></label>
                                    <select x-model="form.orgDepartmentId" @change="onOrgDepartmentChange()" required
                                        class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none bg-white">
                                        <option value="">Select Department</option>
                                        <template x-for="department in filteredDepartments" :key="department.id">
                                            <option :value="department.id" x-text="department.department_name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Division</label>
                                    <select x-model="form.orgDivisionId" @change="onOrgDivisionChange()"
                                        class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none bg-white">
                                        <option value="">Select Division</option>
                                        <template x-for="division in filteredDivisions" :key="division.id">
                                            <option :value="division.id" x-text="division.division_name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Unit</label>
                                    <select x-model="form.orgUnitId" @change="onOrgUnitChange()"
                                        class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none bg-white">
                                        <option value="">Select Unit</option>
                                        <template x-for="unit in filteredUnits" :key="unit.id">
                                            <option :value="unit.id" x-text="unit.unit_name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div class="col-span-2">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Position / Title <span class="text-red-500">*</span></label>
                                    <select x-model="form.orgPositionId" @change="onOrgPositionChange()" required
                                        class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none bg-white">
                                        <option value="">Select Position</option>
                                        <template x-for="position in filteredPositions" :key="position.id">
                                            <option :value="position.id" x-text="position.position_name"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>


                            <div class="text-[11px] text-gray-500" x-show="selectedDepartment || selectedPosition">
                                <p x-show="selectedAddress">
                                    <strong>Address:</strong>
                                    <span x-text="selectedAddress?.full_address || '—'"></span>
                                </p>
                                <p x-show="selectedDepartment">
                                    <strong>Department Head:</strong>
                                    <span x-text="selectedDepartment?.department_head || '—'"></span>
                                </p>
                                <p x-show="selectedPosition">
                                    <strong>Position Unit:</strong>
                                    <span x-text="selectedPosition?.unit_name || '—'"></span>
                                </p>
                            </div>
                        </div>

                        <datalist id="active-employee-options">
                            <template x-for="employee in approvalUsers" :key="employee.id">
                                <option :value="employee.name" :label="[employee.position, employee.department].filter(Boolean).join(' - ')"></option>
                            </template>
                            <option value="Others"></option>
                        </datalist>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">MRF Reference Number</label>
                            <input type="text" x-model="form.requestId" placeholder="Auto-generated, e.g. MRF-2026-001"
                                class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                            <p class="mt-1 text-[11px] text-gray-500">Leave blank to auto-generate. Manual override must be authorized and unique.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Date Requested <span class="text-red-500">*</span></label>
                                <input type="date" x-model="form.dateRequested" required
                                    class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Date Required</label>
                                <input type="date" x-model="form.dateRequired"
                                    class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Employment Type</label>
                            <div class="grid grid-cols-2 gap-2">
                                <template x-for="et in employmentTypeOptions" :key="et">
                                    <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 hover:bg-blue-50 hover:border-blue-300 transition"
                                        :class="form.employmentType === et ? 'bg-blue-50 border-blue-400 text-blue-700 font-semibold' : ''">
                                        <input type="radio" x-model="form.employmentType" :value="et" class="accent-blue-600">
                                        <span x-text="et"></span>
                                    </label>
                                </template>
                            </div>
                            <input x-show="form.employmentType === 'Others'" x-model="form.employmentTypeOther" type="text"
                                :required="form.employmentType === 'Others'"
                                placeholder="Specify employment type"
                                class="mt-2 w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                            <p class="mt-2 text-[11px] leading-5 text-gray-500">
                                Note: Employment Type must be selected based on the nature of engagement, duration of work,
                                applicable Philippine labor law, and approved JK&amp;C Human Capital policy. Probationary employment
                                must not exceed six months unless allowed by law. Project-based and fixed-term engagements must
                                have clear start date, end date, scope, and completion basis.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Immediate Supervisor</label>
                                <input type="text" x-model="form.immediateSupervisor" list="active-employee-options" placeholder="Select active employee or choose Others"
                                    class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                                <input x-show="form.immediateSupervisor === 'Others'" x-model="form.immediateSupervisorOther" type="text"
                                    :required="form.immediateSupervisor === 'Others'"
                                    placeholder="Specify immediate supervisor"
                                    class="mt-2 w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Target Start Date</label>
                                <input type="date" x-model="form.targetStartDate"
                                    class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Job Level / Rank</label>
                                <select x-model="form.jobLevelRank" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                                    <option value="">Select...</option>
                                    <template x-for="option in jobLevelRankOptions" :key="option">
                                        <option x-text="option"></option>
                                    </template>
                                </select>
                                <input x-show="form.jobLevelRank === 'Others'" x-model="form.jobLevelRankOther" type="text"
                                    :required="form.jobLevelRank === 'Others'"
                                    placeholder="Specify job level / rank"
                                    class="mt-2 w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Work Classification</label>
                                <select x-model="form.workClassification" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                                    <option value="">Select...</option>
                                    <template x-for="option in workClassificationOptions" :key="option">
                                        <option x-text="option"></option>
                                    </template>
                                </select>
                                <input x-show="form.workClassification === 'Others'" x-model="form.workClassificationOther" type="text"
                                    :required="form.workClassification === 'Others'"
                                    placeholder="Specify work classification"
                                    class="mt-2 w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Work Arrangement</label>
                                <select x-model="form.workArrangement" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                                    <option value="">Select...</option>
                                    <template x-for="option in workArrangementOptions" :key="option">
                                        <option x-text="option"></option>
                                    </template>
                                </select>
                                <input x-show="form.workArrangement === 'Others'" x-model="form.workArrangementOther" type="text"
                                    :required="form.workArrangement === 'Others'"
                                    placeholder="Specify work arrangement"
                                    class="mt-2 w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Work Schedule</label>
                                <select x-model="form.workSchedule" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                                    <option value="">Select...</option>
                                    <template x-for="option in workScheduleOptions" :key="option">
                                        <option x-text="option"></option>
                                    </template>
                                </select>
                                <input x-show="form.workSchedule === 'Others' || form.workSchedule === 'Custom Schedule'" x-model="form.workScheduleOther" type="text"
                                    :required="form.workSchedule === 'Others' || form.workSchedule === 'Custom Schedule'"
                                    placeholder="Specify custom schedule"
                                    class="mt-2 w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Brief Description of Duties</label>
                            <textarea x-model="form.duties" rows="3" placeholder="Describe duties and responsibilities..."
                                class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none resize-none bg-gray-50"></textarea>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Nature of Request</label>
                                <template x-for="n in ['New / Addition','Replacement']" :key="n">
                                    <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer mb-1">
                                        <input type="radio" x-model="form.natureOfRequest" :value="n" class="accent-blue-600">
                                        <span x-text="n"></span>
                                    </label>
                                </template>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Civil Status</label>
                                <template x-for="s in ['Single','Married','No Preference']" :key="s">
                                    <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer mb-1">
                                        <input type="radio" x-model="form.civilStatus" :value="s" class="accent-blue-600">
                                        <span x-text="s"></span>
                                    </label>
                                </template>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Gender</label>
                                <template x-for="g in ['Male','Female','No Preference']" :key="g">
                                    <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer mb-1">
                                        <input type="radio" x-model="form.gender" :value="g" class="accent-blue-600">
                                        <span x-text="g"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Age Range</label>
                                <input type="text" x-model="form.ageRange" placeholder="e.g. 20-35"
                                    class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Headcount <span class="text-red-500">*</span></label>
                                <input type="number" x-model="form.headcount" min="1" required placeholder="e.g. 2"
                                    class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Educational Requirement</label>
                            <input type="text" x-model="form.education" placeholder="e.g. Bachelor's Degree in IT"
                                class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Preferred Qualifications / Experience</label>
                            <textarea x-model="form.qualifications" rows="2" placeholder="List preferred skills or experience..."
                                class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none resize-none bg-gray-50"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Required Skills / Competencies</label>
                                <textarea x-model="form.requiredSkills" @blur="form.requiredSkills = normalizeBulletText(form.requiredSkills)" rows="4" placeholder="One entry per line. These will appear as bullet points."
                                    class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none resize-none bg-gray-50"></textarea>
                                <p class="mt-1 text-[11px] text-gray-500">Each line is saved as a bullet point. Blank lines are ignored.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Required Licenses / Certifications</label>
                                <textarea x-model="form.requiredLicenses" @blur="form.requiredLicenses = normalizeBulletText(form.requiredLicenses)" rows="4" placeholder="One entry per line. These will appear as bullet points."
                                    class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none resize-none bg-gray-50"></textarea>
                                <p class="mt-1 text-[11px] text-gray-500">Manual typing, pasted lists, and custom entries are converted into bullet lines.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-2">Benefits Checklist</label>
                                <div class="max-h-56 overflow-y-auto border border-gray-200 rounded-lg p-3 bg-gray-50 space-y-2">
                                    <template x-for="benefit in benefitsChecklistOptions" :key="benefit">
                                        <label class="flex items-start gap-2 text-xs text-gray-700">
                                            <input type="checkbox" x-model="form.benefitsChecklist" :value="benefit" class="mt-0.5 accent-blue-600">
                                            <span x-text="benefit"></span>
                                        </label>
                                    </template>
                                </div>
                                <input x-show="form.benefitsChecklist.includes('Others')" x-model="form.benefitsChecklistOther" type="text"
                                    :required="form.benefitsChecklist.includes('Others')"
                                    placeholder="Specify other benefit"
                                    class="mt-2 w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                                <p class="mt-1 text-[11px] text-gray-500">Selected benefits will flow to the Job Offer checklist.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-2">Required Applicant Documents</label>
                                <div class="max-h-56 overflow-y-auto border border-gray-200 rounded-lg p-3 bg-gray-50 space-y-2">
                                    <template x-for="documentName in requiredDocumentOptions" :key="documentName">
                                        <label class="flex items-start gap-2 text-xs text-gray-700">
                                            <input type="checkbox" x-model="form.requiredDocuments" :value="documentName" class="mt-0.5 accent-blue-600">
                                            <span x-text="documentName"></span>
                                        </label>
                                    </template>
                                </div>
                                <input x-show="form.requiredDocuments.includes('Others')" x-model="form.requiredDocumentsOther" type="text"
                                    :required="form.requiredDocuments.includes('Others')"
                                    placeholder="Specify other applicant document"
                                    class="mt-2 w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Salary Budget Minimum</label>
                                <input type="number" x-model="form.salaryMin" step="0.01"
                                    class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Salary Budget Maximum</label>
                                <input type="number" x-model="form.salaryMax" step="0.01"
                                    class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Contract Duration</label>
                                <select x-model="form.contractDuration" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                                    <option value="">Select...</option>
                                    <template x-for="option in contractDurationOptions" :key="option">
                                        <option x-text="option"></option>
                                    </template>
                                </select>
                                <input x-show="form.contractDuration === 'Others'" x-model="form.contractDurationOther" type="text"
                                    :required="form.contractDuration === 'Others'"
                                    placeholder="Specify contract duration"
                                    class="mt-2 w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Urgency Level</label>
                                <select x-model="form.urgencyLevel" class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                                    <option value="">Select...</option>
                                    <template x-for="option in urgencyLevelOptions" :key="option">
                                        <option x-text="option"></option>
                                    </template>
                                </select>
                                <input x-show="form.urgencyLevel === 'Others'" x-model="form.urgencyLevelOther" type="text"
                                    :required="form.urgencyLevel === 'Others'"
                                    placeholder="Specify urgency level"
                                    class="mt-2 w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                            </div>
                        </div>

                        <div class="border-t pt-3">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Attachments and Endorsements</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                    <label class="flex items-center justify-between gap-3 text-xs font-semibold text-gray-700">
                                        <span>Candidate Profile Attached</span>
                                        <input type="checkbox" x-model="form.candidateProfileAttached" class="accent-blue-600">
                                    </label>
                                    <input x-show="form.candidateProfileAttached" type="file" @change="form.candidateProfileFile = $event.target.files[0] || null"
                                        class="mt-2 w-full text-xs text-gray-600">
                                </div>
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                    <label class="flex items-center justify-between gap-3 text-xs font-semibold text-gray-700">
                                        <span>Job Description Attached</span>
                                        <input type="checkbox" x-model="form.jobDescriptionAttached" class="accent-blue-600">
                                    </label>
                                    <input x-show="form.jobDescriptionAttached" type="file" @change="form.jobDescriptionFile = $event.target.files[0] || null"
                                        class="mt-2 w-full text-xs text-gray-600">
                                </div>
                                <template x-for="endorsement in [
                                    { key: 'immediateSupervisorEndorsement', other: 'immediateSupervisorEndorsementOther', label: 'Immediate Supervisor Endorsement' },
                                    { key: 'departmentHeadEndorsement', other: 'departmentHeadEndorsementOther', label: 'Department Head Endorsement' },
                                    { key: 'hcHeadValidation', other: 'hcHeadValidationOther', label: 'HC Head Validation' },
                                    { key: 'financeHeadClearance', other: 'financeHeadClearanceOther', label: 'Finance Head Clearance' }
                                ]" :key="endorsement.key">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1" x-text="endorsement.label"></label>
                                        <input type="text" x-model="form[endorsement.key]" list="active-employee-options" placeholder="Select active employee or choose Others"
                                            class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                                        <input x-show="form[endorsement.key] === 'Others'" x-model="form[endorsement.other]" type="text"
                                            :required="form[endorsement.key] === 'Others'"
                                            placeholder="Specify approver / endorsement"
                                            class="mt-2 w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="border-t pt-3">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Approvals</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Requested by</label>
                                    <input type="text" x-model="form.requestedBy" placeholder="Full name"
                                        class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Approved by</label>
                                    <input type="text" x-model="form.approvedBy" placeholder="Full name"
                                        class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                                </div>
                            </div>
                        </div>

                        <div class="border-t pt-3">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">For HRS Use Only</p>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Additional Remarks</label>
                                <textarea x-model="form.remarks" rows="2" placeholder="Reason for request..."
                                    class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none resize-none bg-gray-50"></textarea>
                            </div>
                            <div class="mt-3">
                                <label class="block text-xs font-semibold text-gray-600 mb-2">Request Status</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <template x-for="rs in ['Filled','Cancelled','Hold','Disapproved']" :key="rs">
                                        <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                            <input type="radio" x-model="form.requestStatus" :value="rs" class="accent-blue-600">
                                            <span x-text="rs"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 mt-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Charged to (Dept)</label>
                                    <input type="text" x-model="form.chargedTo" placeholder="Department"
                                        class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Breakdown Details</label>
                                    <input type="text" x-model="form.breakdownDetails" placeholder="Details"
                                        class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Name of Hired Personnel</label>
                                    <input type="text" x-model="form.hiredPersonnel" placeholder="Full name"
                                        class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Date Hired</label>
                                    <input type="date" x-model="form.dateHired"
                                        class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Processed by</label>
                                    <input type="text" x-model="form.processedBy" placeholder="Full name"
                                        class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Checked / Approved by</label>
                                    <input type="text" x-model="form.checkedBy" placeholder="Full name"
                                        class="w-full text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none">
                                </div>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="flex justify-end gap-3 pt-2 pb-1">
                            <button type="button" @click="showModal = false"
                                class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-5 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium shadow-sm transition">
                                Submit Request
                            </button>
                        </div>

                    </form>
                </div>

                {{-- LEFT: LIVE MRF DOCUMENT PREVIEW --}}
                <div class="flex-1 bg-white rounded-xl shadow border border-gray-200 flex flex-col overflow-hidden">
                    <div class="px-5 py-3 border-b bg-gray-50 rounded-t-xl shrink-0 flex items-center justify-between">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Live Preview</p>
                        <div class="flex items-center gap-2">

                            <button type="button" @click="downloadPDF('mrf-doc-create')" class="text-xs px-3 py-1.5 bg-white hover:bg-gray-50 border border-gray-300 rounded text-gray-700 font-semibold flex items-center gap-1 transition shadow-sm">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Download PDF
                            </button>
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto p-5">
                        <div id="mrf-doc-create" class="border border-gray-400 text-xs text-gray-800 font-sans w-[794px] shrink-0 leading-[1.2] mx-auto shadow-sm bg-white p-4">

                            {{-- Form Header with Logo --}}
                            <div class="flex items-center justify-center pb-4 pt-2 border-b border-gray-400">
                                <img src="{{ asset('images/imaglogo.png') }}" onerror="this.src='{{ asset('images/imag1logo.jpg') }}'" alt="John Kelly & Company" class="h-14 w-auto object-contain mix-blend-multiply">
                            </div>

                            {{-- Title --}}
                            <div class="bg-blue-700 text-white text-center font-bold py-2 text-sm tracking-widest uppercase border-b border-gray-400">
                                Manpower Request Form
                            </div>

                            {{-- Organizational Assignment --}}
                            <div class="border-b border-gray-400">
                                <div class="bg-gray-100 text-center font-bold py-1 text-[11px] uppercase tracking-widest border-b border-gray-400">
                                    Organizational Assignment
                                </div>
                                <div class="grid grid-cols-2 divide-x divide-gray-400">
                                    <div class="p-2 border-b border-gray-400">
                                        <span class="text-gray-500">Address / Work Location:</span>
                                        <p class="font-semibold mt-0.5 min-h-[1rem]" x-text="selectedAddress?.full_address || ''"></p>
                                    </div>
                                    <div class="p-2 border-b border-gray-400">
                                        <span class="text-gray-500">Branch:</span>
                                        <p class="font-semibold mt-0.5 min-h-[1rem]" x-text="selectedBranch?.branch_name || ''"></p>
                                    </div>
                                    <div class="p-2 border-b border-gray-400">
                                        <span class="text-gray-500">Office:</span>
                                        <p class="font-semibold mt-0.5 min-h-[1rem]" x-text="selectedOffice?.office_name || ''"></p>
                                    </div>
                                    <div class="p-2 border-b border-gray-400">
                                        <span class="text-gray-500">Department:</span>
                                        <p class="font-semibold mt-0.5 min-h-[1rem]" x-text="form.department || selectedDepartment?.department_name || ''"></p>
                                    </div>
                                    <div class="p-2 border-b border-gray-400">
                                        <span class="text-gray-500">Division:</span>
                                        <p class="font-semibold mt-0.5 min-h-[1rem]" x-text="selectedDivision?.division_name || ''"></p>
                                    </div>
                                    <div class="p-2 border-b border-gray-400">
                                        <span class="text-gray-500">Unit:</span>
                                        <p class="font-semibold mt-0.5 min-h-[1rem]" x-text="selectedUnit?.unit_name || ''"></p>
                                    </div>
                                    <div class="p-2">
                                        <span class="text-gray-500">Position / Title:</span>
                                        <p class="font-semibold mt-0.5 min-h-[1rem]" x-text="form.position || selectedPosition?.position_name || ''"></p>
                                    </div>
                                    <div class="p-2">
                                        <span class="text-gray-500">Department Head / Position Unit:</span>
                                        <p class="font-semibold mt-0.5 min-h-[1rem]">
                                            <span x-text="selectedDepartment?.department_head || ''"></span>
                                            <span x-show="selectedDepartment?.department_head && selectedPosition?.unit_name"> / </span>
                                            <span x-text="selectedPosition?.unit_name || ''"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Row 1 --}}
                            <div class="grid grid-cols-2 border-b border-gray-400 divide-x divide-gray-400">
                                <div class="p-2">
                                    <span class="text-gray-500">Requesting Department:</span>
                                    <p class="font-semibold mt-0.5 min-h-[1rem]" x-text="form.department || ''"></p>
                                </div>
                                <div class="p-2">
                                    <span class="text-gray-500">Date Requested:</span>
                                    <p class="font-semibold mt-0.5 min-h-[1rem]" x-text="form.dateRequested || ''"></p>
                                    <span class="text-gray-500 block mt-2">Date Required:</span>
                                    <p class="font-semibold mt-0.5 min-h-[1rem]" x-text="form.dateRequired || ''"></p>
                                </div>
                            </div>

                            {{-- Row 2 --}}
                            <div class="grid grid-cols-2 border-b border-gray-400 divide-x divide-gray-400">
                                <div class="p-2">
                                    <span class="text-gray-500">Position / Title:</span>
                                    <p class="font-semibold mt-0.5 min-h-[1rem]" x-text="form.position || ''"></p>
                                </div>
                                <div class="p-2">
                                    <span class="text-gray-500">Employment Type:</span>
                                    <div class="grid grid-cols-2 gap-x-2 mt-1">
                                        <template x-for="et in employmentTypeOptions" :key="et">
                                            <div class="flex items-center gap-1">
                                                <span class="inline-flex items-center justify-center w-3 h-3 border border-gray-400 rounded-sm shrink-0"
                                                    :class="form.employmentType === et ? 'bg-blue-600 border-blue-600' : ''">
                                                    <svg x-show="form.employmentType === et" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 12 12"><path d="M10 3L5 8.5 2 5.5"/></svg>
                                                </span>
                                                <span x-text="et"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Row 3: Duties --}}
                            <div class="border-b border-gray-400 p-2">
                                <span class="text-gray-500">Brief Description of Duties <em>(or attach the Job Description)</em>:</span>
                                <p class="mt-1 whitespace-pre-wrap min-h-[3rem]" x-text="form.duties || ''"></p>
                            </div>

                            {{-- Row 4: Nature / Age / Status / Gender --}}
                            <div class="grid grid-cols-4 border-b border-gray-400 divide-x divide-gray-400">
                                <div class="p-2">
                                    <span class="text-gray-500">Nature of Request:</span>
                                    <template x-for="n in ['New / Addition','Replacement']" :key="n">
                                        <div class="flex items-center gap-1 mt-1">
                                            <span class="inline-flex items-center justify-center w-3 h-3 border border-gray-400 rounded-sm shrink-0"
                                                :class="form.natureOfRequest === n ? 'bg-blue-600 border-blue-600' : ''">
                                                <svg x-show="form.natureOfRequest === n" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 12 12"><path d="M10 3L5 8.5 2 5.5"/></svg>
                                            </span>
                                            <span x-text="n"></span>
                                        </div>
                                    </template>
                                </div>
                                <div class="p-2">
                                    <span class="text-gray-500">Age Range:</span>
                                    <p class="font-semibold mt-1 min-h-[1rem]" x-text="form.ageRange || ''"></p>
                                </div>
                                <div class="p-2">
                                    <span class="text-gray-500">Status:</span>
                                    <template x-for="s in ['Single','Married','No Preference']" :key="s">
                                        <div class="flex items-center gap-1 mt-1">
                                            <span class="inline-flex items-center justify-center w-3 h-3 border border-gray-400 rounded-sm shrink-0"
                                                :class="form.civilStatus === s ? 'bg-blue-600 border-blue-600' : ''">
                                                <svg x-show="form.civilStatus === s" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 12 12"><path d="M10 3L5 8.5 2 5.5"/></svg>
                                            </span>
                                            <span x-text="s"></span>
                                        </div>
                                    </template>
                                </div>
                                <div class="p-2">
                                    <span class="text-gray-500">Gender:</span>
                                    <template x-for="g in ['Male','Female','No Preference']" :key="g">
                                        <div class="flex items-center gap-1 mt-1">
                                            <span class="inline-flex items-center justify-center w-3 h-3 border border-gray-400 rounded-sm shrink-0"
                                                :class="form.gender === g ? 'bg-blue-600 border-blue-600' : ''">
                                                <svg x-show="form.gender === g" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 12 12"><path d="M10 3L5 8.5 2 5.5"/></svg>
                                            </span>
                                            <span x-text="g"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Row 5: Education / Headcount --}}
                            <div class="grid grid-cols-2 border-b border-gray-400 divide-x divide-gray-400">
                                <div class="p-2">
                                    <span class="text-gray-500">Educational Requirement:</span>
                                    <p class="font-semibold mt-0.5 min-h-[1rem]" x-text="form.education || ''"></p>
                                </div>
                                <div class="p-2">
                                    <span class="text-gray-500">Headcount Requested:</span>
                                    <p class="font-semibold mt-0.5 min-h-[1rem]" x-text="form.headcount || ''"></p>
                                </div>
                            </div>

                            {{-- Row 6: Qualifications --}}
                            <div class="border-b border-gray-400 p-2">
                                <span class="text-gray-500">Preferred Qualifications / Experience <em>(not mentioned above or in the JD)</em>:</span>
                                <p class="mt-1 whitespace-pre-wrap min-h-[2.5rem]" x-text="form.qualifications || ''"></p>
                            </div>

                            {{-- APPROVALS header --}}
                            <div class="bg-blue-700 text-white text-center font-bold py-1.5 text-xs tracking-widest uppercase border-b border-gray-400">
                                Approvals
                            </div>
                            <div class="grid grid-cols-2 border-b border-gray-400 divide-x divide-gray-400">
                                <div class="p-2 flex flex-col items-center">
                                    <span class="text-gray-500 self-start text-[10px] uppercase">Requested by:</span>
                                    <p class="font-bold text-gray-800 mt-4 text-xs" x-text="form.requestedBy || ''"></p>
                                    <p class="text-gray-400 italic text-[9px] border-t border-gray-300 w-full text-center pt-1">Signature Over Printed Name</p>
                                </div>
                                <div class="p-2 flex flex-col items-center">
                                    <span class="text-gray-500 self-start text-[10px] uppercase">Approved by:</span>
                                    <p class="font-bold text-gray-800 mt-4 text-xs" x-text="form.approvedBy || ''"></p>
                                    <p class="text-gray-400 italic text-[9px] border-t border-gray-300 w-full text-center pt-1">Signature Over Printed Name</p>
                                </div>
                            </div>

                            {{-- FOR HRS USE ONLY --}}
                            <div class="bg-gray-200 text-center font-bold py-1.5 text-xs tracking-widest uppercase border-b border-gray-400 text-gray-700">
                                For HRS Use Only
                            </div>
                            <div class="border-b border-gray-400 p-2">
                                <span class="text-gray-500">Additional Remarks <em>(Reason for Request)</em>:</span>
                                <p class="mt-1 whitespace-pre-wrap min-h-[2rem]" x-text="form.remarks || ''"></p>
                            </div>
                            <div class="grid grid-cols-2 border-b border-gray-400 divide-x divide-gray-400">
                                <div class="p-2">
                                    <span class="text-gray-500">Request Status:</span>
                                    <div class="grid grid-cols-2 gap-x-2 mt-1">
                                        <template x-for="rs in ['Filled','Cancelled','Hold','Disapproved']" :key="rs">
                                            <div class="flex items-center gap-1 mt-0.5">
                                                <span class="inline-flex items-center justify-center w-3 h-3 border border-gray-400 rounded-sm shrink-0"
                                                    :class="form.requestStatus === rs ? 'bg-blue-600 border-blue-600' : ''">
                                                    <svg x-show="form.requestStatus === rs" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 12 12"><path d="M10 3L5 8.5 2 5.5"/></svg>
                                                </span>
                                                <span x-text="rs"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <p class="mt-2 text-gray-500">Name of Hired Personnel:</p>
                                    <p class="font-semibold min-h-[1rem]" x-text="form.hiredPersonnel || ''"></p>
                                    <p class="mt-2 text-gray-500">Date Hired:</p>
                                    <p class="font-semibold min-h-[1rem]" x-text="form.dateHired || ''"></p>
                                    <p class="text-gray-500 mt-2 text-[10px] uppercase">Processed by:</p>
                                    <div class="flex flex-col items-center mt-2">
                                        <p class="font-bold text-gray-800 text-xs" x-text="form.processedBy || ''"></p>
                                        <p class="text-gray-400 italic text-[9px] border-t border-gray-300 w-full text-center pt-1">Signature Over Printed Name</p>
                                    </div>
                                </div>
                                <div class="p-2">
                                    <span class="text-gray-500 text-[10px] uppercase">Charged to (Department):</span>
                                    <p class="font-semibold mt-0.5 min-h-[1rem]" x-text="form.chargedTo || ''"></p>
                                    <p class="mt-2 text-gray-500 text-[10px] uppercase">Breakdown Details:</p>
                                    <p class="font-semibold min-h-[1rem]" x-text="form.breakdownDetails || ''"></p>
                                    <p class="mt-4 text-gray-500 text-[10px] uppercase">Checked / Approved by:</p>
                                    <div class="flex flex-col items-center mt-2">
                                        <p class="font-bold text-gray-800 text-xs" x-text="form.checkedBy || ''"></p>
                                        <p class="text-gray-400 italic text-[9px] border-t border-gray-300 w-full text-center pt-1">Signature Over Printed Name</p>
                                    </div>
                                </div>
                            </div>

                        </div>{{-- end form doc --}}
                    </div>
                </div>{{-- end right panel --}}

            </div>{{-- end split body --}}
        </div>
    </div>

    {{-- ===================== MANPOWER REQUEST FORM VIEW MODAL ===================== --}}

    {{-- ===================== MANPOWER REQUEST FORM VIEW SLIDE-OVER ===================== --}}
    <div x-show="showViewModal" class="fixed inset-0 overflow-hidden z-[9999]" style="display:none;">
        <div @click="showViewModal = false" class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity"
            x-show="showViewModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="fixed inset-y-0 right-0 w-[95vw] flex pointer-events-none">
            <div x-show="showViewModal" 
                x-transition:enter="transform transition ease-in-out duration-500"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="h-full w-full bg-white shadow-2xl flex flex-col overflow-hidden pointer-events-auto">
                <div class="flex items-center justify-between px-8 py-5 border-b bg-gray-50 flex-shrink-0">
                    <h2 class="text-lg font-bold text-gray-800 tracking-widest uppercase">Manpower Request Form Details</h2>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-3 pr-6 border-r border-gray-300">
                            <span class="text-xs text-gray-500 font-bold uppercase tracking-widest">Document Format: A4</span>
                        </div>
                        <button type="button" @click="downloadPDF('mrf-doc-view')" class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-bold shadow-md transition-all flex items-center gap-2 transform hover:-translate-y-0.5 active:translate-y-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Export to PDF
                        </button>
                        <button @click="showViewModal = false" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-all">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            <div class="flex-1 overflow-y-auto bg-gray-100 py-10 px-6" x-show="viewData">
                <template x-if="viewData">
                <div id="mrf-doc-view" class="border border-gray-300 bg-white p-4 mx-auto w-[794px] shrink-0">
                    <div class="flex items-center justify-center pb-4 pt-2 border-b border-gray-300">
                        <img src="{{ asset('images/imaglogo.png') }}" onerror="this.src='{{ asset('images/imag1logo.jpg') }}'" alt="John Kelly & Company" class="h-14 w-auto object-contain mix-blend-multiply">
                    </div>
                    <div class="bg-blue-700 text-white text-center font-bold py-2 text-sm tracking-widest uppercase border-gray-300">Manpower Request Form</div>

                    <div class="border-b border-gray-300">
                        <div class="bg-gray-100 text-center font-bold py-1 text-[11px] uppercase tracking-widest border-b border-gray-300">
                            Organizational Assignment
                        </div>
                        <div class="grid grid-cols-2 divide-x divide-gray-300">
                            <div class="p-3 border-b border-gray-300"><span class="text-xs text-gray-500">Address / Work Location:</span><p class="font-medium mt-1" x-text="orgDisplay('organizationalAddresses', viewData.address_id, 'full_address')"></p></div>
                            <div class="p-3 border-b border-gray-300"><span class="text-xs text-gray-500">Branch:</span><p class="font-medium mt-1" x-text="orgDisplay('branches', viewData.branch_id, 'branch_name')"></p></div>
                            <div class="p-3 border-b border-gray-300"><span class="text-xs text-gray-500">Office:</span><p class="font-medium mt-1" x-text="orgDisplay('offices', viewData.office_id, 'office_name')"></p></div>
                            <div class="p-3 border-b border-gray-300"><span class="text-xs text-gray-500">Department:</span><p class="font-medium mt-1" x-text="viewData.department || orgDisplay('departments', viewData.department_id, 'department_name')"></p></div>
                            <div class="p-3 border-b border-gray-300"><span class="text-xs text-gray-500">Division:</span><p class="font-medium mt-1" x-text="orgDisplay('divisions', viewData.division_id, 'division_name')"></p></div>
                            <div class="p-3 border-b border-gray-300"><span class="text-xs text-gray-500">Unit:</span><p class="font-medium mt-1" x-text="orgDisplay('units', viewData.unit_id, 'unit_name')"></p></div>
                            <div class="p-3"><span class="text-xs text-gray-500">Position / Title:</span><p class="font-medium mt-1" x-text="viewData.position || orgDisplay('positions', viewData.position_id, 'position_name')"></p></div>
                            <div class="p-3"><span class="text-xs text-gray-500">Department Head / Position Unit:</span><p class="font-medium mt-1"><span x-text="orgDisplay('departments', viewData.department_id, 'department_head', '')"></span><span x-show="orgDisplay('departments', viewData.department_id, 'department_head', '') && orgDisplay('positions', viewData.position_id, 'unit_name', '')"> / </span><span x-text="orgDisplay('positions', viewData.position_id, 'unit_name', '')"></span></p></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 border-b border-gray-300 divide-x divide-gray-300">
                        <div class="p-3"><span class="text-xs text-gray-500">Requesting Department:</span><p class="font-medium mt-1" x-text="viewData.department"></p></div>
                        <div class="p-3">
                            <span class="text-xs text-gray-500">Date Requested:</span><p class="font-medium mt-1" x-text="viewData.dateRequested || viewData.date_requested"></p>
                            <span class="text-xs text-gray-500 block mt-2">Date Required:</span><p class="font-medium mt-1" x-text="viewData.dateRequired || viewData.date_required || '—'"></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 border-b border-gray-300 divide-x divide-gray-300">
                        <div class="p-3"><span class="text-xs text-gray-500">Position / Title:</span><p class="font-medium mt-1" x-text="viewData.position"></p></div>
                        <div class="p-3"><span class="text-xs text-gray-500">Employment Type:</span><p class="font-medium mt-1" x-text="viewData.employmentType || viewData.employment_type || '—'"></p></div>
                    </div>
                    <div class="border-b border-gray-300 p-3">
                        <span class="text-xs text-gray-500">Brief Description of Duties:</span>
                        <p class="mt-1 whitespace-pre-wrap" x-text="viewData.duties || '—'"></p>
                    </div>
                    <div class="grid grid-cols-4 border-b border-gray-300 divide-x divide-gray-300">
                        <div class="p-3"><span class="text-xs text-gray-500">Nature of Request:</span><p class="font-medium mt-1" x-text="viewData.natureOfRequest || '—'"></p></div>
                        <div class="p-3"><span class="text-xs text-gray-500">Age Range:</span><p class="font-medium mt-1" x-text="viewData.ageRange || '—'"></p></div>
                        <div class="p-3"><span class="text-xs text-gray-500">Civil Status:</span><p class="font-medium mt-1" x-text="viewData.civilStatus || '—'"></p></div>
                        <div class="p-3"><span class="text-xs text-gray-500">Gender:</span><p class="font-medium mt-1" x-text="viewData.gender || '—'"></p></div>
                    </div>
                    <div class="grid grid-cols-2 border-b border-gray-300 divide-x divide-gray-300">
                        <div class="p-3"><span class="text-xs text-gray-500">Headcount:</span><p class="font-medium mt-1" x-text="viewData.headcount"></p></div>
                        <div class="p-3"><span class="text-xs text-gray-500">Educational Requirement:</span><p class="font-medium mt-1" x-text="viewData.education || '—'"></p></div>
                    </div>
                    <div class="border-b border-gray-300 p-3">
                        <span class="text-xs text-gray-500">Preferred Qualifications / Experience:</span>
                        <p class="mt-1 whitespace-pre-wrap" x-text="viewData.qualifications || '—'"></p>
                    </div>
                    <div class="bg-blue-700 text-white text-center text-xs font-bold py-1.5 tracking-widest uppercase">Approvals</div>
                    <div class="grid grid-cols-2 border-b border-gray-300 divide-x divide-gray-300">
                        <div class="p-3 flex flex-col items-center">
                            <span class="text-xs text-gray-500 self-start uppercase">Requested by:</span>
                            <p class="font-bold text-gray-800 mt-6 text-sm" x-text="viewData.requestedBy"></p>
                            <p class="text-[10px] text-gray-400 italic border-t border-gray-300 w-full text-center pt-1">Signature Over Printed Name</p>
                        </div>
                        <div class="p-3 flex flex-col items-center">
                            <span class="text-xs text-gray-500 self-start uppercase">Approved by:</span>
                            <p class="font-bold text-gray-800 mt-6 text-sm" x-text="viewData.approvedBy"></p>
                            <p class="text-[10px] text-gray-400 italic border-t border-gray-300 w-full text-center pt-1">Signature Over Printed Name</p>
                        </div>
                    </div>
                    <div class="bg-gray-100 text-center text-xs font-bold py-1.5 tracking-widest uppercase text-gray-700">For HRS Use Only</div>
                    <div class="border-b border-gray-300 p-3"><span class="text-xs text-gray-500">Additional Remarks:</span><p class="mt-1" x-text="viewData.remarks || '—'"></p></div>
                    <div class="grid grid-cols-2 border-b border-gray-300 divide-x divide-gray-300">
                        <div class="p-3">
                            <span class="text-xs text-gray-500">Request Status:</span><p class="font-medium mt-1 text-gray-800" x-text="viewData.requestStatus || '—'"></p>
                            <span class="text-xs text-gray-500 block mt-2">Name of Hired Personnel:</span><p class="font-medium mt-1" x-text="viewData.hiredPersonnel || '—'"></p>
                            <span class="text-xs text-gray-500 block mt-2">Date Hired:</span><p class="font-medium mt-1" x-text="viewData.dateHired || '—'"></p>
                            <span class="text-xs text-gray-500 block mt-3 uppercase">Processed by:</span>
                            <div class="flex flex-col items-center mt-4">
                                <p class="font-bold text-gray-800 text-sm" x-text="viewData.processedBy"></p>
                                <p class="text-[10px] text-gray-400 italic border-t border-gray-300 w-full text-center pt-1">Signature Over Printed Name</p>
                            </div>
                        </div>
                        <div class="p-3">
                            <span class="text-xs text-gray-500 block uppercase">Charged to (Department):</span><p class="font-medium mt-1" x-text="viewData.chargedTo || '—'"></p>
                            <span class="text-xs text-gray-500 block mt-3 uppercase">Breakdown Details:</span><p class="font-medium mt-1" x-text="viewData.breakdownDetails || '—'"></p>
                            <span class="text-xs text-gray-500 block mt-3 uppercase">Checked / Approved by:</span>
                            <div class="flex flex-col items-center mt-4">
                                <p class="font-bold text-gray-800 text-sm" x-text="viewData.checkedBy"></p>
                                <p class="text-[10px] text-gray-400 italic border-t border-gray-300 w-full text-center pt-1">Signature Over Printed Name</p>
                            </div>
                        </div>
                    </div>
                </div>
                </template>
            </div>
            <div class="flex justify-end gap-3 px-10 py-6 border-t bg-white shrink-0">
                <button @click="showViewModal = false" class="px-6 py-2.5 text-sm border border-gray-200 rounded-lg text-gray-500 hover:bg-gray-50 font-bold transition-all mr-auto">Close</button>
                
                <button @click="cancelMRF(viewData.id)" 
                    x-show="viewData.request_status !== 'Approved' && viewData.request_status !== 'Cancelled' && viewData.request_status !== 'Disapproved'"
                    class="px-8 py-3 text-base border-2 border-red-100 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg font-bold transition-all transform hover:scale-[1.02] active:scale-100 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Cancel Request
                </button>

                <button @click="approveMRF(viewData.id); showViewModal = false" 
                    x-show="viewData.request_status !== 'Approved' && viewData.request_status !== 'Cancelled' && viewData.request_status !== 'Disapproved'"
                    class="px-10 py-3 text-base bg-green-600 hover:bg-green-700 text-white rounded-lg font-bold shadow-lg shadow-green-200 transition-all transform hover:scale-[1.02] active:scale-100 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Approve
                </button>
            </div>
            </div>
        </div>
    </div>

    {{-- ===================== JOB PLACEMENT FORM MODAL ===================== --}}
    <div
        x-show="showJpfModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex justify-end bg-black/50 backdrop-blur-sm"
        style="display:none;"
        @click.self="showJpfModal = false"
    >
        <div
            x-show="showJpfModal"
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
                <h2 class="text-sm font-bold text-gray-800 uppercase tracking-widest" x-text="isEditing ? 'Edit Job Posting (JPF)' : 'Create Job Posting (JPF)'"></h2>
                <button @click="showJpfModal = false" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Split body --}}
            <div class="flex flex-1 overflow-hidden gap-4 p-4">

                {{-- RIGHT: INPUT FORM --}}
                <div class="w-[42%] bg-white rounded-xl shadow border border-gray-200 flex flex-col overflow-hidden shrink-0 order-last">
                    <div class="px-5 py-3 border-b bg-blue-700 rounded-t-xl">
                        <p class="text-xs font-bold text-white uppercase tracking-wider">Fill Up Form</p>
                    </div>
                    <form @submit.prevent="submitJPF()" class="flex-1 overflow-y-auto px-5 py-6 space-y-8 bg-white">
                        
                        {{-- REQUISITION DETAILS --}}
                        <div class="space-y-4">
                            <h3 class="text-xs font-black text-blue-700 uppercase tracking-[0.2em] border-b pb-2">Requisition Details</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Job Placement No.</label>
                                    <input type="text" x-model="jpfForm.jobId" placeholder="Auto-generated" readonly class="w-full text-sm px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-500">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Related MRF No. <span class="text-red-500">*</span></label>
                                    <select x-model="jpfForm.mrfId" @change="onJpfMrfChange()" required
                                        class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-100 outline-none bg-white">
                                        <option value="">Select Approved MRF...</option>
                                        <template x-for="mrf in approvedMRFs" :key="mrf.id">
                                            <option :value="mrf.id" x-text="`${mrf.request_id || 'MRF'} - ${mrf.position || 'No position'} (${mrf.department || 'No department'})`"></option>
                                        </template>
                                    </select>
                                    <input type="hidden" x-model="jpfForm.relatedMrfNo">
                                    <p class="text-[11px] mt-1"
                                       :class="approvedMRFs.length ? 'text-gray-500' : 'text-red-500'"
                                       x-text="approvedMRFs.length ? 'Only approved MRF records are available here.' : 'No approved MRF available. Approve an MRF first before creating JPF.'"></p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Date Opened</label>
                                    <input type="date" x-model="jpfForm.dateOpened" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-100 outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Hiring Status</label>
                                    <div class="flex flex-wrap gap-2 mt-1">
                                        <template x-for="st in ['Open', 'Urgent', 'Confidential', 'Closed']" :key="st">
                                            <label class="flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-gray-200 text-[11px] font-bold cursor-pointer transition"
                                                :class="jpfForm.hiringStatus === st ? 'bg-blue-600 border-blue-600 text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'">
                                                <input type="radio" x-model="jpfForm.hiringStatus" :value="st" class="hidden">
                                                <span x-text="st"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- COMPANY DETAILS --}}
                        <div class="space-y-4 border border-blue-100 bg-blue-50/40 rounded-xl p-4">
                            <h3 class="text-xs font-black text-blue-700 uppercase tracking-[0.2em] border-b border-blue-100 pb-2">Company Details / Organizational Link</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Company Name</label>
                                    <input type="text" x-model="jpfForm.companyName" placeholder="John Kelly & Company" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-100 outline-none bg-white">
                                </div>

                                <div class="col-span-2">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Organizational Address / Work Location</label>
                                    <select x-model="jpfForm.orgAddressId" @change="onJpfAddressChange()" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white outline-none">
                                        <option value="">Select Organizational Address...</option>
                                        <template x-for="address in organizationalAddresses" :key="address.id">
                                            <option :value="address.id" x-text="address.full_address"></option>
                                        </template>
                                    </select>
                                    <p class="text-[11px] text-gray-500 mt-1" x-show="selectedJpfAddress" x-text="selectedJpfAddress?.full_address"></p>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Office / Branch / Site</label>
                                    <input type="text" x-model="jpfForm.officeBranchSite" readonly class="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-gray-700 outline-none">
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Department / Unit</label>
                                    <select x-model="jpfForm.orgDepartmentId" @change="onJpfDepartmentChange()" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white outline-none">
                                        <option value="">Select Department...</option>
                                        <template x-for="department in jpfFilteredDepartments" :key="department.id">
                                            <option :value="department.id" x-text="department.department_name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Hiring Manager</label>
                                    <input type="text" x-model="jpfForm.hiringManager" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-100 outline-none bg-white">
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Department Superior</label>
                                    <input type="text" x-model="jpfForm.departmentSuperior" readonly class="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-gray-700 outline-none">
                                </div>
                            </div>
                        </div>

                        {{-- POSITION DETAILS --}}
                        <div class="space-y-4">
                            <h3 class="text-xs font-black text-blue-700 uppercase tracking-[0.2em] border-b pb-2">Position Details</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Position Title</label>
                                    <select x-model="jpfForm.orgPositionId" @change="onJpfPositionChange()" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white outline-none">
                                        <option value="">Select Position from Organizational...</option>
                                        <template x-for="position in jpfFilteredPositions" :key="position.id">
                                            <option :value="position.id" x-text="position.position_name"></option>
                                        </template>
                                    </select>
                                    <input type="hidden" x-model="jpfForm.position">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">No. of Vacancies</label>
                                    <input type="number" x-model="jpfForm.noOfVacancies" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Position Level</label>
                                    <select x-model="jpfForm.positionLevel" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white outline-none">
                                        <option value="">Select Level...</option>
                                        <template x-for="lv in ['Rank & File', 'Staff', 'Senior Staff', 'Supervisor', 'Manager', 'Executive']" :key="lv">
                                            <option :value="lv" x-text="lv"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Employment Type</label>
                                    <input type="text" x-model="jpfForm.employmentType" readonly placeholder="Auto-filled from selected MRF"
                                        class="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-gray-700 outline-none">
                                    <p class="text-[11px] text-gray-500 mt-1">Based on the employment type selected in the related MRF.</p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Reports To</label>
                                    <input type="text" x-model="jpfForm.reportsTo" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg outline-none">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Work Location</label>
                                    <input type="text" x-model="jpfForm.workLocation" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg outline-none">
                                </div>
                            </div>
                        </div>

                        {{-- SALARY OFFER --}}
                        <div class="space-y-4 border border-green-100 bg-green-50/40 rounded-xl p-4">
                            <h3 class="text-xs font-black text-green-700 uppercase tracking-[0.2em] border-b border-green-100 pb-2">Salary Offer / Payroll Link</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Salary Grade from Payroll</label>
                                    <select x-model="jpfForm.salaryGradeId" @change="onJpfSalaryGradeChange()" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white outline-none">
                                        <option value="">Select Salary Grade...</option>
                                        <template x-for="grade in salaryGrades" :key="grade.id">
                                            <option :value="grade.id" x-text="`${grade.code || ''} - ${grade.name || ''} ${grade.monthly_basic_pay ? '(₱' + Number(grade.monthly_basic_pay).toLocaleString() + ')' : ''}`"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Minimum Salary Offer (₱)</label>
                                    <input type="number" x-model="jpfForm.minSalary" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg outline-none bg-white">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Maximum Salary Offer (₱)</label>
                                    <input type="number" x-model="jpfForm.maxSalary" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg outline-none bg-white">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Salary Grade</label>
                                    <input type="text" x-model="jpfForm.salaryGrade" readonly class="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-gray-700 outline-none">
                                </div>
                            </div>
                        </div>

                        {{-- WAGE COMPLIANCE --}}
                        <div class="space-y-4">
                            <h3 class="text-xs font-black text-blue-700 uppercase tracking-[0.2em] border-b pb-2">Philippine Minimum Wage Compliance</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Applicable Region</label>
                                    <input type="text" x-model="jpfForm.applicableRegion" class="w-full text-sm px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-500" readonly>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Applicable Area</label>
                                    <input type="text" x-model="jpfForm.applicableArea" readonly
                                        class="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-gray-700 outline-none">
                                    <p class="text-[11px] text-gray-500 mt-1">Auto-filled from the MRF organizational address.</p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Current Daily Min Wage (₱)</label>
                                    <input type="number" x-model="jpfForm.dailyMinWage" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Monthly Equivalent (₱)</label>
                                    <input type="number" x-model="jpfForm.monthlyEquivalent" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg outline-none">
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-4 mt-2">
                                <template x-for="wc in ['Confirmed compliant with applicable wage order', 'Above minimum wage', 'With allowances / premiums']" :key="wc">
                                    <label class="flex items-center gap-2 text-xs font-semibold text-gray-600 cursor-pointer">
                                        <input type="checkbox" x-model="jpfForm.wageCompliance" :value="wc" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500">
                                        <span x-text="wc"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        {{-- BENEFITS PACKAGE --}}
                        <div class="space-y-4">
                            <h3 class="text-xs font-black text-blue-700 uppercase tracking-[0.2em] border-b pb-2">Benefits Package</h3>
                            <div class="grid grid-cols-2 gap-y-3">
                                <template x-for="bf in ['SSS', 'PhilHealth', 'Pag-IBIG', '13th Month Pay', 'Service Incentive Leave', 'HMO', 'Incentives / Commission', 'Overtime Pay', 'Holiday Pay']" :key="bf">
                                    <label class="flex items-center gap-3 text-xs font-bold text-gray-700 group cursor-pointer">
                                        <div class="relative w-5 h-5 flex items-center justify-center border-2 rounded transition-colors group-hover:border-blue-400"
                                            :class="jpfForm.benefits.includes(bf) ? 'bg-blue-600 border-blue-600' : 'bg-white border-gray-300'">
                                            <input type="checkbox" x-model="jpfForm.benefits" :value="bf" class="hidden">
                                            <svg x-show="jpfForm.benefits.includes(bf)" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="4" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <span x-text="bf"></span>
                                    </label>
                                </template>
                                <div class="col-span-2 flex items-center gap-3 mt-2">
                                    <span class="text-xs font-bold text-gray-400 uppercase">Others:</span>
                                    <input type="text" x-model="jpfForm.otherBenefits" class="flex-1 border-b border-gray-300 focus:border-blue-500 outline-none text-sm py-1">
                                </div>
                            </div>
                        </div>

                        {{-- WORK SCHEDULE --}}
                        <div class="space-y-4">
                            <h3 class="text-xs font-black text-blue-700 uppercase tracking-[0.2em] border-b pb-2">Work Schedule</h3>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Payroll Level <span class="text-red-500">*</span></label>
                                <select x-model="jpfForm.payrollLevelId" @change="onJpfPayrollLevelChange()"
                                    class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white outline-none">
                                    <option value="">Select Payroll Level...</option>
                                    <template x-for="level in jpfFilteredPayrollLevels" :key="level.id">
                                        <option :value="level.id" x-text="formatPayrollLevelOption(level)"></option>
                                    </template>
                                </select>
                                <p class="text-[11px] text-gray-500 mt-1">
                                    Work Schedule and Rest Day/s are auto-filled from the selected Payroll Level.
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <template x-for="ws in jpfWorkScheduleOptions" :key="ws.label + '-' + jpfForm.workSchedule.join('|') + '-' + jpfForm.payrollLevelId">
                                    <label class="flex items-center gap-2 text-xs font-semibold text-gray-600 p-2 rounded-lg border border-gray-100 transition"
                                        :class="isJpfWorkScheduleChecked(ws) ? 'bg-blue-50 border-blue-300 text-blue-700' : 'hover:bg-gray-50'">
                                        <input type="checkbox"
                                            :checked="isJpfWorkScheduleChecked(ws)"
                                            @change="toggleJpfWorkSchedule(ws, $event.target.checked)"
                                            :disabled="jpfForm.payrollLevelId"
                                            class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 disabled:opacity-80">
                                        <span x-text="ws.label"></span>
                                    </label>
                                </template>
                            </div>
                            <div class="flex items-center gap-3 pt-2">
                                <label class="text-[10px] font-bold text-gray-400 uppercase">Rest Day/s:</label>
                                <input type="text" x-model="jpfForm.restDays" placeholder="e.g. Sunday" :readonly="jpfForm.payrollLevelId" class="flex-1 border-b border-gray-300 focus:border-blue-500 outline-none text-sm py-1" :class="jpfForm.payrollLevelId ? 'bg-gray-100 text-gray-700 px-2 rounded' : ''">
                            </div>
                        </div>

                        {{-- JOB REQUIREMENTS --}}
                        <div class="space-y-4">
                            <h3 class="text-xs font-black text-blue-700 uppercase tracking-[0.2em] border-b pb-2">Job Requirements</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Education</label>
                                    <input type="text" x-model="jpfForm.education" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Experience</label>
                                    <input type="text" x-model="jpfForm.experience" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Skills</label>
                                    <input type="text" x-model="jpfForm.skills" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Licenses / Certifications</label>
                                    <input type="text" x-model="jpfForm.licenses" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Preferred Qualifications</label>
                                    <textarea x-model="jpfForm.preferredQualifications" rows="2" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg outline-none bg-gray-50/50 resize-none"></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- DUTIES & RESPONSIBILITIES --}}
                        <div class="space-y-4">
                            <h3 class="text-xs font-black text-blue-700 uppercase tracking-[0.2em] border-b pb-2">Duties & Responsibilities</h3>
                            <textarea x-model="jpfForm.duties" rows="6" placeholder="Outline the key responsibilities..." class="w-full text-sm px-4 py-3 border border-gray-300 rounded-xl outline-none bg-gray-50/50 resize-none"></textarea>
                        </div>

                        {{-- RECRUITMENT CHANNELS --}}
                        <div class="space-y-4">
                            <h3 class="text-xs font-black text-blue-700 uppercase tracking-[0.2em] border-b pb-2">Recruitment Channels</h3>
                            <div class="grid grid-cols-3 gap-3">
                                <template x-for="ch in ['JobStreet', 'Indeed', 'Facebook', 'Referral', 'Walk-in', 'School / Campus Hiring', 'Internal Posting', 'Agency']" :key="ch">
                                    <label class="flex items-center gap-2 text-xs font-semibold text-gray-600 cursor-pointer">
                                        <input type="checkbox" x-model="jpfForm.channels" :value="ch" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500">
                                        <span x-text="ch"></span>
                                    </label>
                                </template>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-bold text-gray-400 uppercase">Others:</span>
                                <input type="text" x-model="jpfForm.otherChannel" class="flex-1 border-b border-gray-300 focus:border-blue-500 outline-none text-sm py-1">
                            </div>
                        </div>

                        {{-- SCREENING FLOW --}}
                        <div class="space-y-4">
                            <h3 class="text-xs font-black text-blue-700 uppercase tracking-[0.2em] border-b pb-2">Screening Flow</h3>
                            <div class="grid grid-cols-2 gap-y-3 px-2">
                                <template x-for="(sf, index) in ['Resume Screening', 'Initial Interview', 'Assessment Exam', 'Final Interview', 'Reference Check', 'Job Offer', 'Pre-employment Requirements', 'Deployment']" :key="sf">
                                    <label class="flex items-center gap-3 text-xs font-bold text-gray-700 group cursor-pointer">
                                        <div class="relative w-5 h-5 flex items-center justify-center border-2 rounded-full transition-colors"
                                            :class="jpfForm.screeningFlow.includes(sf) ? 'bg-blue-600 border-blue-600' : 'bg-white border-gray-300'">
                                            <input type="checkbox" x-model="jpfForm.screeningFlow" :value="sf" class="hidden">
                                            <span x-show="jpfForm.screeningFlow.includes(sf)" class="text-white text-[10px]" x-text="jpfForm.screeningFlow.indexOf(sf) + 1"></span>
                                        </div>
                                        <span x-text="sf"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        {{-- TARGET TIMELINE --}}
                        <div class="space-y-4">
                            <h3 class="text-xs font-black text-blue-700 uppercase tracking-[0.2em] border-b pb-2">Target Timeline</h3>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Date Needed</label>
                                    <input type="date" x-model="jpfForm.dateNeeded" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Posting Start</label>
                                    <input type="date" x-model="jpfForm.postingStartDate" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Target Hire Date</label>
                                    <input type="date" x-model="jpfForm.targetHireDate" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg outline-none">
                                </div>
                            </div>
                        </div>

                        {{-- APPROVALS --}}
                        <div class="space-y-4">
                            <h3 class="text-xs font-black text-blue-700 uppercase tracking-[0.2em] border-b pb-2">Approvals</h3>

                            <div class="bg-blue-50 border border-blue-100 text-blue-700 text-xs rounded-xl px-4 py-3">
                                Select the assigned approver for each level. Names, statuses, and approval dates are recorded automatically after system approval.
                            </div>

                            <div class="grid grid-cols-2 gap-6 bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                                <template x-for="approvalField in [
                                    { key: 'humanCapitalApproval', label: 'Human Capital' },
                                    { key: 'hiringManagerApproval', label: 'Hiring Manager' },
                                    { key: 'financeApproval', label: 'Finance' },
                                    { key: 'presidentApproval', label: 'President / Final' }
                                ]" :key="approvalField.key">
                                    <div class="space-y-3">
                                        <p class="text-[10px] font-black text-gray-400 uppercase border-b w-fit pb-0.5" x-text="approvalField.label"></p>

                                        <select
                                            x-model="jpfForm[approvalField.key].approver_id"
                                            @change="applyApprover(approvalField.key)"
                                            class="w-full text-sm bg-white border border-gray-200 rounded px-2 py-2 focus:ring-2 focus:ring-blue-100 outline-none"
                                        >
                                            <option value="">Select approver...</option>
                                            <template x-for="user in approvalUsers" :key="approvalField.key + '-' + user.id">
                                                <option
                                                    :value="user.id"
                                                    x-text="`${user.name} - ${user.role}${user.position ? ' / ' + user.position : ''}`"
                                                ></option>
                                            </template>
                                        </select>

                                        <div class="grid grid-cols-2 gap-2 text-[11px]">
                                            <div class="bg-white border border-gray-200 rounded-lg px-2 py-2">
                                                <p class="text-[9px] uppercase font-bold text-gray-400">Status</p>
                                                <p class="font-bold" x-text="approvalDisplayStatus(jpfForm[approvalField.key])"></p>
                                            </div>
                                            <div class="bg-white border border-gray-200 rounded-lg px-2 py-2">
                                                <p class="text-[9px] uppercase font-bold text-gray-400">Approved Date</p>
                                                <p class="font-bold" x-text="approvalDisplayDate(jpfForm[approvalField.key])"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- STATUS --}}
                        <div class="space-y-4">
                            <h3 class="text-xs font-black text-blue-700 uppercase tracking-[0.2em] border-b pb-2">Status</h3>

                            <div class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-xs text-gray-600 leading-relaxed">
                                <p><strong>Status rule:</strong> A JPF can only become <span class="font-bold text-blue-700">Posted</span> after all approvers are approved.</p>
                                <p class="mt-1" x-text="jpfStatusHelpText()"></p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <template x-for="st in jpfStatusOptions" :key="st">
                                    <label
                                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-[11px] font-bold transition"
                                        :class="jpfForm.status === st
                                            ? 'bg-gray-800 border-gray-800 text-white cursor-pointer'
                                            : (jpfStatusOptionAllowed(st) ? 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50 cursor-pointer' : 'bg-gray-100 border-gray-200 text-gray-400 cursor-not-allowed opacity-60')"
                                    >
                                        <input
                                            type="radio"
                                            x-model="jpfForm.status"
                                            :value="st"
                                            :disabled="!jpfStatusOptionAllowed(st)"
                                            class="hidden"
                                        >
                                        <span x-text="st"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-8 pb-4 border-t border-gray-100">
                            <button type="button" @click="showJpfModal = false"
                                class="px-6 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition">
                                Discard
                            </button>
                            <button type="submit"
                                class="px-8 py-2.5 text-sm bg-blue-700 hover:bg-blue-800 text-white rounded-xl font-black shadow-lg shadow-blue-100 transition active:scale-95 uppercase tracking-widest">
                                <span x-text="isEditing ? 'Update JPF Record' : 'Save JPF Record'"></span>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- LEFT: LIVE JPF DOCUMENT PREVIEW --}}
                <div class="flex-1 bg-white rounded-xl shadow border border-gray-200 flex flex-col overflow-hidden">
                    <div class="px-5 py-3 border-b bg-gray-50 rounded-t-xl shrink-0 flex items-center justify-between">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Live Preview</p>
                        <div class="flex items-center gap-2">

                            <button type="button" @click="downloadPDF('jpf-doc-create')" class="text-xs px-3 py-1.5 bg-white hover:bg-gray-50 border border-gray-300 rounded text-gray-700 font-semibold flex items-center gap-1 transition shadow-sm">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Download PDF
                            </button>
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto p-5 bg-gray-100">
                        <div id="jpf-doc-create" class="border border-gray-400 text-[10px] text-gray-800 font-sans w-[794px] shrink-0 leading-tight mx-auto shadow-sm bg-white p-6 min-h-[1000px]">
                            {{-- Form Header with Logo --}}
                            <div class="flex items-center justify-center pb-4 pt-2 border-b border-gray-400">
                                <img src="{{ asset('images/imaglogo.png') }}" onerror="this.src='{{ asset('images/imag1logo.jpg') }}'" alt="John Kelly & Company" class="h-14 w-auto object-contain mix-blend-multiply">
                            </div>

                            {{-- Title --}}
                            <div class="bg-gray-800 text-white text-center font-black py-2 text-sm tracking-widest uppercase mb-4">
                                Job Placement Form (JPF)
                            </div>

                            <div class="space-y-4">
                                {{-- REQUISITION DETAILS --}}
                                <div class="border border-gray-400">
                                    <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Requisition Details</div>
                                    <div class="p-3 grid grid-cols-2 gap-y-2">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-32 shrink-0">Job Placement No.:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1rem] italic" x-text="jpfForm.jobId"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-32 shrink-0">Related MRF No.:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1rem] italic" x-text="jpfForm.relatedMrfNo"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-32 shrink-0">Date Opened:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="jpfForm.dateOpened"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-32 shrink-0">Hiring Status:</span>
                                            <div class="flex gap-3">
                                                <template x-for="st in ['Open', 'Urgent', 'Confidential', 'Closed']" :key="st">
                                                    <div class="flex items-center gap-1">
                                                        <span class="w-3 h-3 border border-gray-400 flex items-center justify-center" :class="jpfForm.hiringStatus === st ? 'bg-gray-800' : ''">
                                                            <svg x-show="jpfForm.hiringStatus === st" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                                                        </span>
                                                        <span x-text="st"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- COMPANY DETAILS --}}
                                <div class="border border-gray-400">
                                    <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Company Details</div>
                                    <div class="p-3 grid grid-cols-1 gap-y-2 text-[11px]">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-40 shrink-0">Company Name:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="jpfForm.companyName"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-40 shrink-0">Office / Branch / Site:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="jpfForm.officeBranchSite"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-40 shrink-0">Department / Unit:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="jpfForm.departmentUnit"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-40 shrink-0">Hiring Manager:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="jpfForm.hiringManager"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-40 shrink-0">Department Superior:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="jpfForm.departmentSuperior"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- POSITION DETAILS --}}
                                <div class="border border-gray-400">
                                    <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Position Details</div>
                                    <div class="p-3 space-y-3">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-32 shrink-0">Position Title:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1.2rem] text-sm font-black" x-text="jpfForm.position"></span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold w-32 shrink-0">No. of Vacancies:</span>
                                                <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="jpfForm.noOfVacancies"></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold w-32 shrink-0">Reports To:</span>
                                                <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="jpfForm.reportsTo"></span>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4 pt-2">
                                            <div>
                                                <p class="font-bold mb-2">Position Level:</p>
                                                <div class="grid grid-cols-2 gap-y-1">
                                                    <template x-for="lv in ['Rank & File', 'Staff', 'Senior Staff', 'Supervisor', 'Manager', 'Executive']" :key="lv">
                                                        <div class="flex items-center gap-2">
                                                            <span class="w-3 h-3 border border-gray-400 flex items-center justify-center" :class="jpfForm.positionLevel === lv ? 'bg-gray-800' : ''">
                                                                <svg x-show="jpfForm.positionLevel === lv" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                                                            </span>
                                                            <span x-text="lv"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="font-bold mb-2">Employment Type:</p>
                                                <div class="grid grid-cols-2 gap-y-1">
                                                    <template x-for="et in employmentTypeOptions" :key="et">
                                                        <div class="flex items-center gap-2">
                                                            <span class="w-3 h-3 border border-gray-400 flex items-center justify-center" :class="jpfForm.employmentType === et ? 'bg-gray-800' : ''">
                                                                <svg x-show="jpfForm.employmentType === et" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                                                            </span>
                                                            <span x-text="et"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 pt-2">
                                            <span class="font-bold w-32 shrink-0">Work Location:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="jpfForm.workLocation"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- SALARY OFFER --}}
                                <div class="border border-gray-400">
                                    <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Salary Offer / Compliance</div>
                                    <div class="p-3 grid grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold w-32 shrink-0">Minimum Offer:</span>
                                                <span class="border-b border-gray-300 flex-1 min-h-[1rem]">₱ <span x-text="parseFloat(jpfForm.minSalary || 0).toLocaleString()"></span></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold w-32 shrink-0">Maximum Offer:</span>
                                                <span class="border-b border-gray-300 flex-1 min-h-[1rem]">₱ <span x-text="parseFloat(jpfForm.maxSalary || 0).toLocaleString()"></span></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold w-32 shrink-0">Salary Grade:</span>
                                                <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="jpfForm.salaryGrade"></span>
                                            </div>
                                        </div>
                                        <div class="space-y-2 border-l border-gray-200 pl-4">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold w-32 shrink-0 text-[8px] uppercase">Daily Min Wage:</span>
                                                <span class="border-b border-gray-300 flex-1 min-h-[1rem]">₱ <span x-text="parseFloat(jpfForm.dailyMinWage || 0).toLocaleString()"></span></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold w-32 shrink-0 text-[8px] uppercase">Monthly Equiv:</span>
                                                <span class="border-b border-gray-300 flex-1 min-h-[1rem]">₱ <span x-text="parseFloat(jpfForm.monthlyEquivalent || 0).toLocaleString()"></span></span>
                                            </div>
                                            <div class="space-y-1 pt-1">
                                                <template x-for="wc in ['Confirmed compliant with applicable wage order', 'Above minimum wage', 'With allowances / premiums']" :key="wc">
                                                    <div class="flex items-center gap-1 text-[8px]">
                                                        <span class="w-3 h-3 border border-gray-400 flex items-center justify-center shrink-0" :class="jpfForm.wageCompliance.includes(wc) ? 'bg-gray-800' : ''">
                                                            <svg x-show="jpfForm.wageCompliance.includes(wc)" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                                                        </span>
                                                        <span x-text="wc"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- BENEFITS --}}
                                <div class="border border-gray-400">
                                    <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Benefits Package</div>
                                    <div class="p-3 grid grid-cols-3 gap-y-1">
                                        <template x-for="bf in ['SSS', 'PhilHealth', 'Pag-IBIG', '13th Month Pay', 'Service Incentive Leave', 'HMO', 'Incentives / Commission', 'Overtime Pay', 'Holiday Pay']" :key="bf">
                                            <div class="flex items-center gap-2">
                                                <span class="w-3 h-3 border border-gray-400 flex items-center justify-center shrink-0" :class="jpfForm.benefits.includes(bf) ? 'bg-gray-800' : ''">
                                                    <svg x-show="jpfForm.benefits.includes(bf)" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                                                </span>
                                                <span x-text="bf"></span>
                                            </div>
                                        </template>
                                        <div class="col-span-3 pt-2 italic text-gray-500" x-show="jpfForm.otherBenefits">
                                            Others: <span x-text="jpfForm.otherBenefits"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- WORK SCHEDULE --}}
                                <div class="border border-gray-400">
                                    <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Work Schedule</div>
                                    <div class="p-3 grid grid-cols-2 gap-y-1">
                                        <template x-for="ws in ['Monday to Friday – 8:00 AM to 5:00 PM', 'Monday to Saturday – 8:00 AM to 5:00 PM', 'Shifting Schedule', 'Night Shift', 'Hybrid', 'Work From Home', 'Flexible']" :key="ws">
                                            <div class="flex items-center gap-2">
                                                <span class="w-3 h-3 border border-gray-400 flex items-center justify-center shrink-0" :class="jpfForm.workSchedule.includes(ws) ? 'bg-gray-800' : ''">
                                                    <svg x-show="jpfForm.workSchedule.includes(ws)" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                                                </span>
                                                <span x-text="ws"></span>
                                            </div>
                                        </template>
                                        <div class="col-span-2 pt-2 border-t mt-2">
                                            <span class="font-bold">Rest Day/s:</span> <span x-text="jpfForm.restDays"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- JOB REQUIREMENTS --}}
                                <div class="border border-gray-400">
                                    <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Job Requirements & Duties</div>
                                    <div class="p-3 grid grid-cols-2 gap-x-6 gap-y-3">
                                        <div class="space-y-2">
                                            <div><p class="font-bold underline uppercase text-[8px] mb-0.5">Education:</p><p x-text="jpfForm.education" class="pl-2"></p></div>
                                            <div><p class="font-bold underline uppercase text-[8px] mb-0.5">Experience:</p><p x-text="jpfForm.experience" class="pl-2"></p></div>
                                            <div><p class="font-bold underline uppercase text-[8px] mb-0.5">Skills:</p><p x-text="jpfForm.skills" class="pl-2"></p></div>
                                            <div><p class="font-bold underline uppercase text-[8px] mb-0.5">Licenses:</p><p x-text="jpfForm.licenses" class="pl-2"></p></div>
                                        </div>
                                        <div class="border-l pl-4 border-gray-200">
                                            <p class="font-bold underline uppercase text-[8px] mb-1">Duties & Responsibilities:</p>
                                            <div class="whitespace-pre-wrap text-[9px] leading-relaxed" x-text="jpfForm.duties"></div>
                                        </div>
                                    </div>
                                </div>

                                {{-- FLOW & CHANNELS --}}
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="border border-gray-400 h-full">
                                        <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Recruitment Channels</div>
                                        <div class="p-3 grid grid-cols-1 gap-y-1">
                                            <template x-for="ch in ['JobStreet', 'Indeed', 'Facebook', 'Referral', 'Walk-in', 'School / Campus Hiring', 'Internal Posting', 'Agency']" :key="ch">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2.5 h-2.5 border border-gray-400 flex items-center justify-center shrink-0" :class="jpfForm.channels.includes(ch) ? 'bg-gray-800' : ''"></span>
                                                    <span x-text="ch"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="border border-gray-400 h-full">
                                        <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Screening Flow</div>
                                        <div class="p-3 space-y-1">
                                            <template x-for="sf in ['Resume Screening', 'Initial Interview', 'Assessment Exam', 'Final Interview', 'Reference Check', 'Job Offer', 'Pre-employment Requirements', 'Deployment']" :key="sf">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-3 h-3 border border-gray-400 flex items-center justify-center shrink-0 rounded-full text-[7px]" :class="jpfForm.screeningFlow.includes(sf) ? 'bg-gray-800 text-white' : ''" x-text="jpfForm.screeningFlow.includes(sf) ? (jpfForm.screeningFlow.indexOf(sf) + 1) : ''"></span>
                                                    <span x-text="sf"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                {{-- TIMELINE --}}
                                <div class="border border-gray-400">
                                    <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Target Timeline</div>
                                    <div class="p-3 grid grid-cols-3 divide-x divide-gray-200 text-center">
                                        <div><p class="font-bold uppercase text-[7px] text-gray-400 mb-1">Date Needed</p><p class="font-black" x-text="jpfForm.dateNeeded"></p></div>
                                        <div><p class="font-bold uppercase text-[7px] text-gray-400 mb-1">Posting Start</p><p class="font-black" x-text="jpfForm.postingStartDate"></p></div>
                                        <div><p class="font-bold uppercase text-[7px] text-gray-400 mb-1">Target Hire Date</p><p class="font-black" x-text="jpfForm.targetHireDate"></p></div>
                                    </div>
                                </div>

                                {{-- APPROVALS --}}
                                <div class="border border-gray-400">
                                    <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Approvals</div>
                                    <div class="p-3 grid grid-cols-4 gap-4 text-center">
                                        <template x-for="ap in [
                                            { label: 'Human Capital', data: jpfForm.humanCapitalApproval },
                                            { label: 'Hiring Manager', data: jpfForm.hiringManagerApproval },
                                            { label: 'Finance', data: jpfForm.financeApproval },
                                            { label: 'President / Final', data: jpfForm.presidentApproval }
                                        ]" :key="ap.label">
                                            <div>
                                                <p class="text-[7px] text-gray-400 uppercase mb-2" x-text="ap.label"></p>
                                                <p class="font-bold border-b border-gray-200 min-h-[1.2rem]" x-text="approvalDisplayName(ap.data)"></p>
                                                <p class="text-[7px] mt-1 uppercase font-bold" x-text="approvalDisplayStatus(ap.data)"></p>
                                                <p class="text-[8px]" x-text="approvalDisplayDate(ap.data)"></p>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>{{-- end split body --}}
        </div>
    </div>

    {{-- ===================== JPF VIEW SLIDE-OVER ===================== --}}
    <div x-show="showJpfViewModal" class="fixed inset-0 overflow-hidden z-[9999]" style="display:none;">
        <div @click="showJpfViewModal = false" class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity"
            x-show="showJpfViewModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="fixed inset-y-0 right-0 w-[95vw] flex pointer-events-none">
            <div x-show="showJpfViewModal" 
                x-transition:enter="transform transition ease-in-out duration-500"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="h-full w-full bg-white shadow-2xl flex flex-col overflow-hidden pointer-events-auto">
            {{-- Toolbar --}}
            <div class="h-16 px-8 border-b border-gray-100 flex items-center justify-between bg-white shadow-sm shrink-0">
                <div class="flex items-center gap-4">
                    <button @click="showJpfViewModal = false" class="p-2 hover:bg-gray-100 rounded-full transition text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <div>
                        <h2 class="text-lg font-bold text-gray-800" x-text="viewJpfData.job_id"></h2>
                        <p class="text-xs text-gray-500 uppercase tracking-widest font-semibold" x-text="viewJpfData.position"></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="downloadPDF('jpf-doc-view')" class="px-4 py-2 text-sm font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download PDF
                    </button>
                    <button @click="window.print()" class="px-4 py-2 text-sm font-semibold text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print Details
                    </button>
                    <button @click="showJpfViewModal = false" class="px-6 py-2 text-sm font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition shadow-md shadow-blue-100 uppercase tracking-wide">Close</button>
                </div>
            </div>

            <div class="flex-grow overflow-auto bg-gray-50/50 p-8 shadow-inner">
                <div x-show="viewJpfData" class="w-[794px] mx-auto mb-4 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-black text-gray-900 uppercase tracking-[0.18em]">Approval Workflow</p>
                            <p class="text-[11px] text-gray-500 mt-1">System approvals are controlled here. The printable JPF below stays clean and formal.</p>
                        </div>
                        <span class="shrink-0 text-[10px] px-3 py-1 rounded-full bg-gray-50 text-gray-600 font-bold border border-gray-200">System Approval</span>
                    </div>

                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-4 gap-2">
                            <template x-for="ap in approvalLevels()" :key="'workflow-' + ap.level">
                                <div class="rounded-lg border border-gray-200 bg-gray-50/50 px-3 py-2 min-h-[78px]">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-[9px] font-black uppercase tracking-wider text-gray-500 truncate" x-text="ap.label"></p>
                                        <span
                                            class="inline-flex px-2 py-0.5 rounded-full border text-[8px] font-black uppercase shrink-0"
                                            :class="approvalBadgeClass(ap.data)"
                                            x-text="approvalDisplayStatus(ap.data)"
                                        ></span>
                                    </div>
                                    <p class="mt-2 text-[12px] font-bold text-gray-900 truncate" x-text="approvalDisplayName(ap.data)"></p>
                                    <p class="text-[10px] text-gray-500 truncate" x-text="ap.data?.email || ap.data?.position || ap.data?.role || 'No approver selected'"></p>
                                    <p class="mt-1 text-[10px] text-gray-400" x-text="approvalDisplayDate(ap.data)"></p>
                                </div>
                            </template>
                        </div>

                        <template x-if="currentActionApproval()">
                            <div class="rounded-xl border border-blue-200 bg-blue-50/60 px-4 py-3 flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-blue-700">Your Approval Action</p>
                                    <p class="mt-1 text-sm font-bold text-gray-900">
                                        <span x-text="currentActionApproval().label"></span>
                                        <span class="text-gray-400 font-semibold"> • </span>
                                        <span x-text="approvalDisplayName(currentActionApproval().data)"></span>
                                    </p>
                                    <p class="text-[11px] text-gray-500">Parallel approval is enabled. You can update your own assigned approval level anytime while the JPF is For Approval.</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button" @click="approveJpfLevel(currentActionApproval().level, 'Approved')" class="px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-xs font-bold transition shadow-sm">Approve</button>
                                    <button type="button" @click="approveJpfLevel(currentActionApproval().level, 'Hold')" class="px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition shadow-sm">Hold</button>
                                    <button type="button" @click="approveJpfLevel(currentActionApproval().level, 'Cancelled')" class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition shadow-sm">Cancel</button>
                                </div>
                            </div>
                        </template>

                        <template x-if="!currentActionApproval() && waitingApproval()">
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-gray-500">Waiting for Approval</p>
                                    <p class="mt-1 text-sm font-bold text-gray-900">
                                        <span x-text="waitingApproval().label"></span>
                                        <span class="text-gray-400 font-semibold"> • </span>
                                        <span x-text="approvalDisplayName(waitingApproval().data)"></span>
                                    </p>
                                    <p class="text-[11px] text-gray-500 mt-1">No action is available for your account because the remaining pending approval levels are assigned to other users.</p>
                                </div>
                                <span class="shrink-0 px-3 py-1 rounded-full bg-white border border-gray-200 text-[10px] font-black uppercase text-gray-500">Read only</span>
                            </div>
                        </template>

                        <template x-if="!currentActionApproval() && !waitingApproval() && String(viewJpfData?.status || '').toLowerCase() === 'draft'">
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-gray-500">Draft Record</p>
                                    <p class="text-[11px] text-gray-500 mt-1">This JPF is saved as Draft. Edit it and set the status to For Approval before approvers can act on it.</p>
                                </div>
                                <span class="shrink-0 px-3 py-1 rounded-full bg-white border border-gray-200 text-[10px] font-black uppercase text-gray-500">No action</span>
                            </div>
                        </template>

                        <template x-if="!currentActionApproval() && !waitingApproval() && String(viewJpfData?.status || '').toLowerCase() !== 'draft'">
                            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-green-700">Approval Workflow Complete</p>
                                    <p class="text-[11px] text-green-700 mt-1">No pending approval action is available for this JPF.</p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div id="jpf-doc-view" class="border border-gray-400 text-[10px] text-gray-800 font-sans w-[794px] shrink-0 leading-tight mx-auto shadow-sm bg-white p-6 min-h-[1000px]">
                    <template x-if="viewJpfData">
                        <div class="flex flex-col h-full">
                            {{-- Form Header with Logo --}}
                            <div class="flex items-center justify-center pb-4 pt-2 border-b border-gray-400">
                                <img src="{{ asset('images/imaglogo.png') }}" onerror="this.src='{{ asset('images/imag1logo.jpg') }}'" alt="John Kelly & Company" class="h-14 w-auto object-contain mix-blend-multiply">
                            </div>

                            {{-- Title --}}
                            <div class="bg-gray-800 text-white text-center font-black py-2 text-sm tracking-widest uppercase mb-4">
                                Job Placement Form (JPF)
                            </div>

                            <div class="space-y-4">
                                {{-- REQUISITION DETAILS --}}
                                <div class="border border-gray-400">
                                    <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Requisition Details</div>
                                    <div class="p-3 grid grid-cols-2 gap-y-2">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-32 shrink-0">Job Placement No.:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1rem] italic" x-text="viewJpfData.job_id"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-32 shrink-0">Related MRF No.:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1rem] italic" x-text="viewJpfData.related_mrf_no || '—'"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-32 shrink-0">Date Opened:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="viewJpfData.date_opened || '—'"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-32 shrink-0">Hiring Status:</span>
                                            <div class="flex gap-3">
                                                <template x-for="st in ['Open', 'Urgent', 'Confidential', 'Closed']" :key="st">
                                                    <div class="flex items-center gap-1">
                                                        <span class="w-3 h-3 border border-gray-400 flex items-center justify-center" :class="viewJpfData.hiring_status === st ? 'bg-gray-800' : ''">
                                                            <svg x-show="viewJpfData.hiring_status === st" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                                                        </span>
                                                        <span x-text="st"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- COMPANY DETAILS --}}
                                <div class="border border-gray-400">
                                    <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Company Details</div>
                                    <div class="p-3 grid grid-cols-1 gap-y-2 text-[11px]">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-40 shrink-0">Company Name:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="viewJpfData.company_name || 'John Kelly & Company'"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-40 shrink-0">Office / Branch / Site:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="viewJpfData.office_branch_site || '—'"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-40 shrink-0">Department / Unit:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="viewJpfData.department_unit || '—'"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-40 shrink-0">Hiring Manager:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="viewJpfData.hiring_manager || '—'"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-40 shrink-0">Department Superior:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="viewJpfData.department_superior || '—'"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- POSITION DETAILS --}}
                                <div class="border border-gray-400">
                                    <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Position Details</div>
                                    <div class="p-3 space-y-3">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold w-32 shrink-0">Position Title:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1.2rem] text-sm font-black" x-text="viewJpfData.position"></span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold w-32 shrink-0">No. of Vacancies:</span>
                                                <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="viewJpfData.no_of_vacancies || '—'"></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold w-32 shrink-0">Reports To:</span>
                                                <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="viewJpfData.reports_to || '—'"></span>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4 pt-2">
                                            <div>
                                                <p class="font-bold mb-2">Position Level:</p>
                                                <div class="grid grid-cols-2 gap-y-1">
                                                    <template x-for="lv in ['Rank & File', 'Staff', 'Senior Staff', 'Supervisor', 'Manager', 'Executive']" :key="lv">
                                                        <div class="flex items-center gap-2">
                                                            <span class="w-3 h-3 border border-gray-400 flex items-center justify-center" :class="viewJpfData.position_level === lv ? 'bg-gray-800' : ''">
                                                                <svg x-show="viewJpfData.position_level === lv" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                                                            </span>
                                                            <span x-text="lv"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="font-bold mb-2">Employment Type:</p>
                                                <div class="grid grid-cols-2 gap-y-1">
                                                    <template x-for="et in employmentTypeOptions" :key="et">
                                                        <div class="flex items-center gap-2">
                                                            <span class="w-3 h-3 border border-gray-400 flex items-center justify-center" :class="viewJpfData.employment_type === et ? 'bg-gray-800' : ''">
                                                                <svg x-show="viewJpfData.employment_type === et" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                                                            </span>
                                                            <span x-text="et"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 pt-2">
                                            <span class="font-bold w-32 shrink-0">Work Location:</span>
                                            <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="viewJpfData.location || '—'"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- SALARY OFFER --}}
                                <div class="border border-gray-400">
                                    <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Salary Offer / Compliance</div>
                                    <div class="p-3 grid grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold w-32 shrink-0">Minimum Offer:</span>
                                                <span class="border-b border-gray-300 flex-1 min-h-[1rem]">₱ <span x-text="parseFloat(viewJpfData.min_salary_offer || 0).toLocaleString()"></span></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold w-32 shrink-0">Maximum Offer:</span>
                                                <span class="border-b border-gray-300 flex-1 min-h-[1rem]">₱ <span x-text="parseFloat(viewJpfData.max_salary_offer || 0).toLocaleString()"></span></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold w-32 shrink-0">Salary Grade:</span>
                                                <span class="border-b border-gray-300 flex-1 min-h-[1rem]" x-text="viewJpfData.salary_grade || '—'"></span>
                                            </div>
                                        </div>
                                        <div class="space-y-2 border-l border-gray-200 pl-4">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold w-32 shrink-0 text-[8px] uppercase">Daily Min Wage:</span>
                                                <span class="border-b border-gray-300 flex-1 min-h-[1rem]">₱ <span x-text="parseFloat(viewJpfData.current_daily_min_wage || 0).toLocaleString()"></span></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold w-32 shrink-0 text-[8px] uppercase">Monthly Equiv:</span>
                                                <span class="border-b border-gray-300 flex-1 min-h-[1rem]">₱ <span x-text="parseFloat(viewJpfData.monthly_equivalent || 0).toLocaleString()"></span></span>
                                            </div>
                                            <div class="space-y-1 pt-1">
                                                <template x-for="wc in ['Confirmed compliant with applicable wage order', 'Above minimum wage', 'With allowances / premiums']" :key="wc">
                                                    <div class="flex items-center gap-1 text-[8px]">
                                                        <span class="w-3 h-3 border border-gray-400 flex items-center justify-center shrink-0" :class="(viewJpfData.wage_compliance || []).includes(wc) ? 'bg-gray-800' : ''">
                                                            <svg x-show="(viewJpfData.wage_compliance || []).includes(wc)" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                                                        </span>
                                                        <span x-text="wc"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- BENEFITS --}}
                                <div class="border border-gray-400">
                                    <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Benefits Package</div>
                                    <div class="p-3 grid grid-cols-3 gap-y-1">
                                        <template x-for="bf in ['SSS', 'PhilHealth', 'Pag-IBIG', '13th Month Pay', 'Service Incentive Leave', 'HMO', 'Incentives / Commission', 'Overtime Pay', 'Holiday Pay']" :key="bf">
                                            <div class="flex items-center gap-2">
                                                <span class="w-3 h-3 border border-gray-400 flex items-center justify-center shrink-0" :class="(viewJpfData.benefits_package || []).includes(bf) ? 'bg-gray-800' : ''">
                                                    <svg x-show="(viewJpfData.benefits_package || []).includes(bf)" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                                                </span>
                                                <span x-text="bf"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                {{-- WORK SCHEDULE --}}
                                <div class="border border-gray-400">
                                    <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Work Schedule</div>
                                    <div class="p-3 grid grid-cols-2 gap-y-1">
                                        <template x-for="ws in ['Monday to Friday – 8:00 AM to 5:00 PM', 'Monday to Saturday – 8:00 AM to 5:00 PM', 'Shifting Schedule', 'Night Shift', 'Hybrid', 'Work From Home', 'Flexible']" :key="ws">
                                            <div class="flex items-center gap-2">
                                                <span class="w-3 h-3 border border-gray-400 flex items-center justify-center shrink-0" :class="(viewJpfData.work_schedule || []).includes(ws) ? 'bg-gray-800' : ''">
                                                    <svg x-show="(viewJpfData.work_schedule || []).includes(ws)" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                                                </span>
                                                <span x-text="ws"></span>
                                            </div>
                                        </template>
                                        <div class="col-span-2 pt-2 border-t mt-2">
                                            <span class="font-bold">Rest Day/s:</span> <span x-text="viewJpfData.rest_days || '—'"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- JOB REQUIREMENTS --}}
                                <div class="border border-gray-400">
                                    <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Job Requirements & Duties</div>
                                    <div class="p-3 grid grid-cols-2 gap-x-6 gap-y-3">
                                        <div class="space-y-2 text-[9px]">
                                            <div><p class="font-bold underline uppercase text-[8px] mb-0.5 text-blue-600">Education:</p><p x-text="viewJpfData.education_req || viewJpfData.requirements" class="pl-2"></p></div>
                                            <div><p class="font-bold underline uppercase text-[8px] mb-0.5 text-blue-600">Experience:</p><p x-text="viewJpfData.experience_req || '—'" class="pl-2"></p></div>
                                            <div><p class="font-bold underline uppercase text-[8px] mb-0.5 text-blue-600">Skills:</p><p x-text="viewJpfData.skills_req || '—'" class="pl-2"></p></div>
                                            <div><p class="font-bold underline uppercase text-[8px] mb-0.5 text-blue-600">Licenses:</p><p x-text="viewJpfData.licenses_req || '—'" class="pl-2"></p></div>
                                            <div class="pt-2"><p class="font-bold underline uppercase text-[8px] mb-0.5 text-blue-600">Preferred Qualifications:</p><p x-text="viewJpfData.preferred_qualifications || '—'" class="pl-2 whitespace-pre-wrap"></p></div>
                                        </div>
                                        <div class="border-l pl-4 border-gray-200">
                                            <p class="font-bold underline uppercase text-[8px] mb-1 text-blue-600">Duties & Responsibilities:</p>
                                            <div class="whitespace-pre-wrap text-[9px] leading-relaxed" x-text="viewJpfData.duties_responsibilities || viewJpfData.job_description"></div>
                                        </div>
                                    </div>
                                </div>

                                {{-- FLOW & CHANNELS --}}
                                <div class="grid grid-cols-2 gap-4 text-[9px]">
                                    <div class="border border-gray-400 h-full">
                                        <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Recruitment Channels</div>
                                        <div class="p-3 grid grid-cols-1 gap-y-1">
                                            <template x-for="ch in ['JobStreet', 'Indeed', 'Facebook', 'Referral', 'Walk-in', 'School / Campus Hiring', 'Internal Posting', 'Agency']" :key="ch">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2.5 h-2.5 border border-gray-400 flex items-center justify-center shrink-0" :class="(viewJpfData.recruitment_channels || []).includes(ch) ? 'bg-gray-800' : ''"></span>
                                                    <span x-text="ch"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="border border-gray-400 h-full">
                                        <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Screening Flow</div>
                                        <div class="p-3 space-y-1">
                                            <template x-for="sf in ['Resume Screening', 'Initial Interview', 'Assessment Exam', 'Final Interview', 'Reference Check', 'Job Offer', 'Pre-employment Requirements', 'Deployment']" :key="sf">
                                                <div class="flex items-center gap-2 text-[9px]">
                                                    <span class="w-3 h-3 border border-gray-400 flex items-center justify-center shrink-0 rounded-full text-[7px]" 
                                                        :class="(viewJpfData.screening_flow || []).includes(sf) ? 'bg-gray-800 text-white border-gray-800' : ''" 
                                                        x-text="(viewJpfData.screening_flow || []).includes(sf) ? ((viewJpfData.screening_flow || []).indexOf(sf) + 1) : ''"></span>
                                                    <span x-text="sf"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                {{-- TIMELINE --}}
                                <div class="border border-gray-400">
                                    <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">Target Timeline</div>
                                    <div class="p-3 grid grid-cols-3 divide-x divide-gray-200 text-center">
                                        <div><p class="font-bold uppercase text-[7px] text-gray-400 mb-1">Date Needed</p><p class="font-black" x-text="viewJpfData.date_needed || '—'"></p></div>
                                        <div><p class="font-bold uppercase text-[7px] text-gray-400 mb-1">Posting Start</p><p class="font-black" x-text="viewJpfData.posting_start_date || '—'"></p></div>
                                        <div><p class="font-bold uppercase text-[7px] text-gray-400 mb-1">Target Hire Date</p><p class="font-black" x-text="viewJpfData.target_hire_date || '—'"></p></div>
                                    </div>
                                </div>

                                {{-- APPROVALS --}}
                                <div class="border border-gray-400">
                                    <div class="bg-gray-100 px-3 py-1 border-b border-gray-400 font-black uppercase text-[9px]">System Approvals</div>

                                    <table class="w-full border-collapse text-[9px]">
                                        <thead>
                                            <tr class="bg-gray-50 text-gray-500 uppercase">
                                                <th class="border border-gray-300 px-2 py-1 text-left w-[24%]">Approval Level</th>
                                                <th class="border border-gray-300 px-2 py-1 text-left w-[28%]">Assigned Approver</th>
                                                <th class="border border-gray-300 px-2 py-1 text-center w-[16%]">Status</th>
                                                <th class="border border-gray-300 px-2 py-1 text-center w-[16%]">Approved Date</th>
                                                <th class="border border-gray-300 px-2 py-1 text-left w-[16%]">Approved By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="ap in [
                                                { level: 'human-capital', label: 'Human Capital', data: viewJpfData.human_capital_approval || {} },
                                                { level: 'hiring-manager', label: 'Hiring Manager', data: viewJpfData.hiring_manager_approval || {} },
                                                { level: 'finance', label: 'Finance', data: viewJpfData.finance_approval || {} },
                                                { level: 'president', label: 'President / Final', data: viewJpfData.president_approval || {} }
                                            ]" :key="ap.level">
                                                <tr>
                                                    <td class="border border-gray-300 px-2 py-2 font-black uppercase text-gray-700" x-text="ap.label"></td>
                                                    <td class="border border-gray-300 px-2 py-2">
                                                        <div class="font-bold text-gray-800" x-text="approvalDisplayName(ap.data)"></div>
                                                        <div class="text-[7px] text-gray-500" x-text="ap.data?.position || ap.data?.role || '—'"></div>
                                                    </td>
                                                    <td class="border border-gray-300 px-2 py-2 text-center">
                                                        <span
                                                            class="inline-flex px-2 py-0.5 rounded-full border text-[8px] font-black uppercase"
                                                            :class="approvalBadgeClass(ap.data)"
                                                            x-text="approvalDisplayStatus(ap.data)"
                                                        ></span>
                                                    </td>
                                                    <td class="border border-gray-300 px-2 py-2 text-center font-semibold" x-text="approvalDisplayDate(ap.data)"></td>
                                                    <td class="border border-gray-300 px-2 py-2 font-semibold" x-text="ap.data?.decided_by_name || '—'"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>

                                    <div class="px-3 py-2 text-[8px] text-gray-500 italic border-t border-gray-300">
                                        Approval names, status, and dates are recorded by the system after the assigned approver approves the JPF.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            </div>
        </div>
    </div>

    {{-- ===================== CANDIDATE APPLICATION FORM MODAL ===================== --}}
    <div
        x-show="showCafModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex justify-end bg-black/50 backdrop-blur-sm"
        style="display:none;"
        @click.self="showCafModal = false"
    >
        <div
            x-show="showCafModal"
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
                <h2 class="text-sm font-bold text-gray-800 uppercase tracking-widest" x-text="isEditing ? 'Edit Application (CAF)' : 'Candidate Application Form (CAF)'"></h2>
                <button @click="showCafModal = false" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Split body --}}
            <div class="flex flex-1 overflow-hidden gap-4 p-4">

                {{-- RIGHT: INPUT FORM --}}
                <div class="w-[42%] bg-white rounded-xl shadow border border-gray-200 flex flex-col overflow-hidden shrink-0 order-last">
                    <div class="px-5 py-3 border-b bg-blue-700 rounded-t-xl">
                        <p class="text-xs font-bold text-white uppercase tracking-wider">Applicant Information</p>
                    </div>
                    <form @submit.prevent="submitCAF()" class="flex-1 overflow-y-auto px-5 py-5 space-y-6">
                        <div class="flex gap-6 items-start">
                            {{-- 2x2 Photo Upload --}}
                            <div class="shrink-0">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.15em] mb-2">Applicant Photo (2x2)</label>
                                <div class="relative group">
                                    <label @dragover.prevent @drop.prevent="cafForm.photo = $event.dataTransfer.files[0]"
                                        class="flex flex-col items-center justify-center w-32 h-32 border-2 border-gray-300 border-dashed rounded-2xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition overflow-hidden">
                                        <template x-if="!cafForm.photo && !isEditing">
                                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                <svg class="w-8 h-8 mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-tighter">Upload Photo</p>
                                            </div>
                                        </template>
                                        <template x-if="cafForm.photo">
                                            <img :src="URL.createObjectURL(cafForm.photo)" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!cafForm.photo && isEditing && cafForm.photo_path">
                                            <img :src="'/storage/' + cafForm.photo_path" class="w-full h-full object-cover">
                                        </template>
                                        <input type="file" class="hidden" accept="image/*" @change="cafForm.photo = $event.target.files[0]" />
                                    </label>
                                    <div x-show="cafForm.photo" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 shadow-lg cursor-pointer hover:bg-red-600 transition" @click="cafForm.photo = null">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                                    </div>
                                </div>
                            </div>

                            <div class="flex-1 space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Full Name</label>
                                    <input type="text" x-model="cafForm.fullName" required placeholder="Enter full name"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm transition-all shadow-sm">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Applicant Type</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <template x-for="type in ['New Applicant', 'Existing Employee / Internal Personnel']" :key="type">
                                            <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 hover:bg-blue-50 hover:border-blue-300 transition"
                                                :class="cafForm.applicantType === type ? 'bg-blue-50 border-blue-400 text-blue-700 font-semibold' : ''">
                                                <input type="radio" x-model="cafForm.applicantType" :value="type" class="accent-blue-600">
                                                <span x-text="type"></span>
                                            </label>
                                        </template>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-1">Use Existing Employee / Internal Personnel when the person is already connected with the company but must still pass through MRF, JPF, CAF, Assessment, Interview, Job Offer, PDS, Checklist, and Employee Registration.</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Applied Job / JPF</label>
                                    <select x-model="cafForm.jobPostingId" @change="onCafJpfChange()" required
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm transition-all bg-white cursor-pointer shadow-sm">
                                        <option value="">Select Posted/Open JPF</option>
                                        <template x-for="jpf in postedJPFs" :key="jpf.id || jpf.job_id">
                                            <option :value="jpf.id" x-text="`${jpf.job_id || 'JPF'} - ${jpf.position || 'No position'} (${jpf.department_unit || jpf.departmentUnit || 'No department'})`"></option>
                                        </template>
                                    </select>
                                    <p class="text-[11px] mt-1"
                                       :class="postedJPFs.length ? 'text-gray-500' : 'text-red-500'"
                                       x-text="postedJPFs.length ? 'Only fully approved Posted/Screening JPF records are available for applicants.' : 'No fully approved Posted/Screening JPF available yet.'"></p>

                                    <input type="hidden" x-model="cafForm.positionApplied">

                                    <div class="mt-3 grid grid-cols-1 gap-2 text-[11px] text-gray-600 bg-gray-50 border border-gray-100 rounded-lg p-3" x-show="selectedCafJpf">
                                        <p><strong>Position:</strong> <span x-text="cafForm.positionApplied || '—'"></span></p>
                                        <p><strong>Department / Unit:</strong> <span x-text="selectedCafJpf?.department_unit || selectedCafJpf?.departmentUnit || '—'"></span></p>
                                        <p><strong>Location:</strong> <span x-text="selectedCafJpf?.location || selectedCafJpf?.office_branch_site || selectedCafJpf?.officeBranchSite || '—'"></span></p>
                                        <p><strong>Employment Type:</strong> <span x-text="selectedCafJpf?.employment_type || selectedCafJpf?.employmentType || '—'"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Email Address</label>
                                <input type="email" x-model="cafForm.email" required placeholder="email@example.com"
                                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm transition-all shadow-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Phone Number</label>
                                <input type="text" x-model="cafForm.phone" required placeholder="+63 9xx xxx xxxx"
                                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm transition-all shadow-sm">
                            </div>
                        </div>

                        <div x-show="cafForm.applicantType === 'Existing Employee / Internal Personnel'" x-transition>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Internal Remarks / Current Role</label>
                            <textarea x-model="cafForm.internalRemarks" rows="2" placeholder="Example: Existing president/treasurer/personnel for record completion, internal onboarding, or account creation requirement."
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm transition-all shadow-sm resize-none"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Resume / CV</label>
                                <label @dragover.prevent @drop.prevent="cafForm.cv = $event.dataTransfer.files[0]"
                                    class="flex flex-col items-center justify-center w-full h-28 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition shadow-sm group">
                                    <div class="flex flex-col items-center justify-center pt-4 pb-4">
                                        <svg x-show="!cafForm.cv" class="w-7 h-7 mb-2 text-gray-400 group-hover:text-blue-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                        <svg x-show="cafForm.cv" class="w-7 h-7 mb-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <p class="text-[10px] font-bold text-gray-500 uppercase text-center px-2 line-clamp-1" x-text="cafForm.cv ? cafForm.cv.name : 'Drag Resume'"></p>
                                        <p class="text-[8px] text-gray-400 mt-0.5 uppercase" x-show="!cafForm.cv">PDF, DOCX up to 10MB</p>
                                    </div>
                                    <input type="file" class="hidden" @change="cafForm.cv = $event.target.files[0]" />
                                </label>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Cover Letter</label>
                                <label @dragover.prevent @drop.prevent="cafForm.coverLetterFile = $event.dataTransfer.files[0]"
                                    class="flex flex-col items-center justify-center w-full h-28 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition shadow-sm group">
                                    <div class="flex flex-col items-center justify-center pt-4 pb-4">
                                        <svg x-show="!cafForm.coverLetterFile" class="w-7 h-7 mb-2 text-gray-400 group-hover:text-indigo-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <svg x-show="cafForm.coverLetterFile" class="w-7 h-7 mb-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <p class="text-[10px] font-bold text-gray-500 uppercase text-center px-2 line-clamp-1" x-text="cafForm.coverLetterFile ? cafForm.coverLetterFile.name : 'Drag Cover Letter'"></p>
                                        <p class="text-[8px] text-gray-400 mt-0.5 uppercase" x-show="!cafForm.coverLetterFile">PDF, DOCX up to 10MB</p>
                                    </div>
                                    <input type="file" class="hidden" @change="cafForm.coverLetterFile = $event.target.files[0]" />
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Or Paste Cover Letter Text (Optional)</label>
                            <textarea x-model="cafForm.coverLetter" rows="4" placeholder="If not uploading a file, you can paste the text here..."
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 resize-none bg-gray-50 text-xs shadow-inner"></textarea>
                        </div>
                        
                        <div class="flex justify-end gap-3 pt-5 border-t border-gray-100">
                            <button type="button" @click="showCafModal = false"
                                class="px-5 py-2.5 text-sm font-semibold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                Discard
                            </button>
                            <button type="submit"
                                class="px-6 py-2.5 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold shadow-md shadow-blue-100 transition-all active:scale-95" x-text="isEditing ? 'Update Application' : 'Submit Application'">
                            </button>
                        </div>
                    </form>

                </div>

                {{-- LEFT: LIVE CAF DOCUMENT PREVIEW --}}
                <div class="flex-1 bg-white rounded-xl shadow border border-gray-200 flex flex-col overflow-hidden">
                    <div class="px-5 py-3 border-b bg-gray-50 rounded-t-xl shrink-0 flex items-center justify-between">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Live Preview</p>
                        <div class="flex items-center gap-2">

                            <button type="button" @click="downloadPDF('caf-doc-create')" class="text-xs px-3 py-1.5 bg-white hover:bg-gray-50 border border-gray-300 rounded text-gray-700 font-semibold flex items-center gap-1 transition shadow-sm">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Download PDF
                            </button>
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto p-8 bg-gray-100/50">
                        <div id="caf-doc-create" class="border border-gray-200 bg-white shadow-xl p-12 mx-auto w-[794px] shrink-0 font-sans min-h-[1000px]">
                            <div class="flex justify-between items-start border-b-2 border-gray-800 pb-8 mb-8 gap-8">
                                <div class="shrink-0">
                                    <div class="w-32 h-32 border-2 border-gray-200 rounded-lg overflow-hidden bg-gray-50 flex items-center justify-center relative">
                                        <template x-if="!cafForm.photo && !cafForm.photo_path">
                                            <svg class="w-12 h-12 text-gray-200" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                        </template>
                                        <template x-if="cafForm.photo">
                                            <img :src="URL.createObjectURL(cafForm.photo)" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!cafForm.photo && cafForm.photo_path">
                                            <img :src="'/storage/' + cafForm.photo_path" class="w-full h-full object-cover">
                                        </template>
                                        <div class="absolute bottom-0 inset-x-0 bg-gray-800/50 text-[8px] text-white text-center py-1 font-bold uppercase tracking-widest">2x2 Photo</div>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h1 class="text-4xl font-black text-gray-900 tracking-tighter uppercase mb-2" x-text="cafForm.fullName || 'Candidate Name'"></h1>
                                    <p class="text-xl text-blue-600 font-bold uppercase tracking-widest" x-text="cafForm.positionApplied || 'Position Title'"></p>
                                    <p class="mt-2 inline-flex px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-[10px] font-black uppercase tracking-widest" x-text="cafForm.applicantType || 'New Applicant'"></p>
                                    <div class="mt-4 text-xs font-bold text-gray-500 space-y-1">
                                        <p x-text="cafForm.email || 'email@example.com'"></p>
                                        <p x-text="cafForm.phone || '+63 9xx xxx xxxx'"></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <img src="{{ asset('images/imaglogo.png') }}" onerror="this.src='{{ asset('images/imag1logo.jpg') }}'" alt="Logo" class="h-16 w-auto object-contain ml-auto mb-4">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Candidate Application</p>
                                </div>
                            </div>
                            
                            <div class="space-y-10">
                                <div>
                                    <div class="flex items-center gap-3 mb-6">
                                        <div class="h-0.5 flex-1 bg-gray-200"></div>
                                        <h3 class="text-sm font-black text-gray-800 uppercase tracking-[0.2em]">Application Details</h3>
                                        <div class="h-0.5 flex-1 bg-gray-200"></div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-8 text-sm px-4">
                                        <div class="space-y-1">
                                            <p class="text-[10px] font-bold text-gray-400 uppercase">Application Date</p>
                                            <p class="font-bold text-gray-800" x-text="new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })"></p>
                                        </div>
                                        <div class="space-y-1">
                                            <p class="text-[10px] font-bold text-gray-400 uppercase">Resume Attachment</p>
                                            <p class="font-bold" :class="cafForm.cv ? 'text-green-600' : 'text-red-500'" x-text="cafForm.cv ? cafForm.cv.name : 'No file uploaded'"></p>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex items-center gap-3 mb-6">
                                        <div class="h-0.5 flex-1 bg-gray-200"></div>
                                        <h3 class="text-sm font-black text-gray-800 uppercase tracking-[0.2em]">Cover Letter</h3>
                                        <div class="h-0.5 flex-1 bg-gray-200"></div>
                                    </div>
                                    
                                    <div class="bg-gray-50/50 p-6 rounded-xl border border-gray-100 min-h-[400px]">
                                        <p class="text-gray-700 leading-relaxed whitespace-pre-wrap text-sm" x-text="cafForm.coverLetter || 'Your cover letter content will appear here...'"></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-20 pt-8 border-t border-gray-100 flex justify-between items-center opacity-50">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Internal Candidate Record</p>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">John Kelly & Company</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- end split body --}}
        </div>
    </div>
      {{-- ===================== CAF VIEW SLIDE-OVER ===================== --}}
    <div x-show="showCafViewModal" class="fixed inset-0 overflow-hidden z-[9999]" style="display:none;">
        <div @click="showCafViewModal = false" class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity"
            x-show="showCafViewModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="fixed inset-y-0 right-0 max-w-[90rem] w-full flex pointer-events-none">
            <div x-show="showCafViewModal" 
                x-transition:enter="transform transition ease-in-out duration-700"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-500"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="h-full w-full bg-gray-50 shadow-2xl flex flex-col pointer-events-auto overflow-hidden">
                <template x-if="viewCafData">
                    <div class="flex h-full overflow-hidden">
                        
                        {{-- LEFT: DOCUMENT VIEW --}}
                        <div class="flex-1 flex flex-col bg-gray-200 border-r border-gray-300 p-6 overflow-hidden" x-data="{ docTab: 'summary' }">
                            <div class="flex items-center justify-between mb-4 shrink-0">
                                <div class="flex bg-white rounded-xl p-1 border border-gray-300 shadow-sm">
                                    <button @click="docTab = 'summary'" :class="docTab === 'summary' ? 'bg-teal-600 text-white shadow-lg' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'" class="px-5 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all">Summary</button>
                                    <button x-show="viewCafData.cv_path" @click="docTab = 'resume'" :class="docTab === 'resume' ? 'bg-teal-600 text-white shadow-lg' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'" class="px-5 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all">Resume / CV</button>
                                    <button x-show="viewCafData.cover_letter_path" @click="docTab = 'coverletter'" :class="docTab === 'coverletter' ? 'bg-teal-600 text-white shadow-lg' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'" class="px-5 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all">Cover Letter File</button>
                                </div>
                                <div class="flex gap-2">
                                    <template x-if="viewCafData.cv_path">
                                        <a :href="'/storage/' + viewCafData.cv_path" target="_blank" class="px-4 py-2 bg-white text-gray-700 rounded-lg text-[10px] font-bold uppercase border border-gray-300 shadow-sm hover:bg-gray-50 transition">Fullscreen</a>
                                    </template>
                                </div>
                            </div>
                            
                            <div class="flex-1 bg-white rounded-2xl shadow-inner border border-gray-300 flex flex-col overflow-hidden relative">
                                {{-- SUMMARY TAB --}}
                                <div x-show="docTab === 'summary'" class="flex-1 overflow-y-auto p-12 bg-gray-100/50 flex flex-col items-center">
                                    <div class="w-full max-w-4xl bg-white shadow-2xl border border-gray-200 p-16 font-sans space-y-12 min-h-[1200px]">
                                        <div class="flex justify-between items-start border-b-2 border-gray-800 pb-8">
                                            <div class="w-32 h-32 border-2 border-gray-100 rounded-2xl overflow-hidden bg-gray-50 shrink-0">
                                                <template x-if="viewCafData.photo_path">
                                                    <img :src="'/storage/' + viewCafData.photo_path" class="w-full h-full object-cover">
                                                </template>
                                                <template x-if="!viewCafData.photo_path">
                                                    <div class="w-full h-full flex items-center justify-center text-gray-200">
                                                        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="flex-1 ml-10">
                                                <h1 class="text-4xl font-black text-gray-900 tracking-tighter uppercase mb-2" x-text="viewCafData.name"></h1>
                                                <p class="text-xl text-teal-600 font-bold uppercase tracking-widest" x-text="viewCafData.position"></p>
                                                <div class="mt-4 space-y-1 text-xs font-bold text-gray-500">
                                                    <p x-text="viewCafData.email"></p>
                                                    <p x-text="viewCafData.phone"></p>
                                                </div>
                                            </div>
                                            <img src="{{ asset('images/imaglogo.png') }}" onerror="this.src='{{ asset('images/imag1logo.jpg') }}'" alt="Logo" class="h-16 w-auto object-contain">
                                        </div>

                                        <div class="space-y-6">
                                            <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest border-b pb-2">Cover Letter / Statement</h3>
                                            <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap" x-text="viewCafData.cover_letter && viewCafData.cover_letter !== 'null' ? viewCafData.cover_letter : (viewCafData.cover_letter_path ? 'See attached cover letter file.' : 'No cover letter text provided.')"></div>
                                        </div>
                                        
                                        <div class="pt-20 border-t border-gray-100 flex justify-between items-center opacity-30">
                                            <p class="text-[8px] font-black uppercase tracking-widest">Candidate Record</p>
                                            <p class="text-[8px] font-black uppercase tracking-widest">John Kelly & Company</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- RESUME PDF TAB --}}
                                <div x-show="docTab === 'resume'" class="flex-1 bg-gray-800">
                                    <iframe :src="'/storage/' + viewCafData.cv_path" class="w-full h-full border-none shadow-2xl"></iframe>
                                </div>

                                {{-- COVER LETTER FILE TAB --}}
                                <div x-show="docTab === 'coverletter'" class="flex-1 bg-gray-800">
                                    <iframe :src="'/storage/' + viewCafData.cover_letter_path" class="w-full h-full border-none shadow-2xl"></iframe>
                                </div>
                            </div>
                        </div>

                        {{-- RIGHT: CANDIDATE INFO --}}
                        <div class="w-[450px] bg-white border-l border-gray-200 flex flex-col shrink-0">
                            <div class="h-44 bg-gradient-to-r from-teal-500 to-emerald-600 relative shrink-0">
                                <button @click="showCafViewModal = false" class="absolute top-6 right-6 text-white/50 hover:text-white transition group bg-white/10 p-2 rounded-full backdrop-blur-md z-20">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                <div class="absolute -bottom-12 left-10 z-10 text-center">
                                    <div class="w-32 h-32 bg-white rounded-3xl shadow-2xl flex items-center justify-center border-4 border-white text-teal-600 overflow-hidden mx-auto">
                                        <template x-if="viewCafData.photo_path">
                                            <img :src="'/storage/' + viewCafData.photo_path" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!viewCafData.photo_path">
                                            <svg class="w-16 h-16" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="flex-1 overflow-y-auto px-10 pt-16 pb-8 space-y-10">
                                <div class="text-center">
                                    <h2 class="text-3xl font-black text-gray-900 tracking-tight capitalize" x-text="viewCafData.name"></h2>
                                    <p class="text-teal-600 font-bold tracking-[0.2em] uppercase text-[11px] mt-1" x-text="viewCafData.position"></p>
                                    <span class="inline-flex mt-3 px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-[10px] font-black uppercase tracking-widest" x-text="viewCafData.applicant_type || 'New Applicant'"></span>
                                </div>

                                <div class="grid grid-cols-1 gap-5">
                                    <div class="bg-gray-50/50 p-6 rounded-[2rem] border border-gray-100 font-medium shadow-sm transition hover:shadow-md">
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Email Address</p>
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-white rounded-xl shadow-sm border border-gray-100 flex items-center justify-center text-teal-600 shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            </div>
                                            <p class="text-gray-800 break-all text-sm font-bold" x-text="viewCafData.email"></p>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50/50 p-6 rounded-[2rem] border border-gray-100 font-medium shadow-sm transition hover:shadow-md">
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Phone Number</p>
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-white rounded-xl shadow-sm border border-gray-100 flex items-center justify-center text-teal-600 shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 5.25a.75.75 0 01.75-.75H9a.75.75 0 01.75.75v3.31A.75.75 0 019 9.31L7.15 10.63a.75.75 0 00-.2 1.05c.87 1.34 2.1 2.57 3.44 3.44a.75.75 0 001.05-.2l1.32-1.85a.75.75 0 011-.31h3.31a.75.75 0 01.75.75v5.25a.75.75 0 01-.75.75h-2.25c-7.46 0-13.5-6.04-13.5-13.5v-2.25z"/></svg>
                                            </div>
                                            <p class="text-gray-800 text-sm font-bold" x-text="viewCafData.phone"></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-teal-50/30 p-6 rounded-[2rem] border border-teal-100 shadow-sm">
                                    <div class="flex items-center gap-2 mb-4">
                                        <div class="w-2 h-2 bg-teal-500 rounded-full animate-pulse"></div>
                                        <p class="text-[10px] font-black text-teal-700 uppercase tracking-widest">Application Status</p>
                                    </div>
                                    <p class="text-sm font-bold text-teal-900" x-text="'Applied on ' + (viewCafData.created_at ? new Date(viewCafData.created_at).toLocaleDateString() : '—')"></p>
                                </div>
                            </div>

                            <div class="px-10 py-8 border-t border-gray-100 bg-gray-50 flex flex-col gap-3 shrink-0">
                                <button @click="openAssessmentFromCaf(viewCafData)" 
                                    class="w-full py-4 bg-teal-600 hover:bg-teal-700 text-white font-black rounded-[2rem] transition-all shadow-xl shadow-teal-100 uppercase tracking-[0.25em] text-[11px] active:scale-95">
                                    Proceed to Assessment
                                </button>
                                <button @click="showCafViewModal = false" class="w-full py-3 text-gray-500 hover:text-gray-800 font-bold uppercase tracking-widest text-[10px] transition">
                                    Dismiss
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
                   
    {{-- ===================== ASSESSMENT VIEW SLIDE-OVER ===================== --}}
    <div x-show="showAssessmentViewModal" class="fixed inset-0 overflow-hidden z-[9999]" style="display:none;">
        <div @click="showAssessmentViewModal = false" class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity"
            x-show="showAssessmentViewModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="fixed inset-y-0 right-0 max-w-[90rem] w-full flex pointer-events-none">
            <div x-show="showAssessmentViewModal" 
                x-transition:enter="transform transition ease-in-out duration-700"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-500"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="h-full w-full bg-gray-50 shadow-2xl flex flex-col pointer-events-auto overflow-hidden">
                <template x-if="viewAssessmentData">
                    <div class="flex h-full overflow-hidden">
                        
                        {{-- LEFT: DOCUMENT VIEW --}}
                        <div class="flex-1 flex flex-col bg-gray-200 border-r border-gray-300 p-6 overflow-hidden" x-data="{ docTab: 'summary' }">
                            <div class="flex items-center justify-between mb-4 shrink-0">
                                <div class="flex bg-white rounded-xl p-1 border border-gray-300 shadow-sm">
                                    <button @click="docTab = 'summary'" :class="docTab === 'summary' ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'" class="px-5 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all">Summary</button>
                                    <button x-show="viewAssessmentData.cv_path" @click="docTab = 'resume'" :class="docTab === 'resume' ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'" class="px-5 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all">Resume / CV</button>
                                    <button x-show="viewAssessmentData.cover_letter_path" @click="docTab = 'coverletter'" :class="docTab === 'coverletter' ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'" class="px-5 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all">Cover Letter File</button>
                                </div>
                                <div class="flex gap-2">
                                    <template x-if="viewAssessmentData.cv_path">
                                        <a :href="'/storage/' + viewAssessmentData.cv_path" target="_blank" class="px-4 py-2 bg-white text-gray-700 rounded-lg text-[10px] font-bold uppercase border border-gray-300 shadow-sm hover:bg-gray-50 transition">Fullscreen</a>
                                    </template>
                                </div>
                            </div>
                            
                            <div class="flex-1 bg-white rounded-2xl shadow-inner border border-gray-300 flex flex-col overflow-hidden relative">
                                {{-- SUMMARY TAB --}}
                                <div x-show="docTab === 'summary'" class="flex-1 overflow-y-auto p-12 bg-gray-100/50 flex flex-col items-center">
                                    <div class="w-full max-w-4xl bg-white shadow-2xl border border-gray-200 p-16 font-sans space-y-12 min-h-[800px]">
                                        <div class="flex justify-between items-start border-b-2 border-gray-800 pb-8">
                                            <div class="w-32 h-32 border-2 border-gray-100 rounded-2xl overflow-hidden bg-gray-50 shrink-0">
                                                <template x-if="viewAssessmentData.photo_path">
                                                    <img :src="'/storage/' + viewAssessmentData.photo_path" class="w-full h-full object-cover">
                                                </template>
                                                <template x-if="!viewAssessmentData.photo_path">
                                                    <div class="w-full h-full flex items-center justify-center text-gray-200">
                                                        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="flex-1 ml-10">
                                                <h1 class="text-4xl font-black text-gray-900 tracking-tighter uppercase mb-2" x-text="viewAssessmentData.name"></h1>
                                                <p class="text-xl text-blue-600 font-bold uppercase tracking-widest" x-text="viewAssessmentData.position"></p>
                                                <div class="mt-4 space-y-1 text-xs font-bold text-gray-500">
                                                    <p class="uppercase">Candidate Assessment Record</p>
                                                    <p x-text="'Result: ' + (viewAssessmentData.score || 'In Progress')"></p>
                                                </div>
                                            </div>
                                            <img src="{{ asset('images/imaglogo.png') }}" onerror="this.src='{{ asset('images/imag1logo.jpg') }}'" alt="Logo" class="h-16 w-auto object-contain">
                                        </div>

                                        <div class="grid grid-cols-2 gap-10">
                                            <div class="space-y-6">
                                                <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] border-b pb-2">Assessment Details</h3>
                                                <div class="space-y-4">
                                                    <div>
                                                        <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Test Type</p>
                                                        <p class="text-sm font-bold text-gray-800" x-text="viewAssessmentData.test_type || viewAssessmentData.test"></p>
                                                    </div>
                                                    <div>
                                                        <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Date</p>
                                                        <p class="text-sm font-bold text-gray-800" x-text="viewAssessmentData.assessment_date || viewAssessmentData.date"></p>
                                                    </div>
                                                    <div>
                                                        <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Email</p>
                                                        <p class="text-sm font-bold text-gray-800" x-text="viewAssessmentData.email || '—'"></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="space-y-6">
                                                <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] border-b pb-2">Current Status</h3>
                                                <div class="space-y-4">
                                                    <div>
                                                        <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Status</p>
                                                        <span :class="statusClass(viewAssessmentData.status)" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest" x-text="viewAssessmentData.status"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-6">
                                            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] border-b pb-2">Evaluator Notes</h3>
                                            <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap italic" x-text="viewAssessmentData.notes || 'No notes available for this assessment.'"></div>
                                        </div>
                                        
                                        <div class="pt-20 border-t border-gray-100 flex justify-between items-center opacity-30">
                                            <p class="text-[8px] font-black uppercase tracking-widest">Assessment Record</p>
                                            <p class="text-[8px] font-black uppercase tracking-widest">John Kelly & Company</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- RESUME PDF TAB --}}
                                <div x-show="docTab === 'resume'" class="flex-1 bg-gray-800">
                                    <iframe :src="'/storage/' + viewAssessmentData.cv_path" class="w-full h-full border-none shadow-2xl"></iframe>
                                </div>

                                {{-- COVER LETTER FILE TAB --}}
                                <div x-show="docTab === 'coverletter'" class="flex-1 bg-gray-800">
                                    <iframe :src="'/storage/' + viewAssessmentData.cover_letter_path" class="w-full h-full border-none shadow-2xl"></iframe>
                                </div>
                            </div>
                        </div>

                        {{-- RIGHT: ASSESSMENT INFO --}}
                        <div class="w-[450px] bg-white border-l border-gray-200 flex flex-col shrink-0">
                            <div class="h-44 bg-gradient-to-r from-blue-600 to-indigo-700 relative shrink-0">
                                <button @click="showAssessmentViewModal = false" class="absolute top-6 right-6 text-white/50 hover:text-white transition group bg-white/10 p-2 rounded-full backdrop-blur-md z-20">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                <div class="absolute -bottom-12 left-10 z-10 text-center">
                                    <div class="w-32 h-32 bg-white rounded-3xl shadow-2xl flex items-center justify-center border-4 border-white text-blue-600 overflow-hidden mx-auto">
                                        <template x-if="viewAssessmentData.photo_path">
                                            <img :src="'/storage/' + viewAssessmentData.photo_path" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!viewAssessmentData.photo_path">
                                            <svg class="w-16 h-16" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="flex-1 overflow-y-auto px-10 pt-16 pb-8 space-y-10">
                                <div class="text-center">
                                    <h2 class="text-3xl font-black text-gray-900 tracking-tight capitalize" x-text="viewAssessmentData.name"></h2>
                                    <p class="text-blue-600 font-bold tracking-[0.2em] uppercase text-[11px] mt-1" x-text="viewAssessmentData.position"></p>
                                </div>

                                <div class="grid grid-cols-1 gap-5">
                                    <div class="bg-gray-50/50 p-6 rounded-[2rem] border border-gray-100 font-medium shadow-sm transition hover:shadow-md">
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Assessment Type</p>
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-white rounded-xl shadow-sm border border-gray-100 flex items-center justify-center text-blue-600 shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <select x-model="viewAssessmentData.test_type" 
                                                    class="w-full bg-transparent border-none text-gray-800 text-sm font-bold focus:ring-0 cursor-pointer p-0">
                                                    <option value="Technical Test">Technical Test</option>
                                                    <option value="Amplitude Test">Amplitude Test</option>
                                                    <option value="Personality Test">Personality Test</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50/50 p-6 rounded-[2rem] border border-gray-100 font-medium shadow-sm transition hover:shadow-md">
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Assessment Date</p>
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-white rounded-xl shadow-sm border border-gray-100 flex items-center justify-center text-blue-600 shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z"/></svg>
                                            </div>
                                            <p class="text-gray-800 text-sm font-bold" x-text="viewAssessmentData.assessment_date || viewAssessmentData.date"></p>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50/50 p-6 rounded-[2rem] border border-gray-100 font-medium shadow-sm transition hover:shadow-md">
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Candidate Email</p>
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-white rounded-xl shadow-sm border border-gray-100 flex items-center justify-center text-blue-600 shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <input type="email" x-model="viewAssessmentData.email" 
                                                    placeholder="Enter candidate email..."
                                                    class="w-full bg-transparent border-none text-gray-800 text-sm font-bold focus:ring-0 p-0 placeholder-gray-300">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-gray-50/50 p-6 rounded-[2rem] border border-gray-100 font-medium shadow-sm transition hover:shadow-md" x-show="viewAssessmentData.status === 'In Progress' || viewAssessmentData.score">
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Assessment Score (%)</p>
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-white rounded-xl shadow-sm border border-gray-100 flex items-center justify-center text-blue-600 shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <input type="number" x-model="viewAssessmentData.score_raw" 
                                                    @input="viewAssessmentData.score = $event.target.value + '%'"
                                                    placeholder="Enter score (0-100)"
                                                    class="w-full bg-transparent border-none text-gray-800 text-sm font-bold focus:ring-0 p-0 placeholder-gray-300">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-blue-50/30 p-6 rounded-[2rem] border border-blue-100 shadow-sm">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                                            <p class="text-[10px] font-black text-blue-700 uppercase tracking-widest">Current Status</p>
                                        </div>
                                        <span x-text="viewAssessmentData.score" x-show="viewAssessmentData.score" class="text-xl font-black text-blue-600"></span>
                                    </div>
                                    <p class="text-sm font-bold text-blue-900 uppercase tracking-widest" x-text="viewAssessmentData.status"></p>
                                </div>
                            </div>

                            <div class="px-10 py-8 border-t border-gray-100 bg-gray-50 flex flex-col gap-3 shrink-0">
                                <template x-if="viewAssessmentData.status === 'In Progress'">
                                    <button @click="submitAssessmentResult($event)" class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-[2rem] transition-all shadow-xl shadow-emerald-100 uppercase tracking-[0.25em] text-[11px] active:scale-95 flex items-center justify-center gap-3">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Submit Result
                                    </button>
                                </template>

                                <template x-if="viewAssessmentData.status === 'Pending Assessment'">
                                    <button @click="sendAssessmentTest($event)" class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-black rounded-[2rem] transition-all shadow-xl shadow-blue-100 uppercase tracking-[0.25em] text-[11px] active:scale-95 flex items-center justify-center gap-3">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        Send a Test
                                    </button>
                                </template>

                                <template x-if="viewAssessmentData.status !== 'Pending Assessment' && viewAssessmentData.status !== 'In Progress'">
                                    <button @click="showAssessmentViewModal = false" class="w-full py-4 bg-gray-600 hover:bg-gray-700 text-white font-black rounded-[2rem] transition-all uppercase tracking-[0.25em] text-[11px] active:scale-95">
                                        Dismiss
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>



    {{-- ===================== INTERVIEW VIEW SLIDE-OVER ===================== --}}
    <div x-show="showInterviewViewModal" class="fixed inset-0 overflow-hidden z-[9999]" style="display:none;">
        <div @click="showInterviewViewModal = false" class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity"
            x-show="showInterviewViewModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="fixed inset-y-0 right-0 max-w-xl w-full flex pointer-events-none">
            <div x-show="showInterviewViewModal" 
                x-transition:enter="transform transition ease-in-out duration-500"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="h-full w-full bg-white shadow-2xl flex flex-col pointer-events-auto">
                <template x-if="viewInterviewData">
                    <div class="flex flex-col h-full">
                        {{-- Aesthetic Header --}}
                        <div class="h-32 bg-gradient-to-br from-purple-600 to-indigo-700 flex items-center justify-center overflow-hidden shrink-0 relative">
                            <div class="absolute inset-0 opacity-10">
                                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0 100 C 20 0 50 0 100 100 Z" fill="white"></path></svg>
                            </div>
                            <div class="relative text-center">
                                <div class="inline-flex p-3 bg-white/20 backdrop-blur-md rounded-2xl mb-2 border border-white/30">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <h3 class="text-white font-black text-xl tracking-tight uppercase">Interview Details</h3>
                            </div>
                            <button @click="showInterviewViewModal = false" class="absolute top-6 right-6 text-white/50 hover:text-white transition group bg-white/10 p-2 rounded-full backdrop-blur-md">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="flex-1 overflow-y-auto p-8 space-y-8 bg-white">
                            <div class="grid grid-cols-2 gap-8">
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Candidate Name</p>
                                    <p class="text-gray-900 font-bold text-lg" x-text="viewInterviewData.name"></p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Position</p>
                                    <p class="text-purple-600 font-bold text-lg" x-text="viewInterviewData.position"></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-8 pt-6 border-t border-gray-50 text-sm">
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Interview Type</p>
                                    <p class="text-gray-700 font-medium" x-text="viewInterviewData.type || viewInterviewData.round"></p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Status</p>
                                    <p class="font-bold uppercase text-[11px]" :class="statusClass(viewInterviewData.status)" x-text="viewInterviewData.status"></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-8 pt-6 border-t border-gray-50 text-sm">
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Interviewer</p>
                                    <p class="text-gray-700 font-medium" x-text="viewInterviewData.interviewer"></p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Duration</p>
                                    <p class="text-gray-700 font-medium" x-text="(viewInterviewData.duration || '60') + ' minutes'"></p>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 font-medium">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Date & Time</p>
                                <p class="text-gray-800 text-sm" x-text="viewInterviewData.interview_date || viewInterviewData.date"></p>
                            </div>

                            <div x-show="viewInterviewData.meeting_link" class="space-y-2 pt-6 border-t border-gray-50">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Meeting Link</p>
                                <a :href="viewInterviewData.meeting_link" target="_blank" class="text-blue-600 hover:underline font-medium break-all text-sm block bg-blue-50/50 p-4 rounded-2xl border border-blue-100" x-text="viewInterviewData.meeting_link"></a>
                            </div>
                        </div>

                        <div class="px-8 py-6 border-t border-gray-100 space-y-3 shrink-0">
                            <div class="grid grid-cols-2 gap-3" x-show="!interviewAllowsJobOffer(viewInterviewData)">
                                <button type="button"
                                    @click="updateInterviewStatus(viewInterviewData, 'Completed')"
                                    class="py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition shadow-lg shadow-green-100 uppercase tracking-widest text-[11px]">
                                    Mark Completed
                                </button>

                                <button type="button"
                                    @click="updateInterviewStatus(viewInterviewData, 'Passed')"
                                    class="py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition shadow-lg shadow-blue-100 uppercase tracking-widest text-[11px]">
                                    Mark Passed
                                </button>
                            </div>

                            <div class="flex gap-4">
                                <button @click="showInterviewViewModal = false"
                                    class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition uppercase tracking-widest text-[11px]">
                                    Close
                                </button>

                                <button @click="openJobOfferModal(viewInterviewData)"
                                    :disabled="!interviewAllowsJobOffer(viewInterviewData)"
                                    :class="interviewAllowsJobOffer(viewInterviewData)
                                        ? 'bg-purple-600 hover:bg-purple-700 text-white shadow-lg shadow-purple-200'
                                        : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                                    class="flex-1 py-3 font-bold rounded-xl transition uppercase tracking-widest text-[11px]"
                                    x-text="interviewAllowsJobOffer(viewInterviewData) ? 'Send a Job Offer' : 'Complete/Pass Interview First'">
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- ===================== ADD NEW JOB OFFER MODAL ===================== --}}
    <div
        x-show="showJobOfferModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[70] flex justify-center items-center bg-black/50 backdrop-blur-sm"
        style="display:none;"
        @click.self="showJobOfferModal = false"
    >
        <div 
            class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all"
            x-show="showJobOfferModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        >
            <div class="px-6 py-4 border-b flex items-center justify-between bg-white text-gray-800">
                <h3 class="font-bold text-lg tracking-tight">Add New Job Offer</h3>
                <button @click="showJobOfferModal = false" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form @submit.prevent="submitJobOffer()" class="p-6 space-y-5 bg-white">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Link to Completed / Passed Interview <span class="text-red-500">*</span></label>
                    <select x-model="jobOfferForm.interviewId" @change="onJobOfferInterviewChange()" required
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-100 focus:border-purple-500 text-gray-700 text-sm bg-white transition-all">
                        <option value="">Select Completed/Passed Interview...</option>
                        <template x-for="interview in validJobOfferInterviews" :key="interview.id">
                            <option :value="interview.id" x-text="`${interview.name || 'Candidate'} - ${interview.position || 'No position'} (${interview.status || 'Status'})`"></option>
                        </template>
                    </select>
                    <p class="text-[11px] mt-1"
                       :class="validJobOfferInterviews.length ? 'text-gray-500' : 'text-red-500'"
                       x-text="validJobOfferInterviews.length ? 'Only Completed/Passed interviews are available here.' : 'No Completed/Passed interview is available yet.'"></p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Link to JPF / Job Posting <span class="text-red-500">*</span></label>
                    <select x-model="jobOfferForm.jobPostingId" @change="onJobOfferJpfChange()"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm bg-white transition-all">
                        <option value="">Select JPF to auto-fill Organizational and Payroll details...</option>
                        <template x-for="jpf in validJobOfferJPFs" :key="jpf.id || jpf.job_id">
                            <option :value="jpf.id" x-text="`${jpf.job_id || 'JPF'} - ${jpf.position || 'No position'} (${jpf.department_unit || jpf.departmentUnit || 'No department'})`"></option>
                        </template>
                    </select>
                    <p class="text-[11px] mt-1"
                       :class="validJobOfferJPFs.length ? 'text-gray-500' : 'text-red-500'"
                       x-text="validJobOfferJPFs.length ? 'Only fully approved active JPF records are available here.' : 'No fully approved active JPF is available. Complete approval and move the JPF through the hiring flow first.'"></p>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Candidate Name</label>
                        <input type="text" x-model="jobOfferForm.name" required placeholder="Enter name"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm transition-all bg-gray-50/50">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Position</label>
                        <select x-model="jobOfferForm.orgPositionId" @change="onJobOfferPositionChange()" required
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm bg-white transition-all">
                            <option value="">Select Position...</option>
                            <template x-for="position in positions" :key="position.id">
                                <option :value="position.id" x-text="position.position_name"></option>
                            </template>
                        </select>
                        <input type="hidden" x-model="jobOfferForm.position">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Organizational Address</label>
                        <select x-model="jobOfferForm.orgAddressId" @change="onJobOfferAddressChange()"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm bg-white transition-all">
                            <option value="">Select Address...</option>
                            <template x-for="address in organizationalAddresses" :key="address.id">
                                <option :value="address.id" x-text="address.full_address"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Department</label>
                        <select x-model="jobOfferForm.orgDepartmentId" @change="onJobOfferDepartmentChange()"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm bg-white transition-all">
                            <option value="">Select Department...</option>
                            <template x-for="department in departments" :key="department.id">
                                <option :value="department.id" x-text="department.department_name"></option>
                            </template>
                        </select>
                        <input type="hidden" x-model="jobOfferForm.department">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Company Address</label>
                    <textarea x-model="jobOfferForm.companyAddress" rows="2" readonly
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-700 text-sm bg-gray-100 resize-none"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Salary Grade from Payroll</label>
                        <select x-model="jobOfferForm.salaryGradeId" @change="onJobOfferSalaryGradeChange()"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm bg-white transition-all">
                            <option value="">Select Salary Grade...</option>
                            <template x-for="grade in salaryGrades" :key="grade.id">
                                <option :value="grade.id" x-text="`${grade.code || ''} - ${grade.name || ''} ${grade.monthly_basic_pay ? '(₱' + Number(grade.monthly_basic_pay).toLocaleString() + ')' : ''}`"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Salary</label>
                        <input type="text" x-model="jobOfferForm.salary" placeholder="₱0.00"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm bg-gray-50/50 transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Start Date</label>
                        <input type="date" x-model="jobOfferForm.startDate" required
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm bg-gray-50/50 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Employment Type</label>
                        <select x-model="jobOfferForm.employmentType" required
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm bg-gray-50/50 transition-all cursor-pointer appearance-none">
                            <template x-for="type in employmentTypeOptions" :key="type">
                                <option :value="type" x-text="type"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Benefits</label>
                    <textarea x-model="jobOfferForm.benefits" rows="4" placeholder="Health insurance, 401k, etc..."
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm bg-gray-50/50 resize-none"></textarea>
                </div>

                <div class="pt-4 border-t flex justify-end gap-3">
                    <button type="button" @click="showJobOfferModal = false"
                        class="px-6 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm font-bold hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-8 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-100 transition active:scale-95">
                        Save Draft
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== VIEW JOB OFFER SLIDE-OVER ===================== --}}
    <div x-show="showJobOfferViewModal" class="fixed inset-0 overflow-hidden z-[9999]" style="display:none;">
        <div @click="showJobOfferViewModal = false" class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity"
            x-show="showJobOfferViewModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="fixed inset-y-0 right-0 max-w-xl w-full flex pointer-events-none">
            <div x-show="showJobOfferViewModal" 
                x-transition:enter="transform transition ease-in-out duration-500"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="h-full w-full bg-white shadow-2xl flex flex-col pointer-events-auto">
                <template x-if="viewJobOfferData">
                    <div class="flex flex-col h-full">
                        {{-- Aesthetic Header --}}
                        <div class="h-32 bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center overflow-hidden shrink-0 relative">
                            <div class="absolute inset-0 opacity-10">
                                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0 100 C 20 0 50 0 100 100 Z" fill="white"></path></svg>
                            </div>
                            <div class="relative text-center">
                                <div class="inline-flex p-3 bg-white/20 backdrop-blur-md rounded-2xl mb-2 border border-white/30">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <h3 class="text-white font-black text-xl tracking-tight uppercase">Job Offer Details</h3>
                            </div>
                            <button @click="showJobOfferViewModal = false" class="absolute top-6 right-6 text-white/50 hover:text-white transition group bg-white/10 p-2 rounded-full backdrop-blur-md">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="flex-1 overflow-y-auto px-8 pt-8 pb-8 space-y-8 bg-white">
                            <div class="grid grid-cols-2 gap-8">
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Candidate Name</p>
                                    <p class="text-gray-900 font-bold text-lg" x-text="viewJobOfferData.name"></p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Position</p>
                                    <p class="text-blue-600 font-bold text-lg" x-text="viewJobOfferData.position"></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-8 pt-6 border-t border-gray-50 text-sm">
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Salary</p>
                                    <p class="text-gray-700 font-medium" x-text="viewJobOfferData.salary"></p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Start Date</p>
                                    <p class="text-gray-700 font-medium" x-text="viewJobOfferData.startDate || viewJobOfferData.start_date"></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-8 pt-6 border-t border-gray-50 text-sm">
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Employment Type</p>
                                    <span class="px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg font-bold text-[11px] uppercase tracking-wider border border-blue-100 w-fit" x-text="viewJobOfferData.employment_type || viewJobOfferData.employmentType"></span>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Department</p>
                                    <p class="text-gray-700 font-medium" x-text="viewJobOfferData.department || 'N/A'"></p>
                                </div>
                            </div>

                            <div class="space-y-2 pt-6 border-t border-gray-50">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Benefits</p>
                                <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                                    <p class="text-gray-600 text-[13px] leading-relaxed italic" x-text="viewJobOfferData.benefits || 'No benefits listed.'"></p>
                                </div>
                            </div>
                        </div>

                        <div class="px-8 py-6 border-t border-gray-100 flex gap-4 shrink-0">
                            <button @click="showJobOfferViewModal = false" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition uppercase tracking-widest text-[11px]">
                                Close
                            </button>
                            <button type="button"
                                @click="resendJobOfferEmail(viewJobOfferData)"
                                class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition shadow-lg shadow-blue-200 uppercase tracking-widest text-[11px]">
                                Send Job Offer
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- ===================== ADD NEW ASSESSMENT MODAL ===================== --}}
    <div
        x-show="showAssessmentModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[70] flex justify-center items-center bg-black/50 backdrop-blur-sm"
        style="display:none;"
        @click.self="showAssessmentModal = false"
    >
        <div 
            class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all"
            x-show="showAssessmentModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        >
            <div class="px-6 py-4 border-b flex items-center justify-between bg-white text-gray-800">
                <h3 class="font-bold text-lg tracking-tight">Add New Assessment</h3>
                <button @click="showAssessmentModal = false" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form @submit.prevent="submitAssessment()" class="p-6 space-y-5 bg-white">
                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Candidate Name</label>
                        <input type="text" x-model="assessmentForm.name" required placeholder="Enter name"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm transition-all bg-gray-50/50">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Position</label>
                        <select x-model="assessmentForm.position" required
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm transition-all bg-gray-50/50 cursor-pointer appearance-none">
                            <option value="">Select Position</option>
                            <template x-for="pos in uniqueCafPositions" :key="pos">
                                <option :value="pos" x-text="pos"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Email Address</label>
                    <input type="email" x-model="assessmentForm.email" required placeholder="candidate@email.com"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm transition-all bg-gray-50/50">
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Test Type</label>
                        <select x-model="assessmentForm.test" required
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm bg-gray-50/50 transition-all cursor-pointer appearance-none">
                            <option value="Technical Test">Technical Test</option>
                            <option value="Amplitude Test">Amplitude Test</option>
                            <option value="Personality Test">Personality Test</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Assessment Date</label>
                        <input type="date" x-model="assessmentForm.date" required
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm bg-gray-50/50 transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Notes</label>
                    <textarea x-model="assessmentForm.notes" rows="4" placeholder="Assessment notes..."
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm bg-gray-50/50 resize-none"></textarea>
                </div>

                <div class="pt-4 border-t flex justify-end gap-3">
                    <button type="button" @click="showAssessmentModal = false"
                        class="px-6 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm font-bold hover:bg-gray-50 transition">
                        Discard
                    </button>
                    <button type="submit"
                        class="px-8 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-100 transition active:scale-95">
                        Create Assessment
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== ADD NEW INTERVIEW MODAL ===================== --}}
    <div
        x-show="showInterviewModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[70] flex justify-center items-center bg-black/50 backdrop-blur-sm"
        style="display:none;"
        @click.self="showInterviewModal = false"
    >
        <div 
            class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all"
            x-show="showInterviewModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        >
            <div class="px-6 py-4 border-b flex items-center justify-between bg-white text-gray-800">
                <h3 class="font-bold text-lg tracking-tight">Add New Interview</h3>
                <button @click="showInterviewModal = false" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form @submit.prevent="submitInterview()" class="p-6 space-y-5 bg-white">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Passed Assessment Candidate</label>
                    <select x-model="interviewForm.assessmentId" @change="onInterviewAssessmentChange()" required
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm transition-all bg-gray-50/50 cursor-pointer appearance-none">
                        <option value="">Select Passed Assessment</option>
                        <template x-for="assessment in passedAssessmentCandidates" :key="assessment.id">
                            <option :value="assessment.id" x-text="`${assessment.name} - ${assessment.position}`"></option>
                        </template>
                    </select>
                    <p class="text-[11px] mt-1"
                       :class="passedAssessmentCandidates.length ? 'text-gray-500' : 'text-red-500'"
                       x-text="passedAssessmentCandidates.length ? 'Only Passed Assessment records are available for interview scheduling.' : 'No Passed Assessment available yet.'"></p>
                </div>

                <div class="grid grid-cols-3 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Candidate Name</label>
                        <input type="text" x-model="interviewForm.name" readonly required
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-100 text-gray-700 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Candidate Email</label>
                        <input type="email" x-model="interviewForm.email" readonly required
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-100 text-gray-700 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Position</label>
                        <input type="text" x-model="interviewForm.position" readonly required
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-100 text-gray-700 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Type of Interview</label>
                        <select x-model="interviewForm.type" required
                            @change="interviewForm.type === 'Online' ? generateMeetingLink() : interviewForm.meeting_link = ''"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm bg-gray-50/50 transition-all cursor-pointer appearance-none">
                            <option value="Online">Online</option>
                            <option value="In Person">In Person</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Interviewer</label>
                        <input type="text" x-model="interviewForm.interviewer" required placeholder="Name of interviewer"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm bg-gray-50/50 transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Date & Time</label>
                        <input type="datetime-local" x-model="interviewForm.interview_date" required
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm bg-gray-50/50 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Duration (minutes)</label>
                        <input type="number" x-model="interviewForm.duration" required placeholder="60"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm bg-gray-50/50 transition-all">
                    </div>
                </div>

                <div x-show="interviewForm.type === 'Online'">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Meeting Link</label>
                    <div class="flex gap-2 items-center">
                        <input type="url" x-model="interviewForm.meeting_link" placeholder="https://meet.jit.si/..."
                            class="flex-1 px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 text-gray-700 text-sm bg-gray-50/50 font-mono text-xs" readonly>
                        <button type="button" @click="generateMeetingLink()"
                            class="flex items-center gap-2 px-4 py-3 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-xs font-bold rounded-xl transition shadow-sm shadow-blue-200 whitespace-nowrap">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Generate
                        </button>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-1.5">Mock link — replace with a real meeting URL before sending.</p>
                </div>

                <div class="pt-4 border-t flex justify-end gap-3">
                    <button type="button" @click="showInterviewModal = false"
                        class="px-6 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm font-bold hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-8 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-100 transition active:scale-95">
                        Schedule Interview
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
function recruitmentPage(
    initialMRF = [],
    initialJPF = [],
    initialCAF = [],
    initialAssessment = [],
    initialInterview = [],
    initialJobOffer = [],
    initialOrganizationalAddresses = [],
    initialBranches = [],
    initialOffices = [],
    initialDepartments = [],
    initialDivisions = [],
    initialUnits = [],
    initialPositions = [],
    initialSalaryGrades = [],
    initialPayrollLevels = [],
    initialApprovalUsers = []
) {
    return {
        assessmentPolling: null,
        jobOfferFocusListenerAdded: false,
        currentUserId: {{ auth()->id() ?? 'null' }},
        currentUserRole: @json(auth()->user()->role ?? ''),
        approvalUsers: initialApprovalUsers,
        jpfStatusOptions: ['Draft', 'For Approval', 'Approved', 'Posted', 'Screening', 'Interviewing', 'Offer Stage', 'Filled', 'Closed', 'Cancelled'],
        employmentTypeOptions: ['Intern / OJT', 'Probationary', 'Regular', 'Project-Based', 'Fixed-Term', 'Part-Time', 'Casual / Temporary', 'Consultant / Independent Contractor', 'Others'],
        jobLevelRankOptions: ['Intern', 'Staff', 'Associate', 'Officer', 'Supervisor', 'Manager', 'Executive', 'Others'],
        workClassificationOptions: ['Office-Based', 'Field-Based', 'Project-Based', 'Operational', 'Others'],
        workArrangementOptions: ['On-site', 'Hybrid', 'Work-from-Home', 'Field Work', 'Others'],
        workScheduleOptions: ['8:00 A.M. - 5:00 P.M.', '9:00 A.M. - 6:00 P.M.', '10:00 A.M. - 7:00 P.M.', '11:00 A.M. - 8:00 P.M.', '1:00 P.M. - 10:00 P.M.', 'Flexible Schedule', 'Custom Schedule', 'Others'],
        contractDurationOptions: ['3 Months', '6 Months', '1 Year', 'Project Duration', 'Probationary Period', 'Permanent', 'Others'],
        urgencyLevelOptions: ['Normal', 'Urgent', 'Critical', 'Others'],
        benefitsChecklistOptions: [
            'Social Security System (SSS)',
            'PhilHealth',
            'Pag-IBIG Fund (HDMF)',
            '13th Month Pay',
            'Overtime Pay',
            'Night Differential Pay, if applicable',
            'Rest Day / Special Holiday Premium Pay, if applicable',
            'Maternity Benefits, per law',
            'Paternity Benefits, per law',
            'Solo Parent and other statutory leave benefits, if applicable',
            'Retirement Benefits as required by law or policy, if applicable',
            'Other benefits mandated under Philippine labor laws',
            'Bonus, Performance Incentive Schemes and Merit-Based Rewards',
            'Healthcare, Insurance, and Investment Benefit Plan after 6 months of employment, subject to company policy and eligibility',
            'Day Shift + Weekends Off',
            'No Work on Philippine Holidays, subject to operations',
            'Structured and Professional Work Environment',
            'Exposure to Corporate Advisory and Governance Practice',
            'Opportunity for Long-Term Growth Based on Performance',
            'Others'
        ],
        requiredDocumentOptions: [
            'Resume/CV',
            'Application Letter/Letter of Intent',
            'Employee Personal Data Sheet',
            'Pre-Employment Assessment Questionnaire',
            'Photocopy of any two valid government IDs with 3 specimen signatures',
            'Photocopy of birth certificate',
            'Photocopy of marriage certificate (if applicable)',
            "Photocopy of children's birth certificates (if applicable)",
            'Photocopy of diploma or transcript of records (TOR)',
            'Photocopy of solo parent ID (if applicable)',
            'Photocopy of PWD ID (if applicable)',
            'Original copy of barangay clearance',
            'Original copy of police clearance',
            'Original copy of NBI clearance',
            'Original community tax certificate/cedula',
            'Photocopy of the latest BIR form 2316/ITR',
            "Photocopy of PhilHealth member's data record",
            'Photocopy of Social Security System (SSS) ID',
            'Photocopy of Tax Identification Number (TIN) ID',
            'Photocopy of PAG-IBIG ID',
            'Sketch of present address',
            'Submit the remaining documents within 30 days of your starting date of employment:',
            '3pcs 1x1 colored ID picture with white background',
            '3pcs 2x2 colored ID picture with white background',
            'Original copy of pre-employment medical exam results (CBC, urinalysis, chest X-ray, stool exam, blood typing, drug test, complete physical examination)',
            'Others'
        ],

startAssessmentPolling() {
    this.refreshAssessments();
    this.refreshJobOffers();

    if (this.assessmentPolling) {
        clearInterval(this.assessmentPolling);
    }

    this.assessmentPolling = setInterval(() => {
        this.refreshAssessments();
        this.refreshJobOffers();
    }, 5000);
},


approvalTemplate(label = '') {
    return {
        label: label,
        approver_id: '',
        name: '',
        email: '',
        role: '',
        position: '',
        department: '',
        status: 'Pending',
        date: '',
        approved_at: '',
        decided_by: '',
        decided_by_name: ''
    };
},

applyApprover(levelKey) {
    const approval = this.jpfForm[levelKey] || this.approvalTemplate();
    const selected = this.approvalUsers.find(user => String(user.id) === String(approval.approver_id));

    if (!selected) {
        this.jpfForm[levelKey] = {
            ...approval,
            name: '',
            email: '',
            role: '',
            position: '',
            department: '',
            status: 'Pending',
            date: '',
            approved_at: '',
            decided_by: '',
            decided_by_name: ''
        };
        return;
    }

    this.jpfForm[levelKey] = {
        ...approval,
        approver_id: selected.id,
        name: selected.name || '',
        email: selected.email || '',
        role: selected.role || '',
        position: selected.position || '',
        department: selected.department || '',
        status: 'Pending',
        date: '',
        approved_at: '',
        decided_by: '',
        decided_by_name: ''
    };
},

approvalLevels(source = null) {
    const jpf = source || this.viewJpfData || {};

    return [
        { level: 'human-capital', label: 'Human Capital', data: jpf.human_capital_approval || {} },
        { level: 'hiring-manager', label: 'Hiring Manager', data: jpf.hiring_manager_approval || {} },
        { level: 'finance', label: 'Finance', data: jpf.finance_approval || {} },
        { level: 'president', label: 'President / Final', data: jpf.president_approval || {} }
    ];
},

currentActionApproval() {
    if (String(this.viewJpfData?.status || '').toLowerCase() !== 'for approval') return null;

    return this.approvalLevels().find(ap => {
        const status = this.approvalDisplayStatus(ap.data);
        return ['Pending', 'Hold'].includes(status) && this.canActOnApproval(ap.data);
    }) || null;
},

waitingApproval() {
    if (String(this.viewJpfData?.status || '').toLowerCase() !== 'for approval') return null;

    return this.approvalLevels().find(ap => {
        const status = this.approvalDisplayStatus(ap.data);
        return ['Pending', 'Hold'].includes(status) && !this.canActOnApproval(ap.data);
    }) || null;
},

approvalDisplayName(approval) {
    return (approval && (approval.name || approval.decided_by_name)) ? (approval.name || approval.decided_by_name) : '—';
},

approvalDisplayStatus(approval) {
    return (approval && approval.status) ? approval.status : 'Pending';
},

approvalDisplayDate(approval) {
    return (approval && (approval.date || approval.approved_at)) ? (approval.date || approval.approved_at) : '—';
},

canActOnApproval(approval) {
    if (!approval) return false;

    const status = this.approvalDisplayStatus(approval);
    if (['Approved', 'Cancelled'].includes(status)) return false;

    const hasAssignedApprover = approval.approver_id || approval.name || approval.email;
    if (!hasAssignedApprover) return false;

    const isSelectedApprover = approval.approver_id && String(approval.approver_id) === String(this.currentUserId);

    return isSelectedApprover;
},


pendingApprovalsFor(jpf) {
    if (!jpf || String(jpf.status || '').toLowerCase() !== 'for approval') return [];

    return this.approvalLevels(jpf).filter(ap => {
        const status = this.approvalDisplayStatus(ap.data);
        return ['Pending', 'Hold'].includes(status);
    });
},

myPendingApprovalsFor(jpf) {
    return this.pendingApprovalsFor(jpf).filter(ap => this.canActOnApproval(ap.data));
},

currentPendingApprovalFor(jpf) {
    const mine = this.myPendingApprovalsFor(jpf);
    if (mine.length > 0) return mine[0];

    const pending = this.pendingApprovalsFor(jpf);
    return pending.length > 0 ? pending[0] : null;
},

jpfNeedsMyApproval(jpf) {
    return this.myPendingApprovalsFor(jpf).length > 0;
},

myPendingJpfCount() {
    return (this.data['JPF'] || []).filter(row => this.jpfNeedsMyApproval(row)).length;
},


jpfApprovalItems(source = null) {
    const jpf = source || this.jpfForm || {};

    return [
        jpf.humanCapitalApproval || jpf.human_capital_approval || {},
        jpf.hiringManagerApproval || jpf.hiring_manager_approval || {},
        jpf.financeApproval || jpf.finance_approval || {},
        jpf.presidentApproval || jpf.president_approval || {}
    ];
},

jpfApprovalProgress(source = null) {
    const items = this.jpfApprovalItems(source);
    return items.filter(item => this.approvalDisplayStatus(item) === 'Approved').length;
},

jpfAllApprovalsApproved(source = null) {
    return this.jpfApprovalProgress(source) === 4;
},

jpfStatusOptionAllowed(status) {
    if (['Draft', 'For Approval', 'Cancelled'].includes(status)) return true;

    if (status === 'Approved') {
        return this.jpfAllApprovalsApproved(this.jpfForm);
    }

    if (['Posted', 'Screening', 'Interviewing', 'Offer Stage', 'Filled', 'Closed'].includes(status)) {
        return this.jpfAllApprovalsApproved(this.jpfForm);
    }

    return false;
},

jpfStatusHelpText() {
    const approvedCount = this.jpfApprovalProgress(this.jpfForm);

    if (approvedCount < 4) {
        return `${approvedCount}/4 approvals completed. This JPF will stay For Approval and cannot be Posted yet.`;
    }

    return '4/4 approvals completed. You may now set the status to Posted when HR is ready to publish it.';
},

jpfCanBePublic(jpf) {
    const status = String(jpf?.status || '').toLowerCase();
    return ['posted', 'screening'].includes(status) && this.jpfAllApprovalsApproved(jpf);
},

approvalBadgeClass(approval) {
    const status = this.approvalDisplayStatus(approval);

    if (status === 'Approved') return 'bg-green-100 text-green-700 border-green-200';
    if (status === 'Hold') return 'bg-yellow-100 text-yellow-700 border-yellow-200';
    if (status === 'Cancelled') return 'bg-red-100 text-red-700 border-red-200';

    return 'bg-gray-100 text-gray-600 border-gray-200';
},

approveJpfLevel(level, status) {
    if (!this.viewJpfData || !this.viewJpfData.id) return;

    axios.post(`/human-capital/recruitment/jpf/${this.viewJpfData.id}/approval/${level}`, { status })
        .then(res => {
            if (!res.data.success) {
                alert(res.data.message || 'Unable to update approval.');
                return;
            }

            const item = res.data.data;
            this.viewJpfData = { ...item };

            const idx = this.data['JPF'].findIndex(j => j.id === item.id);
            if (idx !== -1) {
                this.data['JPF'][idx] = {
                    ...item,
                    jobId: item.job_id,
                    type: item.employment_type,
                    posted: item.posted_date
                };
            }

            alert(res.data.message || 'Approval updated.');
        })
        .catch(err => {
            alert('Approval update failed: ' + (err.response?.data?.message || err.message));
        });
},


refreshAssessments() {
    axios.get('/human-capital/recruitment/assessment/latest?ts=' + Date.now())
        .then(res => {
            if (res.data.success) {
                this.data['Assessment'] = res.data.data;
                console.log('Assessment polling updated:', res.data.data);
            }
        })
        .catch(err => {
            console.error('Assessment polling failed:', err);
        });
},


refreshJobOffers() {
    axios.get('/human-capital/recruitment/job-offer/latest?ts=' + Date.now())
        .then(res => {
            if (res.data.success) {
                this.data['Job Offer'] = res.data.data;

                if (this.viewJobOfferData && this.viewJobOfferData.id) {
                    const updated = this.data['Job Offer'].find(item =>
                        String(item.id) === String(this.viewJobOfferData.id)
                    );

                    if (updated) {
                        this.viewJobOfferData = updated;
                    }
                }

                console.log('Job Offer polling updated:', res.data.data);
            }
        })
        .catch(err => {
            console.error('Job Offer polling failed:', err);
        });
},


        activeTab: 'MRF',
        search: '',
        paperSize: 'a4',
        perPage: 50,
        currentPage: 1,
        showFilter: false,
        filterStatus: 'All',
        filterPosition: 'All',
        jpfListView: 'all',
        showModal: false,
        showViewModal: false,
        showJpfModal: false,
        showJpfViewModal: false,
        showCafModal: false,
        showCafViewModal: false,
        showInterviewModal: false,
        showInterviewViewModal: false,
        showAssessmentModal: false,
        showAssessmentViewModal: false,
        showJobOfferModal: false,
        showJobOfferViewModal: false,
        linkCopied: false,
        isEditing: false,
        editingId: null,
        viewData: null,
        viewJpfData: {},
        viewCafData: null,
        viewAssessmentData: null,
        viewInterviewData: null,
        viewJobOfferData: null,

        organizationalAddresses: initialOrganizationalAddresses,
        branches: initialBranches,
        offices: initialOffices,
        departments: initialDepartments,
        divisions: initialDivisions,
        units: initialUnits,
        positions: initialPositions,
        salaryGrades: initialSalaryGrades,
        payrollLevels: initialPayrollLevels,

        tabs: [
            { key: 'MRF',        label: 'Manpower Request Form' },
            { key: 'JPF',        label: 'Job Placement Form' },
            { key: 'CAF',        label: 'Candidate Application Form' },
            { key: 'Assessment', label: 'Assessment' },
            { key: 'Interview',  label: 'Interview' },
            { key: 'Job Offer',  label: 'Job Offer' },
        ],

        jpfWorkScheduleOptions: [
            { key: 'every_day', label: 'Monday to Sunday – 8:00 AM to 5:00 PM' },
            { key: 'no_sat_sun', label: 'Monday to Friday – 8:00 AM to 5:00 PM' },
            { key: 'no_sunday', label: 'Monday to Saturday – 8:00 AM to 5:00 PM' },
            { key: 'shifting', label: 'Shifting Schedule' },
            { key: 'night_shift', label: 'Night Shift' },
            { key: 'hybrid', label: 'Hybrid' },
            { key: 'work_from_home', label: 'Work From Home' },
            { key: 'flexible', label: 'Flexible' },
        ],

        data: {
            'MRF': initialMRF.map(item => ({
                ...item,
                req_id_display: item.request_id, 
                date_display: item.date_requested
            })),
            'JPF': initialJPF.map(item => ({
                ...item,
                job_id_display: item.job_id,
                posted_display: item.posted_date
            })),
            'CAF':        initialCAF,
            'Assessment': initialAssessment,
            'Interview':  initialInterview,
            'Job Offer':  initialJobOffer,
        },

        form: {
            orgAddressId: '',
            orgBranchId: '',
            orgOfficeId: '',
            orgDepartmentId: '',
            orgDivisionId: '',
            orgUnitId: '',
            orgPositionId: '',

            requestId: '',
            department: '', dateRequested: '', dateRequired: '',
            position: '', employmentType: '', employmentTypeOther: '',
            immediateSupervisor: '', targetStartDate: '',
            immediateSupervisorOther: '',
            jobLevelRank: '', jobLevelRankOther: '',
            workClassification: '', workClassificationOther: '',
            workArrangement: '', workArrangementOther: '',
            workSchedule: '', workScheduleOther: '',
            duties: '', natureOfRequest: '', ageRange: '',
            civilStatus: 'No Preference', gender: 'No Preference',
            headcount: '', education: '', qualifications: '',
            requiredSkills: '', benefitsChecklist: [], benefitsChecklistOther: '',
            requiredLicenses: '', requiredDocuments: [], requiredDocumentsOther: '',
            salaryMin: '', salaryMax: '',
            contractDuration: '', contractDurationOther: '',
            urgencyLevel: '', urgencyLevelOther: '',
            candidateProfileAttached: false, jobDescriptionAttached: false,
            candidateProfileFile: null, jobDescriptionFile: null,
            immediateSupervisorEndorsement: '', departmentHeadEndorsement: '',
            hcHeadValidation: '', financeHeadClearance: '',
            immediateSupervisorEndorsementOther: '', departmentHeadEndorsementOther: '',
            hcHeadValidationOther: '', financeHeadClearanceOther: '',
            requestedBy: '', approvedBy: '',
            remarks: '', requestStatus: '',
            chargedTo: '', breakdownDetails: '',
            hiredPersonnel: '', dateHired: '',
            processedBy: '', checkedBy: '',
        },

        jpfForm: {
            // REQUISITION DETAILS
            jobId: '', mrfId: '', relatedMrfNo: '', dateOpened: '', hiringStatus: 'Open',

            // ORGANIZATIONAL LINKS (frontend only for now)
            orgAddressId: '', orgBranchId: '', orgOfficeId: '', orgDepartmentId: '', orgDivisionId: '', orgUnitId: '', orgPositionId: '',

            // COMPANY DETAILS
            companyName: '', officeBranchSite: '', departmentUnit: '', hiringManager: '', departmentSuperior: '',

            // POSITION DETAILS
            position: '', noOfVacancies: '', positionLevel: '', employmentType: '', reportsTo: '', workLocation: '',

            // SALARY OFFER / PAYROLL LINK (frontend only for now)
            salaryGradeId: '', payrollLevelId: '', minSalary: '', maxSalary: '', salaryGrade: '',

            // WAGE COMPLIANCE
            applicableRegion: 'Central Visayas', applicableArea: '', dailyMinWage: '', monthlyEquivalent: '',
            wageCompliance: [], // confirmed, above, allowances

            // BENEFITS PACKAGE
            benefits: ['SSS', 'PhilHealth', 'Pag-IBIG'],

            // WORK SCHEDULE
            workSchedule: [], // Mon-Fri, Mon-Sat, etc.
            workScheduleKey: '',
            restDays: '',

            // JOB REQUIREMENTS
            education: '', experience: '', skills: '', licenses: '', preferredQualifications: '',

            // DUTIES
            duties: '',

            // CHANNELS
            channels: [], // JobStreet, Indeed, etc.

            // SCREENING FLOW
            screeningFlow: [], // Resume Screening, etc.

            // TARGET TIMELINE
            dateNeeded: '', postingStartDate: '', targetHireDate: '',

            // APPROVALS
            humanCapitalApproval: { label: 'Human Capital', approver_id: '', name: '', email: '', role: '', position: '', department: '', status: 'Pending', date: '', approved_at: '', decided_by: '', decided_by_name: '' },
            hiringManagerApproval: { label: 'Hiring Manager', approver_id: '', name: '', email: '', role: '', position: '', department: '', status: 'Pending', date: '', approved_at: '', decided_by: '', decided_by_name: '' },
            financeApproval: { label: 'Finance', approver_id: '', name: '', email: '', role: '', position: '', department: '', status: 'Pending', date: '', approved_at: '', decided_by: '', decided_by_name: '' },
            presidentApproval: { label: 'President / Final', approver_id: '', name: '', email: '', role: '', position: '', department: '', status: 'Pending', date: '', approved_at: '', decided_by: '', decided_by_name: '' },

            // STATUS
            status: 'Draft'
        },

        cafForm: {
            jobPostingId: '',
            applicantType: 'New Applicant',
            internalRemarks: '',
            fullName: '', positionApplied: '', email: '', phone: '', 
            photo: null, cv: null, coverLetterFile: null, coverLetter: ''
        },

        assessmentForm: {
            name: '', email: '', position: '', test: 'Technical Test', date: '', notes: '', caf_id: null
        },

        interviewForm: {
            assessmentId: '',
            name: '', email: '', position: '', type: 'Online', interviewer: '', 
            interview_date: '', duration: '60', meeting_link: ''
        },

        jobOfferForm: {
            interviewId: '',
            jobPostingId: '',
            orgAddressId: '', orgBranchId: '', orgOfficeId: '', orgDepartmentId: '', orgDivisionId: '', orgUnitId: '', orgPositionId: '', salaryGradeId: '',
            name: '', position: '', salary: '', startDate: '',
            employmentType: 'Probationary', department: '', companyAddress: '', benefits: ''
        },

        draggedItem: null,
        onDragStart(item) { this.draggedItem = item; },
        onDrop(status) {
            if (this.draggedItem) {
                const oldStatus = this.draggedItem.status;
                this.draggedItem.status = status;
                
                axios.post(`/human-capital/recruitment/assessment/${this.draggedItem.id}/status`, { status: status })
                .catch(err => {
                    this.draggedItem.status = oldStatus;
                    console.error('Failed to update status:', err);
                });
                
                this.draggedItem = null;
            }
        },


        get selectedAddress() {
            return this.organizationalAddresses.find(a => String(a.id) === String(this.form.orgAddressId)) || null;
        },

        get selectedBranch() {
            return this.branches.find(b => String(b.id) === String(this.form.orgBranchId)) || null;
        },

        get selectedOffice() {
            return this.offices.find(o => String(o.id) === String(this.form.orgOfficeId)) || null;
        },

        get selectedDepartment() {
            return this.departments.find(d => String(d.id) === String(this.form.orgDepartmentId)) || null;
        },

        get selectedDivision() {
            return this.divisions.find(d => String(d.id) === String(this.form.orgDivisionId)) || null;
        },

        get selectedUnit() {
            return this.units.find(u => String(u.id) === String(this.form.orgUnitId)) || null;
        },

        get selectedPosition() {
            return this.positions.find(p => String(p.id) === String(this.form.orgPositionId)) || null;
        },

        get filteredBranches() {
            if (!this.form.orgAddressId) return this.branches;
            return this.branches.filter(branch => String(branch.address_id) === String(this.form.orgAddressId));
        },

        get filteredOffices() {
            if (this.form.orgBranchId) {
                return this.offices.filter(office => String(office.branch_id) === String(this.form.orgBranchId));
            }
            if (this.form.orgAddressId) {
                return this.offices.filter(office => String(office.address_id) === String(this.form.orgAddressId));
            }
            return this.offices;
        },

        get filteredDepartments() {
            if (this.form.orgOfficeId) {
                return this.departments.filter(department => String(department.office_id) === String(this.form.orgOfficeId));
            }
            if (this.form.orgAddressId) {
                return this.departments.filter(department => String(department.address_id) === String(this.form.orgAddressId));
            }
            return this.departments;
        },

        get filteredDivisions() {
            if (this.form.orgDepartmentId) {
                return this.divisions.filter(division => String(division.department_id) === String(this.form.orgDepartmentId));
            }
            if (this.form.orgAddressId) {
                return this.divisions.filter(division => String(division.address_id) === String(this.form.orgAddressId));
            }
            return this.divisions;
        },

        get filteredUnits() {
            if (this.form.orgDivisionId) {
                return this.units.filter(unit => String(unit.division_id) === String(this.form.orgDivisionId));
            }
            if (this.form.orgAddressId) {
                return this.units.filter(unit => String(unit.address_id) === String(this.form.orgAddressId));
            }
            return this.units;
        },

        get filteredPositions() {
            if (this.form.orgUnitId) {
                return this.positions.filter(position => String(position.unit_id) === String(this.form.orgUnitId));
            }
            if (this.form.orgAddressId) {
                return this.positions.filter(position => String(position.address_id) === String(this.form.orgAddressId));
            }
            return this.positions;
        },

        onOrgAddressChange() {
            this.form.orgBranchId = '';
            this.form.orgOfficeId = '';
            this.form.orgDepartmentId = '';
            this.form.orgDivisionId = '';
            this.form.orgUnitId = '';
            this.form.orgPositionId = '';
            this.form.department = '';
            this.form.position = '';
        },

        onOrgBranchChange() {
            this.form.orgOfficeId = '';
            this.form.orgDepartmentId = '';
            this.form.orgDivisionId = '';
            this.form.orgUnitId = '';
            this.form.orgPositionId = '';
            this.form.department = '';
            this.form.position = '';
        },

        onOrgOfficeChange() {
            this.form.orgDepartmentId = '';
            this.form.orgDivisionId = '';
            this.form.orgUnitId = '';
            this.form.orgPositionId = '';
            this.form.department = '';
            this.form.position = '';
        },

        onOrgDepartmentChange() {
            this.form.orgDivisionId = '';
            this.form.orgUnitId = '';
            this.form.orgPositionId = '';
            this.form.position = '';

            if (this.selectedDepartment) {
                this.form.department = this.selectedDepartment.department_name;
                this.form.chargedTo = this.selectedDepartment.department_name;
            } else {
                this.form.department = '';
            }
        },

        onOrgDivisionChange() {
            this.form.orgUnitId = '';
            this.form.orgPositionId = '';
            this.form.position = '';
        },

        onOrgUnitChange() {
            this.form.orgPositionId = '';
            this.form.position = '';
        },

        onOrgPositionChange() {
            if (this.selectedPosition) {
                this.form.position = this.selectedPosition.position_name;
            } else {
                this.form.position = '';
            }
        },

        get approvedMRFs() {
            return this.data['MRF'].filter(mrf => {
                const status = String(mrf.request_status || '').toLowerCase();
                return status === 'approved';
            });
        },

        get selectedJpfMrf() {
            return this.data['MRF'].find(mrf => String(mrf.id) === String(this.jpfForm.mrfId)) || null;
        },

        get selectedJpfAddress() {
            return this.organizationalAddresses.find(a => String(a.id) === String(this.jpfForm.orgAddressId)) || null;
        },

        get selectedJpfDepartment() {
            return this.departments.find(d => String(d.id) === String(this.jpfForm.orgDepartmentId)) || null;
        },

        get selectedJpfPosition() {
            return this.positions.find(p => String(p.id) === String(this.jpfForm.orgPositionId)) || null;
        },

        get selectedJpfSalaryGrade() {
            return this.salaryGrades.find(g => String(g.id) === String(this.jpfForm.salaryGradeId)) || null;
        },

        get jpfFilteredPayrollLevels() {
            if (!this.jpfForm.salaryGradeId) return this.payrollLevels;
            return this.payrollLevels.filter(level => String(level.salary_grade_id) === String(this.jpfForm.salaryGradeId));
        },

        get selectedJpfPayrollLevel() {
            return this.payrollLevels.find(level => String(level.id) === String(this.jpfForm.payrollLevelId)) || null;
        },

        formatPayrollLevelOption(level) {
            if (!level) return '';
            const schedule = this.formatPayrollScheduleLabel(level);
            const hours = level.hours_per_day ? `${Number(level.hours_per_day)} hrs/day` : '';
            return [level.level_name, schedule, hours].filter(Boolean).join(' • ');
        },


formatPayrollScheduleLabel(level) {
    return this.normalizeJpfWorkScheduleLabel(level?.work_schedule_label || level?.work_schedule || '');
},

        get jpfFilteredDepartments() {
            if (!this.jpfForm.orgAddressId) return this.departments;
            return this.departments.filter(department => String(department.address_id) === String(this.jpfForm.orgAddressId));
        },

        get jpfFilteredPositions() {
            if (!this.jpfForm.orgAddressId) return this.positions;
            return this.positions.filter(position => String(position.address_id) === String(this.jpfForm.orgAddressId));
        },

        formatApplicableAreaFromAddress(address) {
            if (!address) return '';

            const areaParts = [
                address.barangay_name,
                address.city_name,
                address.province_name,
                address.region_name
            ].filter(Boolean);

            return areaParts.length ? areaParts.join(', ') : (address.full_address || '');
        },

        onJpfMrfChange() {
            const mrf = this.selectedJpfMrf;

            if (!mrf) {
                this.jpfForm.relatedMrfNo = '';
                this.jpfForm.employmentType = '';
                this.jpfForm.applicableArea = '';
                return;
            }

            if (String(mrf.request_status || '').toLowerCase() !== 'approved') {
                alert('Only approved MRF records can be used to create a JPF.');
                this.jpfForm.mrfId = '';
                this.jpfForm.relatedMrfNo = '';
                return;
            }

            const toValue = (value) => value === null || value === undefined ? '' : String(value);

            this.jpfForm.relatedMrfNo = mrf.request_id || '';
            this.jpfForm.orgAddressId = toValue(mrf.address_id);
            this.jpfForm.orgBranchId = toValue(mrf.branch_id);
            this.jpfForm.orgOfficeId = toValue(mrf.office_id);
            this.jpfForm.orgDepartmentId = toValue(mrf.department_id);
            this.jpfForm.orgDivisionId = toValue(mrf.division_id);
            this.jpfForm.orgUnitId = toValue(mrf.unit_id);
            this.jpfForm.orgPositionId = toValue(mrf.position_id);

            const address = this.organizationalAddresses.find(item => String(item.id) === String(mrf.address_id)) || null;
            const department = this.departments.find(item => String(item.id) === String(mrf.department_id)) || null;
            const position = this.positions.find(item => String(item.id) === String(mrf.position_id)) || null;

            this.jpfForm.officeBranchSite = address ? (address.full_address || '') : '';
            this.jpfForm.workLocation = address ? (address.full_address || '') : '';
            this.jpfForm.applicableArea = this.formatApplicableAreaFromAddress(address);

            this.jpfForm.departmentUnit = department ? (department.department_name || '') : (mrf.department || '');
            this.jpfForm.departmentSuperior = department ? (department.department_head || '') : '';
            this.jpfForm.hiringManager = department ? (department.department_head || this.jpfForm.hiringManager || '') : this.jpfForm.hiringManager;

            this.jpfForm.position = position ? (position.position_name || '') : (mrf.position || '');
            this.jpfForm.noOfVacancies = mrf.headcount || '';
            this.jpfForm.employmentType = mrf.employment_type || '';
            this.jpfForm.positionLevel = mrf.job_level_rank || this.jpfForm.positionLevel || '';
            this.jpfForm.reportsTo = mrf.immediate_supervisor || this.jpfForm.reportsTo || '';
            this.jpfForm.workSchedule = mrf.work_schedule ? [mrf.work_schedule] : this.jpfForm.workSchedule;
            this.jpfForm.minSalary = mrf.salary_min || this.jpfForm.minSalary || '';
            this.jpfForm.maxSalary = mrf.salary_max || this.jpfForm.maxSalary || '';
            this.jpfForm.benefits = Array.isArray(mrf.benefits_checklist) ? mrf.benefits_checklist : this.jpfForm.benefits;
            this.jpfForm.dateNeeded = this.toDateInput(mrf.date_required);
            this.jpfForm.targetHireDate = this.toDateInput(mrf.target_start_date) || this.jpfForm.targetHireDate;
            this.jpfForm.duties = mrf.duties || '';
            this.jpfForm.education = mrf.education || '';
            this.jpfForm.skills = mrf.required_skills || this.jpfForm.skills || '';
            this.jpfForm.licenses = mrf.required_licenses || this.jpfForm.licenses || '';
            this.jpfForm.preferredQualifications = mrf.qualifications || '';
        },

        toDateInput(value) {
            if (!value) return '';

            const raw = String(value);
            if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
                return raw;
            }

            const date = new Date(raw);
            if (Number.isNaN(date.getTime())) {
                return '';
            }

            return date.toISOString().split('T')[0];
        },

        onJpfAddressChange() {
            const address = this.selectedJpfAddress;
            this.jpfForm.officeBranchSite = address ? address.full_address : '';
            this.jpfForm.workLocation = address ? address.full_address : '';
            this.jpfForm.applicableArea = this.formatApplicableAreaFromAddress(address);
            this.jpfForm.orgBranchId = '';
            this.jpfForm.orgOfficeId = '';
            this.jpfForm.orgDepartmentId = '';
            this.jpfForm.orgDivisionId = '';
            this.jpfForm.orgUnitId = '';
            this.jpfForm.orgPositionId = '';
            this.jpfForm.departmentUnit = '';
            this.jpfForm.departmentSuperior = '';
            this.jpfForm.position = '';
        },

        onJpfDepartmentChange() {
            const department = this.selectedJpfDepartment;
            this.jpfForm.departmentUnit = department ? department.department_name : '';
            this.jpfForm.departmentSuperior = department ? (department.department_head || '') : '';
            this.jpfForm.hiringManager = department ? (department.department_head || this.jpfForm.hiringManager || '') : this.jpfForm.hiringManager;
        },

        onJpfPositionChange() {
            const position = this.selectedJpfPosition;
            this.jpfForm.position = position ? position.position_name : '';
        },

        onJpfSalaryGradeChange() {
            const grade = this.selectedJpfSalaryGrade;
            if (!grade) {
                this.jpfForm.salaryGrade = '';
                this.jpfForm.payrollLevelId = '';
                this.jpfForm.workSchedule = [];
                this.jpfForm.workScheduleKey = '';
                this.jpfForm.restDays = '';
                return;
            }

            this.jpfForm.salaryGrade = [grade.code, grade.name].filter(Boolean).join(' - ');
            const monthly = Number(grade.monthly_basic_pay || 0);
            const daily = Number(grade.applicable_daily_rate || 0);

            if (monthly > 0) {
                this.jpfForm.minSalary = monthly;
                this.jpfForm.maxSalary = monthly;
                this.jpfForm.monthlyEquivalent = monthly;
            }

            if (daily > 0) {
                this.jpfForm.dailyMinWage = daily;
            }

            const selectedLevelStillValid = this.jpfFilteredPayrollLevels.some(level => String(level.id) === String(this.jpfForm.payrollLevelId));

            if (!selectedLevelStillValid) {
                this.jpfForm.payrollLevelId = '';
                this.jpfForm.workSchedule = [];
                this.jpfForm.workScheduleKey = '';
                this.jpfForm.restDays = '';
            }

            if (!this.jpfForm.payrollLevelId && this.jpfFilteredPayrollLevels.length === 1) {
                this.jpfForm.payrollLevelId = this.jpfFilteredPayrollLevels[0].id;
                this.onJpfPayrollLevelChange();
            }
        },


normalizeJpfWorkScheduleLabel(value) {
    const raw = String(value || '').trim();

    const legacyMap = {
        every_day: 'Monday to Sunday – 8:00 AM to 5:00 PM',
        no_sunday: 'Monday to Saturday – 8:00 AM to 5:00 PM',
        no_saturday: 'Monday to Friday – 8:00 AM to 5:00 PM',
        no_sat_sun: 'Monday to Friday – 8:00 AM to 5:00 PM',
        no_sat_sun_holidays: 'Monday to Friday – 8:00 AM to 5:00 PM',
    };

    return legacyMap[raw] || raw;
},

getJpfWorkScheduleOptionByLabel(label) {
    const normalized = this.normalizeJpfWorkScheduleLabel(label);
    return this.jpfWorkScheduleOptions.find(option => option.label === normalized) || null;
},

getJpfRestDaysByLabel(label) {
    const normalized = this.normalizeJpfWorkScheduleLabel(label);

    const restDayMap = {
        'Monday to Sunday – 8:00 AM to 5:00 PM': 'None',
        'Monday to Saturday – 8:00 AM to 5:00 PM': 'Sunday',
        'Monday to Friday – 8:00 AM to 5:00 PM': 'Saturday and Sunday',
    };

    return restDayMap[normalized] || '';
},

isJpfWorkScheduleChecked(ws) {
    if (!Array.isArray(this.jpfForm.workSchedule)) {
        this.jpfForm.workSchedule = [];
    }

    return this.jpfForm.workSchedule.includes(ws.label);
},

toggleJpfWorkSchedule(ws, checked) {
    if (this.jpfForm.payrollLevelId) {
        return;
    }

    const label = typeof ws === 'string' ? ws : ws.label;
    const key = typeof ws === 'string' ? '' : ws.key;

    if (!Array.isArray(this.jpfForm.workSchedule)) {
        this.jpfForm.workSchedule = [];
    }

    if (checked) {
        if (!this.jpfForm.workSchedule.includes(label)) {
            this.jpfForm.workSchedule.push(label);
        }

        this.jpfForm.workScheduleKey = key;
    } else {
        this.jpfForm.workSchedule = this.jpfForm.workSchedule.filter(item => item !== label);

        if (this.jpfForm.workScheduleKey === key) {
            this.jpfForm.workScheduleKey = '';
        }
    }
},

onJpfPayrollLevelChange() {
    const level = this.selectedJpfPayrollLevel;

    if (!level) {
        this.jpfForm.workSchedule = [];
        this.jpfForm.workScheduleKey = '';
        this.jpfForm.restDays = '';
        return;
    }

    const label = this.normalizeJpfWorkScheduleLabel(level.work_schedule_label || level.work_schedule);
    const option = this.getJpfWorkScheduleOptionByLabel(label);

    this.jpfForm.workScheduleKey = option ? option.key : '';
    this.jpfForm.workSchedule = label ? [label] : [];
    this.jpfForm.restDays = this.getJpfRestDaysByLabel(label);
},

        get validJobOfferInterviews() {
            return this.data['Interview'].filter(interview => {
                const status = String(interview.status || '').toLowerCase();
                return ['completed', 'passed'].includes(status);
            });
        },

        get selectedJobOfferInterview() {
            return this.data['Interview'].find(item => String(item.id) === String(this.jobOfferForm.interviewId)) || null;
        },

        onJobOfferInterviewChange() {
            const interview = this.selectedJobOfferInterview;

            if (!interview) {
                this.jobOfferForm.name = '';
                this.jobOfferForm.position = '';
                return;
            }

            if (!this.interviewAllowsJobOffer(interview)) {
                alert('Only Completed or Passed interviews can be used for Job Offer.');
                this.jobOfferForm.interviewId = '';
                this.jobOfferForm.name = '';
                this.jobOfferForm.position = '';
                return;
            }

            this.jobOfferForm.name = interview.name || '';
            this.jobOfferForm.position = interview.position || this.jobOfferForm.position || '';

            const matchedPosition = this.positions.find(position =>
                String(position.position_name || '').toLowerCase() === String(this.jobOfferForm.position || '').toLowerCase()
            );

            if (matchedPosition && !this.jobOfferForm.orgPositionId) {
                this.jobOfferForm.orgPositionId = String(matchedPosition.id);
            }
        },

        get validJobOfferJPFs() {
            return this.data['JPF'].filter(jpf => {
                const status = String(jpf.status || '').toLowerCase();
                return ['posted', 'screening', 'interviewing', 'offer stage'].includes(status) && this.jpfAllApprovalsApproved(jpf);
            });
        },

        get selectedJobOfferJpf() {
            return this.data['JPF'].find(j => String(j.id) === String(this.jobOfferForm.jobPostingId)) || null;
        },

        get selectedJobOfferAddress() {
            return this.organizationalAddresses.find(a => String(a.id) === String(this.jobOfferForm.orgAddressId)) || null;
        },

        get selectedJobOfferDepartment() {
            return this.departments.find(d => String(d.id) === String(this.jobOfferForm.orgDepartmentId)) || null;
        },

        get selectedJobOfferPosition() {
            return this.positions.find(p => String(p.id) === String(this.jobOfferForm.orgPositionId)) || null;
        },

        get selectedJobOfferSalaryGrade() {
            return this.salaryGrades.find(g => String(g.id) === String(this.jobOfferForm.salaryGradeId)) || null;
        },

        onJobOfferJpfChange() {
            const jpf = this.selectedJobOfferJpf;

            if (!jpf) {
                return;
            }

            const status = String(jpf.status || '').toLowerCase();

            if (!['posted', 'open'].includes(status)) {
                alert('Only JPF records with status Posted/Open can be used for Job Offer.');
                this.jobOfferForm.jobPostingId = '';
                return;
            }

            const toValue = (value) => value === null || value === undefined ? '' : String(value);

            this.jobOfferForm.orgAddressId = toValue(jpf.address_id);
            this.jobOfferForm.orgBranchId = toValue(jpf.branch_id);
            this.jobOfferForm.orgOfficeId = toValue(jpf.office_id);
            this.jobOfferForm.orgDepartmentId = toValue(jpf.department_id);
            this.jobOfferForm.orgDivisionId = toValue(jpf.division_id);
            this.jobOfferForm.orgUnitId = toValue(jpf.unit_id);
            this.jobOfferForm.orgPositionId = toValue(jpf.position_id);
            this.jobOfferForm.salaryGradeId = toValue(jpf.salary_grade_id);

            this.jobOfferForm.position = jpf.position || '';
            this.jobOfferForm.department = jpf.department_unit || jpf.departmentUnit || '';
            this.jobOfferForm.employmentType = jpf.employment_type || jpf.employmentType || this.jobOfferForm.employmentType;
            this.jobOfferForm.companyAddress = jpf.office_branch_site || jpf.officeBranchSite || jpf.location || jpf.workLocation || '';

            const minSalary = jpf.min_salary_offer || jpf.minSalary || '';
            const maxSalary = jpf.max_salary_offer || jpf.maxSalary || '';
            this.jobOfferForm.salary = minSalary && maxSalary
                ? `${minSalary} - ${maxSalary}`
                : (minSalary || jpf.salary_range || jpf.salaryRange || this.jobOfferForm.salary);

            this.jobOfferForm.benefits = Array.isArray(jpf.benefits_package)
                ? jpf.benefits_package.join(', ')
                : (jpf.benefits_package || jpf.benefits || this.jobOfferForm.benefits);

            const address = this.selectedJobOfferAddress;
            if (address && !this.jobOfferForm.companyAddress) {
                this.jobOfferForm.companyAddress = address.full_address || '';
            }

            const department = this.selectedJobOfferDepartment;
            if (department && !this.jobOfferForm.department) {
                this.jobOfferForm.department = department.department_name || '';
            }

            const position = this.selectedJobOfferPosition;
            if (position && !this.jobOfferForm.position) {
                this.jobOfferForm.position = position.position_name || '';
            }

            const salaryGrade = this.selectedJobOfferSalaryGrade;
            if (salaryGrade && !this.jobOfferForm.salary) {
                const monthly = Number(salaryGrade.monthly_basic_pay || 0);
                this.jobOfferForm.salary = monthly > 0
                    ? `₱ ${monthly.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
                    : [salaryGrade.code, salaryGrade.name].filter(Boolean).join(' - ');
            }
        },

        onJobOfferAddressChange() {
            const address = this.selectedJobOfferAddress;
            this.jobOfferForm.companyAddress = address ? address.full_address : '';
        },

        onJobOfferDepartmentChange() {
            const department = this.selectedJobOfferDepartment;
            this.jobOfferForm.department = department ? department.department_name : '';
        },

        onJobOfferPositionChange() {
            const position = this.selectedJobOfferPosition;
            this.jobOfferForm.position = position ? position.position_name : '';
        },

        onJobOfferSalaryGradeChange() {
            const grade = this.selectedJobOfferSalaryGrade;
            if (!grade) return;
            const monthly = Number(grade.monthly_basic_pay || 0);
            this.jobOfferForm.salary = monthly > 0
                ? `₱ ${monthly.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
                : [grade.code, grade.name].filter(Boolean).join(' - ');
        },

        viewAssessment(item) {
            this.viewAssessmentData = item;
            if (this.viewAssessmentData.score) {
                this.viewAssessmentData.score_raw = parseInt(this.viewAssessmentData.score);
            }
            if (!this.viewAssessmentData.test_type && this.viewAssessmentData.test) {
                this.viewAssessmentData.test_type = this.viewAssessmentData.test;
            }
            this.showAssessmentViewModal = true;
        },

        submitAssessmentResult(event) {
            const score = Number(this.viewAssessmentData.score_raw);
            if (!Number.isFinite(score) || score < 0 || score > 100) {
                alert('Please enter a valid score between 0 and 100.');
                return;
            }

            const btn = event?.currentTarget;
            if (btn) btn.disabled = true;

            axios.post(`/human-capital/recruitment/assessment/${this.viewAssessmentData.id}/result`, {
                score: score
            })
            .then(res => {
                const assessment = this.data['Assessment'].find(a => a.id === this.viewAssessmentData.id);
                if (assessment) {
                    assessment.score = res.data.assessment.score;
                    assessment.status = res.data.assessment.status;
                }
                alert(res.data.message);
                this.showAssessmentViewModal = false;
            })
            .catch(err => {
                console.error('Error submitting result:', err);
                alert('Failed to submit result.');
            })
            .finally(() => {
                if (btn) btn.disabled = false;
            });
        },

        deleteAssessment(id) {
            if (!confirm('Are you sure you want to delete this assessment?')) return;
            
            axios.delete(`/human-capital/recruitment/assessment/${id}`)
            .then(() => {
                this.data['Assessment'] = this.data['Assessment'].filter(a => a.id !== id);
            })
            .catch(err => console.error('Error deleting assessment:', err));
        },

        sendAssessmentTest(event) {
            if (!this.viewAssessmentData) return;
            if (!this.viewAssessmentData.email) {
                alert('Please enter a candidate email before sending the test.');
                return;
            }

            const btn = event?.currentTarget;
            const originalText = btn?.innerHTML || '';
            if (btn) {
                btn.innerHTML = '<svg class="w-4 h-4 animate-spin mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                btn.disabled = true;
            }

            axios.post(`/human-capital/recruitment/assessment/${this.viewAssessmentData.id}/send-test`, {
                test_type: this.viewAssessmentData.test_type || this.viewAssessmentData.test,
                email: this.viewAssessmentData.email
            })
            .then(res => {
                alert('Assessment test invitation has been sent to the candidate.');
                // Status will update to "In Progress" once the candidate clicks the link in their email.
                this.showAssessmentViewModal = false;
            })
            .catch(err => {
                console.error('Error sending test:', err);
                alert('Failed to send test: ' + (err.response?.data?.message || err.message));
            })
            .finally(() => {
                if (btn) {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            });
        },

        viewCAF(row) {
            this.viewCafData = row;
            this.showCafViewModal = true;
        },

        openAssessmentFromCaf(row) {
            this.showCafViewModal = false;
            
            // Notify candidate and update status via backend
            axios.post(`/human-capital/recruitment/caf/${row.id}/proceed`)
            .then(res => {
                const caf = this.data['CAF'].find(c => c.id === row.id);
                if (caf) caf.status = 'Assessment';

                if (res.data.assessment) {
                    // Add to assessment list if not already there
                    const exists = this.data['Assessment'].some(a => a.id === res.data.assessment.id);
                    if (!exists) {
                        this.data['Assessment'].unshift(res.data.assessment);
                    }
                }
                
                // Switch to Assessment tab to show the candidate in Kanban
                this.activeTab = 'Assessment';
            })
            .catch(err => {
                console.error('Error proceeding to assessment:', err);
                alert('Candidate status could not be updated.');
            });
        },

        viewInterview(row) {
            this.viewInterviewData = row;
            this.showInterviewViewModal = true;
        },

        deleteCAF(id) {
            if (!confirm('Are you sure you want to delete this application?')) return;
            
            axios.delete(`/human-capital/recruitment/caf/${id}`)
            .then(() => {
                this.data['CAF'] = this.data['CAF'].filter(c => c.id !== id);
            })
            .catch(err => console.error('Error deleting CAF:', err));
        },

        submitAssessment() {
            axios.post('{{ route("human-capital.recruitment.store_assessment") }}', this.assessmentForm)
            .then(res => {
                this.data['Assessment'].unshift(res.data.data);
                
                // Update local CAF status if it was from a CAF
                if (this.assessmentForm.caf_id) {
                    const caf = this.data['CAF'].find(c => c.id === this.assessmentForm.caf_id);
                    if (caf) caf.status = 'Assessment';
                }
                
                this.showAssessmentModal = false;
            })
            .catch(err => console.error('Error submitting assessment:', err));
        },

        scheduleInterviewFromAssessment(item) {
            if (!item || item.status !== 'Passed') {
                alert('Only Passed Assessment records can be scheduled for interview.');
                return;
            }

            this.interviewForm = {
                assessmentId: item.id || '',
                name: item.name || '',
                email: item.email || '',
                position: item.position || '',
                type: 'Online',
                interviewer: '', 
                interview_date: '',
                duration: '60',
                meeting_link: ''
            };
            this.generateMeetingLink();
            this.activeTab = 'Interview';
            this.showInterviewModal = true;
        },

        generateMeetingLink() {
            const seg = (len) => Array.from({length: len}, () => 'abcdefghijklmnopqrstuvwxyz'[Math.floor(Math.random() * 26)]).join('');
            this.interviewForm.meeting_link = `https://meet.google.com/${seg(3)}-${seg(4)}-${seg(3)}`;
        },

        interviewAllowsJobOffer(interview) {
            if (!interview) return false;

            const status = String(interview.status || '').toLowerCase();

            return ['completed', 'passed'].includes(status);
        },

        updateInterviewStatus(interview, status) {
            if (!interview || !interview.id) {
                alert('Interview record was not found.');
                return;
            }

            axios.post(`/human-capital/recruitment/interview/${interview.id}/status`, {
                status: status
            })
            .then(res => {
                const updated = res.data.data || res.data.interview || { ...interview, status: status };

                const idx = this.data['Interview'].findIndex(item => item.id === interview.id);
                if (idx !== -1) {
                    this.data['Interview'][idx] = {
                        ...this.data['Interview'][idx],
                        ...updated,
                        status: updated.status || status
                    };
                }

                this.viewInterviewData = {
                    ...this.viewInterviewData,
                    ...updated,
                    status: updated.status || status
                };

                alert(`Interview marked as ${status}. You can now proceed to Job Offer.`);
            })
            .catch(err => {
                alert('Error updating interview status: ' + (err.response?.data?.message || err.message));
            });
        },

        submitInterview() {
            if (!this.interviewForm.assessmentId || !this.interviewForm.name || !this.interviewForm.email || !this.interviewForm.position) {
                alert('Please select a Passed Assessment candidate before scheduling the interview.');
                return;
            }

            axios.post('{{ route("human-capital.recruitment.store_interview") }}', this.interviewForm)
            .then(res => {
                this.data['Interview'].unshift(res.data.data);
                this.showInterviewModal = false;

                if (res.data.warning) {
                    alert(res.data.warning);
                } else {
                    alert(res.data.message || 'Interview scheduled and email sent successfully.');
                }
            })
            .catch(err => {
                alert('Error scheduling interview: ' + (err.response?.data?.message || err.message));
            });
        },

        deleteInterview(id) {
            if (!confirm('Are you sure you want to delete this interview record?')) return;
            
            axios.delete(`/human-capital/recruitment/interview/${id}`)
            .then(() => {
                this.data['Interview'] = this.data['Interview'].filter(i => i.id !== id);
            })
            .catch(err => console.error('Error deleting interview:', err));
        },

        submitJobOffer() {
    if (!this.jobOfferForm.interviewId) {
        alert('Please select a Completed/Passed Interview before creating a Job Offer.');
        return;
    }

    if (!this.selectedJobOfferInterview || !this.interviewAllowsJobOffer(this.selectedJobOfferInterview)) {
        alert('Interview must be Completed or Passed before creating a Job Offer.');
        return;
    }

    if (!this.jobOfferForm.jobPostingId) {
        alert('Please select a JPF with status Posted/Open before creating a Job Offer.');
        return;
    }

    if (!this.selectedJobOfferJpf) {
        alert('Selected JPF was not found. Please select another JPF.');
        return;
    }

    const status = String(this.selectedJobOfferJpf.status || '').toLowerCase();

    if (!['posted', 'open'].includes(status)) {
        alert('Only JPF records with status Posted/Open can be used for Job Offer.');
        return;
    }

    axios.post('{{ route("human-capital.recruitment.store_job_offer") }}', this.jobOfferForm)
    .then(res => {
        this.data['Job Offer'].unshift(res.data.data);
        this.showJobOfferModal = false;

        if (res.data.warning) {
            alert(res.data.warning);
        } else {
            alert(res.data.message || 'Job Offer saved as Draft. Review it before sending.');
        }
    })
    .catch(err => {
        alert('Error saving Job Offer: ' + (err.response?.data?.message || err.message));
    });
},

        deleteJobOffer(id) {
            if (!confirm('Are you sure you want to delete this job offer?')) return;
            axios.delete(`/human-capital/recruitment/job-offer/${id}`)
            .then(() => {
                this.data['Job Offer'] = this.data['Job Offer'].filter(j => j.id !== id);
            })
            .catch(err => console.error('Error deleting job offer:', err));
        },

        openJobOfferModal(interviewData = null) {
            if (this.validJobOfferInterviews.length === 0) {
                alert('No Completed/Passed Interview is available yet. Mark an interview as Completed or Passed first.');
                return;
            }

            if (interviewData && !this.interviewAllowsJobOffer(interviewData)) {
                alert('Interview must be Completed or Passed before creating a Job Offer.');
                return;
            }

            if (this.validJobOfferJPFs.length === 0) {
                alert('No JPF with status Posted/Open is available. Please set a JPF status to Posted/Open first.');
                return;
            }

            const selectedInterview = interviewData || this.validJobOfferInterviews[0] || null;

            this.jobOfferForm = {
                interviewId: selectedInterview ? (selectedInterview.id || '') : '',
                jobPostingId: '',
                orgAddressId: '',
                orgBranchId: '',
                orgOfficeId: '',
                orgDepartmentId: '',
                orgDivisionId: '',
                orgUnitId: '',
                orgPositionId: '',
                salaryGradeId: '',
                name: selectedInterview ? (selectedInterview.name || '') : '',
                position: selectedInterview ? (selectedInterview.position || '') : '',
                salary: '',
                startDate: '',
                employmentType: 'Probationary',
                department: '',
                companyAddress: '',
                benefits: ''
            };

            this.showJobOfferModal = true;
        },

        viewJobOffer(offer) {
            this.viewJobOfferData = offer;
            this.showJobOfferViewModal = true;
        },

        resendJobOfferEmail(offer) {
            if (!offer || !offer.id) {
                alert('Job Offer record was not found.');
                return;
            }

            axios.post(`/human-capital/recruitment/job-offer/${offer.id}/resend-email`)
                .then(res => {
                    if (res.data.data) {
                        const idx = this.data['Job Offer'].findIndex(item => String(item.id) === String(res.data.data.id));
                        if (idx !== -1) {
                            this.data['Job Offer'][idx] = res.data.data;
                        }

                        this.viewJobOfferData = res.data.data;
                    }

                    alert(res.data.message || 'Job Offer email resent successfully.');
                })
                .catch(err => {
                    alert('Error resending Job Offer email: ' + (err.response?.data?.message || err.message));
                });
        },

        orgDisplay(collection, id, field, fallback = '—') {
            if (!id) return fallback;
            const list = this[collection] || [];
            const item = list.find(record => String(record.id) === String(id));
            return item && item[field] ? item[field] : fallback;
        },

        editMRF(row) {
            this.isEditing = true;
            this.editingId = row.id || row.request_id;
            const employmentType = this.splitOtherValue(row.employment_type, this.employmentTypeOptions);
            const immediateSupervisor = this.splitOtherValue(row.immediate_supervisor);
            const jobLevelRank = this.splitOtherValue(row.job_level_rank, this.jobLevelRankOptions);
            const workClassification = this.splitOtherValue(row.work_classification, this.workClassificationOptions);
            const workArrangement = this.splitOtherValue(row.work_arrangement, this.workArrangementOptions);
            const workSchedule = this.splitOtherValue(row.work_schedule, this.workScheduleOptions);
            const contractDuration = this.splitOtherValue(row.contract_duration, this.contractDurationOptions);
            const urgencyLevel = this.splitOtherValue(row.urgency_level, this.urgencyLevelOptions);
            const endorsements = row.endorsements || {};
            const immediateSupervisorEndorsement = this.splitOtherValue(endorsements.immediate_supervisor);
            const departmentHeadEndorsement = this.splitOtherValue(endorsements.department_head);
            const hcHeadValidation = this.splitOtherValue(endorsements.hc_head);
            const financeHeadClearance = this.splitOtherValue(endorsements.finance_head);
            this.form = {
                orgAddressId: row.address_id || '',
                orgBranchId: row.branch_id || '',
                orgOfficeId: row.office_id || '',
                orgDepartmentId: row.department_id || '',
                orgDivisionId: row.division_id || '',
                orgUnitId: row.unit_id || '',
                orgPositionId: row.position_id || '',

                requestId: row.request_id || '',
                department: row.department,
                dateRequested: row.date_requested,
                dateRequired: row.date_required,
                position: row.position,
                employmentType: employmentType.value,
                employmentTypeOther: employmentType.other,
                immediateSupervisor: immediateSupervisor.value,
                immediateSupervisorOther: immediateSupervisor.other,
                targetStartDate: row.target_start_date || '',
                jobLevelRank: jobLevelRank.value,
                jobLevelRankOther: jobLevelRank.other,
                workClassification: workClassification.value,
                workClassificationOther: workClassification.other,
                workArrangement: workArrangement.value,
                workArrangementOther: workArrangement.other,
                workSchedule: workSchedule.value,
                workScheduleOther: workSchedule.other,
                duties: row.duties,
                natureOfRequest: row.nature_of_request,
                ageRange: row.age_range,
                civilStatus: row.civil_status,
                gender: row.gender,
                headcount: row.headcount,
                education: row.education,
                qualifications: row.qualifications,
                requiredSkills: row.required_skills || '',
                benefitsChecklist: row.benefits_checklist || [],
                benefitsChecklistOther: '',
                requiredLicenses: row.required_licenses || '',
                requiredDocuments: row.required_documents || [],
                requiredDocumentsOther: '',
                salaryMin: row.salary_min || '',
                salaryMax: row.salary_max || '',
                contractDuration: contractDuration.value,
                contractDurationOther: contractDuration.other,
                urgencyLevel: urgencyLevel.value,
                urgencyLevelOther: urgencyLevel.other,
                candidateProfileAttached: Boolean(row.candidate_profile_attached),
                jobDescriptionAttached: Boolean(row.job_description_attached),
                candidateProfileFile: null,
                jobDescriptionFile: null,
                immediateSupervisorEndorsement: immediateSupervisorEndorsement.value,
                departmentHeadEndorsement: departmentHeadEndorsement.value,
                hcHeadValidation: hcHeadValidation.value,
                financeHeadClearance: financeHeadClearance.value,
                immediateSupervisorEndorsementOther: immediateSupervisorEndorsement.other,
                departmentHeadEndorsementOther: departmentHeadEndorsement.other,
                hcHeadValidationOther: hcHeadValidation.other,
                financeHeadClearanceOther: financeHeadClearance.other,
                requestedBy: row.requested_by,
                approvedBy: row.approved_by,
                remarks: row.remarks,
                requestStatus: row.request_status,
                chargedTo: row.charged_to,
                breakdownDetails: row.breakdown_details,
                hiredPersonnel: row.hired_personnel,
                dateHired: row.date_hired,
                processedBy: row.processed_by,
                checkedBy: row.checked_by,
            };
            this.showModal = true;
        },

        editJPF(row) {
            this.isEditing = true;
            this.editingId = row.id || row.job_id;
            this.jpfForm = {
                jobId: row.job_id,
                mrfId: row.mrf_id || '',
                relatedMrfNo: row.related_mrf_no,
                dateOpened: row.date_opened,
                hiringStatus: row.hiring_status || 'Open',

                orgAddressId: row.address_id || '',
                orgBranchId: row.branch_id || '',
                orgOfficeId: row.office_id || '',
                orgDepartmentId: row.department_id || '',
                orgDivisionId: row.division_id || '',
                orgUnitId: row.unit_id || '',
                orgPositionId: row.position_id || '',
                companyName: row.company_name,
                officeBranchSite: row.office_branch_site,
                departmentUnit: row.department_unit,
                hiringManager: row.hiring_manager,
                departmentSuperior: row.department_superior,

                position: row.position,
                noOfVacancies: row.no_of_vacancies,
                positionLevel: row.position_level,
                employmentType: row.employment_type,
                reportsTo: row.reports_to,
                workLocation: row.location,

                salaryGradeId: row.salary_grade_id || '',
                payrollLevelId: '',
                minSalary: row.min_salary_offer,
                maxSalary: row.max_salary_offer,
                salaryGrade: row.salary_grade,

                applicableRegion: row.applicable_region || 'Central Visayas',
                applicableArea: row.applicable_area,
                dailyMinWage: row.current_daily_min_wage,
                monthlyEquivalent: row.monthly_equivalent,
                wageCompliance: row.wage_compliance || [],

                benefits: row.benefits_package || ['SSS', 'PhilHealth', 'Pag-IBIG'],

                workSchedule: row.work_schedule || [],
                restDays: row.rest_days,

                education: row.education_req || row.requirements,
                experience: row.experience_req,
                skills: row.skills_req,
                licenses: row.licenses_req,
                preferredQualifications: row.preferred_qualifications,

                duties: row.duties_responsibilities || row.job_description,

                channels: row.recruitment_channels || [],
                screeningFlow: row.screening_flow || [],

                dateNeeded: row.date_needed,
                postingStartDate: row.posting_start_date,
                targetHireDate: row.target_hire_date,

                humanCapitalApproval: row.human_capital_approval || this.approvalTemplate('Human Capital'),
                hiringManagerApproval: row.hiring_manager_approval || this.approvalTemplate('Hiring Manager'),
                financeApproval: row.finance_approval || this.approvalTemplate('Finance'),
                presidentApproval: row.president_approval || this.approvalTemplate('President / Final'),

                status: row.status
            };
            this.showJpfModal = true;
        },

        editCAF(row) {
            this.isEditing = true;
            this.editingId = row.id;
            this.cafForm = {
                jobPostingId: row.job_posting_id || '',
                applicantType: row.applicant_type || 'New Applicant',
                internalRemarks: row.internal_remarks || '',
                fullName: row.name,
                positionApplied: row.position,
                email: row.email,
                phone: row.phone,
                photo: null,
                photo_path: row.photo_path,
                cv: null,
                coverLetterFile: null,
                coverLetter: row.cover_letter,
                status: row.status
            };
            this.showCafModal = true;
        },

        openModal() {
            this.isEditing = false;
            this.editingId = null;
            if (this.activeTab === 'MRF') {
                // Reset form
                this.form = {
                    orgAddressId: '',
                    orgBranchId: '',
                    orgOfficeId: '',
                    orgDepartmentId: '',
                    orgDivisionId: '',
                    orgUnitId: '',
                    orgPositionId: '',

                    requestId: '',
                    department: '', dateRequested: '', dateRequired: '',
                    position: '', employmentType: '', employmentTypeOther: '',
                    immediateSupervisor: '', targetStartDate: '',
                    immediateSupervisorOther: '',
                    jobLevelRank: '', jobLevelRankOther: '',
                    workClassification: '', workClassificationOther: '',
                    workArrangement: '', workArrangementOther: '',
                    workSchedule: '', workScheduleOther: '',
                    duties: '', natureOfRequest: '', ageRange: '',
                    civilStatus: 'No Preference', gender: 'No Preference',
                    headcount: '', education: '', qualifications: '',
                    requiredSkills: '', benefitsChecklist: [], benefitsChecklistOther: '',
                    requiredLicenses: '', requiredDocuments: [], requiredDocumentsOther: '',
                    salaryMin: '', salaryMax: '',
                    contractDuration: '', contractDurationOther: '',
                    urgencyLevel: '', urgencyLevelOther: '',
                    candidateProfileAttached: false, jobDescriptionAttached: false,
                    candidateProfileFile: null, jobDescriptionFile: null,
                    immediateSupervisorEndorsement: '', departmentHeadEndorsement: '',
                    hcHeadValidation: '', financeHeadClearance: '',
                    immediateSupervisorEndorsementOther: '', departmentHeadEndorsementOther: '',
                    hcHeadValidationOther: '', financeHeadClearanceOther: '',
                    requestedBy: '', approvedBy: '',
                    remarks: '', requestStatus: '',
                    chargedTo: '', breakdownDetails: '',
                    hiredPersonnel: '', dateHired: '',
                    processedBy: '', checkedBy: '',
                };
                this.showModal = true;
            } else if (this.activeTab === 'JPF') {
                if (this.approvedMRFs.length === 0) {
                    alert('No approved MRF available. Please approve an MRF first before creating a JPF.');
                    return;
                }

                this.jpfForm = {
                    jobId: '', mrfId: '', relatedMrfNo: '', dateOpened: '', hiringStatus: 'Open',
                    orgAddressId: '', orgBranchId: '', orgOfficeId: '', orgDepartmentId: '', orgDivisionId: '', orgUnitId: '', orgPositionId: '',
                    companyName: '', officeBranchSite: '', departmentUnit: '', hiringManager: '', departmentSuperior: '',
                    position: '', noOfVacancies: '', positionLevel: '', employmentType: '', reportsTo: '', workLocation: '',
                    salaryGradeId: '', minSalary: '', maxSalary: '', salaryGrade: '',
                    applicableRegion: 'Central Visayas', applicableArea: '', dailyMinWage: '', monthlyEquivalent: '',
                    wageCompliance: [],
                    benefits: ['SSS', 'PhilHealth', 'Pag-IBIG'],
                    workSchedule: [],
                    restDays: '',
                    education: '', experience: '', skills: '', licenses: '', preferredQualifications: '',
                    duties: '',
                    channels: [],
                    screeningFlow: [],
                    dateNeeded: '', postingStartDate: '', targetHireDate: '',
                    humanCapitalApproval: this.approvalTemplate('Human Capital'),
                    hiringManagerApproval: this.approvalTemplate('Hiring Manager'),
                    financeApproval: this.approvalTemplate('Finance'),
                    presidentApproval: this.approvalTemplate('President / Final'),
                    status: 'Draft'
                };
                this.showJpfModal = true;
            } else if (this.activeTab === 'CAF') {
                if (this.postedJPFs.length === 0) {
                    alert('No fully approved Posted/Screening JPF available. Complete JPF approvals and post the job first before adding applicants.');
                    return;
                }

                this.cafForm = {
                    jobPostingId: '',
                    applicantType: 'New Applicant',
                    internalRemarks: '',
                    fullName: '', positionApplied: '', email: '', phone: '', 
                    photo: null, photo_path: null, cv: null, coverLetterFile: null, coverLetter: ''
                };
                this.showCafModal = true;
            } else if (this.activeTab === 'Interview') {
                if (this.passedAssessmentCandidates.length === 0) {
                    alert('No Passed Assessment available. An applicant must pass the assessment before scheduling an interview.');
                    return;
                }

                this.interviewForm = {
                    assessmentId: '',
                    name: '', email: '', position: '', type: 'Online', interviewer: '', 
                    interview_date: '', duration: '60', meeting_link: ''
                };
                this.generateMeetingLink();
                this.showInterviewModal = true;
            } else if (this.activeTab === 'Assessment') {
                this.assessmentForm = {
                    name: '', position: '', test: 'Technical Test', date: '', notes: '', caf_id: null
                };
                this.showAssessmentModal = true;
            } else if (this.activeTab === 'Job Offer') {
                this.openJobOfferModal();
            } else {
                alert('Add New functionality for ' + this.activeTab + ' is not yet implemented.');
            }
        },

        normalizeBulletText(value) {
            if (!value) return '';
            return String(value)
                .split(/\r?\n|•/g)
                .map(item => item.replace(/^[-*]\s*/, '').trim())
                .filter(Boolean)
                .map(item => `• ${item}`)
                .join('\n');
        },

        valueWithOther(value, otherValue) {
            if (value !== 'Others') return value || '';
            const custom = String(otherValue || '').trim();
            return custom ? `Others: ${custom}` : 'Others';
        },

        splitOtherValue(value, options = []) {
            const text = String(value || '');
            if (text.startsWith('Others:')) {
                return { value: 'Others', other: text.replace(/^Others:\s*/, '') };
            }

            if (text && options.length && !options.includes(text)) {
                return { value: 'Others', other: text };
            }

            return { value: text, other: '' };
        },

        selectionWithOther(values, otherValue) {
            const selected = Array.isArray(values) ? values.filter(Boolean) : [];
            const withoutOther = selected.filter(item => item !== 'Others');
            if (selected.includes('Others')) {
                const custom = String(otherValue || '').trim();
                withoutOther.push(custom ? `Others: ${custom}` : 'Others');
            }
            return withoutOther;
        },

        buildMRFPayload() {
            return {
                ...this.form,
                requestId: this.form.requestId || '',
                employmentType: this.valueWithOther(this.form.employmentType, this.form.employmentTypeOther),
                immediateSupervisor: this.valueWithOther(this.form.immediateSupervisor, this.form.immediateSupervisorOther),
                jobLevelRank: this.valueWithOther(this.form.jobLevelRank, this.form.jobLevelRankOther),
                workClassification: this.valueWithOther(this.form.workClassification, this.form.workClassificationOther),
                workArrangement: this.valueWithOther(this.form.workArrangement, this.form.workArrangementOther),
                workSchedule: this.form.workSchedule === 'Custom Schedule'
                    ? (String(this.form.workScheduleOther || '').trim() ? `Custom Schedule: ${String(this.form.workScheduleOther).trim()}` : 'Custom Schedule')
                    : this.valueWithOther(this.form.workSchedule, this.form.workScheduleOther),
                requiredSkills: this.normalizeBulletText(this.form.requiredSkills),
                requiredLicenses: this.normalizeBulletText(this.form.requiredLicenses),
                benefitsChecklist: this.selectionWithOther(this.form.benefitsChecklist, this.form.benefitsChecklistOther),
                requiredDocuments: this.selectionWithOther(this.form.requiredDocuments, this.form.requiredDocumentsOther),
                contractDuration: this.valueWithOther(this.form.contractDuration, this.form.contractDurationOther),
                urgencyLevel: this.valueWithOther(this.form.urgencyLevel, this.form.urgencyLevelOther),
                immediateSupervisorEndorsement: this.valueWithOther(this.form.immediateSupervisorEndorsement, this.form.immediateSupervisorEndorsementOther),
                departmentHeadEndorsement: this.valueWithOther(this.form.departmentHeadEndorsement, this.form.departmentHeadEndorsementOther),
                hcHeadValidation: this.valueWithOther(this.form.hcHeadValidation, this.form.hcHeadValidationOther),
                financeHeadClearance: this.valueWithOther(this.form.financeHeadClearance, this.form.financeHeadClearanceOther),
                request_status: this.form.requestStatus || 'Pending'
            };
        },

        buildMRFRequestData(payload) {
            if (!this.form.candidateProfileFile && !this.form.jobDescriptionFile) {
                return payload;
            }

            const formData = new FormData();
            if (this.isEditing) {
                formData.append('_method', 'PUT');
            }
            Object.entries(payload).forEach(([key, value]) => {
                if (key === 'candidateProfileFile' || key === 'jobDescriptionFile') return;
                if (Array.isArray(value) || (value && typeof value === 'object')) {
                    formData.append(key, JSON.stringify(value));
                } else {
                    formData.append(key, value ?? '');
                }
            });

            if (this.form.candidateProfileFile) {
                formData.append('candidateProfileFile', this.form.candidateProfileFile);
            }

            if (this.form.jobDescriptionFile) {
                formData.append('jobDescriptionFile', this.form.jobDescriptionFile);
            }

            return formData;
        },

        submitMRF() {
            const url = this.isEditing ? `/human-capital/recruitment/mrf/${this.editingId}` : '{{ route("human-capital.recruitment.store_mrf") }}';
            const payload = this.buildMRFPayload();
            const data = this.buildMRFRequestData(payload);
            const method = this.isEditing && !(data instanceof FormData) ? 'put' : 'post';

            axios({
                method: method,
                url: url,
                data: data,
                headers: data instanceof FormData ? { 'Content-Type': 'multipart/form-data' } : {}
            })
            .then(res => {
                const item = res.data.data;
                if (this.isEditing) {
                    const idx = this.data['MRF'].findIndex(m => m.id === this.editingId);
                    if (idx !== -1) {
                        this.data['MRF'][idx] = item;
                    }
                } else {
                    this.data['MRF'].unshift(item);
                }
                this.showModal = false;
            })
            .catch(err => {
                alert('Error saving MRF: ' + (err.response?.data?.message || err.message));
            });
        },

        submitJPF() {
            if (!this.jpfForm.mrfId) {
                alert('Please select an Approved MRF before creating a JPF.');
                return;
            }

            if (!this.selectedJpfMrf || String(this.selectedJpfMrf.request_status || '').toLowerCase() !== 'approved') {
                alert('The selected MRF is not approved. Only Approved MRF records can be used for JPF.');
                return;
            }

            this.jpfForm.employmentType = this.selectedJpfMrf.employment_type || this.jpfForm.employmentType;

            if (!this.jpfStatusOptionAllowed(this.jpfForm.status)) {
                alert('This status is not allowed yet. Complete all approvals first before posting or moving this JPF to hiring progress statuses.');
                this.jpfForm.status = 'For Approval';
            }

            const url = this.isEditing ? `/human-capital/recruitment/jpf/${this.editingId}` : '{{ route("human-capital.recruitment.store_jpf") }}';
            const method = this.isEditing ? 'put' : 'post';

            axios({
                method: method,
                url: url,
                data: {
                    ...this.jpfForm,
                    posted_date: new Date().toISOString().split('T')[0]
                }
            })
            .then(res => {
                const item = res.data.data;
                if (this.isEditing) {
                    const idx = this.data['JPF'].findIndex(j => (j.id || j.job_id) === this.editingId);
                    if (idx !== -1) {
                        this.data['JPF'][idx] = {
                            ...item,
                            jobId: item.job_id,
                            type: item.employment_type,
                            posted: item.posted_date
                        };
                    }
                } else {
                    this.data['JPF'].unshift({
                        ...item,
                        jobId: item.job_id,
                        type: item.employment_type,
                        posted: item.posted_date
                    });
                }
                this.showJpfModal = false;
            })
            .catch(err => {
                alert('Error saving JPF: ' + (err.response?.data?.message || err.message));
            });
        },

        submitCAF() {
            let formData = new FormData();
            formData.append('jobPostingId', this.cafForm.jobPostingId);
            formData.append('applicantType', this.cafForm.applicantType || 'New Applicant');
            formData.append('internalRemarks', this.cafForm.internalRemarks || '');
            formData.append('fullName', this.cafForm.fullName);
            formData.append('positionApplied', this.cafForm.positionApplied);
            formData.append('email', this.cafForm.email);
            formData.append('phone', this.cafForm.phone);
            formData.append('coverLetter', this.cafForm.coverLetter);
            if (this.isEditing) {
                formData.append('status', this.cafForm.status);
                formData.append('_method', 'PUT');
            }
            if (this.cafForm.cv) {
                formData.append('cv', this.cafForm.cv);
            }
            if (this.cafForm.photo) {
                formData.append('photo', this.cafForm.photo);
            }
            if (this.cafForm.coverLetterFile) {
                formData.append('cover_letter_file', this.cafForm.coverLetterFile);
            }

            const url = this.isEditing ? `/human-capital/recruitment/caf/${this.editingId}` : '{{ route("human-capital.recruitment.store_caf") }}';

            axios.post(url, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
            .then(res => {
                const item = res.data.data;
                if (this.isEditing) {
                    const idx = this.data['CAF'].findIndex(c => c.id === this.editingId);
                    if (idx !== -1) {
                        this.data['CAF'][idx] = item;
                    }
                } else {
                    this.data['CAF'].unshift(item);
                }
                this.showCafModal = false;
            })
            .catch(err => {
                alert('Error submitting CAF: ' + (err.response?.data?.message || err.message));
            });
        },

        viewJPF(row) {
            this.viewJpfData = { ...row };
            this.showJpfViewModal = true;
        },

        downloadCSV() {
            const rows = this.filteredRows;

            if (rows.length === 0) {
                alert('No data available to download.');
                return;
            }

            const exportColumns = {
                'MRF': [
                    { label: 'Request ID', key: 'request_id' },
                    { label: 'Department', key: 'department' },
                    { label: 'Position', key: 'position' },
                    { label: 'Employment Type', key: 'employment_type' },
                    { label: 'Headcount', key: 'headcount' },
                    { label: 'Date Requested', key: 'date_requested' },
                    { label: 'Date Required', key: 'date_required' },
                    { label: 'Duties', key: 'duties' },
                    { label: 'Age Range', key: 'age_range' },
                    { label: 'Civil Status', key: 'civil_status' },
                    { label: 'Gender', key: 'gender' },
                    { label: 'Education', key: 'education' },
                    { label: 'Qualifications', key: 'qualifications' },
                    { label: 'Requested By', key: 'requested_by' },
                    { label: 'Approved By', key: 'approved_by' },
                    { label: 'Remarks', key: 'remarks' },
                    { label: 'Request Status', key: 'request_status' },
                    { label: 'Charged To', key: 'charged_to' },
                    { label: 'Breakdown Details', key: 'breakdown_details' },
                    { label: 'Hired Personnel', key: 'hired_personnel' },
                    { label: 'Date Hired', key: 'date_hired' },
                    { label: 'Processed By', key: 'processed_by' },
                    { label: 'Checked By', key: 'checked_by' },
                ],

                'JPF': [
                    { label: 'Job ID', key: 'job_id' },
                    { label: 'Related MRF No.', key: 'related_mrf_no' },
                    { label: 'Date Opened', key: 'date_opened' },
                    { label: 'Hiring Status', key: 'hiring_status' },
                    { label: 'Company Name', key: 'company_name' },
                    { label: 'Office / Branch / Site', key: 'office_branch_site' },
                    { label: 'Department / Unit', key: 'department_unit' },
                    { label: 'Position', key: 'position' },
                    { label: 'Employment Type', key: 'employment_type' },
                    { label: 'Location', key: 'location' },
                    { label: 'Hiring Manager', key: 'hiring_manager' },
                    { label: 'Department Superior', key: 'department_superior' },
                    { label: 'No. of Vacancies', key: 'no_of_vacancies' },
                    { label: 'Position Level', key: 'position_level' },
                    { label: 'Reports To', key: 'reports_to' },
                    { label: 'Min Salary Offer', key: 'min_salary_offer' },
                    { label: 'Max Salary Offer', key: 'max_salary_offer' },
                    { label: 'Salary Grade', key: 'salary_grade' },
                    { label: 'Monthly Equivalent', key: 'monthly_equivalent' },
                    { label: 'Applicable Region', key: 'applicable_region' },
                    { label: 'Applicable Area', key: 'applicable_area' },
                    { label: 'Daily Minimum Wage', key: 'current_daily_min_wage' },
                    { label: 'Benefits Package', key: 'benefits_package' },
                    { label: 'Work Schedule', key: 'work_schedule' },
                    { label: 'Rest Days', key: 'rest_days' },
                    { label: 'Education Requirement', key: 'education_req' },
                    { label: 'Experience Requirement', key: 'experience_req' },
                    { label: 'Skills Requirement', key: 'skills_req' },
                    { label: 'Licenses Requirement', key: 'licenses_req' },
                    { label: 'Preferred Qualifications', key: 'preferred_qualifications' },
                    { label: 'Duties / Responsibilities', key: 'duties_responsibilities' },
                    { label: 'Status', key: 'status' },
                    { label: 'Posted Date', key: 'posted_date' },
                ],

                'CAF': [
                    { label: 'Applied JPF ID', key: 'job_posting_id' },
                    { label: 'Name', key: 'name' },
                    { label: 'Position', key: 'position' },
                    { label: 'Email', key: 'email' },
                    { label: 'Phone', key: 'phone' },
                    { label: 'Status', key: 'status' },
                    { label: 'Applied Date', key: 'applied_date' },
                    { label: 'Cover Letter', key: 'cover_letter' },
                ],

                'Interview': [
                    { label: 'Name', key: 'name' },
                    { label: 'Position', key: 'position' },
                    { label: 'Interview Type', key: 'type' },
                    { label: 'Interviewer', key: 'interviewer' },
                    { label: 'Interview Date', key: 'interview_date' },
                    { label: 'Duration', key: 'duration' },
                    { label: 'Meeting Link', key: 'meeting_link' },
                    { label: 'Status', key: 'status' },
                ],

                'Job Offer': [
                    { label: 'Candidate Name', key: 'name' },
                    { label: 'Position', key: 'position' },
                    { label: 'Department', key: 'department' },
                    { label: 'Company Address', key: 'company_address' },
                    { label: 'Salary', key: 'salary' },
                    { label: 'Start Date', key: 'start_date' },
                    { label: 'Employment Type', key: 'employment_type' },
                    { label: 'Benefits', key: 'benefits' },
                    { label: 'Status', key: 'status' },
                ],
            };

            const hiddenColumns = [
                'id',
                'mrf_id',
                'job_posting_id',
                'address_id',
                'branch_id',
                'office_id',
                'department_id',
                'division_id',
                'unit_id',
                'position_id',
                'salary_grade_id',
                'created_at',
                'updated_at',
                'deleted_at',
                'req_id_display',
                'date_display',
            ];

            const columns = exportColumns[this.activeTab] || Object.keys(rows[0])
                .filter(key => {
                    return !hiddenColumns.includes(key)
                        && !key.endsWith('_id')
                        && typeof rows[0][key] !== 'object';
                })
                .map(key => ({
                    label: key.replaceAll('_', ' ').replace(/\b\w/g, char => char.toUpperCase()),
                    key: key,
                }));

            const formatValue = (value) => {
                if (value === null || value === undefined) {
                    return '';
                }

                if (Array.isArray(value)) {
                    return value.join('; ');
                }

                if (typeof value === 'object') {
                    return JSON.stringify(value);
                }

                if (typeof value === 'string' && value.match(/^\d{4}-\d{2}-\d{2}T/)) {
                    const date = new Date(value);
                    if (!Number.isNaN(date.getTime())) {
                        return date.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                        });
                    }
                }

                return String(value);
            };

            const escapeCsv = (value) => {
                let val = formatValue(value);

                if (val.includes(',') || val.includes('"') || val.includes('\n')) {
                    val = `"${val.replace(/"/g, '""')}"`;
                }

                return val;
            };

            const csvContent = [
                columns.map(column => escapeCsv(column.label)).join(','),
                ...rows.map(row => columns.map(column => escapeCsv(row[column.key])).join(','))
            ].join('\n');

            const blob = new Blob([csvContent], {
                type: 'text/csv;charset=utf-8;'
            });

            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');

            link.setAttribute('href', url);
            link.setAttribute(
                'download',
                `${this.activeTab.toLowerCase().replace(/\s+/g, '_')}_list_${new Date().toISOString().split('T')[0]}.csv`
            );

            link.style.visibility = 'hidden';

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            URL.revokeObjectURL(url);
        },

        prevPage() {
            if (this.currentPage > 1) this.currentPage--;
        },

        nextPage() {
            if (this.currentPage < this.totalPages) this.currentPage++;
        },

        get paginatedRows() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredRows.slice(start, start + parseInt(this.perPage));
        },

        get totalPages() {
            return Math.ceil(this.filteredRows.length / this.perPage) || 1;
        },

        get startRange() {
            if (this.filteredRows.length === 0) return 0;
            return (this.currentPage - 1) * this.perPage + 1;
        },

        get endRange() {
            return Math.min(this.currentPage * this.perPage, this.filteredRows.length);
        },

        get uniqueMrfPositions() {
            const positions = this.data['MRF'].map(mrf => mrf.position).filter(p => p && p.trim() !== '');
            return [...new Set(positions)];
        },

        get postedJPFs() {
            return this.data['JPF'].filter(jpf => this.jpfCanBePublic(jpf));
        },

        get selectedCafJpf() {
            return this.data['JPF'].find(jpf => String(jpf.id) === String(this.cafForm.jobPostingId)) || null;
        },

        onCafJpfChange() {
            const jpf = this.selectedCafJpf;

            if (!jpf) {
                this.cafForm.positionApplied = '';
                return;
            }

            const status = String(jpf.status || '').toLowerCase();

            if (!this.jpfCanBePublic(jpf)) {
                alert('Only fully approved Posted/Screening JPF records can be selected for applicant/CAF.');
                this.cafForm.jobPostingId = '';
                this.cafForm.positionApplied = '';
                return;
            }

            this.cafForm.positionApplied = jpf.position || '';
        },

        get uniqueJpfPositions() {
            const positions = this.data['JPF'].map(jpf => jpf.position).filter(p => p && p.trim() !== '');
            return [...new Set(positions)];
        },

        get uniqueCafPositions() {
            const positions = this.data['CAF'].map(caf => caf.position).filter(p => p && p.trim() !== '');
            return [...new Set(positions)];
        },

        get uniqueCafNames() {
            const names = this.data['CAF'].map(caf => caf.name).filter(n => n && n.trim() !== '');
            return [...new Set(names)];
        },

        get passedAssessmentCandidates() {
            return this.data['Assessment'].filter(item => String(item.status || '').toLowerCase() === 'passed');
        },

        onInterviewAssessmentChange() {
            const selected = this.passedAssessmentCandidates.find(item => String(item.id) === String(this.interviewForm.assessmentId));

            if (!selected) {
                this.interviewForm.name = '';
                this.interviewForm.email = '';
                this.interviewForm.position = '';
                return;
            }

            this.interviewForm.name = selected.name || '';
            this.interviewForm.email = selected.email || '';
            this.interviewForm.position = selected.position || '';
        },

        viewMRF(row) {
            this.viewData = row;
            this.showViewModal = true;
        },

        approveMRF(id) {
            axios.post(`/human-capital/recruitment/mrf/${id}/approve`)
            .then(res => {
                const item = res.data.data;
                const idx = this.data['MRF'].findIndex(m => m.id === id);
                if (idx !== -1) {
                    this.data['MRF'][idx].request_status = 'Approved';
                    // If viewData is currently showing this MRF, update it too
                    if (this.viewData && this.viewData.id === id) {
                        this.viewData.request_status = 'Approved';
                    }
                }
                this.showViewModal = false;
                // Optional: Show a success notification if implemented
            })
            .catch(err => {
                console.error('Error approving MRF:', err);
                alert('Failed to approve MRF. Please try again.');
            });
        },

        cancelMRF(id) {
            if (!confirm('Are you sure you want to cancel this manpower request?')) return;
            
            axios.post(`/human-capital/recruitment/mrf/${id}/cancel`)
            .then(res => {
                const idx = this.data['MRF'].findIndex(m => m.id === id);
                if (idx !== -1) {
                    this.data['MRF'][idx].request_status = 'Cancelled';
                    if (this.viewData && this.viewData.id === id) {
                        this.viewData.request_status = 'Cancelled';
                    }
                }
                this.showViewModal = false;
            })
            .catch(err => {
                console.error('Error cancelling MRF:', err);
                alert('Failed to cancel MRF. Please try again.');
            });
        },

        deleteMRF(id) {
            if (confirm('Delete this MRF record?')) {
                axios.delete('/human-capital/recruitment/mrf/' + id)
                .then(() => {
                    this.data['MRF'] = this.data['MRF'].filter(m => m.id !== id);
                })
                .catch(err => {
                    alert('Error deleting MRF: ' + (err.response?.data?.message || err.message));
                });
            }
        },

        deleteJPF(id) {
            if (confirm('Delete this Job Posting?')) {
                axios.delete('/human-capital/recruitment/jpf/' + id)
                .then(() => {
                    this.data['JPF'] = this.data['JPF'].filter(j => j.id !== id);
                })
                .catch(err => {
                    alert('Error deleting JPF: ' + (err.response?.data?.message || err.message));
                });
            }
        },

        downloadPDF(elementId) {
            const element = document.getElementById(elementId);
            if (!element) return;
            
            let downloadFilename = 'Manpower_Request_Form.pdf';
            if (elementId.includes('jpf')) {
                downloadFilename = 'Job Placement form.pdf';
            } else if (elementId.includes('caf')) {
                downloadFilename = 'Candidate Application Form.pdf';
            }
            
            const opt = {
                margin:       0.3,
                filename:     downloadFilename,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
            };
            
            html2pdf().set(opt).from(element).save();
        },


        formatDisplayDate(value) {
            if (!value) return '—';

            const date = new Date(value);

            if (Number.isNaN(date.getTime())) {
                return value;
            }

            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: '2-digit',
                year: 'numeric'
            });
        },

        async quickPostJPF(row) {
            if (!row || !row.id) return;

            if (this.jpfApprovalProgress(row) < 4) {
                alert('This JPF cannot be posted yet because approvals are not complete.');
                return;
            }

            const mrfId = row.mrf_id || row.mrfId || row.mrf?.id || '';
            const relatedMrfNo = row.related_mrf_no || row.relatedMrfNo || '';

            if (!mrfId && !relatedMrfNo) {
                alert('This JPF has no linked approved MRF. Please edit the JPF and select an approved MRF first.');
                return;
            }

            if (!confirm('Post this approved JPF to the public Careers page?')) {
                return;
            }

            const payload = {
                ...row,

                // Important:
                // updateJPF expects camelCase form keys, while the table row from the database uses snake_case.
                // These mappings prevent the controller from thinking the MRF is missing and also prevent fields from being wiped.
                mrfId: mrfId,
                relatedMrfNo: relatedMrfNo,

                orgAddressId: row.address_id || '',
                orgBranchId: row.branch_id || '',
                orgOfficeId: row.office_id || '',
                orgDepartmentId: row.department_id || '',
                orgDivisionId: row.division_id || '',
                orgUnitId: row.unit_id || '',
                orgPositionId: row.position_id || '',
                salaryGradeId: row.salary_grade_id || '',

                employmentType: row.employment_type || '',
                workLocation: row.location || '',
                minSalary: row.min_salary_offer || '',
                maxSalary: row.max_salary_offer || '',
                dateOpened: row.date_opened || '',
                hiringStatus: row.hiring_status || '',
                companyName: row.company_name || '',
                officeBranchSite: row.office_branch_site || '',
                departmentUnit: row.department_unit || '',
                hiringManager: row.hiring_manager || '',
                departmentSuperior: row.department_superior || '',
                noOfVacancies: row.no_of_vacancies || '',
                positionLevel: row.position_level || '',
                reportsTo: row.reports_to || '',
                salaryGrade: row.salary_grade || '',
                applicableRegion: row.applicable_region || '',
                applicableArea: row.applicable_area || '',
                dailyMinWage: row.current_daily_min_wage || '',
                monthlyEquivalent: row.monthly_equivalent || '',
                wageCompliance: row.wage_compliance || [],
                benefits: row.benefits_package || [],
                workSchedule: row.work_schedule || [],
                restDays: row.rest_days || '',
                education: row.education_req || row.requirements || '',
                experience: row.experience_req || '',
                skills: row.skills_req || '',
                licenses: row.licenses_req || '',
                preferredQualifications: row.preferred_qualifications || '',
                duties: row.duties_responsibilities || row.job_description || '',
                channels: row.recruitment_channels || [],
                screeningFlow: row.screening_flow || [],
                dateNeeded: row.date_needed || '',
                postingStartDate: row.posting_start_date || '',
                targetHireDate: row.target_hire_date || '',

                humanCapitalApproval: row.human_capital_approval || {},
                hiringManagerApproval: row.hiring_manager_approval || {},
                financeApproval: row.finance_approval || {},
                presidentApproval: row.president_approval || {},

                status: 'Posted',
                posted_date: row.posted_date || new Date().toISOString().slice(0, 10)
            };

            try {
                const res = await fetch(`/human-capital/recruitment/jpf/${row.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    alert(data.message || 'Failed to post JPF.');
                    return;
                }

                const index = this.data.JPF.findIndex(item => item.id === row.id);
                if (index !== -1) {
                    this.data.JPF[index] = data.data;
                }

                alert('JPF has been posted successfully.');
            } catch (error) {
                console.error(error);
                alert('Failed to post JPF.');
            }
        },

        statusClass(status) {
            const map = {
                'Approved':      'bg-green-100 text-green-700',
                'Completed':     'bg-green-100 text-green-700',
                'Passed':        'bg-green-100 text-green-700',
                'Accepted':      'bg-green-100 text-green-700',
                'Filled':        'bg-green-100 text-green-700',
                'Deployment':    'bg-green-100 text-green-700',

                'Open':          'bg-blue-100 text-blue-700',
                'Active':        'bg-blue-100 text-blue-700',
                'Posted':        'bg-blue-100 text-blue-700',
                'In Progress':   'bg-blue-100 text-blue-700',
                'Screening':     'bg-blue-100 text-blue-700',
                'Assessment':    'bg-purple-100 text-purple-700',

                'Interviewing':  'bg-purple-100 text-purple-700',
                'Offer Stage':   'bg-indigo-100 text-indigo-700',

                'Pending':       'bg-yellow-100 text-yellow-700',
                'For Review':    'bg-yellow-100 text-yellow-700',
                'Hold':          'bg-yellow-100 text-yellow-700',
                'Urgent':        'bg-orange-100 text-orange-700 border border-orange-200',

                'Draft':         'bg-gray-100 text-gray-500 border border-gray-200',
                'Confidential':  'bg-gray-800 text-white',
                'Closed':        'bg-gray-500 text-white',

                'Rejected':      'bg-red-100 text-red-700',
                'Failed':        'bg-red-100 text-red-700',
                'Declined':      'bg-red-100 text-red-700',
                'Cancelled':     'bg-red-100 text-red-700',
                'Disapproved':   'bg-red-100 text-red-700',
            };
            return map[status] ?? 'bg-gray-100 text-gray-600';
        },

        isDatePassed(dateStr) {
            if (!dateStr) return false;
            try {
                // Ensure date string is compatible (replace space with T for YYYY-MM-DD HH:MM:SS)
                const normalized = String(dateStr).trim().replace(' ', 'T');
                const interviewDate = new Date(normalized);
                
                if (isNaN(interviewDate.getTime())) {
                    console.warn('Invalid date format:', dateStr);
                    return false;
                }

                const now = new Date();
                const passed = interviewDate < now;
                
                // Debug log to help identify why it might not be changing
                console.log(`Checking Date: ${normalized} | Current: ${now.toISOString()} | Passed: ${passed}`);
                
                return passed;
            } catch (e) {
                console.error('Error in isDatePassed:', e);
                return false;
            }
        },

        get filteredRows() {
            let rows = this.data[this.activeTab] ?? [];

            if (this.activeTab === 'JPF' && this.jpfListView === 'mine') {
                rows = rows.filter(r => this.jpfNeedsMyApproval(r));
            }

            // Apply Status Filter
            if (this.filterStatus !== 'All') {
                rows = rows.filter(r => r.status === this.filterStatus);
            }

            if (this.activeTab === 'CAF' && this.filterPosition !== 'All') {
                rows = rows.filter(r => r.position === this.filterPosition);
            }

            // Apply Search Filter
            if (!this.search.trim()) return rows;
            const q = this.search.toLowerCase();
            return rows.filter(r =>
                Object.values(r).some(v => String(v).toLowerCase().includes(q))
            );
        },

        get uniqueCafPositions() {
            return [...new Set((this.data['CAF'] || []).map(row => row.position).filter(Boolean))].sort();
        },
    };
}
</script>
@endpush
@endsection

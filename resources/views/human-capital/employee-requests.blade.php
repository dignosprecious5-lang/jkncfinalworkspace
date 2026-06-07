@extends('layouts.app')

@section('content')
@php
    $companyHeader = $companyHeader ?? [
        'logo_url' => asset('images/jk-logo.png'),
        'company_name' => 'JOHN KELLY & COMPANY (JK&C INC)',
        'company_address' => '3F Cebu Holdings Center Cebu Business Park, Cebu City, Philippines, 6000',
    ];
@endphp
<div class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col" x-data="employeeRequestsPage()">
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">
        <div class="flex items-center justify-between px-5 py-4 border-b shrink-0 gap-4">
            <div>
                <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">Human Capital</p>
                <h1 class="text-lg font-semibold text-gray-900">Employee Requests</h1>
                <p class="text-xs text-gray-500">Submit and manage employee requests: overtime, leave, attendance corrections, and more.</p>
            </div>

            <button
                type="button"
                @click="openAdd()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-semibold"
            >
                + Add New Request
            </button>
        </div>

        <!-- TABS -->
        <div class="px-4 py-3 border-b bg-white">
            <div class="flex items-center gap-4">
                <button
                    type="button"
                    @click="activeTab = 'my-requests'"
                    :class="activeTab === 'my-requests' ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'border-b-2 border-transparent text-gray-600 hover:text-blue-600'"
                    class="py-2 transition text-sm"
                >
                    My Requests
                </button>

                <button
                    type="button"
                    @click="activeTab = 'list'"
                    x-show="canManageRequests"
                    :class="activeTab === 'list' ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'border-b-2 border-transparent text-gray-600 hover:text-blue-600'"
                    class="py-2 transition text-sm"
                >
                    List of Requests
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="mx-5 mt-4 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <!-- CONTENT AREA -->
        <div class="p-5 flex-grow overflow-hidden">
            <!-- MY REQUESTS TAB -->
            <div x-show="activeTab === 'my-requests'" class="h-full flex flex-col">
                <div class="border rounded-xl h-full overflow-auto bg-white">
                    <table class="w-full text-sm border-collapse">
                        <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                            <tr>
                                <th class="p-3 text-left">Request No.</th>
                                <th class="p-3 text-left">Request Type</th>
                                <th class="p-3 text-left">Date Filed</th>
                                <th class="p-3 text-left">Status</th>
                                <th class="p-3 text-left">Admin Note</th>
                                <th class="p-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="myRequests.length === 0">
                                <tr>
                                    <td colspan="6" class="p-10 text-center text-gray-400">
                                        No requests submitted yet. Click + Add New Request to create one.
                                    </td>
                                </tr>
                            </template>

                            <template x-for="request in myRequests" :key="request.id">
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="p-3 font-semibold text-blue-700" x-text="`REQ-${String(request.id).padStart(4, '0')}`"></td>
                                    <td class="p-3 text-gray-700" x-text="request.request_type"></td>
                                    <td class="p-3 text-gray-700" x-text="request.created_at ? request.created_at.split(' ')[0] : '-'"></td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold" :class="statusClass(request.status)" x-text="request.status || 'Pending'"></span>
                                    </td>
                                    <td class="p-3 text-sm text-gray-600" x-text="request.admin_note || 'N/A'"></td>
                                    <td class="p-3 text-right whitespace-nowrap">
                                        <button type="button" @click="openView(request)" class="text-indigo-600 hover:underline text-xs font-semibold mr-3">View</button>
                                        <button type="button" @click="openEdit(request)" x-show="request.status === 'For Revision'" class="text-blue-600 hover:underline text-xs font-semibold">Revise</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- LIST OF REQUESTS TAB (Admin Only) -->
            <div x-show="activeTab === 'list'" class="h-full flex flex-col">
                <div class="mb-3 flex items-center justify-end gap-2">
                    <label for="employee-request-filter" class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Employee</label>
                    <select
                        id="employee-request-filter"
                        x-model="selectedEmployee"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white min-w-64"
                    >
                        <option value="">All employees</option>
                        <template x-for="employee in employeeFilterOptions" :key="employee">
                            <option :value="employee" x-text="employee"></option>
                        </template>
                    </select>
                </div>

                <div class="border rounded-xl h-full overflow-auto bg-white">
                    <table class="w-full text-sm border-collapse">
                        <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                            <tr>
                                <th class="p-3 text-left">Request No.</th>
                                <th class="p-3 text-left">Employee</th>
                                <th class="p-3 text-left">Request Type</th>
                                <th class="p-3 text-left">Date Filed</th>
                                <th class="p-3 text-left">Status</th>
                                <th class="p-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="filteredRequests.length === 0">
                                <tr>
                                    <td colspan="6" class="p-10 text-center text-gray-400">
                                        No employee requests submitted yet.
                                    </td>
                                </tr>
                            </template>

                            <template x-for="request in filteredRequests" :key="request.id">
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="p-3 font-semibold text-blue-700" x-text="`REQ-${String(request.id).padStart(4, '0')}`"></td>
                                    <td class="p-3 text-gray-700" x-text="request.employee_name"></td>
                                    <td class="p-3 text-gray-700" x-text="request.request_type"></td>
                                    <td class="p-3 text-gray-700" x-text="request.created_at ? request.created_at.split(' ')[0] : '-'"></td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold" :class="statusClass(request.status)" x-text="request.status || 'Pending'"></span>
                                    </td>
                                    <td class="p-3 text-right whitespace-nowrap space-x-2">
                                        <button type="button" @click="openView(request)" class="text-indigo-600 hover:underline text-xs font-semibold">View</button>
                                        <button type="button" @click="openAdminEdit(request)" class="text-blue-600 hover:underline text-xs font-semibold">Edit</button>
                                        <button type="button" @click="openApproveForm(request)" x-show="request.status === 'Pending'" class="text-green-600 hover:underline text-xs font-semibold">Approve</button>
                                        <button type="button" @click="openRejectForm(request)" x-show="request.status !== 'Approved'" class="text-red-600 hover:underline text-xs font-semibold">Reject</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- SLIDER PANEL -->
    <div x-show="showPanel" x-transition.opacity class="fixed inset-0 z-50 bg-black/40 flex justify-end" style="display:none;" @click.self="closePanel()">
        <div class="w-[90vw] max-w-[90vw] h-full bg-white shadow-xl flex flex-col">
            <div class="px-6 py-4 border-b bg-white flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold uppercase tracking-widest text-gray-900" x-text="panelTitle"></h2>
                    <p class="text-xs text-gray-500" x-text="form.id ? `REQ-${String(form.id).padStart(4, '0')}` : 'New Request'"></p>
                </div>
                <button type="button" @click="closePanel()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>

            <div class="flex-1 min-h-0 grid grid-cols-[58%_42%] bg-gray-50">
                <!-- LEFT SIDE: PDF PREVIEW -->
                <div class="min-h-0 border-r bg-gray-100 flex flex-col">
                    <!-- STICKY / FIXED TOOLBAR -->
                    <div class="shrink-0 px-5 py-3 border-b bg-gray-100 flex items-center justify-between z-30 gap-3">
                        <div class="flex items-center gap-3">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Preview</p>
                            <div class="inline-flex rounded-lg border border-gray-300 bg-white p-1">
                                <button type="button" @click="previewMode = 'form'" :class="previewMode === 'form' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'" class="px-3 py-1.5 rounded-md text-xs font-semibold">Request Form</button>
                                <button type="button" @click="previewMode = 'attachment'" :disabled="!attachmentPreviewUrl" :class="previewMode === 'attachment' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50 disabled:text-gray-300 disabled:hover:bg-white'" class="px-3 py-1.5 rounded-md text-xs font-semibold">Attached Document</button>
                                <button type="button" @click="previewMode = 'coe'" :disabled="!isCoeRequest" :class="previewMode === 'coe' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50 disabled:text-gray-300 disabled:hover:bg-white'" class="px-3 py-1.5 rounded-md text-xs font-semibold">COE Preview</button>
                            </div>
                        </div>

                        <template x-if="isApprovedCoe && form.coe_download_url">
                            <a :href="form.coe_download_url" class="px-3 py-2 border rounded-lg text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 shadow-sm">Download PDF</a>
                        </template>
                        <template x-if="!isApprovedCoe || !form.coe_download_url">
                            <button
                                type="button"
                                onclick="window.print()"
                                class="px-3 py-2 border rounded-lg text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 shadow-sm"
                            >
                                Download PDF
                            </button>
                        </template>
                    </div>

    <!-- SCROLLABLE PDF AREA ONLY -->
    <div class="flex-1 min-h-0 overflow-auto p-5">
                        <div x-show="previewMode === 'form'" class="bg-white mx-auto border border-gray-300 shadow-lg px-10 py-8 text-[11px] leading-tight w-[820px] min-h-[1123px] print-area">
                        <div class="text-center border-b-2 border-blue-700 pb-4 mb-4">
                            <img src="{{ $companyHeader['logo_url'] }}" onerror="this.style.display='none';" class="h-24 mx-auto mb-2 object-contain" alt="Company Logo">
                            <p class="mt-2 text-[12px] font-bold uppercase">{{ $companyHeader['company_name'] }}</p>
                            <p class="mt-1 text-[11px] font-semibold">{{ $companyHeader['company_address'] }}</p>
                            <p class="mt-1">Form Code: ERF-F002 | Version: 1.0 | Effective Date: {{ now()->format('F j, Y') }} | Issued by: Human Capital</p>
                        </div>

                        <div class="bg-blue-700 text-white px-3 py-2 font-bold uppercase tracking-widest text-sm mb-3 rounded-sm" x-text="form.request_type || 'Employee Request'"></div>

                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <div><span class="font-bold">Request No:</span> <span x-text="form.id ? `REQ-${String(form.id).padStart(4, '0')}` : 'Auto-generated'"></span></div>
                            <div><span class="font-bold">Status:</span> <span x-text="form.status || 'Pending'"></span></div>
                            <div><span class="font-bold">Filed Date:</span> <span x-text="form.created_at ? form.created_at.split(' ')[0] : '{{ now()->format('Y-m-d') }}'"></span></div>
                            <div><span class="font-bold">Request Type:</span> <span x-text="form.request_type || '-'"></span></div>
                        </div>

                        <div class="section-title">A. Employee Information</div>
                        <table class="preview-table">
                            <tr>
                                <td><b>Employee Name:</b> <span x-text="form.employee_name || '-'"></span></td>
                                <td><b>Department:</b> <span x-text="form.department || '-'"></span></td>
                            </tr>
                        </table>

                        <div class="section-title">B. Request Details</div>
                        <table class="preview-table">
                            <template x-if="form.request_type === 'Overtime Request'">
                                <tr><td colspan="2"><b>Date:</b> <span x-text="form.overtime_date || '-'"></span> | <b>Hours:</b> <span x-text="form.total_hours || '-'"></span></td></tr>
                            </template>
                            <template x-if="form.request_type === 'Leave Application Request'">
                                <tr><td><b>Leave Type:</b> <span x-text="form.leave_type || '-'"></span></td><td><b>Days:</b> <span x-text="form.number_of_days || '-'"></span></td></tr>
                            </template>
                            <template x-if="form.request_type === 'Attendance Correction Request'">
                                <tr><td><b>Attendance Date:</b> <span x-text="form.attendance_date || '-'"></span></td><td><b>Type:</b> <span x-text="form.correction_type || '-'"></span></td></tr>
                            </template>
                            <template x-if="form.request_type === 'Undertime / Absence Request'">
                                <tr><td><b>Request Date:</b> <span x-text="form.request_date || '-'"></span></td><td><b>Type:</b> <span x-text="form.absence_type || '-'"></span></td></tr>
                            </template>
                            <template x-if="form.request_type === 'COE Request Form'">
                                <tr><td><b>Purpose:</b> <span x-text="form.purpose || '-'"></span></td><td><b>Needed:</b> <span x-text="form.date_needed || '-'"></span></td></tr>
                            </template>
                            <tr><td colspan="2"><b>Reason/Details:</b><br><span x-text="form.reason || form.remarks || '-'"></span></td></tr>
                        </table>

                        <div class="section-title">C. Admin Review</div>
                        <table class="preview-table">
                            <tr><td><b>Admin Note:</b><br><span x-text="form.admin_note || '-'"></span></td></tr>
                        </table>

                        <div class="grid grid-cols-2 gap-10 mt-12 text-center">
                            <div><div class="border-b border-gray-700 h-8"></div><p class="mt-1 font-bold">Employee Signature</p></div>
                            <div><div class="border-b border-gray-700 h-8"></div><p class="mt-1 font-bold">HR / Authorized Reviewer</p></div>
                        </div>
                    </div>
        <div x-show="previewMode === 'attachment'" class="bg-white mx-auto border border-gray-300 shadow-lg w-[820px] min-h-[1123px] overflow-hidden">
            <template x-if="attachmentPreviewUrl">
                <div class="h-[1123px] flex flex-col">
                    <div class="px-4 py-3 border-b bg-gray-50 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Attached Document</p>
                            <p class="text-sm font-semibold text-gray-800 truncate" x-text="attachmentPreviewName || 'Attachment'"></p>
                        </div>
                        <a :href="attachmentPreviewUrl" target="_blank" class="shrink-0 px-3 py-2 text-xs font-semibold rounded-lg border border-gray-300 text-gray-700 hover:bg-white">Open</a>
                    </div>
                    <iframe :src="attachmentPreviewUrl" class="flex-1 w-full border-0 bg-white"></iframe>
                </div>
            </template>
            <template x-if="!attachmentPreviewUrl">
                <div class="h-[1123px] flex items-center justify-center px-8 text-center">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">No attached document</p>
                        <p class="mt-1 text-xs text-gray-500">Upload a document on the form to preview it here.</p>
                    </div>
                </div>
            </template>
        </div>
        <div x-show="previewMode === 'coe'" class="bg-white mx-auto border border-gray-300 shadow-lg px-12 py-10 text-[13px] leading-relaxed w-[820px] min-h-[1123px] print-area font-serif text-gray-900">
            <template x-if="isCoeRequest">
                <div>
                    <div class="text-center border-b-2 border-blue-700 pb-5 mb-8">
                        <img :src="coe.logo_url" onerror="this.style.display='none';" class="h-24 mx-auto mb-2 object-contain" alt="Company Logo">
                        <p class="mt-2 text-[14px] font-bold uppercase" x-text="coe.company_name"></p>
                        <p class="mt-1 text-[12px] font-semibold" x-text="coe.company_address"></p>
                    </div>

                    <h3 class="text-center text-[20px] font-bold uppercase tracking-widest mb-8">Certificate of Employment</h3>

                    <p class="mb-5 text-justify">
                        This is to certify that <strong x-text="coe.employee_name"></strong>
                        is/was employed with <strong x-text="coe.company_name"></strong>
                        as <strong x-text="coe.position"></strong>
                        under <strong x-text="coe.department"></strong>
                        from <strong x-text="coe.start_date"></strong>
                        to <strong x-text="coe.end_date"></strong>.
                    </p>

                    <p class="mb-5 text-justify">
                        Based on company records, the employee receives/received a monthly basic salary of
                        <strong x-text="coe.monthly_basic_salary"></strong>, exclusive of incentives, allowances, benefits, and other
                        compensation that may be reflected in the employee's payslip, and subject to applicable deductions, taxes,
                        and company policies.
                    </p>

                    <p class="mb-5 text-justify">
                        This certification is issued upon the request of the employee for
                        <strong x-text="coe.purpose"></strong>.
                    </p>

                    <p class="mb-6 text-justify">
                        Issued on <strong x-text="coe.date_issued"></strong> at
                        <strong x-text="coe.company_address"></strong>, Philippines.
                    </p>

                    <p class="mb-10"><strong>Certificate No.:</strong> <span x-text="coe.coe_number"></span></p>

                    <div class="mb-10 leading-snug">
                        <p class="font-bold">Approved By:</p>
                        <p x-text="coe.approver_name"></p>
                        <p>Human Capital</p>
                        <p>Approved on: <span x-text="coe.date_approved"></span></p>
                    </div>

                    <p class="text-[11px] text-justify mb-6">
                        This certificate discloses only information allowed by law and company policy. It is subject to applicable data privacy
                        requirements. Unauthorized access, use, disclosure, reproduction, or alteration of this certificate is strictly prohibited.
                    </p>

                    <p class="text-center text-[12px] font-bold">
                        This is a computer-generated Certificate of Employment. No signature is required.
                    </p>
                </div>
            </template>
            <template x-if="!isCoeRequest">
                <div class="min-h-[1040px] flex items-center justify-center text-center">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">COE preview is only available for COE requests.</p>
                    </div>
                </div>
            </template>
        </div>
                </div>
                </div>

                <!-- RIGHT SIDE: FORM -->
                <div class="min-h-0 overflow-auto bg-white">
                    <form :action="formAction" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                        @csrf

                        <!-- REQUEST TYPE SELECTOR -->
                        <div class="rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Request Type</div>
                            <div class="p-4">
                                <select name="request_type" x-model="form.request_type" :disabled="isView" required class="w-full border rounded-lg px-3 py-2 text-sm">
                                    <option value="">Select request type</option>
                                    <option value="Overtime Request">Overtime Request</option>
                                    <option value="Leave Application Request">Leave Application Request</option>
                                    <option value="Attendance Correction Request">Attendance Correction Request</option>
                                    <option value="Undertime / Absence Request">Undertime / Absence Request</option>
                                    <option value="COE Request Form">COE Request Form</option>
                                </select>
                            </div>
                        </div>

                        <!-- EMPLOYEE INFORMATION -->
                        <div class="rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Employee Information</div>
                            <div class="p-4 grid grid-cols-1 gap-3">
                                <template x-if="canManageRequests">
                                    <select
                                        name="employee_id"
                                        x-model="form.employee_id"
                                        @change="syncEmployee()"
                                        :disabled="isView || isApproval || isRejection"
                                        :required="!isApproval && !isRejection"
                                        class="w-full border rounded-lg px-3 py-2 text-sm"
                                    >
                                        <option value="">Select employee</option>
                                        <template x-for="employee in employees" :key="employee.id">
                                            <option :value="employee.id" x-text="`${employee.full_name} - ${employee.employee_code || 'No ID'}`"></option>
                                        </template>
                                    </select>
                                </template>

                                <template x-if="!canManageRequests">
                                    <div>
                                        <input type="hidden" name="employee_id" x-model="form.employee_id">
                                        <input type="text" x-model="form.employee_name" readonly class="w-full border rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-700 cursor-not-allowed" placeholder="Employee Name">
                                    </div>
                                </template>

                                <input type="text" x-model="form.department" readonly class="w-full border rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-700 cursor-not-allowed" placeholder="Department">
                            </div>
                        </div>

                        <!-- OVERTIME REQUEST FIELDS -->
                        <div class="rounded-xl border border-gray-200 overflow-hidden" x-show="form.request_type === 'Overtime Request'">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Overtime Details</div>
                            <div class="p-4 grid grid-cols-2 gap-3">
                                <input type="date" name="overtime_date" x-model="form.overtime_date" :readonly="isView" class="border rounded-lg px-3 py-2 text-sm">
                                <input type="number" name="total_hours" x-model="form.total_hours" @input="calculateOvertimeEnd()" step="0.01" :readonly="isView" placeholder="Total Hours" class="border rounded-lg px-3 py-2 text-sm">
                                <input type="time" name="start_time" x-model="form.start_time" @input="calculateOvertimeEnd()" :readonly="isView" class="border rounded-lg px-3 py-2 text-sm">
                                <input type="time" name="end_time" x-model="form.end_time" readonly class="border rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-700 cursor-not-allowed">
                                <textarea name="reason" x-model="form.reason" :readonly="isView" rows="3" placeholder="Reason / Purpose" class="col-span-2 border rounded-lg px-3 py-2 text-sm"></textarea>
                            </div>
                        </div>

                        <!-- LEAVE APPLICATION FIELDS -->
                        <div class="rounded-xl border border-gray-200 overflow-hidden" x-show="form.request_type === 'Leave Application Request'">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Leave Details</div>
                            <div class="p-4 grid grid-cols-2 gap-3">
                                <select name="leave_type" x-model="form.leave_type" :disabled="isView" class="col-span-2 border rounded-lg px-3 py-2 text-sm">
                                    <option value="">Select leave type</option>
                                    <option value="Sick Leave">Sick Leave</option>
                                    <option value="Vacation Leave">Vacation Leave</option>
                                    <option value="Emergency Leave">Emergency Leave</option>
                                    <option value="Maternity Leave">Maternity Leave</option>
                                    <option value="Paternity Leave">Paternity Leave</option>
                                </select>
                                <input type="date" name="start_date" x-model="form.start_date" :readonly="isView" class="border rounded-lg px-3 py-2 text-sm">
                                <input type="date" name="end_date" x-model="form.end_date" :readonly="isView" class="border rounded-lg px-3 py-2 text-sm">
                                <input type="number" name="number_of_days" x-model="form.number_of_days" step="0.5" :readonly="isView" placeholder="Number of Days" class="border rounded-lg px-3 py-2 text-sm">
                                <select name="with_pay" x-model="form.with_pay" :disabled="isView" class="border rounded-lg px-3 py-2 text-sm">
                                    <option value="">With Pay?</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                                <textarea name="reason" x-model="form.reason" :readonly="isView" rows="3" placeholder="Reason" class="col-span-2 border rounded-lg px-3 py-2 text-sm"></textarea>
                            </div>
                        </div>

                        <!-- ATTENDANCE CORRECTION FIELDS -->
                        <div class="rounded-xl border border-gray-200 overflow-hidden" x-show="form.request_type === 'Attendance Correction Request'">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Attendance Correction Details</div>
                            <div class="p-4 grid grid-cols-2 gap-3">
                                <input type="date" name="attendance_date" x-model="form.attendance_date" :readonly="isView" class="border rounded-lg px-3 py-2 text-sm">
                                <select name="correction_type" x-model="form.correction_type" :disabled="isView" class="border rounded-lg px-3 py-2 text-sm">
                                    <option value="">Select correction type</option>
                                    <option value="Time In">Time In</option>
                                    <option value="Time Out">Time Out</option>
                                    <option value="Break Time">Break Time</option>
                                    <option value="Lunch Time">Lunch Time</option>
                                </select>
                                <input type="time" name="correct_time" x-model="form.correct_time" :readonly="isView" class="col-span-2 border rounded-lg px-3 py-2 text-sm">
                                <textarea name="reason" x-model="form.reason" :readonly="isView" rows="3" placeholder="Reason for Correction" class="col-span-2 border rounded-lg px-3 py-2 text-sm"></textarea>
                            </div>
                        </div>

                        <!-- UNDERTIME / ABSENCE FIELDS -->
                        <div class="rounded-xl border border-gray-200 overflow-hidden" x-show="form.request_type === 'Undertime / Absence Request'">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Undertime / Absence Details</div>
                            <div class="p-4 grid grid-cols-2 gap-3">
                                <select name="absence_type" x-model="form.absence_type" :disabled="isView" class="border rounded-lg px-3 py-2 text-sm">
                                    <option value="">Select type</option>
                                    <option value="Undertime">Undertime</option>
                                    <option value="Absence">Absence</option>
                                </select>
                                <input type="date" name="request_date" x-model="form.request_date" :readonly="isView" class="border rounded-lg px-3 py-2 text-sm">
                                <input type="time" name="time_affected" x-model="form.time_affected" :readonly="isView" class="col-span-2 border rounded-lg px-3 py-2 text-sm" placeholder="Time Affected">
                                <textarea name="reason" x-model="form.reason" :readonly="isView" rows="3" placeholder="Reason" class="col-span-2 border rounded-lg px-3 py-2 text-sm"></textarea>
                            </div>
                        </div>

                        <!-- COE REQUEST FIELDS -->
                        <div class="rounded-xl border border-gray-200 overflow-hidden" x-show="form.request_type === 'COE Request Form'">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">COE Request Details</div>
                            <div class="p-4 grid grid-cols-2 gap-3">
                                <select name="purpose" x-model="form.purpose" :disabled="isView" class="col-span-2 border rounded-lg px-3 py-2 text-sm">
                                    <option value="">Select purpose</option>
                                    <option value="Employment Requirement">Employment Requirement</option>
                                    <option value="Loan Application">Loan Application</option>
                                    <option value="Visa / Travel">Visa / Travel</option>
                                    <option value="School Requirement">School Requirement</option>
                                </select>
                                <input type="date" name="date_needed" x-model="form.date_needed" :readonly="isView" class="border rounded-lg px-3 py-2 text-sm">
                                <input type="number" name="number_of_copies" x-model="form.number_of_copies" min="1" :readonly="isView" placeholder="Number of Copies" class="border rounded-lg px-3 py-2 text-sm">
                                <textarea name="remarks" x-model="form.remarks" :readonly="isView" rows="3" placeholder="Remarks" class="col-span-2 border rounded-lg px-3 py-2 text-sm"></textarea>
                            </div>
                        </div>

                        <!-- ATTACHMENT -->
                        <div class="rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Document Attachment</div>
                            <div class="p-4 space-y-3">
                                <template x-if="attachmentPreviewUrl">
                                    <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Current Attachment</p>
                                            <p class="text-sm text-gray-800 truncate" x-text="attachmentPreviewName || 'Attachment'"></p>
                                        </div>
                                        <button type="button" @click="previewMode = 'attachment'" class="shrink-0 px-3 py-2 text-xs font-semibold text-blue-700 border border-blue-200 rounded-lg bg-white hover:bg-blue-50">Preview</button>
                                    </div>
                                </template>
                                <input
                                    type="file"
                                    name="attachment"
                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                                    @change="handleAttachmentChange($event)"
                                    :disabled="isView"
                                    class="w-full border rounded-lg px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                                >
                                <p class="text-xs text-gray-500">Optional proof or supporting document. PDF, image, Word, or Excel files up to 10 MB.</p>
                            </div>
                        </div>

                        <!-- ADMIN REVIEW SECTION (Admin only) -->
                        <template x-if="canManageRequests">
                            <div class="rounded-xl border border-gray-200 overflow-hidden">
                                <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Admin Review</div>
                                <div class="p-4 grid grid-cols-1 gap-3">
                                    <textarea name="admin_note" x-model="form.admin_note" :readonly="isView" rows="3" placeholder="Admin note / remarks" class="border rounded-lg px-3 py-2 text-sm"></textarea>
                                    <select name="status" x-model="form.status" x-show="!isView" class="border rounded-lg px-3 py-2 text-sm">
                                        <option value="Pending">Pending</option>
                                        <option value="Approved">Approved</option>
                                        <option value="For Revision">For Revision</option>
                                        <option value="Declined">Declined</option>
                                    </select>
                                </div>
                            </div>
                        </template>

                        <div class="pt-2 border-t flex justify-end gap-3 pb-2">
                            <button type="button" @click="closePanel()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                            <button type="submit" x-show="!isView" class="px-5 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold" x-text="submitLabel"></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .section-title {
        margin-top: 10px;
        margin-bottom: 4px;
        background: #1d4ed8;
        color: white;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding: 5px 8px;
        border-radius: 2px;
    }

    .preview-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
    }

    .preview-table td {
        border: 1px solid #d1d5db;
        padding: 7px;
        vertical-align: top;
    }

    @media print {
        body * { visibility: hidden; }
        .print-area, .print-area * { visibility: visible; }
        .print-area { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; border: none; }
    }
</style>
@endpush

@push('scripts')
<script>
function employeeRequestsPage() {
    return {
        myRequests: @json($myEmployeeRequests ?? collect()),
        allRequests: @json($employeeRequests ?? collect()),
        canManageRequests: @json($canManageEmployeeRequests ?? false),
        employees: @json($employees ?? collect()),
        currentEmployee: @json($currentEmployeeProfile ?? null),
        activeTab: 'my-requests',
        showPanel: false,
        mode: 'create',
        selectedEmployee: '',
        previewMode: 'form',
        selectedAttachmentUrl: '',
        selectedAttachmentName: '',
        form: {},

        get isView() {
            return this.mode === 'view';
        },

        get isEdit() {
            return this.mode === 'edit';
        },

        get isApproval() {
            return this.mode === 'approve';
        },

        get isRejection() {
            return this.mode === 'reject';
        },

        get isAdminEdit() {
            return this.mode === 'admin-edit';
        },

        get attachmentPreviewUrl() {
            return this.selectedAttachmentUrl || this.form.attachment_url || '';
        },

        get attachmentPreviewName() {
            return this.selectedAttachmentName || this.form.attachment_original_name || '';
        },

        get isCoeRequest() {
            return this.form.request_type === 'COE Request Form';
        },

        get isApprovedCoe() {
            return this.isCoeRequest && this.form.status === 'Approved';
        },

        get coe() {
            return this.form.coe_preview || {};
        },

        get panelTitle() {
            if (this.isView) return 'View Employee Request';
            if (this.isApproval) return 'Approve Employee Request';
            if (this.isRejection) return 'Reject Employee Request';
            if (this.isAdminEdit) return 'Edit Employee Request';
            if (this.isEdit) return 'Edit Employee Request';
            return 'New Employee Request';
        },

        get formAction() {
            const base = `{{ url('/human-capital/employee-requests') }}`;

            if (this.isApproval && this.form.id) {
                return `${base}/${this.form.id}/approve`;
            }

            if (this.isRejection && this.form.id) {
                return `${base}/${this.form.id}/reject`;
            }

            if (this.isEdit && this.form.id) {
                return `${base}/${this.form.id}/update-revision`;
            }

            if (this.isAdminEdit && this.form.id) {
                return `${base}/${this.form.id}/update`;
            }

            return `{{ route('human-capital.employee-requests.store') }}`;
        },

        get submitLabel() {
            if (this.isApproval) return 'Approve Request';
            if (this.isRejection) return 'Reject Request';
            if (this.isAdminEdit) return 'Update Request';
            if (this.isEdit) return 'Submit Revision';
            return 'Save Request';
        },

        get employeeFilterOptions() {
            return [...new Set(this.allRequests.map(request => request.employee_name).filter(Boolean))].sort();
        },

        get filteredRequests() {
            if (!this.selectedEmployee) {
                return this.allRequests;
            }

            return this.allRequests.filter(request => request.employee_name === this.selectedEmployee);
        },

        defaultForm() {
            return {
                id: null,
                request_type: '',
                employee_id: this.currentEmployee?.id || '',
                employee_name: this.currentEmployee?.full_name || '{{ auth()->user()->name ?? "" }}',
                department: this.currentEmployee?.department || '',
                overtime_date: '',
                total_hours: '',
                start_time: '',
                end_time: '',
                leave_type: '',
                start_date: '',
                end_date: '',
                number_of_days: '',
                with_pay: '',
                attendance_date: '',
                correction_type: '',
                correct_time: '',
                request_date: '',
                absence_type: '',
                time_affected: '',
                purpose: '',
                date_needed: '',
                number_of_copies: '',
                reason: '',
                remarks: '',
                admin_note: '',
                status: 'Pending',
                created_at: '',
                attachment_url: '',
                attachment_original_name: '',
                coe_preview: null,
                coe_download_url: '',
            };
        },

        openAdd() {
            this.mode = 'create';
            this.form = this.defaultForm();
            this.resetAttachmentSelection();
            this.previewMode = 'form';
            this.syncEmployee();
            this.showPanel = true;
        },

        openView(request) {
            this.mode = 'view';
            this.form = { ...this.defaultForm(), ...request };
            this.resetAttachmentSelection();
            this.previewMode = request.request_type === 'COE Request Form' ? 'coe' : 'form';
            this.calculateOvertimeEnd();
            this.showPanel = true;
        },

        openEdit(request) {
            this.mode = 'edit';
            this.form = { ...this.defaultForm(), ...request };
            this.resetAttachmentSelection();
            this.previewMode = 'form';
            this.calculateOvertimeEnd();
            this.showPanel = true;
        },

        openAdminEdit(request) {
            this.mode = 'admin-edit';
            this.form = { ...this.defaultForm(), ...request };
            this.resetAttachmentSelection();
            this.previewMode = 'form';
            this.calculateOvertimeEnd();
            this.showPanel = true;
        },

        openApproveForm(request) {
            this.mode = 'approve';
            this.form = { ...this.defaultForm(), ...request, status: 'Approved' };
            this.resetAttachmentSelection();
            this.previewMode = 'form';
            this.calculateOvertimeEnd();
            this.showPanel = true;
        },

        openRejectForm(request) {
            this.mode = 'reject';
            this.form = { ...this.defaultForm(), ...request, status: 'Declined' };
            this.resetAttachmentSelection();
            this.previewMode = 'form';
            this.calculateOvertimeEnd();
            this.showPanel = true;
        },

        closePanel() {
            this.showPanel = false;
        },

        resetAttachmentSelection() {
            if (this.selectedAttachmentUrl) {
                URL.revokeObjectURL(this.selectedAttachmentUrl);
            }

            this.selectedAttachmentUrl = '';
            this.selectedAttachmentName = '';
        },

        handleAttachmentChange(event) {
            this.resetAttachmentSelection();

            const file = event.target.files?.[0];

            if (!file) {
                return;
            }

            this.selectedAttachmentUrl = URL.createObjectURL(file);
            this.selectedAttachmentName = file.name;
            this.previewMode = 'attachment';
        },

        syncEmployee() {
            if (!this.canManageRequests) {
                if (this.currentEmployee) {
                    this.form.employee_id = this.currentEmployee.id;
                    this.form.employee_name = this.currentEmployee.full_name || this.form.employee_name;
                    this.form.department = this.currentEmployee.department || '';
                }

                return;
            }

            const employee = this.employees.find(item => String(item.id) === String(this.form.employee_id));

            if (!employee) {
                this.form.employee_name = '';
                this.form.department = '';
                return;
            }

            this.form.employee_name = employee.full_name || '';
            this.form.department = employee.department || '';
        },

        statusClass(status) {
            const map = {
                'Pending': 'bg-yellow-100 text-yellow-700',
                'Approved': 'bg-green-100 text-green-700',
                'For Revision': 'bg-blue-100 text-blue-700',
                'Declined': 'bg-red-100 text-red-700',
            };
            return map[status] || 'bg-gray-100 text-gray-700';
        },

        calculateOvertimeEnd() {
            if (this.form.request_type !== 'Overtime Request' || !this.form.start_time || !this.form.total_hours) {
                return;
            }

            const [hours, minutes] = this.form.start_time.split(':').map(Number);
            const overtimeMinutes = Math.round(Number(this.form.total_hours) * 60);

            if (!Number.isFinite(hours) || !Number.isFinite(minutes) || !Number.isFinite(overtimeMinutes)) {
                return;
            }

            const endMinutes = ((hours * 60 + minutes + overtimeMinutes) % 1440 + 1440) % 1440;
            const endHours = String(Math.floor(endMinutes / 60)).padStart(2, '0');
            const endMins = String(endMinutes % 60).padStart(2, '0');

            this.form.end_time = `${endHours}:${endMins}`;
        },
    };
}
</script>
@endpush

@endsection

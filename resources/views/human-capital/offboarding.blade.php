@extends('layouts.app')

@section('content')
@php
    $companyHeader = $companyHeader ?? [
        'logo_url' => asset('images/jk-logo.png'),
        'company_name' => 'JOHN KELLY & COMPANY (JK&C INC)',
        'company_address' => '3F Cebu Holdings Center Cebu Business Park, Cebu City, Philippines, 6000',
    ];
@endphp
<div class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col" x-data="offboardingPage()">
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">
        <div class="flex items-center justify-between px-5 py-4 border-b shrink-0 gap-4">
            <div>
                <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">Human Capital</p>
                <h1 class="text-lg font-semibold text-gray-900">Offboarding</h1>
                <p class="text-xs text-gray-500">Termination, resignation, clearance, turnover, final pay, and exit documents.</p>
            </div>

            <button
                type="button"
                @click="openAdd()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-semibold"
            >
                + Add New
            </button>
        </div>

        <div class="px-5 py-3 border-b bg-white shrink-0 overflow-x-auto">
            <div class="inline-flex min-w-max rounded-lg border border-gray-200 overflow-hidden">
                <template x-for="module in modules" :key="module.key">
                    <button
                        type="button"
                        @click="setTab(module.key)"
                        class="px-4 py-2 text-sm border-r border-gray-200 last:border-r-0 transition"
                        :class="activeTab === module.key ? 'bg-blue-50 text-blue-700 font-semibold' : 'bg-white text-gray-600 hover:bg-gray-50'"
                        x-text="module.label"
                    ></button>
                </template>
            </div>
        </div>

        @if(session('success'))
            <div class="mx-5 mt-4 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm font-semibold">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mx-5 mt-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                <p class="font-bold mb-1">Please fix the following:</p>
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="p-5 flex-grow overflow-hidden">
            <div class="border rounded-xl h-full overflow-auto bg-white">
                <table class="w-full text-sm min-w-[1100px] border-collapse">
                    <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                        <tr>
                            <template x-for="column in currentModule().columns" :key="column.key">
                                <th class="p-3 text-left" x-text="column.label"></th>
                            </template>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="filteredRecords().length === 0">
                            <tr>
                                <td :colspan="currentModule().columns.length + 1" class="p-10 text-center text-gray-400">
                                    No records yet. Click + Add New to create one.
                                </td>
                            </tr>
                        </template>

                        <template x-for="record in filteredRecords()" :key="record.id">
                            <tr class="border-t hover:bg-gray-50">
                                <template x-for="column in currentModule().columns" :key="column.key">
                                    <td class="p-3 text-gray-700">
                                        <template x-if="column.key === 'reference_no'">
                                            <span class="font-semibold text-blue-700" x-text="record.reference_no"></span>
                                        </template>
                                        <template x-if="column.key === 'status'">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold" :class="statusClass(record.status)" x-text="record.status"></span>
                                        </template>
                                        <template x-if="!['reference_no', 'status'].includes(column.key)">
                                            <span x-text="displayValue(record, column.key)"></span>
                                        </template>
                                    </td>
                                </template>
                                <td class="p-3 text-right whitespace-nowrap">
                                    <button type="button" @click="openView(record)" class="text-indigo-600 hover:underline text-xs font-semibold mr-3">View</button>
                                    <button type="button" @click="openEdit(record)" class="text-blue-600 hover:underline text-xs font-semibold mr-3">Edit</button>
                                    <form :action="`{{ url('/human-capital/offboarding') }}/${record.id}`" method="POST" class="inline" onsubmit="return confirm('Delete this offboarding record?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:underline text-xs font-semibold">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div
        x-show="showPanel"
        x-transition.opacity
        class="fixed inset-0 z-50 bg-black/40 flex justify-end"
        style="display:none;"
        @click.self="closePanel()"
    >
        <div
            x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="w-screen h-full bg-white shadow-xl flex flex-col"
        >
            <div class="px-6 py-4 border-b bg-white flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold uppercase tracking-widest text-gray-900" x-text="panelTitle"></h2>
                    <p class="text-xs text-gray-500" x-text="form.reference_no || currentModule().label"></p>
                </div>
                <button type="button" @click="closePanel()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>

            <div class="flex-1 min-h-0 grid grid-cols-[58%_42%] bg-gray-50">
                <div class="min-h-0 border-r bg-gray-100 flex flex-col">
                    <div class="shrink-0 flex items-center justify-between border-b border-gray-200 bg-gray-100 px-5 py-3 z-20">
                        <div class="flex items-center gap-3">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">PDF Preview</p>
                            <div class="inline-flex rounded-lg border border-gray-300 bg-white p-1">
                                <button type="button" @click="previewMode = 'form'" :class="previewMode === 'form' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'" class="px-3 py-1.5 rounded-md text-xs font-semibold">Offboarding Form</button>
                                <button type="button" @click="previewMode = 'attachment'" :disabled="!attachmentPreviewUrl" :class="previewMode === 'attachment' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50 disabled:text-gray-300 disabled:hover:bg-white'" class="px-3 py-1.5 rounded-md text-xs font-semibold">Attached Document</button>
                            </div>
                        </div>
                        <button type="button" onclick="window.print()" class="px-3 py-2 border rounded-lg text-xs font-semibold text-gray-700 bg-white">Download PDF</button>
                    </div>

                    <div class="flex-1 min-h-0 overflow-auto p-5">
                    <div x-show="previewMode === 'form'" class="bg-white mx-auto border border-gray-300 shadow-lg px-10 py-8 text-[11px] leading-tight w-[820px] min-h-[1123px] print-area">
                        <div class="text-center border-b-2 border-blue-700 pb-4 mb-4">
                            <img src="{{ $companyHeader['logo_url'] }}" onerror="this.style.display='none';" class="h-24 mx-auto mb-2 object-contain" alt="Company Logo">
                            <p class="mt-2 text-[12px] font-bold uppercase">{{ $companyHeader['company_name'] }}</p>
                            <p class="mt-1 text-[11px] font-semibold">{{ $companyHeader['company_address'] }}</p>
                            <p class="mt-1">Form Code: <span x-text="currentModule().formCode"></span> | Version: 1.0 | Issued by: Human Capital</p>
                        </div>

                        <div class="bg-blue-700 text-white px-3 py-2 font-bold uppercase tracking-widest text-sm mb-3 rounded-sm" x-text="currentModule().label"></div>

                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <div><span class="font-bold">Reference No:</span> <span x-text="form.reference_no || 'Auto-generated'"></span></div>
                            <div><span class="font-bold">Status:</span> <span x-text="form.status || 'Draft'"></span></div>
                            <div><span class="font-bold">Employee:</span> <span x-text="form.employee_name || '-'"></span></div>
                            <div><span class="font-bold">Generated:</span> {{ now()->format('M d, Y h:i A') }}</div>
                        </div>

                        <template x-for="section in currentModule().previewSections" :key="section.title">
                            <div>
                                <div class="section-title" x-text="section.title"></div>
                                <table class="preview-table">
                                    <template x-for="row in chunk(section.fields, 2)" :key="section.title + '-' + row[0].key">
                                        <tr>
                                            <template x-for="item in row" :key="item.key">
                                                <td :colspan="item.full ? 2 : 1">
                                                    <b x-text="item.label + ':'"></b>
                                                    <template x-if="item.multiline">
                                                        <div class="mt-1 min-h-[38px]" x-text="displayValue(form, item.key)"></div>
                                                    </template>
                                                    <template x-if="!item.multiline">
                                                        <span x-text="displayValue(form, item.key)"></span>
                                                    </template>
                                                </td>
                                            </template>
                                            <template x-if="row.length === 1 && !row[0].full">
                                                <td></td>
                                            </template>
                                        </tr>
                                    </template>
                                </table>
                            </div>
                        </template>

                        <div class="grid grid-cols-3 gap-8 mt-12 text-center">
                            <div><div class="border-b border-gray-700 h-8"></div><p class="mt-1 font-bold">Employee</p></div>
                            <div><div class="border-b border-gray-700 h-8"></div><p class="mt-1 font-bold">Department Head</p></div>
                            <div><div class="border-b border-gray-700 h-8"></div><p class="mt-1 font-bold">Human Capital</p></div>
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
                    </div>
                </div>

                <div class="min-h-0 overflow-auto bg-white">
                    <form :action="formAction()" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                        @csrf
                        <template x-if="isEdit">
                            <input type="hidden" name="_method" value="PUT">
                        </template>
                        <input type="hidden" name="form_type" :value="activeTab">
                        <div class="rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Document Details</div>
                            <div class="p-4 grid grid-cols-2 gap-3">
                                <div class="col-span-2">
                                    <label class="label">Offboarding Submodule</label>
                                    <input :value="currentModule().label" readonly class="input bg-gray-100">
                                </div>
                                <div>
                                    <label class="label">Reference No.</label>
                                    <input x-model="form.reference_no" readonly class="input bg-gray-100">
                                </div>
                                <div>
                                    <label class="label">Status</label>
                                    <select name="status" x-model="form.status" :disabled="isView" class="input">
                                        <option>Draft</option>
                                        <option>Pending</option>
                                        <option>Processing</option>
                                        <option>Completed</option>
                                        <option>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest">Employee Information</div>
                            <div class="p-4 grid grid-cols-2 gap-3">
                                <div class="col-span-2">
                                    <label class="label">Employee</label>
                                    <select name="employee_id" x-model="form.employee_id" @change="syncEmployee()" :disabled="isView" required class="input">
                                        <option value="">Select employee</option>
                                        <template x-for="employee in employees" :key="employee.id">
                                            <option :value="employee.id" x-text="`${employee.full_name} - ${employee.employee_code || 'No ID'}`"></option>
                                        </template>
                                    </select>
                                </div>
                                <div><label class="label">Employee ID</label><input x-model="form.employee_code" readonly class="input bg-gray-100"></div>
                                <div><label class="label">Position</label><input x-model="form.position" readonly class="input bg-gray-100"></div>
                                <div class="col-span-2"><label class="label">Department</label><input x-model="form.department" readonly class="input bg-gray-100"></div>
                            </div>
                        </div>

                        <template x-for="section in currentModule().formSections" :key="section.title">
                            <div class="rounded-xl border border-gray-200 overflow-hidden">
                                <div class="px-4 py-2 bg-blue-700 text-white text-xs font-bold uppercase tracking-widest" x-text="section.title"></div>
                                <div class="p-4 grid grid-cols-2 gap-3">
                                    <template x-for="field in section.fields" :key="field.key">
                                        <div :class="field.span === 2 ? 'col-span-2' : ''">
                                            <label class="label" x-text="field.label"></label>

                                            <template x-if="field.type === 'textarea'">
                                                <textarea :name="field.key" x-model="form[field.key]" :readonly="isView" :rows="field.rows || 3" class="input min-h-[88px]"></textarea>
                                            </template>

                                            <template x-if="field.type === 'select'">
                                                <select :name="field.key" x-model="form[field.key]" :disabled="isView" class="input">
                                                    <template x-for="option in field.options" :key="option">
                                                        <option :value="option" x-text="option"></option>
                                                    </template>
                                                </select>
                                            </template>

                                            <template x-if="!['textarea', 'select'].includes(field.type)">
                                                <input :name="field.key" x-model="form[field.key]" :readonly="isView" :type="field.type || 'text'" :step="field.type === 'number' ? '0.01' : null" class="input">
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

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
                                    class="input"
                                >
                                <p class="text-xs text-gray-500">Optional supporting document. PDF, image, Word, or Excel files up to 10 MB.</p>
                            </div>
                        </div>

                        <div class="sticky bottom-0 bg-white border-t pt-4 flex gap-2">
                            <button type="button" @click="closePanel()" class="flex-1 border border-gray-300 rounded-lg py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                            <button type="submit" x-show="!isView" class="flex-1 bg-blue-600 text-white rounded-lg py-2 text-sm font-semibold hover:bg-blue-700">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .label {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        color: #4b5563;
        margin-bottom: 0.25rem;
    }

    .input {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        background: #fff;
    }

    .section-title {
        background: #1d4ed8;
        color: #fff;
        font-weight: 700;
        letter-spacing: .08em;
        margin-top: 12px;
        padding: 6px 8px;
        text-transform: uppercase;
    }

    .preview-table {
        border-collapse: collapse;
        width: 100%;
        margin-bottom: 8px;
    }

    .preview-table td {
        border: 1px solid #d1d5db;
        padding: 7px;
        vertical-align: top;
        width: 50%;
    }

    .preview-table b {
        color: #111827;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        .print-area,
        .print-area * {
            visibility: visible;
        }

        .print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100% !important;
            border: 0 !important;
            box-shadow: none !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
function offboardingPage() {
    const employees = @json($employees);
    const records = @json($records);
    const commonEmployee = [];

    const modules = [
        {
            key: 'termination',
            label: 'Termination',
            prefix: 'TER',
            formCode: 'OFF-TER-F001',
            columns: [
                { key: 'reference_no', label: 'Reference No.' },
                { key: 'employee_name', label: 'Employee' },
                { key: 'termination_reason', label: 'Reason' },
                { key: 'effective_date', label: 'Effective Date' },
                { key: 'status', label: 'Status' },
            ],
            formSections: [
                ...commonEmployee,
                { title: 'Termination Details', fields: [
                    { key: 'notice_date', label: 'Notice Date', type: 'date' },
                    { key: 'effective_date', label: 'Effective Date', type: 'date' },
                    { key: 'termination_reason', label: 'Reason for Termination', type: 'select', options: ['Performance', 'Misconduct', 'Redundancy', 'End of Contract', 'Other'] },
                    { key: 'final_working_day', label: 'Final Working Day', type: 'date' },
                    { key: 'facts_and_basis', label: 'Facts and Basis', type: 'textarea', span: 2, rows: 4 },
                    { key: 'company_property', label: 'Company Property to Return', type: 'textarea', span: 2 },
                ] },
                { title: 'HR Review', fields: [
                    { key: 'hr_reviewer', label: 'HR Reviewer' },
                    { key: 'legal_review', label: 'Legal Review Required', type: 'select', options: ['No', 'Yes'] },
                    { key: 'remarks', label: 'Remarks', type: 'textarea', span: 2 },
                ] },
            ],
            previewSections: [
                { title: 'A. Employee Information', fields: commonPreviewEmployee() },
                { title: 'B. Termination Notice', fields: [
                    p('notice_date', 'Notice Date'), p('effective_date', 'Effective Date'),
                    p('termination_reason', 'Reason'), p('final_working_day', 'Final Working Day'),
                    p('facts_and_basis', 'Facts and Basis', true, true),
                    p('company_property', 'Company Property to Return', true, true),
                ] },
                { title: 'C. HR Review', fields: [p('hr_reviewer', 'HR Reviewer'), p('legal_review', 'Legal Review Required'), p('remarks', 'Remarks', true, true)] },
            ],
        },
        {
            key: 'resignation',
            label: 'Resignation',
            prefix: 'RES',
            formCode: 'OFF-RES-F001',
            columns: [
                { key: 'reference_no', label: 'Reference No.' },
                { key: 'employee_name', label: 'Employee' },
                { key: 'submission_date', label: 'Submitted' },
                { key: 'effective_date', label: 'Effectivity' },
                { key: 'status', label: 'Status' },
            ],
            formSections: [
                ...commonEmployee,
                { title: 'Resignation Details', fields: [
                    { key: 'submission_date', label: 'Submission Date', type: 'date' },
                    { key: 'effective_date', label: 'Effective Date', type: 'date' },
                    { key: 'resignation_type', label: 'Resignation Type', type: 'select', options: ['Voluntary', 'Immediate', 'End of Contract', 'Retirement'] },
                    { key: 'notice_period', label: 'Notice Period' },
                    { key: 'reason_for_leaving', label: 'Reason for Leaving', type: 'textarea', span: 2, rows: 4 },
                    { key: 'transition_notes', label: 'Transition Notes', type: 'textarea', span: 2 },
                ] },
                { title: 'Acceptance', fields: [
                    { key: 'accepted_by', label: 'Accepted By' },
                    { key: 'acceptance_date', label: 'Acceptance Date', type: 'date' },
                    { key: 'remarks', label: 'Remarks', type: 'textarea', span: 2 },
                ] },
            ],
            previewSections: [
                { title: 'A. Employee Information', fields: commonPreviewEmployee() },
                { title: 'B. Resignation Details', fields: [
                    p('submission_date', 'Submission Date'), p('effective_date', 'Effective Date'),
                    p('resignation_type', 'Type'), p('notice_period', 'Notice Period'),
                    p('reason_for_leaving', 'Reason for Leaving', true, true),
                    p('transition_notes', 'Transition Notes', true, true),
                ] },
                { title: 'C. Acceptance', fields: [p('accepted_by', 'Accepted By'), p('acceptance_date', 'Acceptance Date'), p('remarks', 'Remarks', true, true)] },
            ],
        },
        {
            key: 'exit-interview',
            label: 'Exit Interview Form',
            prefix: 'EXI',
            formCode: 'OFF-EXI-F001',
            columns: [
                { key: 'reference_no', label: 'Reference No.' },
                { key: 'employee_name', label: 'Employee' },
                { key: 'interview_date', label: 'Interview Date' },
                { key: 'interviewer', label: 'Interviewer' },
                { key: 'status', label: 'Status' },
            ],
            formSections: [
                ...commonEmployee,
                { title: 'Interview Schedule', fields: [
                    { key: 'interview_date', label: 'Interview Date', type: 'date' },
                    { key: 'interviewer', label: 'Interviewer' },
                    { key: 'employment_experience', label: 'Overall Employment Experience', type: 'select', options: ['Excellent', 'Good', 'Fair', 'Poor'] },
                    { key: 'rehire_eligible', label: 'Eligible for Rehire', type: 'select', options: ['Yes', 'No', 'For Review'] },
                ] },
                { title: 'Feedback', fields: [
                    { key: 'primary_reason', label: 'Primary Reason for Leaving', type: 'textarea', span: 2 },
                    { key: 'work_environment_feedback', label: 'Work Environment Feedback', type: 'textarea', span: 2 },
                    { key: 'management_feedback', label: 'Management Feedback', type: 'textarea', span: 2 },
                    { key: 'recommendations', label: 'Recommendations', type: 'textarea', span: 2 },
                ] },
            ],
            previewSections: [
                { title: 'A. Employee Information', fields: commonPreviewEmployee() },
                { title: 'B. Interview Summary', fields: [
                    p('interview_date', 'Interview Date'), p('interviewer', 'Interviewer'),
                    p('employment_experience', 'Experience'), p('rehire_eligible', 'Eligible for Rehire'),
                    p('primary_reason', 'Primary Reason', true, true),
                    p('work_environment_feedback', 'Work Environment Feedback', true, true),
                    p('management_feedback', 'Management Feedback', true, true),
                    p('recommendations', 'Recommendations', true, true),
                ] },
            ],
        },
        {
            key: 'clearance',
            label: 'Clearance Form',
            prefix: 'CLR',
            formCode: 'OFF-CLR-F001',
            columns: [
                { key: 'reference_no', label: 'Reference No.' },
                { key: 'employee_name', label: 'Employee' },
                { key: 'department', label: 'Department' },
                { key: 'clearance_due_date', label: 'Due Date' },
                { key: 'status', label: 'Status' },
            ],
            formSections: [
                ...commonEmployee,
                { title: 'Clearance Items', fields: [
                    { key: 'clearance_due_date', label: 'Clearance Due Date', type: 'date' },
                    { key: 'it_clearance', label: 'IT Clearance', type: 'select', options: ['Pending', 'Cleared', 'With Accountability'] },
                    { key: 'finance_clearance', label: 'Finance Clearance', type: 'select', options: ['Pending', 'Cleared', 'With Accountability'] },
                    { key: 'admin_clearance', label: 'Admin / Property Clearance', type: 'select', options: ['Pending', 'Cleared', 'With Accountability'] },
                    { key: 'hc_clearance', label: 'Human Capital Clearance', type: 'select', options: ['Pending', 'Cleared', 'With Accountability'] },
                    { key: 'accountabilities', label: 'Outstanding Accountabilities', type: 'textarea', span: 2 },
                ] },
            ],
            previewSections: [
                { title: 'A. Employee Information', fields: commonPreviewEmployee() },
                { title: 'B. Departmental Clearance', fields: [
                    p('clearance_due_date', 'Due Date'), p('it_clearance', 'IT'),
                    p('finance_clearance', 'Finance'), p('admin_clearance', 'Admin / Property'),
                    p('hc_clearance', 'Human Capital'), p('accountabilities', 'Outstanding Accountabilities', true, true),
                ] },
            ],
        },
        {
            key: 'turnover',
            label: 'Turnover Checklist',
            prefix: 'TUR',
            formCode: 'OFF-TUR-F001',
            columns: [
                { key: 'reference_no', label: 'Reference No.' },
                { key: 'employee_name', label: 'Employee' },
                { key: 'assigned_to', label: 'Assigned To' },
                { key: 'turnover_due_date', label: 'Due Date' },
                { key: 'status', label: 'Status' },
            ],
            formSections: [
                ...commonEmployee,
                { title: 'Turnover Details', fields: [
                    { key: 'assigned_to', label: 'Assigned To' },
                    { key: 'turnover_due_date', label: 'Due Date', type: 'date' },
                    { key: 'documents_status', label: 'Documents / Files', type: 'select', options: ['Pending', 'Turned Over', 'Not Applicable'] },
                    { key: 'client_accounts_status', label: 'Client / Account Handover', type: 'select', options: ['Pending', 'Turned Over', 'Not Applicable'] },
                    { key: 'system_access_status', label: 'System Access Handover', type: 'select', options: ['Pending', 'Turned Over', 'Not Applicable'] },
                    { key: 'pending_tasks_status', label: 'Pending Tasks', type: 'select', options: ['Pending', 'Turned Over', 'Not Applicable'] },
                    { key: 'turnover_notes', label: 'Turnover Notes', type: 'textarea', span: 2, rows: 4 },
                ] },
            ],
            previewSections: [
                { title: 'A. Employee Information', fields: commonPreviewEmployee() },
                { title: 'B. Turnover Checklist', fields: [
                    p('assigned_to', 'Assigned To'), p('turnover_due_date', 'Due Date'),
                    p('documents_status', 'Documents / Files'), p('client_accounts_status', 'Client / Account Handover'),
                    p('system_access_status', 'System Access Handover'), p('pending_tasks_status', 'Pending Tasks'),
                    p('turnover_notes', 'Notes', true, true),
                ] },
            ],
        },
        {
            key: 'final-pay',
            label: 'Final Pay Computation Sheet',
            prefix: 'FPC',
            formCode: 'OFF-FPC-F001',
            columns: [
                { key: 'reference_no', label: 'Reference No.' },
                { key: 'employee_name', label: 'Employee' },
                { key: 'net_final_pay', label: 'Net Final Pay' },
                { key: 'release_date', label: 'Release Date' },
                { key: 'status', label: 'Status' },
            ],
            formSections: [
                ...commonEmployee,
                { title: 'Final Pay Computation', fields: [
                    { key: 'basic_pay', label: 'Basic Pay / Salary Due', type: 'number' },
                    { key: 'unused_leave_pay', label: 'Unused Leave Pay', type: 'number' },
                    { key: 'thirteenth_month', label: '13th Month / Pro-rated', type: 'number' },
                    { key: 'other_earnings', label: 'Other Earnings', type: 'number' },
                    { key: 'deductions', label: 'Deductions / Accountabilities', type: 'number' },
                    { key: 'net_final_pay', label: 'Net Final Pay', type: 'number' },
                    { key: 'release_date', label: 'Release Date', type: 'date' },
                    { key: 'payment_method', label: 'Payment Method', type: 'select', options: ['Payroll Account', 'Check', 'Cash', 'Bank Transfer'] },
                    { key: 'computation_notes', label: 'Computation Notes', type: 'textarea', span: 2 },
                ] },
            ],
            previewSections: [
                { title: 'A. Employee Information', fields: commonPreviewEmployee() },
                { title: 'B. Final Pay Computation', fields: [
                    p('basic_pay', 'Basic Pay / Salary Due'), p('unused_leave_pay', 'Unused Leave Pay'),
                    p('thirteenth_month', '13th Month / Pro-rated'), p('other_earnings', 'Other Earnings'),
                    p('deductions', 'Deductions'), p('net_final_pay', 'Net Final Pay'),
                    p('release_date', 'Release Date'), p('payment_method', 'Payment Method'),
                    p('computation_notes', 'Computation Notes', true, true),
                ] },
            ],
        },
        {
            key: 'quitclaim',
            label: 'Quitclaim',
            prefix: 'QTC',
            formCode: 'OFF-QTC-F001',
            columns: [
                { key: 'reference_no', label: 'Reference No.' },
                { key: 'employee_name', label: 'Employee' },
                { key: 'settlement_amount', label: 'Settlement Amount' },
                { key: 'signed_date', label: 'Signed Date' },
                { key: 'status', label: 'Status' },
            ],
            formSections: [
                ...commonEmployee,
                { title: 'Quitclaim Details', fields: [
                    { key: 'settlement_amount', label: 'Settlement Amount', type: 'number' },
                    { key: 'payment_date', label: 'Payment Date', type: 'date' },
                    { key: 'signed_date', label: 'Signed Date', type: 'date' },
                    { key: 'witness_name', label: 'Witness Name' },
                    { key: 'release_clause', label: 'Release / Waiver Clause', type: 'textarea', span: 2, rows: 5 },
                    { key: 'notary_details', label: 'Notary Details', type: 'textarea', span: 2 },
                ] },
            ],
            previewSections: [
                { title: 'A. Employee Information', fields: commonPreviewEmployee() },
                { title: 'B. Quitclaim and Release', fields: [
                    p('settlement_amount', 'Settlement Amount'), p('payment_date', 'Payment Date'),
                    p('signed_date', 'Signed Date'), p('witness_name', 'Witness'),
                    p('release_clause', 'Release / Waiver Clause', true, true),
                    p('notary_details', 'Notary Details', true, true),
                ] },
            ],
        },
    ];

    function p(key, label, multiline = false, full = false) {
        return { key, label, multiline, full };
    }

    function commonPreviewEmployee() {
        return [
            p('employee_name', 'Employee Name'), p('employee_code', 'Employee ID'),
            p('position', 'Position'), p('department', 'Department'),
        ];
    }

    return {
        modules,
        activeTab: 'termination',
        employees,
        records,
        showPanel: false,
        isEdit: false,
        isView: false,
        editingId: null,
        panelTitle: 'Add Termination',
        previewMode: 'form',
        selectedAttachmentUrl: '',
        selectedAttachmentName: '',
        form: {},

        get attachmentPreviewUrl() {
            return this.selectedAttachmentUrl || this.form.attachment_url || '';
        },

        get attachmentPreviewName() {
            return this.selectedAttachmentName || this.form.attachment_original_name || '';
        },

        init() {
            this.form = this.blankForm('termination');
        },

        currentModule() {
            return this.modules.find(module => module.key === this.activeTab) || this.modules[0];
        },

        setTab(key) {
            this.activeTab = key;
            this.form = this.blankForm(key);
            this.resetAttachmentSelection();
            this.previewMode = 'form';

            this.panelTitle = `${this.isEdit ? 'Edit' : this.isView ? 'View' : 'Add'} ${this.currentModule().label}`;
        },

        openAdd() {
            this.isEdit = false;
            this.isView = false;
            this.editingId = null;
            this.form = this.blankForm(this.activeTab);
            this.panelTitle = `Add ${this.currentModule().label}`;
            this.resetAttachmentSelection();
            this.previewMode = 'form';
            this.showPanel = true;
        },

        openView(record) {
            this.isEdit = false;
            this.isView = true;
            this.activeTab = record.form_type || record.type;
            this.form = { ...this.blankForm(this.activeTab), ...record };
            this.panelTitle = `View ${this.currentModule().label}`;
            this.resetAttachmentSelection();
            this.previewMode = 'form';
            this.showPanel = true;
        },

        openEdit(record) {
            this.isEdit = true;
            this.isView = false;
            this.editingId = record.id;
            this.activeTab = record.form_type || record.type;
            this.form = { ...this.blankForm(this.activeTab), ...record };
            this.panelTitle = `Edit ${this.currentModule().label}`;
            this.resetAttachmentSelection();
            this.previewMode = 'form';
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

        formAction() {
            if (this.isEdit && this.editingId) {
                return `{{ url('/human-capital/offboarding') }}/${this.editingId}`;
            }

            return `{{ route('human-capital.offboarding.store') }}`;
        },

        filteredRecords() {
            return this.records.filter(record => (record.form_type || record.type) === this.activeTab);
        },

        blankForm(type) {
            return {
                type,
                reference_no: this.referenceFor(type),
                status: 'Draft',
                employee_id: '',
                employee_name: '',
                employee_code: '',
                position: '',
                department: '',
                attachment_url: '',
                attachment_original_name: '',
            };
        },

        referenceFor(type) {
            const module = this.modules.find(item => item.key === type) || this.modules[0];
            const count = this.records.filter(record => (record.form_type || record.type) === type).length + 1;
            return `${module.prefix}-${new Date().getFullYear()}-${String(count).padStart(4, '0')}`;
        },

        syncEmployee() {
            const employee = this.employees.find(item => String(item.id) === String(this.form.employee_id));

            if (!employee) {
                this.form.employee_name = '';
                this.form.employee_code = '';
                this.form.position = '';
                this.form.department = '';
                return;
            }

            this.form.employee_name = employee.full_name || '';
            this.form.employee_code = employee.employee_code || '';
            this.form.position = employee.position || '';
            this.form.department = employee.department || '';
        },

        displayValue(source, key) {
            const value = source[key];

            if (value === null || value === undefined || value === '') {
                return '-';
            }

            if (['basic_pay', 'unused_leave_pay', 'thirteenth_month', 'other_earnings', 'deductions', 'net_final_pay', 'settlement_amount'].includes(key)) {
                return `PHP ${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            }

            return value;
        },

        chunk(items, size) {
            const rows = [];
            let index = 0;

            while (index < items.length) {
                if (items[index].full) {
                    rows.push([items[index]]);
                    index += 1;
                } else {
                    rows.push(items.slice(index, index + size));
                    index += size;
                }
            }

            return rows;
        },

        statusClass(status) {
            return {
                Draft: 'bg-gray-100 text-gray-700',
                Pending: 'bg-amber-50 text-amber-700',
                Processing: 'bg-blue-50 text-blue-700',
                Completed: 'bg-emerald-50 text-emerald-700',
                Cancelled: 'bg-rose-50 text-rose-700',
            }[status] || 'bg-gray-100 text-gray-700';
        },
    };
}
</script>
@endpush

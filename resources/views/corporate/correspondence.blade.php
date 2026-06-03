@extends('layouts.app')
@section('title', 'Correspondence')

@php
    $types = [
        'Letters',
        'Demand Letter',
        'Request Letter',
        'Follow-Up Letter',
        'Notice',
        'Advisory Letter',
        'Transmittal Letter',
        'Authorization Letter',
        'Acknowledgment Letter',
        'Invitation Letter',
        'Endorsement Letter',
        'Complaint Letter',
        'Explanation Letter',
        'Response Letter',
        'Other',
    ];

    $companyInfo = $companyInfo ?? [
        'company_name' => 'JOHN KELLY & COMPANY (JK&C INC)',
        'registration_number' => '',
        'principal_address' => '3F Cebu Holdings Center Cebu Business Park, Cebu City, Philippines, 6000',
    ];

    $correspondenceLogoUrl = $correspondenceLogoUrl ?? asset('images/jk-logo.png');
@endphp

@section('content')
<div
    id="correspondence-page"
    class="w-full h-full px-6 py-5"
    x-data="{
        showSlideOver: false,
        hasDeadline: false,

        companyName: @js($companyInfo['company_name'] ?? ''),
        registrationNumber: @js($companyInfo['registration_number'] ?? ''),
        principalAddress: @js($companyInfo['principal_address'] ?? ''),
        correspondenceLogoUrl: @js($correspondenceLogoUrl ?? asset('images/jk-logo.png')),

        previewRef: 'AUTO-INCREMENT',
        previewDate: '{{ now()->format('Y-m-d') }}',
        previewType: 'Letters',
        previewTin: '',
        previewToForLabel: 'To',
        previewToFor: '',
        previewFrom: '{{ Auth::user()->name ?? 'System Super Admin' }}',
        previewDepartment: '',
        previewSubject: '',
        previewBody: '<p style=&quot;color:#9ca3af;&quot;>Write the formal correspondence here...</p>',
        previewDeadline: '',
        previewSentVia: 'Email',
        previewCc: '',
        previewAdditional: '',

        previewManagementApproverId: '',
        previewManagementName: '',
        previewManagementPosition: '',
        previewManagementDepartment: '',

        previewExecutiveApproverId: '',
        previewExecutiveName: '',
        previewExecutivePosition: '',
        previewExecutiveDepartment: '',

        closeAddSectionAlpine() {
            this.showSlideOver = false;
            closeAddSection();
        },

        syncManagementApprover() {
            const selected = managementApprovers.find(item => String(item.id) === String(this.previewManagementApproverId));
            this.previewManagementName = selected?.name || '';
            this.previewManagementPosition = selected?.position || '';
            this.previewManagementDepartment = selected?.department || '';
        },

        syncExecutiveApprover() {
            const selected = executiveApprovers.find(item => String(item.id) === String(this.previewExecutiveApproverId));
            this.previewExecutiveName = selected?.name || '';
            this.previewExecutivePosition = selected?.position || '';
            this.previewExecutiveDepartment = selected?.department || '';
        }
    }"
>
    <div class="bg-white border border-gray-200 rounded-xl min-h-[calc(100vh-7rem)] flex flex-col">
        <div class="px-5 py-4 flex items-center justify-between border-b border-gray-200">
            <div>
                <p class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.18em] text-blue-700 bg-blue-50 border border-blue-100 rounded-full px-3 py-1 mb-2">
                    <i class="fas fa-envelope-open-text"></i>
                    Corporate Governance
                </p>
                <h1 class="text-[30px] font-semibold text-gray-800 leading-none">Correspondence</h1>
                <p class="text-sm text-gray-500 mt-2">Create, submit, and approve official corporate correspondence.</p>
            </div>

            <button
                type="button"
                @click="showSlideOver = true"
                onclick="openAddSection()"
                class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm shrink-0 hover:bg-blue-700 transition"
            >
                + Add Correspondence
            </button>
        </div>

<div id="tableSection" class="px-5 pb-4 flex-1 flex flex-col">
            <div class="border border-gray-200 rounded-md overflow-hidden flex-1 overflow-auto">
                <table class="w-full text-sm text-left border-collapse">
                    <thead class="bg-gray-100 text-gray-700 sticky top-0 z-10">
                        <tr>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">Ref#</th>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">Date</th>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">Type</th>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">Company</th>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">To / For</th>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">From</th>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">Subject</th>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">Respond Before</th>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">Workflow</th>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">Approval</th>
                            <th class="px-3 py-3 font-semibold">Template</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody" class="bg-white"></tbody>
                </table>
            </div>
        </div>

        <div id="previewSection" class="hidden p-4 flex-grow overflow-hidden">
            <div class="h-full flex gap-4">
                <div class="flex-1 min-w-0 bg-white border border-gray-200 rounded-xl overflow-hidden">
                    <iframe id="previewFrame" class="w-full h-full bg-white" frameborder="0"></iframe>
                </div>

                <div class="w-[320px] shrink-0 flex flex-col gap-4">
                    <div class="bg-white border border-gray-200 rounded-xl px-5 py-4 flex items-center justify-between">
                        <h2 class="text-[20px] font-semibold text-gray-900">Document Preview</h2>
                        <button type="button" onclick="closePreview()" class="text-sm text-gray-500 hover:text-gray-700">Close</button>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-xl px-5 py-6 overflow-y-auto">
                        <h3 class="text-[18px] font-semibold text-gray-900 mb-6">Correspondence Information</h3>

                        <div class="space-y-5 text-[14px]">
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Type</span><span id="infoType" class="text-right font-medium text-gray-900"></span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Company</span><span id="infoCompany" class="text-right font-medium text-gray-900 break-words"></span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Reg No.</span><span id="infoRegNo" class="text-right font-medium text-gray-900 break-words"></span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Subject</span><span id="infoSubject" class="text-right font-medium text-gray-900"></span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">To / For</span><span id="infoToFor" class="text-right font-medium text-gray-900 break-all"></span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">From</span><span id="infoFrom" class="text-right font-medium text-gray-900 break-all"></span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Workflow</span><span id="infoWorkflowStatus" class="text-right font-medium text-gray-900"></span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Approval</span><span id="infoApprovalStatus" class="text-right font-medium text-gray-900"></span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Review Note</span><span id="infoReviewNote" class="text-right font-medium text-gray-900 break-words"></span></div>

                            <div class="pt-2 border-t border-gray-200 space-y-3" id="previewActions">
                                <a id="openPreviewBtn" href="#" target="_blank" class="text-sm text-blue-600 hover:underline block">Open in New Tab</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="showSlideOver" x-cloak class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/40" @click="closeAddSectionAlpine()"></div>

            <div class="absolute inset-0 flex">
                <div
                    x-show="showSlideOver"
                    x-transition:enter="transform transition ease-in-out duration-300"
                    x-transition:enter-start="-translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transform transition ease-in-out duration-300"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="-translate-x-full"
                    class="w-[70%] h-full bg-[#f5f6f8] overflow-y-auto p-6 border-r border-gray-200"
                >
                    <div class="max-w-[900px] mx-auto flex justify-center">
                        <div id="correspondence-preview-pdf" class="correspondence-a4-page bg-white border border-gray-300 shadow">
                            <div class="correspondence-header mb-12">
                                <div class="header-logo">
                                    <img :src="correspondenceLogoUrl" alt="Company Logo">
                                </div>

                                <div class="header-company">
                                    <p class="company-name" x-text="companyName || 'COMPANY NAME'"></p>
                                    <p>Registration No.: <span x-text="registrationNumber || '____________________'"></span></p>
                                    <p x-text="principalAddress || 'Principal Office Address'"></p>
                                </div>
                            </div>

                            <div class="text-center mb-10">
                                <h2 class="text-[24px] font-bold uppercase tracking-[0.14em]" x-text="previewType || 'CORRESPONDENCE'"></h2>
                            </div>

                            <div class="correspondence-fields text-gray-900">
                                <div class="field-row">
                                    <p class="font-semibold">Date:</p>
                                    <p class="pb-1" x-text="formatDisplayDate(previewDate) || '______________________'"></p>
                                </div>
                                <div class="field-row">
                                    <p class="font-semibold"><span x-text="previewToForLabel || 'To'"></span>:</p>
                                    <p class="pb-1 break-words" x-text="previewToFor || '______________________'"></p>
                                </div>
                                <div class="field-row">
                                    <p class="font-semibold">From:</p>
                                    <p class="pb-1 break-words" x-text="previewFrom || '______________________'"></p>
                                </div>
                                <div class="field-row">
                                    <p class="font-semibold uppercase">Subject:</p>
                                    <p class="pb-1 font-semibold break-words" x-text="previewSubject || '______________________________'"></p>
                                </div>
                            </div>

                            <div class="correspondence-divider"></div>

                            <div class="correspondence-body text-[15px] text-gray-900">
                                <div class="body-content" x-html="previewBody"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    x-show="showSlideOver"
                    x-transition:enter="transform transition ease-in-out duration-300"
                    x-transition:enter-start="translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transform transition ease-in-out duration-300"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-full"
                    class="w-[30%] h-full bg-white shadow-2xl flex flex-col"
                >
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800">Add Correspondence</h2>
                        <button type="button" @click="closeAddSectionAlpine()" class="text-gray-400 hover:text-gray-600 text-lg">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                        <div id="sliderErrorBox" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
                        <div id="sliderSuccessBox" class="hidden rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"></div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Correspondence Type</label>
                            <select id="typeInput" x-model="previewType" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                                @foreach($types as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm">
                            <p class="font-semibold text-blue-700">Header details auto-filled from latest approved GIS</p>
                            <p class="mt-1"><strong>Company Name:</strong> {{ $companyInfo['company_name'] ?? '—' }}</p>
                            <p><strong>Registration Number:</strong> {{ $companyInfo['registration_number'] ?? '—' }}</p>
                            <p><strong>Principal Address:</strong> {{ $companyInfo['principal_address'] ?? '—' }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Subject</label>
                            <input id="subjectInput" x-model="previewSubject" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500" placeholder="Enter subject">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">To / For</label>
                            <div class="grid grid-cols-[110px_1fr] gap-3">
                                <select id="toForLabelInput" x-model="previewToForLabel" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                                    <option value="To">To</option>
                                    <option value="For">For</option>
                                </select>

                                <input id="toForInput" x-model="previewToFor" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500" placeholder="Manually type recipient">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">From</label>
                            <input id="fromInput" x-model="previewFrom" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500" placeholder="Enter sender">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Department / Stakeholder</label>
                            <input id="departmentInput" x-model="previewDepartment" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500" placeholder="Enter department or stakeholder">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">CC</label>
                                <input id="ccInput" x-model="previewCc" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Optional">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Additional</label>
                                <input id="additionalInput" x-model="previewAdditional" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Optional">
                            </div>
                        </div>

                                                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 space-y-4">
                            <div>
                                <p class="text-xs font-bold uppercase text-blue-700">Approval Workflow</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Level 1 approver is selected from Employee Profile. Level 2 approver is selected from the latest approved GIS Directors / Officers list.
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-blue-700 mb-1">Level 1 Approver - From Management</label>
                                <select id="managementApproverInput" x-model="previewManagementApproverId" @change="syncManagementApprover()" class="w-full border border-blue-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                                    <option value="">Select active employee approver</option>
                                    @foreach(($managementApprovers ?? collect()) as $approver)
                                        <option value="{{ $approver['id'] }}">
                                            {{ $approver['name'] }} — {{ $approver['position'] ?? 'Management' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="rounded-lg border border-blue-100 bg-white p-3 text-sm">
                                <p class="text-xs font-bold uppercase text-blue-700 mb-2">From Management</p>
                                <p><span class="font-semibold">Name:</span> <span x-text="previewManagementName || '—'"></span></p>
                                <p><span class="font-semibold">Position:</span> <span x-text="previewManagementPosition || '—'"></span></p>
                                <p><span class="font-semibold">Department:</span> <span x-text="previewManagementDepartment || '—'"></span></p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-blue-700 mb-1">Level 2 Approver - From Executive Management</label>
                                <select id="executiveApproverInput" x-model="previewExecutiveApproverId" @change="syncExecutiveApprover()" class="w-full border border-blue-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                                    <option value="">Select GIS director/officer</option>
                                    @foreach(($executiveApprovers ?? collect()) as $approver)
                                        <option value="{{ $approver['id'] }}">
                                            {{ $approver['name'] }} — {{ $approver['position'] ?? 'Officer' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="rounded-lg border border-blue-100 bg-white p-3 text-sm">
                                <p class="text-xs font-bold uppercase text-blue-700 mb-2">From Executive Management</p>
                                <p><span class="font-semibold">Name:</span> <span x-text="previewExecutiveName || '—'"></span></p>
                                <p><span class="font-semibold">Position:</span> <span x-text="previewExecutivePosition || '—'"></span></p>
                                <p><span class="font-semibold">Office:</span> <span x-text="previewExecutiveDepartment || '—'"></span></p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Body</label>
                            <div id="editor" class="bg-white"></div>
                            <input type="hidden" id="detailsInput">
                        </div>

                        <div class="border rounded-md p-3 bg-gray-50">
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                                <input type="checkbox" x-model="hasDeadline" id="hasDeadlineInput" class="rounded border-gray-300" @change="if (!hasDeadline) { previewDeadline = ''; document.getElementById('deadlineInput').value = ''; }">
                                This correspondence has a response deadline
                            </label>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Respond Before</label>
                            <input id="deadlineInput" type="date" x-model="previewDeadline" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" :disabled="!hasDeadline" :class="!hasDeadline ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500'">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Sent Via</label>
                            <select id="sentViaInput" x-model="previewSentVia" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="Email">Email</option>
                                <option value="LBC">LBC</option>
                                <option value="Internal">Internal</option>
                                <option value="Hand Delivery">Hand Delivery</option>
                            </select>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 flex items-center gap-3">
                        <button type="button" @click="closeAddSectionAlpine()" class="flex-1 border border-gray-300 text-gray-700 rounded-lg py-2.5 text-sm font-medium hover:bg-gray-50 transition">
                            Cancel
                        </button>

                        <button id="saveCorrespondenceBtn" type="button" onclick="addCorrespondence().then(success => { if (success) { closeAddSection(); } })" class="flex-1 bg-blue-600 text-white rounded-lg py-2.5 text-sm font-medium hover:bg-blue-700 transition">
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>

    .correspondence-header {
        display: grid;
        grid-template-columns: 38% 62%;
        align-items: center;
        gap: 18px;
        margin-top: 8px;
    }

    .correspondence-header .header-logo {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 85px;
    }

    .correspondence-header .header-logo img {
        display: block;
        max-width: 190px;
        max-height: 95px;
        object-fit: contain;
    }

    .correspondence-header .header-company {
        text-align: left;
        font-size: 13px;
        line-height: 1.35;
        color: #000;
        overflow-wrap: break-word;
        word-break: normal;
    }

    .correspondence-header .company-name {
        font-weight: 700;
        text-transform: uppercase;
        font-size: 14px;
        margin-bottom: 2px;
    }

    .correspondence-a4-page {
        width: 210mm;
        min-height: 297mm;
        padding: 18mm 18mm 20mm 18mm;
        box-sizing: border-box;
        background: #fff;
        font-family: Georgia, "Times New Roman", serif;
    }

    .correspondence-body {
        min-height: 360px;
        line-height: 1.65;
    }

    .body-content,
    .body-content * {
        max-width: 100%;
        box-sizing: border-box;
        overflow-wrap: break-word;
        word-wrap: break-word;
        word-break: normal;
        white-space: normal;
        font-family: Georgia, "Times New Roman", serif;
    }

    .body-content p {
        margin: 0 0 14px 0;
        line-height: 1.65;
    }

    .body-content .ql-align-left { text-align: left !important; }
    .body-content .ql-align-center { text-align: center !important; }
    .body-content .ql-align-right { text-align: right !important; }
    .body-content .ql-align-justify { text-align: justify !important; }

    .body-content .ql-indent-1 { padding-left: 3em !important; }
    .body-content .ql-indent-2 { padding-left: 6em !important; }
    .body-content .ql-indent-3 { padding-left: 9em !important; }
    .body-content .ql-indent-4 { padding-left: 12em !important; }
    .body-content .ql-indent-5 { padding-left: 15em !important; }
    .body-content .ql-indent-6 { padding-left: 18em !important; }
    .body-content .ql-indent-7 { padding-left: 21em !important; }
    .body-content .ql-indent-8 { padding-left: 24em !important; }

    .body-content table {
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
        margin: 12px 0 !important;
    }

    .body-content td,
    .body-content th {
        border: 1px solid #9ca3af;
        padding: 8px 10px;
        vertical-align: top;
        overflow-wrap: break-word;
        word-break: normal;
    }

    #editor {
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        overflow: hidden;
    }

    #editor .ql-container.ql-snow {
        border: 0;
        font-size: 15px;
        min-height: 320px;
        font-family: Georgia, "Times New Roman", serif;
    }

    #editor .ql-editor {
        min-height: 320px;
        padding: 16px 18px;
        line-height: 1.65;
        overflow-wrap: break-word;
        word-wrap: break-word;
        font-family: Georgia, "Times New Roman", serif;
    }

    #editor .ql-editor table {
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
        margin: 12px 0 !important;
    }

    #editor .ql-editor td,
    #editor .ql-editor th {
        border: 1px solid #9ca3af;
        padding: 8px 10px;
        vertical-align: top;
        overflow-wrap: break-word;
        word-break: normal;
    }

    .ql-font-georgia,
    .ql-font-georgia * {
        font-family: Georgia, "Times New Roman", serif !important;
    }

    .ql-picker.ql-font .ql-picker-label[data-value="georgia"]::before,
    .ql-picker.ql-font .ql-picker-item[data-value="georgia"]::before {
        content: "Georgia";
        font-family: Georgia, "Times New Roman", serif;
    }

    /* Match Town Hall memo spacing */
    .correspondence-a4-page {
        padding: 18mm 18mm 20mm 18mm !important;
    }

    .correspondence-header {
        margin-top: 8px !important;
        margin-bottom: 38px !important;
    }

    .correspondence-header .header-logo img {
        max-width: 205px !important;
        max-height: 105px !important;
    }

    .correspondence-header .header-company {
        font-size: 13px !important;
        line-height: 1.25 !important;
    }

    .correspondence-fields {
        margin-top: 0 !important;
        margin-bottom: 22px !important;
    }

    .correspondence-fields .field-row {
        display: grid;
        grid-template-columns: 105px 1fr;
        column-gap: 10px;
        margin-bottom: 2px;
        line-height: 1.3;
        font-size: 14px;
    }

    .correspondence-fields .field-row p {
        margin: 0;
        padding: 0;
    }

    .correspondence-divider {
        border-top: 1px solid #6b7280;
        margin-top: 8px;
        margin-bottom: 22px;
    }

</style>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/quill-table-better@1/dist/quill-table-better.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill-table-better@1/dist/quill-table-better.js"></script>

<script>
let currentTypeFilter = "All";
let currentWorkflowFilter = "";
let correspondenceRows = [];

const correspondenceTypes = @json($types);
const managementApprovers = @json(($managementApprovers ?? collect())->values());
const executiveApprovers = @json(($executiveApprovers ?? collect())->values());

function formatDisplayDate(value) {
    if (!value) return '';

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString('en-US', {
        month: 'long',
        day: '2-digit',
        year: 'numeric'
    });
}

function slugifyType(type) {
    return String(type || 'other')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function getAlpineData() {
    const root = document.getElementById('correspondence-page');
    return root ? Alpine.$data(root) : null;
}

function showOnlySection(sectionId) {
    document.getElementById('tableSection').classList.add('hidden');
    document.getElementById('previewSection').classList.add('hidden');
    document.getElementById(sectionId).classList.remove('hidden');
}

function updateStatusMessage() {
    // Corporate side tabs were removed.
}

function setActiveTab() {
    // Admin side handles workflow tabs.
}

function showSliderError(message) {
    const box = document.getElementById('sliderErrorBox');
    const successBox = document.getElementById('sliderSuccessBox');
    successBox.classList.add('hidden');
    successBox.innerHTML = '';
    box.innerHTML = message;
    box.classList.remove('hidden');
}

function showSliderSuccess(message) {
    const box = document.getElementById('sliderSuccessBox');
    const errorBox = document.getElementById('sliderErrorBox');
    errorBox.classList.add('hidden');
    errorBox.innerHTML = '';
    box.innerHTML = message;
    box.classList.remove('hidden');
}

function clearSliderMessages() {
    ['sliderErrorBox', 'sliderSuccessBox'].forEach(id => {
        const box = document.getElementById(id);
        if (box) {
            box.classList.add('hidden');
            box.innerHTML = '';
        }
    });
}

function setSaveLoading(isLoading) {
    const btn = document.getElementById('saveCorrespondenceBtn');
    if (!btn) return;

    btn.disabled = isLoading;
    btn.classList.toggle('opacity-60', isLoading);
    btn.classList.toggle('cursor-not-allowed', isLoading);
    btn.textContent = isLoading ? 'Saving...' : 'Save';
}

function openAddSection() {
    const alpineData = getAlpineData();
    if (alpineData) alpineData.showSlideOver = true;
    resetFormDefaults();
    clearSliderMessages();
}

function closeAddSection() {
    const alpineData = getAlpineData();
    if (alpineData) alpineData.showSlideOver = false;
    resetFormDefaults();
    clearSliderMessages();
    showOnlySection('tableSection');
}

function resetFormDefaults() {
    const alpineData = getAlpineData();
    const today = new Date().toISOString().split('T')[0];

    document.getElementById('typeInput').value = 'Letters';
    document.getElementById('subjectInput').value = '';
    document.getElementById('toForLabelInput').value = 'To';
    document.getElementById('toForInput').value = '';
    document.getElementById('fromInput').value = '{{ Auth::user()->name ?? 'System Super Admin' }}';
    document.getElementById('departmentInput').value = '';
    document.getElementById('ccInput').value = '';
    document.getElementById('additionalInput').value = '';
    document.getElementById('managementApproverInput').value = '';
    document.getElementById('executiveApproverInput').value = '';
    document.getElementById('deadlineInput').value = '';
    document.getElementById('sentViaInput').value = 'Email';
    document.getElementById('hasDeadlineInput').checked = false;

    if (alpineData) {
        alpineData.hasDeadline = false;
        alpineData.previewRef = 'AUTO-INCREMENT';
        alpineData.previewDate = today;
        alpineData.previewType = 'Letters';
        alpineData.previewToForLabel = 'To';
        alpineData.previewToFor = '';
        alpineData.previewFrom = '{{ Auth::user()->name ?? 'System Super Admin' }}';
        alpineData.previewDepartment = '';
        alpineData.previewSubject = '';
        alpineData.previewBody = '<p style="color:#9ca3af;">Write the formal correspondence here...</p>';
        alpineData.previewDeadline = '';
        alpineData.previewSentVia = 'Email';
        alpineData.previewCc = '';
        alpineData.previewAdditional = '';
        alpineData.previewManagementApproverId = '';
        alpineData.previewManagementName = '';
        alpineData.previewManagementPosition = '';
        alpineData.previewManagementDepartment = '';
        alpineData.previewExecutiveApproverId = '';
        alpineData.previewExecutiveName = '';
        alpineData.previewExecutivePosition = '';
        alpineData.previewExecutiveDepartment = '';
    }

    if (window.correspondenceQuill) {
        window.correspondenceQuill.setContents([]);
    }

    document.getElementById('detailsInput').value = '';
}

function applyWorkflowFilter(filterValue) {
    // Workflow filtering is managed in the Admin Correspondence dashboard.
    currentWorkflowFilter = '';
    renderTable();
}

async function fetchCorrespondence() {
    const params = new URLSearchParams();

    if (currentTypeFilter !== 'All') {
        params.append('type', currentTypeFilter);
    }

    const res = await fetch(`/correspondence/data?${params.toString()}`, {
        headers: { 'Accept': 'application/json' }
    });

    return await res.json();
}

function getWorkflowClasses(status) {
    if (status === 'Submitted') return 'text-blue-600';
    if (status === 'Uploaded') return 'text-orange-600';
    if (status === 'Accepted') return 'text-green-600';
    if (status === 'Reverted') return 'text-yellow-600';
    if (status === 'Archived') return 'text-gray-600';
    return 'text-gray-500';
}

function getApprovalClasses(status) {
    if (status === 'Approved') return 'text-green-600';
    if (status === 'Rejected') return 'text-red-600';
    if (status === 'Needs Revision') return 'text-yellow-600';
    if (status === 'Pending') return 'text-blue-600';
    return 'text-gray-500';
}

function openPreview(index) {
    const item = correspondenceRows[index];
    if (!item) return;

    const previewUrl = `/correspondence/template/${slugifyType(item.type)}/${item.id}`;

    document.getElementById('previewFrame').src = previewUrl;
    document.getElementById('openPreviewBtn').href = previewUrl;
    document.getElementById('infoType').textContent = item.type ?? '';
    document.getElementById('infoCompany').textContent = item.company_name ?? '';
    document.getElementById('infoRegNo').textContent = item.registration_number ?? '';
    document.getElementById('infoSubject').textContent = item.subject ?? '';
    document.getElementById('infoToFor').textContent = `${item.to_for_label || 'To'}: ${item.to_for || 'N/A'}`;
    document.getElementById('infoFrom').textContent = item.from_name ?? 'N/A';
    document.getElementById('infoWorkflowStatus').textContent = item.workflow_status ?? '';
    document.getElementById('infoApprovalStatus').textContent = item.approval_status ?? '';
    document.getElementById('infoReviewNote').textContent = item.review_note ?? '—';

    const actions = document.getElementById('previewActions');
    actions.innerHTML = `<a id="openPreviewBtn" href="${previewUrl}" target="_blank" class="text-sm text-blue-600 hover:underline block">Open in New Tab</a>`;

    actions.innerHTML += `
        <a href="/correspondence/${item.id}/download-pdf" class="block w-full text-center bg-red-600 text-white rounded-md py-2 hover:bg-red-700">
            Download PDF
        </a>
    `;

    showOnlySection('previewSection');
}

function closePreview() {
    document.getElementById('previewFrame').src = '';
    showOnlySection('tableSection');
}

async function renderTable() {
    closePreview();

    const tableBody = document.getElementById('tableBody');
    tableBody.innerHTML = '';

    const data = await fetchCorrespondence();
    correspondenceRows = data || [];

    updateStatusMessage();
    setActiveTab();

    if (!correspondenceRows.length) {
        tableBody.innerHTML = `<tr><td colspan="11" class="px-3 py-8 text-center text-gray-500">No correspondence records found.</td></tr>`;
        return;
    }

    correspondenceRows.forEach((item, index) => {
        tableBody.innerHTML += `
            <tr class="border-t border-gray-200 hover:bg-gray-50 cursor-pointer" onclick="openPreview(${index})">
                <td class="px-3 py-3 border-r border-gray-200">${item.ref_no ?? `COR-${String(item.id).padStart(5, '0')}`}</td>
                <td class="px-3 py-3 border-r border-gray-200">${item.date ?? ''}</td>
                <td class="px-3 py-3 border-r border-gray-200">${item.type ?? ''}</td>
                <td class="px-3 py-3 border-r border-gray-200">${item.company_name ?? ''}</td>
                <td class="px-3 py-3 border-r border-gray-200">${item.to_for ?? ''}</td>
                <td class="px-3 py-3 border-r border-gray-200">${item.from_name ?? ''}</td>
                <td class="px-3 py-3 border-r border-gray-200">${item.subject ?? ''}</td>
                <td class="px-3 py-3 border-r border-gray-200">${item.deadline ?? 'No Deadline'}</td>
                <td class="px-3 py-3 border-r border-gray-200 ${getWorkflowClasses(item.workflow_status)} font-medium">${item.workflow_status ?? ''}</td>
                <td class="px-3 py-3 border-r border-gray-200 ${getApprovalClasses(item.approval_status)} font-medium">${item.approval_status ?? ''}</td>
                <td class="px-3 py-3">
                    <button type="button" onclick="event.stopPropagation(); openPreview(${index})" class="text-blue-600 hover:underline">View</button>
                </td>
            </tr>
        `;
    });
}

async function addCorrespondence() {
    clearSliderMessages();
    setSaveLoading(true);

    const payload = {
        type: document.getElementById('typeInput').value,
        to_for_label: document.getElementById('toForLabelInput').value,
        tin: '',
        subject: document.getElementById('subjectInput').value,
        to_for: document.getElementById('toForInput').value,
        from_name: document.getElementById('fromInput').value,
        department_stakeholder: document.getElementById('departmentInput').value,
        body: document.getElementById('detailsInput').value,
        cc: document.getElementById('ccInput').value,
        additional: document.getElementById('additionalInput').value,
        deadline: document.getElementById('hasDeadlineInput').checked ? document.getElementById('deadlineInput').value : null,
        sent_via: document.getElementById('sentViaInput').value,
        management_approver_id: document.getElementById('managementApproverInput').value,
        executive_approver_id: document.getElementById('executiveApproverInput').value,
    };

    if (!payload.type || !payload.subject || !payload.to_for || !payload.management_approver_id || !payload.executive_approver_id) {
        showSliderError('Please fill in Correspondence Type, To / For, Subject, Level 1 Approver, and Level 2 Approver.');
        setSaveLoading(false);
        return false;
    }

    try {
        const res = await fetch('/correspondence', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            if (data.errors) {
                showSliderError(Object.values(data.errors).flat().join('<br>'));
            } else {
                showSliderError(data.message || 'Failed to save correspondence.');
            }

            setSaveLoading(false);
            return false;
        }

        showSliderSuccess(data.message || 'Correspondence submitted successfully.');
        await renderTable();
        setSaveLoading(false);
        return true;
    } catch (error) {
        showSliderError('Something went wrong while saving.');
        setSaveLoading(false);
        return false;
    }
}

async function submitCorrespondence(id) {
    const res = await fetch(`/correspondence/${id}/submit`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    });

    const data = await res.json();

    if (!res.ok) {
        alert(data.message || 'Unable to submit record.');
        return;
    }

    alert(data.message || 'Submitted successfully.');
    closePreview();
    await renderTable();
}

document.addEventListener('DOMContentLoaded', function () {
    const editorEl = document.getElementById('editor');
    const hiddenInput = document.getElementById('detailsInput');
    const rootEl = document.getElementById('correspondence-page');

    if (editorEl && hiddenInput && rootEl && window.Quill && window.QuillTableBetter) {
        const Font = Quill.import('formats/font');
        Font.whitelist = ['georgia', 'serif', 'sans-serif', 'monospace'];
        Quill.register(Font, true);

        Quill.register({
            'modules/table-better': QuillTableBetter
        }, true);

        const quill = new Quill('#editor', {
            theme: 'snow',
            placeholder: 'Write the formal correspondence here...',
            modules: {
                toolbar: [
                    [{ font: ['georgia', 'serif', 'sans-serif', 'monospace'] }, { size: ['small', false, 'large', 'huge'] }],
                    [{ header: [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ script: 'sub' }, { script: 'super' }],
                    [{ color: [] }, { background: [] }],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ indent: '-1' }, { indent: '+1' }],
                    [{ align: [] }],
                    ['blockquote', 'link'],
                    ['table-better'],
                    ['clean']
                ],
                table: false,
                'table-better': {
                    language: 'en_US',
                    menus: ['column', 'row', 'merge', 'table', 'cell', 'wrap', 'copy', 'delete'],
                    toolbarTable: true
                },
                keyboard: {
                    bindings: {
                        ...QuillTableBetter.keyboardBindings,
                        correspondenceTabIndent: {
                            key: 9,
                            handler: function(range) {
                                if (range) this.quill.format('indent', '+1', Quill.sources.USER);
                                return false;
                            }
                        },
                        correspondenceShiftTabOutdent: {
                            key: 9,
                            shiftKey: true,
                            handler: function(range) {
                                if (range) this.quill.format('indent', '-1', Quill.sources.USER);
                                return false;
                            }
                        }
                    }
                }
            }
        });

        quill.root.style.fontFamily = 'Georgia, "Times New Roman", serif';
        quill.format('font', 'georgia');

        window.correspondenceQuill = quill;

        const alpineData = Alpine.$data(rootEl);

        quill.on('text-change', function () {
            const html = quill.root.innerHTML;
            const plainText = quill.getText().trim();
            const hasTable = !!quill.root.querySelector('table');

            hiddenInput.value = (plainText || hasTable) ? html : '';

            if (alpineData) {
                alpineData.previewBody = (plainText || hasTable)
                    ? html
                    : '<p style="color:#9ca3af;">Write the formal correspondence here...</p>';
            }
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const alpineData = getAlpineData();
            if (alpineData?.showSlideOver) {
                closeAddSection();
            } else {
                closePreview();
            }
        }
    });

    renderTable();
});
</script>
@endpush

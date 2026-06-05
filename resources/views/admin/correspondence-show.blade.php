@extends('layouts.app')
@section('title', 'Correspondence Details')

@php
    $types = $types ?? [
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

    $openEditor = session('open_revision_editor') || session('open_edit_editor') || $errors->any();

    $previewLogo = $correspondenceLogoUrl ?? asset('images/jk-logo.png');
    $previewBody = $correspondence->body ?: '<p style="color:#9ca3af;">Write the formal correspondence here...</p>';
@endphp

@section('content')
<div
    id="admin-correspondence-page"
    class="w-full px-6 py-5"
    x-data="{
        editMode: @js((bool) $openEditor),

        correspondenceLogoUrl: @js($previewLogo),

        previewType: @js($correspondence->type ?: 'Letters'),
        previewDate: @js(optional($correspondence->correspondence_date)->format('Y-m-d') ?: now()->format('Y-m-d')),
        previewCompanyName: @js($correspondence->company_name ?: ''),
        previewRegistrationNumber: @js($correspondence->registration_number ?: ''),
        previewPrincipalAddress: @js($correspondence->principal_address ?: ''),
        previewToForLabel: @js($correspondence->to_for_label ?: 'To'),
        previewToFor: @js($correspondence->to_for ?: ''),
        previewFrom: @js($correspondence->from_name ?: ''),
        previewSubject: @js($correspondence->subject ?: ''),
        previewDepartment: @js($correspondence->department_stakeholder ?: ''),
        previewCc: @js($correspondence->cc ?: ''),
        previewAdditional: @js($correspondence->additional ?: ''),
        previewDeadline: @js(optional($correspondence->deadline)->format('Y-m-d') ?: ''),
        previewSentVia: @js($correspondence->sent_via ?: 'Email'),
        previewBody: @js($previewBody),

        previewPreparedByName: @js($correspondence->prepared_by_name ?: ($correspondence->from_name ?: '')),
        previewPreparedByPosition: @js($correspondence->prepared_by_position ?: ''),
        previewPreparedByDepartment: @js($correspondence->prepared_by_department ?: ''),
        previewPreparedOn: @js(optional($correspondence->prepared_on)->format('Y-m-d\TH:i') ?: ''),

        previewManagementApproverId: @js((string) ($correspondence->management_approver_id ?: '')),
        previewManagementName: @js($correspondence->management_approver_name ?: ''),
        previewManagementPosition: @js($correspondence->management_approver_position ?: ''),
        previewManagementDepartment: @js($correspondence->management_approver_department ?: ''),
        previewManagementSignatureName: @js($correspondence->management_signature_name ?: ($correspondence->management_approver_name ?: '')),
        previewManagementSignaturePosition: @js($correspondence->management_signature_position ?: ($correspondence->management_approver_position ?: '')),
        previewManagementSignatureDepartment: @js($correspondence->management_signature_department ?: ($correspondence->management_approver_department ?: '')),
        previewManagementApprovedOn: @js(optional($correspondence->management_approved_on)->format('Y-m-d\TH:i') ?: ''),

        previewExecutiveApproverId: @js((string) ($correspondence->executive_approver_id ?: '')),
        previewExecutiveName: @js($correspondence->executive_approver_name ?: ''),
        previewExecutivePosition: @js($correspondence->executive_approver_position ?: ''),
        previewExecutiveDepartment: @js($correspondence->executive_approver_department ?: ''),
        previewExecutiveSignatureName: @js($correspondence->executive_signature_name ?: ($correspondence->executive_approver_name ?: '')),
        previewExecutiveSignaturePosition: @js($correspondence->executive_signature_position ?: ($correspondence->executive_approver_position ?: '')),
        previewExecutiveSignatureDepartment: @js($correspondence->executive_signature_department ?: ($correspondence->executive_approver_department ?: '')),
        previewExecutiveApprovedOn: @js(optional($correspondence->executive_approved_on)->format('Y-m-d\TH:i') ?: ''),

        openEdit() {
            this.editMode = true;
            setTimeout(() => initAdminCorrespondenceEditQuill(), 100);
        },

        closeEdit() {
            this.editMode = false;
        },

        syncManagementApprover() {
            const selected = managementApprovers.find(item => String(item.id) === String(this.previewManagementApproverId));

            this.previewManagementName = selected?.name || '';
            this.previewManagementPosition = selected?.position || '';
            this.previewManagementDepartment = selected?.department || '';

            this.previewManagementSignatureName = selected?.name || '';
            this.previewManagementSignaturePosition = selected?.position || '';
            this.previewManagementSignatureDepartment = selected?.department || '';
        },

        syncExecutiveApprover() {
            const selected = executiveApprovers.find(item => String(item.id) === String(this.previewExecutiveApproverId));

            this.previewExecutiveName = selected?.name || '';
            this.previewExecutivePosition = selected?.position || '';
            this.previewExecutiveDepartment = selected?.department || '';

            this.previewExecutiveSignatureName = selected?.name || '';
            this.previewExecutiveSignaturePosition = selected?.position || '';
            this.previewExecutiveSignatureDepartment = selected?.department || '';
        }
    }"
>
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('admin.correspondence.dashboard') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            ← Back
        </a>

        <div class="flex items-center gap-2">
            <button
                type="button"
                @click="openEdit()"
                class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
            >
                Edit
            </button>

            <a href="{{ route('correspondence.download', $correspondence->id) }}" class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                Download PDF
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($correspondence->review_note)
        <div class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
            <strong>Revision Note:</strong> {{ $correspondence->review_note }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold mb-1">Please fix the following:</p>
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_390px] gap-6">
        <div class="rounded-xl border border-gray-200 bg-gray-100 p-6 overflow-auto min-h-[860px]">
            <iframe
                src="{{ route('correspondence.template', ['type' => strtolower(str_replace(' ', '-', $correspondence->type ?: 'other')), 'id' => $correspondence->id]) }}"
                class="w-full min-h-[860px] rounded-lg border border-gray-300 bg-white"
                frameborder="0"
            ></iframe>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 h-fit sticky top-6">
            <div class="flex items-center justify-between gap-3 mb-5">
                <h2 class="text-xl font-bold text-gray-900">Correspondence Details</h2>

                <button
                    type="button"
                    @click="openEdit()"
                    class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                >
                    Edit
                </button>
            </div>

            <div class="space-y-3 text-sm">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-gray-500">Ref</p>
                        <p class="font-medium">{{ $correspondence->ref_no ?: 'COR-' . str_pad($correspondence->id, 5, '0', STR_PAD_LEFT) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Approval</p>
                        <p class="font-medium">{{ $correspondence->approval_status ?: '—' }}</p>
                    </div>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Subject</p>
                    <p class="font-medium break-words">{{ $correspondence->subject ?: '—' }}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Company</p>
                    <p class="font-medium break-words">{{ $correspondence->company_name ?: '—' }}</p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <p class="text-xs font-bold uppercase text-gray-700 mb-2">Prepared By</p>
                    <p><strong>Name:</strong> {{ $correspondence->prepared_by_name ?: ($correspondence->from_name ?: '—') }}</p>
                    <p><strong>Position:</strong> {{ $correspondence->prepared_by_position ?: '—' }}</p>
                    <p><strong>Department:</strong> {{ $correspondence->prepared_by_department ?: '—' }}</p>
                    <p><strong>Prepared On:</strong> {{ optional($correspondence->prepared_on ?: $correspondence->created_at)->format('M d, Y h:i A') ?: '—' }}</p>
                </div>

                <div class="rounded-lg border border-blue-100 bg-blue-50 p-3">
                    <p class="text-xs font-bold uppercase text-blue-700 mb-2">Level 1 - From Management</p>
                    <p><strong>Name:</strong> {{ $correspondence->management_signature_name ?: ($correspondence->management_approver_name ?: '—') }}</p>
                    <p><strong>Position:</strong> {{ $correspondence->management_signature_position ?: ($correspondence->management_approver_position ?: '—') }}</p>
                    <p><strong>Department:</strong> {{ $correspondence->management_signature_department ?: ($correspondence->management_approver_department ?: '—') }}</p>
                    <p><strong>Approved On:</strong> {{ optional($correspondence->management_approved_on ?: $correspondence->management_approved_at)->format('M d, Y h:i A') ?: '—' }}</p>
                    <p><strong>Status:</strong> {{ $correspondence->management_approval_status ?: '—' }}</p>
                </div>

                <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-3">
                    <p class="text-xs font-bold uppercase text-indigo-700 mb-2">Level 2 - From Executive Management</p>
                    <p><strong>Name:</strong> {{ $correspondence->executive_signature_name ?: ($correspondence->executive_approver_name ?: '—') }}</p>
                    <p><strong>Position:</strong> {{ $correspondence->executive_signature_position ?: ($correspondence->executive_approver_position ?: '—') }}</p>
                    <p><strong>Department / Office:</strong> {{ $correspondence->executive_signature_department ?: ($correspondence->executive_approver_department ?: '—') }}</p>
                    <p><strong>Approved On:</strong> {{ optional($correspondence->executive_approved_on ?: $correspondence->executive_approved_at)->format('M d, Y h:i A') ?: '—' }}</p>
                    <p><strong>Status:</strong> {{ $correspondence->executive_approval_status ?: '—' }}</p>
                </div>
            </div>

            <div class="mt-6 space-y-3">
                @if(!$correspondence->is_archived)
                    @if($correspondence->approval_status !== 'Approved')
                        <form method="POST" action="{{ route('correspondence.approve', $correspondence->id) }}">
                            @csrf
                            <button type="submit" class="w-full rounded-lg bg-green-600 py-2.5 text-sm font-semibold text-white hover:bg-green-700">
                                Approve Current Level
                            </button>
                        </form>

                        <form method="POST" action="{{ route('correspondence.revise', $correspondence->id) }}">
                            @csrf
                            <textarea name="review_note" rows="2" class="mb-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Revision note">{{ old('review_note', $correspondence->review_note ?: 'Needs revision.') }}</textarea>
                            <button type="submit" class="w-full rounded-lg border border-yellow-300 bg-yellow-50 py-2.5 text-sm font-semibold text-yellow-700 hover:bg-yellow-100">
                                Revise
                            </button>
                        </form>

                        <form method="POST" action="{{ route('correspondence.reject', $correspondence->id) }}">
                            @csrf
                            <textarea name="review_note" rows="2" class="mb-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Rejection note">Rejected.</textarea>
                            <button type="submit" class="w-full rounded-lg bg-red-600 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                                Reject
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('correspondence.archive', $correspondence->id) }}">
                        @csrf
                        <button type="submit" class="w-full rounded-lg border border-gray-300 bg-white py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Archive
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Town Hall/Add Correspondence style edit modal: live A4 preview left, editor/form right --}}
    <div x-show="editMode" x-cloak class="fixed inset-0 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/40" @click="closeEdit()"></div>

        <div class="absolute inset-0 flex">
            <div
                x-show="editMode"
                x-transition:enter="transform transition ease-in-out duration-300"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="w-[70%] h-full bg-[#f5f6f8] overflow-y-auto p-6 border-r border-gray-200"
            >
                <div class="max-w-[900px] mx-auto flex justify-center">
                    <div class="correspondence-a4-page bg-white border border-gray-300 shadow">
                        <div class="correspondence-header mb-12">
                            <div class="header-logo">
                                <img :src="correspondenceLogoUrl" alt="Company Logo">
                            </div>

                            <div class="header-company">
                                <p class="company-name" x-text="previewCompanyName || 'COMPANY NAME'"></p>
                                <p>Registration No.: <span x-text="previewRegistrationNumber || '____________________'"></span></p>
                                <p x-text="previewPrincipalAddress || 'Principal Office Address'"></p>
                            </div>
                        </div>

                        <div class="text-center mb-10">
                            <h2 class="text-[24px] font-bold uppercase tracking-[0.14em]" x-text="previewType || 'CORRESPONDENCE'"></h2>
                        </div>

                        <div class="correspondence-fields text-gray-900">
                            <div class="field-row">
                                <p class="font-semibold">Date:</p>
                                <p class="pb-1" x-text="formatCorrespondenceDisplayDate(previewDate) || '______________________'"></p>
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

                        <div class="correspondence-signature-block">
                            <div class="signature-section">
                                <p class="signature-heading">Prepared By:</p>
                                <p class="signature-line" x-text="previewPreparedByName || previewFrom || 'System Super Admin'"></p>
                                <p class="signature-line" x-text="previewPreparedByPosition || 'Position'"></p>
                                <p class="signature-line" x-text="previewPreparedByDepartment || '—'"></p>
                                <p class="signature-line">Prepared on: <span x-text="formatCorrespondenceDateTime(previewPreparedOn) || 'Date and Time'"></span></p>
                            </div>

                            <div class="signature-section">
                                <p class="signature-heading">From Management</p>
                                <p class="signature-line" x-text="previewManagementSignatureName || previewManagementName || 'Name'"></p>
                                <p class="signature-line" x-text="previewManagementSignaturePosition || previewManagementPosition || 'Position'"></p>
                                <p class="signature-line" x-text="previewManagementSignatureDepartment || previewManagementDepartment || 'Department'"></p>
                                <p class="signature-line">Approved on: <span x-text="formatCorrespondenceDateTime(previewManagementApprovedOn) || 'Date and Time'"></span></p>
                            </div>

                            <div class="signature-section">
                                <p class="signature-heading">From Executive Management</p>
                                <p class="signature-line" x-text="previewExecutiveSignatureName || previewExecutiveName || 'Name'"></p>
                                <p class="signature-line" x-text="previewExecutiveSignaturePosition || previewExecutivePosition || 'Position'"></p>
                                <p class="signature-line" x-text="previewExecutiveSignatureDepartment || previewExecutiveDepartment || 'Executive Management'"></p>
                                <p class="signature-line">Approved on: <span x-text="formatCorrespondenceDateTime(previewExecutiveApprovedOn) || 'Date and Time'"></span></p>
                            </div>

                            <p class="computer-generated-note">
                                This is a computer-generated document. Signature is not required.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div
                x-show="editMode"
                x-transition:enter="transform transition ease-in-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="w-[30%] h-full bg-white shadow-2xl flex flex-col"
            >
                <div class="px-6 py-4 border-b border-gray-200 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Edit Correspondence</h2>
                        <p class="text-sm text-gray-500 mt-1">Resubmit for approval after updating.</p>
                    </div>

                    <button type="button" @click="closeEdit()" class="text-gray-400 hover:text-gray-700 text-lg">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form
                    method="POST"
                    action="{{ route('admin.correspondence.revise-update', $correspondence->id) }}"
                    class="flex-1 overflow-y-auto px-6 py-5 space-y-4"
                    onsubmit="syncAdminCorrespondenceEditBody()"
                >
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Correspondence Type</label>
                        <select name="type" x-model="previewType" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                            @foreach($types as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Date</label>
                        <input type="date" name="correspondence_date" x-model="previewDate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                    </div>

                    <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm space-y-3">
                        <p class="font-semibold text-blue-700">Header details</p>

                        <div>
                            <label class="block text-xs font-semibold text-blue-700 mb-1">Company Name</label>
                            <input name="company_name" x-model="previewCompanyName" class="w-full border border-blue-200 rounded-lg px-3 py-2 text-sm bg-white">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-blue-700 mb-1">Registration Number</label>
                            <input name="registration_number" x-model="previewRegistrationNumber" class="w-full border border-blue-200 rounded-lg px-3 py-2 text-sm bg-white">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-blue-700 mb-1">Principal Address</label>
                            <textarea name="principal_address" rows="2" x-model="previewPrincipalAddress" class="w-full border border-blue-200 rounded-lg px-3 py-2 text-sm bg-white"></textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Subject</label>
                        <input name="subject" x-model="previewSubject" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Enter subject">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">To / For</label>
                        <div class="grid grid-cols-[110px_1fr] gap-3">
                            <select name="to_for_label" x-model="previewToForLabel" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                                <option value="To">To</option>
                                <option value="For">For</option>
                            </select>

                            <input name="to_for" x-model="previewToFor" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Recipient">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">From</label>
                        <input name="from_name" x-model="previewFrom" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Sender">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Department / Stakeholder</label>
                        <input name="department_stakeholder" x-model="previewDepartment" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Department">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">CC</label>
                            <input name="cc" x-model="previewCc" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Additional</label>
                            <input name="additional" x-model="previewAdditional" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-3">
                        <p class="text-xs font-bold uppercase text-gray-700">Prepared By Details</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <input name="prepared_by_name" x-model="previewPreparedByName" placeholder="Prepared By Name" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <input name="prepared_by_position" x-model="previewPreparedByPosition" placeholder="Position" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <input name="prepared_by_department" x-model="previewPreparedByDepartment" placeholder="Department" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <input type="datetime-local" name="prepared_on" x-model="previewPreparedOn" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 space-y-3">
                        <p class="text-xs font-bold uppercase text-blue-700">From Management</p>

                        <select name="management_approver_id" x-model="previewManagementApproverId" @change="syncManagementApprover()" class="w-full border border-blue-200 rounded-lg px-3 py-2 text-sm bg-white">
                            <option value="">Select Level 1 approver</option>
                            @foreach(($managementApprovers ?? collect()) as $approver)
                                <option value="{{ $approver['id'] }}">{{ $approver['name'] }} — {{ $approver['position'] ?? 'Management' }}</option>
                            @endforeach
                        </select>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <input name="management_signature_name" x-model="previewManagementSignatureName" placeholder="Level 1 Name" class="rounded-lg border border-blue-200 px-3 py-2 text-sm">
                            <input name="management_signature_position" x-model="previewManagementSignaturePosition" placeholder="Position" class="rounded-lg border border-blue-200 px-3 py-2 text-sm">
                            <input name="management_signature_department" x-model="previewManagementSignatureDepartment" placeholder="Department" class="rounded-lg border border-blue-200 px-3 py-2 text-sm">
                            <input type="datetime-local" name="management_approved_on" x-model="previewManagementApprovedOn" class="rounded-lg border border-blue-200 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-4 space-y-3">
                        <p class="text-xs font-bold uppercase text-indigo-700">From Executive Management</p>

                        <select name="executive_approver_id" x-model="previewExecutiveApproverId" @change="syncExecutiveApprover()" class="w-full border border-indigo-200 rounded-lg px-3 py-2 text-sm bg-white">
                            <option value="">Select Level 2 approver</option>
                            @foreach(($executiveApprovers ?? collect()) as $approver)
                                <option value="{{ $approver['id'] }}">{{ $approver['name'] }} — {{ $approver['position'] ?? 'Officer' }}</option>
                            @endforeach
                        </select>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <input name="executive_signature_name" x-model="previewExecutiveSignatureName" placeholder="Level 2 Name" class="rounded-lg border border-indigo-200 px-3 py-2 text-sm">
                            <input name="executive_signature_position" x-model="previewExecutiveSignaturePosition" placeholder="Position" class="rounded-lg border border-indigo-200 px-3 py-2 text-sm">
                            <input name="executive_signature_department" x-model="previewExecutiveSignatureDepartment" placeholder="Department / Office" class="rounded-lg border border-indigo-200 px-3 py-2 text-sm">
                            <input type="datetime-local" name="executive_approved_on" x-model="previewExecutiveApprovedOn" class="rounded-lg border border-indigo-200 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Body</label>
                        <div id="adminCorrespondenceEditEditor" class="bg-white"></div>
                        <input type="hidden" name="body" id="adminCorrespondenceEditBodyInput" value="{{ e($correspondence->body) }}">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Respond Before</label>
                            <input type="date" name="deadline" x-model="previewDeadline" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Sent Via</label>
                            <select name="sent_via" x-model="previewSentVia" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                                @foreach(['Email', 'LBC', 'Internal', 'Hand Delivery'] as $via)
                                    <option value="{{ $via }}">{{ $via }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Review Note</label>
                        <textarea name="review_note" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('review_note', $correspondence->review_note) }}</textarea>
                    </div>

                    <div class="sticky bottom-0 -mx-6 -mb-5 mt-6 border-t border-gray-200 bg-white px-6 py-4 flex gap-3">
                        <button type="button" @click="closeEdit()" class="flex-1 border border-gray-300 text-gray-700 rounded-lg py-2.5 text-sm font-medium hover:bg-gray-50 transition">
                            Cancel
                        </button>

                        <button type="submit" class="flex-1 bg-blue-600 text-white rounded-lg py-2.5 text-sm font-medium hover:bg-blue-700 transition">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .correspondence-a4-page {
        width: 210mm;
        min-height: 297mm;
        padding: 18mm 18mm 20mm 18mm;
        box-sizing: border-box;
        background: #fff;
        font-family: Georgia, "Times New Roman", serif;
    }

    .correspondence-header {
        display: grid;
        grid-template-columns: 38% 62%;
        align-items: center;
        gap: 18px;
        margin-top: 8px;
        margin-bottom: 38px;
    }

    .correspondence-header .header-logo {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 85px;
    }

    .correspondence-header .header-logo img {
        display: block;
        max-width: 205px;
        max-height: 105px;
        object-fit: contain;
    }

    .correspondence-header .header-company {
        text-align: left;
        font-size: 13px;
        line-height: 1.25;
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

    .correspondence-fields {
        margin-top: 0;
        margin-bottom: 22px;
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

    .ql-font-georgia,
    .ql-font-georgia * {
        font-family: Georgia, "Times New Roman", serif !important;
    }

    .ql-picker.ql-font .ql-picker-label[data-value="georgia"]::before,
    .ql-picker.ql-font .ql-picker-item[data-value="georgia"]::before {
        content: "Georgia";
        font-family: Georgia, "Times New Roman", serif;
    }

    #adminCorrespondenceEditEditor {
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        overflow: hidden;
    }

    #adminCorrespondenceEditEditor .ql-container.ql-snow {
        border: 0;
        font-size: 15px;
        min-height: 320px;
        font-family: Georgia, "Times New Roman", serif;
    }

    #adminCorrespondenceEditEditor .ql-editor {
        min-height: 320px;
        padding: 16px 18px;
        line-height: 1.65;
        overflow-wrap: break-word;
        word-wrap: break-word;
        font-family: Georgia, "Times New Roman", serif;
    }

    .correspondence-signature-block {
        margin-top: 46px !important;
        font-size: 14px !important;
        line-height: 1.28 !important;
        color: #000 !important;
        font-family: Georgia, "Times New Roman", serif !important;
    }

    .correspondence-signature-block .signature-section {
        display: block !important;
        margin: 0 0 20px 0 !important;
        padding: 0 !important;
    }

    .correspondence-signature-block .signature-heading {
        display: block !important;
        margin: 0 0 8px 0 !important;
        padding: 0 !important;
        font-weight: 700 !important;
        line-height: 1.28 !important;
    }

    .correspondence-signature-block .signature-line {
        display: block !important;
        margin: 0 0 2px 0 !important;
        padding: 0 !important;
        font-weight: 400 !important;
        line-height: 1.28 !important;
    }

    .computer-generated-note {
        display: block !important;
        margin: 24px 0 0 0 !important;
        padding: 0 !important;
        font-weight: 700 !important;
        line-height: 1.28 !important;
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
const managementApprovers = @json(($managementApprovers ?? collect())->values());
const executiveApprovers = @json(($executiveApprovers ?? collect())->values());
let adminCorrespondenceEditQuill = null;

function getAdminCorrespondenceAlpineData() {
    const root = document.getElementById('admin-correspondence-page');
    return root ? Alpine.$data(root) : null;
}

function formatCorrespondenceDisplayDate(value) {
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

function formatCorrespondenceDateTime(value) {
    if (!value) return '';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString('en-US', {
        month: 'long',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function initAdminCorrespondenceEditQuill() {
    const editorEl = document.getElementById('adminCorrespondenceEditEditor');
    const hiddenInput = document.getElementById('adminCorrespondenceEditBodyInput');
    const alpineData = getAdminCorrespondenceAlpineData();

    if (!editorEl || !hiddenInput || adminCorrespondenceEditQuill || !window.Quill) {
        return;
    }

    if (window.QuillTableBetter) {
        Quill.register({
            'modules/table-better': QuillTableBetter
        }, true);
    }

    const Font = Quill.import('formats/font');
    Font.whitelist = ['georgia', 'serif', 'sans-serif', 'monospace'];
    Quill.register(Font, true);

    adminCorrespondenceEditQuill = new Quill('#adminCorrespondenceEditEditor', {
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
                ...(window.QuillTableBetter ? [['table-better']] : []),
                ['clean']
            ],
            table: false,
            ...(window.QuillTableBetter ? {
                'table-better': {
                    language: 'en_US',
                    menus: ['column', 'row', 'merge', 'table', 'cell', 'wrap', 'copy', 'delete'],
                    toolbarTable: true
                }
            } : {}),
            keyboard: window.QuillTableBetter ? { bindings: QuillTableBetter.keyboardBindings } : {}
        }
    });

    adminCorrespondenceEditQuill.root.style.fontFamily = 'Georgia, "Times New Roman", serif';
    adminCorrespondenceEditQuill.format('font', 'georgia');

    const existingHtml = hiddenInput.value || '';

    if (existingHtml) {
        adminCorrespondenceEditQuill.clipboard.dangerouslyPasteHTML(existingHtml);
    }

    adminCorrespondenceEditQuill.on('text-change', function () {
        const html = adminCorrespondenceEditQuill.root.innerHTML;
        const plainText = adminCorrespondenceEditQuill.getText().trim();
        const hasTable = !!adminCorrespondenceEditQuill.root.querySelector('table');

        hiddenInput.value = (plainText || hasTable) ? html : '';

        if (alpineData) {
            alpineData.previewBody = (plainText || hasTable)
                ? html
                : '<p style="color:#9ca3af;">Write the formal correspondence here...</p>';
        }
    });
}

function syncAdminCorrespondenceEditBody() {
    const hiddenInput = document.getElementById('adminCorrespondenceEditBodyInput');

    if (hiddenInput && adminCorrespondenceEditQuill) {
        hiddenInput.value = adminCorrespondenceEditQuill.root.innerHTML;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const alpineData = getAdminCorrespondenceAlpineData();

    if (alpineData?.editMode) {
        setTimeout(() => initAdminCorrespondenceEditQuill(), 100);
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const data = getAdminCorrespondenceAlpineData();
            if (data?.editMode) {
                data.editMode = false;
            }
        }
    });
});
</script>
@endpush

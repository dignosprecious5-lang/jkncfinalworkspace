@extends('layouts.app')
@section('title', 'Edit Town Hall')

@section('content')
<div id="townhall-edit-page" class="w-full h-full px-6 py-5" x-data="{
    previewRef: '{{ $communication->ref_no }}',
    previewDate: @js(old('communication_date', $communication->communication_date)),
    previewFrom: @js($communication->from_name),
    previewDepartment: @js(old('department_stakeholder', $communication->department_stakeholder)),
    previewRecipientLabel: @js(old('recipient_label', $communication->recipient_label ?? 'To')),
    previewRecipientType: @js(old('recipient_type', $communication->recipient_type ?? 'all')),
    previewRecipientUserIds: @js(old('recipient_user_ids', $communication->recipient_user_ids ?? [])),
    previewRecipientContactIds: @js(old('recipient_contact_ids', $communication->recipient_contact_ids ?? [])),
    usersForRecipient: @js($usersForRecipients->map(fn($user) => [
        'id' => $user->id,
        'name' => $user->name,
        'role' => $user->role,
    ])->values()),
    contactsForRecipient: @js($contactsForRecipients->map(function ($contact) {
        $name = trim(collect([
            $contact->first_name,
            $contact->middle_name,
            $contact->last_name,
            $contact->name_extension,
        ])->filter()->implode(' ')) ?: $contact->company_name;

        return [
            'id' => $contact->id,
            'name' => $name,
            'role' => 'Client',
        ];
    })->values()),
    managementApprovers: @js($managementApprovers ?? []),
    previewManagementApproverId: @js(old('management_approver_id', $communication->management_approver_id ?? '')),
    previewManagementName: @js($communication->management_approver_name ?? ''),
    previewManagementPosition: @js($communication->management_approver_position ?? ''),
    previewManagementDepartment: @js($communication->management_approver_department ?? ''),
    previewExecutiveName: @js(($communication->executive_approver_name ?? null) ?: ($executiveApprover['name'] ?? 'John Kelly D. Abalde')),
    previewExecutivePosition: @js(($communication->executive_approver_position ?? null) ?: ($executiveApprover['position'] ?? 'President and CEO')),
    previewExecutiveDepartment: @js(($communication->executive_approver_department ?? null) ?: ($executiveApprover['department'] ?? 'Executive Management')),
    previewTo: @js(old('to_for', $communication->to_for ?? 'All Employees')),
    previewPriority: @js(old('priority', $communication->priority ?? 'Low')),
    previewSubject: @js(old('subject', $communication->subject)),
    previewBody: @js(old('message', $communication->message ?: '<p style=&quot;color:#9ca3af;&quot;>Write the formal communication here...</p>')),
    previewCc: @js(old('cc', $communication->cc)),
    previewAdditional: @js(old('additional', $communication->additional)),
    syncManagementApprover() {
        const approver = this.managementApprovers.find(item => String(item.id) === String(this.previewManagementApproverId));

        this.previewManagementName = approver ? approver.name : '';
        this.previewManagementPosition = approver ? approver.position : '';
        this.previewManagementDepartment = approver ? approver.department : '';
    },
    formatPreviewDate(value) {
        if (!value) return '—';

        const raw = String(value).includes('T') ? String(value).split('T')[0] : String(value).split(' ')[0];
        const date = new Date(raw + 'T00:00:00');

        if (Number.isNaN(date.getTime())) return value;

        return date.toLocaleDateString('en-US', {
            month: 'long',
            day: '2-digit',
            year: 'numeric'
        });
    },
    ordinalDay(value) {
        if (!value) return '______________';

        const raw = String(value).includes('T') ? String(value).split('T')[0] : String(value).split(' ')[0];
        const date = new Date(raw + 'T00:00:00');

        if (Number.isNaN(date.getTime())) return '______________';

        const number = date.getDate();
        const mod100 = number % 100;

        if (mod100 >= 11 && mod100 <= 13) {
            return number + 'th';
        }

        switch (number % 10) {
            case 1:
                return number + 'st';
            case 2:
                return number + 'nd';
            case 3:
                return number + 'rd';
            default:
                return number + 'th';
        }
    },
    issuedMonth(value) {
        if (!value) return '______________';

        const raw = String(value).includes('T') ? String(value).split('T')[0] : String(value).split(' ')[0];
        const date = new Date(raw + 'T00:00:00');

        if (Number.isNaN(date.getTime())) return '______________';

        return date.toLocaleDateString('en-US', {
            month: 'long',
            year: 'numeric'
        });
    },
    syncRecipientFields() {
        const selectedUserIds = Array.isArray(this.previewRecipientUserIds)
            ? this.previewRecipientUserIds.map(id => String(id))
            : [];

        const selectedContactIds = Array.isArray(this.previewRecipientContactIds)
            ? this.previewRecipientContactIds.map(id => String(id))
            : [];

        const selectedUsers = this.usersForRecipient.filter(user => {
            return selectedUserIds.includes(String(user.id));
        });

        const selectedContacts = this.contactsForRecipient.filter(contact => {
            return selectedContactIds.includes(String(contact.id));
        });

        const groupLabels = {
            all: 'All Employees',
            all_admins: 'All Admins',
            all_clients: 'All Clients',
            all_users: 'All Users',
            employee: ''
        };

        const names = [
            groupLabels[this.previewRecipientType] || '',
            ...selectedUsers.map(user => user.name),
            ...selectedContacts.map(contact => contact.name)
        ].filter(Boolean);

        this.previewTo = [...new Set(names)].join(', ');
    },
    isRecipientUserSelected(id) {
        return this.previewRecipientUserIds
            .map(item => String(item))
            .includes(String(id));
    },
    selectRecipientUser(id) {
        if (!this.isRecipientUserSelected(id)) {
            this.previewRecipientUserIds.push(String(id));
        }
        this.syncRecipientFields();
    },
    deselectRecipientUser(id) {
        this.previewRecipientUserIds = this.previewRecipientUserIds
            .filter(item => String(item) !== String(id));
        this.syncRecipientFields();
    },
    isRecipientContactSelected(id) {
        return this.previewRecipientContactIds
            .map(item => String(item))
            .includes(String(id));
    },
    selectRecipientContact(id) {
        if (!this.isRecipientContactSelected(id)) {
            this.previewRecipientContactIds.push(String(id));
        }
        this.syncRecipientFields();
    },
    deselectRecipientContact(id) {
        this.previewRecipientContactIds = this.previewRecipientContactIds
            .filter(item => String(item) !== String(id));
        this.syncRecipientFields();
    }
}" x-init="syncRecipientFields(); syncManagementApprover()">

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($communication->approval_notes)
        <div class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
            <span class="font-semibold">Revision Note:</span> {{ $communication->approval_notes }}
        </div>
    @endif

    <div class="flex gap-6 h-[calc(100vh-7rem)]">

        {{-- LEFT PREVIEW PANEL --}}
        <div class="w-[70%] bg-[#f5f6f8] overflow-y-auto p-6 border border-gray-200 rounded-xl">
            <div class="max-w-[850px] mx-auto mb-4 flex justify-between items-center sticky top-0 z-10">
                <a href="{{ route('townhall.show', $communication->id) }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition">
                    ← Back to Memo
                </a>

                <button
                    type="button"
                    id="download-preview-pdf"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 shadow transition"
                >
                    <i class="fas fa-file-pdf"></i>
                    Download Preview PDF
                </button>
            </div>

            <div class="max-w-[850px] mx-auto">
                <div id="memo-preview-pdf" class="memo-edit-preview bg-white border border-gray-300 shadow min-h-[1100px] px-[72px] py-[72px]">

                    {{-- HEADER --}}
                    <div class="memo-page-header">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="shrink-0 pt-1">
                                <img src="{{ asset('images/jk-logo.png') }}" alt="JK Logo" class="h-[72px] w-auto object-contain">
                            </div>

                            <div class="flex-1 pt-1">
                                <p class="text-[12px] leading-[1.35] text-blue-700 font-serif m-0">
                                    Atty. Jose B. Ogang, CPA, MMPSM · Jose Tamayo Rio,<br>
                                    MM-BM, CPA · Lyndon Earl P. Rio, RN, CB · John Kelly Abalde,<br>
                                    CLSSBB, CPM
                                </p>
                            </div>
                        </div>

                        <div class="memo-page-title">
                            <h2>MEMORANDUM</h2>
                        </div>

                        <div class="memo-page-meta memo-content-inset">
                            <p><strong>Memo NO.:</strong> <span x-text="previewRef || 'MEMO-AUTO-INCREMENT'"></span></p>
                            <p><strong>Date:</strong> <span x-text="formatPreviewDate(previewDate)"></span></p>
                            <p>
                                <strong><span x-text="previewRecipientLabel || 'To'"></span>:</strong>
                                <span x-text="previewTo || 'All Employees'"></span>
                            </p>
                            <p><strong>From:</strong> <span x-text="previewFrom || '—'"></span></p>
                            <p><strong>SUBJECT:</strong> <span x-text="previewSubject || '—'"></span></p>
                        </div>

                        <div class="memo-page-divider memo-content-inset"></div>
                    </div>

                    {{-- BODY --}}
                    <div class="memo-page-body memo-content-inset">
                        <div x-html="previewBody || '<p style=&quot;color:#9ca3af;&quot;>No memorandum body provided.</p>'"></div>
                    </div>

                    {{-- EFFECTIVITY --}}
                    <div class="memo-effectivity memo-content-inset">
                        This Memorandum shall take effect immediately and shall remain in force until amended,
                        superseded, or revoked by a subsequent issuance.
                    </div>

                    {{-- ISSUANCE --}}
                    <div class="issued-block memo-content-inset">
                        Issued this
                        <strong><span x-text="ordinalDay(previewDate)"></span></strong>
                        day of
                        <strong><span x-text="issuedMonth(previewDate)"></span></strong>
                        in Cebu City, Philippines.
                    </div>

                    {{-- APPROVAL / ROUTING BLOCKS --}}
                    <div class="approval-routing memo-content-inset">
                        <div class="approval-block">
                            <p class="approval-title">Prepared By:</p>
                            <p x-text="previewFrom || 'Name'"></p>
                            <p>Position</p>
                            <p x-text="previewDepartment || 'Department'"></p>
                            <p>Prepared on: Date and Time</p>
                        </div>

                        <div class="approval-block">
                            <p class="approval-title">From Management</p>
                            <p x-text="previewManagementName || 'Name'"></p>
                            <p x-text="previewManagementPosition || 'Position'"></p>
                            <p x-text="previewManagementDepartment || 'Department'"></p>
                            <p>Approved on: Date and Time</p>
                        </div>

                        <div class="approval-block">
                            <p class="approval-title">From Executive Management</p>
                            <p x-text="previewExecutiveName || 'John Kelly D. Abalde'"></p>
                            <p x-text="previewExecutivePosition || 'President and CEO'"></p>
                            <p x-text="previewExecutiveDepartment || 'Executive Management'"></p>
                            <p>Approved on: Date and Time</p>
                        </div>

                        <p class="computer-generated">
                            This is a computer-generated document. Signature is not required.
                        </p>
                    </div>

                    <div class="memo-footer-note memo-content-inset">
                        This Memorandum is an official corporate record of JK&amp;C INC. Unauthorized reproduction,
                        alteration, disclosure, or misuse of this Memorandum, in whole or in part, is strictly prohibited
                        and may result in administrative sanctions, termination of employment or engagement, and/or the
                        institution of appropriate civil, criminal, or regulatory actions, in accordance with applicable
                        laws and company policies.
                    </div>

                    <div class="memo-footer-address memo-content-inset">
                        JK&amp;C INC.<br>
                        3F Cebu Holdings Center Cebu Business Park, Cebu City, Philippines, 6000
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT FORM PANEL --}}
        <div class="w-[30%] bg-white border border-gray-200 rounded-xl shadow-2xl flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">Edit Communication</h2>
                    <p class="text-xs text-gray-500 mt-1">Resubmit for approval after updating the memo.</p>
                </div>

                <a href="{{ route('townhall.show', $communication->id) }}"
                   class="text-gray-400 hover:text-gray-600 text-lg">
                    <i class="fas fa-times"></i>
                </a>
            </div>

            <form id="townhall-edit-form" action="{{ route('townhall.update', $communication->id) }}" method="POST" enctype="multipart/form-data" class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Ref #</label>
                        <input
                            type="text"
                            value="{{ $communication->ref_no }}"
                            readonly
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-600"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Date</label>
                        <input
                            type="date"
                            name="communication_date"
                            x-model="previewDate"
                            value="{{ old('communication_date', $communication->communication_date) }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                        >
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">From</label>
                        <input
                            type="text"
                            value="{{ $communication->from_name }}"
                            x-model="previewFrom"
                            readonly
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-600 cursor-not-allowed"
                        >
                        <p class="mt-1 text-xs text-gray-400">Automatically set based on creator</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Department / Stakeholder</label>
                        <input
                            type="text"
                            name="department_stakeholder"
                            x-model="previewDepartment"
                            value="{{ old('department_stakeholder', $communication->department_stakeholder) }}"
                            placeholder="Enter department or stakeholder"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Recipient</label>

                    <div class="space-y-3">
                        <div class="grid grid-cols-[120px_1fr] gap-3">
                            <select
                                x-model="previewRecipientLabel"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                            >
                                <option value="To">To</option>
                                <option value="For">For</option>
                            </select>

                            <select
                                name="recipient_type"
                                x-model="previewRecipientType"
                                @change="syncRecipientFields()"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                            >
                                <option value="all">All Employees</option>
                                <option value="all_admins">All Admins</option>
                                <option value="all_clients">All Clients</option>
                                <option value="all_users">All Users</option>
                                <option value="employee">Specific Recipients</option>
                            </select>
                        </div>

<div x-show="previewRecipientType !== ''" x-cloak class="space-y-4">
                                {{-- Additional Specific Recipients --}}
                            <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">
                                        Additional Specific Recipients
                                    </label>

                                    <div class="min-h-[42px] rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 flex flex-wrap gap-2">
                                        <template x-if="previewTo">
                                            <template x-for="name in previewTo.split(',').map(item => item.trim()).filter(Boolean)" :key="name">
                                                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700">
                                                    <span x-text="name"></span>
                                                </span>
                                            </template>
                                        </template>

                                        <template x-if="!previewTo">
                                            <span class="text-xs text-gray-400">No recipient selected</span>
                                        </template>
                                    </div>
                                </div>

                                {{-- Users: Employees / Admins --}}
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">
                                        Employees / Admins
                                    </label>

                                    <div class="max-h-[190px] overflow-y-auto rounded-lg border border-gray-200 bg-white divide-y divide-gray-100">
                                        <template x-for="user in usersForRecipient" :key="'user-' + user.id">
                                            <button
                                                type="button"
                                                @click="selectRecipientUser(user.id)"
                                                @dblclick="deselectRecipientUser(user.id)"
                                                class="w-full px-3 py-2 text-left text-sm transition flex items-center justify-between"
                                                :class="isRecipientUserSelected(user.id)
                                                    ? 'bg-blue-50 text-blue-700'
                                                    : 'hover:bg-gray-50 text-gray-700'"
                                            >
                                                <span>
                                                    <span class="font-medium" x-text="user.name"></span>
                                                    <span class="text-xs text-gray-400" x-text="' — ' + user.role"></span>
                                                </span>

                                                <span
                                                    x-show="isRecipientUserSelected(user.id)"
                                                    class="text-blue-600 text-xs font-semibold"
                                                >
                                                    Selected
                                                </span>
                                            </button>
                                        </template>
                                    </div>

                                    <p class="mt-1 text-xs text-gray-400">
                                        Click once to select. Double-click to deselect.
                                    </p>
                                </div>

                                {{-- Clients / Contacts --}}
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">
                                        Clients / Contacts
                                    </label>

                                    <div class="max-h-[190px] overflow-y-auto rounded-lg border border-gray-200 bg-white divide-y divide-gray-100">
                                        <template x-for="contact in contactsForRecipient" :key="'contact-' + contact.id">
                                            <button
                                                type="button"
                                                @click="selectRecipientContact(contact.id)"
                                                @dblclick="deselectRecipientContact(contact.id)"
                                                class="w-full px-3 py-2 text-left text-sm transition flex items-center justify-between"
                                                :class="isRecipientContactSelected(contact.id)
                                                    ? 'bg-green-50 text-green-700'
                                                    : 'hover:bg-gray-50 text-gray-700'"
                                            >
                                                <span>
                                                    <span class="font-medium" x-text="contact.name"></span>
                                                    <span class="text-xs text-gray-400"> — Client</span>
                                                </span>

                                                <span
                                                    x-show="isRecipientContactSelected(contact.id)"
                                                    class="text-green-600 text-xs font-semibold"
                                                >
                                                    Selected
                                                </span>
                                            </button>
                                        </template>
                                    </div>

                                    <p class="mt-1 text-xs text-gray-400">
                                        Click once to select. Double-click to deselect.
                                    </p>
                                </div>

                                {{-- Hidden inputs for form submit --}}
                                <template x-for="id in previewRecipientUserIds" :key="'hidden-user-' + id">
                                    <input type="hidden" name="recipient_user_ids[]" :value="id">
                                </template>

                                <template x-for="id in previewRecipientContactIds" :key="'hidden-contact-' + id">
                                    <input type="hidden" name="recipient_contact_ids[]" :value="id">
                                </template>
                            </div>

                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                            <span class="font-medium" x-text="previewRecipientLabel"></span>:
                            <span x-text="previewTo || 'All Employees'"></span>
                        </div>
                    </div>

                    <input type="hidden" name="recipient_label" :value="previewRecipientLabel">
                    <input type="hidden" name="to_for" :value="previewTo">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Priority</label>
                    <select
                        name="priority"
                        x-model="previewPriority"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                    >
                        <option value="Low">Low</option>
                        <option value="High">High</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Subject</label>
                    <input
                        type="text"
                        name="subject"
                        x-model="previewSubject"
                        value="{{ old('subject', $communication->subject) }}"
                        placeholder="Enter subject"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                    >
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Body</label>
                    <div id="editor">{!! old('message', $communication->message) !!}</div>
                    <input type="hidden" name="message" id="message">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">CC</label>
                        <input
                            type="text"
                            name="cc"
                            x-model="previewCc"
                            value="{{ old('cc', $communication->cc) }}"
                            placeholder="Enter CC recipients"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Additional</label>
                        <input
                            type="text"
                            name="additional"
                            x-model="previewAdditional"
                            value="{{ old('additional', $communication->additional) }}"
                            placeholder="Optional"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                        >
                    </div>
                </div>

                <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-4 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-blue-700 mb-1">
                            Level 1 Approver - From Management
                        </label>

                        <select
                            name="management_approver_id"
                            x-model="previewManagementApproverId"
                            @change="syncManagementApprover()"
                            class="w-full border border-blue-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                            required
                        >
                            <option value="">Select active employee approver</option>
                            @foreach(($managementApprovers ?? collect()) as $approver)
                                <option value="{{ $approver['id'] }}">
                                    {{ $approver['name'] }} — {{ $approver['position'] ?? 'Position' }}
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

                    <div class="rounded-lg border border-blue-100 bg-white p-3 text-sm">
                        <p class="text-xs font-bold uppercase text-blue-700 mb-2">From Executive Management</p>
                        <p><span class="font-semibold">Name:</span> <span x-text="previewExecutiveName || 'John Kelly D. Abalde'"></span></p>
                        <p><span class="font-semibold">Position:</span> <span x-text="previewExecutivePosition || 'President and CEO'"></span></p>
                        <p><span class="font-semibold">Department:</span> <span x-text="previewExecutiveDepartment || 'Executive Management'"></span></p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Replace Attachment</label>
                    <input
                        type="file"
                        name="attachment"
                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100"
                    >
                    <p class="mt-1 text-xs text-gray-400">
                        Leave blank to keep the current attachment.
                    </p>

                    @if($communication->attachment)
                        <div class="mt-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600">
                            Current attachment:
                            <a href="{{ asset('storage/' . $communication->attachment) }}" target="_blank" class="text-blue-600 hover:underline">
                                View current file
                            </a>
                        </div>
                    @endif
                </div>

                <div class="px-0 py-4 border-t border-gray-200 flex items-center gap-3">
                    <a
                        href="{{ route('townhall.show', $communication->id) }}"
                        class="flex-1 border border-gray-300 text-center text-gray-700 rounded-lg py-2.5 text-sm font-medium hover:bg-gray-50 transition"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="flex-1 bg-blue-600 text-white rounded-lg py-2.5 text-sm font-medium hover:bg-blue-700 transition"
                    >
                        Update & Resubmit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


@push('styles')
<style>
    .memo-edit-preview {
        font-family: "Times New Roman", Georgia, serif;
        color: #111827;
        font-size: 14px;
        line-height: 1.5;
    }

    .memo-content-inset {
        margin-left: 40px;
        margin-right: 40px;
    }

    .memo-page-header {
        margin-bottom: 24px;
    }

    .memo-page-title {
        text-align: center;
        margin-bottom: 28px;
    }

    .memo-page-title h2 {
        font-size: 24px;
        font-weight: 700;
        color: #111827;
        font-family: "Times New Roman", Georgia, serif;
        margin: 0;
    }

    .memo-page-meta {
        margin-bottom: 10px;
        font-size: 14px;
        line-height: 1.35;
        color: #111827;
        font-family: "Times New Roman", Georgia, serif;
    }

    .memo-page-meta p {
        margin: 2px 0;
    }

    .memo-page-divider {
        border-bottom: 1px solid #6b7280;
        margin-top: 10px;
        margin-bottom: 24px;
    }

    .memo-page-body,
    .memo-page-body p,
    .memo-page-body li,
    .memo-page-body span,
    .memo-page-body div,
    .memo-page-body td,
    .memo-page-body th {
        font-family: "Times New Roman", Georgia, serif !important;
        color: #111827;
    }

    .memo-page-body {
        font-size: 14px;
        line-height: 1.3;
        text-align: justify;
        min-height: 360px;
    }

    .memo-page-body p,
    .memo-page-body li {
        text-align: justify;
    }

    .memo-page-body p {
        margin: 0 0 3px 0;
    }

    .memo-page-body ul,
    .memo-page-body ol {
        margin: 0 0 18px 24px;
        padding-left: 18px;
    }

    .memo-page-body table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        margin: 12px 0 16px 0;
    }

    .memo-page-body th,
    .memo-page-body td {
        border: 1px solid #94a3b8;
        padding: 10px 12px;
        vertical-align: top;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .memo-page-body th {
        background: #f8fafc;
        font-weight: 600;
    }

    .memo-effectivity {
        margin-top: 22px;
        font-size: 14px;
        line-height: 1.45;
        text-align: justify;
        font-family: "Times New Roman", Georgia, serif;
    }

    .issued-block {
        margin-top: 14px;
        margin-bottom: 26px;
        font-size: 14px;
        line-height: 1.7;
        font-family: "Times New Roman", Georgia, serif;
    }

    .approval-routing {
        margin-top: 26px;
        font-family: "Times New Roman", Georgia, serif;
        font-size: 13px;
        line-height: 1.25;
        color: #111827;
    }

    .approval-block {
        margin-bottom: 18px;
    }

    .approval-block p {
        margin: 0 0 2px 0;
    }

    .approval-title {
        font-weight: 700;
        margin-bottom: 8px !important;
    }

    .computer-generated {
        margin-top: 4px;
        font-weight: 700;
        font-size: 12px;
    }

    .memo-footer-note {
        margin-top: 56px;
        font-size: 11px;
        line-height: 1.45;
        text-align: justify;
    }

    .memo-footer-address {
        margin-top: 24px;
        font-size: 11px;
        line-height: 1.45;
    }

    #editor .ql-editor {
        min-height: 260px;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editorEl = document.getElementById('editor');
    const hiddenInput = document.getElementById('message');
    const form = document.getElementById('townhall-edit-form');

    if (editorEl && hiddenInput && form) {
        const quill = new Quill('#editor', {
            theme: 'snow',
            placeholder: 'Write the formal communication here...',
            modules: {
                toolbar: [
                    [{ font: [] }, { size: ['small', false, 'large', 'huge'] }],
                    ['bold', 'italic', 'underline'],
                    [{ color: [] }, { background: [] }],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ align: [] }],
                    ['link'],
                    ['clean']
                ]
            }
        });

        const existingMessage = {!! json_encode(old('message', $communication->message)) !!};
        const rootEl = document.getElementById('townhall-edit-page');
        const alpineData = rootEl ? Alpine.$data(rootEl) : null;

        if (existingMessage) {
            quill.root.innerHTML = existingMessage;
            hiddenInput.value = existingMessage;

            if (alpineData) {
                alpineData.previewBody = existingMessage;
            }
        } else {
            const defaultHtml = '<p style="color:#9ca3af;">Write the formal communication here...</p>';
            quill.root.innerHTML = '';
            hiddenInput.value = '';

            if (alpineData) {
                alpineData.previewBody = defaultHtml;
            }
        }

        quill.on('text-change', function () {
            const html = quill.root.innerHTML;
            hiddenInput.value = html;

            if (alpineData) {
                alpineData.previewBody = quill.getText().trim()
                    ? html
                    : '<p style="color:#9ca3af;">Write the formal communication here...</p>';
            }
        });

        form.addEventListener('submit', function () {
            hiddenInput.value = quill.root.innerHTML;
        });
    }

    const downloadBtn = document.getElementById('download-preview-pdf');

    if (downloadBtn) {
        downloadBtn.addEventListener('click', function () {
            const element = document.getElementById('memo-preview-pdf');
            if (!element) return;

            const subject = document.querySelector('input[name="subject"]')?.value?.trim() || 'townhall-memo';
            const safeFileName = subject
                .replace(/[\\/:*?"<>|]+/g, '')
                .replace(/\s+/g, '-')
                .toLowerCase();

            html2pdf().set({
                margin: [0.3, 0.3, 0.3, 0.3],
                filename: `${safeFileName}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, scrollY: 0 },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['css', 'legacy'] }
            }).from(element).save();
        });
    }
});
</script>
@endpush

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
    previewTo: @js(old('to_for', $communication->to_for ?? 'All Employees')),
    previewPriority: @js(old('priority', $communication->priority ?? 'Low')),
    previewSubject: @js(old('subject', $communication->subject)),
    previewBody: @js(old('message', $communication->message ?: '<p style=&quot;color:#9ca3af;&quot;>Write the formal communication here...</p>')),
    previewCc: @js(old('cc', $communication->cc)),
    previewAdditional: @js(old('additional', $communication->additional)),
    syncRecipientFields() {
        if (this.previewRecipientType === 'all') {
            this.previewRecipientUserIds = [];
            this.previewRecipientContactIds = [];
            this.previewTo = 'All Employees';
            return;
        }

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

        this.previewTo = [
            ...selectedUsers.map(user => user.name),
            ...selectedContacts.map(contact => contact.name)
        ].join(', ');
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
}" x-init="syncRecipientFields()">

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
                <div id="memo-preview-pdf" class="memo-preview bg-white border border-gray-300 shadow min-h-[1100px] px-[72px] py-[72px]">

                    {{-- LETTERHEAD --}}
                    <div class="flex items-start justify-between border-b border-gray-300 pb-6 mb-8">
                        <div>
                            <h1 class="text-[22px] font-bold tracking-wide text-gray-900">JOHN KELLY & COMPANY</h1>
                            <p class="text-[12px] text-gray-500 mt-1">Corporate Memorandum</p>
                        </div>

                        <div class="text-right text-[12px] text-gray-600 leading-5">
                            <p>Ref No: <span class="font-semibold" x-text="previewRef"></span></p>
                            <p>Date: <span class="font-semibold" x-text="previewDate || '________________'"></span></p>
                        </div>
                    </div>

                    {{-- MEMO TITLE --}}
                    <div class="text-center mb-8">
                        <h2 class="text-[20px] font-bold tracking-[0.18em] text-gray-900">MEMORANDUM</h2>
                    </div>

                    {{-- META --}}
                    <div class="space-y-3 text-[14px] text-gray-800 mb-10">
                        <div class="grid grid-cols-[120px_1fr] gap-3">
                            <p class="font-semibold uppercase tracking-wide" x-text="previewRecipientLabel"></p>
                            <p class="border-b border-dotted border-gray-300 pb-1" x-text="previewTo || '______________________________'"></p>
                        </div>

                        <div class="grid grid-cols-[120px_1fr] gap-3">
                            <p class="font-semibold uppercase tracking-wide">From</p>
                            <p class="border-b border-dotted border-gray-300 pb-1" x-text="previewFrom || '______________________________'"></p>
                        </div>

                        <div class="grid grid-cols-[120px_1fr] gap-3">
                            <p class="font-semibold uppercase tracking-wide">Department</p>
                            <p class="border-b border-dotted border-gray-300 pb-1" x-text="previewDepartment || '______________________________'"></p>
                        </div>

                        <div class="grid grid-cols-[120px_1fr] gap-3">
                            <p class="font-semibold uppercase tracking-wide">Priority</p>
                            <p class="border-b border-dotted border-gray-300 pb-1" x-text="previewPriority || 'Low'"></p>
                        </div>

                        <div class="grid grid-cols-[120px_1fr] gap-3">
                            <p class="font-semibold uppercase tracking-wide">Subject</p>
                            <p class="border-b border-dotted border-gray-300 pb-1 font-semibold" x-text="previewSubject || '______________________________'"></p>
                        </div>
                    </div>

                    {{-- BODY --}}
                    <div class="text-[15px] leading-8 text-gray-900 min-h-[420px]">
                        <div class="prose prose-sm max-w-none [&_p]:my-4 [&_p]:leading-8 [&_ul]:my-4 [&_ol]:my-4" x-html="previewBody"></div>
                    </div>

                    {{-- SIGNATURE AREA --}}
                    <div class="mt-16 space-y-10 text-[14px] text-gray-800">
                        <div>
                            <p>Prepared by:</p>
                            <p class="mt-2 font-semibold" x-text="previewFrom || '________________'"></p>
                        </div>

                        <div>
                            <p>Approved by:</p>
                            <p class="mt-2 font-semibold">John Kelly D. Abalde</p>
                            <p>President and CEO</p>
                        </div>

                        <div class="pt-6 border-t border-gray-200 space-y-2">
                            <p><span class="font-semibold">CC:</span> <span x-text="previewCc || '______________________________'"></span></p>
                            <p><span class="font-semibold">Additional:</span> <span x-text="previewAdditional || '______________________________'"></span></p>
                        </div>
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
                                <option value="employee">Specific Recipients</option>
                            </select>
                        </div>

<div x-show="previewRecipientType === 'employee'" x-cloak class="space-y-4">
                                {{-- Selected Recipients --}}
                            <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">
                                        Selected Recipients
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

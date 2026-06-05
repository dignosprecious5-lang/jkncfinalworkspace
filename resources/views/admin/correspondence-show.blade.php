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

    $openEditor = session('open_revision_editor') || $correspondence->approval_status === 'Needs Revision';
@endphp

@section('content')
<div class="w-full px-6 py-5">
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('admin.correspondence.dashboard') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            ← Back
        </a>

        <a href="{{ route('correspondence.download', $correspondence->id) }}" class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
            Download PDF
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
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

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_430px] gap-6">
        <div class="rounded-xl border border-gray-200 bg-gray-100 p-6 overflow-auto">
            <iframe
                src="{{ route('correspondence.template', ['type' => strtolower(str_replace(' ', '-', $correspondence->type ?: 'other')), 'id' => $correspondence->id]) }}"
                class="w-full min-h-[860px] rounded-lg border border-gray-300 bg-white"
                frameborder="0"
            ></iframe>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-white p-6 h-fit">
                <h2 class="text-xl font-bold text-gray-900 mb-5">Correspondence Details</h2>

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

                    @if($correspondence->review_note)
                        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-3">
                            <p class="text-xs font-bold uppercase text-yellow-700">Review Note</p>
                            <p class="mt-1 text-yellow-800">{{ $correspondence->review_note }}</p>
                        </div>
                    @endif
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
                                    Revise / Edit Everything
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

            <details class="rounded-xl border border-blue-200 bg-white p-6" @if($openEditor) open @endif>
                <summary class="cursor-pointer text-lg font-bold text-gray-900">
                    Edit Revised Correspondence
                </summary>

                <p class="mt-2 text-sm text-gray-500">
                    After saving, the correspondence will return to Level 1 approval.
                </p>

                <form method="POST" action="{{ route('admin.correspondence.revise-update', $correspondence->id) }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Type</label>
                            <select name="type" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                @foreach($types as $type)
                                    <option value="{{ $type }}" @selected(old('type', $correspondence->type) === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Date</label>
                            <input type="date" name="correspondence_date" value="{{ old('correspondence_date', optional($correspondence->correspondence_date)->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Company Name</label>
                        <input name="company_name" value="{{ old('company_name', $correspondence->company_name) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Registration Number</label>
                        <input name="registration_number" value="{{ old('registration_number', $correspondence->registration_number) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Principal Address</label>
                        <textarea name="principal_address" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('principal_address', $correspondence->principal_address) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Subject</label>
                        <input name="subject" value="{{ old('subject', $correspondence->subject) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>

                    <div class="grid grid-cols-[110px_1fr] gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">To / For</label>
                            <select name="to_for_label" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                <option value="To" @selected(old('to_for_label', $correspondence->to_for_label) === 'To')>To</option>
                                <option value="For" @selected(old('to_for_label', $correspondence->to_for_label) === 'For')>For</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">&nbsp;</label>
                            <input name="to_for" value="{{ old('to_for', $correspondence->to_for) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">From</label>
                        <input name="from_name" value="{{ old('from_name', $correspondence->from_name) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Department / Stakeholder</label>
                        <input name="department_stakeholder" value="{{ old('department_stakeholder', $correspondence->department_stakeholder) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">CC</label>
                            <input name="cc" value="{{ old('cc', $correspondence->cc) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Additional</label>
                            <input name="additional" value="{{ old('additional', $correspondence->additional) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs font-bold uppercase text-gray-700 mb-3">Prepared By</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <input name="prepared_by_name" value="{{ old('prepared_by_name', $correspondence->prepared_by_name) }}" placeholder="Name" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <input name="prepared_by_position" value="{{ old('prepared_by_position', $correspondence->prepared_by_position) }}" placeholder="Position" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <input name="prepared_by_department" value="{{ old('prepared_by_department', $correspondence->prepared_by_department) }}" placeholder="Department" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <input type="datetime-local" name="prepared_on" value="{{ old('prepared_on', optional($correspondence->prepared_on)->format('Y-m-d\TH:i')) }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
                        <p class="text-xs font-bold uppercase text-blue-700 mb-3">From Management</p>

                        <div class="mb-3">
                            <label class="block text-xs font-semibold text-blue-700 mb-1">Level 1 Approver</label>
                            <select name="management_approver_id" class="w-full rounded-lg border border-blue-200 px-3 py-2 text-sm">
                                @foreach(($managementApprovers ?? collect()) as $approver)
                                    <option value="{{ $approver['id'] }}" @selected((int) old('management_approver_id', $correspondence->management_approver_id) === (int) $approver['id'])>
                                        {{ $approver['name'] }} — {{ $approver['position'] ?? 'Management' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <input name="management_signature_name" value="{{ old('management_signature_name', $correspondence->management_signature_name ?: $correspondence->management_approver_name) }}" placeholder="Name" class="rounded-lg border border-blue-200 px-3 py-2 text-sm">
                            <input name="management_signature_position" value="{{ old('management_signature_position', $correspondence->management_signature_position ?: $correspondence->management_approver_position) }}" placeholder="Position" class="rounded-lg border border-blue-200 px-3 py-2 text-sm">
                            <input name="management_signature_department" value="{{ old('management_signature_department', $correspondence->management_signature_department ?: $correspondence->management_approver_department) }}" placeholder="Department" class="rounded-lg border border-blue-200 px-3 py-2 text-sm">
                            <input type="datetime-local" name="management_approved_on" value="{{ old('management_approved_on', optional($correspondence->management_approved_on)->format('Y-m-d\TH:i')) }}" class="rounded-lg border border-blue-200 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-4">
                        <p class="text-xs font-bold uppercase text-indigo-700 mb-3">From Executive Management</p>

                        <div class="mb-3">
                            <label class="block text-xs font-semibold text-indigo-700 mb-1">Level 2 Approver</label>
                            <select name="executive_approver_id" class="w-full rounded-lg border border-indigo-200 px-3 py-2 text-sm">
                                @foreach(($executiveApprovers ?? collect()) as $approver)
                                    <option value="{{ $approver['id'] }}" @selected((int) old('executive_approver_id', $correspondence->executive_approver_id) === (int) $approver['id'])>
                                        {{ $approver['name'] }} — {{ $approver['position'] ?? 'Officer' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <input name="executive_signature_name" value="{{ old('executive_signature_name', $correspondence->executive_signature_name ?: $correspondence->executive_approver_name) }}" placeholder="Name" class="rounded-lg border border-indigo-200 px-3 py-2 text-sm">
                            <input name="executive_signature_position" value="{{ old('executive_signature_position', $correspondence->executive_signature_position ?: $correspondence->executive_approver_position) }}" placeholder="Position" class="rounded-lg border border-indigo-200 px-3 py-2 text-sm">
                            <input name="executive_signature_department" value="{{ old('executive_signature_department', $correspondence->executive_signature_department ?: $correspondence->executive_approver_department) }}" placeholder="Department / Office" class="rounded-lg border border-indigo-200 px-3 py-2 text-sm">
                            <input type="datetime-local" name="executive_approved_on" value="{{ old('executive_approved_on', optional($correspondence->executive_approved_on)->format('Y-m-d\TH:i')) }}" class="rounded-lg border border-indigo-200 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Body</label>
                        <textarea name="body" rows="10" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono">{{ old('body', $correspondence->body) }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">You may edit the HTML body directly here.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Respond Before</label>
                            <input type="date" name="deadline" value="{{ old('deadline', optional($correspondence->deadline)->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Sent Via</label>
                            <select name="sent_via" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                @foreach(['Email', 'LBC', 'Internal', 'Hand Delivery'] as $via)
                                    <option value="{{ $via }}" @selected(old('sent_via', $correspondence->sent_via) === $via)>{{ $via }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Review Note</label>
                        <textarea name="review_note" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('review_note', $correspondence->review_note) }}</textarea>
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-blue-600 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                        Save Revised Correspondence and Send to Level 1
                    </button>
                </form>
            </details>
        </div>
    </div>
</div>
@endsection

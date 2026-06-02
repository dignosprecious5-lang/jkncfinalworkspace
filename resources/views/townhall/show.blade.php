@extends('layouts.app')
@section('title', 'Town Hall Details')

@section('content')
<div class="bg-[#f5f6f8] min-h-screen p-6">

    <div class="max-w-[1400px] mx-auto flex gap-6">

        {{-- LEFT SIDE --}}
        <div id="ack-scroll-container" class="w-[70%] h-[calc(100vh-80px)] overflow-y-auto pr-2">

            {{-- TOP ACTIONS --}}
            <div class="mb-4 flex justify-between items-center">
                <a href="{{ route('townhall') }}"
                   class="border border-gray-300 px-4 py-2 rounded-lg text-sm hover:bg-gray-100">
                    ← Back
                </a>

                <div class="flex items-center gap-2">
                    @if($communication->approval_status === 'Approved')
                        <a href="{{ route('townhall.download.pdf', $communication->id) }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-red-600 hover:bg-red-700 text-white px-4 py-2 text-sm shadow">
                            <i class="fas fa-file-pdf"></i>
                            Download PDF
                        </a>
                    @endif

                    @if(
                        $communication->approval_status === 'Needs Revision' &&
                        $communication->created_by === Auth::id() &&
                        Auth::user()->hasPermission('create_townhall')
                    )
                        <a href="{{ route('townhall.edit', $communication->id) }}"
                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm shadow">
                            Edit Revision
                        </a>
                    @endif
                </div>
            </div>

            @if(
                $communication->approval_status === 'Needs Revision' &&
                $communication->approval_notes
            )
                <div class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                    <span class="font-semibold">Revision Note:</span> {{ $communication->approval_notes }}
                </div>
            @endif

            {{-- MEMO --}}
            <div class="memo-page">

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
                        <p><strong>Memo NO.:</strong> {{ $communication->ref_no }}</p>
                        <p>
                            <strong>Date:</strong>
                            {{ $communication->communication_date ? \Carbon\Carbon::parse($communication->communication_date)->format('F d, Y') : '—' }}
                        </p>
                        <p>
                            <strong>{{ $communication->recipient_label ?? 'To' }}:</strong>
                            @if(($communication->recipient_type ?? 'all') === 'all')
                                All Employees
                            @else
                                {{ $communication->recipient_names ?: '—' }}
                            @endif
                        </p>
                        <p><strong>From:</strong> {{ $communication->from_name ?: '—' }}</p>
                        <p><strong>SUBJECT:</strong> {{ $communication->subject ?: '—' }}</p>
                    </div>

                    <div class="memo-page-divider memo-content-inset"></div>
                </div>

                {{-- BODY --}}
                <div class="memo-page-body memo-content-inset">
                    {!! $communication->message ?: '<p style="color:#9ca3af;">No memorandum body provided.</p>' !!}
                </div>

                {{-- EFFECTIVITY --}}
                <div class="memo-effectivity memo-content-inset">
                    This Memorandum shall take effect immediately and shall remain in force until amended,
                    superseded, or revoked by a subsequent issuance.
                </div>

                {{-- ISSUANCE --}}
                <div class="issued-block memo-content-inset">
                    Issued this
                    <strong>
                        {{ $communication->communication_date ? \Carbon\Carbon::parse($communication->communication_date)->format('jS \\d\\a\\y') : '______________' }}
                    </strong>
                    day of
                    <strong>
                        {{ $communication->communication_date ? \Carbon\Carbon::parse($communication->communication_date)->format('F, Y') : '______________' }}
                    </strong>
                    in Cebu City, Philippines.
                </div>

                {{-- APPROVAL / ROUTING BLOCKS --}}
                <div class="approval-routing memo-content-inset">
                    <div class="approval-block">
                        <p class="approval-title">Prepared By:</p>
                        <p>{{ $communication->from_name ?: 'Name' }}</p>
                        <p>{{ $communication->uploader?->employee?->position ?? 'Position' }}</p>
                        <p>{{ $communication->department_stakeholder ?: 'Department' }}</p>
                        <p>
                            Prepared on:
                            {{ $communication->submitted_at ? \Carbon\Carbon::parse($communication->submitted_at)->format('F d, Y h:i A') : optional($communication->created_at)->format('F d, Y h:i A') }}
                        </p>
                    </div>

                    <div class="approval-block">
                        <p class="approval-title">From Management</p>
                        <p>{{ $communication->management_approver_name ?: 'Name' }}</p>
                        <p>{{ $communication->management_approver_position ?: 'Position' }}</p>
                        <p>{{ $communication->management_approver_department ?: 'Department' }}</p>
                        <p>
                            Approved on:
                            {{ $communication->management_approved_at ? \Carbon\Carbon::parse($communication->management_approved_at)->format('F d, Y h:i A') : 'Date and Time' }}
                        </p>
                    </div>

                    <div class="approval-block">
                        <p class="approval-title">From Executive Management</p>
                        <p>{{ $communication->executive_approver_name ?: 'John Kelly D. Abalde' }}</p>
                        <p>{{ $communication->executive_approver_position ?: 'President and CEO' }}</p>
                        <p>{{ $communication->executive_approver_department ?: 'Executive Management' }}</p>
                        <p>
                            Approved on:
                            {{ $communication->executive_approved_at ? \Carbon\Carbon::parse($communication->executive_approved_at)->format('F d, Y h:i A') : 'Date and Time' }}
                        </p>
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



            {{-- APPROVAL WORKFLOW --}}
            <div class="bg-white border rounded-xl shadow p-5 mb-6">
                <h3 class="font-semibold mb-4">Approval Workflow</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-4">
                        <p class="text-xs font-bold uppercase text-blue-700 mb-2">Level 1 — From Management</p>
                        <p class="text-sm"><span class="font-semibold">Name:</span> {{ $communication->management_approver_name ?: '—' }}</p>
                        <p class="text-sm"><span class="font-semibold">Position:</span> {{ $communication->management_approver_position ?: '—' }}</p>
                        <p class="text-sm"><span class="font-semibold">Department:</span> {{ $communication->management_approver_department ?: '—' }}</p>
                        <p class="text-sm mt-2"><span class="font-semibold">Status:</span> {{ $communication->management_approval_status ?: 'Pending' }}</p>
                    </div>

                    <div class="rounded-xl border border-purple-100 bg-purple-50/50 p-4">
                        <p class="text-xs font-bold uppercase text-purple-700 mb-2">Level 2 — From Executive Management</p>
                        <p class="text-sm"><span class="font-semibold">Name:</span> {{ $communication->executive_approver_name ?: 'John Kelly D. Abalde' }}</p>
                        <p class="text-sm"><span class="font-semibold">Position:</span> {{ $communication->executive_approver_position ?: 'President and CEO' }}</p>
                        <p class="text-sm"><span class="font-semibold">Department:</span> {{ $communication->executive_approver_department ?: 'Executive Management' }}</p>
                        <p class="text-sm mt-2"><span class="font-semibold">Status:</span> {{ $communication->executive_approval_status ?: 'Pending' }}</p>
                    </div>
                </div>
            </div>
            {{-- ACKNOWLEDGEMENT --}}
            @if($communication->approval_status === 'Approved')
            <div class="bg-white border rounded-xl shadow p-5">

                <div class="flex justify-between mb-3">
                    <h3 class="font-semibold">Acknowledgement</h3>
                    <span>{{ $ackCount }}/{{ $totalEmployees }} intended recipients</span>
                </div>

                <div class="w-full bg-gray-200 rounded-full h-3 mb-4">
                    <div class="bg-blue-600 h-3 rounded-full"
                         style="width: {{ $progress }}%"></div>
                </div>

                @if($hasAcknowledged)
                    <p class="text-sm text-green-600 font-medium mb-4">
                        ✔ You have acknowledged this communication.
                    </p>
                @elseif($requiresAcknowledgement)
                    <p class="text-sm text-yellow-700 font-medium mb-4">
                        You are required to acknowledge this communication. Use the Acknowledge button in the right panel.
                    </p>
                @endif

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <b>✔ Acknowledged</b>
                        @forelse($acknowledgedUsers as $user)
                            <p class="text-green-600">{{ $user->name }}</p>
                        @empty
                            <p class="text-gray-400">None yet</p>
                        @endforelse
                    </div>

                    <div>
                        <b>Pending</b>
                        @forelse($notAcknowledgedUsers as $user)
                            <p class="text-red-500">{{ $user->name }}</p>
                        @empty
                            <p class="text-gray-400">All acknowledged</p>
                        @endforelse
                    </div>
                </div>

            </div>
            @endif

        </div>

        {{-- RIGHT SIDE PANEL --}}
        <div class="w-[30%]">
            <div class="bg-white border rounded-xl shadow p-5 sticky top-6 space-y-4">

                <h3 class="font-semibold text-lg">Communication Details</h3>

                <div class="text-sm space-y-3">

                    <div>
                        <p class="text-gray-500 text-xs">Ref</p>
                        <p>{{ $communication->ref_no }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Date</p>
                        <p>{{ $communication->communication_date ? \Carbon\Carbon::parse($communication->communication_date)->format('F d, Y') : '—' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">From</p>
                        <p>{{ $communication->from_name ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">{{ $communication->recipient_label ?? 'To' }}</p>
                        <p>{{ $communication->to_for ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Department</p>
                        <p>{{ $communication->department_stakeholder ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Priority</p>
                        <p>{{ $communication->priority ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Status</p>
                        <p>{{ $communication->approval_status ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Subject</p>
                        <p>{{ $communication->subject ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">CC</p>
                        <p>{{ $communication->cc ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Additional</p>
                        <p>{{ $communication->additional ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Attachment</p>

                        @if($communication->attachment)
                            <a href="{{ asset('storage/' . $communication->attachment) }}"
                               target="_blank"
                               rel="noopener"
                               class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100">
                                <i class="fas fa-paperclip text-xs"></i>
                                View Attachment
                            </a>

                            <p class="mt-2 text-[11px] leading-4 text-gray-400">
                                Opens in a separate tab so the file keeps its actual size and format.
                            </p>
                        @else
                            <p>—</p>
                        @endif
                    </div>

                    @if($communication->approval_notes)
                    <div>
                        <p class="text-gray-500 text-xs">Approval Notes</p>
                        <p>{{ $communication->approval_notes }}</p>
                    </div>
                    @endif
                </div>

                @if($requiresAcknowledgement && !$hasAcknowledged)
                    <form method="POST"
                          action="{{ route('townhall.acknowledge', $communication->id) }}"
                          onsubmit="return confirm('Confirm Acknowledgment\n\nAre you sure you want to acknowledge this communication?');">
                        @csrf

                        <button
                            type="submit"
                            class="block w-full text-center bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 font-semibold">
                            Acknowledge
                        </button>
                    </form>
                @elseif($hasAcknowledged)
                    <button
                        type="button"
                        disabled
                        class="block w-full text-center bg-gray-300 text-gray-600 py-2 rounded-lg cursor-not-allowed font-semibold">
                        Acknowledged
                    </button>
                @endif

                @if($communication->approval_status === 'Approved')
                    <a href="{{ route('townhall.download.pdf', $communication->id) }}"
                       class="block text-center bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">
                        Download PDF
                    </a>
                @endif

                @if(
                    $communication->approval_status === 'Needs Revision' &&
                    $communication->created_by === Auth::id() &&
                    Auth::user()->hasPermission('create_townhall')
                )
                    <a href="{{ route('townhall.edit', $communication->id) }}"
                       class="block text-center bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                        Edit and Resubmit
                    </a>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection

@push('styles')
<style>
    .memo-page {
        width: 100%;
        background: #fff;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        padding: 50px 60px;
        box-sizing: border-box;
        overflow: hidden;
        margin-bottom: 24px;
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
        font-size: 28px;
        font-weight: 600;
        letter-spacing: 0.04em;
        color: #555;
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
        min-height: 420px;
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

    .memo-page-footer {
        margin-top: 40px;
        font-family: "Times New Roman", Georgia, serif;
        color: #1f2937;
    }

    .issued-block {
        font-size: 14px;
        line-height: 1.7;
        margin-bottom: 32px;
    }

    .approved-block {
        margin-top: 32px;
    }

    .prepared-block {
        margin-top: 20px;
    }

    .prepared-label {
        margin: 0 0 36px 0;
        font-size: 14px;
    }

    .signature-line {
        width: 240px;
        border-bottom: 1px solid #374151;
        margin-bottom: 4px;
    }

    .prepared-name {
        margin: 0;
        font-weight: 600;
        line-height: 1.2;
    }

    .prepared-role {
        margin: 0;
        line-height: 1.2;
    }

    .memo-extra-details {
        margin-top: 24px;
        font-size: 13px;
        line-height: 1.5;
    }

    .memo-extra-details p {
        margin: 2px 0;
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

    .memo-effectivity {
        margin-top: 22px;
        font-size: 14px;
        line-height: 1.45;
        text-align: justify;
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

</style>
@endpush

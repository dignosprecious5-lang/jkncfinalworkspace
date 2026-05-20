@extends('layouts.app')

@section('content')
<div class="bg-[#f5f6f8] min-h-screen p-6">
    <div class="max-w-[1400px] mx-auto flex gap-6">

        {{-- LEFT POLICY PREVIEW --}}
        <div class="w-[70%] h-[calc(100vh-80px)] overflow-y-auto pr-2">
            <div class="mb-4 flex justify-between items-center">
                <a href="{{ route('admin.policies.index') }}"
                   class="border border-gray-300 px-4 py-2 rounded-lg text-sm hover:bg-gray-100">
                    ← Back
                </a>
            </div>

            <div class="flex justify-center">
                <div class="policy-paper bg-white border border-gray-300 shadow mb-6">

                    {{-- MEMORANDUM-STYLE HEADER --}}
                    <div class="policy-memo-header">
                        <div class="policy-memo-logo">
                            <img src="{{ asset('images/jk-logo.png') }}" alt="John Kelly & Company Logo">
                        </div>

                        <div class="policy-memo-names">
                            <p>
                                Atty. Jose B. Ogang, CPA, MMPSM · Jose Tamayo Rio,<br>
                                MM-BM, CPA · Lyndon Earl P. Rio, RN, CB · John Kelly Abalde,<br>
                                CLSSBB, CPM
                            </p>
                        </div>
                    </div>

                    {{-- POLICY TITLE --}}
                    <div class="policy-memo-title">
                        <h2>{{ $policy->policy ?: 'POLICY TITLE' }}</h2>
                    </div>

                    {{-- POLICY META --}}
                    <div class="policy-memo-meta">
                        <p>
                            <strong>Policy Title:</strong>
                            {{ $policy->policy ?: '______________________________' }}
                        </p>

                        <p>
                            <strong>Code:</strong>
                            {{ $policy->code ?: 'AUTO-GENERATED' }}
                        </p>

                        <p>
                            <strong>Version:</strong>
                            {{ $policy->version ?: '1.0' }}
                        </p>

                        <p>
                            <strong>Effectivity Date:</strong>
                            {{ $policy->effectivity_date ? \Carbon\Carbon::parse($policy->effectivity_date)->format('F d, Y') : '______________________________' }}
                        </p>

                        <p>
                            <strong>Prepared by:</strong>
                            {{ $policy->prepared_by ?: '______________________________' }}
                        </p>

                        <p>
                            <strong>Reviewed by:</strong>
                            {{ $policy->reviewed_by ?: '______________________________' }}
                        </p>

                        <p>
                            <strong>Approved by:</strong>
                            {{ $policy->approved_by ?: '______________________________' }}
                        </p>

                        <p>
                            <strong>Review Cycle:</strong>
                            {{ $policy->review_cycle ?: '______________________________' }}
                        </p>

                        <p>
                            <strong>Classification:</strong>
                            {{ $policy->classification ?: 'Internal Use Only' }}
                        </p>
                    </div>

                    <div class="policy-memo-divider"></div>

                    {{-- BODY --}}
                    <div class="description-content">
                        {!! $policy->description ?? '<p style="color:#cbd5e0;">No description provided.</p>' !!}
                    </div>

                    {{-- ATTACHMENT PREVIEW --}}
                    @if($policy->attachment)
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Attachment Preview</h3>

                            <div class="border rounded-lg overflow-hidden bg-white">
                                @php
                                    $ext = strtolower(pathinfo($policy->attachment, PATHINFO_EXTENSION));
                                @endphp

                                @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                                    <img src="{{ asset('storage/'.$policy->attachment) }}" class="w-full">
                                @elseif($ext === 'pdf')
                                    <iframe src="{{ asset('storage/'.$policy->attachment) }}" class="w-full h-[500px]"></iframe>
                                @else
                                    <div class="p-4 text-center">
                                        <a href="{{ asset('storage/'.$policy->attachment) }}"
                                           target="_blank"
                                           class="text-blue-600 underline">
                                            Download Attachment
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>

        {{-- RIGHT ADMIN DETAILS --}}
        <div class="w-[30%]">
            <div class="bg-white border rounded-xl shadow p-5 sticky top-6 space-y-4">
                <h3 class="font-semibold text-lg">Policy Details</h3>

                <div class="text-sm space-y-3">
                    <div>
                        <p class="text-gray-500 text-xs">Policy Title</p>
                        <p>{{ $policy->policy ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Code</p>
                        <p>{{ $policy->code ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Version</p>
                        <p>{{ $policy->version ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Effectivity Date</p>
                        <p>
                            {{ $policy->effectivity_date
                                ? \Carbon\Carbon::parse($policy->effectivity_date)->format('M d, Y')
                                : '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Prepared By</p>
                        <p>{{ $policy->prepared_by ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Reviewed By</p>
                        <p>{{ $policy->reviewed_by ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Approved By</p>
                        <p>{{ $policy->approved_by ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Review Cycle</p>
                        <p>{{ $policy->review_cycle ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Classification</p>
                        <p>{{ $policy->classification ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Approval Status</p>
                        <p>
                            @php
                                $approvalStatus = $policy->approval_status ?? 'Pending';

                                $approvalClass = match($approvalStatus) {
                                    'Approved' => 'bg-green-100 text-green-700',
                                    'Rejected' => 'bg-red-100 text-red-700',
                                    'Needs Revision' => 'bg-blue-100 text-blue-700',
                                    default => 'bg-yellow-100 text-yellow-700',
                                };
                            @endphp

                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $approvalClass }}">
                                {{ $approvalStatus }}
                            </span>
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Workflow Status</p>
                        <p>
                            @php
                                $workflowStatus = $policy->workflow_status ?? 'Draft';

                                $workflowClass = match($workflowStatus) {
                                    'Accepted' => 'bg-green-100 text-green-700',
                                    'Submitted' => 'bg-yellow-100 text-yellow-700',
                                    'Reverted' => 'bg-blue-100 text-blue-700',
                                    'Archived' => 'bg-gray-200 text-gray-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp

                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $workflowClass }}">
                                {{ $workflowStatus }}
                            </span>
                        </p>
                    </div>

                    @if(!empty($policy->review_note))
                        <div>
                            <p class="text-gray-500 text-xs">Review Note</p>
                            <p>{{ $policy->review_note }}</p>
                        </div>
                    @endif

                    <div>
                        <p class="text-gray-500 text-xs">Attachment</p>

                        @if($policy->attachment)
                            <a href="{{ asset('storage/' . $policy->attachment) }}"
                               target="_blank"
                               class="text-blue-600 hover:underline">
                                View Attachment
                            </a>
                        @else
                            <p>-</p>
                        @endif
                    </div>
                </div>

                {{-- ADMIN ACTION BUTTONS --}}
                <div class="flex flex-wrap gap-3 pt-2">
                    @if(($policy->workflow_status ?? null) === 'Submitted' && Auth::user()->hasPermission('approve_policies'))
                        <form method="POST" action="{{ route('admin.policies.approve', $policy->id) }}">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium rounded-lg bg-green-600 text-white hover:bg-green-700 transition">
                                Approve
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.policies.reject', $policy->id) }}">
                            @csrf
                            <input type="hidden" name="review_note" value="Rejected by admin">
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition">
                                Reject
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.policies.revise', $policy->id) }}">
                            @csrf
                            <input type="hidden" name="review_note" value="Needs revision">
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium rounded-lg bg-slate-800 text-white hover:bg-slate-900 transition">
                                Revise
                            </button>
                        </form>
                    @endif

                    @if(!$policy->is_archived && Auth::user()->hasPermission('approve_policies'))
                        <form method="POST" action="{{ route('admin.policies.archive', $policy->id) }}">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium rounded-lg bg-gray-700 text-white hover:bg-gray-800 transition">
                                Archive
                            </button>
                        </form>
                    @endif

                    @if($policy->is_archived && Auth::user()->hasPermission('approve_policies'))
                        <form method="POST" action="{{ route('admin.policies.unarchive', $policy->id) }}">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition">
                                Unarchive
                            </button>
                        </form>
                    @endif
                </div>

                {{-- DOWNLOAD PDF --}}
                <a href="{{ route('policies.preview', [
                        'policy' => $policy->policy,
                        'code' => $policy->code,
                        'version' => $policy->version,
                        'effectivity_date' => $policy->effectivity_date,
                        'prepared_by' => $policy->prepared_by,
                        'reviewed_by' => $policy->reviewed_by,
                        'approved_by' => $policy->approved_by,
                        'review_cycle' => $policy->review_cycle,
                        'classification' => $policy->classification,
                        'description' => $policy->description,
                    ]) }}"
                   class="block text-center bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 transition">
                    Download PDF
                </a>
            </div>
        </div>

    </div>
</div>
@endsection

@push('styles')
<style>
    .policy-paper {
        width: 210mm;
        min-height: 297mm;
        padding: 72px;
        background: #fff;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        font-family: "Times New Roman", Georgia, serif;
        color: #111827;
        font-size: 14px;
        line-height: 1.5;
    }

    .policy-memo-header {
        display: flex;
        align-items: flex-start;
        gap: 34px;
        margin-bottom: 34px;
    }

    .policy-memo-logo {
        flex: 0 0 auto;
        padding-top: 4px;
    }

    .policy-memo-logo img {
        height: 76px;
        width: auto;
        object-fit: contain;
    }

    .policy-memo-names {
        flex: 1 1 auto;
        padding-top: 8px;
    }

    .policy-memo-names p {
        font-size: 12px;
        line-height: 1.35;
        color: #111827;
        font-family: "Times New Roman", Georgia, serif;
        margin: 0;
    }

    .policy-memo-title {
        text-align: center;
        margin-bottom: 30px;
    }

    .policy-memo-title h2 {
        font-size: 24px;
        font-weight: 700;
        letter-spacing: 0.12em;
        color: #4b5563;
        font-family: "Times New Roman", Georgia, serif;
        text-transform: uppercase;
        margin: 0;
    }

    .policy-memo-meta {
        font-size: 14px;
        line-height: 1.45;
        color: #111827;
        font-family: "Times New Roman", Georgia, serif;
        margin-bottom: 10px;
    }

    .policy-memo-meta p {
        margin: 2px 0;
    }

    .policy-memo-meta strong {
        font-weight: 700;
    }

    .policy-memo-divider {
        border-bottom: 1px solid #6b7280;
        margin: 12px 0 26px 0;
    }

    .description-content {
        margin-top: 20px;
        width: 100%;
        font-size: 14px;
    }

    .description-content p {
        margin: 0 0 8px 0;
    }

    .description-content ul,
    .description-content ol {
        margin: 0 0 10px 20px;
        padding: 0;
    }

    .description-content li {
        margin-bottom: 4px;
    }

    .description-content h1,
    .description-content h2,
    .description-content h3,
    .description-content h4,
    .description-content h5,
    .description-content h6 {
        margin: 12px 0 8px 0;
        line-height: 1.3;
    }

    .description-content strong {
        font-weight: bold;
    }

    .description-content em {
        font-style: italic;
    }

    .description-content u {
        text-decoration: underline;
    }

    .description-content blockquote {
        border-left: 3px solid #cbd5e0;
        padding-left: 10px;
        margin: 10px 0;
        color: #4a5568;
    }

    .description-content hr {
        border: none;
        border-top: 1px solid #cbd5e0;
        margin: 12px 0;
    }

    .description-content img {
        max-width: 100% !important;
        height: auto !important;
    }

    .description-content table {
        width: 100% !important;
        max-width: 100% !important;
        border-collapse: collapse !important;
        table-layout: fixed !important;
        margin: 12px 0 !important;
        border: 1px solid #94a3b8 !important;
    }

    .description-content th,
    .description-content td {
        border: 1px solid #94a3b8 !important;
        padding: 8px !important;
        vertical-align: top !important;
        text-align: left !important;
        white-space: normal !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
    }

    .description-content th {
        background: #f8fafc !important;
        font-weight: bold !important;
    }

    .description-content colgroup,
    .description-content col {
        display: none !important;
        width: auto !important;
    }

    .description-content td p,
    .description-content th p,
    .description-content td div,
    .description-content th div,
    .description-content td span,
    .description-content th span {
        margin: 0 !important;
        padding: 0 !important;
        white-space: normal !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
    }

    @media (max-width: 1400px) {
        .policy-paper {
            width: 100%;
            min-height: auto;
        }
    }
</style>
@endpush

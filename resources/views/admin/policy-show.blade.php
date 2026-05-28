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

                    {{-- POLICY HEADER --}}
                    <div class="policy-memo-header">
                        <div class="policy-memo-logo">
                            <img src="{{ asset('images/jk-logo.png') }}" alt="John Kelly & Company Logo">
                        </div>
                    </div>

                    {{-- POLICY TITLE --}}
                    <div class="policy-memo-title">
                        <h2>{{ $policy->policy ?: 'POLICY TITLE' }}</h2>
                    </div>

                    {{-- POLICY META --}}
                    <div class="policy-memo-meta">
                        <p><strong>Policy Title:</strong> {{ $policy->policy ?: '______________________________' }}</p>
                        <p><strong>Code:</strong> {{ $policy->code ?: 'AUTO-GENERATED' }}</p>
                        <p><strong>Version:</strong> {{ $policy->version ?: '1.0' }}</p>
                        <p><strong>Effectivity Date:</strong> {{ $policy->effectivity_date ? \Carbon\Carbon::parse($policy->effectivity_date)->format('F d, Y') : '______________________________' }}</p>
                        <p><strong>Prepared by:</strong> {{ $policy->prepared_by ?: '______________________________' }}</p>
                        <p><strong>Reviewed by:</strong> {{ $policy->reviewed_by ?: '______________________________' }}</p>
                        <p><strong>Approved by:</strong> {{ $policy->approved_by ?: '______________________________' }}</p>
                        <p><strong>Review Cycle:</strong> {{ $policy->review_cycle ?: '______________________________' }}</p>
                        <p><strong>Classification:</strong> {{ $policy->classification ?: 'Internal Use Only' }}</p>
                    </div>

                    <div class="policy-memo-divider"></div>

                    {{-- BODY --}}
                    <div class="description-content">
                        {!! $policy->description ?? '<p style="color:#cbd5e0;">No description provided.</p>' !!}
                    </div>
                </div>
            </div>

            {{-- ATTACHMENT PREVIEW OUTSIDE THE A4 DOCUMENT --}}
            @if($policy->attachment)
                <div class="mt-6 mb-6">
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">Attachment Preview</h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    This attachment is shown outside and below the policy document.
                                </p>
                            </div>

                            <a href="{{ asset('storage/'.$policy->attachment) }}"
                               target="_blank"
                               class="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100 transition">
                                <i class="fas fa-external-link-alt text-[10px]"></i>
                                Open Attachment
                            </a>
                        </div>

                        <div class="bg-gray-50 p-4">
                            @php
                                $ext = strtolower(pathinfo($policy->attachment, PATHINFO_EXTENSION));
                            @endphp

                            @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                                <div class="flex justify-center">
                                    <img src="{{ asset('storage/'.$policy->attachment) }}"
                                         class="max-w-full rounded-lg border border-gray-200 bg-white shadow-sm">
                                </div>
                            @elseif($ext === 'pdf')
                                <iframe src="{{ asset('storage/'.$policy->attachment) }}"
                                        class="w-full h-[650px] rounded-lg border border-gray-200 bg-white"></iframe>
                            @else
                                <div class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center">
                                    <p class="text-sm text-gray-600 mb-3">
                                        Preview is not available for this file type.
                                    </p>
                                    <a href="{{ asset('storage/'.$policy->attachment) }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition">
                                        <i class="fas fa-download text-xs"></i>
                                        Download Attachment
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
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
                        <p>{{ $policy->effectivity_date ? \Carbon\Carbon::parse($policy->effectivity_date)->format('M d, Y') : '-' }}</p>
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
                <div class="pt-2">
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Actions</p>

                    <div class="grid grid-cols-2 gap-2">
                        @if(!$policy->is_archived && Auth::user()->hasPermission('approve_policies'))
                            <form method="POST" action="{{ route('admin.policies.approve', $policy->id) }}">
                                @csrf
                                <button type="submit"
                                        class="w-full px-4 py-2 text-sm font-medium rounded-lg bg-green-600 text-white hover:bg-green-700 transition">
                                    Approve
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('admin.policies.review', $policy->id) }}">
                            @csrf
                            <button type="submit"
                                    class="w-full px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition">
                                Review
                            </button>
                        </form>

                        <a href="{{ route('policies.edit', $policy->id) }}"
                           class="w-full px-4 py-2 text-sm font-medium rounded-lg bg-slate-800 text-white hover:bg-slate-900 transition text-center">
                            Edit
                        </a>

                        @if(!$policy->is_archived && Auth::user()->hasPermission('approve_policies'))
                            <form method="POST" action="{{ route('admin.policies.reject', $policy->id) }}">
                                @csrf
                                <input type="hidden" name="review_note" value="Rejected by admin">
                                <button type="submit"
                                        class="w-full px-4 py-2 text-sm font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition">
                                    Reject
                                </button>
                            </form>
                        @endif

                        @if(!$policy->is_archived && Auth::user()->hasPermission('approve_policies'))
                            <form method="POST" action="{{ route('admin.policies.revise', $policy->id) }}" class="col-span-2">
                                @csrf
                                <input type="hidden" name="review_note" value="Needs revision">
                                <button type="submit"
                                        class="w-full px-4 py-2 text-sm font-medium rounded-lg border border-yellow-300 bg-yellow-50 text-yellow-700 hover:bg-yellow-100 transition">
                                    Revise
                                </button>
                            </form>
                        @endif

                        @if(!$policy->is_archived && Auth::user()->hasPermission('approve_policies'))
                            <form method="POST" action="{{ route('admin.policies.archive', $policy->id) }}" class="col-span-2">
                                @csrf
                                <button type="submit"
                                        class="w-full px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition">
                                    Archive
                                </button>
                            </form>
                        @endif

                        @if($policy->is_archived && Auth::user()->hasPermission('approve_policies'))
                            <form method="POST" action="{{ route('admin.policies.unarchive', $policy->id) }}" class="col-span-2">
                                @csrf
                                <button type="submit"
                                        class="w-full px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition">
                                    Unarchive
                                </button>
                            </form>
                        @endif
                    </div>
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
        justify-content: center;
        align-items: center;
        margin-bottom: 34px;
        text-align: center;
    }

    .policy-memo-logo {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        padding-top: 4px;
    }

    .policy-memo-logo img {
        height: 90px;
        width: auto;
        object-fit: contain;
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

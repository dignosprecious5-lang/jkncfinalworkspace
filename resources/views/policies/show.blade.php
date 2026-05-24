@extends('layouts.app')

@section('content')
<div class="bg-[#f5f6f8] min-h-screen p-6">
    <div class="max-w-[1400px] mx-auto flex gap-6">

        <div class="w-[70%] h-[calc(100vh-80px)] overflow-y-auto pr-2">
            <div class="mb-4 flex justify-between items-center">
                <a href="{{ route('policies.index') }}"
                   class="border border-gray-300 px-4 py-2 rounded-lg text-sm hover:bg-gray-100">
                    ← Back
                </a>
            </div>

            <div class="flex justify-center">
                <div class="policy-paper bg-white border border-gray-300 shadow mb-6">

                    <div class="policy-memo-header">
                            <div class="policy-memo-logo">
                                <img src="{{ asset('images/jk-logo.png') }}" alt="John Kelly & Company Logo">
                            </div>
                        </div>

                    <div class="policy-memo-title">
                        <h2>{{ $policy->policy ?: 'POLICY TITLE' }}</h2>
                    </div>

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

                    <div class="description-content">
                        {!! $policy->description ?? '<p style="color:#cbd5e0;">No description provided.</p>' !!}
                    </div>

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
                                        <a href="{{ asset('storage/'.$policy->attachment) }}" target="_blank" class="text-blue-600 underline">
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
                        <p>{{ $policy->approval_status ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Workflow Status</p>
                        <p>{{ $policy->workflow_status ?? '-' }}</p>
                    </div>

                    @if($policy->review_note)
                        <div>
                            <p class="text-gray-500 text-xs">Review Note</p>
                            <p>{{ $policy->review_note }}</p>
                        </div>
                    @endif

                    <div>
                        <p class="text-gray-500 text-xs">Attachment</p>
                        @if($policy->attachment)
                            <a href="{{ asset('storage/' . $policy->attachment) }}" target="_blank" class="text-blue-600 hover:underline">
                                View Attachment
                            </a>
                        @else
                            <p>-</p>
                        @endif
                    </div>
                </div>

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
                   target="_blank"
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

    .description-content img {
        max-width: 100% !important;
        height: auto !important;
    }

    @media (max-width: 1400px) {
        .policy-paper {
            width: 100%;
            min-height: auto;
        }
    }
</style>
@endpush

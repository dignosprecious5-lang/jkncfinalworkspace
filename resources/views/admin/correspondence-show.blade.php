@extends('layouts.app')
@section('title', 'Correspondence Details')

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

    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_390px] gap-6">
        <div class="rounded-xl border border-gray-200 bg-gray-100 p-6 overflow-auto">
            <iframe
                src="{{ route('correspondence.template', ['type' => strtolower(str_replace(' ', '-', $correspondence->type ?: 'other')), 'id' => $correspondence->id]) }}"
                class="w-full min-h-[820px] rounded-lg border border-gray-300 bg-white"
                frameborder="0"
            ></iframe>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 h-fit sticky top-6">
            <h2 class="text-xl font-bold text-gray-900 mb-5">Correspondence Details</h2>

            <div class="space-y-4 text-sm">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-gray-500">Ref</p>
                        <p class="font-medium">{{ $correspondence->ref_no ?: 'COR-' . str_pad($correspondence->id, 5, '0', STR_PAD_LEFT) }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500">Type</p>
                        <p class="font-medium">{{ $correspondence->type ?: '—' }}</p>
                    </div>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Company</p>
                    <p class="font-medium break-words">{{ $correspondence->company_name ?: '—' }}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Registration No.</p>
                    <p class="font-medium break-words">{{ $correspondence->registration_number ?: '—' }}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Principal Address</p>
                    <p class="font-medium break-words">{{ $correspondence->principal_address ?: '—' }}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Date</p>
                    <p class="font-medium">{{ optional($correspondence->correspondence_date)->format('M d, Y') ?: '—' }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-gray-500">To / For</p>
                        <p class="font-medium break-words">{{ $correspondence->to_for ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500">From</p>
                        <p class="font-medium break-words">{{ $correspondence->from_name ?: '—' }}</p>
                    </div>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Subject</p>
                    <p class="font-medium break-words">{{ $correspondence->subject ?: '—' }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-gray-500">Workflow</p>
                        <p class="font-medium">{{ $correspondence->workflow_status ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Approval</p>
                        <p class="font-medium">{{ $correspondence->approval_status ?: '—' }}</p>
                    </div>
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
                @if($correspondence->is_archived)
                    <form method="POST" action="{{ route('correspondence.unarchive', $correspondence->id) }}">
                        @csrf
                        <button type="submit" class="w-full rounded-lg border border-gray-300 bg-white py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Unarchive
                        </button>
                    </form>
                @else
                    @if($correspondence->approval_status !== 'Approved')
                        <form method="POST" action="{{ route('correspondence.approve', $correspondence->id) }}">
                            @csrf
                            <button type="submit" class="w-full rounded-lg bg-green-600 py-2.5 text-sm font-semibold text-white hover:bg-green-700">
                                Approve Current Level
                            </button>
                        </form>

                        <form method="POST" action="{{ route('correspondence.revise', $correspondence->id) }}">
                            @csrf
                            <textarea name="review_note" rows="2" class="mb-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Revision note">Needs revision.</textarea>
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
</div>
@endsection

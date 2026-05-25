@extends('layouts.app')
@section('title', 'Company KYC')

@section('content')
@php
    $authUser = auth()->user();
    $canReviewKyc = in_array((string) ($authUser->role ?? ''), ['Admin', 'SuperAdmin'], true);
    $canManageRequirementDocs = (bool) $authUser;
    $bifStatus = strtolower((string) ($bif?->status ?? ''));
    $bifApproved = $bifStatus === 'approved';
    $bifPendingApproval = $bifStatus === 'pending_approval';
@endphp
<div class="w-full px-4 sm:px-6 lg:px-8 mt-4 pb-8">
    <div class="bg-white border border-gray-100 rounded-md overflow-hidden">
        @include('company.partials.company-header', [
            'company' => $company,
            'companyKycStatus' => $companyKycStatus,
            'companyKycStatusClass' => $companyKycStatusClass,
        ])

        <div class="bg-gray-50 p-4">
            @if (session('bif_success') && !session('bif_client_email'))
                <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                    {{ session('bif_success') }}
                </div>
            @endif
            <div id="companyKycLiveFeedback" class="mb-4 hidden rounded-md border px-4 py-3 text-sm font-medium"></div>

            @if (session('bif_warning'))
                <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                    {{ session('bif_warning') }}
                </div>
            @endif

            @if ($errors->has('kyc'))
                <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                    {{ $errors->first('kyc') }}
                </div>
            @endif

            @if ($errors->has('document'))
                <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                    {{ $errors->first('document') }}
                </div>
            @endif

            @if ($errors->has('change_rejection_reason'))
                <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                    {{ $errors->first('change_rejection_reason') }}
                </div>
            @endif

            <div id="companyKycApp">
                <div class="mb-4 grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900">Business Information Form (BIF)</h2>
                                <p class="mt-1 text-xs text-gray-500">Saved business information is the source of truth for this company record.</p>
                            </div>
                            @if ($bif)
                                <a href="{{ route('company.bif.edit', ['company' => $company->id, 'bif' => $bif->id]) }}" class="text-sm text-blue-600 hover:text-blue-700">Edit</a>
                            @endif
                        </div>

                        <div id="companyBifDocumentContent" class="bg-[#eef4ff] p-4">
                            @if ($bif)
                                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white p-4">
                                    @include('company.bif.partials.document', ['wrapperClass' => 'bif-doc'])
                                </div>
                            @else
                                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-6 py-12 text-center">
                                    <h3 class="text-base font-semibold text-gray-900">No business information submitted yet</h3>
                                    <p class="mt-2 text-sm text-gray-500">Create a Business Information Form to preview company KYC details here.</p>
                                    <a href="{{ route('company.bif.create', $company->id) }}" class="mt-5 inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">
                                        Create BIF
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-100 px-4 py-3">
                                <h3 class="text-base font-semibold text-gray-900">Client Outreach</h3>
                            </div>
                            <div class="px-4 py-4">
                                @if ($bif)
                                    <div class="rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-cyan-50 p-4">
                                        <p class="text-sm font-semibold text-gray-900">Send secure BIF link</p>
                                        <p class="mt-1 text-xs leading-5 text-gray-600">Send a secure BIF link to the client so they can complete missing details and upload onboarding documents.</p>
                                        <button type="button" data-open-send-bif-modal class="mt-4 flex h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                                            Send BIF
                                        </button>
                                    </div>
                                @else
                                    <div class="rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-cyan-50 p-4">
                                        <p class="text-sm font-semibold text-gray-900">Send secure BIF link</p>
                                        <p class="mt-1 text-xs leading-5 text-gray-600">You can send the client a secure BIF link even before the internal record is fully completed.</p>
                                        <button type="button" data-open-send-bif-modal class="mt-4 flex h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                                            Send BIF
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-100 px-4 py-3">
                                <h3 class="text-base font-semibold text-gray-900">Preview / PDF</h3>
                            </div>
                            <div class="space-y-4 px-4 py-4">
                                @if ($bif)
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                        <div class="mb-3">
                                            <p class="text-sm font-semibold text-gray-900">PDF Tools</p>
                                            <p class="mt-1 text-xs leading-5 text-gray-600">Preview the current BIF or export a print-friendly PDF copy for offline review.</p>
                                        </div>
                                        <div class="space-y-3">
                                            <a href="{{ route('company.bif.print', ['company' => $company->id, 'bif' => $bif->id]) }}" target="_blank" class="flex h-12 w-full items-center justify-center rounded-xl border border-gray-300 bg-white text-sm font-semibold text-gray-700 transition hover:bg-gray-100">Preview PDF</a>
                                            <a href="{{ route('company.bif.print', ['company' => $company->id, 'bif' => $bif->id, 'autoprint' => 1]) }}" target="_blank" class="flex h-12 w-full items-center justify-center rounded-xl bg-blue-600 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Download PDF</a>
                                        </div>
                                    </div>
                                @else
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                        <div class="mb-3">
                                            <p class="text-sm font-semibold text-gray-900">PDF Tools</p>
                                            <p class="mt-1 text-xs leading-5 text-gray-600">Create or save the BIF first to unlock the printable preview and downloadable PDF.</p>
                                        </div>
                                        <div class="space-y-3">
                                            <button type="button" disabled class="flex h-12 w-full items-center justify-center rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-400">Preview PDF</button>
                                            <button type="button" disabled class="flex h-12 w-full items-center justify-center rounded-xl bg-gray-200 text-sm font-semibold text-gray-500">Download PDF</button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-[320px_1fr]">
                    <div class="space-y-4">
                        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                                <h2 class="text-base font-semibold text-gray-900">Business Information Summary</h2>
                                @if ($bif)
                                    <a href="{{ route('company.bif.edit', ['company' => $company->id, 'bif' => $bif->id]) }}" class="text-sm text-blue-600 hover:text-blue-700">Edit</a>
                                @endif
                            </div>
                            <div id="companyBifSummaryCard" class="space-y-4 px-4 py-4 text-sm">
                                <div>
                                    <p class="text-gray-500">BIF Number</p>
                                    <p class="font-medium text-gray-900">{{ $bif?->bif_no ?: '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Status</p>
                                    <span id="companyBifStatusBadge" class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $statusPillClass }}">{{ $statusLabel }}</span>
                                </div>
                                <div>
                                    <p class="text-gray-500">Date Submitted</p>
                                    <p id="companyBifSubmittedAt" class="font-medium text-gray-900">{{ $bif?->submitted_at ? $bif->submitted_at->format('F j, Y') : '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Authorized Contact Person</p>
                                    <p class="font-medium text-gray-900">{{ $bif?->authorized_contact_person_name ?: '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">TIN</p>
                                    <p class="font-medium text-gray-900">{{ $bif?->tin_no ?: '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Last Updated</p>
                                    <p class="font-medium text-gray-900">{{ $bif?->updated_at ? $bif->updated_at->format('F j, Y g:i A') : '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Data Source</p>
                                    <p class="font-medium text-gray-900">
                                        @if (($bif?->last_submission_source ?? null) === 'client')
                                            Client-submitted
                                        @elseif (($bif?->last_submission_source ?? null) === 'manual')
                                            Manually encoded
                                        @else
                                            -
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Last Client Submission</p>
                                    <p id="companyBifClientSubmittedAt" class="font-medium text-gray-900">{{ $bif?->client_submitted_at ? $bif->client_submitted_at->format('F j, Y g:i A') : '-' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-100 px-4 py-3">
                                <h3 class="text-base font-semibold text-gray-900">Actions</h3>
                            </div>
                            <div class="space-y-2 px-4 py-4">
                                @if ($bif && $bif->change_request_status === 'pending')
                                    <div class="mb-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-3 text-xs text-amber-900">
                                        <p class="font-semibold">Pending BIF Change Request</p>
                                        <p class="mt-1">Requested by: {{ $bif->change_requested_by_name ?: 'User' }}</p>
                                        <p>Requested at: {{ $bif->change_requested_at ? $bif->change_requested_at->format('F j, Y g:i A') : '-' }}</p>
                                        @if (filled($bif->change_request_note))
                                            <p class="mt-1">Note: {{ $bif->change_request_note }}</p>
                                        @endif
                                    </div>
                                @elseif ($bif && $bif->change_request_status === 'rejected')
                                    <div class="mb-2 rounded-lg border border-red-200 bg-red-50 px-3 py-3 text-xs text-red-800">
                                        <p class="font-semibold">Latest Change Request Rejected</p>
                                        <p class="mt-1">Reviewed by: {{ $bif->change_reviewed_by_name ?: 'Admin' }}</p>
                                        <p>Reviewed at: {{ $bif->change_reviewed_at ? $bif->change_reviewed_at->format('F j, Y g:i A') : '-' }}</p>
                                        @if (filled($bif->change_rejection_reason))
                                            <p class="mt-1">Reason: {{ $bif->change_rejection_reason }}</p>
                                        @endif
                                    </div>
                                @endif

                                @if ($canReviewKyc)
                                    @php
                                        $canReviewDecision = $bif && $bif->status === 'pending_approval';
                                    @endphp
                                    @if ($bif && $bif->change_request_status === 'pending')
                                        <form method="POST" action="{{ route('company.bif.change-request.approve', ['company' => $company->id, 'bif' => $bif->id]) }}">
                                            @csrf
                                            <button type="submit" class="mb-2 h-10 w-full rounded-lg bg-amber-600 text-sm font-medium text-white hover:bg-amber-700">Approve Requested BIF Changes</button>
                                        </form>

                                        <form method="POST" action="{{ route('company.bif.change-request.reject', ['company' => $company->id, 'bif' => $bif->id]) }}" class="mb-2 space-y-2">
                                            @csrf
                                            <textarea name="change_rejection_reason" rows="2" class="w-full rounded-lg border border-red-200 px-3 py-2 text-xs outline-none focus:border-red-400 focus:ring-2 focus:ring-red-100" placeholder="Reason for rejecting requested changes" required>{{ old('change_rejection_reason') }}</textarea>
                                            <button type="submit" class="h-10 w-full rounded-lg bg-red-600 text-sm font-medium text-white hover:bg-red-700">Reject Requested BIF Changes</button>
                                        </form>
                                    @endif

                                    @if ($bifApproved)
                                        <button type="button" class="h-10 w-full rounded-lg border border-green-200 bg-green-100 text-sm font-medium text-green-700 cursor-not-allowed" disabled>Approved</button>
                                    @else
                                        <form id="approveCompanyKycForm" method="POST" action="{{ route('company.kyc.approve', $company->id) }}">
                                            @csrf
                                            <button type="submit" class="h-10 w-full rounded-lg bg-green-600 text-sm font-medium text-white {{ $canReviewDecision ? 'hover:bg-green-700' : 'cursor-not-allowed opacity-60' }}" @disabled(! $canReviewDecision)>Approve</button>
                                        </form>

                                        <form id="rejectCompanyKycForm" method="POST" action="{{ route('company.kyc.reject', $company->id) }}" class="space-y-2">
                                            @csrf
                                            <button type="submit" class="h-10 w-full rounded-lg bg-red-600 text-sm font-medium text-white {{ $canReviewDecision ? 'hover:bg-red-700' : 'cursor-not-allowed opacity-60' }}" @disabled(! $canReviewDecision)>Reject</button>
                                        </form>
                                    @endif
                                    @if (! $canReviewDecision && ! $bifApproved)
                                        <p class="text-xs text-gray-500">Approve/Reject is enabled only after user submits for verification.</p>
                                    @endif
                                @else
                                    @if ($bif && $bif->change_request_status === 'pending')
                                        <button type="button" class="h-10 w-full rounded-lg bg-amber-500 text-sm font-medium text-white cursor-not-allowed opacity-80" disabled>Waiting For Admin Decision</button>
                                    @elseif ($bifApproved)
                                        <button type="button" class="h-10 w-full rounded-lg border border-green-200 bg-green-100 text-sm font-medium text-green-700 cursor-not-allowed" disabled>Approved</button>
                                        <a href="{{ route('company.bif.edit', ['company' => $company->id, 'bif' => $bif->id]) }}" class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-blue-600 text-sm font-medium text-white hover:bg-blue-700">Request BIF Changes</a>
                                    @elseif ($bifPendingApproval)
                                        <button type="button" class="h-10 w-full rounded-lg bg-slate-500 text-sm font-medium text-white cursor-not-allowed opacity-80" disabled>Submitted For Approval</button>
                                    @else
                                        <form id="submitCompanyKycForm" method="POST" action="{{ route('company.kyc.submit', $company->id) }}">
                                            @csrf
                                            <button type="submit" class="h-10 w-full rounded-lg bg-blue-600 text-sm font-medium text-white {{ $bif ? 'hover:bg-blue-700' : 'cursor-not-allowed opacity-60' }}" @disabled(! $bif)>Submit For Verification</button>
                                        </form>
                                        @if ($bif && ! $requirementsComplete)
                                            <p class="text-xs text-amber-600">Recommendation: upload the remaining onboarding documents, but you may already submit this BIF for verification.</p>
                                        @endif
                                    @endif
                                @endif
                                <div class="pt-2">
                                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">KYC Activity</p>
                                    <div class="space-y-1 text-xs text-gray-500">
                                        @foreach ($kycActivityLogs as $log)
                                            <p>{{ $log }}</p>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-100 px-4 py-3">
                            <h2 class="text-base font-semibold text-gray-900">Business Onboarding Requirements</h2>
                            <p class="mt-1 text-xs text-gray-500">Upload and manage the required onboarding documents based on the business organization.</p>
                        </div>
                        <div id="companyKycRequirementsList" class="max-h-[520px] space-y-3 overflow-y-auto p-4">
                            @foreach ($kycRequirements as $group)
                                <section class="rounded-xl border border-gray-200 bg-white p-3">
                                    <div class="border-b border-gray-100 pb-3">
                                        <h3 class="text-sm font-semibold text-gray-900">{{ $group['group'] }}</h3>
                                    </div>
                                    <div class="space-y-3 pt-3">
                                        @foreach ($group['items'] as $requirement)
                                            <article class="rounded-xl border border-gray-200 bg-white p-3">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <p class="text-sm font-semibold text-gray-900">{{ $requirement['label'] }}</p>
                                                        <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium {{ $requirement['uploaded'] ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-gray-100 text-gray-600 border border-gray-200' }}">
                                                            {{ $requirement['uploaded'] ? 'Uploaded' : 'Missing' }}
                                                        </span>
                                                        <p class="mt-1 text-xs text-gray-500">{{ $requirement['helper'] }}</p>
                                                    </div>
                                                    <div class="flex items-center gap-2 text-xs">
                                                        @if ($requirement['template_url'] ?? null)
                                                            <a href="{{ $requirement['template_url'] }}" class="rounded-md border border-blue-200 px-2 py-1 text-blue-600 hover:bg-blue-50">
                                                                Preview Form
                                                            </a>
                                                            <a href="{{ $requirement['template_download_url'] }}" target="_blank" class="rounded-md bg-blue-600 px-2 py-1 text-white hover:bg-blue-700">
                                                                Download Form
                                                            </a>
                                                        @endif

                                                        @if ($canManageRequirementDocs)
                                                            <form method="POST" action="{{ route('company.kyc.requirements.upload', ['company' => $company->id, 'requirement' => $requirement['key']]) }}" enctype="multipart/form-data" class="inline-flex">
                                                                @csrf
                                                                <label class="cursor-pointer rounded-md border border-gray-200 px-2 py-1 text-gray-600 hover:bg-gray-50">
                                                                    {{ ($requirement['template_url'] ?? null) ? ($requirement['uploaded'] ? 'Replace Signed Copy' : 'Upload Signed Copy') : ($requirement['uploaded'] ? 'Replace' : 'Upload') }}
                                                                    <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="hidden" onchange="this.form.submit()">
                                                                </label>
                                                            </form>
                                                        @endif

                                                        @if ($requirement['uploaded'] && $requirement['file_url'])
                                                            <button
                                                                type="button"
                                                                class="rounded-md border border-gray-200 px-2 py-1 text-gray-600 hover:bg-gray-50"
                                                                data-open-requirement-view
                                                                data-view-url="{{ $requirement['file_url'] }}"
                                                                data-view-title="{{ $requirement['label'] }}"
                                                                data-view-file-name="{{ $requirement['file_name'] }}"
                                                                data-view-mime-type="{{ $requirement['mime_type'] }}"
                                                                data-view-uploaded-at="{{ $requirement['uploaded_at'] }}"
                                                                data-view-issued-by="{{ $requirement['issued_by'] }}"
                                                            >
                                                                View
                                                            </button>
                                                        @else
                                                            <button type="button" class="rounded-md border border-gray-200 px-2 py-1 text-gray-600 opacity-40 pointer-events-none">View</button>
                                                        @endif

                                                        @if ($canManageRequirementDocs)
                                                            <form method="POST" action="{{ route('company.kyc.requirements.remove', ['company' => $company->id, 'requirement' => $requirement['key']]) }}" class="inline-flex" onsubmit="return confirm('Remove this uploaded requirement file?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="rounded-md border border-red-200 px-2 py-1 text-red-600 {{ $requirement['uploaded'] ? 'hover:bg-red-50' : 'opacity-40 pointer-events-none' }}" @disabled(! $requirement['uploaded'])>Remove</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<x-slide-over id="sendBifModal" width="sm:max-w-[560px]">
    <div class="border-b border-gray-100 px-4 py-4 sm:px-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Send Business Information Form</h2>
                <p class="mt-1 text-sm text-gray-500">Email a secure BIF link to the client so they can complete missing business details and upload onboarding documents.</p>
            </div>
            <button type="button" data-close-send-bif-modal class="h-9 w-9 rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
    </div>

    <form id="sendBifForm" method="POST" action="{{ route('company.bif.send', $company->id) }}" class="flex min-h-0 flex-1 flex-col">
        @csrf
        <div class="flex-1 space-y-4 overflow-y-auto px-4 py-4 sm:px-6">
            <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                The secure link opens a mobile-friendly client form. Submitted details populate the Company KYC BIF record and uploaded documents are linked back to this company profile.
            </div>

            <div>
                <label for="recipient_email" class="mb-1 block text-sm font-medium text-gray-700">Recipient Email</label>
                <input id="recipient_email" name="recipient_email" type="email" value="{{ old('recipient_email', $bifRecipientEmail) }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" required>
                <p class="mt-1 text-xs text-gray-500">Defaults to the authorized contact person email from the BIF when available. You can change it before sending.</p>
                @error('recipient_email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-600">
                <p class="font-semibold uppercase tracking-wide text-gray-500">What the client can do</p>
                <p class="mt-2">Complete business organization, address, ownership structure, signatories, UBOs, and upload supporting documents based on business organization.</p>
            </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-close-send-bif-modal class="h-9 min-w-[100px] rounded-full border border-gray-200 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="h-9 min-w-[140px] rounded-full bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">
                    Send BIF Link
                </button>
            </div>
        </div>
    </form>
</x-slide-over>

<x-slide-over id="requirementViewModal" width="sm:max-w-[980px]">
    <div class="border-b border-gray-100 px-4 py-4 sm:px-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">View Document</h2>
            </div>
            <button type="button" data-close-requirement-view-modal class="h-9 w-9 rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
    </div>

    <div class="flex min-h-0 flex-1 flex-col">
        <div class="grid min-h-0 flex-1 grid-cols-1 gap-0 overflow-y-auto lg:grid-cols-[1.1fr_0.9fr]">
            <div class="border-b border-gray-100 p-5 lg:border-b-0 lg:border-r">
                <div id="requirementViewPanel" class="flex min-h-[520px] items-center justify-center rounded-xl border border-gray-200 bg-gray-50">
                    <p class="text-sm text-gray-500">No file selected.</p>
                </div>
            </div>
            <div class="space-y-3 p-5">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Document Title</p>
                    <p id="requirementViewTitle" class="mt-1 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-900">-</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Date Upload</p>
                    <p id="requirementViewUploadedAt" class="mt-1 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800">-</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Issued By</p>
                    <p id="requirementViewIssuedBy" class="mt-1 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800">-</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">MIME Type</p>
                    <p id="requirementViewMime" class="mt-1 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800">-</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">File Name</p>
                    <p id="requirementViewFileName" class="mt-1 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800">-</p>
                </div>
                <div>
                    <a id="requirementViewOpenLink" href="#" target="_blank" rel="noopener" class="inline-flex rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
                        Open file
                    </a>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-close-requirement-view-modal class="h-9 min-w-[100px] rounded-full border border-gray-200 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Close
                </button>
            </div>
        </div>
    </div>
</x-slide-over>

<div id="companySendSuccessOverlay" class="pointer-events-none fixed inset-0 z-[80] hidden items-start justify-center px-4 pt-8" aria-hidden="true">
    <div id="companySendSuccessCard" class="w-full max-w-sm translate-y-2 opacity-0 rounded-xl border border-gray-200 bg-white p-4 shadow-lg shadow-slate-200/70 transition duration-300">
        <div class="flex items-start gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white">
                <i class="fas fa-paper-plane text-xs"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-600">Client Outreach</p>
                <h3 class="mt-1 text-base font-semibold text-slate-900">Sent to client's email</h3>
                <p id="companySendSuccessText" class="mt-1 text-sm leading-5 text-slate-600"></p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sendBifModal = document.getElementById('sendBifModal');
        const requirementViewModal = document.getElementById('requirementViewModal');
        const openSendBifButtons = document.querySelectorAll('[data-open-send-bif-modal]');
        const closeSendBifButtons = document.querySelectorAll('[data-close-send-bif-modal]');
        const openRequirementViewButtons = document.querySelectorAll('[data-open-requirement-view]');
        const closeRequirementViewButtons = document.querySelectorAll('[data-close-requirement-view-modal]');
        const shouldOpenSendModal = @json($errors->has('recipient_email'));

        const openModal = () => window.jkncSlideOver.open(sendBifModal);
        const closeModal = () => window.jkncSlideOver.close(sendBifModal);
        const openRequirementModal = () => window.jkncSlideOver.open(requirementViewModal);
        const closeRequirementModal = () => window.jkncSlideOver.close(requirementViewModal);
        const requirementViewTitle = document.getElementById('requirementViewTitle');
        const requirementViewPanel = document.getElementById('requirementViewPanel');
        const requirementViewFileName = document.getElementById('requirementViewFileName');
        const requirementViewMime = document.getElementById('requirementViewMime');
        const requirementViewUploadedAt = document.getElementById('requirementViewUploadedAt');
        const requirementViewIssuedBy = document.getElementById('requirementViewIssuedBy');
        const requirementViewOpenLink = document.getElementById('requirementViewOpenLink');
        const liveFeedback = document.getElementById('companyKycLiveFeedback');
        const sendSuccessOverlay = document.getElementById('companySendSuccessOverlay');
        const sendSuccessCard = document.getElementById('companySendSuccessCard');
        const sendSuccessText = document.getElementById('companySendSuccessText');
        const sendBifForm = document.getElementById('sendBifForm');
        const submitCompanyKycForm = document.getElementById('submitCompanyKycForm');
        const approveCompanyKycForm = document.getElementById('approveCompanyKycForm');
        const rejectCompanyKycForm = document.getElementById('rejectCompanyKycForm');
        const companyBifStatusBadge = document.getElementById('companyBifStatusBadge');
        const companyBifSubmittedAt = document.getElementById('companyBifSubmittedAt');
        const companyBifClientSubmittedAt = document.getElementById('companyBifClientSubmittedAt');
        const statusBadgeClasses = {
            draft: 'bg-gray-100 text-gray-700 border border-gray-200',
            pending_approval: 'bg-amber-100 text-amber-700 border border-amber-200',
            approved: 'bg-green-100 text-green-700 border border-green-200',
            rejected: 'bg-red-100 text-red-700 border border-red-200',
        };

        const formatDate = (value) => {
            if (!value) {
                return '-';
            }
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return value;
            }
            return date.toLocaleString();
        };
        const formatDateOnly = (value) => {
            if (!value) return '-';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        };
        const showFeedback = (message, tone = 'success') => {
            if (!liveFeedback) return;
            const tones = {
                success: 'border-green-200 bg-green-50 text-green-700',
                error: 'border-red-200 bg-red-50 text-red-700',
                warning: 'border-amber-200 bg-amber-50 text-amber-800',
            };
            liveFeedback.className = `mb-4 rounded-md border px-4 py-3 text-sm font-medium ${tones[tone] || tones.success}`;
            liveFeedback.textContent = message || '';
            liveFeedback.classList.toggle('hidden', !message);
        };
        let sendSuccessTimeout = null;
        const showSendSuccess = (email) => {
            if (!sendSuccessOverlay || !sendSuccessCard || !sendSuccessText || !email) return;
            sendSuccessText.textContent = `The secure Business Information Form has been sent to ${email}.`;
            sendSuccessOverlay.classList.remove('hidden');
            sendSuccessOverlay.classList.add('flex');
            sendSuccessOverlay.setAttribute('aria-hidden', 'false');
            window.clearTimeout(sendSuccessTimeout);
            requestAnimationFrame(() => {
                sendSuccessCard.classList.remove('translate-y-2', 'opacity-0');
            });
            sendSuccessTimeout = window.setTimeout(() => {
                sendSuccessCard.classList.add('translate-y-2', 'opacity-0');
                window.setTimeout(() => {
                    sendSuccessOverlay.classList.add('hidden');
                    sendSuccessOverlay.classList.remove('flex');
                    sendSuccessOverlay.setAttribute('aria-hidden', 'true');
                }, 300);
            }, 3200);
        };
        const replaceHtmlIfPresent = (id, sourceDoc) => {
            const current = document.getElementById(id);
            const incoming = sourceDoc.getElementById(id);
            if (!current || !incoming) return;
            current.innerHTML = incoming.innerHTML;
        };
        const syncCompanyKycFragments = async () => {
            try {
                const refreshUrl = new URL(window.location.href);
                refreshUrl.searchParams.set('tab', 'business-client-information');
                refreshUrl.searchParams.set('_ts', String(Date.now()));
                const response = await fetch(refreshUrl.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    cache: 'no-store',
                });
                if (!response.ok) return;
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                replaceHtmlIfPresent('companyHeaderWrap', doc);
                replaceHtmlIfPresent('companyHeaderBreadcrumb', doc);
                replaceHtmlIfPresent('companyHeaderSummary', doc);
                replaceHtmlIfPresent('companyBifDocumentContent', doc);
                replaceHtmlIfPresent('companyBifSummaryCard', doc);
                replaceHtmlIfPresent('companyKycRequirementsList', doc);
            } catch (error) {
            }
        };
        const submitJsonForm = async (form, fallbackError) => {
            const response = await fetch(form.action, {
                method: form.method || 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
                cache: 'no-store',
            });

            if (response.status === 422) {
                const payload = await response.json();
                const firstError = Object.values(payload.errors || {}).flat()[0] || 'Please review the form.';
                throw new Error(firstError);
            }

            if (!response.ok) {
                throw new Error(fallbackError);
            }

            return response.json();
        };
        const syncBifState = (bif) => {
            if (!bif) return;
            const status = String(bif.status || '').toLowerCase();
            const label = status === 'pending_approval'
                ? 'Pending Approval'
                : status === 'approved'
                    ? 'Approved'
                    : status === 'rejected'
                        ? 'Rejected'
                        : 'Draft';
            if (companyBifStatusBadge) {
                companyBifStatusBadge.className = `inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${statusBadgeClasses[status] || statusBadgeClasses.draft}`;
                companyBifStatusBadge.textContent = label;
            }
            if (companyBifSubmittedAt) {
                companyBifSubmittedAt.textContent = formatDateOnly(bif.submitted_at);
            }
            if (companyBifClientSubmittedAt && bif.client_submitted_at !== undefined) {
                companyBifClientSubmittedAt.textContent = formatDate(bif.client_submitted_at);
            }
        };

        const renderRequirementPreview = (url, mimeType, fileName, title) => {
            if (!requirementViewPanel) {
                return;
            }

            const safeTitle = title || 'Requirement Document';
            const normalizedMime = (mimeType || '').toLowerCase();
            const normalizedName = (fileName || '').toLowerCase();
            const isPdf = normalizedMime.includes('pdf') || normalizedName.endsWith('.pdf');
            const isImage = normalizedMime.startsWith('image/')
                || normalizedName.endsWith('.jpg')
                || normalizedName.endsWith('.jpeg')
                || normalizedName.endsWith('.png')
                || normalizedName.endsWith('.gif')
                || normalizedName.endsWith('.webp');

            if (isPdf) {
                requirementViewPanel.innerHTML = `<iframe src="${url}" class="h-[520px] w-full rounded-lg border border-gray-200 bg-white"></iframe>`;
                return;
            }

            if (isImage) {
                requirementViewPanel.innerHTML = `<img src="${url}" alt="${safeTitle}" class="h-[520px] w-full rounded-lg border border-gray-200 bg-white object-contain">`;
                return;
            }

            requirementViewPanel.innerHTML = `
                <div class="px-4 text-center">
                    <p class="text-sm text-gray-600">Preview is not available for this file type.</p>
                    <a href="${url}" target="_blank" rel="noopener" class="mt-3 inline-flex rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">Open file</a>
                </div>
            `;
        };

        openSendBifButtons.forEach((button) => {
            button.addEventListener('click', openModal);
        });

        closeSendBifButtons.forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-open-requirement-view]');
            if (!button) return;

            const url = button.getAttribute('data-view-url') || '';
            const title = button.getAttribute('data-view-title') || 'Requirement Document';
            const fileName = button.getAttribute('data-view-file-name') || '';
            const mimeType = button.getAttribute('data-view-mime-type') || '';
            const uploadedAt = button.getAttribute('data-view-uploaded-at') || '';
            const issuedBy = button.getAttribute('data-view-issued-by') || '';

            if (requirementViewTitle) {
                requirementViewTitle.textContent = title;
            }
            if (requirementViewFileName) {
                requirementViewFileName.textContent = fileName || '-';
            }
            if (requirementViewMime) {
                requirementViewMime.textContent = mimeType || '-';
            }
            if (requirementViewUploadedAt) {
                requirementViewUploadedAt.textContent = formatDate(uploadedAt);
            }
            if (requirementViewIssuedBy) {
                requirementViewIssuedBy.textContent = issuedBy || '-';
            }
            if (requirementViewOpenLink) {
                requirementViewOpenLink.href = url || '#';
            }

            renderRequirementPreview(url, mimeType, fileName, title);
            openRequirementModal();
        });

        closeRequirementViewButtons.forEach((button) => {
            button.addEventListener('click', closeRequirementModal);
        });

        sendBifModal?.addEventListener('click', function (event) {
            if (event.target === sendBifModal || event.target.hasAttribute('data-drawer-overlay')) {
                closeModal();
            }
        });

        requirementViewModal?.addEventListener('click', function (event) {
            if (event.target === requirementViewModal || event.target.hasAttribute('data-drawer-overlay')) {
                closeRequirementModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeModal();
                closeRequirementModal();
            }
        });

        if (shouldOpenSendModal) {
            openModal();
        }

        const sessionClientEmail = @json(session('bif_client_email'));
        if (sessionClientEmail) {
            showSendSuccess(sessionClientEmail);
        }

        sendBifForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                const payload = await submitJsonForm(sendBifForm, 'Unable to send the BIF link.');
                showFeedback('');
                showSendSuccess(payload.recipient_email || '');
                syncCompanyKycFragments();
                closeModal();
            } catch (error) {
                showFeedback(error.message, 'error');
            }
        });

        submitCompanyKycForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                const payload = await submitJsonForm(submitCompanyKycForm, 'Unable to submit company KYC.');
                syncBifState(payload.bif || null);
                showFeedback(payload.warning || payload.message, payload.warning ? 'warning' : 'success');
                syncCompanyKycFragments();
            } catch (error) {
                showFeedback(error.message, 'error');
            }
        });

        approveCompanyKycForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                const payload = await submitJsonForm(approveCompanyKycForm, 'Unable to approve company KYC.');
                syncBifState(payload.bif || null);
                showFeedback(payload.message, 'success');
                syncCompanyKycFragments();
            } catch (error) {
                showFeedback(error.message, 'error');
            }
        });

        rejectCompanyKycForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                const payload = await submitJsonForm(rejectCompanyKycForm, 'Unable to reject company KYC.');
                syncBifState(payload.bif || null);
                showFeedback(payload.message, 'success');
                syncCompanyKycFragments();
            } catch (error) {
                showFeedback(error.message, 'error');
            }
        });
        window.setInterval(syncCompanyKycFragments, 10000);
    });
</script>
@endsection

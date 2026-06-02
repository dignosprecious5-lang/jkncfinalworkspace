@extends('layouts.app')

@section('content')
<div class="bg-[#f5f6f8] min-h-screen p-6" x-data="{ activeTab: 'policy' }">
    <div class="max-w-[1400px] mx-auto flex gap-6">

        {{-- LEFT DOCUMENT AREA --}}
        <div class="w-[70%] h-[calc(100vh-80px)] overflow-y-auto pr-2">
            <div class="mb-4 flex justify-between items-center">
                <a href="{{ route('admin.policies.index') }}"
                   class="border border-gray-300 px-4 py-2 rounded-lg text-sm hover:bg-gray-100">
                    ← Back
                </a>

                <div class="inline-flex rounded-xl border border-gray-200 bg-white p-1 shadow-sm">
                    <button
                        type="button"
                        @click="activeTab = 'policy'"
                        class="rounded-lg px-4 py-2 text-sm font-semibold transition"
                        :class="activeTab === 'policy' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50'"
                    >
                        Policy
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'attachment'"
                        class="rounded-lg px-4 py-2 text-sm font-semibold transition"
                        :class="activeTab === 'attachment' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50'"
                    >
                        Attachment
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'audit'"
                        class="rounded-lg px-4 py-2 text-sm font-semibold transition"
                        :class="activeTab === 'audit' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50'"
                    >
                        Audit Tracker
                    </button>
                </div>
            </div>

            {{-- POLICY TAB --}}
            <div x-show="activeTab === 'policy'" x-cloak>
                <div class="flex justify-center">
                    <div class="policy-paper bg-white border border-gray-300 shadow mb-6">

                        {{-- COVER PAGE --}}
                        <section class="policy-cover-page">
                            <div class="cover-logo">
                                <img src="{{ $policyLogoUrl ?? asset('images/jk-logo.png') }}" alt="John Kelly & Company Logo">
                            </div>

                            <div class="cover-company">
                                <p class="cover-company-name">JOHN KELLY &amp; COMPANY (JK&amp;C INC)</p>
                                <p>3F Cebu Holdings Center Cebu Business Park, Cebu City, Philippines, 6000</p>
                            </div>

                            <div class="cover-title">
                                <h1>{{ $policy->policy ?: 'POLICY TITLE' }}</h1>
                                <p>{{ $policy->policy_subtitle ?: 'Policy Document' }}</p>
                            </div>

                            <div class="cover-details">
                                <table>
                                    <tr>
                                        <td>Code</td>
                                        <td>{{ $policy->code ?: 'AUTO-GENERATED' }}</td>
                                    </tr>
                                    <tr>
                                        <td>Version</td>
                                        <td>{{ $policy->version ?: '1.0' }}</td>
                                    </tr>
                                    <tr>
                                        <td>Effectivity Date</td>
                                        <td>{{ $policy->effectivity_date ? \Carbon\Carbon::parse($policy->effectivity_date)->format('F d, Y') : '______________________________' }}</td>
                                    </tr>
                                    <tr>
                                        <td>Prepared by</td>
                                        <td>{{ $policy->prepared_by ?: '______________________________' }}</td>
                                    </tr>
                                    <tr>
                                        <td>Reviewed by</td>
                                        <td>{{ $policy->reviewed_by ?: '______________________________' }}</td>
                                    </tr>
                                    <tr>
                                        <td>Approved by</td>
                                        <td>{{ $policy->approved_by ?: '______________________________' }}</td>
                                    </tr>
                                    <tr>
                                        <td>Review Cycle</td>
                                        <td>{{ $policy->review_cycle ?: '______________________________' }}</td>
                                    </tr>
                                    <tr>
                                        <td>Classification</td>
                                        <td>{{ $policy->classification ?: 'Internal Use Only' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </section>

                        {{-- BODY PAGE --}}
                        <section class="policy-body-page">
                            <h2>{{ $policy->policy ?: 'POLICY TITLE' }}</h2>

                            <div class="description-content">
                                {!! $policy->description ?? '<p style="color:#777;">No description provided.</p>' !!}
                            </div>
                        </section>
                    </div>
                </div>
            </div>

            {{-- ATTACHMENT TAB --}}
            <div x-show="activeTab === 'attachment'" x-cloak>
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Uploaded Signed / Scanned Approved Policy</h3>
                            <p class="text-xs text-gray-500 mt-1">
                                The attachment is separated from the generated policy document.
                            </p>
                        </div>

                        @if(($policy->attachments ?? collect())->isNotEmpty() || $policy->attachment)
                            <a href="{{ asset('storage/'.$policy->attachment) }}"
                               target="_blank"
                               class="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100 transition">
                                <i class="fas fa-external-link-alt text-[10px]"></i>
                                Open Attachment
                            </a>
                        @endif
                    </div>

                    <div class="bg-gray-50 p-4">
                        @php
                            $policyAttachments = $policy->attachments ?? collect();
                            if ($policyAttachments->isEmpty() && $policy->attachment) {
                                $policyAttachments = collect([(object) [
                                    'file_path' => $policy->attachment,
                                    'original_name' => basename($policy->attachment),
                                ]]);
                            }
                        @endphp

                        @if($policyAttachments->isNotEmpty())
                            <div class="space-y-4">
                                @foreach($policyAttachments as $attachmentItem)
                                    @php
                                        $filePath = $attachmentItem->file_path ?? $attachmentItem->attachment ?? null;
                                        $fileName = $attachmentItem->original_name ?? basename($filePath ?? '');
                                        $ext = strtolower(pathinfo($filePath ?? '', PATHINFO_EXTENSION));
                                    @endphp

                                    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                                        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-800 truncate">{{ $fileName }}</p>
                                                <p class="text-[11px] text-gray-400 uppercase">{{ $ext ?: 'file' }}</p>
                                            </div>

                                            <a href="{{ asset('storage/'.$filePath) }}"
                                               target="_blank"
                                               class="shrink-0 inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100 transition">
                                                <i class="fas fa-external-link-alt text-[10px]"></i>
                                                Open
                                            </a>
                                        </div>

                                        <div class="bg-gray-50 p-4">
                                            @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                                                <div class="flex justify-center">
                                                    <img src="{{ asset('storage/'.$filePath) }}" class="max-w-full rounded-lg border border-gray-200 bg-white shadow-sm">
                                                </div>
                                            @elseif($ext === 'pdf')
                                                <iframe src="{{ asset('storage/'.$filePath) }}" class="w-full h-[620px] rounded-lg border border-gray-200 bg-white"></iframe>
                                            @else
                                                <div class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center">
                                                    <p class="text-sm text-gray-600 mb-3">Preview is not available for this file type.</p>
                                                    <a href="{{ asset('storage/'.$filePath) }}" target="_blank"
                                                       class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition">
                                                        <i class="fas fa-download text-xs"></i>
                                                        Download Attachment
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400">
                                    <i class="fas fa-paperclip text-lg"></i>
                                </div>
                                <h3 class="mt-4 text-base font-semibold text-gray-800">No attachment uploaded</h3>
                                <p class="mt-1 text-sm text-gray-500">The signed/scanned approved policy files will appear here once uploaded.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>


            {{-- AUDIT TRACKER TAB --}}
            <div x-show="activeTab === 'audit'" x-cloak>
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900">Audit Tracker</h3>
                        <p class="text-xs text-gray-500 mt-1">
                            Tracks admin actions, system user, date/time, IP address, and policy status changes.
                        </p>
                    </div>

                    <div class="p-5">
                        @php
                            $auditItems = $policyAudits ?? collect();
                        @endphp

                        @if($auditItems->isNotEmpty())
                            <div class="relative">
                                <div class="absolute left-[13px] top-0 bottom-0 w-px bg-gray-200"></div>

                                <div class="space-y-5">
                                    @foreach($auditItems as $audit)
                                        @php
                                            $actionClasses = match($audit->action) {
                                                'approved' => 'bg-green-100 text-green-700 ring-green-200',
                                                'rejected' => 'bg-red-100 text-red-700 ring-red-200',
                                                'revision_requested' => 'bg-yellow-100 text-yellow-700 ring-yellow-200',
                                                'archived' => 'bg-gray-200 text-gray-700 ring-gray-300',
                                                'unarchived' => 'bg-blue-100 text-blue-700 ring-blue-200',
                                                'updated' => 'bg-indigo-100 text-indigo-700 ring-indigo-200',
                                                'submitted' => 'bg-sky-100 text-sky-700 ring-sky-200',
                                                default => 'bg-slate-100 text-slate-700 ring-slate-200',
                                            };
                                        @endphp

                                        <div class="relative pl-10">
                                            <div class="absolute left-0 top-1.5 h-7 w-7 rounded-full bg-white ring-4 ring-gray-100 flex items-center justify-center">
                                                <span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span>
                                            </div>

                                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                                <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                                    <div class="min-w-0">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $actionClasses }}">
                                                                {{ ucwords(str_replace('_', ' ', $audit->action)) }}
                                                            </span>

                                                            <span class="text-xs text-gray-500">
                                                                {{ optional($audit->created_at)->format('M d, Y h:i A') }}
                                                            </span>
                                                        </div>

                                                        <p class="mt-2 text-sm font-medium text-gray-800">
                                                            {{ $audit->description ?? 'Policy action recorded.' }}
                                                        </p>

                                                        <div class="mt-3 grid grid-cols-1 gap-2 text-xs text-gray-600 md:grid-cols-2">
                                                            <div>
                                                                <span class="font-semibold text-gray-500">Performed by:</span>
                                                                {{ $audit->user->name ?? 'System / Unknown User' }}
                                                            </div>

                                                            <div>
                                                                <span class="font-semibold text-gray-500">User ID:</span>
                                                                {{ $audit->user_id ?? '-' }}
                                                            </div>

                                                            <div>
                                                                <span class="font-semibold text-gray-500">IP Address:</span>
                                                                {{ $audit->ip_address ?? '-' }}
                                                            </div>

                                                            <div class="min-w-0">
                                                                <span class="font-semibold text-gray-500">Device:</span>
                                                                <span class="break-words">{{ $audit->user_agent ?? '-' }}</span>
                                                            </div>
                                                        </div>

                                                        @if(!empty($audit->new_values))
                                                            <details class="mt-3 rounded-lg border border-gray-200 bg-white p-3">
                                                                <summary class="cursor-pointer text-xs font-semibold text-gray-600">
                                                                    View Snapshot
                                                                </summary>

                                                                <div class="mt-3 grid grid-cols-1 gap-2 text-xs text-gray-600 md:grid-cols-2">
                                                                    <div><span class="font-semibold">Status:</span> {{ $audit->new_values['workflow_status'] ?? '-' }}</div>
                                                                    <div><span class="font-semibold">Approval:</span> {{ $audit->new_values['approval_status'] ?? '-' }}</div>
                                                                    <div><span class="font-semibold">Reviewed By:</span> {{ $audit->new_values['reviewed_by'] ?? '-' }}</div>
                                                                    <div><span class="font-semibold">Approved By:</span> {{ $audit->new_values['approved_by'] ?? '-' }}</div>
                                                                    <div><span class="font-semibold">Code:</span> {{ $audit->new_values['code'] ?? '-' }}</div>
                                                                    <div><span class="font-semibold">Version:</span> {{ $audit->new_values['version'] ?? '-' }}</div>
                                                                </div>
                                                            </details>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-12 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-gray-400">
                                    <i class="fas fa-history text-lg"></i>
                                </div>
                                <h3 class="mt-4 text-base font-semibold text-gray-800">No audit records yet</h3>
                                <p class="mt-1 text-sm text-gray-500">Audit entries will appear here once actions are performed.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        {{-- RIGHT ADMIN DETAILS --}}
        <div class="w-[30%]">
            <div class="policy-details-panel bg-white border rounded-xl shadow p-5 sticky top-6 space-y-4">
                <h3 class="font-semibold text-lg">Policy Details</h3>

                <div class="text-sm space-y-3">
                    <div>
                        <p class="text-gray-500 text-xs">Policy Title</p>
                        <p><span class="policy-clamp-2">{{ $policy->policy ?? '-' }}</span></p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Code</p>
                        <p><span class="policy-clamp-1">{{ $policy->code ?? '-' }}</span></p>
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
                        <p><span class="policy-clamp-2">{{ $policy->prepared_by ?? '-' }}</span></p>
                    </div>

                    <div>
                        <p class="text-gray-500 text-xs">Reviewed By</p>
                        <p><span class="policy-clamp-2">{{ $policy->reviewed_by ?? '-' }}</span></p>
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

                        @if(($policy->attachments ?? collect())->isNotEmpty() || $policy->attachment)
                            <button type="button"
                               @click="activeTab = 'attachment'"
                               class="text-blue-600 hover:underline">
                                View Attachments
                            </button>
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
                        'policy_subtitle' => $policy->policy_subtitle,
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
    [x-cloak] {
        display: none !important;
    }

    .policy-paper {
        width: 210mm;
        min-height: 297mm;
        background: #fff;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        font-family: Georgia, "Times New Roman", serif;
        color: #000;
        font-size: 12pt;
        line-height: 1.45;
    }

    .policy-cover-page {
        min-height: 297mm;
        padding: 72px;
        position: relative;
        text-align: center;
        border-bottom: 1px solid #e5e7eb;
    }

    .cover-logo {
        margin-top: 16px;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
    }

    .cover-logo img {
        display: block;
        width: 260px;
        max-height: 120px;
        object-fit: contain;
        margin-left: auto;
        margin-right: auto;
    }

    .cover-company {
        margin-top: 10px;
        font-size: 11pt;
        line-height: 1.2;
    }

    .cover-company-name {
        font-weight: bold;
        margin: 0;
    }

    .cover-title {
        margin-top: 145px;
        line-height: 1.25;
    }

    .cover-title h1 {
        margin: 0;
        font-size: 12pt;
        font-weight: bold;
        text-transform: uppercase;
    }

    .cover-title p {
        margin: 2px 0 0;
        font-size: 10pt;
    }

    .cover-details {
        width: 485px;
        margin: 150px auto 0;
        text-align: left;
        font-size: 11pt;
    }

    .cover-details table {
        width: 100%;
        border-collapse: collapse;
    }

    .cover-details td {
        border: none;
        padding: 5px 8px;
        vertical-align: top;
    }

    .cover-details td:first-child {
        width: 150px;
    }

    .cover-details td:last-child {
        font-weight: bold;
    }

    .policy-body-page {
        min-height: 297mm;
        padding: 72px;
        page-break-before: always;
    }

    .policy-body-page h2 {
        margin: 0 0 24px 0;
        text-align: center;
        font-size: 14pt;
        font-weight: bold;
        text-transform: uppercase;
    }

    .description-content {
        width: 100%;
        font-size: 12pt;
        line-height: 1.45;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .description-content p {
        margin: 0 0 10px 0;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .description-content ul,
    .description-content ol {
        margin: 0 0 10px 22px;
        padding: 0;
    }

    .description-content li,
    .description-content span,
    .description-content div {
        overflow-wrap: anywhere;
        word-break: break-word;
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
        border: 1px solid #000 !important;
    }

    .description-content th,
    .description-content td {
        border: 1px solid #000 !important;
        padding: 7px !important;
        vertical-align: top !important;
        text-align: left !important;
        white-space: normal !important;
        word-break: break-word !important;
        overflow-wrap: anywhere !important;
    }

    .description-content th {
        background: #f3f3f3 !important;
        font-weight: bold !important;
    }

    @media (max-width: 1400px) {
        .policy-paper {
            width: 100%;
        }

        .cover-details {
            width: 90%;
        }
    }

    /* FINAL HARD FIX: keep long policy text inside cards, tables, side panels, and A4 preview */
    .policy-paper,
    .policy-paper *,
    #policy-preview-sheet,
    #policy-preview-sheet *,
    .policy-library,
    .policy-library *,
    .policy-dashboard-table,
    .policy-dashboard-table *,
    .policy-details-panel,
    .policy-details-panel *,
    table,
    table *,
    td,
    th {
        min-width: 0 !important;
        max-width: 100% !important;
        overflow-wrap: anywhere !important;
        word-break: break-word !important;
        white-space: normal !important;
    }

    .policy-paper,
    #policy-preview-sheet {
        overflow-x: hidden !important;
    }

    .cover-title,
    .cover-title h1,
    .cover-title p {
        width: 100% !important;
        max-width: 100% !important;
        overflow: hidden !important;
        text-align: center !important;
        overflow-wrap: anywhere !important;
        word-break: break-word !important;
        white-space: normal !important;
    }

    .cover-details {
        max-width: 485px !important;
        overflow: hidden !important;
    }

    .cover-details table {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed !important;
    }

    .cover-details td:first-child {
        width: 150px !important;
        min-width: 150px !important;
        max-width: 150px !important;
    }

    .cover-details td:last-child {
        width: auto !important;
        min-width: 0 !important;
        max-width: 335px !important;
        overflow: hidden !important;
    }

    .description-content,
    .description-content * {
        max-width: 100% !important;
        overflow-wrap: anywhere !important;
        word-break: break-word !important;
        white-space: normal !important;
    }

    .description-content {
        overflow-x: hidden !important;
    }

    /* Keep long text in the list/dashboard rows from destroying table layout */
    .policy-table-fixed {
        table-layout: fixed !important;
        width: 100% !important;
    }

    .policy-table-fixed td,
    .policy-table-fixed th {
        overflow: hidden !important;
        text-overflow: ellipsis;
        vertical-align: top !important;
    }

    .policy-break {
        display: block !important;
        max-width: 100% !important;
        overflow-wrap: anywhere !important;
        word-break: break-word !important;
        white-space: normal !important;
    }

    .policy-clamp-2 {
        display: -webkit-box !important;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden !important;
        overflow-wrap: anywhere !important;
        word-break: break-word !important;
    }

    .policy-clamp-1 {
        display: block !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
        max-width: 100% !important;
    }


    /* Policy module default font */
    .policy-paper,
    .policy-paper *,
    #policy-preview-sheet,
    #policy-preview-sheet *,
    .description-content,
    .description-content *,
    .policy-preview-body,
    .policy-preview-body * {
        font-family: Georgia, "Times New Roman", serif !important;
    }

    /* Quill editor Georgia font option */
    .ql-font-georgia,
    .ql-font-georgia * {
        font-family: Georgia, "Times New Roman", serif !important;
    }

    .ql-picker.ql-font .ql-picker-label[data-value="georgia"]::before,
    .ql-picker.ql-font .ql-picker-item[data-value="georgia"]::before {
        content: "Georgia";
        font-family: Georgia, "Times New Roman", serif;
    }

    .ql-picker.ql-font .ql-picker-label[data-value="serif"]::before,
    .ql-picker.ql-font .ql-picker-item[data-value="serif"]::before {
        content: "Serif";
    }

    .ql-picker.ql-font .ql-picker-label[data-value="sans-serif"]::before,
    .ql-picker.ql-font .ql-picker-item[data-value="sans-serif"]::before {
        content: "Sans Serif";
    }

    .ql-picker.ql-font .ql-picker-label[data-value="monospace"]::before,
    .ql-picker.ql-font .ql-picker-item[data-value="monospace"]::before {
        content: "Monospace";
    }

    #policy-editor .ql-editor {
        font-family: Georgia, "Times New Roman", serif !important;
    }


    /* Quill alignment support for live preview, show pages, and PDF */
    .ql-align-left {
        text-align: left !important;
    }

    .ql-align-center {
        text-align: center !important;
    }

    .ql-align-right {
        text-align: right !important;
    }

    .ql-align-justify {
        text-align: justify !important;
        text-justify: inter-word;
    }

    .description-content .ql-align-left,
    .policy-preview-body .ql-align-left,
    #policy-preview-sheet .ql-align-left {
        text-align: left !important;
    }

    .description-content .ql-align-center,
    .policy-preview-body .ql-align-center,
    #policy-preview-sheet .ql-align-center {
        text-align: center !important;
    }

    .description-content .ql-align-right,
    .policy-preview-body .ql-align-right,
    #policy-preview-sheet .ql-align-right {
        text-align: right !important;
    }

    .description-content .ql-align-justify,
    .policy-preview-body .ql-align-justify,
    #policy-preview-sheet .ql-align-justify {
        text-align: justify !important;
        text-justify: inter-word;
    }

</style>
@endpush

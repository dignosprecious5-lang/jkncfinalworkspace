@extends('layouts.app')
@section('title', 'NatGov Preview')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 mt-4" x-data="{ activeVersion: '{{ $approvedUrl ? 'approved' : 'draft' }}', selectedDraftUrl: @js($selectedDraftUrl), showApproveModal: false }" @keydown.escape.window="showApproveModal = false">
    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-100">
            <a href="{{ $backRoute }}" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <div class="text-lg font-semibold">NatGov Preview</div>
                <div class="text-xs text-gray-500">Company {{ $natgov->client ?? '-' }}</div>
            </div>
            <div class="flex-1"></div>
            <div class="inline-flex rounded-full bg-gray-100 p-1">
                <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-full" :class="activeVersion === 'draft' ? 'bg-white shadow text-gray-900' : 'text-gray-500'" @click="activeVersion = 'draft'">Draft</button>
                <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-full" :class="activeVersion === 'approved' ? 'bg-white shadow text-gray-900' : 'text-gray-500'" @click="activeVersion = 'approved'">Approved</button>
            </div>
            <a href="{{ $editRoute }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">Edit</a>
            <form method="POST" action="{{ $deleteRoute }}" onsubmit="return confirm('Delete this NatGov entry?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg">Delete</button>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 p-6">
            <div class="lg:col-span-3 space-y-4">
                <div x-show="activeVersion === 'draft'" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">Draft NatGov File</div>
                            <div class="text-xs text-slate-500">Choose any saved draft revision to review it here.</div>
                        </div>
                    </div>

                    @if (!empty($draftOptions) && count($draftOptions) > 1)
                        <div class="mt-3">
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Draft Revision Selector</label>
                            <select x-model="selectedDraftUrl" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                                @foreach ($draftOptions as $option)
                                    <option value="{{ $option['url'] }}">
                                        {{ $option['label'] }}@if(!empty($option['uploaded_at'])) • {{ $option['uploaded_at'] }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if (!empty($draftOptions))
                        <div class="mt-3">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Saved Draft Files</div>
                            <div class="mt-2 space-y-2">
                                @foreach ($draftOptions as $option)
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-lg border px-3 py-2 text-left text-sm transition"
                                        :class="selectedDraftUrl === @js($option['url']) ? 'border-blue-300 bg-blue-50 text-blue-900' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50'"
                                        @click="selectedDraftUrl = @js($option['url']); activeVersion = 'draft'"
                                    >
                                        <span class="min-w-0">
                                            <span class="block truncate font-medium">{{ $option['label'] }}</span>
                                            @if (!empty($option['uploaded_at']))
                                                <span class="mt-0.5 block text-xs text-slate-500">{{ $option['uploaded_at'] }}</span>
                                            @endif
                                        </span>
                                        <span class="ml-3 shrink-0 rounded-full px-2 py-1 text-[11px] font-semibold" :class="selectedDraftUrl === @js($option['url']) ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600'">Load</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($latestDraft)
                        <div class="mt-3 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600">
                            Latest draft: <span class="font-semibold text-slate-900">{{ $latestDraft['name'] ?? basename($latestDraft['path']) }}</span>
                            @if (!empty($latestDraft['uploaded_at']))
                                <span class="text-slate-400">• {{ $latestDraft['uploaded_at'] }}</span>
                            @endif
                        </div>
                    @endif

                    @if ($draftUrl)
                        <iframe :src="selectedDraftUrl" class="mt-4 w-full h-[700px] border rounded bg-white"></iframe>
                    @else
                        <div class="mt-4 w-full h-[700px] border rounded flex items-center justify-center bg-gray-50 text-gray-400 text-sm">No draft file available yet.</div>
                    @endif
                </div>

                <div x-show="activeVersion === 'approved'" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">Approved NatGov File</div>
                            <div class="text-xs text-slate-500">Only the latest uploaded approved file appears here even if multiple approved PDFs were saved.</div>
                        </div>
                    </div>
                    @if ($latestApproved)
                        <div class="mt-3 rounded-lg border border-emerald-200 bg-white px-3 py-2 text-xs text-emerald-700">
                            Latest approved: <span class="font-semibold text-slate-900">{{ $latestApproved['name'] ?? basename($latestApproved['path']) }}</span>
                            @if (!empty($latestApproved['uploaded_at']))
                                <span class="text-slate-400">• {{ $latestApproved['uploaded_at'] }}</span>
                            @endif
                        </div>
                    @endif
                    @if ($approvedUrl)
                        <iframe src="{{ $approvedUrl }}" class="mt-4 w-full h-[700px] border rounded bg-white"></iframe>
                    @else
                        <div class="mt-4 w-full h-[700px] border rounded flex items-center justify-center bg-gray-50 text-gray-400 text-sm">No approved file uploaded yet.</div>
                    @endif
                </div>
            </div>

            <div class="lg:col-span-2 space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div class="text-sm font-semibold text-gray-900 mb-3">NatGov Details</div>
                    <div class="space-y-2 text-sm">
                        <div><span class="text-xs text-gray-600 uppercase tracking-wide">Company</span><div class="font-medium text-gray-900">{{ $natgov->client ?? '-' }}</div></div>
                        <div><span class="text-xs text-gray-600 uppercase tracking-wide">Government Agency</span><div class="font-medium text-gray-900">{{ $natgov->agency ?? '-' }}</div></div>
                        <div><span class="text-xs text-gray-600 uppercase tracking-wide">Registration Number</span><div class="font-medium text-gray-900">{{ $natgov->registration_no ?? '-' }}</div></div>
                        <div><span class="text-xs text-gray-600 uppercase tracking-wide">Registration Date</span><div class="font-medium text-gray-900">{{ optional($natgov->registration_date)->format('M d, Y') ?? '-' }}</div></div>
                        <div><span class="text-xs text-gray-600 uppercase tracking-wide">Renewal Date</span><div class="font-medium text-gray-900">{{ optional($natgov->renewal_date ?? $natgov->deadline_date)->format('M d, Y') ?? '-' }}</div></div>
                        <div><span class="text-xs text-gray-600 uppercase tracking-wide">Status</span><div class="font-medium text-gray-900">{{ $natgov->display_status }}</div></div>
                    </div>

                    @if (auth()->user()?->isAdmin() || auth()->user()?->isSuperAdmin())
                        <div class="mt-4 flex items-center gap-2">
                            @if ($natgov->display_status === 'Approved')
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Approved</span>
                            @else
                                <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 hover:shadow-md" @click="showApproveModal = true">
                                    <i class="fas fa-check-circle text-[13px]"></i>
                                    Approve
                                </button>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-4">
                    <div class="text-sm font-semibold text-gray-900">Add More Draft Files</div>
                    <div class="mt-1 text-xs text-gray-500">You can keep uploading more draft revisions. Click any saved draft file and it will load in the preview immediately.</div>
                    <form method="POST" action="{{ $updateRoute }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="redirect_to" value="preview">
                        <input type="file" name="document_paths[]" accept="application/pdf" multiple class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-slate-700 file:text-white hover:file:bg-slate-800">
                        <button type="submit" class="w-full rounded-lg bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Upload Draft Revision{{ $draftDocuments->count() === 1 ? '' : 's' }}
                        </button>
                    </form>
                    @if ($draftDocuments->isNotEmpty())
                        <div class="mt-3 text-xs text-gray-500">Draft files saved: {{ $draftDocuments->count() }}</div>
                    @endif
                </div>

                <div class="bg-white border border-emerald-200 rounded-xl p-4">
                    <div class="text-sm font-semibold text-gray-900">Upload Approved File Later</div>
                    <div class="mt-1 text-xs text-gray-500">Use this when approved PDFs become available. The newest approved PDF becomes the visible approved preview.</div>
                    <form method="POST" action="{{ $updateRoute }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="redirect_to" value="preview">
                        <input type="file" name="approved_document_paths[]" accept="application/pdf" multiple class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-600 file:text-white hover:file:bg-emerald-700">
                        <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            {{ $natgov->approved_document_path ? 'Upload Approved Revision' : 'Upload Approved File' }}
                        </button>
                    </form>
                    @if ($approvedDocuments->isNotEmpty())
                        <div class="mt-3 text-xs text-gray-500">Approved files saved: {{ $approvedDocuments->count() }}</div>
                    @endif
                </div>

                <div class="bg-white border border-amber-200 rounded-xl p-4">
                    <div class="text-sm font-semibold text-gray-900">Authority Notes</div>
                    <div class="mt-1 text-xs text-gray-500">Each note is saved separately with its own visibility permission. Users will only see notes allowed for their role.</div>
                    <form method="POST" action="{{ route('natgov.notes.store', $natgov) }}" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label class="text-xs text-gray-600">Visible To Role</label>
                            <select name="visible_to_role" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                <option value="Admin">Admin</option>
                                <option value="Employee">Employee</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">New Note</label>
                            <textarea name="body" rows="5" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Write registration notes, follow-ups, or authority-specific remarks here..."></textarea>
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Send Note</button>
                    </form>

                    <div class="mt-4 space-y-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Visible Note List</div>
                        @forelse (($visibleAuthorityNotes ?? collect()) as $note)
                            <div class="rounded-lg border border-amber-100 bg-amber-50 px-3 py-3">
                                <div class="flex items-center justify-between gap-3 text-[11px] text-gray-500">
                                    <div>
                                        <span class="font-semibold text-gray-800">{{ $note->user?->name ?? 'Unknown User' }}</span>
                                        <span>({{ $note->user?->role ?? 'No Role' }})</span>
                                    </div>
                                    <div>{{ optional($note->created_at)->timezone('Asia/Manila')->format('M d, Y h:i A') }}</div>
                                </div>
                                <div class="mt-1 text-[11px] uppercase tracking-wide text-amber-700">Visible to {{ $note->visible_to_role }}</div>
                                <div class="mt-2 whitespace-pre-line text-sm text-gray-900">{{ $note->body }}</div>
                            </div>
                        @empty
                            <div class="rounded-lg border border-dashed border-gray-300 px-3 py-4 text-sm text-gray-500">
                                No visible authority notes yet for your role.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-cloak>
        <div x-show="showApproveModal" class="fixed inset-0 z-50 bg-slate-950/55 backdrop-blur-[2px]" @click="showApproveModal = false"></div>
        <div x-show="showApproveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div
                x-transition.opacity.duration.150ms
                x-transition.scale.origin.center.duration.180ms
                class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-[0_24px_80px_rgba(15,23,42,0.24)] ring-1 ring-slate-200"
                @click.stop
            >
                <div class="bg-gradient-to-r from-emerald-600 via-emerald-500 to-teal-500 px-6 py-5 text-white">
                    <div class="flex items-start gap-4">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/20">
                            <i class="fas fa-check text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-lg font-semibold leading-6">Approve NatGov Record?</div>
                            <div class="mt-1 text-sm text-white/85">This will mark the record as approved for compliance tracking and unlock the approved status flow.</div>
                        </div>
                        <button type="button" class="ml-auto text-white/80 transition hover:text-white" @click="showApproveModal = false" aria-label="Close approve dialog">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="px-6 py-5">
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 px-4 py-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">What happens next</div>
                        <ul class="mt-2 space-y-1 text-sm text-slate-700">
                            <li class="flex gap-2"><span class="mt-1 inline-block h-2 w-2 rounded-full bg-emerald-500"></span>The record becomes approved for the current NatGov workflow.</li>
                            <li class="flex gap-2"><span class="mt-1 inline-block h-2 w-2 rounded-full bg-emerald-500"></span>The status display updates without changing the uploaded files.</li>
                            <li class="flex gap-2"><span class="mt-1 inline-block h-2 w-2 rounded-full bg-emerald-500"></span>Only admins and superadmins can perform this action.</li>
                        </ul>
                    </div>

                    <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button type="button" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" @click="showApproveModal = false">
                            Cancel
                        </button>
                        <form method="POST" action="{{ route('natgov.approve', $natgov) }}">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 hover:shadow-md sm:w-auto">
                                <i class="fas fa-check-circle text-[13px]"></i>
                                Yes, Approve
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

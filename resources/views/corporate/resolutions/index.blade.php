@extends('layouts.app')
@section('title', 'Resolutions')

@section('content')
@php
    $today = now()->toDateString();
    $currentUser = auth()->user()?->name ?? '';
    $sectionRibbonPartial = $sectionRibbonPartial ?? 'corporate.partials.section-ribbon';
    $documentDefaultsUrl = $documentDefaultsUrl ?? route('corporate-document-defaults');
    $resolutionStoreUrl = $resolutionStoreUrl ?? route('resolutions.store');
    $minuteOptions = $minutes->map(fn ($minute) => [
        'id' => $minute->id,
        'minutes_ref' => $minute->minutes_ref,
        'notice_id' => $minute->notice_id,
        'notice_ref' => $minute->notice_ref,
        'governing_body' => $minute->governing_body,
        'type_of_meeting' => $minute->type_of_meeting,
        'meeting_no' => $minute->meeting_no,
        'date_of_meeting' => optional($minute->date_of_meeting)->toDateString(),
        'time_started' => $minute->time_started,
        'location' => $minute->location,
        'chairman' => $minute->chairman,
        'secretary' => $minute->secretary,
    ])->values();
@endphp

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4">
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
        @if (isset($company))
            @include('company.partials.company-header', ['company' => $company])
        @endif

        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100">
            @include($sectionRibbonPartial, ['activeTab' => 'resolution', 'topButtonLabel' => 'Add Resolution'])
        </div>
    </div>
</div>

<style>
    .resolution-rich-editor[contenteditable="true"][data-placeholder]:empty::before {
        content: attr(data-placeholder);
        color: #94a3b8;
        pointer-events: none;
    }
</style>

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4" x-data="resolutionForm({{ Js::from($minuteOptions) }}, @js($currentUser), @js($documentDefaultsUrl), @js($nextResolutionNumber ?? ''))" @keydown.escape.window="showAddPanel = false">
    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-100">
            <div class="text-lg font-semibold">Resolutions</div>
            <div class="flex-1"></div>
            <button type="button" @click="openPanel()" :disabled="!hasMinutes" :class="hasMinutes ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-gray-200 text-gray-400 cursor-not-allowed'" class="h-9 px-4 rounded-full text-sm font-medium flex items-center gap-2 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Add Resolution
            </button>
        </div>

        <div x-show="!hasMinutes" x-cloak class="mx-4 mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Add Minutes of Meeting first before creating a resolution. New resolutions now inherit their meeting details from an existing minutes record.
        </div>

        <div class="p-4">
            <div class="overflow-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Resolution No.</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Linked Notice</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Resolution</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Meeting</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Draft / Original</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Secretary Certs</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-gray-900">
                        @forelse ($resolutions as $resolution)
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ $resolution->preview_url ?? route('resolutions.preview', $resolution) }}'">
                                <td class="px-4 py-3 font-medium">{{ $resolution->resolution_no }}</td>
                                <td class="px-4 py-3">
                                    <div>{{ $resolution->notice_ref }}</div>
                                    <div class="text-xs text-gray-500">{{ $resolution->notice?->governing_body }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div>{{ $resolution->board_resolution }}</div>
                                    <div class="text-xs text-gray-500">{{ \Illuminate\Support\Str::limit(strip_tags($resolution->resolution_body), 80) }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div>{{ optional($resolution->date_of_meeting)->format('M d, Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $resolution->location }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <span class="inline-flex rounded-full px-2 py-1 font-semibold {{ $resolution->draft_file_path ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">Draft {{ $resolution->draft_file_path ? 'uploaded' : 'generated' }}</span>
                                    <span class="inline-flex rounded-full px-2 py-1 font-semibold {{ $resolution->notarized_file_path ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">Original {{ $resolution->notarized_file_path ? 'ready' : 'pending' }}</span>
                                </td>
                                <td class="px-4 py-3">{{ $resolution->secretaryCertificates->count() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">No resolutions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-cloak>
        <div x-show="showAddPanel" class="fixed inset-0 bg-black/40 z-40" @click="showAddPanel = false"></div>
        <div x-show="showAddPanel"
            class="fixed inset-y-0 right-0 w-full max-w-2xl bg-white shadow-2xl z-50 flex flex-col"
            x-transition:enter="transform transition ease-in-out duration-200"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            @click.stop
        >
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <div>
                    <div class="text-lg font-semibold">Add Resolution</div>
                    <div class="text-xs text-gray-500">Choose an existing minutes record and the linked meeting details will fill automatically for the resolution and downstream secretary certificates.</div>
                </div>
                <div class="flex-1"></div>
                <button class="text-gray-500 hover:text-gray-700" @click="showAddPanel = false" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="{{ $resolutionStoreUrl }}" enctype="multipart/form-data" class="flex-1 overflow-y-auto p-6 space-y-6">
                @csrf

                <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                    Select an existing minutes record first. The resolution now stays tied to that minutes entry, and the linked meeting fields below are auto-filled from it.
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-600">Resolution No.</label>
                        <input type="text" name="resolution_no" x-ref="resolutionNo" value="{{ $nextResolutionNumber ?? '' }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="RES-2026-001">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Date Uploaded</label>
                        <input type="date" name="date_uploaded" value="{{ $today }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Uploaded By</label>
                        <input type="text" name="uploaded_by" x-ref="uploadedBy" value="{{ $currentUser }}" data-default-field="current_user" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Uploader">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Linked Minutes</label>
                        <select name="minute_id" x-model="selectedMinuteId" @change="applyMinute()" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            <option value="">Select minutes</option>
                            <template x-for="minute in minutes" :key="minute.id">
                                <option :value="minute.id" x-text="`${minute.minutes_ref || 'Draft Minutes'} • ${minute.notice_ref || ''}`"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Only existing minutes can be linked when creating a new resolution.</p>
                    </div>
                    <input type="hidden" name="notice_id" x-ref="noticeId">
                    <div>
                        <label class="text-xs text-gray-600">Governing Body</label>
                        <select name="governing_body" x-ref="governingBody" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                            <option value="Stockholders">Stockholders</option>
                            <option value="Board of Directors">Board of Directors</option>
                            <option value="Joint Stockholders and Board of Directors">Joint Stockholders and Board of Directors</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Type of Meeting</label>
                        <select name="type_of_meeting" x-ref="meetingType" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                            <option value="Regular">Regular</option>
                            <option value="Special">Special</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Notice Ref #</label>
                        <input type="text" name="notice_ref" x-ref="noticeRef" readonly class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm" placeholder="NOTICE-2026-001">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Meeting No.</label>
                        <input type="text" name="meeting_no" x-ref="meetingNo" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Meeting Date</label>
                        <input type="date" name="date_of_meeting" x-ref="meetingDate" value="{{ $today }}" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Location</label>
                        <input type="text" name="location" x-ref="location" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-600">Board Resolution Title</label>
                        <input type="text" name="board_resolution" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Authority to transact with government agencies">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-600">Resolution Body</label>
                        <div class="mt-1 rounded-xl border border-gray-300 overflow-hidden">
                            <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 bg-gray-50 px-3 py-3">
                                <select class="rounded-lg border border-gray-300 px-2 py-1 text-xs" @change="applyResolutionFormat('fontName', $event.target.value)">
                                    <option value="">Font</option>
                                    <option value="Arial">Arial</option>
                                    <option value="Times New Roman">Times New Roman</option>
                                    <option value="Georgia">Georgia</option>
                                    <option value="Verdana">Verdana</option>
                                </select>
                                <select class="rounded-lg border border-gray-300 px-2 py-1 text-xs" @change="applyResolutionFormat('fontSize', $event.target.value)">
                                    <option value="">Size</option>
                                    <option value="2">12</option>
                                    <option value="3" selected>14</option>
                                    <option value="4">16</option>
                                    <option value="5">18</option>
                                </select>
                                <button type="button" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs font-semibold" @click="applyResolutionFormat('bold')">B</button>
                                <button type="button" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs italic" @click="applyResolutionFormat('italic')">I</button>
                                <button type="button" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs underline" @click="applyResolutionFormat('underline')">U</button>
                                <button type="button" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs" @click="applyResolutionFormat('insertUnorderedList')">Bullets</button>
                                <button type="button" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs" @click="applyResolutionFormat('insertOrderedList')">Numbering</button>
                                <button type="button" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs" @click="applyResolutionFormat('justifyLeft')">Left</button>
                                <button type="button" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs" @click="applyResolutionFormat('justifyCenter')">Center</button>
                                <button type="button" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs" @click="applyResolutionFormat('justifyRight')">Right</button>
                                <button type="button" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs" @click="applyResolutionFormat('removeFormat')">Clear</button>
                            </div>
                            <div
                                x-ref="resolutionEditor"
                                contenteditable="true"
                                data-placeholder="Write the full resolved clauses here. This will be used in the draft preview and as the basis for the secretary certificate."
                                class="resolution-rich-editor min-h-[260px] bg-white p-4 text-sm leading-7 outline-none"
                                @input="syncResolutionBody()"
                            ></div>
                            <input type="hidden" name="resolution_body" x-ref="resolutionBodyField">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Directors / Attendees</label>
                        <input type="text" name="directors" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Comma-separated names">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Chairman</label>
                        <input type="text" name="chairman" x-ref="chairman" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Secretary</label>
                        <input type="text" name="secretary" x-ref="secretary" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Notary Public</label>
                        <input type="text" name="notary_public" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Notary Public">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Doc No.</label>
                        <input type="text" name="notary_doc_no" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Page No.</label>
                        <input type="text" name="notary_page_no" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Book No.</label>
                        <input type="text" name="notary_book_no" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Series No.</label>
                        <input type="text" name="notary_series_no" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" value="{{ now()->year }}">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Notarized On</label>
                        <input type="date" name="notarized_on" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Notarized At</label>
                        <input type="text" name="notarized_at" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Cebu City">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Upload Draft (PDF)</label>
                        <input type="file" name="draft_file_path" accept="application/pdf" class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-slate-700 file:text-white hover:file:bg-slate-800">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Upload Original / Notarized (PDF)</label>
                        <input type="file" name="notarized_file_path" accept="application/pdf" class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 flex items-center gap-2 -mx-6 -mb-6">
                    <button class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-900 text-sm font-medium rounded-lg" @click="showAddPanel = false" type="button">
                        Cancel
                    </button>
                    <div class="flex-1"></div>
                    <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg" type="submit">
                        Save Resolution
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function resolutionForm(minutes, currentUser, defaultsEndpoint, initialResolutionNo) {
        return {
            showAddPanel: false,
            minutes,
            currentUser,
            defaultsEndpoint,
            initialResolutionNo,
            selectedMinuteId: '',
            openPanel() {
                if (!this.hasMinutes) {
                    return;
                }

                this.showAddPanel = true;
                if (this.$refs.resolutionNo) {
                    this.$refs.resolutionNo.value = this.initialResolutionNo || this.$refs.resolutionNo.value || '';
                }
                this.loadDefaults();

                if (this.$refs.uploadedBy) {
                    this.$refs.uploadedBy.value = this.currentUser || '';
                }

                if (this.$refs.resolutionEditor) {
                    this.$refs.resolutionEditor.innerHTML = '';
                }
                if (this.$refs.resolutionBodyField) {
                    this.$refs.resolutionBodyField.value = '';
                }

                if (!this.selectedMinuteId && this.minutes.length) {
                    this.selectedMinuteId = String(this.minutes[0].id);
                }

                this.$nextTick(() => this.applyMinute());
            },
            get hasMinutes() {
                return this.minutes.length > 0;
            },
            async loadDefaults() {
                if (!this.defaultsEndpoint) {
                    return;
                }

                try {
                    const res = await fetch(this.defaultsEndpoint);
                    if (!res.ok) {
                        return;
                    }

                    const defaults = await res.json();
                    if (this.$refs.resolutionNo) {
                        this.$refs.resolutionNo.value = defaults.resolution_no || this.initialResolutionNo || '';
                    }
                } catch (e) {
                    // ignore defaults errors
                }
            },
            applyMinute() {
                const selected = this.minutes.find((minute) => String(minute.id) === String(this.selectedMinuteId));
                if (!selected) {
                    this.$refs.noticeId.value = '';
                    this.$refs.noticeRef.value = '';
                    this.$refs.governingBody.value = 'Board of Directors';
                    this.$refs.meetingType.value = 'Regular';
                    this.$refs.meetingNo.value = '';
                    this.$refs.meetingDate.value = '';
                    this.$refs.location.value = '';
                    this.$refs.chairman.value = '';
                    this.$refs.secretary.value = '';
                    return;
                }

                this.$refs.noticeId.value = selected.notice_id || '';
                this.$refs.noticeRef.value = selected.notice_ref || '';
                this.$refs.governingBody.value = selected.governing_body || 'Board of Directors';
                this.$refs.meetingType.value = selected.type_of_meeting || 'Regular';
                this.$refs.meetingNo.value = selected.meeting_no || '';
                this.$refs.meetingDate.value = selected.date_of_meeting || '';
                this.$refs.location.value = selected.location || '';
                this.$refs.chairman.value = selected.chairman || '';
                this.$refs.secretary.value = selected.secretary || '';
            },
            applyResolutionFormat(command, value = null) {
                if (!this.$refs.resolutionEditor) {
                    return;
                }

                this.$refs.resolutionEditor.focus();
                document.execCommand(command, false, value);
                this.syncResolutionBody();
            },
            syncResolutionBody() {
                if (!this.$refs.resolutionBodyField || !this.$refs.resolutionEditor) {
                    return;
                }

                this.$refs.resolutionBodyField.value = String(this.$refs.resolutionEditor.innerHTML || '').trim();
            },
        };
    }
</script>
@endsection

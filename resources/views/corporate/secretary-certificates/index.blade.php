@extends('layouts.app')
@section('title', 'Secretary Certificates')

@section('content')
@php
    $today = now()->toDateString();
    $currentUser = auth()->user()?->name ?? '';
    $sectionRibbonPartial = $sectionRibbonPartial ?? 'corporate.partials.section-ribbon';
    $documentDefaultsUrl = $documentDefaultsUrl ?? route('corporate-document-defaults');
    $certificateStoreUrl = $certificateStoreUrl ?? route('secretary-certificates.store');
    $resolutionOptions = $resolutions->map(fn ($resolution) => [
        'id' => $resolution->id,
        'resolution_no' => $resolution->resolution_no,
        'notice_id' => $resolution->notice_id,
        'notice_ref' => $resolution->notice_ref,
        'governing_body' => $resolution->governing_body,
        'type_of_meeting' => $resolution->type_of_meeting,
        'meeting_no' => $resolution->meeting_no,
        'date_of_meeting' => optional($resolution->date_of_meeting)->toDateString(),
        'location' => $resolution->location,
        'board_resolution' => $resolution->board_resolution,
        'resolution_body' => $resolution->full_resolution_body ?? $resolution->resolution_body,
        'secretary' => $resolution->secretary,
        'notary_public' => $resolution->notary_public,
        'notarial_place' => $resolution->notarized_at ?: $resolution->location,
        'notary_doc_no' => $resolution->notary_doc_no,
        'notary_page_no' => $resolution->notary_page_no,
        'notary_book_no' => $resolution->notary_book_no,
        'notary_series_no' => $resolution->notary_series_no,
        'minute_id' => $resolution->minute_id,
        'minutes_ref' => $resolution->minute?->minutes_ref,
        'source_type' => 'resolution',
    ])->values();
    $minuteOptions = ($minutes ?? collect())->map(fn ($minute) => [
        'id' => $minute->id,
        'minutes_ref' => $minute->minutes_ref,
        'notice_id' => $minute->notice_id,
        'notice_ref' => $minute->notice_ref,
        'governing_body' => $minute->governing_body,
        'type_of_meeting' => $minute->type_of_meeting,
        'meeting_no' => $minute->meeting_no,
        'date_of_meeting' => optional($minute->date_of_meeting)->toDateString(),
        'location' => $minute->location,
        'secretary' => $minute->secretary,
        'source_type' => 'minutes',
    ])->values();
@endphp

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4">
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
        @if (isset($company))
            @include('company.partials.company-header', ['company' => $company])
        @endif

        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100">
            @include($sectionRibbonPartial, ['activeTab' => 'secretary', 'topButtonLabel' => 'Add Certificate'])
        </div>
    </div>
</div>

<style>
    .secretary-rich-editor[contenteditable="true"][data-placeholder]:empty::before {
        content: attr(data-placeholder);
        color: #94a3b8;
        pointer-events: none;
    }
</style>

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4" x-data="secretaryCertificateForm({{ Js::from($resolutionOptions) }}, {{ Js::from($minuteOptions) }}, @js($documentDefaultsUrl), @js($nextCertificateNumber ?? ''))" @keydown.escape.window="showAddPanel = false">
    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-100">
            <div class="text-lg font-semibold">Secretary Certificates</div>
            <div class="flex-1"></div>
            <button type="button" @click="openPanel()" class="h-9 px-4 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Add Certificate
            </button>
        </div>

        <div class="p-4">
            <div class="overflow-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Certificate No.</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Linked Resolution</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Purpose</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Meeting</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Original Upload</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-gray-900">
                        @forelse ($certificates as $certificate)
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ $certificate->preview_url ?? route('secretary-certificates.preview', $certificate) }}'">
                                <td class="px-4 py-3 font-medium">{{ $certificate->certificate_no }}</td>
                                <td class="px-4 py-3">
                                    <div>{{ $certificate->resolution_no }}</div>
                                    <div class="text-xs text-gray-500">{{ $certificate->minutes_ref ?: $certificate->notice_ref }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $certificate->purpose }}</td>
                                <td class="px-4 py-3">
                                    <div>{{ optional($certificate->date_of_meeting)->format('M d, Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $certificate->location }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $certificate->document_path ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $certificate->document_path ? 'Original uploaded' : 'Draft only' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">No certificates found.</td>
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
                    <div class="text-lg font-semibold">Add Secretary Certificate</div>
                    <div class="text-xs text-gray-500">Choose a resolution and the shared details will fill automatically.</div>
                </div>
                <div class="flex-1"></div>
                <button class="text-gray-500 hover:text-gray-700" @click="showAddPanel = false" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="{{ $certificateStoreUrl }}" enctype="multipart/form-data" class="flex-1 overflow-y-auto p-6 space-y-6" @submit="prepareSubmit()">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-600">Certificate No.</label>
                        <input type="text" name="certificate_no" x-ref="certificateNo" value="{{ $nextCertificateNumber ?? '' }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="SEC-2026-001">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Date Uploaded</label>
                        <input type="date" name="date_uploaded" value="{{ $today }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Uploaded By</label>
                        <input type="text" name="uploaded_by" value="{{ $currentUser }}" data-default-field="current_user" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Certificate Source</label>
                        <select x-model="sourceType" @change="applySource()" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            <option value="resolution">Resolution + Minutes</option>
                            <option value="minutes">Minutes Only</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Linked Resolution</label>
                        <select name="resolution_id" x-model="selectedResolutionId" @change="applyResolution()" :disabled="sourceType !== 'resolution'" :class="sourceType !== 'resolution' ? 'bg-gray-50 text-gray-400' : ''" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            <option value="">Select a resolution</option>
                            <template x-for="resolution in resolutions" :key="resolution.id">
                                <option :value="resolution.id" x-text="`${resolution.resolution_no || 'Draft Resolution'} • ${resolution.board_resolution || ''}`"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Linked Minutes</label>
                        <select name="minute_id" x-model="selectedMinuteId" @change="applyMinute()" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            <option value="">Select minutes</option>
                            <template x-for="minute in minutes" :key="minute.id">
                                <option :value="minute.id" x-text="`${minute.minutes_ref || 'Draft Minutes'} • ${minute.notice_ref || ''}`"></option>
                            </template>
                        </select>
                    </div>
                    <input type="hidden" name="notice_id" x-ref="noticeId">
                    <div>
                        <label class="text-xs text-gray-600">Notice Ref #</label>
                        <input type="text" name="notice_ref" x-ref="noticeRef" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Minutes Ref</label>
                        <input type="text" name="minutes_ref" x-ref="minutesRef" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Resolution No.</label>
                        <input type="text" name="resolution_no" x-ref="resolutionNo" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Governing Body</label>
                        <select name="governing_body" x-ref="governingBody" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            <option value="Stockholders">Stockholders</option>
                            <option value="Board of Directors">Board of Directors</option>
                            <option value="Joint Stockholders and Board of Directors">Joint Stockholders and Board of Directors</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Type of Meeting</label>
                        <select name="type_of_meeting" x-ref="meetingType" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            <option value="Regular">Regular</option>
                            <option value="Special">Special</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Meeting No.</label>
                        <input type="text" name="meeting_no" x-ref="meetingNo" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Date Issued</label>
                        <input type="date" name="date_issued" value="{{ $today }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-600">Purpose</label>
                        <input type="text" name="purpose" x-ref="purpose" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-600">Certificate Body</label>
                        <input type="hidden" name="resolution_body" x-ref="resolutionBodyInput">
                        <div class="mt-1 overflow-hidden rounded-xl border border-gray-300 bg-white">
                            <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 bg-gray-50 px-3 py-3">
                                <select @change="applyBodyFormat('fontName', $event.target.value)" class="rounded-lg border border-gray-300 px-2 py-1 text-xs">
                                    <option value="Arial">Arial</option>
                                    <option value="Times New Roman">Times New Roman</option>
                                    <option value="Georgia">Georgia</option>
                                    <option value="Verdana">Verdana</option>
                                </select>
                                <select @change="applyBodyFormat('fontSize', $event.target.value)" class="rounded-lg border border-gray-300 px-2 py-1 text-xs">
                                    <option value="2">12</option>
                                    <option value="3" selected>14</option>
                                    <option value="4">16</option>
                                    <option value="5">18</option>
                                </select>
                                <button type="button" @click="applyBodyFormat('bold')" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs font-semibold">B</button>
                                <button type="button" @click="applyBodyFormat('italic')" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs italic">I</button>
                                <button type="button" @click="applyBodyFormat('underline')" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs underline">U</button>
                                <button type="button" @click="applyBodyFormat('insertUnorderedList')" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs">Bullets</button>
                                <button type="button" @click="applyBodyFormat('insertOrderedList')" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs">Numbering</button>
                                <button type="button" @click="applyBodyFormat('justifyLeft')" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs">Left</button>
                                <button type="button" @click="applyBodyFormat('justifyCenter')" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs">Center</button>
                                <button type="button" @click="applyBodyFormat('justifyRight')" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs">Right</button>
                                <button type="button" @click="applyBodyFormat('removeFormat')" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs">Clear</button>
                            </div>
                            <div x-ref="resolutionBodyEditor" @input="syncBody()" class="secretary-rich-editor min-h-[260px] px-4 py-3 text-sm leading-7 text-gray-900 outline-none" contenteditable="true" data-placeholder="Auto-filled from the linked Resolution. Add details only if needed..."></div>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Meeting Date</label>
                        <input type="date" name="date_of_meeting" x-ref="meetingDate" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Location</label>
                        <input type="text" name="location" x-ref="location" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Secretary</label>
                        <input type="text" name="secretary" x-ref="secretary" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Secretary Address</label>
                        <input type="text" name="secretary_address" x-ref="secretaryAddress" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Auto-filled from GIS if available">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Secretary TIN</label>
                        <input type="text" name="secretary_tin" x-ref="secretaryTin" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Auto-filled from GIS if available">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Notarial Place / City</label>
                        <input type="text" name="notarial_place" x-ref="notarialPlace" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Cebu City, Philippines">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Notary Public</label>
                        <input type="text" name="notary_public" x-ref="notaryPublic" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Doc No.</label>
                        <input type="text" name="notary_doc_no" x-ref="docNo" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Page No.</label>
                        <input type="text" name="notary_page_no" x-ref="pageNo" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Book No.</label>
                        <input type="text" name="notary_book_no" x-ref="bookNo" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Series No.</label>
                        <input type="text" name="notary_series_no" x-ref="seriesNo" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-600">Upload Original Certificate (PDF)</label>
                        <input type="file" name="document_path" accept="application/pdf" class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 flex items-center gap-2 -mx-6 -mb-6">
                    <button class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-900 text-sm font-medium rounded-lg" @click="showAddPanel = false" type="button">
                        Cancel
                    </button>
                    <div class="flex-1"></div>
                    <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg" type="submit">
                        Save Certificate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function secretaryCertificateForm(resolutions, minutes, defaultsEndpoint, initialCertificateNo) {
        return {
            showAddPanel: false,
            resolutions,
            minutes,
            defaultsEndpoint,
            initialCertificateNo,
            selectedResolutionId: '',
            selectedMinuteId: '',
            sourceType: 'resolution',
            resolutionBodyHtml: '',
            openPanel() {
                this.showAddPanel = true;
                if (this.$refs.certificateNo) {
                    this.$refs.certificateNo.value = this.initialCertificateNo || this.$refs.certificateNo.value || '';
                }
                this.loadDefaults();
                this.$nextTick(() => {
                    this.applySource();
                    this.syncBody();
                });
            },
            normalizeEditorHtml(html) {
                return String(html ?? '')
                    .replace(/<div><br><\/div>/gi, '')
                    .replace(/<p><br><\/p>/gi, '')
                    .trim();
            },
            syncBody() {
                this.resolutionBodyHtml = this.normalizeEditorHtml(this.$refs.resolutionBodyEditor?.innerHTML || '');
                if (this.$refs.resolutionBodyInput) {
                    this.$refs.resolutionBodyInput.value = this.resolutionBodyHtml;
                }
            },
            applyBodyFormat(command, value = null) {
                if (!this.$refs.resolutionBodyEditor) {
                    return;
                }

                this.$refs.resolutionBodyEditor.focus();
                document.execCommand(command, false, value);
                this.syncBody();
            },
            prepareSubmit() {
                this.syncBody();
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
                    if (this.$refs.certificateNo) {
                        this.$refs.certificateNo.value = defaults.certificate_no || this.initialCertificateNo || '';
                    }
                } catch (e) {
                    // ignore defaults errors
                }
            },
            applySource() {
                if (this.sourceType === 'resolution') {
                    if (!this.selectedResolutionId && this.resolutions.length) {
                        this.selectedResolutionId = String(this.resolutions[0].id);
                    }
                    this.applyResolution();
                    return;
                }

                this.selectedResolutionId = '';
                this.$refs.resolutionNo.value = '';
                if (!this.selectedMinuteId && this.minutes.length) {
                    this.selectedMinuteId = String(this.minutes[0].id);
                }
                this.applyMinute();
            },
            applyResolution() {
                if (this.sourceType !== 'resolution') {
                    return;
                }

                const selected = this.resolutions.find((resolution) => String(resolution.id) === String(this.selectedResolutionId));
                if (!selected) {
                    return;
                }

                this.selectedMinuteId = selected.minute_id ? String(selected.minute_id) : '';
                this.$refs.noticeId.value = selected.notice_id || '';
                this.$refs.noticeRef.value = selected.notice_ref || '';
                this.$refs.minutesRef.value = selected.minutes_ref || '';
                this.$refs.resolutionNo.value = selected.resolution_no || '';
                this.$refs.governingBody.value = selected.governing_body || 'Board of Directors';
                this.$refs.meetingType.value = selected.type_of_meeting || 'Regular';
                this.$refs.meetingNo.value = selected.meeting_no || '';
                this.$refs.meetingDate.value = selected.date_of_meeting || '';
                this.$refs.location.value = selected.location || '';
                this.$refs.purpose.value = selected.board_resolution || '';
                if (this.$refs.resolutionBodyEditor) {
                    this.$refs.resolutionBodyEditor.innerHTML = selected.resolution_body || selected.board_resolution || '';
                }
                this.$refs.secretary.value = selected.secretary || '';
                if (this.$refs.secretaryAddress) this.$refs.secretaryAddress.value = '';
                if (this.$refs.secretaryTin) this.$refs.secretaryTin.value = '';
                if (this.$refs.notarialPlace) this.$refs.notarialPlace.value = selected.notarial_place || selected.location || 'Cebu City, Philippines';
                this.$refs.notaryPublic.value = selected.notary_public || '';
                this.$refs.docNo.value = selected.notary_doc_no || '';
                this.$refs.pageNo.value = selected.notary_page_no || '';
                this.$refs.bookNo.value = selected.notary_book_no || '';
                this.$refs.seriesNo.value = selected.notary_series_no || '';
                this.syncBody();
            },
            applyMinute() {
                const selected = this.minutes.find((minute) => String(minute.id) === String(this.selectedMinuteId));
                if (!selected) {
                    return;
                }

                this.$refs.noticeId.value = selected.notice_id || '';
                this.$refs.noticeRef.value = selected.notice_ref || '';
                this.$refs.minutesRef.value = selected.minutes_ref || '';
                this.$refs.resolutionNo.value = this.sourceType === 'minutes' ? '' : this.$refs.resolutionNo.value;
                this.$refs.governingBody.value = selected.governing_body || 'Board of Directors';
                this.$refs.meetingType.value = selected.type_of_meeting || 'Regular';
                this.$refs.meetingNo.value = selected.meeting_no || '';
                this.$refs.meetingDate.value = selected.date_of_meeting || '';
                this.$refs.location.value = selected.location || '';
                if (this.sourceType === 'minutes') {
                    this.$refs.purpose.value = `Certified extract from Minutes Ref. ${selected.minutes_ref || ''}`;
                    if (this.$refs.resolutionBodyEditor) {
                        this.$refs.resolutionBodyEditor.innerHTML = `Certified from Minutes Ref. ${selected.minutes_ref || ''}.`;
                    }
                }
                this.$refs.secretary.value = selected.secretary || '';
                if (this.$refs.secretaryAddress) this.$refs.secretaryAddress.value = '';
                if (this.$refs.secretaryTin) this.$refs.secretaryTin.value = '';
                if (this.$refs.notarialPlace) this.$refs.notarialPlace.value = selected.location || 'Cebu City, Philippines';
                if (this.sourceType === 'minutes') {
                    this.$refs.notaryPublic.value = '';
                    this.$refs.docNo.value = '';
                    this.$refs.pageNo.value = '';
                    this.$refs.bookNo.value = '';
                    this.$refs.seriesNo.value = '';
                }
                this.syncBody();
            },
        };
    }
</script>
@endsection

@extends('layouts.app')
@section('title', 'Notices')

@section('content')
@php
    $today = now()->toDateString();
    $currentUser = auth()->user()?->name ?? '';
    $defaultNoticeBodyText = '';
    $sectionRibbonPartial = $sectionRibbonPartial ?? 'corporate.partials.section-ribbon';
    $documentDefaultsUrl = $documentDefaultsUrl ?? route('corporate-document-defaults');
    $noticeStoreUrl = $noticeStoreUrl ?? route('notices.store');

    // President requested company name
    $companyName = $companyName ?? 'JK&C INC.';
    $companyRegNo = $companyRegNo ?? '2025120230900-02';
    $companyAddress = $companyAddress ?? '3RD FLOOR, UNIT 305 CEBU HOLDINGS CENTER CARDINAL ROSALES AVE., CEBU BUSINESS PARK HIPPODROMO, CEBU CITY, 6000';
@endphp

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4">
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
        @if (isset($company))
            @include('company.partials.company-header', ['company' => $company])
        @endif

        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100">
            @include($sectionRibbonPartial, ['activeTab' => 'notices', 'topButtonLabel' => 'Add Notice'])
        </div>
    </div>
</div>

<style>
    .rich-editor[contenteditable="true"][data-placeholder]:empty::before {
        content: attr(data-placeholder);
        color: #94a3b8;
        pointer-events: none;
    }

    .notice-preview-body,
    .notice-preview-body * {
        max-width: 100% !important;
        box-sizing: border-box !important;
        white-space: normal !important;
        overflow-wrap: anywhere !important;
        word-wrap: break-word !important;
        word-break: break-word !important;
    }

    .notice-preview-body p,
    .notice-preview-body div,
    .notice-preview-body span,
    .notice-preview-body li,
    .notice-preview-body ul,
    .notice-preview-body ol,
    .notice-preview-body pre,
    .notice-preview-body code,
    .notice-preview-body blockquote {
        max-width: 100% !important;
        white-space: normal !important;
        overflow-wrap: anywhere !important;
        word-wrap: break-word !important;
        word-break: break-word !important;
    }

    .notice-preview-body table {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
    }

    .notice-preview-body td,
    .notice-preview-body th {
        max-width: 100% !important;
        white-space: normal !important;
        overflow-wrap: anywhere !important;
        word-wrap: break-word !important;
        word-break: break-word !important;
        vertical-align: top !important;
        border: 1px solid #111827 !important;
        padding: 8px !important;
    }

    .notice-preview-body img,
    .notice-preview-body iframe,
    .notice-preview-body embed,
    .notice-preview-body object,
    .notice-preview-body video {
        max-width: 100% !important;
        height: auto !important;
    }

    .rich-editor {
        white-space: normal !important;
        overflow-wrap: anywhere !important;
        word-wrap: break-word !important;
        word-break: break-word !important;
    }

    .rich-editor,
    .rich-editor * {
        max-width: 100% !important;
        box-sizing: border-box !important;
        white-space: normal !important;
        overflow-wrap: anywhere !important;
        word-wrap: break-word !important;
        word-break: break-word !important;
    }

    .rich-editor p,
    .rich-editor div,
    .rich-editor span,
    .rich-editor li,
    .rich-editor ul,
    .rich-editor ol,
    .rich-editor pre,
    .rich-editor code,
    .rich-editor blockquote {
        max-width: 100% !important;
        white-space: normal !important;
        overflow-wrap: anywhere !important;
        word-wrap: break-word !important;
        word-break: break-word !important;
    }

    .rich-editor table {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
    }

    .rich-editor td,
    .rich-editor th {
        max-width: 100% !important;
        white-space: normal !important;
        overflow-wrap: anywhere !important;
        word-wrap: break-word !important;
        word-break: break-word !important;
        vertical-align: top !important;
        border: 1px solid #111827 !important;
        padding: 8px !important;
    }

    .rich-editor img {
        max-width: 100% !important;
        height: auto !important;
    }
</style>

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4"
    x-data="noticeComposer(@js($documentDefaultsUrl), @js($nextNoticeNumber ?? ''))"
    @keydown.escape.window="showAddPanel = false">
    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-100">
            <div class="text-lg font-semibold">Notices of Meeting</div>
            <div class="flex-1"></div>
            <button type="button" @click="openPanel()" class="h-9 px-4 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Add Notice
            </button>
        </div>

        <div class="p-4">
            <div class="overflow-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Notice #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Meeting</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Schedule</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Location</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Body / File</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Linked Records</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-gray-900">
                        @forelse ($notices as $notice)
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ $notice->preview_url ?? route('notices.preview', $notice) }}'">
                                <td class="px-4 py-3 font-medium">{{ $notice->notice_number ?: 'Draft Notice' }}</td>
                                <td class="px-4 py-3">
                                    <div>{{ $notice->governing_body }}</div>
                                    <div class="text-xs text-gray-500">{{ $notice->type_of_meeting }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div>{{ optional($notice->date_of_meeting)->format('M d, Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $notice->time_started }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $notice->location }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ ($notice->body_mode === 'upload') ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ ($notice->body_mode === 'upload') ? 'Uploaded PDF' : 'Built in editor' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600">
                                    Minutes: {{ $notice->minutes->count() }}<br>
                                    Resolutions: {{ $notice->resolutions->count() }}<br>
                                    Sec. Certs: {{ $notice->secretaryCertificates->count() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">No notices found.</td>
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
            class="fixed inset-y-0 right-0 w-full max-w-[96rem] bg-white shadow-2xl z-50 flex flex-col"
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
                    <div class="text-lg font-semibold">Add Notice</div>
                    <div class="text-xs text-gray-500">Upload the original PDF or compose the notice body here.</div>
                </div>
                <div class="flex-1"></div>
                <button class="text-gray-500 hover:text-gray-700" @click="showAddPanel = false" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="{{ $noticeStoreUrl }}" enctype="multipart/form-data" class="flex-1 overflow-y-auto p-6" @submit="prepareSubmit()">
                @csrf

                <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.7fr)_minmax(420px,0.95fr)] gap-6 min-h-[calc(100vh-12rem)]">
                    <div class="rounded-2xl border border-slate-200 overflow-hidden bg-[#f7f7fb] flex flex-col">
                        <div class="px-5 py-4 border-b border-slate-200 bg-white">
                            <div class="text-sm font-semibold text-slate-900">Live Notice Preview</div>
                            <div class="mt-1 text-xs text-slate-500">This updates in real time from the slider and uses the same company notice layout as the saved preview.</div>
                        </div>

                        <div class="flex-1 overflow-auto p-10">
                            <div class="mx-auto min-h-full max-w-[920px] bg-white px-16 py-14 text-[15px] leading-8 text-slate-900 shadow-[0_18px_50px_rgba(15,23,42,0.08)] overflow-hidden">
                                <div class="text-center leading-6">
                                    <div class="text-[17px] font-bold uppercase tracking-[0.04em]">{{ $companyName }}</div>
                                    <div class="text-[14px] font-bold">COMPANY REG. NO.: {{ $companyRegNo }}</div>
                                    <div class="mt-1 text-[14px]">{{ $companyAddress }}</div>
                                </div>

                                <div class="mt-12 text-center text-[17px] font-bold uppercase leading-7" x-text="livePreviewTitle"></div>

                                <div class="mt-12 space-y-5 text-[15px]">
                                    <div><span class="font-bold">To:</span> <span class="ml-2 font-bold" x-text="livePreviewRecipient"></span></div>
                                    <div><span class="font-bold">Date:</span> <span class="ml-2 font-bold" x-text="livePreviewDate"></span></div>
                                </div>

                                <div class="mt-10 text-[15px] leading-8">
                                    <p class="font-bold" x-text="livePreviewIntro"></p>
                                    <div class="mt-5">
                                        <div class="font-semibold">Agenda:</div>
                                        <div class="mt-2 notice-preview-body" x-html="livePreviewBody"></div>
                                    </div>
                                </div>

                                <div class="mt-20">
                                    <div>Very truly yours,</div>
                                    <div class="mt-12 text-[18px] font-bold" x-text="livePreviewSecretary"></div>
                                    <div class="text-sm text-slate-600">Corporate Secretary</div>
                                </div>

                                <div class="mt-16 flex items-end justify-between gap-6 border-t border-slate-200 pt-4 text-[11px] leading-4 text-slate-600">
                                    <div class="min-w-0">
                                        <div class="font-bold uppercase break-words" x-text="livePreviewFooterTitle"></div>
                                        <div>{{ $companyName }}</div>
                                        <div>Company Reg. No.: {{ $companyRegNo }}</div>
                                        <div>{{ $companyAddress }}</div>
                                    </div>
                                    <div class="font-bold shrink-0">Page 1 of 1</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden flex flex-col">
                        <div class="flex-1 overflow-y-auto">
                            <div class="px-6 py-5 space-y-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs text-gray-600">Notice #</label>
                                        <input type="text" name="notice_number" x-ref="noticeNumber" value="{{ $nextNoticeNumber ?? '' }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="2026-001">
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-600">Date Notice</label>
                                        <input type="date" name="date_of_notice" value="{{ $today }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-600">Date Updated</label>
                                        <input type="date" name="date_updated" value="{{ $today }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-600">Upload Notice (PDF)</label>
                                        <input type="file" name="document_path" accept="application/pdf" class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white hover:file:bg-blue-700" @change="bodyMode = 'upload'">
                                    </div>
                                </div>

                                <div>
                                    <label class="text-xs text-gray-600">Body Source</label>
                                    <select name="body_mode" x-model="bodyMode" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                        <option value="builder">Create in slider</option>
                                        <option value="upload">Use uploaded PDF</option>
                                    </select>
                                </div>

                                <div class="rounded-2xl border border-gray-200 overflow-hidden sticky top-0 bg-white z-10 shadow-sm">
                                    <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                                        <div class="text-sm font-semibold text-gray-900">Notice Body Builder</div>
                                        <div class="mt-1 text-xs text-gray-500">Write the body here with formatting tools. The saved notice preview will use this exact builder content.</div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 px-4 py-3 bg-white">
                                        <select class="rounded-lg border border-gray-300 px-2 py-1 text-xs" @change="applyFormat('fontName', $event.target.value)">
                                            <option value="">Font</option>
                                            <option value="Arial">Arial</option>
                                            <option value="Times New Roman">Times New Roman</option>
                                            <option value="Georgia">Georgia</option>
                                            <option value="Verdana">Verdana</option>
                                        </select>

                                        <select class="rounded-lg border border-gray-300 px-2 py-1 text-xs" @change="applyFormat('fontSize', $event.target.value)">
                                            <option value="">Size</option>
                                            <option value="2">12</option>
                                            <option value="3" selected>14</option>
                                            <option value="4">16</option>
                                            <option value="5">18</option>
                                        </select>

                                        <button type="button" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs font-semibold" @click="applyFormat('bold')">B</button>
                                        <button type="button" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs italic" @click="applyFormat('italic')">I</button>
                                        <button type="button" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs underline" @click="applyFormat('underline')">U</button>
                                        <button type="button" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs" @click="applyFormat('insertUnorderedList')">Bullets</button>
                                        <button type="button" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs" @click="applyFormat('insertOrderedList')">Numbering</button>
                                        <button type="button" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs" @click="applyFormat('justifyLeft')">Left</button>
                                        <button type="button" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs" @click="applyFormat('justifyCenter')">Center</button>
                                        <button type="button" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs" @click="applyFormat('justifyRight')">Right</button>
                                        <button type="button" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs" @click="clearEditor()">Clear</button>
                                    </div>

                                    <div
                                        x-ref="editor"
                                        contenteditable="true"
                                        data-placeholder="Type the notice body here..."
                                        class="rich-editor min-h-[360px] w-full overflow-y-auto bg-white p-4 text-sm leading-7 outline-none"
                                        @focus="bodyMode = 'builder'"
                                        @input="bodyMode = 'builder'; syncBody()"
                                        @paste="handlePaste($event)"
                                    ></div>

                                    <input x-ref="bodyField" :value="bodyHtml" type="hidden" name="body_html">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs text-gray-600">Governing Body</label>
                                        <select name="governing_body" @change="syncLivePreview()" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                            <option value="Stockholders">Stockholders</option>
                                            <option value="Board of Directors">Board of Directors</option>
                                            <option value="Joint Stockholders and Board of Directors">Joint Stockholders and Board of Directors</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-600">Type of Meeting</label>
                                        <select name="type_of_meeting" @change="syncLivePreview()" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                            <option value="Regular">Regular</option>
                                            <option value="Special">Special</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-600">Meeting Date</label>
                                        <input type="date" name="date_of_meeting" value="{{ $today }}" @input="syncLivePreview()" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-600">Time</label>
                                        <input type="time" name="time_started" @input="syncLivePreview()" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-600">Meeting #</label>
                                        <input type="text" name="meeting_no" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="25th Annual Meeting">
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-600">Chairman</label>
                                        <input type="text" name="chairman" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Chairman">
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-600">Secretary</label>
                                        <input type="text" name="secretary" @input="syncLivePreview()" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Corporate Secretary">
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-600">Uploaded By</label>
                                        <input type="text" name="uploaded_by" value="{{ $currentUser }}" data-default-field="current_user" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Uploader">
                                    </div>
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-4">
                                    <div>
                                        <label class="text-xs text-gray-600">Meeting Location</label>
                                        <p class="mt-1 text-xs text-gray-500">Fill in the venue details below. These will be combined into the saved location field.</p>
                                    </div>

                                    <input type="hidden" name="location" x-ref="locationField">

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="text-xs text-gray-600">1. Venue Name</label>
                                            <input type="text" x-model="locationParts.venue" @input="syncLocation()" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="ABC Building">
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-600">2. Room / Floor</label>
                                            <input type="text" x-model="locationParts.room" @input="syncLocation()" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="3rd Floor, Conference Room A">
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-600">3. Street Address</label>
                                            <input type="text" x-model="locationParts.street" @input="syncLocation()" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="123 Cardinal Rosales Ave.">
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-600">4. City / Municipality</label>
                                            <input type="text" x-model="locationParts.city" @input="syncLocation()" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Cebu City">
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-600">5. Province</label>
                                            <input type="text" x-model="locationParts.province" @input="syncLocation()" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Cebu">
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-600">6. Country</label>
                                            <input type="text" x-model="locationParts.country" @input="syncLocation()" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Philippines">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="text-xs text-gray-600">Saved Location Preview</label>
                                        <div class="mt-1 rounded-md border border-dashed border-gray-300 bg-white px-3 py-2 text-sm text-gray-700" x-text="locationPreview || 'Location will be generated from the fields above.'"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 flex items-center gap-2 -mx-6 -mb-6">
                    <button class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-900 text-sm font-medium rounded-lg" @click="showAddPanel = false" type="button">
                        Cancel
                    </button>
                    <div class="flex-1"></div>
                    <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg" type="submit">
                        Save Notice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function noticeComposer(defaultsEndpoint, initialNoticeNumber) {
        return {
            showAddPanel: false,
            defaultsEndpoint,
            initialNoticeNumber,

            companyName: @js($companyName),

            bodyMode: 'builder',
            bodyHtml: '',
            livePreviewTitle: 'NOTICE AND AGENDA OF THE SPECIAL BOARD OF DIRECTORS MEETING',
            livePreviewFooterTitle: 'NOTICE FOR SPECIAL BOARD OF DIRECTORS MEETING',
            livePreviewRecipient: 'ALL DIRECTORS',
            livePreviewDate: '________________',
            livePreviewIntro: @js("NOTICE is hereby given that a Special Board of Directors Meeting of {$companyName} will be held at __________________ on __________________ at __________________."),
            livePreviewBody: '<p style="color:#94a3b8;">Start typing the notice body to preview it here.</p>',
            livePreviewSecretary: 'Corporate Secretary',
            locationParts: {
                venue: '',
                room: '',
                street: '',
                city: '',
                province: '',
                country: 'Philippines',
            },
            locationPreview: '',
            defaultBodyText: @js($defaultNoticeBodyText),

            openPanel() {
                this.showAddPanel = true;
                this.$nextTick(() => {
                    if (this.$refs.noticeNumber) {
                        this.$refs.noticeNumber.value = this.initialNoticeNumber || this.$refs.noticeNumber.value || '';
                    }

                    this.bodyMode = 'builder';
                    this.bodyHtml = '';

                    if (this.$refs.editor) {
                        this.$refs.editor.innerHTML = '<p><br></p>';
                        this.normalizeEditorDom(this.$refs.editor);
                    }

                    this.loadDefaults();
                    this.syncBody();
                    this.syncLocation();
                    this.syncLivePreview();
                });
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
                    if (this.$refs.noticeNumber) {
                        this.$refs.noticeNumber.value = defaults.notice_number || this.initialNoticeNumber || '';
                    }
                } catch (e) {
                    // ignore defaults errors
                }
            },

            handlePaste() {
                setTimeout(() => {
                    this.syncBody();
                }, 0);
            },

            clearEditor() {
                if (!this.$refs.editor) {
                    return;
                }

                this.$refs.editor.innerHTML = '<p><br></p>';
                this.syncBody();
            },

            syncBody() {
                if (!this.$refs.editor) {
                    this.bodyHtml = '';
                    this.syncLivePreview();
                    return;
                }

                this.normalizeEditorDom(this.$refs.editor);
                this.bodyHtml = this.normalizeEditorHtml(this.$refs.editor.innerHTML || '');
                this.syncLivePreview();
            },

            prepareSubmit() {
                this.syncBody();
            },

            applyFormat(command, value = null) {
                if (!this.$refs.editor) {
                    return;
                }

                this.bodyMode = 'builder';
                this.$refs.editor.focus();
                document.execCommand(command, false, value);

                this.$nextTick(() => {
                    this.syncBody();
                });
            },

            normalizeEditorDom(root) {
                if (!root) return;

                root.querySelectorAll('*').forEach((el) => {
                    el.style.maxWidth = '100%';
                    el.style.boxSizing = 'border-box';
                    el.style.whiteSpace = 'normal';
                    el.style.overflowWrap = 'anywhere';
                    el.style.wordWrap = 'break-word';
                    el.style.wordBreak = 'break-word';

                    if (el.tagName === 'TABLE') {
                        el.style.width = '100%';
                        el.style.maxWidth = '100%';
                        el.style.tableLayout = 'fixed';
                        el.style.borderCollapse = 'collapse';
                    }

                    if (el.tagName === 'TD' || el.tagName === 'TH') {
                        el.style.border = el.style.border || '1px solid #111827';
                        el.style.padding = el.style.padding || '8px';
                        el.style.verticalAlign = 'top';
                        el.style.whiteSpace = 'normal';
                        el.style.overflowWrap = 'anywhere';
                        el.style.wordWrap = 'break-word';
                        el.style.wordBreak = 'break-word';
                    }

                    if (el.tagName === 'IMG') {
                        el.style.maxWidth = '100%';
                        el.style.height = 'auto';
                    }

                    el.removeAttribute('width');
                });

                if ((root.innerHTML || '').trim() === '') {
                    root.innerHTML = '<p><br></p>';
                }
            },

            normalizeEditorHtml(html) {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = String(html ?? '');

                wrapper.querySelectorAll('script').forEach((el) => el.remove());

                wrapper.querySelectorAll('*').forEach((el) => {
                    el.style.maxWidth = '100%';
                    el.style.boxSizing = 'border-box';
                    el.style.whiteSpace = 'normal';
                    el.style.overflowWrap = 'anywhere';
                    el.style.wordWrap = 'break-word';
                    el.style.wordBreak = 'break-word';

                    if (el.tagName === 'TABLE') {
                        el.style.width = '100%';
                        el.style.maxWidth = '100%';
                        el.style.tableLayout = 'fixed';
                        el.style.borderCollapse = 'collapse';
                    }

                    if (el.tagName === 'TD' || el.tagName === 'TH') {
                        el.style.border = '1px solid #111827';
                        el.style.padding = '8px';
                        el.style.verticalAlign = 'top';
                        el.style.whiteSpace = 'normal';
                        el.style.overflowWrap = 'anywhere';
                        el.style.wordWrap = 'break-word';
                        el.style.wordBreak = 'break-word';
                    }

                    if (el.tagName === 'IMG') {
                        el.style.maxWidth = '100%';
                        el.style.height = 'auto';
                    }

                    el.removeAttribute('width');
                });

                const normalized = wrapper.innerHTML
                    .replace(/<div><br><\/div>/gi, '')
                    .replace(/<p><br><\/p>/gi, '<p>&nbsp;</p>')
                    .trim();

                return normalized || '<p>&nbsp;</p>';
            },

            syncLivePreview() {
                const governingBodyField = document.querySelector('select[name="governing_body"]');
                const meetingTypeField = document.querySelector('select[name="type_of_meeting"]');
                const meetingDateField = document.querySelector('input[name="date_of_meeting"]');
                const meetingTimeField = document.querySelector('input[name="time_started"]');
                const secretaryField = document.querySelector('input[name="secretary"]');

                const governingBody = governingBodyField?.value || 'Board of Directors';
                const meetingType = meetingTypeField?.value || 'Special';
                const meetingDate = meetingDateField?.value || '';
                const meetingTime = meetingTimeField?.value || '';
                const secretary = secretaryField?.value || 'Corporate Secretary';

                const recipientLabel = governingBody === 'Stockholders'
                    ? 'ALL STOCKHOLDERS'
                    : (governingBody === 'Joint Stockholders and Board of Directors'
                        ? 'ALL STOCKHOLDERS AND DIRECTORS'
                        : 'ALL DIRECTORS');

                const formattedDate = meetingDate
                    ? new Date(`${meetingDate}T00:00:00`).toLocaleDateString('en-US', { month: 'long', day: '2-digit', year: 'numeric' })
                    : '________________';

                const formattedTime = meetingTime || '________________';
                const meetingTitle = `${meetingType} ${governingBody} Meeting`.toUpperCase();

                this.livePreviewTitle = `NOTICE AND AGENDA OF THE ${meetingTitle}`;
                this.livePreviewFooterTitle = `NOTICE FOR ${meetingTitle}`;
                this.livePreviewRecipient = recipientLabel;
                this.livePreviewDate = formattedDate;
                this.livePreviewIntro = `NOTICE is hereby given that a ${meetingType} ${governingBody} Meeting of ${this.companyName} will be held at ${this.locationPreview || '________________'} on ${formattedDate} at ${formattedTime}.`;
                this.livePreviewBody = this.bodyHtml || '<p style="color:#94a3b8;">Start typing the notice body to preview it here.</p>';
                this.livePreviewSecretary = secretary;
            },

            syncLocation() {
                const parts = [
                    this.locationParts.venue,
                    this.locationParts.room,
                    this.locationParts.street,
                    this.locationParts.city,
                    this.locationParts.province,
                    this.locationParts.country || 'Philippines',
                ]
                .map((value) => (value || '').trim())
                .filter(Boolean);

                this.locationPreview = parts.join(', ');

                if (this.$refs.locationField) {
                    this.$refs.locationField.value = this.locationPreview;
                }

                this.syncLivePreview();
            },
        };
    }
</script>
@endsection

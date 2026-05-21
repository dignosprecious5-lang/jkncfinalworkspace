@extends('layouts.app')
@section('title', 'Resolution Preview')

@section('content')
@php
    $sectionRibbonPartial = $sectionRibbonPartial ?? 'corporate.partials.section-ribbon';
    $draftPathCandidates = collect([
        $resolution->draft_file_path,
        preg_replace('#^/?storage/#', '', (string) $resolution->draft_file_path),
    ])
        ->filter()
        ->unique()
        ->values();
    $resolvedDraftPath = $draftPathCandidates->first(fn ($path) => \Illuminate\Support\Facades\Storage::disk('public')->exists($path));
    $draftUrl = $resolvedDraftPath ? route('uploads.show', ['path' => $resolvedDraftPath]) : null;

    $notarizedPathCandidates = collect([
        $resolution->notarized_file_path,
        preg_replace('#^/?storage/#', '', (string) $resolution->notarized_file_path),
    ])
        ->filter()
        ->unique()
        ->values();
    $resolvedNotarizedPath = $notarizedPathCandidates->first(fn ($path) => \Illuminate\Support\Facades\Storage::disk('public')->exists($path));
    $notarizedUrl = $resolvedNotarizedPath ? route('uploads.show', ['path' => $resolvedNotarizedPath]) : null;
    $clauseText = trim((string) $resolution->resolution_body);
    $companyName = config('app.name', 'JK&C INC.');
    $meetingDate = optional($resolution->date_of_meeting)->format('F d, Y') ?: '________________';
    $notaryYear = optional($resolution->notarized_on)->format('Y') ?: now()->year;
    $initialDraftPane = $draftUrl ? 'attachment' : 'live';
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
    @media print {
        body * {
            visibility: hidden;
        }

        #resolution-print,
        #resolution-print * {
            visibility: visible;
        }

        #resolution-print {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            box-shadow: none !important;
            filter: none !important;
        }
    }

    .resolution-rich-editor[contenteditable="true"][data-placeholder]:empty::before {
        content: attr(data-placeholder);
        color: #94a3b8;
        pointer-events: none;
    }

    .resolution-inline-editor[contenteditable="true"]:focus {
        outline: 2px solid #93c5fd;
        outline-offset: 2px;
        border-radius: 4px;
        background: rgba(239, 246, 255, 0.9);
    }

    .resolution-inline-editor[contenteditable="true"][data-placeholder]:empty::before {
        content: attr(data-placeholder);
        color: #94a3b8;
        pointer-events: none;
    }

    .resolution-workspace-card {
        min-height: calc(100vh - 15rem);
    }
</style>

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4" x-data="{ activeVersion: 'draft', activeDraftPane: '{{ $initialDraftPane }}' }">
    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-100">
            <a href="{{ $backRoute }}" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <div class="text-lg font-semibold">Resolution Preview</div>
                <div class="text-xs text-gray-500">Resolution No. {{ $resolution->resolution_no ?: 'Draft' }}</div>
            </div>
            <div class="flex-1"></div>
            <div class="inline-flex rounded-full bg-gray-100 p-1">
                <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-full" :class="activeVersion === 'draft' ? 'bg-white shadow text-gray-900' : 'text-gray-500'" @click="activeVersion = 'draft'">Draft</button>
                <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-full" :class="activeVersion === 'original' ? 'bg-white shadow text-gray-900' : 'text-gray-500'" @click="activeVersion = 'original'">Original / Notarized</button>
            </div>
        </div>

        <div class="space-y-6 p-6">
            <div x-show="activeVersion === 'draft'" class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.7fr)_minmax(420px,0.95fr)] gap-6 min-h-[calc(100vh-15rem)]">
                <div>
                    <div class="rounded-2xl border border-slate-200 overflow-hidden bg-[#f8fafc] flex flex-col resolution-workspace-card">
                        <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center gap-3 bg-white">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Template Builder Page</div>
                                <div class="text-xs text-gray-500">This page mirrors the resolution draft layout and updates in real time while you build the body.</div>
                            </div>
                            <div class="flex-1"></div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Live Template</span>
                            @if ($draftUrl)
                                <button
                                    type="button"
                                    class="rounded-full px-3 py-1 text-xs font-semibold transition"
                                    :class="activeDraftPane === 'attachment' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600'"
                                    @click="activeDraftPane = activeDraftPane === 'attachment' ? 'live' : 'attachment'">
                                    <span x-text="activeDraftPane === 'attachment' ? 'Back To Live Template' : 'Open Attached Draft PDF'"></span>
                                </button>
                            @endif
                        </div>
                        <div class="flex-1 overflow-auto p-6">
                            @if ($generatedBodyPreviewUrl)
                                <div class="mb-4 flex flex-wrap items-center justify-end gap-2">
                                    <a href="{{ $generatedBodyPreviewUrl }}" target="_blank" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">
                                        Download Built PDF
                                    </a>
                                    <button type="button" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-900 text-sm font-medium rounded-lg" onclick="printResolutionBuiltPdf('{{ $generatedBodyPreviewUrl }}')">
                                        Print Built PDF
                                    </button>
                                </div>
                            @endif

                            <div x-show="activeDraftPane === 'live'">
                                <div id="resolution-print" class="mx-auto max-w-[860px] rounded-sm bg-white px-14 py-12 shadow-[0_18px_50px_rgba(15,23,42,0.08)] min-h-[920px] text-[13px] leading-6 text-gray-900" style="font-family: Georgia, 'Times New Roman', serif;">
                            <div class="text-center text-black">
                                <div class="leading-none tracking-tight">
                                    <div class="text-[4.25rem] font-normal">John Kelly</div>
                                    <div class="flex items-end justify-center gap-3">
                                        <span class="text-[3.25rem] leading-none font-semibold text-blue-600">&amp;</span>
                                        <span class="text-[4.25rem] leading-none font-normal">Company</span>
                                    </div>
                                </div>
                                <div class="mt-2 text-[2rem] font-medium tracking-tight">JK&amp;C INC.</div>
                                <div class="mt-3 text-sm">COMPANY REG. NO.: 2025120230900-02</div>
                                <div class="mt-1 text-sm">3RD FLOOR, UNIT 305 CEBU HOLDINGS CENTER CARDINAL ROSALES AVE CEBU</div>
                                <div class="text-sm">BUSINESS PARK HIPPODROMO, CEBU CITY (Capital), CEBU, REGION VII</div>
                                <div class="text-sm">(CENTRAL VISAYAS), 6000;</div>
                            </div>

                            <div class="mt-12 text-center border-b border-black pb-2">
                                <div class="text-lg font-semibold uppercase underline">Board Resolution No. <span data-preview="resolution-no">{{ $resolution->resolution_no ?: '25-002' }}</span></div>
                            </div>

                            <div class="mt-8 space-y-5 text-justify">
                                <p>
                                    <span class="font-semibold">WHEREAS,</span> during the <span data-preview="meeting-type-lower">{{ strtolower($resolution->type_of_meeting ?: 'special') }}</span>
                                    meeting of the <span data-preview="governing-body">{{ $resolution->governing_body ?: 'Board of Directors' }}</span> of
                                    <span class="font-semibold underline">{{ $companyName }}</span> held on
                                    <span class="font-semibold underline" data-preview="meeting-date">{{ $meetingDate }}</span>, where a quorum was present and acted all throughout,
                                    the body approved the following action:
                                </p>

                                <div
                                    data-preview="resolution-body"
                                    id="resolution-body-preview-editor"
                                    class="min-h-[180px] whitespace-pre-wrap"
                                >{!! $resolution->resolution_body ?: ($resolution->board_resolution ?: 'No board resolution text has been encoded yet.') !!}</div>

                                <p>
                                    <span class="font-semibold">WHEREAS RESOLVED;</span> that the foregoing resolutions are hereby approved and adopted.
                                </p>

                                <p>
                                    <span class="font-semibold">WHEREAS FINALLY RESOLVED,</span> that the foregoing resolution is valid and existing
                                    until withdrawn, revoked, or modified by the Corporation.
                                </p>

                                <p>
                                    <span class="font-semibold">BE IT FURTHER RESOLVED,</span> that the Corporate Secretary is hereby authorized and directed
                                    to include this Resolution in the Company's Minute Book and to notify all concerned parties of the adoption of this Resolution.
                                </p>

                                <p>
                                    <span class="font-semibold underline">FINALLY BE IT FURTHER RESOLVED</span> that the undersigned affirm the foregoing resolution
                                    and adopt it on this <span class="font-semibold underline" data-preview="meeting-date">{{ $meetingDate }}</span>.
                                </p>
                            </div>

                            <div class="mt-16 text-right">
                                <div class="inline-block min-w-[240px] border-t border-black pt-2 text-center">
                                    <div class="font-semibold uppercase" data-preview="chairman">{{ $resolution->chairman ?: 'JOSE BAYBAYANON OGANG' }}</div>
                                    <div>Chairman</div>
                                </div>
                            </div>

                            <div class="mt-16 space-y-5 text-justify">
                                <p>
                                    IN WITNESS WHEREOF, I, <span class="font-semibold underline" data-preview="chairman">{{ $resolution->chairman ?: '____________________' }}</span>,
                                    in my capacity as chairman of the board, have signed these presents this ______ day of __________ at
                                    <span data-preview="notarized-at">{{ $resolution->notarized_at ?: '______________' }}</span>.
                                </p>
                            </div>

                            <div class="mt-12 text-right">
                                <div class="inline-block min-w-[260px] border-t border-black pt-2 text-center">
                                    <div class="font-semibold uppercase" data-preview="chairman">{{ $resolution->chairman ?: 'JOSE BAYBAYANON OGANG' }}</div>
                                    <div>Chairman</div>
                                </div>
                            </div>

                            <div class="mt-16 space-y-4 text-sm">
                                <p>
                                    <span class="font-semibold uppercase">Subscribed and sworn to before me,</span> a Notary Public for and in
                                    <span data-preview="notarized-at">{{ $resolution->notarized_at ?: '______________' }}</span> this day of __________, affiant presented to me __________ issued at __________.
                                </p>
                                <div class="text-right mt-10">
                                    <div class="inline-block min-w-[220px] border-t border-black pt-2 text-center font-semibold uppercase">
                                        <span data-preview="notary-public">{{ $resolution->notary_public ?: 'Notary Public' }}</span>
                                    </div>
                                </div>
                                <div class="mt-8 space-y-1">
                                    <div>Doc. No. <span data-preview="notary-doc-no">{{ $resolution->notary_doc_no ?: '_____' }}</span>;</div>
                                    <div>Page No. <span data-preview="notary-page-no">{{ $resolution->notary_page_no ?: '_____' }}</span>;</div>
                                    <div>Book No. <span data-preview="notary-book-no">{{ $resolution->notary_book_no ?: '_____' }}</span>;</div>
                                    <div>Series of <span data-preview="notary-series-no" data-fallback-year="{{ $notaryYear }}">{{ $resolution->notary_series_no ?: $notaryYear }}</span>.</div>
                                </div>
                            </div>
                        </div>
                            </div>
                            @if ($draftUrl)
                                <div x-show="activeDraftPane === 'attachment'" class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                                    <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900">Attached Draft PDF</div>
                                            <div class="text-xs text-gray-500">This is the uploaded draft file saved with the resolution.</div>
                                        </div>
                                        <a href="{{ $draftUrl }}" target="_blank" class="inline-flex rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-black">
                                            Open in New Tab
                                        </a>
                                    </div>
                                    <iframe
                                        src="{{ $draftUrl }}"
                                        class="w-full h-[700px] border-0 bg-white">
                                    </iframe>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden flex flex-col resolution-workspace-card">
                    <div class="flex-1 overflow-y-auto">
                        <div class="px-6 py-5 space-y-5">
                            <div class="rounded-2xl border border-gray-200 overflow-hidden sticky top-0 bg-white z-10 shadow-sm">
                                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                                    <div class="text-sm font-semibold text-gray-900">Resolution Body Builder</div>
                                    <div class="mt-1 text-xs text-gray-500">Write the resolution here with formatting tools. The live template uses this exact content.</div>
                                </div>
                                <form method="POST" action="{{ $updateRoute }}" enctype="multipart/form-data" class="space-y-5" id="resolution-live-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="minute_id" value="{{ $resolution->minute_id }}">
                    <input type="hidden" name="notice_id" value="{{ $resolution->notice_id }}">
                    <div class="px-4 py-4 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-gray-600">Resolution No.</label>
                            <input type="text" name="resolution_no" value="{{ $resolution->resolution_no }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="resolution-no" data-live-empty="25-002">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Meeting Date</label>
                            <input type="date" name="date_of_meeting" value="{{ optional($resolution->date_of_meeting)->toDateString() }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="meeting-date" data-live-format="resolution-date-group">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Governing Body</label>
                            <select name="governing_body" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="governing-body">
                                @foreach (['Stockholders', 'Board of Directors', 'Joint Stockholders and Board of Directors'] as $bodyOption)
                                    <option value="{{ $bodyOption }}" @selected($resolution->governing_body === $bodyOption)>{{ $bodyOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Meeting Type</label>
                            <select name="type_of_meeting" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="meeting-type" data-live-format="meeting-type-group">
                                @foreach (['Regular', 'Special'] as $meetingTypeOption)
                                    <option value="{{ $meetingTypeOption }}" @selected($resolution->type_of_meeting === $meetingTypeOption)>{{ $meetingTypeOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs text-gray-600">Board Resolution</label>
                            <input type="text" name="board_resolution" value="{{ $resolution->board_resolution }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="board-resolution">
                        </div>
                        <div class="md:col-span-2">
                            <div class="border border-gray-200 rounded-xl overflow-hidden">
                                <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 bg-gray-50 px-3 py-3">
                                    <select class="rounded-lg border border-gray-300 px-2 py-1 text-xs" data-resolution-rich-font>
                                        <option value="Arial">Arial</option>
                                        <option value="Times New Roman">Times New Roman</option>
                                        <option value="Georgia">Georgia</option>
                                        <option value="Verdana">Verdana</option>
                                    </select>
                                    <select class="rounded-lg border border-gray-300 px-2 py-1 text-xs" data-resolution-rich-size>
                                        <option value="2">12</option>
                                        <option value="3" selected>14</option>
                                        <option value="4">16</option>
                                        <option value="5">18</option>
                                    </select>
                                    <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-resolution-rich-cmd="bold">Bold</button>
                                    <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-resolution-rich-cmd="italic">Italic</button>
                                    <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-resolution-rich-cmd="underline">Underline</button>
                                    <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-resolution-rich-cmd="insertUnorderedList">Bullets</button>
                                    <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-resolution-rich-cmd="insertOrderedList">Numbering</button>
                                    <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-resolution-rich-cmd="justifyLeft">Left</button>
                                    <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-resolution-rich-cmd="justifyCenter">Center</button>
                                    <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-resolution-rich-cmd="justifyRight">Right</button>
                                    <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-resolution-rich-cmd="removeFormat">Clear</button>
                                </div>
                                <div
                                    id="resolution-body-editor"
                                    contenteditable="true"
                                    data-placeholder="Write the full resolved clauses here."
                                    class="resolution-rich-editor min-h-[360px] p-4 text-sm leading-7 text-gray-900 outline-none"
                                >{!! $resolution->resolution_body ?: '' !!}</div>
                                <input type="hidden" name="resolution_body" id="resolution-body-input" value="{{ $resolution->resolution_body }}" data-live-target="resolution-body" data-live-format="multiline" data-live-empty="No board resolution text has been encoded yet.">
                            </div>
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Chairman</label>
                            <input type="text" name="chairman" value="{{ $resolution->chairman }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="chairman" data-live-empty="JOSE BAYBAYANON OGANG">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Secretary</label>
                            <input type="text" name="secretary" value="{{ $resolution->secretary }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="secretary-name" data-live-empty="Name of secretary">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs text-gray-600">Directors</label>
                            <input type="text" name="directors" value="{{ $resolution->directors }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-4">
                        <div class="text-sm font-semibold text-gray-900">Template Notes</div>
                        <div class="text-xs text-gray-500">This builder mirrors the resolution template arrangement: company heading, board resolution title, whereas clause, resolution body, and sign-off sections.</div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-gray-600">Original / Notarized PDF</label>
                            <input type="file" name="notarized_file_path" accept="application/pdf" class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                            @if ($resolution->notarized_file_path)
                                <label class="mt-2 inline-flex items-center gap-2 text-xs font-medium text-red-700">
                                    <input type="checkbox" name="remove_notarized_file_path" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                    Remove current original / notarized PDF
                                </label>
                            @endif
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Draft PDF</label>
                            <input type="file" name="draft_file_path" accept="application/pdf" class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-slate-700 file:text-white hover:file:bg-slate-800">
                            @if ($resolution->draft_file_path)
                                <label class="mt-2 inline-flex items-center gap-2 text-xs font-medium text-red-700">
                                    <input type="checkbox" name="remove_draft_file_path" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                    Remove current draft PDF
                                </label>
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-gray-600">Notary Public</label>
                            <input type="text" name="notary_public" value="{{ $resolution->notary_public }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="notary-public" data-live-empty="NOTARY PUBLIC">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Notarized At</label>
                            <input type="text" name="notarized_at" value="{{ $resolution->notarized_at }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="notarized-at" data-live-empty="______________">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Notarized On</label>
                            <input type="date" name="notarized_on" value="{{ optional($resolution->notarized_on)->toDateString() }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="notary-series-no" data-live-format="year-fallback">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Doc No.</label>
                            <input type="text" name="notary_doc_no" value="{{ $resolution->notary_doc_no }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="notary-doc-no" data-live-empty="_____">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Page No.</label>
                            <input type="text" name="notary_page_no" value="{{ $resolution->notary_page_no }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="notary-page-no" data-live-empty="_____">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Book No.</label>
                            <input type="text" name="notary_book_no" value="{{ $resolution->notary_book_no }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="notary-book-no" data-live-empty="_____">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs text-gray-600">Series No.</label>
                            <input type="text" name="notary_series_no" value="{{ $resolution->notary_series_no }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="notary-series-no">
                        </div>
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">
                        Save Resolution Changes
                    </button>
                    </div>
                </form>
                            </div>

                            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                                <div class="text-sm font-semibold text-gray-900 mb-3">Resolution Information</div>
                                <div class="space-y-2 text-sm">
                                    <div><span class="text-xs text-gray-600 uppercase tracking-wide">Linked Notice</span><div class="font-medium text-gray-900">{{ $resolution->notice_ref }}</div></div>
                                    <div><span class="text-xs text-gray-600 uppercase tracking-wide">Governing Body</span><div class="font-medium text-gray-900" data-preview="governing-body">{{ $resolution->governing_body }}</div></div>
                                    <div><span class="text-xs text-gray-600 uppercase tracking-wide">Meeting Type</span><div class="font-medium text-gray-900" data-preview="meeting-type">{{ $resolution->type_of_meeting }}</div></div>
                                    <div><span class="text-xs text-gray-600 uppercase tracking-wide">Meeting Date</span><div class="font-medium text-gray-900" data-preview="meeting-date-short">{{ optional($resolution->date_of_meeting)->format('M d, Y') }}</div></div>
                                    <div><span class="text-xs text-gray-600 uppercase tracking-wide">Board Resolution</span><div class="font-medium text-gray-900" data-preview="board-resolution">{{ $resolution->board_resolution }}</div></div>
                                    <div><span class="text-xs text-gray-600 uppercase tracking-wide">Secretary Certificates</span><div class="font-medium text-gray-900">{{ $resolution->secretaryCertificates->count() }} linked</div></div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4">
                                <div class="text-sm font-semibold text-gray-900 mb-3">Signatories</div>
                                <div class="space-y-2 text-sm">
                                    <div><span class="text-xs text-gray-600 uppercase tracking-wide">Chairman</span><div class="font-medium text-gray-900">{{ $resolution->chairman }}</div></div>
                                    <div><span class="text-xs text-gray-600 uppercase tracking-wide">Secretary</span><div class="font-medium text-gray-900">{{ $resolution->secretary }}</div></div>
                                    <div><span class="text-xs text-gray-600 uppercase tracking-wide">Directors</span><div class="font-medium text-gray-900">{{ $resolution->directors }}</div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="activeVersion === 'original'">
                <div class="rounded-2xl border border-slate-200 overflow-hidden bg-white resolution-workspace-card">
                    <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                        <div class="text-sm font-semibold text-gray-900">Original / Notarized Preview</div>
                        <div class="text-xs text-gray-500">Review the notarized upload here.</div>
                    </div>
                    @if ($notarizedUrl)
                        <iframe
                            src="{{ $notarizedUrl }}"
                            class="w-full h-[820px] border-0 bg-white">
                        </iframe>
                    @else
                        <div class="w-full h-[700px] flex items-center justify-center bg-gray-50 text-gray-400 text-sm">
                            <div class="text-lg font-semibold">Original / notarized copy not uploaded yet</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function printResolutionBuiltPdf(url) {
        if (!url) {
            return;
        }

        const printWindow = window.open(url, '_blank');
        if (!printWindow) {
            return;
        }

        const triggerPrint = () => {
            try {
                printWindow.focus();
                printWindow.print();
            } catch (error) {
                // Let the opened PDF stay available even if direct print fails.
            }
        };

        printWindow.addEventListener('load', triggerPrint, { once: true });
        setTimeout(triggerPrint, 1200);
    }

    (() => {
        const form = document.getElementById('resolution-live-form');
        const resolutionBodyEditor = document.getElementById('resolution-body-editor');
        const resolutionBodyInput = document.getElementById('resolution-body-input');
        const resolutionBodyPreviewEditor = document.getElementById('resolution-body-preview-editor');
        if (!form) {
            return;
        }

        const formatDate = (value, style) => {
            if (!value) {
                return '';
            }

            const parsed = new Date(`${value}T00:00:00`);
            if (Number.isNaN(parsed.getTime())) {
                return value;
            }

            if (style === 'year') {
                return String(parsed.getFullYear());
            }

            if (style === 'short') {
                return parsed.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
            }

            return parsed.toLocaleDateString('en-US', { month: 'long', day: '2-digit', year: 'numeric' });
        };

        const applyValue = (input) => {
            const targetName = input.dataset.liveTarget;
            if (!targetName) {
                return;
            }

            const targets = document.querySelectorAll(`[data-preview="${targetName}"]`);
            if (!targets.length) {
                return;
            }

            let value = input.value.trim();
            if (input.dataset.liveFormat === 'resolution-date-group') {
                const longValue = value ? formatDate(value, 'long') : '';
                const shortValue = value ? formatDate(value, 'short') : '';

                document.querySelectorAll('[data-preview="meeting-date"]').forEach((target) => {
                    target.textContent = longValue || '________________';
                });
                document.querySelectorAll('[data-preview="meeting-date-short"]').forEach((target) => {
                    target.textContent = shortValue || '';
                });
                return;
            }

            if (input.dataset.liveFormat === 'meeting-type-group') {
                document.querySelectorAll('[data-preview="meeting-type"]').forEach((target) => {
                    target.textContent = value || 'Special';
                });
                document.querySelectorAll('[data-preview="meeting-type-lower"]').forEach((target) => {
                    target.textContent = (value || 'special').toLowerCase();
                });
                return;
            }

            if (input.dataset.liveFormat === 'multiline') {
                const finalHtml = value || 'No board resolution text has been encoded yet.';

                targets.forEach((target) => {
                    target.innerHTML = finalHtml;
                });
                return;
            }

            if (input.dataset.liveFormat === 'year-fallback') {
                const directSeriesInput = form.querySelector('[name="notary_series_no"]');
                const directSeriesValue = directSeriesInput ? directSeriesInput.value.trim() : '';
                value = directSeriesValue || formatDate(value, 'year');
            }

            if (targetName === 'notary-series-no' && !value) {
                const notarizedOnInput = form.querySelector('[name="notarized_on"]');
                value = notarizedOnInput ? formatDate(notarizedOnInput.value, 'year') : '';
            }

            const fallback = input.dataset.liveEmpty || targets[0].dataset.fallbackYear || '';
            const finalValue = value || fallback;

            targets.forEach((target) => {
                target.textContent = finalValue;
            });
        };

        form.querySelectorAll('[data-live-target]').forEach((input) => {
            input.addEventListener('input', () => applyValue(input));
            input.addEventListener('change', () => applyValue(input));
        });

        if (resolutionBodyEditor && resolutionBodyInput) {
            const syncResolutionBody = () => {
                const html = String(resolutionBodyEditor.innerHTML || '').trim();
                const fallbackHtml = 'No board resolution text has been encoded yet.';

                resolutionBodyInput.value = html;
                applyValue(resolutionBodyInput);

                if (resolutionBodyPreviewEditor) {
                    resolutionBodyPreviewEditor.innerHTML = html || fallbackHtml;
                }
            };

            resolutionBodyEditor.addEventListener('input', syncResolutionBody);

            form.querySelectorAll('[data-resolution-rich-cmd]').forEach((button) => {
                button.addEventListener('click', () => {
                    resolutionBodyEditor.focus();
                    document.execCommand(button.dataset.resolutionRichCmd, false, null);
                    syncResolutionBody();
                });
            });

            const fontSelect = form.querySelector('[data-resolution-rich-font]');
            if (fontSelect) {
                fontSelect.addEventListener('change', () => {
                    resolutionBodyEditor.focus();
                    document.execCommand('fontName', false, fontSelect.value);
                    syncResolutionBody();
                });
            }

            const sizeSelect = form.querySelector('[data-resolution-rich-size]');
            if (sizeSelect) {
                sizeSelect.addEventListener('change', () => {
                    resolutionBodyEditor.focus();
                    document.execCommand('fontSize', false, sizeSelect.value);
                    syncResolutionBody();
                });
            }

            syncResolutionBody();
        }

        form.querySelectorAll('[data-live-target]').forEach((input) => {
            applyValue(input);
        });
    })();
</script>
@endsection

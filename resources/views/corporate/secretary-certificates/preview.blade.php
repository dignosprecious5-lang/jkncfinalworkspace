@extends('layouts.app')
@section('title', 'Secretary Certificate Preview')

@section('content')
@php
    $sectionRibbonPartial = $sectionRibbonPartial ?? 'corporate.partials.section-ribbon';
    $draftUrl = $generatedDraftUrl ?? null;
    $draftDownloadUrl = $generatedDraftDownloadUrl ?? $draftUrl;
    $documentUrl = $certificate->document_path ? route('uploads.show', ['path' => $certificate->document_path]) : null;
    $corporateContext = $corporateContext ?? [];
    $resolution = $certificate->resolution;
    $minute = $certificate->minute ?: $resolution?->minute;
    $defaultSecretary = $corporateContext['secretary_name'] ?? ($certificate->secretary ?: ($resolution?->secretary ?: ($minute?->secretary ?: '________________')));
    $companyName = $corporateContext['company_name'] ?? '________________';
    $companyRegNo = $corporateContext['company_reg_no'] ?? '________________';
    $companyAddress = $corporateContext['company_address'] ?? '________________';
    $secretaryAddress = $corporateContext['secretary_address'] ?? 'principal office of the Corporation';
    $defaultTin = $corporateContext['secretary_tin'] ?? null;
    $notarialPlace = $corporateContext['notarial_place'] ?? ($certificate->location ?: 'Cebu City, Philippines');
    $certificatePurpose = $corporateContext['purpose'] ?? ($certificate->purpose ?: ($resolution?->board_resolution ?: 'Certified Resolution'));
    $meetingDate = optional($certificate->date_of_meeting)->format('F d, Y') ?: '________________';
    $issuedDate = optional($certificate->date_issued)->format('F d, Y') ?: '________________';
    $certificateBody = $corporateContext['resolution_body'] ?? ($certificate->resolution_body ?: ($resolution?->resolution_body ?: ($resolution?->board_resolution ?: ('Certified from Minutes Ref. ' . ($certificate->minutes_ref ?: ($minute?->minutes_ref ?: '________________')) . '.'))));
    $resolutionLabel = match ($certificate->governing_body) {
        'Stockholders' => "STOCKHOLDERS' RESOLUTION NO. ",
        'Joint Stockholders and Board of Directors' => 'JOINT BOARD AND STOCKHOLDERS RESOLUTION NO. ',
        default => 'BOARD RESOLUTION NO. ',
    };

    $formatResolutionBodyForDisplay = function ($body) {
        $text = html_entity_decode((string) $body, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Preserve paragraph breaks from saved rich-editor HTML before stripping tags.
        $text = preg_replace('#</(p|div|li|h[1-6])>\s*<(?=(p|div|li|h[1-6])\b)#i', "\n\n<", $text);
        $text = preg_replace('#<br\s*/?>#i', "\n", $text);
        $text = strip_tags($text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        // Force the standard resolution clauses into separate corporate-style paragraphs.
        $clauseBreaks = [
            '/\s*(WHEREAS\s+RESOLVED[;,]\s+)/iu',
            '/\s*(WHEREAS\s+FINALLY\s+RESOLVED[;,]\s+)/iu',
            '/\s*(BE\s+IT\s+FURTHER\s+RESOLVED[;,]\s+)/iu',
            '/\s*(FINALLY\s+BE\s+IT\s+FURTHER\s+RESOLVED\s+)/iu',
            '/\s*(All\s+prior\s+inconsistent\s+resolutions\s+or\s+actions\s+of\s+the\s+Board\s+of\s+Directors\s+)/iu',
        ];

        foreach ($clauseBreaks as $pattern) {
            $text = preg_replace($pattern, "\n\n$1", $text);
        }

        $text = preg_replace('/\n{3,}/', "\n\n", trim($text));
        $paragraphs = preg_split('/\n\s*\n+/', $text);
        $html = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim(preg_replace('/[ \t]+/', ' ', $paragraph));
            if ($paragraph === '') {
                continue;
            }

            $escaped = e($paragraph);

            // Bold only the clause heading, not the whole sentence.
            $patterns = [
                '/^(WHEREAS\s+RESOLVED[;,]?)(\s*)/iu',
                '/^(WHEREAS\s+FINALLY\s+RESOLVED[;,]?)(\s*)/iu',
                '/^(BE\s+IT\s+FURTHER\s+RESOLVED[;,]?)(\s*)/iu',
                '/^(FINALLY\s+BE\s+IT\s+FURTHER\s+RESOLVED)(\s*)/iu',
                '/^(Whereas[;,]?)(\s*)/iu',
            ];

            foreach ($patterns as $pattern) {
                $new = preg_replace($pattern, '<strong>$1</strong> ', $escaped, 1);
                if ($new !== $escaped) {
                    $escaped = $new;
                    break;
                }
            }

            // Emphasize the auto-filled signing date and place in the final clause.
            $escaped = preg_replace(
                '/(We have affixed our signatures on this\s+)(.*?)(\s+at\s+)(.*?)(\.)$/iu',
                '$1<strong><u>$2</u></strong>$3<strong><u>$4</u></strong>$5',
                $escaped,
                1
            );

            $html[] = '<p>' . $escaped . '</p>';
        }

        return implode("\n", $html);
    };

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

    .certificate-workspace-card {
        min-height: calc(100vh - 15rem);
    }

    .corporate-resolution-body,
    .corporate-resolution-body * {
        max-width: 100%;
        overflow-wrap: anywhere;
        word-break: break-word;
        white-space: normal;
    }

    .corporate-resolution-body p {
        margin: 0 0 18px 0;
        text-align: justify;
        line-height: 1.75;
    }

    .corporate-resolution-body strong {
        font-weight: 700;
    }

    .certificate-purpose-heading {
        margin: 18px 0 22px 0;
        text-align: center;
        font-size: 17px;
        line-height: 1.45;
        font-weight: 700;
        text-transform: uppercase;
    }

</style>

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4" x-data="{ activeVersion: 'draft', activeDraftPane: 'live' }">
    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-100">
            <a href="{{ $backRoute }}" class="text-gray-500 hover:text-gray-700"><i class="fas fa-arrow-left"></i></a>
            <div>
                <div class="text-lg font-semibold">Secretary Certificate Preview</div>
                <div class="text-xs text-gray-500">Certificate No. <span data-preview="certificate-no">{{ $certificate->certificate_no ?: 'Draft' }}</span></div>
            </div>
            <div class="flex-1"></div>
            <div class="inline-flex rounded-full bg-gray-100 p-1">
                <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-full" :class="activeVersion === 'draft' ? 'bg-white shadow text-gray-900' : 'text-gray-500'" @click="activeVersion = 'draft'">Draft</button>
                <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-full" :class="activeVersion === 'original' ? 'bg-white shadow text-gray-900' : 'text-gray-500'" @click="activeVersion = 'original'">Original</button>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.7fr)_minmax(420px,0.95fr)] gap-6 p-6">
            <div class="space-y-4">
                <div x-show="activeVersion === 'draft'">
                    <div class="rounded-2xl border border-slate-200 overflow-hidden bg-[#f8fafc] flex flex-col certificate-workspace-card">
                        <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center gap-3 bg-white">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Template Builder Page</div>
                                <div class="text-xs text-gray-500">This page mirrors the secretary certificate draft layout and updates in real time.</div>
                            </div>
                            <div class="flex-1"></div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Live Template</span>
                            @if ($draftUrl)
                                <button type="button" class="rounded-full px-3 py-1 text-xs font-semibold transition" :class="activeDraftPane === 'attachment' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600'" @click="activeDraftPane = activeDraftPane === 'attachment' ? 'live' : 'attachment'">
                                    <span x-text="activeDraftPane === 'attachment' ? 'Back To Live Template' : 'Open Built Draft PDF'"></span>
                                </button>
                            @endif
                        </div>
                        <div class="flex-1 overflow-auto p-6">
                            <div x-show="activeDraftPane === 'live'">
                                <div class="mx-auto max-w-[860px] rounded-sm bg-white px-14 py-12 shadow-[0_18px_50px_rgba(15,23,42,0.08)] text-[13px] leading-7 text-gray-900 min-h-[920px]" style="font-family: Georgia, 'Times New Roman', serif;">
                                    <div>Republic of the Philippines)</div>
                                    <div>______________________) S.S.</div>

                                    <div class="mt-8 text-center text-[20px] font-bold">SECRETARY'S CERTIFICATE</div>

                                    <div class="mt-8 space-y-4 text-justify">
                                        <p>I, <strong data-preview="certificate-secretary">{{ $certificate->secretary ?: $defaultSecretary }}</strong>, of legal age, Filipino and with residence/address at <strong data-preview="certificate-secretary-address">{{ $secretaryAddress }}</strong>, depose under oath and hereby state:</p>
                                        <p>That, I am the incumbent Corporate Secretary of <strong>{{ $companyName }}</strong>, a corporation duly organized and existing under the laws of the Republic of the Philippines, with SEC Registration No. <strong>{{ $companyRegNo }}</strong> and principal office at <strong>{{ $companyAddress }}</strong>.</p>
                                        <p>That, as Corporate Secretary, I have access to the corporate records of <strong>{{ $companyName }}</strong>.</p>
                                        <p>That, per corporate records, at the <span data-preview="certificate-meeting-type">{{ $certificate->type_of_meeting ?: 'Special' }}</span> Meeting of the <span data-preview="certificate-governing-body">{{ $certificate->governing_body ?: 'Board of Directors' }}</span> of the Corporation held on <strong data-preview="certificate-meeting-date">{{ $meetingDate }}</strong>, and recorded under Minutes Ref. <strong>{{ $certificate->minutes_ref ?: '-' }}</strong>, the following corporate action was duly approved and recorded in the Minute Book, a legal quorum being present and voting, viz:</p>

                                        <div class="my-6 text-center font-bold uppercase" data-preview="certificate-resolution-title">{{ $certificate->resolution_no ? $resolutionLabel . $certificate->resolution_no : 'CERTIFIED MINUTES EXTRACT' }}</div>
                                        <div class="certificate-purpose-heading" data-preview="certificate-purpose">{{ $certificatePurpose }}</div>
                                        <div data-preview="certificate-body" class="min-h-[180px] corporate-resolution-body">{!! $formatResolutionBodyForDisplay($certificateBody) !!}</div>

                                        <p>That, the foregoing resolution shall be in full force and effect unless revoked by the Board of Directors. Moreover, the foregoing resolution is in accordance and does not in any way contravene any provision of the Articles of Incorporation or By-Laws of the Corporation.</p>
                                        <p>WITNESS MY HAND this ________ day of ___________, <span data-preview="certificate-issued-year">{{ optional($certificate->date_issued)->format('Y') ?: now()->year }}</span> at <span data-preview="certificate-notarial-place">{{ $notarialPlace }}</span>.</p>
                                    </div>

                                    <div class="mt-12 text-right">
                                        <div class="inline-block min-w-[250px] border-t border-black pt-2 text-center">
                                            <div><strong data-preview="certificate-secretary">{{ $certificate->secretary ?: $defaultSecretary }}</strong></div>
                                            <div>Corporate Secretary</div>
                                            @if ($defaultTin)
                                                <div class="text-[11px]">TIN {{ $defaultTin }}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mt-10 text-[12px] leading-6">
                                        <p>SUBSCRIBED AND SWORN TO BEFORE ME, a Notary Public for and in <span data-preview="certificate-notarial-place">{{ $notarialPlace }}</span>, this ____ day of ____________, 20__. Affiant presented to me __________________________ issued at __________________________.</p>
                                        <div class="mt-8 text-right">
                                            <div class="inline-block min-w-[250px] border-t border-black pt-2 text-center">
                                                <div data-preview="certificate-notary-public">{{ $certificate->notary_public ?: 'Notary Public' }}</div>
                                            </div>
                                        </div>
                                        <div class="mt-6">
                                            <div>Doc. No. <span data-preview="certificate-doc-no">{{ $certificate->notary_doc_no ?: '_____' }}</span>;</div>
                                            <div>Page No. <span data-preview="certificate-page-no">{{ $certificate->notary_page_no ?: '_____' }}</span>;</div>
                                            <div>Book No. <span data-preview="certificate-book-no">{{ $certificate->notary_book_no ?: '_____' }}</span>;</div>
                                            <div>Series of <span data-preview="certificate-series-no" data-fallback-year="{{ now()->year }}">{{ $certificate->notary_series_no ?: now()->year }}</span>.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if ($draftUrl)
                                <div x-show="activeDraftPane === 'attachment'" class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                                    <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900">Built Draft PDF</div>
                                            <div class="text-xs text-gray-500">This is the generated PDF version of the certificate.</div>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <a href="{{ $draftUrl }}" target="_blank" class="inline-flex rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-black">Open in New Tab</a>
                                            <a href="{{ $draftDownloadUrl }}" target="_blank" class="inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Download PDF</a>
                                        </div>
                                    </div>
                                    <iframe src="{{ $draftUrl }}" class="w-full h-[700px] border-0 bg-white"></iframe>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div x-show="activeVersion === 'original'">
                    <div class="rounded-2xl border border-slate-200 overflow-hidden bg-white certificate-workspace-card">
                        <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                            <div class="text-sm font-semibold text-gray-900">Original Certificate Preview</div>
                            <div class="text-xs text-gray-500">Review the uploaded original file here.</div>
                        </div>
                        @if ($documentUrl)
                            <iframe src="{{ $documentUrl }}" class="w-full h-[820px] border-0 bg-white"></iframe>
                        @else
                            <div class="w-full h-[700px] flex items-center justify-center bg-gray-50 text-gray-400 text-sm">Original certificate not uploaded yet.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden flex flex-col certificate-workspace-card">
                <div class="flex-1 overflow-y-auto">
                    <div class="px-6 py-5 space-y-5">
                <form method="POST" action="{{ $updateRoute }}" enctype="multipart/form-data" class="rounded-2xl border border-gray-200 bg-white p-4 space-y-4 sticky top-0 z-10 shadow-sm" id="certificate-live-form">
                    @csrf
                    @method('PUT')
                    <div>
                        <div class="text-sm font-semibold text-gray-900">Secretary Certificate Builder</div>
                        <div class="text-xs text-gray-500 mt-1">Generated from linked Resolution/Minutes and latest approved GIS. Edit only if needed.</div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div><label class="text-xs text-gray-600">Certificate No.</label><input type="text" name="certificate_no" value="{{ $certificate->certificate_no }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="certificate-no" data-live-empty="Draft"></div>
                        <div><label class="text-xs text-gray-600">Minutes Ref.</label><input type="text" name="minutes_ref" value="{{ $certificate->minutes_ref }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></div>
                        <div><label class="text-xs text-gray-600">Resolution No.</label><input type="text" name="resolution_no" value="{{ $certificate->resolution_no }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="certificate-resolution-no" data-live-empty="25-004"></div>
                        <div><label class="text-xs text-gray-600">Date Issued</label><input type="date" name="date_issued" value="{{ optional($certificate->date_issued)->toDateString() }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="certificate-issued-date" data-live-format="date-group"></div>
                        <div><label class="text-xs text-gray-600">Meeting Date</label><input type="date" name="date_of_meeting" value="{{ optional($certificate->date_of_meeting)->toDateString() }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="certificate-meeting-date" data-live-format="meeting-date-group"></div>
                        <div>
                            <label class="text-xs text-gray-600">Governing Body</label>
                            <select name="governing_body" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="certificate-governing-body">
                                @foreach (['Stockholders', 'Board of Directors', 'Joint Stockholders and Board of Directors'] as $bodyOption)
                                    <option value="{{ $bodyOption }}" @selected($certificate->governing_body === $bodyOption)>{{ $bodyOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Meeting Type</label>
                            <select name="type_of_meeting" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="certificate-meeting-type">
                                @foreach (['Regular', 'Special'] as $meetingTypeOption)
                                    <option value="{{ $meetingTypeOption }}" @selected($certificate->type_of_meeting === $meetingTypeOption)>{{ $meetingTypeOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2"><label class="text-xs text-gray-600">Certified Resolution / Purpose</label><input type="text" name="purpose" value="{{ $certificate->purpose }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="certificate-purpose"></div>
                        <div class="col-span-2">
                            <div class="overflow-hidden rounded-xl border border-gray-300 bg-white">
                                <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 bg-white px-3 py-3">
                                    <select class="rounded-lg border border-gray-300 px-2 py-1 text-xs" data-secretary-rich-font>
                                        <option value="Arial">Arial</option>
                                        <option value="Times New Roman">Times New Roman</option>
                                        <option value="Georgia">Georgia</option>
                                        <option value="Verdana">Verdana</option>
                                    </select>
                                    <select class="rounded-lg border border-gray-300 px-2 py-1 text-xs" data-secretary-rich-size>
                                        <option value="2">12</option>
                                        <option value="3" selected>14</option>
                                        <option value="4">16</option>
                                        <option value="5">18</option>
                                    </select>
                                    <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-secretary-rich-cmd="bold">Bold</button>
                                    <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-secretary-rich-cmd="italic">Italic</button>
                                    <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-secretary-rich-cmd="underline">Underline</button>
                                    <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-secretary-rich-cmd="insertUnorderedList">Bullets</button>
                                    <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-secretary-rich-cmd="insertOrderedList">Numbering</button>
                                    <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-secretary-rich-cmd="justifyLeft">Left</button>
                                    <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-secretary-rich-cmd="justifyCenter">Center</button>
                                    <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-secretary-rich-cmd="justifyRight">Right</button>
                                    <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-secretary-rich-cmd="removeFormat">Clear</button>
                                </div>
                                <div id="certificate-body-editor" contenteditable="true" data-placeholder="Auto-filled from the linked Resolution. Add details only if needed..." class="secretary-rich-editor min-h-[360px] p-4 text-sm leading-7 text-gray-900 outline-none break-words [overflow-wrap:anywhere]">{!! $certificateBody !!}</div>
                                <input type="hidden" name="resolution_body" id="certificate-body-input" value="{{ $certificateBody }}" data-live-target="certificate-body" data-live-format="multiline" data-live-empty="Certified from corporate minutes.">
                            </div>
                        </div>
                        <div><label class="text-xs text-gray-600">Secretary</label><input type="text" name="secretary" value="{{ $certificate->secretary }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="certificate-secretary" data-live-empty="{{ $defaultSecretary }}"></div>
                        <div><label class="text-xs text-gray-600">Secretary Address</label><input type="text" name="secretary_address" value="{{ data_get($certificate, 'secretary_address', $secretaryAddress) }}" data-live-target="certificate-secretary-address" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></div>
                        <div><label class="text-xs text-gray-600">Secretary TIN</label><input type="text" name="secretary_tin" value="{{ data_get($certificate, 'secretary_tin', $defaultTin) }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></div>
                        <div><label class="text-xs text-gray-600">Notarial Place / City</label><input type="text" name="notarial_place" value="{{ data_get($certificate, 'notarial_place', $notarialPlace) }}" data-live-target="certificate-notarial-place" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></div>
                        <div><label class="text-xs text-gray-600">Notary Public</label><input type="text" name="notary_public" value="{{ $certificate->notary_public }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="certificate-notary-public" data-live-empty="Notary Public"></div>
                        <div><label class="text-xs text-gray-600">Doc No.</label><input type="text" name="notary_doc_no" value="{{ $certificate->notary_doc_no }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="certificate-doc-no" data-live-empty="_____"></div>
                        <div><label class="text-xs text-gray-600">Page No.</label><input type="text" name="notary_page_no" value="{{ $certificate->notary_page_no }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="certificate-page-no" data-live-empty="_____"></div>
                        <div><label class="text-xs text-gray-600">Book No.</label><input type="text" name="notary_book_no" value="{{ $certificate->notary_book_no }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="certificate-book-no" data-live-empty="_____"></div>
                        <div><label class="text-xs text-gray-600">Series No.</label><input type="text" name="notary_series_no" value="{{ $certificate->notary_series_no }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" data-live-target="certificate-series-no"></div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div class="text-sm font-semibold text-gray-900">Template Notes</div>
                        <div class="mt-1 text-xs text-gray-500">This builder mirrors the certificate template arrangement: oath heading, certification statements, resolution title, certified body, and notary section.</div>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Original Certificate PDF</label>
                        <input type="file" name="document_path" accept="application/pdf" class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                        @if ($certificate->document_path)
                            <label class="mt-2 inline-flex items-center gap-2 text-xs font-medium text-red-700">
                                <input type="checkbox" name="remove_document_path" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                Remove current original certificate PDF
                            </label>
                        @endif
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">Save Certificate Changes</button>
                </form>

                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div class="text-sm font-semibold text-gray-900 mb-3">Certificate Details</div>
                    <div class="space-y-2 text-sm">
                        <div><span class="text-xs text-gray-600 uppercase tracking-wide">Minutes Ref.</span><div class="font-medium text-gray-900">{{ $certificate->minutes_ref ?: '-' }}</div></div>
                        <div><span class="text-xs text-gray-600 uppercase tracking-wide">Resolution No.</span><div class="font-medium text-gray-900" data-preview="certificate-resolution-no">{{ $certificate->resolution_no }}</div></div>
                        <div><span class="text-xs text-gray-600 uppercase tracking-wide">Notice Ref</span><div class="font-medium text-gray-900">{{ $certificate->notice_ref }}</div></div>
                        <div><span class="text-xs text-gray-600 uppercase tracking-wide">Date Issued</span><div class="font-medium text-gray-900" data-preview="certificate-issued-date-short">{{ optional($certificate->date_issued)->format('M d, Y') }}</div></div>
                        <div><span class="text-xs text-gray-600 uppercase tracking-wide">Certified Resolution / Purpose</span><div class="font-medium text-gray-900" data-preview="certificate-purpose">{{ $certificate->purpose }}</div></div>
                    </div>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                    <div class="text-sm font-semibold text-gray-900 mb-3">Shared Resolution Data</div>
                    <div class="space-y-2 text-sm">
                        <div><span class="text-xs text-gray-600 uppercase tracking-wide">Governing Body</span><div class="font-medium text-gray-900" data-preview="certificate-governing-body">{{ $certificate->governing_body }}</div></div>
                        <div><span class="text-xs text-gray-600 uppercase tracking-wide">Meeting Type</span><div class="font-medium text-gray-900" data-preview="certificate-meeting-type">{{ $certificate->type_of_meeting }}</div></div>
                        <div><span class="text-xs text-gray-600 uppercase tracking-wide">Meeting Date</span><div class="font-medium text-gray-900" data-preview="certificate-meeting-date-short">{{ optional($certificate->date_of_meeting)->format('M d, Y') }}</div></div>
                        <div><span class="text-xs text-gray-600 uppercase tracking-wide">Location</span><div class="font-medium text-gray-900">{{ $certificate->location }}</div></div>
                        <div><span class="text-xs text-gray-600 uppercase tracking-wide">Secretary</span><div class="font-medium text-gray-900" data-preview="certificate-secretary">{{ $certificate->secretary }}</div></div>
                    </div>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                    <div class="text-sm font-semibold text-gray-900 mb-3">Notary Details</div>
                    <div class="space-y-2 text-sm">
                        <div><span class="text-xs text-gray-600 uppercase tracking-wide">Notary Public</span><div class="font-medium text-gray-900" data-preview="certificate-notary-public">{{ $certificate->notary_public }}</div></div>
                        <div><span class="text-xs text-gray-600 uppercase tracking-wide">Doc / Page / Book / Series</span><div class="font-medium text-gray-900"><span data-preview="certificate-doc-no">{{ $certificate->notary_doc_no }}</span> / <span data-preview="certificate-page-no">{{ $certificate->notary_page_no }}</span> / <span data-preview="certificate-book-no">{{ $certificate->notary_book_no }}</span> / <span data-preview="certificate-series-no" data-fallback-year="{{ now()->year }}">{{ $certificate->notary_series_no }}</span></div></div>
                    </div>
                </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const form = document.getElementById('certificate-live-form');
        const certificateBodyEditor = document.getElementById('certificate-body-editor');
        const certificateBodyInput = document.getElementById('certificate-body-input');
        const certificateBodyPreview = document.querySelector('[data-preview="certificate-body"]');
        if (!form) return;

        const formatDate = (value, style) => {
            if (!value) return '';
            const parsed = new Date(`${value}T00:00:00`);
            if (Number.isNaN(parsed.getTime())) return value;
            if (style === 'year') return String(parsed.getFullYear());
            if (style === 'short') return parsed.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
            return parsed.toLocaleDateString('en-US', { month: 'long', day: '2-digit', year: 'numeric' });
        };


        const formatCertificateResolutionBody = (html) => {
            const source = String(html || '');
            if (!source.trim()) return 'Certified from corporate minutes.';

            const holder = document.createElement('div');
            holder.innerHTML = source
                .replace(/<\/(p|div|li|h[1-6])>\s*<(?=(p|div|li|h[1-6])\b)/gi, '</$1>\n\n<')
                .replace(/<br\s*\/?>/gi, '\n');

            let text = holder.textContent || holder.innerText || '';
            text = text.replace(/\r\n|\r/g, '\n').replace(/[ \t]+/g, ' ').trim();
            if (!text) return 'Certified from corporate minutes.';

            const breaks = [
                /\s*(WHEREAS\s+RESOLVED[;,]\s+)/giu,
                /\s*(WHEREAS\s+FINALLY\s+RESOLVED[;,]\s+)/giu,
                /\s*(BE\s+IT\s+FURTHER\s+RESOLVED[;,]\s+)/giu,
                /\s*(FINALLY\s+BE\s+IT\s+FURTHER\s+RESOLVED\s+)/giu,
                /\s*(All\s+prior\s+inconsistent\s+resolutions\s+or\s+actions\s+of\s+the\s+Board\s+of\s+Directors\s+)/giu,
            ];
            breaks.forEach((pattern) => { text = text.replace(pattern, '\n\n$1'); });
            text = text.replace(/\n{3,}/g, '\n\n').trim();

            const escapeHtml = (value) => value
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            return text.split(/\n\s*\n+/)
                .map((paragraph) => paragraph.replace(/[ \t]+/g, ' ').trim())
                .filter(Boolean)
                .map((paragraph) => {
                    let escaped = escapeHtml(paragraph);
                    const headings = [
                        /^(WHEREAS\s+RESOLVED[;,]?)(\s*)/iu,
                        /^(WHEREAS\s+FINALLY\s+RESOLVED[;,]?)(\s*)/iu,
                        /^(BE\s+IT\s+FURTHER\s+RESOLVED[;,]?)(\s*)/iu,
                        /^(FINALLY\s+BE\s+IT\s+FURTHER\s+RESOLVED)(\s*)/iu,
                        /^(Whereas[;,]?)(\s*)/iu,
                    ];
                    for (const pattern of headings) {
                        const next = escaped.replace(pattern, '<strong>$1</strong> ');
                        if (next !== escaped) {
                            escaped = next;
                            break;
                        }
                    }
                    escaped = escaped.replace(
                        /(We have affixed our signatures on this\s+)(.*?)(\s+at\s+)(.*?)(\.)$/iu,
                        '$1<strong><u>$2</u></strong>$3<strong><u>$4</u></strong>$5'
                    );
                    return `<p>${escaped}</p>`;
                })
                .join('');
        };

        const applyValue = (input) => {
            const targetName = input.dataset.liveTarget;
            if (!targetName) return;
            const targets = document.querySelectorAll(`[data-preview="${targetName}"]`);
            if (!targets.length) return;

            let value = input.value.trim();
            if (input.dataset.liveFormat === 'date-group') {
                const longValue = value ? formatDate(value, 'long') : '';
                const shortValue = value ? formatDate(value, 'short') : '';
                const yearValue = value ? formatDate(value, 'year') : '';
                document.querySelectorAll('[data-preview="certificate-issued-date"]').forEach((target) => target.textContent = longValue || '________________');
                document.querySelectorAll('[data-preview="certificate-issued-date-short"]').forEach((target) => target.textContent = shortValue || '');
                document.querySelectorAll('[data-preview="certificate-issued-year"]').forEach((target) => target.textContent = yearValue || String(new Date().getFullYear()));
                return;
            }
            if (input.dataset.liveFormat === 'meeting-date-group') {
                const longValue = value ? formatDate(value, 'long') : '';
                const shortValue = value ? formatDate(value, 'short') : '';
                document.querySelectorAll('[data-preview="certificate-meeting-date"]').forEach((target) => target.textContent = longValue || '________________');
                document.querySelectorAll('[data-preview="certificate-meeting-date-short"]').forEach((target) => target.textContent = shortValue || '');
                return;
            }
            if (input.dataset.liveFormat === 'multiline') {
                const formatted = formatCertificateResolutionBody(value);
                targets.forEach((target) => { target.innerHTML = formatted; });
                return;
            }
            if (targetName === 'certificate-series-no' && !value) value = String(new Date().getFullYear());
            if (targetName === 'certificate-resolution-no') {
                document.querySelectorAll('[data-preview="certificate-resolution-title"]').forEach((target) => {
                    const body = document.querySelector('[name="governing_body"]')?.value || 'Board of Directors';
                    const label = body === 'Stockholders' ? 'STOCKHOLDERS\' RESOLUTION NO. ' : (body === 'Joint Stockholders and Board of Directors' ? 'JOINT BOARD AND STOCKHOLDERS RESOLUTION NO. ' : 'BOARD RESOLUTION NO. ');
                    target.textContent = value ? `${label}${value}` : 'CERTIFIED MINUTES EXTRACT';
                });
            }
            const fallback = input.dataset.liveEmpty || targets[0].dataset.fallbackYear || '';
            targets.forEach((target) => { target.textContent = value || fallback; });
        };

        form.querySelectorAll('[data-live-target]').forEach((input) => {
            input.addEventListener('input', () => applyValue(input));
            input.addEventListener('change', () => applyValue(input));
        });

        if (certificateBodyEditor && certificateBodyInput) {
            const syncCertificateBody = () => {
                const html = String(certificateBodyEditor.innerHTML || '').trim();
                const fallbackHtml = 'Certified from corporate minutes.';
                certificateBodyInput.value = html;
                applyValue(certificateBodyInput);
                if (certificateBodyPreview) {
                    certificateBodyPreview.innerHTML = formatCertificateResolutionBody(html || fallbackHtml);
                }
            };

            certificateBodyEditor.addEventListener('input', syncCertificateBody);

            form.querySelectorAll('[data-secretary-rich-cmd]').forEach((button) => {
                button.addEventListener('click', () => {
                    certificateBodyEditor.focus();
                    document.execCommand(button.dataset.secretaryRichCmd, false, null);
                    syncCertificateBody();
                });
            });

            const fontSelect = form.querySelector('[data-secretary-rich-font]');
            if (fontSelect) {
                fontSelect.addEventListener('change', () => {
                    certificateBodyEditor.focus();
                    document.execCommand('fontName', false, fontSelect.value);
                    syncCertificateBody();
                });
            }

            const sizeSelect = form.querySelector('[data-secretary-rich-size]');
            if (sizeSelect) {
                sizeSelect.addEventListener('change', () => {
                    certificateBodyEditor.focus();
                    document.execCommand('fontSize', false, sizeSelect.value);
                    syncCertificateBody();
                });
            }

            syncCertificateBody();
        }

        form.querySelectorAll('[data-live-target]').forEach((input) => {
            applyValue(input);
        });
    })();
</script>
@endsection

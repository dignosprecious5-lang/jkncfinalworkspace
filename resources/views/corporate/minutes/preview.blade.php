@extends('layouts.app')
@section('title', 'Minutes Preview')

@section('content')
@php
    $sectionRibbonPartial = $sectionRibbonPartial ?? 'corporate.partials.section-ribbon';
    $documentUrl = $minute->document_path ? route('uploads.show', ['path' => $minute->document_path]) : null;
    $documentDownloadUrl = $minute->document_path ? route('uploads.show', ['path' => $minute->document_path, 'download' => 1]) : null;
    $approvedMinutesUrl = $minute->approved_minutes_path ? route('uploads.show', ['path' => $minute->approved_minutes_path]) : null;
    $approvedMinutesDownloadUrl = $minute->approved_minutes_path ? route('uploads.show', ['path' => $minute->approved_minutes_path, 'download' => 1]) : null;
    $tentativeAudioUrl = $minute->tentative_audio_path ? route('uploads.show', ['path' => $minute->tentative_audio_path]) : null;
    $tentativeAudioDownloadUrl = $minute->tentative_audio_path ? route('uploads.show', ['path' => $minute->tentative_audio_path, 'download' => 1]) : null;
    $meetingVideoUrl = $minute->meeting_video_path ? route('uploads.show', ['path' => $minute->meeting_video_path]) : null;
    $meetingVideoDownloadUrl = $minute->meeting_video_path ? route('uploads.show', ['path' => $minute->meeting_video_path, 'download' => 1]) : null;
    $scriptFileUrl = $minute->script_file_path ? route('uploads.show', ['path' => $minute->script_file_path]) : null;
    $scriptFileDownloadUrl = $minute->script_file_path ? route('uploads.show', ['path' => $minute->script_file_path, 'download' => 1]) : null;
    $finalAudioUrl = $minute->final_audio_path ? route('uploads.show', ['path' => $minute->final_audio_path]) : null;
    $finalAudioDownloadUrl = $minute->final_audio_path ? route('uploads.show', ['path' => $minute->final_audio_path, 'download' => 1]) : null;
    $recordingClipOptions = collect($minute->recording_clips ?? [])->map(fn ($path) => [
        'id' => $path,
        'filename' => basename($path),
        'url' => route('uploads.show', ['path' => $path]),
        'download_url' => route('uploads.show', ['path' => $path, 'download' => 1]),
        'saved' => true,
    ])->values();
    $canApproveMinutes = in_array(auth()->user()?->role, ['Admin', 'Super Admin', 'superadmin'], true);
    $minutesDocumentTitle = strtoupper(trim('Minutes of the ' . ($minute->type_of_meeting ?: 'Special') . ' ' . ($minute->governing_body ?: 'Meeting')));
    $templatePreviewUrl = $templatePreviewUrl ?? null;
    $templatePreviewDownloadUrl = $templatePreviewDownloadUrl ?? null;
    $workspaceSaveUrl = $workspaceSaveUrl ?? route('minutes.workspace-save', $minute);
    $finalAudioSaveUrl = $finalAudioSaveUrl ?? route('minutes.final-audio', $minute);
    $finalSaveUrl = $finalSaveUrl ?? route('minutes.final-save', $minute);
    $minutePreviewRoute = $editRoute ?? route('minutes.preview', $minute);

    $activeMinutesPdfVersion = request('version') === 'original' && $approvedMinutesUrl ? 'original' : 'draft';
    $activeMinutesPdfUrl = $activeMinutesPdfVersion === 'original' ? $approvedMinutesUrl : $templatePreviewUrl;
    $activeMinutesPdfDownloadUrl = $activeMinutesPdfVersion === 'original' ? $approvedMinutesDownloadUrl : $templatePreviewDownloadUrl;

    $parseAttendanceRows = function ($value, array $fallback = []) {
        $rows = [];

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $rows = $decoded;
            } else {
                $rows = collect(preg_split('/\r\n|\r|\n/', $value))
                    ->map(function ($line) {
                        $parts = array_map('trim', explode('|', $line, 2));

                        return [
                            'name' => $parts[0] ?? '',
                            'position' => $parts[1] ?? '',
                        ];
                    })
                    ->filter(fn ($row) => ($row['name'] ?? '') !== '' || ($row['position'] ?? '') !== '')
                    ->values()
                    ->all();
            }
        } elseif (is_array($value)) {
            $rows = $value;
        }

        $rows = collect($rows)
            ->map(function ($row) {
                return [
                    'name' => trim((string) ($row['name'] ?? '')),
                    'position' => trim((string) ($row['position'] ?? $row['role'] ?? '')),
                ];
            })
            ->filter(fn ($row) => $row['name'] !== '' || $row['position'] !== '')
            ->values()
            ->all();

        return !empty($rows) ? $rows : $fallback;
    };

    $directorsPresentRows = $parseAttendanceRows($minute->directors_present ?? null, array_values(array_filter([
        $minute->chairman ? ['name' => $minute->chairman, 'position' => 'President/Chairman'] : null,
        $minute->secretary ? ['name' => $minute->secretary, 'position' => 'Corporate Secretary'] : null,
    ])));
    $directorsAbsentRows = $parseAttendanceRows($minute->directors_absent ?? null);
    $secretariatRows = $parseAttendanceRows($minute->secretariat ?? null, $minute->secretary ? [
        ['name' => $minute->secretary, 'position' => 'Corporate Secretary'],
    ] : []);
    $guestRows = $parseAttendanceRows($minute->guests ?? null);
    $governingBodyLower = strtolower((string) ($minute->governing_body ?? ''));
    if (str_contains($governingBodyLower, 'joint')) {
        $attendanceBaseLabel = 'Directors and Stockholders';
    } elseif (str_contains($governingBodyLower, 'stockholder') && !str_contains($governingBodyLower, 'board')) {
        $attendanceBaseLabel = 'Stockholders';
    } else {
        $attendanceBaseLabel = 'Directors';
    }
    $presentHeading = $attendanceBaseLabel . ' Present';
    $absentHeading = $attendanceBaseLabel . ' Absent';
    $jkCompanyName = strtoupper((data_get($corporateContext ?? [], 'company_name') ?: data_get($corporateContext ?? [], 'companyName')) ?: 'JOHN KELLY & COMPANY');
    $jkCompanyAddress = (data_get($corporateContext ?? [], 'company_address') ?: data_get($corporateContext ?? [], 'companyAddress')) ?: '3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000';

    $gisLogoPath = (data_get($corporateContext ?? [], 'logo_path') ?: data_get($corporateContext ?? [], 'logoPath'));
    $gisLogoUrl = null;
    $gisLogoDataUri = null;
    if ($gisLogoPath) {
        $normalizedLogoPath = preg_replace('#^/?storage/#', '', (string) $gisLogoPath);
        try { $gisLogoUrl = route('uploads.show', ['path' => $normalizedLogoPath]); } catch (\Throwable $e) { $gisLogoUrl = asset('storage/' . $normalizedLogoPath); }
        $absoluteLogoPath = storage_path('app/public/' . $normalizedLogoPath);
        if (is_file($absoluteLogoPath)) {
            $mime = function_exists('mime_content_type') ? (mime_content_type($absoluteLogoPath) ?: 'image/png') : 'image/png';
            $gisLogoDataUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($absoluteLogoPath));
        }
    }

    $meetingTitleLine = trim(($minute->type_of_meeting ?: 'Regular') . ' ' . ($minute->governing_body ?: 'Board of Directors') . ' Meeting');
    $meetingDateLine = optional($minute->date_of_meeting)->format('F d, Y') ?: '________________';
    $meetingTimeLine = $minute->time_started ? \Carbon\Carbon::parse($minute->time_started)->format('g:i A') : '________________';
@endphp

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4">
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
        @if (isset($company))
            @include('company.partials.company-header', ['company' => $company])
        @endif

        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100">
            @include($sectionRibbonPartial, ['activeTab' => 'minutes', 'topButtonLabel' => 'Add Minutes'])
        </div>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #minutes-print, #minutes-print * { visibility: visible; }
        #minutes-print { position: absolute; left: 0; top: 0; width: 100%; }
    }

    .minutes-rich-editor[contenteditable="true"][data-placeholder]:empty::before {
        content: attr(data-placeholder);
        color: #94a3b8;
        pointer-events: none;
    }

    #minutes-template-editor {
        min-height: 0 !important;
        line-height: 1.45 !important;
        padding-bottom: 0 !important;
        margin-bottom: 0 !important;
    }

    #minutes-template-editor p,
    #minutes-template-editor div {
        margin-top: 0 !important;
        margin-bottom: 4px !important;
        min-height: 0 !important;
    }

    #minutes-print-signatures {
        margin-top: 30px !important;
        line-height: 1.65 !important;
    }

    .minutes-signoff-block {
        margin-top: 18px !important;
    }

    .minutes-signoff-block:first-child {
        margin-top: 0 !important;
    }


    .minutes-template-workspace {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }

    @media (min-width: 1280px) {
        .minutes-template-workspace {
            grid-template-columns: minmax(0, 840px) minmax(380px, 1fr);
        }
    }

    .minutes-preview-scroller {
        max-height: calc(100vh - 235px);
        overflow: auto;
    }

    .minutes-builder-sticky {
        position: sticky;
        top: 96px;
        max-height: calc(100vh - 130px);
        overflow: auto;
    }

    .minutes-a4-sheet {
        width: 794px;
        min-height: 1123px;
        max-width: none;
    }

    @media (max-width: 1279px) {
        .minutes-builder-sticky {
            position: static;
            max-height: none;
        }

        .minutes-a4-sheet {
            max-width: 100%;
        }
    }
</style>

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4">
    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 px-4 py-4 border-b border-gray-100">
            <a href="{{ $backRoute }}" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <div class="text-lg font-semibold">Minutes Preview</div>
                <div class="text-xs text-gray-500">Reference: {{ $minute->minutes_ref ?? '-' }}</div>
            </div>
        <div class="flex-1"></div>
        <div class="flex items-center gap-2 rounded-full border border-gray-200 bg-gray-50 p-1">
            <button type="button" data-preview-tab-button="template" class="rounded-full bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition">Template Builder</button>
            <button type="button" data-preview-tab-button="ongoing" class="rounded-full px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:bg-gray-200">On-Going Preview</button>
            <button type="button" data-preview-tab-button="final" class="rounded-full px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:bg-gray-200">Final Preview</button>
        </div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $minute->approved_by ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                {{ $minute->approved_by ? 'Approved' : 'Pending approval' }}
            </span>
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">{{ $minute->meeting_mode ?? '-' }}</span>
            @if($canApproveMinutes)
                <a href="#minutes-approval-card" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold">
                    {{ $minute->approved_by ? 'Update Approval' : 'Approve Minutes' }}
                </a>
            @endif
            <a href="{{ $editRoute }}" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold">Edit</a>
            <form method="POST" action="{{ $deleteRoute }}" onsubmit="return confirm('Delete these minutes?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-semibold">Delete</button>
            </form>
        </div>

        <div class="space-y-6 p-6">
            <div class="space-y-4">
                <section data-preview-tab="ongoing" class="space-y-4">
                    <div class="bg-gray-900 rounded-xl overflow-hidden">
                        <div class="bg-gray-800 px-4 py-3 flex items-center gap-2 border-b border-gray-700">
                            <span class="text-gray-300 text-sm font-medium">On-Going Meeting Workspace</span>
                            <div class="flex-1"></div>
                            <button
                                type="button"
                                class="p-2 hover:bg-gray-700 rounded text-gray-300 transition disabled:cursor-not-allowed disabled:opacity-40"
                                @disabled(!$documentUrl)
                                onclick="handleMinutesPrint(@js($documentUrl))">
                                <i class="fas fa-print"></i>
                            </button>
                            <button
                                type="button"
                                class="p-2 hover:bg-gray-700 rounded text-gray-300 transition disabled:cursor-not-allowed disabled:opacity-40"
                                @disabled(!$documentDownloadUrl)
                                onclick="handleMinutesDownload(@js($documentDownloadUrl))">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                        <div class="p-6 text-center text-gray-300">
                            <p class="text-lg font-semibold">Capture the live meeting package</p>
                            <p class="text-sm text-gray-400 mt-1">{{ $minute->location ?? '-' }} • {{ optional($minute->date_of_meeting)->format('Y-m-d') ?? '-' }}</p>
                            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                                <button id="record-start-btn" type="button" class="px-5 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-medium">Record</button>
                                <button id="record-pause-btn" type="button" class="px-5 py-2 bg-amber-500 text-gray-950 rounded-lg hover:bg-amber-400 transition text-sm font-medium hidden">Pause</button>
                                <button id="record-resume-btn" type="button" class="px-5 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-medium hidden">Resume</button>
                                <button id="record-stop-btn" type="button" class="px-5 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-800 transition text-sm font-medium hidden">Stop</button>
                                <label for="minutes-video-upload" class="cursor-pointer px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">Attach Meeting Video</label>
                            </div>
                            @if($minute->call_link)
                                <p class="text-xs text-gray-400 mt-3">Call Link: {{ $minute->call_link }}</p>
                            @endif
                            <div id="audio-recorder-panel" class="mt-6 max-w-xl mx-auto rounded-xl border border-gray-700 bg-gray-950/70 p-4 text-left">
                                <div class="flex items-center gap-3">
                                    <div id="audio-recorder-dot" class="h-3 w-3 rounded-full bg-gray-500"></div>
                                    <div>
                                        <div id="audio-recorder-status" class="text-sm font-semibold text-white">Ready to record audio</div>
                                        <div class="text-xs text-gray-400">Use Record, Pause, Resume, and Stop. Each finished take is added to the recordings list below.</div>
                                    </div>
                                </div>
                                <div class="mt-4 text-xs text-gray-400">Duration: <span id="audio-recorder-timer">00:00</span></div>
                                <div id="audio-recorder-error" class="mt-4 hidden rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-xs text-red-200"></div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Live Attachments</div>
                                <div class="text-xs text-gray-500">Add the media and script that will be bundled into the final minutes view. Tentative drafts are saved locally for this minutes record.</div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <div id="minutes-save-status" class="inline-flex items-center rounded-full bg-slate-100 px-3 py-2 text-xs font-medium text-slate-600">Draft not saved yet</div>
                                <a id="minutes-video-download" href="#" class="hidden px-4 py-2 rounded-lg bg-gray-800 hover:bg-black text-white text-sm font-medium">Download Video Copy</a>
                            </div>
                        </div>

                        <div class="rounded-xl border border-emerald-200 bg-white p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">Tentative Recording</div>
                                    <div class="text-xs text-gray-500">Select a recording below, then compile it into the final preview when the meeting ends.</div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <label for="minutes-audio-upload" class="cursor-pointer inline-flex rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Upload Recording</label>
                                </div>
                            </div>
                            <input id="minutes-audio-upload" type="file" accept="audio/*" class="hidden">
                            <div id="minutes-audio-meta" class="hidden mt-3 flex flex-wrap items-center gap-2 text-xs text-gray-600">
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 font-semibold text-emerald-700">Tentative Audio Ready</span>
                                <span id="minutes-audio-filename" class="font-medium text-gray-700"></span>
                            </div>
                            <audio id="minutes-audio-player" controls class="mt-3 w-full hidden"></audio>
                            <div id="minutes-audio-empty" class="mt-3 text-sm text-gray-500">No tentative audio recording captured yet. Record in person or upload an audio file above.</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">Recordings List</div>
                                    <div class="text-xs text-gray-500">Every finished recording appears here. You can select one, remove a mistaken take, or compile the chosen take into the final preview.</div>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span id="recordings-list-status" class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">No recordings yet</span>
                                    <button id="minutes-compile-audio" type="button" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Compile to Final Preview</button>
                                </div>
                            </div>
                            <div id="recordings-list" class="mt-4 space-y-3"></div>
                            <div id="recordings-list-empty" class="mt-3 text-sm text-gray-500">Recorded clips will appear here after you stop a recording or upload an audio file.</div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Meeting Video</label>
                                    <div class="flex flex-wrap gap-2">
                                        <button id="minutes-save-video" type="button" class="rounded-lg bg-slate-800 px-3 py-2 text-xs font-semibold text-white hover:bg-black">Save Video</button>
                                        <button id="minutes-remove-video" type="button" class="rounded-lg border border-red-300 bg-white px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Remove Video</button>
                                    </div>
                                </div>
                                <input id="minutes-video-upload" type="file" accept="video/*" class="mt-2 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-gray-800 file:text-white hover:file:bg-black">
                                <div id="minutes-video-meta" class="hidden mt-3 flex flex-wrap items-center gap-2 text-xs text-gray-600">
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 font-semibold text-blue-700">Video Attached</span>
                                    <span id="minutes-video-filename" class="font-medium text-gray-700"></span>
                                </div>
                                <video id="minutes-video-player" controls class="mt-3 w-full rounded-lg hidden"></video>
                                <div id="minutes-video-empty" class="mt-3 text-sm text-gray-500">Attach a meeting recording or exported call video for the final packet.</div>
                            </div>

                            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Script File</label>
                                    <div class="flex flex-wrap gap-2">
                                        <button id="minutes-save-script" type="button" class="rounded-lg bg-amber-600 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-700">Save Script File</button>
                                        <button id="minutes-remove-script" type="button" class="rounded-lg border border-red-300 bg-white px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Remove Script File</button>
                                    </div>
                                </div>
                                <input id="minutes-script-upload" type="file" accept=".pdf,.doc,.docx,.txt" class="mt-2 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-amber-600 file:text-white hover:file:bg-amber-700">
                                <div id="minutes-script-file" class="hidden mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                                    <div class="font-semibold">Attached Script</div>
                                    <div id="minutes-script-filename" class="mt-1"></div>
                                    <a id="minutes-script-download" href="#" class="mt-3 inline-flex px-3 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold">Download Script File</a>
                                </div>
                                <div id="minutes-script-empty" class="mt-3 text-sm text-gray-500">Upload a supporting script, transcript, or speaking guide.</div>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-xl p-4 space-y-3">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Meeting Script</div>
                                <div class="text-xs text-gray-500">Keep the speaking script or transcript inside the on-going meeting workspace.</div>
                            </div>
                            <textarea id="minutes-script-editor" rows="10" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm" placeholder="Write the script, discussion outline, or final speaking flow here...">{{ $minute->script_text ?? '' }}</textarea>
                        </div>
                    </div>

                </section>

                <section data-preview-tab="template" class="hidden space-y-4">
                    <div class="minutes-template-workspace">
                        {{-- LEFT SIDE: A4 TEMPLATE PREVIEW --}}
                        <div class="min-w-0 rounded-2xl border border-slate-200 overflow-hidden bg-[#f8fafc] flex flex-col self-start">
                            <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center gap-2 bg-white">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">Minutes Template Preview</div>
                                    <div class="text-xs text-gray-500">Preview the minutes format before saving the final minutes.</div>
                                </div>
                                <div class="flex-1"></div>
                                <div class="inline-flex rounded-full bg-slate-100 p-1 text-xs font-semibold">
                                    <a href="{{ $minutePreviewRoute }}?version=draft" class="rounded-full px-3 py-1 {{ $activeMinutesPdfVersion === 'draft' ? 'bg-white text-blue-700 shadow' : 'text-slate-600 hover:text-slate-900' }}">Draft</a>
                                    <a href="{{ $approvedMinutesUrl ? $minutePreviewRoute . '?version=original' : '#' }}" class="rounded-full px-3 py-1 {{ $activeMinutesPdfVersion === 'original' ? 'bg-white text-blue-700 shadow' : 'text-slate-600 hover:text-slate-900' }} {{ $approvedMinutesUrl ? '' : 'pointer-events-none opacity-50' }}">Approved / Signed</a>
                                </div>
                                <a
                                    id="minutes-template-download-btn"
                                    href="{{ $activeMinutesPdfDownloadUrl ?: '#' }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 {{ $activeMinutesPdfDownloadUrl ? '' : 'pointer-events-none opacity-50' }}"
                                >
                                    <i class="fas fa-download"></i>
                                    Download {{ $activeMinutesPdfVersion === 'original' ? 'Approved / Signed' : 'Draft' }} PDF
                                </a>
                            </div>

                            <div class="minutes-preview-scroller p-4">
                                <div
                                    class="minutes-a4-sheet mx-auto rounded-sm bg-white shadow-[0_18px_50px_rgba(15,23,42,0.08)]"
                                    style="font-family: Georgia, 'Times New Roman', serif; padding: 56px 64px;"
                                >
                                    <div class="text-center">
                                        @if($gisLogoUrl)
                                            <img src="{{ $gisLogoUrl }}" alt="Company Logo" class="mx-auto mb-2 h-16 w-auto object-contain">
                                        @else
                                            <div class="text-[32px] leading-tight font-semibold uppercase" style="font-family: Georgia, 'Times New Roman', serif;">{{ $jkCompanyName }}</div>
                                        @endif
                                        <div class="mt-4 text-[15px]">{{ $jkCompanyAddress }}</div>

                                        <div class="mt-8 text-[18px] font-bold uppercase">MINUTES OF THE</div>
                                        <div class="text-[17px]">{{ $meetingTitleLine }}</div>
                                        <div class="text-[17px]">of</div>
                                        <div class="text-[18px] uppercase">{{ $jkCompanyName }}</div>

                                        <div class="mt-8 text-[16px]">held at</div>
                                        <div class="mt-3 text-[16px] leading-6">{{ $minute->location ?: '________________' }}</div>
                                        @if($minute->meeting_mode)
                                            <div class="mt-2 text-[16px] leading-6">and Mode of Meeting: {{ $minute->meeting_mode }}</div>
                                        @endif
                                        @if($minute->call_link && !str_contains(strtolower((string) $minute->meeting_mode), 'physical'))
                                            <div class="mt-1 text-[16px] leading-6">
                                                Meeting Link:<br>
                                                <span style="color:#2563eb;text-decoration:underline;word-break:break-all;">{{ $minute->call_link }}</span>
                                            </div>
                                        @endif

                                        <div class="mt-5 text-[16px]">on</div>
                                        <div class="text-[16px]">{{ $meetingDateLine }}</div>
                                        <div class="text-[16px]">at {{ $meetingTimeLine }}</div>
                                    </div>

                                    <div class="mt-5 text-[15px] leading-7">
                                        <div class="font-bold text-[16px]">{{ $presentHeading }}</div>
                                        <table class="mt-1 w-full table-fixed border-collapse">
                                            <thead>
                                                <tr>
                                                    <th class="w-[45%] py-1 text-left font-bold">Name</th>
                                                    <th class="py-1 text-left font-bold">Position</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($directorsPresentRows as $row)
                                                    <tr>
                                                        <td class="py-1 pr-4">{{ $row['name'] ?: '________________' }}</td>
                                                        <td class="py-1">{{ $row['position'] ?: '________________' }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td class="py-1 pr-4">________________</td>
                                                        <td class="py-1">________________</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>

                                        <div class="mt-2 font-bold text-[16px]">{{ $absentHeading }}</div>
                                        <table class="mt-1 w-full table-fixed border-collapse">
                                            <thead>
                                                <tr>
                                                    <th class="w-[45%] py-1 text-left font-bold">Name</th>
                                                    <th class="py-1 text-left font-bold">Position</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($directorsAbsentRows as $row)
                                                    <tr>
                                                        <td class="py-1 pr-4">{{ $row['name'] ?: '________________' }}</td>
                                                        <td class="py-1">{{ $row['position'] ?: '________________' }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td class="py-1 pr-4 font-bold" colspan="2">NO ABSENT</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>

                                        <div class="mt-6 font-bold text-[16px]">Corporate Secretary</div>
                                        <table class="mt-1 w-full table-fixed border-collapse">
                                            <thead>
                                                <tr>
                                                    <th class="w-[45%] py-1 text-left font-bold">Name</th>
                                                    <th class="py-1 text-left font-bold">Role</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($secretariatRows as $row)
                                                    <tr>
                                                        <td class="py-1 pr-4">{{ $row['name'] ?: '________________' }}</td>
                                                        <td class="py-1">{{ $row['position'] ?: '________________' }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td class="py-1 pr-4">________________</td>
                                                        <td class="py-1">Corporate Secretary</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>

                                        <div class="mt-2 font-bold text-[16px]">Guests</div>
                                        <table class="mt-1 w-full table-fixed border-collapse">
                                            <thead>
                                                <tr>
                                                    <th class="w-[45%] py-1 text-left font-bold">Name</th>
                                                    <th class="py-1 text-left font-bold">Role</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($guestRows as $row)
                                                    <tr>
                                                        <td class="py-1 pr-4">{{ $row['name'] ?: '________________' }}</td>
                                                        <td class="py-1">{{ $row['position'] ?: '________________' }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td class="py-1 pr-4 font-bold" colspan="2">NO GUESTS</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="mt-3">
                                        <div class="text-[15px] font-bold">Minutes Proper:</div>
                                        <div id="minutes-template-editor" class="minutes-rich-editor mt-1 min-h-0 whitespace-pre-wrap text-[15px] leading-6 text-slate-900 outline-none" contenteditable="true" data-placeholder="Type the minutes following the template here..."></div>
                                    </div>

                                    <div id="minutes-print-signatures" class="mt-8 text-[15px] leading-7">
                                        <div class="minutes-signoff-block">
                                            <div class="font-bold">Prepared by:</div>
                                            <div class="mt-2 font-bold uppercase">{{ $minute->secretary ?: '________________' }}</div>
                                            <div class="font-bold">Corporate Secretary</div>
                                        </div>

                                        <div class="minutes-signoff-block mt-8">
                                            <div class="font-bold">Attested by:</div>
                                            <div class="mt-2 font-bold uppercase">{{ $minute->chairman ?: '________________' }}</div>
                                            <div class="font-bold">Chairman of the Meeting</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- RIGHT SIDE: BODY BUILDER --}}
                        <div class="minutes-builder-sticky min-w-0 rounded-2xl border border-gray-200 bg-white overflow-hidden self-start">
                            <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">Minutes Body Builder</div>
                                        <div class="mt-1 text-xs text-gray-500">Write the minutes here with formatting tools. The template and final preview use this exact content.</div>
                                    </div>
                                    <button id="minutes-save-body" type="button" class="shrink-0 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Save Changes</button>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 px-4 py-3 bg-white">
                                <select id="notes-font" class="rounded-lg border border-gray-300 px-2 py-1 text-xs">
                                    <option value="Arial">Arial</option>
                                    <option value="Times New Roman">Times New Roman</option>
                                    <option value="Georgia">Georgia</option>
                                    <option value="Verdana">Verdana</option>
                                    <option value="Courier New">Courier New</option>
                                </select>
                                <select id="notes-size" class="rounded-lg border border-gray-300 px-2 py-1 text-xs">
                                    <option value="1">10</option>
                                    <option value="2">12</option>
                                    <option value="3" selected>14</option>
                                    <option value="4">16</option>
                                    <option value="5">18</option>
                                </select>
                                <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-cmd="bold">Bold</button>
                                <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-cmd="italic">Italic</button>
                                <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-cmd="underline">Underline</button>
                                <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-cmd="insertUnorderedList">Bullets</button>
                                <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-cmd="insertOrderedList">Numbering</button>
                                <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-cmd="justifyLeft">Left</button>
                                <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-cmd="justifyCenter">Center</button>
                                <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-cmd="justifyRight">Right</button>
                                <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-cmd="hiliteColor" data-value="yellow">Highlight</button>
                                <button type="button" class="px-2 py-1 border border-gray-300 rounded-lg text-xs" data-cmd="removeFormat">Clear</button>
                            </div>

                            <div id="notes-editor" class="minutes-rich-editor min-h-[420px] p-4 text-sm leading-7 outline-none" contenteditable="true" data-placeholder="Type the minutes of meeting here...">{!! $minute->recording_notes ?: '' !!}</div>

                            <div class="border-t border-gray-100 bg-gray-50 p-4">
                                <div class="text-sm font-semibold text-gray-900">Template Notes</div>
                                <div class="mt-1 text-xs text-gray-500">This builder page mirrors the minutes template arrangement: centered heading, held at, on, attendance section, minutes proper, and sign-off area.</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section data-preview-tab="final" class="hidden space-y-4">
                    <div id="minutes-print" class="rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-blue-50 p-6 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-4">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-700">Final Minutes Preview</div>
                                <h2 class="mt-2 text-2xl font-semibold text-slate-900">{{ $minute->governing_body ?? 'Minutes of Meeting' }}</h2>
                                <p class="mt-1 text-sm text-slate-600">{{ $minute->type_of_meeting ?? 'Meeting' }} meeting for {{ optional($minute->date_of_meeting)->format('F d, Y') ?? '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-blue-200 bg-white px-4 py-3 text-sm text-slate-700">
                                <div class="font-semibold text-slate-900">{{ $minute->minutes_ref ?? '-' }}</div>
                                <div class="mt-1">Prepared by {{ $minute->secretary ?? '-' }}</div>
                                <div>{{ $minute->uploaded_by ?? 'System User' }}</div>
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button id="minutes-final-save" type="button" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                Save
                            </button>
                        </div>
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Meeting Snapshot</div>
                                <div class="mt-3 space-y-2 text-sm text-slate-700">
                                    <div><span class="font-semibold text-slate-900">Date:</span> {{ optional($minute->date_of_meeting)->format('F d, Y') ?? '-' }}</div>
                                    <div><span class="font-semibold text-slate-900">Time:</span> {{ $minute->time_started ?? '-' }} - {{ $minute->time_ended ?? '-' }}</div>
                                    <div><span class="font-semibold text-slate-900">Location:</span> {{ $minute->location ?? '-' }}</div>
                                    <div><span class="font-semibold text-slate-900">Mode:</span> {{ $minute->meeting_mode ?? '-' }}</div>
                                </div>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Attached Final Files</div>
                                <div class="mt-3 space-y-3 text-sm text-slate-700">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <span class="min-w-0 break-words">Minutes PDF</span>
                                        @if($documentDownloadUrl)
                                            <a href="{{ $documentDownloadUrl }}" class="shrink-0 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">Download</a>
                                        @else
                                            <span class="shrink-0 text-xs text-slate-400">No file uploaded</span>
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <span class="min-w-0 break-words">Approved / Signed Minutes</span>
                                        @if($approvedMinutesDownloadUrl)
                                            <a href="{{ $approvedMinutesDownloadUrl }}" class="shrink-0 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Download</a>
                                        @else
                                            <span class="shrink-0 text-xs text-slate-400">No approved file uploaded</span>
                                        @endif
                                    </div>
                                    @if($canApproveMinutes)
                                        <form id="minutes-approval-card" method="POST" action="{{ route('minutes.approve', $minute) }}" enctype="multipart/form-data" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                                            @csrf
                                            <div class="text-xs font-semibold text-emerald-800">Upload Approved / Signed Minutes</div>
                                            <input type="file" name="approved_minutes_path" accept="application/pdf" class="mt-2 block w-full rounded-lg border border-emerald-200 bg-white px-3 py-2 text-xs" {{ $approvedMinutesDownloadUrl ? '' : 'required' }}>
                                            <button type="submit" class="mt-2 w-full rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Save Approved / Signed Copy</button>
                                        </form>
                                    @endif
                                    <div id="final-script-file-row" class="hidden flex flex-wrap items-center justify-between gap-3">
                                        <span id="final-script-file-label" class="min-w-0 flex-1 break-all">Attached Script</span>
                                        <a id="final-script-file-download" href="#" class="shrink-0 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">Download</a>
                                    </div>
                                    <div id="final-media-empty" class="text-xs text-slate-400">Audio and video attachments will appear here once added in the on-going preview.</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 xl:grid-cols-2 gap-4">
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">Final Recording</div>
                                        <div class="text-xs text-slate-500">Listen to the captured or uploaded meeting audio.</div>
                                    </div>
                                    <span id="final-audio-badge" class="hidden rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Ready</span>
                                </div>
                                <div id="final-audio-meta" class="hidden mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Final Recording File</div>
                                    <div id="final-audio-filename" class="mt-1 text-sm font-medium text-slate-900"></div>
                                    <a id="final-audio-download" href="#" download="minutes-recording.webm" class="mt-3 inline-flex rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Download Final Recording</a>
                                </div>
                                <audio id="final-audio-player" controls class="mt-4 w-full hidden"></audio>
                                <div id="final-audio-empty" class="mt-4 text-sm text-slate-500">No audio linked yet.</div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">Final Video Attachment</div>
                                        <div class="text-xs text-slate-500">Watch the attached recording beside the final minutes.</div>
                                    </div>
                                    <span id="final-video-badge" class="hidden rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">Attached</span>
                                </div>
                                <video id="final-video-player" controls class="mt-4 w-full rounded-xl hidden"></video>
                                <div id="final-video-empty" class="mt-4 text-sm text-slate-500">No video attached yet.</div>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 xl:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.7fr)] gap-4">
                            <div class="rounded-xl border border-slate-200 bg-white p-5">
                                <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-4">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">Minutes Template PDF Preview</div>
                                        <div class="mt-1 text-xs text-slate-500">PDF version generated from the template builder content used in the final minutes packet.</div>
                                    </div>
                                    <div class="text-right text-[11px] leading-5 text-slate-500">
                                        <div>{{ $minute->minutes_ref ?? '-' }}</div>
                                        <div>{{ optional($minute->date_of_meeting)->format('F d, Y') ?? '-' }}</div>
                                    </div>
                                </div>
                                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 overflow-hidden">
                                    @if($activeMinutesPdfUrl)
                                        <iframe id="minutes-template-pdf-frame" src="{{ $activeMinutesPdfUrl }}" class="h-[720px] w-full bg-white"></iframe>
                                        <div id="minutes-template-pdf-empty" class="hidden px-6 py-10 text-sm text-slate-500">The PDF preview will appear here after the final preview is generated.</div>
                                    @else
                                        <iframe id="minutes-template-pdf-frame" src="" class="hidden h-[720px] w-full bg-white"></iframe>
                                        <div id="minutes-template-pdf-empty" class="px-6 py-10 text-sm text-slate-500">The PDF preview will appear here after the final preview is generated.</div>
                                    @endif
                                </div>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white p-5">
                                <div class="text-sm font-semibold text-slate-900">Final Script</div>
                                <div id="final-script-output" class="mt-4 whitespace-pre-wrap text-sm leading-6 text-slate-700"></div>
                                <div id="final-script-empty" class="mt-4 text-sm text-slate-500">No script drafted yet.</div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
<script>
    window.handleMinutesPrint = (url) => {
        if (!url) {
            return;
        }

        window.open(url, '_blank', 'noopener,noreferrer');
    };

    window.handleMinutesDownload = (url) => {
        if (!url) {
            return;
        }

        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.target = '_blank';
        anchor.rel = 'noopener noreferrer';
        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();
    };

    (() => {
        const buttons = Array.from(document.querySelectorAll('[data-preview-tab-button]'));
        const tabs = Array.from(document.querySelectorAll('[data-preview-tab]'));
        if (!buttons.length || !tabs.length) return;

        const setActiveTab = (target) => {
            tabs.forEach((tab) => {
                tab.classList.toggle('hidden', tab.dataset.previewTab !== target);
            });

            buttons.forEach((button) => {
                const active = button.dataset.previewTabButton === target;
                button.className = active
                    ? 'rounded-full bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition'
                    : 'rounded-full px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:bg-gray-200';
            });
        };

        buttons.forEach((button) => {
            button.addEventListener('click', () => setActiveTab(button.dataset.previewTabButton));
        });

        setActiveTab('template');
    })();

    const editor = document.getElementById('notes-editor');
    const fontSelect = document.getElementById('notes-font');
    const sizeSelect = document.getElementById('notes-size');
    const scriptEditor = document.getElementById('minutes-script-editor');
    const templateEditor = document.getElementById('minutes-template-editor');
    const finalScriptOutput = document.getElementById('final-script-output');
    const finalScriptEmpty = document.getElementById('final-script-empty');
    const templatePdfFrame = document.getElementById('minutes-template-pdf-frame');
    const templatePdfEmpty = document.getElementById('minutes-template-pdf-empty');
    const templateDownloadButton = document.getElementById('minutes-template-download-btn');

    let activeMinutesEditor = editor;

    const compactMinutesHtml = (html = '') => {
        let output = String(html || '').trim();

        // Remove empty trailing editor blocks that create a huge blank gap before Prepared by / Attested by.
        const emptyTailPattern = /(?:\s|&nbsp;|<br\s*\/?>(?:\s|&nbsp;)*|<p[^>]*>(?:\s|&nbsp;|<br\s*\/?\s*)*<\/p>|<div[^>]*>(?:\s|&nbsp;|<br\s*\/?\s*)*<\/div>)+$/gi;
        let previous;
        do {
            previous = output;
            output = output.replace(emptyTailPattern, '').trim();
        } while (output !== previous);

        return output;
    };

    const updateMinutesEditors = (html, source = 'builder') => {
        if (source !== 'builder' && editor) {
            editor.innerHTML = html;
        }

        if (source !== 'template' && templateEditor) {
            templateEditor.innerHTML = compactMinutesHtml(html);
        }
    };

    const syncFinalNotes = (source = 'builder') => {
        if (!editor) return;
        const html = source === 'preview'
            ? ''
            : source === 'template'
                ? (templateEditor?.innerHTML || '')
                : editor.innerHTML;
        updateMinutesEditors(html, source);
    };

    const syncFinalScript = () => {
        if (!scriptEditor || !finalScriptOutput || !finalScriptEmpty) return;
        const text = scriptEditor.value.trim();
        finalScriptOutput.textContent = text;
        finalScriptOutput.classList.toggle('hidden', text === '');
        finalScriptEmpty.classList.toggle('hidden', text !== '');
    };

    const syncTemplatePdfPreview = (url = null, downloadUrl = null) => {
        if (templateDownloadButton) {
            if (downloadUrl) {
                templateDownloadButton.href = downloadUrl;
                templateDownloadButton.classList.remove('pointer-events-none', 'opacity-50');
            } else {
                templateDownloadButton.href = '#';
                templateDownloadButton.classList.add('pointer-events-none', 'opacity-50');
            }
        }

        if (!templatePdfFrame || !templatePdfEmpty) {
            return;
        }

        if (!url) {
            templatePdfFrame.classList.add('hidden');
            templatePdfFrame.removeAttribute('src');
            templatePdfEmpty.classList.remove('hidden');
            return;
        }

        templatePdfFrame.src = url;
        templatePdfFrame.classList.remove('hidden');
        templatePdfEmpty.classList.add('hidden');
    };

    if (editor) {
        editor.addEventListener('focus', () => { activeMinutesEditor = editor; });
        editor.addEventListener('input', () => syncFinalNotes('builder'));
    }
    if (templateEditor) {
        templateEditor.addEventListener('focus', () => { activeMinutesEditor = templateEditor; });
        templateEditor.addEventListener('input', () => syncFinalNotes('template'));
    }
    if (scriptEditor) scriptEditor.addEventListener('input', syncFinalScript);

    document.querySelectorAll('[data-cmd]').forEach((button) => {
        button.addEventListener('click', () => {
            (activeMinutesEditor || editor)?.focus();
            document.execCommand(button.dataset.cmd, false, button.dataset.value || null);
            syncFinalNotes(activeMinutesEditor === templateEditor ? 'template' : 'builder');
        });
    });

    if (fontSelect) {
        fontSelect.addEventListener('change', () => {
            (activeMinutesEditor || editor)?.focus();
            document.execCommand('fontName', false, fontSelect.value);
            syncFinalNotes(activeMinutesEditor === templateEditor ? 'template' : 'builder');
        });
    }

    if (sizeSelect) {
        sizeSelect.addEventListener('change', () => {
            (activeMinutesEditor || editor)?.focus();
            document.execCommand('fontSize', false, sizeSelect.value);
            syncFinalNotes(activeMinutesEditor === templateEditor ? 'template' : 'builder');
        });
    }

    syncFinalNotes();
    syncFinalScript();
    syncTemplatePdfPreview(@js($templatePreviewUrl), @js($templatePreviewDownloadUrl));

    (() => {
        const startButton = document.getElementById('record-start-btn');
        const pauseButton = document.getElementById('record-pause-btn');
        const resumeButton = document.getElementById('record-resume-btn');
        const stopButton = document.getElementById('record-stop-btn');
        const status = document.getElementById('audio-recorder-status');
        const timer = document.getElementById('audio-recorder-timer');
        const errorBox = document.getElementById('audio-recorder-error');
        const dot = document.getElementById('audio-recorder-dot');
        const previewPlayer = document.getElementById('minutes-audio-player');
        const previewEmpty = document.getElementById('minutes-audio-empty');
        const previewMeta = document.getElementById('minutes-audio-meta');
        const previewFilename = document.getElementById('minutes-audio-filename');
        const recordingsList = document.getElementById('recordings-list');
        const recordingsListEmpty = document.getElementById('recordings-list-empty');
        const recordingsListStatus = document.getElementById('recordings-list-status');
        const uploadInput = document.getElementById('minutes-audio-upload');
        const videoInput = document.getElementById('minutes-video-upload');
        const videoPlayer = document.getElementById('minutes-video-player');
        const videoEmpty = document.getElementById('minutes-video-empty');
        const videoMeta = document.getElementById('minutes-video-meta');
        const videoFilename = document.getElementById('minutes-video-filename');
        const videoDownload = document.getElementById('minutes-video-download');
        const scriptUpload = document.getElementById('minutes-script-upload');
        const scriptFile = document.getElementById('minutes-script-file');
        const scriptEmpty = document.getElementById('minutes-script-empty');
        const scriptFilename = document.getElementById('minutes-script-filename');
        const scriptDownload = document.getElementById('minutes-script-download');
        const finalAudioPlayer = document.getElementById('final-audio-player');
        const finalAudioEmpty = document.getElementById('final-audio-empty');
        const finalAudioBadge = document.getElementById('final-audio-badge');
        const finalAudioMeta = document.getElementById('final-audio-meta');
        const finalAudioFilename = document.getElementById('final-audio-filename');
        const finalAudioDownload = document.getElementById('final-audio-download');
        const compileAudioButton = document.getElementById('minutes-compile-audio');
        const saveVideoButton = document.getElementById('minutes-save-video');
        const removeVideoButton = document.getElementById('minutes-remove-video');
        const saveScriptButton = document.getElementById('minutes-save-script');
        const removeScriptButton = document.getElementById('minutes-remove-script');
        const finalSaveButton = document.getElementById('minutes-final-save');
        const bodySaveButton = document.getElementById('minutes-save-body');
        const finalVideoPlayer = document.getElementById('final-video-player');
        const finalVideoEmpty = document.getElementById('final-video-empty');
        const finalVideoBadge = document.getElementById('final-video-badge');
        const finalScriptFileRow = document.getElementById('final-script-file-row');
        const finalScriptFileLabel = document.getElementById('final-script-file-label');
        const finalScriptFileDownload = document.getElementById('final-script-file-download');
        const finalMediaEmpty = document.getElementById('final-media-empty');
        const saveStatus = document.getElementById('minutes-save-status');

        if (
            !startButton || !pauseButton || !resumeButton || !stopButton || !status || !timer || !errorBox || !dot ||
            !previewPlayer || !previewEmpty || !previewMeta || !previewFilename ||
            !recordingsList || !recordingsListEmpty || !recordingsListStatus ||
            !uploadInput || !videoInput || !videoPlayer || !videoEmpty || !videoMeta || !videoFilename ||
            !videoDownload || !scriptUpload || !scriptFile || !scriptEmpty || !scriptFilename ||
            !scriptDownload || !finalAudioPlayer || !finalAudioEmpty || !finalAudioBadge ||
            !finalAudioMeta || !finalAudioFilename || !finalAudioDownload ||
            !compileAudioButton ||
            !saveVideoButton || !removeVideoButton || !saveScriptButton || !removeScriptButton || !finalSaveButton ||
            !finalVideoPlayer || !finalVideoEmpty || !finalVideoBadge || !finalScriptFileRow ||
            !finalScriptFileLabel || !finalScriptFileDownload || !finalMediaEmpty ||
            !saveStatus
        ) {
            return;
        }

        const draftKey = @js('minute-draft-' . $minute->id);
        const draftDbName = 'minutes-tentative-records';
        const draftStoreName = 'drafts';
        let mediaRecorder = null;
        let mediaStream = null;
        let chunks = [];
        let startedAt = null;
        let timerHandle = null;
        let currentAudioBlob = null;
        let recordingClips = @js($recordingClipOptions);
        let selectedRecordingId = recordingClips[0]?.id || null;
        let finalAudioBlob = null;
        let finalAudioUrl = null;
        let currentVideoFile = null;
        let currentVideoFilename = '';
        let currentScriptFile = null;
        let currentScriptFilename = '';
        let uploadedVideoUrl = null;
        let uploadedScriptUrl = null;
        let currentAudioFilename = 'minutes-recording.webm';
        let finalAudioFilenameValue = 'minutes-recording.webm';
        let saveTimeout = null;

        const cleanupObjectUrl = (value) => {
            if (value) URL.revokeObjectURL(value);
        };

        const csrfToken = @js(csrf_token());
        const workspaceSaveUrl = @js($workspaceSaveUrl);
        const finalAudioSaveUrl = @js($finalAudioSaveUrl);
        const finalSaveUrl = @js($finalSaveUrl);

        const setSaveStatus = (message, tone = 'slate') => {
            const tones = {
                slate: 'bg-slate-100 text-slate-600',
                blue: 'bg-blue-100 text-blue-700',
                emerald: 'bg-emerald-100 text-emerald-700',
                amber: 'bg-amber-100 text-amber-700',
                red: 'bg-red-100 text-red-700',
            };

            saveStatus.className = `inline-flex items-center rounded-full px-3 py-2 text-xs font-medium ${tones[tone] || tones.slate}`;
            saveStatus.textContent = message;
        };

        const extractErrorMessage = async (response, fallback) => {
            try {
                const payload = await response.json();

                if (payload?.message) {
                    return payload.message;
                }

                const fieldMessage = Object.values(payload?.errors || {})[0]?.[0];
                if (fieldMessage) {
                    return fieldMessage;
                }
            } catch (error) {
                // Ignore JSON parse issues and fall back to the provided message.
            }

            return fallback;
        };

        const fileFromBlob = (blob, filename, fallbackType) => {
            if (!(blob instanceof Blob)) {
                return null;
            }

            if (blob instanceof File) {
                return blob;
            }

            return new File([blob], filename, { type: blob.type || fallbackType });
        };

        const resolveStandaloneFile = async (file, url, filename, fallbackType) => {
            if (file instanceof File) {
                return file;
            }

            if (!url || url === '#') {
                return null;
            }

            const response = await fetch(url);
            if (!response.ok) {
                throw new Error('file-fetch-failed');
            }

            const blob = await response.blob();
            return fileFromBlob(blob, filename || 'attachment', blob.type || fallbackType);
        };

        const openDraftDb = () => new Promise((resolve, reject) => {
            const request = window.indexedDB.open(draftDbName, 1);

            request.onupgradeneeded = () => {
                const db = request.result;
                if (!db.objectStoreNames.contains(draftStoreName)) {
                    db.createObjectStore(draftStoreName, { keyPath: 'id' });
                }
            };

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });

        const getDraft = async () => {
            const db = await openDraftDb();

            return new Promise((resolve, reject) => {
                const tx = db.transaction(draftStoreName, 'readonly');
                const store = tx.objectStore(draftStoreName);
                const request = store.get(draftKey);

                request.onsuccess = () => resolve(request.result || null);
                request.onerror = () => reject(request.error);
                tx.oncomplete = () => db.close();
            });
        };

        const putDraft = async (payload) => {
            const db = await openDraftDb();

            return new Promise((resolve, reject) => {
                const tx = db.transaction(draftStoreName, 'readwrite');
                tx.objectStore(draftStoreName).put(payload);
                tx.oncomplete = () => {
                    db.close();
                    resolve();
                };
                tx.onerror = () => reject(tx.error);
            });
        };

        const makeClipId = () => `clip-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;

        const getSelectedClip = () => recordingClips.find((clip) => clip.id === selectedRecordingId) || null;

        const syncButtonState = ({ isRecording = false, isPaused = false } = {}) => {
            startButton.classList.toggle('hidden', isRecording);
            pauseButton.classList.toggle('hidden', !isRecording || isPaused);
            resumeButton.classList.toggle('hidden', !isRecording || !isPaused);
            stopButton.classList.toggle('hidden', !isRecording);
        };

        const saveTentativeDraft = async (mode = 'auto') => {
            if (!window.indexedDB) {
                setSaveStatus('Draft storage unavailable in this browser', 'red');
                return;
            }

            try {
                setSaveStatus(mode === 'manual' ? 'Saving tentative record...' : 'Autosaving tentative record...', 'blue');

                await putDraft({
                    id: draftKey,
                    notesHtml: editor?.innerHTML || '',
                    scriptText: scriptEditor?.value || '',
                    recordingClips: recordingClips.map((clip) => ({
                        id: clip.id,
                        filename: clip.filename,
                        blob: clip.blob instanceof Blob ? clip.blob : null,
                        saved: Boolean(clip.saved),
                        url: clip.saved ? clip.url : null,
                        downloadUrl: clip.saved ? clip.downloadUrl : null,
                    })),
                    selectedRecordingId,
                    finalAudioBlob,
                    finalAudioFilename: finalAudioFilenameValue,
                    savedAt: new Date().toISOString(),
                });

                setSaveStatus(`Tentative record saved ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`, 'emerald');
            } catch (error) {
                setSaveStatus('Could not save tentative record', 'red');
            }
        };

        const queueDraftSave = () => {
            window.clearTimeout(saveTimeout);
            setSaveStatus('Changes queued for autosave...', 'blue');
            saveTimeout = window.setTimeout(() => saveTentativeDraft('auto'), 800);
        };

        const resetError = () => {
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
        };

        const showError = (message) => {
            errorBox.textContent = message;
            errorBox.classList.remove('hidden');
        };

        const formatTime = (seconds) => {
            const mins = Math.floor(seconds / 60).toString().padStart(2, '0');
            const secs = Math.floor(seconds % 60).toString().padStart(2, '0');
            return `${mins}:${secs}`;
        };

        const updateTimer = () => {
            if (!startedAt) {
                timer.textContent = '00:00';
                return;
            }

            timer.textContent = formatTime(Math.floor((Date.now() - startedAt) / 1000));
        };

        const stopTracks = () => {
            if (!mediaStream) return;
            mediaStream.getTracks().forEach((track) => track.stop());
            mediaStream = null;
        };

        const setIdleState = () => {
            syncButtonState();
            status.textContent = recordingClips.length ? 'Ready for the next recording take' : 'Ready to record audio';
            dot.className = 'h-3 w-3 rounded-full bg-gray-500';
        };

        const setRecordingState = () => {
            syncButtonState({ isRecording: true });
            status.textContent = 'Recording from this device microphone';
            dot.className = 'h-3 w-3 rounded-full bg-red-500 animate-pulse';
        };

        const setPausedState = () => {
            syncButtonState({ isRecording: true, isPaused: true });
            status.textContent = 'Recording paused';
            dot.className = 'h-3 w-3 rounded-full bg-amber-400';
        };

        const syncFinalMediaEmpty = () => {
            const hasAudio = !finalAudioPlayer.classList.contains('hidden');
            const hasVideo = !finalVideoPlayer.classList.contains('hidden');
            const hasScript = !finalScriptFileRow.classList.contains('hidden');
            finalMediaEmpty.classList.toggle('hidden', hasAudio || hasVideo || hasScript);
        };

        const syncFinalAudio = (url, filename, downloadUrl = null) => {
            if (!url) {
                finalAudioPlayer.classList.add('hidden');
                finalAudioPlayer.removeAttribute('src');
                finalAudioPlayer.load();
                finalAudioEmpty.classList.remove('hidden');
                finalAudioBadge.classList.add('hidden');
                finalAudioMeta.classList.add('hidden');
                finalAudioFilename.textContent = '';
                finalAudioDownload.href = '#';
                finalAudioDownload.download = 'minutes-recording.webm';
                syncFinalMediaEmpty();
                return;
            }

            finalAudioPlayer.src = url;
            finalAudioPlayer.classList.remove('hidden');
            finalAudioPlayer.load();
            finalAudioEmpty.classList.add('hidden');
            finalAudioBadge.classList.remove('hidden');
            finalAudioMeta.classList.remove('hidden');
            finalAudioFilename.textContent = filename || 'minutes-recording.webm';
            finalAudioDownload.href = downloadUrl || url;
            finalAudioDownload.download = filename || 'minutes-recording.webm';
            syncFinalMediaEmpty();
        };

        const syncPreviewAudio = (url, filename, downloadUrl = null) => {
            currentAudioFilename = filename || 'minutes-recording.webm';

            if (!url) {
                previewPlayer.pause();
                previewPlayer.classList.add('hidden');
                previewPlayer.removeAttribute('src');
                previewPlayer.load();
                previewMeta.classList.add('hidden');
                previewFilename.textContent = '';
                previewEmpty.classList.remove('hidden');
                currentAudioBlob = null;
                return;
            }

            previewPlayer.src = url;
            previewPlayer.classList.remove('hidden');
            previewPlayer.load();
            previewMeta.classList.remove('hidden');
            previewFilename.textContent = currentAudioFilename;
            previewEmpty.classList.add('hidden');
        };

        const syncPreviewPlayer = (clip) => {
            if (!clip) {
                syncPreviewAudio(null, currentAudioFilename);
                return;
            }

            currentAudioBlob = clip.blob instanceof Blob ? clip.blob : null;
            currentAudioFilename = clip.filename || currentAudioFilename;
            syncPreviewAudio(clip.url, clip.filename, clip.downloadUrl || clip.url);
        };

        const renderRecordingsList = () => {
            recordingsList.innerHTML = '';

            if (!recordingClips.length) {
                recordingsListEmpty.classList.remove('hidden');
                recordingsListStatus.textContent = 'No recordings yet';
                recordingsListStatus.className = 'inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600';
                syncPreviewPlayer(null);
                return;
            }

            recordingsListEmpty.classList.add('hidden');
            recordingsListStatus.textContent = `${recordingClips.length} recording${recordingClips.length === 1 ? '' : 's'} ready`;
            recordingsListStatus.className = 'inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700';

            recordingClips.forEach((clip) => {
                const selected = clip.id === selectedRecordingId;
                const row = document.createElement('div');
                row.className = selected
                    ? 'rounded-xl border border-emerald-300 bg-emerald-50 p-3'
                    : 'rounded-xl border border-slate-200 bg-slate-50 p-3';

                row.innerHTML = `
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">${clip.filename || 'Recording clip'}</div>
                            <div class="mt-1 text-xs text-slate-500">${clip.saved ? 'Saved on server' : 'Stored locally until you save the selected recording'}</div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" data-recording-action="select" data-recording-id="${clip.id}" class="${selected ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-100'} rounded-lg px-3 py-2 text-xs font-semibold">Select</button>
                            ${clip.downloadUrl ? `<a href="${clip.downloadUrl}" class="rounded-lg bg-white px-3 py-2 text-xs font-semibold text-slate-700 border border-slate-300 hover:bg-slate-100">Download</a>` : ''}
                            <button type="button" data-recording-action="remove" data-recording-id="${clip.id}" class="rounded-lg border border-red-300 bg-white px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Remove</button>
                        </div>
                    </div>
                `;

                recordingsList.appendChild(row);
            });

            const selectedClip = getSelectedClip() || recordingClips[0];
            if (selectedClip && selectedClip.id !== selectedRecordingId) {
                selectedRecordingId = selectedClip.id;
            }
            syncPreviewPlayer(selectedClip);
        };

        const setSelectedClip = (id) => {
            selectedRecordingId = id;
            renderRecordingsList();
            queueDraftSave();
        };

        const addClip = (blobOrFile, filename, saved = false, url = null, downloadUrl = null, id = null) => {
            const file = fileFromBlob(blobOrFile, filename, 'audio/webm');
            const clipId = id || makeClipId();
            const clipUrl = url || (file ? URL.createObjectURL(file) : null);

            recordingClips = [
                ...recordingClips,
                {
                    id: clipId,
                    filename: filename || file?.name || `minutes-${recordingClips.length + 1}.webm`,
                    blob: saved ? null : file,
                    saved,
                    url: clipUrl,
                    downloadUrl: downloadUrl || clipUrl,
                },
            ];

            selectedRecordingId = clipId;
            renderRecordingsList();
        };

        const replaceSavedClips = (clips, preferredId = null, preferredFilename = null) => {
            recordingClips = (clips || []).map((clip, index) => ({
                id: clip.id || clip.url || `saved-clip-${index}`,
                filename: clip.filename || `recording-${index + 1}.webm`,
                blob: null,
                saved: true,
                url: clip.url,
                downloadUrl: clip.download_url || clip.downloadUrl || clip.url,
            }));

            const preferredClip = recordingClips.find((clip) => clip.id === preferredId)
                || recordingClips.find((clip) => preferredFilename && clip.filename === preferredFilename)
                || recordingClips[0]
                || null;

            selectedRecordingId = preferredClip?.id || null;
            renderRecordingsList();
        };

        const getRetainedSavedClipIds = () => recordingClips
            .filter((clip) => clip.saved && clip.id)
            .map((clip) => clip.id);

        const removeClipLocally = (clipId) => {
            const clip = recordingClips.find((item) => item.id === clipId) || null;

            if (!clip) {
                return null;
            }

            if (!clip.saved) {
                cleanupObjectUrl(clip.url);
                if (clip.downloadUrl && clip.downloadUrl !== clip.url) {
                    cleanupObjectUrl(clip.downloadUrl);
                }
            }

            recordingClips = recordingClips.filter((item) => item.id !== clipId);

            if (selectedRecordingId === clipId) {
                selectedRecordingId = recordingClips[0]?.id || null;
            }

            renderRecordingsList();

            return clip;
        };

        const resolveClipFile = async (clip) => {
            if (!clip) {
                return null;
            }

            if (clip.blob instanceof Blob) {
                return fileFromBlob(clip.blob, clip.filename, 'audio/webm');
            }

            if (!clip.url) {
                return null;
            }

            const response = await fetch(clip.url);
            if (!response.ok) {
                throw new Error('clip-fetch-failed');
            }

            const blob = await response.blob();
            return fileFromBlob(blob, clip.filename, blob.type || 'audio/webm');
        };

        const applyWorkspaceResponse = (payload) => {
            const selectedClip = getSelectedClip();
            replaceSavedClips(
                payload.recording_clips || [],
                selectedClip?.id || selectedRecordingId,
                selectedClip?.filename || currentAudioFilename
            );

            if (payload.tentative_audio_url) {
                syncPreviewAudio(
                    payload.tentative_audio_url,
                    payload.tentative_audio_filename || currentAudioFilename,
                    payload.tentative_audio_download_url || payload.tentative_audio_url
                );
            } else if (getSelectedClip()) {
                syncPreviewPlayer(getSelectedClip());
            } else {
                syncPreviewAudio(null, currentAudioFilename);
            }

            if (payload.meeting_video_url) {
                syncPreviewVideo(
                    payload.meeting_video_url,
                    payload.meeting_video_filename || 'meeting-video',
                    payload.meeting_video_download_url || payload.meeting_video_url
                );
                cleanupObjectUrl(uploadedVideoUrl);
                uploadedVideoUrl = null;
                currentVideoFile = null;
                currentVideoFilename = '';
            } else {
                syncPreviewVideo(null, '');
                cleanupObjectUrl(uploadedVideoUrl);
                uploadedVideoUrl = null;
                currentVideoFile = null;
                currentVideoFilename = '';
            }

            if (payload.script_file_url) {
                syncScriptFile(
                    payload.script_file_url,
                    payload.script_file_filename || 'script-file',
                    payload.script_file_download_url || payload.script_file_url
                );
                cleanupObjectUrl(uploadedScriptUrl);
                uploadedScriptUrl = null;
                currentScriptFile = null;
                currentScriptFilename = '';
            } else {
                syncScriptFile(null, '');
                cleanupObjectUrl(uploadedScriptUrl);
                uploadedScriptUrl = null;
                currentScriptFile = null;
                currentScriptFilename = '';
            }

            if (payload.final_audio_url) {
                syncFinalAudio(
                    payload.final_audio_url,
                    payload.final_audio_filename || finalAudioFilenameValue,
                    payload.final_audio_download_url || payload.final_audio_url
                );
            } else {
                syncFinalAudio(null, finalAudioFilenameValue);
            }

            if (payload.recording_notes !== undefined && editor) {
                updateMinutesEditors(payload.recording_notes || '', 'server');
            }

            if (payload.script_text !== undefined && scriptEditor) {
                scriptEditor.value = payload.script_text || '';
                syncFinalScript();
            }

            if (payload.template_preview_url !== undefined) {
                syncTemplatePdfPreview(payload.template_preview_url, payload.template_preview_download_url || payload.template_preview_url);
            }
        };

        const persistWorkspaceFiles = async ({
            saveTentativeAudio = false,
            removeTentativeAudio = false,
            uploadUnsavedClips = false,
            syncRecordingClips = false,
            saveVideo = false,
            removeVideo = false,
            saveScript = false,
            removeScript = false,
            includeNotes = true,
            includeScriptText = true,
        } = {}) => {
            const formData = new FormData();
            const selectedClip = getSelectedClip();

            if (saveTentativeAudio) {
                const tentativeAudioFile = await resolveClipFile(selectedClip);
                if (tentativeAudioFile) {
                    formData.append('tentative_audio', tentativeAudioFile);
                }
            } else if (removeTentativeAudio) {
                formData.append('remove_tentative_audio', '1');
            }

            if (syncRecordingClips) {
                formData.append('sync_recording_clips', '1');
                getRetainedSavedClipIds().forEach((clipId) => {
                    formData.append('retained_recording_clips[]', clipId);
                });
            }

            if (uploadUnsavedClips) {
                recordingClips
                    .filter((clip) => !clip.saved)
                    .forEach((clip) => {
                        const clipFile = fileFromBlob(clip.blob, clip.filename, 'audio/webm');
                        if (clipFile) {
                            formData.append('recording_clips[]', clipFile);
                        }
                    });
            }

            if (saveVideo) {
                const meetingVideoFile = await resolveStandaloneFile(
                    currentVideoFile,
                    uploadedVideoUrl || videoPlayer.getAttribute('src'),
                    currentVideoFilename || videoFilename.textContent || 'meeting-video',
                    'video/mp4'
                );

                if (meetingVideoFile) {
                    formData.append('meeting_video', meetingVideoFile);
                }
            } else if (removeVideo) {
                formData.append('remove_meeting_video', '1');
            }

            if (saveScript) {
                const scriptFileToSave = await resolveStandaloneFile(
                    currentScriptFile,
                    uploadedScriptUrl || scriptDownload.getAttribute('href'),
                    currentScriptFilename || scriptFilename.textContent || 'script-file',
                    'application/pdf'
                );

                if (scriptFileToSave) {
                    formData.append('script_file', scriptFileToSave);
                }
            } else if (removeScript) {
                formData.append('remove_script_file', '1');
            }

            if (includeNotes) {
                formData.append('recording_notes', editor?.innerHTML || '');
            }

            if (includeScriptText) {
                formData.append('script_text', scriptEditor?.value || '');
            }

            const response = await fetch(workspaceSaveUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            if (!response.ok) {
                throw new Error(await extractErrorMessage(response, 'Workspace save failed.'));
            }

            return response.json();
        };

        const persistFinalAudio = async ({ remove = false } = {}) => {
            const formData = new FormData();

            if (remove) {
                formData.append('remove_final_audio', '1');
            } else {
                const selectedClip = getSelectedClip();
                const finalAudioFile = await resolveClipFile(selectedClip);

                if (finalAudioFile) {
                    formData.append('final_audio', finalAudioFile);
                }
            }

            const response = await fetch(finalAudioSaveUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            if (!response.ok) {
                throw new Error(await extractErrorMessage(response, 'Final audio save failed.'));
            }

            return response.json();
        };

        const persistFinalPreview = async () => {
            const formData = new FormData();
            const selectedClip = getSelectedClip();
            const tentativeAudioFile = await resolveClipFile(selectedClip);
            const finalAudioFile = await resolveClipFile(selectedClip);

            if (tentativeAudioFile) {
                formData.append('tentative_audio', tentativeAudioFile);
            }

            if (finalAudioFile) {
                formData.append('final_audio', finalAudioFile);
            }

            formData.append('sync_recording_clips', '1');
            getRetainedSavedClipIds().forEach((clipId) => {
                formData.append('retained_recording_clips[]', clipId);
            });

            recordingClips
                .filter((clip) => !clip.saved)
                .forEach((clip) => {
                    const clipFile = fileFromBlob(clip.blob, clip.filename, 'audio/webm');
                    if (clipFile) {
                        formData.append('recording_clips[]', clipFile);
                    }
                });

            const finalPreviewVideoFile = await resolveStandaloneFile(
                currentVideoFile,
                uploadedVideoUrl || videoPlayer.getAttribute('src'),
                currentVideoFilename || videoFilename.textContent || 'meeting-video',
                'video/mp4'
            );

            if (finalPreviewVideoFile) {
                formData.append('meeting_video', finalPreviewVideoFile);
            }

            const finalPreviewScriptFile = await resolveStandaloneFile(
                currentScriptFile,
                uploadedScriptUrl || scriptDownload.getAttribute('href'),
                currentScriptFilename || scriptFilename.textContent || 'script-file',
                'application/pdf'
            );

            if (finalPreviewScriptFile) {
                formData.append('script_file', finalPreviewScriptFile);
            }

            formData.append('recording_notes', editor?.innerHTML || '');
            formData.append('script_text', scriptEditor?.value || '');

            const response = await fetch(finalSaveUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            if (!response.ok) {
                throw new Error(await extractErrorMessage(response, 'Final preview save failed.'));
            }

            return response.json();
        };

        const restoreTentativeDraft = async () => {
            if (!window.indexedDB) {
                return;
            }

            try {
                const draft = await getDraft();
                if (!draft) {
                    setSaveStatus('Draft not saved yet', 'slate');
                    return;
                }

                if (editor && draft.notesHtml) {
                    updateMinutesEditors(draft.notesHtml, 'server');
                }

                if (scriptEditor && draft.scriptText) {
                    scriptEditor.value = draft.scriptText;
                    syncFinalScript();
                }

                if (Array.isArray(draft.recordingClips) && draft.recordingClips.length) {
                    const restoredClips = draft.recordingClips.map((clip, index) => ({
                        id: clip.id || `draft-clip-${index}`,
                        filename: clip.filename || `recording-${index + 1}.webm`,
                        blob: clip.blob instanceof Blob ? fileFromBlob(clip.blob, clip.filename || `recording-${index + 1}.webm`, 'audio/webm') : null,
                        saved: Boolean(clip.saved && clip.url),
                        url: clip.saved ? clip.url : (clip.blob instanceof Blob ? URL.createObjectURL(clip.blob) : null),
                        downloadUrl: clip.saved ? (clip.downloadUrl || clip.url) : (clip.blob instanceof Blob ? URL.createObjectURL(clip.blob) : null),
                    }));

                    recordingClips = restoredClips.filter((clip) => clip.url);
                    selectedRecordingId = draft.selectedRecordingId || recordingClips[0]?.id || null;
                    renderRecordingsList();
                    status.textContent = 'Tentative recordings restored from saved draft';
                    dot.className = 'h-3 w-3 rounded-full bg-emerald-500';
                }

                if (draft.finalAudioBlob instanceof Blob && draft.finalAudioBlob.size > 0) {
                    finalAudioBlob = draft.finalAudioBlob;
                    finalAudioFilenameValue = draft.finalAudioFilename || finalAudioFilenameValue;
                    cleanupObjectUrl(finalAudioUrl);
                    finalAudioUrl = URL.createObjectURL(draft.finalAudioBlob);
                    syncFinalAudio(finalAudioUrl, finalAudioFilenameValue);
                }

                const restoredAt = draft.savedAt
                    ? new Date(draft.savedAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                    : 'recently';
                setSaveStatus(`Tentative record restored ${restoredAt}`, 'amber');
            } catch (error) {
                setSaveStatus('Could not restore tentative record', 'red');
            }
        };

        const syncPreviewVideo = (url, filename, downloadUrl = null) => {
            if (!url) {
                videoPlayer.classList.add('hidden');
                videoPlayer.removeAttribute('src');
                videoPlayer.load();
                videoEmpty.classList.remove('hidden');
                videoMeta.classList.add('hidden');
                videoFilename.textContent = '';
                videoDownload.classList.add('hidden');
                videoDownload.href = '#';

                finalVideoPlayer.classList.add('hidden');
                finalVideoPlayer.removeAttribute('src');
                finalVideoPlayer.load();
                finalVideoEmpty.classList.remove('hidden');
                finalVideoBadge.classList.add('hidden');
                syncFinalMediaEmpty();
                return;
            }

            videoPlayer.src = url;
            videoPlayer.classList.remove('hidden');
            videoPlayer.load();
            videoEmpty.classList.add('hidden');
            videoMeta.classList.remove('hidden');
            videoFilename.textContent = filename;
            videoDownload.href = downloadUrl || url;
            videoDownload.download = filename;
            videoDownload.classList.remove('hidden');

            finalVideoPlayer.src = url;
            finalVideoPlayer.classList.remove('hidden');
            finalVideoPlayer.load();
            finalVideoEmpty.classList.add('hidden');
            finalVideoBadge.classList.remove('hidden');
            syncFinalMediaEmpty();
        };

        const syncScriptFile = (url, filename, downloadUrl = null) => {
            if (!url) {
                scriptFile.classList.add('hidden');
                scriptEmpty.classList.remove('hidden');
                scriptFilename.textContent = '';
                scriptDownload.href = '#';
                finalScriptFileRow.classList.add('hidden');
                finalScriptFileLabel.textContent = 'Attached Script';
                finalScriptFileDownload.href = '#';
                syncFinalMediaEmpty();
                return;
            }

            scriptFile.classList.remove('hidden');
            scriptEmpty.classList.add('hidden');
            scriptFilename.textContent = filename;
            scriptDownload.href = downloadUrl || url;
            scriptDownload.download = filename;
            finalScriptFileRow.classList.remove('hidden');
            finalScriptFileLabel.textContent = filename;
            finalScriptFileDownload.href = downloadUrl || url;
            finalScriptFileDownload.download = filename;
            syncFinalMediaEmpty();
        };

        uploadInput.addEventListener('change', (event) => {
            const file = event.target.files?.[0];
            if (!file) {
                return;
            }

            addClip(file, file.name);
            status.textContent = 'Uploaded audio added to the recordings list';
            dot.className = 'h-3 w-3 rounded-full bg-emerald-500';
            uploadInput.value = '';
            queueDraftSave();
        });

        videoInput.addEventListener('change', (event) => {
            const file = event.target.files?.[0];
            cleanupObjectUrl(uploadedVideoUrl);
            uploadedVideoUrl = null;
            currentVideoFile = null;

            if (!file) {
                syncPreviewVideo(null, '');
                return;
            }

            currentVideoFile = file;
            currentVideoFilename = file.name;
            uploadedVideoUrl = URL.createObjectURL(file);
            syncPreviewVideo(uploadedVideoUrl, file.name);
            queueDraftSave();
        });

        scriptUpload.addEventListener('change', (event) => {
            const file = event.target.files?.[0];
            cleanupObjectUrl(uploadedScriptUrl);
            uploadedScriptUrl = null;
            currentScriptFile = null;

            if (!file) {
                syncScriptFile(null, '');
                return;
            }

            currentScriptFile = file;
            currentScriptFilename = file.name;
            uploadedScriptUrl = URL.createObjectURL(file);
            syncScriptFile(uploadedScriptUrl, file.name);
            queueDraftSave();
        });

        const startRecording = async () => {
            resetError();

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || typeof MediaRecorder === 'undefined') {
                showError('This browser does not support microphone recording.');
                return;
            }

            try {
                chunks = [];

                mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(mediaStream);

                mediaRecorder.addEventListener('dataavailable', (event) => {
                    if (event.data && event.data.size > 0) chunks.push(event.data);
                });

                mediaRecorder.addEventListener('stop', () => {
                    const freshBlob = new Blob(chunks, { type: mediaRecorder.mimeType || 'audio/webm' });
                    const filename = `minutes-${new Date().toISOString().replace(/[:.]/g, '-')}.webm`;
                    addClip(freshBlob, filename);
                    status.textContent = 'Recording captured and added to the recordings list';
                    dot.className = 'h-3 w-3 rounded-full bg-emerald-500';
                    stopTracks();
                    mediaRecorder = null;
                    startedAt = null;
                    timer.textContent = '00:00';
                    setIdleState();
                    queueDraftSave();
                });

                mediaRecorder.start();
                startedAt = Date.now();
                updateTimer();
                timerHandle = window.setInterval(updateTimer, 1000);
                setRecordingState();
            } catch (error) {
                stopTracks();
                setIdleState();
                showError('Microphone access was blocked or unavailable.');
            }
        };

        const stopRecording = () => {
            if (!mediaRecorder) return;
            if (timerHandle) {
                window.clearInterval(timerHandle);
                timerHandle = null;
            }

            if (mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
            } else {
                stopTracks();
                mediaRecorder = null;
                startedAt = null;
                timer.textContent = '00:00';
                setIdleState();
            }
        };

        startButton.addEventListener('click', startRecording);
        pauseButton.addEventListener('click', () => {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.pause();
                setPausedState();
            }
        });
        resumeButton.addEventListener('click', () => {
            if (mediaRecorder && mediaRecorder.state === 'paused') {
                mediaRecorder.resume();
                setRecordingState();
            }
        });
        stopButton.addEventListener('click', stopRecording);

        recordingsList.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-recording-action]');
            if (!button) return;

            const clip = recordingClips.find((item) => item.id === button.dataset.recordingId);
            if (!clip) return;

            if (button.dataset.recordingAction === 'select') {
                setSelectedClip(clip.id);
                return;
            }

            if (button.dataset.recordingAction === 'remove') {
                const removedClip = removeClipLocally(clip.id);
                queueDraftSave();

                if (!removedClip?.saved) {
                    setSaveStatus('Recording removed from the local list', 'emerald');
                    return;
                }

                try {
                    setSaveStatus('Removing recording from the minutes workspace...', 'blue');
                    const payload = await persistWorkspaceFiles({
                        saveTentativeAudio: Boolean(getSelectedClip()),
                        removeTentativeAudio: !getSelectedClip(),
                        uploadUnsavedClips: true,
                        syncRecordingClips: true,
                    });
                    applyWorkspaceResponse(payload);
                    await saveTentativeDraft('manual');
                    setSaveStatus('Recording removed successfully', 'emerald');
                } catch (error) {
                    setSaveStatus('Could not remove that recording from the workspace', 'red');
                }
            }
        });

        if (editor) {
            editor.addEventListener('input', queueDraftSave);
        }

        if (templateEditor) {
            templateEditor.addEventListener('input', queueDraftSave);
        }

        if (bodySaveButton) {
            bodySaveButton.addEventListener('click', async () => {
                try {
                    syncFinalNotes(activeMinutesEditor === templateEditor ? 'template' : 'builder');
                    setSaveStatus('Saving minutes body to server...', 'blue');
                    const payload = await persistWorkspaceFiles({ includeNotes: true, includeScriptText: true });
                    applyWorkspaceResponse(payload);
                    await saveTentativeDraft('manual');
                    setSaveStatus('Minutes body saved. Download PDF is now updated.', 'emerald');
                } catch (error) {
                    setSaveStatus(error?.message || 'Could not save minutes body', 'red');
                }
            });
        }

        if (scriptEditor) {
            scriptEditor.addEventListener('input', queueDraftSave);
        }

        compileAudioButton.addEventListener('click', async () => {
            const selectedClip = getSelectedClip();
            if (!selectedClip) {
                setSaveStatus('Select a recording from the list first', 'amber');
                return;
            }

            try {
                setSaveStatus('Saving the selected recording before compile...', 'blue');
                const workspacePayload = await persistWorkspaceFiles({
                    saveTentativeAudio: true,
                    uploadUnsavedClips: true,
                    syncRecordingClips: true,
                });
                applyWorkspaceResponse(workspacePayload);

                setSaveStatus('Compiling the selected recording into the final preview...', 'blue');
                const payload = await persistFinalAudio();
                finalAudioBlob = selectedClip.blob instanceof Blob ? selectedClip.blob : null;
                finalAudioFilenameValue = payload.final_audio_filename || selectedClip.filename;
                applyWorkspaceResponse(payload);
                await saveTentativeDraft('manual');
                setSaveStatus('Recording compiled into the final preview', 'emerald');
            } catch (error) {
                setSaveStatus(error?.message || 'Could not compile the selected recording', 'red');
            }
        });

        saveVideoButton.addEventListener('click', async () => {
            if (!(currentVideoFile instanceof File) && !uploadedVideoUrl && !videoPlayer.getAttribute('src')) {
                setSaveStatus('Choose a meeting video first', 'amber');
                return;
            }

            try {
                setSaveStatus('Saving the meeting video...', 'blue');
                const payload = await persistWorkspaceFiles({ saveVideo: true });
                applyWorkspaceResponse(payload);
                await saveTentativeDraft('manual');
                setSaveStatus('Meeting video saved successfully', 'emerald');
            } catch (error) {
                setSaveStatus(error?.message || 'Could not save the meeting video', 'red');
            }
        });

        removeVideoButton.addEventListener('click', async () => {
            cleanupObjectUrl(uploadedVideoUrl);
            uploadedVideoUrl = null;
            currentVideoFile = null;
            videoInput.value = '';
            syncPreviewVideo(null, '');

            try {
                setSaveStatus('Removing the meeting video...', 'blue');
                const payload = await persistWorkspaceFiles({ removeVideo: true });
                applyWorkspaceResponse(payload);
                await saveTentativeDraft('manual');
                setSaveStatus('Meeting video removed', 'emerald');
            } catch (error) {
                setSaveStatus(error?.message || 'Could not remove the meeting video', 'red');
            }
        });

        saveScriptButton.addEventListener('click', async () => {
            if (!(currentScriptFile instanceof File) && scriptDownload.getAttribute('href') === '#') {
                setSaveStatus('Choose a script file first', 'amber');
                return;
            }

            try {
                setSaveStatus('Saving the script file...', 'blue');
                const payload = await persistWorkspaceFiles({ saveScript: true });
                applyWorkspaceResponse(payload);
                await saveTentativeDraft('manual');
                setSaveStatus('Script file saved successfully', 'emerald');
            } catch (error) {
                setSaveStatus(error?.message || 'Could not save the script file', 'red');
            }
        });

        removeScriptButton.addEventListener('click', async () => {
            cleanupObjectUrl(uploadedScriptUrl);
            uploadedScriptUrl = null;
            currentScriptFile = null;
            scriptUpload.value = '';
            syncScriptFile(null, '');

            try {
                setSaveStatus('Removing the script file...', 'blue');
                const payload = await persistWorkspaceFiles({ removeScript: true });
                applyWorkspaceResponse(payload);
                await saveTentativeDraft('manual');
                setSaveStatus('Script file removed', 'emerald');
            } catch (error) {
                setSaveStatus(error?.message || 'Could not remove the script file', 'red');
            }
        });

        finalSaveButton.addEventListener('click', async () => {
            try {
                setSaveStatus('Saving the full final preview...', 'blue');
                const payload = await persistFinalPreview();
                const selectedClip = getSelectedClip();
                finalAudioBlob = selectedClip?.blob instanceof Blob ? selectedClip.blob : null;
                finalAudioFilenameValue = payload.final_audio_filename || selectedClip?.filename || finalAudioFilenameValue;
                applyWorkspaceResponse(payload);
                await saveTentativeDraft('manual');
                setSaveStatus('Final preview saved successfully', 'emerald');
            } catch (error) {
                setSaveStatus(error?.message || 'Could not save the final preview', 'red');
            }
        });

        window.addEventListener('beforeunload', () => {
            window.clearTimeout(saveTimeout);
            if (recordingClips.length || editor?.textContent?.trim() || scriptEditor?.value?.trim()) {
                saveTentativeDraft('auto');
            }
            if (timerHandle) window.clearInterval(timerHandle);
            cleanupObjectUrl(finalAudioUrl);
            cleanupObjectUrl(uploadedVideoUrl);
            cleanupObjectUrl(uploadedScriptUrl);
            stopTracks();
        });

        replaceSavedClips(@js($recordingClipOptions));
        if (!recordingClips.length && @js($tentativeAudioUrl)) {
            addClip(
                null,
                @js($minute->tentative_audio_path ? basename($minute->tentative_audio_path) : 'tentative-recording.webm'),
                true,
                @js($tentativeAudioUrl),
                @js($tentativeAudioDownloadUrl),
                'tentative-audio'
            );
        }
        syncFinalAudio(null, finalAudioFilenameValue);
        syncPreviewVideo(null, '');
        syncScriptFile(null, '');
        setIdleState();
        if (@js($meetingVideoUrl)) {
            syncPreviewVideo(
                @js($meetingVideoUrl),
                @js($minute->meeting_video_path ? basename($minute->meeting_video_path) : null),
                @js($meetingVideoDownloadUrl)
            );
        }
        if (@js($scriptFileUrl)) {
            syncScriptFile(
                @js($scriptFileUrl),
                @js($minute->script_file_path ? basename($minute->script_file_path) : null),
                @js($scriptFileDownloadUrl)
            );
        }
        if (@js($finalAudioUrl)) {
            syncFinalAudio(
                @js($finalAudioUrl),
                @js($minute->final_audio_path ? basename($minute->final_audio_path) : null),
                @js($finalAudioDownloadUrl)
            );
        }
        restoreTentativeDraft();
    })();
</script>
@endsection

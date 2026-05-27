@extends('layouts.app')
@section('title', 'Minutes')

@section('content')
@php
    $today = now()->toDateString();
    $currentUser = auth()->user()?->name ?? '';
    $canApproveMinutes = auth()->user()?->role === 'Admin';
    $sectionRibbonPartial = $sectionRibbonPartial ?? 'corporate.partials.section-ribbon';
    $documentDefaultsUrl = $documentDefaultsUrl ?? route('corporate-document-defaults');
    $minutesStoreUrl = $minutesStoreUrl ?? route('minutes.store');
    $noticeOptions = ($notices ?? collect())->map(fn ($notice) => [
        'id' => $notice->id,
        'notice_number' => $notice->notice_number,
        'governing_body' => $notice->governing_body,
        'type_of_meeting' => $notice->type_of_meeting,
        'meeting_no' => $notice->meeting_no,
        'date_of_meeting' => optional($notice->date_of_meeting)->toDateString(),
        'time_started' => $notice->time_started,
        'location' => $notice->location,
        'chairman' => $notice->chairman,
        'secretary' => $notice->secretary,
    ])->values();
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
    .minutes-slider-editor[contenteditable="true"][data-placeholder]:empty::before {
        content: attr(data-placeholder);
        color: #94a3b8;
        pointer-events: none;
    }
</style>

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4" x-data="minutesForm({{ Js::from($noticeOptions) }}, @js($today), @js($currentUser), @js($documentDefaultsUrl), @js($nextMinutesRef ?? ''))" @keydown.escape.window="showAddPanel = false">
    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-100">
            <div class="text-lg font-semibold">Minutes of Meeting</div>
            <div class="flex-1"></div>
            <button
                type="button"
                @click="openAddPanel()"
                :disabled="!hasNotices"
                :class="hasNotices ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                class="h-9 px-4 rounded-full text-sm font-medium flex items-center gap-2 transition"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Add Minutes
            </button>
        </div>

        <div x-show="!hasNotices" x-cloak class="mx-4 mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Add a Notice of Meeting first before creating Minutes of Meeting. Minutes now depend on an existing notice and will inherit its core meeting details.
        </div>

        <div class="p-4">
            <div class="overflow-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Minutes Ref</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Linked Notice</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Meeting</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Schedule</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Mode</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Approval</th>
                            @if ($canApproveMinutes)
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="text-sm text-gray-900">
                        @forelse ($minutes as $minute)
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ $minute->preview_url ?? route('minutes.preview', $minute) }}'">
                                <td class="px-4 py-3 font-medium">{{ $minute->minutes_ref }}</td>
                                <td class="px-4 py-3">
                                    <div>{{ $minute->notice_ref }}</div>
                                    <div class="text-xs text-gray-500">{{ $minute->notice?->governing_body }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div>{{ $minute->type_of_meeting }}</div>
                                    <div class="text-xs text-gray-500">{{ $minute->location }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div>{{ optional($minute->date_of_meeting)->format('M d, Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $minute->time_started }} - {{ $minute->time_ended }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $minute->meeting_mode }}</td>
                                <td class="px-4 py-3">
                                    @if ($minute->approved_by)
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Approved</span>
                                        <div class="mt-1 text-xs text-gray-500">{{ $minute->approved_by }}</div>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Pending approval</span>
                                    @endif
                                </td>
                                @if ($canApproveMinutes)
                                    <td class="px-4 py-3" onclick="event.stopPropagation()">
                                        <form method="POST" action="{{ $minute->approve_url ?? route('minutes.approve', $minute) }}" enctype="multipart/form-data" class="space-y-2">
                                            @csrf
                                            <input type="file" name="approved_minutes_path" accept="application/pdf" class="block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-2.5 file:rounded-lg file:border-0 file:bg-emerald-600 file:text-white hover:file:bg-emerald-700" onclick="event.stopPropagation()">
                                            <button type="submit" class="inline-flex rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700" onclick="event.stopPropagation()">
                                                {{ $minute->approved_by ? 'Update Approval' : 'Approve Minutes' }}
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canApproveMinutes ? 7 : 6 }}" class="px-4 py-6 text-center text-sm text-gray-500">No minutes found.</td>
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
                    <div class="text-lg font-semibold">Add Minutes</div>
                    <div class="text-xs text-gray-500">Link the minutes to a notice so the core meeting details stay aligned.</div>
                </div>
                <div class="flex-1"></div>
                <button class="text-gray-500 hover:text-gray-700" @click="showAddPanel = false" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="{{ $minutesStoreUrl }}" enctype="multipart/form-data" class="flex-1 overflow-y-auto p-6 space-y-6" @submit="prepareSubmit()">
                @csrf
                <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                    Select an existing Notice of Meeting first. The linked notice will suggest and auto-fill the core fields below so the minutes stay aligned with the notice record.
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-600">Minutes Ref</label>
                        <input type="text" name="minutes_ref" x-ref="minutesRef" value="{{ $nextMinutesRef ?? '' }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="MIN-2026-001">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Date Uploaded</label>
                        <input type="date" name="date_uploaded" value="{{ $today }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-600">Uploaded By</label>
                        <input type="text" name="uploaded_by" x-ref="uploadedBy" value="{{ $currentUser }}" data-default-field="current_user" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Linked Notice</label>
                        <select name="notice_id" x-model="selectedNoticeId" @change="applyNotice()" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            <option value="">Select a notice</option>
                            <template x-for="notice in notices" :key="notice.id">
                                <option :value="notice.id" x-text="`${notice.notice_number || 'Draft Notice'} • ${notice.type_of_meeting || ''}`"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Only notices already recorded in the system can be used for new minutes.</p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Governing Body</label>
                        <select name="governing_body" x-ref="governingBody" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                            <option value="Stockholders">Stockholders</option>
                            <option value="Board of Directors">Board of Directors</option>
                            <option value="Joint Stockholders and Board of Directors">Joint Stockholders and Board of Directors</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Suggested from the selected notice.</p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Type of Meeting</label>
                        <select name="type_of_meeting" x-ref="meetingType" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                            <option value="Regular">Regular</option>
                            <option value="Special">Special</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Suggested from the selected notice.</p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Meeting Mode</label>
                        <select name="meeting_mode" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            <option value="In-Person">In-Person</option>
                            <option value="Virtual">Virtual</option>
                            <option value="Hybrid">Hybrid</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Notice Ref #</label>
                        <input type="text" name="notice_ref" x-ref="noticeRef" readonly class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Meeting Date</label>
                        <input type="date" name="date_of_meeting" x-ref="meetingDate" value="{{ $today }}" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Time Started</label>
                        <input type="time" name="time_started" x-ref="timeStarted" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Time Ended</label>
                        <input type="time" name="time_ended" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Location</label>
                        <input type="text" name="location" x-ref="location" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Call Link</label>
                        <input type="text" name="call_link" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Meeting #</label>
                        <input type="text" name="meeting_no" x-ref="meetingNo" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Chairman</label>
                        <input type="text" name="chairman" x-ref="chairman" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Secretary</label>
                        <input type="text" name="secretary" x-ref="secretary" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Attendees</div>
                                <div class="mt-1 text-xs text-gray-500">Add each attendee using separate Name and Position/Role fields. You can add as many rows as needed.</div>
                            </div>
                        </div>

                        <input type="hidden" name="directors_present" x-ref="directorsPresentInput">
                        <input type="hidden" name="directors_absent" x-ref="directorsAbsentInput">
                        <input type="hidden" name="secretariat" x-ref="secretariatInput">
                        <input type="hidden" name="guests" x-ref="guestsInput">

                        <div class="mt-4 space-y-5">
                            <div class="rounded-xl border border-gray-200 bg-white p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-700">Directors Present</div>
                                        <div class="mt-1 text-xs text-gray-500">Example: John Kelly D. Abalde — President / Chief Executive Officer</div>
                                    </div>
                                    <button type="button" @click="addAttendee('directors_present')" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">+ Add</button>
                                </div>
                                <div class="mt-3 space-y-2">
                                    <template x-for="(person, index) in attendees.directors_present" :key="`directors-present-${index}`">
                                        <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] gap-2">
                                            <input type="text" x-model="person.name" class="rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Name">
                                            <input type="text" x-model="person.position" class="rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Position">
                                            <button type="button" @click="removeAttendee('directors_present', index)" class="rounded-md border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50" x-show="attendees.directors_present.length > 1">Remove</button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-white p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-700">Directors Absent</div>
                                        <div class="mt-1 text-xs text-gray-500">Leave blank if there are no absent directors.</div>
                                    </div>
                                    <button type="button" @click="addAttendee('directors_absent')" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">+ Add</button>
                                </div>
                                <div class="mt-3 space-y-2">
                                    <template x-for="(person, index) in attendees.directors_absent" :key="`directors-absent-${index}`">
                                        <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] gap-2">
                                            <input type="text" x-model="person.name" class="rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Name">
                                            <input type="text" x-model="person.position" class="rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Position">
                                            <button type="button" @click="removeAttendee('directors_absent', index)" class="rounded-md border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50" x-show="attendees.directors_absent.length > 1">Remove</button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-white p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-700">Secretariat</div>
                                        <div class="mt-1 text-xs text-gray-500">Example: Angelie Avenido — Secretariat / Minutes-Taker</div>
                                    </div>
                                    <button type="button" @click="addAttendee('secretariat')" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">+ Add</button>
                                </div>
                                <div class="mt-3 space-y-2">
                                    <template x-for="(person, index) in attendees.secretariat" :key="`secretariat-${index}`">
                                        <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] gap-2">
                                            <input type="text" x-model="person.name" class="rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Name">
                                            <input type="text" x-model="person.position" class="rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Role">
                                            <button type="button" @click="removeAttendee('secretariat', index)" class="rounded-md border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50" x-show="attendees.secretariat.length > 1">Remove</button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-white p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-700">Guests</div>
                                        <div class="mt-1 text-xs text-gray-500">Leave blank if there are no guests.</div>
                                    </div>
                                    <button type="button" @click="addAttendee('guests')" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">+ Add</button>
                                </div>
                                <div class="mt-3 space-y-2">
                                    <template x-for="(person, index) in attendees.guests" :key="`guests-${index}`">
                                        <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] gap-2">
                                            <input type="text" x-model="person.name" class="rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Name">
                                            <input type="text" x-model="person.position" class="rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Role">
                                            <button type="button" @click="removeAttendee('guests', index)" class="rounded-md border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50" x-show="attendees.guests.length > 1">Remove</button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs text-gray-600">Upload Minutes (PDF)</label>
                        <input type="file" name="document_path" accept="application/pdf" class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-600">Recording Notes</label>
                        <input type="hidden" name="recording_notes" x-ref="recordingNotesInput">
                        <div class="mt-1 overflow-hidden rounded-xl border border-gray-300 bg-white">
                            <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 px-3 py-3 bg-gray-50">
                                <select @change="applyMinutesFormat('fontName', $event.target.value)" class="rounded-lg border border-gray-300 px-2 py-1 text-xs">
                                    <option value="Arial">Arial</option>
                                    <option value="Times New Roman">Times New Roman</option>
                                    <option value="Georgia">Georgia</option>
                                    <option value="Verdana">Verdana</option>
                                    <option value="Courier New">Courier New</option>
                                </select>
                                <select @change="applyMinutesFormat('fontSize', $event.target.value)" class="rounded-lg border border-gray-300 px-2 py-1 text-xs">
                                    <option value="1">10</option>
                                    <option value="2">12</option>
                                    <option value="3" selected>14</option>
                                    <option value="4">16</option>
                                    <option value="5">18</option>
                                </select>
                                <button type="button" @click="applyMinutesFormat('bold')" class="px-2 py-1 border border-gray-300 rounded-lg text-xs">Bold</button>
                                <button type="button" @click="applyMinutesFormat('italic')" class="px-2 py-1 border border-gray-300 rounded-lg text-xs">Italic</button>
                                <button type="button" @click="applyMinutesFormat('underline')" class="px-2 py-1 border border-gray-300 rounded-lg text-xs">Underline</button>
                                <button type="button" @click="applyMinutesFormat('insertUnorderedList')" class="px-2 py-1 border border-gray-300 rounded-lg text-xs">Bullets</button>
                                <button type="button" @click="applyMinutesFormat('insertOrderedList')" class="px-2 py-1 border border-gray-300 rounded-lg text-xs">Numbering</button>
                                <button type="button" @click="applyMinutesFormat('justifyLeft')" class="px-2 py-1 border border-gray-300 rounded-lg text-xs">Left</button>
                                <button type="button" @click="applyMinutesFormat('justifyCenter')" class="px-2 py-1 border border-gray-300 rounded-lg text-xs">Center</button>
                                <button type="button" @click="applyMinutesFormat('justifyRight')" class="px-2 py-1 border border-gray-300 rounded-lg text-xs">Right</button>
                                <button type="button" @click="applyMinutesFormat('hiliteColor', 'yellow')" class="px-2 py-1 border border-gray-300 rounded-lg text-xs">Highlight</button>
                                <button type="button" @click="applyMinutesFormat('removeFormat')" class="px-2 py-1 border border-gray-300 rounded-lg text-xs">Clear</button>
                            </div>
                            <div x-ref="minutesEditor" @input="syncMinutesBody()" class="minutes-slider-editor min-h-[260px] px-4 py-3 text-sm leading-7 outline-none" contenteditable="true" data-placeholder="Type the minutes of meeting here..."></div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 flex items-center gap-2 -mx-6 -mb-6">
                    <button class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-900 text-sm font-medium rounded-lg" @click="showAddPanel = false" type="button">
                        Cancel
                    </button>
                    <div class="flex-1"></div>
                    <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg" type="submit">
                        Save Minutes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function minutesForm(notices, today, currentUser, defaultsEndpoint, initialMinutesRef) {
        return {
            showAddPanel: false,
            notices,
            today,
            currentUser,
            defaultsEndpoint,
            initialMinutesRef,
            selectedNoticeId: '',
            minutesBodyHtml: '',
            attendees: {
                directors_present: [{ name: '', position: '' }],
                directors_absent: [{ name: '', position: '' }],
                secretariat: [{ name: '', position: '' }],
                guests: [{ name: '', position: '' }],
            },
            get hasNotices() {
                return this.notices.length > 0;
            },
            openAddPanel() {
                if (!this.hasNotices) {
                    return;
                }

                this.showAddPanel = true;
                if (this.$refs.minutesRef) {
                    this.$refs.minutesRef.value = this.initialMinutesRef || this.$refs.minutesRef.value || '';
                }
                this.loadDefaults();
                if (this.$refs.uploadedBy) {
                    this.$refs.uploadedBy.value = this.currentUser || '';
                }

                if (!this.selectedNoticeId && this.notices.length) {
                    this.selectedNoticeId = String(this.notices[0].id);
                }

                this.$nextTick(() => this.applyNotice());
                this.$nextTick(() => {
                    this.minutesBodyHtml = '';
                    if (this.$refs.minutesEditor) {
                        this.$refs.minutesEditor.innerHTML = '';
                    }
                    if (this.$refs.recordingNotesInput) {
                        this.$refs.recordingNotesInput.value = '';
                    }
                    this.resetAttendees();
                    this.syncAttendees();
                });
            },
            emptyAttendeeRow() {
                return { name: '', position: '' };
            },
            resetAttendees() {
                this.attendees = {
                    directors_present: [this.emptyAttendeeRow()],
                    directors_absent: [this.emptyAttendeeRow()],
                    secretariat: [this.emptyAttendeeRow()],
                    guests: [this.emptyAttendeeRow()],
                };
            },
            addAttendee(group) {
                if (!this.attendees[group]) {
                    return;
                }

                this.attendees[group].push(this.emptyAttendeeRow());
                this.$nextTick(() => this.syncAttendees());
            },
            removeAttendee(group, index) {
                if (!this.attendees[group]) {
                    return;
                }

                this.attendees[group].splice(index, 1);

                if (!this.attendees[group].length) {
                    this.attendees[group].push(this.emptyAttendeeRow());
                }

                this.$nextTick(() => this.syncAttendees());
            },
            cleanAttendees(group) {
                return (this.attendees[group] || [])
                    .map((person) => ({
                        name: String(person.name || '').trim(),
                        position: String(person.position || '').trim(),
                    }))
                    .filter((person) => person.name !== '' || person.position !== '');
            },
            syncAttendees() {
                const mapping = {
                    directors_present: 'directorsPresentInput',
                    directors_absent: 'directorsAbsentInput',
                    secretariat: 'secretariatInput',
                    guests: 'guestsInput',
                };

                Object.entries(mapping).forEach(([group, refName]) => {
                    if (this.$refs[refName]) {
                        this.$refs[refName].value = JSON.stringify(this.cleanAttendees(group));
                    }
                });
            },
            normalizeEditorHtml(html) {
                return String(html ?? '')
                    .replace(/<div><br><\/div>/gi, '')
                    .replace(/<p><br><\/p>/gi, '')
                    .trim();
            },
            syncMinutesBody() {
                this.minutesBodyHtml = this.normalizeEditorHtml(this.$refs.minutesEditor?.innerHTML || '');
                if (this.$refs.recordingNotesInput) {
                    this.$refs.recordingNotesInput.value = this.minutesBodyHtml;
                }
            },
            applyMinutesFormat(command, value = null) {
                if (!this.$refs.minutesEditor) {
                    return;
                }

                this.$refs.minutesEditor.focus();
                document.execCommand(command, false, value);
                this.syncMinutesBody();
            },
            prepareSubmit() {
                this.syncMinutesBody();
                this.syncAttendees();
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
                    if (this.$refs.minutesRef) {
                        this.$refs.minutesRef.value = defaults.minutes_ref || this.initialMinutesRef || '';
                    }
                } catch (e) {
                    // ignore defaults errors
                }
            },
            applyNotice() {
                const selected = this.notices.find((notice) => String(notice.id) === String(this.selectedNoticeId));
                if (!selected) {
                    this.$refs.noticeRef.value = '';
                    this.$refs.governingBody.value = 'Board of Directors';
                    this.$refs.meetingType.value = 'Regular';
                    this.$refs.meetingDate.value = this.today || '';
                    this.$refs.timeStarted.value = '';
                    this.$refs.location.value = '';
                    this.$refs.meetingNo.value = '';
                    this.$refs.chairman.value = '';
                    this.$refs.secretary.value = '';
                    return;
                }

                this.$refs.noticeRef.value = selected.notice_number || '';
                this.$refs.governingBody.value = selected.governing_body || 'Board of Directors';
                this.$refs.meetingType.value = selected.type_of_meeting || 'Regular';
                this.$refs.meetingDate.value = selected.date_of_meeting || '';
                this.$refs.timeStarted.value = selected.time_started || '';
                this.$refs.location.value = selected.location || '';
                this.$refs.meetingNo.value = selected.meeting_no || '';
                this.$refs.chairman.value = selected.chairman || '';
                this.$refs.secretary.value = selected.secretary || '';
            },
        };
    }
</script>
@endsection

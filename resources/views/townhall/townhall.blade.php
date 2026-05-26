@extends('layouts.app')
@section('title', 'Town Hall')

@section('content')
<div id="townhall-page" class="w-full h-full px-6 py-5" x-data="townhallContactSuggest()" x-init="syncRecipientFields()">

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $attendanceAction = $todayAttendance->next_punch_action;
        $attendanceButtonLabel = $todayAttendance->next_punch_label;
        $attendanceIcon = $todayAttendance->next_punch_icon;
        $todayHours = (float) ($todayAttendance->total_working_hours ?? 0);
    @endphp

    <section class="mb-5 overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="grid gap-0 lg:grid-cols-[1.2fr_0.8fr]">
            <div class="relative bg-gray-950 px-6 py-6 text-white">
                <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-blue-500/30 to-transparent"></div>
                <div class="relative">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-200">Today Attendance</p>
                    <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="text-2xl font-semibold">{{ now()->format('l, F d') }}</h2>
                            <p class="mt-1 text-sm text-gray-300">
                                {{ $todayAttendance->time_in ? $todayAttendance->current_punch_status : 'Start your workday from Town Hall.' }}
                                @if($todayAttendance->time_out)
                                    Finished at {{ $todayAttendance->time_out->format('h:i A') }}.
                                @elseif($attendanceAction)
                                    Next punch: {{ $attendanceButtonLabel }}.
                                @endif
                            </p>
                        </div>

                        <form method="POST" action="{{ route('townhall.attendance.clock') }}">
                            @csrf
                            <input type="hidden" name="action" value="{{ $attendanceAction }}">
                            <button
                                type="submit"
                                @disabled($attendanceAction === null)
                                class="inline-flex min-w-[150px] items-center justify-center gap-2 rounded-lg px-5 py-3 text-sm font-semibold shadow-lg transition
                                    {{ $attendanceAction === 'clock_out'
                                        ? 'bg-white text-gray-950 hover:bg-gray-100'
                                        : ($attendanceAction === null
                                            ? 'cursor-not-allowed bg-white/15 text-white/60'
                                            : 'bg-blue-500 text-white hover:bg-blue-400') }}">
                                <i class="fas {{ $attendanceIcon }}"></i>
                                {{ $attendanceButtonLabel }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 divide-x divide-gray-100 border-t border-gray-100 bg-white text-center lg:border-l lg:border-t-0">
                <div class="p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">In</p>
                    <p class="mt-2 text-sm font-semibold text-gray-900">{{ $todayAttendance->time_in?->format('h:i A') ?? '--:--' }}</p>
                </div>
                <div class="p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Status</p>
                    <p class="mt-2 text-sm font-semibold text-gray-900">{{ $todayAttendance->current_punch_status }}</p>
                </div>
                <div class="p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Hours</p>
                    <p class="mt-2 text-sm font-semibold text-gray-900">{{ number_format($todayHours, 2) }}</p>
                </div>
                <div class="col-span-3 border-t border-gray-100 p-4 text-left">
                    <a href="{{ route('human-capital.attendance') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:text-blue-800">
                        View full attendance
                        <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    @if(Auth::user()->hasPermission('create_townhall'))
    <div x-show="showSlideOver" x-cloak class="fixed inset-0 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/40" @click="showSlideOver = false"></div>

        <div class="absolute inset-0 flex">
            {{-- LEFT PREVIEW PANEL --}}
            <div
                x-show="showSlideOver"
                x-transition:enter="transform transition ease-in-out duration-300"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="w-[70%] h-full bg-[#f5f6f8] overflow-y-auto p-6 border-r border-gray-200"
            >
                <div class="max-w-[850px] mx-auto mb-4 flex justify-end">
                    <button
                        type="button"
                        id="download-preview-pdf"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 shadow transition"
                    >
                        <i class="fas fa-file-pdf"></i>
                        Download PDF
                    </button>
                </div>

                <div class="max-w-[850px] mx-auto">
                    <div id="memo-preview-pages" class="space-y-6"></div>
                </div>
            </div>

            {{-- RIGHT FORM PANEL --}}
            <div
                x-show="showSlideOver"
                x-transition:enter="transform transition ease-in-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="w-[30%] h-full bg-white shadow-2xl flex flex-col"
            >
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-800">Add Communication</h2>

                    <button
                        type="button"
                        @click="showSlideOver = false"
                        class="text-gray-400 hover:text-gray-600 text-lg"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="townhall-form" action="{{ route('townhall.store') }}" method="POST" enctype="multipart/form-data" class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                    @csrf

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Ref #</label>
                            <input
                                type="text"
                                value="MEMO-AUTO-INCREMENT"
                                readonly
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-600"
                            >
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Date</label>
                            <input
                                type="date"
                                name="communication_date"
                                x-model="previewDate"
                                value="{{ old('communication_date', now()->format('Y-m-d')) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">From</label>
                            <input
                                type="text"
                                value="{{ Auth::user()->name }}"
                                x-model="previewFrom"
                                readonly
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-600 cursor-not-allowed"
                            >
                            <p class="mt-1 text-xs text-gray-400">Automatically set based on signed-in user</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Department / Stakeholder</label>
                            <input
                                type="text"
                                name="department_stakeholder"
                                x-model="previewDepartment"
                                value="{{ old('department_stakeholder') }}"
                                placeholder="Enter department or stakeholder"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Recipient</label>

                        <div class="space-y-3">
                            <div class="grid grid-cols-[120px_1fr] gap-3">
                                <select
                                    x-model="previewRecipientLabel"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                                >
                                    <option value="To">To</option>
                                    <option value="For">For</option>
                                </select>

                                <select
                                    name="recipient_type"
                                    x-model="previewRecipientType"
                                    @change="$nextTick(() => syncRecipientFields())"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                                >
                                    <option value="all">All Employees</option>
                                    <option value="all_admins">All Admins</option>
                                    <option value="all_clients">All Clients</option>
                                    <option value="all_users">All Users</option>
                                    <option value="employee">Specific Recipients</option>
                                </select>
                            </div>

<div x-show="previewRecipientType !== ''" x-cloak class="space-y-4">
                                {{-- Additional Specific Recipients --}}
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">
                                        Additional Specific Recipients
                                    </label>

                                    <div class="min-h-[42px] rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 flex flex-wrap gap-2">
                                        <template x-if="previewTo">
                                            <template x-for="name in previewTo.split(',').map(item => item.trim()).filter(Boolean)" :key="name">
                                                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700">
                                                    <span x-text="name"></span>
                                                </span>
                                            </template>
                                        </template>

                                        <template x-if="!previewTo">
                                            <span class="text-xs text-gray-400">No recipient selected</span>
                                        </template>
                                    </div>
                                </div>

                                {{-- Users: Employees / Admins --}}
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">
                                        Employees / Admins
                                    </label>

                                    <div class="max-h-[190px] overflow-y-auto rounded-lg border border-gray-200 bg-white divide-y divide-gray-100">
                                        <template x-for="user in usersForRecipient" :key="'user-' + user.id">
                                            <button
                                                type="button"
                                                @click="selectRecipientUser(user.id)"
                                                @dblclick="deselectRecipientUser(user.id)"
                                                class="w-full px-3 py-2 text-left text-sm transition flex items-center justify-between"
                                                :class="isRecipientUserSelected(user.id)
                                                    ? 'bg-blue-50 text-blue-700'
                                                    : 'hover:bg-gray-50 text-gray-700'"
                                            >
                                                <span>
                                                    <span class="font-medium" x-text="user.name"></span>
                                                    <span class="text-xs text-gray-400" x-text="' — ' + user.role"></span>
                                                </span>

                                                <span
                                                    x-show="isRecipientUserSelected(user.id)"
                                                    class="text-blue-600 text-xs font-semibold"
                                                >
                                                    Selected
                                                </span>
                                            </button>
                                        </template>
                                    </div>

                                    <p class="mt-1 text-xs text-gray-400">
                                        Click once to select. Double-click to deselect.
                                    </p>
                                </div>

                                {{-- Clients / Contacts --}}
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">
                                        Clients / Contacts
                                    </label>

                                    <div class="max-h-[190px] overflow-y-auto rounded-lg border border-gray-200 bg-white divide-y divide-gray-100">
                                        <template x-for="contact in contactsForRecipient" :key="'contact-' + contact.id">
                                            <button
                                                type="button"
                                                @click="selectRecipientContact(contact.id)"
                                                @dblclick="deselectRecipientContact(contact.id)"
                                                class="w-full px-3 py-2 text-left text-sm transition flex items-center justify-between"
                                                :class="isRecipientContactSelected(contact.id)
                                                    ? 'bg-green-50 text-green-700'
                                                    : 'hover:bg-gray-50 text-gray-700'"
                                            >
                                                <span>
                                                    <span class="font-medium" x-text="contact.name"></span>
                                                    <span class="text-xs text-gray-400"> — Client</span>
                                                </span>

                                                <span
                                                    x-show="isRecipientContactSelected(contact.id)"
                                                    class="text-green-600 text-xs font-semibold"
                                                >
                                                    Selected
                                                </span>
                                            </button>
                                        </template>
                                    </div>

                                    <p class="mt-1 text-xs text-gray-400">
                                        Click once to select. Double-click to deselect.
                                    </p>
                                </div>

                                {{-- Hidden inputs for form submit --}}
                                <template x-for="id in previewRecipientUserIds" :key="'hidden-user-' + id">
                                    <input type="hidden" name="recipient_user_ids[]" :value="id">
                                </template>

                                <template x-for="id in previewRecipientContactIds" :key="'hidden-contact-' + id">
                                    <input type="hidden" name="recipient_contact_ids[]" :value="id">
                                </template>
                            </div>

                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                <span class="font-medium" x-text="previewRecipientLabel"></span>:
                                <span x-text="previewTo || 'All Employees'"></span>
                            </div>
                        </div>

                        <input type="hidden" name="recipient_label" :value="previewRecipientLabel">
                        <input type="hidden" name="to_for" :value="previewTo">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Priority</label>
                        <select
                            name="priority"
                            x-model="previewPriority"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                        >
                            <option value="Low">Low</option>
                            <option value="High">High</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Subject</label>
                        <input
                            type="text"
                            name="subject"
                            x-model="previewSubject"
                            value="{{ old('subject') }}"
                            placeholder="Enter subject"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Body</label>

                        <div class="rounded-xl border border-gray-300 bg-[#fafafa] overflow-hidden shadow-sm">
                            <div class="word-ribbon border-b border-gray-200 bg-white px-3 py-2">
                                <div class="text-[11px] font-medium text-gray-500">
                                    Document Editor
                                </div>
                                <div class="mt-1 text-[11px] text-gray-400">
                                    Tip: click the table icon to insert a table. For table actions, click inside the table and use the table menu.
                                </div>
                            </div>

                            <div id="editor"></div>
                        </div>

                        <input type="hidden" name="message" id="message">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">CC</label>
                            <div class="relative">
                                <input
                                    type="text"
                                    name="cc"
                                    x-model="previewCc"
                                    @focus="openSuggestions('cc')"
                                    @input.debounce.250ms="searchSuggestions('cc')"
                                    @keydown.arrow-down.prevent="highlightNext('cc')"
                                    @keydown.arrow-up.prevent="highlightPrev('cc')"
                                    @keydown.enter.prevent="selectHighlighted('cc')"
                                    @keydown.escape="closeSuggestions('cc')"
                                    autocomplete="off"
                                    value="{{ old('cc') }}"
                                    placeholder="Enter CC recipients"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                                >

                                <div
                                    x-show="dropdowns.cc.show && dropdowns.cc.items.length > 0"
                                    x-cloak
                                    @click.away="closeSuggestions('cc')"
                                    class="absolute z-50 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg overflow-hidden"
                                >
                                    <template x-for="(contact, index) in dropdowns.cc.items" :key="'cc-' + contact.id">
                                        <button
                                            type="button"
                                            @click="selectSuggestion('cc', contact)"
                                            :class="dropdowns.cc.highlighted === index ? 'bg-blue-50' : 'bg-white'"
                                            class="w-full px-3 py-2 text-left hover:bg-blue-50 border-b border-gray-100 last:border-b-0"
                                        >
                                            <div class="text-sm font-medium text-gray-800" x-text="contact.name"></div>
                                            <div class="text-xs text-gray-500" x-text="contact.role + (contact.company_name ? ' • ' + contact.company_name : (contact.email ? ' • ' + contact.email : ''))"></div>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Additional</label>
                            <div class="relative">
                                <input
                                    type="text"
                                    name="additional"
                                    x-model="previewAdditional"
                                    @focus="openSuggestions('additional')"
                                    @input.debounce.250ms="searchSuggestions('additional')"
                                    @keydown.arrow-down.prevent="highlightNext('additional')"
                                    @keydown.arrow-up.prevent="highlightPrev('additional')"
                                    @keydown.enter.prevent="selectHighlighted('additional')"
                                    @keydown.escape="closeSuggestions('additional')"
                                    autocomplete="off"
                                    value="{{ old('additional') }}"
                                    placeholder="Optional"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                                >

                                <div
                                    x-show="dropdowns.additional.show && dropdowns.additional.items.length > 0"
                                    x-cloak
                                    @click.away="closeSuggestions('additional')"
                                    class="absolute z-50 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg overflow-hidden"
                                >
                                    <template x-for="(contact, index) in dropdowns.additional.items" :key="'additional-' + contact.id">
                                        <button
                                            type="button"
                                            @click="selectSuggestion('additional', contact)"
                                            :class="dropdowns.additional.highlighted === index ? 'bg-blue-50' : 'bg-white'"
                                            class="w-full px-3 py-2 text-left hover:bg-blue-50 border-b border-gray-100 last:border-b-0"
                                        >
                                            <div class="text-sm font-medium text-gray-800" x-text="contact.name"></div>
                                            <div class="text-xs text-gray-500" x-text="contact.role + (contact.company_name ? ' • ' + contact.company_name : (contact.email ? ' • ' + contact.email : ''))"></div>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Attachment</label>
                        <input
                            type="file"
                            name="attachment"
                            accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100"
                        >
                        <p class="mt-1 text-xs text-gray-400">
                            Allowed: JPG, JPEG, PNG, GIF, WEBP, PDF, DOC, DOCX
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Expiry Date & Time</label>
                        <input
                            type="datetime-local"
                            name="expires_at"
                            x-model="previewExpiry"
                            value="{{ old('expires_at') }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                        >
                        <p class="mt-1 text-xs text-gray-400">
                            Communication will automatically archive after this date and time
                        </p>
                    </div>

                    <div class="px-0 py-4 border-t border-gray-200 flex items-center gap-3">
                        <button
                            type="button"
                            @click="showSlideOver = false"
                            class="flex-1 border border-gray-300 text-gray-700 rounded-lg py-2.5 text-sm font-medium hover:bg-gray-50 transition"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="flex-1 bg-blue-600 text-white rounded-lg py-2.5 text-sm font-medium hover:bg-blue-700 transition"
                        >
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- MAIN TOWN HALL FEED --}}
    <div class="space-y-5">

        {{-- HERO / HEADER --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="relative px-6 py-6 md:px-7">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-50 via-white to-sky-50"></div>

                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-700">
                            <i class="fas fa-bullhorn text-[10px]"></i>
                            Company Communication
                        </div>

                        <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Town Hall</h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Stay updated with company memorandums, announcements, and official communications.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700">
                            <i class="fas fa-bars text-xs"></i>
                        </button>

                        <button class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700">
                            <i class="far fa-rectangle-list text-xs"></i>
                        </button>

                        @if(Auth::user()->hasPermission('create_townhall'))
                            <button
                                @click="showSlideOver = true"
                                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                            >
                                <i class="fas fa-plus text-xs"></i>
                                Add Communication
                            </button>
                        @endif

                        <button class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700">
                            <i class="fas fa-ellipsis-v text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ANNOUNCEMENTS LIST --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Announcements & Memorandums</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Browse official company communications intended for you and your group.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                        <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                        {{ $communications->firstItem() ?? 0 }}–{{ $communications->lastItem() ?? 0 }} of {{ $communications->total() }}
                    </span>

                    <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">
                        <i class="fas fa-eye-slash text-[10px]"></i>
                        Restricted memos are hidden as ***
                    </span>
                </div>
            </div>

            <div class="p-5 space-y-4">
                @forelse($communications as $communication)
                    @php
                        $currentUser = Auth::user();
                        $role = strtolower(trim((string) $currentUser->role));

                        $recipientUserIds = collect($communication->recipient_user_ids ?? [])
                            ->map(fn ($id) => (int) $id)
                            ->toArray();

                        $canViewMemo = $currentUser->hasPermission('approve_townhall')
                            || ($communication->recipient_type === 'all_users')
                            || (in_array($communication->recipient_type, ['all', 'all_employees', 'employee'], true)
                                && $role === 'employee'
                                && (($communication->recipient_type ?? '') !== 'employee'
                                    || in_array((int) $currentUser->id, $recipientUserIds, true)
                                    || (int) $communication->recipient_user_id === (int) $currentUser->id))
                            || ($communication->recipient_type === 'all_admins'
                                && in_array($role, ['admin', 'superadmin', 'super admin', 'system super admin'], true))
                            || ($communication->recipient_type === 'all_clients'
                                && in_array($role, ['client', 'customer'], true))
                            || ((int) $communication->recipient_user_id === (int) $currentUser->id)
                            || in_array((int) $currentUser->id, $recipientUserIds, true);

                        $censored = !$canViewMemo;

                        $priority = $communication->priority ?? 'Low';
                        $priorityClasses = match($priority) {
                            'High' => 'bg-red-50 text-red-700 ring-red-100',
                            'Medium' => 'bg-amber-50 text-amber-700 ring-amber-100',
                            default => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                        };

                        $approval = $communication->approval_status ?? 'Pending';
                        $approvalClasses = match($approval) {
                            'Approved' => 'bg-green-50 text-green-700 ring-green-100',
                            'Rejected' => 'bg-red-50 text-red-700 ring-red-100',
                            'Needs Revision' => 'bg-blue-50 text-blue-700 ring-blue-100',
                            default => 'bg-yellow-50 text-yellow-700 ring-yellow-100',
                        };
                    @endphp

                    <div
                        class="group rounded-2xl border {{ $censored ? 'border-slate-200 bg-slate-50/70' : 'border-slate-200 bg-white hover:border-blue-200 hover:shadow-md' }} transition"
                        @if(!$censored)
                            onclick="window.location='{{ route('townhall.show', $communication->id) }}'"
                        @endif
                        title="{{ $censored ? 'This memo is not intended for you' : '' }}"
                    >
                        <div class="p-5">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                            {{ $censored ? '***' : ($communication->ref_no ?: '—') }}
                                        </span>

                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $priorityClasses }}">
                                            {{ $censored ? '***' : $priority }}
                                        </span>

                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $communication->is_archived ? 'bg-slate-100 text-slate-600 ring-slate-200' : $approvalClasses }}">
                                            {{ $censored ? '***' : ($communication->is_archived ? 'Expired' : $approval) }}
                                        </span>

                                        @if($censored)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">
                                                <i class="fas fa-lock text-[10px]"></i>
                                                Restricted
                                            </span>
                                        @endif
                                    </div>

                                    <h3 class="mt-4 text-lg font-semibold {{ $censored ? 'text-slate-400' : 'text-slate-900 group-hover:text-blue-700' }} transition">
                                        {{ $censored ? '***' : ($communication->subject ?: 'No Subject') }}
                                    </h3>

                                    <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                        <div class="rounded-xl bg-slate-50 px-4 py-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Date</p>
                                            <p class="mt-1 text-sm font-medium text-slate-800">
                                                {{ $censored ? '***' : ($communication->communication_date ? \Carbon\Carbon::parse($communication->communication_date)->format('M d, Y') : '—') }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl bg-slate-50 px-4 py-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">From</p>
                                            <p class="mt-1 text-sm font-medium text-slate-800">
                                                {{ $censored ? '***' : ($communication->from_name ?: '—') }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl bg-slate-50 px-4 py-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Department</p>
                                            <p class="mt-1 text-sm font-medium text-slate-800">
                                                {{ $censored ? '***' : ($communication->department_stakeholder ?: '—') }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl bg-slate-50 px-4 py-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Expiry</p>
                                            <p class="mt-1 text-sm font-medium text-slate-800">
                                                @if($censored)
                                                    ***
                                                @elseif($communication->expires_at)
                                                    {{ \Carbon\Carbon::parse($communication->expires_at)->format('M d, Y') }}
                                                @else
                                                    —
                                                @endif
                                            </p>
                                            @if(!$censored && $communication->expires_at)
                                                <p class="mt-0.5 text-xs text-slate-400">
                                                    {{ \Carbon\Carbon::parse($communication->expires_at)->format('h:i A') }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                            {{ $communication->recipient_label ?? 'To' }}
                                        </p>
                                        <p class="mt-1 text-sm font-medium text-slate-800">
                                            @if($censored)
                                                ***
                                            @else
                                                {{ $communication->recipient_names ?? $communication->to_for ?? 'Selected Recipients' }}
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="xl:ml-6 xl:w-[180px] shrink-0">
                                    <div class="flex h-full flex-col justify-between gap-3">
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Attachment</p>
                                            <div class="mt-2">
                                                @if($censored)
                                                    <span class="text-sm font-medium text-slate-400">***</span>
                                                @elseif($communication->attachment)
                                                    <a
                                                        href="{{ asset('storage/' . $communication->attachment) }}"
                                                        target="_blank"
                                                        class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-medium text-blue-700 ring-1 ring-slate-200 transition hover:bg-blue-50"
                                                        onclick="event.stopPropagation()"
                                                    >
                                                        <i class="fas fa-paperclip text-xs"></i>
                                                        View File
                                                    </a>
                                                @else
                                                    <span class="text-sm font-medium text-slate-500">No attachment</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex flex-col gap-2">
                                            @if($censored)
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-400 cursor-not-allowed"
                                                >
                                                    <i class="fas fa-lock text-xs"></i>
                                                    Restricted
                                                </button>
                                            @else
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                                                    onclick="event.stopPropagation(); window.location='{{ route('townhall.show', $communication->id) }}'"
                                                >
                                                    <i class="fas fa-eye text-xs"></i>
                                                    Open Memo
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                            <i class="fas fa-folder-open text-lg"></i>
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-slate-800">No Town Hall communications found</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Once communications are created and approved, they will appear here.
                        </p>
                    </div>
                @endforelse
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
                <div class="text-xs text-slate-500">
                    Showing {{ $communications->firstItem() ?? 0 }} to {{ $communications->lastItem() ?? 0 }} of {{ $communications->total() }} entries
                </div>

                <div class="text-xs text-slate-400">
                    Restricted communications are shown as *** when not intended for your account.
                </div>
            </div>

            @if(method_exists($communications, 'links'))
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $communications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/quill-table-better@1/dist/quill-table-better.css" rel="stylesheet">

<style>
    #townhall-page {
        background:
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.08), transparent 18%),
            linear-gradient(to bottom, #f8fafc, #f8fafc);
    }

    .word-ribbon {
        background: linear-gradient(to bottom, #ffffff, #f8fafc);
    }

    #editor .ql-toolbar.ql-snow {
        border: 0 !important;
        border-bottom: 1px solid #e5e7eb !important;
        background: #fff;
        padding: 10px 12px;
    }

    #editor .ql-container.ql-snow {
        border: 0 !important;
        min-height: 340px;
        background: #fff;
    }

    #editor .ql-editor {
        min-height: 340px;
        padding: 28px 26px;
        font-size: 15px;
        line-height: 1.85;
        color: #111827;
        font-family: "Calibri", "Arial", sans-serif;
    }

    #editor .ql-editor.ql-blank::before {
        left: 26px;
        right: 26px;
        font-style: italic;
        color: #9ca3af;
    }

    #editor .ql-picker-label,
    #editor .ql-picker-item,
    #editor .ql-stroke,
    #editor .ql-fill {
        color: #374151;
        stroke: #374151;
    }

    #editor .ql-editor p {
        margin-bottom: 0.65rem;
    }

    #editor .ql-editor h1,
    #editor .ql-editor h2,
    #editor .ql-editor h3 {
        line-height: 1.35;
        margin: 0.75rem 0;
    }

    .preview-body table,
    .memo-body-block table {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
        margin: 12px 0 !important;
    }

    .preview-body table tbody,
    .preview-body table thead,
    .preview-body table tr {
        width: 100% !important;
    }

    .preview-body table colgroup,
    .preview-body table col,
    .memo-body-block table colgroup,
    .memo-body-block table col {
        width: auto !important;
    }

    .preview-body th,
    .preview-body td,
    .memo-body-block th,
    .memo-body-block td {
        width: auto !important;
        min-width: 0 !important;
        border: 1px solid #94a3b8 !important;
        padding: 10px 12px !important;
        vertical-align: top !important;
        word-break: break-word !important;
        overflow-wrap: anywhere !important;
        white-space: normal !important;
    }

    .preview-body th,
    .memo-body-block th {
        background: #f8fafc !important;
        font-weight: 600 !important;
    }

    .ql-editor table {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
        margin: 12px 0 !important;
    }

    .ql-editor table colgroup,
    .ql-editor table col {
        width: auto !important;
    }

    .ql-editor th,
    .ql-editor td {
        min-width: 0 !important;
        border: 1px solid #94a3b8 !important;
        padding: 10px 12px !important;
        vertical-align: top !important;
        word-break: break-word !important;
        overflow-wrap: anywhere !important;
        white-space: normal !important;
        background: #fff !important;
    }

    .ql-editor th {
        background: #f8fafc !important;
        font-weight: 600 !important;
    }

    .preview-body p,
    .preview-body li,
    .preview-body span,
    .preview-body div,
    .ql-editor p,
    .ql-editor li,
    .ql-editor span,
    .ql-editor div,
    .memo-body-block p,
    .memo-body-block li,
    .memo-body-block span,
    .memo-body-block div {
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .preview-body h1,
    .preview-body h2,
    .preview-body h3,
    .memo-body-block h1,
    .memo-body-block h2,
    .memo-body-block h3 {
        line-height: 1.35;
        margin: 0.75rem 0;
    }

    .preview-body ul,
    .preview-body ol,
    .memo-body-block ul,
    .memo-body-block ol {
        padding-left: 1.5rem;
    }

    .qlbt-operation-menu,
    .ql-table-better-menu,
    .quill-table-better-wrapper {
        z-index: 9999 !important;
    }

    [x-cloak] {
        display: none !important;
    }

    .memo-page {
        width: 100%;
        min-height: 1123px;
        background: #fff;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        padding: 50px 60px;
        box-sizing: border-box;
        position: relative;
        overflow: hidden;
    }

    .memo-page-header {
        margin-bottom: 24px;
    }

    .memo-page-title {
        text-align: center;
        margin-bottom: 28px;
    }

    .memo-page-title h2 {
        font-size: 28px;
        font-weight: 600;
        letter-spacing: 0.04em;
        color: #555;
        font-family: "Times New Roman", Georgia, serif;
        margin: 0;
    }

    .memo-page-meta {
        margin-bottom: 10px;
        font-size: 14px;
        line-height: 1.3;
        color: #111827;
        font-family: "Times New Roman", Georgia, serif;
    }

    .memo-page-divider {
        border-bottom: 1px solid #6b7280;
        margin: 10px 0 24px 0;
    }

    .memo-page-body {
        font-size: 14px;
        line-height: 1.7;
        color: #111827;
        font-family: "Times New Roman", Georgia, serif;
    }

    .memo-page-body p {
        margin: 0 0 18px 0;
    }

    .memo-page-footer {
        margin-top: 40px;
        font-family: "Times New Roman", Georgia, serif;
        color: #1f2937;
    }

    .memo-footer-note {
        margin-top: 48px;
        font-size: 11px;
        line-height: 1.35;
    }

    .memo-footer-address {
        margin-top: 20px;
        font-size: 11px;
        line-height: 1.3;
    }

    .memo-measure-wrap {
        position: absolute;
        left: -99999px;
        top: 0;
        width: 850px;
        visibility: hidden;
        pointer-events: none;
        z-index: -1;
    }

    .memo-body-block,
    .memo-body-block * {
        font-family: "Times New Roman", Georgia, serif !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill-table-better@1/dist/quill-table-better.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function buildMemoHeader(data, pageNumber) {
    const showMeta = pageNumber === 1;

    return `
        <div class="memo-page-header">
            <div style="display:flex;align-items:flex-start;gap:16px;margin-bottom:24px;">
                <div style="flex:0 0 auto;padding-top:4px;">
                    <img src="${data.logoUrl}" alt="JK Logo" style="height:72px;width:auto;object-fit:contain;">
                </div>

                <div style="flex:1 1 auto;padding-top:4px;">
                    <p style="font-size:12px;line-height:1.35;color:#4b5563;font-family:'Times New Roman', Georgia, serif;margin:0;">
                        Atty. Jose B. Ogang, CPA, MMPSM · Jose Tamayo Rio,<br>
                        MM-BM, CPA · Lyndon Earl P. Rio, RN, CB · John Kelly Abalde,<br>
                        CLSSBB, CPM
                    </p>
                </div>
            </div>

            <div class="memo-page-title">
                <h2>MEMORANDUM</h2>
            </div>

            ${
                showMeta
                    ? `
                    <div class="memo-page-meta">
                        <p style="margin:2px 0;"><strong>Memo NO.:</strong> ${escapeHtml(data.ref)}</p>
                        <p style="margin:2px 0;"><strong>Date:</strong> ${escapeHtml(data.date)}</p>
                        <p style="margin:2px 0;"><strong>${escapeHtml(data.recipientLabel)}:</strong> ${escapeHtml(data.to)}</p>
                        <p style="margin:2px 0;"><strong>From:</strong> ${escapeHtml(data.from)}</p>
                        <p style="margin:2px 0;"><strong>SUBJECT:</strong> ${escapeHtml(data.subject)}</p>
                    </div>
                    <div class="memo-page-divider"></div>
                    `
                    : ''
            }
        </div>
    `;
}

function buildMemoFooter(data, isLastPage) {
    return `
        <div class="memo-page-footer">
            ${
                isLastPage
                    ? `
                    <div style="font-size:14px;line-height:1.7;">
                        <p style="margin:0 0 32px 0;">
                            Issued this <strong>${escapeHtml(data.date)}</strong> in Cebu City, Philippines.
                        </p>

                        <div style="margin-top:20px;">
                            <p style="margin:0 0 8px 0;">Prepared by:</p>
                            <p style="margin:0;font-weight:600;line-height:1.2;">${escapeHtml(data.from)}</p>
                        </div>

                        <div style="margin-top:34px;">
                            <p style="margin:0 0 8px 0;">Approved by:</p>
                            <p style="margin:0;font-weight:600;line-height:1.2;">John Kelly D. Abalde</p>
                            <p style="margin:0;line-height:1.2;">President and CEO</p>
                        </div>
                    </div>
                    `
                    : ''
            }

            <div class="memo-footer-note">
                This Memorandum is an official corporate record of JK&amp;C INC. Unauthorized reproduction,
                alteration, disclosure, or misuse of this Memorandum, in whole or in part, is strictly prohibited
                and may result in administrative sanctions, termination of employment or engagement, and/or the
                institution of appropriate civil, criminal, or regulatory actions, in accordance with applicable
                laws and company policies.
            </div>

            <div class="memo-footer-address">
                JK&amp;C INC.<br>
                3F Cebu Holdings Center Cebu Business Park, Cebu City, Philippines, 6000
            </div>
        </div>
    `;
}

function createMemoPageShell(data, pageNumber, isLastPage = false) {
    return `
        <div class="memo-page">
            ${buildMemoHeader(data, pageNumber)}
            <div class="memo-page-body" data-page-body></div>
            ${buildMemoFooter(data, isLastPage)}
        </div>
    `;
}

function paginateMemoPreview(data) {
    const container = document.getElementById('memo-preview-pages');
    if (!container) return;

    container.innerHTML = '';

    const measureWrap = document.createElement('div');
    measureWrap.className = 'memo-measure-wrap';
    document.body.appendChild(measureWrap);

    const bodyHtml = (data.body || '').trim() || '<p style="color:#9ca3af;">Write the formal communication here...</p>';

    const source = document.createElement('div');
    source.innerHTML = bodyHtml;

    const nodes = Array.from(source.childNodes).filter(node => {
        if (node.nodeType === Node.TEXT_NODE) {
            return node.textContent.trim() !== '';
        }
        return true;
    });

    const blocks = nodes.length
        ? nodes.map(node => {
            const wrapper = document.createElement('div');
            wrapper.className = 'memo-body-block';
            wrapper.appendChild(node.cloneNode(true));
            return wrapper;
        })
        : (() => {
            const wrapper = document.createElement('div');
            wrapper.className = 'memo-body-block';
            wrapper.innerHTML = '<p style="color:#9ca3af;">Write the formal communication here...</p>';
            return [wrapper];
        })();

    let pageNumber = 1;
    let pages = [];
    let currentPageWrap = document.createElement('div');
    currentPageWrap.innerHTML = createMemoPageShell(data, pageNumber, false);
    let currentPageEl = currentPageWrap.firstElementChild;
    let currentBody = currentPageEl.querySelector('[data-page-body]');
    measureWrap.appendChild(currentPageEl);

    function getAvailableHeight(pageEl, bodyEl) {
        const footer = pageEl.querySelector('.memo-page-footer');
        return pageEl.clientHeight - bodyEl.offsetTop - footer.offsetHeight - 10;
    }

    blocks.forEach((block) => {
        const candidate = block.cloneNode(true);
        currentBody.appendChild(candidate);

        const availableHeight = getAvailableHeight(currentPageEl, currentBody);

        if (currentBody.scrollHeight > availableHeight) {
            currentBody.removeChild(candidate);

            pages.push(currentPageEl);

            pageNumber++;
            const nextPageWrap = document.createElement('div');
            nextPageWrap.innerHTML = createMemoPageShell(data, pageNumber, false);
            currentPageEl = nextPageWrap.firstElementChild;
            currentBody = currentPageEl.querySelector('[data-page-body]');
            measureWrap.appendChild(currentPageEl);

            currentBody.appendChild(block.cloneNode(true));
        }
    });

    pages.push(currentPageEl);

    if (pages.length > 0) {
        const lastIndex = pages.length - 1;
        const lastBodyHtml = pages[lastIndex].querySelector('[data-page-body]').innerHTML;

        const rebuiltLast = document.createElement('div');
        rebuiltLast.innerHTML = createMemoPageShell(data, lastIndex + 1, true);
        rebuiltLast.firstElementChild.querySelector('[data-page-body]').innerHTML = lastBodyHtml;
        pages[lastIndex] = rebuiltLast.firstElementChild;
    }

    container.innerHTML = '';
    pages.forEach(page => container.appendChild(page));

    document.body.removeChild(measureWrap);
}

function townhallContactSuggest() {
    return {
        showSlideOver: false,
        previewRef: 'MEMO-AUTO-INCREMENT',
        previewDate: @js(old('communication_date', now()->format('Y-m-d'))),
        previewFrom: @js(Auth::user()->name),
        previewDepartment: @js(old('department_stakeholder', '')),
        previewRecipientLabel: @js(old('recipient_label', 'To')),
        previewRecipientType: @js(old('recipient_type', 'all')),
        previewRecipientUserIds: @js(old('recipient_user_ids', [])),
        previewRecipientContactIds: @js(old('recipient_contact_ids', [])),
        usersForRecipient: @js($usersForRecipients->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
        ])->values()),
        contactsForRecipient: @js($contactsForRecipients->map(function ($contact) {
            $name = trim(collect([
                $contact->first_name,
                $contact->middle_name,
                $contact->last_name,
                $contact->name_extension,
            ])->filter()->implode(' ')) ?: $contact->company_name;

            return [
                'id' => $contact->id,
                'name' => $name,
                'role' => 'Client',
            ];
        })->values()),
        previewTo: @js(old('to_for', 'All Employees')),
        previewPriority: @js(old('priority', 'Low')),
        previewSubject: @js(old('subject', '')),
        previewBody: @js(old('message', '<p style="color:#9ca3af;">Write the formal communication here...</p>')),
        previewCc: @js(old('cc', '')),
        previewAdditional: @js(old('additional', '')),
        previewExpiry: @js(old('expires_at', '')),

        syncRecipientFields() {
            const selectedUserIds = Array.isArray(this.previewRecipientUserIds)
                ? this.previewRecipientUserIds.map(id => String(id))
                : [];

            const selectedContactIds = Array.isArray(this.previewRecipientContactIds)
                ? this.previewRecipientContactIds.map(id => String(id))
                : [];

            const selectedUsers = this.usersForRecipient.filter(user => {
                return selectedUserIds.includes(String(user.id));
            });

            const selectedContacts = this.contactsForRecipient.filter(contact => {
                return selectedContactIds.includes(String(contact.id));
            });

            const groupLabels = {
                all: 'All Employees',
                all_admins: 'All Admins',
                all_clients: 'All Clients',
                all_users: 'All Users',
                employee: ''
            };

            const names = [
                groupLabels[this.previewRecipientType] || '',
                ...selectedUsers.map(user => user.name),
                ...selectedContacts.map(contact => contact.name)
            ].filter(Boolean);

            this.previewTo = [...new Set(names)].join(', ');
        },

        isRecipientUserSelected(id) {
            return this.previewRecipientUserIds
                .map(item => String(item))
                .includes(String(id));
        },

        selectRecipientUser(id) {
            if (!this.isRecipientUserSelected(id)) {
                this.previewRecipientUserIds.push(String(id));
            }

            this.syncRecipientFields();
        },

        deselectRecipientUser(id) {
            this.previewRecipientUserIds = this.previewRecipientUserIds
                .filter(item => String(item) !== String(id));

            this.syncRecipientFields();
        },

        isRecipientContactSelected(id) {
            return this.previewRecipientContactIds
                .map(item => String(item))
                .includes(String(id));
        },

        selectRecipientContact(id) {
            if (!this.isRecipientContactSelected(id)) {
                this.previewRecipientContactIds.push(String(id));
            }

            this.syncRecipientFields();
        },

        deselectRecipientContact(id) {
            this.previewRecipientContactIds = this.previewRecipientContactIds
                .filter(item => String(item) !== String(id));

            this.syncRecipientFields();
        },

        dropdowns: {
            to: { items: [], show: false, highlighted: -1 },
            cc: { items: [], show: false, highlighted: -1 },
            additional: { items: [], show: false, highlighted: -1 },
        },

        getFieldValue(field) {
            if (field === 'to') return this.previewTo || '';
            if (field === 'cc') return this.previewCc || '';
            if (field === 'additional') return this.previewAdditional || '';
            return '';
        },

        setFieldValue(field, value) {
            if (field === 'to') this.previewTo = value;
            if (field === 'cc') this.previewCc = value;
            if (field === 'additional') this.previewAdditional = value;
        },

        extractLastToken(value) {
            if (!value) return '';
            const parts = value.split(',');
            return (parts[parts.length - 1] || '').trim();
        },

        async openSuggestions(field) {
            await this.fetchSuggestions(field, '', 3);
        },

        async searchSuggestions(field) {
            const rawValue = this.getFieldValue(field);
            const query = this.extractLastToken(rawValue);
            await this.fetchSuggestions(field, query, 8);
        },

        async fetchSuggestions(field, query = '', limit = 8) {
            try {
                const response = await fetch(`{{ route('townhall.recipients.search') }}?q=${encodeURIComponent(query)}&limit=${limit}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    this.dropdowns[field].items = [];
                    this.dropdowns[field].show = false;
                    this.dropdowns[field].highlighted = -1;
                    return;
                }

                const data = await response.json();

                this.dropdowns[field].items = Array.isArray(data) ? data : [];
                this.dropdowns[field].show = this.dropdowns[field].items.length > 0;
                this.dropdowns[field].highlighted = this.dropdowns[field].items.length > 0 ? 0 : -1;
            } catch (error) {
                this.dropdowns[field].items = [];
                this.dropdowns[field].show = false;
                this.dropdowns[field].highlighted = -1;
            }
        },

        selectSuggestion(field, contact) {
            const name = (contact.name || '').trim();
            if (!name) return;

            const currentValue = this.getFieldValue(field).trim();

            if (!currentValue) {
                this.setFieldValue(field, field === 'to' ? name + ', ' : name);
                this.closeSuggestions(field);
                return;
            }

            const parts = currentValue.split(',');
            parts[parts.length - 1] = name;

            const cleaned = parts
                .map(part => part.trim())
                .filter(Boolean);

            const unique = [];
            cleaned.forEach(item => {
                if (!unique.includes(item)) {
                    unique.push(item);
                }
            });

            if (field === 'to') {
                this.setFieldValue(field, unique.join(', ') + ', ');
            } else {
                this.setFieldValue(field, unique.join(', '));
            }

            this.closeSuggestions(field);
        },

        highlightNext(field) {
            if (!this.dropdowns[field].show || this.dropdowns[field].items.length === 0) return;

            if (this.dropdowns[field].highlighted < this.dropdowns[field].items.length - 1) {
                this.dropdowns[field].highlighted++;
            } else {
                this.dropdowns[field].highlighted = 0;
            }
        },

        highlightPrev(field) {
            if (!this.dropdowns[field].show || this.dropdowns[field].items.length === 0) return;

            if (this.dropdowns[field].highlighted > 0) {
                this.dropdowns[field].highlighted--;
            } else {
                this.dropdowns[field].highlighted = this.dropdowns[field].items.length - 1;
            }
        },

        selectHighlighted(field) {
            if (
                this.dropdowns[field].show &&
                this.dropdowns[field].highlighted >= 0 &&
                this.dropdowns[field].items[this.dropdowns[field].highlighted]
            ) {
                this.selectSuggestion(field, this.dropdowns[field].items[this.dropdowns[field].highlighted]);
            }
        },

        closeSuggestions(field) {
            this.dropdowns[field].show = false;
            this.dropdowns[field].highlighted = -1;
        }
    };
}

document.addEventListener('DOMContentLoaded', function () {
    const editorEl = document.getElementById('editor');
    const hiddenInput = document.getElementById('message');
    const form = document.getElementById('townhall-form');
    const defaultHtml = '<p style="color:#9ca3af;">Write the formal communication here...</p>';

    function renderPages(alpineData) {
        if (!alpineData) return;

        paginateMemoPreview({
            logoUrl: `{{ asset('images/jk-logo.png') }}`,
            ref: alpineData.previewRef || 'AUTO-INCREMENT',
            date: alpineData.previewDate || '______________',
            recipientLabel: alpineData.previewRecipientLabel || 'To',
            to: alpineData.previewTo || '______________________________',
            from: alpineData.previewFrom || '______________________________',
            subject: alpineData.previewSubject || '______________________________',
            body: alpineData.previewBody || defaultHtml
        });
    }

    const rootEl = document.getElementById('townhall-page');
    const alpineData = rootEl ? Alpine.$data(rootEl) : null;

    if (editorEl && hiddenInput && form && window.Quill && window.QuillTableBetter) {
        Quill.register({
            'modules/table-better': QuillTableBetter
        }, true);

        const quill = new Quill('#editor', {
            theme: 'snow',
            placeholder: 'Write the formal communication here...',
            modules: {
                toolbar: [
                    [{ font: [] }, { size: ['small', false, 'large', 'huge'] }],
                    [{ header: [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ script: 'sub' }, { script: 'super' }],
                    [{ color: [] }, { background: [] }],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ indent: '-1' }, { indent: '+1' }],
                    [{ align: [] }],
                    ['blockquote', 'link'],
                    ['table-better'],
                    ['clean']
                ],
                table: false,
                'table-better': {
                    language: 'en_US',
                    menus: ['column', 'row', 'merge', 'table', 'cell', 'wrap', 'copy', 'delete'],
                    toolbarTable: true
                },
                keyboard: {
                    bindings: QuillTableBetter.keyboardBindings
                }
            }
        });

        const oldMessage = {!! json_encode(old('message')) !!};

        function updatePreview() {
            const html = quill.root.innerHTML;
            const hasText = quill.getText().trim().length > 0;
            const hasTable = !!quill.root.querySelector('table');

            hiddenInput.value = html;

            if (alpineData) {
                alpineData.previewBody = (hasText || hasTable) ? html : defaultHtml;
                renderPages(alpineData);
            }
        }

        if (oldMessage) {
            const delta = quill.clipboard.convert({ html: oldMessage });
            quill.setContents(delta);
            hiddenInput.value = oldMessage;

            if (alpineData) {
                alpineData.previewBody = oldMessage;
            }
        } else {
            quill.setText('');
            hiddenInput.value = '';

            if (alpineData) {
                alpineData.previewBody = defaultHtml;
            }
        }

        quill.on('text-change', function () {
            updatePreview();
        });

        form.addEventListener('submit', function () {
            hiddenInput.value = quill.root.innerHTML;
        });
    }

    renderPages(alpineData);

    if (alpineData && window.Alpine) {
        const watchedFields = [
            'previewDate',
            'previewFrom',
            'previewDepartment',
            'previewRecipientLabel',
            'previewRecipientType',
            'previewRecipientUserIds',
            'previewTo',
            'previewPriority',
            'previewSubject',
            'previewCc',
            'previewAdditional',
            'previewExpiry'
        ];

        watchedFields.forEach((key) => {
            Alpine.effect(() => {
                alpineData[key];
                renderPages(alpineData);
            });
        });
    }

    const downloadBtn = document.getElementById('download-preview-pdf');

    if (downloadBtn) {
        downloadBtn.addEventListener('click', function () {
            const element = document.getElementById('memo-preview-pages');
            if (!element) return;

            const subject = document.querySelector('input[name="subject"]')?.value?.trim() || 'townhall-memo';
            const safeFileName = subject
                .replace(/[\\/:*?"<>|]+/g, '')
                .replace(/\s+/g, '-')
                .toLowerCase();

            const options = {
                margin: [0.15, 0.15, 0.15, 0.15],
                filename: `${safeFileName}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, scrollY: 0 },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['css', 'legacy'] }
            };

            html2pdf().set(options).from(element).save();
        });
    }
});
</script>
@endpush

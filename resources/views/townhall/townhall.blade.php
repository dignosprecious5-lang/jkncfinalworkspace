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
                                value="{{ old('communication_date') }}"
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
                                    <option value="employee">Specific Employee</option>
                                </select>
                            </div>

                            <div x-show="previewRecipientType === 'employee'" x-cloak>
                                <select
                                    name="recipient_user_id"
                                    x-model="previewRecipientUserId"
                                    @change="$nextTick(() => syncRecipientFields())"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                                >
                                    <option value="">Select employee</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}" {{ (string) old('recipient_user_id') === (string) $employee->id ? 'selected' : '' }}>
                                            {{ $employee->name }}
                                        </option>
                                    @endforeach
                                </select>
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

    {{-- MAIN CARD --}}
    <div class="bg-white border border-gray-200 rounded-xl min-h-[calc(100vh-7rem)] flex flex-col">
        <div class="px-5 py-4 flex items-center justify-between">
            <h1 class="text-[30px] font-semibold text-gray-800 leading-none">Town Hall</h1>

            <div class="flex items-center gap-2">
                <button class="w-8 h-8 rounded-md border border-gray-200 text-gray-400 hover:bg-gray-50 flex items-center justify-center">
                    <i class="fas fa-bars text-xs"></i>
                </button>

                <button class="w-8 h-8 rounded-md border border-gray-200 text-gray-400 hover:bg-gray-50 flex items-center justify-center">
                    <i class="far fa-rectangle-list text-xs"></i>
                </button>

                @if(Auth::user()->hasPermission('create_townhall'))
                    <button
                        @click="showSlideOver = true"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-4 py-2 rounded-full transition"
                    >
                        <i class="fas fa-plus mr-1"></i> Add Communication
                    </button>
                @endif

                <button class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 hover:bg-blue-200 flex items-center justify-center">
                    <i class="fas fa-ellipsis-v text-xs"></i>
                </button>
            </div>
        </div>

        <div class="px-5 pb-4 flex-1 flex flex-col">
            <div class="border border-gray-200 rounded-md overflow-hidden flex-1 overflow-auto">
                <table class="w-full text-sm text-left border-collapse">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">Ref#</th>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">Date</th>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">Expiry</th>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">Department/Stakeholder</th>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">From</th>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">Subject</th>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">For/To</th>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">Priority</th>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">Approval</th>
                            <th class="px-3 py-3 border-r border-gray-200 font-semibold">Attachment</th>
                            <th class="px-3 py-3 font-semibold w-10"></th>
                        </tr>
                    </thead>

                    <tbody class="bg-white text-gray-700">
                        @forelse($communications as $communication)
                            <tr
                                class="border-t border-gray-200 hover:bg-gray-50 cursor-pointer transition"
                                onclick="window.location='{{ route('townhall.show', $communication->id) }}'"
                            >
                                <td class="px-3 py-3 border-r border-gray-200">{{ $communication->ref_no }}</td>

                                <td class="px-3 py-3 border-r border-gray-200">
                                    {{ $communication->communication_date
                                        ? \Carbon\Carbon::parse($communication->communication_date)->format('M d, Y')
                                        : '—' }}
                                </td>

                                <td class="px-3 py-3 border-r border-gray-200">
                                    @if($communication->expires_at)
                                        <div>{{ \Carbon\Carbon::parse($communication->expires_at)->format('M d, Y') }}</div>
                                        <div class="text-[11px] text-gray-400">
                                            {{ \Carbon\Carbon::parse($communication->expires_at)->format('h:i A') }}
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>

                                <td class="px-3 py-3 border-r border-gray-200">{{ $communication->department_stakeholder }}</td>
                                <td class="px-3 py-3 border-r border-gray-200">{{ $communication->from_name }}</td>
                                <td class="px-3 py-3 border-r border-gray-200">{{ $communication->subject }}</td>
                                <td class="px-3 py-3 border-r border-gray-200">
                                    {{ $communication->recipient_label ?? 'To' }}:
                                    @if(($communication->recipient_type ?? 'all') === 'all')
                                        All Employees
                                    @else
                                        {{ $communication->recipientUser->name ?? $communication->to_for ?? 'Selected Employee' }}
                                    @endif
                                </td>

                                <td class="px-3 py-3 border-r border-gray-200">
                                    @php
                                        $priority = $communication->priority ?? 'Low';
                                        $classes = $priority === 'High'
                                            ? 'bg-red-50 text-red-700'
                                            : 'bg-green-50 text-green-700';
                                    @endphp

                                    <span class="px-2 py-1 text-xs rounded-full font-medium {{ $classes }}">
                                        {{ $priority }}
                                    </span>
                                </td>

                                <td class="px-3 py-3 border-r border-gray-200">
                                    @if($communication->is_archived)
                                        <span class="px-2 py-1 text-xs rounded-full font-medium bg-gray-200 text-gray-700">
                                            Expired
                                        </span>
                                    @else
                                        @php
                                            $approval = $communication->approval_status ?? 'Pending';
                                            $approvalClasses = match($approval) {
                                                'Approved' => 'bg-green-50 text-green-700',
                                                'Rejected' => 'bg-red-50 text-red-700',
                                                'Needs Revision' => 'bg-blue-50 text-blue-700',
                                                default => 'bg-yellow-50 text-yellow-700',
                                            };
                                        @endphp
                                        <span class="px-2 py-1 text-xs rounded-full font-medium {{ $approvalClasses }}">
                                            {{ $approval }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-3 py-3 border-r border-gray-200">
                                    @if($communication->attachment)
                                        <a
                                            href="{{ asset('storage/' . $communication->attachment) }}"
                                            target="_blank"
                                            class="text-blue-600 hover:underline"
                                            onclick="event.stopPropagation()"
                                        >
                                            View
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>

                                <td class="px-3 py-3 text-center text-gray-400">
                                    <button
                                        type="button"
                                        class="hover:text-gray-600"
                                        onclick="event.stopPropagation(); window.location='{{ route('townhall.show', $communication->id) }}'"
                                    >
                                        …
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-3 py-8 text-center text-gray-500">
                                    No Town Hall communications found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2 flex items-center justify-between text-[10px] text-gray-500 px-1">
                <div class="flex items-center gap-6">
                    <span class="flex items-center gap-1">
                        Total Task
                        <span class="text-black font-medium">{{ $communications->total() }}</span>
                    </span>
                    <span class="flex items-center gap-1">
                        Pending
                        <span class="text-yellow-600 font-medium">{{ $communications->where('approval_status', 'Pending')->count() }}</span>
                    </span>
                    <span class="flex items-center gap-1">
                        Approved
                        <span class="text-green-600 font-medium">{{ $communications->where('approval_status', 'Approved')->count() }}</span>
                    </span>
                    <span class="flex items-center gap-1">
                        Needs Revision
                        <span class="text-blue-600 font-medium">{{ $communications->where('approval_status', 'Needs Revision')->count() }}</span>
                    </span>
                    <span class="flex items-center gap-1">
                        Rejected
                        <span class="text-red-600 font-medium">{{ $communications->where('approval_status', 'Rejected')->count() }}</span>
                    </span>
                </div>

                <div class="flex items-center gap-5">
                    <span class="flex items-center gap-1">
                        Records per page
                        <select class="bg-transparent text-gray-600 outline-none">
                            <option>10</option>
                        </select>
                    </span>

                    <span>
                        {{ $communications->firstItem() ?? 0 }} to {{ $communications->lastItem() ?? 0 }}
                    </span>
                </div>
            </div>

            @if(method_exists($communications, 'links'))
                <div class="mt-3">
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
        previewDate: @js(old('communication_date', '')),
        previewFrom: @js(Auth::user()->name),
        previewDepartment: @js(old('department_stakeholder', '')),
        previewRecipientLabel: @js(old('recipient_label', 'To')),
        previewRecipientType: @js(old('recipient_type', 'all')),
        previewRecipientUserId: @js(old('recipient_user_id', '')),
        employeesForRecipient: @js($employees->map(fn($employee) => ['id' => $employee->id, 'name' => $employee->name])->values()),
        previewTo: @js(old('to_for', 'All Employees')),
        previewPriority: @js(old('priority', 'Low')),
        previewSubject: @js(old('subject', '')),
        previewBody: @js(old('message', '<p style="color:#9ca3af;">Write the formal communication here...</p>')),
        previewCc: @js(old('cc', '')),
        previewAdditional: @js(old('additional', '')),
        previewExpiry: @js(old('expires_at', '')),

        syncRecipientFields() {
            if (this.previewRecipientType === 'all') {
                this.previewRecipientUserId = '';
                this.previewTo = 'All Employees';
                return;
            }

            const selected = this.employeesForRecipient.find(employee => {
                return String(employee.id) === String(this.previewRecipientUserId);
            });

            this.previewTo = selected ? selected.name : '';
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
            ref: alpineData.previewRef || 'MEMO-AUTO-INCREMENT',
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
            'previewRecipientUserId',
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

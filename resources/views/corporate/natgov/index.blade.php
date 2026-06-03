@extends('layouts.app')
@section('title', 'NatGov')

@section('content')
@php
    $currentUser = auth()->user()?->name ?? '';
    $selectedAgencies = old('agencies_selected', []);
    $isNatGovAdmin = auth()->user()?->isAdmin() || auth()->user()?->isSuperAdmin();
    $selectedStatus = old('status', 'Pending');
    $selectedStatusOverride = old('status_override', '');
    $searchAgency = isset($searchAgency) ? $searchAgency : '';
    $statusFilter = isset($statusFilter) ? $statusFilter : '';
    $agencyFilter = isset($agencyFilter) ? $agencyFilter : '';
    $renewalPeriodFilter = isset($renewalPeriodFilter) ? $renewalPeriodFilter : '';
    $sortBy = isset($sortBy) ? $sortBy : '';
    $sortDirection = isset($sortDirection) ? $sortDirection : 'asc';
    $statusOptions = isset($statusOptions) ? $statusOptions : [];
    $agencyOptions = isset($agencyOptions) ? $agencyOptions : [];
    $renewalPeriodOptions = isset($renewalPeriodOptions) ? $renewalPeriodOptions : [];
    $companyDefaults = isset($companyDefaults) ? $companyDefaults : [];
    $natgovs = isset($natgovs) ? $natgovs : collect();

    $sortLink = function (string $column) use ($sortBy, $sortDirection, $searchAgency, $statusFilter, $agencyFilter, $renewalPeriodFilter) {
        $nextDirection = $sortBy === $column && $sortDirection === 'asc' ? 'desc' : 'asc';
        return route('natgov', array_filter([
            'search_agency' => $searchAgency,
            'status' => $statusFilter,
            'agency' => $agencyFilter,
            'renewal_period' => $renewalPeriodFilter,
            'sort_by' => $column,
            'sort_direction' => $nextDirection,
        ], fn ($value) => $value !== ''));
    };
@endphp
<div class="w-full px-4 sm:px-6 lg:px-8 mt-4" x-data="{ showAddPanel: {{ $errors->any() ? 'true' : 'false' }} }" @keydown.escape.window="showAddPanel = false">
    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div class="flex items-center gap-3 px-4 py-4">
            <div>
                <div class="text-lg font-semibold">NatGov</div>
                <p class="text-sm text-gray-500">National Government Compliance Registry for registrations, permits, licenses, accreditations, memberships, and renewal monitoring.</p>
            </div>
            <div class="flex-1"></div>
            <button type="button" data-open-add-panel @click="showAddPanel = true" class="h-9 px-4 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Add NatGov
            </button>
        </div>

        <div class="border-t border-gray-100"></div>

        <form method="GET" action="{{ route('natgov') }}" class="px-4 py-3 bg-gray-50 border-b border-gray-100 space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                <input type="text" name="search_agency" value="{{ $searchAgency }}" placeholder="Search Government Agency..." class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                <select name="status" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">All Statuses</option>
                    @foreach ($statusOptions as $option)
                        <option value="{{ $option }}" @selected($statusFilter === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <select name="agency" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">All Government Agencies</option>
                    @foreach ($agencyOptions as $option)
                        <option value="{{ $option }}" @selected($agencyFilter === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <select name="renewal_period" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">All Renewal Periods</option>
                    @foreach ($renewalPeriodOptions as $value => $label)
                        <option value="{{ $value }}" @selected($renewalPeriodFilter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Apply</button>
                <a href="{{ route('natgov') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</a>
            </div>
        </form>

        <div class="p-4">
            <div class="overflow-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700"><a href="{{ $sortLink('client') }}">Company</a></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700"><a href="{{ $sortLink('agency') }}">Government Agency</a></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700"><a href="{{ $sortLink('registration_no') }}">Registration Number</a></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700"><a href="{{ $sortLink('registration_date') }}">Registration Date</a></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700"><a href="{{ $sortLink('renewal_date') }}">Renewal Date</a></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700"><a href="{{ $sortLink('status') }}">Status</a></th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-gray-900">
                        @forelse ($natgovs as $natgov)
                            @php
                                $displayStatus = $natgov->display_status;
                                $statusClasses = match ($displayStatus) {
                                    'Expired' => 'bg-red-50 text-red-700 border-red-200',
                                    'Expiring Soon' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'For Renewal' => 'bg-orange-50 text-orange-700 border-orange-200',
                                    'Pending' => 'bg-slate-50 text-slate-700 border-slate-200',
                                    'Active' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'Approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'Suspended' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                    'Cancelled', 'Revoked' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-slate-50 text-slate-700 border-slate-200',
                                };
                            @endphp
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ route('natgov.preview', $natgov) }}'">
                                <td class="px-4 py-3 font-medium">{{ $natgov->client ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $natgov->agency ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $natgov->registration_no ?: '-' }}</td>
                                <td class="px-4 py-3">{{ optional($natgov->registration_date)->format('M d, Y') ?: '-' }}</td>
                                <td class="px-4 py-3">{{ optional($natgov->renewal_date ?? $natgov->deadline_date)->format('M d, Y') ?: '-' }}</td>
                                <td class="px-4 py-3"><span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">{{ $displayStatus }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">No NatGov entries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-cloak>
        <div x-show="showAddPanel" class="fixed inset-0 bg-black/40 z-40" @click="showAddPanel = false"></div>
        <div
            x-show="showAddPanel"
            data-add-panel
            class="fixed inset-y-0 right-0 w-full max-w-3xl bg-white shadow-2xl z-50 flex min-h-0 flex-col overflow-hidden"
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
                    <div class="text-lg font-semibold">Add NatGov</div>
                    <p class="text-sm text-gray-500">Track national government registrations, permits, and renewal dates with automatic Town Hall compliance deadline syncing.</p>
                </div>
                <div class="flex-1"></div>
                <button class="text-gray-500 hover:text-gray-700" @click="showAddPanel = false" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('natgov.store') }}" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <div class="min-h-0 flex-1 overflow-y-auto p-6 space-y-5">
                    @if ($errors->any())
                        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <div class="font-semibold mb-1">Please fix the following:</div>
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-xs text-amber-700">
                        Saving a renewal date automatically creates or updates a related Town Hall compliance deadline for this NatGov record.
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-gray-600">Company</label>
                            <input type="text" name="client" value="{{ old('client', $companyDefaults['client'] ?? '') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Company">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Registration Number</label>
                            <input type="text" name="registration_no" value="{{ old('registration_no') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Registration / permit / license / authority number">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Registration Date</label>
                            <input type="date" name="registration_date" value="{{ old('registration_date') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Renewal Date</label>
                            <input type="date" name="renewal_date" value="{{ old('renewal_date') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Status</label>
                            <input type="text" name="status" value="{{ $selectedStatus }}" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-600" readonly disabled>
                            <p class="mt-1 text-[11px] text-gray-500">This is auto-generated from the NatGov process and cannot be edited here.</p>
                        </div>
                        @if ($isNatGovAdmin)
                            <div>
                                <label class="text-xs text-gray-600">Admin Status Override</label>
                                <select name="status_override" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                    <option value="">Auto / No Override</option>
                                    @foreach ($statusOptions as $option)
                                        @if (in_array($option, ['Pending', 'Approved', 'Suspended', 'Cancelled', 'Revoked'], true))
                                            <option value="{{ $option }}" @selected($selectedStatusOverride === $option)>{{ $option }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div>
                            <label class="text-xs text-gray-600">Uploaded By</label>
                            <input type="text" value="{{ old('uploaded_by', $currentUser) }}" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-600" readonly disabled>
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Date Uploaded</label>
                            <input type="text" value="{{ old('date_uploaded', now()->toDateString()) }}" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-600" readonly disabled>
                        </div>
                    </div>

                    @if ($isNatGovAdmin)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm font-semibold text-slate-900">Status Options</div>
                            <div class="mt-1 text-xs text-slate-500">The system generates the current status from the NatGov process. Administrators may apply an override when necessary.</div>
                            <div class="mt-4 grid grid-cols-1 gap-3 text-sm">
                                <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                <div class="font-semibold text-slate-900">Active</div>
                                    <div class="text-xs text-slate-500">Used when the renewal date is more than 90 days away and no admin override is applied.</div>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                    <div class="font-semibold text-slate-900">For Renewal</div>
                                    <div class="text-xs text-slate-500">Used when the renewal date is 90 days or less away, but more than 30 days remain, and no admin override is applied.</div>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                    <div class="font-semibold text-slate-900">Expiring Soon</div>
                                    <div class="text-xs text-slate-500">Used when the renewal date is 30 days or less away and no admin override is applied.</div>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                    <div class="font-semibold text-slate-900">Expired</div>
                                    <div class="text-xs text-slate-500">Used when the renewal date has already passed and no admin override is applied.</div>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                    <div class="font-semibold text-slate-900">Pending</div>
                                    <div class="text-xs text-slate-500">Used when an admin override is set to Pending, or when the record has no renewal date yet and is still awaiting processing.</div>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                    <div class="font-semibold text-slate-900">Approved</div>
                                    <div class="text-xs text-slate-500">Used only when an admin override is set to Approved.</div>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                    <div class="font-semibold text-slate-900">Suspended</div>
                                    <div class="text-xs text-slate-500">Used only when an admin override is set to Suspended.</div>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                    <div class="font-semibold text-slate-900">Cancelled</div>
                                    <div class="text-xs text-slate-500">Used only when an admin override is set to Cancelled.</div>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                    <div class="font-semibold text-slate-900">Revoked</div>
                                    <div class="text-xs text-slate-500">Used only when an admin override is set to Revoked.</div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="rounded-xl border border-gray-200 p-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Government Agency Name</h3>
                            <p class="mt-1 text-xs text-gray-500">Select one or more government agencies for this registration. Use Other to enter a new agency without changing the system.</p>
                        </div>
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-80 overflow-y-auto pr-1">
                            @foreach ($agencyOptions as $option)
                                <label class="flex items-start gap-3 rounded-lg border border-gray-200 px-3 py-3 text-sm text-gray-700">
                                    <input type="checkbox" name="agencies_selected[]" value="{{ $option }}" @checked(in_array($option, $selectedAgencies, true)) @if($option === 'Other') data-other-toggle="agency" @endif class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div id="agencyOtherWrapper" class="mt-4 {{ in_array('Other', $selectedAgencies, true) ? '' : 'hidden' }}">
                            <label class="text-xs text-gray-600">Other Agency</label>
                            <input type="text" name="agency_other" value="{{ old('agency_other') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Enter another government agency">
                        </div>
                    </div>

                    <input type="hidden" name="uploaded_by" value="{{ old('uploaded_by', $currentUser) }}">
                    <input type="hidden" name="date_uploaded" value="{{ old('date_uploaded', now()->toDateString()) }}">

                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="text-xs text-gray-600">Draft Documents</label>
                            <p class="mt-1 text-[11px] text-gray-500">Allow uploading multiple PDF files. Examples: Draft applications, draft submissions, supporting documents, renewal applications.</p>
                            <input type="file" name="document_paths[]" accept="application/pdf" multiple class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-slate-700 file:text-white hover:file:bg-slate-800">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Approved Documents</label>
                            <p class="mt-1 text-[11px] text-gray-500">Allow uploading multiple PDF files. Examples: Certificates, permits, licenses, authorities, accreditations, government approvals.</p>
                            <input type="file" name="approved_document_paths[]" accept="application/pdf" multiple class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-600 file:text-white hover:file:bg-emerald-700">
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 flex items-center gap-2">
                    <button class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-900 text-sm font-medium rounded-lg" @click="showAddPanel = false" type="button">
                        Cancel
                    </button>
                    <div class="flex-1"></div>
                    <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg" type="submit">
                        Save NatGov
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const panel = document.querySelector('[data-add-panel]');
        if (!panel) return;

        const checkbox = panel.querySelector('[data-other-toggle="agency"]');
        const wrapper = panel.querySelector('#agencyOtherWrapper');
        if (!checkbox || !wrapper) return;

        const update = () => {
            wrapper.classList.toggle('hidden', !checkbox.checked);
        };

        checkbox.addEventListener('change', update);
        update();
    })();
</script>
@endpush

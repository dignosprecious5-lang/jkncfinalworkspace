@extends('layouts.app')
@section('title', 'BIR & Tax')

@section('content')
@php
    $currentUser = auth()->user()?->name ?? '';
    $selectedTaxTypes = old('tax_types_selected', []);
    $selectedFormTypes = old('form_types_selected', []);
    $selectedStatus = old('status', 'Pending');
    $selectedFrequency = old('filing_frequency', 'Monthly');
@endphp
<div class="w-full px-4 sm:px-6 lg:px-8 mt-4" x-data="{ showAddPanel: {{ $errors->any() ? 'true' : 'false' }} }" @keydown.escape.window="showAddPanel = false">
    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div class="flex items-center gap-3 px-4 py-4">
            <div>
                <div class="text-lg font-semibold">BIR & Tax</div>
                <p class="text-sm text-gray-500">Track filing deadlines by tax type and form type, with Town Hall memo sync for every saved due date.</p>
            </div>
            <div class="flex-1"></div>
            <button type="button" data-open-add-panel @click="showAddPanel = true" class="h-9 px-4 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Add BIR & Tax
            </button>
        </div>

        <div class="border-t border-gray-100"></div>

        <form method="GET" action="{{ route('bir-tax') }}" class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <input
                    type="text"
                    name="search_tax_types"
                    value="{{ $searchTaxTypes }}"
                    placeholder="Search Tax Type/s..."
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                >
                <input
                    type="text"
                    name="search_form_type"
                    value="{{ $searchFormType }}"
                    placeholder="Search Form Type..."
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                >
            </div>
            <div class="mt-3 flex items-center gap-2">
                <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Search</button>
                <a href="{{ route('bir-tax') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</a>
            </div>
        </form>

        <div class="p-4">
            <div class="overflow-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">TIN</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Taxpayer</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">RDO</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Registered Address</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Tax Type/s</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Form Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Tax Due</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Filing Frequency</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Due Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-gray-900">
                        @forelse ($taxes as $tax)
                            @php
                                $displayStatus = $tax->display_status;
                                $statusClasses = match ($displayStatus) {
                                    'Overdue' => 'bg-red-50 text-red-700 border-red-200',
                                    'Due Today' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'Upcoming' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'Filed', 'Completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    default => 'bg-slate-50 text-slate-700 border-slate-200',
                                };
                            @endphp
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ route('bir-tax.preview', $tax) }}'">
                                <td class="px-4 py-3 font-medium">{{ $tax->tin ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $tax->tax_payer ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $tax->rdo ?: $tax->registering_office ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $tax->registered_address ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $tax->tax_types ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $tax->form_type ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $tax->tax_due !== null ? number_format((float) $tax->tax_due, 2) : '-' }}</td>
                                <td class="px-4 py-3">{{ $tax->filing_frequency ?: '-' }}</td>
                                <td class="px-4 py-3">{{ optional($tax->due_date)->format('M d, Y') ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">{{ $displayStatus }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-6 text-center text-sm text-gray-500">No BIR & Tax entries found.</td>
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
                    <div class="text-lg font-semibold">Add BIR & Tax</div>
                    <p class="text-sm text-gray-500">Save tax deadlines by filing requirement, then let the system push the due-date memo to Town Hall automatically.</p>
                </div>
                <div class="flex-1"></div>
                <button class="text-gray-500 hover:text-gray-700" @click="showAddPanel = false" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('bir-tax.store') }}" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col">
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
                        Saving a due date automatically creates or updates a related Town Hall deadline memo for this BIR & Tax record.
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-gray-600">TIN</label>
                            <input type="text" name="tin" value="{{ old('tin', $companyDefaults['tin'] ?? '') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="TIN">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Taxpayer</label>
                            <input type="text" name="tax_payer" value="{{ old('tax_payer', $companyDefaults['tax_payer'] ?? '') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Taxpayer">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">RDO</label>
                            <input type="text" name="rdo" value="{{ old('rdo') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Revenue District Office">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Registered Address</label>
                            <input type="text" name="registered_address" value="{{ old('registered_address', $companyDefaults['registered_address'] ?? '') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Registered address">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Tax Due</label>
                            <input type="number" step="0.01" min="0" name="tax_due" value="{{ old('tax_due') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="0.00">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Filing Frequency</label>
                            <select name="filing_frequency" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                @foreach ($filingFrequencyOptions as $option)
                                    <option value="{{ $option }}" @selected($selectedFrequency === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Due Date</label>
                            <input type="date" name="due_date" value="{{ old('due_date') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Status</label>
                            <select name="status" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                @foreach ($statusOptions as $option)
                                    <option value="{{ $option }}" @selected($selectedStatus === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Tax Type/s</h3>
                                <p class="mt-1 text-xs text-gray-500">Select all BIR tax types covered by this filing. Use Others when the filing needs a custom tax type.</p>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($taxTypeOptions as $option)
                                <label class="flex items-start gap-3 rounded-lg border border-gray-200 px-3 py-3 text-sm text-gray-700">
                                    <input type="checkbox" name="tax_types_selected[]" value="{{ $option }}" @checked(in_array($option, $selectedTaxTypes, true)) @if($option === 'Other') data-other-toggle="tax-types" @endif class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div id="taxTypesOtherWrapper" class="mt-4 {{ in_array('Other', $selectedTaxTypes, true) ? '' : 'hidden' }}">
                            <label class="text-xs text-gray-600">Others</label>
                            <input type="text" name="tax_types_other" value="{{ old('tax_types_other') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Add another tax type">
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Form Type</h3>
                                <p class="mt-1 text-xs text-gray-500">Select one or more BIR form types tied to this deadline. Use Others for custom or uncommon form types.</p>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($formTypeOptions as $option)
                                <label class="flex items-start gap-3 rounded-lg border border-gray-200 px-3 py-3 text-sm text-gray-700">
                                    <input type="checkbox" name="form_types_selected[]" value="{{ $option }}" @checked(in_array($option, $selectedFormTypes, true)) @if($option === 'Other') data-other-toggle="form-types" @endif class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div id="formTypesOtherWrapper" class="mt-4 {{ in_array('Other', $selectedFormTypes, true) ? '' : 'hidden' }}">
                            <label class="text-xs text-gray-600">Others</label>
                            <input type="text" name="form_type_other" value="{{ old('form_type_other') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Add another form type">
                        </div>
                    </div>

                    <input type="hidden" name="uploaded_by" value="{{ old('uploaded_by', $currentUser) }}">
                    <input type="hidden" name="date_uploaded" value="{{ old('date_uploaded', now()->toDateString()) }}">

                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="text-xs text-gray-600">Upload Draft BIR & Tax PDFs</label>
                            <input type="file" name="document_paths[]" accept="application/pdf" multiple class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-slate-700 file:text-white hover:file:bg-slate-800">
                            <div class="mt-1 text-[11px] text-gray-500">Upload draft filing files here. The newest draft becomes the default draft preview.</div>
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Upload Approved BIR & Tax PDFs</label>
                            <input type="file" name="approved_document_paths[]" accept="application/pdf" multiple class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-600 file:text-white hover:file:bg-emerald-700">
                            <div class="mt-1 text-[11px] text-gray-500">Upload approved filing copies when they are available.</div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 flex items-center gap-2">
                    <button class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-900 text-sm font-medium rounded-lg" @click="showAddPanel = false" type="button">
                        Cancel
                    </button>
                    <div class="flex-1"></div>
                    <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg" type="submit">
                        Save BIR & Tax
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

        const syncOtherToggle = (selector, wrapperId) => {
            const checkbox = panel.querySelector(selector);
            const wrapper = panel.querySelector(wrapperId);
            if (!checkbox || !wrapper) return;

            const update = () => {
                wrapper.classList.toggle('hidden', !checkbox.checked);
            };

            checkbox.addEventListener('change', update);
            update();
        };

        syncOtherToggle('[data-other-toggle="tax-types"]', '#taxTypesOtherWrapper');
        syncOtherToggle('[data-other-toggle="form-types"]', '#formTypesOtherWrapper');
    })();
</script>
@endpush

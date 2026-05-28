@extends('layouts.app')
@section('title', 'NatGov')

@section('content')
@php
    $currentUser = auth()->user()?->name ?? '';
@endphp
<div class="w-full px-4 sm:px-6 lg:px-8 mt-4" x-data="{ showAddPanel: false }" @keydown.escape.window="showAddPanel = false">

    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-3 px-4 py-4">
            <div class="text-lg font-semibold">NatGov</div>
            <div class="flex-1"></div>
            <button type="button" data-open-add-panel @click="showAddPanel = true" class="h-9 px-4 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Add NatGov
            </button>
        </div>

        <div class="border-t border-gray-100"></div>

        {{-- SEARCH SECTION --}}
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <div class="grid grid-cols-1 gap-3">
                <input type="text" placeholder="Search agency..." class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </div>
        </div>

        {{-- TABLE --}}
        <div class="p-4">
            <div class="overflow-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Company</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">TIN</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Govt Body/Agency</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Registration Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Reg. Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Deadline</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Registration No.</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Status</th>
                            
                        </tr>
                    </thead>
                    <tbody class="text-sm text-gray-900">
    @forelse ($natgovs as $natgov)
        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ route('natgov.preview', $natgov) }}'">
            <td class="px-4 py-3 font-medium">{{ $natgov->client }}</td>
            <td class="px-4 py-3">{{ $natgov->tin }}</td>
            <td class="px-4 py-3">{{ $natgov->agency }}</td>
            <td class="px-4 py-3">{{ $natgov->registration_status }}</td>
            <td class="px-4 py-3">{{ optional($natgov->registration_date)->format('M d, Y') }}</td>
            <td class="px-4 py-3">{{ optional($natgov->deadline_date)->format('M d, Y') }}</td>
            <td class="px-4 py-3">{{ $natgov->registration_no }}</td>
            <td class="px-4 py-3">{{ $natgov->display_status }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500">No NatGov entries found.</td>
        </tr>
    @endforelse
</tbody>
                </table>
            </div>
    {{-- ADD NATGOV SLIDER --}}
    <div x-cloak>
    <div x-show="showAddPanel" class="fixed inset-0 bg-black/40 z-40" @click="showAddPanel = false"></div>
    <div x-show="showAddPanel" data-add-panel
        class="fixed inset-y-0 right-0 w-full max-w-xl bg-white shadow-2xl z-50 flex flex-col"
        x-transition:enter="transform transition ease-in-out duration-200"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in-out duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        @click.stop
    >
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
            <div class="text-lg font-semibold">Add NatGov</div>
            <div class="flex-1"></div>
            <button class="text-gray-500 hover:text-gray-700" @click="showAddPanel = false" type="button">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('natgov.store') }}" enctype="multipart/form-data" class="flex flex-1 flex-col">
            @csrf
            <div class="p-6 overflow-y-auto space-y-4">
<div class="rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-xs text-amber-700">
                    Saving a deadline automatically creates or updates a Town Hall deadline memo for this NatGov record.
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-600">Company</label>
                        <input type="text" name="client" value="{{ old('client') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Company">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">TIN</label>
                        <input type="text" name="tin" value="{{ old('tin') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="TIN">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Govt Body/Agency</label>
                        <select name="agency" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            <option value="">Select agency</option>
                            @foreach (['SSS', 'Pag-IBIG', 'PHILHEALTH', 'Dole'] as $agencyOption)
                                <option value="{{ $agencyOption }}" @selected(old('agency') === $agencyOption)>{{ $agencyOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Registration Status</label>
                        <input type="text" name="registration_status" value="{{ old('registration_status') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Status">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Reg. Date</label>
                        <input type="date" name="registration_date" value="{{ old('registration_date') }}" data-default-field="today" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Deadline Date</label>
                        <input type="date" name="deadline_date" value="{{ old('deadline_date') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Registration No.</label>
                        <input type="text" name="registration_no" value="{{ old('registration_no') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="REG-001">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Status</label>
                        <input type="text" name="status" value="{{ old('status') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Active">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Uploaded By</label>
                        <input type="text" name="uploaded_by" value="{{ old('uploaded_by', $currentUser) }}" data-default-field="current_user" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Uploader">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Date Uploaded</label>
                        <input type="date" name="date_uploaded" value="{{ old('date_uploaded') }}" data-default-field="today" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-600">Upload Draft NatGov PDFs</label>
                        <input type="file" name="document_paths[]" accept="application/pdf" multiple class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-slate-700 file:text-white hover:file:bg-slate-800">
                        <div class="mt-1 text-[11px] text-gray-500">You can upload multiple draft PDFs. The newest file will be the one shown in the draft preview.</div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-600">Upload Approved NatGov PDFs</label>
                        <input type="file" name="approved_document_paths[]" accept="application/pdf" multiple class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-600 file:text-white hover:file:bg-emerald-700">
                        <div class="mt-1 text-[11px] text-gray-500">You can upload multiple approved PDFs. The newest file will be the one shown in the approved preview.</div>
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

<script>
    (function () {
        const container = document.currentScript.closest('body');
        const addButton = container.querySelector('[data-open-add-panel]');
        const addPanel = container.querySelector('[data-add-panel]');
        const today = new Date().toISOString().split('T')[0];
        const currentUser = @js($currentUser);

        const applyDefaults = () => {
            if (!addPanel) return;

            addPanel.querySelectorAll('[data-default-field="today"]').forEach((field) => {
                if (!field.value) {
                    field.value = today;
                }
            });

            addPanel.querySelectorAll('[data-default-field="current_user"]').forEach((field) => {
                if (!field.value) {
                    field.value = currentUser;
                }
            });
};

        if (addButton) {
            addButton.addEventListener('click', applyDefaults);
        }
    })();
</script>


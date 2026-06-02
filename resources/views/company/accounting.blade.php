@extends('layouts.app')
@section('title', 'Company Accounting')

@section('content')
@php
    $companyTinValue = old('tin', $companyTin ?? ($company->tin_no ?? $company->tin ?? ''));
    $currentUserNameValue = old('uploaded_by', old('user', $currentUserName ?? (auth()->user()->name ?? auth()->user()->email ?? 'System User')));
@endphp
<div class="w-full px-4 sm:px-6 lg:px-8 mt-4 pb-8">
    <div class="bg-white border border-gray-100 rounded-md overflow-hidden">
        @include('company.partials.company-header', ['company' => $company])

        <section class="bg-gray-50 p-4 min-h-[760px]">
            <div id="companyAccountingApp" class="bg-white rounded-xl border border-gray-200" x-data="{ showSlideOver: false }">
                <div x-show="showSlideOver" class="fixed inset-0 z-50 overflow-hidden" x-cloak>
                    <div class="absolute inset-0 overflow-hidden">
                        <div x-show="showSlideOver" @click="showSlideOver = false" class="absolute inset-0 bg-gray-900 bg-opacity-50 transition-opacity"></div>
                        <div class="absolute inset-y-0 right-0 max-w-full flex">
                            <div x-show="showSlideOver" class="w-screen max-w-sm bg-white shadow-2xl flex flex-col h-full"
                                 x-transition:enter="transform transition ease-in-out duration-300"
                                 x-transition:enter-start="translate-x-full"
                                 x-transition:enter-end="translate-x-0"
                                 x-transition:leave="transform transition ease-in-out duration-300"
                                 x-transition:leave-start="translate-x-0"
                                 x-transition:leave-end="translate-x-full">
                                <div class="p-6 border-b flex items-center justify-between">
                                    <div>
                                        <h2 id="accountingDrawerTitle" class="text-lg font-bold text-gray-800">Add Entry</h2>
                                        <p class="mt-1 text-sm text-gray-500">Records added here are automatically associated with {{ $company->company_name }}.</p>
                                    </div>
                                    <button @click="showSlideOver = false" type="button" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>

                                <form id="accountingForm" method="POST" action="{{ route('company.accounting.store', $company->id) }}" class="flex min-h-0 flex-1 flex-col">
                                    @csrf
                                    <input type="hidden" id="accountingFormMethod" name="_method" value="POST">

                                    <div class="p-6 flex-1 overflow-y-auto space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium mb-1">Category *</label>
                                            <select id="accountingCategoryInput" name="category" class="w-full border rounded-md p-2 bg-white">
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category }}" @selected($selectedCategory === $category)>{{ $category }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-1">Client *</label>
                                            <input type="text" value="{{ $company->company_name }}" class="w-full border rounded-md p-2 bg-gray-100 text-gray-600" readonly>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-1">TIN *</label>
                                            <input id="accountingTinInput" name="tin" type="text" value="{{ $companyTinValue }}" placeholder="Enter TIN" class="w-full border rounded-md p-2 bg-gray-50" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-1">Date Uploaded *</label>
                                            <input id="accountingDateInput" name="date_uploaded" type="date" class="w-full border rounded-md p-2" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-1">Uploaded By *</label>
                                            <input id="accountingUploadedByInput" name="uploaded_by" type="text" value="{{ $currentUserNameValue }}" class="w-full border rounded-md p-2 bg-gray-100 text-gray-600" readonly required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-1">Status *</label>
                                            <select id="accountingStatusInput" name="status" class="w-full border rounded-md p-2 bg-white">
                                                @foreach (['Open', 'Completed', 'Overdue'] as $status)
                                                    <option value="{{ $status }}">{{ $status }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-1">Upload Files *</label>
                                            <div class="border-2 border-dashed p-6 text-center text-gray-400 rounded-lg text-sm">Drag & drop or <span class="text-blue-600 underline cursor-pointer">browse</span></div>
                                        </div>
                                    </div>

                                    <div class="p-6 border-t flex gap-3">
                                        <button @click="showSlideOver = false" type="button" class="flex-1 py-2 border rounded-md font-medium text-gray-600">Cancel</button>
                                        <button id="accountingSubmitButton" type="submit" class="flex-1 py-2 bg-blue-600 text-white rounded-md font-medium">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <div class="flex items-center gap-1 text-sm">
                        @foreach ($categories as $category)
                            <a href="{{ route('company.accounting', ['company' => $company->id, 'category' => $category]) }}" class="px-6 py-2 {{ $selectedCategory === $category ? 'bg-white border border-gray-300 border-b-0 rounded-t-md font-medium text-gray-800 -mb-[13px] z-10' : 'text-gray-600 hover:bg-gray-50 rounded-t-md transition' }}">
                                {{ $category }}
                            </a>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="flex">
                            <button @click="showSlideOver = true; resetAccountingForm()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-l-md text-sm font-medium transition">
                                + Add
                            </button>
                            <button class="bg-blue-600 hover:bg-blue-700 text-white px-2 rounded-r-md border-l border-blue-500 transition">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-4">
                    @if (session('accounting_success'))
                        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                            {{ session('accounting_success') }}
                        </div>
                    @endif

                    <div class="border rounded-md overflow-hidden">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-500 border-b">
                                <tr>
                                    <th class="p-3 font-semibold">Date Uploaded</th>
                                    <th class="p-3 font-semibold">Uploaded By</th>
                                    <th class="p-3 font-semibold">TIN</th>
                                    <th class="p-3 font-semibold text-right">Status</th>
                                    <th class="p-3 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @forelse ($records as $record)
                                    @php
                                        $statusClass = match ($record['status']) {
                                            'Completed' => 'text-green-500',
                                            'Overdue' => 'text-red-500',
                                            default => 'text-yellow-500',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="p-3">{{ \Illuminate\Support\Carbon::parse($record['date_uploaded'])->format('F d, Y') }}</td>
                                        <td class="p-3">{{ $record['uploaded_by'] }}</td>
                                        <td class="p-3">{{ $record['tin'] }}</td>
                                        <td class="p-3 text-right"><span class="font-semibold {{ $statusClass }}">{{ $record['status'] }}</span></td>
                                        <td class="p-3">
                                            <div class="flex items-center justify-end gap-2">
                                                <button type="button" class="inline-flex h-8 items-center rounded-full border border-gray-200 px-3 text-xs font-medium text-gray-700 hover:bg-gray-50" onclick="editAccountingRecord(@js($record))">Edit</button>
                                                <form method="POST" action="{{ route('company.accounting.destroy', [$company->id, $record['id']]) }}" onsubmit="return confirm('Delete this accounting entry?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="category" value="{{ $selectedCategory }}">
                                                    <button type="submit" class="inline-flex h-8 items-center rounded-full border border-red-200 px-3 text-xs font-medium text-red-600 hover:bg-red-50">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-10 text-center text-gray-400 italic">No accounting records for this company yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center justify-between text-[11px] text-gray-500 px-1">
                        <div class="flex gap-6">
                            <span class="flex items-center gap-1.5">Total Task <span class="w-2 h-2 rounded-full bg-blue-800"></span> {{ $stats['total'] }}</span>
                            <span class="flex items-center gap-1.5">Open Task <span class="w-2 h-2 rounded-full bg-yellow-400"></span> {{ $stats['open'] }}</span>
                            <span class="flex items-center gap-1.5">Completed <span class="w-2 h-2 rounded-full bg-green-500"></span> {{ $stats['completed'] }}</span>
                            <span class="flex items-center gap-1.5">Overdue <span class="w-2 h-2 rounded-full bg-red-500"></span> {{ $stats['overdue'] }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span>Records per page <select class="bg-transparent border-none outline-none cursor-pointer font-semibold text-gray-700"><option>10</option></select></span>
                            <span>{{ $records->count() > 0 ? '1 to ' . $records->count() : '0 to 0' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
    function resetAccountingForm() {
        const form = document.getElementById('accountingForm');
        form.reset();
        form.action = @json(route('company.accounting.store', $company->id));
        document.getElementById('accountingFormMethod').value = 'POST';
        document.getElementById('accountingDrawerTitle').textContent = 'Add Entry';
        document.getElementById('accountingSubmitButton').textContent = 'Save';
        document.getElementById('accountingCategoryInput').value = @json($selectedCategory);
        document.getElementById('accountingTinInput').value = @json($companyTinValue);
        document.getElementById('accountingUploadedByInput').value = @json($currentUserNameValue);
    }

    function editAccountingRecord(record) {
        const container = document.getElementById('companyAccountingApp');
        resetAccountingForm();
        document.getElementById('accountingForm').action = @json(route('company.accounting.update', [$company->id, '__RECORD__'])).replace('__RECORD__', record.id);
        document.getElementById('accountingFormMethod').value = 'PUT';
        document.getElementById('accountingDrawerTitle').textContent = 'Edit Entry';
        document.getElementById('accountingSubmitButton').textContent = 'Update';
        document.getElementById('accountingCategoryInput').value = record.category;
        document.getElementById('accountingTinInput').value = record.tin;
        document.getElementById('accountingDateInput').value = record.date_uploaded;
        document.getElementById('accountingUploadedByInput').value = @json($currentUserNameValue);
        document.getElementById('accountingStatusInput').value = record.status;
        if (container && container.__x) {
            container.__x.$data.showSlideOver = true;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        @if ($errors->any())
            document.getElementById('companyAccountingApp')?.__x?.$data && (document.getElementById('companyAccountingApp').__x.$data.showSlideOver = true);
        @endif
    });
</script>
@endsection

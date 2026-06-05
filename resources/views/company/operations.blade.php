@extends('layouts.app')
@section('title', 'Company Operations')

@section('content')
@php
    $companyTinValue = old('tin', $companyTin ?? ($company->tin_no ?? $company->tin ?? ''));
    $currentUserNameValue = old('uploaded_by', old('user', $currentUserName ?? (auth()->user()->name ?? auth()->user()->email ?? 'System User')));
    $companyRegistrationNumber = $company->registration_number ?? $company->company_reg_no ?? '';
    $companyPrincipalAddress = $company->principal_address ?? $company->address ?? '';
@endphp
<div class="w-full px-4 sm:px-6 lg:px-8 mt-4 pb-8">
    <div class="bg-white border border-gray-100 rounded-md overflow-hidden">
        @include('company.partials.company-header', ['company' => $company])

        <section class="bg-gray-50 p-4 min-h-[760px]">
            <div id="companyOperationsApp" class="bg-white rounded-xl border border-gray-200" x-data="{ showSlideOver: false }">
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
                                        <h2 id="operationsDrawerTitle" class="text-lg font-bold text-gray-800">Add Operations Entry</h2>
                                        <p class="mt-1 text-sm text-gray-500">Records added here are automatically associated with {{ $company->company_name }}.</p>
                                    </div>
                                    <button @click="showSlideOver = false" type="button" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>

                                <form id="operationsForm" method="POST" action="{{ route('company.operations.store', $company->id) }}" class="flex min-h-0 flex-1 flex-col">
                                    @csrf
                                    <input type="hidden" id="operationsFormMethod" name="_method" value="POST">

                                    <div class="p-6 flex-1 overflow-y-auto space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium mb-1">Client *</label>
                                            <input type="text" value="{{ $company->company_name }}" class="w-full border rounded-md p-2 bg-gray-100 text-gray-600" readonly>
                                        </div>
                                        <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm">
                                            <div class="font-semibold text-gray-900">{{ $company->company_name }}</div>
                                            <div class="mt-2 space-y-2 text-xs text-gray-700">
                                                <div class="flex justify-between gap-3">
                                                    <span class="font-semibold text-blue-700">Registration Number</span>
                                                    <span class="text-right text-gray-900">{{ $companyRegistrationNumber ?: 'No registration number found' }}</span>
                                                </div>
                                                <div>
                                                    <div class="font-semibold text-blue-700">Principal Address</div>
                                                    <div class="mt-1 leading-relaxed text-gray-900">{{ $companyPrincipalAddress ?: 'No principal address found' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-1">TIN *</label>
                                            <input id="operationsTinInput" name="tin" type="text" value="{{ $companyTinValue }}" placeholder="Enter TIN" class="w-full border rounded-md p-2 bg-gray-50" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-1">Date Uploaded *</label>
                                            <input id="operationsDateInput" name="date_uploaded" type="date" class="w-full border rounded-md p-2" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-1">Uploaded By *</label>
                                            <input id="operationsUploadedByInput" name="uploaded_by" type="text" value="{{ $currentUserNameValue }}" class="w-full border rounded-md p-2 bg-gray-100 text-gray-600" readonly required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-1">Record Name *</label>
                                            <input id="operationsRecordNameInput" name="record_name" type="text" placeholder="Enter record name" class="w-full border rounded-md p-2" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-1">Supporting Docs *</label>
                                            <input id="operationsDocsInput" name="supporting_docs" type="text" placeholder="Enter supporting document" class="w-full border rounded-md p-2" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-1">Status *</label>
                                            <select id="operationsStatusInput" name="status" class="w-full border rounded-md p-2 bg-white">
                                                @foreach (['Open', 'Completed', 'Overdue'] as $status)
                                                    <option value="{{ $status }}">{{ $status }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="p-6 border-t flex gap-3">
                                        <button @click="showSlideOver = false" type="button" class="flex-1 py-2 border rounded-md font-medium text-gray-600">Cancel</button>
                                        <button id="operationsSubmitButton" type="submit" class="flex-1 py-2 bg-blue-600 text-white rounded-md font-medium">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <div class="text-sm font-medium text-gray-700">Operations</div>

                    <div class="flex items-center gap-3">
                        <div class="flex items-center border rounded-md overflow-hidden bg-gray-50">
                            <button class="p-2 hover:bg-white transition border-r"><i class="fas fa-bars text-gray-400"></i></button>
                            <button class="p-2 hover:bg-white transition"><i class="fas fa-th-large text-gray-400"></i></button>
                        </div>

                        <div class="flex">
                            <button @click="showSlideOver = true; resetOperationsForm()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-l-md text-sm font-medium transition">
                                + Add
                            </button>
                            <button class="bg-blue-600 hover:bg-blue-700 text-white px-2 rounded-r-md border-l border-blue-500 transition">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                        </div>

                        <button class="p-2 text-gray-400 hover:text-gray-600 transition">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </div>
                </div>

                <div class="p-4">
                    @if (session('operations_success'))
                        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                            {{ session('operations_success') }}
                        </div>
                    @endif

                    <div class="border rounded-md overflow-hidden">
                        <table class="w-full text-[13px] text-left">
                            <thead class="bg-gray-50 text-gray-500 border-b">
                                <tr>
                                    <th class="p-3 font-semibold border-r">Date Uploaded</th>
                                    <th class="p-3 font-semibold border-r">Uploaded By</th>
                                    <th class="p-3 font-semibold border-r">TIN</th>
                                    <th class="p-3 font-semibold border-r">Record Name</th>
                                    <th class="p-3 font-semibold border-r">Supporting Docs</th>
                                    <th class="p-3 font-semibold border-r">Status</th>
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
                                        <td class="p-3 border-r">{{ \Illuminate\Support\Carbon::parse($record['date_uploaded'])->format('F d, Y') }}</td>
                                        <td class="p-3 border-r">{{ $record['uploaded_by'] }}</td>
                                        <td class="p-3 border-r">{{ $record['tin'] }}</td>
                                        <td class="p-3 border-r">{{ $record['record_name'] }}</td>
                                        <td class="p-3 border-r">{{ $record['supporting_docs'] }}</td>
                                        <td class="p-3 border-r"><span class="font-semibold {{ $statusClass }}">{{ $record['status'] }}</span></td>
                                        <td class="p-3">
                                            <div class="flex items-center justify-end gap-2">
                                                <button type="button" class="inline-flex h-8 items-center rounded-full border border-gray-200 px-3 text-xs font-medium text-gray-700 hover:bg-gray-50" onclick="editOperationsRecord(@js($record))">Edit</button>
                                                <form method="POST" action="{{ route('company.operations.destroy', [$company->id, $record['id']]) }}" onsubmit="return confirm('Delete this operations entry?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex h-8 items-center rounded-full border border-red-200 px-3 text-xs font-medium text-red-600 hover:bg-red-50">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="h-10">
                                        <td colspan="7" class="p-10 text-center text-gray-400 italic">No operations records for this company yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center justify-between text-[11px] text-gray-500 px-1">
                        <div class="flex gap-6">
                            <span class="flex items-center gap-1.5">Total Task <span class="w-2 h-2 rounded-full bg-blue-900"></span> {{ $stats['total'] }}</span>
                            <span class="flex items-center gap-1.5">Open Task <span class="w-2 h-2 rounded-full bg-yellow-400"></span> {{ $stats['open'] }}</span>
                            <span class="flex items-center gap-1.5">Completed <span class="w-2 h-2 rounded-full bg-green-500"></span> {{ $stats['completed'] }}</span>
                            <span class="flex items-center gap-1.5">Overdue <span class="w-2 h-2 rounded-full bg-red-500"></span> {{ $stats['overdue'] }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span>Records per page
                                <select class="bg-transparent border-none outline-none cursor-pointer font-semibold text-gray-700">
                                    <option>10</option>
                                </select>
                            </span>
                            <span>{{ $records->count() > 0 ? '1 to ' . $records->count() : '0 to 0' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
    function resetOperationsForm() {
        const form = document.getElementById('operationsForm');
        form.reset();
        form.action = @json(route('company.operations.store', $company->id));
        document.getElementById('operationsFormMethod').value = 'POST';
        document.getElementById('operationsDrawerTitle').textContent = 'Add Operations Entry';
        document.getElementById('operationsSubmitButton').textContent = 'Save';
        document.getElementById('operationsTinInput').value = @json($companyTinValue);
        document.getElementById('operationsUploadedByInput').value = @json($currentUserNameValue);
    }

    function editOperationsRecord(record) {
        const container = document.getElementById('companyOperationsApp');
        resetOperationsForm();
        document.getElementById('operationsForm').action = @json(route('company.operations.update', [$company->id, '__RECORD__'])).replace('__RECORD__', record.id);
        document.getElementById('operationsFormMethod').value = 'PUT';
        document.getElementById('operationsDrawerTitle').textContent = 'Edit Operations Entry';
        document.getElementById('operationsSubmitButton').textContent = 'Update';
        document.getElementById('operationsTinInput').value = record.tin;
        document.getElementById('operationsDateInput').value = record.date_uploaded;
        document.getElementById('operationsUploadedByInput').value = @json($currentUserNameValue);
        document.getElementById('operationsRecordNameInput').value = record.record_name;
        document.getElementById('operationsDocsInput').value = record.supporting_docs;
        document.getElementById('operationsStatusInput').value = record.status;
        if (container && container.__x) {
            container.__x.$data.showSlideOver = true;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        @if ($errors->any())
            document.getElementById('companyOperationsApp')?.__x?.$data && (document.getElementById('companyOperationsApp').__x.$data.showSlideOver = true);
        @endif
    });
</script>
@endsection

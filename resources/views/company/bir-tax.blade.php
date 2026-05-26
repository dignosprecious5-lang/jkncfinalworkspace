@extends('layouts.app')
@section('title', 'Company BIR & Tax')

@section('content')
@php
    $companyTinValue = old('tin', $companyTin ?? ($company->tin_no ?? $company->tin ?? ''));
    $currentUserNameValue = old('uploaded_by', old('user', $currentUserName ?? (auth()->user()->name ?? auth()->user()->email ?? 'System User')));
@endphp
<div class="w-full px-4 sm:px-6 lg:px-8 mt-4 pb-8">
    <div class="bg-white border border-gray-100 rounded-md overflow-hidden">
        @include('company.partials.company-header', ['company' => $company])

        <section class="bg-gray-50 p-4 min-h-[760px]">
            <div id="companyBirTaxApp" class="bg-white border border-gray-100 rounded-xl overflow-hidden" x-data="{ showAddPanel: false }">
                <div x-cloak>
                    <div x-show="showAddPanel" class="fixed inset-0 bg-black/40 z-40" @click="showAddPanel = false"></div>
                    <div
                        x-show="showAddPanel"
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
                            <div>
                                <h2 id="birTaxDrawerTitle" class="text-lg font-semibold">Add BIR & Tax</h2>
                                <p class="text-sm text-gray-500">Records added here are automatically associated with {{ $company->company_name }}.</p>
                            </div>
                            <div class="flex-1"></div>
                            <button class="text-gray-500 hover:text-gray-700" @click="showAddPanel = false" type="button">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <form id="birTaxForm" method="POST" action="{{ route('company.bir-tax.store', $company->id) }}" class="flex min-h-0 flex-1 flex-col">
                            @csrf
                            <input type="hidden" id="birTaxFormMethod" name="_method" value="POST">

                            <div class="p-6 overflow-y-auto space-y-4">
                                <div>
                                    <label class="text-xs text-gray-600">TIN</label>
                                    <input id="birTaxTinInput" name="tin" type="text" value="{{ old('tin') }}" placeholder="e.g., 123-456-789-000" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600">Tax Payer</label>
                                    <input id="birTaxPayerInput" name="tax_payer" type="text" value="{{ old('tax_payer', $company->company_name) }}" placeholder="Tax payer name" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600">Registering Office</label>
                                    <input id="birTaxOfficeInput" name="registering_office" type="text" value="{{ old('registering_office') }}" placeholder="BIR RDO" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600">Registered Address</label>
                                    <input id="birTaxAddressInput" name="registered_address" type="text" value="{{ old('registered_address', $company->address) }}" placeholder="Address" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600">Tax Types</label>
                                    <input id="birTaxTypesInput" name="tax_types" type="text" value="{{ old('tax_types') }}" placeholder="VAT, WHT, etc." class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600">Form Type</label>
                                    <input id="birTaxFormTypeInput" name="form_type" type="text" value="{{ old('form_type') }}" placeholder="Form type" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600">Filing Frequency</label>
                                    <input id="birTaxFrequencyInput" name="filing_frequency" type="text" value="{{ old('filing_frequency') }}" placeholder="Monthly, Quarterly, Annual" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600">Due Date</label>
                                    <input id="birTaxDueDateInput" name="due_date" type="date" value="{{ old('due_date') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600">Uploaded By</label>
                                    <input id="birTaxUploadedByInput" name="uploaded_by" type="text" value="{{ $currentUserNameValue }}" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-100 px-3 py-2 text-sm text-gray-600" readonly>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600">Date Uploaded</label>
                                    <input id="birTaxDateUploadedInput" name="date_uploaded" type="date" value="{{ old('date_uploaded') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600">Upload BIR & Tax Document (PDF)</label>
                                    <input id="birTaxUploadedFileInput" name="uploaded_file" type="text" value="{{ old('uploaded_file') }}" placeholder="document.pdf" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                @if ($errors->any())
                                    <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                        Please complete the required BIR & Tax fields.
                                    </div>
                                @endif
                            </div>

                            <div class="px-6 py-4 border-t border-gray-100 flex items-center gap-2">
                                <button class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-900 text-sm font-medium rounded-lg" @click="showAddPanel = false" type="button">
                                    Cancel
                                </button>
                                <div class="flex-1"></div>
                                <button id="birTaxSubmitButton" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg" type="submit">
                                    Save BIR & Tax
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="flex items-center gap-3 px-4 py-4">
                    <div>
                        <div class="text-lg font-semibold">BIR & Tax</div>
                        <p class="text-sm text-gray-500">Manage BIR and tax records for this company.</p>
                    </div>
                    <div class="flex-1"></div>
                    <button @click="showAddPanel = true; resetBirTaxForm()" class="h-9 px-4 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium flex items-center gap-2" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
                        </svg>
                        Add BIR & Tax
                    </button>
                </div>

                <div class="border-t border-gray-100"></div>

                <form method="GET" action="{{ route('company.bir-tax', $company->id) }}" class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <input type="text" name="search_tin" value="{{ $searchTin }}" placeholder="Search TIN..." class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <input type="text" name="search_taxpayer" value="{{ $searchTaxpayer }}" placeholder="Search taxpayer..." class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <div class="flex gap-2 md:col-span-2">
                            <button type="submit" class="px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Search</button>
                            <a href="{{ route('company.bir-tax', $company->id) }}" class="px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-100">Clear</a>
                        </div>
                    </div>
                </form>

                <div class="p-4">
                    @if (session('bir_tax_success'))
                        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                            {{ session('bir_tax_success') }}
                        </div>
                    @endif

                    <div class="overflow-auto border border-gray-100 rounded-lg">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-200 bg-gray-50">
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">TIN</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Tax Payer</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Registering Office</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Registered Address</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Tax Types</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Form Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Filing Frequency</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Due Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Uploaded By</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Date Uploaded</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm text-gray-900 bg-white">
                                @forelse ($records as $record)
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 font-medium">{{ $record['tin'] }}</td>
                                        <td class="px-4 py-3">{{ $record['tax_payer'] }}</td>
                                        <td class="px-4 py-3">{{ $record['registering_office'] }}</td>
                                        <td class="px-4 py-3">{{ $record['registered_address'] }}</td>
                                        <td class="px-4 py-3">{{ $record['tax_types'] }}</td>
                                        <td class="px-4 py-3">{{ $record['form_type'] }}</td>
                                        <td class="px-4 py-3">{{ $record['filing_frequency'] }}</td>
                                        <td class="px-4 py-3">{{ \Illuminate\Support\Carbon::parse($record['due_date'])->format('M d, Y') }}</td>
                                        <td class="px-4 py-3">{{ $record['uploaded_by'] }}</td>
                                        <td class="px-4 py-3">{{ \Illuminate\Support\Carbon::parse($record['date_uploaded'])->format('M d, Y') }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-end gap-2">
                                                <button type="button" onclick='editBirTaxRecord(@json($record))' class="inline-flex h-8 items-center rounded-full border border-gray-200 px-3 text-xs font-medium text-gray-700 hover:bg-gray-50">Edit</button>
                                                <form method="POST" action="{{ route('company.bir-tax.destroy', [$company->id, $record['id']]) }}" onsubmit="return confirm('Delete this BIR & Tax record?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex h-8 items-center rounded-full border border-red-200 px-3 text-xs font-medium text-red-600 hover:bg-red-50">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="px-4 py-10 text-center text-gray-400 italic">No BIR & Tax records for this company yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
    function resetBirTaxForm() {
        const form = document.getElementById('birTaxForm');
        form.reset();
        form.action = @json(route('company.bir-tax.store', $company->id));
        document.getElementById('birTaxFormMethod').value = 'POST';
        document.getElementById('birTaxDrawerTitle').textContent = 'Add BIR & Tax';
        document.getElementById('birTaxSubmitButton').textContent = 'Save BIR & Tax';
        document.getElementById('birTaxPayerInput').value = @json(old('tax_payer', $company->company_name));
        document.getElementById('birTaxTinInput').value = @json($companyTinValue);
        document.getElementById('birTaxAddressInput').value = @json(old('registered_address', $company->address));
        document.getElementById('birTaxUploadedByInput').value = @json($currentUserNameValue);
    }

    function editBirTaxRecord(record) {
        const container = document.getElementById('companyBirTaxApp');
        resetBirTaxForm();
        document.getElementById('birTaxForm').action = @json(route('company.bir-tax.update', [$company->id, '__RECORD__'])).replace('__RECORD__', record.id);
        document.getElementById('birTaxFormMethod').value = 'PUT';
        document.getElementById('birTaxDrawerTitle').textContent = 'Edit BIR & Tax';
        document.getElementById('birTaxSubmitButton').textContent = 'Update BIR & Tax';
        document.getElementById('birTaxTinInput').value = record.tin ?? '';
        document.getElementById('birTaxPayerInput').value = record.tax_payer ?? '';
        document.getElementById('birTaxOfficeInput').value = record.registering_office ?? '';
        document.getElementById('birTaxAddressInput').value = record.registered_address ?? '';
        document.getElementById('birTaxTypesInput').value = record.tax_types ?? '';
        document.getElementById('birTaxFormTypeInput').value = record.form_type ?? '';
        document.getElementById('birTaxFrequencyInput').value = record.filing_frequency ?? '';
        document.getElementById('birTaxDueDateInput').value = record.due_date ?? '';
        document.getElementById('birTaxUploadedByInput').value = @json($currentUserNameValue);
        document.getElementById('birTaxDateUploadedInput').value = record.date_uploaded ?? '';
        document.getElementById('birTaxUploadedFileInput').value = record.uploaded_file ?? '';
        if (container && container.__x) {
            container.__x.$data.showAddPanel = true;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        @if ($errors->any())
            document.getElementById('companyBirTaxApp')?.__x?.$data && (document.getElementById('companyBirTaxApp').__x.$data.showAddPanel = true);
        @endif
    });
</script>
@endsection

@extends('layouts.app')
@section('title', 'Company LGU')

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
            <div id="companyLguApp" class="bg-white rounded-xl border border-gray-200 flex flex-col min-h-[680px]" x-data="{ showSlideOver: false }">
                <div x-show="showSlideOver" class="fixed inset-0 z-50 overflow-hidden" x-cloak>
                    <div class="absolute inset-0">
                        <div @click="showSlideOver = false" class="absolute inset-0 bg-gray-900 bg-opacity-50"></div>
                        <div class="absolute inset-y-0 right-0 flex max-w-full">
                            <div class="w-screen max-w-md bg-white shadow-2xl flex flex-col h-full"
                                x-transition:enter="transform transition ease-in-out duration-300"
                                x-transition:enter-start="translate-x-full"
                                x-transition:enter-end="translate-x-0"
                                x-transition:leave="transform transition ease-in-out duration-300"
                                x-transition:leave-start="translate-x-0"
                                x-transition:leave-end="translate-x-full">
                                <div class="p-6 border-b flex justify-between">
                                    <div>
                                        <h2 id="lguDrawerTitle" class="font-bold text-lg">Add Permit Entry</h2>
                                        <p class="mt-1 text-sm text-gray-500">Records added here are automatically associated with {{ $company->company_name }}.</p>
                                    </div>
                                    <button type="button" @click="showSlideOver = false" class="text-gray-500 hover:text-gray-700">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>

                                <form id="lguForm" method="POST" action="{{ route('company.lgu.store', $company->id) }}" class="flex min-h-0 flex-1 flex-col">
                                    @csrf
                                    <input type="hidden" id="lguFormMethod" name="_method" value="POST">

                                    <div class="p-6 space-y-4 flex-1 overflow-y-auto">
                                        <div>
                                            <label class="block text-sm font-medium">Permit Type</label>
                                            <select id="permitTypeInput" name="permit_type" class="w-full border rounded-md p-2 bg-white">
                                                @foreach ($permitTypes as $permitType)
                                                    <option value="{{ $permitType }}" @selected($selectedPermit === $permitType)>{{ $permitType }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium">Client</label>
                                            <input class="w-full border rounded-md p-2 bg-gray-100 text-gray-600" value="{{ $company->company_name }}" readonly>
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
                                            <label class="block text-sm font-medium">Date</label>
                                            <input id="dateInput" name="date" type="date" class="w-full border rounded-md p-2" required>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium">Uploader</label>
                                            <input id="userInput" name="user" value="{{ $currentUserNameValue }}" class="w-full border rounded-md p-2 bg-gray-100 text-gray-600" readonly required>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium">TIN</label>
                                            <input id="tinInput" name="tin" value="{{ $companyTinValue }}" class="w-full border rounded-md p-2 bg-gray-50" placeholder="TIN" required>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium">Registration Status</label>
                                            <select id="regInput" name="reg" class="w-full border rounded-md p-2 bg-white">
                                                @foreach (['Renewed', 'Pending', 'Expired', 'Active'] as $registrationStatus)
                                                    <option value="{{ $registrationStatus }}">{{ $registrationStatus }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium">Status</label>
                                            <select id="statusInput" name="status" class="w-full border rounded-md p-2 bg-white">
                                                @foreach (['Active', 'For Review', 'Overdue'] as $status)
                                                    <option value="{{ $status }}">{{ $status }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="p-6 border-t flex gap-3">
                                        <button type="button" @click="showSlideOver = false" class="flex-1 border py-2 rounded">
                                            Cancel
                                        </button>
                                        <button type="submit" id="lguSubmitButton" class="flex-1 bg-blue-600 text-white py-2 rounded">
                                            Save
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between px-4 py-3 border-b shrink-0">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-gray-900">LGU</h2>
                        <p class="mt-1 text-sm text-gray-500">Manage local government unit records for this company.</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <button id="permitDropdownBtn" class="flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-md font-medium hover:bg-gray-200">
                                <span id="selectedPermit">{{ $selectedPermit }}</span> <span>▾</span>
                            </button>
                            <div id="permitMenu" class="hidden absolute left-0 mt-2 w-56 bg-white border shadow-xl rounded-md z-50 py-1">
                                @foreach ($permitTypes as $permitType)
                                    <a href="{{ route('company.lgu', ['company' => $company->id, 'permit' => $permitType, 'status' => $selectedStatus]) }}" class="block px-4 py-2 hover:bg-gray-100 cursor-pointer">{{ $permitType }}</a>
                                @endforeach
                            </div>
                        </div>

                        <button @click="showSlideOver = true; resetLguForm()" class="bg-blue-600 text-white px-6 py-2 rounded text-sm">
                            + Add
                        </button>
                    </div>
                </div>

                @if (session('lgu_success'))
                    <div class="mx-4 mt-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                        {{ session('lgu_success') }}
                    </div>
                @endif

                <div class="p-4 flex-grow overflow-hidden">
                    <div class="border rounded-md h-full overflow-auto">
                        <table class="w-full text-sm table-fixed border-collapse">
                            <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                                <tr>
                                    <th id="sortDate" class="w-32 p-3 text-left cursor-pointer select-none hover:text-blue-600">Date <span id="dateIcon">↓</span></th>
                                    <th id="sortUploader" class="w-32 p-3 text-left cursor-pointer select-none hover:text-blue-600">Uploader <span id="userIcon">↓</span></th>
                                    <th class="w-24 p-3 text-left">TIN</th>
                                    <th class="w-32 p-3 text-left">Reg Status</th>
                                    <th class="w-32 p-3 text-left relative overflow-visible">
                                        <button id="statusFilterBtn" class="flex items-center gap-1 hover:text-blue-600 font-bold">Status ▾</button>
                                        <div id="statusMenu" class="hidden absolute left-0 mt-2 w-36 bg-white border shadow-xl rounded-md z-50 py-1">
                                            @foreach (['all' => 'Show All', 'Active' => 'Active', 'For Review' => 'For Review', 'Overdue' => 'Overdue'] as $value => $label)
                                                <a href="{{ route('company.lgu', ['company' => $company->id, 'permit' => $selectedPermit, 'status' => $value]) }}" class="block px-4 py-2 hover:bg-gray-100 cursor-pointer {{ $value === 'Active' ? 'text-green-600' : ($value === 'For Review' ? 'text-yellow-600' : ($value === 'Overdue' ? 'text-red-600' : '')) }}">{{ $label }}</a>
                                            @endforeach
                                        </div>
                                    </th>
                                    <th class="w-36 p-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody" class="bg-white">
                                @forelse ($records as $record)
                                    @php
                                        $statusClass = $record['status'] === 'Active' ? 'text-green-600' : ($record['status'] === 'Overdue' ? 'text-red-600' : 'text-yellow-600');
                                        $dotClass = $record['status'] === 'Active' ? 'bg-green-500' : ($record['status'] === 'Overdue' ? 'bg-red-500' : 'bg-yellow-500');
                                    @endphp
                                    <tr class="border-t hover:bg-gray-50" data-record='@json($record)'>
                                        <td class="p-3">{{ $record['date'] }}</td>
                                        <td class="p-3">{{ $record['user'] }}</td>
                                        <td class="p-3">{{ $record['tin'] }}</td>
                                        <td class="p-3">{{ $record['reg'] }}</td>
                                        <td class="p-3">
                                            <span class="status-val flex items-center gap-1.5 {{ $statusClass }}"><span class="w-2 h-2 {{ $dotClass }} rounded-full"></span>{{ $record['status'] }}</span>
                                        </td>
                                        <td class="p-3">
                                            <div class="flex items-center justify-end gap-2">
                                                <button type="button" class="inline-flex h-8 items-center rounded-full border border-gray-200 px-3 text-xs font-medium text-gray-700 hover:bg-gray-50" onclick="editLguRecord(@js($record))">
                                                    Edit
                                                </button>
                                                <form method="POST" action="{{ route('company.lgu.destroy', [$company->id, $record['id']]) }}" onsubmit="return confirm('Delete this LGU record?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="permit" value="{{ $selectedPermit }}">
                                                    <button type="submit" class="inline-flex h-8 items-center rounded-full border border-red-200 px-3 text-xs font-medium text-red-600 hover:bg-red-50">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="p-10 text-center text-gray-400 italic">
                                            No LGU records for this company yet.
                                        </td>
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
    document.addEventListener('DOMContentLoaded', function () {
        const permitDropdownBtn = document.getElementById('permitDropdownBtn');
        const permitMenu = document.getElementById('permitMenu');
        const statusFilterBtn = document.getElementById('statusFilterBtn');
        const statusMenu = document.getElementById('statusMenu');
        const tableBody = document.getElementById('tableBody');
        const rows = Array.from(tableBody.querySelectorAll('tr[data-record]'));
        let sortDirs = { date: true, user: true };

        permitDropdownBtn?.addEventListener('click', function (event) {
            event.stopPropagation();
            permitMenu.classList.toggle('hidden');
        });

        statusFilterBtn?.addEventListener('click', function (event) {
            event.stopPropagation();
            statusMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', function () {
            permitMenu?.classList.add('hidden');
            statusMenu?.classList.add('hidden');
        });

        const sortRows = (key, iconId) => {
            const sorted = rows.sort((a, b) => {
                const recordA = JSON.parse(a.dataset.record);
                const recordB = JSON.parse(b.dataset.record);
                const first = String(recordA[key] ?? '');
                const second = String(recordB[key] ?? '');

                return sortDirs[key] ? first.localeCompare(second) : second.localeCompare(first);
            });

            sortDirs[key] = !sortDirs[key];
            document.getElementById(iconId).textContent = sortDirs[key] ? '↓' : '↑';
            sorted.forEach((row) => tableBody.appendChild(row));
        };

        document.getElementById('sortDate')?.addEventListener('click', function () {
            sortRows('date', 'dateIcon');
        });

        document.getElementById('sortUploader')?.addEventListener('click', function () {
            sortRows('user', 'userIcon');
        });

        @if ($errors->any())
            document.getElementById('companyLguApp')?.__x?.$data && (document.getElementById('companyLguApp').__x.$data.showSlideOver = true);
        @endif
    });

    function resetLguForm() {
        const form = document.getElementById('lguForm');
        form.reset();
        form.action = @json(route('company.lgu.store', $company->id));
        document.getElementById('lguFormMethod').value = 'POST';
        document.getElementById('lguDrawerTitle').textContent = 'Add Permit Entry';
        document.getElementById('lguSubmitButton').textContent = 'Save';
        document.getElementById('permitTypeInput').value = @json($selectedPermit);
        document.getElementById('tinInput').value = @json($companyTinValue);
        document.getElementById('userInput').value = @json($currentUserNameValue);
    }

    function editLguRecord(record) {
        const container = document.getElementById('companyLguApp');
        resetLguForm();
        document.getElementById('lguForm').action = @json(route('company.lgu.update', [$company->id, '__RECORD__'])).replace('__RECORD__', record.id);
        document.getElementById('lguFormMethod').value = 'PUT';
        document.getElementById('lguDrawerTitle').textContent = 'Edit Permit Entry';
        document.getElementById('lguSubmitButton').textContent = 'Update';
        document.getElementById('permitTypeInput').value = record.permit_type;
        document.getElementById('dateInput').value = record.date;
        document.getElementById('userInput').value = @json($currentUserNameValue);
        document.getElementById('tinInput').value = record.tin;
        document.getElementById('regInput').value = record.reg;
        document.getElementById('statusInput').value = record.status;
        if (container && container.__x) {
            container.__x.$data.showSlideOver = true;
        }
    }
</script>
@endsection

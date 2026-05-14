@extends('layouts.app')

@section('content')
<div class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col">

    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">

        <!-- HEADER -->
        <div class="flex items-center justify-between px-4 py-3 border-b">
            <h1 class="text-lg font-semibold text-gray-900">
                Awards & Certificates
            </h1>
        </div>

        <!-- FILTER BAR (Admin/SuperAdmin Only) -->
        @if($isAdmin)
            <div class="px-4 py-3 border-b bg-gray-50">
                <div class="flex items-center gap-4">
                    <label for="employeeFilter" class="text-sm font-medium text-gray-700">
                        Filter by Employee:
                    </label>
                    <select 
                        id="employeeFilter"
                        onchange="filterByEmployee(this.value)"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="">All Employees</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ $selectedEmployeeId == $employee->id ? 'selected' : '' }}>
                                {{ $employee->first_name }} {{ $employee->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif

        <!-- CONTENT -->
        <div class="p-6 overflow-auto">

            <!-- TABLE -->
            <div class="border rounded-xl overflow-hidden">

                <table class="w-full text-sm">

                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="p-3 text-left">Employee</th>
                            <th class="p-3 text-left">Award / Training</th>
                            <th class="p-3 text-left">Certificate Code</th>
                            <th class="p-3 text-left">Issued Date</th>
                            <th class="p-3 text-left">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($awards as $award)

                            <tr class="border-t hover:bg-gray-50">

                                <!-- Employee -->
                                <td class="p-3 font-medium text-gray-900">
                                    {{ $award->employee->full_name ?? 'N/A' }}
                                </td>

                                <!-- Training / Award name -->
                                <td class="p-3">
                                    <div class="font-semibold text-gray-800">
                                        {{ $award->training->title ?? 'Training' }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        Completed Training Award
                                    </div>
                                </td>

                                <!-- Certificate Code -->
                                <td class="p-3 text-gray-700">
                                    {{ $award->certificate_code }}
                                </td>

                                <!-- Issued Date -->
                                <td class="p-3 text-gray-700">
                                    {{ $award->issued_at ? $award->issued_at->format('F d, Y') : '-' }}
                                </td>

                                <!-- Actions -->
                                <td class="p-3">

                                    <div class="flex gap-3">

                                        <!-- View Certificate -->
                                        <button
                                            onclick="openCertificate({{ $award->id }})"
                                            class="text-blue-600 hover:underline text-xs font-semibold"
                                        >
                                            View Certificate
                                        </button>

                                        <!-- View Details (optional future modal) -->
                                        <button
                                            class="text-gray-600 hover:underline text-xs font-semibold"
                                        >
                                            Details
                                        </button>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="5" class="p-6 text-center text-gray-400">
                                    No certificates issued yet.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>
</div>

<!-- SIMPLE CERTIFICATE MODAL -->
<div id="certificateModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white w-[800px] p-10 rounded-xl relative">

        <button onclick="closeCertificate()" class="absolute top-3 right-3 text-gray-500 text-xl">
            ✕
        </button>

        <div id="certificateContent">
            <!-- Filled by JS -->
        </div>

    </div>
</div>

<script>
function openCertificate(id) {
    const awards = @json($awards);

    const award = awards.find(a => a.id === id);

    if (!award) return;

    document.getElementById('certificateContent').innerHTML = `
        <div class="text-center">

            <img src="/images/jk-logo-template.png" class="h-20 mx-auto mb-4">

            <h2 class="text-2xl font-bold">John Kelly & Company</h2>

            <p class="text-sm text-gray-600 mt-1">Certificate of Completion</p>

            <div class="mt-6">This is to certify that</div>

            <div class="text-2xl font-bold text-blue-700 mt-2">
                ${award.employee?.full_name ?? 'Employee'}
            </div>

            <div class="mt-3">has successfully completed</div>

            <div class="font-semibold text-lg mt-2">
                ${award.training?.title ?? 'Training'}
            </div>

            <div class="mt-6 text-sm text-gray-500">
                Certificate Code: ${award.certificate_code}
            </div>

            <div class="mt-2 text-sm text-gray-500">
                Issued: ${award.issued_at ?? '-'}
            </div>

        </div>
    `;

    document.getElementById('certificateModal').classList.remove('hidden');
}

function closeCertificate() {
    document.getElementById('certificateModal').classList.add('hidden');
}

function filterByEmployee(employeeId) {
    const params = new URLSearchParams();
    if (employeeId) {
        params.append('employee_id', employeeId);
    }
    const queryString = params.toString();
    window.location.href = `{{ route('human-capital.awards') }}${queryString ? '?' + queryString : ''}`;
}
</script>

@endsection
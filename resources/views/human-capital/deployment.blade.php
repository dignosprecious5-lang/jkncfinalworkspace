@extends('layouts.app')

@section('content')
<div
    class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col"
    x-data="deploymentPage()"
>
    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">

        <div class="flex items-center justify-between px-4 py-3 border-b shrink-0 gap-4">
            <div class="flex items-center flex-1 min-w-0">
                <h1 class="text-lg font-semibold text-gray-900">Deployment</h1>
            </div>

            <button
                type="button"
                @click="openAdd()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm shrink-0 font-semibold"
            >
                + Add
            </button>
        </div>

        @if (session('success'))
            <div class="mx-4 mt-4 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <div class="p-4 flex-grow overflow-hidden">
            <div class="border rounded-xl h-full overflow-auto bg-white">
                <table class="w-full text-sm min-w-[1200px] border-collapse">
                    <thead class="bg-gray-50 text-gray-600 sticky top-0 z-20">
                        <tr>
                            <th class="p-3 text-left">Employee ID</th>
                            <th class="p-3 text-left">Employee Name</th>
                            <th class="p-3 text-left">Position</th>
                            <th class="p-3 text-left">Branch</th>
                            <th class="p-3 text-left">Office</th>
                            <th class="p-3 text-left">Department</th>
                            <th class="p-3 text-left">Manager</th>
                            <th class="p-3 text-left">Deployment Date</th>
                            <th class="p-3 text-left">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white">
                        <template x-if="deployments.length === 0">
                            <tr>
                                <td colspan="10" class="p-10 text-center text-gray-400">
                                    No deployment records yet. Click + Add to assign an employee.
                                </td>
                            </tr>
                        </template>

                        <template x-for="deployment in deployments" :key="deployment.id">
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-3 font-semibold text-blue-700" x-text="deployment.employee_code || '-'"></td>
                                <td class="p-3 font-medium text-gray-900" x-text="deployment.employee_name || '-'"></td>
                                <td class="p-3 text-gray-700" x-text="deployment.position || '-'"></td>
                                <td class="p-3 text-gray-700" x-text="deployment.branch_name || '-'"></td>
                                <td class="p-3 text-gray-700" x-text="deployment.office_name || '-'"></td>
                                <td class="p-3 text-gray-700" x-text="deployment.department_name || '-'"></td>
                                <td class="p-3 text-gray-700" x-text="deployment.reporting_manager || '-'"></td>
                                <td class="p-3 text-gray-700" x-text="deployment.deployment_date || '-'"></td>
                                <td class="p-3">
                                    <span
                                        class="px-2 py-1 rounded-full text-xs font-semibold"
                                        :class="statusClass(deployment.status)"
                                        x-text="deployment.status"
                                    ></span>
                                </td>
                                <td class="p-3 text-right whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="openView(deployment)"
                                        class="text-indigo-600 hover:underline text-xs font-semibold mr-3"
                                    >
                                        View
                                    </button>

                                    <button
                                        type="button"
                                        @click="openEdit(deployment)"
                                        class="text-blue-600 hover:underline text-xs font-semibold mr-3"
                                    >
                                        Edit
                                    </button>

                                    <form
                                        :action="`{{ url('/human-capital/deployment') }}/${deployment.id}`"
                                        method="POST"
                                        class="inline"
                                        onsubmit="return confirm('Delete this deployment record?')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:underline text-xs font-semibold">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ADD / EDIT MODAL --}}
    <div
        x-show="showForm"
        x-transition.opacity
        class="fixed inset-0 z-50 flex justify-end bg-black/40"
        style="display: none;"
        @click.self="closeForm()"
    >
        <div class="w-full max-w-[560px] bg-white h-full shadow-xl flex flex-col">
            <div class="px-5 py-4 bg-blue-700 text-white flex items-center justify-between">
                <div>
                    <h2 class="font-bold uppercase tracking-wider" x-text="isEdit ? 'Edit Deployment' : 'New Deployment'"></h2>
                    <p class="text-xs text-blue-100">Assign registered employee to work deployment</p>
                </div>

                <button type="button" @click="closeForm()" class="text-white text-xl leading-none">&times;</button>
            </div>

            <form
                method="POST"
                :action="isEdit ? `{{ url('/human-capital/deployment') }}/${form.id}` : '{{ route('human-capital.deployment.store') }}'"
                class="flex-1 overflow-y-auto p-5 space-y-4"
            >
                @csrf

                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Employee <span class="text-red-500">*</span>
                    </label>

                    <select
                        name="employee_id"
                        x-model="form.employee_id"
                        @change="selectEmployee()"
                        :disabled="isEdit"
                        required
                        class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none bg-white disabled:bg-gray-100"
                    >
                        <option value="">Select employee...</option>
                        <template x-for="employee in employees" :key="employee.id">
                            <option
                                :value="employee.id"
                                x-text="`${employee.employee_code} - ${employee.full_name}`"
                            ></option>
                        </template>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Employee ID</label>
                        <input type="text" x-model="preview.employee_code" readonly class="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg bg-gray-100">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Position</label>
                        <input type="text" x-model="preview.position" readonly class="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg bg-gray-100">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Branch</label>
                        <input type="text" x-model="preview.branch_name" readonly class="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg bg-gray-100">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Office</label>
                        <input type="text" x-model="preview.office_name" readonly class="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg bg-gray-100">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Department</label>
                        <input type="text" x-model="preview.department_name" readonly class="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg bg-gray-100">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Unit</label>
                        <input type="text" x-model="preview.unit_name" readonly class="w-full text-sm px-3 py-2 border border-gray-200 rounded-lg bg-gray-100">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Deployment Date</label>
                        <input type="date" name="deployment_date" x-model="form.deployment_date" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Deployment Type</label>
                        <select name="deployment_type" x-model="form.deployment_type" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white">
                            <option>Initial Deployment</option>
                            <option>Reassignment</option>
                            <option>Temporary Assignment</option>
                            <option>Transfer</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Reporting Manager</label>
                    <input type="text" name="reporting_manager" x-model="form.reporting_manager" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="status" x-model="form.status" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white">
                        <option>Pending Deployment</option>
                        <option>Deployed</option>
                        <option>Reassigned</option>
                        <option>Cancelled</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Remarks</label>
                    <textarea name="remarks" x-model="form.remarks" rows="4" class="w-full text-sm px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>

                <div class="pt-4 border-t flex justify-end gap-3">
                    <button type="button" @click="closeForm()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">
                        Cancel
                    </button>

                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold">
                        Save Deployment
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- VIEW MODAL --}}
    <div
        x-show="showView"
        x-transition.opacity
        class="fixed inset-0 z-50 flex justify-end bg-black/40"
        style="display: none;"
        @click.self="showView = false"
    >
        <div class="w-full max-w-[560px] bg-white h-full shadow-xl flex flex-col">
            <div class="px-5 py-4 bg-indigo-700 text-white flex items-center justify-between">
                <div>
                    <h2 class="font-bold uppercase tracking-wider">Deployment Details</h2>
                    <p class="text-xs text-indigo-100" x-text="selected?.employee_code"></p>
                </div>
                <button type="button" @click="showView = false" class="text-white text-xl leading-none">&times;</button>
            </div>

            <template x-if="selected">
                <div class="p-5 space-y-4 overflow-y-auto">
                    <div class="rounded-xl border border-gray-200 p-4 bg-gray-50">
                        <p class="text-xs font-bold text-gray-400 uppercase">Employee</p>
                        <p class="text-xl font-bold text-gray-900" x-text="selected.employee_name"></p>
                        <p class="text-sm text-gray-500" x-text="selected.position || '-'"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="border rounded-lg p-4">
                            <p class="text-xs font-bold text-gray-400 uppercase">Branch</p>
                            <p class="font-semibold" x-text="selected.branch_name || '-'"></p>
                        </div>

                        <div class="border rounded-lg p-4">
                            <p class="text-xs font-bold text-gray-400 uppercase">Office</p>
                            <p class="font-semibold" x-text="selected.office_name || '-'"></p>
                        </div>

                        <div class="border rounded-lg p-4">
                            <p class="text-xs font-bold text-gray-400 uppercase">Department</p>
                            <p class="font-semibold" x-text="selected.department_name || '-'"></p>
                        </div>

                        <div class="border rounded-lg p-4">
                            <p class="text-xs font-bold text-gray-400 uppercase">Unit</p>
                            <p class="font-semibold" x-text="selected.unit_name || '-'"></p>
                        </div>

                        <div class="border rounded-lg p-4">
                            <p class="text-xs font-bold text-gray-400 uppercase">Deployment Date</p>
                            <p class="font-semibold" x-text="selected.deployment_date || '-'"></p>
                        </div>

                        <div class="border rounded-lg p-4">
                            <p class="text-xs font-bold text-gray-400 uppercase">Status</p>
                            <p class="font-semibold" x-text="selected.status || '-'"></p>
                        </div>
                    </div>

                    <div class="border rounded-lg p-4">
                        <p class="text-xs font-bold text-gray-400 uppercase">Reporting Manager</p>
                        <p class="font-semibold" x-text="selected.reporting_manager || '-'"></p>
                    </div>

                    <div class="border rounded-lg p-4">
                        <p class="text-xs font-bold text-gray-400 uppercase">Remarks</p>
                        <p class="text-sm text-gray-700" x-text="selected.remarks || '-'"></p>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function deploymentPage() {
    return {
        employees: @json($employees),
        deployments: @json($deployments),

        showForm: false,
        showView: false,
        isEdit: false,
        selected: null,

        form: {
            id: null,
            employee_id: '',
            deployment_date: '',
            reporting_manager: '',
            deployment_type: 'Initial Deployment',
            status: 'Pending Deployment',
            remarks: '',
        },

        preview: {
            employee_code: '',
            position: '',
            branch_name: '',
            office_name: '',
            department_name: '',
            unit_name: '',
        },

        openAdd() {
            this.isEdit = false;
            this.resetForm();
            this.showForm = true;
        },

        openEdit(deployment) {
            this.isEdit = true;
            this.form = {
                id: deployment.id,
                employee_id: deployment.employee_id,
                deployment_date: deployment.deployment_date || '',
                reporting_manager: deployment.reporting_manager || '',
                deployment_type: deployment.deployment_type || 'Initial Deployment',
                status: deployment.status || 'Pending Deployment',
                remarks: deployment.remarks || '',
            };

            this.preview = {
                employee_code: deployment.employee_code || '',
                position: deployment.position || '',
                branch_name: deployment.branch_name || '',
                office_name: deployment.office_name || '',
                department_name: deployment.department_name || '',
                unit_name: deployment.unit_name || '',
            };

            this.showForm = true;
        },

        openView(deployment) {
            this.selected = deployment;
            this.showView = true;
        },

        closeForm() {
            this.showForm = false;
            this.resetForm();
        },

        resetForm() {
            this.form = {
                id: null,
                employee_id: '',
                deployment_date: '',
                reporting_manager: '',
                deployment_type: 'Initial Deployment',
                status: 'Pending Deployment',
                remarks: '',
            };

            this.preview = {
                employee_code: '',
                position: '',
                branch_name: '',
                office_name: '',
                department_name: '',
                unit_name: '',
            };
        },

        selectEmployee() {
            const employee = this.employees.find(item => String(item.id) === String(this.form.employee_id));

            if (!employee) {
                this.preview = {
                    employee_code: '',
                    position: '',
                    branch_name: '',
                    office_name: '',
                    department_name: '',
                    unit_name: '',
                };
                return;
            }

            this.preview = {
                employee_code: employee.employee_code || '',
                position: employee.position || '',
                branch_name: employee.branch_name || '',
                office_name: employee.office_name || '',
                department_name: employee.department_name || '',
                unit_name: employee.unit_name || '',
            };
        },

        statusClass(status) {
            if (status === 'Deployed') return 'bg-green-100 text-green-700';
            if (status === 'Reassigned') return 'bg-purple-100 text-purple-700';
            if (status === 'Cancelled') return 'bg-red-100 text-red-700';
            return 'bg-yellow-100 text-yellow-700';
        },
    }
}
</script>
@endsection